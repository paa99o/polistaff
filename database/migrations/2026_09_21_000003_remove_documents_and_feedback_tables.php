<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('feedbacks');
        Schema::dropIfExists('member_documents');
    }

    public function down(): void
    {
        // These retired modules are intentionally not recreated.
    }
};
