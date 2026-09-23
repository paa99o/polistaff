<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropColumn(['attendance_opens_at', 'attendance_closes_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dateTime('attendance_opens_at')->nullable();
            $table->dateTime('attendance_closes_at')->nullable();
        });
    }
};
