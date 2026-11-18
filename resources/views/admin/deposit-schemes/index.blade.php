@extends('superadmin.layouts.app')

@section('page-title', 'Deposit Schemes')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Deposit Schemes</h1>
        <div class="page-breadcrumb text-muted small">
            <span>Deposits</span> <i class="bi bi-chevron-right mx-1" style="font-size:0.7rem;"></i> <span>Add & Manage Schemes</span>
        </div>
    </div>
    @if(auth()->user()->isSuperAdmin())
    <div class="d-flex gap-2">
        <a href="{{ route('superadmin.fd-commissions.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-percent"></i>
            <span>Rank Commissions Matrix</span>
        </a>
        <a href="{{ route('admin.deposit-schemes.create') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-plus-lg"></i>
            <span>Add Deposit Scheme</span>
        </a>
    </div>
    @endif
</div>

<!-- Quick Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="card border">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-medium">Total Schemes</div>
                    <div class="fs-4 fw-bold text-dark mt-1 num-tabular">{{ $totalCount ?? $schemes->total() }}</div>
                </div>
                <div class="bg-light border text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-journal-bookmark fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card border">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-medium">Active Schemes</div>
                    <div class="fs-4 fw-bold text-success mt-1 num-tabular">{{ $activeCount ?? 0 }}</div>
                </div>
                <div class="bg-light border text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-check-circle fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card border">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-medium">Inactive Schemes</div>
                    <div class="fs-4 fw-bold text-secondary mt-1 num-tabular">{{ $inactiveCount ?? 0 }}</div>
                </div>
                <div class="bg-light border text-secondary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-pause-circle fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card border mb-4">
    <div class="card-body py-3 px-3">
        <form method="GET" action="{{ route('admin.deposit-schemes.index') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div style="min-width: 220px;" class="flex-grow-1">
                <label class="form-label small text-muted mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Search name, code, type…" value="{{ request('search') }}">
                </div>
            </div>

            <div style="min-width: 170px;">
                <label class="form-label small text-muted mb-1">Scheme Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Scheme Types</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 140px;">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE" {{ strtoupper(request('status') ?? '') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                    <option value="INACTIVE" {{ strtoupper(request('status') ?? '') === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                </select>
            </div>

            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('admin.deposit-schemes.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Schemes Table Card -->
<div class="card border">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <div class="fw-semibold text-dark">All Deposit Schemes</div>
        <span class="badge bg-light text-dark border num-tabular">{{ $schemes->total() }} {{ Str::plural('Scheme', $schemes->total()) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th style="padding-left: 1.25rem;">Code</th>
                        <th>Scheme Details</th>
                        <th>Type</th>
                        <th>Amount Limits</th>
                        <th>Tenure</th>
                        <th style="min-width: 175px;">Interest Rates</th>
                        <th>Frequency</th>
                        <th>Accounts</th>
                        <th>Status</th>
                        <th class="text-end" style="padding-right: 1.25rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schemes as $scheme)
                        <tr>
                            <td style="padding-left: 1.25rem;">
                                <a href="{{ route('admin.deposit-schemes.show', $scheme) }}" class="text-decoration-none">
                                    <span class="badge bg-dark text-white font-monospace px-2 py-1">{{ $scheme->code }}</span>
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('admin.deposit-schemes.show', $scheme) }}" class="text-dark fw-semibold text-decoration-none d-block">
                                    {{ $scheme->name }}
                                </a>
                                @if($scheme->description)
                                    <small class="text-muted text-truncate d-block" style="max-width: 240px;" title="{{ $scheme->description }}">
                                        {{ $scheme->description }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $scheme->scheme_type }}</span>
                            </td>
                            <td>
                                <div class="fw-medium num-tabular">₹{{ number_format($scheme->min_amount, 2) }}</div>
                                <small class="text-muted num-tabular">
                                    to {{ $scheme->max_amount ? '₹' . number_format($scheme->max_amount, 2) : 'No Max' }}
                                </small>
                            </td>
                            <td>
                                <span class="fw-medium num-tabular">{{ $scheme->tenure }}</span>
                                <small class="text-muted">{{ strtolower($scheme->tenure_unit) }}</small>
                            </td>
                            <td>
                                @if($scheme->isKsp())
                                    @php
                                        $kspCfg = $scheme->getKspConfig();
                                        $rStart = (int) $kspCfg['return_start_year'];
                                        $rEnd = (int) $kspCfg['return_end_year'];
                                        $winBadge = $rStart === $rEnd ? "{$rStart}th Yr" : ($rEnd === $rStart + 1 ? "{$rStart} & {$rEnd} Yr" : "Yr {$rStart}–{$rEnd}");
                                    @endphp
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold" style="font-size:0.675rem;">
                                            {{ $winBadge }}
                                        </span>
                                        <span class="fw-bold text-success fs-7 num-tabular">
                                            ₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspCfg['annual_return_amount'], 0) }}<small class="text-muted fw-normal" style="font-size:0.65rem;">/yr</small>
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge bg-light text-muted border fw-normal" style="font-size:0.65rem;">Insurance</span>
                                        <span class="text-primary fw-semibold num-tabular" style="font-size:0.75rem;">
                                            ₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspCfg['insurance_coverage'], 0) }}
                                        </span>
                                    </div>
                                @else
                                    @php
                                        $applicableRate = $scheme->getInterestRateForTenure($scheme->tenure, $scheme->tenure_unit);
                                        $seniorExtra = (float) $scheme->getSeniorCitizenExtraRateForTenure($scheme->tenure, $scheme->tenure_unit);
                                        $seniorTotal = $applicableRate + $seniorExtra;
                                        $activeTiersCount = $scheme->activeInterestRates->count();
                                    @endphp
                                    <!-- Type 1: Standard / Regular Rate -->
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="badge bg-light text-dark border fw-semibold" style="font-size:0.675rem;">Standard</span>
                                        <span class="fw-bold text-success fs-7 num-tabular">
                                            {{ number_format($applicableRate, 2) }}% <small class="text-muted fw-normal" style="font-size:0.65rem;">p.a.</small>
                                        </span>
                                    </div>

                                    <!-- Senior Citizen Rate (if configured) -->
                                    @if($seniorExtra > 0)
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fw-semibold" style="font-size:0.675rem;">
                                                <i class="bi bi-person-heart me-1"></i>Senior
                                            </span>
                                            <span class="fw-bold text-primary num-tabular" style="font-size:0.8rem;">
                                                {{ number_format($seniorTotal, 2) }}%
                                                <small class="text-muted fw-normal" style="font-size:0.65rem;">(+{{ number_format($seniorExtra, 2) }}%)</small>
                                            </span>
                                        </div>
                                    @endif

                                    @if($activeTiersCount > 1)
                                        <div class="pt-1 mt-1 border-top d-flex align-items-center justify-content-between">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle num-tabular" style="font-size:0.65rem;">
                                                <i class="bi bi-clock-history me-1"></i>{{ $activeTiersCount }} Tiers
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border small text-uppercase">
                                    {{ str_replace('_', ' ', $scheme->collection_frequency) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border num-tabular">{{ $scheme->deposits_count ?? 0 }}</span>
                            </td>
                            <td>
                                <span class="{{ $scheme->getStatusBadgeClass() }}">
                                    {{ strtoupper($scheme->status) }}
                                </span>
                            </td>
                            <td class="text-end" style="padding-right: 1.25rem;">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <!-- View Option -->
                                    <a href="{{ route('admin.deposit-schemes.show', $scheme) }}"
                                       class="btn btn-sm btn-outline-dark" title="View Scheme Details">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if(auth()->user()->isSuperAdmin())
                                    <!-- Rank Commissions Matrix Option -->
                                    <a href="{{ route('superadmin.fd-commissions.edit', $scheme) }}"
                                       class="btn btn-sm btn-outline-info" title="Configure / Manage Rank Commissions ({{ number_format((float) $scheme->total_commission_percentage, 2) }}%)">
                                        <i class="bi bi-percent"></i>
                                    </a>

                                    <!-- Edit Option -->
                                    <a href="{{ route('admin.deposit-schemes.edit', $scheme) }}"
                                       class="btn btn-sm btn-outline-primary" title="Edit Scheme">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <!-- Clone Option -->
                                    <a href="{{ route('admin.deposit-schemes.clone', $scheme) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Clone Scheme: {{ $scheme->name }}">
                                        <i class="bi bi-copy"></i>
                                    </a>

                                    <!-- Status Toggle Option -->
                                    <form action="{{ route('admin.deposit-schemes.toggle-status', $scheme) }}"
                                          method="POST" class="d-inline"
                                          data-confirm="Are you sure you want to {{ $scheme->isActive() ? 'deactivate' : 'activate' }} this scheme?"
                                          data-confirm-title="{{ $scheme->isActive() ? 'Deactivate Scheme' : 'Activate Scheme' }}"
                                          data-confirm-type="{{ $scheme->isActive() ? 'warning' : 'success' }}"
                                          data-confirm-btn="{{ $scheme->isActive() ? 'Deactivate' : 'Activate' }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $scheme->isActive() ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                title="{{ $scheme->isActive() ? 'Deactivate Scheme' : 'Activate Scheme' }}">
                                            <i class="bi {{ $scheme->isActive() ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                                        </button>
                                    </form>

                                    <!-- Delete Option -->
                                    <form action="{{ route('admin.deposit-schemes.destroy', $scheme) }}"
                                          method="POST" class="d-inline"
                                          data-confirm="Are you sure you want to delete scheme: {{ $scheme->name }} ({{ $scheme->code }})? This action cannot be undone."
                                          data-confirm-title="Delete Deposit Scheme"
                                          data-confirm-type="danger"
                                          data-confirm-btn="Delete Scheme">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="force" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Scheme">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <div class="py-3">
                                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-muted"></i>
                                    <h6 class="fw-semibold text-dark mb-1">No Deposit Schemes Found</h6>
                                    <p class="small text-muted mb-3">No deposit schemes match the current filters or none have been configured yet.</p>
                                    @if(auth()->user()->isSuperAdmin())
                                    <a href="{{ route('admin.deposit-schemes.create') }}" class="btn btn-dark btn-sm">
                                        <i class="bi bi-plus-lg me-1"></i> Add New Scheme
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($schemes->hasPages())
        <div class="card-footer bg-white border-top py-2 px-3">
            {{ $schemes->links() }}
        </div>
    @endif
</div>
@endsection
