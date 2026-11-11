# Bug Report: Penanganan Pembatalan Google OAuth Callback (400 Bad Request)

- **ID Laporan**: `BUG-AUTH-GOOGLE-CANCEL-001`
- **Komponen**: `Auth / Google OAuth (Socialite)`
- **File Terdampak**: `app/Http/Controllers/Auth/GoogleAuthController.php`
- **Tingkat Keparahan**: Medium (Dampak UX Tinggi)
- **Status**: Resolved (Telah Diperbaiki)
- **Tanggal Ditemukan & Diperbaiki**: 10 November 2026

---

## 1. Deskripsi Masalah
Ketika pengguna menekan tombol **"Masuk dengan Google"** lalu membatalkan proses pada layar persetujuan Google (*Google OAuth Consent Screen*) atau menutup dialog OAuth, sistem mengarahkan kembali pengguna ke endpoint callback (`/auth/google/callback`) tanpa menyertakan parameter otorisasi `code` (atau menyertakan parameter `error=access_denied`).

Sebelumnya, method `handleGoogleCallback()` langsung memanggil:
```php
$googleUser = Socialite::driver('google')->stateless()->user();
```
tanpa memeriksa parameter permintaan terlebih dahulu. Karena `code` kosong, library Socialite tetap berusaha menukarkan authorization code kosong tersebut ke endpoint Google:
`POST https://www.googleapis.com/oauth2/v4/token`

Akibatnya, server Google mengembalikan respon `400 Bad Request`:
```json
{
  "error": "invalid_request",
  "error_description": "Missing required parameter: code"
}
```
Pengecualian (*client error exception*) ini tertangkap pada blok `catch (\Throwable $e)` dan memunculkan pesan error teknis mentah di halaman login:
> *"Periksa kembali data Anda: Gagal mengautentikasi akun Google: Client error: `POST https://www.googleapis.com/oauth2/v4/token` resulted in a `400 Bad Request` response: {\n "error": "invalid_request",\n "error_description": "Missing required parameter: code"\n}"*

Pesan tersebut membingungkan pengguna dan menampilkan detail teknis internal API Google.

---

## 2. Langkah Reproduksi (Steps to Reproduce)
1. Akses halaman login MarketTrust (`/login`) atau registrasi (`/register`).
2. Klik tombol **"Masuk dengan Google"**.
3. Di halaman consent Google, klik tombol **"Batal"** / **"Cancel"** (atau arahkan browser ke `/auth/google/callback?error=access_denied` atau `/auth/google/callback` tanpa parameter `code`).
4. Amati pesan error yang muncul di halaman login.

---

## 3. Analisis Akar Masalah (Root Cause Analysis)
Protokol OAuth 2.0 menetapkan bahwa jika pengguna menolak memberikan izin (*consent*), *authorization server* akan me-redirect pengguna ke *redirect_uri* dengan parameter `error` (misal `access_denied`) dan tanpa parameter `code`. Pemanggilan `Socialite::driver('google')->user()` tanpa validasi awal menyebabkan Socialite mengasumsikan parameter `code` selalu ada, sehingga terjadi kegagalan HTTP POST request ke token endpoint Google.

---

## 4. Solusi & Perbaikan
Perbaikan diimplementasikan pada `GoogleAuthController::handleGoogleCallback()`:
1. **Pencegahan Awal (*Early Return*)**:
   Mengecek apakah `$request->has('error')` atau `!$request->filled('code')`. Jika pengguna membatalkan atau tidak ada parameter `code`, redirect langsung ke route `login` tanpa melakukan request HTTP ke Google.
2. **Pesan Pengguna Ramah**:
   Menampilkan pesan error yang jelas dan ramah:
   > *"Anda membatalkan login dengan akun Google baru Anda."*
3. **Fallback Catch Block**:
   Jika token exchange gagal dengan pesan error `Missing required parameter: code` atau `access_denied`, sistem tetap menangkap dan mengembalikan pesan ramah yang sama.

---

## 5. Hasil Verifikasi
- Pengguna yang membatalkan login Google kini diarahkan kembali ke halaman login dengan alert merah ramah pengguna: *"Anda membatalkan login dengan akun Google baru Anda."*
- Log aplikasi tidak lagi mencatat false-positive error token exchange untuk pembatalan yang dilakukan secara sengaja oleh pengguna.
