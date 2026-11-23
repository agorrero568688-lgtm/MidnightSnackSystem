<?php

namespace App\Http\Controllers;

use App\Models\CustomerFeedback;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function show(string $token): View
    {
        $order = Order::query()->where('public_token', $token)->firstOrFail();

        return view('orders.feedback', [
            'order' => $order,
            'alreadyFeedback' => CustomerFeedback::query()->where('phone', $this->digits($order->customer_phone))->exists(),
            'token' => $token,
        ]);
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $order = Order::query()->where('public_token', $token)->firstOrFail();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        CustomerFeedback::query()->create([
            'order_id' => $order->id,
            'phone' => $this->digits($order->customer_phone),
            'rating' => (int) $validated['rating'],
        ]);

        return redirect()
            ->route('feedback.show', $token)
            ->with('status', 'Thanks! Your feedback helps us serve you better.');
    }

    private function digits(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone) ?? '';
    }
}
