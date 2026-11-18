@extends('superadmin.layouts.app')

@section('page-title', 'Edit Promotion Rule: ' . $promotionRule->name)

@section('content')
<style>
    /* ─── Swiss Minimalist Banking Architecture ─── */
    :root {
        --swiss-border: var(--border-color, #E2E8F0);
        --swiss-border-subtle: var(--surface-subtle, #F1F5F9);
        --swiss-slate-900: var(--text-heading, #0F172A);
        --swiss-slate-800: var(--text-body, #1E293B);
        --swiss-slate-700: var(--text-body, #334155);
        --swiss-slate-500: var(--text-muted, #64748B);
        --swiss-slate-400: var(--text-subtle, #94A3B8);
        --swiss-green-bank: var(--color-primary, #14532D);
        --swiss-green-deep: var(--color-primary-hover, #0F3F22);
    }

    .bank-micro-label {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--swiss-slate-500);
    }
    .bank-tabular-num {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }

    /* Section Cards */
    .bank-card {
        background: var(--card-bg, #FFFFFF);
        border: 1px solid var(--swiss-border);
        border-radius: 10px;
        box-shadow: var(--shadow-card);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .bank-card-header {
        background: var(--surface-subtle, #F8FAFC);
        border-bottom: 1px solid var(--swiss-border-subtle);
        padding: 14px 20px;
    }
    .bank-step-tag {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        color: var(--swiss-green-bank);
        background: var(--bs-primary-bg-subtle, #ECFDF5);
        border: 1px solid var(--bs-primary-border-subtle, #A7F3D0);
        border-radius: 4px;
        padding: 2px 6px;
        font-family: ui-monospace, monospace;
    }

    /* Inputs */
    .form-control, .form-select {
        border-color: var(--swiss-border);
        border-radius: 8px;
        font-size: 0.875rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--swiss-green-bank);
        box-shadow: none;
    }
    .input-group-text {
        border-color: var(--swiss-border);
        background-color: var(--surface-subtle, #F8FAFC);
        color: var(--text-muted, #475569);
        font-size: 0.875rem;
    }

    /* Split Bar */
    .split-track {
        height: 8px;
        width: 100%;
        background: var(--border-color, #E2E8F0);
        border-radius: 999px;
        overflow: hidden;
        display: flex;
    }
    .split-fill-rd {
        background: var(--swiss-green-bank);
        height: 100%;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .split-fill-fd {
        background: var(--color-copper, #C2410C);
        height: 100%;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Tenure Matrix Box */
    .tenure-target-box {
        background: var(--surface-subtle, #F8FAFC);
        border: 1px solid var(--swiss-border);
        border-radius: 8px;
        padding: 8px 10px;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    .tenure-target-box:focus-within {
        border-color: var(--swiss-green-bank);
        background: var(--card-bg, #FFFFFF);
    }

    /* Sidebar Sticky */
    .sticky-sidebar {
        position: sticky;
        top: 80px;
    }
</style>

<!-- Header & Quick Actions -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="page-title m-0" style="font-size: 1.5rem; font-weight: 700; color: var(--text-heading); letter-spacing: -0.025em;">
                Edit Promotion Rule
            </h1>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1" style="font-size: 0.7rem; font-weight: 600; border-radius: 6px;">
                <i class="bi bi-pencil-square me-1"></i>Policy Modification
            </span>
        </div>
        <div class="page-breadcrumb" style="font-size: 0.8rem; color: #64748B;">
            Career Operations <span class="mx-1 text-secondary opacity-50">/</span> <a href="{{ route('superadmin.promotion-rules.index') }}" class="text-decoration-none text-muted">Promotion Rules</a> <span class="mx-1 text-secondary opacity-50">/</span> <span class="text-dark fw-medium">{{ $promotionRule->name }}</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('superadmin.promotion-rules.show', $promotionRule) }}" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1.5" style="border-radius: 8px; font-weight: 500; border-color: var(--border-strong); padding: 6px 14px;">
            <i class="bi bi-eye"></i>
            <span>View Criteria</span>
        </a>
        <a href="{{ route('superadmin.promotion-rules.index') }}" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1.5" style="border-radius: 8px; font-weight: 500; border-color: var(--border-strong); padding: 6px 14px;">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Rules</span>
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-4 border" role="alert" style="border-left: 4px solid #DC2626 !important;">
    <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 mt-0.5 text-danger flex-shrink-0"></i>
        <div>
            <strong class="d-block mb-1" style="font-size: 0.9rem;">Validation Alert: Please resolve the following issues:</strong>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Historical Integrity Preserved Banner -->
<div class="alert alert-info d-flex align-items-center mb-4 border-0" role="alert" style="border-radius: 10px; border-left: 4px solid var(--bs-info, #3B82F6) !important; box-shadow: none;">
    <i class="bi bi-shield-lock-fill me-2.5 fs-5 flex-shrink-0 text-primary"></i>
    <div class="small" style="font-size: 0.8125rem;">
        <strong>Audit Integrity Safeguard:</strong>
        Updates applied here govern all future eligibility evaluations. Any past promotion requests already processed remain immutably archived with their historical criteria snapshots.
    </div>
</div>

<form method="POST" action="{{ route('superadmin.promotion-rules.update', $promotionRule) }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <!-- Main Form Column (Left - 8 Cols) -->
        <div class="col-lg-8">
            <!-- 1. Career Designation & Progression -->
            <div class="bank-card">
                <div class="bank-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="bank-step-tag">STEP 01</span>
                        <h5 class="mb-0 fw-bold text-dark" style="font-size: 0.975rem; letter-spacing: -0.01em;">
                            Career Designation &amp; Advancement Path
                        </h5>
                    </div>
                    <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.6875rem;">
                        <i class="bi bi-lock-fill me-1 text-success"></i>Sequential Enforcement
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="bank-micro-label mb-1 d-block">Rule Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $promotionRule->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size: 0.72rem;">Formal designation of this promotion step in the career ladder.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="from_rank_id" class="bank-micro-label mb-1 d-block">Current Designation (From Rank) <span class="text-danger">*</span></label>
                            <select id="from_rank_id" name="from_rank_id"
                                    class="form-select @error('from_rank_id') is-invalid @enderror" required>
                                <option value="">Select current rank…</option>
                                @foreach($ranks as $rank)
                                    <option value="{{ $rank->id }}" {{ (string) old('from_rank_id', $promotionRule->from_rank_id) === (string) $rank->id ? 'selected' : '' }}>
                                        {{ $rank->name }} (Level {{ $rank->level ?? $rank->sort_order }})
                                    </option>
                                @endforeach
                            </select>
                            @error('from_rank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size: 0.72rem;">Current agent designation prior to qualifying for this advancement.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="to_rank_id" class="bank-micro-label mb-1 d-flex justify-content-between align-items-center">
                                <span>Promoted Designation (To Rank) <span class="text-danger">*</span></span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" id="to_rank_badge" style="font-size: 0.6875rem;">
                                    <i class="bi bi-lock-fill me-1"></i>Next Rank Fixed
                                </span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0">
                                    <i class="bi bi-arrow-right text-success"></i>
                                </span>
                                <select id="to_rank_id" name="to_rank_id"
                                        class="form-select border-start-0 @error('to_rank_id') is-invalid @enderror"
                                        style="background-color: var(--surface-subtle); cursor: not-allowed; pointer-events: none;"
                                        tabindex="-1"
                                        required>
                                    @php
                                        $selectedToRankId = old('to_rank_id', $promotionRule->to_rank_id);
                                    @endphp
                                    @foreach($ranks as $rank)
                                        @if((string) $selectedToRankId === (string) $rank->id)
                                            <option value="{{ $rank->id }}" selected>
                                                {{ $rank->name }} (Level {{ $rank->level ?? $rank->sort_order }})
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            @error('to_rank_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            <div class="form-text" id="to_rank_help_text" style="font-size: 0.72rem;">
                                Promoted designation is automatically constrained to the sequential next rank.
                            </div>
                            <div class="alert alert-warning py-2 px-3 mt-2 small d-none border-0" id="max_rank_alert" role="alert" style="background: #FEF3C7; color: #92400E; border-radius: 8px;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <span id="max_rank_message">Selected rank is at the highest level. No further promotion can be configured.</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="financial_year_id" class="bank-micro-label mb-1 d-block">Financial Year Association</label>
                            <select id="financial_year_id" name="financial_year_id" class="form-select @error('financial_year_id') is-invalid @enderror">
                                <option value="">Permanent Global Policy (All Financial Cycles)</option>
                                @foreach($financialYears as $fy)
                                    <option value="{{ $fy->id }}" {{ (string) old('financial_year_id', $promotionRule->financial_year_id) === (string) $fy->id ? 'selected' : '' }}>
                                        {{ $fy->name }} ({{ $fy->code }}) {{ $fy->is_current ? '— Current Active Cycle' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('financial_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size: 0.72rem;">Bind specifically to an operating year or leave global to apply across all cycles.</div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="bank-micro-label mb-1 d-block">Policy Description &amp; Resolution Notes</label>
                            <textarea id="description" name="description" rows="2"
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $promotionRule->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Team Deposit Target & Composition -->
            <div class="bank-card">
                <div class="bank-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="bank-step-tag">STEP 02</span>
                        <h5 class="mb-0 fw-bold text-dark" style="font-size: 0.975rem; letter-spacing: -0.01em;">
                            Team Deposit Target &amp; Mandatory Split
                        </h5>
                    </div>
                    <span id="compositionSumBadge" class="badge bg-success px-2.5 py-1" style="font-size: 0.7rem; font-variant-numeric: tabular-nums;">
                        Sum: 100.00%
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="min_team_deposit" class="bank-micro-label mb-1 d-block">
                                Minimum Team Deposit Target (₹) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">₹</span>
                                <input type="number" step="0.01" min="0" id="min_team_deposit" name="min_team_deposit"
                                       class="form-control deposit-amount-input @error('min_team_deposit') is-invalid @enderror"
                                       value="{{ old('min_team_deposit', $promotionRule->min_team_deposit) }}"
                                       data-preview-target="#min_team_deposit_preview"
                                       style="font-weight: 700; font-size: 1.05rem; font-variant-numeric: tabular-nums;"
                                       required>
                            </div>
                            <div id="min_team_deposit_preview" class="small fw-semibold text-success mt-1" style="display:none; font-size:0.75rem;"></div>
                            @error('min_team_deposit')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size: 0.72rem;">Combined qualifying deposit volume of candidate plus downline tree.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="rd_composition_percentage" class="bank-micro-label mb-1 d-block">
                                Compulsory Recurring Deposit (RD) % <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="100" id="rd_composition_percentage" name="rd_composition_percentage"
                                       class="form-control @error('rd_composition_percentage') is-invalid @enderror"
                                       value="{{ old('rd_composition_percentage', $promotionRule->rd_composition_percentage) }}" required style="font-weight: 600;">
                                <span class="input-group-text bg-light text-muted">%</span>
                            </div>
                            <div class="form-text" style="font-size: 0.72rem;">Minimum % of team target required from RD accounts (Default: 10%).</div>
                            @error('rd_composition_percentage')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="fd_composition_percentage" class="bank-micro-label mb-1 d-block">
                                Maximum Fixed Deposit (FD) Cap % <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="100" id="fd_composition_percentage" name="fd_composition_percentage"
                                       class="form-control @error('fd_composition_percentage') is-invalid @enderror"
                                       value="{{ old('fd_composition_percentage', $promotionRule->fd_composition_percentage) }}" required style="font-weight: 600;">
                                <span class="input-group-text bg-light text-muted">%</span>
                            </div>
                            <div class="form-text" style="font-size: 0.72rem;">Maximum % recognized from FD accounts towards promotion (Default: 90%).</div>
                            @error('fd_composition_percentage')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <!-- Live Visual Split Preview Bar -->
                        <div class="col-12">
                            <div class="p-3 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                                <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 0.72rem;">
                                    <span class="fw-semibold text-dark">Live Portfolio Allocation:</span>
                                    <span class="text-muted">
                                        <strong class="text-success" id="ruleSplitRdText">{{ number_format($promotionRule->rd_composition_percentage, 0) }}% RD</strong> +
                                        <strong class="text-warning" id="ruleSplitFdText">{{ number_format($promotionRule->fd_composition_percentage, 0) }}% FD</strong>
                                    </span>
                                </div>
                                <div class="split-track">
                                    <div id="ruleSplitFillRd" class="split-fill-rd" style="width: {{ $promotionRule->rd_composition_percentage }}%;"></div>
                                    <div id="ruleSplitFillFd" class="split-fill-fd" style="width: {{ $promotionRule->fd_composition_percentage }}%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="bank-micro-label mb-1 d-block">Eligible Deposit Statuses <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4 flex-wrap p-2.5" style="background: var(--surface-subtle); border: 1px solid var(--border-color);">
                                @php
                                    $selectedStatuses = old('qualifying_deposit_statuses', $promotionRule->qualifying_deposit_statuses ?? ['ACTIVE', 'APPROVED']);
                                @endphp
                                @foreach($depositStatuses as $statusKey => $statusLabel)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="qualifying_deposit_statuses[]"
                                               value="{{ $statusKey }}" id="status_{{ $statusKey }}"
                                               {{ in_array($statusKey, (array) $selectedStatuses) ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-medium text-dark" for="status_{{ $statusKey }}">
                                            {{ $statusLabel }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text" style="font-size: 0.72rem;">Draft, pending approval, and rejected deposits are always excluded from evaluation.</div>
                            @error('qualifying_deposit_statuses')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Personal Volume Quota -->
            <div class="bank-card">
                <div class="bank-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="bank-step-tag">STEP 03</span>
                        <h5 class="mb-0 fw-bold text-dark" style="font-size: 0.975rem; letter-spacing: -0.01em;">
                            Personal Direct Volume Quota
                        </h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="min_individual_deposit" class="bank-micro-label mb-1 d-block">
                                Minimum Individual Direct Deposit (₹)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">₹</span>
                                <input type="number" step="0.01" min="0" id="min_individual_deposit" name="min_individual_deposit"
                                       class="form-control deposit-amount-input @error('min_individual_deposit') is-invalid @enderror"
                                       value="{{ old('min_individual_deposit', $promotionRule->min_individual_deposit) }}"
                                       data-preview-target="#min_individual_deposit_preview"
                                       style="font-weight: 700; font-size: 1rem; font-variant-numeric: tabular-nums;">
                            </div>
                            <div id="min_individual_deposit_preview" class="small fw-semibold text-success mt-1" style="display:none; font-size:0.75rem;"></div>
                            @error('min_individual_deposit')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size: 0.72rem;">
                                Deposit volume sourced directly by the agent themselves. Enter 0.00 if 100% of target can be satisfied by downline agents.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Downline Team Matrix -->
            <div class="bank-card">
                <div class="bank-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="bank-step-tag">STEP 04</span>
                        <h5 class="mb-0 fw-bold text-dark" style="font-size: 0.975rem; letter-spacing: -0.01em;">
                            Qualifying Downline Hierarchy
                        </h5>
                    </div>
                    <button type="button" id="add-tier-btn" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1" style="border-radius: 6px; font-size: 0.75rem; padding: 3px 10px; border-color: var(--border-strong);">
                        <i class="bi bi-plus-lg"></i> Add Requirement
                    </button>
                </div>
                <div class="card-body p-4">
                    <div class="text-muted small mb-3" style="font-size: 0.78rem;">
                        Specify the direct downline agents required in the candidate's hierarchy to unlock advancement.
                    </div>

                    <div id="qualifying-tiers-container" class="d-flex flex-column gap-2">
                        @php
                            $existingTiers = $promotionRule->qualifying_agent_tiers ?? [];
                            if (empty($existingTiers) && $promotionRule->min_qualifying_direct_facilitators > 0) {
                                $existingTiers = [[
                                    'count' => $promotionRule->min_qualifying_direct_facilitators,
                                    'rank_id' => $promotionRule->qualifying_rank_id ?? '',
                                ]];
                            }
                            if (empty($existingTiers)) {
                                $existingTiers = [['count' => 1, 'rank_id' => '']];
                            }
                            $tiersToRender = old('qualifying_agent_tiers', $existingTiers);
                        @endphp
                        @foreach($tiersToRender as $idx => $tier)
                            <div class="qualifying-tier-row d-flex align-items-center gap-2 p-2 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                                <div style="width: 120px;">
                                    <div class="input-group input-group-sm">
                                        <input type="number" min="0" max="100" class="form-control text-center"
                                               name="qualifying_agent_tiers[{{ $idx }}][count]"
                                               value="{{ $tier['count'] ?? 1 }}" required style="font-weight: 700;">
                                        <span class="input-group-text bg-white" style="font-size: 0.75rem;">Agents</span>
                                    </div>
                                </div>
                                <span class="text-muted small">of rank</span>
                                <div class="flex-grow-1">
                                    <select class="form-select form-select-sm" name="qualifying_agent_tiers[{{ $idx }}][rank_id]">
                                        <option value="">Any Active Designation</option>
                                        @foreach($ranks as $rank)
                                            <option value="{{ $rank->id }}" {{ (string) ($tier['rank_id'] ?? '') === (string) $rank->id ? 'selected' : '' }}>
                                                {{ $rank->name }} (Level {{ $rank->level ?? $rank->sort_order }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-tier-btn" style="border-radius: 6px; padding: 4px 8px;" title="Remove this condition">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    @error('qualifying_agent_tiers')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- 5. Deposit Calculation Period & Qualifying Statuses -->
            <div class="bank-card">
                <div class="bank-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="bank-step-tag">STEP 05</span>
                        <h5 class="mb-0 fw-bold text-dark" style="font-size: 0.975rem; letter-spacing: -0.01em;">
                            Calculation Horizon &amp; Agent Status
                        </h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="calculation_period" class="bank-micro-label mb-1 d-block">
                                Deposit Calculation Horizon <span class="text-danger">*</span>
                            </label>
                            <select id="calculation_period" name="calculation_period"
                                    class="form-select @error('calculation_period') is-invalid @enderror" required>
                                @foreach($calculationPeriods as $periodKey => $periodLabel)
                                    <option value="{{ $periodKey }}" {{ old('calculation_period', $promotionRule->calculation_period) === $periodKey ? 'selected' : '' }}>
                                        {{ $periodLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('calculation_period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size: 0.72rem;">Evaluation window over which deposits are accumulated.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="required_agent_status" class="bank-micro-label mb-1 d-block">
                                Required Agent Standing <span class="text-danger">*</span>
                            </label>
                            <select id="required_agent_status" name="required_agent_status"
                                    class="form-select @error('required_agent_status') is-invalid @enderror" required>
                                <option value="active" {{ old('required_agent_status', $promotionRule->required_agent_status) === 'active' ? 'selected' : '' }}>Active Status Only</option>
                                <option value="any" {{ old('required_agent_status', $promotionRule->required_agent_status) === 'any' ? 'selected' : '' }}>Any Status</option>
                            </select>
                            @error('required_agent_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size: 0.72rem;">Only active agents with good standing are evaluated when active.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Tenure-Based Target Multipliers -->
            <div class="bank-card">
                <div class="bank-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="bank-step-tag">STEP 06</span>
                        <h5 class="mb-0 fw-bold text-dark" style="font-size: 0.975rem; letter-spacing: -0.01em;">
                            Tenure-Based Target Multipliers
                        </h5>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="apply_tenure_targets" name="apply_tenure_targets" value="1"
                               {{ old('apply_tenure_targets', $promotionRule->apply_tenure_targets) ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold text-dark" for="apply_tenure_targets">Enable Multipliers</label>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="text-muted small mb-3" style="font-size: 0.78rem;">
                        When enabled, deposits credit target volume proportional to tenure length rather than 100% face value.
                    </div>

                    @php
                        $ruleTenure = $promotionRule->tenure_target_percentages ?? [];
                        if (is_string($ruleTenure)) {
                            $ruleTenure = json_decode($ruleTenure, true) ?? [];
                        }
                        $globalDefaults = $globalTargets['tenure_target_percentages'] ?? \App\Models\PromotionRule::DEFAULT_TENURE_TARGETS;
                        $fdTargets = $ruleTenure['fd'] ?? $ruleTenure['fd_rd'] ?? $globalDefaults['fd'] ?? [];
                        $rdTargets = $ruleTenure['rd'] ?? $ruleTenure['fd_rd'] ?? $globalDefaults['rd'] ?? [];
                        $ddTargets = $ruleTenure['dd'] ?? $globalDefaults['dd'] ?? [];
                    @endphp

                    <div id="tenure_targets_panel" class="p-3 bg-light rounded-3 border" style="border-color: var(--border-color) !important;">
                        <!-- FD Tenure -->
                        <div class="fw-semibold text-dark mb-2 small d-flex align-items-center gap-1.5">
                            <i class="bi bi-clock-history text-success"></i> Fixed Deposit (FD) Duration Weightage
                        </div>
                        <div class="row g-2 mb-3">
                            @foreach(['1' => '1 Year', '2' => '2 Years', '3' => '3 Years', '5' => '5 Years', '6' => '6 Years'] as $yr => $lbl)
                                <div class="col-4 col-md">
                                    <div class="tenure-target-box">
                                        <label class="d-block text-muted small mb-1" style="font-size: 0.7rem; font-weight: 500;">{{ $lbl }}</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.1" min="0" max="200"
                                                   name="tenure_target_percentages[fd][{{ $yr }}]"
                                                   class="form-control form-control-sm text-end"
                                                   value="{{ old("tenure_target_percentages.fd.{$yr}", $fdTargets[$yr] ?? 50.0) }}" style="font-weight: 600;">
                                            <span class="input-group-text bg-white" style="font-size: 0.7rem;">%</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- RD Tenure -->
                        <div class="fw-semibold text-dark mb-2 small d-flex align-items-center gap-1.5">
                            <i class="bi bi-arrow-repeat text-primary"></i> Recurring Deposit (RD) Duration Weightage
                        </div>
                        <div class="row g-2 mb-3">
                            @foreach(['1' => '1 Year', '2' => '2 Years', '3' => '3 Years', '5' => '5 Years', '6' => '6 Years', '7' => '7 Years'] as $yr => $lbl)
                                <div class="col-4 col-md">
                                    <div class="tenure-target-box">
                                        <label class="d-block text-muted small mb-1" style="font-size: 0.7rem; font-weight: 500;">{{ $lbl }}</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.1" min="0" max="200"
                                                   name="tenure_target_percentages[rd][{{ $yr }}]"
                                                   class="form-control form-control-sm text-end"
                                                   value="{{ old("tenure_target_percentages.rd.{$yr}", $rdTargets[$yr] ?? 50.0) }}" style="font-weight: 600;">
                                            <span class="input-group-text bg-white" style="font-size: 0.7rem;">%</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- DD Tenure -->
                        <div class="fw-semibold text-dark mb-2 small d-flex align-items-center gap-1.5">
                            <i class="bi bi-calendar-event text-warning"></i> Daily Deposit (DD) Duration Weightage
                        </div>
                        <div class="row g-2">
                            @foreach(['1' => '1 Year', '2' => '2 Years', '3' => '3 Years'] as $yr => $lbl)
                                <div class="col-4">
                                    <div class="tenure-target-box">
                                        <label class="d-block text-muted small mb-1" style="font-size: 0.7rem; font-weight: 500;">{{ $lbl }}</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.1" min="0" max="200"
                                                   name="tenure_target_percentages[dd][{{ $yr }}]"
                                                   class="form-control form-control-sm text-end"
                                                   value="{{ old("tenure_target_percentages.dd.{$yr}", $ddTargets[$yr] ?? 20.0) }}" style="font-weight: 600;">
                                            <span class="input-group-text bg-white" style="font-size: 0.7rem;">%</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column (Right - 4 Cols) -->
        <div class="col-lg-4">
            <div class="sticky-sidebar">
                <!-- 7. Governance, Workflow & Status -->
                <div class="bank-card">
                    <div class="bank-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="bank-step-tag">GOVERNANCE</span>
                            <h5 class="mb-0 fw-bold text-dark" style="font-size: 0.975rem; letter-spacing: -0.01em;">
                                Sign-off &amp; Enforcement
                            </h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="requires_approval" class="bank-micro-label mb-1 d-block">Approval Mechanism <span class="text-danger">*</span></label>
                            <select id="requires_approval" name="requires_approval"
                                    class="form-select @error('requires_approval') is-invalid @enderror" required>
                                <option value="0" {{ old('requires_approval', $promotionRule->requires_approval ? '1' : '0') == '0' ? 'selected' : '' }}>Auto Promote (Instant Execution)</option>
                                <option value="1" {{ old('requires_approval', $promotionRule->requires_approval ? '1' : '0') == '1' ? 'selected' : '' }}>Requires Review &amp; Approval</option>
                            </select>
                            <div class="form-text" style="font-size: 0.72rem;">When enabled, designation updates only upon authorized administrative sign-off.</div>
                            @error('requires_approval')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="authorized_approval_role" class="bank-micro-label mb-1 d-block">Authorized Signatory <span class="text-danger">*</span></label>
                            <select id="authorized_approval_role" name="authorized_approval_role"
                                    class="form-select @error('authorized_approval_role') is-invalid @enderror" required>
                                @foreach($approvalRoles as $roleKey => $roleLabel)
                                    <option value="{{ $roleKey }}" {{ old('authorized_approval_role', $promotionRule->authorized_approval_role) === $roleKey ? 'selected' : '' }}>
                                        {{ $roleLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('authorized_approval_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="bank-micro-label mb-1 d-block">Operational Status <span class="text-danger">*</span></label>
                            <select id="status" name="status"
                                    class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $promotionRule->status) === 'active' ? 'selected' : '' }}>Active (Enforced)</option>
                                <option value="inactive" {{ old('status', $promotionRule->status) === 'inactive' ? 'selected' : '' }}>Inactive (Draft)</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="effective_from" class="bank-micro-label mb-1 d-block">Effective From Date</label>
                            <input type="date" id="effective_from" name="effective_from"
                                   class="form-control @error('effective_from') is-invalid @enderror"
                                   value="{{ old('effective_from', $promotionRule->effective_from?->format('Y-m-d')) }}">
                            <div class="form-text" style="font-size: 0.72rem;">Leave blank for immediate applicability.</div>
                            @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="effective_to" class="bank-micro-label mb-1 d-block">Effective To Date</label>
                            <input type="date" id="effective_to" name="effective_to"
                                   class="form-control @error('effective_to') is-invalid @enderror"
                                   value="{{ old('effective_to', $promotionRule->effective_to?->format('Y-m-d')) }}">
                            <div class="form-text" style="font-size: 0.72rem;">Leave blank for open-ended applicability.</div>
                            @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <!-- Audit Ledger Card -->
                <div class="bank-card p-3 mb-3" style="background: var(--surface-subtle);">
                    <span class="bank-micro-label mb-2 d-block">Audit Trail</span>
                    <div class="d-flex flex-column gap-1.5" style="font-size: 0.75rem;">
                        <div class="d-flex justify-content-between text-muted">
                            <span>Author:</span>
                            <span class="text-dark fw-medium">{{ $promotionRule->creator?->name ?? 'System Administrator' }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted">
                            <span>Created:</span>
                            <span class="text-dark fw-medium">{{ $promotionRule->created_at?->format('d M Y, H:i') ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted">
                            <span>Last Updated:</span>
                            <span class="text-dark fw-medium">{{ $promotionRule->updated_at?->format('d M Y, H:i') ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Card -->
                <div class="bank-card p-3">
                    <button type="submit" class="btn btn-primary fw-semibold w-100 mb-2" style="border-radius: 8px; padding: 10px 16px; font-size: 0.925rem; letter-spacing: -0.01em;">
                        <i class="bi bi-check-lg me-1"></i> Update Promotion Rule
                    </button>
                    <a href="{{ route('superadmin.promotion-rules.index') }}" class="btn btn-outline-dark w-100" style="border-radius: 8px; padding: 8px 16px; font-size: 0.85rem; border-color: var(--border-strong);">
                        Cancel &amp; Discard
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @php
        $ranksList = $ranks->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'level' => $r->level ?? $r->sort_order,
                'sort_order' => (int) $r->sort_order,
                'status' => $r->status,
            ];
        })->values();
    @endphp
    const ranksData = {!! json_encode($ranksList) !!};

    const fromSelect = document.getElementById('from_rank_id');
    const toSelect = document.getElementById('to_rank_id');
    const nameInput = document.getElementById('name');
    const badgeEl = document.getElementById('to_rank_badge');
    const helpTextEl = document.getElementById('to_rank_help_text');
    const maxRankAlert = document.getElementById('max_rank_alert');
    const maxRankMsg = document.getElementById('max_rank_message');
    const submitBtn = document.querySelector('button[type="submit"]');

    function getNextRank(fromId) {
        if (!fromId) return null;
        const current = ranksData.find(r => String(r.id) === String(fromId));
        if (!current) return null;
        const nextCandidates = ranksData.filter(r => r.sort_order > current.sort_order && (!r.status || r.status === 'active'));
        if (!nextCandidates.length) return null;
        nextCandidates.sort((a, b) => a.sort_order - b.sort_order);
        return nextCandidates[0];
    }

    function syncPromotionPath() {
        if (!fromSelect || !toSelect) return;
        const fromId = fromSelect.value;
        const currentRank = ranksData.find(r => String(r.id) === String(fromId));

        if (!fromId || !currentRank) {
            toSelect.innerHTML = '<option value="">Select current rank first…</option>';
            toSelect.classList.remove('is-invalid');
            if (maxRankAlert) maxRankAlert.classList.add('d-none');
            if (submitBtn) submitBtn.disabled = false;
            if (badgeEl) {
                badgeEl.className = 'badge bg-light text-secondary border px-2 py-0.5';
                badgeEl.innerHTML = '<i class="bi bi-lock-fill me-1"></i>Auto Fixed';
            }
            if (helpTextEl) {
                helpTextEl.textContent = 'Next sequential rank is locked to preserve career progression integrity.';
            }
            return;
        }

        const nextRank = getNextRank(fromId);

        if (nextRank) {
            toSelect.innerHTML = `<option value="${nextRank.id}" selected>${nextRank.name} (Level ${nextRank.level})</option>`;
            toSelect.classList.remove('is-invalid');
            if (maxRankAlert) maxRankAlert.classList.add('d-none');
            if (submitBtn) submitBtn.disabled = false;

            if (badgeEl) {
                badgeEl.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5';
                badgeEl.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Next Rank Fixed';
            }

            if (helpTextEl) {
                helpTextEl.innerHTML = `Fixed to <strong>${nextRank.name}</strong> (Level ${nextRank.level}).`;
            }
        } else {
            toSelect.innerHTML = '<option value="">No higher rank available (Max level reached)</option>';
            toSelect.classList.add('is-invalid');
            if (maxRankAlert) {
                maxRankMsg.textContent = `${currentRank.name} (Level ${currentRank.level}) is the highest career rank. No promotion rule can be configured beyond this level.`;
                maxRankAlert.classList.remove('d-none');
            }
            if (submitBtn) submitBtn.disabled = true;

            if (badgeEl) {
                badgeEl.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5';
                badgeEl.innerHTML = '<i class="bi bi-slash-circle me-1"></i>Max Rank';
            }

            if (helpTextEl) {
                helpTextEl.textContent = 'No subsequent rank exists in the career ladder.';
            }
        }
    }

    fromSelect.addEventListener('change', syncPromotionPath);
    fromSelect.addEventListener('input', syncPromotionPath);
});

// ── Qualifying Agent Tiers ───────────────────────────────────────────
(function () {
    const container = document.getElementById('qualifying-tiers-container');
    const addBtn    = document.getElementById('add-tier-btn');

    if (!container || !addBtn) return;

    function reindexTiers() {
        container.querySelectorAll('.qualifying-tier-row').forEach((row, idx) => {
            row.querySelector('input[type=number]').name  = `qualifying_agent_tiers[${idx}][count]`;
            row.querySelector('select').name              = `qualifying_agent_tiers[${idx}][rank_id]`;
        });
    }

    container.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.remove-tier-btn');
        if (!removeBtn) return;
        const rows = container.querySelectorAll('.qualifying-tier-row');
        if (rows.length <= 1) {
            rows[0].querySelector('input[type="number"]').value = 0;
            rows[0].querySelector('select').value = '';
            return;
        }
        removeBtn.closest('.qualifying-tier-row').remove();
        reindexTiers();
    });

    addBtn.addEventListener('click', function () {
        const firstRow = container.querySelector('.qualifying-tier-row');
        if (!firstRow) return;
        const newRow   = firstRow.cloneNode(true);
        const idx      = container.querySelectorAll('.qualifying-tier-row').length;
        newRow.querySelector('input[type="number"]').value = 1;
        newRow.querySelector('select').value = '';
        newRow.querySelector('input[type="number"]').name = `qualifying_agent_tiers[${idx}][count]`;
        newRow.querySelector('select').name               = `qualifying_agent_tiers[${idx}][rank_id]`;
        container.appendChild(newRow);
    });
})();

// ── Live Cash Text Preview ───────────────────────────────────────────
(function () {
    function formatIndianCashText(num) {
        num = parseFloat(num);
        if (!num || isNaN(num) || num <= 0) return '';
        let text = '';
        if (num >= 10000000) {
            let cr = (num / 10000000);
            text = (cr % 1 === 0 ? cr : cr.toFixed(2).replace(/\.?0+$/, '')) + ' Crore';
        } else if (num >= 100000) {
            let lk = (num / 100000);
            text = (lk % 1 === 0 ? lk : lk.toFixed(2).replace(/\.?0+$/, '')) + ' Lakh';
        } else if (num >= 1000) {
            let th = (num / 1000);
            text = (th % 1 === 0 ? th : th.toFixed(2).replace(/\.?0+$/, '')) + ' Thousand';
        } else {
            text = num.toLocaleString('en-IN');
        }
        return text;
    }

    function updateDepositPreviews() {
        document.querySelectorAll('.deposit-amount-input').forEach(input => {
            const targetSelector = input.getAttribute('data-preview-target');
            if (!targetSelector) return;
            const previewEl = document.querySelector(targetSelector);
            if (!previewEl) return;

            const val = parseFloat(input.value);
            if (val > 0) {
                const text = formatIndianCashText(val);
                previewEl.innerHTML = `<i class="bi bi-info-circle me-1"></i>${text} (₹${val.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})})`;
                previewEl.style.display = 'block';
            } else {
                previewEl.style.display = 'none';
                previewEl.textContent = '';
            }
        });
    }

    document.querySelectorAll('.deposit-amount-input').forEach(input => {
        input.addEventListener('input', updateDepositPreviews);
        input.addEventListener('change', updateDepositPreviews);
    });

    updateDepositPreviews();
})();

// ── Live Composition Sum & Dynamic Split Bar Validation ──────────────
(function () {
    const rdInput = document.getElementById('rd_composition_percentage');
    const fdInput = document.getElementById('fd_composition_percentage');
    const badge = document.getElementById('compositionSumBadge');
    const fillRd = document.getElementById('ruleSplitFillRd');
    const fillFd = document.getElementById('ruleSplitFillFd');
    const textRd = document.getElementById('ruleSplitRdText');
    const textFd = document.getElementById('ruleSplitFdText');

    if (!rdInput || !fdInput || !badge) return;

    function updateSum() {
        const rd = parseFloat(rdInput.value) || 0;
        const fd = parseFloat(fdInput.value) || 0;
        const sum = Math.round((rd + fd) * 100) / 100;

        if (textRd) textRd.textContent = Math.round(rd) + '% RD';
        if (textFd) textFd.textContent = Math.round(fd) + '% FD';

        if (fillRd && fillFd) {
            const safeSum = sum > 0 ? sum : 100;
            const rdWidth = Math.min(100, Math.max(0, (rd / safeSum) * 100));
            const fdWidth = Math.min(100, Math.max(0, (fd / safeSum) * 100));
            fillRd.style.width = rdWidth + '%';
            fillFd.style.width = fdWidth + '%';
        }

        if (Math.abs(sum - 100.0) < 0.01) {
            badge.className = 'badge bg-success px-2.5 py-1';
            badge.textContent = 'Sum: 100.00% (Valid)';
        } else {
            badge.className = 'badge bg-danger px-2.5 py-1';
            badge.textContent = 'Sum: ' + sum.toFixed(2) + '% (Must be 100%)';
        }
    }

    rdInput.addEventListener('input', updateSum);
    fdInput.addEventListener('input', updateSum);
    updateSum();
})();
</script>
@endpush
