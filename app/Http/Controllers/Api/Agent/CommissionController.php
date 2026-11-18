<?php

namespace App\Http\Controllers\Api\Agent;

use App\Models\Commission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController extends BaseAgentApiController
{
    /**
     * Commission monthly summary overview (counts & totals per status).
     */
    public function overview(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();
        $period = $request->input('period', now()->format('Y-m'));

        $query = Commission::where('agent_id', $agent->id);
        if ($period) {
            $query->where('period', $period);
        }

        $all = (clone $query)->get();

        $byStatus = fn ($status) => $all->filter(fn ($c) => strtoupper((string) $c->status) === $status);

        $pending = $byStatus(Commission::STATUS_PENDING);
        $approved = $byStatus(Commission::STATUS_APPROVED);
        $payable = $byStatus(Commission::STATUS_PAYABLE);
        $paid = $byStatus(Commission::STATUS_PAID);
        $rejected = $byStatus(Commission::STATUS_REJECTED);

        return response()->json([
            'period' => (string) $period,
            'total_count' => (int) $all->count(),
            'total_amount' => (float) $all->sum('commission_amount'),
            'pending_count' => (int) $pending->count(),
            'pending_amount' => (float) $pending->sum('commission_amount'),
            'approved_count' => (int) $approved->count(),
            'approved_amount' => (float) $approved->sum('commission_amount'),
            'payable_count' => (int) $payable->count(),
            'payable_amount' => (float) $payable->sum('commission_amount'),
            'paid_count' => (int) $paid->count(),
            'paid_amount' => (float) $paid->sum('commission_amount'),
            'rejected_count' => (int) $rejected->count(),
            'rejected_amount' => (float) $rejected->sum('commission_amount'),
        ]);
    }

    /**
     * Paginated commission ledger.
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        $query = Commission::with(['deposit.scheme', 'deposit.customer', 'scheme', 'customer'])
            ->where('agent_id', $agent->id);

        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->input('status')));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('commission_number', 'like', "%{$search}%")
                    ->orWhere('commission_id', 'like', "%{$search}%")
                    ->orWhereHas('deposit', function ($d) use ($search) {
                        $d->where('account_number', 'like', "%{$search}%")
                            ->orWhere('deposit_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->get('per_page', 20);
        $paginator = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn ($c) => $this->formatCommission($c))->values()->all(),
            'meta' => $this->formatPaginationMeta($paginator),
        ]);
    }

    /**
     * Show single commission details.
     */
    public function show($id): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        $commission = Commission::where('id', $id)
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        return response()->json($this->formatCommission($commission));
    }
}
