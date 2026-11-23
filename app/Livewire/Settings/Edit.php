<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Livewire\Component;

class Edit extends Component
{
    public string $storeName = '';

    public string $storeAddress = '';

    public string $storePhone = '';

    public string $receiptFooter = '';

    public string $receiptWidth = '58';

    public bool $requireShift = true;

    public bool $allowNegativeStock = false;

    public string $lowStockDefault = '5';

    public function mount(): void
    {
        $this->storeName = (string) Setting::get('store_name');
        $this->storeAddress = (string) Setting::get('store_address');
        $this->storePhone = (string) Setting::get('store_phone');
        $this->receiptFooter = (string) Setting::get('receipt_footer');
        $this->receiptWidth = (string) Setting::get('receipt_width', '58');
        $this->requireShift = Setting::get('require_shift') === '1';
        $this->allowNegativeStock = Setting::bool('allow_negative_stock');
        $this->lowStockDefault = (string) Setting::get('low_stock_default', '5');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'storeName' => ['required', 'string', 'max:100'],
            'storeAddress' => ['nullable', 'string', 'max:200'],
            'storePhone' => ['nullable', 'string', 'max:50'],
            'receiptFooter' => ['nullable', 'string', 'max:200'],
            'receiptWidth' => ['required', 'in:58,80'],
            'lowStockDefault' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        Setting::setMany([
            'store_name' => $validated['storeName'],
            'store_address' => $validated['storeAddress'] ?? '',
            'store_phone' => $validated['storePhone'] ?? '',
            'receipt_footer' => $validated['receiptFooter'] ?? '',
            'receipt_width' => $validated['receiptWidth'],
            'require_shift' => $this->requireShift ? '1' : '0',
            'allow_negative_stock' => $this->allowNegativeStock ? '1' : '0',
            'low_stock_default' => $validated['lowStockDefault'],
        ]);

        session()->flash('status', 'Store settings saved.');
    }

    public function render()
    {
        return view('livewire.settings.edit')
            ->layout('layouts.app', ['title' => 'Settings', 'subtitle' => 'Store details and till behaviour']);
    }
}
