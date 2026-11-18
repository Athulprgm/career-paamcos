@extends('superadmin.layouts.app')

@section('page-title', 'Agent Profile — ' . $agent->name)

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="page-title mb-1">{{ $agent->name }}</h1>
        <div class="page-breadcrumb">People / <a href="{{ route('admin.agents.index') }}" style="color:inherit;">Agents</a> / {{ $agent->member_code ?? $agent->agent_id }}</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        @if(auth()->user()->isSuperAdmin() && $agent->status === 'pending')
            <form action="{{ route('admin.agents.accept', $agent) }}" method="POST" class="d-inline"
                  data-confirm="Verify and accept agent '{{ $agent->name }}' into active service?"
                  data-confirm-title="Verify & Accept Agent"
                  data-confirm-type="success"
                  data-confirm-btn="Verify & Accept">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-check-circle me-1"></i> Verify & Accept
                </button>
            </form>
            <form action="{{ route('admin.agents.reject', $agent) }}" method="POST" class="d-inline"
                  data-confirm="Reject recruited agent '{{ $agent->name }}'?"
                  data-confirm-title="Reject Agent"
                  data-confirm-type="danger"
                  data-confirm-btn="Reject">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Reject
                </button>
            </form>
        @endif
        <a href="{{ route('admin.leads.index', ['agent_id' => $agent->id]) }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-briefcase me-1"></i> Agent Leads ({{ $totalLeadsCount }})
        </a>
        <a href="{{ route('admin.career.agent-performance', $agent) }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-graph-up me-1"></i> Career Details
        </a>
        <a href="{{ route('admin.targets.agents.show', $agent) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-crosshair me-1"></i> FY Targets
        </a>
        <a href="{{ route('admin.agents.edit', $agent) }}" class="btn btn-dark btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit Profile
        </a>
        @if($agent->status !== 'pending')
        <form action="{{ route('admin.agents.toggle-status', $agent) }}" method="POST" class="d-inline"
              data-confirm="Are you sure you want to {{ $agent->status === 'active' ? 'deactivate' : 'activate' }} this agent?"
              data-confirm-title="{{ $agent->status === 'active' ? 'Deactivate Agent' : 'Activate Agent' }}"
              data-confirm-type="{{ $agent->status === 'active' ? 'danger' : 'success' }}"
              data-confirm-btn="{{ $agent->status === 'active' ? 'Deactivate' : 'Activate' }}">
            @csrf
            <button type="submit" class="btn btn-sm {{ $agent->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                <i class="bi {{ $agent->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }} me-1"></i>
                {{ $agent->status === 'active' ? 'Deactivate' : 'Activate' }}
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
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>
        @endif
        <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@if($agent->status === 'pending')
<div class="alert alert-warning border-warning p-3 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-warning text-dark p-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
            <i class="bi bi-hourglass-split fs-4"></i>
        </div>
        <div>
            <h6 class="mb-0 fw-bold text-dark">Agent Pending Super Admin Verification & Acceptance</h6>
            <div class="small text-muted mt-1">
                Recruited by: <strong>{{ $agent->parentAgent?->name ?? 'Direct Upline' }}</strong>
                ({{ $agent->parentAgent?->agent_id ?? '—' }} &bull; Rank: {{ $agent->parentAgent?->rank?->name ?? 'Standard' }})
                &bull; Proposed Rank: <strong>{{ $agent->rank?->name ?? 'Standard' }} (Level {{ $agent->rank?->level ?? 1 }})</strong>
                &bull; Branch: <strong>{{ $agent->branch?->name ?? 'Free Field' }}</strong>
                @if($agent->place)
                    &bull; Place: <strong>{{ $agent->place }}</strong>
                @endif
            </div>
        </div>
    </div>
    @if(auth()->user()->isSuperAdmin())
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#assignBranchPlaceModal">
            <i class="bi bi-geo-alt-fill me-1"></i> Assign Branch & Place
        </button>
        <form action="{{ route('admin.agents.accept', $agent) }}" method="POST"
              data-confirm="Verify and accept agent '{{ $agent->name }}' into active service with current branch/place?"
              data-confirm-title="Verify & Accept Agent"
              data-confirm-type="success"
              data-confirm-btn="Verify & Accept">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-check-circle me-1"></i> Quick Accept
            </button>
        </form>
        <form action="{{ route('admin.agents.reject', $agent) }}" method="POST"
              data-confirm="Reject recruited agent '{{ $agent->name }}'?"
              data-confirm-title="Reject Agent"
              data-confirm-type="danger"
              data-confirm-btn="Reject">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-x-circle me-1"></i> Reject
            </button>
        </form>
    </div>
    @endif
