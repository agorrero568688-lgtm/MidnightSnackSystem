<?php

namespace App\Livewire\Orders;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Board extends Component
{
    public string $filter = 'active';

    public string $search = '';

    public ?int $selectedId = null;

    public string $discountType = 'none';

    public string $discountName = '';

    public string $discountIdNumber = '';

    public string $staffNotes = '';

    public string $cancelReason = '';

    public bool $showCancel = false;

    public string $flashError = '';

    public string $notice = '';

    public int $lastSeenId = 0;

    public function mount(): void
    {
        $this->lastSeenId = (int) Order::query()
            ->where('status', OrderStatus::Pending->value)
            ->max('id');

        $requested = (int) request('order');

        if ($requested > 0) {
            $this->openOrder($requested);
        }
    }

    /** Called by wire:poll; dispatches a browser alert only when something new arrived. */
    public function pollOrders(): void
    {
        $new = Order::query()
            ->where('status', OrderStatus::Pending->value)
            ->where('id', '>', $this->lastSeenId)
            ->orderBy('id')
            ->get(['id', 'reference_no']);

        if ($new->isEmpty()) {
            return;
        }

        $this->lastSeenId = (int) $new->max('id');

        $this->dispatch(
            'order-alert',
            count: $new->count(),
            reference: $new->last()->reference_no,
        );
    }

    public function setFilter(string $filter): void
    {
        if (in_array($filter, ['active', 'today', 'all', 'completed'], true)) {
            $this->filter = $filter;
        }
    }

    public function openOrder(int $id): void
    {
        $this->selectedId = $id;
        $this->flashError = '';
        $this->notice = '';
        $this->hydrateSelection();
    }

    public function closeOrder(): void
    {
        $this->selectedId = null;
        $this->showCancel = false;
        $this->flashError = '';
    }

    public function confirmOrder(OrderService $orders): void
    {
        $this->run(fn () => $orders->confirm($this->selectedOrder(), auth()->user()), 'Order confirmed. Tell the kitchen.');
    }

    public function startPreparing(OrderService $orders): void
    {
        $this->run(fn () => $orders->startPreparing($this->selectedOrder(), auth()->user()), 'The kitchen is on it.');
    }

    public function markReady(OrderService $orders): void
    {
        $this->run(fn () => $orders->markReady($this->selectedOrder(), auth()->user()), 'Order is ready for pickup or the rider.');
    }

    public function dispatchDelivery(OrderService $orders): void
    {
        $this->run(fn () => $orders->dispatchDelivery($this->selectedOrder(), auth()->user()), 'Rider dispatched.');
    }

    public function markPaid(OrderService $orders): void
    {
        $this->run(fn () => $orders->markPaid($this->selectedOrder(), auth()->user()), 'Payment recorded.');
    }

    public function markRefunded(OrderService $orders): void
    {
        $this->run(fn () => $orders->markRefunded($this->selectedOrder(), auth()->user()), 'Refund recorded.');
    }

    public function completeOrder(OrderService $orders): void
    {
        $this->run(function () use ($orders) {
            $order = $this->selectedOrder();
            $sale = $orders->complete($order, auth()->user());
            $this->notice = "Order {$order->reference_no} closed. Sale {$sale->reference_no} recorded.";
        }, null);
    }

    public function applyDiscount(OrderService $orders): void
    {
        $type = DiscountType::tryFrom($this->discountType) ?? DiscountType::None;

        $this->run(
            fn () => $orders->applyDiscount(
                $this->selectedOrder(),
                auth()->user(),
                $type,
                $this->discountName !== '' ? $this->discountName : null,
                $this->discountIdNumber !== '' ? $this->discountIdNumber : null,
            ),
            $type === DiscountType::None ? 'Discount removed. Total recalculated.' : 'Discount applied. Total recalculated.',
        );
    }

    public function saveNotes(OrderService $orders): void
    {
        $this->run(
            fn () => $orders->saveStaffNotes($this->selectedOrder(), auth()->user(), $this->staffNotes),
            'Staff note saved.',
        );
    }

