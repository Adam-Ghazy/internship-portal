<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.group_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('recruitment.application_group_members')->restrictOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
        });

        DB::statement("ALTER TABLE recruitment.group_invitations ADD CONSTRAINT group_invitations_token_check CHECK (token_hash ~ '^[0-9a-f]{64}\$')");
        DB::statement("ALTER TABLE recruitment.group_invitations ADD CONSTRAINT group_invitations_expiry_check CHECK (expires_at > created_at)");
        DB::statement("ALTER TABLE recruitment.group_invitations ADD CONSTRAINT group_invitations_used_revoked_check CHECK (NOT(used_at IS NOT NULL AND revoked_at IS NOT NULL))");
        DB::statement("CREATE UNIQUE INDEX one_open_invitation ON recruitment.group_invitations(member_id) WHERE used_at IS NULL AND revoked_at IS NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.group_invitations');
    }
};
