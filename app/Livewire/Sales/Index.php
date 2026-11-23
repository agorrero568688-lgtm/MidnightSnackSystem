<?php

namespace App\Livewire\Sales;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Services\SaleService;
use App\Support\Money;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $from = '';

    public string $to = '';

    public string $status = 'all';

    public string $payment = 'all';

    public string $search = '';

    public ?int $voidingId = null;

    public string $voidReason = '';

    public function mount(): void
    {
        $this->from = today()->subDays(6)->toDateString();
        $this->to = today()->toDateString();
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function today(): void
    {
        $this->from = today()->toDateString();
        $this->to = today()->toDateString();
        $this->resetPage();
    }

    public function startVoid(int $id): void
    {
        $this->voidingId = $id;
        $this->voidReason = '';
        $this->resetErrorBag();
    }

    public function cancelVoid(): void
    {
        $this->voidingId = null;
        $this->voidReason = '';
    }

    public function confirmVoid(SaleService $sales): void
    {
        $validated = $this->validate([
            'voidReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $sale = Sale::findOrFail($this->voidingId);

        try {
            $sales->void($sale, auth()->user(), $validated['voidReason']);
        } catch (ValidationException $exception) {
            $this->addError('voidReason', collect($exception->errors())->flatten()->first());

            return;
        }

        $this->cancelVoid();
        session()->flash('status', "Sale {$sale->reference_no} voided and stock returned.");
    }

    public function render()
    {
        $query = Sale::query()
            ->with('user')
            ->withSum('items', 'qty')
            ->when($this->from, fn ($builder) => $builder->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($builder) => $builder->whereDate('created_at', '<=', $this->to))
            ->when($this->status !== 'all', fn ($builder) => $builder->where('status', $this->status))
            ->when($this->payment !== 'all', fn ($builder) => $builder->where('payment_method', $this->payment))
            ->when(trim($this->search) !== '', function ($builder) {
                $term = '%'.mb_strtolower(trim($this->search)).'%';
                $builder->where(function ($inner) use ($term) {
                    $inner->whereRaw('lower(reference_no) like ?', [$term])
                        ->orWhereRaw('lower(customer_name) like ?', [$term]);
                });
            })
            ->latest();

        $summary = Sale::query()
            ->where('status', SaleStatus::Completed->value)
            ->when($this->from, fn ($builder) => $builder->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($builder) => $builder->whereDate('created_at', '<=', $this->to));

        $totals = (clone $summary)->selectRaw('coalesce(sum(total), 0) as total, coalesce(sum(discount_amount), 0) as discounts, count(*) as count')->first();

        return view('livewire.sales.index', [
            'sales' => $query->paginate(20),
            'summaryTotal' => Money::format($totals->total ?? 0),
            'summaryDiscounts' => Money::format($totals->discounts ?? 0),
            'summaryCount' => (int) ($totals->count ?? 0),
            'payments' => PaymentMethod::options(),
            'voidingSale' => $this->voidingId ? Sale::with('items')->find($this->voidingId) : null,
        ])->layout('layouts.app', ['title' => 'Sales', 'subtitle' => 'Receipt history, voids and reprints']);
    }
}
