<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Sale $sale): View
    {
        $sale->load(['items', 'user', 'voidedBy']);

        $width = request('width', Setting::get('receipt_width', '58'));
        $width = in_array($width, ['58', '80'], true) ? $width : '58';

        return view('receipts.sale', [
            'sale' => $sale,
            'width' => $width,
            'print' => request()->boolean('print', true),
        ]);
    }
}
