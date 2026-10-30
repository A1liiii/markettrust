# Daftar Fitur yang Belum Diselesaikan, Perlu Dilengkapi, & Perlu Diperbaiki (Berdasarkan SKPL Terbaru)

Dokumen ini merangkum daftar fitur pada repositori **MarketTrust** yang **belum diselesaikan**, **perlu dilengkapi**, dan **perlu diperbaiki** mengacu pada dokumen **SKPL Terbaru (KF-01 s.d. KF-11, KNF-01 s.d. KNF-07, UC-01 s.d. UC-30, Activity Diagram, & ERD)**.

---

## A. Fitur yang Belum Diselesaikan (Belum Ada Alur End-to-End)

### 1. `UC-26` (`KF-01`) — Kelola Rekening Payout Seller
- **Kondisi Saat Ini**:
  - Tabel `seller_payout_accounts` sudah ada pada migrasi [`2026_10_13_021737_add_transaction_workflow_schema.php`](../database/migrations/2026_10_13_021737_add_transaction_workflow_schema.php), tetapi belum ada Model, Controller, Route, dan UI.
- **Yang Harus Diselesaikan**:
  - [ ] Membuat model `App\Models\SellerPayoutAccount`.
  - [ ] Membuat halaman & endpoint pengaturan Rekening Payout Seller untuk menambah dan memperbarui tujuan `BANK`, `EWALLET` (provider, nomor tujuan, nama pemilik), atau `QRIS` (unggah gambar QRIS + nama pemilik).
  - [ ] Menerapkan pemilihan **1 tujuan aktif** per Seller (menonaktifkan tujuan aktif sebelumnya secara atomik) beserta *unique index parsial* sesuai spesifikasi `UC-26`.

---

### 2. `UC-10` & `UC-11` (`KF-03`, `KF-04`) — Pengajuan, Revisi, Persetujuan, & Penolakan Kesepakatan dari Forum/DM
- **Kondisi Saat Ini**:
  - Tabel `agreements`, [`Agreement.php`](../app/Models/Agreement.php), dan [`FeeCalculationService.php`](../app/Services/FeeCalculationService.php) sudah mendukung perhitungan mode `EXCLUDE`/`INCLUDE` untuk sumber `FORUM`, tetapi belum ada Controller, Route, dan UI di halaman percakapan DM ([`resources/views/conversations/show.blade.php`](../resources/views/conversations/show.blade.php)) maupun drawer chat.
- **Yang Harus Diselesaikan**:
  - [ ] **`UC-10` (Ajukan Kesepakatan dari Forum/DM)**: Form & endpoint di dalam percakapan DM untuk mengisi usulan kesepakatan (memilih pihak Buyer & Seller, produk/varian R1/R2, rincian hasil yang dijanjikan, harga dasar, mode fee `EXCLUDE`/`INCLUDE`, dan metode penyerahan), menampilkan pratinjau `buyer_total` & `seller_net`, menyimpan `agreements` berstatus `DRAFT` beserta `revision_no`, dan mengirim notifikasi ke penerima usulan.
  - [ ] **Revisi Usulan (`UC-10` Alternate Flow c)**: Mengubah usulan sebelum terkunci final menaikkan `revision_no` dan membatalkan persetujuan atas revisi lama.
  - [ ] **`UC-11` (Setujui atau Tolak Kesepakatan)**: Tombol & endpoint bagi penerima usulan untuk:
    - **Setuju** atas nomor revisi (`revision_no`) yang sama $\rightarrow$ mengunci kesepakatan menjadi `FINALIZED` dan otomatis membuat 1 record `Transaction` berstatus `WAITING_PAYMENT` (`payment_due_at = finalized_at + 1 jam`).
    - **Tolak** $\rightarrow$ mengisi alasan penolakan, mengubah status menjadi `REJECTED` (`rejected_by`, `rejected_at`, `rejection_reason`) tanpa membuat transaksi pembayaran.

---

