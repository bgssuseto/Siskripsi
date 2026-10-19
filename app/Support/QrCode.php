<?php

namespace App\Support;

/**
 * Generator QR Code minimal (mode byte, koreksi galat level M atau H) tanpa dependensi
 * Composer — server produksi tidak bisa menjalankan composer install. Mengikuti
 * spesifikasi ISO/IEC 18004 (diadaptasi dari pustaka QR Nayuki, lisensi MIT).
 * Output berupa SVG yang bisa langsung dipakai dompdf maupun browser.
 */
class QrCode
{
    // Indeks = versi (1..40). M ≈ 15% pemulihan, H ≈ 30% (cukup untuk logo di tengah).
    private const ECC_PER_BLOCK = [
        'M' => [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26, 26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28],
        'H' => [-1, 17, 28, 22, 16, 22, 28, 26, 26, 24, 28, 24, 28, 22, 24, 24, 30, 28, 28, 26, 28, 30, 24, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
    ];
    private const NUM_BLOCKS = [
        'M' => [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49],
        'H' => [-1, 1, 1, 2, 4, 4, 4, 5, 6, 8, 8, 11, 11, 16, 16, 18, 16, 19, 21, 25, 25, 25, 34, 30, 32, 35, 37, 40, 42, 45, 48, 51, 54, 57, 60, 63, 66, 70, 74, 77, 81],
    ];
    private const ECC_FORMAT_BITS = ['M' => 0, 'H' => 2];

    private string $ecc;

    private int $version;
    private int $size;
    /** @var array<int, array<int, bool>> */
    private array $modules = [];
    /** @var array<int, array<int, bool>> */
    private array $isFunction = [];

    private function __construct(string $data, string $ecc = 'M')
    {
        $this->ecc = $ecc;
        $bytes = array_values(unpack('C*', $data) ?: []);

        // Pilih versi terkecil yang muat
        for ($ver = 1; ; $ver++) {
            if ($ver > 40) {
                throw new \InvalidArgumentException('Data terlalu panjang untuk QR Code.');
            }
            $countBits = $ver < 10 ? 8 : 16;
            $needed = 4 + $countBits + count($bytes) * 8;
            if ($needed <= $this->numDataCodewords($ver) * 8) {
                break;
            }
        }
        $this->version = $ver;
        $this->size = $ver * 4 + 17;

        // Susun bit data: mode byte (0100), panjang, isi, terminator, padding
        $bits = [];
        $append = function (int $val, int $len) use (&$bits) {
            for ($i = $len - 1; $i >= 0; $i--) {
                $bits[] = ($val >> $i) & 1;
            }
        };
        $append(0b0100, 4);
        $append(count($bytes), $countBits);
        foreach ($bytes as $b) {
            $append($b, 8);
        }
        $capacity = $this->numDataCodewords($ver) * 8;
        $append(0, min(4, $capacity - count($bits)));
        $append(0, (8 - count($bits) % 8) % 8);
        for ($pad = 0xEC; count($bits) < $capacity; $pad ^= 0xEC ^ 0x11) {
            $append($pad, 8);
        }

        $codewords = array_fill(0, intdiv(count($bits), 8), 0);
        foreach ($bits as $i => $bit) {
            $codewords[$i >> 3] |= $bit << (7 - ($i & 7));
        }

        $this->modules    = array_fill(0, $this->size, array_fill(0, $this->size, false));
        $this->isFunction = $this->modules;

        $this->drawFunctionPatterns();
        $this->drawCodewords($this->addEccAndInterleave($codewords));

        // Pilih mask dengan penalti terendah
        $bestMask = 0;
        $minPenalty = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $this->applyMask($mask);
            $this->drawFormatBits($mask);
            $penalty = $this->penaltyScore();
            if ($penalty < $minPenalty) {
                $bestMask = $mask;
                $minPenalty = $penalty;
            }
            $this->applyMask($mask); // XOR kembali
        }
        $this->applyMask($bestMask);
        $this->drawFormatBits($bestMask);
    }

    /**
     * SVG QR Code (dengan quiet zone 4 modul). Koordinat ditulis langsung dalam
     * ukuran akhir — renderer SVG dompdf tidak menskalakan viewBox dengan benar.
     *
     * $kosongTengah: rasio lebar area tengah yang dikosongkan (untuk ditimpa
     * logo). Hanya aman dengan $ecc = 'H' dan rasio ≤ 0.3.
     */
    public static function svg(string $data, int $moduleSize = 8, string $ecc = 'M', float $kosongTengah = 0.0): string
    {
        return self::bangun($data, $moduleSize, $ecc, $kosongTengah)['svg'];
    }

