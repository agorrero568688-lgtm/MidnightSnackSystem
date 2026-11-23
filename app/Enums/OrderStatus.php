<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case OutForDelivery = 'out_for_delivery';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Preparing',
            self::Ready => 'Ready',
            self::OutForDelivery => 'Out for delivery',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'sky',
            self::Preparing => 'violet',
            self::Ready => 'green',
            self::OutForDelivery => 'sky',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }

    public function isActive(): bool
    {
        return ! $this->isFinal();
    }

    /** Column heading for the staff board. */
    public function boardLabel(): string
    {
        return match ($this) {
            self::Pending => 'New',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'In the kitchen',
            self::Ready => 'Ready',
            self::OutForDelivery => 'On the road',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Statuses that belong on the live board, in flow order. */
    public static function boardColumns(): array
    {
        return [self::Pending, self::Confirmed, self::Preparing, self::Ready, self::OutForDelivery];
    }

    /** @return array<int, string> */
    public static function activeValues(): array
    {
        return array_map(
            fn (self $status) => $status->value,
            array_values(array_filter(self::cases(), fn (self $status) => $status->isActive())),
        );
    }
}
