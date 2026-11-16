<?php

namespace Tests\Feature;

use App\Models\Recruitment\RecruitmentUser;
use Database\Seeders\PortalSeeder;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicantPerformanceTest extends TestCase
{
    use DatabaseTransactions;

    private function draft(): array
    {
        $this->seed(PortalSeeder::class);
        $user = RecruitmentUser::where('email', 'bagas@inka.test')->firstOrFail();
        $vacancy = DB::table('recruitment.vacancies')->where('status', 'published')->first();
        $this->actingAs($user)->get(route('apply.create', $vacancy->slug))->assertOk();
        $application = DB::table('recruitment.applications')->where('applicant_user_id', $user->id)->first();

        return [$user, $application];
    }

    public function test_orphan_cleanup_defaults_to_dry_run_and_preserves_referenced_files(): void
    {
        Storage::fake('local');
        [$user, $application] = $this->draft();
        $base = ['disk' => 'local', 'uploader_id' => $user->id, 'mime' => 'application/pdf',
            'byte_size' => 10, 'sha256' => str_repeat('a', 64), 'created_at' => now()->subDays(3)];
        $orphan = DB::table('recruitment.stored_files')->insertGetId($base + ['object_key' => 'private/applications/orphan.pdf']);
        $kept = DB::table('recruitment.stored_files')->insertGetId($base + ['object_key' => 'private/applications/kept.pdf']);
        $type = DB::table('recruitment.vacancy_document_requirements')->where('vacancy_id', $application->vacancy_id)->value('document_type_id');
        DB::table('recruitment.application_draft_documents')->insert(['application_id' => $application->id, 'document_type_id' => $type, 'stored_file_id' => $kept]);
        Storage::disk('local')->put('private/applications/orphan.pdf', 'orphan');
        Storage::disk('local')->put('private/applications/kept.pdf', 'kept');
        $this->artisan('applications:prune-files')->assertSuccessful();
        Storage::disk('local')->assertExists('private/applications/orphan.pdf');
        $this->artisan('applications:prune-files', ['--execute' => true])->assertSuccessful();
        Storage::disk('local')->assertMissing('private/applications/orphan.pdf');
        Storage::disk('local')->assertExists('private/applications/kept.pdf');
        $this->assertFalse(DB::table('recruitment.stored_files')->where('id', $orphan)->exists());
        $this->assertTrue(DB::table('recruitment.stored_files')->where('id', $kept)->exists());
    }

    public function test_cleanup_preserves_historical_submission_and_recent_orphans(): void
    {
        Storage::fake('local');
        [$user, $application] = $this->draft();
        $type = DB::table('recruitment.vacancy_document_requirements')->where('vacancy_id', $application->vacancy_id)->value('document_type_id');
        $base = ['disk' => 'local', 'uploader_id' => $user->id, 'mime' => 'application/pdf',
            'byte_size' => 10, 'sha256' => str_repeat('b', 64)];
        $historic = DB::table('recruitment.stored_files')->insertGetId($base + ['object_key' => 'private/applications/history.pdf', 'created_at' => now()->subDays(5)]);
        $recent = DB::table('recruitment.stored_files')->insertGetId($base + ['object_key' => 'private/applications/recent.pdf']);
        $submission = DB::table('recruitment.application_submissions')->insertGetId([
            'application_id' => $application->id, 'version_no' => 1, 'profile_snapshot' => '{}',
            'education_snapshot' => '[]', 'vacancy_snapshot' => '{}', 'submitted_by' => $user->id,
        ]);
        DB::table('recruitment.application_documents')->insert(['submission_id' => $submission, 'document_type_id' => $type, 'stored_file_id' => $historic]);
        Storage::disk('local')->put('private/applications/history.pdf', 'historic');
        Storage::disk('local')->put('private/applications/recent.pdf', 'recent');
        $this->artisan('applications:prune-files', ['--execute' => true])->assertSuccessful();
        Storage::disk('local')->assertExists('private/applications/history.pdf');
        Storage::disk('local')->assertExists('private/applications/recent.pdf');
        $this->assertSame(2, DB::table('recruitment.stored_files')->whereIn('id', [$historic, $recent])->count());
        $this->artisan('applications:prune-files', ['--hours' => 0, '--execute' => true])->assertFailed();
    }

    public function test_upload_requires_ownership_before_storing_file(): void
    {
        Storage::fake('local');
        [, $application] = $this->draft();
        $other = RecruitmentUser::create(['name' => 'Other applicant', 'email' => 'other-performance@example.test', 'password' => 'password']);
        DB::table('recruitment.applicant_profiles')->insert(['user_id' => $other->id]);
        $this->flushSession();
        $this->actingAs($other)->post(route('apply.documents', $application->id), [
            'lock_version' => 0, 'document_type_id' => 1,
            'file' => UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertNotFound();
        $this->assertSame([], Storage::disk('local')->allFiles('private/applications'));
    }

    public function test_profile_save_uses_selected_institutions_and_one_education_insert(): void
    {
        [, $application] = $this->draft();
        $institution = DB::table('recruitment.institutions')->insertGetId(['name' => 'Selected University', 'institution_type' => 'university']);
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });
        $education = ['institution_id' => $institution, 'education_level' => 'S1', 'major' => 'Informatika', 'is_active' => true];
        $this->post(route('apply.profile', $application->id), [
            'lock_version' => 0, 'phone' => '08123456789', 'educations' => [$education, $education],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $institutionQueries = array_filter($queries, fn ($sql) => str_contains($sql, '"recruitment"."institutions"'));
        $this->assertCount(1, $institutionQueries);
        $this->assertStringContainsString(' in (', array_values($institutionQueries)[0]);
        $this->assertCount(1, array_filter($queries, fn ($sql) => str_starts_with($sql, 'insert into "recruitment"."applicant_educations"')));
        $this->assertSame(2, DB::table('recruitment.applicant_educations')->where('applicant_profile_id', $application->applicant_profile_id)->count());
    }

    public function test_applicant_list_is_paginated(): void
    {
        $this->draft();
        $this->get(route('applications.index'))->assertOk()->assertViewHas('applications',
            fn ($rows) => $rows instanceof Paginator && $rows->perPage() === 15);
    }

    public function test_education_count_is_bounded_before_writes(): void
    {
        [, $application] = $this->draft();
        $this->post(route('apply.profile', $application->id), [
            'lock_version' => 0, 'phone' => '08123456789',
            'educations' => array_fill(0, 21, []),
        ])->assertSessionHasErrors('educations');
        $this->assertSame(0, DB::table('recruitment.application_drafts')->where('application_id', $application->id)->value('lock_version'));
    }

    public function test_upload_object_exists_before_locks_and_vacancy_precedes_application(): void
    {
        Storage::fake('local');
        [, $application] = $this->draft();
        $type = DB::table('recruitment.vacancy_document_requirements')->where('vacancy_id', $application->vacancy_id)->value('document_type_id');
        $locks = [];
        DB::listen(function ($query) use (&$locks) {
            if (str_contains($query->sql, 'for update')) {
                $this->assertCount(1, Storage::disk('local')->allFiles('private/applications'));
                $locks[] = $query->sql;
            }
        });
        $content = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF";
        $this->post(route('apply.documents', $application->id), [
            'lock_version' => 0, 'document_type_id' => $type,
            'file' => UploadedFile::fake()->createWithContent('cv.pdf', $content),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $vacancyLock = array_key_first(array_filter($locks, fn ($sql) => str_contains($sql, '"recruitment"."vacancies"')));
        $applicationLock = array_key_first(array_filter($locks, fn ($sql) => str_contains($sql, '"recruitment"."applications"')));
        $this->assertNotNull($vacancyLock);
        $this->assertNotNull($applicationLock);
        $this->assertLessThan($applicationLock, $vacancyLock);
        $stored = DB::table('recruitment.stored_files')->where('object_key', Storage::disk('local')->allFiles('private/applications')[0])->first();
        $this->assertSame(hash('sha256', $content), $stored->sha256);
        $this->assertSame(1, DB::table('recruitment.application_drafts')->where('application_id', $application->id)->value('lock_version'));
    }

    public function test_stale_upload_removes_new_object_without_changing_draft(): void
    {
        Storage::fake('local');
        [, $application] = $this->draft();
        $type = DB::table('recruitment.vacancy_document_requirements')->where('vacancy_id', $application->vacancy_id)->value('document_type_id');
        $this->post(route('apply.documents', $application->id), [
            'lock_version' => 99, 'document_type_id' => $type,
            'file' => UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"),
        ])->assertSessionHasErrors('lock_version');
        $this->assertSame([], Storage::disk('local')->allFiles('private/applications'));
        $this->assertFalse(DB::table('recruitment.application_draft_documents')->where('application_id', $application->id)->exists());
    }
}
