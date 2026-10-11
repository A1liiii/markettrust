<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ConversationController;
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

    // Direct Messaging (DM)
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::match(['get', 'post'], '/conversations/listing/{listing}', [ConversationController::class, 'startFromListing'])->name('conversations.from-listing');
    Route::match(['get', 'post'], '/conversations/post/{post}', [ConversationController::class, 'startFromPost'])->name('conversations.from-post');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'sendMessage'])->name('conversations.messages.send');
});

