<?php

namespace App\Libraries;

use App\Modules\Shop\Models\OrderModel;

/**
 * Every notification the platform sends: an in-app notification plus a
 * branded email. Account, purchase and membership emails always send;
 * community and course-update emails respect the user's preferences.
 *
 * User-supplied text is escaped here because the email template prints
 * paragraphs as HTML.
 */
class Notify
{
    /* ------------------------------------------------------------ */
    /* Account                                                      */
    /* ------------------------------------------------------------ */

    public static function welcome(int $userId): void
    {
        $u = self::user($userId);
        if (! $u) {
            return;
        }
        self::email('welcome', $u, 'Welcome to ' . site_name(), [
            'heading'    => 'Welcome to ' . site_name(),
            'preheader'  => 'Your account is ready: start with the Prayer Journal course.',
            'paragraphs' => [
                'Your account is ready. Here is how to begin:',
                '<strong>1. Start the course.</strong> <em>My Prayer Journal</em> walks you through six prayer secrets and guided daily devotions.<br><strong>2. Keep a journal.</strong> Write prayer points, meditations and gratitude, and mark prayers answered.<br><strong>3. Read the Bible.</strong> Follow a reading plan and build a daily streak.',
            ],
            'button'    => ['Go to my dashboard', site_url('dashboard')],
            'secondary' => ['Browse courses', site_url('courses')],
        ]);
    }

    public static function accountCreatedByAdmin(int $userId, ?string $temporaryPassword): void
    {
        $u = self::user($userId);
        if (! $u) {
            return;
        }
        $rows = ['Email' => esc($u['email']), 'Username' => esc($u['username'])];
        if ($temporaryPassword) {
            $rows['Temporary password'] = '<span style="font-family:monospace;font-size:15px">' . esc($temporaryPassword) . '</span>';
        }
        self::email('account_created', $u, 'Your ' . site_name() . ' account is ready', [
            'heading'    => 'An account has been created for you',
            'paragraphs' => ['The ' . esc(site_name()) . ' team has set up an account for you. Sign in with the details below.'],
            'rows'       => $rows,
            'button'     => ['Sign in', site_url('login')],
            'note'       => $temporaryPassword ? 'For your security, please change this password after signing in (Account settings → Security).' : null,
        ]);
    }

    public static function passwordChanged(int $userId, bool $byAdmin = false): void
    {
        $u = self::user($userId);
        if (! $u) {
            return;
        }
        self::email('password_changed', $u, 'Your password was changed', [
            'heading'    => 'Your password was changed',
            'tone'       => 'warning',
            'paragraphs' => [($byAdmin ? 'An administrator reset the password on your account' : 'The password on your account was just changed') . ' on ' . date('j F Y \a\t H:i') . '. You have been signed out of the app on other devices.'],
            'note'       => 'If you did not expect this, reply to this email or contact us straight away.',
            'button'     => ['Sign in', site_url('login')],
        ]);
    }

    public static function accountSuspended(int $userId): void
    {
        $u = self::user($userId);
        if (! $u) {
            return;
        }
        self::email('account_suspended', $u, 'Your account has been suspended', [
            'heading'    => 'Your account has been suspended',
            'tone'       => 'danger',
            'paragraphs' => ['Access to your ' . esc(site_name()) . ' account has been paused by our team.'],
            'note'       => 'If you think this is a mistake, simply reply to this email and we will help.',
        ]);
    }

    public static function accountRestored(int $userId): void
    {
        $u = self::user($userId);
        if (! $u) {
            return;
        }
        self::email('account_restored', $u, 'Your account is active again', [
            'heading'    => 'Welcome back',
            'tone'       => 'success',
            'paragraphs' => ['Your account has been restored and you can sign in again.'],
            'button'     => ['Sign in', site_url('login')],
        ]);
    }

    /* ------------------------------------------------------------ */
    /* Learning                                                     */
    /* ------------------------------------------------------------ */

