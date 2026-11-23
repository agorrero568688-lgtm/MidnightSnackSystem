<?php

namespace App\Livewire;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $day = today();

        $sales = Sale::query()
            ->completed()
            ->whereDate('created_at', $day);

        $todayTotal = (clone $sales)->sum('total');
        $todayCount = (clone $sales)->count();
        $todayDiscounts = (clone $sales)->sum('discount_amount');

        $itemsSold = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereDate('sales.created_at', $day)
            ->sum('sale_items.qty');

        $recentSales = Sale::query()
            ->with('user')
            ->withSum('items', 'qty')
            ->latest()
            ->limit(8)
            ->get();

        $lowStock = Product::query()
            ->active()
            ->lowStock()
            ->orderBy('stock_qty')
            ->limit(6)
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
            ->limit(6)
            ->get();

        return view('livewire.dashboard', [
            'todayTotal' => Money::format($todayTotal),
            'todayCount' => $todayCount,
            'todayDiscounts' => Money::format($todayDiscounts),
            'itemsSold' => (int) $itemsSold,
            'recentSales' => $recentSales,
            'lowStock' => $lowStock,
            'topProducts' => $topProducts,
            'lowStockCount' => Product::query()->active()->lowStock()->count(),
        ])->layout('layouts.app', ['title' => 'Dashboard', 'subtitle' => $day->format('l, F j, Y')]);
    }
}
