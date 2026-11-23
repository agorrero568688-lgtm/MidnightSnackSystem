<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataDestructionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_see_the_destruct_page(): void
    {
        $this->get('/data/destruct')->assertRedirect('/login');
    }

    public function test_cashiers_cannot_see_the_destruct_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/data/destruct')->assertForbidden();
    }

    public function test_admin_sees_the_destruct_page_with_the_data_tables(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create();
        Shift::query()->create([
            'user_id' => $admin->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/data/destruct')
            ->assertOk()
            ->assertSee('Delete all data')
            ->assertSee('products')
            ->assertSee('shifts');
    }

    public function test_the_confirmation_phrase_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create();

        $this->actingAs($admin)
            ->postJson('/data/destruct', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('confirmation');

        $this->assertCount(1, Product::all());
    }

    public function test_a_wrong_phrase_deletes_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create();

        $this->actingAs($admin)
            ->postJson('/data/destruct', ['confirmation' => 'no no no'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('confirmation');

        $this->assertCount(1, Product::all());
    }

    public function test_the_exact_phrase_deletes_everything(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create();

        Sale::query()->create([
            'user_id' => $admin->id,
            'subtotal' => '10.00',
            'total' => '10.00',
        ]);

        $this->actingAs($admin)
            ->post('/data/destruct', ['confirmation' => 'DELETE ALL DATA'])
            ->assertRedirect('/data/destruct');

        foreach (['products', 'sales', 'users', 'orders', 'order_items', 'sale_items', 'stock_movements', 'shifts', 'categories', 'settings'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }
}
