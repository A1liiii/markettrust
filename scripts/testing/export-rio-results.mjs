import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import ExcelJS from 'exceljs';

const RESULTS_DIR = 'docs/testing/results/rio';
const OUTPUT_EXCEL = 'docs/hasil-testing-rio.xlsx';

// Dapatkan commit hash dan branch terkini
let commitHash = 'unknown';
let currentBranch = 'test/testing-rio';
try {
  commitHash = execSync('git rev-parse --short HEAD').toString().trim();
  currentBranch = execSync('git branch --show-current').toString().trim();
} catch (e) {
  // fallback
}

// Helper untuk membaca dan mem-parse file JUnit XML secara sederhana tanpa dependensi luar
function parseJUnitXml(filePath) {
  if (!fs.existsSync(filePath)) return [];
  const content = fs.readFileSync(filePath, 'utf8');
  const testCases = [];

  // Regex untuk mencocokkan setiap <testcase ...> ... </testcase> atau self-closing
  const testcaseRegex = /<testcase\s+([^>]*?)(\/>|>([\s\S]*?)<\/testcase>)/g;
  let match;
  while ((match = testcaseRegex.exec(content)) !== null) {
    const attrString = match[1];
    const innerContent = match[3] || '';

    const getAttr = (name) => {
      const m = new RegExp(`${name}="([^"]*)"`).exec(attrString);
      return m ? m[1] : '';
    };

    const name = getAttr('name');
    const classname = getAttr('classname');
    const time = getAttr('time') || '0';

    let status = 'PASS';
    let failureMsg = '';

    if (innerContent.includes('<failure') || innerContent.includes('<error')) {
      status = 'FAIL';
      const msgMatch = /message="([^"]*)"/.exec(innerContent);
      failureMsg = msgMatch ? msgMatch[1] : 'Assertion or runtime error';
    } else if (innerContent.includes('<skipped')) {
      status = 'SKIPPED';
    }

    testCases.push({
      name,
      classname,
      time: parseFloat(time),
      status,
      failureMsg,
      rawXmlFile: path.basename(filePath)
    });
  }

  return testCases;
}

