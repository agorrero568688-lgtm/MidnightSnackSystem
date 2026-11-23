<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Services\OrderNotifier;
use App\Services\SmsGateway;
use App\Support\Money;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public string $storeName = '';

    public string $storeAddress = '';

    public string $storePhone = '';

    public string $receiptFooter = '';

    public string $receiptWidth = '58';

    public bool $requireShift = true;

    public bool $allowNegativeStock = false;

    public string $lowStockDefault = '5';

    public bool $onlineOrderingEnabled = true;

    public bool $ordersPaused = false;

    public string $storeHours = '';

    public bool $deliveryEnabled = true;

    public bool $pickupEnabled = true;

    public string $deliveryFee = '0.00';

    public string $deliveryFreeBarangays = '';

    public string $freeDeliveryThreshold = '0.00';

    public string $minimumOrderTotal = '0.00';

    public string $gcashName = '';

    public string $gcashNumber = '';

    public $gcashQr = null;

    public ?string $existingGcashQrPath = null;

    public bool $orderAlertsEnabled = true;

    public string $telegramBotToken = '';

    public string $telegramChatId = '';

    public bool $smsEnabled = false;

    public string $smsGatewayUrl = '';

    public string $smsGatewayKey = '';

    public string $smsSenderName = 'Diding SMS';

    public string $smsReminderMinutes = '15';

    public string $smsFeedbackEveryN = '1';

    public string $smsStatus = '';

    public string $notice = '';

    public string $telegramStatus = '';

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

        $this->onlineOrderingEnabled = Setting::bool('online_ordering_enabled');
        $this->ordersPaused = Setting::bool('orders_paused');
        $this->storeHours = (string) (Setting::get('store_hours') ?: 'Open 24/7, daily');
        $this->deliveryEnabled = Setting::bool('delivery_enabled');
        $this->pickupEnabled = Setting::bool('pickup_enabled');
        $this->deliveryFee = (string) Setting::get('delivery_fee', '0.00');
        $this->deliveryFreeBarangays = (string) Setting::get('delivery_free_barangays', "Magosilom\nLininti-an");
        $this->freeDeliveryThreshold = (string) Setting::get('free_delivery_threshold', '0.00');
        $this->minimumOrderTotal = (string) Setting::get('minimum_order_total', '0.00');
        $this->gcashName = (string) Setting::get('gcash_name');
        $this->gcashNumber = (string) Setting::get('gcash_number');
        $this->existingGcashQrPath = Setting::get('gcash_qr_path') ?: null;
        $this->orderAlertsEnabled = Setting::bool('order_alerts_enabled');
        $this->telegramBotToken = (string) Setting::get('telegram_bot_token');
        $this->telegramChatId = (string) Setting::get('telegram_chat_id');
        $this->smsEnabled = Setting::bool('sms_enabled');
        $this->smsGatewayUrl = (string) Setting::get('sms_gateway_url');
        $this->smsGatewayKey = (string) Setting::get('sms_gateway_key');
        $this->smsSenderName = (string) Setting::get('sms_sender_name', 'Diding SMS');
        $this->smsReminderMinutes = (string) Setting::get('sms_reminder_minutes', '15');
        $this->smsFeedbackEveryN = (string) Setting::get('sms_feedback_every_n', '1');
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
            'storeHours' => ['nullable', 'string', 'max:100'],
            'deliveryFee' => ['required', 'numeric', 'min:0', 'max:99999'],
            'deliveryFreeBarangays' => ['required', 'string', 'max:400'],
            'freeDeliveryThreshold' => ['required', 'numeric', 'min:0', 'max:99999'],
            'minimumOrderTotal' => ['required', 'numeric', 'min:0', 'max:99999'],
            'gcashName' => ['nullable', 'string', 'max:100'],
            'gcashNumber' => ['nullable', 'string', 'max:30'],
            'gcashQr' => ['nullable', 'image', 'max:2048'],
            'telegramBotToken' => ['nullable', 'string', 'max:200'],
            'telegramChatId' => ['nullable', 'string', 'max:100'],
            'smsGatewayUrl' => ['nullable', 'url', 'max:200'],
            'smsGatewayKey' => ['nullable', 'string', 'max:100'],
            'smsSenderName' => ['required', 'string', 'max:12'],
            'smsReminderMinutes' => ['required', 'integer', 'min:5,max:120'],
            'smsFeedbackEveryN' => ['required', 'integer', 'min:1,max:10'],
        ]);

        if ($this->gcashQr) {
            if ($this->existingGcashQrPath) {
                Storage::disk('public')->delete($this->existingGcashQrPath);
            }

            $this->existingGcashQrPath = $this->gcashQr->store('store-settings', 'public');
        }

        Setting::setMany([
            'store_name' => $validated['storeName'],
            'store_address' => $validated['storeAddress'] ?? '',
            'store_phone' => $validated['storePhone'] ?? '',
            'receipt_footer' => $validated['receiptFooter'] ?? '',
            'receipt_width' => $validated['receiptWidth'],
            'require_shift' => $this->requireShift ? '1' : '0',
            'allow_negative_stock' => $this->allowNegativeStock ? '1' : '0',
            'low_stock_default' => $validated['lowStockDefault'],

            'online_ordering_enabled' => $this->onlineOrderingEnabled ? '1' : '0',
            'orders_paused' => $this->ordersPaused ? '1' : '0',
            'store_hours' => $validated['storeHours'] ?? '',
            'delivery_enabled' => $this->deliveryEnabled ? '1' : '0',
            'pickup_enabled' => $this->pickupEnabled ? '1' : '0',
            'delivery_fee' => Money::decimal(Money::cents($validated['deliveryFee'])),
            'delivery_free_barangays' => trim((string) $validated['deliveryFreeBarangays']),
            'free_delivery_threshold' => Money::decimal(Money::cents($validated['freeDeliveryThreshold'])),
            'minimum_order_total' => Money::decimal(Money::cents($validated['minimumOrderTotal'])),
            'gcash_name' => $validated['gcashName'] ?? '',
            'gcash_number' => $validated['gcashNumber'] ?? '',
            'gcash_qr_path' => $this->existingGcashQrPath ?? '',
            'order_alerts_enabled' => $this->orderAlertsEnabled ? '1' : '0',
            'telegram_bot_token' => trim((string) ($validated['telegramBotToken'] ?? '')),
            'telegram_chat_id' => trim((string) ($validated['telegramChatId'] ?? '')),
            'sms_enabled' => $this->smsEnabled ? '1' : '0',
            'sms_gateway_url' => trim((string) $validated['smsGatewayUrl']),
            'sms_gateway_key' => trim((string) ($validated['smsGatewayKey'] ?? '')),
            'sms_sender_name' => $validated['smsSenderName'],
            'sms_reminder_minutes' => $validated['smsReminderMinutes'],
            'sms_feedback_every_n' => $validated['smsFeedbackEveryN'],
        ]);

        $this->gcashQr = null;
        $this->notice = 'Store settings saved.';
        $this->telegramStatus = '';

        session()->flash('status', 'Store settings saved.');
    }

    public function removeGcashQr(): void
    {
        if ($this->existingGcashQrPath) {
            Storage::disk('public')->delete($this->existingGcashQrPath);
        }

        $this->existingGcashQrPath = null;
        $this->gcashQr = null;
        Setting::set('gcash_qr_path', '');

        $this->notice = 'GCash QR image removed.';
    }

    public function testTelegram(OrderNotifier $notifier): void
    {
        $sent = $notifier->sendTest(trim($this->telegramBotToken), trim($this->telegramChatId));

        $this->telegramStatus = $sent
            ? 'Test message sent. Check the staff group.'
            : 'Could not send. Check the bot token and chat ID, and make sure the bot is in the group.';
    }

    public function testSms(SmsGateway $sms): void
    {
        $phone = preg_replace('/[^0-9]/', '', (string) Setting::get('store_phone', ''));

        if (blank($phone)) {
            $this->smsStatus = 'Set the store phone first, then try again.';

            return;
        }

        $sent = $sms->send($phone, "[{$this->smsSenderName}] — This is a test message. Is your SMS setup working?");

        $this->smsStatus = $sent
            ? 'Test SMS sent to the store phone.'
            : 'Could not send. Check the gateway URL, API key and that SMS is enabled.';
    }

    public function render()
    {
        return view('livewire.settings.edit')
            ->layout('layouts.app', ['title' => 'Settings', 'subtitle' => 'Store details, online ordering and alerting']);
    }
}
