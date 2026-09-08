<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\BackupInspectionService;
use App\Services\SystemBackupService;
use App\Services\SystemRestoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function export(SystemBackupService $backup): StreamedResponse
    {
        $filename = 'polistaff-backup-'.now()->format('Ymd-His').'.zip';

        $audit = AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'exported',
            'module' => 'Backup',
            'description' => 'Exported full system backup.',
            'changes' => ['filename' => $filename],
            'ip_address' => request()->ip(),
        ]);
        $snapshot = $backup->snapshot();
        $manifest = $snapshot['manifest'];
        $audit->update(['changes' => ['filename' => $filename, 'totals' => $manifest['totals']]]);

        return response()->streamDownload(
            fn () => $backup->stream($snapshot),
            $filename,
            ['Content-Type' => 'application/zip'],
        );
    }

    public function inspect(Request $request, BackupInspectionService $inspector): View|RedirectResponse
    {
        $validated = $request->validate([
            'backup_file' => ['required', 'file', 'extensions:zip', 'max:524288'],
        ], [
            'backup_file.required' => 'Sila pilih fail sandaran untuk diperiksa.',
            'backup_file.file' => 'Fail sandaran yang dipilih tidak sah.',
            'backup_file.extensions' => 'Fail sandaran mestilah dalam format ZIP.',
            'backup_file.max' => 'Saiz fail sandaran tidak boleh melebihi 512 MB.',
        ]);

        $file = $validated['backup_file'];
        $inspection = $inspector->inspect($file);

        if ($inspection['is_valid']) {
            $token = (string) Str::uuid();
            $relativePath = 'backup-previews/'.auth()->id().'/'.$token.'.zip';
            $stream = fopen($file->getRealPath(), 'rb');
            $stored = $stream !== false && Storage::disk('local')->writeStream($relativePath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (! $stored) {
                return to_route('settings.edit')->with('error', 'Fail preview tidak dapat disimpan. Sila cuba semula.');
            }

            session()->put('backup_restore_previews.'.$token, [
                'path' => $relativePath,
                'sha256' => hash_file('sha256', Storage::disk('local')->path($relativePath)),
                'expires_at' => now()->addMinutes(30)->timestamp,
            ]);
            $inspection['restore_token'] = $token;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'inspected',
            'module' => 'Backup',
            'description' => 'Inspected a system backup without restoring it.',
            'changes' => [
                'filename' => $file->getClientOriginalName(),
                'is_valid' => $inspection['is_valid'],
                'errors' => count($inspection['errors']),
                'warnings' => count($inspection['warnings']),
            ],
            'ip_address' => $request->ip(),
        ]);

        if (isset($inspection['restore_token'])) {
            return to_route('backup.preview', $inspection['restore_token']);
        }

        return view('settings.backup-preview', compact('inspection'));
    }

    public function preview(string $token, BackupInspectionService $inspector): View|RedirectResponse
    {
        $preview = session('backup_restore_previews.'.$token);
        if (! Str::isUuid($token)
            || ! is_array($preview)
            || ($preview['expires_at'] ?? 0) < now()->timestamp
            || ! Storage::disk('local')->exists($preview['path'] ?? '')) {
            return to_route('settings.edit')->with('error', 'Sesi preview telah tamat. Sila periksa semula fail sandaran.');
        }

        $path = Storage::disk('local')->path($preview['path']);
        $inspection = $inspector->inspect(new UploadedFile($path, 'polistaff-backup.zip', 'application/zip', null, true));
        $inspection['restore_token'] = $token;

        return view('settings.backup-preview', compact('inspection'));
    }

    public function restore(Request $request, BackupInspectionService $inspector, SystemRestoreService $restore): RedirectResponse
    {
        $validated = $request->validate([
            'restore_token' => ['required', 'uuid'],
            'confirmation' => ['required', 'in:PULIHKAN'],
        ], [
            'confirmation.required' => 'Taip PULIHKAN untuk mengesahkan pemulihan.',
            'confirmation.in' => 'Pengesahan tidak tepat. Taip PULIHKAN dalam huruf besar.',
        ]);

        $sessionKey = 'backup_restore_previews.'.$validated['restore_token'];
        $preview = session($sessionKey);
        if (! is_array($preview)
            || ($preview['expires_at'] ?? 0) < now()->timestamp
            || ! Storage::disk('local')->exists($preview['path'] ?? '')) {
            return to_route('settings.edit')->with('error', 'Sesi preview telah tamat. Sila periksa semula fail sandaran.');
        }

        $path = Storage::disk('local')->path($preview['path']);
        if (! hash_equals($preview['sha256'], hash_file('sha256', $path))) {
            return to_route('settings.edit')->with('error', 'Fail preview telah berubah dan tidak boleh dipulihkan.');
        }

        $inspection = $inspector->inspect(new UploadedFile($path, basename($path), 'application/zip', null, true));
        if (! $inspection['is_valid']) {
            return to_route('settings.edit')->with('error', 'Sandaran gagal pemeriksaan semula dan tidak dipulihkan.');
        }

        try {
            $result = $restore->restore($path, $inspection['manifest']);
        } catch (Throwable $exception) {
            report($exception);

            return to_route('settings.edit')->with('error', 'Pemulihan gagal. Data keselamatan pra-pemulihan telah disimpan.');
        }

        session()->forget($sessionKey);
        Storage::disk('local')->delete($preview['path']);

        AuditLog::create([
            'user_id' => User::whereKey(auth()->id())->exists() ? auth()->id() : null,
            'action' => 'restored',
            'module' => 'Backup',
            'description' => 'Restored a verified system backup.',
            'changes' => $result,
            'ip_address' => $request->ip(),
        ]);

        return to_route('settings.edit')->with('success', 'Sandaran berjaya dipulihkan. Backup keselamatan data sebelumnya telah disimpan.');
    }
}
