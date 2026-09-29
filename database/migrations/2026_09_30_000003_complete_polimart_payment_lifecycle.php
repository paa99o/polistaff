<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polimart_orders', function (Blueprint $table): void {
            $table->text('payment_review_note')->nullable()->after('payment_reference');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('polimart_orders', function (Blueprint $table): void {
            $table->dropColumn(['payment_review_note', 'payment_expires_at']);
        });
    }
};
