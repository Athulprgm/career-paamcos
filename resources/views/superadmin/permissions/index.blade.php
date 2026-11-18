@extends('superadmin.layouts.app')

@section('page-title', 'Permissions & Staff Allocation')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title h3 mb-1">Permissions & Access Control</h1>
        <div class="page-breadcrumb text-muted small">
            <span>Administration</span>
            <span class="mx-1">/</span>
            <span>Security & Access</span>
            <span class="mx-1">/</span>
            <span class="text-primary fw-medium">Staff & Role Permission Allocation</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form action="{{ route('superadmin.permissions.seed') }}" method="POST" onsubmit="return confirm('Seed and synchronize standard system permissions with system roles?');" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1 shadow-sm px-3" title="Seed and sync system permissions">
                <i class="bi bi-arrow-repeat"></i>
                <span>Seed / Sync Permissions</span>
            </button>
        </form>
        <button type="button" class="btn btn-dark btn-sm d-flex align-items-center gap-1 shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAddPermission">
            <i class="bi bi-plus-circle"></i>
            <span>Add New Permission</span>
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill fs-5 text-success"></i>
    <div>{{ session('success') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2 mb-1 fw-bold">
        <i class="bi bi-exclamation-triangle-fill text-danger"></i>
        <span>Please resolve the following issues:</span>
    </div>
    <ul class="mb-0 ps-3 small">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Navigation Tabs --}}
<ul class="nav nav-pills mb-4 gap-2" id="permissionTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ request('tab', 'catalog') === 'catalog' ? 'active' : '' }} fw-semibold px-3 py-2 rounded-2"
                id="catalog-tab" data-bs-toggle="pill" data-bs-target="#catalog" type="button" role="tab">
            <i class="bi bi-collection-fill me-1"></i> Permissions Directory ({{ $allPermissions->count() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ request('tab') === 'staff' ? 'active' : '' }} fw-semibold px-3 py-2 rounded-2"
                id="staff-tab" data-bs-toggle="pill" data-bs-target="#staff" type="button" role="tab">
            <i class="bi bi-person-gear me-1"></i> Staff Permission Allocation
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ request('tab') === 'roles' ? 'active' : '' }} fw-semibold px-3 py-2 rounded-2"
                id="roles-tab" data-bs-toggle="pill" data-bs-target="#roles" type="button" role="tab">
            <i class="bi bi-shield-lock-fill me-1"></i> Role Default Permissions
        </button>
    </li>
</ul>

