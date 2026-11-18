@extends('superadmin.layouts.app')

@section('page-title', 'Agent Notifications')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-sm-center align-items-start gap-2 mb-3 mb-md-4">
    <div class="min-w-0">
        <h1 class="page-title text-break">Operational Notifications & Alerts</h1>
        <div class="page-breadcrumb">Agent / Notifications</div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap w-100 w-sm-auto justify-content-start justify-content-sm-end">
        <span class="badge bg-dark px-3 py-2 flex-grow-1 flex-sm-grow-0 text-center" style="font-size:0.8rem;">
            <i class="bi bi-bell-fill me-1 text-warning"></i>
            {{ $counts['total'] }} Actionable Items
        </span>
        <a href="{{ route('agent.dashboard') }}" class="btn btn-outline-dark btn-sm flex-grow-1 flex-sm-grow-0 text-center">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
        </a>
    </div>
</div>

{{-- ── Operational Stat Cards (Synced with Dashboard Design) ──────────────── --}}
<div class="row g-2 g-sm-3 mb-3 mb-md-4">
    <div class="col-12 col-sm-4">
        <a href="{{ route('agent.notifications', ['type' => 'all']) }}" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon black"><i class="bi bi-bell-fill"></i></div>
                <div class="min-w-0">
                    <div class="stat-value num-tabular">{{ $counts['total'] }}</div>
                    <div class="stat-label text-truncate">Total Alerts</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-4">
        <a href="{{ route('agent.notifications', ['type' => 'commissions']) }}" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon emerald"><i class="bi bi-cash-stack"></i></div>
                <div class="min-w-0">
                    <div class="stat-value num-tabular">{{ $counts['commissions'] }}</div>
                    <div class="stat-label text-truncate">Commissions</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-4">
        <a href="{{ route('agent.notifications', ['type' => 'career']) }}" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon dark"><i class="bi bi-award-fill"></i></div>
                <div class="min-w-0">
                    <div class="stat-value num-tabular">{{ $counts['career'] + $counts['notices'] }}</div>
                    <div class="stat-label text-truncate">Career & Notices</div>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- ── Minimal Filter Tabs (Swipeable on Mobile) ──────── --}}
<div class="mobile-chip-bar d-flex gap-2 mb-3 mb-md-4">
    <a href="{{ route('agent.notifications', ['type' => 'all']) }}"
       class="btn btn-sm text-nowrap {{ $typeFilter === 'all' ? 'btn-dark' : 'btn-outline-dark' }}">
        All Alerts ({{ $counts['total'] }})
    </a>
    <a href="{{ route('agent.notifications', ['type' => 'deposits']) }}"
       class="btn btn-sm text-nowrap {{ $typeFilter === 'deposits' ? 'btn-dark' : 'btn-outline-dark' }}">
        <i class="bi bi-piggy-bank me-1 text-info"></i> Deposits ({{ $counts['deposits'] }})
    </a>
    <a href="{{ route('agent.notifications', ['type' => 'commissions']) }}"
       class="btn btn-sm text-nowrap {{ $typeFilter === 'commissions' ? 'btn-dark' : 'btn-outline-dark' }}">
        <i class="bi bi-percent me-1 text-success"></i> Commissions ({{ $counts['commissions'] }})
    </a>
    <a href="{{ route('agent.notifications', ['type' => 'career']) }}"
       class="btn btn-sm text-nowrap {{ $typeFilter === 'career' ? 'btn-dark' : 'btn-outline-dark' }}">
        <i class="bi bi-award me-1 text-warning"></i> Career ({{ $counts['career'] }})
    </a>
    <a href="{{ route('agent.notifications', ['type' => 'notices']) }}"
       class="btn btn-sm text-nowrap {{ $typeFilter === 'notices' ? 'btn-dark' : 'btn-outline-dark' }}">
        <i class="bi bi-megaphone me-1 text-secondary"></i> Announcements ({{ $counts['notices'] }})
    </a>
</div>

