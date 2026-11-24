<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'is_available',
        'is_active',
        'is_online',
        'sort_order',
    ];

    protected $attributes = [
        'unit' => 'serving',
        'cost_price' => 0,
        'selling_price' => 0,
        'is_available' => true,
        'is_active' => true,
        'is_online' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_available' => 'boolean',
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

    /** @param Builder<Product> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<Product> $query */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_available', true);
    }

    /** @param Builder<Product> $query */
    public function scopeOnline(Builder $query): void
    {
        $query->where('is_active', true)->where('is_online', true);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->is_available;
    }

    public function isSoldOut(): bool
    {
        return ! $this->is_available;
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
