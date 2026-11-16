<?php

namespace Tests\Feature;

use Database\Seeders\PortalSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(PortalSeeder::class);
    }

    public function test_catalog_is_paginated_and_has_a_stable_order(): void
    {
        $this->get('/')->assertOk()->assertViewHas('vacancies', function ($vacancies) {
            $this->assertSame(12, $vacancies->perPage());
            $this->assertLessThanOrEqual(12, $vacancies->count());

            return true;
        });
    }

    public function test_unpublished_vacancy_cannot_be_viewed(): void
    {
        $vacancy = DB::table('recruitment.vacancies')->first();
        DB::table('recruitment.vacancies')->where('id', $vacancy->id)->update(['status' => 'draft', 'published_at' => null]);

        $this->get('/posisi/'.$vacancy->slug)->assertNotFound();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
