@extends('superadmin.layouts.app')

@section('page-title', 'Agent Ranks')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Agent Ranks</h1>
        <div class="page-breadcrumb">Branch & Details / Agent Ranks</div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('admin.targets.configurations') }}" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-crosshair"></i>
            <span>FY Rank Targets</span>
        </a>
        <a href="{{ route('superadmin.promotion-rules.index') }}" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-trophy"></i>
            <span>Promotion Rules</span>
        </a>
        <a href="{{ route('superadmin.agent-ranks.create') }}" class="btn btn-dark btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-plus-lg"></i>
            <span>Add Rank</span>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <div class="flex-grow-1" style="min-width: 220px;">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search rank name, code…" value="{{ request('search') }}">
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
                <a href="{{ route('superadmin.agent-ranks.index') }}" class="btn btn-outline-secondary btn-sm">
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
                        <th style="width: 60px;">Level</th>
                        <th style="width: 60px;">Order</th>
                        <th>Rank Name</th>
                        <th>Code</th>
                        <th class="text-end">Base Deposit Target</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranks as $rank)
                        <tr>
                            <td>
                                <span class="badge bg-dark text-white fw-semibold">
                                    L{{ $rank->level ?? $rank->sort_order }}
                                </span>
                            </td>
                            <td>
                                <code class="font-monospace">#{{ $rank->sort_order }}</code>
                            </td>
                            <td>
                                <a href="{{ route('superadmin.agent-ranks.edit', $rank) }}" class="text-decoration-none text-dark fw-semibold">
                                    {{ $rank->name }}
                                </a>
                            </td>
                            <td>
                                @if($rank->code)
                                    <code class="font-monospace">{{ $rank->code }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @php
                                    $targetAmt = (float) ($rank->minimum_deposit_amount ?? $rank->min_deposit_amount ?? 0);
                                @endphp
                                @if($targetAmt > 0)
                                    <span class="fw-bold text-dark tabular-nums">₹{{ number_format($targetAmt, 2) }}</span>
                                    <a href="{{ route('admin.targets.configurations') }}" class="d-block text-success text-decoration-none small" title="View in FY Rank Targets">
                                        <i class="bi bi-crosshair me-0.5"></i>FY Target
                                    </a>
                                @else
                                    <span class="text-muted tabular-nums">₹0.00</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ Str::limit($rank->description, 60) ?? '—' }}</td>
                            <td>
                                @if($rank->status === 'active')
                                    <span class="badge-active">Active</span>
                                @else
                                    <span class="badge-inactive">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.targets.configurations') }}"
                                       class="btn btn-sm btn-outline-success" title="FY Targets for {{ $rank->name }}">
                                        <i class="bi bi-crosshair"></i>
                                    </a>
                                    <a href="{{ route('superadmin.promotion-rules.index', ['from_rank_id' => $rank->id]) }}"
                                       class="btn btn-sm btn-outline-primary" title="View Promotion Rules for {{ $rank->name }}">
                                        <i class="bi bi-trophy"></i>
                                    </a>
                                    <a href="{{ route('superadmin.agent-ranks.edit', $rank) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Edit Rank">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('superadmin.agent-ranks.toggle-status', $rank) }}" method="POST"
                                          data-confirm="Are you sure you want to {{ $rank->status === 'active' ? 'deactivate' : 'activate' }} this rank?"
                                          data-confirm-title="{{ $rank->status === 'active' ? 'Deactivate Agent Rank' : 'Activate Agent Rank' }}"
                                          data-confirm-type="{{ $rank->status === 'active' ? 'warning' : 'success' }}"
                                          data-confirm-btn="{{ $rank->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm {{ $rank->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                title="{{ $rank->status === 'active' ? 'Deactivate Rank' : 'Activate Rank' }}">
                                            <i class="bi bi-{{ $rank->status === 'active' ? 'pause-circle' : 'play-circle' }}"></i>
                                        </button>
                                    </form>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-rank"
                                            title="Delete Rank"
                                            data-action="{{ route('superadmin.agent-ranks.destroy', $rank) }}"
                                            data-name="{{ $rank->name }}"
                                            data-code="{{ $rank->code ?? '' }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-award d-block mb-2 text-muted" style="font-size:2rem;"></i>
                                No agent ranks found.
                                <a href="{{ route('superadmin.agent-ranks.create') }}"
                                   class="d-block mt-2 btn btn-dark btn-sm mx-auto" style="width:fit-content;">
                                    Add First Rank
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($ranks->hasPages())
        <div class="card-footer bg-transparent border-top py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $ranks->firstItem() }}–{{ $ranks->lastItem() }} of {{ $ranks->total() }} ranks
                </div>
                {{ $ranks->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

<!-- Custom Delete Agent Rank Confirmation Modal -->
<div class="modal fade" id="deleteRankModal" tabindex="-1" aria-labelledby="deleteRankModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; background: var(--card-bg);">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger-subtle text-danger" style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="bi bi-trash3-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="deleteRankModalLabel" style="font-size: 1.1rem; letter-spacing: -0.01em;">Delete Agent Rank</h5>
                        <p class="text-muted small mb-0">Confirm permanent rank removal</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="p-3 my-2 rounded-3 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Rank Name:</span>
                        <span class="fw-semibold text-dark text-end" id="deleteModalRankName">—</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">Rank Code:</span>
                        <code class="bg-white border px-2 py-0.5 rounded text-dark font-monospace" id="deleteModalRankCode" style="font-size: 0.8rem;">—</code>
                    </div>
                </div>
                <div class="alert alert-danger py-2 px-3 small mb-0 d-flex align-items-center gap-2" style="border-radius: 8px;">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <div>This action cannot be undone. Ensure no agents or commission rules are linked to this rank.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-dark btn-sm px-3" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 500;">
                    Cancel
                </button>
                <form id="deleteRankForm" method="POST" action="" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm px-3 d-flex align-items-center gap-1" style="border-radius: 8px; font-weight: 500; background: #DC2626; border-color: #DC2626;">
                        <i class="bi bi-trash me-1"></i> Delete Rank
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteModalEl = document.getElementById('deleteRankModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const deleteForm = document.getElementById('deleteRankForm');
    const deleteName = document.getElementById('deleteModalRankName');
    const deleteCode = document.getElementById('deleteModalRankCode');

    document.querySelectorAll('.btn-delete-rank').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const name = this.getAttribute('data-name');
            const code = this.getAttribute('data-code');

            if (deleteForm) deleteForm.action = action;
            if (deleteName) deleteName.textContent = name;
            if (deleteCode) deleteCode.textContent = code || '—';

            if (deleteModal) deleteModal.show();
        });
    });
});
</script>
@endpush
@endsection
