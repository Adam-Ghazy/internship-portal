<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.application_reviews', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('application_id');
            $table->bigInteger('vacancy_id');
            $table->bigInteger('org_unit_id');
            $table->string('stage', 16);
            $table->bigInteger('assignee_id');
            $table->bigInteger('staff_assignment_id');
            $table->integer('revision_no')->default(1);
            $table->string('status', 16);
            $table->string('outcome', 24)->nullable();
            $table->text('note_internal')->nullable();
            $table->bigInteger('based_on_submission_id');
            $table->foreignId('assigned_by')->constrained('recruitment.users')->restrictOnDelete();
            $table->timestampTz('acted_at')->nullable();
            $table->bigInteger('supersedes_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['application_id', 'stage', 'revision_no'], 'reviews_app_stage_rev_unique');
            $table->unique(['application_id', 'stage', 'id'], 'reviews_app_stage_id_unique');
            $table->unique(['application_id', 'id', 'stage', 'status'], 'reviews_app_id_stage_status_unique');
        });

        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT reviews_stage_check CHECK (stage IN ('manager','sm'))");
        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT reviews_revision_check CHECK (revision_no > 0)");
        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT reviews_status_check CHECK (status IN ('blocked','pending','completed','superseded','cancelled'))");
        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT reviews_completed_check CHECK ((status='completed' AND outcome IS NOT NULL AND acted_at IS NOT NULL AND note_internal IS NOT NULL AND length(btrim(note_internal))>0) OR (status<>'completed' AND outcome IS NULL AND acted_at IS NULL))");
        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT reviews_outcome_check CHECK (outcome IS NULL OR (stage='manager' AND outcome IN ('recommended','not_recommended')) OR (stage='sm' AND outcome IN ('accepted','rejected')))");
        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT reviews_supersedes_check CHECK (supersedes_id IS NULL OR supersedes_id <> id)");
        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT reviews_app_vacancy_fk FOREIGN KEY (application_id, vacancy_id) REFERENCES recruitment.applications(id, vacancy_id) ON DELETE RESTRICT");
        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT reviews_vacancy_unit_fk FOREIGN KEY (vacancy_id, org_unit_id) REFERENCES recruitment.vacancies(id, org_unit_id) ON DELETE RESTRICT");
        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT reviews_staff_fk FOREIGN KEY (staff_assignment_id, assignee_id, stage, org_unit_id) REFERENCES recruitment.staff_assignments(id, user_id, role_code, org_unit_id) ON DELETE RESTRICT");
        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT reviews_submission_fk FOREIGN KEY (application_id, based_on_submission_id) REFERENCES recruitment.application_submissions(application_id, id) ON DELETE RESTRICT");
        DB::statement("CREATE UNIQUE INDEX one_effective_review ON recruitment.application_reviews(application_id, stage) WHERE status IN ('blocked','pending','completed')");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.application_reviews');
    }
};
