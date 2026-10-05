<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\ExpenseClaim;
use App\Models\MemberFeeBill;
use App\Models\User;
use App\Policies\ExpenseClaimPolicy;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PoliStaffBusinessRulesTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_user_role_check_matches_only_the_assigned_role(): void
    {
        $staff = new User(['role' => 'member']);

        $this->assertTrue($staff->hasRole('member'));
        $this->assertTrue($staff->hasRole('treasurer', 'member'));
        $this->assertFalse($staff->hasRole('admin'));
    }

    public function test_email_preference_uses_the_matching_category_setting(): void
    {
        $user = new User(['email_activities' => false, 'email_finance' => true]);

        $this->assertFalse($user->wantsEmail('activities'));
        $this->assertTrue($user->wantsEmail('finance'));
        $this->assertTrue($user->wantsEmail('unknown-category'));
    }

    public function test_approved_activity_registration_window_is_open_at_inclusive_boundaries(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 09:00:00'));
        $activity = new Activity([
            'status' => 'approved',
            'registration_opens_at' => '2026-10-06 09:00:00',
            'registration_closes_at' => '2026-10-06 10:00:00',
        ]);

        $this->assertTrue($activity->registrationIsOpen());

        Carbon::setTestNow(Carbon::parse('2026-10-06 10:00:00'));
        $this->assertTrue($activity->registrationIsOpen());
    }

    public function test_registration_is_closed_outside_its_window_or_before_approval(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 08:59:59'));
        $activity = new Activity([
            'status' => 'approved',
            'registration_opens_at' => '2026-10-06 09:00:00',
            'registration_closes_at' => '2026-10-06 10:00:00',
        ]);
        $this->assertFalse($activity->registrationIsOpen());

        Carbon::setTestNow(Carbon::parse('2026-10-06 09:30:00'));
        $activity->status = 'pending_approval';
        $this->assertFalse($activity->registrationIsOpen());
    }

    public function test_attendance_requires_approved_activity_token_and_active_time_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 09:30:00'));
        $activity = new Activity([
            'status' => 'approved',
            'date_time' => '2026-10-06 09:00:00',
            'end_time' => '2026-10-06 10:00:00',
            'qr_code_token' => 'test-token',
        ]);
        $this->assertTrue($activity->attendanceIsOpen());

        $activity->qr_code_token = null;
        $this->assertFalse($activity->attendanceIsOpen());

        $activity->qr_code_token = 'test-token';
        $activity->status = 'pending_approval';
        $this->assertFalse($activity->attendanceIsOpen());

        $activity->status = 'approved';
        Carbon::setTestNow(Carbon::parse('2026-10-06 10:00:01'));
        $this->assertFalse($activity->attendanceIsOpen());
    }

    public function test_activity_finished_state_uses_end_time_or_start_time_fallback(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 10:00:00'));
        $withEndTime = new Activity([
            'date_time' => '2026-10-06 09:00:00',
            'end_time' => '2026-10-06 10:00:00',
        ]);
        $withoutEndTime = new Activity(['date_time' => '2026-10-06 09:00:00']);

        $this->assertFalse($withEndTime->isFinished());
        $this->assertTrue($withoutEndTime->isFinished());
    }

    public function test_claim_policy_enforces_treasurer_then_admin_approval(): void
    {
        $policy = new ExpenseClaimPolicy;
        $treasurer = new User(['role' => 'treasurer']);
        $admin = new User(['role' => 'admin']);
        $pending = new ExpenseClaim(['status' => 'pending']);
        $verified = new ExpenseClaim(['status' => 'treasurer_verified']);

        $this->assertTrue($policy->verify($treasurer, $pending));
        $this->assertFalse($policy->verify($admin, $pending));
        $this->assertFalse($policy->approve($admin, $pending));
        $this->assertTrue($policy->approve($admin, $verified));
        $this->assertFalse($policy->approve($treasurer, $verified));
    }

    public function test_claim_rejection_is_limited_to_the_current_review_stage(): void
    {
        $policy = new ExpenseClaimPolicy;
        $treasurer = new User(['role' => 'treasurer']);
        $admin = new User(['role' => 'admin']);

        $this->assertTrue($policy->reject($treasurer, new ExpenseClaim(['status' => 'pending'])));
        $this->assertFalse($policy->reject($admin, new ExpenseClaim(['status' => 'pending'])));
        $this->assertTrue($policy->reject($admin, new ExpenseClaim(['status' => 'treasurer_verified'])));
        $this->assertFalse($policy->reject($treasurer, new ExpenseClaim(['status' => 'treasurer_verified'])));
    }

    public function test_fee_bill_remaining_amount_never_becomes_negative(): void
    {
        $unpaid = new MemberFeeBill(['amount' => 20, 'paid_amount' => 5]);
        $paid = new MemberFeeBill(['amount' => 20, 'paid_amount' => 20]);
        $overpaid = new MemberFeeBill(['amount' => 20, 'paid_amount' => 25]);

        $this->assertSame(15.0, $unpaid->remainingAmount());
        $this->assertSame(0.0, $paid->remainingAmount());
        $this->assertSame(0.0, $overpaid->remainingAmount());
    }
}
