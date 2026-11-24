<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    /**
     * Run the abandoned-order reminder sweep. Vercel sends the CRON_SECRET
     * as a bearer token; an external cron can do the same.
     */
    public function abandonedReminders(Request $request): JsonResponse
    {
        $secret = (string) config('services.cron.secret');

        abort_unless(
            $secret !== '' && hash_equals($secret, (string) $request->bearerToken()),
            403,
        );

        $exitCode = Artisan::call('snack:abandoned-reminders');

        return response()->json([
            'ok' => $exitCode === 0,
            'output' => Artisan::output(),
        ]);
    }
}
