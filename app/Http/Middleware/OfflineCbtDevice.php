<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Authenticates the offline CBT desktop app by its device key
 * (Authorization: Bearer <key>). Keys are created and revoked on the
 * Offline CBT admin page; only a SHA-256 hash is stored.
 */
class OfflineCbtDevice
{
    public function handle(Request $request, Closure $next)
    {
        $key = (string) $request->bearerToken();
        $device = $key !== ''
            ? DB::table('offline_cbt_devices')->where('token_hash', hash('sha256', $key))->whereNull('revoked_at')->first()
            : null;

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'This computer is not authorised. Create a device key on the portal (CBT → Offline CBT) and enter it in the app.'], 401);
        }

        DB::table('offline_cbt_devices')->where('id', $device->id)->update([
            'last_seen_at' => now(),
            'last_ip'      => $request->ip(),
            'app_version'  => mb_substr((string) $request->header('X-Offline-Cbt-Version', ''), 0, 32) ?: null,
        ]);

        $request->attributes->set('offline_cbt_device', $device);
        return $next($request);
    }
}
