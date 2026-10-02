<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.application_documents', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->foreignId('submission_id')->constrained('recruitment.application_submissions')->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained('recruitment.document_types')->restrictOnDelete();
            $table->foreignId('stored_file_id')->constrained('recruitment.stored_files')->restrictOnDelete();
            $table->bigInteger('source_group_document_id')->nullable();
            $table->unique(['submission_id', 'document_type_id'], 'app_documents_sub_type_unique');
        });

        DB::statement("ALTER TABLE recruitment.application_documents ADD CONSTRAINT app_documents_source_fk FOREIGN KEY (source_group_document_id, stored_file_id, document_type_id) REFERENCES recruitment.group_documents(id, stored_file_id, document_type_id) ON DELETE RESTRICT");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.application_documents');
    }
};
