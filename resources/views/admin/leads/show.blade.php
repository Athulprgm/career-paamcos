@extends('superadmin.layouts.app')

@section('page-title', 'Lead Details — ' . $lead->name)

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="page-title mb-0">{{ $lead->name }}</h1>
            <span class="{{ $lead->getStatusBadgeClass() }}">{{ $lead->status }}</span>
        </div>
        <div class="page-breadcrumb">
            Operations / <a href="{{ route('admin.leads.index') }}" style="color:inherit;">Agent Leads</a> / Details
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="{{ route('admin.deposits.create', ['lead_id' => $lead->id]) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-wallet2 me-1"></i> Add Deposit for this Lead
        </a>

        @if($lead->agent)
            <a href="{{ route('admin.agents.show', $lead->agent) }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-person-vcard me-1"></i> View Agent Profile
            </a>
        @endif

        <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Leads
        </a>

        @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
            <form action="{{ route('admin.leads.destroy', $lead) }}" method="POST" class="d-inline m-0"
                  onsubmit="return confirm('Permanently delete lead {{ $lead->name }}? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i> Delete Lead
                </button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($existingCustomer && !$lead->isConverted())
<div class="alert alert-info border-info p-3 mb-3 d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill fs-4 text-info"></i>
        <div>
            <strong>Matching Account Found:</strong>
            A registered customer with mobile number <code>{{ $lead->mobile }}</code> already exists in the system:
            <strong>{{ $existingCustomer->name }}</strong> (<span class="num-tabular">{{ $existingCustomer->customer_number }}</span> &bull; Branch: {{ $existingCustomer->branch?->name }}).
        </div>
    </div>
</div>
@endif

