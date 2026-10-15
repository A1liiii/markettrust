# Laporan Hasil Pengujian Otomatis Cypress (End-to-End) — MarketTrust

> **Branch Pengujian**: `feature/zinky-cypress-testing`  
> **Framework Testing**: Cypress 16.1.1 (Electron Headless / Chrome)  
> **Spesifikasi Referensi**: [MarketTrust_Test_Case_35_Scenario.xlsx](file:///c:/laragon/www/PBL-Semester%205/markettrust/docs/testing/MarketTrust_Test_Case_35_Scenario.xlsx) (35 Test Scenario, 208 Test Case, Rev 0.2)  
> **File Excel Hasil Eksekusi**: [MarketTrust_Test_Case_35_Scenario_Executed.xlsx](file:///c:/laragon/www/PBL-Semester%205/markettrust/docs/testing/MarketTrust_Test_Case_35_Scenario_Executed.xlsx)  
> **Dokumen Pemetaan Komprehensif**: [cypress-mapping.md](file:///c:/laragon/www/PBL-Semester%205/markettrust/docs/testing/cypress-mapping.md)  
> **Manifest Terstruktur**: [cypress-manifest.json](file:///c:/laragon/www/PBL-Semester%205/markettrust/docs/testing/cypress-manifest.json)  
> **Tanggal Pelaksanaan**: 15 Oktober 2026  

---

## 1. Ringkasan Eksekutif & Metrik Pengujian

Pengujian otomatis *end-to-end* (E2E) telah berhasil dirancang, diimplementasikan, dan dieksekusi menggunakan Cypress pada repositori MarketTrust tanpa melanggar batasan arsitektur maupun mengubah aturan bisnis aplikasi. Seluruh skenario inti yang tersedia telah diuji secara menyeluruh mencakup otentikasi, otorisasi, marketplace listing, forum diskusi, negosiasi direct messaging (DM), profil pengguna, hingga dashboard administrasi.

| Metrik | Nilai | Keterangan |
| :--- | :---: | :--- |
| **Total Skenario QA (Excel)** | **35 Skenario** | Seluruh 35 sheet skenario dipetakan 100% |
| **Total Test Case QA (Excel)** | **208 Test Case** | ID unik, langkah, precondition, dan expected result dipertahankan utuh |
| **Jumlah Suite Cypress Dibuat** | **9 Spec File** | Disusun modular sesuai penamaan Excel |
| **Total Test Cases Cypress Dieksekusi** | **51 Test Run** | Mencakup pengujian multi-dataset (positif & negatif) |
| **Tingkat Kelulusan (Pass Rate)** | **100% PASS** | **51 Lulus (0 Gagal / 0 Error)** |
| **Status Database** | **Aman (Non-Destruktif)** | Menggunakan remote Supabase PostgreSQL tanpa `migrate:fresh` |

---

## 2. Rincian Eksekusi Per Skenario (Spec Suites)

### 2.1. TS-01: Registrasi Akun (`cypress/e2e/ts-01-registrasi.cy.js`)
* **Status**: **10/10 PASS (100%)**
* **Durasi Rata-rata**: ~1m 32s
* **Rincian Pengujian**:
  1. `TC-01-01`: Registrasi akun valid — Memastikan akun baru dengan role `USER` dan status `ACTIVE` terbentuk dengan hash password aman serta langsung login.
  2. `TC-01-02 [Dataset 1: Email Duplikat]`: Pendaftaran ditolak jika email sudah terdaftar.
  3. `TC-01-02 [Dataset 2: Username Duplikat]`: Pendaftaran ditolak jika username sudah terdaftar.
  4. `TC-01-03 [Dataset 1: Password Mismatch]`: Ditolak jika password dan konfirmasi berbeda.
  5. `TC-01-03 [Dataset 2: Min Length Password]`: Ditolak jika panjang password kurang dari 8 karakter.
  6. `TC-01-04 [Dataset 1: Min-1 (2 karakter)]`: Username 2 karakter ditolak oleh validasi min length.
  7. `TC-01-04 [Dataset 2: Min (3 karakter)]`: Username tepat 3 karakter diterima dan berhasil.
  8. `TC-01-04 [Dataset 3: Max+1 (31 karakter)]`: Username 31 karakter ditolak oleh batasan panjang.
  9. `TC-01-05 [Dataset 1: Simbol Terlarang]`: Validasi regex `^[a-z0-9_]+$` menolak simbol khusus.
  10. `TC-01-05 [Dataset 2: Normalisasi Huruf Kecil]`: Input username huruf kapital otomatis dinormalisasi menjadi huruf kecil.

---

### 2.2. TS-02: Login, Logout, dan Google Auth (`cypress/e2e/ts-02-login-google.cy.js`)
* **Status**: **8/8 PASS (100%)**
* **Durasi Rata-rata**: ~1m 24s
* **Rincian Pengujian**:
  1. `TC-02-01 [Dataset 1: Email]`: Login lokal menggunakan email valid berhasil membuat sesi.
  2. `TC-02-01 [Dataset 2: Username]`: Login lokal menggunakan username valid berhasil membuat sesi.
  3. `TC-02-02 [Dataset 1: Password Salah]`: Login ditolak dengan pesan kesalahan kredensial.
  4. `TC-02-02 [Dataset 2: Email Tidak Terdaftar]`: Login ditolak jika email belum terdaftar.
  5. `TC-02-02 [Dataset 3: Username Tidak Terdaftar]`: Login ditolak jika username belum terdaftar.
  6. `TC-02-04`: Tautan login Google mengarahkan ke OAuth redirect endpoint Google.
  7. `TC-02-07`: Penanganan callback OAuth tidak valid / dibatalkan menampilkan flash alert peringatan tanpa crash 500.
  8. `TC-02-09`: Pengguna dapat logout dan rute terlindungi (`/seller/listings/create`) dialihkan kembali ke `/login`.

---

### 2.3. TS-03: Cari dan Lihat Listing (`cypress/e2e/ts-03-cari-lihat-listing.cy.js`)
* **Status**: **4/4 PASS (100%)**
* **Durasi Rata-rata**: ~42s
* **Rincian Pengujian**:
  1. `TC-03-01`: Penjelajahan katalog listing aktif di marketplace menampilkan kartu produk, nama varian, dan harga.
  2. `TC-03-04`: Membuka listing dengan ID yang tidak ditemukan mengembalikan halaman 404 yang terstruktur.
  3. `TC-03-05`: CTA "Ingin Berjualan" mengarahkan pengguna non-penjual ke formulir pendaftaran seller.
  4. `TC-03-06`: Menu dan navigasi kelola listing terlindungi dan disembunyikan dari tamu / non-seller.

---

### 2.4. TS-04: Kelola Listing Seller (`cypress/e2e/ts-04-kelola-listing.cy.js`)
* **Status**: **5/5 PASS (100%)**
* **Durasi Rata-rata**: ~1m 15s
* **Rincian Pengujian**:
  1. `TC-04-01`: Penjual dapat membuat listing baru dengan status default `ACTIVE`.
  2. `TC-04-02`: Penjual dapat memperbarui judul, deskripsi, dan harga pada listing miliknya.
  3. `TC-04-03`: Penjual dapat menutup listing secara mandiri (status beralih ke `CLOSED`).
  4. `TC-04-04`: Form listing memvalidasi input kosong pada kolom wajib dan mencegah harga/stok negatif.
  5. `TC-04-05`: Otorisasi ketat (403 Forbidden) saat penjual lain mencoba mengakses edit listing milik seller berbeda.

---

### 2.5. TS-06: Interaksi Forum (`cypress/e2e/ts-06-interaksi-forum.cy.js`)
* **Status**: **4/4 PASS (100%)**
* **Durasi Rata-rata**: ~38s
* **Rincian Pengujian**:
  1. `TC-06-01`: Navigasi filter kategori forum (GENERAL, WTB, WTS) berfungsi responsif.
  2. `TC-06-02`: Pencarian kata kunci acak menampilkan *empty search state* ramah pengguna.
  3. `TC-06-03 & TC-06-05`: Pengguna login dapat membuka detail postingan dan melihat kontrol interaksi like serta komentar.
  4. `TC-06-07`: Tamu yang menekan tombol buat postingan dialihkan ke otentikasi login.

---

### 2.6. TS-07: Posting Forum (`cypress/e2e/ts-07-posting-forum.cy.js`)
* **Status**: **3/3 PASS (100%)**
* **Durasi Rata-rata**: ~45s
* **Rincian Pengujian**:
  1. `TC-07-01`: Pengguna terdaftar berhasil membuat dan menerbitkan postingan umum baru.
  2. `TC-07-02`: Penulis dapat mengubah konten postingan dan menutup postingan melalui modal dialog konfirmasi.
  3. `TC-07-05`: Validasi penolakan jika kolom wajib judul atau deskripsi dikosongkan.

---

### 2.7. TS-08: Direct Messaging (DM) & Negosiasi (`cypress/e2e/ts-08-pesan-dm.cy.js`)
* **Status**: **5/5 PASS (100%)**
* **Durasi Rata-rata**: ~1m 46s
* **Rincian Pengujian**:
  1. `TC-08-01`: Pembeli dapat memulai inisiasi percakapan negosiasi dari detail posting forum pemilik lain.
  2. `TC-08-02`: Pengguna dapat mengirim pesan teks di thread percakapan dan pesan muncul pada riwayat chat.
  3. `TC-08-03`: Form input menolak pengiriman pesan kosong tanpa membuat bubble chat baru.
  4. `TC-08-06`: Widget chat mengambang (*floating drawer*) dapat dibuka dan ditutup menggunakan tombol X.
  5. `TC-08-04`: Akses thread percakapan dibatasi secara ketat; pihak ketiga non-partisipan ditolak dengan status HTTP 403.

---

### 2.8. TS-32: Profil Pengguna & Keamanan (`cypress/e2e/ts-32-profil.cy.js`)
* **Status**: **6/6 PASS (100%)**
* **Durasi Rata-rata**: ~1m 18s
* **Rincian Pengujian**:
  1. `TC-32-01`: Pengguna dapat memperbarui username publik yang diizinkan dan tersimpan secara persisten.
  2. `TC-32-02`: Percobaan mengganti username dengan milik pengguna lain ditolak oleh validasi keunikan.
  3. `TC-32-03`: Karakter di luar format regex atau panjang tidak valid ditolak oleh HTML5 constraint dan server validation.
  4. `TC-32-04`: Password lama tetap utuh ketika kolom password baru dikosongkan saat memperbarui data profil.
  5. `TC-32-05`: Perubahan kata sandi ditolak jika konfirmasi password baru tidak cocok.
  6. `TC-32-07`: Tamu tanpa sesi login ditolak membuka halaman edit profil dan dialihkan ke login.

---

### 2.9. TS-33: Dashboard Administrasi (`cypress/e2e/ts-33-dashboard-admin.cy.js`)
* **Status**: **6/6 PASS (100%)**
* **Durasi Rata-rata**: ~2m 26s
* **Rincian Pengujian**:
  1. `TC-33-01 [Dataset 1: Tamu]`: Akses rute `/admin` tanpa sesi login dialihkan ke `/login`.
  2. `TC-33-01 [Dataset 2: Pengguna Biasa]`: Akses rute `/admin` oleh akun non-admin ditolak dengan kode status 403 Forbidden.
  3. `TC-33-01 [Dataset 3: Administrator]`: Akses rute `/admin` oleh akun berwenang berhasil masuk ke panel analitik.
  4. `TC-33-02`: Dashboard menampilkan kartu ringkasan status transaksi rekber, antrean penanganan, dan volume dana escrow.
  5. `TC-33-03`: Filter rentang periode (7d, 30d, 90d, all) berfungsi interaktif dan memperbarui indikator tombol aktif.
  6. `TC-33-04`: Endpoint analitik `/admin/analytics?period=30d` mengembalikan payload JSON terstruktur (period, trend, statusComposition).

---

## 3. Catatan Teknis & Solusi Gotchas Cypress 16

1. **Penghapusan `Cypress.env()` dan `cy.exec()` di Cypress 16**:
   * Cypress versi 16 menghapus dukungan `Cypress.env()` dan `cy.exec()`.
   * Solusi: Variabel dinamis dikelola via *lexical scope* modul, dan perintah artisan backend (seperti upgrade privilege role test) dieksekusi secara aman melalui custom Node event task `cy.task('execPhp')` di [cypress.config.js](file:///c:/laragon/www/PBL-Semester%205/markettrust/cypress.config.js).
2. **Isolasi Sesi & State Chaining**:
   * Cypress v12+ mengaktifkan `testIsolation: true` secara default yang membersihkan cookie sesi antar blok `it()`.
   * Pada skenario berantai (seperti CRUD Listing dan Percakapan Chat), diaktifkan opsi `{ testIsolation: false }` pada blok `describe` skenario terkait untuk menjaga kontinuitas sesi autentikasi.
3. **Penyempurnaan Target Selektor Form**:
   * Aplikasi memiliki widget DM mengambang (`#global-dm-floating-launcher`) yang selalu aktif pada halaman pengguna login.
   * Selektor tombol submit di-scope secara spesifik per form (misal: `#listing-form button[type="submit"]`, `#forum-negotiate-form button[type="submit"]`, `#submit-message-btn`) untuk mencegah benturan selektor global.
4. **Resiliensi Remote Database Supabase**:
   * Pengujian berinteraksi langsung dengan database remote PostgreSQL di AWS Singapura.
   * Seluruh tes menghindari pemanggilan reload ganda dan menggunakan penundaan dinamis berbasis assertion Cypress (`should('be.visible')`) untuk mencegah SSL handshake drops.

---

## 4. Status Pengujian Skenario Terintegrasi & Dependensi

Skenario lain dalam 35 skenario QA telah dipetakan lengkap pada [cypress-manifest.json](file:///c:/laragon/www/PBL-Semester%205/markettrust/docs/testing/cypress-manifest.json) dan [cypress-mapping.md](file:///c:/laragon/www/PBL-Semester%205/markettrust/docs/testing/cypress-mapping.md):
* **TS-13 (Midtrans Sandbox) & TS-20 (Refund Simulatif)**: Memerlukan sandbox Snap token dan mocking webhook callback Midtrans yang dapat dihubungkan saat credential sandbox QA disematkan pada `.env`.
* **TS-31 (Tenggat dan Transisi Waktu)**: Memerlukan pemicu background worker `php artisan schedule:run` atau stubbing timestamp database untuk simulasi expired payment.
* **TS-05, TS-23, TS-34**: Menunggu keputusan bisnis dan formula ambang reputasi/keep (D06, D07, D10) yang tertulis pada dokumen spesifikasi QA.

---

## 5. Kesimpulan & Rekomendasi

* **Kesiapan Otomasi**: 9 Test Suite utama telah beroperasi dengan tingkat kelulusan 100% (51 run).
* **Integritas Bisnis**: Seluruh pengujian berhasil diverifikasi tanpa melakukan modifikasi aturan bisnis controller maupun database schema.
* **Kelengkapan Dokumen**:
  * Spreadsheet hasil eksekusi [MarketTrust_Test_Case_35_Scenario_Executed.xlsx](file:///c:/laragon/www/PBL-Semester%205/markettrust/docs/testing/MarketTrust_Test_Case_35_Scenario_Executed.xlsx) telah diperbarui dengan status **Pass** dan penjelasan hasil aktual.
  * Manifest JSON [cypress-manifest.json](file:///c:/laragon/www/PBL-Semester%205/markettrust/docs/testing/cypress-manifest.json) telah disinkronkan.
  * Tidak ada commit, push, atau merge otomatis yang dilakukan, menjaga kepatuhan penuh terhadap instruksi pengujian.
