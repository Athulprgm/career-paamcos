@extends('superadmin.layouts.app')

@section('page-title', 'Promotion Rule: ' . $promotionRule->name)

@section('content')
<style>
    /* ─── MultiCred Executive Banking Theme ─── */
    :root {
        --bank-primary: var(--color-primary, #14532D);
        --bank-primary-hover: var(--color-primary-hover, #0F3F22);
        --bank-primary-subtle: var(--bs-primary-bg-subtle, #ECFDF5);
        --bank-primary-border: var(--bs-primary-border-subtle, #A7F3D0);
        --bank-primary-text: var(--bs-primary-text-emphasis, #0F3F22);

        --bank-slate-900: var(--text-heading, #0F172A);
        --bank-slate-800: var(--text-body, #1E293B);
        --bank-slate-700: var(--text-body, #334155);
        --bank-slate-600: var(--text-muted, #475569);
        --bank-slate-500: var(--text-muted, #64748B);
        --bank-slate-400: var(--text-subtle, #94A3B8);
        --bank-slate-200: var(--border-color, #E2E8F0);
        --bank-slate-100: var(--border-color, #F1F5F9);
        --bank-slate-50: var(--surface-subtle, #F8FAFC);

        --bank-amber: #D97706;
        --bank-amber-subtle: #FEF3C7;
        --bank-amber-border: #FDE68A;

        --bank-card-bg: var(--card-bg, #FFFFFF);
        --bank-card-border: var(--border-color, #E2E8F0);
        --bank-radius: 10px;
        --bank-radius-sm: 8px;
    }

    /* Micro Typography */
    .bank-micro-label {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--bank-slate-500);
        margin-bottom: 4px;
        display: block;
    }
    .bank-tabular-num {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }

    /* Enterprise Cards */
    .bank-card {
        background: var(--bank-card-bg);
        border: 1px solid var(--bank-card-border);
        border-radius: 10px;
        box-shadow: none;
        margin-bottom: 20px;
        overflow: hidden;
    }
    .bank-card-header {
        background: var(--card-bg);
        border-bottom: 1px solid var(--bank-slate-200);
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .bank-card-body {
        padding: 20px;
    }
    .bank-section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--bank-slate-900);
        letter-spacing: -0.01em;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .bank-sub-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--bank-slate-800);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .bank-section-badge {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 6px;
        background: var(--bank-slate-50);
        color: var(--bank-slate-600);
        border: 1px solid var(--bank-slate-200);
        letter-spacing: 0.01em;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Progression Stage Cards */
    .bank-progression-card {
        background: var(--card-bg);
        border: 1px solid var(--bank-slate-200);
        border-radius: var(--bank-radius-sm);
        padding: 14px 16px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .bank-progression-card-target {
        background: var(--bs-primary-bg-subtle, #ECFDF5);
        border: 1px solid var(--bs-primary-border-subtle, #A7F3D0);
        border-radius: var(--bank-radius-sm);
        padding: 14px 16px;
    }

    /* Inner Detail Panels */
    .bank-detail-panel {
        background: var(--bank-slate-50);
        border: 1px solid var(--bank-slate-200);
        border-radius: var(--bank-radius-sm);
        padding: 14px 16px;
    }
    .bank-detail-panel-highlight {
        background: var(--bs-primary-bg-subtle, #ECFDF5);
        border: 1px solid var(--bs-primary-border-subtle, #A7F3D0);
        border-radius: var(--bank-radius-sm);
        padding: 16px 18px;
    }

    /* Status Pills */
    .bank-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        font-size: 0.72rem;
        font-weight: 600;
        border-radius: 999px;
        letter-spacing: 0.01em;
        white-space: nowrap;
    }
    .bank-pill-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
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
        background: var(--bank-slate-100);
        color: var(--bank-slate-600);
        border: 1px solid var(--bank-slate-200);
    }
    .bank-pill-inactive .bank-pill-dot {
        background: var(--bank-slate-400);
    }
    .bank-pill-amber {
        background: var(--bank-amber-subtle);
        color: #92400E;
        border: 1px solid var(--bank-amber-border);
    }
    .bank-pill-amber .bank-pill-dot {
        background: #D97706;
    }
    .bank-pill-blue {
        background: #EFF6FF;
        color: #1D4ED8;
        border: 1px solid #BFDBFE;
    }
    .bank-pill-blue .bank-pill-dot {
        background: #2563EB;
    }
    .bank-pill-red {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FECACA;
    }
    .bank-pill-red .bank-pill-dot {
        background: #DC2626;
    }
    html.dark .bank-pill-blue {
        background: rgba(59, 130, 246, 0.16);
        color: #60A5FA;
        border-color: rgba(59, 130, 246, 0.35);
    }
    html.dark .bank-pill-red {
        background: rgba(239, 68, 68, 0.16);
        color: #F87171;
        border-color: rgba(239, 68, 68, 0.35);
    }
    html.dark .bank-pill-amber {
        background: rgba(245, 158, 11, 0.16);
        color: #FBBF24;
        border-color: rgba(245, 158, 11, 0.35);
    }

    /* Linear Style Split Ratio Bar */
    .bank-ratio-track {
        height: 8px;
        width: 100%;
        background: var(--bank-slate-200);
        border-radius: 999px;
        overflow: hidden;
        display: flex;
    }
    .bank-ratio-fill-rd {
        background: var(--bank-primary);
        height: 100%;
        transition: width 0.3s ease;
    }
    .bank-ratio-fill-fd {
        background: var(--bank-amber);
        height: 100%;
        transition: width 0.3s ease;
    }

    /* Tenure Schedule Boxes */
    .bank-tenure-box {
        background: var(--card-bg);
        border: 1px solid var(--bank-slate-200);
        border-radius: var(--bank-radius-sm);
        padding: 9px 12px;
        height: 100%;
        transition: border-color 0.15s ease;
    }
    .bank-tenure-box:hover {
        border-color: var(--bank-slate-400);
    }

    /* Key-Value Definition Rows */
    .bank-kv-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 9px 0;
        border-bottom: 1px solid var(--bank-slate-100);
        font-size: 0.8125rem;
        gap: 16px;
    }
    .bank-kv-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .bank-kv-row:first-child {
        padding-top: 0;
    }
    .bank-kv-label {
        color: var(--bank-slate-500);
        font-weight: 500;
        flex-shrink: 0;
    }
    .bank-kv-value {
        color: var(--bank-slate-900);
        font-weight: 600;
        text-align: right;
        word-break: break-word;
    }

    /* Checklist Item in Sidebar */
    .bank-checklist-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 9px 0;
        border-bottom: 1px solid var(--bank-slate-100);
        font-size: 0.8125rem;
    }
    .bank-checklist-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .bank-checklist-item:first-child {
        padding-top: 0;
    }
    .bank-check-icon {
        color: var(--bank-primary);
        font-size: 0.95rem;
        line-height: 1.2;
        flex-shrink: 0;
    }

    /* Enterprise Table Styling */
    .bank-table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }
    .bank-table thead th {
        background: var(--bank-slate-50);
        color: var(--bank-slate-500);
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 12px 16px;
        border-bottom: 1px solid var(--bank-slate-200);
        border-top: none;
        white-space: nowrap;
    }
    .bank-table tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--bank-slate-100);
        color: var(--bank-slate-700);
        font-size: 0.8125rem;
        vertical-align: middle;
        background: var(--card-bg);
    }
    .bank-table tbody tr:hover td {
        background: var(--surface-subtle);
    }
    .bank-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Table Search Box */
    .bank-search-box {
        position: relative;
        min-width: 260px;
        max-width: 380px;
    }
    .bank-search-box i {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--bank-slate-400);
        font-size: 0.8125rem;
        pointer-events: none;
    }
    .bank-search-box input {
        padding-left: 32px;
        padding-right: 12px;
        height: 35px;
        font-size: 0.8125rem;
        border-radius: var(--bank-radius-sm);
        border: 1px solid var(--bank-slate-200);
        background: var(--card-bg);
        color: var(--bank-slate-900);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .bank-search-box input:focus {
        border-color: var(--bank-primary);
        box-shadow: none;
        outline: none;
    }

    /* Sticky Sidebar */
    @media (min-width: 992px) {
        .sticky-sidebar {
            position: sticky;
            top: 76px;
        }
    }
</style>

<!-- ─── HEADER: Page title, breadcrumbs, rule status and action buttons ─── -->
<div class="mb-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb m-0" style="font-size: 0.8rem;">
            <li class="breadcrumb-item text-muted">Career Operations</li>
            <li class="breadcrumb-item">
                <a href="{{ route('superadmin.promotion-rules.index') }}" class="text-decoration-none" style="color: var(--bank-slate-600);">
                    Promotion Rules
                </a>
            </li>
            <li class="breadcrumb-item active fw-medium" aria-current="page" style="color: var(--bank-slate-900);">
                {{ $promotionRule->name }}
            </li>
        </ol>
    </nav>

    <!-- Header Content & Action Toolbar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <!-- Title & Badges -->
        <div class="d-flex align-items-center gap-2.5 flex-wrap">
            <h1 class="m-0 fw-bold" style="font-size: 1.45rem; color: var(--bank-slate-900); letter-spacing: -0.025em;">
                {{ $promotionRule->name }}
            </h1>

            <!-- Rule Status Pill -->
            @if($promotionRule->isActive())
                <span class="bank-pill bank-pill-active">
                    <span class="bank-pill-dot"></span>
                    <span>Active Policy</span>
                </span>
            @else
                <span class="bank-pill bank-pill-inactive">
                    <span class="bank-pill-dot"></span>
                    <span>Inactive</span>
                </span>
            @endif

            <!-- Operating Scope Badge -->
            @if($promotionRule->financialYear)
                <span class="bank-pill" style="background: var(--bank-slate-100); color: var(--bank-slate-700); border: 1px solid var(--bank-slate-200);">
                    <i class="bi bi-calendar3 me-1 text-muted"></i>FY {{ $promotionRule->financialYear->code }}
                </span>
            @else
                <span class="bank-pill" style="background: var(--bank-slate-100); color: var(--bank-slate-700); border: 1px solid var(--bank-slate-200);">
                    <i class="bi bi-globe me-1 text-muted"></i>Permanent Global Policy
                </span>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('superadmin.promotion-rules.edit', $promotionRule) }}" 
               class="btn btn-sm d-inline-flex align-items-center gap-1.5 text-white" 
               style="background: var(--bank-primary); border-color: var(--bank-primary); border-radius: var(--bank-radius-sm); font-weight: 600; padding: 7px 14px; font-size: 0.8125rem;">
                <i class="bi bi-pencil"></i>
                <span>Edit Rule</span>
            </a>

            <form action="{{ route('superadmin.promotion-rules.toggle-status', $promotionRule) }}" method="POST" class="m-0"
                  data-confirm="Are you sure you want to {{ $promotionRule->isActive() ? 'deactivate' : 'activate' }} this promotion rule?"
                  data-confirm-title="{{ $promotionRule->isActive() ? 'Deactivate Rule' : 'Activate Rule' }}"
                  data-confirm-type="{{ $promotionRule->isActive() ? 'warning' : 'success' }}"
                  data-confirm-btn="{{ $promotionRule->isActive() ? 'Deactivate' : 'Activate' }}">
                @csrf
                <button type="submit" 
                        class="btn btn-sm d-inline-flex align-items-center gap-1.5 {{ $promotionRule->isActive() ? 'btn-outline-secondary' : 'btn-outline-success' }}" 
                        style="border-radius: var(--bank-radius-sm); font-weight: 500; padding: 7px 13px; font-size: 0.8125rem;">
                    <i class="bi bi-{{ $promotionRule->isActive() ? 'pause-circle' : 'play-circle' }}"></i>
                    <span>{{ $promotionRule->isActive() ? 'Deactivate' : 'Activate' }}</span>
                </button>
            </form>

            <a href="{{ route('superadmin.promotion-rules.index') }}" 
               class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5" 
               style="border-radius: var(--bank-radius-sm); font-weight: 500; border-color: var(--bank-slate-200); background: var(--bank-card-bg); color: var(--bank-slate-700); padding: 7px 13px; font-size: 0.8125rem;">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Rules Ledger</span>
            </a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4 border-0" role="alert" style="border-radius: 8px; border-left: 4px solid #16A34A !important; box-shadow: none;">
    <div class="d-flex align-items-center">
        <i class="bi bi-check-circle-fill fs-5 me-2.5 text-success"></i>
        <div class="fw-medium" style="font-size: 0.875rem;">{{ session('success') }}</div>
    </div>
    <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- ─── WORKSPACE: Main 8-Col Area & Sidebar 4-Col Area ─── -->
<div class="row g-4 mb-4">
    <!-- ═══════════════════════════════════════════════════════════
         MAIN COLUMN (8 COLUMNS)
         1. Career Path & Policy Scope
         2. Team Deposit Composition & Individual Target
         3. Direct Agent Requirements & Temporal Integrity
    ════════════════════════════════════════════════════════════ -->
    <div class="col-lg-8">

        <!-- 1. CAREER PATH -->
        <div class="bank-card">
            <div class="bank-card-header">
                <h2 class="bank-section-title">
                    <i class="bi bi-signpost-split text-success"></i>
                    <span>Career Path</span>
                </h2>
                <span class="bank-section-badge">
                    <i class="bi bi-arrow-right-circle text-muted"></i>
                    Career Designation &amp; Advancement Path
                </span>
            </div>
            <div class="bank-card-body">
                <!-- Visual From/To Designation Progression -->
                <div class="p-3 mb-3 rounded-3" style="background: var(--bank-slate-50); border: 1px solid var(--bank-slate-200);">
                    <div class="row align-items-center g-3">
                        <!-- Starting Rank -->
                        <div class="col-md-5">
                            <div class="bank-progression-card">
                                <span class="bank-micro-label">
                                    Starting Designation &bull; Level {{ $promotionRule->fromRank?->level ?? $promotionRule->fromRank?->sort_order }}
                                </span>
                                <div class="fw-bold text-dark" style="font-size: 1.15rem; letter-spacing: -0.01em;">
                                    {{ $promotionRule->fromRank?->name ?? 'Level ' . $promotionRule->from_rank_id }}
                                </div>
                                @if($promotionRule->fromRank?->position_name)
                                    <div class="text-muted" style="font-size: 0.75rem; margin-top: 2px;">
                                        {{ $promotionRule->fromRank->position_name }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Connector Step -->
                        <div class="col-md-2 text-center">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                 style="width: 36px; height: 36px; background: var(--bank-primary); color: #FFFFFF;">
                                <i class="bi bi-arrow-right fs-6"></i>
                            </div>
                            <div class="text-muted text-uppercase mt-1" style="font-size: 0.625rem; font-weight: 700; letter-spacing: 0.05em;">
                                Advancement
                            </div>
                        </div>

                        <!-- Target Promoted Rank -->
                        <div class="col-md-5">
                            <div class="bank-progression-card-target">
                                <span class="bank-micro-label" style="color: var(--bank-primary);">
                                    Promoted Designation &bull; Level {{ $promotionRule->toRank?->level ?? $promotionRule->toRank?->sort_order }}
                                </span>
                                <div class="fw-bold" style="color: var(--bank-primary); font-size: 1.15rem; letter-spacing: -0.01em;">
                                    {{ $promotionRule->toRank?->name ?? 'Level ' . $promotionRule->to_rank_id }}
                                </div>
                                @if($promotionRule->toRank?->position_name)
                                    <div class="text-muted" style="font-size: 0.75rem; margin-top: 2px;">
                                        {{ $promotionRule->toRank->position_name }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Parameters Metadata Grid -->
                <div class="row g-3">
                    <div class="col-md-7">
                        <span class="bank-micro-label">Formal Policy Name</span>
                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;">
                            {{ $promotionRule->name }}
                        </div>
                    </div>
                    <div class="col-md-5">
                        <span class="bank-micro-label">Operating Cycle Binding</span>
                        <div class="fw-medium text-dark" style="font-size: 0.875rem;">
                            {{ $promotionRule->financialYear?->name ?? 'Permanent Global (All Cycles)' }}
                        </div>
                    </div>
                </div>

                @if($promotionRule->description)
                    <div class="mt-3 p-3 rounded-2 text-muted" style="background: var(--bank-slate-50); border: 1px solid var(--bank-slate-200); font-size: 0.8125rem;">
                        <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em;">
                            <i class="bi bi-info-circle me-1 text-primary"></i> Administrative Scope &amp; Resolution:
                        </strong>
                        {{ $promotionRule->description }}
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. TEAM DEPOSIT COMPOSITION & INDIVIDUAL TARGET -->
        <div class="bank-card">
            <div class="bank-card-header">
                <h2 class="bank-section-title">
                    <i class="bi bi-pie-chart text-success"></i>
                    <span>Team Deposit Composition</span>
                </h2>
                <span class="bank-section-badge">
                    <i class="bi bi-layers text-muted"></i>
                    Team Deposit Target &amp; Mandatory Split
                </span>
            </div>
            <div class="bank-card-body">
                <!-- Primary Target Volume Display -->
                <div class="bank-detail-panel-highlight mb-3">
                    <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2 mb-1">
                        <span class="bank-micro-label">Minimum Required Team Deposit Volume</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.72rem; font-weight: 600; border-radius: 4px;">
                            {{ $promotionRule->min_team_deposit_text }}
                        </span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fw-bold text-dark bank-tabular-num" style="font-size: 1.75rem; letter-spacing: -0.03em;">
                            ₹{{ $promotionRule->formatted_min_team_deposit }}
                        </span>
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                        {{ $promotionRule->min_team_deposit_words }} Rupees
                    </div>
                </div>

                <!-- Composition Split Cards -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="bank-detail-panel h-100">
                            <span class="bank-micro-label">Compulsory RD Floor (Recurring Deposit)</span>
                            <div class="fw-bold bank-tabular-num" style="font-size: 1.35rem; color: var(--bank-primary);">
                                {{ number_format((float) $promotionRule->rd_composition_percentage, 0) }}%
                            </div>
                            <div class="text-muted small mt-1" style="font-size: 0.72rem; line-height: 1.45;">
                                Minimum percentage required from recurring deposit accounts toward qualification volume.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bank-detail-panel h-100">
                            <span class="bank-micro-label">Maximum FD Cap (Fixed Deposit)</span>
                            <div class="fw-bold bank-tabular-num" style="font-size: 1.35rem; color: var(--bank-amber);">
                                {{ number_format((float) $promotionRule->fd_composition_percentage, 0) }}%
                            </div>
                            <div class="text-muted small mt-1" style="font-size: 0.72rem; line-height: 1.45;">
                                Maximum percentage recognized from fixed deposit accounts toward qualification volume.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sleek Visual Ratio Track -->
                <div class="p-3 rounded-2 border mb-4" style="background: var(--bank-slate-50); border-color: var(--bank-slate-200) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 0.75rem;">
                        <span class="fw-semibold text-dark">Portfolio Split Proportion:</span>
                        <span class="bank-tabular-num">
                            <strong style="color: var(--bank-primary);">{{ number_format((float) $promotionRule->rd_composition_percentage, 0) }}% RD</strong>
                            <span class="text-muted mx-1">+</span>
                            <strong style="color: var(--bank-amber);">{{ number_format((float) $promotionRule->fd_composition_percentage, 0) }}% FD</strong>
                        </span>
                    </div>
                    <div class="bank-ratio-track mb-1.5">
                        <div class="bank-ratio-fill-rd" style="width: {{ $promotionRule->rd_composition_percentage }}%;"></div>
                        <div class="bank-ratio-fill-fd" style="width: {{ $promotionRule->fd_composition_percentage }}%;"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted" style="font-size: 0.6875rem;">
                        <span>RD Floor Guarantee</span>
                        <span>FD Maximum Cap</span>
                    </div>
                </div>

                <!-- Integrated Individual Target Section -->
                <div class="pt-3 border-top" style="border-color: var(--bank-slate-100) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2.5">
                        <h3 class="bank-sub-title m-0">
                            <i class="bi bi-person-check text-success"></i>
                            <span>Individual Target</span>
                        </h3>
                        <span class="bank-section-badge">Personal Quota</span>
                    </div>

                    @if((float) $promotionRule->min_individual_deposit > 0)
                        <div class="bank-detail-panel-highlight">
                            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2 mb-1">
                                <span class="bank-micro-label">Direct Personal Volume Quota</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.72rem; font-weight: 600; border-radius: 4px;">
                                    {{ $promotionRule->min_individual_deposit_text }}
                                </span>
                            </div>
                            <div class="fw-bold text-dark bank-tabular-num" style="font-size: 1.5rem; letter-spacing: -0.02em;">
                                ₹{{ $promotionRule->formatted_min_individual_deposit }}
                            </div>
                            <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                {{ $promotionRule->min_individual_deposit_words }} Rupees mandatory direct candidate deposit volume.
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-3 p-3 rounded-2 border" style="background: var(--bank-slate-50); border-color: var(--bank-slate-200) !important;">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="bi bi-check-lg fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark" style="font-size: 0.875rem;">100% Satisfiable via Downline Team Volume</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">
                                    No mandatory personal quota is imposed. Candidate target can be completely fulfilled by recursive downline agents.
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. ELIGIBILITY RULES: DIRECT AGENTS, PERIOD & TENURE -->
        <div class="bank-card">
            <div class="bank-card-header">
                <h2 class="bank-section-title">
                    <i class="bi bi-diagram-3 text-success"></i>
                    <span>Direct Agent Requirements</span>
                </h2>
                <span class="bank-section-badge">
                    <i class="bi bi-people text-muted"></i>
                    Downline Hierarchy
                </span>
            </div>
            <div class="bank-card-body">
                @php
                    $tiers = $promotionRule->qualifying_agent_tiers ?? [];
                    if (empty($tiers) && $promotionRule->min_qualifying_direct_facilitators > 0) {
                        $tiers = [[
                            'count' => $promotionRule->min_qualifying_direct_facilitators,
                            'rank_id' => $promotionRule->qualifying_rank_id ?? '',
                        ]];
                    }
                @endphp

                <!-- Downline Matrix -->
                @if(!empty($tiers))
                    <div class="d-flex flex-column gap-2 mb-4">
                        @foreach($tiers as $tier)
                            @php
                                $tRank = isset($tier['rank_id']) && $tier['rank_id'] ? $ranks->firstWhere('id', $tier['rank_id']) : $promotionRule->getTargetQualifyingRank();
                            @endphp
                            <div class="d-flex align-items-center justify-content-between p-3 rounded-2 border" style="background: var(--bank-slate-50); border-color: var(--bank-slate-200) !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="px-2.5 py-1 fw-bold bank-tabular-num" 
                                          style="background: var(--bank-card-bg); color: var(--bank-slate-900); border: 1px solid var(--bank-slate-200); border-radius: var(--bank-radius-sm); font-size: 0.85rem; font-family: ui-monospace, monospace;">
                                        {{ $tier['count'] }}&times;
                                    </span>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.875rem;">
                                            {{ $tRank?->name ?? 'Direct Agent' }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.72rem;">
                                            {{ $tRank ? 'Designation Level ' . ($tRank->level ?? $tRank->sort_order) : 'Any active designation in good standing' }}
                                        </div>
                                    </div>
                                </div>
                                <span class="badge bg-white text-secondary border px-2 py-1" style="font-size: 0.7rem; border-color: var(--bank-slate-200) !important;">
                                    <i class="bi bi-check-circle-fill text-success me-1"></i>Mandatory Quota
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted small p-3 rounded-2 border mb-4" style="background: var(--bank-slate-50); border-color: var(--bank-slate-200) !important;">
                        <i class="bi bi-dash-circle me-1"></i> No direct frontline downline hierarchy quota configured for this advancement step.
                    </div>
                @endif

                <!-- Calculation Period & Standing -->
                <div class="pt-3 border-top mb-4" style="border-color: var(--bank-slate-100) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2.5">
                        <h3 class="bank-sub-title m-0">
                            <i class="bi bi-calendar-range text-success"></i>
                            <span>Calculation Period</span>
                        </h3>
                        <span class="bank-section-badge">Temporal Horizon</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bank-detail-panel h-100">
                                <span class="bank-micro-label">Deposit Calculation Horizon</span>
                                <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">
                                    {{ $promotionRule->getCalculationPeriodLabel() }}
                                </div>
                                <div class="text-muted small" style="font-size: 0.75rem; line-height: 1.45;">
                                    @if($promotionRule->calculation_period === 'financial_year')
                                        Only deposits collected within the active Financial Year boundaries count toward candidate qualification.
                                    @elseif($promotionRule->calculation_period === 'calendar_year')
                                        Deposits collected strictly within the calendar year window (Jan 1 to Dec 31).
                                    @elseif($promotionRule->calculation_period === 'quarter')
                                        Deposits collected within the active 90-day calendar quarter cycle.
                                    @elseif($promotionRule->calculation_period === 'current_month')
                                        Deposits originated during the active calendar month.
                                    @elseif($promotionRule->calculation_period === 'last_30_days')
                                        Deposits within the rolling last 30-day window.
                                    @elseif($promotionRule->calculation_period === 'last_90_days')
                                        Deposits within the rolling last 90-day window.
                                    @elseif($promotionRule->calculation_period === 'last_365_days')
                                        Deposits within the rolling last 365-day annual window.
                                    @else
                                        Cumulative lifetime deposits collected across the candidate's complete service tenure.
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="bank-detail-panel h-100">
                                <span class="bank-micro-label">Required Candidate Standing</span>
                                <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">
                                    {{ $promotionRule->required_agent_status === 'active' ? 'Active Status Only' : 'Any Status' }}
                                </div>
                                <div class="text-muted small" style="font-size: 0.75rem; line-height: 1.45;">
                                    Candidate agent must maintain verified, active membership in good standing without disciplinary suspension.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Qualifying Statuses -->
                <div class="pt-3 border-top mb-4" style="border-color: var(--bank-slate-100) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2.5">
                        <h3 class="bank-sub-title m-0">
                            <i class="bi bi-shield-check text-success"></i>
                            <span>Qualifying Statuses</span>
                        </h3>
                        <span class="bank-section-badge">Ledger Integrity</span>
                    </div>

                    <div class="p-3 rounded-2 border" style="background: var(--bank-slate-50); border-color: var(--bank-slate-200) !important;">
                        <span class="bank-micro-label mb-2">Recognized Deposit Transaction Statuses</span>
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            @foreach($promotionRule->getQualifyingStatuses() as $st)
                                <span class="badge px-2.5 py-1.5" style="background: var(--bank-card-bg); color: var(--bank-slate-800); border: 1px solid var(--bank-slate-200); font-size: 0.75rem; font-family: ui-monospace, monospace; border-radius: 4px;">
                                    <i class="bi bi-check2 text-success me-1"></i>{{ $st }}
                                </span>
                            @endforeach
                        </div>
                        <div class="text-muted small" style="font-size: 0.75rem;">
                            <i class="bi bi-info-circle me-1"></i> Draft, pending approval, and rejected deposits are strictly excluded from career evaluation ledgers.
                        </div>
                    </div>
                </div>

                <!-- Tenure Multipliers -->
                <div class="pt-3 border-top" style="border-color: var(--bank-slate-100) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2.5">
                        <h3 class="bank-sub-title m-0">
                            <i class="bi bi-hourglass-split text-success"></i>
                            <span>Tenure Multipliers</span>
                        </h3>
                        @if($promotionRule->apply_tenure_targets)
                            <span class="bank-pill bank-pill-active">
                                <span class="bank-pill-dot"></span>
                                <span>Multipliers Active</span>
                            </span>
                        @else
                            <span class="bank-pill bank-pill-inactive">
                                <span class="bank-pill-dot"></span>
                                <span>Disabled (100% Face Value)</span>
                            </span>
                        @endif
                    </div>

                    @if($promotionRule->apply_tenure_targets)
                        @php $tTargets = $promotionRule->getTenureTargetPercentages(); @endphp
                        
                        <!-- Fixed Deposit Duration -->
                        <div class="fw-semibold text-dark mb-2 small d-flex align-items-center gap-1.5">
                            <i class="bi bi-clock-history text-success"></i> Fixed Deposit (FD) Duration Schedule
                        </div>
                        <div class="row g-2 mb-3">
                            @foreach(['1' => '1 Year', '2' => '2 Years', '3' => '3 Years', '5' => '5 Years', '6' => '6 Years'] as $yr => $lbl)
                                <div class="col-6 col-sm-4 col-md">
                                    <div class="bank-tenure-box">
                                        <span class="bank-micro-label">{{ $lbl }}</span>
                                        <div class="fw-bold text-dark bank-tabular-num" style="font-size: 0.95rem;">
                                            {{ $tTargets['fd'][$yr] ?? $tTargets['fd_rd'][$yr] ?? 50 }}%
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Recurring Deposit Duration -->
                        <div class="fw-semibold text-dark mb-2 small d-flex align-items-center gap-1.5">
                            <i class="bi bi-arrow-repeat text-primary"></i> Recurring Deposit (RD) Duration Schedule
                        </div>
                        <div class="row g-2 mb-3">
                            @foreach(['1' => '1 Year', '2' => '2 Years', '3' => '3 Years', '5' => '5 Years', '6' => '6 Years', '7' => '7 Years'] as $yr => $lbl)
                                <div class="col-6 col-sm-4 col-md">
                                    <div class="bank-tenure-box">
                                        <span class="bank-micro-label">{{ $lbl }}</span>
                                        <div class="fw-bold text-dark bank-tabular-num" style="font-size: 0.95rem;">
                                            {{ $tTargets['rd'][$yr] ?? $tTargets['fd_rd'][$yr] ?? 50 }}%
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Daily Deposit (DD) Schedule if available -->
                        @if(!empty($tTargets['dd']))
                            <div class="fw-semibold text-dark mb-2 small d-flex align-items-center gap-1.5">
                                <i class="bi bi-calendar2-day text-secondary"></i> Daily Deposit (DD) Duration Schedule
                            </div>
                            <div class="row g-2">
                                @foreach(['1' => '1 Year', '2' => '2 Years', '3' => '3 Years'] as $yr => $lbl)
                                    <div class="col-4 col-md">
                                        <div class="bank-tenure-box">
                                            <span class="bank-micro-label">{{ $lbl }}</span>
                                            <div class="fw-bold text-dark bank-tabular-num" style="font-size: 0.95rem;">
                                                {{ $tTargets['dd'][$yr] ?? 20 }}%
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="d-flex align-items-center gap-2 p-3 rounded-2 border" style="background: var(--bank-slate-50); border-color: var(--bank-slate-200) !important;">
                            <i class="bi bi-info-circle text-muted fs-5"></i>
                            <div class="text-muted small" style="font-size: 0.78rem;">
                                Tenure duration weighting is disabled for this rule. All qualifying deposits are credited at 100% face value toward career targets.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SIDEBAR (4 COLUMNS)
         1. Promotion Summary
         2. Eligibility Overview
    ════════════════════════════════════════════════════════════ -->
    <div class="col-lg-4">
        <div class="sticky-sidebar">

            <!-- 1. PROMOTION SUMMARY -->
            <div class="bank-card">
                <div class="bank-card-header">
                    <h2 class="bank-section-title">
                        <i class="bi bi-file-earmark-text text-success"></i>
                        <span>Promotion Summary</span>
                    </h2>
                </div>
                <div class="bank-card-body">
                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Career Step</span>
                        <span class="bank-kv-value bank-tabular-num">
                            L{{ $promotionRule->fromRank?->level ?? $promotionRule->fromRank?->sort_order }}
                            <i class="bi bi-arrow-right mx-1 text-muted" style="font-size: 0.75rem;"></i>
                            L{{ $promotionRule->toRank?->level ?? $promotionRule->toRank?->sort_order }}
                        </span>
                    </div>

                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Target Designation</span>
                        <span class="bank-kv-value" style="color: var(--bank-primary);">
                            {{ $promotionRule->toRank?->name ?? 'Rank ' . $promotionRule->to_rank_id }}
                        </span>
                    </div>

                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Min. Team Volume</span>
                        <span class="bank-kv-value bank-tabular-num">
                            ₹{{ $promotionRule->formatted_min_team_deposit }}
                        </span>
                    </div>

                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Min. Individual</span>
                        <span class="bank-kv-value bank-tabular-num">
                            @if((float) $promotionRule->min_individual_deposit > 0)
                                ₹{{ $promotionRule->formatted_min_individual_deposit }}
                            @else
                                <span class="text-muted fw-normal">None (0.00)</span>
                            @endif
                        </span>
                    </div>

                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Deposit Ratio</span>
                        <span class="bank-kv-value bank-tabular-num">
                            <span style="color: var(--bank-primary);">{{ number_format((float) $promotionRule->rd_composition_percentage, 0) }}% RD</span>
                            <span class="text-muted mx-0.5">/</span>
                            <span style="color: var(--bank-amber);">{{ number_format((float) $promotionRule->fd_composition_percentage, 0) }}% FD</span>
                        </span>
                    </div>

                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Governance Mode</span>
                        <span class="bank-kv-value">
                            @if($promotionRule->requires_approval)
                                <span class="bank-pill bank-pill-amber">
                                    <span class="bank-pill-dot"></span>
                                    <span>Manual Review</span>
                                </span>
                            @else
                                <span class="bank-pill bank-pill-blue">
                                    <span class="bank-pill-dot"></span>
                                    <span>Auto-Promote</span>
                                </span>
                            @endif
                        </span>
                    </div>

                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Authorized Role</span>
                        <span class="bank-kv-value">
                            @if(!$promotionRule->requires_approval)
                                <span class="text-muted fw-normal">Auto-Promote (Instant Execution)</span>
                            @elseif($promotionRule->authorized_approval_role === 'super_admin')
                                Super Admin Only
                            @elseif($promotionRule->authorized_approval_role === 'admin')
                                Branch / Super Admin
                            @else
                                Any Administrator
                            @endif
                        </span>
                    </div>

                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Effective Validity</span>
                        <span class="bank-kv-value bank-tabular-num" style="font-size: 0.78rem;">
                            @if($promotionRule->effective_from && $promotionRule->effective_to)
                                {{ $promotionRule->effective_from->format('d M Y') }} &ndash; {{ $promotionRule->effective_to->format('d M Y') }}
                            @elseif($promotionRule->effective_from)
                                From {{ $promotionRule->effective_from->format('d M Y') }} (Open-ended)
                            @elseif($promotionRule->effective_to)
                                Until {{ $promotionRule->effective_to->format('d M Y') }}
                            @else
                                Permanent Policy (Open-ended)
                            @endif
                        </span>
                    </div>

                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Policy Status</span>
                        <span class="bank-kv-value">
                            @if($promotionRule->isActive())
                                <span class="bank-pill bank-pill-active"><span class="bank-pill-dot"></span><span>Active</span></span>
                            @else
                                <span class="bank-pill bank-pill-inactive"><span class="bank-pill-dot"></span><span>Inactive</span></span>
                            @endif
                        </span>
                    </div>

                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Author</span>
                        <span class="bank-kv-value fw-normal text-muted" style="font-size: 0.78rem;">
                            {{ $promotionRule->creator->name ?? 'System Setup' }}
                        </span>
                    </div>

                    <div class="bank-kv-row">
                        <span class="bank-kv-label">Last Audit Update</span>
                        <span class="bank-kv-value bank-tabular-num fw-normal text-muted" style="font-size: 0.78rem;">
                            {{ $promotionRule->updated_at ? $promotionRule->updated_at->format('d M Y, H:i') : '—' }}
                        </span>
                    </div>

                    <!-- Quick Action Links -->
                    <div class="pt-3 mt-3 border-top" style="border-color: var(--bank-slate-100) !important;">
                        <a href="{{ route('superadmin.promotion-rules.edit', $promotionRule) }}" 
                           class="btn btn-sm text-white fw-semibold w-100" 
                           style="border-radius: var(--bank-radius-sm); background: var(--bank-primary); border-color: var(--bank-primary); padding: 8px 14px; font-size: 0.8125rem;">
                            <i class="bi bi-pencil me-1"></i> Modify Parameters
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. ELIGIBILITY OVERVIEW -->
            <div class="bank-card">
                <div class="bank-card-header">
                    <h2 class="bank-section-title">
                        <i class="bi bi-list-check text-success"></i>
                        <span>Eligibility Overview</span>
                    </h2>
                </div>
                <div class="bank-card-body">
                    <span class="bank-micro-label mb-2">Candidate Qualification Criteria</span>

                    <!-- Checklist -->
                    <div class="bank-checklist-item">
                        <i class="bi bi-check-circle-fill bank-check-icon"></i>
                        <div>
                            <div class="fw-semibold text-dark" style="font-size: 0.8125rem;">
                                ₹{{ $promotionRule->formatted_min_team_deposit }} Team Target
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                Qualifying team volume must meet or exceed required threshold.
                            </div>
                        </div>
                    </div>

                    <div class="bank-checklist-item">
                        <i class="bi bi-check-circle-fill bank-check-icon"></i>
                        <div>
                            <div class="fw-semibold text-dark" style="font-size: 0.8125rem;">
                                {{ number_format((float) $promotionRule->rd_composition_percentage, 0) }}% Min. RD Composition
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                Recurring deposits floor enforced; FD capped at {{ number_format((float) $promotionRule->fd_composition_percentage, 0) }}%.
                            </div>
                        </div>
                    </div>

                    <div class="bank-checklist-item">
                        <i class="bi bi-check-circle-fill bank-check-icon"></i>
                        <div>
                            <div class="fw-semibold text-dark" style="font-size: 0.8125rem;">
                                @if((float) $promotionRule->min_individual_deposit > 0)
                                    ₹{{ $promotionRule->formatted_min_individual_deposit }} Personal Volume
                                @else
                                    Personal Quota Waived
                                @endif
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                {{ (float) $promotionRule->min_individual_deposit > 0 ? 'Candidate direct book requirement' : 'Fully satisfiable via downline team volume' }}
                            </div>
                        </div>
                    </div>

                    <div class="bank-checklist-item">
                        <i class="bi bi-check-circle-fill bank-check-icon"></i>
                        <div>
                            <div class="fw-semibold text-dark" style="font-size: 0.8125rem;">
                                @if(!empty($tiers))
                                    @foreach($tiers as $idx => $t)
                                        @php
                                            $fRank = isset($t['rank_id']) && $t['rank_id'] ? $ranks->firstWhere('id', $t['rank_id']) : null;
                                        @endphp
                                        <span>{{ $t['count'] }}&times; {{ $fRank?->name ?? 'Direct Agent' }}</span>@if(!$loop->last), @endif
                                    @endforeach
                                    Required
                                @else
                                    No Direct Hierarchy Quota
                                @endif
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                Frontline downline agents requirement.
                            </div>
                        </div>
                    </div>

                    <div class="bank-checklist-item">
                        <i class="bi bi-check-circle-fill bank-check-icon"></i>
                        <div>
                            <div class="fw-semibold text-dark" style="font-size: 0.8125rem;">
                                {{ $promotionRule->getCalculationPeriodLabel() }}
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                Calculation cycle and eligibility window.
                            </div>
                        </div>
                    </div>

                    <div class="bank-checklist-item">
                        <i class="bi bi-check-circle-fill bank-check-icon"></i>
                        <div>
                            <div class="fw-semibold text-dark" style="font-size: 0.8125rem;">
                                {{ $promotionRule->apply_tenure_targets ? 'Tenure Multiplier Active' : '100% Face Value Credited' }}
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                {{ $promotionRule->apply_tenure_targets ? 'Weighted by term schedule' : 'Unweighted face value calculation' }}
                            </div>
                        </div>
                    </div>

                    <!-- Associated Activity & Snapshot Preservation Notice -->
                    <div class="mt-3 p-3" style="background: var(--bank-slate-50); border: 1px solid var(--bank-slate-200);">
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                            <span class="bank-micro-label m-0">Promotion Activity</span>
                            <span class="badge" style="background: var(--bs-primary-bg-subtle, rgba(20, 83, 45, 0.08)); color: var(--bank-primary); font-size: 0.72rem; font-weight: 600; font-variant-numeric: tabular-nums;">
                                {{ $requests->total() }} Associated
                            </span>
                        </div>
                        <p class="text-muted mb-0" style="font-size: 0.73rem; line-height: 1.45;">
                            When candidates fulfill these criteria, promotion requests snapshot this exact rule version to preserve an immutable audit record.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     BOTTOM SECTION:
     Linked Promotion Requests with a clean, searchable-style data table
════════════════════════════════════════════════════════════ -->
<div class="bank-card">
    <div class="bank-card-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h2 class="bank-section-title">
                <i class="bi bi-people-fill text-success"></i>
                <span>Linked Promotion Requests</span>
            </h2>
            <span class="visually-hidden">LINKED PROMOTION REQUESTS</span>
            <span class="badge" style="background: var(--bank-slate-100); color: var(--bank-slate-600); border: 1px solid var(--bank-slate-200); font-size: 0.72rem; font-weight: 600; border-radius: 4px; font-variant-numeric: tabular-nums;">
                {{ $requests->total() }} Total
            </span>
        </div>

        <!-- Clean Searchable-Style Input -->
        <div class="bank-search-box">
            <i class="bi bi-search"></i>
            <input type="text" 
                   id="promotionRequestSearch" 
                   class="form-control" 
                   placeholder="Filter by agent, branch or status..."
                   onkeyup="filterPromotionRequests()" 
                   aria-label="Filter promotion requests">
        </div>
    </div>

    <div class="bank-card-body p-0">
        <div class="table-responsive">
            <table class="bank-table" id="promotionRequestsTable">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 140px;">Request #</th>
                        <th style="min-width: 180px;">Candidate Agent</th>
                        <th style="min-width: 140px;">Branch</th>
                        <th style="min-width: 140px;">Target Rank</th>
                        <th style="min-width: 130px;">Submitted</th>
                        <th style="width: 130px;">Status</th>
                        <th style="min-width: 140px;">Reviewed By</th>
                        <th class="pe-4 text-end" style="width: 90px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr class="promotion-request-row">
                            <td class="ps-4 fw-bold bank-tabular-num" style="color: var(--bank-slate-900); font-family: ui-monospace, monospace; font-size: 0.8125rem;">
                                {{ $req->request_number }}
                            </td>
                            <td>
                                <div class="fw-semibold text-dark" style="font-size: 0.85rem;">
                                    {{ $req->agent?->name ?? 'N/A' }}
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem; font-family: ui-monospace, monospace;">
                                    {{ $req->agent?->agent_code }}
                                </div>
                            </td>
                            <td style="color: var(--bank-slate-600);">
                                {{ $req->agent?->branch?->name ?? '—' }}
                            </td>
                            <td>
                                <span class="badge px-2 py-1" style="background: var(--bs-primary-bg-subtle, rgba(20, 83, 45, 0.08)); color: var(--bank-primary); border: 1px solid var(--bs-primary-border-subtle, rgba(20, 83, 45, 0.16)); font-size: 0.72rem; font-weight: 600; border-radius: 4px;">
                                    {{ $req->proposedLevel?->name ?? 'Target Rank' }}
                                </span>
                            </td>
                            <td class="bank-tabular-num" style="font-size: 0.78rem; color: var(--bank-slate-600);">
                                {{ $req->request_date ? $req->request_date->format('d M Y') : '—' }}
                            </td>
                            <td>
                                @if($req->status === 'PENDING_REVIEW')
                                    <span class="bank-pill bank-pill-amber">
                                        <span class="bank-pill-dot"></span>
                                        <span>PENDING</span>
                                    </span>
                                @elseif($req->status === 'APPROVED')
                                    <span class="bank-pill bank-pill-active">
                                        <span class="bank-pill-dot"></span>
                                        <span>APPROVED</span>
                                    </span>
                                @elseif($req->status === 'PROMOTED')
                                    <span class="bank-pill bank-pill-blue">
                                        <span class="bank-pill-dot"></span>
                                        <span>PROMOTED</span>
                                    </span>
                                @elseif($req->status === 'REJECTED')
                                    <span class="bank-pill bank-pill-red">
                                        <span class="bank-pill-dot"></span>
                                        <span>REJECTED</span>
                                    </span>
                                @else
                                    <span class="badge px-2 py-0.5" style="background: var(--bank-slate-100); color: var(--bank-slate-600); border: 1px solid var(--bank-slate-200); font-size: 0.7rem; border-radius: 4px;">
                                        {{ $req->status }}
                                    </span>
                                @endif
                            </td>
                            <td style="font-size: 0.8125rem; color: var(--bank-slate-600);">
                                {{ $req->reviewer?->name ?? '—' }}
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('admin.career.requests.show', $req) }}" 
                                   class="btn btn-sm btn-outline-dark" 
                                   style="border-radius: var(--bank-radius-sm); padding: 3px 9px; font-size: 0.75rem; border-color: var(--bank-slate-200); background: var(--bank-card-bg); color: var(--bank-slate-700);">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyRequestsRow">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--bank-slate-50); border: 1px solid var(--bank-slate-200); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 8px; color: var(--bank-slate-400); font-size: 1.25rem;">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <div class="fw-semibold text-dark" style="font-size: 0.875rem;">No promotion requests associated with this rule</div>
                                <p class="text-muted small mb-0" style="font-size: 0.75rem;">Eligible agent promotion requests will automatically appear here once initiated.</p>
                            </td>
                        </tr>
                    @endforelse
                    
                    <!-- Search No Results Row (Hidden by default) -->
                    <tr id="noMatchRequestsRow" style="display: none;">
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-search text-muted me-1"></i>
                            <span style="font-size: 0.8125rem;">No promotion requests matched your filter.</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="px-4 py-3 border-top" style="border-color: var(--bank-slate-100) !important;">
                {{ $requests->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<script>
    /**
     * Instant Client-side Table Filter for Linked Promotion Requests
     */
    function filterPromotionRequests() {
        const input = document.getElementById('promotionRequestSearch');
        if (!input) return;

        const filter = input.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.promotion-request-row');
        const noMatchRow = document.getElementById('noMatchRequestsRow');
        let visibleCount = 0;

        rows.forEach(function(row) {
            const text = row.innerText.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (noMatchRow) {
            if (visibleCount === 0 && rows.length > 0) {
                noMatchRow.style.display = '';
            } else {
                noMatchRow.style.display = 'none';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('promotionRequestSearch');
        if (searchInput) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    filterPromotionRequests();
                }
            });
        }
    });
</script>
@endsection
