@extends('superadmin.layouts.app')

@section('page-title', 'Add Branch Admin')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Branch Admin</h1>
        <div class="page-breadcrumb">Branch & Details / <a href="{{ route('superadmin.branch-admins.index') }}" style="color:inherit;">Branch Admins</a> / New</div>
    </div>
    <a href="{{ route('superadmin.branch-admins.index') }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Admin Information</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('superadmin.branch-admins.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="mobile" class="form-label">Mobile</label>
                            <input type="text" id="mobile" name="mobile"
                                   class="form-control @error('mobile') is-invalid @enderror"
                                   value="{{ old('mobile') }}" placeholder="+91 9876543210">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="branch_id" class="form-label">Assign Branch <span class="text-danger">*</span></label>
                            <select id="branch_id" name="branch_id"
                                    class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select branch…</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Branch Login Note & Welcome Email -->
                        <div class="col-12">
                            <div class="p-3 rounded border bg-light">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-shield-check text-success fs-5"></i>
                                    <span class="fw-semibold text-dark">Branch Login Credentials</span>
                                </div>
                                <p class="text-muted small mb-2">
                                    Branch Admins authenticate using the assigned branch's login password. They can sign in at the login page using either their email address or the assigned branch code.
                                </p>
                                <div class="form-check mt-2 pt-2 border-top">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="send_welcome_email"
                                           id="send_welcome_email"
                                           value="1"
                                           {{ old('send_welcome_email', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-medium text-dark" for="send_welcome_email">
                                        <i class="bi bi-envelope-check text-primary me-1"></i>
                                        Send Welcome Email with login details to admin email
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i> Create Admin
                        </button>
                        <a href="{{ route('superadmin.branch-admins.index') }}" class="btn btn-outline-dark">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
