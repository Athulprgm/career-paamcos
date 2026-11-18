<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AgentRank;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AgentRankController extends Controller
{
    public function index(Request $request)
    {
        $query = AgentRank::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $ranks = $query->orderBy('sort_order')->orderBy('name')->paginate(15)->withQueryString();

        return view('superadmin.agent-ranks.index', compact('ranks'));
    }

    public function create()
    {
        return view('superadmin.agent-ranks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:ranks,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'minimum_deposit_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (array_key_exists('minimum_deposit_amount', $validated) && $validated['minimum_deposit_amount'] !== null) {
            $validated['minimum_deposit_amount'] = (float) $validated['minimum_deposit_amount'];
            $validated['min_deposit_amount'] = $validated['minimum_deposit_amount'];
        }

        // Auto-assign a unique level (max existing + 1)
        $maxLevel = AgentRank::max('level') ?? 0;
        $validated['level'] = $maxLevel + 1;
        // Generate a unique code
        $baseCode = strtoupper(preg_replace('/\s+/', '_', $validated['name']));
        $code = $baseCode;
        $suffix = 1;
        while (AgentRank::where('code', $code)->exists()) {
            $code = $baseCode.'_'.$suffix++;
        }
        $validated['code'] = $code;

        $agentRank = AgentRank::create($validated);

        AuditLog::record(
            event: 'agent_rank_created',
            description: "Created agent rank [{$agentRank->name}] ({$agentRank->code}).",
            auditable: $agentRank,
            newValues: $agentRank->only(['name', 'code', 'level', 'sort_order', 'status'])
        );

        return redirect()->route('superadmin.agent-ranks.index')
            ->with('success', 'Agent rank created successfully.');
    }

    public function edit(AgentRank $agentRank)
    {
        return view('superadmin.agent-ranks.edit', compact('agentRank'));
    }

    public function update(Request $request, AgentRank $agentRank)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:ranks,name,'.$agentRank->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'minimum_deposit_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (array_key_exists('minimum_deposit_amount', $validated)) {
            $validated['minimum_deposit_amount'] = $validated['minimum_deposit_amount'] !== null ? (float) $validated['minimum_deposit_amount'] : 0.00;
            $validated['min_deposit_amount'] = $validated['minimum_deposit_amount'];
        }

        // Keep the existing level; only update code if name changed
        $baseCode = strtoupper(preg_replace('/\s+/', '_', $validated['name']));
        $code = $baseCode;
        $suffix = 1;
        while (AgentRank::where('code', $code)->where('id', '!=', $agentRank->id)->exists()) {
            $code = $baseCode.'_'.$suffix++;
        }
        $validated['code'] = $code;
        // Keep existing level; level is immutable once set
        unset($validated['level']);

        $oldValues = $agentRank->only(['name', 'code', 'sort_order', 'status', 'minimum_deposit_amount']);
        $agentRank->update($validated);

        AuditLog::record(
            event: 'agent_rank_updated',
            description: "Updated agent rank [{$agentRank->name}].",
            auditable: $agentRank,
            oldValues: $oldValues,
            newValues: $agentRank->only(['name', 'code', 'sort_order', 'status', 'minimum_deposit_amount'])
        );

        return redirect()->route('superadmin.agent-ranks.index')
            ->with('success', 'Agent rank updated successfully.');
    }

    public function destroy(AgentRank $agentRank)
    {
        if ($agentRank->agents()->exists()) {
            return redirect()->route('superadmin.agent-ranks.index')
                ->with('error', "Cannot delete agent rank [{$agentRank->name}] because it is currently assigned to agent(s). Please reassign them first or deactivate this rank.");
        }

        if ($agentRank->commissionRules()->exists()) {
            return redirect()->route('superadmin.agent-ranks.index')
                ->with('error', "Cannot delete agent rank [{$agentRank->name}] because it is used in commission rules. Please reassign or remove those rules first.");
        }

        try {
            $name = $agentRank->name;
            $oldValues = $agentRank->only(['name', 'code', 'sort_order', 'status']);

            AuditLog::record(
                event: 'agent_rank_deleted',
                description: "Deleted agent rank [{$name}].",
                auditable: $agentRank,
                oldValues: $oldValues
            );

            $agentRank->delete();

            return redirect()->route('superadmin.agent-ranks.index')
                ->with('success', "Agent rank [{$name}] deleted successfully.");
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.agent-ranks.index')
                ->with('error', "Could not delete agent rank [{$agentRank->name}]: {$e->getMessage()}");
        }
    }

    public function toggleStatus(AgentRank $agentRank)
    {
        $newStatus = $agentRank->status === 'active' ? 'inactive' : 'active';
        $agentRank->update(['status' => $newStatus]);

        AuditLog::record(
            event: 'agent_rank_status_toggled',
            description: "Agent rank [{$agentRank->name}] status changed to {$newStatus}.",
            auditable: $agentRank,
            newValues: ['status' => $newStatus]
        );

        return redirect()->back()
            ->with('success', "Agent rank {$newStatus} successfully.");
    }
}
