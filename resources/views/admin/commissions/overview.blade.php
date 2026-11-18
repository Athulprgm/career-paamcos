@extends('superadmin.layouts.app')

@section('page-title', 'Commission Overview')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Commission Overview</h1>
        <div class="page-breadcrumb">Commission / Performance & Payout Analytics</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if(auth()->check() && auth()->user()->isSuperAdmin())
        <a href="{{ route('superadmin.fd-commissions.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-percent me-1"></i> Deposit Commissions
        </a>
        @endif
        <a href="{{ route('admin.commissions.pending') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-hourglass-split me-1"></i> Pending ({{ $pendingCount }})
        </a>
        <a href="{{ route('admin.commissions.payable') }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-wallet2 me-1"></i> Payable ({{ $payableCount }})
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
        <form method="GET" action="{{ route('admin.commissions.overview') }}" class="d-flex gap-2 flex-wrap align-items-end">
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

            <!-- Agent filter -->
            @if(is_null($restrictedAgentId))
            <div style="min-width:180px;">
                <label class="form-label text-muted small mb-1">Agent</label>
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    @foreach($agents as $ag)
                        <option value="{{ $ag->id }}" {{ request('agent_id') == $ag->id ? 'selected' : '' }}>
                            {{ $ag->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Scheme filter -->
            <div style="min-width:200px;">
                <label class="form-label text-muted small mb-1">Deposit Scheme</label>
                <select name="scheme_id" class="form-select form-select-sm">
                    <option value="">All Schemes</option>
                    @foreach($schemes as $sch)
                        <option value="{{ $sch->id }}" {{ request('scheme_id') == $sch->id ? 'selected' : '' }}>
                            {{ $sch->code }} - {{ $sch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Period filter -->
            <div style="min-width:150px;">
                <label class="form-label text-muted small mb-1">Period</label>
                <select name="period" class="form-select form-select-sm">
                    <option value="">All Periods</option>
                    @foreach($periods as $p)
                        <option value="{{ $p }}" {{ request('period') === $p ? 'selected' : '' }}>
                            {{ $p }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex gap-1 align-self-end">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('admin.commissions.overview') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Real Database Metrics Grid -->
<div class="row g-3 mb-4">
    <!-- 1. Pending -->
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon grey" style="background:rgba(217, 119, 6, 0.12);color:#d97706;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <div class="stat-value text-warning">₹{{ number_format($pendingAmount, 2) }}</div>
                <div class="stat-label">Pending ({{ $pendingCount }})</div>
            </div>
        </div>
    </div>

    <!-- 2. Approved -->
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon dark">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div>
                <div class="stat-value" style="color:var(--color-primary);">₹{{ number_format($approvedAmount, 2) }}</div>
                <div class="stat-label">Approved ({{ $approvedCount }})</div>
            </div>
        </div>
    </div>

    <!-- 3. Payable -->
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon emerald">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="stat-value text-success">₹{{ number_format($payableAmount, 2) }}</div>
                <div class="stat-label">Payable ({{ $payableCount }})</div>
            </div>
        </div>
    </div>

    <!-- 4. Paid -->
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div>
                <div class="stat-value" style="color:var(--color-accent);">₹{{ number_format($paidAmount, 2) }}</div>
                <div class="stat-label">Paid ({{ $paidCount }})</div>
            </div>
        </div>
    </div>

    <!-- 5. Current Period (YYYY-MM) -->
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon black">
                <i class="bi bi-calendar-month"></i>
            </div>
            <div>
                <div class="stat-value">₹{{ number_format($currentPeriodAmount, 2) }}</div>
                <div class="stat-label">{{ $currentPeriod }} ({{ $currentPeriodCount }})</div>
            </div>
        </div>
    </div>

    <!-- 6. Total Commission -->
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon light">
                <i class="bi bi-pie-chart"></i>
            </div>
            <div>
                <div class="stat-value">₹{{ number_format($totalCommissionAmount, 2) }}</div>
                <div class="stat-label">Total Commission</div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Commissions Table Card -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i>
            <span>Recent Commission Records</span>
            <span class="badge bg-light text-dark border ms-1">{{ $recentCommissions->count() }}</span>
        </div>
        <a href="{{ route('admin.commissions.index') }}" class="btn btn-sm btn-outline-dark">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Commission #</th>
                        <th>Agent</th>
                        <th>Customer / Deposit</th>
                        <th>Rule / Type</th>
                        <th class="text-end">Base Amount (₹)</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Commission (₹)</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentCommissions as $comm)
                    <tr>
                        <td>
                            <a href="{{ route('admin.commissions.show', $comm) }}" class="fw-semibold text-decoration-none" style="color:var(--color-primary);">
                                {{ $comm->commission_number }}
                            </a>
                            <div class="small text-muted">Period: {{ $comm->period }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $comm->agent->name ?? 'N/A' }}</div>
                            <div class="small text-muted">{{ $comm->agent->member_code ?? $comm->agent->agent_id }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $comm->customer->name ?? 'N/A' }}</div>
                            <div class="small text-muted">Dep #{{ $comm->deposit->deposit_number ?? '' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $comm->rule->name ?? 'Standard Rule' }}</div>
                            <div class="small text-muted">{{ $comm->rule->commission_type ?? 'PERCENTAGE' }}</div>
                        </td>
                        <td class="text-end fw-semibold">₹{{ number_format($comm->base_amount, 2) }}</td>
                        <td class="text-end">
                            {{ $comm->rule && $comm->rule->commission_type === 'FIXED' ? 'Flat' : $comm->rate . '%' }}
                        </td>
                        <td class="text-end text-success fw-bold">₹{{ number_format($comm->calculated_amount, 2) }}</td>
                        <td class="text-center">
                            @php $st = strtoupper((string) $comm->status); @endphp
                            @if($st === 'PENDING')
                                <span class="badge-pending">PENDING</span>
                            @elseif($st === 'APPROVED')
                                <span class="badge bg-primary text-white">APPROVED</span>
                            @elseif($st === 'PAYABLE')
                                <span class="badge-active">PAYABLE</span>
                            @elseif($st === 'PAID')
                                <span class="badge-paid">PAID</span>
                            @elseif($st === 'REJECTED')
                                <span class="badge-rejected">REJECTED</span>
                            @else
                                <span class="badge bg-light text-dark border">{{ $st }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.commissions.show', $comm) }}" class="btn btn-outline-dark btn-sm py-1 px-2" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                            No commission records found matching criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