<div class="row g-3">
    <!-- Left Column: Lead Profile & Details -->
    <div class="col-lg-7">
        <!-- Lead Information Card -->
        <div class="card mb-3 border">
            <div class="card-header bg-white py-3 fw-semibold text-dark">
                <i class="bi bi-person-lines-fill me-1 text-primary"></i> Lead Information
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">FULL NAME</div>
                        <div class="fw-semibold text-dark mt-1 fs-6">{{ $lead->name }}</div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">STATUS</div>
                        <div class="mt-1">
                            <span class="{{ $lead->getStatusBadgeClass() }}">{{ $lead->status }}</span>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">MOBILE NUMBER</div>
                        <div class="fw-medium text-dark mt-1">
                            <a href="tel:{{ $lead->mobile }}" class="text-dark text-decoration-none num-tabular">
                                <i class="bi bi-telephone text-muted me-1"></i>{{ $lead->mobile }}
                            </a>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">EMAIL ADDRESS</div>
                        <div class="fw-medium text-dark mt-1">
                            @if($lead->email)
                                <a href="mailto:{{ $lead->email }}" class="text-dark text-decoration-none">
                                    <i class="bi bi-envelope text-muted me-1"></i>{{ $lead->email }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">ADDRESS</div>
                        <div class="text-dark mt-1">{{ $lead->address ?: '—' }}</div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">DATE ADDED</div>
                        <div class="text-dark mt-1">
                            <i class="bi bi-calendar3 text-muted me-1"></i>
                            <span class="num-tabular">{{ $lead->created_at ? $lead->created_at->format('d M Y, h:i A') : '—' }}</span>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">RECORDED BY</div>
                        <div class="text-dark mt-1">
                            <i class="bi bi-person text-muted me-1"></i>
                            {{ $lead->creator?->name ?? 'System' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scheme Interest & Target -->
        <div class="card mb-3 border">
            <div class="card-header bg-white py-3 fw-semibold text-dark">
                <i class="bi bi-piggy-bank me-1 text-primary"></i> Financial Interest & Deposit Scheme
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">INTERESTED DEPOSIT SCHEME</div>
                        <div class="fw-semibold text-dark mt-1">
                            {{ $lead->interestedScheme?->name ?? 'General Inquiry (No specific scheme)' }}
                        </div>
                        @if($lead->interestedScheme)
                            <div class="text-muted small mt-1 font-monospace">
                                Code: {{ $lead->interestedScheme->code }} &bull; Type: {{ $lead->interestedScheme->scheme_type ?? 'Standard' }}
                            </div>
                        @endif
                    </div>

                    <div class="col-sm-6">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">EXPECTED DEPOSIT AMOUNT</div>
                        <div class="fw-bold text-dark mt-1 fs-5 num-tabular">
                            @if($lead->expected_amount)
                                ₹{{ number_format($lead->expected_amount, 2) }}
                            @else
                                <span class="text-muted fs-6 fw-normal">Not specified</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">NOTES / OBSERVATIONS</div>
                        <div class="p-3 bg-light border mt-1 text-dark" style="font-size:0.875rem; min-height:60px;">
                            {{ $lead->notes ?: 'No notes or observations recorded for this lead.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deposits Linked to this Lead -->
        <div class="card mb-3 border">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark">
                    <i class="bi bi-wallet2 me-1 text-primary"></i> Linked Deposits & Agent Commissions
                </span>
                <a href="{{ route('admin.deposits.create', ['lead_id' => $lead->id]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Deposit
                </a>
            </div>
            <div class="card-body p-0">
                @if($lead->deposits && $lead->deposits->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Deposit #</th>
                                    <th>Scheme & Amount</th>
                                    <th>Deposit Status</th>
                                    <th>Agent Commission</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lead->deposits as $dep)
                                    <tr>
                                        <td>
                                            @if(auth()->user()->isAgent())
                                                <span class="fw-bold text-dark font-monospace num-tabular">{{ $dep->deposit_number }}</span>
                                            @else
                                                <a href="{{ route('admin.deposits.show', $dep) }}" class="fw-bold text-dark text-decoration-none num-tabular">
                                                    {{ $dep->deposit_number }}
                                                </a>
                                            @endif
                                            <div class="text-muted small num-tabular">{{ $dep->created_at ? $dep->created_at->format('d M Y') : '—' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark">{{ $dep->scheme?->name ?? 'Standard Deposit' }}</div>
                                            <div class="text-primary fw-semibold num-tabular">₹{{ number_format($dep->total_deposit_amount, 2) }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $dep->status }}</span>
                                        </td>
                                        <td>
                                            @php $commission = $dep->commissions->first(); @endphp
                                            @if($commission)
                                                <div class="fw-bold text-dark num-tabular">₹{{ number_format($commission->commission_amount, 2) }}</div>
                                                <span class="{{ $commission->getStatusBadgeClass() }}">{{ $commission->status }}</span>
                                            @else
                                                <span class="text-muted">None</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!auth()->user()->isAgent())
                                                <a href="{{ route('admin.deposits.show', $dep) }}" class="btn btn-sm btn-outline-dark" title="View Deposit">
                                                    <i class="bi bi-arrow-right"></i>
                                                </a>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-folder-x fs-3 d-block mb-2 text-muted"></i>
                        No deposits have been created for this lead yet.
                        <div class="mt-2">
                            <a href="{{ route('admin.deposits.create', ['lead_id' => $lead->id]) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-wallet2 me-1"></i> Create First Deposit
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Agent Lineage & Quick Status Update -->
    <div class="col-lg-5">
        <!-- Assigned Agent Card -->
        <div class="card mb-3 border">
            <div class="card-header bg-white py-3 fw-semibold text-dark">
                <i class="bi bi-person-vcard me-1 text-primary"></i> Assigned Agent & Branch
            </div>
            <div class="card-body p-3" style="font-size:0.85rem;">
                @if($lead->agent)
                    <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                        <div style="width:48px;height:48px;background:var(--color-deep-green, #14532D);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:600;">
                            {{ strtoupper(substr($lead->agent->name, 0, 1)) }}
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">
                                <a href="{{ route('admin.agents.show', $lead->agent) }}" class="text-dark text-decoration-none">
                                    {{ $lead->agent->name }}
                                </a>
                            </h6>
                            <div class="text-muted small font-monospace">
                                <span class="num-tabular">{{ $lead->agent->member_code ?? $lead->agent->agent_id }}</span>
                                &bull; <span class="badge bg-light text-dark border">{{ $lead->agent->rank?->name ?? 'Agent' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-building me-2"></i>Branch</span>
                        <span class="fw-semibold text-dark">{{ $lead->branch?->name ?? $lead->agent->branch?->name ?? '—' }}</span>
                    </div>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-telephone me-2"></i>Agent Contact</span>
                        <span class="fw-medium text-dark num-tabular">{{ $lead->agent->phone }}</span>
                    </div>

                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted"><i class="bi bi-envelope me-2"></i>Agent Email</span>
                        <span class="fw-medium text-dark text-break">{{ $lead->agent->email }}</span>
                    </div>
                @else
                    <div class="text-muted py-3 text-center">
                        No agent profile linked.
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Status & Follow-up Update Form -->
        <div class="card mb-3 border">
            <div class="card-header bg-white py-3 fw-semibold text-dark">
                <i class="bi bi-pencil-square me-1 text-primary"></i> Update Status & Follow-up
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('admin.leads.update-status', $lead) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="status" class="form-label small fw-semibold">Lead Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select form-select-sm @error('status') is-invalid @enderror" required>
                            @foreach(\App\Models\Lead::getAvailableStatuses() as $key => $label)
                                <option value="{{ $key }}" {{ old('status', $lead->status) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="follow_up_date" class="form-label small fw-semibold">Next Follow-up Date</label>
                        <input type="date" id="follow_up_date" name="follow_up_date"
                               class="form-control form-control-sm @error('follow_up_date') is-invalid @enderror"
                               value="{{ old('follow_up_date', $lead->follow_up_date?->format('Y-m-d')) }}">
                        @error('follow_up_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label small fw-semibold">Notes & Remarks</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="form-control form-control-sm @error('notes') is-invalid @enderror"
                                  placeholder="Record any discussion notes, updates, or customer requirements...">{{ old('notes', $lead->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-dark btn-sm w-100">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
