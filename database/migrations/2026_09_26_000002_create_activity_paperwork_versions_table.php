<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_paperwork_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version');
            $table->string('template_version', 30)->default('1.0');
            $table->string('status', 20)->default('draft');
            $table->json('content');
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->unique(['activity_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_paperwork_versions');
    }
};
