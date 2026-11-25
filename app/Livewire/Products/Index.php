<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Support\PosCatalog;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = 'all';

    #[Url]
    public string $availability = 'all';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function toggleActive(Product $product): void
    {
        $product->update(['is_active' => ! $product->is_active]);
        PosCatalog::flush();

        session()->flash('status', $product->is_active
            ? "\"{$product->name}\" is now available on the cashier screen."
            : "\"{$product->name}\" was hidden from the cashier screen.");
    }

    public function toggleAvailable(Product $product): void
    {
        $product->update(['is_available' => ! $product->is_available]);
        PosCatalog::flush();

        session()->flash('status', $product->is_available
            ? "\"{$product->name}\" is available again."
            : "\"{$product->name}\" is marked as sold out.");
    }

    public function delete(Product $product): void
    {
        $name = $product->name;
        $product->delete();
        PosCatalog::flush();

        session()->flash('status', "\"{$name}\" was deleted. Past sales keep their snapshot.");
    }

    public function render()
    {
        $query = Product::query()->with('category')->orderBy('name');

        if (trim($this->search) !== '') {
            $term = '%'.mb_strtolower(trim($this->search)).'%';
            $query->where(function ($builder) use ($term) {
                $builder->whereRaw('lower(name) like ?', [$term])
                    ->orWhereRaw('lower(sku) like ?', [$term])
                    ->orWhere('barcode', 'like', $term);
            });
        }

        if ($this->category !== 'all') {
            $query->where('category_id', $this->category);
        }

        if ($this->availability === 'available') {
            $query->where('is_available', true);
        }

        if ($this->availability === 'unavailable') {
            $query->where('is_available', false);
        }

        return view('livewire.products.index', [
            'products' => $query->paginate(15),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'totalActive' => Product::query()->where('is_active', true)->count(),
            'totalUnavailable' => Product::query()->active()->where('is_available', false)->count(),
        ])->layout('layouts.app', ['title' => 'Products', 'subtitle' => 'Catalogue and pricing']);
    }
}
