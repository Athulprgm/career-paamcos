@extends('superadmin.layouts.app')

@section('page-title', 'Career Reports')

@section('content')
@include('admin.reports.partials.print-styles', ['orientation' => 'landscape'])

@php
    $branchName = !empty($filters['branch_id']) ? ($branches->firstWhere('id', $filters['branch_id'])->name ?? 'Branch #'.$filters['branch_id']) : 'All Branches';
    $agentName = !empty($filters['agent_id']) ? ($agents->firstWhere('id', $filters['agent_id'])->name ?? 'Agent #'.$filters['agent_id']) : null;
    $dateFrom = $filters['date_from'] ?? null;
    $dateTo = $filters['date_to'] ?? null;
    $periodLabel = ($dateFrom || $dateTo) ? (($dateFrom ?? 'Start').' to '.($dateTo ?? 'Now')) : 'All Dates';

    $printMeta = [
        'Nomination Range' => $periodLabel,
        'Branch' => $branchName,
    ];
    if ($agentName) $printMeta['Agent'] = $agentName;
    if (!empty($filters['status'])) $printMeta['Status'] = str_replace('_', ' ', $filters['status']);
    $printMeta['Total Nominations'] = number_format($summary['total_requests'] ?? 0);
    $printMeta['Promoted Agents'] = number_format($summary['approved_requests'] ?? 0);
@endphp

<!-- Co-operative Official Print Header (Visible only when printed) -->
@include('admin.reports.partials.print-header', [
    'title' => 'Career Progression & Promotion Audit Report',
    'subtitle' => 'Career Progression Ladder, Eligibility & Promotions',
    'meta' => $printMeta
])

<!-- Page Header (Screen only) -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Career Reports</h1>
        <div class="page-breadcrumb">Reports / Career Progression Ladder, Eligibility & Promotions</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center report-actions">
        <a href="{{ route('admin.reports.career.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

<!-- Unified Reports Navigation Tab Bar -->
@include('admin.reports._nav')

<!-- Summary Metrics Cards -->
<div class="row g-3 mb-4 report-summary-cards">
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon dark"><i class="bi bi-award-fill"></i></div>
            <div>
                <div class="stat-value">{{ $summary['total_requests'] }}</div>
                <div class="stat-label">Total Nominations</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-value text-warning">{{ $summary['pending_requests'] }}</div>
                <div class="stat-label">Pending Review</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon emerald"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-value text-success">{{ $summary['approved_requests'] }}</div>
                <div class="stat-label">Approved & Promoted</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
            <div>
                <div class="stat-value text-danger">{{ $summary['rejected_requests'] }}</div>
                <div class="stat-label">Rejected Requests</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-person-check"></i></div>
            <div>
                <div class="stat-value text-primary">{{ $summary['eligible_agents_count'] }}</div>
                <div class="stat-label">Eligible Candidates</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon light"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="stat-value text-secondary">{{ $summary['historical_promotions'] }}</div>
                <div class="stat-label">Milestones Logged</div>
            </div>
        </div>
    </div>
</div>

