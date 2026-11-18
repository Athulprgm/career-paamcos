@extends('superadmin.layouts.app')

@section('page-title', 'Add Branch')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Branch</h1>
        <div class="page-breadcrumb">Branch & Details / <a href="{{ route('superadmin.branches.index') }}" style="color:inherit;">Branches</a> / New</div>
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
                <form method="POST" action="{{ route('superadmin.branches.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g. Main Central Branch" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="code" class="form-label">Branch Code <span class="text-danger">*</span></label>
                            <input type="text" id="code" name="code"
                                   class="form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code') }}" placeholder="BR001" required maxlength="30">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea id="address" name="address" rows="2"
                                      class="form-control @error('address') is-invalid @enderror"
                                      placeholder="Full branch address">{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" id="phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="+91 9876543210">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="branch@example.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:0.75rem;">Used for login and branch notifications.</div>
                        </div>

                        <div class="col-md-6">
                            <x-password-generator-input
                                name="password"
                                id="branch_password"
                                label="Temporary Login Password"
                                placeholder="Leave blank to auto-generate & email"
                                hint="For temporary or initial login via Branch Code or Email. Auto-generated if left blank."
                                checkboxLabel="Send Welcome Email with credentials to branch email"
                            />
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active"
                                       name="is_active" value="1"
                                       {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i> Create Branch
                        </button>
                        <a href="{{ route('superadmin.branches.index') }}" class="btn btn-outline-dark">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
