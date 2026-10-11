<?php

namespace App\Libraries;

use App\Modules\Core\Models\AppSettingModel;
use Config\Services;

/**
 * Branded transactional email.
 *
 * Messages are rendered immediately and stored in `email_queue`; the cron
 * job (`php spark app:cron`, every 5 minutes) delivers them so pages never
 * wait on the mail server. Until cron has been set up, messages are sent
 * straight away instead, so nothing is lost on a fresh install.
 *
 * Transport comes from .env (defaults to the server's own mail, which works
 * on cPanel). For better inbox placement use your domain's SMTP account:
 *   email.protocol = smtp
 *   email.SMTPHost = mail.yourdomain.com
 *   email.SMTPUser = hello@yourdomain.com
 *   email.SMTPPass = ...
 *   email.SMTPPort = 465
 *   email.SMTPCrypto = ssl
 */
class Mailer
{
    public const MAX_ATTEMPTS = 3;

    /**
     * Renders the branded message template and queues it.
     *
     * @param array{heading?:string, preheader?:string, paragraphs?:list<string>, rows?:array<string,string>, items?:list<array>, totals?:array<string,string>, button?:array{0:string,1:string}, secondary?:array{0:string,1:string}, note?:string, footer?:string, tone?:string} $content
     */
    public static function queue(string $template, string $toEmail, ?string $toName, string $subject, array $content, ?int $userId = null): ?int
    {
        if (! filter_var($toEmail, FILTER_VALIDATE_EMAIL) || str_ends_with($toEmail, '@invalid')) {
            return null;
        }
        $html = view('emails/message', $content + ['subject' => $subject, 'toName' => $toName], ['saveData' => false]);
        $db   = db_connect();
        $db->table('email_queue')->insert([
            'user_id'    => $userId,
            'to_email'   => $toEmail,
            'to_name'    => $toName,
            'template'   => $template,
            'subject'    => $subject,
            'html'       => $html,
            'text'       => self::plainText($content, $subject),
            'status'     => 'queued',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();

        if (! self::cronIsRunning() && ENVIRONMENT !== 'testing') {
            self::deliver($id);
        }

        return $id;
    }

    /**
     * Sends queued messages. Returns [sent, failed].
     *
     * @return array{0:int,1:int}
     */
    public static function flush(int $limit = 50): array
    {
        $ids  = array_column(db_connect()->table('email_queue')->select('id')->where('status', 'queued')->orderBy('id')->limit($limit)->get()->getResultArray(), 'id');
        $sent = $failed = 0;
        foreach ($ids as $id) {
            self::deliver((int) $id) ? $sent++ : $failed++;
        }

        return [$sent, $failed];
    }

    public static function deliver(int $id): bool
    {
        $db  = db_connect();
        $row = $db->table('email_queue')->where('id', $id)->get()->getRowArray();
        if (! $row || $row['status'] === 'sent') {
            return true;
        }
        $email = Services::email(self::transport(), false);
        $email->setMailType('html');
        $email->setFrom(self::fromEmail(), self::fromName());
        if ($reply = site_setting('mail_reply_to')) {
            $email->setReplyTo($reply);
        }
        $email->setTo($row['to_email']);
        $email->setSubject($row['subject']);
        $email->setMessage($row['html']);
        $email->setAltMessage((string) $row['text']);

        $ok = ENVIRONMENT === 'testing' ? true : $email->send(false);
        $attempts = (int) $row['attempts'] + 1;
        $db->table('email_queue')->where('id', $id)->update($ok
            ? ['status' => 'sent', 'attempts' => $attempts, 'sent_at' => date('Y-m-d H:i:s'), 'last_error' => null]
            : ['status' => $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'queued', 'attempts' => $attempts, 'last_error' => mb_substr(strip_tags((string) $email->printDebugger(['headers'])), 0, 250)]);

        return $ok;
    }

    /**
     * SMTP details from Studio → Settings → Emails take precedence over .env.
     */
    public static function transport(): array
    {
        $config = (array) config('Email');
        $host   = trim((string) site_setting('smtp_host'));
        if ($host !== '') {
            $config = array_merge($config, [
                'protocol'   => 'smtp',
                'SMTPHost'   => $host,
                'SMTPUser'   => (string) site_setting('smtp_user'),
                'SMTPPass'   => self::secret('smtp_password'),
                'SMTPPort'   => (int) (site_setting('smtp_port') ?: 465),
                'SMTPCrypto' => site_setting('smtp_crypto', 'ssl') === 'none' ? '' : site_setting('smtp_crypto', 'ssl'),
                'SMTPTimeout' => 15,
            ]);
        }

        return $config + ['mailType' => 'html', 'charset' => 'UTF-8'];
    }

    /**
     * Settings stored encrypted (e.g. the SMTP password).
     */
    public static function secret(string $key): string
    {
        $stored = (string) site_setting($key, '');
        if ($stored === '') {
            return '';
        }
        try {
            return service('encrypter')->decrypt(base64_decode($stored, true) ?: '');
        } catch (\Throwable $e) {
            log_message('error', 'Could not decrypt setting ' . $key . ': ' . $e->getMessage());

            return '';
        }
    }

    public static function storeSecret(string $key, string $value): void
    {
        (new AppSettingModel())->put($key, $value === '' ? '' : base64_encode(service('encrypter')->encrypt($value)));
    }

    public static function fromEmail(): string
    {
        $configured = site_setting('mail_from_email');
        if ($configured && filter_var($configured, FILTER_VALIDATE_EMAIL)) {
            return $configured;
        }
        $host = parse_url((string) config('App')->baseURL, PHP_URL_HOST) ?: 'localhost';

        return 'noreply@' . preg_replace('/^www\./', '', $host);
    }

    public static function fromName(): string
    {
        return site_setting('mail_from_name') ?: site_name();
    }

    /**
     * Where admin alerts go: the setting, or every superadmin/admin.
     *
     * @return list<string>
     */
    public static function adminRecipients(): array
    {
        $configured = array_filter(array_map('trim', explode(',', (string) site_setting('admin_alert_email'))), static fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
        if ($configured) {
            return array_values($configured);
        }

        return array_column(db_connect()->table('auth_identities i')->select('i.secret')
            ->join('auth_groups_users g', 'g.user_id = i.user_id')->join('users u', 'u.id = i.user_id')
            ->where('i.type', 'email_password')->whereIn('g.group', ['superadmin', 'admin'])->where('u.deleted_at IS NULL')
            ->groupBy('i.secret')->get()->getResultArray(), 'secret');
    }

    public static function cronIsRunning(): bool
    {
        $last = (int) site_setting('cron_last_run', '0');

        return $last > time() - 20 * 60;
    }

    public static function markCronRun(): void
    {
        (new AppSettingModel())->put('cron_last_run', (string) time());
        site_setting('__flush__');
    }

    private static function plainText(array $c, string $subject): string
    {
        $lines = [$c['heading'] ?? $subject, ''];
        foreach ($c['paragraphs'] ?? [] as $p) {
            $lines[] = trim(html_entity_decode(strip_tags($p)));
            $lines[] = '';
        }
        foreach ($c['rows'] ?? [] as $k => $v) {
            $lines[] = $k . ': ' . trim(html_entity_decode(strip_tags($v)));
        }
        foreach ($c['items'] ?? [] as $i) {
            $lines[] = '- ' . $i['title'] . (isset($i['amount']) ? '  ' . $i['amount'] : '');
        }
        foreach ($c['totals'] ?? [] as $k => $v) {
            $lines[] = $k . ': ' . $v;
        }
        if (! empty($c['button'])) {
            $lines[] = '';
            $lines[] = $c['button'][0] . ': ' . $c['button'][1];
        }
        if (! empty($c['note'])) {
            $lines[] = '';
            $lines[] = trim(html_entity_decode(strip_tags($c['note'])));
        }
        $lines[] = '';
        $lines[] = '— ' . site_name() . ' · ' . base_url();

        return implode("\n", $lines);
    }
}
