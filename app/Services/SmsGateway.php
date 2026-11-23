<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the store's SMS gateway. The configured URL must accept
 * JSON POSTs with a Bearer token and respond with a JSON body.
 */
class SmsGateway
{
    /** Text sent right after an order is marked ready. */
    public function readyMessage(Order $order): string
    {
        return sprintf('[%s] — Your snack is ready to pick up now!', $this->storeName());
    }

    /** Text sent to nudge a customer that is still waiting to pay. */
    public function abandonedMessage(Order $order): string
    {
        return sprintf(
            '[%s] — Still waiting on your snack. We saved your spot — reply PAY if you already paid via GCash.',
            $this->storeName(),
        );
    }

    /** Text sent to ask for feedback after an order completes. */
    public function feedbackMessage(Order $order): string
    {
        return sprintf(
            '[%s] — Saw you earlier — reply 5=great, 4=good, 3=ok, 2=poor, 1=bad.',
            $this->storeName(),
        );
    }

    /**
     * Send one message. Returns false when SMS is not configured or the
     * gateway fails, so callers can log and move on.
     */
    public function send(string $phone, string $message): bool
    {
        $phone = $this->normalizePhone($phone);

        if ($phone === '' || ! Setting::bool('sms_enabled')) {
            return false;
        }

        $url = trim((string) Setting::get('sms_gateway_url', ''));

        if ($url === '') {
            return false;
        }

        $payload = [
            'to' => $phone,
            'message' => $message,
            'sender' => $this->senderName(),
        ];

        try {
            $response = Http::timeout(15)
                ->post($url, $payload);
        } catch (\Throwable $exception) {
            Log::warning('SMS send threw', [
                'error' => $exception->getMessage(),
                'phone' => $phone,
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('SMS gateway rejected the send', [
                'status' => $response->status(),
                'phone' => $phone,
            ]);

            return false;
        }

        Log::info('SMS sent', ['phone' => $phone, 'message' => $message]);

        return true;
    }

    public function normalizePhone(string $phone): string
    {
        return (string) preg_replace('/[^0-9]/', '', $phone);
    }

    private function senderName(): string
    {
        return trim((string) Setting::get('sms_sender_name', 'Diding SMS')) ?: 'Diding SMS';
    }

    private function storeName(): string
    {
        return trim((string) Setting::get('store_name', 'Diding\'s Midnight Snack'));
    }
}
