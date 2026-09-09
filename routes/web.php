<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityRegistrationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminMemberController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ExpenseClaimController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\MemberDocumentController;
use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentSubmissionController;
use App\Http\Controllers\PolimartController;
use App\Http\Controllers\PolimartChatController;
use App\Http\Controllers\PolimartReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserPreferenceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/membership/apply', [MembershipApplicationController::class, 'create'])->name('membership.apply');
    Route::post('/membership/apply', [MembershipApplicationController::class, 'store'])->name('membership.store');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [PasswordController::class, 'edit'])->name('profile.password');
    Route::put('/profile/password', [PasswordController::class, 'update'])->name('profile.password.update');
    Route::get('/preferences', [UserPreferenceController::class, 'edit'])->name('preferences.edit');
    Route::put('/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');
    Route::get('/documents', [MemberDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [MemberDocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [MemberDocumentController::class, 'show'])->name('documents.show');
    Route::delete('/documents/{document}', [MemberDocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/polimart', [PolimartController::class, 'index'])->name('polimart.index');
    Route::get('/polimart/create', [PolimartController::class, 'create'])->name('polimart.create');
    Route::get('/polimart/favorites', [PolimartController::class, 'favorites'])->name('polimart.favorites');
    Route::get('/polimart/chat', [PolimartChatController::class, 'index'])->name('polimart.chat.index');
    Route::post('/polimart/{polimartItem}/chat', [PolimartChatController::class, 'start'])->name('polimart.chat.start');
    Route::get('/polimart/chat/{conversation}', [PolimartChatController::class, 'show'])->name('polimart.chat.show');
    Route::post('/polimart/chat/{conversation}/messages', [PolimartChatController::class, 'send'])->name('polimart.chat.send');
    Route::post('/polimart', [PolimartController::class, 'store'])->name('polimart.store');
    Route::get('/polimart/seller/{user}', [PolimartController::class, 'seller'])->name('polimart.seller');
    Route::post('/polimart/{polimartItem}/report', [PolimartReportController::class, 'store'])->name('polimart.report');
    Route::post('/polimart/{polimartItem}/favorite', [PolimartController::class, 'toggleFavorite'])->name('polimart.favorite');
    Route::post('/polimart/{polimartItem}/review', [PolimartController::class, 'review'])->name('polimart.review');
    Route::get('/polimart/{polimartItem}', [PolimartController::class, 'show'])->name('polimart.show');
    Route::get('/polimart/{polimartItem}/edit', [PolimartController::class, 'edit'])->name('polimart.edit');
    Route::put('/polimart/{polimartItem}', [PolimartController::class, 'update'])->name('polimart.update');
    Route::patch('/polimart/{polimartItem}/status', [PolimartController::class, 'updateStatus'])->name('polimart.status');
    Route::delete('/polimart/{polimartItem}', [PolimartController::class, 'destroy'])->name('polimart.destroy');

    Route::get('/admin/members/pending', [AdminMemberController::class, 'pending'])->middleware('role:admin')->name('admin.members.pending');
    Route::patch('/admin/members/{user}/approve', [AdminMemberController::class, 'approve'])->middleware('role:admin')->name('admin.members.approve');
    Route::patch('/admin/members/{user}/reject', [AdminMemberController::class, 'reject'])->middleware('role:admin')->name('admin.members.reject');
    Route::get('/admin', [AdminController::class, 'index'])->middleware('role:admin')->name('admin.index');
    Route::post('/admin/queue/retry-failed', [AdminController::class, 'retryFailedJobs'])->middleware('role:admin')->name('admin.queue.retry-failed');
    Route::get('/admin/audit', [AdminController::class, 'audit'])->middleware('role:admin')->name('admin.audit');
    Route::get('/admin/polimart/reports', [PolimartReportController::class, 'index'])->middleware('role:admin')->name('admin.polimart.reports');
    Route::patch('/admin/polimart/reports/{polimartReport}', [PolimartReportController::class, 'update'])->middleware('role:admin')->name('admin.polimart.reports.update');
    Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])->middleware('role:admin')->name('admin.users.update');
    Route::get('/admin/settings', [SystemSettingController::class, 'edit'])->middleware('role:admin')->name('settings.edit');
    Route::put('/admin/settings', [SystemSettingController::class, 'update'])->middleware('role:admin')->name('settings.update');
    Route::get('/finance/fees', [SystemSettingController::class, 'feeOperations'])->middleware('role:admin,chairman,treasurer')->name('finance.fees.index');
    Route::post('/finance/fees/generate', [SystemSettingController::class, 'generateMonthlyFees'])->middleware('role:admin,treasurer')->name('finance.fees.generate');
    Route::post('/finance/fees/reminders', [SystemSettingController::class, 'sendFeeReminders'])->middleware('role:admin,treasurer')->name('finance.fees.reminders');
    Route::post('/admin/settings/maintenance', [SystemSettingController::class, 'enableMaintenance'])->middleware('role:admin')->name('settings.maintenance.enable');
    Route::delete('/admin/settings/maintenance', [SystemSettingController::class, 'disableMaintenance'])->middleware('role:admin')->name('settings.maintenance.disable');
    Route::get('/admin/backup', [BackupController::class, 'export'])->middleware('role:admin')->name('backup.export');
    Route::post('/admin/backup/inspect', [BackupController::class, 'inspect'])->middleware('role:admin')->name('backup.inspect');
    Route::get('/admin/backup/preview/{token}', [BackupController::class, 'preview'])->middleware('role:admin')->name('backup.preview');
    Route::post('/admin/backup/restore', [BackupController::class, 'restore'])->middleware('role:admin')->name('backup.restore');

    Route::resource('activities', ActivityController::class)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin');
    Route::patch('/activities/{activity}/approve', [ActivityController::class, 'approve'])->middleware('role:chairman,admin')->name('activities.approve');
    Route::patch('/activities/{activity}/refresh-qr', [ActivityController::class, 'refreshQrToken'])->middleware('role:chairman,admin,treasurer')->name('activities.refresh-qr');
    Route::post('/activities/{activity}/register', [ActivityRegistrationController::class, 'store'])->name('activities.register');
    Route::delete('/activities/{activity}/register', [ActivityRegistrationController::class, 'destroy'])->name('activities.unregister');
    Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('role:admin,chairman,treasurer')->name('attendance.index');
    Route::get('/attendance/scan', [AttendanceController::class, 'scan'])->name('attendance.scan');
    Route::post('/attendance/store', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('/activities/{activity}/attendance/{registration}', [AttendanceController::class, 'storeForRegistration'])->middleware('role:admin,chairman,treasurer')->name('activities.attendance.store');

    Route::resource('transactions', TransactionController::class)
        ->middleware('role:treasurer,chairman,admin')
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:treasurer,admin');
    Route::get('/transactions/{transaction}/receipt.pdf', [TransactionController::class, 'receiptPdf'])->middleware('role:treasurer,chairman,admin')->name('transactions.receipt.pdf');
    Route::get('/reports/overview', [ReportController::class, 'overview'])->middleware('role:treasurer,chairman,admin')->name('reports.overview');
    Route::get('/reports/financial', [ReportController::class, 'financial'])->middleware('role:treasurer,chairman,admin')->name('reports.financial');
    Route::get('/reports/financial.pdf', [ReportController::class, 'financialPdf'])->middleware('role:treasurer,chairman,admin')->name('reports.financial.pdf');
    Route::get('/reports/financial.csv', [ReportController::class, 'financialCsv'])->middleware('role:treasurer,chairman,admin')->name('reports.financial.csv');
    Route::get('/reports/attendance.csv', [ReportController::class, 'attendanceCsv'])->middleware('role:treasurer,chairman,admin')->name('reports.attendance.csv');
    Route::get('/reports/activities/{activity}/attendance.csv', [ReportController::class, 'activityAttendanceCsv'])->middleware('role:treasurer,chairman,admin')->name('reports.activities.attendance.csv');

    Route::resource('claims', ExpenseClaimController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::get('/claims/{claim}/resubmit', [ExpenseClaimController::class, 'resubmitForm'])->name('claims.resubmit.form');
    Route::post('/claims/{claim}/resubmit', [ExpenseClaimController::class, 'resubmit'])->name('claims.resubmit');
    Route::get('/claims/{claim}/receipt', [ExpenseClaimController::class, 'receipt'])->name('claims.receipt');
    Route::patch('/claims/{claim}/verify', [ExpenseClaimController::class, 'verify'])->middleware('role:treasurer,admin')->name('claims.verify');
    Route::patch('/claims/{claim}/approve', [ExpenseClaimController::class, 'approve'])->middleware('role:chairman,admin')->name('claims.approve');
    Route::patch('/claims/{claim}/reject', [ExpenseClaimController::class, 'reject'])->middleware('role:chairman,admin')->name('claims.reject');

    Route::get('/payments', [PaymentSubmissionController::class, 'index'])->name('payments.index');
    Route::get('/payments/statement', [PaymentSubmissionController::class, 'statement'])->name('payments.statement');
    Route::get('/payments/create', [PaymentSubmissionController::class, 'create'])->name('payments.create');
    Route::post('/payments', [PaymentSubmissionController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}', [PaymentSubmissionController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/proof', [PaymentSubmissionController::class, 'proof'])->name('payments.proof');
    Route::post('/payments/{payment}/resubmit', [PaymentSubmissionController::class, 'resubmit'])->name('payments.resubmit');
    Route::delete('/payments/{payment}/cancel', [PaymentSubmissionController::class, 'cancel'])->name('payments.cancel');
    Route::patch('/payments/{payment}/approve', [PaymentSubmissionController::class, 'approve'])->middleware('role:treasurer,admin')->name('payments.approve');
    Route::patch('/payments/{payment}/reject', [PaymentSubmissionController::class, 'reject'])->middleware('role:treasurer,admin')->name('payments.reject');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/admin/notifications/delivery', [NotificationController::class, 'deliveryMonitor'])->middleware('role:admin')->name('admin.notifications.delivery');
    Route::post('/admin/email-deliveries/{delivery}/retry', [NotificationController::class, 'retryDelivery'])->middleware('role:admin')->name('admin.email-deliveries.retry');
    Route::post('/admin/notifications/{notification}/retry', [NotificationController::class, 'retryEmail'])->middleware('role:admin')->name('admin.notifications.retry');
    Route::get('/notifications/create', [NotificationController::class, 'create'])->middleware('role:admin,chairman')->name('notifications.create');
    Route::post('/notifications', [NotificationController::class, 'store'])->middleware('role:admin,chairman')->name('notifications.store');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/admin/feedback', [FeedbackController::class, 'index'])->middleware('role:admin,chairman')->name('admin.feedback.index');
});
