<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'ic_number')) {
                $table->string('ic_number')->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['member', 'treasurer', 'chairman', 'admin'])->default('member')->after('password');
            }
            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('department');
            }
            if (! Schema::hasColumn('users', 'membership_status')) {
                $table->enum('membership_status', ['pending', 'active', 'inactive'])->default('pending')->after('phone');
            }
            if (! Schema::hasColumn('users', 'joined_date')) {
                $table->date('joined_date')->nullable()->after('membership_status');
            }
            if (! Schema::hasColumn('users', 'fee_balance')) {
                $table->decimal('fee_balance', 10, 2)->default(0)->after('joined_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['ic_number', 'role', 'department', 'phone', 'membership_status', 'joined_date', 'fee_balance'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
