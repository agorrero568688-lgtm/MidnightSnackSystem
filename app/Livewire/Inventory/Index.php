<?php

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use App\Support\Money;
use App\Support\PosCatalog;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public string $tab = 'levels';

    public string $productId = '';

    public string $mode = 'in';

    public string $qty = '';

    public string $note = '';

    public string $search = '';

    public string $logType = 'all';

    public function updatedTab(): void
    {
        $this->resetErrorBag();
    }

    public function selectProduct(int $id, string $mode = 'in'): void
    {
        $this->productId = (string) $id;
        $this->mode = $mode;
        $this->qty = '';
        $this->note = '';
        $this->tab = 'levels';
    }

    public function saveMovement(StockService $stock): void
    {
        $validated = $this->validate([
            'productId' => ['required', 'integer', Rule::exists('products', 'id')],
            'mode' => ['required', Rule::in(['in', 'adjust', 'waste'])],
            'qty' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($validated['productId']);
        $qty = (int) $validated['qty'];

        if ($validated['mode'] !== 'adjust' && $qty < 1) {
            $this->addError('qty', 'Enter a quantity of at least 1.');

            return;
        }

        if (! $product->track_stock) {
            $this->addError('qty', "\"{$product->name}\" is not set to track stock. Enable it on the product first.");

            return;
        }

        $message = match ($validated['mode']) {
            'in' => tap("Received {$qty} × {$product->name}.", fn () => $stock->receive($product, $qty, auth()->user(), $validated['note'] ?: null)),
            'adjust' => tap("{$product->name} count set to {$qty}.", fn () => $stock->adjustTo($product, $qty, auth()->user(), $validated['note'] ?: null)),
            'waste' => tap("Recorded {$qty} × {$product->name} as wastage.", fn () => $stock->wastage($product, $qty, auth()->user(), $validated['note'] ?: null)),
        };

        $this->reset('qty', 'note');
        PosCatalog::flush();
        session()->flash('status', $message);
    }

    public function render()
    {
        $levels = Product::query()
            ->with('category')
            ->withMax('movements', 'created_at')
            ->orderBy('name');

        if (trim($this->search) !== '') {
            $term = '%'.mb_strtolower(trim($this->search)).'%';
            $levels->where(function ($builder) use ($term) {
                $builder->whereRaw('lower(name) like ?', [$term])
                    ->orWhereRaw('lower(sku) like ?', [$term]);
            });
        }

        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->latest()
            ->limit(60);

        if ($this->logType !== 'all') {
            $movements->where('type', $this->logType);
        }

        $products = Product::query()->orderBy('name')->get();

        return view('livewire.inventory.index', [
            'levels' => $levels->limit(200)->get(),
            'movements' => $movements->get(),
            'products' => $products,
            'selectedProduct' => $this->productId ? $products->firstWhere('id', (int) $this->productId) : null,
            'trackedCount' => Product::query()->where('track_stock', true)->count(),
            'lowCount' => Product::query()->active()->lowStock()->count(),
            'outCount' => Product::query()->active()->where('track_stock', true)->where('stock_qty', '<=', 0)->count(),
            'stockValue' => Product::query()->where('track_stock', true)->get()
                ->sum(fn (Product $product) => Money::cents($product->cost_price) * max(0, $product->stock_qty)),
        ])->layout('layouts.app', ['title' => 'Inventory', 'subtitle' => 'Stock in, counts and movement history']);
    }
}
