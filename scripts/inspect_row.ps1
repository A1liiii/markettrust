Add-Type -AssemblyName System.IO.Compression.FileSystem
$z = [System.IO.Compression.ZipFile]::OpenRead('docs/testing/MarketTrust_Test_Case_35_Scenario.xlsx')
$e = $z.GetEntry('xl/worksheets/sheet1.xml')
$s = $e.Open()
$r = New-Object System.IO.StreamReader($s)
$x = $r.ReadToEnd()
$r.Close()
$s.Close()
$z.Dispose()

[xml]$doc = $x
$ns = New-Object System.Xml.XmlNamespaceManager($doc.NameTable)
$ns.AddNamespace('x', $doc.DocumentElement.NamespaceURI)
$row14 = $doc.SelectSingleNode('//x:row[@r="14"]', $ns)
Write-Host $row14.OuterXml
