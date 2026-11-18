@extends('superadmin.layouts.app')

@section('page-title', 'Add Agent Rank')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Agent Rank</h1>
        <div class="page-breadcrumb">Branch & Details / <a href="{{ route('superadmin.agent-ranks.index') }}" style="color:inherit;">Agent Ranks</a> / New</div>
    </div>
    <a href="{{ route('superadmin.agent-ranks.index') }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Rank Details</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('superadmin.agent-ranks.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Rank Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g. Field Agent" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="sort_order" class="form-label">Level / Order <span class="text-danger">*</span></label>
                            <input type="number" id="sort_order" name="sort_order"
                                   class="form-control @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', 1) }}" min="0" required>
                            <div class="form-text">Lower = entry level</div>
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-12">
                            <label for="minimum_deposit_amount" class="form-label">Base Deposit Target (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">₹</span>
                                <input type="number" step="0.01" min="0" id="minimum_deposit_amount" name="minimum_deposit_amount"
                                       class="form-control @error('minimum_deposit_amount') is-invalid @enderror"
                                       value="{{ old('minimum_deposit_amount', '300000.00') }}" placeholder="e.g. 300000.00">
                            </div>
                            <div class="form-text">Baseline deposit target required for this rank. Used as default for Financial Year targets &amp; career advancement rules.</div>
                            @error('minimum_deposit_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Optional description of this agent rank">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="status" name="status"
                                    class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i> Create Rank
                        </button>
                        <a href="{{ route('superadmin.agent-ranks.index') }}" class="btn btn-outline-dark">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Example Ranks</div>
            <div class="card-body p-3">
                <div class="text-xs text-muted mb-2">Common agent ranks:</div>
                <ul class="text-xs text-secondary ps-3 mb-0 lh-lg">
                    <li>Field Agent (Level 1)</li>
                    <li>Senior Agent (Level 2)</li>
                    <li>Team Leader (Level 3)</li>
                    <li>Regional Manager (Level 4)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
