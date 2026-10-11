<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Forum\ForumPostController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MarketTrust
|--------------------------------------------------------------------------
*/

// Halaman Utama / Index
Route::get('/', [HomeController::class, 'index'])->name('home');

// Forum Komunitas (Publik)
Route::get('/forum', [ForumPostController::class, 'index'])->name('forum.index');

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

    // Pengelolaan Forum Post
    Route::get('/forum/create', [ForumPostController::class, 'create'])->name('forum.create');
    Route::post('/forum', [ForumPostController::class, 'store'])->name('forum.store');
    Route::get('/forum/{post}/edit', [ForumPostController::class, 'edit'])->name('forum.edit');
    Route::put('/forum/{post}', [ForumPostController::class, 'update'])->name('forum.update');
    Route::post('/forum/{post}/close', [ForumPostController::class, 'close'])->name('forum.close');
});

// Detail Forum Post (Bisa diakses publik, diletakkan setelah /forum/create)
Route::get('/forum/{post}', [ForumPostController::class, 'show'])->name('forum.show');
