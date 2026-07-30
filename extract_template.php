<?php
// Extract text from Template Undangan Skripsi.docx
$file = 'Template Undangan Skripsi.docx';

$zip = new ZipArchive;
if ($zip->open($file) === TRUE) {
    $content = $zip->getFromName('word/document.xml');
    if ($content) {
        $xml = new SimpleXMLElement($content);
        $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        
        $paragraphs = $xml->xpath('//w:p');
        foreach ($paragraphs as $p) {
            $texts = $p->xpath('.//w:t');
            $line = '';
            foreach ($texts as $t) {
                $line .= (string)$t;
            }
            if (trim($line) !== '') {
                echo $line . "\n";
            }
        }
    }
    $zip->close();
}
