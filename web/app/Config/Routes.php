<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

service('auth')->routes($routes);

$routes->get('dashboard', '\App\Modules\Core\Controllers\DashboardController::index', ['filter' => 'session']);

// ---------------------------------------------------------------------
// LMS: public catalog + enrollment
// ---------------------------------------------------------------------
$routes->get('courses', '\App\Modules\Lms\Controllers\CourseCatalogController::index');
$routes->get('courses/(:segment)', '\App\Modules\Lms\Controllers\CourseCatalogController::show/$1');
$routes->get('courses/(:segment)/enroll', '\App\Modules\Lms\Controllers\CourseCatalogController::enroll/$1', ['filter' => 'session']);
$routes->post('courses/(:segment)/review', '\App\Modules\Lms\Controllers\CourseCatalogController::review/$1', ['filter' => 'session']);
$routes->get('certificates/(:segment)', '\App\Modules\Lms\Controllers\CertificateController::show/$1');
$routes->get('my-certificates', '\App\Modules\Lms\Controllers\CertificateController::mine', ['filter' => 'session']);

// LMS: student study area
$routes->group('study', ['filter' => 'session'], static function ($routes) {
    $routes->get('(:segment)', '\App\Modules\Lms\Controllers\StudyController::show/$1');
    $routes->get('(:segment)/lesson/(:num)', '\App\Modules\Lms\Controllers\StudyController::lesson/$1/$2');
    $routes->post('(:segment)/lesson/(:num)/complete', '\App\Modules\Lms\Controllers\StudyController::completeLesson/$1/$2');
    $routes->match(['GET', 'POST'], '(:segment)/quiz/(:num)', '\App\Modules\Lms\Controllers\StudyController::quiz/$1/$2');
    $routes->match(['GET', 'POST'], '(:segment)/assignment/(:num)', '\App\Modules\Lms\Controllers\StudyController::assignment/$1/$2');
});

// LMS: instructor authoring (requires instructor/admin/superadmin group)
$routes->group('instructor', ['filter' => ['session', 'group:instructor,admin,superadmin']], static function ($routes) {
    $routes->get('courses', '\App\Modules\Lms\Controllers\InstructorCourseController::index');
    $routes->match(['GET', 'POST'], 'courses/create', '\App\Modules\Lms\Controllers\InstructorCourseController::create');
    $routes->match(['GET', 'POST'], 'courses/(:num)/edit', '\App\Modules\Lms\Controllers\InstructorCourseController::edit/$1');
    $routes->match(['GET', 'POST'], 'courses/(:num)/lessons/create', '\App\Modules\Lms\Controllers\InstructorCourseController::addLesson/$1');
    $routes->match(['GET', 'POST'], 'courses/(:num)/quizzes/create', '\App\Modules\Lms\Controllers\InstructorCourseController::addQuiz/$1');
    $routes->get('courses/(:num)/gradebook', '\App\Modules\Lms\Controllers\GradingController::gradebook/$1');
    $routes->get('assignments/(:num)/submissions', '\App\Modules\Lms\Controllers\GradingController::submissions/$1');
    $routes->post('submissions/(:num)/grade', '\App\Modules\Lms\Controllers\GradingController::grade/$1');
});

// ---------------------------------------------------------------------
// Bible
// ---------------------------------------------------------------------
$routes->get('bible', '\App\Modules\Bible\Controllers\BibleController::index');
$routes->get('bible/search', '\App\Modules\Bible\Controllers\BibleController::search');
$routes->get('bible/(:segment)/(:num)', '\App\Modules\Bible\Controllers\BibleController::chapter/$1/$2');
$routes->group('bible', ['filter' => 'session'], static function ($routes) {
    $routes->post('verses/(:num)/bookmark', '\App\Modules\Bible\Controllers\BibleController::toggleBookmark/$1');
    $routes->post('verses/(:num)/highlight', '\App\Modules\Bible\Controllers\BibleController::setHighlight/$1');
    $routes->post('verses/(:num)/note', '\App\Modules\Bible\Controllers\BibleController::addNote/$1');
    $routes->get('my-bookmarks', '\App\Modules\Bible\Controllers\BibleController::myBookmarks');
});

