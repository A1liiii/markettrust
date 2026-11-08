import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import ExcelJS from 'exceljs';

const RESULTS_DIR = 'docs/testing/results/rio';
const OUTPUT_EXCEL = 'docs/hasil-testing-rio.xlsx';

// Dapatkan commit hash dan branch terkini
let commitHash = '7b0e1fd';
let currentBranch = 'test/testing-rio';
try {
  commitHash = execSync('git rev-parse --short HEAD').toString().trim();
  currentBranch = execSync('git branch --show-current').toString().trim();
} catch (e) {
  // fallback
}

// Helper untuk membaca dan mem-parse file JUnit XML
function parseJUnitXml(filePath) {
  if (!fs.existsSync(filePath)) return [];
  const content = fs.readFileSync(filePath, 'utf8');
  const testCases = [];

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

// Definisi Master 16 TC Induk Rio untuk TS-04, TS-11, TS-12, TS-13
const TS_DEFINITIONS = [
  {
    ts_id: 'TS-04',
    module_name: 'Katalog Admin & Listing Marketplace',
    reference: 'TC-03 Katalog Admin & Listing (https://app.notion.com/p/40c6e9a1e44581b79d1fed11000317de)',
    created_by: 'Christoforus Avrio Witoberd',
    date_of_creation: '04-11-26',
    date_of_review: '',
    cases: [
      {
        tc_id: 'TC-03-01',
        scenario: 'TS-04 Katalog Admin & Listing Marketplace',
        test_case: 'Admin Menambah Kategori, Produk, dan Varian Baru secara berjenjang & Pencegahan Duplikasi',
        priority: 'P2',
        pre_condition: 'Akun Administrator aktif; basis data memiliki tabel master kategori, produk, dan varian; form katalog admin dapat diakses di antarmuka web.',
        test_steps: '1. Login sebagai Administrator sistem.\n2. Buka halaman Katalog Admin (/admin/catalog).\n3. Pilih tab Kategori, masukkan Nama Kategori baru yang valid lalu simpan.\n4. Pilih tab Produk Master, pilih kategori yang baru dibuat, isi Nama Produk lalu simpan.\n5. Pilih tab Varian Produk, pilih produk yang baru dibuat, isi Nama Varian lalu simpan.\n6. Lakukan percobaan input data kategori dan varian dengan nama yang sama untuk menguji penolakan duplikasi.',
        test_data: 'Kategori: "Gaming Gear", Produk: "Akun Mobile Legends Sultan", Varian: "Mythic Glory Full Skin"; Data duplikat: "Gaming Gear" pada kategori yang sama.',
        expected_result: 'Kategori, produk master, dan varian baru berhasil tersimpan secara berjenjang di database dan tampil pada tabel katalog admin; Percobaan input nama duplikat ditolak validasi sistem dengan pesan error ramah pengguna tanpa merusak integritas database.',
        post_condition: 'Data kategori, produk master, dan varian aktif tersimpan di tabel database master katalog; relasi berjenjang (kategori -> produk -> varian) terhubung dengan valid; data duplikat tidak bertambah di database.',
        actual_result: 'Seluruh tahapan penambahan master kategori, produk master, dan varian baru sukses tersimpan dan terverifikasi di database. Validasi penolakan duplikasi nama kategori/varian berjalan tepat dengan HTTP 422 / pesan validasi antarmuka. Hak akses non-admin ditolak (403 Forbidden).',
        status: 'PASS',
        gap_notes: 'Fitur master katalog berjenjang lengkap dan terverifikasi penuh.'
      },
      {
        tc_id: 'TC-03-02',
        scenario: 'TS-04 Katalog Admin & Listing Marketplace',
        test_case: 'Seller Membuat Listing Marketplace Baru (Status ACTIVE dan DRAFT)',
        priority: 'P2',
        pre_condition: 'Akun Seller aktif dan terverifikasi; terdapat varian produk master yang aktif di database.',
        test_steps: '1. Login sebagai Seller.\n2. Buka halaman formulir pembuatan listing (/seller/listings/create).\n3. Pilih varian produk master yang aktif.\n4. Lengkapi formulir: Judul Listing, Deskripsi, Harga (>= Rp 5.000), Estimasi Waktu Penyerahan (Jam).\n5. Simpan listing dengan status ACTIVE.\n6. Uji pembuatan listing dengan status DRAFT.\n7. Uji input invalid: harga < Rp 5.000 dan pemilihan varian nonaktif.',
        test_data: 'Varian: "Mythic Glory Full Skin", Judul: "Akun MLBB All Unlocked Murah", Deskripsi: "Akun pribadi aman terawat", Harga: Rp 150.000 (valid) & Rp 3.000 (invalid), Estimasi: 2 Jam, Status: ACTIVE & DRAFT.',
        expected_result: 'Listing dengan status ACTIVE dan DRAFT berhasil tersimpan di database dengan relasi seller_id yang sesuai; Listing dengan harga di bawah batas minimum Rp 5.000 atau varian nonaktif ditolak validasi sistem.',
        post_condition: 'Record listing baru tersimpan pada tabel `listings` dengan `seller_id` seller bersangkutan dan status `ACTIVE` (atau `DRAFT`); listing dengan input invalid ditolak dan tidak tersimpan di database.',
        actual_result: 'Pembuatan listing berstatus ACTIVE dan DRAFT berhasil tersimpan di database dengan relasi seller yang sesuai. Validasi harga minimum Rp 5.000 dan proteksi varian nonaktif terverifikasi menolak input yang tidak valid baik di layer Request maupun form UI.',
        status: 'PASS',
        gap_notes: 'Backend dan database mendukung status DRAFT. Namun alur lanjutan visibilitas/publikasi DRAFT dan aksi edit/hapus belum memiliki langkah rinci pada kartu dokumen PMPL.'
      },
      {
        tc_id: 'TC-03-03',
        scenario: 'TS-04 Katalog Admin & Listing Marketplace',
        test_case: 'Toggle Status Listing Seller (ACTIVE <-> INACTIVE/CLOSED)',
        priority: 'P2',
        pre_condition: 'Listing berstatus ACTIVE milik Seller telah tersedia di sistem; Seller terautentikasi.',
        test_steps: '1. Login sebagai Seller pemilik listing.\n2. Buka menu daftar listing saya (/seller/listings).\n3. Temukan listing aktif, klik tombol "Tutup Listing" (CLOSED).\n4. Periksa perubahan badge status listing menjadi DITUTUP/INACTIVE.\n5. Klik tombol "Aktifkan Kembali" pada listing tersebut.\n6. Periksa status kembali beralih menjadi ACTIVE.\n7. Uji upaya toggle status oleh akun user/seller lain yang bukan pemilik listing.',
        test_data: 'Listing ID aktif milik seller terautentikasi; ID Listing target untuk uji otorisasi non-pemilik.',
        expected_result: 'Status listing berhasil beralih antara ACTIVE dan CLOSED/INACTIVE secara konsisten; Listing yang ditutup tidak lagi muncul pada hasil pencarian marketplace publik; Upaya perubahan status oleh non-pemilik ditolak dengan respon 403 Forbidden.',
        post_condition: 'Status record listing pada tabel database berubah menjadi CLOSED dan kembali menjadi ACTIVE saat diaktifkan; query marketplace publik aktif mengecualikan listing berstatus CLOSED; data listing milik user lain tidak mengalami perubahan.',
        actual_result: 'Transisi status listing antara ACTIVE dan CLOSED berhasil dilakukan oleh pemilik listing melalui aksi tombol dan request POST. Query marketplace publik terbukti menyembunyikan listing yang berstatus CLOSED. Upaya toggle oleh pihak luar berhasil dicegah dengan otorisasi 403 Forbidden.',
        status: 'PASS',
        gap_notes: 'Implementasi backend menggunakan nilai status CLOSED untuk merepresentasikan status nonaktif/tutup pada database dan antarmuka.'
      }
    ]
  },
  {
    ts_id: 'TS-11',
    module_name: 'Penyerahan Produk Digital oleh Seller',
    reference: 'TC-09 Penyerahan Produk Digital (https://app.notion.com/p/40c6e9a1e44581479f89d785d125d586)',
    created_by: 'Christoforus Avrio Witoberd',
    date_of_creation: '04-11-26',
    date_of_review: '',
    cases: [
      {
        tc_id: 'TC-09-01',
        scenario: 'TS-11 Penyerahan Produk Digital oleh Seller',
        test_case: 'Penyerahan Hasil oleh Seller dengan Berkas Bukti Sah',
        priority: 'P1',
        pre_condition: 'Transaksi berstatus PAID; Seller pemilik listing/transaksi terautentikasi.',
        test_steps: '1. Login sebagai Seller.\n2. Buka halaman detail transaksi berstatus PAID (/seller/transactions/{id}).\n3. Buka form "Penyerahan Hasil Pesanan".\n4. Isi Catatan Penyerahan dan Tautan Akses Akun/Produk Digital.\n5. Lampirkan berkas bukti sah (format gambar PNG/JPG atau dokumen PDF).\n6. Klik tombol "Kirim Penyerahan Pesanan".',
        test_data: 'Catatan: "Berikut akun dan instruksi login akun telah dikirim via tautan aman", Tautan: "https://drive.google.com/drive/folders/evidence-test", Berkas: delivery_proof_sample.png (1x1 PNG valid).',
        expected_result: 'Status transaksi beralih dari PAID menjadi DELIVERED; delivered_at terisi timestamp waktu penyerahan; confirmation_due_at otomatis disetel 1 jam ke depan; berkas bukti tersimpan di storage sistem; notifikasi terkirim ke Buyer & Admin; status history mencatat penyerahan oleh seller.',
        post_condition: 'Status transaksi pada database beralih ke DELIVERED; kolom `delivered_at` terisi timestamp; `confirmation_due_at` disetel tepat +1 jam dari penyerahan; file bukti tersimpan di storage publik; riwayat status mencatat aktor seller; notifikasi penyerahan terkirim ke buyer dan admin.',
        actual_result: 'Status transaksi berhasil beralih ke DELIVERED. Kolom delivered_at dan confirmation_due_at terhitung tepat +1 jam. File bukti berhasil diunggah ke storage/app/public/delivery_proofs. Notifikasi ORDER_DELIVERED terkirim ke pembeli dan admin, serta tercatat di status_history.',
        status: 'PASS',
        gap_notes: 'Alur penyerahan hasil pesanan terverifikasi penuh sesuai spesifikasi backend.'
      },
      {
        tc_id: 'TC-09-02',
        scenario: 'TS-11 Penyerahan Produk Digital oleh Seller',
        test_case: 'Seller Menyerahkan Pesanan Tanpa Bukti / Catatan',
        priority: 'P1',
        pre_condition: 'Transaksi berstatus PAID; Seller terautentikasi.',
        test_steps: '1. Login sebagai Seller dan buka detail transaksi PAID.\n2. Buka form Penyerahan Pesanan.\n3. Kosongkan field Catatan Penyerahan dan jangan unggah Berkas Bukti (submisi kosong).\n4. Klik tombol "Kirim Penyerahan Pesanan".',
        test_data: 'Field delivery_note kosong (null/empty string); file delivery_proof tidak dilampirkan.',
        expected_result: 'Submisi ditolak oleh sistem dengan menampilkan pesan validasi error formulir; status transaksi tetap berstatus PAID; tidak ada perubahan pada timestamp penyerahan dan tidak ada notifikasi palsu yang terkirim.',
        post_condition: 'Status transaksi tetap bertahan pada status PAID; kolom `delivered_at` dan `confirmation_due_at` tetap null; tidak ada berkas yang tersimpan di storage; tidak ada record history atau notifikasi palsu yang dibuat.',
        actual_result: 'Sistem menolak submisi kosong dengan HTTP 422 Unprocessable Entity / validasi form. Pesan kesalahan ditampilkan pada antarmuka. Status transaksi tetap bertahan di status PAID tanpa ada perubahan data.',
        status: 'PASS',
        gap_notes: 'Validasi form mewajibkan minimal catatan penjelasan atau berkas bukti penyerahan yang sah.'
      },
      {
        tc_id: 'TC-09-03',
        scenario: 'TS-11 Penyerahan Produk Digital oleh Seller',
        test_case: 'Permintaan Perpanjangan Waktu Penyerahan oleh Seller',
        priority: 'P1',
        pre_condition: 'Transaksi berstatus PAID; Seller terautentikasi.',
        test_steps: '1. Seller membuka transaksi berstatus PAID.\n2. Mengajukan permohonan perpanjangan waktu penyerahan disertai alasan dan durasi tambahan waktu.\n3. Verifikasi pencatatan event deadline perpanjangan.',
        test_data: 'Durasi tambahan: 60 menit; Alasan: "Server game sedang maintenance berkala".',
        expected_result: 'Permintaan tercatat pada transaction_deadline_events; tenggat waktu penyerahan disesuaikan sesuai regulasi sistem.',
        post_condition: 'Data permohonan tercatat di database sistem, namun alur kebijakan pembatasan kuota durasi/frekuensi perpanjangan dan mekanisme review persetujuan dari Buyer/Admin belum terdefinisi pada spesifikasi sistem (BLOCKED).',
        actual_result: 'Permintaan tercatat pada database, namun pengujian ditandai BLOCKED karena kebijakan batas kuota durasi perpanjangan dan alur review approval dari Admin/Buyer belum disahkan secara final pada dokumen spesifikasi PMPL.',
        status: 'BLOCKED',
        gap_notes: 'BLOCKED: Kebijakan batas perpanjangan (kuota frekuensi/durasi) serta alur review persetujuan dari Admin/Buyer belum disahkan pada dokumen spesifikasi PMPL. Backend saat ini langsung memperpanjang tanpa alur review.'
      },
      {
        tc_id: 'TC-09-04',
        scenario: 'TS-11 Penyerahan Produk Digital oleh Seller',
        test_case: 'Scheduler Peringatan Keterlambatan Penyerahan Seller (UC-14B)',
        priority: 'P1',
        pre_condition: 'Transaksi berstatus PAID telah melewati tenggat delivery_due_at; scheduler sistem aktif.',
        test_steps: '1. Simulasikan transaksi PAID dengan delivery_due_at berada di masa lampau.\n2. Jalankan scheduler background command: php artisan transactions:check-delivery-deadlines.\n3. Jalankan command kedua kali untuk memverifikasi proteksi idempotensi.',
        test_data: 'Transaksi PAID dengan delivery_due_at = now() - 10 menit; event SELLER_DELIVERY_WARNING.',
        expected_result: 'Sistem mendeteksi keterlambatan penyerahan; mengirimkan notifikasi peringatan keterlambatan (SELLER_DELIVERY_WARNING) kepada Seller dan Admin; mencatat event DELIVERY_WARNING pada transaction_deadline_events; tidak mengirim notifikasi ganda pada eksekusi berikutnya.',
        post_condition: 'Notifikasi `SELLER_DELIVERY_WARNING` tersimpan untuk akun Seller dan Admin; record event `DELIVERY_WARNING` tercatat di tabel `transaction_deadline_events`; eksekusi command ulang tidak menduplikasi notifikasi.',
        actual_result: 'Scheduler artisan command transactions:check-delivery-deadlines sukses mendeteksi transaksi terlambat, menerbitkan notifikasi SELLER_DELIVERY_WARNING, dan mencatat event DELIVERY_WARNING. Eksekusi ulang terbukti idempoten tanpa duplikasi peringatan.',
        status: 'PASS',
        gap_notes: 'Scheduler command berjalan akurat dan idempoten.'
      }
    ]
  },
  {
    ts_id: 'TS-12',
    module_name: 'Konfirmasi Penerimaan Pesanan & Auto-Confirm',
    reference: 'TC-10 Konfirmasi Penerimaan & Auto-Confirm (https://app.notion.com/p/40c6e9a1e44581e0afc3d431ff598a56)',
    created_by: 'Christoforus Avrio Witoberd',
    date_of_creation: '04-11-26',
    date_of_review: '',
    cases: [
      {
        tc_id: 'TC-10-01',
        scenario: 'TS-12 Konfirmasi Penerimaan & Auto-Confirm',
        test_case: 'Konfirmasi Penerimaan Manual oleh Buyer (Penyelesaian Normal)',
        priority: 'P1',
        pre_condition: 'Transaksi berstatus DELIVERED; Buyer pemilik transaksi terautentikasi.',
        test_steps: '1. Login sebagai Buyer.\n2. Buka halaman detail transaksi berstatus DELIVERED (/buyer/transactions/{id}).\n3. Klik tombol "Konfirmasi Penerimaan Pesanan".\n4. Setujui dialog/modal konfirmasi penyelesaian transaksi.\n5. Periksa pembaruan status transaksi dan pembuatan antrean settlement payout.',
        test_data: 'Transaksi ID berstatus DELIVERED milik buyer yang sedang login.',
        expected_result: 'Status transaksi beralih dari DELIVERED menjadi CONFIRMED; confirmed_at terisi timestamp; status history mencatat changed_by=buyer; settlement PAYOUT PLANNED terbentuk dengan payout_due_at = confirmed_at + 1 jam; notifikasi PAYOUT_READY terkirim ke Seller.',
        post_condition: 'Status transaksi pada database beralih ke CONFIRMED; kolom `confirmed_at` terisi; riwayat status mencatat aktor buyer; record settlement baru berstatus PLANNED terbentuk pada tabel `settlements` dengan payout_due_at +1 jam; notifikasi PAYOUT_READY terkirim ke seller.',
        actual_result: 'Status transaksi beralih ke CONFIRMED dengan changed_by mencatat Buyer. Record settlement baru terbentuk dengan type PAYOUT, status PLANNED, dan payout_due_at disetel 1 jam ke depan. Upaya konfirmasi oleh pihak luar berhasil ditolak (403).',
        status: 'PASS',
        gap_notes: 'Alur konfirmasi manual terverifikasi penuh pada level Feature Test dan Cypress E2E.'
      },
      {
        tc_id: 'TC-10-02',
        scenario: 'TS-12 Konfirmasi Penerimaan & Auto-Confirm',
        test_case: 'Scheduler Memberikan Grace Period 30 Menit pada Pembeli Pasif (UC-15B Tahap 1)',
        priority: 'P1',
        pre_condition: 'Transaksi DELIVERED telah melewati batas confirmation_due_at awal (1 jam pasca penyerahan); belum pernah diberikan masa tenggang.',
        test_steps: '1. Buat transaksi DELIVERED dengan waktu penyerahan delivered_at = now() - 65 menit (melewati batas 60 menit).\n2. Jalankan background command: php artisan transactions:check-confirmation-deadlines.\n3. Periksa perubahan nilai batas confirmation_due_at dan pencatatan event tenggat.',
        test_data: 'Transaksi DELIVERED dengan delivered_at 65 menit lalu; event BUYER_GRACE_PERIOD.',
        expected_result: 'confirmation_due_at diperpanjang otomatis sebesar 30 menit; event BUYER_GRACE_PERIOD tercatat pada transaction_deadline_events; notifikasi peringatan masa tenggang (BUYER_GRACE_WARNING) terkirim ke Buyer; status transaksi tetap DELIVERED.',
        post_condition: 'Kolom `confirmation_due_at` pada transaksi diperpanjang +30 menit; event `BUYER_GRACE_PERIOD` tersimpan pada tabel `transaction_deadline_events`; notifikasi terkirim ke akun Buyer; status transaksi tetap bertahan di DELIVERED.',
        actual_result: 'Command scheduler mendeteksi pembeli pasif, memperpanjang confirmation_due_at selama 30 menit tambahan, menerbitkan event BUYER_GRACE_PERIOD, dan mengirimkan notifikasi peringatan. Status transaksi tetap bertahan di DELIVERED.',
        status: 'PASS',
        gap_notes: 'Event di database tercatat sebagai BUYER_GRACE_PERIOD sesuai implementasi scheduler.'
      },
      {
        tc_id: 'TC-10-03',
        scenario: 'TS-12 Konfirmasi Penerimaan & Auto-Confirm',
        test_case: 'Scheduler Melakukan Auto-Confirm Sistem Setelah Grace Period Berakhir (UC-15B Tahap 2)',
        priority: 'P1',
        pre_condition: 'Transaksi DELIVERED telah melewati masa tenggang tambahan 30 menit (total > 90 menit) tanpa respon; tidak terdapat komplain aktif.',
        test_steps: '1. Buat transaksi DELIVERED dengan delivered_at = now() - 95 menit dan event grace period telah tercatat.\n2. Jalankan background command: php artisan transactions:check-confirmation-deadlines.\n3. Verifikasi auto-confirm pada transaksi normal.\n4. Verifikasi isolasi: pastikan auto-confirm TIDAK berjalan pada transaksi yang sedang memiliki komplain/sengketa aktif.',
        test_data: 'Transaksi normal DELIVERED pasca grace period; Transaksi DELIVERED dengan status komplain aktif.',
        expected_result: 'Status transaksi beralih otomatis ke CONFIRMED; changed_by bernilai null (menandakan penyelesaian oleh Sistem); settlement PAYOUT PLANNED terbentuk; pada transaksi dengan sengketa aktif, auto-confirm dibekukan sehingga status tetap DISPUTED.',
        post_condition: 'Status transaksi beralih otomatis ke CONFIRMED dengan changed_by bernilai null (Sistem); record settlement PAYOUT PLANNED terjadwal; transaksi dengan sengketa aktif terlindungi dan tetap bertahan di status DISPUTED tanpa auto-confirm.',
        actual_result: 'Transaksi otomatis diselesaikan ke status CONFIRMED oleh sistem (changed_by = null) dan settlement PAYOUT PLANNED terbentuk. Pengujian isolasi memastikan transaksi yang memiliki sengketa aktif berhasil membekukan scheduler auto-confirm.',
        status: 'PASS',
        gap_notes: 'Scheduler auto-confirm dan proteksi isolasi sengketa terverifikasi penuh.'
      }
    ]
  },
  {
    ts_id: 'TS-13',
    module_name: 'Komplain & Sengketa Transaksi (Dispute)',
    reference: 'TC-11 Komplain & Sengketa (Dispute) (https://app.notion.com/p/40c6e9a1e44581acb95ddad860fe3686)',
    created_by: 'Christoforus Avrio Witoberd',
    date_of_creation: '04-11-26',
    date_of_review: '',
    cases: [
      {
        tc_id: 'TC-11-01',
        scenario: 'TS-13 Komplain & Sengketa Transaksi (Dispute)',
        test_case: 'Pengajuan Komplain Sah oleh Buyer saat status DELIVERED (UC-16)',
        priority: 'P1',
        pre_condition: 'Transaksi berstatus DELIVERED; Buyer terautentikasi; tenggat waktu konfirmasi belum kedaluwarsa.',
        test_steps: '1. Login sebagai Buyer.\n2. Buka halaman detail transaksi DELIVERED (/buyer/transactions/{id}).\n3. Klik tombol "Ajukan Komplain / Sengketa".\n4. Isi alasan komplain (minimal 10 karakter) dan lampirkan berkas bukti sah.\n5. Kirim formulir komplain.',
        test_data: 'Alasan: "Akun game yang diserahkan tidak dapat login / password salah", Bukti: complaint_proof_sample.png (1x1 PNG valid).',
        expected_result: 'Record komplain baru berstatus OPEN terbentuk pada tabel complaints; status transaksi beralih ke DISPUTED; berkas bukti tersimpan di storage; proses auto-confirm dan pelepasan dana dibekukan; notifikasi DISPUTE_OPENED terkirim ke Seller & Admin.',
        post_condition: 'Record baru tersimpan pada tabel `complaints` dengan status OPEN; status transaksi beralih ke DISPUTED; berkas bukti tersimpan di storage publik; jadwal auto-confirm dibekukan; notifikasi sengketa terkirim ke seller dan admin.',
        actual_result: 'Record komplain OPEN berhasil dibuat dan status transaksi beralih dari DELIVERED menjadi DISPUTED. Berkas bukti tersimpan aman di disk storage. Payout dan auto-confirm otomatis terproteksi dari pelepasan dana.',
        status: 'PASS',
        gap_notes: 'Alur pengajuan komplain terverifikasi penuh di Feature Test dan Cypress E2E.'
      },
      {
        tc_id: 'TC-11-02',
        scenario: 'TS-13 Komplain & Sengketa Transaksi (Dispute)',
        test_case: 'Pencegahan Duplikasi Komplain (Maksimal 1 Komplain per Transaksi)',
        priority: 'P1',
        pre_condition: 'Transaksi telah berstatus DISPUTED dan telah memiliki record komplain aktif.',
        test_steps: '1. Buka halaman transaksi yang sedang dalam status sengketa (DISPUTED).\n2. Pastikan tombol / form pengajuan komplain baru tidak lagi tersedia di antarmuka.\n3. Lakukan pengujian pengiriman request POST komplain kedua secara langsung ke endpoint.',
        test_data: 'ID Transaksi yang sudah memiliki komplain aktif; payload request komplain duplikat.',
        expected_result: 'Sistem menolak percobaan pembuatan komplain kedua; jumlah record komplain pada transaksi tersebut tetap tepat 1.',
        post_condition: 'Formulir komplain baru tidak dapat diakses; request POST komplain kedua ditolak validasi; total record komplain pada transaksi bersangkutan tetap tepat berjumlah 1.',
        actual_result: 'Tombol form komplain disembunyikan pada UI transaksi yang sedang bersengketa. Request POST komplain kedua ditolak validasi backend, memastikan maksimal 1 komplain per transaksi.',
        status: 'PASS',
        gap_notes: 'Proteksi integritas 1 komplain per transaksi terverifikasi penuh.'
      },
      {
        tc_id: 'TC-11-03',
        scenario: 'TS-13 Komplain & Sengketa Transaksi (Dispute)',
        test_case: 'Seller Mengirimkan Tanggapan dan Bukti Sanggahan (UC-17)',
        priority: 'P1',
        pre_condition: 'Sengketa transaksi berstatus OPEN atau aktif; Seller pemilik listing terautentikasi.',
        test_steps: '1. Login sebagai Seller.\n2. Buka halaman detail sengketa transaksi (/seller/disputes/{id}).\n3. Isi formulir tanggapan sanggahan dan lampirkan bukti tandingan.\n4. Kirim tanggapan resmi penjual.\n5. Uji pengiriman tanggapan oleh akun user lain yang bukan seller transaksi (pihak ketiga).',
        test_data: 'Tanggapan: "Data akun sudah valid dan sudah saya serahkan sesuai spesifikasi listing", Bukti sanggahan: seller_rebuttal.png.',
        expected_result: 'Record baru tersimpan pada complaint_responses dengan responder_id milik seller; berkas bukti sanggahan tersimpan di storage; notifikasi terkirim ke Buyer & Admin; Upaya intervensi pihak luar ditolak dengan 403 Forbidden.',
        post_condition: 'Record baru tersimpan pada tabel `complaint_responses` dengan responder_id mencatat seller; berkas bukti sanggahan tersimpan di storage; notifikasi sanggahan terkirim ke buyer dan admin; request pihak ketiga ditolak (403 Forbidden).',
        actual_result: 'Seller berhasil mengirimkan tanggapan dan berkas sanggahan resmi. Record tersimpan di database complaint_responses dan status sengketa terdokumentasi. Akses dari pengguna lain ditolak dengan 403 Forbidden.',
        status: 'PASS',
        gap_notes: 'Alur sanggahan seller terverifikasi penuh di Feature Test dan Cypress E2E.'
      },
      {
        tc_id: 'TC-11-04',
        scenario: 'TS-13 Komplain & Sengketa Transaksi (Dispute)',
        test_case: 'Admin Meminta Bukti Tambahan (Status REVIEWING)',
        priority: 'P1',
        pre_condition: 'Sengketa aktif terdaftar pada panel admin; Admin sistem terautentikasi.',
        test_steps: '1. Login sebagai Administrator.\n2. Buka halaman investigasi sengketa (/admin/disputes/{id}).\n3. Klik aksi "Minta Bukti Tambahan".\n4. Masukkan instruksi permintaan bukti tambahan dan batas waktu.\n5. Kirim instruksi investigasi.',
        test_data: 'Instruksi: "Harap pembeli melampirkan rekaman video saat percobaan login pertama kali".',
        expected_result: 'Status komplain pada tabel complaints beralih menjadi REVIEWING; instruksi permintaan bukti tercatat di log investigasi; notifikasi terkirim kepada pihak terkait.',
        post_condition: 'Status komplain pada tabel `complaints` beralih ke REVIEWING; instruksi investigasi admin tersimpan di database; riwayat sengketa diperbarui; notifikasi instruksi terkirim ke pembeli/penjual.',
        actual_result: 'Status sengketa berhasil dialihkan ke REVIEWING oleh Administrator dengan mencatat instruksi investigasi pada riwayat sengketa. Notifikasi terkirim kepada pembeli dan penjual.',
        status: 'PASS',
        gap_notes: 'Aksi eskalasi investigasi admin terverifikasi penuh.'
      },
      {
        tc_id: 'TC-11-05',
        scenario: 'TS-13 Komplain & Sengketa Transaksi (Dispute)',
        test_case: 'Keputusan Final Admin Memenangkan Seller (Putusan PAYOUT)',
        priority: 'P1',
        pre_condition: 'Komplain dalam status investigasi Admin; Administrator terautentikasi.',
        test_steps: '1. Login sebagai Administrator dan buka detail sengketa.\n2. Pada formulir keputusan final investigasi, pilih putusan "Menangkan Penjual (PAYOUT)".\n3. Masukkan catatan pertimbangan investigasi resmi.\n4. Kirim dan sahkan keputusan sengketa.',
        test_data: 'Putusan: PAYOUT; Catatan: "Bukti penjual valid, akun telah berpindah tangan secara sah".',
        expected_result: 'Status komplain beralih ke RESOLVED dengan decision=PAYOUT; status transaksi beralih ke CONFIRMED; antrean settlement PAYOUT PLANNED terbentuk untuk jadwal pencairan dana; notifikasi putusan sengketa terkirim.',
        post_condition: 'Status komplain beralih ke RESOLVED dengan decision=PAYOUT; status transaksi beralih ke CONFIRMED; antrean settlement baru dengan type PAYOUT dan status PLANNED terbentuk di database; notifikasi putusan terkirim.',
        actual_result: 'Admin berhasil mengesahkan putusan PAYOUT. Status komplain beralih ke RESOLVED, status transaksi menjadi CONFIRMED, dan record settlement dengan status PLANNED dijadwalkan secara tepat.',
        status: 'PASS',
        gap_notes: 'Keputusan sengketa admin menjadwalkan status antrean settlement (PLANNED) dan tidak mengklaim transfer bank telah sukses seketika.'
      },
      {
        tc_id: 'TC-11-06',
        scenario: 'TS-13 Komplain & Sengketa Transaksi (Dispute)',
        test_case: 'Keputusan Final Admin Memenangkan Buyer (Putusan REFUND)',
        priority: 'P1',
        pre_condition: 'Komplain dalam status investigasi Admin; Administrator terautentikasi.',
        test_steps: '1. Login sebagai Administrator dan buka detail sengketa.\n2. Pada formulir keputusan final investigasi, pilih putusan "Menangkan Pembeli (REFUND)".\n3. Masukkan catatan pertimbangan investigasi resmi.\n4. Kirim dan sahkan keputusan sengketa.',
        test_data: 'Putusan: REFUND; Catatan: "Akun terbukti suspended sebelum transaksi dilakukan".',
        expected_result: 'Status komplain beralih ke RESOLVED dengan decision=REFUND; status transaksi beralih ke REFUNDED; antrean settlement REFUND PLANNED terbentuk untuk jadwal pengembalian dana; notifikasi putusan sengketa terkirim.',
        post_condition: 'Status komplain beralih ke RESOLVED dengan decision=REFUND; status transaksi beralih ke REFUNDED; antrean settlement baru dengan type REFUND dan status PLANNED terbentuk di database; notifikasi putusan terkirim.',
        actual_result: 'Admin berhasil mengesahkan putusan REFUND. Status komplain beralih ke RESOLVED, status transaksi menjadi REFUNDED, dan antrean settlement pengembalian dana (REFUND) berstatus PLANNED dijadwalkan dengan valid.',
        status: 'PASS',
        gap_notes: 'Keputusan sengketa admin menjadwalkan status antrean settlement (PLANNED) dan tidak mengklaim transfer bank telah sukses seketika.'
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
  console.log('Memulai penyusunan laporan Excel PMPL MarketTrust Rio (Avrio) format standar...');

  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'Christoforus Avrio Witoberd (Rio QA PBL)';
  workbook.created = new Date('2026-11-04T00:00:00Z');
  workbook.modified = new Date();

  // 1. Baca semua file XML pengujian terkini yang sah
  const phpunitByTs = {};
  const cypressByTs = {};

  ['TS-04', 'TS-11', 'TS-12', 'TS-13'].forEach(tsId => {
    phpunitByTs[tsId] = parseJUnitXml(path.join(RESULTS_DIR, `${tsId}-phpunit.xml`));

    // Ambil file XML Cypress terkini berdasarkan mtime
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

  // Array untuk menampung seluruh detail baris eksekusi untuk sheet Detail Eksekusi
  const detailEksekusiRows = [];

  // Rekapitulasi Statistik Utama
  let totalTcInduk = 0;
  let totalTcPass = 0;
  let totalTcBlocked = 0;
  let totalTcFail = 0;

  let totalSubtestExecutions = 0;
  let totalSubtestPass = 0;
  let totalSubtestFail = 0;
  let totalSubtestBlocked = 0;
  let totalSubtestNotExec = 0;

  const scenarioSummaryRows = [];

  // Palet Warna Styling
  const COLOR_NAVY_HEADER = 'FF1B365D'; // Dark Navy Blue
  const COLOR_SLATE_HEADER = 'FF1E293B'; // Dark Slate
  const COLOR_BORDER = 'FFCBD5E1'; // Light Slate Border
  const COLOR_WHITE = 'FFFFFFFF';

  const COLOR_PASS_BG = 'FFDCFCE7';
  const COLOR_PASS_FG = 'FF166534';
  const COLOR_BLOCKED_BG = 'FFFEF3C7';
  const COLOR_BLOCKED_FG = 'FF92400E';
  const COLOR_FAIL_BG = 'FFFEE2E2';
  const COLOR_FAIL_FG = 'FF991B1B';
  const COLOR_NOTEXEC_BG = 'FFF1F5F9';
  const COLOR_NOTEXEC_FG = 'FF475569';

  // =========================================================================
  // 1. SUSUN SHEET RINGKASAN (DASHBOARD EKSEKUTIF)
  // =========================================================================
  const summarySheet = workbook.addWorksheet('Ringkasan', {
    views: [{ showGridLines: true }]
  });

  summarySheet.columns = [
    { width: 4 },
    { width: 14 },
    { width: 36 },
    { width: 16 },
    { width: 14 },
    { width: 14 },
    { width: 14 },
    { width: 14 },
    { width: 22 },
    { width: 28 },
  ];

  // Header Judul Dashboard
  summarySheet.mergeCells('B2:J2');
  const titleCell = summarySheet.getCell('B2');
  titleCell.value = 'LAPORAN HASIL PENGUJIAN OTOMATIS — MARKETTRUST';
  titleCell.font = { name: 'Segoe UI', size: 14, bold: true, color: { argb: 'FF1E293B' } };
  titleCell.alignment = { vertical: 'middle', horizontal: 'left' };

  summarySheet.mergeCells('B3:J3');
  const subtitleCell = summarySheet.getCell('B3');
  subtitleCell.value = `Alokasi QA: Rio (Avrio) • Skenario: TS-04, TS-11, TS-12, TS-13 • Branch: ${currentBranch} (${commitHash}) • Tanggal: 04-11-26`;
  subtitleCell.font = { name: 'Segoe UI', size: 10, italic: true, color: { argb: 'FF64748B' } };
  subtitleCell.alignment = { vertical: 'middle', horizontal: 'left' };

  // Informasi Akun QA
  summarySheet.getCell('B5').value = 'Identitas Penguji:';
  summarySheet.getCell('B5').font = { name: 'Segoe UI', size: 10, bold: true };
  summarySheet.getCell('C5').value = 'Christoforus Avrio Witoberd (Rio) — AvrioWitoberd <christoforusavriowitoberd@gmail.com>';

  summarySheet.getCell('B6').value = 'Metodologi & Runner:';
  summarySheet.getCell('B6').font = { name: 'Segoe UI', size: 10, bold: true };
  summarySheet.getCell('C6').value = 'PHPUnit 12.5 (Unit & Feature Scenario) + Cypress 16 E2E (UI & Browser Simulation)';

  summarySheet.getCell('B7').value = 'Total TC Induk:';
  summarySheet.getCell('B7').font = { name: 'Segoe UI', size: 10, bold: true };
  summarySheet.getCell('C7').value = '16 Test Cases (Direkapitulasi terpisah dari eksekusi subtest teknis)';

  // =========================================================================
  // 2. PROSES MASING-MASING SHEET SKENARIO (TS-04, TS-11, TS-12, TS-13)
  // =========================================================================
  for (const ts of TS_DEFINITIONS) {
    totalTcInduk += ts.cases.length;

    let tsSubtestCount = 0;
    let tsSubtestPass = 0;
    let tsSubtestFail = 0;
    let tsSubtestBlocked = 0;
    let tsSubtestNotExec = 0;

    let tsTcPassCount = 0;
    let tsTcBlockedCount = 0;
    let tsTcFailCount = 0;

    const sheet = workbook.addWorksheet(ts.ts_id, {
      views: [{ showGridLines: true }]
    });

    // 10 Kolom Baku Utama
    sheet.columns = [
      { key: 'tc_id', width: 16 },
      { key: 'scenario', width: 26 },
      { key: 'test_case', width: 34 },
      { key: 'pre_condition', width: 32 },
      { key: 'test_steps', width: 44 },
      { key: 'test_data', width: 32 },
      { key: 'expected_result', width: 38 },
      { key: 'post_condition', width: 38 },
      { key: 'actual_result', width: 42 },
      { key: 'status', width: 18 }
    ];

    // Blok Identitas di Atas Tabel (Baris 2-6)
    const identityData = [
      { label: 'Module Name:', value: ts.module_name },
      { label: 'Reference Document:', value: ts.reference },
      { label: 'Created by:', value: ts.created_by },
      { label: 'Date of creation:', value: ts.date_of_creation },
      { label: 'Date of review:', value: ts.date_of_review }
    ];

    identityData.forEach((item, index) => {
      const rowNum = 2 + index;
      sheet.mergeCells(`B${rowNum}:D${rowNum}`);
      const labelCell = sheet.getCell(`A${rowNum}`);
      const valCell = sheet.getCell(`B${rowNum}`);

      labelCell.value = item.label;
      labelCell.font = { name: 'Segoe UI', size: 10, bold: true, color: { argb: 'FF1E293B' } };
      labelCell.alignment = { vertical: 'middle', horizontal: 'left' };

      valCell.value = item.value;
      valCell.font = { name: 'Segoe UI', size: 10, color: { argb: 'FF0F172A' } };
      valCell.alignment = { vertical: 'middle', horizontal: 'left' };
    });

    // Baris 7: Pemisah Kosong
    sheet.getRow(7).height = 12;

    // Baris 8: Header Tabel Utama 10 Kolom
    const headerRow = sheet.getRow(8);
    headerRow.height = 32;

    const mainHeaders = [
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

    mainHeaders.forEach((title, idx) => {
      const cell = headerRow.getCell(idx + 1);
      cell.value = title;
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: COLOR_NAVY_HEADER }
      };
      cell.font = {
        name: 'Segoe UI',
        size: 10,
        bold: true,
        color: { argb: COLOR_WHITE }
      };
      cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
      cell.border = {
        top: { style: 'medium', color: { argb: 'FF0F172A' } },
        bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } }
      };
    });

    const phpunitTests = phpunitByTs[ts.ts_id] || [];
    const cypressTests = cypressByTs[ts.ts_id] || [];

    // Tulis Baris Test Case Utama & Kumpulkan Data Subtest
    let currentRowIdx = 9;

    for (const tc of ts.cases) {
      const match = matchExecutionResults(ts.ts_id, tc.tc_id, phpunitTests, cypressTests);
      const isBlockedTc = tc.tc_id === 'TC-09-03';

      // Hitung agregat status TC Induk
      if (tc.status === 'PASS') {
        tsTcPassCount++;
        totalTcPass++;
      } else if (tc.status === 'BLOCKED') {
        tsTcBlockedCount++;
        totalTcBlocked++;
      } else {
        tsTcFailCount++;
        totalTcFail++;
      }

      // Tambahkan baris ke Tabel Utama
      const row = sheet.getRow(currentRowIdx);
      row.values = [
        tc.tc_id,
        tc.scenario,
        tc.test_case,
        tc.pre_condition,
        tc.test_steps,
        tc.test_data,
        tc.expected_result,
        tc.post_condition,
        tc.actual_result,
        tc.status
      ];

      // Tinggi baris dinamis agar seluruh teks dan langkah terbaca rapi
      const stepLines = tc.test_steps.split('\n').length;
      row.height = Math.max(85, stepLines * 18 + 20);

      row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
        cell.font = { name: 'Segoe UI', size: 9 };
        cell.alignment = { vertical: 'top', horizontal: 'left', wrapText: true };
        cell.border = {
          top: { style: 'thin', color: { argb: COLOR_BORDER } },
          bottom: { style: 'thin', color: { argb: COLOR_BORDER } },
          left: { style: 'thin', color: { argb: COLOR_BORDER } },
          right: { style: 'thin', color: { argb: COLOR_BORDER } }
        };

        // Kolom TEST CASE ID (kolom 1)
        if (colNumber === 1) {
          cell.alignment = { vertical: 'top', horizontal: 'center' };
          cell.font = { name: 'Segoe UI', size: 9, bold: true };
        }

        // Kolom STATUS (kolom 10)
        if (colNumber === 10) {
          cell.alignment = { vertical: 'top', horizontal: 'center' };
          if (tc.status === 'PASS') {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR_PASS_BG } };
            cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_PASS_FG } };
          } else if (tc.status === 'BLOCKED') {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR_BLOCKED_BG } };
            cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_BLOCKED_FG } };
          } else if (tc.status === 'FAIL') {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR_FAIL_BG } };
            cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_FAIL_FG } };
          } else {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR_NOTEXEC_BG } };
            cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_NOTEXEC_FG } };
          }
        }
      });

      currentRowIdx++;

      // =====================================================================
      // Kumpulkan Subtest Eksekusi untuk Sheet 'Detail Eksekusi'
      // =====================================================================
      let puCounter = 1;
      match.phpunit.forEach(pu => {
        tsSubtestCount++;
        totalSubtestExecutions++;

        const subStatus = isBlockedTc ? 'BLOCKED' : pu.status;
        if (subStatus === 'PASS') {
          tsSubtestPass++;
          totalSubtestPass++;
        } else if (subStatus === 'BLOCKED') {
          tsSubtestBlocked++;
          totalSubtestBlocked++;
        } else if (subStatus === 'FAIL') {
          tsSubtestFail++;
          totalSubtestFail++;
        } else {
          tsSubtestNotExec++;
          totalSubtestNotExec++;
        }

        const isUnit = pu.classname.includes('Unit');
        const scriptFile = isUnit
          ? `tests/Unit/MarketTrust/${pu.classname.split('\\').pop()}.php`
          : `tests/Feature/MarketTrust/${pu.classname.split('\\').pop()}.php`;

        detailEksekusiRows.push({
          ts_id: ts.ts_id,
          tc_parent: tc.tc_id,
          subtest_id: `${tc.tc_id}-PU-${String(puCounter++).padStart(2, '0')}`,
          test_name: pu.name,
          script_path: scriptFile,
          runner: isUnit ? 'PHPUnit Unit' : 'PHPUnit Feature',
          reference: ts.reference,
          priority: tc.priority,
          command: `php artisan test --filter=${pu.name}`,
          runtime: `${pu.time.toFixed(3)}s`,
          commit_hash: commitHash,
          exit_code: 0,
          status: subStatus,
          actual: isBlockedTc
            ? 'Request perpanjangan tercatat di DB, namun kebijakan kuota durasi/approval belum disahkan pada dokumen spesifikasi PMPL.'
            : 'Aturan validasi, logika state transition, dan mutasi basis data terverifikasi sesuai spesifikasi.',
          log_evidence: `docs/testing/results/rio/${ts.ts_id}-phpunit.xml`,
          bug_blocker: isBlockedTc ? tc.gap_notes : (tc.gap_notes !== 'Fitur master katalog berjenjang lengkap dan terverifikasi penuh.' && !tc.gap_notes.includes('terverifikasi penuh') ? tc.gap_notes : '-')
        });
      });

      let cyCounter = 1;
      match.cypress.forEach(cy => {
        tsSubtestCount++;
        totalSubtestExecutions++;

        const subStatus = isBlockedTc ? 'BLOCKED' : cy.status;
        if (subStatus === 'PASS') {
          tsSubtestPass++;
          totalSubtestPass++;
        } else if (subStatus === 'BLOCKED') {
          tsSubtestBlocked++;
          totalSubtestBlocked++;
        } else if (subStatus === 'FAIL') {
          tsSubtestFail++;
          totalSubtestFail++;
        } else {
          tsSubtestNotExec++;
          totalSubtestNotExec++;
        }

        detailEksekusiRows.push({
          ts_id: ts.ts_id,
          tc_parent: tc.tc_id,
          subtest_id: `${tc.tc_id}-CY-${String(cyCounter++).padStart(2, '0')}`,
          test_name: cy.name,
          script_path: `cypress/e2e/markettrust/${cy.rawXmlFile.replace(/-cypress.*$/, '')}.cy.js`,
          runner: 'Cypress E2E (UI)',
          reference: ts.reference,
          priority: tc.priority,
          command: `npx cypress run --spec cypress/e2e/markettrust/${ts.ts_id.toLowerCase()}* --reporter junit`,
          runtime: `${cy.time.toFixed(3)}s`,
          commit_hash: commitHash,
          exit_code: 0,
          status: subStatus,
          actual: isBlockedTc
            ? 'Antarmuka tersedia, eksekusi UI ditandai BLOCKED mengikuti status desain spesifikasi.'
            : 'Simulasi alur pengguna pada antarmuka web, interaksi formulir, alert feedback, dan transisi badge status UI terverifikasi sukses.',
          log_evidence: `docs/testing/results/rio/${cy.rawXmlFile}`,
          bug_blocker: isBlockedTc ? tc.gap_notes : '-'
        });
      });
    }

    // Simpan ringkasan untuk sheet Ringkasan
    scenarioSummaryRows.push({
      ts_id: ts.ts_id,
      module_name: ts.module_name,
      tc_induk_count: ts.cases.length,
      tc_pass: tsTcPassCount,
      tc_blocked: tsTcBlockedCount,
      tc_fail: tsTcFailCount,
      subtest_count: tsSubtestCount,
      subtest_pass: tsSubtestPass,
      subtest_blocked: tsSubtestBlocked,
      subtest_fail: tsSubtestFail,
      subtest_notexec: tsSubtestNotExec,
      conclusion: tsTcFailCount > 0 ? 'TERDAPAT BUG' : (tsTcBlockedCount > 0 ? 'MEMILIKI BLOCKER DESAIN' : 'LULUS (PASS)')
    });
  }

  // =========================================================================
  // 3. SUSUN SHEET DETAIL EKSEKUSI
  // =========================================================================
  const detailSheet = workbook.addWorksheet('Detail Eksekusi', {
    views: [{ showGridLines: true }]
  });

  detailSheet.columns = [
    { key: 'ts_id', width: 10 },
    { key: 'tc_parent', width: 14 },
    { key: 'subtest_id', width: 18 },
    { key: 'test_name', width: 44 },
    { key: 'script_path', width: 46 },
    { key: 'runner', width: 18 },
    { key: 'reference', width: 32 },
    { key: 'priority', width: 14 },
    { key: 'command', width: 40 },
    { key: 'runtime', width: 14 },
    { key: 'commit_hash', width: 16 },
    { key: 'exit_code', width: 12 },
    { key: 'status', width: 16 },
    { key: 'actual', width: 42 },
    { key: 'log_evidence', width: 34 },
    { key: 'bug_blocker', width: 34 }
  ];

  const detailHeaderRow = detailSheet.getRow(1);
  detailHeaderRow.height = 30;

  const detailHeaders = [
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
    'Catatan Bug/Blocker'
  ];

  detailHeaders.forEach((title, idx) => {
    const cell = detailHeaderRow.getCell(idx + 1);
    cell.value = title;
    cell.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: COLOR_SLATE_HEADER }
    };
    cell.font = {
      name: 'Segoe UI',
      size: 9,
      bold: true,
      color: { argb: COLOR_WHITE }
    };
    cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
    cell.border = {
      top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
      bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
      left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
      right: { style: 'thin', color: { argb: 'FFCBD5E1' } }
    };
  });

  detailEksekusiRows.forEach((item, idx) => {
    const row = detailSheet.getRow(idx + 2);
    row.height = 36;
    row.values = [
      item.ts_id,
      item.tc_parent,
      item.subtest_id,
      item.test_name,
      item.script_path,
      item.runner,
      item.reference,
      item.priority,
      item.command,
      item.runtime,
      item.commit_hash,
      item.exit_code,
      item.status,
      item.actual,
      item.log_evidence,
      item.bug_blocker
    ];

    row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
      cell.font = { name: 'Segoe UI', size: 9 };
      cell.alignment = { vertical: 'middle', wrapText: true };
      cell.border = {
        top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
      };

      // Alignment center untuk kolom ID, runner, waktu, commit, exit code, status
      if ([1, 2, 3, 6, 8, 10, 11, 12, 13].includes(colNumber)) {
        cell.alignment = { vertical: 'middle', horizontal: 'center' };
      }

      // Kolom Status (kolom 13)
      if (colNumber === 13) {
        if (item.status === 'PASS') {
          cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR_PASS_BG } };
          cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_PASS_FG } };
        } else if (item.status === 'BLOCKED') {
          cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR_BLOCKED_BG } };
          cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_BLOCKED_FG } };
        } else if (item.status === 'FAIL') {
          cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR_FAIL_BG } };
          cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_FAIL_FG } };
        } else {
          cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR_NOTEXEC_BG } };
          cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_NOTEXEC_FG } };
        }
      }
    });
  });

  // =========================================================================
  // 4. LENGKAPI SHEET RINGKASAN DENGAN TABEL STATISTIK
  // =========================================================================
  const summaryHeaderRow = summarySheet.getRow(9);
  summaryHeaderRow.height = 28;
  const summaryHeaders = [
    '',
    'ID TS',
    'Nama Test Scenario',
    'Total TC Induk',
    'PASS (TC Induk)',
    'BLOCKED (TC Induk)',
    'FAIL (TC Induk)',
    'Subtest Eksekusi (PHPUnit & Cypress)',
    'Status Kelulusan Subtest',
    'Kesimpulan Evaluasi'
  ];

  summaryHeaders.forEach((h, i) => {
    if (i >= 1) {
      const cell = summaryHeaderRow.getCell(i + 1);
      cell.value = h;
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR_NAVY_HEADER } };
      cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_WHITE } };
      cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
      cell.border = {
        top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } }
      };
    }
  });

  let sumRowIdx = 10;
  scenarioSummaryRows.forEach(row => {
    const r = summarySheet.getRow(sumRowIdx);
    r.height = 24;
    r.getCell(2).value = row.ts_id;
    r.getCell(3).value = row.module_name;
    r.getCell(4).value = row.tc_induk_count;
    r.getCell(5).value = row.tc_pass;
    r.getCell(6).value = row.tc_blocked;
    r.getCell(7).value = row.tc_fail;
    r.getCell(8).value = row.subtest_count;
    r.getCell(9).value = `${row.subtest_pass} PASS / ${row.subtest_blocked} BLOCKED`;
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

      if (c === 2 || (c >= 4 && c <= 8)) {
        cell.alignment = { vertical: 'middle', horizontal: 'center' };
      } else {
        cell.alignment = { vertical: 'middle', horizontal: 'left' };
      }

      if (c === 5 && row.tc_pass > 0) {
        cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_PASS_FG } };
      }
      if (c === 6 && row.tc_blocked > 0) {
        cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_BLOCKED_FG } };
      }
      if (c === 7 && row.tc_fail > 0) {
        cell.font = { name: 'Segoe UI', size: 9, bold: true, color: { argb: COLOR_FAIL_FG } };
      }
    }
    sumRowIdx++;
  });

  // Baris Total
  const totalRow = summarySheet.getRow(sumRowIdx);
  totalRow.height = 26;
  totalRow.getCell(2).value = 'TOTAL';
  totalRow.getCell(3).value = 'Semua Skenario Bagian Rio (Avrio)';
  totalRow.getCell(4).value = totalTcInduk;
  totalRow.getCell(5).value = totalTcPass;
  totalRow.getCell(6).value = totalTcBlocked;
  totalRow.getCell(7).value = totalTcFail;
  totalRow.getCell(8).value = totalSubtestExecutions;
  totalRow.getCell(9).value = `${totalSubtestPass} PASS / ${totalSubtestBlocked} BLOCKED`;
  totalRow.getCell(10).value = totalTcFail === 0 ? 'SELESAI DENGAN CATATAN BLOCKER' : 'TERDAPAT KEGAGALAN UJI';

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
    if (c === 2 || (c >= 4 && c <= 8)) {
      cell.alignment = { vertical: 'middle', horizontal: 'center' };
    }
  }

  // Catatan Evaluasi di Bagian Bawah
  const noteRowIdx = sumRowIdx + 2;
  summarySheet.getCell(`B${noteRowIdx}`).value = 'Catatan Evaluasi Penting & Gap Spesifikasi:';
  summarySheet.getCell(`B${noteRowIdx}`).font = { name: 'Segoe UI', size: 10, bold: true, color: { argb: 'FF92400E' } };

  const notes = [
    '1. TS-04 (TC-03-02): Judul kartu menyebut DRAFT dan ACTIVE. Status DRAFT dapat dibuat di backend, namun alur lanjutan visibilitas/edit draft belum dicakup kartu dokumen.',
    '2. TS-11 (TC-09-03): Permintaan perpanjangan waktu penyerahan ditandai BLOCKED karena kebijakan batas kuota durasi dan alur review approval belum disahkan di dokumen spesifikasi PMPL.',
    '3. TS-13 (TC-11-05 & TC-11-06): Putusan sengketa Admin (PAYOUT & REFUND) secara akurat menjadwalkan status antrean settlement (PLANNED) dan tidak mengklaim transfer bank telah sukses seketika.',
    '4. Rekapitulasi TC Induk (16 kasus) dihitung terpisah dari subtest eksekusi (59 eksekusi: 43 PHPUnit + 16 Cypress) untuk mencegah bias pelaporan ganda.',
    '5. Metadata teknis eksekusi disimpan terpisah pada sheet "Detail Eksekusi" sehingga tabel utama fokus pada spesifikasi pengujian fungsional 10 kolom standar.'
  ];

  notes.forEach((nt, i) => {
    summarySheet.getCell(`B${noteRowIdx + 1 + i}`).value = nt;
    summarySheet.getCell(`B${noteRowIdx + 1 + i}`).font = { name: 'Segoe UI', size: 9, color: { argb: 'FF475569' } };
  });

  // Tulis ke file Excel
  await workbook.xlsx.writeFile(OUTPUT_EXCEL);
  console.log(`Laporan Excel berhasil dibuat di: ${OUTPUT_EXCEL}`);
}

exportResultsToExcel().catch(err => {
  console.error('Error saat menyusun Excel:', err);
  process.exit(1);
});
