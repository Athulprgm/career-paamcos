@extends('superadmin.layouts.app')

@section('page-title', 'Customer Reports')

@section('content')
@include('admin.reports.partials.print-styles', ['orientation' => 'landscape'])

@php
    $branchName = !empty($filters['branch_id']) ? ($branches->firstWhere('id', $filters['branch_id'])->name ?? 'Branch #'.$filters['branch_id']) : 'All Branches';
    $agentName = !empty($filters['agent_id']) ? ($agents->firstWhere('id', $filters['agent_id'])->name ?? 'Agent #'.$filters['agent_id']) : null;
    $dateFrom = $filters['date_from'] ?? null;
    $dateTo = $filters['date_to'] ?? null;
    $periodLabel = ($dateFrom || $dateTo) ? (($dateFrom ?? 'Start').' to '.($dateTo ?? 'Now')) : 'All Dates';

    $printMeta = [
        'Registration Range' => $periodLabel,
        'Branch' => $branchName,
    ];
    if ($agentName) $printMeta['Agent'] = $agentName;
    if (!empty($filters['status'])) $printMeta['Status'] = $filters['status'];
    $printMeta['Total Customers'] = number_format($summary['total_customers']);
    $printMeta['Active Accounts'] = number_format($summary['active_customers']);
@endphp

<!-- Co-operative Official Print Header (Visible only when printed) -->
@include('admin.reports.partials.print-header', [
    'title' => 'Customer Portfolio & Demographics Report',
    'subtitle' => 'Customer Demographics, Membership & Deposit Portfolios',
    'meta' => $printMeta
])

<!-- Page Header (Screen only) -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Customer Reports</h1>
        <div class="page-breadcrumb">Reports / Customer Demographics & Deposits</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center report-actions">
        <a href="{{ route('admin.reports.customers.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
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
    <div class="col-sm-6">
        <div class="stat-card">
            <div class="stat-icon black"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value">{{ number_format($summary['total_customers']) }}</div>
                <div class="stat-label">Total Registered Customers</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="stat-card">
            <div class="stat-icon emerald"><i class="bi bi-person-check-fill"></i></div>
            <div>
                <div class="stat-value text-success">{{ number_format($summary['active_customers']) }}</div>
                <div class="stat-label">Active Operating Accounts</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 filter-card">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.reports.customers') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div style="min-width:130px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div style="min-width:130px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            @if($restrictedBranchId === null)
            <div style="min-width:160px;" class="flex-grow-1 flex-md-grow-0">
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
            <div style="min-width:160px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Agent</label>
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    @foreach($agents as $ag)
                        <option value="{{ $ag->id }}" {{ ($filters['agent_id'] ?? '') == $ag->id ? 'selected' : '' }}>{{ $ag->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div style="min-width:140px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Account Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE" {{ ($filters['status'] ?? '') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                    <option value="PENDING" {{ ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' }}>PENDING</option>
                    <option value="INACTIVE" {{ ($filters['status'] ?? '') === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                    <option value="REJECTED" {{ ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                </select>
            </div>
            <div style="min-width:180px;" class="flex-grow-1">
                <label class="form-label text-muted small mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, Mobile, Number..." class="form-control form-control-sm">
            </div>
            <div class="d-flex gap-1 align-self-end mt-2 mt-md-0">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Apply
                </button>
                <a href="{{ route('admin.reports.customers') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
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
            <span>Customer Directory & Portfolios</span>
        </div>
        <span class="badge bg-light text-dark border">{{ $records->total() }} Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Customer #</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Branch</th>
                        <th>Agent</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Active Deposits</th>
                        <th class="text-end">Total Deposit Value</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $cust)
                    <tr>
                        <td>
                            <span class="fw-semibold text-dark">
                                {{ $cust->customer_number }}
                            </span>
                        </td>
                        <td class="fw-semibold text-dark">{{ $cust->name }}</td>
                        <td>{{ $cust->mobile ?? '-' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $cust->branch?->name ?? '-' }}</span>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">{{ $cust->agent?->name ?? 'Direct' }}</div>
                            <div class="small text-muted">{{ $cust->agent?->agent_code ?? '-' }}</div>
                        </td>
                        <td class="text-center">
                            @php
                                $badgeClass = match($cust->status) {
                                    'ACTIVE' => 'badge bg-success-subtle text-success border border-success-subtle',
                                    'PENDING' => 'badge bg-warning-subtle text-warning border border-warning-subtle',
                                    'INACTIVE' => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    'REJECTED' => 'badge bg-danger-subtle text-danger border border-danger-subtle',
                                    default => 'badge bg-light text-dark border',
                                };
                            @endphp
                            <span class="{{ $badgeClass }}">{{ $cust->status }}</span>
                        </td>
                        <td class="text-center fw-semibold">
                            {{ $cust->active_deposits_count }} <span class="small text-muted">/ {{ $cust->total_deposits_count }}</span>
                        </td>
                        <td class="text-end fw-semibold text-success">
                            ₹{{ number_format((float) ($cust->total_deposits_sum ?? 0), 2) }}
                        </td>
                        <td>{{ $cust->created_at ? $cust->created_at->format('d M Y') : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No customer records found matching the specified criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="6" class="text-uppercase text-end">Total / Summary:</td>
                        <td class="text-center">{{ number_format($records->sum('active_deposits_count')) }} Active Deposits</td>
                        <td class="text-end text-success">₹{{ number_format((float) $records->sum('total_deposits_sum'), 2) }}</td>
                        <td class="text-center">{{ number_format($records->total()) }} Customers</td>
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
