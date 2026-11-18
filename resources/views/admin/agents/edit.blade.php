@extends('superadmin.layouts.app')

@section('page-title', 'Edit Agent — ' . $agent->name)

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Edit Agent</h1>
        <div class="page-breadcrumb">People / <a href="{{ route('admin.agents.index') }}" style="color:inherit;">Agents</a> / {{ $agent->name }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.agents.show', $agent) }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-eye me-1"></i> View Profile
        </a>
        <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<form method="POST" action="{{ route('admin.agents.update', $agent) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <!-- Section 1: Basic Information -->
        <div class="col-lg-6">
            <div class="card h-100 border">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-primary"></i>
                        <span class="fw-semibold">Personal & Contact Details</span>
                    </div>
                    @if($agent->status === 'active')
                        <span class="badge-active">Active</span>
                    @elseif($agent->status === 'pending')
                        <span class="badge-pending">Pending</span>
                    @elseif($agent->status === 'rejected')
                        <span class="badge-inactive">Rejected</span>
                    @else
                        <span class="badge-inactive">Inactive</span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <!-- Profile Photo with Crop & Adjust -->
                    <x-profile-image-cropper name="profile_image" :value="$agent->profile_image_url" label="Profile Photo" hint="Current photo shown. Upload a new photo, or crop and adjust." />

                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-12">
                            <label for="name" class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $agent->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Agent ID / Code -->
                        <div class="col-md-6">
                            <label for="agent_id" class="form-label fw-medium">Agent ID / Code <span class="text-danger">*</span></label>
                            <input type="text" id="agent_id" name="agent_id"
                                   class="form-control font-monospace @error('agent_id') is-invalid @enderror"
                                   value="{{ old('agent_id', $agent->agent_id) }}" required>
                            @error('agent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Joining Date -->
                        <div class="col-md-6">
                            <label for="joining_date" class="form-label fw-medium">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" id="joining_date" name="joining_date"
                                   class="form-control @error('joining_date') is-invalid @enderror"
                                   value="{{ old('joining_date', $agent->joining_date ? $agent->joining_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                            @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Email -->
                        <div class="col-12">
                            <label for="email" class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $agent->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Mobile -->
                        <div class="col-12">
                            <label for="phone" class="form-label fw-medium">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" id="phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $agent->phone) }}" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Place / Operating Area -->
                        <div class="col-md-6">
                            <label for="place" class="form-label fw-medium">Place / Operating Area</label>
                            <input type="text" id="place" name="place"
                                   class="form-control @error('place') is-invalid @enderror"
                                   value="{{ old('place', $agent->place) }}" placeholder="e.g. City, Town, or Market Area">
                            <div class="form-text text-muted" style="font-size:0.75rem;">Field operating location (agents are free to work anywhere).</div>
                            @error('place')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Address -->
                        <div class="col-md-6">
                            <label for="address" class="form-label fw-medium">Full Address</label>
                            <input type="text" id="address" name="address"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address', $agent->address) }}" placeholder="Residential or contact address">
                            <div class="form-text text-muted" style="font-size:0.75rem;">Contact or mailing address.</div>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Reset Password with Auto-Generation -->
                        <div class="col-12">
                            <x-password-generator-input
                                name="password"
                                id="agent_edit_password"
                                label="Reset Agent Password"
                                placeholder="Leave blank to keep existing password"
                                hint="Leave empty to retain current password, or auto-generate/enter a new one."
                                checkboxLabel="Send updated credentials email to agent"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Organization & Hierarchy -->
        <div class="col-lg-6">
            <div class="card h-100 border">
                <div class="card-header bg-white py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-diagram-3 text-primary"></i>
                            <span class="fw-semibold">Role & Hierarchy Assignment</span>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small fw-semibold"><i class="bi bi-geo-alt me-1"></i>Free Field Worker</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <!-- Agent Rank -->
                        <div class="col-12">
                            <label for="rank_id" class="form-label fw-medium">
                                Designation / Rank <span class="text-danger">*</span>
                                @if(auth()->user()->isSuperAdmin())
                                    <span class="badge bg-danger-subtle text-danger border ms-1" style="font-size:0.7rem;">Super Admin Adjustment</span>
                                @endif
                            </label>
                            @if(auth()->user()->isSuperAdmin())
                                <select id="rank_id" name="rank_id"
                                        class="form-select @error('rank_id') is-invalid @enderror" required>
                                    @foreach($ranks as $rank)
                                        <option value="{{ $rank->id }}" {{ old('rank_id', $agent->rank_id) == $rank->id ? 'selected' : '' }}>
                                            {{ $rank->name }} (Level {{ $rank->level }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted" style="font-size:0.75rem;">Direct changes log an adjustment audit record.</div>
                            @else
                                <input type="hidden" name="rank_id" value="{{ $agent->rank_id }}">
                                <input type="text" class="form-control bg-light" value="{{ $agent->rank?->name ?? 'Rank' }} (Level {{ $agent->rank?->level ?? '1' }})" readonly>
                                <div class="form-text text-muted" style="font-size:0.75rem;">Designation changes must be processed through Promotion Requests.</div>
                            @endif
                            @error('rank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Status -->
                        <div class="col-12">
                            <label for="status" class="form-label fw-medium">Account Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $agent->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $agent->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="pending" {{ old('status', $agent->status) === 'pending' ? 'selected' : '' }}>Pending Verification</option>
                                <option value="rejected" {{ old('status', $agent->status) === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="col-12">
            <div class="card border p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Fields marked with <span class="text-danger">*</span> are required.
                    </span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-dark">Cancel</a>
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i> Update Agent
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection


