<form wire:submit="save" class="space-y-5">
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Product details</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Name</label>
                        <input wire:model="name" type="text" class="input" placeholder="e.g. Piattos Cheese 40g">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">SKU</label>
                        <input wire:model="sku" type="text" class="input font-mono uppercase" placeholder="PIATTOS-CHS">
                        @error('sku') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Barcode</label>
                        <input wire:model="barcode" type="text" class="input font-mono" placeholder="4800016641234">
                        @error('barcode') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-[11px] text-slate-500">Scan the item to fill this automatically.</p>
                    </div>

                    <div>
                        <label class="label">Category</label>
                        <select wire:model="category_id" class="input">
                            <option value="">Uncategorised</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Selling unit</label>
                        <input wire:model="unit" type="text" class="input" placeholder="pc, pack, bottle">
                        @error('unit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Pricing &amp; stock</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Cost price</label>
                        <div class="relative">
                            <span class="absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400">₱</span>
                            <input wire:model="cost_price" type="number" step="0.01" min="0" class="input pl-7">
                        </div>
                        @error('cost_price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Selling price</label>
                        <div class="relative">
                            <span class="absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400">₱</span>
                            <input wire:model="selling_price" type="number" step="0.01" min="0" class="input pl-7">
                        </div>
                        @error('selling_price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Low stock threshold</label>
                        <input wire:model="low_stock_threshold" type="number" min="0" class="input">
                        @error('low_stock_threshold') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">{{ $editing ? 'Stock on hand' : 'Opening stock' }}</label>
                        <input wire:model="stock_qty" type="number" min="0" class="input disabled:bg-slate-100"
                            @disabled($editing) placeholder="0">
                        @if ($editing)
                            <p class="mt-1 text-[11px] text-slate-500">Use <a href="{{ route('inventory.index') }}" class="font-semibold text-amber-600">Inventory</a> to adjust stock — every change is logged.</p>
                        @endif
                        @error('stock_qty') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Availability</h2></div>
                <div class="card-body space-y-4">
                    <label class="flex items-start gap-3">
                        <input wire:model="is_active" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500">
                        <span>
                            <span class="block text-sm font-medium">Show at the till</span>
                            <span class="block text-xs text-slate-500">Hidden products stay in reports but can't be sold.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input wire:model="track_stock" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500">
                        <span>
                            <span class="block text-sm font-medium">Track stock</span>
                            <span class="block text-xs text-slate-500">Turn off for items sold without a stock count.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="card card-body space-y-2 text-sm text-slate-600">
                <p class="font-semibold text-slate-800">Tips</p>
                <p>· Keep SKUs short and unique — cashiers can type them at the till.</p>
                <p>· Barcodes must be unique. Leave blank if the item has none.</p>
                <p>· Price is stored exactly; totals always use the database price.</p>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove>{{ $editing ? 'Save changes' : 'Create product' }}</span>
            <span wire:loading>Saving…</span>
        </button>
    </div>
</form>