### 3. `UC-14` & `UC-14B` (`KF-05`, `KF-10`) — Penyerahan Hasil & Bukti oleh Seller + Peringatan & Tambahan Waktu
- **Kondisi Saat Ini**:
  - Kolom `delivery_note`, `result_link`, `delivered_at`, `confirmation_due_at` pada tabel `transactions` dan relasi `delivery_transaction_id` pada tabel `evidence_files` sudah ada, tetapi belum ada Controller, Route, UI penyerahan, maupun Scheduler.
- **Yang Harus Diselesaikan**:
  - [ ] **`UC-14` (Serahkan Hasil & Bukti oleh Seller)**: Form & endpoint pada halaman detail transaksi ([`resources/views/transactions/show.blade.php`](../resources/views/transactions/show.blade.php)) khusus Seller saat transaksi berstatus `PAID` untuk mengisi catatan penyerahan (`delivery_note`), tautan hasil (`result_link`), dan mengunggah bukti penyerahan resmi (`evidence_files.delivery_transaction_id`, gambar/PDF maks. 5 MB).
  - [ ] Mencatat `delivered_at` satu kali, mengubah status transaksi menjadi `DELIVERED`, menetapkan `confirmation_due_at = delivered_at + 1 jam`, dan mengirim notifikasi ke Buyer.
  - [ ] **`UC-14B` (Scheduler Peringatan & Tambahan Waktu Penyerahan)**: Proses terjadwal yang memeriksa transaksi `PAID` tanpa `delivered_at` yang melewati `delivery_due_at` (1 jam), mengirim peringatan satu kali ke Seller & Admin, serta fitur pencatatan tambahan waktu beserta alasan pada `transaction_deadline_events` (atau menandai terlambat untuk tinjauan Admin jika tambahan waktu berakhir tanpa penyerahan).

---

### 4. `UC-15` & `UC-15B` (`KF-06`, `KF-09`, `KF-10`) — Konfirmasi Penerimaan Buyer & Konfirmasi Otomatis (Auto-Confirm)
- **Kondisi Saat Ini**:
  - Kolom `confirmed_at` dan enum `TransactionStatus::CONFIRMED` sudah ada, tetapi belum ada tombol/endpoint konfirmasi Buyer maupun scheduler auto-confirm.
- **Yang Harus Diselesaikan**:
  - [ ] **`UC-15` (Konfirmasi Penerimaan oleh Buyer)**: Tombol & endpoint **Konfirmasi Penerimaan** bagi Buyer pada transaksi berstatus `DELIVERED` (selama tidak ada komplain aktif), mencatat `confirmed_at`, mengubah status ke `CONFIRMED`, menyiapkan antrean payout manual dengan `payout_due_at = waktu layak + 1 jam`, dan mengirim notifikasi ke Seller & Admin.
  - [ ] **`UC-15B` (Scheduler Auto-Confirm)**:
    - Jika Buyer belum merespons setelah 1 jam sejak `delivered_at` (`confirmation_due_at` terlewati) dan belum ada komplain aktif: kirim notifikasi satu kali ke Buyer dan berikan tambahan waktu **30 menit (*grace deadline*)**.
    - Jika tambahan 30 menit berakhir tanpa respons dan tanpa komplain aktif: kunci status secara atomik menjadi `CONFIRMED` oleh proses sistem (harus dapat dibedakan dari konfirmasi manual Buyer pada riwayat sesuai `KNF-03`), siapkan antrean payout (`payout_due_at = +1 jam`), tanpa transfer otomatis.

---

### 5. `UC-16`, `UC-17`, `UC-17B`, & `UC-18` (`KF-06`, `KF-07`, `KF-09`, `KF-10`) — Alur Komplain Buyer, Tanggapan Seller, & Keputusan Admin
- **Kondisi Saat Ini**:
  - Tabel & model [`Complaint.php`](../app/Models/Complaint.php) serta [`ComplaintResponse.php`](../app/Models/ComplaintResponse.php) sudah ada, tetapi belum ada form Buyer/Seller dan halaman `/admin/disputes` di [`AdminModuleController.php`](../app/Http/Controllers/Admin/AdminModuleController.php) masih *placeholder*.