    /**
     * QR level H dengan area tengah dikosongkan untuk logo. Mengembalikan data
     * URI SVG dan rasio lebar area kosong terhadap lebar gambar (termasuk
     * quiet zone) — dipakai untuk mengukur & memusatkan logo di atasnya.
     *
     * @return array{uri: string, rasio_logo: float}
     */
    public static function denganRuangLogo(string $data, float $kosongTengah = 0.26, int $moduleSize = 8): array
    {
        $hasil = self::bangun($data, $moduleSize, 'H', $kosongTengah);

        return [
            'uri'        => 'data:image/svg+xml;base64,' . base64_encode($hasil['svg']),
            'rasio_logo' => $hasil['lebar_kosong'] / $hasil['lebar_total'],
        ];
    }

    /** @return array{svg: string, lebar_kosong: int, lebar_total: int} */
    private static function bangun(string $data, int $moduleSize, string $ecc, float $kosongTengah): array
    {
        $qr = new self($data, $ecc);
        $border = 4;
        $dim = ($qr->size + $border * 2) * $moduleSize;

        $lebarKosong = (int) round($qr->size * $kosongTengah);
        if ($lebarKosong > 0 && ($qr->size - $lebarKosong) % 2 !== 0) {
            $lebarKosong++; // jaga agar area kosong tepat di tengah
        }
        $awalKosong = intdiv($qr->size - $lebarKosong, 2);
        $akhirKosong = $awalKosong + $lebarKosong;

        $path = '';
        for ($y = 0; $y < $qr->size; $y++) {
            for ($x = 0; $x < $qr->size; $x++) {
                $diTengah = $lebarKosong > 0 && $x >= $awalKosong && $x < $akhirKosong && $y >= $awalKosong && $y < $akhirKosong;
                if ($qr->modules[$y][$x] && !$diTengah) {
                    $path .= 'M' . (($x + $border) * $moduleSize) . ',' . (($y + $border) * $moduleSize)
                        . 'h' . $moduleSize . 'v' . $moduleSize . 'h-' . $moduleSize . 'z';
                }
            }
        }

        return [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $dim . '" height="' . $dim . '" viewBox="0 0 ' . $dim . ' ' . $dim . '" shape-rendering="crispEdges">'
                . '<rect x="0" y="0" width="' . $dim . '" height="' . $dim . '" fill="#ffffff"/><path d="' . $path . '" fill="#000000"/></svg>',
            'lebar_kosong' => $lebarKosong,
            'lebar_total'  => $qr->size + $border * 2,
        ];
    }

