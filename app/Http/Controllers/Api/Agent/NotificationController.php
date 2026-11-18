<?php

namespace App\Http\Controllers\Api\Agent;

use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\Lead;
use App\Models\PromotionRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class NotificationController extends BaseAgentApiController
{
    /**
     * Return list of agent notifications with unread count and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();
        $agentId = (int) $agent->id;
        $branchId = (int) ($agent->branch_id ?? auth()->user()->branch_id ?? 1);
        $category = $request->get('category') ?? $request->get('type', 'all');

        $readIds = Cache::get("agent_{$agentId}_read_notifications", []);
        if (! is_array($readIds)) {
            $readIds = [];
        }
        $markAllReadAt = Cache::get("agent_{$agentId}_all_read_at");
        $markAllReadCarbon = $markAllReadAt ? Carbon::parse($markAllReadAt) : null;

        $notifications = collect();

        // 1. Deposits
        if ($category === 'all' || $category === 'deposits') {
            $deposits = Deposit::where('agent_id', $agentId)
                ->with(['customer', 'scheme'])
                ->latest('updated_at')
                ->limit(20)
                ->get();

            foreach ($deposits as $dep) {
                $status = strtoupper((string) $dep->status);
                $custName = $dep->customer?->name ?? 'Customer';
                $accNum = $dep->account_number ?? $dep->deposit_number ?? ('DEP'.$dep->id);
                $amt = number_format((float) ($dep->amount ?? 0), 2);
                $notifId = 100000 + (int) $dep->id;
                $updatedCarbon = $dep->updated_at ?? now();
                $isRead = in_array($notifId, $readIds, true)
                    || ($markAllReadCarbon && $updatedCarbon->lte($markAllReadCarbon));

                $notifications->push([
                    'id' => $notifId,
                    'title' => "Deposit #{$accNum} - {$status}",
                    'body' => "{$custName}: ₹{$amt} in {$dep->scheme?->name}",
                    'category' => 'deposits',
                    'is_read' => $isRead,
                    'created_at' => $dep->updated_at ? $dep->updated_at->toISOString() : now()->toISOString(),
                    'link_route' => $dep->lead_id ? "/leads/{$dep->lead_id}" : null,
                    'entity_type' => 'deposit',
                    'entity_id' => (int) $dep->id,
                    'meta' => $status,
                ]);
            }
        }

        // 2. Commissions
        if ($category === 'all' || $category === 'commissions') {
            $commissions = Commission::where('agent_id', $agentId)
                ->with(['deposit', 'customer'])
                ->latest('updated_at')
                ->limit(20)
                ->get();

            foreach ($commissions as $comm) {
                $status = strtoupper((string) $comm->status);
                $amt = number_format((float) ($comm->commission_amount ?? 0), 2);
                $notifId = 200000 + (int) $comm->id;
                $updatedCarbon = $comm->updated_at ?? now();
                $isRead = in_array($notifId, $readIds, true)
                    || ($markAllReadCarbon && $updatedCarbon->lte($markAllReadCarbon));

                $notifications->push([
                    'id' => $notifId,
                    'title' => "Commission {$status} (₹{$amt})",
                    'body' => "Commission #{$comm->commission_number} for period {$comm->period}",
                    'category' => 'commissions',
                    'is_read' => $isRead,
                    'created_at' => $comm->updated_at ? $comm->updated_at->toISOString() : now()->toISOString(),
                    'link_route' => "/commissions/{$comm->id}",
                    'entity_type' => 'commission',
                    'entity_id' => (int) $comm->id,
                    'meta' => $status,
                ]);
            }
        }

        // 3. Career
        if ($category === 'all' || $category === 'career') {
            $requests = PromotionRequest::where('agent_id', $agentId)
                ->with(['proposedLevel'])
                ->latest('updated_at')
                ->limit(10)
                ->get();

            foreach ($requests as $req) {
                $targetRank = $req->proposedLevel?->name ?? 'Higher Rank';
                $status = str_replace('_', ' ', (string) $req->status);
                $notifId = 300000 + (int) $req->id;
                $updatedCarbon = $req->updated_at ?? now();
                $isRead = in_array($notifId, $readIds, true)
                    || ($markAllReadCarbon && $updatedCarbon->lte($markAllReadCarbon));

                $notifications->push([
                    'id' => $notifId,
                    'title' => "Promotion: {$status}",
                    'body' => "Your promotion application for {$targetRank} is {$status}.",
                    'category' => 'career',
                    'is_read' => $isRead,
                    'created_at' => $req->updated_at ? $req->updated_at->toISOString() : now()->toISOString(),
                    'link_route' => '/career',
                    'entity_type' => 'promotion_request',
                    'entity_id' => (int) $req->id,
                    'meta' => (string) $req->status,
                ]);
            }
        }

        // 4. Lead Follow-up Reminders
        if ($category === 'all' || $category === 'branch_notices') {
            $leadsDue = Lead::where('agent_id', $agentId)
                ->whereDate('follow_up_date', '<=', today())
                ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED])
                ->latest('follow_up_date')
                ->limit(10)
                ->get();

            foreach ($leadsDue as $leadDue) {
                $isOverdue = $leadDue->follow_up_date && Carbon::parse($leadDue->follow_up_date)->lt(today());
                $statusText = $isOverdue ? 'Overdue Follow-up' : 'Follow-up Due Today';
                $dateFormatted = $leadDue->follow_up_date ? Carbon::parse($leadDue->follow_up_date)->format('d M Y') : 'Today';
                $notifId = 500000 + (int) $leadDue->id;
                $updatedCarbon = $leadDue->updated_at ?? now();
                $isRead = in_array($notifId, $readIds, true)
                    || ($markAllReadCarbon && $updatedCarbon->lte($markAllReadCarbon));

                $notifications->push([
                    'id' => $notifId,
                    'title' => "{$statusText}: {$leadDue->name}",
                    'body' => "Follow-up scheduled for {$dateFormatted}. Phone: {$leadDue->mobile}",
                    'category' => 'branch_notices',
                    'is_read' => $isRead,
                    'created_at' => $leadDue->updated_at ? $leadDue->updated_at->toISOString() : now()->toISOString(),
                    'link_route' => "/leads/{$leadDue->id}",
                    'entity_type' => 'lead',
                    'entity_id' => (int) $leadDue->id,
                    'meta' => $leadDue->status,
                ]);
            }
        }

        // 5. Branch Notices
        if ($category === 'all' || $category === 'branch_notices') {
            $notices = AuditLog::where('branch_id', $branchId)
                ->latest('created_at')
                ->limit(10)
                ->get();

            foreach ($notices as $notice) {
                $notifId = 400000 + (int) $notice->id;
                $createdCarbon = $notice->created_at ?? now();
                $isRead = in_array($notifId, $readIds, true)
                    || ($markAllReadCarbon && $createdCarbon->lte($markAllReadCarbon));

                $notifications->push([
                    'id' => $notifId,
                    'title' => ucwords(str_replace('_', ' ', (string) $notice->event)),
                    'body' => (string) $notice->description,
                    'category' => 'branch_notices',
                    'is_read' => $isRead,
                    'created_at' => $notice->created_at ? $notice->created_at->toISOString() : now()->toISOString(),
                    'link_route' => null,
                    'entity_type' => 'notice',
                    'entity_id' => (int) $notice->id,
                    'meta' => null,
                ]);
            }
        }

        // Sort by created_at desc
        $sorted = $notifications->sortByDesc('created_at')->values();

        $unreadCount = $sorted->where('is_read', false)->count();

        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);
        $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'unread_count' => (int) $unreadCount,
            'data' => $slice->all(),
            'meta' => $this->formatPaginationMeta($paginator),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();
        $agentId = (int) $agent->id;
        $numId = (int) $id;

        $readIds = Cache::get("agent_{$agentId}_read_notifications", []);
        if (! is_array($readIds)) {
            $readIds = [];
        }
        if (! in_array($numId, $readIds, true)) {
            $readIds[] = $numId;
            Cache::forever("agent_{$agentId}_read_notifications", $readIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read for this agent.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();
        $agentId = (int) $agent->id;

        Cache::forever("agent_{$agentId}_all_read_at", now()->toISOString());

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
