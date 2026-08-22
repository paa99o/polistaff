<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityRegistration;
use App\Models\ExpenseClaim;
use App\Models\SystemSetting;
use App\Models\PaymentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PoliBestFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_register_as_pending_member(): void
    {
        $this->post('/register', [
            'name' => 'Ali Staff',
            'ic_number' => '900101111111',
            'email' => 'ali@example.test',
            'department' => 'JTMK',
            'phone' => '0111111111',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', ['email' => 'ali@example.test', 'membership_status' => 'pending', 'role' => 'member']);
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

    public function test_member_can_register_for_activity_with_capacity(): void
    {
        $user = User::factory()->create();
        $activity = Activity::create(['title' => 'Program Komuniti', 'date_time' => now()->addDay(), 'location' => 'Dewan', 'max_participants' => 1, 'status' => 'approved', 'qr_code_token' => Str::uuid()->toString()]);

        $this->actingAs($user)->post(route('activities.register', $activity))->assertRedirect();

        $this->assertDatabaseHas('activity_registrations', ['user_id' => $user->id, 'activity_id' => $activity->id, 'status' => 'registered']);
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

    public function test_treasurer_can_approve_payment_and_generate_transaction(): void
    {
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
        ])->assertRedirect(route('payments.show', $payment));

        $this->assertDatabaseHas('payment_submissions', ['id' => $payment->id, 'status' => 'approved', 'reviewed_by' => $treasurer->id]);
        $this->assertDatabaseHas('transactions', ['user_id' => $member->id, 'amount' => 20, 'category' => 'Yuran']);
        $this->assertSame('30.00', $member->fresh()->fee_balance);
    }

    public function test_chairman_can_approve_expense_claim_and_generate_expense(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $chairman = User::factory()->create(['role' => 'chairman']);
        $member = User::factory()->create();
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
    }

    public function test_admin_can_generate_monthly_fees(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['membership_status' => 'active', 'fee_balance' => 0]);
        SystemSetting::setValue('monthly_fee', 15);

        $this->actingAs($admin)->post(route('settings.monthly-fees'))->assertRedirect();
        $this->actingAs($admin)->post(route('settings.monthly-fees'))->assertRedirect();

        $this->assertSame('15.00', $member->fresh()->fee_balance);
        $this->assertDatabaseCount('member_fee_bills', 1);
    }
}