    public static function enrolled(int $userId, array $course): void
    {
        $u = self::user($userId);
        if (! $u) {
            return;
        }
        self::inApp($userId, 'course', 'You are enrolled in ' . $course['title'] . '.');
        self::email('enrolled', $u, 'You are enrolled: ' . $course['title'], [
            'heading'    => 'You are enrolled',
            'paragraphs' => ['Welcome to <strong>' . esc($course['title']) . '</strong>. Your progress, notes and journal entries are saved as you go, so you can pick up on any device.'],
            'button'     => ['Start learning', site_url('study/' . $course['slug'])],
        ]);
    }

    public static function courseCompleted(int $userId, array $course, ?string $certificateCode): void
    {
        $u = self::user($userId);
        if (! $u) {
            return;
        }
        self::inApp($userId, 'course', 'You completed ' . $course['title'] . '.');
        self::email('course_completed', $u, 'Congratulations — you completed ' . $course['title'], [
            'heading'    => 'Well done — course complete!',
            'tone'       => 'success',
            'paragraphs' => ['You have completed every lesson of <strong>' . esc($course['title']) . '</strong>. What a faithful journey.'],
            'button'     => $certificateCode ? ['View your certificate', site_url('certificates/' . $certificateCode)] : ['Back to my learning', site_url('my-learning')],
            'secondary'  => ['Open my journal', site_url('journal')],
        ]);
    }

    public static function assignmentGraded(int $userId, string $assignment, int $points, int $max, string $feedback, string $courseSlug): void
    {
        $u = self::user($userId);
        if (! $u) {
            return;
        }
        self::inApp($userId, 'graded', 'Your assignment "' . $assignment . '" was graded: ' . $points . '/' . $max);
        self::email('assignment_graded', $u, 'Your assignment was graded: ' . $assignment, [
            'heading'    => 'Your assignment was graded',
            'rows'       => ['Assignment' => esc($assignment), 'Score' => $points . ' / ' . $max],
            'paragraphs' => $feedback !== '' ? ['<strong>Feedback from your instructor</strong><br>' . nl2br(esc($feedback))] : [],
            'button'     => ['Open the course', site_url('study/' . $courseSlug)],
        ]);
    }

    public static function assignmentSubmitted(array $course, string $assignment, int $learnerId): void
    {
        $learner = self::user($learnerId);
        $ids     = array_unique(array_merge([(int) $course['created_by']], array_map('intval', array_column(db_connect()->table('course_instructors')->select('user_id')->where('course_id', $course['id'])->get()->getResultArray(), 'user_id'))));
        foreach ($ids as $id) {
            if ($id === $learnerId || ! ($u = self::user($id))) {
                continue;
            }
            self::inApp($id, 'assignment', ($learner['name'] ?? 'A learner') . ' submitted "' . $assignment . '".');
            self::email('assignment_submitted', $u, 'New submission to grade: ' . $assignment, [
                'heading'    => 'A new assignment is ready to grade',
                'rows'       => ['Course' => esc($course['title']), 'Assignment' => esc($assignment), 'Learner' => esc($learner['name'] ?? '')],
                'button'     => ['Open grading', site_url('studio/grading')],
                'footer'     => 'You are receiving this because you teach this course.',
            ]);
        }
    }

    public static function announcement(array $course, string $title, string $body): void
    {
        $rows = db_connect()->table('enrollments')->select('user_id')->where('course_id', $course['id'])->where('status !=', 'revoked')->get()->getResultArray();
        foreach ($rows as $r) {
            $id = (int) $r['user_id'];
            self::inApp($id, 'announcement', $course['title'] . ': ' . $title);
            if (self::wants($id, 'email_courses') && ($u = self::user($id))) {
                self::email('announcement', $u, $title . ' — ' . $course['title'], [
                    'heading'    => $title,
                    'paragraphs' => array_map(static fn ($p) => nl2br(esc($p)), preg_split('/\n{2,}/', trim($body))),
                    'button'     => ['Open the course', site_url('study/' . $course['slug'])],
                    'footer'     => 'You are receiving this because you are enrolled in ' . $course['title'] . '. Turn off course updates in your email preferences.',
                ]);
            }
        }
    }

