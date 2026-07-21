<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_modern_login_page_with_demo_credentials(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('تسجيل الدخول')
            ->assertSee('admin@daftar.test')
            ->assertSee('بيانات الدخول التجريبية');
    }

    public function test_authenticated_user_is_redirected_from_home_to_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect('/admin');
    }

    public function test_valid_credentials_log_in_and_redirect_to_admin(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_error(): void
    {
        User::factory()->create(['email' => 'someone@daftar.test']);

        $this->from('/')
            ->post('/login', ['email' => 'someone@daftar.test', 'password' => 'wrong'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
