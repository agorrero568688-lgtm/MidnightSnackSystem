<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Throwable;

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

    /**
     * Disk that stores product photos.
     *
     * Production uses Supabase Storage (S3) so uploads are durable across
     * Vercel deploys and the filesystem is read-only for app code.
     */
    public function photoDisk(): string
    {
        return config('app.env') === 'production' ? 's3' : 'public';
    }

    /**
     * Resolve the stored photo into a URL the browser can actually load.
     *
     * Uploaded photos live on the public disk (local in development, Supabase
     * S3 in production). Menu art committed under public/images ships with the
     * container, so it keeps working even when the serverless filesystem is
     * read-only and /storage symlinks do not exist.
     */
    public function getImageSrcAttribute(): ?string
    {
        $path = trim((string) ($this->image_path ?: $this->getAttribute('image_url')));

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'data:') || preg_match('#^(?:https?:)?//#i', $path) === 1) {
            return $path;
        }

        // Normalise legacy values such as "storage/products/photo.jpg".
        $path = ltrim((string) preg_replace('#^/?storage/#', '', $path), '/');

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'images/') && is_file(public_path($path))) {
            return asset($path);
        }

        $diskName = $this->photoDisk();

        try {
            if ($diskName === 's3') {
                // Remote existence checks (HEAD requests) are unreliable on
                // Supabase and can throw. For the storefront we can safely
                // build the public URL and let the browser fallback hide any
                // 404 with the menu gradient + food icon.
                $url = Storage::disk($diskName)->url($path);

                return $url !== '' ? $url : null;
            }

            if (! Storage::disk($diskName)->exists($path)) {
                return null;
            }

            return Storage::disk($diskName)->url($path);
        } catch (Throwable) {
            return null;
        }
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
