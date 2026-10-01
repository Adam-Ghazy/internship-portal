<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.org_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('recruitment.org_units')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name', 160);
            $table->timestampTz('archived_at')->nullable();
        });

        DB::statement("ALTER TABLE recruitment.org_units ADD CONSTRAINT org_units_parent_check CHECK (parent_id IS NULL OR parent_id <> id)");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.org_units');
    }
};
