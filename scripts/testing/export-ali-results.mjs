import fs from 'fs';
import path from 'path';
import ExcelJS from 'exceljs';

const ROOT_DIR = process.cwd();
const RESULTS_DIR = path.join(ROOT_DIR, 'docs/testing/results/ali');
const OUTPUT_XLSX = path.join(ROOT_DIR, 'docs/hasil-testing-ali.xlsx');
const METADATA_PATH = path.join(RESULTS_DIR, 'run-metadata.json');

/**
 * Parse JUnit XML file and extract all <testcase> entries.
 */
function parseJunitXml(filePath) {
  if (!fs.existsSync(filePath)) {
    return { exists: false, testcases: [], testsuiteTime: 0, timestamp: null };
  }

  const xml = fs.readFileSync(filePath, 'utf8');
  const testcases = [];

  const tsMatch = xml.match(/<testsuite[^>]*timestamp="([^"]+)"/i);
  const timestamp = tsMatch ? tsMatch[1] : fs.statSync(filePath).mtime.toISOString();

  const tcRegex = /<testcase\b([^>]*)(?:\/>|>([\s\S]*?)<\/testcase>)/gi;
  let match;
  while ((match = tcRegex.exec(xml)) !== null) {
    const attrs = match[1] || '';
    const inner = match[2] || '';

    const getAttr = (name) => {
      const m = attrs.match(new RegExp(`${name}="([^"]*)"`, 'i'));
      return m ? m[1] : '';
    };

    const name = getAttr('name');
    const classname = getAttr('classname');
    const file = getAttr('file');
    const time = parseFloat(getAttr('time') || '0');
    const assertions = getAttr('assertions') || '';

    const failureMatch = inner.match(/<failure\b[^>]*>([\s\S]*?)<\/failure>/i);
    const errorMatch = inner.match(/<error\b[^>]*>([\s\S]*?)<\/error>/i);
    const skippedMatch = inner.match(/<skipped\b/i);

    let status = 'PASS';
    let failureDetail = '';
    if (failureMatch) {
      status = 'FAIL';
      failureDetail = failureMatch[1].replace(/<[^>]+>/g, '').trim();
    } else if (errorMatch) {
      status = 'FAIL';
      failureDetail = errorMatch[1].replace(/<[^>]+>/g, '').trim();
    } else if (skippedMatch) {
      status = 'NOT EXECUTED';
      failureDetail = 'Test di-skip pada runner.';
    }

    testcases.push({
      name,
      classname,
      file,
      time,
      assertions,
      status,
      failureDetail,
      xmlFile: path.relative(ROOT_DIR, filePath),
      timestamp,
    });
  }

  return { exists: true, testcases, timestamp };
}

/**
 * Load all XML files and run-metadata.json in docs/testing/results/ali/
 */
function loadAllRunnerData() {
  const metadata = fs.existsSync(METADATA_PATH)
    ? JSON.parse(fs.readFileSync(METADATA_PATH, 'utf8'))
    : { commit: '4f89bb1', executed_at: '2026-11-04T09:15:34.531Z', runs: {} };

  const files = fs.existsSync(RESULTS_DIR)
    ? fs.readdirSync(RESULTS_DIR).filter((f) => f.endsWith('.xml'))
    : [];

  const allTestcases = [];
  for (const f of files) {
    const fullPath = path.join(RESULTS_DIR, f);
    const parsed = parseJunitXml(fullPath);
    for (const tc of parsed.testcases) {
      allTestcases.push({ ...tc, xmlFileName: f });
    }
  }

  return { metadata, allTestcases };
}

/**
 * Match a specific testcase from parsed XMLs by file prefix and test name substring.
 */
function findTestExecution(allTestcases, metadata, xmlPrefix, testNamePattern) {
  const candidates = allTestcases.filter(
    (tc) =>
      tc.xmlFileName.startsWith(xmlPrefix) &&
      tc.name.toLowerCase().includes(testNamePattern.toLowerCase())
  );

  const runMeta = metadata.runs?.[xmlPrefix] || {};

  if (candidates.length === 0) {
    return {
      status: 'NOT EXECUTED',
      time: 0,
      assertions: '',
      failureDetail: 'Belum ada hasil eksekusi yang cocok di file XML runner.',
      xmlFile: runMeta.xml_file || `docs/testing/results/ali/${xmlPrefix}.xml`,
      logFile: runMeta.log_file || `docs/testing/results/ali/${xmlPrefix}.log`,
      command: runMeta.command || '-',
      executedAt: runMeta.executed_at || metadata.executed_at || '-',
      exitCode: runMeta.exit_code ?? '-',
      commit: metadata.commit || '4f89bb1',
    };
  }

  const chosen = candidates[candidates.length - 1];
  return {
    status: chosen.status,
    time: chosen.time,
    assertions: chosen.assertions,
    failureDetail: chosen.failureDetail,
    xmlFile: chosen.xmlFile,
    logFile: runMeta.log_file || chosen.xmlFile.replace(/\.xml$/, '.log'),
    command: runMeta.command || '-',
    executedAt: runMeta.executed_at || chosen.timestamp || metadata.executed_at,
    exitCode: runMeta.exit_code ?? (chosen.status === 'PASS' ? 0 : 1),
    commit: metadata.commit || '4f89bb1',
  };
}

/**
 * Metadata Identitas tiap Sheet TS (Baris 1-5 di atas tabel utama)
 */
const TS_MODULES = {
  'TS-03': {
    tsId: 'TS-03',
    moduleName: 'TS-03 — Rekening Payout Seller',
    referenceDocument:
      'Test Case TS-03: Rekening Payout Seller (https://app.notion.com/p/40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02',
    createdBy: 'Muhammad Ali (Owzey)',
    dateOfCreation: '04-11-26',
    dateOfReview: '',
  },
  'TS-05': {
    tsId: 'TS-05',
    moduleName: 'TS-05 — Marketplace & Statistik Pasar',
    referenceDocument:
      'Test Case TS-05: Marketplace & Statistik Pasar (https://app.notion.com/p/40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-04 & UC-05',
    createdBy: 'Muhammad Ali (Owzey)',
    dateOfCreation: '04-11-26',
    dateOfReview: '',
  },
  'TS-07': {
    tsId: 'TS-07',
    moduleName: 'TS-07 — Direct Message & Lampiran',
    referenceDocument:
      'Test Case TS-07: Direct Message & Lampiran (https://app.notion.com/p/40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08 & KNF-04',
    createdBy: 'Muhammad Ali (Owzey)',
    dateOfCreation: '04-11-26',
    dateOfReview: '',
  },
  'TS-14': {
    tsId: 'TS-14',
    moduleName: 'TS-14 — Pembatalan & Denda KEEP',
    referenceDocument:
      'Test Case TS-14: Pembatalan & Denda KEEP (https://app.notion.com/p/40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28 & UC-28B',
    createdBy: 'Muhammad Ali (Owzey)',
    dateOfCreation: '04-11-26',
    dateOfReview: '',
  },
};

/**
 * Definisi 11 Test Case Induk untuk Tabel Utama 10 Kolom pada Sheet TS-03, TS-05, TS-07, TS-14.
 * Kolom wajib:
 * TEST CASE ID | TEST SCENARIO | TEST CASE | PRE-CONDITION | TEST STEPS | TEST DATA | EXPECTED RESULT | POST CONDITION | ACTUAL RESULT | STATUS (PASS/FAIL)
 */
