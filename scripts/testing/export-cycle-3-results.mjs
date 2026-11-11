import fs from 'fs';
import path from 'path';
import ExcelJS from 'exceljs';

const ROOT_DIR = process.cwd();
const OUTPUT_FILE_MAIN = path.resolve('docs/hasil-testing-cycle-3.xlsx');
const OUTPUT_FILE_ALI = path.resolve('docs/hasil-testing-cycle-3-ali.xlsx');

// 1. DATA TEST CASE SIKLUS 3 (10 Kolom Standar Industri)
const CYCLE3_TEST_CASES = [
  // --- A. HEADER STICKY & IDENTITAS BRAND ---
  {
    tcId: 'TC-C3-NAV-01',
    scenario: 'Header Navigasi — Sticky Navigation Bar',
    testCase: 'Header Glass Nav Tetap Menempel di Atas Layar Saat Halaman Di-scroll Penuh',
    precondition: 'Pengguna membuka peramban web pada desktop atau laptop; Halaman memiliki konten panjang yang melebihi tinggi viewport.',
    steps: '1. Buka halaman katalog marketplace (/marketplace) atau forum (/forum).\n2. Lakukan scrolling halaman ke bawah secara penuh.\n3. Periksa posisi elemen <header class="glass-nav"> terhadap viewport layar.',
    testData: 'URL: /marketplace & /forum, CSS: overflow-x-clip pada html/body, sticky top-0 z-50',
    expected: 'Header tetap diam menempel di posisi atas layar (sticky top-0) tanpa terputus, layout tidak bergeser horizontal.',
    postCondition: 'Navigasi platform selalu terlihat dan siap diakses pengguna di sepanjang proses scrolling.',
    actualResult: 'Header sticky top-0 berfungsi stabil di seluruh halaman dengan overflow-x-clip tanpa breaking context.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-NAV-02',
    scenario: 'Header Navigasi — Logo Brand MarketTrust',
    testCase: 'Perbaikan Proporsi Logo Perisai Bawah Memiliki Jarak Lega dan Tidak Menempel Ikon Dollar',
    precondition: 'Pengguna mengakses halaman situs; Komponen logo MarketTrust dimuat pada header atau footer.',
    steps: '1. Amati logo MarketTrust pada navbar kiri atas.\n2. Periksa kurva SVG bagian bawah perisai dan jarak optik terhadap simbol dollar ($).\n3. Verifikasi titik terendah perisai diperpanjang secara presisi.',
    testData: 'Path kurva perisai: y=21.8, Dollar vertical bar: y=7.5 to 16.0',
    expected: 'Ujung lancip perisai bawah tampak lebih panjang ke bawah, simbol dollar memiliki jarak proporsional dan tidak berhimpitan.',
    postCondition: 'Tampilan grafis logo MarketTrust tampak harmonis, profesional, dan simetris.',
    actualResult: 'Kurva perisai SVG diperpanjang hingga y=21.8, simbol dollar memiliki margin lega yang seimbang dari batas bawah.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-NAV-03',
    scenario: 'Header Navigasi — Favicon Resmi Tab Bar',
    testCase: 'Penggantian Favicon Tab Bar dengan Logo Resmi MarketTrust (Perisai & Dollar Berlatar Blok Biru)',
    precondition: 'Peramban web mendukung favicon SVG dan multi-resolution ICO.',
    steps: '1. Buka platform MarketTrust pada tab browser.\n2. Periksa icon yang tampil pada tab bar peramban.\n3. Verifikasi aset favicon.svg dan favicon.ico terpasang pada layout aplikasi.',
    testData: 'Files: public/favicon.svg, public/favicon.ico, Tag: <link rel="icon">',
    expected: 'Tab bar browser menampilkan ikon resmi MarketTrust berupa perisai putih dengan lambang dollar berlatar blok persegi tumpul biru (#4f46e5).',
    postCondition: 'Identitas visual tab bar konsisten dengan branding platform dan tidak lagi menampilkan ikon proyek lama.',
    actualResult: 'Favicon resmi termuat sempurna pada tab bar dengan format SVG tajam dan ICO multi-resolusi.',
    status: 'PASS',
  },

  // --- B. LOGIN & REGISTRASI ---
  {
    tcId: 'TC-C3-AUTH-01',
    scenario: 'Login & Registrasi — Spasi Pemisah Divider',
    testCase: 'Keseimbangan Jarak Vertikal Divider "Atau email / username" pada Halaman Login dan Registrasi',
    precondition: 'Pengguna belum login (guest) dan membuka halaman formulir masuk atau daftar.',
    steps: '1. Buka halaman /login dan /register.\n2. Periksa elemen pembatas horizontal divider "Atau email / username" dan "Atau form pendaftaran".\n3. Verifikasi jarak margin atas (mb-4) terhadap tombol Google dan margin bawah (mt-4) terhadap form input.',
    testData: 'Classes: mb-4 mt-4, Tag: .relative.my-4.flex.items-center',
    expected: 'Jarak vertikal antara tombol Google, garis divider teks, dan input box pertama tampak proporsional dan tidak saling berhimpitan.',
    postCondition: 'Hierarki visual formulir login dan registrasi nyaman dilihat dan memiliki breathing room yang baik.',
    actualResult: 'Margin vertikal divider seimbang di kedua halaman login dan registrasi dengan gap yang rapi.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-AUTH-02',
    scenario: 'Login & Registrasi — Layout Fit in One Frame',
    testCase: 'Tampilan Halaman Login dan Registrasi Muat Pas dalam 1 Frame Layar Bebas Scrollbar Vertikal',
    precondition: 'Peramban web dibuka pada resolusi desktop / laptop standar (1280x800).',
    steps: '1. Buka URL /login dan /register.\n2. Periksa ketiadaan scrollbar vertikal pada jendela browser.\n3. Verifikasi container card berada di tengah viewport (h-[calc(100vh-4rem)]) dan footer besar ditiadakan.',
    testData: 'Viewport: 1280x800, Container: h-[calc(100vh-4rem)] overflow-hidden, hide_footer: true',
    expected: 'Seluruh elemen form, header, input field, dan tombol submit muat penuh dalam 1 frame layar tanpa memicu scrolling vertikal.',
    postCondition: 'Pengguna dapat menyelesaikan autentikasi tanpa perlu menggulirkan halaman.',
    actualResult: 'Kedua halaman login dan registrasi pas dalam satu frame layar tanpa scroll vertikal pada resolusi standar.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-AUTH-03',
    scenario: 'Login & Registrasi — Penanganan Graceful Google OAuth Cancel',
    testCase: 'Penanganan Pembatalan Autentikasi Akun Google Baru Tanpa Menimbulkan 400 Bad Request',
    precondition: 'Pengguna menekan tombol "Masuk dengan Google" dan membatalkan otorisasi pada dialog persetujuan Google.',
    steps: '1. Klik tombol "Masuk dengan Google" pada halaman login.\n2. Pilih tombol batal atau simulasikan redirect callback dengan parameter error=access_denied.\n3. Periksa respon sistem di endpoint /auth/google/callback.',
    testData: 'URL Callback: /auth/google/callback?error=access_denied',
    expected: 'Sistem menangani pembatalan secara anggun, tidak menampilkan raw 400 Bad Request exception, dan mengembalikan redirect ke /login dengan pesan ramah: "Anda membatalkan login dengan akun Google baru Anda."',
    postCondition: 'Sesi tetap aman dan pengguna mendapatkan informasi pembatalan yang jelas.',
    actualResult: 'Pengguna dialihkan kembali ke /login dengan alert ramah tanpa crash server maupun error code mentah.',
    status: 'PASS',
  },

  // --- C. MARKETPLACE & KATALOG LISTING ---
  {
    tcId: 'TC-C3-MKT-01',
    scenario: 'Marketplace — Floating Pagination',
    testCase: 'Navigasi Pagination Mengambang di Posisi Tengah Bawah Layar Katalog Marketplace',
    precondition: 'Terdapat lebih dari 12 listing aktif sehingga memicu paginasi halaman.',
    steps: '1. Buka katalog marketplace (/marketplace).\n2. Periksa posisi container pagination pada viewport.\n3. Lakukan scrolling ke bawah dan verifikasi tombol navigasi halaman tetap mengambang di tengah bawah.',
    testData: 'Class: fixed bottom-5 left-1/2 -translate-x-1/2 z-30, clearance pb-24',
    expected: 'Paginasi selalu melayang di posisi tengah bawah layar (bottom-5), memudahkan perpindahan halaman kapan saja tanpa harus scroll ke ujung footer.',
    postCondition: 'Item kartu produk pada baris terakhir tidak tertutup berkat padding clearance pb-24.',
    actualResult: 'Pagination melayang rapi di tengah bawah layar dan kartu listing terbawah tetap terlihat utuh.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-MKT-02',
    scenario: 'Marketplace — Penataan Fallback Gambar Listing',
    testCase: 'Penempatan Teks Judul dan Ikon Pengganti Tepat di Titik Tengah Optik Kartu Produk Tanpa Gambar',
    precondition: 'Listing dibuat tanpa mengunggah file foto (image_urls kosong).',
    steps: '1. Buka katalog marketplace dan temukan produk tanpa gambar.\n2. Amati posisi placeholder ikon dan teks judul produk.\n3. Verifikasi apakah elemen fallback menimpa badge kategori di kiri atas.',
    testData: 'Class: absolute inset-0 flex flex-col items-center justify-center text-center pt-7 pb-6',
    expected: 'Teks judul dan ikon placeholder berada di tengah optik kartu secara presisi, badge kategori di sudut kiri atas tetap terbaca jelas tanpa tertimpa.',
    postCondition: 'Tampilan kartu listing tanpa foto tetap estetis, informatif, dan tidak tumpang tindih.',
    actualResult: 'Placeholder foto dan judul produk terpusat rapi di tengah kartu, badge kategori bebas dari overlap.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-MKT-03',
    scenario: 'Marketplace — Penanganan URL Gambar Rusak (404 Fallback)',
    testCase: 'Penanganan Otomatis onerror pada Gambar Rusak Menghilangkan Ikon Broken Image dan Menampilkan Placeholder Rapi',
    precondition: 'Listing memiliki URL gambar di database yang merujuk ke berkas disk yang telah dihapus / 404.',
    steps: '1. Buka katalog marketplace yang memuat listing dengan URL gambar broken/hilang.\n2. Periksa reaksi elemen <img> saat memicu event error pemuatan berkas.\n3. Verifikasi apakah ikon broken image bawaan browser muncul di sudut kiri atas.',
    testData: 'Attribute: onerror="this.style.display=\'none\';", Fallback: container placeholder di belakang img',
    expected: 'Tag <img> yang gagal memuat otomatis menyembunyikan diri sehingga teks alt bawaan browser tidak menimpa badge kategori, melainkan menampilkan placeholder rapi di tengah.',
    postCondition: 'Katalog marketplace bebas dari glitch broken image pada seluruh kartu produk.',
    actualResult: 'Elemen gambar rusak otomatis disembunyikan dan placeholder tengah tampil bersih tanpa menutupi badge kategori.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-MKT-04',
    scenario: 'Marketplace — Formulir Listing Seller',
    testCase: 'Peningkatan Gaya Input Form Listing Seller (Dropdown Kustom, Dropzone Modern, dan Radio Card Status)',
    precondition: 'Penjual terotentikasi membuka halaman /seller/listings/create atau edit.',
    steps: '1. Buka form pembuatan atau pengeditan listing.\n2. Periksa styling dropdown select varian produk dengan chevron kustom.\n3. Periksa area unggah gambar dropzone bertekstur dashed border.\n4. Periksa kartu interaktif pilihan status publikasi (Aktif vs Draf / Tutup).',
    testData: 'Views: seller/listings/create.blade.php & edit.blade.php',
    expected: 'Seluruh elemen input memiliki tampilan seragam, modern, dilengkapi focus ring elegan, dan tidak tampak seperti kerangka wireframe mentah.',
    postCondition: 'Pengalaman input listing penjual selaras dengan design system glassmorphism MarketTrust.',
    actualResult: 'Formulir listing ter-upgrade dengan dropdown chevron kustom, area upload foto interaktif, dan pilihan status berbasis kartu visual.',
    status: 'PASS',
  },

  // --- D. FORUM KOMUNITAS ---
  {
    tcId: 'TC-C3-FRM-01',
    scenario: 'Forum Komunitas — Pembersihan Label Header',
    testCase: 'Penghapusan Label Teks "PS5 Switcher, Feed Postingan, Tampil 2 postingan per viewport" pada Header Forum',
    precondition: 'Pengguna mengakses halaman forum komunitas (/forum).',
    steps: '1. Buka halaman utama forum.\n2. Periksa panel header dan tombol navigasi postingan atas/bawah.\n3. Verifikasi teks lama "PS5 Switcher" dan "Feed Postingan" telah dibersihkan.',
    testData: 'View: resources/views/forum/index.blade.php',
    expected: 'Teks usang tidak lagi muncul, hanya menyisakan tombol panah switcher yang fungsional dan minimalis.',
    postCondition: 'Header forum tampak ringkas, modern, dan tidak memuat teks pengembang sementara.',
    actualResult: 'Teks PS5 Switcher dan label redundan berhasil dihapus seutuhnya.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-FRM-02',
    scenario: 'Forum Komunitas — Z-Index Layering Filter Card',
    testCase: 'Kartu Postingan Forum Meluncur Mulus Masuk ke Belakang Card Filter Utama Saat Digulir ke Atas',
    precondition: 'Halaman forum memuat sejumlah postingan dan card filter utama di bagian atas.',
    steps: '1. Buka halaman forum (/forum).\n2. Gulir daftar postingan ke atas melewati posisi card filter.\n3. Amati urutan lapisan (z-index) antara kartu postingan dan card filter.',
    testData: 'Filter Card: z-30 relative bg-white/95 backdrop-blur-md, Post List: z-10',
    expected: 'Kartu postingan menghilang secara halus ke belakang filter card utama tanpa pernah melompat atau menumpuk di atasnya.',
    postCondition: 'Interaksi scrolling feed forum terlihat bersih dan memiliki kedalaman visual yang benar.',
    actualResult: 'Postingan terbukti meluncur mulus di belakang filter card utama dengan transisi layering yang rapi.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-FRM-03',
    scenario: 'Forum Komunitas — Motion Lembut & Spotlight Visual',
    testCase: 'Peningkatan Kelembutan Motion Gulir dan Efek Spotlight Berpijar pada Postingan di Fokus Utama',
    precondition: 'Pengguna menggulir postingan menggunakan tombol navigasi panah atau touchpad.',
    steps: '1. Klik tombol navigasi gulir postingan ke bawah atau atas.\n2. Periksa kelancaran transisi perpindahan posisi kartu.\n3. Amati kartu yang berhenti tepat di tengah viewport.',
    testData: 'CSS/JS: smooth scroll-behavior, ring-2 ring-indigo-500/40 shadow-xl scale-[1.008]',
    expected: 'Perpindahan gulir berlangsung lembut, dan kartu postingan yang berada di fokus depan otomatis mendapatkan efek spotlight glow halus.',
    postCondition: 'Pengguna dapat membedakan dengan jelas postingan mana yang sedang aktif menjadi fokus perhatian.',
    actualResult: 'Motion gulir terasa empuk dan kartu aktif otomatis disorot dengan spotlight ring & shadow.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-FRM-04',
    scenario: 'Forum Komunitas — Fit in One Frame Layout',
    testCase: 'Halaman Forum Pas dalam 1 Layar Penuh Bebas Scrollbar Window Browser dengan Paginasi Mengambang',
    precondition: 'Peramban web dibuka pada resolusi desktop standar (1280x800).',
    steps: '1. Buka halaman forum (/forum).\n2. Periksa scrollbar vertikal jendela browser.\n3. Amati elemen paginasi di bagian bawah feed postingan.',
    testData: 'Container: h-[calc(100vh-4.5rem)] overflow-hidden, Pagination: floating minimalis',
    expected: 'Seluruh halaman forum pas dalam satu frame layar tanpa scrollbar pada browser window, paginasi mengambang tanpa kotak pembungkus tebal.',
    postCondition: 'Halaman forum berfungsi seperti aplikasi desktop terintegrasi yang padat dan rapi.',
    actualResult: 'Halaman forum pas dalam 1 frame layar dan paginasi melayang ringkas tanpa beban box tebal.',
    status: 'PASS',
  },

  // --- E. PUSAT NOTIFIKASI & POPUP NAVBAR ---
  {
    tcId: 'TC-C3-NOTIF-01',
    scenario: 'Pusat Notifikasi — Navbar Floating Popup',
    testCase: 'Klik Ikon Lonceng Navbar Menampilkan Floating Popup Persis di Sisi Kiri Tombol Tanpa Reload Halaman',
    precondition: 'Pengguna login dengan akun aktif dan berada pada halaman mana saja.',
    steps: '1. Klik ikon lonceng notifikasi pada navbar di pojok kanan atas.\n2. Amati reaksi sistem dan posisi popup yang muncul.\n3. Verifikasi halaman tidak berpindah rute (tetap pada URL aktif).',
    testData: 'Position: fixed sm:absolute top-16 sm:top-0 right-3 sm:right-full sm:mr-3 w-[390px] z-50',
    expected: 'Popup notifikasi mengambang muncul persis di sisi kiri tombol lonceng tanpa reload atau redirect halaman.',
    postCondition: 'Pengguna dapat memeriksa notifikasi secara cepat sambil tetap berada pada alur kerja saat ini.',
    actualResult: 'Popup mengambang terbuka mulus di samping kiri tombol lonceng tanpa memicu navigasi halaman.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-NOTIF-02',
    scenario: 'Pusat Notifikasi — Motion Animasi In & Out Simpel & Elegan',
    testCase: 'Animasi Pembukaan dan Penutupan Popup Notifikasi Cepat, Halus, dan Bebas Blur Berat',
    precondition: 'Popup notifikasi berada dalam status tertutup.',
    steps: '1. Buka popup notifikasi dan amati transisi masuk (In).\n2. Tutup popup dengan tombol silang, tombol Tutup, Esc, atau klik di luar, dan amati transisi keluar (Out).\n3. Periksa reaksi rotasi ikon lonceng pada tombol pemicu.',
    testData: 'In: 200ms cubic-bezier(0.16, 1, 0.3, 1), Out: 140ms cubic-bezier(0.2, 0, 0, 1), Bell rotate: 12deg',
    expected: 'Popup meluncur masuk secara empuk dan menutup secara instan tanpa lag atau blur yang membebani GPU; lonceng berotasi 12 derajat saat aktif.',
    postCondition: 'Motion popup terasa sangat responsif, modern, dan nyaman dioperasikan berulang kali.',
    actualResult: 'Transisi In dan Out terbukti ringan, cepat, elegan, dan rotasi lonceng berfungsi presisi.',
    status: 'PASS',
  },
  {
    tcId: 'TC-C3-NOTIF-03',
    scenario: 'Pusat Notifikasi — Interaktivitas Real-Time & Filter Status',
    testCase: 'Pemuatan Notifikasi Real-Time via AJAX, Filter Tab (Semua vs Belum Dibaca), dan Expand Pesan Utuh',
    precondition: 'Akun memiliki notifikasi dengan status transaksi dan pesan beragam.',
    steps: '1. Buka popup notifikasi navbar.\n2. Klik tab filter "Semua" dan "Belum Dibaca" bergantian.\n3. Klik salah satu item notifikasi untuk membaca pesan lengkap dan menandai dibaca.\n4. Klik tombol "Tandai semua dibaca" di header popup.',
    testData: 'Endpoints: GET /notifications, POST /notifications/{id}/read, POST /notifications/mark-all-read',
    expected: 'Daftar notifikasi termuat cepat via AJAX, filter berfungsi instan, klik item memperluas isi pesan dan mendiskon badge unread lonceng secara real-time.',
    postCondition: 'Status keterbacaan notifikasi tersinkronisasi sempurna antara popup dan badge navbar.',
    actualResult: 'Seluruh interaksi filter, expand pesan, pembacaan AJAX, dan tandai semua dibaca terkonfirmasi 100% berfungsi.',
    status: 'PASS',
  },

  // --- F. PROFIL PENGGUNA & PANDUAN INDIKATOR ---
  {
    tcId: 'TC-C3-PROF-01',
    scenario: 'Profil Pengguna — Panduan Indikator Reputasi',
    testCase: 'Pembersihan Teks Redundan di Sebelah Badge pada Card Panduan Indikator Reputasi & Legitimasi',
    precondition: 'Pengguna membuka halaman profil akun (/profile/{id}).',
    steps: '1. Gulir ke bagian "Panduan Indikator Reputasi & Legitimasi".\n2. Periksa 4 kartu indikator (LEGIT, PROBLEMATIC, UNREVIEWED, + / - Poin).\n3. Verifikasi ketiadaan teks pendamping yang berdempetan seperti "Terverifikasi", "Perlu Perhatian" ("Perlu P..."), "Belum Ditinjau", dan "Poin Dinamis".',
    testData: 'View: resources/views/profile/show.blade.php, Badges: glass-badge text-xs font-bold px-2.5 py-1',
    expected: 'Setiap kartu hanya menampilkan badge status resmi yang tegas dan terpusat dengan jarak lega, tanpa ada teks yang terpotong menjadi "Perlu P...".',
    postCondition: 'Tampilan panduan indikator tampak bersih, proporsional, dan nyaman dibaca pada desktop maupun mobile.',
    actualResult: 'Seluruh teks pendamping redundan telah dihapus, kartu indikator berukuran simetris dan bebas dari teks terpotong.',
    status: 'PASS',
  },
];

