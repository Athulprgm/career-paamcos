@extends('superadmin.layouts.app')

@section('page-title', 'Agent Scorecard — ' . $agent->name)

@section('content')
<div class="page-header">
    <div>
        <div class="d-flex align-items-center gap-3">
            <h1 class="page-title mb-0">{{ $agent->name }}</h1>
            <span class="badge bg-light text-dark border fs-6">
                {{ $agent->careerLevel->name ?? 'Level 1' }}
            </span>
        </div>
        <div class="page-breadcrumb">Career / Agent Performance Scorecard / {{ $agent->member_code ?? $agent->agent_id }}</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.career.performance') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Matrix
        </a>
        <a href="{{ route('admin.targets.agents.show', $agent) }}" class="btn btn-outline-success btn-sm" title="View Financial Year Target Dashboard">
            <i class="bi bi-crosshair me-1"></i> FY Targets
        </a>
        <a href="{{ route('admin.agents.show', $agent) }}" class="btn btn-outline-dark btn-sm" title="View Agent Profile">
            <i class="bi bi-person me-1"></i> Profile
        </a>
        <a href="{{ route('admin.deposits.index', ['agent_id' => $agent->id]) }}" class="btn btn-outline-dark btn-sm" title="View Deposits Sourced by Agent">
            <i class="bi bi-piggy-bank me-1"></i> Sourced Deposits
        </a>
        @if(!empty($eligibility['is_eligible']))
            <a href="{{ route('admin.career.requests.create', $agent) }}" class="btn btn-success btn-sm">
                <i class="bi bi-arrow-up-circle me-1"></i> Request Promotion
            </a>
        @endif
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

<!-- Period Selector -->
<div class="card mb-4">
    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="small">
            <span class="text-muted">Agent Code:</span> <strong>{{ $agent->member_code ?? $agent->agent_id }}</strong>
            <span class="text-muted ms-3">Branch:</span> <strong>{{ $agent->branch->name ?? 'N/A' }}</strong>
            <span class="text-muted ms-3">Joined:</span> <strong>{{ $agent->joining_date ? $agent->joining_date->format('d M Y') : '—' }}</strong> ({{ $performance['tenure_days'] }} days)
        </div>
        <form method="GET" action="{{ route('admin.career.agent-performance', $agent) }}" class="d-flex gap-2 align-items-center">
            <label class="form-label text-muted small mb-0">Period:</label>
            <input type="month" name="period" class="form-control form-control-sm" value="{{ $period }}" onchange="this.form.submit()">
        </form>
    </div>
</div>

<!-- Key Performance Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted small d-block mb-1">Active Customers</span>
                <h3 class="mb-0 fw-bold" style="color:var(--color-primary);">{{ $performance['active_customers'] }}</h3>
                <div class="small text-muted">{{ $performance['total_customers'] }} total accounts</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted small d-block mb-1">Deposit Accounts Volume</span>
                <h3 class="mb-0 fw-bold text-success">₹{{ number_format($performance['deposit_amount'], 2) }}</h3>
                <div class="small text-muted">{{ $performance['deposit_count'] }} deposits mobilized</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted small d-block mb-1">Paid Collections</span>
                <h3 class="mb-0 fw-bold" style="color:var(--color-secondary);">₹{{ number_format($performance['collection_amount'], 2) }}</h3>
                <div class="small text-muted">{{ $performance['collection_count'] }} collection payments</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted small d-block mb-1">Performance Score</span>
                <h3 class="mb-0 fw-bold" style="color:var(--color-accent);">{{ number_format($performance['performance_score'], 1) }} <span class="fs-6 text-muted fw-normal">/ 100</span></h3>
                <div class="small text-muted">Composite index</div>
            </div>
        </div>
    </div>
</div>

