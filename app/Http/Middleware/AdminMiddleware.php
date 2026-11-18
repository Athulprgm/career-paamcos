<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('superadmin.login')
                ->with('error', 'Please log in to access this area.');
        }

        $user = auth()->user();

        // Must be an admin, agent, or staff member
        if (! ($user->isAdmin() || $user->isAgent() || $user->isStaff())) {
            abort(403, 'Access denied. Administrative, staff, or agent privileges required.');
        }

        if ($user->isSuperAdmin()) {
            $user->ensureSuperAdminAgentProfile();
        }

        // Deactivated staff account check
        if ($user->isStaff() && $user->staffProfile && $user->staffProfile->status === 'inactive') {
            auth()->logout();

            return redirect()->route('superadmin.login')
                ->with('error', 'Your staff account is currently inactive. Please contact the administrator.');
        }

        // Non-super-admin users (including Branch Admins) cannot access deposit schemes or mutate global rules/levels
        if (! $user->isSuperAdmin()) {
            if ($request->is('admin/deposit-schemes*')) {
                abort(403, 'Access denied: Super Admin privileges required to access or manage deposit schemes.');
            }

            if (
                $request->is('admin/commission-rules/create*') ||
                $request->is('admin/commission-rules/*/edit*') ||
                $request->is('admin/commission-rules/*/toggle-status*') ||
                ($request->is('admin/commission-rules*') && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']))
            ) {
                abort(403, 'Access denied: Super Admin privileges required to configure system schemes or rules.');
            }
        }

        // Non-administrative users (Staff & Agents) cannot manage personnel or organization settings
        if (! $user->isAdmin()) {
            if ($request->is('admin/staff*') || $request->is('admin/agents*') || ($request->is('superadmin/*') && ! $request->is('superadmin/profile*'))) {
                if ($user->isAgent() && $request->is('admin/agents/create*')) {
                    return redirect()->route('agent.agents.create');
                }

                abort(403, 'Access denied: Administrative privileges required to manage staff or agents.');
            }

            if ($request->is('admin/settings*') && ! $user->hasPermission('manage-settings')) {
                abort(403, 'Access denied: Administrative privileges or settings permission required to access system settings.');
            }
        }

        // Customer creation has been completely removed from the project
        if ($request->is('admin/customers/create*') || ($request->is('admin/customers') && $request->isMethod('POST'))) {
            abort(404, 'Customer registration has been removed.');
        }

        // Agents are prohibited from managing customers, administrative approvals, mutating deposits, reports, or settings
        if ($user->isAgent() && ! $user->isAdmin()) {
            if (
                $request->is('admin/customers*') ||
                $request->is('admin/deposits/create*') ||
                $request->is('admin/deposits/*/edit*') ||
                ($request->is('admin/deposits*') && ! $request->isMethod('GET')) ||
                $request->is('admin/deposits-history*') ||
                $request->is('admin/commissions/*/approve*') ||
                $request->is('admin/commissions/*/reject*') ||
                $request->is('admin/commissions/*/mark-payable*') ||
                $request->is('admin/commissions/*/pay*') ||
                $request->is('admin/career/requests/*/approve*') ||
                $request->is('admin/career/requests/*/reject*') ||
                $request->is('admin/reports*') ||
                $request->is('admin/settings*') ||
                $request->is('admin/leads*')
            ) {
                abort(403, 'Access denied: Agents are not permitted to perform this action.');
            }
        }

        // Staff least-privilege role & permission enforcement
        if ($user->isStaff() && ! $user->isAdmin() && ! $user->isAgent()) {
            // Deposits: staff must have view-deposits or manage-deposits, or create-deposits for creation
            if ($request->is('admin/deposits*') || $request->is('admin/deposits-history*')) {
                if ($request->is('admin/deposits/create*') || ($request->is('admin/deposits') && $request->isMethod('POST'))) {
                    if (! $user->hasPermission('create-deposits')) {
                        abort(403, 'Access denied: Create deposits privilege required.');
                    }
                } else {
                    if (! ($user->hasPermission('view-deposits') || $user->hasPermission('manage-deposits'))) {
                        abort(403, 'Access denied: Deposit management privileges required.');
                    }
                }
            }

            // Final approval / lifecycle activation requires branch administrator
            if (
                $request->is('admin/deposits/*/approve*') ||
                $request->is('admin/deposits/*/activate*') ||
                $request->is('admin/deposits/*/mature*') ||
                $request->is('admin/deposits/*/close*')
            ) {
                abort(403, 'Access denied: Final deposit approval requires branch administrator privileges.');
            }

            // Commissions: staff must have view-commissions or approve-commissions
            if ($request->is('admin/commissions*')) {
                if (! ($user->hasPermission('view-commissions') || $user->hasPermission('approve-commissions'))) {
                    abort(403, 'Access denied: Commission privileges required.');
                }

                // Approval / payment operations require approve-commissions
                if (
                    $request->is('admin/commissions/*/approve*') ||
                    $request->is('admin/commissions/*/reject*') ||
                    $request->is('admin/commissions/*/mark-payable*') ||
                    $request->is('admin/commissions/*/pay*')
                ) {
                    if (! $user->hasPermission('approve-commissions')) {
                        abort(403, 'Access denied: Commission approval privileges required.');
                    }
                }
            }

            // Reports: requires view-reports
            if ($request->is('admin/reports*')) {
                if (! $user->hasPermission('view-reports')) {
                    abort(403, 'Access denied: View reports privilege required.');
                }
                if ($request->is('admin/reports/*/export*') && ! $user->hasPermission('export-reports')) {
                    abort(403, 'Access denied: Export reports privilege required.');
                }
            }

        }

        return $next($request);
    }
}
