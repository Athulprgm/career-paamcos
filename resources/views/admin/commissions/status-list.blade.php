@extends('superadmin.layouts.app')

@section('page-title', $pageTitle)

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="page-title mb-1">{{ $pageTitle }}</h1>
        <div class="page-breadcrumb text-muted">Commission / {{ $status }} Records</div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="{{ route('admin.commissions.overview') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Overview
        </a>
        <a href="{{ route('admin.commissions.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-list-check me-1"></i> All Commissions
        </a>
        @if(auth()->check() && auth()->user()->isSuperAdmin())
        <a href="{{ route('superadmin.fd-commissions.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-percent me-1"></i> Deposit Commissions
        </a>
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 py-2 px-3 small" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3 py-2 px-3 small" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ url()->current() }}" class="d-flex gap-2 flex-wrap align-items-center">
            <div style="min-width: 180px;" class="flex-grow-1">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search COM#, Deposit#, Agent, Customer…" value="{{ request('search') }}">
            </div>

            @if(is_null($restrictedBranchId))
            <div style="min-width: 180px;">
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

            @if(is_null($restrictedAgentId))
            <div style="min-width: 180px;">
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    @foreach($agents as $a)
                        <option value="{{ $a->id }}" {{ request('agent_id') == $a->id ? 'selected' : '' }}>
                            {{ $a->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div style="min-width: 150px;">
                <select name="period" class="form-select form-select-sm">
                    <option value="">Period</option>
                    @foreach($periods as $p)
                        <option value="{{ $p }}" {{ request('period') === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-dark btn-sm">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'branch_id', 'agent_id', 'period']))
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                    <i class="bi bi-x"></i>
                </a>
            @endif
        </form>
    </div>
</div>

<!-- Commissions Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-3" style="width: 150px;">Commission #</th>
                        <th style="width: 90px;">Year</th>
                        <th>Agent</th>
                        <th>Customer</th>
                        <th>Branch</th>
                        <th>Rate / Scheme</th>
                        <th class="text-end">Base (₹)</th>
                        <th class="text-end">Commission (₹)</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $groupedCommissions = $commissions->groupBy(function($comm) {
                            return $comm->deposit_id ? 'dep_' . $comm->deposit_id : 'standalone_' . $comm->id;
                        });
                    @endphp

                    @forelse($groupedCommissions as $groupKey => $groupItems)
                        @php
                            $firstComm = $groupItems->first();
                            $deposit = $firstComm->deposit;
                            $customer = $firstComm->customer ?? $deposit?->customer;
                            $agent = $firstComm->agent ?? $deposit?->agent;
                            $branch = $firstComm->branch ?? $deposit?->branch;
                            $scheme = $deposit?->scheme ?? $firstComm->scheme;
                            $tenureYears = $deposit ? $deposit->getTenureInYears() : ($groupItems->max('payout_year') ?: 1);
                            $depositAmount = $deposit ? $deposit->amount : $firstComm->base_amount;
                            $totalCommission = $groupItems->sum('calculated_amount');
                        @endphp

                        @if($deposit)
                            {{-- Minimal Deposit Header (Shows First: Deposit Details, Date & Time, Customer, Agent, Scheme, Principal & Total Commission) --}}
                            <tr class="table-light border-top" style="background-color: #fafbfc; border-top: 2px solid var(--border-color) !important;">
                                <td colspan="10" class="py-2 px-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ route('admin.deposits.show', $deposit) }}" class="fw-bold font-monospace text-dark text-decoration-none" style="font-size: 0.85rem;" title="View Deposit">
                                                <i class="bi bi-shield-check text-success me-1"></i>{{ $deposit->deposit_number }}
                                            </a>
                                            <span class="text-muted">&bull;</span>
                                            <span class="fw-semibold text-dark">{{ $scheme->name ?? 'Deposit' }}</span>
                                            <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">{{ $tenureYears }} Yr(s) Tenure</span>
                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                <i class="bi bi-clock me-1"></i>{{ ($deposit->created_at ?? $firstComm->created_at)->format('d M Y, h:i A') }}
                                            </span>
                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                &bull; Cust: <strong class="text-dark">{{ $customer->name ?? 'N/A' }}</strong>
                                            </span>
                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                &bull; Agent: <strong class="text-dark">{{ $agent->name ?? 'N/A' }}</strong>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3" style="font-size: 0.8rem;">
                                            <span class="text-muted">Deposit: <strong class="text-dark">₹{{ number_format($depositAmount, 2) }}</strong></span>
                                            <span class="text-muted">Total Comm: <strong class="text-success fw-bold">₹{{ number_format($totalCommission, 2) }}</strong></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            {{-- Year-wise Commission Rows Underneath the Main Deposit Row --}}
                            @foreach($groupItems->sortBy('payout_year') as $comm)
                            <tr>
                                <td class="ps-3 font-monospace">
                                    <a href="{{ route('admin.commissions.show', $comm) }}" class="text-dark text-decoration-none fw-semibold">
                                        {{ $comm->commission_number }}
                                    </a>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $comm->period }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white py-0 px-2" style="font-size: 0.75rem;">
                                        Year {{ $comm->payout_year ?? 1 }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $agent->name ?? 'N/A' }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $agent->member_code ?? $agent->agent_id ?? '' }}</div>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $customer->name ?? 'N/A' }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $customer->customer_number ?? '' }}</div>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $branch->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $comm->rate }}%</span>
                                    <span class="text-muted ms-1" style="font-size: 0.72rem;">{{ $scheme->name ?? 'Rate' }}</span>
                                </td>
                                <td class="text-end fw-medium text-dark">
                                    ₹{{ number_format($comm->base_amount, 2) }}
                                </td>
                                <td class="text-end fw-bold text-success">
                                    ₹{{ number_format($comm->calculated_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($status === 'PENDING')
                                        <span class="badge-pending">PENDING</span>
                                    @elseif($status === 'APPROVED')
                                        <span class="badge-approved">APPROVED</span>
                                    @elseif($status === 'PAYABLE')
                                        <span class="badge-payable">PAYABLE</span>
                                    @elseif($status === 'PAID')
                                        <span class="badge-paid">PAID</span>
                                    @else
                                        <span class="badge bg-light text-dark border">{{ $status }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('admin.commissions.show', $comm) }}" class="btn btn-outline-dark btn-sm py-0 px-2" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if($status === 'PENDING' && !auth()->user()->isAgent())
                                            <form action="{{ route('admin.commissions.approve', $comm) }}" method="POST" class="d-inline"
                                                  data-confirm="Approve Year {{ $comm->payout_year }} commission [{{ $comm->commission_number }}] for ₹{{ number_format($comm->calculated_amount, 2) }}?"
                                                  data-confirm-title="Approve Commission"
                                                  data-confirm-type="success"
                                                  data-confirm-btn="Approve">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm py-0 px-2" title="Approve Year {{ $comm->payout_year }}">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                        @elseif($status === 'APPROVED' && !auth()->user()->isAgent())
                                            <form action="{{ route('admin.commissions.mark-payable', $comm) }}" method="POST" class="d-inline"
                                                  data-confirm="Mark commission {{ $comm->commission_number }} as PAYABLE?"
                                                  data-confirm-title="Mark Commission as Payable"
                                                  data-confirm-type="success"
                                                  data-confirm-btn="Mark Payable">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm py-0 px-2" title="Mark as Payable">
                                                    <i class="bi bi-wallet2"></i>
                                                </button>
                                            </form>
                                        @elseif($status === 'PAYABLE' && !auth()->user()->isAgent())
                                            <a href="{{ route('admin.commissions.pay', $comm) }}" class="btn btn-success btn-sm py-0 px-2" title="Pay Now">
                                                <i class="bi bi-credit-card"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            {{-- Standalone Commission --}}
                            <tr>
                                <td class="ps-3 font-monospace">
                                    <a href="{{ route('admin.commissions.show', $firstComm) }}" class="text-dark text-decoration-none fw-semibold">
                                        {{ $firstComm->commission_number }}
                                    </a>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $firstComm->period }}</div>
                                </td>
                                <td>
                                    <span class="text-muted" style="font-size: 0.75rem;">—</span>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $agent->name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $customer->name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $branch->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $firstComm->rate }}%</span>
                                </td>
                                <td class="text-end fw-medium text-dark">
                                    ₹{{ number_format($firstComm->base_amount, 2) }}
                                </td>
                                <td class="text-end fw-bold text-success">
                                    ₹{{ number_format($firstComm->calculated_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $status }}</span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.commissions.show', $firstComm) }}" class="btn btn-outline-dark btn-sm py-0 px-2">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                No commission records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($commissions->hasPages())
    <div class="card-footer py-2 px-3 d-flex justify-content-between align-items-center">
        <span class="small text-muted">
            Showing {{ $commissions->firstItem() }} to {{ $commissions->lastItem() }} of {{ $commissions->total() }} records
        </span>
        <div>
            {{ $commissions->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
