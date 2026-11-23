<?php

namespace Tests\Feature;

use App\Jobs\SendFeedbackSmsJob;
use App\Jobs\SendOrderReadySmsJob;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_ready_dispatches_the_order_ready_sms_job(): void
    {
        Queue::fake();

        $product = Product::factory()->create();

        $order = app(OrderService::class)->place([
            'order_type' => 'pickup',
            'payment_method' => 'cash',
            'customer_name' => 'John Doe',
            'customer_phone' => '0917 123 4567',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ]);

        $service = app(OrderService::class);
        $service->confirm($order, User::factory()->admin()->create());
        $service->startPreparing($order, User::factory()->admin()->create());
        $service->markReady($order, User::factory()->admin()->create());

        Queue::assertPushed(SendOrderReadySmsJob::class, fn ($job) => $job->orderId === $order->id);
    }

    public function test_completing_an_order_dispatches_the_feedback_sms_job(): void
    {
        Queue::fake();

        $product = Product::factory()->create();

        $order = app(OrderService::class)->place([
            'order_type' => 'pickup',
            'payment_method' => 'cash',
            'customer_name' => 'John Doe',
            'customer_phone' => '0917 123 4567',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ]);

        $service = app(OrderService::class);
        $service->confirm($order, User::factory()->admin()->create());
        $service->startPreparing($order, User::factory()->admin()->create());
        $service->markReady($order, User::factory()->admin()->create());
        $service->markPaid($order, User::factory()->admin()->create());
        $service->complete($order, User::factory()->admin()->create());

        Queue::assertPushed(SendFeedbackSmsJob::class, fn ($job) => $job->orderId === $order->id);
    }
}
