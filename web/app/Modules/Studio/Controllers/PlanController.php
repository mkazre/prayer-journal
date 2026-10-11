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
            $p['members'] = db_connect()->table('user_subscriptions')->where(['plan_id' => $p['id'], 'status' => 'active'])->countAllResults();
        }

        return $this->render('shop/plans', ['title' => 'Membership plans', 'active' => 'plans', 'plans' => $plans,
            'courses' => db_connect()->table('courses')->select('id, title, access_mode')->where('deleted_at IS NULL')->orderBy('title')->get()->getResultArray()]);
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
}
