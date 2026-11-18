@extends('superadmin.layouts.app')

@section('page-title', 'Commission Reports')

@section('content')
@include('admin.reports.partials.print-styles', ['orientation' => 'landscape'])

@php
    $branchName = !empty($filters['branch_id']) ? ($branches->firstWhere('id', $filters['branch_id'])->name ?? 'Branch #'.$filters['branch_id']) : 'All Branches';
    $agentName = !empty($filters['agent_id']) ? ($agents->firstWhere('id', $filters['agent_id'])->name ?? 'Agent #'.$filters['agent_id']) : null;
    $dateFrom = $filters['date_from'] ?? null;
    $dateTo = $filters['date_to'] ?? null;
    $periodLabel = ($dateFrom || $dateTo) ? (($dateFrom ?? 'Start').' to '.($dateTo ?? 'Now')) : (!empty($filters['period']) ? $filters['period'] : 'All Periods');

    $printMeta = [
        'Period' => $periodLabel,
        'Branch' => $branchName,
    ];
    if ($agentName) $printMeta['Agent'] = $agentName;
    if (!empty($filters['status'])) $printMeta['Status'] = $filters['status'];
    $printMeta['Total Commission'] = '₹'.number_format($summary['total_commission'], 2);
@endphp

<!-- Co-operative Official Print Header (Visible only when printed) -->
@include('admin.reports.partials.print-header', [
    'title' => 'Agent Commission Ledger & Payout Report',
    'subtitle' => 'Agent Commission Ledgers & Payout Batches',
    'meta' => $printMeta
])

<!-- Page Header (Screen only) -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Commission Reports</h1>
        <div class="page-breadcrumb">Reports / Agent Commission Ledgers & Payout Batches</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center report-actions">
        <a href="{{ route('admin.reports.commissions.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
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
            <div class="stat-icon dark"><i class="bi bi-percent"></i></div>
            <div>
                <div class="stat-value">₹{{ number_format($summary['total_commission'], 2) }}</div>
                <div class="stat-label">Total Commission</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-value text-warning">₹{{ number_format($summary['pending_commission'], 2) }}</div>
                <div class="stat-label">Pending Review</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon light"><i class="bi bi-check-circle"></i></div>
            <div>
                <div class="stat-value">₹{{ number_format($summary['approved_commission'], 2) }}</div>
                <div class="stat-label">Approved</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="stat-value text-primary">₹{{ number_format($summary['payable_commission'], 2) }}</div>
                <div class="stat-label">Payable Batches</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon emerald"><i class="bi bi-cash-coin"></i></div>
            <div>
                <div class="stat-value text-success">₹{{ number_format($summary['paid_commission'], 2) }}</div>
                <div class="stat-label">Paid Out</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
            <div>
                <div class="stat-value text-danger">₹{{ number_format($summary['rejected_commission'], 2) }}</div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 filter-card">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.reports.commissions') }}" class="d-flex gap-2 flex-wrap align-items-end">
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
            <div style="min-width:160px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="PAID" {{ ($filters['status'] ?? '') === 'PAID' ? 'selected' : '' }}>PAID</option>
                    <option value="PAYABLE" {{ ($filters['status'] ?? '') === 'PAYABLE' ? 'selected' : '' }}>PAYABLE</option>
                    <option value="APPROVED" {{ ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                    <option value="PENDING" {{ ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' }}>PENDING</option>
                    <option value="REJECTED" {{ ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                </select>
            </div>
            <div style="min-width:130px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Period (YYYY-MM)</label>
                <input type="text" name="period" value="{{ $filters['period'] ?? '' }}" placeholder="2026-03" class="form-control form-control-sm">
            </div>
            <div style="min-width:160px;" class="flex-grow-1">
                <label class="form-label text-muted small mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Commission #, Agent, Deposit..." class="form-control form-control-sm">
            </div>
            <div class="d-flex gap-1 align-self-end mt-2 mt-md-0">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Apply
                </button>
                <a href="{{ route('admin.reports.commissions') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
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
            <span>Commission Ledgers & Records</span>
        </div>
        <span class="badge bg-light text-dark border">{{ $records->total() }} Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Commission #</th>
                        <th>Agent</th>
                        <th>Customer</th>
                        <th>Deposit #</th>
                        <th>Collection #</th>
                        <th class="text-end">Base Amount</th>
                        <th class="text-center">Rate</th>
                        <th class="text-end">Commission</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Period</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $com)
                    <tr>
                        <td>
                            <a href="{{ route('admin.commissions.show', $com) }}" class="fw-semibold text-dark text-decoration-none">
                                {{ $com->commission_number ?: $com->commission_id }}
                            </a>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">{{ $com->agent?->name ?? 'N/A' }}</div>
                            <div class="small text-muted">{{ $com->agent?->agent_code ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">{{ $com->customer?->name ?? '-' }}</div>
                            <div class="small text-muted">{{ $com->customer?->customer_number ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="small text-muted font-monospace">{{ $com->deposit?->deposit_number ?? '-' }}</span>
                            @if($com->deposit && $com->payout_year)
                                <div><span class="badge bg-primary text-white py-0 px-1" style="font-size:0.7rem;">Yr {{ $com->payout_year }}</span></div>
                            @endif
                        </td>
                        <td>
                            <span class="small text-muted font-monospace">{{ $com->collection?->collection_number ?? '-' }}</span>
                        </td>
                        <td class="text-end">₹{{ number_format((float) $com->base_amount, 2) }}</td>
                        <td class="text-center"><span class="badge bg-light text-dark border">{{ number_format((float) ($com->commission_rate ?? $com->rate), 2) }}%</span></td>
                        <td class="text-end fw-semibold text-success">₹{{ number_format((float) $com->commission_amount, 2) }}</td>
                        <td class="text-center">
                            @php
                                $badgeClass = match($com->status) {
                                    'PAID' => 'badge bg-success-subtle text-success border border-success-subtle',
                                    'PAYABLE' => 'badge bg-primary-subtle text-primary border border-primary-subtle',
                                    'APPROVED' => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    'PENDING' => 'badge bg-warning-subtle text-warning border border-warning-subtle',
                                    'REJECTED' => 'badge bg-danger-subtle text-danger border border-danger-subtle',
                                    default => 'badge bg-light text-dark border',
                                };
                            @endphp
                            <span class="{{ $badgeClass }}">{{ $com->status }}</span>
                        </td>
                        <td class="text-center"><span class="badge bg-light text-dark border font-monospace">{{ $com->period ?? '-' }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No commission records found matching the specified criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5" class="text-uppercase text-end">Total / Summary:</td>
                        <td class="text-end">₹{{ number_format((float) $records->sum('base_amount'), 2) }}</td>
                        <td></td>
                        <td class="text-end text-success">₹{{ number_format((float) ($summary['total_commission'] ?? 0), 2) }}</td>
                        <td colspan="2" class="text-center">{{ number_format($records->total()) }} Vouchers</td>
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
