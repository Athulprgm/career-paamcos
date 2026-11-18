@extends('superadmin.layouts.app')

@section('page-title', 'Agent Business Leads')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="page-title mb-1">
            @if($selectedAgent)
                Leads — {{ $selectedAgent->name }}
            @else
                Agent Business Leads
            @endif
        </h1>
        <div class="page-breadcrumb">
            @if($selectedAgent)
                People / <a href="{{ route('admin.agents.index') }}" style="color:inherit;">Agents</a> / <a href="{{ route('admin.agents.show', $selectedAgent) }}" style="color:inherit;">{{ $selectedAgent->name }}</a> / Business Leads
            @else
                Operations / Agent Leads
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($selectedAgent)
            <a href="{{ route('admin.agents.show', $selectedAgent) }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Agent Profile
            </a>
        @endif
        <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-people me-1"></i> View Agents
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Key Metrics Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.leads.index', array_filter(['agent_id' => request('agent_id'), 'branch_id' => request('branch_id')])) }}" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon dark"><i class="bi bi-briefcase-fill"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ number_format($statusCounts['all'] ?? 0) }}</div>
                    <div class="stat-label">Total Leads</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.leads.index', array_merge(request()->except('page'), ['status' => 'NEW'])) }}" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon emerald"><i class="bi bi-plus-circle-fill"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ number_format($statusCounts['new'] ?? 0) }}</div>
                    <div class="stat-label">New Leads</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.leads.index', array_merge(request()->except('page'), ['status' => 'INTERESTED'])) }}" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-telephone-inbound-fill"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ number_format(($statusCounts['contacted'] ?? 0) + ($statusCounts['interested'] ?? 0)) }}</div>
                    <div class="stat-label">In Follow-up / Interested</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.leads.index', array_merge(request()->except('page'), ['status' => 'CONVERTED'])) }}" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon teal"><i class="bi bi-person-check-fill"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ number_format($statusCounts['converted'] ?? 0) }}</div>
                    <div class="stat-label">Converted to Customers</div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-3 border">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.leads.index') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div style="min-width:200px; flex: 1 1 200px;">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search lead name, mobile, email…" value="{{ request('search') }}">
            </div>

            @if(is_null($restrictedBranchId))
            <div style="min-width:180px;">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div style="min-width:210px;">
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ (string) request('agent_id') === (string) $agent->id ? 'selected' : '' }}>
                            {{ $agent->name }} ({{ $agent->member_code ?? $agent->agent_id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width:165px;">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="NEW" {{ request('status') === 'NEW' ? 'selected' : '' }}>NEW</option>
                    <option value="CONTACTED" {{ request('status') === 'CONTACTED' ? 'selected' : '' }}>CONTACTED</option>
                    <option value="INTERESTED" {{ request('status') === 'INTERESTED' ? 'selected' : '' }}>INTERESTED</option>
                    <option value="CONVERTED" {{ request('status') === 'CONVERTED' ? 'selected' : '' }}>CONVERTED</option>
                    <option value="NOT INTERESTED" {{ request('status') === 'NOT INTERESTED' ? 'selected' : '' }}>NOT INTERESTED</option>
                </select>
            </div>

            <button type="submit" class="btn btn-dark btn-sm">
                <i class="bi bi-search me-1"></i> Filter
            </button>

            @if(request()->hasAny(['search', 'branch_id', 'agent_id', 'status']))
                <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-x me-1"></i> Clear
                </a>
            @endif
        </form>
    </div>
</div>

<!-- Leads Listing Table -->
<div class="card border">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Lead Name</th>
                        <th>Contact</th>
                        <th>Branch & Agent</th>
                        <th>Interested Scheme</th>
                        <th>Status</th>
                        <th>Follow-up</th>
                        <th>Added On</th>
                        <th class="text-end pe-3" style="width:130px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td class="text-muted small num-tabular">{{ $leads->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('admin.leads.show', $lead) }}" class="fw-semibold text-dark text-decoration-none">
                                    {{ $lead->name }}
                                </a>
                                @if($lead->isConverted() && $lead->convertedCustomer)
                                    <div class="small text-success">
                                        <i class="bi bi-check2-circle me-1"></i>Cust: <span class="num-tabular">{{ $lead->convertedCustomer->customer_number }}</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div><i class="bi bi-telephone text-muted me-1" style="font-size:0.75rem;"></i><span class="num-tabular">{{ $lead->mobile }}</span></div>
                                @if($lead->email)
                                    <div class="text-muted small"><i class="bi bi-envelope text-muted me-1" style="font-size:0.75rem;"></i>{{ $lead->email }}</div>
                                @endif
                            </td>
                            <td>
                                @if($lead->agent)
                                    <div>
                                        <a href="{{ route('admin.agents.show', $lead->agent) }}" class="text-decoration-none text-dark fw-medium">
                                            <i class="bi bi-person me-1 text-muted"></i>{{ $lead->agent->name }}
                                        </a>
                                    </div>
                                    <div class="text-muted small font-monospace">
                                        <span class="num-tabular">{{ $lead->agent->member_code ?? $lead->agent->agent_id }}</span>
                                        @if($lead->branch) &bull; {{ $lead->branch->name }} @endif
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $lead->interestedScheme?->name ?? 'General Inquiry' }}</div>
                                @if($lead->expected_amount)
                                    <div class="text-muted small num-tabular">₹{{ number_format($lead->expected_amount, 2) }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $lead->getStatusBadgeClass() }}">{{ $lead->status }}</span>
                            </td>
                            <td>
                                @if($lead->follow_up_date)
                                    <span class="small num-tabular {{ $lead->follow_up_date->isPast() && !$lead->isConverted() ? 'text-danger fw-semibold' : 'text-muted' }}">
                                        {{ $lead->follow_up_date->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-muted small num-tabular">
                                {{ $lead->created_at ? $lead->created_at->format('d M Y') : '—' }}
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <a href="{{ route('admin.deposits.create', ['lead_id' => $lead->id]) }}" class="btn btn-sm btn-primary" title="Create Deposit directly for this Lead">
                                        <i class="bi bi-wallet2 me-1"></i>Deposit
                                    </a>
                                    <a href="{{ route('admin.leads.show', $lead) }}" class="btn btn-sm btn-outline-dark" title="View Lead Details">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                                        <form action="{{ route('admin.leads.destroy', $lead) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Permanently delete lead {{ $lead->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Lead">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <div class="py-3">
                                    <i class="bi bi-briefcase fs-1 text-muted d-block mb-2"></i>
                                    <h6>No business leads found</h6>
                                    <p class="small text-muted mb-0">No leads match the selected filter criteria for this branch.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($leads->hasPages())
    <div class="card-footer bg-white py-2 px-3 border-top">
        {{ $leads->links() }}
    </div>
    @endif
</div>
@endsection
