<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Diding');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/pos')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_a_user_can_sign_in_with_their_username(): void
    {
        $user = User::factory()->create(['username' => 'cashier', 'password' => 'secret123']);

        $this->post('/login', ['username' => 'cashier', 'password' => 'secret123'])
            ->assertRedirect('/pos');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_does_not_sign_in(): void
    {
        User::factory()->create(['username' => 'cashier', 'password' => 'secret123']);

        $this->post('/login', ['username' => 'cashier', 'password' => 'nope'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_sign_in(): void
    {
        User::factory()->inactive()->create(['username' => 'cashier', 'password' => 'secret123']);

        $this->post('/login', ['username' => 'cashier', 'password' => 'secret123'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_cashiers_can_open_the_till_but_not_the_back_office(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)->get('/pos')->assertOk();
        $this->actingAs($cashier)->get('/dashboard')->assertForbidden();
        $this->actingAs($cashier)->get('/products')->assertForbidden();
    }

    public function test_admins_can_open_the_back_office_and_the_till(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/products')->assertOk();
        $this->actingAs($admin)->get('/pos')->assertOk();
    }

    public function test_root_redirects_according_to_role(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertRedirect('/dashboard');
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/pos');
    }
}
