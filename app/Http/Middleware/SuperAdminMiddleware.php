<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('superadmin.login')
                ->with('error', 'Please log in to access this area.');
        }

        if (! auth()->user()->isSuperAdmin()) {
            if ($request->routeIs('superadmin.dashboard') || $request->is('superadmin/dashboard')) {
                if (auth()->user()->isBranchAdmin()) {
                    return redirect()->route('branch-admin.dashboard');
                }
                if (auth()->user()->isAgent()) {
                    return redirect()->route('agent.dashboard');
                }
                if (auth()->user()->isStaff()) {
                    return redirect()->route('staff.dashboard');
                }
            }

            abort(403, 'Access denied. Super Admin privileges required.');
        }

        auth()->user()->ensureSuperAdminAgentProfile();

        return $next($request);
    }
}
