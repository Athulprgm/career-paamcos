@extends('superadmin.layouts.app')

@section('page-title', 'Term & Recurring Deposit Accounts')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Term & Recurring Deposit Accounts</h1>
        <div class="page-breadcrumb">Deposit Operations / All Deposit Accounts</div>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->check() && !auth()->user()->isAgent())
        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('view-deposits') || auth()->user()->hasPermission('manage-deposits'))
        <a href="{{ route('admin.deposits.history') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-clock-history me-1"></i> Audit Trail Log
        </a>
        @endif
        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('create-deposits'))
        <a href="{{ route('admin.deposits.create') }}" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Open New Deposit Account
        </a>
        @endif
        @endif
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.deposits.index') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <!-- Search -->
            <div style="min-width:200px;">
                <label class="form-label text-muted small mb-1">Search Accounts</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="A/C #, customer, advisor..." value="{{ request('search') }}">
            </div>

            <!-- Branch (for superadmin or multi-branch) -->
            @if(is_null($restrictedBranchId))
            <div style="min-width:180px;">
                <label class="form-label text-muted small mb-1">Operating Branch</label>
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

            <!-- Agent -->
            @if(is_null($restrictedAgentId))
            <div style="min-width:180px;">
                <label class="form-label text-muted small mb-1">Sourcing Advisor</label>
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Sourcing Advisors</option>
                    @foreach($agents as $ag)
                        @php
                            $agRank = $ag->rank?->name ?? $ag->careerLevel?->name;
                        @endphp
                        <option value="{{ $ag->id }}" {{ request('agent_id') == $ag->id ? 'selected' : '' }}>
                            {{ $ag->name }}@if($agRank) [{{ $agRank }}]@endif
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Scheme -->
            <div style="min-width:200px;">
                <label class="form-label text-muted small mb-1">Deposit Product / Scheme</label>
                <select name="scheme_id" class="form-select form-select-sm">
                    <option value="">All Schemes</option>
                    @foreach(($schemes ?? []) as $sch)
                        <option value="{{ $sch->id }}" {{ request('scheme_id') == $sch->id ? 'selected' : '' }}>
                            {{ $sch->code }} - {{ $sch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div style="min-width:175px;">
                <label class="form-label text-muted small mb-1">Account Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                    <option value="PENDING_APPROVAL" {{ request('status') === 'PENDING_APPROVAL' ? 'selected' : '' }}>PENDING APPROVAL</option>
                    <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                    <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                    <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                </select>
            </div>

            <!-- Date Range -->
            <div style="min-width:130px;">
                <label class="form-label text-muted small mb-1">Opening Date (From)</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
            </div>
            <div style="min-width:130px;">
                <label class="form-label text-muted small mb-1">Opening Date (To)</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
            </div>

            <div>
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-search me-1"></i> Apply Filter
                </button>
                <a href="{{ route('admin.deposits.index') }}" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Reset Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 140px;">Account No.</th>
                        <th>Primary Account Holder</th>
                        <th>Operating Branch</th>
                        <th>Sourcing Advisor</th>
                        <th>Deposit Product / Plan</th>
                        <th class="text-end" style="width: 130px;">Principal (₹)</th>
                        <th style="width: 110px;">Value Date</th>
                        <th style="width: 110px;">Maturity Date</th>
                        <th style="width: 120px;">Account Status</th>
                        <th class="text-end" style="width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $dep)
                        <tr>
                            <td class="fw-bold">
                                <a href="{{ route('admin.deposits.show', $dep) }}" class="text-dark text-decoration-none">
                                    {{ $dep->deposit_number }}
                                </a>
                            </td>
                            <td>
                                @if($dep->customer)
                                    <span class="text-dark fw-medium">
                                        {{ $dep->customer->name }}
                                    </span>
                                    <small class="text-muted d-block">{{ $dep->customer->customer_number }}</small>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-secondary">{{ $dep->branch?->name ?? 'N/A' }}</small>
                            </td>
                            <td>
                                @if($dep->agent)
                                    <a href="{{ route('admin.agents.show', $dep->agent) }}" class="fw-semibold text-dark text-decoration-none" title="View Sourcing Advisor Profile">
                                        {{ $dep->agent->name }}
                                    </a>
                                    <div class="d-flex align-items-center gap-1 mt-0.5">
                                        <span class="text-muted" style="font-size:0.75rem;">{{ $dep->agent->member_code ?? $dep->agent->agent_id }}</span>
                                        <a href="{{ route('admin.targets.agents.show', $dep->agent) }}" class="text-success text-decoration-none" title="View Target Progress">
                                            <i class="bi bi-crosshair" style="font-size:0.75rem;"></i>
                                        </a>
                                    </div>
                                @else
                                    <small class="text-secondary">N/A</small>
                                @endif
                            </td>
                            <td>
                                <div><span class="badge bg-light text-dark border">{{ $dep->scheme?->code ?? 'N/A' }}</span></div>
                                <small class="badge bg-secondary-subtle text-secondary border mt-1">{{ $dep->deposit_type ?? $dep->scheme?->scheme_type ?? 'FD' }}</small>
                                @if($dep->isKsp())
                                    @php
                                        $rowKsp = $dep->getKspDetails();
                                    @endphp
                                    <div>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle mt-1">
                                            <i class="bi bi-shield-check me-1"></i>KSP &bull; Ins: ₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($rowKsp['insurance_coverage'] ?? 300000, 0) }}
                                        </span>
                                    </div>
                                @endif
                                @if($dep->isDouble())
                                    <div><span class="badge bg-warning-subtle text-dark border border-warning mt-1"><i class="bi bi-stars text-warning me-1"></i>2x Doubling</span></div>
                                @endif
                                @if($dep->isSeniorCitizen())
                                    <div><span class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1"><i class="bi bi-person-heart me-1"></i>Senior Citizen</span></div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="fw-bold num-tabular">₹{{ number_format($dep->amount, 2) }}{{ $dep->isKsp() ? '/yr' : '' }}</div>
                                @if($dep->maturity_amount)
                                    <small class="text-success d-block num-tabular" style="font-size:0.75rem;">{{ $dep->isKsp() ? 'Total Return:' : 'Maturity:' }} ₹{{ number_format($dep->maturity_amount, 2) }}</small>
                                @endif
                            </td>
                            <td class="num-tabular text-nowrap">
                                <small>{{ $dep->start_date?->format('d M Y') ?? 'N/A' }}</small>
                            </td>
                            <td class="num-tabular text-nowrap">
                                <small class="fw-semibold">{{ $dep->maturity_date?->format('d M Y') ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <span class="{{ $dep->getStatusBadgeClass() }}">
                                    {{ str_replace('_', ' ', $dep->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.deposits.show', $dep) }}" class="btn btn-sm btn-outline-dark" title="View Account Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasPermission('manage-deposits')))
                                        @if($dep->isPendingApproval())
                                            <form action="{{ route('admin.deposits.approve', $dep) }}" method="POST" class="d-inline"
                                                  data-confirm="Approve deposit account {{ $dep->deposit_number }}?"
                                                  data-confirm-title="Approve Deposit Account"
                                                  data-confirm-type="success"
                                                  data-confirm-btn="Approve Deposit Account">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Approve Deposit Account">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                                No deposit accounts found matching the specified criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($deposits->hasPages())
    <div class="card-footer bg-white border-top py-2 px-3">
        {{ $deposits->links() }}
    </div>
    @endif
</div>
@endsection