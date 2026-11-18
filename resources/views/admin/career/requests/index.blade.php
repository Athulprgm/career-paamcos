@extends('superadmin.layouts.app')

@section('page-title', 'Promotion Requests')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Promotion Requests</h1>
        <div class="page-breadcrumb">Career / Progression Review & Approval Workflow</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.career.overview') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Career Overview
        </a>
        <a href="{{ route('admin.career.performance') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-graph-up me-1"></i> Performance Matrix
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Filters Card -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.career.requests.index') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <!-- Search -->
            <div style="min-width:180px;">
                <label class="form-label text-muted small mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="PR#, Agent name…" value="{{ request('search') }}">
            </div>

            <!-- Branch filter -->
            @if(is_null($restrictedBranchId))
            <div style="min-width:180px;">
                <label class="form-label text-muted small mb-1">Branch</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Status filter -->
            <div style="min-width:170px;">
                <label class="form-label text-muted small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="PENDING_REVIEW" {{ request('status') === 'PENDING_REVIEW' ? 'selected' : '' }}>Pending Review</option>
                    <option value="PROMOTED" {{ request('status') === 'PROMOTED' ? 'selected' : '' }}>Promoted</option>
                    <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                    <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="d-flex gap-1 align-self-end">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('admin.career.requests.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table Card -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Agent</th>
                        <th>Branch</th>
                        <th>Progression Path</th>
                        <th>Period</th>
                        <th>Request Date</th>
                        <th class="text-center">Status</th>
                        <th>Reviewed By</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr>
                        <td>
                            <a href="{{ route('admin.career.requests.show', $req) }}" class="fw-semibold text-decoration-none" style="color:var(--color-primary);">
                                {{ $req->request_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $req->agent->name ?? 'N/A' }}</div>
                            <div class="small text-muted">{{ $req->agent->member_code ?? $req->agent->agent_id }}</div>
                        </td>
                        <td>
                            <div class="small fw-semibold">{{ $req->branch->name ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $req->currentLevel->name ?? 'Level 1' }}</span>
                            <span class="text-muted mx-1">&rarr;</span>
                            <span class="badge bg-primary text-white">{{ $req->proposedLevel->name ?? 'Next Level' }}</span>
                        </td>
                        <td>{{ $req->performance_period ?? 'N/A' }}</td>
                        <td>{{ $req->request_date ? $req->request_date->format('d M Y') : '—' }}</td>
                        <td class="text-center">
                            @if($req->status === 'PENDING_REVIEW')
                                <span class="badge-pending">PENDING REVIEW</span>
                            @elseif($req->status === 'APPROVED')
                                <span class="badge-approved">APPROVED</span>
                            @elseif($req->status === 'PROMOTED')
                                <span class="badge-payable">PROMOTED</span>
                            @elseif($req->status === 'REJECTED')
                                <span class="badge-rejected">REJECTED</span>
                            @else
                                <span class="badge bg-light text-dark border">{{ $req->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($req->reviewer)
                                <div class="small">{{ $req->reviewer->name }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ $req->reviewed_at ? $req->reviewed_at->format('d M Y') : '' }}</div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.career.requests.show', $req) }}" class="btn btn-outline-dark btn-sm py-1 px-2" title="Review Request">
                                <i class="bi bi-eye"></i> Review
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                            No promotion requests found matching criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($requests->hasPages())
    <div class="card-footer py-2 px-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
            Showing {{ $requests->firstItem() }} to {{ $requests->lastItem() }} of {{ $requests->total() }} requests
        </div>
        <div>
            {{ $requests->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
