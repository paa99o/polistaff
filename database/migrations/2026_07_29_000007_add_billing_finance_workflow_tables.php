<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_fee_bills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('billing_month');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->timestamps();
            $table->unique(['user_id', 'billing_month']);
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->string('status')->default('active')->after('payment_method');
            $table->foreignId('reversal_of_id')->nullable()->after('status')->constrained('transactions')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->after('reversal_of_id')->constrained('users')->nullOnDelete();
            $table->dateTime('reversed_at')->nullable()->after('reversed_by');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
        });

        Schema::table('payment_submissions', function (Blueprint $table): void {
            $table->decimal('allocated_amount', 10, 2)->default(0)->after('amount');
        });

        Schema::table('expense_claims', function (Blueprint $table): void {
            $table->foreignId('treasurer_verified_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            $table->dateTime('treasurer_verified_at')->nullable()->after('treasurer_verified_by');
            $table->text('treasurer_notes')->nullable()->after('treasurer_verified_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE expense_claims MODIFY status ENUM('pending','treasurer_verified','approved','rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('expense_claims', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('treasurer_verified_by');
            $table->dropColumn(['treasurer_verified_at', 'treasurer_notes']);
        });

        Schema::table('payment_submissions', function (Blueprint $table): void {
            $table->dropColumn('allocated_amount');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reversal_of_id');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['status', 'reversed_at', 'reversal_reason']);
        });

        Schema::dropIfExists('member_fee_bills');
    }
};
