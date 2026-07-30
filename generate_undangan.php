<?php
/**
 * Generate Undangan Skripsi per Dosen - FIXED VERSION
 * 
 * Reads data from "Data Sidang Skripsi Juli 2026 (4).xlsx" 
 * and generates one .docx file per lecturer using 
 * "Ir. Indra Lina Putra, S.Kom., M.Kom.docx" as the template base.
 * 
 * Output directory: E:\skripsi\output_undangan\
 */

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// ==================== 1. READ EXCEL DATA ====================
$excelFile = 'Data Sidang Skripsi Juli 2026 (4).xlsx';
$spreadsheet = IOFactory::load($excelFile);

// Helper to read a cell, trying calculated value first
function getCellValue($sheet, $cell) {
    $raw = $sheet->getCell($cell)->getValue();
    // If it's a formula, get calculated value
    if (is_string($raw) && strpos($raw, '=') === 0) {
        return trim($sheet->getCell($cell)->getCalculatedValue());
    }
    return trim((string)$raw);
}

// Read Skripsi sheet (Sheet 0)
$sheet0 = $spreadsheet->getSheet(0);
$allData = [];
for ($row = 2; $row <= 29; $row++) {
    $nim = getCellValue($sheet0, 'B' . $row);
    if (empty($nim)) continue;
    
    $hari = getCellValue($sheet0, 'J' . $row);
    $jam = getCellValue($sheet0, 'K' . $row);
    
    // Skip entries that are "Langsung Pemberkasan"
    if (stripos($hari, 'Langsung') !== false || stripos($jam, 'Langsung') !== false) {
        continue;
    }
    
    $allData[] = [
        'nim' => $nim,
        'nama' => getCellValue($sheet0, 'C' . $row),
        'judul' => getCellValue($sheet0, 'D' . $row),
        'dosbing_utama' => getCellValue($sheet0, 'E' . $row),
        'dosbing_pendamping' => getCellValue($sheet0, 'F' . $row),
        'ketua_penguji' => getCellValue($sheet0, 'G' . $row),
        'penguji1' => getCellValue($sheet0, 'H' . $row),
        'penguji2' => getCellValue($sheet0, 'I' . $row),
        'hari' => $hari,
        'jam' => $jam,
        'ruang' => getCellValue($sheet0, 'L' . $row),
    ];
}

// Read Jurnal sheet (Sheet 1)
$sheet1 = $spreadsheet->getSheet(1);
$jurnalData = [];
for ($row = 2; $row <= 20; $row++) {
    $nim = getCellValue($sheet1, 'B' . $row);
    if (empty($nim)) continue;
    
    $hari = getCellValue($sheet1, 'J' . $row);
    $jam = getCellValue($sheet1, 'K' . $row);
    
    if (stripos($hari, 'Langsung') !== false || stripos($jam, 'Langsung') !== false) {
        continue;
    }
    
    $jurnalData[] = [
        'nim' => $nim,
        'nama' => getCellValue($sheet1, 'C' . $row),
        'judul' => getCellValue($sheet1, 'D' . $row),
        'dosbing_utama' => getCellValue($sheet1, 'E' . $row),
        'dosbing_pendamping' => getCellValue($sheet1, 'F' . $row),
        'ketua_penguji' => getCellValue($sheet1, 'G' . $row),
        'penguji1' => getCellValue($sheet1, 'H' . $row),
        'penguji2' => getCellValue($sheet1, 'I' . $row),
        'hari' => $hari,
        'jam' => $jam,
        'ruang' => getCellValue($sheet1, 'L' . $row),
    ];
}

$mergedData = array_merge($allData, $jurnalData);

echo "Total sidang entries (Skripsi): " . count($allData) . "\n";
echo "Total sidang entries (Jurnal): " . count($jurnalData) . "\n";
echo "Total merged: " . count($mergedData) . "\n\n";

