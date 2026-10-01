<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.vacancy_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained('recruitment.vacancies')->restrictOnDelete();
            $table->string('label', 160);
            $table->text('description')->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.vacancy_requirements');
    }
};
