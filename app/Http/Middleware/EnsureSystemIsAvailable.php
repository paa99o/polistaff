<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemIsAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('system_settings') || SystemSetting::getValue('maintenance_enabled', '0') !== '1') {
            return $next($request);
        }

        if ($request->user()?->hasRole('admin') || $request->is('login') || $request->routeIs('logout', 'password.*')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Sistem sedang diselenggara.'], 503);
        }

        return response()->view('maintenance', [
            'message' => SystemSetting::getValue('maintenance_message', 'Sistem sedang diselenggara buat sementara waktu.'),
            'estimatedEnd' => SystemSetting::getValue('maintenance_estimated_end'),
            'contactEmail' => SystemSetting::getValue('contact_email', 'admin@polibest.test'),
        ], 503);
    }
}