<div class="tab-content" id="permissionTabsContent">

    {{-- ── TAB 1: Permissions Catalog ───────────────────────────────────── --}}
    <div class="tab-pane fade {{ request('tab', 'catalog') === 'catalog' ? 'show active' : '' }}" id="catalog" role="tabpanel">
        
        {{-- Filter Bar --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('superadmin.permissions.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="tab" value="catalog">
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search permission name or slug..." value="{{ $search }}">
                    </div>

                    <select name="group" class="form-select form-select-sm" style="max-width: 200px;" onchange="this.form.submit()">
                        <option value="">All Categories ({{ count($availableGroups) }})</option>
                        @foreach($availableGroups as $grp)
                            <option value="{{ $grp }}" {{ $groupFilter === $grp ? 'selected' : '' }}>
                                {{ ucfirst($grp) }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-sm btn-outline-dark">Filter</button>
                    @if($search || $groupFilter)
                        <a href="{{ route('superadmin.permissions.index', ['tab' => 'catalog']) }}" class="btn btn-sm btn-light text-muted">Clear</a>
                    @endif

                    <div class="ms-auto text-muted small">
                        Showing <strong>{{ $allPermissions->count() }}</strong> defined operational permissions
                    </div>
                </form>
            </div>
        </div>

        {{-- Grouped Permissions List --}}
        <div class="row g-4">
            @forelse($groupedPermissions as $groupName => $perms)
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-dark px-2.5 py-1 text-uppercase font-monospace" style="font-size: 0.72rem;">{{ $groupName }}</span>
                            <span class="fw-bold text-dark fs-6">{{ ucwords(str_replace(['_', '-'], ' ', $groupName)) }} Module</span>
                            <span class="text-muted small">({{ $perms->count() }} permissions)</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th style="width: 28%;">Permission Name</th>
                                    <th style="width: 22%;">Slug Identifier</th>
                                    <th style="width: 30%;">Description & Purpose</th>
                                    <th style="width: 10%;">Roles</th>
                                    <th class="text-end" style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($perms as $perm)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $perm->name }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace px-2 py-1">{{ $perm->slug }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $perm->description ?? 'Standard system operational authority.' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                            {{ $perm->roles->count() }} roles
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-dark py-0 px-2"
                                                    onclick="editPermissionModal({{ json_encode($perm) }})"
                                                    title="Edit Permission">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('superadmin.permissions.destroy', $perm) }}" method="POST"
                                                  onsubmit="return confirm('Delete permission [{{ $perm->slug }}]? Any roles or staff holding this will lose the privilege.');"
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete Permission">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 py-5 text-center text-muted">
                    <i class="bi bi-shield-slash display-4 d-block mb-3 text-secondary"></i>
                    <h5>No permissions matched your search criteria.</h5>
                    <p class="small text-muted mb-3">Add a new operational permission or seed the standard system permissions catalog.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <form action="{{ route('superadmin.permissions.seed') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-dark btn-sm px-3">
                                <i class="bi bi-arrow-repeat me-1"></i> Seed Default Permissions
                            </button>
                        </form>
                        <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPermission">
                            Add Permission
                        </button>
                    </div>
                </div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── TAB 2: Staff Permission Allocation ───────────────────────────── --}}
    <div class="tab-pane fade {{ request('tab') === 'staff' ? 'show active' : '' }}" id="staff" role="tabpanel">
        
        <div class="row g-4">
            {{-- Left: Staff Member Selector --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 sticky-top" style="top: 80px;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-people-fill text-primary"></i> Select Staff Member
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Select Staff</label>
                            <select class="form-select form-select-sm" id="staffSelector" onchange="window.location.href='{{ route('superadmin.permissions.index', ['tab' => 'staff']) }}&staff_id=' + this.value">
                                @foreach($staffMembers as $s)
                                    <option value="{{ $s->id }}" {{ $selectedStaff && $selectedStaff->id === $s->id ? 'selected' : '' }}>
                                        {{ $s->name }} ({{ $s->employee_id }}) — {{ $s->branch?->name ?? 'Head Office' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if($selectedStaff)
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fw-bold text-dark fs-6">{{ $selectedStaff->name }}</div>
                            <div class="text-muted small mb-2">{{ $selectedStaff->email }}</div>

                            <hr class="my-2 border-secondary-subtle">

                            <div class="d-flex justify-content-between small py-1">
                                <span class="text-muted">Staff Code:</span>
                                <span class="fw-semibold font-monospace">{{ $selectedStaff->employee_id }}</span>
                            </div>
                            <div class="d-flex justify-content-between small py-1">
                                <span class="text-muted">Branch:</span>
                                <span class="fw-semibold">{{ $selectedStaff->branch?->name ?? 'All Branches' }}</span>
                            </div>
                            <div class="d-flex justify-content-between small py-1">
                                <span class="text-muted">Position:</span>
                                <span class="fw-semibold">{{ $selectedStaff->position?->name ?? 'Operational Staff' }}</span>
                            </div>
                            <div class="d-flex justify-content-between small py-1">
                                <span class="text-muted">System Role:</span>
                                <span class="badge bg-dark">{{ $selectedStaff->user?->roles->first()?->name ?? 'Staff' }}</span>
                            </div>
                            <div class="d-flex justify-content-between small py-1">
                                <span class="text-muted">Status:</span>
                                <span class="badge {{ $selectedStaff->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                                    {{ ucfirst($selectedStaff->status) }}
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right: Permission Checklist for Staff Member --}}
            <div class="col-lg-8">
                @if($selectedStaff)
                <form action="{{ route('superadmin.permissions.allocate-staff') }}" method="POST">
                    @csrf
                    <input type="hidden" name="staff_id" value="{{ $selectedStaff->id }}">

                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Operational Permissions for {{ $selectedStaff->name }}</h5>
                                <span class="text-muted small">Checked permissions grant active authorization inside the Staff Workspace</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-dark" onclick="toggleAllCheckboxes('.staff-perm-checkbox', true)">
                                    <i class="bi bi-check2-all me-1"></i>Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllCheckboxes('.staff-perm-checkbox', false)">
                                    <i class="bi bi-dash-circle me-1"></i>Clear
                                </button>
                                <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm">
                                    <i class="bi bi-save me-1"></i>Save Permissions
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                @foreach($groupedPermissions as $category => $items)
                                <div class="col-md-6">
                                    <div class="card h-100 border rounded-3">
                                        <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark text-capitalize small">
                                                {{ ucwords(str_replace(['_', '-'], ' ', $category)) }}
                                            </span>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted small"
                                                    onclick="toggleCategoryCheckboxes('cat_{{ Str::slug($category) }}')">
                                                Toggle
                                            </button>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="d-flex flex-column gap-2">
                                                @foreach($items as $perm)
                                                <div class="form-check">
                                                    <input class="form-check-input staff-perm-checkbox cat_{{ Str::slug($category) }}"
                                                           type="checkbox"
                                                           name="permissions[]"
                                                           value="{{ $perm->slug }}"
                                                           id="staff_perm_{{ $perm->slug }}"
                                                           {{ in_array($perm->slug, $staffAssignedPerms, true) ? 'checked' : '' }}>
                                                    <label class="form-check-label d-flex flex-column" for="staff_perm_{{ $perm->slug }}">
                                                        <span class="fw-semibold text-dark small">{{ $perm->name }}</span>
                                                        <span class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $perm->slug }}</span>
                                                    </label>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="bi bi-shield-check me-1"></i>Save Staff Permissions
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                @else
                <div class="card border-0 shadow-sm rounded-3 py-5 text-center text-muted">
                    <h5>No staff members found in system.</h5>
                    <p class="small text-muted">Register staff accounts in the Staff Module first.</p>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ── TAB 3: Role Default Permissions ─────────────────────────────── --}}
    <div class="tab-pane fade {{ request('tab') === 'roles' ? 'show active' : '' }}" id="roles" role="tabpanel">
        
        <div class="row g-4">
            {{-- Left: Role Selector --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-person-badge-fill text-primary"></i> System Access Roles
                        </h6>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($roles as $r)
                        <a href="{{ route('superadmin.permissions.index', ['tab' => 'roles', 'role_id' => $r->id]) }}"
                           class="list-group-item list-group-item-action py-3 {{ $selectedRole && $selectedRole->id === $r->id ? 'active' : '' }}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold">{{ $r->name }}</span>
                                <span class="badge {{ $selectedRole && $selectedRole->id === $r->id ? 'bg-light text-dark' : 'bg-dark' }}">
                                    {{ $r->permissions->count() }} perms
                                </span>
                            </div>
                            <small class="{{ $selectedRole && $selectedRole->id === $r->id ? 'text-white-50' : 'text-muted' }} d-block font-monospace">
                                {{ $r->slug }}
                            </small>
                            <small class="{{ $selectedRole && $selectedRole->id === $r->id ? 'text-light' : 'text-muted' }} d-block mt-1">
                                {{ Str::limit($r->description ?? 'System standard access role', 60) }}
                            </small>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right: Role Permissions Matrix --}}
            <div class="col-lg-8">
                @if($selectedRole)
                @php
                    $rolePermIds = $selectedRole->permissions->pluck('id')->toArray();
                @endphp
                <form action="{{ route('superadmin.permissions.allocate-role') }}" method="POST">
                    @csrf
                    <input type="hidden" name="role_id" value="{{ $selectedRole->id }}">

                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Role Permissions: {{ $selectedRole->name }}</h5>
                                <span class="text-muted small">Inherited by all users assigned to role <code>{{ $selectedRole->slug }}</code></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-dark" onclick="toggleAllCheckboxes('.role-perm-checkbox', true)">
                                    <i class="bi bi-check2-all me-1"></i>Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllCheckboxes('.role-perm-checkbox', false)">
                                    <i class="bi bi-dash-circle me-1"></i>Clear
                                </button>
                                <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm">
                                    <i class="bi bi-save me-1"></i>Save Role
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                @foreach($groupedPermissions as $category => $items)
                                <div class="col-md-6">
                                    <div class="card h-100 border rounded-3">
                                        <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark text-capitalize small">
                                                {{ ucwords(str_replace(['_', '-'], ' ', $category)) }}
                                            </span>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted small"
                                                    onclick="toggleCategoryCheckboxes('rolecat_{{ Str::slug($category) }}')">
                                                Toggle
                                            </button>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="d-flex flex-column gap-2">
                                                @foreach($items as $perm)
                                                <div class="form-check">
                                                    <input class="form-check-input role-perm-checkbox rolecat_{{ Str::slug($category) }}"
                                                           type="checkbox"
                                                           name="permissions[]"
                                                           value="{{ $perm->id }}"
                                                           id="role_perm_{{ $perm->id }}"
                                                           {{ in_array($perm->id, $rolePermIds, true) ? 'checked' : '' }}>
                                                    <label class="form-check-label d-flex flex-column" for="role_perm_{{ $perm->id }}">
                                                        <span class="fw-semibold text-dark small">{{ $perm->name }}</span>
                                                        <span class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $perm->slug }}</span>
                                                    </label>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="bi bi-shield-check me-1"></i>Update {{ $selectedRole->name }} Permissions
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                @endif
            </div>
        </div>

    </div>

