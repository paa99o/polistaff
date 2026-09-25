<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->string('activity_type', 80)->nullable()->after('title');
            $table->string('program_category', 150)->nullable()->after('activity_type');
            $table->string('organizing_unit', 150)->nullable()->after('program_category');
            $table->string('person_in_charge', 150)->nullable()->after('organizing_unit');
            $table->unsignedInteger('expected_participants')->nullable()->after('max_participants');
            $table->text('participant_criteria')->nullable()->after('expected_participants');
            $table->string('implementation_mode', 30)->nullable()->after('participant_criteria');
            $table->json('proposal_data')->nullable()->after('implementation_mode');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropColumn([
                'activity_type',
                'program_category',
                'organizing_unit',
                'person_in_charge',
                'expected_participants',
                'participant_criteria',
                'implementation_mode',
                'proposal_data',
            ]);
        });
    }
};
