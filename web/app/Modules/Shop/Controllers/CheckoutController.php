<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Models\LessonModel;
use App\Modules\Shop\Models\CartModel;
use App\Modules\Shop\Models\DiscountModel;
use App\Modules\Shop\Models\LessonUnlockModel;
use App\Modules\Shop\Models\OrderModel;
use App\Modules\Shop\Models\PaymentModel;
use App\Modules\Shop\Models\SubscriptionPlanModel;
use App\Modules\Shop\Models\UserSubscriptionModel;
use App\Modules\Shop\Services\PaymentGateway;
use App\Modules\Shop\Services\PayFastGateway;

class CheckoutController extends BaseController
{
    public function cart()
    {
        $cartModel = new CartModel();
        $cart      = $cartModel->findOrCreate(auth()->id(), session()->session_id ?? session_id());
        $items     = $cartModel->items($cart['id']);
        if ($items === []) {
            return redirect()->to(site_url('cart'))->with('error', 'Your cart is empty.');
        }

        if ($this->request->getMethod() === 'POST') {
            $subtotal      = $cartModel->subtotalCents($cart['id']);
            $discount      = null;
            $discountCents = 0;
            $code          = (string) $this->request->getPost('discount_code');
            if ($code !== '') {
                $discountModel = new DiscountModel();
                $discount      = $discountModel->findValid($code);
                if ($discount) {
                    $discountCents = $discountModel->amountCents($discount, $subtotal);
                }
            }

            $taxable    = max(0, $subtotal - $discountCents);
            $taxCents   = 0; // Tax rules are configured per region in the admin; default 0 until set up.
            $shipping   = 0;
            $totalCents = $taxable + $taxCents + $shipping;

            $order = (new OrderModel())->createFromCart(
                $cart,
                $items,
                (string) ($this->request->getPost('email') ?: auth()->user()?->email),
                auth()->id(),
                [
                    'subtotal_cents' => $subtotal,
                    'discount_cents' => $discountCents,
                    'tax_cents'      => $taxCents,
                    'shipping_cents' => $shipping,
                    'total_cents'    => $totalCents,
                    'currency'       => 'USD',
                ],
                $discount,
                (string) $this->request->getPost('shipping_address')
            );

            $paymentId = (new PaymentModel())->insert([
                'order_id'     => $order['id'],
                'user_id'      => auth()->id(),
                'gateway'      => 'pending',
                'amount_cents' => $order['total_cents'],
                'currency'     => $order['currency'],
                'status'       => 'pending',
            ], true);

            return $this->beginPayment($paymentId, $order['total_cents'], $order['currency'], 'Order ' . $order['order_number'], site_url('orders/' . $order['id'] . '/confirmation'), site_url('checkout/cart'));
        }

        return view('Shop/checkout/cart', ['title' => 'Checkout', 'items' => $items, 'subtotal' => $cartModel->subtotalCents($cart['id'])]);
    }

