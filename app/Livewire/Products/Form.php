<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Support\PosCatalog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $name = '';

    public string $description = '';

    public string $sku = '';

    public ?string $barcode = null;

    public string $unit = 'serving';

    public $category_id = '';

    public $cost_price = '0.00';

    public $selling_price = '0.00';

    public bool $is_available = true;

    public bool $is_active = true;

    public bool $is_online = true;

    public string $sort_order = '0';

    public $image = null;

    public ?string $existingImagePath = null;

    /** @var array<int, array{id: int, name: string}> */
    public array $categories = [];

    public function mount(?Product $product = null): void
    {
        $this->categories = Category::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name])
            ->all();

        if ($product && $product->exists) {
            $this->product = $product;
            $this->fill([
                'name' => $product->name,
                'description' => (string) $product->description,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'unit' => $product->unit,
                'category_id' => $product->category_id ?? '',
                'cost_price' => $product->cost_price,
                'selling_price' => $product->selling_price,
                'is_available' => $product->is_available,
                'is_active' => $product->is_active,
                'is_online' => $product->is_online,
                'sort_order' => (string) $product->sort_order,
            ]);

            $this->existingImagePath = $product->image_path;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($this->product?->id)],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->ignore($this->product?->id)],
            'unit' => ['required', 'string', 'max:20'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'is_available' => ['boolean'],
            'is_active' => ['boolean'],
            'is_online' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'image.image' => 'The menu photo must be a valid image.',
            'image.mimes' => 'The menu photo must be a JPG, PNG or WebP image.',
            'image.max' => 'The menu photo must be 10 MB or smaller.',
        ]);

        $imagePath = $this->existingImagePath;
        $photoDisk = (new Product)->photoDisk();

        if ($this->image) {
            if ($imagePath) {
                Storage::disk($photoDisk)->delete($imagePath);
            }

            $imagePath = $this->image->store('products', $photoDisk);
        }

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image_path' => $imagePath,
            'sku' => mb_strtoupper($validated['sku']),
            'barcode' => $validated['barcode'] ?? null,
            'unit' => $validated['unit'],
            'category_id' => $validated['category_id'] ?: null,
            'cost_price' => $validated['cost_price'],
            'selling_price' => $validated['selling_price'],
            'is_available' => $this->is_available,
            'is_active' => $this->is_active,
            'is_online' => $this->is_online,
            'sort_order' => (int) $this->sort_order,
        ];

        if ($this->product) {
            $this->product->update($payload);
            $message = "\"{$this->product->name}\" was updated.";
        } else {
            $product = Product::create($payload);

            $message = "\"{$product->name}\" was added to the catalogue.";
        }

        PosCatalog::flush();

        $this->image = null;
        session()->flash('status', $message);

        $this->redirect(route('products.index'));
    }

    public function render()
    {
        return view('livewire.products.form', [
            'editing' => $this->product !== null,
        ])->layout('layouts.app', [
            'title' => $this->product ? 'Edit product' : 'New product',
            'subtitle' => $this->product ? $this->product->name : 'Add an item to the catalogue',
        ]);
    }
}
