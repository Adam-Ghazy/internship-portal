<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('email', 254);
            $table->string('password', 255);
            $table->string('remember_token', 100)->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('disabled_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->unique('email');
        });

        DB::statement("ALTER TABLE recruitment.users ADD CONSTRAINT users_email_check CHECK (email = lower(btrim(email)) AND length(email) > 3)");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.users');
    }
};