// ==================== 2. NORMALIZE DOSEN NAMES ====================
// Map variations to canonical names
$nameAliases = [
    'Dr. Rina Fiati, S.T., M.Cs' => 'Dr. Rina Fiati, ST., M.Cs',
    'Rizkysari Mei Maharani, S.Kom., M.Kom' => 'Rizkysari Meimaharani, S.Kom., M.Kom',
    'Dr. Ahmad Abdul Chamid, S.Kom., M.Kom.' => 'Dr. Ahmad Abdul Chamid,S.Kom., M.Kom',
    'Dr. Ahmad Abdul Chamid, S.Kom., M.Kom' => 'Dr. Ahmad Abdul Chamid,S.Kom., M.Kom',
];

function canonicalName($name) {
    global $nameAliases;
    $name = trim(preg_replace('/\s+/', ' ', $name));
    return $nameAliases[$name] ?? $name;
}

// Normalize all names in data
foreach ($mergedData as &$entry) {
    $entry['ketua_penguji'] = canonicalName($entry['ketua_penguji']);
    $entry['penguji1'] = canonicalName($entry['penguji1']);
    $entry['penguji2'] = canonicalName($entry['penguji2']);
}
unset($entry);

// ==================== 3. GROUP BY DOSEN ====================
$dosenSessions = [];

foreach ($mergedData as $entry) {
    $roles = [
        'ketua_penguji' => $entry['ketua_penguji'],
        'penguji1' => $entry['penguji1'],
        'penguji2' => $entry['penguji2'],
    ];
    
    foreach ($roles as $role => $dosenName) {
        if (empty($dosenName)) continue;
        // Skip formula leftovers
        if (strpos($dosenName, '=') === 0) continue;
        
        if (!isset($dosenSessions[$dosenName])) {
            $dosenSessions[$dosenName] = [];
        }
        
        // Avoid duplicate entries (same student appearing twice for the same dosen)
        $key = $entry['nim'] . '|' . $entry['hari'] . '|' . $entry['jam'];
        $dosenSessions[$dosenName][$key] = $entry;
    }
}

// Convert to indexed arrays
foreach ($dosenSessions as $dosen => &$sessions) {
    $sessions = array_values($sessions);
}
unset($sessions);

echo "Unique lecturers: " . count($dosenSessions) . "\n";
foreach ($dosenSessions as $dosen => $sessions) {
    echo "  - $dosen: " . count($sessions) . " sessions\n";
}
echo "\n";

// ==================== 4. HELPER FUNCTIONS ====================

function sortSessions(&$sessions) {
    usort($sessions, function($a, $b) {
        preg_match('/(\d+)\s+\w+\s+\d+/', $a['hari'], $matchA);
        preg_match('/(\d+)\s+\w+\s+\d+/', $b['hari'], $matchB);
        $dateA = isset($matchA[1]) ? intval($matchA[1]) : 0;
        $dateB = isset($matchB[1]) ? intval($matchB[1]) : 0;
        
        if ($dateA !== $dateB) return $dateA - $dateB;
        
        preg_match('/(\d+)[\.\:](\d+)/', $a['jam'], $timeA);
        preg_match('/(\d+)[\.\:](\d+)/', $b['jam'], $timeB);
        $minutesA = (isset($timeA[1]) ? intval($timeA[1]) * 60 : 0) + (isset($timeA[2]) ? intval($timeA[2]) : 0);
        $minutesB = (isset($timeB[1]) ? intval($timeB[1]) * 60 : 0) + (isset($timeB[2]) ? intval($timeB[2]) : 0);
        
        return $minutesA - $minutesB;
    });
}

