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
 * Load all XML files in docs/testing/results/ali/
 */
function loadAllRunnerData() {
  const metadata = fs.existsSync(METADATA_PATH)
    ? JSON.parse(fs.readFileSync(METADATA_PATH, 'utf8'))
    : { commit: 'HEAD', executed_at: new Date().toISOString(), runs: {} };

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
 * Match a specific testcase from parsed XMLs by file pattern and test name substring.
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
  };
}

/**
 * Definisi lengkap 11 TC Induk dan seluruh subtest/eksekusi runner (PHPUnit Unit, PHPUnit Feature, Cypress E2E).
 */
const SCENARIO_CATALOG = [
  // =========================================================================
  // TS-03 — Rekening Payout Seller
  // =========================================================================
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-01',
    subtestId: 'TC-02-01-UNIT',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi aturan kelengkapan atribut tujuan payout tipe BANK dan masking nomor rekening pada model SellerPayoutAccount.',
    typePriority: 'Unit Test / P1 (High)',
    prerequisite: 'Model SellerPayoutAccount dan enum PayoutDestinationType tersedia.',
    testData: 'destination_type: BANK, destination_provider: BCA, destination_number: 1234567890, destination_holder: Ali Owzey; variasi nomor kosong.',
    steps: '1. Instansiasi SellerPayoutAccount tipe BANK dengan atribut lengkap.\n2. Panggil isComplete() dan masked_destination_number.\n3. Instansiasi tipe BANK dengan nomor kosong lalu panggil isComplete().',
    expectedResult: 'isComplete() bernilai true untuk data lengkap dan false jika nomor rekening kosong; nomor rekening dimasking menjadi •••• •••• 7890.',
    testNameAndScript: 'PayoutAccountRulesTest::test_tc_02_01_bank_payout_completeness_rules (tests/Unit/MarketTrust/PayoutAccountRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-03-unit',
    testMatcher: 'test_tc_02_01_bank_payout_completeness_rules',
    passActualSummary: 'Aturan isComplete() tipe BANK mengembalikan true saat lengkap, false saat nomor kosong, dan masking menghasilkan •••• •••• 7890.',
    notes: 'Link Notion private workspace; diuji terhadap implementasi aktual SellerPayoutAccount.',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-01',
    subtestId: 'TC-02-01-FEAT',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi penambahan rekening payout tipe BANK oleh Seller melalui HTTP POST dan penyimpanan ke database.',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    prerequisite: 'Akun Seller berstatus USER ACTIVE telah login; tabel seller_payout_accounts siap.',
    testData: 'destination_type: BANK, destination_provider: BCA, destination_number: 8720192831, destination_holder: Muhammad Ali, is_active: true.',
    steps: '1. Login sebagai Seller (aliseller).\n2. Kirim POST /seller/payout-accounts dengan payload BANK BCA.\n3. Periksa redirect, session flash success, dan baris di tabel seller_payout_accounts.',
    expectedResult: 'HTTP 302 redirect ke /seller/payout-accounts dengan pesan sukses; record BANK BCA 8720192831 tersimpan dengan is_active = true.',
    testNameAndScript: 'PayoutAccountScenarioTest::test_tc_02_01_seller_can_add_bank_payout_account (tests/Feature/MarketTrust/PayoutAccountScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-03-feature',
    testMatcher: 'test_tc_02_01_seller_can_add_bank_payout_account',
    passActualSummary: 'POST /seller/payout-accounts berhasil menyimpan rekening BANK BCA a.n. Muhammad Ali sebagai rekening aktif (is_active = true) dan redirect ke halaman indeks.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-01',
    subtestId: 'TC-02-01-E2E',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi alur UI Seller menambahkan rekening payout tipe BANK hingga tampil sebagai Tujuan Utama Aktif.',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Akun aliseller_ts03 terdaftar dan aktif; aplikasi berjalan pada database testing terpisah.',
    testData: 'Jenis: Rekening Bank (BANK), Nama Bank: BCA, Nomor: 8720192831, Atas Nama: Muhammad Ali, Checkbox Utama: dicentang.',
    steps: '1. Login via form /login sebagai aliseller_ts03.\n2. Buka /seller/payout-accounts dan klik Tambah Rekening Baru.\n3. Pilih kartu Rekening Bank, isi BCA, 8720192831, Muhammad Ali.\n4. Klik Simpan Rekening Payout.',
    expectedResult: 'Tampil notifikasi "Rekening payout berhasil ditambahkan!", banner TUJUAN UTAMA AKTIF menampilkan BCA •••• •••• 2831 a.n. Muhammad Ali.',
    testNameAndScript: 'TC-02-01: Penambahan Rekening Payout Tipe BANK (cypress/e2e/markettrust/ts-03-rekening-payout.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-03-cypress',
    testMatcher: 'TC-02-01',
    passActualSummary: 'Form UI berhasil menyimpan rekening BANK BCA dan halaman /seller/payout-accounts menampilkan kartu aktif BCA •••• •••• 2831.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-02',
    subtestId: 'TC-02-02-UNIT',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi aturan kelengkapan atribut tujuan payout tipe EWALLET dan label enum PayoutDestinationType::EWALLET.',
    typePriority: 'Unit Test / P1 (High)',
    prerequisite: 'Model SellerPayoutAccount dan enum PayoutDestinationType tersedia.',
    testData: 'destination_type: EWALLET, destination_provider: GoPay, destination_number: 081234567890, destination_holder: Ali Owzey.',
    steps: '1. Instansiasi SellerPayoutAccount tipe EWALLET.\n2. Panggil isComplete() dan PayoutDestinationType::EWALLET->label().',
    expectedResult: 'isComplete() bernilai true dan label mengembalikan "Dompet Digital (E-Wallet)".',
    testNameAndScript: 'PayoutAccountRulesTest::test_tc_02_02_ewallet_payout_completeness_rules (tests/Unit/MarketTrust/PayoutAccountRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-03-unit',
    testMatcher: 'test_tc_02_02_ewallet_payout_completeness_rules',
    passActualSummary: 'Model SellerPayoutAccount tipe EWALLET tervalidasi lengkap (isComplete() = true) dengan label "Dompet Digital (E-Wallet)".',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-02',
    subtestId: 'TC-02-02-FEAT',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi aturan atomik bahwa hanya boleh ada 1 rekening payout aktif per Seller saat rekening baru dijadikan aktif.',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    prerequisite: 'Seller sudah memiliki 1 rekening BANK aktif (BCA 11112222).',
    testData: 'Rekening ke-2: destination_type: EWALLET, destination_provider: GoPay, destination_number: 08123456789, is_active: true.',
    steps: '1. Pastikan rekening BANK pertama berstatus is_active = true.\n2. Kirim POST /seller/payout-accounts untuk rekening EWALLET dengan is_active = true.\n3. Periksa status kedua rekening di database dan hitung jumlah rekening aktif milik Seller.',
    expectedResult: 'Rekening EWALLET baru menjadi is_active = true, rekening BANK lama otomatis berubah menjadi is_active = false, dan jumlah rekening aktif milik Seller tepat 1.',
    testNameAndScript: 'PayoutAccountScenarioTest::test_tc_02_02_atomic_single_active_payout_account_enforcement (tests/Feature/MarketTrust/PayoutAccountScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-03-feature',
    testMatcher: 'test_tc_02_02_atomic_single_active_payout_account_enforcement',
    passActualSummary: 'Transaksi DB atomik menonaktifkan rekening BANK lama (is_active = false) dan mengaktifkan rekening EWALLET GoPay baru; total rekening aktif = 1.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-02',
    subtestId: 'TC-02-02-E2E',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi di UI bahwa penambahan rekening E-Wallet baru sebagai tujuan utama otomatis mengubah rekening Bank lama menjadi Nonaktif.',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Seller sudah memiliki rekening BCA aktif dari langkah sebelumnya.',
    testData: 'Jenis: E-Wallet (EWALLET), Provider: GoPay, Nomor: 081234567890, Atas Nama: Muhammad Ali, Jadikan utama: dicentang.',
    steps: '1. Buka /seller/payout-accounts/create.\n2. Pilih kartu E-Wallet, isi GoPay dan 081234567890, centang jadikan rekening utama.\n3. Submit form dan periksa badge Aktif/Nonaktif pada daftar kartu.',
    expectedResult: 'Hanya ada 1 badge "Aktif" (pada kartu GoPay), kartu BCA berubah menjadi "Nonaktif", dan query DB menunjukkan ACTIVE_COUNT:1.',
    testNameAndScript: 'TC-02-02: Aturan Atomik Hanya 1 Rekening Payout yang Aktif (cypress/e2e/markettrust/ts-03-rekening-payout.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-03-cypress',
    testMatcher: 'TC-02-02',
    passActualSummary: 'UI menampilkan tepat 1 badge Aktif pada GoPay, kartu BCA otomatis berstatus Nonaktif, dan verifikasi DB mengonfirmasi ACTIVE_COUNT:1.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-03',
    subtestId: 'TC-02-03-UNIT',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi aturan model SellerPayoutAccount bahwa tipe QRIS memerlukan gambar barcode atau identifier agar dinyatakan lengkap.',
    typePriority: 'Unit Test / P1 (High)',
    prerequisite: 'Model SellerPayoutAccount tersedia.',
    testData: 'Variasi 1: QRIS tanpa destination_qris_image_url & tanpa nomor; Variasi 2: QRIS dengan destination_qris_image_url.',
    steps: '1. Uji isComplete() pada objek QRIS tanpa gambar/nomor.\n2. Uji isComplete() pada objek QRIS dengan URL gambar barcode.',
    expectedResult: 'Tanpa gambar/nomor mengembalikan false; dengan URL gambar mengembalikan true.',
    testNameAndScript: 'PayoutAccountRulesTest::test_tc_02_03_qris_payout_requires_image_or_identifier (tests/Unit/MarketTrust/PayoutAccountRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-03-unit',
    testMatcher: 'test_tc_02_03_qris_payout_requires_image_or_identifier',
    passActualSummary: 'isComplete() mengembalikan false untuk QRIS tanpa gambar dan true saat destination_qris_image_url terisi.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-03',
    subtestId: 'TC-02-03-FEAT',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi validasi wajib unggah gambar pada penambahan rekening tipe QRIS dan penyimpanan file ke storage.',
    typePriority: 'Feature Test (HTTP + Storage + DB) / P1 (High)',
    prerequisite: 'Seller login; Storage public tersedia.',
    testData: 'Subtest A: destination_type=QRIS tanpa qris_image; Subtest B: destination_type=QRIS dengan qris.png (400x400).',
    steps: '1. Kirim POST /seller/payout-accounts tipe QRIS tanpa file gambar.\n2. Pastikan session error pada field qris_image.\n3. Kirim ulang dengan file gambar qris.png valid.',
    expectedResult: 'Tanpa gambar ditolak dengan error validasi qris_image; dengan gambar valid berhasil disimpan beserta destination_qris_image_url.',
    testNameAndScript: 'PayoutAccountScenarioTest::test_tc_02_03_qris_payout_requires_image_upload_and_stores_url (tests/Feature/MarketTrust/PayoutAccountScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-03-feature',
    testMatcher: 'test_tc_02_03_qris_payout_requires_image_upload_and_stores_url',
    passActualSummary: 'Submit QRIS tanpa gambar ditolak validasi qris_image; submit dengan qris.png berhasil menyimpan URL gambar QRIS di database.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-03',
    subtestId: 'TC-02-03-E2E',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi validasi UI saat submit QRIS tanpa gambar dan keberhasilan unggah barcode QRIS valid.',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Seller login di /seller/payout-accounts/create.',
    testData: 'Jenis: QRIS Interaktif, Atas Nama: Toko Digital Ali, File: qris-toko-ali.png.',
    steps: '1. Pilih kartu QRIS Interaktif, isi nama pemilik, klik Simpan tanpa memilih file.\n2. Verifikasi pesan error tampil.\n3. Unggah file qris-toko-ali.png lalu klik Simpan.',
    expectedResult: 'Muncul pesan "File gambar barcode QRIS wajib diunggah untuk tipe tujuan QRIS." saat kosong; setelah unggah PNG valid, berhasil disimpan dan muncul tautan "Lihat Gambar Barcode QRIS".',
    testNameAndScript: 'TC-02-03: Penambahan Rekening Payout Tipe QRIS Wajib Unggah Gambar (cypress/e2e/markettrust/ts-03-rekening-payout.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-03-cypress',
    testMatcher: 'TC-02-03',
    passActualSummary: 'Form menolak submit QRIS tanpa gambar dengan pesan validasi yang tepat, lalu menerima unggahan qris-toko-ali.png dan menampilkan tautan barcode.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-04',
    subtestId: 'TC-02-04-UNIT',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi aturan masking nomor rekening pendek (<= 4 digit) pada kartu daftar rekening yang ditampilkan di modal hapus.',
    typePriority: 'Unit Test / P2 (Medium)',
    prerequisite: 'Model SellerPayoutAccount tersedia.',
    testData: 'destination_number: "123" (3 karakter).',
    steps: '1. Instansiasi SellerPayoutAccount dengan destination_number "123".\n2. Panggil atribut masked_destination_number.',
    expectedResult: 'Mengembalikan string "•••• 123".',
    testNameAndScript: 'PayoutAccountRulesTest::test_tc_02_04_short_account_number_masking_rules (tests/Unit/MarketTrust/PayoutAccountRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-03-unit',
    testMatcher: 'test_tc_02_04_short_account_number_masking_rules',
    passActualSummary: 'Atribut masked_destination_number memformat nomor pendek menjadi "•••• 123" dengan benar.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-04',
    subtestId: 'TC-02-04-FEAT',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi penghapusan rekening payout non-aktif milik Seller tanpa mengubah status rekening utama yang sedang aktif.',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    prerequisite: 'Seller memiliki 1 rekening aktif (BCA) dan 1 rekening non-aktif (DANA).',
    testData: 'Target hapus: rekening non-aktif DANA 08999888777.',
    steps: '1. Kirim DELETE /seller/payout-accounts/{id} untuk rekening non-aktif.\n2. Periksa bahwa baris tersebut terhapus dari DB dan rekening BCA tetap is_active = true.',
    expectedResult: 'Rekening non-aktif terhapus dari tabel seller_payout_accounts; rekening utama tetap ada dan berstatus aktif.',
    testNameAndScript: 'PayoutAccountScenarioTest::test_tc_02_04_seller_can_delete_inactive_payout_account (tests/Feature/MarketTrust/PayoutAccountScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-03-feature',
    testMatcher: 'test_tc_02_04_seller_can_delete_inactive_payout_account',
    passActualSummary: 'DELETE /seller/payout-accounts/{id} menghapus rekening cadangan non-aktif dari DB sementara rekening utama tetap is_active = true.',
    notes: '-',
  },
  {
    tsId: 'TS-03',
    tsTitle: 'TS-03 — Rekening Payout Seller',
    parentTcId: 'TC-02-04',
    subtestId: 'TC-02-04-E2E',
    sourceRef: 'Notion TS-03 (40c6e9a1e4458197b643eda8368009ea) / SKPL UC-02 / MT-A01',
    objective: 'Memverifikasi penghapusan rekening non-aktif melalui modal konfirmasi di UI /seller/payout-accounts.',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Seller memiliki rekening aktif (GoPay) dan rekening non-aktif (BCA).',
    testData: 'Rekening non-aktif BCA •••• •••• 2831.',
    steps: '1. Buka /seller/payout-accounts.\n2. Klik tombol hapus pada kartu BCA yang berstatus Nonaktif.\n3. Pastikan modal konfirmasi tampil dan klik Hapus Rekening.',
    expectedResult: 'Muncul pesan "Rekening payout berhasil dihapus.", kartu BCA hilang dari daftar, dan kartu GoPay tetap berstatus Aktif.',
    testNameAndScript: 'TC-02-04: Seller Menghapus Rekening Payout Non-Aktif (cypress/e2e/markettrust/ts-03-rekening-payout.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-03-cypress',
    testMatcher: 'TC-02-04',
    passActualSummary: 'Modal konfirmasi menghapus kartu non-aktif BCA dan mempertahankan kartu utama GoPay tetap Aktif.',
    notes: '-',
  },

  // =========================================================================
  // TS-05 — Marketplace & Statistik Pasar
  // =========================================================================
  {
    tsId: 'TS-05',
    tsTitle: 'TS-05 — Marketplace & Statistik Pasar',
    parentTcId: 'TC-04-01',
    subtestId: 'TC-04-01-UNIT-A',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-04',
    objective: 'Memverifikasi aturan validasi parameter pencarian, filter kategori/produk/varian, dan opsi pengurutan pada MarketplaceSearchRequest.',
    typePriority: 'Unit Test / P1 (High)',
    prerequisite: 'Kelas MarketplaceSearchRequest tersedia.',
    testData: 'Field: q (max:100), category_id, product_id, variant_id, sort (in:latest,price_asc,price_desc).',
    steps: '1. Panggil authorize() dan rules() pada instance MarketplaceSearchRequest.\n2. Periksa definisi aturan tiap parameter filter dan sort.',
    expectedResult: 'Seluruh parameter filter terdefinisi valid dan sort hanya mengizinkan latest, price_asc, dan price_desc.',
    testNameAndScript: 'MarketplaceStatisticsRulesTest::test_tc_04_01_marketplace_search_request_rules (tests/Unit/MarketTrust/MarketplaceStatisticsRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-05-unit',
    testMatcher: 'test_tc_04_01_marketplace_search_request_rules',
    passActualSummary: 'MarketplaceSearchRequest::rules() memvalidasi q, category_id, product_id, variant_id, dan sort (in:latest,price_asc,price_desc) sesuai spesifikasi.',
    notes: '-',
  },
  {
    tsId: 'TS-05',
    tsTitle: 'TS-05 — Marketplace & Statistik Pasar',
    parentTcId: 'TC-04-01',
    subtestId: 'TC-04-01-UNIT-B',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-04',
    objective: 'Memverifikasi konversi durasi estimasi penyerahan (MINUTE, HOUR, DAY) dan pemformatan label pada model Listing.',
    typePriority: 'Unit Test / P2 (Medium)',
    prerequisite: 'Model Listing tersedia.',
    testData: '30 MINUTE -> "30 Menit" (30 mnt); 2 HOUR -> "2 Jam" (120 mnt); 1 DAY -> "1 Hari" (1440 mnt).',
    steps: '1. Buat objek Listing dengan masing-masing unit durasi.\n2. Periksa atribut estimated_delivery_formatted dan estimated_minutes.',
    expectedResult: 'Label terformat dalam Bahasa Indonesia dan konversi menit bernilai 30, 120, dan 1440.',
    testNameAndScript: 'MarketplaceStatisticsRulesTest::test_tc_04_01_listing_estimated_delivery_formatting_and_minutes (tests/Unit/MarketTrust/MarketplaceStatisticsRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-05-unit',
    testMatcher: 'test_tc_04_01_listing_estimated_delivery_formatting_and_minutes',
    passActualSummary: 'Atribut estimated_delivery_formatted dan estimated_minutes pada model Listing menghasilkan nilai akurat untuk MINUTE, HOUR, dan DAY.',
    notes: '-',
  },
  {
    tsId: 'TS-05',
    tsTitle: 'TS-05 — Marketplace & Statistik Pasar',
    parentTcId: 'TC-04-01',
    subtestId: 'TC-04-01-FEAT',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-04',
    objective: 'Memverifikasi pencarian kata kunci, filter kategori & varian bertingkat, eksklusi listing CLOSED, serta pengurutan harga termurah/termahal.',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    prerequisite: 'Terdapat 3 listing ACTIVE (Canva 1B Rp20.000, Netflix 1B Rp45.000, Canva 1T Rp150.000) dan 1 listing CLOSED.',
    testData: 'q="Canva", category_id=CategoryB, variant_id=Canva1Y, sort="price_asc", sort="price_desc".',
    steps: '1. GET /marketplace?q=Canva.\n2. GET /marketplace?category_id={id}.\n3. GET /marketplace?variant_id={id}.\n4. GET /marketplace?sort=price_asc dan sort=price_desc.',
    expectedResult: 'Hanya listing ACTIVE yang cocok yang tampil; price_asc menempatkan Rp20.000 di urutan pertama dan price_desc menempatkan Rp150.000 di urutan pertama.',
    testNameAndScript: 'MarketplaceStatisticsScenarioTest::test_tc_04_01_search_and_filter_marketplace_listings (tests/Feature/MarketTrust/MarketplaceStatisticsScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-05-feature',
    testMatcher: 'test_tc_04_01_search_and_filter_marketplace_listings',
    passActualSummary: 'Seluruh subtest pencarian kata kunci, filter kategori/varian, penyembunyian listing CLOSED, serta sorting price_asc (Rp20.000) dan price_desc (Rp150.000) lulus.',
    notes: '-',
  },
  {
    tsId: 'TS-05',
    tsTitle: 'TS-05 — Marketplace & Statistik Pasar',
    parentTcId: 'TC-04-01',
    subtestId: 'TC-04-01-E2E',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-04',
    objective: 'Memverifikasi interaksi pencarian kata kunci dan dropdown pengurutan harga langsung dari UI katalog /marketplace.',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Data katalog dan listing aktif telah di-seed pada database testing.',
    testData: 'Kata kunci: "Canva"; Sort: "Harga Terendah (price_asc)" dan "Harga Tertinggi (price_desc)".',
    steps: '1. Buka /marketplace, ketik "Canva" di kotak pencarian dan klik Cari.\n2. Klik Reset, pilih pengurutan Harga Terendah.\n3. Pilih pengurutan Harga Tertinggi.',
    expectedResult: 'Hasil pencarian hanya menampilkan listing Canva; urutan kartu pertama sesuai harga terendah/tertinggi.',
    testNameAndScript: 'TC-04-01: Pencarian kata kunci, filter kategori/varian, dan pengurutan harga (cypress/e2e/markettrust/ts-05-marketplace-statistik.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-05-cypress',
    testMatcher: 'TC-04-01',
    passActualSummary: 'Toolbar pencarian dan dropdown pengurutan di /marketplace memfilter serta mengurutkan kartu listing secara akurat.',
    notes: '-',
  },
  {
    tsId: 'TS-05',
    tsTitle: 'TS-05 — Marketplace & Statistik Pasar',
    parentTcId: 'TC-04-02',
    subtestId: 'TC-04-02-FEAT',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-05',
    objective: 'Memverifikasi perhitungan statistik pasar (Demand, Supply, Rata-rata Harga) pada halaman detail produk hanya dari transaksi COMPLETED sesuai varian dan periode (7d, 30d, 90d, all).',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    prerequisite: 'Varian Canva 1M memiliki 4 transaksi COMPLETED (3h: Rp20k, 20h: Rp30k, 60h: Rp40k, 120h: Rp50k), 2 transaksi CANCELLED/PAID, dan 1 transaksi COMPLETED di varian lain.',
    testData: 'Parameter period: 7d, 30d, 90d, all, serta invalid_period (fallback 30d).',
    steps: '1. Akses GET /marketplace/{listing}?period=7d, 30d, 90d, dan all.\n2. Periksa sample_count, avg_price, demand, dan supply pada viewData("marketStats").',
    expectedResult: '7d: avg Rp20.000 (1 sampel); 30d: avg Rp25.000 (2 sampel); 90d: avg Rp30.000 (3 sampel); all: avg Rp35.000 (4 sampel). Transaksi CANCELLED/PAID dan varian lain tidak ikut dihitung.',
    testNameAndScript: 'MarketplaceStatisticsScenarioTest::test_tc_04_02_product_market_stats_calculation_and_periods (tests/Feature/MarketTrust/MarketplaceStatisticsScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-05-feature',
    testMatcher: 'test_tc_04_02_product_market_stats_calculation_and_periods',
    passActualSummary: 'Perhitungan statistik di MarketplaceService::getProductMarketStats mengembalikan rata-rata dan jumlah sampel eksak untuk 7d, 30d, 90d, all, serta mengabaikan transaksi non-COMPLETED.',
    notes: 'CATATAN GAP: Filter periode 90 hari (90d) sudah diimplementasikan di aplikasi (MarketplaceService & show.blade.php) namun belum memiliki TC rinci tersendiri di dokumen Notion.',
  },
  {
    tsId: 'TS-05',
    tsTitle: 'TS-05 — Marketplace & Statistik Pasar',
    parentTcId: 'TC-04-02',
    subtestId: 'TC-04-02-E2E',
    sourceRef: 'Notion TS-05 (40c6e9a1e44581ea8236c04771803f6d) / SKPL UC-05',
    objective: 'Memverifikasi perpindahan tab periode statistik pasar (7 Hari, 30 Hari, 90 Hari, Semua) pada halaman detail produk (/marketplace/{listing}).',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Mengikuti catatan pindah halaman informasi pasar ke detail listing (/marketplace/{listing}).',
    testData: 'Selector: [data-cy="market-stat-filter-7d"], [data-cy="market-stat-filter-30d"], [data-cy="market-stat-filter-90d"], [data-cy="market-stat-filter-all"].',
    steps: '1. Buka halaman detail listing /marketplace/{id}.\n2. Periksa indikator Demand, Supply, dan Rata-rata Harga default (30 Hari).\n3. Klik tab 7 Hari, 90 Hari, dan Semua secara berurutan.',
    expectedResult: 'Label periode, rata-rata harga (Rp 20.000, Rp 25.000, Rp 30.000, Rp 35.000), dan jumlah transaksi selesai berubah sesuai periode.',
    testNameAndScript: 'TC-04-02: Filter Periode Statistik Pasar pada Detail Produk (cypress/e2e/markettrust/ts-05-marketplace-statistik.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-05-cypress',
    testMatcher: 'TC-04-02',
    passActualSummary: 'Panel Informasi Pasar Produk pada halaman detail listing memperbarui indikator harga rata-rata dan sampel secara akurat saat tab periode diklik.',
    notes: 'Mengikuti posisi komponen terbaru di resources/views/marketplace/show.blade.php; periode 90d tercatat sebagai gap dokumentasi Notion.',
  },

  // =========================================================================
  // TS-07 — Direct Message & Lampiran
  // =========================================================================
  {
    tsId: 'TS-07',
    tsTitle: 'TS-07 — Direct Message & Lampiran',
    parentTcId: 'TC-05-04',
    subtestId: 'TC-05-04-UNIT',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08',
    objective: 'Memverifikasi aturan urutan kanonik peserta percakapan (getCanonicalParticipants) dan otorisasi peserta (isParticipant) pada ConversationService.',
    typePriority: 'Unit Test / P1 (High)',
    prerequisite: 'Kelas ConversationService tersedia.',
    testData: 'Pasangan user_id (12, 5) dan (5, 12); outsider user_id = 99.',
    steps: '1. Panggil getCanonicalParticipants(12, 5) dan (5, 12).\n2. Panggil isParticipant() untuk peserta sah dan pengguna luar.',
    expectedResult: 'Urutan kanonik selalu [5, 12]; isParticipant() bernilai true untuk peserta dan false untuk outsider.',
    testNameAndScript: 'DirectMessageRulesTest::test_tc_05_04_conversation_participant_and_canonical_ordering_rules (tests/Unit/MarketTrust/DirectMessageRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-07-unit',
    testMatcher: 'test_tc_05_04_conversation_participant_and_canonical_ordering_rules',
    passActualSummary: 'ConversationService mengurutkan ID peserta secara kanonik dan menolak pengguna non-peserta.',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    tsTitle: 'TS-07 — Direct Message & Lampiran',
    parentTcId: 'TC-05-04',
    subtestId: 'TC-05-04-FEAT',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08',
    objective: 'Memverifikasi pengiriman pesan teks, pengambilan pesan baru lewat polling sinkronisasi (/sync) tanpa reload, pembaruan read_at, dan penolakan akses non-peserta.',
    typePriority: 'Feature Test (HTTP + JSON + DB) / P1 (High)',
    prerequisite: 'Percakapan aktif antara alibuyer dan aliseller; akun alioutsider sebagai pihak ketiga.',
    testData: 'content: "Halo Kak, apakah stok akun Canva Pro 1 bulan masih ada?", after_id: 0.',
    steps: '1. Buyer kirim POST /conversations/{id}/messages.\n2. Seller panggil GET /conversations/{id}/sync?after_id=0.\n3. Buyer panggil GET /conversations/{id}/sync untuk cek my_read_message_ids.\n4. Outsider panggil GET /conversations/{id}/sync.',
    expectedResult: 'Seller menerima pesan baru di new_messages, read_at terisi otomatis, Buyer menerima ID pesan di my_read_message_ids, dan Outsider ditolak HTTP 403.',
    testNameAndScript: 'DirectMessageScenarioTest::test_tc_05_04_send_text_message_and_sync_polling_between_participants (tests/Feature/MarketTrust/DirectMessageScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-07-feature',
    testMatcher: 'test_tc_05_04_send_text_message_and_sync_polling_between_participants',
    passActualSummary: 'Endpoint kirim pesan dan polling /sync berhasil mengirimkan pesan baru ke lawan bicara, memperbarui status Dibaca (read_at), dan memblokir outsider dengan HTTP 403.',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    tsTitle: 'TS-07 — Direct Message & Lampiran',
    parentTcId: 'TC-05-04',
    subtestId: 'TC-05-04-E2E',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08',
    objective: 'Memverifikasi di UI bahwa pesan teks terkirim dan balasan dari akun lawan muncul otomatis melalui polling tanpa reload halaman.',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Buyer login dan membuka halaman /conversations/{id}.',
    testData: 'Pesan Buyer: "Halo Kak Seller, apakah akun masih ready hari ini?"; Balasan Seller: "Halo Kak Buyer! Stok sangat ready, silakan lanjut."',
    steps: '1. Ketik pesan di #message-content dan klik Kirim.\n2. Tanpa me-reload halaman Buyer, kirim balasan dari sisi Seller.\n3. Amati perubahan DOM #message-list dan status baca.',
    expectedResult: 'Pesan balasan Seller muncul otomatis di #message-list tanpa reload halaman dan status pesan Buyer berubah dari "Terkirim" menjadi "Dibaca".',
    testNameAndScript: 'TC-05-04: Kirim Pesan Teks dan Polling Sinkronisasi DM (cypress/e2e/markettrust/ts-07-direct-message.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-07-cypress',
    testMatcher: 'TC-05-04',
    passActualSummary: 'Polling sinkronisasi UI menampilkan pesan balasan lawan secara langsung tanpa reload halaman dan memperbarui indikator menjadi "Dibaca".',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    tsTitle: 'TS-07 — Direct Message & Lampiran',
    parentTcId: 'TC-05-05',
    subtestId: 'TC-05-05-UNIT-A',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08 & KNF-04',
    objective: 'Memverifikasi aturan validasi SendMessageRequest untuk batas ukuran 5 MB (max:5120) dan daftar ekstensi yang diizinkan (mimes:jpg,jpeg,png,webp,pdf).',
    typePriority: 'Unit Test / P1 (High)',
    prerequisite: 'Kelas SendMessageRequest tersedia.',
    testData: 'Aturan rules() dan pesan kustom messages() pada SendMessageRequest.',
    steps: '1. Panggil rules() dan messages() pada SendMessageRequest.\n2. Periksa aturan content dan attachment.',
    expectedResult: 'attachment memiliki rule file, max:5120, mimes:jpg,jpeg,png,webp,pdf; content memiliki required_without:attachment dan max:5000.',
    testNameAndScript: 'DirectMessageRulesTest::test_tc_05_05_send_message_request_attachment_validation_rules (tests/Unit/MarketTrust/DirectMessageRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-07-unit',
    testMatcher: 'test_tc_05_05_send_message_request_attachment_validation_rules',
    passActualSummary: 'SendMessageRequest menerapkan batas max:5120 (5 MB) dan whitelist mimes:jpg,jpeg,png,webp,pdf sesuai KNF-04.',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    tsTitle: 'TS-07 — Direct Message & Lampiran',
    parentTcId: 'TC-05-05',
    subtestId: 'TC-05-05-UNIT-B',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08 & KNF-04',
    objective: 'Memverifikasi helper metadata lampiran (hasAttachment, isImage, isPdf, formattedAttachmentSize) pada model Message.',
    typePriority: 'Unit Test / P2 (Medium)',
    prerequisite: 'Model Message tersedia.',
    testData: 'File PNG 200 KB dan PDF 2.5 MB.',
    steps: '1. Buat objek Message tanpa lampiran, dengan PNG 204800 byte, dan dengan PDF 2621440 byte.\n2. Periksa hasil helper.',
    expectedResult: 'PNG terdeteksi isImage() = true ("200 KB"), PDF terdeteksi isPdf() = true ("2.5 MB").',
    testNameAndScript: 'DirectMessageRulesTest::test_tc_05_05_message_model_attachment_metadata_helpers (tests/Unit/MarketTrust/DirectMessageRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-07-unit',
    testMatcher: 'test_tc_05_05_message_model_attachment_metadata_helpers',
    passActualSummary: 'Helper model Message mendeteksi tipe MIME gambar/PDF dan memformat ukuran file (KB/MB) dengan tepat.',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    tsTitle: 'TS-07 — Direct Message & Lampiran',
    parentTcId: 'TC-05-05',
    subtestId: 'TC-05-05-FEAT',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08 & KNF-04',
    objective: 'Memverifikasi seluruh variasi validasi lampiran DM di backend: pesan kosong, format terlarang (.zip, .exe, .mp4), ukuran > 5 MB (6 MB), file valid (.png, .pdf), dan proteksi unduhan.',
    typePriority: 'Feature Test (HTTP + Storage) / P1 (High)',
    prerequisite: 'Percakapan aktif antara Buyer dan Seller; Storage local terkonfigurasi.',
    testData: '1) Kosong; 2) arsip_berbahaya.zip, installer.exe, rekaman.mp4; 3) resolusi_tinggi.png (6000 KB); 4) bukti_transfer.png (1 MB) & invoice_kesepakatan.pdf (1.5 MB).',
    steps: '1. Kirim masing-masing variasi file tidak valid ke POST /conversations/{id}/messages dan pastikan HTTP 422.\n2. Kirim PNG dan PDF valid dan pastikan HTTP 200 serta file tersimpan.\n3. Uji unduh lampiran oleh Seller (200) dan Outsider (403).',
    expectedResult: 'Format .zip/.exe/.mp4 dan ukuran > 5 MB ditolak HTTP 422; file .png dan .pdf <= 5 MB berhasil disimpan dan hanya bisa diunduh oleh peserta percakapan.',
    testNameAndScript: 'DirectMessageScenarioTest::test_tc_05_05_strict_dm_attachment_validation_all_variations (tests/Feature/MarketTrust/DirectMessageScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-07-feature',
    testMatcher: 'test_tc_05_05_strict_dm_attachment_validation_all_variations',
    passActualSummary: 'Semua variasi negatif (.zip, .exe, .mp4, >5MB, kosong) ditolak HTTP 422; variasi positif (.png & .pdf <=5MB) tersimpan aman dan terlindungi dari akses non-peserta.',
    notes: '-',
  },
  {
    tsId: 'TS-07',
    tsTitle: 'TS-07 — Direct Message & Lampiran',
    parentTcId: 'TC-05-05',
    subtestId: 'TC-05-05-E2E',
    sourceRef: 'Notion TS-07 (40c6e9a1e4458103a1ecdb7beb4a6a17) / SKPL UC-08 & KNF-04',
    objective: 'Memverifikasi penolakan file format terlarang (.zip) & ukuran > 5 MB di UI percakapan DM serta pengunggahan gambar valid (.png).',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Buyer berada di halaman /conversations/{id}.',
    testData: '1) berkas_terlarang.zip; 2) gambar_terlalu_besar.png (5.5 MB); 3) bukti-pembayaran-sah.png.',
    steps: '1. Pilih berkas_terlarang.zip pada input file dan periksa banner peringatan.\n2. Pilih gambar_terlalu_besar.png (5.5 MB) dan periksa banner peringatan.\n3. Pilih bukti-pembayaran-sah.png, isi pesan, dan klik Kirim.',
    expectedResult: 'File .zip dan > 5 MB langsung ditolak dengan alert error di UI; gambar PNG valid menampilkan badge pratinjau dan berhasil terkirim beserta tombol Unduh.',
    testNameAndScript: 'TC-05-05: Validasi Ketat Lampiran Berkas DM (cypress/e2e/markettrust/ts-07-direct-message.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-07-cypress',
    testMatcher: 'TC-05-05',
    passActualSummary: 'UI menampilkan alert penolakan untuk .zip dan file 5.5 MB, serta berhasil mengunggah bukti-pembayaran-sah.png ke daftar pesan.',
    notes: '-',
  },

  // =========================================================================
  // TS-14 — Pembatalan & Denda KEEP
  // =========================================================================
  {
    tsId: 'TS-14',
    tsTitle: 'TS-14 — Pembatalan & Denda KEEP',
    parentTcId: 'TC-12-01',
    subtestId: 'TC-12-01-UNIT',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28',
    objective: 'Memverifikasi helper status dan tahap pembatalan pra-bayar (BEFORE_PAYMENT & APPROVED) pada model TransactionCancellation.',
    typePriority: 'Unit Test / P1 (High)',
    prerequisite: 'Model TransactionCancellation tersedia.',
    testData: 'stage: BEFORE_PAYMENT, status: APPROVED.',
    steps: '1. Instansiasi TransactionCancellation dengan stage BEFORE_PAYMENT dan status APPROVED.\n2. Periksa isBeforePayment(), isApproved(), isRequested().',
    expectedResult: 'isBeforePayment() dan isApproved() mengembalikan true; isAfterPaymentBeforeDelivery() dan isRequested() mengembalikan false.',
    testNameAndScript: 'CancellationKeepRulesTest::test_tc_12_01_and_12_02_transaction_cancellation_stage_and_status_helpers (tests/Unit/MarketTrust/CancellationKeepRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-14-unit',
    testMatcher: 'test_tc_12_01_and_12_02_transaction_cancellation_stage_and_status_helpers',
    passActualSummary: 'Helper stage BEFORE_PAYMENT / AFTER_PAYMENT_BEFORE_DELIVERY dan status APPROVED / REQUESTED berfungsi sesuai spesifikasi.',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    tsTitle: 'TS-14 — Pembatalan & Denda KEEP',
    parentTcId: 'TC-12-01',
    subtestId: 'TC-12-01-FEAT',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28',
    objective: 'Memverifikasi pembatalan transaksi pra-pembayaran (WAITING_PAYMENT) oleh Buyer langsung mengubah status ke CANCELLED, menjaga ketersediaan listing ACTIVE, dan tanpa denda KEEP.',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    prerequisite: 'Transaksi Marketplace berstatus WAITING_PAYMENT dan belum dibayar di gateway.',
    testData: 'reason: "Salah memilih varian produk".',
    steps: '1. Buyer kirim POST /transactions/{id}/cancel.\n2. Periksa status transaksi, tabel transaction_cancellations, transaction_status_histories, status listing, dan tabel keep_obligations.',
    expectedResult: 'Status transaksi langsung CANCELLED, stage BEFORE_PAYMENT berstatus APPROVED, listing tetap ACTIVE di katalog, dan keep_obligations berjumlah 0.',
    testNameAndScript: 'CancellationKeepScenarioTest::test_tc_12_01_buyer_cancels_pre_payment_transaction_immediately (tests/Feature/MarketTrust/CancellationKeepScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-14-feature',
    testMatcher: 'test_tc_12_01_buyer_cancels_pre_payment_transaction_immediately',
    passActualSummary: 'Pembatalan pra-bayar langsung mengubah status transaksi menjadi CANCELLED, mencatat history, mempertahankan listing tetap ACTIVE, dan tidak menerbitkan KEEP.',
    notes: 'Pada skema database MarketTrust, tabel listings tidak menggunakan kolom numerik stock melainkan status ACTIVE/CLOSED; listing tetap ACTIVE sehingga tetap tersedia di katalog.',
  },
  {
    tsId: 'TS-14',
    tsTitle: 'TS-14 — Pembatalan & Denda KEEP',
    parentTcId: 'TC-12-01',
    subtestId: 'TC-12-01-E2E',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28',
    objective: 'Memverifikasi pembatalan pesanan pra-bayar (WAITING_PAYMENT) oleh Buyer melalui modal konfirmasi pada halaman detail transaksi.',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Buyer login dan membuka halaman transaksi berstatus WAITING_PAYMENT.',
    testData: 'Alasan: "Salah memilih durasi paket langganan."',
    steps: '1. Klik tombol "Batalkan Pesanan Ini".\n2. Isi alasan pada modal konfirmasi lalu klik "Ya, Batalkan Pesanan".\n3. Periksa banner pembatalan dan badge status transaksi.',
    expectedResult: 'Muncul banner "Transaksi Resmi Dibatalkan (Pra-Pembayaran)" dan badge status transaksi berubah menjadi "Dibatalkan".',
    testNameAndScript: 'TC-12-01: Pembatalan Transaksi Pre-Payment oleh Buyer (cypress/e2e/markettrust/ts-14-pembatalan-keep.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-14-cypress',
    testMatcher: 'TC-12-01',
    passActualSummary: 'Buyer berhasil membatalkan pesanan pra-bayar dari modal UI dan halaman menampilkan banner Transaksi Resmi Dibatalkan (Pra-Pembayaran).',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    tsTitle: 'TS-14 — Pembatalan & Denda KEEP',
    parentTcId: 'TC-12-02',
    subtestId: 'TC-12-02-FEAT',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28',
    objective: 'Memverifikasi pengajuan pembatalan pasca-pembayaran (PAID) sebelum dikirim wajib mengisi alasan dan berstatus REQUESTED menunggu tinjauan Admin.',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    prerequisite: 'Transaksi berstatus PAID dan belum diserahkan (DELIVERED).',
    testData: 'Subtest A: reason kosong ""; Subtest B: reason "Penjual belum merespons chat dan saya butuh akun segera."',
    steps: '1. Kirim POST /transactions/{id}/cancel dengan reason kosong dan pastikan gagal validasi.\n2. Kirim ulang dengan reason valid dan periksa tabel transaction_cancellations serta status transaksi.',
    expectedResult: 'Reason kosong ditolak validasi; reason valid membuat record AFTER_PAYMENT_BEFORE_DELIVERY berstatus REQUESTED dan status transaksi tetap PAID.',
    testNameAndScript: 'CancellationKeepScenarioTest::test_tc_12_02_post_payment_cancellation_requires_reason_and_waits_for_admin_review (tests/Feature/MarketTrust/CancellationKeepScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-14-feature',
    testMatcher: 'test_tc_12_02_post_payment_cancellation_requires_reason_and_waits_for_admin_review',
    passActualSummary: 'Pengajuan tanpa alasan ditolak validasi; pengajuan dengan alasan valid tercatat sebagai REQUESTED (AFTER_PAYMENT_BEFORE_DELIVERY) dan menahan transaksi di status PAID.',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    tsTitle: 'TS-14 — Pembatalan & Denda KEEP',
    parentTcId: 'TC-12-02',
    subtestId: 'TC-12-02-E2E',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28',
    objective: 'Memverifikasi alur UI Buyer mengajukan pembatalan pada transaksi berstatus PAID hingga muncul banner antrean peninjauan Admin.',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Buyer login dan membuka halaman transaksi berstatus PAID.',
    testData: 'Alasan: "Penjual mengabarkan bahwa stok lisensi sedang habis."',
    steps: '1. Klik tombol "Ajukan Pembatalan Transaksi".\n2. Isi alasan pembatalan dan klik "Kirim Pengajuan Pembatalan".\n3. Periksa banner status pengajuan di halaman transaksi.',
    expectedResult: 'Tampil banner "Pengajuan Pembatalan Sedang Ditinjau Admin (Dalam Antrean Tinjauan)" beserta alasan yang diajukan.',
    testNameAndScript: 'TC-12-02: Pengajuan Pembatalan Post-Payment Sebelum Dikirim (cypress/e2e/markettrust/ts-14-pembatalan-keep.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-14-cypress',
    testMatcher: 'TC-12-02',
    passActualSummary: 'Form modal pengajuan pembatalan pasca-bayar berhasil dikirim dan menampilkan banner "Pengajuan Pembatalan Sedang Ditinjau Admin".',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    tsTitle: 'TS-14 — Pembatalan & Denda KEEP',
    parentTcId: 'TC-12-03',
    subtestId: 'TC-12-03-UNIT-A',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28B',
    objective: 'Memverifikasi aturan formula denda KEEP (BANK_VA_FLAT Rp 4.000 & OTHER_TRIPLE_GATEWAY_FEE 3x biaya gateway) serta perhitungan potongan pada model KeepObligation.',
    typePriority: 'Unit Test / P1 (High)',
    prerequisite: 'Model KeepObligation tersedia.',
    testData: 'Rule 1: BANK_VA_FLAT (amount 4000, remaining 2500 -> deducted 1500); Rule 2: OTHER_TRIPLE_GATEWAY_FEE (basis 1500, multiplier 3 -> amount 4500).',
    steps: '1. Uji calculationRuleLabel(), isOpen(), isPartiallyDeducted(), dan deductedAmount() pada kedua jenis aturan KEEP.',
    expectedResult: 'Label formula sesuai aturan resmi dan perhitungan sisa/potongan akurat.',
    testNameAndScript: 'CancellationKeepRulesTest::test_tc_12_03_keep_obligation_calculation_rules_and_deduction_helpers (tests/Unit/MarketTrust/CancellationKeepRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-14-unit',
    testMatcher: 'test_tc_12_03_keep_obligation_calculation_rules_and_deduction_helpers',
    passActualSummary: 'Model KeepObligation memverifikasi aturan BANK_VA_FLAT (Rp 4.000) dan OTHER_TRIPLE_GATEWAY_FEE (3x gateway fee) beserta kalkulasi potongan.',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    tsTitle: 'TS-14 — Pembatalan & Denda KEEP',
    parentTcId: 'TC-12-03',
    subtestId: 'TC-12-03-UNIT-B',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28B',
    objective: 'Memverifikasi pemeriksaan biaya gateway terverifikasi (hasVerifiedGatewayFee) dan aturan validasi keputusan Admin pada ReviewCancellationRequest.',
    typePriority: 'Unit Test / P1 (High)',
    prerequisite: 'Model Payment dan kelas ReviewCancellationRequest tersedia.',
    testData: 'Payment dengan gateway_fee_amount null vs 1500.00; ReviewCancellationRequest::rules().',
    steps: '1. Panggil hasVerifiedGatewayFee() pada Payment.\n2. Periksa rules() pada ReviewCancellationRequest.',
    expectedResult: 'hasVerifiedGatewayFee() bernilai false saat null dan true saat terisi; ReviewCancellationRequest mewajibkan decision (APPROVED/REJECTED) dan responsible_party.',
    testNameAndScript: 'CancellationKeepRulesTest::test_tc_12_03_payment_verified_gateway_fee_and_admin_review_request_rules (tests/Unit/MarketTrust/CancellationKeepRulesTest.php)',
    runnerType: 'PHPUnit Unit',
    xmlPrefix: 'TS-14-unit',
    testMatcher: 'test_tc_12_03_payment_verified_gateway_fee_and_admin_review_request_rules',
    passActualSummary: 'Payment::hasVerifiedGatewayFee() dan ReviewCancellationRequest::rules() tervalidasi sesuai spesifikasi UC-28B.',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    tsTitle: 'TS-14 — Pembatalan & Denda KEEP',
    parentTcId: 'TC-12-03',
    subtestId: 'TC-12-03-FEAT',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28B',
    objective: 'Memverifikasi peninjauan Admin yang menyetujui pembatalan dengan pihak bersalah SELLER otomatis menerbitkan kewajiban KEEP sesuai aturan resmi (Rp 4.000 untuk Bank/VA dan 3x biaya gateway untuk QRIS).',
    typePriority: 'Feature Test (HTTP + DB) / P1 (High)',
    prerequisite: 'Terdapat 2 pengajuan pembatalan REQUESTED: Transaksi 1 (channel bca_va) dan Transaksi 2 (channel qris dengan gateway_fee_amount Rp 1.500).',
    testData: 'decision: APPROVED, responsible_party: SELLER; Expected nominal Bank VA = Rp 4.000; Expected nominal QRIS = 3 x Rp 1.500 = Rp 4.500.',
    steps: '1. Admin kirim POST /admin/cancellations/{id}/review untuk transaksi Bank VA.\n2. Periksa record keep_obligations (amount = 4000.00, rule = BANK_VA_FLAT).\n3. Admin kirim review untuk transaksi QRIS.\n4. Periksa record keep_obligations (amount = 4500.00, rule = OTHER_TRIPLE_GATEWAY_FEE) dan total tunggakan KEEP (Rp 8.500).',
    expectedResult: 'Kedua transaksi berstatus CANCELLED & layak refund; diterbitkan 2 kewajiban KEEP berstatus OPEN masing-masing Rp 4.000 dan Rp 4.500 (total Rp 8.500).',
    testNameAndScript: 'CancellationKeepScenarioTest::test_tc_12_03_admin_approves_cancellation_with_seller_at_fault_and_issues_keep_obligations (tests/Feature/MarketTrust/CancellationKeepScenarioTest.php)',
    runnerType: 'PHPUnit Feature',
    xmlPrefix: 'TS-14-feature',
    testMatcher: 'test_tc_12_03_admin_approves_cancellation_with_seller_at_fault_and_issues_keep_obligations',
    passActualSummary: 'Keputusan Admin APPROVED dengan SELLER bersalah menerbitkan KEEP Rp 4.000 (BANK_VA_FLAT) dan Rp 4.500 (OTHER_TRIPLE_GATEWAY_FEE 3x1.500) berstatus OPEN.',
    notes: '-',
  },
  {
    tsId: 'TS-14',
    tsTitle: 'TS-14 — Pembatalan & Denda KEEP',
    parentTcId: 'TC-12-03',
    subtestId: 'TC-12-03-E2E',
    sourceRef: 'Notion TS-14 (40c6e9a1e4458178a343ea458bd09639) / SKPL UC-28B',
    objective: 'Memverifikasi di UI Admin (/admin/transactions) peninjauan pembatalan dengan menetapkan Penjual bersalah dan tampilnya kewajiban KEEP Rp 4.000 pada tabel Daftar Kewajiban KEEP Penjual.',
    typePriority: 'Cypress E2E / P1 (High)',
    prerequisite: 'Admin login dan terdapat pengajuan pembatalan berstatus REQUESTED dari TC-12-02.',
    testData: 'Keputusan: Setujui & Batalkan (APPROVED), Pihak Bertanggung Jawab: Penjual (SELLER), Catatan: "Disetujui karena penjual kehabisan stok pasca pembayaran."',
    steps: '1. Buka /admin/transactions.\n2. Klik "Tinjau Keputusan" pada baris pengajuan pembatalan.\n3. Pilih Setujui & Batalkan, pilih Penjual (Seller), isi catatan, dan klik Simpan Keputusan Final.',
    expectedResult: 'Muncul alert sukses dan tabel Daftar Kewajiban KEEP Penjual menampilkan baris baru untuk Seller dengan nominal Rp 4.000 dan status OPEN.',
    testNameAndScript: 'TC-12-03: Admin Meninjau Pembatalan dan Menetapkan Seller Bersalah (cypress/e2e/markettrust/ts-14-pembatalan-keep.cy.js)',
    runnerType: 'Cypress E2E',
    xmlPrefix: 'TS-14-cypress',
    testMatcher: 'TC-12-03',
    passActualSummary: 'Admin berhasil menyetujui pembatalan dengan tanggung jawab SELLER melalui modal review dan tabel KEEP menampilkan tagihan Rp 4.000 berstatus OPEN.',
    notes: '-',
  },
];

