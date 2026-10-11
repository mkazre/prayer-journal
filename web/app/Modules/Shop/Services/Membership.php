<?php

namespace App\Modules\Shop\Services;

use App\Libraries\Notify;
use App\Modules\Shop\Models\SubscriptionPlanModel;
use App\Modules\Shop\Models\UserSubscriptionModel;

/**
 * The membership lifecycle.
 *
 *   pending ──payment──▶ active ──renewal payment──▶ active (period extended)
 *                          │
 *                          ├─ member cancels ──▶ cancelled (access until period end) ──▶ expired
 *                          └─ renewal not received ──▶ past_due (grace period)
 *                                   ├─ payment arrives ──▶ active
 *                                   └─ grace ends ──▶ expired (access suspended automatically)
 *
 * `checkAll()` runs from the cron job and sends the reminder, payment-failed,
 * final-notice and suspension emails at the right moments.
 */
class Membership
{
    /** Days a member keeps access after a missed renewal, while PayFast retries. */
    public const GRACE_DAYS = 5;

    /** Send the "renews soon" reminder this many days before renewal. */
    public const REMIND_DAYS = 3;

    public static function hasAccess(int $userId): bool
    {
        $now = time();
        foreach ((new UserSubscriptionModel())->where('user_id', $userId)->whereIn('status', ['active', 'cancelled', 'past_due'])->findAll() as $s) {
            if (! $s['expires_at']) {
                return true;
            }
            $end = strtotime($s['expires_at']) + ($s['status'] === 'past_due' || ($s['status'] === 'active' && $s['renews']) ? self::GRACE_DAYS * 86400 : 0);
            if ($end > $now) {
                return true;
            }
        }

        return false;
    }

    public static function current(int $userId): ?array
    {
        $row = (new UserSubscriptionModel())->where('user_id', $userId)->whereIn('status', ['active', 'cancelled', 'past_due'])->orderBy('id', 'DESC')->first();
        if (! $row) {
            return null;
        }
        $row['plan'] = (new SubscriptionPlanModel())->find($row['plan_id']);

        return $row;
    }

