<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GcashQrController extends Controller
{
    public function show(): StreamedResponse
    {
        $path = Setting::get('gcash_qr_path');

        abort_if(blank($path), 404);

        $disk = Storage::disk('public');

        try {
            abort_unless($disk->exists($path), 404);
        } catch (Throwable) {
            abort(404);
        }

        try {
            return $disk->response($path, null, [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        } catch (Throwable) {
            abort(404);
        }
    }
}
