<?php

namespace App\Enums;

enum DiscountType: string
{
    case None = 'none';
    case Senior = 'senior';
    case Pwd = 'pwd';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No discount',
            self::Senior => 'Senior Citizen (20%)',
            self::Pwd => 'PWD (20%)',
        };
    }

    /** Percentage rate applied to the subtotal. */
    public function rate(): int
    {
        return match ($this) {
            self::None => 0,
            self::Senior, self::Pwd => 20,
        };
    }

    public function requiresCustomer(): bool
    {
        return $this !== self::None;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