    public function cancelOrder(OrderService $orders): void
    {
        $validated = $this->validate([
            'cancelReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $this->run(
            fn () => $orders->cancel($this->selectedOrder(), auth()->user(), $validated['cancelReason']),
            'Order cancelled and reserved stock returned.',
        );

        $this->showCancel = false;
        $this->cancelReason = '';
    }

    public function render()
    {
        $statusCounts = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $awaitingPayment = Order::query()
            ->where('payment_status', PaymentStatus::AwaitingVerification->value)
            ->count();

        $todayOrders = Order::query()
            ->where('status', OrderStatus::Completed->value)
            ->whereDate('completed_at', today());

        $todayCompleted = (clone $todayOrders)->count();
        $todayRevenue = Money::format((clone $todayOrders)->sum('total'));

        $query = Order::query()->withCount('items');

        match ($this->filter) {
            'today' => $query->whereDate('placed_at', today())->latest('placed_at'),
            'all' => $query->latest('placed_at'),
            'completed' => $query->where('status', OrderStatus::Completed->value)->latest('completed_at'),
            default => $query->whereIn('status', OrderStatus::activeValues())
                ->orderByRaw("case when status = 'pending' then 0 when status = 'confirmed' then 1 else 2 end")
                ->orderBy('placed_at'),
        };

        if (trim($this->search) !== '') {
            $term = '%'.mb_strtolower(trim($this->search)).'%';
            // Stored phones are digit-only, so search by the digit form too.
            $phoneTerm = '%'.preg_replace('/[^0-9]/', '', trim($this->search)).'%';

            $query->where(function ($builder) use ($term, $phoneTerm) {
                $builder->whereRaw('lower(reference_no) like ?', [$term])
                    ->orWhereRaw('lower(customer_name) like ?', [$term])
                    ->orWhereRaw('lower(customer_phone) like ?', [$phoneTerm]);
            });
        }

        /** @var Collection<int, Order> $orders */
        $orders = $query->limit(100)->get();

        $columns = collect(OrderStatus::boardColumns())
            ->map(fn (OrderStatus $status) => [
                'status' => $status,
                'orders' => $orders->where('status', $status)->values(),
            ])
            ->all();

        $selected = null;

        if ($this->selectedId) {
            $selected = Order::query()
                ->with(['items', 'sale', 'confirmedBy', 'completedBy', 'cancelledBy', 'paymentVerifiedBy'])
                ->find($this->selectedId);
        }

        return view('livewire.orders.board', [
            'orders' => $orders,
            'columns' => $columns,
            'selected' => $selected,
            'pendingCount' => (int) ($statusCounts[OrderStatus::Pending->value] ?? 0),
            'activeCount' => (int) $statusCounts->only(OrderStatus::activeValues())->sum(),
            'awaitingPayment' => $awaitingPayment,
            'todayCompleted' => $todayCompleted,
            'todayRevenue' => $todayRevenue,
            'discountOptions' => DiscountType::options(),
        ])->layout('layouts.app', [
            'title' => 'Online orders',
            'subtitle' => 'Confirm, cook and hand off online orders',
        ]);
    }

    private function selectedOrder(): Order
    {
        return Order::query()->findOrFail($this->selectedId);
    }

    private function hydrateSelection(): void
    {
        $order = $this->selectedId
            ? Order::query()->with(['items', 'sale', 'paymentVerifiedBy'])->find($this->selectedId)
            : null;

        if (! $order) {
            $this->selectedId = null;

            return;
        }

        $this->discountType = $order->discount_type->value;
        $this->discountName = (string) $order->customer_name;
        $this->discountIdNumber = (string) $order->discount_id_number;
        $this->staffNotes = (string) $order->staff_notes;
        $this->cancelReason = '';
        $this->showCancel = false;
    }

    private function run(\Closure $action, ?string $success): void
    {
        $this->flashError = '';

        try {
            $action();
        } catch (ValidationException $exception) {
            $this->flashError = (string) (collect($exception->errors())->flatten()->first() ?? 'Could not update the order.');

            return;
        }

        if ($success) {
            $this->notice = $success;
        }

        $this->hydrateSelection();
    }
}
