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
use App\Mail\PolimartOrderStatusMail;
use App\Mail\PortalNotificationMail;
use App\Models\Activity;
use App\Models\ActivityRegistration;
use App\Models\Attendance;
use App\Models\Donation;
use App\Models\ExpenseClaim;
use App\Models\PaymentSubmission;
use App\Models\PolimartItem;
use App\Models\PolimartOrder;
use App\Models\PolimartSellerPaymentProfile;
use App\Models\PolimartReport;
use App\Models\PolimartReview;
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
            'ic_number' => '900101111111',
            'department' => 'JTMK',
            'phone' => '0111111111',
            'address' => 'Besut',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'ali@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertSame('pending', $user->membership_status);
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
        $token = bin2hex(random_bytes(32));
        DB::table('email_verification_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'token' => $token,
        ]);

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Alamat emel '.$user->email.' berjaya disahkan. Admin boleh refresh senarai kelulusan ahli.');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'Authentication',
            'action' => 'verified-email',
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('verification.notice'));
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

        $this->actingAs($admin)->get(route('admin.users', ['profile' => 'incomplete']))
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

    public function test_inactive_member_cannot_access_member_features_before_approval(): void
    {
        $user = User::factory()->create(['membership_status' => 'inactive']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('membership.apply'));

        $this->actingAs($user)->get(route('payments.index'))
            ->assertRedirect(route('membership.apply'));

        $this->actingAs($user)->get(route('membership.apply'))
            ->assertOk();
    }

    public function test_management_roles_can_access_dashboard_without_active_membership(): void
    {
        foreach (['admin', 'treasurer'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'membership_status' => 'inactive',
            ]);

            $this->actingAs($user)->get(route('dashboard'))->assertOk();
        }
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
            'membership_review_notes' => 'Maklumat permohonan tidak lengkap.',
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

    public function test_rejected_member_can_view_reason_and_resubmit_application(): void
    {
        $user = User::factory()->create([
            'membership_status' => 'inactive',
            'membership_review_notes' => 'Sila kemas kini alamat semasa.',
        ]);

        $this->actingAs($user)->get(route('membership.apply'))
            ->assertOk()
            ->assertSee('Sila kemas kini alamat semasa.')
            ->assertSee('Hantar Semula Permohonan');

        $this->actingAs($user)->post(route('membership.store'), [
            'name' => $user->name,
            'ic_number' => '900101111111',
            'department' => 'JTMK',
            'phone' => '0111111111',
            'address' => 'Alamat baharu pemohon',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'membership_status' => 'pending',
            'membership_review_notes' => null,
            'address' => 'Alamat baharu pemohon',
        ]);
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
            ->assertSee('Taklimat Dashboard');
    }

    public function test_admin_dashboard_net_balance_ignores_reversed_transactions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Transaction::create([
            'type' => 'income',
            'amount' => 100,
            'description' => 'Income reversed',
            'receipt_number' => 'PB-REV-IN',
            'transaction_date' => now()->toDateString(),
            'category' => 'Yuran',
            'status' => 'reversed',
        ]);
        Transaction::create([
            'type' => 'income',
            'amount' => 25,
            'description' => 'Income active',
            'receipt_number' => 'PB-ACT-IN',
            'transaction_date' => now()->toDateString(),
            'category' => 'Yuran',
            'status' => 'active',
        ]);
        Transaction::create([
            'type' => 'expense',
            'amount' => 10,
            'description' => 'Expense reversed',
            'receipt_number' => 'PB-REV-OUT',
            'transaction_date' => now()->toDateString(),
            'category' => 'Operasi',
            'status' => 'reversed',
        ]);

        $this->actingAs($admin)->get(route('reports.financial'))
            ->assertOk()
            ->assertSee('RM 25.00');
    }

    public function test_role_dashboard_shows_relevant_pending_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Activity::create([
            'title' => 'Aktiviti Menunggu Kelulusan',
            'date_time' => now()->addWeek(),
            'location' => 'Dewan',
            'status' => 'pending_approval',
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
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
            'date_time' => now()->startOfMonth()->addDays(14)->setTime(9, 0),
            'location' => 'Dewan Utama',
            'status' => 'approved',
            'qr_code_token' => Str::uuid()->toString(),
        ]);
        Activity::create([
            'title' => 'Aktiviti Belum Diluluskan',
            'date_time' => now()->startOfMonth()->addDays(15)->setTime(9, 0),
            'location' => 'Bilik Mesyuarat',
            'status' => 'pending_approval',
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        $this->actingAs($member)->get(route('activities.index', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Aktiviti Dalam Kalendar')
            ->assertDontSee('Aktiviti Belum Diluluskan');
    }

    public function test_activity_status_cards_open_separate_lists_with_approved_actions_only(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $approved = Activity::create([
            'title' => 'Aktiviti Diluluskan Saya',
            'date_time' => now()->subHours(2),
            'end_time' => now()->subHour(),
            'location' => 'Dewan Utama',
            'status' => 'approved',
            'created_by' => $member->id,
            'qr_code_token' => null,
        ]);
        Activity::create([
            'title' => 'Aktiviti Ditolak Saya',
            'date_time' => now()->subDay(),
            'location' => 'Bilik Mesyuarat',
            'status' => 'rejected',
            'created_by' => $member->id,
            'qr_code_token' => null,
        ]);

        $this->actingAs($member)->get(route('activities.index'))
            ->assertOk()
            ->assertSee(route('activities.status-list', 'approved'))
            ->assertDontSee('<table class="table mobile-records mb-0">', false);

        $this->actingAs($member)->get(route('activities.status-list', 'approved'))
            ->assertOk()
            ->assertSee('Aktiviti Diluluskan Saya')
            ->assertSee('Lihat Butiran')
            ->assertSee('Muat Turun Kertas Kerja')
            ->assertSee(route('activities.show', $approved));

        $this->actingAs($member)->get(route('activities.status-list', 'rejected'))
            ->assertOk()
            ->assertSee('Aktiviti Ditolak Saya')
            ->assertDontSee('Lihat Butiran')
            ->assertDontSee('Muat Turun Kertas Kerja');
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

        $this->actingAs($treasurer)->get(route('reports.financial.csv', ['mode' => 'monthly', 'year' => 2026, 'month' => 9, 'download' => 1]))
            ->assertOk()
            ->assertSee('Yuran September', false)
            ->assertDontSee('Yuran Ogos', false);
    }

    public function test_financial_pdf_requires_preview_before_download(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $this->actingAs($treasurer)->get(route('reports.financial.pdf'))
            ->assertOk()
            ->assertSee('Semakan sebelum muat turun')
            ->assertSee('Muat Turun PDF');

        $this->actingAs($treasurer)->get(route('reports.financial.pdf', ['render' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="laporan-kewangan.pdf"');

        $this->actingAs($treasurer)->get(route('reports.financial.pdf', ['download' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-kewangan.pdf"');
    }

    public function test_attendance_report_offers_pdf_preview_and_download(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $this->actingAs($treasurer)->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('Eksport Laporan')
            ->assertSee('PDF')
            ->assertSee('CSV');

        $this->actingAs($treasurer)->get(route('reports.attendance.pdf'))
            ->assertOk()
            ->assertSee('Semakan sebelum muat turun')
            ->assertSee('Muat Turun PDF');

        $this->actingAs($treasurer)->get(route('reports.attendance.pdf', ['download' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-kehadiran.pdf"');
    }

    public function test_member_can_record_attendance_by_token(): void
    {
        $user = User::factory()->create();
        $activity = Activity::create(['title' => 'Program Sukan', 'date_time' => now()->subHour(), 'end_time' => now()->addHour(), 'location' => 'Padang', 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);
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
        $activity = Activity::create(['title' => 'Program Manual', 'date_time' => now()->subHour(), 'end_time' => now()->addHour(), 'location' => 'Dewan', 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);
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

    public function test_only_treasurer_can_generate_activity_qr_token(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create();
        $activity = Activity::create(['title' => 'Program QR', 'date_time' => now()->subMinute(), 'end_time' => now()->addHour(), 'location' => 'Dewan', 'status' => 'approved', 'qr_code_token' => null]);
        $oldToken = $activity->qr_code_token;

        ActivityRegistration::create(['user_id' => $member->id, 'activity_id' => $activity->id, 'status' => 'registered', 'registered_at' => now()]);

        $this->actingAs($admin)->patch(route('activities.refresh-qr', $activity))
            ->assertForbidden();

        $this->actingAs($treasurer)->patch(route('activities.refresh-qr', $activity))
            ->assertRedirect();

        $activity->refresh();

        $this->assertNotSame($oldToken, $activity->qr_code_token);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $treasurer->id,
            'action' => 'generated',
            'module' => 'Activity QR',
            'record_type' => Activity::class,
            'record_id' => $activity->id,
        ]);

        $this->actingAs($member)->post('/attendance/store', ['token' => $activity->qr_code_token])->assertRedirect(route('activities.show', $activity));

        $this->assertDatabaseHas('attendances', ['user_id' => $member->id, 'activity_id' => $activity->id]);
    }

    public function test_management_can_download_activity_attendance_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['name' => 'Ali Staff', 'email' => 'ali@example.test', 'department' => 'JTMK']);
        $activity = Activity::create(['title' => 'Program CSV', 'date_time' => now()->subHours(2), 'end_time' => now()->subHour(), 'location' => 'Dewan', 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);

        Attendance::create([
            'user_id' => $member->id,
            'activity_id' => $activity->id,
            'scanned_at' => now(),
            'qr_code_token' => $activity->qr_code_token,
        ]);

        $this->actingAs($admin)->get(route('reports.activities.attendance.csv', $activity))
            ->assertOk()
            ->assertSee('Semakan sebelum muat turun')
            ->assertSee('Ali Staff')
            ->assertSee('Muat Turun CSV');

        $this->actingAs($admin)->get(route('reports.activities.attendance.csv', ['activity' => $activity, 'download' => 1]))
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

    public function test_cancelling_activity_registration_promotes_waitlisted_member(): void
    {
        $firstMember = User::factory()->create(['role' => 'member']);
        $waitlistedMember = User::factory()->create(['role' => 'member']);
        $activity = Activity::create([
            'title' => 'Program Kapasiti Terhad',
            'date_time' => now()->addDay(),
            'location' => 'Dewan',
            'max_participants' => 1,
            'status' => 'approved',
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        $this->actingAs($firstMember)->post(route('activities.register', $activity))->assertRedirect();
        $this->actingAs($waitlistedMember)->post(route('activities.register', $activity))->assertRedirect();
        $this->assertDatabaseHas('activity_registrations', ['user_id' => $waitlistedMember->id, 'activity_id' => $activity->id, 'status' => 'waitlisted']);

        $this->actingAs($firstMember)->delete(route('activities.unregister', $activity))->assertRedirect();

        $this->assertDatabaseHas('activity_registrations', ['user_id' => $firstMember->id, 'activity_id' => $activity->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('activity_registrations', ['user_id' => $waitlistedMember->id, 'activity_id' => $activity->id, 'status' => 'registered']);
        $this->assertDatabaseHas('notifications', ['user_id' => $waitlistedMember->id, 'title' => 'Waiting list diluluskan']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Activity Registration', 'action' => 'cancelled']);
    }

    public function test_admin_can_create_and_update_activity_with_evidence_photo(): void
    {
        Storage::fake('public');
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->post(route('activities.store'), [
            'title' => 'Gotong Royong',
            'description' => 'Aktiviti membersihkan kawasan kolej.',
            'date_time' => now()->addWeek()->format('Y-m-d H:i:s'),
            'end_time' => '12:00',
            'location' => 'Dewan Utama',
            'max_participants' => 30,
            'status' => 'draft',
            'evidence_photo' => UploadedFile::fake()->image('bukti-awal.jpg'),
        ])->assertRedirect();

        $activity = Activity::where('title', 'Gotong Royong')->firstOrFail();
        $firstPhoto = $activity->evidence_photo_path;

        $this->assertNotNull($firstPhoto);
        Storage::disk('public')->assertExists($firstPhoto);

        $this->actingAs($member)->put(route('activities.update', $activity), [
            'title' => 'Gotong Royong Perdana',
            'description' => 'Aktiviti membersihkan kawasan kolej dan pejabat.',
            'date_time' => now()->addWeeks(2)->format('Y-m-d H:i:s'),
            'end_time' => '12:00',
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
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member);

        $this->from(route('activities.create'))->post(route('activities.store'), [
            'title' => 'Program Tarikh',
            'date_time' => now()->addWeek()->format('Y-m-d H:i:s'),
            'end_time' => '12:00',
            'location' => 'Dewan',
            'status' => 'pending_approval',
            'registration_opens_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'registration_closes_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors([
            'registration_closes_at' => 'Registration Closes mesti sama atau selepas Registration Opens.',
        ]);
    }

    public function test_activity_windows_must_surround_the_activity_time(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $activityTime = now()->addWeek();

        $this->actingAs($member)->post(route('activities.store'), [
            'title' => 'Program Window',
            'date_time' => $activityTime->format('Y-m-d H:i:s'),
            'end_time' => '12:00',
            'location' => 'Dewan',
            'status' => 'pending_approval',
            'registration_closes_at' => $activityTime->copy()->addHour()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors([
            'registration_closes_at' => 'Registration Closes mesti pada atau sebelum tarikh aktiviti.',
        ]);

    }

    public function test_chairman_approval_emails_active_members_about_activity(): void
    {
        Mail::fake();

        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $admin = User::factory()->create(['role' => 'admin']);
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

        $this->actingAs($admin)->patch(route('activities.verify', $activity))->assertForbidden();
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'status' => 'pending_approval', 'treasurer_verified_by' => null]);
        $this->actingAs($treasurer)->patch(route('activities.verify', $activity))->assertRedirect();
        $this->actingAs($admin)->patch(route('activities.approve', $activity))->assertRedirect();

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

        $this->actingAs($admin)->patch(route('activities.cancel', $activity))
            ->assertRedirect(route('activities.show', $activity));
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'status' => 'cancelled']);

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

    public function test_editing_fee_transaction_adjusts_only_the_difference(): void
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

        $transaction = Transaction::firstOrFail();

        $this->actingAs($treasurer)->put(route('transactions.update', $transaction), [
            'user_id' => $member->id,
            'type' => 'income',
            'amount' => 30,
            'description' => 'Bayaran yuran dikemas kini',
            'transaction_date' => now()->toDateString(),
            'category' => 'Yuran',
            'payment_method' => 'Tunai',
        ])->assertRedirect();

        $this->assertSame('20.00', $member->fresh()->fee_balance);
    }

    public function test_reversing_fee_transaction_restores_the_reduced_balance(): void
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

        $transaction = Transaction::firstOrFail();

        $this->actingAs($treasurer)->delete(route('transactions.destroy', $transaction), [
            'reversal_reason' => 'Pembetulan rekod',
        ])->assertRedirect();

        $this->assertSame('50.00', $member->fresh()->fee_balance);
        $this->assertSame('reversed', $transaction->fresh()->status);
    }

    public function test_member_cannot_access_financial_module(): void
    {
        $this->actingAs(User::factory()->create())->get('/transactions')->assertForbidden();
    }

    public function test_member_can_submit_payment_proof(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['fee_balance' => 20]);

        $this->actingAs($user)->post('/payments', [
            'months' => 3,
            'amount' => 30,
            'payment_method' => 'Online Transfer',
            'proof' => UploadedFile::fake()->create('proof.pdf', 120, 'application/pdf'),
            'notes' => 'Bayaran yuran',
        ])->assertRedirect('/payments');

        $payment = PaymentSubmission::first();
        $this->assertNotNull($payment);
        Storage::disk('private')->assertExists($payment->proof_path);
        $this->assertSame('pending', $payment->status);
    }

    public function test_payment_form_shows_paid_and_unpaid_bills_for_current_year(): void
    {
        $user = User::factory()->create();
        $paidMonth = now()->startOfMonth()->subMonth();
        $unpaidMonth = now()->startOfMonth();

        DB::table('member_fee_bills')->insert([
            'user_id' => $user->id,
            'billing_month' => $paidMonth->toDateString(),
            'due_date' => $paidMonth->copy()->endOfMonth(),
            'amount' => 20,
            'paid_amount' => 20,
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('member_fee_bills')->insert([
            'user_id' => $user->id,
            'billing_month' => $unpaidMonth->toDateString(),
            'due_date' => $unpaidMonth->copy()->endOfMonth(),
            'amount' => 20,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Bayaran bulan yang telah selesai')
            ->assertSee($paidMonth->format('F Y'))
            ->assertSee('Selesai')
            ->assertSee($unpaidMonth->format('F Y'))
            ->assertSee('Baki RM');
    }

    public function test_payment_form_has_specific_field_validation_errors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/payments', [
            'amount' => 0,
            'payment_method' => '',
        ])->assertSessionHasErrors([
            'months' => 'Sila pilih tempoh bayaran yuran.',
            'amount' => 'Jumlah bayaran mesti sekurang-kurangnya RM 0.01.',
            'payment_method' => 'Sila pilih kaedah bayaran.',
        ]);
    }

    public function test_member_can_resubmit_rejected_payment_proof(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['fee_balance' => 20]);
        $oldProof = UploadedFile::fake()->create('old-proof.pdf', 120, 'application/pdf');
        $oldPath = $oldProof->store('payment-proofs', 'private');
        $payment = PaymentSubmission::create([
            'user_id' => $user->id,
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof_path' => $oldPath,
            'status' => 'rejected',
            'review_notes' => 'Bukti tidak jelas.',
        ]);

        DB::table('member_fee_bills')->insert([
            'user_id' => $user->id,
            'billing_month' => now()->subMonth()->startOfMonth()->toDateString(),
            'due_date' => now()->subMonth()->endOfMonth()->toDateString(),
            'amount' => 20,
            'paid_amount' => 0,
            'status' => 'overdue',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->post(route('payments.resubmit', $payment), [
            'amount' => 20,
            'payment_method' => 'DuitNow',
            'payment_date' => now()->toDateString(),
            'proof' => UploadedFile::fake()->create('new-proof.pdf', 120, 'application/pdf'),
            'notes' => 'Bukti baharu.',
        ])->assertRedirect(route('payments.show', $payment));

        $payment = $payment->fresh();
        $this->assertSame('pending', $payment->status);
        $this->assertNull($payment->review_notes);
        $this->assertNotSame($oldPath, $payment->proof_path);
        Storage::disk('private')->assertMissing($oldPath);
        Storage::disk('private')->assertExists($payment->proof_path);
    }

    public function test_member_can_cancel_pending_payment_without_deleting_history(): void
    {
        $user = User::factory()->create();
        $payment = PaymentSubmission::create([
            'user_id' => $user->id,
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof_path' => 'payment-proofs/pending.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->delete(route('payments.cancel', $payment))
            ->assertRedirect('/payments');

        $this->assertDatabaseHas('payment_submissions', ['id' => $payment->id, 'status' => 'cancelled']);
    }

    public function test_staff_cannot_create_polimart_listing_or_access_seller_controls(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['phone' => '0123456789']);

        $this->actingAs($user)->get(route('polimart.index'))
            ->assertOk()
            ->assertDontSee('Mula Jual')
            ->assertDontSee('Listing Saya')
            ->assertDontSee('Maklumat Bayaran Saya')
            ->assertDontSee('Urus Pesanan Saya');
        $this->get(route('admin.polimart.orders'))->assertForbidden();
        $this->get(route('polimart.index', ['mine' => 1]))->assertForbidden();
        $this->get(route('polimart.create'))->assertForbidden();
        $this->get(route('polimart.payment-settings'))->assertForbidden();
        $this->put(route('polimart.payment-settings.update'), [])->assertForbidden();
        $this->get(route('admin.polimart.orders'))->assertForbidden();
        $this->post(route('polimart.store'), [
            'name' => 'Kuih Raya',
            'category' => 'Makanan',
            'price' => 25,
            'stock' => 1,
            'contact' => '0123456789',
            'description' => 'Balang sederhana untuk pickup di pejabat.',
            'image' => UploadedFile::fake()->image('kuih-raya.jpg'),
        ])->assertForbidden();

        $this->assertDatabaseMissing('polimart_items', ['name' => 'Kuih Raya']);

        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $this->actingAs($treasurer)->get(route('polimart.index'))
            ->assertOk()
            ->assertDontSee('Mula Jual')
            ->assertDontSee('Listing Saya')
            ->assertDontSee('Maklumat Bayaran Saya');
        $this->get(route('polimart.create'))->assertForbidden();
        $this->get(route('polimart.payment-settings'))->assertForbidden();
        $this->post(route('polimart.store'), [])->assertForbidden();
    }

    public function test_admin_can_create_polimart_listing(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('polimart.index'))
            ->assertOk()
            ->assertSee('Mula Jual')
            ->assertSee('Listing Saya')
            ->assertSee('Maklumat Bayaran Saya');
        $this->get(route('polimart.create'))->assertOk();
        $this->post(route('polimart.store'), [
            'name' => 'Kuih Raya',
            'category' => 'Makanan',
            'price' => 25,
            'stock' => 1,
            'contact' => '0123456789',
            'description' => 'Balang sederhana untuk pickup di pejabat.',
            'image' => UploadedFile::fake()->image('kuih-raya.jpg'),
        ])->assertRedirect();

        $item = PolimartItem::where('name', 'Kuih Raya')->firstOrFail();
        $this->assertSame($admin->id, $item->user_id);
        Storage::disk('public')->assertExists($item->image_path);
    }

    public function test_staff_can_search_polimart_by_name_and_category(): void
    {
        $user = User::factory()->create();
        PolimartItem::create(['user_id' => $user->id, 'name' => 'Brownies Coklat', 'category' => 'Makanan', 'price' => 18, 'contact' => '0123456789', 'status' => 'active']);
        PolimartItem::create(['user_id' => $user->id, 'name' => 'Lampu Meja', 'category' => 'Elektronik', 'price' => 30, 'contact' => '0123456789', 'status' => 'active']);

        $this->actingAs($user)->get(route('polimart.index', ['q' => 'Brownies', 'category' => 'Makanan']))
            ->assertOk()
            ->assertSee('Brownies Coklat')
            ->assertDontSee('Lampu Meja');
    }

    public function test_belian_saya_link_is_inside_the_polimart_card_only(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->get(route('polimart.index'))
            ->assertOk()
            ->assertSee('Belian Saya')
            ->assertSee(route('polimart.my-orders'));

        $this->assertSame(1, substr_count($response->getContent(), route('polimart.my-orders')));
    }

    public function test_staff_can_favorite_and_report_an_active_listing(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Kek', 'category' => 'Makanan', 'price' => 20, 'contact' => '0123456789', 'status' => 'active']);

        $this->actingAs($buyer)->post(route('polimart.favorite', $item))->assertRedirect();
        $this->assertDatabaseHas('polimart_favorites', ['user_id' => $buyer->id, 'polimart_item_id' => $item->id]);
        $this->actingAs($buyer)->post(route('polimart.favorite', $item))->assertRedirect();
        $this->assertDatabaseMissing('polimart_favorites', ['user_id' => $buyer->id, 'polimart_item_id' => $item->id]);
        $this->actingAs($buyer)->post(route('polimart.favorite', $item))->assertRedirect();

        $this->actingAs($buyer)->post(route('polimart.report', $item), ['reason' => 'misleading', 'details' => 'Maklumat harga tidak jelas.'])->assertRedirect();
        $this->assertDatabaseHas('polimart_reports', ['reporter_id' => $buyer->id, 'polimart_item_id' => $item->id, 'status' => 'pending']);

        $this->actingAs($buyer)->from(route('polimart.show', $item))->post(route('polimart.report', $item), ['reason' => 'other'])->assertRedirect(route('polimart.show', $item));
        $this->assertDatabaseCount('polimart_reports', 1);
    }

    public function test_staff_can_view_saved_polimart_favorites(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Baju Pre-loved', 'category' => 'Pakaian', 'price' => 15, 'contact' => '0123456789', 'status' => 'active']);

        $this->actingAs($buyer)->post(route('polimart.favorite', $item));
        $this->actingAs($buyer)->get(route('polimart.favorites'))->assertOk()->assertSee('Baju Pre-loved');
    }

    public function test_admin_can_hide_a_reported_polimart_listing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Listing Reported', 'category' => 'Lain-lain', 'price' => 10, 'contact' => '0123456789', 'status' => 'active']);
        $report = PolimartReport::create(['polimart_item_id' => $item->id, 'reporter_id' => $admin->id, 'reason' => 'other']);

        $this->actingAs($admin)->patch(route('admin.polimart.reports.update', $report), ['status' => 'hidden'])->assertRedirect();
        $this->assertDatabaseHas('polimart_reports', ['id' => $report->id, 'status' => 'hidden', 'reviewed_by' => $admin->id]);
        $this->assertDatabaseHas('polimart_items', ['id' => $item->id, 'status' => 'hidden']);
    }

    public function test_admin_can_remove_listing_while_preserving_report_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Listing Scam', 'category' => 'Lain-lain', 'price' => 10, 'contact' => '0123456789', 'status' => 'active']);
        $report = PolimartReport::create(['polimart_item_id' => $item->id, 'reporter_id' => $admin->id, 'reason' => 'scam']);

        $this->actingAs($admin)->patch(route('admin.polimart.reports.update', $report), ['status' => 'removed'])->assertRedirect();
        $this->assertDatabaseMissing('polimart_items', ['id' => $item->id]);
        $this->assertDatabaseHas('polimart_reports', ['id' => $report->id, 'status' => 'removed', 'polimart_item_id' => null]);
    }

    public function test_admin_removal_of_reported_listing_deletes_its_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create();
        $imagePath = UploadedFile::fake()->image('reported.jpg')->store('polimart-items', 'public');
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Listing Scam', 'category' => 'Lain-lain', 'price' => 10, 'contact' => '0123456789', 'image_path' => $imagePath, 'status' => 'active']);
        $report = PolimartReport::create(['polimart_item_id' => $item->id, 'reporter_id' => $admin->id, 'reason' => 'scam']);

        $this->actingAs($admin)->patch(route('admin.polimart.reports.update', $report), ['status' => 'removed'])->assertRedirect();
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_buyer_can_review_a_sold_polimart_listing(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'contact' => '0123456789', 'status' => 'sold']);
        $this->createPolimartOrder($item, $buyer, 'completed');

        $this->actingAs($buyer)->get(route('polimart.show', $item))
            ->assertOk()
            ->assertSee('Berikan ulasan');
        $this->actingAs($buyer)->post(route('polimart.review', $item), ['rating' => 5, 'comment' => 'Urusan mudah.'])->assertRedirect();
        $this->assertDatabaseHas('polimart_reviews', ['user_id' => $buyer->id, 'polimart_item_id' => $item->id, 'rating' => 5]);
        $this->assertInstanceOf(PolimartReview::class, $item->reviews()->first());
    }

    public function test_non_buyer_cannot_review_a_sold_polimart_listing(): void
    {
        $seller = User::factory()->create();
        $member = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'contact' => '0123456789', 'status' => 'sold']);

        $this->actingAs($member)->get(route('polimart.show', $item))->assertDontSee('Review barang');
        $this->actingAs($member)->post(route('polimart.review', $item), ['rating' => 5])->assertForbidden();
        $this->assertDatabaseCount('polimart_reviews', 0);
    }

    public function test_order_must_be_completed_before_buyer_can_review(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'stock' => 2, 'contact' => '0123456789', 'status' => 'active']);
        $order = $this->createPolimartOrder($item, $buyer, 'pending');
        $order->update(['payment_status' => 'paid']);

        $this->actingAs($buyer)->post(route('polimart.review', $item), ['rating' => 5])->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.polimart.orders.update', $order), ['status' => 'confirmed'])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.polimart.orders.update', $order), ['status' => 'completed'])->assertRedirect();
        $this->actingAs($buyer)->get(route('polimart.show', $item))->assertOk()->assertSee('Berikan ulasan');
        $this->actingAs($buyer)->post(route('polimart.review', $item), ['rating' => 5])->assertRedirect();
        $this->assertDatabaseHas('polimart_reviews', ['user_id' => $buyer->id, 'polimart_item_id' => $item->id]);
        Mail::assertSent(PolimartOrderStatusMail::class, 2);
    }

    public function test_polimart_order_cancellation_restores_stock_and_cannot_be_repeated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'stock' => 0, 'contact' => '0123456789', 'status' => 'sold']);
        $order = $this->createPolimartOrder($item, User::factory()->create(), 'pending', 2);

        $this->actingAs($admin)->patch(route('admin.polimart.orders.update', $order), ['status' => 'cancelled'])->assertRedirect();
        $this->assertDatabaseHas('polimart_items', ['id' => $item->id, 'stock' => 2, 'status' => 'active']);
        $this->assertDatabaseHas('polimart_orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->actingAs($admin)->patch(route('admin.polimart.orders.update', $order), ['status' => 'cancelled'])->assertUnprocessable();
        $this->assertDatabaseHas('polimart_items', ['id' => $item->id, 'stock' => 2]);
    }

    public function test_polimart_checkout_keeps_listing_active_until_stock_reaches_zero(): void
    {
        Mail::fake();
        $seller = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'stock' => 2, 'contact' => '0123456789', 'status' => 'active']);
        PolimartSellerPaymentProfile::create(['user_id' => $seller->id, 'qr_code_path' => 'polimart-payment-qr/ujian.png', 'bank_name' => 'Bank Ujian', 'account_name' => 'Seller Ujian', 'account_number' => '1234567890']);
        $checkout = [
            'customer_name' => 'Pembeli Ujian', 'customer_email' => 'checkout@example.test',
            'customer_phone' => '0123456789', 'address_line_1' => '1 Jalan Staf',
            'city' => 'Kuala Lumpur', 'postcode' => '50000', 'state' => 'Kuala Lumpur',
            'payment_method' => 'qr', 'terms' => '1',
        ];

        $this->post(route('polimart.cart.add', $item), ['quantity' => 1])->assertRedirect();
        $this->post(route('polimart.checkout.store'), $checkout)->assertOk()->assertSee('Semak Status Pesanan');
        $this->assertDatabaseHas('polimart_items', ['id' => $item->id, 'stock' => 1, 'status' => 'active']);
        $this->assertDatabaseHas('polimart_orders', ['customer_email' => 'checkout@example.test', 'payment_method' => 'qr']);
        $this->assertSame('qr', PolimartOrder::where('customer_email', 'checkout@example.test')->firstOrFail()->payment_instructions['method']);
        Mail::assertSent(PolimartOrderStatusMail::class, fn (PolimartOrderStatusMail $mail): bool => $mail->hasTo('checkout@example.test'));

        $this->post(route('polimart.cart.add', $item), ['quantity' => 1])->assertRedirect();
        $this->post(route('polimart.checkout.store'), $checkout)->assertOk();
        $this->assertDatabaseHas('polimart_items', ['id' => $item->id, 'stock' => 0, 'status' => 'sold']);
    }

    public function test_logged_in_buyer_sees_only_orders_linked_to_their_account(): void
    {
        Mail::fake();
        $buyer = User::factory()->create(['role' => 'member']);
        $otherBuyer = User::factory()->create(['role' => 'member']);
        $seller = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'stock' => 2, 'contact' => '0123456789', 'status' => 'active']);
        PolimartSellerPaymentProfile::create([
            'user_id' => $seller->id,
            'qr_code_path' => 'polimart-payment-qr/test.png',
            'account_name' => 'Seller Ujian',
        ]);
        $otherOrder = $this->createPolimartOrder($item, $otherBuyer, 'pending');
        $otherOrder->update(['user_id' => $otherBuyer->id]);

        $this->actingAs($buyer)->post(route('polimart.cart.add', $item), ['quantity' => 1])->assertRedirect();
        $this->post(route('polimart.checkout.store'), [
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'customer_phone' => '0123456789',
            'address_line_1' => '1 Jalan Staf',
            'city' => 'Kuala Lumpur',
            'postcode' => '50000',
            'state' => 'Kuala Lumpur',
            'payment_method' => 'qr',
            'terms' => '1',
        ])->assertOk()->assertSee('Semak Status Pesanan');

        $buyerOrder = PolimartOrder::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame('awaiting_payment', $buyerOrder->payment_status);
        $this->actingAs($buyer)->get(route('polimart.my-orders'))
            ->assertOk()
            ->assertSee($buyerOrder->order_number)
            ->assertDontSee($otherOrder->order_number);
    }

    public function test_polimart_pages_expire_due_orders_and_release_reserved_stock(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'stock' => 0, 'contact' => '0123456789', 'status' => 'sold']);
        $order = $this->createPolimartOrder($item, $buyer, 'pending');
        $order->update([
            'payment_status' => 'awaiting_payment',
            'payment_expires_at' => now()->subMinute(),
        ]);

        $this->get(route('polimart.index'))->assertOk();

        $this->assertDatabaseHas('polimart_orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'payment_status' => 'expired',
        ]);
        $this->assertDatabaseHas('polimart_items', [
            'id' => $item->id,
            'stock' => 1,
            'status' => 'active',
        ]);
    }

    public function test_polimart_payment_proof_rejection_resubmission_and_order_completion(): void
    {
        Storage::fake('private');
        Mail::fake();
        $seller = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'member']);
        $item = PolimartItem::create([
            'user_id' => $seller->id,
            'name' => 'Buku Ujian',
            'category' => 'Pre-loved',
            'price' => 20,
            'stock' => 1,
            'contact' => '0123456789',
            'status' => 'sold',
        ]);
        $order = $this->createPolimartOrder($item, $buyer, 'pending');
        $order->update([
            'user_id' => $buyer->id,
            'payment_method' => 'qr',
            'payment_status' => 'awaiting_payment',
            'payment_expires_at' => now()->addDay(),
        ]);
        $proofUrl = fn () => URL::temporarySignedRoute('polimart.orders.payment-proof.submit', now()->addDays(3), ['polimartOrder' => $order->id]);

        $this->actingAs($buyer)->post($proofUrl(), [
            'payment_proof' => UploadedFile::fake()->image('bukti-bayaran.png'),
            'payment_reference' => 'REF-TEST-01',
        ])->assertRedirect();
        $order->refresh();
        $this->assertSame('proof_submitted', $order->payment_status);
        Storage::disk('private')->assertExists($order->payment_proof_path);

        $this->actingAs($seller)->patch(route('admin.polimart.orders.payment-reject', $order), [
            'payment_review_note' => 'Bukti kurang jelas.',
        ])->assertRedirect();
        $this->assertSame('rejected', $order->fresh()->payment_status);

        $this->actingAs($buyer)->post($proofUrl(), [
            'payment_proof' => UploadedFile::fake()->image('bukti-bayaran-baru.png'),
            'payment_reference' => 'REF-TEST-02',
        ])->assertRedirect();
        $this->assertSame('proof_submitted', $order->fresh()->payment_status);

        $this->actingAs($seller)->patch(route('admin.polimart.orders.payment-confirm', $order))->assertRedirect();
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->payment_paid_at);

        $this->actingAs($seller)->patch(route('admin.polimart.orders.update', $order), ['status' => 'confirmed'])->assertRedirect();
        $this->actingAs($seller)->patch(route('admin.polimart.orders.update', $order), ['status' => 'completed'])->assertRedirect();
        $this->assertDatabaseHas('polimart_orders', ['id' => $order->id, 'status' => 'completed', 'payment_status' => 'paid']);

        $refundOrder = $this->createPolimartOrder($item, $buyer, 'confirmed');
        $refundOrder->update(['payment_method' => 'qr', 'payment_status' => 'paid']);
        $this->actingAs($seller)->patch(route('admin.polimart.orders.update', $refundOrder), ['status' => 'cancelled'])->assertRedirect();
        $this->assertDatabaseHas('polimart_orders', ['id' => $refundOrder->id, 'status' => 'cancelled', 'payment_status' => 'refund_required']);
        $this->actingAs($seller)->patch(route('admin.polimart.orders.refund-confirm', $refundOrder))->assertRedirect();
        $this->assertSame('refunded', $refundOrder->fresh()->payment_status);
    }

    public function test_checkout_rejects_mixed_sellers_and_requires_saved_payment_details(): void
    {
        $sellerOne = User::factory()->create();
        $sellerTwo = User::factory()->create();
        $first = PolimartItem::create(['user_id' => $sellerOne->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'stock' => 2, 'contact' => '0123456789', 'status' => 'active']);
        $second = PolimartItem::create(['user_id' => $sellerTwo->id, 'name' => 'Beg', 'category' => 'Pre-loved', 'price' => 20, 'stock' => 2, 'contact' => '0191234567', 'status' => 'active']);

        $this->post(route('polimart.cart.add', $first), ['quantity' => 1])->assertRedirect();
        $this->from(route('polimart.show', $second))->post(route('polimart.cart.add', $second), ['quantity' => 1])->assertRedirect(route('polimart.show', $second));
        $this->assertSame([$first->id], array_keys(session('polimart_cart')));
        $this->get(route('polimart.checkout'))->assertOk()->assertSee('belum menyediakan');
    }

    public function test_seller_can_save_payment_qr_and_bank_details(): void
    {
        Storage::fake('public');
        $seller = User::factory()->create(['role' => 'admin']);

        $this->actingAs($seller)->put(route('polimart.payment-settings.update'), [
            'qr_code' => UploadedFile::fake()->image('bayaran.png'),
            'bank_name' => 'Bank Ujian',
            'account_name' => 'Penjual Ujian',
            'account_number' => '1234567890',
        ])->assertRedirect();

        $profile = PolimartSellerPaymentProfile::where('user_id', $seller->id)->firstOrFail();
        $this->assertSame('Bank Ujian', $profile->bank_name);
        $this->assertSame('Penjual Ujian', $profile->account_name);
        Storage::disk('public')->assertExists($profile->qr_code_path);
    }

    public function test_guest_can_track_order_with_signed_link_but_not_unsigned_order_url(): void
    {
        $seller = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'stock' => 1, 'contact' => '0123456789', 'status' => 'active']);
        $order = $this->createPolimartOrder($item, User::factory()->create(), 'pending');
        $signedUrl = URL::temporarySignedRoute('polimart.orders.track', now()->addDay(), ['polimartOrder' => $order->id]);

        $this->get($signedUrl)->assertOk()->assertSee($order->order_number)->assertDontSee($order->customer_email)->assertDontSee($order->customer_phone);
        $this->get(route('polimart.orders.track', $order))->assertForbidden();
    }

    public function test_only_listing_owner_or_admin_can_edit_or_delete_polimart_listing(): void
    {
        $seller = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'contact' => '0123456789', 'status' => 'active']);

        $this->actingAs($other)->get(route('polimart.edit', $item))->assertForbidden();
        $this->actingAs($other)->put(route('polimart.update', $item), [])->assertForbidden();
        $this->actingAs($other)->delete(route('polimart.destroy', $item))->assertForbidden();
        $this->actingAs($other)->patch(route('polimart.status', $item), ['status' => 'sold'])->assertForbidden();
        $this->actingAs($seller)->get(route('polimart.edit', $item))->assertOk();
    }

    public function test_hidden_polimart_listing_is_not_publicly_visible(): void
    {
        $seller = User::factory()->create();
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Listing Tersembunyi', 'category' => 'Lain-lain', 'price' => 10, 'contact' => '0123456789', 'status' => 'hidden']);

        $this->get(route('polimart.index'))->assertOk()->assertDontSee('Listing Tersembunyi');
        $this->get(route('polimart.show', $item))->assertNotFound();
    }

    public function test_seller_can_replace_and_delete_polimart_listing_image(): void
    {
        Storage::fake('public');
        $seller = User::factory()->create(['role' => 'admin']);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('polimart-items', 'public');
        $item = PolimartItem::create(['user_id' => $seller->id, 'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'contact' => '0123456789', 'image_path' => $oldPath, 'status' => 'active']);

        $this->actingAs($seller)->put(route('polimart.update', $item), [
            'name' => 'Buku', 'category' => 'Pre-loved', 'price' => 12, 'stock' => 1,
            'contact' => '0123456789', 'image' => UploadedFile::fake()->image('new.jpg'),
        ])->assertRedirect();
        $item->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($item->image_path);

        $newPath = $item->image_path;
        $this->actingAs($seller)->delete(route('polimart.destroy', $item))->assertRedirect();
        Storage::disk('public')->assertMissing($newPath);
    }

    private function createPolimartOrder(PolimartItem $item, User $buyer, string $status, int $quantity = 1): PolimartOrder
    {
        return PolimartOrder::create([
            'order_number' => 'PM-'.strtoupper(Str::random(10)),
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'customer_phone' => '0123456789',
            'address_line_1' => '1 Jalan Staf',
            'city' => 'Kuala Lumpur',
            'postcode' => '50000',
            'state' => 'Kuala Lumpur',
            'items' => [['id' => $item->id, 'seller_id' => $item->user_id, 'name' => $item->name, 'quantity' => $quantity, 'price' => (float) $item->price, 'seller' => $item->user->name]],
            'subtotal' => (float) $item->price * $quantity,
            'shipping_fee' => 7,
            'total' => ((float) $item->price * $quantity) + 7,
            'status' => $status,
        ]);
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

    public function test_admin_can_approve_treasurer_verified_expense_claim_and_generate_expense(): void
    {
        Mail::fake();

        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['email' => 'claimant@example.test']);
        SystemSetting::setValue('opening_balance', 100);
        $claim = ExpenseClaim::create([
            'user_id' => $member->id,
            'title' => 'Khairat Kematian',
            'amount' => 100,
            'category' => 'Khairat Kematian',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/test.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($treasurer)->patch(route('claims.verify', $claim), ['treasurer_notes' => 'receipt checked'])->assertRedirect(route('claims.index'));
        $this->actingAs($admin)->patch(route('claims.approve', $claim), ['review_notes' => 'ok'])->assertRedirect(route('claims.index'));

        $this->assertDatabaseHas('expense_claims', ['id' => $claim->id, 'status' => 'approved', 'treasurer_verified_by' => $treasurer->id]);
        $this->assertDatabaseHas('transactions', ['type' => 'expense', 'amount' => 100, 'category' => 'Khairat Kematian']);
        Mail::assertQueued(ExpenseClaimVerifiedMail::class, fn (ExpenseClaimVerifiedMail $mail) => $mail->hasTo('claimant@example.test'));
        Mail::assertQueued(ExpenseClaimApprovedMail::class, fn (ExpenseClaimApprovedMail $mail) => $mail->hasTo('claimant@example.test'));
    }

    public function test_admin_is_redirected_with_clear_message_when_claim_has_not_been_verified_by_treasurer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create();
        $claim = ExpenseClaim::create([
            'user_id' => $member->id,
            'title' => 'Tuntutan belum disemak',
            'amount' => 30,
            'category' => 'Aktiviti',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/test.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->patch(route('claims.approve', $claim), ['review_notes' => 'Semakan admin'])
            ->assertRedirect(route('claims.index'))
            ->assertSessionHas('claim_error', 'Tuntutan ini perlu disahkan Bendahari sebelum Admin boleh meluluskannya.');

        $this->assertDatabaseHas('expense_claims', ['id' => $claim->id, 'status' => 'pending', 'transaction_id' => null]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_admin_is_redirected_with_clear_message_when_claim_exceeds_available_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create();
        SystemSetting::setValue('opening_balance', 10);
        $claim = ExpenseClaim::create([
            'user_id' => $member->id,
            'title' => 'Tuntutan melebihi baki',
            'amount' => 30,
            'category' => 'Aktiviti',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/test.pdf',
            'status' => 'treasurer_verified',
            'treasurer_verified_by' => $treasurer->id,
            'treasurer_verified_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('claims.approve', $claim), ['review_notes' => 'Semakan admin'])
            ->assertRedirect(route('claims.index'))
            ->assertSessionHas('claim_error', 'Baki kelab tidak mencukupi untuk meluluskan tuntutan ini.');

        $this->assertDatabaseHas('expense_claims', ['id' => $claim->id, 'status' => 'treasurer_verified', 'transaction_id' => null]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_repeated_treasurer_verification_returns_to_list_without_duplicate_processing(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create();
        $claim = ExpenseClaim::create([
            'user_id' => $member->id,
            'title' => 'Tuntutan sudah disokong',
            'amount' => 30,
            'category' => 'Aktiviti',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/test.pdf',
            'status' => 'treasurer_verified',
            'treasurer_verified_by' => $treasurer->id,
            'treasurer_verified_at' => now(),
        ]);

        $this->actingAs($treasurer)->patch(route('claims.verify', $claim), ['treasurer_notes' => 'Ulangan'])
            ->assertRedirect(route('claims.index'))
            ->assertSessionHas('claim_notice');

        $this->assertDatabaseHas('expense_claims', ['id' => $claim->id, 'status' => 'treasurer_verified', 'treasurer_notes' => null]);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_only_treasurer_can_review_pending_claim_before_admin_decision(): void
    {
        Mail::fake();

        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create();
        $claim = ExpenseClaim::create([
            'user_id' => $member->id,
            'title' => 'Tuntutan ujian aliran',
            'amount' => 30,
            'category' => 'Aktiviti',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/test.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get(route('claims.show', $claim))
            ->assertOk()
            ->assertSee('Tuntutan ini menunggu semakan Bendahari.')
            ->assertDontSee('Sahkan sebagai Bendahari')
            ->assertDontSee('Tolak Tuntutan');
        $this->actingAs($admin)->get(route('claims.index'))
            ->assertOk()
            ->assertDontSee('data-kind="support"', false)
            ->assertDontSee('data-kind="reject"', false);

        $this->actingAs($admin)->patch(route('claims.verify', $claim), ['treasurer_notes' => 'Cuba pintas'])
            ->assertForbidden();
        $this->actingAs($admin)->patch(route('claims.reject', $claim), ['review_notes' => 'Cuba pintas'])
            ->assertForbidden();
        $this->assertDatabaseHas('expense_claims', ['id' => $claim->id, 'status' => 'pending', 'treasurer_verified_by' => null]);

        $this->actingAs($treasurer)->patch(route('claims.verify', $claim), ['treasurer_notes' => 'Dokumen disemak'])
            ->assertRedirect(route('claims.index'));
        $this->assertDatabaseHas('expense_claims', [
            'id' => $claim->id,
            'status' => 'treasurer_verified',
            'treasurer_verified_by' => $treasurer->id,
        ]);

        $this->actingAs($admin)->get(route('claims.show', $claim))
            ->assertOk()
            ->assertSee('Luluskan Tuntutan')
            ->assertSee('Tolak Tuntutan')
            ->assertDontSee('Sahkan sebagai Bendahari');
        $this->actingAs($admin)->get(route('claims.index'))
            ->assertOk()
            ->assertSee('data-kind="approve"', false)
            ->assertSee('data-kind="reject"', false)
            ->assertDontSee('data-kind="support"', false);

        $this->actingAs($admin)->patch(route('claims.reject', $claim), ['review_notes' => 'Tidak memenuhi syarat'])
            ->assertRedirect(route('claims.index'));
        $this->assertDatabaseHas('expense_claims', [
            'id' => $claim->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_staff_treasurer_admin_donation_flow_enforces_two_stage_approval(): void
    {
        Storage::fake('private');
        Mail::fake();
        SystemSetting::setValue('opening_balance', 500);

        $staff = User::factory()->create(['role' => 'member', 'membership_status' => 'active']);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'membership_status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'membership_status' => 'active']);

        $this->actingAs($staff)->post(route('donations.store'), [
            'category' => 'Sumbangan Kebajikan',
            'description' => 'Bantuan kecemasan untuk ahli.',
            'approved_paperwork' => UploadedFile::fake()->create('kelulusan.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('donations.index'));

        $donation = Donation::query()->where('user_id', $staff->id)->sole();
        $this->assertSame('pending', $donation->status);
        Storage::disk('private')->assertExists($donation->paperwork_path);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Donation', 'record_id' => $donation->id, 'action' => 'submitted']);
        $this->assertDatabaseHas('notifications', ['user_id' => $treasurer->id, 'link' => route('donations.show', $donation)]);

        $this->actingAs($admin)->patch(route('donations.verify', $donation), ['limit_amount' => 200])->assertForbidden();
        $this->assertSame('pending', $donation->fresh()->status);

        $this->actingAs($treasurer)->patch(route('donations.verify', $donation), [
            'limit_amount' => 200,
            'treasurer_notes' => 'Disokong dalam had yang ditetapkan.',
        ])->assertRedirect();

        $donation->refresh();
        $this->assertSame('treasurer_verified', $donation->status);
        $this->assertSame($treasurer->id, $donation->treasurer_verified_by);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'link' => route('donations.show', $donation)]);

        $this->actingAs($treasurer)->patch(route('donations.approve', $donation), ['amount' => 150])->assertForbidden();
        $this->actingAs($admin)->patch(route('donations.approve', $donation), [
            'amount' => 150,
            'review_notes' => 'Diluluskan mengikut had bendahari.',
        ])->assertRedirect();

        $donation->refresh();
        $this->assertSame('approved', $donation->status);
        $this->assertSame('150.00', $donation->amount);
        $this->assertNotNull($donation->transaction_id);
        $this->assertDatabaseHas('transactions', [
            'id' => $donation->transaction_id,
            'type' => 'expense',
            'amount' => 150,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Donation', 'record_id' => $donation->id, 'action' => 'approved']);
        $this->assertDatabaseHas('notifications', ['user_id' => $staff->id, 'title' => 'Sumbangan diluluskan']);
        $this->actingAs($admin)->patch(route('donations.approve', $donation), ['amount' => 150])->assertStatus(422);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_admin_cannot_approve_donation_above_treasurer_limit_or_available_balance(): void
    {
        SystemSetting::setValue('opening_balance', 100);
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'member']);
        $donation = Donation::create([
            'user_id' => $staff->id,
            'category' => 'Bantuan',
            'request_date' => now()->toDateString(),
            'limit_amount' => 80,
            'status' => 'treasurer_verified',
            'treasurer_verified_by' => $treasurer->id,
            'treasurer_verified_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('donations.approve', $donation), ['amount' => 81])->assertStatus(422);
        $this->assertSame('treasurer_verified', $donation->fresh()->status);
        $this->assertDatabaseCount('transactions', 0);

        $insufficientBalanceDonation = Donation::create([
            'user_id' => $staff->id,
            'category' => 'Program Bantuan',
            'request_date' => now()->toDateString(),
            'limit_amount' => 120,
            'status' => 'treasurer_verified',
            'treasurer_verified_by' => $treasurer->id,
            'treasurer_verified_at' => now(),
        ]);
        $this->actingAs($admin)->patch(route('donations.approve', $insufficientBalanceDonation), ['amount' => 110])->assertStatus(422);
        $this->assertSame('treasurer_verified', $insufficientBalanceDonation->fresh()->status);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_staff_treasurer_admin_expense_claim_flow_completes_end_to_end(): void
    {
        Storage::fake('private');
        Mail::fake();

        $staff = User::factory()->create(['role' => 'member', 'email_finance' => false]);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'email_finance' => false]);
        $admin = User::factory()->create(['role' => 'admin', 'email_finance' => false]);
        SystemSetting::setValue('opening_balance', 200);

        $this->actingAs($staff)->post(route('claims.store'), [
            'title' => 'Tuntutan ujian automatik',
            'description' => 'Ujian aliran tuntutan dalam pangkalan data sementara.',
            'amount' => 100,
            'category' => 'Khairat Kematian',
            'receipt' => UploadedFile::fake()->create('test-receipt.pdf', 64, 'application/pdf'),
        ])->assertRedirect(route('claims.index'));

        $claim = ExpenseClaim::query()->where('user_id', $staff->id)->sole();
        $this->assertSame('pending', $claim->status);
        Storage::disk('private')->assertExists($claim->receipt_path);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $treasurer->id,
            'title' => 'Tuntutan baharu menunggu semakan',
        ]);

        $this->actingAs($treasurer)->patch(route('claims.verify', $claim), [
            'treasurer_notes' => 'Dokumen ujian disemak.',
        ])->assertRedirect(route('claims.index'));
        $claim->refresh();
        $this->assertSame('treasurer_verified', $claim->status);
        $this->assertSame($treasurer->id, $claim->treasurer_verified_by);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'title' => 'Tuntutan menunggu kelulusan admin',
        ]);

        $this->actingAs($admin)->get(route('claims.show', $claim))
            ->assertOk()
            ->assertSee('Luluskan Tuntutan')
            ->assertSee('Tolak Tuntutan')
            ->assertDontSee('Sahkan sebagai Bendahari');

        $this->actingAs($admin)->patch(route('claims.approve', $claim), [
            'review_notes' => 'Diluluskan dalam ujian automatik.',
        ])->assertRedirect(route('claims.index'));

        $this->assertDatabaseHas('expense_claims', [
            'id' => $claim->id,
            'status' => 'approved',
            'treasurer_verified_by' => $treasurer->id,
            'reviewed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $claim->fresh()->transaction_id,
            'type' => 'expense',
            'amount' => 100,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $staff->id,
            'title' => 'Tuntutan diluluskan',
        ]);
        Mail::assertNothingOutgoing();
    }

    public function test_treasurer_claim_details_offer_both_verification_and_rejection_actions(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create();
        $claim = ExpenseClaim::create([
            'user_id' => $member->id,
            'title' => 'Tuntutan semakan bendahari',
            'amount' => 30,
            'category' => 'Aktiviti',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/test.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($treasurer)->get(route('claims.show', $claim))
            ->assertOk()
            ->assertSee('Sahkan sebagai Bendahari')
            ->assertSee('Tolak Tuntutan')
            ->assertSee('Sebab Ditolak');
    }

    public function test_treasurer_can_reject_pending_expense_claim_and_email_member(): void
    {
        Mail::fake();

        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['email' => 'claimant@example.test']);
        $claim = ExpenseClaim::create([
            'user_id' => $member->id,
            'title' => 'Khairat Kematian',
            'amount' => 100,
            'category' => 'Khairat Kematian',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/test.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($treasurer)->patch(route('claims.reject', $claim), [
            'review_notes' => 'Resit tidak lengkap.',
        ])->assertRedirect(route('claims.index'));

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
            'amount' => 'Jumlah tuntutan yang dipilih perlu RM 100.00.',
            'category' => 'Sila isi kategori tuntutan.',
            'receipt' => 'Sila upload dokumen tuntutan.',
        ]);
    }

    public function test_payment_and_claim_dates_cannot_be_in_the_future(): void
    {
        $user = User::factory()->create();
        $future = now()->addDay()->toDateString();

        $this->actingAs($user)->post(route('payments.store'), [
            'months' => 3,
            'amount' => 30,
            'payment_method' => 'Online Transfer',
            'payment_date' => $future,
        ])->assertRedirect('/payments');
        $payment = PaymentSubmission::latest('id')->firstOrFail();
        $this->assertSame(now()->toDateString(), $payment->payment_date->toDateString());

        $this->actingAs($user)->post(route('claims.store'), [
            'title' => 'Khairat Kematian',
            'amount' => 100,
            'category' => 'Khairat Kematian',
            'claim_date' => $future,
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('claims.index'));
        $claim = ExpenseClaim::latest('id')->firstOrFail();
        $this->assertSame(now()->toDateString(), $claim->claim_date->toDateString());
    }

    public function test_member_can_edit_pending_claim_and_replace_receipt(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $oldPath = UploadedFile::fake()->create('old-receipt.pdf', 120, 'application/pdf')->store('expense-claims', 'private');
        $claim = ExpenseClaim::create([
            'user_id' => $user->id,
            'title' => 'Khairat Kematian',
            'description' => 'Catatan lama',
            'amount' => 100,
            'category' => 'Khairat Kematian',
            'claim_date' => now()->toDateString(),
            'receipt_path' => $oldPath,
            'status' => 'pending',
        ]);

        $this->actingAs($user)->put(route('claims.update', $claim), [
            'title' => 'Sambutan Harijadi Staff',
            'description' => 'Catatan baharu',
            'amount' => 100,
            'category' => 'Sambutan Harijadi Staff',
            'claim_date' => now()->toDateString(),
            'receipt' => UploadedFile::fake()->create('new-receipt.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('claims.show', $claim));

        $claim = $claim->fresh();
        $this->assertSame('Sambutan Harijadi Staff', $claim->title);
        $this->assertNotSame($oldPath, $claim->receipt_path);
        Storage::disk('private')->assertMissing($oldPath);
        Storage::disk('private')->assertExists($claim->receipt_path);
    }

    public function test_rejected_claim_can_be_resubmitted_with_new_receipt(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $oldPath = UploadedFile::fake()->create('old-receipt.pdf', 120, 'application/pdf')->store('expense-claims', 'private');
        $claim = ExpenseClaim::create([
            'user_id' => $user->id,
            'title' => 'Hadiah Kejayaan Anak',
            'amount' => 100,
            'category' => 'Hadiah Kejayaan Anak',
            'claim_date' => now()->toDateString(),
            'receipt_path' => $oldPath,
            'status' => 'rejected',
            'review_notes' => 'Resit tidak jelas.',
        ]);

        $this->actingAs($user)->post(route('claims.resubmit', $claim), [
            'title' => 'Hadiah Kejayaan Anak',
            'description' => 'Resit baharu',
            'amount' => 100,
            'category' => 'Hadiah Kejayaan Anak',
            'claim_date' => now()->toDateString(),
            'receipt' => UploadedFile::fake()->create('new-receipt.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('claims.show', $claim));

        $claim = $claim->fresh();
        $this->assertSame('pending', $claim->status);
        $this->assertNull($claim->review_notes);
        Storage::disk('private')->assertMissing($oldPath);
        Storage::disk('private')->assertExists($claim->receipt_path);
    }

    public function test_member_can_delete_pending_claim_but_not_approved_claim(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $claim = ExpenseClaim::create([
            'user_id' => $user->id,
            'title' => 'Tuntutan pending',
            'amount' => 20,
            'category' => 'Makanan',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/pending.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->delete(route('claims.destroy', $claim))->assertRedirect('/claims');
        $this->assertDatabaseMissing('expense_claims', ['id' => $claim->id]);

        $approved = ExpenseClaim::create([
            'user_id' => $user->id,
            'title' => 'Tuntutan approved',
            'amount' => 20,
            'category' => 'Makanan',
            'claim_date' => now()->toDateString(),
            'receipt_path' => 'expense-claims/approved.pdf',
            'status' => 'approved',
        ]);

        $this->actingAs($user)->delete(route('claims.destroy', $approved))->assertStatus(422);
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
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($admin)->get(route('finance.fees.index'))
            ->assertOk()
            ->assertSee('Pengurusan Yuran')
            ->assertSee('Jana Bil');

        $this->actingAs($treasurer)->get(route('finance.fees.index'))->assertOk();
        $this->actingAs($member)->get(route('finance.fees.index'))->assertForbidden();

        $this->actingAs($treasurer)->post(route('finance.fees.generate'))->assertRedirect();
        $this->actingAs($treasurer)->post(route('finance.fees.reminders'))->assertRedirect();
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

    public function test_admin_can_send_portal_notification_email_to_selected_user(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['email' => 'member@example.test']);

        $this->actingAs($admin)->post(route('notifications.store'), [
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
            'title' => 'Penyelenggaraan sistem POLIBEST',
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
            'title' => 'POLIBEST kembali beroperasi',
        ]);
        Mail::assertQueued(PortalNotificationMail::class, fn (PortalNotificationMail $mail) => $mail->hasTo('restored-member@example.test'));

        $this->actingAs($member)->get(route('dashboard'))->assertOk();
    }

    public function test_admin_can_download_full_system_backup(): void
    {
        Storage::fake('public');
        Storage::fake('private');
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
        Storage::fake('private');
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
        Storage::fake('private');
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

    public function test_staff_cannot_access_financial_transactions(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $transaction = Transaction::create([
            'type' => 'income',
            'amount' => 20,
            'description' => 'Kutipan yuran',
            'receipt_number' => 'PB-ACCESS-001',
            'transaction_date' => now()->toDateString(),
            'category' => 'Yuran',
            'status' => 'active',
        ]);

        $this->actingAs($member)->get(route('transactions.index'))->assertForbidden();
        $this->actingAs($member)->get(route('transactions.show', $transaction))->assertForbidden();
        $this->actingAs($member)->get(route('transactions.create'))->assertForbidden();
        $this->actingAs($member)->get(route('transactions.edit', $transaction))->assertForbidden();
        $this->actingAs($member)->delete(route('transactions.destroy', $transaction))->assertForbidden();

        $this->assertSame('active', $transaction->fresh()->status);
    }

    public function test_staff_cannot_view_or_review_another_members_payment(): void
    {
        $staff = User::factory()->create(['role' => 'member']);
        $member = User::factory()->create();
        $payment = PaymentSubmission::create([
            'user_id' => $member->id,
            'amount' => 20,
            'payment_method' => 'Online Transfer',
            'payment_date' => now()->toDateString(),
            'proof_path' => 'payment-proofs/test.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($staff)->get(route('payments.show', $payment))->assertForbidden();
        $this->actingAs($staff)->patch(route('payments.approve', $payment))->assertForbidden();
        $this->actingAs($staff)->patch(route('payments.reject', $payment), ['review_notes' => 'Ditolak'])->assertForbidden();

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

        $this->actingAs($member)->get(route('payments.show', $payment))->assertForbidden();
        $this->actingAs($member)->get(route('payments.proof', $payment))->assertForbidden();
        $this->actingAs($member)->get(route('claims.show', $claim))->assertForbidden();
        $this->actingAs($member)->get(route('claims.receipt', $claim))->assertForbidden();
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
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $this->actingAs($member)->get(route('settings.edit'))->assertForbidden();
        $this->actingAs($admin)->get(route('backup.export'))->assertOk();
        $this->actingAs($treasurer)->get(route('admin.index'))->assertForbidden();
    }
}