- **Yang Harus Diselesaikan**:
  - [ ] **`UC-16` (Ajukan Komplain — Buyer)**: Form & endpoint pengajuan komplain pada transaksi `DELIVERED` (dalam jendela 1 jam pemeriksaan atau tambahan 30 menit) dengan alasan & unggahan bukti wajib (`evidence_files.complaint_id`), menyimpan komplain `OPEN` (maks. 1 komplain per transaksi), mengubah status transaksi ke `DISPUTED`, menghentikan auto-confirm & menahan payout, menetapkan tenggat tanggapan Seller 24 jam, dan mengirim notifikasi ke Seller & Admin.
  - [ ] **`UC-17` (Tanggapi Komplain — Seller)**: Form & endpoint bagi Seller terkait untuk mengirim tanggapan (`complaint_responses`) dan bukti pendukung (`evidence_files.response_id`) dalam 24 jam sejak komplain dicatat, lalu memulai tenggat keputusan Admin 24 jam sejak tanggapan sah pertama diterima.
  - [ ] **`UC-17B` (Scheduler Tenggat Komplain)**: Memantau batas 24 jam tanggapan Seller (jika lewat tanpa tanggapan, lanjut pemeriksaan Admin dengan tenggat keputusan 24 jam sejak batas Seller berakhir; diam tidak otomatis memenangkan Buyer) serta memantau tenggat 24 jam keputusan Admin (mengirim notifikasi keterlambatan jika lewat).
  - [ ] **`UC-18` (Tinjau & Putuskan Komplain — Admin)**: Mengganti placeholder `/admin/disputes` dengan halaman peninjauan lengkap (snapshot kesepakatan, status pembayaran, bukti penyerahan, bukti komplain, tanggapan Seller, dan riwayat status); fitur meminta bukti tambahan (`REVIEWING` + alasan perpanjangan & tenggat baru); serta form keputusan final (`PAYOUT` atau `REFUND`) beserta alasan yang menutup komplain menjadi `RESOLVED` dan menyiapkan 1 record `settlements` (diarahkan ke `UC-19` atau `UC-20`).

---

### 6. `UC-27`, `UC-28`, & `UC-28B` (`KF-08`, `KF-09`, `KF-10`) — Pembatalan Transaksi (Pre/Post-Payment) & Kewajiban KEEP Seller
- **Kondisi Saat Ini**:
  - Model `TransactionCancellation` dan `KeepObligation`, `CancellationService`, `TransactionCancellationController`, `AdminCancellationController`, routes pembatalan, modal UI transaksi, serta panel review Admin telah selesai diimplementasikan penuh dan terverifikasi 14/14 test feature lulus.
- **Yang Telah Diselesaikan**:
  - [x] Membuat model `App\Models\TransactionCancellation` dan `App\Models\KeepObligation`.
  - [x] **`UC-27` (Ajukan Pembatalan — Buyer/Seller)**:
    - Hanya diizinkan sebelum penyerahan resmi (`DELIVERED`).
    - **Sebelum Pembayaran (`WAITING_PAYMENT`)**: Rekonsiliasi status gateway terlebih dahulu; jika dipastikan belum bayar, simpan `transaction_cancellations` (`stage = BEFORE_PAYMENT`, `status = APPROVED`), ubah transaksi menjadi `CANCELLED`, tutup akses pembayaran, dan catat pihak pembatal (alasan opsional, tidak otomatis melabeli `PROBLEMATIC`).
    - **Setelah Pembayaran Sebelum Penyerahan (`PAID`)**: Wajib mengisi alasan; simpan `transaction_cancellations` (`stage = AFTER_PAYMENT_BEFORE_DELIVERY`, `status = REQUESTED`) dan kirim ke antrean Admin (`UC-28`).
  - [x] **`UC-28` & `UC-28B` (Tinjau Pembatalan Post-Payment & Hitung KEEP — Admin)**:
    - Halaman/panel Admin untuk memeriksa permintaan `AFTER_PAYMENT_BEFORE_DELIVERY` berstatus `REQUESTED`, menentukan `responsible_party` (`BUYER`, `SELLER`, `PLATFORM`, `UNDETERMINED`) dan `decision_note`.
    - Jika **Ditolak (`REJECTED`)**: transaksi melanjutkan tahap `PAID`.
    - Jika **Disetujui (`APPROVED`)**: siapkan proses refund (`UC-20`) tanpa langsung menandai `REFUNDED`.
    - Jika `responsible_party = SELLER` (`UC-28B`): buat 1 `keep_obligations` berstatus `OPEN` untuk Seller tersebut:
      - Metode pembayaran asal Bank/Virtual Account: `calculation_rule = BANK_VA_FLAT`, `fee_basis_amount = 4000`, `multiplier = 1`, `amount = 4000`, `remaining_amount = 4000`.
      - Metode lainnya: `calculation_rule = OTHER_TRIPLE_GATEWAY_FEE`, `multiplier = 3`, `amount = 3 * gateway_fee_amount` aktual (jika `gateway_fee_amount` belum tersedia, tahan perhitungan KEEP sampai terverifikasi).

