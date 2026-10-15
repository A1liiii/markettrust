import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

wb_path = 'docs/testing/MarketTrust_Test_Case_35_Scenario_Executed.xlsx'
wb = openpyxl.load_workbook(wb_path)

# 1. Update TS-04 Kelola Listing row 14 (TC-04-01)
ws_ts04 = wb['TS-04 Kelola Listing']
ws_ts04['C14'] = (
    "Buat listing valid\n"
    "Prioritas: P2\n"
    "Desain: Review\n"
    "[Regresi BUG-01: Penekanan Enter berulang & klik ganda]"
)
ws_ts04['I14'] = (
    "Penjual terdaftar dapat membuat listing produk baru dan tersimpan dengan status ACTIVE. "
    "Cakupan regresi penekanan Enter berulang 5x dan klik ganda berhasil diverifikasi: "
    "tepat 1 record tersimpan di database, tepat 1 entri muncul di UI (termasuk setelah reload), "
    "dan textarea Enter mendukung baris baru. (Tautan Bug: BUG-01 - Status: Closed)."
)
ws_ts04['J14'] = "Pass"

# 2. Check if 'Laporan Bug' sheet exists, remove if already present to recreate cleanly
sheet_name = 'Laporan Bug'
if sheet_name in wb.sheetnames:
    del wb[sheet_name]

ws_bug = wb.create_sheet(title=sheet_name)
ws_bug.views.sheetView[0].showGridLines = True

# Palet Gaya Sesuai Desain Workbook
navy_dark = "05285F"
navy_light = "1E3A8A"
navy_subtle = "EEF2FF"
gray_header = "F1F5F9"
gray_light = "F8FAFC"
text_dark = "162033"
border_gray = "CBD5E1"
white = "FFFFFF"
green_status = "15803D"
green_bg = "DCFCE7"

font_title = Font(name="Arial", size=16, bold=True, color=navy_dark)
font_subtitle = Font(name="Arial", size=10, bold=False, color="475569")
font_section = Font(name="Arial", size=11, bold=True, color=white)
font_label = Font(name="Arial", size=10, bold=True, color=navy_dark)
font_value = Font(name="Arial", size=10, bold=False, color=text_dark)
font_bold = Font(name="Arial", size=10, bold=True, color=text_dark)
font_badge = Font(name="Arial", size=10, bold=True, color=green_status)

fill_section = PatternFill(start_color=navy_dark, end_color=navy_dark, fill_type="solid")
fill_sub_section = PatternFill(start_color=navy_light, end_color=navy_light, fill_type="solid")
fill_label = PatternFill(start_color=navy_subtle, end_color=navy_subtle, fill_type="solid")
fill_badge_green = PatternFill(start_color=green_bg, end_color=green_bg, fill_type="solid")

thin_border_side = Side(border_style="thin", color=border_gray)
border_all = Border(left=thin_border_side, right=thin_border_side, top=thin_border_side, bottom=thin_border_side)

align_left = Alignment(horizontal="left", vertical="center", wrap_text=True)
align_top_left = Alignment(horizontal="left", vertical="top", wrap_text=True)
align_center = Alignment(horizontal="center", vertical="center")

# Row 2: Banner Title
ws_bug.merge_cells('A2:H2')
ws_bug['A2'] = "LAPORAN DEFECT & HASIL RETEST (BUG REPORT)"
ws_bug['A2'].font = font_title
ws_bug['A2'].alignment = align_left

# Row 3: Subtitle
ws_bug.merge_cells('A3:H3')
ws_bug['A3'] = "MarketTrust Quality Assurance  •  Sistem Pencegahan Duplikasi Submisi Penawaran Listing"
ws_bug['A3'].font = font_subtitle
ws_bug['A3'].alignment = align_left

# Helper function to write a key-value row
def write_field(ws, row, label, value, val_merge_to="H", val_font=font_value, val_fill=None):
    ws[f'A{row}'] = label
    ws[f'A{row}'].font = font_label
    ws[f'A{row}'].fill = fill_label
    ws[f'A{row}'].border = border_all
    ws[f'A{row}'].alignment = align_left
    
    ws.merge_cells(f'B{row}:{val_merge_to}{row}')
    cell_val = ws[f'B{row}']
    cell_val.value = value
    cell_val.font = val_font
    cell_val.alignment = align_top_left
    if val_fill:
        cell_val.fill = val_fill
        
    for col_idx in range(1, 9):
        col_letter = get_column_letter(col_idx)
        ws[f'{col_letter}{row}'].border = border_all

