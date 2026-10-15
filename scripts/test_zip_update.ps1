Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$source = "docs/testing/MarketTrust_Test_Case_35_Scenario.xlsx"
$dest = "docs/testing/MarketTrust_Test_Case_35_Scenario_Executed.xlsx"
Copy-Item $source $dest -Force

$mode = [System.IO.Compression.ZipArchiveMode]::Update
$zip = [System.IO.Compression.ZipFile]::Open($dest, $mode)
$entry = $zip.GetEntry('xl/worksheets/sheet1.xml')
$stream = $entry.Open()
$reader = New-Object System.IO.StreamReader($stream)
$content = $reader.ReadToEnd()
$reader.Close()
$stream.Close()

Write-Host "Dest sheet1 length: $($content.Length)"
$zip.Dispose()
Write-Host "Success open in update mode"
