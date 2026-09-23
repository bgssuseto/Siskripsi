<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    private const DISK = 'local';
    private const DIR = 'backups';

    /**
     * List existing backup files, newest first.
     */
    public function index(): View
    {
        Storage::disk(self::DISK)->makeDirectory(self::DIR);

        $files = collect(Storage::disk(self::DISK)->files(self::DIR))
            ->filter(fn ($path) => str_ends_with($path, '.sql'))
            ->map(function ($path) {
                return [
                    'name'       => basename($path),
                    'size'       => Storage::disk(self::DISK)->size($path),
                    'created_at' => \Illuminate\Support\Carbon::createFromTimestamp(Storage::disk(self::DISK)->lastModified($path)),
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        return view('backup.index', compact('files'));
    }

    /**
     * Run mysqldump and store a timestamped .sql backup of the current database.
     */
    public function create(Request $request): RedirectResponse
    {
        Storage::disk(self::DISK)->makeDirectory(self::DIR);

        $connection = config('database.default');
        $db = config("database.connections.{$connection}");

        $filename = 'backup_' . now()->format('Ymd_His') . '.sql';
        $fullPath = Storage::disk(self::DISK)->path(self::DIR . '/' . $filename);

        $mysqldumpBin = config('services.mysql.mysqldump_path', 'mysqldump');

        $command = [
            $mysqldumpBin,
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--user=' . $db['username'],
            '--single-transaction',
            '--skip-lock-tables',
            '--result-file=' . $fullPath,
            $db['database'],
        ];

        $process = new Process($command, null, ['MYSQL_PWD' => $db['password']]);
        $process->setTimeout(300);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
            return back()->with('error', 'Gagal membuat backup: pastikan perintah "mysqldump" tersedia di server ini. (' . $e->getMessage() . ')');
        }

        ActivityLogger::log('created', null, "Membuat backup database: {$filename}.");

        return back()->with('success', "Backup database berhasil dibuat: {$filename}");
    }

    /**
     * Download a previously created backup file.
     */
    public function download(string $filename)
    {
        $this->validateFilename($filename);

        $path = self::DIR . '/' . $filename;
        if (!Storage::disk(self::DISK)->exists($path)) {
            abort(404, 'File backup tidak ditemukan.');
        }

        return Storage::disk(self::DISK)->download($path);
    }

    /**
     * Delete an old backup file to free up server storage.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $this->validateFilename($filename);

        $path = self::DIR . '/' . $filename;
        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
            ActivityLogger::log('deleted', null, "Menghapus file backup database: {$filename}.");
        }

        return back()->with('success', "File backup {$filename} berhasil dihapus.");
    }

    /**
     * Restore the database from an uploaded .sql file. This OVERWRITES all
     * current data with the contents of the uploaded dump — irreversible
     * without a fresh backup of the current state first.
     */
    public function restore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sql_file'   => ['required', 'file', 'mimes:sql,txt', 'max:51200'],
            'confirm'    => ['required', 'in:RESTORE'],
        ], [
            'confirm.in' => 'Anda harus mengetik "RESTORE" untuk mengonfirmasi tindakan ini.',
        ]);

        $connection = config('database.default');
        $db = config("database.connections.{$connection}");

        $uploadedPath = $validated['sql_file'] instanceof \Illuminate\Http\UploadedFile
            ? $request->file('sql_file')->getRealPath()
            : null;

        if (!$uploadedPath) {
            return back()->with('error', 'File SQL tidak valid.');
        }

        // The uploaded file is piped verbatim as stdin into the `mysql` CLI — a
        // legitimate mysqldump export never needs statements that write files or
        // grant privileges. If the DB user happens to have FILE privilege (common
        // on shared hosting), an unchecked upload could otherwise turn this into
        // arbitrary server file writes. Reject anything that isn't a plain data
        // restore, even though this endpoint is already super_admin-only.
        $dangerousPatterns = [
            '/\bINTO\s+OUTFILE\b/i',
            '/\bINTO\s+DUMPFILE\b/i',
            '/\bLOAD_FILE\s*\(/i',
            '/\bLOAD\s+DATA\s+(LOCAL\s+)?INFILE\b/i',
            '/\bCREATE\s+USER\b/i',
            '/\bGRANT\s+/i',
            '/\bDROP\s+DATABASE\b/i',
            '/\bDROP\s+SCHEMA\b/i',
            '/\bSET\s+GLOBAL\b/i',
        ];
        $sqlContent = file_get_contents($uploadedPath);
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $sqlContent)) {
                return back()->with('error', 'File SQL ditolak: mengandung statement yang tidak diizinkan untuk restore (mis. INTO OUTFILE/LOAD_FILE/GRANT/DROP DATABASE). Pastikan file ini benar-benar hasil backup mysqldump, bukan file lain.');
            }
        }
        unset($sqlContent);

        $mysqlBin = config('services.mysql.mysql_path', 'mysql');

        $command = [
            $mysqlBin,
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--user=' . $db['username'],
            $db['database'],
        ];

        $process = new Process($command, null, ['MYSQL_PWD' => $db['password']]);
        $process->setInput(fopen($uploadedPath, 'r'));
        $process->setTimeout(300);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            return back()->with('error', 'Gagal me-restore database: ' . $e->getMessage());
        }

        ActivityLogger::log('updated', null, 'Database di-restore dari file backup yang diunggah — SELURUH data sebelumnya ditimpa.');

        return back()->with('success', 'Database berhasil di-restore dari file yang diunggah.');
    }

    /**
     * Guard against path traversal — only allow the exact filename pattern
     * this controller itself generates.
     */
    private function validateFilename(string $filename): void
    {
        if (!preg_match('/^backup_\d{8}_\d{6}\.sql$/', $filename)) {
            abort(404);
        }
    }
}