{{-- ── Notification Data Tables (Synced Table Card Design) ──────────────── --}}
<div class="row g-3">

    {{-- 3. Deposit Updates --}}
    @if(in_array($typeFilter, ['all', 'deposits']) && $depositUpdates->isNotEmpty())
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-piggy-bank me-2 text-info"></i>Deposit Status Updates & Maturities</span>
                <span class="badge bg-light text-dark border">{{ $depositUpdates->count() }} updates</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle text-nowrap">
                        <thead>
                            <tr>
                                <th>Deposit #</th>
                                <th>Customer</th>
                                <th>Scheme</th>
                                <th>Amount</th>
                                <th>Maturity Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($depositUpdates as $dep)
                                <tr>
                                    <td class="font-monospace fw-semibold num-tabular">{{ $dep->deposit_number }}</td>
                                    <td>{{ $dep->customer?->name ?? '—' }}</td>
                                    <td>{{ $dep->scheme?->name ?? '—' }}</td>
                                    <td class="fw-bold num-tabular">₹{{ number_format((float)$dep->amount, 2) }}</td>
                                    <td>
                                        @if($dep->maturity_date)
                                            <span class="badge bg-light text-dark border num-tabular">
                                                {{ \Carbon\Carbon::parse($dep->maturity_date)->format('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $dep->getStatusBadgeClass() }}">{{ $dep->status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 4. Commission Updates --}}
    @if(in_array($typeFilter, ['all', 'commissions']) && $commissionUpdates->isNotEmpty())
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-percent me-2 text-success"></i>Commission Updates</span>
                <span class="badge bg-light text-dark border">{{ $commissionUpdates->count() }} updates</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle text-nowrap">
                        <thead>
                            <tr>
                                <th>Customer / Account</th>
                                <th>Period</th>
                                <th>Deposit #</th>
                                <th>Calculated Amount</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commissionUpdates as $comm)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $comm->customer?->name ?? 'Client Account' }}</div>
                                        <div class="text-muted num-tabular" style="font-size:0.72rem;">{{ $comm->commission_number }}</div>
                                    </td>
                                    <td class="num-tabular">{{ $comm->period }}</td>
                                    <td class="font-monospace num-tabular">{{ $comm->deposit?->deposit_number ?? '—' }}</td>
                                    <td class="fw-bold text-success num-tabular">₹{{ number_format((float)$comm->commission_amount, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $comm->getStatusBadgeClass() }}">{{ $comm->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.commissions.show', $comm) }}" class="btn btn-sm btn-outline-dark">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 5. Career & Promotion Requests --}}
    @if(in_array($typeFilter, ['all', 'career']) && $careerUpdates->isNotEmpty())
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-award me-2 text-warning"></i>Career & Promotion Requests</span>
                <span class="badge bg-light text-dark border">{{ $careerUpdates->count() }} requests</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle text-nowrap">
                        <thead>
                            <tr>
                                <th>Current Level</th>
                                <th>Proposed Level</th>
                                <th>Submitted Date</th>
                                <th>Remarks / Notes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($careerUpdates as $req)
                                <tr>
                                    <td>{{ $req->currentLevel?->name ?? 'Standard' }}</td>
                                    <td class="fw-bold text-dark">{{ $req->proposedLevel?->name ?? 'Next Level' }}</td>
                                    <td class="num-tabular">{{ $req->created_at->format('d M Y') }}</td>
                                    <td>
                                        <span class="text-muted small">{{ $req->remarks ?: '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $req->getStatusBadgeClass() }}">{{ $req->status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 6. Branch Announcements --}}
    @if(in_array($typeFilter, ['all', 'notices']) && $branchNotices->isNotEmpty())
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-megaphone me-2 text-secondary"></i>Branch Admin Announcements & Activity</span>
                <span class="badge bg-light text-dark border">{{ $branchNotices->count() }} notices</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle text-nowrap">
                        <thead>
                            <tr>
                                <th>Activity / Bulletin</th>
                                <th>Type</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchNotices as $notice)
                                <tr>
                                    <td class="fw-medium text-dark">{{ $notice->description }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ strtoupper(str_replace('_', ' ', $notice->event)) }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">{{ $notice->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Empty State --}}
    @php
        $hasItems = match($typeFilter) {
            'deposits' => $counts['deposits'] > 0,
            'commissions' => $counts['commissions'] > 0,
            'career' => $counts['career'] > 0,
            'notices' => $counts['notices'] > 0,
            default => ($counts['total'] > 0 || $counts['notices'] > 0),
        };
    @endphp

    @if(! $hasItems)
    <div class="col-12">
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="bi bi-bell-slash fs-1 d-block mb-2 text-muted"></i>
                <h6 class="fw-bold text-dark">No notifications at this time</h6>
                <p class="text-muted small mb-3">
                    @if($typeFilter !== 'all')
                        No active alerts found in the "{{ ucfirst($typeFilter) }}" category.
                    @else
                        You're completely up to date with deposits, commissions, and career alerts.
                    @endif
                </p>
                @if($typeFilter !== 'all')
                    <a href="{{ route('agent.notifications', ['type' => 'all']) }}" class="btn btn-outline-dark btn-sm">
                        <i class="bi bi-layers me-1"></i> View All Alerts
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
