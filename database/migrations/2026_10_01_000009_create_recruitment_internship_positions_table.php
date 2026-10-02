<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.internship_positions', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->string('code', 40)->unique();
            $table->string('title', 160);
            $table->text('description')->default('');
            $table->timestampTz('archived_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.internship_positions');
    }
};
