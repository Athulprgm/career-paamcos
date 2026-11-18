@extends('superadmin.layouts.app')

@section('page-title', 'My Career Progress & Eligibility')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-sm-center align-items-start gap-2 mb-3 mb-md-4">
    <div class="min-w-0">
        <h1 class="page-title mb-1 text-truncate">My Career Progress</h1>
        <div class="page-breadcrumb text-muted text-break">
            <span>Agent</span> / <span class="text-dark fw-semibold">My Performance &amp; Career &bull; Promotion Eligibility</span>
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-sm-auto">
        <a href="{{ route('agent.promotion-history') }}" class="btn btn-outline-dark btn-sm flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-clock-history me-1"></i> Promotion History
        </a>
        <form method="GET" action="{{ route('agent.performance') }}" class="d-flex align-items-center gap-2 flex-fill flex-sm-grow-0">
            <label class="small text-muted fw-semibold m-0 text-nowrap">Period:</label>
            <input type="month" name="period" class="form-control form-control-sm" value="{{ $period }}" onchange="this.form.submit()">
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 mb-md-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3 mb-md-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
    $nextRank = $eligibility['target_level'] ?? null;
    $teamCrit = $eligibility['criteria']['team_deposit'] ?? null;
    $indCrit = $eligibility['criteria']['individual_deposit'] ?? null;
    $facCrit = $eligibility['criteria']['qualifying_facilitators'] ?? null;
    $isEligible = !empty($eligibility['is_eligible']);
    $hasPending = !empty($pendingRequest);
@endphp

