<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table): void {
            $table->string('proof_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table): void {
            $table->string('proof_path')->nullable(false)->change();
        });
    }
};
