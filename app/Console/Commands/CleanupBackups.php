<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CleanupBackups extends Command
{
    protected $signature = 'backup:cleanup
        {--preview-hours=1 : Tempoh simpan fail preview dalam jam}
        {--workspace-hours=6 : Tempoh simpan workspace restore dalam jam}
        {--backup-days=30 : Tempoh simpan backup keselamatan dalam hari}
        {--keep=5 : Bilangan minimum backup keselamatan terbaru}';

    protected $description = 'Remove expired backup previews, restore workspaces and old safety backups';

    public function handle(): int
    {
        $previewHours = max(1, (int) $this->option('preview-hours'));
        $workspaceHours = max(1, (int) $this->option('workspace-hours'));
        $backupDays = max(1, (int) $this->option('backup-days'));
        $keep = max(1, (int) $this->option('keep'));

        $deletedPreviews = $this->deleteOlderFiles('backup-previews', now()->subHours($previewHours)->timestamp);
        $deletedWorkspaceFiles = $this->deleteOlderFiles('restore-workspaces', now()->subHours($workspaceHours)->timestamp);
        $deletedBackups = $this->deleteOldSafetyBackups(now()->subDays($backupDays)->timestamp, $keep);

        $total = $deletedPreviews + $deletedWorkspaceFiles + $deletedBackups;

        if ($total > 0 && Schema::hasTable('audit_logs')) {
            AuditLog::create([
                'user_id' => null,
                'action' => 'cleaned',
                'module' => 'Backup',
                'description' => 'Removed expired backup files automatically.',
                'changes' => [
                    'previews' => $deletedPreviews,
                    'workspace_files' => $deletedWorkspaceFiles,
                    'safety_backups' => $deletedBackups,
                ],
                'ip_address' => null,
            ]);
        }

        $this->info("Backup cleanup completed. Preview: {$deletedPreviews}, workspace: {$deletedWorkspaceFiles}, safety backup: {$deletedBackups}.");

        return self::SUCCESS;
    }

    private function deleteOlderFiles(string $directory, int $cutoff): int
    {
        $disk = Storage::disk('local');
        $deleted = 0;

        foreach ($disk->allFiles($directory) as $file) {
            if ($disk->lastModified($file) < $cutoff && $disk->delete($file)) {
                $deleted++;
            }
        }

        foreach (array_reverse($disk->allDirectories($directory)) as $childDirectory) {
            if ($disk->allFiles($childDirectory) === []) {
                $disk->deleteDirectory($childDirectory);
            }
        }

        return $deleted;
    }

    private function deleteOldSafetyBackups(int $cutoff, int $keep): int
    {
        $disk = Storage::disk('local');
        $files = collect($disk->files('system-backups'))
            ->filter(fn (string $file): bool => str_ends_with(strtolower($file), '.zip'))
            ->sortByDesc(fn (string $file): int => $disk->lastModified($file))
            ->values();
        $deleted = 0;

        foreach ($files->slice($keep) as $file) {
            if ($disk->lastModified($file) < $cutoff && $disk->delete($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
