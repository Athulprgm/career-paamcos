@extends('superadmin.layouts.app')

@section('page-title', 'Branches')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Branches</h1>
        <div class="page-breadcrumb">Branch & Details / Branches</div>
    </div>
    <a href="{{ route('superadmin.branches.create') }}" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Branch
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <div class="flex-grow-1" style="min-width: 220px;">
                <input
                    type="text"
                    name="search"
                    class="form-control form-control-sm"
                    placeholder="Search branch name, code, email, phone…"
                    value="{{ request('search') }}"
                >
            </div>
            <div style="min-width: 140px;">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-dark btn-sm">
                <i class="bi bi-search me-1"></i> Filter
            </button>
            @if(request()->hasAny(['search','status']))
                <a href="{{ route('superadmin.branches.index') }}" class="btn btn-outline-secondary btn-sm">
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
                        <th>Branch Name</th>
                        <th>Code</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr>
                            <td class="text-muted small">{{ $branches->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $branch->name }}</div>
                                @if($branch->address)
                                    <div class="text-muted small">{{ Str::limit($branch->address, 50) }}</div>
                                @endif
                            </td>
                            <td><code class="font-monospace">{{ $branch->code }}</code></td>
                            <td class="small">{{ $branch->phone ?? '—' }}</td>
                            <td class="small">{{ $branch->email ?? '—' }}</td>
                            <td>
                                @if($branch->is_active)
                                    <span class="badge-active">Active</span>
                                @else
                                    <span class="badge-inactive">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('superadmin.branches.edit', $branch) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Edit Branch">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm {{ $branch->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} btn-toggle-branch"
                                            title="{{ $branch->is_active ? 'Deactivate Branch' : 'Activate Branch' }}"
                                            data-action="{{ route('superadmin.branches.toggle-status', $branch) }}"
                                            data-name="{{ $branch->name }}"
                                            data-code="{{ $branch->code }}"
                                            data-active="{{ $branch->is_active ? '1' : '0' }}">
                                        <i class="bi bi-{{ $branch->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-branch"
                                            title="Delete Branch"
                                            data-action="{{ route('superadmin.branches.destroy', $branch) }}"
                                            data-name="{{ $branch->name }}"
                                            data-code="{{ $branch->code }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-building d-block mb-2 text-muted" style="font-size:2rem;"></i>
                            No branches found.
                            <a href="{{ route('superadmin.branches.create') }}" class="d-block mt-2 btn btn-dark btn-sm mx-auto" style="width:fit-content;">
                                Add First Branch
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($branches->hasPages())
        <div class="card-footer bg-transparent border-top py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $branches->firstItem() }}–{{ $branches->lastItem() }} of {{ $branches->total() }} branches
                </div>
                {{ $branches->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

<!-- Custom Delete Branch Confirmation Modal -->
<div class="modal fade" id="deleteBranchModal" tabindex="-1" aria-labelledby="deleteBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; background: var(--card-bg);">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger-subtle text-danger" style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="bi bi-trash3-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="deleteBranchModalLabel" style="font-size: 1.1rem; letter-spacing: -0.01em;">Delete Branch</h5>
                        <p class="text-muted small mb-0">Confirm permanent branch removal</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="p-3 my-2 rounded-3 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Branch Name:</span>
                        <span class="fw-semibold text-dark text-end" id="deleteModalBranchName">—</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">Branch Code:</span>
                        <code class="bg-white border px-2 py-0.5 rounded text-dark font-monospace" id="deleteModalBranchCode" style="font-size: 0.8rem;">—</code>
                    </div>
                </div>
                <div class="alert alert-danger py-2 px-3 small mb-0 d-flex align-items-center gap-2" style="border-radius: 8px;">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <div>This action cannot be undone. Associated branch records will be unlinked or removed.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-dark btn-sm px-3" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 500;">
                    Cancel
                </button>
                <form id="deleteBranchForm" method="POST" action="" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm px-3 d-flex align-items-center gap-1" style="border-radius: 8px; font-weight: 500; background: #DC2626; border-color: #DC2626;">
                        <i class="bi bi-trash me-1"></i> Delete Branch
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Custom Toggle Branch Status Confirmation Modal -->
<div class="modal fade" id="toggleBranchStatusModal" tabindex="-1" aria-labelledby="toggleBranchStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; background: var(--card-bg);">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-3">
                    <div id="toggleModalIconContainer" style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i id="toggleModalIcon" class="bi bi-pause-circle-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="toggleBranchStatusModalLabel" style="font-size: 1.1rem; letter-spacing: -0.01em;">Change Status</h5>
                        <p class="text-muted small mb-0" id="toggleModalSubtitle">Confirm branch status change</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="p-3 my-2 rounded-3 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Branch Name:</span>
                        <span class="fw-semibold text-dark text-end" id="toggleModalBranchName">—</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">Branch Code:</span>
                        <code class="bg-white border px-2 py-0.5 rounded text-dark font-monospace" id="toggleModalBranchCode" style="font-size: 0.8rem;">—</code>
                    </div>
                </div>
                <p class="small text-muted mb-0" id="toggleModalDescription">
                    Are you sure you want to change this branch's operational status?
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-dark btn-sm px-3" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 500;">
                    Cancel
                </button>
                <form id="toggleBranchStatusForm" method="POST" action="" class="d-inline m-0">
                    @csrf
                    <button type="submit" id="toggleModalSubmitBtn" class="btn btn-sm px-3 d-flex align-items-center gap-1" style="border-radius: 8px; font-weight: 500;">
                        Confirm
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete Branch Modal Handler
    const deleteModalEl = document.getElementById('deleteBranchModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const deleteForm = document.getElementById('deleteBranchForm');
    const deleteName = document.getElementById('deleteModalBranchName');
    const deleteCode = document.getElementById('deleteModalBranchCode');

    document.querySelectorAll('.btn-delete-branch').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const name = this.getAttribute('data-name');
            const code = this.getAttribute('data-code');

            if (deleteForm) deleteForm.action = action;
            if (deleteName) deleteName.textContent = name;
            if (deleteCode) deleteCode.textContent = code;

            if (deleteModal) deleteModal.show();
        });
    });

    // Toggle Status Modal Handler
    const toggleModalEl = document.getElementById('toggleBranchStatusModal');
    const toggleModal = toggleModalEl ? new bootstrap.Modal(toggleModalEl) : null;
    const toggleForm = document.getElementById('toggleBranchStatusForm');
    const toggleTitle = document.getElementById('toggleBranchStatusModalLabel');
    const toggleSubtitle = document.getElementById('toggleModalSubtitle');
    const toggleName = document.getElementById('toggleModalBranchName');
    const toggleCode = document.getElementById('toggleModalBranchCode');
    const toggleDesc = document.getElementById('toggleModalDescription');
    const toggleIconContainer = document.getElementById('toggleModalIconContainer');
    const toggleIcon = document.getElementById('toggleModalIcon');
    const toggleSubmitBtn = document.getElementById('toggleModalSubmitBtn');

    document.querySelectorAll('.btn-toggle-branch').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const name = this.getAttribute('data-name');
            const code = this.getAttribute('data-code');
            const isActive = this.getAttribute('data-active') === '1';

            if (toggleForm) toggleForm.action = action;
            if (toggleName) toggleName.textContent = name;
            if (toggleCode) toggleCode.textContent = code;

            if (isActive) {
                // Deactivate configuration
                if (toggleTitle) toggleTitle.textContent = 'Deactivate Branch';
                if (toggleSubtitle) toggleSubtitle.textContent = 'Temporarily disable branch operations';
                if (toggleDesc) toggleDesc.textContent = 'Deactivating this branch will prevent staff and agents under this branch from accessing the system until reactivated.';
                if (toggleIconContainer) {
                    toggleIconContainer.className = 'bg-warning-subtle text-warning border border-warning-subtle';
                }
                if (toggleIcon) {
                    toggleIcon.className = 'bi bi-pause-circle-fill';
                }
                if (toggleSubmitBtn) {
                    toggleSubmitBtn.className = 'btn btn-warning text-dark btn-sm px-3';
                    toggleSubmitBtn.innerHTML = '<i class="bi bi-pause-circle me-1"></i> Deactivate Branch';
                }
            } else {
                // Activate configuration
                if (toggleTitle) toggleTitle.textContent = 'Activate Branch';
                if (toggleSubtitle) toggleSubtitle.textContent = 'Enable branch operations and logins';
                if (toggleDesc) toggleDesc.textContent = 'Activating this branch will restore all operational capabilities for staff, agents, and branch admins.';
                if (toggleIconContainer) {
                    toggleIconContainer.className = 'bg-success-subtle text-success border border-success-subtle';
                }
                if (toggleIcon) {
                    toggleIcon.className = 'bi bi-play-circle-fill';
                }
                if (toggleSubmitBtn) {
                    toggleSubmitBtn.className = 'btn btn-success btn-sm px-3';
                    toggleSubmitBtn.innerHTML = '<i class="bi bi-play-circle me-1"></i> Activate Branch';
                }
            }

            if (toggleModal) toggleModal.show();
        });
    });
});
</script>
@endpush
@endsection
