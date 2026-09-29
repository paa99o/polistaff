<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polimart_seller_payment_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('qr_code_path')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->timestamps();
        });

        Schema::table('polimart_orders', function (Blueprint $table): void {
            $table->string('payment_method')->nullable()->after('status');
            $table->json('payment_instructions')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('polimart_orders', function (Blueprint $table): void {
            $table->dropColumn(['payment_method', 'payment_instructions']);
        });

        Schema::dropIfExists('polimart_seller_payment_profiles');
    }
};
