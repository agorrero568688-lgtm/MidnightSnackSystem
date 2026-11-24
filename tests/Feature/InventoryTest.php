<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Livewire\Inventory\Index as InventoryIndex;
use App\Models\Inventory;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_service_records_decimal_movements(): void
    {
        $inventory = Inventory::factory()->create(['quantity' => 10, 'unit' => 'kg']);
        $service = app(InventoryService::class);

        $service->receive($inventory, 2.5);
        $this->assertSame('12.500', $inventory->fresh()->quantity);

        $service->adjustTo($inventory->fresh(), 10);
        $this->assertSame('10.000', $inventory->fresh()->quantity);

        $service->wastage($inventory->fresh(), 1.25);
        $this->assertSame('8.750', $inventory->fresh()->quantity);

        $this->assertDatabaseCount('inventory_movements', 3);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'type' => InventoryMovementType::In->value,
            'qty_change' => '2.500',
            'qty_after' => '12.500',
        ]);
    }

    public function test_low_stock_scope_and_value(): void
    {
        $low = Inventory::factory()->lowStock()->create(['cost_per_unit' => 100]);
        $healthy = Inventory::factory()->create(['quantity' => 10, 'reorder_level' => 2]);

        $this->assertTrue($low->isLowStock());
        $this->assertSame(5000, $low->stockValueCents());
        $this->assertFalse($healthy->isLowStock());

        $lowStock = Inventory::query()->lowStock()->pluck('id');

        $this->assertTrue($lowStock->contains($low->id));
        $this->assertFalse($lowStock->contains($healthy->id));
    }

    public function test_admin_can_add_an_ingredient_and_record_a_movement(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(InventoryIndex::class)
            ->call('openCreate')
            ->set('name', 'Pork Belly')
            ->set('category', 'Meat & Seafood')
            ->set('unit', 'kg')
            ->set('quantity', '10')
            ->set('reorderLevel', '3')
            ->set('costPerUnit', '280')
            ->call('saveIngredient')
            ->assertHasNoErrors();

        $inventory = Inventory::query()->where('name', 'Pork Belly')->sole();

        $this->assertSame('10.000', $inventory->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'type' => InventoryMovementType::In->value,
            'note' => 'Opening stock',
        ]);

        Livewire::actingAs($admin)
            ->test(InventoryIndex::class)
            ->set('inventoryId', (string) $inventory->id)
            ->set('mode', 'in')
            ->set('qty', '2.5')
            ->call('saveMovement')
            ->assertHasNoErrors();

        $this->assertSame('12.500', $inventory->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'type' => InventoryMovementType::In->value,
            'qty_change' => '2.500',
            'qty_after' => '12.500',
        ]);
    }

    public function test_inventory_page_renders_for_admins(): void
    {
        $admin = User::factory()->admin()->create();

        Inventory::factory()->create(['name' => 'Cooking Oil']);

        $this->actingAs($admin)
            ->get('/inventory')
            ->assertOk()
            ->assertSee('New ingredient')
            ->assertSee('Cooking Oil');
    }
}