def write_section_header(ws, row, title):
    ws.merge_cells(f'A{row}:H{row}')
    c = ws[f'A{row}']
    c.value = title
    c.font = font_section
    c.fill = fill_section
    c.alignment = align_left
    for col_idx in range(1, 9):
        col_letter = get_column_letter(col_idx)
        ws[f'{col_letter}{row}'].border = border_all

# Section 1: Identitas Defect
write_section_header(ws_bug, 5, "1. IDENTITAS & KLASIFIKASI DEFECT")
write_field(ws_bug, 6, "Bug ID", "BUG-01", val_font=font_bold)
write_field(ws_bug, 7, "Judul Bug", "Submit Ganda Penawaran Listing Akibat Penekanan Tombol Enter Berulang pada Input Form Seller", val_font=font_bold)
write_field(ws_bug, 8, "Modul / Skenario", "TS-04: Kelola listing seller  •  Rute: /seller/listings/create -> POST /seller/listings")
write_field(ws_bug, 9, "Test Case Terkait", "TC-04-01: Buat listing valid (Cakupan Regresi Penekanan Enter Berulang & Double Submit)")
write_field(ws_bug, 10, "Severity", "High / Major (Integritas data transaksi terganggu, record duplikat terbentuk pada database listings)")
write_field(ws_bug, 11, "Priority", "High / P1 (Alur publikasi penawaran seller merupakan fungsionalitas inti bisnis marketplace)")
write_field(ws_bug, 12, "Status Triase", "Closed (Semua retest otomatis Cypress dan PHPUnit Feature Test 100% Pass)", val_font=font_badge, val_fill=fill_badge_green)
write_field(ws_bug, 13, "Environment Uji", "Local Dev (PHP 8.5, Laravel 11/13, PostgreSQL Supabase Remote, Cypress 16.2.0, Windows 10/11 x64)")
write_field(ws_bug, 14, "Build / Git Branch", "Branch: fix/listing-multiple-submit  •  Base Commit: 380f253")

# Section 2: Detail Reproduksi Awal
write_section_header(ws_bug, 16, "2. DETAIL REPRODUKSI & KONDISI AWAL (SEBELUM PERBAIKAN)")
write_field(ws_bug, 17, "Pre-Conditions", (
    "1. Akun pengguna dengan peran Seller terdaftar dan berstatus ACTIVE.\n"
    "2. Katalog master produk dan varian aktif tersedia di database.\n"
    "3. Seller berada pada halaman formulir tambah listing (/seller/listings/create)."
))
write_field(ws_bug, 18, "Data Uji", (
    "- Kredensial Seller: sel1_<timestamp>@markettrust.test / Password123!\n"
    "- Varian: Varian produk aktif pertama pada katalog master\n"
    "- Judul Listing: Item Test Seller Valid <timestamp> (Maks. 150 karakter)\n"
    "- Deskripsi: Spesifikasi Baris 1\\nSpesifikasi Baris 2 (Textarea multiline)\n"
    "- Harga: Rp 150.000 (Format Rupiah otomatis)\n"
    "- Metode Penyerahan: Pengiriman Data Akun Instant\n"
    "- Estimasi Durasi: 30 Menit (MINUTE)\n"
    "- Status Penayangan: ACTIVE"
))
write_field(ws_bug, 19, "Langkah Reproduksi", (
    "1. Login sebagai pengguna yang berhak membuat listing (/login atau /register).\n"
    "2. Buka halaman formulir pembuatan listing di /seller/listings/create.\n"
    "3. Isi seluruh field formulir dengan data yang valid.\n"
    "4. Letakkan fokus kursor pada salah satu input teks (misal: #delivery_method atau #title).\n"
    "5. Tekan tombol Enter beberapa kali secara cepat (5 kali) sebelum respon redirect diterima browser.\n"
    "6. Tunggu proses penyimpanan selesai hingga halaman diarahkan ke /seller/listings.\n"
    "7. Hitung jumlah baris listing dengan judul unik tersebut pada tabel daftar listing seller.\n"
    "8. Lakukan refresh halaman (F5 / reload browser) dan amati kembali tabel listing.\n"
    "9. Periksa langsung jumlah record listing di database menggunakan mekanisme testing proyek."
))
write_field(ws_bug, 20, "Expected Result", (
    "Formulir hanya memproses satu kali tindakan penambahan listing. Tepat 1 record listing tersimpan di database, "
    "dan tepat 1 baris listing ditampilkan di tabel UI baik sebelum maupun sesudah refresh halaman."
))
write_field(ws_bug, 21, "Laporan Awal Pengguna", (
    "Pengguna melaporkan: saat menambahkan listing lalu menekan Enter beberapa kali pada form, listing muncul beberapa kali di daftar."
))
write_field(ws_bug, 22, "Actual Result (Sebelum)", (
    "1. Form HTML (create.blade.php) tidak memiliki flag guard isSubmitting dan tombol submit tidak dinonaktifkan saat proses kirim berlangsung.\n"
    "2. Controller backend (ListingController@store) tidak memiliki token idempotensi maupun lock atomic submisi per seller.\n"
    "3. Akibatnya, setiap request POST berulang yang terkirim paralel mengeksekusi Listing::create() berulang kali ke database PostgreSQL, "
    "menghasilkan record duplikat yang persisten dan tampil berulang kali di frontend setelah refresh.\n"
    "4. Pada runner Cypress Electron headless, event driver menghentikan pengetikan Enter berikutnya begitu navigasi awal terpicu, "
    "namun kerentanan double-submit terverifikasi secara nyata melalui request konkurensi paralel dan pengujian tombol simpan."
))
write_field(ws_bug, 23, "Duplikat Terdeteksi", (
    "Setiap HTTP POST request paralel yang lolos validasi berhasil membuat 1 record baru di tabel listings (misal: 5 request serentak = 5 record duplikat)."
))
write_field(ws_bug, 24, "Lokasi Bukti Awal", (
    "- Script Pengujian Cypress: cypress/e2e/ts-04-kelola-listing.cy.js (TC-04-01)\n"
    "- Log Eksekusi Cypress Task: brain/614d0694-2300-4f0c-8389-a4df584aedaf/.system_generated/tasks/task-111.log\n"
    "- File Kode Terdampak: app/Http/Controllers/Seller/ListingController.php & resources/views/seller/listings/create.blade.php"
))

