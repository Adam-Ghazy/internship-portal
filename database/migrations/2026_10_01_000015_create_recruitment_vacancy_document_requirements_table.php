<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.vacancy_document_requirements', function (Blueprint $table) {
            $table->foreignId('vacancy_id')->constrained('recruitment.vacancies')->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained('recruitment.document_types')->restrictOnDelete();
            $table->boolean('required')->default(true);
            $table->string('source_allowed', 16);
            $table->primary(['vacancy_id', 'document_type_id']);
        });

        DB::statement("ALTER TABLE recruitment.vacancy_document_requirements ADD CONSTRAINT vacancy_doc_req_source_check CHECK (source_allowed IN ('individual','group','either'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.vacancy_document_requirements');
    }
};