// ---------------------------------------------------------------------
// Shop: storefront + cart + checkout
// ---------------------------------------------------------------------
$routes->get('shop', '\App\Modules\Shop\Controllers\StorefrontController::index');
$routes->get('shop/(:segment)', '\App\Modules\Shop\Controllers\StorefrontController::show/$1');
$routes->get('cart', '\App\Modules\Shop\Controllers\CartController::show');
$routes->post('cart/add/(:num)', '\App\Modules\Shop\Controllers\CartController::add/$1');
$routes->post('cart/items/(:num)', '\App\Modules\Shop\Controllers\CartController::update/$1');
$routes->match(['GET', 'POST'], 'checkout/cart', '\App\Modules\Shop\Controllers\CheckoutController::cart');
$routes->match(['GET', 'POST'], 'checkout/course/(:num)', '\App\Modules\Shop\Controllers\CheckoutController::course/$1');
$routes->match(['GET', 'POST'], 'checkout/lesson/(:num)', '\App\Modules\Shop\Controllers\CheckoutController::lesson/$1');
$routes->match(['GET', 'POST'], 'checkout/plan/(:num)', '\App\Modules\Shop\Controllers\CheckoutController::plan/$1');
$routes->post('webhooks/stripe', '\App\Modules\Shop\Controllers\CheckoutController::webhook');
$routes->post('webhooks/payfast', '\App\Modules\Shop\Controllers\CheckoutController::payfastNotify');
$routes->get('orders/(:num)/confirmation', '\App\Modules\Shop\Controllers\OrderController::confirmation/$1', ['filter' => 'session']);
$routes->get('my-orders', '\App\Modules\Shop\Controllers\OrderController::myOrders', ['filter' => 'session']);
$routes->get('pricing', '\App\Modules\Shop\Controllers\PricingController::index');
$routes->get('wishlist', '\App\Modules\Shop\Controllers\WishlistController::index', ['filter' => 'session']);
$routes->post('wishlist/toggle/(:num)', '\App\Modules\Shop\Controllers\WishlistController::toggle/$1', ['filter' => 'session']);

// Core: search + prayer requests
$routes->get('search', '\App\Modules\Core\Controllers\SearchController::index');
$routes->get('prayer-requests', '\App\Modules\Core\Controllers\PrayerRequestController::index');
$routes->post('prayer-requests/create', '\App\Modules\Core\Controllers\PrayerRequestController::create', ['filter' => 'session']);
$routes->post('prayer-requests/(:num)/pray', '\App\Modules\Core\Controllers\PrayerRequestController::pray/$1', ['filter' => 'session']);

// Shop: back-office (requires admin/superadmin, or vendor for their own products)
$routes->group('shop-admin', ['filter' => ['session', 'group:admin,superadmin,vendor']], static function ($routes) {
    $routes->get('products', '\App\Modules\Shop\Controllers\ShopAdminController::products');
    $routes->match(['GET', 'POST'], 'products/create', '\App\Modules\Shop\Controllers\ShopAdminController::createProduct');
    $routes->post('products/(:num)/publish', '\App\Modules\Shop\Controllers\ShopAdminController::publishProduct/$1');
    $routes->get('inventory', '\App\Modules\Shop\Controllers\ShopAdminController::inventory');
});
// ---------------------------------------------------------------------
// REST API (for the Expo mobile app)
// ---------------------------------------------------------------------
$routes->group('api/v1', static function ($routes) {
    $routes->post('auth/register', '\App\Modules\Api\Controllers\AuthApiController::register');
    $routes->post('auth/login', '\App\Modules\Api\Controllers\AuthApiController::login');

    $routes->get('courses', '\App\Modules\Api\Controllers\LmsApiController::courses');
    $routes->get('courses/(:segment)', '\App\Modules\Api\Controllers\LmsApiController::course/$1');
    $routes->get('bible/books', '\App\Modules\Api\Controllers\BibleApiController::books');
    $routes->get('bible/(:segment)/(:num)', '\App\Modules\Api\Controllers\BibleApiController::chapter/$1/$2');
    $routes->get('bible/search', '\App\Modules\Api\Controllers\BibleApiController::search');
    $routes->get('shop/products', '\App\Modules\Api\Controllers\ShopApiController::products');
    $routes->get('shop/products/(:segment)', '\App\Modules\Api\Controllers\ShopApiController::product/$1');
    $routes->get('shop/plans', '\App\Modules\Api\Controllers\ShopApiController::plans');
});

