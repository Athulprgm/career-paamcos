<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Lead;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseAgentApiController extends Controller
{
    /**
     * Get the authenticated Agent model for the current Sanctum user.
     */
    protected function getAuthenticatedAgent(): Agent
    {
        $user = auth()->user();
        $agent = $user?->agentProfile;

        if (! $agent) {
            abort(response()->json([
                'status' => 'inactive',
                'message' => 'Unauthorized access: No agent profile linked to this account.',
            ], 403));
        }

        return $agent;
    }

    /**
     * Format Agent model to canonical AgentUser JSON structure expected by Flutter.
     */
    protected function formatAgentUser(Agent $agent): array
    {
        $agent->loadMissing(['branch', 'rank', 'parentAgent.rank', 'reportingStaff']);

        return [
            'id' => (int) $agent->id,
            'agent_id' => (string) ($agent->agent_id ?? $agent->member_code ?? ''),
            'member_code' => (string) ($agent->member_code ?? $agent->agent_id ?? ''),
            'name' => (string) $agent->name,
            'email' => (string) $agent->email,
            'phone' => (string) $agent->phone,
            'status' => strtolower((string) ($agent->status ?? 'inactive')),
            'rejection_reason' => $agent->rejection_reason ?? null,
            'profile_image_url' => $agent->profile_image_url ?? null,
            'place' => $agent->place ?? null,
            'address' => $agent->address ?? null,
            'joining_date' => $agent->joining_date ? $agent->joining_date->toISOString() : now()->toISOString(),
            'rank' => [
                'id' => (int) ($agent->rank?->id ?? 1),
                'name' => (string) ($agent->rank?->name ?? 'Advisor'),
                'code' => (string) ($agent->rank?->code ?? 'ADV'),
                'level' => (int) ($agent->rank?->level ?? $agent->rank?->sort_order ?? 1),
            ],
            'branch' => $agent->branch ? [
                'id' => (int) $agent->branch->id,
                'name' => (string) $agent->branch->name,
                'code' => (string) ($agent->branch->code ?? 'BR'.$agent->branch->id),
                'phone' => $agent->branch->phone ?? null,
                'city' => $agent->branch->city ?? $agent->branch->place ?? null,
            ] : null,
            'parent_agent' => $agent->parentAgent ? [
                'id' => (int) $agent->parentAgent->id,
                'name' => (string) $agent->parentAgent->name,
                'agent_id' => (string) ($agent->parentAgent->agent_id ?? $agent->parentAgent->member_code ?? ''),
                'phone' => $agent->parentAgent->phone ?? null,
                'rank_name' => $agent->parentAgent->rank?->name ?? null,
            ] : null,
            'reporting_staff' => $agent->reportingStaff ? [
                'id' => (int) $agent->reportingStaff->id,
                'name' => (string) $agent->reportingStaff->name,
                'staff_id' => (string) ($agent->reportingStaff->staff_id ?? ''),
                'phone' => $agent->reportingStaff->phone ?? null,
            ] : null,
        ];
    }

    /**
     * Format Downline Agent item.
     */
    protected function formatDownline(Agent $downline): array
    {
        $downline->loadMissing(['branch', 'rank']);

        $totalLeads = $downline->leads_count ?? $downline->leads()->count();
        $convertedLeads = $downline->leads()->where('status', Lead::STATUS_CONVERTED)->count();
        $totalCollection = (float) $downline->deposits()->sum('amount');

        return [
            'id' => (int) $downline->id,
            'parent_agent_id' => (int) $downline->parent_agent_id,
            'agent_id' => (string) ($downline->agent_id ?? $downline->member_code ?? ''),
            'member_code' => (string) ($downline->member_code ?? $downline->agent_id ?? ''),
            'name' => (string) $downline->name,
            'email' => (string) $downline->email,
            'phone' => (string) $downline->phone,
            'status' => strtolower((string) ($downline->status ?? 'inactive')),
            'rank' => [
                'id' => (int) ($downline->rank?->id ?? 1),
                'name' => (string) ($downline->rank?->name ?? 'Advisor'),
                'code' => (string) ($downline->rank?->code ?? 'ADV'),
                'level' => (int) ($downline->rank?->level ?? $downline->rank?->sort_order ?? 1),
            ],
            'branch' => $downline->branch ? [
                'id' => (int) $downline->branch->id,
                'name' => (string) $downline->branch->name,
                'code' => (string) ($downline->branch->code ?? 'BR'.$downline->branch->id),
            ] : null,
            'place' => $downline->place ?? null,
            'address' => $downline->address ?? null,
            'joining_date' => $downline->joining_date ? $downline->joining_date->toISOString() : now()->toISOString(),
            'profile_image_url' => $downline->profile_image_url ?? null,
            'total_leads_count' => (int) $totalLeads,
            'converted_leads_count' => (int) $convertedLeads,
            'total_collection_amount' => (float) $totalCollection,
        ];
    }

    /**
     * Format Lead model.
     */
    protected function formatLead(Lead $lead): array
    {
        $lead->loadMissing(['branch', 'interestedScheme', 'convertedCustomer', 'deposits.scheme']);

        $deposits = $lead->deposits->map(function ($deposit) {
            $scheme = $deposit->scheme;
            $schemeType = (string) ($scheme?->scheme_type ?? $scheme?->type ?? 'RD');

            return [
                'deposit_id' => (int) $deposit->id,
                'deposit_number' => (string) ($deposit->account_number ?? $deposit->deposit_number ?? 'DEP'.$deposit->id),
                'scheme_name' => (string) ($scheme?->name ?? 'Deposit Scheme'),
                'scheme_type' => strtoupper($schemeType),
                'amount' => (float) ($deposit->amount ?? $deposit->opening_amount ?? 0),
                'status' => (string) ($deposit->status ?? 'ACTIVE'),
                'opened_at' => $deposit->opened_at ? $deposit->opened_at->toISOString() : $deposit->created_at?->toISOString(),
                'maturity_date' => $deposit->maturity_date ? $deposit->maturity_date->toISOString() : null,
            ];
        })->values()->all();

        return [
            'id' => (int) $lead->id,
            'name' => (string) $lead->name,
            'mobile' => (string) ($lead->mobile ?? $lead->phone ?? ''),
            'email' => $lead->email ?? null,
            'address' => $lead->address ?? null,
            'branch' => $lead->branch ? [
                'id' => (int) $lead->branch->id,
                'name' => (string) $lead->branch->name,
                'code' => (string) ($lead->branch->code ?? 'BR'.$lead->branch->id),
            ] : null,
            'interested_scheme' => $lead->interestedScheme ? [
                'id' => (int) $lead->interestedScheme->id,
                'name' => (string) $lead->interestedScheme->name,
                'code' => (string) ($lead->interestedScheme->code ?? 'SCH'.$lead->interestedScheme->id),
                'type' => (string) ($lead->interestedScheme->scheme_type ?? $lead->interestedScheme->type ?? 'RD'),
            ] : null,
            'expected_amount' => (float) ($lead->expected_amount ?? 0),
            'status' => (string) ($lead->status ?? 'NEW'),
            'follow_up_date' => $lead->follow_up_date ? $lead->follow_up_date->toISOString() : null,
            'notes' => $lead->notes ?? null,
            'converted_customer' => $lead->convertedCustomer ? [
                'customer_id' => (int) $lead->convertedCustomer->id,
                'customer_code' => (string) ($lead->convertedCustomer->customer_code ?? $lead->convertedCustomer->member_code ?? 'CUST'.$lead->convertedCustomer->id),
                'name' => (string) $lead->convertedCustomer->name,
                'mobile' => (string) ($lead->convertedCustomer->mobile ?? $lead->convertedCustomer->phone ?? ''),
                'kyc_verified_at' => $lead->convertedCustomer->kyc_verified_at ? $lead->convertedCustomer->kyc_verified_at->toISOString() : null,
            ] : null,
            'deposits' => $deposits,
            'created_at' => $lead->created_at ? $lead->created_at->toISOString() : now()->toISOString(),
        ];
    }

    /**
     * Format Commission model.
     */
    protected function formatCommission(Commission $commission): array
    {
        $commission->loadMissing(['deposit.scheme', 'deposit.customer', 'scheme', 'customer']);

        $scheme = $commission->scheme ?? $commission->deposit?->scheme;
        $customerName = $commission->customer?->name ?? $commission->deposit?->customer?->name ?? 'Customer';
        $depositNumber = (string) ($commission->deposit?->account_number ?? $commission->deposit?->deposit_number ?? 'DEP'.($commission->deposit_id ?? $commission->id));

        return [
            'id' => (int) $commission->id,
            'commission_number' => (string) ($commission->commission_number ?? $commission->commission_id ?? 'COM'.$commission->id),
            'deposit_number' => $depositNumber,
            'customer_or_lead_name' => (string) $customerName,
            'scheme' => [
                'id' => (int) ($scheme?->id ?? 1),
                'name' => (string) ($scheme?->name ?? 'Standard Scheme'),
                'code' => (string) ($scheme?->code ?? 'SCH1'),
                'type' => (string) ($scheme?->scheme_type ?? $scheme?->type ?? 'RD'),
            ],
            'base_amount' => (float) ($commission->base_amount ?? 0),
            'rate_percentage' => (float) ($commission->rate ?? $commission->commission_rate ?? 0),
            'commission_amount' => (float) ($commission->commission_amount ?? $commission->calculated_amount ?? 0),
            'status' => strtoupper((string) ($commission->status ?? 'PENDING')),
            'period' => (string) ($commission->period ?? now()->format('Y-m')),
            'approved_at' => $commission->approved_at ? $commission->approved_at->toISOString() : null,
            'payment_date' => $commission->payment_date ? $commission->payment_date->toISOString() : null,
            'payment_method' => $commission->payment_method ?? null,
            'payment_reference' => $commission->payment_reference ?? null,
            'reference_number' => $commission->reference_number ?? null,
            'payment_notes' => $commission->payment_notes ?? null,
            'rejection_reason' => $commission->rejection_reason ?? null,
            'created_at' => $commission->created_at ? $commission->created_at->toISOString() : now()->toISOString(),
        ];
    }

    /**
     * Format PaginationMeta structure.
     */
    protected function formatPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => (int) $paginator->currentPage(),
            'last_page' => (int) $paginator->lastPage(),
            'per_page' => (int) $paginator->perPage(),
            'total' => (int) $paginator->total(),
        ];
    }
}
