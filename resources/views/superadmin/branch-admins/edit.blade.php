@extends('superadmin.layouts.app')

@section('page-title', 'Edit Branch Admin')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Branch Admin</h1>
        <div class="page-breadcrumb">Branch & Details / <a href="{{ route('superadmin.branch-admins.index') }}" style="color:inherit;">Branch Admins</a> / Edit</div>
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
                <form method="POST" action="{{ route('superadmin.branch-admins.update', $branchAdmin) }}">
                    @csrf @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $branchAdmin->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="mobile" class="form-label">Mobile</label>
                            <input type="text" id="mobile" name="mobile"
                                   class="form-control @error('mobile') is-invalid @enderror"
                                   value="{{ old('mobile') }}">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $branchAdmin->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="branch_id" class="form-label">Assign Branch <span class="text-danger">*</span></label>
                            <select id="branch_id" name="branch_id"
                                    class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select branch…</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ old('branch_id', $branchAdmin->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Branch Login Credentials Note -->
                        <div class="col-12">
                            <div class="p-3 rounded border bg-light">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi bi-shield-check text-success fs-5"></i>
                                    <span class="fw-semibold text-dark">Branch Login Authentication</span>
                                </div>
                                <p class="text-muted small mb-0">
                                    This Branch Admin uses the assigned branch's login password. To update the login password, please update the branch password in <a href="{{ route('superadmin.branches.index') }}" class="text-decoration-underline text-dark fw-medium">Branch Management</a>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                        <a href="{{ route('superadmin.branch-admins.index') }}" class="btn btn-outline-dark">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