function getScheduleRows($sessions) {
    $scheduleMap = [];
    
    foreach ($sessions as $s) {
        $key = $s['hari'] . '|' . $s['ruang'];
        if (!isset($scheduleMap[$key])) {
            $scheduleMap[$key] = [
                'hari' => $s['hari'],
                'ruang' => $s['ruang'],
                'times' => [],
            ];
        }
        preg_match('/(\d+[\.\:]\d+)\s*[-–]\s*(\d+[\.\:]\d+)/', $s['jam'], $m);
        if ($m) {
            $scheduleMap[$key]['times'][] = [
                'start' => str_replace(':', '.', $m[1]),
                'end' => str_replace(':', '.', $m[2]),
            ];
        }
    }
    
    $rows = [];
    foreach ($scheduleMap as $data) {
        if (!empty($data['times'])) {
            $toMinutes = function($t) {
                $parts = explode('.', $t);
                return intval($parts[0]) * 60 + intval($parts[1] ?? 0);
            };
            
            $starts = array_map(function($t) use ($toMinutes) { return $toMinutes($t['start']); }, $data['times']);
            $ends = array_map(function($t) use ($toMinutes) { return $toMinutes($t['end']); }, $data['times']);
            
            $minIdx = array_search(min($starts), $starts);
            $maxIdx = array_search(max($ends), $ends);
            
            $jam = $data['times'][$minIdx]['start'] . ' – ' . $data['times'][$maxIdx]['end'];
        } else {
            $jam = '';
        }
        
        $rows[] = [
            'hari' => $data['hari'],
            'ruang' => $data['ruang'],
            'jam' => $jam,
        ];
    }
    
    usort($rows, function($a, $b) {
        preg_match('/(\d+)/', $a['hari'], $mA);
        preg_match('/(\d+)/', $b['hari'], $mB);
        $dA = intval($mA[1] ?? 0);
        $dB = intval($mB[1] ?? 0);
        if ($dA !== $dB) return $dA - $dB;
        
        // Same date, sort by time
        preg_match('/(\d+)[\.\:](\d+)/', $a['jam'], $tA);
        preg_match('/(\d+)[\.\:](\d+)/', $b['jam'], $tB);
        $minA = (intval($tA[1] ?? 0)) * 60 + intval($tA[2] ?? 0);
        $minB = (intval($tB[1] ?? 0)) * 60 + intval($tB[2] ?? 0);
        return $minA - $minB;
    });
    
    return $rows;
}

function e($str) {
    return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function makeScheduleRowXml($no, $dosenName, $hari, $ruang, $jam) {
    return '<w:tr w:rsidR="00CD19A7" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml">
  <w:tc><w:tcPr><w:tcW w:w="480" w:type="dxa"/></w:tcPr>
    <w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>' . e($no) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="4241" w:type="dxa"/></w:tcPr>
    <w:p><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>' . e($dosenName) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="2445" w:type="dxa"/></w:tcPr>
    <w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>' . e($hari) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="1488" w:type="dxa"/></w:tcPr>
    <w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>' . e($ruang) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="1647" w:type="dxa"/></w:tcPr>
    <w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>' . e($jam) . '</w:t></w:r></w:p></w:tc>
</w:tr>';
}

function makeStudentRowXml($no, $nama, $ketuaPenguji, $penguji1, $penguji2, $hari, $jam, $ruang) {
    return '<w:tr w:rsidR="00CD19A7" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml">
  <w:tc><w:tcPr><w:tcW w:w="480" w:type="dxa"/></w:tcPr>
    <w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr><w:t>' . e($no) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="1800" w:type="dxa"/></w:tcPr>
    <w:p><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr><w:t>' . e($nama) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="2100" w:type="dxa"/></w:tcPr>
    <w:p><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr><w:t>' . e($ketuaPenguji) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="2100" w:type="dxa"/></w:tcPr>
    <w:p><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr><w:t>' . e($penguji1) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="2100" w:type="dxa"/></w:tcPr>
    <w:p><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr><w:t>' . e($penguji2) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="1800" w:type="dxa"/></w:tcPr>
    <w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr><w:t>' . e($hari) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="1400" w:type="dxa"/></w:tcPr>
    <w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr><w:t>' . e($jam) . '</w:t></w:r></w:p></w:tc>
  <w:tc><w:tcPr><w:tcW w:w="900" w:type="dxa"/></w:tcPr>
    <w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr><w:t>' . e($ruang) . '</w:t></w:r></w:p></w:tc>
</w:tr>';
}

// ==================== 5. GENERATE DOCX FILES ====================
$templateFile = 'Ir. Indra Lina Putra, S.Kom., M.Kom.docx';
$outputDir = 'output_undangan';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
} else {
    // Clean old files
    foreach (glob($outputDir . '/*.docx') as $old) {
        unlink($old);
    }
}