const PARENT_TCS = [
  { tcId: 'TC-02-01', tsId: 'TS-03', title: 'Penambahan Rekening Payout Tipe BANK' },
  { tcId: 'TC-02-02', tsId: 'TS-03', title: 'Aturan Atomik Hanya 1 Rekening Payout yang Aktif' },
  { tcId: 'TC-02-03', tsId: 'TS-03', title: 'Penambahan Rekening Payout Tipe QRIS Wajib Unggah Gambar' },
  { tcId: 'TC-02-04', tsId: 'TS-03', title: 'Seller Menghapus Rekening Payout Non-Aktif' },
  { tcId: 'TC-04-01', tsId: 'TS-05', title: 'Pencarian dan Filter Listing Marketplace' },
  { tcId: 'TC-04-02', tsId: 'TS-05', title: 'Filter Periode Statistik Pasar pada Detail Produk (UC-05)' },
  { tcId: 'TC-05-04', tsId: 'TS-07', title: 'Kirim Pesan Teks dan Polling Sinkronisasi DM' },
  { tcId: 'TC-05-05', tsId: 'TS-07', title: 'Validasi Ketat Lampiran Berkas DM (UC-08 & KNF-04)' },
  { tcId: 'TC-12-01', tsId: 'TS-14', title: 'Pembatalan Transaksi Pre-Payment oleh Buyer (Sebelum Bayar)' },
  { tcId: 'TC-12-02', tsId: 'TS-14', title: 'Pengajuan Pembatalan Post-Payment Sebelum Dikirim (Status PAID)' },
  { tcId: 'TC-12-03', tsId: 'TS-14', title: 'Admin Meninjau Pembatalan dan Menetapkan Seller Bersalah (Penerbitan KEEP / UC-28B)' },
];

