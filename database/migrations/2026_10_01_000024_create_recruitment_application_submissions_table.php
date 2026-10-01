<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.application_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('recruitment.applications')->restrictOnDelete();
            $table->integer('version_no');
            $table->jsonb('profile_snapshot');
            $table->jsonb('education_snapshot');
            $table->jsonb('vacancy_snapshot');
            $table->bigInteger('submitted_by');
            $table->timestampTz('submitted_at')->useCurrent();
            $table->unique(['application_id', 'version_no'], 'submissions_app_version_unique');
            $table->unique(['application_id', 'id'], 'submissions_app_id_unique');
        });

        DB::statement("ALTER TABLE recruitment.application_submissions ADD CONSTRAINT submissions_version_check CHECK (version_no > 0)");
        DB::statement("ALTER TABLE recruitment.application_submissions ADD CONSTRAINT submissions_profile_check CHECK (jsonb_typeof(profile_snapshot) = 'object')");
        DB::statement("ALTER TABLE recruitment.application_submissions ADD CONSTRAINT submissions_education_check CHECK (jsonb_typeof(education_snapshot) = 'array')");
        DB::statement("ALTER TABLE recruitment.application_submissions ADD CONSTRAINT submissions_vacancy_check CHECK (jsonb_typeof(vacancy_snapshot) = 'object')");
        DB::statement("ALTER TABLE recruitment.application_submissions ADD CONSTRAINT submissions_submitter_fk FOREIGN KEY (application_id, submitted_by) REFERENCES recruitment.applications(id, applicant_user_id) ON DELETE RESTRICT");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.application_submissions');
    }
};