---

### 7. `UC-19`, `UC-20`, `UC-29`, & `UC-29B` (`KF-09`, `KF-10`) — Pencatatan Payout & Refund Simulatif, Pemotongan KEEP, & Penanganan Payout Gagal
- **Kondisi Saat Ini**:
  - Tabel `settlements` dan `keep_deductions` sudah ada, tetapi halaman `/admin/transactions` di [`AdminModuleController.php`](../app/Http/Controllers/Admin/AdminModuleController.php) masih *placeholder*.
- **Yang Harus Diselesaikan**:
  - [ ] Membuat model `App\Models\KeepDeduction` dan halaman operasional `/admin/transactions`.
  - [ ] **`UC-19` (Catat Payout Simulatif — Admin)**:
    - Memeriksa kelayakan (status `CONFIRMED` atau keputusan komplain `PAYOUT`, tanpa komplain aktif), mengambil tujuan payout aktif Seller (`UC-26`) dan menyalinnya ke `settlements` (menahan proses jika tujuan Seller belum lengkap).
    - Menampilkan `gross_amount` (dari `seller_net` snapshot) dan daftar `keep_obligations` berstatus `OPEN` milik Seller dari transaksi asal lain.
    - Menghitung potongan KEEP maksimal sebesar `gross_amount` (`amount = gross_amount - keep_deducted_amount`, tidak boleh negatif; jika sisa KEEP melebihi payout, potong sampai Rp0 dan sisa kewajiban dibawa ke payout berikutnya).
    - Admin mengisi referensi, catatan, dan bukti simulasi (`evidence_files.settlement_id`); setelah dipastikan berhasil, simpan `keep_deductions`, kurangi `remaining_amount` pada `keep_obligations` (ubah ke `SETTLED` jika lunas), tandai `settlements` = `COMPLETED` dan `transactions` = `COMPLETED` secara atomik.
  - [ ] **`UC-29` & `UC-29B` (Tangani Payout Gagal & Percobaan Ulang — Admin)**:
    - Mencatat percobaan payout yang gagal atau belum pasti (`FAILED` / `UNKNOWN`) pada `settlement_attempts` (beserta penyebab, referensi, waktu, dan tindak lanjut) tanpa memotong KEEP final atau menandai transaksi selesai, dan tanpa membuat KEEP baru.
    - Alur perbaikan tujuan oleh Seller (`UC-26`), validasi Admin, pengumuman sistem jika gangguan berdampak luas, serta pengulangan payout pada settlement yang sama.
  - [ ] **`UC-20` (Catat Refund Simulatif — Admin)**:
    - Untuk transaksi dengan keputusan `REFUND` (dari komplain `UC-18` atau pembatalan `UC-28`), menampilkan nominal serta tujuan refund Buyer (untuk kesalahan Seller, KEEP menjadi kewajiban Seller dan **tidak memotong refund Buyer**).
    - Admin mengisi metode, referensi, dan bukti refund simulatif $\rightarrow$ menyimpan `settlements` (`settlement_type = REFUND`, `status = COMPLETED`) dan mengubah transaksi menjadi `REFUNDED`.