</div>

{{-- ── MODAL: Add New Permission (Super Admin Work) ──────────────────────── --}}
<div class="modal fade" id="modalAddPermission" tabindex="-1" aria-labelledby="modalAddPermissionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('superadmin.permissions.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fs-6 fw-bold" id="modalAddPermissionLabel">
                        <i class="bi bi-shield-plus me-1"></i> Add Operational Permission
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="perm_name" class="form-label fw-semibold small">Permission Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="perm_name" name="name" required
                               placeholder="e.g. Verify KYC Applications, Manage Leads"
                               oninput="autoGenerateSlug(this.value, 'perm_slug')">
                        <div class="form-text text-muted small">Human-friendly name shown across staff allocation checklists.</div>
                    </div>

                    <div class="mb-3">
                        <label for="perm_slug" class="form-label fw-semibold small">Slug Identifier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace" id="perm_slug" name="slug"
                               placeholder="e.g. verify-deposits, manage-leads">
                        <div class="form-text text-muted small">Unique code used for internal authorization checks. Lowercase, dashes.</div>
                    </div>

                    <div class="mb-3">
                        <label for="perm_group" class="form-label fw-semibold small">Module Category <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="perm_group" name="group" required
                               list="groupSuggestions" placeholder="e.g. deposits, commissions, staff, leads">
                        <datalist id="groupSuggestions">
                            @foreach($availableGroups as $grp)
                                <option value="{{ $grp }}">{{ ucfirst($grp) }}</option>
                            @endforeach
                            <option value="leads">Leads</option>
                            <option value="customers">Customers</option>
                            <option value="deposits">Deposits</option>
                            <option value="commissions">Commissions</option>
                            <option value="staff">Staff</option>
                            <option value="reports">Reports</option>
                            <option value="settings">Settings</option>
                        </datalist>
                        <div class="form-text text-muted small">Select an existing category or type a new one.</div>
                    </div>

                    <div class="mb-3">
                        <label for="perm_desc" class="form-label fw-semibold small">Description</label>
                        <textarea class="form-control" id="perm_desc" name="description" rows="2"
                                  placeholder="Describe what operational capabilities this privilege provides..."></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold small mb-2">Assign Automatically to Roles (Optional)</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($roles as $r)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="assign_roles[]" value="{{ $r->id }}" id="add_role_{{ $r->id }}">
                                <label class="form-check-label small" for="add_role_{{ $r->id }}">{{ $r->name }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark btn-sm px-3">Create Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── MODAL: Edit Permission ────────────────────────────────────────── --}}
<div class="modal fade" id="modalEditPermission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formEditPermission" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fs-6 fw-bold">
                        <i class="bi bi-pencil-square me-1"></i> Edit Permission
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_perm_name" class="form-label fw-semibold small">Permission Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_perm_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_perm_slug" class="form-label fw-semibold small">Slug Identifier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace" id="edit_perm_slug" name="slug" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_perm_group" class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_perm_group" name="group" required list="groupSuggestions">
                    </div>

                    <div class="mb-0">
                        <label for="edit_perm_desc" class="form-label fw-semibold small">Description</label>
                        <textarea class="form-control" id="edit_perm_desc" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark btn-sm px-3">Update Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function autoGenerateSlug(text, targetId) {
        const slug = text.toString().toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
        document.getElementById(targetId).value = slug;
    }

    function toggleAllCheckboxes(selector, checked) {
        document.querySelectorAll(selector).forEach(cb => {
            cb.checked = checked;
        });
    }

    function toggleCategoryCheckboxes(categoryClass) {
        const cbs = document.querySelectorAll('.' + categoryClass);
        if (cbs.length === 0) return;
        const allChecked = Array.from(cbs).every(cb => cb.checked);
        cbs.forEach(cb => cb.checked = !allChecked);
    }

    function editPermissionModal(permission) {
        const form = document.getElementById('formEditPermission');
        form.action = "{{ url('superadmin/permissions') }}/" + permission.id;
        document.getElementById('edit_perm_name').value = permission.name;
        document.getElementById('edit_perm_slug').value = permission.slug;
        document.getElementById('edit_perm_group').value = permission.group;
        document.getElementById('edit_perm_desc').value = permission.description || '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditPermission'));
        modal.show();
    }
</script>
@endpush
@endsection
