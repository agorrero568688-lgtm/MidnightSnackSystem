<?php

namespace App\Enums;

enum OrderType: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Delivery => 'Delivery',
            self::Pickup => 'Pickup',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Delivery => 'truck',
            self::Pickup => 'store',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Delivery => 'We bring it to your door',
            self::Pickup => 'Pick it up at the counter',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Delivery->value => self::Delivery->label(),
            self::Pickup->value => self::Pickup->label(),
        ];
    }
}