    /* ------------------------------------------------------------ */
    /* Shop                                                         */
    /* ------------------------------------------------------------ */

    public static function orderConfirmed(int $orderId): void
    {
        $orders = new OrderModel();
        $order  = $orders->find($orderId);
        if (! $order) {
            return;
        }
        [$items, $totals] = self::orderLines($orders, $order);
        $hasDigital = (bool) array_filter($orders->items($orderId), static fn ($i) => ($i['product_type'] ?? '') === 'digital');
        if ($order['user_id']) {
            self::inApp((int) $order['user_id'], 'order', 'Payment received for order ' . $order['order_number'] . '.');
        }
        $to = ['id' => $order['user_id'] ? (int) $order['user_id'] : null, 'email' => $order['email'], 'name' => $order['customer_name'] ?: ''];
        self::email('order_confirmed', $to, 'Order ' . $order['order_number'] . ' confirmed — thank you!', [
            'heading'    => 'Thank you for your order',
            'tone'       => 'success',
            'preheader'  => 'We have received your payment for order ' . $order['order_number'] . '.',
            'paragraphs' => [$hasDigital ? 'Your payment was received. Your downloads are ready on the order page.' : 'Your payment was received and we are preparing your order. We will email you again when it ships.'],
            'rows'       => ['Order' => esc($order['order_number']), 'Date' => date('j F Y', strtotime((string) ($order['paid_at'] ?: $order['created_at'])))],
            'items'      => $items,
            'totals'     => $totals,
            'button'     => ['View your order', site_url('orders/' . $orderId)],
        ]);

        foreach (Mailer::adminRecipients() as $admin) {
            self::email('admin_new_order', ['email' => $admin, 'name' => null, 'id' => null], 'New order ' . $order['order_number'] . ' — ' . money((int) $order['total_cents'], $order['currency']), [
                'heading' => 'New order received',
                'rows'    => ['Order' => esc($order['order_number']), 'Customer' => esc(trim(($order['customer_name'] ?: '') . ' <' . $order['email'] . '>')), 'Total' => money((int) $order['total_cents'], $order['currency']), 'Shipping' => esc((string) ($order['shipping_method'] ?: 'Digital / none'))],
                'items'   => $items,
                'button'  => ['Open in Studio', site_url('studio/orders/' . $orderId)],
                'footer'  => 'Admin alert from ' . site_name() . '.',
            ]);
        }
    }

    public static function orderShipped(int $orderId, ?string $carrier, ?string $tracking): void
    {
        $order = (new OrderModel())->find($orderId);
        if (! $order) {
            return;
        }
        $rows = ['Order' => esc($order['order_number'])];
        if ($carrier) {
            $rows['Courier'] = esc($carrier);
        }
        if ($tracking) {
            $rows['Tracking number'] = esc($tracking);
        }
        self::email('order_shipped', ['id' => $order['user_id'] ? (int) $order['user_id'] : null, 'email' => $order['email'], 'name' => $order['customer_name'] ?: ''], 'Your order ' . $order['order_number'] . ' is on its way', [
            'heading'    => 'Your order is on its way',
            'tone'       => 'success',
            'paragraphs' => ['Good news: your order has been handed to the courier.'],
            'rows'       => $rows,
            'button'     => ['Track your order', site_url('orders/' . $orderId)],
        ]);
    }

