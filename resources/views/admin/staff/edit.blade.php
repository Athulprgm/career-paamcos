@extends('superadmin.layouts.app')

@section('page-title', 'Edit Staff Member')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Staff Member</h1>
        <div class="page-breadcrumb">People / <a href="{{ route('admin.staff.index') }}" style="color:inherit;">Staff</a> / {{ $staff->name }} / Edit</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.staff.show', $staff) }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-eye me-1"></i> View Profile
        </a>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Update Staff Information & Access Control</span>
                @if($staff->status === 'active')
                    <span class="badge-active">Active</span>
                @else
                    <span class="badge-inactive">Inactive</span>
                @endif
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.staff.update', $staff) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $staff->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Employee ID -->
                        <div class="col-md-6">
                            <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" id="employee_id" name="employee_id"
                                   class="form-control font-monospace @error('employee_id') is-invalid @enderror"
                                   value="{{ old('employee_id', $staff->employee_id) }}" required>
                            @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $staff->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Mobile -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" id="phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $staff->phone) }}" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Branch -->
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Assigned Branch <span class="text-danger">*</span></label>
                            @if(!is_null($restrictedBranchId))
                                @php $branch = $branches->first(); @endphp
                                <input type="hidden" name="branch_id" value="{{ $restrictedBranchId }}">
                                <input type="text" class="form-control bg-light" value="{{ $branch ? $branch->name . ' (' . $branch->code . ')' : 'Branch #' . $restrictedBranchId }}" readonly>
                                <div class="form-text text-muted">Fixed to your assigned branch.</div>
                            @else
                                <select id="branch_id" name="branch_id"
                                        class="form-select @error('branch_id') is-invalid @enderror" required>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $staff->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }} ({{ $branch->code }})
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Staff Position -->
                        <div class="col-md-6">
                            <label for="position_id" class="form-label">Staff Position <span class="text-danger">*</span></label>
                            <select id="position_id" name="position_id"
                                    class="form-select @error('position_id') is-invalid @enderror" required>
                                <option value="">— Select Position —</option>
                                @foreach($positions as $pos)
                                    <option value="{{ $pos->id }}" {{ old('position_id', $staff->position_id) == $pos->id ? 'selected' : '' }}>
                                        {{ $pos->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('position_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if(isset($managers) && $managers->count() > 0)
                        <!-- Reporting Manager -->
                        <div class="col-md-6">
                            <label for="reporting_manager_id" class="form-label">Reporting Manager</label>
                            <select id="reporting_manager_id" name="reporting_manager_id"
                                    class="form-select @error('reporting_manager_id') is-invalid @enderror">
                                <option value="">— None (Top Level) —</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}" {{ old('reporting_manager_id', $staff->reporting_manager_id) == $manager->id ? 'selected' : '' }}>
                                        {{ $manager->name }} ({{ $manager->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">Must belong to the selected branch. Cannot be self.</div>
                            @error('reporting_manager_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @endif

                        <!-- Joining Date -->
                        <div class="col-md-6">
                            <label for="joining_date" class="form-label">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" id="joining_date" name="joining_date"
                                   class="form-control @error('joining_date') is-invalid @enderror"
                                   value="{{ old('joining_date', optional($staff->joining_date)->format('Y-m-d')) }}" required>
                            @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label for="status" class="form-label">Account Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $staff->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $staff->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>


                        <!-- Optional Password Change with Auto-Generation -->
                        <div class="col-12">
                            <x-password-generator-input
                                name="password"
                                id="staff_edit_password"
                                label="Reset Staff Password"
                                placeholder="Leave blank to keep existing password"
                                hint="Leave empty to retain current password, or auto-generate a new one."
                                checkboxLabel="Send updated credentials email to staff member"
                            />
                        </div>
                    </div>

                    @php
                        $permsGrouped = isset($permissions) ? $permissions : ($permissionsList ?? []);
                        $currentPermissions = (array) ($staff->permissions ?? $staff->user?->permissions ?? $assignedPermissions ?? []);
                        $iconMap = [
                            'audit' => 'bi-journal-text',
                            'commissions' => 'bi-percent',
                            'customers' => 'bi-people',
                            'deposits' => 'bi-wallet2',
                            'employees' => 'bi-person-badge',
                            'ranks' => 'bi-trophy',
                            'reports' => 'bi-graph-up',
                            'schemes' => 'bi-piggy-bank',
                            'settings' => 'bi-gear-wide-connected',
                        ];
                    @endphp

                    @if(!empty($permsGrouped) && count($permsGrouped) > 0)
                    <!-- Granular Operational Permissions -->
                    <div class="card mt-4 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-color: var(--border-color) !important;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-2" style="width: 32px; height: 32px; background: var(--color-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                                    <i class="bi bi-shield-lock"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.925rem; letter-spacing: -0.01em;">Assigned Operational Permissions</h6>
                                    <span class="text-muted" style="font-size: 0.75rem;">Granular operational authority (Least-Privilege model)</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-dark py-1 px-2.5" id="btnSelectAllPerms" style="font-size: 0.75rem; font-weight: 500;">
                                    <i class="bi bi-check2-all me-1"></i>Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5" id="btnDeselectAllPerms" style="font-size: 0.75rem; font-weight: 500;">
                                    <i class="bi bi-dash-circle me-1"></i>Clear All
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                @foreach($permsGrouped as $category => $items)
                                @php
                                    $cleanCat = !empty(trim($category)) ? strtolower(trim($category)) : 'settings';
                                    $catTitle = ucwords(str_replace(['_', '-'], ' ', $cleanCat));
                                    $catIcon = $iconMap[$cleanCat] ?? 'bi-shield-check';
                                @endphp
                                <div class="col-md-6">
                                    <div class="card h-100 border" style="background: var(--card-bg); border-color: var(--border-color) !important;">
                                        <div class="card-header py-2 px-3 bg-white border-bottom d-flex align-items-center justify-content-between" style="border-color: var(--border-color) !important;">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="d-inline-flex align-items-center justify-content-center text-dark rounded-1" style="width: 24px; height: 24px; background: var(--surface-subtle); font-size: 0.8rem;">
                                                    <i class="bi {{ $catIcon }}"></i>
                                                </span>
                                                <span class="fw-semibold text-dark text-capitalize" style="font-size: 0.825rem; letter-spacing: -0.01em;">{{ $catTitle }}</span>
                                            </div>
                                            <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.68rem; font-weight: 500;">{{ count($items) }}</span>
                                        </div>
                                        <div class="card-body p-2 px-3">
                                            <div class="d-flex flex-column gap-1">
                                                @foreach($items as $key => $label)
                                                    @php
                                                        $pSlug = is_object($label) ? $label->slug : $key;
                                                        $pTitle = is_object($label) ? $label->name : $label;
                                                    @endphp
                                                    <div class="form-check py-1 px-2 permission-check-wrap" style="transition: background 0.15s ease; margin-bottom: 0;">
                                                        <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="{{ $pSlug }}" id="perm_{{ $pSlug }}"
                                                            {{ in_array($pSlug, old('permissions', $currentPermissions)) ? 'checked' : '' }}>
                                                        <label class="form-check-label small text-dark fw-normal user-select-none w-100" for="perm_{{ $pSlug }}" style="cursor: pointer; font-size: 0.8125rem;">
                                                            {{ $pTitle }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-dark">Cancel</a>
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i> Update Staff Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Actions Card -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Status Management</div>
            <div class="card-body">
                <p class="text-muted" style="font-size:0.825rem;">
                    Currently: <strong>{{ ucfirst($staff->status) }}</strong>.
                    {{ $staff->status === 'active' ? 'Deactivating disables active authority and blocks login while preserving reports and history.' : 'Activating restores operational privileges.' }}
                </p>
                <button type="button"
                        class="btn w-100 {{ $staff->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}"
                        data-bs-toggle="modal" data-bs-target="#toggleStaffStatusModal">
                    <i class="bi {{ $staff->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }} me-1"></i>
                    {{ $staff->status === 'active' ? 'Deactivate Staff' : 'Activate Staff' }}
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">System Record</div>
            <div class="card-body" style="font-size:0.8rem;color:#666;">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span>Internal ID</span>
                    <span class="font-monospace">#{{ $staff->id }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span>Direct Subordinates</span>
                    <span class="fw-semibold">{{ $staff->directReports()->count() }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span>Assigned Agents</span>
                    <span class="fw-semibold">{{ $staff->reportingAgents()->count() }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Created</span>
                    <span>{{ $staff->created_at ? $staff->created_at->format('d M Y, h:i A') : '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Toggle Staff Status Confirmation Modal -->
<div class="modal fade" id="toggleStaffStatusModal" tabindex="-1" aria-labelledby="toggleStaffStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; background: var(--card-bg);">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-3">
                    <div class="{{ $staff->status === 'active' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}" style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="bi {{ $staff->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="toggleStaffStatusModalLabel" style="font-size: 1.1rem; letter-spacing: -0.01em;">
                            {{ $staff->status === 'active' ? 'Deactivate Staff Member' : 'Activate Staff Member' }}
                        </h5>
                        <p class="text-muted small mb-0">Confirm staff member status change</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="p-3 my-2 rounded-3 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Staff Member:</span>
                        <span class="fw-semibold text-dark text-end">{{ $staff->name }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Employee ID:</span>
                        <code class="bg-white border px-2 py-0.5 rounded text-dark font-monospace" style="font-size: 0.8rem;">{{ $staff->employee_id }}</code>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Branch:</span>
                        <span class="fw-semibold text-dark text-end">{{ $staff->branch->name ?? '—' }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">Position:</span>
                        <span class="badge bg-dark text-white fw-normal">{{ $staff->position->name ?? '—' }}</span>
                    </div>
                </div>
                <p class="small text-muted mb-0">
                    {{ $staff->status === 'active'
                        ? 'Deactivating this staff member will prevent them from accessing the system and managing operations until reactivated.'
                        : 'Activating this staff member will restore their operational capabilities and system login access.' }}
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-dark btn-sm px-3" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 500;">
                    Cancel
                </button>
                <form method="POST" action="{{ route('admin.staff.toggle-status', $staff) }}" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn {{ $staff->status === 'active' ? 'btn-danger' : 'btn-success' }} btn-sm px-3 d-flex align-items-center gap-1" style="border-radius: 8px; font-weight: 500;">
                        <i class="bi {{ $staff->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }} me-1"></i>
                        {{ $staff->status === 'active' ? 'Deactivate Staff' : 'Activate Staff' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('btnSelectAllPerms');
    const deselectAllBtn = document.getElementById('btnDeselectAllPerms');
    const checkboxes = document.querySelectorAll('.permission-checkbox');

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = true);
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
        });
    }

});
</script>
<style>
.permission-check-wrap:hover {
    background: var(--surface-subtle);
}
</style>
@endpush
@endsection
