@php
    $schemeModel = $depositScheme ?? $clonedScheme ?? null;
    $mrpConfig = $schemeModel?->mrpConfiguration;
    $schemeName = old('name', $schemeModel?->name ?? '');
    $isMrpActive = (bool) (
        ($isInitialMrp ?? false) ||
        ($mrpConfig?->is_enabled) ||
        stripos($schemeName, 'MRP') !== false ||
        stripos($schemeName, 'MONTHLY RETURN') !== false
    );

    $contributionAmount = old('mrp_contribution_amount', $mrpConfig?->contribution_amount !== null ? number_format((float) $mrpConfig->contribution_amount, 2, '.', '') : '100000.00');
    $minTenure = old('mrp_min_tenure', $mrpConfig?->min_tenure ?? 1);
    $maxTenure = old('mrp_max_tenure', $mrpConfig?->max_tenure ?? 5);
    $tenureUnit = old('mrp_tenure_unit', $mrpConfig?->tenure_unit ?? 'YEARS');
    $calcMethod = 'STANDARD';

    $generalRate = old('mrp_general_rate', $mrpConfig?->general_rate !== null ? number_format((float) $mrpConfig->general_rate, 2, '.', '') : '10.00');
    $seniorRate = old('mrp_senior_rate', $mrpConfig?->senior_rate !== null ? number_format((float) $mrpConfig->senior_rate, 2, '.', '') : '10.50');

    // Resolve Initial Year Rates
    $initialRates = [];
    if (old('mrp_rates') !== null && is_array(old('mrp_rates'))) {
        foreach (old('mrp_rates') as $idx => $r) {
            $gRate = (float) ($r['general_rate'] ?? 0);
            $sRate = (float) ($r['senior_rate'] ?? 0);
            $initialRates[] = [
                'year' => (int) ($r['year'] ?? ($idx + 1)),
                'general_rate' => $gRate,
                'general_monthly_return' => (float) ($r['general_monthly_return'] ?? \App\Models\DepositSchemeMrpConfiguration::calculateMonthlyReturn((float) $contributionAmount, $gRate, $calcMethod)),
                'senior_rate' => $sRate,
                'senior_monthly_return' => (float) ($r['senior_monthly_return'] ?? \App\Models\DepositSchemeMrpConfiguration::calculateMonthlyReturn((float) $contributionAmount, $sRate, $calcMethod)),
            ];
        }
    } elseif ($schemeModel && $schemeModel->mrpRates && $schemeModel->mrpRates->count() > 0) {
        foreach ($schemeModel->mrpRates as $r) {
            $initialRates[] = [
                'year' => (int) $r->year,
                'general_rate' => (float) $r->general_rate,
                'general_monthly_return' => (float) $r->general_monthly_return,
                'senior_rate' => (float) $r->senior_rate,
                'senior_monthly_return' => (float) $r->senior_monthly_return,
            ];
        }
    } else {
        // Sensible default rows matching tenure range (Years 1 to 5)
        $limit = max(1, min((int) $maxTenure, 5));
        for ($y = 1; $y <= $limit; $y++) {
            $initialRates[] = [
                'year' => $y,
                'general_rate' => (float) $generalRate,
                'general_monthly_return' => \App\Models\DepositSchemeMrpConfiguration::calculateMonthlyReturn((float) $contributionAmount, (float) $generalRate, $calcMethod),
                'senior_rate' => (float) $seniorRate,
                'senior_monthly_return' => \App\Models\DepositSchemeMrpConfiguration::calculateMonthlyReturn((float) $contributionAmount, (float) $seniorRate, $calcMethod),
            ];
        }
    }
@endphp

