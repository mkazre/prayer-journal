<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Libraries\Notify;
use App\Modules\Lms\Models\CourseSectionModel;
use App\Modules\Lms\Models\LessonModel;
use App\Modules\Lms\Models\SectionUnlockModel;
use App\Modules\Shop\Models\CartModel;
use App\Modules\Shop\Models\LessonUnlockModel;
use App\Modules\Shop\Models\OrderModel;
use App\Modules\Shop\Models\PaymentModel;
use App\Modules\Shop\Models\SubscriptionPlanModel;
use App\Modules\Shop\Models\UserSubscriptionModel;
use App\Modules\Shop\Services\Membership;
use App\Modules\Shop\Services\PayFastGateway;
use App\Modules\Shop\Services\PaymentGateway;
use App\Modules\Shop\Services\Pricing;

class CheckoutController extends BaseController
{
    /**
     * One-page checkout for the cart (guests welcome).
     */
    public function cart()
    {
        $carts = new CartModel();
        $cart  = $carts->current();
        $items = $carts->items((int) $cart['id']);
        if ($items === []) {
            return redirect()->to(site_url('cart'))->with('error', 'Your cart is empty.');
        }
        foreach ($items as $i) {
            $tracked = (int) $i['track_inventory'] && ! (int) $i['allow_backorder'] && $i['product_type'] !== 'digital';
            if (! (int) $i['is_active'] || ($tracked && (int) $i['quantity'] > (int) $i['stock'])) {
                return redirect()->to(site_url('cart'))->with('error', '"' . $i['title'] . '" no longer has enough stock. Please update your cart.');
            }
        }
        $digital = ! CartModel::needsShipping($items) || array_filter($items, static fn ($i) => $i['product_type'] === 'digital') !== [];
        $in      = $this->request->getMethod() === 'POST' ? (array) $this->request->getPost() : [];
        $country = strtoupper((string) ($in['country'] ?? $this->request->getGet('country') ?? Pricing::storeCountry()));
        $quote   = Pricing::quote($items, $country, session('cart_discount'), isset($in['shipping_rate']) ? (int) $in['shipping_rate'] : null, $in['region'] ?? null);

        if ($this->request->getMethod() === 'POST' && ! $this->request->getPost('refresh')) {
            if ($digital && ! auth()->loggedIn()) {
                return redirect()->to(site_url('login'))->with('info', 'Sign in to buy downloads — they are kept in your account.');
            }
            $rules = [
                'email' => 'required|valid_email|max_length[255]',
                'name'  => 'required|max_length[150]',
            ];
            if ($quote['needs_shipping']) {
                $rules += ['line1' => 'required|max_length[200]', 'city' => 'required|max_length[100]', 'postal_code' => 'required|max_length[20]', 'country' => 'required|exact_length[2]'];
            }
            if (! $this->validateData($in, $rules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
            if (! $quote['shipping_available']) {
                return redirect()->back()->withInput()->with('error', 'We do not deliver to that country yet.');
            }
            if ($quote['discount_error']) {
                session()->remove('cart_discount');

                return redirect()->back()->withInput()->with('error', $quote['discount_error']);
            }

            $address = $quote['needs_shipping'] ? [
                'name' => trim($in['name']), 'line1' => trim($in['line1']), 'line2' => trim($in['line2'] ?? ''), 'city' => trim($in['city']),
                'region' => trim($in['region'] ?? ''), 'postal_code' => trim($in['postal_code']), 'country' => $country, 'phone' => trim($in['phone'] ?? ''),
            ] : null;

            $order = (new OrderModel())->createFromCart($cart, $items, trim($in['email']), auth()->loggedIn() ? $this->userId() : null, [
                'subtotal_cents' => $quote['subtotal'],
                'discount_cents' => $quote['discount'],
                'tax_cents'      => $quote['tax'],
                'shipping_cents' => $quote['shipping'],
                'total_cents'    => $quote['total'],
                'currency'       => $quote['currency'],
            ], $quote['discount_row'], $address, [
                'customer_name'   => trim($in['name']),
                'phone'           => trim($in['phone'] ?? '') ?: null,
                'shipping_method' => $quote['shipping_rate']['name'] ?? null,
                'customer_note'   => trim(mb_substr((string) ($in['note'] ?? ''), 0, 1000)) ?: null,
            ]);

            // Empty the cart now that its contents live on the order.
            db_connect()->table('cart_items')->where('cart_id', $cart['id'])->delete();
            session()->remove('cart_discount');
            $guestOrders   = (array) session('guest_orders');
            $guestOrders[] = (int) $order['id'];
            session()->set('guest_orders', $guestOrders);

            $paymentId = (int) (new PaymentModel())->insert([
                'order_id'     => $order['id'],
                'user_id'      => auth()->loggedIn() ? $this->userId() : null,
                'gateway'      => 'pending',
                'amount_cents' => $order['total_cents'],
                'currency'     => $order['currency'],
                'status'       => 'pending',
            ], true);

            return $this->beginPayment($paymentId, (int) $order['total_cents'], $order['currency'], 'Order ' . $order['order_number'], site_url('orders/' . $order['id']), site_url('orders/' . $order['id']), (string) $order['email']);
        }

        $user    = auth()->user();
        $last    = auth()->loggedIn() ? db_connect()->table('orders')->where('user_id', $this->userId())->where('shipping_address IS NOT NULL')->orderBy('id', 'DESC')->get()->getRowArray() : null;
        $prefill = $last ? OrderModel::address($last) : [];

        return view('Shop/checkout/cart', [
            'title'     => 'Checkout',
            'items'     => $items,
            'quote'     => $quote,
            'country'   => $country,
            'prefill'   => array_merge(['email' => $user->email ?? '', 'name' => $user ? display_name() : ''], $prefill, array_map('strval', array_filter($in, 'is_string'))),
            'gateways'  => $this->gateways(),
            'digital'   => $digital,
        ]);
    }

    public function course(int $courseId)
    {
        $course = (new CourseModel())->find($courseId);
        if (! $course || $course['status'] !== 'published') {
            $this->notFound();
        }
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'))->with('info', 'Sign in to buy this course.');
        }
        // Module-only and membership learners may still buy the full course outright.
        $existing = (new EnrollmentModel())->findFor($courseId, $this->userId());
        if (EnrollmentModel::isActive($existing) && ! in_array($existing['source'], ['module', 'subscription'], true)) {
            return redirect()->to(site_url('study/' . $course['slug']));
        }
        if ($this->request->getMethod() === 'POST') {
            $paymentId = (int) (new PaymentModel())->insert([
                'course_id'    => $course['id'],
                'user_id'      => $this->userId(),
                'gateway'      => 'pending',
                'amount_cents' => $course['price_cents'],
                'currency'     => $course['currency'],
                'status'       => 'pending',
            ], true);

            return $this->beginPayment($paymentId, (int) $course['price_cents'], $course['currency'], $course['title'], site_url('study/' . $course['slug']), site_url('checkout/course/' . $courseId));
        }

        return view('Shop/checkout/product', ['title' => 'Checkout', 'kind' => 'Course', 'name' => $course['title'], 'description' => $course['summary'], 'price' => (int) $course['price_cents'], 'currency' => $course['currency'], 'gateways' => $this->gateways(), 'icon' => 'graduation']);
    }

    /**
     * Pay-per-lesson: unlock a single lesson that carries its own price.
     */
    public function lesson(int $lessonId)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }
        $lesson = (new LessonModel())->find($lessonId);
        $course = $lesson ? (new CourseModel())->find($lesson['course_id']) : null;
        if (! $lesson || ! $course) {
            $this->notFound();
        }
        if ((new LessonUnlockModel())->hasUnlocked($this->userId(), $lessonId)) {
            return redirect()->to(site_url('study/' . $course['slug'] . '/lesson/' . $lessonId));
        }
        if ($this->request->getMethod() === 'POST') {
            $paymentId = (int) (new PaymentModel())->insert([
                'lesson_id'    => $lessonId,
                'user_id'      => $this->userId(),
                'gateway'      => 'pending',
                'amount_cents' => $lesson['price_cents'],
                'currency'     => $course['currency'],
                'status'       => 'pending',
            ], true);

            return $this->beginPayment($paymentId, (int) $lesson['price_cents'], $course['currency'], $lesson['title'], site_url('study/' . $course['slug'] . '/lesson/' . $lessonId), site_url('checkout/lesson/' . $lessonId));
        }

