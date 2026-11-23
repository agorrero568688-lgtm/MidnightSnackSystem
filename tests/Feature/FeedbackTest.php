<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_feedback_page_renders(): void
    {
        $order = Order::factory()->create(['customer_phone' => '09171234567']);

        $this->get('/feedback/'.$order->public_token)
            ->assertOk()
            ->assertSee('Rate your snack')
            ->assertSee('Excellent')
            ->assertSee('Bad');
    }

    public function test_a_feedback_record_is_created(): void
    {
        $order = Order::factory()->create(['customer_phone' => '0917 123 4567']);

        $this->post('/feedback/'.$order->public_token, ['rating' => 4])
            ->assertRedirect('/feedback/'.$order->public_token);

        $this->assertDatabaseHas('customer_feedback', [
            'order_id' => $order->id,
            'phone' => '09171234567',
            'rating' => 4,
        ]);
    }

    public function test_invalid_ratings_are_rejected(): void
    {
        $order = Order::factory()->create();

        $this->post('/feedback/'.$order->public_token, ['rating' => 8]);

        $this->assertDatabaseCount('customer_feedback', 0);
    }

    public function test_unknown_tokens_404(): void
    {
        $this->get('/feedback/notatoken')->assertStatus(404);
        $this->post('/feedback/notatoken', ['rating' => 4])->assertStatus(404);
    }
}
