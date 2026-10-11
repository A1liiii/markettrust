<?php

use App\Http\Controllers\Admin\FeeConfigController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\FeePreviewController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
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

// Pengguna Terautentikasi (Sudah Login & Aktif)
Route::middleware(['auth', 'active_account'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Manajemen Profil
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Preview Kalkulasi Fee
    Route::post('/fee/preview', [FeePreviewController::class, 'preview'])->name('fee.preview');
});

// Area Khusus Administrator
Route::middleware(['auth', 'active_account', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/fee-configs', [FeeConfigController::class, 'index'])->name('fee-configs.index');
    Route::post('/fee-configs', [FeeConfigController::class, 'store'])->name('fee-configs.store');

    // Master Katalog
    Route::middleware('can:manage-catalog')->prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CatalogController::class, 'index'])->name('index');

        Route::post('/categories', [\App\Http\Controllers\Admin\CatalogController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [\App\Http\Controllers\Admin\CatalogController::class, 'updateCategory'])->name('categories.update');
        Route::patch('/categories/{category}/toggle', [\App\Http\Controllers\Admin\CatalogController::class, 'toggleCategoryStatus'])->name('categories.toggle');

        Route::post('/products', [\App\Http\Controllers\Admin\CatalogController::class, 'storeProduct'])->name('products.store');
        Route::put('/products/{product}', [\App\Http\Controllers\Admin\CatalogController::class, 'updateProduct'])->name('products.update');
        Route::patch('/products/{product}/toggle', [\App\Http\Controllers\Admin\CatalogController::class, 'toggleProductStatus'])->name('products.toggle');

        Route::post('/variants', [\App\Http\Controllers\Admin\CatalogController::class, 'storeVariant'])->name('variants.store');
        Route::put('/variants/{variant}', [\App\Http\Controllers\Admin\CatalogController::class, 'updateVariant'])->name('variants.update');
        Route::patch('/variants/{variant}/toggle', [\App\Http\Controllers\Admin\CatalogController::class, 'toggleVariantStatus'])->name('variants.toggle');
    });
});
