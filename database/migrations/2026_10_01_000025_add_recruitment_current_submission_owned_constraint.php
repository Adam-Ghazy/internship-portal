<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE recruitment.applications ADD CONSTRAINT current_submission_owned FOREIGN KEY (id, current_submission_id) REFERENCES recruitment.application_submissions(application_id, id) ON DELETE RESTRICT");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE recruitment.applications DROP CONSTRAINT IF EXISTS current_submission_owned");
    }
};
