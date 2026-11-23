<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\OrderPaymentMethod;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'public_token',
        'sale_id',
        'status',
        'payment_status',
        'payment_method',
        'order_type',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'notes',
        'staff_notes',
        'subtotal',
        'delivery_fee',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'total',
        'discount_id_number',
        'payment_reference',
        'payment_proof_path',
        'payment_verified_by',
        'paid_at',
        'confirmed_by',
        'completed_by',
        'cancelled_by',
        'placed_at',
        'confirmed_at',
        'ready_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'alert_sent_at',
    ];

    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'order_type' => 'delivery',
        'discount_type' => 'none',
        'discount_rate' => 0,
        'subtotal' => 0,
        'delivery_fee' => 0,
        'discount_amount' => 0,
        'total' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => OrderPaymentMethod::class,
            'order_type' => OrderType::class,
            'discount_type' => DiscountType::class,
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'discount_rate' => 'integer',
            'payment_verified_by' => 'integer',
            'confirmed_by' => 'integer',
            'completed_by' => 'integer',
            'cancelled_by' => 'integer',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'ready_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
            'alert_sent_at' => 'datetime',
        ];
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<User, $this> */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return BelongsTo<User, $this> */
    public function paymentVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    /** @param Builder<Order> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', OrderStatus::activeValues());
    }

    /** @param Builder<Order> $query */
    public function scopePlacedToday(Builder $query): void
    {
        $query->whereDate('placed_at', today());
    }

    /** @param Builder<Order> $query */
    public function scopeAwaitingPayment(Builder $query): void
    {
        $query->whereIn('payment_status', [
            PaymentStatus::Unpaid->value,
            PaymentStatus::AwaitingVerification->value,
        ]);
    }

    public function isDelivery(): bool
    {
        return $this->order_type === OrderType::Delivery;
    }

    public function isPickup(): bool
    {
        return $this->order_type === OrderType::Pickup;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, [OrderStatus::Ready, OrderStatus::OutForDelivery], true)
            && $this->isPaid();
    }

    public function trackingUrl(): string
    {
        return route('orders.track', ['token' => $this->public_token]);
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('qty');
    }

    public function totalCents(): int
    {
        return Money::cents($this->total);
    }

    public function subtotalCents(): int
    {
        return Money::cents($this->subtotal);
    }

    public function deliveryFeeCents(): int
    {
        return Money::cents($this->delivery_fee);
    }

    public function statusColor(): string
    {
        return $this->status->color();
    }

    public function placedAtLabel(): string
    {
        return ($this->placed_at ?? $this->created_at)?->format('M d, h:i A') ?? '—';
    }
}
