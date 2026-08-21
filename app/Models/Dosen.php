<?php

namespace App\Models;

use App\Concerns\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Crypt;

class Dosen extends Model
{
    use HasFactory, HasHashedRouteKey;

    protected $fillable = [
        'nidn',
        'nama_dosen',
        'alias',
        'kepakaran',
        'jabatan_fungsional',
        'no_wa',
        'can_fill_kesediaan',
    ];

    protected $casts = [
        'can_fill_kesediaan' => 'boolean',
    ];

    /**
     * Urutan jabatan fungsional dosen dari yang terendah ke tertinggi.
     * Dipakai untuk membandingkan pangkat pada aturan komposisi dewan penguji.
     */
    public const JABATAN_FUNGSIONAL_RANKS = [
        'Tenaga Pengajar' => 1,
        'Asisten Ahli'    => 2,
        'Lektor'          => 3,
        'Lektor Kepala'   => 4,
        'Guru Besar'      => 5,
    ];

    public function getJabatanRankAttribute(): int
    {
        return self::JABATAN_FUNGSIONAL_RANKS[$this->jabatan_fungsional] ?? 0;
    }

    public function getWaFormattedAttribute(): ?string
    {
        if (empty($this->no_wa)) return null;
        $num = preg_replace('/[^0-9]/', '', $this->no_wa);
        if (str_starts_with($num, '0')) {
            $num = '62' . substr($num, 1);
        }
        return $num;
    }

    public function getPublicTokenAttribute(): string
    {
        $hash = substr(hash('sha256', 'siskripsi_dosen_salt_' . $this->id), 0, 8);
        return rtrim(strtr(base64_encode($this->id . ':' . $hash), '+/', '-_'), '=');
    }

    public function getPublicUrlAttribute(): string
    {
        return route('public.dosen.jadwal', ['token' => $this->public_token]);
    }

    public function getPublicPdfUrlAttribute(): string
    {
        return route('public.dosen.jadwal.pdf', ['token' => $this->public_token]);
    }

    /**
     * Build a public jadwal link, preferring the human-readable alias shortlink
     * (/j/{alias}) when one is set, falling back to the opaque token link otherwise.
     * Optionally scoped to a periode+gelombang+jenis via query params (p/g/j) so the
     * link freezes to whatever was selected at share-time instead of floating with
     * "whatever periode happens to be active" forever.
     */
    public function publicUrlFor(?int $periodeId = null, ?int $gelombang = null, ?string $jenis = null, bool $pdf = false): string
    {
        $routeName = $this->alias
            ? ($pdf ? 'public.dosen.jadwal.alias.pdf' : 'public.dosen.jadwal.alias')
            : ($pdf ? 'public.dosen.jadwal.pdf' : 'public.dosen.jadwal');

        $params = $this->alias ? ['alias' => $this->alias] : ['token' => $this->public_token];

        if ($periodeId) {
            $params['p'] = $periodeId;
        }
        if ($gelombang !== null && $gelombang !== '') {
            $params['g'] = $gelombang;
        }
        if ($jenis) {
            $params['j'] = $jenis;
        }

        return route($routeName, $params);
    }

    public function sidangsSebagaiPembimbingUtama()
    {
        return $this->hasMany(Sidang::class, 'dosen_pembimbing_utama_id');
    }

    public function sidangsSebagaiKetuaPenguji()
    {
        return $this->hasMany(Sidang::class, 'ketua_penguji_id');
    }

    /**
     * Clean a lecturer name for matching by removing titles, degrees, and punctuation.
     */
    public static function cleanName(string $name): string
    {
        $n = mb_strtolower($name);
        $titles = [
            'dr.', 'ir.', 's.kom.', 's.kom', 'm.kom.', 'm.kom', 'st.', 'st', 'm.cs.', 'm.cs',
            's.si.', 's.si', 'm.t.', 'm.t', 'ph.d', 'prof.', 's.pd.', 'm.pd.', 's.t.', 'm.sc.'
        ];
        foreach ($titles as $t) {
            $n = str_replace($t, ' ', $n);
        }
        $n = preg_replace('/[^\w\s]/u', ' ', $n);
        return trim(preg_replace('/\s+/', ' ', $n));
    }

    /**
     * Smartly resolve a lecturer by name from master database.
     * Matches exact, case-insensitive, title-removed, word match, or fuzzy typo match.
     * Prevents creating duplicate dosen entries when Excel contains typos or short names like "Esti".
     */
    public static function resolveByName(?string $namaDosen): ?self
    {
        if (empty($namaDosen)) return null;
        $raw = trim($namaDosen);
        if (empty($raw)) return null;

        // 1. Exact match
        $dosen = self::where('nama_dosen', $raw)->first();
        if ($dosen) return $dosen;

        // 2. Case-insensitive exact match
        $dosen = self::whereRaw('LOWER(nama_dosen) = ?', [mb_strtolower($raw)])->first();
        if ($dosen) return $dosen;

        $cleanInput = self::cleanName($raw);
        if (empty($cleanInput)) return null;

        $allDosens = self::all();

        // 3. Match by normalized name (without titles)
        foreach ($allDosens as $d) {
            $cleanMaster = self::cleanName($d->nama_dosen);
            if ($cleanMaster === $cleanInput) {
                return $d;
            }
        }

        // 4. Substring / Word Match (e.g. "Esti" or "Esti Wijayanti" -> "Esti Wijayanti, S.Kom., M.Kom")
        $inputWords = array_values(array_filter(explode(' ', $cleanInput), fn($w) => strlen($w) >= 2));

        if (!empty($inputWords)) {
            $matchingDosens = [];
            foreach ($allDosens as $d) {
                $cleanMaster = self::cleanName($d->nama_dosen);
                $masterWords = array_values(array_filter(explode(' ', $cleanMaster), fn($w) => strlen($w) >= 2));

                $matchedCount = 0;
                foreach ($inputWords as $iw) {
                    foreach ($masterWords as $mw) {
                        if ($iw === $mw || str_contains($mw, $iw) || str_contains($iw, $mw)) {
                            $matchedCount++;
                            break;
                        }
                    }
                }
                if ($matchedCount === count($inputWords)) {
                    $matchingDosens[] = $d;
                }
            }

            if (count($matchingDosens) === 1) {
                return $matchingDosens[0];
            } elseif (count($matchingDosens) > 1) {
                usort($matchingDosens, function($a, $b) use ($cleanInput) {
                    return abs(strlen(self::cleanName($a->nama_dosen)) - strlen($cleanInput)) - abs(strlen(self::cleanName($b->nama_dosen)) - strlen($cleanInput));
                });
                return $matchingDosens[0];
            }
        }

        // 5. Fuzzy similarity for minor typos (e.g. "Wibowo Hari" -> "Wibowo Harry")
        $bestMatch = null;
        $bestScore = 0;
        foreach ($allDosens as $d) {
            $cleanMaster = self::cleanName($d->nama_dosen);
            similar_text($cleanInput, $cleanMaster, $percent);
            if ($percent > $bestScore && $percent >= 70) {
                $bestScore = $percent;
                $bestMatch = $d;
            }
        }

        if ($bestMatch) {
            return $bestMatch;
        }

        // 6. Fallback: Create new entry if no match exists
        $dummyNidn = 'NIDN-' . strtoupper(substr(md5($raw), 0, 8));
        return self::create([
            'nama_dosen' => $raw,
            'nidn'       => $dummyNidn,
        ]);
    }
}
