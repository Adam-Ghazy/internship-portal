<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.stored_files', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->string('disk', 40);
            $table->string('object_key', 500);
            $table->foreignId('uploader_id')->constrained('recruitment.users')->restrictOnDelete();
            $table->string('mime', 100);
            $table->bigInteger('byte_size');
            $table->string('sha256', 64);
            $table->string('scan_status', 16)->default('pending');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['disk', 'object_key'], 'stored_files_disk_key_unique');
        });

        DB::statement("ALTER TABLE recruitment.stored_files ADD CONSTRAINT stored_files_mime_check CHECK (mime = 'application/pdf')");
        DB::statement("ALTER TABLE recruitment.stored_files ADD CONSTRAINT stored_files_size_check CHECK (byte_size > 0 AND byte_size <= 5242880)");
        DB::statement("ALTER TABLE recruitment.stored_files ADD CONSTRAINT stored_files_sha256_check CHECK (sha256 ~ '^[0-9a-f]{64}\$')");
        DB::statement("ALTER TABLE recruitment.stored_files ADD CONSTRAINT stored_files_scan_check CHECK (scan_status IN ('pending','clean','rejected'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.stored_files');
    }
};
