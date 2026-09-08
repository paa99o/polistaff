<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use JsonException;
use PhpZip\ZipFile;
use Throwable;

class BackupInspectionService
{
    private const MAX_ENTRIES = 20000;

    private const MAX_ENTRY_BYTES = 536870912;

    private const MAX_TOTAL_BYTES = 2147483648;

    private const MAX_MANIFEST_BYTES = 5242880;

    public function inspect(UploadedFile $file): array
    {
        $result = [
            'is_valid' => false,
            'errors' => [],
            'warnings' => [],
            'manifest' => null,
            'summary' => [
                'filename' => $file->getClientOriginalName(),
                'archive_size_bytes' => (int) ($file->getSize() ?: 0),
                'entry_count' => 0,
                'database_verified' => false,
                'database_data_verified' => false,
                'files_verified' => 0,
                'safe_paths' => false,
                'compatible_driver' => false,
            ],
        ];

        $path = $file->getRealPath();
        if ($path === false) {
            $result['errors'][] = 'Fail yang dimuat naik tidak dapat dibaca.';

            return $this->finalize($result);
        }

        $zip = new ZipFile;

        try {
            $zip->openFile($path);
            $entries = $zip->getListFiles();
            $result['summary']['entry_count'] = count($entries);

            $this->validateArchiveLimits($zip, $entries, $result);
            $this->validateEntryPaths($entries, $result);

            if (! $zip->hasEntry('manifest.json')) {
                $result['errors'][] = 'Fail manifest.json tidak ditemui dalam arkib.';

                return $this->finalize($result);
            }

            if ($zip->getEntry('manifest.json')->getUncompressedSize() > self::MAX_MANIFEST_BYTES) {
                $result['errors'][] = 'Saiz manifest.json melebihi had keselamatan.';

                return $this->finalize($result);
            }

            try {
                $manifest = json_decode($zip->getEntryContents('manifest.json'), true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $result['errors'][] = 'Kandungan manifest.json tidak sah.';

                return $this->finalize($result);
            }

            if (! is_array($manifest)) {
                $result['errors'][] = 'Struktur manifest.json tidak sah.';

                return $this->finalize($result);
            }

            $result['manifest'] = $manifest;
            $this->validateManifest($zip, $entries, $manifest, $result);
        } catch (Throwable) {
            $result['errors'][] = 'Fail ini bukan arkib ZIP yang sah atau kandungannya telah rosak.';
        } finally {
            try {
                $zip->close();
            } catch (Throwable) {
                // The inspection result already records an unreadable archive.
            }
        }

        return $this->finalize($result);
    }

    private function finalize(array $result): array
    {
        $result['errors'] = array_values(array_unique($result['errors']));
        $result['warnings'] = array_values(array_unique($result['warnings']));
        $result['is_valid'] = $result['errors'] === [];

        return $result;
    }

    private function validateArchiveLimits(ZipFile $zip, array $entries, array &$result): void
    {
        if (count($entries) > self::MAX_ENTRIES) {
            $result['errors'][] = 'Arkib mengandungi terlalu banyak fail untuk diperiksa dengan selamat.';
        }

        $totalBytes = 0;
        foreach ($entries as $entryName) {
            $entry = $zip->getEntry($entryName);
            if ($entry->isDirectory()) {
                continue;
            }

            $size = $entry->getUncompressedSize();
            $totalBytes += $size;

            if ($size > self::MAX_ENTRY_BYTES) {
                $result['errors'][] = "Fail {$entryName} melebihi had saiz keselamatan.";
            }
        }

        if ($totalBytes > self::MAX_TOTAL_BYTES) {
            $result['errors'][] = 'Jumlah saiz kandungan arkib melebihi had keselamatan.';
        }
    }

    private function validateEntryPaths(array $entries, array &$result): void
    {
        foreach ($entries as $entryName) {
            if (! $this->isSafeRelativePath($entryName)) {
                $result['errors'][] = "Laluan fail berbahaya dikesan: {$entryName}";
            }

            $normalized = strtolower(str_replace('\\', '/', $entryName));
            if ($normalized === '.env' || str_starts_with($normalized, 'config/')) {
                $result['errors'][] = "Fail sensitif tidak sepatutnya berada dalam sandaran: {$entryName}";
            }
        }

        $result['summary']['safe_paths'] = ! collect($result['errors'])
            ->contains(fn (string $error): bool => str_contains($error, 'Laluan fail berbahaya') || str_contains($error, 'Fail sensitif'));
    }

    private function validateManifest(ZipFile $zip, array $entries, array $manifest, array &$result): void
    {
        if (($manifest['application'] ?? null) !== SystemBackupService::APPLICATION) {
            $result['errors'][] = 'Arkib ini bukan sandaran sistem POLISTAFF.';
        }

        if (($manifest['format_version'] ?? null) !== SystemBackupService::FORMAT_VERSION) {
            $result['errors'][] = 'Versi format sandaran tidak disokong oleh sistem ini.';
        }

        $generatedAt = $manifest['generated_at'] ?? null;
        if (! is_string($generatedAt) || trim($generatedAt) === '' || strtotime($generatedAt) === false) {
            $result['errors'][] = 'Tarikh penjanaan sandaran tidak sah.';
        }

        $driver = $manifest['database_driver'] ?? null;
        $result['summary']['compatible_driver'] = is_string($driver) && $driver === DB::getDriverName();
        if (! $result['summary']['compatible_driver']) {
            $result['errors'][] = 'Jenis database sandaran tidak sepadan dengan database sistem semasa.';
        }

        $tables = $manifest['tables'] ?? null;
        if (! is_array($tables)) {
            $result['errors'][] = 'Senarai jadual database dalam manifest tidak sah.';
        } else {
            $tableNames = [];
            foreach ($tables as $table) {
                if (! is_array($table) || ! is_string($table['name'] ?? null) || ! is_numeric($table['records'] ?? null)) {
                    $result['errors'][] = 'Maklumat jadual database dalam manifest tidak lengkap.';

                    continue;
                }
                $tableNames[] = $table['name'];
            }

            foreach (SystemBackupService::TABLES as $requiredTable) {
                if (DB::getSchemaBuilder()->hasTable($requiredTable) && ! in_array($requiredTable, $tableNames, true)) {
                    $result['errors'][] = "Jadual penting tidak tersenarai dalam sandaran: {$requiredTable}";
                }
            }
        }

        $database = $manifest['database'] ?? null;
        if (! is_array($database)
            || ($database['path'] ?? null) !== SystemBackupService::DATABASE_PATH
            || ! is_numeric($database['size_bytes'] ?? null)
            || ! $this->isSha256($database['sha256'] ?? null)
            || ($database['data_path'] ?? null) !== SystemBackupService::DATABASE_DATA_PATH
            || ! is_numeric($database['data_size_bytes'] ?? null)
            || ! $this->isSha256($database['data_sha256'] ?? null)) {
            $result['errors'][] = 'Maklumat pengesahan database dalam manifest tidak lengkap.';
        } elseif (! $zip->hasEntry(SystemBackupService::DATABASE_PATH)) {
            $result['errors'][] = 'Fail database/polistaff.sql tidak ditemui dalam arkib.';
        } else {
            $result['summary']['database_verified'] = $this->verifyEntry($zip, SystemBackupService::DATABASE_PATH, $database, $result);

            if (! $zip->hasEntry(SystemBackupService::DATABASE_DATA_PATH)) {
                $result['errors'][] = 'Fail database/polistaff.json tidak ditemui dalam arkib.';
            } else {
                $result['summary']['database_data_verified'] = $this->verifyEntry($zip, SystemBackupService::DATABASE_DATA_PATH, [
                    'size_bytes' => $database['data_size_bytes'],
                    'sha256' => $database['data_sha256'],
                ], $result);
            }
        }

        $files = $manifest['files'] ?? null;
        $expectedEntries = ['manifest.json', 'README.txt', SystemBackupService::DATABASE_PATH, SystemBackupService::DATABASE_DATA_PATH];
        if (! is_array($files)) {
            $result['errors'][] = 'Senarai fail upload dalam manifest tidak sah.';
        } else {
            foreach ($files as $file) {
                if (! is_array($file)
                    || ! is_string($file['path'] ?? null)
                    || ! is_numeric($file['size_bytes'] ?? null)
                    || ! $this->isSha256($file['sha256'] ?? null)) {
                    $result['errors'][] = 'Maklumat salah satu fail upload dalam manifest tidak lengkap.';

                    continue;
                }

                $archivePath = 'uploads/'.str_replace('\\', '/', ltrim($file['path'], '/\\'));
                if (! $this->isSafeRelativePath($archivePath)) {
                    $result['errors'][] = "Laluan fail upload tidak selamat: {$file['path']}";

                    continue;
                }

                $expectedEntries[] = $archivePath;
                if (! $zip->hasEntry($archivePath)) {
                    $result['errors'][] = "Fail upload tidak ditemui: {$file['path']}";

                    continue;
                }

                if ($this->verifyEntry($zip, $archivePath, $file, $result)) {
                    $result['summary']['files_verified']++;
                }
            }
        }

        foreach ($entries as $entryName) {
            if (! $zip->isDirectory($entryName) && ! in_array($entryName, $expectedEntries, true)) {
                $result['warnings'][] = "Fail tambahan tidak tersenarai dalam manifest: {$entryName}";
            }
        }

        $this->validateTotals($manifest, $result);
    }

    private function verifyEntry(ZipFile $zip, string $entryName, array $metadata, array &$result): bool
    {
        $actualSize = $zip->getEntry($entryName)->getUncompressedSize();
        if ($actualSize !== (int) $metadata['size_bytes']) {
            $result['errors'][] = "Saiz fail tidak sepadan dengan manifest: {$entryName}";

            return false;
        }

        $stream = $zip->getEntryStream($entryName);
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);

        if (! hash_equals(strtolower($metadata['sha256']), hash_final($hash))) {
            $result['errors'][] = "Checksum fail tidak sepadan: {$entryName}";

            return false;
        }

        return true;
    }

