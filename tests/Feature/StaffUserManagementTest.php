<?php

namespace Tests\Feature;

use App\Models\Recruitment\ApplicantProfile;
use App\Models\Recruitment\RecruitmentUser;
use App\Models\Recruitment\StaffAssignment;
use Database\Seeders\PortalSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffUserManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PortalSeeder::class);
        StaffAssignment::where('role_code', 'admin')->update(['valid_from' => now()->subDay()]);
        $this->actingAs(RecruitmentUser::where('username', 'admin')->firstOrFail());
    }

    public function test_admin_normalizes_email_before_creating_staff(): void
    {
        $this->post('/staf/pengguna', $this->data() + ['email' => '  STAFF@Example.Test  '])->assertRedirect(route('staff.users.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('recruitment.users', ['email' => 'staff@example.test']);
    }

    public function test_foreign_assignment_is_rejected_without_creating_another_assignment(): void
    {
        $user = RecruitmentUser::where('username', 'managerSDM')->firstOrFail();
        $foreign = RecruitmentUser::where('username', 'smSDM')->firstOrFail()->staffAssignments()->first();
        $before = StaffAssignment::where('user_id', $user->id)->count();
        $this->patch('/staf/pengguna/'.$user->id, $this->dataFor($user) + ['assignment_id' => $foreign->id])
            ->assertSessionHasErrors('assignment_id');
        $this->assertSame($before, StaffAssignment::where('user_id', $user->id)->count());
    }

    public function test_completed_review_survives_role_change_with_historical_assignment(): void
    {
        [$user, $assignment, $review] = $this->review('completed');
        $payload = $this->dataFor($user) + ['assignment_id' => $assignment->id];
        $payload['role_code'] = 'sm';
        $this->patch('/staf/pengguna/'.$user->id, $payload)->assertRedirect(route('staff.users.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('recruitment.application_reviews', ['id' => $review, 'staff_assignment_id' => $assignment->id, 'stage' => 'manager', 'status' => 'completed']);
        $this->assertSame('manager', $assignment->fresh()->role_code);
        $this->assertNotNull($assignment->fresh()->valid_to);
        $this->assertDatabaseHas('recruitment.staff_assignments', ['user_id' => $user->id, 'role_code' => 'sm', 'valid_to' => null]);
    }

    public function test_pending_review_prevents_scope_change_and_rolls_back_user_changes(): void
    {
        [$user, $assignment] = $this->review('pending');
        $payload = $this->dataFor($user) + ['assignment_id' => $assignment->id];
        $payload['role_code'] = 'sm';
        $payload['name'] = 'Should not persist';
        $this->patch('/staf/pengguna/'.$user->id, $payload)->assertSessionHasErrors('assignment_id');
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertNull($assignment->fresh()->valid_to);
    }

    public function test_duplicate_open_assignment_returns_validation_instead_of_database_error(): void
    {
        $user = RecruitmentUser::where('username', 'managerSDM')->firstOrFail();
        $this->patch('/staf/pengguna/'.$user->id, $this->dataFor($user))->assertSessionHasErrors('assignment_id');
    }

    public function test_admin_password_change_invalidates_the_staff_session(): void
    {
        $user = RecruitmentUser::where('username', 'managerSDM')->firstOrFail();
        $oldHash = $user->password;
        $assignment = $user->staffAssignments()->firstOrFail();
        $payload = $this->dataFor($user) + ['assignment_id' => $assignment->id, 'password' => 'replacement-password', 'password_confirmation' => 'replacement-password'];
        $this->patch('/staf/pengguna/'.$user->id, $payload)->assertRedirect(route('staff.users.index'))->assertSessionHasNoErrors();
        $this->assertNotSame($oldHash, $user->fresh()->password);

        $this->actingAs($user->fresh())->withSession(['password_hash_web' => $oldHash])
            ->get('/staf')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_cannot_expire_own_assignment(): void
    {
        $user = RecruitmentUser::where('username', 'admin')->firstOrFail();
        $assignment = $user->staffAssignments()->firstOrFail();
        $payload = $this->dataFor($user) + ['assignment_id' => $assignment->id, 'valid_to' => now()->subHour()->toDateTimeString()];
        $this->patch('/staf/pengguna/'.$user->id, $payload)->assertSessionHasErrors('valid_to');
        $this->assertNull($assignment->fresh()->valid_to);
    }

    public function test_unit_change_preserves_completed_review_scope(): void
    {
        [$user, $assignment, $review] = $this->review('completed');
        $payload = $this->dataFor($user) + ['assignment_id' => $assignment->id];
        $payload['org_unit_id'] = DB::table('recruitment.org_units')->where('code', 'ENG')->value('id');
        $this->patch('/staf/pengguna/'.$user->id, $payload)->assertRedirect(route('staff.users.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('recruitment.application_reviews', ['id' => $review, 'org_unit_id' => $assignment->org_unit_id, 'staff_assignment_id' => $assignment->id]);
        $this->assertSame($assignment->org_unit_id, $assignment->fresh()->org_unit_id);
        $this->assertNotNull($assignment->fresh()->valid_to);
    }

    public function test_normalized_duplicate_email_is_validation_error(): void
    {
        $this->post('/staf/pengguna', $this->data() + ['email' => ' ADMIN@INKA.TEST '])->assertSessionHasErrors('email');
    }

    private function data(): array
    {
        return ['name' => 'Staff Test', 'username' => 'staff-test', 'password' => 'password-test', 'password_confirmation' => 'password-test', 'role_code' => 'admin', 'valid_from' => now()->subDay()->toDateTimeString()];
    }

    private function dataFor(RecruitmentUser $user): array
    {
        $assignment = $user->staffAssignments()->firstOrFail();

        return ['name' => $user->name, 'username' => $user->username, 'email' => $user->email, 'role_code' => $assignment->role_code, 'org_unit_id' => $assignment->org_unit_id, 'valid_from' => $assignment->valid_from->toDateTimeString()];
    }

    private function review(string $status): array
    {
        $user = RecruitmentUser::where('username', 'managerSDM')->firstOrFail();
        $assignment = $user->staffAssignments()->firstOrFail();
        $assignment->update(['valid_from' => now()->subDay()]);
        $applicant = RecruitmentUser::where('username', 'bagas')->firstOrFail();
        $profile = ApplicantProfile::where('user_id', $applicant->id)->firstOrFail();
        $vacancy = DB::table('recruitment.vacancies')->where('org_unit_id', $assignment->org_unit_id)->first();
        $application = DB::table('recruitment.applications')->insertGetId(['public_reference' => 'STAFF-TEST', 'applicant_profile_id' => $profile->id, 'applicant_user_id' => $applicant->id, 'vacancy_id' => $vacancy->id, 'period_id' => $vacancy->period_id]);
        $submission = DB::table('recruitment.application_submissions')->insertGetId(['application_id' => $application, 'version_no' => 1, 'profile_snapshot' => '{}', 'education_snapshot' => '[]', 'vacancy_snapshot' => '{}', 'submitted_by' => $applicant->id]);
        $review = DB::table('recruitment.application_reviews')->insertGetId(['application_id' => $application, 'vacancy_id' => $vacancy->id, 'org_unit_id' => $vacancy->org_unit_id, 'stage' => 'manager', 'assignee_id' => $user->id, 'staff_assignment_id' => $assignment->id, 'status' => $status, 'based_on_submission_id' => $submission, 'assigned_by' => auth()->id(), 'outcome' => $status === 'completed' ? 'recommended' : null, 'note_internal' => $status === 'completed' ? 'Completed review' : null, 'acted_at' => $status === 'completed' ? now() : null]);

        return [$user, $assignment, $review];
    }
}
