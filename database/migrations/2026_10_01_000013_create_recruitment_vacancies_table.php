<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.vacancies', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->bigInteger('request_id');
            $table->bigInteger('org_unit_id');
            $table->bigInteger('position_id');
            $table->bigInteger('period_id');
            $table->foreignId('program_id')->constrained('recruitment.internship_programs')->restrictOnDelete();
            $table->string('slug', 200)->unique();
            $table->integer('quota');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->text('description')->default('');
            $table->string('status', 16)->default('draft');
            $table->timestampTz('published_at')->nullable();
            $table->unique(['id', 'period_id'], 'vacancies_id_period_unique');
            $table->unique(['id', 'org_unit_id'], 'vacancies_id_unit_unique');
        });

        DB::statement("ALTER TABLE recruitment.vacancies ADD CONSTRAINT vacancies_quota_check CHECK (quota > 0)");
        DB::statement("ALTER TABLE recruitment.vacancies ADD CONSTRAINT vacancies_dates_check CHECK (ends_on >= starts_on)");
        DB::statement("ALTER TABLE recruitment.vacancies ADD CONSTRAINT vacancies_status_check CHECK (status IN ('draft','published','closed','archived'))");
        DB::statement("ALTER TABLE recruitment.vacancies ADD CONSTRAINT vacancies_published_check CHECK ((status='draft' AND published_at IS NULL) OR (status<>'draft' AND published_at IS NOT NULL))");
        DB::statement("ALTER TABLE recruitment.vacancies ADD CONSTRAINT vacancies_request_fk FOREIGN KEY (request_id, org_unit_id, position_id, period_id) REFERENCES recruitment.internship_requests(id, org_unit_id, position_id, period_id) ON DELETE RESTRICT");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.vacancies');
    }
};
