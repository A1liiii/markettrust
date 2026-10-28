<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminModuleController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FeePreviewController;
use App\Http\Controllers\Forum\ForumCommentController;
use App\Http\Controllers\Forum\ForumLikeController;
use App\Http\Controllers\Forum\ForumPostController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketplaceCheckoutController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Seller\ListingController;
use App\Http\Controllers\Seller\SellerPayoutAccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionDeliveryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MarketTrust
|--------------------------------------------------------------------------
*/

// Halaman Utama / Index
Route::get('/', [HomeController::class, 'index'])->name('home');

// Katalog & Detail Marketplace Digital (Publik)
Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('/marketplace/{listing}', [MarketplaceController::class, 'show'])->name('marketplace.show');

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

// Pengguna Terautentikasi (Sudah Login & Aktif)
Route::middleware(['auth', 'active_account'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Manajemen Profil
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Preview Kalkulasi Fee
    Route::post('/fee/preview', [FeePreviewController::class, 'preview'])->name('fee.preview');

    // Direct Messaging (DM)
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::match(['get', 'post'], '/conversations/listing/{listing}', [ConversationController::class, 'startFromListing'])->name('conversations.from-listing');
    Route::match(['get', 'post'], '/conversations/post/{post}', [ConversationController::class, 'startFromPost'])->name('conversations.from-post');
    Route::post('/conversations/post/{post}/quick', [ConversationController::class, 'startFromPostQuick'])->name('conversations.from-post.quick');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'sendMessage'])->name('conversations.messages.send');
    Route::get('/conversations/{conversation}/messages/{message}/attachment', [ConversationController::class, 'downloadAttachment'])->name('conversations.messages.attachment');
    Route::get('/conversations/{conversation}/sync', [ConversationController::class, 'sync'])->name('conversations.sync');

    // Checkout Marketplace & Detail Transaksi Kesepakatan
    Route::get('/marketplace/{listing}/checkout', [MarketplaceCheckoutController::class, 'review'])->name('marketplace.checkout.review');
    Route::post('/marketplace/{listing}/checkout', [MarketplaceCheckoutController::class, 'confirm'])->name('marketplace.checkout.confirm');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transactions/{transaction}/payment-token', [PaymentController::class, 'createSnapToken'])->name('transactions.payment-token');
    Route::post('/transactions/{transaction}/sync-payment', [PaymentController::class, 'syncStatus'])->name('transactions.sync-payment');
    Route::post('/transactions/{transaction}/deliver', [TransactionDeliveryController::class, 'store'])->name('transactions.deliver');
    Route::get('/transactions/{transaction}/evidence/{evidence}', [TransactionDeliveryController::class, 'downloadEvidence'])->name('transactions.delivery-evidence');
    Route::post('/transactions/{transaction}/extend-delivery', [TransactionDeliveryController::class, 'extendDeadline'])->name('transactions.extend-delivery');

    // Pusat Notifikasi Pengguna & Integrasi Event
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::match(['get', 'post'], '/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
});

// Webhook Notifikasi Pembayaran Midtrans (Publik & Tanpa CSRF)
Route::post('/api/midtrans/notification', [PaymentController::class, 'handleNotification'])->name('midtrans.notification');
Route::post('/payments/midtrans/notification', [PaymentController::class, 'handleNotification']);

// Area Khusus Administrator
Route::middleware(['auth', 'active_account', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Pusat Kontrol & Analitik
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [AdminDashboardController::class, 'analytics'])->name('dashboard.analytics');

    // Master Katalog
    Route::middleware('can:manage-catalog')->prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', [CatalogController::class, 'index'])->name('index');

        Route::post('/categories', [CatalogController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [CatalogController::class, 'updateCategory'])->name('categories.update');
        Route::patch('/categories/{category}/toggle', [CatalogController::class, 'toggleCategoryStatus'])->name('categories.toggle');

        Route::post('/products', [CatalogController::class, 'storeProduct'])->name('products.store');
        Route::put('/products/{product}', [CatalogController::class, 'updateProduct'])->name('products.update');
        Route::patch('/products/{product}/toggle', [CatalogController::class, 'toggleProductStatus'])->name('products.toggle');

        Route::post('/variants', [CatalogController::class, 'storeVariant'])->name('variants.store');
        Route::put('/variants/{variant}', [CatalogController::class, 'updateVariant'])->name('variants.update');
        Route::patch('/variants/{variant}/toggle', [CatalogController::class, 'toggleVariantStatus'])->name('variants.toggle');
    });

    // Modul Lanjutan Administrator
    Route::get('/transactions', [AdminModuleController::class, 'transactions'])->name('transactions.index');
    Route::get('/disputes', [AdminModuleController::class, 'disputes'])->name('disputes.index');
    Route::get('/users', [AdminModuleController::class, 'users'])->name('users.index');
    Route::get('/audit-logs', [AdminModuleController::class, 'auditLogs'])->name('audit-logs.index');
});

