@php
    $schemeModel = $depositScheme ?? $clonedScheme ?? null;
    $resolvedCode = strtoupper(trim((string) old('code', isset($depositScheme) ? $depositScheme->code : ($suggestedCode ?? ''))));
    $resolvedName = trim((string) old('name', $schemeModel?->name ?? ''));
    $isKspActive = \App\Models\DepositScheme::isKspCode($resolvedCode, $resolvedName) || ($schemeModel && $schemeModel->isKsp());

    $defaultPeriodFromCode = \App\Models\DepositScheme::extractKspDepositPeriod($resolvedCode, $resolvedName, 10);
    $savedKsp = ($schemeModel && is_array($schemeModel->ksp_configuration))
        ? $schemeModel->getKspConfig()
        : \App\Models\DepositScheme::getKspConfigForPeriod($defaultPeriodFromCode);

    $kspAnnualDepositAmount = old('ksp_annual_deposit_amount', $savedKsp['annual_deposit_amount'] ?? 15000);
    $kspDepositPeriod = (int) old('ksp_deposit_period', $savedKsp['deposit_period'] ?? $defaultPeriodFromCode);
    $kspInsuranceCoverage = old('ksp_insurance_coverage', $savedKsp['insurance_coverage'] ?? 300000);
    $kspReturnStartYear = (int) old('ksp_return_start_year', $savedKsp['return_start_year'] ?? 11);
    $kspReturnEndYear = (int) old('ksp_return_end_year', $savedKsp['return_end_year'] ?? (10 + $kspDepositPeriod));
    $kspAnnualReturnAmount = old('ksp_annual_return_amount', $savedKsp['annual_return_amount'] ?? 45000);
    $kspReturnFrequency = old('ksp_return_frequency', $savedKsp['return_frequency'] ?? 'Yearly');
    $kspPrematureWithdrawal = old('ksp_premature_withdrawal', $savedKsp['premature_withdrawal'] ?? 'Not Allowed');

    $returnFrequencyOptions = [
        'Yearly' => 'Yearly',
        'Half-Yearly' => 'Half-Yearly',
        'Quarterly' => 'Quarterly',
        'Monthly' => 'Monthly',
        'At Maturity' => 'At Maturity',
    ];

    $prematureWithdrawalOptions = [
        'Not Allowed' => 'Not Allowed',
        'Allowed' => 'Allowed',
        'Allowed with Penalty' => 'Allowed with Penalty',
    ];
@endphp

