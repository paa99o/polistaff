<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('theme_preference', 20)->default('light')->after('profile_photo_path');
            $table->string('text_size_preference', 20)->default('normal')->after('theme_preference');
            $table->boolean('reduce_motion')->default(false)->after('text_size_preference');
            $table->boolean('email_announcements')->default(true)->after('reduce_motion');
            $table->boolean('email_activities')->default(true)->after('email_announcements');
            $table->boolean('email_finance')->default(true)->after('email_activities');
            $table->boolean('email_fee_reminders')->default(true)->after('email_finance');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'theme_preference',
                'text_size_preference',
                'reduce_motion',
                'email_announcements',
                'email_activities',
                'email_finance',
                'email_fee_reminders',
            ]);
        });
    }
};
