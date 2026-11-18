@extends('superadmin.layouts.app')

@section('page-title', 'Branch Notifications & Alerts')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Notifications & Operational Alerts</h1>
        <div class="page-breadcrumb">
            Branch Admin /
            @if(auth()->user()->branch)
                {{ auth()->user()->branch->name }} /
            @endif
            Notifications
        </div>
    </div>
    <div>
        <span class="badge bg-dark px-3 py-2" style="font-size:0.8rem;">
            <i class="bi bi-bell-fill me-1 text-warning"></i>
            {{ $counts['total'] }} Actionable Items
        </span>
    </div>
</div>

{{-- ── Filter Tabs ───────────────────────────────────────────────────────── --}}
<div class="d-flex gap-2 flex-wrap mb-4">
    <a href="{{ route('branch-admin.notifications', ['type' => 'all']) }}"
       class="btn btn-sm {{ $typeFilter === 'all' ? 'btn-dark' : 'btn-outline-dark' }}">
        All Alerts ({{ $counts['total'] }})
    </a>
    <a href="{{ route('branch-admin.notifications', ['type' => 'maturities']) }}"
       class="btn btn-sm {{ $typeFilter === 'maturities' ? 'btn-dark' : 'btn-outline-dark' }}">
        <i class="bi bi-hourglass-bottom me-1 text-info"></i> Upcoming Maturities ({{ $counts['maturities'] }})
    </a>
    <a href="{{ route('branch-admin.notifications', ['type' => 'commissions']) }}"
       class="btn btn-sm {{ $typeFilter === 'commissions' ? 'btn-dark' : 'btn-outline-dark' }}">
        <i class="bi bi-percent me-1 text-primary"></i> Commission Updates ({{ $counts['commissions'] }})
    </a>
    <a href="{{ route('branch-admin.notifications', ['type' => 'promotions']) }}"
       class="btn btn-sm {{ $typeFilter === 'promotions' ? 'btn-dark' : 'btn-outline-dark' }}">
        <i class="bi bi-award-fill me-1 text-success"></i> Promotion Requests ({{ $counts['promotions'] }})
    </a>
    <a href="{{ route('branch-admin.notifications', ['type' => 'notices']) }}"
       class="btn btn-sm {{ $typeFilter === 'notices' ? 'btn-dark' : 'btn-outline-dark' }}">
        <i class="bi bi-info-circle-fill me-1 text-secondary"></i> Admin Notices ({{ $counts['notices'] }})
    </a>
</div>

<div class="row g-3">
    {{-- ── 1. Upcoming Deposit Maturities ─────────────────────────────────── --}}
    @if(in_array($typeFilter, ['all', 'maturities']) && $maturingDeposits->isNotEmpty())
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-hourglass-split me-2 text-info"></i>Upcoming Deposit Maturities (Next 30 Days)</span>
                <span class="badge-matured">{{ $maturingDeposits->count() }} maturing</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Deposit #</th>
                                <th>Customer</th>
                                <th>Scheme</th>
                                <th>Principal</th>
                                <th>Maturity Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($maturingDeposits as $deposit)
                                <tr>
                                    <td class="font-monospace fw-semibold">{{ $deposit->deposit_number }}</td>
                                    <td>{{ $deposit->customer?->name ?? '—' }}</td>
                                    <td>{{ $deposit->scheme?->name ?? '—' }}</td>
                                    <td class="fw-bold">₹{{ number_format((float)$deposit->amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $deposit->maturity_date?->format('d M Y') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.deposits.show', $deposit) }}" class="btn btn-sm btn-outline-dark">
                                            <i class="bi bi-eye me-1"></i> View Details
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

    {{-- ── 4. Commission Updates ─────────────────────────────────────────── --}}
    @if(in_array($typeFilter, ['all', 'commissions']) && $pendingCommissions->isNotEmpty())
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-percent me-2 text-primary"></i>Commission Action Needed</span>
                <span class="badge-pending">{{ $pendingCommissions->count() }} pending</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingCommissions as $commission)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $commission->agent?->name ?? '—' }}</div>
                                        <div class="text-muted" style="font-size:0.72rem;">{{ $commission->commission_number }}</div>
                                    </td>
                                    <td class="fw-bold">₹{{ number_format((float)$commission->commission_amount, 2) }}</td>
                                    <td>
                                        @if($commission->status === 'PENDING')
                                            <span class="badge-pending">Pending Approval</span>
                                        @elseif($commission->status === 'PAYABLE')
                                            <span class="badge-payable">Payable</span>
                                        @else
                                            <span class="badge bg-light text-dark border">{{ $commission->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.commissions.show', $commission) }}" class="btn btn-sm btn-outline-dark">
                                            Review
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

    {{-- ── 5. Promotion Requests ─────────────────────────────────────────── --}}
    @if(in_array($typeFilter, ['all', 'promotions']) && $promotionRequests->isNotEmpty())
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-award me-2 text-success"></i>Agent Promotion Requests</span>
                <span class="badge-pending">{{ $promotionRequests->count() }} pending</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Proposed Level</th>
                                <th>Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($promotionRequests as $req)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $req->agent?->name ?? '—' }}</div>
                                        <div class="text-muted" style="font-size:0.72rem;">{{ $req->request_number }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $req->proposedLevel?->name ?? 'Next Level' }}
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size:0.8rem;">
                                        {{ $req->request_date?->format('d M Y') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.career.requests.show', $req) }}" class="btn btn-sm btn-dark">
                                            Review
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

    {{-- ── 6. Admin Notices ──────────────────────────────────────────────── --}}
    @if(in_array($typeFilter, ['all', 'notices']) && $adminNotices->isNotEmpty())
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-info-circle me-2 text-secondary"></i>Recent Branch Activity & Notices</span>
                <span class="badge bg-light text-dark border">{{ $adminNotices->count() }} logs</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Description</th>
                                <th>Operator</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($adminNotices as $notice)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace" style="font-size:0.7rem;">
                                            {{ str_replace('_', ' ', $notice->event) }}
                                        </span>
                                    </td>
                                    <td>{{ $notice->description }}</td>
                                    <td class="text-muted" style="font-size:0.8rem;">
                                        {{ $notice->user_name ?? 'System' }}
                                    </td>
                                    <td class="text-muted" style="font-size:0.78rem;white-space:nowrap;">
                                        {{ $notice->created_at?->diffForHumans() }}
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

    {{-- Empty State --}}
    @if($counts['total'] === 0 && $adminNotices->isEmpty())
    <div class="col-12">
        <div class="card py-5 text-center">
            <div class="card-body">
                <i class="bi bi-check-circle text-success" style="font-size:2.5rem;"></i>
                <h5 class="mt-3">All clear! No pending alerts for this branch.</h5>
                <p class="text-muted" style="font-size:0.85rem;">
                    There are no urgent maturities, pending promotions, or commission actions requiring your attention right now.
                </p>
                <a href="{{ route('branch-admin.dashboard') }}" class="btn btn-dark btn-sm">
                    <i class="bi bi-speedometer2 me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
