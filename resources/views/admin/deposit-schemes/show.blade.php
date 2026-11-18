@extends('superadmin.layouts.app')

@section('page-title', 'Deposit Scheme: ' . $depositScheme->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $depositScheme->name }} ({{ $depositScheme->code }})</h1>
        <div class="page-breadcrumb">Deposits / Deposit Schemes / {{ $depositScheme->code }}</div>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('superadmin.fd-commissions.edit', $depositScheme) }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-percent me-1"></i> Configure Rank Commissions
        </a>
        <a href="{{ route('admin.deposit-schemes.edit', $depositScheme) }}" class="btn btn-dark btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit Scheme
        </a>
        <a href="{{ route('admin.deposit-schemes.clone', $depositScheme) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-copy me-1"></i> Clone Scheme
        </a>
        <form action="{{ route('admin.deposit-schemes.toggle-status', $depositScheme) }}" method="POST" class="d-inline"
              data-confirm="Are you sure you want to {{ $depositScheme->isActive() ? 'deactivate' : 'activate' }} this scheme?"
              data-confirm-title="{{ $depositScheme->isActive() ? 'Deactivate Scheme' : 'Activate Scheme' }}"
              data-confirm-type="{{ $depositScheme->isActive() ? 'warning' : 'success' }}"
              data-confirm-btn="{{ $depositScheme->isActive() ? 'Deactivate' : 'Activate' }}">
            @csrf
            <button type="submit" class="btn btn-sm {{ $depositScheme->isActive() ? 'btn-outline-danger' : 'btn-outline-success' }}">
                <i class="bi {{ $depositScheme->isActive() ? 'bi-slash-circle' : 'bi-check-circle' }} me-1"></i>
                {{ $depositScheme->isActive() ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
        <form action="{{ route('admin.deposit-schemes.destroy', $depositScheme) }}" method="POST" class="d-inline"
              onsubmit="return confirm('Permanently delete deposit scheme {{ $depositScheme->name }} ({{ $depositScheme->code }})? All interest rate and commission configurations for this scheme will be removed.')">
            @csrf
            @method('DELETE')
            <input type="hidden" name="force" value="1">
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i> Delete Scheme
            </button>
        </form>
        @endif
        <a href="{{ route('admin.deposit-schemes.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Schemes
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Scheme Overview -->
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fs-6 fw-bold">Scheme Specifications</h5>
                <span class="{{ $depositScheme->getStatusBadgeClass() }}">{{ strtoupper($depositScheme->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Scheme Name</div>
                        <div class="fw-semibold fs-6">{{ $depositScheme->name }}</div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Scheme Code</div>
                        <div class="fw-semibold">{{ $depositScheme->code }}</div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Type</div>
                        <div><span class="badge bg-light text-dark border">{{ $depositScheme->scheme_type }}</span></div>
                    </div>

                    @if($depositScheme->description)
                    <div class="col-12">
                        <div class="text-muted small">Description</div>
                        <p class="mb-0 text-secondary">{{ $depositScheme->description }}</p>
                    </div>
                    @endif

                    <div class="col-12"><hr class="my-2"></div>

                    <div class="col-sm-4">
                        <div class="text-muted small">{{ ($depositScheme->isMrpEnabled() || stripos($depositScheme->name, 'MRP') !== false || stripos($depositScheme->name, 'MONTHLY RETURN') !== false) ? 'Contribution Amount' : 'Minimum Amount' }}</div>
                        <div class="fw-bold fs-6 num-tabular">₹{{ number_format($depositScheme->min_amount, 2) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Maximum Amount</div>
                        <div class="fw-bold fs-6 num-tabular">
                            {{ $depositScheme->max_amount ? '₹' . number_format($depositScheme->max_amount, 2) : 'No Upper Limit' }}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Standard Interest Rate</div>
                        <div class="fw-bold fs-6 text-dark num-tabular">{{ number_format($depositScheme->getInterestRateForTenure($depositScheme->tenure, $depositScheme->tenure_unit), 2) }}% p.a.</div>
                    </div>

                    <div class="col-sm-4">
                        <div class="text-muted small">Tenure Duration</div>
                        <div class="fw-semibold">{{ $depositScheme->tenure }} {{ strtolower($depositScheme->tenure_unit) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Collection Frequency</div>
                        <div class="fw-semibold text-uppercase">{{ str_replace('_', ' ', $depositScheme->collection_frequency) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Interest Payout</div>
                        <div class="fw-semibold text-primary">{{ str_replace('_', ' ', $depositScheme->interest_payout_frequency ?? 'AT_MATURITY') }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Promotion Target Credit %</div>
                        <div class="fw-semibold text-warning-emphasis">
                            <i class="bi bi-trophy text-warning me-1"></i>
                            {{ $depositScheme->promotion_target_percentage !== null ? number_format((float) $depositScheme->promotion_target_percentage, 2) . '% credited' : '100.00% (Default Full Volume)' }}
                        </div>
                        <small class="text-muted" style="font-size:0.75rem;">Volume ratio credited toward agent promotion targets</small>
                    </div>
                    <div class="col-12"><hr class="my-2"></div>

                    <div class="col-sm-6">
                        <div class="text-muted small">Created By</div>
                        <div class="text-secondary">{{ $depositScheme->creator?->name ?? 'System' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Created At</div>
                        <div class="text-secondary">{{ $depositScheme->created_at?->format('d M Y, h:i A') ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>



        @if($depositScheme->isKsp())
        @php
            $kspConfig = $depositScheme->getKspConfig();
        @endphp
        <div class="card mb-3" id="ksp_details_card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-success fs-5"></i>
                    <h5 class="card-title mb-0 fs-6 fw-bold text-uppercase">KSP SPECIAL CONFIGURATION</h5>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Kissan Samrudhi Plan (KSP)</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small">Annual Deposit Amount</div>
                        <div class="fw-bold fs-6 num-tabular">₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspConfig['annual_deposit_amount'], 0) }}</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small">Deposit Period</div>
                        <div class="fw-semibold num-tabular">{{ $kspConfig['deposit_period'] }} {{ $kspConfig['deposit_period'] === 1 ? 'Year' : 'Years' }}</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small">Insurance Coverage</div>
                        <div class="fw-bold fs-6 text-primary num-tabular">₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspConfig['insurance_coverage'], 0) }}</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small">Annual Return Amount</div>
                        <div class="fw-bold fs-6 text-success num-tabular">₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspConfig['annual_return_amount'], 0) }}</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small">Return Start Year</div>
                        <div class="fw-semibold num-tabular">Year {{ $kspConfig['return_start_year'] }}</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small">Return End Year</div>
                        <div class="fw-semibold num-tabular">Year {{ $kspConfig['return_end_year'] }}</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small">Return Frequency</div>
                        <div class="fw-semibold">{{ $kspConfig['return_frequency'] }}</div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted small">Premature Withdrawal</div>
                        <div class="fw-semibold">{{ $kspConfig['premature_withdrawal'] }}</div>
                    </div>
                </div>

                @php
                    $kspStartYr = (int) $kspConfig['return_start_year'];
                    $kspEndYr = (int) $kspConfig['return_end_year'];
                    $kspPayoutCount = max(1, $kspEndYr - $kspStartYr + 1);
                    $kspTotalReturn = (float) $kspConfig['annual_return_amount'] * $kspPayoutCount;
                @endphp
                <div class="mt-3 p-3 bg-light border">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div class="fw-semibold small text-dark">
                            <i class="bi bi-calendar-range text-success me-1"></i>KSP {{ $kspConfig['deposit_period'] }} Year Return Payout Schedule
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle num-tabular">
                            Total Return: ₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspTotalReturn, 0) }} ({{ $kspPayoutCount }} {{ $kspPayoutCount === 1 ? 'Year' : 'Years' }})
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @for($y = $kspStartYr; $y <= min($kspEndYr, $kspStartYr + 29); $y++)
                            <span class="badge bg-white text-dark border font-monospace num-tabular px-2 py-1" style="font-size:0.74rem;">
                                <i class="bi bi-arrow-right-circle-fill text-success me-1"></i>{{ $y }}th Year: <strong>₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspConfig['annual_return_amount'], 0) }}</strong>
                            </span>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($depositScheme->mrpConfiguration && $depositScheme->mrpConfiguration->is_enabled)
        <div class="card mb-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar2-week text-primary fs-5"></i>
                    <h5 class="card-title mb-0 fs-6 fw-bold">Monthly Return Plan (MRP) Configuration</h5>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Monthly Contribution Amount</div>
                        <div class="fw-bold fs-6 num-tabular">₹{{ number_format($depositScheme->mrpConfiguration->contribution_amount, 2) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Tenure Limits</div>
                        <div class="fw-semibold">{{ $depositScheme->mrpConfiguration->min_tenure }} - {{ $depositScheme->mrpConfiguration->max_tenure }} {{ strtolower($depositScheme->mrpConfiguration->tenure_unit) }}</div>
                    </div>
                </div>

                @if($depositScheme->mrpRates && $depositScheme->mrpRates->count() > 0)
                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-secondary small text-uppercase">
                                <th class="text-center">Year</th>
                                <th>General Rate</th>
                                <th>General Monthly Return</th>
                                <th>Senior Rate</th>
                                <th>Senior Monthly Return</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($depositScheme->mrpRates as $rate)
                            <tr>
                                <td class="text-center fw-bold">Year {{ $rate->year }}</td>
                                <td>{{ number_format($rate->general_rate, 2) }}% p.a.</td>
                                <td class="fw-bold text-success">₹{{ number_format($rate->general_monthly_return, 2) }}</td>
                                <td>{{ number_format($rate->senior_rate, 2) }}% p.a.</td>
                                <td class="fw-bold text-primary">₹{{ number_format($rate->senior_monthly_return, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Recent Deposits under this Scheme -->
        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fs-6 fw-bold">Recent Deposits under this Scheme</h5>
                <span class="badge bg-dark">{{ $recentDeposits->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Deposit #</th>
                                <th>Customer</th>
                                <th>Branch</th>
                                <th>Amount</th>
                                <th>Start Date</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentDeposits as $dep)
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="{{ route('admin.deposits.show', $dep) }}" class="text-dark text-decoration-none">
                                            {{ $dep->deposit_number }}
                                        </a>
                                    </td>
                                    <td>{{ $dep->customer?->name ?? 'N/A' }}</td>
                                    <td>{{ $dep->branch?->name ?? 'N/A' }}</td>
                                    <td class="fw-bold num-tabular">₹{{ number_format($dep->amount, 2) }}</td>
                                    <td>{{ $dep->start_date?->format('d M Y') }}</td>
                                    <td>
                                        <span class="{{ $dep->getStatusBadgeClass() }}">
                                            {{ $dep->status }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.deposits.show', $dep) }}" class="btn btn-sm btn-outline-dark">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No deposits have been created under this scheme yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">Scheme Summary</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Total Deposit Accounts</span>
                    <span class="fw-bold fs-6">{{ $depositScheme->deposits_count }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Status</span>
                    <span class="{{ $depositScheme->getStatusBadgeClass() }}">{{ strtoupper($depositScheme->status) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Created By</span>
                    <span class="fw-medium">{{ $depositScheme->creator?->name ?? 'System' }}</span>
                </div>
            </div>
            @if($depositScheme->isActive() && !auth()->user()?->isSuperAdmin())
            <div class="card-footer bg-white border-top">
                <a href="{{ route('admin.deposits.create') }}" class="btn btn-dark w-100 btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Open Deposit With Scheme
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