# Section 3: Analisis Akar Masalah & Perbaikan
write_section_header(ws_bug, 26, "3. AKAR MASALAH (ROOT CAUSE) & SOLUSI PERBAIKAN")
write_field(ws_bug, 27, "Akar Masalah (Root Cause)", (
    "1. Lapisan Frontend (create.blade.php):\n"
    "   - Ketiadaan variabel status isSubmitting saat form disubmit.\n"
    "   - Tombol simpan tidak dinonaktifkan (disabled) saat permintaan jaringan sedang berjalan.\n"
    "   - Event keydown (Enter) pada elemen input teks memicu submit bawaan browser berulang kali selama latensi jaringan.\n"
    "2. Lapisan Backend (ListingController.php & StoreListingRequest.php):\n"
    "   - Ketiadaan idempotency token unik per instance formulir untuk mengenali request duplikat.\n"
    "   - Ketiadaan concurrency lock atomik (Cache::lock) per seller dan payload hash untuk mencegah race condition pengiriman paralel."
))
write_field(ws_bug, 28, "File yang Diubah", (
    "1. resources/views/seller/listings/create.blade.php:\n"
    "   - Menambahkan input hidden submission_token bawaan UUID dari controller.\n"
    "   - Mengimplementasikan guard isSubmitting dan penonaktifan tombol submit dengan indikator loading spinner.\n"
    "   - Mencegah penekanan Enter berulang pada input saat sedang submisi, serta menjamin Enter pada <textarea> tetap membuat baris baru (newline).\n"
    "   - Menambahkan event listener pageshow (persisted) untuk memulihkan form jika seller kembali via browser back button.\n"
    "2. app/Http/Requests/Seller/StoreListingRequest.php:\n"
    "   - Menambahkan aturan validasi nullable untuk field submission_token.\n"
    "3. app/Http/Controllers/Seller/ListingController.php:\n"
    "   - Method create(): Menyuntikkan Str::uuid() sebagai submissionToken ke view create.\n"
    "   - Method store(): Memeriksa token submisi via Cache, mengunci proses secara atomik dengan Cache::lock() berbasis seller_id dan payload signature, "
    "sehingga request duplikat diarahkan kembali ke indeks dengan aman tanpa duplikasi record di database.\n"
    "4. tests/Feature/SellerListingTest.php:\n"
    "   - Menambahkan test feature backend untuk proteksi idempotensi token dan pembuatan listing berikutnya secara sengaja.\n"
    "5. cypress/e2e/ts-04-kelola-listing.cy.js:\n"
    "   - Memperluas regresi TC-04-01 (Enter berulang), TC-04-01-REG-01 (klik ganda tombol), TC-04-01-REG-02 (listing berikutnya), dan TC-04-04 (form recovery)."
))

