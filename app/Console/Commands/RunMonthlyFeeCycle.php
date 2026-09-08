<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\MonthlyFeeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunMonthlyFeeCycle extends Command
{
    protected $signature = 'fees:monthly-cycle {--month= : Billing month in YYYY-MM format}';

    protected $description = 'Generate monthly member fees and send outstanding fee reminders.';

    public function handle(MonthlyFeeService $monthlyFeeService): int
    {
        $month = $this->option('month');

        if ($month && ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->error('Month must use YYYY-MM format.');

            return self::FAILURE;
        }

        $result = $monthlyFeeService->generate($month ?: null);

        $this->info('Monthly fee cycle processed.');
        $this->info('Billing month: '.$result['billing_month']->format('m/Y'));
        $this->info('New bills: '.$result['new_bills']);

        Artisan::call('fee:remind');
        $reminderOutput = trim(Artisan::output());

        AuditLog::create([
            'user_id' => null,
            'action' => 'processed',
            'module' => 'Monthly Fee Cycle',
            'description' => 'Generated monthly fees and sent fee reminders.',
            'changes' => [
                'billing_month' => $result['billing_month']->format('Y-m'),
                'monthly_fee' => $result['monthly_fee'],
                'new_bills' => $result['new_bills'],
                'reminder_output' => $reminderOutput,
            ],
            'ip_address' => null,
        ]);

        $this->info($reminderOutput);

        return self::SUCCESS;
    }
}
