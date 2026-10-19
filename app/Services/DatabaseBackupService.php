<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Pure-PHP MySQL backup/restore — deliberately avoids `mysqldump`/`mysql`
 * CLI + Symfony Process entirely, because many shared-hosting PHP builds
 * (e.g. Hostinger) disable proc_open/exec/shell_exec for security, which
 * makes any shell-based approach fail with
 * "The Process class relies on proc_open, which is not available...".
 * Everything here goes through the existing PDO connection instead.
 */
class DatabaseBackupService
{
    /**
     * Dump every table (structure + data) in the current database connection
     * to a plain .sql file, in a format any standard MySQL/phpMyAdmin import
     * can also read (not just this app's own restore()).
     */
    public function dumpToFile(string $path): void
    {
        $pdo = DB::connection()->getPdo();
        $database = DB::connection()->getDatabaseName();

        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Tidak dapat menulis file backup di server.');
        }

        try {
            fwrite($handle, "-- Sistem Informasi Skripsi TI — database backup\n");
            fwrite($handle, "-- Generated: " . now()->toDateTimeString() . "\n");
            fwrite($handle, "-- Database: {$database}\n\n");
            fwrite($handle, "SET NAMES utf8mb4;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = collect(DB::select('SHOW TABLES'))
                ->map(fn ($row) => array_values((array) $row)[0])
                ->values();

            foreach ($tables as $table) {
                $this->dumpTableStructure($handle, $table);
                $this->dumpTableData($handle, $pdo, $table);
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }
    }

    private function dumpTableStructure($handle, string $table): void
    {
        $create = DB::select("SHOW CREATE TABLE `{$table}`")[0];
        // Column name differs for views ("Create View") vs tables ("Create Table") —
        // this app has no views, but stay defensive rather than assume the key.
        $createSql = $create->{'Create Table'} ?? $create->{'Create View'} ?? null;

        if ($createSql === null) {
            return;
        }

        fwrite($handle, "-- Structure for `{$table}`\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($handle, $createSql . ";\n\n");
    }

    private function dumpTableData($handle, \PDO $pdo, string $table): void
    {
        $count = DB::table($table)->count();
        if ($count === 0) {
            return;
        }

        fwrite($handle, "-- Data for `{$table}` ({$count} rows)\n");

        // Plain get() rather than chunk() — every table in this app is small
        // enough (academic-scale data, not big-data volumes) that loading a
        // table at a time is simpler and doesn't need an ORDER BY to stay
        // consistent across pages.
        $rows = DB::table($table)->get();

        foreach ($rows as $row) {
            $data = (array) $row;
            $columns = array_map(fn ($c) => "`{$c}`", array_keys($data));
            $values = array_map(function ($value) use ($pdo) {
                if ($value === null) {
                    return 'NULL';
                }
                if (is_int($value) || is_float($value)) {
                    return $value;
                }
                return $pdo->quote((string) $value);
            }, array_values($data));

            fwrite(
                $handle,
                'INSERT INTO `' . $table . '` (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n"
            );
        }

        fwrite($handle, "\n");
    }

    /**
     * Execute a raw SQL dump (as produced by dumpToFile(), or a standard
     * mysqldump/phpMyAdmin export) against the current database connection,
     * statement by statement.
     */
    public function restoreFromSql(string $sql): void
    {
        $statements = $this->splitStatements($sql);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($statements as $statement) {
                if ($statement === '') {
                    continue;
                }
                DB::unprepared($statement);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Split a multi-statement SQL dump into individual statements, respecting
     * quoted strings/backtick identifiers/comments so that semicolons inside
     * a value (or a CREATE TABLE's internal newlines) never split a statement
     * in the wrong place. A small hand-rolled state machine rather than a
     * regex, since quoting/escaping rules aren't regular.
     *
     * @return array<int, string>
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $length = strlen($sql);

        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = ($i + 1 < $length) ? $sql[$i + 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                }
                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                if ($char === '-' && $next === '-') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
                if ($char === '#') {
                    $inLineComment = true;
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $i++;
                    continue;
                }
            }

            if ($char === "'" && !$inDoubleQuote && !$inBacktick) {
                $current .= $char;
                if ($inSingleQuote && $next === "'") {
                    // doubled '' escape inside a single-quoted string
                    $current .= $next;
                    $i++;
                    continue;
                }
                if (!$inSingleQuote || !$this->isBackslashEscaped($sql, $i)) {
                    $inSingleQuote = !$inSingleQuote;
                }
                continue;
            }

            if ($char === '"' && !$inSingleQuote && !$inBacktick) {
                $current .= $char;
                if ($inDoubleQuote && $next === '"') {
                    $current .= $next;
                    $i++;
                    continue;
                }
                if (!$inDoubleQuote || !$this->isBackslashEscaped($sql, $i)) {
                    $inDoubleQuote = !$inDoubleQuote;
                }
                continue;
            }

            if ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
                $inBacktick = !$inBacktick;
                $current .= $char;
                continue;
            }

            if ($char === ';' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $trimmed = trim($current);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    /**
     * Whether the quote character at $pos is escaped by an odd number of
     * immediately-preceding backslashes (\\' is a literal backslash + closed
     * quote, \' is an escaped quote).
     */
    private function isBackslashEscaped(string $sql, int $pos): bool
    {
        $backslashes = 0;
        $j = $pos - 1;
        while ($j >= 0 && $sql[$j] === '\\') {
            $backslashes++;
            $j--;
        }

        return $backslashes % 2 === 1;
    }
}
