<?php

namespace App\Enums;

enum OrderPaymentMethod: string
{
    case GCash = 'gcash';
    case CashOnDelivery = 'cod';
    case CashOnPickup = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::GCash => 'GCash',
            self::CashOnDelivery => 'Cash on delivery',
            self::CashOnPickup => 'Cash at pickup',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::GCash => 'GCash',
            self::CashOnDelivery => 'COD',
            self::CashOnPickup => 'Cash',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::GCash => 'Pay online and send us the reference number',
            self::CashOnDelivery => 'Pay the rider in cash when your order arrives',
            self::CashOnPickup => 'Pay at the counter when you pick up',
        };
    }

    public function isGcash(): bool
    {
        return $this === self::GCash;
    }

    public function isCash(): bool
    {
        return ! $this->isGcash();
    }

    /** The value written to the resulting sale. */
    public function toSaleMethod(): PaymentMethod
    {
        return $this->isGcash() ? PaymentMethod::GCash : PaymentMethod::Cash;
    }

    /** @return array<int, self> */
    public static function forType(OrderType $type): array
    {
        return $type === OrderType::Delivery
            ? [self::GCash, self::CashOnDelivery]
            : [self::GCash, self::CashOnPickup];
    }

    /** @return array<int, string> */
    public static function valuesForType(OrderType $type): array
    {
        return array_map(fn (self $method) => $method->value, self::forType($type));
    }
}
