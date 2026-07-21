<?php
$file = $argv[1] ?? 'E:/skripsi/Berita Acara Skripsi New.docx';
$zip = new ZipArchive();
if (!$zip->open($file)) { die("Cannot open: $file\n"); }
$xml = $zip->getFromName('word/document.xml');
$zip->close();

// Extract paragraphs
preg_match_all('/<w:p[ >].*?<\/w:p>/s', $xml, $paras);
foreach ($paras[0] as $para) {
    $text = preg_replace('/<w:br[^\/]*\/>/','[BR]', $para);
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', trim($text));
    if ($text !== '') echo $text . "\n";
}
