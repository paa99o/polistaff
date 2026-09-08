<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use PhpZip\ZipFile;
use RuntimeException;
use Throwable;

class SystemRestoreService
{
    public function __construct(private readonly SystemBackupService $backup) {}

    /** @throws Throwable */
    public function restore(string $archivePath, array $manifest): array
    {
        $safetyBackup = $this->createSafetyBackup();
        $previousMaintenance = SystemSetting::getValue('maintenance_enabled', '0');
        SystemSetting::setValue('maintenance_enabled', '1');

        try {
            $zip = new ZipFile;
            $zip->openFile($archivePath);
            $workspace = 'restore-workspaces/'.Str::uuid();

            try {
                $tables = $this->readAndValidateData($zip, $manifest);
                $this->stageUploads($zip, $manifest['files'], $workspace.'/incoming');
                $currentFiles = $this->copyCurrentUploads($workspace.'/rollback');

                try {
                    $this->replaceUploads($workspace.'/incoming', array_column($manifest['files'], 'path'));
                    $this->restoreDatabase($tables);
                    $restoredFiles = count($manifest['files']);
                } catch (Throwable $exception) {
                    $this->replaceUploads($workspace.'/rollback', $currentFiles);

                    throw $exception;
                }
            } finally {
                $zip->close();
                Storage::disk('local')->deleteDirectory($workspace);
            }
        } finally {
            SystemSetting::setValue('maintenance_enabled', $previousMaintenance);
        }

        return [
            'safety_backup' => $safetyBackup,
            'tables' => count($tables),
            'records' => collect($tables)->sum(fn (array $table): int => count($table['rows'])),
            'files' => $restoredFiles,
        ];
    }

    private function createSafetyBackup(): string
    {
        $directory = 'system-backups';
        Storage::disk('local')->makeDirectory($directory);
        $relativePath = $directory.'/pre-restore-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.zip';

        $this->backup->writeTo(Storage::disk('local')->path($relativePath), $this->backup->snapshot());

        return $relativePath;
    }

    /** @throws JsonException */
    private function readAndValidateData(ZipFile $zip, array $manifest): array
    {
        $payload = json_decode(
            $zip->getEntryContents(SystemBackupService::DATABASE_DATA_PATH),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($payload)
            || ($payload['format_version'] ?? null) !== SystemBackupService::FORMAT_VERSION
            || ! is_array($payload['tables'] ?? null)) {
            throw new RuntimeException('Struktur data pemulihan tidak sah.');
        }

        $manifestTables = collect($manifest['tables'])->keyBy('name');
        $tables = [];

        foreach ($payload['tables'] as $table) {
            $name = $table['name'] ?? null;
            $rows = $table['rows'] ?? null;

            if (! is_string($name) || ! in_array($name, SystemBackupService::TABLES, true) || ! is_array($rows)) {
                throw new RuntimeException('Sandaran mengandungi jadual yang tidak dibenarkan.');
            }

            if (! Schema::hasTable($name) || ! $manifestTables->has($name)) {
                throw new RuntimeException("Jadual {$name} tidak serasi dengan sistem semasa.");
            }

            if ((int) $manifestTables[$name]['records'] !== count($rows)) {
                throw new RuntimeException("Bilangan rekod jadual {$name} tidak sepadan.");
            }

            $columns = Schema::getColumnListing($name);
            sort($columns);
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    throw new RuntimeException("Rekod jadual {$name} tidak sah.");
                }

                $rowColumns = array_keys($row);
                sort($rowColumns);
                if ($rowColumns !== $columns) {
                    throw new RuntimeException("Struktur kolum jadual {$name} tidak serasi.");
                }
            }

            $tables[] = ['name' => $name, 'rows' => $rows];
        }

        if (collect($tables)->pluck('name')->sort()->values()->all() !== $manifestTables->keys()->sort()->values()->all()) {
            throw new RuntimeException('Senarai jadual data tidak sepadan dengan manifest.');
        }

        return $tables;
    }

    private function restoreDatabase(array $tables): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($tables): void {
                foreach (array_reverse($tables) as $table) {
                    DB::table($table['name'])->delete();
                }

                foreach ($tables as $table) {
                    foreach (array_chunk($table['rows'], 250) as $rows) {
                        if ($rows !== []) {
                            DB::table($table['name'])->insert($rows);
                        }
                    }
                }
            }, 3);
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function stageUploads(ZipFile $zip, array $files, string $directory): void
    {
        foreach ($files as $file) {
            $stream = $zip->getEntryStream('uploads/'.$file['path']);

            try {
                if (! Storage::disk('local')->writeStream($directory.'/'.$file['path'], $stream)) {
                    throw new RuntimeException("Fail {$file['path']} gagal disediakan untuk pemulihan.");
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
    }

    private function copyCurrentUploads(string $directory): array
    {
        $public = Storage::disk('public');
        $files = collect($public->allFiles())
            ->reject(fn (string $path): bool => str_starts_with(basename($path), '.'))
            ->values()
            ->all();

        foreach ($files as $path) {
            $stream = $public->readStream($path);
            if ($stream === false) {
                throw new RuntimeException("Fail semasa {$path} tidak dapat disalin.");
            }

            try {
                if (! Storage::disk('local')->writeStream($directory.'/'.$path, $stream)) {
                    throw new RuntimeException("Fail semasa {$path} tidak dapat disimpan untuk rollback.");
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        return $files;
    }

    private function replaceUploads(string $sourceDirectory, array $files): void
    {
        $public = Storage::disk('public');
        $public->delete(collect($public->allFiles())
            ->reject(fn (string $path): bool => str_starts_with(basename($path), '.'))
            ->all());

        foreach ($files as $path) {
            $stream = Storage::disk('local')->readStream($sourceDirectory.'/'.$path);
            if ($stream === false) {
                throw new RuntimeException("Fail {$path} tidak dapat dibaca semasa pemulihan.");
            }

            try {
                if (! $public->writeStream($path, $stream)) {
                    throw new RuntimeException("Fail {$path} gagal dipulihkan.");
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
    }
}
