<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountWelcomeMail;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use App\Services\PasswordGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class StaffController extends Controller
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

        return $user ? ($user->branch_id ?? $user->staffProfile?->branch_id) : null;
    }

    /**
     * Enforce branch restriction on a specific branch ID.
     */
    protected function authorizeBranchAccess(?int $branchId): void
    {
        $restricted = $this->getRestrictedBranchId();
        if ($restricted !== null && (int) $branchId !== (int) $restricted) {
            abort(403, 'Unauthorized access: You are restricted to your assigned branch.');
        }
    }

    /**
     * Get list of allowed permission slugs.
     *
     * @return array<int, string>
     */
    protected function getAllowedPermissionSlugs(): array
    {
        $systemSlugs = [
            'view-ranks', 'manage-ranks', 'approve-promotions',
            'view-employees', 'manage-staff', 'manage-positions',
            'view-customers', 'create-customers', 'edit-customers', 'verify-kyc',
            'view-schemes', 'manage-schemes',
            'view-deposits', 'create-deposits', 'manage-deposits', 'verify-deposits', 'close-deposits',
            'view-commissions', 'approve-commissions', 'create-settlements', 'manage-commission-rules',
            'view-reports', 'export-reports',
            'view-agents', 'manage-agents',
            'view-leads', 'manage-leads',
            'view-branches', 'manage-branches',
            'view-settings', 'manage-settings',
            'view-audit-logs',
        ];

        try {
            $dbSlugs = Permission::pluck('slug')->all();
        } catch (\Throwable) {
            $dbSlugs = [];
        }

        return array_values(array_unique(array_merge($systemSlugs, $dbSlugs)));
    }

    public function index(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();

        $query = Staff::with(['branch', 'position', 'reportingManager', 'user.roles']);

        // Structural branch restriction
        if ($restrictedBranchId !== null) {
            $query->where('branch_id', $restrictedBranchId);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Position filter
        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $staffList = $query->orderBy('name')->paginate(15)->withQueryString();

        // Branches for filter dropdown
        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $positions = StaffPosition::active()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.staff.index', compact('staffList', 'branches', 'positions', 'restrictedBranchId'));
    }

    public function create()
    {
        $restrictedBranchId = $this->getRestrictedBranchId();

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $positions = StaffPosition::active()->orderBy('sort_order')->orderBy('name')->get();

        $roles = Role::with('permissions')->whereIn('slug', ['staff', 'employee', 'facilitator', 'finance'])
            ->orderBy('name')
            ->get();

        $permissions = Permission::orderBy('group')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                if (empty($p->group)) {
                    $p->group = 'settings';
                }

                return $p;
            })
            ->groupBy('group');

        // Potential reporting managers
        $managerQuery = Staff::active()->orderBy('name');
        if ($restrictedBranchId !== null) {
            $managerQuery->where('branch_id', $restrictedBranchId);
        }
        $managers = $managerQuery->get();

        return view('admin.staff.create', compact('branches', 'positions', 'managers', 'roles', 'permissions', 'restrictedBranchId'));
    }

    public function store(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();

        // If restricted, force branch_id
        if ($restrictedBranchId !== null) {
            $request->merge(['branch_id' => $restrictedBranchId]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_id' => ['required', 'string', 'max:50', 'unique:staff,employee_id', 'unique:staff,staff_code'],
            'email' => ['required', 'email', 'max:255', 'unique:staff,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['required', 'string', 'max:20'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($this->getAllowedPermissionSlugs())],
            'reporting_manager_id' => [
                'nullable',
                'integer',
                'exists:staff,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value) {
                        $manager = Staff::find($value);
                        if ($manager && (int) $manager->branch_id !== (int) $request->branch_id) {
                            $fail('Reporting manager must belong to the selected branch.');
                        }
                    }
                },
            ],
            'joining_date' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'employee_id.unique' => 'This Employee ID is already registered.',
            'email.unique' => 'This email address is already registered to another staff member.',
            'position_id.exists' => 'The selected staff position is invalid.',
            'branch_id.exists' => 'The selected branch is invalid.',
        ]);

        $this->authorizeBranchAccess($validated['branch_id']);

        $rawPassword = $request->filled('password')
            ? (string) $request->password
            : PasswordGeneratorService::generate(10);

        $roleId = $request->input('role_id');
        if (! $roleId && $request->filled('role')) {
            $roleVal = $request->input('role');
            $roleId = is_numeric($roleVal) ? (int) $roleVal : Role::where('slug', $roleVal)->value('id');
        }

        $staff = DB::transaction(function () use ($validated, $request, $rawPassword, $roleId) {
            $staff = Staff::create([
                'name' => $validated['name'],
                'employee_id' => $validated['employee_id'],
                'staff_code' => $validated['employee_id'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'branch_id' => $validated['branch_id'],
                'position_id' => $validated['position_id'],
                'reporting_manager_id' => $validated['reporting_manager_id'] ?? null,
                'joining_date' => $validated['joining_date'],
                'permissions' => $request->input('permissions', []),
                'status' => $validated['status'],
            ]);

            $this->syncStaffUser($staff, $rawPassword, $roleId);

            AuditLog::record(
                event: 'staff_created',
                description: "Created staff member [{$staff->name}] ({$staff->employee_id}).",
                auditable: $staff,
                newValues: $staff->only(['name', 'employee_id', 'email', 'phone', 'branch_id', 'position_id', 'status', 'user_id', 'permissions'])
            );

            return $staff;
        });

        // Dispatch Welcome Credentials Email
        $emailDispatched = false;
        if ($request->boolean('send_welcome_email', true) && ! empty($staff->email)) {
            try {
                $staff->loadMissing(['branch', 'position']);
                Mail::to($staff->email)->send(new AccountWelcomeMail(
                    recipientName: $staff->name,
                    accountType: 'Staff Member',
                    loginEmail: $staff->email,
                    rawPassword: $rawPassword,
                    loginUrl: route('superadmin.login'),
                    identifier: $staff->employee_id,
                    branchName: $staff->branch?->name,
                    roleOrRank: $staff->position?->name,
                    customMessage: "Welcome to the Pristine Agro team! Your operational workspace access has been established for [{$staff->branch?->name}]."
                ));
                $emailDispatched = true;
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch staff welcome credentials email to [{$staff->email}]: ".$e->getMessage());
            }
        }

        $flashMessage = "Staff member '{$staff->name}' created successfully.";
        if ($emailDispatched) {
            $flashMessage .= " Welcome email with login credentials has been sent to {$staff->email}.";
        }

        return redirect()->route('admin.staff.index')
            ->with('success', $flashMessage);
    }

    public function show(Staff $staff)
    {
        $this->authorizeBranchAccess($staff->branch_id);

        $staff->load([
            'branch',
            'position',
            'reportingManager',
            'directReports.position',
            'reportingAgents.rank',
            'user.roles',
        ]);

        $recentLogs = $staff->user_id
            ? AuditLog::where('user_id', $staff->user_id)->latest()->take(10)->get()
            : collect();
        $recentActivities = $recentLogs;

        $assignedPermissions = is_array($staff->permissions) ? $staff->permissions : ($staff->user?->permissions ?? []);

        return view('admin.staff.show', compact('staff', 'recentLogs', 'recentActivities', 'assignedPermissions'));
    }

    public function edit(Staff $staff)
    {
        $this->authorizeBranchAccess($staff->branch_id);

        $restrictedBranchId = $this->getRestrictedBranchId();

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $positions = StaffPosition::active()->orderBy('sort_order')->orderBy('name')->get();

        $roles = Role::with('permissions')->whereIn('slug', ['staff', 'employee', 'facilitator', 'finance'])
            ->orderBy('name')
            ->get();

        $permissions = Permission::orderBy('group')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                if (empty($p->group)) {
                    $p->group = 'settings';
                }

                return $p;
            })
            ->groupBy('group');

        $assignedRoleId = $staff->user?->roles->first()?->id ?? Role::where('slug', 'staff')->value('id');
        $assignedPermissions = is_array($staff->permissions) ? $staff->permissions : [];

        // Managers from the same branch, excluding self
        $managerQuery = Staff::active()
            ->where('id', '!=', $staff->id)
            ->orderBy('name');

        if ($restrictedBranchId !== null) {
            $managerQuery->where('branch_id', $restrictedBranchId);
        } else {
            $managerQuery->where('branch_id', $staff->branch_id);
        }

        $managers = $managerQuery->get();

        return view('admin.staff.edit', compact(
            'staff',
            'branches',
            'positions',
            'managers',
            'roles',
            'permissions',
            'assignedRoleId',
            'assignedPermissions',
            'restrictedBranchId'
        ));
    }

    public function update(Request $request, Staff $staff)
    {
        $this->authorizeBranchAccess($staff->branch_id);

        $restrictedBranchId = $this->getRestrictedBranchId();

        if ($restrictedBranchId !== null) {
            $request->merge(['branch_id' => $restrictedBranchId]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('staff', 'employee_id')->ignore($staff->id),
                Rule::unique('staff', 'staff_code')->ignore($staff->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('staff', 'email')->ignore($staff->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['required', 'string', 'max:20'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($this->getAllowedPermissionSlugs())],
            'reporting_manager_id' => [
                'nullable',
                'integer',
                'exists:staff,id',
                function ($attribute, $value, $fail) use ($staff, $request) {
                    if ($value) {
                        if ((int) $value === (int) $staff->id) {
                            $fail('A staff member cannot be their own reporting manager.');
                        }
                        $manager = Staff::find($value);
                        if ($manager && (int) $manager->branch_id !== (int) $request->branch_id) {
                            $fail('Reporting manager must belong to the selected branch.');
                        }
                    }
                },
            ],
            'joining_date' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'employee_id.unique' => 'This Employee ID is already registered.',
            'email.unique' => 'This email address is already registered to another staff member.',
            'position_id.exists' => 'The selected staff position is invalid.',
            'branch_id.exists' => 'The selected branch is invalid.',
        ]);

        $this->authorizeBranchAccess($validated['branch_id']);

        $oldValues = $staff->only(['name', 'employee_id', 'email', 'phone', 'branch_id', 'position_id', 'status', 'permissions']);

        $roleId = $request->input('role_id');
        if (! $roleId && $request->filled('role')) {
            $roleVal = $request->input('role');
            $roleId = is_numeric($roleVal) ? (int) $roleVal : Role::where('slug', $roleVal)->value('id');
        }

        DB::transaction(function () use ($staff, $validated, $request, $roleId) {
            $staff->update([
                'name' => $validated['name'],
                'employee_id' => $validated['employee_id'],
                'staff_code' => $validated['employee_id'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'branch_id' => $validated['branch_id'],
                'position_id' => $validated['position_id'],
                'reporting_manager_id' => $validated['reporting_manager_id'] ?? null,
                'joining_date' => $validated['joining_date'],
                'permissions' => $request->input('permissions', []),
                'status' => $validated['status'],
            ]);

            $rawPassword = $request->filled('password') ? (string) $request->password : null;
            $this->syncStaffUser($staff, $rawPassword, $roleId);
        });

        $emailDispatched = false;
        if ($request->filled('password') && $request->boolean('send_welcome_email') && ! empty($staff->email)) {
            try {
                $staff->loadMissing(['branch', 'position']);
                Mail::to($staff->email)->send(new AccountWelcomeMail(
                    recipientName: $staff->name,
                    accountType: 'Staff Member',
                    loginEmail: $staff->email,
                    rawPassword: (string) $request->password,
                    loginUrl: route('superadmin.login'),
                    identifier: $staff->employee_id,
                    branchName: $staff->branch?->name,
                    roleOrRank: $staff->position?->name,
                    customMessage: "Your staff portal credentials for [{$staff->branch?->name}] have been updated."
                ));
                $emailDispatched = true;
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch updated staff credentials email to [{$staff->email}]: ".$e->getMessage());
            }
        }

        AuditLog::record(
            event: 'staff_updated',
            description: "Updated staff member [{$staff->name}] ({$staff->employee_id}).",
            auditable: $staff,
            oldValues: $oldValues,
            newValues: $staff->only(['name', 'employee_id', 'email', 'phone', 'branch_id', 'position_id', 'status', 'user_id', 'permissions'])
        );

        $flashMessage = "Staff member '{$staff->name}' updated successfully.";
        if ($emailDispatched) {
            $flashMessage .= ' Updated credentials email has been dispatched.';
        }

        return redirect()->route('admin.staff.index')
            ->with('success', $flashMessage);
    }

    public function toggleStatus(Staff $staff)
    {
        $this->authorizeBranchAccess($staff->branch_id);

        $newStatus = $staff->status === 'active' ? 'inactive' : 'active';
        $staff->update(['status' => $newStatus]);

        $actionWord = $newStatus === 'active' ? 'activated' : 'deactivated';

        AuditLog::record(
            event: 'staff_status_toggled',
            description: "Staff member [{$staff->name}] has been {$actionWord}.",
            auditable: $staff,
            newValues: ['status' => $newStatus]
        );

        return redirect()->back()
            ->with('success', "Staff member '{$staff->name}' has been {$actionWord}.");
    }

    /**
     * Delete (soft-delete) the specified staff member.
     */
    public function destroy(Staff $staff)
    {
        $this->authorizeBranchAccess($staff->branch_id);

        if ($staff->directReports()->exists()) {
            return redirect()->route('admin.staff.index')
                ->with('error', "Cannot delete staff member '{$staff->name}': They currently have direct subordinates assigned. Please reassign those staff members first.");
        }

        if ($staff->reportingAgents()->exists()) {
            return redirect()->route('admin.staff.index')
                ->with('error', "Cannot delete staff member '{$staff->name}': They currently have agents assigned under their supervision. Please reassign those agents first.");
        }

        $staffName = $staff->name;
        $employeeId = $staff->employee_id;

        DB::transaction(function () use ($staff, $staffName, $employeeId) {
            // Delete user if associated and not super admin
            if ($staff->user && ! $staff->user->isSuperAdmin()) {
                $staff->user->roles()->detach();
                $staff->user->delete();
            }

            $staff->delete();

            AuditLog::record(
                event: 'staff_deleted',
                description: "Deleted staff member [{$staffName}] ({$employeeId}).",
                auditable: $staff,
                oldValues: [
                    'name' => $staffName,
                    'employee_id' => $employeeId,
                    'branch_id' => $staff->branch_id,
                ]
            );
        });

        return redirect()->route('admin.staff.index')
            ->with('success', "Staff member '{$staffName}' has been deleted successfully.");
    }

    /**
     * Ensure a User account exists and is synchronized for the staff member.
     */
    protected function syncStaffUser(Staff $staff, ?string $rawPassword = null, ?int $roleId = null): User
    {
        $user = $staff->user_id ? User::find($staff->user_id) : null;
        if (! $user && ! empty($staff->email)) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($staff->email)])->first();
        }

        $defaultRoleId = Role::where('slug', 'staff')->value('id');
        $roleToAssign = $roleId ?: ($user?->roles()->first()?->id ?: $defaultRoleId);

        if (! $user) {
            $password = $rawPassword ?: 'password123';
            $user = User::create([
                'name' => $staff->name,
                'email' => $staff->email,
                'password' => Hash::make($password),
                'branch_id' => $staff->branch_id,
                'email_verified_at' => now(),
            ]);
        } else {
            $data = [
                'name' => $staff->name,
                'email' => $staff->email,
                'branch_id' => $staff->branch_id,
            ];
            if (! empty($rawPassword)) {
                $data['password'] = Hash::make($rawPassword);
            }
            $user->update($data);
        }

        if ($roleToAssign) {
            $user->roles()->sync([$roleToAssign]);
        }

        if ($staff->user_id !== $user->id) {
            $staff->user_id = $user->id;
            $staff->saveQuietly();
        }

        return $user;
    }
}