---

### 8. `UC-24` & `UC-30` (`KF-11`, `KF-10`) — Pelaporan Akun oleh Pengguna & Peninjauan Akun oleh Admin
- **Kondisi Saat Ini**:
  - Tabel & model [`AccountReport.php`](../app/Models/AccountReport.php) serta [`ReputationEvent.php`](../app/Models/ReputationEvent.php) sudah ada, tetapi belum ada form pelaporan pengguna dan halaman `/admin/users` di [`AdminModuleController.php`](../app/Http/Controllers/Admin/AdminModuleController.php) masih *placeholder*.
- **Yang Harus Diselesaikan**:
  - [ ] **`UC-30` (Laporkan Akun — Pengguna Terdaftar)**: Tombol & formulir **Laporkan Akun** pada halaman profil pengguna atau detail transaksi (mengisi alasan & bukti pendukung `evidence_files.report_id`), mencegah duplikasi laporan aktif yang identik, menyimpan `account_reports` (`status = OPEN`, `source = USER`) tanpa memberikan sanksi/pengurangan poin otomatis pada terlapor.
  - [ ] **`UC-24` (Tinjau Akun — Admin)**: Mengganti placeholder `/admin/users` dengan antrean pemeriksaan laporan/kasus akun (menampilkan bukti, snapshot relevan, riwayat pembatalan & KEEP). Admin dapat meminta bukti tambahan (`REVIEWING`), menutup laporan yang tidak terbukti (`DISMISSED` tanpa sanksi), atau mencatat keputusan moderasi (`RESOLVED`, mengubah `reputation_status` / `account_status`, menyimpan `reputation_events` dengan `event_key` unik, dan mencatat ke `audit_logs`).

---

## B. Fitur yang Perlu Dilengkapi (Sudah Ada Sebagian, Belum Lengkap)

### 1. `UC-22` (`KF-10`) — Menu & Halaman Daftar Notifikasi Pengguna
- **Kondisi Saat Ini**:
  - Tabel `notifications`, model [`Notification.php`](../app/Models/Notification.php), dan [`NotificationService.php`](../app/Services/NotificationService.php) sudah ada, tetapi baru dipanggil saat checkout marketplace dan belum memiliki halaman/menu UI.
- **Yang Perlu Dilengkapi**:
  - [ ] Route, Controller, dan menu/ikon Notifikasi di navbar ([`resources/views/layouts/app.blade.php`](../resources/views/layouts/app.blade.php)) bagi Buyer, Seller, dan Admin untuk melihat daftar notifikasi (dibaca/belum dibaca), menandai `read_at` saat dipilih, dan membuka halaman rujukan setelah memeriksa hak akses.
  - [ ] Memasang pemanggilan `NotificationService::notify()` pada seluruh event di `UC-22` langkah 3 (usulan kesepakatan, pembayaran terverifikasi/expired, peringatan penyerahan Seller, tambahan 30 menit Buyer, komplain 24 jam, keputusan komplain, pembatalan, KEEP, dan hasil payout/refund).

---

### 2. `UC-13`, `UC-13B`, & `UC-13C` (`KF-04`, `KF-10`) — Kelengkapan Verifikasi & Rekonsiliasi Midtrans Sandbox
- **Kondisi Saat Ini**:
  - Integrasi Snap Token, Webhook Callback, dan Sync manual di [`MidtransPaymentService.php`](../app/Services/MidtransPaymentService.php) sudah berjalan.
