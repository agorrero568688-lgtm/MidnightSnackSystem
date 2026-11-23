<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Sale = 'sale';
    case Void = 'void';
    case Adjustment = 'adjustment';
    case Wastage = 'wastage';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Stock in',
            self::Sale => 'Sale',
            self::Void => 'Void / return',
            self::Adjustment => 'Adjustment',
            self::Wastage => 'Wastage',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::In => 'green',
            self::Sale => 'sky',
            self::Void => 'amber',
            self::Adjustment => 'violet',
            self::Wastage => 'red',
        };
    }
}
