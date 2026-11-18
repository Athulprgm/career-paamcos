<?php

namespace App\Http\Controllers\Api\Agent;

use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\DepositScheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupsController extends BaseAgentApiController
{
    /**
     * Return bootstrap lookups bundle for mobile app initialization.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $branches = Branch::where('is_active', true)
            ->orWhereNull('is_active')
            ->orderBy('name')
            ->get()
            ->map(function ($b) {
                return [
                    'id' => (int) $b->id,
                    'name' => (string) $b->name,
                    'code' => (string) ($b->code ?? 'BR'.$b->id),
                    'phone' => $b->phone ?? null,
                    'city' => $b->city ?? $b->place ?? null,
                ];
            });

        $schemes = DepositScheme::where('status', DepositScheme::STATUS_ACTIVE)
            ->orWhere('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => (int) $s->id,
                    'name' => (string) $s->name,
                    'code' => (string) ($s->code ?? $s->scheme_code ?? 'SCH'.$s->id),
                    'type' => (string) ($s->scheme_type ?? $s->type ?? 'RD'),
                    'interest_rate' => (float) ($s->interest_rate ?? 0),
                    'min_amount' => (float) ($s->min_amount ?? $s->minimum_deposit_amount ?? 100),
                ];
            });

        $ranks = AgentRank::where('status', 'active')
            ->orWhere('status', 'ACTIVE')
            ->orWhereNull('status')
            ->orderBy('level')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'name' => (string) $r->name,
                    'code' => (string) ($r->code ?? 'RNK'.$r->id),
                    'level' => (int) ($r->level ?? $r->sort_order ?? 1),
                ];
            });

        return response()->json([
            'branches' => $branches,
            'schemes' => $schemes,
            'ranks' => $ranks,
        ]);
    }
}
