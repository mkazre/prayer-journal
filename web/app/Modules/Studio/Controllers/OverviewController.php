<?php

namespace App\Modules\Studio\Controllers;

use App\Modules\Lms\Models\CourseModel;

class OverviewController extends StudioController
{
    public function index(): string
    {
        $db    = db_connect();
        $since = date('Y-m-d H:i:s', strtotime('-30 days'));
        $data  = ['title' => 'Studio', 'active' => 'overview', 'isAdmin' => $this->isManager(), 'isInstructor' => $this->isInstructor(), 'isVendor' => $this->isVendor()];

        if ($this->isInstructor()) {
            $courses   = (new CourseModel())->manageableBy($this->userId(), $this->isManager());
            $courseIds = array_map('intval', array_column($courses, 'id')) ?: [0];
            $data['courses']        = array_slice($courses, 0, 5);
            $data['courseCount']    = count($courses);
            $data['learners']       = $db->table('enrollments')->whereIn('course_id', $courseIds)->countAllResults();
            $data['newLearners']    = $db->table('enrollments')->whereIn('course_id', $courseIds)->where('enrolled_at >=', $since)->countAllResults();
            $data['completions']    = $db->table('lesson_completions lc')->join('enrollments e', 'e.id = lc.enrollment_id')->whereIn('e.course_id', $courseIds)->where('lc.completed_at >=', $since)->countAllResults();
            $data['toGrade']        = $db->table('assignment_submissions s')->join('assignments a', 'a.id = s.assignment_id')->whereIn('a.course_id', $courseIds)->where('s.status', 'submitted')->countAllResults();
            $data['enrollChart']    = $this->daily($db->table('enrollments')->select('DATE(enrolled_at) AS d, COUNT(*) AS n')->whereIn('course_id', $courseIds)->where('enrolled_at >=', $since)->groupBy('d')->get()->getResultArray());
            $data['recentLearners'] = $db->table('enrollments e')->select('e.*, u.username, c.title AS course_title')->join('users u', 'u.id = e.user_id')->join('courses c', 'c.id = e.course_id')
                ->whereIn('e.course_id', $courseIds)->orderBy('e.enrolled_at', 'DESC')->limit(6)->get()->getResultArray();
        }
        if ($this->isManager()) {
            $paid = ['paid', 'processing', 'shipped', 'completed'];
            $data['revenue']     = (int) ($db->table('orders')->selectSum('total_cents')->whereIn('status', $paid)->where('created_at >=', $since)->get()->getRow('total_cents') ?? 0);
            $data['orderCount']  = $db->table('orders')->whereIn('status', $paid)->where('created_at >=', $since)->countAllResults();
            $data['toFulfil']    = $db->table('orders')->where('status', 'paid')->countAllResults();
            $data['users']       = $db->table('users')->countAllResults();
            $data['salesChart']  = $this->daily($db->table('orders')->select('DATE(created_at) AS d, SUM(total_cents) AS n')->whereIn('status', $paid)->where('created_at >=', $since)->groupBy('d')->get()->getResultArray());
            $data['recentOrders'] = $db->table('orders')->orderBy('created_at', 'DESC')->limit(6)->get()->getResultArray();
            $data['lowStock']    = $db->table('product_variants v')->select('v.id, v.sku, p.title, p.id AS product_id, COALESCE(SUM(il.quantity),0) AS qty', false)
                ->join('products p', 'p.id = v.product_id')->join('inventory_levels il', 'il.variant_id = v.id', 'left')
                ->where('p.track_inventory', 1)->where('p.product_type !=', 'digital')->where('p.deleted_at IS NULL')->groupBy('v.id, v.sku, p.title, p.id')
                ->having('qty <=', 5)->orderBy('qty')->limit(5)->get()->getResultArray();
            $data['prayerCount'] = $db->table('prayer_requests')->where('created_at >=', $since)->countAllResults();
        }

        return $this->render('overview', $data);
    }

    /**
     * Fills a 30-day series from grouped rows (d => n).
     */
    private function daily(array $rows): array
    {
        $map = array_column($rows, 'n', 'd');
        $out = [];
        for ($i = 29; $i >= 0; $i--) {
            $d       = date('Y-m-d', strtotime("-{$i} days"));
            $out[$d] = (int) ($map[$d] ?? 0);
        }

        return $out;
    }
}
