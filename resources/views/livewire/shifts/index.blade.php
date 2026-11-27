<div class="space-y-5">
    <div class="card card-body flex flex-wrap items-end gap-3">
        <div>
            <label class="label">From</label>
            <input wire:model.live="from" type="date" class="input w-40">
        </div>
        <div>
            <label class="label">To</label>
            <input wire:model.live="to" type="date" class="input w-40">
        </div>
        <div class="flex gap-4 text-sm">
            <span class="badge badge-green">{{ $openCount }} open</span>
            <span class="badge {{ $totalVarianceCents < 0 ? 'badge-red' : 'badge-slate' }}">
                Net variance {{ \App\Support\Money::formatCents($totalVarianceCents) }}
            </span>
        </div>
    </div>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="data-table responsive-table">
                <thead>
                    <tr>
                        <th>Cashier</th>
                        <th>Opened</th>
                        <th class="hidden sm:table-cell">Closed</th>
                        <th class="text-right">Opening</th>
                        <th class="text-right">Expected</th>
                        <th class="text-right">Counted</th>
                        <th class="text-right">Variance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shifts as $shift)
                        <tr wire:key="shift-{{ $shift->id }}">
                            <td data-label="Cashier" class="font-semibold">{{ $shift->user?->name }}</td>
                            <td data-label="Opened" class="text-xs whitespace-nowrap text-ink-600">{{ $shift->opened_at->format('M d, Y h:i A') }}</td>
                            <td data-label="Closed" class="hidden text-xs whitespace-nowrap text-ink-600 sm:table-cell">{{ $shift->closed_at?->format('M d, Y h:i A') ?? '—' }}</td>
                            <td data-label="Opening" class="text-right">{{ \App\Support\Money::format($shift->opening_cash) }}</td>
                            <td data-label="Expected" class="text-right">{{ $shift->expected_cash !== null ? \App\Support\Money::format($shift->expected_cash) : '—' }}</td>
                            <td data-label="Counted" class="text-right">{{ $shift->closing_cash !== null ? \App\Support\Money::format($shift->closing_cash) : '—' }}</td>
                            <td data-label="Variance" class="text-right font-semibold">
                                @if ($shift->variance === null)
                                    <span class="text-ink-400">—</span>
                                @elseif (\App\Support\Money::cents($shift->variance) === 0)
                                    <span class="text-ink-500">Balanced</span>
                                @else
                                    <span class="{{ \App\Support\Money::cents($shift->variance) < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ \App\Support\Money::format($shift->variance) }}
                                    </span>
                                @endif
                            </td>
                            <td data-label="Status">
                                @if ($shift->isOpen())
                                    <span class="badge badge-green">Open</span>
                                @else
                                    <span class="badge badge-slate">Closed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">No shifts in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($shifts->hasPages())
            <div class="border-t border-ink-100 px-4 py-3">{{ $shifts->links() }}</div>
        @endif
    </div>
</div>
