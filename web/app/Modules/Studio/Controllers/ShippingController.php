<?php

namespace App\Modules\Studio\Controllers;

class ShippingController extends StudioController
{
    public function index(): string
    {
        $this->requireManager();
        $db    = db_connect();
        $zones = $db->table('shipping_zones')->orderBy('id')->get()->getResultArray();
        foreach ($zones as &$z) {
            $z['rates'] = $db->table('shipping_rates')->where('zone_id', $z['id'])->orderBy('price_cents')->get()->getResultArray();
        }

        return $this->render('shop/shipping', ['title' => 'Shipping & tax', 'active' => 'shipping', 'zones' => $zones, 'taxes' => $db->table('tax_rules')->orderBy('country')->get()->getResultArray()]);
    }

    public function saveZone()
    {
        $this->requireManager();
        $name      = trim((string) $this->post('name'));
        $countries = strtoupper(preg_replace('/[^A-Za-z,*]/', '', (string) $this->post('countries')));
        if ($name === '' || $countries === '') {
            return $this->failed('A zone needs a name and at least one country code (or * for everywhere else).');
        }
        $db = db_connect();
        $id = (int) $this->post('id');
        $id ? $db->table('shipping_zones')->where('id', $id)->update(['name' => $name, 'countries' => $countries]) : $db->table('shipping_zones')->insert(['name' => $name, 'countries' => $countries]);

        return $this->done('Zone saved.', [], site_url('studio/shipping'));
    }

    public function deleteZone(int $id)
    {
        $this->requireManager();
        $db = db_connect();
        $db->table('shipping_rates')->where('zone_id', $id)->delete();
        $db->table('shipping_zones')->where('id', $id)->delete();

        return $this->done('Zone deleted.', [], site_url('studio/shipping'));
    }

    public function saveRate()
    {
        $this->requireManager();
        $name = trim((string) $this->post('name'));
        if ($name === '') {
            return $this->failed('Name the rate (e.g. "Standard courier").');
        }
        $db   = db_connect();
        $data = ['zone_id' => (int) $this->post('zone_id'), 'name' => $name, 'price_cents' => max(0, $this->money('price')), 'min_order_cents' => $this->post('min_order') !== '' ? $this->money('min_order') : null];
        $id   = (int) $this->post('id');
        $id ? $db->table('shipping_rates')->where('id', $id)->update($data) : $db->table('shipping_rates')->insert($data);

        return $this->done('Rate saved.', [], site_url('studio/shipping'));
    }

    public function deleteRate(int $id)
    {
        $this->requireManager();
        db_connect()->table('shipping_rates')->where('id', $id)->delete();

        return $this->done('Rate deleted.', [], site_url('studio/shipping'));
    }

    public function saveTax()
    {
        $this->requireManager();
        $country = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $this->post('country')), 0, 2));
        if (strlen($country) !== 2) {
            return $this->failed('Use a two-letter country code, e.g. ZA.');
        }
        db_connect()->table('tax_rules')->insert(['country' => $country, 'region' => trim((string) $this->post('region')) ?: null, 'rate_percent' => max(0, min(100, (int) $this->post('rate')))]);

        return $this->done('Tax rule added.', [], site_url('studio/shipping#tax'));
    }

    public function deleteTax(int $id)
    {
        $this->requireManager();
        db_connect()->table('tax_rules')->where('id', $id)->delete();

        return $this->done('Tax rule deleted.', [], site_url('studio/shipping#tax'));
    }
}
