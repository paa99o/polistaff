<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('activities', 'evidence_photo_path')) {
                $table->string('evidence_photo_path')->nullable()->after('qr_code_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            if (Schema::hasColumn('activities', 'evidence_photo_path')) {
                $table->dropColumn('evidence_photo_path');
            }
        });
    }
};
