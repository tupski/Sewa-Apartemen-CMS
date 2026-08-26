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
use App\Http\Controllers\Admin\CurrencyRateController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\SlugSettingsController;
use Illuminate\Support\Facades\Route;

// Homepage
Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Public Property Routes
Route::get('/apartments', [\App\Http\Controllers\PropertyController::class, 'publicIndex'])->name('properties.public.index');
Route::get('/apartments/{property:slug}', [\App\Http\Controllers\PropertyController::class, 'publicShow'])->name('properties.public.show');

// Public Blog Routes
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');

// Public search suggestions (JSON, consumed by Alpine autocomplete)
Route::get('/search/suggest', [SearchController::class, 'suggest'])
    ->name('search.suggest')
    ->middleware('throttle:30,1');

// Public SEO routes
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Public CMS Pages
Route::get('/pages/{page:slug}', [PageController::class, 'publicShow'])->name('pages.show');

Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public Booking Routes
Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store')->middleware('throttle:10,1');
// FIND-001: public booking pages are keyed by the random access token, never the numeric id / sequential code
Route::get('/bookings/{token}/success', [BookingController::class, 'success'])->name('bookings.success');
Route::get('/booking/status/{token}', [BookingController::class, 'publicStatus'])->name('bookings.status')->middleware('throttle:30,1');
Route::post('/booking/validate-voucher', [BookingController::class, 'validateVoucher'])->name('bookings.validate-voucher')->middleware('throttle:20,1');

// Admin CMS Routes (require authentication + admin role)
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Media Management
    Route::resource('media', MediaController::class);
    Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');

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
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');

    // Property Management
    Route::resource('properties', PropertyController::class);
    Route::patch('properties/{property}/status', [PropertyController::class, 'updateStatus'])->name('properties.status');
    Route::patch('properties/{property}/featured', [PropertyController::class, 'toggleFeatured'])->name('properties.featured');

    // Amenity Management
    Route::resource('amenities', AmenityController::class);
    Route::patch('amenities/{amenity}/status', [AmenityController::class, 'updateStatus'])->name('amenities.status');

    // Booking Management (Admin)
    Route::resource('bookings', BookingController::class)->only(['index', 'show', 'destroy']);
    Route::patch('bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::patch('bookings/{booking}/complete', [BookingController::class, 'complete'])->name('bookings.complete');
    Route::post('bookings/{booking}/notes', [BookingController::class, 'updateNotes'])->name('bookings.notes');
    Route::get('bookings/export/csv', [BookingController::class, 'export'])->name('bookings.export');

    // User Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);

    // Redirect Management
    Route::resource('redirects', RedirectController::class);

    // Blog Management
    Route::resource('posts', PostController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('tags', TagController::class);

    // Promo Rates (nested under properties)
    Route::post('properties/{property}/promos', [PromoRateController::class, 'store'])->name('properties.promos.store');
    Route::put('properties/{property}/promos/{promo}', [PromoRateController::class, 'update'])->name('properties.promos.update');
    Route::delete('properties/{property}/promos/{promo}', [PromoRateController::class, 'destroy'])->name('properties.promos.destroy');

    // Voucher Management
    Route::resource('vouchers', VoucherController::class);

    // Language Management
    Route::resource('languages', LanguageController::class)->except(['show']);
    Route::patch('languages/{language}/toggle-status', [LanguageController::class, 'toggleStatus'])->name('languages.toggle-status');

    // Currency Rates
    Route::get('currency-rates', [CurrencyRateController::class, 'index'])->name('currency-rates.index');
    Route::post('currency-rates', [CurrencyRateController::class, 'store'])->name('currency-rates.store');
    Route::put('currency-rates/{currencyRate}', [CurrencyRateController::class, 'update'])->name('currency-rates.update');
    Route::delete('currency-rates/{currencyRate}', [CurrencyRateController::class, 'destroy'])->name('currency-rates.destroy');
    Route::post('currency-rates/fetch', [CurrencyRateController::class, 'fetchNow'])->name('currency-rates.fetch');

    // Slug Settings
    Route::get('slug-settings', [SlugSettingsController::class, 'index'])->name('slug-settings.index');
    Route::post('slug-settings', [SlugSettingsController::class, 'update'])->name('slug-settings.update');

    // Locale switcher (admin session)
    Route::post('set-locale', function (\Illuminate\Http\Request $request) {
        $code = $request->input('locale');
        $valid = \App\Models\Language::where('code', $code)->where('is_active', true)->exists();
        if ($valid) session(['locale' => $code]);
        return back();
    })->name('admin.set-locale');

    // Currency switcher (admin session)
    Route::post('set-currency', function (\Illuminate\Http\Request $request) {
        $cur = strtoupper($request->input('currency', 'IDR'));
        session(['display_currency' => $cur]);
        return back();
    })->name('admin.set-currency');
});

require __DIR__.'/auth.php';
