<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('recruitment.applications')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('recruitment.users')->restrictOnDelete();
            $table->string('event_type', 80);
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('occurred_at')->useCurrent();
        });

        DB::statement("ALTER TABLE recruitment.application_events ADD CONSTRAINT app_events_metadata_check CHECK (jsonb_typeof(metadata) = 'object')");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.application_events');
    }
};
