@extends('superadmin.layouts.app')

@section('page-title', 'Add Agent')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Add Agent</h1>
        <div class="page-breadcrumb">People / <a href="{{ route('admin.agents.index') }}" style="color:inherit;">Agents</a> / New</div>
    </div>
    <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Agents
    </a>
</div>

<form method="POST" action="{{ route('admin.agents.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
        <!-- Section 1: Basic Information -->
        <div class="col-lg-6">
            <div class="card h-100 border">
                <div class="card-header bg-white py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-primary"></i>
                        <span class="fw-semibold">Personal & Contact Details</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Profile Photo with Crop & Adjust -->
                    <x-profile-image-cropper name="profile_image" label="Profile Photo" hint="Upload and crop photo. Adjust zoom or rotate to center the face." />

                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-12">
                            <label for="name" class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g. Suresh Kumar" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Agent ID / Code -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="agent_id" class="form-label fw-medium mb-0">Agent ID / Code</label>
                                <span class="badge bg-light text-muted border" style="font-size: 0.7rem; font-weight: 500;">Auto-generated if blank</span>
                            </div>
                            <div class="input-group">
                                <input type="text" id="agent_id" name="agent_id"
                                       class="form-control font-monospace @error('agent_id') is-invalid @enderror"
                                       value="{{ old('agent_id') }}" placeholder="Auto-generated from joining date (e.g. PAG11261)">
                                <button type="button" class="btn btn-outline-secondary" id="btn_generate_agent_id" title="Generate next unique Agent ID">
                                    <i class="bi bi-arrow-repeat me-1"></i> Auto Generate
                                </button>
                            </div>
                            <div class="form-text text-muted" style="font-size:0.75rem;">Format: PAG + MM + YY + Seq (e.g. PAG11261). Auto-generated on save if left blank.</div>
                            @error('agent_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <!-- Joining Date -->
                        <div class="col-md-6">
                            <label for="joining_date" class="form-label fw-medium">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" id="joining_date" name="joining_date"
                                   class="form-control @error('joining_date') is-invalid @enderror"
                                   value="{{ old('joining_date', date('Y-m-d')) }}" required>
                            @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Email -->
                        <div class="col-12">
                            <label for="email" class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="agent@example.com" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Mobile -->
                        <div class="col-12">
                            <label for="phone" class="form-label fw-medium">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" id="phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="+91 9876543210" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Place / Operating Area -->
                        <div class="col-md-6">
                            <label for="place" class="form-label fw-medium">Place / Operating Area</label>
                            <input type="text" id="place" name="place"
                                   class="form-control @error('place') is-invalid @enderror"
                                   value="{{ old('place') }}" placeholder="e.g. City, Town, or Market Area">
                            <div class="form-text text-muted" style="font-size:0.75rem;">Field operating location (agents are free to work anywhere).</div>
                            @error('place')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Address -->
                        <div class="col-md-6">
                            <label for="address" class="form-label fw-medium">Full Address</label>
                            <input type="text" id="address" name="address"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address') }}" placeholder="Residential or contact address">
                            <div class="form-text text-muted" style="font-size:0.75rem;">Optional street or mailing address.</div>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Temporary Login Password with Auto-Generation -->
                        <div class="col-12">
                            <x-password-generator-input
                                name="password"
                                id="agent_password"
                                label="Temporary Login Password"
                                placeholder="Leave blank to auto-generate & email credentials"
                                hint="For temporary or initial use. If left blank, a secure password will be auto-generated and emailed to the agent."
                                checkboxLabel="Send Welcome Email with credentials to agent's email"
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
                            <label for="rank_id" class="form-label fw-medium">Designation / Rank <span class="text-danger">*</span></label>
                            <select id="rank_id" name="rank_id"
                                    class="form-select @error('rank_id') is-invalid @enderror" required>
                                <option value="">— Select Rank —</option>
                                @foreach($ranks as $rank)
                                    <option value="{{ $rank->id }}" {{ old('rank_id') == $rank->id ? 'selected' : '' }}>
                                        {{ $rank->name }} (Level {{ $rank->level }})
                                    </option>
                                @endforeach
                            </select>
                            @error('rank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Status -->
                        <div class="col-12">
                            <label for="status" class="form-label fw-medium">Initial Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                            <i class="bi bi-check-lg me-1"></i> Save Agent
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btn_generate_agent_id');
    const input = document.getElementById('agent_id');
    const nameInput = document.getElementById('name');
    const joiningDateInput = document.getElementById('joining_date');

    let isCustom = input && input.value.trim() !== '' && !/^PAG\d+$/i.test(input.value.trim());

    function localCompute(dateStr) {
        let d = dateStr ? new Date(dateStr) : new Date();
        if (isNaN(d.getTime())) d = new Date();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yy = String(d.getFullYear()).slice(-2);
        return 'PAG' + mm + yy + '1';
    }

    function fetchNextId(force = false) {
        const nameVal = nameInput ? nameInput.value.trim() : '';
        const dateVal = joiningDateInput ? joiningDateInput.value : '';

        if (!force && isCustom) {
            return;
        }

        const preview = localCompute(dateVal);
        if (preview && (!isCustom || force)) {
            if (!input.value.trim() || force || /^PAG\d+$/i.test(input.value.trim())) {
                input.value = preview;
                input.classList.remove('is-invalid');
            }
        }

        const params = new URLSearchParams();
        if (nameVal) params.append('name', nameVal);
        if (dateVal) params.append('joining_date', dateVal);

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Generating...';
        }

        fetch('{{ route("admin.agents.next-id") }}?' + params.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.agent_id && input && (!isCustom || force)) {
                input.value = data.agent_id;
                input.classList.remove('is-invalid');
                input.placeholder = data.agent_id;
            }
        })
        .catch(() => {})
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Auto Generate';
            }
        });
    }

    if (input) {
        input.addEventListener('input', function () {
            isCustom = input.value.trim() !== '' && !/^PAG\d+$/i.test(input.value.trim());
        });
    }

    if (btn) {
        btn.addEventListener('click', function () {
            isCustom = false;
            fetchNextId(true);
        });
    }

    if (joiningDateInput) {
        joiningDateInput.addEventListener('change', function () {
            if (!isCustom) {
                fetchNextId(false);
            }
        });
    }

    if (input && (!input.value.trim() || /^AG/i.test(input.value.trim()))) {
        fetchNextId(false);
    }
});
</script>
@endpush


