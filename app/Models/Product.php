<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'barcode',
        'name',
        'description',
        'image_path',
        'unit',
        'cost_price',
        'selling_price',
        'stock_qty',
        'low_stock_threshold',
        'track_stock',
        'is_active',
        'is_online',
        'sort_order',
    ];

    protected $attributes = [
        'unit' => 'pc',
        'cost_price' => 0,
        'selling_price' => 0,
        'stock_qty' => 0,
        'low_stock_threshold' => 5,
        'track_stock' => true,
        'is_active' => true,
        'is_online' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock_qty' => 'integer',
            'low_stock_threshold' => 'integer',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_online' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @param Builder<Product> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<Product> $query */
    public function scopeOnline(Builder $query): void
    {
        $query->where('is_active', true)->where('is_online', true);
    }

    /** @param Builder<Product> $query */
    public function scopeLowStock(Builder $query): void
    {
        $query->where('track_stock', true)
            ->whereColumn('stock_qty', '<=', 'low_stock_threshold');
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->stock_qty <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->track_stock && $this->stock_qty <= 0;
    }

    public function stockLabel(): string
    {
        if (! $this->track_stock) {
            return 'Unlimited';
        }

        return (string) $this->stock_qty;
    }

    /** Find a product by scanned barcode or typed SKU. */
    public static function findByCode(string $code): ?self
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        return static::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($code) {
                $query->where('barcode', $code)
                    ->orWhereRaw('lower(sku) = ?', [mb_strtolower($code)]);
            })
            ->first();
    }
}