// Extract template components
$templateZip = new ZipArchive;
if ($templateZip->open($templateFile) !== TRUE) {
    die("Cannot open template: $templateFile\n");
}

$templateFiles = [];
for ($i = 0; $i < $templateZip->numFiles; $i++) {
    $name = $templateZip->getNameIndex($i);
    $templateFiles[$name] = $templateZip->getFromName($name);
}
$templateZip->close();

$ns = 'xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:cx="http://schemas.microsoft.com/office/drawing/2014/chartex" xmlns:cx1="http://schemas.microsoft.com/office/drawing/2015/9/8/chartex" xmlns:cx2="http://schemas.microsoft.com/office/drawing/2015/10/21/chartex" xmlns:cx3="http://schemas.microsoft.com/office/drawing/2016/5/9/chartex" xmlns:cx4="http://schemas.microsoft.com/office/drawing/2016/5/10/chartex" xmlns:cx5="http://schemas.microsoft.com/office/drawing/2016/5/11/chartex" xmlns:cx6="http://schemas.microsoft.com/office/drawing/2016/5/12/chartex" xmlns:cx7="http://schemas.microsoft.com/office/drawing/2016/5/13/chartex" xmlns:cx8="http://schemas.microsoft.com/office/drawing/2016/5/14/chartex" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:aink="http://schemas.microsoft.com/office/drawing/2016/ink" xmlns:am3d="http://schemas.microsoft.com/office/drawing/2017/model3d" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:oel="http://schemas.microsoft.com/office/2019/extlst" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" xmlns:w16cex="http://schemas.microsoft.com/office/word/2018/wordml/cex" xmlns:w16cid="http://schemas.microsoft.com/office/word/2016/wordml/cid" xmlns:w16="http://schemas.microsoft.com/office/word/2018/wordml" xmlns:w16du="http://schemas.microsoft.com/office/word/2023/wordml/word16du" xmlns:w16sdtdh="http://schemas.microsoft.com/office/word/2020/wordml/sdtdatahash" xmlns:w16se="http://schemas.microsoft.com/office/word/2015/wordml/symex" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 w15 w16se w16cid w16 w16cex w16sdtdh w16du wp14"';

