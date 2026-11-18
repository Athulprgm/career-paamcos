@extends('superadmin.layouts.app')

@section('page-title', 'Add New Lead')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-sm-center align-items-start gap-2 mb-3 mb-md-4">
    <div class="min-w-0">
        <h1 class="page-title mb-1 text-truncate">Add Business Lead</h1>
        <div class="page-breadcrumb text-muted text-break">
            <a href="{{ route('agent.leads.index') }}" class="text-decoration-none">Leads</a> /
            <span class="text-dark fw-semibold">New Lead</span>
        </div>
    </div>
    <div>
        <a href="{{ route('agent.leads.index') }}" class="btn btn-outline-secondary btn-sm text-nowrap d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Leads</span>
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border">
            <div class="card-header bg-white py-3">
                <h6 class="card-title m-0 fw-bold">Prospective Customer Information</h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="{{ route('agent.leads.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Customer Area Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" class="form-select searchable-select @error('branch_id') is-invalid @enderror" data-search-placeholder="Search branch name or code..." data-placeholder="Select Branch (Area)" required>
                                <option value="">Select Branch (Area)</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" data-badge="{{ $branch->code }}" {{ (string) old('branch_id', $agent?->branch_id) === (string) $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->code }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">Branch where this customer will be serviced.</div>
                            @error('branch_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach(\App\Models\Lead::getAvailableStatuses() as $key => $label)
                                    <option value="{{ $key }}" {{ old('status', 'NEW') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Rahul Sharma" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" placeholder="10-digit mobile number" required>
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="name@example.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Interested Deposit Scheme</label>
                            <select name="interested_scheme_id" id="interested_scheme_id" class="form-select searchable-select @error('interested_scheme_id') is-invalid @enderror" data-search-placeholder="Search deposit scheme name or code..." data-placeholder="Select Scheme (Optional)">
                                <option value="">Select Scheme (Optional)</option>
                                @foreach($schemes as $scheme)
                                    <option value="{{ $scheme->id }}" data-badge="{{ $scheme->code }}" {{ old('interested_scheme_id') == $scheme->id ? 'selected' : '' }}>
                                        {{ $scheme->name }} ({{ $scheme->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('interested_scheme_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Expected Deposit Amount (₹)</label>
                            <input type="number" step="0.01" name="expected_amount" class="form-control @error('expected_amount') is-invalid @enderror" value="{{ old('expected_amount') }}" placeholder="e.g. 50000">
                            @error('expected_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Next Follow-up Date</label>
                            <input type="date" name="follow_up_date" class="form-control @error('follow_up_date') is-invalid @enderror" value="{{ old('follow_up_date') }}">
                            @error('follow_up_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="Locality, City, Postal Code...">{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Meeting Notes / Comments</label>
                            <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="Record discussion details, customer interest points, requirements...">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-end gap-2">
                        <a href="{{ route('agent.leads.index') }}" class="btn btn-outline-secondary flex-fill flex-sm-grow-0 text-nowrap">Cancel</a>
                        <button type="submit" class="btn btn-dark flex-fill flex-sm-grow-0 text-nowrap">
                            <i class="bi bi-check-lg me-1"></i> Save Lead
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@include('components.searchable-select-script')
@endsection
