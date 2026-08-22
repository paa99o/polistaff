<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dateTime('registration_opens_at')->nullable()->after('max_participants');
            $table->dateTime('registration_closes_at')->nullable()->after('registration_opens_at');
            $table->dateTime('attendance_opens_at')->nullable()->after('registration_closes_at');
            $table->dateTime('attendance_closes_at')->nullable()->after('attendance_opens_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE activity_registrations MODIFY status ENUM('registered','waitlisted','cancelled') NOT NULL DEFAULT 'registered'");
        }
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropColumn(['registration_opens_at', 'registration_closes_at', 'attendance_opens_at', 'attendance_closes_at']);
        });
    }
};
