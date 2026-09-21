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
            $table->foreignId('treasurer_verified_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            $table->dateTime('treasurer_verified_at')->nullable()->after('treasurer_verified_by');
            $table->text('treasurer_notes')->nullable()->after('treasurer_verified_at');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE activities MODIFY status ENUM('draft','pending_approval','treasurer_verified','approved','rejected','cancelled') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE activities MODIFY status ENUM('draft','pending_approval','approved','rejected','cancelled') NOT NULL DEFAULT 'draft'");
        }
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('treasurer_verified_by');
            $table->dropColumn(['treasurer_verified_at', 'treasurer_notes']);
        });
    }
};