<!-- Agent Summary & Career Pathway Banner -->
<div class="card border mb-3 mb-md-4">
    <div class="card-body p-3 p-md-4">
        <div class="row align-items-center g-3">
            <div class="col-12 col-lg-5">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="bg-dark text-white p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                        <i class="bi bi-person-badge fs-3"></i>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <h5 class="fw-bold text-dark mb-1 text-truncate">{{ $agent->name }}</h5>
                        <div class="text-muted small text-break">
                            Code: <span class="fw-semibold text-dark">{{ $agent->member_code ?? $agent->agent_id }}</span>
                            &bull; Status: <span class="badge {{ $agent->getStatusBadgeClass() }}">{{ strtoupper($agent->status) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="d-flex flex-wrap justify-content-start justify-content-lg-end align-items-center gap-2 gap-sm-3">
                    <div>
                        <span class="text-muted small d-block">Current Rank</span>
                        <span class="badge bg-primary px-3 py-2 text-nowrap" style="font-size: 0.825rem;">
                            <i class="bi bi-award me-1"></i>{{ $agent->rank?->name ?? 'Facilitator' }} (Rank {{ $agent->rank?->level ?? $agent->rank?->sort_order ?? 1 }})
                        </span>
                    </div>

                    @if($nextRank)
                    <div class="text-muted fs-5 d-none d-sm-block">&rarr;</div>
                    <div>
                        <span class="text-muted small d-block">Next Rank</span>
                        <span class="badge bg-dark px-3 py-2 text-nowrap" style="font-size: 0.825rem;">
                            <i class="bi bi-trophy me-1 text-warning"></i>{{ $nextRank->name }} (Rank {{ $nextRank->level ?? $nextRank->sort_order }})
                        </span>
                    </div>
                    @endif

                    <div class="border-start-sm ps-sm-2">
                        <span class="text-muted small d-block">Eligibility</span>
                        @if($hasPending)
                            <span class="badge-pending px-3 py-2 text-nowrap" style="font-size: 0.825rem;">
                                <i class="bi bi-hourglass-split me-1"></i>UNDER REVIEW
                            </span>
                        @elseif($isEligible)
                            <span class="badge-active px-3 py-2 text-nowrap" style="font-size: 0.825rem;">
                                <i class="bi bi-check-circle-fill me-1"></i>ELIGIBLE FOR PROMOTION
                            </span>
                        @else
                            <span class="badge-inactive px-3 py-2 text-nowrap" style="font-size: 0.825rem;">
                                <i class="bi bi-clock me-1"></i>IN PROGRESS
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Target Achievement & RD/FD Composition Banner -->
@if(isset($targetAchievement) && $targetAchievement['has_target'])
<div class="card border mb-3 mb-md-4">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h6 class="card-title m-0 fw-bold text-truncate">
                <i class="bi bi-bullseye me-2 text-primary"></i>Target Management: {{ $targetAchievement['current_rank']['name'] ?? 'Current' }} &rarr; {{ $targetAchievement['next_rank']['name'] ?? 'Advancement' }}
            </h6>
        </div>
        <div>
            @if($targetAchievement['is_promotable'])
                <span class="badge bg-success text-white text-nowrap"><i class="bi bi-check-circle me-1"></i>Target Met &amp; Promotable</span>
            @elseif($targetAchievement['remaining_amount'] == 0)
                <span class="badge bg-info text-dark text-nowrap"><i class="bi bi-check me-1"></i>Volume Reached (Check Downline)</span>
            @else
                <span class="badge bg-light text-dark border text-nowrap">Remaining: ₹{{ number_format($targetAchievement['remaining_amount'], 2) }}</span>
            @endif
        </div>
    </div>
    <div class="card-body p-3 p-md-4">
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <span class="text-muted small d-block">Target Amount</span>
                <span class="fs-5 fw-bold text-dark">₹{{ number_format($targetAchievement['target_amount'], 2) }}</span>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted small d-block">Eligible Deposits</span>
                <span class="fs-5 fw-bold text-success">₹{{ number_format($targetAchievement['eligible_deposits'], 2) }}</span>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted small d-block">Remaining</span>
                <span class="fs-5 fw-bold {{ $targetAchievement['remaining_amount'] > 0 ? 'text-danger' : 'text-success' }}">
                    ₹{{ number_format($targetAchievement['remaining_amount'], 2) }}
                </span>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted small d-block">Achievement Progress</span>
                <span class="fs-5 fw-bold text-primary">{{ $targetAchievement['progress_percentage'] }}%</span>
            </div>
        </div>

        <div class="progress mb-3" style="height: 8px;">
            <div class="progress-bar {{ $targetAchievement['progress_percentage'] >= 100 ? 'bg-success' : 'bg-primary' }}"
                 style="width: {{ min(100, $targetAchievement['progress_percentage']) }}%"></div>
        </div>

        <div class="p-2 p-md-3 bg-light border mb-3">
            <div class="row g-2 text-center">
                <div class="col-6 border-end">
                    <div class="text-muted small fw-semibold" style="font-size: 0.72rem;">RD COMPOSITION (MIN 10%)</div>
                    <div class="small mt-1">
                        <strong>₹{{ number_format($targetAchievement['rd_progress']['actual'], 2) }}</strong>
                        <span class="text-muted">/ ₹{{ number_format($targetAchievement['rd_progress']['required'], 2) }}</span>
                    </div>
                    <div class="mt-1">
                        @if($targetAchievement['rd_progress']['is_met'])
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">Met</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">Shortfall: ₹{{ number_format(max(0, $targetAchievement['rd_progress']['required'] - $targetAchievement['rd_progress']['actual']), 2) }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-6">
                    <div class="text-muted small fw-semibold" style="font-size: 0.72rem;">FD COMPOSITION (MAX 90% CAP)</div>
                    <div class="small mt-1">
                        <strong>₹{{ number_format($targetAchievement['fd_progress']['actual'], 2) }}</strong>
                        <span class="text-muted">(Eligible: ₹{{ number_format($targetAchievement['fd_progress']['capped_eligible'], 2) }})</span>
                    </div>
                    <div class="mt-1">
                        <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">Cap: ₹{{ number_format($targetAchievement['fd_progress']['cap_amount'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($targetAchievement['unmet_conditions']))
            <div class="alert alert-warning py-2 px-3 mb-0 border" style="font-size: 0.825rem;">
                <div class="fw-semibold text-warning-emphasis mb-1">
                    <i class="bi bi-exclamation-circle-fill me-1"></i> What's needed to qualify for {{ $targetAchievement['next_rank']['name'] ?? 'next rank' }}:
                </div>
                <ul class="mb-0 ps-3">
                    @foreach($targetAchievement['unmet_conditions'] as $condition)
                        <li>{{ $condition }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endif

<!-- Key Performance & Target Progress Cards (2x2 on mobile) -->
<div class="row g-2 g-sm-3 mb-3 mb-md-4">
    <!-- Team Deposit Target & Achieved -->
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-truncate">Team Business</span>
                    <i class="bi bi-wallet2 text-primary fs-5 flex-shrink-0"></i>
                </div>
                @php
                    $teamReq = (float) ($teamCrit['required'] ?? $performance['deposit_amount']);
                    $teamAct = (float) ($teamCrit['actual'] ?? $performance['deposit_amount']);
                    $teamPct = $teamReq > 0 ? min(100, round(($teamAct / $teamReq) * 100, 1)) : 100;
                @endphp
                <div class="fs-5 fw-bold text-dark text-break">₹{{ number_format($teamAct, 2) }}</div>
                <div class="text-muted small mb-2 text-truncate">
                    Target: @if($teamReq > 0) ₹{{ number_format($teamReq, 2) }} @else Optional @endif
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar {{ $teamPct >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $teamPct }}%"></div>
                </div>
                <div class="d-flex justify-content-between text-muted" style="font-size: 0.725rem; margin-top: 4px;">
                    <span>Progress</span>
                    <span class="fw-semibold">{{ $teamPct }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Qualifying Direct Agents Target & Achieved -->
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-truncate">Direct Agents</span>
                    <i class="bi bi-people text-info fs-5 flex-shrink-0"></i>
                </div>
                @php
                    $facReq = (int) ($facCrit['required'] ?? 0);
                    $facAct = (int) ($facCrit['actual'] ?? 0);
                    $facPct = $facReq > 0 ? min(100, round(($facAct / $facReq) * 100, 1)) : 100;
                @endphp
                <div class="fs-5 fw-bold text-dark">{{ $facAct }} Active</div>
                <div class="text-muted small mb-2 text-truncate">
                    Target: @if($facReq > 0) {{ $facReq }} Agents @else None @endif
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar {{ $facPct >= 100 ? 'bg-success' : 'bg-info' }}" style="width: {{ $facPct }}%"></div>
                </div>
                <div class="d-flex justify-content-between text-muted" style="font-size: 0.725rem; margin-top: 4px;">
                    <span>Progress</span>
                    <span class="fw-semibold">{{ $facPct }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Deposit Mobilized -->
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-truncate">Personal Deposits</span>
                    <i class="bi bi-piggy-bank text-warning fs-5 flex-shrink-0"></i>
                </div>
                @php
                    $indReq = (float) ($indCrit['required'] ?? 0);
                    $indAct = (float) ($indCrit['actual'] ?? $performance['deposit_amount']);
                    $indPct = $indReq > 0 ? min(100, round(($indAct / $indReq) * 100, 1)) : 100;
                @endphp
                <div class="fs-5 fw-bold text-dark text-break">₹{{ number_format($indAct, 2) }}</div>
                <div class="text-muted small mb-2 text-truncate">
                    Target: @if($indReq > 0) ₹{{ number_format($indReq, 2) }} @else Optional @endif
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar {{ $indPct >= 100 ? 'bg-success' : 'bg-warning' }}" style="width: {{ $indPct }}%"></div>
                </div>
                <div class="d-flex justify-content-between text-muted" style="font-size: 0.725rem; margin-top: 4px;">
                    <span>Counts: {{ number_format($performance['deposit_count'] ?? 0) }}</span>
                    <span class="fw-semibold">{{ $indPct }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Customers & Commission -->
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-truncate">Commission</span>
                    <i class="bi bi-cash-stack text-success fs-5 flex-shrink-0"></i>
                </div>
                <div class="fs-5 fw-bold text-success text-break">₹{{ number_format($performance['commission_amount'] ?? 0, 2) }}</div>
                <div class="text-muted small mb-2 text-truncate">
                    Period: {{ $period }}
                </div>
                <div class="small text-muted mt-2 text-truncate">
                    Cust: <span class="fw-semibold text-dark">{{ number_format($performance['customer_count'] ?? 0) }}</span>
                    &bull; Coll: <span class="fw-semibold text-dark">₹{{ number_format($performance['collection_amount'] ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 g-md-4">
    <!-- Next Level Criteria & Eligibility Checklist -->
    <div class="col-lg-7">
        <div class="card border mb-3 mb-md-4">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="card-title m-0 fw-bold text-truncate">
                    <i class="bi bi-bullseye me-2 text-primary"></i>Promotion Eligibility Checklist
                </h6>
                @if($nextRank)
                    <span class="badge bg-light text-dark border text-nowrap">Advancement to {{ $nextRank->name }}</span>
                @endif
            </div>
            <div class="card-body p-3 p-md-4">
                @if($nextRank)
                    <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="min-w-0">
                            <strong>Target Rank:</strong> {{ $nextRank->name }} (Level {{ $nextRank->level ?? $nextRank->sort_order }})
                        </div>
                        <div>
                            @if($hasPending)
                                <span class="badge bg-warning text-dark border text-nowrap"><i class="bi bi-hourglass-split me-1"></i>Request Pending</span>
                            @elseif($isEligible)
                                <span class="badge bg-success text-white text-nowrap"><i class="bi bi-check-circle me-1"></i>All Criteria Met</span>
                            @else
                                <span class="badge bg-secondary text-nowrap">In Progress</span>
                            @endif
                        </div>
                    </div>

                    <!-- Desktop Checklist Table (>= 768px) -->
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-bordered align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Criteria Requirement</th>
                                    <th class="text-center">Required Target</th>
                                    <th class="text-center">Achieved Amount</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $criteriaList = $eligibility['criteria_checklist'] ?? $eligibility['criteria'] ?? [];
                                @endphp
                                @forelse($criteriaList as $key => $check)
                                    @php
                                        $isMet = !empty($check['met']) || !empty($check['achieved']);
                                        $label = $check['label'] ?? ucwords(str_replace('_', ' ', $key));
                                        $unit = !empty($check['unit']) ? ' ' . $check['unit'] : '';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $label }}</div>
                                            @if(isset($check['progress']))
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar {{ $isMet ? 'bg-success' : 'bg-primary' }}" style="width: {{ min(100, $check['progress']) }}%"></div>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(is_numeric($check['required']))
                                                {{ ($check['unit'] ?? '') === '₹' ? '₹' . number_format($check['required'], 2) : number_format($check['required']) . $unit }}
                                            @else
                                                {{ $check['required'] }}
                                            @endif
                                        </td>
                                        <td class="text-center fw-semibold">
                                            @if(is_numeric($check['actual']))
                                                {{ ($check['unit'] ?? '') === '₹' ? '₹' . number_format($check['actual'], 2) : number_format($check['actual']) . $unit }}
                                            @else
                                                {{ $check['actual'] }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($isMet)
                                                <span class="badge bg-success text-white"><i class="bi bi-check-lg me-1"></i>Met</span>
                                            @else
                                                <span class="badge bg-danger text-white"><i class="bi bi-x-lg me-1"></i>Unmet</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No specific criteria configured for this transition.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Checklist Cards (< 768px) -->
                    <div class="d-block d-md-none">
                        @php
                            $criteriaList = $eligibility['criteria_checklist'] ?? $eligibility['criteria'] ?? [];
                        @endphp
                        @forelse($criteriaList as $key => $check)
                        @php
                            $isMet = !empty($check['met']) || !empty($check['achieved']);
                            $label = $check['label'] ?? ucwords(str_replace('_', ' ', $key));
                            $unit = !empty($check['unit']) ? ' ' . $check['unit'] : '';
                            $reqFormatted = is_numeric($check['required']) ? (($check['unit'] ?? '') === '₹' ? '₹' . number_format($check['required'], 2) : number_format($check['required']) . $unit) : $check['required'];
                            $actFormatted = is_numeric($check['actual']) ? (($check['unit'] ?? '') === '₹' ? '₹' . number_format($check['actual'], 2) : number_format($check['actual']) . $unit) : $check['actual'];
                            $progressPct = isset($check['progress']) ? min(100, (float)$check['progress']) : ($isMet ? 100 : 0);
                        @endphp
                        <div class="card border p-3 mb-2">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <span class="fw-bold text-dark" style="font-size: 0.88rem;">{{ $label }}</span>
                                @if($isMet)
                                    <span class="badge bg-success text-white py-1 px-2"><i class="bi bi-check-lg me-1"></i>Met</span>
                                @else
                                    <span class="badge bg-danger text-white py-1 px-2"><i class="bi bi-x-lg me-1"></i>Unmet</span>
                                @endif
                            </div>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar {{ $isMet ? 'bg-success' : 'bg-primary' }}" style="width: {{ $progressPct }}%"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 0.75rem;">
                                <span>Target: <strong class="text-dark">{{ $reqFormatted }}</strong></span>
                                <span>Achieved: <strong class="{{ $isMet ? 'text-success' : 'text-primary' }}">{{ $actFormatted }}</strong> ({{ round($progressPct) }}%)</span>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-3">No specific criteria configured for this transition.</div>
                        @endforelse
                    </div>

                    <!-- Action Banner & Promotion Request Submission -->
                    @if($hasPending)
                        <div class="alert alert-warning border d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-0">
                            <div>
                                <i class="bi bi-hourglass-split me-2 fs-5"></i>
                                <strong>Promotion Request Pending Review</strong> [{{ $pendingRequest->request_number }}]
                                <div class="small text-muted mt-1">
                                    Submitted on {{ $pendingRequest->created_at->format('d M Y') }}. Your Branch Administrator is currently evaluating your performance.
                                </div>
                            </div>
                            <span class="badge bg-warning text-dark border text-nowrap">Awaiting Decision</span>
                        </div>
                    @elseif($isEligible)
                        <div class="mt-4 p-3 bg-success bg-opacity-10 border border-success">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div class="text-success min-w-0">
                                    <h6 class="fw-bold mb-1"><i class="bi bi-stars me-1"></i>Congratulations! You are eligible for promotion!</h6>
                                    <div class="small">You satisfy all targets required to advance to <strong>{{ $nextRank->name }}</strong>. Click below to submit your official promotion request.</div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm px-3 w-100 w-sm-auto text-nowrap" data-bs-toggle="modal" data-bs-target="#requestPromotionModal">
                                    <i class="bi bi-arrow-up-circle me-1"></i> Request Promotion
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 p-3 bg-light border">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div class="text-muted small min-w-0">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>
                                    Complete all required targets above to qualify for promotion to <strong>{{ $nextRank->name }}</strong>.
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm w-100 w-sm-auto text-nowrap" disabled title="Fulfill all criteria to unlock">
                                    <i class="bi bi-lock me-1"></i> Request Promotion
                                </button>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-trophy fs-1 d-block mb-2 text-warning"></i>
                        <h6>You have achieved the highest career rank!</h6>
                        <p class="small mb-0">Outstanding achievement. Continue leading by example.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Career Progression History & Promotion Requests -->
    <div class="col-lg-5">
        <!-- Promotion Requests -->
        <div class="card border mb-3 mb-md-4">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="card-title m-0 fw-bold text-truncate">
                    <i class="bi bi-arrow-up-circle me-2 text-info"></i>Promotion Requests
                </h6>
                <a href="{{ route('agent.promotion-history') }}" class="small text-decoration-none text-nowrap">View All</a>
            </div>
            <div class="card-body p-0">
                @if($promotionRequests->count() > 0)
                <ul class="list-group list-group-flush">
                    @foreach($promotionRequests->take(5) as $req)
                    <li class="list-group-item p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-1">
                            <div class="min-w-0">
                                <span class="fw-bold text-dark">{{ $req->request_number }}</span>
                                <span class="text-muted small ms-1">&rarr; {{ $req->proposedLevel?->name }}</span>
                            </div>
                            <span class="badge {{ $req->getStatusBadgeClass() }} text-nowrap">{{ $req->status }}</span>
                        </div>
                        <div class="small text-muted">
                            Submitted: {{ $req->created_at->format('d M Y') }}
                            @if($req->reviewed_at) &bull; Reviewed: {{ $req->reviewed_at->format('d M Y') }} @endif
                        </div>
                        @if($req->rejection_reason)
                            <div class="small text-danger mt-1 text-break">
                                <i class="bi bi-x-circle me-1"></i>Reason: {{ $req->rejection_reason }}
                            </div>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center py-4 text-muted small p-3">
                    No active promotion requests.
                </div>
                @endif
            </div>
        </div>

        <!-- Career History -->
        <div class="card border">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="card-title m-0 fw-bold text-truncate">
                    <i class="bi bi-clock-history me-2 text-secondary"></i>Rank Elevation History
                </h6>
                <a href="{{ route('agent.promotion-history') }}" class="small text-decoration-none text-nowrap">View Log</a>
            </div>
            <div class="card-body p-0">
                @if($careerHistories->count() > 0)
                <ul class="list-group list-group-flush">
                    @foreach($careerHistories->take(5) as $history)
                    <li class="list-group-item p-3">
                        <div class="fw-semibold text-dark text-truncate">
                            Elevated to {{ $history->newLevel?->name ?? 'Rank' }}
                        </div>
                        <div class="small text-muted text-break">
                            Previous: {{ $history->previousLevel?->name ?? 'None' }}
                            &bull; Date: {{ $history->promotion_date?->format('d M Y') ?? 'N/A' }}
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center py-4 text-muted small p-3">
                    Initial appointment level: {{ $agent->rank?->name ?? 'Facilitator' }}.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal: Submit Promotion Request -->
@if($isEligible && !$hasPending && $nextRank)
<div class="modal fade" id="requestPromotionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('agent.promotion-request.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Submit Promotion Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        You have met all the targets and criteria to advance from <strong>{{ $agent->rank?->name ?? 'Facilitator' }}</strong> to <strong>{{ $nextRank->name }}</strong>.
                    </p>
                    <div class="p-3 bg-light border mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Target Rank:</span>
                            <strong class="text-success">{{ $nextRank->name }} (Rank {{ $nextRank->level ?? $nextRank->sort_order }})</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Scope:</span>
                            <span>Free Field Worker</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Reviewing Authority:</span>
                            <span>Branch Administrator</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes / Recommendation to Branch Admin (Optional)</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Add any details about your field performance, customer network, or team contribution..."></textarea>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-wrap justify-content-end gap-2">
                    <button type="button" class="btn btn-light w-100 w-sm-auto" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success w-100 w-sm-auto">
                        <i class="bi bi-send me-1"></i> Confirm & Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
