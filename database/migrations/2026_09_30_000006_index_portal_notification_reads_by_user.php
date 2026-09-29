<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_notification_reads', function (Blueprint $table): void {
            $table->index(['user_id', 'notification_id']);
        });
    }

    public function down(): void
    {
        Schema::table('portal_notification_reads', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'notification_id']);
        });
    }
};
