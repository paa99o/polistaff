<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polimart_items', function (Blueprint $table): void {
            // Existing listings remain available until the admin updates their quantity.
            $table->unsignedInteger('stock')->default(1)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('polimart_items', function (Blueprint $table): void {
            $table->dropColumn('stock');
        });
    }
};
