@extends('superadmin.layouts.app')

@section('page-title', 'Deposit Reports')

@section('content')
@include('admin.reports.partials.print-styles', ['orientation' => 'landscape'])

@php
    $branchName = !empty($filters['branch_id']) ? ($branches->firstWhere('id', $filters['branch_id'])->name ?? 'Branch #'.$filters['branch_id']) : 'All Branches';
    $agentName = !empty($filters['agent_id']) ? ($agents->firstWhere('id', $filters['agent_id'])->name ?? 'Agent #'.$filters['agent_id']) : null;
    $schemeName = !empty($filters['scheme_id']) ? ($schemes->firstWhere('id', $filters['scheme_id'])->name ?? 'Scheme #'.$filters['scheme_id']) : null;
    $dateFrom = $filters['date_from'] ?? null;
    $dateTo = $filters['date_to'] ?? null;
    $periodLabel = ($dateFrom || $dateTo) ? (($dateFrom ?? 'Start').' to '.($dateTo ?? 'Now')) : 'All Dates';

    $printMeta = [
        'Date Range' => $periodLabel,
        'Branch' => $branchName,
    ];
    if ($agentName) $printMeta['Agent'] = $agentName;
    if ($schemeName) $printMeta['Scheme'] = $schemeName;
    if (!empty($filters['status'])) $printMeta['Status'] = $filters['status'];
    $printMeta['Total Accounts'] = number_format($summary['total_count']);
@endphp

<!-- Co-operative Official Print Header (Visible only when printed) -->
@include('admin.reports.partials.print-header', [
    'title' => 'Deposit Portfolio & Accounts Report',
    'subtitle' => 'Deposit Accounts, Portfolios & Schemes',
    'meta' => $printMeta
])

<!-- Page Header (Screen only) -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Deposit Reports</h1>
        <div class="page-breadcrumb">Reports / Deposit Accounts, Portfolios & Schemes</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center report-actions">
        <a href="{{ route('admin.reports.deposits.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
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
            <div class="stat-icon dark"><i class="bi bi-piggy-bank-fill"></i></div>
            <div>
                <div class="stat-value">{{ number_format($summary['total_count']) }}</div>
                <div class="stat-label">Total Accounts</div>
                <div class="small text-muted mt-1">₹{{ number_format($summary['total_amount'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon emerald"><i class="bi bi-check2-circle"></i></div>
            <div>
                <div class="stat-value text-success">{{ number_format($summary['active_count']) }}</div>
                <div class="stat-label">Active Deposits</div>
                <div class="small text-muted mt-1">₹{{ number_format($summary['active_amount'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-calendar-check"></i></div>
            <div>
                <div class="stat-value">{{ number_format($summary['matured_count']) }}</div>
                <div class="stat-label">Matured</div>
                <div class="small text-muted mt-1">Completed contracts</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon light"><i class="bi bi-archive"></i></div>
            <div>
                <div class="stat-value text-secondary">{{ number_format($summary['closed_count']) }}</div>
                <div class="stat-label">Closed</div>
                <div class="small text-muted mt-1">Settled accounts</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-value text-warning">{{ number_format($summary['pending_count']) }}</div>
                <div class="stat-label">Pending / Draft</div>
                <div class="small text-muted mt-1">Awaiting activation</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
            <div>
                <div class="stat-value text-danger">{{ number_format($summary['rejected_count']) }}</div>
                <div class="stat-label">Rejected</div>
                <div class="small text-muted mt-1">Declined contracts</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 filter-card">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.reports.deposits') }}" class="d-flex gap-2 flex-wrap align-items-end">
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
            <div style="min-width:190px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Scheme</label>
                <select name="deposit_scheme_id" class="form-select form-select-sm">
                    <option value="">All Schemes</option>
                    @foreach($schemes as $s)
                        <option value="{{ $s->id }}" {{ ($filters['deposit_scheme_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }})</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:175px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE" {{ ($filters['status'] ?? '') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                    <option value="APPROVED" {{ ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                    <option value="PENDING_APPROVAL" {{ ($filters['status'] ?? '') === 'PENDING_APPROVAL' ? 'selected' : '' }}>PENDING_APPROVAL</option>
                    <option value="MATURED" {{ ($filters['status'] ?? '') === 'MATURED' ? 'selected' : '' }}>MATURED</option>
                    <option value="CLOSED" {{ ($filters['status'] ?? '') === 'CLOSED' ? 'selected' : '' }}>CLOSED</option>
                    <option value="REJECTED" {{ ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                </select>
            </div>
            <div style="min-width:170px;" class="flex-grow-1">
                <label class="form-label text-muted small mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Deposit # or Customer..." class="form-control form-control-sm">
            </div>
            <div class="d-flex gap-1 align-self-end mt-2 mt-md-0">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Apply
                </button>
                <a href="{{ route('admin.reports.deposits') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
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
            <span>Deposit Account Records</span>
        </div>
        <span class="badge bg-light text-dark border">{{ $records->total() }} Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Deposit #</th>
                        <th>Customer</th>
                        <th>Agent</th>
                        <th>Branch</th>
                        <th>Scheme</th>
                        <th class="text-end">Amount</th>
                        <th>Start Date</th>
                        <th>Maturity Date</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $d)
                    <tr>
                        <td>
                            <a href="{{ route('admin.deposits.show', $d) }}" class="fw-semibold text-dark text-decoration-none">
                                {{ $d->deposit_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $d->customer?->name ?? 'N/A' }}</div>
                            <div class="small text-muted">{{ $d->customer?->customer_number ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">{{ $d->agent?->name ?? 'Direct' }}</div>
                            <div class="small text-muted">{{ $d->agent?->agent_code ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $d->branch?->name ?? '-' }}</span>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">{{ $d->scheme?->name ?? '-' }}</div>
                            <div class="small text-muted font-monospace">{{ $d->scheme?->code ?? '-' }}</div>
                        </td>
                        <td class="text-end fw-semibold text-success">
                            ₹{{ number_format((float) $d->amount, 2) }}
                        </td>
                        <td>{{ $d->start_date ? \Carbon\Carbon::parse($d->start_date)->format('d M Y') : '-' }}</td>
                        <td>{{ $d->maturity_date ? \Carbon\Carbon::parse($d->maturity_date)->format('d M Y') : '-' }}</td>
                        <td class="text-center">
                            @php
                                $badgeClass = match($d->status) {
                                    'ACTIVE' => 'badge bg-success-subtle text-success border border-success-subtle',
                                    'APPROVED' => 'badge bg-primary-subtle text-primary border border-primary-subtle',
                                    'MATURED' => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    'CLOSED' => 'badge bg-light text-muted border',
                                    'PENDING_APPROVAL', 'DRAFT' => 'badge bg-warning-subtle text-warning border border-warning-subtle',
                                    'REJECTED' => 'badge bg-danger-subtle text-danger border border-danger-subtle',
                                    default => 'badge bg-light text-dark border',
                                };
                            @endphp
                            <span class="{{ $badgeClass }}">{{ $d->status }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No deposit records found matching the specified criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5" class="text-uppercase text-end">Total / Summary:</td>
                        <td class="text-end text-success">₹{{ number_format((float) $summary['total_amount'], 2) }}</td>
                        <td colspan="2"></td>
                        <td class="text-center">{{ number_format($summary['total_count']) }} Accounts</td>
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
