<?php

namespace App\Enums;

enum SalesChannel: string
{
    case Pos = 'pos';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::Pos => 'Walk-in',
            self::Online => 'Online',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pos => 'slate',
            self::Online => 'sky',
        };
    }
}
