<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case AwaitingVerification = 'awaiting_verification';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::AwaitingVerification => 'Awaiting verification',
            self::Paid => 'Paid',
            self::Refunded => 'Refunded',
            self::Failed => 'Failed',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::AwaitingVerification => 'Verify',
            self::Paid => 'Paid',
            self::Refunded => 'Refunded',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'slate',
            self::AwaitingVerification => 'amber',
            self::Paid => 'green',
            self::Refunded => 'violet',
            self::Failed => 'red',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }
}
