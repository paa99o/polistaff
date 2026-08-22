<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\ExpenseClaim;
use App\Models\MemberDocument;
use App\Models\PaymentSubmission;
use App\Models\PortalNotification;
use App\Models\Transaction;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Policies\ExpenseClaimPolicy;
use App\Policies\MemberDocumentPolicy;
use App\Policies\PaymentSubmissionPolicy;
use App\Policies\PortalNotificationPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(PaymentSubmission::class, PaymentSubmissionPolicy::class);
        Gate::policy(ExpenseClaim::class, ExpenseClaimPolicy::class);
        Gate::policy(MemberDocument::class, MemberDocumentPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(PortalNotification::class, PortalNotificationPolicy::class);

        Gate::define('view-financial-reports', fn (User $user) => $user->hasRole('treasurer', 'chairman', 'admin'));
        Gate::define('manage-activities', fn (User $user) => $user->hasRole('admin'));
        Gate::define('approve-expenses', fn (User $user) => $user->hasRole('chairman', 'admin'));
        Gate::define('manage-members', fn (User $user) => $user->hasRole('admin'));
    }
}
