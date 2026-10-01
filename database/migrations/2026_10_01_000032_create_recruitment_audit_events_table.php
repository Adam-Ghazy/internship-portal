<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type', 80);
            $table->bigInteger('resource_id');
            $table->foreignId('actor_id')->nullable()->constrained('recruitment.users')->restrictOnDelete();
            $table->string('event_type', 80);
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('occurred_at')->useCurrent();
        });

        DB::statement("ALTER TABLE recruitment.audit_events ADD CONSTRAINT audit_events_metadata_check CHECK (jsonb_typeof(metadata) = 'object')");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.audit_events');
    }
};
