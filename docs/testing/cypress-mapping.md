# Pemetaan Komprehensif Cypress Testing — MarketTrust (35 TS / 208 TC)

> Dokumen ini memetakan seluruh **35 Test Scenario (TS)** dan **208 Test Case (TC)** dari dokumen resmi spesifikasi pengujian MarketTrust Revisi 0.2 ([MarketTrust_Test_Case_35_Scenario.xlsx](file:///c:/laragon/www/PBL-Semester%205/markettrust/docs/testing/MarketTrust_Test_Case_35_Scenario.xlsx)).
> Setiap Test Case terdaftar lengkap tanpa ada yang dihilangkan, mencakup rute terkait, lokasi implementasi dalam repositori, ketersediaan fitur, metode pengujian, prasyarat, aktor, spec file rencana, serta assertion spesifik.

---

## Ringkasan Kesiapan 35 Test Scenario

| ID TS | Nama Skenario | Total TC | Status Ketersediaan Fitur | Metode Pengujian Utama | Spec File Rencana |
| :--- | :--- | :---: | :--- | :--- | :--- |
| **TS-01** | Registrasi akun | 5 | tersedia | Cypress E2E | `cypress/e2e/ts-01-registrasi.cy.js` |
| **TS-02** | Login logout dan Google | 10 | tersedia (Lokal), sebagian (Google OAuth butuh mock) | Cypress E2E & Backend Test | `cypress/e2e/ts-02-login-google.cy.js` |
| **TS-03** | Cari dan lihat listing | 6 | tersedia | Cypress E2E | `cypress/e2e/ts-03-cari-lihat-listing.cy.js` |
| **TS-04** | Kelola listing | 5 | tersedia | Cypress E2E | `cypress/e2e/ts-04-kelola-listing.cy.js` |
| **TS-05** | Informasi pasar produk | 6 | sebagian (Menunggu keputusan bisnis D10) | Cypress E2E / Backend test | `cypress/e2e/ts-05-informasi-pasar.cy.js` |
| **TS-06** | Interaksi forum | 7 | tersedia | Cypress E2E | `cypress/e2e/ts-06-interaksi-forum.cy.js` |
| **TS-07** | Kelola posting forum | 8 | tersedia | Cypress E2E | `cypress/e2e/ts-07-posting-forum.cy.js` |
| **TS-08** | Pesan melalui DM | 9 | tersedia | Cypress E2E | `cypress/e2e/ts-08-pesan-dm.cy.js` |
| **TS-09** | Checkout marketplace | 6 | sebagian (Form checkout terhubung ke kesepakatan final) | Cypress E2E | `cypress/e2e/ts-09-checkout.cy.js` |
| **TS-10** | Draft dan revisi forum | 5 | tersedia | Cypress E2E | `cypress/e2e/ts-10-draft-forum.cy.js` |
| **TS-11** | Persetujuan kesepakatan | 5 | tersedia | Cypress E2E | `cypress/e2e/ts-11-persetujuan.cy.js` |
| **TS-12** | Rincian agreement dan fee | 5 | tersedia | Cypress E2E & Backend test | `cypress/e2e/ts-12-agreement-fee.cy.js` |
| **TS-13** | Midtrans Sandbox | 10 | sebagian (Memerlukan sandbox token & stub callback) | Cypress E2E & Backend integration test | `cypress/e2e/ts-13-midtrans.cy.js` |
| **TS-14** | Penyerahan digital | 5 | tersedia | Cypress E2E | `cypress/e2e/ts-14-penyerahan.cy.js` |
| **TS-15** | Konfirmasi penerimaan | 4 | tersedia | Cypress E2E | `cypress/e2e/ts-15-konfirmasi.cy.js` |
| **TS-16** | Pengajuan komplain | 5 | tersedia | Cypress E2E | `cypress/e2e/ts-16-ajukan-komplain.cy.js` |
| **TS-17** | Tanggapan komplain | 4 | tersedia | Cypress E2E | `cypress/e2e/ts-17-tanggapan-komplain.cy.js` |
| **TS-18** | Keputusan komplain | 5 | tersedia | Cypress E2E | `cypress/e2e/ts-18-keputusan-komplain.cy.js` |
| **TS-19** | Payout simulatif | 5 | tersedia | Cypress E2E | `cypress/e2e/ts-19-payout.cy.js` |
| **TS-20** | Refund simulatif | 7 | sebagian (Formula potongan 3x fee Midtrans TBD D06) | Cypress E2E / Backend test | `cypress/e2e/ts-20-refund.cy.js` |
| **TS-21** | Status dan riwayat | 5 | tersedia | Cypress E2E | `cypress/e2e/ts-21-riwayat.cy.js` |
| **TS-22** | Notifikasi | 5 | tersedia | Cypress E2E | `cypress/e2e/ts-22-notifikasi.cy.js` |
| **TS-23** | Kontribusi dan reputasi | 5 | sebagian (Bobot & ambang poin TBD D10) | Cypress E2E / Backend test | `cypress/e2e/ts-23-reputasi.cy.js` |
| **TS-24** | Peninjauan akun | 6 | sebagian (Form pelaporan publik TBD) | Cypress E2E / Manual | `cypress/e2e/ts-24-review-akun.cy.js` |
| **TS-25** | Kategori produk varian | 6 | tersedia | Cypress E2E | `cypress/e2e/ts-25-katalog.cy.js` |
| **TS-26** | Tujuan settlement | 4 | sebagian (QRIS/E-Wallet TBD D10) | Cypress E2E | `cypress/e2e/ts-26-tujuan-settlement.cy.js` |
| **TS-27** | Perhitungan fee melalui service | 9 | tersedia | Cypress E2E & Backend PHPUnit | `cypress/e2e/ts-27-hitung-fee.cy.js` |
| **TS-28** | Keamanan dan privasi | 6 | tersedia | Cypress E2E & Security scan | `cypress/e2e/ts-28-keamanan.cy.js` |
| **TS-29** | Tampilan dan kompatibilitas | 3 | tersedia | Cypress E2E UI | `cypress/e2e/ts-29-tampilan.cy.js` |
| **TS-30** | End to end | 4 | tersedia | Cypress E2E Flow | `cypress/e2e/ts-30-end-to-end.cy.js` |
| **TS-31** | Tenggat dan transisi | 11 | sebagian (Scheduler background job) | Backend PHPUnit / Pest & Cypress | `cypress/e2e/ts-31-tenggat.cy.js` |
| **TS-32** | Profil pengguna | 7 | tersedia | Cypress E2E | `cypress/e2e/ts-32-profil.cy.js` |
| **TS-33** | Dashboard admin | 4 | tersedia | Cypress E2E | `cypress/e2e/ts-33-dashboard-admin.cy.js` |
| **TS-34** | Payout gagal dan keep | 6 | sebagian (Aturan pembukuan keep TBD D07) | Backend Test & Cypress E2E | `cypress/e2e/ts-34-keep.cy.js` |
| **TS-35** | Pembatalan transaksi | 5 | tersedia (Pre-payment), sebagian (Post-payment TBD D08) | Cypress E2E | `cypress/e2e/ts-35-pembatalan.cy.js` |

---

## Rincian Pemetaan Seluruh 208 Test Case

### TS-01 — Registrasi akun (5 Test Cases)

- **Fitur Terkait**: Autentikasi Registrasi Lokal
- **Route/Endpoint**: `GET /register (register)`, `POST /register`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Auth/AuthController.php@register`, `app/Http/Requests/Auth/RegisterRequest.php`, `resources/views/auth/register.blade.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-01-registrasi.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-01-01** | Registrasi valid Prioritas: P2 Desain: TBD | Pengguna belum login; formulir registrasi tersedia; email dan username uji disiapkan. | 1. Isi username/email unik.<br>2. Isi password dan konfirmasi sesuai kebijakan.<br>3. Kirim formulir. | Satu akun USER ACTIVE dibuat; password berupa hash; hasil registrasi ditampilkan. | `TBD` |
| **TC-01-02** | Identitas duplikat Prioritas: P2 Desain: Review | Pengguna belum login; formulir registrasi tersedia; email dan username uji disiapkan. | 1. Gunakan email terdaftar.<br>2. Ulangi dengan username terdaftar. | Ditolak dengan pesan jelas; tidak ada akun duplikat. | `Review` |
| **TC-01-03** | Kelengkapan dan format Prioritas: P2 Desain: TBD | Pengguna belum login; formulir registrasi tersedia; email dan username uji disiapkan. | 1. Kosongkan setiap kolom wajib bergantian.<br>2. Uji email tidak valid.<br>3. Uji konfirmasi password berbeda. | Input ditolak pada kolom terkait; akun tidak dibuat. | `TBD` |
| **TC-01-04** | Batas username Prioritas: P2 Desain: TBD | Pengguna belum login; formulir registrasi tersedia; email dan username uji disiapkan. | 1. Daftarkan username sepanjang 2, 3, 30, dan 31 karakter dengan identitas lain valid. | 3 dan 30 diterima; 2 dan 31 ditolak. | `TBD` |
| **TC-01-05** | Pola dan normalisasi Prioritas: P2 Desain: TBD | Pengguna belum login; formulir registrasi tersedia; email dan username uji disiapkan. | 1. Uji huruf besar.<br>2. Uji spasi/simbol selain underscore.<br>3. Uji variasi kapital username terdaftar. | Huruf dinormalisasi ke huruf kecil; pola tidak sah ditolak; tidak ada duplikasi setelah normalisasi. | `TBD` |

### TS-02 — Login logout dan Google (10 Test Cases)

- **Fitur Terkait**: Autentikasi Login Lokal, Logout & Google OAuth
- **Route/Endpoint**: `GET /login (login)`, `POST /login`, `POST /logout (logout)`, `GET /auth/google`, `GET /auth/google/callback`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Auth/AuthController.php`, `app/Http/Controllers/Auth/GoogleAuthController.php`, `resources/views/auth/login.blade.php`
- **Ketersediaan Fitur**: tersedia (Lokal), sebagian (Google OAuth butuh mock)
- **Metode Pengujian**: Cypress E2E & Backend Test
- **File Spec Rencana**: `cypress/e2e/ts-02-login-google.cy.js`
- **Catatan Kendala / Keputusan TBD**: Google OAuth callback perlu simulasi lingkungan uji

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-02-01** | Login lokal valid Prioritas: P1 Desain: Review | Akun lokal aktif, Google, suspended, dan akun lokal belum terhubung tersedia. | 1. Login sebagai USER dan ADMIN dengan kredensial benar. | Sesi dibuat; hak akses mengikuti account type. | `Review` |
| **TC-02-02** | Login tidak valid Prioritas: P1 Desain: Review | Akun lokal aktif, Google, suspended, dan akun lokal belum terhubung tersedia. | 1. Uji password salah, email tidak terdaftar, dan kolom kosong. | Login ditolak; sesi autentikasi tidak dibuat. | `Review` |
| **TC-02-03** | Akun suspended Prioritas: P1 Desain: Review | Akun lokal aktif, Google, suspended, dan akun lokal belum terhubung tersedia. | 1. Coba login lokal dan Google pada akun suspended. | Kedua metode menolak akses akun suspended. | `Review` |
| **TC-02-04** | Google pertama kali Prioritas: P1 Desain: TBD | Akun lokal aktif, Google, suspended, dan akun lokal belum terhubung tersedia. | 1. Autentikasi Google valid.<br>2. Pilih username unik.<br>3. Selesaikan pendaftaran. | Email/avatar tersedia digunakan; google_id tersimpan; password lokal tidak diwajibkan. | `TBD` |
| **TC-02-05** | Username dan avatar Google Prioritas: P1 Desain: TBD | Akun lokal aktif, Google, suspended, dan akun lokal belum terhubung tersedia. | 1. Uji username duplikat/tidak valid.<br>2. Uji respons profil tanpa avatar. | Pendaftaran menunggu username valid; avatar kosong tidak menyebabkan kegagalan. | `TBD` |
| **TC-02-06** | Google berikutnya Prioritas: P1 Desain: Review | Akun lokal aktif, Google, suspended, dan akun lokal belum terhubung tersedia. | 1. Ubah profil pengguna lalu login ulang dengan google_id sama. | Akun sama digunakan; tidak ada akun baru; profil tidak otomatis ditimpa. | `Review` |
| **TC-02-07** | Autentikasi Google gagal Prioritas: P1 Desain: Review | Akun lokal aktif, Google, suspended, dan akun lokal belum terhubung tersedia. | 1. Batalkan consent<br>2. uji callback gagal dan state tidak valid di lingkungan uji. | Tidak ada sesi atau akun baru dari autentikasi gagal/tidak valid. | `Review` |
| **TC-02-08** | Hubungkan akun Google Prioritas: P1 Desain: TBD | Akun lokal aktif, Google, suspended, dan akun lokal belum terhubung tersedia. | 1. Gunakan Google dengan email akun lokal.<br>2. Coba tanpa pembuktian kepemilikan.<br>3. Ulangi setelah verifikasi sah. | Email sama tidak cukup untuk menggabungkan akun; penghubungan berhasil setelah kepemilikan terbukti. | `TBD` |
| **TC-02-09** | Logout Prioritas: P1 Desain: Review | Akun lokal aktif, Google, suspended, dan akun lokal belum terhubung tersedia. | 1. Login.<br>2. Logout.<br>3. Buka URL privat dan kirim tindakan privat. | Sesi berakhir; sesi lama tidak dapat mengakses data/tindakan privat. | `Review` |
| **TC-02-10** | Sesi kedaluwarsa Prioritas: P1 Desain: Review | Akun lokal aktif, Google, suspended, dan akun lokal belum terhubung tersedia. | 1. Buka halaman dan kirim perubahan setelah sesi berakhir. | Diminta login kembali; perubahan tidak dijalankan sebagai pengguna lama. | `Review` |

### TS-03 — Cari dan lihat listing (6 Test Cases)

- **Fitur Terkait**: Katalog Marketplace & Pencarian Listing
- **Route/Endpoint**: `GET / (home)`, `GET /seller/listings`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/HomeController.php`, `resources/views/home.blade.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-03-cari-lihat-listing.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-03-01** | Daftar dan detail Prioritas: P2 Desain: Review | Listing aktif/nonaktif dan beberapa produk/varian tersedia. | 1. Buka marketplace lalu detail listing aktif. | Harga, deskripsi, Seller, varian, estimasi/metode penyerahan sesuai data. | `Review` |
| **TC-03-02** | Pencarian cocok Prioritas: P2 Desain: Review | Listing aktif/nonaktif dan beberapa produk/varian tersedia. | 1. Cari kata kunci yang sesuai listing uji. | Hasil relevan ditampilkan sesuai aturan pencarian. | `Review` |
| **TC-03-03** | Pencarian kosong Prioritas: P2 Desain: Review | Listing aktif/nonaktif dan beberapa produk/varian tersedia. | 1. Cari kata kunci yang tidak tersedia. | Keadaan kosong ditampilkan tanpa error. | `Review` |
| **TC-03-04** | Listing nonaktif atau tidak ada Prioritas: P2 Desain: Review | Listing aktif/nonaktif dan beberapa produk/varian tersedia. | 1. Buka URL listing nonaktif/ID tidak ada<br>2. coba mulai transaksi. | Tidak dapat memulai transaksi baru; tidak ada kebocoran data atau error internal. | `Review` |
| **TC-03-05** | Akses ingin berjualan Prioritas: P2 Desain: Review | Listing aktif/nonaktif dan beberapa produk/varian tersedia. | 1. Login akun tanpa listing<br>2. buka Marketplace<br>3. tekan ingin berjualan. | Aksi menuju form listing. Tombol mulai berjualan lama tidak muncul pada lokasi yang sudah diganti. | `Review` |
| **TC-03-06** | Menu listing setelah produk pertama Prioritas: P2 Desain: Review | Listing aktif/nonaktif dan beberapa produk/varian tersedia. | 1. Pada A tanpa listing, catat navbar<br>2. buat satu listing valid lalu refresh/login ulang. | Menu listing belum muncul sebelum produk pertama dan muncul setelah minimal satu produk dibuat. Perilaku setelah seluruh listing dihapus menunggu D12. | `Review` |

### TS-04 — Kelola listing (5 Test Cases)

- **Fitur Terkait**: Manajemen Listing Seller (CRUD)
- **Route/Endpoint**: `GET /seller/listings`, `GET /seller/listings/create`, `POST /seller/listings`, `GET /seller/listings/{listing}/edit`, `PUT /seller/listings/{listing}`, `PATCH /seller/listings/{listing}/toggle-status`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Seller/ListingController.php`, `app/Models/Listing.php`, `resources/views/seller/listings/`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-04-kelola-listing.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-04-01** | Buat listing valid Prioritas: P2 Desain: Review | Seller A dan B aktif; katalog tersedia. | 1. Isi produk/varian, judul, deskripsi, harga, estimasi penyerahan<br>2. simpan. | Listing tersimpan atas pemilik benar; dapat ditemukan saat aktif. | `Review` |
| **TC-04-02** | Ubah listing sendiri Prioritas: P2 Desain: Review | Seller A dan B aktif; katalog tersedia. | 1. Ubah harga, rincian, estimasi listing sendiri. | Perubahan tersimpan; agreement final lama tetap sama. | `Review` |
| **TC-04-03** | Nonaktifkan listing Prioritas: P2 Desain: Review | Seller A dan B aktif; katalog tersedia. | 1. Nonaktifkan listing lalu coba checkout baru. | Transaksi baru ditolak; transaksi sebelumnya tetap dapat ditelusuri. | `Review` |
| **TC-04-04** | Validasi listing Prioritas: P2 Desain: Review | Seller A dan B aktif; katalog tersedia. | 1. Uji kolom wajib kosong, harga nol/negatif, varian tidak sah, durasi nol/negatif. | Data tidak valid ditolak; tidak ada penyimpanan sebagian. | `Review` |
| **TC-04-05** | Listing pihak lain Prioritas: P2 Desain: Review | Seller A dan B aktif; katalog tersedia. | 1. Seller B mencoba ubah/nonaktifkan listing A melalui UI/request langsung. | Ditolak; listing tetap sama. | `Review` |

### TS-05 — Informasi pasar produk (6 Test Cases)

- **Fitur Terkait**: Indikator Pasar Produk (Demand, Supply, Rata-rata Harga)
- **Route/Endpoint**: `GET / (home)`, `Katalog Produk`
- **Lokasi Implementasi Repositori**: `app/Models/ProductVariant.php`, `resources/views/`
- **Ketersediaan Fitur**: sebagian (Menunggu keputusan bisnis D10)
- **Metode Pengujian**: Cypress E2E / Backend test
- **File Spec Rencana**: `cypress/e2e/ts-05-informasi-pasar.cy.js`
- **Catatan Kendala / Keputusan TBD**: Aturan agregasi periode & batas minimum data masih TBD (D10)

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-05-01** | Demand Prioritas: P2 Desain: TBD | Dataset WTB/WTS, listing, transaksi lintas varian/status tersedia. Periode dan minimum data TBD. | 1. Siapkan 3 WTB aktif dan 1 nonaktif pada varian sama<br>2. buka indikator. | Jika definisi usulan disahkan, demand = 3 dalam periode berlaku; sumber/periode dijelaskan. | `TBD` |
| **TC-05-02** | Supply Prioritas: P2 Desain: TBD | Dataset WTB/WTS, listing, transaksi lintas varian/status tersedia. Periode dan minimum data TBD. | 1. Siapkan 2 listing aktif, 1 WTS aktif, serta penawaran nonaktif. | Jika definisi usulan disahkan, supply = 3; bukan jumlah stok nyata. | `TBD` |
| **TC-05-03** | Rata rata harga Prioritas: P2 Desain: TBD | Dataset WTB/WTS, listing, transaksi lintas varian/status tersedia. Periode dan minimum data TBD. | 1. Siapkan transaksi selesai Rp100.000 dan Rp200.000 pada varian sama. | Jika memenuhi periode/minimum data, rata-rata Rp150.000; jumlah data 2; tanpa fee. | `TBD` |
| **TC-05-04** | Pemisahan varian Prioritas: P2 Desain: TBD | Dataset WTB/WTS, listing, transaksi lintas varian/status tersedia. Periode dan minimum data TBD. | 1. Tambahkan produk mirip/varian lain dengan harga berbeda. | Varian lain tidak masuk perhitungan varian yang dibuka. | `TBD` |
| **TC-05-05** | Filter status dan periode Prioritas: P2 Desain: TBD | Dataset WTB/WTS, listing, transaksi lintas varian/status tersedia. Periode dan minimum data TBD. | 1. Tambahkan cancelled, refunded, belum selesai, dan transaksi di luar periode. | Data tidak memenuhi syarat tidak masuk rata-rata. | `TBD` |
| **TC-05-06** | Tanpa data harga Prioritas: P2 Desain: TBD | Dataset WTB/WTS, listing, transaksi lintas varian/status tersedia. Periode dan minimum data TBD. | 1. Buka varian tanpa transaksi layak. | Tampil “Data harga belum tersedia”; tidak ada rata-rata palsu atau error. | `TBD` |

### TS-06 — Interaksi forum (7 Test Cases)

- **Fitur Terkait**: Forum Komunitas: Kategori, Komentar & Like
- **Route/Endpoint**: `GET /forum (forum.index)`, `GET /forum/{post} (forum.show)`, `POST /forum/{post}/comments`, `DELETE /forum/comments/{comment}`, `POST /forum/{post}/like`, `POST /forum/comments/{comment}/like`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Forum/ForumPostController.php`, `app/Http/Controllers/Forum/ForumCommentController.php`, `app/Http/Controllers/Forum/ForumLikeController.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-06-interaksi-forum.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-06-01** | Kategori dan detail Prioritas: P2 Desain: Review | Posting Umum/WTB/WTS dan komentar tersedia; dua pengguna aktif. | 1. Buka setiap kategori, cari posting, baca detail. | Kategori, isi, penulis, foto sesuai posting. | `Review` |
| **TC-06-02** | Forum kosong Prioritas: P2 Desain: Review | Posting Umum/WTB/WTS dan komentar tersedia; dua pengguna aktif. | 1. Cari posting yang tidak ada/buka kategori kosong. | Keadaan kosong jelas ditampilkan. | `Review` |
| **TC-06-03** | Komentar valid Prioritas: P2 Desain: Review | Posting Umum/WTB/WTS dan komentar tersedia; dua pengguna aktif. | 1. Kirim komentar teks pada posting yang dapat diakses. | Komentar tersimpan pada posting dan penulis tepat. | `Review` |
| **TC-06-04** | Komentar kosong Prioritas: P2 Desain: Review | Posting Umum/WTB/WTS dan komentar tersedia; dua pengguna aktif. | 1. Kirim komentar kosong/hanya spasi. | Ditolak; tidak ada record kosong. | `Review` |
| **TC-06-05** | Like posting Prioritas: P2 Desain: Review | Posting Umum/WTB/WTS dan komentar tersedia; dua pengguna aktif. | 1. Like posting<br>2. ulangi request like<br>3. lalu unlike. | Maksimal satu like per pengguna/posting; jumlah akurat; unlike menghapus like miliknya. | `Review` |
| **TC-06-06** | Like komentar Prioritas: P2 Desain: Review | Posting Umum/WTB/WTS dan komentar tersedia; dua pengguna aktif. | 1. Like komentar<br>2. ulangi request<br>3. lalu unlike. | Maksimal satu like per pengguna/komentar; tidak memengaruhi like posting. | `Review` |
| **TC-06-07** | Interaksi tanpa sesi Prioritas: P2 Desain: Review | Posting Umum/WTB/WTS dan komentar tersedia; dua pengguna aktif. | 1. Kirim komentar/like tanpa login. | Ditolak; data tidak berubah. | `Review` |

### TS-07 — Kelola posting forum (8 Test Cases)

- **Fitur Terkait**: Pembuatan & Pengelolaan Posting Forum (Umum, WTB, WTS)
- **Route/Endpoint**: `GET /forum/create`, `POST /forum`, `GET /forum/{post}/edit`, `PUT /forum/{post}`, `POST /forum/{post}/close`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Forum/ForumPostController.php`, `app/Models/ForumPost.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-07-posting-forum.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-07-01** | Posting semua kategori Prioritas: P2 Desain: Review | Pengguna A/B aktif; produk/varian relevan tersedia. | 1. Buat posting Umum, WTB, WTS dengan kolom yang sesuai. | Kategori/pemilik benar; relasi produk mengikuti kategori. | `Review` |
| **TC-07-02** | Ubah dan nonaktifkan Prioritas: P2 Desain: Review | Pengguna A/B aktif; produk/varian relevan tersedia. | 1. Ubah posting sendiri lalu nonaktifkan. | Perubahan berlaku; tampilan/statistik mengikuti status; snapshot lama tetap. | `Review` |
| **TC-07-03** | Batas foto Prioritas: P2 Desain: TBD | Pengguna A/B aktif; produk/varian relevan tersedia. | 1. Buat posting dengan 0, 1, 4, dan 5 foto. | 0–4 diterima; 5 ditolak; foto pertama menjadi cover jika ada. | `TBD` |
| **TC-07-04** | Mengganti foto Prioritas: P2 Desain: TBD | Pengguna A/B aktif; produk/varian relevan tersedia. | 1. Pada posting 4 foto, hapus satu/tambah satu<br>2. coba tambah tanpa hapus. | Total maksimal 4; foto kelima ditolak. | `TBD` |
| **TC-07-05** | Kolom dan relasi salah Prioritas: P2 Desain: Review | Pengguna A/B aktif; produk/varian relevan tersedia. | 1. Kosongkan judul/isi/kategori wajib<br>2. gunakan varian tidak sah. | Penyimpanan ditolak dengan pesan sesuai. | `Review` |
| **TC-07-06** | Posting pihak lain Prioritas: P2 Desain: Review | Pengguna A/B aktif; produk/varian relevan tersedia. | 1. B mencoba ubah/nonaktifkan posting A lewat UI/request. | Ditolak; posting tidak berubah. | `Review` |
| **TC-07-07** | Form Forum Umum Prioritas: P2 Desain: Review | Pengguna A/B aktif; produk/varian relevan tersedia. | 1. Pilih Umum<br>2. isi judul/isi diskusi yang valid<br>3. simpan tanpa memilih katalog atau harga transaksi. | Diskusi berhasil dibuat; kolom transaksi tidak diwajibkan untuk Umum; tidak terbentuk transaksi escrow. | `Review` |
| **TC-07-08** | Perpindahan kategori Prioritas: P2 Desain: Review | Pengguna A/B aktif; produk/varian relevan tersedia. | 1. Mulai dari Umum lalu pilih WTB dan WTS pada percobaan berbeda<br>2. submit data sesuai tiap kategori. | Field dan validasi mengikuti kategori yang dipilih; perubahan form Umum tidak merusak WTB/WTS. | `Review` |

### TS-08 — Pesan melalui DM (9 Test Cases)

- **Fitur Terkait**: Direct Messaging Berbasis Konteks Listing/Forum (Scope Zinky)
- **Route/Endpoint**: `GET /conversations`, `POST /conversations/listing/{listing}`, `POST /conversations/post/{post}`, `GET /conversations/{conversation}`, `POST /conversations/{conversation}/messages`, `GET /conversations/{conversation}/sync`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/ConversationController.php`, `app/Models/Conversation.php`, `app/Models/Message.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-08-pesan-dm.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-08-01** | Membuka percakapan Prioritas: P1 Desain: Review | Dua peserta, satu nonpeserta, dan konteks listing/posting tersedia. | 1. Mulai DM dari listing dan posting forum. | Dua peserta berbeda; satu konteks asal yang tepat. | `Review` |
| **TC-08-02** | Kirim baca balas Prioritas: P1 Desain: Review | Dua peserta, satu nonpeserta, dan konteks listing/posting tersedia. | 1. A mengirim<br>2. B membaca dan membalas. | Isi, pengirim, waktu, status baca tersimpan benar. | `Review` |
| **TC-08-03** | Pesan kosong Prioritas: P1 Desain: Review | Dua peserta, satu nonpeserta, dan konteks listing/posting tersedia. | 1. Kirim teks kosong/hanya spasi. | Ditolak tanpa pesan baru. | `Review` |
| **TC-08-04** | Akses nonpeserta Prioritas: P1 Desain: Review | Dua peserta, satu nonpeserta, dan konteks listing/posting tersedia. | 1. C membuka dan mengirim pesan memakai ID percakapan tersebut. | Pembacaan/pengiriman ditolak. | `Review` |
| **TC-08-05** | DM bukan finalisasi Prioritas: P1 Desain: Review | Dua peserta, satu nonpeserta, dan konteks listing/posting tersedia. | 1. Kirim pesan kesepakatan/penyerahan tanpa alur formal. | Pesan tidak otomatis membuat agreement final, transaksi, atau status DELIVERED. | `Review` |
| **TC-08-06** | Daftar chat mengambang Prioritas: P2 Desain: Review | Dua peserta, satu nonpeserta, dan konteks listing/posting tersedia. | 1. Klik ikon kanan bawah lalu pilih salah satu percakapan A-B. | Daftar percakapan terbuka; chat yang dipilih sesuai peserta; tampilan dapat ditutup lewat X tanpa tombol minus yang sudah dihapus. | `Review` |
| **TC-08-07** | Enter dan tombol kirim Prioritas: P2 Desain: Review | Dua peserta, satu nonpeserta, dan konteks listing/posting tersedia. | 1. Kirim teks unik satu kali lewat Enter<br>2. pada dataset lain lewat tombol kirim<br>3. buka ulang chat. | Masing-masing aksi membuat tepat satu pesan; pengirim dan percakapan benar; pesan bertahan setelah dibuka ulang. | `Review` |
| **TC-08-08** | Konteks Forum ke DM Prioritas: P2 Desain: Review | Dua peserta, satu nonpeserta, dan konteks listing/posting tersedia. | 1. Dari posting milik B, A membuka chat dan mengirim teks unik<br>2. periksa konteks percakapan. | Chat menuju B dengan referensi posting yang tepat; tidak mengambil listing/posting milik pengguna lain. | `Review` |
| **TC-08-09** | Ganti chat tanpa salah kirim Prioritas: P1 Desain: Review | Dua peserta, satu nonpeserta, dan konteks listing/posting tersedia. | 1. Buka A-B<br>2. ketik draft<br>3. pindah A-C<br>4. kirim pesan penanda dan buka kedua chat. | Pesan penanda hanya tersimpan pada percakapan tujuan aktif; draft dan histori tidak tercampur antar peserta. | `Review` |

### TS-09 — Checkout marketplace (6 Test Cases)

- **Fitur Terkait**: Checkout & Pembentukan Transaksi Katalog
- **Route/Endpoint**: `POST /fee/preview`, `Checkout / Transaksi Endpoint`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/FeePreviewController.php`, `app/Services/FeeCalculationService.php`
- **Ketersediaan Fitur**: sebagian (Form checkout terhubung ke kesepakatan final)
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-09-checkout.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-09-01** | Checkout valid Prioritas: P1 Desain: Review | Listing aktif; Buyer dan Seller berbeda; payout Seller valid; tabel fee yang disahkan tersedia. | 1. Pilih listing aktif Rp1.000.000.<br>2. Periksa harga dan fee Rp10.000.<br>3. Setujui sekali. | Agreement final dan satu transaksi WAITING_PAYMENT; Buyer total Rp1.010.000 dan Seller net Rp1.000.000 menurut alokasi Marketplace dalam dokumen sumber. Tanpa pilihan EX/INC dan tanpa persetujuan ulang Seller. | `Review` |
| **TC-09-02** | Buyer tidak setuju Prioritas: P1 Desain: Review | Listing aktif; Buyer dan Seller berbeda; payout Seller valid; tabel fee yang disahkan tersedia. | 1. Buka ringkasan lalu batalkan sebelum menyetujui. | Tidak terbentuk transaksi final. | `Review` |
| **TC-09-03** | Listing berubah Prioritas: P1 Desain: Review | Listing aktif; Buyer dan Seller berbeda; payout Seller valid; tabel fee yang disahkan tersedia. | 1. Buka checkout<br>2. Seller ubah/nonaktifkan listing sebelum persetujuan Buyer. | Validasi ulang; minta persetujuan rincian terbaru atau tolak; tidak diam-diam finalkan harga lama. | `Review` |
| **TC-09-04** | Transaksi diri sendiri Prioritas: P1 Desain: Review | Listing aktif; Buyer dan Seller berbeda; payout Seller valid; tabel fee yang disahkan tersedia. | 1. Pemilik listing mencoba membeli listing sendiri. | Ditolak; Buyer dan Seller berbeda. | `Review` |
| **TC-09-05** | Checkout berulang Prioritas: P1 Desain: Review | Listing aktif; Buyer dan Seller berbeda; payout Seller valid; tabel fee yang disahkan tersedia. | 1. Kirim ulang request sama/double-click. | Pengulangan request sama tidak menggandakan transaksi; pembelian baru disengaja dibedakan. | `Review` |
| **TC-09-06** | Payout belum tersedia Prioritas: P1 Desain: Review | Listing aktif; Buyer dan Seller berbeda; payout Seller valid; tabel fee yang disahkan tersedia. | 1. Coba bayar tanpa tujuan payout Seller valid. | Pembayaran belum diizinkan; kekurangan tujuan dijelaskan. | `Review` |

### TS-10 — Draft dan revisi forum (5 Test Cases)

- **Fitur Terkait**: Sheet Kesepakatan Forum & Negosiasi Draft (Scope Zinky)
- **Route/Endpoint**: `POST /forum/{post}/negotiate`, `Sheet Kesepakatan Endpoints`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/ConversationController.php`, `app/Models/AgreementSheet.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-10-draft-forum.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-10-01** | Draft valid Prioritas: P1 Desain: Review | Dua pihak berbeda memiliki konteks forum/DM; sheet belum final. | 1. Isi pihak, item/varian, quantity, harga total, fee type, metode/durasi penyerahan. | Draft revisi awal terbentuk; belum dapat dibayar. | `Review` |
| **TC-10-02** | Draft tidak valid Prioritas: P1 Desain: Review | Dua pihak berbeda memiliki konteks forum/DM; sheet belum final. | 1. Uji pihak sama, wajib kosong, quantity/durasi tidak positif, harga tidak sah. | Ditolak tanpa draft tidak konsisten. | `Review` |
| **TC-10-03** | Revisi draft Prioritas: P1 Desain: Review | Dua pihak berbeda memiliki konteks forum/DM; sheet belum final. | 1. Setelah satu pihak setuju, ubah harga/rincian. | revision_no naik; persetujuan lama dibatalkan; perlu persetujuan revisi baru. | `Review` |
| **TC-10-04** | Revisi final Prioritas: P1 Desain: Review | Dua pihak berbeda memiliki konteks forum/DM; sheet belum final. | 1. Coba ubah agreement final. | Rincian terkunci; snapshot tidak berubah. | `Review` |
| **TC-10-05** | Harga total dan quantity Prioritas: P1 Desain: Review | Dua pihak berbeda memiliki konteks forum/DM; sheet belum final. | 1. Gunakan quantity 2 dan base_price total Rp100.000. | Harga dasar tetap Rp100.000; tidak dikalikan menjadi Rp200.000. | `Review` |

### TS-11 — Persetujuan kesepakatan (5 Test Cases)

- **Fitur Terkait**: Persetujuan Ganda Kesepakatan & Pembentukan Transaksi
- **Route/Endpoint**: `Agreement Approval Endpoints`
- **Lokasi Implementasi Repositori**: `app/Models/AgreementSheet.php`, `app/Models/Transaction.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-11-persetujuan.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-11-01** | Persetujuan kedua pihak Prioritas: P1 Desain: Review | Draft valid tersedia; kedua pihak ditentukan. | 1. Buyer/Seller menyetujui revisi sama<br>2. tujuan payout valid. | Agreement final; satu transaksi terbentuk. | `Review` |
| **TC-11-02** | Satu pihak setuju Prioritas: P1 Desain: Review | Draft valid tersedia; kedua pihak ditentukan. | 1. Hanya Buyer atau Seller menyetujui. | Belum final; belum dapat dibayar. | `Review` |
| **TC-11-03** | Penolakan Prioritas: P1 Desain: Review | Draft valid tersedia; kedua pihak ditentukan. | 1. Salah satu pihak menolak dan mengisi alasan sesuai form. | Status, penolak, waktu/alasan tercatat; transaksi berbayar tidak terbentuk. | `Review` |
| **TC-11-04** | Revisi lama Prioritas: P1 Desain: Review | Draft valid tersedia; kedua pihak ditentukan. | 1. Buka revisi 1<br>2. revisi ke 2<br>3. kirim persetujuan revisi 1. | Persetujuan stale ditolak; revisi 2 tidak dianggap disetujui. | `Review` |
| **TC-11-05** | Ilegal atau berulang Prioritas: P1 Desain: Review | Draft valid tersedia; kedua pihak ditentukan. | 1. Nonpeserta menyetujui<br>2. pihak sah mengirim ulang persetujuan. | Nonpeserta ditolak; request ulang tidak membuat transaksi ganda. | `Review` |

### TS-12 — Rincian agreement dan fee (5 Test Cases)

- **Fitur Terkait**: Rincian Fee Inclusive/Exclusive & Snapshot Locking
- **Route/Endpoint**: `POST /fee/preview`, `Agreement View`
- **Lokasi Implementasi Repositori**: `app/Services/FeeCalculationService.php`, `app/Models/AgreementSheet.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E & Backend test
- **File Spec Rencana**: `cypress/e2e/ts-12-agreement-fee.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-12-01** | Kelengkapan rincian Prioritas: P1 Desain: Review | Draft dan final tersedia. Harga dasar Rp1.000.000; fee Rp10.000 sebagai nilai aturan yang sudah dicatat. | 1. Buka agreement sebagai Buyer/Seller. | Pihak, item, varian, revisi/persetujuan, harga, fee, nominal, metode/durasi sesuai data. | `Review` |
| **TC-12-02** | Marketplace fixed dan Forum EX Prioritas: P1 Desain: Review | Draft dan final tersedia. Harga dasar Rp1.000.000; fee Rp10.000 sebagai nilai aturan yang sudah dicatat. | 1. Gunakan harga dasar Rp1.000.000 dan fee Rp10.000 pada transaksi terpisah. | Keduanya menampilkan Buyer total Rp1.010.000 dan Seller net Rp1.000.000; Marketplace tidak menampilkan pilihan EX/INC. Forum mencatat EX pada snapshot. | `Review` |
| **TC-12-03** | Forum INC Prioritas: P1 Desain: Review | Draft dan final tersedia. Harga dasar Rp1.000.000; fee Rp10.000 sebagai nilai aturan yang sudah dicatat. | 1. Gunakan harga dasar Rp1.000.000 dan fee Rp10.000<br>2. finalkan kesepakatan kedua pihak. | Buyer total Rp1.000.000; Seller net Rp990.000; mode INC tersimpan pada snapshot Forum. | `Review` |
| **TC-12-04** | Snapshot tetap Prioritas: P1 Desain: Review | Draft dan final tersedia. Harga dasar Rp1.000.000; fee Rp10.000 sebagai nilai aturan yang sudah dicatat. | 1. Ubah listing/katalog/fee setelah agreement final. | Snapshot dan nominal lama tetap sama. | `Review` |
| **TC-12-05** | Akses agreement Prioritas: P1 Desain: Review | Draft dan final tersedia. Harga dasar Rp1.000.000; fee Rp10.000 sebagai nilai aturan yang sudah dicatat. | 1. Nonpihak membuka ID agreement melalui URL/request. | Detail privat ditolak. | `Review` |

### TS-13 — Midtrans Sandbox (10 Test Cases)

- **Fitur Terkait**: Integrasi Pembayaran Midtrans Snap Sandbox & Callback
- **Route/Endpoint**: `Payment Gateway Routes / Callback Webhook`
- **Lokasi Implementasi Repositori**: `app/Services/PaymentService.php`, `app/Http/Controllers/PaymentController.php`
- **Ketersediaan Fitur**: sebagian (Memerlukan sandbox token & stub callback)
- **Metode Pengujian**: Cypress E2E & Backend integration test
- **File Spec Rencana**: `cypress/e2e/ts-13-midtrans.cy.js`
- **Catatan Kendala / Keputusan TBD**: Callback eksternal diuji via direct webhook payload simulator

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-13-01** | Mulai pembayaran Prioritas: P1 Desain: Review | Agreement final; tujuan payout valid; WAITING_PAYMENT; sandbox siap; nominal sesuai buyer_total. | 1. Buyer membuka pembayaran transaksi memenuhi syarat. | Order sandbox/nominal benar; satu record payments terkait. | `Review` |
| **TC-13-02** | Sukses terverifikasi Prioritas: P1 Desain: Review | Agreement final; tujuan payout valid; WAITING_PAYMENT; sandbox siap; nominal sesuai buyer_total. | 1. Selesaikan pembayaran uji<br>2. terima hasil terverifikasi. | Payment PAID; transaksi PAID; paid_at dan tenggat penyerahan tercatat. | `Review` |
| **TC-13-03** | Pending Prioritas: P1 Desain: Review | Agreement final; tujuan payout valid; WAITING_PAYMENT; sandbox siap; nominal sesuai buyer_total. | 1. Simulasikan hasil pending sah. | Belum PAID; status pending dijelaskan. | `Review` |
| **TC-13-04** | Gagal batal expired Prioritas: P1 Desain: Review | Agreement final; tujuan payout valid; WAITING_PAYMENT; sandbox siap; nominal sesuai buyer_total. | 1. Jalankan setiap hasil pada percobaan terpisah. | Status payment sesuai; tidak ada izin penyerahan tanpa pembayaran berhasil. | `Review` |
| **TC-13-05** | Payload tidak sah Prioritas: P1 Desain: Review | Agreement final; tujuan payout valid; WAITING_PAYMENT; sandbox siap; nominal sesuai buyer_total. | 1. Kirim signature/payload salah atau order tak dikenal melalui alat uji. | Tidak menjadi PAID; kesalahan tertangani tanpa bocor kredensial. | `Review` |
| **TC-13-06** | Nominal tidak cocok Prioritas: P1 Desain: Review | Agreement final; tujuan payout valid; WAITING_PAYMENT; sandbox siap; nominal sesuai buyer_total. | 1. Kirim hasil dengan nominal berbeda dari buyer_total. | Tidak diterima sebagai pembayaran sah. | `Review` |
| **TC-13-07** | Callback berulang Prioritas: P1 Desain: Review | Agreement final; tujuan payout valid; WAITING_PAYMENT; sandbox siap; nominal sesuai buyer_total. | 1. Kirim ulang callback sukses sama, termasuk bersamaan. | Satu payment/efek status; poin/notifikasi tidak ganda. | `Review` |
| **TC-13-08** | Callback tidak berurutan Prioritas: P1 Desain: Review | Agreement final; tujuan payout valid; WAITING_PAYMENT; sandbox siap; nominal sesuai buyer_total. | 1. Setelah sukses sah, kirim pending lama. | Status tidak turun ke pending; mengikuti transisi terverifikasi. | `Review` |
| **TC-13-09** | Manipulasi browser Prioritas: P1 Desain: Review | Agreement final; tujuan payout valid; WAITING_PAYMENT; sandbox siap; nominal sesuai buyer_total. | 1. Ubah return URL/status request menjadi success tanpa hasil sah. | Klaim browser tidak membuat PAID. | `Review` |
| **TC-13-10** | Tidak memenuhi syarat Prioritas: P1 Desain: Review | Agreement final; tujuan payout valid; WAITING_PAYMENT; sandbox siap; nominal sesuai buyer_total. | 1. Uji draft, transaksi pihak lain/terminal, dan gateway tidak tersedia. | Ilegal ditolak; gangguan tidak menghasilkan payment/status palsu. | `Review` |

### TS-14 — Penyerahan digital (5 Test Cases)

- **Fitur Terkait**: Penyerahan Hasil Digital / Bukti Layanan oleh Seller
- **Route/Endpoint**: `Delivery Endpoints`
- **Lokasi Implementasi Repositori**: `app/Models/Transaction.php`, `app/Models/Delivery.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-14-penyerahan.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-14-01** | Penyerahan valid Prioritas: P1 Desain: Review | Seller transaksi aktif; transaksi PAID dan bukti valid tersedia. | 1. Baca snapshot<br>2. isi hasil/catatan dan bukti<br>3. kirim. | Tersimpan; DELIVERED; waktu/notifikasi Buyer tercatat. | `Review` |
| **TC-14-02** | Data wajib tidak lengkap Prioritas: P1 Desain: Review | Seller transaksi aktif; transaksi PAID dan bukti valid tersedia. | 1. Kosongkan bukti atau hasil wajib. | Ditolak; status tetap. | `Review` |
| **TC-14-03** | Status salah Prioritas: P1 Desain: Review | Seller transaksi aktif; transaksi PAID dan bukti valid tersedia. | 1. Serahkan pada WAITING_PAYMENT/terminal. | Ditolak. | `Review` |
| **TC-14-04** | Bukan Seller Prioritas: P1 Desain: Review | Seller transaksi aktif; transaksi PAID dan bukti valid tersedia. | 1. Buyer/nonpihak mencoba menyerahkan. | Ditolak; hanya Seller transaksi berwenang. | `Review` |
| **TC-14-05** | Penyerahan ulang Prioritas: P1 Desain: Review | Seller transaksi aktif; transaksi PAID dan bukti valid tersedia. | 1. Kirim ulang request penyerahan sama. | Tidak ada penyerahan/efek ganda. | `Review` |

### TS-15 — Konfirmasi penerimaan (4 Test Cases)

- **Fitur Terkait**: Konfirmasi Penerimaan Barang/Jasa oleh Buyer
- **Route/Endpoint**: `Confirmation Endpoints`
- **Lokasi Implementasi Repositori**: `app/Models/Transaction.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-15-konfirmasi.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-15-01** | Konfirmasi valid Prioritas: P1 Desain: Review | Transaksi DELIVERED tersedia. | 1. Buyer memeriksa hasil lalu konfirmasi. | CONFIRMED; confirmed_at; payout siap; belum COMPLETED. | `Review` |
| **TC-15-02** | Belum diserahkan Prioritas: P1 Desain: Review | Transaksi DELIVERED tersedia. | 1. Konfirmasi sebelum DELIVERED. | Ditolak. | `Review` |
| **TC-15-03** | Pelaku salah Prioritas: P1 Desain: Review | Transaksi DELIVERED tersedia. | 1. Seller/nonpihak konfirmasi. | Ditolak; hanya Buyer terkait. | `Review` |
| **TC-15-04** | Konfirmasi ulang Prioritas: P1 Desain: Review | Transaksi DELIVERED tersedia. | 1. Kirim ulang setelah berhasil. | Tidak ada perubahan/efek ganda. | `Review` |

### TS-16 — Pengajuan komplain (5 Test Cases)

- **Fitur Terkait**: Pengajuan Dispute / Komplain Transaksi Berbukti
- **Route/Endpoint**: `Dispute Endpoints`
- **Lokasi Implementasi Repositori**: `app/Models/Dispute.php`, `app/Models/Transaction.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-16-ajukan-komplain.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-16-01** | Komplain valid Prioritas: P1 Desain: Review | Buyer terkait; tahap mengizinkan komplain; tenggat TBD. | 1. Isi alasan/bukti dalam waktu yang diizinkan. | Satu OPEN; DISPUTED; Seller/Admin diberi tahu. | `Review` |
| **TC-16-02** | Alasan atau bukti kosong Prioritas: P1 Desain: Review | Buyer terkait; tahap mengizinkan komplain; tenggat TBD. | 1. Kosongkan alasan/bukti bergantian. | Ditolak; status tetap. | `Review` |
| **TC-16-03** | Bukan Buyer Prioritas: P1 Desain: Review | Buyer terkait; tahap mengizinkan komplain; tenggat TBD. | 1. Seller/nonpihak mengajukan. | Ditolak. | `Review` |
| **TC-16-04** | Komplain ganda Prioritas: P1 Desain: Review | Buyer terkait; tahap mengizinkan komplain; tenggat TBD. | 1. Ajukan kembali/kirim bersamaan pada transaksi sama. | Maksimal satu kasus per transaksi. | `Review` |
| **TC-16-05** | Transaksi ditutup Prioritas: P1 Desain: Review | Buyer terkait; tahap mengizinkan komplain; tenggat TBD. | 1. Ajukan pada COMPLETED/REFUNDED/status yang dilarang kebijakan. | Ditolak sesuai aturan status. | `Review` |

### TS-17 — Tanggapan komplain (4 Test Cases)

- **Fitur Terkait**: Tanggapan Komplain oleh Seller
- **Route/Endpoint**: `Dispute Response Endpoints`
- **Lokasi Implementasi Repositori**: `app/Models/DisputeResponse.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-17-tanggapan-komplain.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-17-01** | Tanggapan valid Prioritas: P1 Desain: Review | Kasus aktif dan Seller terkait tersedia. | 1. Baca kasus<br>2. isi tanggapan/bukti sesuai kewajiban. | Tersimpan atas Seller benar; Admin diberi tahu. | `Review` |
| **TC-17-02** | Tanggapan kosong Prioritas: P1 Desain: Review | Kasus aktif dan Seller terkait tersedia. | 1. Kirim kosong/hanya spasi. | Ditolak. | `Review` |
| **TC-17-03** | Kasus pihak lain Prioritas: P1 Desain: Review | Kasus aktif dan Seller terkait tersedia. | 1. Seller lain membuka/menanggapi. | Akses dan penyimpanan ditolak. | `Review` |
| **TC-17-04** | Bukan keputusan Prioritas: P1 Desain: Review | Kasus aktif dan Seller terkait tersedia. | 1. Kirim tanggapan valid. | Tidak otomatis memutus payout/refund atau menutup transaksi. | `Review` |

### TS-18 — Keputusan komplain (5 Test Cases)

- **Fitur Terkait**: Resolusi Komplain oleh Admin (Payout vs Refund)
- **Route/Endpoint**: `GET /admin/disputes`, `Admin Dispute Resolution Endpoints`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Admin/AdminModuleController.php@disputes`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-18-keputusan-komplain.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-18-01** | Bahan peninjauan Prioritas: P1 Desain: Review | Admin aktif; kasus memiliki snapshot dan bukti. | 1. Admin membuka kasus. | Snapshot, payment, penyerahan, komplain, tanggapan, riwayat tersedia. | `Review` |
| **TC-18-02** | Keputusan payout Prioritas: P1 Desain: Review | Admin aktif; kasus memiliki snapshot dan bukti. | 1. Pilih PAYOUT<br>2. isi alasan. | Keputusan/alasan/pelaku/waktu tersimpan; settlement belum selesai. | `Review` |
| **TC-18-03** | Keputusan refund Prioritas: P1 Desain: Review | Admin aktif; kasus memiliki snapshot dan bukti. | 1. Pilih REFUND<br>2. isi alasan. | Keputusan tersimpan; pencatatan refund langkah berikutnya. | `Review` |
| **TC-18-04** | Alasan atau informasi kurang Prioritas: P1 Desain: Review | Admin aktif; kasus memiliki snapshot dan bukti. | 1. Kosongkan alasan<br>2. uji permintaan informasi tambahan. | Tanpa alasan ditolak; meminta informasi tidak membuat keputusan akhir. | `Review` |
| **TC-18-05** | Akses dan pengulangan Prioritas: P1 Desain: Review | Admin aktif; kasus memiliki snapshot dan bukti. | 1. USER memutuskan<br>2. Admin kirim ulang keputusan sama. | USER ditolak; efek keputusan tidak ganda. | `Review` |

### TS-19 — Payout simulatif (5 Test Cases)

- **Fitur Terkait**: Pencatatan Payout Manual Simulatif Seller oleh Admin
- **Route/Endpoint**: `GET /admin/transactions`, `Payout Endpoints`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Admin/AdminModuleController.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-19-payout.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-19-01** | Payout setelah konfirmasi Prioritas: P1 Desain: Review | CONFIRMED atau keputusan PAYOUT; tujuan Seller valid. | 1. Admin catat seller_net, referensi/bukti sesuai kebijakan. | PAYOUT COMPLETED; transaksi COMPLETED; pelaku/waktu/bukti tercatat. | `Review` |
| **TC-19-02** | Payout komplain Prioritas: P1 Desain: Review | CONFIRMED atau keputusan PAYOUT; tujuan Seller valid. | 1. Catat payout pada keputusan PAYOUT. | Keputusan/nominal benar; penyelesaian sekali. | `Review` |
| **TC-19-03** | Belum memenuhi syarat Prioritas: P1 Desain: TBD | CONFIRMED atau keputusan PAYOUT; tujuan Seller valid. | 1. Payout sebelum konfirmasi/komplain belum diputus. | Ditolak. | `TBD` |
| **TC-19-04** | Data atau pelaku salah Prioritas: P1 Desain: Review | CONFIRMED atau keputusan PAYOUT; tujuan Seller valid. | 1. Uji nominal bukan seller_net, wajib kosong, pelaku USER. | Ditolak; tidak ada settlement final tidak sah. | `Review` |
| **TC-19-05** | Ganda dan final terkunci Prioritas: P1 Desain: Review | CONFIRMED atau keputusan PAYOUT; tujuan Seller valid. | 1. Kirim ulang/bersamaan<br>2. coba ubah settlement COMPLETED. | Satu penyelesaian final; detail terkunci; tanpa transfer nyata. | `Review` |

### TS-20 — Refund simulatif (7 Test Cases)

- **Fitur Terkait**: Pencatatan Refund Simulatif Buyer oleh Admin
- **Route/Endpoint**: `Admin Settlement / Refund Endpoints`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Admin/AdminModuleController.php`
- **Ketersediaan Fitur**: sebagian (Formula potongan 3x fee Midtrans TBD D06)
- **Metode Pengujian**: Cypress E2E / Backend test
- **File Spec Rencana**: `cypress/e2e/ts-20-refund.cy.js`
- **Catatan Kendala / Keputusan TBD**: Aturan potongan tarif resmi Midtrans menunggu pengesahan

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-20-01** | Refund valid Prioritas: P1 Desain: TBD | Keputusan refund dan tujuan Buyer valid. Dasar bruto refund serta biaya Midtrans untuk order uji harus disahkan. | 1. Ambil dasar refund G dari kebijakan yang disahkan dan fee Midtrans M untuk order uji<br>2. hitung R = G - 3 x M. Admin catat R dan tujuan Buyer. | Nominal dicocokkan dengan R; komponen G, M dan potongan 3 x M tersimpan/tertelusur. REFUNDED hanya setelah settlement manual berhasil dicatat. | `TBD` |
| **TC-20-02** | Tanpa keputusan Prioritas: P1 Desain: Review | Keputusan refund dan tujuan Buyer valid. Dasar bruto refund serta biaya Midtrans untuk order uji harus disahkan. | 1. Coba tanpa keputusan REFUND/keputusan PAYOUT. | Ditolak. | `Review` |
| **TC-20-03** | Nominal atau tujuan salah Prioritas: P1 Desain: TBD | Keputusan refund dan tujuan Buyer valid. Dasar bruto refund serta biaya Midtrans untuk order uji harus disahkan. | 1. Dengan G dan M disahkan, kirim R+1, R-1, lalu tujuan yang tidak lengkap sebagai dataset terpisah. | Setiap nominal di luar hasil rumus dan tujuan tidak lengkap ditolak; tidak ada settlement final; status serta saldo tidak berubah. | `TBD` |
| **TC-20-04** | Pelaku salah Prioritas: P1 Desain: Review | Keputusan refund dan tujuan Buyer valid. Dasar bruto refund serta biaya Midtrans untuk order uji harus disahkan. | 1. Buyer/Seller mencatat refund. | Ditolak. | `Review` |
| **TC-20-05** | Penyelesaian ganda Prioritas: P1 Desain: Review | Keputusan refund dan tujuan Buyer valid. Dasar bruto refund serta biaya Midtrans untuk order uji harus disahkan. | 1. Ulangi refund<br>2. refund setelah payout<br>3. payout setelah refund. | Hanya satu final PAYOUT/REFUND; tanpa transfer nyata. | `Review` |
| **TC-20-06** | Potongan refund Prioritas: P1 Desain: TBD | Keputusan refund dan tujuan Buyer valid. Dasar bruto refund serta biaya Midtrans untuk order uji harus disahkan. | 1. Gunakan dataset perhitungan G=Rp1.010.000 dan M=Rp4.000 hanya setelah dasar G disahkan untuk order tersebut. | Potongan Rp12.000; refund Rp998.000. Ini contoh perhitungan dengan fixture M, bukan tarif resmi Midtrans. | `TBD` |
| **TC-20-07** | Refund negatif Prioritas: P1 Desain: TBD | Keputusan refund dan tujuan Buyer valid. Dasar bruto refund serta biaya Midtrans untuk order uji harus disahkan. | 1. Gunakan fixture G lebih kecil daripada 3 x M<br>2. ajukan settlement. | Tidak ada transfer atau pencatatan nominal negatif. Perlakuan nol/penolakan mengikuti keputusan D06. | `TBD` |

### TS-21 — Status dan riwayat (5 Test Cases)

- **Fitur Terkait**: Audit Log Status Transaksi & Riwayat Perubahan Status (Scope Zinky)
- **Route/Endpoint**: `GET /admin/audit-logs`, `Transaction History View`
- **Lokasi Implementasi Repositori**: `app/Models/TransactionStatusHistory.php`, `app/Http/Controllers/Admin/AdminModuleController.php@auditLogs`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-21-riwayat.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-21-01** | Daftar pihak terkait Prioritas: P1 Desain: Review | Transaksi berbagai tahap; akun terkait/nonpihak tersedia. | 1. Buyer/Seller membuka daftar dan detail miliknya. | Hanya transaksi berhak; rincian konsisten. | `Review` |
| **TC-21-02** | Pemantauan Admin Prioritas: P1 Desain: Review | Transaksi berbagai tahap; akun terkait/nonpihak tersedia. | 1. Admin membuka transaksi dalam kewenangannya. | Data operasional sesuai akses tersedia. | `Review` |
| **TC-21-03** | Akses nonpihak Prioritas: P1 Desain: Review | Transaksi berbagai tahap; akun terkait/nonpihak tersedia. | 1. Ubah ID URL/request memakai akun lain. | Transaksi, rekening, hasil/bukti privat tidak terbuka. | `Review` |
| **TC-21-04** | Riwayat dan audit Prioritas: P1 Desain: Review | Transaksi berbagai tahap; akun terkait/nonpihak tersedia. | 1. Lakukan perubahan sah<br>2. periksa status/history/audit. | Urutan, from/to, pelaku/sistem, waktu/tindakan konsisten. | `Review` |
| **TC-21-05** | Perbedaan tahap Prioritas: P1 Desain: Review | Transaksi berbagai tahap; akun terkait/nonpihak tersedia. | 1. Bandingkan PAID, CONFIRMED, COMPLETED, REFUNDED<br>2. buka daftar kosong. | Tahap dibedakan jelas; keadaan kosong tertangani. | `Review` |

### TS-22 — Notifikasi (5 Test Cases)

- **Fitur Terkait**: Sistem Notifikasi Pengguna & Deduplikasi Event (Scope Zinky)
- **Route/Endpoint**: `Notification Endpoints / Navbar`
- **Lokasi Implementasi Repositori**: `app/Models/Notification.php`, `app/Services/NotificationService.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-22-notifikasi.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-22-01** | Penerima dan event Prioritas: P2 Desain: Review | Event payment, penyerahan, komplain, konfirmasi, settlement tersedia. | 1. Jalankan tiap event penting<br>2. cek penerima. | Jenis/waktu/pesan/rujukan benar; hanya pihak relevan. | `Review` |
| **TC-22-02** | Status baca Prioritas: P2 Desain: Review | Event payment, penyerahan, komplain, konfirmasi, settlement tersedia. | 1. Buka notifikasi belum dibaca. | read_at berubah hanya pada notifikasi miliknya. | `Review` |
| **TC-22-03** | Tanpa notifikasi Prioritas: P2 Desain: Review | Event payment, penyerahan, komplain, konfirmasi, settlement tersedia. | 1. Buka akun tanpa notifikasi. | Keadaan kosong jelas. | `Review` |
| **TC-22-04** | Akses pihak lain Prioritas: P2 Desain: Review | Event payment, penyerahan, komplain, konfirmasi, settlement tersedia. | 1. Buka ID notifikasi/rujukan privat dengan akun lain. | Ditolak. | `Review` |
| **TC-22-05** | Event ulang Prioritas: P2 Desain: Review | Event payment, penyerahan, komplain, konfirmasi, settlement tersedia. | 1. Proses ulang event/callback sama. | Tidak ada notifikasi bisnis ganda; siap payout bukan payout selesai. | `Review` |

### TS-23 — Kontribusi dan reputasi (5 Test Cases)

- **Fitur Terkait**: Poin Reputasi & Label Kontribusi Pengguna
- **Route/Endpoint**: `Profile View / Reputation Display`
- **Lokasi Implementasi Repositori**: `app/Models/ReputationEvent.php`, `app/Models/User.php`
- **Ketersediaan Fitur**: sebagian (Bobot & ambang poin TBD D10)
- **Metode Pengujian**: Cypress E2E / Backend test
- **File Spec Rencana**: `cypress/e2e/ts-23-reputasi.cy.js`
- **Catatan Kendala / Keputusan TBD**: Aturan bobot poin belum disahkan (TBD)

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-23-01** | Total poin Prioritas: P2 Desain: TBD | reputation_events tersedia; bobot/visibilitas TBD. | 1. Fixture +10, +5, -3<br>2. buka indikator. | Total 12; arti poin dijelaskan. | `TBD` |
| **TC-23-02** | Event transaksi Prioritas: P2 Desain: TBD | reputation_events tersedia; bobot/visibilitas TBD. | 1. Selesaikan transaksi memenuhi rule poin. | Event sesuai bobot; yang belum memenuhi syarat tidak mendapat poin. | `TBD` |
| **TC-23-03** | Event ulang Prioritas: P2 Desain: TBD | reputation_events tersedia; bobot/visibilitas TBD. | 1. Proses ulang event transaksi sama. | event_key mencegah poin ganda. | `TBD` |
| **TC-23-04** | Status terpisah Prioritas: P2 Desain: TBD | reputation_events tersedia; bobot/visibilitas TBD. | 1. Uji poin tinggi tanpa keputusan dan akun dengan keputusan. | Tanpa keputusan: Belum ditinjau; status mengikuti keputusan terbaru, bukan ambang poin. | `TBD` |
| **TC-23-05** | Visibilitas Prioritas: P2 Desain: TBD | reputation_events tersedia; bobot/visibilitas TBD. | 1. Buka sebagai pemilik, pengguna lain, Admin. | Sesuai kebijakan; bukti/alasan privat tidak bocor. | `TBD` |

### TS-24 — Peninjauan akun (6 Test Cases)

- **Fitur Terkait**: Pelaporan Akun & Peninjauan Status oleh Admin
- **Route/Endpoint**: `GET /admin/users`, `Account Review Endpoints`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Admin/AdminModuleController.php@users`
- **Ketersediaan Fitur**: sebagian (Form pelaporan publik TBD)
- **Metode Pengujian**: Cypress E2E / Manual
- **File Spec Rencana**: `cypress/e2e/ts-24-review-akun.cy.js`
- **Catatan Kendala / Keputusan TBD**: Alur pelaporan publik belum final

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-24-01** | Laporan valid Prioritas: P2 Desain: TBD | Target, laporan, bukti tersedia; pengajuan laporan alur pendukung. | 1. Isi target/alasan/bukti melalui alur yang disepakati. | Sumber, pelapor, target/konteks benar tersimpan. | `TBD` |
| **TC-24-02** | Laporan tidak valid Prioritas: P2 Desain: TBD | Target, laporan, bukti tersedia; pengajuan laporan alur pendukung. | 1. Uji target tidak ada, alasan/bukti wajib kosong. | Ditolak tanpa kasus tidak konsisten. | `TBD` |
| **TC-24-03** | Review Admin Prioritas: P2 Desain: TBD | Target, laporan, bukti tersedia; pengajuan laporan alur pendukung. | 1. Admin membuka laporan/konteks/bukti. | Informasi diperlukan tersedia; akses dibatasi. | `TBD` |
| **TC-24-04** | Keputusan review Prioritas: P2 Desain: TBD | Target, laporan, bukti tersedia; pengajuan laporan alur pendukung. | 1. Selesaikan/tolak laporan<br>2. tetapkan status bila perlu<br>3. isi alasan. | Keputusan/alasan/pelaku/waktu dan event terkait tercatat. | `TBD` |
| **TC-24-05** | Belum terbukti Prioritas: P2 Desain: TBD | Target, laporan, bukti tersedia; pengajuan laporan alur pendukung. | 1. Buat laporan OPEN<br>2. periksa profil. | Tidak otomatis mengurangi poin/memberi label PROBLEMATIC. | `TBD` |
| **TC-24-06** | Privasi dan akses Prioritas: P2 Desain: TBD | Target, laporan, bukti tersedia; pengajuan laporan alur pendukung. | 1. USER melihat bukti privat, review, ubah reputasi. | Ditolak; laporan bukan tuduhan publik sebelum keputusan. | `TBD` |

### TS-25 — Kategori produk varian (6 Test Cases)

- **Fitur Terkait**: Master Katalog Admin (Kategori, Produk, Varian)
- **Route/Endpoint**: `GET /admin/catalog`, `POST /admin/catalog/categories`, `PUT /admin/catalog/categories/{category}`, `PATCH /admin/catalog/categories/{category}/toggle`, `POST /admin/catalog/products`, `PUT /admin/catalog/products/{product}`, `PATCH /admin/catalog/products/{product}/toggle`, `POST /admin/catalog/variants`, `PUT /admin/catalog/variants/{variant}`, `PATCH /admin/catalog/variants/{variant}/toggle`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Admin/CatalogController.php`, `app/Models/ProductCategory.php`, `app/Models/Product.php`, `app/Models/ProductVariant.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-25-katalog.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-25-01** | Buat katalog Prioritas: P2 Desain: Review | Admin/USER aktif; katalog tersedia. | 1. Admin buat kategori, produk, varian valid. | Relasi kategori–produk–varian benar. | `Review` |
| **TC-25-02** | Ubah katalog Prioritas: P2 Desain: Review | Admin/USER aktif; katalog tersedia. | 1. Ubah nama/deskripsi. | Penggunaan baru berubah; snapshot lama tetap. | `Review` |
| **TC-25-03** | Duplikasi Prioritas: P2 Desain: Review | Admin/USER aktif; katalog tersedia. | 1. Uji kategori sama<br>2. produk sama dalam kategori<br>3. varian sama dalam produk. | Duplikasi pada ruang unik ditolak. | `Review` |
| **TC-25-04** | Relasi tidak sah Prioritas: P2 Desain: Review | Admin/USER aktif; katalog tersedia. | 1. Kosongkan nama/gunakan parent tak ada. | Ditolak; tidak ada relasi yatim. | `Review` |
| **TC-25-05** | Nonaktifkan katalog Prioritas: P2 Desain: Review | Admin/USER aktif; katalog tersedia. | 1. Nonaktifkan data sudah digunakan. | Pilihan baru sesuai rule aktif; history/snapshot utuh. | `Review` |
| **TC-25-06** | Akses USER Prioritas: P2 Desain: Review | Admin/USER aktif; katalog tersedia. | 1. USER buat/ubah/nonaktifkan katalog. | Ditolak melalui UI/request. | `Review` |

### TS-26 — Tujuan settlement (4 Test Cases)

- **Fitur Terkait**: Konfigurasi Rekening Settlement (Bank, E-Wallet, QRIS)
- **Route/Endpoint**: `Settlement Destination Endpoints`
- **Lokasi Implementasi Repositori**: `app/Models/UserPayoutDestination.php`
- **Ketersediaan Fitur**: sebagian (QRIS/E-Wallet TBD D10)
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-26-tujuan-settlement.cy.js`
- **Catatan Kendala / Keputusan TBD**: Metode QRIS/Ewallet menunggu spesifikasi schema

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-26-01** | BANK dan EWALLET Prioritas: P1 Desain: TBD | Settlement PLANNED/final tersedia. | 1. Isi provider, nomor, nama sesuai tipe. | Tujuan valid pada transaksi benar. | `TBD` |
| **TC-26-02** | QRIS Prioritas: P1 Desain: TBD | Settlement PLANNED/final tersedia. | 1. Isi gambar QRIS/data penerima wajib. | Tujuan QRIS valid tersimpan. | `TBD` |
| **TC-26-03** | Data atau akses salah Prioritas: P1 Desain: TBD | Settlement PLANNED/final tersedia. | 1. Kosongkan nomor/gambar<br>2. ubah tujuan transaksi pihak lain. | Tidak lengkap/tidak berwenang ditolak. | `TBD` |
| **TC-26-04** | Perubahan tujuan Prioritas: P1 Desain: TBD | Settlement PLANNED/final tersedia. | 1. Pelaku berwenang ubah sebelum pelaksanaan<br>2. ulangi setelah COMPLETED. | Perubahan sah diaudit; final terkunci. | `TBD` |

### TS-27 — Perhitungan fee melalui service (9 Test Cases)

- **Fitur Terkait**: Kalkulasi Fee Transaksi Terpusat (FeeCalculationService)
- **Route/Endpoint**: `POST /fee/preview`
- **Lokasi Implementasi Repositori**: `app/Services/FeeCalculationService.php`, `app/Http/Controllers/FeePreviewController.php`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E & Backend PHPUnit
- **File Spec Rencana**: `cypress/e2e/ts-27-hitung-fee.cy.js`
- **Catatan Kendala / Keputusan TBD**: Tier di bawah Rp1 juta TBD

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-27-01** | Nominal melampaui batas lama Prioritas: P1 Desain: Review | Panggil service pada lingkungan uji; gunakan snapshot transaksi final. Tabel fee di bawah Rp1 juta harus dilengkapi sebelum kasus terkait dijalankan. | 1. Panggil service untuk Rp1.000.000 dan Rp1.000.001. | Keduanya fee Rp10.000; nominal di atas Rp1 juta tidak ditolak hanya karena batas lama. Batas minimum/maksimum baru mengikuti keputusan D02. | `Review` |
| **TC-27-02** | Batas kenaikan fee Prioritas: P1 Desain: Review | Panggil service pada lingkungan uji; gunakan snapshot transaksi final. Tabel fee di bawah Rp1 juta harus dilengkapi sebelum kasus terkait dijalankan. | 1. Jalankan Rp9.999.999, Rp10.000.000, Rp10.000.001, Rp19.999.999, Rp20.000.000, Rp20.000.001 sebagai dataset terpisah. | Fee berurutan Rp10.000, Rp20.000, Rp20.000, Rp20.000, Rp30.000, Rp30.000; rekam hasil per dataset. | `Review` |
| **TC-27-03** | Manipulasi fee dari client Prioritas: P1 Desain: Review | Panggil service pada lingkungan uji; gunakan snapshot transaksi final. Tabel fee di bawah Rp1 juta harus dilengkapi sebelum kasus terkait dijalankan. | 1. Kirim checkout sah tetapi ubah parameter fee/total pada request menjadi 0 atau 1<br>2. ulangi dengan sumber Marketplace dan Forum. | Server menghitung fee dari service dan sumber transaksi; request tidak dapat menurunkan fee atau memaksakan EX/INC pada Marketplace. | `Review` |
| **TC-27-04** | Snapshot saat aturan service berubah Prioritas: P1 Desain: Review | Panggil service pada lingkungan uji; gunakan snapshot transaksi final. Tabel fee di bawah Rp1 juta harus dilengkapi sebelum kasus terkait dijalankan. | 1. Finalkan transaksi A pada versi rule 1<br>2. deploy perubahan rule terkontrol pada lingkungan uji<br>3. buat transaksi B. | A mempertahankan nominal snapshot. B memakai rule baru yang disahkan. Hash build dan hasil dua transaksi dicatat; tidak ada CRUD FeeConfig. | `Review` |
| **TC-27-05** | Tier di bawah satu juta Prioritas: P1 Desain: TBD | Panggil service pada lingkungan uji; gunakan snapshot transaksi final. Tabel fee di bawah Rp1 juta harus dilengkapi sebelum kasus terkait dijalankan. | 1. Setelah tabel fee proposal disahkan, uji satu rupiah di bawah, tepat, dan satu rupiah di atas setiap batas tier. | Fee persis sesuai tabel acuan; setiap nominal memiliki dataset dan hasil terpisah; aturan pembulatan tidak ditebak. | `TBD` |
| **TC-27-06** | Batas satu juta Prioritas: P1 Desain: TBD | Panggil service pada lingkungan uji; gunakan snapshot transaksi final. Tabel fee di bawah Rp1 juta harus dilengkapi sebelum kasus terkait dijalankan. | 1. Gunakan Rp999.999 dan Rp1.000.000. | Rp1.000.000 menghasilkan Rp10.000; Rp999.999 mengikuti tabel fee di bawah satu juta yang disahkan. | `TBD` |
| **TC-27-07** | Mode fee menurut sumber Prioritas: P1 Desain: Review | Panggil service pada lingkungan uji; gunakan snapshot transaksi final. Tabel fee di bawah Rp1 juta harus dilengkapi sebelum kasus terkait dijalankan. | 1. Buat checkout Marketplace serta draft Forum EX dan INC dengan harga sama. | Marketplace memakai fixed fee tanpa pilihan mode; Forum menerima EX/INC dan menghitung total/net sesuai snapshot. | `Review` |
| **TC-27-08** | Input nominal tidak sah Prioritas: P1 Desain: Review | Panggil service pada lingkungan uji; gunakan snapshot transaksi final. Tabel fee di bawah Rp1 juta harus dilengkapi sebelum kasus terkait dijalankan. | 1. Panggil service/request dengan kosong, teks, nol, negatif dan nilai di luar kapasitas tipe data sebagai dataset terpisah. | Input yang tidak memenuhi kontrak ditolak terkontrol; tidak overflow atau membuat nominal transaksi tidak sah. | `Review` |
| **TC-27-09** | Konsistensi nominal Prioritas: P1 Desain: Review | Panggil service pada lingkungan uji; gunakan snapshot transaksi final. Tabel fee di bawah Rp1 juta harus dilengkapi sebelum kasus terkait dijalankan. | 1. Pada harga Rp10 juta, bandingkan preview, snapshot, pembayaran dan payout. | Fee Rp20.000 konsisten; total/net mengikuti sumber dan mode; client tidak menjadi sumber nominal otoritatif. | `Review` |

### TS-28 — Keamanan dan privasi (6 Test Cases)

- **Fitur Terkait**: Proteksi XSS, CSRF, URL Tampering, Hash Checking
- **Route/Endpoint**: `Seluruh route web & middleware`
- **Lokasi Implementasi Repositori**: `app/Http/Middleware/`, `resources/views/`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E & Security scan
- **File Spec Rencana**: `cypress/e2e/ts-28-keamanan.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-28-01** | Input script Prioritas: P1 Desain: Review | Lingkungan milik tim; data fiktif; tipe/ukuran file TBD. | 1. Masukkan HTML/script uji pada post, komentar, DM, catatan. | Tidak menjalankan script; sanitasi/escaping sesuai field. | `Review` |
| **TC-28-02** | Input manipulatif Prioritas: P1 Desain: Review | Lingkungan milik tim; data fiktif; tipe/ukuran file TBD. | 1. Kirim input uji khusus pada pencarian/login. | Tidak bypass login/buka data lain/bocorkan error database. | `Review` |
| **TC-28-03** | CSRF Prioritas: P1 Desain: Review | Lingkungan milik tim; data fiktif; tipe/ukuran file TBD. | 1. Kirim perubahan web tanpa token sah<br>2. callback gateway diuji dengan autentikasi gateway. | Perubahan web ditolak; callback tidak memakai sesi Buyer sebagai bukti gateway. | `Review` |
| **TC-28-04** | Validasi file Prioritas: P1 Desain: TBD | Lingkungan milik tim; data fiktif; tipe/ukuran file TBD. | 1. Uji valid, tipe terlarang, ekstensi berbeda isi, ukuran berlebih. | Hanya file sesuai kebijakan; file tidak dieksekusi; batas TBD. | `TBD` |
| **TC-28-05** | Bukti dan hasil privat Prioritas: P1 Desain: Review | Lingkungan milik tim; data fiktif; tipe/ukuran file TBD. | 1. Akses URL bukti/hasil digital sebagai nonpihak/tanpa sesi. | Tidak dapat diambil tanpa otorisasi. | `Review` |
| **TC-28-06** | Rahasia tersimpan Prioritas: P1 Desain: Review | Lingkungan milik tim; data fiktif; tipe/ukuran file TBD. | 1. Periksa akun, response, audit/log dengan akses tim sah. | Password hash; password/token/kredensial tidak tampil di response/audit/log. | `Review` |

### TS-29 — Tampilan dan kompatibilitas (3 Test Cases)

- **Fitur Terkait**: Responsivitas UI, Konsistensi Nominal & Empty States
- **Route/Endpoint**: `Halaman Utama, Forum, Marketplace, Admin`
- **Lokasi Implementasi Repositori**: `resources/views/`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E UI
- **File Spec Rencana**: `cypress/e2e/ts-29-tampilan.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-29-01** | Halaman utama Prioritas: P2 Desain: Review | Chrome desktop; viewport dicatat. | 1. Buka akun, marketplace, forum, DM, transaksi, Admin. | Form/tabel/tombol digunakan; isi penting tidak terpotong. | `Review` |
| **TC-29-02** | Nominal dan status Prioritas: P2 Desain: Review | Chrome desktop; viewport dicatat. | 1. Bandingkan checkout, agreement, payment, settlement. | Label harga, fee, total/net/status konsisten dan jelas. | `Review` |
| **TC-29-03** | Kosong gagal loading Prioritas: P2 Desain: Review | Chrome desktop; viewport dicatat. | 1. Uji data kosong, validasi gagal, gangguan koneksi. | Pesan jelas; tidak mengaku sukses ketika belum tersimpan. | `Review` |

### TS-30 — End to end (4 Test Cases)

- **Fitur Terkait**: Alur Penuh Transaksi Marketplace & Forum (Order s/d Payout)
- **Route/Endpoint**: `Siklus Penuh Transaksi`
- **Lokasi Implementasi Repositori**: `Kombinasi Controller Marketplace, DM, Agreement, Payment, Settlement`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E Flow
- **File Spec Rencana**: `cypress/e2e/ts-30-end-to-end.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-30-01** | Marketplace payout Prioritas: P1 Desain: Review | Modul siap; transaksi baru untuk tiap kasus/cabang. | 1. Listing → checkout → bayar → serah → konfirmasi → payout. | COMPLETED; snapshot/nominal/riwayat/bukti/notifikasi konsisten. | `Review` |
| **TC-30-02** | Forum exclude payout Prioritas: P1 Desain: Review | Modul siap; transaksi baru untuk tiap kasus/cabang. | 1. Post/DM → draft exclude → dua persetujuan → bayar → serah → konfirmasi → payout. | Buyer harga + fee; Seller harga dasar; COMPLETED. | `Review` |
| **TC-30-03** | Forum include revisi Prioritas: P1 Desain: Review | Modul siap; transaksi baru untuk tiap kasus/cabang. | 1. Post/DM → include → revisi → setuju terbaru → bayar → serah → konfirmasi → payout. | Persetujuan lama batal; Buyer harga dasar; Seller dipotong fee; COMPLETED. | `Review` |
| **TC-30-04** | Komplain settlement Prioritas: P1 Desain: Review | Modul siap; transaksi baru untuk tiap kasus/cabang. | 1. Bayar → serah → komplain → tanggapan → keputusan → settlement<br>2. uji PAYOUT/REFUND terpisah. | Status akhir sesuai; satu final; tidak ada efek/poin/notifikasi ganda. | `Review` |

### TS-31 — Tenggat dan transisi (11 Test Cases)

- **Fitur Terkait**: Manajemen Timeout, Scheduler, Expired, Auto-Confirm
- **Route/Endpoint**: `Console Commands / Scheduler Jobs`
- **Lokasi Implementasi Repositori**: `app/Console/Commands/`, `routes/console.php`
- **Ketersediaan Fitur**: sebagian (Scheduler background job)
- **Metode Pengujian**: Backend PHPUnit / Pest & Cypress
- **File Spec Rencana**: `cypress/e2e/ts-31-tenggat.cy.js`
- **Catatan Kendala / Keputusan TBD**: Memerlukan kontrol waktu server (Carbon::setTestNow)

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-31-01** | Tenggat pembayaran 1 jam Prioritas: P1 Desain: TBD | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. Setelah titik awal pembayaran disahkan, uji t+3599, t+3600, t+3601 detik pada transaksi berbeda. | Durasi tersimpan 3600 detik dari titik awal yang disahkan; pending/expired tidak menjadi PAID. Hasil tepat batas dan callback terlambat mengikuti keputusan D03. | `TBD` |
| **TC-31-02** | Tenggat penyerahan Prioritas: P1 Desain: TBD | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. 1 jam. Pada pembayaran sah t0, periksa delivery_due_at dan uji penyerahan pada t0+3599, +3600, +3601. | delivery_due_at = paid_at + 3600 detik; notifikasi/peringatan tidak menduplikasi. Tindakan sesudah batas menunggu aturan tambahan waktu D04. | `TBD` |
| **TC-31-03** | Konfirmasi dan komplain Prioritas: P1 Desain: TBD | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. Untuk penyerahan sah t0, uji tindakan Buyer pada +3599, +3600, +3601 dan +5400 detik<br>2. gunakan transaksi berbeda. | Jendela konfirmasi 1 jam dan tambahan 30 menit diperhitungkan; komplain aktif menahan kelayakan payout. Transisi tepat batas dan pemicu payout mengikuti D05; bukan bukti transfer otomatis. | `TBD` |
| **TC-31-04** | Transisi ilegal bersamaan Prioritas: P1 Desain: Review | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. Coba WAITING_PAYMENT → COMPLETED<br>2. konfirmasi vs komplain<br>3. payout vs refund bersamaan. | Transisi ilegal ditolak; satu hasil sah; status/history/settlement konsisten. | `Review` |
| **TC-31-05** | Notifikasi tambahan waktu Prioritas: P1 Desain: TBD | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. Biarkan konfirmasi Buyer melewati 1 jam hingga tambahan 30 menit<br>2. jalankan scheduler pada waktu terkendali. | Notifikasi yang disahkan terkirim sekali kepada penerima tepat; jadwal 30 menit tercatat; nama event disesuaikan implementasi. | `TBD` |
| **TC-31-06** | Komplain menahan payout Prioritas: P1 Desain: Review | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. Buat komplain aktif sebelum pemrosesan tenggat<br>2. jalankan scheduler dua kali. | Tidak ada settlement payout final selama komplain aktif; tidak ada efek ganda. | `Review` |
| **TC-31-07** | Tenggat payout Prioritas: P1 Desain: TBD | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. Setelah syarat payout terpenuhi, hitung 1 jam dari event awal yang disahkan dan uji sebelum/pada/sesudah batas. | payout_due_at sesuai 3600 detik; tindak lanjut sesuai D05. Keterlambatan tidak dicatat seolah transfer sudah berhasil. | `TBD` |
| **TC-31-08** | Callback sukses setelah expired Prioritas: P1 Desain: TBD | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. Kirim callback sukses sah setelah tenggat atau pembatalan. | Hasil mengikuti keputusan D03; callback tidak hilang diam-diam dan tidak memicu payout tanpa rekonsiliasi. | `TBD` |
| **TC-31-09** | Scheduler berulang Prioritas: P1 Desain: Review | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. Proses transaksi jatuh tempo dengan job yang sama dua kali, termasuk percobaan berdekatan. | Maksimal satu perubahan bisnis yang sah dan satu notifikasi per event; audit menyimpan urutan benar. | `Review` |
| **TC-31-10** | Waktu server Prioritas: P2 Desain: Review | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. Bandingkan timestamp tersimpan dengan tampilan WIB pada forum, pesan dan transaksi menggunakan satu event acuan. | Konversi waktu konsisten; countdown memakai tenggat server, bukan perubahan jam browser. | `Review` |
| **TC-31-11** | Durasi penanganan komplain Prioritas: P1 Desain: TBD | Server, scheduler, dan queue dapat dikontrol dengan waktu uji. Titik mulai serta aturan tepat pada tenggat disahkan sebelum eksekusi. | 1. Pada kasus aktif, uji tenggat tanggapan dan keputusan setelah nilainya disahkan. | Durasi, peringatan dan eskalasi sesuai keputusan D04; selama belum final tidak diisi nilai asumsi. | `TBD` |

### TS-32 — Profil pengguna (7 Test Cases)

- **Fitur Terkait**: Manajemen Profil, Ubah Username, Password, Avatar
- **Route/Endpoint**: `GET /profile`, `GET /profile/edit`, `PUT /profile`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/ProfileController.php`, `resources/views/profile/`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-32-profil.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-32-01** | Edit profil valid Prioritas: P2 Desain: Review | Dua akun aktif A dan B; profil A tersedia; aturan username, avatar, password tercatat dari kebutuhan yang disahkan. | 1. A ubah nama tampilan/username yang diizinkan<br>2. simpan<br>3. refresh dan login ulang. | Nilai tersimpan dan muncul konsisten pada form serta profil; username tidak berisi markup atribut HTML. | `Review` |
| **TC-32-02** | Username duplikat Prioritas: P2 Desain: Review | Dua akun aktif A dan B; profil A tersedia; aturan username, avatar, password tercatat dari kebutuhan yang disahkan. | 1. A mencoba username B yang sudah ada<br>2. simpan. | Ditolak dengan pesan field yang jelas; username A dan B tidak berubah. | `Review` |
| **TC-32-03** | Username kosong dan batas Prioritas: P2 Desain: TBD | Dua akun aktif A dan B; profil A tersedia; aturan username, avatar, password tercatat dari kebutuhan yang disahkan. | 1. Uji kosong, spasi, panjang min-1/min/max/max+1 serta simbol menurut kontrak yang disahkan. | Hasil per dataset mengikuti aturan username; tidak ada penyimpanan sebagian. | `TBD` |
| **TC-32-04** | Password kosong saat edit Prioritas: P2 Desain: TBD | Dua akun aktif A dan B; profil A tersedia; aturan username, avatar, password tercatat dari kebutuhan yang disahkan. | 1. Ubah nama tampilan<br>2. biarkan kolom password opsional kosong<br>3. simpan dan login dengan password lama. | Password tidak berubah ketika kosong, jika kontrak edit menetapkannya opsional; nama tersimpan. | `TBD` |
| **TC-32-05** | Konfirmasi password Prioritas: P2 Desain: Review | Dua akun aktif A dan B; profil A tersedia; aturan username, avatar, password tercatat dari kebutuhan yang disahkan. | 1. Isi password baru dan konfirmasi berbeda pada akun lokal. | Perubahan password ditolak; password lama tetap berlaku; pesan kesalahan tidak menampilkan password. | `Review` |
| **TC-32-06** | Avatar valid dan tidak valid Prioritas: P2 Desain: TBD | Dua akun aktif A dan B; profil A tersedia; aturan username, avatar, password tercatat dari kebutuhan yang disahkan. | 1. Unggah gambar sah dan setiap jenis/ukuran terlarang menurut batas yang disahkan. | Gambar sah tersimpan; yang melanggar ditolak; avatar lama tetap utuh ketika validasi gagal. | `TBD` |
| **TC-32-07** | Profil pihak lain Prioritas: P1 Desain: Review | Dua akun aktif A dan B; profil A tersedia; aturan username, avatar, password tercatat dari kebutuhan yang disahkan. | 1. A mengirim request edit menggunakan ID B. | Ditolak; profil dan kredensial B tidak berubah. | `Review` |

### TS-33 — Dashboard admin (4 Test Cases)

- **Fitur Terkait**: Pusat Kontrol & Analitik Administrator
- **Route/Endpoint**: `GET /admin`, `GET /admin/analytics`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Admin/AdminDashboardController.php`, `resources/views/admin/`
- **Ketersediaan Fitur**: tersedia
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-33-dashboard-admin.cy.js`

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-33-01** | Akses dashboard Prioritas: P1 Desain: Review | Admin aktif dan USER tersedia; dataset dashboard serta definisi setiap metrik disepakati. | 1. Buka dashboard dengan Admin, USER dan guest pada dataset terpisah. | Admin berwenang masuk; USER/guest tidak memperoleh data admin. Status HTTP/redirect dicocokkan kontrak yang disahkan. | `Review` |
| **TC-33-02** | Angka ringkasan Prioritas: P2 Desain: TBD | Admin aktif dan USER tersedia; dataset dashboard serta definisi setiap metrik disepakati. | 1. Siapkan dataset diketahui<br>2. cocokkan setiap kartu dengan query perhitungan acuan. | Nilai kartu sama dengan hasil perhitungan; definisi transaksi aktif/selesai dan pengguna aktif disahkan sebelum eksekusi. | `TBD` |
| **TC-33-03** | Chart periode Prioritas: P2 Desain: TBD | Admin aktif dan USER tersedia; dataset dashboard serta definisi setiap metrik disepakati. | 1. Siapkan data pada dua batas periode dan dua status<br>2. ubah filter yang tersedia. | Chart memakai periode, zona waktu dan status yang disahkan; label serta total sesuai dataset. | `TBD` |
| **TC-33-04** | Dashboard tanpa data Prioritas: P2 Desain: Review | Admin aktif dan USER tersedia; dataset dashboard serta definisi setiap metrik disepakati. | 1. Gunakan dataset kosong<br>2. buka dashboard. | Tampil keadaan kosong atau nilai nol yang benar; tidak menampilkan angka/grafik contoh sebagai data aktual. | `Review` |

### TS-34 — Payout gagal dan keep (6 Test Cases)

- **Fitur Terkait**: Penanganan Kegagalan Payout, Biaya Keep & Retensi
- **Route/Endpoint**: `Admin Settlement Modules`
- **Lokasi Implementasi Repositori**: `app/Http/Controllers/Admin/AdminModuleController.php`
- **Ketersediaan Fitur**: sebagian (Aturan pembukuan keep TBD D07)
- **Metode Pengujian**: Backend Test & Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-34-keep.cy.js`
- **Catatan Kendala / Keputusan TBD**: Aturan keep rekening nonbank TBD

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-34-01** | Payout gagal karena sistem Prioritas: P1 Desain: TBD | Transaksi layak payout; ada simulasi kegagalan sistem dan tujuan Seller; nilai keep dan biaya transfer dapat ditelusuri. | 1. Simulasikan kegagalan sistem saat payout manual dicatat<br>2. periksa status dan tindak lanjut. | Tidak menjadi COMPLETED; alasan kegagalan tercatat; pengumuman sesuai kebijakan. Saluran/penerima pengumuman disahkan lebih dulu. | `TBD` |
| **TC-34-02** | Payout gagal karena Seller Prioritas: P1 Desain: TBD | Transaksi layak payout; ada simulasi kegagalan sistem dan tujuan Seller; nilai keep dan biaya transfer dapat ditelusuri. | 1. Gunakan tujuan Seller yang ditolak<br>2. tandai hasil investigasi sebagai kesalahan Seller. | Nominal masuk mekanisme keep dan tetap dapat ditelusuri; tidak dianggap transfer berhasil; format ledger keep mengikuti D07. | `TBD` |
| **TC-34-03** | Biaya keep bank Prioritas: P1 Desain: TBD | Transaksi layak payout; ada simulasi kegagalan sistem dan tujuan Seller; nilai keep dan biaya transfer dapat ditelusuri. | 1. Gunakan keep bank K=Rp100.000 dan biaya Rp4.000 setelah arah pembukuan disahkan. | Biaya Rp4.000 diterapkan sekali; nilai sebelum/sesudah sesuai keputusan D07, tanpa potongan tersembunyi. | `TBD` |
| **TC-34-04** | Keep metode lain Prioritas: P1 Desain: TBD | Transaksi layak payout; ada simulasi kegagalan sistem dan tujuan Seller; nilai keep dan biaya transfer dapat ditelusuri. | 1. Uji metode nonbank dengan biaya dasar yang disahkan. | Pengali 3 diterapkan pada dasar biaya yang benar; dasar pengali belum boleh diasumsikan sebagai fee platform/Midtrans. | `TBD` |
| **TC-34-05** | Potongan transaksi berikutnya Prioritas: P1 Desain: TBD | Transaksi layak payout; ada simulasi kegagalan sistem dan tujuan Seller; nilai keep dan biaya transfer dapat ditelusuri. | 1. Seller memiliki keep<br>2. selesaikan transaksi berikutnya lalu proses ulang request. | Keep diperhitungkan sekali dan memiliki referensi transaksi asal/berikutnya; tidak memotong dua kali. | `TBD` |
| **TC-34-06** | Kerahasiaan keep Prioritas: P1 Desain: Review | Transaksi layak payout; ada simulasi kegagalan sistem dan tujuan Seller; nilai keep dan biaya transfer dapat ditelusuri. | 1. A membuka ledger keep B lewat URL/request. | Ditolak; nominal, rekening dan bukti investigasi B tidak bocor. | `Review` |

### TS-35 — Pembatalan transaksi (5 Test Cases)

- **Fitur Terkait**: Pembatalan Transaksi Pre-Payment & Post-Payment (Scope Zinky)
- **Route/Endpoint**: `Transaction Cancellation Endpoints`
- **Lokasi Implementasi Repositori**: `app/Models/Transaction.php`, `app/Services/TransactionService.php`
- **Ketersediaan Fitur**: tersedia (Pre-payment), sebagian (Post-payment TBD D08)
- **Metode Pengujian**: Cypress E2E
- **File Spec Rencana**: `cypress/e2e/ts-35-pembatalan.cy.js`
- **Catatan Kendala / Keputusan TBD**: Aturan potongan pembatalan post-paid TBD D08

| ID TC | Judul & Prioritas | Prasyarat & Aktor | Langkah Pengujian | Expected Result & Assertion | Status Desain |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-35-01** | Buyer batal sebelum bayar Prioritas: P1 Desain: Review | Buyer A dan Seller B; transaksi belum dibayar, sudah dibayar, dan terminal tersedia; kebijakan pembatalan disahkan. | 1. Buyer batalkan WAITING_PAYMENT<br>2. ulangi request. | Pembatalan diterima sekali; tidak ada settlement; histori pernah membatalkan tercatat menurut kebijakan. | `Review` |
| **TC-35-02** | Seller batal sebelum bayar Prioritas: P1 Desain: Review | Buyer A dan Seller B; transaksi belum dibayar, sudah dibayar, dan terminal tersedia; kebijakan pembatalan disahkan. | 1. Seller batalkan transaksi belum dibayar. | Pembatalan sah tercatat; tidak ada payout/refund atas dana yang belum dibayar. | `Review` |
| **TC-35-03** | Pembatalan setelah bayar Prioritas: P1 Desain: TBD | Buyer A dan Seller B; transaksi belum dibayar, sudah dibayar, dan terminal tersedia; kebijakan pembatalan disahkan. | 1. Seller mengajukan pembatalan PAID dengan alasan<br>2. uji Buyer membatalkan secara terpisah. | Hak aktor, pengembalian dana dan potongan mengikuti D08; tidak menganggap seluruh aktor bebas membatalkan setelah bayar. | `TBD` |
| **TC-35-04** | Pembatalan tanpa alasan Prioritas: P1 Desain: TBD | Buyer A dan Seller B; transaksi belum dibayar, sudah dibayar, dan terminal tersedia; kebijakan pembatalan disahkan. | 1. Ajukan pembatalan tanpa alasan pada tahap yang mewajibkan alasan. | Validasi dan penetapan problematik mengikuti keputusan review D08; tidak memberi label berdasarkan asumsi tester. | `TBD` |
| **TC-35-05** | Pembatalan status terminal Prioritas: P1 Desain: Review | Buyer A dan Seller B; transaksi belum dibayar, sudah dibayar, dan terminal tersedia; kebijakan pembatalan disahkan. | 1. Buyer/Seller batalkan COMPLETED atau REFUNDED<br>2. ulangi request bersamaan. | Ditolak; settlement, status final dan riwayat tidak berubah. | `Review` |

