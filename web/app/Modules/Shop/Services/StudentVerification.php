<?php

namespace App\Modules\Shop\Services;

use App\Libraries\Mailer;

/**
 * Automated student verification through SheerID.
 *
 *   1. start(): our server creates a verification for the signed-in user
 *      (POST /rest/v2/verification, tagged with their user id) and sends
 *      them to SheerID's hosted form for that verification.
 *   2. SheerID checks current enrolment electronically.
 *   3. SheerID calls our webhook (signed with HMAC-SHA256); we then read the
 *      result from GET /rest/v2/verification/{id}/details. The same check
 *      runs when the student returns to our site, so it works even if the
 *      webhook is delayed.
 *   4. Verified students may buy the student price of a membership plan
 *      until their status expires (end of the academic year by default).
 *
 * @see https://developer.sheerid.com/tutorials/secure-verification-creation
 * @see https://developer.sheerid.com/tutorials/verifications/webhooks
 */
class StudentVerification
{
    private const API = 'https://services.sheerid.com/rest/v2';

    public static function programId(): string
    {
        return trim((string) site_setting('sheerid_program_id', ''));
    }

    private static function token(): string
    {
        return trim(Mailer::secret('sheerid_access_token'));
    }

    /**
     * Ready to verify students (program ID and access token saved in Studio).
     */
    public static function isConfigured(): bool
    {
        return self::programId() !== '' && self::token() !== '';
    }

    /**
     * The user's current, unexpired verified record, if any.
     */
    public static function current(int $userId): ?array
    {
        return db_connect()->table('student_verifications')->where(['user_id' => $userId, 'status' => 'verified'])
            ->where('expires_at >', date('Y-m-d H:i:s'))->orderBy('expires_at', 'DESC')->get()->getRowArray() ?: null;
    }

    public static function isVerified(int $userId): bool
    {
        return self::current($userId) !== null;
    }

    /**
     * The latest attempt (any status), for the account page.
     */
    public static function latest(int $userId): ?array
    {
        return db_connect()->table('student_verifications')->where('user_id', $userId)->orderBy('id', 'DESC')->get()->getRowArray() ?: null;
    }

    /**
     * Creates a SheerID verification for the user and returns the hosted
     * form URL to send them to.
     */
    public static function start(int $userId, string $email): string
    {
        if (! self::isConfigured()) {
            throw new \RuntimeException('Student verification is not set up yet.');
        }
        $res = self::http('POST', '/verification', [
            'programId'      => self::programId(),
            'trackingId'     => 'user-' . $userId,
            'externalUserId' => (string) $userId,
            'installPageUrl' => site_url('account/student'),
            'locale'         => 'en-US',
        ]);
        $id = (string) ($res['verificationId'] ?? '');
        if ($id === '') {
            throw new \RuntimeException('SheerID did not start a verification: ' . ($res['systemErrorMessage'] ?? 'unknown error'));
        }
        $now = date('Y-m-d H:i:s');
        db_connect()->table('student_verifications')->insert([
            'user_id' => $userId, 'provider' => 'sheerid', 'external_id' => $id, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
        ]);

        return 'https://services.sheerid.com/verify/' . rawurlencode(self::programId()) . '/?verificationId=' . rawurlencode($id);
    }

    /**
     * Webhook entry point. Returns false when the signature is wrong.
     */
    public static function handleWebhook(string $rawBody, string $signature): bool
    {
        $secret = trim(Mailer::secret('sheerid_webhook_secret'));
        if ($secret === '' || $signature === '' || ! hash_equals(hash_hmac('sha256', $rawBody, $secret), strtolower(trim($signature)))) {
            return false;
        }
        $payload = json_decode($rawBody, true);
        $id      = (string) ($payload['verificationId'] ?? '');
        if ($id !== '') {
            self::refresh($id);
        }

        return true;
    }

