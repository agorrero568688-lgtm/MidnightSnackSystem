<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Support\GcashQr;
use App\Support\Money;
use Illuminate\Support\Collection;

class StorefrontService
{
    /**
     * Everything the public menu, checkout and cart need in one pass.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->online()
            ->with('category:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $sections = $categories
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'products' => $products->where('category_id', $category->id)->values(),
            ])
            ->filter(fn (array $section) => $section['products']->isNotEmpty())
            ->values();

        $uncategorised = $products->whereNull('category_id')->values();

        if ($uncategorised->isNotEmpty()) {
            $sections->push([
                'id' => 0,
                'name' => 'More from the kitchen',
                'products' => $uncategorised,
            ]);
        }

        $cartProducts = [];

        foreach ($products as $product) {
            $cartProducts[$product->id] = [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category?->name,
                'price_cents' => Money::cents($product->selling_price),
                'unit' => $product->unit,
                'image' => $product->image_src,
                'description' => $product->description,
                'available' => $product->is_available,
                'max_qty' => 20,
            ];
        }

        $orderingOpen = $this->orderingOpen();

        return [
            'sections' => $sections,
            'products' => $cartProducts,
            'orderingOpen' => $orderingOpen,
            'bootstrap' => [
                'products' => $cartProducts,
                'config' => $this->storeConfig(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function storeConfig(): array
    {
        $freeBarangays = array_values(array_filter(array_map(
            'trim',
            explode("\n", (string) Setting::get('delivery_free_barangays', '')),
        )));

        return [
            'ordering_open' => $this->orderingOpen(),
            'store_name' => Setting::get('store_name'),
            'store_address' => Setting::get('store_address'),
            'store_phone' => Setting::get('store_phone'),
            'store_hours' => Setting::get('store_hours'),
            'delivery_enabled' => Setting::bool('delivery_enabled'),
            'pickup_enabled' => Setting::bool('pickup_enabled'),
            'delivery_fee_cents' => Money::cents(Setting::get('delivery_fee', '0')),
            'delivery_free_barangays' => $freeBarangays,
            'delivery_free_barangays_text' => $freeBarangays !== [] ? implode(', ', $freeBarangays) : '',
            'free_delivery_threshold_cents' => Money::cents(Setting::get('free_delivery_threshold', '0')),
            'minimum_order_cents' => Money::cents(Setting::get('minimum_order_total', '0')),
            'gcash_name' => Setting::get('gcash_name'),
            'gcash_number' => Setting::get('gcash_number'),
            'gcash_qr_url' => $this->gcashQrUrl(),
            'place_order_url' => route('orders.store'),
            'checkout_url' => route('orders.checkout'),
            'menu_url' => route('storefront'),
        ];
    }

    public function orderingOpen(): bool
    {
        return Setting::bool('online_ordering_enabled')
            && ! Setting::bool('orders_paused')
            && (Setting::bool('delivery_enabled') || Setting::bool('pickup_enabled'));
    }

    public function gcashQrUrl(): ?string
    {
        return GcashQr::url();
    }

    /** @return Collection<int, Product> */
    public function productsForCheckout(): Collection
    {
        return Product::query()->online()->orderBy('name')->get();
    }
}