// Modul Seller: Pengelolaan Listing
Route::middleware(['auth', 'active_account'])
    ->prefix('seller/listings')
    ->name('seller.listings.')
    ->group(function () {
        Route::get('/', [ListingController::class, 'index'])->name('index');
        Route::get('/create', [ListingController::class, 'create'])->name('create');
        Route::post('/', [ListingController::class, 'store'])->name('store');
        Route::get('/{listing}/edit', [ListingController::class, 'edit'])->name('edit');
        Route::put('/{listing}', [ListingController::class, 'update'])->name('update');
        Route::get('/{listing}/preview', [ListingController::class, 'preview'])->name('preview');
        Route::patch('/{listing}/toggle-status', [ListingController::class, 'toggleStatus'])->name('toggle-status');
    });

// Modul Seller: Pengaturan Rekening Payout
Route::middleware(['auth', 'active_account'])
    ->prefix('seller/payout-accounts')
    ->name('seller.payout-accounts.')
    ->group(function () {
        Route::get('/', [SellerPayoutAccountController::class, 'index'])->name('index');
        Route::get('/create', [SellerPayoutAccountController::class, 'create'])->name('create');
        Route::post('/', [SellerPayoutAccountController::class, 'store'])->name('store');
        Route::get('/{payoutAccount}/edit', [SellerPayoutAccountController::class, 'edit'])->name('edit');
        Route::put('/{payoutAccount}', [SellerPayoutAccountController::class, 'update'])->name('update');
        Route::patch('/{payoutAccount}/activate', [SellerPayoutAccountController::class, 'activate'])->name('activate');
        Route::delete('/{payoutAccount}', [SellerPayoutAccountController::class, 'destroy'])->name('destroy');
    });

// Alias rute profil untuk akses cepat ke rekening payout
Route::middleware(['auth', 'active_account'])->group(function () {
    Route::get('/profile/payout-accounts', [SellerPayoutAccountController::class, 'index'])->name('profile.payout-accounts');
});

// Pengelolaan Forum Post oleh pengguna aktif
Route::middleware(['auth', 'active_account'])->group(function () {
    Route::get('/forum/create', [ForumPostController::class, 'create'])->name('forum.create');
    Route::post('/forum', [ForumPostController::class, 'store'])->name('forum.store');
    Route::get('/forum/{post}/edit', [ForumPostController::class, 'edit'])->name('forum.edit');
    Route::put('/forum/{post}', [ForumPostController::class, 'update'])->name('forum.update');
    Route::post('/forum/{post}/close', [ForumPostController::class, 'close'])->name('forum.close');

    // Interaksi Forum: Komentar, Like, dan Negosiasi
    Route::post('/forum/{post}/comments', [ForumCommentController::class, 'store'])->name('forum.comments.store');
    Route::delete('/forum/comments/{comment}', [ForumCommentController::class, 'destroy'])->name('forum.comments.destroy');
    Route::post('/forum/{post}/like', [ForumLikeController::class, 'togglePost'])->name('forum.posts.like');
    Route::post('/forum/comments/{comment}/like', [ForumLikeController::class, 'toggleComment'])->name('forum.comments.like');
    Route::post('/forum/{post}/negotiate', [ForumPostController::class, 'negotiate'])->name('forum.negotiate');
});

// Detail posting dapat dibaca publik
Route::get('/forum/{post}', [ForumPostController::class, 'show'])->name('forum.show');
