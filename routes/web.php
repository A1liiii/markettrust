<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MarketTrust
|--------------------------------------------------------------------------
*/

// Halaman Utama / Index
Route::get('/', [HomeController::class, 'index'])->name('home');

// Tamu (Belum Login)
Route::middleware('guest')->group(function () {
    // Autentikasi Lokal
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Google OAuth 2.0
    Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    // Pemilihan Username Google Pertama Kali
    Route::get('/auth/google/username', [GoogleAuthController::class, 'showChooseUsernameForm'])->name('auth.google.username');
    Route::post('/auth/google/username', [GoogleAuthController::class, 'completeGoogleRegistration'])->name('auth.google.username.store');

    // Tautkan Akun Google ke Akun Lokal Eksisting
    Route::get('/auth/google/link', [GoogleAuthController::class, 'showLinkForm'])->name('auth.google.link');
    Route::post('/auth/google/link', [GoogleAuthController::class, 'linkAccount'])->name('auth.google.link.store');
});

// Pengguna Terautentikasi (Sudah Login)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Modul Admin: Master Katalog (Kategori, Produk, Varian)
    Route::middleware('can:manage-catalog')->prefix('admin/catalog')->name('admin.catalog.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CatalogController::class, 'index'])->name('index');

        // Kategori Produk
        Route::post('/categories', [\App\Http\Controllers\Admin\CatalogController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [\App\Http\Controllers\Admin\CatalogController::class, 'updateCategory'])->name('categories.update');
        Route::patch('/categories/{category}/toggle', [\App\Http\Controllers\Admin\CatalogController::class, 'toggleCategoryStatus'])->name('categories.toggle');

        // Master Produk
        Route::post('/products', [\App\Http\Controllers\Admin\CatalogController::class, 'storeProduct'])->name('products.store');
        Route::put('/products/{product}', [\App\Http\Controllers\Admin\CatalogController::class, 'updateProduct'])->name('products.update');
        Route::patch('/products/{product}/toggle', [\App\Http\Controllers\Admin\CatalogController::class, 'toggleProductStatus'])->name('products.toggle');

        // Master Varian
        Route::post('/variants', [\App\Http\Controllers\Admin\CatalogController::class, 'storeVariant'])->name('variants.store');
        Route::put('/variants/{variant}', [\App\Http\Controllers\Admin\CatalogController::class, 'updateVariant'])->name('variants.update');
        Route::patch('/variants/{variant}/toggle', [\App\Http\Controllers\Admin\CatalogController::class, 'toggleVariantStatus'])->name('variants.toggle');
    });

    // Modul Seller: Pengelolaan Listing
    Route::prefix('seller/listings')->name('seller.listings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Seller\ListingController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Seller\ListingController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Seller\ListingController::class, 'store'])->name('store');
        Route::get('/{listing}/edit', [\App\Http\Controllers\Seller\ListingController::class, 'edit'])->name('edit');
        Route::put('/{listing}', [\App\Http\Controllers\Seller\ListingController::class, 'update'])->name('update');
        Route::patch('/{listing}/toggle-status', [\App\Http\Controllers\Seller\ListingController::class, 'toggleStatus'])->name('toggle-status');
    });
});

