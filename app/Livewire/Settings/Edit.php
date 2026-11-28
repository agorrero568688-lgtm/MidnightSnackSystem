<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Services\OrderNotifier;
use App\Services\SmsGateway;
use App\Support\Concerns\ConfirmsWithPassword;
use App\Support\GcashQr;
use App\Support\Money;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class Edit extends Component
{
    use ConfirmsWithPassword, WithFileUploads;

    public string $storeName = '';

    public string $storeAddress = '';

    public string $storePhone = '';

    public string $receiptFooter = '';

    public string $receiptWidth = '58';

    public bool $requireShift = true;

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

    public string $orderAlertPhones = '';

    public bool $smsEnabled = false;

    public string $smsGatewayUrl = '';

    public string $smsGatewayKey = '';

    public string $smsSenderName = 'Diding SMS';

    public string $smsReminderMinutes = '15';

    public string $smsFeedbackEveryN = '1';

    public string $smsStatus = '';

    public string $notice = '';

    public string $orderAlertStatus = '';

    #[Locked]
    public ?string $originalGcashName = null;

    #[Locked]
    public ?string $originalGcashNumber = null;

    #[Locked]
    public ?string $originalQrPath = null;

    public function mount(): void
    {
        $this->storeName = (string) Setting::get('store_name');
        $this->storeAddress = (string) Setting::get('store_address');
        $this->storePhone = (string) Setting::get('store_phone');
        $this->receiptFooter = (string) Setting::get('receipt_footer');
        $this->receiptWidth = (string) Setting::get('receipt_width', '58');
        $this->requireShift = Setting::get('require_shift') === '1';

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
        $this->originalGcashName = $this->gcashName;
        $this->originalGcashNumber = $this->gcashNumber;
        $this->originalQrPath = $this->existingGcashQrPath;
        $this->orderAlertsEnabled = Setting::bool('order_alerts_enabled');
        $this->orderAlertPhones = (string) Setting::get('order_alert_phones');
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
            'storeHours' => ['nullable', 'string', 'max:100'],
            'deliveryFee' => ['required', 'numeric', 'min:0', 'max:99999'],
            'deliveryFreeBarangays' => ['required', 'string', 'max:400'],
            'freeDeliveryThreshold' => ['required', 'numeric', 'min:0', 'max:99999'],
            'minimumOrderTotal' => ['required', 'numeric', 'min:0', 'max:99999'],
            'gcashName' => ['nullable', 'string', 'max:100'],
            'gcashNumber' => ['nullable', 'string', 'max:30'],
            'gcashQr' => ['nullable', 'image', 'max:2048'],
            'orderAlertPhones' => ['nullable', 'string', 'max:500'],
            'smsGatewayUrl' => ['nullable', 'url', 'max:200'],
            'smsGatewayKey' => ['nullable', 'string', 'max:100'],
            'smsSenderName' => ['required', 'string', 'max:12'],
            'smsReminderMinutes' => ['required', 'integer', 'min:5,max:120'],
            'smsFeedbackEveryN' => ['required', 'integer', 'min:1,max:10'],
        ]);

        $paymentDetailsChanged = ($validated['gcashName'] ?? '') !== $this->originalGcashName
            || ($validated['gcashNumber'] ?? '') !== $this->originalGcashNumber
            || $this->gcashQr !== null;

        if ($paymentDetailsChanged && ! $this->confirmPasswordOrFail()) {
            return;
        }

        $previousQrPath = $this->existingGcashQrPath;
        $newQrPath = null;

        if ($this->gcashQr) {
            try {
                $newQrPath = GcashQr::store($this->gcashQr);
            } catch (Throwable) {
                $this->addError('gcashQr', 'Could not upload the QR image. Please try again.');

                return;
            }
        }

        Setting::setMany([
            'store_name' => $validated['storeName'],
            'store_address' => $validated['storeAddress'] ?? '',
            'store_phone' => $validated['storePhone'] ?? '',
            'receipt_footer' => $validated['receiptFooter'] ?? '',
            'receipt_width' => $validated['receiptWidth'],
            'require_shift' => $this->requireShift ? '1' : '0',

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
            'gcash_qr_path' => $newQrPath ?? $previousQrPath ?? '',
            'order_alerts_enabled' => $this->orderAlertsEnabled ? '1' : '0',
            'order_alert_phones' => trim((string) ($validated['orderAlertPhones'] ?? '')),
            'sms_enabled' => $this->smsEnabled ? '1' : '0',
            'sms_gateway_url' => trim((string) $validated['smsGatewayUrl']),
            'sms_gateway_key' => trim((string) ($validated['smsGatewayKey'] ?? '')),
            'sms_sender_name' => $validated['smsSenderName'],
            'sms_reminder_minutes' => $validated['smsReminderMinutes'],
            'sms_feedback_every_n' => $validated['smsFeedbackEveryN'],
        ]);

        if ($newQrPath !== null && $newQrPath !== $previousQrPath) {
            // Only remove the old image once the new one is safely stored and
            // the database now points at the replacement.
            GcashQr::delete($previousQrPath);
            $this->existingGcashQrPath = $newQrPath;
        }

        $this->gcashQr = null;
        $this->notice = 'Store settings saved.';
        $this->orderAlertStatus = '';

        session()->flash('status', 'Store settings saved.');
        $this->redirectRoute('settings.edit');
    }

    public function removeGcashQr(): void
    {
        if (! $this->confirmPasswordOrFail()) {
            return;
        }

        GcashQr::delete($this->existingGcashQrPath);

        $this->existingGcashQrPath = null;
        $this->gcashQr = null;
        Setting::set('gcash_qr_path', '');

        $this->notice = 'GCash QR image removed.';
    }

    public function testOrderAlert(OrderNotifier $notifier): void
    {
        $sent = $notifier->sendTest(
            trim($this->orderAlertPhones),
            trim($this->smsGatewayUrl),
            trim($this->smsGatewayKey),
            trim($this->smsSenderName),
        );

        $this->orderAlertStatus = $sent
            ? 'Test alert sent. Check the admin phone number(s).'
            : 'Could not send. Check the admin phone number(s), gateway URL and API key.';
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