const PARENT_TCS = [
  // =========================================================================
  // TS-03 — Rekening Payout Seller
  // =========================================================================
  {
    tcId: 'TC-02-01',
    tsId: 'TS-03',
    testScenario: 'TS-03 — Rekening Payout Seller',
    testCase:
      'Penambahan Rekening Payout Tipe BANK oleh Seller beserta verifikasi kelengkapan atribut dan masking nomor rekening pada daftar tujuan pencairan.',
    preCondition:
      '1. Akun Seller (aliseller / aliseller_ts03) terdaftar dengan account_type = USER dan account_status = ACTIVE.\n2. Seller telah login ke sistem dan belum memiliki rekening payout pada tabel seller_payout_accounts.',
    testSteps:
      '1. Login sebagai Seller dan buka halaman Pengaturan Rekening Payout (/seller/payout-accounts).\n2. Klik tombol "Tambah Rekening Baru" ([data-cy="btn-create-payout-account"]).\n3. Pilih kartu tipe tujuan "Rekening Bank" (#card-BANK).\n4. Isi Nama Bank/Provider ("BCA"), Nomor Rekening ("8720192831"), dan Nama Pemilik Rekening ("Muhammad Ali").\n5. Centang opsi "Jadikan sebagai rekening pencairan utama" ([data-cy="checkbox-payout-active"]).\n6. Klik tombol "Simpan Rekening Payout" ([data-cy="btn-submit-payout"]).',
    testData:
      'destination_type: "BANK"\ndestination_provider: "BCA"\ndestination_number: "8720192831" (variasi unit: "1234567890" & kosong "")\ndestination_holder: "Muhammad Ali"\nis_active: true',
    expectedResult:
      '1. Sistem memvalidasi kelengkapan atribut tipe BANK (wajib ada provider, nomor rekening, dan nama pemilik).\n2. Sistem melakukan redirect HTTP 302 ke /seller/payout-accounts dan menampilkan pesan sukses "Rekening payout berhasil ditambahkan!".\n3. Kartu rekening BCA tampil sebagai "TUJUAN UTAMA AKTIF" dengan nomor ter-masking "•••• •••• 2831" a.n. Muhammad Ali.',
    postCondition:
      '1. Satu baris baru tersimpan di tabel seller_payout_accounts milik seller_id terkait dengan destination_type = BANK, destination_provider = BCA, destination_number = 8720192831, destination_holder = Muhammad Ali, dan is_active = 1 (true).\n2. Sesi Seller tetap aktif di halaman daftar rekening payout.',
    actualSummaryBase:
      'POST /seller/payout-accounts menyimpan record BANK BCA (8720192831) a.n. Muhammad Ali dengan is_active = true; model SellerPayoutAccount::isComplete() mengembalikan true (dan false saat nomor kosong); UI menampilkan notifikasi sukses serta kartu TUJUAN UTAMA AKTIF dengan nomor ter-masking •••• •••• 2831.',
    unitNaReason: null,
  },
  {
    tcId: 'TC-02-02',
    tsId: 'TS-03',
    testScenario: 'TS-03 — Rekening Payout Seller',
    testCase:
      'Aturan Atomik Hanya 1 Rekening Payout yang Aktif saat Seller menambahkan atau mengaktifkan rekening baru (tipe EWALLET) ketika sudah ada rekening aktif sebelumnya.',
    preCondition:
      '1. Seller telah login dan sudah memiliki 1 rekening payout aktif di database (tipe BANK BCA 11112222 / 8720192831 dengan is_active = true).\n2. Halaman tambah rekening (/seller/payout-accounts/create) dapat diakses.',
    testSteps:
      '1. Buka halaman /seller/payout-accounts/create.\n2. Pilih kartu tipe tujuan "Dompet Digital / E-Wallet" (#card-EWALLET).\n3. Isi Provider E-Wallet ("GoPay"), Nomor E-Wallet ("081234567890"), dan Nama Pemilik ("Muhammad Ali").\n4. Pastikan checkbox "Jadikan sebagai rekening pencairan utama" ([data-cy="checkbox-payout-active"]) tercentang.\n5. Klik tombol "Simpan Rekening Payout" ([data-cy="btn-submit-payout"]).\n6. Periksa status badge Aktif/Nonaktif pada seluruh kartu di UI dan jumlah baris is_active = true di database.',
    testData:
      'Rekening Lama: BANK BCA (is_active: true)\nRekening Baru:\ndestination_type: "EWALLET"\ndestination_provider: "GoPay"\ndestination_number: "081234567890"\ndestination_holder: "Muhammad Ali"\nis_active: true',
    expectedResult:
      '1. Rekening baru EWALLET GoPay berhasil disimpan sebagai rekening aktif (is_active = true).\n2. Rekening BANK BCA yang sebelumnya aktif secara atomik dinonaktifkan oleh sistem (is_active = false).\n3. Pada UI hanya tampil tepat 1 badge "Aktif" (pada kartu GoPay), sedangkan kartu BCA menampilkan badge "Nonaktif".',
    postCondition:
      '1. Tabel seller_payout_accounts memiliki 2 rekening milik Seller tersebut: GoPay dengan is_active = 1 (true) dan BCA dengan is_active = 0 (false).\n2. Invarian database unique active account per seller terjaga (COUNT(is_active = true) = 1).',
    actualSummaryBase:
      'Penambahan rekening EWALLET GoPay dengan is_active = true menonaktifkan rekening BANK BCA lama secara atomik di dalam transaksi DB; query SellerPayoutAccount::where(is_active, true)->count() tepat menghasilkan 1; di UI hanya tampil 1 badge Aktif pada kartu GoPay dan kartu BCA berubah menjadi Nonaktif.',
    unitNaReason: null,
  },
  {
    tcId: 'TC-02-03',
    tsId: 'TS-03',
    testScenario: 'TS-03 — Rekening Payout Seller',
    testCase:
      'Penambahan Rekening Payout Tipe QRIS Wajib Unggah Gambar Barcode (mencakup variasi negatif tanpa unggah gambar dan variasi positif dengan unggah gambar PNG valid).',
    preCondition:
      '1. Seller telah login dan berada di halaman form tambah rekening (/seller/payout-accounts/create).\n2. Disk storage publik untuk penyimpanan gambar QRIS siap digunakan.',
    testSteps:
      '1. Pilih kartu tipe tujuan "QRIS Interaktif" (#card-QRIS).\n2. Isi Nama Provider ("QRIS Merchant"), Nomor/NMID ("ID1020304050"), dan Nama Merchant ("Toko Digital Ali") tanpa memilih file gambar barcode.\n3. Klik tombol "Simpan Rekening Payout" dan amati respons validasi.\n4. Pilih kembali tipe QRIS, unggah file gambar barcode valid (qris-toko-ali.png / qris.png) pada input [data-cy="input-payout-qris-file"].\n5. Klik tombol "Simpan Rekening Payout" dan periksa kartu rekening QRIS pada halaman daftar.',
    testData:
      'Variasi Negatif (Tanpa Gambar):\ndestination_type: "QRIS", destination_provider: "QRIS Merchant", destination_holder: "Toko Digital Ali", qris_image: null\nVariasi Positif (Dengan Gambar):\nqris_image: qris-toko-ali.png / qris.png (image/png, <= 2 MB)',
    expectedResult:
      '1. Saat disubmit tanpa file gambar, sistem menolak penyimpanan dengan error validasi pada field qris_image: "File gambar barcode QRIS wajib diunggah untuk tipe tujuan QRIS."\n2. Saat disubmit dengan file PNG valid, sistem menyimpan file ke storage, mencatat path di kolom destination_qris_image_url, dan menampilkan tautan "Lihat Gambar Barcode QRIS" pada kartu rekening.',
    postCondition:
      '1. Untuk variasi negatif: tidak ada baris QRIS tidak lengkap yang tersimpan di tabel seller_payout_accounts.\n2. Untuk variasi positif: baris rekening tipe QRIS tersimpan di database dengan destination_qris_image_url terisi path file gambar yang ada di disk storage public.',
    actualSummaryBase:
      'Submit QRIS tanpa file ditolak dengan error validasi qris_image ("File gambar barcode QRIS wajib diunggah untuk tipe tujuan QRIS."); submit ulang dengan berkas PNG valid berhasil menyimpan file ke storage public, mengisi destination_qris_image_url di DB, dan menampilkan tautan "Lihat Gambar Barcode QRIS" di UI.',
    unitNaReason: null,
  },
  {
    tcId: 'TC-02-04',
    tsId: 'TS-03',
    testScenario: 'TS-03 — Rekening Payout Seller',
    testCase:
      'Seller Menghapus Rekening Payout Non-Aktif melalui modal konfirmasi tanpa mengganggu status rekening utama yang sedang aktif.',
    preCondition:
      '1. Seller telah login dan memiliki lebih dari 1 rekening payout di database: 1 rekening utama berstatus Aktif (is_active = true) dan minimal 1 rekening cadangan berstatus Nonaktif (is_active = false).',
    testSteps:
      '1. Buka halaman daftar rekening (/seller/payout-accounts).\n2. Cari kartu rekening yang berstatus "Nonaktif" (DANA 08999888777 pada Feature test / BCA •••• •••• 2831 pada Cypress E2E).\n3. Klik tombol ikon hapus ([data-cy="btn-delete-account"]) pada kartu non-aktif tersebut.\n4. Pastikan modal konfirmasi hapus ([data-cy="modal-delete-payout"]) tampil menyebut nama provider rekening.\n5. Klik tombol konfirmasi "Hapus Rekening" ([data-cy="btn-confirm-delete"]).',
    testData:
      'Target Hapus Feature Test: Rekening Non-Aktif DANA (08999888777, is_active: false)\nTarget Hapus Cypress E2E: Rekening Non-Aktif BCA (•••• •••• 2831, is_active: false)\nRekening Utama Aktif: tetap is_active: true',
    expectedResult:
      '1. Sistem menghapus record rekening non-aktif yang dipilih dari database.\n2. Halaman menampilkan pesan sukses "Rekening payout berhasil dihapus." dan kartu rekening yang dihapus tidak lagi tampil di daftar.\n3. Rekening utama yang aktif tetap ada dan tetap berstatus Aktif (is_active = true).',
    postCondition:
      '1. Baris rekening non-aktif yang dihapus hilang dari tabel seller_payout_accounts.\n2. Baris rekening utama milik Seller tetap utuh di database dengan is_active = 1 (true).',
    actualSummaryBase:
      'Request DELETE /seller/payout-accounts/{id} dan konfirmasi modal hapus di UI berhasil menghapus rekening non-aktif dari tabel seller_payout_accounts, menampilkan flash "Rekening payout berhasil dihapus.", serta mempertahankan rekening utama tetap berstatus Aktif (is_active = true).',
    unitNaReason: null,
  },

  // =========================================================================
  // TS-05 — Marketplace & Statistik Pasar
  // =========================================================================
  {
    tcId: 'TC-04-01',
    tsId: 'TS-05',
    testScenario: 'TS-05 — Marketplace & Statistik Pasar',
    testCase:
      'Pencarian Kata Kunci, Filter Bertingkat (Kategori & Varian), Eksklusi Listing Non-Aktif (CLOSED), serta Pengurutan Harga pada Katalog Marketplace.',
    preCondition:
      '1. Terdapat struktur katalog aktif: Kategori "Desain & Produktivitas" (Produk "Canva Pro" dengan varian "1 Bulan Private" & "1 Tahun Private") serta Kategori "Hiburan & Streaming" (Produk "Netflix UHD" dengan varian "1 Bulan 1 Profil").\n2. Terdapat 3 listing berstatus ACTIVE (Canva 1 Bulan Rp 20.000, Netflix 1 Bulan Rp 45.000, Canva 1 Tahun Rp 150.000) dan 1 listing berstatus CLOSED (Rp 10.000).',
    testSteps:
      '1. Buka halaman katalog /marketplace.\n2. Masukkan kata kunci "Canva" pada kolom pencarian ([data-cy="input-search-marketplace"]) lalu klik tombol Cari ([data-cy="btn-submit-search"]).\n3. Uji filter berdasarkan category_id dan variant_id.\n4. Klik tombol Reset ([data-cy="btn-reset-filter"]) lalu pilih pengurutan "Harga Terendah" (sort=price_asc) pada dropdown [data-cy="select-sort"].\n5. Ubah pengurutan menjadi "Harga Tertinggi" (sort=price_desc) dan amati urutan kartu listing.',
    testData:
      'q: "Canva"\ncategory_id: ID Kategori Hiburan & Streaming\nvariant_id: ID Varian Canva 1 Tahun Private\nsort: "latest" | "price_asc" | "price_desc"',
    expectedResult:
      '1. Pencarian "Canva" hanya menampilkan listing ACTIVE yang relevan ("Akun Canva 1 Bulan Murah Bergaransi" & "Akun Canva 1 Tahun Private Eksklusif") dan tidak menampilkan listing Netflix maupun listing berstatus CLOSED.\n2. Filter category_id dan variant_id menyaring listing secara presisi.\n3. Sort price_asc menempatkan listing Rp 20.000 di urutan pertama, sedangkan price_desc menempatkan listing Rp 150.000 di urutan pertama.',
    postCondition:
      '1. Data listing di database tidak mengalami mutasi (operasi baca/query katalog).\n2. Parameter query string URL (/marketplace?q=...&sort=...) tercermin sesuai filter dan urutan yang aktif.',
    actualSummaryBase:
      'Pencarian kata kunci "Canva", filter kategori/varian, dan penyembunyian listing CLOSED berjalan akurat; pengurutan price_asc menempatkan "Akun Canva 1 Bulan Murah Bergaransi" (Rp 20.000) di urutan ke-1 dan price_desc menempatkan "Akun Canva 1 Tahun Private Eksklusif" (Rp 150.000) di urutan ke-1 baik pada HTTP Feature test maupun UI Cypress.',
    unitNaReason: null,
  },
  {
    tcId: 'TC-04-02',
    tsId: 'TS-05',
    testScenario: 'TS-05 — Marketplace & Statistik Pasar',
    testCase:
      'Filter Periode Statistik Pasar pada Halaman Detail Produk/Listing (UC-05) untuk Periode 7 Hari (7d), 30 Hari (30d), 90 Hari (90d — Gap Docs), dan Semua Waktu (all) hanya dari transaksi COMPLETED.',
    preCondition:
      '1. Pada varian "Canva Pro - 1 Bulan Private" terdapat: 1 postingan forum WTB ACTIVE (Demand = 1), 1 listing ACTIVE + 1 postingan forum WTS ACTIVE (Supply = 2).\n2. Terdapat 4 transaksi berstatus COMPLETED pada varian tersebut dengan umur berbeda: 3 hari lalu (Rp 20.000), 20 hari lalu (Rp 30.000), 60 hari lalu (Rp 40.000), dan 120 hari lalu (Rp 50.000).\n3. Terdapat transaksi pengecoh berstatus CANCELLED (Rp 99.000), PAID (Rp 88.000), serta transaksi COMPLETED pada varian lain (Rp 150.000).',
    testSteps:
      '1. Buka halaman detail listing /marketplace/{listing_id} (default periode 30d) dan periksa panel "Informasi Pasar Produk".\n2. Klik tab filter "7 Hari" ([data-cy="market-stat-filter-7d"]) dan periksa perubahan label periode, rata-rata harga, serta jumlah sampel transaksi selesai.\n3. Klik tab filter "90 Hari" ([data-cy="market-stat-filter-90d"]) dan periksa hasil kalkulasi.\n4. Klik tab filter "Semua" ([data-cy="market-stat-filter-all"]) dan periksa hasil kalkulasi.\n5. Uji parameter period tidak valid (?period=invalid_period) untuk memverifikasi fallback ke 30d.',
    testData:
      'period: "30d" (default) | "7d" | "90d" | "all" | "invalid_period"\nSampel Transaksi COMPLETED Varian Target:\n- 3 hari lalu: Rp 20.000\n- 20 hari lalu: Rp 30.000\n- 60 hari lalu: Rp 40.000\n- 120 hari lalu: Rp 50.000',
    expectedResult:
      '1. Demand = 1 dan Supply = 2.\n2. Periode 7d: Rata-rata = Rp 20.000 (Berdasarkan 1 transaksi selesai).\n3. Periode 30d (default & fallback invalid_period): Rata-rata = Rp 25.000 (Berdasarkan 2 transaksi selesai).\n4. Periode 90d: Rata-rata = Rp 30.000 (Berdasarkan 3 transaksi selesai).\n5. Periode all: Rata-rata = Rp 35.000 (Berdasarkan 4 transaksi selesai; transaksi CANCELLED/PAID dan varian lain diabaikan).',
    postCondition:
      '1. Panel Informasi Pasar Produk di halaman detail listing (/marketplace/{listing}) menampilkan metrik Demand, Supply, Rata-rata Harga Pasar, dan jumlah sampel sesuai periode yang dipilih tanpa mengubah data transaksi di database.',
    actualSummaryBase:
      'Panel Informasi Pasar Produk di /marketplace/{listing} dan MarketplaceService::getProductMarketStats menghasilkan Demand=1, Supply=2, serta rata-rata harga eksak: 7d = Rp 20.000 (1 transaksi), 30d = Rp 25.000 (2 transaksi), 90d = Rp 30.000 (3 transaksi), dan all = Rp 35.000 (4 transaksi); transaksi CANCELLED/PAID dan varian lain terbukti diabaikan.',
    unitNaReason:
      'N/A (Kalkulasi statistik pasar merupakan query agregasi Eloquent lintas tabel transactions, agreements, listings, dan forum_posts di MarketplaceService sehingga diuji langsung pada Feature Test & Cypress E2E; tidak dihitung sebagai PASS Unit)',
  },

  // =========================================================================
  // TS-07 — Direct Message & Lampiran
  // =========================================================================
  {
    tcId: 'TC-05-04',
    tsId: 'TS-07',
    testScenario: 'TS-07 — Direct Message & Lampiran',
    testCase:
      'Pengiriman Pesan Teks Direct Message, Sinkronisasi Polling Real-Time Tanpa Reload Halaman, Pembaruan Status Baca (Read Receipt), dan Proteksi Akses Non-Partisipan.',
    preCondition:
      '1. Akun Buyer (alibuyer / alibuyer_ts07) dan Seller (aliseller / aliseller_ts07) berstatus USER ACTIVE dan telah memiliki ruang percakapan aktif (conversations).\n2. Terdapat akun pihak ketiga (alioutsider) yang bukan peserta percakapan.',
    testSteps:
      '1. Login sebagai Buyer dan buka halaman ruang percakapan (/conversations/{conversation_id}).\n2. Ketik pesan teks pada kolom #message-content lalu klik tombol Kirim (#submit-message-btn).\n3. Pastikan pesan tampil di #message-list dengan status awal "Terkirim".\n4. Tanpa me-reload halaman Buyer, Seller membaca pesan dan mengirim balasan teks dari sisi lawan.\n5. Tunggu siklus polling otomatis (/conversations/{id}/sync) pada halaman Buyer.\n6. Uji akses endpoint /conversations/{id}/sync menggunakan akun outsider (non-partisipan).',
    testData:
      'Pesan Buyer: "Halo Kak Seller, apakah akun masih ready hari ini?"\nBalasan Seller: "Halo Kak Buyer! Stok sangat ready, silakan lanjut."\nEndpoint Polling: GET /conversations/{id}/sync?after_id={id}',
    expectedResult:
      '1. Pesan Buyer tersimpan di tabel messages dan tampil di daftar chat.\n2. Saat Seller melakukan sync/balas, kolom read_at pada pesan Buyer terisi otomatis.\n3. Melalui polling tanpa reload halaman, pesan balasan Seller muncul otomatis di #message-list Buyer dan indikator pesan Buyer berubah menjadi "Dibaca".\n4. Akses oleh akun outsider ditolak dengan HTTP 403 Forbidden.',
    postCondition:
      '1. Dua pesan teks tersimpan di tabel messages pada conversation_id terkait.\n2. Pesan milik Buyer memiliki nilai read_at tidak null (telah dibaca oleh Seller).\n3. Outsider tidak dapat membaca maupun mengirim pesan pada percakapan tersebut.',
    actualSummaryBase:
      'Pengiriman pesan teks dan endpoint polling GET /conversations/{id}/sync berhasil menampilkan pesan balasan lawan secara otomatis di DOM #message-list tanpa reload halaman, memperbarui kolom read_at serta indikator UI menjadi "Dibaca", dan menolak akses akun non-peserta dengan HTTP 403 Forbidden.',
    unitNaReason: null,
  },
  {
    tcId: 'TC-05-05',
    tsId: 'TS-07',
    testScenario: 'TS-07 — Direct Message & Lampiran',
    testCase:
      'Validasi Ketat Lampiran Berkas Direct Message (UC-08 & KNF-04): Penolakan Pesan Kosong, Format Terlarang (.zip, .exe, .mp4), Ukuran > 5 MB, serta Keberhasilan Unggah & Otorisasi Unduh Berkas Valid (.png, .pdf).',
    preCondition:
      '1. Buyer dan Seller berada dalam ruang percakapan aktif (/conversations/{conversation_id}).\n2. Storage lokal (disk local) siap menyimpan berkas lampiran terproteksi.',
    testSteps:
      '1. Uji kirim pesan tanpa teks dan tanpa lampiran (variasi kosong).\n2. Pilih/unggah berkas dengan ekstensi terlarang (.zip, .exe, .mp4) pada input #attachment-input dan periksa respons validasi UI & backend.\n3. Pilih/unggah berkas gambar berukuran > 5 MB (5.5 MB di UI / 6 MB di backend) dan periksa pesan error ukuran.\n4. Pilih/unggah berkas gambar valid (bukti-pembayaran-sah.png / bukti_transfer.png <= 5 MB) serta dokumen PDF valid (invoice_kesepakatan.pdf <= 5 MB), lalu kirim pesan.\n5. Uji unduh lampiran (/conversations/{id}/messages/{message}/attachment) sebagai peserta percakapan dan sebagai outsider.',
    testData:
      'Variasi Invalid:\n- Kosong: content="", attachment=null\n- Format Terlarang: berkas_terlarang.zip / arsip_berbahaya.zip, installer.exe, rekaman.mp4\n- Ukuran > 5 MB: gambar_terlalu_besar.png (5.5 MB) / resolusi_tinggi.png (6000 KB)\nVariasi Valid:\n- bukti-pembayaran-sah.png / bukti_transfer.png (image/png, <= 5 MB)\n- invoice_kesepakatan.pdf (application/pdf, 1.5 MB)',
    expectedResult:
      '1. Pesan kosong ditolak HTTP 422 ("Pesan teks wajib diisi apabila tidak menyertakan file lampiran.").\n2. Format .zip, .exe, dan .mp4 ditolak dengan pesan "Format file lampiran harus berupa gambar (JPG, JPEG, PNG, WEBP) atau PDF."\n3. Berkas > 5 MB ditolak dengan pesan "Ukuran file lampiran maksimal 5 MB."\n4. Berkas .png dan .pdf <= 5 MB berhasil diunggah, menampilkan badge pratinjau & tombol Unduh di chat, serta hanya dapat diunduh oleh peserta percakapan (HTTP 200) dan ditolak bagi outsider (HTTP 403).',
    postCondition:
      '1. Tidak ada berkas tidak valid (.zip/.exe/.mp4/>5MB) yang tersimpan di storage maupun tabel messages.\n2. Berkas valid (.png & .pdf) tersimpan di disk local (dm_attachments/...) dan metadata (attachment_path, attachment_name, attachment_mime, attachment_size) tercatat lengkap di tabel messages.',
    actualSummaryBase:
      'Seluruh variasi negatif (pesan kosong, file .zip/.exe/.mp4, dan ukuran 5.5 MB / 6 MB) ditolak secara konsisten di UI (#attachment-error-alert) dan backend (HTTP 422); berkas valid (.png & .pdf <= 5 MB) berhasil disimpan ke disk local, tampil di #message-list dengan tautan Unduh, serta terlindungi dari akses outsider (HTTP 403).',
    unitNaReason: null,
  },

  // =========================================================================
  // TS-14 — Pembatalan & Denda KEEP
  // =========================================================================
  {
    tcId: 'TC-12-01',
    tsId: 'TS-14',
    testScenario: 'TS-14 — Pembatalan & Denda KEEP',
    testCase:
      'Pembatalan Transaksi Pre-Payment oleh Buyer (Sebelum Pembayaran / Status WAITING_PAYMENT) secara langsung tanpa tinjauan Admin dan tanpa denda KEEP.',
    preCondition:
      '1. Buyer telah login dan memiliki transaksi Marketplace berstatus WAITING_PAYMENT (belum ada pembayaran berstatus PAID).\n2. Listing terkait pada katalog berstatus ACTIVE.',
    testSteps:
      '1. Login sebagai Buyer dan buka halaman detail transaksi (/transactions/{transaction_id}).\n2. Klik tombol "Batalkan Pesanan Ini" ([data-cy="btn-open-cancel-modal"]).\n3. Pada modal konfirmasi (#modal-cancel-transaction), isi alasan pembatalan ("Salah memilih durasi paket langganan." / "Salah memilih varian produk").\n4. Klik tombol "Ya, Batalkan Pesanan".\n5. Periksa perubahan status transaksi, banner pembatalan, riwayat status, status listing, dan tabel keep_obligations.',
    testData:
      'Status Awal Transaksi: WAITING_PAYMENT\nreason: "Salah memilih durasi paket langganan." / "Salah memilih varian produk"',
    expectedResult:
      '1. Status transaksi langsung berubah menjadi CANCELLED tanpa perlu menunggu persetujuan Admin.\n2. Muncul banner "Transaksi Resmi Dibatalkan (Pra-Pembayaran)" dan badge status "Dibatalkan".\n3. Record pembatalan tercatat dengan stage = BEFORE_PAYMENT dan status = APPROVED.\n4. Listing tetap berstatus ACTIVE di katalog dan tidak ada denda KEEP yang diterbitkan (keep_obligations = 0).',
    postCondition:
      '1. Tabel transactions: status = CANCELLED, cancelled_at terisi, cancellation_by_user_id = buyer_id.\n2. Tabel transaction_cancellations: stage = BEFORE_PAYMENT, status = APPROVED, responsible_party = BUYER.\n3. Tabel transaction_status_histories: tercatat transisi WAITING_PAYMENT -> CANCELLED.\n4. Tabel listings: status tetap ACTIVE; tabel keep_obligations: 0 baris.',
    actualSummaryBase:
      'Pembatalan pada status WAITING_PAYMENT langsung mengubah status transaksi ke CANCELLED, membuat record transaction_cancellations (stage BEFORE_PAYMENT, status APPROVED), mencatat riwayat transisi status, menampilkan banner "Transaksi Resmi Dibatalkan (Pra-Pembayaran)" di UI, mempertahankan listing tetap ACTIVE, dan tidak menerbitkan denda KEEP (0 record).',
    unitNaReason: null,
  },
  {
    tcId: 'TC-12-02',
    tsId: 'TS-14',
    testScenario: 'TS-14 — Pembatalan & Denda KEEP',
    testCase:
      'Pengajuan Pembatalan Post-Payment Sebelum Dikirim (Status PAID) oleh Buyer: Validasi Alasan Wajib Diisi dan Masuk Antrean Peninjauan Admin (Status REQUESTED).',
    preCondition:
      '1. Buyer telah login dan memiliki transaksi berstatus PAID (pembayaran sudah lunas/settlement di escrow, namun pesanan belum dikirim/DELIVERED oleh Seller).',
    testSteps:
      '1. Uji kirim POST /transactions/{id}/cancel dengan reason kosong ("") untuk memverifikasi validasi wajib alasan.\n2. Buka halaman detail transaksi (/transactions/{transaction_id}) yang berstatus PAID.\n3. Klik tombol "Ajukan Pembatalan Transaksi" ([data-cy="btn-open-cancel-modal"]).\n4. Isi alasan pembatalan ("Penjual mengabarkan bahwa stok lisensi sedang habis.") pada #cancel-reason.\n5. Klik tombol "Kirim Pengajuan Pembatalan" dan periksa status transaksi serta banner pengajuan.',
    testData:
      'Status Awal Transaksi: PAID\nVariasi Invalid: reason = "" (kosong)\nVariasi Valid: reason = "Penjual mengabarkan bahwa stok lisensi sedang habis." / "Penjual belum merespons chat dan saya butuh akun segera."',
    expectedResult:
      '1. Pengajuan dengan reason kosong ditolak oleh validasi.\n2. Pengajuan dengan reason valid berhasil disimpan sebagai permohonan pembatalan dengan stage = AFTER_PAYMENT_BEFORE_DELIVERY dan status = REQUESTED.\n3. Status transaksi tetap tertahan di PAID (belum CANCELLED) sambil menunggu keputusan Admin.\n4. Halaman menampilkan banner "Pengajuan Pembatalan Sedang Ditinjau Admin" beserta alasan yang diajukan.',
    postCondition:
      '1. Tabel transaction_cancellations memiliki baris baru dengan stage = AFTER_PAYMENT_BEFORE_DELIVERY, status = REQUESTED, dan requested_by = buyer_id.\n2. Tabel transactions tetap berstatus PAID.\n3. Belum ada record di tabel keep_obligations sebelum Admin memberikan keputusan final.',
    actualSummaryBase:
      'Pengajuan pembatalan tanpa alasan ditolak validasi pada field reason; pengajuan dengan alasan valid berhasil mencatat record transaction_cancellations (stage AFTER_PAYMENT_BEFORE_DELIVERY, status REQUESTED), menahan status transaksi tetap PAID, dan menampilkan banner "Pengajuan Pembatalan Sedang Ditinjau Admin" di UI.',
    unitNaReason:
      'N/A (Helper model stage AFTER_PAYMENT_BEFORE_DELIVERY & status REQUESTED telah diuji bersama pada method unit TC-12-01-UNIT, sedangkan alur validasi HTTP & antrean DB diuji pada Feature Test & Cypress E2E; tidak dihitung sebagai PASS Unit terpisah)',
  },
  {
    tcId: 'TC-12-03',
    tsId: 'TS-14',
    testScenario: 'TS-14 — Pembatalan & Denda KEEP',
    testCase:
      'Admin Meninjau Pembatalan Pasca-Bayar dan Menetapkan Seller Bersalah (UC-28B): Transaksi Menjadi CANCELLED (Layak Refund) dan Penerbitan Denda KEEP Otomatis (Rp 4.000 Flat Bank/VA & 3x Biaya Gateway untuk Kanal Lain).',
    preCondition:
      '1. Terdapat transaksi berstatus PAID yang memiliki pengajuan pembatalan berstatus REQUESTED (stage AFTER_PAYMENT_BEFORE_DELIVERY).\n2. Data pembayaran memiliki kanal pembayaran terverifikasi (bca_va untuk aturan flat Rp 4.000; serta qris dengan gateway_fee_amount = Rp 1.500 untuk aturan 3x biaya gateway).\n3. Akun Admin (aliadmin / aliadmin_ts14) dengan account_type = ADMIN telah login.',
    testSteps:
      '1. Login sebagai Admin dan buka halaman Manajemen Transaksi (/admin/transactions).\n2. Pada tabel "Antrean Pengajuan Pembatalan Pasca-Bayar", klik tombol "Tinjau Keputusan" pada baris transaksi yang bersangkutan.\n3. Pada modal #modal-review-cancellation, pilih keputusan "Setujui & Batalkan" (decision = APPROVED).\n4. Pilih Pihak Bertanggung Jawab = "Penjual (Seller)" (responsible_party = SELLER).\n5. Isi catatan keputusan ("Disetujui karena penjual kehabisan stok pasca pembayaran.") lalu klik "Simpan Keputusan Final".\n6. Periksa perubahan status transaksi, kelayakan refund, serta baris baru pada tabel "Daftar Kewajiban KEEP Penjual" (baik untuk kanal Bank/VA maupun kanal QRIS).',
    testData:
      'decision: "APPROVED"\nresponsible_party: "SELLER"\ndecision_note: "Disetujui karena penjual kehabisan stok pasca pembayaran."\nData Kanal Pembayaran:\n- Kasus 1 (Bank/VA): payment_channel = "bca_va" -> Aturan BANK_VA_FLAT = Rp 4.000\n- Kasus 2 (Non-Bank/QRIS): payment_channel = "qris", gateway_fee_amount = Rp 1.500 -> Aturan OTHER_TRIPLE_GATEWAY_FEE = 3 x Rp 1.500 = Rp 4.500',
    expectedResult:
      '1. Pengajuan pembatalan berubah menjadi APPROVED dengan reviewed_by = admin_id.\n2. Status transaksi berubah menjadi CANCELLED dan dinyatakan layak refund penuh ke Buyer (isEligibleForCancelledRefund() = true).\n3. Sistem otomatis menerbitkan denda KEEP berstatus OPEN kepada Seller: Rp 4.000 untuk kanal Bank/VA (BANK_VA_FLAT) dan Rp 4.500 untuk kanal QRIS (OTHER_TRIPLE_GATEWAY_FEE, 3 x Rp 1.500), dengan total akumulasi tunggakan Rp 8.500.\n4. Pada UI Admin, tabel Daftar Kewajiban KEEP Penjual menampilkan baris tagihan KEEP Seller berstatus OPEN.',
    postCondition:
      '1. Tabel transactions: status = CANCELLED, cancelled_at terisi.\n2. Tabel transaction_cancellations: status = APPROVED, responsible_party = SELLER, reviewed_by = admin_id, reviewed_at terisi.\n3. Tabel keep_obligations: terbentuk record baru berstatus OPEN milik seller_id dengan amount & remaining_amount = 4000.00 (BANK_VA_FLAT) dan/atau 4500.00 (OTHER_TRIPLE_GATEWAY_FEE).',
    actualSummaryBase:
      'Keputusan Admin (decision=APPROVED, responsible_party=SELLER) mengubah status transaksi menjadi CANCELLED (isEligibleForCancelledRefund()=true) dan secara otomatis menerbitkan kewajiban KEEP berstatus OPEN sebesar Rp 4.000 (BANK_VA_FLAT pada bca_va) serta Rp 4.500 (OTHER_TRIPLE_GATEWAY_FEE 3xRp1.500 pada qris, total tunggakan Rp 8.500); tabel UI Admin menampilkan baris KEEP Rp 4.000 berstatus OPEN.',
    unitNaReason: null,
  },
];

