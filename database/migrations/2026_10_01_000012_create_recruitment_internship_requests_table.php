<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.internship_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('recruitment.users')->restrictOnDelete();
            $table->foreignId('org_unit_id')->constrained('recruitment.org_units')->restrictOnDelete();
            $table->foreignId('position_id')->constrained('recruitment.internship_positions')->restrictOnDelete();
            $table->foreignId('period_id')->constrained('recruitment.recruitment_periods')->restrictOnDelete();
            $table->integer('requested_count');
            $table->text('criteria')->default('');
            $table->text('duties')->default('');
            $table->string('status', 24)->default('draft');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['id', 'org_unit_id', 'position_id', 'period_id'], 'internship_requests_composite_unique');
        });

        DB::statement("ALTER TABLE recruitment.internship_requests ADD CONSTRAINT internship_requests_count_check CHECK (requested_count > 0)");
        DB::statement("ALTER TABLE recruitment.internship_requests ADD CONSTRAINT internship_requests_status_check CHECK (status IN ('draft','submitted','processing','fulfilled','cancelled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.internship_requests');
    }
};
