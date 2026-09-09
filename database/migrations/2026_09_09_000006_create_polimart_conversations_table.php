<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polimart_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('polimart_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->unique(['polimart_item_id', 'buyer_id']);
        });

        Schema::create('polimart_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('polimart_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['polimart_conversation_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polimart_messages');
        Schema::dropIfExists('polimart_conversations');
    }
};
