<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.applicant_educations', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->foreignId('applicant_profile_id')->constrained('recruitment.applicant_profiles')->restrictOnDelete();
            $table->foreignId('institution_id')->constrained('recruitment.institutions')->restrictOnDelete();
            $table->string('education_level', 40);
            $table->string('major', 160);
            $table->boolean('is_active')->default(true);
            $table->decimal('grade', 6, 2)->nullable();
            $table->decimal('grade_scale', 6, 2)->nullable();
        });

        DB::statement("ALTER TABLE recruitment.applicant_educations ADD CONSTRAINT applicant_educations_grade_check CHECK ((grade IS NULL AND grade_scale IS NULL) OR (grade IS NOT NULL AND grade_scale IS NOT NULL AND grade >= 0 AND grade_scale > 0 AND grade <= grade_scale))");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.applicant_educations');
    }
};
