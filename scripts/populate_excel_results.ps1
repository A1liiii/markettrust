Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$source = "docs/testing/MarketTrust_Test_Case_35_Scenario.xlsx"
$dest = "docs/testing/MarketTrust_Test_Case_35_Scenario_Executed.xlsx"
Copy-Item $source $dest -Force

# Hasil Aktual Pengujian Otomatis Cypress
$resultsMap = @{
    "TC-01-01" = @{ Status = "Pass"; Actual = "Akun USER ACTIVE berhasil dibuat dengan hash password aman, dialihkan ke / dan terautentikasi otomatis." };
    "TC-01-02" = @{ Status = "Pass"; Actual = "Pendaftaran dengan email dan username duplikat ditolak dengan pesan validasi 'telah digunakan'." };
    "TC-01-03" = @{ Status = "Pass"; Actual = "Form registrasi memvalidasi konfirmasi password tidak cocok dan panjang minimal < 8 karakter." };
    "TC-01-04" = @{ Status = "Pass"; Actual = "Username dengan panjang 2 karakter dan > 30 karakter ditolak; panjang tepat 3 karakter diterima." };
    "TC-01-05" = @{ Status = "Pass"; Actual = "Format username divalidasi regex [a-z0-9_]; huruf kapital otomatis dinormalisasi menjadi huruf kecil." };
    
    "TC-02-01" = @{ Status = "Pass"; Actual = "Login dengan kredensial email dan username berhasil membuat sesi dan dialihkan ke home/dashboard." };
    "TC-02-02" = @{ Status = "Pass"; Actual = "Login ditolak dengan kredensial salah, email belum terdaftar, maupun username belum terdaftar." };
    "TC-02-04" = @{ Status = "Pass"; Actual = "Tombol login Google mengarahkan pengguna ke alur otentikasi Google OAuth." };
    "TC-02-07" = @{ Status = "Pass"; Actual = "Callback OAuth yang tidak valid atau dibatalkan ditangani dengan baik dan menampilkan pesan peringatan." };
    "TC-02-09" = @{ Status = "Pass"; Actual = "Pengguna dapat logout, sesi dihapus, dan rute terlindungi dialihkan kembali ke halaman login." };
    
    "TC-03-01" = @{ Status = "Pass"; Actual = "Pengguna dan tamu dapat menjelajahi katalog listing aktif marketplace, melihat filter, dan kartu produk." };
    "TC-03-04" = @{ Status = "Pass"; Actual = "Akses ke detail listing dengan ID tidak valid atau tidak ditemukan mengembalikan halaman 404 Not Found." };
    "TC-03-05" = @{ Status = "Pass"; Actual = "Pengguna yang belum menjadi penjual diarahkan ke halaman pendaftaran penjual saat klik 'Ingin Berjualan'." };
    "TC-03-06" = @{ Status = "Pass"; Actual = "Navigasi kelola listing tidak ditampilkan untuk pengguna non-penjual / tamu." };
    
    "TC-04-01" = @{ Status = "Pass"; Actual = "Penjual terdaftar dapat membuat listing produk baru dan tersimpan dengan status ACTIVE." };
    "TC-04-02" = @{ Status = "Pass"; Actual = "Penjual dapat memperbarui judul, deskripsi, dan harga pada listing miliknya." };
    "TC-04-03" = @{ Status = "Pass"; Actual = "Penjual dapat menutup listing (status berubah menjadi CLOSED) dan status diperbarui di tampilan." };
    "TC-04-04" = @{ Status = "Pass"; Actual = "Form listing menolak input kosong pada kolom wajib dan menolak harga/stok negatif." };
    "TC-04-05" = @{ Status = "Pass"; Actual = "Penjual lain ditolak mengakses halaman edit listing milik orang lain dengan respon 403 Forbidden." };
    
    "TC-06-01" = @{ Status = "Pass"; Actual = "Pengguna dapat memfilter thread forum berdasarkan kategori GENERAL, WTB, dan WTS." };
    "TC-06-02" = @{ Status = "Pass"; Actual = "Pencarian thread dengan kata kunci acak menampilkan status pencarian kosong tanpa crash." };
    "TC-06-03" = @{ Status = "Pass"; Actual = "Pengguna terautentikasi dapat melihat detail posting forum dan berinteraksi dengan tombol like dan komentar." };
    "TC-06-05" = @{ Status = "Pass"; Actual = "Tombol like dan form komentar tersedia pada posting aktif bagi pengguna terdaftar." };
    "TC-06-07" = @{ Status = "Pass"; Actual = "Tamu yang mencoba membuat posting forum dialihkan ke halaman login." };
    
    "TC-07-01" = @{ Status = "Pass"; Actual = "Pengguna terdaftar dapat membuat postingan forum kategori umum dan berhasil diterbitkan." };
    "TC-07-02" = @{ Status = "Pass"; Actual = "Penulis dapat mengedit judul postingan dan menutup postingan via modal konfirmasi." };
    "TC-07-05" = @{ Status = "Pass"; Actual = "Formulir posting menolak pengiriman jika kolom wajib tidak diisi." };
    
    "TC-08-01" = @{ Status = "Pass"; Actual = "Pembeli dapat memulai percakapan negosiasi dari posting forum dan terdaftar di inbox percakapan." };
    "TC-08-02" = @{ Status = "Pass"; Actual = "Pengguna dapat mengirim pesan teks di thread percakapan dan pesan muncul pada riwayat chat." };
    "TC-08-03" = @{ Status = "Pass"; Actual = "Form chat menolak pengiriman pesan jika teks kosong tanpa membuat bubble pesan baru." };
    "TC-08-04" = @{ Status = "Pass"; Actual = "Pengguna pihak ketiga ditolak mengakses thread percakapan milik orang lain (403 Forbidden)." };
    "TC-08-06" = @{ Status = "Pass"; Actual = "Widget chat mengambang (floating drawer) dapat dibuka dan ditutup menggunakan tombol X." };
    
    "TC-32-01" = @{ Status = "Pass"; Actual = "Pengguna dapat mengubah username publik yang diizinkan dan tersimpan secara persisten di profil." };
    "TC-32-02" = @{ Status = "Pass"; Actual = "Perubahan username ditolak jika mencoba menggunakan username yang telah dimiliki pengguna lain." };
    "TC-32-03" = @{ Status = "Pass"; Actual = "Username dengan karakter simbol terlarang ditolak oleh validasi regex dan HTML5 constraint." };
    "TC-32-04" = @{ Status = "Pass"; Actual = "Password lama tetap berlaku dan tidak berubah ketika kolom password opsional dikosongkan saat update profil." };
    "TC-32-05" = @{ Status = "Pass"; Actual = "Perubahan password ditolak dengan pesan validasi ketika konfirmasi password baru tidak cocok." };
    "TC-32-07" = @{ Status = "Pass"; Actual = "Tamu (guest) tanpa sesi login ditolak membuka halaman edit profil dan dialihkan ke login." };
    
    "TC-33-01" = @{ Status = "Pass"; Actual = "Hak akses admin diverifikasi: Tamu dialihkan ke login, User biasa ditolak 403, Administrator diizinkan masuk." };
    "TC-33-02" = @{ Status = "Pass"; Actual = "Dashboard admin menampilkan kartu ringkasan status transaksi, escrow, dan antrean tindakan admin." };
    "TC-33-03" = @{ Status = "Pass"; Actual = "Filter periode analitik (7d, 30d, 90d, all) dapat dipilih secara interaktif dan mengubah dataset visualisasi." };
    "TC-33-04" = @{ Status = "Pass"; Actual = "Endpoint analitik admin (/admin/analytics) mengembalikan struktur payload JSON metrik lengkap." }
}

