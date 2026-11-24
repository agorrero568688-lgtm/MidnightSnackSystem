<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Services\SmsGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_when_enabled_and_configured(): void
    {
        Http::fake();

        Setting::setMany([
            'sms_enabled' => '1',
            'sms_gateway_url' => 'https://api.gateway.com/sms',
            'sms_gateway_key' => 'secret-key',
        ]);

        $sent = app(SmsGateway::class)->send('0917 123 4567', 'Hello');

        $this->assertTrue($sent);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.gateway.com/sms'
            && $request['to'] === '09171234567'
            && $request['message'] === 'Hello'
            && $request['sender'] === 'Diding SMS');
    }

    public function test_it_does_nothing_when_disabled(): void
    {
        Http::fake();

        Setting::set('sms_enabled', '0');

        $sent = app(SmsGateway::class)->send('09171234567', 'Hello');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_it_does_nothing_without_a_url(): void
    {
        Http::fake();

        Setting::setMany([
            'sms_enabled' => '1',
            'sms_gateway_url' => '',
        ]);

        $sent = app(SmsGateway::class)->send('09171234567', 'Hello');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_it_returns_false_for_empty_numbers(): void
    {
        Http::fake();

        Setting::setMany([
            'sms_enabled' => '1',
            'sms_gateway_url' => 'https://api.gateway.com/sms',
        ]);

        $sent = app(SmsGateway::class)->send('', 'Hello');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_ready_and_feedback_messages_contain_store_context(): void
    {
        $sms = app(SmsGateway::class);
        $order = Order::factory()->create(['customer_phone' => '09171234567']);

        $ready = $sms->readyMessage($order);
        $feedback = $sms->feedbackMessage($order);
        $abandoned = $sms->abandonedMessage($order);

        $this->assertStringContainsString('ready to pick up', $ready);
        $this->assertStringContainsString('Saw you earlier', $feedback);
        $this->assertStringContainsString('upload your GCash receipt', $abandoned);
        $this->assertStringContainsString(route('orders.track', ['token' => $order->public_token]), $abandoned);
    }
}
