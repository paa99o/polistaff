<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->dateTime('end_time')->nullable()->after('date_time');
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE activities MODIFY status ENUM('draft','pending_approval','approved','rejected','cancelled') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['end_time', 'reviewed_at', 'review_notes']);
        });
    }
};
