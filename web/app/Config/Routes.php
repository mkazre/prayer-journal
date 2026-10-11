<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

service('auth')->routes($routes);

$core  = '\App\Modules\Core\Controllers\\';
$lms   = '\App\Modules\Lms\Controllers\\';
$bible = '\App\Modules\Bible\Controllers\\';
$shop  = '\App\Modules\Shop\Controllers\\';
$std   = '\App\Modules\Studio\Controllers\\';
$api   = '\App\Modules\Api\Controllers\\';

// ---------------------------------------------------------------------
// Account & dashboard
// ---------------------------------------------------------------------
$routes->group('', ['filter' => 'session'], static function ($routes) use ($core, $lms) {
    $routes->get('dashboard', $core . 'DashboardController::index');
    $routes->get('my-learning', $lms . 'StudyController::myLearning');
    $routes->get('account', $core . 'AccountController::index');
    $routes->post('account/profile', $core . 'AccountController::profile');
    $routes->post('account/password', $core . 'AccountController::password');
    $routes->post('account/preferences', $core . 'AccountController::preferences');
    $routes->get('notifications', $core . 'AccountController::notifications');
});

// ---------------------------------------------------------------------
// LMS: catalog, course pages, previews, certificates
// ---------------------------------------------------------------------
$routes->get('courses', $lms . 'CourseCatalogController::index');
$routes->get('courses/(:segment)', $lms . 'CourseCatalogController::show/$1');
$routes->get('courses/(:segment)/preview/(:num)', $lms . 'CourseCatalogController::preview/$1/$2');
$routes->post('courses/(:segment)/enroll', $lms . 'CourseCatalogController::enroll/$1', ['filter' => 'session']);
$routes->get('courses/(:segment)/enroll', $lms . 'CourseCatalogController::enroll/$1', ['filter' => 'session']);
$routes->post('courses/(:segment)/review', $lms . 'CourseCatalogController::review/$1', ['filter' => 'session']);
$routes->get('certificates/(:segment)', $lms . 'CertificateController::show/$1');
$routes->get('my-certificates', $lms . 'CertificateController::mine', ['filter' => 'session']);

// LMS: learner player
$routes->group('study', ['filter' => 'session'], static function ($routes) use ($lms) {
    $routes->get('(:segment)', $lms . 'StudyController::show/$1');
    $routes->get('(:segment)/notes', $lms . 'StudyController::notes/$1');
    $routes->get('(:segment)/lesson/(:num)', $lms . 'StudyController::lesson/$1/$2');
    $routes->post('(:segment)/lesson/(:num)/complete', $lms . 'StudyController::completeLesson/$1/$2');
    $routes->post('(:segment)/lesson/(:num)/note', $lms . 'StudyController::saveNote/$1/$2');
    $routes->post('(:segment)/lesson/(:num)/journal', $lms . 'StudyController::saveJournal/$1/$2');
    $routes->post('(:segment)/lesson/(:num)/discuss', $lms . 'StudyController::discuss/$1/$2');
    $routes->match(['GET', 'POST'], '(:segment)/quiz/(:num)', $lms . 'StudyController::quiz/$1/$2');
    $routes->match(['GET', 'POST'], '(:segment)/assignment/(:num)', $lms . 'StudyController::assignment/$1/$2');
});

// Personal prayer journal
$routes->group('journal', ['filter' => 'session'], static function ($routes) use ($lms) {
    $routes->get('/', $lms . 'JournalController::index');
    $routes->get('print', $lms . 'JournalController::printable');
    $routes->post('create', $lms . 'JournalController::create');
    $routes->post('(:num)', $lms . 'JournalController::update/$1');
    $routes->post('(:num)/answered', $lms . 'JournalController::toggleAnswered/$1');
    $routes->post('(:num)/delete', $lms . 'JournalController::delete/$1');
});

