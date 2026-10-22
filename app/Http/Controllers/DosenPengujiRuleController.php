<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\DosenPengujiRule;
use App\Services\ActivityLogger;
use App\Http\Middleware\PinRulePenguji;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class DosenPengujiRuleController extends Controller
{
    /**
     * Buka halaman rahasia dengan PIN. Salah PIN berulang kali dikunci
     * sementara per user (dan per IP) supaya PIN tidak bisa ditebak.
     */
    public function buka(Request $request): JsonResponse|RedirectResponse
    {
        $cfg = config('rahasia.rule_penguji');
        $kunciLimiter = 'pin-rule-penguji:' . $request->user()->id . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($kunciLimiter, (int) $cfg['maks_percobaan'])) {
            $detik = RateLimiter::availableIn($kunciLimiter);
            return $this->jawabPin($request, false, 'Terlalu banyak percobaan. Coba lagi dalam ' . ceil($detik / 60) . ' menit.', 429, $detik);
        }

        $pin = preg_replace('/\D/', '', (string) $request->input('pin'));

        if (strlen($pin) !== 6 || !Hash::check($pin, (string) $cfg['pin_hash'])) {
            RateLimiter::hit($kunciLimiter, (int) $cfg['jeda_detik']);
            $sisa = RateLimiter::remaining($kunciLimiter, (int) $cfg['maks_percobaan']);
            ActivityLogger::log('pin_gagal', null, 'Percobaan PIN salah pada halaman Rule Komposisi Penguji.');

            return $this->jawabPin($request, false, $sisa > 0
                ? "PIN salah. Sisa {$sisa} percobaan."
                : 'PIN salah. Halaman dikunci sementara selama ' . ceil($cfg['jeda_detik'] / 60) . ' menit.', 422);
        }

        RateLimiter::clear($kunciLimiter);
        $request->session()->regenerate();
        PinRulePenguji::perpanjang($request);
        ActivityLogger::log('pin_dibuka', null, 'Membuka halaman Rule Komposisi Penguji dengan PIN.');

        return $this->jawabPin($request, true, 'PIN benar. Membuka halaman…');
    }

    /** Kunci kembali halaman rahasia secara manual. */
    public function kunci(Request $request): RedirectResponse
    {
        $request->session()->forget(PinRulePenguji::SESSION_KEY);

        return redirect()->route('master.dosen-penguji-rule.index')->with('success', 'Halaman Rule Komposisi Penguji telah dikunci.');
    }

    private function jawabPin(Request $request, bool $ok, string $pesan, int $status = 200, ?int $tungguDetik = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(array_filter([
                'success' => $ok,
                'message' => $pesan,
                'tunggu'  => $tungguDetik,
            ], fn ($v) => $v !== null), $status);
        }

        return redirect()->route('master.dosen-penguji-rule.index')->with($ok ? 'success' : 'error', $pesan);
    }

    public function index(Request $request): View
    {
        $query = DosenPengujiRule::with('dosen');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('dosen', function ($q) use ($search) {
                $q->where('nama_dosen', 'like', "%{$search}%");
            });
        }

        $rules = $query->get()->sortBy(fn ($r) => $r->dosen->nama_dosen ?? '')->values();

        // Dosen yang belum punya rule sama sekali, untuk opsi "Tambah Rule"
        $dosenBelumAdaRule = Dosen::whereNotIn('id', DosenPengujiRule::pluck('dosen_id'))
            ->excludingSuperAdminPlaceholder()
            ->orderBy('nama_dosen')
            ->get();

        $dosens = Dosen::excludingSuperAdminPlaceholder()->orderBy('nama_dosen')->get();

        return view('master.dosen-penguji-rule.index', compact('rules', 'dosenBelumAdaRule', 'dosens'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dosen_id' => ['required', 'exists:dosens,id', 'unique:dosen_penguji_rules,dosen_id'],
            'boleh_dosen_ids' => ['nullable', 'array'],
            'boleh_dosen_ids.*' => ['exists:dosens,id'],
            'tidak_boleh_dosen_ids' => ['nullable', 'array'],
            'tidak_boleh_dosen_ids.*' => ['exists:dosens,id'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], [
            'dosen_id.required' => 'Dosen wajib dipilih.',
            'dosen_id.unique' => 'Dosen ini sudah memiliki rule komposisi penguji. Silakan edit rule yang sudah ada.',
        ]);

        $rule = $this->saveRule(new DosenPengujiRule(), $validated);

        ActivityLogger::log(
            'created',
            $rule,
            "Menambahkan rule komposisi dosen penguji untuk {$rule->dosen->nama_dosen}."
        );

        return back()->with('success', "Rule komposisi penguji untuk {$rule->dosen->nama_dosen} berhasil ditambahkan.");
    }

    public function update(Request $request, DosenPengujiRule $dosenPengujiRule): RedirectResponse
    {
        $validated = $request->validate([
            'boleh_dosen_ids' => ['nullable', 'array'],
            'boleh_dosen_ids.*' => ['exists:dosens,id'],
            'tidak_boleh_dosen_ids' => ['nullable', 'array'],
            'tidak_boleh_dosen_ids.*' => ['exists:dosens,id'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $rule = $this->saveRule($dosenPengujiRule, $validated);

        ActivityLogger::log(
            'updated',
            $rule,
            "Memperbarui rule komposisi dosen penguji untuk {$rule->dosen->nama_dosen}."
        );

        return back()->with('success', "Rule komposisi penguji untuk {$rule->dosen->nama_dosen} berhasil diperbarui.");
    }

    public function destroy(DosenPengujiRule $dosenPengujiRule): RedirectResponse
    {
        $nama = $dosenPengujiRule->dosen->nama_dosen ?? '-';
        $dosenPengujiRule->delete();

        return back()->with('success', "Rule komposisi penguji untuk {$nama} berhasil dihapus.");
    }

    private function saveRule(DosenPengujiRule $rule, array $validated): DosenPengujiRule
    {
        $dosenId = $validated['dosen_id'] ?? $rule->dosen_id;

        $boleh = array_values(array_unique(array_map('intval', $validated['boleh_dosen_ids'] ?? [])));
        $tidakBoleh = array_values(array_unique(array_map('intval', $validated['tidak_boleh_dosen_ids'] ?? [])));

        // Satu dosen tidak boleh muncul di kedua daftar sekaligus untuk dosen yang sama —
        // "tidak boleh" menang jika tumpang tindih, karena itu larangan keras.
        $boleh = array_values(array_diff($boleh, $tidakBoleh));

        // Seorang dosen tidak bisa jadi pasangan untuk dirinya sendiri.
        $boleh = array_values(array_diff($boleh, [(int) $dosenId]));
        $tidakBoleh = array_values(array_diff($tidakBoleh, [(int) $dosenId]));

        $rule->dosen_id = $dosenId;
        $rule->boleh_dosen_ids = $boleh;
        $rule->tidak_boleh_dosen_ids = $tidakBoleh;
        $rule->keterangan = $validated['keterangan'] ?? null;
        $rule->save();

        return $rule->fresh('dosen');
    }
}
