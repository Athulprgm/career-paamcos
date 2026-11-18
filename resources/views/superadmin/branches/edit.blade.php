@extends('superadmin.layouts.app')

@section('page-title', 'Edit Branch')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Branch</h1>
        <div class="page-breadcrumb">Branch & Details / <a href="{{ route('superadmin.branches.index') }}" style="color:inherit;">Branches</a> / Edit</div>
    </div>
    <a href="{{ route('superadmin.branches.index') }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Branch Information</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('superadmin.branches.update', $branch) }}">
                    @csrf @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $branch->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="code" class="form-label">Branch Code <span class="text-danger">*</span></label>
                            <input type="text" id="code" name="code"
                                   class="form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code', $branch->code) }}" required maxlength="30">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea id="address" name="address" rows="2"
                                      class="form-control @error('address') is-invalid @enderror">{{ old('address', $branch->address) }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" id="phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $branch->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $branch->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:0.75rem;">Used for login and branch notifications.</div>
                        </div>

                        <div class="col-md-6">
                            <x-password-generator-input
                                name="password"
                                id="branch_edit_password"
                                label="Change Branch Password"
                                placeholder="Leave blank to keep current password"
                                hint="Leave blank to retain current password, or auto-generate a new one."
                                checkboxLabel="Send updated credentials email to branch email"
                            />
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active"
                                       name="is_active" value="1"
                                       {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                        <a href="{{ route('superadmin.branches.index') }}" class="btn btn-outline-dark">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info Panel -->
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">Branch Info</div>
            <div class="card-body p-3">
                <div class="text-xs text-muted mb-1 font-medium text-uppercase">Created</div>
                <div class="fs-13 fw-semibold text-dark mb-3 font-mono">{{ $branch->created_at->format('d M Y') }}</div>
                <div class="text-xs text-muted mb-1 font-medium text-uppercase">Last Updated</div>
                <div class="fs-13 fw-semibold text-dark font-mono">{{ $branch->updated_at->format('d M Y') }}</div>
            </div>
        </div>
    </div>
</div>

@endsection
