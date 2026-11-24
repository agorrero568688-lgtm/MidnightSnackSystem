<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'user_id',
        'type',
        'qty_change',
        'qty_after',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => InventoryMovementType::class,
            'qty_change' => 'decimal:3',
            'qty_after' => 'decimal:3',
        ];
    }

    /** @return BelongsTo<Inventory, $this> */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
