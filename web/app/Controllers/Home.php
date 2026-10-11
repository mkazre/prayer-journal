<?php

namespace App\Controllers;

use App\Modules\Lms\Models\CourseModel;
use App\Modules\Shop\Models\ProductModel;

class Home extends BaseController
{
    public function index(): string
    {
        $db       = db_connect();
        $courses  = new CourseModel();
        $featured = $courses->catalog(['limit' => 6]);
        $spotlight = $featured[0] ?? null;

        $devotion = null;
        if ($spotlight) {
            $devotion = $db->table('lessons')->where('course_id', $spotlight['id'])->where('type', 'devotion')->where('status', 'published')
                ->orderBy('position')->limit(1, ((int) date('z')) % max(1, (int) $db->table('lessons')->where('course_id', $spotlight['id'])->where('type', 'devotion')->countAllResults()))
                ->get()->getRowArray();
        }

        return view('home', [
            'title'     => site_name(),
            'courses'   => $featured,
            'spotlight' => $spotlight,
            'devotion'  => $devotion,
            'verse'     => verse_of_the_day(),
            'products'  => (new ProductModel())->storefront(['limit' => 4, 'sort' => 'featured']),
            'prayers'   => (new \App\Modules\Core\Models\PrayerRequestModel())->feed(3),
            'stats'     => [
                'devotions' => $db->table('lessons')->where('type', 'devotion')->where('status', 'published')->countAllResults(),
                'verses'    => $db->table('bible_verses')->countAllResults(),
                'prayers'   => (int) ($db->table('prayer_requests')->selectSum('prayer_count')->get()->getRow()->prayer_count ?? 0),
            ],
        ]);
    }
}
