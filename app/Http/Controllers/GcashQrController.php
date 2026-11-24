<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GcashQrController extends Controller
{
    public function show(): StreamedResponse
    {
        $path = Setting::get('gcash_qr_path');

        abort_if(blank($path), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
