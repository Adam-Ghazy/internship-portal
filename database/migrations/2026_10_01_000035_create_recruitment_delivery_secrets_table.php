<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.delivery_secrets', function (Blueprint $table) {
            $table->foreignId('delivery_id')->primary()->constrained('recruitment.notification_deliveries')->restrictOnDelete();
            $table->binary('ciphertext');
            $table->string('key_version', 40);
            $table->timestampTz('expires_at');
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE recruitment.delivery_secrets ADD CONSTRAINT delivery_secrets_expiry_check CHECK (expires_at > created_at)");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.delivery_secrets');
    }
};
