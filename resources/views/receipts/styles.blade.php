@php
    $millimetres = $width === '80' ? '80mm' : '58mm';
    $baseSize = $width === '80' ? '12px' : '11px';
@endphp
<style>
    @page {
        size: {{ $millimetres }} auto;
        margin: 2mm;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        background: #fff;
        color: #000;
        font-family: 'Courier New', ui-monospace, monospace;
        font-size: {{ $baseSize }};
        line-height: 1.35;
    }

    .receipt {
        width: {{ $millimetres }};
        margin: 0 auto;
        padding: 2mm 2mm 6mm;
    }

    .center {
        text-align: center;
    }

    .store {
        font-size: 1.35em;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .small {
        font-size: 0.9em;
    }

    .void {
        font-weight: 700;
        letter-spacing: 0.15em;
    }

    .rule {
        border-top: 1px dashed #000;
        margin: 6px 0;
    }

    .row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
    }

    .row.total {
        font-size: 1.2em;
        font-weight: 700;
        margin-top: 2px;
    }

    .item {
        margin-bottom: 3px;
    }

    .item-name {
        overflow-wrap: anywhere;
    }

    button {
        font: inherit;
        padding: 4px 10px;
        margin: 0 2px;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            width: {{ $millimetres }};
        }
    }
</style>
