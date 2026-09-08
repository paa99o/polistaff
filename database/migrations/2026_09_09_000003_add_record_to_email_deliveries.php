<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table): void {
            $table->string('record_type')->nullable()->after('notification_id');
            $table->unsignedBigInteger('record_id')->nullable()->after('record_type');
            $table->index(['record_type', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table): void {
            $table->dropIndex('email_deliveries_record_type_record_id_index');
            $table->dropColumn(['record_type', 'record_id']);
        });
    }
};
