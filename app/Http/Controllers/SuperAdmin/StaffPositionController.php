<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\StaffPosition;
use Illuminate\Http\Request;

class StaffPositionController extends Controller
{
    public function index(Request $request)
    {
        $query = StaffPosition::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $positions = $query->orderBy('sort_order')->orderBy('name')->paginate(15)->withQueryString();

        return view('superadmin.staff-positions.index', compact('positions'));
    }

    public function create()
    {
        return view('superadmin.staff-positions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if (! isset($validated['sort_order']) || $validated['sort_order'] === null) {
            $maxOrder = StaffPosition::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxOrder + 1;
        }

        $position = StaffPosition::create($validated);

        AuditLog::record(
            event: 'staff_position_created',
            description: "Created staff position [{$position->name}].",
            auditable: $position,
            newValues: $position->only(['name', 'sort_order', 'status'])
        );

        return redirect()->route('superadmin.staff-positions.index')
            ->with('success', 'Staff position created successfully.');
    }

    public function edit(StaffPosition $staffPosition)
    {
        return view('superadmin.staff-positions.edit', compact('staffPosition'));
    }

    public function update(Request $request, StaffPosition $staffPosition)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name,'.$staffPosition->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if (! isset($validated['sort_order']) || $validated['sort_order'] === null) {
            $validated['sort_order'] = $staffPosition->sort_order ?? 1;
        }

        $oldValues = $staffPosition->only(['name', 'sort_order', 'status']);
        $staffPosition->update($validated);

        AuditLog::record(
            event: 'staff_position_updated',
            description: "Updated staff position [{$staffPosition->name}].",
            auditable: $staffPosition,
            oldValues: $oldValues,
            newValues: $staffPosition->only(['name', 'sort_order', 'status'])
        );

        return redirect()->route('superadmin.staff-positions.index')
            ->with('success', 'Staff position updated successfully.');
    }

    public function destroy(StaffPosition $staffPosition)
    {
        if ($staffPosition->staff()->exists()) {
            return redirect()->route('superadmin.staff-positions.index')
                ->with('error', "Cannot delete staff position [{$staffPosition->name}] because it is currently assigned to staff member(s). Please reassign them first or deactivate this position.");
        }

        try {
            $name = $staffPosition->name;
            $oldValues = $staffPosition->only(['name', 'sort_order', 'status']);

            AuditLog::record(
                event: 'staff_position_deleted',
                description: "Deleted staff position [{$name}].",
                auditable: $staffPosition,
                oldValues: $oldValues
            );

            $staffPosition->delete();

            return redirect()->route('superadmin.staff-positions.index')
                ->with('success', "Staff position [{$name}] deleted successfully.");
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.staff-positions.index')
                ->with('error', "Could not delete staff position [{$staffPosition->name}]: {$e->getMessage()}");
        }
    }

    public function toggleStatus(StaffPosition $staffPosition)
    {
        $newStatus = $staffPosition->status === 'active' ? 'inactive' : 'active';
        $staffPosition->update(['status' => $newStatus]);

        AuditLog::record(
            event: 'staff_position_status_toggled',
            description: "Staff position [{$staffPosition->name}] status changed to {$newStatus}.",
            auditable: $staffPosition,
            newValues: ['status' => $newStatus]
        );

        return redirect()->back()
            ->with('success', "Staff position {$newStatus} successfully.");
    }
}
