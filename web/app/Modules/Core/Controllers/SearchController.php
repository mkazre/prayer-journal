<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Scripture;
use App\Modules\Bible\Models\BibleVerseModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\JournalEntryModel;
use App\Modules\Shop\Models\ProductModel;

/**
 * One search box for everything: a Bible reference jumps straight to the
 * passage, and words search courses, lessons, products, Scripture and (when
 * signed in) the person's own journal.
 */
class SearchController extends BaseController
{
    public function index()
    {
        $term = trim((string) $this->request->getGet('q'));
        $data = ['title' => 'Search', 'term' => $term, 'reference' => null, 'courses' => [], 'lessons' => [], 'products' => [], 'verses' => [], 'verseCount' => 0, 'journal' => []];

        if (mb_strlen($term) >= 2) {
            if (($ref = Scripture::parse($term)) && $this->request->getGet('go') !== null) {
                return redirect()->to(Scripture::url($ref));
            }
            $data['reference'] = $ref;
            $data['courses']   = (new CourseModel())->catalog(['q' => $term, 'limit' => 6]);
            $data['lessons']   = db_connect()->table('lessons l')
                ->select('l.id, l.title, l.summary, l.type, c.slug AS course_slug, c.title AS course_title')
                ->join('courses c', 'c.id = l.course_id')
                ->where('c.status', 'published')->where('c.deleted_at IS NULL')
                ->groupStart()->like('l.title', $term)->orLike('l.summary', $term)->groupEnd()
                ->orderBy('c.id')->orderBy('l.position')->limit(8)->get()->getResultArray();
            $data['products'] = (new ProductModel())->storefront(['q' => $term, 'limit' => 4]);
            if (! $ref) {
                $verses             = new BibleVerseModel();
                $data['verses']     = $verses->searchPage($term, [], 6, 0);
                $data['verseCount'] = $verses->searchCount($term, []);
            }
            if (auth()->loggedIn()) {
                $data['journal'] = (new JournalEntryModel())->search($this->userId(), ['q' => $term])->limit(5)->get()->getResultArray();
            }
        }

        return view('Core/search', $data);
    }
}
