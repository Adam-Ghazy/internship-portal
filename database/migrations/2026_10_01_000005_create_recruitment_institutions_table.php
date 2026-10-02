<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.institutions', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->string('name', 200);
            $table->string('institution_type', 40);
            $table->timestampTz('archived_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.institutions');
    }
};
