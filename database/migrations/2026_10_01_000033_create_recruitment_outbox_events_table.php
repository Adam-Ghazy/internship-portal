<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.outbox_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 200)->unique();
            $table->string('event_type', 80);
            $table->jsonb('payload')->default('{}');
            $table->timestampTz('available_at')->useCurrent();
            $table->timestampTz('lease_until')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE recruitment.outbox_events ADD CONSTRAINT outbox_payload_check CHECK (jsonb_typeof(payload) = 'object')");
        DB::statement("ALTER TABLE recruitment.outbox_events ADD CONSTRAINT outbox_attempts_check CHECK (attempts >= 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.outbox_events');
    }
};
