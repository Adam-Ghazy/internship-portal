<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.recruitment_periods', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->string('code', 40)->unique();
            $table->string('title', 160);
            $table->timestampTz('opens_at');
            $table->timestampTz('closes_at');
        });

        DB::statement("ALTER TABLE recruitment.recruitment_periods ADD CONSTRAINT recruitment_periods_range CHECK (opens_at < closes_at)");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.recruitment_periods');
    }
};
