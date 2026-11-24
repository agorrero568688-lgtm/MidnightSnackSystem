<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'Meat & Seafood',
        'Noodles & Rice',
        'Bread & Frozen',
        'Dairy & Eggs',
        'Condiments & Oil',
        'Beverages',
        'Packaging',
    ];

    public const UNITS = ['kg', 'pcs', 'packs', 'liters', 'bottles', 'trays', 'rolls'];

    protected $fillable = [
        'name',
        'category',
        'quantity',
        'unit',
        'reorder_level',
        'cost_per_unit',
        'is_active',
    ];

    protected $attributes = [
        'quantity' => 0,
        'unit' => 'pcs',
        'reorder_level' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'cost_per_unit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<InventoryMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /** @param Builder<Inventory> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<Inventory> $query */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function isLowStock(): bool
    {
        return (float) $this->quantity <= (float) $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->quantity <= 0;
    }

    public function stockValueCents(): int
    {
        return (int) round((float) $this->quantity * Money::cents($this->cost_per_unit));
    }

    public function quantityLabel(): string
    {
        $quantity = rtrim(rtrim(number_format((float) $this->quantity, 3, '.', ''), '0'), '.');

        return $quantity === '' ? '0' : $quantity;
    }

    public function reorderLabel(): string
    {
        $level = rtrim(rtrim(number_format((float) $this->reorder_level, 3, '.', ''), '0'), '.');

        return $level === '' ? '0' : $level;
    }
}
