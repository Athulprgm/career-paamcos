<?php

namespace App\Http\Controllers\Api\Agent;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Deposit;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Services\NumberingService;
use App\Services\PasswordGeneratorService;
use App\Services\ProfileImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DownlineController extends BaseAgentApiController
{
    /**
     * List all direct downline agents recruited by the authenticated agent.
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        $query = Agent::where('parent_agent_id', $agent->id)
            ->with(['rank', 'branch'])
            ->withCount(['leads', 'downlineAgents']);

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('agent_id', 'like', "%{$term}%")
                    ->orWhere('member_code', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = (int) $request->get('per_page', 20);
        $paginator = $query->latest('joining_date')->paginate($perPage);

        // Team stats
        $totalDownlines = Agent::where('parent_agent_id', $agent->id)->count();
        $activeDownlines = Agent::where('parent_agent_id', $agent->id)->where('status', Agent::STATUS_ACTIVE)->count();
        $pendingDownlines = Agent::where('parent_agent_id', $agent->id)->where('status', Agent::STATUS_PENDING)->count();
        $inactiveDownlines = Agent::where('parent_agent_id', $agent->id)->where('status', Agent::STATUS_INACTIVE)->count();
        $rejectedDownlines = Agent::where('parent_agent_id', $agent->id)->where('status', Agent::STATUS_REJECTED)->count();
        $directDownlineIds = Agent::where('parent_agent_id', $agent->id)->pluck('id');
        $totalTeamLeads = Lead::whereIn('agent_id', $directDownlineIds)->count();
        $totalTeamCollection = (float) Deposit::whereIn('agent_id', $directDownlineIds)->sum('amount');

        return response()->json([
            'kpis' => [
                'total_downlines' => (int) $totalDownlines,
                'active_count' => (int) $activeDownlines,
                'pending_count' => (int) $pendingDownlines,
                'inactive_count' => (int) $inactiveDownlines,
                'rejected_count' => (int) $rejectedDownlines,
                'total_team_leads' => (int) $totalTeamLeads,
                'total_team_collection' => (float) $totalTeamCollection,
            ],
            'data' => $paginator->getCollection()->map(fn ($d) => $this->formatDownline($d))->values()->all(),
            'meta' => $this->formatPaginationMeta($paginator),
        ]);
    }

    /**
     * Preview next agent code/ID.
     */
    public function nextId(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $joiningDate = $request->input('joining_date');

        try {
            $nextId = app(NumberingService::class)->previewAgentId($name, $joiningDate);
        } catch (\Throwable) {
            $nextId = 'PAG'.date('my').'1';
        }

        return response()->json([
            'success' => true,
            'agent_id' => $nextId,
        ]);
    }

    /**
     * Recruit / add a new downline agent under the current agent.
     */
    public function recruit(Request $request): JsonResponse
    {
        $currentAgent = $this->getAuthenticatedAgent();
        $currentAgent->load('rank');

        // A recruiting agent can ONLY add same position or down priority (lower position) agents
        $currentAgentRank = $currentAgent->rank;
        $maxAllowedLevel = $currentAgentRank ? (int) ($currentAgentRank->level ?? $currentAgentRank->sort_order ?? 1) : 1;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'agent_id' => ['nullable', 'string', 'max:50', 'unique:agents,agent_id', 'unique:agents,member_code'],
            'email' => ['required', 'email', 'max:255', 'unique:agents,email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:6'],
            'rank_id' => [
                'nullable',
                'integer',
                'exists:ranks,id',
                function ($attribute, $value, $fail) use ($maxAllowedLevel, $currentAgentRank) {
                    if ($value) {
                        $targetRank = AgentRank::find($value);
                        if ($targetRank && (int) ($targetRank->level ?? $targetRank->sort_order ?? 1) > $maxAllowedLevel) {
                            $rankLabel = $currentAgentRank ? "{$currentAgentRank->name} (Level {$currentAgentRank->level})" : "Level {$maxAllowedLevel}";
                            $fail("You can only assign an agent to your same position or a down-priority position (Level {$maxAllowedLevel} or lower). Ranks higher than {$rankLabel} are restricted.");
                        }
                    }
                },
            ],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'joining_date' => ['nullable', 'date'],
            'profile_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $joiningDate = ! empty($validated['joining_date']) ? $validated['joining_date'] : now()->toDateString();

        $agentId = ! empty($validated['agent_id'])
            ? trim($validated['agent_id'])
            : app(NumberingService::class)->generateAgentId($validated['name'], $joiningDate);

        $rawPassword = ! empty($validated['password'])
            ? $validated['password']
            : app(PasswordGeneratorService::class)->generate();

        $rankId = ! empty($validated['rank_id'])
            ? (int) $validated['rank_id']
            : AgentRank::active()->where('level', '<=', $maxAllowedLevel)->orderBy('level', 'asc')->value('id');

        $profileImagePath = null;
        if ($request->hasFile('profile_image')) {
            $profileImagePath = app(ProfileImageService::class)->upload(
                $request->file('profile_image'),
                'agents'
            );
        }

        $newAgent = DB::transaction(function () use ($validated, $agentId, $rawPassword, $rankId, $currentAgent, $joiningDate, $profileImagePath) {
            $agent = Agent::create([
                'name' => $validated['name'],
                'agent_id' => $agentId,
                'member_code' => $agentId,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'place' => $validated['place'] ?? null,
                'address' => $validated['address'] ?? null,
                'password' => Hash::make($rawPassword),
                'branch_id' => $validated['branch_id'] ?? $currentAgent->branch_id,
                'rank_id' => $rankId,
                'parent_agent_id' => $currentAgent->id,
                'reporting_staff_id' => $currentAgent->reporting_staff_id,
                'joining_date' => $joiningDate,
                'status' => Agent::STATUS_PENDING,
                'profile_image' => $profileImagePath,
            ]);

            // Create/link User account
            $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent']);
            $newUser = User::create([
                'name' => $agent->name,
                'email' => $agent->email,
                'password' => Hash::make($rawPassword),
                'branch_id' => $agent->branch_id,
                'profile_image' => $agent->profile_image,
                'email_verified_at' => now(),
            ]);
            $newUser->roles()->syncWithoutDetaching([$agentRole->id]);
            $agent->updateQuietly(['user_id' => $newUser->id]);

            return $agent;
        });

        return response()->json($this->formatDownline($newAgent), 201);
    }

    /**
     * View downline agent profile.
     */
    public function show($id): JsonResponse
    {
        $currentAgent = $this->getAuthenticatedAgent();

        $downline = Agent::where('id', $id)
            ->where('parent_agent_id', $currentAgent->id)
            ->firstOrFail();

        return response()->json($this->formatDownline($downline));
    }
}
