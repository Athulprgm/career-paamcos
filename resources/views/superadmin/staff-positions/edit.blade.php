@extends('superadmin.layouts.app')

@section('page-title', 'Edit Staff Position')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Staff Position</h1>
        <div class="page-breadcrumb">Branch & Details / <a href="{{ route('superadmin.staff-positions.index') }}" style="color:inherit;">Staff Positions</a> / Edit</div>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('superadmin.staff-positions.destroy', $staffPosition) }}" method="POST"
              data-confirm="Are you sure you want to permanently delete staff position '{{ $staffPosition->name }}'? This action cannot be undone."
              data-confirm-title="Delete Staff Position"
              data-confirm-type="danger"
              data-confirm-btn="Delete Position">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>
        <a href="{{ route('superadmin.staff-positions.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Position Details</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('superadmin.staff-positions.update', $staffPosition) }}">
                    @csrf @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Position Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $staffPosition->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="sort_order" class="form-label">Level / Order <span class="text-danger">*</span></label>
                            <input type="number" id="sort_order" name="sort_order"
                                   class="form-control @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $staffPosition->sort_order) }}" min="0" required>
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $staffPosition->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="status" name="status"
                                    class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active"   {{ old('status', $staffPosition->status) === 'active'   ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $staffPosition->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                        <a href="{{ route('superadmin.staff-positions.index') }}" class="btn btn-outline-dark">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
