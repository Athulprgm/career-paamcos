<?php

namespace App\Http\Controllers\Api\Agent;

use App\Models\Agent;
use App\Models\Commission;
use App\Models\Lead;
use App\Models\PromotionRequest;
use App\Services\CareerPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseAgentApiController
{
    public function __construct(
        protected CareerPerformanceService $careerService
    ) {}

    /**
     * Dashboard overview payload for the mobile app.
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();
        $agentId = (int) $agent->id;

        // 1. Leads KPIs
        $totalLeads = Lead::where('agent_id', $agentId)->count();
        $newLeads = Lead::where('agent_id', $agentId)->where('status', Lead::STATUS_NEW)->count();
        $convertedLeads = Lead::where('agent_id', $agentId)->where('status', Lead::STATUS_CONVERTED)->count();
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0.0;

        // 2. Commission KPIs
        $pendingCommission = (float) Commission::where('agent_id', $agentId)
            ->where('status', Commission::STATUS_PENDING)
            ->sum('commission_amount');

        $earnedCommission = (float) Commission::where('agent_id', $agentId)
            ->whereIn('status', [Commission::STATUS_APPROVED, Commission::STATUS_PAYABLE, Commission::STATUS_PAID])
            ->sum('commission_amount');

        // 3. Follow up metrics
        $today = Carbon::today()->toDateString();
        $dueTodayFollowUps = Lead::where('agent_id', $agentId)
            ->whereDate('follow_up_date', $today)
            ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED])
            ->count();

        $overdueFollowUps = Lead::where('agent_id', $agentId)
            ->whereDate('follow_up_date', '<', $today)
            ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED])
            ->count();

        $totalDownlines = Agent::where('parent_agent_id', $agentId)->count();

        // 4. Career Snapshot
        $currentPeriod = now()->format('Y-m');
        $achievement = [];
        try {
            $achievement = $this->careerService->calculateTargetAchievement($agent, null, $currentPeriod);
        } catch (\Throwable $e) {
            // Fallback if target calculations fail
        }

        $currentRankName = (string) ($agent->rank?->name ?? 'Advisor');
        $currentRankLevel = (int) ($agent->rank?->level ?? $agent->rank?->sort_order ?? 1);
        $nextRankName = $achievement['next_rank']['name'] ?? null;
        $nextRankLevel = isset($achievement['next_rank']['level']) ? (int) $achievement['next_rank']['level'] : null;
        $progressPercentage = (float) ($achievement['progress_percent'] ?? 0.0);
        $compositeScore = (float) ($achievement['composite_score'] ?? $progressPercentage);

        // Check if pending promotion request exists
        $hasPendingRequest = PromotionRequest::where('agent_id', $agentId)
            ->where('status', PromotionRequest::STATUS_PENDING_REVIEW)
            ->exists();

        $eligibilityBadge = 'IN PROGRESS';
        if ($hasPendingRequest) {
            $eligibilityBadge = 'UNDER REVIEW';
        } elseif (($achievement['is_eligible'] ?? false) || $progressPercentage >= 100) {
            $eligibilityBadge = 'ELIGIBLE FOR PROMOTION';
        }

        // 5. Lists
        $pendingFollowUps = Lead::where('agent_id', $agentId)
            ->whereDate('follow_up_date', '<=', $today)
            ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED])
            ->orderBy('follow_up_date')
            ->limit(5)
            ->get()
            ->map(fn ($lead) => $this->formatLead($lead))
            ->values()
            ->all();

        $recentLeads = Lead::where('agent_id', $agentId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($lead) => $this->formatLead($lead))
            ->values()
            ->all();

        $recentCommissions = Commission::where('agent_id', $agentId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($c) => $this->formatCommission($c))
            ->values()
            ->all();

        return response()->json([
            'agent_card' => $this->formatAgentUser($agent),
            'kpis' => [
                'total_leads' => (int) $totalLeads,
                'new_leads_count' => (int) $newLeads,
                'converted_leads' => (int) $convertedLeads,
                'conversion_rate' => (float) $conversionRate,
                'pending_commission_amount' => (float) $pendingCommission,
                'earned_commission_amount' => (float) $earnedCommission,
                'due_today_follow_ups' => (int) $dueTodayFollowUps,
                'overdue_follow_ups' => (int) $overdueFollowUps,
                'total_downlines' => (int) $totalDownlines,
            ],
            'career_snapshot' => [
                'current_rank_name' => $currentRankName,
                'current_rank_level' => $currentRankLevel,
                'next_rank_name' => $nextRankName,
                'next_rank_level' => $nextRankLevel,
                'composite_score' => (float) $compositeScore,
                'progress_percentage' => (float) $progressPercentage,
                'eligibility_badge' => $eligibilityBadge,
            ],
            'pending_follow_ups' => $pendingFollowUps,
            'recent_leads' => $recentLeads,
            'recent_commissions' => $recentCommissions,
        ]);
    }
}