    /**
     * First successful payment.
     */
    public static function activate(int $subscriptionId, ?string $gateway = null, ?string $token = null): void
    {
        $model = new UserSubscriptionModel();
        $sub   = $model->find($subscriptionId);
        if (! $sub || in_array($sub['status'], ['active', 'cancelled'], true)) {
            return;
        }
        $plan  = (new SubscriptionPlanModel())->find($sub['plan_id']);
        $isSub = $plan && $plan['type'] === 'subscription';
        $model->update($subscriptionId, [
            'status'          => 'active',
            'started_at'      => date('Y-m-d H:i:s'),
            'expires_at'      => $isSub ? self::nextPeriod(time(), $plan) : null,
            'gateway'         => $gateway,
            'gateway_token'   => $token,
            'renews'          => $isSub && $gateway === 'payfast' && $token ? 1 : 0,
            'last_payment_at' => date('Y-m-d H:i:s'),
            'notice_stage'    => 0,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        // One active membership at a time: retire any older ones.
        $model->where('user_id', $sub['user_id'])->where('id !=', $subscriptionId)->whereIn('status', ['active', 'past_due'])->set(['status' => 'expired', 'updated_at' => date('Y-m-d H:i:s')])->update();

        Notify::membership('started', $model->find($subscriptionId), $plan);
    }

    /**
     * A recurring payment arrived (PayFast ITN). Extends the period from the
     * previous due date, so paying late never shortens the next period.
     */
    public static function renew(int $subscriptionId): void
    {
        $model = new UserSubscriptionModel();
        $sub   = $model->find($subscriptionId);
        if (! $sub) {
            return;
        }
        $plan = (new SubscriptionPlanModel())->find($sub['plan_id']);
        $from = max(time(), $sub['expires_at'] ? strtotime($sub['expires_at']) : time());
        // A renewal that arrives within a period (e.g. a duplicate ITN) only counts once.
        if ($sub['last_payment_at'] && strtotime($sub['last_payment_at']) > time() - 20 * 86400 && $sub['status'] === 'active') {
            return;
        }
        $model->update($subscriptionId, [
            'status'          => 'active',
            'expires_at'      => self::nextPeriod($sub['status'] === 'expired' ? time() : $from, $plan),
            'last_payment_at' => date('Y-m-d H:i:s'),
            'notice_stage'    => 0,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        Notify::membership('renewed', $model->find($subscriptionId), $plan);
    }

    /**
     * Stops future billing; access continues to the end of the paid period.
     */
    public static function cancel(int $subscriptionId, bool $callGateway = true): bool
    {
        $model = new UserSubscriptionModel();
        $sub   = $model->find($subscriptionId);
        if (! $sub || ! in_array($sub['status'], ['active', 'past_due'], true)) {
            return false;
        }
        if ($callGateway && $sub['gateway'] === 'payfast' && $sub['gateway_token']) {
            if (! (new PayFastGateway())->cancelSubscription($sub['gateway_token'])) {
                return false;
            }
        }
        $model->update($subscriptionId, ['status' => $sub['status'] === 'past_due' ? 'expired' : 'cancelled', 'renews' => 0, 'cancelled_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        Notify::membership('cancelled', $model->find($subscriptionId), (new SubscriptionPlanModel())->find($sub['plan_id']));

        return true;
    }

    /**
     * Cron: reminders, missed payments, grace period and automatic suspension.
     *
     * @return array<string,int> counts per action
     */
    public static function checkAll(): array
    {
        $model = new UserSubscriptionModel();
        $plans = [];
        $plan  = static function (int $id) use (&$plans) {
            return $plans[$id] ??= (new SubscriptionPlanModel())->find($id);
        };
        $now  = time();
        $done = ['reminded' => 0, 'past_due' => 0, 'final_notice' => 0, 'suspended' => 0, 'ended' => 0];

        foreach ($model->whereIn('status', ['active', 'cancelled', 'past_due'])->where('expires_at IS NOT NULL')->findAll() as $s) {
            $p   = $plan((int) $s['plan_id']);
            $due = strtotime($s['expires_at']);
            if (! $p) {
                continue;
            }

            if ($s['status'] === 'cancelled' && $due <= $now) {
                $model->update($s['id'], ['status' => 'expired', 'updated_at' => date('Y-m-d H:i:s')]);
                Notify::membership('ended', $s, $p);
                $done['ended']++;
            } elseif ($s['status'] === 'active' && ! $s['renews'] && $due <= $now) {
                // One-off period (e.g. paid without a recurring token) simply ends.
                $model->update($s['id'], ['status' => 'expired', 'updated_at' => date('Y-m-d H:i:s')]);
                Notify::membership('ended', $s, $p);
                $done['ended']++;
            } elseif ($s['status'] === 'active' && $s['renews'] && $due <= $now) {
                $model->update($s['id'], ['status' => 'past_due', 'notice_stage' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
                Notify::membership('payment_failed', $s, $p);
                $done['past_due']++;
            } elseif ($s['status'] === 'active' && $s['renews'] && $due - $now <= self::REMIND_DAYS * 86400 && $s['reminded_for'] !== $s['expires_at']) {
                $model->update($s['id'], ['reminded_for' => $s['expires_at'], 'updated_at' => date('Y-m-d H:i:s')]);
                Notify::membership('reminder', $s, $p);
                $done['reminded']++;
            } elseif ($s['status'] === 'past_due') {
                $graceEnd = $due + self::GRACE_DAYS * 86400;
                if ($now >= $graceEnd) {
                    $model->update($s['id'], ['status' => 'expired', 'renews' => 0, 'notice_stage' => 3, 'updated_at' => date('Y-m-d H:i:s')]);
                    if ($s['gateway'] === 'payfast' && $s['gateway_token']) {
                        // Stop PayFast retrying a membership we have suspended.
                        (new PayFastGateway())->cancelSubscription($s['gateway_token']);
                    }
                    Notify::membership('suspended', $s, $p);
                    $done['suspended']++;
                } elseif ((int) $s['notice_stage'] < 2 && $graceEnd - $now <= 2 * 86400) {
                    $model->update($s['id'], ['notice_stage' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
                    Notify::membership('final_notice', $s, $p);
                    $done['final_notice']++;
                }
            }
        }

        return $done;
    }

    public static function nextPeriod(int $from, ?array $plan): ?string
    {
        if (! $plan || $plan['type'] !== 'subscription') {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime($plan['billing_interval'] === 'year' ? '+1 year' : '+1 month', $from));
    }
}
