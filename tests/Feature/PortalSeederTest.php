<?php

namespace Tests\Feature;

use App\Models\Recruitment\RecruitmentUser;
use Database\Seeders\PortalSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reseeding_preserves_existing_credentials_and_disabled_state(): void
    {
        $this->seed(PortalSeeder::class);
        $admin = RecruitmentUser::where('email', 'admin@inka.test')->firstOrFail();
        $admin->update(['password' => 'changed-secret-2026', 'disabled_at' => now()]);
        $password = $admin->fresh()->password;

        $this->seed(PortalSeeder::class);

        $admin->refresh();
        $this->assertSame($password, $admin->password);
        $this->assertTrue(Hash::check('changed-secret-2026', $admin->password));
        $this->assertNotNull($admin->disabled_at);
    }

    public function test_demo_seed_is_rejected_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('local atau testing');

        (new PortalSeeder)->run();
    }
}
