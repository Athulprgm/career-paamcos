<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('superadmin.login')
                ->with('error', 'Please log in to access this area.');
        }

        $user = auth()->user();

        // Must have agent role
        if (! $user->isAgent()) {
            abort(403, 'Access denied. Agent privileges required.');
        }

        $agent = $user->agentProfile;
        if (! $agent) {
            abort(403, 'Access denied. No agent profile linked to this account.');
        }

        if (! $agent->isActive()) {
            if ($agent->isPending()) {
                abort(403, 'Access denied. Your agent account is pending verification and acceptance by the Super Admin.');
            }

            abort(403, 'Access denied. Your agent account is currently inactive.');
        }

        // Agents are free-field agents and do not require a particular branch assignment

        return $next($request);
    }
}