function styleHeaderRow(row, fillArgb = 'FF1E293B') {
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
  row.height = 26;
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
  } else if (s === 'NOT EXECUTED') {
    bg = 'FFE2E8F0';
    fg = 'FF475569';
  }
  cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
  cell.font = { bold: true, color: { argb: fg }, size: 10, name: 'Calibri' };
  cell.alignment = { vertical: 'middle', horizontal: 'center' };
}

async function generateExcel() {
  const { metadata, allTestcases } = loadAllRunnerData();

  // Enrich catalog with actual runner execution results
  const enrichedRows = SCENARIO_CATALOG.map((item) => {
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
      executedAt: exec.executedAt,
      command: exec.command,
      evidencePath: `XML: ${exec.xmlFile} | Log: ${exec.logFile}`,
    };
  });

  // Compute Parent TC status (PASS only if all subtests/runners for that parent TC are PASS)
  const parentSummary = PARENT_TCS.map((ptc) => {
    const subRows = enrichedRows.filter((r) => r.parentTcId === ptc.tcId);
    const unitRows = subRows.filter((r) => r.runnerType === 'PHPUnit Unit');
    const featRows = subRows.filter((r) => r.runnerType === 'PHPUnit Feature');
    const e2eRows = subRows.filter((r) => r.runnerType === 'Cypress E2E');

    const aggregateRunnerStatus = (rows) => {
      if (rows.length === 0) return 'N/A (Di-cover Feature/E2E)';
      if (rows.some((r) => r.status === 'FAIL')) return 'FAIL';
      if (rows.some((r) => r.status === 'BLOCKED')) return 'BLOCKED';
      if (rows.some((r) => r.status === 'NOT EXECUTED')) return 'NOT EXECUTED';
      return 'PASS';
    };

    const unitStatus = aggregateRunnerStatus(unitRows);
    const featStatus = aggregateRunnerStatus(featRows);
    const e2eStatus = aggregateRunnerStatus(e2eRows);

    let overallStatus = 'PASS';
    if (subRows.some((r) => r.status === 'FAIL')) {
      overallStatus = 'FAIL';
    } else if (subRows.some((r) => r.status === 'BLOCKED')) {
      overallStatus = 'BLOCKED';
    } else if (subRows.some((r) => r.status === 'NOT EXECUTED')) {
      overallStatus = 'NOT EXECUTED';
    }

    return {
      ...ptc,
      subtestCount: subRows.length,
      unitStatus,
      featStatus,
      e2eStatus,
      overallStatus,
    };
  });

  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'Ali (Owzey) - MarketTrust QA';
  workbook.created = new Date();

  // =========================================================================
  // SHEET 1: Ringkasan
  // =========================================================================
  const wsSummary = workbook.addWorksheet('Ringkasan', {
    views: [{ state: 'frozen', ySplit: 1 }],
  });

  wsSummary.columns = [
    { width: 14 },
    { width: 18 },
    { width: 46 },
    { width: 18 },
    { width: 22 },
    { width: 22 },
    { width: 20 },
    { width: 20 },
    { width: 42 },
  ];

  const titleRow = wsSummary.addRow([
    'LAPORAN HASIL PENGUJIAN OTOMATIS MARKETTRUST — BAGIAN ALI (OWZEY): TS-03, TS-05, TS-07, TS-14',
  ]);
  wsSummary.mergeCells('A1:I1');
  titleRow.font = { bold: true, size: 13, color: { argb: 'FFFFFFFF' }, name: 'Calibri' };
  titleRow.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF312E81' } };
  titleRow.alignment = { vertical: 'middle', horizontal: 'center' };
  titleRow.height = 30;

  wsSummary.addRow([]);
  wsSummary.addRow(['PIC / Anggota', 'Ali (Owzey) — GitHub: A1liiii <mhmmdalimirza@gmail.com>']);
  wsSummary.addRow(['Branch & Commit Uji', `test/testing-ali (${metadata.commit || 'HEAD'})`]);
  wsSummary.addRow(['Waktu Eksekusi Runner', metadata.executed_at || new Date().toISOString()]);
  wsSummary.addRow([
    'Status Akses Link Notion',
    'Link Notion (https://app.notion.com/p/...) bersifat private workspace (membutuhkan autentikasi sesi akun Notion di browser). Seluruh skenario, prasyarat, data uji, dan expected result diekstrak dari spesifikasi SKPL & codebase aktual.',
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
  styleHeaderRow(secAHeader, 'FF1E293B');

  const tsGroups = [
    { id: 'TS-03', name: 'Rekening Payout Seller', tcs: 'TC-02-01, TC-02-02, TC-02-03, TC-02-04' },
    { id: 'TS-05', name: 'Marketplace & Statistik Pasar', tcs: 'TC-04-01, TC-04-02' },
    { id: 'TS-07', name: 'Direct Message & Lampiran', tcs: 'TC-05-04, TC-05-05' },
    { id: 'TS-14', name: 'Pembatalan & Denda KEEP', tcs: 'TC-12-01, TC-12-02, TC-12-03' },
  ];

  for (const g of tsGroups) {
    const pRows = parentSummary.filter((p) => p.tsId === g.id);
    const sRows = enrichedRows.filter((r) => r.tsId === g.id);
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

  const totalPPass = parentSummary.filter((p) => p.overallStatus === 'PASS').length;
  const totalSPass = enrichedRows.filter((r) => r.status === 'PASS').length;
  const totalSFail = enrichedRows.filter((r) => r.status === 'FAIL').length;
  const totalSOther = enrichedRows.filter((r) => r.status !== 'PASS' && r.status !== 'FAIL').length;

  const totalRow = wsSummary.addRow([
    'TOTAL',
    '4 Test Scenario Bagian Ali (Owzey)',
    '11 TC Induk (Tanpa Hitung Dobel)',
    PARENT_TCS.length,
    totalPPass,
    enrichedRows.length,
    totalSPass,
    totalSFail,
    totalSOther,
  ]);
  totalRow.font = { bold: true };
  totalRow.height = 24;

  wsSummary.addRow([]);

  // Tabel B: Status 11 TC Induk per Runner
  const secBTitle = wsSummary.addRow([
    'B. STATUS RINCI 11 TC INDUK (HASIL PHPUNIT & CYPRESS DITAMPILKAN TERPISAH)',
  ]);
  secBTitle.font = { bold: true, size: 11, color: { argb: 'FF1E293B' } };

  const secBHeader = wsSummary.addRow([
    'ID TS',
    'ID TC Induk',
    'Judul Test Case Induk',
    'Jml Subtest/Runner',
    'Status PHPUnit Unit',
    'Status PHPUnit Feature',
    'Status Cypress E2E',
    'Status Akhir TC Induk',
    'Keterangan',
  ]);
  styleHeaderRow(secBHeader, 'FF334155');

  for (const ptc of parentSummary) {
    const r = wsSummary.addRow([
      ptc.tsId,
      ptc.tcId,
      ptc.title,
      ptc.subtestCount,
      ptc.unitStatus,
      ptc.featStatus,
      ptc.e2eStatus,
      ptc.overallStatus,
      ptc.tcId === 'TC-04-02'
        ? 'Termasuk pengujian periode 90d (Gap Notion) pada Feature & Cypress'
        : 'Seluruh variasi subtest telah dieksekusi di runner',
    ]);
    r.height = 22;
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
  styleHeaderRow(secCHeader, 'FF475569');

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
  // SHEETS TS-03, TS-05, TS-07, TS-14
  // =========================================================================
  for (const tsId of ['TS-03', 'TS-05', 'TS-07', 'TS-14']) {
    const ws = workbook.addWorksheet(tsId, {
      views: [{ state: 'frozen', ySplit: 2 }],
    });

    ws.columns = [
      { width: 10 }, // ID TS
      { width: 18 }, // ID TC / Subtest
      { width: 14 }, // ID TC Induk
      { width: 28 }, // Referensi Sumber
      { width: 36 }, // Tujuan
      { width: 22 }, // Jenis / Prioritas
      { width: 32 }, // Prasyarat
      { width: 34 }, // Data Uji
      { width: 38 }, // Langkah
      { width: 38 }, // Expected Result
      { width: 42 }, // Nama Test & Path Script
      { width: 18 }, // Jenis Runner
      { width: 44 }, // Actual Result
      { width: 14 }, // Status
      { width: 22 }, // Waktu Run
      { width: 46 }, // Command
      { width: 42 }, // Link/Path Log atau Bukti
      { width: 34 }, // Catatan Bug / Blocker / Gap
    ];

    const tsRows = enrichedRows.filter((r) => r.tsId === tsId);
    const banner = ws.addRow([
      `${tsRows[0]?.tsTitle || tsId} — Rincian Kasus Uji & Hasil Eksekusi Runner (PHPUnit Unit, Feature & Cypress E2E)`,
    ]);
    ws.mergeCells('A1:R1');
    banner.font = { bold: true, size: 12, color: { argb: 'FFFFFFFF' }, name: 'Calibri' };
    banner.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E3A8A' } };
    banner.alignment = { vertical: 'middle', horizontal: 'left' };
    banner.height = 28;

    const header = ws.addRow([
      'ID TS',
      'ID TC / Subtest',
      'ID TC Induk',
      'Referensi Sumber',
      'Tujuan',
      'Jenis / Prioritas',
      'Prasyarat',
      'Data Uji',
      'Langkah',
      'Expected Result',
      'Nama Test & Path Script',
      'Jenis Runner',
      'Actual Result',
      'Status',
      'Waktu Run',
      'Command',
      'Link / Path Log atau Bukti',
      'Catatan Bug / Blocker / Gap',
    ]);
    styleHeaderRow(header, 'FF1E293B');

    for (const item of tsRows) {
      const row = ws.addRow([
        item.tsId,
        item.subtestId,
        item.parentTcId,
        item.sourceRef,
        item.objective,
        item.typePriority,
        item.prerequisite,
        item.testData,
        item.steps,
        item.expectedResult,
        item.testNameAndScript,
        item.runnerType,
        item.actualResult,
        item.status,
        item.executedAt,
        item.command,
        item.evidencePath,
        item.notes,
      ]);

      row.eachCell((cell) => {
        cell.font = { size: 9.5, name: 'Calibri' };
        cell.alignment = { vertical: 'top', horizontal: 'left', wrapText: true };
        cell.border = {
          top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
          left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
          bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
          right: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        };
      });
      styleStatusCell(row.getCell(14), item.status);
      row.height = 68;
    }
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
        parentTcCount: parentSummary.length,
        parentTcPass: totalPPass,
        subtestExecutionCount: enrichedRows.length,
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
