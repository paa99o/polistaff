<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JsonException;
use ZipStream\ZipStream;

class SystemBackupService
{
    public const FORMAT_VERSION = 2;

    public const APPLICATION = 'POLISTAFF';

    public const DATABASE_PATH = 'database/polistaff.sql';

    public const DATABASE_DATA_PATH = 'database/polistaff.json';

    public const TABLES = [
        'users',
        'activities',
        'activity_registrations',
        'attendances',
        'transactions',
        'payment_submissions',
        'member_fee_bills',
        'expense_claims',
        'notifications',
        'feedbacks',
        'member_documents',
        'polimart_items',
        'system_settings',
        'audit_logs',
    ];

    /** @throws JsonException */
    public function snapshot(): array
    {
        $tableSnapshots = collect(self::TABLES)
            ->filter(fn (string $table): bool => DB::getSchemaBuilder()->hasTable($table))
            ->map(function (string $table): array {
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                $rows = DB::table($table)
                    ->orderBy($columns[0])
                    ->get()
                    ->map(fn (object $row): array => (array) $row)
                    ->all();

                return ['name' => $table, 'rows' => $rows];
            })
            ->values()
            ->all();

        $tables = collect($tableSnapshots)
            ->map(fn (array $table): array => [
                'name' => $table['name'],
                'records' => count($table['rows']),
            ])
            ->all();

        $files = collect(Storage::disk('public')->allFiles())
            ->reject(fn (string $path): bool => str_starts_with(basename($path), '.'))
            ->map(fn (string $path): array => [
                'path' => $path,
                'size_bytes' => Storage::disk('public')->size($path),
                'sha256' => hash_file('sha256', Storage::disk('public')->path($path)),
            ])
            ->values()
            ->all();

        $databaseSql = $this->databaseSql(array_column($tables, 'name'));
        $databaseData = json_encode([
            'format_version' => self::FORMAT_VERSION,
            'tables' => $tableSnapshots,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $manifest = [
            'format_version' => self::FORMAT_VERSION,
            'application' => self::APPLICATION,
            'generated_at' => now()->toIso8601String(),
            'database_driver' => DB::getDriverName(),
            'database' => [
                'path' => self::DATABASE_PATH,
                'size_bytes' => strlen($databaseSql),
                'sha256' => hash('sha256', $databaseSql),
                'data_path' => self::DATABASE_DATA_PATH,
                'data_size_bytes' => strlen($databaseData),
                'data_sha256' => hash('sha256', $databaseData),
            ],
            'tables' => $tables,
            'files' => $files,
            'totals' => [
                'tables' => count($tables),
                'records' => collect($tables)->sum('records'),
                'files' => count($files),
                'file_bytes' => collect($files)->sum('size_bytes'),
            ],
        ];

        return [
            'manifest' => $manifest,
            'database_sql' => $databaseSql,
            'database_data' => $databaseData,
        ];
    }

    public function stream(array $snapshot): void
    {
        $zip = new ZipStream(sendHttpHeaders: false);

        $this->writeArchive($zip, $snapshot);
    }

    public function writeTo(string $path, array $snapshot): void
    {
        $stream = fopen($path, 'wb');
        if ($stream === false) {
            throw new \RuntimeException('Unable to create the safety backup file.');
        }

        try {
            $zip = new ZipStream(outputStream: $stream, sendHttpHeaders: false);
            $this->writeArchive($zip, $snapshot);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function writeArchive(ZipStream $zip, array $snapshot): void
    {
        $manifest = $snapshot['manifest'];

        $zip->addFile(
            fileName: self::DATABASE_PATH,
            data: $snapshot['database_sql'],
        );
        $zip->addFile(
            fileName: self::DATABASE_DATA_PATH,
            data: $snapshot['database_data'],
        );
        $zip->addFile(
            fileName: 'manifest.json',
            data: json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
        $zip->addFile(
            fileName: 'README.txt',
            data: "POLISTAFF SYSTEM BACKUP\n\nDatabase records are stored in database/polistaff.json and database/polistaff.sql.\nUploaded files are stored in uploads/.\nRun the matching application migrations before restoring data.\nThis archive does not contain .env or application credentials.\n",
        );

        foreach ($manifest['files'] as $file) {
            $path = $file['path'];

            if (str_contains($path, '..') || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $zip->addFileFromPath(
                fileName: 'uploads/'.str_replace('\\', '/', ltrim($path, '/\\')),
                path: Storage::disk('public')->path($path),
            );
        }

        $zip->finish();
    }

    private function databaseSql(array $tables): string
    {
        $sql = '-- POLISTAFF database backup generated at '.now()->toDateTimeString().PHP_EOL;
        $sql .= '-- Apply the application migrations before importing this file.'.PHP_EOL.PHP_EOL;
        $sql .= 'BEGIN TRANSACTION;'.PHP_EOL.PHP_EOL;

        foreach ($tables as $table) {
            $columns = DB::getSchemaBuilder()->getColumnListing($table);
            $quotedColumns = implode(', ', array_map($this->quoteIdentifier(...), $columns));
            $quotedTable = $this->quoteIdentifier($table);

            $sql .= '-- Table: '.$table.PHP_EOL;

            foreach (DB::table($table)->orderBy($columns[0])->get() as $row) {
                $values = array_map(
                    fn (mixed $value): string => $value === null ? 'NULL' : DB::getPdo()->quote((string) $value),
                    array_values((array) $row),
                );
                $sql .= 'INSERT INTO '.$quotedTable.' ('.$quotedColumns.') VALUES ('.implode(', ', $values).');'.PHP_EOL;
            }

            $sql .= PHP_EOL;
        }

        return $sql.'COMMIT;'.PHP_EOL;
    }

    private function quoteIdentifier(string $identifier): string
    {
        $quote = DB::getDriverName() === 'mysql' ? '`' : '"';

        return $quote.str_replace($quote, $quote.$quote, $identifier).$quote;
    }
}
