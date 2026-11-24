<?php

namespace App\Livewire\Pos;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Services\CheckoutService;
use App\Services\ShiftService;
use App\Support\Money;
use App\Support\PosCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Terminal extends Component
{
    /** @param array<string, mixed>|null $payload */
    public function checkout(?array $payload, CheckoutService $checkout, ShiftService $shifts): array
    {
        $user = Auth::user();

        if (Setting::get('require_shift') === '1' && ! $user->currentShift()) {
            return ['ok' => false, 'message' => 'Open a shift before ringing up sales.'];
        }

        $payload ??= [];

        try {
            $sale = $checkout->checkout(
                cashier: $user,
                shift: $user->currentShift(),
                lines: $payload['items'] ?? [],
                paymentMethod: PaymentMethod::tryFrom((string) ($payload['payment_method'] ?? 'cash')) ?? PaymentMethod::Cash,
                amountTendered: $payload['amount_tendered'] ?? null,
                discountType: DiscountType::tryFrom((string) ($payload['discount_type'] ?? 'none')) ?? DiscountType::None,
                customerName: $payload['customer_name'] ?? null,
                discountIdNumber: $payload['discount_id_number'] ?? null,
                notes: $payload['notes'] ?? null,
            );
        } catch (ValidationException $exception) {
            return [
                'ok' => false,
                'message' => collect($exception->errors())->flatten()->first() ?? 'Could not complete the sale.',
            ];
        }

        $width = Setting::get('receipt_width', '58');

        return [
            'ok' => true,
            'sale' => [
                'id' => $sale->id,
                'reference_no' => $sale->reference_no,
                'total_cents' => Money::cents($sale->total),
                'tendered_cents' => Money::cents($sale->amount_tendered),
                'change_cents' => Money::cents($sale->change_due),
                'payment_method' => $sale->payment_method->value,
                'item_count' => $sale->itemCount(),
                'receipt_url' => route('sales.receipt', ['sale' => $sale, 'print' => 1, 'width' => $width]),
            ],
        ];
    }

    public function openShift(string|int|float|null $openingCash, ShiftService $shifts): array
    {
        try {
            $shift = $shifts->open(Auth::user(), $openingCash);
        } catch (ValidationException $exception) {
            return [
                'ok' => false,
                'message' => collect($exception->errors())->flatten()->first() ?? 'Could not open the shift.',
            ];
        }

        return ['ok' => true, 'shift' => $this->shiftPayload($shift)];
    }

    public function closeShift(string|int|float|null $countedCash, ShiftService $shifts): array
    {
        $shift = Auth::user()->currentShift();

        if (! $shift) {
            return ['ok' => false, 'message' => 'There is no open shift to close.'];
        }

        try {
            $shifts->close($shift, $countedCash);
        } catch (ValidationException $exception) {
            return [
                'ok' => false,
                'message' => collect($exception->errors())->flatten()->first() ?? 'Could not close the shift.',
            ];
        }

        return ['ok' => true];
    }

    public function render()
    {
        $user = Auth::user();
        $shift = $user->currentShift();

        $catalog = Cache::remember(PosCatalog::KEY, now()->addSeconds(45), function () {
            return Product::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'price_cents' => Money::cents($product->selling_price),
                    'is_available' => $product->is_available,
                    'category_id' => $product->category_id,
                    'unit' => $product->unit,
                ])
                ->all();
        });

        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name])
            ->all();

        $bootstrap = [
            'catalog' => $catalog,
            'categories' => $categories,
            'shift' => $shift ? $this->shiftPayload($shift) : null,
            'config' => [
                'store_name' => Setting::get('store_name'),
                'receipt_width' => Setting::get('receipt_width', '58'),
                'require_shift' => Setting::get('require_shift') === '1',
            ],
        ];

        return view('livewire.pos.terminal', [
            'bootstrap' => $bootstrap,
            'user' => $user,
            'shift' => $shift,
            'isAdmin' => $user->isAdmin(),
        ])->layout('layouts.pos', ['title' => 'Till']);
    }

    private function shiftPayload(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'opened_at' => $shift->opened_at->format('M d, Y h:i A'),
            'opening_cents' => Money::cents($shift->opening_cash),
            'expected_cents' => $shift->expectedCashCents(),
            'cashier' => $shift->user?->name ?? Auth::user()->name,
        ];
    }
}
