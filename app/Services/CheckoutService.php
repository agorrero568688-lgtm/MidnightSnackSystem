<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\SalesChannel;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Support\Money;
use App\Support\PosCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Ring up a sale.
     *
     * Prices are always taken from the database — never from the client — and
     * stock rows are locked before anything is written.
     *
     * @param  array<int, array{product_id: int|string, qty: int|string}>  $lines
     *
     * @throws ValidationException
     */
    public function checkout(
        User $cashier,
        ?Shift $shift,
        array $lines,
        PaymentMethod $paymentMethod,
        string|int|float|null $amountTendered,
        DiscountType $discountType = DiscountType::None,
        ?string $customerName = null,
        ?string $discountIdNumber = null,
        ?string $notes = null,
    ): Sale {
        $quantities = $this->normalizeLines($lines);

        if ($quantities === []) {
            throw ValidationException::withMessages(['cart' => 'Add at least one item before charging.']);
        }

        if ($discountType->requiresCustomer()) {
            if (blank($customerName) || blank($discountIdNumber)) {
                throw ValidationException::withMessages([
                    'discount' => 'Customer name and OSCA/PWD ID number are required for a '.$discountType->label().'.',
                ]);
            }
        }

        $completedSale = DB::transaction(function () use (
            $cashier, $shift, $quantities, $paymentMethod, $amountTendered,
            $discountType, $customerName, $discountIdNumber, $notes
        ) {
            /** @var Collection<int, Product> $products */
            $products = Product::query()
                ->whereIn('id', array_keys($quantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $allowNegative = Setting::bool('allow_negative_stock');
            $subtotalCents = 0;
            $rows = [];

            foreach ($quantities as $productId => $qty) {
                $product = $products->get($productId);

                if (! $product) {
                    throw ValidationException::withMessages(['cart' => 'One of the items is no longer available.']);
                }

                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => "\"{$product->name}\" is inactive and cannot be sold.",
                    ]);
                }

                if ($product->track_stock && ! $allowNegative && $product->stock_qty < $qty) {
                    throw ValidationException::withMessages([
                        'cart' => "Not enough stock for \"{$product->name}\" — only {$product->stock_qty} left.",
                    ]);
                }

                $unitCents = Money::cents($product->selling_price);
                $lineCents = $unitCents * $qty;
                $subtotalCents += $lineCents;

                $rows[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'unit_cents' => $unitCents,
                    'line_cents' => $lineCents,
                ];
            }

            $discountCents = (int) round($subtotalCents * $discountType->rate() / 100);
            $totalCents = max(0, $subtotalCents - $discountCents);

            $tenderedCents = $paymentMethod->isCash()
                ? Money::cents($amountTendered)
                : ($amountTendered === null || $amountTendered === '' ? $totalCents : Money::cents($amountTendered));

            if ($paymentMethod->isCash() && $tenderedCents < $totalCents) {
                throw ValidationException::withMessages([
                    'payment' => 'Cash tendered is less than the total due.',
                ]);
            }

            $sale = Sale::create([
                'user_id' => $cashier->id,
                'shift_id' => $shift?->id,
                'subtotal' => Money::decimal($subtotalCents),
                'discount_type' => $discountType,
                'discount_rate' => $discountType->rate(),
                'discount_amount' => Money::decimal($discountCents),
                'total' => Money::decimal($totalCents),
                'amount_tendered' => Money::decimal($tenderedCents),
                'change_due' => Money::decimal($tenderedCents - $totalCents),
                'payment_method' => $paymentMethod,
                'channel' => SalesChannel::Pos,
                'delivery_fee' => Money::decimal(0),
                'status' => SaleStatus::Completed,
                'customer_name' => $discountType->requiresCustomer() ? $customerName : null,
                'discount_id_number' => $discountType->requiresCustomer() ? $discountIdNumber : null,
                'notes' => $notes,
            ]);

            $sale->reference_no = 'DMS-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT);
            $sale->save();

            foreach ($rows as $row) {
                /** @var Product $product */
                $product = $row['product'];

                $sale->items()->create([
                    'product_id' => $product->id,
                    'name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'unit_price' => Money::decimal($row['unit_cents']),
                    'qty' => $row['qty'],
                    'line_total' => Money::decimal($row['line_cents']),
                ]);

                if ($product->track_stock) {
                    $this->stock->record(
                        $product,
                        StockMovementType::Sale,
                        -$row['qty'],
                        $cashier,
                        "Sale {$sale->reference_no}",
                        'sale',
                        $sale->id,
                    );
                }
            }

            return $sale->load('items');
        }, 3);

        PosCatalog::flush();

        return $completedSale;
    }

    /**
     * Merge duplicate lines and drop invalid quantities.
     *
     * @param  array<int, array{product_id: int|string, qty: int|string}>  $lines
     * @return array<int, int>
     */
    private function normalizeLines(array $lines): array
    {
        $quantities = [];

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $qty = (int) ($line['qty'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $quantities[$productId] = ($quantities[$productId] ?? 0) + $qty;
        }

        return $quantities;
    }
}