$routes->group('api/v1', ['filter' => 'tokens'], static function ($routes) {
    $routes->get('auth/me', '\App\Modules\Api\Controllers\AuthApiController::me');
    $routes->post('auth/logout', '\App\Modules\Api\Controllers\AuthApiController::logout');

    $routes->get('my-courses', '\App\Modules\Api\Controllers\LmsApiController::myCourses');
    $routes->post('courses/(:segment)/enroll', '\App\Modules\Api\Controllers\LmsApiController::enroll/$1');
    $routes->get('study/(:segment)/lesson/(:num)', '\App\Modules\Api\Controllers\LmsApiController::lesson/$1/$2');
    $routes->post('study/(:segment)/lesson/(:num)/complete', '\App\Modules\Api\Controllers\LmsApiController::completeLesson/$1/$2');
    $routes->get('study/(:segment)/quiz/(:num)', '\App\Modules\Api\Controllers\LmsApiController::quiz/$1/$2');
    $routes->post('study/(:segment)/quiz/(:num)/submit', '\App\Modules\Api\Controllers\LmsApiController::submitQuiz/$1/$2');
    $routes->get('study/(:segment)/assignments', '\App\Modules\Api\Controllers\LmsApiController::assignments/$1');
    $routes->post('study/(:segment)/assignments/(:num)/submit', '\App\Modules\Api\Controllers\LmsApiController::submitAssignment/$1/$2');

    $routes->post('bible/verses/(:num)/bookmark', '\App\Modules\Api\Controllers\BibleApiController::toggleBookmark/$1');
    $routes->get('bible/my-bookmarks', '\App\Modules\Api\Controllers\BibleApiController::myBookmarks');
    $routes->post('bible/verses/(:num)/highlight', '\App\Modules\Api\Controllers\BibleApiController::setHighlight/$1');
    $routes->post('bible/verses/(:num)/note', '\App\Modules\Api\Controllers\BibleApiController::addNote/$1');
    $routes->get('bible/streak', '\App\Modules\Api\Controllers\BibleApiController::streak');

    $routes->get('cart', '\App\Modules\Api\Controllers\ShopApiController::cart');
    $routes->post('cart/add/(:num)', '\App\Modules\Api\Controllers\ShopApiController::addToCart/$1');
    $routes->post('shop/products/(:num)/wishlist', '\App\Modules\Api\Controllers\ShopApiController::toggleWishlist/$1');
});

$routes->group('shop-admin', ['filter' => ['session', 'group:admin,superadmin']], static function ($routes) {
    $routes->get('discounts', '\App\Modules\Shop\Controllers\ShopAdminController::discounts');
    $routes->post('discounts/create', '\App\Modules\Shop\Controllers\ShopAdminController::createDiscount');
    $routes->get('orders', '\App\Modules\Shop\Controllers\ShopAdminController::orders');
    $routes->post('orders/(:num)/fulfill', '\App\Modules\Shop\Controllers\ShopAdminController::fulfillOrder/$1');
    $routes->get('reports', '\App\Modules\Shop\Controllers\ShopAdminController::reports');
    $routes->get('plans', '\App\Modules\Shop\Controllers\ShopAdminController::plans');
    $routes->post('plans/create', '\App\Modules\Shop\Controllers\ShopAdminController::createPlan');
    $routes->match(['GET', 'POST'], 'announcement', '\App\Modules\Shop\Controllers\ShopAdminController::announcement');
});