<!-- ─── 3. MRP – Monthly Return Configuration ─── -->
<div class="card border mb-4" id="mrp_configuration_card" style="{{ $isMrpActive ? '' : 'display: none;' }}">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-dark p-2" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;">3</span>
            <h6 class="card-title mb-0 fw-bold">MRP – Monthly Return Configuration</h6>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="mrp_contribution_amount" class="small fw-semibold text-muted mb-0 text-nowrap">Monthly Contribution Amount (₹):</label>
                <div class="input-group input-group-sm" style="width: 170px;">
                    <span class="input-group-text bg-light text-muted fw-bold">₹</span>
                    <input type="number" step="0.01" min="1" name="mrp_contribution_amount" id="mrp_contribution_amount"
                           class="form-control text-end fw-bold"
                           placeholder="100000.00" value="{{ $contributionAmount }}" oninput="onMrpContributionChange(this)">
                </div>
            </div>
            <button type="button" class="btn btn-dark btn-sm py-1 px-3" onclick="addMrpYearRow()">
                <i class="bi bi-plus-lg me-1"></i>Add Year
            </button>
        </div>
    </div>

    <!-- Active MRP Configuration Body -->
    <div class="card-body p-4" id="mrp_config_body">
        <input type="hidden" name="mrp_enabled" id="mrp_enabled" value="{{ $isMrpActive ? '1' : '0' }}" {{ $isMrpActive ? '' : 'disabled' }}>
        <input type="hidden" name="mrp_min_tenure" id="mrp_min_tenure" value="{{ $minTenure }}">
        <input type="hidden" name="mrp_max_tenure" id="mrp_max_tenure" value="{{ $maxTenure }}">
        <input type="hidden" name="mrp_tenure_unit" id="mrp_tenure_unit" value="{{ $tenureUnit }}">
        <input type="hidden" name="mrp_general_rate" id="mrp_general_rate" value="{{ $generalRate }}">
        <input type="hidden" name="mrp_senior_rate" id="mrp_senior_rate" value="{{ $seniorRate }}">
        <input type="hidden" name="mrp_calculation_method" id="mrp_calculation_method" value="STANDARD">

        @error('mrp_contribution_amount')
            <div class="alert alert-danger py-2 px-3 small border mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
            </div>
        @enderror

        @error('mrp_rates')
            <div class="alert alert-danger py-2 px-3 small border mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
            </div>
        @enderror

        <!-- Subtle Header & Formula Notice -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <span class="fw-semibold small text-dark">
                <i class="bi bi-calendar2-range me-1 text-secondary"></i>Dynamic Year-wise Configuration Matrix
            </span>
            <span class="badge bg-light text-muted border fw-normal" style="font-size:0.75rem;">
                Monthly Return = (Contribution Amount x Annual Rate) ÷ 1200
            </span>
        </div>

        <div id="mrp_duplicate_warning" class="alert alert-danger py-2 px-3 small border mb-3" style="display:none;">
            <i class="bi bi-exclamation-octagon-fill me-1"></i>
            <strong>Duplicate Year Detected:</strong> Each year in the schedule must be unique.
        </div>

        <!-- Dynamic Table -->
        <div class="table-responsive border rounded bg-white">
            <table class="table table-sm table-hover align-middle mb-0" id="mrp_rates_table">
                <thead class="table-light border-bottom">
                    <tr class="text-secondary small fw-semibold text-uppercase" style="font-size:0.75rem; letter-spacing:0.5px;">
                        <th style="width: 12%;" class="text-center py-2">Year <span class="text-danger">*</span></th>
                        <th style="width: 22%;" class="py-2">General Rate (%) <span class="text-danger">*</span></th>
                        <th style="width: 22%;" class="py-2">General Monthly Return</th>
                        <th style="width: 22%;" class="py-2">Senior Citizen Rate (%) <span class="text-danger">*</span></th>
                        <th style="width: 18%;" class="py-2">Senior Monthly Return</th>
                        <th style="width: 4%;" class="text-center py-2"></th>
                    </tr>
                </thead>
                <tbody id="mrp_rates_tbody">
                    <!-- Populated dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let mrpRatesData = @json($initialRates);
let mrpRowIndexCounter = 0;

/**
 * Decimal-safe client calculation: (Contribution * Rate) / 1200
 */