    /**
     * Pulls the verification's result from SheerID and records it.
     */
    public static function refresh(string $verificationId): ?array
    {
        $db  = db_connect();
        $row = $db->table('student_verifications')->where(['provider' => 'sheerid', 'external_id' => $verificationId])->get()->getRowArray();
        if (! $row || ! self::isConfigured()) {
            return $row ?: null;
        }
        $details = self::http('GET', '/verification/' . rawurlencode($verificationId) . '/details');
        $last    = (array) ($details['lastResponse'] ?? []);
        $step    = (string) ($last['currentStep'] ?? '');
        // Guard against a verification from another program or another user.
        if (($details['programId'] ?? self::programId()) !== self::programId()
            || (isset($details['trackingId']) && $details['trackingId'] !== 'user-' . $row['user_id'])) {
            return $row;
        }
        $org    = $details['personInfo']['organization']['name'] ?? null;
        $update = ['segment' => $last['subSegment'] ?? ($last['segment'] ?? null), 'institution' => $org ? mb_substr((string) $org, 0, 200) : $row['institution'], 'updated_at' => date('Y-m-d H:i:s')];
        if ($step === 'success' && $row['status'] !== 'verified') {
            $update += ['status' => 'verified', 'verified_at' => date('Y-m-d H:i:s'), 'expires_at' => self::expiryFrom(time()), 'detail' => null, 'notified_at' => null];
        } elseif ($step === 'error') {
            $update += ['status' => 'rejected', 'detail' => mb_substr(implode(', ', (array) ($last['errorIds'] ?? [])) ?: 'Not eligible', 0, 255)];
        } elseif ($step !== 'success') {
            $update += ['detail' => $step !== '' ? 'In progress: ' . $step : null];
        }
        $db->table('student_verifications')->where('id', $row['id'])->update($update);

        return $db->table('student_verifications')->where('id', $row['id'])->get()->getRowArray();
    }

    /**
     * Verified status lasts until 31 December of the academic year it was
     * granted in (South African academic years follow the calendar year),
     * or 12 months if set in Studio.
     */
    public static function expiryFrom(int $ts): string
    {
        if (site_setting('student_validity') === '12_months') {
            return date('Y-m-d H:i:s', strtotime('+12 months', $ts));
        }

        return date('Y', $ts) . '-12-31 23:59:59';
    }

    /**
     * Cron: expires lapsed records, moves their PayFast subscriptions to the
     * standard price and tells the member (once, with 14 days' warning).
     *
     * @return array<string,int>
     */
    public static function checkAll(): array
    {
        $db   = db_connect();
        $done = ['warned' => 0, 'expired' => 0, 'repriced' => 0];
        $soon = date('Y-m-d H:i:s', strtotime('+14 days'));
        foreach ($db->table('student_verifications')->where('status', 'verified')->where('expires_at <=', $soon)->where('notified_at IS NULL')->get()->getResultArray() as $v) {
            if (strtotime($v['expires_at']) > time()) {
                \App\Libraries\Notify::studentStatus('expiring', (int) $v['user_id'], $v);
                $db->table('student_verifications')->where('id', $v['id'])->update(['notified_at' => date('Y-m-d H:i:s')]);
                $done['warned']++;
            }
        }
        foreach ($db->table('student_verifications')->where('status', 'verified')->where('expires_at <=', date('Y-m-d H:i:s'))->get()->getResultArray() as $v) {
            $db->table('student_verifications')->where('id', $v['id'])->update(['status' => 'expired', 'updated_at' => date('Y-m-d H:i:s')]);
            $done['expired']++;
            if (self::isVerified((int) $v['user_id'])) {
                continue; // they already re-verified
            }
            $done['repriced'] += Membership::endStudentPricing((int) $v['user_id']) ? 1 : 0;
            \App\Libraries\Notify::studentStatus('expired', (int) $v['user_id'], $v);
        }

        return $done;
    }

    /**
     * @return array<string,mixed>
     */
    private static function http(string $method, string $path, ?array $json = null): array
    {
        if (ENVIRONMENT === 'testing' && isset($GLOBALS['sheerid_fake'])) {
            return ($GLOBALS['sheerid_fake'])($method, $path, $json);
        }
        $options = ['headers' => ['Authorization' => 'Bearer ' . self::token(), 'Accept' => 'application/json']];
        if ($json !== null) {
            $options['json'] = $json;
        }
        try {
            $response = service('curlrequest', ['timeout' => 20, 'http_errors' => false], null, null, false)->request($method, self::API . $path, $options);
            $data     = json_decode((string) $response->getBody(), true);

            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            log_message('error', 'SheerID ' . $method . ' ' . $path . ' failed: ' . $e->getMessage());

            return [];
        }
    }
}
