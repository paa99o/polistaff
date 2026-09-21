<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table): void {
            $table->json('bill_ids')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table): void {
            $table->dropColumn('bill_ids');
        });
    }
};
