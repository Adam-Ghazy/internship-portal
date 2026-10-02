<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.notification_deliveries', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->foreignId('outbox_id')->constrained('recruitment.outbox_events')->restrictOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('recruitment.users')->restrictOnDelete();
            $table->foreignId('invitation_id')->nullable()->constrained('recruitment.group_invitations')->restrictOnDelete();
            $table->string('channel', 16);
            $table->string('recipient_key', 300);
            $table->string('email_snapshot', 254)->nullable();
            $table->string('status', 16)->default('pending');
            $table->integer('attempts')->default(0);
            $table->string('provider_reference', 200)->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->unique(['outbox_id', 'channel', 'recipient_key'], 'deliveries_outbox_channel_key_unique');
        });

        DB::statement("ALTER TABLE recruitment.notification_deliveries ADD CONSTRAINT deliveries_channel_check CHECK (channel IN ('email','in_app'))");
        DB::statement("ALTER TABLE recruitment.notification_deliveries ADD CONSTRAINT deliveries_status_check CHECK (status IN ('pending','sending','sent','failed','cancelled'))");
        DB::statement("ALTER TABLE recruitment.notification_deliveries ADD CONSTRAINT deliveries_attempts_check CHECK (attempts >= 0)");
        DB::statement("ALTER TABLE recruitment.notification_deliveries ADD CONSTRAINT deliveries_recipient_check CHECK ((channel='email' AND email_snapshot IS NOT NULL AND email_snapshot = lower(btrim(email_snapshot)) AND recipient_key = 'email:' || email_snapshot) OR (channel='in_app' AND recipient_user_id IS NOT NULL AND email_snapshot IS NULL AND recipient_key = 'user:' || recipient_user_id::text))");
        DB::statement("ALTER TABLE recruitment.notification_deliveries ADD CONSTRAINT deliveries_sent_check CHECK (status <> 'sent' OR sent_at IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.notification_deliveries');
    }
};
