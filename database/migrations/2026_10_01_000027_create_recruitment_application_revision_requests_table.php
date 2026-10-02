<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.application_revision_requests', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->foreignId('application_id')->constrained('recruitment.applications')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('recruitment.users')->restrictOnDelete();
            $table->jsonb('fields_requested');
            $table->text('reason');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('due_at');
            $table->string('status', 16)->default('open');
            $table->bigInteger('completed_submission_id')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
        });

        DB::statement("ALTER TABLE recruitment.application_revision_requests ADD CONSTRAINT revision_fields_check CHECK (jsonb_typeof(fields_requested) = 'array')");
        DB::statement("ALTER TABLE recruitment.application_revision_requests ADD CONSTRAINT revision_reason_check CHECK (length(btrim(reason)) > 0)");
        DB::statement("ALTER TABLE recruitment.application_revision_requests ADD CONSTRAINT revision_due_check CHECK (due_at > created_at)");
        DB::statement("ALTER TABLE recruitment.application_revision_requests ADD CONSTRAINT revision_status_check CHECK (status IN ('open','fulfilled','cancelled'))");
        DB::statement("ALTER TABLE recruitment.application_revision_requests ADD CONSTRAINT revision_state_check CHECK ((status='fulfilled' AND completed_submission_id IS NOT NULL AND completed_at IS NOT NULL AND cancelled_at IS NULL) OR (status='open' AND completed_submission_id IS NULL AND completed_at IS NULL AND cancelled_at IS NULL) OR (status='cancelled' AND completed_submission_id IS NULL AND completed_at IS NULL AND cancelled_at IS NOT NULL))");
        DB::statement("ALTER TABLE recruitment.application_revision_requests ADD CONSTRAINT revision_completed_fk FOREIGN KEY (application_id, completed_submission_id) REFERENCES recruitment.application_submissions(application_id, id) ON DELETE RESTRICT");
        DB::statement("CREATE UNIQUE INDEX one_open_revision ON recruitment.application_revision_requests(application_id) WHERE status = 'open'");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.application_revision_requests');
    }
};