// ---------------------------------------------------------------------
// Bible
// ---------------------------------------------------------------------
$routes->get('bible', $bible . 'BibleController::index');
$routes->get('bible/search', $bible . 'BibleController::search');
$routes->get('bible/passage', $bible . 'BibleController::passage');
$routes->get('bible/plans', $bible . 'ReadingPlanController::index');
$routes->get('bible/plans/(:segment)', $bible . 'ReadingPlanController::show/$1');
$routes->group('bible', ['filter' => 'session'], static function ($routes) use ($bible) {
    $routes->get('library', $bible . 'BibleController::library');
    $routes->get('my-bookmarks', $bible . 'BibleController::library');
    $routes->post('plans/(:segment)/start', $bible . 'ReadingPlanController::start/$1');
    $routes->post('plans/(:segment)/leave', $bible . 'ReadingPlanController::leave/$1');
    $routes->post('plans/(:segment)/day/(:num)', $bible . 'ReadingPlanController::toggleDay/$1/$2');
    $routes->post('verses/(:num)/bookmark', $bible . 'BibleController::toggleBookmark/$1');
    $routes->post('verses/(:num)/highlight', $bible . 'BibleController::setHighlight/$1');
    $routes->post('verses/(:num)/note', $bible . 'BibleController::addNote/$1');
    $routes->post('notes/(:num)/delete', $bible . 'BibleController::deleteNote/$1');
    $routes->post('settings', $bible . 'BibleController::settings');
});
$routes->get('bible/(:segment)', $bible . 'BibleController::book/$1');
$routes->get('bible/(:segment)/(:num)', $bible . 'BibleController::chapter/$1/$2');

// ---------------------------------------------------------------------
// Shop: storefront, cart, checkout, orders
// ---------------------------------------------------------------------
$routes->get('shop', $shop . 'StorefrontController::index');
$routes->get('shop/collections/(:segment)', $shop . 'StorefrontController::collection/$1');
$routes->get('shop/(:segment)', $shop . 'StorefrontController::show/$1');
$routes->post('shop/(:segment)/review', $shop . 'StorefrontController::review/$1', ['filter' => 'session']);
$routes->get('cart', $shop . 'CartController::show');
$routes->post('cart/add/(:num)', $shop . 'CartController::add/$1');
$routes->post('cart/items/(:num)', $shop . 'CartController::update/$1');
$routes->post('cart/items/(:num)/remove', $shop . 'CartController::remove/$1');
$routes->post('cart/discount', $shop . 'CartController::discount');
$routes->match(['GET', 'POST'], 'checkout', $shop . 'CheckoutController::cart');
$routes->match(['GET', 'POST'], 'checkout/cart', $shop . 'CheckoutController::cart');
$routes->match(['GET', 'POST'], 'checkout/course/(:num)', $shop . 'CheckoutController::course/$1');
$routes->match(['GET', 'POST'], 'checkout/lesson/(:num)', $shop . 'CheckoutController::lesson/$1');
$routes->match(['GET', 'POST'], 'checkout/plan/(:num)', $shop . 'CheckoutController::plan/$1');
$routes->match(['GET', 'POST'], 'checkout/module/(:num)', $shop . 'CheckoutController::module/$1');
$routes->post('account/membership/cancel', $shop . 'CheckoutController::cancelMembership', ['filter' => 'session']);
$routes->post('webhooks/stripe', $shop . 'CheckoutController::webhook');
$routes->post('webhooks/payfast', $shop . 'CheckoutController::payfastNotify');
$routes->get('orders/(:num)', $shop . 'OrderController::show/$1');
$routes->get('orders/(:num)/confirmation', $shop . 'OrderController::show/$1');
$routes->get('orders/(:num)/download/(:num)', $shop . 'OrderController::download/$1/$2', ['filter' => 'session']);
$routes->get('my-orders', $shop . 'OrderController::myOrders', ['filter' => 'session']);
$routes->get('pricing', $shop . 'PricingController::index');
$routes->get('wishlist', $shop . 'WishlistController::index', ['filter' => 'session']);
$routes->post('wishlist/toggle/(:num)', $shop . 'WishlistController::toggle/$1', ['filter' => 'session']);

// ---------------------------------------------------------------------
// Community & search
// ---------------------------------------------------------------------
$routes->get('search', $core . 'SearchController::index');
$routes->get('prayer-requests', $core . 'PrayerRequestController::index');
$routes->post('prayer-requests/create', $core . 'PrayerRequestController::create', ['filter' => 'session']);
$routes->post('prayer-requests/(:num)/pray', $core . 'PrayerRequestController::pray/$1', ['filter' => 'session']);
$routes->post('prayer-requests/(:num)/answered', $core . 'PrayerRequestController::answered/$1', ['filter' => 'session']);
$routes->post('prayer-requests/(:num)/delete', $core . 'PrayerRequestController::delete/$1', ['filter' => 'session']);

