<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polimart_reports', function (Blueprint $table): void {
            $table->dropForeign(['polimart_item_id']);
            $table->unsignedBigInteger('polimart_item_id')->nullable()->change();
            $table->foreign('polimart_item_id')->references('id')->on('polimart_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('polimart_reports', function (Blueprint $table): void {
            $table->dropForeign(['polimart_item_id']);
            $table->unsignedBigInteger('polimart_item_id')->nullable(false)->change();
            $table->foreign('polimart_item_id')->references('id')->on('polimart_items')->cascadeOnDelete();
        });
    }
};
