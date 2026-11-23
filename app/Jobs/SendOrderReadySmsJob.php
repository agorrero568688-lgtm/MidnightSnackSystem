<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOrderReadySmsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(SmsGateway $sms): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order || ! in_array($order->status, [OrderStatus::Ready, OrderStatus::OutForDelivery], true)) {
            return;
        }

        $sms->send($order->customer_phone, $sms->readyMessage($order));
    }
}