// ---------------------------------------------------------------------
// Studio: one back office for instructors, vendors and admins.
// Each controller narrows access further (e.g. orders are admin-only).
// ---------------------------------------------------------------------
$routes->group('studio', ['filter' => ['session', 'group:superadmin,admin,manager,instructor,vendor']], static function ($routes) use ($std) {
    $routes->get('/', $std . 'OverviewController::index');
    $routes->post('uploads', $std . 'UploadController::image');

    // Courses
    $routes->get('courses', $std . 'CourseController::index');
    $routes->match(['GET', 'POST'], 'courses/new', $std . 'CourseController::create');
    $routes->match(['GET', 'POST'], 'courses/(:num)', $std . 'CourseController::edit/$1');
    $routes->get('courses/(:num)/curriculum', $std . 'CurriculumController::index/$1');
    $routes->post('courses/(:num)/reorder', $std . 'CurriculumController::reorder/$1');
    $routes->post('courses/(:num)/sections', $std . 'CurriculumController::createSection/$1');
    $routes->post('sections/(:num)', $std . 'CurriculumController::updateSection/$1');
    $routes->post('sections/(:num)/delete', $std . 'CurriculumController::deleteSection/$1');
    $routes->match(['GET', 'POST'], 'courses/(:num)/settings', $std . 'CourseController::settings/$1');
    $routes->get('courses/(:num)/students', $std . 'CourseController::students/$1');
    $routes->post('courses/(:num)/students/enroll', $std . 'CourseController::enrollStudent/$1');
    $routes->post('enrollments/(:num)/revoke', $std . 'CourseController::revoke/$1');
    $routes->post('enrollments/(:num)/reset', $std . 'CourseController::resetProgress/$1');
    $routes->get('courses/(:num)/analytics', $std . 'CourseController::analytics/$1');
    $routes->match(['GET', 'POST'], 'courses/(:num)/announcements', $std . 'CourseController::announcements/$1');
    $routes->get('courses/(:num)/reviews', $std . 'CourseController::reviews/$1');
    $routes->post('reviews/(:num)/delete', $std . 'CourseController::deleteReview/$1');
    $routes->post('courses/(:num)/publish', $std . 'CourseController::togglePublish/$1');
    $routes->post('courses/(:num)/duplicate', $std . 'CourseController::duplicate/$1');
    $routes->post('courses/(:num)/delete', $std . 'CourseController::delete/$1');

    $routes->match(['GET', 'POST'], 'courses/(:num)/lessons/new', $std . 'LessonController::create/$1');
    $routes->match(['GET', 'POST'], 'lessons/(:num)', $std . 'LessonController::edit/$1');
    $routes->post('lessons/(:num)/delete', $std . 'LessonController::delete/$1');
    $routes->post('lessons/(:num)/duplicate', $std . 'LessonController::duplicate/$1');
    $routes->match(['GET', 'POST'], 'courses/(:num)/quizzes/new', $std . 'QuizController::create/$1');
    $routes->match(['GET', 'POST'], 'quizzes/(:num)', $std . 'QuizController::edit/$1');
    $routes->post('quizzes/(:num)/delete', $std . 'QuizController::delete/$1');
    $routes->match(['GET', 'POST'], 'courses/(:num)/assignments/new', $std . 'AssignmentController::create/$1');
    $routes->match(['GET', 'POST'], 'assignments/(:num)', $std . 'AssignmentController::edit/$1');
    $routes->post('assignments/(:num)/delete', $std . 'AssignmentController::delete/$1');

    $routes->get('grading', $std . 'GradingController::index');
    $routes->post('submissions/(:num)/grade', $std . 'GradingController::grade/$1');
    $routes->get('courses/(:num)/gradebook', $std . 'GradingController::gradebook/$1');
    $routes->get('students', $std . 'CourseController::allStudents');

    // Shop
    $routes->get('products', $std . 'ProductController::index');
    $routes->post('products/bulk', $std . 'ProductController::bulk');
    $routes->match(['GET', 'POST'], 'products/new', $std . 'ProductController::create');
    $routes->match(['GET', 'POST'], 'products/(:num)', $std . 'ProductController::edit/$1');
    $routes->post('products/(:num)/delete', $std . 'ProductController::delete/$1');
    $routes->post('products/(:num)/duplicate', $std . 'ProductController::duplicate/$1');
    $routes->post('product-images/(:num)/delete', $std . 'ProductController::deleteImage/$1');
    $routes->get('inventory', $std . 'ProductController::inventory');
    $routes->post('inventory/adjust', $std . 'ProductController::adjustInventory');
    $routes->get('orders', $std . 'OrderController::index');
    $routes->get('orders/(:num)', $std . 'OrderController::show/$1');
    $routes->post('orders/(:num)/status', $std . 'OrderController::status/$1');
    $routes->post('orders/(:num)/fulfill', $std . 'OrderController::fulfill/$1');
    $routes->post('orders/(:num)/note', $std . 'OrderController::note/$1');
    $routes->get('collections', $std . 'CatalogController::index');
    $routes->post('collections', $std . 'CatalogController::saveCollection');
    $routes->post('collections/(:num)/delete', $std . 'CatalogController::deleteCollection/$1');
    $routes->post('categories', $std . 'CatalogController::saveCategory');
    $routes->post('categories/(:num)/delete', $std . 'CatalogController::deleteCategory/$1');
    $routes->get('discounts', $std . 'DiscountController::index');
    $routes->post('discounts', $std . 'DiscountController::save');
    $routes->post('discounts/(:num)/toggle', $std . 'DiscountController::toggle/$1');
    $routes->post('discounts/(:num)/delete', $std . 'DiscountController::delete/$1');
    $routes->get('shipping', $std . 'ShippingController::index');
    $routes->post('shipping/zones', $std . 'ShippingController::saveZone');
    $routes->post('shipping/zones/(:num)/delete', $std . 'ShippingController::deleteZone/$1');
    $routes->post('shipping/rates', $std . 'ShippingController::saveRate');
    $routes->post('shipping/rates/(:num)/delete', $std . 'ShippingController::deleteRate/$1');
    $routes->post('shipping/taxes', $std . 'ShippingController::saveTax');
    $routes->post('shipping/taxes/(:num)/delete', $std . 'ShippingController::deleteTax/$1');
    $routes->get('plans', $std . 'PlanController::index');
    $routes->post('plans', $std . 'PlanController::save');
    $routes->post('plans/(:num)/toggle', $std . 'PlanController::toggle/$1');
    $routes->post('memberships/(:num)/cancel', $std . 'PlanController::cancelMember/$1');
    $routes->get('reports', $std . 'ReportController::index');

    // Community & site
    $routes->get('prayer', $std . 'SiteController::prayer');
    $routes->post('prayer/(:num)/delete', $std . 'SiteController::deletePrayer/$1');
    $routes->get('users', $std . 'SiteController::users');
    $routes->post('users', $std . 'SiteController::createUser');
    $routes->post('users/(:num)', $std . 'SiteController::updateUser/$1');
    $routes->post('users/(:num)/groups', $std . 'SiteController::userGroups/$1');
    $routes->post('users/(:num)/suspend', $std . 'SiteController::suspendUser/$1');
    $routes->post('users/(:num)/delete', $std . 'SiteController::deleteUser/$1');
    $routes->match(['GET', 'POST'], 'settings', $std . 'SiteController::settings');
    $routes->post('settings/test-email', $std . 'SiteController::testEmail');
    $routes->get('settings/preview-reminder', $std . 'SiteController::previewReminder');
    $routes->get('emails', $std . 'SiteController::emails');
    $routes->get('emails/(:num)', $std . 'SiteController::viewEmail/$1');
    $routes->post('emails/(:num)/retry', $std . 'SiteController::retryEmail/$1');
});

