<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    public function test_bundled_menu_art_resolves_to_a_public_asset_url(): void
    {
        Storage::fake('public');

        $product = new Product(['image_path' => 'images/products/chicken-lomi.jpg']);

        $this->assertFileExists(public_path('images/products/chicken-lomi.jpg'));
        $this->assertSame(
            asset('images/products/chicken-lomi.jpg'),
            $product->image_src,
        );
    }

    public function test_external_and_data_uris_are_returned_as_is(): void
    {
        Storage::fake('public');

        foreach ([
            'https://cdn.example.com/photo.jpg',
            'http://cdn.example.com/photo.jpg',
            '//cdn.example.com/photo.jpg',
            'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
        ] as $path) {
            $product = new Product(['image_path' => $path]);

            $this->assertSame($path, $product->image_src, "Expected [{$path}] to pass through unchanged.");
        }
    }

    public function test_legacy_storage_paths_are_stripped_and_resolved(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/menu-photo.jpg', 'photo-bytes');

        $product = new Product(['image_path' => 'storage/products/menu-photo.jpg']);

        $this->assertSame(Storage::disk('public')->url('products/menu-photo.jpg'), $product->image_src);
    }

    public function test_missing_files_resolve_to_null_so_the_fallback_can_render(): void
    {
        Storage::fake('public');

        $product = new Product(['image_path' => 'storage/products/does-not-exist.jpg']);

        $this->assertNull($product->image_src);
    }

    public function test_photo_disk_is_s3_in_production(): void
    {
        config()->set('app.env', 'production');

        $this->assertSame('s3', (new Product)->photoDisk());

        config()->set('app.env', 'testing');

        $this->assertSame('public', (new Product)->photoDisk());
    }

    public function test_production_images_are_resolved_from_the_s3_disk(): void
    {
        config()->set('app.env', 'production');
        Storage::fake('s3');
        Storage::disk('s3')->put('products/photo.jpg', 'photo');

        $product = new Product(['image_path' => 'products/photo.jpg']);

        $this->assertSame(Storage::disk('s3')->url('products/photo.jpg'), $product->image_src);
    }

    public function test_blank_values_resolve_to_null(): void
    {
        Storage::fake('public');

        $this->assertNull((new Product(['image_path' => '']))->image_src);
        $this->assertNull((new Product(['image_path' => '   ']))->image_src);
    }
}
