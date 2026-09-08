<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('theme_preference', 'system')
            ->update(['theme_preference' => 'light']);
    }

    public function down(): void
    {
        // Existing light preferences cannot be distinguished from converted values.
    }
};
