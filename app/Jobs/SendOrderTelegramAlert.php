<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendOrderTelegramAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    public function __construct(public readonly int $orderId) {}

    public function handle(): void
    {
        $token = Setting::get('telegram_bot_token') ?: config('services.telegram.bot_token');
        $chatId = Setting::get('telegram_chat_id') ?: config('services.telegram.chat_id');

        if (blank($token) || blank($chatId)) {
            return;
        }

        $order = Order::query()->withCount('items')->find($this->orderId);

        if (! $order) {
            return;
        }

        $lines = [
            '🔔 <b>New online order</b>',
            '<b>'.e($order->reference_no).'</b> · '.e($order->order_type->label()),
            e($order->payment_method->label()).' · '.e($order->payment_status->label()),
            $order->items_count.' line'.($order->items_count === 1 ? '' : 's').' · '.e($order->total).' total',
            e($order->placedAtLabel()),
        ];

        if ($order->payment_method->isGcash() && $order->payment_status->value === 'awaiting_verification') {
            $lines[] = '⚠️ GCash reference is waiting for verification.';
        }

        $lines[] = '';
        $lines[] = '<a href="'.e(route('orders.index', ['order' => $order->id])).'">Open the order board</a>';

        Http::timeout(10)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => implode("\n", $lines),
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ])
            ->throw();

        $order->forceFill(['alert_sent_at' => now()])->save();
    }
}
