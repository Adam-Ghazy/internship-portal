<?php

namespace Tests\Feature;

use App\Models\Recruitment\RecruitmentUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_is_throttled_even_with_rotating_identifiers(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['login' => "missing{$attempt}", 'password' => 'incorrect'])->assertRedirect();
        }

        $this->post('/login', ['login' => 'another', 'password' => 'incorrect'])->assertStatus(429);
    }

    public function test_registration_is_throttled_before_validation(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/register', [])->assertSessionHasErrors('email');
        }

        $this->post('/register', [])->assertStatus(429);
    }

    public function test_disabled_session_is_rejected_on_applicant_routes(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $user->update(['disabled_at' => now()]);

        $this->get('/lamaran')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_disabled_session_returns_unauthorized_json(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $user->update(['disabled_at' => now()]);

        $this->getJson('/dashboard')->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_login_seeds_password_hash_before_first_protected_request(): void
    {
        $user = $this->user();
        $this->post('/login', ['login' => strtoupper($user->email), 'password' => 'original-password'])
            ->assertRedirect('/dashboard')->assertSessionHas('password_hash_web', $user->password);
    }

    public function test_password_reset_invalidates_an_existing_login_session(): void
    {
        $user = $this->user();
        $this->post('/login', ['login' => $user->email, 'password' => 'original-password'])->assertRedirect();
        $user->update(['password' => 'changed-password']);
        Auth::forgetGuards();

        $this->get('/lamaran')->assertRedirect('/login');
        $this->assertGuest();
    }

    private function user(): RecruitmentUser
    {
        return RecruitmentUser::create([
            'name' => 'Security Test',
            'email' => 'security@example.test',
            'password' => 'original-password',
        ]);
    }
}