foreach ($dosenSessions as $dosenName => $sessions) {
    sortSessions($sessions);
    
    $scheduleRows = getScheduleRows($sessions);
    $totalMahasiswa = count($sessions);
    
    // Build Table 1 rows
    $table1Xml = '';
    $no = 1;
    foreach ($scheduleRows as $sched) {
        $table1Xml .= makeScheduleRowXml($no, $dosenName, $sched['hari'], $sched['ruang'], $sched['jam']);
        $no++;
    }
    
    // Build Table 2 rows
    $table2Xml = '';
    $no = 1;
    foreach ($sessions as $s) {
        $table2Xml .= makeStudentRowXml(
            $no, $s['nama'], $s['ketua_penguji'], $s['penguji1'], $s['penguji2'],
            $s['hari'], $s['jam'], $s['ruang']
        );
        $no++;
    }
    
    $totalText = e("Total Jumlah Uji : $totalMahasiswa Mahasiswa");
    
    $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document ' . $ns . '>
  <w:body>
    <w:p w:rsidR="00D265FF" w:rsidRDefault="00D265FF" w:rsidP="0087261F">
      <w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:bCs/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:eastAsia="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:bCs/><w:noProof/></w:rPr>
        <w:drawing>
          <wp:anchor distT="114300" distB="114300" distL="114300" distR="43200" simplePos="0" relativeHeight="251659264" behindDoc="0" locked="0" layoutInCell="1" hidden="0" allowOverlap="1">
            <wp:simplePos x="0" y="0"/>
            <wp:positionH relativeFrom="page"><wp:posOffset>-27296</wp:posOffset></wp:positionH>
            <wp:positionV relativeFrom="page"><wp:posOffset>-8748</wp:posOffset></wp:positionV>
            <wp:extent cx="6048375" cy="1771650"/>
            <wp:effectExtent l="0" t="0" r="0" b="0"/>
            <wp:wrapTopAndBottom distT="114300" distB="114300"/>
            <wp:docPr id="7" name="image1.png"/>
            <wp:cNvGraphicFramePr/>
            <a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
              <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
                <pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
                  <pic:nvPicPr><pic:cNvPr id="0" name="image1.png"/><pic:cNvPicPr preferRelativeResize="0"/></pic:nvPicPr>
                  <pic:blipFill><a:blip r:embed="rId4"/><a:srcRect/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>
                  <pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="6048375" cy="1771650"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:ln/></pic:spPr>
                </pic:pic>
              </a:graphicData>
            </a:graphic>
          </wp:anchor>
        </w:drawing>
      </w:r>
      <w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:bCs/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr><w:t xml:space="preserve">REKAP HARI DAN RUANG SIDANG SKRIPSI JULI 2026</w:t></w:r>
    </w:p>
    <w:tbl>
      <w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="10301" w:type="dxa"/><w:tblInd w:w="-431" w:type="dxa"/><w:tblLook w:val="04A0" w:firstRow="1" w:lastRow="0" w:firstColumn="1" w:lastColumn="0" w:noHBand="0" w:noVBand="1"/></w:tblPr>
      <w:tblGrid><w:gridCol w:w="480"/><w:gridCol w:w="4241"/><w:gridCol w:w="2445"/><w:gridCol w:w="1488"/><w:gridCol w:w="1647"/></w:tblGrid>
      <w:tr w:rsidR="00CD19A7">
        <w:tc><w:tcPr><w:tcW w:w="480" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>No</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="4241" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Nama Dosen</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="2445" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Hari</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="1488" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Ruang</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="1647" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Jam</w:t></w:r></w:p></w:tc>
      </w:tr>
      ' . $table1Xml . '
    </w:tbl>
    <w:p><w:pPr><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:bCs/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:bCs/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr><w:t xml:space="preserve">' . $totalText . '</w:t></w:r></w:p>
    <w:tbl>
      <w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="12680" w:type="dxa"/><w:tblInd w:w="-431" w:type="dxa"/><w:tblLook w:val="04A0" w:firstRow="1" w:lastRow="0" w:firstColumn="1" w:lastColumn="0" w:noHBand="0" w:noVBand="1"/></w:tblPr>
      <w:tblGrid><w:gridCol w:w="480"/><w:gridCol w:w="1800"/><w:gridCol w:w="2100"/><w:gridCol w:w="2100"/><w:gridCol w:w="2100"/><w:gridCol w:w="1800"/><w:gridCol w:w="1400"/><w:gridCol w:w="900"/></w:tblGrid>
      <w:tr w:rsidR="00CD19A7">
        <w:tc><w:tcPr><w:tcW w:w="480" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>No</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="1800" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Nama</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="2100" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Ketua Penguji</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="2100" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Penguji 1</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="2100" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Penguji2</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="1800" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Hari</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="1400" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Jam</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="900" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b/><w:bCs/></w:rPr></w:pPr><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>Ruang</w:t></w:r></w:p></w:tc>
      </w:tr>
      ' . $table2Xml . '
    </w:tbl>
    <w:p><w:pPr><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/></w:rPr></w:pPr></w:p>
    <w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="708" w:footer="708" w:gutter="0"/><w:cols w:space="708"/><w:docGrid w:linePitch="360"/></w:sectPr>
  </w:body>
</w:document>';

    $safeFileName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $dosenName);
    $outputFile = $outputDir . '/' . $safeFileName . '.docx';
    
    $zip = new ZipArchive;
    if ($zip->open($outputFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($templateFiles as $name => $content) {
            if ($name === 'word/document.xml') {
                $zip->addFromString($name, $documentXml);
            } else {
                $zip->addFromString($name, $content);
            }
        }
        $zip->close();
        echo "✓ Generated: $outputFile ($totalMahasiswa mahasiswa)\n";
    } else {
        echo "✗ ERROR: $outputFile\n";
    }
}

echo "\n========================================\n";
echo "Done! " . count($dosenSessions) . " files generated in: $outputDir/\n";
echo "========================================\n";
