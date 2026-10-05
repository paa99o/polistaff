<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polimart_orders', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('order_number')->constrained()->nullOnDelete();
            $table->index(['status', 'payment_status', 'payment_expires_at'], 'polimart_orders_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::table('polimart_orders', function (Blueprint $table): void {
            $table->dropIndex('polimart_orders_expiry_idx');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
