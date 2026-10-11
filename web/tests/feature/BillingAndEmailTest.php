<?php

use App\Libraries\Mailer;
use App\Libraries\Notify;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Services\CourseAccess;
use App\Modules\Lms\Services\Curriculum;
use App\Modules\Lms\Services\LearningReminders;
use App\Modules\Shop\Models\UserSubscriptionModel;
use App\Modules\Shop\Services\Membership;
use CodeIgniter\Config\Factories;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use Config\Filters;
use Tests\Support\AppTestCase;

/**
 * PayFast memberships, automatic suspension, pay-per-module, emails and
 * learning reminders.
 *
 * @internal
 */
final class BillingAndEmailTest extends AppTestCase
{
    use AuthenticationTesting;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PAYFAST_MERCHANT_ID=10000100');
        putenv('PAYFAST_MERCHANT_KEY=46f0cd694581a');
        putenv('PAYFAST_PASSPHRASE');
        $filters = config(Filters::class);
        unset($filters->globals['before']['csrf']);
        Factories::injectMock('config', 'Filters', $filters);
    }

    protected function tearDown(): void
    {
        putenv('PAYFAST_MERCHANT_ID');
        putenv('PAYFAST_MERCHANT_KEY');
        parent::tearDown();
    }

    private function plan(): int
    {
        db_connect()->table('subscription_plans')->insert(['name' => 'Monthly membership', 'type' => 'subscription', 'billing_interval' => 'month', 'price_cents' => 9900, 'currency' => 'ZAR', 'is_active' => 1]);

        return (int) db_connect()->insertID();
    }

    /**
     * @return array{0:int,1:int} [subscription id, payment id]
     */
    private function pendingSubscription(int $userId, int $planId): array
    {
        $db = db_connect();
        $db->table('user_subscriptions')->insert(['user_id' => $userId, 'plan_id' => $planId, 'status' => 'pending']);
        $sub = (int) $db->insertID();
        $db->table('payments')->insert(['plan_id' => $planId, 'subscription_id' => $sub, 'user_id' => $userId, 'gateway' => 'payfast', 'amount_cents' => 9900, 'currency' => 'ZAR', 'status' => 'pending']);

        return [$sub, (int) $db->insertID()];
    }

    private function itn(array $fields)
    {
        $pairs = [];
        foreach ($fields as $k => $v) {
            $pairs[] = $k . '=' . urlencode((string) $v);
        }
        $fields['signature'] = md5(implode('&', $pairs));

        return $this->post('webhooks/payfast', $fields);
    }

    private function emails(string $template): int
    {
        return db_connect()->table('email_queue')->where('template', $template)->countAllResults();
    }

    public function testPayfastFirstPaymentRenewalAndDuplicates(): void
    {
        $user = $this->makeUser();
        $this->makeUser('admin');
        $plan = $this->plan();
        [$sub, $payment] = $this->pendingSubscription((int) $user->id, $plan);

        // Wrong amount is ignored.
        $this->itn(['m_payment_id' => $payment, 'pf_payment_id' => '1', 'payment_status' => 'COMPLETE', 'amount_gross' => '1.00', 'token' => 'tok-1']);
        $this->assertSame('pending', (new UserSubscriptionModel())->find($sub)['status']);

        $this->itn(['m_payment_id' => $payment, 'pf_payment_id' => '1', 'payment_status' => 'COMPLETE', 'amount_gross' => '99.00', 'token' => 'tok-1']);
        $s = (new UserSubscriptionModel())->find($sub);
        $this->assertSame('active', $s['status']);
        $this->assertSame('tok-1', $s['gateway_token']);
        $this->assertSame(1, (int) $s['renews']);
        $this->assertTrue(Membership::hasAccess((int) $user->id));
        $this->assertSame(1, $this->emails('membership_started'));
        $this->assertSame(1, $this->emails('admin_membership'));

        // Next month's charge extends the period from the old due date.
        db_connect()->table('user_subscriptions')->where('id', $sub)->update(['last_payment_at' => date('Y-m-d H:i:s', strtotime('-30 days'))]);
        $due = $s['expires_at'];
        $this->itn(['m_payment_id' => $payment, 'pf_payment_id' => '2', 'payment_status' => 'COMPLETE', 'amount_gross' => '99.00', 'token' => 'tok-1']);
        $renewed = (new UserSubscriptionModel())->find($sub);
        $this->assertSame(date('Y-m-d', strtotime('+1 month', strtotime($due))), substr($renewed['expires_at'], 0, 10));
        $this->assertSame(1, $this->emails('membership_renewed'));

        // The same notification twice is recorded once.
        $this->itn(['m_payment_id' => $payment, 'pf_payment_id' => '2', 'payment_status' => 'COMPLETE', 'amount_gross' => '99.00', 'token' => 'tok-1']);
        $this->assertSame(2, db_connect()->table('payments')->where(['subscription_id' => $sub, 'status' => 'paid'])->countAllResults());

        // A forged notification (bad signature) is rejected.
        $this->post('webhooks/payfast', ['m_payment_id' => $payment, 'payment_status' => 'CANCELLED', 'signature' => 'nope'])->assertStatus(400);
        $this->assertSame('active', (new UserSubscriptionModel())->find($sub)['status']);
    }

    public function testMissedPaymentsLeadToRemindersThenAutomaticSuspension(): void
    {
        $user = $this->makeUser();
        $plan = $this->plan();
        ['course' => $course] = $this->makeCourse(1, ['in_membership' => true], ['access_mode' => 'paid', 'price_cents' => 50000]);
        [$sub] = $this->pendingSubscription((int) $user->id, $plan);
        Membership::activate($sub, 'payfast', 'tok-9');
        $this->assertNotNull(CourseAccess::resolve($course, (int) $user->id));
        $set = static fn (string $when) => db_connect()->table('user_subscriptions')->where('id', $sub)->update(['expires_at' => date('Y-m-d H:i:s', strtotime($when))]);

        $set('+2 days');
        $this->assertSame(1, Membership::checkAll()['reminded']);
        $this->assertSame(0, Membership::checkAll()['reminded'], 'only one reminder per period');

        $set('-1 hour');
        $this->assertSame(1, Membership::checkAll()['past_due']);
        $this->assertSame(1, $this->emails('membership_payment_failed'));
        $this->assertTrue(Membership::hasAccess((int) $user->id), 'grace period keeps access');

        $set('-' . (Membership::GRACE_DAYS - 1) . ' days');
        $this->assertSame(1, Membership::checkAll()['final_notice']);

        $set('-' . (Membership::GRACE_DAYS + 1) . ' days');
        $this->assertSame(1, Membership::checkAll()['suspended']);
        $this->assertSame(1, $this->emails('membership_suspended'));
        $this->assertFalse(Membership::hasAccess((int) $user->id));
        $this->assertNull(CourseAccess::resolve($course, (int) $user->id), 'course access ends with the membership');
        $this->assertTrue(CourseAccess::lapsed($course, (int) $user->id));
    }

    public function testCancelledMembersKeepAccessUntilPeriodEnd(): void
    {
        $user = $this->makeUser();
        [$sub] = $this->pendingSubscription((int) $user->id, $this->plan());
        Membership::activate($sub, 'payfast', 'tok-3');

        $this->actingAs($user)->post('account/membership/cancel')->assertRedirect();
        $this->assertSame('cancelled', (new UserSubscriptionModel())->find($sub)['status']);
        $this->assertTrue(Membership::hasAccess((int) $user->id));

        db_connect()->table('user_subscriptions')->where('id', $sub)->update(['expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))]);
        $this->assertSame(1, Membership::checkAll()['ended']);
        $this->assertFalse(Membership::hasAccess((int) $user->id));
    }

    public function testBuyingOneModuleUnlocksOnlyThatModuleUntilTheCourseIsBought(): void
    {
        // No gateway configured, so checkout offers the development test payment.
        putenv('PAYFAST_MERCHANT_ID');
        putenv('PAYFAST_MERCHANT_KEY');
        ['course' => $course, 'section' => $first] = $this->makeCourse(2, [], ['access_mode' => 'paid', 'price_cents' => 50000]);
        $db = db_connect();
        $db->table('course_sections')->insert(['course_id' => $course['id'], 'title' => 'Week 2', 'position' => 2, 'price_cents' => 15000]);
        $second = (int) $db->insertID();
        $db->table('lessons')->insert(['course_id' => $course['id'], 'section_id' => $second, 'title' => 'Week 2 lesson', 'type' => 'text', 'position' => 1, 'status' => 'published', 'price_cents' => 0]);
        $user = $this->makeUser();

        $this->actingAs($user)->post('checkout/module/' . $second, ['gateway' => 'test'])->assertRedirect();
        $enrollment = CourseAccess::resolve($course, (int) $user->id);
        $this->assertSame('module', $enrollment['source']);
        $this->assertSame(1, $this->emails('purchase_receipt'));

        $sections = Curriculum::annotate(Curriculum::load((int) $course['id']), $course, $enrollment, (int) $user->id);
        $byId     = array_column($sections, null, 'id');
        $this->assertFalse($byId[$second]['items'][0]['locked']);
        $this->assertTrue($byId[$first]['items'][0]['locked']);
        $this->assertSame('Buy the full course to unlock', $byId[$first]['items'][0]['reason']);

        // Buying the whole course upgrades the same enrollment.
        $this->actingAs($user)->post('checkout/course/' . $course['id'], ['gateway' => 'test']);
        $enrollment = (new EnrollmentModel())->findFor((int) $course['id'], (int) $user->id);
        $this->assertSame('purchase', $enrollment['source']);
        $flat = Curriculum::flatten(Curriculum::annotate(Curriculum::load((int) $course['id']), $course, $enrollment, (int) $user->id));
        $this->assertSame([], array_filter($flat, static fn ($i) => $i['locked']));
    }

    public function testCoursesCanBeBoughtOrIncludedInMembership(): void
    {
        ['course' => $course] = $this->makeCourse(1, ['in_membership' => true], ['access_mode' => 'paid', 'price_cents' => 50000]);
        $member = $this->makeUser();
        $other  = $this->makeUser();
        [$sub] = $this->pendingSubscription((int) $member->id, $this->plan());
        Membership::activate($sub, 'test');

        $this->assertSame('subscription', CourseAccess::resolve($course, (int) $member->id)['source']);
        $this->assertNull(CourseAccess::resolve($course, (int) $other->id));
    }

    public function testBrandedEmailsAreQueuedAndDelivered(): void
    {
        $user = $this->makeUser('student', 'thandi');
        Notify::welcome((int) $user->id);
        $row = db_connect()->table('email_queue')->where('template', 'welcome')->get()->getRowArray();

        $this->assertSame('thandi@example.test', $row['to_email']);
        $this->assertStringContainsString('logo-mark.png', $row['html']);
        $this->assertStringContainsString('Hi thandi', $row['html']);
        $this->assertNotEmpty($row['text']);

        [$sent] = Mailer::flush();
        $this->assertSame(1, $sent);
        $this->seeInDatabase('email_queue', ['id' => $row['id'], 'status' => 'sent']);
    }

    public function testCommunityEmailsRespectPreferences(): void
    {
        $user = $this->makeUser();
        (new \App\Modules\Core\Models\UserProfileModel())->savePreferences((int) $user->id, ['email_community' => '0']);
        Notify::prayedFor((int) $user->id, 'Healing', 'Grace');

        $this->assertSame(0, $this->emails('prayed_for'));
        $this->seeInDatabase('notifications', ['user_id' => $user->id, 'type' => 'prayer']);
    }

    public function testLearningRemindersArePersonalAndNotRepeatedTooOften(): void
    {
        ['course' => $course, 'lessons' => $lessons] = $this->makeCourse(3);
        $user = $this->makeUser('student', 'grace');
        (new \App\Modules\Core\Models\UserProfileModel())->upsert((int) $user->id, ['display_name' => 'Grace Mokoena']);
        $e = (new EnrollmentModel())->enroll((int) $course['id'], (int) $user->id);
        (new EnrollmentModel())->markLessonComplete((int) $e['id'], $lessons[0]);
        db_connect()->table('enrollments')->where('id', $e['id'])->update(['last_activity_at' => date('Y-m-d H:i:s', strtotime('-5 days'))]);
        db_connect()->table('users')->where('id', $user->id)->update(['last_active' => date('Y-m-d H:i:s', strtotime('-5 days'))]);

        $this->assertSame(1, LearningReminders::run()['learners']);
        $mail = db_connect()->table('email_queue')->where('template', 'learning_reminder')->get()->getRowArray();
        $this->assertStringStartsWith('Grace, your next lesson is waiting: Lesson 2', $mail['subject']);
        $this->assertStringContainsString('Lesson 2', $mail['html']);
        $this->assertStringContainsString('1 of 3 lessons complete', $mail['html']);
        $this->assertStringContainsString('5 days', $mail['html']);

        // Not again the next day.
        $this->assertSame(0, LearningReminders::run(strtotime('+1 day'))['learners']);
    }

    public function testCronRunsEverything(): void
    {
        command('app:cron');
        $this->assertTrue(Mailer::cronIsRunning());
    }
}