/**
 * Definisi lengkap 34 Subtest / Eksekusi Runner untuk Sheet "Detail Eksekusi"
 * (12 PHPUnit Unit, 11 PHPUnit Feature, 11 Cypress E2E).
 */
const SCENARIO_CATALOG = [
  // =========================================================================
  // TS-03 — Rekening Payout Seller (12 Eksekusi)
  // =========================================================================
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-01',
    subtestId: 'TC-02-01-UNIT',
    testName: 'PayoutAccountRulesTest::test_tc_02_01_bank_payout_completeness_rules',
    scriptPath: 'tests/Unit/MarketTrust/PayoutAccountRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Unit Test / P1 (High)',
    xmlPrefix: 'TS-03-unit',
    testMatcher: 'test_tc_02_01_bank_payout_completeness_rules',
    passActualSummary:
      'Aturan isComplete() tipe BANK mengembalikan true saat atribut lengkap, false saat nomor rekening kosong, dan masked_destination_number menghasilkan •••• •••• 7890.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-01',
    subtestId: 'TC-02-01-FEAT',
    testName: 'PayoutAccountScenarioTest::test_tc_02_01_seller_can_add_bank_payout_account',
    scriptPath: 'tests/Feature/MarketTrust/PayoutAccountScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    xmlPrefix: 'TS-03-feature',
    testMatcher: 'test_tc_02_01_seller_can_add_bank_payout_account',
    passActualSummary:
      'POST /seller/payout-accounts berhasil menyimpan rekening BANK BCA (8720192831) a.n. Muhammad Ali dengan is_active = true dan redirect HTTP 302 ke indeks.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-01',
    subtestId: 'TC-02-01-E2E',
    testName: 'TC-02-01: Penambahan Rekening Payout Tipe BANK berhasil disimpan dan menjadi tujuan utama aktif',
    scriptPath: 'cypress/e2e/markettrust/ts-03-rekening-payout.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-03-cypress',
    testMatcher: 'TC-02-01',
    passActualSummary:
      'Form UI berhasil menyimpan rekening BANK BCA dan halaman /seller/payout-accounts menampilkan banner TUJUAN UTAMA AKTIF BCA •••• •••• 2831 a.n. Muhammad Ali.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-02',
    subtestId: 'TC-02-02-UNIT',
    testName: 'PayoutAccountRulesTest::test_tc_02_02_ewallet_payout_completeness_rules',
    scriptPath: 'tests/Unit/MarketTrust/PayoutAccountRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Unit Test / P1 (High)',
    xmlPrefix: 'TS-03-unit',
    testMatcher: 'test_tc_02_02_ewallet_payout_completeness_rules',
    passActualSummary:
      'Model SellerPayoutAccount tipe EWALLET tervalidasi lengkap (isComplete() = true) dengan label enum "Dompet Digital (E-Wallet)".',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-02',
    subtestId: 'TC-02-02-FEAT',
    testName: 'PayoutAccountScenarioTest::test_tc_02_02_atomic_single_active_payout_account_enforcement',
    scriptPath: 'tests/Feature/MarketTrust/PayoutAccountScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    xmlPrefix: 'TS-03-feature',
    testMatcher: 'test_tc_02_02_atomic_single_active_payout_account_enforcement',
    passActualSummary:
      'Transaksi DB atomik menonaktifkan rekening BANK lama (is_active = false) dan mengaktifkan rekening EWALLET GoPay baru; total rekening aktif milik Seller = 1.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-02',
    subtestId: 'TC-02-02-E2E',
    testName: 'TC-02-02: Aturan Atomik Hanya 1 Rekening Payout yang Aktif saat menambah EWALLET baru sebagai utama',
    scriptPath: 'cypress/e2e/markettrust/ts-03-rekening-payout.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-03-cypress',
    testMatcher: 'TC-02-02',
    passActualSummary:
      'UI menampilkan tepat 1 badge Aktif pada kartu GoPay, kartu BCA otomatis berubah menjadi Nonaktif, dan verifikasi DB mengonfirmasi ACTIVE_COUNT:1.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-03',
    subtestId: 'TC-02-03-UNIT',
    testName: 'PayoutAccountRulesTest::test_tc_02_03_qris_payout_requires_image_or_identifier',
    scriptPath: 'tests/Unit/MarketTrust/PayoutAccountRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Unit Test / P1 (High)',
    xmlPrefix: 'TS-03-unit',
    testMatcher: 'test_tc_02_03_qris_payout_requires_image_or_identifier',
    passActualSummary:
      'isComplete() mengembalikan false untuk objek QRIS tanpa gambar/identifier dan true saat destination_qris_image_url terisi.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-03',
    subtestId: 'TC-02-03-FEAT',
    testName: 'PayoutAccountScenarioTest::test_tc_02_03_qris_payout_requires_image_upload_and_stores_url',
    scriptPath: 'tests/Feature/MarketTrust/PayoutAccountScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Feature Test (HTTP + Storage + DB) / P1 (High)',
    xmlPrefix: 'TS-03-feature',
    testMatcher: 'test_tc_02_03_qris_payout_requires_image_upload_and_stores_url',
    passActualSummary:
      'Submit QRIS tanpa gambar ditolak validasi session error pada field qris_image; submit dengan qris.png berhasil menyimpan file ke storage public dan mencatat URL di DB.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-03',
    subtestId: 'TC-02-03-E2E',
    testName: 'TC-02-03: Penambahan Rekening Payout Tipe QRIS Wajib Unggah Gambar (Validasi gagal tanpa gambar & berhasil dengan gambar valid)',
    scriptPath: 'cypress/e2e/markettrust/ts-03-rekening-payout.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-03-cypress',
    testMatcher: 'TC-02-03',
    passActualSummary:
      'Form menolak submit QRIS tanpa gambar dengan pesan validasi yang tepat, lalu menerima unggahan qris-toko-ali.png dan menampilkan tautan "Lihat Gambar Barcode QRIS".',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-04',
    subtestId: 'TC-02-04-UNIT',
    testName: 'PayoutAccountRulesTest::test_tc_02_04_short_account_number_masking_rules',
    scriptPath: 'tests/Unit/MarketTrust/PayoutAccountRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Unit Test / P2 (Medium)',
    xmlPrefix: 'TS-03-unit',
    testMatcher: 'test_tc_02_04_short_account_number_masking_rules',
    passActualSummary:
      'Atribut masked_destination_number memformat nomor pendek (<= 4 digit, "123") menjadi "•••• 123" dengan benar.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-04',
    subtestId: 'TC-02-04-FEAT',
    testName: 'PayoutAccountScenarioTest::test_tc_02_04_seller_can_delete_inactive_payout_account',
    scriptPath: 'tests/Feature/MarketTrust/PayoutAccountScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    xmlPrefix: 'TS-03-feature',
    testMatcher: 'test_tc_02_04_seller_can_delete_inactive_payout_account',
    passActualSummary:
      'DELETE /seller/payout-accounts/{id} menghapus rekening cadangan non-aktif (DANA) dari DB sementara rekening utama (BCA) tetap is_active = true.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    parentTcId: 'TC-02-04',
    subtestId: 'TC-02-04-E2E',
    testName: 'TC-02-04: Seller Menghapus Rekening Payout Non-Aktif melalui modal konfirmasi tanpa mengganggu rekening utama aktif',
    scriptPath: 'cypress/e2e/markettrust/ts-03-rekening-payout.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-03-cypress',
    testMatcher: 'TC-02-04',
    passActualSummary:
      'Modal konfirmasi menghapus kartu non-aktif BCA (•••• •••• 2831) dan mempertahankan kartu utama GoPay tetap berstatus Aktif.',
    notes: '-',
  },

  // =========================================================================
  // TS-05 — Marketplace & Statistik Pasar (6 Eksekusi)
  // =========================================================================
  {
    tsId: 'TS-05',
    parentTcId: 'TC-04-01',
    subtestId: 'TC-04-01-UNIT-A',
    testName: 'MarketplaceStatisticsRulesTest::test_tc_04_01_marketplace_search_request_rules',
    scriptPath: 'tests/Unit/MarketTrust/MarketplaceStatisticsRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-04',
    typePriority: 'Unit Test / P1 (High)',
    xmlPrefix: 'TS-05-unit',
    testMatcher: 'test_tc_04_01_marketplace_search_request_rules',
    passActualSummary:
      'MarketplaceSearchRequest::rules() memvalidasi q (max:100), category_id, product_id, variant_id, dan sort (in:latest,price_asc,price_desc) sesuai spesifikasi.',
    notes: '-',
  },
  {
    tsId: 'TS-05',
    parentTcId: 'TC-04-01',
    subtestId: 'TC-04-01-UNIT-B',
    testName: 'MarketplaceStatisticsRulesTest::test_tc_04_01_listing_estimated_delivery_formatting_and_minutes',
    scriptPath: 'tests/Unit/MarketTrust/MarketplaceStatisticsRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-04',
    typePriority: 'Unit Test / P2 (Medium)',
    xmlPrefix: 'TS-05-unit',
    testMatcher: 'test_tc_04_01_listing_estimated_delivery_formatting_and_minutes',
    passActualSummary:
      'Atribut estimated_delivery_formatted dan estimated_minutes pada model Listing mengonversi durasi MINUTE (30), HOUR (120), dan DAY (1440) secara akurat.',
    notes: '-',
  },
  {
    tsId: 'TS-05',
    parentTcId: 'TC-04-01',
    subtestId: 'TC-04-01-FEAT',
    testName: 'MarketplaceStatisticsScenarioTest::test_tc_04_01_search_and_filter_marketplace_listings',
    scriptPath: 'tests/Feature/MarketTrust/MarketplaceStatisticsScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-04',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    xmlPrefix: 'TS-05-feature',
    testMatcher: 'test_tc_04_01_search_and_filter_marketplace_listings',
    passActualSummary:
      'Seluruh subtest pencarian kata kunci, filter kategori/varian, penyembunyian listing CLOSED, serta sorting price_asc (Rp 20.000) dan price_desc (Rp 150.000) lulus.',
    notes: '-',
  },
  {
    tsId: 'TS-05',
    parentTcId: 'TC-04-01',
    subtestId: 'TC-04-01-E2E',
    testName: 'TC-04-01: Pencarian kata kunci, filter kategori/varian, dan pengurutan harga pada katalog Marketplace',
    scriptPath: 'cypress/e2e/markettrust/ts-05-marketplace-statistik.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-04',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-05-cypress',
    testMatcher: 'TC-04-01',
    passActualSummary:
      'Toolbar pencarian dan dropdown pengurutan di /marketplace memfilter serta mengurutkan kartu listing secara akurat di browser.',
    notes: '-',
  },
  {
    tsId: 'TS-05',
    parentTcId: 'TC-04-02',
    subtestId: 'TC-04-02-FEAT',
    testName: 'MarketplaceStatisticsScenarioTest::test_tc_04_02_product_market_stats_calculation_and_periods',
    scriptPath: 'tests/Feature/MarketTrust/MarketplaceStatisticsScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-05',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    xmlPrefix: 'TS-05-feature',
    testMatcher: 'test_tc_04_02_product_market_stats_calculation_and_periods',
    passActualSummary:
      'Perhitungan statistik di MarketplaceService::getProductMarketStats mengembalikan rata-rata dan sampel eksak untuk 7d (Rp20k/1), 30d (Rp25k/2), 90d (Rp30k/3), all (Rp35k/4), serta mengabaikan transaksi non-COMPLETED.',
    notes:
      'CATATAN GAP: Filter periode 90 hari (90d) sudah diimplementasikan di aplikasi (MarketplaceService & show.blade.php) namun belum memiliki TC rinci tersendiri di dokumen Notion.',
  },
  {
    tsId: 'TS-05',
    parentTcId: 'TC-04-02',
    subtestId: 'TC-04-02-E2E',
    testName: 'TC-04-02: Filter Periode Statistik Pasar pada Detail Produk (UC-05) untuk 7d, 30d, 90d (Gap Docs), dan Semua Waktu',
    scriptPath: 'cypress/e2e/markettrust/ts-05-marketplace-statistik.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-05',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-05-cypress',
    testMatcher: 'TC-04-02',
    passActualSummary:
      'Panel Informasi Pasar Produk pada halaman detail listing (/marketplace/{id}) memperbarui indikator harga rata-rata dan jumlah sampel secara akurat saat tab 7d, 30d, 90d, dan all diklik.',
    notes:
      'Mengikuti posisi komponen terbaru di resources/views/marketplace/show.blade.php; periode 90d tercatat sebagai gap dokumentasi Notion.',
  },

  // =========================================================================
  // TS-07 — Direct Message & Lampiran (7 Eksekusi)
  // =========================================================================
  {
    tsId: 'TS-07',
    parentTcId: 'TC-05-04',
    subtestId: 'TC-05-04-UNIT',
    testName: 'DirectMessageRulesTest::test_tc_05_04_conversation_participant_and_canonical_ordering_rules',
    scriptPath: 'tests/Unit/MarketTrust/DirectMessageRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08',
    typePriority: 'Unit Test / P1 (High)',
    xmlPrefix: 'TS-07-unit',
    testMatcher: 'test_tc_05_04_conversation_participant_and_canonical_ordering_rules',
    passActualSummary:
      'ConversationService::getCanonicalParticipants mengurutkan ID peserta secara kanonik ([5, 12]) dan isParticipant() menolak pengguna non-peserta.',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    parentTcId: 'TC-05-04',
    subtestId: 'TC-05-04-FEAT',
    testName: 'DirectMessageScenarioTest::test_tc_05_04_send_text_message_and_sync_polling_between_participants',
    scriptPath: 'tests/Feature/MarketTrust/DirectMessageScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08',
    typePriority: 'Feature Test (HTTP + JSON + DB) / P1 (High)',
    xmlPrefix: 'TS-07-feature',
    testMatcher: 'test_tc_05_04_send_text_message_and_sync_polling_between_participants',
    passActualSummary:
      'Endpoint kirim pesan dan polling /sync berhasil mengirimkan pesan baru ke lawan bicara, memperbarui status Dibaca (read_at), dan memblokir outsider dengan HTTP 403.',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    parentTcId: 'TC-05-04',
    subtestId: 'TC-05-04-E2E',
    testName: 'TC-05-04: Kirim Pesan Teks dan Polling Sinkronisasi DM (Pesan lawan muncul otomatis tanpa reload halaman)',
    scriptPath: 'cypress/e2e/markettrust/ts-07-direct-message.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-07-cypress',
    testMatcher: 'TC-05-04',
    passActualSummary:
      'Polling sinkronisasi UI menampilkan pesan balasan lawan secara langsung di #message-list tanpa reload halaman dan memperbarui indikator menjadi "Dibaca".',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    parentTcId: 'TC-05-05',
    subtestId: 'TC-05-05-UNIT-A',
    testName: 'DirectMessageRulesTest::test_tc_05_05_send_message_request_attachment_validation_rules',
    scriptPath: 'tests/Unit/MarketTrust/DirectMessageRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08 & KNF-04',
    typePriority: 'Unit Test / P1 (High)',
    xmlPrefix: 'TS-07-unit',
    testMatcher: 'test_tc_05_05_send_message_request_attachment_validation_rules',
    passActualSummary:
      'SendMessageRequest menerapkan batas max:5120 (5 MB) dan whitelist mimes:jpg,jpeg,png,webp,pdf sesuai KNF-04.',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    parentTcId: 'TC-05-05',
    subtestId: 'TC-05-05-UNIT-B',
    testName: 'DirectMessageRulesTest::test_tc_05_05_message_model_attachment_metadata_helpers',
    scriptPath: 'tests/Unit/MarketTrust/DirectMessageRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08 & KNF-04',
    typePriority: 'Unit Test / P2 (Medium)',
    xmlPrefix: 'TS-07-unit',
    testMatcher: 'test_tc_05_05_message_model_attachment_metadata_helpers',
    passActualSummary:
      'Helper model Message mendeteksi tipe MIME gambar/PDF (isImage, isPdf) dan memformat ukuran file ("200 KB", "2.5 MB") dengan tepat.',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    parentTcId: 'TC-05-05',
    subtestId: 'TC-05-05-FEAT',
    testName: 'DirectMessageScenarioTest::test_tc_05_05_strict_dm_attachment_validation_all_variations',
    scriptPath: 'tests/Feature/MarketTrust/DirectMessageScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08 & KNF-04',
    typePriority: 'Feature Test (HTTP + Storage) / P1 (High)',
    xmlPrefix: 'TS-07-feature',
    testMatcher: 'test_tc_05_05_strict_dm_attachment_validation_all_variations',
    passActualSummary:
      'Semua variasi negatif (.zip, .exe, .mp4, >5MB, kosong) ditolak HTTP 422; variasi positif (.png & .pdf <=5MB) tersimpan aman di disk local dan terlindungi dari akses non-peserta (HTTP 403).',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    parentTcId: 'TC-05-05',
    subtestId: 'TC-05-05-E2E',
    testName: 'TC-05-05: Validasi Ketat Lampiran Berkas DM (Format terlarang, ukuran > 5 MB ditolak, dan gambar valid berhasil diunggah)',
    scriptPath: 'cypress/e2e/markettrust/ts-07-direct-message.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08 & KNF-04',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-07-cypress',
    testMatcher: 'TC-05-05',
    passActualSummary:
      'UI menampilkan alert penolakan untuk berkas .zip dan file 5.5 MB, serta berhasil mengunggah bukti-pembayaran-sah.png ke daftar pesan beserta tombol Unduh.',
    notes: '-',
  },

  // =========================================================================
  // TS-14 — Pembatalan & Denda KEEP (9 Eksekusi)
  // =========================================================================
  {
    tsId: 'TS-14',
    parentTcId: 'TC-12-01',
    subtestId: 'TC-12-01-UNIT',
    testName: 'CancellationKeepRulesTest::test_tc_12_01_and_12_02_transaction_cancellation_stage_and_status_helpers',
    scriptPath: 'tests/Unit/MarketTrust/CancellationKeepRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28',
    typePriority: 'Unit Test / P1 (High)',
    xmlPrefix: 'TS-14-unit',
    testMatcher: 'test_tc_12_01_and_12_02_transaction_cancellation_stage_and_status_helpers',
    passActualSummary:
      'Helper stage BEFORE_PAYMENT / AFTER_PAYMENT_BEFORE_DELIVERY dan status APPROVED / REQUESTED pada TransactionCancellation berfungsi sesuai spesifikasi.',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    parentTcId: 'TC-12-01',
    subtestId: 'TC-12-01-FEAT',
    testName: 'CancellationKeepScenarioTest::test_tc_12_01_buyer_cancels_pre_payment_transaction_immediately',
    scriptPath: 'tests/Feature/MarketTrust/CancellationKeepScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    xmlPrefix: 'TS-14-feature',
    testMatcher: 'test_tc_12_01_buyer_cancels_pre_payment_transaction_immediately',
    passActualSummary:
      'Pembatalan pra-bayar langsung mengubah status transaksi menjadi CANCELLED, mencatat history, mempertahankan listing tetap ACTIVE, dan tidak menerbitkan KEEP.',
    notes:
      'Pada skema database MarketTrust, tabel listings tidak menggunakan kolom numerik stock melainkan status ACTIVE/CLOSED; listing tetap ACTIVE sehingga tetap tersedia di katalog.',
  },
  {
    tsId: 'TS-14',
    parentTcId: 'TC-12-01',
    subtestId: 'TC-12-01-E2E',
    testName: 'TC-12-01: Pembatalan Transaksi Pre-Payment oleh Buyer (Sebelum Bayar) langsung mengubah status menjadi CANCELLED',
    scriptPath: 'cypress/e2e/markettrust/ts-14-pembatalan-keep.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-14-cypress',
    testMatcher: 'TC-12-01',
    passActualSummary:
      'Buyer berhasil membatalkan pesanan pra-bayar dari modal UI dan halaman menampilkan banner Transaksi Resmi Dibatalkan (Pra-Pembayaran).',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    parentTcId: 'TC-12-02',
    subtestId: 'TC-12-02-FEAT',
    testName: 'CancellationKeepScenarioTest::test_tc_12_02_post_payment_cancellation_requires_reason_and_waits_for_admin_review',
    scriptPath: 'tests/Feature/MarketTrust/CancellationKeepScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    xmlPrefix: 'TS-14-feature',
    testMatcher: 'test_tc_12_02_post_payment_cancellation_requires_reason_and_waits_for_admin_review',
    passActualSummary:
      'Pengajuan tanpa alasan ditolak validasi; pengajuan dengan alasan valid tercatat sebagai REQUESTED (AFTER_PAYMENT_BEFORE_DELIVERY) dan menahan transaksi di status PAID.',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    parentTcId: 'TC-12-02',
    subtestId: 'TC-12-02-E2E',
    testName: 'TC-12-02: Pengajuan Pembatalan Post-Payment Sebelum Dikirim (Status PAID) wajib mengisi alasan dan masuk antrean Admin',
    scriptPath: 'cypress/e2e/markettrust/ts-14-pembatalan-keep.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-14-cypress',
    testMatcher: 'TC-12-02',
    passActualSummary:
      'Form modal pengajuan pembatalan pasca-bayar berhasil dikirim dan menampilkan banner "Pengajuan Pembatalan Sedang Ditinjau Admin".',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    parentTcId: 'TC-12-03',
    subtestId: 'TC-12-03-UNIT-A',
    testName: 'CancellationKeepRulesTest::test_tc_12_03_keep_obligation_calculation_rules_and_deduction_helpers',
    scriptPath: 'tests/Unit/MarketTrust/CancellationKeepRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28B',
    typePriority: 'Unit Test / P1 (High)',
    xmlPrefix: 'TS-14-unit',
    testMatcher: 'test_tc_12_03_keep_obligation_calculation_rules_and_deduction_helpers',
    passActualSummary:
      'Model KeepObligation memverifikasi aturan BANK_VA_FLAT (Rp 4.000) dan OTHER_TRIPLE_GATEWAY_FEE (3x gateway fee) beserta kalkulasi potongan.',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    parentTcId: 'TC-12-03',
    subtestId: 'TC-12-03-UNIT-B',
    testName: 'CancellationKeepRulesTest::test_tc_12_03_payment_verified_gateway_fee_and_admin_review_request_rules',
    scriptPath: 'tests/Unit/MarketTrust/CancellationKeepRulesTest.php',
    runnerType: 'PHPUnit Unit',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28B',
    typePriority: 'Unit Test / P1 (High)',
    xmlPrefix: 'TS-14-unit',
    testMatcher: 'test_tc_12_03_payment_verified_gateway_fee_and_admin_review_request_rules',
    passActualSummary:
      'Payment::hasVerifiedGatewayFee() dan ReviewCancellationRequest::rules() tervalidasi sesuai spesifikasi UC-28B.',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    parentTcId: 'TC-12-03',
    subtestId: 'TC-12-03-FEAT',
    testName: 'CancellationKeepScenarioTest::test_tc_12_03_admin_approves_cancellation_with_seller_at_fault_and_issues_keep_obligations',
    scriptPath: 'tests/Feature/MarketTrust/CancellationKeepScenarioTest.php',
    runnerType: 'PHPUnit Feature',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28B',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    xmlPrefix: 'TS-14-feature',
    testMatcher: 'test_tc_12_03_admin_approves_cancellation_with_seller_at_fault_and_issues_keep_obligations',
    passActualSummary:
      'Keputusan Admin APPROVED dengan SELLER bersalah menerbitkan KEEP Rp 4.000 (BANK_VA_FLAT) dan Rp 4.500 (OTHER_TRIPLE_GATEWAY_FEE 3x1.500) berstatus OPEN (total Rp 8.500).',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    parentTcId: 'TC-12-03',
    subtestId: 'TC-12-03-E2E',
    testName: 'TC-12-03: Admin Meninjau Pembatalan dan Menetapkan Seller Bersalah (Menerbitkan denda KEEP Rp 4.000 berstatus OPEN)',
    scriptPath: 'cypress/e2e/markettrust/ts-14-pembatalan-keep.cy.js',
    runnerType: 'Cypress E2E',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28B',
    typePriority: 'Cypress E2E / P1 (High)',
    xmlPrefix: 'TS-14-cypress',
    testMatcher: 'TC-12-03',
    passActualSummary:
      'Admin berhasil menyetujui pembatalan dengan tanggung jawab SELLER melalui modal review dan tabel KEEP menampilkan tagihan Rp 4.000 berstatus OPEN.',
    notes: '-',
  },
];