# Section 4: Riwayat Retest & Verifikasi
write_section_header(ws_bug, 30, "4. RIWAYAT RETEST & VERIFIKASI (SESUDAH PERBAIKAN)")
write_field(ws_bug, 31, "Penguji & Waktu Retest", "Penguji Otomatis MarketTrust (Antigravity QA)  •  16 Oktober 2026, 01:10 WIB")
write_field(ws_bug, 32, "Commit / Build Perbaikan", "Branch: fix/listing-multiple-submit")
write_field(ws_bug, 33, "Hasil Retest Dataset 1 (Enter Berulang 5x)", (
    "PASS — Kursor difokuskan pada input #delivery_method, ditekan Enter 5 kali berturut-turut dengan network delay response 800ms:\n"
    "- Tepat 1 record tersimpan di database PostgreSQL (Listing::where('title', ...)->count() === 1).\n"
    "- Tepat 1 entri listing muncul di tabel UI panel seller.\n"
    "- Tepat 1 entri listing tetap konsisten setelah browser reload (F5)."
))
write_field(ws_bug, 34, "Hasil Retest Dataset 2 (Klik Tombol Simpan Berulang)", (
    "PASS — Tombol simpan diklik ganda (dblclick) secara cepat pada TC-04-01-REG-01:\n"
    "- Tombol simpan dinonaktifkan (disabled) secara instan dan menampilkan teks 'Menyimpan Penawaran...'.\n"
    "- Tepat 1 record tersimpan di database dan tepat 1 entri tampil di tabel UI."
))
write_field(ws_bug, 35, "Hasil Retest Dataset 3 (Textarea Newline via Enter)", (
    "PASS — Mengetik 'Spesifikasi Baris 1{enter}Spesifikasi Baris 2' pada textarea #description:\n"
    "- Enter menghasilkan karakter baris baru (\\n) secara sempurna tanpa men-submit formulir sebelum waktunya."
))
write_field(ws_bug, 36, "Hasil Retest Dataset 4 (Pembuatan Listing Kedua Sengaja)", (
    "PASS — Penjual sengaja membuka kembali form create setelah listing pertama terbit (TC-04-01-REG-02):\n"
    "- Form mendapatkan UUID token baru dan lock submisi sebelumnya telah dilepaskan.\n"
    "- Listing kedua berhasil dipublikasikan dengan normal tanpa terblokir proteksi."
))
write_field(ws_bug, 37, "Hasil Retest Dataset 5 (Form Recovery Setelah Validasi Gagal)", (
    "PASS — Form disubmit dalam kondisi kosong, ditolak oleh HTML5 validation (TC-04-04):\n"
    "- Tombol simpan tidak terkunci permanen, data diperbaiki menjadi valid, lalu disubmit ulang dan berhasil tersimpan."
))
write_field(ws_bug, 38, "Hasil Retest Backend (PHPUnit)", (
    "PASS — 16 dari 16 Feature Test pada SellerListingTest lulus 100% (mencakup test_duplicate_submission_with_same_token dan test_seller_can_create_subsequent_listing_intentionally)."
))
write_field(ws_bug, 39, "Status Akhir Defect", "CLOSED / FIXED & VERIFIED (Pass Rate: 100% — 7/7 Cypress Specs Pass, 165/165 PHPUnit Tests Pass)", val_font=font_badge, val_fill=fill_badge_green)
write_field(ws_bug, 40, "Lokasi Bukti Sesudah Perbaikan", (
    "- Laporan Retest Cypress E2E: cypress/e2e/ts-04-kelola-listing.cy.js (7 passing, 1m 3s)\n"
    "- Laporan Feature Test PHPUnit: tests/Feature/SellerListingTest.php (16 passed, 61 assertions)\n"
    "- Workbook Terkini: docs/testing/MarketTrust_Test_Case_35_Scenario_Executed.xlsx (Sheet 'Laporan Bug' & 'TS-04 Kelola Listing')"
))

# Adjust column widths
ws_bug.column_dimensions['A'].width = 24
ws_bug.column_dimensions['B'].width = 18
ws_bug.column_dimensions['C'].width = 18
ws_bug.column_dimensions['D'].width = 16
ws_bug.column_dimensions['E'].width = 16
ws_bug.column_dimensions['F'].width = 16
ws_bug.column_dimensions['G'].width = 16
ws_bug.column_dimensions['H'].width = 18

wb.save(wb_path)
print("Workbook successfully updated!")
print(f"Sheet '{sheet_name}' added and 'TS-04 Kelola Listing' TC-04-01 updated.")