    public static function orderStatus(int $orderId, string $status): void
    {
        $order = (new OrderModel())->find($orderId);
        if (! $order || ! in_array($status, ['cancelled', 'refunded', 'completed'], true)) {
            return;
        }
        $copy = [
            'cancelled' => ['Your order has been cancelled', 'Order ' . $order['order_number'] . ' has been cancelled. If you were charged, the refund will follow shortly.', 'danger'],
            'refunded'  => ['Your refund has been processed', 'We have refunded order ' . $order['order_number'] . '. Depending on your bank it can take 5–10 working days to appear.', 'brand'],
            'completed' => ['Your order is complete', 'Order ' . $order['order_number'] . ' has been delivered. We pray it blesses you!', 'success'],
        ][$status];
        if ($order['user_id']) {
            self::inApp((int) $order['user_id'], 'order', $copy[1]);
        }
        self::email('order_' . $status, ['id' => $order['user_id'] ? (int) $order['user_id'] : null, 'email' => $order['email'], 'name' => $order['customer_name'] ?: ''], $copy[0] . ' (' . $order['order_number'] . ')', [
            'heading'    => $copy[0],
            'tone'       => $copy[2],
            'paragraphs' => [esc($copy[1])],
            'rows'       => ['Order total' => money((int) $order['total_cents'], $order['currency'])],
            'button'     => ['View your order', site_url('orders/' . $orderId)],
            'note'       => 'Questions? Just reply to this email.',
        ]);
    }

    /**
     * Receipt for a course, module or single-lesson purchase.
     */
    public static function purchaseReceipt(int $userId, string $kind, string $title, int $amountCents, string $currency, string $url, string $reference): void
    {
        $u = self::user($userId);
        if (! $u) {
            return;
        }
        self::inApp($userId, 'purchase', 'Payment received: ' . $title . '.');
        self::email('purchase_receipt', $u, 'Receipt: ' . $title, [
            'heading'    => 'Payment received — thank you',
            'tone'       => 'success',
            'paragraphs' => ['Your ' . esc(strtolower($kind)) . ' is unlocked and ready.'],
            'items'      => [['title' => $title, 'meta' => esc($kind), 'amount' => money($amountCents, $currency)]],
            'totals'     => ['Total paid' => money($amountCents, $currency)],
            'rows'       => ['Reference' => esc($reference), 'Date' => date('j F Y')],
            'button'     => ['Start now', $url],
        ]);
    }

    /* ------------------------------------------------------------ */
    /* Membership                                                   */
    /* ------------------------------------------------------------ */

