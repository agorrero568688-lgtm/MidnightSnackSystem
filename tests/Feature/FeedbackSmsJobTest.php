<?php

namespace Tests\Feature;

use App\Jobs\SendFeedbackSmsJob;
use App\Models\CustomerFeedback;
use App\Models\Order;
use App\Models\Setting;
use App\Services\SmsGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FeedbackSmsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_the_feedback_message(): void
    {
        Http::fake();

        Setting::setMany([
            'sms_enabled' => '1',
            'sms_gateway_url' => 'https://api.gateway.com/sms',
        ]);

        $order = Order::factory()->create(['customer_phone' => '0917 123 4567']);

        app()->make(SendFeedbackSmsJob::class, ['orderId' => $order->id])->handle(app(SmsGateway::class));

        Http::assertSent(fn ($request) => $request['to'] === '09171234567'
            && str_contains($request['message'], 'Saw you earlier'));
    }

    public function test_job_respects_the_per_day_every_n_setting(): void
    {
        Http::fake();

        Setting::setMany([
            'sms_enabled' => '1',
            'sms_gateway_url' => 'https://api.gateway.com/sms',
            'sms_feedback_every_n' => '1',
        ]);

        Order::factory()->create(['customer_phone' => '09171234567']);
        CustomerFeedback::query()->create(['phone' => '09171234567', 'rating' => 4]);

        $order = Order::query()->sole();

        app()->make(SendFeedbackSmsJob::class, ['orderId' => $order->id])->handle(app(SmsGateway::class));

        Http::assertNothingSent();
    }

    public function test_job_does_nothing_without_a_phone(): void
    {
        Http::fake();

        Setting::setMany([
            'sms_enabled' => '1',
            'sms_gateway_url' => 'https://api.gateway.com/sms',
        ]);

        Order::factory()->create(['customer_phone' => '']);

        app()->make(SendFeedbackSmsJob::class, ['orderId' => Order::query()->sole()->id])->handle(app(SmsGateway::class));

        Http::assertNothingSent();
    }
}
