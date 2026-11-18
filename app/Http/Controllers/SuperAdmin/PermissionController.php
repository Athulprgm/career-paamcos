<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Staff;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionController extends Controller
{
    /**
     * Display permissions list, role matrices, and staff permission allocation.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $groupFilter = trim((string) $request->input('group', ''));

        $query = Permission::with('roles')->orderBy('group')->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($groupFilter !== '') {
            $query->where('group', $groupFilter);
        }

        $allPermissions = $query->get()->map(function ($p) {
            if (empty($p->group)) {
                $p->group = 'general';
            }

            return $p;
        });

        // Group permissions by category
        $groupedPermissions = $allPermissions->groupBy('group');

        // All distinct groups in system for filter / create
        $availableGroups = Permission::select('group')
            ->distinct()
            ->whereNotNull('group')
            ->where('group', '!=', '')
            ->orderBy('group')
            ->pluck('group')
            ->toArray();

        if (empty($availableGroups)) {
            $availableGroups = ['deposits', 'commissions', 'customers', 'staff', 'leads', 'ranks', 'reports', 'settings', 'general'];
        }

        // System roles with assigned permissions
        $roles = Role::with('permissions')->orderBy('id')->get();

        // Staff members for allocation
        $staffMembers = Staff::with(['branch', 'position', 'user.roles'])
            ->orderBy('name')
            ->get();

        // Currently selected staff for allocation tab (if any)
        $selectedStaffId = $request->input('staff_id');
        $selectedStaff = $selectedStaffId ? $staffMembers->firstWhere('id', (int) $selectedStaffId) : null;
        if (! $selectedStaff && $staffMembers->isNotEmpty()) {
            $selectedStaff = $staffMembers->first();
        }

        // Effective permissions for selected staff member
        $staffAssignedPerms = [];
        if ($selectedStaff) {
            $staffAssignedPerms = is_array($selectedStaff->permissions)
                ? $selectedStaff->permissions
                : ($selectedStaff->user?->permissions()->toArray() ?? []);
        }

        // Currently selected role for role allocation tab
        $selectedRoleId = $request->input('role_id');
        $selectedRole = $selectedRoleId ? $roles->firstWhere('id', (int) $selectedRoleId) : null;
        if (! $selectedRole && $roles->isNotEmpty()) {
            $selectedRole = $roles->firstWhere('slug', 'staff') ?? $roles->first();
        }

        return view('superadmin.permissions.index', compact(
            'allPermissions',
            'groupedPermissions',
            'availableGroups',
            'roles',
            'staffMembers',
            'selectedStaff',
            'staffAssignedPerms',
            'selectedRole',
            'search',
            'groupFilter'
        ));
    }

    /**
     * Store a newly created permission (Permission add option by Super Admin).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:permissions,slug'],
            'group' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'assign_roles' => ['nullable', 'array'],
            'assign_roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Permission::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $group = Str::lower(trim($validated['group']));

        $permission = Permission::create([
            'name' => trim($validated['name']),
            'slug' => $slug,
            'group' => $group,
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
        ]);

        // Attach to selected roles if provided
        if (! empty($validated['assign_roles'])) {
            $permission->roles()->syncWithoutDetaching($validated['assign_roles']);
        }

        AuditLog::record(
            event: 'permission_created',
            description: 'Super Admin ['.auth()->user()->name."] created permission [{$permission->name}] ({$permission->slug}) in group [{$permission->group}].",
            auditable: $permission,
            newValues: $permission->only(['name', 'slug', 'group', 'description'])
        );

        return redirect()->route('superadmin.permissions.index', ['group' => $permission->group])
            ->with('success', "Permission '{$permission->name}' ({$permission->slug}) has been created successfully.");
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('permissions', 'slug')->ignore($permission->id)],
            'group' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $oldValues = $permission->only(['name', 'slug', 'group', 'description']);

        $permission->update([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['slug']),
            'group' => Str::lower(trim($validated['group'])),
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
        ]);

        AuditLog::record(
            event: 'permission_updated',
            description: 'Super Admin ['.auth()->user()->name."] updated permission [{$permission->name}] ({$permission->slug}).",
            auditable: $permission,
            oldValues: $oldValues,
            newValues: $permission->only(['name', 'slug', 'group', 'description'])
        );

        return redirect()->route('superadmin.permissions.index')
            ->with('success', "Permission '{$permission->name}' has been updated successfully.");
    }

    /**
     * Delete the specified permission.
     */
    public function destroy(Permission $permission): RedirectResponse
    {
        $permName = $permission->name;
        $permSlug = $permission->slug;

        // Detach from all roles
        $permission->roles()->detach();
        $permission->delete();

        AuditLog::record(
            event: 'permission_deleted',
            description: 'Super Admin ['.auth()->user()->name."] deleted permission [{$permName}] ({$permSlug}).",
            auditable: $permission,
            oldValues: ['name' => $permName, 'slug' => $permSlug]
        );

        return redirect()->route('superadmin.permissions.index')
            ->with('success', "Permission '{$permName}' has been removed successfully.");
    }

    /**
     * Allocate granular permissions to a specific staff member.
     */
    public function allocateStaff(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $staff = Staff::with('user')->findOrFail($validated['staff_id']);
        $oldPerms = $staff->permissions;

        $newPerms = array_values(array_unique($request->input('permissions', [])));

        $staff->update([
            'permissions' => $newPerms,
        ]);

        AuditLog::record(
            event: 'staff_permissions_allocated',
            description: 'Super Admin ['.auth()->user()->name."] updated permission allocation for staff [{$staff->name}] ({$staff->employee_id}). Assigned ".count($newPerms).' permissions.',
            auditable: $staff,
            oldValues: ['permissions' => $oldPerms],
            newValues: ['permissions' => $newPerms]
        );

        return redirect()->route('superadmin.permissions.index', ['staff_id' => $staff->id, 'tab' => 'staff'])
            ->with('success', "Permissions successfully allocated to staff member '{$staff->name}'.");
    }

    /**
     * Allocate default permissions to a system role.
     */
    public function allocateRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $newPermIds = $request->input('permissions', []);

        $role->permissions()->sync($newPermIds);

        AuditLog::record(
            event: 'role_permissions_allocated',
            description: 'Super Admin ['.auth()->user()->name."] updated default permissions for role [{$role->name}]. Total assigned: ".count($newPermIds).'.',
            auditable: $role,
            newValues: ['permission_ids' => $newPermIds]
        );

        return redirect()->route('superadmin.permissions.index', ['role_id' => $role->id, 'tab' => 'roles'])
            ->with('success', "Default permissions for role '{$role->name}' have been updated successfully.");
    }

    /**
     * Seed or re-synchronize default system permissions and role assignments.
     */
    public function seed(): RedirectResponse
    {
        $seeder = new PermissionSeeder;
        $seeder->run();

        AuditLog::record(
            event: 'permissions_seeded',
            description: 'Super Admin ['.auth()->user()->name.'] seeded and synchronized standard system permissions.',
            auditable: auth()->user(),
        );

        return redirect()->route('superadmin.permissions.index')
            ->with('success', 'Standard system permissions have been successfully seeded and mapped to system roles.');
    }
}