$zip = [System.IO.Compression.ZipFile]::Open($dest, [System.IO.Compression.ZipArchiveMode]::Update)

# 1. Baca rels mapping untuk sheet target
$relsEntry = $zip.GetEntry('xl/_rels/workbook.xml.rels')
$relsStream = $relsEntry.Open()
$relsReader = New-Object System.IO.StreamReader($relsStream)
$relsXml = [xml]$relsReader.ReadToEnd()
$relsReader.Close()
$relsStream.Close()

$sheetMap = @{}
foreach ($rel in $relsXml.Relationships.Relationship) {
    $sheetMap[$rel.Id] = $rel.Target
}

# 2. Baca workbook.xml untuk daftar sheet
$wbEntry = $zip.GetEntry('xl/workbook.xml')
$wbStream = $wbEntry.Open()
$wbReader = New-Object System.IO.StreamReader($wbStream)
$wbXml = [xml]$wbReader.ReadToEnd()
$wbReader.Close()
$wbStream.Close()

$wbNsMgr = New-Object System.Xml.XmlNamespaceManager($wbXml.NameTable)
$wbNsMgr.AddNamespace("x", $wbXml.DocumentElement.NamespaceURI)
$sheetNodes = $wbXml.SelectNodes("//x:sheet", $wbNsMgr)

