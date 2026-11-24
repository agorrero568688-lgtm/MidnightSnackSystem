<form wire:submit="save" class="space-y-5">
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Product details</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Name</label>
                        <input wire:model="name" type="text" class="input" placeholder="e.g. Chicken Lomi">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label">Description <span class="text-slate-400 normal-case">(shown online)</span></label>
                        <textarea wire:model="description" rows="2" maxlength="500" class="input"
                            placeholder="Crispy, saucy and cooked to order."></textarea>
                        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">SKU</label>
                        <input wire:model="sku" type="text" class="input font-mono uppercase" placeholder="LOMI-CHICKEN">
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
                        <input wire:model="unit" type="text" class="input" placeholder="serving, plate, pc">
                        @error('unit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Pricing</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Cost price <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                        <div class="relative">
                            <span class="absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400">₱</span>
                            <input wire:model="cost_price" type="number" step="0.01" min="0" class="input pl-7">
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Leave at zero if costing lives on ingredients.</p>
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
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Availability</h2></div>
                <div class="card-body space-y-4">
                    <label class="flex items-start gap-3">
                        <input wire:model="is_available" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500">
                        <span>
                            <span class="block text-sm font-medium">Available for sale</span>
                            <span class="block text-xs text-slate-500">Turn off when the kitchen runs out. The item stays listed but shows as sold out.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input wire:model="is_active" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500">
                        <span>
                            <span class="block text-sm font-medium">Show at the till</span>
                            <span class="block text-xs text-slate-500">Hidden products stay in reports but can't be sold.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input wire:model="is_online" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500">
                        <span>
                            <span class="block text-sm font-medium">Show on the online menu</span>
                            <span class="block text-xs text-slate-500">Customers can order this item from the public storefront.</span>
                        </span>
                    </label>

                    <div>
                        <label class="label">Menu order</label>
                        <input wire:model="sort_order" type="number" min="0" max="9999" class="input input-sm">
                        <p class="mt-1 text-[11px] text-slate-500">Lower numbers appear first.</p>
                        @error('sort_order') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Menu photo</h2></div>
                <div class="card-body space-y-3">
                    @if ($image)
                        <img src="{{ $image->temporaryUrl() }}" alt="New product photo" class="h-40 w-full rounded-xl object-cover ring-1 ring-slate-200">
                    @elseif ($existingImagePath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingImagePath) }}" alt="{{ $name }}" class="h-40 w-full rounded-xl object-cover ring-1 ring-slate-200">
                    @else
                        <div class="flex h-40 w-full items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-xs text-slate-400">
                            No photo yet
                        </div>
                    @endif

                    <input wire:model="image" type="file" accept="image/*" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700">
                    <p class="text-[11px] text-slate-500">PNG or JPG, up to 2 MB. Without a photo the menu uses a coloured placeholder.</p>
                    @error('image') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
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
