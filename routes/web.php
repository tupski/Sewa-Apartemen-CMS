<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CurrencyRateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\SlugSettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NavigationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromoRateController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemPageSeoController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\VoucherController;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public Property Routes (slugs configurable via admin "Slug & Path")
Route::get('/'.slug('slug_apartments', 'apartments'), [PropertyController::class, 'publicIndex'])->name('properties.public.index');
Route::get('/'.slug('slug_apartments', 'apartments').'/{property:slug}', [PropertyController::class, 'publicShow'])->name('properties.public.show');

// Public Blog Routes (slugs configurable via admin "Slug & Path")
Route::get('/'.slug('slug_blog', 'blog'), [BlogController::class, 'index'])->name('blog.index');
Route::get('/'.slug('slug_blog', 'blog').'/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/'.slug('slug_blog', 'blog').'/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/'.slug('slug_blog', 'blog').'/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');

// Public search suggestions (JSON, consumed by Alpine autocomplete)
Route::get('/search/suggest', [SearchController::class, 'suggest'])
    ->name('search.suggest')
    ->middleware('throttle:30,1');

// Public SEO routes
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Public Contact page + form submission
Route::get('/kontak', [ContactController::class, 'index'])->name('contact');
Route::post('/kontak', [ContactController::class, 'store'])->name('contact.store')->middleware(['throttle:5,1', 'captcha']);

// Public Promotions page
Route::get('/promosi', [PromotionController::class, 'index'])->name('promotions');

// Public CMS Pages (legacy /pages/{slug} path kept for backward compatibility;
// the catch-all /{slug} route is registered at the bottom of this file)
Route::get('/pages/{page:slug}', [PageController::class, 'publicShow'])->name('pages.show');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public Booking Routes (slugs configurable via admin "Slug & Path")
Route::post('/'.slug('slug_booking', 'bookings'), [BookingController::class, 'store'])->name('bookings.store')->middleware('throttle:10,1');
// FIND-001: public booking pages are keyed by the random access token, never the numeric id / sequential code
Route::get('/'.slug('slug_booking_success', 'bookings').'/{token}/success', [BookingController::class, 'success'])->name('bookings.success');
Route::get('/'.slug('slug_booking_status', 'booking/status').'/{token}', [BookingController::class, 'publicStatus'])->name('bookings.status')->middleware('throttle:30,1');
Route::post('/'.slug('slug_booking_status', 'booking/status').'/validate-voucher', [BookingController::class, 'validateVoucher'])->name('bookings.validate-voucher')->middleware('throttle:20,1');

