<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

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

        $path = (string) $order->payment_proof_path;

        try {
            // Neither exists() nor response() is safe here: both issue extra
            // HEAD requests on S3, which can turn a valid receipt into a 500.
            // readStream() opens the object once and throws if it is missing.
            $stream = Storage::disk('local')->readStream($path);
        } catch (Throwable) {
            abort(404);
        }

        if (! is_resource($stream)) {
            abort(404);
        }

        $filename = basename($path);
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $contentType = (new MimeTypes)->getMimeTypes($extension)[0] ?? 'application/octet-stream';

        $response = new StreamedResponse(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        });

        $response->headers->set('Content-Type', $contentType);
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition('inline', $filename),
        );

        return $response;
    }
}
