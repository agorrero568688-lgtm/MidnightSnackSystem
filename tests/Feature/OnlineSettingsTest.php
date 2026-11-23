<?php

namespace Tests\Feature;

use App\Livewire\Settings\Edit;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_save_the_online_ordering_settings(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Edit::class)
            ->set('deliveryFee', '49.50')
            ->set('freeDeliveryThreshold', '500')
            ->set('minimumOrderTotal', '150')
            ->set('ordersPaused', true)
            ->set('deliveryEnabled', true)
            ->set('pickupEnabled', false)
            ->set('gcashName', "Diding's Midnight Snack")
            ->set('gcashNumber', '0917 123 4567')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('49.50', Setting::get('delivery_fee'));
        $this->assertSame('500.00', Setting::get('free_delivery_threshold'));
        $this->assertSame('150.00', Setting::get('minimum_order_total'));
        $this->assertSame('1', Setting::get('orders_paused'));
        $this->assertSame('0', Setting::get('pickup_enabled'));
        $this->assertSame('0917 123 4567', Setting::get('gcash_number'));
    }

    public function test_the_telegram_test_message_reports_success(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Edit::class)
            ->set('telegramBotToken', '123456:ABC-DEF')
            ->set('telegramChatId', '-1001234567890')
            ->call('testTelegram')
            ->assertSet('telegramStatus', 'Test message sent. Check the staff group.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === '-1001234567890');
    }

    public function test_the_telegram_test_message_reports_failure(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false], 401),
        ]);

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Edit::class)
            ->set('telegramBotToken', 'bad-token')
            ->set('telegramChatId', '-1001234567890')
            ->call('testTelegram')
            ->assertSet('telegramStatus', 'Could not send. Check the bot token and chat ID, and make sure the bot is in the group.');
    }

    public function test_admin_can_save_the_free_delivery_barangays(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Edit::class)
            ->set('deliveryFreeBarangays', "Magosilom\nLininti-an")
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame("Magosilom\nLininti-an", Setting::get('delivery_free_barangays'));
    }

    public function test_admin_can_save_the_sms_settings(): void
    {
        Http::fake();

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Edit::class)
            ->set('smsEnabled', true)
            ->set('smsGatewayUrl', 'https://api.gateway.com/sms')
            ->set('smsGatewayKey', 'secret-key')
            ->set('smsSenderName', 'Diding SMS')
            ->set('smsReminderMinutes', '20')
            ->set('smsFeedbackEveryN', '2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('1', Setting::get('sms_enabled'));
        $this->assertSame('https://api.gateway.com/sms', Setting::get('sms_gateway_url'));
        $this->assertSame('secret-key', Setting::get('sms_gateway_key'));
        $this->assertSame('Diding SMS', Setting::get('sms_sender_name'));
        $this->assertSame('20', Setting::get('sms_reminder_minutes'));
        $this->assertSame('2', Setting::get('sms_feedback_every_n'));
    }
}
