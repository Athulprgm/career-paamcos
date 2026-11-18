@extends('superadmin.layouts.app')

@section('page-title', 'Agent Management')

@section('content')
<style>
    /* ─── Enterprise Swiss Design System: Agent Management ─── */
    .agent-tree-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding: 8px 4px 16px 4px;
    }
    .agent-tree {
        list-style: none;
        margin: 0;
        padding: 0;
        min-width: 640px;
    }
    .agent-tree-item {
        position: relative;
        padding: 6px 0;
        list-style: none;
    }

    /* Nested children container */
    .agent-tree-children {
        list-style: none;
        margin: 0 0 0 28px;
        padding: 0 0 0 24px;
        position: relative;
        border-left: 2px solid var(--border-color, #E2E8E4);
        margin-top: 6px;
    }

    /* Horizontal branch connector to each child node */
    .agent-tree-children > .agent-tree-item::before {
        content: "";
        position: absolute;
        left: -24px;
        top: 32px;
        width: 24px;
        height: 2px;
        background: var(--border-color, #E2E8E4);
    }

    /* Agent card container */
    .agent-node {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border: 1px solid var(--border-color, #E2E8E4);
        border-radius: var(--radius-lg, 12px);
        background: var(--card-bg, #FFFFFF);
        box-shadow: var(--shadow-card);
        transition: transform 0.18s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.18s ease, border-color 0.18s ease;
    }
    .agent-node:hover {
        border-color: var(--color-primary, #14532D);
        transform: translateY(-1px);
        box-shadow: var(--shadow-hover);
    }
    .agent-node.is-inactive {
        opacity: 0.75;
    }

    /* Expand / Collapse Toggle button */
    .agent-tree-toggle {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: 1px solid var(--border-color, #E2E8E4);
        background: var(--surface-subtle, #F1F5F3);
        color: var(--text-heading, #0F172A);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        cursor: pointer;
        padding: 0;
        flex-shrink: 0;
        transition: all 0.15s ease;
    }
    .agent-tree-toggle:hover {
        background: var(--color-primary-light, #ECFDF5);
        color: var(--color-primary, #14532D);
        border-color: var(--color-primary-border, #A7F3D0);
    }
    .agent-tree-toggle.is-collapsed i {
        transform: rotate(-90deg);
    }
    .agent-tree-toggle i {
        transition: transform 0.2s ease;
    }
    .agent-tree-toggle-spacer {
        width: 28px;
        flex-shrink: 0;
    }

    /* Avatars */
    .agent-node-avatar {
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #14532D 0%, #0F3F22 100%);
        color: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.95rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    }
    .agent-table-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #14532D 0%, #0F3F22 100%);
        color: #FFFFFF;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
        flex-shrink: 0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .agent-name-link {
        color: var(--text-heading, #0F172A);
        font-weight: 600;
        transition: color 0.15s ease;
    }
    .agent-name-link:hover {
        color: var(--color-primary, #14532D);
    }
    html.dark .agent-name-link {
        color: #F8FAFC !important;
    }
    html.dark .agent-name-link:hover {
        color: #34D399 !important;
    }

    .agent-node-main {
        flex: 1;
        min-width: 0;
    }
    .agent-node-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 14px;
        font-size: 0.78rem;
        color: var(--text-muted, #64748B);
    }

    /* Dark Mode Overrides */
    html.dark .agent-node {
        background: var(--card-bg, #111A15);
        border-color: var(--border-color, #1F3027);
    }
    html.dark .agent-tree-children {
        border-left-color: var(--border-color, #1F3027);
    }
    html.dark .agent-tree-children > .agent-tree-item::before {
        background: var(--border-color, #1F3027);
    }
    html.dark .agent-tree-toggle {
        background: rgba(255, 255, 255, 0.05);
        border-color: var(--border-color, #1F3027);
        color: #E2E8F0;
    }

    /* KPI interactive cards */
    .agent-kpi-link {
        text-decoration: none !important;
        color: inherit;
        display: block;
        height: 100%;
    }
    .agent-kpi-link .stat-card {
        cursor: pointer;
    }
    .agent-kpi-link.is-active .stat-card {
        border-color: var(--color-primary, #14532D) !important;
        box-shadow: 0 0 0 2px var(--color-primary-ring, rgba(20, 83, 45, 0.25));
        background: var(--color-primary-light, #ECFDF5);
    }
    html.dark .agent-kpi-link.is-active .stat-card {
        background: rgba(16, 185, 129, 0.08);
        border-color: #10B981 !important;
    }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Agent Management</h1>
        <div class="page-breadcrumb">People & Network / Agents Directory</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <!-- View Mode Switcher -->
        <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
            <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}"
               class="btn {{ $viewMode === 'table' ? 'btn-dark' : 'btn-outline-secondary' }}"
               title="Directory table view">
                <i class="bi bi-table me-1"></i> Table View
            </a>
            <a href="{{ request()->fullUrlWithQuery(['view' => 'tree']) }}"
               class="btn {{ $viewMode === 'tree' ? 'btn-dark' : 'btn-outline-secondary' }}"
               title="Hierarchy tree view">
                <i class="bi bi-diagram-3 me-1"></i> Hierarchy Tree
            </a>
        </div>

        @if(auth()->user()->isSuperAdmin() || auth()->user()->isBranchAdmin())
        <a href="{{ route('admin.agents.create') }}" class="btn btn-dark btn-sm">
            <i class="bi bi-person-plus-fill me-1"></i> Add Agent
        </a>
        @endif
    </div>
</div>

<!-- Key Performance Metric Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.agents.index', array_merge(request()->except(['status', 'page']), ['view' => $viewMode])) }}"
           class="agent-kpi-link {{ !request('status') ? 'is-active' : '' }}" title="View all agents">
            <div class="stat-card">
                <div class="stat-icon dark"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ number_format($totalCount ?? 0) }}</div>
                    <div class="stat-label">Total Agents</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.agents.index', array_merge(request()->except(['status', 'page']), ['status' => 'active', 'view' => $viewMode])) }}"
           class="agent-kpi-link {{ request('status') === 'active' ? 'is-active' : '' }}" title="Filter active agents">
            <div class="stat-card">
                <div class="stat-icon emerald"><i class="bi bi-person-check-fill"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ number_format($activeCount ?? 0) }}</div>
                    <div class="stat-label">Active Agents</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.agents.index', array_merge(request()->except(['status', 'page']), ['status' => 'pending', 'view' => $viewMode])) }}"
           class="agent-kpi-link {{ request('status') === 'pending' ? 'is-active' : '' }}" title="Filter pending verification">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ number_format($pendingCount ?? 0) }}</div>
                    <div class="stat-label">Pending Verification</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.agents.index', array_merge(request()->except(['status', 'page']), ['status' => 'inactive', 'view' => $viewMode])) }}"
           class="agent-kpi-link {{ in_array(request('status'), ['inactive', 'rejected']) ? 'is-active' : '' }}" title="Filter inactive & rejected agents">
            <div class="stat-card">
                <div class="stat-icon black"><i class="bi bi-person-dash-fill"></i></div>
                <div>
                    <div class="stat-value num-tabular">{{ number_format($inactiveCount ?? 0) }}</div>
                    <div class="stat-label">Inactive / Rejected</div>
                </div>
            </div>
        </a>
    </div>
</div>

@if(($pendingCount ?? 0) > 0 && auth()->user()->isSuperAdmin())
<div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between p-3 mb-3 border-warning gap-2">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-shield-exclamation fs-4 text-warning"></i>
        <div>
            <div class="fw-bold"><span class="num-tabular">{{ $pendingCount }}</span> Recruited Agent{{ $pendingCount > 1 ? 's' : '' }} Pending Verification</div>
            <div class="small text-muted">Agents added by other agents require Super Admin verification and acceptance before account activation.</div>
        </div>
    </div>
    <a href="{{ route('admin.agents.index', ['status' => 'pending', 'view' => $viewMode]) }}" class="btn btn-warning btn-sm fw-bold">
        <i class="bi bi-shield-check me-1"></i> Review Pending (<span class="num-tabular">{{ $pendingCount }}</span>)
    </a>
</div>
@endif

<!-- Search & Filters -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.agents.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
            <input type="hidden" name="view" value="{{ $viewMode }}">

            <div class="flex-grow-1" style="min-width: 220px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-transparent border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0"
                           placeholder="Search name, code, phone, email…" value="{{ request('search') }}">
                </div>
            </div>

            @if(is_null($restrictedBranchId))
            <div style="min-width: 200px;">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches & Free Field</option>
                    <option value="free" {{ request('branch_id') === 'free' ? 'selected' : '' }}>— Free Field Only —</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div style="min-width: 160px;">
                <select name="rank_id" class="form-select form-select-sm">
                    <option value="">All Ranks</option>
                    @foreach($ranks as $rank)
                        <option value="{{ $rank->id }}" {{ request('rank_id') == $rank->id ? 'selected' : '' }}>
                            {{ $rank->name }} (Lvl {{ $rank->level }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 160px;">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Verification ({{ $pendingCount ?? 0 }})</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <button type="submit" class="btn btn-dark btn-sm">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>

            @if(request()->hasAny(['search','branch_id','rank_id','status']))
                <a href="{{ route('admin.agents.index', ['view' => $viewMode]) }}" class="btn btn-outline-secondary btn-sm" title="Clear all filters">
                    <i class="bi bi-x-lg me-1"></i> Clear
                </a>
            @endif
        </form>
    </div>
</div>

@if($viewMode === 'tree')
<!-- Hierarchy Tree View -->
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 px-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-diagram-3 fs-5 text-primary"></i>
            <div>
                <span class="fw-semibold text-heading d-block">Agent Hierarchy (Upline → Downline Network)</span>
                <span class="small text-muted">Visualizing hierarchical sponsor structures & downline teams</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" onclick="expandAllTreeNodes()" title="Expand all branches">
                <i class="bi bi-arrows-expand me-1"></i> Expand All
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" onclick="collapseAllTreeNodes()" title="Collapse all branches">
                <i class="bi bi-arrows-collapse me-1"></i> Collapse All
            </button>
            <span class="badge bg-light text-dark border px-2 py-1 num-tabular">{{ $agents->count() }} agents</span>
        </div>
    </div>
    <div class="card-body p-3">
        @if($agentTree->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-person-vcard d-block mb-2 text-secondary opacity-50" style="font-size:2.5rem;"></i>
                <div class="fw-semibold text-heading">No agents found in hierarchy</div>
                <p class="small text-muted mt-1">Try clearing filters or add a new agent to start building your network.</p>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isBranchAdmin())
                <a href="{{ route('admin.agents.create') }}" class="btn btn-dark btn-sm mt-2">
                    <i class="bi bi-person-plus me-1"></i> Add Agent
                </a>
                @endif
            </div>
        @else
            <div class="agent-tree-container">
                <ul class="agent-tree" id="agentHierarchyTree">
                    @foreach($agentTree as $rootAgent)
                        @include('admin.agents._tree-node', ['node' => $rootAgent, 'depth' => 0])
                    @endforeach
                </ul>
            </div>
            <div class="text-muted mt-3 pt-2 border-top d-flex flex-wrap justify-content-between align-items-center gap-2" style="font-size:0.75rem;">
                <div><i class="bi bi-info-circle me-1"></i> Agents recruited by other agents are nested directly beneath their sponsor. Click chevron buttons to expand/collapse.</div>
                <div><span class="num-tabular">{{ $agents->count() }}</span> total network node{{ $agents->count() > 1 ? 's' : '' }}</div>
            </div>
        @endif
    </div>
</div>
@else
<!-- Paginated Table View (Default) -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width:48px;" class="ps-3">#</th>
                        <th style="min-width:220px;">Agent</th>
                        <th style="min-width:170px;">Contact</th>
                        <th style="min-width:150px;">Branch & Place</th>
                        <th style="min-width:130px;">Rank</th>
                        <th style="min-width:170px;">Network / Sponsor</th>
                        <th style="min-width:110px;">Status</th>
                        <th style="min-width:240px;" class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                        <tr>
                            <td class="text-muted font-monospace ps-3 num-tabular" style="font-size:0.8rem;">
                                {{ $agents->firstItem() + $loop->index }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($agent->profile_image_url)
                                        <img src="{{ $agent->profile_image_url }}" alt="{{ $agent->name }}" class="agent-table-avatar object-fit-cover">
                                    @else
                                        <div class="agent-table-avatar">
                                            {{ strtoupper(substr($agent->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.agents.show', $agent) }}" class="text-decoration-none agent-name-link">
                                            {{ $agent->name }}
                                        </a>
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <span class="badge bg-light text-dark border font-monospace px-1 py-0 num-tabular" style="font-size:0.7rem;">
                                                {{ $agent->member_code ?? $agent->agent_id }}
                                            </span>
                                            @if($agent->joining_date)
                                                <span class="text-muted num-tabular" style="font-size:0.7rem;">&bull; Joined {{ $agent->joining_date->format('d M Y') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($agent->phone)
                                    <div><i class="bi bi-telephone text-muted me-1" style="font-size:0.75rem;"></i><a href="tel:{{ $agent->phone }}" class="text-decoration-none text-body num-tabular" style="font-size:0.825rem;">{{ $agent->phone }}</a></div>
                                @endif
                                @if($agent->email)
                                    <div class="text-muted" style="font-size:0.75rem;"><i class="bi bi-envelope text-muted me-1"></i><a href="mailto:{{ $agent->email }}" class="text-decoration-none text-muted">{{ $agent->email }}</a></div>
                                @endif
                            </td>
                            <td>
                                @if($agent->branch)
                                    <div class="fw-medium text-heading" style="font-size:0.85rem;">{{ $agent->branch->name }}</div>
                                    <div class="text-muted font-monospace" style="font-size:0.72rem;">{{ $agent->branch->code }}</div>
                                @else
                                    <span class="badge bg-light text-primary border"><i class="bi bi-geo-alt me-1"></i>Free Field</span>
                                @endif
                                @if($agent->place)
                                    <div class="small text-muted mt-1"><i class="bi bi-geo-alt me-1 text-primary"></i>{{ $agent->place }}</div>
                                @endif
                            </td>
                            <td>
                                @if($agent->rank)
                                    <span class="badge bg-dark text-white fw-normal" style="font-size:0.72rem;">
                                        {{ $agent->rank->name }}
                                    </span>
                                    <div class="text-muted font-monospace" style="font-size:0.72rem;">Lvl {{ $agent->rank->level }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($agent->parentAgent)
                                    <div class="small">
                                        <i class="bi bi-diagram-3 text-muted me-1"></i>
                                        <span class="text-muted">Under:</span>
                                        <a href="{{ route('admin.agents.show', $agent->parentAgent) }}" class="text-decoration-none fw-medium text-body">
                                            {{ $agent->parentAgent->name }}
                                        </a>
                                    </div>
                                @endif
                                @if($agent->reportingStaff)
                                    <div class="small text-muted">
                                        <i class="bi bi-person-workspace me-1"></i>Staff: {{ $agent->reportingStaff->name }}
                                    </div>
                                @endif
                                @if(!$agent->parentAgent && !$agent->reportingStaff)
                                    <span class="text-muted small">Direct Branch Top-Tier</span>
                                @endif
                            </td>
                            <td>
                                @if($agent->status === 'active')
                                    <span class="badge-active">Active</span>
                                @elseif($agent->status === 'pending')
                                    <span class="badge-pending"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                @elseif($agent->status === 'rejected')
                                    <span class="badge-inactive">Rejected</span>
                                @else
                                    <span class="badge-inactive">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-3" style="white-space: nowrap;">
                                <div class="d-inline-flex gap-1 align-items-center justify-content-end">
                                    @if(auth()->user()->isSuperAdmin() && $agent->status === 'pending')
                                        <form action="{{ route('admin.agents.accept', $agent) }}" method="POST"
                                              data-confirm="Verify and accept agent '{{ $agent->name }}' into active service?"
                                              data-confirm-title="Verify & Accept Agent"
                                              data-confirm-type="success"
                                              data-confirm-btn="Verify & Accept">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success px-2 py-1" title="Verify & Accept Agent">
                                                <i class="bi bi-check-lg me-1"></i>Accept
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.agents.reject', $agent) }}" method="POST"
                                              data-confirm="Reject agent application for '{{ $agent->name }}'?"
                                              data-confirm-title="Reject Agent"
                                              data-confirm-type="danger"
                                              data-confirm-btn="Reject">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="Reject Agent">
                                                <i class="bi bi-x-lg me-1"></i>Reject
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.agents.show', $agent) }}"
                                       class="btn btn-action" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.targets.agents.show', $agent) }}"
                                       class="btn btn-action text-success" title="Financial Year Targets">
                                        <i class="bi bi-crosshair"></i>
                                    </a>
                                    <a href="{{ route('admin.career.agent-performance', $agent) }}"
                                       class="btn btn-action text-primary" title="Career Scorecard">
                                        <i class="bi bi-award"></i>
                                    </a>
                                    <a href="{{ route('admin.leads.index', ['agent_id' => $agent->id]) }}"
                                       class="btn btn-action" title="View Leads">
                                        <i class="bi bi-briefcase"></i>
                                    </a>
                                    <a href="{{ route('admin.agents.edit', $agent) }}"
                                       class="btn btn-action" title="Edit Agent">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($agent->status !== 'pending')
                                    <form action="{{ route('admin.agents.toggle-status', $agent) }}" method="POST"
                                          data-confirm="Are you sure you want to {{ $agent->status === 'active' ? 'deactivate' : 'activate' }} this agent?"
                                          data-confirm-title="{{ $agent->status === 'active' ? 'Deactivate Agent' : 'Activate Agent' }}"
                                          data-confirm-type="{{ $agent->status === 'active' ? 'danger' : 'success' }}"
                                          data-confirm-btn="{{ $agent->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        @csrf
                                        <button type="submit" class="btn btn-action {{ $agent->status === 'active' ? 'text-danger' : 'text-success' }}"
                                                title="{{ $agent->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                            <i class="bi {{ $agent->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @if(auth()->user()->isSuperAdmin())
                                    <form action="{{ route('admin.agents.destroy', $agent) }}" method="POST" class="d-inline"
                                          data-confirm="Are you sure you want to delete agent '{{ $agent->name }}' ({{ $agent->agent_id }})? This action cannot be undone."
                                          data-confirm-title="Delete Agent"
                                          data-confirm-type="danger"
                                          data-confirm-btn="Delete Agent">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-action text-danger" title="Delete Agent">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-person-vcard d-block mb-2 text-secondary opacity-50" style="font-size:2.5rem;"></i>
                                <div class="fw-semibold text-heading">No agents found</div>
                                <p class="small text-muted mt-1">Try adjusting search criteria or add your first agent.</p>
                                @if(auth()->user()->isSuperAdmin() || auth()->user()->isBranchAdmin())
                                <a href="{{ route('admin.agents.create') }}"
                                   class="d-inline-block mt-2 btn btn-dark btn-sm">
                                    <i class="bi bi-person-plus me-1"></i> Add Agent
                                </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($agents->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center py-2 px-3 border-top">
            <span class="small text-muted">
                Showing {{ $agents->firstItem() }} to {{ $agents->lastItem() }} of {{ $agents->total() }} agents
            </span>
            {{ $agents->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endif
@endsection

@push('scripts')
<script>
    function toggleAgentDownline(btn) {
        var li = btn.closest('.agent-tree-item');
        if (!li) return;
        var childrenUl = li.querySelector(':scope > .agent-tree-children');
        if (!childrenUl) return;

        if (childrenUl.style.display === 'none') {
            childrenUl.style.display = '';
            btn.classList.remove('is-collapsed');
        } else {
            childrenUl.style.display = 'none';
            btn.classList.add('is-collapsed');
        }
    }

    function expandAllTreeNodes() {
        document.querySelectorAll('.agent-tree-children').forEach(function(ul) {
            ul.style.display = '';
        });
        document.querySelectorAll('.agent-tree-toggle').forEach(function(btn) {
            btn.classList.remove('is-collapsed');
        });
    }

    function collapseAllTreeNodes() {
        document.querySelectorAll('.agent-tree-children').forEach(function(ul) {
            ul.style.display = 'none';
        });
        document.querySelectorAll('.agent-tree-toggle').forEach(function(btn) {
            btn.classList.add('is-collapsed');
        });
    }
</script>
@endpush
