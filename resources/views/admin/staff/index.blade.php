@extends('superadmin.layouts.app')

@section('page-title', 'Staff Management')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Staff Management</h1>
        <div class="page-breadcrumb">People / Staff</div>
    </div>
    <a href="{{ route('admin.staff.create') }}" class="btn btn-dark btn-sm">
        <i class="bi bi-person-plus me-1"></i> Add Staff
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.staff.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
            <div class="flex-grow-1" style="min-width: 220px;">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search name, ID, email, mobile…" value="{{ request('search') }}">
            </div>

            @if(is_null($restrictedBranchId))
            <div style="min-width: 170px;">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div style="min-width: 170px;">
                <select name="position_id" class="form-select form-select-sm">
                    <option value="">All Positions</option>
                    @foreach($positions as $position)
                        <option value="{{ $position->id }}" {{ request('position_id') == $position->id ? 'selected' : '' }}>
                            {{ $position->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 130px;">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-dark btn-sm">
                <i class="bi bi-search me-1"></i> Filter
            </button>

            @if(request()->hasAny(['search','branch_id','position_id','status']))
                <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x me-1"></i> Clear
                </a>
            @endif
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 45px;">#</th>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Contact</th>
                        <th>Branch</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffList as $staff)
                        <tr>
                            <td class="text-muted small num-tabular">{{ $staffList->firstItem() + $loop->index }}</td>
                            <td>
                                <code class="font-monospace num-tabular">
                                    {{ $staff->employee_id }}
                                </code>
                            </td>
                            <td>
                                <a href="{{ route('admin.staff.show', $staff) }}" class="text-decoration-none text-dark fw-semibold">
                                    {{ $staff->name }}
                                </a>
                                @if($staff->joining_date)
                                    <div class="text-muted small num-tabular">Joined: {{ $staff->joining_date->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="text-dark small">{{ $staff->email }}</div>
                                <div class="text-muted small num-tabular">{{ $staff->phone }}</div>
                            </td>
                            <td>
                                @if($staff->branch)
                                    <div class="fw-medium text-dark">{{ $staff->branch->name }}</div>
                                    <div class="text-muted small">{{ $staff->branch->code }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($staff->position)
                                    <span class="badge bg-light text-dark border">{{ $staff->position->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($staff->status === 'active')
                                    <span class="badge-active">Active</span>
                                @else
                                    <span class="badge-inactive">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.staff.show', $staff) }}"
                                       class="btn btn-sm btn-outline-secondary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.staff.edit', $staff) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Edit Staff">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm {{ $staff->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }} btn-toggle-staff"
                                            data-action="{{ route('admin.staff.toggle-status', $staff) }}"
                                            data-name="{{ $staff->name }}"
                                            data-id="{{ $staff->employee_id }}"
                                            data-branch="{{ $staff->branch->name ?? '—' }}"
                                            data-position="{{ $staff->position->name ?? '—' }}"
                                            data-active="{{ $staff->status === 'active' ? '1' : '0' }}"
                                            title="{{ $staff->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $staff->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-staff"
                                            data-action="{{ route('admin.staff.destroy', $staff) }}"
                                            data-name="{{ $staff->name }}"
                                            data-id="{{ $staff->employee_id }}"
                                            data-branch="{{ $staff->branch->name ?? '—' }}"
                                            data-position="{{ $staff->position->name ?? '—' }}"
                                            title="Delete Staff Member">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-people d-block mb-2 text-muted" style="font-size:2rem;"></i>
                                No staff members found.
                                <a href="{{ route('admin.staff.create') }}"
                                   class="d-block mt-2 btn btn-dark btn-sm mx-auto" style="width:fit-content;">
                                    Add First Staff Member
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($staffList->hasPages())
        <div class="card-footer bg-transparent border-top py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted small">
                    Showing {{ $staffList->firstItem() }} to {{ $staffList->lastItem() }} of {{ $staffList->total() }} results
                </span>
                {{ $staffList->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

<!-- Custom Toggle Staff Status Confirmation Modal -->
<div class="modal fade" id="toggleStaffStatusModal" tabindex="-1" aria-labelledby="toggleStaffStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border" style="background: var(--card-bg);">
            <div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div id="toggleStaffModalIconContainer" class="border" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i id="toggleStaffModalIcon" class="bi bi-person-slash"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="toggleStaffStatusModalLabel" style="font-size: 1.05rem;">Change Status</h5>
                        <p class="text-muted small mb-0" id="toggleStaffModalSubtitle">Confirm staff member status change</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="p-3 my-2 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Staff Member:</span>
                        <span class="fw-semibold text-dark text-end" id="toggleStaffModalName">—</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Employee ID:</span>
                        <code class="bg-white border px-2 py-0.5 text-dark font-monospace num-tabular" id="toggleStaffModalId" style="font-size: 0.8rem;">—</code>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Branch:</span>
                        <span class="fw-semibold text-dark text-end" id="toggleStaffModalBranch">—</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">Position:</span>
                        <span class="badge bg-dark text-white fw-normal" id="toggleStaffModalPosition">—</span>
                    </div>
                </div>
                <p class="small text-muted mb-0" id="toggleStaffModalDescription">
                    Are you sure you want to change this staff member's active status?
                </p>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex gap-2 justify-content-end bg-light">
                <button type="button" class="btn btn-outline-dark btn-sm px-3" data-bs-dismiss="modal">
                    Cancel
                </button>
                <form id="toggleStaffStatusForm" method="POST" action="" class="d-inline m-0">
                    @csrf
                    <button type="submit" id="toggleStaffModalSubmitBtn" class="btn btn-sm px-3 d-flex align-items-center gap-1">
                        Confirm
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Custom Delete Staff Member Confirmation Modal -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-labelledby="deleteStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border" style="background: var(--card-bg);">
            <div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="border border-danger-subtle bg-danger-subtle text-danger" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="bi bi-trash"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="deleteStaffModalLabel" style="font-size: 1.05rem;">Delete Staff Member</h5>
                        <p class="text-muted small mb-0">Record removal confirmation</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="p-3 my-2 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Staff Member:</span>
                        <span class="fw-semibold text-dark text-end" id="deleteStaffModalName">—</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Employee ID:</span>
                        <code class="bg-white border px-2 py-0.5 text-dark font-monospace num-tabular" id="deleteStaffModalId" style="font-size: 0.8rem;">—</code>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Branch:</span>
                        <span class="fw-semibold text-dark text-end" id="deleteStaffModalBranch">—</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">Position:</span>
                        <span class="badge bg-dark text-white fw-normal" id="deleteStaffModalPosition">—</span>
                    </div>
                </div>
                <div class="alert alert-warning py-2 px-3 small mb-0 border">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    This action will soft-delete the staff profile, remove user account login credentials, and audit the removal. Subordinates or assigned agents must be reassigned prior to deletion.
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex gap-2 justify-content-end bg-light">
                <button type="button" class="btn btn-outline-dark btn-sm px-3" data-bs-dismiss="modal">
                    Cancel
                </button>
                <form id="deleteStaffForm" method="POST" action="" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm px-3 d-flex align-items-center gap-1">
                        <i class="bi bi-trash me-1"></i> Delete Staff
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleModalEl = document.getElementById('toggleStaffStatusModal');
    const toggleModal = toggleModalEl ? new bootstrap.Modal(toggleModalEl) : null;
    const toggleForm = document.getElementById('toggleStaffStatusForm');
    const toggleTitle = document.getElementById('toggleStaffStatusModalLabel');
    const toggleSubtitle = document.getElementById('toggleStaffModalSubtitle');
    const toggleName = document.getElementById('toggleStaffModalName');
    const toggleId = document.getElementById('toggleStaffModalId');
    const toggleBranch = document.getElementById('toggleStaffModalBranch');
    const togglePosition = document.getElementById('toggleStaffModalPosition');
    const toggleDesc = document.getElementById('toggleStaffModalDescription');
    const toggleIconContainer = document.getElementById('toggleStaffModalIconContainer');
    const toggleIcon = document.getElementById('toggleStaffModalIcon');
    const toggleSubmitBtn = document.getElementById('toggleStaffModalSubmitBtn');

    document.querySelectorAll('.btn-toggle-staff').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const name = this.getAttribute('data-name');
            const empId = this.getAttribute('data-id');
            const branch = this.getAttribute('data-branch');
            const position = this.getAttribute('data-position');
            const isActive = this.getAttribute('data-active') === '1';

            if (toggleForm) toggleForm.action = action;
            if (toggleName) toggleName.textContent = name;
            if (toggleId) toggleId.textContent = empId;
            if (toggleBranch) toggleBranch.textContent = branch;
            if (togglePosition) togglePosition.textContent = position;

            if (isActive) {
                if (toggleTitle) toggleTitle.textContent = 'Deactivate Staff Member';
                if (toggleSubtitle) toggleSubtitle.textContent = 'Temporarily disable staff access';
                if (toggleDesc) toggleDesc.textContent = 'Deactivating this staff member will prevent them from accessing the system and managing operations until reactivated.';
                if (toggleIconContainer) {
                    toggleIconContainer.className = 'border bg-danger-subtle text-danger border-danger-subtle';
                }
                if (toggleIcon) {
                    toggleIcon.className = 'bi bi-person-slash';
                }
                if (toggleSubmitBtn) {
                    toggleSubmitBtn.className = 'btn btn-warning text-dark btn-sm px-3';
                    toggleSubmitBtn.innerHTML = '<i class="bi bi-person-slash me-1"></i> Deactivate Staff';
                }
            } else {
                if (toggleTitle) toggleTitle.textContent = 'Activate Staff Member';
                if (toggleSubtitle) toggleSubtitle.textContent = 'Restore staff permissions and access';
                if (toggleDesc) toggleDesc.textContent = 'Activating this staff member will restore their operational capabilities and system login access.';
                if (toggleIconContainer) {
                    toggleIconContainer.className = 'border bg-success-subtle text-success border-success-subtle';
                }
                if (toggleIcon) {
                    toggleIcon.className = 'bi bi-person-check';
                }
                if (toggleSubmitBtn) {
                    toggleSubmitBtn.className = 'btn btn-success btn-sm px-3';
                    toggleSubmitBtn.innerHTML = '<i class="bi bi-person-check me-1"></i> Activate Staff';
                }
            }

            if (toggleModal) toggleModal.show();
        });
    });

    // Delete Staff Member Modal
    const deleteModalEl = document.getElementById('deleteStaffModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const deleteForm = document.getElementById('deleteStaffForm');
    const deleteName = document.getElementById('deleteStaffModalName');
    const deleteId = document.getElementById('deleteStaffModalId');
    const deleteBranch = document.getElementById('deleteStaffModalBranch');
    const deletePosition = document.getElementById('deleteStaffModalPosition');

    document.querySelectorAll('.btn-delete-staff').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const name = this.getAttribute('data-name');
            const empId = this.getAttribute('data-id');
            const branch = this.getAttribute('data-branch');
            const position = this.getAttribute('data-position');

            if (deleteForm) deleteForm.action = action;
            if (deleteName) deleteName.textContent = name;
            if (deleteId) deleteId.textContent = empId;
            if (deleteBranch) deleteBranch.textContent = branch;
            if (deletePosition) deletePosition.textContent = position;

            if (deleteModal) deleteModal.show();
        });
    });
});
</script>
@endpush
@endsection
