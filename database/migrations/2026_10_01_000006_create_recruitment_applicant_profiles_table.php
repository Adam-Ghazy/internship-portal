<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.applicant_profiles', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->foreignId('user_id')->unique()->constrained('recruitment.users')->restrictOnDelete();
            $table->string('phone', 32)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['id', 'user_id'], 'applicant_profiles_id_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.applicant_profiles');
    }
};