function calculateMrpMonthlyReturn(contribution, rate) {
    const c = parseFloat(contribution) || 0;
    const r = parseFloat(rate) || 0;
    if (c <= 0 || r <= 0) return 0.00;

    const exact = (c * r) / 1200.0;
    return Math.round(exact * 100.0) / 100.0;
}

function getContributionAmount() {
    const input = document.getElementById('mrp_contribution_amount');
    return parseFloat(input ? input.value : 0) || 0;
}

function onMrpContributionChange(input) {
    recalculateAllMrpReturns();

    // Auto-align Section 2 min_amount if present
    const sec2MinAmount = document.getElementById('min_amount');
    const val = parseFloat(input.value) || 0;
    if (sec2MinAmount && val > 0) {
        sec2MinAmount.value = val.toFixed(2);
    }
}

function syncHiddenFields() {
    const tbody = document.getElementById('mrp_rates_tbody');
    if (!tbody) return;

    const yearInputs = tbody.querySelectorAll('.mrp-year-input');
    const years = [];
    yearInputs.forEach(inp => {
        const y = parseInt(inp.value, 10);
        if (!isNaN(y) && y > 0) years.push(y);
    });

    const minT = years.length > 0 ? Math.min(...years) : 1;
    const maxT = years.length > 0 ? Math.max(...years) : 1;

    const minInput = document.getElementById('mrp_min_tenure');
    const maxInput = document.getElementById('mrp_max_tenure');
    if (minInput) minInput.value = minT;
    if (maxInput) maxInput.value = maxT;

    // Sync Section 2 tenure
    const sec2Tenure = document.getElementById('tenure');
    if (sec2Tenure) sec2Tenure.value = maxT;

    const firstGRateInput = tbody.querySelector('tr:first-child .mrp-general-rate-input');
    const firstSRateInput = tbody.querySelector('tr:first-child .mrp-senior-rate-input');
    if (firstGRateInput) {
        const gVal = parseFloat(firstGRateInput.value) || 10.00;
        const gHidden = document.getElementById('mrp_general_rate');
        if (gHidden) gHidden.value = gVal.toFixed(2);
        const sec2Rate = document.getElementById('interest_rate');
        if (sec2Rate) sec2Rate.value = gVal.toFixed(2);
    }
    if (firstSRateInput) {
        const sVal = parseFloat(firstSRateInput.value) || 10.50;
        const sHidden = document.getElementById('mrp_senior_rate');
        if (sHidden) sHidden.value = sVal.toFixed(2);
        const gVal = parseFloat(firstGRateInput ? firstGRateInput.value : 10.00) || 10.00;
        const sec2SeniorExtra = document.getElementById('senior_citizen_extra_rate');
        if (sec2SeniorExtra) sec2SeniorExtra.value = Math.max(0, sVal - gVal).toFixed(2);
    }
}

function recalculateRowReturns(rowElement) {
    if (!rowElement) return;

    const contribution = getContributionAmount();

    const gRateInput = rowElement.querySelector('.mrp-general-rate-input');
    const sRateInput = rowElement.querySelector('.mrp-senior-rate-input');

    const gReturnDisplay = rowElement.querySelector('.mrp-general-return-display');
    const sReturnDisplay = rowElement.querySelector('.mrp-senior-return-display');

    const gRate = parseFloat(gRateInput ? gRateInput.value : 0) || 0;
    const sRate = parseFloat(sRateInput ? sRateInput.value : 0) || 0;

    const gReturn = calculateMrpMonthlyReturn(contribution, gRate);
    const sReturn = calculateMrpMonthlyReturn(contribution, sRate);

    if (gReturnDisplay) gReturnDisplay.value = gReturn.toFixed(2);
    if (sReturnDisplay) sReturnDisplay.value = sReturn.toFixed(2);
}

function onGeneralRateChange(input) {
    const row = input.closest('tr');
    if (!row) return;

    const gRate = parseFloat(input.value) || 0;
    const sRateInput = row.querySelector('.mrp-senior-rate-input');

    if (sRateInput && (!sRateInput.dataset.customized || sRateInput.dataset.customized === 'false')) {
        sRateInput.value = (gRate + 0.50).toFixed(2);
    }

    recalculateRowReturns(row);
    syncHiddenFields();
}

