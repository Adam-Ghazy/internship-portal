<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment.users', function (Blueprint $table) {
            $table->string('username', 64)->nullable()->unique('users_username_unique');
        });

        DB::statement("ALTER TABLE recruitment.users ADD CONSTRAINT users_username_check CHECK (username IS NULL OR username = btrim(username))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE recruitment.users DROP CONSTRAINT IF EXISTS users_username_check');

        Schema::table('recruitment.users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }
};