<?php
// Debug: check all files in template docx and print ALL text including empty paragraphs
$file = 'Template Undangan Skripsi.docx';

$zip = new ZipArchive;
if ($zip->open($file) === TRUE) {
    echo "=== FILES ===\n";
    for ($i = 0; $i < $zip->numFiles; $i++) {
        echo $zip->getNameIndex($i) . "\n";
    }
    
    $content = $zip->getFromName('word/document.xml');
    if ($content) {
        // Extract all text nodes
        $dom = new DOMDocument;
        $dom->loadXML($content);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        
        $paragraphs = $xpath->query('//w:p');
        echo "\n=== PARAGRAPHS (total: " . $paragraphs->length . ") ===\n";
        
        foreach ($paragraphs as $idx => $p) {
            $textNodes = $xpath->query('.//w:t', $p);
            $text = '';
            foreach ($textNodes as $t) {
                $text .= $t->textContent;
            }
            echo "P$idx: [$text]\n";
        }
        
        // Check for tables
        $tables = $xpath->query('//w:tbl');
        echo "\n=== TABLES (total: " . $tables->length . ") ===\n";
    }
    $zip->close();
}
