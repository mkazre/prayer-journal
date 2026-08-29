<?php

namespace App\Modules\Shop\Services;

use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * Thin wrapper around Stripe Checkout so orders and course purchases share
 * one payment path. Falls back to an instant "test" confirmation when no
 * STRIPE_SECRET_KEY is configured, so the store is usable in local/dev
 * environments before real payment keys are set up on the live subdomain.
 */
class PaymentGateway
{
    private ?string $secretKey;

    public function __construct()
    {
        $this->secretKey = env('STRIPE_SECRET_KEY') ?: null;
        if ($this->secretKey) {
            Stripe::setApiKey($this->secretKey);
        }
    }

    public function isLive(): bool
    {
        return $this->secretKey !== null;
    }

    /**
     * @return array{mode: 'redirect'|'instant', url?: string, session_id?: string}
     */
    public function createCheckout(string $reference, int $amountCents, string $currency, string $successUrl, string $cancelUrl, string $description): array
    {
        if (! $this->isLive()) {
            // Dev/test mode: no gateway configured, treat as immediately paid.
            return ['mode' => 'instant'];
        }

        $session = StripeSession::create([
            'mode'                => 'payment',
            'payment_method_types'=> ['card'],
            'line_items'          => [[
                'price_data' => [
                    'currency'     => strtolower($currency),
                    'product_data' => ['name' => $description],
                    'unit_amount'  => $amountCents,
                ],
                'quantity' => 1,
            ]],
            'client_reference_id' => $reference,
            'success_url'         => $successUrl,
            'cancel_url'          => $cancelUrl,
        ]);

        return ['mode' => 'redirect', 'url' => $session->url, 'session_id' => $session->id];
    }

    public function verifyWebhookSignature(string $payload, string $signatureHeader): ?object
    {
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');
        if (! $this->isLive() || ! $webhookSecret) {
            return null;
        }

        try {
            return Webhook::constructEvent($payload, $signatureHeader, $webhookSecret);
        } catch (\Exception $e) {
            return null;
        }
    }
}
