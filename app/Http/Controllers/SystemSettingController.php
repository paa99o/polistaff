<?php

namespace App\Http\Controllers;

use App\Mail\PortalNotificationMail;
use App\Models\AuditLog;
use App\Models\PortalNotification;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\EmailAuditService;
use App\Services\MonthlyFeeService;
use App\Services\EmailDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class SystemSettingController extends Controller
{
    public function __construct(private EmailAuditService $emailAuditService, private EmailDeliveryService $emailDeliveryService) {}

    public function edit(): View
    {
        return view('settings.edit', ['settings' => [
            'club_name' => SystemSetting::getValue('club_name', 'PoliBest'),
            'monthly_fee' => SystemSetting::getValue('monthly_fee', '20'),
            'receipt_prefix' => SystemSetting::getValue('receipt_prefix', 'PB'),
            'contact_email' => SystemSetting::getValue('contact_email', 'admin@polibest.test'),
            'opening_balance' => SystemSetting::getValue('opening_balance', '0'),
            'maintenance_enabled' => SystemSetting::getValue('maintenance_enabled', '0') === '1',
            'maintenance_message' => SystemSetting::getValue('maintenance_message', 'Sistem sedang diselenggara bagi memastikan perkhidmatan kekal stabil.'),
            'maintenance_estimated_end' => SystemSetting::getValue('maintenance_estimated_end'),
        ]]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['club_name' => ['required', 'string', 'max:120'], 'monthly_fee' => ['required', 'numeric', 'min:0'], 'receipt_prefix' => ['required', 'string', 'max:10'], 'contact_email' => ['required', 'email'], 'opening_balance' => ['required', 'numeric']]);
        foreach ($data as $key => $value) {
            SystemSetting::setValue($key, $value);
        }
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'updated', 'module' => 'System Settings', 'description' => 'Updated system settings.', 'changes' => $data, 'ip_address' => $request->ip()]);

        return back()->with('status', 'Tetapan sistem dikemas kini.');
    }

    public function feeOperations(): View
    {
        return view('finance.fees', [
            'monthlyFee' => (float) SystemSetting::getValue('monthly_fee', '20'),
            'activeMembers' => User::where('membership_status', 'active')->count(),
            'outstandingMembers' => User::where('membership_status', 'active')->where('fee_balance', '>', 0)->count(),
            'outstandingTotal' => (float) User::where('membership_status', 'active')->sum('fee_balance'),
        ]);
    }

    public function generateMonthlyFees(Request $request, MonthlyFeeService $monthlyFeeService): RedirectResponse
    {
        $data = $request->validate(['billing_month' => ['nullable', 'date_format:Y-m']]);
        $result = $monthlyFeeService->generate($data['billing_month'] ?? null);

        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'generated', 'module' => 'Monthly Fee Automation', 'description' => 'Generated monthly fees for active members.', 'changes' => ['billing_month' => $result['billing_month']->format('Y-m'), 'monthly_fee' => $result['monthly_fee'], 'new_bills' => $result['new_bills']], 'ip_address' => $request->ip()]);

        return back()->with('status', 'Yuran '.$result['billing_month']->format('m/Y').' dijana untuk '.$result['new_bills'].' ahli. Rekod sedia ada tidak digandakan.');
    }

    public function sendFeeReminders(Request $request): RedirectResponse
    {
        Artisan::call('fee:remind');

        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'sent', 'module' => 'Fee Reminder', 'description' => 'Sent manual fee reminders from system settings.', 'changes' => ['output' => trim(Artisan::output())], 'ip_address' => $request->ip()]);

        return back()->with('status', trim(Artisan::output()) ?: 'Peringatan yuran dihantar.');
    }

    public function enableMaintenance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'maintenance_message' => ['required', 'string', 'max:500'],
            'maintenance_estimated_end' => ['nullable', 'date', 'after:now'],
        ], [
            'maintenance_message.required' => 'Sila masukkan mesej penyelenggaraan.',
            'maintenance_message.max' => 'Mesej penyelenggaraan tidak boleh melebihi 500 aksara.',
            'maintenance_estimated_end.after' => 'Anggaran tamat mestilah selepas waktu sekarang.',
        ]);

        SystemSetting::setValue('maintenance_message', $data['maintenance_message']);
        SystemSetting::setValue('maintenance_estimated_end', $data['maintenance_estimated_end'] ?? '');
        SystemSetting::setValue('maintenance_enabled', '1');

        $notified = $this->notifyUsers(
            'Penyelenggaraan sistem Polistaff',
            $data['maintenance_message'],
            'warning',
            'maintenance enabled'
        );

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'enabled',
            'module' => 'System Maintenance',
            'description' => 'Enabled system maintenance mode.',
            'changes' => [
                'message' => $data['maintenance_message'],
                'estimated_end' => $data['maintenance_estimated_end'] ?? null,
                'notified_users' => $notified,
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Mode penyelenggaraan diaktifkan. '.$notified.' pengguna telah dimaklumkan.');
    }

    public function disableMaintenance(Request $request): RedirectResponse
    {
        SystemSetting::setValue('maintenance_enabled', '0');
        SystemSetting::setValue('maintenance_estimated_end', '');

        $notified = $this->notifyUsers(
            'Polistaff kembali beroperasi',
            'Penyelenggaraan telah selesai dan sistem kini boleh digunakan seperti biasa.',
            'success',
            'maintenance completed'
        );

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'disabled',
            'module' => 'System Maintenance',
            'description' => 'Disabled system maintenance mode.',
            'changes' => ['notified_users' => $notified],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Mode penyelenggaraan dimatikan. Sistem kembali beroperasi.');
    }

    private function notifyUsers(string $title, string $message, string $type, string $emailEvent): int
    {
        $notified = 0;

        User::where('membership_status', 'active')
            ->where('id', '!=', auth()->id())
            ->chunkById(100, function ($users) use ($title, $message, $type, $emailEvent, &$notified): void {
                foreach ($users as $user) {
                    $notification = PortalNotification::create([
                        'user_id' => $user->id,
                        'title' => $title,
                        'message' => $message,
                        'type' => $type,
                    ]);

                    if ($user->email && $user->wantsEmail('announcements')) {
                        try {
                            $this->emailDeliveryService->send($user, $emailEvent, new PortalNotificationMail($notification), $notification);
                            $this->emailAuditService->sent($user, $emailEvent, $notification);
                        } catch (Throwable $exception) {
                            Log::warning('Maintenance notification email failed.', [
                                'user_id' => $user->id,
                                'error' => $exception->getMessage(),
                            ]);
                        }
                    }

                    $notified++;
                }
            });

        return $notified;
    }
}