function recalculateAllMrpReturns() {
    const rows = document.querySelectorAll('#mrp_rates_tbody tr.mrp-rate-row');
    rows.forEach(row => recalculateRowReturns(row));
}

function validateDuplicateYears() {
    const yearInputs = document.querySelectorAll('#mrp_rates_tbody .mrp-year-input');
    const seen = new Set();
    let hasDuplicate = false;

    yearInputs.forEach(input => {
        const val = parseInt(input.value, 10);
        if (!isNaN(val) && val > 0) {
            if (seen.has(val)) {
                hasDuplicate = true;
                input.classList.add('is-invalid', 'border-danger');
            } else {
                seen.add(val);
                input.classList.remove('is-invalid', 'border-danger');
            }
        }
    });

    const warningAlert = document.getElementById('mrp_duplicate_warning');
    if (warningAlert) {
        warningAlert.style.display = hasDuplicate ? 'block' : 'none';
    }

    return !hasDuplicate;
}

function addMrpYearRow(yearVal = null, gRateVal = null, sRateVal = null) {
    const tbody = document.getElementById('mrp_rates_tbody');
    if (!tbody) return;

    if (yearVal === null) {
        const existingYears = [];
        tbody.querySelectorAll('.mrp-year-input').forEach(input => {
            const parsed = parseInt(input.value, 10);
            if (!isNaN(parsed)) existingYears.push(parsed);
        });
        yearVal = existingYears.length > 0 ? Math.max(...existingYears) + 1 : 1;
    }

    if (gRateVal === null) {
        const lastGRate = tbody.querySelector('tr:last-child .mrp-general-rate-input');
        gRateVal = lastGRate ? parseFloat(lastGRate.value) : 10.00;
    }
    if (sRateVal === null) {
        const lastSRate = tbody.querySelector('tr:last-child .mrp-senior-rate-input');
        sRateVal = lastSRate ? parseFloat(lastSRate.value) : (parseFloat(gRateVal) + 0.50);
    }

    const contribution = getContributionAmount();
    const gReturn = calculateMrpMonthlyReturn(contribution, gRateVal);
    const sReturn = calculateMrpMonthlyReturn(contribution, sRateVal);

    const index = mrpRowIndexCounter++;

    const tr = document.createElement('tr');
    tr.className = 'mrp-rate-row';
    tr.dataset.index = index;

    tr.innerHTML = `
        <td class="text-center py-2">
            <input type="number" min="1" step="1" name="mrp_rates[${index}][year]"
                   class="form-control form-control-sm text-center fw-bold mrp-year-input mx-auto"
                   style="max-width: 80px;"
                   value="${yearVal}" required oninput="onYearInputChange(this)">
        </td>
        <td class="py-2">
            <div class="input-group input-group-sm">
                <input type="number" step="0.01" min="0" max="100" name="mrp_rates[${index}][general_rate]"
                       class="form-control text-end fw-semibold mrp-general-rate-input"
                       value="${parseFloat(gRateVal).toFixed(2)}" required oninput="onGeneralRateChange(this)">
                <span class="input-group-text bg-light text-muted small">%</span>
            </div>
        </td>
        <td class="py-2">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-success fw-bold py-0">₹</span>
                <input type="text" name="mrp_rates[${index}][general_monthly_return]"
                       class="form-control text-end fw-bold bg-light text-success border-start-0 mrp-general-return-display"
                       value="${gReturn.toFixed(2)}" readonly tabindex="-1" title="General Monthly Return">
                <span class="input-group-text bg-light text-muted small py-0">/mo</span>
            </div>
        </td>
        <td class="py-2">
            <div class="input-group input-group-sm">
                <input type="number" step="0.01" min="0" max="100" name="mrp_rates[${index}][senior_rate]"
                       class="form-control text-end fw-semibold mrp-senior-rate-input"
                       value="${parseFloat(sRateVal).toFixed(2)}" required oninput="onSeniorRateManualInput(this); recalculateRowReturns(this.closest('tr'))">
                <span class="input-group-text bg-light text-muted small">%</span>
            </div>
        </td>
        <td class="py-2">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-primary fw-bold py-0">₹</span>
                <input type="text" name="mrp_rates[${index}][senior_monthly_return]"
                       class="form-control text-end fw-bold bg-light text-primary border-start-0 mrp-senior-return-display"
                       value="${sReturn.toFixed(2)}" readonly tabindex="-1" title="Senior Citizen Monthly Return">
                <span class="input-group-text bg-light text-muted small py-0">/mo</span>
            </div>
        </td>
        <td class="text-center py-2">
            <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" title="Remove this Year" onclick="removeMrpYearRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    validateDuplicateYears();
    syncHiddenFields();
}

function onSeniorRateManualInput(input) {
    input.dataset.customized = 'true';
    syncHiddenFields();
}

function removeMrpYearRow(button) {
    const row = button.closest('tr');
    if (!row) return;

    row.remove();
    validateDuplicateYears();
    syncHiddenFields();
}

function onYearInputChange(input) {
    validateDuplicateYears();
    syncHiddenFields();
}

/**
 * Toggle MRP section display and input disabled states
 */
window.toggleMrpSection = function(enable) {
    const card = document.getElementById('mrp_configuration_card');
    const enabledInput = document.getElementById('mrp_enabled');
    if (!card) return;

    card.style.display = enable ? 'block' : 'none';
    if (enabledInput) {
        enabledInput.value = enable ? '1' : '0';
        enabledInput.disabled = !enable;
    }

    const inputs = card.querySelectorAll('input:not(#mrp_enabled), select, textarea, button');
    inputs.forEach(el => {
        el.disabled = !enable;
    });

    if (enable) {
        const sec2MinAmount = document.getElementById('min_amount');
        const mrpContrib = document.getElementById('mrp_contribution_amount');
        if (sec2MinAmount && mrpContrib) {
            const val = parseFloat(sec2MinAmount.value) || 0;
            if (val > 0) {
                mrpContrib.value = val.toFixed(2);
            } else if (parseFloat(mrpContrib.value) > 0) {
                sec2MinAmount.value = parseFloat(mrpContrib.value).toFixed(2);
            }
            recalculateAllMrpReturns();
        }
    }
};

// Initialise matrix on DOM loaded
document.addEventListener('DOMContentLoaded', function () {
    const isInitiallyActive = {{ $isMrpActive ? 'true' : 'false' }};
    if (!isInitiallyActive) {
        window.toggleMrpSection(false);
    }

    const tbody = document.getElementById('mrp_rates_tbody');
    if (tbody && Array.isArray(mrpRatesData) && mrpRatesData.length > 0) {
        tbody.innerHTML = '';
        mrpRatesData.forEach(r => {
            addMrpYearRow(r.year, r.general_rate, r.senior_rate);
        });
    }
    syncHiddenFields();

    // Listen to changes on Section 2 min_amount to auto-sync MRP contribution
    const sec2MinAmount = document.getElementById('min_amount');
    if (sec2MinAmount) {
        sec2MinAmount.addEventListener('input', function() {
            const card = document.getElementById('mrp_configuration_card');
            if (card && card.style.display !== 'none') {
                const mrpContrib = document.getElementById('mrp_contribution_amount');
                const val = parseFloat(this.value) || 0;
                if (mrpContrib && val > 0) {
                    mrpContrib.value = val.toFixed(2);
                    recalculateAllMrpReturns();
                }
            }
        });
    }

    // Attach form submission validation
    const form = document.getElementById('schemeCreateForm') || document.getElementById('schemeEditForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const enabledInput = document.getElementById('mrp_enabled');
            if (enabledInput && !enabledInput.disabled && enabledInput.value === '1') {
                if (!validateDuplicateYears()) {
                    e.preventDefault();
                    alert('Please resolve duplicate years in the MRP configuration before saving.');
                    return false;
                }
            }
        });
    }
});
</script>
