@extends('superadmin.layouts.app')

@section('page-title', 'Audit Reports')

@section('content')
@include('admin.reports.partials.print-styles', ['orientation' => 'landscape'])

@php
    $dateFrom = $filters['date_from'] ?? null;
    $dateTo = $filters['date_to'] ?? null;
    $periodLabel = ($dateFrom || $dateTo) ? (($dateFrom ?? 'Start').' to '.($dateTo ?? 'Now')) : 'All Recorded Dates';

    $printMeta = [
        'Audit Period' => $periodLabel,
    ];
    if (!empty($filters['event'])) $printMeta['Event Type'] = $filters['event'];
    if (!empty($filters['user_id'])) $printMeta['Audited User'] = 'User #'.$filters['user_id'];
    $printMeta['Total Events'] = number_format($summary['total_events']);
    $printMeta['Financial Volume'] = '₹'.number_format($summary['total_financial_amount'], 2);
@endphp

<!-- Co-operative Official Print Header (Visible only when printed) -->
@include('admin.reports.partials.print-header', [
    'title' => 'Immutable System Audit & Financial Log Report',
    'subtitle' => 'Immutable Security Audit Trail & Financial Logs',
    'meta' => $printMeta
])

<!-- Page Header (Screen only) -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Audit Reports</h1>
        <div class="page-breadcrumb">Reports / Immutable Security Audit Trail & Financial Logs</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center report-actions">
        <a href="{{ route('admin.reports.audit.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
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
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon dark"><i class="bi bi-shield-check"></i></div>
            <div>
                <div class="stat-value">{{ number_format($summary['total_events']) }}</div>
                <div class="stat-label">Total Audit Events</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon emerald"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="stat-value text-success">{{ number_format($summary['financial_events']) }}</div>
                <div class="stat-label">Financial Transactions</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-currency-rupee"></i></div>
            <div>
                <div class="stat-value text-primary">₹{{ number_format($summary['total_financial_amount'], 2) }}</div>
                <div class="stat-label">Audited Financial Volume</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon light"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value">{{ number_format($summary['unique_users_count']) }}</div>
                <div class="stat-label">Unique Authorized Actors</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 filter-card">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.reports.audit') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div style="min-width:130px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div style="min-width:130px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div style="min-width:150px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">User / Actor</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All Users</option>
                    @foreach($allUsers as $u)
                        <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:140px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Event Type</label>
                <input type="text" name="event" value="{{ $filters['event'] ?? '' }}" placeholder="APPROVED, CREATED..." class="form-control form-control-sm">
            </div>
            <div style="min-width:140px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Entity</label>
                <input type="text" name="auditable_type" value="{{ $filters['auditable_type'] ?? '' }}" placeholder="Deposit, Commission..." class="form-control form-control-sm">
            </div>
            <div style="min-width:170px;" class="flex-grow-1">
                <label class="form-label text-muted small mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Description, User, IP..." class="form-control form-control-sm">
            </div>
            <div class="d-flex gap-1 align-self-end mt-2 mt-md-0">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Apply
                </button>
                <a href="{{ route('admin.reports.audit') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
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
            <i class="bi bi-shield-check text-muted"></i>
            <span>Security & Audit Trail Log</span>
        </div>
        <span class="badge bg-light text-dark border">{{ $records->total() }} Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Target Entity</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $log)
                    <tr>
                        <td>
                            <div class="small fw-semibold text-dark">{{ $log->created_at ? $log->created_at->format('d M Y') : '-' }}</div>
                            <div class="small text-muted font-monospace">{{ $log->created_at ? $log->created_at->format('H:i:s') : '-' }}</div>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">{{ $log->user_name ?? ($log->user?->name ?? 'System') }}</div>
                            <div class="small text-muted">{{ $log->user?->email ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $log->event }}</span>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">{{ class_basename($log->auditable_type ?? 'N/A') }}</div>
                            <div class="small text-muted font-monospace">#{{ $log->auditable_id ?? '-' }}</div>
                        </td>
                        <td class="small" style="max-width: 280px;">
                            {{ $log->description }}
                        </td>
                        <td class="text-end fw-semibold {{ $log->financial_amount ? 'text-success' : 'text-muted' }}">
                            {{ $log->financial_amount !== null ? '₹' . number_format((float) $log->financial_amount, 2) : '-' }}
                        </td>
                        <td>
                            <code class="small text-muted">{{ $log->ip_address ?? '-' }}</code>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No audit log records found matching the specified criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5" class="text-uppercase text-end">Total / Summary:</td>
                        <td class="text-end text-success">₹{{ number_format((float) $summary['total_financial_amount'], 2) }}</td>
                        <td class="text-center">{{ number_format($records->total()) }} Events</td>
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
