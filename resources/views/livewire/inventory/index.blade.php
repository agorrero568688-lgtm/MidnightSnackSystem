<div class="space-y-5">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Ingredients tracked</p>
            <p class="mt-1 text-3xl font-black">{{ $trackedCount }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Low stock</p>
            <p class="mt-1 text-3xl font-black {{ $lowCount > 0 ? 'text-amber-600' : '' }}">{{ $lowCount }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Out of stock</p>
            <p class="mt-1 text-3xl font-black {{ $outCount > 0 ? 'text-rose-600' : '' }}">{{ $outCount }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Stock value (cost)</p>
            <p class="mt-1 text-3xl font-black">{{ \App\Support\Money::formatCents($stockValue) }}</p>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2 text-xs">
            <span class="badge badge-slate">{{ $trackedCount }} active</span>
            <span class="badge {{ $lowCount > 0 ? 'badge-amber' : 'badge-slate' }}">{{ $lowCount }} low</span>
            <span class="badge {{ $outCount > 0 ? 'badge-red' : 'badge-slate' }}">{{ $outCount }} out</span>
        </div>
        <button wire:click="openCreate" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New ingredient
        </button>
    </div>

    @if ($showForm)
        <div class="card">
            <div class="card-header">
                <h2 class="text-sm font-bold">{{ $editingId ? 'Edit ingredient' : 'New ingredient' }}</h2>
                <button wire:click="cancelForm" class="text-xs font-semibold text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
            <form wire:submit="saveIngredient" class="card-body grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="label">Name</label>
                    <input wire:model="name" type="text" class="input" placeholder="e.g. Pork Belly">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Category</label>
                    <select wire:model="category" class="input">
                        @foreach ($categories as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Unit</label>
                    <select wire:model="unit" class="input">
                        @foreach ($units as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('unit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">{{ $editingId ? 'Current quantity' : 'Opening quantity' }}</label>
                    <input wire:model="quantity" type="number" step="0.001" min="0" class="input disabled:bg-slate-100" @disabled($editingId)>
                    @if ($editingId)
                        <p class="mt-1 text-[11px] text-slate-500">Use the movement form to change stock.</p>
                    @endif
                    @error('quantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Reorder level</label>
                    <input wire:model="reorderLevel" type="number" step="0.001" min="0" class="input">
                    @error('reorderLevel') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Cost per unit <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                    <div class="relative">
                        <span class="absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400">₱</span>
                        <input wire:model="costPerUnit" type="number" step="0.01" min="0" class="input pl-7">
                    </div>
                    @error('costPerUnit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-2 pb-2 text-sm text-slate-600">
                        <input wire:model="isActive" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-amber-500">
                        Active in inventory
                    </label>
                </div>

                <div class="flex items-end justify-end gap-2 sm:col-span-2 xl:col-span-4">
                    <button type="button" wire:click="cancelForm" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $editingId ? 'Save changes' : 'Add ingredient' }}</span>
                        <span wire:loading>Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="card h-fit">
            <div class="card-header">
                <h2 class="text-sm font-bold">Record a movement</h2>
            </div>
            <form wire:submit="saveMovement" class="card-body space-y-4">
                <div>
                    <label class="label">Ingredient</label>
                    <select wire:model.live="inventoryId" class="input">
                        <option value="">Choose an ingredient…</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}">{{ $ingredient->name }} · {{ $ingredient->category }}</option>
                        @endforeach
                    </select>
                    @error('inventoryId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Movement</label>
                    <div class="grid grid-cols-3 gap-1.5">
                        @foreach (['in' => 'Stock in', 'adjust' => 'Set count', 'waste' => 'Wastage'] as $value => $label)
                            <button type="button" wire:click="$set('mode', '{{ $value }}')"
                                class="rounded-lg px-2 py-2 text-xs font-semibold transition {{ $mode === $value ? 'bg-amber-400 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="label">{{ $mode === 'adjust' ? 'New stock count' : 'Quantity' }}</label>
                    <input wire:model="qty" type="number" step="0.001" min="0" class="input" placeholder="0.000">
                    @error('qty') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    @if ($selectedInventory)
                        <p class="mt-1 text-[11px] text-slate-500">
                            Current: <span class="font-semibold text-slate-700">{{ $selectedInventory->name }}</span>
                            · {{ $selectedInventory->quantityLabel() }} {{ $selectedInventory->unit }}
                        </p>
                    @endif
                </div>

                <div>
                    <label class="label">Note <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                    <input wire:model="note" type="text" class="input" placeholder="Supplier, invoice no., reason…">
                    @error('note') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save movement</span>
                    <span wire:loading>Saving…</span>
                </button>
            </form>
        </div>

        <div class="card xl:col-span-2">
            <div class="card-header">
                <div class="flex gap-1 rounded-xl bg-slate-100 p-1">
                    <button wire:click="$set('tab', 'levels')"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $tab === 'levels' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Stock levels</button>
                    <button wire:click="$set('tab', 'log')"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $tab === 'log' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Movement log</button>
                </div>

                @if ($tab === 'levels')
                    <div class="flex gap-2">
                        <select wire:model.live="categoryFilter" class="input input-sm w-44">
                            <option value="all">All categories</option>
                            @foreach ($categories as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search ingredients" class="input input-sm w-48">
                    </div>
                @else
                    <select wire:model.live="logType" class="input input-sm w-40">
                        <option value="all">All movements</option>
                        @foreach (\App\Enums\InventoryMovementType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="overflow-x-auto">
                @if ($tab === 'levels')
                    <table class="data-table">
                        <thead>
                            <tr><th>Ingredient</th><th>Category</th><th>Quantity</th><th>Reorder</th><th>Value</th><th>Last movement</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse ($levels as $inventory)
                                <tr wire:key="level-{{ $inventory->id }}">
                                    <td>
                                        <p class="font-semibold">{{ $inventory->name }}</p>
                                        @if (! $inventory->is_active)
                                            <span class="badge badge-slate">Archived</span>
                                        @endif
                                    </td>
                                    <td class="text-slate-600">{{ $inventory->category }}</td>
                                    <td>
                                        @if ($inventory->isOutOfStock())
                                            <span class="badge badge-red">Out</span>
                                        @elseif ($inventory->isLowStock())
                                            <span class="badge badge-amber">{{ $inventory->quantityLabel() }} {{ $inventory->unit }}</span>
                                        @else
                                            <span class="badge badge-green">{{ $inventory->quantityLabel() }} {{ $inventory->unit }}</span>
                                        @endif
                                    </td>
                                    <td class="text-slate-600">{{ $inventory->reorderLabel() }} {{ $inventory->unit }}</td>
                                    <td>{{ \App\Support\Money::formatCents($inventory->stockValueCents()) }}</td>
                                    <td class="text-xs text-slate-500">
                                        {{ $inventory->movements_max_created_at ? \Illuminate\Support\Carbon::parse($inventory->movements_max_created_at)->diffForHumans() : 'Never' }}
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button wire:click="selectInventory({{ $inventory->id }}, 'in')" class="btn btn-sm btn-ghost">Restock</button>
                                            <button wire:click="openEdit({{ $inventory->id }})" class="btn btn-sm btn-ghost">Edit</button>
                                            <button wire:click="toggleIngredient({{ $inventory->id }})" class="btn btn-sm btn-ghost">
                                                {{ $inventory->is_active ? 'Archive' : 'Activate' }}
                                            </button>
                                            @if (! $inventory->is_active)
                                                <button wire:click="deleteIngredient({{ $inventory->id }})"
                                                    wire:confirm="Delete {{ $inventory->name }} permanently?"
                                                    class="btn btn-sm text-rose-600 hover:bg-rose-50">Delete</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty-state">No ingredients match.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="data-table">
                        <thead>
                            <tr><th>When</th><th>Ingredient</th><th>Type</th><th class="text-right">Change</th><th class="text-right">After</th><th>By</th><th>Note</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr wire:key="movement-{{ $movement->id }}">
                                    <td class="text-xs whitespace-nowrap text-slate-500">{{ $movement->created_at->format('M d, h:i A') }}</td>
                                    <td class="font-medium">{{ $movement->inventory?->name ?? 'Deleted ingredient' }}</td>
                                    <td><span class="badge badge-{{ $movement->type->color() }}">{{ $movement->type->label() }}</span></td>
                                    <td class="text-right font-semibold {{ (float) $movement->qty_change >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ (float) $movement->qty_change > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $movement->qty_change, 3, '.', ''), '0'), '.') }}
                                    </td>
                                    <td class="text-right text-slate-600">{{ rtrim(rtrim(number_format((float) $movement->qty_after, 3, '.', ''), '0'), '.') }}</td>
                                    <td class="text-xs text-slate-500">{{ $movement->user?->name ?? 'System' }}</td>
                                    <td class="max-w-48 truncate text-xs text-slate-500">{{ $movement->note ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty-state">No stock movements recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
