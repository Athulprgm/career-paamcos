<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BranchAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('superadmin.login')
                ->with('error', 'Please log in to access this area.');
        }

        $user = auth()->user();

        // Only Branch Admins may access this area
        if (! $user->isBranchAdmin()) {
            abort(403, 'Access denied. Branch Admin privileges required.');
        }

        // A Branch Admin without an assigned branch must be denied —
        // they must not accidentally see organisation-wide data.
        if (! $user->branch_id) {
            abort(403, 'Access denied. No branch has been assigned to your account. Contact the Super Admin.');
        }

        return $next($request);
    }
}
