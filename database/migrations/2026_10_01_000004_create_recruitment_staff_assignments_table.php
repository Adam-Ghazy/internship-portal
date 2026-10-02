<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment.staff_assignments', function (Blueprint $table) {
            $table->bigIncrements('id')->generatedAs();
            $table->foreignId('user_id')->constrained('recruitment.users')->restrictOnDelete();
            $table->string('role_code', 16);
            $table->foreignId('org_unit_id')->nullable()->constrained('recruitment.org_units')->restrictOnDelete();
            $table->timestampTz('valid_from')->useCurrent();
            $table->timestampTz('valid_to')->nullable();
            $table->unique(['id', 'user_id', 'role_code', 'org_unit_id'], 'staff_assignments_id_user_role_unit_unique');
        });

        DB::statement("ALTER TABLE recruitment.staff_assignments ADD CONSTRAINT staff_assignments_role_check CHECK (role_code IN ('admin','manager','sm'))");
        DB::statement("ALTER TABLE recruitment.staff_assignments ADD CONSTRAINT staff_assignments_valid_range CHECK (valid_to IS NULL OR valid_to > valid_from)");
        DB::statement("ALTER TABLE recruitment.staff_assignments ADD CONSTRAINT staff_assignments_scope_check CHECK ((role_code='admin' AND org_unit_id IS NULL) OR (role_code IN ('manager','sm') AND org_unit_id IS NOT NULL))");
        DB::statement("CREATE UNIQUE INDEX staff_one_current ON recruitment.staff_assignments(user_id, role_code, org_unit_id) NULLS NOT DISTINCT WHERE valid_to IS NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment.staff_assignments');
    }
};
