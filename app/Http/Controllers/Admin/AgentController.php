<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountWelcomeMail;
use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CareerHistory;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\Lead;
use App\Models\PromotionRequest;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Services\CareerPerformanceService;
use App\Services\NumberingService;
use App\Services\PasswordGeneratorService;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    /**
     * Get branch restriction for the current user.
     * Null means user is Super Admin (unrestricted).
     */
    protected function getRestrictedBranchId(): ?int
    {
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            return null;
        }

        return $user ? ($user->branch_id ?? $user->staffProfile?->branch_id ?? $user->agentProfile?->branch_id) : null;
    }

    /**
     * Enforce branch restriction on a specific branch ID.
     */
    protected function authorizeBranchAccess(?int $branchId): void
    {
        $restricted = $this->getRestrictedBranchId();
        // Free field agents (null branch) are not restricted to any particular branch
        if ($restricted !== null && $branchId !== null && (int) $branchId !== (int) $restricted) {
            abort(403, 'Unauthorized access: You are restricted to your assigned branch.');
        }
    }

    public function index(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();

        $query = Agent::with(['branch', 'rank', 'reportingStaff', 'parentAgent']);

        // Structural branch restriction: free-field agents (null branch) are accessible across branches
        if ($restrictedBranchId !== null) {
            $query->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        } elseif ($request->filled('branch_id')) {
            if ($request->branch_id === 'free' || $request->branch_id === 'none') {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', $request->branch_id);
            }
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Rank filter
        if ($request->filled('rank_id')) {
            $query->where('rank_id', $request->rank_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Default to hierarchy tree view; switch to flat table view on demand
        $viewMode = $request->input('view') === 'table' ? 'table' : 'tree';

        if ($viewMode === 'table') {
            $agents = $query->orderBy('name')->paginate(15)->withQueryString();
            $agentTree = collect();
        } else {
            $agents = $query->orderBy('name')->get();
            $agentTree = $this->buildAgentTree($agents);
        }

        // Branches for filter dropdown
        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $ranks = AgentRank::active()->orderBy('level', 'desc')->orderBy('name')->get();

        // Summary KPI statistics
        $statsBaseQuery = Agent::query();
        if ($restrictedBranchId !== null) {
            $statsBaseQuery->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        }
        $totalCount = (clone $statsBaseQuery)->count();
        $activeCount = (clone $statsBaseQuery)->where('status', Agent::STATUS_ACTIVE)->count();
        $pendingCount = (clone $statsBaseQuery)->where('status', Agent::STATUS_PENDING)->count();
        $inactiveCount = (clone $statsBaseQuery)->whereIn('status', [Agent::STATUS_INACTIVE, Agent::STATUS_REJECTED])->count();

        return view('admin.agents.index', compact(
            'agents',
            'branches',
            'ranks',
            'restrictedBranchId',
            'agentTree',
            'viewMode',
            'totalCount',
            'activeCount',
            'pendingCount',
            'inactiveCount'
        ));
    }

    /**
     * Build the upline → downline hierarchy from a flat agent collection.
     *
     * Agents whose parent is outside the current (filtered) set — or whose
     * upline chain would form a cycle — are returned as tree roots.
     *
     * @param  Collection<int, Agent>  $agents
     * @return Collection<int, Agent>
     */
    protected function buildAgentTree(Collection $agents): Collection
    {
        $byId = $agents->keyBy('id');
        $roots = collect();

        foreach ($agents as $agent) {
            $agent->setRelation('treeChildren', collect());
        }

        foreach ($agents as $agent) {
            $parentId = $agent->parent_agent_id;
            $parent = $parentId !== null ? $byId->get($parentId) : null;

            if ($parent === null || $this->wouldCreateCycle($agent, $parent, $byId)) {
                $roots->push($agent);

                continue;
            }

            $parent->treeChildren->push($agent);
        }

        return $roots;
    }

    /**
     * Walk the upline chain to ensure attaching $child under $parent cannot loop.
     *
     * @param  Collection<int, Agent>  $byId
     */
    protected function wouldCreateCycle(Agent $child, Agent $parent, Collection $byId): bool
    {
        $visited = [$child->id => true];
        $current = $parent;

        while ($current !== null && ! array_key_exists($current->id, $visited)) {
            $visited[$current->id] = true;
            $current = $current->parent_agent_id !== null
                ? $byId->get($current->parent_agent_id)
                : null;
        }

        return $current !== null;
    }

    public function create()
    {
        $restrictedBranchId = $this->getRestrictedBranchId();

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $ranks = AgentRank::active()->orderBy('level', 'desc')->orderBy('name')->get();

        // Potential reporting staff: all active staff for Super Admin, or scoped to branch
        $staffQuery = Staff::active()->with('branch')->orderBy('name');
        if ($restrictedBranchId !== null) {
            $staffQuery->where('branch_id', $restrictedBranchId);
        }
        $staffMembers = $staffQuery->get();

        // Potential parent upline agents: agents are free, can sponsor across any branch/place
        $parentAgentsQuery = Agent::active()->with(['branch', 'rank'])->orderBy('name');
        if ($restrictedBranchId !== null) {
            $parentAgentsQuery->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        }
        $parentAgents = $parentAgentsQuery->get();

        // Auto-suggest next sequential Agent ID
        try {
            $suggestedAgentId = app(NumberingService::class)->previewNext('agent');
        } catch (\Throwable) {
            $suggestedAgentId = 'PAG'.date('my').'1';
        }

        return view('admin.agents.create', compact('branches', 'ranks', 'staffMembers', 'parentAgents', 'restrictedBranchId', 'suggestedAgentId'));
    }

    /**
     * Provide the next available Agent ID / Code via JSON for dynamic UI auto-generation.
     */
    public function nextId(Request $request)
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

    public function store(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'agent_id' => ['nullable', 'string', 'max:50', 'unique:agents,agent_id', 'unique:agents,member_code'],
            'email' => ['required', 'email', 'max:255', 'unique:agents,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['required', 'string', 'max:20'],
            'place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'rank_id' => ['required', 'integer', 'exists:ranks,id'],
            'parent_agent_id' => ['nullable', 'integer', 'exists:agents,id'],
            'reporting_staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'joining_date' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive,pending'],
            'profile_image' => ['nullable', 'string'],
            'profile_image_file' => ['nullable', 'image', 'max:5120'],
        ], [
            'agent_id.unique' => 'This Agent ID / Code is already registered.',
            'email.unique' => 'This email address is already registered to another agent.',
            'rank_id.exists' => 'The selected agent rank is invalid.',
            'branch_id.exists' => 'The selected branch is invalid.',
            'profile_image_file.image' => 'The profile image must be an image file (JPG, PNG, WebP).',
            'profile_image_file.max' => 'The profile image must not exceed 5MB.',
        ]);

        $this->authorizeBranchAccess(! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null);

        $rawPassword = $request->filled('password')
            ? (string) $request->input('password')
            : PasswordGeneratorService::generate(10);

        // Auto-generate agent ID if left empty: PAG + MM + YY + sequence (e.g. PAG11261)
        $agentId = ! empty($validated['agent_id'])
            ? trim((string) $validated['agent_id'])
            : Agent::generateAgentId($validated['name'], $validated['joining_date'] ?? null);

        if (! empty($validated['agent_id'])) {
            app(NumberingService::class)->recordUsage('agent', $agentId);
        }

        $profileImagePath = null;
        if ($request->hasFile('profile_image_file')) {
            $profileImagePath = app(ProfileImageService::class)->saveImage($request->file('profile_image_file'));
        } elseif ($request->filled('profile_image')) {
            $profileImagePath = app(ProfileImageService::class)->saveImage($request->input('profile_image'));
        }

        $agent = Agent::create([
            'name' => $validated['name'],
            'agent_id' => $agentId,
            'member_code' => $agentId,
            'email' => $validated['email'],
            'password' => Hash::make($rawPassword),
            'phone' => $validated['phone'],
            'place' => $validated['place'] ?? null,
            'address' => $validated['address'] ?? null,
            'branch_id' => ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            'rank_id' => $validated['rank_id'],
            'parent_agent_id' => $validated['parent_agent_id'] ?? null,
            'reporting_staff_id' => $validated['reporting_staff_id'] ?? null,
            'joining_date' => $validated['joining_date'],
            'status' => $validated['status'],
            'profile_image' => $profileImagePath,
        ]);

        $this->syncAgentUser($agent, $rawPassword);

        // Dispatch Welcome Credentials Email
        $emailDispatched = false;
        if ($request->boolean('send_welcome_email', true) && ! empty($agent->email)) {
            try {
                $agent->loadMissing(['branch', 'rank']);
                Mail::to($agent->email)->send(new AccountWelcomeMail(
                    recipientName: $agent->name,
                    accountType: 'Agent',
                    loginEmail: $agent->email,
                    rawPassword: $rawPassword,
                    loginUrl: route('superadmin.login'),
                    identifier: $agent->agent_id,
                    branchName: $agent->branch?->name,
                    roleOrRank: $agent->rank?->name,
                    customMessage: 'Your agent account has been created. You can now access the agent workspace and start tracking your network, customers, and commissions.'
                ));
                $emailDispatched = true;
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch agent welcome credentials email to [{$agent->email}]: ".$e->getMessage());
            }
        }

        AuditLog::record(
            event: 'agent_created',
            description: "Created agent [{$agent->name}] ({$agent->agent_id}).",
            auditable: $agent,
            newValues: $agent->only(['name', 'agent_id', 'email', 'phone', 'place', 'address', 'branch_id', 'rank_id', 'status'])
        );

        $flashMessage = "Agent '{$agent->name}' created successfully.";
        if ($emailDispatched) {
            $flashMessage .= " Welcome email with login credentials has been sent to {$agent->email}.";
        }

        return redirect()->route('admin.agents.index')
            ->with('success', $flashMessage);
    }

    public function show(Agent $agent)
    {
        $this->authorizeBranchAccess($agent->branch_id);

        $agent->load(['branch', 'rank', 'reportingStaff.position', 'parentAgent', 'downlineAgents']);

        // 1. Deposit Performance
        $depositPerformance = [
            'total_count' => $agent->deposits()->count(),
            'active_count' => $agent->deposits()->where('status', Deposit::STATUS_ACTIVE)->count(),
            'active_volume' => (float) $agent->deposits()->where('status', Deposit::STATUS_ACTIVE)->sum('amount'),
            'total_volume' => (float) $agent->deposits()->sum('amount'),
        ];

        // 3. Collection Performance
        $collectionPerformance = [
            'paid_count' => DepositCollection::where('agent_id', $agent->id)->where('status', DepositCollection::STATUS_PAID)->count(),
            'paid_volume' => (float) DepositCollection::where('agent_id', $agent->id)->sum('paid_amount'),
            'due_count' => DepositCollection::where('agent_id', $agent->id)->whereIn('status', [DepositCollection::STATUS_DUE, DepositCollection::STATUS_PARTIAL, DepositCollection::STATUS_OVERDUE])->count(),
            'outstanding_volume' => (float) DepositCollection::where('agent_id', $agent->id)
                ->whereNotIn('status', [DepositCollection::STATUS_PAID, DepositCollection::STATUS_CANCELLED])
                ->sum(DB::raw('due_amount - paid_amount')),
        ];

        // 4. Commission Summary
        $commissionSummary = [
            'total_earned' => (float) Commission::where('agent_id', $agent->id)->where('status', '!=', Commission::STATUS_REJECTED)->sum('commission_amount'),
            'paid' => (float) Commission::where('agent_id', $agent->id)->where('status', Commission::STATUS_PAID)->sum('commission_amount'),
            'pending' => (float) Commission::where('agent_id', $agent->id)->where('status', Commission::STATUS_PENDING)->sum('commission_amount'),
            'payable' => (float) Commission::where('agent_id', $agent->id)->where('status', Commission::STATUS_PAYABLE)->sum('commission_amount'),
        ];

        // 5. Career & Target Performance
        $targetAchievement = app(CareerPerformanceService::class)->calculateTargetAchievement($agent);
        $agent->refresh()->load('rank');
        $careerPerformance = [
            'current_rank' => $agent->rank,
            'promotion_requests_count' => PromotionRequest::where('agent_id', $agent->id)->count(),
            'latest_promotion' => PromotionRequest::where('agent_id', $agent->id)->with(['proposedLevel', 'currentLevel'])->latest()->first(),
        ];

        // 6. Business Leads
        $agentLeads = $agent->leads()
            ->with(['interestedScheme', 'convertedCustomer'])
            ->latest('id')
            ->limit(10)
            ->get();
        $totalLeadsCount = $agent->leads()->count();
        $newLeadsCount = $agent->leads()->where('status', Lead::STATUS_NEW)->count();
        $convertedLeadsCount = $agent->leads()->where('status', Lead::STATUS_CONVERTED)->count();

        $branches = Branch::active()->orderBy('name')->get();

        return view('admin.agents.show', compact(
            'agent',
            'branches',
            'depositPerformance',
            'collectionPerformance',
            'commissionSummary',
            'careerPerformance',
            'targetAchievement',
            'agentLeads',
            'totalLeadsCount',
            'newLeadsCount',
            'convertedLeadsCount'
        ));
    }

    public function edit(Agent $agent)
    {
        $this->authorizeBranchAccess($agent->branch_id);

        $restrictedBranchId = $this->getRestrictedBranchId();

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $ranks = AgentRank::active()->orderBy('level', 'desc')->orderBy('name')->get();

        // Staff members: Super Admin can pick staff from any branch; branch-restricted user is scoped
        $staffQuery = Staff::active()->with('branch')->orderBy('name');
        if ($restrictedBranchId !== null) {
            $staffQuery->where('branch_id', $restrictedBranchId);
        }
        $staffMembers = $staffQuery->get();

        // Potential parent upline agents: agents are free, hierarchy can span across any branch/place
        $parentAgentsQuery = Agent::active()->where('id', '!=', $agent->id)->with(['branch', 'rank'])->orderBy('name');
        if ($restrictedBranchId !== null) {
            $parentAgentsQuery->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        }
        $parentAgents = $parentAgentsQuery->get();

        return view('admin.agents.edit', compact('agent', 'branches', 'ranks', 'staffMembers', 'parentAgents', 'restrictedBranchId'));
    }

    public function update(Request $request, Agent $agent)
    {
        $this->authorizeBranchAccess($agent->branch_id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'agent_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('agents', 'agent_id')->ignore($agent->id),
                Rule::unique('agents', 'member_code')->ignore($agent->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('agents', 'email')->ignore($agent->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['required', 'string', 'max:20'],
            'place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'rank_id' => ['required', 'integer', 'exists:ranks,id'],
            'parent_agent_id' => [
                'nullable',
                'integer',
                'exists:agents,id',
                function ($attribute, $value, $fail) use ($agent) {
                    if ($value && (int) $value === (int) $agent->id) {
                        $fail('An agent cannot be their own parent upline agent.');
                    }
                },
            ],
            'reporting_staff_id' => [
                'nullable',
                'integer',
                'exists:staff,id',
            ],
            'joining_date' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive,pending,rejected'],
            'profile_image' => ['nullable', 'string'],
            'profile_image_file' => ['nullable', 'image', 'max:5120'],
            'remove_profile_image' => ['nullable', 'boolean'],
        ], [
            'agent_id.unique' => 'This Agent ID is already registered.',
            'email.unique' => 'This email address is already registered to another agent.',
            'rank_id.exists' => 'The selected agent rank is invalid.',
            'branch_id.exists' => 'The selected branch is invalid.',
            'profile_image_file.image' => 'The profile image must be an image file (JPG, PNG, WebP).',
            'profile_image_file.max' => 'The profile image must not exceed 5MB.',
        ]);

        $this->authorizeBranchAccess(! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null);

        $oldRankId = (int) $agent->rank_id;
        $newRankId = (int) $validated['rank_id'];

        // Security check: Only Super Admin can directly adjust designations outside promotion workflow
        if ($oldRankId !== $newRankId && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action: Direct designation changes are prohibited. Please submit a Promotion Request.');
        }

        // Audit & history logging for manual adjustment by Super Admin
        if ($oldRankId !== $newRankId && auth()->user()->isSuperAdmin()) {
            CareerHistory::create([
                'agent_id' => $agent->id,
                'previous_level_id' => $oldRankId,
                'new_level_id' => $newRankId,
                'promotion_date' => now()->toDateString(),
                'approved_by' => auth()->id(),
                'action' => CareerHistory::ACTION_MANUAL_ADJUSTMENT,
                'remarks' => 'Manual designation adjustment by Super Admin via Agent Profile edit.',
            ]);
        }

        $rawPassword = $request->filled('password') ? (string) $request->input('password') : null;

        $oldValues = $agent->only(['name', 'agent_id', 'email', 'phone', 'place', 'address', 'branch_id', 'rank_id', 'parent_agent_id', 'status', 'profile_image']);

        $profileImagePath = $agent->profile_image;
        if ($request->boolean('remove_profile_image')) {
            app(ProfileImageService::class)->deleteImage($agent->profile_image);
            $profileImagePath = null;
        } elseif ($request->hasFile('profile_image_file')) {
            $profileImagePath = app(ProfileImageService::class)->saveImage($request->file('profile_image_file'), $agent->profile_image);
        } elseif ($request->filled('profile_image') && str_starts_with($request->input('profile_image'), 'data:image/')) {
            $profileImagePath = app(ProfileImageService::class)->saveImage($request->input('profile_image'), $agent->profile_image);
        }

        $updateData = [
            'name' => $validated['name'],
            'agent_id' => $validated['agent_id'],
            'member_code' => $validated['agent_id'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'place' => $validated['place'] ?? null,
            'address' => $validated['address'] ?? null,
            'branch_id' => ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            'rank_id' => $validated['rank_id'],
            'parent_agent_id' => $request->has('parent_agent_id') ? ($validated['parent_agent_id'] ?? null) : $agent->parent_agent_id,
            'reporting_staff_id' => $request->has('reporting_staff_id') ? ($validated['reporting_staff_id'] ?? null) : $agent->reporting_staff_id,
            'joining_date' => $validated['joining_date'],
            'status' => $validated['status'],
            'profile_image' => $profileImagePath,
        ];

        if ($rawPassword !== null) {
            $updateData['password'] = Hash::make($rawPassword);
        }

        $agent->update($updateData);

        if ($agent->user) {
            $agent->user->update([
                'name' => $agent->name,
                'email' => $agent->email,
                'branch_id' => null,
                'profile_image' => $agent->profile_image,
            ]);
        }

        if ($rawPassword !== null || ! $agent->user_id || $profileImagePath !== $agent->getOriginal('profile_image')) {
            $this->syncAgentUser($agent, $rawPassword ?? 'password123');
        }

        $emailDispatched = false;
        if ($rawPassword !== null && $request->boolean('send_welcome_email') && ! empty($agent->email)) {
            try {
                $agent->loadMissing(['branch', 'rank']);
                Mail::to($agent->email)->send(new AccountWelcomeMail(
                    recipientName: $agent->name,
                    accountType: 'Agent',
                    loginEmail: $agent->email,
                    rawPassword: $rawPassword,
                    loginUrl: route('superadmin.login'),
                    identifier: $agent->agent_id,
                    branchName: $agent->branch?->name,
                    roleOrRank: $agent->rank?->name,
                    customMessage: 'Your agent portal login password has been updated by an administrator.'
                ));
                $emailDispatched = true;
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch updated agent credentials email to [{$agent->email}]: ".$e->getMessage());
            }
        }

        AuditLog::record(
            event: 'agent_updated',
            description: "Updated agent [{$agent->name}] ({$agent->agent_id}).",
            auditable: $agent,
            oldValues: $oldValues,
            newValues: $agent->only(['name', 'agent_id', 'email', 'phone', 'place', 'address', 'branch_id', 'rank_id', 'status'])
        );

        $flashMessage = "Agent '{$agent->name}' updated successfully.";
        if ($emailDispatched) {
            $flashMessage .= ' Updated credentials email has been dispatched.';
        }

        return redirect()->route('admin.agents.index')
            ->with('success', $flashMessage);
    }

    public function toggleStatus(Agent $agent)
    {
        $this->authorizeBranchAccess($agent->branch_id);

        $newStatus = $agent->status === 'active' ? 'inactive' : 'active';
        $agent->update(['status' => $newStatus]);

        $actionWord = $newStatus === 'active' ? 'activated' : 'deactivated';

        AuditLog::record(
            event: 'agent_status_toggled',
            description: "Agent [{$agent->name}] has been {$actionWord}.",
            auditable: $agent,
            newValues: ['status' => $newStatus]
        );

        return redirect()->back()
            ->with('success', "Agent '{$agent->name}' has been {$actionWord}.");
    }

    public function destroy(Agent $agent)
    {
        $this->authorizeBranchAccess($agent->branch_id);

        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action: Only Super Admin can delete agents.');
        }

        if ($agent->downlineAgents()->exists()) {
            return redirect()->back()
                ->with('error', "Cannot delete agent '{$agent->name}': They currently have direct downline agents in their network. Please reassign those downlines or deactivate this agent instead.");
        }

        if ($agent->customers()->exists()) {
            return redirect()->back()
                ->with('error', "Cannot delete agent '{$agent->name}': They currently have customer accounts assigned. Please reassign those customers or deactivate this agent instead.");
        }

        if ($agent->deposits()->exists() || $agent->collections()->exists()) {
            return redirect()->back()
                ->with('error', "Cannot delete agent '{$agent->name}': Financial deposits or collection records are linked to this agent. You can deactivate this agent instead.");
        }

        $agentName = $agent->name;
        $agentId = $agent->agent_id;
        $oldValues = $agent->only(['name', 'agent_id', 'email', 'phone', 'branch_id', 'rank_id', 'status']);

        DB::transaction(function () use ($agent, $agentName, $agentId, $oldValues) {
            // Delete associated user login account if exists and not super admin
            if ($agent->user && ! $agent->user->isSuperAdmin()) {
                $agent->user->roles()->detach();
                $agent->user->delete();
            }

            // Soft-delete the agent record
            $agent->delete();

            AuditLog::record(
                event: 'agent_deleted',
                description: 'Super Admin ['.auth()->user()->name."] deleted agent [{$agentName}] ({$agentId}).",
                auditable: $agent,
                oldValues: $oldValues
            );
        });

        return redirect()->route('admin.agents.index')
            ->with('success', "Agent '{$agentName}' ({$agentId}) deleted successfully.");
    }

    /**
     * Verify and accept a recruited or pending agent into active service (Super Admin only).
     * Super Admin can assign/confirm any branch or place upon acceptance.
     */
    public function accept(Request $request, Agent $agent)
    {
        $this->authorizeBranchAccess($agent->branch_id);

        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action: Only Super Admin can verify and accept recruited agents.');
        }

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'reporting_staff_id' => ['nullable', 'integer', 'exists:staff,id'],
        ]);

        $oldStatus = $agent->status;
        $oldBranchId = $agent->branch_id;

        $updateData = [
            'status' => Agent::STATUS_ACTIVE,
            'branch_id' => ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null,
        ];
        if ($request->has('place')) {
            $updateData['place'] = $validated['place'];
        }
        if ($request->has('address')) {
            $updateData['address'] = $validated['address'];
        }
        if (! empty($validated['reporting_staff_id'])) {
            $updateData['reporting_staff_id'] = (int) $validated['reporting_staff_id'];
        }

        $agent->update($updateData);

        if ($agent->user) {
            $agent->user->update([
                'branch_id' => $agent->branch_id,
                'email_verified_at' => $agent->user->email_verified_at ?? now(),
            ]);
        }

        $branchName = $agent->branch?->name ?? 'Free Field';
        $placeText = $agent->place ? " (Place: {$agent->place})" : '';

        // Dispatch Welcome / Acceptance notification email
        $emailDispatched = false;
        if (! empty($agent->email)) {
            try {
                $agent->loadMissing(['branch', 'rank']);
                Mail::to($agent->email)->send(new AccountWelcomeMail(
                    recipientName: $agent->name,
                    accountType: 'Agent',
                    loginEmail: $agent->email,
                    rawPassword: '(Your configured account password)',
                    loginUrl: route('superadmin.login'),
                    identifier: $agent->agent_id,
                    branchName: $agent->branch?->name,
                    roleOrRank: $agent->rank?->name,
                    customMessage: "Congratulations! Your agent registration has been officially verified and accepted by Central Administration, assigned to {$branchName}{$placeText}. Your account is now fully active."
                ));
                $emailDispatched = true;
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch agent acceptance email to [{$agent->email}]: ".$e->getMessage());
            }
        }

        AuditLog::record(
            event: 'agent_verified',
            description: 'Super Admin ['.auth()->user()->name."] verified and accepted agent [{$agent->name}] ({$agent->agent_id}) assigned to [{$branchName}]{$placeText}.",
            auditable: $agent,
            oldValues: ['status' => $oldStatus, 'branch_id' => $oldBranchId],
            newValues: ['status' => Agent::STATUS_ACTIVE, 'branch_id' => $agent->branch_id, 'place' => $agent->place]
        );

        $flashMessage = "Agent '{$agent->name}' ({$agent->agent_id}) has been verified, accepted, and assigned to {$branchName}{$placeText} successfully.";
        if ($emailDispatched) {
            $flashMessage .= " An official activation email has been sent to {$agent->email}.";
        }

        return redirect()->back()
            ->with('success', $flashMessage);
    }

    /**
     * Reject a recruited agent application (Super Admin only).
     */
    public function reject(Agent $agent)
    {
        $this->authorizeBranchAccess($agent->branch_id);

        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action: Only Super Admin can reject agent applications.');
        }

        $oldStatus = $agent->status;
        $agent->update(['status' => Agent::STATUS_REJECTED]);

        AuditLog::record(
            event: 'agent_rejected',
            description: 'Super Admin ['.auth()->user()->name."] rejected agent [{$agent->name}] ({$agent->agent_id}).",
            auditable: $agent,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => Agent::STATUS_REJECTED]
        );

        return redirect()->back()
            ->with('success', "Agent '{$agent->name}' ({$agent->agent_id}) has been rejected.");
    }

    /**
     * Ensure a User account exists and is synced for the agent.
     */
    protected function syncAgentUser(Agent $agent, string $password): User
    {
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], [
            'name' => 'Agent',
            'description' => 'Business and commission member with Member Code, rank, upline/downline hierarchy.',
            'status' => 'active',
        ]);

        $user = $agent->user_id ? User::find($agent->user_id) : null;

        if (! $user && ! empty($agent->email)) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($agent->email)])->first();
        }

        if ($user) {
            $user->update([
                'name' => $agent->name,
                'email' => $agent->email,
                'password' => Hash::make($password),
                'branch_id' => $agent->branch_id,
                'profile_image' => $agent->profile_image,
            ]);
            $user->roles()->syncWithoutDetaching([$agentRole->id]);

            if ((int) $agent->user_id !== (int) $user->id) {
                $agent->updateQuietly(['user_id' => $user->id]);
            }

            return $user;
        }

        $newUser = User::create([
            'name' => $agent->name,
            'email' => $agent->email,
            'password' => Hash::make($password),
            'branch_id' => $agent->branch_id,
            'profile_image' => $agent->profile_image,
            'email_verified_at' => now(),
        ]);

        $newUser->roles()->syncWithoutDetaching([$agentRole->id]);

        $agent->updateQuietly(['user_id' => $newUser->id]);

        return $newUser;
    }
}
