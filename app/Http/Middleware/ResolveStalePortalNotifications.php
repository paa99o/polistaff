<?php

namespace App\Http\Middleware;

use App\Services\PortalNotificationReconciler;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStalePortalNotifications
{
    public function __construct(private PortalNotificationReconciler $reconciler) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $this->reconciler->reconcileFor($request->user());
        }

        return $next($request);
    }
}
