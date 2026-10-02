<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.application_publications', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->foreignId('application_id')->unique()->constrained('recruitment.applications')->restrictOnDelete();
            $table->bigInteger('final_review_id')->unique();
            $table->string('final_stage', 16)->default('sm');
            $table->string('final_status', 16)->default('completed');
            $table->foreignId('published_by')->constrained('recruitment.users')->restrictOnDelete();
            $table->timestampTz('published_at')->useCurrent();
            $table->text('public_message')->default('');
        });

        DB::statement("ALTER TABLE recruitment.application_publications ADD CONSTRAINT publications_stage_check CHECK (final_stage = 'sm')");
        DB::statement("ALTER TABLE recruitment.application_publications ADD CONSTRAINT publications_status_check CHECK (final_status = 'completed')");
        DB::statement("ALTER TABLE recruitment.application_publications ADD CONSTRAINT publications_review_fk FOREIGN KEY (application_id, final_review_id, final_stage, final_status) REFERENCES recruitment.application_reviews(application_id, id, stage, status) ON DELETE RESTRICT");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.application_publications');
    }
};
