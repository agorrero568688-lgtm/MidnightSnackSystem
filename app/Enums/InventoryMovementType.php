<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case In = 'in';
    case Adjustment = 'adjustment';
    case Wastage = 'wastage';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Stock in',
            self::Adjustment => 'Adjustment',
            self::Wastage => 'Wastage',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::In => 'green',
            self::Adjustment => 'violet',
            self::Wastage => 'red',
        };
    }
}
