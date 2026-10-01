<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE recruitment.application_reviews ADD CONSTRAINT supersedes_same_stage FOREIGN KEY (application_id, stage, supersedes_id) REFERENCES recruitment.application_reviews(application_id, stage, id) ON DELETE RESTRICT");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE recruitment.application_reviews DROP CONSTRAINT IF EXISTS supersedes_same_stage");
    }
};
