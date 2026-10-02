<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.applications', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->string('public_reference', 50)->unique();
            $table->bigInteger('applicant_profile_id');
            $table->bigInteger('applicant_user_id');
            $table->bigInteger('vacancy_id');
            $table->bigInteger('period_id');
            $table->bigInteger('group_member_id')->nullable()->unique();
            $table->string('stage', 32)->default('draft');
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->bigInteger('current_submission_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->unique(['applicant_profile_id', 'vacancy_id'], 'applications_profile_vacancy_unique');
            $table->unique(['id', 'vacancy_id'], 'applications_id_vacancy_unique');
            $table->unique(['id', 'applicant_user_id'], 'applications_id_user_unique');
        });

        DB::statement("ALTER TABLE recruitment.applications ADD CONSTRAINT applications_stage_check CHECK (stage IN ('draft','submitted','administrative_review','needs_revision','manager_review','sm_review','decided','withdrawn'))");
        DB::statement("ALTER TABLE recruitment.applications ADD CONSTRAINT applications_draft_check CHECK ((stage='draft' AND submitted_at IS NULL AND current_submission_id IS NULL) OR (stage<>'draft' AND submitted_at IS NOT NULL AND current_submission_id IS NOT NULL))");
        DB::statement("ALTER TABLE recruitment.applications ADD CONSTRAINT applications_withdrawn_check CHECK ((stage='withdrawn') = (withdrawn_at IS NOT NULL))");
        DB::statement("ALTER TABLE recruitment.applications ADD CONSTRAINT applications_profile_fk FOREIGN KEY (applicant_profile_id, applicant_user_id) REFERENCES recruitment.applicant_profiles(id, user_id) ON DELETE RESTRICT");
        DB::statement("ALTER TABLE recruitment.applications ADD CONSTRAINT applications_vacancy_fk FOREIGN KEY (vacancy_id, period_id) REFERENCES recruitment.vacancies(id, period_id) ON DELETE RESTRICT");
        DB::statement("ALTER TABLE recruitment.applications ADD CONSTRAINT applications_group_member_fk FOREIGN KEY (group_member_id, applicant_user_id, period_id) REFERENCES recruitment.application_group_members(id, claimed_user_id, period_id) ON DELETE RESTRICT");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.applications');
    }
};