- **Yang Perlu Dilengkapi**:
  - [ ] Menyimpan **`gateway_fee_amount` aktual** pada tabel `payments` saat pembayaran berhasil diverifikasi (`UC-13` langkah 5).
  - [ ] Mengirim notifikasi ke Buyer dan Seller saat pembayaran berubah menjadi `PAID` atau `EXPIRED`/`CANCELLED`.
  - [ ] Menangani **callback berhasil yang datang terlambat** setelah transaksi melewati `payment_due_at` atau sudah `CANCELLED` (`UC-13B` / `Alternate Flow f`): menahan kelanjutan otomatis ke tahap penyerahan dan menandainya untuk rekonsiliasi Admin.
  - [ ] Scheduler otomatis (`UC-13C`) untuk mengubah transaksi `WAITING_PAYMENT` yang telah melewati `payment_due_at` (1 jam) menjadi `CANCELLED` dan payment menjadi `EXPIRED` setelah rekonsiliasi ke gateway memastikan belum ada pembayaran sah.

---

### 3. `UC-21` & `KF-10` — Kelengkapan Halaman Detail Riwayat Transaksi & Audit Log Admin
- **Kondisi Saat Ini**:
  - Halaman daftar & detail transaksi pengguna ([`resources/views/transactions/show.blade.php`](../resources/views/transactions/show.blade.php)) sudah menampilkan stepper, snapshot kesepakatan, dan `TransactionStatusHistory`, tetapi halaman `/admin/audit-logs` di [`AdminModuleController.php`](../app/Http/Controllers/Admin/AdminModuleController.php) masih *placeholder*.
- **Yang Perlu Dilengkapi**:
  - [ ] Menampilkan bagian terpisah pada detail transaksi (`UC-21` langkah 3 & 4) untuk: bukti penyerahan & `result_link`, status konfirmasi manual vs otomatis, detail komplain & tanggapan, rincian settlement, perpanjangan tenggat, alasan & pihak bertanggung jawab atas pembatalan, serta rincian KEEP asal/potongan/sisa kewajiban sesuai hak akses.
  - [ ] Mengganti placeholder `/admin/audit-logs` dengan tabel daftar jejak audit (`audit_logs`) beserta pencatatan otomatis setiap tindakan penting Admin/sistem.

---

### 4. `UC-23` (`KF-11`) — Detail Riwayat Poin Legitimasi & Catatan Pembatalan/KEEP pada Profil
- **Kondisi Saat Ini**:
  - Halaman profil ([`resources/views/profile/show.blade.php`](../resources/views/profile/show.blade.php)) sudah menampilkan angka `reputation_points` dan badge `UNREVIEWED` / `LEGIT` / `PROBLEMATIC`.
- **Yang Perlu Dilengkapi**:
  - [ ] Menampilkan daftar/riwayat `reputation_events` yang membentuk total poin beserta penjelasan makna indikatornya, serta menampilkan catatan pembatalan/KEEP hanya sesuai kewenangan dan hasil tinjauan tanpa membocorkan bukti privat (`UC-23` langkah 2–4).
  - [ ] Menyediakan route profil publik pengguna lain (saat ini `/profile` hanya menampilkan profil pengguna yang sedang login sendiri) agar Buyer/Seller dapat melihat profil dan poin legitimasi lawan transaksinya.

---

### 5. `UC-05` (`KF-11`) — Informasi Periode & Minimum Sampel pada Statistik Pasar Produk
- **Kondisi Saat Ini**:
  - Widget Informasi Pasar di [`MarketplaceService.php`](../app/Services/MarketplaceService.php) dan [`resources/views/marketplace/show.blade.php`](../resources/views/marketplace/show.blade.php) telah dilengkapi dengan filter periode waktu (7 hari, 30 hari, 90 hari, semua waktu), keterangan eksplisit periode aktif pada antarmuka, fallback guard parameter tidak valid, dan transparansi jumlah sampel transaksi selesai per varian.
- **Yang Telah Dilengkapi**:
  - [x] Menambahkan filter periode waktu perhitungan serta keterangan eksplisit periode yang digunakan pada UI sesuai `UC-05` langkah 3 & 4.

---

## C. Fitur yang Perlu Diperbaiki (Penyesuaian Kode Eksisting terhadap Aturan SKPL)

