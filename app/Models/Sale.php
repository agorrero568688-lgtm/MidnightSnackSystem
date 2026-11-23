<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'user_id',
        'shift_id',
        'subtotal',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'total',
        'amount_tendered',
        'change_due',
        'payment_method',
        'status',
        'customer_name',
        'discount_id_number',
        'voided_by',
        'voided_at',
        'void_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'payment_method' => PaymentMethod::class,
            'status' => SaleStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_tendered' => 'decimal:2',
            'change_due' => 'decimal:2',
            'discount_rate' => 'integer',
            'voided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Shift, $this> */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /** @return BelongsTo<User, $this> */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @param Builder<Sale> $query */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', SaleStatus::Completed->value);
    }

    public function isVoided(): bool
    {
        return $this->status === SaleStatus::Voided;
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('qty');
    }
}
