@extends('superadmin.layouts.app')

@section('page-title', isset($clonedScheme) ? 'Clone Deposit Scheme: ' . $clonedScheme->name : 'Create Deposit Scheme')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title fs-4 fw-bold mb-1">
            @if(isset($clonedScheme))
                <i class="bi bi-copy text-secondary me-2"></i>Clone Deposit Scheme
            @else
                Add Deposit Scheme
            @endif
        </h1>
        <div class="page-breadcrumb text-muted small">
            @if(isset($clonedScheme))
                <span>Deposits</span> <i class="bi bi-chevron-right mx-1" style="font-size:0.7rem;"></i>
                <a href="{{ route('admin.deposit-schemes.index') }}" class="text-decoration-none text-muted">Deposit Schemes</a>
                <i class="bi bi-chevron-right mx-1" style="font-size:0.7rem;"></i>
                <span>Clone <strong class="text-dark">{{ $clonedScheme->code }}</strong></span>
            @else
                Deposits / Deposit Schemes / New Scheme Configuration
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        @if(isset($clonedScheme))
            <a href="{{ route('admin.deposit-schemes.show', $clonedScheme) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-eye me-1"></i> View Original
            </a>
        @endif
        <a href="{{ route('admin.deposit-schemes.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Schemes
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-11 col-xl-10">
        @if(isset($clonedScheme))
            <div class="alert alert-info border mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 bg-info bg-opacity-25 text-info d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                        <i class="bi bi-copy fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Cloning from Scheme: {{ $clonedScheme->name }} ({{ $clonedScheme->code }})</div>
                        <div class="small text-muted">All specifications, rates, and commission rules have been duplicated below. A unique code and copy name are suggested. Review and save.</div>
                    </div>
                </div>
                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Clone Mode</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger mb-4 border">
                <div class="fw-semibold mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                    <span>Please correct the errors below before saving:</span>
                </div>
                <ul class="mb-0 ps-4 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $initialSchemeName = old('name', isset($clonedScheme) ? ($suggestedName ?? $clonedScheme->name . ' (Copy)') : '');
            $isInitialMrp = (bool) (
                stripos($initialSchemeName, 'MRP') !== false ||
                stripos($initialSchemeName, 'MONTHLY RETURN') !== false ||
                (isset($clonedScheme) && $clonedScheme->isMrpEnabled())
            );
        @endphp

        <form action="{{ route('admin.deposit-schemes.store') }}" method="POST" id="schemeCreateForm">
            @csrf
            @if(isset($clonedScheme))
                <input type="hidden" name="cloned_from_id" value="{{ $clonedScheme->id }}">
            @endif

            <!-- ─── 1. Scheme Identity & Classification ─── -->
            <div class="card border mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                    <span class="badge bg-dark p-2" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;">1</span>
                    <h6 class="card-title mb-0 fw-bold">Scheme Identity & Classification</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- Scheme Name Text Input -->
                        <div class="col-md-7">
                            <label class="form-label required fw-medium mb-1">Scheme Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="scheme_name" class="form-control @error('name') is-invalid @enderror @error('scheme_type') is-invalid @enderror"
                                   placeholder="e.g. Standard 12M Fixed Deposit" value="{{ old('name', isset($clonedScheme) ? ($suggestedName ?? $clonedScheme->name . ' (Copy)') : '') }}" required
                                   oninput="onSchemeNameInput(this)" list="scheme_name_suggestions" autocomplete="off">
                            <datalist id="scheme_name_suggestions">
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
                                <option value="Monthly Return Plan (MRP) 1 Year">
                                <option value="Monthly Return Plan (MRP) 3 Year">
                                <option value="Monthly Return Plan (MRP) 5 Year">
                                <option value="MRP (Monthly Return Plan)">
                                <option value="Kissan Samrudhi Plan (KSP) 1 Year">
                                <option value="Kissan Samrudhi Plan (KSP) 2 Year">
                                <option value="Kissan Samrudhi Plan (KSP) 3 Year">
                                <option value="Kissan Samrudhi Plan (KSP) 4 Year">
                                <option value="Kissan Samrudhi Plan (KSP) 5 Year">
                                <option value="Kissan Samrudhi Plan (KSP) 6 Year">
                                <option value="Kissan Samrudhi Plan (KSP) 7 Year">
                                <option value="Kissan Samrudhi Plan (KSP) 8 Year">
                                <option value="Kissan Samrudhi Plan (KSP) 9 Year">
                                <option value="Kissan Samrudhi Plan (KSP) 10 Year">
                                <option value="Kissan Samrudhi Plan (KSP)">
                            </datalist>
                            <input type="hidden" name="scheme_type" id="scheme_type_hidden" value="{{ old('scheme_type', isset($clonedScheme) ? $clonedScheme->scheme_type : 'FD') }}">
                            <div id="type_badge_preview" class="mt-1 small text-muted">
                                @if(isset($clonedScheme))
                                    <span class="badge bg-light text-dark border">{{ $clonedScheme->scheme_type }}</span>
                                @else
                                    Descriptive commercial title for customers and receipts.
                                @endif
                            </div>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('scheme_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Scheme Code with Auto-Generator & Verification -->
                        <div class="col-md-5">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label required fw-medium mb-0">Scheme Code <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center gap-2">
                                    <span id="code_mode_badge" class="badge {{ isset($clonedScheme) ? 'bg-info-subtle text-info border border-info' : 'bg-light text-secondary border' }} px-1" style="font-size:0.68rem;">{{ isset($clonedScheme) ? 'Cloned' : 'Auto' }}</span>
                                    <button type="button" class="btn btn-link p-0 small text-decoration-none fw-semibold" onclick="triggerAutoGenerateCode(true)" title="Regenerate code based on scheme name, type, and tenure">
                                        <i class="bi bi-magic me-1"></i>Auto Gen
                                    </button>
                                </div>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-upc"></i></span>
                                <input type="text" name="code" id="scheme_code" class="form-control font-monospace text-uppercase fw-semibold @error('code') is-invalid @enderror"
                                       maxlength="30"
                                       placeholder="e.g. FDKDSP5Y" value="{{ old('code', $suggestedCode ?? '') }}" required
                                       autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="checkCodeAvailability()" title="Verify code availability">
                                    <i class="bi bi-shield-check"></i>
                                </button>
                            </div>
                            <div id="code_feedback" class="mt-1 small text-muted" style="font-size:0.75rem;">
                                Format: <span class="font-monospace fw-semibold">DepositType + Abbr + Tenure</span> (max 30 chars).
                            </div>
                            @error('code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Live Code Preview Banner -->
                        <div class="col-12 mt-1">
                            <div class="p-3 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-3" id="live_preview_card">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-dark text-white p-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="bi bi-qr-code fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small fw-bold text-uppercase" style="letter-spacing:0.5px;font-size:0.7rem;">Live Scheme Code Preview</div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span id="preview_code_display" class="badge bg-dark font-monospace fs-6 px-3 py-1">
                                                {{ old('code', $suggestedCode ?? 'FDKDSP5Y') }}
                                            </span>
                                            <span id="preview_status_badge" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                                <i class="bi bi-check-circle-fill me-1"></i>Available
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end text-muted small d-none d-md-block" style="font-size:0.75rem;">
                                    <div>Examples: <code class="text-primary fw-bold">FDKDSP5Y</code> (FD, 5Y), <code class="text-primary fw-bold">RDSV12M</code> (RD, 12M), <code class="text-primary fw-bold">FDMI6M</code> (MIS, 6M)</div>
                                    <div class="text-secondary font-monospace mt-1">Uppercase: Y = Years, M = Months, D = Days</div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label fw-medium">Scheme Description & Key Features</label>
                            <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Brief details regarding investor suitability, payout structure, and benefits...">{{ old('description', isset($clonedScheme) ? $clonedScheme->description : '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Initial Publishing Status -->
                        <div class="col-12 mt-2">
                            <div class="p-3 bg-light border">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="status" id="scheme_status_switch" value="ACTIVE"
                                           {{ old('status', isset($clonedScheme) ? $clonedScheme->status : 'ACTIVE') === 'ACTIVE' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold text-dark" for="scheme_status_switch">
                                        Activate Scheme Immediately
                                    </label>
                                </div>
                                <small class="text-muted">When active, staff and agents can immediately open new deposit accounts under this scheme across authorized branches.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── 2. Financial Limits, Term & Interest Structure ─── -->
            <div class="card border mb-4" id="financial_limits_card">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark p-2" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;">2</span>
                        <h6 class="card-title mb-0 fw-bold">Financial Limits, Tenure & Return Structure</h6>
                    </div>
                    <span id="sec2_ksp_notice_badge" class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 small" style="display: none;">
                        <i class="bi bi-sliders me-1"></i>Base fields auto-synced &amp; locked for KSP
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- Min Deposit / Installment Amount -->
                        <div class="col-md-6" id="sec2_min_amount_col">
                            <label class="form-label required fw-medium" id="min_amount_label">
                                <span id="min_amount_label_text">{{ $isInitialMrp ? 'Contribution Amount (₹)' : 'Minimum Amount (₹)' }}</span>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">₹</span>
                                <input type="number" step="0.01" min="1" name="min_amount" id="min_amount"
                                       class="form-control @error('min_amount') is-invalid @enderror"
                                       placeholder="{{ $isInitialMrp ? 'e.g. 100000.00' : 'e.g. 1000.00' }}"
                                       value="{{ old('min_amount', isset($clonedScheme) ? $clonedScheme->min_amount : ($isInitialMrp ? '100000.00' : '1000.00')) }}" required>
                            </div>
                            <small class="text-muted" id="min_amount_hint">
                                {{ $isInitialMrp ? 'Contribution amount for Monthly Return Plan.' : 'Minimum opening or recurring installment amount.' }}
                            </small>
                            @error('min_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Max Deposit Amount -->
                        <div class="col-md-6" id="sec2_max_amount_col">
                            <label class="form-label fw-medium">Maximum Amount (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">₹</span>
                                <input type="number" step="0.01" min="0" name="max_amount" id="max_amount"
                                       class="form-control @error('max_amount') is-invalid @enderror"
                                       placeholder="Leave empty for unlimited" value="{{ old('max_amount', isset($clonedScheme) ? $clonedScheme->max_amount : '') }}">
                            </div>
                            <small class="text-muted">Optional upper cap per account (leave blank for unlimited).</small>
                            @error('max_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Tenure -->
                        <div class="col-md-4" id="sec2_tenure_col">
                            <label class="form-label required fw-medium">Tenure Duration <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="tenure" id="tenure"
                                   class="form-control @error('tenure') is-invalid @enderror"
                                   placeholder="e.g. 12" value="{{ old('tenure', isset($clonedScheme) ? $clonedScheme->tenure : 12) }}" required oninput="suggestCode()">
                            <small class="text-muted" id="tenure_hint">Duration of the deposit scheme.</small>
                            @error('tenure')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Tenure Unit -->
                        <div class="col-md-4" id="sec2_tenure_unit_col">
                            <label class="form-label required fw-medium">Tenure Unit <span class="text-danger">*</span></label>
                            <select name="tenure_unit" id="tenure_unit" class="form-select @error('tenure_unit') is-invalid @enderror" required onchange="suggestCode()">
                                @foreach($tenureUnits as $unitKey => $unitLabel)
                                    <option value="{{ $unitKey }}" {{ old('tenure_unit', isset($clonedScheme) ? $clonedScheme->tenure_unit : 'MONTHS') == $unitKey ? 'selected' : '' }}>
                                        {{ $unitLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Standard term unit.</small>
                            @error('tenure_unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Collection Frequency -->
                        <div class="col-md-4" id="sec2_collection_freq_col">
                            <label class="form-label required fw-medium">Collection Frequency <span class="text-danger">*</span></label>
                            <select name="collection_frequency" id="collection_frequency" class="form-select @error('collection_frequency') is-invalid @enderror" required>
                                @foreach($frequencies as $freqKey => $freqLabel)
                                    <option value="{{ $freqKey }}" {{ old('collection_frequency', isset($clonedScheme) ? $clonedScheme->collection_frequency : 'ONE_TIME') == $freqKey ? 'selected' : '' }}>
                                        {{ $freqLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="freq_hint">How installments are collected.</small>
                            @error('collection_frequency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Base Annual Interest Rate -->
                        <div class="col-md-4" id="sec2_interest_rate_col">
                            <label class="form-label required fw-medium">Standard Interest Rate (% p.a.) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="interest_rate" id="interest_rate"
                                       class="form-control @error('interest_rate') is-invalid @enderror"
                                       placeholder="e.g. 8.50" value="{{ old('interest_rate', isset($clonedScheme) ? $clonedScheme->interest_rate : '8.50') }}" required oninput="suggestCode()">
                                <span class="input-group-text bg-light">% p.a.</span>
                            </div>
                            <div class="form-check form-switch mt-2" id="sec2_double_toggle_wrap">
                                <input class="form-check-input" type="checkbox" name="is_double" value="1" id="is_double_scheme"
                                       {{ old('is_double', isset($clonedScheme) ? $clonedScheme->is_double : false) ? 'checked' : '' }} onchange="onDoubleSchemeToggle(this)">
                                <label class="form-check-label fw-bold text-dark small" for="is_double_scheme">
                                    <i class="bi bi-stars text-warning me-1"></i> Double Feature (Maturity Doubling)
                                </label>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">No interest rate. Deposit amount doubles at maturity.</small>
                            </div>
                            @error('interest_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('is_double')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Senior Citizen Extra Rate -->
                        <div class="col-md-4" id="sec2_senior_rate_col">
                            <label class="form-label fw-medium">Senior Citizen Extra Rate (% p.a.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person-plus text-primary"></i></span>
                                <input type="number" step="0.01" min="0" max="10" name="senior_citizen_extra_rate" id="senior_citizen_extra_rate"
                                       class="form-control @error('senior_citizen_extra_rate') is-invalid @enderror"
                                       placeholder="e.g. 0.50" value="{{ old('senior_citizen_extra_rate', isset($clonedScheme) ? $clonedScheme->senior_citizen_extra_rate : '0.00') }}">
                                <span class="input-group-text bg-light">+% p.a.</span>
                            </div>
                            <small class="text-muted">Additional rate for senior citizens (optional).</small>
                            @error('senior_citizen_extra_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Interest Payout / Compounding Frequency -->
                        <div class="col-md-4" id="sec2_payout_freq_col">
                            <label class="form-label required fw-medium">Interest Payout Frequency <span class="text-danger">*</span></label>
                            <select name="interest_payout_frequency" id="interest_payout_frequency" class="form-select @error('interest_payout_frequency') is-invalid @enderror" required>
                                @foreach($payoutFrequencies as $pfKey => $pfLabel)
                                    <option value="{{ $pfKey }}" {{ old('interest_payout_frequency', isset($clonedScheme) ? $clonedScheme->interest_payout_frequency : 'AT_MATURITY') == $pfKey ? 'selected' : '' }}>
                                        {{ $pfLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Cumulative at maturity or regular payout.</small>
                            @error('interest_payout_frequency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Promotion Target Credit % -->
                        <div class="col-md-4" id="sec2_promo_target_col">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="promotion_target_percentage" class="form-label fw-medium mb-0">
                                    <i class="bi bi-trophy text-warning me-1"></i>Promotion Target Credit %
                                </label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none fw-semibold" id="btnAutoSuggestPromoTarget" style="font-size:0.75rem;" title="Auto-suggest based on tenure and scheme type">
                                    <i class="bi bi-magic me-1"></i>Auto Suggest
                                </button>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" id="promotion_target_percentage" name="promotion_target_percentage"
                                       class="form-control text-end fw-semibold @error('promotion_target_percentage') is-invalid @enderror"
                                       value="{{ old('promotion_target_percentage', isset($clonedScheme) && $clonedScheme->promotion_target_percentage !== null ? number_format((float) $clonedScheme->promotion_target_percentage, 2, '.', '') : '') }}"
                                       placeholder="e.g. 20.00">
                                <span class="input-group-text bg-light text-muted">% credited</span>
                            </div>
                            <small class="text-muted d-block" style="font-size:0.75rem;">
                                Weighted % of deposit volume counted toward agent promotion targets (e.g. 1Y: 20%, 3Y: 40%, 5Y: 80%).
                            </small>
                            @error('promotion_target_percentage')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        @if(isset($clonedScheme) && $clonedScheme->interestRates && $clonedScheme->interestRates->count() > 1)
                        <div class="col-12 mt-2">
                            <div class="p-3 bg-light rounded border">
                                <div class="fw-semibold small text-dark mb-1">
                                    <i class="bi bi-clock-history me-1 text-primary"></i> Multi-tier Interest Rates ({{ $clonedScheme->interestRates->count() }} tiers)
                                </div>
                                <div class="small text-muted mb-2">
                                    The original scheme has {{ $clonedScheme->interestRates->count() }} configured interest rate tiers that will be automatically cloned:
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($clonedScheme->interestRates as $rate)
                                        <span class="badge bg-white text-dark border">
                                            {{ $rate->tenure }} {{ strtolower($rate->tenure_unit) }}: {{ number_format($rate->interest_rate, 2) }}% p.a.
                                            @if($rate->senior_citizen_extra_rate > 0)
                                                (+{{ number_format($rate->senior_citizen_extra_rate, 2) }}% Senior)
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($clonedScheme) && $clonedScheme->rankCommissions && $clonedScheme->rankCommissions->count() > 0)
                        <div class="col-12 mt-2">
                            <div class="p-3 bg-light rounded border">
                                <div class="fw-semibold small text-dark mb-1">
                                    <i class="bi bi-percent me-1 text-info"></i> Rank Commissions Matrix ({{ $clonedScheme->rankCommissions->count() }} rules configured)
                                </div>
                                <div class="small text-muted">
                                    All configured rank commission rules (Total: {{ number_format((float) $clonedScheme->total_commission_percentage, 2) }}%) will be cloned to this new scheme upon saving.
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @include('admin.deposit-schemes.partials.ksp-section')

            <div class="d-flex justify-content-between align-items-center mb-5 pb-3">
                <a href="{{ route('admin.deposit-schemes.index') }}" class="btn btn-outline-dark">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </a>
                <div class="d-flex gap-2">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Form
                    </button>
                    <button type="submit" class="btn btn-dark px-4">
                        <i class="bi bi-check2-circle me-1"></i> {{ isset($clonedScheme) ? 'Save Cloned Scheme' : 'Save & Launch Scheme' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let checkCodeDebounceTimer = null;

function detectSchemeTypeFromName(name) {
    if (!name) return 'FD';
    const upper = name.toUpperCase().trim();
    if (upper.includes('DOUBLE') || upper.includes('DOUBL') || /\bDDS\b/.test(upper)) return 'DOUBLE';
    if (upper.includes('RECUR') || /\bRD\b/.test(upper)) return 'RD';
    if (upper.includes('DAIL') || upper.includes('PIGMY') || /\bDD\b/.test(upper)) return 'DD';
    if (upper.includes('MRP') || upper.includes('MONTHLY RETURN') || upper.includes('MONTHLY INCOME') || /\bMIS\b/.test(upper)) return 'MIS';
    return 'FD';
}

/**
 * Extract uppercase scheme abbreviation matching backend DepositScheme::extractAbbreviation
 */
function extractAbbreviation(name, depositType) {
    if (!name) return '';
    let clean = name.trim();

    // 1. Remove parenthesized tenure mentions
    clean = clean.replace(/\(\s*\d+\s*(?:years?|yrs?|months?|mos?|days?|y|m|d)\s*\)/gi, '');

    // 2. Remove standalone tenure mentions
    clean = clean.replace(/\b\d+\s*(?:years?|yrs?|months?|mos?|days?|y|m|d)\b/gi, '');

    // 3. Remove leading deposit type if matching
    const dt = (depositType || 'FD').toUpperCase().trim();
    if (dt === 'FD') {
        clean = clean.replace(/^(fixed\s*deposit|fd)\b\s*/i, '');
    } else if (dt === 'RD') {
        clean = clean.replace(/^(recurring\s*deposit|rd)\b\s*/i, '');
    } else if (dt === 'DD') {
        clean = clean.replace(/^(daily\s*deposit|dd|pigmy)\b\s*/i, '');
    } else if (dt === 'MIS') {
        clean = clean.replace(/^(monthly\s*return\s*plan|monthly\s*income\s*scheme|mrp|mis)\b\s*/i, '');
    } else {
        clean = clean.replace(/^(fixed\s*deposit|recurring\s*deposit|daily\s*deposit|monthly\s*return\s*plan|monthly\s*income\s*scheme)\b\s*/i, '');
    }

    clean = clean.replace(/[^a-zA-Z0-9\s]/g, ' ').trim();
    if (!clean) return '';

    let words = clean.split(/\s+/).filter(Boolean);
    if (!words.length) return '';

    if (words.length > 2) {
        const stopWords = ['AND', 'OF', 'FOR', 'THE', 'IN', 'ON', 'AT', 'TO', 'A', 'AN', 'WITH'];
        const filtered = words.filter(w => !stopWords.includes(w.toUpperCase()));
        if (filtered.length > 0) words = filtered;
    }

    if (words.length >= 2) {
        return words.map(w => w.charAt(0).toUpperCase()).join('');
    }

    const single = words[0];
    if (single.length <= 2) {
        return single.toUpperCase();
    }

    if (single === single.toUpperCase() && single.length <= 4) {
        return single;
    }

    const first = single.charAt(0).toUpperCase();
    const rest = single.substring(1);
    const consonantMatch = rest.match(/[bcdfghjklmnpqrstvwxyz]/i);
    if (consonantMatch) {
        return first + consonantMatch[0].toUpperCase();
    }

    return single.substring(0, 2).toUpperCase();
}

/**
 * Generate standardized scheme code: DepositType + SchemeAbbr + Tenure
 */
function computeGeneratedCode() {
    const hiddenType = document.getElementById('scheme_type_hidden');
    const nameInput = document.getElementById('scheme_name');
    const tenureInput = document.getElementById('tenure');
    const unitSelect = document.getElementById('tenure_unit');

    const type = ((hiddenType ? hiddenType.value : 'FD') || 'FD').toUpperCase().trim();
    const name = nameInput ? nameInput.value : '';
    if ((/\bKSP\b/i.test(name) || /kissan\s+samrudhi\s+plan/i.test(name)) && !/dhan/i.test(name)) {
        const kspYrMatch = name.match(/(\d+)\s*(?:years?|yrs?|y)\b/i);
        return kspYrMatch ? ('KSP' + kspYrMatch[1] + 'Y') : 'KSP';
    }
    const tenure = Math.max(1, parseInt(tenureInput ? tenureInput.value : 1) || 1);
    const unit = ((unitSelect ? unitSelect.value : 'YEARS') || 'YEARS').toUpperCase();

    let suf = 'M';
    if (unit === 'YEARS' || unit === 'YEAR' || unit === 'Y') suf = 'Y';
    else if (unit === 'DAYS' || unit === 'DAY' || unit === 'D') suf = 'D';

    const abbr = extractAbbreviation(name, type);
    const raw = (type + abbr + tenure + suf).toUpperCase();
    return raw.substring(0, 30);
}

/**
 * Update the live preview badge and optionally auto-fill input
 */
function updateLiveCodePreview(forceOverwrite = false) {
    const codeInput = document.getElementById('scheme_code');
    const previewDisplay = document.getElementById('preview_code_display');
    const previewBadge = document.getElementById('preview_status_badge');
    const modeBadge = document.getElementById('code_mode_badge');

    const generated = computeGeneratedCode();

    if (previewDisplay) {
        previewDisplay.textContent = generated || '---';
    }

    const isAuto = !codeInput || !codeInput.value || codeInput.dataset.autoGenerated === 'true' || forceOverwrite;

    if (codeInput && isAuto) {
        codeInput.value = generated;
        codeInput.dataset.autoGenerated = 'true';
        if (modeBadge) {
            modeBadge.textContent = 'Auto';
            modeBadge.className = 'badge bg-light text-secondary border px-1';
        }
    } else if (modeBadge) {
        modeBadge.textContent = 'Custom';
        modeBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning px-1';
    }

    if (typeof window.syncKspVisibilityFromCode === 'function') {
        window.syncKspVisibilityFromCode();
    }

    // Debounce server availability check
    if (checkCodeDebounceTimer) clearTimeout(checkCodeDebounceTimer);
    checkCodeDebounceTimer = setTimeout(function () {
        checkCodeAvailability();
    }, 350);
}

function suggestCode() {
    updateLiveCodePreview(false);
}

function triggerAutoGenerateCode(force = true) {
    updateLiveCodePreview(force);
    const codeInput = document.getElementById('scheme_code');
    if (codeInput) {
        codeInput.classList.add('border-primary');
        setTimeout(() => codeInput.classList.remove('border-primary'), 600);
    }
}

function checkCodeAvailability() {
    const codeInput = document.getElementById('scheme_code');
    const previewBadge = document.getElementById('preview_status_badge');
    const codeFeedback = document.getElementById('code_feedback');
    const hiddenType = document.getElementById('scheme_type_hidden');
    const nameInput = document.getElementById('scheme_name');
    const tenureInput = document.getElementById('tenure');
    const unitSelect = document.getElementById('tenure_unit');

    const code = codeInput ? codeInput.value.trim().toUpperCase() : '';
    if (!code) {
        if (previewBadge) {
            previewBadge.className = 'badge bg-secondary-subtle text-secondary border px-2 py-1 small';
            previewBadge.innerHTML = '<i class="bi bi-dash-circle me-1"></i>Enter Scheme Details';
        }
        return;
    }

    if (previewBadge) {
        previewBadge.className = 'badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 small';
        previewBadge.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Checking...';
    }

    const params = new URLSearchParams({
        code: code,
        name: nameInput ? nameInput.value : '',
        scheme_type: hiddenType ? hiddenType.value : 'FD',
        tenure: tenureInput ? tenureInput.value : 1,
        tenure_unit: unitSelect ? unitSelect.value : 'MONTHS'
    });

    fetch(`{{ route('admin.deposit-schemes.preview-code') }}?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.is_unique) {
            if (previewBadge) {
                previewBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small';
                previewBadge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Unique & Available';
            }
            if (codeFeedback) {
                codeFeedback.innerHTML = `<span class="text-success fw-medium"><i class="bi bi-check-circle me-1"></i>Code <strong>${data.code}</strong> is unique and ready to save.</span>`;
            }
        } else {
            if (previewBadge) {
                previewBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 small';
                previewBadge.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Code Taken';
            }
            if (codeFeedback) {
                codeFeedback.innerHTML = `<span class="text-danger fw-medium"><i class="bi bi-exclamation-circle me-1"></i>Code <strong>${data.code}</strong> is taken! Suggested: <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 font-monospace" onclick="applySuggestedCode('${data.generated_code}')">${data.generated_code}</button></span>`;
            }
        }
    })
    .catch(() => {
        if (previewBadge) {
            previewBadge.className = 'badge bg-light text-dark border px-2 py-1 small';
            previewBadge.innerHTML = '<i class="bi bi-info-circle me-1"></i>Format: ' + code;
        }
    });
}

function applySuggestedCode(suggested) {
    const codeInput = document.getElementById('scheme_code');
    if (codeInput && suggested) {
        codeInput.value = suggested.toUpperCase();
        codeInput.dataset.autoGenerated = 'true';
        updateLiveCodePreview(false);
    }
}

function onDoubleSchemeToggle(checkbox) {
    const rateInput = document.getElementById('interest_rate');
    const seniorInput = document.getElementById('senior_citizen_extra_rate');
    if (checkbox.checked) {
        if (rateInput) {
            if (rateInput.value !== '0' && rateInput.value !== '0.00' && rateInput.value !== '') {
                rateInput.dataset.prevVal = rateInput.value;
            }
            rateInput.value = '0.00';
            rateInput.readOnly = true;
            rateInput.classList.add('bg-light');
        }
        if (seniorInput) {
            seniorInput.value = '0.00';
            seniorInput.readOnly = true;
            seniorInput.classList.add('bg-light');
        }
    } else {
        if (rateInput) {
            rateInput.readOnly = false;
            rateInput.classList.remove('bg-light');
            rateInput.value = rateInput.dataset.prevVal || '8.50';
        }
        if (seniorInput) {
            seniorInput.readOnly = false;
            seniorInput.classList.remove('bg-light');
        }
    }
    suggestCode();
}

function onSchemeNameInput(input) {
    const name = input ? input.value.trim() : (document.getElementById('scheme_name')?.value.trim() || '');
    const upper = name.toUpperCase();
    const type = detectSchemeTypeFromName(name);
    const hiddenType = document.getElementById('scheme_type_hidden');
    if (hiddenType) {
        hiddenType.value = type;
    }

    const badgeDiv = document.getElementById('type_badge_preview');
    const freqSelect = document.getElementById('collection_frequency');
    const payoutSelect = document.getElementById('interest_payout_frequency');
    const freqHint = document.getElementById('freq_hint');

    const isMrp = upper.includes('MRP') || upper.includes('MONTHLY RETURN');

    // Dynamic Minimum Amount vs Contribution Amount text & hints for MRP
    const minAmountLabelText = document.getElementById('min_amount_label_text');
    const minAmountHint = document.getElementById('min_amount_hint');
    const minAmountInput = document.getElementById('min_amount');

    if (isMrp) {
        if (minAmountLabelText) minAmountLabelText.textContent = 'Contribution Amount (₹)';
        if (minAmountHint) minAmountHint.textContent = 'Contribution amount for Monthly Return Plan.';
        if (minAmountInput) {
            minAmountInput.placeholder = 'e.g. 100000.00';
            if (!minAmountInput.value || minAmountInput.value === '1000.00' || minAmountInput.value === '1000') {
                minAmountInput.value = '100000.00';
            }
        }
    } else {
        if (minAmountLabelText) minAmountLabelText.textContent = 'Minimum Amount (₹)';
        if (minAmountHint) minAmountHint.textContent = 'Minimum opening or recurring installment amount.';
        if (minAmountInput) {
            minAmountInput.placeholder = 'e.g. 1000.00';
            if (minAmountInput.value === '100000.00' || minAmountInput.value === '100000') {
                minAmountInput.value = '1000.00';
            }
        }
    }

    if (!name) {
        if (badgeDiv) badgeDiv.innerHTML = '<span class="text-muted small">Descriptive commercial title for customers and receipts.</span>';
    } else if (type === 'DOUBLE') {
        if (freqSelect) freqSelect.value = 'ONE_TIME';
        if (freqHint) freqHint.textContent = 'One-time initial deposit (Doubling Scheme).';
        if (badgeDiv) badgeDiv.innerHTML = '<span class="badge bg-warning text-dark"><i class="bi bi-stars me-1 text-warning"></i>Double Scheme</span>';
        const dToggle = document.getElementById('is_double_scheme');
        if (dToggle && !dToggle.checked) {
            dToggle.checked = true;
            onDoubleSchemeToggle(dToggle);
        }
    } else if (type === 'RD') {
        if (freqSelect) freqSelect.value = 'MONTHLY';
        if (freqHint) freqHint.textContent = 'Monthly recurring installment collection.';
        if (badgeDiv) badgeDiv.innerHTML = '<span class="badge bg-success">Recurring Deposit (RD)</span>';
    } else if (type === 'DD') {
        if (freqSelect) freqSelect.value = 'DAILY';
        if (freqHint) freqHint.textContent = 'Daily door-to-door collection.';
        if (badgeDiv) badgeDiv.innerHTML = '<span class="badge bg-warning text-dark">Daily Collection (DD)</span>';
    } else if (type === 'MIS') {
        if (freqSelect) freqSelect.value = 'ONE_TIME';
        if (payoutSelect) payoutSelect.value = 'MONTHLY';
        if (isMrp) {
            if (freqHint) freqHint.textContent = 'One-time lump-sum contribution with dynamic monthly returns.';
            if (badgeDiv) badgeDiv.innerHTML = '<span class="badge bg-dark text-white"><i class="bi bi-calendar-check me-1"></i>Monthly Return Plan (MRP)</span>';
        } else {
            if (freqHint) freqHint.textContent = 'One-time deposit with regular monthly returns.';
            if (badgeDiv) badgeDiv.innerHTML = '<span class="badge bg-info text-dark">Monthly Income Scheme (MIS)</span>';
        }
    } else {
        if (freqSelect) freqSelect.value = 'ONE_TIME';
        if (freqHint) freqHint.textContent = 'One-time initial deposit (Lump sum).';
        if (badgeDiv) badgeDiv.innerHTML = '<span class="badge bg-primary">Fixed Deposit (FD)</span>';
    }

    // Auto-detect tenure & unit from name: e.g. "FD 2 Year" -> tenure: 2, unit: YEARS
    const yearMatch = name.match(/(\d+)\s*(?:year|yr|y)\b/i);
    const monthMatch = name.match(/(\d+)\s*(?:month|mo|m)\b/i);
    const dayMatch = name.match(/(\d+)\s*(?:day|d)\b/i);

    const tenureInput = document.getElementById('tenure');
    const tenureUnitSelect = document.getElementById('tenure_unit');

    if (yearMatch && tenureInput && tenureUnitSelect) {
        tenureInput.value = yearMatch[1];
        tenureUnitSelect.value = 'YEARS';
    } else if (monthMatch && tenureInput && tenureUnitSelect) {
        tenureInput.value = monthMatch[1];
        tenureUnitSelect.value = 'MONTHS';
    } else if (dayMatch && tenureInput && tenureUnitSelect) {
        tenureInput.value = dayMatch[1];
        tenureUnitSelect.value = 'DAYS';
    }

    updateLiveCodePreview(false);
}

// User manually typing in scheme code
document.getElementById('scheme_code')?.addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
    this.dataset.autoGenerated = 'false';
    const modeBadge = document.getElementById('code_mode_badge');
    if (modeBadge) {
        modeBadge.textContent = 'Custom';
        modeBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning px-1';
    }
    const previewDisplay = document.getElementById('preview_code_display');
    if (previewDisplay) {
        previewDisplay.textContent = this.value || '---';
    }
    if (checkCodeDebounceTimer) clearTimeout(checkCodeDebounceTimer);
    checkCodeDebounceTimer = setTimeout(checkCodeAvailability, 350);
});

// Event listeners for tenure & unit changes
document.getElementById('tenure')?.addEventListener('input', () => updateLiveCodePreview(false));
document.getElementById('tenure_unit')?.addEventListener('change', () => updateLiveCodePreview(false));

function getSuggestedPromoTarget(schemeType, tenure, tenureUnit) {
    const t = parseInt(tenure, 10) || 0;
    const u = (tenureUnit || 'MONTHS').toUpperCase();
    const months = (u === 'YEARS' || u === 'YEAR') ? t * 12 : ((u === 'DAYS' || u === 'DAY') ? Math.round(t / 30) : t);
    const st = (schemeType || 'FD').toUpperCase();

    if (st === 'FD') {
        if (months <= 12) return '20.00';
        if (months <= 24) return '30.00';
        if (months <= 36) return '40.00';
        if (months <= 60) return '80.00';
        return '100.00';
    }
    if (st === 'RD') {
        if (months <= 12) return '20.00';
        if (months <= 24) return '30.00';
        if (months <= 36) return '40.00';
        if (months <= 60) return '80.00';
        return '90.00';
    }
    if (st === 'DD') {
        if (months <= 12) return '20.00';
        if (months <= 24) return '40.00';
        return '50.00';
    }
    return '100.00';
}

document.getElementById('btnAutoSuggestPromoTarget')?.addEventListener('click', function () {
    const promoInput = document.getElementById('promotion_target_percentage');
    const tenureVal = document.getElementById('tenure')?.value || 12;
    const unitVal = document.getElementById('tenure_unit')?.value || 'MONTHS';
    const typeVal = document.getElementById('scheme_type_hidden')?.value || 'FD';
    if (promoInput) {
        promoInput.value = getSuggestedPromoTarget(typeVal, tenureVal, unitVal);
        promoInput.classList.add('border-primary');
        setTimeout(() => promoInput.classList.remove('border-primary'), 600);
    }
});

// Form submit validation: force code uppercase and check format
document.getElementById('schemeCreateForm')?.addEventListener('submit', function (e) {
    const codeInput = document.getElementById('scheme_code');
    if (codeInput) {
        codeInput.value = codeInput.value.trim().toUpperCase();
        if (!codeInput.value) {
            updateLiveCodePreview(true);
        }
        if (!/^[A-Z0-9\-]+$/.test(codeInput.value)) {
            e.preventDefault();
            alert('Scheme code must only contain uppercase letters, numbers, and hyphens.');
            codeInput.focus();
            return false;
        }
    }
});
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('scheme_code');
    const isCloned = @json(isset($clonedScheme));
    if (codeInput) {
        if (!codeInput.value) {
            codeInput.dataset.autoGenerated = 'true';
        } else if (isCloned) {
            codeInput.dataset.autoGenerated = 'false';
            const modeBadge = document.getElementById('code_mode_badge');
            if (modeBadge) {
                modeBadge.textContent = 'Cloned';
                modeBadge.className = 'badge bg-info-subtle text-info-emphasis border border-info px-1';
            }
        }
    }
    const nameInput = document.getElementById('scheme_name');
    if (nameInput && nameInput.value) {
        onSchemeNameInput(nameInput);
    } else {
        updateLiveCodePreview(false);
    }
});
</script>
@endsection