    public function course(int $courseId)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }

        $course = (new CourseModel())->find($courseId);
        if (! $course) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($this->request->getMethod() === 'POST') {
            $paymentId = (new PaymentModel())->insert([
                'course_id'    => $course['id'],
                'user_id'      => auth()->id(),
                'gateway'      => 'pending',
                'amount_cents' => $course['price_cents'],
                'currency'     => $course['currency'],
                'status'       => 'pending',
            ], true);

            return $this->beginPayment($paymentId, $course['price_cents'], $course['currency'], $course['title'], site_url('study/' . $course['slug']), site_url('checkout/course/' . $courseId));
        }

        return view('Shop/checkout/course', ['title' => 'Checkout: ' . $course['title'], 'course' => $course]);
    }

    /**
     * Pay-per-chapter: unlock a single lesson that carries its own price,
     * without requiring the whole course to be purchased.
     */
    public function lesson(int $lessonId)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }

        $lesson = (new LessonModel())->find($lessonId);
        $course = $lesson ? (new CourseModel())->find($lesson['course_id']) : null;
        if (! $lesson || ! $course) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ((new LessonUnlockModel())->hasUnlocked(auth()->id(), $lessonId)) {
            return redirect()->to(site_url('study/' . $course['slug'] . '/lesson/' . $lessonId));
        }

        if ($this->request->getMethod() === 'POST') {
            $paymentId = (new PaymentModel())->insert([
                'lesson_id'    => $lessonId,
                'user_id'      => auth()->id(),
                'gateway'      => 'pending',
                'amount_cents' => $lesson['price_cents'],
                'currency'     => $course['currency'],
                'status'       => 'pending',
            ], true);

            return $this->beginPayment($paymentId, $lesson['price_cents'], $course['currency'], $lesson['title'], site_url('study/' . $course['slug'] . '/lesson/' . $lessonId), site_url('checkout/lesson/' . $lessonId));
        }

        return view('Shop/checkout/lesson', ['title' => 'Unlock: ' . $lesson['title'], 'course' => $course, 'lesson' => $lesson]);
    }

    /**
     * Subscription or lifetime-deal plans (managed from shop-admin).
     */
    public function plan(int $planId)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }

        $plan = (new SubscriptionPlanModel())->find($planId);
        if (! $plan || ! $plan['is_active']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($this->request->getMethod() === 'POST') {
            $subscriptionId = (new UserSubscriptionModel())->insert([
                'user_id' => auth()->id(),
                'plan_id' => $planId,
                'status'  => 'pending',
            ], true);

            $paymentId = (new PaymentModel())->insert([
                'plan_id'         => $planId,
                'subscription_id' => $subscriptionId,
                'user_id'         => auth()->id(),
                'gateway'         => 'pending',
                'amount_cents'    => $plan['price_cents'],
                'currency'        => $plan['currency'],
                'status'          => 'pending',
            ], true);

            return $this->beginPayment($paymentId, $plan['price_cents'], $plan['currency'], $plan['name'], site_url('dashboard'), site_url('checkout/plan/' . $planId));
        }

        return view('Shop/checkout/plan', ['title' => 'Subscribe: ' . $plan['name'], 'plan' => $plan]);
    }

    protected function beginPayment(int $paymentId, int $amountCents, string $currency, string $description, string $successUrl, string $cancelUrl)
    {
        $chosenGateway = (string) ($this->request->getPost('gateway') ?: 'stripe');

        if ($chosenGateway === 'payfast') {
            $payFast = new PayFastGateway();
            if ($payFast->isLive()) {
                (new PaymentModel())->update($paymentId, ['gateway' => 'payfast']);

                $fields = $payFast->buildFields(
                    (string) $paymentId,
                    $amountCents,
                    $description,
                    $successUrl,
                    $cancelUrl,
                    site_url('webhooks/payfast'),
                    (string) (auth()->user()->email ?? '')
                );

                return view('Shop/checkout/payfast_redirect', ['title' => 'Redirecting to PayFast...', 'processUrl' => $payFast->processUrl(), 'fields' => $fields]);
            }
        }

        $gateway = new PaymentGateway();
        $result  = $gateway->createCheckout((string) $paymentId, $amountCents, $currency, $successUrl, $cancelUrl, $description);

        if ($result['mode'] === 'instant') {
            $this->confirmPayment($paymentId);

            return redirect()->to($successUrl)->with('message', 'Payment confirmed (test mode - no live gateway configured).');
        }

        (new PaymentModel())->update($paymentId, [
            'gateway'     => 'stripe',
            'gateway_ref' => $result['session_id'],
        ]);

        return redirect()->to($result['url']);
    }

    /**
     * Called for the instant/dev-mode path, and by gateway webhooks once real payments are wired up.
     * $paymentId is always the `payments` table row id.
     */
    protected function confirmPayment(int $paymentId): void
    {
        $payment = (new PaymentModel())->find($paymentId);
        if (! $payment || $payment['status'] === 'paid') {
            return;
        }
        (new PaymentModel())->update($paymentId, ['status' => 'paid']);

        if ($payment['order_id']) {
            (new OrderModel())->markPaid((int) $payment['order_id']);
        } elseif ($payment['course_id']) {
            (new EnrollmentModel())->enroll((int) $payment['course_id'], (int) $payment['user_id']);
        } elseif ($payment['lesson_id']) {
            (new LessonUnlockModel())->unlock((int) $payment['user_id'], (int) $payment['lesson_id']);
        } elseif ($payment['plan_id'] && $payment['subscription_id']) {
            $plan = (new SubscriptionPlanModel())->find($payment['plan_id']);
            (new UserSubscriptionModel())->activate((int) $payment['subscription_id'], $plan);
        }
    }

    public function webhook()
    {
        $payload   = $this->request->getBody();
        $signature = $this->request->getHeaderLine('Stripe-Signature');
        $event     = (new PaymentGateway())->verifyWebhookSignature($payload, $signature);

        if ($event && $event->type === 'checkout.session.completed') {
            $paymentId = (int) ($event->data->object->client_reference_id ?? 0);
            if ($paymentId > 0) {
                $this->confirmPayment($paymentId);
            }
        }

        return $this->response->setStatusCode(200)->setBody('ok');
    }

    public function payfastNotify()
    {
        $post = $this->request->getPost();
        if ((new PayFastGateway())->verifySignature($post) && ($post['payment_status'] ?? '') === 'COMPLETE') {
            $paymentId = (int) ($post['m_payment_id'] ?? 0);
            if ($paymentId > 0) {
                $this->confirmPayment($paymentId);
            }
        }

        return $this->response->setStatusCode(200)->setBody('ok');
    }
}
