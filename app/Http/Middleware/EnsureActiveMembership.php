<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->membership_status === 'active' || $user->hasRole('admin', 'chairman', 'treasurer')) {
            return $next($request);
        }

        if ($request->routeIs(
            'membership.*',
            'profile.*',
            'preferences.*',
            'password.*',
            'verification.*',
            'logout'
        )) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akaun anda perlu diluluskan sebagai ahli aktif terlebih dahulu.',
            ], 403);
        }

        return to_route('membership.apply')->with(
            'status',
            'Sila lengkapkan atau tunggu kelulusan permohonan keahlian sebelum menggunakan fungsi ini.'
        );
    }
}