<!-- Career Progression & Promotion Rule Eligibility -->
@if(isset($targetAchievement))
<div class="card mb-4 border">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bullseye me-2 text-primary"></i>Target Management &amp; Deposit Achievement
                </h5>
                <a href="{{ route('admin.targets.agents.show', $agent) }}" class="btn btn-outline-success btn-sm py-0 px-2" style="font-size: 0.75rem;">
                    <i class="bi bi-crosshair me-1"></i> View FY Target Dashboard
                </a>
            </div>
            <div class="small text-muted mt-1">
                Target Progression: <strong>{{ $targetAchievement['current_rank']['name'] ?? 'Current' }}</strong> &rarr; <strong>{{ $targetAchievement['next_rank']['name'] ?? 'Top Rank' }}</strong>
            </div>
        </div>
        <div>
            @if($targetAchievement['is_promotable'])
                <span class="badge bg-success text-white px-3 py-2 fs-6"><i class="bi bi-check-circle-fill me-1"></i>PROMOTION ELIGIBLE</span>
            @elseif(!empty($targetAchievement['eligibility']['has_pending_request']))
                <span class="badge bg-warning text-dark px-3 py-2 fs-6"><i class="bi bi-hourglass-split me-1"></i>REQUEST PENDING</span>
            @elseif($targetAchievement['has_target'])
                <span class="badge bg-light text-dark border px-3 py-2 fs-6"><i class="bi bi-clock me-1"></i>TARGET IN PROGRESS</span>
            @else
                <span class="badge bg-secondary text-white px-3 py-2 fs-6">HIGHEST RANK</span>
            @endif
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.72rem;">Required Target</div>
                <div class="fs-4 fw-bold text-dark">₹{{ number_format($targetAchievement['target_amount'], 2) }}</div>
                <div class="text-muted small">For {{ $targetAchievement['next_rank']['name'] ?? 'Advancement' }}</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.72rem;">Eligible Deposits</div>
                <div class="fs-4 fw-bold text-success">₹{{ number_format($targetAchievement['eligible_deposits'], 2) }}</div>
                <div class="text-muted small">Under {{ number_format($targetAchievement['rd_percent'] ?? 10, 0) }}% RD / {{ number_format($targetAchievement['fd_percent'] ?? 90, 0) }}% FD Rule</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.72rem;">Remaining Amount</div>
                <div class="fs-4 fw-bold {{ $targetAchievement['remaining_amount'] > 0 ? 'text-danger' : 'text-success' }}">
                    ₹{{ number_format($targetAchievement['remaining_amount'], 2) }}
                </div>
                <div class="text-muted small">{{ $targetAchievement['remaining_amount'] == 0 ? 'Target Achieved' : 'Needed to Qualify' }}</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.72rem;">Target Progress</div>
                <div class="fs-4 fw-bold text-primary">{{ $targetAchievement['progress_percentage'] }}%</div>
                <div class="text-muted small">Completion Rate</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1 small">
                <span class="fw-semibold text-muted">Progress toward ₹{{ number_format($targetAchievement['target_amount'], 2) }} target</span>
                <span class="fw-bold {{ $targetAchievement['progress_percentage'] >= 100 ? 'text-success' : 'text-primary' }}">{{ $targetAchievement['progress_percentage'] }}%</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar {{ $targetAchievement['progress_percentage'] >= 100 ? 'bg-success' : 'bg-primary' }}"
                     role="progressbar"
                     style="width: {{ min(100, $targetAchievement['progress_percentage']) }}%;"
                     aria-valuenow="{{ $targetAchievement['progress_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        <!-- 10% RD + 90% FD Composition Grid -->
        <div class="p-3 bg-light border mb-3">
            <div class="row g-3 text-center">
                <div class="col-md-6 border-end-md">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">RD Composition (Min 10% Target)</div>
                    <div class="mt-1">
                        <span class="fs-5 fw-bold text-dark">₹{{ number_format($targetAchievement['rd_progress']['actual'], 2) }}</span>
                        <span class="text-muted small">/ ₹{{ number_format($targetAchievement['rd_progress']['required'], 2) }} required</span>
                    </div>
                    <div class="mt-1">
                        @if($targetAchievement['rd_progress']['is_met'])
                            <span class="badge bg-success text-white"><i class="bi bi-check-circle me-1"></i>RD Target Satisfied</span>
                        @else
                            <span class="badge bg-danger text-white"><i class="bi bi-exclamation-triangle me-1"></i>Shortfall: ₹{{ number_format(max(0, $targetAchievement['rd_progress']['required'] - $targetAchievement['rd_progress']['actual']), 2) }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">FD Composition (Max 90% Eligible Cap)</div>
                    <div class="mt-1">
                        <span class="fs-5 fw-bold text-dark">₹{{ number_format($targetAchievement['fd_progress']['actual'], 2) }}</span>
                        <span class="text-muted small">(Eligible: ₹{{ number_format($targetAchievement['fd_progress']['capped_eligible'], 2) }})</span>
                    </div>
                    <div class="mt-1">
                        <span class="badge bg-light text-muted border">Max Cap: ₹{{ number_format($targetAchievement['fd_progress']['cap_amount'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unmet Conditions Alert -->
        @if(!empty($targetAchievement['unmet_conditions']))
            <div class="alert alert-warning py-2 px-3 mb-0 border">
                <div class="fw-semibold text-warning-emphasis mb-1">
                    <i class="bi bi-exclamation-circle-fill me-1"></i> Unmet Conditions for Promotion:
                </div>
                <ul class="mb-0 ps-3 small">
                    @foreach($targetAchievement['unmet_conditions'] as $condition)
                        <li>{{ $condition }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="alert alert-success py-2 px-3 mb-0 border small">
                <i class="bi bi-check-circle-fill me-1 text-success"></i>
                @if($targetAchievement['is_promotable'])
                    All targets, {{ number_format($targetAchievement['rd_percent'] ?? 10, 0) }}% RD / {{ number_format($targetAchievement['fd_percent'] ?? 90, 0) }}% FD deposit composition rules, and qualifying downline requirements are fully satisfied!
                @else
                    Highest career rank achieved.
                @endif
            </div>
        @endif
    </div>
</div>
@endif

<div class="row g-4">
    <!-- Left Column: Eligibility Engine Checklist -->
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="bi bi-shield-check me-2" style="color:var(--color-primary);"></i>Eligibility Evaluation
                    </h5>
                    <div class="small text-muted">
                        Target Progression:
                        @if(!empty($eligibility['target_level']))
                            <strong style="color:var(--color-primary);">{{ $eligibility['target_level']->name }} (Level {{ $eligibility['target_level']->sort_order }})</strong>
                        @else
                            <em>Top career level reached</em>
                        @endif
                    </div>
                </div>

                @if(!empty($eligibility['is_eligible']))
                    <span class="badge-payable fs-6"><i class="bi bi-check-circle me-1"></i>ELIGIBLE FOR PROMOTION</span>
                @elseif(!empty($eligibility['has_pending_request']))
                    <span class="badge-pending fs-6"><i class="bi bi-hourglass-split me-1"></i>REQUEST PENDING</span>
                @else
                    <span class="badge bg-light text-muted border fs-6">CRITERIA INCOMPLETE</span>
                @endif
            </div>
            <div class="card-body p-0">
                @if(!empty($eligibility['criteria']))
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Qualification Criterion</th>
                                <th class="text-center">Required Target</th>
                                <th class="text-center">Actual Performance</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eligibility['criteria'] as $item)
                            @php
                                $isAchieved = !empty($item['achieved']) || !empty($item['met']);
                                $unit = !empty($item['unit']) ? ' ' . $item['unit'] : '';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item['label'] ?? 'Criteria' }}</div>
                                </td>
                                <td class="text-center">
                                    @if(is_numeric($item['required']))
                                        @if($item['required'] > 0)
                                            {{ ($item['unit'] ?? '') === '₹' ? '₹' . number_format((float) $item['required'], 2) : $item['required'] . $unit }}
                                        @else
                                            <span class="text-muted">None (Optional)</span>
                                        @endif
                                    @else
                                        {{ $item['required'] }}
                                    @endif
                                </td>
                                <td class="text-center fw-semibold">
                                    @if(is_numeric($item['actual']))
                                        {{ ($item['unit'] ?? '') === '₹' ? '₹' . number_format((float) $item['actual'], 2) : $item['actual'] . $unit }}
                                    @else
                                        {{ $item['actual'] }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(is_numeric($item['required']) && $item['required'] == 0)
                                        <span class="badge bg-light text-muted border">—</span>
                                    @elseif($isAchieved)
                                        <span class="badge bg-success text-white"><i class="bi bi-check-lg me-1"></i>Achieved</span>
                                    @else
                                        <span class="badge bg-danger text-white"><i class="bi bi-x-lg me-1"></i>Unmet</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-4 text-center text-muted">
                    No active progression criteria found.
                </div>
                @endif
            </div>
            <div class="card-footer bg-transparent py-3">
                @if(!empty($eligibility['is_eligible']))
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-success small fw-medium">
                            <i class="bi bi-check-circle-fill me-1"></i> All qualification criteria satisfied.
                        </div>
                        <a href="{{ route('admin.career.requests.create', $agent) }}" class="btn btn-success btn-sm">
                            <i class="bi bi-arrow-up-circle me-1"></i> Submit Promotion Request
                        </a>
                    </div>
                @elseif(!empty($eligibility['has_pending_request']))
                    <div class="alert alert-warning mb-0 small">
                        <i class="bi bi-info-circle-fill me-1"></i> A promotion request is currently awaiting administrator review.
                    </div>
                @else
                    <div class="small text-muted">
                        <i class="bi bi-info-circle me-1"></i> Promotion requests can be submitted once all non-zero requirements are achieved.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Promotion Requests & Permanent History -->
    <div class="col-lg-5">
        <!-- Promotion Request History -->
        <div class="card mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0">Promotion Applications</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Proposed Rank</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($promotionRequests as $req)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.career.requests.show', $req) }}" class="fw-semibold text-decoration-none" style="color:var(--color-primary);">
                                        {{ $req->request_number }}
                                    </a>
                                    <div class="small text-muted">{{ $req->request_date ? $req->request_date->format('d M Y') : '' }}</div>
                                </td>
                                <td>{{ $req->proposedLevel->name ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @if($req->status === 'PENDING_REVIEW')
                                        <span class="badge-pending">PENDING</span>
                                    @elseif($req->status === 'APPROVED')
                                        <span class="badge-approved">APPROVED</span>
                                    @elseif($req->status === 'PROMOTED')
                                        <span class="badge-payable">PROMOTED</span>
                                    @elseif($req->status === 'REJECTED')
                                        <span class="badge-rejected">REJECTED</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.career.requests.show', $req) }}" class="btn btn-outline-dark btn-sm py-1 px-2">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted small">
                                    No promotion requests recorded yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Permanent Career History Timeline -->
        <div class="card">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0">Career History Timeline</h5>
            </div>
            <div class="card-body">
                @if($careerHistories->isNotEmpty())
                    <ul class="list-unstyled mb-0">
                        @foreach($careerHistories as $hist)
                            <li class="pb-3 mb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong>{{ $hist->newLevel->name ?? 'Promoted' }}</strong>
                                    <span class="text-muted small">{{ $hist->promotion_date ? $hist->promotion_date->format('d M Y') : '' }}</span>
                                </div>
                                <div class="small text-muted">
                                    @if($hist->previousLevel)
                                        From: {{ $hist->previousLevel->name }} &rarr; To: {{ $hist->newLevel->name }}
                                    @else
                                        Initial rank assignment
                                    @endif
                                </div>
                                @if($hist->approver)
                                    <div class="text-muted" style="font-size:0.75rem;">
                                        Approved by: {{ $hist->approver->name }}
                                    </div>
                                @endif
                                @if($hist->remarks)
                                    <div class="small text-muted mt-1"><em>{{ $hist->remarks }}</em></div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted small py-3 text-center">
                        <i class="bi bi-clock-history fs-3 d-block mb-2 text-muted"></i>
                        Currently at starting career tier. No prior promotions logged.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
