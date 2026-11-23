<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_parses_decimal_amounts_into_centavos(): void
    {
        $this->assertSame(2550, Money::cents('25.50'));
        $this->assertSame(2550, Money::cents(25.5));
        $this->assertSame(102550, Money::cents('₱1,025.50'));
        $this->assertSame(0, Money::cents(null));
        $this->assertSame(0, Money::cents(''));
        $this->assertSame(-150, Money::cents('-1.50'));
    }

    public function test_it_rounds_half_centavos_up(): void
    {
        $this->assertSame(101, Money::cents('1.005'));
        $this->assertSame(100, Money::cents('1.004'));
    }

    public function test_it_renders_centavos_as_decimal_strings(): void
    {
        $this->assertSame('0.05', Money::decimal(5));
        $this->assertSame('12.34', Money::decimal(1234));
        $this->assertSame('-12.34', Money::decimal(-1234));
        $this->assertSame('₱1,234.50', Money::formatCents(123450));
    }
}
