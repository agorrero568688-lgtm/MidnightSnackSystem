<?php

namespace App\Jobs;

use App\Models\CustomerFeedback;
use App\Models\Order;
use App\Models\Setting;
use App\Services\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendFeedbackSmsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(SmsGateway $sms): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order) {
            return;
        }

        $phone = $sms->normalizePhone((string) $order->customer_phone);

        if ($phone === '') {
            return;
        }

        $every = max(1, (int) Setting::get('sms_feedback_every_n', '1'));

        $sentToday = CustomerFeedback::query()
            ->where('phone', $phone)
            ->whereDate('created_at', today())
            ->count();

        if ($sentToday >= $every) {
            return;
        }

        $sms->send($order->customer_phone, $sms->feedbackMessage($order));
    }
}
