<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.internship_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 160);
            $table->timestampTz('archived_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.internship_programs');
    }
};
