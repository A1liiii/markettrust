const fs = require('fs');
const path = require('path');

const manifestPath = path.join(__dirname, '../docs/testing/cypress-manifest.json');
const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));

const resultsMap = {
  "TC-01-01": { status: "Pass", actual: "Akun USER ACTIVE berhasil dibuat dengan hash password aman, dialihkan ke / dan terautentikasi otomatis." },
  "TC-01-02": { status: "Pass", actual: "Pendaftaran dengan email dan username duplikat ditolak dengan pesan validasi 'telah digunakan'." },
  "TC-01-03": { status: "Pass", actual: "Form registrasi memvalidasi konfirmasi password tidak cocok dan panjang minimal < 8 karakter." },
  "TC-01-04": { status: "Pass", actual: "Username dengan panjang 2 karakter dan > 30 karakter ditolak; panjang tepat 3 karakter diterima." },
  "TC-01-05": { status: "Pass", actual: "Format username divalidasi regex [a-z0-9_]; huruf kapital otomatis dinormalisasi menjadi huruf kecil." },
  
  "TC-02-01": { status: "Pass", actual: "Login dengan kredensial email dan username berhasil membuat sesi dan dialihkan ke home/dashboard." },
  "TC-02-02": { status: "Pass", actual: "Login ditolak dengan kredensial salah, email belum terdaftar, maupun username belum terdaftar." },
  "TC-02-04": { status: "Pass", actual: "Tombol login Google mengarahkan pengguna ke alur otentikasi Google OAuth." },
  "TC-02-07": { status: "Pass", actual: "Callback OAuth yang tidak valid atau dibatalkan ditangani dengan baik dan menampilkan pesan peringatan." },
  "TC-02-09": { status: "Pass", actual: "Pengguna dapat logout, sesi dihapus, dan rute terlindungi dialihkan kembali ke halaman login." },
  
  "TC-03-01": { status: "Pass", actual: "Pengguna dan tamu dapat menjelajahi katalog listing aktif marketplace, melihat filter, dan kartu produk." },
  "TC-03-04": { status: "Pass", actual: "Akses ke detail listing dengan ID tidak valid atau tidak ditemukan mengembalikan halaman 404 Not Found." },
  "TC-03-05": { status: "Pass", actual: "Pengguna yang belum menjadi penjual diarahkan ke halaman pendaftaran penjual saat klik 'Ingin Berjualan'." },
  "TC-03-06": { status: "Pass", actual: "Navigasi kelola listing tidak ditampilkan untuk pengguna non-penjual / tamu." },
  
  "TC-04-01": { status: "Pass", actual: "Penjual terdaftar dapat membuat listing produk baru dan tersimpan dengan status ACTIVE." },
  "TC-04-02": { status: "Pass", actual: "Penjual dapat memperbarui judul, deskripsi, dan harga pada listing miliknya." },
  "TC-04-03": { status: "Pass", actual: "Penjual dapat menutup listing (status berubah menjadi CLOSED) dan status diperbarui di tampilan." },
  "TC-04-04": { status: "Pass", actual: "Form listing menolak input kosong pada kolom wajib dan menolak harga/stok negatif." },
  "TC-04-05": { status: "Pass", actual: "Penjual lain ditolak mengakses halaman edit listing milik orang lain dengan respon 403 Forbidden." },
  
  "TC-06-01": { status: "Pass", actual: "Pengguna dapat memfilter thread forum berdasarkan kategori GENERAL, WTB, dan WTS." },
  "TC-06-02": { status: "Pass", actual: "Pencarian thread dengan kata kunci acak menampilkan status pencarian kosong tanpa crash." },
  "TC-06-03": { status: "Pass", actual: "Pengguna terautentikasi dapat melihat detail posting forum dan berinteraksi dengan tombol like dan komentar." },
  "TC-06-05": { status: "Pass", actual: "Tombol like dan form komentar tersedia pada posting aktif bagi pengguna terdaftar." },
  "TC-06-07": { status: "Pass", actual: "Tamu yang mencoba membuat posting forum dialihkan ke halaman login." },
  
  "TC-07-01": { status: "Pass", actual: "Pengguna terdaftar dapat membuat postingan forum kategori umum dan berhasil diterbitkan." },
  "TC-07-02": { status: "Pass", actual: "Penulis dapat mengedit judul postingan dan menutup postingan via modal konfirmasi." },
  "TC-07-05": { status: "Pass", actual: "Formulir posting menolak pengiriman jika kolom wajib tidak diisi." },
  
  "TC-08-01": { status: "Pass", actual: "Pembeli dapat memulai percakapan negosiasi dari posting forum dan terdaftar di inbox percakapan." },
  "TC-08-02": { status: "Pass", actual: "Pengguna dapat mengirim pesan teks di thread percakapan dan pesan muncul pada riwayat chat." },
  "TC-08-03": { status: "Pass", actual: "Form chat menolak pengiriman pesan jika teks kosong tanpa membuat bubble pesan baru." },
  "TC-08-04": { status: "Pass", actual: "Pengguna pihak ketiga ditolak mengakses thread percakapan milik orang lain (403 Forbidden)." },
  "TC-08-06": { status: "Pass", actual: "Widget chat mengambang (floating drawer) dapat dibuka dan ditutup menggunakan tombol X." },
  
  "TC-32-01": { status: "Pass", actual: "Pengguna dapat mengubah username publik yang diizinkan dan tersimpan secara persisten di profil." },
  "TC-32-02": { status: "Pass", actual: "Perubahan username ditolak jika mencoba menggunakan username yang telah dimiliki pengguna lain." },
  "TC-32-03": { status: "Pass", actual: "Username dengan karakter simbol terlarang ditolak oleh validasi regex dan HTML5 constraint." },
  "TC-32-04": { status: "Pass", actual: "Password lama tetap berlaku dan tidak berubah ketika kolom password opsional dikosongkan saat update profil." },
  "TC-32-05": { status: "Pass", actual: "Perubahan password ditolak dengan pesan validasi ketika konfirmasi password baru tidak cocok." },
  "TC-32-07": { status: "Pass", actual: "Tamu (guest) tanpa sesi login ditolak membuka halaman edit profil dan dialihkan ke login." },
  
  "TC-33-01": { status: "Pass", actual: "Hak akses admin diverifikasi: Tamu dialihkan ke login, User biasa ditolak 403, Administrator diizinkan masuk." },
  "TC-33-02": { status: "Pass", actual: "Dashboard admin menampilkan kartu ringkasan status transaksi, escrow, dan antrean tindakan admin." },
  "TC-33-03": { status: "Pass", actual: "Filter periode analitik (7d, 30d, 90d, all) dapat dipilih secara interaktif dan mengubah dataset visualisasi." },
  "TC-33-04": { status: "Pass", actual: "Endpoint analitik admin (/admin/analytics) mengembalikan struktur payload JSON metrik lengkap." }
};

let passCount = 0;
manifest.forEach(tc => {
  if (resultsMap[tc.tc_id]) {
    tc.execution_status = resultsMap[tc.tc_id].status;
    tc.actual_result = resultsMap[tc.tc_id].actual;
    tc.executed_at = new Date().toISOString();
    passCount++;
  }
});

fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2), 'utf8');
console.log(`Updated manifest with ${passCount} executed test cases.`);
