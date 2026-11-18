<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\PromotionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $agent = $user->agentProfile;

        if (! $agent) {
            abort(403, 'No agent profile linked.');
        }

        $agentId = (int) $agent->id;
        $branchId = (int) ($agent->branch_id ?? $user->branch_id);
        $today = Carbon::today()->toDateString();
        $thirtyDaysFromNow = Carbon::today()->addDays(30)->toDateString();

        $typeFilter = $request->get('type', 'all');

        // 1. Deposit Status Updates (Recent approved, activated, matured, rejected)
        $depositUpdates = Deposit::where('agent_id', $agentId)
            ->where(function ($q) use ($today, $thirtyDaysFromNow) {
                $q->whereIn('status', [Deposit::STATUS_APPROVED, Deposit::STATUS_ACTIVE, Deposit::STATUS_REJECTED, Deposit::STATUS_MATURED])
                    ->orWhere(function ($sub) use ($today, $thirtyDaysFromNow) {
                        $sub->where('status', Deposit::STATUS_ACTIVE)
                            ->whereBetween('maturity_date', [$today, $thirtyDaysFromNow]);
                    });
            })
            ->with(['customer', 'scheme'])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        // 2. Commission Updates (Approved, Payable, Paid)
        $commissionUpdates = Commission::where('agent_id', $agentId)
            ->whereIn('status', [Commission::STATUS_APPROVED, Commission::STATUS_PAYABLE, Commission::STATUS_PAID, Commission::STATUS_PENDING])
            ->with(['deposit', 'customer'])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        // 3. Promotion / Career Updates
        $careerUpdates = PromotionRequest::where('agent_id', $agentId)
            ->with(['currentLevel', 'proposedLevel', 'reviewer'])
            ->latest()
            ->limit(10)
            ->get();

        // 4. Branch Admin Announcements / Notices
        $branchNotices = AuditLog::where('branch_id', $branchId)
            ->whereIn('event', [
                'deposit_approved',
                'deposit_rejected',
                'deposit_closed',
                'promotion_approved',
                'promotion_rejected',
            ])
            ->latest()
            ->limit(15)
            ->get();

        $counts = [
            'total' => $depositUpdates->count() + $commissionUpdates->count() + $careerUpdates->count() + $branchNotices->count(),
            'deposits' => $depositUpdates->count(),
            'commissions' => $commissionUpdates->count(),
            'career' => $careerUpdates->count(),
            'notices' => $branchNotices->count(),
        ];

        return view('agent.notifications.index', compact(
            'depositUpdates',
            'commissionUpdates',
            'careerUpdates',
            'branchNotices',
            'counts',
            'typeFilter'
        ));
    }
}
