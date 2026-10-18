<?php

use App\Libraries\Mailer;
use App\Modules\Shop\Models\UserSubscriptionModel;
use App\Modules\Shop\Services\Membership;
use App\Modules\Shop\Services\PayFastGateway;
use App\Modules\Shop\Services\StudentVerification;
use CodeIgniter\Config\Factories;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use Config\Filters;
use Tests\Support\AppTestCase;

/**
 * PayFast settings and ITN signatures, SheerID student verification, and
 * student pricing on membership plans.
 *
 * @internal
 */
final class StudentPricingTest extends AppTestCase
{
    use AuthenticationTesting;

    /** @var list<array{0:string,1:string,2:?array}> SheerID calls made */
    private array $calls = [];

    /** currentStep the fake SheerID reports */
    private string $step = 'success';

    protected function setUp(): void
    {
        parent::setUp();
        $filters = config(Filters::class);
        unset($filters->globals['before']['csrf']);
        Factories::injectMock('config', 'Filters', $filters);
        // Secrets are stored encrypted; deploy.sh generates the live key.
        $enc      = config('Encryption');
        $enc->key = str_repeat("\x07", 32);
        Factories::injectMock('config', 'Encryption', $enc);
        \Config\Services::resetSingle('encrypter');
        foreach (['PAYFAST_MERCHANT_ID', 'PAYFAST_MERCHANT_KEY', 'PAYFAST_PASSPHRASE'] as $k) {
            putenv($k);
        }
        $GLOBALS['sheerid_fake'] = function (string $method, string $path, ?array $json) {
            $this->calls[] = [$method, $path, $json];
            if ($method === 'POST') {
                return ['verificationId' => 'ver-' . count($this->calls), 'currentStep' => 'collectStudentPersonalInfo'];
            }

            return ['programId' => 'prog-1', 'trackingId' => $this->trackingFor($path), 'lastResponse' => ['currentStep' => $this->step, 'segment' => 'student', 'subSegment' => 'fullAndPartTimeUniversity', 'errorIds' => $this->step === 'error' ? ['notEligible'] : []]];
        };
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['sheerid_fake']);
        parent::tearDown();
    }

    private function trackingFor(string $path): string
    {
        preg_match('#/verification/([^/]+)/details#', $path, $m);
        $row = db_connect()->table('student_verifications')->where('external_id', $m[1] ?? '')->get()->getRowArray();

        return 'user-' . ($row['user_id'] ?? 0);
    }

    private function configureSheerId(): void
    {
        $this->setting('sheerid_program_id', 'prog-1');
        Mailer::storeSecret('sheerid_access_token', 'token-abc');
        Mailer::storeSecret('sheerid_webhook_secret', 'whsec-123');
        site_setting('__flush__');
    }

    private function plan(): int
    {
        db_connect()->table('subscription_plans')->insert(['name' => 'Monthly', 'type' => 'subscription', 'billing_interval' => 'month', 'price_cents' => 2999, 'student_price_cents' => 999, 'currency' => 'ZAR', 'is_active' => 1]);

        return (int) db_connect()->insertID();
    }

    private function verifyStudent(int $userId): void
    {
        $this->configureSheerId();
        StudentVerification::start($userId, 'student@example.test');
        $id = db_connect()->table('student_verifications')->where('user_id', $userId)->get()->getRow('external_id');
        $body = json_encode(['verificationId' => $id, 'notifierEventType' => 'SUCCESS']);
        $this->withHeaders(['X-SheerID-Signature' => hash_hmac('sha256', $body, 'whsec-123'), 'Content-Type' => 'application/json'])
            ->withBody($body)->post('webhooks/sheerid')->assertOK();
    }

    public function testPayfastCredentialsComeFromStudioAndRecurringNeedsAPassphrase(): void
    {
        $this->assertFalse((new PayFastGateway())->isLive());

        $this->setting('payfast_merchant_id', '10000100');
        Mailer::storeSecret('payfast_merchant_key', '46f0cd694581a');
        $this->setting('payfast_mode', 'sandbox');
        site_setting('__flush__');
        $pf = new PayFastGateway();
        $this->assertTrue($pf->isLive());
        $this->assertFalse($pf->canBillRecurring(), 'subscriptions need a passphrase');
        $this->assertStringContainsString('sandbox.payfast.co.za', $pf->processUrl());

        Mailer::storeSecret('payfast_passphrase', 'jt7NOE43FZPn');
        $this->setting('payfast_mode', 'live');
        site_setting('__flush__');
        $pf = new PayFastGateway();
        $this->assertTrue($pf->canBillRecurring());
        $this->assertSame('https://www.payfast.co.za/eng/process', $pf->processUrl());
    }

    public function testItnSignaturesIncludeBlankFields(): void
    {
        $this->setting('payfast_merchant_id', '10000100');
        Mailer::storeSecret('payfast_merchant_key', '46f0cd694581a');
        Mailer::storeSecret('payfast_passphrase', 'jt7NOE43FZPn');
        site_setting('__flush__');
        // As PayFast posts it: every field in order, blanks included.
        $itn   = ['m_payment_id' => '7', 'pf_payment_id' => '123', 'payment_status' => 'COMPLETE', 'item_name' => 'Monthly', 'name_first' => 'Thandi', 'name_last' => '', 'custom_str1' => '', 'amount_gross' => '29.99'];
        $pairs = [];
        foreach ($itn as $k => $v) {
            $pairs[] = $k . '=' . urlencode($v);
        }
        $itn['signature'] = md5(implode('&', $pairs) . '&passphrase=' . urlencode('jt7NOE43FZPn'));

        $this->assertTrue((new PayFastGateway())->verifySignature($itn));
        $itn['amount_gross'] = '1.00';
        $this->assertFalse((new PayFastGateway())->verifySignature($itn), 'tampered amount');
    }

    public function testSheerIdVerificationUnlocksTheStudentPrice(): void
    {
        $user = $this->makeUser();
        $plan = $this->plan();
        $this->assertFalse(StudentVerification::isConfigured());
        $this->actingAs($user)->get('account/student')->assertSee('Student verification opens soon');

        $this->configureSheerId();
        $redirect = $this->actingAs($user)->post('account/student/verify');
        $redirect->assertRedirect();
        $this->assertStringStartsWith('https://services.sheerid.com/verify/prog-1/?verificationId=ver-', $redirect->getRedirectUrl());
        $this->assertSame(['programId' => 'prog-1', 'trackingId' => 'user-' . $user->id], array_intersect_key($this->calls[0][2], ['programId' => 1, 'trackingId' => 1]));
        $id = db_connect()->table('student_verifications')->where('user_id', $user->id)->get()->getRow('external_id');

        // A forged webhook is refused and changes nothing.
        $body = json_encode(['verificationId' => $id, 'notifierEventType' => 'SUCCESS']);
        $this->withHeaders(['X-SheerID-Signature' => 'forged'])->withBody($body)->post('webhooks/sheerid')->assertStatus(401);
        $this->assertFalse(StudentVerification::isVerified((int) $user->id));

        $this->withHeaders(['X-SheerID-Signature' => hash_hmac('sha256', $body, 'whsec-123')])->withBody($body)->post('webhooks/sheerid')->assertOK();
        $this->assertTrue(StudentVerification::isVerified((int) $user->id));
        $this->assertSame(date('Y') . '-12-31 23:59:59', StudentVerification::current((int) $user->id)['expires_at']);

        $pricing = $this->actingAs($user)->get('pricing');
        $pricing->assertSee('Student price');
        $pricing->assertSee('R9.99', null);
        $this->actingAs($user)->get('checkout/plan/' . $plan)->assertSee('student price');
    }

    public function testRejectedVerificationKeepsTheStandardPrice(): void
    {
        $user = $this->makeUser();
        $this->step = 'error';
        $this->verifyStudent((int) $user->id);
        $this->assertFalse(StudentVerification::isVerified((int) $user->id));
        $this->assertSame('rejected', StudentVerification::latest((int) $user->id)['status']);
    }

    public function testVerifiedStudentPaysStudentPriceAndRenewsAtIt(): void
    {
        $this->setting('payfast_merchant_id', '10000100');
        Mailer::storeSecret('payfast_merchant_key', '46f0cd694581a');
        Mailer::storeSecret('payfast_passphrase', 'jt7NOE43FZPn');
        site_setting('__flush__');
        $user = $this->makeUser();
        $plan = $this->plan();
        $this->verifyStudent((int) $user->id);

        // Checkout posts a PayFast subscription for R9.99.
        $form = $this->actingAs($user)->post('checkout/plan/' . $plan, ['gateway' => 'payfast']);
        $form->assertSee('name="recurring_amount" value="9.99"', null);
        $form->assertSee('name="amount" value="9.99"', null);
        $sub = (new UserSubscriptionModel())->where('user_id', $user->id)->orderBy('id', 'DESC')->first();
        $this->assertSame(999, (int) $sub['amount_cents']);
        $this->assertSame(1, (int) $sub['is_student']);
        $payment = (int) db_connect()->table('payments')->where('subscription_id', $sub['id'])->get()->getRow('id');

        // First ITN and next month's renewal at the student price are both accepted.
        $itn = function (string $pfId) use ($payment) {
            $f = ['m_payment_id' => (string) $payment, 'pf_payment_id' => $pfId, 'payment_status' => 'COMPLETE', 'amount_gross' => '9.99', 'token' => 'tok-s', 'name_last' => ''];
            $pairs = [];
            foreach ($f as $k => $v) {
                $pairs[] = $k . '=' . urlencode($v);
            }
            $f['signature'] = md5(implode('&', $pairs) . '&passphrase=' . urlencode('jt7NOE43FZPn'));

            return $this->post('webhooks/payfast', $f);
        };
        $itn('900')->assertOK();
        $this->assertSame('active', (new UserSubscriptionModel())->find($sub['id'])['status']);
        db_connect()->table('user_subscriptions')->where('id', $sub['id'])->update(['last_payment_at' => date('Y-m-d H:i:s', strtotime('-30 days'))]);
        $itn('901')->assertOK();
        $this->assertSame(2, db_connect()->table('payments')->where(['subscription_id' => $sub['id'], 'status' => 'paid'])->countAllResults());
    }

    public function testExpiredStudentStatusMovesTheMembershipToTheStandardPrice(): void
    {
        $user = $this->makeUser();
        $plan = $this->plan();
        $this->verifyStudent((int) $user->id);
        $db = db_connect();
        $db->table('user_subscriptions')->insert(['user_id' => $user->id, 'plan_id' => $plan, 'status' => 'pending', 'amount_cents' => 999, 'is_student' => 1]);
        $sub = (int) $db->insertID();
        Membership::activate($sub, 'payfast', 'tok-x');

        $db->table('student_verifications')->where('user_id', $user->id)->update(['expires_at' => date('Y-m-d H:i:s', strtotime('+3 days'))]);
        $this->assertSame(1, StudentVerification::checkAll()['warned']);
        $this->assertSame(1, $db->table('email_queue')->where('template', 'student_expiring')->countAllResults());

        $db->table('student_verifications')->where('user_id', $user->id)->update(['expires_at' => date('Y-m-d H:i:s', strtotime('-1 minute'))]);
        $result = StudentVerification::checkAll();
        $this->assertSame(1, $result['expired']);
        $this->assertSame(1, $result['repriced']);
        $s = (new UserSubscriptionModel())->find($sub);
        $this->assertSame(2999, (int) $s['amount_cents']);
        $this->assertSame(0, (int) $s['is_student']);
        $this->assertSame(1, $db->table('email_queue')->where('template', 'student_expired')->countAllResults());
    }
}