    private function validateTotals(array $manifest, array &$result): void
    {
        if (! is_array($manifest['totals'] ?? null) || ! is_array($manifest['tables'] ?? null) || ! is_array($manifest['files'] ?? null)) {
            $result['errors'][] = 'Ringkasan jumlah sandaran dalam manifest tidak sah.';

            return;
        }

        $expected = [
            'tables' => count($manifest['tables']),
            'records' => collect($manifest['tables'])->sum(fn ($table) => is_array($table) ? (int) ($table['records'] ?? 0) : 0),
            'files' => count($manifest['files']),
            'file_bytes' => collect($manifest['files'])->sum(fn ($file) => is_array($file) ? (int) ($file['size_bytes'] ?? 0) : 0),
        ];

        foreach ($expected as $key => $value) {
            if (! array_key_exists($key, $manifest['totals']) || (int) $manifest['totals'][$key] !== $value) {
                $result['errors'][] = 'Ringkasan jumlah sandaran tidak sepadan dengan kandungan manifest.';
                break;
            }
        }
    }

    private function isSafeRelativePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);
        $segments = explode('/', trim($normalized, '/'));

        return $path !== ''
            && ! str_starts_with($normalized, '/')
            && ! preg_match('/^[A-Za-z]:/', $normalized)
            && ! in_array('..', $segments, true)
            && ! str_contains($normalized, "\0");
    }

    private function isSha256(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-f0-9]{64}$/i', $value) === 1;
    }
}