// Old back-office URLs now live in Studio.
$routes->addRedirect('instructor/courses', 'studio/courses');
$routes->addRedirect('shop-admin/products', 'studio/products');
$routes->addRedirect('shop-admin/inventory', 'studio/inventory');
$routes->addRedirect('shop-admin/orders', 'studio/orders');
$routes->addRedirect('shop-admin/discounts', 'studio/discounts');
$routes->addRedirect('shop-admin/reports', 'studio/reports');
$routes->addRedirect('shop-admin/plans', 'studio/plans');
$routes->addRedirect('shop-admin/announcement', 'studio/settings');

// ---------------------------------------------------------------------
// REST API (for the Expo mobile app)
// ---------------------------------------------------------------------
// Preflight requests; the cors filter answers them.
$routes->options('api/(:any)', static fn () => null);

$routes->group('api/v1', static function ($routes) use ($api) {
    $routes->post('auth/register', $api . 'AuthApiController::register');
    $routes->post('auth/login', $api . 'AuthApiController::login');
    $routes->get('app', $api . 'AppApiController::config');

    $routes->get('courses', $api . 'LmsApiController::courses');
    $routes->get('courses/(:segment)', $api . 'LmsApiController::course/$1');
    $routes->get('bible/books', $api . 'BibleApiController::books');
    $routes->get('bible/search', $api . 'BibleApiController::search');
    $routes->get('bible/passage', $api . 'BibleApiController::passage');
    $routes->get('bible/verse-of-the-day', $api . 'BibleApiController::verseOfTheDay');
    $routes->get('bible/(:segment)/(:num)', $api . 'BibleApiController::chapter/$1/$2');
    $routes->get('shop/products', $api . 'ShopApiController::products');
    $routes->get('shop/products/(:segment)', $api . 'ShopApiController::product/$1');
    $routes->get('shop/plans', $api . 'ShopApiController::plans');
    $routes->get('prayer-requests', $api . 'AppApiController::prayerRequests');
});

