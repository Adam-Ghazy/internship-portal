<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS accepted_reviews_by_vacancy ON recruitment.application_reviews (vacancy_id) WHERE stage = 'sm' AND status = 'completed' AND outcome = 'accepted'");
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS applicant_recent_applications ON recruitment.applications (applicant_profile_id, updated_at DESC, id DESC)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS published_vacancies ON recruitment.vacancies (status, published_at DESC, id DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS recruitment.accepted_reviews_by_vacancy');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS recruitment.applicant_recent_applications');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS recruitment.published_vacancies');
    }
};
