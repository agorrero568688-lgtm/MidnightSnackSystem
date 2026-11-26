<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\GcashQr;
use App\Support\PublicMediaUrl;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GcashQrController extends Controller
{
    public function show(): StreamedResponse|RedirectResponse
    {
        $value = trim((string) Setting::get('gcash_qr_path'));

        abort_if($value === '', 404);

        // Settings may store a full public/media URL instead of a disk path.
        if (GcashQr::isAbsolute($value)) {
            return redirect()->away($value);
        }

        $path = GcashQr::pathFor($value);

        abort_if($path === null, 404);

        try {
            // Remote media is addressed directly; no HEAD request is made.
            if (PublicMediaUrl::isRemote(GcashQr::diskName())) {
                $url = PublicMediaUrl::remote($path, GcashQr::diskName());

                abort_if($url === '', 404);

                return redirect()->away($url);
            }

            $disk = GcashQr::disk();

            abort_unless($disk->exists($path), 404);

            return $disk->response($path, null, [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        } catch (Throwable) {
            abort(404);
        }
    }
}
