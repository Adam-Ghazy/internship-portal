<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.application_groups', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->foreignId('institution_id')->constrained('recruitment.institutions')->restrictOnDelete();
            $table->foreignId('period_id')->constrained('recruitment.recruitment_periods')->restrictOnDelete();
            $table->foreignId('coordinator_user_id')->constrained('recruitment.users')->restrictOnDelete();
            $table->string('title', 200);
            $table->string('status', 16)->default('active');
            $table->unique(['id', 'period_id'], 'application_groups_id_period_unique');
        });

        DB::statement("ALTER TABLE recruitment.application_groups ADD CONSTRAINT application_groups_status_check CHECK (status IN ('active','closed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.application_groups');
    }
};
