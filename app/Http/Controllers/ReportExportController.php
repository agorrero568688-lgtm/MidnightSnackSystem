<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function daily(Request $request): StreamedResponse
    {
        $date = $request->date('date') ?? today();
        $day = $date->toDateString();

        $sales = Sale::query()
            ->with(['items', 'user'])
            ->where('status', SaleStatus::Completed)
            ->whereDate('created_at', $day)
            ->orderBy('id')
            ->get();

        $filename = "diding-sales-{$day}.csv";

        return response()->streamDownload(function () use ($sales) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Reference', 'Time', 'Cashier', 'Item', 'SKU', 'Qty', 'Unit Price', 'Line Total',
                'Payment', 'Discount Type', 'Discount Amount', 'Sale Total',
            ]);

            foreach ($sales as $sale) {
                foreach ($sale->items as $item) {
                    fputcsv($handle, [
                        $sale->reference_no,
                        $sale->created_at->format('Y-m-d H:i:s'),
                        $sale->user?->name,
                        $item->name_snapshot,
                        $item->sku_snapshot,
                        $item->qty,
                        $item->unit_price,
                        $item->line_total,
                        $sale->payment_method->label(),
                        $sale->discount_type->label(),
                        $sale->discount_amount,
                        $sale->total,
                    ]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