    public static function svgDataUri(string $data, int $moduleSize = 8, string $ecc = 'M', float $kosongTengah = 0.0): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::svg($data, $moduleSize, $ecc, $kosongTengah));
    }

    // ───────────── Pola fungsi ─────────────

    private function drawFunctionPatterns(): void
    {
        for ($i = 0; $i < $this->size; $i++) {
            $this->setFunction(6, $i, $i % 2 === 0);
            $this->setFunction($i, 6, $i % 2 === 0);
        }

        $this->drawFinder(3, 3);
        $this->drawFinder($this->size - 4, 3);
        $this->drawFinder(3, $this->size - 4);

        $pos = $this->alignmentPositions();
        $n = count($pos);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $n - 1) || ($i === $n - 1 && $j === 0)) {
                    continue;
                }
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $this->setFunction($pos[$i] + $dx, $pos[$j] + $dy, max(abs($dx), abs($dy)) !== 1);
                    }
                }
            }
        }

        $this->drawFormatBits(0); // placeholder, ditimpa setelah mask dipilih
        $this->drawVersion();
    }

    private function drawFinder(int $x, int $y): void
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $dist = max(abs($dx), abs($dy));
                $xx = $x + $dx;
                $yy = $y + $dy;
                if ($xx >= 0 && $xx < $this->size && $yy >= 0 && $yy < $this->size) {
                    $this->setFunction($xx, $yy, $dist !== 2 && $dist !== 4);
                }
            }
        }
    }

    private function alignmentPositions(): array
    {
        if ($this->version === 1) {
            return [];
        }
        $numAlign = intdiv($this->version, 7) + 2;
        $step = $this->version === 32 ? 26 : (int) ceil(($this->version * 4 + 4) / ($numAlign * 2 - 2)) * 2;
        $result = [];
        for ($i = 0, $pos = $this->size - 7; $i < $numAlign - 1; $i++, $pos -= $step) {
            array_unshift($result, $pos);
        }
        array_unshift($result, 6);

        return $result;
    }

    private function drawFormatBits(int $mask): void
    {
        $data = (self::ECC_FORMAT_BITS[$this->ecc] << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;

        for ($i = 0; $i <= 5; $i++) {
            $this->setFunction(8, $i, self::bit($bits, $i));
        }
        $this->setFunction(8, 7, self::bit($bits, 6));
        $this->setFunction(8, 8, self::bit($bits, 7));
        $this->setFunction(7, 8, self::bit($bits, 8));
        for ($i = 9; $i < 15; $i++) {
            $this->setFunction(14 - $i, 8, self::bit($bits, $i));
        }

        for ($i = 0; $i < 8; $i++) {
            $this->setFunction($this->size - 1 - $i, 8, self::bit($bits, $i));
        }
        for ($i = 8; $i < 15; $i++) {
            $this->setFunction(8, $this->size - 15 + $i, self::bit($bits, $i));
        }
        $this->setFunction(8, $this->size - 8, true);
    }

    private function drawVersion(): void
    {
        if ($this->version < 7) {
            return;
        }
        $rem = $this->version;
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
        }
        $bits = ($this->version << 12) | $rem;
        for ($i = 0; $i < 18; $i++) {
            $bit = self::bit($bits, $i);
            $a = $this->size - 11 + $i % 3;
            $b = intdiv($i, 3);
            $this->setFunction($a, $b, $bit);
            $this->setFunction($b, $a, $bit);
        }
    }

    private function setFunction(int $x, int $y, bool $dark): void
    {
        $this->modules[$y][$x] = $dark;
        $this->isFunction[$y][$x] = true;
    }

    // ───────────── Data & koreksi galat ─────────────

    private static function numRawDataModules(int $ver): int
    {
        $result = (16 * $ver + 128) * $ver + 64;
        if ($ver >= 2) {
            $numAlign = intdiv($ver, 7) + 2;
            $result -= (25 * $numAlign - 10) * $numAlign - 55;
            if ($ver >= 7) {
                $result -= 36;
            }
        }

        return $result;
    }

    private function numDataCodewords(int $ver): int
    {
        return intdiv(self::numRawDataModules($ver), 8) - self::ECC_PER_BLOCK[$this->ecc][$ver] * self::NUM_BLOCKS[$this->ecc][$ver];
    }

    private function addEccAndInterleave(array $data): array
    {
        $numBlocks = self::NUM_BLOCKS[$this->ecc][$this->version];
        $blockEccLen = self::ECC_PER_BLOCK[$this->ecc][$this->version];
        $rawCodewords = intdiv(self::numRawDataModules($this->version), 8);
        $numShortBlocks = $numBlocks - $rawCodewords % $numBlocks;
        $shortBlockLen = intdiv($rawCodewords, $numBlocks);

        $divisor = self::rsDivisor($blockEccLen);
        $blocks = [];
        for ($i = 0, $k = 0; $i < $numBlocks; $i++) {
            $len = $shortBlockLen - $blockEccLen + ($i < $numShortBlocks ? 0 : 1);
            $dat = array_slice($data, $k, $len);
            $k += $len;
            $ecc = self::rsRemainder($dat, $divisor);
            if ($i < $numShortBlocks) {
                $dat[] = 0; // placeholder agar panjang blok seragam
            }
            $blocks[] = array_merge($dat, $ecc);
        }

        $result = [];
        $blockLen = count($blocks[0]);
        for ($i = 0; $i < $blockLen; $i++) {
            foreach ($blocks as $j => $block) {
                if ($i !== $shortBlockLen - $blockEccLen || $j >= $numShortBlocks) {
                    $result[] = $block[$i];
                }
            }
        }

        return $result;
    }

    private static function rsDivisor(int $degree): array
    {
        $result = array_fill(0, $degree, 0);
        $result[$degree - 1] = 1;
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = self::gfMul($result[$j], $root);
                if ($j + 1 < $degree) {
                    $result[$j] ^= $result[$j + 1];
                }
            }
            $root = self::gfMul($root, 0x02);
        }

        return $result;
    }

    private static function rsRemainder(array $data, array $divisor): array
    {
        $result = array_fill(0, count($divisor), 0);
        foreach ($data as $b) {
            $factor = $b ^ array_shift($result);
            $result[] = 0;
            foreach ($divisor as $i => $coef) {
                $result[$i] ^= self::gfMul($coef, $factor);
            }
        }

        return $result;
    }

    private static function gfMul(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = (($z << 1) ^ (($z >> 7) * 0x11D)) & 0xFF;
            $z ^= (($y >> $i) & 1) * $x;
        }

        return $z;
    }

    private function drawCodewords(array $data): void
    {
        $total = count($data) * 8;
        $i = 0;
        for ($right = $this->size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $this->size; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? $this->size - 1 - $vert : $vert;
                    if (!$this->isFunction[$y][$x] && $i < $total) {
                        $this->modules[$y][$x] = self::bit($data[$i >> 3], 7 - ($i & 7));
                        $i++;
                    }
                }
            }
        }
    }

    // ───────────── Mask ─────────────

    private function applyMask(int $mask): void
    {
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                $invert = match ($mask) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                    5 => $x * $y % 2 + $x * $y % 3 === 0,
                    6 => ($x * $y % 2 + $x * $y % 3) % 2 === 0,
                    7 => (($x + $y) % 2 + $x * $y % 3) % 2 === 0,
                };
                if ($invert && !$this->isFunction[$y][$x]) {
                    $this->modules[$y][$x] = !$this->modules[$y][$x];
                }
            }
        }
    }

    /** Penalti pemilihan mask (aturan 1–4 spesifikasi). */
    private function penaltyScore(): int
    {
        $n = $this->size;
        $result = 0;

        // Aturan 1: deret warna sama ≥ 5 pada baris & kolom
        for ($axis = 0; $axis < 2; $axis++) {
            for ($a = 0; $a < $n; $a++) {
                $run = 1;
                for ($b = 1; $b < $n; $b++) {
                    $cur  = $axis === 0 ? $this->modules[$a][$b] : $this->modules[$b][$a];
                    $prev = $axis === 0 ? $this->modules[$a][$b - 1] : $this->modules[$b - 1][$a];
                    if ($cur === $prev) {
                        $run++;
                        if ($run === 5) {
                            $result += 3;
                        } elseif ($run > 5) {
                            $result++;
                        }
                    } else {
                        $run = 1;
                    }
                }
            }
        }

        // Aturan 2: blok 2x2 warna sama
        $dark = 0;
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($this->modules[$y][$x]) {
                    $dark++;
                }
                if ($x < $n - 1 && $y < $n - 1) {
                    $c = $this->modules[$y][$x];
                    if ($c === $this->modules[$y][$x + 1] && $c === $this->modules[$y + 1][$x] && $c === $this->modules[$y + 1][$x + 1]) {
                        $result += 3;
                    }
                }
            }
        }

        // Aturan 3: pola mirip finder (1:1:3:1:1 diapit 4 modul terang) — bisa
        // mengecoh detektor pemindai, jadi mask yang memunculkannya dihindari.
        $pola1 = '10111010000';
        $pola2 = '00001011101';
        for ($a = 0; $a < $n; $a++) {
            $baris = '';
            $kolom = '';
            for ($b = 0; $b < $n; $b++) {
                $baris .= $this->modules[$a][$b] ? '1' : '0';
                $kolom .= $this->modules[$b][$a] ? '1' : '0';
            }
            foreach ([$baris, $kolom] as $garis) {
                for ($i = 0; $i + 11 <= $n; $i++) {
                    $potong = substr($garis, $i, 11);
                    if ($potong === $pola1 || $potong === $pola2) {
                        $result += 40;
                    }
                }
            }
        }

        // Aturan 4: keseimbangan modul gelap/terang
        $total = $n * $n;
        $k = (int) ceil(abs($dark * 20 - $total * 10) / $total) - 1;

        return $result + max(0, $k) * 10;
    }

    private static function bit(int $x, int $i): bool
    {
        return (($x >> $i) & 1) !== 0;
    }
}
