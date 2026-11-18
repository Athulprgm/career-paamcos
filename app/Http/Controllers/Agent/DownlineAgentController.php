<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Mail\AccountWelcomeMail;
use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\NumberingService;
use App\Services\PasswordGeneratorService;
use App\Services\ProfileImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class DownlineAgentController extends Controller
{
    /**
     * Get the authenticated agent profile or abort 403.
     */
    protected function getAuthenticatedAgent(): Agent
    {
        $user = auth()->user();
        $agent = $user?->agentProfile;

        if (! $agent) {
            abort(403, 'Unauthorized access: No agent profile linked to this account.');
        }

        return $agent;
    }

    /**
     * Display listing of direct downline agents recruited by the authenticated agent.
     */
    public function index(Request $request): View
    {
        $currentAgent = $this->getAuthenticatedAgent();

        $query = Agent::where('parent_agent_id', $currentAgent->id)
            ->with(['rank', 'branch'])
            ->withCount(['leads', 'downlineAgents']);

        // Search filter
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

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $downlines = $query->latest('joining_date')->paginate(15)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('agent.agents.partials.table', compact('downlines'))->render(),
                'total' => $downlines->total(),
                'first_item' => $downlines->firstItem() ?? 0,
                'last_item' => $downlines->lastItem() ?? 0,
                'count_text' => $downlines->total() === 1 ? '1 agent found' : number_format($downlines->total()).' agents found',
            ]);
        }

        // Team stats
        $totalDownlines = Agent::where('parent_agent_id', $currentAgent->id)->count();
        $activeDownlines = Agent::where('parent_agent_id', $currentAgent->id)->where('status', Agent::STATUS_ACTIVE)->count();
        $pendingDownlines = Agent::where('parent_agent_id', $currentAgent->id)->where('status', Agent::STATUS_PENDING)->count();
        $directDownlineIds = Agent::where('parent_agent_id', $currentAgent->id)->pluck('id');
        $totalTeamLeads = DB::table('leads')->whereIn('agent_id', $directDownlineIds)->count();

        return view('agent.agents.index', compact(
            'currentAgent',
            'downlines',
            'totalDownlines',
            'activeDownlines',
            'pendingDownlines',
            'totalTeamLeads'
        ));
    }

    /**
     * Show form to recruit/add a new downline agent under the authenticated agent.
     */
    public function create(): View
    {
        $currentAgent = $this->getAuthenticatedAgent();
        $currentAgent->load(['branch', 'rank']);

        // A recruiting agent can ONLY add same position or down priority (lower position) agents
        $currentAgentRank = $currentAgent->rank;
        $maxAllowedLevel = $currentAgentRank ? (int) ($currentAgentRank->level ?? $currentAgentRank->sort_order ?? 1) : (int) (AgentRank::active()->min('level') ?? 1);

        // Active ranks filtered to same position or down priority (level <= current agent level)
        $ranks = AgentRank::active()
            ->where('level', '<=', $maxAllowedLevel)
            ->orderBy('level', 'asc')
            ->get();
        $defaultRank = $ranks->first();

        // Suggested agent identifier
        try {
            $suggestedAgentId = app(NumberingService::class)->previewNext('agent');
        } catch (\Throwable) {
            $suggestedAgentId = 'PAG'.date('my').'1';
        }

        $branches = Branch::active()->orderBy('name')->get();

        return view('agent.agents.create', compact('currentAgent', 'ranks', 'defaultRank', 'suggestedAgentId', 'maxAllowedLevel', 'branches'));
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

    /**
     * Store a newly recruited downline agent under the authenticated agent.
     */
    public function store(Request $request): RedirectResponse
    {
        $currentAgent = $this->getAuthenticatedAgent();
        $currentAgent->load('rank');

        // A recruiting agent can ONLY add same position or down priority (lower position) agents
        $currentAgentRank = $currentAgent->rank;
        $maxAllowedLevel = $currentAgentRank ? (int) ($currentAgentRank->level ?? $currentAgentRank->sort_order ?? 1) : (int) (AgentRank::active()->min('level') ?? 1);

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
                        if ($targetRank && (int) $targetRank->level > $maxAllowedLevel) {
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
            'profile_image' => ['nullable', 'string'],
            'profile_image_file' => ['nullable', 'image', 'max:5120'],
        ], [
            'agent_id.unique' => 'This Agent ID is already registered in the system.',
            'email.unique' => 'This email address is already registered to another user or agent.',
            'rank_id.exists' => 'The selected agent rank is invalid.',
            'profile_image_file.image' => 'The profile image must be an image file (JPG, PNG, WebP).',
            'profile_image_file.max' => 'The profile image must not exceed 5MB.',
            'password.min' => 'The password must be at least 6 characters.',
        ]);

        $joiningDate = ! empty($validated['joining_date']) ? $validated['joining_date'] : now()->toDateString();

        // Resolve Agent ID: use provided or generate sequentially
        $agentId = ! empty($validated['agent_id'])
            ? trim($validated['agent_id'])
            : Agent::generateAgentId($validated['name'], $joiningDate);

        if (! empty($validated['agent_id'])) {
            app(NumberingService::class)->recordUsage('agent', $agentId);
        }

        // Resolve entry rank: default to lowest allowed rank
        $rankId = ! empty($validated['rank_id'])
            ? (int) $validated['rank_id']
            : AgentRank::active()->where('level', '<=', $maxAllowedLevel)->orderBy('level', 'asc')->value('id');

        $profileImagePath = null;
        if ($request->hasFile('profile_image_file')) {
            $profileImagePath = app(ProfileImageService::class)->saveImage($request->file('profile_image_file'));
        } elseif ($request->filled('profile_image')) {
            $profileImagePath = app(ProfileImageService::class)->saveImage($request->input('profile_image'));
        }

        $rawPassword = $request->filled('password')
            ? (string) $request->input('password')
            : PasswordGeneratorService::generate(10);

        $assignedBranchId = ! empty($validated['branch_id'])
            ? (int) $validated['branch_id']
            : ($currentAgent->branch_id ? (int) $currentAgent->branch_id : null);
        $place = ! empty($validated['place']) ? trim((string) $validated['place']) : null;

        $newAgent = DB::transaction(function () use ($validated, $agentId, $rawPassword, $currentAgent, $rankId, $joiningDate, $profileImagePath, $place, $assignedBranchId) {
            // Recruited downlines are placed in 'pending' status awaiting Super Admin verification and acceptance
            // Super Admin can assign or confirm any branch/place upon verification
            $agent = Agent::create([
                'name' => $validated['name'],
                'agent_id' => $agentId,
                'member_code' => $agentId,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'place' => $place,
                'address' => $validated['address'] ?? null,
                'password' => Hash::make($rawPassword),
                'branch_id' => $assignedBranchId,
                'rank_id' => $rankId,
                'parent_agent_id' => $currentAgent->id,
                'reporting_staff_id' => $currentAgent->reporting_staff_id,
                'joining_date' => $joiningDate,
                'status' => Agent::STATUS_PENDING,
                'profile_image' => $profileImagePath,
            ]);

            $this->syncAgentUser($agent, $rawPassword);

            AuditLog::record(
                event: 'agent_recruited',
                description: "Agent [{$currentAgent->name}] ({$currentAgent->agent_id}) registered downline agent [{$agent->name}] ({$agent->agent_id}) - Pending Super Admin verification.",
                auditable: $agent,
                newValues: $agent->only(['name', 'agent_id', 'email', 'phone', 'place', 'address', 'branch_id', 'rank_id', 'parent_agent_id', 'status'])
            );

            return $agent;
        });

        // Dispatch Welcome / Onboarding Credentials Email
        $emailDispatched = false;
        if ($request->boolean('send_welcome_email', true) && ! empty($newAgent->email)) {
            try {
                $newAgent->loadMissing(['branch', 'rank']);
                Mail::to($newAgent->email)->send(new AccountWelcomeMail(
                    recipientName: $newAgent->name,
                    accountType: 'Agent (Pending Verification)',
                    loginEmail: $newAgent->email,
                    rawPassword: $rawPassword,
                    loginUrl: route('superadmin.login'),
                    identifier: $newAgent->agent_id,
                    branchName: $newAgent->branch?->name,
                    roleOrRank: $newAgent->rank?->name,
                    customMessage: "You were registered as a team agent by {$currentAgent->name}. Your account is undergoing Super Admin verification and acceptance."
                ));
                $emailDispatched = true;
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch recruited agent credentials email to [{$newAgent->email}]: ".$e->getMessage());
            }
        }

        $flashMessage = "New agent '{$newAgent->name}' ({$newAgent->agent_id}) was successfully registered under your network and submitted for Super Admin verification and acceptance.";
        if ($emailDispatched) {
            $flashMessage .= " A welcome email with temporary login credentials has been sent to {$newAgent->email}.";
        }

        return redirect()->route('agent.agents.index')
            ->with('success', $flashMessage);
    }

    /**
     * Display details for a specific downline agent in the authenticated agent's network.
     */
    public function show(Agent $agent): View
    {
        $currentAgent = $this->getAuthenticatedAgent();

        // Enforce downline ownership isolation
        if ((int) $agent->parent_agent_id !== (int) $currentAgent->id) {
            abort(403, 'Unauthorized access: You can only view agents in your direct downline network.');
        }

        $agent->load(['rank', 'branch', 'leads.interestedScheme', 'downlineAgents']);

        return view('agent.agents.show', compact('currentAgent', 'agent'));
    }

    /**
     * Ensure a User login account exists and is synchronized for the agent.
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
