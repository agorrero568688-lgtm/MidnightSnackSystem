<div class="space-y-5">
    <div class="card card-body">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="label">From</label>
                <input wire:model.live="from" type="date" class="input w-40">
            </div>
            <div>
                <label class="label">To</label>
                <input wire:model.live="to" type="date" class="input w-40">
            </div>
            <div class="min-w-52 flex-1">
                <label class="label">Search</label>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Reference or customer name" class="input">
            </div>
            <div>
                <label class="label">Status</label>
                <select wire:model.live="status" class="input w-36">
                    <option value="all">All</option>
                    <option value="completed">Completed</option>
                    <option value="voided">Voided</option>
                </select>
            </div>
            <div>
                <label class="label">Payment</label>
                <select wire:model.live="payment" class="input w-36">
                    <option value="all">All</option>
                    @foreach ($payments as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="today" class="btn btn-secondary">Today</button>
        </div>

        <div class="mt-4 flex flex-wrap gap-x-8 gap-y-2 border-t border-ink-100 pt-4 text-sm">
            <div><span class="text-ink-500">Net sales:</span> <span class="font-bold text-ink-900">{{ $summaryTotal }}</span></div>
            <div><span class="text-ink-500">Transactions:</span> <span class="font-bold">{{ number_format($summaryCount) }}</span></div>
            <div><span class="text-ink-500">Discounts:</span> <span class="font-bold">{{ $summaryDiscounts }}</span></div>
        </div>
    </div>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="data-table responsive-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Date &amp; time</th>
                        <th>Cashier</th>
                        <th class="text-right">Items</th>
                        <th>Payment</th>
                        <th class="hidden sm:table-cell">Channel</th>
                        <th class="hidden sm:table-cell">Discount</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}" class="{{ $sale->isVoided() ? 'bg-rose-50/40' : '' }}">
                            <td data-label="Reference" class="font-mono text-xs font-semibold">{{ $sale->reference_no }}</td>
                            <td data-label="Date &amp; time" class="text-xs whitespace-nowrap text-ink-600">{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                            <td data-label="Cashier" class="text-ink-600">{{ $sale->user?->name }}</td>
                            <td data-label="Items" class="text-right">{{ (int) $sale->items_sum_qty }}</td>
                            <td data-label="Payment"><span class="badge badge-slate">{{ $sale->payment_method->label() }}</span></td>
                            <td data-label="Channel" class="hidden sm:table-cell"><span class="badge badge-{{ $sale->channel->color() }}">{{ $sale->channel->label() }}</span></td>
                            <td data-label="Discount" class="hidden text-xs sm:table-cell">
                                @if ($sale->discount_amount > 0)
                                    <span class="badge badge-violet">{{ $sale->discount_type->label() }}</span>
                                @else
                                    <span class="text-ink-400">—</span>
                                @endif
                            </td>
                            <td data-label="Total" class="text-right font-semibold">{{ \App\Support\Money::format($sale->total) }}</td>
                            <td data-label="Status">
                                @if ($sale->isVoided())
                                    <span class="badge badge-red">Voided</span>
                                @else
                                    <span class="badge badge-green">Completed</span>
                                @endif
                            </td>
                            <td data-label="Actions">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('sales.receipt', $sale) }}" target="_blank" class="btn btn-sm btn-ghost">
                                        <x-icon name="print" class="h-4 w-4" /> Receipt
                                    </a>
                                    @unless ($sale->isVoided())
                                        <button wire:click="startVoid({{ $sale->id }})" class="btn btn-sm text-rose-600 hover:bg-rose-50">Void</button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="empty-state">No sales in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sales->hasPages())
            <div class="border-t border-ink-100 px-4 py-3">{{ $sales->links() }}</div>
        @endif
    </div>

    {{-- Void modal --}}
    @if ($voidingSale)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-ink-950/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold">Void {{ $voidingSale->reference_no }}?</h3>
                <p class="mt-1 text-sm text-ink-500">
                    This keeps the record marked as voided. This cannot be undone.
                </p>

                <div class="mt-4">
                    <label class="label">Reason</label>
                    <textarea wire:model="voidReason" rows="3" class="input" placeholder="e.g. Wrong item scanned, customer cancelled"></textarea>
                    @error('voidReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button wire:click="cancelVoid" class="btn btn-secondary">Cancel</button>
                    <button wire:click="confirmVoid" class="btn btn-danger" wire:loading.attr="disabled">
                        <span wire:loading.remove>Void sale</span>
                        <span wire:loading>Voiding…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
