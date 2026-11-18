<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\PromotionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display operational notifications for the assigned branch.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $branchId = (int) $user->branch_id;
        $today = Carbon::today()->toDateString();
        $thirtyDaysFromNow = Carbon::today()->addDays(30)->toDateString();

        $typeFilter = $request->get('type', 'all');

        // 1. Upcoming Deposit Maturities
        $maturingDeposits = Deposit::where('branch_id', $branchId)
            ->where('status', Deposit::STATUS_ACTIVE)
            ->whereBetween('maturity_date', [$today, $thirtyDaysFromNow])
            ->with(['customer', 'scheme', 'agent'])
            ->orderBy('maturity_date')
            ->limit(20)
            ->get();

        // 2. Commission Updates (Pending approval or payable)
        $pendingCommissions = Commission::where('branch_id', $branchId)
            ->whereIn('status', [Commission::STATUS_PENDING, Commission::STATUS_PAYABLE])
            ->with(['agent', 'deposit'])
            ->latest()
            ->limit(20)
            ->get();

        // 3. Promotion Requests (Pending review)
        $promotionRequests = PromotionRequest::where('branch_id', $branchId)
            ->where('status', PromotionRequest::STATUS_PENDING_REVIEW)
            ->with(['agent', 'currentLevel', 'proposedLevel', 'requester'])
            ->latest()
            ->limit(20)
            ->get();

        // 4. Important Admin Notices (Recent high-priority operational audit logs)
        $adminNotices = AuditLog::where('branch_id', $branchId)
            ->whereIn('event', [
                'deposit_approved',
                'deposit_rejected',
                'deposit_closed',
                'kyc_approved',
                'kyc_rejected',
                'promotion_approved',
                'promotion_rejected',
            ])
            ->latest()
            ->limit(15)
            ->get();

        // Total count of actionable items
        $totalActionableCount = $maturingDeposits->count()
            + $pendingCommissions->count()
            + $promotionRequests->count();

        $counts = [
            'total' => $totalActionableCount,
            'maturities' => $maturingDeposits->count(),
            'commissions' => $pendingCommissions->count(),
            'promotions' => $promotionRequests->count(),
            'notices' => $adminNotices->count(),
        ];

        return view('branch-admin.notifications.index', compact(
            'maturingDeposits',
            'pendingCommissions',
            'promotionRequests',
            'adminNotices',
            'counts',
            'typeFilter'
        ));
    }
}
