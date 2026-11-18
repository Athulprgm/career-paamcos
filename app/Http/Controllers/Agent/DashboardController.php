<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Lead;
use App\Services\CareerPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected CareerPerformanceService $careerService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $agent = $user->agentProfile;

        if (! $agent) {
            abort(403, 'No agent profile linked to this account.');
        }

        $agentId = (int) $agent->id;
        $branchId = (int) ($agent->branch_id ?? $user->branch_id);
        $today = Carbon::today()->toDateString();
        $startOfMonth = Carbon::today()->startOfMonth();

        // 1. Leads Metrics
        $myLeadsCount = Lead::where('agent_id', $agentId)->count();
        $newLeadsCount = Lead::where('agent_id', $agentId)->where('status', Lead::STATUS_NEW)->count();
        $convertedLeadsCount = Lead::where('agent_id', $agentId)->where('status', Lead::STATUS_CONVERTED)->count();

        // 2. Recent Leads (latest 5 added by this agent)
        $recentLeads = Lead::where('agent_id', $agentId)
            ->with(['branch', 'interestedScheme', 'convertedCustomer', 'deposits'])
            ->latest()
            ->limit(5)
            ->get();

        // 3. Commission Metrics
        $pendingCommissions = Commission::where('agent_id', $agentId)
            ->where('status', Commission::STATUS_PENDING);
        $pendingCommissionCount = (clone $pendingCommissions)->count();
        $pendingCommissionAmount = (float) (clone $pendingCommissions)->sum('commission_amount');

        $approvedCommissions = Commission::where('agent_id', $agentId)
            ->whereIn('status', [Commission::STATUS_APPROVED, Commission::STATUS_PAYABLE, Commission::STATUS_PAID]);
        $approvedCommissionCount = (clone $approvedCommissions)->count();
        $totalEarnedCommission = (float) (clone $approvedCommissions)->sum('commission_amount');

        // 4. Recent Commissions with Deposit & Lead info
        $recentCommissions = Commission::where('agent_id', $agentId)
            ->with(['deposit.lead', 'rule', 'customer'])
            ->latest()
            ->limit(10)
            ->get();

        // 5. Current Career Level
        $currentPeriod = now()->format('Y-m');
        $performance = $this->careerService->calculateAgentPerformance($agent, $currentPeriod);
        $eligibility = $this->careerService->checkEligibility($agent, null, $currentPeriod);
        $currentLevelName = $agent->rank?->name ?? $agent->careerLevel?->name ?? 'Standard Agent';

        // 6. Leads requiring follow-up
        $pendingLeads = Lead::where('agent_id', $agentId)
            ->whereIn('status', [Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_INTERESTED])
            ->orderBy('follow_up_date')
            ->limit(5)
            ->get();

        $myDownlinesCount = Agent::where('parent_agent_id', $agentId)->count();

        return view('agent.dashboard', compact(
            'agent',
            'myLeadsCount',
            'newLeadsCount',
            'convertedLeadsCount',
            'recentLeads',
            'pendingCommissionCount',
            'pendingCommissionAmount',
            'approvedCommissionCount',
            'totalEarnedCommission',
            'recentCommissions',
            'currentLevelName',
            'performance',
            'eligibility',
            'pendingLeads',
            'myDownlinesCount'
        ));
    }
}
