<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('email_recipient')->nullable()->after('link');
            $table->string('email_status')->default('not_sent')->after('email_recipient');
            $table->unsignedInteger('email_attempts')->default(0)->after('email_status');
            $table->dateTime('email_sent_at')->nullable()->after('email_attempts');
            $table->text('email_error')->nullable()->after('email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropColumn(['email_recipient', 'email_status', 'email_attempts', 'email_sent_at', 'email_error']);
        });
    }
};
