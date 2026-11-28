<?php

namespace App\Services;

use App\Jobs\SendOrderTelegramAlert;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderNotifier
{
    /** Queue a short Telegram alert. No customer PII is included. */
    public function newOrder(Order $order): void
    {
        if (! Setting::bool('order_alerts_enabled')) {
            return;
        }

        if (! $this->configured()) {
            return;
        }

        SendOrderTelegramAlert::dispatch($order->id)->afterCommit();
    }

    public function configured(): bool
    {
        return filled(Setting::get('telegram_bot_token') ?: config('services.telegram.bot_token'))
            && filled(Setting::get('telegram_chat_id') ?: config('services.telegram.chat_id'));
    }

    /** Send a test message with the values currently in the settings form. */
    public function sendTest(string $token, string $chatId): bool
    {
        if (blank($token) || blank($chatId)) {
            return false;
        }

        try {
            return Http::timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => 'Γ£à Diding\'s test alert ΓÇö online orders will show up here.',
                    'disable_web_page_preview' => true,
                ])
                ->successful();
        } catch (\Throwable $exception) {
            Log::warning('Telegram test failed', ['message' => $exception->getMessage()]);

            return false;
        }
    }
}
