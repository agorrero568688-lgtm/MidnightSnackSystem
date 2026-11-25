<?php

namespace Tests\Feature;

use App\Livewire\Products\Form;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_menu_photo_up_to_10_mb_is_accepted(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->assertSee('up to 10 MB')
            ->set('name', 'Large menu photo')
            ->set('sku', 'PHOTO-10MB')
            ->set('unit', 'serving')
            ->set('cost_price', '10.00')
            ->set('selling_price', '20.00')
            ->set('image', $this->fakePhoto(10 * 1024 * 1024))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('products.index'));

        $product = Product::query()->where('sku', 'PHOTO-10MB')->sole();

        $this->assertNotEmpty($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_a_menu_photo_larger_than_10_mb_is_rejected(): void
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
            ->set('image', $this->fakePhoto(10 * 1024 * 1024 + 1))
            ->call('save')
            ->assertHasErrors(['image' => 'max']);

        $this->assertDatabaseMissing('products', ['sku' => 'PHOTO-TOO-BIG']);
    }

    public function test_a_menu_photo_upload_goes_to_the_s3_disk_in_production(): void
    {
        config()->set('app.env', 'production');
        Storage::fake('s3');
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
        Storage::disk('s3')->assertExists($product?->image_path);
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
