@extends('superadmin.layouts.app')

@section('page-title', 'FD Commission Management')

@section('content')
<style>
    /* ─── FD Commission Matrix Enterprise Styling ──────────────────────────── */
    .fd-matrix-card .table-responsive {
        border: none;
        border-radius: 0;
        box-shadow: none;
        background: transparent;
    }
    .fd-matrix-card .table th {
        padding: 11px 14px;
    }
    .fd-matrix-card .table td {
        padding: 12px 14px;
        vertical-align: middle;
    }

    /* Filter alignment */
    .fd-filter-bar .form-control-sm,
    .fd-filter-bar .form-select-sm,
    .fd-filter-bar .btn-sm {
        height: 34px;
    }
    .fd-search-wrap {
        position: relative;
        min-width: 220px;
        flex: 1 1 240px;
    }
    .fd-search-wrap .search-icon {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 0.8rem;
        pointer-events: none;
    }
    .fd-search-wrap .form-control {
        padding-left: 32px;
    }

    /* Scheme Icon Avatar */
    .scheme-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .scheme-icon-box.icon-fd {
        background: #ecfdf5;
        color: #14532d;
        border: 1px solid #a7f3d0;
    }
    .scheme-icon-box.icon-rd {
        background: #fff7ed;
        color: #9a3412;
        border: 1px solid #fed7aa;
    }
    .scheme-icon-box.icon-dd {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .scheme-icon-box.icon-mis {
        background: #f0f9ff;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .scheme-icon-box.icon-double {
        background: #faf5ff;
        color: #6b21a8;
        border: 1px solid #e9d5ff;
    }
    .scheme-icon-box.icon-default {
        background: var(--surface-subtle, #f8fafc);
        color: var(--text-muted, #475569);
        border: 1px solid var(--border-color, #e2e8f0);
    }

    html.dark .scheme-icon-box.icon-fd {
        background: rgba(16, 185, 129, 0.16);
        color: #34d399;
        border-color: rgba(52, 211, 153, 0.32);
    }
    html.dark .scheme-icon-box.icon-rd {
        background: rgba(251, 146, 60, 0.16);
        color: #fdba74;
        border-color: rgba(251, 146, 60, 0.34);
    }
    html.dark .scheme-icon-box.icon-dd {
        background: rgba(245, 158, 11, 0.16);
        color: #fbbf24;
        border-color: rgba(251, 191, 36, 0.32);
    }
    html.dark .scheme-icon-box.icon-mis {
        background: rgba(14, 165, 233, 0.16);
        color: #38bdf8;
        border-color: rgba(56, 189, 248, 0.32);
    }
    html.dark .scheme-icon-box.icon-double {
        background: rgba(168, 85, 247, 0.16);
        color: #c084fc;
        border-color: rgba(192, 132, 252, 0.32);
    }

    /* Type Pills */
    .scheme-type-pill {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }
    .scheme-type-pill.type-fd {
        background: #ecfdf5;
        color: #14532d;
        border: 1px solid #a7f3d0;
    }
    .scheme-type-pill.type-rd {
        background: #fff7ed;
        color: #9a3412;
        border: 1px solid #fed7aa;
    }
    .scheme-type-pill.type-dd {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .scheme-type-pill.type-mis {
        background: #f0f9ff;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .scheme-type-pill.type-double {
        background: #faf5ff;
        color: #6b21a8;
        border: 1px solid #e9d5ff;
    }
    .scheme-type-pill.type-default {
        background: var(--surface-subtle, #f8fafc);
        color: var(--text-body, #334155);
        border: 1px solid var(--border-color, #e2e8f0);
    }

    html.dark .scheme-type-pill.type-fd {
        background: rgba(16, 185, 129, 0.16);
        color: #34d399;
        border-color: rgba(52, 211, 153, 0.32);
    }
    html.dark .scheme-type-pill.type-rd {
        background: rgba(251, 146, 60, 0.16);
        color: #fdba74;
        border-color: rgba(251, 146, 60, 0.34);
    }
    html.dark .scheme-type-pill.type-dd {
        background: rgba(245, 158, 11, 0.16);
        color: #fbbf24;
        border-color: rgba(251, 191, 36, 0.32);
    }
    html.dark .scheme-type-pill.type-mis {
        background: rgba(14, 165, 233, 0.16);
        color: #38bdf8;
        border-color: rgba(56, 189, 248, 0.32);
    }
    html.dark .scheme-type-pill.type-double {
        background: rgba(168, 85, 247, 0.16);
        color: #c084fc;
        border-color: rgba(192, 132, 252, 0.32);
    }

    /* Micro chips inside table cells */
    .scheme-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 5px;
        background: var(--surface-subtle);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        white-space: nowrap;
    }
    .rate-pill-label {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 0.68rem;
        font-weight: 600;
        padding: 1px 6px;
        border-radius: 4px;
        white-space: nowrap;
    }
    .rate-pill-standard {
        background: var(--surface-subtle);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
    }
    .rate-pill-senior {
        background: #f0f9ff;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .rate-pill-double {
        background: #faf5ff;
        color: #6b21a8;
        border: 1px solid #e9d5ff;
    }
    html.dark .rate-pill-senior {
        background: rgba(14, 165, 233, 0.16);
        color: #38bdf8;
        border-color: rgba(56, 189, 248, 0.32);
    }
    html.dark .rate-pill-double {
        background: rgba(168, 85, 247, 0.16);
        color: #c084fc;
        border-color: rgba(192, 132, 252, 0.32);
    }

    .comm-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        text-decoration: none;
        white-space: nowrap;
    }
    .comm-status-pill.pending-setup {
        background: var(--status-pending-bg);
        color: var(--status-pending-text);
        border: 1px solid var(--status-pending-border);
    }
    .comm-status-pill.pending-setup:hover {
        filter: brightness(0.96);
    }

    /* Crisp Table Action Buttons */
    .fd-action-btn {
        width: 30px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        font-size: 0.82rem;
    }
</style>

{{-- Page Header --}}
<div class="page-header">
    <div>
        <h1 class="page-title">FD Commission Management</h1>
        <div class="page-breadcrumb">Commission / Deposit Schemes Matrix</div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('admin.deposit-schemes.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-journal-bookmark"></i>
            <span>Deposit Schemes</span>
        </a>
        <a href="{{ route('superadmin.fd-commissions.create') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-plus-lg"></i>
            <span>Add Commission Scheme</span>
        </a>
    </div>
</div>

{{-- Notifications --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Metric Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon brand"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div>
                <div class="stat-value">{{ number_format($totalCount) }}</div>
                <div class="stat-label">Total Schemes</div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                    {{ number_format($activeCount) }} active &bull; {{ number_format($inactiveCount) }} inactive
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon emerald"><i class="bi bi-diagram-3-fill"></i></div>
            <div>
                <div class="stat-value text-success">{{ number_format($configuredCount) }}</div>
                <div class="stat-label">Configured Commissions</div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                    @if(($totalCount - $configuredCount) > 0)
                        {{ number_format($totalCount - $configuredCount) }} pending setup
                    @else
                        All schemes configured
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-pause-circle-fill"></i></div>
            <div>
                <div class="stat-value">{{ number_format($inactiveCount) }}</div>
                <div class="stat-label">Inactive Schemes</div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                    {{ number_format($activeCount) }} currently active
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-percent"></i></div>
            <div>
                <div class="stat-value text-primary">{{ number_format($avgCommission, 2) }}%</div>
                <div class="stat-label">Avg Commission Rate</div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                    @if($configuredCount > 0)
                        Across {{ number_format($configuredCount) }} configured {{ Str::plural('scheme', $configuredCount) }}
                    @else
                        No commission pools set yet
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3 fd-filter-bar">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('superadmin.fd-commissions.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
            <div class="fd-search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input
                    type="text"
                    name="search"
                    class="form-control form-control-sm"
                    placeholder="Search scheme name, code…"
                    value="{{ request('search') }}"
                >
            </div>
            <div style="min-width: 165px;">
                <select name="scheme_type" class="form-select form-select-sm">
                    <option value="">All Scheme Types</option>
                    @foreach($schemeTypes as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}" {{ strtoupper((string)request('scheme_type')) === strtoupper((string)$typeKey) ? 'selected' : '' }}>
                            {{ $typeLabel }} ({{ $typeCounts[$typeKey] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 155px;">
                <select name="commission_status" class="form-select form-select-sm">
                    <option value="">All Commissions</option>
                    <option value="configured" {{ strtolower((string)request('commission_status')) === 'configured' ? 'selected' : '' }}>Configured ({{ $configuredCount }})</option>
                    <option value="unconfigured" {{ strtolower((string)request('commission_status')) === 'unconfigured' ? 'selected' : '' }}>Not Configured ({{ max(0, $totalCount - $configuredCount) }})</option>
                </select>
            </div>
            <div style="min-width: 140px;">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE"   {{ strtoupper((string)request('status')) === 'ACTIVE'   ? 'selected' : '' }}>Active ({{ $activeCount }})</option>
                    <option value="INACTIVE" {{ strtoupper((string)request('status')) === 'INACTIVE' ? 'selected' : '' }}>Inactive ({{ $inactiveCount }})</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'scheme_type', 'status', 'commission_status']))
                    <a href="{{ route('superadmin.fd-commissions.index') }}" class="btn btn-outline-dark btn-sm" title="Clear Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Schemes Table Card --}}
<div class="card fd-matrix-card">
    <div class="card-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-table text-primary"></i>
            <span>Deposit Commission Matrix</span>
        </div>
        <span class="scheme-meta-chip num-tabular">
            {{ $schemes->total() }} {{ Str::plural('Scheme', $schemes->total()) }}
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 48px;" class="ps-3">#</th>
                        <th style="min-width: 230px;">Scheme</th>
                        <th style="width: 90px;">Type</th>
                        <th style="min-width: 130px;">Tenure &amp; Target</th>
                        <th style="min-width: 175px;">Interest Rates</th>
                        <th style="min-width: 180px;">Commission Matrix</th>
                        <th style="width: 95px;">Status</th>
                        <th class="text-end pe-3" style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schemes as $scheme)
                        @php
                            $st = strtoupper((string)($scheme->scheme_type ?? 'FD'));
                            $isDouble = $scheme->isDouble();
                            if ($isDouble || $st === 'DOUBLE') {
                                $typeClass = 'type-double';
                                $iconClass = 'icon-double';
                                $iconName  = 'bi-stars';
                            } elseif ($st === 'FD') {
                                $typeClass = 'type-fd';
                                $iconClass = 'icon-fd';
                                $iconName  = 'bi-shield-check';
                            } elseif ($st === 'RD') {
                                $typeClass = 'type-rd';
                                $iconClass = 'icon-rd';
                                $iconName  = 'bi-arrow-repeat';
                            } elseif ($st === 'DD') {
                                $typeClass = 'type-dd';
                                $iconClass = 'icon-dd';
                                $iconName  = 'bi-calendar-check';
                            } elseif ($st === 'MIS') {
                                $typeClass = 'type-mis';
                                $iconClass = 'icon-mis';
                                $iconName  = 'bi-wallet2';
                            } else {
                                $typeClass = 'type-default';
                                $iconClass = 'icon-default';
                                $iconName  = 'bi-journal-bookmark';
                            }

                            $canDelete = ($scheme->deposits_count == 0 && $scheme->commission_ledgers_count == 0);

                            $tenureUnitRaw = strtolower((string) ($scheme->tenure_unit ?? 'months'));
                            $tenureUnitLabel = (int) $scheme->tenure === 1
                                ? ucfirst(rtrim($tenureUnitRaw, 's'))
                                : ucfirst($tenureUnitRaw);

                            $applicableRate = (float) $scheme->getInterestRateForTenure((int) $scheme->tenure, $scheme->tenure_unit);
                            $seniorExtra = (float) $scheme->getSeniorCitizenExtraRateForTenure((int) $scheme->tenure, $scheme->tenure_unit);
                            $seniorTotal = $applicableRate + $seniorExtra;

                            $eligibleComms = $scheme->rankCommissions->where('is_eligible', true);
                            $hasCommissionRules = $scheme->rankCommissions->isNotEmpty();
                            $eligibleRanksCount = $eligibleComms->pluck('rank_id')->unique()->count();
                            $activePayoutYears = $eligibleComms
                                ->pluck('payout_year')
                                ->map(fn($y) => (int)($y ?: 1))
                                ->unique()
                                ->sort()
                                ->values();
                            $yearCount = $activePayoutYears->count();
                            $primaryYear = $activePayoutYears->first() ?? max(1, $scheme->getTenureInYears());
                            $primaryYearCommission = (float) $eligibleComms
                                ->filter(fn($rc) => (int)($rc->payout_year ?: 1) === (int) $primaryYear)
                                ->sum('commission_percentage');
                            $totalCommission = (float) $scheme->total_commission_percentage;
                            $isConfigured = $hasCommissionRules || $totalCommission > 0;
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted small num-tabular">{{ $schemes->firstItem() + $loop->index }}</td>

                            {{-- Scheme Name, Avatar & Code --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="scheme-icon-box {{ $iconClass }}">
                                        <i class="bi {{ $iconName }}"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('superadmin.fd-commissions.show', $scheme) }}" class="text-decoration-none fw-semibold text-dark d-block text-truncate" style="font-size: 0.875rem; max-width: 240px;" title="{{ $scheme->name }}">
                                            {{ $scheme->name }}
                                        </a>
                                        <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                            <code>{{ $scheme->code }}</code>
                                            @if($isDouble)
                                                <span class="rate-pill-label rate-pill-double">2&times; Maturity</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Type --}}
                            <td>
                                <span class="scheme-type-pill {{ $typeClass }}">{{ $st }}</span>
                            </td>

                            {{-- Tenure & Target Credit --}}
                            <td>
                                <div class="fw-semibold text-dark num-tabular">
                                    {{ $scheme->tenure }}
                                    <span class="text-muted fw-normal small">{{ $tenureUnitLabel }}</span>
                                </div>
                                <div class="mt-1">
                                    <span class="scheme-meta-chip" title="Volume credited toward Agent Rank Promotion Targets">
                                        <i class="bi bi-trophy text-warning"></i>
                                        <span>{{ number_format((float) $scheme->getPromotionTargetPercentage(), 1) }}% Target</span>
                                    </span>
                                </div>
                            </td>

                            {{-- Interest Rates (Standard + Senior Citizen) --}}
                            <td>
                                @if($isDouble)
                                    <div class="d-flex flex-column gap-1">
                                        <span class="rate-pill-label rate-pill-double align-self-start">
                                            <i class="bi bi-stars"></i> Double Feature
                                        </span>
                                        <span class="text-muted small" style="font-size: 0.72rem;">Doubles principal at maturity</span>
                                    </div>
                                @else
                                    <div class="d-flex flex-column gap-1">
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <span class="rate-pill-label rate-pill-standard">Standard</span>
                                            <span class="fw-bold text-dark font-monospace num-tabular" style="font-size: 0.875rem;">
                                                {{ number_format($applicableRate, 2) }}% <span class="text-muted fw-normal" style="font-size: 0.7rem;">p.a.</span>
                                            </span>
                                        </div>
                                        @if($seniorExtra > 0)
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <span class="rate-pill-label rate-pill-senior">
                                                    <i class="bi bi-person-plus"></i> Senior
                                                </span>
                                                <span class="fw-semibold text-primary font-monospace num-tabular" style="font-size: 0.8rem;">
                                                    {{ number_format($seniorTotal, 2) }}%
                                                    <span class="text-muted fw-normal" style="font-size: 0.68rem;">(+{{ number_format($seniorExtra, 2) }}%)</span>
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Commission Matrix --}}
                            <td>
                                @if(!$isConfigured)
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <a href="{{ route('superadmin.fd-commissions.edit', $scheme) }}" class="comm-status-pill pending-setup" title="Click to configure rank commissions">
                                            <i class="bi bi-sliders"></i>
                                            <span>Not Configured</span>
                                        </a>
                                        <span class="text-muted" style="font-size: 0.72rem;">
                                            0/{{ $totalRanksCount }} ranks set
                                        </span>
                                    </div>
                                @else
                                    <div class="d-flex flex-column">
                                        <div class="d-flex align-items-baseline gap-1">
                                            <span class="fw-bold text-success font-monospace num-tabular" style="font-size: 0.94rem;">
                                                {{ number_format($totalCommission, 2) }}%
                                            </span>
                                            <span class="text-muted small" style="font-size: 0.72rem;">Pool</span>
                                        </div>
                                        @if($yearCount > 1)
                                            <span class="text-muted" style="font-size: 0.73rem; line-height: 1.3;">
                                                Yr {{ $primaryYear }}: {{ number_format($primaryYearCommission, 2) }}% &bull; {{ $yearCount }} yrs &bull; {{ $eligibleRanksCount }}/{{ $totalRanksCount }} ranks
                                            </span>
                                        @else
                                            <span class="text-muted" style="font-size: 0.73rem; line-height: 1.3;">
                                                Year {{ $primaryYear }} &bull; {{ $eligibleRanksCount }}/{{ $totalRanksCount }} {{ Str::plural('rank', $eligibleRanksCount) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td>
                                @if($scheme->isActive())
                                    <span class="badge-active">Active</span>
                                @else
                                    <span class="badge-inactive">Inactive</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="text-end pe-3 text-nowrap">
                                <div class="d-inline-flex align-items-center justify-content-end gap-1 flex-nowrap">
                                    <a href="{{ route('superadmin.fd-commissions.show', $scheme) }}" class="btn btn-outline-dark fd-action-btn" title="View Commission Matrix">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('superadmin.fd-commissions.edit', $scheme) }}" class="btn btn-outline-primary fd-action-btn" title="Edit / Configure Commission">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="{{ route('superadmin.fd-commissions.create', ['clone_from' => $scheme->id]) }}" class="btn btn-outline-secondary fd-action-btn" title="Clone Scheme">
                                        <i class="bi bi-copy"></i>
                                    </a>
                                    <form id="toggle-form-{{ $scheme->id }}" action="{{ route('superadmin.fd-commissions.toggle-status', $scheme) }}" method="POST" class="d-inline m-0"
                                          data-confirm="Are you sure you want to {{ $scheme->isActive() ? 'deactivate' : 'activate' }} {{ addslashes($scheme->name) }}?"
                                          data-confirm-title="{{ $scheme->isActive() ? 'Deactivate Scheme' : 'Activate Scheme' }}"
                                          data-confirm-type="{{ $scheme->isActive() ? 'warning' : 'success' }}"
                                          data-confirm-btn="{{ $scheme->isActive() ? 'Deactivate' : 'Activate' }}">
                                        @csrf
                                        <button type="submit"
                                                class="btn fd-action-btn {{ $scheme->isActive() ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                title="{{ $scheme->isActive() ? 'Deactivate Scheme' : 'Activate Scheme' }}">
                                            <i class="bi bi-{{ $scheme->isActive() ? 'pause-circle' : 'play-circle' }}"></i>
                                        </button>
                                    </form>
                                    @if($canDelete)
                                        <button type="button" class="btn btn-outline-danger fd-action-btn btn-delete-scheme"
                                                title="Delete Scheme"
                                                data-action="{{ route('superadmin.fd-commissions.destroy', $scheme) }}"
                                                data-name="{{ $scheme->name }}"
                                                data-code="{{ $scheme->code }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-journal-x d-block mb-2" style="font-size: 2rem; opacity: 0.5;"></i>
                                <div class="fw-medium">No deposit schemes found matching your criteria.</div>
                                <div class="mt-2">
                                    <a href="{{ route('superadmin.fd-commissions.create') }}" class="btn btn-dark btn-sm">
                                        <i class="bi bi-plus-lg me-1"></i> Add Commission Scheme
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($schemes->hasPages())
        <div class="card-footer bg-transparent border-top" style="padding: 12px 20px;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted" style="font-size: 0.775rem;">
                    Showing {{ $schemes->firstItem() }}–{{ $schemes->lastItem() }} of {{ $schemes->total() }} schemes
                </div>
                {{ $schemes->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @elseif($schemes->total() > 0)
        <div class="card-footer bg-transparent border-top" style="padding: 12px 20px;">
            <div class="text-muted" style="font-size: 0.775rem;">
                Showing {{ $schemes->total() }} {{ Str::plural('scheme', $schemes->total()) }}
            </div>
        </div>
    @endif
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteSchemeModal" tabindex="-1" aria-labelledby="deleteSchemeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border" style="background: var(--card-bg);">
            <div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-danger-subtle text-danger border border-danger-subtle rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 1rem;">
                        <i class="bi bi-trash3-fill"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="deleteSchemeModalLabel">Delete Deposit Scheme</h6>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="p-3 my-2 rounded border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Scheme Name:</span>
                        <span class="fw-semibold text-dark text-end" id="deleteModalSchemeName">—</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">Scheme Code:</span>
                        <code class="border px-2 py-1 text-dark font-monospace" id="deleteModalSchemeCode" style="font-size: 0.8rem;">—</code>
                    </div>
                </div>
                <div class="alert alert-danger py-2 px-3 small border mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <div>This scheme has 0 active deposit accounts. Rank commission configurations will be removed.</div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-dark btn-sm px-3" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 500;">
                    Cancel
                </button>
                <form id="deleteSchemeForm" method="POST" action="" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="force" value="1">
                    <button type="submit" class="btn btn-danger btn-sm px-3 d-flex align-items-center gap-1" style="border-radius: 8px; font-weight: 500; background: #DC2626; border-color: #DC2626;">
                        <i class="bi bi-trash me-1"></i> Delete Scheme
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete Scheme Modal Logic
    const deleteModalEl = document.getElementById('deleteSchemeModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const deleteForm = document.getElementById('deleteSchemeForm');
    const deleteName = document.getElementById('deleteModalSchemeName');
    const deleteCode = document.getElementById('deleteModalSchemeCode');

    document.querySelectorAll('.btn-delete-scheme').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const name = this.getAttribute('data-name');
            const code = this.getAttribute('data-code');

            if (deleteForm) deleteForm.action = action;
            if (deleteName) deleteName.textContent = name;
            if (deleteCode) deleteCode.textContent = code || '—';

            if (deleteModal) deleteModal.show();
        });
    });
});
</script>
@endpush
@endsection