### 1. Perhitungan `delivery_due_at` saat Pembayaran Terverifikasi (`UC-13` Langkah 4 & `KF-05`)
- **Lokasi File**: [`app/Services/MidtransPaymentService.php`](../app/Services/MidtransPaymentService.php) (baris 227–228)
- **Masalah Saat Ini**:
  - Menggunakan `$agreement->delivery_within_minutes ?? 1440` untuk menghitung `delivery_due_at`.
- **Perbaikan Sesuai SKPL**:
  - [ ] Sesuai `KF-05`, `UC-13` langkah 4, dan Activity Diagram 13B, `delivery_due_at` harus ditetapkan **1 jam (`$now->copy()->addHour()`) sejak waktu verifikasi pembayaran tercatat (`paid_at + 1 jam`)**.

---

### 2. Tampilan Rincian Harga, Mode Fee, Fee Amount, & Seller Net pada Checkout Marketplace (`UC-09` Langkah 2 & `KNF-03`)
- **Lokasi File**: [`resources/views/marketplace/checkout.blade.php`](../resources/views/marketplace/checkout.blade.php) (baris 74–91) dan [`app/Http/Controllers/MarketplaceCheckoutController.php`](../app/Http/Controllers/MarketplaceCheckoutController.php) (baris 47–58)
- **Masalah Saat Ini**:
  - Tabel *"Rincian Pembayaran Transaksi"* di `checkout.blade.php` hanya menampilkan *"Harga Produk: Rp (buyerTotal)"* dan *"Total Pembayaran: Rp (buyerTotal)"* tanpa menampilkan pemisahan `basePrice`, mode fee (`EXCLUDE`), nominal `feeAmount`, dan `sellerNet` (hak penerimaan Seller). Selain itu, variabel `$hasSellerPayout` sudah dihitung di controller tetapi belum divalidasi/ditampilkan peringatannya di UI.
- **Perbaikan Sesuai SKPL**:
  - [ ] Menampilkan baris Harga Dasar (`basePrice`), Mode Fee (`EXCLUDE`), Biaya Layanan Platform (`feeAmount`), Total Pembayaran Buyer (`buyerTotal`), dan Hak Penerimaan Bersih Seller (`sellerNet`) secara transparan sebelum Buyer menyetujui checkout.

---

### 3. Batas Ukuran & Format File Lampiran pada Direct Message (`UC-08` Alternate Flow c & `KNF-04`)
- **Lokasi File**: [`app/Http/Requests/Conversation/SendMessageRequest.php`](../app/Http/Requests/Conversation/SendMessageRequest.php) (baris 27) dan [`resources/views/conversations/show.blade.php`](../resources/views/conversations/show.blade.php) (baris 277)
- **Masalah Saat Ini**:
  - Validasi `attachment` di `SendMessageRequest.php` menggunakan `'max:15360'` (15 MB) dan mengizinkan `'mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png,webp,mp4,webm,zip'`.
- **Perbaikan Sesuai SKPL**:
  - [x] Sesuai `KNF-04` dan `UC-08` Alternate Flow c, lampiran DM dibatasi hanya berupa **gambar (`jpg,jpeg,png,webp`) atau `pdf` dengan ukuran maksimal 5 MB (`max:5120`)**.

---

### 4. Pembedaan Fee Platform, Biaya Gateway Aktual, dan KEEP pada Halaman Detail Kesepakatan (`UC-12` Langkah 2–4)
- **Lokasi File**: [`resources/views/transactions/show.blade.php`](../resources/views/transactions/show.blade.php) (baris 395–433)
- **Masalah Saat Ini**:
  - Tabel finansial di `transactions/show.blade.php` belum menampilkan label mode fee (`EXCLUDE`/`INCLUDE`), belum menampilkan `seller_net` ketika dilihat oleh Buyer, dan belum membedakan informasi biaya gateway aktual (`gateway_fee_amount`) serta kewajiban `KEEP`.
- **Perbaikan Sesuai SKPL**:
  - [ ] Menampilkan mode fee (`EX`/`INC`), `seller_net`, serta informasi terpisah antara fee platform (`fee_amount`), biaya gateway aktual (`gateway_fee_amount`), dan kewajiban `KEEP` sesuai `UC-12` langkah 2–4.
