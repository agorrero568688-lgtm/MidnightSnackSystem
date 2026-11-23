<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;

class StockService
{
    /**
     * Apply a stock delta to a product and record the movement.
     *
     * The caller is responsible for wrapping this in a transaction and for
     * locking the product row when concurrent writes are possible.
     */
    public function record(
        Product $product,
        StockMovementType $type,
        int $qtyChange,
        ?User $user = null,
        ?string $note = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        $product->stock_qty = $product->stock_qty + $qtyChange;
        $product->save();

        return StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'type' => $type,
            'qty_change' => $qtyChange,
            'qty_after' => $product->stock_qty,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
        ]);
    }

    public function receive(Product $product, int $qty, ?User $user = null, ?string $note = null): StockMovement
    {
        return $this->record($product, StockMovementType::In, $qty, $user, $note ?: 'Stock received');
    }

    /** Set stock to an absolute value, recording the difference as an adjustment. */
    public function adjustTo(Product $product, int $newQty, ?User $user = null, ?string $note = null): StockMovement
    {
        $delta = $newQty - $product->stock_qty;

        return $this->record($product, StockMovementType::Adjustment, $delta, $user, $note ?: 'Manual adjustment');
    }

    public function wastage(Product $product, int $qty, ?User $user = null, ?string $note = null): StockMovement
    {
        return $this->record($product, StockMovementType::Wastage, -abs($qty), $user, $note ?: 'Wastage / expired');
    }
}
