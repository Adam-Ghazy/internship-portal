<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Index biasa sesuai schema.sql (yang bukan partial/unik yang sudah dibuat di migration tabel)
        DB::statement("CREATE INDEX application_inbox ON recruitment.applications(vacancy_id, stage)");
        DB::statement("CREATE INDEX application_history ON recruitment.applications(applicant_profile_id, submitted_at)");
        DB::statement("CREATE INDEX reviewer_inbox ON recruitment.application_reviews(assignee_id, status, stage)");
        DB::statement("CREATE INDEX event_history ON recruitment.application_events(application_id, occurred_at)");
        DB::statement("CREATE INDEX outbox_due ON recruitment.outbox_events(available_at) WHERE processed_at IS NULL");
        DB::statement("CREATE INDEX pending_deliveries ON recruitment.notification_deliveries(status) WHERE status IN ('pending','failed')");
        DB::statement("CREATE INDEX draft_file_refs ON recruitment.application_draft_documents(stored_file_id)");
        DB::statement("CREATE INDEX submission_file_refs ON recruitment.application_documents(stored_file_id)");
        DB::statement("CREATE INDEX group_file_refs ON recruitment.group_documents(stored_file_id)");
        DB::statement("CREATE INDEX vacancy_request_idx ON recruitment.vacancies(request_id)");
        DB::statement("CREATE INDEX member_user_idx ON recruitment.application_group_members(claimed_user_id)");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS recruitment.application_inbox");
        DB::statement("DROP INDEX IF EXISTS recruitment.application_history");
        DB::statement("DROP INDEX IF EXISTS recruitment.reviewer_inbox");
        DB::statement("DROP INDEX IF EXISTS recruitment.event_history");
        DB::statement("DROP INDEX IF EXISTS recruitment.outbox_due");
        DB::statement("DROP INDEX IF EXISTS recruitment.pending_deliveries");
        DB::statement("DROP INDEX IF EXISTS recruitment.draft_file_refs");
        DB::statement("DROP INDEX IF EXISTS recruitment.submission_file_refs");
        DB::statement("DROP INDEX IF EXISTS recruitment.group_file_refs");
        DB::statement("DROP INDEX IF EXISTS recruitment.vacancy_request_idx");
        DB::statement("DROP INDEX IF EXISTS recruitment.member_user_idx");
    }
};
