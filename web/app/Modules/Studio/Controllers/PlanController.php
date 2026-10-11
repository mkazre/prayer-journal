<?php

namespace App\Modules\Studio\Controllers;

use App\Modules\Shop\Models\SubscriptionPlanModel;

class PlanController extends StudioController
{
    public function index(): string
    {
        $this->requireManager();
        $plans = (new SubscriptionPlanModel())->findAll();
        foreach ($plans as &$p) {
            $p['members'] = db_connect()->table('user_subscriptions')->where('plan_id', $p['id'])->whereIn('status', ['active', 'past_due', 'cancelled'])->countAllResults();
        }
        $members = db_connect()->table('user_subscriptions s')
            ->select('s.*, sp.name AS plan_name, sp.price_cents, sp.currency, u.username, i.secret AS email, pr.display_name')
            ->join('subscription_plans sp', 'sp.id = s.plan_id')->join('users u', 'u.id = s.user_id')
            ->join('auth_identities i', "i.user_id = u.id AND i.type = 'email_password'", 'left')->join('user_profiles pr', 'pr.user_id = u.id', 'left')
            ->where('s.status !=', 'pending')->orderBy("CASE s.status WHEN 'past_due' THEN 0 WHEN 'active' THEN 1 WHEN 'cancelled' THEN 2 ELSE 3 END", '', false)->orderBy('s.expires_at')
            ->limit(300)->get()->getResultArray();

        return $this->render('shop/plans', ['title' => 'Membership plans', 'active' => 'plans', 'plans' => $plans, 'members' => $members,
            'courses' => db_connect()->table('courses')->select('id, title, access_mode, settings')->where('deleted_at IS NULL')->orderBy('title')->get()->getResultArray()]);
    }

    public function save()
    {
        $this->requireManager();
        $name = trim((string) $this->post('name'));
        if ($name === '') {
            return $this->failed('Name the plan.');
        }
        $type = $this->post('type') === 'lifetime' ? 'lifetime' : 'subscription';
        $data = ['name' => $name, 'type' => $type, 'billing_interval' => $type === 'subscription' ? ($this->post('billing_interval') === 'year' ? 'year' : 'month') : null,
            'price_cents' => max(0, $this->money('price')), 'currency' => store_currency(), 'is_active' => 1];
        $model = new SubscriptionPlanModel();
        $id    = (int) $this->post('id');
        $id ? $model->update($id, $data) : $model->insert($data + ['created_at' => date('Y-m-d H:i:s')]);

        return $this->done('Plan saved.', [], site_url('studio/plans'));
    }

    public function toggle(int $id)
    {
        $this->requireManager();
        $model = new SubscriptionPlanModel();
        $plan  = $model->find($id) ?? $this->notFound();
        $model->update($id, ['is_active' => $plan['is_active'] ? 0 : 1]);

        return $this->done($plan['is_active'] ? 'Plan hidden from the pricing page.' : 'Plan is live.');
    }

    public function cancelMember(int $subscriptionId)
    {
        $this->requireManager();
        $ok = \App\Modules\Shop\Services\Membership::cancel($subscriptionId);

        return $ok ? $this->done('Membership cancelled. The member keeps access until the end of the paid period.') : $this->failed('Could not cancel (already cancelled, or PayFast could not be reached).');
    }
}
