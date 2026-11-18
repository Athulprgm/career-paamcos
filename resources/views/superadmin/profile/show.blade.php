@extends('superadmin.layouts.app')

@section('page-title', 'Profile Settings')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Profile Settings</h1>
        <div class="page-breadcrumb">Settings / Account Profile</div>
    </div>
    <div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 rounded-pill fw-medium">
            <i class="bi bi-shield-check me-1"></i>
            {{ $user->roles->pluck('name')->implode(', ') ?: 'Super Administrator' }}
        </span>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4 py-2.5 px-3 rounded-3 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill text-success fs-5"></i>
    <div class="flex-grow-1 small fw-medium">{{ session('success') }}</div>
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4 py-2.5 px-3 rounded-3 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
    <div class="flex-grow-1 small fw-medium">{{ session('error') }}</div>
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-4 py-2.5 px-3 rounded-3 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2 mb-1 fw-semibold small">
        <i class="bi bi-exclamation-octagon-fill text-danger"></i>
        <span>Please correct the errors below:</span>
    </div>
    <ul class="mb-0 ps-3 small">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row g-4">
    <!-- ─── Left Column: Account Details ──────────────────────────────────── -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2 py-3 px-4">
                <i class="bi bi-person-badge text-primary"></i>
                <span class="fw-semibold">Account Overview</span>
            </div>
            <div class="card-body p-4">
                <div class="text-center pb-4 mb-3 border-bottom">
                    <div class="position-relative d-inline-block mb-3">
                        @if($user->profile_image_url)
                            <img src="{{ $user->profile_image_url }}" alt="{{ $user->name }}"
                                 class="rounded-circle border border-2 shadow-sm object-fit-cover bg-white"
                                 style="width: 80px; height: 80px;" id="sideAvatarImg">
                        @else
                            <div class="rounded-circle shadow-sm d-flex align-items-center justify-content-center text-white fw-bold mx-auto"
                                 style="width: 80px; height: 80px; font-size: 1.6rem; background: var(--color-primary, #14532D);">
                                {{ strtoupper(substr($user->name ?? 'SA', 0, 2)) }}
                            </div>
                        @endif
                        <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle"
                              style="width: 14px; height: 14px;" title="Active"></span>
                    </div>

                    <h5 class="fw-bold text-heading mb-1 text-truncate" id="sideUserName">{{ $user->name }}</h5>
                    <div class="text-muted small text-truncate mb-2">{{ $user->email }}</div>

                    <span class="badge bg-dark text-white px-2.5 py-1 rounded-pill small">
                        {{ $user->roles->pluck('name')->implode(', ') ?: ($user->isSuperAdmin() ? 'Super Admin' : ($user->isBranchAdmin() ? 'Branch Admin' : 'Admin')) }}
                    </span>
                </div>

                <div class="d-flex flex-column gap-3 small">
                    <div>
                        <div class="text-muted mb-1">Branch Affiliation</div>
                        @if($user->isAgent())
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                <i class="bi bi-geo-alt me-1"></i>Free Field Worker (No Branch)
                            </span>
                            <div class="text-muted mt-1" style="font-size: 0.76rem;">Free worker &bull; operating across all branches</div>
                        @elseif($user->branch)
                            <div class="fw-semibold text-heading">
                                <i class="bi bi-building me-1 text-primary"></i>{{ $user->branch->name }}
                                <span class="text-muted font-monospace">({{ $user->branch->code }})</span>
                            </div>
                        @else
                            <div class="text-secondary">
                                <i class="bi bi-building me-1 text-primary"></i>Head Office / All Branches
                            </div>
                        @endif
                    </div>

                    @if($user->isAgent() && $user->agentProfile)
                    <div class="pt-2 border-top">
                        <div class="text-muted mb-1">Agent ID / Member Code</div>
                        <code class="px-2 py-0.5 bg-light border rounded text-primary fw-bold font-monospace">
                            {{ $user->agentProfile->agent_id ?: ($user->agentProfile->member_code ?: '—') }}
                        </code>
                    </div>
                    <div>
                        <div class="text-muted mb-1">Current Rank</div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                            <i class="bi bi-award me-1"></i>{{ $user->agentProfile->rank?->name ?? 'Agent' }} (Level {{ $user->agentProfile->rank?->level ?? 1 }})
                        </span>
                    </div>
                    @if($user->agentProfile->place)
                    <div>
                        <div class="text-muted mb-1">Operating Locality</div>
                        <span class="fw-medium text-heading"><i class="bi bi-geo-alt text-danger me-1"></i>{{ $user->agentProfile->place }}</span>
                    </div>
                    @endif
                    @endif

                    @if($user->isStaff() && $user->staffProfile)
                    <div class="pt-2 border-top">
                        <div class="text-muted mb-1">Employee ID</div>
                        <code class="px-2 py-0.5 bg-light border rounded text-dark fw-bold font-monospace">
                            {{ $user->staffProfile->employee_id ?: '—' }}
                        </code>
                    </div>
                    @if($user->staffProfile->position)
                    <div>
                        <div class="text-muted mb-1">Staff Position</div>
                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                            {{ $user->staffProfile->position->name }}
                        </span>
                    </div>
                    @endif
                    @endif

                    <div class="pt-3 border-top d-flex justify-content-between text-muted">
                        <span>Account Created:</span>
                        <strong class="text-heading">{{ $user->created_at->format('d M Y') }}</strong>
                    </div>

                    <div class="d-flex justify-content-between text-muted">
                        <span>Last Updated:</span>
                        <strong class="text-heading">{{ $user->updated_at->format('d M Y') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Right Column: Action Forms ────────────────────────────────────── -->
    <div class="col-lg-8 d-flex flex-column gap-4">

        <!-- 1. Profile Information Form -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square text-primary"></i>
                    <span class="fw-semibold">Edit Profile Information</span>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('superadmin.profile.update') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')

                    <div class="mb-4">
                        <x-profile-image-cropper
                            name="profile_image"
                            :value="$user->profile_image_url"
                            label="Profile Photo / Society Logo"
                            hint="Upload and crop photo. By default, Super Admin displays the official MultiCred logo."
                        />
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-medium text-heading small">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}"
                                   oninput="document.getElementById('sideUserName').innerText = this.value || 'User'"
                                   required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-medium text-heading small">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}"
                                   required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4 fw-medium shadow-sm">
                            <i class="bi bi-check2 me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Change Password Form -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-primary"></i>
                    <span class="fw-semibold">Update Password</span>
                </div>
                <span class="text-muted small">Minimum 8 characters</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('superadmin.profile.password') }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-medium text-heading small">
                            Current Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   placeholder="Enter current password"
                                   required>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePass('current_password', this)" title="Show / Hide Password">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-medium text-heading small">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" id="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Minimum 8 characters"
                                       required minlength="8">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePass('password', this)" title="Show / Hide Password">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label fw-medium text-heading small">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       class="form-control"
                                       placeholder="Confirm new password"
                                       required>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePass('password_confirmation', this)" title="Show / Hide Password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-dark px-4 fw-medium shadow-sm">
                            <i class="bi bi-lock me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}
</script>
@endsection
