<?php

namespace App\Modules\Studio\Controllers;

use App\Modules\Shop\Models\DiscountModel;

class DiscountController extends StudioController
{
    public function index(): string
    {
        $this->requireAdmin();
        $rows = (new DiscountModel())->orderBy('created_at', 'DESC')->findAll();
        foreach ($rows as &$d) {
            $d['problem'] = (new DiscountModel())->problem($d);
            $d['sales']   = (int) (db_connect()->table('orders')->selectSum('discount_cents')->where('discount_id', $d['id'])->whereIn('status', ['paid', 'processing', 'shipped', 'completed'])->get()->getRow('discount_cents') ?? 0);
        }

        return $this->render('shop/discounts', ['title' => 'Discounts', 'active' => 'discounts', 'discounts' => $rows]);
    }

    public function save()
    {
        $this->requireAdmin();
        $model = new DiscountModel();
        $id    = (int) $this->post('id');
        $code  = strtoupper(preg_replace('/[^A-Z0-9_-]/i', '', (string) $this->post('code')));
        if ($code === '') {
            $code = strtoupper(bin2hex(random_bytes(4)));
        }
        if ($model->where('code', $code)->where('id !=', $id)->first()) {
            return $this->failed('The code ' . $code . ' already exists.');
        }
        $type  = isset(DiscountModel::TYPES[$this->post('type')]) ? $this->post('type') : 'percent';
        $value = $type === 'fixed' ? $this->money('value') : (int) $this->post('value');
        if ($type === 'percent' && ($value < 1 || $value > 100)) {
            return $this->failed('A percentage must be between 1 and 100.');
        }
        $data = [
            'code'               => $code,
            'type'               => $type,
            'value'              => $type === 'shipping' ? 0 : $value,
            'description'        => trim((string) $this->post('description')) ?: null,
            'min_subtotal_cents' => $this->post('min_subtotal') !== '' ? $this->money('min_subtotal') : null,
            'max_redemptions'    => $this->post('max_redemptions') !== '' ? max(1, (int) $this->post('max_redemptions')) : null,
            'starts_at'          => $this->post('starts_at') ? date('Y-m-d H:i:s', strtotime($this->post('starts_at'))) : null,
            'expires_at'         => $this->post('expires_at') ? date('Y-m-d H:i:s', strtotime($this->post('expires_at'))) : null,
            'is_active'          => 1,
        ];
        if ($id) {
            $model->update($id, $data);
        } else {
            $data['times_redeemed'] = 0;
            $model->insert($data);
        }

        return $this->done('Discount ' . $code . ' saved.', [], site_url('studio/discounts'));
    }

    public function toggle(int $id)
    {
        $this->requireAdmin();
        $model = new DiscountModel();
        $d     = $model->find($id) ?? $this->notFound();
        $model->update($id, ['is_active' => $d['is_active'] ? 0 : 1]);

        return $this->done($d['is_active'] ? 'Discount paused.' : 'Discount activated.');
    }

    public function delete(int $id)
    {
        $this->requireAdmin();
        (new DiscountModel())->delete($id);

        return $this->done('Discount deleted.', [], site_url('studio/discounts'));
    }
}
