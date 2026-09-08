<?php

namespace Tests\Feature;

use App\Mail\ActivityApprovedMail;
use App\Mail\ActivityCancelledMail;
use App\Mail\ExpenseClaimApprovedMail;
use App\Mail\ExpenseClaimRejectedMail;
use App\Mail\ExpenseClaimVerifiedMail;
use App\Mail\FeeReminderMail;
use App\Mail\MembershipApprovedMail;
use App\Mail\MembershipRejectedMail;
use App\Mail\PaymentApprovedMail;
use App\Mail\PaymentRejectedMail;
use App\Mail\PortalNotificationMail;
use App\Models\Activity;
use App\Models\ActivityRegistration;
use App\Models\Attendance;
use App\Models\ExpenseClaim;
use App\Models\MemberDocument;
use App\Models\PaymentSubmission;
use App\Models\PolimartItem;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class PoliBestFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_basic_account(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Ali Staff',
            'email' => 'ali@example.test',
            'phone' => '0111111111',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'ali@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertSame('inactive', $user->membership_status);
        $this->assertSame('member', $user->role);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_unverified_user_is_guided_to_email_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Semak peti masuk anda')
            ->assertSee($user->email);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    public function test_user_can_verify_email_with_a_signed_single_use_link(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Alamat emel berjaya disahkan.');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'Authentication',
            'action' => 'verified-email',
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_unverified_user_can_request_another_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Pautan pengesahan baharu telah dihantar.');

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_changing_profile_email_requires_fresh_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'ic_number' => '900101111111',
            'address' => 'Alamat asal',
        ]);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'ic_number' => $user->ic_number,
            'email' => 'alamat-baharu@example.test',
            'department' => $user->department,
            'phone' => $user->phone,
            'address' => $user->address,
        ])->assertRedirect(route('verification.notice'));

        $user->refresh();
        $this->assertSame('alamat-baharu@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.test']);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa kata laluan?');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_password_reset_request_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertSessionHas('status', 'Jika emel tersebut berdaftar, pautan reset kata laluan telah dihantar.');

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_a_valid_single_use_token(): void
    {
        $user = User::factory()->create(['email' => 'recover@example.test', 'password' => 'old-password']);
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Tetapkan semula kata laluan');

        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ];

        $this->post(route('password.update'), $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'Authentication',
            'action' => 'reset-password',
        ]);

        $this->post(route('password.update'), $payload)
            ->assertSessionHasErrors('email');
    }

    public function test_custom_error_pages_render_with_safe_actions(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get(route('settings.edit'))
            ->assertForbidden()
            ->assertSee('Anda tidak mempunyai kebenaran')
            ->assertSee('Kembali ke dashboard');

        $this->get('/halaman-yang-tidak-wujud')
            ->assertNotFound()
            ->assertSee('Alamat ini tidak membawa ke mana-mana');

        Route::get('/testing/session-expired', fn () => abort(419));
        $this->get('/testing/session-expired')
            ->assertStatus(419)
            ->assertSee('Sesi keselamatan anda telah tamat')
            ->assertSee('Log masuk semula');

        config(['app.debug' => false]);
        Route::get('/testing/server-error', fn () => throw new \RuntimeException('Sensitive internal detail'));
        $this->get('/testing/server-error')
            ->assertStatus(500)
            ->assertSee('Sesuatu tidak berjalan seperti sepatutnya')
            ->assertDontSee('Sensitive internal detail');
    }

    public function test_new_user_profile_shows_missing_information_notice(): void
    {
        $user = User::factory()->create([
            'ic_number' => null,
            'department' => null,
            'phone' => null,
            'address' => null,
            'profile_photo_path' => null,
        ]);
        $activity = Activity::create([
            'title' => 'Aktiviti Passport',
            'date_time' => now()->subDay(),
            'location' => 'Dewan',
            'status' => 'approved',
            'qr_code_token' => Str::uuid()->toString(),
        ]);
        Attendance::create([
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'scanned_at' => now()->subDay(),
            'qr_code_token' => $activity->qr_code_token,
        ]);

        $this->actingAs($user)->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Lengkapkan profil anda')
            ->assertSee('Kad Ahli Kelab Staf')
            ->assertSee('ID Ahli')
            ->assertSee('Activity Passport')
            ->assertSee('Ahli Baru')
            ->assertSee('Aktiviti Passport')
            ->assertSee('Gambar profil')
            ->assertSee('Nombor IC')
            ->assertSee('Alamat');
    }

    public function test_user_can_update_profile_with_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['profile_photo_path' => null]);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Ali Staff',
            'ic_number' => '900101111111',
            'email' => $user->email,
            'department' => 'JTMK',
            'phone' => '0111111111',
            'address' => 'No 1, Jalan Politeknik',
            'profile_photo' => UploadedFile::fake()->image('ali.jpg'),
        ])->assertRedirect(route('profile.show'));

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'updated',
            'module' => 'Profile',
        ]);
    }

    public function test_user_can_view_and_update_personal_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('preferences.edit'))
            ->assertOk()
            ->assertSee('Tetapan Saya')
            ->assertSee('Notifikasi Emel')
            ->assertSee('Aksesibiliti');

        $this->actingAs($user)->put(route('preferences.update'), [
            'theme_preference' => 'dark',
            'text_size_preference' => 'large',
            'reduce_motion' => '1',
            'email_announcements' => '0',
            'email_activities' => '1',
            'email_finance' => '0',
            'email_fee_reminders' => '1',
        ])->assertRedirect(route('preferences.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'theme_preference' => 'dark',
            'text_size_preference' => 'large',
            'reduce_motion' => true,
            'email_announcements' => false,
            'email_activities' => true,
            'email_finance' => false,
            'email_fee_reminders' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'User Preferences',
            'action' => 'updated',
        ]);
    }

    public function test_disabled_announcement_email_still_creates_in_app_notification(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create([
            'email' => 'quiet@example.test',
            'email_announcements' => false,
        ]);

        $this->actingAs($admin)->post(route('notifications.store'), [
            'target' => 'individual',
            'user_id' => $member->id,
            'title' => 'Hebahan Ujian',
            'message' => 'Notifikasi dalam aplikasi mesti kekal.',
            'type' => 'info',
        ])->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'title' => 'Hebahan Ujian',
        ]);
        Mail::assertNothingSent();
    }

    public function test_dashboard_reminds_user_to_complete_profile(): void
    {
        $user = User::factory()->create([
            'ic_number' => null,
            'department' => null,
            'phone' => null,
            'address' => null,
            'profile_photo_path' => null,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Profil belum lengkap')
            ->assertSee('Lengkapkan: Gambar profil, Nombor IC, Jabatan, Telefon, Alamat.');
    }

    public function test_admin_can_filter_users_by_profile_completion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'name' => 'Lengkap Staff',
            'ic_number' => '900101111111',
            'department' => 'JTMK',
            'phone' => '0111111111',
            'address' => 'Besut',
            'profile_photo_path' => 'profile-photos/lengkap.jpg',
        ]);
        User::factory()->create([
            'name' => 'Belum Staff',
            'ic_number' => null,
            'profile_photo_path' => null,
        ]);

        $this->actingAs($admin)->get(route('admin.index', ['profile' => 'incomplete']))
            ->assertOk()
            ->assertSee('Belum Staff')
            ->assertSee('Belum lengkap')
            ->assertDontSee('Lengkap Staff');
    }

    public function test_user_can_apply_for_club_staff_membership(): void
    {
        $user = User::factory()->create(['membership_status' => 'inactive', 'ic_number' => null, 'department' => null, 'address' => null]);

        $this->actingAs($user)->post(route('membership.store'), [
            'name' => 'Ali Staff',
            'ic_number' => '900101111111',
            'department' => 'JTMK',
            'phone' => '0111111111',
            'address' => 'No 1, Jalan Politeknik, 06000 Jitra, Kedah',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'ic_number' => '900101111111',
            'department' => 'JTMK',
            'membership_status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_membership_and_email_member(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['email' => 'newmember@example.test', 'membership_status' => 'pending', 'joined_date' => null]);

        $this->actingAs($admin)->patch(route('admin.members.approve', $member))->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'membership_status' => 'active',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'title' => 'Keahlian diluluskan',
        ]);

        Mail::assertQueued(MembershipApprovedMail::class, fn (MembershipApprovedMail $mail) => $mail->hasTo('newmember@example.test'));
    }

    public function test_admin_can_reject_membership_and_email_member(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['email' => 'newmember@example.test', 'membership_status' => 'pending']);

        $this->actingAs($admin)->patch(route('admin.members.reject', $member), [
            'reason' => 'Maklumat permohonan tidak lengkap.',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'membership_status' => 'inactive',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'title' => 'Permohonan ahli ditolak',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'record_id' => $member->id,
            'action' => 'rejected',
            'module' => 'Membership Approval',
        ]);

        Mail::assertQueued(MembershipRejectedMail::class, fn (MembershipRejectedMail $mail) => $mail->hasTo('newmember@example.test'));
    }

    public function test_reject_membership_requires_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['membership_status' => 'pending']);

        $this->actingAs($admin)->patch(route('admin.members.reject', $member))
            ->assertSessionHasErrors(['reason' => 'Sila isi sebab permohonan ditolak.']);
    }

    public function test_admin_dashboard_shows_operational_summary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['membership_status' => 'pending']);
        User::factory()->create(['membership_status' => 'active', 'fee_balance' => 40, 'profile_photo_path' => 'profile-photos/member.jpg']);
        Activity::create([
            'title' => 'Taklimat Dashboard',
            'date_time' => now()->addWeek(),
            'location' => 'Dewan',
            'status' => 'approved',
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        $this->actingAs($admin)->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Kerja Perlu Tindakan')
            ->assertSee('Tunggakan Yuran')
            ->assertSee('RM 40.00')
            ->assertSee('Taklimat Dashboard')
            ->assertSee('profile-photos/member.jpg');
    }

    public function test_role_dashboard_shows_relevant_pending_actions(): void
    {
        $chairman = User::factory()->create(['role' => 'chairman']);
        Activity::create([
            'title' => 'Aktiviti Menunggu Kelulusan',
            'date_time' => now()->addWeek(),
            'location' => 'Dewan',
            'status' => 'pending_approval',
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        $this->actingAs($chairman)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aktiviti menunggu kelulusan')
            ->assertSee('Semak Aktiviti');

        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create();
        PaymentSubmission::create([
            'user_id' => $member->id,
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof_path' => 'payment-proofs/dashboard.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($treasurer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('bukti bayaran menunggu semakan')
            ->assertSee('Semak Bayaran');
    }

    public function test_admin_can_retry_failed_queue_jobs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'TestJob']),
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.queue.retry-failed'))
            ->assertRedirect()
            ->assertSessionHas('status', '1 job gagal dimasukkan semula ke dalam queue.');

        $this->assertDatabaseCount('failed_jobs', 0);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'module' => 'Email Queue',
            'action' => 'retried',
        ]);
    }

    public function test_member_can_view_approved_activities_in_monthly_calendar(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        Activity::create([
            'title' => 'Aktiviti Dalam Kalendar',
            'date_time' => '2026-09-15 09:00:00',
            'location' => 'Dewan Utama',
            'status' => 'approved',
            'qr_code_token' => Str::uuid()->toString(),
        ]);
        Activity::create([
            'title' => 'Aktiviti Belum Diluluskan',
            'date_time' => '2026-09-16 09:00:00',
            'location' => 'Bilik Mesyuarat',
            'status' => 'pending_approval',
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        $this->actingAs($member)->get(route('activities.index', ['month' => '2026-09']))
            ->assertOk()
            ->assertSee('Aktiviti Dalam Kalendar')
            ->assertSee('Dewan Utama')
            ->assertDontSee('Aktiviti Belum Diluluskan');
    }

    public function test_management_can_view_report_overview_dashboard(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['membership_status' => 'active', 'fee_balance' => 25, 'department' => 'JTMK']);
        $activity = Activity::create(['title' => 'Program Laporan', 'date_time' => now(), 'location' => 'Dewan', 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);

        Transaction::create(['user_id' => $member->id, 'type' => 'income', 'amount' => 60, 'description' => 'Bayaran yuran', 'receipt_number' => 'PB-TST-001', 'transaction_date' => now()->toDateString(), 'category' => 'Yuran', 'status' => 'active']);
        Transaction::create(['user_id' => $member->id, 'type' => 'expense', 'amount' => 10, 'description' => 'Alat tulis', 'receipt_number' => 'PB-TST-002', 'transaction_date' => now()->toDateString(), 'category' => 'Operasi', 'status' => 'active']);
        ActivityRegistration::create(['user_id' => $member->id, 'activity_id' => $activity->id, 'status' => 'registered', 'registered_at' => now()]);
        Attendance::create(['user_id' => $member->id, 'activity_id' => $activity->id, 'scanned_at' => now(), 'qr_code_token' => $activity->qr_code_token]);

        $this->actingAs($treasurer)->get(route('reports.overview'))
            ->assertOk()
            ->assertSee('Laporan Ringkasan')
            ->assertSee('RM 60.00')
            ->assertSee('RM 10.00')
            ->assertSee('Program Laporan')
            ->assertSee('JTMK');
    }

    public function test_management_can_filter_financial_report_with_visual_summary(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create();

        Transaction::create(['user_id' => $member->id, 'type' => 'income', 'amount' => 80, 'description' => 'Yuran September', 'receipt_number' => 'PB-FIN-001', 'transaction_date' => '2026-09-06', 'category' => 'Yuran', 'status' => 'active']);
        Transaction::create(['user_id' => $member->id, 'type' => 'expense', 'amount' => 30, 'description' => 'Jamuan September', 'receipt_number' => 'PB-FIN-002', 'transaction_date' => '2026-09-07', 'category' => 'Aktiviti', 'status' => 'active']);
        Transaction::create(['user_id' => $member->id, 'type' => 'income', 'amount' => 200, 'description' => 'Yuran Ogos', 'receipt_number' => 'PB-FIN-003', 'transaction_date' => '2026-08-01', 'category' => 'Yuran', 'status' => 'active']);

        $this->actingAs($treasurer)->get(route('reports.financial', ['mode' => 'monthly', 'year' => 2026, 'month' => 9]))
            ->assertOk()
            ->assertSee('Finance Analytics')
            ->assertSee('September 2026')
            ->assertSee('RM 80.00')
            ->assertSee('RM 30.00')
            ->assertSee('Yuran September')
            ->assertDontSee('Yuran Ogos');
    }

    public function test_financial_csv_respects_selected_filter(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create();

        Transaction::create(['user_id' => $member->id, 'type' => 'income', 'amount' => 80, 'description' => 'Yuran September', 'receipt_number' => 'PB-CSV-001', 'transaction_date' => '2026-09-06', 'category' => 'Yuran', 'status' => 'active']);
        Transaction::create(['user_id' => $member->id, 'type' => 'income', 'amount' => 200, 'description' => 'Yuran Ogos', 'receipt_number' => 'PB-CSV-002', 'transaction_date' => '2026-08-01', 'category' => 'Yuran', 'status' => 'active']);

        $this->actingAs($treasurer)->get(route('reports.financial.csv', ['mode' => 'monthly', 'year' => 2026, 'month' => 9]))
            ->assertOk()
            ->assertSee('Yuran September', false)
            ->assertDontSee('Yuran Ogos', false);
    }

    public function test_member_can_record_attendance_by_token(): void
    {
        $user = User::factory()->create();
        $activity = Activity::create(['title' => 'Program Sukan', 'date_time' => now()->addDay(), 'location' => 'Padang', 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);
        ActivityRegistration::create(['user_id' => $user->id, 'activity_id' => $activity->id, 'status' => 'registered', 'registered_at' => now()]);

        $this->actingAs($user)->post('/attendance/store', ['token' => $activity->qr_code_token])->assertRedirect(route('activities.show', $activity));

        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'activity_id' => $activity->id]);
    }

    public function test_member_must_register_before_recording_attendance(): void
    {
        $user = User::factory()->create();
        $activity = Activity::create(['title' => 'Program Ilmu', 'date_time' => now()->addDay(), 'location' => 'Bilik Seminar', 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);

        $this->actingAs($user)->post('/attendance/store', ['token' => $activity->qr_code_token])->assertRedirect(route('activities.show', $activity));

        $this->assertDatabaseMissing('attendances', ['user_id' => $user->id, 'activity_id' => $activity->id]);
    }

    public function test_management_can_mark_registered_participant_attendance_manually(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create();
        $activity = Activity::create(['title' => 'Program Manual', 'date_time' => now()->addDay(), 'location' => 'Dewan', 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);
        $registration = ActivityRegistration::create(['user_id' => $member->id, 'activity_id' => $activity->id, 'status' => 'registered', 'registered_at' => now()]);

        $this->actingAs($admin)->post(route('activities.attendance.store', [$activity, $registration]))
            ->assertRedirect();

        $attendance = Attendance::where('user_id', $member->id)->where('activity_id', $activity->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'module' => 'Manual Attendance',
            'record_type' => Attendance::class,
            'record_id' => $attendance->id,
        ]);
    }

    public function test_management_can_refresh_activity_qr_token(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create();
        $activity = Activity::create(['title' => 'Program QR', 'date_time' => now()->addDay(), 'location' => 'Dewan', 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);
        $oldToken = $activity->qr_code_token;

        ActivityRegistration::create(['user_id' => $member->id, 'activity_id' => $activity->id, 'status' => 'registered', 'registered_at' => now()]);

        $this->actingAs($admin)->patch(route('activities.refresh-qr', $activity))
            ->assertRedirect();

        $activity->refresh();

        $this->assertNotSame($oldToken, $activity->qr_code_token);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'updated',
            'module' => 'Activity QR',
            'record_type' => Activity::class,
            'record_id' => $activity->id,
        ]);

        $this->actingAs($member)->post('/attendance/store', ['token' => $oldToken])->assertNotFound();
        $this->actingAs($member)->post('/attendance/store', ['token' => $activity->qr_code_token])->assertRedirect(route('activities.show', $activity));

        $this->assertDatabaseHas('attendances', ['user_id' => $member->id, 'activity_id' => $activity->id]);
    }

    public function test_management_can_download_activity_attendance_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['name' => 'Ali Staff', 'email' => 'ali@example.test', 'department' => 'JTMK']);
        $activity = Activity::create(['title' => 'Program CSV', 'date_time' => now()->addDay(), 'location' => 'Dewan', 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);

        Attendance::create([
            'user_id' => $member->id,
            'activity_id' => $activity->id,
            'scanned_at' => now(),
            'qr_code_token' => $activity->qr_code_token,
        ]);

        $this->actingAs($admin)->get(route('reports.activities.attendance.csv', $activity))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('member,email,department,activity,scanned_at', false)
            ->assertSee('Ali Staff', false)
            ->assertSee('Program CSV', false);
    }

    public function test_member_can_register_for_activity_with_capacity(): void
    {
        $user = User::factory()->create();
        $activity = Activity::create(['title' => 'Program Komuniti', 'date_time' => now()->addDay(), 'location' => 'Dewan', 'max_participants' => 1, 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);

        $this->actingAs($user)->post(route('activities.register', $activity))->assertRedirect();

        $this->assertDatabaseHas('activity_registrations', ['user_id' => $user->id, 'activity_id' => $activity->id, 'status' => 'registered']);
    }

    public function test_admin_can_create_and_update_activity_with_evidence_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('activities.store'), [
            'title' => 'Gotong Royong',
            'description' => 'Aktiviti membersihkan kawasan kolej.',
            'date_time' => now()->addWeek()->format('Y-m-d H:i:s'),
            'location' => 'Dewan Utama',
            'max_participants' => 30,
            'status' => 'draft',
            'evidence_photo' => UploadedFile::fake()->image('bukti-awal.jpg'),
        ])->assertRedirect();

        $activity = Activity::where('title', 'Gotong Royong')->firstOrFail();
        $firstPhoto = $activity->evidence_photo_path;

        $this->assertNotNull($firstPhoto);
        Storage::disk('public')->assertExists($firstPhoto);

        $this->actingAs($admin)->put(route('activities.update', $activity), [
            'title' => 'Gotong Royong Perdana',
            'description' => 'Aktiviti membersihkan kawasan kolej dan pejabat.',
            'date_time' => now()->addWeeks(2)->format('Y-m-d H:i:s'),
            'location' => 'Dewan Seminar',
            'max_participants' => 40,
            'status' => 'pending_approval',
            'evidence_photo' => UploadedFile::fake()->image('bukti-baru.jpg'),
        ])->assertRedirect(route('activities.show', $activity));

        $activity->refresh();

        $this->assertSame('Gotong Royong Perdana', $activity->title);
        $this->assertSame('Dewan Seminar', $activity->location);
        Storage::disk('public')->assertMissing($firstPhoto);
        Storage::disk('public')->assertExists($activity->evidence_photo_path);
    }

    public function test_activity_form_shows_specific_registration_date_validation_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);

        $this->from(route('activities.create'))->post(route('activities.store'), [
            'title' => 'Program Tarikh',
            'date_time' => now()->addWeek()->format('Y-m-d H:i:s'),
            'location' => 'Dewan',
            'status' => 'pending_approval',
            'registration_opens_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'registration_closes_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors([
            'registration_closes_at' => 'Registration Closes mesti sama atau selepas Registration Opens.',
        ]);
    }

    public function test_chairman_approval_emails_active_members_about_activity(): void
    {
        Mail::fake();

        $chairman = User::factory()->create(['role' => 'chairman']);
        $activeMember = User::factory()->create(['email' => 'active@example.test']);
        User::factory()->create(['email' => 'inactive@example.test', 'membership_status' => 'inactive']);
        User::factory()->create(['email' => 'treasurer@example.test', 'role' => 'treasurer']);
        $activity = Activity::create([
            'title' => 'Hari Keluarga',
            'description' => 'Aktiviti tahunan kelab staff.',
            'date_time' => now()->addWeek(),
            'location' => 'Dewan Utama',
            'status' => 'pending_approval',
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        $this->actingAs($chairman)->patch(route('activities.approve', $activity))->assertRedirect();

        $this->assertSame('approved', $activity->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $activeMember->id,
            'title' => 'Aktiviti diluluskan',
        ]);

        Mail::assertQueued(ActivityApprovedMail::class, fn (ActivityApprovedMail $mail) => $mail->hasTo('active@example.test'));
        Mail::assertNotQueued(ActivityApprovedMail::class, fn (ActivityApprovedMail $mail) => $mail->hasTo('inactive@example.test'));
        Mail::assertNotQueued(ActivityApprovedMail::class, fn (ActivityApprovedMail $mail) => $mail->hasTo('treasurer@example.test'));
    }

    public function test_cancelling_activity_emails_registered_and_waitlisted_members(): void
    {
        Mail::fake();
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $registeredMember = User::factory()->create(['email' => 'registered@example.test']);
        $waitlistedMember = User::factory()->create(['email' => 'waitlisted@example.test']);
        User::factory()->create(['email' => 'unregistered@example.test']);
        $activity = Activity::create([
            'title' => 'Kursus Keselamatan',
            'description' => 'Taklimat keselamatan kampus.',
            'date_time' => now()->addWeek(),
            'location' => 'Bilik Seminar',
            'status' => 'approved',
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        ActivityRegistration::create(['user_id' => $registeredMember->id, 'activity_id' => $activity->id, 'status' => 'registered', 'registered_at' => now()]);
        ActivityRegistration::create(['user_id' => $waitlistedMember->id, 'activity_id' => $activity->id, 'status' => 'waitlisted', 'registered_at' => now()]);

        $this->actingAs($admin)->put(route('activities.update', $activity), [
            'title' => 'Kursus Keselamatan',
            'description' => 'Taklimat keselamatan kampus.',
            'date_time' => now()->addWeek()->format('Y-m-d H:i:s'),
            'location' => 'Bilik Seminar',
            'status' => 'cancelled',
        ])->assertRedirect(route('activities.show', $activity));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $registeredMember->id,
            'title' => 'Aktiviti dibatalkan',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $waitlistedMember->id,
            'title' => 'Aktiviti dibatalkan',
        ]);

        Mail::assertQueued(ActivityCancelledMail::class, fn (ActivityCancelledMail $mail) => $mail->hasTo('registered@example.test'));
        Mail::assertQueued(ActivityCancelledMail::class, fn (ActivityCancelledMail $mail) => $mail->hasTo('waitlisted@example.test'));
        Mail::assertNotQueued(ActivityCancelledMail::class, fn (ActivityCancelledMail $mail) => $mail->hasTo('unregistered@example.test'));
    }

    public function test_treasurer_can_create_fee_transaction_and_reduce_balance(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['fee_balance' => 50]);

        $this->actingAs($treasurer)->post('/transactions', [
            'user_id' => $member->id,
            'type' => 'income',
            'amount' => 20,
            'description' => 'Bayaran yuran bulanan',
            'transaction_date' => now()->toDateString(),
            'category' => 'Yuran',
            'payment_method' => 'Tunai',
        ])->assertRedirect();

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame('30.00', $member->fresh()->fee_balance);
    }

    public function test_member_cannot_access_financial_module(): void
    {
        $this->actingAs(User::factory()->create())->get('/transactions')->assertForbidden();
    }

    public function test_member_can_submit_payment_proof(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['fee_balance' => 20]);

        $this->actingAs($user)->post('/payments', [
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof' => UploadedFile::fake()->create('proof.pdf', 120, 'application/pdf'),
            'notes' => 'Bayaran yuran',
        ])->assertRedirect('/payments');

        $payment = PaymentSubmission::first();
        $this->assertNotNull($payment);
        Storage::disk('public')->assertExists($payment->proof_path);
        $this->assertSame('pending', $payment->status);
    }

    public function test_payment_form_has_specific_field_validation_errors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/payments', [
            'amount' => 0,
            'payment_method' => '',
            'payment_date' => '',
        ])->assertSessionHasErrors([
            'amount' => 'Jumlah bayaran mesti sekurang-kurangnya RM 0.01.',
            'payment_method' => 'Sila pilih kaedah bayaran.',
            'payment_date' => 'Sila pilih tarikh bayaran.',
            'proof' => 'Sila upload fail bukti bayaran.',
        ]);
    }

    public function test_staff_can_create_polimart_listing(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['phone' => '0123456789']);

        $this->actingAs($user)->post(route('polimart.store'), [
            'name' => 'Kuih Raya',
            'category' => 'Makanan',
            'price' => 25,
            'contact' => '0123456789',
            'description' => 'Balang sederhana untuk pickup di pejabat.',
            'image' => UploadedFile::fake()->image('kuih-raya.jpg'),
        ])->assertRedirect();

        $item = PolimartItem::where('name', 'Kuih Raya')->first();
        $this->assertNotNull($item);
        $this->assertSame($user->id, $item->user_id);
        Storage::disk('public')->assertExists($item->image_path);
    }

    public function test_treasurer_can_approve_payment_and_generate_transaction(): void
    {
        Mail::fake();

        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['email' => 'payer@example.test', 'fee_balance' => 50]);
        $payment = PaymentSubmission::create([
            'user_id' => $member->id,
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof_path' => 'payment-proofs/test.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($treasurer)->patch(route('payments.approve', $payment), [
            'review_notes' => 'Verified',
        ])->assertRedirect(route('payments.show', $payment));

        $this->assertDatabaseHas('payment_submissions', ['id' => $payment->id, 'status' => 'approved', 'reviewed_by' => $treasurer->id]);
        $this->assertDatabaseHas('transactions', ['user_id' => $member->id, 'amount' => 20, 'category' => 'Yuran']);
        $this->assertSame('30.00', $member->fresh()->fee_balance);
        Mail::assertQueued(PaymentApprovedMail::class, fn (PaymentApprovedMail $mail) => $mail->hasTo('payer@example.test'));
    }

    public function test_payment_detail_shows_audit_timeline_for_management_roles(): void
    {
        Mail::fake();

        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['fee_balance' => 50]);
        $payment = PaymentSubmission::create([
            'user_id' => $member->id,
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof_path' => 'payment-proofs/test.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($treasurer)->patch(route('payments.approve', $payment), [
            'review_notes' => 'Verified',
        ]);

        $this->actingAs($treasurer)->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Timeline')
            ->assertSee('Approved payment proof and generated receipt');
    }

    public function test_treasurer_can_reject_payment_and_email_member(): void
    {
        Mail::fake();

        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['email' => 'payer@example.test', 'fee_balance' => 50]);
        $payment = PaymentSubmission::create([
            'user_id' => $member->id,
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof_path' => 'payment-proofs/test.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($treasurer)->patch(route('payments.reject', $payment), [
            'review_notes' => 'Bukti bayaran tidak jelas.',
        ])->assertRedirect(route('payments.show', $payment));

        $this->assertDatabaseHas('payment_submissions', [
            'id' => $payment->id,
            'status' => 'rejected',
            'review_notes' => 'Bukti bayaran tidak jelas.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'title' => 'Bayaran ditolak',
        ]);

        Mail::assertQueued(PaymentRejectedMail::class, fn (PaymentRejectedMail $mail) => $mail->hasTo('payer@example.test'));
    }

    public function test_chairman_can_approve_expense_claim_and_generate_expense(): void
    {
        Mail::fake();

        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $chairman = User::factory()->create(['role' => 'chairman']);
        $member = User::factory()->create(['email' => 'claimant@example.test']);
        SystemSetting::setValue('opening_balance', 100);
        $claim = ExpenseClaim::create([
            'user_id' => $member->id,
            'title' => 'Makanan program',
            'amount' => 30,
            'category' => 'Aktiviti',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/test.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($treasurer)->patch(route('claims.verify', $claim), ['treasurer_notes' => 'receipt checked'])->assertRedirect(route('claims.show', $claim));
        $this->actingAs($chairman)->patch(route('claims.approve', $claim), ['review_notes' => 'ok'])->assertRedirect(route('claims.show', $claim));

        $this->assertDatabaseHas('expense_claims', ['id' => $claim->id, 'status' => 'approved', 'treasurer_verified_by' => $treasurer->id]);
        $this->assertDatabaseHas('transactions', ['type' => 'expense', 'amount' => 30, 'category' => 'Aktiviti']);
        Mail::assertQueued(ExpenseClaimVerifiedMail::class, fn (ExpenseClaimVerifiedMail $mail) => $mail->hasTo('claimant@example.test'));
        Mail::assertQueued(ExpenseClaimApprovedMail::class, fn (ExpenseClaimApprovedMail $mail) => $mail->hasTo('claimant@example.test'));
    }

    public function test_chairman_can_reject_expense_claim_and_email_member(): void
    {
        Mail::fake();

        $chairman = User::factory()->create(['role' => 'chairman']);
        $member = User::factory()->create(['email' => 'claimant@example.test']);
        $claim = ExpenseClaim::create([
            'user_id' => $member->id,
            'title' => 'Alatan program',
            'amount' => 45,
            'category' => 'Aktiviti',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/test.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($chairman)->patch(route('claims.reject', $claim), [
            'review_notes' => 'Resit tidak lengkap.',
        ])->assertRedirect(route('claims.show', $claim));

        $this->assertDatabaseHas('expense_claims', [
            'id' => $claim->id,
            'status' => 'rejected',
            'review_notes' => 'Resit tidak lengkap.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'title' => 'Tuntutan ditolak',
        ]);

        Mail::assertQueued(ExpenseClaimRejectedMail::class, fn (ExpenseClaimRejectedMail $mail) => $mail->hasTo('claimant@example.test'));
    }

    public function test_claim_form_has_specific_field_validation_errors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('claims.store'), [
            'amount' => 0,
        ])->assertSessionHasErrors([
            'title' => 'Sila isi tajuk tuntutan.',
            'amount' => 'Jumlah tuntutan mesti sekurang-kurangnya RM 0.01.',
            'category' => 'Sila isi kategori tuntutan.',
            'claim_date' => 'Sila pilih tarikh tuntutan.',
            'receipt' => 'Sila upload resit tuntutan.',
        ]);
    }

    public function test_reject_payment_requires_specific_reason_message(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create();
        $payment = PaymentSubmission::create([
            'user_id' => $member->id,
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof_path' => 'payment-proofs/test.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($treasurer)->patch(route('payments.reject', $payment))
            ->assertSessionHasErrors(['review_notes' => 'Sila isi sebab bayaran ditolak.']);
    }

    public function test_admin_can_generate_monthly_fees(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['membership_status' => 'active', 'fee_balance' => 0]);
        SystemSetting::setValue('monthly_fee', 15);

        $this->actingAs($admin)->post(route('finance.fees.generate'))->assertRedirect();
        $this->actingAs($admin)->post(route('finance.fees.generate'))->assertRedirect();

        $this->assertSame('15.00', $member->fresh()->fee_balance);
        $this->assertDatabaseCount('member_fee_bills', 1);
    }

    public function test_fee_operations_page_is_available_to_finance_leadership_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $chairman = User::factory()->create(['role' => 'chairman']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($admin)->get(route('finance.fees.index'))
            ->assertOk()
            ->assertSee('Pengurusan Yuran')
            ->assertSee('Jana Bil');

        $this->actingAs($chairman)->get(route('finance.fees.index'))->assertOk();
        $this->actingAs($treasurer)->get(route('finance.fees.index'))->assertOk();
        $this->actingAs($member)->get(route('finance.fees.index'))->assertForbidden();

        $this->actingAs($chairman)->post(route('finance.fees.generate'))->assertForbidden();
        $this->actingAs($chairman)->post(route('finance.fees.reminders'))->assertForbidden();
    }

    public function test_monthly_fee_cycle_generates_bills_and_sends_reminders(): void
    {
        Mail::fake();

        $member = User::factory()->create(['email' => 'member@example.test', 'membership_status' => 'active', 'fee_balance' => 0]);
        User::factory()->create(['email' => 'inactive@example.test', 'membership_status' => 'inactive', 'fee_balance' => 0]);
        SystemSetting::setValue('monthly_fee', 20);

        $this->artisan('fees:monthly-cycle', ['--month' => '2026-09'])
            ->expectsOutput('Monthly fee cycle processed.')
            ->expectsOutput('Billing month: 09/2026')
            ->expectsOutput('New bills: 1')
            ->expectsOutput('Fee reminders processed. Emails sent: 1')
            ->assertSuccessful();

        $this->assertSame('20.00', $member->fresh()->fee_balance);
        $this->assertDatabaseCount('member_fee_bills', 1);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'title' => 'Peringatan tunggakan yuran',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'processed',
            'module' => 'Monthly Fee Cycle',
        ]);
        Mail::assertQueued(FeeReminderMail::class, fn (FeeReminderMail $mail) => $mail->hasTo('member@example.test'));

        $this->artisan('fees:monthly-cycle', ['--month' => '2026-09'])
            ->expectsOutput('New bills: 0')
            ->assertSuccessful();

        $this->assertSame('20.00', $member->fresh()->fee_balance);
        $this->assertDatabaseCount('member_fee_bills', 1);
    }

    public function test_fee_reminder_command_emails_active_members_with_outstanding_balance(): void
    {
        Mail::fake();

        $owingMember = User::factory()->create(['email' => 'owing@example.test', 'fee_balance' => 30, 'membership_status' => 'active']);
        User::factory()->create(['email' => 'paid@example.test', 'fee_balance' => 0, 'membership_status' => 'active']);
        User::factory()->create(['email' => 'inactive@example.test', 'fee_balance' => 30, 'membership_status' => 'inactive']);

        $this->artisan('fee:remind')
            ->expectsOutput('Fee reminders processed. Emails sent: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $owingMember->id,
            'title' => 'Peringatan tunggakan yuran',
        ]);

        Mail::assertQueued(FeeReminderMail::class, fn (FeeReminderMail $mail) => $mail->hasTo('owing@example.test'));
        Mail::assertNotQueued(FeeReminderMail::class, fn (FeeReminderMail $mail) => $mail->hasTo('paid@example.test'));
        Mail::assertNotQueued(FeeReminderMail::class, fn (FeeReminderMail $mail) => $mail->hasTo('inactive@example.test'));
    }

    public function test_admin_can_send_fee_reminders_from_settings_page(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['email' => 'owing@example.test', 'fee_balance' => 25, 'membership_status' => 'active']);

        $this->actingAs($admin)->post(route('finance.fees.reminders'))->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'title' => 'Peringatan tunggakan yuran',
        ]);

        Mail::assertQueued(FeeReminderMail::class, fn (FeeReminderMail $mail) => $mail->hasTo('owing@example.test'));
    }

    public function test_chairman_can_send_portal_notification_email_to_selected_user(): void
    {
        Mail::fake();

        $chairman = User::factory()->create(['role' => 'chairman']);
        $member = User::factory()->create(['email' => 'member@example.test']);

        $this->actingAs($chairman)->post(route('notifications.store'), [
            'target' => 'individual',
            'user_id' => $member->id,
            'title' => 'Mesyuarat Staff',
            'message' => 'Sila hadir mesyuarat pada hari Jumaat.',
            'type' => 'info',
            'link' => route('notifications.index'),
        ])->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'title' => 'Mesyuarat Staff',
        ]);

        Mail::assertQueued(PortalNotificationMail::class, fn (PortalNotificationMail $mail) => $mail->hasTo('member@example.test'));
    }

    public function test_admin_can_send_portal_notification_email_to_all_users(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.test']);
        $firstMember = User::factory()->create(['email' => 'first@example.test']);
        $secondMember = User::factory()->create(['email' => 'second@example.test']);

        $this->actingAs($admin)->post(route('notifications.store'), [
            'target' => 'all_active',
            'title' => 'Hebahan Umum',
            'message' => 'Hebahan kepada semua pengguna Polistaff.',
            'type' => 'info',
        ])->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'title' => 'Hebahan Umum',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $firstMember->id,
            'title' => 'Hebahan Umum',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $secondMember->id,
            'title' => 'Hebahan Umum',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => null,
            'title' => 'Hebahan Umum',
        ]);

        Mail::assertQueued(PortalNotificationMail::class, 3);
    }

    public function test_broadcast_notification_read_state_is_per_user(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();

        $this->actingAs($admin)->post(route('notifications.store'), [
            'target' => 'all_active',
            'title' => 'Hebahan Read State',
            'message' => 'Test read state.',
            'type' => 'info',
        ]);

        $firstNotification = $firstMember->portalNotifications()->where('title', 'Hebahan Read State')->firstOrFail();
        $secondNotification = $secondMember->portalNotifications()->where('title', 'Hebahan Read State')->firstOrFail();

        $this->actingAs($firstMember)->patch(route('notifications.read', $firstNotification))->assertRedirect();

        $this->assertTrue($firstNotification->fresh()->is_read);
        $this->assertFalse($secondNotification->fresh()->is_read);
    }

    public function test_admin_can_target_portal_notification_by_department(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $jtmkMember = User::factory()->create(['email' => 'jtmk@example.test', 'department' => 'JTMK', 'membership_status' => 'active']);
        User::factory()->create(['email' => 'jke@example.test', 'department' => 'JKE', 'membership_status' => 'active']);

        $this->actingAs($admin)->post(route('notifications.store'), [
            'target' => 'department',
            'department' => 'JTMK',
            'title' => 'Hebahan JTMK',
            'message' => 'Makluman khas untuk JTMK.',
            'type' => 'info',
        ])->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $jtmkMember->id,
            'title' => 'Hebahan JTMK',
        ]);

        Mail::assertQueued(PortalNotificationMail::class, fn (PortalNotificationMail $mail) => $mail->hasTo('jtmk@example.test'));
        Mail::assertNotQueued(PortalNotificationMail::class, fn (PortalNotificationMail $mail) => $mail->hasTo('jke@example.test'));
    }

    public function test_notification_form_requires_matching_target_field(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('notifications.store'), [
            'target' => 'department',
            'title' => 'Hebahan',
            'message' => 'Makluman.',
            'type' => 'info',
        ])->assertSessionHasErrors([
            'department' => 'Sila pilih department untuk sasaran department.',
        ]);
    }

    public function test_admin_can_enable_maintenance_and_notify_active_members(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create([
            'email' => 'maintenance-member@example.test',
            'membership_status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('settings.maintenance.enable'), [
            'maintenance_message' => 'Sistem sedang dinaik taraf untuk meningkatkan kestabilan.',
            'maintenance_estimated_end' => now()->addHour()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertSame('1', SystemSetting::getValue('maintenance_enabled'));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'title' => 'Penyelenggaraan sistem Polistaff',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'module' => 'System Maintenance',
            'action' => 'enabled',
        ]);
        Mail::assertQueued(PortalNotificationMail::class, fn (PortalNotificationMail $mail) => $mail->hasTo('maintenance-member@example.test'));

        $this->actingAs($member)->get(route('dashboard'))
            ->assertStatus(503)
            ->assertSee('Sistem sedang dinaik taraf untuk meningkatkan kestabilan.');

        $this->actingAs($admin)->get(route('settings.edit'))->assertOk();
    }

    public function test_login_remains_available_during_maintenance(): void
    {
        SystemSetting::setValue('maintenance_enabled', '1');
        SystemSetting::setValue('maintenance_message', 'Kerja penyelenggaraan sedang dijalankan.');

        $this->get('/')->assertStatus(503)->assertSee('Kerja penyelenggaraan sedang dijalankan.');
        $this->get(route('login'))->assertOk();

        $admin = User::factory()->create(['role' => 'admin', 'password' => 'password']);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
    }

    public function test_admin_can_disable_maintenance_and_restore_member_access(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create([
            'email' => 'restored-member@example.test',
            'membership_status' => 'active',
        ]);
        SystemSetting::setValue('maintenance_enabled', '1');

        $this->actingAs($admin)
            ->delete(route('settings.maintenance.disable'))
            ->assertRedirect();

        $this->assertSame('0', SystemSetting::getValue('maintenance_enabled'));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'title' => 'Polistaff kembali beroperasi',
        ]);
        Mail::assertQueued(PortalNotificationMail::class, fn (PortalNotificationMail $mail) => $mail->hasTo('restored-member@example.test'));

        $this->actingAs($member)->get(route('dashboard'))->assertOk();
    }

    public function test_admin_can_download_full_system_backup(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/member.jpg', 'fake-image-content');

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('backup.export'));

        $response->assertOk()->assertHeader('content-type', 'application/zip');
        $this->assertStringContainsString('polistaff-backup-', $response->headers->get('content-disposition'));

        $archive = $response->streamedContent();
        $this->assertStringStartsWith('PK', $archive);
        $this->assertStringContainsString('database/polistaff.sql', $archive);
        $this->assertStringContainsString('database/polistaff.json', $archive);
        $this->assertStringContainsString('manifest.json', $archive);
        $this->assertStringContainsString('uploads/profile-photos/member.jpg', $archive);
        $this->assertStringNotContainsString('.env', $archive);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'module' => 'Backup',
            'action' => 'exported',
        ]);
    }

    public function test_admin_can_inspect_a_valid_backup_without_restoring_data(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/member.jpg', 'verified-image-content');

        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['name' => 'Ahli Kekal']);
        $archive = $this->actingAs($admin)->get(route('backup.export'))->streamedContent();
        $userCount = User::count();

        $inspectionResponse = $this->actingAs($admin)->post(route('backup.inspect'), [
            'backup_file' => UploadedFile::fake()->createWithContent('polistaff-backup.zip', $archive),
        ]);

        $inspectionResponse->assertRedirect();
        $response = $this->actingAs($admin)->get($inspectionResponse->headers->get('Location'));
        $response->assertOk()
            ->assertSee('Sandaran sah dan boleh dipercayai')
            ->assertSee('Data pemulihan dan SQL telah disahkan')
            ->assertSee('1 daripada 1 fail disahkan')
            ->assertSee('Tiada data dipulihkan')
            ->assertSee('Pulihkan Sandaran');

        $this->assertSame($userCount, User::count());
        $this->assertSame('Ahli Kekal', $member->fresh()->name);
        $this->assertSame('verified-image-content', Storage::disk('public')->get('profile-photos/member.jpg'));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'module' => 'Backup',
            'action' => 'inspected',
        ]);
    }

    public function test_admin_can_restore_verified_database_and_uploads_with_a_safety_backup(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('documents/original.txt', 'original-file');

        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['name' => 'Nama Dalam Backup']);
        $archive = $this->actingAs($admin)->get(route('backup.export'))->streamedContent();

        $member->update(['name' => 'Nama Selepas Backup']);
        $extraMember = User::factory()->create(['name' => 'Pengguna Tambahan']);
        Storage::disk('public')->put('documents/original.txt', 'changed-file');
        Storage::disk('public')->put('documents/extra.txt', 'extra-file');

        $inspectionResponse = $this->actingAs($admin)->post(route('backup.inspect'), [
            'backup_file' => UploadedFile::fake()->createWithContent('restore-me.zip', $archive),
        ]);
        $inspectionResponse->assertRedirect();
        $previewUrl = $inspectionResponse->headers->get('Location');
        $token = basename(parse_url($previewUrl, PHP_URL_PATH));

        $this->actingAs($admin)->post(route('backup.restore'), [
            'restore_token' => $token,
            'confirmation' => 'PULIHKAN',
        ])->assertRedirect(route('settings.edit'));

        $this->assertSame('Nama Dalam Backup', User::findOrFail($member->id)->name);
        $this->assertNull(User::find($extraMember->id));
        $this->assertSame('original-file', Storage::disk('public')->get('documents/original.txt'));
        $this->assertFalse(Storage::disk('public')->exists('documents/extra.txt'));
        $this->assertNotEmpty(Storage::disk('local')->files('system-backups'));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'module' => 'Backup',
            'action' => 'restored',
        ]);
    }

    public function test_restore_requires_exact_confirmation_and_admin_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $token = (string) Str::uuid();

        $this->actingAs($admin)->from(route('backup.preview', $token))->post(route('backup.restore'), [
            'restore_token' => $token,
            'confirmation' => 'pulihkan',
        ])->assertSessionHasErrors('confirmation');

        $this->actingAs($member)->post(route('backup.restore'), [
            'restore_token' => $token,
            'confirmation' => 'PULIHKAN',
        ])->assertForbidden();
    }

    public function test_backup_inspector_rejects_a_corrupted_archive(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('backup.inspect'), [
            'backup_file' => UploadedFile::fake()->createWithContent('damaged-backup.zip', 'not-a-zip-archive'),
        ])->assertOk()
            ->assertSee('Sandaran gagal pemeriksaan')
            ->assertSee('bukan arkib ZIP yang sah');
    }

    public function test_non_admin_cannot_inspect_a_system_backup(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->post(route('backup.inspect'), [
            'backup_file' => UploadedFile::fake()->createWithContent('backup.zip', 'not-a-zip-archive'),
        ])->assertForbidden();
    }

    public function test_backup_cleanup_removes_expired_files_and_keeps_recent_safety_backups(): void
    {
        Storage::fake('local');
        $disk = Storage::disk('local');

        $disk->put('backup-previews/1/expired.zip', 'expired-preview');
        $disk->put('backup-previews/1/current.zip', 'current-preview');
        $disk->put('restore-workspaces/expired/incoming/file.txt', 'expired-workspace');
        $disk->put('system-backups/old-one.zip', 'old-one');
        $disk->put('system-backups/old-two.zip', 'old-two');
        $disk->put('system-backups/recent-one.zip', 'recent-one');
        $disk->put('system-backups/recent-two.zip', 'recent-two');

        touch($disk->path('backup-previews/1/expired.zip'), now()->subHours(2)->timestamp);
        touch($disk->path('restore-workspaces/expired/incoming/file.txt'), now()->subHours(8)->timestamp);
        touch($disk->path('system-backups/old-one.zip'), now()->subDays(45)->timestamp);
        touch($disk->path('system-backups/old-two.zip'), now()->subDays(40)->timestamp);

        $this->artisan('backup:cleanup', ['--keep' => 2])
            ->expectsOutput('Backup cleanup completed. Preview: 1, workspace: 1, safety backup: 2.')
            ->assertSuccessful();

        $this->assertFalse($disk->exists('backup-previews/1/expired.zip'));
        $this->assertTrue($disk->exists('backup-previews/1/current.zip'));
        $this->assertFalse($disk->exists('restore-workspaces/expired/incoming/file.txt'));
        $this->assertFalse($disk->exists('system-backups/old-one.zip'));
        $this->assertFalse($disk->exists('system-backups/old-two.zip'));
        $this->assertTrue($disk->exists('system-backups/recent-one.zip'));
        $this->assertTrue($disk->exists('system-backups/recent-two.zip'));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null,
            'module' => 'Backup',
            'action' => 'cleaned',
        ]);
    }

    public function test_chairman_can_monitor_but_cannot_modify_financial_transactions(): void
    {
        $chairman = User::factory()->create(['role' => 'chairman']);
        $transaction = Transaction::create([
            'type' => 'income',
            'amount' => 20,
            'description' => 'Kutipan yuran',
            'receipt_number' => 'PB-ACCESS-001',
            'transaction_date' => now()->toDateString(),
            'category' => 'Yuran',
            'status' => 'active',
        ]);

        $this->actingAs($chairman)->get(route('transactions.index'))->assertOk();
        $this->actingAs($chairman)->get(route('transactions.show', $transaction))->assertOk();
        $this->actingAs($chairman)->get(route('transactions.create'))->assertForbidden();
        $this->actingAs($chairman)->get(route('transactions.edit', $transaction))->assertForbidden();
        $this->actingAs($chairman)->delete(route('transactions.destroy', $transaction))->assertForbidden();

        $this->assertSame('active', $transaction->fresh()->status);
    }

    public function test_chairman_can_monitor_but_cannot_review_payments(): void
    {
        $chairman = User::factory()->create(['role' => 'chairman']);
        $member = User::factory()->create();
        $payment = PaymentSubmission::create([
            'user_id' => $member->id,
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof_path' => 'payment-proofs/test.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($chairman)->get(route('payments.show', $payment))
            ->assertOk()
            ->assertDontSee('Luluskan dan Jana Resit');
        $this->actingAs($chairman)->patch(route('payments.approve', $payment))->assertForbidden();
        $this->actingAs($chairman)->patch(route('payments.reject', $payment), ['review_notes' => 'Ditolak'])->assertForbidden();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_member_cannot_see_unpublished_activities_or_other_participants(): void
    {
        $member = User::factory()->create(['name' => 'Ahli Semasa']);
        $otherMember = User::factory()->create(['name' => 'Peserta Sulit']);
        $draft = Activity::create([
            'title' => 'Aktiviti Belum Diterbitkan',
            'date_time' => now()->addWeek(),
            'location' => 'Bilik Mesyuarat',
            'status' => 'pending_approval',
            'qr_code_token' => (string) Str::uuid(),
        ]);
        $approved = Activity::create([
            'title' => 'Aktiviti Terbuka',
            'date_time' => now()->addWeek(),
            'location' => 'Dewan',
            'status' => 'approved',
            'qr_code_token' => (string) Str::uuid(),
        ]);
        ActivityRegistration::create([
            'activity_id' => $approved->id,
            'user_id' => $otherMember->id,
            'status' => 'registered',
            'registered_at' => now(),
        ]);

        $this->actingAs($member)->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Aktiviti Terbuka')
            ->assertDontSee('Aktiviti Belum Diterbitkan');
        $this->actingAs($member)->get(route('activities.show', $draft))->assertNotFound();
        $this->actingAs($member)->get(route('activities.show', $approved))
            ->assertOk()
            ->assertDontSee('Peserta Sulit');
    }

    public function test_member_cannot_access_another_members_private_records(): void
    {
        Storage::fake('public');

        $member = User::factory()->create();
        $owner = User::factory()->create();
        $payment = PaymentSubmission::create([
            'user_id' => $owner->id,
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof_path' => 'payment-proofs/private.jpg',
            'status' => 'pending',
        ]);
        $claim = ExpenseClaim::create([
            'user_id' => $owner->id,
            'title' => 'Tuntutan Sulit',
            'amount' => 30,
            'category' => 'Aktiviti',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/private.pdf',
            'status' => 'pending',
        ]);
        $document = MemberDocument::create([
            'user_id' => $owner->id,
            'title' => 'Dokumen Sulit',
            'document_type' => 'Peribadi',
            'file_path' => 'member-documents/private.pdf',
        ]);

        $this->actingAs($member)->get(route('payments.show', $payment))->assertForbidden();
        $this->actingAs($member)->get(route('payments.proof', $payment))->assertForbidden();
        $this->actingAs($member)->get(route('claims.show', $claim))->assertForbidden();
        $this->actingAs($member)->get(route('claims.receipt', $claim))->assertForbidden();
        $this->actingAs($member)->get(route('documents.show', $document))->assertForbidden();
        $this->actingAs($member)->delete(route('documents.destroy', $document))->assertForbidden();
    }

    public function test_admin_cannot_remove_access_from_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'membership_status' => 'active']);

        $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'role' => 'member',
            'membership_status' => 'inactive',
            'fee_balance' => 0,
        ])->assertStatus(422);

        $admin->refresh();
        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->membership_status);
    }

    public function test_sensitive_admin_pages_reject_other_roles(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $chairman = User::factory()->create(['role' => 'chairman']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $this->actingAs($member)->get(route('settings.edit'))->assertForbidden();
        $this->actingAs($chairman)->get(route('backup.export'))->assertForbidden();
        $this->actingAs($treasurer)->get(route('admin.index'))->assertForbidden();
    }
}
