<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.group_documents', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->foreignId('group_id')->constrained('recruitment.application_groups')->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained('recruitment.document_types')->restrictOnDelete();
            $table->integer('version_no');
            $table->foreignId('stored_file_id')->constrained('recruitment.stored_files')->restrictOnDelete();
            $table->unique(['group_id', 'document_type_id', 'version_no'], 'group_documents_version_unique');
            $table->unique(['id', 'stored_file_id', 'document_type_id'], 'group_documents_id_file_type_unique');
        });

        DB::statement("ALTER TABLE recruitment.group_documents ADD CONSTRAINT group_documents_version_check CHECK (version_no > 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.group_documents');
    }
};
