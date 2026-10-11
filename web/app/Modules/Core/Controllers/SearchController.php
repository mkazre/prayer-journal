<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Bible\Models\BibleVerseModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Shop\Models\ProductModel;

class SearchController extends BaseController
{
    public function index(): string
    {
        $term = (string) $this->request->getGet('q');
        $courses  = [];
        $products = [];
        $verses   = [];

        if ($term !== '') {
            $courses  = (new CourseModel())->published()->like('title', $term)->findAll(5);
            $products = (new ProductModel())->published()->like('title', $term)->findAll(5);
            $verses   = (new BibleVerseModel())->search($term, 5);
        }

        return view('Core/search', ['title' => 'Search', 'term' => $term, 'courses' => $courses, 'products' => $products, 'verses' => $verses]);
    }
}
