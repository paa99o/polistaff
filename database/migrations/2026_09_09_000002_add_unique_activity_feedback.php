<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table): void {
            $table->unique(['user_id', 'activity_id'], 'feedbacks_user_activity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table): void {
            $table->dropUnique('feedbacks_user_activity_unique');
        });
    }
};
