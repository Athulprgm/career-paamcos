@extends('superadmin.layouts.app')

@section('page-title', 'Lead Details — ' . $lead->name)

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-sm-center align-items-start gap-2 mb-3 mb-md-4">
    <div class="min-w-0">
        <h1 class="page-title mb-1 text-truncate">{{ $lead->name }}</h1>
        <div class="page-breadcrumb text-muted text-break">
            <a href="{{ route('agent.leads.index') }}" class="text-decoration-none">Leads</a> /
            <span class="text-dark fw-semibold">{{ $lead->name }}</span>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 w-100 w-sm-auto">
        <a href="{{ route('agent.leads.index') }}" class="btn btn-outline-secondary btn-sm flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        @if(!$lead->isConverted())
            <a href="{{ route('agent.leads.edit', $lead) }}" class="btn btn-outline-dark btn-sm flex-fill flex-sm-grow-0 text-nowrap">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
        @else
            <span class="badge bg-success py-2 px-3 text-nowrap">
                <i class="bi bi-check-circle me-1"></i> Converted
            </span>
        @endif
        @php
            $cleanLeadPhone = preg_replace('/[^0-9]/', '', (string) $lead->mobile);
        @endphp
        @if($cleanLeadPhone)
        <a href="https://wa.me/{{ $cleanLeadPhone }}?text={{ urlencode('Hello ' . $lead->name . ', greetings from MultiCred.') }}" target="_blank" class="btn btn-outline-success btn-sm flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-whatsapp me-1"></i> WhatsApp
        </a>
        @endif
        <a href="tel:{{ $lead->mobile }}" class="btn btn-dark btn-sm flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-telephone me-1"></i> Call
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 mb-md-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3 mb-md-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row g-3 g-md-4">
    <!-- Left Column: Lead Profile & Details -->
    <div class="col-lg-4">
        <!-- Profile Card -->
        <div class="card border mb-3 mb-md-4">
            <div class="card-body text-center p-3 p-md-4">
                <div class="bg-dark text-white mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold" style="width: 72px; height: 72px; font-size: 1.5rem;">
                    {{ strtoupper(substr($lead->name, 0, 1)) }}
                </div>
                <h5 class="fw-bold mb-1 text-truncate">{{ $lead->name }}</h5>
                <div class="mb-2">
                    <span class="{{ $lead->getStatusBadgeClass() }}">{{ $lead->status }}</span>
                </div>
            </div>
            <div class="border-top p-3 bg-light">
                <div class="d-flex justify-content-between py-1 small text-break">
                    <span class="text-muted">Branch</span>
                    <span class="fw-semibold text-dark text-end ms-2">{{ $lead->branch?->name ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 small text-break">
                    <span class="text-muted">Follow-up Date</span>
                    <span class="fw-semibold text-dark text-end ms-2 num-tabular">
                        {{ $lead->follow_up_date ? $lead->follow_up_date->format('d M Y') : 'None scheduled' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between py-1 small text-break">
                    <span class="text-muted">Registered On</span>
                    <span class="fw-semibold text-dark text-end ms-2 num-tabular">{{ $lead->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Contact Details -->
        <div class="card border mb-3 mb-md-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title m-0 fw-bold">Contact Details</h6>
            </div>
            <div class="card-body p-3">
                <div class="mb-3 text-break">
                    <label class="text-muted small d-block">Phone Number</label>
                    <a href="tel:{{ $lead->mobile }}" class="text-decoration-none text-dark fw-medium num-tabular">
                        <i class="bi bi-telephone text-muted me-1"></i> {{ $lead->mobile }}
                    </a>
                </div>
                <div class="mb-3 text-break">
                    <label class="text-muted small d-block">Email Address</label>
                    @if($lead->email)
                        <a href="mailto:{{ $lead->email }}" class="text-decoration-none text-dark fw-medium">
                            <i class="bi bi-envelope text-muted me-1"></i> {{ $lead->email }}
                        </a>
                    @else
                        <span class="text-muted small">Not provided</span>
                    @endif
                </div>
                <div class="text-break">
                    <label class="text-muted small d-block">Address</label>
                    <span class="text-dark">
                        <i class="bi bi-geo-alt text-muted me-1"></i> {{ $lead->address ?: 'Not provided' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Fast Status Update -->
        <div class="card border">
            <div class="card-header bg-white py-3">
                <h6 class="card-title m-0 fw-bold">Update Lead Status</h6>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('agent.leads.quick-status', $lead) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label small text-muted">Status</label>
                        <select name="status" class="form-select form-select-sm" required>
                            @foreach(\App\Models\Lead::getAvailableStatuses() as $key => $label)
                                <option value="{{ $key }}" {{ $lead->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Follow-up Date</label>
                        <input type="date" name="follow_up_date" class="form-control form-control-sm" value="{{ $lead->follow_up_date ? $lead->follow_up_date->format('Y-m-d') : '' }}">
                    </div>
                    <button type="submit" class="btn btn-sm btn-dark w-100">
                        <i class="bi bi-check2 me-1"></i> Save Status
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Opportunity & Notes -->
    <div class="col-lg-8">
        <!-- Overview Stat Cards -->
        <div class="row g-2 g-sm-3 mb-3 mb-md-4">
            <div class="col-12 col-sm-6">
                <div class="stat-card h-100">
                    <div class="stat-icon emerald"><i class="bi bi-currency-rupee"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="stat-value text-success num-tabular">
                            {{ $lead->expected_amount ? '₹'.number_format($lead->expected_amount, 2) : '—' }}
                        </div>
                        <div class="stat-label text-truncate">Expected Investment</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6">
                <div class="stat-card h-100">
                    <div class="stat-icon dark"><i class="bi bi-piggy-bank"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="stat-value text-truncate" style="font-size: 1.15rem;">
                            {{ $lead->interestedScheme?->name ?? 'Any Scheme' }}
                        </div>
                        <div class="stat-label text-truncate">Target Deposit Scheme</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes / Comments -->
        <div class="card border mb-3 mb-md-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title m-0 fw-bold">Interaction Notes & Comments</h6>
            </div>
            <div class="card-body p-3 p-md-4">
                @if($lead->notes)
                    <div class="p-3 bg-light border mb-3 text-dark text-break" style="white-space: pre-wrap; font-size: 0.9rem;">{{ $lead->notes }}</div>
                @else
                    <div class="text-muted fst-italic mb-3">No interaction notes recorded yet.</div>
                @endif

                <form method="POST" action="{{ route('agent.leads.quick-status', $lead) }}" class="border-top pt-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $lead->status }}">
                    <label class="form-label small fw-semibold">Add Follow-up Note</label>
                    <textarea name="note" rows="3" class="form-control form-control-sm mb-2" placeholder="Record discussion details, call summary, or customer questions..." required></textarea>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-plus-lg me-1"></i> Append Note
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($lead->isConverted())
        <!-- Converted Customer Information -->
        <div class="card border mb-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title m-0 fw-bold">Converted Account</h6>
                <span class="badge bg-success">Deposit Successful</span>
            </div>
            <div class="card-body p-3">
                @if($lead->convertedCustomer)
                    <div class="p-3 bg-light border mb-3">
                        <div class="text-muted small">Registered Customer</div>
                        <div class="fw-bold text-dark fs-6">{{ $lead->convertedCustomer->name }}</div>
                        <div class="font-monospace small text-muted num-tabular">{{ $lead->convertedCustomer->customer_number }}</div>
                    </div>
                @endif

                @if($lead->deposits && $lead->deposits->isNotEmpty())
                    @foreach($lead->deposits as $dep)
                        <div class="p-2 mb-2 bg-light border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold font-monospace text-dark small num-tabular">{{ $dep->deposit_number }}</span>
                                <span class="{{ $dep->getStatusBadgeClass() }}">{{ $dep->status }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                                <span>Scheme:</span>
                                <span class="fw-semibold text-dark">{{ $dep->scheme?->name ?? 'Deposit Scheme' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small text-muted">
                                <span>Deposit Amount:</span>
                                <span class="fw-bold text-success fs-6 num-tabular">₹{{ number_format($dep->amount, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
