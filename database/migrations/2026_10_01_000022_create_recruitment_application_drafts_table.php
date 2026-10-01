<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.application_drafts', function (Blueprint $table) {
            $table->foreignId('application_id')->primary()->constrained('recruitment.applications')->restrictOnDelete();
            $table->jsonb('payload')->default('{}');
            $table->integer('lock_version')->default(0);
            $table->timestampTz('updated_at')->useCurrent();
        });

        DB::statement("ALTER TABLE recruitment.application_drafts ADD CONSTRAINT application_drafts_payload_check CHECK (jsonb_typeof(payload) = 'object')");
        DB::statement("ALTER TABLE recruitment.application_drafts ADD CONSTRAINT application_drafts_lock_check CHECK (lock_version >= 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.application_drafts');
    }
};
