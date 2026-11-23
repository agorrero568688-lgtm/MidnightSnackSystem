<form wire:submit="save" class="space-y-5">
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Store details</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Store name</label>
                        <input wire:model="storeName" type="text" class="input">
                        @error('storeName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Address</label>
                        <input wire:model="storeAddress" type="text" class="input" placeholder="Street, barangay, city">
                        @error('storeAddress') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Phone / contact</label>
                        <input wire:model="storePhone" type="text" class="input" placeholder="0917 000 0000">
                        @error('storePhone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Default low-stock threshold</label>
                        <input wire:model="lowStockDefault" type="number" min="0" class="input">
                        @error('lowStockDefault') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Receipts</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Paper width</label>
                        <select wire:model="receiptWidth" class="input">
                            <option value="58">58 mm thermal</option>
                            <option value="80">80 mm thermal</option>
                        </select>
                        @error('receiptWidth') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Footer message</label>
                        <input wire:model="receiptFooter" type="text" class="input" placeholder="Salamat po! Come again.">
                        @error('receiptFooter') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Till behaviour</h2></div>
                <div class="card-body space-y-4">
                    <label class="flex items-start gap-3">
                        <input wire:model="requireShift" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500">
                        <span>
                            <span class="block text-sm font-medium">Require a shift before selling</span>
                            <span class="block text-xs text-slate-500">Cashiers open a drawer session with their starting cash, and close it with a count.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input wire:model="allowNegativeStock" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500">
                        <span>
                            <span class="block text-sm font-medium">Allow stock to go negative</span>
                            <span class="block text-xs text-slate-500">
                                Off means the till blocks a sale when stock runs out. On lets a miscount be reconciled later.
                            </span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card card-body space-y-2 text-sm text-slate-600">
                <p class="font-semibold text-slate-800">Receipt preview</p>
                <p class="text-xs">Changes apply to new receipts. Reopening an old receipt link re-renders with the current store details.</p>
                <div class="mt-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 font-mono text-[11px] leading-relaxed">
                    <p class="text-center font-bold">{{ $storeName }}</p>
                    @if ($storeAddress) <p class="text-center">{{ $storeAddress }}</p> @endif
                    @if ($storePhone) <p class="text-center">{{ $storePhone }}</p> @endif
                    <p class="my-1 border-t border-dashed border-slate-300"></p>
                    <p>Ref: DMS-000123</p>
                    <p>Cashier: {{ auth()->user()->name }}</p>
                    <p class="my-1 border-t border-dashed border-slate-300"></p>
                    <p class="flex justify-between"><span>2 x Chips</span><span>₱50.00</span></p>
                    <p class="flex justify-between font-bold"><span>TOTAL</span><span>₱50.00</span></p>
                    <p class="my-1 border-t border-dashed border-slate-300"></p>
                    <p class="text-center">{{ $receiptFooter }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove>Save settings</span>
            <span wire:loading>Saving…</span>
        </button>
    </div>
</form>
