<?php

namespace App\Support;

use Carbon\Carbon;

class TanggalIndo
{
    private const BULAN = [
        'Januari'   => '01', 'Februari' => '02', 'Maret'     => '03',
        'April'     => '04', 'Mei'      => '05', 'Juni'      => '06',
        'Juli'      => '07', 'Agustus'  => '08', 'September' => '09',
        'Oktober'   => '10', 'November' => '11', 'Desember'  => '12',
    ];

    /**
     * Format tanggal lengkap bahasa Indonesia untuk export Excel / mail merge,
     * mis. "Jum'at, 10 Juni 2026". Mengembalikan $kosong jika tanggal null.
     */
    public static function lengkap($tanggal, string $kosong = '-'): string
    {
        if (empty($tanggal)) {
            return $kosong;
        }

        $date = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal);

        return str_replace('Jumat', "Jum'at", $date->locale('id')->translatedFormat('l, j F Y'));
    }

    /**
     * Baca tanggal dari sel Excel impor (Sempro & Skripsi) → "Y-m-d".
     * Menerima nomor seri tanggal Excel, "Y-m-d", "Senin, 10 Juni 2026",
     * "10 Juni 2026", serta "d/m/Y" atau "m/d/Y" (ditebak dari nilai > 12).
     */
    public static function dariExcel(mixed $dateVal, $cell = null): ?string
    {
        if (empty($dateVal)) return null;

        if ($cell && is_numeric($cell->getValue())) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cell->getValue())->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        if (is_numeric($dateVal)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateVal)->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        $dateStr = trim((string) $dateVal);
        if (empty($dateStr)) return null;

        // Standard Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        // Buang nama hari di depan ("Senin, ...").
        $clean = preg_replace('/^[A-Za-z]+,\s*/u', '', $dateStr);

        foreach (self::BULAN as $namaBulan => $numBulan) {
            if (stripos($clean, $namaBulan) !== false) {
                if (preg_match('/(\d{1,2})\s+' . $namaBulan . '\s+(\d{4})/i', $clean, $m)) {
                    $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                    return "{$m[2]}-{$numBulan}-{$day}";
                }
            }
        }

        try {
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $dateStr, $m)) {
                $val1 = (int) $m[1];
                $val2 = (int) $m[2];
                $year = (int) $m[3];
                if ($val1 > 12 && $val2 <= 12) {
                    return sprintf('%04d-%02d-%02d', $year, $val2, $val1);
                } elseif ($val2 > 12 && $val1 <= 12) {
                    return sprintf('%04d-%02d-%02d', $year, $val1, $val2);
                } else {
                    return sprintf('%04d-%02d-%02d', $year, $val2, $val1);
                }
            }
            return null;
        }
    }
}
