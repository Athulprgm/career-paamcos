@extends('superadmin.layouts.app')

@section('page-title', 'Reports & Analytics Overview')

@section('content')
@include('admin.reports.partials.print-styles', ['orientation' => 'landscape'])

@php
    $dateFrom = $filters['date_from'] ?? null;
    $dateTo = $filters['date_to'] ?? null;
    $periodLabel = ($dateFrom || $dateTo) ? (($dateFrom ?? 'Start').' to '.($dateTo ?? 'Now')) : 'All Operating History';

    $printMeta = [
        'Reporting Period' => $periodLabel,
        'Active Deposits' => number_format($active_deposits).' Accounts',
        'Active Portfolio' => '₹'.number_format($active_deposit_value, 2),
        'Active Customers' => number_format($active_customers),
    ];
    if (!empty($branch_breakdown)) {
        $printMeta['Branches Audited'] = count($branch_breakdown).' Branches';
    }
@endphp

<!-- Co-operative Official Print Header (Visible only when printed) -->
@include('admin.reports.partials.print-header', [
    'title' => 'Executive Management & Financial Operations Overview',
    'subtitle' => 'Executive Dashboard & Co-operative Financial Summary',
    'meta' => $printMeta
])

<style>
.report-suite-card {
    transition: all 0.2s ease;
    border: 1px solid var(--border-color);
    background: var(--card-bg);
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    display: block;
    height: 100%;
}
.report-suite-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(15, 23, 42, 0.08));
    border-color: var(--border-strong);
    color: inherit;
}
.report-suite-card .action-arrow {
    opacity: 0.4;
    transition: all 0.2s ease;
}
.report-suite-card:hover .action-arrow {
    opacity: 1;
    transform: translateX(3px);
    color: var(--color-accent) !important;
}
.stat-card-overview {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 18px 20px;
    background: var(--card-bg);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    transition: box-shadow 0.15s ease;
}
.stat-card-overview:hover {
    box-shadow: var(--shadow-card);
}
</style>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Reports & Analytics Overview</h1>
        <div class="page-breadcrumb">Reports / Executive Dashboard & Financial Summary</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center report-actions">
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Print Overview
        </button>
    </div>
</div>

<!-- Unified Reports Navigation Tab Bar -->
@include('admin.reports._nav')

<!-- Filter Card -->
<div class="card mb-4 filter-card">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.reports.overview') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div style="min-width:140px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div style="min-width:140px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            @if($restrictedBranchId === null)
            <div style="min-width:180px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Branch</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="d-flex gap-1 align-self-end mt-2 mt-md-0">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('admin.reports.overview') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Primary Financial & Operational KPIs -->
<div class="row g-3 mb-4 report-summary-cards">
    <!-- Customers KPI -->
    <div class="col-12 col-md-4">
        <div class="stat-card-overview">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="stat-icon black"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="stat-value">{{ number_format($total_customers) }}</div>
                    <div class="stat-label">Customer Base</div>
                </div>
            </div>
            <div class="pt-2 border-top d-flex justify-content-between align-items-center small text-muted">
                <span>Active: <strong class="text-success">{{ number_format($active_customers) }}</strong></span>
                <a href="{{ route('admin.reports.customers') }}" class="text-decoration-none fw-medium text-dark d-print-none">Details &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Active Deposits Value KPI -->
    <div class="col-12 col-md-4">
        <div class="stat-card-overview">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="stat-icon emerald"><i class="bi bi-piggy-bank-fill"></i></div>
                <div>
                    <div class="stat-value text-success">₹{{ number_format($active_deposit_value, 2) }}</div>
                    <div class="stat-label">Active Deposits</div>
                </div>
            </div>
            <div class="pt-2 border-top d-flex justify-content-between align-items-center small text-muted">
                <span><strong>{{ number_format($active_deposits) }}</strong> / {{ number_format($total_deposits) }} Accounts</span>
                <a href="{{ route('admin.reports.deposits') }}" class="text-decoration-none fw-medium text-dark d-print-none">Details &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Commissions Paid KPI -->
    <div class="col-12 col-md-4">
        <div class="stat-card-overview">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="stat-icon amber"><i class="bi bi-percent"></i></div>
                <div>
                    <div class="stat-value text-warning">₹{{ number_format($paid_commission, 2) }}</div>
                    <div class="stat-label">Commissions Paid</div>
                </div>
            </div>
            <div class="pt-2 border-top d-flex justify-content-between align-items-center small text-muted">
                <span>Pending: <strong>₹{{ number_format($pending_commission, 2) }}</strong></span>
                <a href="{{ route('admin.reports.commissions') }}" class="text-decoration-none fw-medium text-dark d-print-none">Details &rarr;</a>
            </div>
        </div>
    </div>
</div>

<!-- Secondary KPIs: Field Force & Financial Volume -->
<div class="row g-3 mb-4 report-summary-cards">
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="stat-icon dark"><i class="bi bi-person-badge-fill"></i></div>
            <div>
                <div class="stat-value">{{ $active_agents }}</div>
                <div class="stat-label">Active Agents ({{ $total_agents }} Total)</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="stat-icon violet"><i class="bi bi-award-fill"></i></div>
            <div>
                <div class="stat-value">{{ $pending_promotions }}</div>
                <div class="stat-label">Pending Promotion Requests</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="stat-icon emerald"><i class="bi bi-graph-up-arrow"></i></div>
            <div>
                <div class="stat-value text-success">₹{{ number_format($total_deposit_value, 2) }}</div>
                <div class="stat-label">Total Deposit Portfolio Volume</div>
            </div>
        </div>
    </div>