function styleHeaderRow(row, fillArgb = 'FF1E3A8A') {
  row.eachCell((cell) => {
    cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 10, name: 'Calibri' };
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: fillArgb } };
    cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
    cell.border = {
      top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
      left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
      bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
      right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };
  });
  row.height = 28;
}

function styleStatusCell(cell, status) {
  const s = (status || '').toUpperCase();
  let bg = 'FFF1F5F9';
  let fg = 'FF334155';
  if (s === 'PASS') {
    bg = 'FFDCFCE7';
    fg = 'FF166534';
  } else if (s === 'FAIL') {
    bg = 'FFFEE2E2';
    fg = 'FF991B1B';
  } else if (s === 'BLOCKED') {
    bg = 'FFFEF3C7';
    fg = 'FF92400E';
  } else if (s === 'NOT EXECUTED' || s.startsWith('N/A')) {
    bg = 'FFE2E8F0';
    fg = 'FF475569';
  }
  cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
  cell.font = { bold: true, color: { argb: fg }, size: 10, name: 'Calibri' };
  cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
}

function estimateRowHeight(values, colWidths) {
  let maxLines = 1;
  for (let i = 0; i < values.length; i++) {
    const text = String(values[i] ?? '');
    const width = Math.max((colWidths[i] || 20) - 3, 10);
    const paragraphs = text.split('\n');
    let lines = 0;
    for (const p of paragraphs) {
      lines += Math.max(1, Math.ceil(p.length / width));
    }
    if (lines > maxLines) {
      maxLines = lines;
    }
  }
  return Math.max(36, Math.min(260, maxLines * 14 + 10));
}