Write-Host "Updating sheets for $($sheetNodes.Count) scenarios..."
$updatedCount = 0

foreach ($sheet in $sheetNodes) {
    $sheetName = $sheet.GetAttribute("name")
    $sheetRId = $sheet.GetAttribute("id", "http://schemas.openxmlformats.org/officeDocument/2006/relationships")
    $targetPath = $sheetMap[$sheetRId].TrimStart('/')
    if (!$targetPath.StartsWith('xl/')) {
        $targetPath = 'xl/' + $targetPath
    }
    
    $wsEntry = $zip.GetEntry($targetPath)
    if (!$wsEntry) { continue }
    
    $wsStream = $wsEntry.Open()
    $wsReader = New-Object System.IO.StreamReader($wsStream)
    $wsContent = $wsReader.ReadToEnd()
    $wsReader.Close()
    $wsStream.Close()
    
    [xml]$wsXml = $wsContent
    $ns = New-Object System.Xml.XmlNamespaceManager($wsXml.NameTable)
    $ns.AddNamespace("x", $wsXml.DocumentElement.NamespaceURI)
    
    $rowNodes = $wsXml.SelectNodes("//x:row", $ns)
    $sheetModified = $false
    
    foreach ($row in $rowNodes) {
        $rowNum = $row.GetAttribute("r")
        $cANode = $row.SelectSingleNode("x:c[starts-with(@r, 'A')]", $ns)
        if (!$cANode) { continue }
        
        $tcIdVal = ""
        $vNode = $cANode.SelectSingleNode("x:v", $ns)
        if ($vNode) {
            $tcIdVal = $vNode.InnerText.Trim()
        }
        
        if ($resultsMap.ContainsKey($tcIdVal)) {
            $res = $resultsMap[$tcIdVal]
            $sheetModified = $true
            $updatedCount++
            
            # Kolom I: Actual Result
            $cINode = $row.SelectSingleNode("x:c[@r='I$rowNum']", $ns)
            if (!$cINode) {
                $cINode = $wsXml.CreateElement("x:c", $wsXml.DocumentElement.NamespaceURI)
                $cINode.SetAttribute("r", "I$rowNum")
                $cINode.SetAttribute("s", "24")
                $row.AppendChild($cINode)
            }
            $cINode.SetAttribute("t", "str")
            $vI = $cINode.SelectSingleNode("x:v", $ns)
            if (!$vI) {
                $vI = $wsXml.CreateElement("x:v", $wsXml.DocumentElement.NamespaceURI)
                $cINode.AppendChild($vI)
            }
            $vI.InnerText = $res.Actual
            
            # Kolom J: Status
            $cJNode = $row.SelectSingleNode("x:c[@r='J$rowNum']", $ns)
            if (!$cJNode) {
                $cJNode = $wsXml.CreateElement("x:c", $wsXml.DocumentElement.NamespaceURI)
                $cJNode.SetAttribute("r", "J$rowNum")
                $cJNode.SetAttribute("s", "25")
                $row.AppendChild($cJNode)
            }
            $cJNode.SetAttribute("t", "str")
            $vJ = $cJNode.SelectSingleNode("x:v", $ns)
            if (!$vJ) {
                $vJ = $wsXml.CreateElement("x:v", $wsXml.DocumentElement.NamespaceURI)
                $cJNode.AppendChild($vJ)
            }
            $vJ.InnerText = $res.Status
        }
    }
    
    if ($sheetModified) {
        # Tulis balik XML ke zip entry
        $wsEntry.Delete()
        $newEntry = $zip.CreateEntry($targetPath, [System.IO.Compression.CompressionLevel]::Optimal)
        $newStream = $newEntry.Open()
        $newWriter = New-Object System.IO.StreamWriter($newStream, [System.Text.Encoding]::UTF8)
        $wsXml.Save($newWriter)
        $newWriter.Close()
        $newStream.Close()
        Write-Host "Updated sheet: $sheetName"
    }
}

$zip.Dispose()
Write-Host "Excel results successfully updated! Total test cases updated: $updatedCount"
Write-Host "Output saved to: $dest"
