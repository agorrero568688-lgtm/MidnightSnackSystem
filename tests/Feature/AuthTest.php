<?php

namespace Tests\Feature;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_cashiers_can_open_the_cashier_screen_but_not_the_back_office(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)->get('/pos')->assertOk();
        $this->actingAs($cashier)->get('/dashboard')->assertForbidden();
        $this->actingAs($cashier)->get('/products')->assertForbidden();
    }

    public function test_admins_can_open_the_back_office_and_the_cashier_screen(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/products')->assertOk();
        $this->actingAs($admin)->get('/pos')->assertOk();
    }

    public function test_root_shows_the_public_storefront_to_everyone(): void
    {
        $this->get('/')->assertOk()->assertSee("Diding's Midnight Snack");

        $this->actingAs(User::factory()->admin()->create())->get('/')->assertOk();
        $this->actingAs(User::factory()->create())->get('/')->assertOk();
    }

    public function test_a_successful_sign_in_is_logged(): void
    {
        $user = User::factory()->create(['username' => 'cashier', 'password' => 'secret123']);

        $this->post('/login', ['username' => 'cashier', 'password' => 'secret123'])
            ->assertRedirect('/pos');

        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->id,
            'username' => 'cashier',
            'event' => LoginLog::SUCCESS,
        ]);
    }

    public function test_a_failed_sign_in_is_logged_without_a_password(): void
    {
        User::factory()->create(['username' => 'cashier', 'password' => 'secret123']);

        $this->post('/login', ['username' => 'cashier', 'password' => 'nope'])
            ->assertSessionHasErrors('username');

        $this->assertDatabaseHas('login_logs', [
            'user_id' => null,
            'username' => 'cashier',
            'event' => LoginLog::FAILED,
        ]);
    }

    public function test_signing_out_everywhere_deletes_sessions_and_rotates_the_remember_token(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        DB::table('sessions')->insert([
            ['id' => 'session-a', 'user_id' => $user->id, 'payload' => 'a', 'last_activity' => time()],
            ['id' => 'session-b', 'user_id' => $user->id, 'payload' => 'b', 'last_activity' => time()],
        ]);

        $rememberToken = $user->remember_token;

        $this->actingAs($user)->post('/logout-all')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertNotSame($rememberToken, $user->fresh()->remember_token);
    }

    public function test_a_guest_cannot_sign_out_everywhere(): void
    {
        $this->post('/logout-all')->assertRedirect('/login');
    }
}