<!-- Agents Distribution across Career Ladder -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2 fw-semibold">
            <i class="bi bi-diagram-3 text-muted"></i>
            <span>Career Ladder Distribution</span>
        </div>
        @if(auth()->check() && auth()->user()->isSuperAdmin())
        <a href="{{ route('superadmin.agent-ranks.index') }}" class="small text-decoration-none text-dark fw-medium d-print-none">
            Manage Agent Ranks &rarr;
        </a>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($ladder_breakdown as $row)
            <div class="col-sm-6 col-md-4 col-lg-2">
                <div class="card ladder-distribution-card h-100 text-center border p-3 bg-light rounded-3">
                    <span class="badge bg-dark mb-2 align-self-center">Rank {{ $row['level']->rank_order }}</span>
                    <div class="fw-semibold text-dark text-truncate" title="{{ $row['level']->name }}">{{ $row['level']->name }}</div>
                    <div class="small text-muted mb-2 font-monospace">{{ $row['level']->code }}</div>
                    <div class="fs-4 fw-bold text-dark">{{ $row['total_agents'] }} <span class="fs-6 fw-normal text-muted">agents</span></div>
                    <div class="small text-success mt-1">Active: {{ $row['active_agents'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 filter-card">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.reports.career') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div style="min-width:130px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div style="min-width:130px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            @if($restrictedBranchId === null)
            <div style="min-width:175px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Branch</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($restrictedAgentId === null)
            <div style="min-width:175px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Agent</label>
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    @foreach($agents as $ag)
                        <option value="{{ $ag->id }}" {{ ($filters['agent_id'] ?? '') == $ag->id ? 'selected' : '' }}>{{ $ag->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div style="min-width:175px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Request Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="PENDING_REVIEW" {{ ($filters['status'] ?? '') === 'PENDING_REVIEW' ? 'selected' : '' }}>PENDING_REVIEW</option>
                    <option value="APPROVED" {{ ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                    <option value="PROMOTED" {{ ($filters['status'] ?? '') === 'PROMOTED' ? 'selected' : '' }}>PROMOTED</option>
                    <option value="REJECTED" {{ ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                </select>
            </div>
            <div class="d-flex gap-1 align-self-end mt-2 mt-md-0">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Apply
                </button>
                <a href="{{ route('admin.reports.career') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2 fw-semibold">
            <i class="bi bi-table text-muted"></i>
            <span>Promotion Nomination Requests</span>
        </div>
        <span class="badge bg-light text-dark border">{{ $records->total() }} Requests</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Request #</th>
                        <th>Agent</th>
                        <th>Branch</th>
                        <th>Current Level</th>
                        <th>Proposed Level</th>
                        <th>Period</th>
                        <th>Requested On</th>
                        <th>Reviewed By</th>
                        <th class="text-center">Status</th>
                        <th class="text-center d-print-none">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $req)
                    <tr>
                        <td>
                            <a href="{{ route('admin.career.requests.show', $req) }}" class="fw-semibold text-dark text-decoration-none">
                                {{ $req->request_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $req->agent?->name ?? 'N/A' }}</div>
                            <div class="small text-muted font-monospace">{{ $req->agent?->agent_code ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $req->branch?->name ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $req->currentLevel?->name ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $req->proposedLevel?->name ?? '-' }}</span>
                        </td>
                        <td><span class="font-monospace small">{{ $req->performance_period ?? '-' }}</span></td>
                        <td>{{ $req->request_date ? \Carbon\Carbon::parse($req->request_date)->format('d M Y') : '-' }}</td>
                        <td>
                            <div class="small fw-semibold text-dark">{{ $req->reviewer?->name ?? '-' }}</div>
                            <div class="small text-muted">{{ $req->reviewed_at ? $req->reviewed_at->format('d M Y') : '-' }}</div>
                        </td>
                        <td class="text-center">
                            @php
                                $badgeClass = match($req->status) {
                                    'PROMOTED' => 'badge bg-success-subtle text-success border border-success-subtle',
                                    'APPROVED' => 'badge bg-primary-subtle text-primary border border-primary-subtle',
                                    'PENDING_REVIEW' => 'badge bg-warning-subtle text-warning border border-warning-subtle',
                                    'REJECTED' => 'badge bg-danger-subtle text-danger border border-danger-subtle',
                                    default => 'badge bg-light text-dark border',
                                };
                            @endphp
                            <span class="{{ $badgeClass }}">{{ str_replace('_', ' ', $req->status) }}</span>
                        </td>
                        <td class="text-center d-print-none">
                            <a href="{{ route('admin.career.requests.show', $req) }}" class="btn btn-outline-dark btn-sm py-1 px-2" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No career promotion records found matching the specified criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="8" class="text-uppercase text-end">Total / Summary:</td>
                        <td class="text-center">{{ number_format($records->total()) }} Nominations</td>
                        <td class="d-print-none"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @if($records->hasPages())
    <div class="card-footer bg-transparent py-3">
        {{ $records->links() }}
    </div>
    @endif
</div>

<!-- Co-operative Official Verification & Sign-off Block (Visible only when printed) -->
@include('admin.reports.partials.print-footer')
@endsection