</div>

<!-- Executive Reports Suite Navigation Hub -->
<div class="card mb-4 d-print-none">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2 fw-semibold">
            <i class="bi bi-grid-3x3-gap text-muted"></i>
            <span>Executive Reports Suite</span>
        </div>
        <span class="badge bg-light text-dark border">7 Specialized Ledgers</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <!-- 1. Customers -->
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('admin.reports.customers') }}" class="report-suite-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon dark" style="width:38px;height:38px;font-size:1.1rem;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <i class="bi bi-arrow-right action-arrow text-muted fs-6"></i>
                    </div>
                    <div class="fw-semibold text-dark">Customer Reports</div>
                    <div class="text-muted small">Demographics & account holdings</div>
                </a>
            </div>

            <!-- 2. Deposits -->
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('admin.reports.deposits') }}" class="report-suite-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon emerald" style="width:38px;height:38px;font-size:1.1rem;">
                            <i class="bi bi-piggy-bank-fill"></i>
                        </div>
                        <i class="bi bi-arrow-right action-arrow text-muted fs-6"></i>
                    </div>
                    <div class="fw-semibold text-dark">Deposit Reports</div>
                    <div class="text-muted small">Schemes, active balances, maturity dates</div>
                </a>
            </div>

            <!-- 3. Commissions -->
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('admin.reports.commissions') }}" class="report-suite-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon amber" style="width:38px;height:38px;font-size:1.1rem;">
                            <i class="bi bi-percent"></i>
                        </div>
                        <i class="bi bi-arrow-right action-arrow text-muted fs-6"></i>
                    </div>
                    <div class="fw-semibold text-dark">Commission Reports</div>
                    <div class="text-muted small">Agent payout ledgers, approvals & disbursals</div>
                </a>
            </div>

            <!-- 4. Agents -->
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('admin.reports.agents') }}" class="report-suite-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon dark" style="width:38px;height:38px;font-size:1.1rem;">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <i class="bi bi-arrow-right action-arrow text-muted fs-6"></i>
                    </div>
                    <div class="fw-semibold text-dark">Agent Performance</div>
                    <div class="text-muted small">Productivity scorecards & mobilized volumes</div>
                </a>
            </div>

            <!-- 5. Career -->
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('admin.reports.career') }}" class="report-suite-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon violet" style="width:38px;height:38px;font-size:1.1rem;">
                            <i class="bi bi-award-fill"></i>
                        </div>
                        <i class="bi bi-arrow-right action-arrow text-muted fs-6"></i>
                    </div>
                    <div class="fw-semibold text-dark">Career Progression</div>
                    <div class="text-muted small">Rank advancement, eligibility & promotions</div>
                </a>
            </div>

            <!-- 6. Audit -->
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('admin.reports.audit') }}" class="report-suite-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon red" style="width:38px;height:38px;font-size:1.1rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <i class="bi bi-arrow-right action-arrow text-muted fs-6"></i>
                    </div>
                    <div class="fw-semibold text-dark">Audit Trail Reports</div>
                    <div class="text-muted small">Immutable security logs & financial operations</div>
                </a>
            </div>

            <!-- 7. Agent FY Targets -->
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('admin.targets.index') }}" class="report-suite-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon emerald" style="width:38px;height:38px;font-size:1.1rem;">
                            <i class="bi bi-crosshair"></i>
                        </div>
                        <i class="bi bi-arrow-right action-arrow text-muted fs-6"></i>
                    </div>
                    <div class="fw-semibold text-dark">Agent FY Targets</div>
                    <div class="text-muted small">Financial year deposit & recruitment target reports</div>
                </a>
            </div>
        </div>
    </div>
</div>

@if(!empty($branch_breakdown))
<!-- Branch Performance Breakdown (Super Admin Only) -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2 fw-semibold">
            <i class="bi bi-buildings text-muted"></i>
            <span>Branch Performance Breakdown</span>
        </div>
        <span class="badge bg-light text-dark border">{{ count($branch_breakdown) }} Branches</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Branch</th>
                        <th>Code</th>
                        <th class="text-center">Active Deposits</th>
                        <th class="text-end">Deposit Volume</th>
                        <th class="text-center">Customers</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branch_breakdown as $row)
                    <tr>
                        <td class="fw-semibold text-dark">{{ $row['branch']->name }}</td>
                        <td><span class="badge bg-light text-dark border font-monospace">{{ $row['branch']->code }}</span></td>
                        <td class="text-center"><span class="badge bg-light text-secondary border">{{ $row['active_deposits'] }}</span></td>
                        <td class="text-end fw-semibold text-success">₹{{ number_format($row['deposit_value'], 2) }}</td>
                        <td class="text-center">{{ number_format($row['customers']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-uppercase text-end">Total / Summary:</td>
                        <td class="text-center">{{ number_format(collect($branch_breakdown)->sum('active_deposits')) }}</td>
                        <td class="text-end text-success">₹{{ number_format(collect($branch_breakdown)->sum('deposit_value'), 2) }}</td>
                        <td class="text-center">{{ number_format(collect($branch_breakdown)->sum('customers')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Co-operative Official Verification & Sign-off Block (Visible only when printed) -->
@include('admin.reports.partials.print-footer')
@endsection
