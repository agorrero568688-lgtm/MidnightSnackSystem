<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the store's SMS gateway. The configured URL receives a
 * JSON POST with `to`, `message` and `sender`; when an API key is set it is
 * sent as an `Authorization: Bearer` header.
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
            '[%s] — Still waiting on your snack. We saved your spot — upload your GCash receipt here: %s',
            $this->storeName(),
            route('orders.track', ['token' => $order->public_token]),
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

    /** Text sent to admin phones when a new online order is placed. */
    public function newOrderAlertMessage(Order $order): string
    {
        $itemsCount = $order->items_count ?? $order->items()->count();

        $lines = [
            sprintf('[%s] New online order', $this->storeName()),
            sprintf('%s · %s', $order->reference_no, $order->order_type->label()),
            sprintf('%s · %s', $order->payment_method->label(), $order->payment_status->label()),
            sprintf('%d %s · ₱%s total', $itemsCount, $itemsCount === 1 ? 'line' : 'lines', $order->total),
            $order->placedAtLabel(),
        ];

        if ($order->payment_method->isGcash() && $order->payment_status->value === 'awaiting_verification') {
            $lines[] = 'GCash payment screenshot is waiting for verification.';
        }

        $lines[] = 'Order board: '.route('orders.index', ['order' => $order->id]);

        return implode("\n", $lines);
    }

    /**
     * Send one customer SMS message. Returns false when customer SMS is disabled,
     * not configured, or the gateway fails.
     */
    public function send(string $phone, string $message): bool
    {
        if (! Setting::bool('sms_enabled')) {
            return false;
        }

        return $this->sendUsingConfig(
            $phone,
            $message,
            (string) Setting::get('sms_gateway_url', ''),
            (string) Setting::get('sms_gateway_key', ''),
            $this->senderName(),
        );
    }

    /**
     * Send an internal admin/staff alert SMS using the configured gateway,
     * regardless of whether customer SMS updates are enabled.
     */
    public function sendAlert(string $phone, string $message): bool
    {
        return $this->sendUsingConfig(
            $phone,
            $message,
            (string) Setting::get('sms_gateway_url', ''),
            (string) Setting::get('sms_gateway_key', ''),
            $this->senderName(),
        );
    }

    public function sendUsingConfig(
        string $phone,
        string $message,
        string $gatewayUrl,
        string $gatewayKey = '',
        string $senderName = '',
    ): bool {
        $phone = $this->normalizePhone($phone);
        $url = trim($gatewayUrl);

        if ($phone === '' || $url === '') {
            return false;
        }

        $sender = trim($senderName) !== '' ? trim($senderName) : $this->senderName();
        $key = trim($gatewayKey);

        $payload = [
            'to' => $phone,
            'message' => $message,
            'sender' => $sender,
        ];

        try {
            $request = Http::timeout(15);

            if ($key !== '') {
                $request = $request->withToken($key);
            }

            $response = $request->post($url, $payload);
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

    /**
     * Parse a comma- or newline-separated list of phone numbers into unique
     * normalized digit strings.
     *
     * @return list<string>
     */
    public function parsePhones(string $raw): array
    {
        $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
        $phones = [];

        foreach ($parts as $part) {
            $normalized = $this->normalizePhone($part);

            if ($normalized !== '') {
                $phones[] = $normalized;
            }
        }

        return array_values(array_unique($phones));
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
