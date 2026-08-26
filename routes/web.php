<?php

use App\Http\Controllers\AmenityController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NavigationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromoRateController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CurrencyRateController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\SlugSettingsController;
use Illuminate\Support\Facades\Route;

// Homepage
Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Public Property Routes (slugs configurable via admin "Slug & Path")
Route::get('/' . slug('slug_apartments', 'apartments'), [\App\Http\Controllers\PropertyController::class, 'publicIndex'])->name('properties.public.index');
Route::get('/' . slug('slug_apartments', 'apartments') . '/{property:slug}', [\App\Http\Controllers\PropertyController::class, 'publicShow'])->name('properties.public.show');

// Public Blog Routes (slugs configurable via admin "Slug & Path")
Route::get('/' . slug('slug_blog', 'blog'), [BlogController::class, 'index'])->name('blog.index');
Route::get('/' . slug('slug_blog', 'blog') . '/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/' . slug('slug_blog', 'blog') . '/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/' . slug('slug_blog', 'blog') . '/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');

// Public search suggestions (JSON, consumed by Alpine autocomplete)
Route::get('/search/suggest', [SearchController::class, 'suggest'])
    ->name('search.suggest')
    ->middleware('throttle:30,1');

// Public SEO routes
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Public Contact page + form submission
Route::get('/kontak', [\App\Http\Controllers\ContactController::class, 'index'])->name('contact');
Route::post('/kontak', [\App\Http\Controllers\ContactController::class, 'store'])->name('contact.store')->middleware(['throttle:5,1', 'captcha']);

// Public Promotions page
Route::get('/promosi', [\App\Http\Controllers\PromotionController::class, 'index'])->name('promotions');

// Public CMS Pages (legacy /pages/{slug} path kept for backward compatibility;
// the catch-all /{slug} route is registered at the bottom of this file)
Route::get('/pages/{page:slug}', [PageController::class, 'publicShow'])->name('pages.show');

Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public Booking Routes (slugs configurable via admin "Slug & Path")
Route::post('/' . slug('slug_booking', 'bookings'), [BookingController::class, 'store'])->name('bookings.store')->middleware('throttle:10,1');
// FIND-001: public booking pages are keyed by the random access token, never the numeric id / sequential code
Route::get('/' . slug('slug_booking_success', 'bookings') . '/{token}/success', [BookingController::class, 'success'])->name('bookings.success');
Route::get('/' . slug('slug_booking_status', 'booking/status') . '/{token}', [BookingController::class, 'publicStatus'])->name('bookings.status')->middleware('throttle:30,1');
Route::post('/' . slug('slug_booking_status', 'booking/status') . '/validate-voucher', [BookingController::class, 'validateVoucher'])->name('bookings.validate-voucher')->middleware('throttle:20,1');

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

    // Settings Management
    Route::get('settings/{group?}', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings/{group}', [SettingsController::class, 'update'])->name('settings.update');

    // Property Management
    Route::resource('properties', PropertyController::class);
    Route::patch('properties/{property}/status', [PropertyController::class, 'updateStatus'])->name('properties.status');
    Route::patch('properties/{property}/featured', [PropertyController::class, 'toggleFeatured'])->name('properties.featured');

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
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);

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
    Route::post('set-locale', function (\Illuminate\Http\Request $request) {
        $code = $request->input('locale');
        $valid = \App\Models\Language::where('code', $code)->where('is_active', true)->exists();
        if ($valid) session(['locale' => $code]);
        return back();
    })->name('set-locale');

    // Currency switcher (admin session)
    Route::post('set-currency', function (\Illuminate\Http\Request $request) {
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