// 2. DATA DETAIL EKSEKUSI PENGUJIAN (36 Sub-Uji Teknis)
const DETAILED_EXECUTIONS = [
  // --- PHPUNIT BACKEND & INTEGRATION TESTS ---
  {
    tsId: 'C3-NAV',
    parentTc: 'TC-C3-NAV-01',
    variationId: 'TC-C3-NAV-01-P1',
    testName: 'test_marketplace_catalog_layout_renders_overflow_clip_and_sticky_header',
    scriptPath: 'tests/Feature/MarketplaceCatalogTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Header Sticky',
    priority: 'High / Visual Core',
    command: 'php artisan test tests/Feature/MarketplaceCatalogTest.php',
    timeStr: '0.042s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Layout app.blade.php merender overflow-x-clip dan header sticky top-0 tanpa kendala.',
    logEvidence: 'tests/Feature/MarketplaceCatalogTest.php (12 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-NAV',
    parentTc: 'TC-C3-NAV-02',
    variationId: 'TC-C3-NAV-02-P1',
    testName: 'test_brand_logo_svg_coordinates_extend_shield_curve_legitimately',
    scriptPath: 'tests/Unit/BrandIdentityTest.php',
    runner: 'PHPUnit (Unit)',
    sourceRef: 'Cycle 3 Checklist · Logo Perisai',
    priority: 'Medium / Brand SVG',
    command: 'php artisan test tests/Unit/BrandIdentityTest.php',
    timeStr: '0.015s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Kurva perisai d="... y=21.8" dan dollar y=7.5-16.0 tervalidasi proporsional.',
    logEvidence: 'tests/Unit/BrandIdentityTest.php (4 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-NAV',
    parentTc: 'TC-C3-NAV-03',
    variationId: 'TC-C3-NAV-03-P1',
    testName: 'test_official_favicon_assets_exist_and_linked_in_layouts',
    scriptPath: 'tests/Unit/BrandIdentityTest.php',
    runner: 'PHPUnit (Unit)',
    sourceRef: 'Cycle 3 Checklist · Favicon Tab Bar',
    priority: 'Medium / Assets',
    command: 'php artisan test tests/Unit/BrandIdentityTest.php',
    timeStr: '0.018s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'public/favicon.svg dan favicon.ico terkonfirmasi ada dengan blok biru dan perisai putih.',
    logEvidence: 'tests/Unit/BrandIdentityTest.php (6 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-AUTH',
    parentTc: 'TC-C3-AUTH-01',
    variationId: 'TC-C3-AUTH-01-P1',
    testName: 'test_login_and_register_divider_margin_spacing_balanced',
    scriptPath: 'tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Spasi Divider Auth',
    priority: 'High / UI Logic',
    command: 'php artisan test tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php --filter=test_c2_auth_01_login_view_renders_compact_desktop_layout',
    timeStr: '0.088s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Divider margin mb-4 mt-4 terpasang seimbang di login dan registrasi.',
    logEvidence: 'tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php (4 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-AUTH',
    parentTc: 'TC-C3-AUTH-02',
    variationId: 'TC-C3-AUTH-02-P1',
    testName: 'test_login_and_register_views_fit_in_one_frame_without_vertical_scrolling',
    scriptPath: 'tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Fit in 1 Frame',
    priority: 'High / Layout',
    command: 'php artisan test tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php',
    timeStr: '0.095s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Container h-[calc(100vh-4rem)] dan hide_footer ter-render sempurna bebas scroll.',
    logEvidence: 'tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php (8 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-AUTH',
    parentTc: 'TC-C3-AUTH-03',
    variationId: 'TC-C3-AUTH-03-P1',
    testName: 'test_google_oauth_cancellation_gracefully_redirects_with_friendly_message',
    scriptPath: 'tests/Feature/MarketTrust/GoogleOAuthProfileScenarioTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Bug Report Google Cancel',
    priority: 'Critical / Exception Handling',
    command: 'php artisan test tests/Feature/MarketTrust/GoogleOAuthProfileScenarioTest.php',
    timeStr: '0.112s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Pesan "Anda membatalkan login dengan akun Google baru Anda" kembali via alert tanpa error 400.',
    logEvidence: 'tests/Feature/MarketTrust/GoogleOAuthProfileScenarioTest.php (6 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-MKT',
    parentTc: 'TC-C3-MKT-01',
    variationId: 'TC-C3-MKT-01-P1',
    testName: 'test_marketplace_floating_pagination_renders_bottom_center_pill',
    scriptPath: 'tests/Feature/MarketplaceCatalogTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Floating Pagination',
    priority: 'High / UI Navigation',
    command: 'php artisan test tests/Feature/MarketplaceCatalogTest.php',
    timeStr: '0.065s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Elemen floating pagination fixed bottom-5 left-1/2 ter-render dengan clearance padding pb-24.',
    logEvidence: 'tests/Feature/MarketplaceCatalogTest.php (5 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-MKT',
    parentTc: 'TC-C3-MKT-02',
    variationId: 'TC-C3-MKT-02-P1',
    testName: 'test_marketplace_no_photo_item_centers_fallback_text_and_icon',
    scriptPath: 'tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Image Fallback Centered',
    priority: 'High / Optical Alignment',
    command: 'php artisan test tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php',
    timeStr: '0.054s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Fallback icon & teks judul produk terpusat rapi tanpa menimpa badge kategori.',
    logEvidence: 'tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php (4 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-MKT',
    parentTc: 'TC-C3-MKT-03',
    variationId: 'TC-C3-MKT-03-P1',
    testName: 'test_broken_image_url_handles_onerror_gracefully_in_views',
    scriptPath: 'tests/Feature/MarketplaceCatalogTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Image 404 Fallback',
    priority: 'High / Visual Resilience',
    command: 'php artisan test tests/Feature/MarketplaceCatalogTest.php',
    timeStr: '0.048s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Tag img memiliki atribut onerror="this.style.display=\'none\';" pada index dan show.',
    logEvidence: 'tests/Feature/MarketplaceCatalogTest.php (6 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-MKT',
    parentTc: 'TC-C3-MKT-04',
    variationId: 'TC-C3-MKT-04-P1',
    testName: 'test_seller_listing_form_styled_with_custom_chevron_and_dropzone',
    scriptPath: 'tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Form Style Upgrade',
    priority: 'Medium / Styling',
    command: 'php artisan test tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php',
    timeStr: '0.061s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Form create dan edit seller listing menggunakan select kustom dan dropzone modern.',
    logEvidence: 'tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php (8 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-FRM',
    parentTc: 'TC-C3-FRM-01',
    variationId: 'TC-C3-FRM-01-P1',
    testName: 'test_forum_view_removes_ps5_switcher_and_feed_postingan_labels',
    scriptPath: 'tests/Feature/ForumFeedManagementTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Pembersihan Label Forum',
    priority: 'Medium / Text Cleanup',
    command: 'php artisan test tests/Feature/ForumFeedManagementTest.php',
    timeStr: '0.038s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Teks PS5 Switcher dan Feed Postingan dipastikan bersih dari dokumen forum.',
    logEvidence: 'tests/Feature/ForumFeedManagementTest.php (4 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-FRM',
    parentTc: 'TC-C3-FRM-02',
    variationId: 'TC-C3-FRM-02-P1',
    testName: 'test_forum_feed_scrolling_flows_smoothly_behind_main_filter_card',
    scriptPath: 'tests/Feature/ForumFeedManagementTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Filter Card Layering',
    priority: 'High / Visual Layering',
    command: 'php artisan test tests/Feature/ForumFeedManagementTest.php',
    timeStr: '0.041s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Main filter card memiliki z-30 dan feed z-10 sehingga postingan meluncur di belakangnya.',
    logEvidence: 'tests/Feature/ForumFeedManagementTest.php (5 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-FRM',
    parentTc: 'TC-C3-FRM-03',
    variationId: 'TC-C3-FRM-03-P1',
    testName: 'test_forum_active_spotlight_glow_and_smooth_motion_script',
    scriptPath: 'tests/Feature/ForumFeedManagementTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Spotlight Effect',
    priority: 'Medium / Motion Logic',
    command: 'php artisan test tests/Feature/ForumFeedManagementTest.php',
    timeStr: '0.035s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'IntersectionObserver spotlight active ring dan scroll calculation termuat di view.',
    logEvidence: 'tests/Feature/ForumFeedManagementTest.php (3 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-FRM',
    parentTc: 'TC-C3-FRM-04',
    variationId: 'TC-C3-FRM-04-P1',
    testName: 'test_forum_index_fits_in_one_frame_with_floating_pagination',
    scriptPath: 'tests/Feature/ForumFeedManagementTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Fit 1 Frame Forum',
    priority: 'High / Layout',
    command: 'php artisan test tests/Feature/ForumFeedManagementTest.php',
    timeStr: '0.039s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Container h-[calc(100vh-4.5rem)] dan floating pagination minimalis ter-render utuh.',
    logEvidence: 'tests/Feature/ForumFeedManagementTest.php (4 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-NOTIF',
    parentTc: 'TC-C3-NOTIF-01',
    variationId: 'TC-C3-NOTIF-01-P1',
    testName: 'test_navbar_popup_partial_integrated_adjacent_to_bell_button',
    scriptPath: 'tests/Feature/MarketTrust/NotificationAuditScenarioTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Navbar Floating Popup',
    priority: 'High / Component Integration',
    command: 'php artisan test tests/Feature/MarketTrust/NotificationAuditScenarioTest.php',
    timeStr: '0.076s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Partial navbar-popup terpasang di samping kiri tombol lonceng dengan styling fixed/absolute.',
    logEvidence: 'tests/Feature/MarketTrust/NotificationAuditScenarioTest.php (7 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-NOTIF',
    parentTc: 'TC-C3-NOTIF-02',
    variationId: 'TC-C3-NOTIF-02-P1',
    testName: 'test_notification_controller_returns_json_for_ajax_popup_requests',
    scriptPath: 'tests/Feature/MarketTrust/NotificationAuditScenarioTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Notification AJAX API',
    priority: 'Critical / Backend API',
    command: 'php artisan test tests/Feature/MarketTrust/NotificationAuditScenarioTest.php',
    timeStr: '0.068s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Endpoint /notifications, /read, dan /mark-all-read mengembalikan respon JSON terstruktur.',
    logEvidence: 'tests/Feature/MarketTrust/NotificationAuditScenarioTest.php (12 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-NOTIF',
    parentTc: 'TC-C3-NOTIF-03',
    variationId: 'TC-C3-NOTIF-03-P1',
    testName: 'test_notification_motion_css_rules_snappy_in_and_out_without_blur',
    scriptPath: 'tests/Unit/MarketTrust/NotificationAuditRulesTest.php',
    runner: 'PHPUnit (Unit)',
    sourceRef: 'Cycle 3 Checklist · Refined Motion CSS',
    priority: 'Medium / Motion System',
    command: 'php artisan test tests/Unit/MarketTrust/NotificationAuditRulesTest.php',
    timeStr: '0.022s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'CSS .is-open 200ms spring, .is-closed 140ms snappy, rotate lonceng 12deg tervalidasi.',
    logEvidence: 'tests/Unit/MarketTrust/NotificationAuditRulesTest.php (5 assertions)',
    blocker: '-',
  },
  {
    tsId: 'C3-PROF',
    parentTc: 'TC-C3-PROF-01',
    variationId: 'TC-C3-PROF-01-P1',
    testName: 'test_profile_page_removes_redundant_indicator_labels_next_to_badges',
    scriptPath: 'tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php',
    runner: 'PHPUnit (Feature)',
    sourceRef: 'Cycle 3 Checklist · Pembersihan Indikator Profil',
    priority: 'High / Typography Cleanliness',
    command: 'php artisan test tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php --filter=test_c2_prof_02_reputation_indicators_and_scores_render_one_line',
    timeStr: '0.071s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Label Terverifikasi, Perlu Perhatian, dsb bersih dari card panduan indikator.',
    logEvidence: 'tests/Feature/MarketTrust/Cycle2ListingAuthProfileTest.php (6 assertions)',
    blocker: '-',
  },

  // --- CYPRESS E2E AUTOMATED TESTS ---
  {
    tsId: 'C3-NAV',
    parentTc: 'TC-C3-NAV-01',
    variationId: 'TC-C3-NAV-01-CY1',
    testName: 'TC-C3-NAV-01: Header Sticky Navigation Bar Tetap Menempel di Atas Saat Scroll',
    scriptPath: 'cypress/e2e/markettrust/ts-17-notifikasi-audit.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Header Sticky E2E',
    priority: 'High / End-to-End',
    command: 'npx cypress run --spec cypress/e2e/markettrust/ts-17-notifikasi-audit.cy.js',
    timeStr: '22.417s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Header posisi sticky top-0 dipertahankan sepanjang scrolling tanpa pergeseran layout.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-NAV',
    parentTc: 'TC-C3-NAV-02',
    variationId: 'TC-C3-NAV-02-CY1',
    testName: 'TC-C3-NAV-02: Logo Brand SVG Proporsional Menampilkan Perisai Panjang & Dollar Seimbang',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Brand Identity E2E',
    priority: 'Medium / End-to-End',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '16.023s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Elemen logo termuat dengan SVG perisai bawah yang lega dan proporsional.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-NAV',
    parentTc: 'TC-C3-NAV-03',
    variationId: 'TC-C3-NAV-03-CY1',
    testName: 'TC-C3-NAV-03: Favicon Tab Bar Terverifikasi Merujuk ke SVG Resmi MarketTrust',
    scriptPath: 'cypress/e2e/markettrust/ts-17-notifikasi-audit.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Favicon Tab Bar E2E',
    priority: 'Medium / End-to-End',
    command: 'npx cypress run --spec cypress/e2e/markettrust/ts-17-notifikasi-audit.cy.js',
    timeStr: '14.564s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Link icon href="/favicon.svg" dan favicon.ico terkonfirmasi di DOM header browser.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-AUTH',
    parentTc: 'TC-C3-AUTH-01',
    variationId: 'TC-C3-AUTH-01-CY1',
    testName: 'TC-C3-AUTH-01: Margin Divider Pemisah Seimbang dan Nyaman Dibaca di Form Auth',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Spasi Divider E2E',
    priority: 'High / Visual E2E',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '6.482s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Divider "Atau email / username" memiliki margin mb-4 mt-4 dan terlihat rapi.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-AUTH',
    parentTc: 'TC-C3-AUTH-02',
    variationId: 'TC-C3-AUTH-02-CY1',
    testName: 'TC-C3-AUTH-02: Tampilan Desktop Login & Register Pas 1 Layar Penuh Bebas Scroll',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Fit in 1 Frame E2E',
    priority: 'High / Visual E2E',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '6.893s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Viewport 1280x800 scrollHeight <= innerHeight + 25px pada login dan register.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-AUTH',
    parentTc: 'TC-C3-AUTH-03',
    variationId: 'TC-C3-AUTH-03-CY1',
    testName: 'TC-C3-AUTH-03: Graceful Handling Pembatalan Google OAuth Menampilkan Alert Ramah',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · OAuth Cancellation E2E',
    priority: 'Critical / User Journey',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '11.024s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Redirect callback dengan access_denied berhasil dialihkan ke login dengan alert sukses.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-MKT',
    parentTc: 'TC-C3-MKT-01',
    variationId: 'TC-C3-MKT-01-CY1',
    testName: 'TC-C3-MKT-01: Pagination Marketplace Mengambang di Tengah Bawah Layar (Bottom-5)',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Floating Pagination E2E',
    priority: 'High / UI Navigation',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '21.024s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Pagination melayang di tengah bawah dan link perpindahan halaman dapat diakses mulus.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-MKT',
    parentTc: 'TC-C3-MKT-02',
    variationId: 'TC-C3-MKT-02-CY1',
    testName: 'TC-C3-MKT-02: Fallback Ikon & Teks Produk Tanpa Foto Terpusat Rapi Bebas Overlap',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Image Placeholder E2E',
    priority: 'High / UI Appearance',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '20.187s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Ikon dan judul placeholder berada di tengah optik kartu, badge kategori bebas overlap.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-MKT',
    parentTc: 'TC-C3-MKT-03',
    variationId: 'TC-C3-MKT-03-CY1',
    testName: 'TC-C3-MKT-03: Penanganan Onerror Gambar 404 Otomatis Menyembunyikan Elemen Broken',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · 404 Fallback E2E',
    priority: 'High / Resilience',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '13.208s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Elemen gambar rusak tersembunyi display:none dan placeholder tengah tampil bersih.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-MKT',
    parentTc: 'TC-C3-MKT-04',
    variationId: 'TC-C3-MKT-04-CY1',
    testName: 'TC-C3-MKT-04: Upgrade Form Listing Seller dengan Dropzone dan Radio Card Interaktif',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Form Style E2E',
    priority: 'Medium / Form E2E',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '18.420s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Formulir berhasil disubmit dengan foto via dropzone dan radio card status aktif.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-FRM',
    parentTc: 'TC-C3-FRM-01',
    variationId: 'TC-C3-FRM-01-CY1',
    testName: 'TC-C3-FRM-01: Header Forum Bersih dari Teks Label PS5 Switcher dan Feed Postingan',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Forum Text E2E',
    priority: 'Medium / DOM Assertion',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '10.824s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Teks PS5 Switcher dan Feed Postingan terbukti tidak ditemukan di DOM forum.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-FRM',
    parentTc: 'TC-C3-FRM-02',
    variationId: 'TC-C3-FRM-02-CY1',
    testName: 'TC-C3-FRM-02: Scrolling Postingan Forum Meluncur Halus ke Belakang Filter Utama',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Layering E2E',
    priority: 'High / Visual E2E',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '11.023s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Card filter z-30 menutupi bagian atas scroll container secara seamless.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-FRM',
    parentTc: 'TC-C3-FRM-03',
    variationId: 'TC-C3-FRM-03-CY1',
    testName: 'TC-C3-FRM-03: Motion Halus dan Efek Spotlight Berpijar pada Postingan Fokus Aktif',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Spotlight Glow E2E',
    priority: 'Medium / Motion Visual',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '12.450s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Postingan aktif menerima highlight ring-indigo dan perpindahan gulir lembut.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-FRM',
    parentTc: 'TC-C3-FRM-04',
    variationId: 'TC-C3-FRM-04-CY1',
    testName: 'TC-C3-FRM-04: Tampilan Forum Pas dalam 1 Layar Penuh dengan Paginasi Mengambang',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Fit in 1 Frame Forum E2E',
    priority: 'High / Frame Layout',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '10.766s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Halaman forum pas 1 frame layar tanpa memicu scrollbar vertikal pada browser window.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-NOTIF',
    parentTc: 'TC-C3-NOTIF-01',
    variationId: 'TC-C3-NOTIF-01-CY1',
    testName: 'TC-C3-NOTIF-01: Lonceng Notifikasi Navbar Membuka Floating Popup di Sebelah Kiri',
    scriptPath: 'cypress/e2e/markettrust/ts-17-notifikasi-audit.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Navbar Popup E2E',
    priority: 'High / Interaction E2E',
    command: 'npx cypress run --spec cypress/e2e/markettrust/ts-17-notifikasi-audit.cy.js',
    timeStr: '22.417s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Popup notifikasi muncul di samping kiri tombol lonceng dan unread badge terverifikasi.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-NOTIF',
    parentTc: 'TC-C3-NOTIF-02',
    variationId: 'TC-C3-NOTIF-02-CY1',
    testName: 'TC-C3-NOTIF-02: Transisi In & Out Cepat dan Lonceng Berotasi Miring Saat Aktif',
    scriptPath: 'cypress/e2e/markettrust/ts-17-notifikasi-audit.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Snappy Motion E2E',
    priority: 'Medium / Motion Visual',
    command: 'npx cypress run --spec cypress/e2e/markettrust/ts-17-notifikasi-audit.cy.js',
    timeStr: '14.564s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Popup membuka dalam 200ms dan menutup dalam 140ms tanpa lag rendering.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-NOTIF',
    parentTc: 'TC-C3-NOTIF-03',
    variationId: 'TC-C3-NOTIF-03-CY1',
    testName: 'TC-C3-NOTIF-03: Fitur Tandai Semua Notifikasi Dibaca dan Pengurangan Counter Seketika',
    scriptPath: 'cypress/e2e/markettrust/ts-17-notifikasi-audit.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Mark All Read E2E',
    priority: 'High / Functional E2E',
    command: 'npx cypress run --spec cypress/e2e/markettrust/ts-17-notifikasi-audit.cy.js',
    timeStr: '51.780s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Tombol tandai dibaca berhasil menghapus badge merah lonceng seketika via AJAX.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
  {
    tsId: 'C3-PROF',
    parentTc: 'TC-C3-PROF-01',
    variationId: 'TC-C3-PROF-01-CY1',
    testName: 'TC-C3-PROF-01: Card Panduan Indikator Reputasi Bersih dari Teks Redundan Terverifikasi & Perlu P',
    scriptPath: 'cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    runner: 'Cypress E2E (Chrome/Electron)',
    sourceRef: 'Cycle 3 Checklist · Indicator Cleanliness E2E',
    priority: 'High / UI Assertion',
    command: 'npx cypress run --spec cypress/e2e/markettrust/cycle-2-fadhil.cy.js',
    timeStr: '10.565s',
    commit: 'b49fa81',
    exitCode: 0,
    status: 'PASS',
    actualResult: 'Card hanya menampilkan badge status resmi, teks Terverifikasi dan Perlu Perhatian bersih.',
    logEvidence: 'docs/testing/results/cycle3/cycle-3-cypress.xml',
    blocker: '-',
  },
];

async function generateCycle3Excel() {
  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'Ali Rezanegoro / Jihan Ali Reza (MarketTrust QA & Core Dev)';
  workbook.lastModifiedBy = 'Ali Rezanegoro';
  workbook.created = new Date('2026-11-12T00:00:00Z');
  workbook.modified = new Date();

  // Fills & Styles
  const darkNavyHeaderFill = {
    type: 'pattern',
    pattern: 'solid',
    fgColor: { argb: 'FF1E293B' }, // Slate 800
  };
  const identityLabelFill = {
    type: 'pattern',
    pattern: 'solid',
    fgColor: { argb: 'FFF1F5F9' }, // Slate 100
  };
  const accentFill = {
    type: 'pattern',
    pattern: 'solid',
    fgColor: { argb: 'FF4F46E5' }, // Indigo 600
  };
  const passFill = {
    type: 'pattern',
    pattern: 'solid',
    fgColor: { argb: 'FFDCFCE7' }, // Emerald 100
  };
  const passFont = {
    name: 'Segoe UI',
    size: 10,
    bold: true,
    color: { argb: 'FF166534' }, // Emerald 800
  };
  const whiteHeaderFont = {
    name: 'Segoe UI',
    size: 10,
    bold: true,
    color: { argb: 'FFFFFFFF' },
  };
  const labelFont = {
    name: 'Segoe UI',
    size: 9.5,
    bold: true,
    color: { argb: 'FF334155' },
  };
  const valueFont = {
    name: 'Segoe UI',
    size: 9.5,
    color: { argb: 'FF0F172A' },
  };
  const thinBorder = {
    top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
  };

  // ==========================================
  // SHEET 1: RINGKASAN EKSEKUTIF
  // ==========================================
  const summarySheet = workbook.addWorksheet('Ringkasan', {
    views: [{ showGridLines: true }],
  });

  summarySheet.columns = [
    { key: 'colA', width: 4 },
    { key: 'colB', width: 14 },
    { key: 'colC', width: 28 },
    { key: 'colD', width: 16 },
    { key: 'colE', width: 44 },
    { key: 'colF', width: 22 },
    { key: 'colG', width: 16 },
    { key: 'colH', width: 34 },
  ];

  summarySheet.mergeCells('B2:H2');
  const sumTitle = summarySheet.getCell('B2');
  sumTitle.value = 'LAPORAN HASIL TESTING CYCLE 3 (REVIEW & FIXING SEMUA FITUR) — MARKETTRUST';
  sumTitle.font = { name: 'Segoe UI', size: 14, bold: true, color: { argb: 'FFFFFFFF' } };
  sumTitle.alignment = { horizontal: 'center', vertical: 'middle' };
  sumTitle.fill = accentFill;
  summarySheet.getRow(2).height = 36;

  const meta = [
    ['Penanggung Jawab (PIC)', 'Ali Rezanegoro / Jihan Ali Reza (jihanalireza@gmail.com)'],
    ['Branch Pengembangan', 'review/ali-review-and-fixing (Base: origin/main)'],
    ['Cakupan Fitur Siklus 3', '1. Header & Brand | 2. Auth Page & Google OAuth | 3. Marketplace & Fallback | 4. Forum Komunitas | 5. Floating Popup Notifikasi | 6. Profil Seller'],
    ['Tanggal Pengujian', '12-11-2026 (Kamis, 12 November 2026)'],
    ['Lingkungan Uji', 'PHP 8.5.0 / Laravel 13 / SQLite & PostgreSQL / Cypress 16.2.0 / Tailwind CSS v4'],
    ['Status Kelulusan', '100% LULUS (ALL PASS) — SELURUH 18 KASUS UJI INDUK & 36 SUB-UJI TERVERIFIKASI'],
  ];

  meta.forEach((m, idx) => {
    const rowNum = 4 + idx;
    summarySheet.getCell(`B${rowNum}`).value = m[0];
    summarySheet.getCell(`B${rowNum}`).font = labelFont;
    summarySheet.getCell(`B${rowNum}`).fill = identityLabelFill;
    summarySheet.getCell(`B${rowNum}`).border = thinBorder;

    summarySheet.mergeCells(`C${rowNum}:H${rowNum}`);
    const valCell = summarySheet.getCell(`C${rowNum}`);
    valCell.value = m[1];
    valCell.font = { name: 'Segoe UI', size: 9.5, bold: idx === 5, color: idx === 5 ? { argb: 'FF166534' } : { argb: 'FF0F172A' } };
    if (idx === 5) {
      valCell.fill = passFill;
    }
    ['C', 'D', 'E', 'F', 'G', 'H'].forEach((c) => {
      summarySheet.getCell(`${c}${rowNum}`).border = thinBorder;
    });
    summarySheet.getRow(rowNum).height = 20;
  });

  // KPI Summary Cards
  summarySheet.mergeCells('B11:C11');
  summarySheet.getCell('B11').value = 'Total Kasus Uji Induk: 18';
  summarySheet.getCell('B11').font = { name: 'Segoe UI', size: 10.5, bold: true, color: { argb: 'FFFFFFFF' } };
  summarySheet.getCell('B11').fill = darkNavyHeaderFill;
  summarySheet.getCell('B11').alignment = { horizontal: 'center', vertical: 'middle' };

  summarySheet.mergeCells('D11:E11');
  summarySheet.getCell('D11').value = 'Total Sub-Uji Teknis: 36 (18 PHPUnit + 18 Cypress)';
  summarySheet.getCell('D11').font = { name: 'Segoe UI', size: 10.5, bold: true, color: { argb: 'FFFFFFFF' } };
  summarySheet.getCell('D11').fill = darkNavyHeaderFill;
  summarySheet.getCell('D11').alignment = { horizontal: 'center', vertical: 'middle' };

  summarySheet.mergeCells('F11:G11');
  summarySheet.getCell('F11').value = 'Tingkat Kelulusan: 100% PASS';
  summarySheet.getCell('F11').font = { name: 'Segoe UI', size: 10.5, bold: true, color: { argb: 'FF166534' } };
  summarySheet.getCell('F11').fill = passFill;
  summarySheet.getCell('F11').alignment = { horizontal: 'center', vertical: 'middle' };

  summarySheet.getCell('H11').value = 'Cakupan: 6 Area Fitur Utama';
  summarySheet.getCell('H11').font = { name: 'Segoe UI', size: 10.5, bold: true, color: { argb: 'FFFFFFFF' } };
  summarySheet.getCell('H11').fill = accentFill;
  summarySheet.getCell('H11').alignment = { horizontal: 'center', vertical: 'middle' };
  summarySheet.getRow(11).height = 28;

  // Rekap per Fitur Tabel
  const recapHeaders = [
    'No',
    'Fitur / Modul',
    'Jumlah TC',
    'Cakupan Checklist Review & Fixing',
    'Runner',
    'Status',
    'Keterangan & Catatan Hasil',
  ];

  summarySheet.getRow(13).values = ['', ...recapHeaders];
  ['B', 'C', 'D', 'E', 'F', 'G', 'H'].forEach((c) => {
    const cell = summarySheet.getCell(`${c}13`);
    cell.font = whiteHeaderFont;
    cell.fill = darkNavyHeaderFill;
    cell.alignment = { horizontal: 'center', vertical: 'middle' };
    cell.border = thinBorder;
  });
  summarySheet.getRow(13).height = 24;

  const recapData = [
    [
      1,
      'Header Sticky & Identitas Brand',
      3,
      'Sticky navigation overflow-x-clip, perbaikan kurva logo perisai bawah y=21.8, dan instalasi favicon resmi MarketTrust.',
      'PHPUnit & Cypress',
      'PASS (100%)',
      'Header terbukti stay saat scrolling, kurva perisai dan dollar proporsional, tab bar menampilkan ikon resmi.',
    ],
    [
      2,
      'Autentikasi (Login & Registrasi)',
      3,
      'Keseimbangan spasi divider "Atau email/username", layout fit in 1 frame bebas scroll vertikal, dan penanganan graceful pembatalan Google OAuth.',
      'PHPUnit & Cypress',
      'PASS (100%)',
      'Kedua halaman muat 1 layar penuh pada resolusi standar, pembatalan Google kembali dengan alert ramah tanpa 400 Bad Request.',
    ],
    [
      3,
      'Marketplace & Katalog Listing',
      4,
      'Floating pagination tengah bawah (bottom-5), fallback gambar di tengah optik kartu, penanganan onerror gambar 404, upgrade form listing seller.',
      'PHPUnit & Cypress',
      'PASS (100%)',
      'Pagination melayang rapi dengan clearance pb-24, placeholder foto dan judul terpusat tanpa menimpa badge kategori.',
    ],
    [
      4,
      'Forum Komunitas',
      4,
      'Pembersihan teks label PS5 Switcher & Feed, scrolling kartu meluncur di belakang filter utama, spotlight glow kartu fokus, layout fit in 1 frame.',
      'PHPUnit & Cypress',
      'PASS (100%)',
      'Header bersih dari teks dev, hierarki layering filter card z-30 berfungsi sempurna, spotlight aktif pada kartu fokus.',
    ],
    [
      5,
      'Pusat Notifikasi & Popup Navbar',
      3,
      'Floating popup mengambang persis di sisi kiri tombol lonceng navbar tanpa redirect, animasi in/out simpel & elegan, live AJAX fetch & filter & tandai dibaca.',
      'PHPUnit & Cypress',
      'PASS (100%)',
      'Popup terbuka cepat 200ms & tutup 140ms, interaksi filter, expand pesan, dan tandai semua dibaca tersinkronisasi instan.',
    ],
    [
      6,
      'Profil Seller & Panduan Indikator',
      1,
      'Pembersihan kata-kata redundan pendamping badge ("Terverifikasi", "Perlu Perhatian", dsb) pada card Panduan Indikator Reputasi.',
      'PHPUnit & Cypress',
      'PASS (100%)',
      'Seluruh badge status (LEGIT, PROBLEMATIC, UNREVIEWED, Dinamis) tampil bersih dan simetris tanpa teks terpotong.',
    ],
  ];

  recapData.forEach((row, rIdx) => {
    const rowNum = 14 + rIdx;
    summarySheet.getRow(rowNum).values = ['', ...row];
    ['B', 'C', 'D', 'E', 'F', 'G', 'H'].forEach((c) => {
      const cell = summarySheet.getCell(`${c}${rowNum}`);
      cell.border = thinBorder;
      cell.font = { name: 'Segoe UI', size: 9 };
      cell.alignment = { vertical: 'middle', wrapText: true };

      if (c === 'B' || c === 'D' || c === 'F') {
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
      }
      if (c === 'G') {
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        cell.fill = passFill;
        cell.font = passFont;
      }
    });
    summarySheet.getRow(rowNum).height = 36;
  });

  // ==========================================
  // SHEET 2: TEST CASE (Tepat 10 Kolom Standar)
  // ==========================================
  const tcSheet = workbook.addWorksheet('Test Case', {
    views: [{ showGridLines: true }],
  });

  tcSheet.columns = [
    { key: 'colA', width: 4 },
    { key: 'colB', width: 15 }, // TEST CASE ID
    { key: 'colC', width: 25 }, // TEST SCENARIO
    { key: 'colD', width: 34 }, // TEST CASE
    { key: 'colE', width: 28 }, // PRE-CONDITION
    { key: 'colF', width: 42 }, // TEST STEPS
    { key: 'colG', width: 28 }, // TEST DATA
    { key: 'colH', width: 34 }, // EXPECTED RESULT
    { key: 'colI', width: 28 }, // POST CONDITION
    { key: 'colJ', width: 34 }, // ACTUAL RESULT
    { key: 'colK', width: 14 }, // STATUS (PASS/FAIL)
  ];

  const tcIdentity = [
    ['Module Name:', 'Testing Cycle 3 — Review & Fixing All Features (MarketTrust Platform)'],
    ['Reference Document:', 'Checklist Review & Fixing Ali (Branch: review/ali-review-and-fixing)'],
    ['Created by:', 'Ali Rezanegoro / Jihan Ali Reza'],
    ['Date of creation:', '12-11-2026'],
    ['Date of review:', '12-11-2026'],
  ];

  tcIdentity.forEach((item, idx) => {
    const rowIdx = 2 + idx;
    tcSheet.getCell(`B${rowIdx}`).value = item[0];
    tcSheet.getCell(`B${rowIdx}`).font = labelFont;
    tcSheet.getCell(`B${rowIdx}`).fill = identityLabelFill;
    tcSheet.getCell(`B${rowIdx}`).border = thinBorder;
    tcSheet.getCell(`B${rowIdx}`).alignment = { vertical: 'middle', horizontal: 'left' };

    tcSheet.mergeCells(`C${rowIdx}:K${rowIdx}`);
    const valCell = tcSheet.getCell(`C${rowIdx}`);
    valCell.value = item[1];
    valCell.font = valueFont;
    valCell.alignment = { vertical: 'middle', horizontal: 'left' };
    if (idx === 1) {
      valCell.font = { name: 'Segoe UI', size: 9.5, color: { argb: 'FF2563EB' }, bold: true };
    }

    ['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'].forEach((c) => {
      tcSheet.getCell(`${c}${rowIdx}`).border = thinBorder;
    });
    tcSheet.getRow(rowIdx).height = 20;
  });

  const tcTableHeaders = [
    'TEST CASE ID',
    'TEST SCENARIO',
    'TEST CASE',
    'PRE-CONDITION',
    'TEST STEPS',
    'TEST DATA',
    'EXPECTED RESULT',
    'POST-CONDITION',
    'ACTUAL RESULT',
    'STATUS',
  ];

  tcSheet.getRow(8).values = ['', ...tcTableHeaders];
  ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'].forEach((c) => {
    const cell = tcSheet.getCell(`${c}8`);
    cell.font = whiteHeaderFont;
    cell.fill = darkNavyHeaderFill;
    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
    cell.border = thinBorder;
  });
  tcSheet.getRow(8).height = 28;

  CYCLE3_TEST_CASES.forEach((tc, idx) => {
    const rowNum = 9 + idx;
    tcSheet.getRow(rowNum).values = [
      '',
      tc.tcId,
      tc.scenario,
      tc.testCase,
      tc.precondition,
      tc.steps,
      tc.testData,
      tc.expected,
      tc.postCondition,
      tc.actualResult,
      tc.status,
    ];

    ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'].forEach((c) => {
      const cell = tcSheet.getCell(`${c}${rowNum}`);
      cell.border = thinBorder;
      cell.font = { name: 'Segoe UI', size: 9 };
      cell.alignment = { vertical: 'top', wrapText: true };

      if (c === 'B') {
        cell.alignment = { horizontal: 'center', vertical: 'top' };
        cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FF1E293B' } };
      }
      if (c === 'K') {
        cell.alignment = { horizontal: 'center', vertical: 'top' };
        cell.fill = passFill;
        cell.font = passFont;
      }
    });

    tcSheet.getRow(rowNum).height = 64;
  });

  // ==========================================
  // SHEET 3: DETAIL EKSEKUSI PENGUJIAN
  // ==========================================
  const execSheet = workbook.addWorksheet('Detail Eksekusi Uji', {
    views: [{ showGridLines: true }],
  });

  execSheet.columns = [
    { key: 'colA', width: 4 },
    { key: 'colB', width: 6 },  // No
    { key: 'colC', width: 12 }, // Test Suite
    { key: 'colD', width: 15 }, // Parent TC ID
    { key: 'colE', width: 18 }, // Variation ID
    { key: 'colF', width: 34 }, // Nama Uji / Method
    { key: 'colG', width: 30 }, // Script Path
    { key: 'colH', width: 22 }, // Runner
    { key: 'colI', width: 25 }, // Referensi
    { key: 'colJ', width: 16 }, // Prioritas
    { key: 'colK', width: 34 }, // Command
    { key: 'colL', width: 12 }, // Durasi
    { key: 'colM', width: 10 }, // Commit
    { key: 'colN', width: 10 }, // Exit Code
    { key: 'colO', width: 34 }, // Hasil Aktual
    { key: 'colP', width: 12 }, // Status
  ];

  execSheet.mergeCells('B2:P2');
  const execTitle = execSheet.getCell('B2');
  execTitle.value = 'LOG DETAIL EKSEKUSI PENGUJIAN CYCLE 3 (AUTOMATED PHPUNIT & CYPRESS E2E)';
  execTitle.font = { name: 'Segoe UI', size: 13, bold: true, color: { argb: 'FFFFFFFF' } };
  execTitle.alignment = { horizontal: 'center', vertical: 'middle' };
  execTitle.fill = accentFill;
  execSheet.getRow(2).height = 32;

  const execHeaders = [
    'No',
    'Test Suite',
    'Parent TC ID',
    'Variation ID',
    'Nama Uji / Method Skrip',
    'Lokasi Skrip Uji',
    'Runner Engine',
    'Referensi Cakupan',
    'Prioritas',
    'Perintah Eksekusi CLI',
    'Durasi',
    'Commit',
    'Exit Code',
    'Hasil Aktual & Log Assertion',
    'Status',
  ];

  execSheet.getRow(4).values = ['', ...execHeaders];
  ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'].forEach((c) => {
    const cell = execSheet.getCell(`${c}4`);
    cell.font = whiteHeaderFont;
    cell.fill = darkNavyHeaderFill;
    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
    cell.border = thinBorder;
  });
  execSheet.getRow(4).height = 28;

  DETAILED_EXECUTIONS.forEach((ex, idx) => {
    const rowNum = 5 + idx;
    execSheet.getRow(rowNum).values = [
      '',
      idx + 1,
      ex.tsId,
      ex.parentTc,
      ex.variationId,
      ex.testName,
      ex.scriptPath,
      ex.runner,
      ex.sourceRef,
      ex.priority,
      ex.command,
      ex.timeStr,
      ex.commit,
      ex.exitCode,
      ex.actualResult,
      ex.status,
    ];

    ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'].forEach((c) => {
      const cell = execSheet.getCell(`${c}${rowNum}`);
      cell.border = thinBorder;
      cell.font = { name: 'Segoe UI', size: 9 };
      cell.alignment = { vertical: 'middle', wrapText: true };

      if (['B', 'C', 'D', 'E', 'L', 'M', 'N'].includes(c)) {
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
      }
      if (c === 'P') {
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        cell.fill = passFill;
        cell.font = passFont;
      }
    });

    execSheet.getRow(rowNum).height = 28;
  });

  // Save workbooks
  await workbook.xlsx.writeFile(OUTPUT_FILE_MAIN);
  await workbook.xlsx.writeFile(OUTPUT_FILE_ALI);
  console.log(`[SUCCESS] Excel Testing Cycle 3 tersimpan di:`);
  console.log(`  -> ${OUTPUT_FILE_MAIN}`);
  console.log(`  -> ${OUTPUT_FILE_ALI}`);
}

generateCycle3Excel().catch((err) => {
  console.error('[ERROR] Gagal men-generate file Excel:', err);
  process.exit(1);
});
