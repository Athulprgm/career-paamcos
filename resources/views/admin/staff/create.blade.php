@extends('superadmin.layouts.app')

@section('page-title', 'Add Staff Member')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Staff Member</h1>
        <div class="page-breadcrumb">People / <a href="{{ route('admin.staff.index') }}" style="color:inherit;">Staff</a> / New</div>
    </div>
    <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Staff List
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Staff Details & Account Access</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.staff.store') }}">
                    @csrf

                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g. Rahul Sharma" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Employee ID -->
                        <div class="col-md-6">
                            <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" id="employee_id" name="employee_id"
                                   class="form-control font-monospace @error('employee_id') is-invalid @enderror"
                                   value="{{ old('employee_id') }}" placeholder="e.g. STF0002" required>
                            @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="staff@example.com" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Mobile -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" id="phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="+91 9876543210" required>
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
                                    <option value="">— Select Branch —</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
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
                                    <option value="{{ $pos->id }}" {{ old('position_id') == $pos->id ? 'selected' : '' }}>
                                        {{ $pos->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('position_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Joining Date -->
                        <div class="col-md-6">
                            <label for="joining_date" class="form-label">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" id="joining_date" name="joining_date"
                                   class="form-control @error('joining_date') is-invalid @enderror"
                                   value="{{ old('joining_date', date('Y-m-d')) }}" required>
                            @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label for="status" class="form-label">Account Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', 'inactive') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>


                        <!-- Temporary Login Password with Auto-Generation -->
                        <div class="col-12">
                            <x-password-generator-input
                                name="password"
                                id="staff_password"
                                label="Temporary Login Password"
                                placeholder="Leave blank to auto-generate & email credentials"
                                hint="For temporary or initial use. If left blank, a secure password will be auto-generated and emailed to the staff member."
                                checkboxLabel="Send Welcome Email with credentials to staff member's email"
                            />
                        </div>
                    </div>

                    @php
                        $permsGrouped = isset($permissions) ? $permissions : ($permissionsList ?? []);
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
                                                            {{ in_array($pSlug, old('permissions', [])) ? 'checked' : '' }}>
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
                            <i class="bi bi-check-lg me-1"></i> Save Staff Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Instructions / Rules card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Guidelines</div>
            <div class="card-body">
                <ul class="list-unstyled mb-0" style="font-size:0.825rem;line-height:1.8;">
                    <li class="mb-2">
                        <i class="bi bi-shield-check text-success me-2"></i>
                        <strong>Unique ID:</strong> Employee ID must be unique across all staff.
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-building me-2"></i>
                        <strong>Branch Rule:</strong> Staff must be mapped to a valid, active branch.
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-diagram-2 me-2"></i>
                        <strong>Position:</strong> Must belong to an authorized company position.
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-key me-2"></i>
                        <strong>User Account:</strong> A user account is automatically created and login credentials are auto-generated and emailed to the staff member.
                    </li>
                    <li>
                        <i class="bi bi-toggle2-on me-2"></i>
                        <strong>Status:</strong> Inactive staff are preserved for audit logs and history, and cannot log in.
                    </li>
                </ul>
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
