@extends('superadmin.layouts.app')

@section('page-title', 'Promotion Rules')

@section('content')
<style>
    /* ─── Enterprise Banking Architecture & Styling ─── */
    .bank-tabular-num {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }

    /* Subdued Institutional Metadata Strip */
    .bank-meta-strip {
        background: var(--card-bg, #FFFFFF);
        border: 1px solid var(--border-color, #E2E8F0);
        border-radius: 6px;
        padding: 10px 16px;
        font-size: 0.8125rem;
    }
    .bank-meta-divider {
        color: var(--border-strong, #CBD5E1);
        user-select: none;
    }

    /* Search & Filter Toolbar */
    .bank-filter-toolbar {
        background: var(--card-bg, #FFFFFF);
        border: 1px solid var(--border-color, #E2E8F0);
        border-radius: 6px;
        padding: 12px 14px;
    }
    .bank-search-wrap {
        position: relative;
    }
    .bank-search-wrap i.search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-subtle, #94A3B8);
        font-size: 0.825rem;
        pointer-events: none;
    }
    .bank-search-wrap input {
        padding-left: 32px;
        background-color: var(--card-bg, #FFFFFF);
        border: 1px solid var(--border-strong, #CBD5E1);
        border-radius: 6px;
        font-size: 0.8125rem;
        height: 34px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .bank-search-wrap input:focus {
        border-color: var(--color-primary, #14532D);
        box-shadow: none;
    }

    /* Filter Chips */
    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 8px;
        font-size: 0.72rem;
        border-radius: 4px;
        background: var(--surface-subtle, #F8FAFC);
        color: var(--text-body, #1E293B);
        border: 1px solid var(--border-strong, #CBD5E1);
        line-height: 1.4;
    }
    .filter-chip-remove {
        cursor: pointer;
        color: var(--text-muted, #64748B);
        line-height: 1;
        font-size: 0.95rem;
    }
    .filter-chip-remove:hover {
        color: #DC2626;
    }

    /* Table & Action Buttons */
    .rule-action-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-size: 0.8rem;
        border-color: var(--border-strong, #CBD5E1);
        background: var(--card-bg, #FFFFFF);
        color: var(--text-body, #334155);
        transition: background-color 0.1s ease, border-color 0.1s ease, color 0.1s ease;
    }
    .rule-action-btn:hover {
        background: var(--surface-subtle, #F1F5F9);
        color: var(--text-heading, #0F172A);
        border-color: var(--border-strong, #94A3B8);
    }
    .rule-title-link {
        color: var(--text-heading, #0F172A);
        text-decoration: none;
        transition: color 0.12s ease;
    }
    .rule-title-link:hover {
        color: var(--color-primary, #14532D);
        text-decoration: underline;
    }
    .rule-row:hover {
        background-color: var(--surface-subtle, #F8FAFC) !important;
    }

    /* Status Pills */
    .bank-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 8px;
        font-size: 0.72rem;
        font-weight: 600;
        border-radius: 9999px;
        line-height: 1.2;
        letter-spacing: 0.01em;
        white-space: nowrap;
        vertical-align: middle;
    }
    .bank-pill-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    .bank-pill-active {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
    }
    .bank-pill-active .bank-pill-dot {
        background: #16A34A;
    }
    .bank-pill-inactive {
        background: #F1F5F9;
        color: #64748B;
        border: 1px solid #CBD5E1;
    }
    .bank-pill-inactive .bank-pill-dot {
        background: #94A3B8;
    }
    html.dark .bank-pill-active {
        background: rgba(16, 185, 129, 0.16);
        color: #34D399;
        border-color: rgba(52, 211, 153, 0.32);
    }
    html.dark .bank-pill-active .bank-pill-dot {
        background: #34D399;
    }
    html.dark .bank-pill-inactive {
        background: rgba(148, 163, 184, 0.12);
        color: #94A3B8;
        border-color: rgba(148, 163, 184, 0.25);
    }

    /* Split Bar */
    .split-track {
        height: 6px;
        width: 100%;
        background: var(--border-color, #E2E8F0);
        border-radius: 3px;
        overflow: hidden;
        display: flex;
    }
    .split-fill-rd {
        background: var(--color-primary, #14532D);
        height: 100%;
        transition: width 0.2s ease;
    }
    .split-fill-fd {
        background: var(--color-copper, #C2410C);
        height: 100%;
        transition: width 0.2s ease;
    }

    .tenure-target-box {
        background: var(--surface-subtle, #F8FAFC);
        border: 1px solid var(--border-color, #E2E8F0);
        border-radius: 6px;
        padding: 8px 10px;
    }
    .tenure-target-box:focus-within {
        border-color: var(--color-primary, #14532D);
        background: var(--card-bg, #FFFFFF);
    }
</style>

<!-- 1. Executive Banking Header -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h1 class="h4 fw-bold text-dark mb-1" style="letter-spacing: -0.02em;">
            Promotion Rules
        </h1>
        <p class="text-muted mb-0" style="font-size: 0.8125rem;">
            Configure career level advancement targets, deposit composition ratios, and governance requirements.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('superadmin.agent-ranks.index') }}" class="btn btn-outline-secondary btn-sm" style="font-size: 0.8125rem; font-weight: 500; padding: 6px 12px; border-radius: 6px;">
            <i class="bi bi-award me-1 text-secondary"></i>Agent Ranks
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGlobalPromotionTargets" style="font-size: 0.8125rem; font-weight: 500; padding: 6px 12px; border-radius: 6px;">
            <i class="bi bi-sliders me-1 text-secondary"></i>Policy Parameters
        </button>
        <a href="{{ route('superadmin.promotion-rules.create') }}" id="modalAddPromotionRule" class="btn btn-primary btn-sm" style="font-size: 0.8125rem; font-weight: 600; padding: 6px 14px; border-radius: 6px;">
            <i class="bi bi-plus-lg me-1"></i>Add Promotion Rule
        </a>
    </div>
</div>

{{-- Flash Alerts --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 border-0 py-2 px-3" role="alert" style="border-radius: 6px; background: #DCFCE7; color: #166534; border-left: 3px solid #16A34A !important; font-size: 0.8125rem;">
    <div class="d-flex align-items-center">
        <i class="bi bi-check-circle-fill me-2 text-success"></i>
        <div class="fw-medium">{{ session('success') }}</div>
    </div>
    <button type="button" class="btn-close shadow-none py-2" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3 border py-2 px-3" role="alert" style="border-radius: 6px; border-left: 3px solid #DC2626 !important; font-size: 0.8125rem;">
    <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>
        <div class="fw-medium">{{ session('error') }}</div>
    </div>
    <button type="button" class="btn-close shadow-none py-2" data-bs-dismiss="alert"></button>
</div>
@endif

@php
    $gt = $globalTargets['tenure_target_percentages'] ?? \App\Models\PromotionRule::DEFAULT_TENURE_TARGETS;
    $rdComp = $globalTargets['rd_composition_percentage'] ?? 10.0;
    $fdComp = $globalTargets['fd_composition_percentage'] ?? 90.0;
    $tenureTargetsOn = $globalTargets['apply_tenure_targets'] ?? false;
    $currentFy = $financialYears->where('is_current', true)->first();
@endphp

<!-- 2. Institutional Metadata & Policy Overview Strip -->
<div class="bank-meta-strip d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="d-flex flex-wrap align-items-center gap-2.5 text-secondary">
        <span class="d-inline-flex align-items-center gap-1 px-2 py-0.5 rounded text-dark fw-semibold" style="background: var(--surface-subtle); border: 1px solid var(--border-color); font-size: 0.72rem;">
            <i class="bi bi-shield-check text-success"></i> Promotion Targets Policy
        </span>
        <span class="text-dark fw-medium d-none d-lg-inline" style="font-size: 0.8125rem;">
            Dynamic Promotion Targets &amp; Compulsory Conditions
        </span>
        <span class="bank-meta-divider d-none d-lg-inline">|</span>
        <div>
            <span class="text-muted">Total Pathways:</span>
            <strong class="text-dark ms-1 bank-tabular-num">{{ number_format($totalCount ?? $rules->total()) }}</strong>
        </div>
        <span class="bank-meta-divider">|</span>
        <div>
            <span class="text-muted">Active:</span>
            <strong class="text-success ms-1 bank-tabular-num">{{ number_format($activeCount ?? 0) }}</strong>
        </div>
        <span class="bank-meta-divider">|</span>
        <div>
            <span class="text-muted">Auto-Promote:</span>
            <strong class="text-dark ms-1 bank-tabular-num">{{ $autoPromoteCount }}</strong>
        </div>
        <span class="bank-meta-divider">|</span>
        <div>
            <span class="text-muted">Cycle:</span>
            <strong class="text-dark ms-1">{{ $currentFy?->code ?? 'Current FY' }}</strong>
        </div>
        <span class="bank-meta-divider">|</span>
        <div>
            <span class="text-muted">Split:</span>
            <span class="ms-1 fw-semibold text-dark">
                <span style="color: var(--color-primary, #14532D);">{{ number_format($rdComp, 0) }}% RD</span>
                <span class="text-muted mx-0.5">/</span>
                <span style="color: var(--color-copper, #C2410C);">{{ number_format($fdComp, 0) }}% FD</span>
            </span>
        </div>
        <span class="bank-meta-divider">|</span>
        <div>
            <span class="text-muted">Multipliers:</span>
            <strong class="ms-1 {{ $tenureTargetsOn ? 'text-success' : 'text-muted' }}">
                {{ $tenureTargetsOn ? 'Weighted' : 'Flat' }}
            </strong>
        </div>
    </div>
    <div>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGlobalPromotionTargets" style="font-size: 0.75rem; padding: 3px 9px; border-radius: 4px;">
            <i class="bi bi-gear me-1"></i>Configure Policy
        </button>
    </div>
</div>

<!-- 3. Search & Filter Console Toolbar -->
<div class="bank-filter-toolbar mb-3">
    <form id="promotion-rules-filter-form" method="GET" action="{{ route('superadmin.promotion-rules.index') }}">
        <div class="row g-2 align-items-center">
            <!-- Search Input -->
            <div class="col-12 col-md-4 col-lg-3">
                <div class="bank-search-wrap">
                    <i class="bi bi-search search-icon"></i>
                    <input
                        type="text"
                        id="filter-search"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Search rules, ranks, roles..."
                        value="{{ request('search') }}"
                        style="border-radius: 6px; font-size: 0.8125rem; height: 34px; border-color: var(--border-strong);"
                    >
                </div>
            </div>

            <!-- Financial Year -->
            <div class="col-6 col-md-4 col-lg-2">
                <select id="filter-financial-year" name="financial_year_id" class="form-select form-select-sm" style="border-radius: 6px; font-size: 0.8125rem; height: 34px; border-color: var(--border-strong);">
                    <option value="">All Operating Cycles</option>
                    <option value="global" {{ request('financial_year_id') === 'global' ? 'selected' : '' }}>Permanent Global</option>
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}" {{ (string) request('financial_year_id') === (string) $fy->id ? 'selected' : '' }}>
                            {{ $fy->code }} {{ $fy->is_current ? '(Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- From Rank -->
            <div class="col-6 col-md-4 col-lg-2">
                <select id="filter-from-rank" name="from_rank_id" class="form-select form-select-sm" style="border-radius: 6px; font-size: 0.8125rem; height: 34px; border-color: var(--border-strong);">
                    <option value="">From: All Ranks</option>
                    @foreach($ranks as $rank)
                        <option value="{{ $rank->id }}" {{ (string) request('from_rank_id') === (string) $rank->id ? 'selected' : '' }}>
                            L{{ $rank->level ?? $rank->sort_order }} {{ $rank->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- To Rank -->
            <div class="col-6 col-md-4 col-lg-2">
                <select id="filter-to-rank" name="to_rank_id" class="form-select form-select-sm" style="border-radius: 6px; font-size: 0.8125rem; height: 34px; border-color: var(--border-strong);">
                    <option value="">To: All Ranks</option>
                    @foreach($ranks as $rank)
                        <option value="{{ $rank->id }}" {{ (string) request('to_rank_id') === (string) $rank->id ? 'selected' : '' }}>
                            L{{ $rank->level ?? $rank->sort_order }} {{ $rank->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div class="col-6 col-md-2 col-lg-1">
                <select id="filter-status" name="status" class="form-select form-select-sm" style="border-radius: 6px; font-size: 0.8125rem; height: 34px; border-color: var(--border-strong);">
                    <option value="">Status</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <!-- Action Controls -->
            <div class="col-12 col-md-6 col-lg-2 d-flex gap-1.5 align-items-center justify-content-end ms-auto">
                <button type="button" id="btn-toggle-advanced-filters" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" style="border-radius: 6px; height: 34px; padding: 0 10px; font-size: 0.8125rem;" title="More Filters">
                    <i class="bi bi-funnel"></i>
                    <span>More</span>
                </button>
                <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1" style="border-radius: 6px; height: 34px; padding: 0 12px; font-size: 0.8125rem; font-weight: 500;">
                    <i class="bi bi-filter"></i>
                    <span>Apply</span>
                </button>
                <a href="{{ route('superadmin.promotion-rules.index') }}" id="filter-clear-all" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center {{ request()->hasAny(['search','status','from_rank_id','to_rank_id','period','approval_role','financial_year_id']) ? '' : 'd-none' }}" style="border-radius: 6px; height: 34px; width: 34px; padding: 0;" title="Clear filters">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </div>

        <!-- Secondary Advanced Filters -->
        <div id="advanced-filters-row" class="row g-2 mt-2 pt-2 border-top {{ request()->hasAny(['period','approval_role']) ? '' : 'd-none' }}" style="border-color: var(--border-color) !important;">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <label for="filter-period" class="form-label text-muted mb-1" style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Calculation Horizon</label>
                <select id="filter-period" name="period" class="form-select form-select-sm" style="border-radius: 6px; font-size: 0.8125rem; height: 34px; border-color: var(--border-strong);">
                    <option value="">All Calculation Periods</option>
                    @foreach($calculationPeriods as $pKey => $pLabel)
                        <option value="{{ $pKey }}" {{ request('period') === $pKey ? 'selected' : '' }}>{{ $pLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <label for="filter-approval-role" class="form-label text-muted mb-1" style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Sign-off Governance</label>
                <select id="filter-approval-role" name="approval_role" class="form-select form-select-sm" style="border-radius: 6px; font-size: 0.8125rem; height: 34px; border-color: var(--border-strong);">
                    <option value="">All Approval Roles</option>
                    <option value="auto" {{ request('approval_role') === 'auto' ? 'selected' : '' }}>Auto-Promotion (No Sign-off)</option>
                    <option value="super_admin" {{ request('approval_role') === 'super_admin' ? 'selected' : '' }}>Super Admin Only</option>
                    <option value="admin" {{ request('approval_role') === 'admin' ? 'selected' : '' }}>Branch Admin or Super Admin</option>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-6 d-flex align-items-end text-muted pb-1" style="font-size: 0.75rem;">
                <span>Filter rules by specific evaluation windows or administrative sign-off authorities.</span>
            </div>
        </div>

        <!-- Active Filter Chips -->
        <div id="active-filters-chips-bar" class="d-flex align-items-center gap-1.5 flex-wrap mt-2 pt-2 border-top d-none" style="border-color: var(--border-color) !important;">
            <span class="text-muted fw-semibold me-1" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Filters:</span>
            <div id="active-filter-chips-list" class="d-inline-flex align-items-center gap-1.5 flex-wrap"></div>
            <a href="{{ route('superadmin.promotion-rules.index') }}" onclick="if(window.resetPromotionFilters){event.preventDefault();window.resetPromotionFilters();}" class="btn btn-link btn-sm text-secondary text-decoration-none p-0 ms-1" style="font-size: 0.75rem;">
                Clear all
            </a>
        </div>
    </form>
</div>

<!-- 4. Asynchronous Data Table Ledger Container -->
<div id="rules-table-container">
    @include('superadmin.promotion-rules._table')
</div>

<!-- Modal: Policy Parameters & Tenure Multipliers -->
<div class="modal fade" id="modalGlobalPromotionTargets" tabindex="-1" aria-labelledby="modalGlobalPromotionTargetsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 10px; overflow: hidden; background: var(--card-bg); border: 1px solid var(--border-color) !important; box-shadow: none;">
            <div class="modal-header px-4 py-3 border-bottom" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-success-subtle text-success border border-success-subtle" style="width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                        <i class="bi bi-sliders"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalGlobalPromotionTargetsLabel" style="font-size: 1rem; letter-spacing: -0.01em;">
                            Promotion Policy &amp; Multipliers
                        </h5>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            Establish institution-wide RD floor, FD cap, and duration multipliers.
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('superadmin.promotion-rules.global-targets') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <!-- Step 1: Compulsory Deposit Split -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.875rem;">1. Compulsory Allocation Floor &amp; Cap</h6>
                            <span id="globalSplitSumBadge" class="badge bg-success px-2 py-1" style="font-size: 0.72rem; border-radius: 4px; font-variant-numeric: tabular-nums;">
                                Sum: 100.00% (Valid)
                            </span>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.775rem;">
                            Compulsory Recurring Deposit contribution and Maximum Fixed Deposit volume recognized toward career advancement. Must sum to strictly 100.00%.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1" style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Compulsory RD % <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" id="global_rd_composition_percentage" name="rd_composition_percentage" class="form-control" value="{{ old('rd_composition_percentage', $rdComp) }}" required style="border-radius: 6px 0 0 6px; font-weight: 600; height: 34px;">
                                    <span class="input-group-text bg-light text-muted" style="border-radius: 0 6px 6px 0;">%</span>
                                </div>
                                <div class="form-text" style="font-size: 0.72rem;">Minimum RD business candidate must achieve. Default: 10.00%.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1" style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Maximum FD Cap % <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" id="global_fd_composition_percentage" name="fd_composition_percentage" class="form-control" value="{{ old('fd_composition_percentage', $fdComp) }}" required style="border-radius: 6px 0 0 6px; font-weight: 600; height: 34px;">
                                    <span class="input-group-text bg-light text-muted" style="border-radius: 0 6px 6px 0;">%</span>
                                </div>
                                <div class="form-text" style="font-size: 0.72rem;">Maximum FD volume recognized toward promotion target. Default: 90.00%.</div>
                            </div>
                        </div>

                        <!-- Precision Visual Split Preview Bar -->
                        <div class="mt-3 p-2 rounded border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                            <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 0.72rem;">
                                <span class="fw-semibold text-dark">Live Portfolio Split Allocation:</span>
                                <span class="text-muted">
                                    <strong class="text-success" id="liveSplitRdText">{{ number_format($rdComp, 0) }}% RD</strong> +
                                    <strong class="text-warning" id="liveSplitFdText">{{ number_format($fdComp, 0) }}% FD</strong>
                                </span>
                            </div>
                            <div class="split-track">
                                <div id="liveSplitFillRd" class="split-fill-rd" style="width: {{ $rdComp }}%;"></div>
                                <div id="liveSplitFillFd" class="split-fill-fd" style="width: {{ $fdComp }}%;"></div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3 text-muted opacity-25">

                    <!-- Step 2: Tenure Multipliers -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.875rem;">2. Duration Weightage Multipliers Matrix</h6>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="global_apply_tenure_targets" name="apply_tenure_targets" value="1" {{ $tenureTargetsOn ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold text-dark" for="global_apply_tenure_targets">Enable Multipliers</label>
                            </div>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.775rem;">
                            When enabled, deposits count towards career targets based on tenure length instead of 100% flat face value.
                        </p>

                        <!-- FD Tenure Grid -->
                        <div class="mb-3">
                            <div class="fw-semibold text-dark mb-1.5 small" style="font-size: 0.75rem;">
                                Fixed Deposit (FD) Duration Weightage
                            </div>
                            <div class="row g-2">
                                @foreach(['1' => '1 Year', '2' => '2 Years', '3' => '3 Years', '5' => '5 Years', '6' => '6 Years'] as $yr => $lbl)
                                    <div class="col-4 col-md">
                                        <div class="tenure-target-box">
                                            <label class="d-block text-muted small mb-1" style="font-size: 0.6875rem; font-weight: 500;">{{ $lbl }}</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.1" min="0" max="200" name="tenure_target_percentages[fd][{{ $yr }}]" class="form-control form-control-sm text-end" value="{{ $gt['fd'][$yr] ?? $gt['fd_rd'][$yr] ?? 50.0 }}" style="font-weight: 600;">
                                                <span class="input-group-text bg-white" style="font-size: 0.6875rem;">%</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- RD Tenure Grid -->
                        <div class="mb-3">
                            <div class="fw-semibold text-dark mb-1.5 small" style="font-size: 0.75rem;">
                                Recurring Deposit (RD) Duration Weightage
                            </div>
                            <div class="row g-2">
                                @foreach(['1' => '1 Year', '2' => '2 Years', '3' => '3 Years', '5' => '5 Years', '6' => '6 Years', '7' => '7 Years'] as $yr => $lbl)
                                    <div class="col-4 col-md">
                                        <div class="tenure-target-box">
                                            <label class="d-block text-muted small mb-1" style="font-size: 0.6875rem; font-weight: 500;">{{ $lbl }}</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.1" min="0" max="200" name="tenure_target_percentages[rd][{{ $yr }}]" class="form-control form-control-sm text-end" value="{{ $gt['rd'][$yr] ?? $gt['fd_rd'][$yr] ?? 50.0 }}" style="font-weight: 600;">
                                                <span class="input-group-text bg-white" style="font-size: 0.6875rem;">%</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- DD Tenure Grid -->
                        <div class="mb-2">
                            <div class="fw-semibold text-dark mb-1.5 small" style="font-size: 0.75rem;">
                                Daily Deposit (DD) Duration Weightage
                            </div>
                            <div class="row g-2">
                                @foreach(['1' => '1 Year', '2' => '2 Years', '3' => '3 Years'] as $yr => $lbl)
                                    <div class="col-4">
                                        <div class="tenure-target-box">
                                            <label class="d-block text-muted small mb-1" style="font-size: 0.6875rem; font-weight: 500;">{{ $lbl }}</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.1" min="0" max="200" name="tenure_target_percentages[dd][{{ $yr }}]" class="form-control form-control-sm text-end" value="{{ $gt['dd'][$yr] ?? 20.0 }}" style="font-weight: 600;">
                                                <span class="input-group-text bg-white" style="font-size: 0.6875rem;">%</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <hr class="my-3 text-muted opacity-25">

                    <!-- Step 3: Sync to All Existing Rules -->
                    <div class="p-2.5 rounded border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sync_all_rules" name="sync_all_rules" value="1" checked>
                            <label class="form-check-label small fw-semibold text-dark" for="sync_all_rules">
                                Synchronize all existing promotion rules immediately
                            </label>
                        </div>
                        <div class="text-muted small ms-4 mt-0.5" style="font-size: 0.72rem;">
                            Overwrites RD/FD composition split and tenure duration multipliers across all promotion rules.
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-2.5 border-top" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" style="border-radius: 6px; font-size: 0.8125rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold" style="border-radius: 6px; font-size: 0.8125rem; padding: 5px 14px;">
                        Save Policy Parameters
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Delete Rule Confirmation Dialog -->
<div class="modal fade" id="deleteRuleModal" tabindex="-1" aria-labelledby="deleteRuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0" style="border-radius: 10px; overflow: hidden; background: var(--card-bg); border: 1px solid var(--border-color) !important; box-shadow: none;">
            <div class="modal-header px-4 py-3 border-bottom" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                <h5 class="modal-title fw-bold text-dark mb-0" id="deleteRuleModalLabel" style="font-size: 0.95rem;">
                    Confirm Rule Deletion
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted small mb-3" style="font-size: 0.825rem;">
                    Are you sure you want to permanently delete <strong id="deleteModalRuleName" class="text-dark">this rule</strong>? This action cannot be reversed.
                </p>
                <form id="deleteRuleForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal" style="border-radius: 6px; font-size: 0.8125rem;">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold" style="border-radius: 6px; font-size: 0.8125rem;">
                            <i class="bi bi-trash3 me-1"></i> Confirm Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Delete modal handler ─────────────────────────────────────────────
    const deleteModalEl = document.getElementById('deleteRuleModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const deleteForm = document.getElementById('deleteRuleForm');
    const deleteName = document.getElementById('deleteModalRuleName');

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-rule');
        if (!btn) return;
        const action = btn.getAttribute('data-action');
        const name = btn.getAttribute('data-name');
        if (deleteForm) deleteForm.action = action;
        if (deleteName) deleteName.textContent = name;
        if (deleteModal) deleteModal.show();
    });

    // ── Advanced Filters Toggle ──────────────────────────────────────────
    const toggleAdvBtn = document.getElementById('btn-toggle-advanced-filters');
    const advRow = document.getElementById('advanced-filters-row');
    if (toggleAdvBtn && advRow) {
        toggleAdvBtn.addEventListener('click', function() {
            advRow.classList.toggle('d-none');
            toggleAdvBtn.classList.toggle('active');
        });
    }

    // ── Realtime Filter & Live Search Handler ────────────────────────────
    const form = document.getElementById('promotion-rules-filter-form');
    const container = document.getElementById('rules-table-container');
    const searchInput = document.getElementById('filter-search');
    const clearAllBtn = document.getElementById('filter-clear-all');
    const activeChipsBar = document.getElementById('active-filters-chips-bar');
    const activeChipsList = document.getElementById('active-filter-chips-list');

    if (form && container) {
        let abortController = null;
        let debounceTimer = null;

        function hasActiveFilters() {
            const formData = new FormData(form);
            for (let [key, val] of formData.entries()) {
                if (val && String(val).trim() !== '') return true;
            }
            return false;
        }

        function renderActiveFilterChips() {
            if (!activeChipsBar || !activeChipsList) return;

            activeChipsList.innerHTML = '';
            let count = 0;

            const fields = [
                { name: 'search', label: 'Search' },
                { name: 'financial_year_id', label: 'FY' },
                { name: 'from_rank_id', label: 'From' },
                { name: 'to_rank_id', label: 'To' },
                { name: 'status', label: 'Status' },
                { name: 'period', label: 'Period' },
                { name: 'approval_role', label: 'Approver' },
            ];

            fields.forEach(field => {
                const el = form.querySelector(`[name="${field.name}"]`);
                if (!el || !el.value || String(el.value).trim() === '') return;

                let displayVal = el.value;
                if (el.tagName.toLowerCase() === 'select') {
                    const selectedOpt = el.options[el.selectedIndex];
                    if (selectedOpt) displayVal = selectedOpt.textContent.trim();
                }

                const chip = document.createElement('span');
                chip.className = 'filter-chip';
                chip.innerHTML = `<span><strong>${field.label}:</strong> ${displayVal}</span><span class="filter-chip-remove" data-field="${field.name}" title="Remove filter">&times;</span>`;
                activeChipsList.appendChild(chip);
                count++;
            });

            if (count > 0) {
                activeChipsBar.classList.remove('d-none');
            } else {
                activeChipsBar.classList.add('d-none');
            }
        }

        function updateUiState() {
            if (clearAllBtn) {
                if (hasActiveFilters()) {
                    clearAllBtn.classList.remove('d-none');
                } else {
                    clearAllBtn.classList.add('d-none');
                }
            }
            renderActiveFilterChips();
        }

        function buildFilterUrl(baseUrl = form.action) {
            const url = new URL(baseUrl, window.location.origin);
            const formData = new FormData(form);
            for (let [key, val] of formData.entries()) {
                const trimmed = String(val).trim();
                if (trimmed !== '') {
                    url.searchParams.set(key, trimmed);
                } else {
                    url.searchParams.delete(key);
                }
            }
            return url;
        }

        function fetchRules(targetUrl, updateHistory = true) {
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            const fetchUrl = new URL(targetUrl, window.location.origin);
            fetchUrl.searchParams.set('ajax', '1');

            const card = container.querySelector('.card');
            if (card) {
                card.style.opacity = '0.55';
                card.style.pointerEvents = 'none';
            }

            fetch(fetchUrl.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                signal: abortController.signal
            })
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                if (data && typeof data.html === 'string') {
                    container.innerHTML = data.html;
                }
                if (updateHistory) {
                    const historyUrl = new URL(targetUrl, window.location.origin);
                    historyUrl.searchParams.delete('ajax');
                    window.history.replaceState({ path: historyUrl.toString() }, '', historyUrl.toString());
                }
                updateUiState();
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    console.error('Promotion rules filter error:', err);
                }
            })
            .finally(() => {
                const refreshedCard = container.querySelector('.card');
                if (refreshedCard) {
                    refreshedCard.style.opacity = '1';
                    refreshedCard.style.pointerEvents = 'auto';
                }
            });
        }

        function triggerFilter(immediate = false) {
            updateUiState();
            if (debounceTimer) clearTimeout(debounceTimer);

            if (immediate) {
                const url = buildFilterUrl();
                url.searchParams.delete('page');
                fetchRules(url.toString(), true);
            } else {
                debounceTimer = setTimeout(() => {
                    const url = buildFilterUrl();
                    url.searchParams.delete('page');
                    fetchRules(url.toString(), true);
                }, 250);
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                triggerFilter(false);
            });
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    triggerFilter(true);
                }
            });
        }

        form.querySelectorAll('select').forEach(sel => {
            sel.addEventListener('change', function() {
                triggerFilter(true);
            });
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            triggerFilter(true);
        });

        window.resetPromotionFilters = function() {
            if (searchInput) searchInput.value = '';
            form.querySelectorAll('select').forEach(sel => {
                sel.value = '';
            });
            triggerFilter(true);
        };

        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.resetPromotionFilters();
            });
        }

        // Click to remove individual filter chip
        if (activeChipsBar) {
            activeChipsBar.addEventListener('click', function(e) {
                const removeBtn = e.target.closest('.filter-chip-remove');
                if (!removeBtn) return;
                const fieldName = removeBtn.getAttribute('data-field');
                const fieldEl = form.querySelector(`[name="${fieldName}"]`);
                if (fieldEl) {
                    fieldEl.value = '';
                    triggerFilter(true);
                }
            });
        }

        // Pagination links click inside container
        container.addEventListener('click', function(e) {
            const pageLink = e.target.closest('.pagination a.page-link, .pagination a');
            if (pageLink && pageLink.href) {
                e.preventDefault();
                fetchRules(pageLink.href, true);
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        window.addEventListener('popstate', function() {
            const currentUrl = new URL(window.location.href);
            if (searchInput) {
                searchInput.value = currentUrl.searchParams.get('search') || '';
            }
            form.querySelectorAll('select').forEach(sel => {
                sel.value = currentUrl.searchParams.get(sel.name) || '';
            });
            updateUiState();
            fetchRules(window.location.href, false);
        });

        updateUiState();
    }

    // ── Global Policy Split Sum & Live Visual Bar Validation ──────────────
    (function () {
        const rd = document.getElementById('global_rd_composition_percentage');
        const fd = document.getElementById('global_fd_composition_percentage');
        const badge = document.getElementById('globalSplitSumBadge');
        const fillRd = document.getElementById('liveSplitFillRd');
        const fillFd = document.getElementById('liveSplitFillFd');
        const textRd = document.getElementById('liveSplitRdText');
        const textFd = document.getElementById('liveSplitFdText');

        if (!rd || !fd || !badge) return;

        function updateGlobalSum() {
            const rVal = parseFloat(rd.value) || 0;
            const fVal = parseFloat(fd.value) || 0;
            const sum = Math.round((rVal + fVal) * 100) / 100;

            if (textRd) textRd.textContent = Math.round(rVal) + '% RD';
            if (textFd) textFd.textContent = Math.round(fVal) + '% FD';

            if (fillRd && fillFd) {
                const safeSum = sum > 0 ? sum : 100;
                const rdWidth = Math.min(100, Math.max(0, (rVal / safeSum) * 100));
                const fdWidth = Math.min(100, Math.max(0, (fVal / safeSum) * 100));
                fillRd.style.width = rdWidth + '%';
                fillFd.style.width = fdWidth + '%';
            }

            if (Math.abs(sum - 100.0) < 0.01) {
                badge.className = 'badge bg-success px-2 py-1';
                badge.textContent = 'Sum: 100.00% (Valid)';
            } else {
                badge.className = 'badge bg-danger px-2 py-1';
                badge.textContent = 'Sum: ' + sum.toFixed(2) + '% (Must be 100%)';
            }
        }

        rd.addEventListener('input', updateGlobalSum);
        fd.addEventListener('input', updateGlobalSum);
        updateGlobalSum();
    })();
});
</script>
@endpush
