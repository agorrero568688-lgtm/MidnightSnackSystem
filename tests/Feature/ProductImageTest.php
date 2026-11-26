<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery;
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

    public function test_photo_disk_is_always_the_configured_public_disk(): void
    {
        $this->assertSame('public', (new Product)->photoDisk());

        config()->set('filesystems.disks.public.driver', 's3');

        $this->assertSame('public', (new Product)->photoDisk());
    }

    public function test_images_are_resolved_from_the_configured_public_disk(): void
    {
        config([
            'filesystems.disks.public.driver' => 's3',
            'filesystems.disks.public.url' => 'https://media.example.com/products',
        ]);
        Storage::fake('public');
        Storage::disk('public')->put('products/photo.jpg', 'photo');

        $product = new Product(['image_path' => 'products/photo.jpg']);

        $this->assertSame('https://media.example.com/products/products/photo.jpg', $product->image_src);
    }

    public function test_blank_values_resolve_to_null(): void
    {
        Storage::fake('public');

        $this->assertNull((new Product(['image_path' => '']))->image_src);
        $this->assertNull((new Product(['image_path' => '   ']))->image_src);
    }

    public function test_an_s3_public_disk_builds_urls_without_touching_the_disk(): void
    {
        config([
            'filesystems.disks.public.driver' => 's3',
            'filesystems.disks.public.url' => 'https://media.example.com/media',
        ]);

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldNotReceive('exists');
        $disk->shouldNotReceive('lastModified');
        Storage::set('public', $disk);

        $product = new Product(['image_path' => 'products/photo.jpg']);

        $this->assertSame(
            'https://media.example.com/media/products/photo.jpg',
            $product->image_src,
        );
    }

    public function test_bundled_art_uses_asset_url_even_when_public_disk_is_s3(): void
    {
        config([
            'filesystems.disks.public.driver' => 's3',
            'filesystems.disks.public.url' => 'https://media.example.com/media',
        ]);

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldNotReceive('exists');
        $disk->shouldNotReceive('lastModified');
        Storage::set('public', $disk);

        $product = new Product(['image_path' => 'public/images/products/chicken-lomi.jpg']);

        $this->assertSame(
            asset('images/products/chicken-lomi.jpg'),
            $product->image_src,
        );
    }
}
