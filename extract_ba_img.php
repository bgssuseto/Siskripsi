<?php
$file = 'E:/skripsi/Berita Acara Skripsi New.docx';
$zip = new ZipArchive();
$zip->open($file);
$rels = $zip->getFromName('word/_rels/document.xml.rels');
echo "RELS:\n" . $rels . "\n\n";

// List media files
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (strpos($name, 'media/') !== false) {
        echo "MEDIA: " . $name . "\n";
        // Extract image
        $data = $zip->getFromIndex($i);
        $outName = 'public/images/ba_' . basename($name);
        file_put_contents($outName, $data);
        echo "Saved: $outName\n";
    }
}
$zip->close();