    public static function membership(string $event, array $sub, array $plan): void
    {
        $u = self::user((int) $sub['user_id']);
        if (! $u) {
            return;
        }
        $price   = money((int) $plan['price_cents'], $plan['currency']) . ($plan['type'] === 'subscription' ? ' / ' . ($plan['billing_interval'] === 'year' ? 'year' : 'month') : '');
        $until   = $sub['expires_at'] ? date('j F Y', strtotime($sub['expires_at'])) : null;
        $graceTo = $sub['expires_at'] ? date('j F Y', strtotime($sub['expires_at']) + \App\Modules\Shop\Services\Membership::GRACE_DAYS * 86400) : null;
        $manage  = ['Manage membership', site_url('account#membership')];
        $update  = $sub['gateway'] === 'payfast' && $sub['gateway_token'] ? ['Update payment details', \App\Modules\Shop\Services\PayFastGateway::updateCardUrl($sub['gateway_token'], site_url('account#membership'))] : $manage;
        $rows    = ['Plan' => esc($plan['name']), 'Price' => $price];

        $c = match ($event) {
            'started' => ['Your membership is active', 'Welcome — your ' . $plan['name'] . ' is active', 'success', [
                'Thank you for joining! You now have access to every membership course.',
                $plan['type'] === 'subscription' ? 'Your membership renews automatically' . ($until ? ' on <strong>' . $until . '</strong>' : '') . '. You can cancel at any time.' : 'This is a lifetime membership — no renewals.',
            ], ['Explore courses', site_url('courses')]],
            'renewed' => ['Membership renewed — receipt', 'Thank you — your membership has renewed', 'success', [
                'We received your payment and your membership continues' . ($until ? ' until <strong>' . $until . '</strong>' : '') . '.',
            ], ['Continue learning', site_url('my-learning')]],
            'reminder' => ['Your membership renews on ' . $until, 'Your membership renews soon', 'brand', [
                'Just a friendly reminder: your ' . esc($plan['name']) . ' renews automatically on <strong>' . $until . '</strong> and ' . $price . ' will be charged to your saved payment method.',
                'Nothing to do if you would like to continue. To change your card or cancel, use the buttons below.',
            ], $update],
            'payment_failed' => ['Action needed: we could not process your membership payment', 'We could not process your payment', 'warning', [
                'Your membership renewal payment did not go through. Your access continues for now while we try again.',
                'To avoid interruption, please update your payment details before <strong>' . $graceTo . '</strong>.',
            ], $update],
            'final_notice' => ['Final notice: your membership will be suspended', 'Your access will be paused soon', 'danger', [
                'We still have not been able to collect your membership payment.',
                'Your access to membership courses will be <strong>suspended on ' . $graceTo . '</strong> unless payment is received. Your progress and journal are kept safe.',
            ], $update],
            'suspended' => ['Your membership access has been suspended', 'Your membership has been suspended', 'danger', [
                'Because we could not collect your payment, access to membership courses has been paused.',
                'Your progress, notes and journal entries are kept safe. Rejoin any time to pick up exactly where you left off.',
            ], ['Renew membership', site_url('pricing')]],
            'cancelled' => ['Your membership has been cancelled', 'Your membership is cancelled', 'brand', [
                'We have cancelled your membership renewal' . ($until ? '. You keep full access until <strong>' . $until . '</strong>' : '') . '.',
                'Thank you for being part of the community. You are welcome back any time.',
            ], ['See plans', site_url('pricing')]],
            'ended' => ['Your membership has ended', 'Your membership has ended', 'brand', [
                'Your membership period is over and access to membership courses has ended. Your journal and progress are safe.',
            ], ['Rejoin', site_url('pricing')]],
            default => null,
        };
        if (! $c) {
            return;
        }
        [$subject, $heading, $tone, $paragraphs, $button] = $c;
        if ($until && in_array($event, ['started', 'renewed', 'cancelled'], true)) {
            $rows[$event === 'cancelled' ? 'Access until' : 'Next renewal'] = $until;
        }
        self::inApp((int) $sub['user_id'], 'membership', $heading . '.');
        self::email('membership_' . $event, $u, $subject, [
            'heading' => $heading, 'tone' => $tone, 'paragraphs' => $paragraphs, 'rows' => $rows, 'button' => $button,
            'secondary' => in_array($event, ['reminder', 'payment_failed', 'final_notice'], true) ? $manage : null,
            'note'    => in_array($event, ['payment_failed', 'final_notice', 'suspended'], true) ? 'Already sorted this out? Thank you — you can ignore this message.' : null,
        ]);

        if (in_array($event, ['started', 'cancelled', 'suspended'], true)) {
            foreach (Mailer::adminRecipients() as $admin) {
                self::email('admin_membership', ['email' => $admin, 'name' => null, 'id' => null], 'Membership ' . $event . ': ' . $u['name'], [
                    'heading' => 'Membership ' . $event,
                    'rows'    => ['Member' => esc($u['name'] . ' <' . $u['email'] . '>'), 'Plan' => esc($plan['name']), 'Price' => $price],
                    'button'  => ['Open memberships', site_url('studio/plans')],
                    'footer'  => 'Admin alert from ' . site_name() . '.',
                ]);
            }
        }
    }

    /* ------------------------------------------------------------ */
    /* Community                                                    */
    /* ------------------------------------------------------------ */

    public static function prayedFor(int $authorId, string $requestBody, string $prayerName): void
    {
        self::inApp($authorId, 'prayer', $prayerName . ' prayed for your request.');
        // At most one email a day per person, so a busy wall never floods an inbox.
        $recent = db_connect()->table('email_queue')->where(['user_id' => $authorId, 'template' => 'prayed_for'])->where('created_at >', date('Y-m-d H:i:s', strtotime('-1 day')))->countAllResults();
        if ($recent || ! self::wants($authorId, 'email_community') || ! ($u = self::user($authorId))) {
            return;
        }
        self::email('prayed_for', $u, 'Someone is praying for you', [
            'heading'    => 'You are not alone',
            'paragraphs' => [esc($prayerName) . ' prayed for your request:', '<em>“' . esc(mb_strimwidth($requestBody, 0, 220, '…')) . '”</em>'],
            'button'     => ['Open the prayer wall', site_url('prayer-requests')],
            'footer'     => 'You can turn off prayer wall emails in your email preferences.',
        ]);
    }

