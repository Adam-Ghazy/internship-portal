<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.application_group_members', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('group_id');
            $table->bigInteger('period_id');
            $table->string('invited_email', 254);
            $table->string('invited_name', 160);
            $table->foreignId('claimed_user_id')->nullable()->constrained('recruitment.users')->restrictOnDelete();
            $table->timestampTz('confirmed_at')->nullable();
            $table->string('status', 16)->default('invited');
            $table->unique(['group_id', 'invited_email'], 'group_members_email_unique');
            $table->unique(['group_id', 'claimed_user_id'], 'group_members_claimed_unique');
            $table->unique(['id', 'claimed_user_id', 'period_id'], 'group_members_id_user_period_unique');
        });

        DB::statement("ALTER TABLE recruitment.application_group_members ADD CONSTRAINT group_members_email_check CHECK (invited_email = lower(btrim(invited_email)))");
        DB::statement("ALTER TABLE recruitment.application_group_members ADD CONSTRAINT group_members_status_check CHECK (status IN ('invited','confirmed','revoked'))");
        DB::statement("ALTER TABLE recruitment.application_group_members ADD CONSTRAINT group_members_claimed_check CHECK ((claimed_user_id IS NULL) = (confirmed_at IS NULL))");
        DB::statement("ALTER TABLE recruitment.application_group_members ADD CONSTRAINT group_members_confirmed_check CHECK (status <> 'confirmed' OR claimed_user_id IS NOT NULL)");
        DB::statement("ALTER TABLE recruitment.application_group_members ADD CONSTRAINT group_members_group_fk FOREIGN KEY (group_id, period_id) REFERENCES recruitment.application_groups(id, period_id) ON DELETE RESTRICT");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.application_group_members');
    }
};
