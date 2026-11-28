<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Setting;
use App\Services\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class SendOrderSmsAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    public function __construct(public readonly int $orderId) {}

    public function handle(SmsGateway $sms): void
    {
        $phones = $sms->parsePhones((string) Setting::get('order_alert_phones', ''));

        if ($phones === [] || blank(Setting::get('sms_gateway_url'))) {
            return;
        }

        $order = Order::query()->withCount('items')->find($this->orderId);

        if (! $order) {
            return;
        }

        $message = $sms->newOrderAlertMessage($order);
        $anySent = false;

        foreach ($phones as $phone) {
            if ($sms->sendAlert($phone, $message)) {
                $anySent = true;
            }
        }

        if (! $anySent) {
            throw new RuntimeException('Could not deliver order SMS alert to any admin phone number.');
        }

        $order->forceFill(['alert_sent_at' => now()])->save();
    }
}
