<?php

namespace App\Support;

use RuntimeException;

/**
 * Menggabungkan beberapa PDF keluaran DomPDF menjadi satu file, tanpa library
 * tambahan. Tiap PDF sumber cukup dibaca sekali lalu langsung ditulis ke file
 * hasil, jadi pemakaian memori tidak bertambah seiring jumlah PDF.
 *
 * Hanya untuk PDF sederhana seperti keluaran DomPDF: tabel xref klasik, tanpa
 * object stream / xref stream, dan tanpa incremental update.
 */
class PdfGabung
{
    /** @var resource */
    private $out;

    private int $posisi = 0;

    /** @var array<int,int> nomor objek => offset byte di file hasil */
    private array $offsets = [];

    /** Objek 1 = Catalog, objek 2 = Pages induk; sumber mulai dari 3. */
    private int $nomorBerikut = 3;

    /** @var list<int> node Pages tiap PDF sumber */
    private array $kids = [];

    private int $jumlahHalaman = 0;

    public function __construct(private string $pathHasil)
    {
        $out = fopen($pathHasil, 'wb');
        if ($out === false) {
            throw new RuntimeException("Tidak bisa menulis {$pathHasil}");
        }
        $this->out = $out;
        $this->tulis("%PDF-1.7\n%\xE2\xE3\xCF\xD3\n");
    }

    public function tambah(string $pdf): void
    {
        if (!preg_match('/startxref\s+(\d+)\s+%%EOF\s*$/', $pdf, $m)) {
            throw new RuntimeException('PDF tidak valid: startxref tidak ditemukan.');
        }
        $posXref = (int) $m[1];

        if (!preg_match('/\Gxref\s+(\d+)\s+(\d+)\s+/', $pdf, $h, 0, $posXref)) {
            throw new RuntimeException('PDF tidak didukung: tidak memakai tabel xref klasik.');
        }

        $mulai = (int) $h[1];
        $jumlah = (int) $h[2];
        preg_match_all('/(\d{10}) (\d{5}) ([nf])/', substr($pdf, $posXref + strlen($h[0]), $jumlah * 20), $baris, PREG_SET_ORDER);

        $offsetSumber = [];
        foreach ($baris as $i => $b) {
            if ($b[3] === 'n') {
                $offsetSumber[$mulai + $i] = (int) $b[1];
            }
        }

        $trailer = substr($pdf, $posXref);
        if (!preg_match('/\/Root (\d+) 0 R/', $trailer, $r)) {
            throw new RuntimeException('PDF tidak valid: /Root tidak ditemukan.');
        }
        $nomorRoot = (int) $r[1];
        $nomorInfo = preg_match('/\/Info (\d+) 0 R/', $trailer, $i) ? (int) $i[1] : null;

        // Batas tiap objek = offset objek berikutnya (atau awal xref), jadi isi
        // stream biner tidak perlu di-parse.
        $urut = $offsetSumber;
        asort($urut);
        $nomorUrut = array_keys($urut);

        $geser = $this->nomorBerikut;
        $remap = fn (string $teks) => preg_replace_callback(
            '/\b(\d+) 0 R\b/',
            fn ($x) => ((int) $x[1] + $geser) . ' 0 R',
            $teks
        );

        $nomorPages = null;
        $maks = 0;

        foreach ($nomorUrut as $idx => $nomor) {
            $maks = max($maks, $nomor);
            if ($nomor === $nomorRoot || $nomor === $nomorInfo) {
                if ($nomor === $nomorRoot) {
                    $root = $this->potong($pdf, $urut[$nomor], $urut, $nomorUrut, $idx, $posXref);
                    if (preg_match('/\/Pages (\d+) 0 R/', $root, $p)) {
                        $nomorPages = (int) $p[1];
                    }
                }
                continue;
            }

            $obj = $this->potong($pdf, $urut[$nomor], $urut, $nomorUrut, $idx, $posXref);
            $obj = rtrim($obj);

            // Pisahkan kamus objek dari isi stream biner; referensi hanya
            // diganti di bagian kamus.
            if (preg_match('/^(.*?>>\s*)stream(\r\n|\n)/s', $obj, $s)) {
                $kepala = $s[1];
                $badan = substr($obj, strlen($kepala));
            } else {
                $kepala = $obj;
                $badan = '';
            }

            $kepala = preg_replace('/^\d+ 0 obj/', ($nomor + $geser) . ' 0 obj', $kepala, 1);
            $kepala = $remap($kepala);

            if (preg_match('/\/Type\s*\/Pages\b/', $kepala)) {
                // Node Pages sumber menjadi anak dari Pages induk. Resources
                // tetap di node ini sehingga halaman-halamannya tetap mewarisi.
                $kepala = preg_replace('/<<\s*/', '<< /Parent 2 0 R ', $kepala, 1);
                if (preg_match('/\/Count (\d+)/', $kepala, $c)) {
                    $this->jumlahHalaman += (int) $c[1];
                }
            }

            $this->offsets[$nomor + $geser] = $this->posisi;
            $this->tulis($kepala . $badan . "\n");
        }

        if ($nomorPages === null) {
            throw new RuntimeException('PDF tidak valid: node Pages tidak ditemukan.');
        }

        $this->kids[] = $nomorPages + $geser;
        $this->nomorBerikut = $geser + $maks + 1;
    }

    public function selesai(): void
    {
        $this->offsets[1] = $this->posisi;
        $this->tulis("1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n");

        $kids = implode(' ', array_map(fn ($n) => "{$n} 0 R", $this->kids));
        $this->offsets[2] = $this->posisi;
        $this->tulis("2 0 obj\n<< /Type /Pages /Kids [{$kids}] /Count {$this->jumlahHalaman} >>\nendobj\n");

        $size = $this->nomorBerikut;
        $posXref = $this->posisi;
        $xref = "xref\n0 {$size}\n0000000000 65535 f \n";
        for ($n = 1; $n < $size; $n++) {
            $xref .= isset($this->offsets[$n])
                ? sprintf("%010d 00000 n \n", $this->offsets[$n])
                : "0000000000 65535 f \n";
        }
        $this->tulis($xref);
        $this->tulis("trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$posXref}\n%%EOF\n");

        fclose($this->out);
    }

    private function potong(string $pdf, int $awal, array $urut, array $nomorUrut, int $idx, int $posXref): string
    {
        $akhir = isset($nomorUrut[$idx + 1]) ? $urut[$nomorUrut[$idx + 1]] : $posXref;

        return substr($pdf, $awal, $akhir - $awal);
    }

    private function tulis(string $data): void
    {
        fwrite($this->out, $data);
        $this->posisi += strlen($data);
    }
}
