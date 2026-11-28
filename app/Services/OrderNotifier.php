<?php

namespace App\Services;

use App\Jobs\SendOrderSmsAlert;
use App\Models\Order;
use App\Models\Setting;

class OrderNotifier
{
    public function __construct(private readonly SmsGateway $sms) {}

    /** Queue a short SMS alert to the configured admin numbers. No customer PII is included. */
    public function newOrder(Order $order): void
    {
        if (! Setting::bool('order_alerts_enabled')) {
            return;
        }

        if (! $this->configured()) {
            return;
        }

        SendOrderSmsAlert::dispatch($order->id)->afterCommit();
    }

    public function configured(): bool
    {
        return $this->sms->parsePhones((string) Setting::get('order_alert_phones', '')) !== []
            && filled(Setting::get('sms_gateway_url'));
    }

    /** Send a test SMS alert using the values currently in the settings form. */
    public function sendTest(
        string $phonesRaw,
        string $gatewayUrl,
        string $gatewayKey = '',
        string $senderName = '',
    ): bool {
        $phones = $this->sms->parsePhones($phonesRaw);

        if ($phones === [] || blank($gatewayUrl)) {
            return false;
        }

        $sender = trim($senderName) !== ''
            ? trim($senderName)
            : (trim((string) Setting::get('sms_sender_name', 'Diding SMS')) ?: 'Diding SMS');

        $message = "[{$sender}] — Test order alert. New online orders will be texted here.";
        $allSent = true;

        foreach ($phones as $phone) {
            if (! $this->sms->sendUsingConfig($phone, $message, $gatewayUrl, $gatewayKey, $sender)) {
                $allSent = false;
            }
        }

        return $allSent;
    }
}
