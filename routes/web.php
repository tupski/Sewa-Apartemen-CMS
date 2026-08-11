<?php

use App\Http\Controllers\AmenityController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NavigationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public Blog Routes
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');

// Public SEO routes
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public Booking Routes
Route::get('/units/{unit:slug}/booking', [BookingController::class, 'create'])->name('bookings.create');
Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
Route::get('/bookings/{booking}/success', [BookingController::class, 'success'])->name('bookings.success');

// Admin CMS Routes (require authentication)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {

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

    // Unit Management
    Route::resource('units', UnitController::class);
    Route::patch('units/{unit}/status', [UnitController::class, 'updateStatus'])->name('units.status');

    // Amenity Management
    Route::resource('amenities', AmenityController::class);
    Route::patch('amenities/{amenity}/status', [AmenityController::class, 'updateStatus'])->name('amenities.status');

    // Booking Management (Admin)
    Route::resource('bookings', BookingController::class)->only(['index', 'show', 'destroy']);
    Route::patch('bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::patch('bookings/{booking}/complete', [BookingController::class, 'complete'])->name('bookings.complete');

    // Redirect Management
    Route::resource('redirects', RedirectController::class);

    // Blog Management
    Route::resource('posts', PostController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('tags', TagController::class);
});

require __DIR__.'/auth.php';
