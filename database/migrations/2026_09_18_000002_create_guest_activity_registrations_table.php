<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_activity_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40);
            $table->enum('status', ['registered', 'waitlisted', 'cancelled'])->default('registered');
            $table->dateTime('registered_at');
            $table->timestamps();
            $table->unique(['activity_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_activity_registrations');
    }
};
