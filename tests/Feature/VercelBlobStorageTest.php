<?php

namespace Tests\Feature;

use App\Livewire\Products\Form;
use App\Livewire\Settings\Edit;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\StorefrontService;
use App\Support\BlobStorage;
use App\Support\GcashQr;
use App\Support\PublicMediaUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class VercelBlobStorageTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.blob.media.driver' => 'blob',
            'services.blob.media.token' => 'vercel_blob_rw_publicstore_publicsecret',
            'services.blob.media.store_id' => null,
            'services.blob.media.base_url' => null,
            'services.blob.legacy_media_url' => null,
            'services.blob.proofs.driver' => 'blob',
            'services.blob.proofs.token' => 'vercel_blob_rw_receiptstore_privatesecret',
            'services.blob.proofs.store_id' => null,
            'services.blob.proofs.base_url' => null,
            'services.blob.legacy_proofs.url' => null,
            'services.blob.legacy_proofs.key' => null,
        ]);
    }

    public function test_a_public_upload_posts_to_blob_and_stores_the_absolute_url(): void
    {
        Http::fake([
            'vercel.com/api/blob/*' => Http::response([
                'url' => 'https://publicstore.public.blob.vercel-storage.com/products/photo.png',
                'pathname' => 'products/photo.png',
                'contentType' => 'image/png',
                'contentDisposition' => 'inline',
                'etag' => 'etag-1',
            ]),
        ]);

        $url = BlobStorage::store($this->png('photo.png'), 'products', BlobStorage::MEDIA, 'public');

        $this->assertSame('https://publicstore.public.blob.vercel-storage.com/products/photo.png', $url);

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && str_starts_with($request->url(), 'https://vercel.com/api/blob/?pathname=products%2F')
                && $request->hasHeader('Authorization', 'Bearer vercel_blob_rw_publicstore_publicsecret')
                && $request->hasHeader('x-api-version', '13')
                && $request->hasHeader('x-vercel-blob-store-id', 'publicstore')
                && $request->hasHeader('x-vercel-blob-access', 'public')
                && $request->hasHeader('x-content-type', 'image/png');
        });
    }

    public function test_a_failed_blob_upload_returns_an_error(): void
    {
        Http::fake([
            'vercel.com/api/blob/*' => Http::response(['error' => ['message' => 'nope']], 500),
        ]);

        $this->expectException(\RuntimeException::class);

        BlobStorage::store($this->png('photo.png'), 'products', BlobStorage::MEDIA, 'public');
    }

    public function test_blob_delete_posts_only_the_confirmed_url(): void
    {
        Http::fake([
            'vercel.com/api/blob/delete' => Http::response([]),
        ]);

        $url = 'https://receiptstore.private.blob.vercel-storage.com/order-proofs/receipt.png';

        BlobStorage::delete($url, BlobStorage::PROOFS, 'local');

        Http::assertSent(function ($request) use ($url) {
            return $request->method() === 'POST'
                && $request->url() === 'https://vercel.com/api/blob/delete'
                && $request['urls'] === [$url]
                && $request->hasHeader('x-vercel-blob-store-id', 'receiptstore');
        });
    }

    public function test_a_legacy_relative_asset_is_not_sent_to_the_blob_delete_api(): void
    {
        Http::fake();

        config(['services.blob.legacy_media_url' => 'https://old.example/products']);

        BlobStorage::delete('products/old-photo.jpg', BlobStorage::MEDIA, 'public');

        Http::assertNothingSent();
    }

    public function test_a_private_receipt_is_fetched_with_the_private_token(): void
    {
        Http::fake([
            'receiptstore.private.blob.vercel-storage.com/*' => Http::response('receipt bytes', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $receipt = BlobStorage::read(
            'https://receiptstore.private.blob.vercel-storage.com/order-proofs/receipt.png',
            BlobStorage::PROOFS,
            'local',
        );

        $this->assertSame('image/png', $receipt['content_type']);
        $this->assertSame('receipt bytes', stream_get_contents($receipt['stream']));
        fclose($receipt['stream']);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer vercel_blob_rw_receiptstore_privatesecret'));
    }

    public function test_a_legacy_supabase_receipt_uses_the_rest_api_without_s3(): void
    {
        config([
            'services.blob.legacy_proofs.url' => 'https://project.supabase.co',
            'services.blob.legacy_proofs.bucket' => 'proofs',
            'services.blob.legacy_proofs.key' => 'service-role-key',
        ]);

        Http::fake([
            'project.supabase.co/*' => Http::response('legacy receipt', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $receipt = BlobStorage::read('order-proofs/receipt.png', BlobStorage::PROOFS, 'local');

        $this->assertSame('image/jpeg', $receipt['content_type']);
        $this->assertSame('legacy receipt', stream_get_contents($receipt['stream']));
        fclose($receipt['stream']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://project.supabase.co/storage/v1/object/proofs/order-proofs/receipt.png'
                && $request->hasHeader('Authorization', 'Bearer service-role-key')
                && $request->hasHeader('apikey', 'service-role-key');
        });
    }

    public function test_legacy_media_paths_still_render_after_switching_to_blob(): void
    {
        config(['services.blob.legacy_media_url' => 'https://old.example/products']);

        $product = new Product(['image_path' => 'products/menu.jpg']);
        Setting::set('gcash_qr_path', 'store-settings/qr.png');

        $this->assertSame('https://old.example/products/products/menu.jpg', $product->image_src);
        $this->assertSame('https://old.example/products/store-settings/qr.png', GcashQr::url());
    }

    public function test_the_gcash_qr_route_redirects_to_the_absolute_blob_url(): void
    {
        $url = 'https://publicstore.public.blob.vercel-storage.com/store-settings/qr.png';

        Setting::set('gcash_qr_path', $url);

        $this->get(route('gcash.qr'))->assertRedirect($url);
        $this->assertSame($url, app(StorefrontService::class)->gcashQrUrl());
    }

    public function test_a_qr_upload_uses_blob_and_updates_settings_only_after_success(): void
    {
        $url = 'https://publicstore.public.blob.vercel-storage.com/store-settings/qr.png';

        Http::fake([
            'vercel.com/api/blob/*' => Http::response([
                'url' => $url,
                'pathname' => 'store-settings/qr.png',
                'contentType' => 'image/png',
                'contentDisposition' => 'inline',
                'etag' => 'etag-2',
            ]),
        ]);

        $admin = User::factory()->admin()->create(['password' => 'secret123']);

        Livewire::actingAs($admin)
            ->test(Edit::class)
            ->set('confirmPassword', 'secret123')
            ->set('gcashQr', UploadedFile::fake()->createWithContent('qr.png', base64_decode(self::PNG)))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($url, Setting::get('gcash_qr_path'));
        $this->assertSame($url, PublicMediaUrl::resolve($url));
    }

    public function test_a_menu_photo_uses_blob_and_deletes_the_old_object_after_saving(): void
    {
        $newUrl = 'https://publicstore.public.blob.vercel-storage.com/products/new.png';
        $oldUrl = 'https://publicstore.public.blob.vercel-storage.com/products/old.png';

        Http::fake([
            'vercel.com/api/blob/delete' => Http::response([]),
            'vercel.com/api/blob/*' => Http::response([
                'url' => $newUrl,
                'pathname' => 'products/new.png',
                'contentType' => 'image/png',
                'contentDisposition' => 'inline',
                'etag' => 'etag-3',
            ]),
        ]);

        $product = Product::factory()->create(['image_path' => $oldUrl]);
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Form::class, ['product' => $product])
            ->set('image', $this->png('new.png'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($newUrl, $product->fresh()->image_path);
        $this->assertSame($newUrl, $product->fresh()->image_src);

        Http::assertSent(fn ($request) => $request->url() === 'https://vercel.com/api/blob/delete'
            && $request['urls'] === [$oldUrl]);
    }

    public function test_the_proof_route_streams_a_private_blob_receipt(): void
    {
        $url = 'https://receiptstore.private.blob.vercel-storage.com/order-proofs/receipt.png';

        Http::fake([
            'receiptstore.private.blob.vercel-storage.com/*' => Http::response('receipt bytes', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $order = Order::factory()->create(['payment_proof_path' => $url]);

        $this->get(route('orders.proof.public', $order->public_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer vercel_blob_rw_receiptstore_privatesecret'));
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG));
    }
}