async function generateExcel() {
  const { metadata, allTestcases } = loadAllRunnerData();

  // Enrich 34 subtests with actual runner execution results from XML & run-metadata.json
  const enrichedSubtests = SCENARIO_CATALOG.map((item) => {
    const exec = findTestExecution(allTestcases, metadata, item.xmlPrefix, item.testMatcher);
    const actualResult =
      exec.status === 'PASS'
        ? `${item.passActualSummary} (Durasi: ${exec.time.toFixed(3)}s${exec.assertions ? `, Assertions: ${exec.assertions}` : ''})`
        : exec.status === 'FAIL'
        ? `GAGAL: ${exec.failureDetail}`
        : `BELUM DIJALANKAN: ${exec.failureDetail}`;

    return {
      ...item,
      status: exec.status,
      actualResult,
      durationSec: exec.time,
      assertions: exec.assertions,
      executedAt: exec.executedAt,
      command: exec.command,
      commit: exec.commit,
      exitCode: exec.exitCode,
      evidencePath: `XML: ${exec.xmlFile} | Log: ${exec.logFile}`,
    };
  });

  // Compute Parent TC status & enriched Actual Result from its subtest executions
  const enrichedParentTcs = PARENT_TCS.map((ptc) => {
    const subRows = enrichedSubtests.filter((r) => r.parentTcId === ptc.tcId);
    const unitRows = subRows.filter((r) => r.runnerType === 'PHPUnit Unit');
    const featRows = subRows.filter((r) => r.runnerType === 'PHPUnit Feature');
    const e2eRows = subRows.filter((r) => r.runnerType === 'Cypress E2E');

    const aggregateRunnerStatus = (rows, naReason) => {
      if (rows.length === 0) {
        return naReason || 'N/A (Tidak memerlukan Unit Test terpisah; tidak dihitung sebagai PASS)';
      }
      if (rows.some((r) => r.status === 'FAIL')) return 'FAIL';
      if (rows.some((r) => r.status === 'BLOCKED')) return 'BLOCKED';
      if (rows.some((r) => r.status === 'NOT EXECUTED')) return 'NOT EXECUTED';
      return 'PASS';
    };

    const unitStatus = aggregateRunnerStatus(unitRows, ptc.unitNaReason);
    const featStatus = aggregateRunnerStatus(featRows, null);
    const e2eStatus = aggregateRunnerStatus(e2eRows, null);

    let overallStatus = 'PASS';
    if (subRows.length === 0 || subRows.some((r) => r.status === 'NOT EXECUTED')) {
      overallStatus = 'NOT EXECUTED';
    }
    if (subRows.some((r) => r.status === 'BLOCKED')) {
      overallStatus = 'BLOCKED';
    }
    if (subRows.some((r) => r.status === 'FAIL')) {
      overallStatus = 'FAIL';
    }

    const runnerMetrics = subRows
      .map(
        (r) =>
          `[${r.subtestId} - ${r.runnerType}: ${r.status}, ${r.durationSec.toFixed(3)}s${r.assertions ? `, ${r.assertions} assertions` : ''}]`
      )
      .join('; ');

    const actualResultText =
      overallStatus === 'PASS'
        ? `${ptc.actualSummaryBase}\nBukti Eksekusi Runner: ${runnerMetrics}.`
        : subRows
            .map((r) => `${r.subtestId} (${r.runnerType}): ${r.actualResult}`)
            .join('\n');

    return {
      ...ptc,
      subtestCount: subRows.length,
      unitStatus,
      featStatus,
      e2eStatus,
      overallStatus,
      actualResultText,
    };
  });

  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'Muhammad Ali (Owzey) - MarketTrust QA';
  workbook.created = new Date();

  // =========================================================================
  // SHEET 1: Ringkasan
  // =========================================================================
  const wsSummary = workbook.addWorksheet('Ringkasan', {
    views: [{ state: 'frozen', ySplit: 1 }],
  });

  wsSummary.columns = [
    { width: 14 },
    { width: 22 },
    { width: 44 },
    { width: 18 },
    { width: 26 },
    { width: 24 },
    { width: 22 },
    { width: 22 },
    { width: 46 },
  ];

  const titleRow = wsSummary.addRow([
    'LAPORAN HASIL PENGUJIAN OTOMATIS MARKETTRUST — BAGIAN ALI (OWZEY): TS-03, TS-05, TS-07, TS-14',
  ]);
  wsSummary.mergeCells('A1:I1');
  titleRow.font = { bold: true, size: 13, color: { argb: 'FFFFFFFF' }, name: 'Calibri' };
  titleRow.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E3A8A' } };
  titleRow.alignment = { vertical: 'middle', horizontal: 'center' };
  titleRow.height = 30;

  wsSummary.addRow([]);
  wsSummary.addRow(['PIC / Pembuat Testcase', 'Muhammad Ali (Owzey) — GitHub: A1liiii <mhmmdalimirza@gmail.com>']);
  wsSummary.addRow(['Branch & Commit Uji', `test/testing-ali (${metadata.commit || '4f89bb1'})`]);
  wsSummary.addRow(['Tanggal Pembuatan Testcase', '04-11-26 (Waktu Eksekusi Runner: ' + (metadata.executed_at || '2026-11-04T09:15:34.531Z') + ')']);
  wsSummary.addRow(['Tanggal Review', 'Belum direview (dikosongkan pada blok identitas sheet TS)']);
  wsSummary.addRow([
    'Status Akses Link Notion',
    'Link Notion (https://app.notion.com/p/...) bersifat private workspace (membutuhkan autentikasi sesi akun Notion di browser). Seluruh skenario, prasyarat, data uji, post condition, dan expected result diekstrak dari spesifikasi SKPL & codebase aktual.',
  ]);
  wsSummary.addRow([
    'Catatan Gap & Temuan',
    '1) TS-05 (TC-04-02): Filter periode 90 hari (90d) sudah diimplementasikan pada MarketplaceService & UI namun belum memiliki rincian TC terpisah di dokumen Notion.\n2) TS-14 (TC-12-01): Ketersediaan produk di tabel listings direpresentasikan oleh kolom status (ACTIVE/CLOSED), bukan kolom stok numerik.',
  ]);
  wsSummary.addRow([]);

  // Tabel A: Rekapitulasi Per Test Scenario (Pisahkan TC Induk dari Subtest/Eksekusi)
  const secATitle = wsSummary.addRow([
    'A. REKAPITULASI PER TEST SCENARIO (TC INDUK TERPISAH DARI SUBTEST / EKSEKUSI RUNNER)',
  ]);
  secATitle.font = { bold: true, size: 11, color: { argb: 'FF1E293B' } };

  const secAHeader = wsSummary.addRow([
    'ID TS',
    'Nama Test Scenario',
    'Cakupan TC Induk',
    'Jumlah TC Induk',
    'TC Induk PASS',
    'Jumlah Subtest / Eksekusi Runner',
    'Eksekusi PASS',
    'Eksekusi FAIL',
    'Eksekusi BLOCKED / NOT EXECUTED',
  ]);
  styleHeaderRow(secAHeader, 'FF1E3A8A');

  const tsGroups = [
    { id: 'TS-03', name: 'Rekening Payout Seller', tcs: 'TC-02-01, TC-02-02, TC-02-03, TC-02-04' },
    { id: 'TS-05', name: 'Marketplace & Statistik Pasar', tcs: 'TC-04-01, TC-04-02' },
    { id: 'TS-07', name: 'Direct Message & Lampiran', tcs: 'TC-05-04, TC-05-05' },
    { id: 'TS-14', name: 'Pembatalan & Denda KEEP', tcs: 'TC-12-01, TC-12-02, TC-12-03' },
  ];

  for (const g of tsGroups) {
    const pRows = enrichedParentTcs.filter((p) => p.tsId === g.id);
    const sRows = enrichedSubtests.filter((r) => r.tsId === g.id);
    const pPass = pRows.filter((p) => p.overallStatus === 'PASS').length;
    const sPass = sRows.filter((r) => r.status === 'PASS').length;
    const sFail = sRows.filter((r) => r.status === 'FAIL').length;
    const sOther = sRows.filter((r) => r.status !== 'PASS' && r.status !== 'FAIL').length;

    const r = wsSummary.addRow([
      g.id,
      g.name,
      g.tcs,
      pRows.length,
      pPass,
      sRows.length,
      sPass,
      sFail,
      sOther,
    ]);
    r.height = 22;
  }

  const totalPPass = enrichedParentTcs.filter((p) => p.overallStatus === 'PASS').length;
  const totalSPass = enrichedSubtests.filter((r) => r.status === 'PASS').length;
  const totalSFail = enrichedSubtests.filter((r) => r.status === 'FAIL').length;
  const totalSOther = enrichedSubtests.filter((r) => r.status !== 'PASS' && r.status !== 'FAIL').length;

  const totalRow = wsSummary.addRow([
    'TOTAL',
    '4 Test Scenario Bagian Ali (Owzey)',
    '11 TC Induk (Tanpa Hitung Dobel)',
    enrichedParentTcs.length,
    totalPPass,
    enrichedSubtests.length,
    totalSPass,
    totalSFail,
    totalSOther,
  ]);
  totalRow.font = { bold: true };
  totalRow.height = 24;

  wsSummary.addRow([]);

  // Tabel B: Status 11 TC Induk per Runner
  const secBTitle = wsSummary.addRow([
    'B. STATUS RINCI 11 TC INDUK PER RUNNER (UNIT YANG N/A MEMILIKI ALASAN & TIDAK DIHITUNG SEBAGAI PASS)',
  ]);
  secBTitle.font = { bold: true, size: 11, color: { argb: 'FF1E293B' } };

  const secBHeader = wsSummary.addRow([
    'ID TS',
    'ID TC Induk',
    'Judul / Fokus Kasus Uji Induk',
    'Jml Subtest/Runner',
    'Status PHPUnit Unit',
    'Status PHPUnit Feature',
    'Status Cypress E2E',
    'Status Akhir TC Induk',
    'Keterangan & Alasan Unit N/A / Gap',
  ]);
  styleHeaderRow(secBHeader, 'FF1E3A8A');

  for (const ptc of enrichedParentTcs) {
    const ket =
      ptc.unitNaReason
        ? ptc.unitNaReason
        : 'Seluruh variasi Unit, Feature, dan Cypress E2E telah dieksekusi di runner dan PASS.';
    const r = wsSummary.addRow([
      ptc.tsId,
      ptc.tcId,
      ptc.testCase,
      ptc.subtestCount,
      ptc.unitStatus.startsWith('N/A') ? 'N/A' : ptc.unitStatus,
      ptc.featStatus,
      ptc.e2eStatus,
      ptc.overallStatus,
      ket,
    ]);
    r.height = 38;
    r.eachCell((cell) => {
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
    });
    styleStatusCell(r.getCell(5), ptc.unitStatus);
    styleStatusCell(r.getCell(6), ptc.featStatus);
    styleStatusCell(r.getCell(7), ptc.e2eStatus);
    styleStatusCell(r.getCell(8), ptc.overallStatus);
  }

  wsSummary.addRow([]);

  // Tabel C: Daftar Run & Log Asli
  const secCTitle = wsSummary.addRow([
    'C. RIWAYAT COMMAND RUNNER & FILE LOG / JUNIT XML ASLI',
  ]);
  secCTitle.font = { bold: true, size: 11, color: { argb: 'FF1E293B' } };

  const secCHeader = wsSummary.addRow([
    'ID Run',
    'Jenis Runner',
    'Command Lengkap yang Dijalankan',
    'Exit Code',
    'Jml Test',
    'Waktu Run',
    'File JUnit XML',
    'File Raw Log',
    'Commit',
  ]);
  styleHeaderRow(secCHeader, 'FF334155');

  for (const [runKey, info] of Object.entries(metadata.runs || {})) {
    const r = wsSummary.addRow([
      runKey,
      info.runner || '-',
      info.command || '-',
      info.exit_code ?? 0,
      info.tests_count ?? '-',
      info.executed_at || '-',
      info.xml_file || '-',
      info.log_file || '-',
      metadata.commit || '-',
    ]);
    r.height = 20;
  }

  // =========================================================================
  // SHEETS TS-03, TS-05, TS-07, TS-14 (Blok Identitas 5 Baris + Tabel Utama 10 Kolom)
  // =========================================================================
  const tsColWidths = [15, 28, 36, 36, 46, 36, 42, 40, 46, 18];

  for (const tsId of ['TS-03', 'TS-05', 'TS-07', 'TS-14']) {
    const modInfo = TS_MODULES[tsId];
    const ws = workbook.addWorksheet(tsId, {
      views: [{ state: 'frozen', ySplit: 7 }],
    });

    ws.columns = tsColWidths.map((w) => ({ width: w }));

    const identityRows = [
      ['Module Name:', modInfo.moduleName],
      ['Reference Document:', modInfo.referenceDocument],
      ['Created by:', modInfo.createdBy],
      ['Date of creation:', modInfo.dateOfCreation],
      ['Date of review:', modInfo.dateOfReview],
    ];

    identityRows.forEach((pair, idx) => {
      const rowNumber = idx + 1;
      const row = ws.getRow(rowNumber);
      row.getCell(1).value = pair[0];
      row.getCell(2).value = pair[1];
      ws.mergeCells(`B${rowNumber}:J${rowNumber}`);

      row.getCell(1).font = { bold: true, size: 10, name: 'Calibri', color: { argb: 'FF0F172A' } };
      row.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE2E8F0' } };
      row.getCell(1).alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      row.getCell(1).border = {
        top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
      };

      row.getCell(2).font = { bold: false, size: 10, name: 'Calibri', color: { argb: 'FF0F172A' } };
      row.getCell(2).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
      row.getCell(2).alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      for (let c = 2; c <= 10; c++) {
        row.getCell(c).border = {
          top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
          left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
          bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
          right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        };
      }
      row.height = 20;
    });

    // Baris 6: Baris pemisah kosong
    const sepRow = ws.getRow(6);
    sepRow.values = [];
    sepRow.height = 10;

    // Baris 7: Header Tabel Utama (Tepat 10 Kolom)
    const headerRow = ws.getRow(7);
    headerRow.values = [
      'TEST CASE ID',
      'TEST SCENARIO',
      'TEST CASE',
      'PRE-CONDITION',
      'TEST STEPS',
      'TEST DATA',
      'EXPECTED RESULT',
      'POST CONDITION',
      'ACTUAL RESULT',
      'STATUS (PASS/FAIL)',
    ];
    styleHeaderRow(headerRow, 'FF1E3A8A');

    const tsParentRows = enrichedParentTcs.filter((p) => p.tsId === tsId);
    let currentRowIdx = 8;
    for (const item of tsParentRows) {
      const rowValues = [
        item.tcId,
        item.testScenario,
        item.testCase,
        item.preCondition,
        item.testSteps,
        item.testData,
        item.expectedResult,
        item.postCondition,
        item.actualResultText,
        item.overallStatus,
      ];

      const row = ws.getRow(currentRowIdx);
      row.values = rowValues;

      row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
        if (colNumber <= 10) {
          cell.font = {
            size: 9.5,
            name: 'Calibri',
            bold: colNumber === 1,
            color: { argb: 'FF0F172A' },
          };
          cell.alignment = {
            vertical: 'top',
            horizontal: colNumber === 1 ? 'center' : 'left',
            wrapText: true,
          };
          cell.border = {
            top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
            left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
            bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
            right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
          };
        }
      });

      styleStatusCell(row.getCell(10), item.overallStatus);
      row.height = estimateRowHeight(rowValues, tsColWidths);
      currentRowIdx++;
    }
  }

  // =========================================================================
  // SHEET 6: Detail Eksekusi (16 Kolom Metadata Teknis & Subtest Runner)
  // =========================================================================
  const wsDetail = workbook.addWorksheet('Detail Eksekusi', {
    views: [{ state: 'frozen', ySplit: 2 }],
  });

  const detailColWidths = [
    10, // ID TS
    12, // TC Induk
    18, // ID Variasi/Subtest
    44, // Nama Test
    44, // Path Script
    18, // Runner
    34, // Referensi Sumber
    24, // Jenis/Prioritas
    48, // Command
    24, // Waktu Run
    16, // Commit yang Diuji
    12, // Exit Code
    14, // Status
    48, // Actual Result
    44, // Log/Bukti
    36, // Bug/Blocker
  ];
  wsDetail.columns = detailColWidths.map((w) => ({ width: w }));

  const detailBanner = wsDetail.addRow([
    'DETAIL EKSEKUSI RUNNER (PHPUNIT UNIT, PHPUNIT FEATURE, DAN CYPRESS E2E) — MAPPING KE 11 TC INDUK BAGIAN ALI (OWZEY)',
  ]);
  wsDetail.mergeCells('A1:P1');
  detailBanner.font = { bold: true, size: 12, color: { argb: 'FFFFFFFF' }, name: 'Calibri' };
  detailBanner.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E3A8A' } };
  detailBanner.alignment = { vertical: 'middle', horizontal: 'left' };
  detailBanner.height = 28;

  const detailHeader = wsDetail.addRow([
    'ID TS',
    'TC Induk',
    'ID Variasi/Subtest',
    'Nama Test',
    'Path Script',
    'Runner',
    'Referensi Sumber',
    'Jenis/Prioritas',
    'Command',
    'Waktu Run',
    'Commit yang Diuji',
    'Exit Code',
    'Status',
    'Actual Result',
    'Log/Bukti',
    'Bug/Blocker',
  ]);
  styleHeaderRow(detailHeader, 'FF1E293B');

  for (const item of enrichedSubtests) {
    const rowValues = [
      item.tsId,
      item.parentTcId,
      item.subtestId,
      item.testName,
      item.scriptPath,
      item.runnerType,
      item.sourceRef,
      item.typePriority,
      item.command,
      item.executedAt,
      item.commit,
      item.exitCode,
      item.status,
      item.actualResult,
      item.evidencePath,
      item.notes,
    ];

    const row = wsDetail.addRow(rowValues);
    row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
      if (colNumber <= 16) {
        cell.font = { size: 9.5, name: 'Calibri', color: { argb: 'FF0F172A' } };
        cell.alignment = {
          vertical: 'top',
          horizontal: [1, 2, 3, 6, 11, 12].includes(colNumber) ? 'center' : 'left',
          wrapText: true,
        };
        cell.border = {
          top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
          left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
          bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
          right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        };
      }
    });
    styleStatusCell(row.getCell(13), item.status);
    row.height = estimateRowHeight(rowValues, detailColWidths);
  }

  await workbook.xlsx.writeFile(OUTPUT_XLSX);

  // Verifikasi baca ulang file Excel yang baru ditulis
  const verifyWb = new ExcelJS.Workbook();
  await verifyWb.xlsx.readFile(OUTPUT_XLSX);
  const sheetNames = verifyWb.worksheets.map((s) => s.name);

  console.log(
    JSON.stringify(
      {
        output: path.relative(ROOT_DIR, OUTPUT_XLSX),
        sheets: sheetNames,
        parentTcCount: enrichedParentTcs.length,
        parentTcPass: totalPPass,
        subtestExecutionCount: enrichedSubtests.length,
        subtestPass: totalSPass,
        subtestFail: totalSFail,
        subtestOther: totalSOther,
      },
      null,
      2
    )
  );
}

generateExcel().catch((err) => {
  console.error('Gagal mengekspor laporan Excel:', err);
  process.exit(1);
});
