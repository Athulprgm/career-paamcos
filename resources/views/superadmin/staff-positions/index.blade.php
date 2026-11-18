@extends('superadmin.layouts.app')

@section('page-title', 'Staff Positions')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Staff Positions</h1>
        <div class="page-breadcrumb">Branch & Details / Staff Positions</div>
    </div>
    <a href="{{ route('superadmin.staff-positions.create') }}" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Position
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <div class="flex-grow-1" style="min-width: 220px;">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search position name…" value="{{ request('search') }}">
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
                <a href="{{ route('superadmin.staff-positions.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x me-1"></i> Clear
                </a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 70px;">Order</th>
                        <th>Position Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($positions as $position)
                        <tr>
                            <td>
                                <code class="font-monospace">#{{ $position->sort_order }}</code>
                            </td>
                            <td class="fw-semibold text-dark">{{ $position->name }}</td>
                            <td class="text-muted small">{{ Str::limit($position->description, 60) ?? '—' }}</td>
                            <td>
                                @if($position->status === 'active')
                                    <span class="badge-active">Active</span>
                                @else
                                    <span class="badge-inactive">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('superadmin.staff-positions.edit', $position) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Edit Position">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('superadmin.staff-positions.toggle-status', $position) }}" method="POST"
                                          data-confirm="Are you sure you want to {{ $position->status === 'active' ? 'deactivate' : 'activate' }} this position?"
                                          data-confirm-title="{{ $position->status === 'active' ? 'Deactivate Staff Position' : 'Activate Staff Position' }}"
                                          data-confirm-type="{{ $position->status === 'active' ? 'warning' : 'success' }}"
                                          data-confirm-btn="{{ $position->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm {{ $position->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                title="{{ $position->status === 'active' ? 'Deactivate Position' : 'Activate Position' }}">
                                            <i class="bi bi-{{ $position->status === 'active' ? 'pause-circle' : 'play-circle' }}"></i>
                                        </button>
                                    </form>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-position"
                                            title="Delete Position"
                                            data-action="{{ route('superadmin.staff-positions.destroy', $position) }}"
                                            data-name="{{ $position->name }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-person-badge d-block mb-2 text-muted" style="font-size:2rem;"></i>
                                No staff positions found.
                                <a href="{{ route('superadmin.staff-positions.create') }}"
                                   class="d-block mt-2 btn btn-dark btn-sm mx-auto" style="width:fit-content;">
                                    Add First Position
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($positions->hasPages())
        <div class="card-footer bg-transparent border-top py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $positions->firstItem() }}–{{ $positions->lastItem() }} of {{ $positions->total() }} positions
                </div>
                {{ $positions->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

<!-- Custom Delete Staff Position Confirmation Modal -->
<div class="modal fade" id="deletePositionModal" tabindex="-1" aria-labelledby="deletePositionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; background: var(--card-bg);">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger-subtle text-danger" style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="bi bi-trash3-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="deletePositionModalLabel" style="font-size: 1.1rem; letter-spacing: -0.01em;">Delete Position</h5>
                        <p class="text-muted small mb-0">Confirm permanent position removal</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="p-3 my-2 rounded-3 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">Position Name:</span>
                        <span class="fw-semibold text-dark text-end" id="deleteModalPositionName">—</span>
                    </div>
                </div>
                <div class="alert alert-danger py-2 px-3 small mb-0 d-flex align-items-center gap-2" style="border-radius: 8px;">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <div>This action cannot be undone. Ensure no staff members are assigned to this position.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-dark btn-sm px-3" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 500;">
                    Cancel
                </button>
                <form id="deletePositionForm" method="POST" action="" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm px-3 d-flex align-items-center gap-1" style="border-radius: 8px; font-weight: 500; background: #DC2626; border-color: #DC2626;">
                        <i class="bi bi-trash me-1"></i> Delete Position
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteModalEl = document.getElementById('deletePositionModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const deleteForm = document.getElementById('deletePositionForm');
    const deleteName = document.getElementById('deleteModalPositionName');

    document.querySelectorAll('.btn-delete-position').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const name = this.getAttribute('data-name');

            if (deleteForm) deleteForm.action = action;
            if (deleteName) deleteName.textContent = name;

            if (deleteModal) deleteModal.show();
        });
    });
});
</script>
@endpush
@endsection