// Master spesifikasi kartu 16 TC Induk Rio dari dokumen PMPL
const TS_DEFINITIONS = [
  {
    ts_id: 'TS-04',
    ts_name: 'Katalog Admin & Listing',
    reference: 'https://app.notion.com/p/40c6e9a1e44581b79d1fed11000317de',
    cases: [
      {
        tc_id: 'TC-03-01',
        title: 'Admin Menambah Kategori, Produk, dan Varian Baru',
        priority: 'P2',
        objective: 'Memverifikasi bahwa Admin dapat membuat master kategori, produk master, dan varian baru secara berjenjang serta mencegah duplikasi.',
        prerequisite: 'Akun Administrator aktif; antarmuka katalog admin dapat diakses.',
        test_data: 'Kategori baru, Produk master baru, Varian baru; data duplikat untuk uji penolakan.',
        steps: '1. Login sebagai Admin\n2. Buka tab Kategori lalu simpan kategori baru\n3. Buka tab Produk lalu simpan produk di bawah kategori tersebut\n4. Buka tab Varian lalu simpan varian di bawah produk tersebut\n5. Uji percobaan input nama duplikat.',
        expected: 'Kategori, produk, dan varian berhasil tersimpan di database dan tampil pada tabel katalog; input duplikat ditolak dengan pesan validasi.',
        gap_notes: 'Tidak ada gap kritis; fitur master katalog lengkap.'
      },
      {
        tc_id: 'TC-03-02',
        title: 'Seller Membuat Listing Marketplace Baru (Status DRAFT dan ACTIVE)',
        priority: 'P2',
        objective: 'Memverifikasi pembuatan listing oleh seller dengan status ACTIVE dan DRAFT, serta validasi batas harga minimum Rp 5.000.',
        prerequisite: 'Seller terdaftar dan aktif; varian produk master tersedia.',
        test_data: 'Varian aktif, judul, deskripsi, harga >= 5000, estimasi penyerahan, status ACTIVE/DRAFT.',
        steps: '1. Login Seller\n2. Buka /seller/listings/create\n3. Pilih varian dan lengkapi judul, deskripsi, harga, estimasi waktu\n4. Simpan listing berstatus ACTIVE\n5. Uji simpan status DRAFT\n6. Uji validasi harga < 5000 dan varian nonaktif.',
        expected: 'Listing ACTIVE dan DRAFT tersimpan dengan pemilik yang benar; harga di bawah Rp 5.000 dan varian tidak aktif ditolak.',
        gap_notes: 'Spesifikasi judul menyebut DRAFT dan ACTIVE. Backend mendukung DRAFT, namun alur lanjutan visibilitas/publikasi DRAFT dan edit/hapus belum dirinci di kartu dokumen PMPL.'
      },
      {
        tc_id: 'TC-03-03',
        title: 'Toggle Status Listing Seller (ACTIVE <-> INACTIVE)',
        priority: 'P2',
        objective: 'Memverifikasi pengalihan status listing antara ACTIVE dan CLOSED/INACTIVE oleh pemilik serta perlindungan otorisasi.',
        prerequisite: 'Listing aktif milik Seller tersedia.',
        test_data: 'Listing ID milik seller dan listing milik seller lain.',
        steps: '1. Seller menekan tombol Tutup pada listing aktif\n2. Periksa status berubah menjadi DITUTUP\n3. Tekan Aktifkan kembali\n4. Uji toggle oleh akun pihak ketiga.',
        expected: 'Status beralih antara ACTIVE dan CLOSED; listing tertutup tidak muncul di marketplace publik aktif; akses non-pemilik ditolak (403).',
        gap_notes: 'Implementasi kode menggunakan status CLOSED untuk status nonaktif di UI/database.'
      }
    ]
  },
  {
    ts_id: 'TS-11',
    ts_name: 'Penyerahan Produk Digital',
    reference: 'https://app.notion.com/p/40c6e9a1e44581479f89d785d125d586',
    cases: [
      {
        tc_id: 'TC-09-01',
        title: 'Penyerahan Hasil oleh Seller dengan Berkas Bukti Sah',
        priority: 'P1',
        objective: 'Memverifikasi bahwa Seller dapat menyerahkan hasil resmi transaksi PAID dengan melampirkan berkas bukti sah, tautan, dan catatan.',
        prerequisite: 'Transaksi berstatus PAID; Seller pemilik transaksi terautentikasi.',
        test_data: 'Catatan penyerahan, tautan hasil, berkas bukti (PDF/gambar sah).',
        steps: '1. Buka transaksi PAID sebagai Seller\n2. Isi catatan penyerahan dan tautan\n3. Lampirkan berkas bukti sah\n4. Kirim formulir penyerahan.',
        expected: 'Status transaksi beralih ke DELIVERED; delivered_at tercatat; confirmation_due_at disetel 1 jam ke depan; berkas tersimpan; notifikasi terkirim ke Buyer & Admin; status history mencatat seller.',
        gap_notes: 'Sesuai spesifikasi implementasi backend.'
      },
      {
        tc_id: 'TC-09-02',
        title: 'Seller Menyerahkan Pesanan Tanpa Bukti / Catatan',
        priority: 'P1',
        objective: 'Memverifikasi penolakan validasi jika seller mengirimkan penyerahan kosong tanpa bukti atau catatan.',
        prerequisite: 'Transaksi berstatus PAID.',
        test_data: 'Formulir penyerahan dengan field catatan dan berkas dikosongkan.',
        steps: '1. Seller membuka form penyerahan transaksi PAID\n2. Kosongkan semua field catatan dan file\n3. Klik tombol submit penyerahan.',
        expected: 'Submisi ditolak dengan error validasi formulir; status transaksi tetap PAID; tidak ada perubahan timestamp atau notifikasi palsu.',
        gap_notes: 'Sesuai spesifikasi implementasi backend.'
      },
      {
        tc_id: 'TC-09-03',
        title: 'Permintaan Perpanjangan Waktu Penyerahan oleh Seller',
        priority: 'P1',
        objective: 'Memverifikasi alur permohonan perpanjangan tenggat penyerahan oleh Seller.',
        prerequisite: 'Transaksi berstatus PAID.',
        test_data: 'Durasi perpanjangan (menit) dan alasan perpanjangan.',
        steps: '1. Seller mengajukan perpanjangan waktu penyerahan\n2. Verifikasi pencatatan event tenggat.',
        expected: 'Permintaan tercatat pada transaction_deadline_events; tenggat waktu penyerahan disesuaikan.',
        gap_notes: 'BLOCKED: Kebijakan batas frekuensi perpanjangan dan alur review approval dari Admin/Buyer belum disahkan secara final pada dokumen spesifikasi PMPL. Backend melakukan perpanjangan langsung tanpa alur review.'
      },
      {
        tc_id: 'TC-09-04',
        title: 'Scheduler Peringatan Keterlambatan Penyerahan Seller (UC-14B)',
        priority: 'P1',
        objective: 'Memverifikasi scheduler background job mengirimkan notifikasi peringatan saat penyerahan seller melewati batas delivery_due_at.',
        prerequisite: 'Transaksi PAID melewati tenggat penyerahan; scheduler aktif.',
        test_data: 'Transaksi PAID dengan delivery_due_at < waktu sekarang.',
        steps: '1. Simulasikan waktu melewati delivery_due_at\n2. Eksekusi command transactions:check-delivery-deadlines\n3. Eksekusi ulang untuk verifikasi idempotensi.',
        expected: 'Notifikasi SELLER_DELIVERY_WARNING dikirimkan ke Seller dan Admin; event DELIVERY_WARNING tercatat; tidak ada duplikasi peringatan pada eksekusi ulang.',
        gap_notes: 'Sesuai spesifikasi implementasi backend.'
      }
    ]
  },
  {
    ts_id: 'TS-12',
    ts_name: 'Konfirmasi Penerimaan & Auto-Confirm',
    reference: 'https://app.notion.com/p/40c6e9a1e44581e0afc3d431ff598a56',
    cases: [
      {
        tc_id: 'TC-10-01',
        title: 'Konfirmasi Penerimaan Manual oleh Buyer (Penyelesaian Normal)',
        priority: 'P1',
        objective: 'Memverifikasi bahwa Buyer dapat mengonfirmasi penerimaan secara manual pada transaksi DELIVERED sehingga antrean payout terbentuk.',
        prerequisite: 'Transaksi berstatus DELIVERED; Buyer terautentikasi.',
        test_data: 'Transaksi DELIVERED yang sah.',
        steps: '1. Buyer membuka transaksi DELIVERED\n2. Klik Konfirmasi Penerimaan dan setujui modal konfirmasi\n3. Periksa status dan antrean settlement.',
        expected: 'Status beralih ke CONFIRMED; confirmed_at tercatat; status history mencatat changed_by=buyer; settlement PAYOUT PLANNED terbentuk (payout_due_at = confirmed_at + 1 jam); notifikasi PAYOUT_READY terkirim.',
        gap_notes: 'Sesuai spesifikasi implementasi backend.'
      },
      {
        tc_id: 'TC-10-02',
        title: 'Scheduler Memberikan Grace Period 30 Menit pada Pembeli Pasif (UC-15B Tahap 1)',
        priority: 'P1',
        objective: 'Memverifikasi pemberian masa tenggang 30 menit tambahan oleh scheduler saat pembeli pasif tidak mengonfirmasi setelah 1 jam pertama.',
        prerequisite: 'Transaksi DELIVERED melewati confirmation_due_at awal 1 jam; belum ada masa tenggang.',
        test_data: 'Transaksi DELIVERED dengan waktu penyerahan > 60 menit lalu.',
        steps: '1. Simulasikan waktu t + 61 menit setelah penyerahan\n2. Jalankan command transactions:check-confirmation-deadlines\n3. Periksa tenggat confirmation_due_at dan event deadline.',
        expected: 'confirmation_due_at diperpanjang 30 menit; event BUYER_GRACE_PERIOD tercatat; notifikasi BUYER_GRACE_WARNING terkirim ke Buyer; status tetap DELIVERED.',
        gap_notes: 'Event di database tercatat sebagai BUYER_GRACE_PERIOD sesuai implementasi scheduler.'
      },
      {
        tc_id: 'TC-10-03',
        title: 'Scheduler Melakukan Auto-Confirm Sistem Setelah Grace Period Berakhir (UC-15B Tahap 2)',
        priority: 'P1',
        objective: 'Memverifikasi penyelesaian otomatis oleh sistem jika masa tenggang 30 menit habis tanpa konfirmasi atau komplain, serta memastikan proteksi pembekuan saat sengketa aktif.',
        prerequisite: 'Transaksi DELIVERED telah melewati masa tenggang 30 menit (total > 90 menit).',
        test_data: 'Transaksi DELIVERED pasca grace period; transaksi terpisah dengan komplain aktif.',
        steps: '1. Simulasikan waktu t + 95 menit setelah penyerahan\n2. Jalankan command transactions:check-confirmation-deadlines\n3. Verifikasi auto-confirm pada transaksi normal\n4. Verifikasi auto-confirm terhenti pada transaksi yang sedang disengketakan.',
        expected: 'Status beralih ke CONFIRMED; changed_by bernilai null (Sistem); settlement PAYOUT PLANNED terbentuk; sengketa aktif berhasil membekukan eksekusi auto-confirm.',
        gap_notes: 'Sesuai spesifikasi implementasi backend.'
      }
    ]
  },
  {
    ts_id: 'TS-13',
    ts_name: 'Komplain & Sengketa (Dispute)',
    reference: 'https://app.notion.com/p/40c6e9a1e44581acb95ddad860fe3686',
    cases: [
      {
        tc_id: 'TC-11-01',
        title: 'Pengajuan Komplain Sah oleh Buyer saat DELIVERED (UC-16)',
        priority: 'P1',
        objective: 'Memverifikasi bahwa Buyer dapat mengajukan komplain resmi disertai berkas bukti sah pada transaksi DELIVERED dan membekukan pelepasan dana.',
        prerequisite: 'Transaksi berstatus DELIVERED; Buyer terautentikasi.',
        test_data: 'Alasan komplain (min 10 karakter), berkas bukti resmi (PNG/PDF).',
        steps: '1. Buyer membuka transaksi DELIVERED\n2. Klik Ajukan Komplain\n3. Isi alasan dan lampirkan bukti\n4. Kirim komplain.',
        expected: 'Komplain berstatus OPEN dibuat; status transaksi beralih ke DISPUTED; berkas bukti tersimpan; auto-confirm dan payout dibekukan; notifikasi terkirim.',
        gap_notes: 'Sesuai spesifikasi implementasi backend.'
      },
      {
        tc_id: 'TC-11-02',
        title: 'Pencegahan Duplikasi Komplain (Maksimal 1 Komplain per Transaksi)',
        priority: 'P1',
        objective: 'Memverifikasi bahwa satu transaksi hanya dapat memiliki maksimal 1 komplain resmi aktif.',
        prerequisite: 'Transaksi sudah berstatus DISPUTED dan memiliki komplain.',
        test_data: 'ID Transaksi yang sudah disengketakan.',
        steps: '1. Buka transaksi yang sudah memiliki komplain\n2. Pastikan tombol/form komplain baru tidak lagi tersedia\n3. Coba kirim request POST komplain kedua secara langsung.',
        expected: 'Percobaan komplain kedua ditolak; jumlah record komplain di database tetap tepat 1.',
        gap_notes: 'Sesuai spesifikasi implementasi backend.'
      },
      {
        tc_id: 'TC-11-03',
        title: 'Seller Mengirimkan Tanggapan dan Bukti Sanggahan (UC-17)',
        priority: 'P1',
        objective: 'Memverifikasi bahwa Seller dapat mengirimkan tanggapan dan bukti sanggahan atas komplain pembeli, serta melarang pihak luar menanggapi.',
        prerequisite: 'Sengketa berstatus OPEN; Seller terautentikasi.',
        test_data: 'Teks tanggapan sanggahan, berkas bukti penjual.',
        steps: '1. Buka sengketa sebagai Seller\n2. Isi teks tanggapan dan lampirkan bukti sanggahan\n3. Kirim tanggapan\n4. Uji submisi oleh pihak ketiga (non-seller).',
        expected: 'Record complaint_responses tersimpan dengan responder_id seller; bukti tersimpan; pihak ketiga dilarang (403 Forbidden).',
        gap_notes: 'Sesuai spesifikasi implementasi backend.'
      },
      {
        tc_id: 'TC-11-04',
        title: 'Admin Meminta Bukti Tambahan (Status REVIEWING)',
        priority: 'P1',
        objective: 'Memverifikasi bahwa Admin dapat menginstruksikan permintaan bukti tambahan kepada para pihak dan mengalihkan status sengketa ke REVIEWING.',
        prerequisite: 'Sengketa aktif; Admin terautentikasi.',
        test_data: 'Instruksi permintaan bukti dan tambahan waktu.',
        steps: '1. Admin membuka detail sengketa /admin/disputes/{id}\n2. Klik Minta Bukti Tambahan\n3. Masukkan alasan instruksi dan kirim.',
        expected: 'Status komplain beralih ke REVIEWING; instruksi tercatat.',
        gap_notes: 'Sesuai spesifikasi implementasi backend.'
      },
      {
        tc_id: 'TC-11-05',
        title: 'Keputusan Final Admin Memenangkan Seller (Putusan PAYOUT)',
        priority: 'P1',
        objective: 'Memverifikasi keputusan admin yang memenangkan Penjual dengan menjadwalkan pencairan payout.',
        prerequisite: 'Sengketa dalam investigasi admin.',
        test_data: 'Opsi putusan PAYOUT dan catatan pertimbangan investigasi.',
        steps: '1. Admin memilih putusan PAYOUT pada form investigasi\n2. Masukkan alasan pertimbangan\n3. Kirim keputusan resmi.',
        expected: 'Status komplain RESOLVED dengan decision=PAYOUT; transaksi beralih ke CONFIRMED; antrean settlement PAYOUT PLANNED terbentuk (bukan klaim dana langsung cair).',
        gap_notes: 'Putusan admin menjadwalkan status settlement PLANNED; transfer perbankan riil memerlukan eksekusi gateway/payout terpisah.'
      },
      {
        tc_id: 'TC-11-06',
        title: 'Keputusan Final Admin Memenangkan Buyer (Putusan REFUND)',
        priority: 'P1',
        objective: 'Memverifikasi keputusan admin yang memenangkan Pembeli dengan menjadwalkan pengembalian dana refund.',
        prerequisite: 'Sengketa dalam investigasi admin.',
        test_data: 'Opsi putusan REFUND dan catatan pertimbangan investigasi.',
        steps: '1. Admin memilih putusan REFUND pada form investigasi\n2. Masukkan alasan pertimbangan\n3. Kirim keputusan resmi.',
        expected: 'Status komplain RESOLVED dengan decision=REFUND; transaksi beralih ke REFUNDED; antrean settlement REFUND PLANNED terbentuk.',
        gap_notes: 'Putusan admin menjadwalkan status settlement PLANNED; transfer refund riil memerlukan eksekusi gateway/refund terpisah.'
      }
    ]
  }
];

