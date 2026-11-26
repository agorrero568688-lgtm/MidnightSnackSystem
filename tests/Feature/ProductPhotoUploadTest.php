<?php

namespace Tests\Feature;

use App\Livewire\Products\Form;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ProductPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_menu_photo_up_to_4_mb_is_accepted(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->assertSee('up to 4 MB')
            ->set('name', 'Large menu photo')
            ->set('sku', 'PHOTO-4MB')
            ->set('unit', 'serving')
            ->set('cost_price', '10.00')
            ->set('selling_price', '20.00')
            ->set('image', $this->fakePhoto(4 * 1024 * 1024))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('products.index'));

        $product = Product::query()->where('sku', 'PHOTO-4MB')->sole();

        $this->assertNotEmpty($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_a_failed_menu_photo_upload_does_not_create_a_product(): void
    {
        Storage::fake('public');

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldIgnoreMissing();
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::set('public', $disk);

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->set('name', 'Failed menu photo')
            ->set('sku', 'PHOTO-FAILED')
            ->set('unit', 'serving')
            ->set('cost_price', '10.00')
            ->set('selling_price', '20.00')
            ->set('image', $this->fakePhoto(1024))
            ->call('save')
            ->assertHasErrors('image');

        $this->assertDatabaseMissing('products', ['sku' => 'PHOTO-FAILED']);
    }

    public function test_a_menu_photo_larger_than_4_mb_is_rejected(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->set('name', 'Too large menu photo')
            ->set('sku', 'PHOTO-TOO-BIG')
            ->set('unit', 'serving')
            ->set('cost_price', '10.00')
            ->set('selling_price', '20.00')
            ->set('image', $this->fakePhoto(4 * 1024 * 1024 + 1))
            ->call('save')
            ->assertHasErrors(['image' => 'max']);

        $this->assertDatabaseMissing('products', ['sku' => 'PHOTO-TOO-BIG']);
    }

    public function test_a_menu_photo_upload_goes_to_the_configured_public_disk_when_it_is_s3(): void
    {
        config([
            'filesystems.disks.public.driver' => 's3',
            'filesystems.disks.public.url' => 'https://media.example.com/products',
        ]);
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->set('name', 'Production menu photo')
            ->set('sku', 'PHOTO-PROD-S3')
            ->set('unit', 'serving')
            ->set('cost_price', '10.00')
            ->set('selling_price', '20.00')
            ->set('image', $this->fakePhoto(4 * 1024 * 1024))
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('sku', 'PHOTO-PROD-S3')->first();

        $this->assertNotNull($product);
        $this->assertStringStartsWith('products/', $product?->image_path);
        Storage::disk('public')->assertExists($product?->image_path);
    }

    /** Build a valid PNG padded to the requested byte size. */
    private function fakePhoto(int $bytes): File
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );

        $temp = tmpfile();

        fwrite($temp, $png.str_repeat("\0", max(0, $bytes - strlen($png))));

        return new File('menu-photo.png', $temp);
    }
}
