<?php

namespace App\Livewire\Reports;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\Shift;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Daily extends Component
{
    public string $date = '';

    public function mount(): void
    {
        $this->date = today()->toDateString();
    }

    public function render()
    {
        $day = $this->date !== '' ? $this->date : today()->toDateString();

        $base = Sale::query()
            ->where('status', SaleStatus::Completed->value)
            ->whereDate('created_at', $day);

        $totals = (clone $base)->selectRaw('
            coalesce(sum(total), 0) as total,
            coalesce(sum(subtotal), 0) as subtotal,
            coalesce(sum(discount_amount), 0) as discounts,
            count(*) as transactions
        ')->first();

        $items = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereDate('sales.created_at', $day)
            ->sum('sale_items.qty');

        $payments = (clone $base)
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('coalesce(sum(total), 0) as total'))
            ->groupBy('payment_method')
            ->get();

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereDate('sales.created_at', $day)
            ->select(
                'sale_items.name_snapshot',
                DB::raw('sum(sale_items.qty) as qty'),
                DB::raw('sum(sale_items.line_total) as total'),
            )
            ->groupBy('sale_items.name_snapshot')
            ->orderByDesc('qty')
            ->limit(15)
            ->get();

        $cashSales = (clone $base)->where('payment_method', 'cash')->sum('total');
        $voided = Sale::query()
            ->where('status', SaleStatus::Voided->value)
            ->whereDate('created_at', $day)
            ->count();

        $shifts = Shift::query()
            ->with('user')
            ->whereDate('opened_at', $day)
            ->orderBy('opened_at')
            ->get();

        return view('livewire.reports.daily', [
            'day' => $day,
            'total' => Money::format($totals->total ?? 0),
            'subtotal' => Money::format($totals->subtotal ?? 0),
            'discounts' => Money::format($totals->discounts ?? 0),
            'transactions' => (int) ($totals->transactions ?? 0),
            'itemsSold' => (int) $items,
            'payments' => $payments,
            'topProducts' => $topProducts,
            'cashSales' => Money::format($cashSales),
            'voidedCount' => $voided,
            'shifts' => $shifts,
        ])->layout('layouts.app', ['title' => 'Daily report', 'subtitle' => 'Z-reading for the chosen day']);
    }
}
