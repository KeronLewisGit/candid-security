<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ApplyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Route;

// ---- Public site ----
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/services/{service}', [PageController::class, 'service'])->name('services.show');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/locations', [PageController::class, 'locations'])->name('locations');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/photo-credits', [PageController::class, 'credits'])->name('credits');
Route::get('/sitemap', [PageController::class, 'sitemapPage'])->name('sitemap.page');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

// Client intake: guard-service quote, armoured pickup, general message.
Route::get('/request-a-quote', [RequestController::class, 'quote'])->name('quote');
Route::get('/armoured-pickup', [RequestController::class, 'pickup'])->name('pickup');
Route::get('/contact', [RequestController::class, 'contact'])->name('contact');
Route::post('/requests/{kind}', [RequestController::class, 'store'])->whereIn('kind', ['quote', 'pickup', 'contact'])->middleware('throttle:forms')->name('requests.store');
Route::get('/requests/received', [RequestController::class, 'received'])->name('requests.received');

// Careers + the online application that replaces the paper form.
Route::get('/careers', [CareerController::class, 'index'])->name('careers');
Route::get('/careers/{job}', [CareerController::class, 'show'])->name('careers.show');
Route::get('/apply', [ApplyController::class, 'start'])->name('apply');
Route::post('/apply', [ApplyController::class, 'begin'])->middleware('throttle:forms')->name('apply.begin');
Route::middleware('auth:applicant')->group(function () {
    Route::get('/apply/{reference}/step/{step}', [ApplyController::class, 'step'])->whereNumber('step')->name('apply.step');
    Route::post('/apply/{reference}/step/{step}', [ApplyController::class, 'save'])->whereNumber('step')->name('apply.save');
    Route::get('/apply/{reference}/submitted', [ApplyController::class, 'submitted'])->name('apply.submitted');
});

// Addresses from the old WordPress site, so bookmarks and search results keep working.
foreach ([
    'contact-information-3' => '/contact', 'employment-opportunities-2' => '/careers', 'corporate-info/company-history' => '/about',
    'executive-and-management-team' => '/about', 'corporate-info' => '/about',
] as $old => $new) {
    Route::permanentRedirect($old, $new);
}

// ---- Applicant portal ----
Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest:applicant')->group(function () {
        Route::get('login', [Portal\AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [Portal\AuthController::class, 'login'])->middleware('throttle:login');
        Route::get('register', [Portal\AuthController::class, 'showRegister'])->name('register');
        Route::post('register', [Portal\AuthController::class, 'register'])->middleware('throttle:forms');
        Route::get('forgot-password', [Portal\AuthController::class, 'showForgot'])->name('password.request');
        Route::post('forgot-password', [Portal\AuthController::class, 'sendReset'])->middleware('throttle:login')->name('password.email');
        Route::get('reset-password/{token}', [Portal\AuthController::class, 'showReset'])->name('password.reset');
        Route::post('reset-password', [Portal\AuthController::class, 'reset'])->middleware('throttle:login')->name('password.update');
    });
    Route::middleware('auth:applicant')->group(function () {
        Route::get('/', [Portal\PortalController::class, 'dashboard'])->name('dashboard');
        Route::post('logout', [Portal\AuthController::class, 'logout'])->name('logout');
        Route::get('applications/{reference}', [Portal\PortalController::class, 'show'])->name('applications.show');
        Route::post('applications/{reference}/documents', [Portal\PortalController::class, 'upload'])->middleware('throttle:20,1')->name('applications.documents');
        Route::post('applications/{reference}/book', [Portal\PortalController::class, 'book'])->middleware('throttle:10,1')->name('applications.book');
        Route::delete('applications/{reference}/book/{booking}', [Portal\PortalController::class, 'cancel'])->name('applications.cancel');
        Route::post('applications/{reference}/withdraw', [Portal\PortalController::class, 'withdraw'])->name('applications.withdraw');
        Route::get('documents/{document}', [Portal\PortalController::class, 'document'])->name('documents.show');
        Route::get('inbox', [Portal\PortalController::class, 'inbox'])->name('inbox');
        Route::get('profile', [Portal\PortalController::class, 'profile'])->name('profile');
        Route::put('profile', [Portal\PortalController::class, 'updateProfile'])->name('profile.update');
    });
});

// ---- Staff ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    // Recruitment: admin + recruiter.
    Route::middleware('role:recruiter')->group(function () {
        Route::get('applications', [Admin\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/pipeline', [Admin\ApplicationController::class, 'pipeline'])->name('applications.pipeline');
        Route::get('applications/export', [Admin\ApplicationController::class, 'export'])->name('applications.export');
        Route::post('applications/bulk', [Admin\ApplicationController::class, 'bulk'])->name('applications.bulk');
        Route::get('applications/{application}', [Admin\ApplicationController::class, 'show'])->name('applications.show');
        Route::patch('applications/{application}', [Admin\ApplicationController::class, 'update'])->name('applications.update');
        Route::post('applications/{application}/notes', [Admin\ApplicationController::class, 'note'])->name('applications.notes');
        Route::post('applications/{application}/message', [Admin\ApplicationController::class, 'message'])->name('applications.message');
        Route::post('applications/{application}/documents', [Admin\DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}', [Admin\DocumentController::class, 'show'])->name('documents.show');
        Route::patch('documents/{document}', [Admin\DocumentController::class, 'update'])->name('documents.update');

        Route::resource('jobs', Admin\JobController::class)->except('show');
        Route::get('slots', [Admin\SlotController::class, 'index'])->name('slots.index');
        Route::post('slots', [Admin\SlotController::class, 'store'])->name('slots.store');
        Route::delete('slots/{slot}', [Admin\SlotController::class, 'destroy'])->name('slots.destroy');
        Route::resource('templates', Admin\TemplateController::class)->except('show');
    });

    // Client operations: admin + operations.
    Route::middleware('role:operations')->group(function () {
        Route::get('requests', [Admin\RequestController::class, 'index'])->name('requests.index');
        Route::get('requests/{serviceRequest}', [Admin\RequestController::class, 'show'])->name('requests.show');
        Route::patch('requests/{serviceRequest}', [Admin\RequestController::class, 'update'])->name('requests.update');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::resource('users', Admin\UserController::class)->except('show');
    });
});