        return view('Shop/checkout/product', ['title' => 'Unlock lesson', 'kind' => 'Lesson', 'name' => $lesson['title'], 'description' => 'From ' . $course['title'], 'price' => (int) $lesson['price_cents'], 'currency' => $course['currency'], 'gateways' => $this->gateways(), 'icon' => 'unlock']);
    }

    /**
     * Pay-per-module: unlock one module of a course.
     */
    public function module(int $sectionId)
    {
        $section = (new CourseSectionModel())->find($sectionId);
        $course  = $section ? (new CourseModel())->find($section['course_id']) : null;
        if (! $section || ! $course || $course['status'] !== 'published' || (int) $section['price_cents'] <= 0) {
            $this->notFound();
        }
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'))->with('info', 'Sign in to buy this module.');
        }
        $enrollment = (new EnrollmentModel())->findFor((int) $course['id'], $this->userId());
        $hasFull    = EnrollmentModel::isActive($enrollment) && ($enrollment['source'] ?? '') !== 'module';
        if ($hasFull || (new SectionUnlockModel())->has($this->userId(), $sectionId)) {
            return redirect()->to(site_url('study/' . $course['slug']))->with('info', 'You already have this module.');
        }
        if ($this->request->getMethod() === 'POST') {
            $paymentId = (int) (new PaymentModel())->insert([
                'section_id'   => $sectionId,
                'course_id'    => null,
                'user_id'      => $this->userId(),
                'gateway'      => 'pending',
                'amount_cents' => $section['price_cents'],
                'currency'     => $course['currency'],
                'status'       => 'pending',
            ], true);

            return $this->beginPayment($paymentId, (int) $section['price_cents'], $course['currency'], $section['title'] . ' — ' . $course['title'], site_url('study/' . $course['slug']), site_url('checkout/module/' . $sectionId));
        }

        return view('Shop/checkout/product', ['title' => 'Unlock module', 'kind' => 'Module', 'name' => $section['title'], 'description' => 'One module of ' . $course['title'] . '. Lifetime access to its lessons, quizzes and journal prompts.', 'price' => (int) $section['price_cents'], 'currency' => $course['currency'], 'gateways' => $this->gateways(), 'icon' => 'layers']);
    }

    /**
     * Monthly/yearly memberships (recurring through PayFast) and lifetime plans.
     */
    public function plan(int $planId)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'))->with('info', 'Sign in to choose a membership.');
        }
        $plan = (new SubscriptionPlanModel())->find($planId);
        if (! $plan || ! $plan['is_active']) {
            $this->notFound();
        }
        $current = Membership::current($this->userId());
        if ($current && $current['status'] === 'active') {
            return redirect()->to(site_url('account#membership'))->with('info', 'You already have an active membership.');
        }
        $recurring = $plan['type'] === 'subscription';
        // Automatic renewals run through PayFast; offer only gateways that can bill this plan.
        $gateways = $this->gateways();
        if ($recurring && isset($gateways['payfast'])) {
            $gateways = ['payfast' => 'PayFast (card, debit order) — renews automatically'];
        }
        if ($this->request->getMethod() === 'POST') {
            $subscriptionId = (int) (new UserSubscriptionModel())->insert(['user_id' => $this->userId(), 'plan_id' => $planId, 'status' => 'pending', 'updated_at' => date('Y-m-d H:i:s')], true);
            $paymentId      = (int) (new PaymentModel())->insert([
                'plan_id'         => $planId,
                'subscription_id' => $subscriptionId,
                'user_id'         => $this->userId(),
                'gateway'         => 'pending',
                'amount_cents'    => $plan['price_cents'],
                'currency'        => $plan['currency'],
                'status'          => 'pending',
            ], true);

            return $this->beginPayment($paymentId, (int) $plan['price_cents'], $plan['currency'], $plan['name'], site_url('account#membership'), site_url('checkout/plan/' . $planId), null,
                $recurring ? ['amount_cents' => (int) $plan['price_cents'], 'interval' => $plan['billing_interval'] ?: 'month'] : null, $gateways);
        }

        $interval = $recurring ? ' / ' . ($plan['billing_interval'] ?: 'month') : ' once';

        return view('Shop/checkout/product', ['title' => 'Membership', 'kind' => 'Membership', 'name' => $plan['name'],
            'description' => $recurring ? 'Access to every membership course. Renews automatically each ' . ($plan['billing_interval'] ?: 'month') . ' — cancel any time from your account.' : 'Access to every membership course, forever.',
            'price' => (int) $plan['price_cents'], 'currency' => $plan['currency'], 'suffix' => $interval, 'gateways' => $gateways, 'icon' => 'sparkles']);
    }

    /**
     * Member cancels renewal from their account page.
     */
    public function cancelMembership()
    {
        $current = Membership::current($this->userId());
        if (! $current || ! in_array($current['status'], ['active', 'past_due'], true)) {
            return redirect()->to(site_url('account#membership'))->with('error', 'There is no active membership to cancel.');
        }
        if (! Membership::cancel((int) $current['id'])) {
            return redirect()->to(site_url('account#membership'))->with('error', 'We could not reach PayFast to cancel right now. Please try again in a few minutes or contact us.');
        }

        return redirect()->to(site_url('account#membership'))->with('message', 'Your membership will not renew. You keep access until the end of the current period.');
    }

    /**
     * Gateways that can take a payment right now.
     *
     * @return array<string,string> key => label
     */
    private function gateways(): array
    {
        $out = [];
        if ((new PaymentGateway())->isLive()) {
            $out['stripe'] = 'Card (Stripe)';
        }
        if ((new PayFastGateway())->isLive()) {
            $out['payfast'] = 'PayFast (card, EFT, SnapScan)';
        }
        if ($out === [] && self::testPaymentsAllowed()) {
            $out['test'] = 'Test payment (no gateway configured)';
        }

        return $out;
    }

    /**
     * Instant "test" payments are for local development only, unless an
     * administrator explicitly allows them (e.g. on a staging site).
     */
    public static function testPaymentsAllowed(): bool
    {
        return ENVIRONMENT !== 'production' || site_setting('allow_test_payments') === '1';
    }

    protected function beginPayment(int $paymentId, int $amountCents, string $currency, string $description, string $successUrl, string $cancelUrl, ?string $email = null, ?array $recurring = null, ?array $gateways = null)
    {
        if ($amountCents <= 0) {
            $this->confirmPayment($paymentId, 'free order');

            return redirect()->to($successUrl)->with('message', 'All done — nothing to pay.');
        }
        $gateways ??= $this->gateways();
        $chosen   = (string) ($this->request->getPost('gateway') ?: array_key_first($gateways));
        if (! isset($gateways[$chosen])) {
            (new PaymentModel())->update($paymentId, ['status' => 'failed']);

            return redirect()->to($cancelUrl)->with('error', 'Online payments are not set up yet. Please contact us to complete your purchase.');
        }

        if ($chosen === 'payfast') {
            $payFast = new PayFastGateway();
            (new PaymentModel())->update($paymentId, ['gateway' => 'payfast']);

            return view('Shop/checkout/payfast_redirect', ['title' => 'Redirecting to PayFast…', 'processUrl' => $payFast->processUrl(),
                'fields' => $payFast->buildFields((string) $paymentId, $amountCents, $description, $successUrl, $cancelUrl, site_url('webhooks/payfast'), (string) ($email ?: auth()->user()->email ?? ''), $recurring)]);
        }
        if ($chosen === 'test') {
            (new PaymentModel())->update($paymentId, ['gateway' => 'test']);
            $this->confirmPayment($paymentId, 'test payment');

            return redirect()->to($successUrl)->with('message', 'Payment confirmed (test mode — no real money was taken).');
        }

        $result = (new PaymentGateway())->createCheckout((string) $paymentId, $amountCents, $currency, $successUrl, $cancelUrl, $description);
        (new PaymentModel())->update($paymentId, ['gateway' => 'stripe', 'gateway_ref' => $result['session_id'] ?? null]);

        return redirect()->to($result['url']);
    }

    /**
     * Fulfils whatever the payment was for. Idempotent.
     */
    protected function confirmPayment(int $paymentId, string $via = 'payment gateway', ?string $token = null): void
    {
        $payments = new PaymentModel();
        $payment  = $payments->find($paymentId);
        if (! $payment || $payment['status'] === 'paid') {
            return;
        }
        $payments->update($paymentId, ['status' => 'paid']);
        $userId = (int) $payment['user_id'];
        $ref    = 'PAY-' . str_pad((string) $paymentId, 6, '0', STR_PAD_LEFT);

        if ($payment['order_id']) {
            (new OrderModel())->markPaid((int) $payment['order_id'], $via);
        } elseif ($payment['course_id']) {
            $course = (new CourseModel())->find($payment['course_id']);
            (new EnrollmentModel())->enroll((int) $payment['course_id'], $userId, 'purchase', false);
            if ($course) {
                Notify::purchaseReceipt($userId, 'Course', $course['title'], (int) $payment['amount_cents'], $payment['currency'], site_url('study/' . $course['slug']), $ref);
            }
        } elseif ($payment['section_id']) {
            $section = (new CourseSectionModel())->find($payment['section_id']);
            $course  = $section ? (new CourseModel())->find($section['course_id']) : null;
            if ($section && $course) {
                (new SectionUnlockModel())->unlock($userId, (int) $section['id']);
                (new EnrollmentModel())->enroll((int) $course['id'], $userId, 'module', false);
                Notify::purchaseReceipt($userId, 'Module', $section['title'] . ' — ' . $course['title'], (int) $payment['amount_cents'], $payment['currency'], site_url('study/' . $course['slug']), $ref);
            }
        } elseif ($payment['lesson_id']) {
            (new LessonUnlockModel())->unlock($userId, (int) $payment['lesson_id']);
            $lesson = (new LessonModel())->find($payment['lesson_id']);
            $course = $lesson ? (new CourseModel())->find($lesson['course_id']) : null;
            if ($lesson && $course) {
                Notify::purchaseReceipt($userId, 'Lesson', $lesson['title'], (int) $payment['amount_cents'], $payment['currency'], site_url('study/' . $course['slug'] . '/lesson/' . $lesson['id']), $ref);
            }
        } elseif ($payment['plan_id'] && $payment['subscription_id']) {
            Membership::activate((int) $payment['subscription_id'], $payment['gateway'] ?: null, $token);
        }
    }

    public function webhook()
    {
        $event = (new PaymentGateway())->verifyWebhookSignature($this->request->getBody(), $this->request->getHeaderLine('Stripe-Signature'));
        if ($event && $event->type === 'checkout.session.completed') {
            $paymentId = (int) ($event->data->object->client_reference_id ?? 0);
            $payment   = $paymentId ? (new PaymentModel())->find($paymentId) : null;
            $paid      = (int) ($event->data->object->amount_total ?? -1);
            if ($payment && ($paid === -1 || $paid === (int) $payment['amount_cents'])) {
                $this->confirmPayment($paymentId, 'Stripe');
            }
        }

        return $this->response->setStatusCode(200)->setBody('ok');
    }

    /**
     * PayFast ITN: first payments, monthly renewals and cancellations.
     */
    public function payfastNotify()
    {
        $post = $this->request->getPost();
        if (! (new PayFastGateway())->validateItn($post)) {
            return $this->response->setStatusCode(400)->setBody('invalid');
        }
        $payments  = new PaymentModel();
        $paymentId = (int) ($post['m_payment_id'] ?? 0);
        $payment   = $paymentId ? $payments->find($paymentId) : null;
        if (! $payment) {
            return $this->response->setStatusCode(200)->setBody('ok');
        }
        $status = strtoupper((string) ($post['payment_status'] ?? ''));
        $token  = trim((string) ($post['token'] ?? '')) ?: null;
        $pfId   = (string) ($post['pf_payment_id'] ?? '');
        $amount = (int) round((float) ($post['amount_gross'] ?? 0) * 100);

        if ($status === 'CANCELLED' && $payment['subscription_id']) {
            Membership::cancel((int) $payment['subscription_id'], false);
        } elseif ($status === 'COMPLETE' && $payment['status'] !== 'paid') {
            // First payment: reject anything that does not match what we asked for.
            if (abs($amount - (int) $payment['amount_cents']) <= 1) {
                $payments->update($paymentId, ['gateway' => 'payfast', 'external_id' => $pfId ?: null]);
                $this->confirmPayment($paymentId, 'PayFast', $token);
            }
        } elseif ($status === 'COMPLETE' && $payment['subscription_id'] && $pfId !== '' && ! $payments->where('external_id', $pfId)->countAllResults()) {
            // A recurring charge on an existing PayFast subscription.
            $plan = (new SubscriptionPlanModel())->find($payment['plan_id']);
            if ($plan && abs($amount - (int) $plan['price_cents']) <= 1) {
                $payments->insert([
                    'plan_id' => $payment['plan_id'], 'subscription_id' => $payment['subscription_id'], 'user_id' => $payment['user_id'],
                    'gateway' => 'payfast', 'external_id' => $pfId, 'amount_cents' => $amount, 'currency' => $plan['currency'], 'status' => 'paid',
                ]);
                if ($token) {
                    (new UserSubscriptionModel())->update((int) $payment['subscription_id'], ['gateway_token' => $token]);
                }
                Membership::renew((int) $payment['subscription_id']);
            }
        }

        return $this->response->setStatusCode(200)->setBody('ok');
    }
}
