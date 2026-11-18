@extends('superadmin.layouts.app')

@section('page-title', 'Staff Dashboard')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title h3 mb-1">Staff Operational Workspace &mdash; Banking Operations</h1>
        <div class="page-breadcrumb text-muted small">
            <span>Staff Portal</span>
            <span class="mx-1">/</span>
            <span>{{ $staff?->branch?->name ?? 'All Branches' }}</span>
            <span class="mx-1">/</span>
            <span class="text-primary fw-medium">{{ $staff?->position?->title ?? $user->role ?? 'Operational Staff' }}</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-dark px-3 py-2">
            <i class="bi bi-person-badge me-1"></i> Staff ID: {{ $staff?->employee_id ?? 'STF-'.$user->id }}
        </span>
        @if($staff?->branch)
        <span class="badge bg-secondary px-3 py-2">
            <i class="bi bi-building me-1"></i> {{ $staff->branch->name }}
        </span>
        @endif
    </div>
</div>

{{-- ── Operational Metrics Cards ───────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    {{-- Deposits to Verify --}}
    @if($canDeposits)
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.deposits.index', ['status' => 'PENDING_APPROVAL']) }}" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon brand"><i class="bi bi-piggy-bank"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ $pendingDepositsCount }}</div>
                    <div class="stat-label">Deposits to Verify (Audit Queue)</div>
                    <div class="text-primary small fw-semibold mt-1 d-flex align-items-center gap-1">
                        Open Verification Queue <i class="bi bi-arrow-right"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endif

    {{-- Pending Commissions --}}
    @if($canCommissions)
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.commissions.pending') }}" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-percent"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ $pendingCommissionsCount }}</div>
                    <div class="stat-label">Commissions Pending (Review Queue)</div>
                    <div class="text-muted small mt-1 num-tabular">
                        ₹{{ number_format($pendingCommissionsAmount, 2) }}
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endif

    {{-- Branch Leads --}}
    @if($canLeads)
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.leads.index') }}" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon brand"><i class="bi bi-briefcase"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ $branchLeadsCount }}</div>
                    <div class="stat-label">Customer Acquisition Pipeline</div>
                    <div class="text-primary small fw-semibold mt-1 d-flex align-items-center gap-1">
                        View Pipeline <i class="bi bi-arrow-right"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endif

    {{-- Today's Processed Operations --}}
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
            <div>
                <div class="stat-value num-tabular">{{ $todayProcessedCount }}</div>
                <div class="stat-label">Operations Processed Today</div>
                <div class="text-muted small mt-1">Audit trail logged</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Actionable Work Queues ────────────────────────────────────────── --}}
<div class="row g-4">
    {{-- Left Column: Commissions --}}
    <div class="col-lg-6">
        @if($canCommissions)
        <div class="card h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-percent text-warning"></i> Deposit Mobilization Commissions Pending Review
                </div>
                <a href="{{ route('admin.commissions.pending') }}" class="btn btn-sm btn-outline-warning text-dark">
                    Review Queue ({{ $pendingCommissionsCount }})
                </a>
            </div>
            <div class="card-body p-0">
                @if($pendingCommissionsList->count() > 0)
                <div class="table-responsive border-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Sourcing Advisor</th>
                                <th>Product / Deposit Scheme</th>
                                <th class="text-end">Payable Commission</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingCommissionsList as $comm)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $comm->agent?->name ?? '—' }}</div>
                                    <small class="text-muted">{{ $comm->agent?->member_code ?? $comm->agent?->agent_id }}</small>
                                </td>
                                <td>
                                    <div>{{ $comm->deposit?->scheme?->name ?? 'Deposit Commission' }}</div>
                                    <small class="text-muted">{{ $comm->created_at?->format('d M Y') }}</small>
                                </td>
                                <td class="text-end num-tabular">
                                    <span class="fw-bold text-success">₹{{ number_format($comm->commission_amount, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.commissions.show', $comm) }}" class="btn btn-sm btn-outline-dark">
                                        Review
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-check2-circle display-6 d-block mb-2 text-success"></i>
                    No pending commissions awaiting approval.
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Right Column: Deposits & Recent Activity --}}
    <div class="col-lg-6">
        @if($canDeposits)
        <div class="card mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-piggy-bank text-info"></i> Deposit Account Verification Queue (Maker-Checker)
                </div>
                <a href="{{ route('admin.deposits.index', ['status' => 'PENDING_APPROVAL']) }}" class="btn btn-sm btn-outline-info">
                    Audit Queue ({{ $pendingDepositsCount }})
                </a>
            </div>
            <div class="card-body p-0">
                @if($pendingDepositsList->count() > 0)
                <div class="table-responsive border-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Deposit Account No.</th>
                                <th>Primary Account Holder</th>
                                <th class="text-end">Principal Amount</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingDepositsList as $dep)
                            <tr>
                                <td>
                                    <span class="fw-bold font-monospace">{{ $dep->deposit_number ?? $dep->account_number }}</span>
                                    <div class="small text-muted">{{ $dep->scheme?->name ?? 'Deposit' }}</div>
                                </td>
                                <td>
                                    <div>{{ $dep->customer?->name ?? '—' }}</div>
                                    <small class="text-muted">{{ $dep->branch?->name }}</small>
                                </td>
                                <td class="text-end num-tabular">
                                    <span class="fw-bold text-dark">₹{{ number_format($dep->amount ?? $dep->deposit_amount, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.deposits.show', $dep) }}" class="btn btn-sm btn-dark">
                                        Audit & Verify
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-check-all display-6 d-block mb-2 text-success"></i>
                    No deposit applications currently waiting for staff verification!
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- My Recent Audit Trail --}}
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-secondary"></i> My Recent Activity Log
                </div>
            </div>
            <div class="card-body p-0">
                @if($recentActivities->count() > 0)
                <ul class="list-group list-group-flush">
                    @foreach($recentActivities as $act)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-bottom">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary font-monospace">{{ strtoupper($act->action) }}</span>
                                <span class="fw-medium text-dark">{{ $act->record_reference ?? $act->module }}</span>
                            </div>
                            <div class="text-muted small mt-1">
                                {{ Str::limit(is_array($act->details) ? json_encode($act->details) : ($act->details ?? $act->ip_address), 60) }}
                            </div>
                        </div>
                        <div class="text-muted small text-end">
                            {{ $act->created_at->diffForHumans() }}
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-journal-text display-6 d-block mb-2 text-muted"></i>
                    No activity records found for your account today.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
