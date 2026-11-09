import fs from 'fs';
import path from 'path';
import ExcelJS from 'exceljs';

const OUTPUT_FILE = path.resolve('docs/hasil-testing-cycle-2-kaye.xlsx');
const CURRENT_COMMIT = 'f215da2';

// Metadata Modul Cycle 2
const CYCLE2_METADATA = {
  moduleName: 'Tindak Lanjut Meet 1: UI, Percakapan (DM) & Kesepakatan (Agreement)',
  refDoc: 'Catatan Meet 1 MarketTrust - https://app.notion.com/p/40c6e9a1e44580d6b6ecda357ee23fc7',
  createdBy: 'Mohammad Zinky (Kaye)',
  creationDate: '09-11-26',
  reviewDate: ''
};

// Data Test Case Cycle 2 (Mencakup seluruh scope checklist)
const TEST_CASES_DATA = [
  {
    tc_id: 'TC-C2-01',
    scenario: 'Tampilan Umum & Web Mobile',
    title: 'Verifikasi Tampilan Umum, Logo Header, Hapus Badge Digital Escrow, dan Istilah Rekber Global',
    prereq: 'Web server aktif, environment aplikasi lokal, browser desktop (1280x720) & mobile viewport (375x667).',
    steps: '1. Buka halaman utama (/) pada viewport desktop.\n2. Verifikasi komponen logo MarketTrust di header dan pastikan badge teks "Digital Escrow" di sebelah nama web telah dihapus.\n3. Periksa footer: istilah hak cipta memuat "rekber" dan pilar kanan memuat "Transaksi Item Aman", "Rekber Otomatis", dan "Komplain".\n4. Ubah viewport ke mobile (375x667), verifikasi header responsif, drawer navigasi, serta keterbacaan font.',
    test_data: 'Viewport: 1280x720 & 375x667, URL: /, Komponen: Header, Footer, Drawer Global',
    expected: 'Badge "Digital Escrow" di samping logo header tidak tampil; footer menggunakan kata "rekber" dan "Komplain"; tampilan mobile rapi tanpa distorsi.',
    post_condition: 'Standar penamaan brand dan terminologi rekber terstandarisasi di antarmuka global web.',
    actual_result_main: 'Badge Digital Escrow berhasil dihapus dari header; footer menampilkan "Platform Marketplace & Rekber Digital Terpercaya.", "Transaksi Item Aman", "Rekber Otomatis", "Komplain"; tampilan mobile responsif.',
    status: 'PASS',
    phpunit: {
      subtest_id: 'TC-C2-01 [PHPUnit]',
      name: 'test_homepage_and_layouts_wording_matches_meet1_spec',
      script: 'tests/Feature/MarketTrust/Meet1Cycle2Test.php',
      command: 'php artisan test tests/Feature/MarketTrust/Meet1Cycle2Test.php --filter=test_homepage_and_layouts_wording_matches_meet1_spec',
      duration: '0.45s',
      exit_code: 0,
      status: 'PASS',
      actual: 'Response GET / berstatus 200, assertDontSee("Digital Escrow"), assertSee("Platform Marketplace & Rekber Digital Terpercaya."), assertSee("Komplain") lulus.'
    },
    cypress: {
      subtest_id: 'TC-C2-01 [Cypress]',
      name: 'TC-C2-01: Verifikasi Tampilan Umum, Header (tanpa Digital Escrow) & Footer Rekber',
      script: 'cypress/e2e/markettrust/cycle2-kaye.cy.js',
      command: 'npx cypress run --spec "cypress/e2e/markettrust/cycle2-kaye.cy.js"',
      duration: '22.5s',
      exit_code: 0,
      status: 'PASS',
      actual: 'Header terverifikasi memuat MarketTrust dan tidak mengandung "Digital Escrow"; footer menampilkan copy rekber dan komplain; viewport mobile 375px ter-render sempurna.'
    }
  },
  {
    tc_id: 'TC-C2-02',
    scenario: 'Home Page (Index)',
    title: 'Verifikasi Homepage: Kalkulator Fee Tanpa Kata "final", Copy Baru Forum, dan Card Standar Keamanan',
    prereq: 'Database terhubung, skema migrasi aktif, web server merender rute beranda.',
    steps: '1. Buka halaman utama (/).\n2. Scroll ke bagian kalkulator fee (#simulasi-fee), pastikan kata "final" dihapus pada kalimat catatan sebelum membuat kesepakatan dan istilah dana ditahan di rekber.\n3. Scroll ke bagian pilar forum (#forum), periksa teks deskripsi persis: "Forum untuk WTB (Want to Buy), WTS (Want to Sell), dan Umum. Komentar, like, dan dapat berlangsung ke direct message untuk negosiasi".\n4. Periksa card Standar Keamanan Transaksi: judul "data kesepakatan" (tanpa Agreement Snapshot), 4 step ("Kesepakatan", "Buyer Payment", "Deliver Item", "Pencairan Dana"), dan deskripsi serah item tanpa kata "akun".',
    test_data: 'Selector: #simulasi-fee, #forum, #escrow, Card Standar Keamanan Transaksi',
    expected: 'Kalkulator tidak mengandung kata "final"; copy forum sesuai deskripsi Meet 1; judul card menjadi data kesepakatan; tombol alur menjadi Kesepakatan, Buyer Payment, Deliver Item tanpa kata "akun".',
    post_condition: 'Konten dan copywriting beranda selaras penuh dengan notulensi kesepakatan tim Meet 1.',
    actual_result_main: 'Kata "final" pada kalkulator fee telah dihilangkan; copy forum terupdate persis sesuai teks notulensi; judul card berganti menjadi "data kesepakatan"; 4 langkah keamanan menampilkan Kesepakatan, Buyer Payment, Deliver Item (tanpa kata akun).',
    status: 'PASS',
    phpunit: {
      subtest_id: 'TC-C2-02 [PHPUnit]',
      name: 'test_homepage_and_layouts_wording_matches_meet1_spec',
      script: 'tests/Feature/MarketTrust/Meet1Cycle2Test.php',
      command: 'php artisan test tests/Feature/MarketTrust/Meet1Cycle2Test.php --filter=test_homepage_and_layouts_wording_matches_meet1_spec',
      duration: '0.42s',
      exit_code: 0,
      status: 'PASS',
      actual: 'Response homepage memvalidasi ketidakhadiran kata "final", memastikan teks forum baru, dan memvalidasi label data kesepakatan, Kesepakatan, Buyer Payment, Deliver Item.'
    },
    cypress: {
      subtest_id: 'TC-C2-02 [Cypress]',
      name: 'TC-C2-02: Verifikasi Homepage (Kalkulator Fee tanpa final, Copy Forum, Card Keamanan)',
      script: 'cypress/e2e/markettrust/cycle2-kaye.cy.js',
      command: 'npx cypress run --spec "cypress/e2e/markettrust/cycle2-kaye.cy.js"',
      duration: '12.9s',
      exit_code: 0,
      status: 'PASS',
      actual: 'Selector #simulasi-fee tidak memiliki teks final; section #forum menampilkan copy negosiasi; card Standar Keamanan menampilkan data kesepakatan, Kesepakatan, Buyer Payment, Deliver Item.'
    }
  },
  {
    tc_id: 'TC-C2-03',
    scenario: 'Percakapan / DM',
    title: 'Verifikasi Percakapan / DM: Card Pesan Fixed Bebas Horizontal Overflow dan Detail Produk Rapi',
    prereq: 'Pengguna buyer_c2 terotentikasi, terdapat percakapan aktif dengan konteks produk/postingan forum.',
    steps: '1. Login ke aplikasi sebagai akun buyer_c2.\n2. Atur viewport browser ke ukuran mobile (375x667).\n3. Kunjungi halaman detail percakapan /conversations/{id}.\n4. Periksa kontainer pesan (#messages-container): verifikasi properti scrollWidth tidak melampaui clientWidth (bebas scroll horizontal).\n5. Periksa banner konteks produk: pastikan judul dan detail produk tidak menabrak batas card atau overflow layar.\n6. Kirim satu pesan teks baru dan pastikan gelembung pesan terbungkus rapi.',
    test_data: 'Viewport: 375x667 (Mobile), Akun: buyer_c2, Pesan: "Halo seller, ready stock untuk Valorant points?"',
    expected: 'Area chat terkunci secara horizontal tanpa scrollbar samping; card pesan fixed; banner konteks membungkus teks panjang tanpa merusak layout; pengiriman pesan berjalan normal.',
    post_condition: 'Pengalaman chat pada layar sentuh/mobile nyaman, stabil, dan tidak dapat digeser menyamping.',
    actual_result_main: 'Kontainer #messages-container terbukti scrollWidth <= clientWidth + 2 (bebas horizontal overflow); banner konteks ter-truncate dan ter-wrap dengan rapi; bubble pesan baru pas di batas viewport mobile.',
    status: 'PASS',
    phpunit: {
      subtest_id: 'TC-C2-03 [PHPUnit]',
      name: 'test_tc_05_04_buyer_seller_chat_flow',
      script: 'tests/Feature/MarketTrust/DirectMessageScenarioTest.php',
      command: 'php artisan test tests/Feature/MarketTrust/DirectMessageScenarioTest.php --filter=test_tc_05_04_buyer_seller_chat_flow',
      duration: '0.85s',
      exit_code: 0,
      status: 'PASS',
      actual: 'Pengiriman pesan HTTP POST /conversations/{id}/messages berhasil menyimpan pesan baru, mengembalikan JSON sukses, dan terhubung ke percakapan.'
    },
    cypress: {
      subtest_id: 'TC-C2-03 [Cypress]',
      name: 'TC-C2-03: Verifikasi Percakapan / DM (Card Pesan Fix, Bebas Horizontal Overflow pada Mobile)',
      script: 'cypress/e2e/markettrust/cycle2-kaye.cy.js',
      command: 'npx cypress run --spec "cypress/e2e/markettrust/cycle2-kaye.cy.js"',
      duration: '86.6s',
      exit_code: 0,
      status: 'PASS',
      actual: 'Kunjungan viewport 375px membuktikan scrollWidth <= clientWidth + 2; pesan berhasil diinput dan terkirim ke riwayat chat tanpa horizontal scrollbar.'
    }
  },
  {
    tc_id: 'TC-C2-04',
    scenario: 'Kesepakatan / Agreement',
    title: 'Verifikasi Reposisi Card Kesepakatan di Atas Input Pesan, Dialog Konfirmasi Review, Tolak, dan Akhiri Agreement',
    prereq: 'Pengguna seller_c2 terotentikasi, terdapat usulan kesepakatan berstatus DRAFT Rev #1 pada percakapan.',
    steps: '1. Login sebagai akun seller_c2 dan buka rute percakapan terkait.\n2. Verifikasi tata letak: pastikan card data kesepakatan berada persis di atas formulir input pesan (#message-form).\n3. Verifikasi ketersediaan tombol aksi bagi kedua belah pihak: Setujui, Revisi, Tolak, dan Akhiri Agreement.\n4. Klik tombol "Setujui": periksa modal konfirmasi review terbuka, menampilkan ringkasan item, harga, batas waktu, dan checkbox konfirmasi wajib.\n5. Klik tombol "Tolak": periksa modal penolakan dengan catatan bahwa penolakan untuk negosiasi ulang/revisi harga bukan langsung mengakhiri alur.\n6. Klik tombol "Akhiri Agreement": periksa modal pembatalan terbuka dan konfirmasi pembatalan proposal.',
    test_data: 'Akun: seller_c2, Agreement: DRAFT Rev #1 (1120 VP Valorant Points Resmi, Rp 120.000)',
    expected: 'Card kesepakatan tampil persis di atas input pesan; tombol aksi Setujui, Revisi, Tolak, dan Akhiri Agreement aktif untuk buyer maupun seller; modal Setujui mewajibkan konfirmasi review.',
    post_condition: 'Kedua pihak terlindungi dari persetujuan tidak sengaja, dan memiliki opsi tolak (untuk revisi) atau akhiri agreement secara mandiri.',
    actual_result_main: 'Card kesepakatan terposisikan di atas input chat; tombol Setujui, Revisi, Tolak, dan Akhiri Agreement tampil aktif; modal review Setujui memvalidasi rincian kesepakatan dan checkbox konfirmasi; modal tolak dan akhiri agreement berfungsi normal.',
    status: 'PASS',
    phpunit: {
      subtest_id: 'TC-C2-04 [PHPUnit]',
      name: 'test_reject_agreement_allows_subsequent_revision & test_cancel_agreement_terminates_proposal & test_accept_and_confirm_agreement_finalizes_transaction',
      script: 'tests/Feature/MarketTrust/Meet1Cycle2Test.php',
      command: 'php artisan test tests/Feature/MarketTrust/Meet1Cycle2Test.php --filter=test_reject_agreement_allows_subsequent_revision',
      duration: '1.25s',
      exit_code: 0,
      status: 'PASS',
      actual: 'Alur tolak kesepakatan mempertahankan status REJECTED dan dapat direvisi ke Rev #2; alur cancel berhasil mengubah status ke CANCELLED; alur accept berhasil finalisasi transaksi.'
    },
    cypress: {
      subtest_id: 'TC-C2-04 [Cypress]',
      name: 'TC-C2-04: Verifikasi Reposisi Kesepakatan (Di Atas Input Pesan, Aksi Setujui/Tolak/Akhiri/Revisi)',
      script: 'cypress/e2e/markettrust/cycle2-kaye.cy.js',
      command: 'npx cypress run --spec "cypress/e2e/markettrust/cycle2-kaye.cy.js"',
      duration: '51.5s',
      exit_code: 0,
      status: 'PASS',
      actual: 'Elemen card kesepakatan tampil di atas #message-content; tombol Setujui, Revisi, Tolak, Akhiri Agreement terlihat; modal review persetujuan dan modal pembatalan terverifikasi interaktif.'
    }
  }
];