    public static function discussionReply(int $authorId, string $replierName, string $lessonTitle, string $url): void
    {
        self::inApp($authorId, 'discussion', $replierName . ' replied to you in "' . $lessonTitle . '".');
        if (! self::wants($authorId, 'email_community') || ! ($u = self::user($authorId))) {
            return;
        }
        self::email('discussion_reply', $u, $replierName . ' replied to your question', [
            'heading'    => 'New reply to your question',
            'paragraphs' => [esc($replierName) . ' replied in the lesson <strong>' . esc($lessonTitle) . '</strong>.'],
            'button'     => ['Read the reply', $url],
        ]);
    }

    /* ------------------------------------------------------------ */
    /* Plumbing                                                     */
    /* ------------------------------------------------------------ */

    /**
     * @return array{id:int,email:string,name:string,username:string}|null
     */
    public static function user(int $userId): ?array
    {
        $row = db_connect()->table('users u')->select('u.id, u.username, i.secret AS email, p.display_name')
            ->join('auth_identities i', "i.user_id = u.id AND i.type = 'email_password'", 'left')
            ->join('user_profiles p', 'p.user_id = u.id', 'left')
            ->where('u.id', $userId)->where('u.deleted_at IS NULL')->get()->getRowArray();
        if (! $row || ! $row['email']) {
            return null;
        }

        return ['id' => (int) $row['id'], 'email' => $row['email'], 'username' => $row['username'], 'name' => $row['display_name'] ?: $row['username']];
    }

    public static function wants(int $userId, string $pref): bool
    {
        return (bool) (user_prefs($userId, true)[$pref] ?? true);
    }

    public static function inApp(int $userId, string $type, string $body): void
    {
        db_connect()->table('notifications')->insert(['user_id' => $userId, 'type' => $type, 'body' => mb_substr($body, 0, 500), 'created_at' => date('Y-m-d H:i:s')]);
    }

    private static function email(string $template, array $to, string $subject, array $content): void
    {
        try {
            Mailer::queue($template, $to['email'], $to['name'] ?? null, $subject, array_filter($content, static fn ($v) => $v !== null && $v !== []), $to['id'] ?? null);
        } catch (\Throwable $e) {
            // Email must never break the action that triggered it.
            log_message('error', 'Email "' . $template . '" failed: ' . $e->getMessage());
        }
    }

    /**
     * @return array{0:list<array>,1:array<string,string>}
     */
    private static function orderLines(OrderModel $orders, array $order): array
    {
        $cur   = $order['currency'];
        $items = array_map(static fn ($i) => ['title' => $i['title_snapshot'], 'meta' => 'Qty ' . (int) $i['quantity'] . (($i['product_type'] ?? '') === 'digital' ? ' · Digital download' : ''), 'amount' => money((int) $i['unit_price_cents'] * (int) $i['quantity'], $cur)], $orders->items((int) $order['id']));
        $totals = ['Subtotal' => money((int) $order['subtotal_cents'], $cur)];
        if ((int) $order['discount_cents']) {
            $totals['Discount'] = '−' . money((int) $order['discount_cents'], $cur);
        }
        if ((int) $order['shipping_cents']) {
            $totals['Shipping'] = money((int) $order['shipping_cents'], $cur);
        }
        if ((int) $order['tax_cents']) {
            $totals[site_setting('tax_label', 'VAT') . ((site_setting('prices_include_tax', '1') === '1') ? ' (included)' : '')] = money((int) $order['tax_cents'], $cur);
        }
        $totals['Total'] = money((int) $order['total_cents'], $cur);

        return [$items, $totals];
    }
}
