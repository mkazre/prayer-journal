<?php

namespace App\Modules\Shop\Services;

/**
 * PayFast (South African payment gateway) integration.
 *
 * PayFast has no official PHP SDK; a checkout is a signed HTML form the
 * browser POSTs to PayFast's process URL, and payment confirmation arrives
 * later via an ITN (Instant Transaction Notification) webhook.
 *
 * @see https://developers.payfast.co.za/docs
 */
class PayFastGateway
{
    private ?string $merchantId;
    private ?string $merchantKey;
    private ?string $passphrase;
    private bool $sandbox;

    public function __construct()
    {
        $this->merchantId  = env('PAYFAST_MERCHANT_ID') ?: null;
        $this->merchantKey = env('PAYFAST_MERCHANT_KEY') ?: null;
        $this->passphrase  = env('PAYFAST_PASSPHRASE') ?: null;
        $this->sandbox     = (bool) (env('PAYFAST_SANDBOX') ?? true);
    }

    public function isLive(): bool
    {
        return $this->merchantId !== null && $this->merchantKey !== null;
    }

    public function processUrl(): string
    {
        return $this->sandbox ? 'https://sandbox.payfast.co.za/eng/process' : 'https://www.payfast.co.za/eng/process';
    }

    /**
     * @return array<string,string> Signed form fields to POST to processUrl().
     */
    public function buildFields(string $reference, int $amountCents, string $itemName, string $returnUrl, string $cancelUrl, string $notifyUrl, string $email, ?array $recurring = null): array
    {
        $fields = [
            'merchant_id'  => $this->merchantId,
            'merchant_key' => $this->merchantKey,
            'return_url'   => $returnUrl,
            'cancel_url'   => $cancelUrl,
            'notify_url'   => $notifyUrl,
            'email_address'=> $email,
            'm_payment_id' => $reference,
            'amount'       => number_format($amountCents / 100, 2, '.', ''),
            'item_name'    => mb_substr($itemName, 0, 100),
        ];
        if ($recurring) {
            // PayFast subscription: charges recurring_amount every period
            // until cancelled (cycles = 0). Field order matters for the signature.
            $fields += [
                'subscription_type' => '1',
                'billing_date'      => date('Y-m-d'),
                'recurring_amount'  => number_format($recurring['amount_cents'] / 100, 2, '.', ''),
                'frequency'         => $recurring['interval'] === 'year' ? '6' : '3',
                'cycles'            => '0',
            ];
        }

        $fields['signature'] = $this->generateSignature($fields);

        return $fields;
    }

    private function generateSignature(array $fields): string
    {
        $pairs = [];
        foreach ($fields as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $pairs[] = $key . '=' . urlencode((string) $value);
        }
        $query = implode('&', $pairs);
        if ($this->passphrase) {
            $query .= '&passphrase=' . urlencode($this->passphrase);
        }

        return md5($query);
    }

    /**
     * Full ITN check as PayFast recommends: signature, then a server-to-server
     * confirmation that PayFast really sent this notification.
     */
    public function validateItn(array $postData): bool
    {
        if (! $this->verifySignature($postData)) {
            return false;
        }
        if (ENVIRONMENT === 'testing') {
            return true;
        }
        $host = $this->sandbox ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        $pairs = [];
        foreach ($postData as $k => $v) {
            if ($k !== 'signature') {
                $pairs[] = $k . '=' . urlencode((string) $v);
            }
        }
        try {
            $response = service('curlrequest', ['timeout' => 15], null, null, false)->post('https://' . $host . '/eng/query/validate', [
                'body'    => implode('&', $pairs),
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            ]);

            return trim((string) $response->getBody()) === 'VALID';
        } catch (\Throwable $e) {
            log_message('error', 'PayFast ITN validation failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Stops a PayFast subscription through the PayFast API.
     */
    public function cancelSubscription(string $token): bool
    {
        if (! $this->isLive()) {
            return ENVIRONMENT !== 'production';
        }
        if (ENVIRONMENT === 'testing') {
            return true;
        }
        $timestamp = date('Y-m-d\TH:i:sP');
        $headers   = ['merchant-id' => (string) $this->merchantId, 'version' => 'v1', 'timestamp' => $timestamp];
        $sign      = $headers;
        if ($this->passphrase) {
            $sign['passphrase'] = $this->passphrase;
        }
        ksort($sign);
        $signature = md5(http_build_query($sign));
        try {
            $response = service('curlrequest', ['timeout' => 20, 'http_errors' => false], null, null, false)->request('PUT', 'https://api.payfast.co.za/subscriptions/' . rawurlencode($token) . '/cancel' . ($this->sandbox ? '?testing=true' : ''), [
                'headers' => $headers + ['signature' => $signature, 'Content-Type' => 'application/json'],
            ]);
            $data = json_decode((string) $response->getBody(), true);

            return $response->getStatusCode() < 300 && (($data['status'] ?? '') === 'success' || ($data['code'] ?? 0) === 200);
        } catch (\Throwable $e) {
            log_message('error', 'PayFast cancel failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Page where a subscriber updates the card PayFast charges.
     */
    public static function updateCardUrl(string $token, string $returnUrl): string
    {
        $host = (bool) (env('PAYFAST_SANDBOX') ?? true) ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';

        return 'https://' . $host . '/eng/recurring/update/' . rawurlencode($token) . '?return=' . rawurlencode($returnUrl);
    }

    public function verifySignature(array $postData): bool
    {
        $signature = $postData['signature'] ?? '';
        unset($postData['signature']);

        return hash_equals($this->generateSignature($postData), $signature);
    }
}
