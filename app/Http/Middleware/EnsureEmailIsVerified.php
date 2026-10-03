<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Authenticated users may keep a serialized model in their session. Refresh it
        // so admin approval or membership changes made in another session take effect.
        $user?->refresh();

        if (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->routeIs('verification.*', 'logout', 'profile.show', 'profile.edit', 'profile.update')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Alamat emel belum disahkan.'], 403);
        }

        return to_route('verification.notice');
    }
}
