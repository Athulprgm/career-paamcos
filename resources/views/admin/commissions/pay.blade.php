@extends('superadmin.layouts.app')

@section('page-title', 'Pay Commission ' . $commission->commission_number)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Disburse Commission Payment</h1>
        <div class="page-breadcrumb">Commission / Payout / {{ $commission->commission_number }}</div>
    </div>
    <a href="{{ route('admin.commissions.show', $commission) }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Details
    </a>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Summary Card -->
        <div class="card mb-4" style="border-top: 4px solid var(--color-secondary, #15803D);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="text-muted small">Commission Number:</span>
                        <h4 class="mb-0 fw-bold" style="color:var(--color-primary);">{{ $commission->commission_number }}</h4>
                    </div>
                    <span class="badge-payable fs-6">PAYABLE</span>
                </div>

                <div class="row g-3 py-2 border-top border-bottom mb-3" style="background:var(--surface-subtle);">
                    <div class="col-sm-4">
                        <span class="text-muted small d-block">Agent:</span>
                        <strong>{{ $commission->agent->name ?? 'N/A' }}</strong>
                        <div class="small text-muted">{{ $commission->agent->member_code ?? $commission->agent->agent_id }}</div>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted small d-block">Branch:</span>
                        <strong>{{ $commission->branch->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-sm-4 text-sm-end">
                        <span class="text-muted small d-block">Payout Amount:</span>
                        <h3 class="mb-0 text-success fw-bold">₹{{ number_format($commission->calculated_amount, 2) }}</h3>
                    </div>
                </div>

                <!-- Payment Form -->
                <form action="{{ route('admin.commissions.process-payment', $commission) }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                <option value="BANK_TRANSFER" {{ old('payment_method') === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer (NEFT / RTGS / IMPS)</option>
                                <option value="UPI" {{ old('payment_method') === 'UPI' ? 'selected' : '' }}>UPI / Digital Payment</option>
                                <option value="CHEQUE" {{ old('payment_method') === 'CHEQUE' ? 'selected' : '' }}>Cheque / DD</option>
                                <option value="CASH" {{ old('payment_method') === 'CASH' ? 'selected' : '' }}>Cash</option>
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Reference / Transaction Number</label>
                            <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror"
                                   placeholder="e.g. UTR12345678, CHQ#987654" value="{{ old('reference_number') }}">
                            <div class="form-text">Bank transaction UTR, cheque number, or external payment voucher ID.</div>
                            @error('reference_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Notes / Remarks</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3"
                                      placeholder="Additional payment disbursement notes...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <a href="{{ route('admin.commissions.show', $commission) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-success px-4"
                                data-confirm="Confirm disbursement of ₹{{ number_format($commission->calculated_amount, 2) }} to {{ $commission->agent->name }}? This action cannot be reversed."
                                data-confirm-title="Confirm Commission Disbursement"
                                data-confirm-type="success"
                                data-confirm-btn="Disburse Payout">
                            <i class="bi bi-check2-circle me-1"></i> Confirm & Disburse Payout
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
