@extends('superadmin.layouts.app')

@section('page-title', 'Career Overview')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Career & Performance Overview</h1>
        <div class="page-breadcrumb">Career / Progression Analytics & Workflow</div>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->check() && auth()->user()->isSuperAdmin())
        <a href="{{ route('superadmin.promotion-rules.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-trophy me-1"></i> Promotion Rules
        </a>
        @endif
        <a href="{{ route('admin.career.performance') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-graph-up me-1"></i> Performance Matrix
        </a>
        <a href="{{ route('admin.career.requests.index') }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-arrow-up-circle me-1"></i> Pending ({{ $pendingPromotionsCount }})
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
<div class="card mb-4">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.career.overview') }}" class="d-flex gap-2 flex-wrap align-items-end">
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

            <div class="d-flex gap-1 align-self-end">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('admin.career.overview') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Key Performance & Career Metrics -->
<div class="row g-3 mb-4">
    <!-- Total & Active Agents -->
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-medium">Agent Force</span>
                    <span class="badge bg-light text-dark border"><i class="bi bi-people me-1"></i>Staff</span>
                </div>
                <div class="fs-3 fw-bold mb-1" style="color:var(--color-primary);">{{ $activeAgents }} <span class="fs-6 text-muted fw-normal">/ {{ $totalAgents }} Total</span></div>
                <div class="small text-success">
                    <i class="bi bi-check-circle me-1"></i>Active Field Representatives
                </div>
            </div>
        </div>
    </div>

    <!-- Career Levels -->
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-medium">Career Hierarchy</span>
                    <span class="badge bg-light text-dark border"><i class="bi bi-layers me-1"></i>Ranks</span>
                </div>
                <div class="fs-3 fw-bold mb-1" style="color:var(--color-accent);">{{ $careerLevelsCount }} Levels</div>
                <div class="small text-muted">
                    Configured Progression Ranks
                </div>
            </div>
        </div>
    </div>

    <!-- Eligible for Promotion -->
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-medium">Eligible Candidates</span>
                    <span class="badge-payable"><i class="bi bi-award me-1"></i>Qualified</span>
                </div>
                <div class="fs-3 fw-bold text-success mb-1">{{ $eligibleAgentsCount }}</div>
                <div class="small text-muted">
                    Target Criteria Achieved
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Promotion Reviews -->
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-medium">Pending Promotion Reviews</span>
                    <span class="badge-pending"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                </div>
                <div class="fs-3 fw-bold mb-1" style="color:#D97706;">{{ $pendingPromotionsCount }}</div>
                <div class="small text-muted">
                    Awaiting Review & Approval
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Career Progression Flowchart Banner -->
<div class="card mb-4" style="background:var(--surface-subtle); border:1px solid var(--border-color);">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="card-title mb-0 fw-bold" style="color:var(--color-primary);">
                <i class="bi bi-diagram-3 me-2"></i>Configured Career Progression Ladder
            </h6>
            @if(auth()->check() && auth()->user()->isSuperAdmin())
            <a href="{{ route('superadmin.agent-ranks.index') }}" class="small text-decoration-none" style="color:var(--color-primary);">
                View Agent Ranks &rarr;
            </a>
            @endif
        </div>
        <div class="d-flex align-items-center gap-2 overflow-auto py-2">
            @foreach($careerLevels as $index => $level)
                <div class="px-3 py-2 bg-white border text-center" style="min-width:140px;">
                    <div class="badge bg-secondary mb-1">Level {{ $level->sort_order }}</div>
                    <div class="fw-bold small text-truncate" title="{{ $level->name }}">{{ $level->name }}</div>
                    <div class="small text-muted"><code>{{ $level->code }}</code></div>
                </div>
                @if(!$loop->last)
                    <div class="text-muted fw-bold">&rarr;</div>
                @endif
            @endforeach
        </div>
    </div>
</div>

<!-- Recent Promotion Requests Table -->
<div class="card">
    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-clock-history me-2" style="color:var(--color-primary);"></i>Recent Promotion Activity
        </h5>
        <a href="{{ route('admin.career.requests.index') }}" class="btn btn-outline-dark btn-sm">
            View All Requests
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Agent</th>
                        <th>Current Rank</th>
                        <th>Proposed Rank</th>
                        <th>Period</th>
                        <th>Request Date</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPromotions as $req)
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
                            <span class="badge bg-light text-dark border">{{ $req->currentLevel->name ?? 'Level 1' }}</span>
                        </td>
                        <td>
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
                        <td class="text-center">
                            <a href="{{ route('admin.career.requests.show', $req) }}" class="btn btn-outline-dark btn-sm py-1 px-2" title="View Request Details">
                                <i class="bi bi-eye"></i> Review
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                            No promotion activity found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
