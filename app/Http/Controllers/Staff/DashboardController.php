<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\Lead;
use App\Models\Staff;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the minimal, role-tailored staff dashboard.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $staff = $user->staffProfile;

        // Strict branch isolation: null only for Super Admin
        $branchId = $user->isSuperAdmin() ? null : ($user->branch_id ?? $staff?->branch_id);

        // Permissions flags for actionable modules
        $canDeposits = $user->isAdmin() || $user->hasPermission('view-deposits') || $user->hasPermission('manage-deposits');
        $canCommissions = $user->isAdmin() || $user->hasPermission('view-commissions') || $user->hasPermission('approve-commissions');
        $canLeads = $user->isAdmin() || $user->hasPermission('view-leads') || $user->hasPermission('manage-leads');
        $canStaff = $user->isAdmin() || $user->hasPermission('view-employees');
        $canReports = $user->isAdmin() || $user->hasPermission('view-reports');

        $pendingDepositsCount = $canDeposits
            ? Deposit::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', Deposit::STATUS_PENDING_APPROVAL)
                ->whereNull('verified_at')
                ->count()
            : 0;

        $pendingCommissionsCount = $canCommissions
            ? Commission::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', Commission::STATUS_PENDING)
                ->count()
            : 0;

        $pendingCommissionsAmount = $canCommissions
            ? (float) Commission::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', Commission::STATUS_PENDING)
                ->sum('commission_amount')
            : 0.0;

        $branchLeadsCount = $canLeads
            ? Lead::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count()
            : 0;

        $branchStaffCount = $canStaff
            ? Staff::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->active()->count()
            : 0;

        $todayProcessedCount = AuditLog::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $pendingDepositsList = $canDeposits
            ? Deposit::with(['branch', 'customer', 'agent', 'scheme'])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', Deposit::STATUS_PENDING_APPROVAL)
                ->whereNull('verified_at')
                ->latest()
                ->take(5)
                ->get()
            : collect();

        $pendingCommissionsList = $canCommissions
            ? Commission::with(['agent', 'deposit', 'collection'])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', Commission::STATUS_PENDING)
                ->latest()
                ->take(5)
                ->get()
            : collect();

        $recentActivities = AuditLog::where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        return view('staff.dashboard', compact(
            'user',
            'staff',
            'canDeposits',
            'canCommissions',
            'canLeads',
            'canStaff',
            'canReports',
            'pendingDepositsCount',
            'pendingCommissionsCount',
            'pendingCommissionsAmount',
            'branchLeadsCount',
            'branchStaffCount',
            'todayProcessedCount',
            'pendingDepositsList',
            'pendingCommissionsList',
            'recentActivities'
        ));
    }
}
