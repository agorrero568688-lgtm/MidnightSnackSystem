<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sale->reference_no ?? 'Receipt' }}</title>
    @include('receipts.styles', ['width' => $width])
</head>
<body onload="{{ $print ? 'window.print()' : '' }}">
    @php
        $storeName = \App\Models\Setting::get('store_name');
        $storeAddress = \App\Models\Setting::get('store_address');
        $storePhone = \App\Models\Setting::get('store_phone');
        $footer = \App\Models\Setting::get('receipt_footer');
    @endphp

    <div class="receipt">
        <div class="center">
            <div class="store">{{ $storeName }}</div>
            @if ($storeAddress)
                <div>{{ $storeAddress }}</div>
            @endif
            @if ($storePhone)
                <div>{{ $storePhone }}</div>
            @endif
        </div>

        <div class="rule"></div>

        <div class="row">
            <span>Ref</span><span>{{ $sale->reference_no ?? '—' }}</span>
        </div>
        <div class="row">
            <span>Date</span><span>{{ $sale->created_at->format('M d, Y h:i A') }}</span>
        </div>
        <div class="row">
            <span>Cashier</span><span>{{ $sale->user?->name }}</span>
        </div>

        @if ($sale->isVoided())
            <div class="rule"></div>
            <div class="center void">*** VOIDED ***</div>
            @if ($sale->void_reason)
                <div class="center small">{{ $sale->void_reason }}</div>
            @endif
        @endif

        <div class="rule"></div>

        @foreach ($sale->items as $item)
            <div class="item">
                <div class="item-name">{{ $item->name_snapshot }}</div>
                <div class="row">
                    <span>{{ $item->qty }} x {{ \App\Support\Money::format($item->unit_price) }}</span>
                    <span>{{ \App\Support\Money::format($item->line_total) }}</span>
                </div>
            </div>
        @endforeach

        <div class="rule"></div>

        <div class="row">
            <span>Subtotal</span><span>{{ \App\Support\Money::format($sale->subtotal) }}</span>
        </div>

        @if ($sale->discount_amount > 0)
            <div class="row">
                <span>{{ $sale->discount_type->label() }}</span>
                <span>-{{ \App\Support\Money::format($sale->discount_amount) }}</span>
            </div>
            @if ($sale->customer_name)
                <div class="small">{{ $sale->customer_name }} · {{ $sale->discount_id_number }}</div>
            @endif
        @endif

        <div class="row total">
            <span>TOTAL</span><span>{{ \App\Support\Money::format($sale->total) }}</span>
        </div>
        <div class="row">
            <span>{{ $sale->payment_method->label() }}</span>
            <span>{{ \App\Support\Money::format($sale->amount_tendered) }}</span>
        </div>
        <div class="row">
            <span>Change</span><span>{{ \App\Support\Money::format($sale->change_due) }}</span>
        </div>

        @if ($sale->notes)
            <div class="rule"></div>
            <div class="small">{{ $sale->notes }}</div>
        @endif

        <div class="rule"></div>

        <div class="center small">{{ $footer }}</div>
        <div class="center small">Items: {{ $sale->itemCount() }}</div>

        @if ($print)
            <div class="no-print center" style="margin-top: 12px;">
                <button onclick="window.print()">Print again</button>
                <button onclick="window.close()">Close</button>
            </div>
        @endif
    </div>
</body>
</html>
