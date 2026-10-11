<?php

namespace App\Modules\Studio\Controllers;

class ReportController extends StudioController
{
    public function index(): string
    {
        $this->requireAdmin();
        $db    = db_connect();
        $days  = in_array((int) $this->request->getGet('days'), [7, 30, 90, 365], true) ? (int) $this->request->getGet('days') : 30;
        $since = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $paid  = ['paid', 'processing', 'shipped', 'completed'];

        $rows   = array_column($db->table('orders')->select('DATE(created_at) AS d, SUM(total_cents) AS n')->whereIn('status', $paid)->where('created_at >=', $since)->groupBy('d')->get()->getResultArray(), 'n', 'd');
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d          = date('Y-m-d', strtotime("-{$i} days"));
            $series[$d] = (int) ($rows[$d] ?? 0);
        }
        $orders  = $db->table('orders')->whereIn('status', $paid)->where('created_at >=', $since);
        $summary = $orders->select('COUNT(*) AS orders, COALESCE(SUM(total_cents),0) AS gross, COALESCE(SUM(discount_cents),0) AS discounts, COALESCE(SUM(tax_cents),0) AS tax, COALESCE(SUM(shipping_cents),0) AS shipping')->get()->getRowArray();
        $courseSales = (int) ($db->table('payments')->selectSum('amount_cents')->where('status', 'paid')->where('course_id IS NOT NULL')->where('created_at >=', $since)->get()->getRow('amount_cents') ?? 0);
        $planSales   = (int) ($db->table('payments')->selectSum('amount_cents')->where('status', 'paid')->where('plan_id IS NOT NULL')->where('created_at >=', $since)->get()->getRow('amount_cents') ?? 0);

        return $this->render('shop/reports', [
            'title'   => 'Reports', 'active' => 'reports', 'days' => $days, 'series' => $series, 'summary' => $summary,
            'courseSales' => $courseSales, 'planSales' => $planSales,
            'top'     => $db->table('order_items oi')->select('oi.title_snapshot, SUM(oi.quantity) AS units, SUM(oi.quantity * oi.unit_price_cents) AS revenue')->join('orders o', 'o.id = oi.order_id')
                ->whereIn('o.status', $paid)->where('o.created_at >=', $since)->groupBy('oi.title_snapshot')->orderBy('revenue', 'DESC')->limit(10)->get()->getResultArray(),
            'codes'   => $db->table('orders o')->select('d.code, COUNT(*) AS uses, SUM(o.discount_cents) AS total')->join('discounts d', 'd.id = o.discount_id')
                ->whereIn('o.status', $paid)->where('o.created_at >=', $since)->groupBy('d.code')->orderBy('uses', 'DESC')->get()->getResultArray(),
            'newUsers' => $db->table('users')->where('created_at >=', $since)->countAllResults(),
            'enrolls'  => $db->table('enrollments')->where('enrolled_at >=', $since)->countAllResults(),
        ]);
    }
}
