<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderProofController extends Controller
{
    public function show(Order $order): StreamedResponse
    {
        return $this->stream($order);
    }

    /** Same receipt, scoped to the customer holding the order token. */
    public function showByToken(string $token): StreamedResponse
    {
        $order = Order::query()->where('public_token', $token)->firstOrFail();

        return $this->stream($order);
    }

    private function stream(Order $order): StreamedResponse
    {
        abort_unless(filled($order->payment_proof_path), 404);

        abort_unless(Storage::disk('local')->exists($order->payment_proof_path), 404);

        return Storage::disk('local')->response($order->payment_proof_path);
    }
}