$routes->group('api/v1', ['filter' => 'tokens'], static function ($routes) use ($api) {
    $routes->get('auth/me', $api . 'AuthApiController::me');
    $routes->post('auth/logout', $api . 'AuthApiController::logout');
    $routes->get('dashboard', $api . 'AppApiController::dashboard');

    $routes->get('my-courses', $api . 'LmsApiController::myCourses');
    $routes->post('courses/(:segment)/enroll', $api . 'LmsApiController::enroll/$1');
    $routes->get('study/(:segment)', $api . 'LmsApiController::curriculum/$1');
    $routes->get('study/(:segment)/lesson/(:num)', $api . 'LmsApiController::lesson/$1/$2');
    $routes->post('study/(:segment)/lesson/(:num)/complete', $api . 'LmsApiController::completeLesson/$1/$2');
    $routes->post('study/(:segment)/lesson/(:num)/journal', $api . 'LmsApiController::saveJournal/$1/$2');
    $routes->get('study/(:segment)/quiz/(:num)', $api . 'LmsApiController::quiz/$1/$2');
    $routes->post('study/(:segment)/quiz/(:num)/submit', $api . 'LmsApiController::submitQuiz/$1/$2');
    $routes->get('study/(:segment)/assignments', $api . 'LmsApiController::assignments/$1');
    $routes->post('study/(:segment)/assignments/(:num)/submit', $api . 'LmsApiController::submitAssignment/$1/$2');

    $routes->get('journal', $api . 'JournalApiController::index');
    $routes->post('journal', $api . 'JournalApiController::create');
    $routes->post('journal/(:num)', $api . 'JournalApiController::update/$1');
    $routes->post('journal/(:num)/answered', $api . 'JournalApiController::toggleAnswered/$1');
    $routes->delete('journal/(:num)', $api . 'JournalApiController::delete/$1');

    $routes->post('bible/verses/(:num)/bookmark', $api . 'BibleApiController::toggleBookmark/$1');
    $routes->get('bible/my-bookmarks', $api . 'BibleApiController::myBookmarks');
    $routes->post('bible/verses/(:num)/highlight', $api . 'BibleApiController::setHighlight/$1');
    $routes->post('bible/verses/(:num)/note', $api . 'BibleApiController::addNote/$1');
    $routes->get('bible/streak', $api . 'BibleApiController::streak');

    $routes->post('prayer-requests', $api . 'AppApiController::createPrayerRequest');
    $routes->post('prayer-requests/(:num)/pray', $api . 'AppApiController::pray/$1');

    $routes->get('cart', $api . 'ShopApiController::cart');
    $routes->post('cart/add/(:num)', $api . 'ShopApiController::addToCart/$1');
    $routes->post('cart/items/(:num)', $api . 'ShopApiController::updateCartItem/$1');
    $routes->post('shop/products/(:num)/wishlist', $api . 'ShopApiController::toggleWishlist/$1');
});
