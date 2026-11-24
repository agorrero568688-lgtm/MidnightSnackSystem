<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\User;

class InventoryService
{
    /**
     * Apply a decimal stock delta to an ingredient and record the movement.
     *
     * The caller is responsible for wrapping this in a transaction when
     * concurrent writes are possible.
     */
    public function record(
        Inventory $inventory,
        InventoryMovementType $type,
        float $qtyChange,
        ?User $user = null,
        ?string $note = null,
    ): InventoryMovement {
        $qtyChange = round($qtyChange, 3);
        $inventory->quantity = round((float) $inventory->quantity + $qtyChange, 3);
        $inventory->save();

        return InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'user_id' => $user?->id,
            'type' => $type,
            'qty_change' => $qtyChange,
            'qty_after' => $inventory->quantity,
            'note' => $note,
        ]);
    }

    public function receive(Inventory $inventory, float $qty, ?User $user = null, ?string $note = null): InventoryMovement
    {
        return $this->record($inventory, InventoryMovementType::In, $qty, $user, $note ?: 'Stock received');
    }

    /** Set the stock to an absolute value, recording the difference as an adjustment. */
    public function adjustTo(Inventory $inventory, float $newQty, ?User $user = null, ?string $note = null): InventoryMovement
    {
        $delta = round($newQty - (float) $inventory->quantity, 3);

        return $this->record($inventory, InventoryMovementType::Adjustment, $delta, $user, $note ?: 'Manual adjustment');
    }

    public function wastage(Inventory $inventory, float $qty, ?User $user = null, ?string $note = null): InventoryMovement
    {
        return $this->record($inventory, InventoryMovementType::Wastage, -abs($qty), $user, $note ?: 'Wastage / expired');
    }
}