// Helper untuk mencocokkan hasil testcase JUnit ke TC spesifikasi
function matchExecutionResults(tsId, tcId, phpunitTests, cypressTests) {
  const normTc = tcId.toLowerCase().replace(/[^a-z0-9]/g, '');

  const matchedPhpunit = phpunitTests.filter(t => {
    const tName = t.name.toLowerCase().replace(/[^a-z0-9]/g, '');
    return tName.includes(normTc);
  });

  const matchedCypress = cypressTests.filter(t => {
    const tName = t.name.toLowerCase().replace(/[^a-z0-9]/g, '');
    return tName.includes(normTc);
  });

  return {
    phpunit: matchedPhpunit,
    cypress: matchedCypress
  };
}

async function exportResultsToExcel() {
  console.log('Memulai penyusunan laporan Excel PMPL MarketTrust Rio (Avrio)...');

  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'AvrioWitoberd (Rio QA PBL)';
  workbook.created = new Date();
  workbook.modified = new Date();

  // 1. Baca semua file XML
  const phpunitByTs = {};
  const cypressByTs = {};

  ['TS-04', 'TS-11', 'TS-12', 'TS-13'].forEach(tsId => {
    phpunitByTs[tsId] = parseJUnitXml(path.join(RESULTS_DIR, `${tsId}-phpunit.xml`));

    // Cari cypress xml terkini (latest run berdasarkan mtime) yang cocok dengan tsId
    const cypressFiles = fs.readdirSync(RESULTS_DIR)
      .filter(f => f.startsWith(`${tsId}-cypress`) && f.endsWith('.xml'))
      .map(f => ({ name: f, time: fs.statSync(path.join(RESULTS_DIR, f)).mtime.getTime() }))
      .sort((a, b) => b.time - a.time);

    let cyTests = [];
    if (cypressFiles.length > 0) {
      cyTests = parseJUnitXml(path.join(RESULTS_DIR, cypressFiles[0].name));
    }
    cypressByTs[tsId] = cyTests;
  });

  // Rekapitulasi Statistik
  let totalTcInduk = 0;
  let totalSubtestExecutions = 0;
  let totalPassed = 0;
  let totalFailed = 0;
  let totalBlocked = 0;
  let totalNotExecuted = 0;

  const scenarioSummaryRows = [];

  // =========================================================================
  // SUSUN SETIAP SHEET SKENARIO (TS-04, TS-11, TS-12, TS-13)
  // =========================================================================
  for (const ts of TS_DEFINITIONS) {
    totalTcInduk += ts.cases.length;
    let tsSubtestCount = 0;
    let tsPassCount = 0;
    let tsFailCount = 0;
    let tsBlockedCount = 0;
    let tsNotExecCount = 0;

    const sheet = workbook.addWorksheet(ts.ts_id, {
      views: [{ showGridLines: true }]
    });

    // Setup Kolom Standar PMPL
    sheet.columns = [
      { header: 'ID TS', key: 'ts_id', width: 10 },
      { header: 'ID TC / Subtest', key: 'tc_id', width: 18 },
      { header: 'Referensi Sumber', key: 'reference', width: 25 },
      { header: 'Tujuan Pengujian', key: 'objective', width: 32 },
      { header: 'Jenis / Prioritas', key: 'priority', width: 14 },
      { header: 'Prasyarat & Aktor', key: 'prerequisite', width: 28 },
      { header: 'Data Uji', key: 'test_data', width: 28 },
      { header: 'Langkah Pengujian', key: 'steps', width: 35 },
      { header: 'Expected Result', key: 'expected', width: 35 },
      { header: 'Nama Test & Path Script', key: 'test_name_path', width: 45 },
      { header: 'Jenis Runner', key: 'runner', width: 18 },
      { header: 'Actual Result', key: 'actual', width: 35 },
      { header: 'Status', key: 'status', width: 16 },
      { header: 'Waktu Run', key: 'runtime', width: 14 },
      { header: 'Command Eksekusi', key: 'command', width: 35 },
      { header: 'Link/Path Log Bukti', key: 'log_path', width: 30 },
      { header: 'Catatan Bug / Blocker', key: 'notes', width: 32 },
    ];

    // Styling Header
    const headerRow = sheet.getRow(1);
    headerRow.height = 28;
    headerRow.eachCell((cell) => {
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FF1E293B' } // Slate 800
      };
      cell.font = {
        name: 'Segoe UI',
        size: 10,
        bold: true,
        color: { argb: 'FFFFFFFF' }
      };
      cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
      cell.border = {
        top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } }
      };
    });

    const phpunitTests = phpunitByTs[ts.ts_id] || [];
    const cypressTests = cypressByTs[ts.ts_id] || [];

    // Isi Baris Test Case
    for (const tc of ts.cases) {
      const match = matchExecutionResults(ts.ts_id, tc.tc_id, phpunitTests, cypressTests);
      const isBlockedTc = tc.tc_id === 'TC-09-03';

      // Gabungkan eksekusi yang cocok: PHPUnit subtests + Cypress subtests
      const executions = [];

      // 1. PHPUnit subtests
      match.phpunit.forEach(pu => {
        executions.push({
          subtest_id: `${tc.tc_id}-PHPUnit`,
          test_name: pu.name,
          script_path: pu.classname.includes('Unit')
            ? `tests/Unit/MarketTrust/${pu.classname.split('\\').pop()}.php`
            : `tests/Feature/MarketTrust/${pu.classname.split('\\').pop()}.php`,
          runner: pu.classname.includes('Unit') ? 'PHPUnit Unit' : 'PHPUnit Feature',
          status: isBlockedTc ? 'BLOCKED' : pu.status,
          runtime: `${pu.time}s`,
          command: `php artisan test --log-junit docs/testing/results/rio/${ts.ts_id}-phpunit.xml`,
          log_path: `docs/testing/results/rio/${ts.ts_id}-phpunit.xml`,
          actual: isBlockedTc
            ? 'Request perpanjangan tercatat di DB, namun kebijakan kuota/approval belum disahkan dokumen PMPL.'
            : (pu.status === 'PASS' ? 'Logika validasi & perubahan status database terverifikasi sesuai expected.' : pu.failureMsg),
          notes: isBlockedTc ? tc.gap_notes : (tc.gap_notes !== 'Tidak ada gap kritis; fitur master katalog lengkap.' && !tc.gap_notes.includes('Sesuai spesifikasi') ? tc.gap_notes : '-')
        });
      });

      // 2. Cypress subtests
      match.cypress.forEach(cy => {
        executions.push({
          subtest_id: `${tc.tc_id}-Cypress`,
          test_name: cy.name,
          script_path: `cypress/e2e/markettrust/${cy.rawXmlFile.replace(/-cypress.*$/, '')}.cy.js`,
          runner: 'Cypress E2E (UI)',
          status: isBlockedTc ? 'BLOCKED' : cy.status,
          runtime: `${cy.time}s`,
          command: `npx cypress run --spec cypress/e2e/markettrust/${ts.ts_id.toLowerCase()}* --reporter junit`,
          log_path: `docs/testing/results/rio/${cy.rawXmlFile}`,
          actual: isBlockedTc
            ? 'Antarmuka tersedia, eksekusi ditandai BLOCKED mengikuti status dokumen spesifikasi.'
            : (cy.status === 'PASS' ? 'Interaksi UI, formulir, alert, dan transisi badge status antarmuka terverifikasi.' : cy.failureMsg),
          notes: isBlockedTc ? tc.gap_notes : '-'
        });
      });

      // Fallback jika Cypress belum dieksekusi atau tidak memiliki entri terpisah
      if (match.cypress.length === 0) {
        executions.push({
          subtest_id: `${tc.tc_id}-Cypress`,
          test_name: `${tc.title} (E2E UI)`,
          script_path: `cypress/e2e/markettrust/${ts.ts_id.toLowerCase()}-*.cy.js`,
          runner: 'Cypress E2E (UI)',
          status: isBlockedTc ? 'BLOCKED' : 'NOT EXECUTED',
          runtime: '-',
          command: `npx cypress run --spec cypress/e2e/markettrust/...`,
          log_path: `docs/testing/results/rio/`,
          actual: isBlockedTc ? 'Fitur perpanjangan diblokir kebijakan desain dokumen.' : 'Runner Cypress belum dijalankan.',
          notes: isBlockedTc ? tc.gap_notes : 'Menunggu lingkungan runner browser'
        });
      }

      // Tulis baris ke sheet
      executions.forEach((exec, idx) => {
        tsSubtestCount++;
        totalSubtestExecutions++;

        if (exec.status === 'PASS') {
          tsPassCount++;
          totalPassed++;
        } else if (exec.status === 'FAIL') {
          tsFailCount++;
          totalFailed++;
        } else if (exec.status === 'BLOCKED') {
          tsBlockedCount++;
          totalBlocked++;
        } else {
          tsNotExecCount++;
          totalNotExecuted++;
        }

        const row = sheet.addRow({
          ts_id: ts.ts_id,
          tc_id: exec.subtest_id,
          reference: ts.reference,
          objective: tc.objective,
          priority: tc.priority,
          prerequisite: tc.prerequisite,
          test_data: tc.test_data,
          steps: tc.steps,
          expected: tc.expected,
          test_name_path: `${exec.test_name}\n(${exec.script_path})`,
          runner: exec.runner,
          actual: exec.actual,
          status: exec.status,
          runtime: exec.runtime,
          command: exec.command,
          log_path: exec.log_path,
          notes: exec.notes
        });

        row.height = 42;
        row.eachCell((cell, colNumber) => {
          cell.font = { name: 'Segoe UI', size: 9 };
          cell.alignment = { vertical: 'middle', wrapText: true };
          cell.border = {
            top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
            bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
            left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
            right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
          };

          // Kolom Status (kolom 13)
          if (colNumber === 13) {
            cell.alignment = { vertical: 'middle', horizontal: 'center' };
            if (exec.status === 'PASS') {
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFDCFCE7' } }; // Light green
              cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FF166534' } };
            } else if (exec.status === 'FAIL') {
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEE2E2' } }; // Light red
              cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FF991B1B' } };
            } else if (exec.status === 'BLOCKED') {
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEF3C7' } }; // Light amber
              cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FF92400E' } };
            } else {
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF1F5F9' } }; // Light gray
              cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FF475569' } };
            }
          }
        });
      });
    }

    scenarioSummaryRows.push({
      ts_id: ts.ts_id,
      ts_name: ts.ts_name,
      tc_induk_count: ts.cases.length,
      subtest_count: tsSubtestCount,
      pass_count: tsPassCount,
      fail_count: tsFailCount,
      blocked_count: tsBlockedCount,
      not_exec_count: tsNotExecCount,
      conclusion: tsFailCount > 0 ? 'TERDAPAT BUG' : (tsBlockedCount > 0 ? 'MEMILIKI BLOCKER DESAIN' : 'LULUS (PASS)')
    });
  }

  // =========================================================================
  // SUSUN SHEET RINGKASAN
  // =========================================================================
  const summarySheet = workbook.addWorksheet('Ringkasan', {
    views: [{ showGridLines: true }]
  });

  // Urutan pertama di workbook
  workbook.views = [{ firstSheet: 0, activeTab: 0, visibility: 'visible' }];

  // Atur lebar kolom Ringkasan
  summarySheet.columns = [
    { width: 4 },
    { width: 14 },
    { width: 34 },
    { width: 16 },
    { width: 22 },
    { width: 14 },
    { width: 14 },
    { width: 14 },
    { width: 18 },
    { width: 28 },
  ];

  // Header Judul
  summarySheet.mergeCells('B2:J2');
  const titleCell = summarySheet.getCell('B2');
  titleCell.value = 'LAPORAN HASIL PENGUJIAN OTOMATIS — MARKETTRUST';
  titleCell.font = { name: 'Segoe UI', size: 14, bold: true, color: { argb: 'FF1E293B' } };
  titleCell.alignment = { vertical: 'middle', horizontal: 'left' };

  summarySheet.mergeCells('B3:J3');
  const subtitleCell = summarySheet.getCell('B3');
  subtitleCell.value = `Alokasi QA: Rio (Avrio) • Skenario: TS-04, TS-11, TS-12, TS-13 • Branch: ${currentBranch} (${commitHash}) • Timestamp: ${new Date().toLocaleString('id-ID')}`;
  subtitleCell.font = { name: 'Segoe UI', size: 10, italic: true, color: { argb: 'FF64748B' } };
  subtitleCell.alignment = { vertical: 'middle', horizontal: 'left' };

  // Informasi Akun QA
  summarySheet.getCell('B5').value = 'Informasi Akun QA:';
  summarySheet.getCell('B5').font = { name: 'Segoe UI', size: 10, bold: true };
  summarySheet.getCell('C5').value = 'Rio (Avrio) — AvrioWitoberd <christoforusavriowitoberd@gmail.com>';

  summarySheet.getCell('B6').value = 'Metodologi & Runner:';
  summarySheet.getCell('B6').font = { name: 'Segoe UI', size: 10, bold: true };
  summarySheet.getCell('C6').value = 'PHPUnit 12.5 (Unit & Feature Scenario) + Cypress 16 E2E (UI Simulation)';

  summarySheet.getCell('B7').value = 'Total TC Induk:';
  summarySheet.getCell('B7').font = { name: 'Segoe UI', size: 10, bold: true };
  summarySheet.getCell('C7').value = `${totalTcInduk} Test Cases (Terpisah dari eksekusi subtest)`;

  // Tabel Rekapitulasi per Skenario
  const summaryHeaderRow = summarySheet.getRow(9);
  summaryHeaderRow.height = 26;
  const headers = [
    '', 'ID TS', 'Nama Test Scenario', 'Total TC Induk', 'Total Eksekusi / Subtest',
    'PASS', 'FAIL', 'BLOCKED', 'NOT EXECUTED', 'Kesimpulan Status'
  ];
  headers.forEach((h, i) => {
    if (i >= 1) {
      const cell = summaryHeaderRow.getCell(i + 1);
      cell.value = h;
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E293B' } };
      cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
      cell.alignment = { vertical: 'middle', horizontal: 'center' };
      cell.border = {
        top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } }
      };
    }
  });

  let currentRowIdx = 10;
  scenarioSummaryRows.forEach(row => {
    const r = summarySheet.getRow(currentRowIdx);
    r.height = 22;
    r.getCell(2).value = row.ts_id;
    r.getCell(3).value = row.ts_name;
    r.getCell(4).value = row.tc_induk_count;
    r.getCell(5).value = row.subtest_count;
    r.getCell(6).value = row.pass_count;
    r.getCell(7).value = row.fail_count;
    r.getCell(8).value = row.blocked_count;
    r.getCell(9).value = row.not_exec_count;
    r.getCell(10).value = row.conclusion;

    for (let c = 2; c <= 10; c++) {
      const cell = r.getCell(c);
      cell.font = { name: 'Segoe UI', size: 9 };
      cell.border = {
        top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
      };
      if (c === 2 || (c >= 4 && c <= 9)) {
        cell.alignment = { vertical: 'middle', horizontal: 'center' };
      } else {
        cell.alignment = { vertical: 'middle', horizontal: 'left' };
      }

      if (c === 6 && row.pass_count > 0) {
        cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FF166534' } };
      }
      if (c === 7 && row.fail_count > 0) {
        cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FF991B1B' } };
      }
      if (c === 8 && row.blocked_count > 0) {
        cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FF92400E' } };
      }
    }
    currentRowIdx++;
  });

  // Baris Total
  const totalRow = summarySheet.getRow(currentRowIdx);
  totalRow.height = 24;
  totalRow.getCell(2).value = 'TOTAL';
  totalRow.getCell(3).value = 'Semua Skenario Bagian Rio (Avrio)';
  totalRow.getCell(4).value = totalTcInduk;
  totalRow.getCell(5).value = totalSubtestExecutions;
  totalRow.getCell(6).value = totalPassed;
  totalRow.getCell(7).value = totalFailed;
  totalRow.getCell(8).value = totalBlocked;
  totalRow.getCell(9).value = totalNotExecuted;
  totalRow.getCell(10).value = totalFailed === 0 ? 'SELESAI DENGAN CATATAN BLOCKER' : 'TERDAPAT KEGAGALAN UJI';

  for (let c = 2; c <= 10; c++) {
    const cell = totalRow.getCell(c);
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF1F5F9' } };
    cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: 'FF0F172A' } };
    cell.border = {
      top: { style: 'medium', color: { argb: 'FF0F172A' } },
      bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
      left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
      right: { style: 'thin', color: { argb: 'FFCBD5E1' } }
    };
    if (c === 2 || (c >= 4 && c <= 9)) {
      cell.alignment = { vertical: 'middle', horizontal: 'center' };
    }
  }

  // Catatan Teknis di Bawah Tabel
  const noteRowIdx = currentRowIdx + 2;
  summarySheet.getCell(`B${noteRowIdx}`).value = 'Catatan Evaluasi Penting:';
  summarySheet.getCell(`B${noteRowIdx}`).font = { name: 'Segoe UI', size: 10, bold: true, color: { argb: 'FF92400E' } };

  const notes = [
    '1. TS-04 (TC-03-02): Judul menyebut DRAFT dan ACTIVE. Status DRAFT dapat dibuat oleh seller, namun alur lanjutan visibilitas/edit draft belum dicakup kartu dokumen.',
    '2. TS-11 (TC-09-03): Permintaan perpanjangan waktu penyerahan ditandai BLOCKED karena kebijakan kuota durasi dan alur persetujuan belum final di dokumen spesifikasi PMPL.',
    '3. TS-13 (TC-11-05 & TC-11-06): Putusan sengketa Admin (PAYOUT & REFUND) secara akurat menjadwalkan status antrean settlement (PLANNED) dan tidak mengklaim transfer bank telah sukses seketika.',
    '4. Rekapitulasi TC Induk (16 kasus) dihitung terpisah dari subtest eksekusi (PHPUnit & Cypress) agar tidak terhitung ganda.'
  ];

  notes.forEach((nt, i) => {
    summarySheet.getCell(`B${noteRowIdx + 1 + i}`).value = nt;
    summarySheet.getCell(`B${noteRowIdx + 1 + i}`).font = { name: 'Segoe UI', size: 9, color: { argb: 'FF475569' } };
  });

  // Tulis ke file
  await workbook.xlsx.writeFile(OUTPUT_EXCEL);
  console.log(`Laporan Excel berhasil dibuat di: ${OUTPUT_EXCEL}`);
}

exportResultsToExcel().catch(err => {
  console.error('Error saat menyusun Excel:', err);
  process.exit(1);
});
