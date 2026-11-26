<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\StorefrontService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GcashQrTest extends TestCase
{
    use RefreshDatabase;

    /** A real 1x1 PNG so MIME sniffing returns image/png. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_the_qr_route_serves_the_stored_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('store-settings/qr.png', base64_decode(self::PNG));
        Setting::set('gcash_qr_path', 'store-settings/qr.png');

        $response = $this->get(route('gcash.qr'));

        $response->assertOk();
        $this->assertStringStartsWith('image/png', (string) $response->headers->get('content-type'));
    }

    public function test_the_qr_route_returns_404_when_no_qr_is_configured(): void
    {
        Storage::fake('public');
        Setting::set('gcash_qr_path', '');

        $this->get(route('gcash.qr'))->assertNotFound();
    }

    public function test_the_qr_route_returns_404_when_the_file_is_missing(): void
    {
        Storage::fake('public');
        Setting::set('gcash_qr_path', 'store-settings/missing.png');

        $this->get(route('gcash.qr'))->assertNotFound();
    }

    public function test_storefront_config_omits_the_qr_when_the_file_is_missing(): void
    {
        Storage::fake('public');
        Setting::set('gcash_qr_path', 'store-settings/missing.png');

        $this->assertNull(app(StorefrontService::class)->storeConfig()['gcash_qr_url']);
    }

    public function test_storefront_config_points_at_the_route_with_a_cache_buster(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('store-settings/qr.png', base64_decode(self::PNG));
        Setting::set('gcash_qr_path', 'store-settings/qr.png');

        $url = app(StorefrontService::class)->storeConfig()['gcash_qr_url'];

        $this->assertIsString($url);
        $this->assertStringStartsWith(route('gcash.qr', [], false), $url);
        $this->assertStringContainsString('v=', $url);
    }

    public function test_gcash_qr_url_returns_direct_urls(): void
    {
        Storage::fake('public');
        Setting::set('gcash_qr_path', 'https://media.example/qr.png');

        $this->assertSame('https://media.example/qr.png', app(StorefrontService::class)->gcashQrUrl());
    }

    public function test_gcash_qr_url_uses_the_s3_disk_in_production_without_exists_checks(): void
    {
        config(['app.env' => 'production']);
        Storage::fake('s3');
        Setting::set('gcash_qr_path', 'store-settings/qr.png');

        $url = app(StorefrontService::class)->gcashQrUrl();

        $this->assertSame(Storage::disk('s3')->url('store-settings/qr.png'), $url);
    }

    public function test_production_does_not_depend_on_the_local_public_disk(): void
    {
        config(['app.env' => 'production']);
        Storage::fake('public');
        Storage::fake('s3');
        Storage::disk('public')->put('store-settings/qr.png', base64_decode(self::PNG));
        Setting::set('gcash_qr_path', 'store-settings/qr.png');

        $url = app(StorefrontService::class)->gcashQrUrl();

        $this->assertSame(Storage::disk('s3')->url('store-settings/qr.png'), $url);
    }

    public function test_the_qr_route_redirects_to_the_media_url_in_production(): void
    {
        config(['app.env' => 'production']);
        Storage::fake('s3');
        Setting::set('gcash_qr_path', 'store-settings/qr.png');

        $this->get(route('gcash.qr'))
            ->assertRedirect(Storage::disk('s3')->url('store-settings/qr.png'));
    }

    public function test_gcash_qr_url_normalises_legacy_storage_prefixes(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('store-settings/qr.png', base64_decode(self::PNG));
        Setting::set('gcash_qr_path', 'storage/store-settings/qr.png');

        $url = app(StorefrontService::class)->gcashQrUrl();

        $this->assertStringStartsWith(route('gcash.qr', [], false), $url);
    }

    public function test_the_qr_route_redirects_when_the_setting_is_a_full_url(): void
    {
        Setting::set('gcash_qr_path', 'https://media.example/qr.png');

        $this->get(route('gcash.qr'))
            ->assertRedirect('https://media.example/qr.png');
    }
}
