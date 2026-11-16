<?php

namespace Tests\Feature;

use App\Models\Recruitment\RecruitmentUser;
use Database\Seeders\PortalSeeder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private int $application;

    private RecruitmentUser $admin;

    private RecruitmentUser $manager;

    private RecruitmentUser $sm;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Staff workflow requires PostgreSQL functions and constraints.');
        }
        $this->withoutVite();
        $this->seed(PortalSeeder::class);
        $this->admin = RecruitmentUser::where('email', 'admin@inka.test')->firstOrFail();
        $this->manager = RecruitmentUser::where('email', 'manager@inka.test')->firstOrFail();
        $this->sm = RecruitmentUser::where('email', 'sm@inka.test')->firstOrFail();
        $applicant = RecruitmentUser::where('email', 'bagas@inka.test')->firstOrFail();
        $profile = DB::table('recruitment.applicant_profiles')->where('user_id', $applicant->id)->first();
        $vacancy = DB::table('recruitment.vacancies')->where('slug', 'pengembangan-aplikasi-web')->first();
        $this->application = DB::table('recruitment.applications')->insertGetId([
            'public_reference' => 'STAFF-WORKFLOW-TEST', 'applicant_profile_id' => $profile->id,
            'applicant_user_id' => $applicant->id, 'vacancy_id' => $vacancy->id, 'period_id' => $vacancy->period_id,
        ]);
        $submission = DB::table('recruitment.application_submissions')->insertGetId([
            'application_id' => $this->application, 'version_no' => 1, 'profile_snapshot' => '{}',
            'education_snapshot' => '[]', 'vacancy_snapshot' => '{}', 'submitted_by' => $applicant->id,
        ]);
        DB::table('recruitment.applications')->where('id', $this->application)->update([
            'stage' => 'submitted', 'submitted_at' => now(), 'current_submission_id' => $submission,
        ]);
        $assignments = DB::table('recruitment.staff_assignments')->where('org_unit_id', $vacancy->org_unit_id)
            ->pluck('id', 'role_code');
        $this->asStaff($this->admin)->post(route('staff.assign', $this->application), [
            'manager_assignment_id' => $assignments['manager'], 'sm_assignment_id' => $assignments['sm'],
        ])->assertSessionHasNoErrors();
    }

    public function test_rendered_review_and_publication_forms_submit_the_controller_contract(): void
    {
        $this->submitReviewForm($this->manager, 'recommended', 'Rekomendasi berdasarkan dokumen.');
        $this->assertDatabaseHas('recruitment.applications', ['id' => $this->application, 'stage' => 'sm_review']);
        $this->submitReviewForm($this->sm, 'accepted', 'Persyaratan penerimaan terpenuhi.');
        $page = $this->asStaff($this->admin)->get(route('staff.show', $this->application))->assertOk();
        [$action, $payload] = $this->formPayload($page->getContent(), '/putuskan', 'Selamat, Anda diterima untuk magang.');
        $this->assertArrayHasKey('message', $payload);
        $this->post($action, $payload)->assertSessionHasNoErrors()->assertRedirect(route('staff.show', $this->application));
        $this->assertDatabaseHas('recruitment.application_publications', [
            'application_id' => $this->application, 'public_message' => 'Selamat, Anda diterima untuk magang.',
            'published_by' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('recruitment.outbox_events', ['event_type' => 'decision.published']);
    }

    public function test_invalid_review_input_is_rejected_without_acquiring_row_locks(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $this->asStaff($this->manager)->post(route('staff.review', $this->application), [
            'outcome' => 'accepted', 'note' => '',
        ])->assertSessionHasErrors(['outcome', 'note']);
        $locks = array_filter($queries, fn ($sql) => preg_match('/for (update|share)/i', $sql));
        $this->assertSame([], array_values($locks));
        $this->assertDatabaseHas('recruitment.applications', ['id' => $this->application, 'stage' => 'manager_review']);
    }

    public function test_unassigned_same_unit_reviewer_cannot_view_or_review_an_application(): void
    {
        $outsider = RecruitmentUser::create(['name' => 'Other manager', 'email' => 'other-manager@example.test', 'password' => 'password']);
        $unit = DB::table('recruitment.staff_assignments')->where('user_id', $this->manager->id)->value('org_unit_id');
        DB::table('recruitment.staff_assignments')->insert([
            'user_id' => $outsider->id, 'org_unit_id' => $unit, 'role_code' => 'manager', 'valid_from' => now()->subDay(),
        ]);
        $this->asStaff($outsider)->get(route('staff.show', $this->application))->assertNotFound();
        $this->post(route('staff.review', $this->application), ['outcome' => 'recommended', 'note' => 'Unauthorized review.'])->assertNotFound();
        $this->asStaff($this->manager)->post(route('staff.publish', $this->application), ['message' => 'Unauthorized publication.'])->assertForbidden();
    }

    public function test_expired_review_assignment_is_not_restored_by_a_new_role_assignment(): void
    {
        $assignment = DB::table('recruitment.staff_assignments')->where('user_id', $this->manager->id)->first();
        DB::table('recruitment.staff_assignments')->where('id', $assignment->id)->update([
            'valid_from' => now()->subDays(2), 'valid_to' => now()->subDay(),
        ]);
        DB::table('recruitment.staff_assignments')->insert([
            'user_id' => $this->manager->id, 'role_code' => 'manager',
            'org_unit_id' => $assignment->org_unit_id, 'valid_from' => now()->subHour(),
        ]);
        $this->asStaff($this->manager)->get(route('staff.show', $this->application))->assertNotFound();
        $this->post(route('staff.review', $this->application), [
            'outcome' => 'recommended', 'note' => 'Expired assignment review.',
        ])->assertNotFound();
        $this->get(route('staff.index'))->assertOk()->assertDontSee('STAFF-WORKFLOW-TEST');
    }

    public function test_histories_have_independent_bounded_pages(): void
    {
        for ($i = 0; $i < 26; $i++) {
            DB::table('recruitment.application_events')->insert([
                'application_id' => $this->application, 'actor_id' => $this->admin->id,
                'event_type' => 'test.event',
            ]);
        }
        $page = $this->asStaff($this->admin)->get(route('staff.show', [
            'application' => $this->application, 'events_page' => 2,
        ]))->assertOk();
        $events = $page->viewData('events');
        $reviews = $page->viewData('reviews');
        $this->assertInstanceOf(LengthAwarePaginator::class, $events);
        $this->assertSame(2, $events->currentPage());
        $this->assertSame(2, $events->count());
        $this->assertSame(27, $events->total());
        $this->assertSame(1, $reviews->currentPage());
        $this->assertSame(2, $reviews->total());
        $this->assertStringContainsString('events_page=2', $reviews->url(2));
    }

    private function asStaff(RecruitmentUser $actor): self
    {
        return $this->withSession(['password_hash_web' => $actor->getAuthPassword()])->actingAs($actor, 'web');
    }

    private function submitReviewForm(RecruitmentUser $actor, string $outcome, string $note): void
    {
        $page = $this->asStaff($actor)->get(route('staff.show', $this->application))->assertOk();
        [$action, $payload] = $this->formPayload($page->getContent(), '/review', $note);
        $this->assertArrayHasKey('note', $payload);
        $payload['outcome'] = $outcome;
        $this->post($action, $payload)->assertSessionHasNoErrors()->assertRedirect(route('staff.show', $this->application));
        $this->assertDatabaseHas('recruitment.application_reviews', [
            'application_id' => $this->application, 'assignee_id' => $actor->id,
            'status' => 'completed', 'outcome' => $outcome, 'note_internal' => $note,
        ]);
    }

    private function formPayload(string $html, string $suffix, string $text): array
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        foreach ($document->getElementsByTagName('form') as $form) {
            $action = $form->getAttribute('action');
            if (! str_ends_with($action, $suffix)) {
                continue;
            }
            $payload = [];
            foreach ($form->getElementsByTagName('input') as $input) {
                if ($input->getAttribute('name')) {
                    $payload[$input->getAttribute('name')] = $input->getAttribute('value');
                }
            }
            foreach ($form->getElementsByTagName('textarea') as $textarea) {
                $payload[$textarea->getAttribute('name')] = $text;
            }

            return [$action, $payload];
        }
        $this->fail('Expected workflow form not found: '.$suffix);
    }
}