// Admin CMS Routes (require authentication + admin role)
// The admin path prefix is configurable via admin "Slug & Path" (admin_prefix setting)
Route::middleware(['auth', 'verified', 'admin'])->prefix(slug('admin_prefix', 'admin'))->name('admin.')->group(function () {

    // Media Management
    // NOTE: custom routes declared BEFORE the resource so 'upload' / 'from-url'
    // are not matched as a {media} wildcard by the resource's show/update routes.
    Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::post('media/from-url', [MediaController::class, 'fromUrl'])->name('media.from-url');
    Route::resource('media', MediaController::class);

    // Pages Management
    // NOTE: the system-pages SEO routes must be declared BEFORE the resource so
    // 'system-seo' is not swallowed as a {page} wildcard by the resource routes.
    Route::get('pages/system-seo/{systemPage}', [SystemPageSeoController::class, 'edit'])->name('pages.system-seo.edit');
    Route::match(['put', 'patch'], 'pages/system-seo/{systemPage}', [SystemPageSeoController::class, 'update'])->name('pages.system-seo.update');
    Route::resource('pages', PageController::class);
    Route::patch('pages/{page}/status', [PageController::class, 'updateStatus'])->name('pages.status');

    // Blocks Management
    Route::resource('blocks', BlockController::class);
    Route::post('blocks/reorder', [BlockController::class, 'reorder'])->name('blocks.reorder');
    Route::patch('blocks/{block}/status', [BlockController::class, 'updateStatus'])->name('blocks.status');

    // Navigation Management
    Route::resource('navigations', NavigationController::class);
    Route::post('navigations/reorder', [NavigationController::class, 'reorder'])->name('navigations.reorder');
    Route::patch('navigations/{navigation}/status', [NavigationController::class, 'updateStatus'])->name('navigations.status');

    // Git Version Control (AJAX)
    // NOTE: these must be declared BEFORE settings/{group?} so 'git-status' /
    // 'git-pull' / 'git-fetch' / 'post-update' / 'git-commit-history' /
    // 'git-rollback' / 'git-backup-database' are not swallowed as a {group}
    // wildcard value.
    Route::get('settings/git-status', [SettingsController::class, 'gitStatus'])->name('settings.git-status');
    // On-demand update check — runs git:check-updates inline and refreshes the cached state.
    Route::post('settings/git-check-updates', [SettingsController::class, 'gitCheckUpdates'])->name('settings.git-check-updates');
    Route::post('settings/git-pull', [SettingsController::class, 'gitPull'])->name('settings.git-pull');
    Route::post('settings/git-fetch', [SettingsController::class, 'gitFetch'])->name('settings.git-fetch');
    // Post-update actions. {action} is only an allowlist KEY — the argv arrays are
    // hardcoded in PostUpdateActionService; unknown keys are rejected with 422.
    Route::post('settings/post-update/{action}', [SettingsController::class, 'gitPostUpdate'])->name('settings.post-update');

    // Version Control — remote info, commit history, rollback, return-to-branch,
    // DB backup. All state-changing routes are POST; the SHA is validated
    // server-side (regex + cat-file existence) in the controller.
    Route::get('settings/git-remote-info', [SettingsController::class, 'gitRemoteInfo'])->name('settings.git-remote-info');
    Route::get('settings/git-commit-history', [SettingsController::class, 'gitCommitHistory'])->name('settings.git-commit-history');
    Route::post('settings/git-rollback', [SettingsController::class, 'gitRollback'])->name('settings.git-rollback');
    Route::post('settings/git-return-to-branch', [SettingsController::class, 'gitReturnToBranch'])->name('settings.git-return-to-branch');
    Route::post('settings/git-backup-database', [SettingsController::class, 'gitBackupDatabase'])->name('settings.git-backup-database');
    Route::get('settings/git-backup-download/{filename}', [SettingsController::class, 'gitBackupDownload'])->name('settings.git-backup-download');

    // Settings Management
    Route::get('settings/{group?}', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('clear-cache', [SettingsController::class, 'clearCache'])->name('clear-cache');

    // Property Management
    Route::post('properties/bulk-action', [PropertyController::class, 'bulkAction'])->name('properties.bulk-action');
    Route::resource('properties', PropertyController::class);
    Route::patch('properties/{property}/status', [PropertyController::class, 'updateStatus'])->name('properties.status');
    Route::patch('properties/{property}/featured', [PropertyController::class, 'toggleFeatured'])->name('properties.featured');
    // Persistent Geoapify POIs (Phase 5)
    Route::get('properties/{property}/nearby-places', [PropertyController::class, 'nearbyPlaces'])->name('properties.nearby-places');
    // SEC-001: throttled — every resync clears the 24h cache and forces a paid
    // Geoapify call, so cap it at 5 attempts per 10 minutes.
    Route::post('properties/{property}/resync-nearby-places', [PropertyController::class, 'resyncNearbyPlaces'])->name('properties.resync-nearby-places')->middleware('throttle:5,10');

    // Amenity Management
    Route::resource('amenities', AmenityController::class);
    Route::patch('amenities/{amenity}/status', [AmenityController::class, 'updateStatus'])->name('amenities.status');

    // Booking Management (Admin)
    // NOTE: export route must be declared BEFORE the resource so 'export' is not
    // matched as a {booking} wildcard by the resource's show route.
    Route::get('bookings/export/csv', [BookingController::class, 'export'])->name('bookings.export');
    Route::resource('bookings', BookingController::class)->only(['index', 'show', 'destroy']);
    Route::patch('bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::patch('bookings/{booking}/complete', [BookingController::class, 'complete'])->name('bookings.complete');
    Route::post('bookings/{booking}/notes', [BookingController::class, 'updateNotes'])->name('bookings.notes');

    // User Management
    Route::resource('users', UserController::class);

    // Redirect Management
    Route::resource('redirects', RedirectController::class);

    // Blog Management
    // NOTE: custom routes must be declared BEFORE the resource so 'upload-image'
    // is not matched as a {post} wildcard by the resource's show/update routes.
    Route::post('posts/upload-image', [PostController::class, 'uploadImage'])->name('posts.upload-image');
    Route::resource('posts', PostController::class);
    // AJAX quick-create of a blog category from the post form (returns JSON).
    Route::post('categories/store-ajax', [CategoryController::class, 'storeAjax'])->name('categories.store-ajax');
    Route::resource('categories', CategoryController::class);
    Route::resource('tags', TagController::class);

    // Promo Rates (nested under properties)
    Route::post('properties/{property}/promos', [PromoRateController::class, 'store'])->name('properties.promos.store');
    Route::put('properties/{property}/promos/{promo}', [PromoRateController::class, 'update'])->name('properties.promos.update');
    Route::delete('properties/{property}/promos/{promo}', [PromoRateController::class, 'destroy'])->name('properties.promos.destroy');

    // Voucher Management
    Route::resource('vouchers', VoucherController::class);

    // Language Management
    Route::get('languages/{language}/translations', [LanguageController::class, 'editTranslations'])->name('languages.translations');
    Route::match(['put', 'patch'], 'languages/{language}/translations', [LanguageController::class, 'updateTranslations'])->name('languages.translations.update');
    Route::resource('languages', LanguageController::class)->except(['show']);
    Route::patch('languages/{language}/toggle-status', [LanguageController::class, 'toggleStatus'])->name('languages.toggle-status');

    // Currency Rates
    Route::get('currency-rates', [CurrencyRateController::class, 'index'])->name('currency-rates.index');
    Route::post('currency-rates', [CurrencyRateController::class, 'store'])->name('currency-rates.store');
    Route::put('currency-rates/{currencyRate}', [CurrencyRateController::class, 'update'])->name('currency-rates.update');
    Route::delete('currency-rates/{currencyRate}', [CurrencyRateController::class, 'destroy'])->name('currency-rates.destroy');
    Route::post('currency-rates/fetch', [CurrencyRateController::class, 'fetchNow'])->name('currency-rates.fetch');

    // Backup & Restore
    Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
    Route::post('backup/download', [BackupController::class, 'download'])->name('backup.download');
    Route::post('backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
    Route::post('backup/restore/confirm', [BackupController::class, 'confirmRestore'])->name('backup.restore.confirm');

    // Slug Settings
    Route::get('slug-settings', [SlugSettingsController::class, 'index'])->name('slug-settings.index');
    Route::post('slug-settings', [SlugSettingsController::class, 'update'])->name('slug-settings.update');

    // Locale switcher (admin session)
    // NOTE: inside name('admin.') group — use 'set-locale' not 'admin.set-locale'
    // to avoid double-prefix (would register as admin.admin.set-locale otherwise).
    Route::post('set-locale', function (Request $request) {
        $code = $request->input('locale');
        $valid = Language::where('code', $code)->where('is_active', true)->exists();
        if ($valid) {
            session(['locale' => $code]);
        }

        return back();
    })->name('set-locale');

    // Currency switcher (admin session)
    Route::post('set-currency', function (Request $request) {
        $cur = strtoupper($request->input('currency', 'IDR'));
        session(['display_currency' => $cur]);

        return back();
    })->name('set-currency');
});

require __DIR__.'/auth.php';

// Catch-all CMS Page route: renders any published page at its slug.
// MUST be registered LAST so it does not shadow other routes (login, register, etc.).
// This route matches any single-segment URL that is not caught by a previous route.
Route::get('/{page:slug}', [PageController::class, 'publicShow'])->name('pages.show');
