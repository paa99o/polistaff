<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('treasurer_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->text('description')->nullable();
            $table->date('request_date');
            $table->decimal('limit_amount', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->enum('status', ['pending', 'treasurer_verified', 'approved', 'rejected'])->default('pending');
            $table->text('treasurer_notes')->nullable();
            $table->dateTime('treasurer_verified_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('donations'); }
};
