<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payment_submissions MODIFY status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('payment_submissions', function (Blueprint $table): void {
                $table->string('status_new')->default('pending');
            });

            DB::statement('UPDATE payment_submissions SET status_new = status');

            Schema::table('payment_submissions', function (Blueprint $table): void {
                $table->dropColumn('status');
            });

            Schema::table('payment_submissions', function (Blueprint $table): void {
                $table->renameColumn('status_new', 'status');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payment_submissions MODIFY status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
