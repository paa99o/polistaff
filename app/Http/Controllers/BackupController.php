<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function export(): StreamedResponse
    {
        $tables = ['users', 'activities', 'activity_registrations', 'attendances', 'transactions', 'payment_submissions', 'member_fee_bills', 'expense_claims', 'notifications', 'feedbacks', 'member_documents', 'system_settings', 'audit_logs'];
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'exported', 'module' => 'Backup', 'description' => 'Exported database backup.', 'changes' => ['tables' => $tables], 'ip_address' => request()->ip()]);

        return response()->streamDownload(function () use ($tables): void {
            echo "-- PoliBest backup generated at ".now()->toDateTimeString()."\n\n";
            foreach ($tables as $table) {
                if (! DB::getSchemaBuilder()->hasTable($table)) {
                    continue;
                }
                echo "-- Table: {$table}\n";
                foreach (DB::table($table)->get() as $row) {
                    $values = array_map(fn ($value) => $value === null ? 'NULL' : DB::getPdo()->quote((string) $value), (array) $row);
                    echo 'INSERT INTO '.$table.' VALUES ('.implode(', ', $values).");\n";
                }
                echo "\n";
            }
        }, 'polibest-backup-'.now()->format('Ymd-His').'.sql', ['Content-Type' => 'text/plain']);
    }
}