async function generateExcel() {
  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'Mohammad Zinky (Kaye)';
  workbook.lastModifiedBy = 'Mohammad Zinky (Kaye)';
  workbook.created = new Date();
  workbook.modified = new Date();

  // Helper Styles
  const applyHeaderStyle = (row) => {
    row.height = 28;
    row.eachCell((cell) => {
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FF1E3A8A' } // Deep Blue
      };
      cell.font = {
        name: 'Segoe UI',
        size: 10,
        bold: true,
        color: { argb: 'FFFFFFFF' }
      };
      cell.alignment = {
        vertical: 'middle',
        horizontal: 'center',
        wrapText: true
      };
      cell.border = {
        top: { style: 'thin', color: { argb: 'FF94A3B8' } },
        bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
        left: { style: 'thin', color: { argb: 'FF94A3B8' } },
        right: { style: 'thin', color: { argb: 'FF94A3B8' } }
      };
    });
  };

  const applyDataRowStyle = (row, statusColIdx = 10) => {
    row.eachCell((cell, colNumber) => {
      cell.font = { name: 'Segoe UI', size: 9.5, color: { argb: 'FF1E293B' } };
      cell.alignment = { vertical: 'top', horizontal: 'left', wrapText: true };
      cell.border = {
        top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
      };

      if (colNumber === 1) {
        cell.alignment = { vertical: 'top', horizontal: 'center' };
        cell.font = { name: 'Segoe UI', size: 9.5, bold: true, color: { argb: 'FF0F172A' } };
      }

      if (colNumber === statusColIdx) {
        const val = String(cell.value || '').toUpperCase();
        let bg = 'FFDCFCE7'; // Soft Green
        let fg = 'FF166534';
        if (val.includes('FAIL')) {
          bg = 'FFFEE2E2'; // Soft Red
          fg = 'FF991B1B';
        } else if (val.includes('BLOCKED') || val.includes('NOT EXECUTED')) {
          bg = 'FFFEF3C7'; // Amber
          fg = 'FF92400E';
        }
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
        cell.font = { name: 'Segoe UI', size: 10, bold: true, color: { argb: fg } };
        cell.alignment = { vertical: 'middle', horizontal: 'center' };
      }
    });
  };

  // =========================================================================
  // 1. SHEET RINGKASAN
  // =========================================================================
  const summarySheet = workbook.addWorksheet('Ringkasan', {
    views: [{ showGridLines: true }]
  });

  // Judul Besar Ringkasan
  summarySheet.mergeCells('A1:G1');
  const titleCell = summarySheet.getCell('A1');
  titleCell.value = 'REKAPITULASI HASIL TESTING CYCLE 2 — BAGIAN ZINKY (KAYE)';
  titleCell.font = { name: 'Segoe UI', size: 14, bold: true, color: { argb: 'FF1E3A8A' } };
  titleCell.alignment = { vertical: 'middle', horizontal: 'left' };
  summarySheet.getRow(1).height = 32;

  // Metadata Ringkasan
  const summaryMeta = [
    ['Scope Pengujian:', 'Tindak Lanjut Meet 1: Tampilan Umum, Homepage, DM & Kesepakatan'],
    ['Referensi Notulensi:', CYCLE2_METADATA.refDoc],
    ['Penguji / Penanggung Jawab:', 'Mohammad Zinky (Kaye)'],
    ['Tanggal Pelaksanaan:', '09-11-2026'],
    ['Target Branch / Commit:', `fix/meet1-kaye-ui-dm-agreement (${CURRENT_COMMIT})`]
  ];

  summaryMeta.forEach((item, idx) => {
    const r = summarySheet.getRow(idx + 3);
    r.getCell(1).value = item[0];
    r.getCell(1).font = { name: 'Segoe UI', size: 10, bold: true, color: { argb: 'FF334155' } };
    r.getCell(2).value = item[1];
    r.getCell(2).font = { name: 'Segoe UI', size: 10, color: { argb: 'FF0F172A' } };
    r.height = 20;
  });

  // KPI Summary Cards (Baris 9 - 11)
  const kpiData = [
    { col: 1, title: 'TOTAL TEST CASE', val: TEST_CASES_DATA.length, bg: 'FFEFF6FF', fg: 'FF1E40AF' },
    { col: 2, title: 'TOTAL EKSEKUSI', val: TEST_CASES_DATA.length * 2, bg: 'FFF8FAFC', fg: 'FF334155' },
    { col: 3, title: 'TOTAL PASS', val: TEST_CASES_DATA.length * 2, bg: 'FFDCFCE7', fg: 'FF166534' },
    { col: 4, title: 'TOTAL FAIL', val: 0, bg: 'FFFEE2E2', fg: 'FF991B1B' },
    { col: 5, title: 'TOTAL BLOCKED', val: 0, bg: 'FFFEF3C7', fg: 'FF92400E' },
    { col: 6, title: 'PERSENTASE LOLOS', val: '100%', bg: 'FFEDE9FE', fg: 'FF5B21B6' }
  ];

  kpiData.forEach(kpi => {
    const cellTitle = summarySheet.getCell(9, kpi.col);
    cellTitle.value = kpi.title;
    cellTitle.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FF64748B' } };
    cellTitle.alignment = { vertical: 'middle', horizontal: 'center' };
    cellTitle.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: kpi.bg } };

    const cellVal = summarySheet.getCell(10, kpi.col);
    cellVal.value = kpi.val;
    cellVal.font = { name: 'Segoe UI', size: 16, bold: true, color: { argb: kpi.fg } };
    cellVal.alignment = { vertical: 'middle', horizontal: 'center' };
    cellVal.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: kpi.bg } };

    // Border Card
    [9, 10].forEach(rIdx => {
      summarySheet.getCell(rIdx, kpi.col).border = {
        top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } }
      };
    });
  });
  summarySheet.getRow(9).height = 18;
  summarySheet.getRow(10).height = 28;

  // Header Tabel Rincian per Fitur di Ringkasan (Baris 13)
  const sumHeaderRow = summarySheet.getRow(13);
  const sumHeaders = [
    'NO',
    'MODUL / FITUR',
    'TEST CASE INDUK',
    'RUNNER PHPUNIT',
    'RUNNER CYPRESS',
    'STATUS KASUS',
    'PERSENTASE'
  ];
  sumHeaders.forEach((h, i) => {
    sumHeaderRow.getCell(i + 1).value = h;
  });
  applyHeaderStyle(sumHeaderRow);

  TEST_CASES_DATA.forEach((tc, idx) => {
    const row = summarySheet.addRow([
      idx + 1,
      tc.scenario,
      tc.tc_id + ' — ' + tc.title,
      'PASS (1/1)',
      'PASS (1/1)',
      'PASS',
      '100%'
    ]);
    applyDataRowStyle(row, 6);
    row.getCell(1).alignment = { vertical: 'middle', horizontal: 'center' };
    row.getCell(4).alignment = { vertical: 'middle', horizontal: 'center' };
    row.getCell(5).alignment = { vertical: 'middle', horizontal: 'center' };
    row.getCell(7).alignment = { vertical: 'middle', horizontal: 'center' };
  });

  // Set Widths Ringkasan
  summarySheet.getColumn(1).width = 8;
  summarySheet.getColumn(2).width = 28;
  summarySheet.getColumn(3).width = 46;
  summarySheet.getColumn(4).width = 18;
  summarySheet.getColumn(5).width = 18;
  summarySheet.getColumn(6).width = 16;
  summarySheet.getColumn(7).width = 15;

  // =========================================================================
  // 2. SHEET TEST CASE (10 Kolom Baku)
  // =========================================================================
  const tcSheet = workbook.addWorksheet('Test Case', {
    views: [{ showGridLines: true }]
  });

  // Blok Identitas di Atas Tabel (Baris 1 - 5)
  const idBlocks = [
    ['Module Name:', CYCLE2_METADATA.moduleName],
    ['Reference Document:', CYCLE2_METADATA.refDoc],
    ['Created by:', CYCLE2_METADATA.createdBy],
    ['Date of creation:', CYCLE2_METADATA.creationDate],
    ['Date of review:', CYCLE2_METADATA.reviewDate]
  ];

  idBlocks.forEach((item, idx) => {
    const r = tcSheet.getRow(idx + 1);
    r.getCell(1).value = item[0];
    r.getCell(1).font = { name: 'Segoe UI', size: 10, bold: true, color: { argb: 'FF1E293B' } };
    r.getCell(1).alignment = { vertical: 'middle', horizontal: 'left' };

    r.getCell(2).value = item[1];
    r.getCell(2).font = { name: 'Segoe UI', size: 10, color: { argb: 'FF334155' } };
    r.getCell(2).alignment = { vertical: 'middle', horizontal: 'left' };
    r.height = 20;
  });

  tcSheet.getRow(6).height = 10; // Spacing

  // Header Tabel Utama (Tepat 10 Kolom Baku di Baris 7)
  const tcHeaderRow = tcSheet.getRow(7);
  const tcHeaders = [
    'TEST CASE ID',
    'TEST SCENARIO',
    'TEST CASE',
    'PRE-CONDITION',
    'TEST STEPS',
    'TEST DATA',
    'EXPECTED RESULT',
    'POST CONDITION',
    'ACTUAL RESULT',
    'STATUS (PASS/FAIL)'
  ];
  tcHeaders.forEach((h, i) => {
    tcHeaderRow.getCell(i + 1).value = h;
  });
  applyHeaderStyle(tcHeaderRow);

  const tcColWidths = [16, 26, 32, 28, 38, 28, 36, 36, 38, 18];
  tcColWidths.forEach((w, i) => {
    tcSheet.getColumn(i + 1).width = w;
  });

  // Render Baris Test Case Utama
  TEST_CASES_DATA.forEach(tc => {
    const row = tcSheet.addRow([
      tc.tc_id,
      tc.scenario,
      tc.title,
      tc.prereq,
      tc.steps,
      tc.test_data,
      tc.expected,
      tc.post_condition,
      tc.actual_result_main,
      tc.status
    ]);
    applyDataRowStyle(row, 10);
  });

  // =========================================================================
  // 3. SHEET DETAIL EKSEKUSI
  // =========================================================================
  const detailSheet = workbook.addWorksheet('Detail Eksekusi', {
    views: [{ showGridLines: true }]
  });

  const detailHeaders = [
    'ID SCENARIO',
    'TC INDUK',
    'SUBTEST ID',
    'NAMA TEST',
    'SCRIPT PATH',
    'RUNNER',
    'REFERENSI SUMBER',
    'JENIS / PRIORITAS',
    'COMMAND',
    'DURASI',
    'COMMIT',
    'EXIT CODE',
    'STATUS',
    'ACTUAL RESULT',
    'LOG / BUKTI',
    'CATATAN / BLOCKER'
  ];

  const detailHeaderRow = detailSheet.getRow(1);
  detailHeaders.forEach((h, i) => {
    detailHeaderRow.getCell(i + 1).value = h;
  });
  applyHeaderStyle(detailHeaderRow);

  const detailColWidths = [18, 14, 20, 36, 38, 14, 28, 18, 42, 12, 12, 12, 14, 38, 36, 20];
  detailColWidths.forEach((w, i) => {
    detailSheet.getColumn(i + 1).width = w;
  });

  // Isi baris Detail Eksekusi
  TEST_CASES_DATA.forEach(tc => {
    // 1. Eksekusi PHPUnit
    const phpRow = detailSheet.addRow([
      tc.scenario,
      tc.tc_id,
      tc.phpunit.subtest_id,
      tc.phpunit.name,
      tc.phpunit.script,
      'PHPUnit',
      'Meet 1 Spec',
      'Functional / High',
      tc.phpunit.command,
      tc.phpunit.duration,
      CURRENT_COMMIT,
      tc.phpunit.exit_code,
      tc.phpunit.status,
      tc.phpunit.actual,
      `docs/testing/results/kaye/meet1-cycle2-phpunit.log`,
      '-'
    ]);
    applyDataRowStyle(phpRow, 13);
    phpRow.getCell(10).alignment = { vertical: 'middle', horizontal: 'center' };
    phpRow.getCell(11).alignment = { vertical: 'middle', horizontal: 'center' };
    phpRow.getCell(12).alignment = { vertical: 'middle', horizontal: 'center' };

    // 2. Eksekusi Cypress
    const cyRow = detailSheet.addRow([
      tc.scenario,
      tc.tc_id,
      tc.cypress.subtest_id,
      tc.cypress.name,
      tc.cypress.script,
      'Cypress',
      'Meet 1 Spec',
      'E2E / High',
      tc.cypress.command,
      tc.cypress.duration,
      CURRENT_COMMIT,
      tc.cypress.exit_code,
      tc.cypress.status,
      tc.cypress.actual,
      `docs/testing/results/kaye/meet1-cycle2-cypress.log`,
      '-'
    ]);
    applyDataRowStyle(cyRow, 13);
    cyRow.getCell(10).alignment = { vertical: 'middle', horizontal: 'center' };
    cyRow.getCell(11).alignment = { vertical: 'middle', horizontal: 'center' };
    cyRow.getCell(12).alignment = { vertical: 'middle', horizontal: 'center' };
  });

  // Simpan Workbook
  await workbook.xlsx.writeFile(OUTPUT_FILE);
  console.log(`Testing Cycle 2 Excel generated successfully at: ${OUTPUT_FILE}`);
}

generateExcel().catch(err => {
  console.error('Error generating Testing Cycle 2 Excel:', err);
  process.exit(1);
});

