@extends('superadmin.layouts.app')

@section('page-title', 'Add Staff Position')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Staff Position</h1>
        <div class="page-breadcrumb">Branch & Details / <a href="{{ route('superadmin.staff-positions.index') }}" style="color:inherit;">Staff Positions</a> / New</div>
    </div>
    <a href="{{ route('superadmin.staff-positions.index') }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Position Details</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('superadmin.staff-positions.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Position Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g. Senior Officer" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Optional description of this position">{{ old('description') }}</textarea>
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
                            <i class="bi bi-check-lg me-1"></i> Create Position
                        </button>
                        <a href="{{ route('superadmin.staff-positions.index') }}" class="btn btn-outline-dark">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Example Positions</div>
            <div class="card-body p-3">
                <div class="text-xs text-muted mb-2">Common staff positions:</div>
                <ul class="text-xs text-secondary ps-3 mb-0 lh-lg">
                    <li>Junior Officer</li>
                    <li>Branch Officer</li>
                    <li>Senior Officer</li>
                    <li>Manager</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
