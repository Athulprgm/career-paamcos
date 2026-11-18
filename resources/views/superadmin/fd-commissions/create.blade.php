@extends('superadmin.layouts.app')

@section('page-title', 'Add Deposit Commission')

@section('content')
<style>
    .fd-form-card .table-responsive {
        border: none;
        border-radius: 0;
        box-shadow: none;
        background: transparent;
    }
    .senior-rate-preview-bar {
        background: var(--surface-subtle);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 0.78rem;
    }
</style>
<div class="page-header">
    <div>
        <h1 class="page-title">Add Deposit Commission <span class="visually-hidden">Create Fixed Deposit (FD) Scheme</span></h1>
        <div class="page-breadcrumb">Commission / <a href="{{ route('superadmin.fd-commissions.index') }}" style="color:inherit;">Deposit Commissions</a> / New</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('superadmin.fd-commissions.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Please fix the errors below:</strong>
    <ul class="mb-0 mt-1 ps-3" style="font-size:0.8rem;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form method="POST" action="{{ route('superadmin.fd-commissions.store') }}" id="fdSchemeForm">
    @csrf

    <!-- Scheme Fetcher / Preloader -->
    <div class="card mb-3">
        <div class="card-body py-2 px-3">
            <div class="row align-items-center g-2">
                <div class="col-md-7">
                    <label for="deposit_scheme_id" class="form-label mb-1" style="font-size:0.8rem;">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Fetch from Existing Deposit Scheme / Template (Optional)
                    </label>
                    <div class="d-flex gap-2">
                        <select id="deposit_scheme_id" name="deposit_scheme_id" class="form-select form-select-sm">
                            <option value="">-- Start from Scratch (Blank) --</option>
                            @foreach($depositSchemes as $ds)
                                @php
                                    $isSelected = (string) old('deposit_scheme_id', $selectedScheme?->id) === (string) $ds->id;
                                    $dsSeniorExtra = (float) ($ds->senior_citizen_extra_rate ?? 0);
                                    $dsSeniorTotal = (float) $ds->interest_rate + $dsSeniorExtra;
                                @endphp
                                <option value="{{ $ds->id }}" {{ $isSelected ? 'selected' : '' }}>
                                    {{ $ds->name }} ({{ $ds->code }}) — {{ $ds->scheme_type }} &bull; {{ number_format((float) $ds->interest_rate, 2) }}%@if($dsSeniorExtra > 0) (Sr: {{ number_format($dsSeniorTotal, 2) }}%)@endif &bull; {{ $ds->tenure }} {{ $ds->tenure_unit }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-5">
                    <div id="schemeStatusAlert" class="p-2 border" style="background:var(--surface-subtle);font-size:0.775rem;">
                        <div class="d-flex align-items-center justify-content-between">
                            <span id="schemeAlertTitle" class="fw-semibold text-dark">Dynamic Scheme Creator</span>
                            <span id="lblFormMode" class="badge-active">New Scheme</span>
                        </div>
                        <div id="schemeAlertDesc" class="text-muted mt-1" style="font-size:0.75rem;">
                            Configure single exact-tenure commission plan (e.g. 1 Year, 2 Year) for this scheme.
                        </div>
                        <div id="schemeAlertBadges" class="d-none gap-1 mt-1 flex-wrap">
                            <span class="badge bg-light text-dark border font-monospace" id="badgeSchemeCode"></span>
                            <span class="badge bg-light text-dark border" id="badgeSchemeType"></span>
                            <span class="badge bg-light text-dark border" id="badgeSchemeInterest"></span>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle d-none" id="badgeSchemeSeniorInterest"></span>
                            <span class="badge bg-light text-dark border" id="badgeSchemeTenure"></span>
                            <span class="badge bg-light text-dark border" id="badgeSchemeStatus"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Intent Mode -->
            <input type="hidden" name="action_mode" id="action_mode" value="{{ $selectedScheme ? 'update_existing' : 'create_new' }}">
            <div class="mt-2 pt-2 border-top" id="actionModeContainer" style="{{ $selectedScheme ? 'display:block;' : 'display:none;' }}">
                <div class="d-flex align-items-center justify-content-between text-success" style="font-size:0.8rem;">
                    <span>
                        <i class="bi bi-shield-check me-1"></i>Configuring rank commissions for: <strong id="selectedSchemeNameBadge">{{ $selectedScheme?->name }} ({{ $selectedScheme?->code }})</strong>.
                    </span>
                    <span class="text-muted" style="font-size:0.75rem;">
                        Commissions will be saved against this scheme ID. No duplicate scheme will be created.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="payout_year" id="payoutYearInput" value="{{ old('payout_year', max(1, $selectedScheme?->getTenureInYears() ?? 1)) }}">

    @php
        $schemeRankMap = $selectedScheme ? $selectedScheme->rankCommissions->keyBy('rank_id') : collect();
    @endphp

    <div class="row g-3">
        <!-- Scheme Details -->
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Scheme Specifications</span>
                    <span class="badge bg-light text-muted border font-monospace" style="font-size:0.7rem;">Exact Tenure Scheme</span>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-dark mb-1">
                            Scheme Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="name" name="name"
                               class="form-control form-control-sm @error('name') is-invalid @enderror"
                               value="{{ old('name', $selectedScheme?->name) }}"
                               placeholder="e.g. FD 1 Year Regular, FD 2 Year Growth"
                               list="scheme_name_presets" required autocomplete="off">
                        <datalist id="scheme_name_presets">
                            <option value="FD 1 Year (12 Months)">
                            <option value="FD 2 Year (24 Months)">
                            <option value="FD 3 Year (36 Months)">
                            <option value="FD 5 Year (60 Months)">
                            <option value="RD 1 Year (12 Months)">
                            <option value="RD 2 Year (24 Months)">
                            <option value="RD 3 Year (36 Months)">
                            <option value="Daily Deposit 100 Days">
                            <option value="Daily Deposit 365 Days">
                            <option value="Monthly Income Scheme 1 Year">
                            <option value="Monthly Income Scheme 3 Year">
                        </datalist>
                        <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Type a descriptive name or select a tenure preset above.</small>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="code" class="form-label small fw-semibold text-dark mb-0">
                                    Scheme Code <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none fw-semibold" id="btnAutoGenCode" style="font-size:0.75rem;" title="Auto-generate clean unique code">
                                    <i class="bi bi-magic me-1"></i>Auto Gen
                                </button>
                            </div>
                            <input type="text" id="code" name="code"
                                   class="form-control form-control-sm font-monospace text-uppercase fw-semibold @error('code') is-invalid @enderror"
                                   value="{{ old('code', $selectedScheme?->code) }}"
                                   maxlength="30"
                                   placeholder="e.g. FDKDSP5Y" required>
                            <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Format: DepositType + SchemeAbbr + Tenure (e.g. FDKDSP5Y).</small>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="scheme_type" class="form-label small fw-semibold text-dark mb-1">
                                Type <span class="text-danger">*</span>
                            </label>
                            <select id="scheme_type" name="scheme_type" class="form-select form-select-sm @error('scheme_type') is-invalid @enderror" required>
                                @foreach($schemeTypes as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}" {{ old('scheme_type', $selectedScheme?->scheme_type ?? 'FD') === $typeKey ? 'selected' : '' }}>
                                        {{ $typeLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('scheme_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                            <label for="interest_rate" class="form-label small fw-semibold text-dark mb-1">
                                Interest Rate (%) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="100" id="interest_rate" name="interest_rate"
                                       class="form-control form-control-sm text-end @error('interest_rate') is-invalid @enderror"
                                       value="{{ old('interest_rate', $selectedScheme ? number_format((float) $selectedScheme->interest_rate, 2, '.', '') : '') }}"
                                       placeholder="7.50" required>
                                <span class="input-group-text bg-light text-muted">% p.a.</span>
                            </div>
                            @error('interest_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="senior_citizen_extra_rate" class="form-label small fw-semibold text-dark mb-1">
                                <i class="bi bi-person-plus text-primary me-1"></i>Senior Citizen Extra (%)
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="10" id="senior_citizen_extra_rate" name="senior_citizen_extra_rate"
                                       class="form-control form-control-sm text-end @error('senior_citizen_extra_rate') is-invalid @enderror"
                                       value="{{ old('senior_citizen_extra_rate', $selectedScheme ? number_format((float) ($selectedScheme->senior_citizen_extra_rate ?? 0), 2, '.', '') : '0.00') }}"
                                       placeholder="0.50">
                                <span class="input-group-text bg-light text-muted">+% p.a.</span>
                            </div>
                            @error('senior_citizen_extra_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    @php
                        $initBaseRate = (float) old('interest_rate', $selectedScheme?->interest_rate ?? 0);
                        $initSeniorExtra = (float) old('senior_citizen_extra_rate', $selectedScheme?->senior_citizen_extra_rate ?? 0);
                        $initSeniorTotal = $initBaseRate + $initSeniorExtra;
                    @endphp
                    <div class="senior-rate-preview-bar d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3" id="seniorRateSummaryBar">
                        <span class="text-muted">
                            <i class="bi bi-person-heart text-primary me-1"></i>Senior Citizen Effective Rate:
                        </span>
                        <span class="fw-bold font-monospace text-primary" id="seniorTotalRatePreview">
                            {{ number_format($initSeniorTotal, 2) }}% p.a.
                            <span class="text-muted fw-normal small" id="seniorRateBreakdownText">
                                ({{ number_format($initBaseRate, 2) }}% + {{ number_format($initSeniorExtra, 2) }}%)
                            </span>
                        </span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="tenure" class="form-label small fw-semibold text-dark mb-1">
                                Tenure <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" min="1" max="1200" id="tenure" name="tenure"
                                       class="form-control form-control-sm text-center @error('tenure') is-invalid @enderror"
                                       value="{{ old('tenure', $selectedScheme?->tenure ?? 12) }}" required>
                                <select name="tenure_unit" id="tenure_unit" class="form-select form-select-sm" style="max-width: 105px;">
                                    @foreach($tenureUnits as $unitKey => $unitLabel)
                                        <option value="{{ $unitKey }}" {{ old('tenure_unit', $selectedScheme?->tenure_unit ?? 'MONTHS') === $unitKey ? 'selected' : '' }}>
                                            {{ $unitLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('tenure')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="collection_frequency" class="form-label small fw-semibold text-dark mb-1">Collection</label>
                            <select id="collection_frequency" name="collection_frequency" class="form-select form-select-sm @error('collection_frequency') is-invalid @enderror">
                                @foreach($collectionFrequencies as $freqKey => $freqLabel)
                                    <option value="{{ $freqKey }}" {{ old('collection_frequency', $selectedScheme?->collection_frequency ?? 'ONE_TIME') === $freqKey ? 'selected' : '' }}>
                                        {{ $freqLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('collection_frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="min_amount" class="form-label small fw-semibold text-dark mb-1">Min Deposit</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">₹</span>
                                <input type="number" step="100" min="0" id="min_amount" name="min_amount"
                                       class="form-control form-control-sm @error('min_amount') is-invalid @enderror"
                                       value="{{ old('min_amount', $selectedScheme ? $selectedScheme->min_amount : 1000) }}">
                            </div>
                            @error('min_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="max_amount" class="form-label small fw-semibold text-dark mb-1">Max Deposit</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">₹</span>
                                <input type="number" step="100" min="0" id="max_amount" name="max_amount"
                                       class="form-control form-control-sm @error('max_amount') is-invalid @enderror"
                                       value="{{ old('max_amount', $selectedScheme?->max_amount) }}" placeholder="Optional">
                            </div>
                            @error('max_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="interest_payout_frequency" class="form-label small fw-semibold text-dark mb-1">Interest Payout</label>
                            <select id="interest_payout_frequency" name="interest_payout_frequency" class="form-select form-select-sm @error('interest_payout_frequency') is-invalid @enderror">
                                @foreach($payoutFrequencies as $payoutKey => $payoutLabel)
                                    <option value="{{ $payoutKey }}" {{ old('interest_payout_frequency', $selectedScheme?->interest_payout_frequency ?? 'AT_MATURITY') === $payoutKey ? 'selected' : '' }}>
                                        {{ $payoutLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('interest_payout_frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="status" class="form-label small fw-semibold text-dark mb-1">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select form-select-sm @error('status') is-invalid @enderror">
                                <option value="ACTIVE" {{ old('status', $selectedScheme?->status ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                                <option value="INACTIVE" {{ old('status', $selectedScheme?->status) === 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3 p-3 rounded border" style="background: var(--surface-subtle);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="promotion_target_percentage" class="form-label small fw-semibold text-dark mb-0">
                                <i class="bi bi-trophy text-warning me-1"></i>Promotion Target Credit %
                            </label>
                            <button type="button" class="btn btn-link p-0 text-decoration-none fw-semibold" id="btnAutoSuggestPromoTarget" style="font-size:0.75rem;" title="Auto-suggest standard rate based on tenure">
                                <i class="bi bi-magic me-1"></i>Auto Suggest
                            </button>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" max="100" id="promotion_target_percentage" name="promotion_target_percentage"
                                   class="form-control form-control-sm text-end fw-semibold @error('promotion_target_percentage') is-invalid @enderror"
                                   value="{{ old('promotion_target_percentage', isset($selectedScheme) && $selectedScheme->promotion_target_percentage !== null ? number_format((float) $selectedScheme->promotion_target_percentage, 2, '.', '') : '') }}"
                                   placeholder="e.g. 20.00">
                            <span class="input-group-text bg-white text-muted">% credited</span>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:0.75rem;">
                            Weighted % of deposit volume counted toward agent rank promotion targets (e.g. 1Y: 20%, 2Y: 30%, 3Y: 40%, 5Y: 80%, 7Y RD: 90%, DD 1Y: 20%, 2Y: 40%, 3Y: 50%).
                        </small>
                        @error('promotion_target_percentage')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label small fw-semibold text-dark mb-1">Description</label>
                        <textarea id="description" name="description" rows="2"
                                  class="form-control form-control-sm @error('description') is-invalid @enderror"
                                  placeholder="Optional scheme description or terms">{{ old('description', $selectedScheme?->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="notes" class="form-label small fw-semibold text-dark mb-1">Audit Note</label>
                        <input type="text" id="notes" name="notes"
                               class="form-control form-control-sm"
                               value="{{ old('notes', $selectedScheme ? "Configured rank commissions for {$selectedScheme->name} ({$selectedScheme->code})." : 'Initial scheme creation with rank commission configuration.') }}"
                               placeholder="Reason for change">
                    </div>
                </div>
            </div>
        </div>

        <!-- Rank-wise Commission Configuration -->
        <div class="col-lg-7">
            <div class="card mb-3 fd-form-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <span>Rank-wise Commission Configuration</span>
                        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:400;">
                            Set commission percentages for each agent rank under this deposit scheme
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-dark btn-sm" id="btnSelectAll">All</button>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="btnDeselectAll">None</button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 48px;" class="text-center">Active</th>
                                    <th>Agent Rank</th>
                                    <th style="width: 70px;" class="text-center">Level</th>
                                    <th style="width: 150px;" class="text-end pe-3">Commission %</th>
                                </tr>
                            </thead>
                            <tbody id="rankCommissionTableBody">
                                @forelse($ranks as $index => $rank)
                                    @php
                                        $defaultEligible = $selectedScheme && $schemeRankMap->has($rank->id)
                                            ? (bool) $schemeRankMap->get($rank->id)->is_eligible
                                            : true;
                                        $defaultPct = $selectedScheme && $schemeRankMap->has($rank->id)
                                            ? ($schemeRankMap->get($rank->id)->commission_percentage !== null ? number_format((float) $schemeRankMap->get($rank->id)->commission_percentage, 2, '.', '') : '')
                                            : '';
                                        $oldEligible = old("ranks.{$index}.is_eligible", $defaultEligible);
                                        $oldPct = old("ranks.{$index}.commission_percentage", $defaultPct);
                                    @endphp
                                    <tr class="rank-row {{ $oldEligible ? '' : 'text-muted bg-light' }}" id="row-rank-{{ $rank->id }}">
                                        <td class="text-center">
                                            <input type="hidden" name="ranks[{{ $index }}][rank_id]" value="{{ $rank->id }}">
                                            <input type="checkbox"
                                                   name="ranks[{{ $index }}][is_eligible]"
                                                   value="1"
                                                   class="form-check-input rank-eligible-toggle"
                                                   id="toggle-{{ $rank->id }}"
                                                   data-rank-id="{{ $rank->id }}"
                                                   {{ $oldEligible ? 'checked' : '' }}>
                                        </td>
                                        <td>
                                            <label for="toggle-{{ $rank->id }}" class="mb-0 fw-medium text-dark user-select-none d-block" style="cursor: pointer;">
                                                {{ $rank->name }}
                                            </label>
                                            <span class="text-muted small font-monospace">{{ $rank->code ?? '' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border">
                                                L{{ $rank->level ?? ($rank->sort_order ?? $index + 1) }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="input-group input-group-sm ms-auto" style="max-width: 130px;">
                                                <input type="number"
                                                       step="0.01"
                                                       min="0"
                                                       max="100"
                                                       name="ranks[{{ $index }}][commission_percentage]"
                                                       id="pct-{{ $rank->id }}"
                                                       class="form-control form-control-sm text-end rank-commission-input @error("ranks.{$index}.commission_percentage") is-invalid @enderror"
                                                       value="{{ $oldPct }}"
                                                       placeholder="0.00"
                                                       data-rank-id="{{ $rank->id }}"
                                                       {{ $oldEligible ? '' : 'disabled' }}>
                                                <span class="input-group-text bg-light text-muted">%</span>
                                            </div>
                                            @error("ranks.{$index}.commission_percentage")
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small">
                                            No agent ranks found in system.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Total Commission Summary Bar -->
                <div class="card-footer bg-transparent border-top py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="small text-muted">
                        <span id="lblEligibleCount" class="fw-semibold text-dark">0</span> of {{ count($ranks) }} ranks eligible
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small fw-semibold text-muted text-uppercase">Total Commission:</span>
                        <span class="badge bg-dark text-white fs-6 font-monospace px-3 py-2" id="badgeTotalCommission">
                            0.00%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Form Action Buttons -->
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('superadmin.fd-commissions.index') }}" class="btn btn-outline-dark">
                    Cancel
                </a>
                <button type="submit" class="btn btn-dark" id="btnSubmitScheme">
                    <i class="bi bi-check-lg me-1"></i> <span id="btnSubmitText">Save Scheme</span>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const depositSchemesData = @json($schemesJson ?? []);
    const schemeSelect    = document.getElementById('deposit_scheme_id');
    const eligibleToggles = document.querySelectorAll('.rank-eligible-toggle');
    const commissionInputs = document.querySelectorAll('.rank-commission-input');
    const badgeTotal      = document.getElementById('badgeTotalCommission');
    const lblCount        = document.getElementById('lblEligibleCount');
    const btnSelectAll    = document.getElementById('btnSelectAll');
    const btnDeselectAll  = document.getElementById('btnDeselectAll');
    const btnSubmitText   = document.getElementById('btnSubmitText');
    const lblFormMode     = document.getElementById('lblFormMode');
    const payoutYearInput = document.getElementById('payoutYearInput');

    /* ═══════════ Commission total ════════════════════════════════════ */
    function calculateTotal() {
        let total = 0, count = 0;
        eligibleToggles.forEach(function (toggle) {
            const rid   = toggle.getAttribute('data-rank-id');
            const input = document.getElementById('pct-' + rid);
            const row   = document.getElementById('row-rank-' + rid);
            if (toggle.checked) {
                count++;
                if (input) {
                    input.removeAttribute('disabled');
                    total += parseFloat(input.value) || 0;
                }
                if (row) row.classList.remove('text-muted', 'bg-light');
            } else {
                if (input) input.setAttribute('disabled', 'disabled');
                if (row)   row.classList.add('text-muted', 'bg-light');
            }
        });

        badgeTotal.textContent = total.toFixed(2) + '%';
        lblCount.textContent   = count;
        badgeTotal.className   = total > 100
            ? 'badge bg-danger text-white fs-6 font-monospace px-3 py-2'
            : 'badge bg-dark text-white fs-6 font-monospace px-3 py-2';
    }

    /* ═══════════ Code auto-generation ════════════════════════════════ */
    const actionModeContainer = document.getElementById('actionModeContainer');
    const modeCreateNew       = document.getElementById('mode_create_new');
    const modeUpdateExisting  = document.getElementById('mode_update_existing');
    const btnAutoGenCode      = document.getElementById('btnAutoGenCode');
    const nameInput           = document.getElementById('name');
    const codeInput           = document.getElementById('code');
    const typeSelect          = document.getElementById('scheme_type');
    const tenureInput         = document.getElementById('tenure');
    const tenureUnitSelect    = document.getElementById('tenure_unit');
    const isClone             = @json($isClone ?? false);

    function getExistingCodes() {
        return Object.values(depositSchemesData).map(function (s) { return (s.code || '').toUpperCase().trim(); });
    }

    function extractAbbr(name, depositType) {
        if (!name) return '';
        let clean = name.trim();
        clean = clean.replace(/\(\s*\d+\s*(?:years?|yrs?|months?|mos?|days?|y|m|d)\s*\)/gi, '');
        clean = clean.replace(/\b\d+\s*(?:years?|yrs?|months?|mos?|days?|y|m|d)\b/gi, '');
        const dt = (depositType || 'FD').toUpperCase().trim();
        if (dt === 'FD') clean = clean.replace(/^(fixed\s*deposit|fd)\b\s*/i, '');
        else if (dt === 'RD') clean = clean.replace(/^(recurring\s*deposit|rd)\b\s*/i, '');
        else if (dt === 'DD') clean = clean.replace(/^(daily\s*deposit|dd|pigmy)\b\s*/i, '');
        else if (dt === 'MIS') clean = clean.replace(/^(monthly\s*income\s*scheme|mis)\b\s*/i, '');
        clean = clean.replace(/[^a-zA-Z0-9\s]/g, ' ').trim();
        if (!clean) return '';
        let words = clean.split(/\s+/).filter(Boolean);
        if (!words.length) return '';
        if (words.length > 2) {
            const stopWords = ['AND', 'OF', 'FOR', 'THE', 'IN', 'ON', 'AT', 'TO', 'A', 'AN', 'WITH'];
            const filtered = words.filter(w => !stopWords.includes(w.toUpperCase()));
            if (filtered.length > 0) words = filtered;
        }
        if (words.length >= 2) return words.map(w => w.charAt(0).toUpperCase()).join('');
        const single = words[0];
        if (single.length <= 2) return single.toUpperCase();
        if (single === single.toUpperCase() && single.length <= 4) return single;
        const first = single.charAt(0).toUpperCase();
        const m = single.substring(1).match(/[bcdfghjklmnpqrstvwxyz]/i);
        return m ? (first + m[0].toUpperCase()) : single.substring(0, 2).toUpperCase();
    }

    function updatePayoutYear() {
        const ten  = Math.max(1, parseInt(tenureInput ? tenureInput.value : 1) || 1);
        const unit = ((tenureUnitSelect ? tenureUnitSelect.value : 'MONTHS') || 'MONTHS').toUpperCase();
        let tenureInYears = 1;
        if (unit === 'YEARS') tenureInYears = ten;
        else if (unit === 'MONTHS') tenureInYears = Math.max(1, Math.floor(ten / 12));
        else if (unit === 'DAYS') tenureInYears = Math.max(1, Math.floor(ten / 365));
        if (payoutYearInput) payoutYearInput.value = tenureInYears;
    }

    const interestRateInput = document.getElementById('interest_rate');
    const seniorExtraInput  = document.getElementById('senior_citizen_extra_rate');
    const seniorTotalEl     = document.getElementById('seniorTotalRatePreview');

    function updateSeniorRatePreview(isDoubleScheme = false) {
        if (!seniorTotalEl) return;
        const baseRate = parseFloat(interestRateInput ? interestRateInput.value : 0) || 0;
        const seniorExtra = parseFloat(seniorExtraInput ? seniorExtraInput.value : 0) || 0;
        if (isDoubleScheme) {
            seniorTotalEl.innerHTML = 'Double Feature <span class="text-muted fw-normal small">(2&times; at maturity)</span>';
            return;
        }
        const total = baseRate + seniorExtra;
        seniorTotalEl.innerHTML = `${total.toFixed(2)}% p.a. <span class="text-muted fw-normal small">(${baseRate.toFixed(2)}% + ${seniorExtra.toFixed(2)}%)</span>`;
    }

    if (interestRateInput) {
        interestRateInput.addEventListener('input', function () { updateSeniorRatePreview(false); });
    }
    if (seniorExtraInput) {
        seniorExtraInput.addEventListener('input', function () { updateSeniorRatePreview(false); });
    }

    function generateUniqueCode() {
        const type = ((typeSelect ? typeSelect.value : 'FD') || 'FD').toUpperCase().trim();
        const ten  = Math.max(1, parseInt(tenureInput ? tenureInput.value : 1) || 1);
        const unit = ((tenureUnitSelect ? tenureUnitSelect.value : 'YEARS') || 'YEARS').toUpperCase();
        let suf = 'M';
        if (unit === 'YEARS' || unit === 'YEAR' || unit === 'Y') suf = 'Y';
        else if (unit === 'DAYS' || unit === 'DAY' || unit === 'D') suf = 'D';

        const abbr = extractAbbr(nameInput ? nameInput.value : '', type);
        const base = (type + abbr + ten + suf).toUpperCase().substring(0, 30);
        const existing = getExistingCodes();
        let final = base, c = 1;
        const val = schemeSelect ? schemeSelect.value : null;
        if (val && depositSchemesData[val] && !isClone) {
            const originalCode = depositSchemesData[val].code || '';
            if (codeInput) {
                codeInput.value = originalCode;
                codeInput.dataset.autoGenerated = 'false';
            }
            return originalCode;
        }

        while (existing.includes(final)) { 
            const suffix = '-' + (c++);
            const pLen = Math.max(1, 30 - suffix.length);
            final = base.substring(0, pLen) + suffix;
        }
        if (codeInput) { 
            codeInput.value = final; 
            codeInput.dataset.autoGenerated = 'true'; 
        }
        return final;
    }

    if (btnAutoGenCode) {
        btnAutoGenCode.addEventListener('click', function () {
            generateUniqueCode();
            if (codeInput) {
                codeInput.classList.add('border-primary');
                setTimeout(function () { codeInput.classList.remove('border-primary'); }, 600);
            }
        });
    }
    if (codeInput) { codeInput.addEventListener('input', function () { this.dataset.autoGenerated = 'false'; }); }

    if (nameInput) {
        nameInput.addEventListener('input', function () {
            const val = this.value.trim(); if (!val) return;
            const upper = val.toUpperCase();
            if (typeSelect) {
                if (upper.includes('RECUR') || /\bRD\b/.test(upper))              typeSelect.value = 'RD';
                else if (upper.includes('DAIL') || upper.includes('PIGMY') || /\bDD\b/.test(upper)) typeSelect.value = 'DD';
                else if (upper.includes('MONTHLY INCOME') || /\bMIS\b/.test(upper)) typeSelect.value = 'MIS';
                else if (upper.includes('FIXED') || /\bFD\b/.test(upper))         typeSelect.value = 'FD';
            }
            const ym = val.match(/(\d+)\s*(?:year|yr|y)\b/i);
            const mm = val.match(/(\d+)\s*(?:month|mo|m)\b/i);
            const dm = val.match(/(\d+)\s*(?:day|d)\b/i);
            if (ym && tenureInput && tenureUnitSelect) { tenureInput.value = ym[1]; tenureUnitSelect.value = 'YEARS'; }
            else if (mm && tenureInput && tenureUnitSelect) { tenureInput.value = mm[1]; tenureUnitSelect.value = 'MONTHS'; }
            else if (dm && tenureInput && tenureUnitSelect) { tenureInput.value = dm[1]; tenureUnitSelect.value = 'DAYS'; }
            updatePayoutYear();
            const selScheme = schemeSelect ? schemeSelect.value : null;
            if (!selScheme && codeInput && (!codeInput.value || codeInput.dataset.autoGenerated === 'true')) generateUniqueCode();
        });
    }

    if (tenureInput) {
        tenureInput.addEventListener('input',  function () { 
            updatePayoutYear();
            const selScheme = schemeSelect ? schemeSelect.value : null; 
            if (!selScheme && codeInput && (!codeInput.value || codeInput.dataset.autoGenerated === 'true')) generateUniqueCode(); 
            if (!selScheme && promoTargetInput && (!promoTargetInput.value || promoTargetInput.dataset.autoFilled === 'true')) {
                const sug = getSuggestedPromoTarget(typeSelect ? typeSelect.value : 'FD', tenureInput.value, tenureUnitSelect ? tenureUnitSelect.value : 'MONTHS');
                if (sug) { promoTargetInput.value = sug; promoTargetInput.dataset.autoFilled = 'true'; }
            }
        });
    }
    if (tenureUnitSelect) {
        tenureUnitSelect.addEventListener('change', function () { 
            updatePayoutYear();
            const selScheme = schemeSelect ? schemeSelect.value : null; 
            if (!selScheme && codeInput && (!codeInput.value || codeInput.dataset.autoGenerated === 'true')) generateUniqueCode(); 
            if (!selScheme && promoTargetInput && (!promoTargetInput.value || promoTargetInput.dataset.autoFilled === 'true')) {
                const sug = getSuggestedPromoTarget(typeSelect ? typeSelect.value : 'FD', tenureInput ? tenureInput.value : 12, tenureUnitSelect.value);
                if (sug) { promoTargetInput.value = sug; promoTargetInput.dataset.autoFilled = 'true'; }
            }
        });
    }
    if (typeSelect) {
        typeSelect.addEventListener('change',  function () { 
            const selScheme = schemeSelect ? schemeSelect.value : null; 
            if (!selScheme && codeInput && (!codeInput.value || codeInput.dataset.autoGenerated === 'true')) generateUniqueCode(); 
            if (!selScheme && promoTargetInput && (!promoTargetInput.value || promoTargetInput.dataset.autoFilled === 'true')) {
                const sug = getSuggestedPromoTarget(typeSelect.value, tenureInput ? tenureInput.value : 12, tenureUnitSelect ? tenureUnitSelect.value : 'MONTHS');
                if (sug) { promoTargetInput.value = sug; promoTargetInput.dataset.autoFilled = 'true'; }
            }
        });
    }

    /* ═══════════ Promotion Target Auto-Suggest ══════════════════════ */
    function getSuggestedPromoTarget(schemeType, tenure, tenureUnit) {
        const t = parseInt(tenure, 10) || 0;
        const u = (tenureUnit || 'MONTHS').toUpperCase();
        let years = 0;
        if (u === 'YEARS' || u === 'YEAR' || u === 'Y') {
            years = t;
        } else if (u === 'MONTHS' || u === 'MONTH' || u === 'M') {
            years = Math.floor(t / 12);
        } else if (u === 'DAYS' || u === 'DAY' || u === 'D') {
            years = Math.floor(t / 365);
        }
        const st = (schemeType || 'FD').toUpperCase();
        if (st.includes('DD') || st.includes('DAILY') || st.includes('PIGMY')) {
            if (years === 1) return '20.00';
            if (years === 2) return '40.00';
            if (years === 3) return '50.00';
        } else if (st.includes('RD') || st.includes('RECUR')) {
            if (years === 1) return '20.00';
            if (years === 2) return '30.00';
            if (years === 3) return '40.00';
            if (years === 5 || years === 6) return '80.00';
            if (years === 7) return '90.00';
        } else {
            if (years === 1) return '20.00';
            if (years === 2) return '30.00';
            if (years === 3) return '40.00';
            if (years === 5 || years === 6) return '80.00';
        }
        return '';
    }

    const btnAutoSuggestPromo = document.getElementById('btnAutoSuggestPromoTarget');
    const promoTargetInput = document.getElementById('promotion_target_percentage');

    if (btnAutoSuggestPromo && promoTargetInput) {
        btnAutoSuggestPromo.addEventListener('click', function () {
            const st = typeSelect ? typeSelect.value : 'FD';
            const t = tenureInput ? tenureInput.value : 12;
            const u = tenureUnitSelect ? tenureUnitSelect.value : 'MONTHS';
            const suggested = getSuggestedPromoTarget(st, t, u);
            if (suggested) {
                promoTargetInput.value = suggested;
                promoTargetInput.dataset.autoFilled = 'true';
                promoTargetInput.classList.add('border-primary');
                setTimeout(function () { promoTargetInput.classList.remove('border-primary'); }, 600);
            }
        });
    }

    if (promoTargetInput) {
        promoTargetInput.addEventListener('input', function () {
            this.dataset.autoFilled = 'false';
        });
    }

    /* ═══════════ Action mode UI ══════════════════════════════════════ */
    function syncActionModeUI() {
        const val = schemeSelect ? schemeSelect.value : null;
        const isUpdate = (val && depositSchemesData[val] && !isClone);
        const icon = document.getElementById('btnSubmitScheme')?.querySelector('i');
        const actionModeInput = document.getElementById('action_mode');
        if (isUpdate) {
            if (actionModeInput) actionModeInput.value = 'update_existing';
            lblFormMode.textContent = 'Configure Scheme Commissions';
            lblFormMode.className   = 'badge bg-success-subtle text-success small';
            if (btnSubmitText) btnSubmitText.textContent = 'Save Commission Configuration';
            if (icon) icon.className = 'bi bi-check-lg me-1';
        } else {
            if (actionModeInput) actionModeInput.value = 'create_new';
            lblFormMode.textContent = 'New Scheme';
            lblFormMode.className   = 'badge bg-primary-subtle text-primary small';
            if (btnSubmitText) btnSubmitText.textContent = 'Create Scheme';
            if (icon) icon.className = 'bi bi-plus-lg me-1';
        }
    }

    /* ═══════════ Scheme selector ════════════════════════════════════ */
    function onSchemeSelectionChange() {
        const val         = schemeSelect.value;
        const alertTitle  = document.getElementById('schemeAlertTitle');
        const alertDesc   = document.getElementById('schemeAlertDesc');
        const alertBadges = document.getElementById('schemeAlertBadges');
        const badgeCode   = document.getElementById('badgeSchemeCode');
        const badgeType   = document.getElementById('badgeSchemeType');
        const badgeInterest = document.getElementById('badgeSchemeInterest');
        const badgeSeniorInterest = document.getElementById('badgeSchemeSeniorInterest');
        const badgeTenure = document.getElementById('badgeSchemeTenure');
        const badgeStatus = document.getElementById('badgeSchemeStatus');

        if (val && val !== 'NEW' && depositSchemesData[val]) {
            const scheme = depositSchemesData[val];
            if (actionModeContainer) actionModeContainer.style.display = 'block';
            const selBadge = document.getElementById('selectedSchemeNameBadge');
            if (selBadge) selBadge.textContent = scheme.name + ' (' + scheme.code + ')';
            const nameEl = document.getElementById('name');
            if (nameEl) nameEl.value = isClone ? scheme.name + ' (New Variant)' : (scheme.name || '');
            if (document.getElementById('scheme_type'))          document.getElementById('scheme_type').value          = scheme.scheme_type || 'FD';
            if (document.getElementById('collection_frequency'))  document.getElementById('collection_frequency').value  = scheme.collection_frequency || 'ONE_TIME';
            if (document.getElementById('interest_payout_frequency')) document.getElementById('interest_payout_frequency').value = scheme.interest_payout_frequency || 'AT_MATURITY';
            if (document.getElementById('interest_rate'))         document.getElementById('interest_rate').value         = scheme.interest_rate !== null ? parseFloat(scheme.interest_rate).toFixed(2) : '';
            if (document.getElementById('senior_citizen_extra_rate')) {
                document.getElementById('senior_citizen_extra_rate').value = (scheme.senior_citizen_extra_rate !== null && scheme.senior_citizen_extra_rate !== undefined)
                    ? parseFloat(scheme.senior_citizen_extra_rate).toFixed(2)
                    : '0.00';
            }
            if (document.getElementById('tenure'))                document.getElementById('tenure').value                = scheme.tenure || 12;
            if (document.getElementById('tenure_unit'))           document.getElementById('tenure_unit').value           = scheme.tenure_unit || 'MONTHS';
            if (document.getElementById('min_amount'))            document.getElementById('min_amount').value            = scheme.min_amount || '';
            if (document.getElementById('max_amount'))            document.getElementById('max_amount').value            = scheme.max_amount || '';
            if (document.getElementById('status'))                document.getElementById('status').value                = scheme.status || 'ACTIVE';
            if (document.getElementById('description'))           document.getElementById('description').value           = scheme.description || '';
            if (document.getElementById('promotion_target_percentage')) {
                document.getElementById('promotion_target_percentage').value = (scheme.promotion_target_percentage !== null && scheme.promotion_target_percentage !== undefined)
                    ? parseFloat(scheme.promotion_target_percentage).toFixed(2)
                    : '';
            }
            if (document.getElementById('notes'))                 document.getElementById('notes').value                 = 'Configured rank commissions for scheme.';

            if (!isClone) {
                if (document.getElementById('code')) {
                    document.getElementById('code').value = scheme.code || '';
                    document.getElementById('code').dataset.autoGenerated = 'false';
                    document.getElementById('code').readOnly = true;
                }
            } else {
                if (document.getElementById('code')) {
                    document.getElementById('code').readOnly = false;
                }
                generateUniqueCode();
            }

            const baseRateVal = parseFloat(scheme.interest_rate || 0);
            const seniorExtraVal = parseFloat(scheme.senior_citizen_extra_rate || 0);
            const seniorTotalVal = baseRateVal + seniorExtraVal;

            alertTitle.textContent = scheme.name;
            alertDesc.textContent  = 'Prefilled specs from template. Save as new or update this scheme.';
            alertBadges.classList.remove('d-none'); alertBadges.classList.add('d-flex');
            badgeCode.textContent     = scheme.code;
            badgeType.textContent     = scheme.scheme_type;
            badgeInterest.textContent = scheme.is_double ? 'Double (2x)' : (baseRateVal.toFixed(2) + '% Base');
            if (badgeSeniorInterest) {
                if (!scheme.is_double && seniorExtraVal > 0) {
                    badgeSeniorInterest.textContent = 'Senior: ' + seniorTotalVal.toFixed(2) + '% (+' + seniorExtraVal.toFixed(2) + '%)';
                    badgeSeniorInterest.classList.remove('d-none');
                } else {
                    badgeSeniorInterest.classList.add('d-none');
                }
            }
            badgeTenure.textContent   = scheme.tenure + ' ' + scheme.tenure_unit;
            badgeStatus.textContent   = scheme.status;
            updateSeniorRatePreview(Boolean(scheme.is_double));
            syncActionModeUI();

            const commissions = scheme.rank_commissions || [];
            const commMap = {};
            commissions.forEach(function (rc) {
                commMap[rc.rank_id] = rc;
            });

            eligibleToggles.forEach(function (toggle) {
                const rid = toggle.getAttribute('data-rank-id');
                const input = document.getElementById('pct-' + rid);
                if (commMap[rid]) {
                    toggle.checked = Boolean(commMap[rid].is_eligible);
                    if (input) {
                        input.value = commMap[rid].commission_percentage !== null ? parseFloat(commMap[rid].commission_percentage).toFixed(2) : '';
                    }
                } else {
                    toggle.checked = true;
                    if (input) input.value = '';
                }
            });

            updatePayoutYear();
        } else if (val === 'NEW') {
            window.location.href = "{{ route('admin.deposit-schemes.create') }}";
            return;
        } else {
            if (actionModeContainer) actionModeContainer.style.display = 'none';
            if (modeCreateNew) modeCreateNew.checked = true;
            alertTitle.textContent = 'Dynamic Scheme Creator';
            alertDesc.textContent  = 'Create multiple deposits of same type or prefill from an existing scheme.';
            alertBadges.classList.add('d-none'); alertBadges.classList.remove('d-flex');
            if (badgeSeniorInterest) badgeSeniorInterest.classList.add('d-none');
            lblFormMode.textContent = 'New Scheme';
            lblFormMode.className   = 'badge bg-primary-subtle text-primary small';
            if (btnSubmitText) btnSubmitText.textContent = 'Create Scheme';
            const icon = document.getElementById('btnSubmitScheme')?.querySelector('i');
            if (icon) icon.className = 'bi bi-plus-lg me-1';
            if (codeInput) {
                codeInput.readOnly = false;
                if (!codeInput.value) generateUniqueCode();
            }
            if (seniorExtraInput && !seniorExtraInput.value) seniorExtraInput.value = '0.00';
            updateSeniorRatePreview(false);
            updatePayoutYear();
        }
        calculateTotal();
    }

    if (schemeSelect) { schemeSelect.addEventListener('change', onSchemeSelectionChange); }

    eligibleToggles.forEach(function (t) { t.addEventListener('change', calculateTotal); });
    commissionInputs.forEach(function (i) { i.addEventListener('input',  calculateTotal); });

    if (btnSelectAll)   { btnSelectAll.addEventListener('click',   function () { eligibleToggles.forEach(function (t) { t.checked = true;  }); calculateTotal(); }); }
    if (btnDeselectAll) { btnDeselectAll.addEventListener('click', function () { eligibleToggles.forEach(function (t) { t.checked = false; }); calculateTotal(); }); }

    /* ═══════════ Bootstrap ══════════════════════════════════════════ */
    if (schemeSelect && schemeSelect.value) {
        onSchemeSelectionChange();
    } else {
        updatePayoutYear();
        calculateTotal();
        if (codeInput && !codeInput.value) generateUniqueCode();
    }
});
</script>
@endsection
