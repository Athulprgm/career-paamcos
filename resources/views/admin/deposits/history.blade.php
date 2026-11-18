@extends('superadmin.layouts.app')

@section('page-title', 'Deposit Operations Audit Trail')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Deposit Operations Audit Trail</h1>
        <div class="page-breadcrumb">Deposit Operations / Activity & Audit Trail</div>
    </div>
    <a href="{{ route('admin.deposits.index') }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-wallet2 me-1"></i> Back to Deposit Accounts
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.deposits.history') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div style="min-width:240px;">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search transaction description, officer..." value="{{ request('search') }}">
            </div>

            <div style="min-width:200px;">
                <select name="event" class="form-select form-select-sm">
                    <option value="">All Action Events</option>
                    <option value="deposit_created" {{ request('event') === 'deposit_created' ? 'selected' : '' }}>Account Originated (Created)</option>
                    <option value="deposit_approved" {{ request('event') === 'deposit_approved' ? 'selected' : '' }}>Account Authorized (Approved)</option>
                    <option value="deposit_rejected" {{ request('event') === 'deposit_rejected' ? 'selected' : '' }}>Application Rejected</option>
                    <option value="deposit_activated" {{ request('event') === 'deposit_activated' ? 'selected' : '' }}>Account Activated</option>
                </select>
            </div>

            <button type="submit" class="btn btn-dark btn-sm">
                <i class="bi bi-search me-1"></i> Filter
            </button>

            @if(request()->hasAny(['search','event']))
                <a href="{{ route('admin.deposits.history') }}" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-x me-1"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:160px;">Transaction Date & Time</th>
                        <th style="width:170px;">Operation / Action Event</th>
                        <th style="width:150px;">Processing Officer</th>
                        <th>Activity Description & Details</th>
                        <th style="width:140px;" class="text-end">Principal (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="small text-muted">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $log->event }}</span>
                            </td>
                            <td class="fw-medium small">
                                {{ $log->user_name }}
                            </td>
                            <td class="small text-dark">{{ $log->description }}</td>
                            <td class="small text-end fw-bold num-tabular">
                                {{ $log->financial_amount ? '₹' . number_format($log->financial_amount, 2) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-clock-history fs-2 d-block mb-2 text-secondary"></i>
                                No deposit activity or audit entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white border-top py-2 px-3">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
