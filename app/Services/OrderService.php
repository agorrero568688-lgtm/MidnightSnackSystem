<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\OrderPaymentMethod;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\SalesChannel;
use App\Enums\SaleStatus;
use App\Jobs\SendAbandonedOrderReminderJob;
use App\Jobs\SendFeedbackSmsJob;
use App\Jobs\SendOrderReadySmsJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use App\Support\Money;
use App\Support\PosCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly OrderNotifier $notifier,
    ) {}

    /**
     * Place a public order.
     *
     * Prices are re-read from the database so a client can never choose its
     * own price. Ingredient stock is tracked separately in the inventory
     * module and is not reserved here. GCash orders must arrive with a
     * screenshot of the payment receipt, stored on the private disk.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function place(array $data): Order
    {
        if (! Setting::bool('online_ordering_enabled')) {
            throw ValidationException::withMessages(['order' => 'Online ordering is closed right now.']);
        }

        if (Setting::bool('orders_paused')) {
            throw ValidationException::withMessages(['order' => 'We have paused new orders. Please try again in a little while.']);
        }

        $type = OrderType::tryFrom((string) ($data['order_type'] ?? ''))
            ?? throw ValidationException::withMessages(['order_type' => 'Choose delivery or pickup.']);

        if ($type === OrderType::Delivery && ! Setting::bool('delivery_enabled')) {
            throw ValidationException::withMessages(['order_type' => 'Delivery is not available right now.']);
        }

        if ($type === OrderType::Pickup && ! Setting::bool('pickup_enabled')) {
            throw ValidationException::withMessages(['order_type' => 'Pickup is not available right now.']);
        }

        $method = OrderPaymentMethod::tryFrom((string) ($data['payment_method'] ?? ''));

        if (! $method || ! in_array($method, OrderPaymentMethod::forType($type), true)) {
            throw ValidationException::withMessages(['payment_method' => 'Choose a valid payment method for this order.']);
        }

        $proof = $data['payment_proof'] ?? null;

        if ($method->isGcash() && ! $proof instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'payment_proof' => 'Upload a screenshot of your GCash receipt to place the order.',
            ]);
        }

        $lines = $this->normalizeLines($data['items'] ?? []);

        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'Your cart is empty.']);
        }

        $quantities = [];

        foreach ($lines as $line) {
            $quantities[$line['product_id']] = ($quantities[$line['product_id']] ?? 0) + $line['qty'];
        }

        $proofPath = null;

        if ($method->isGcash() && $proof instanceof UploadedFile) {
            $proofPath = $proof->store('order-proofs', 'local');
        }

        try {
            $order = DB::transaction(function () use ($data, $type, $method, $lines, $quantities, $proofPath) {
                /** @var Collection<int, Product> $products */
                $products = Product::query()
                    ->whereIn('id', array_keys($quantities))
                    ->orderBy('id')
                    ->get()
                    ->keyBy('id');

                foreach ($quantities as $productId => $qty) {
                    $product = $products->get($productId);

                    if (! $product) {
                        throw ValidationException::withMessages(['items' => 'One of the items is no longer available.']);
                    }

                    if (! $product->is_active || ! $product->is_online || ! $product->is_available) {
                        throw ValidationException::withMessages([
                            'items' => "\"{$product->name}\" is not available for online orders right now.",
                        ]);
                    }
                }

                $subtotalCents = 0;
                $rows = [];

                foreach ($lines as $line) {
                    $product = $products->get($line['product_id']);
                    $unitCents = Money::cents($product->selling_price);
                    $lineCents = $unitCents * $line['qty'];
                    $subtotalCents += $lineCents;

                    $rows[] = [
                        'product' => $product,
                        'qty' => $line['qty'],
                        'note' => $line['note'],
                        'unit_cents' => $unitCents,
                        'line_cents' => $lineCents,
                    ];
                }

                $minimumCents = Money::cents(Setting::get('minimum_order_total', '0'));

                if ($minimumCents > 0 && $subtotalCents < $minimumCents) {
                    throw ValidationException::withMessages([
                        'items' => 'The minimum order is '.Money::formatCents($minimumCents).'. Please add a little more.',
                    ]);
                }

                $deliveryCents = 0;

                if ($type === OrderType::Delivery) {
                    $deliveryCents = $this->deliveryIsFree(trim((string) ($data['delivery_address'] ?? '')))
                        ? 0
                        : Money::cents(Setting::get('delivery_fee', '0'));
                    $freeThreshold = Money::cents(Setting::get('free_delivery_threshold', '0'));

                    if ($freeThreshold > 0 && $subtotalCents >= $freeThreshold) {
                        $deliveryCents = 0;
                    }
                }

                $totalCents = $subtotalCents + $deliveryCents;

                $order = Order::create([
                    'public_token' => Str::random(48),
                    'status' => OrderStatus::Pending,
                    'payment_status' => $method->isGcash()
                        ? PaymentStatus::AwaitingVerification
                        : PaymentStatus::Unpaid,
                    'payment_method' => $method,
                    'order_type' => $type,
                    'customer_name' => trim((string) $data['customer_name']),
                    'customer_phone' => $this->normalizePhone(trim((string) $data['customer_phone'])),
                    'customer_email' => filled($data['customer_email'] ?? null) ? trim((string) $data['customer_email']) : null,
                    'delivery_address' => $type === OrderType::Delivery ? trim((string) ($data['delivery_address'] ?? '')) : null,
                    'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                    'subtotal' => Money::decimal($subtotalCents),
                    'delivery_fee' => Money::decimal($deliveryCents),
                    'discount_type' => DiscountType::None,
                    'discount_rate' => 0,
                    'discount_amount' => Money::decimal(0),
                    'total' => Money::decimal($totalCents),
                    'payment_proof_path' => $proofPath,
                    'placed_at' => now(),
                ]);

                $order->reference_no = 'ORD-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
                $order->save();

                foreach ($rows as $row) {
                    /** @var Product $product */
                    $product = $row['product'];

                    $order->items()->create([
                        'product_id' => $product->id,
                        'name_snapshot' => $product->name,
                        'sku_snapshot' => $product->sku,
                        'unit_price' => Money::decimal($row['unit_cents']),
                        'qty' => $row['qty'],
                        'line_total' => Money::decimal($row['line_cents']),
                        'note' => $row['note'],
                    ]);
                }

                return $order->load('items');
            }, 3);
        } catch (\Throwable $e) {
            if ($proofPath !== null) {
                Storage::disk('local')->delete($proofPath);
            }

            throw $e;
        }

        PosCatalog::flush();

        // Alerts run inline on Vercel (QUEUE_CONNECTION=sync). A Telegram
        // outage must never turn a successfully placed order into a 500.
        try {
            $this->notifier->newOrder($order);
        } catch (\Throwable $e) {
            Log::warning('New-order alert failed', [
                'order' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }

        return $order;
    }

    public function confirm(Order $order, User $staff): Order
    {
        return $this->transition($order, $staff, OrderStatus::Pending, [
            'status' => OrderStatus::Confirmed,
            'confirmed_by' => $staff->id,
            'confirmed_at' => now(),
        ], 'Only new orders can be confirmed.');
    }

    public function startPreparing(Order $order, User $staff): Order
    {
        return $this->transition($order, $staff, OrderStatus::Confirmed, [
            'status' => OrderStatus::Preparing,
        ], 'Confirm the order before starting preparation.');
    }

    public function markReady(Order $order, User $staff): Order
    {
        $locked = $this->transition($order, $staff, OrderStatus::Preparing, [
            'status' => OrderStatus::Ready,
            'ready_at' => now(),
        ], 'Start preparing the order first.');

        SendOrderReadySmsJob::dispatch($order->id);

        return $locked->load('items');
    }

    public function dispatchDelivery(Order $order, User $staff): Order
    {
        $locked = $this->lock($order);

        if (! $locked->isDelivery()) {
            $this->fail('Pickup orders are not dispatched.');
        }

        return $this->transition($order, $staff, OrderStatus::Ready, [
            'status' => OrderStatus::OutForDelivery,
        ], 'Mark the order ready before dispatching a rider.');
    }

    public function markPaid(Order $order, User $staff): Order
    {
        return DB::transaction(function () use ($order, $staff) {
            $locked = $this->lock($order);

            if ($locked->status === OrderStatus::Cancelled) {
                $this->fail('This order was cancelled.');
            }

            if ($locked->payment_status === PaymentStatus::Refunded) {
                $this->fail('This payment was already refunded.');
            }

            $locked->forceFill([
                'payment_status' => PaymentStatus::Paid,
                'paid_at' => $locked->paid_at ?? now(),
                'payment_verified_by' => $staff->id,
            ])->save();

            return $locked->load('items');
        }, 3);
    }

    public function markRefunded(Order $order, User $staff): Order
    {
        return DB::transaction(function () use ($order) {
            $locked = $this->lock($order);

            if ($locked->payment_status !== PaymentStatus::Paid) {
                $this->fail('Only a paid order can be marked refunded.');
            }

            if (! $locked->status->isFinal()) {
                $this->fail('Cancel the order before recording a refund.');
            }

            $locked->forceFill(['payment_status' => PaymentStatus::Refunded])->save();

            return $locked->load('items');
        }, 3);
    }

    /**
     * Close an order by creating the matching sale.
     *
     * The sale copies the line snapshots; product stock is no longer tracked
     * on menu items, so there is nothing to reconcile here.
     *
     * @throws ValidationException
     */
    public function complete(Order $order, User $staff): Sale
    {
        $sale = DB::transaction(function () use ($order, $staff) {
            $locked = $this->lock($order)->load('items');

            if (! in_array($locked->status, [OrderStatus::Ready, OrderStatus::OutForDelivery], true)) {
                $this->fail('The order must be ready before it can be completed.');
            }

            if (! $locked->isPaid()) {
                $this->fail('Record the payment before completing this order.');
            }

            if ($locked->sale_id) {
                $this->fail('This order has already been converted to a sale.');
            }

            $sale = Sale::create([
                'user_id' => $staff->id,
                'shift_id' => $staff->currentShift()?->id,
                'subtotal' => $locked->subtotal,
                'discount_type' => $locked->discount_type,
                'discount_rate' => $locked->discount_rate,
                'discount_amount' => $locked->discount_amount,
                'delivery_fee' => $locked->delivery_fee,
                'total' => $locked->total,
                'amount_tendered' => $locked->total,
                'change_due' => Money::decimal(0),
                'payment_method' => $locked->payment_method->toSaleMethod(),
                'channel' => SalesChannel::Online,
                'status' => SaleStatus::Completed,
                'customer_name' => $locked->customer_name,
                'discount_id_number' => $locked->discount_id_number,
                'notes' => $locked->notes,
            ]);

            $sale->reference_no = 'DMS-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT);
            $sale->save();

            foreach ($locked->items as $item) {
                $sale->items()->create([
                    'product_id' => $item->product_id,
                    'name_snapshot' => $item->name_snapshot,
                    'sku_snapshot' => $item->sku_snapshot,
                    'unit_price' => $item->unit_price,
                    'qty' => $item->qty,
                    'line_total' => $item->line_total,
                ]);
            }

            $locked->forceFill([
                'status' => OrderStatus::Completed,
                'completed_by' => $staff->id,
                'completed_at' => now(),
                'delivered_at' => $locked->isDelivery() ? now() : $locked->delivered_at,
                'sale_id' => $sale->id,
            ])->save();

            return $sale->load('items');
        }, 3);

        PosCatalog::flush();

        SendFeedbackSmsJob::dispatch($order->id);

        return $sale;
    }

    /**
     * Cancel an order.
     *
     * @throws ValidationException
     */
    public function cancel(Order $order, User $staff, ?string $reason = null): Order
    {
        $cancelled = DB::transaction(function () use ($order, $staff, $reason) {
            $locked = $this->lock($order)->load('items');

            if ($locked->status->isFinal()) {
                $this->fail('This order is already closed.');
            }

            if ($locked->sale_id) {
                $this->fail('This order has a sale. Void the sale instead.');
            }

            $locked->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancelled_by' => $staff->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save();

            return $locked->load('items');
        }, 3);

        PosCatalog::flush();

        return $cancelled;
    }

    /**
     * Apply a verified senior/PWD discount before payment is recorded.
     *
     * @throws ValidationException
     */
    public function applyDiscount(
        Order $order,
        User $staff,
        DiscountType $type,
        ?string $customerName = null,
        ?string $idNumber = null,
    ): Order {
        return DB::transaction(function () use ($order, $type, $customerName, $idNumber) {
            $locked = $this->lock($order);

            if ($locked->status->isFinal()) {
                $this->fail('This order is already closed.');
            }

            if ($locked->isPaid()) {
                $this->fail('Refund the payment before changing the total.');
            }

            if ($type->requiresCustomer() && (blank($customerName) || blank($idNumber))) {
                $this->fail('Customer name and OSCA/PWD ID number are required for this discount.');
            }

            $subtotalCents = Money::cents($locked->subtotal);
            $discountCents = (int) round($subtotalCents * $type->rate() / 100);
            $totalCents = max(0, $subtotalCents + Money::cents($locked->delivery_fee) - $discountCents);

            $locked->forceFill([
                'discount_type' => $type,
                'discount_rate' => $type->rate(),
                'discount_amount' => Money::decimal($discountCents),
                'total' => Money::decimal($totalCents),
                'customer_name' => filled($customerName) ? trim((string) $customerName) : $locked->customer_name,
                'discount_id_number' => $type->requiresCustomer() ? trim((string) $idNumber) : null,
            ])->save();

            return $locked->load('items');
        }, 3);
    }

    /** Let a customer upload or replace their GCash receipt from the tracking page. */
    public function submitPayment(Order $order, UploadedFile $proof): Order
    {
        $previousPath = $order->payment_proof_path;
        $proofPath = $proof->store('order-proofs', 'local');

        try {
            $updated = DB::transaction(function () use ($order, $proofPath) {
                $locked = $this->lock($order);

                if (! $locked->payment_method->isGcash()) {
                    $this->fail('This order is paid in cash.');
                }

                if ($locked->status->isFinal()) {
                    $this->fail('This order is already closed.');
                }

                if ($locked->isPaid()) {
                    $this->fail('This order is already marked paid.');
                }

                $locked->forceFill([
                    'payment_proof_path' => $proofPath,
                    'payment_status' => PaymentStatus::AwaitingVerification,
                ])->save();

                return $locked;
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($proofPath);

            throw $e;
        }

        if ($previousPath && $previousPath !== $proofPath) {
            Storage::disk('local')->delete($previousPath);
        }

        return $updated;
    }

    public function saveStaffNotes(Order $order, User $staff, ?string $notes): Order
    {
        $notes = filled($notes) ? mb_substr(trim((string) $notes), 0, 1000) : null;

        $order->forceFill([
            'staff_notes' => $notes,
        ])->save();

        return $order;
    }

    /**
     * Send abandoned-order reminders to customers still waiting to be
     * confirmed. Returns the number of reminders queued.
     */
    public function sendAbandonedReminders(): int
    {
        $minutes = max(5, (int) Setting::get('sms_reminder_minutes', '15'));

        $orders = Order::query()
            ->where('status', OrderStatus::Pending->value)
            ->where('placed_at', '<=', now()->subMinutes($minutes))
            ->get();

        foreach ($orders as $order) {
            SendAbandonedOrderReminderJob::dispatch($order->id);
        }

        return $orders->count();
    }

    /**
     * Suggest the products a repeat customer has ordered most. Returns at
     * least two orders before suggesting, so brand-new phones get nothing.
     *
     * @return Collection<int, Product>
     */
    public function suggestionsForPhone(string $phone): Collection
    {
        $phone = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($phone === '') {
            return collect();
        }

        $counts = Order::query()
            ->whereRaw('lower(customer_phone) = ?', [$phone])
            ->get()
            ->flatMap(fn (Order $order) => $order->items->pluck('product_id'))
            ->countBy();

        if ($counts->sum() < 2) {
            return collect();
        }

        $products = Product::query()
            ->whereIn('id', $counts->keys()->all())
            ->where('is_active', true)
            ->where('is_online', true)
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        return $counts
            ->sortByDesc(fn (int $count, int $id): int => $count)
            ->map(fn (int $count, int $id): ?Product => $products[$id] ?? null)
            ->filter()
            ->values()
            ->take(4);
    }

    /**
     * Normalize customer phone digits for storage and lookups.
     */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone) ?? '';
    }

    /**
     * True when the customer's address mentions one of the configured
     * free-delivery barangays (case/punctuation-insensitive).
     */
    private function deliveryIsFree(string $address): bool
    {
        $address = $this->normalizeBarangay($address);

        if ($address === '') {
            return false;
        }

        $free = array_filter(array_map('trim', explode("\n", (string) Setting::get('delivery_free_barangays'))));

        foreach ($free as $barangay) {
            $name = $this->normalizeBarangay($barangay);

            if ($name !== '' && str_contains($address, $name)) {
                return true;
            }
        }

        return false;
    }

    /** Lowercase alphanumerics only — for address/barangay matching. */
    private function normalizeBarangay(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return (string) preg_replace('/[^a-z0-9]+/', '', $value);
    }

    /** @param array<string, mixed> $changes */
    private function transition(Order $order, User $staff, OrderStatus $from, array $changes, string $message): Order
    {
        return DB::transaction(function () use ($order, $from, $changes, $message) {
            $locked = $this->lock($order);

            if ($locked->status !== $from) {
                $this->fail($message);
            }

            $locked->forceFill($changes)->save();

            return $locked->load('items');
        }, 3);
    }

    private function lock(Order $order): Order
    {
        /** @var Order $locked */
        $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

        return $locked;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['order' => $message]);
    }

    /**
     * Merge repeated lines but keep different notes on the same item separate.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{product_id: int, qty: int, note: ?string}>
     */
    private function normalizeLines(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = min(20, (int) ($item['qty'] ?? 0));
            $note = trim((string) ($item['note'] ?? ''));

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $note = $note !== '' ? mb_substr($note, 0, 200) : null;
            $key = $productId.'|'.($note ?? '');

            if (isset($lines[$key])) {
                $lines[$key]['qty'] = min(20, $lines[$key]['qty'] + $qty);
            } else {
                $lines[$key] = ['product_id' => $productId, 'qty' => $qty, 'note' => $note];
            }
        }

        return array_values($lines);
    }
}
