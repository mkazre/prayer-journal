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
    public function buildFields(string $reference, int $amountCents, string $itemName, string $returnUrl, string $cancelUrl, string $notifyUrl, string $email): array
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
            'item_name'    => $itemName,
        ];

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

    public function verifySignature(array $postData): bool
    {
        $signature = $postData['signature'] ?? '';
        unset($postData['signature']);

        return hash_equals($this->generateSignature($postData), $signature);
    }
}
