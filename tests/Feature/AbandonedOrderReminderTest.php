<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\SendAbandonedOrderReminderJob;
use App\Models\Order;
use App\Models\Setting;
use App\Services\OrderService;
use App\Services\SmsGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AbandonedOrderReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_reminders_for_old_pending_orders(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'status' => OrderStatus::Pending->value,
            'placed_at' => now()->subMinutes(20),
        ]);

        $count = app(OrderService::class)->sendAbandonedReminders();

        $this->assertSame(1, $count);

        Queue::assertPushed(SendAbandonedOrderReminderJob::class, fn ($job) => $job->orderId === $order->id);
    }

    public function test_it_skips_recent_and_confirmed_orders(): void
    {
        Queue::fake();

        Order::factory()->create([
            'status' => OrderStatus::Pending->value,
            'placed_at' => now(), // Too recent
        ]);

        Order::factory()->create([
            'status' => OrderStatus::Confirmed->value,
            'placed_at' => now()->subDays(1),
        ]);

        $this->assertSame(0, app(OrderService::class)->sendAbandonedReminders());

        Queue::assertNothingPushed();
    }

    public function test_job_does_nothing_for_non_pending_orders(): void
    {
        Http::fake();

        Setting::setMany([
            'sms_enabled' => '1',
            'sms_gateway_url' => 'https://api.gateway.com/sms',
        ]);

        Order::factory()->create([
            'status' => OrderStatus::Confirmed->value,
            'customer_phone' => '09171234567',
        ]);

        app()->make(SendAbandonedOrderReminderJob::class, ['orderId' => Order::query()->sole()->id])
            ->handle(app(SmsGateway::class));

        Http::assertNothingSent();
    }

    public function test_job_sends_the_abandoned_message(): void
    {
        Http::fake();

        Setting::setMany([
            'sms_enabled' => '1',
            'sms_gateway_url' => 'https://api.gateway.com/sms',
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::Pending->value,
            'customer_phone' => '0917 123 4567',
        ]);

        app()->make(SendAbandonedOrderReminderJob::class, ['orderId' => $order->id])->handle(app(SmsGateway::class));

        Http::assertSent(fn ($request) => $request['to'] === '09171234567'
            && str_contains($request['message'], 'Still waiting')
            && str_contains($request['message'], route('orders.track', ['token' => $order->public_token])));
    }
}