<!-- ─── 3. KSP SPECIAL CONFIGURATION ─── -->
<div class="card border mb-4" id="ksp_configuration_card" data-ksp-active="{{ $isKspActive ? 'true' : 'false' }}" aria-hidden="{{ $isKspActive ? 'false' : 'true' }}" style="{{ $isKspActive ? '' : 'display: none;' }}">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-dark p-2" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;">3</span>
            <h6 class="card-title mb-0 fw-bold text-uppercase">KSP SPECIAL CONFIGURATION</h6>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-dark font-monospace px-2 py-1 small" id="ksp_active_plan_badge">
                KSP {{ $kspDepositPeriod }} {{ $kspDepositPeriod === 1 ? 'Year' : 'Years' }}
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                <i class="bi bi-shield-check me-1"></i>Kissan Samrudhi Plan (KSP)
            </span>
        </div>
    </div>

    <div class="card-body p-4" id="ksp_config_body">
        <!-- Year-wise KSP Plan Selector (KSP 1 Year .. KSP 10 Year) -->
        <div class="p-3 bg-light border mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="small fw-bold text-dark text-uppercase" style="letter-spacing:0.4px;font-size:0.72rem;">
                    <i class="bi bi-calendar2-check text-primary me-1"></i>Select KSP Year-Wise Plan Tier (1 Year to 10 Years)
                </div>
                <span class="text-muted small" style="font-size:0.74rem;">
                    Rule: <strong>N Year Deposit</strong> &rarr; Return paid from <strong>11th Year</strong> to <strong>(10 + N)th Year</strong>
                </span>
            </div>
            <div class="d-flex flex-wrap gap-1" id="ksp_year_pills_container">
                @for($yr = 1; $yr <= 10; $yr++)
                    @php
                        $isYrSelected = ($kspDepositPeriod === $yr);
                        $retLabel = $yr === 1 ? '11th Yr' : ($yr === 2 ? '11 & 12 Yr' : '11–' . (10 + $yr) . ' Yr');
                    @endphp
                    <span role="button" tabindex="0"
                          class="badge {{ $isYrSelected ? 'bg-dark text-white' : 'bg-white text-dark' }} border px-2 py-1 ksp-year-pill"
                          style="cursor:pointer;font-size:0.75rem;user-select:none;"
                          data-ksp-year="{{ $yr }}"
                          onclick="selectKspYearPlan({{ $yr }}, true)"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();selectKspYearPlan({{ $yr }}, true);}">
                        <strong>KSP {{ $yr }} {{ $yr === 1 ? 'Year' : 'Year' }}</strong>
                        <span class="opacity-75 ms-1">({{ $retLabel }})</span>
                    </span>
                @endfor
            </div>
        </div>

        <div id="ksp_year_range_warning" class="alert alert-danger py-2 px-3 small border mb-3" style="display:none;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <span id="ksp_year_range_warning_text"><strong>Invalid Return Period:</strong> Return End Year must be valid for the selected Deposit Period.</span>
        </div>

        <div class="row g-3">
            <!-- 1. Annual Deposit Amount -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="ksp_annual_deposit_amount" class="form-label required fw-medium mb-0">
                        Annual Deposit Amount <span class="text-danger">*</span>
                    </label>
                    <span class="badge bg-light text-dark border font-monospace num-tabular" id="ksp_annual_deposit_formatted" style="font-size:0.7rem;">
                        ₹{{ \App\Services\CurrencyFormatter::formatIndianNumber((float) $kspAnnualDepositAmount, 0) }}
                    </span>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light">₹</span>
                    <input type="number" step="0.01" min="15000" name="ksp_annual_deposit_amount" id="ksp_annual_deposit_amount"
                           class="form-control num-tabular @error('ksp_annual_deposit_amount') is-invalid @enderror"
                           placeholder="15000" value="{{ $kspAnnualDepositAmount }}"
                           {{ $isKspActive ? 'required' : 'disabled' }}
                           oninput="onKspCurrencyInput(this, 'ksp_annual_deposit_formatted')">
                </div>
                <small class="text-muted">Minimum ₹15,000 per year.</small>
                @error('ksp_annual_deposit_amount')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <!-- 2. Deposit Period -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="ksp_deposit_period" class="form-label required fw-medium mb-0">
                        Deposit Period <span class="text-danger">*</span>
                    </label>
                    <span class="badge bg-light text-dark border num-tabular" id="ksp_deposit_period_formatted" style="font-size:0.7rem;">
                        {{ (int) $kspDepositPeriod }} {{ (int) $kspDepositPeriod === 1 ? 'Year' : 'Years' }}
                    </span>
                </div>
                <div class="input-group">
                    <input type="number" step="1" min="1" max="100" name="ksp_deposit_period" id="ksp_deposit_period"
                           class="form-control num-tabular @error('ksp_deposit_period') is-invalid @enderror"
                           placeholder="10" value="{{ $kspDepositPeriod }}"
                           {{ $isKspActive ? 'required' : 'disabled' }}
                           oninput="onKspPeriodInput(this)">
                    <span class="input-group-text bg-light">Years</span>
                </div>
                <small class="text-muted" id="ksp_deposit_period_hint">1Y &rarr; 11th Yr | 2Y &rarr; 11 &amp; 12 Yr | 10Y &rarr; 11–20 Yr.</small>
                @error('ksp_deposit_period')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <!-- 3. Insurance Coverage -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="ksp_insurance_coverage" class="form-label required fw-medium mb-0">
                        Insurance Coverage <span class="text-danger">*</span>
                    </label>
                    <span class="badge bg-light text-dark border font-monospace num-tabular" id="ksp_insurance_coverage_formatted" style="font-size:0.7rem;">
                        ₹{{ \App\Services\CurrencyFormatter::formatIndianNumber((float) $kspInsuranceCoverage, 0) }}
                    </span>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light">₹</span>
                    <input type="number" step="0.01" min="0" name="ksp_insurance_coverage" id="ksp_insurance_coverage"
                           class="form-control num-tabular @error('ksp_insurance_coverage') is-invalid @enderror"
                           placeholder="300000" value="{{ $kspInsuranceCoverage }}"
                           {{ $isKspActive ? 'required' : 'disabled' }}
                           oninput="onKspCurrencyInput(this, 'ksp_insurance_coverage_formatted')">
                </div>
                <small class="text-muted">Default ₹3,00,000 coverage benefit.</small>
                @error('ksp_insurance_coverage')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <!-- 4. Return Start Year -->
            <div class="col-12 col-md-6 col-lg-3">
                <label for="ksp_return_start_year" class="form-label required fw-medium mb-1">
                    Return Start Year <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light">Year</span>
                    <input type="number" step="1" min="1" max="100" name="ksp_return_start_year" id="ksp_return_start_year"
                           class="form-control num-tabular @error('ksp_return_start_year') is-invalid @enderror"
                           placeholder="11" value="{{ $kspReturnStartYear }}"
                           {{ $isKspActive ? 'required' : 'disabled' }}
                           oninput="validateKspYearRange(); updateKspScheduleSummary();">
                </div>
                <small class="text-muted">Returns always begin in 11th Year.</small>
                @error('ksp_return_start_year')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <!-- 5. Return End Year -->
            <div class="col-12 col-md-6 col-lg-3">
                <label for="ksp_return_end_year" class="form-label required fw-medium mb-1">
                    Return End Year <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light">Year</span>
                    <input type="number" step="1" min="1" max="100" name="ksp_return_end_year" id="ksp_return_end_year"
                           class="form-control num-tabular @error('ksp_return_end_year') is-invalid @enderror"
                           placeholder="20" value="{{ $kspReturnEndYear }}"
                           {{ $isKspActive ? 'required' : 'disabled' }}
                           oninput="this.dataset.manualOverride='1'; validateKspYearRange(); updateKspScheduleSummary();">
                </div>
                <small class="text-muted" id="ksp_return_end_hint">Auto-calculated: 10 + Deposit Period.</small>
                @error('ksp_return_end_year')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <!-- 6. Annual Return Amount -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="ksp_annual_return_amount" class="form-label required fw-medium mb-0">
                        Annual Return Amount <span class="text-danger">*</span>
                    </label>
                    <span class="badge bg-light text-dark border font-monospace num-tabular" id="ksp_annual_return_formatted" style="font-size:0.7rem;">
                        ₹{{ \App\Services\CurrencyFormatter::formatIndianNumber((float) $kspAnnualReturnAmount, 0) }}
                    </span>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light">₹</span>
                    <input type="number" step="0.01" min="0" name="ksp_annual_return_amount" id="ksp_annual_return_amount"
                           class="form-control num-tabular @error('ksp_annual_return_amount') is-invalid @enderror"
                           placeholder="45000" value="{{ $kspAnnualReturnAmount }}"
                           {{ $isKspActive ? 'required' : 'disabled' }}
                           oninput="onKspCurrencyInput(this, 'ksp_annual_return_formatted')">
                </div>
                <small class="text-muted">Default ₹45,000 guaranteed annual return.</small>
                @error('ksp_annual_return_amount')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <!-- 7. Return Frequency -->
            <div class="col-12 col-md-6 col-lg-3">
                <label for="ksp_return_frequency" class="form-label required fw-medium mb-1">
                    Return Frequency <span class="text-danger">*</span>
                </label>
                <select name="ksp_return_frequency" id="ksp_return_frequency"
                        class="form-select @error('ksp_return_frequency') is-invalid @enderror"
                        {{ $isKspActive ? 'required' : 'disabled' }}
                        onchange="syncKspToBaseFields(); updateKspScheduleSummary();">
                    @foreach($returnFrequencyOptions as $freqVal => $freqLabel)
                        <option value="{{ $freqVal }}" {{ strcasecmp((string) $kspReturnFrequency, $freqVal) === 0 ? 'selected' : '' }}>
                            {{ $freqLabel }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Default Yearly return schedule.</small>
                @error('ksp_return_frequency')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <!-- 8. Premature Withdrawal -->
            <div class="col-12 col-md-6 col-lg-3">
                <label for="ksp_premature_withdrawal" class="form-label required fw-medium mb-1">
                    Premature Withdrawal <span class="text-danger">*</span>
                </label>
                <select name="ksp_premature_withdrawal" id="ksp_premature_withdrawal"
                        class="form-select @error('ksp_premature_withdrawal') is-invalid @enderror"
                        {{ $isKspActive ? 'required' : 'disabled' }}
                        onchange="updateKspScheduleSummary()">
                    @foreach($prematureWithdrawalOptions as $pwVal => $pwLabel)
                        <option value="{{ $pwVal }}" {{ strcasecmp(str_replace('_', ' ', (string) $kspPrematureWithdrawal), $pwVal) === 0 ? 'selected' : '' }}>
                            {{ $pwLabel }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Default Not Allowed under KSP rules.</small>
                @error('ksp_premature_withdrawal')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Live Year-Wise Return Payout Schedule Preview -->
        <div class="mt-3 p-3 bg-light border" id="ksp_live_schedule_box">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="fw-bold small text-dark" id="ksp_schedule_heading">
                    <i class="bi bi-calendar-range text-success me-1"></i>KSP {{ $kspDepositPeriod }} Year Return Payout Schedule
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle num-tabular small" id="ksp_schedule_total_badge">
                    Total Return: ₹{{ \App\Services\CurrencyFormatter::formatIndianNumber((float) $kspAnnualReturnAmount * max(1, $kspReturnEndYear - $kspReturnStartYear + 1), 0) }}
                </span>
            </div>
            <div class="small text-muted mb-2" id="ksp_schedule_description"></div>
            <div class="d-flex flex-wrap gap-1" id="ksp_schedule_year_chips"></div>
        </div>
    </div>
</div>

<script>
function formatIndianRupeesClient(value) {
    const num = parseFloat(value);
    if (isNaN(num) || num < 0) return '₹0';
    return '₹' + num.toLocaleString('en-IN', { maximumFractionDigits: 2 });
}

function getOrdinalYearLabel(yr) {
    const n = parseInt(yr, 10);
    if (isNaN(n)) return '';
    const mod100 = n % 100;
    if (mod100 >= 11 && mod100 <= 13) return n + 'th Year';
    switch (n % 10) {
        case 1: return n + 'st Year';
        case 2: return n + 'nd Year';
        case 3: return n + 'rd Year';
        default: return n + 'th Year';
    }
}

function isKspSchemeCodeOrName(code, name) {
    const c = (code || '').trim().toUpperCase();
    if (c && /^(?:FD|RD|MIS)?KSP(?:\d+[YMD]?|[-_][A-Z0-9]+)?$/.test(c)) {
        return true;
    }
    const n = (name || '').trim().toUpperCase();
    if (n && (/\bKSP\b/.test(n) || n.includes('KISSAN SAMRUDHI')) && !n.includes('DHAN')) {
        return true;
    }
    return false;
}

function selectKspYearPlan(years, updateSchemeHeader = false) {
    const yr = Math.max(1, parseInt(years, 10) || 1);
    const periodInput = document.getElementById('ksp_deposit_period');
    const startInput = document.getElementById('ksp_return_start_year');
    const endInput = document.getElementById('ksp_return_end_year');

    if (periodInput) periodInput.value = yr;
    if (startInput) startInput.value = 11;
    if (endInput) {
        endInput.dataset.manualOverride = '0';
        endInput.value = 10 + yr;
    }

    if (updateSchemeHeader) {
        const nameInput = document.getElementById('scheme_name');
        const codeInput = document.getElementById('scheme_code');
        const isEditForm = Boolean(document.getElementById('schemeEditForm'));
        if (nameInput && (!isEditForm || isKspSchemeCodeOrName('', nameInput.value))) {
            nameInput.value = `Kissan Samrudhi Plan (KSP) ${yr} Year`;
        }
        if (codeInput && (!isEditForm || codeInput.value.toUpperCase() !== 'KSP')) {
            if (!codeInput.value || codeInput.dataset.autoGenerated === 'true' || /^KSP\d*Y?$/i.test(codeInput.value)) {
                codeInput.value = `KSP${yr}Y`;
                const previewDisplay = document.getElementById('preview_code_display');
                if (previewDisplay) previewDisplay.textContent = codeInput.value;
            }
        }
    }

    onKspPeriodInput(periodInput, true);
}

function onKspCurrencyInput(input, badgeId) {
    const badge = document.getElementById(badgeId);
    if (badge && input) {
        badge.textContent = formatIndianRupeesClient(input.value);
    }
    if (input && input.id === 'ksp_annual_deposit_amount') {
        const val = parseFloat(input.value) || 0;
        if (val < 15000) {
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
        syncKspToBaseFields();
    }
    updateKspScheduleSummary();
}

function onKspPeriodInput(input, fromPreset = false) {
    const badge = document.getElementById('ksp_deposit_period_formatted');
    const planBadge = document.getElementById('ksp_active_plan_badge');
    const startInput = document.getElementById('ksp_return_start_year');
    const endInput = document.getElementById('ksp_return_end_year');

    const yrs = Math.max(1, parseInt(input ? input.value : 10, 10) || 1);
    if (badge) {
        badge.textContent = yrs + (yrs === 1 ? ' Year' : ' Years');
    }
    if (planBadge) {
        planBadge.textContent = `KSP ${yrs} ${yrs === 1 ? 'Year' : 'Years'}`;
    }

    // Auto-align Return Start Year (11) and Return End Year (10 + Deposit Period)
    const startYr = startInput ? (parseInt(startInput.value, 10) || 11) : 11;
    if (endInput && (fromPreset || endInput.dataset.manualOverride !== '1')) {
        endInput.value = (startYr - 1) + yrs;
    }

    // Highlight matching year pill
    const pills = document.querySelectorAll('#ksp_year_pills_container .ksp-year-pill');
    pills.forEach(pill => {
        const pillYr = parseInt(pill.getAttribute('data-ksp-year'), 10);
        if (pillYr === yrs) {
            pill.classList.remove('bg-white', 'text-dark');
            pill.classList.add('bg-dark', 'text-white');
        } else {
            pill.classList.remove('bg-dark', 'text-white');
            pill.classList.add('bg-white', 'text-dark');
        }
    });

    validateKspYearRange();
    syncKspToBaseFields();
    updateKspScheduleSummary();
}

function updateKspScheduleSummary() {
    const depositInput = document.getElementById('ksp_annual_deposit_amount');
    const periodInput = document.getElementById('ksp_deposit_period');
    const insInput = document.getElementById('ksp_insurance_coverage');
    const startInput = document.getElementById('ksp_return_start_year');
    const endInput = document.getElementById('ksp_return_end_year');
    const returnAmtInput = document.getElementById('ksp_annual_return_amount');
    const freqSelect = document.getElementById('ksp_return_frequency');

    const heading = document.getElementById('ksp_schedule_heading');
    const totalBadge = document.getElementById('ksp_schedule_total_badge');
    const desc = document.getElementById('ksp_schedule_description');
    const chipsContainer = document.getElementById('ksp_schedule_year_chips');
    const endHint = document.getElementById('ksp_return_end_hint');

    if (!periodInput || !startInput || !endInput) return;

    const annualDeposit = parseFloat(depositInput ? depositInput.value : 15000) || 15000;
    const period = Math.max(1, parseInt(periodInput.value, 10) || 1);
    const insurance = parseFloat(insInput ? insInput.value : 300000) || 300000;
    const startYr = parseInt(startInput.value, 10) || 11;
    const endYr = parseInt(endInput.value, 10) || (10 + period);
    const annualReturn = parseFloat(returnAmtInput ? returnAmtInput.value : 45000) || 45000;
    const freq = freqSelect ? freqSelect.value : 'Yearly';

    const payoutYearsCount = Math.max(0, endYr - startYr + 1);
    const totalDeposited = annualDeposit * period;
    const totalReturn = annualReturn * payoutYearsCount;

    let returnYearsPhrase = '';
    if (payoutYearsCount === 1) {
        returnYearsPhrase = `in the <strong>${getOrdinalYearLabel(startYr)}</strong>`;
    } else if (payoutYearsCount === 2) {
        returnYearsPhrase = `in the <strong>${getOrdinalYearLabel(startYr)} and ${getOrdinalYearLabel(endYr)}</strong>`;
    } else if (payoutYearsCount > 2) {
        returnYearsPhrase = `from <strong>${getOrdinalYearLabel(startYr)} to ${getOrdinalYearLabel(endYr)}</strong> (${payoutYearsCount} years)`;
    } else {
        returnYearsPhrase = 'Invalid year range';
    }

    if (endHint) {
        if (period === 1) {
            endHint.textContent = `1 Year Deposit: Return received in ${getOrdinalYearLabel(startYr)}.`;
        } else if (period === 2) {
            endHint.textContent = `2 Year Deposit: Return received in ${getOrdinalYearLabel(startYr)} & ${getOrdinalYearLabel(endYr)}.`;
        } else {
            endHint.textContent = `${period} Year Deposit: Return received in Years ${startYr}–${endYr}.`;
        }
    }

    if (heading) {
        heading.innerHTML = `<i class="bi bi-calendar-range text-success me-1"></i>KSP ${period} ${period === 1 ? 'Year' : 'Year'} Plan — Return Payout Schedule`;
    }
    if (totalBadge) {
        totalBadge.textContent = `Total Return: ${formatIndianRupeesClient(totalReturn)} (${payoutYearsCount} ${payoutYearsCount === 1 ? 'Payout' : 'Payouts'})`;
    }
    if (desc) {
        const depositYearsText = period === 1 ? 'Year 1' : `Years 1 to ${period}`;
        desc.innerHTML = `Deposit <strong>${formatIndianRupeesClient(annualDeposit)}/yr</strong> for <strong>${period} ${period === 1 ? 'Year' : 'Years'}</strong> (${depositYearsText} &bull; Total Deposit: <strong>${formatIndianRupeesClient(totalDeposited)}</strong>) &rarr; Get <strong>${formatIndianRupeesClient(annualReturn)}</strong> (${freq}) ${returnYearsPhrase} + <strong>${formatIndianRupeesClient(insurance)}</strong> Insurance Coverage.`;
    }

    if (chipsContainer) {
        chipsContainer.innerHTML = '';
        if (payoutYearsCount >= 1 && payoutYearsCount <= 30) {
            for (let y = startYr; y <= endYr; y++) {
                const chip = document.createElement('span');
                chip.className = 'badge bg-white text-dark border font-monospace num-tabular px-2 py-1';
                chip.style.fontSize = '0.73rem';
                chip.innerHTML = `<i class="bi bi-arrow-right-circle-fill text-success me-1"></i>${getOrdinalYearLabel(y)}: <strong>${formatIndianRupeesClient(annualReturn)}</strong>`;
                chipsContainer.appendChild(chip);
            }
        }
    }
}

function syncKspToBaseFields() {
    const card = document.getElementById('ksp_configuration_card');
    if (!card || card.dataset.kspActive !== 'true') return;

    const kspDepositInput = document.getElementById('ksp_annual_deposit_amount');
    const kspPeriodInput = document.getElementById('ksp_deposit_period');
    const kspFreqSelect = document.getElementById('ksp_return_frequency');

    const minAmountInput = document.getElementById('min_amount');
    const tenureInput = document.getElementById('tenure');
    const tenureUnitSelect = document.getElementById('tenure_unit');
    const payoutFreqSelect = document.getElementById('interest_payout_frequency');

    if (minAmountInput && kspDepositInput) {
        const depVal = parseFloat(kspDepositInput.value) || 15000;
        minAmountInput.value = depVal.toFixed(2);
    }

    if (tenureInput && kspPeriodInput) {
        const periodVal = Math.max(1, parseInt(kspPeriodInput.value, 10) || 10);
        tenureInput.value = periodVal;
    }

    if (tenureUnitSelect) {
        tenureUnitSelect.value = 'YEARS';
    }

    if (payoutFreqSelect && kspFreqSelect) {
        const freqUpper = (kspFreqSelect.value || 'Yearly').toUpperCase();
        if (freqUpper === 'YEARLY' || freqUpper === 'HALF-YEARLY') {
            payoutFreqSelect.value = 'ANNUALLY';
        } else if (freqUpper === 'QUARTERLY') {
            payoutFreqSelect.value = 'QUARTERLY';
        } else if (freqUpper === 'MONTHLY') {
            payoutFreqSelect.value = 'MONTHLY';
        } else {
            payoutFreqSelect.value = 'AT_MATURITY';
        }
    }
}

function validateKspYearRange() {
    const periodInput = document.getElementById('ksp_deposit_period');
    const startInput = document.getElementById('ksp_return_start_year');
    const endInput = document.getElementById('ksp_return_end_year');
    const warning = document.getElementById('ksp_year_range_warning');
    const warningText = document.getElementById('ksp_year_range_warning_text');
    if (!startInput || !endInput) return true;

    const period = Math.max(1, parseInt(periodInput ? periodInput.value : 10, 10) || 1);
    const startYear = parseInt(startInput.value, 10);
    const endYear = parseInt(endInput.value, 10);

    const isValid = !isNaN(startYear) && !isNaN(endYear) && (period === 1 ? endYear >= startYear : endYear > startYear);
    if (!isValid && startInput.value !== '' && endInput.value !== '') {
        endInput.classList.add('is-invalid');
        if (warningText) {
            warningText.innerHTML = period === 1
                ? '<strong>Invalid Return Period:</strong> Return End Year must be greater than or equal to Return Start Year.'
                : '<strong>Invalid Return Period:</strong> Return End Year must be greater than Return Start Year.';
        }
        if (warning) warning.style.display = 'block';
    } else {
        endInput.classList.remove('is-invalid');
        if (warning) warning.style.display = 'none';
    }
    return isValid;
}

window.toggleKspSection = function(enable) {
    const card = document.getElementById('ksp_configuration_card');
    if (!card) return;

    const wasActive = card.dataset.kspActive === 'true';

    card.style.display = enable ? '' : 'none';
    card.dataset.kspActive = enable ? 'true' : 'false';
    card.setAttribute('aria-hidden', enable ? 'false' : 'true');

    const fields = card.querySelectorAll('input, select');
    fields.forEach(el => {
        el.disabled = !enable;
        if (enable) {
            el.setAttribute('required', 'required');
        } else {
            el.removeAttribute('required');
            el.classList.remove('is-invalid');
        }
    });

    // Section 2 notice badge & field wrappers
    const noticeBadge = document.getElementById('sec2_ksp_notice_badge');
    const minCol = document.getElementById('sec2_min_amount_col');
    const maxCol = document.getElementById('sec2_max_amount_col');
    const tenureCol = document.getElementById('sec2_tenure_col');
    const unitCol = document.getElementById('sec2_tenure_unit_col');
    const collFreqCol = document.getElementById('sec2_collection_freq_col');
    const interestCol = document.getElementById('sec2_interest_rate_col');
    const seniorCol = document.getElementById('sec2_senior_rate_col');
    const payoutFreqCol = document.getElementById('sec2_payout_freq_col');

    const minAmountInput = document.getElementById('min_amount');
    const minAmountLabelText = document.getElementById('min_amount_label_text');
    const minAmountHint = document.getElementById('min_amount_hint');
    const maxAmountInput = document.getElementById('max_amount');
    const tenureInput = document.getElementById('tenure');
    const tenureHint = document.getElementById('tenure_hint');
    const tenureUnitSelect = document.getElementById('tenure_unit');
    const collFreqSelect = document.getElementById('collection_frequency');
    const freqHint = document.getElementById('freq_hint');
    const rateInput = document.getElementById('interest_rate');
    const doubleCheck = document.getElementById('is_double_scheme');
    const seniorInput = document.getElementById('senior_citizen_extra_rate');

    if (enable) {
        if (noticeBadge) noticeBadge.style.display = '';

        // 1. Hide unwanted non-KSP fields in Section 2
        [maxCol, interestCol, seniorCol, payoutFreqCol].forEach(col => {
            if (col) {
                col.style.display = 'none';
                col.setAttribute('data-ksp-hidden', 'true');
            }
        });

        if (maxAmountInput) {
            if (!wasActive) maxAmountInput.dataset.kspPrevMax = maxAmountInput.value || '';
            maxAmountInput.value = '';
        }
        if (doubleCheck && doubleCheck.checked) {
            doubleCheck.checked = false;
        }
        if (rateInput) {
            if (!wasActive && rateInput.value !== '0' && rateInput.value !== '0.00') {
                rateInput.dataset.kspPrevRate = rateInput.value;
            }
            rateInput.value = '0.00';
        }
        if (seniorInput) {
            if (!wasActive) seniorInput.dataset.kspPrevSenior = seniorInput.value || '0.00';
            seniorInput.value = '0.00';
        }

        // 2. Dim and lock base fields in Section 2 that are driven by KSP Configuration
        [minCol, tenureCol, unitCol, collFreqCol].forEach(col => {
            if (col) {
                col.style.opacity = '0.6';
                col.setAttribute('data-ksp-dimmed', 'true');
            }
        });

        if (minAmountInput) {
            if (!wasActive) minAmountInput.dataset.kspPrevMin = minAmountInput.value || '1000.00';
            minAmountInput.readOnly = true;
            minAmountInput.classList.add('bg-light');
        }
        if (minAmountLabelText) {
            minAmountLabelText.textContent = 'Annual Deposit Amount (₹)';
        }
        if (minAmountHint) {
            minAmountHint.textContent = 'Auto-synced with KSP Annual Deposit Amount below.';
        }

        if (tenureInput) {
            if (!wasActive) tenureInput.dataset.kspPrevTenure = tenureInput.value || '12';
            tenureInput.readOnly = true;
            tenureInput.classList.add('bg-light');
        }
        if (tenureHint) {
            tenureHint.textContent = 'Auto-synced with KSP Deposit Period below.';
        }

        if (tenureUnitSelect) {
            if (!wasActive) tenureUnitSelect.dataset.kspPrevUnit = tenureUnitSelect.value || 'MONTHS';
            tenureUnitSelect.value = 'YEARS';
            tenureUnitSelect.classList.add('bg-light');
            tenureUnitSelect.style.pointerEvents = 'none';
            tenureUnitSelect.setAttribute('tabindex', '-1');
        }

        if (collFreqSelect) {
            if (!wasActive) collFreqSelect.dataset.kspPrevFreq = collFreqSelect.value || 'ONE_TIME';
            collFreqSelect.value = 'ONE_TIME';
            collFreqSelect.classList.add('bg-light');
            collFreqSelect.style.pointerEvents = 'none';
            collFreqSelect.setAttribute('tabindex', '-1');
        }
        if (freqHint) {
            freqHint.textContent = 'Annual deposit schedule managed by KSP Configuration.';
        }

        syncKspToBaseFields();
        updateKspScheduleSummary();
    } else {
        const warning = document.getElementById('ksp_year_range_warning');
        if (warning) warning.style.display = 'none';
        if (noticeBadge) noticeBadge.style.display = 'none';

        // Restore hidden columns in Section 2
        [maxCol, interestCol, seniorCol, payoutFreqCol].forEach(col => {
            if (col) {
                col.style.display = '';
                col.removeAttribute('data-ksp-hidden');
            }
        });

        // Restore dimmed columns in Section 2
        [minCol, tenureCol, unitCol, collFreqCol].forEach(col => {
            if (col) {
                col.style.opacity = '';
                col.removeAttribute('data-ksp-dimmed');
            }
        });

        if (minAmountInput) {
            minAmountInput.readOnly = false;
            minAmountInput.classList.remove('bg-light');
            if (wasActive && minAmountInput.dataset.kspPrevMin) {
                minAmountInput.value = minAmountInput.dataset.kspPrevMin;
            }
        }
        if (tenureInput) {
            tenureInput.readOnly = false;
            tenureInput.classList.remove('bg-light');
            if (wasActive && tenureInput.dataset.kspPrevTenure) {
                tenureInput.value = tenureInput.dataset.kspPrevTenure;
            }
        }
        if (tenureHint) {
            tenureHint.textContent = 'Duration of the deposit scheme.';
        }
        if (tenureUnitSelect) {
            tenureUnitSelect.classList.remove('bg-light');
            tenureUnitSelect.style.pointerEvents = '';
            tenureUnitSelect.removeAttribute('tabindex');
            if (wasActive && tenureUnitSelect.dataset.kspPrevUnit) {
                tenureUnitSelect.value = tenureUnitSelect.dataset.kspPrevUnit;
            }
        }
        if (collFreqSelect) {
            collFreqSelect.classList.remove('bg-light');
            collFreqSelect.style.pointerEvents = '';
            collFreqSelect.removeAttribute('tabindex');
            if (wasActive && collFreqSelect.dataset.kspPrevFreq) {
                collFreqSelect.value = collFreqSelect.dataset.kspPrevFreq;
            }
        }
        if (rateInput && wasActive && (!doubleCheck || !doubleCheck.checked)) {
            rateInput.readOnly = false;
            rateInput.classList.remove('bg-light');
            if (rateInput.dataset.kspPrevRate) {
                rateInput.value = rateInput.dataset.kspPrevRate;
            } else if (rateInput.value === '0.00' || rateInput.value === '0') {
                rateInput.value = '8.50';
            }
        }
        if (seniorInput && wasActive && seniorInput.dataset.kspPrevSenior !== undefined) {
            seniorInput.value = seniorInput.dataset.kspPrevSenior;
        }
    }
};

window.syncKspVisibilityFromCode = function() {
    const codeInput = document.getElementById('scheme_code');
    const nameInput = document.getElementById('scheme_name');
    const code = codeInput ? codeInput.value.trim().toUpperCase() : '';
    const name = nameInput ? nameInput.value.trim() : '';

    const isKsp = isKspSchemeCodeOrName(code, name);
    window.toggleKspSection(isKsp);

    if (isKsp) {
        // If code or name specifies a year (e.g. KSP1Y, KSP2Y, or "KSP 2 Year"), auto-select that KSP year period
        const codeYrMatch = code.match(/KSP[-_]?(\d+)[YMD]?$/i);
        const nameYrMatch = name.match(/(\d+)\s*(?:years?|yrs?|y)\b/i);
        const detectedYr = codeYrMatch ? parseInt(codeYrMatch[1], 10) : (nameYrMatch ? parseInt(nameYrMatch[1], 10) : null);
        const periodInput = document.getElementById('ksp_deposit_period');
        if (detectedYr && detectedYr >= 1 && detectedYr <= 100 && periodInput && parseInt(periodInput.value, 10) !== detectedYr) {
            selectKspYearPlan(detectedYr, false);
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    window.syncKspVisibilityFromCode();
    updateKspScheduleSummary();

    const codeInput = document.getElementById('scheme_code');
    if (codeInput) {
        codeInput.addEventListener('input', window.syncKspVisibilityFromCode);
        codeInput.addEventListener('change', window.syncKspVisibilityFromCode);
    }

    const form = document.getElementById('schemeCreateForm') || document.getElementById('schemeEditForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const card = document.getElementById('ksp_configuration_card');
            if (card && card.dataset.kspActive === 'true') {
                const depositInput = document.getElementById('ksp_annual_deposit_amount');
                const depositVal = parseFloat(depositInput ? depositInput.value : 0) || 0;
                if (depositVal < 15000) {
                    e.preventDefault();
                    if (depositInput) {
                        depositInput.classList.add('is-invalid');
                        depositInput.focus();
                    }
                    alert('Annual Deposit Amount for KSP must be at least ₹15,000.');
                    return false;
                }
                if (!validateKspYearRange()) {
                    e.preventDefault();
                    const endInput = document.getElementById('ksp_return_end_year');
                    if (endInput) endInput.focus();
                    alert('Please enter a valid Return Start Year and Return End Year.');
                    return false;
                }
            }
        });
    }
});
</script>
