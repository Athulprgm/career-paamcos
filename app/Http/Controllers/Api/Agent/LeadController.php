<?php

namespace App\Http\Controllers\Api\Agent;

use App\Models\Deposit;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends BaseAgentApiController
{
    /**
     * Display a paginated listing of leads for the authenticated agent.
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        // 1. Auto-sync converted leads that have approved/active deposits
        Lead::where('agent_id', $agent->id)
            ->where('status', '!=', Lead::STATUS_CONVERTED)
            ->whereHas('deposits', function ($q) {
                $q->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_PENDING_APPROVAL]);
            })
            ->update([
                'status' => Lead::STATUS_CONVERTED,
                'converted_at' => now(),
            ]);

        $query = Lead::with(['branch', 'interestedScheme', 'convertedCustomer', 'deposits.scheme'])
            ->where('agent_id', $agent->id);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('scheme_id')) {
            $query->where('interested_scheme_id', $request->scheme_id);
        }

        if ($request->filled('follow_up_filter')) {
            $filter = $request->input('follow_up_filter');
            if ($filter === 'today') {
                $query->whereDate('follow_up_date', today());
            } elseif ($filter === 'upcoming') {
                $query->whereDate('follow_up_date', '>=', today());
            } elseif ($filter === 'overdue') {
                $query->whereDate('follow_up_date', '<', today())
                    ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED]);
            }
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        if ($sort === 'amount_desc') {
            $query->orderBy('expected_amount', 'desc')->orderBy('id', 'desc');
        } elseif ($sort === 'amount_asc') {
            $query->orderBy('expected_amount', 'asc')->orderBy('id', 'desc');
        } elseif ($sort === 'follow_up_asc') {
            $query->orderByRaw('follow_up_date IS NULL, follow_up_date ASC')->orderBy('id', 'desc');
        } elseif ($sort === 'name_asc') {
            $query->orderBy('name', 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $perPage = (int) $request->get('per_page', 15);
        $paginator = $query->paginate($perPage);

        // Pipeline summary metrics
        $pipelineBanner = [
            'total_leads' => (int) Lead::where('agent_id', $agent->id)->count(),
            'new_leads' => (int) Lead::where('agent_id', $agent->id)->where('status', Lead::STATUS_NEW)->count(),
            'contacted_leads' => (int) Lead::where('agent_id', $agent->id)->where('status', Lead::STATUS_CONTACTED)->count(),
            'interested_leads' => (int) Lead::where('agent_id', $agent->id)->where('status', Lead::STATUS_INTERESTED)->count(),
            'converted_leads' => (int) Lead::where('agent_id', $agent->id)->where('status', Lead::STATUS_CONVERTED)->count(),
            'not_interested_leads' => (int) Lead::where('agent_id', $agent->id)->where('status', Lead::STATUS_NOT_INTERESTED)->count(),
            'total_pipeline_value' => (float) Lead::where('agent_id', $agent->id)
                ->whereIn('status', [Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_INTERESTED])
                ->sum('expected_amount'),
            'converted_value' => (float) Lead::where('agent_id', $agent->id)
                ->where('status', Lead::STATUS_CONVERTED)
                ->sum('expected_amount'),
            'due_today_count' => (int) Lead::where('agent_id', $agent->id)
                ->whereDate('follow_up_date', today())
                ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED])
                ->count(),
        ];

        return response()->json([
            'pipeline_banner' => $pipelineBanner,
            'data' => $paginator->getCollection()->map(fn ($lead) => $this->formatLead($lead))->values()->all(),
            'meta' => $this->formatPaginationMeta($paginator),
        ]);
    }

    /**
     * Store a newly created lead.
     */
    public function store(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'interested_scheme_id' => ['nullable', 'integer', 'exists:deposit_schemes,id'],
            'expected_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in([
                Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_INTERESTED,
                Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED,
            ])],
            'follow_up_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $lead = new Lead;
        $lead->agent_id = $agent->id;
        $lead->branch_id = $validated['branch_id'] ?? $agent->branch_id;
        $lead->name = $validated['name'];
        $lead->mobile = $validated['mobile'];
        $lead->email = $validated['email'] ?? null;
        $lead->address = $validated['address'] ?? null;
        $lead->interested_scheme_id = $validated['interested_scheme_id'] ?? null;
        $lead->expected_amount = $validated['expected_amount'] ?? 0;
        $lead->status = $validated['status'] ?? Lead::STATUS_NEW;
        $lead->follow_up_date = $validated['follow_up_date'] ?? null;
        $lead->notes = $validated['notes'] ?? null;
        if (($validated['status'] ?? null) === Lead::STATUS_CONVERTED) {
            $lead->converted_at = now();
        }
        $lead->created_by = auth()->id();
        $lead->save();

        $formatted = $this->formatLead($lead);

        return response()->json(array_merge([
            'success' => true,
            'message' => 'Lead created successfully.',
            'data' => $formatted,
        ], $formatted), 201);
    }

    /**
     * Display a specific lead.
     */
    public function show($id): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        $lead = Lead::where('id', $id)
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        $formatted = $this->formatLead($lead);

        return response()->json(array_merge([
            'success' => true,
            'data' => $formatted,
        ], $formatted));
    }

    /**
     * Update an existing lead.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        $lead = Lead::where('id', $id)
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'mobile' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'interested_scheme_id' => ['nullable', 'integer', 'exists:deposit_schemes,id'],
            'expected_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in([
                Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_INTERESTED,
                Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED,
            ])],
            'follow_up_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $lead->fill($validated);
        if (($validated['status'] ?? null) === Lead::STATUS_CONVERTED && ! $lead->converted_at) {
            $lead->converted_at = now();
        }
        $lead->save();

        $formatted = $this->formatLead($lead->fresh());

        return response()->json(array_merge([
            'success' => true,
            'message' => 'Lead updated successfully.',
            'data' => $formatted,
        ], $formatted));
    }

    /**
     * Quick status update endpoint.
     */
    public function quickStatus(Request $request, $id): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        $lead = Lead::where('id', $id)
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_INTERESTED,
                Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED,
            ])],
            'notes' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
        ]);

        $lead->status = $validated['status'];
        if ($validated['status'] === Lead::STATUS_CONVERTED && ! $lead->converted_at) {
            $lead->converted_at = now();
        }
        if (array_key_exists('follow_up_date', $validated)) {
            $lead->follow_up_date = $validated['follow_up_date'];
        }
        $noteText = $validated['notes'] ?? $validated['note'] ?? null;
        if (! empty($noteText)) {
            $timestamp = now()->format('d M Y, h:i A');
            $newNote = "[{$timestamp}] {$noteText}";
            $lead->notes = $lead->notes ? "{$lead->notes}\n{$newNote}" : $newNote;
        }
        $lead->save();

        $formatted = $this->formatLead($lead->fresh());

        return response()->json(array_merge([
            'success' => true,
            'message' => "Lead status changed to '{$lead->status}'.",
            'data' => $formatted,
        ], $formatted));
    }

    /**
     * Remove the lead if not converted.
     */
    public function destroy($id): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        $lead = Lead::where('id', $id)
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        if ($lead->status === Lead::STATUS_CONVERTED || $lead->isConverted()) {
            return response()->json([
                'message' => 'Converted leads cannot be deleted because they are linked to customer records.',
            ], 422);
        }

        $lead->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully.',
        ]);
    }
}
