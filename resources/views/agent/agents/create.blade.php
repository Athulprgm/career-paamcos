@extends('superadmin.layouts.app')

@section('page-title', 'Add Downline Agent')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-sm-center align-items-start gap-2 mb-3 mb-md-4">
    <div class="min-w-0">
        <h1 class="page-title mb-1 text-truncate">Add Downline Agent</h1>
        <div class="page-breadcrumb text-muted text-break">
            <a href="{{ route('agent.agents.index') }}" class="text-decoration-none">My Team</a> /
            <span class="text-dark fw-semibold">New Agent</span>
        </div>
    </div>
    <div class="w-100 w-sm-auto">
        <a href="{{ route('agent.agents.index') }}" class="btn btn-outline-dark btn-sm w-100 w-sm-auto text-nowrap">
            <i class="bi bi-arrow-left me-1"></i> Back to My Team
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9 col-xl-8">
        <!-- Sponsor Context Card -->
        <div class="card border mb-3 mb-md-4 bg-light">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">
                    <div class="bg-dark text-white p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="bi bi-diagram-3-fill fs-5"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark text-truncate">Network Sponsorship & Hierarchy Rule</div>
                        <div class="small text-muted text-break">
                            Sponsoring Agent: <strong class="text-dark">{{ $currentAgent->name }}</strong>
                            (<span class="font-monospace">{{ $currentAgent->member_code ?? $currentAgent->agent_id }}</span>)
                            &bull; Your Rank: <strong class="text-dark">{{ $currentAgent->rank?->name ?? 'Agent' }} (Level {{ $currentAgent->rank?->level ?? 1 }})</strong>
                            &bull; Branch: <strong class="text-dark">{{ $currentAgent->branch?->name ?? 'Free Field' }}</strong>
                        </div>
                        <div class="small text-primary mt-1 text-break">
                            <i class="bi bi-shield-lock me-1"></i>
                            You can recruit agents with the <strong>same position</strong> or <strong>down-priority positions</strong> (Level {{ $maxAllowedLevel }} or lower).
                            Agents are free-field agents operating anywhere; branch assignment is optional.
                        </div>
                    </div>
                    <span class="badge bg-dark align-self-start align-self-sm-center text-nowrap">Direct Downline</span>
                </div>
            </div>
        </div>

        <!-- Add Agent Form Card -->
        <div class="card border">
            <div class="card-header bg-white py-3">
                <h6 class="card-title m-0 fw-bold">New Agent Registration Details</h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="{{ route('agent.agents.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <!-- Profile Photo with Crop & Adjust -->
                        <div class="col-12">
                            <x-profile-image-cropper name="profile_image" label="Agent Profile Photo" hint="Upload a photo. Use the built-in crop and adjust tool to fit the profile badge." />
                        </div>

                        <!-- Full Name -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g. Ramesh Patel" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Agent ID / Member Code -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="agent_id" class="form-label fw-semibold mb-0">Agent ID / Code</label>
                                <span class="badge bg-light text-muted border" style="font-size: 0.7rem; font-weight: 500;">Auto-generated if blank</span>
                            </div>
                            <div class="input-group">
                                <input type="text" id="agent_id" name="agent_id" class="form-control font-monospace @error('agent_id') is-invalid @enderror"
                                       value="{{ old('agent_id') }}" placeholder="Auto-generated from joining date (e.g. PAG11261)">
                                <button type="button" class="btn btn-outline-secondary" id="btn_generate_agent_id" title="Generate next unique Agent ID">
                                    <i class="bi bi-arrow-repeat me-1"></i> Auto Generate
                                </button>
                            </div>
                            <div class="form-text small">Format: PAG + MM + YY + Seq (e.g. PAG11261). Auto-generated on save if left blank.</div>
                            @error('agent_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="agent@example.com" required>
                            <div class="form-text small">Used for agent portal login credentials.</div>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Mobile Number -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="+91 9876543210" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Place / Operating Area -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Place / Operating Area</label>
                            <input type="text" name="place" class="form-control @error('place') is-invalid @enderror"
                                   value="{{ old('place') }}" placeholder="e.g. City, Town, or Market Area">
                            <div class="form-text small">Locality or field area where agent will operate.</div>
                            @error('place')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Initial Rank -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Initial Rank (Same or Down-Priority Position)</label>
                            <select name="rank_id" class="form-select @error('rank_id') is-invalid @enderror">
                                @foreach($ranks as $rank)
                                    <option value="{{ $rank->id }}" {{ (string) old('rank_id', $defaultRank?->id) === (string) $rank->id ? 'selected' : '' }}>
                                        {{ $rank->name }} (Level {{ $rank->level }})
                                        @if((int) $rank->level === (int) $maxAllowedLevel)
                                            — [Same Position]
                                        @else
                                            — [Down Priority]
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Only ranks at or below your position (Level {{ $maxAllowedLevel }}) can be assigned.</div>
                            @error('rank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Joining Date -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Joining Date</label>
                            <input type="date" name="joining_date" class="form-control @error('joining_date') is-invalid @enderror"
                                   value="{{ old('joining_date', date('Y-m-d')) }}">
                            @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Login Password / Manual Entry or Auto-Generation -->
                        <div class="col-12">
                            <x-password-generator-input
                                name="password"
                                id="agent_password"
                                label="Login Password"
                                placeholder="Enter password manually or click Auto-Generate"
                                hint="Create password manually (minimum 6 characters) or leave blank to auto-generate & email credentials to the agent."
                                checkboxLabel="Send Welcome Email with credentials to agent's email"
                            />
                        </div>

                        <!-- Residential / Office Address -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Address / Contact Location</label>
                            <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror"
                                      placeholder="Residential or contact address...">{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-3 mt-4 pt-3 border-top">
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i> Requires Super Admin verification & acceptance.
                        </div>
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <a href="{{ route('agent.agents.index') }}" class="btn btn-outline-dark flex-fill flex-sm-grow-0 text-nowrap">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-dark flex-fill flex-sm-grow-0 text-nowrap">
                                <i class="bi bi-person-check me-1"></i> Register & Submit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btn_generate_agent_id');
    const input = document.getElementById('agent_id');
    const nameInput = document.querySelector('input[name="name"]');
    const joiningDateInput = document.querySelector('input[name="joining_date"]');

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

        fetch('{{ route("agent.agents.next-id") }}?' + params.toString(), {
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
