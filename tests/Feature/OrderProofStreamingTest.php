<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class OrderProofStreamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_receipt_route_streams_without_extra_remote_metadata_requests(): void
    {
        $order = Order::factory()->create([
            'payment_proof_path' => 'order-proofs/receipt.png',
        ]);

        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'receipt bytes');
        rewind($stream);

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldNotReceive('exists');
        $disk->shouldNotReceive('response');
        $disk->shouldReceive('readStream')
            ->once()
            ->with('order-proofs/receipt.png')
            ->andReturn($stream);
        Storage::set('local', $disk);

        $this->get(route('orders.proof.public', $order->public_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_an_unreadable_receipt_returns_404_instead_of_a_server_error(): void
    {
        $order = Order::factory()->create([
            'payment_proof_path' => 'order-proofs/missing.png',
        ]);

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('readStream')
            ->once()
            ->with('order-proofs/missing.png')
            ->andThrow(new RuntimeException('Object not found'));
        Storage::set('local', $disk);

        $this->get(route('orders.proof.public', $order->public_token))
            ->assertNotFound();
    }
}
