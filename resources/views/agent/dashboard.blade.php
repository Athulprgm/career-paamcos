@extends('superadmin.layouts.app')

@section('page-title', 'Agent Operational Dashboard')

@section('content')
<!-- Mobile App Minimal & Premium Agent Header (< 768px) -->
<div class="d-md-none mb-3">
    <div class="agent-minimal-card">
        <!-- Top Agent Profile Row -->
        <div class="d-flex align-items-center gap-3">
            <div class="position-relative flex-shrink-0">
                @if($agent->profile_image_url)
                    <img src="{{ $agent->profile_image_url }}" alt="{{ $agent->name }}" class="agent-minimal-avatar object-fit-cover">
                @else
                    <div class="agent-minimal-avatar agent-minimal-avatar-text">
                        {{ strtoupper(substr($agent->name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-grow-1">
                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                    <span class="agent-minimal-code">
                        <i class="bi bi-shield-check me-1 text-success"></i>{{ $agent->member_code ?? $agent->agent_id }}
                    </span>
                    <span class="agent-minimal-rank">
                        <i class="bi bi-award me-1"></i>{{ $agent->rank?->name ?? 'Agent' }} &bull; Lvl {{ $agent->rank?->level ?? 1 }}
                    </span>
                </div>
                <h5 class="agent-minimal-name text-truncate mb-0">{{ ucwords($agent->name) }}</h5>
                <div class="agent-minimal-sub text-truncate">
                    <i class="bi bi-geo-alt me-1 text-primary"></i>Field Operations
                </div>
            </div>
        </div>

        <!-- 4 Minimal Quick Actions -->
        <div class="agent-minimal-actions">
            <a href="{{ route('agent.leads.create') }}" class="agent-action-chip primary">
                <div class="agent-action-icon">
                    <i class="bi bi-plus-lg"></i>
                </div>
                <span>Add Lead</span>
            </a>
            <a href="{{ route('agent.leads.index') }}" class="agent-action-chip">
                <div class="agent-action-icon">
                    <i class="bi bi-briefcase"></i>
                </div>
                <span>My Leads</span>
            </a>
            <a href="{{ route('agent.agents.index') }}" class="agent-action-chip">
                <div class="agent-action-icon">
                    <i class="bi bi-people"></i>
                </div>
                <span>My Team</span>
            </a>
            <a href="{{ route('agent.performance') }}" class="agent-action-chip">
                <div class="agent-action-icon">
                    <i class="bi bi-trophy"></i>
                </div>
                <span>Career</span>
            </a>
        </div>
    </div>
</div>

<!-- Desktop Page Header (Visible on Desktop >= 768px) -->
<div class="page-header d-none d-md-flex flex-wrap justify-content-between align-items-sm-center align-items-start gap-2 mb-3 mb-md-4">
    <div class="min-w-0">
        <h1 class="page-title mb-1 text-truncate">Agent Operational Dashboard</h1>
        <div class="page-breadcrumb text-muted text-break">
            <span>Agent Workspace</span> /
            <span class="text-dark fw-semibold">{{ $agent->name }}</span>
            <span class="badge bg-light text-secondary border ms-1">{{ $agent->member_code ?? $agent->agent_id }}</span>
            @if($agent->branch)
                / <i class="bi bi-building ms-1 me-1"></i>{{ $agent->branch->name }}
            @else
                / <span class="badge bg-light text-primary border ms-1"><i class="bi bi-geo-alt me-1"></i>Free Field</span>
            @endif
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 w-100 w-sm-auto">
        <a href="{{ route('agent.performance') }}" class="btn btn-outline-success btn-sm flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-award me-1"></i> Career Progression
        </a>
        <a href="{{ route('agent.leads.create') }}" class="btn btn-primary btn-sm flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-plus-lg me-1"></i> Add Lead
        </a>
        <a href="{{ route('agent.leads.index') }}" class="btn btn-outline-dark btn-sm flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-briefcase me-1"></i> View My Leads
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 mb-md-4 border" role="alert">
    <i class="bi bi-check2 me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Key Operational Metrics -->
<div class="row g-2 g-sm-3 mb-3 mb-md-4">
    <!-- Total Business Leads -->
    <div class="col-6 col-xl-3">
        <a href="{{ route('agent.leads.index') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-top-row d-flex align-items-center justify-content-between w-100 mb-2">
                    <div class="stat-icon brand"><i class="bi bi-briefcase"></i></div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle small d-md-none" style="font-size: 0.65rem; padding: 2px 6px;">
                        +{{ number_format($newLeadsCount) }} new
                    </span>
                </div>
                <div class="min-w-0 flex-grow-1 w-100">
                    <div class="stat-value num-tabular">{{ number_format($myLeadsCount) }}</div>
                    <div class="stat-label text-truncate">My Business Leads <span class="d-none d-md-inline text-muted">(+{{ number_format($newLeadsCount) }} new)</span></div>
                </div>
            </div>
        </a>
    </div>

    <!-- Converted Leads -->
    <div class="col-6 col-xl-3">
        <a href="{{ route('agent.leads.index') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-top-row d-flex align-items-center justify-content-between w-100 mb-2">
                    <div class="stat-icon brand"><i class="bi bi-check2-circle"></i></div>
                    <span class="badge bg-light text-secondary border small d-md-none" style="font-size: 0.65rem; padding: 2px 6px;">
                        Active
                    </span>
                </div>
                <div class="min-w-0 flex-grow-1 w-100">
                    <div class="stat-value text-success num-tabular">{{ number_format($convertedLeadsCount) }}</div>
                    <div class="stat-label text-truncate">Converted Leads</div>
                </div>
            </div>
        </a>
    </div>

    <!-- Pending Commission -->
    <div class="col-6 col-xl-3">
        <a href="{{ route('admin.commissions.overview') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-top-row d-flex align-items-center justify-content-between w-100 mb-2">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle small d-md-none" style="font-size: 0.65rem; padding: 2px 6px;">
                        {{ number_format($pendingCommissionCount) }} txns
                    </span>
                </div>
                <div class="min-w-0 flex-grow-1 w-100">
                    <div class="stat-value text-warning-emphasis num-tabular">₹{{ number_format($pendingCommissionAmount, 2) }}</div>
                    <div class="stat-label text-truncate">Pending (<span class="fw-semibold">{{ number_format($pendingCommissionCount) }}</span>)</div>
                </div>
            </div>
        </a>
    </div>

    <!-- Approved / Total Earned Commission -->
    <div class="col-6 col-xl-3">
        <a href="{{ route('admin.commissions.overview') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-top-row d-flex align-items-center justify-content-between w-100 mb-2">
                    <div class="stat-icon brand"><i class="bi bi-cash-coin"></i></div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle small d-md-none" style="font-size: 0.65rem; padding: 2px 6px;">
                        {{ number_format($approvedCommissionCount) }} paid
                    </span>
                </div>
                <div class="min-w-0 flex-grow-1 w-100">
                    <div class="stat-value text-success num-tabular">₹{{ number_format($totalEarnedCommission, 2) }}</div>
                    <div class="stat-label text-truncate">Earned (<span class="fw-semibold">{{ number_format($approvedCommissionCount) }}</span>)</div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Commissions Ledger & Status -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <i class="bi bi-coin text-warning fs-5 flex-shrink-0"></i>
                    <h6 class="card-title m-0 fw-bold text-truncate">My Commissions &amp; Status</h6>
                </div>
                <a href="{{ route('admin.commissions.overview') }}" class="btn btn-sm btn-outline-dark text-nowrap">View Overview</a>
            </div>
            <div class="card-body p-0">
                @if($recentCommissions->isNotEmpty())
                <!-- Desktop Table View -->
                <div class="d-none d-md-block table-responsive border-0">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th>Commission #</th>
                                <th>Deposit / Lead</th>
                                <th class="text-end">Base Amount</th>
                                <th class="text-center">Rate</th>
                                <th class="text-end">Commission</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentCommissions as $comm)
                            <tr>
                                <td>
                                    <span class="fw-bold font-monospace small text-dark">{{ $comm->commission_number }}</span>
                                    <div class="text-muted" style="font-size:0.75rem;">{{ $comm->created_at->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small text-truncate" style="max-width: 160px;">
                                        {{ $comm->deposit?->lead?->name ?? $comm->customer?->name ?? 'Lead' }}
                                    </div>
                                    <div class="text-muted" style="font-size:0.75rem;">
                                        Dep: {{ $comm->deposit?->deposit_number ?? '—' }}
                                    </div>
                                </td>
                                <td class="text-end num-tabular small">₹{{ number_format($comm->base_amount, 2) }}</td>
                                <td class="text-center num-tabular small fw-semibold text-success">{{ number_format($comm->rate, 2) }}%</td>
                                <td class="text-end num-tabular fw-bold text-dark">₹{{ number_format($comm->commission_amount, 2) }}</td>
                                <td>
                                    <span class="{{ $comm->getStatusBadgeClass() }}">
                                        {{ $comm->status }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card Feed View -->
                <div class="d-block d-md-none divide-y">
                    @foreach($recentCommissions as $comm)
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between gap-2">
                        <div class="min-w-0 flex-grow-1" style="min-width: 0;">
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span class="badge bg-light text-dark font-monospace border" style="font-size: 0.65rem;">{{ $comm->commission_number }}</span>
                                <span class="{{ $comm->getStatusBadgeClass() }}" style="font-size: 0.65rem;">{{ $comm->status }}</span>
                            </div>
                            <div class="fw-bold text-dark text-truncate" style="font-size: 0.85rem;">
                                {{ $comm->deposit?->lead?->name ?? $comm->customer?->name ?? 'Lead Deposit' }}
                            </div>
                            <div class="text-muted num-tabular" style="font-size: 0.72rem;">
                                Base: ₹{{ number_format($comm->base_amount, 2) }} &bull; {{ $comm->created_at->format('d M Y') }}
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="fw-bold text-success num-tabular" style="font-size: 0.95rem;">₹{{ number_format($comm->commission_amount, 2) }}</div>
                            <span class="badge bg-success-subtle text-success small num-tabular" style="font-size: 0.7rem;">{{ number_format($comm->rate, 2) }}%</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-5 text-muted p-3">
                    <i class="bi bi-cash-stack fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <p class="mb-1 fw-semibold">No commission records yet.</p>
                    <small>When deposits are booked from your leads, your commission will reflect here automatically.</small>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- My Recent Leads -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <i class="bi bi-briefcase text-primary fs-5 flex-shrink-0"></i>
                    <h6 class="card-title m-0 fw-bold text-truncate">My Recent Leads</h6>
                </div>
                <a href="{{ route('agent.leads.index') }}" class="btn btn-sm btn-outline-secondary text-nowrap">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentLeads->isNotEmpty())
                <!-- Desktop Table View -->
                <div class="d-none d-md-block table-responsive border-0">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th>Lead Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentLeads as $lead)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark small text-truncate" style="max-width: 140px;">{{ $lead->name }}</div>
                                    <div class="text-muted" style="font-size:0.75rem;">
                                        <i class="bi bi-geo-alt text-danger me-1"></i>{{ $lead->branch?->name ?? '—' }} &bull; {{ $lead->interestedScheme?->name ?? 'General Inquiry' }}
                                    </div>
                                </td>
                                <td class="small num-tabular">
                                    {{ $lead->mobile }}
                                </td>
                                <td>
                                    <span class="{{ $lead->getStatusBadgeClass() }}">{{ $lead->status }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('agent.leads.show', $lead) }}" class="btn btn-sm btn-outline-dark py-0 px-2" style="font-size:0.75rem;">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Lead Cards -->
                <div class="d-block d-md-none divide-y">
                    @foreach($recentLeads as $lead)
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <div class="bg-dark text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                {{ strtoupper(substr($lead->name, 0, 1)) }}
                            </div>
                            <div class="flex-grow-1 min-w-0" style="min-width: 0;">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <a href="{{ route('agent.leads.show', $lead) }}" class="fw-bold text-dark text-decoration-none text-truncate" style="font-size: 0.88rem;">
                                        {{ $lead->name }}
                                    </a>
                                    <span class="{{ $lead->getStatusBadgeClass() }} flex-shrink-0" style="font-size: 0.68rem; padding: 2px 6px;">{{ $lead->status }}</span>
                                </div>
                                <div class="text-muted text-truncate" style="font-size: 0.73rem;">
                                    <i class="bi bi-geo-alt text-danger me-1"></i>{{ $lead->branch?->name ?? '—' }} &bull; {{ $lead->interestedScheme?->name ?? 'General Inquiry' }}
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-2 pt-1">
                            <a href="tel:{{ $lead->mobile }}" class="btn btn-outline-dark btn-sm py-1 px-2 d-inline-flex align-items-center gap-1 text-truncate" style="font-size: 0.75rem; max-width: 52%;">
                                <i class="bi bi-telephone text-success flex-shrink-0"></i>
                                <span class="text-truncate num-tabular">{{ $lead->mobile }}</span>
                            </a>
                            <a href="{{ route('agent.leads.show', $lead) }}" class="btn btn-primary btn-sm py-1 px-3 d-inline-flex align-items-center gap-1 flex-shrink-0" style="font-size: 0.75rem;">
                                <span>Details</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-5 text-muted p-3">
                    <i class="bi bi-briefcase fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <p class="mb-1 fw-semibold">No leads added yet.</p>
                    <a href="{{ route('agent.leads.create') }}" class="btn btn-sm btn-primary mt-2">
                        <i class="bi bi-plus-lg me-1"></i> Add Your First Lead
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
