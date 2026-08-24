<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityRegistrationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminMemberController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseClaimController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\MemberDocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentSubmissionController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PolimartController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/membership/apply', [MembershipApplicationController::class, 'create'])->name('membership.apply');
    Route::post('/membership/apply', [MembershipApplicationController::class, 'store'])->name('membership.store');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [PasswordController::class, 'edit'])->name('profile.password');
    Route::put('/profile/password', [PasswordController::class, 'update'])->name('profile.password.update');
    Route::get('/documents', [MemberDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [MemberDocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [MemberDocumentController::class, 'show'])->name('documents.show');
    Route::delete('/documents/{document}', [MemberDocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/polimart', [PolimartController::class, 'index'])->name('polimart.index');
    Route::get('/polimart/create', [PolimartController::class, 'create'])->name('polimart.create');
    Route::post('/polimart', [PolimartController::class, 'store'])->name('polimart.store');
    Route::delete('/polimart/{polimartItem}', [PolimartController::class, 'destroy'])->name('polimart.destroy');

    Route::get('/admin/members/pending', [AdminMemberController::class, 'pending'])->middleware('role:admin')->name('admin.members.pending');
    Route::patch('/admin/members/{user}/approve', [AdminMemberController::class, 'approve'])->middleware('role:admin')->name('admin.members.approve');
    Route::get('/admin', [AdminController::class, 'index'])->middleware('role:admin')->name('admin.index');
    Route::get('/admin/audit', [AdminController::class, 'audit'])->middleware('role:admin')->name('admin.audit');
    Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])->middleware('role:admin')->name('admin.users.update');
    Route::get('/admin/settings', [SystemSettingController::class, 'edit'])->middleware('role:admin')->name('settings.edit');
    Route::put('/admin/settings', [SystemSettingController::class, 'update'])->middleware('role:admin')->name('settings.update');
    Route::post('/admin/settings/monthly-fees', [SystemSettingController::class, 'generateMonthlyFees'])->middleware('role:admin')->name('settings.monthly-fees');
    Route::get('/admin/backup', [BackupController::class, 'export'])->middleware('role:admin')->name('backup.export');

    Route::resource('activities', ActivityController::class);
    Route::patch('/activities/{activity}/approve', [ActivityController::class, 'approve'])->middleware('role:chairman,admin')->name('activities.approve');
    Route::post('/activities/{activity}/register', [ActivityRegistrationController::class, 'store'])->name('activities.register');
    Route::delete('/activities/{activity}/register', [ActivityRegistrationController::class, 'destroy'])->name('activities.unregister');
    Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('role:admin,chairman,treasurer')->name('attendance.index');
    Route::get('/attendance/scan', [AttendanceController::class, 'scan'])->name('attendance.scan');
    Route::post('/attendance/store', [AttendanceController::class, 'store'])->name('attendance.store');

    Route::resource('transactions', TransactionController::class)->middleware('role:treasurer,chairman,admin');
    Route::get('/transactions/{transaction}/receipt.pdf', [TransactionController::class, 'receiptPdf'])->middleware('role:treasurer,chairman,admin')->name('transactions.receipt.pdf');
    Route::get('/reports/financial', [ReportController::class, 'financial'])->middleware('role:treasurer,chairman,admin')->name('reports.financial');
    Route::get('/reports/financial.pdf', [ReportController::class, 'financialPdf'])->middleware('role:treasurer,chairman,admin')->name('reports.financial.pdf');
    Route::get('/reports/financial.csv', [ReportController::class, 'financialCsv'])->middleware('role:treasurer,chairman,admin')->name('reports.financial.csv');
    Route::get('/reports/attendance.csv', [ReportController::class, 'attendanceCsv'])->middleware('role:treasurer,chairman,admin')->name('reports.attendance.csv');

    Route::resource('claims', ExpenseClaimController::class)->only(['index', 'create', 'store', 'show']);
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
    Route::patch('/payments/{payment}/approve', [PaymentSubmissionController::class, 'approve'])->middleware('role:treasurer,chairman,admin')->name('payments.approve');
    Route::patch('/payments/{payment}/reject', [PaymentSubmissionController::class, 'reject'])->middleware('role:treasurer,chairman,admin')->name('payments.reject');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/create', [NotificationController::class, 'create'])->middleware('role:admin,chairman')->name('notifications.create');
    Route::post('/notifications', [NotificationController::class, 'store'])->middleware('role:admin,chairman')->name('notifications.store');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/admin/feedback', [FeedbackController::class, 'index'])->middleware('role:admin,chairman')->name('admin.feedback.index');
});