</div>
@endif

<!-- 4 Key Performance Metric Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon emerald"><i class="bi bi-piggy-bank-fill"></i></div>
            <div>
                <div class="stat-value num-tabular">₹{{ number_format($depositPerformance['active_volume'] ?? 0, 0) }}</div>
                <div class="stat-label">Active Deposits ({{ $depositPerformance['active_count'] ?? 0 }} accounts)</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="stat-value num-tabular">₹{{ number_format($collectionPerformance['paid_volume'] ?? 0, 0) }}</div>
                <div class="stat-label">Collections Paid ({{ $collectionPerformance['paid_count'] ?? 0 }} entries)</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-percent"></i></div>
            <div>
                <div class="stat-value num-tabular">₹{{ number_format($commissionSummary['total_earned'] ?? 0, 0) }}</div>
                <div class="stat-label">Total Commission (₹{{ number_format($commissionSummary['pending'] ?? 0, 0) }} Pending)</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon dark"><i class="bi bi-award-fill"></i></div>
            <div>
                <div class="stat-value" style="font-size:1.1rem;">{{ $agent->rank?->name ?? 'Representative' }}</div>
                <div class="stat-label">Lvl {{ $agent->rank?->level ?? '1' }} ({{ $activeCustomersCount ?? 0 }} Customers)</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Left Column: Agent Profile Card & Branch Lineage -->
    <div class="col-lg-4">
        <div class="card mb-3 border">
            <div class="card-body text-center p-4">
                @if($agent->profile_image_url)
                    <img src="{{ $agent->profile_image_url }}" alt="{{ $agent->name }}" class="mx-auto mb-3 border border-2 border-white object-fit-cover d-block" style="width:76px;height:76px;">
                @else
                    <div class="mx-auto mb-3" style="width:68px;height:68px;background:var(--color-deep-green, #14532D);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:600;">
                        {{ strtoupper(substr($agent->name, 0, 1)) }}
                    </div>
                @endif
                <h5 class="mb-1 fw-bold text-dark">{{ $agent->name }}</h5>
                <div class="text-muted mb-2 font-monospace num-tabular" style="font-size:0.85rem;">{{ $agent->member_code ?? $agent->agent_id }}</div>

                <div class="mb-3">
                    @if($agent->status === 'active')
                        <span class="badge-active">Active Agent</span>
                    @elseif($agent->status === 'pending')
                        <span class="badge-pending"><i class="bi bi-hourglass-split me-1"></i>Pending Verification</span>
                    @elseif($agent->status === 'rejected')
                        <span class="badge-inactive">Rejected</span>
                    @else
                        <span class="badge-inactive">Inactive</span>
                    @endif
                </div>

                <div class="border-top pt-3 text-start" style="font-size:0.825rem;">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-envelope me-2"></i>Email</span>
                        <span class="fw-medium text-dark text-break">{{ $agent->email }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-phone me-2"></i>Mobile</span>
                        <span class="fw-medium text-dark num-tabular">{{ $agent->phone }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-calendar3 me-2"></i>Joining Date</span>
                        <span class="fw-medium text-dark num-tabular">{{ $agent->joining_date ? $agent->joining_date->format('d M Y') : '—' }}</span>
                    </div>
                    @if($agent->place)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-geo-alt me-2"></i>Place / Location</span>
                        <span class="fw-medium text-dark">{{ $agent->place }}</span>
                    </div>
                    @endif
                    @if($agent->address)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-pin-map me-2"></i>Address</span>
                        <span class="fw-medium text-dark text-end" style="max-width: 65%;">{{ $agent->address }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted"><i class="bi bi-clock-history me-2"></i>Registered</span>
                        <span class="text-muted num-tabular">{{ $agent->created_at ? $agent->created_at->format('d M Y') : '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hierarchy & Worker Status -->
        <div class="card mb-3 border">
            <div class="card-header bg-white py-3 fw-semibold">
                <i class="bi bi-diagram-3 me-1 text-primary"></i> Hierarchy & Organization
            </div>
            <div class="card-body p-3" style="font-size:0.85rem;">
                <div class="mb-3 pb-2 border-bottom">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">WORKER TYPE</div>
                    <div class="fw-semibold mt-1 text-dark">
                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-geo-alt me-1"></i>Free Field Worker</span>
                    </div>
                </div>

                <div class="mb-3 pb-2 border-bottom">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">REPORTING STAFF OFFICER</div>
                    <div class="mt-1">
                        @if($agent->reportingStaff)
                            <a href="{{ route('admin.staff.show', $agent->reportingStaff) }}" class="text-decoration-none fw-semibold text-dark">
                                <i class="bi bi-person-workspace me-1 text-muted"></i>{{ $agent->reportingStaff->name }}
                            </a>
                            <div class="text-muted font-monospace small">
                                {{ $agent->reportingStaff->employee_id }} — {{ $agent->reportingStaff->position->name ?? 'Staff' }}
                            </div>
                        @else
                            <span class="text-muted">Direct Branch Administration</span>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">PARENT SPONSOR / UPLINE</div>
                    <div class="mt-1">
                        @if($agent->parentAgent)
                            <a href="{{ route('admin.agents.show', $agent->parentAgent) }}" class="text-decoration-none fw-semibold text-dark">
                                <i class="bi bi-person-check me-1 text-muted"></i>{{ $agent->parentAgent->name }}
                            </a>
                            <span class="text-muted font-monospace small">({{ $agent->parentAgent->member_code ?? $agent->parentAgent->agent_id }})</span>
                        @else
                            <span class="text-muted">Top-Tier (Direct Branch Sponsor)</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Assigned Customers & Downlines -->
    <div class="col-lg-8">
        <!-- Target Management & Promotion Progress Card -->
        @if(isset($targetAchievement))
        <div class="card mb-3 border">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="fw-semibold text-dark d-flex align-items-center gap-2 flex-wrap">
                    <i class="bi bi-bullseye me-1 text-primary"></i> Target Management &amp; Promotion Status
                    <a href="{{ route('admin.targets.agents.show', $agent) }}" class="badge bg-light text-success border text-decoration-none py-1 px-2" title="View Financial Year Agent Target Dashboard">
                        <i class="bi bi-crosshair me-1"></i> FY Targets
                    </a>
                    <a href="{{ route('admin.career.agent-performance', $agent) }}" class="badge bg-light text-primary border text-decoration-none py-1 px-2" title="View Career Performance Scorecard">
                        <i class="bi bi-award me-1"></i> Career Scorecard
                    </a>
                    <a href="{{ route('admin.deposits.index', ['agent_id' => $agent->id]) }}" class="badge bg-light text-dark border text-decoration-none py-1 px-2" title="View Deposits Sourced by Agent">
                        <i class="bi bi-piggy-bank me-1"></i> Sourced Deposits
                    </a>
                </div>
                <div class="d-flex align-items-center gap-1">
                    @if(!empty($targetAchievement['apply_tenure_targets']))
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" title="Tenure duration percentage multipliers applied">
                            <i class="bi bi-clock-history me-1"></i>Tenure Weighted
                        </span>
                    @endif
                    @if($targetAchievement['is_promotable'])
                        <span class="badge bg-success text-white px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>PROMOTION ELIGIBLE</span>
                    @elseif(!empty($targetAchievement['eligibility']['has_pending_request']))
                        <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>REQUEST PENDING</span>
                    @elseif($targetAchievement['has_target'])
                        <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-clock me-1"></i>TARGET IN PROGRESS</span>
                    @else
                        <span class="badge bg-secondary text-white px-2 py-1">HIGHEST RANK</span>
                    @endif
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Target Amount</div>
                        <div class="fs-5 fw-bold text-dark num-tabular">₹{{ number_format($targetAchievement['target_amount'], 2) }}</div>
                        <div class="text-muted small">
                            {{ $targetAchievement['current_rank']['name'] ?? 'Current' }} &rarr; {{ $targetAchievement['next_rank']['name'] ?? 'Top' }}
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Eligible Deposits</div>
                        <div class="fs-5 fw-bold text-success num-tabular">₹{{ number_format($targetAchievement['eligible_deposits'], 2) }}</div>
                        <div class="text-muted small"><span class="num-tabular">{{ number_format($targetAchievement['rd_percent'] ?? 10, 0) }}%</span> RD / <span class="num-tabular">{{ number_format($targetAchievement['fd_percent'] ?? 90, 0) }}%</span> FD Rule</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Remaining Amount</div>
                        <div class="fs-5 fw-bold num-tabular {{ $targetAchievement['remaining_amount'] > 0 ? 'text-danger' : 'text-success' }}">
                            ₹{{ number_format($targetAchievement['remaining_amount'], 2) }}
                        </div>
                        <div class="text-muted small">{{ $targetAchievement['remaining_amount'] == 0 ? 'Target Achieved' : 'To Target' }}</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Achievement Progress</div>
                        <div class="fs-5 fw-bold text-primary num-tabular">{{ $targetAchievement['progress_percentage'] }}%</div>
                        <div class="text-muted small">Overall Volume</div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="fw-semibold text-muted">Target Fulfillment</span>
                        <span class="fw-bold num-tabular {{ $targetAchievement['progress_percentage'] >= 100 ? 'text-success' : 'text-primary' }}">{{ $targetAchievement['progress_percentage'] }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar {{ $targetAchievement['progress_percentage'] >= 100 ? 'bg-success' : 'bg-primary' }}"
                             role="progressbar"
                             style="width: {{ min(100, $targetAchievement['progress_percentage']) }}%;"
                             aria-valuenow="{{ $targetAchievement['progress_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- 10% RD + 90% FD + Recruits Composition Grid -->
                <div class="p-2 bg-light border mb-3">
                    <div class="row g-2 text-center align-items-center">
                        @php
                            $hasRecruits = !empty($targetAchievement['recruits_progress']) && $targetAchievement['recruits_progress']['required'] > 0;
                            $colClass = $hasRecruits ? 'col-md-4' : 'col-6';
                        @endphp
                        <div class="{{ $colClass }} border-end">
                            <div class="text-muted small fw-semibold" style="font-size: 0.72rem;">RD COMPOSITION (MIN 10%)</div>
                            <div class="small mt-1">
                                <strong class="num-tabular">₹{{ number_format($targetAchievement['rd_progress']['actual'], 2) }}</strong>
                                <span class="text-muted num-tabular">/ ₹{{ number_format($targetAchievement['rd_progress']['required'], 2) }}</span>
                            </div>
                            <div class="mt-1">
                                @if($targetAchievement['rd_progress']['is_met'])
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">Requirement Met</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle num-tabular" style="font-size: 0.68rem;">Shortfall: ₹{{ number_format(max(0, $targetAchievement['rd_progress']['required'] - $targetAchievement['rd_progress']['actual']), 2) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="{{ $colClass }} {{ $hasRecruits ? 'border-end' : '' }}">
                            <div class="text-muted small fw-semibold" style="font-size: 0.72rem;">FD COMPOSITION (MAX 90% CAPPED)</div>
                            <div class="small mt-1">
                                <strong class="num-tabular">₹{{ number_format($targetAchievement['fd_progress']['actual'], 2) }}</strong>
                                <span class="text-muted num-tabular">(Eligible: ₹{{ number_format($targetAchievement['fd_progress']['capped_eligible'], 2) }})</span>
                            </div>
                            <div class="mt-1">
                                <span class="badge bg-light text-muted border num-tabular" style="font-size: 0.68rem;">Max Eligible Cap: ₹{{ number_format($targetAchievement['fd_progress']['cap_amount'], 2) }}</span>
                            </div>
                        </div>
                        @if($hasRecruits)
                        <div class="col-md-4">
                            <div class="text-muted small fw-semibold" style="font-size: 0.72rem;">DIRECT QUALIFYING AGENTS</div>
                            <div class="small mt-1">
                                <strong class="num-tabular">{{ $targetAchievement['recruits_progress']['actual'] }}</strong>
                                <span class="text-muted">/ <span class="num-tabular">{{ $targetAchievement['recruits_progress']['required'] }}</span> {{ $targetAchievement['recruits_progress']['target_rank_name'] }}s</span>
                            </div>
                            <div class="mt-1">
                                @if($targetAchievement['recruits_progress']['is_met'])
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">Requirement Met</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;">Need <span class="num-tabular">{{ max(0, $targetAchievement['recruits_progress']['required'] - $targetAchievement['recruits_progress']['actual']) }}</span> more</span>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Unmet Conditions Alert / Checklist -->
                @if(!empty($targetAchievement['unmet_conditions']))
                    <div class="alert alert-warning py-2 px-3 mb-0 border" style="font-size: 0.82rem;">
                        <div class="fw-semibold text-warning-emphasis mb-1">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> Pending Conditions for Promotion to {{ $targetAchievement['next_rank']['name'] ?? 'Next Rank' }}:
                        </div>
                        <ul class="mb-0 ps-3">
                            @foreach($targetAchievement['unmet_conditions'] as $condition)
                                <li>{{ $condition }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="alert alert-success py-2 px-3 mb-0 border" style="font-size: 0.82rem;">
                        <i class="bi bi-check-circle-fill me-1 text-success"></i>
                        @if($targetAchievement['is_promotable'])
                            All targets, RD/FD composition requirements, and downline rank qualifications satisfied!
                        @else
                            Highest career rank achieved.
                        @endif
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Direct Downlines Card -->
        <div class="card mb-3 border">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="fw-semibold text-dark">
                    <i class="bi bi-diagram-2 me-1 text-primary"></i> Direct Downline Agents
                </div>
                <span class="badge bg-light text-dark border num-tabular">{{ $agent->downlineAgents->count() }} downlines</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Agent ID</th>
                                <th>Name</th>
                                <th>Rank</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agent->downlineAgents as $downline)
                                <tr>
                                    <td class="font-monospace num-tabular">{{ $downline->member_code ?? $downline->agent_id }}</td>
                                    <td class="fw-medium text-dark">{{ $downline->name }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $downline->rank->name ?? '—' }}</span>
                                    </td>
                                    <td class="num-tabular">{{ $downline->phone }}</td>
                                    <td>
                                        @if($downline->status === 'active')
                                            <span class="badge-active">Active</span>
                                        @elseif($downline->status === 'pending')
                                            <span class="badge-pending">Pending</span>
                                        @else
                                            <span class="badge-inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('admin.agents.show', $downline) }}" class="btn btn-sm btn-outline-dark" title="View Downline Agent">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No direct downline agents recruited yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Business Leads Card -->
        <div class="card mb-3 border">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="fw-semibold text-dark">
                    <i class="bi bi-briefcase me-1 text-primary"></i> Business Leads Added by {{ $agent->name }}
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border num-tabular">{{ $totalLeadsCount }} total ({{ $convertedLeadsCount }} converted)</span>
                    @if($totalLeadsCount > 0)
                        <a href="{{ route('admin.leads.index', ['agent_id' => $agent->id]) }}" class="btn btn-sm btn-outline-dark" style="font-size:0.75rem;">
                            View All Leads <i class="bi bi-arrow-right"></i>
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Lead Name</th>
                                <th>Contact</th>
                                <th>Target Scheme</th>
                                <th>Status</th>
                                <th>Follow-up</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agentLeads as $lead)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.leads.show', $lead) }}" class="fw-semibold text-dark text-decoration-none">
                                            {{ $lead->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-telephone text-muted me-1" style="font-size:0.75rem;"></i><span class="num-tabular">{{ $lead->mobile }}</span></div>
                                        @if($lead->email)
                                            <div class="text-muted small"><i class="bi bi-envelope text-muted me-1" style="font-size:0.75rem;"></i>{{ $lead->email }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $lead->interestedScheme?->name ?? 'General Inquiry' }}</div>
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
                                    <td class="text-end pe-3">
                                        <a href="{{ route('admin.leads.show', $lead) }}" class="btn btn-sm btn-outline-dark" title="View Lead Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No business leads registered by this agent yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isSuperAdmin() && $agent->status === 'pending')
<!-- Assign Branch & Place Modal -->
<div class="modal fade" id="assignBranchPlaceModal" tabindex="-1" aria-labelledby="assignBranchPlaceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border">
            <form action="{{ route('admin.agents.accept', $agent) }}" method="POST">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fs-6 fw-bold text-dark" id="assignBranchPlaceModalLabel">
                        <i class="bi bi-person-check-fill text-primary me-2"></i>Verify & Accept Free Field Agent
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-success-subtle border border-success-subtle small text-dark py-2 px-3 mb-3">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Agents operate as <strong>free field workers</strong> across all locations. You can confirm their operational locality and notes below.
                    </div>

                    <div class="mb-3">
                        <label for="modal_place" class="form-label fw-semibold small">Place / Operating Area</label>
                        <input type="text" name="place" id="modal_place" class="form-control form-control-sm"
                               value="{{ old('place', $agent->place) }}" placeholder="e.g. City, Town, Sector, or Market Area">
                        <div class="form-text small">Designated locality or field area.</div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_address" class="form-label fw-semibold small">Address / Notes</label>
                        <textarea name="address" id="modal_address" rows="2" class="form-control form-control-sm"
                                  placeholder="Contact address or operational location notes...">{{ old('address', $agent->address) }}</textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-dark">
                        <i class="bi bi-check-circle me-1"></i> Confirm & Accept Agent
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
