<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'variance',
        'notes',
    ];

    protected $casts = [
        'status' => ShiftStatus::class,
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /** @param Builder<Shift> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', ShiftStatus::Open->value);
    }

    public function isOpen(): bool
    {
        return $this->status === ShiftStatus::Open;
    }

    /** Cash sales rung up during this shift. */
    public function cashSalesTotalCents(): int
    {
        return $this->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->get()
            ->sum(fn (Sale $sale) => Money::cents($sale->total));
    }

    public function expectedCashCents(): int
    {
        return Money::cents($this->opening_cash) + $this->cashSalesTotalCents();
    }
}
