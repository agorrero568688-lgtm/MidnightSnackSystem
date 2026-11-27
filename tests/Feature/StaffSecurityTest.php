<?php

namespace Tests\Feature;

use App\Livewire\Settings\Edit;
use App\Livewire\Users\Index;
use App\Models\LoginLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_gcash_changes_require_password_confirmation(): void
    {
        Setting::set('gcash_name', 'Original name');
        $admin = User::factory()->admin()->create(['password' => 'secret123']);

        Livewire::actingAs($admin)
            ->test(Edit::class)
            ->set('gcashName', 'Attacker name')
            ->call('save')
            ->assertHasErrors('confirmPassword');

        $this->assertSame('Original name', Setting::get('gcash_name'));
    }

    public function test_a_confirmed_gcash_change_is_saved(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret123']);

        Livewire::actingAs($admin)
            ->test(Edit::class)
            ->set('gcashName', 'Confirmed name')
            ->set('confirmPassword', 'secret123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Confirmed name', Setting::get('gcash_name'));
    }

    public function test_role_changes_require_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret123']);
        $cashier = User::factory()->create(['role' => 'cashier', 'username' => 'cashier']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('startEdit', $cashier->id)
            ->assertSet('originalRole', 'cashier')
            ->set('role', 'admin')
            ->call('save')
            ->assertHasErrors('confirmPassword');

        $this->assertSame('cashier', $cashier->fresh()->role->value);
    }

    public function test_deactivating_a_user_requires_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret123']);
        $cashier = User::factory()->create(['is_active' => true]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('confirmPassword', 'wrong-password')
            ->call('toggleActive', $cashier->id);

        $this->assertTrue($cashier->fresh()->is_active);
    }

    public function test_the_users_page_shows_recent_sign_ins(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['username' => 'cashier']);

        LoginLog::create([
            'user_id' => $user->id,
            'username' => 'cashier',
            'event' => LoginLog::FAILED,
            'ip_address' => '203.0.113.9',
            'user_agent' => 'Mozilla/5.0 Test Browser',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('Recent sign-ins')
            ->assertSee('203.0.113.9')
            ->assertSee('Failed');
    }
}
