<?php

namespace App\Http\Controllers;

use App\Models\BebasProdi;
use App\Models\Kaprodi;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BebasProdiController extends Controller
{
    private const BERKAS_DIR = 'bebas-prodi/berkas';

    // ───────────────────────── Mahasiswa ─────────────────────────

    public function mahasiswaIndex(): View
    {
        $user      = Auth::user();
        $kelayakan = BebasProdi::kelayakan($user->nim);
        $pengajuan = $user->nim ? BebasProdi::where('nim', $user->nim)->first() : null;

        return view('bebas-prodi.mahasiswa', compact('user', 'kelayakan', 'pengajuan'));
    }

    public function mahasiswaStore(Request $request): RedirectResponse
    {
        $user      = Auth::user();
        $kelayakan = BebasProdi::kelayakan($user->nim);

        if (!$kelayakan['eligible']) {
            return back()->with('error', 'Pengajuan Bebas Prodi baru terbuka setelah Anda dinyatakan lulus Seminar Proposal dan Skripsi.');
        }

        $pengajuan = BebasProdi::where('nim', $user->nim)->first();
        if ($pengajuan && $pengajuan->status !== BebasProdi::STATUS_DITOLAK) {
            return back()->with('error', $pengajuan->status === BebasProdi::STATUS_DISETUJUI
                ? 'Pengajuan Bebas Prodi Anda sudah disetujui.'
                : 'Pengajuan Bebas Prodi Anda sudah dikirim dan sedang menunggu verifikasi.');
        }

        $isJurnal = $kelayakan['jalur'] === 'jurnal';

        $validated = $request->validate([
            'link_poster'     => ['required', 'url', 'max:500'],
            'link_linkedin'   => ['required', 'url', 'max:500'],
            'link_program'    => ['nullable', 'url', 'max:500'],
            'link_jurnal'     => [$isJurnal ? 'required' : 'nullable', 'url', 'max:500'],
            'follow_instagram'=> ['accepted'],
            'follow_linkedin' => ['accepted'],
            'file_berkas'     => [$pengajuan ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:4096'],
        ], [
            'link_poster.required'     => 'Link Google Drive poster skripsi wajib diisi.',
            'link_poster.url'          => 'Link poster harus berupa URL yang valid (diawali https://).',
            'link_linkedin.required'   => 'Link profil LinkedIn wajib diisi.',
            'link_linkedin.url'        => 'Link LinkedIn harus berupa URL yang valid (diawali https://).',
            'link_program.url'         => 'Link program aplikasi harus berupa URL yang valid.',
            'link_jurnal.required'     => 'Link artikel jurnal yang telah terbit wajib diisi untuk jalur Jurnal.',
            'link_jurnal.url'          => 'Link artikel jurnal harus berupa URL yang valid.',
            'follow_instagram.accepted'=> 'Anda wajib follow Instagram @ti.umk sebelum mengirim.',
            'follow_linkedin.accepted' => 'Anda wajib follow LinkedIn resmi UMK sebelum mengirim.',
            'file_berkas.required'     => 'File berkas (PDF) wajib diunggah.',
            'file_berkas.mimes'        => 'File berkas harus berformat PDF.',
            'file_berkas.max'          => 'Ukuran file berkas maksimal 4 MB.',
            'file_berkas.uploaded'     => 'File berkas gagal diunggah ke server — kemungkinan ukurannya terlalu besar. Coba kompres file PDF-nya lalu unggah ulang.',
        ]);

        $filePath = $pengajuan?->file_berkas;
        if ($request->hasFile('file_berkas')) {
            if ($filePath) {
                Storage::disk('local')->delete($filePath);
            }
            $filePath = $request->file('file_berkas')->storeAs(
                self::BERKAS_DIR,
                time() . '_' . preg_replace('/[^A-Za-z0-9]/', '', $user->nim) . '_bebas_prodi.pdf',
                'local'
            );
        }

        $data = [
            'user_id'        => $user->id,
            'nama_mahasiswa' => $user->name,
            'jalur'          => $kelayakan['jalur'],
            'link_poster'    => $validated['link_poster'],
            'link_linkedin'  => $validated['link_linkedin'],
            'link_program'   => $validated['link_program'] ?? null,
            'link_jurnal'    => $isJurnal ? $validated['link_jurnal'] : null,
            'file_berkas'    => $filePath,
            'status'         => BebasProdi::STATUS_MENUNGGU,
            'komentar'       => null,
            'submitted_at'   => now(),
        ];

        if ($pengajuan) {
            $pengajuan->update($data);
            $message = 'Revisi pengajuan Bebas Prodi berhasil dikirim ulang!';
        } else {
            BebasProdi::create($data + ['nim' => $user->nim]);
            $message = 'Pengajuan Bebas Prodi berhasil dikirim! Silakan tunggu verifikasi admin.';
        }

        return redirect()->route('mahasiswa.bebas-prodi.index')->with('success', $message);
    }

    public function mahasiswaCetak(): Response
    {
        $user      = Auth::user();
        $pengajuan = $user->nim ? BebasProdi::where('nim', $user->nim)->first() : null;

        abort_unless($pengajuan && $pengajuan->status === BebasProdi::STATUS_DISETUJUI, 404);

        return $this->renderSurat($pengajuan);
    }

    // ───────────────────────── Admin ─────────────────────────

    public function index(Request $request): View
    {
        $status = $request->get('status');
        $search = trim((string) $request->get('q', ''));

        $query = BebasProdi::with(['verifier', 'kaprodi'])
            ->when(in_array($status, ['menunggu', 'disetujui', 'ditolak'], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nim', 'like', "%{$search}%")
                ->orWhere('nama_mahasiswa', 'like', "%{$search}%")
                ->orWhere('nomor_surat', 'like', "%{$search}%")));

        $pengajuans = $query
            ->orderByRaw("CASE status WHEN 'menunggu' THEN 0 WHEN 'ditolak' THEN 1 ELSE 2 END")
            ->orderByDesc('submitted_at')
            ->paginate(20)
            ->withQueryString();

        $counts = BebasProdi::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $nextNomor = max(
            (int) BebasProdi::max('nomor_surat') + 1,
            (int) config('bebas_prodi.nomor_awal')
        );
        $kaprodis       = Kaprodi::withCount('bebasProdis')->orderByRaw('mulai_menjabat IS NULL')->orderByDesc('mulai_menjabat')->orderByDesc('id')->get();
        $kaprodiSekarang = Kaprodi::untukTanggal();

        return view('bebas-prodi.index', compact('pengajuans', 'counts', 'status', 'search', 'nextNomor', 'kaprodis', 'kaprodiSekarang'));
    }

    public function verifikasi(Request $request, BebasProdi $bebasProdi): RedirectResponse
    {
        $validated = $request->validate([
            'status'        => ['required', 'in:disetujui,ditolak'],
            'komentar'      => ['nullable', 'required_if:status,ditolak', 'string', 'max:1000'],
            'tanggal_surat' => ['nullable', 'date'],
            'kaprodi_id'    => ['nullable', 'exists:kaprodis,id'],
        ], [
            'komentar.required_if' => 'Catatan penolakan wajib diisi agar mahasiswa tahu apa yang perlu diperbaiki.',
        ]);

        $before = $bebasProdi->status;

        if ($validated['status'] === BebasProdi::STATUS_DISETUJUI) {
            $bebasProdi->setujui(Auth::id(), $validated['tanggal_surat'] ?? null, isset($validated['kaprodi_id']) ? (int) $validated['kaprodi_id'] : null);
            $message = "Bebas Prodi {$bebasProdi->nama_mahasiswa} disetujui dengan nomor surat {$bebasProdi->nomor_surat_lengkap}.";
        } else {
            $bebasProdi->update([
                'status'      => BebasProdi::STATUS_DITOLAK,
                'komentar'    => $validated['komentar'],
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);
            $message = "Pengajuan Bebas Prodi {$bebasProdi->nama_mahasiswa} ditolak dengan catatan.";
        }

        ActivityLogger::log(
            'verifikasi',
            $bebasProdi,
            "Mengubah status Bebas Prodi {$bebasProdi->nama_mahasiswa} ({$bebasProdi->nim}) dari \"{$before}\" menjadi \"{$bebasProdi->status}\"." .
                ($bebasProdi->nomor_surat ? " Nomor surat: {$bebasProdi->nomor_surat_lengkap}." : '') .
                (!empty($validated['komentar']) && $bebasProdi->status === BebasProdi::STATUS_DITOLAK ? " Catatan: {$validated['komentar']}" : ''),
            ['before' => ['status' => $before], 'after' => ['status' => $bebasProdi->status]]
        );

        return back()->with('success', $message);
    }

    public function destroy(BebasProdi $bebasProdi): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        Storage::disk('local')->delete($bebasProdi->file_berkas);
        $nama = $bebasProdi->nama_mahasiswa;
        $bebasProdi->delete();

        ActivityLogger::log('deleted', null, "Menghapus pengajuan Bebas Prodi {$nama}.");

        return back()->with('success', "Pengajuan Bebas Prodi {$nama} dihapus.");
    }

    public function cetak(BebasProdi $bebasProdi): Response
    {
        abort_unless($bebasProdi->status === BebasProdi::STATUS_DISETUJUI, 404);

        return $this->renderSurat($bebasProdi);
    }

    // ───────────────────────── Kaprodi (super admin) ─────────────────────────

    public function kaprodiStore(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $data = $this->validateKaprodi($request, true);
        $kaprodi = Kaprodi::create(collect($data)->except('ttd')->all());
        $this->simpanTtd($request, $kaprodi);

        ActivityLogger::log('created', $kaprodi, "Menambah Kaprodi penandatangan {$kaprodi->nama} ({$kaprodi->masa_jabatan}).");

        return back()->with('success', "Kaprodi {$kaprodi->nama} berhasil ditambahkan.");
    }

    public function kaprodiUpdate(Request $request, Kaprodi $kaprodi): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $data = $this->validateKaprodi($request, false);
        $kaprodi->update(collect($data)->except('ttd')->all());
        $this->simpanTtd($request, $kaprodi);

        ActivityLogger::log('updated', $kaprodi, "Mengubah data Kaprodi penandatangan {$kaprodi->nama} ({$kaprodi->masa_jabatan}).");

        return back()->with('success', "Data Kaprodi {$kaprodi->nama} berhasil diperbarui.");
    }

    public function kaprodiDestroy(Kaprodi $kaprodi): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        if ($kaprodi->bebasProdis()->exists()) {
            return back()->with('error', "Kaprodi {$kaprodi->nama} sudah menandatangani surat sehingga tidak bisa dihapus. Isi tanggal selesai menjabat saja.");
        }

        Storage::disk('local')->delete((string) $kaprodi->ttd_path);
        $nama = $kaprodi->nama;
        $kaprodi->delete();

        ActivityLogger::log('deleted', null, "Menghapus Kaprodi penandatangan {$nama}.");

        return back()->with('success', "Kaprodi {$nama} dihapus.");
    }

    private function validateKaprodi(Request $request, bool $isNew): array
    {
        return $request->validate([
            'nama'             => ['required', 'string', 'max:255'],
            'mulai_menjabat'   => [$isNew ? 'required' : 'nullable', 'date'],
            'selesai_menjabat' => ['nullable', 'date', 'after_or_equal:mulai_menjabat'],
            'ttd'              => ['nullable', 'file', 'mimes:png', 'max:2048'],
        ], [
            'nama.required'                   => 'Nama Kaprodi (lengkap dengan gelar) wajib diisi.',
            'mulai_menjabat.required'         => 'Tanggal mulai menjabat wajib diisi.',
            'selesai_menjabat.after_or_equal' => 'Tanggal selesai menjabat tidak boleh sebelum tanggal mulai.',
            'ttd.mimes'                       => 'Tanda tangan harus berformat PNG (sebaiknya latar transparan).',
            'ttd.max'                         => 'Ukuran gambar tanda tangan maksimal 2 MB.',
        ]);
    }

    private function simpanTtd(Request $request, Kaprodi $kaprodi): void
    {
        if (!$request->hasFile('ttd')) {
            return;
        }

        Storage::disk('local')->delete((string) $kaprodi->ttd_path);
        $path = $request->file('ttd')->storeAs('bebas-prodi/ttd', $kaprodi->id . '_' . time() . '.png', 'local');
        $kaprodi->update(['ttd_path' => $path]);
    }

    // ───────────────────────── Shared ─────────────────────────

    /** Berkas PDF pengajuan — bisa dibuka pemiliknya atau admin/koordinator. */
    public function berkas(BebasProdi $bebasProdi): Response
    {
        $user    = Auth::user();
        $isOwner = $user->isMahasiswa() && $user->nim && $user->nim === $bebasProdi->nim;
        $isStaff = $user->isSuperAdmin() || $user->isKoordinator();
        abort_unless($isOwner || $isStaff, 403);

        $disk = Storage::disk('local');
        abort_unless($bebasProdi->file_berkas && $disk->exists($bebasProdi->file_berkas), 404);

        return response()->file($disk->path($bebasProdi->file_berkas), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function renderSurat(BebasProdi $bebasProdi): Response
    {
        // Surat lama (sebelum ada data kaprodi) jatuh ke kaprodi yang menjabat pada tanggal surat.
        $kaprodi = $bebasProdi->kaprodi ?? Kaprodi::untukTanggal($bebasProdi->tanggal_surat);

        $pdf = Pdf::loadView('bebas-prodi.surat', [
            'p'           => $bebasProdi,
            'nama'        => mb_convert_case(mb_strtolower($bebasProdi->nama_mahasiswa), MB_CASE_TITLE, 'UTF-8'),
            'tanggal'     => $bebasProdi->tanggal_surat->locale('id')->translatedFormat('j F Y'),
            'kaprodi'     => $kaprodi?->nama ?? '',
            'kopBase64'   => $this->imageBase64(public_path('images/kop_surat.png')),
            'ttdBase64'   => $kaprodi?->ttdBase64() ?? '',
        ])->setPaper('a4', 'portrait');

        $filename = 'Surat_Bebas_Prodi_' . $bebasProdi->nim . '.pdf';

        return $pdf->stream($filename);
    }

    private function imageBase64(string $path): string
    {
        if (!is_file($path)) {
            return '';
        }

        return 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path));
    }
}
