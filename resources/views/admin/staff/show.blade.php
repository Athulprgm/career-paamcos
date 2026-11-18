@extends('superadmin.layouts.app')

@section('page-title', 'Staff Profile — ' . $staff->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $staff->name }}</h1>
        <div class="page-breadcrumb">People / <a href="{{ route('admin.staff.index') }}" style="color:inherit;">Staff</a> / Profile</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.staff.edit', $staff) }}" class="btn btn-dark btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit Profile
        </a>
        <button type="button"
                class="btn btn-sm {{ $staff->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}"
                data-bs-toggle="modal" data-bs-target="#toggleStaffStatusModal">
            <i class="bi {{ $staff->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }} me-1"></i>
            {{ $staff->status === 'active' ? 'Deactivate' : 'Activate' }}
        </button>
        <button type="button"
                class="btn btn-sm btn-outline-danger"
                data-bs-toggle="modal" data-bs-target="#deleteStaffModal">
            <i class="bi bi-trash me-1"></i> Delete
        </button>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Staff Profile Card -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center p-4">
                <div class="mx-auto mb-3" style="width:72px;height:72px;background:var(--color-deep-green, #14532D);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:600;">
                    {{ strtoupper(substr($staff->name, 0, 1)) }}
                </div>
                <h5 class="mb-1">{{ $staff->name }}</h5>
                <div class="text-muted mb-2 font-monospace num-tabular" style="font-size:0.85rem;">{{ $staff->employee_id }}</div>

                <div class="mb-3">
                    @if($staff->status === 'active')
                        <span class="badge-active">Active</span>
                    @else
                        <span class="badge-inactive">Inactive</span>
                    @endif
                </div>

                <div class="border-top pt-3 text-start" style="font-size:0.825rem;">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-envelope me-2"></i>Email</span>
                        <span class="fw-medium">{{ $staff->email }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-phone me-2"></i>Mobile</span>
                        <span class="fw-medium num-tabular">{{ $staff->phone }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-calendar3 me-2"></i>Joining Date</span>
                        <span class="fw-medium num-tabular">{{ $staff->joining_date ? $staff->joining_date->format('d M Y') : '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted"><i class="bi bi-clock-history me-2"></i>Registered</span>
                        <span class="text-muted num-tabular">{{ $staff->created_at ? $staff->created_at->format('d M Y') : '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hierarchy Context -->
        <div class="card mb-3">
            <div class="card-header">Branch & Role</div>
            <div class="card-body p-3" style="font-size:0.85rem;">
                <div class="mb-3">
                    <div class="text-muted" style="font-size:0.75rem;">ASSIGNED BRANCH</div>
                    <div class="fw-semibold mt-1">
                        @if($staff->branch)
                            <i class="bi bi-building me-1"></i>{{ $staff->branch->name }}
                            <span class="text-muted font-monospace" style="font-size:0.75rem;">({{ $staff->branch->code }})</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <div class="text-muted" style="font-size:0.75rem;">POSITION</div>
                    <div class="fw-semibold mt-1">
                        @if($staff->position)
                            <span class="badge bg-dark text-white fw-normal px-2 py-1">{{ $staff->position->name }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="text-muted" style="font-size:0.75rem;">REPORTING MANAGER</div>
                    <div class="mt-1">
                        @if($staff->reportingManager)
                            <a href="{{ route('admin.staff.show', $staff->reportingManager) }}" class="text-decoration-none fw-semibold text-dark">
                                <i class="bi bi-person-check me-1"></i>{{ $staff->reportingManager->name }}
                            </a>
                            <div class="text-muted font-monospace" style="font-size:0.75rem;">
                                {{ $staff->reportingManager->employee_id }} — {{ $staff->reportingManager->position->name ?? 'Staff' }}
                            </div>
                        @else
                            <span class="text-muted">None (Direct Branch / Operational Head)</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- System User & Permissions -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>System Access & Permissions</span>
                <span class="badge bg-primary">{{ strtoupper($userAccount->role ?? $staff->role ?? 'STAFF') }}</span>
            </div>
            <div class="card-body p-3">
                <div class="mb-2 text-muted small">
                    <i class="bi bi-person-lock me-1"></i> User Login: <strong class="text-dark">{{ $userAccount?->email ?? $staff->email }}</strong>
                </div>
                <div class="mb-3 text-muted small">
                    <i class="bi bi-shield-check me-1"></i> Auth Status:
                    @if(($userAccount?->status ?? $staff->status) === 'active')
                        <span class="badge bg-success">Active & Enabled</span>
                    @else
                        <span class="badge bg-danger">Disabled / Suspended</span>
                    @endif
                </div>

                <div class="border-top pt-2">
                    <span class="text-muted small fw-semibold d-block mb-2">Granted Operational Permissions:</span>
                    <div class="d-flex flex-wrap gap-1">
                        @php
                            $permsToShow = $assignedPermissions ?? (is_array($staff->permissions) ? $staff->permissions : ($staff->user?->permissions ?? []));
                        @endphp
                        @forelse($permsToShow as $perm)
                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem;">
                                <i class="bi bi-check2 text-success me-1"></i>{{ $perm }}
                            </span>
                        @empty
                            <span class="text-muted small">No special granular permissions assigned.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Information: Direct Reports, Agents & Audit Log -->
    <div class="col-lg-8">
        <!-- Direct Subordinates -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Direct Reports (Subordinate Staff)</span>
                <span class="badge bg-light text-dark border">{{ $staff->directReports->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staff->directReports as $subordinate)
                                <tr>
                                    <td class="font-monospace">{{ $subordinate->employee_id }}</td>
                                    <td class="fw-medium">{{ $subordinate->name }}</td>
                                    <td>{{ $subordinate->position->name ?? '—' }}</td>
                                    <td>
                                        @if($subordinate->status === 'active')
                                            <span class="badge-active">Active</span>
                                        @else
                                            <span class="badge-inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.staff.show', $subordinate) }}" class="btn btn-sm btn-outline-dark">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        No direct reporting staff members assigned.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reporting Agents -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Supervised Agents</span>
                <span class="badge bg-light text-dark border">{{ $staff->reportingAgents->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Agent ID</th>
                                <th>Name</th>
                                <th>Rank</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staff->reportingAgents as $agent)
                                <tr>
                                    <td class="font-monospace">{{ $agent->agent_id }}</td>
                                    <td class="fw-medium">{{ $agent->name }}</td>
                                    <td>{{ $agent->rank->name ?? '—' }}</td>
                                    <td>
                                        @if($agent->status === 'active')
                                            <span class="badge-active">Active</span>
                                        @else
                                            <span class="badge-inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.agents.show', $agent) }}" class="btn btn-sm btn-outline-dark">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        No field agents currently reporting to this staff member.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Activity Audit Logs -->
        @php
            $recentLogs = $recentLogs ?? $recentActivities ?? collect();
        @endphp
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-1"></i> Recent Activity Audit Trail</span>
                <span class="badge bg-light text-dark border">{{ $recentLogs->count() }} records</span>
            </div>
            <div class="card-body p-0">
                @if($recentLogs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.825rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Action</th>
                                <th>Record Ref</th>
                                <th>Timestamp</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentLogs as $log)
                            <tr>
                                <td>
                                    <span class="badge bg-dark font-monospace">{{ strtoupper($log->action ?? $log->event ?? 'LOG') }}</span>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark">{{ $log->record_reference ?? $log->auditable_type ?? $log->description ?? '—' }}</span>
                                </td>
                                <td class="text-muted">
                                    {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '—' }}
                                </td>
                                <td class="text-muted font-monospace">
                                    {{ $log->ip_address ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-3 small">
                    No recent audit activity records found for this staff member.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Custom Toggle Staff Status Confirmation Modal -->
<div class="modal fade" id="toggleStaffStatusModal" tabindex="-1" aria-labelledby="toggleStaffStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border" style="background: var(--card-bg);">
            <div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="border {{ $staff->status === 'active' ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-success-subtle text-success border-success-subtle' }}" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="bi {{ $staff->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="toggleStaffStatusModalLabel" style="font-size: 1.05rem;">
                            {{ $staff->status === 'active' ? 'Deactivate Staff Member' : 'Activate Staff Member' }}
                        </h5>
                        <p class="text-muted small mb-0">Confirm staff member status change</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="p-3 my-2 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Staff Member:</span>
                        <span class="fw-semibold text-dark text-end">{{ $staff->name }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Employee ID:</span>
                        <code class="bg-white border px-2 py-0.5 text-dark font-monospace num-tabular" style="font-size: 0.8rem;">{{ $staff->employee_id }}</code>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Branch:</span>
                        <span class="fw-semibold text-dark text-end">{{ $staff->branch->name ?? '—' }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">Position:</span>
                        <span class="badge bg-dark text-white fw-normal">{{ $staff->position->name ?? '—' }}</span>
                    </div>
                </div>
                <p class="small text-muted mb-0">
                    {{ $staff->status === 'active'
                        ? 'Deactivating this staff member will prevent them from accessing the system and managing operations until reactivated.'
                        : 'Activating this staff member will restore their operational capabilities and system login access.' }}
                </p>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex gap-2 justify-content-end bg-light">
                <button type="button" class="btn btn-outline-dark btn-sm px-3" data-bs-dismiss="modal">
                    Cancel
                </button>
                <form method="POST" action="{{ route('admin.staff.toggle-status', $staff) }}" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn {{ $staff->status === 'active' ? 'btn-warning text-dark' : 'btn-success' }} btn-sm px-3 d-flex align-items-center gap-1">
                        <i class="bi {{ $staff->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }} me-1"></i>
                        {{ $staff->status === 'active' ? 'Deactivate Staff' : 'Activate Staff' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Custom Delete Staff Member Confirmation Modal -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-labelledby="deleteStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border" style="background: var(--card-bg);">
            <div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="border border-danger-subtle bg-danger-subtle text-danger" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="bi bi-trash"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="deleteStaffModalLabel" style="font-size: 1.05rem;">Delete Staff Member</h5>
                        <p class="text-muted small mb-0">Record removal confirmation</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="p-3 my-2 border" style="background: var(--surface-subtle); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Staff Member:</span>
                        <span class="fw-semibold text-dark text-end">{{ $staff->name }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Employee ID:</span>
                        <code class="bg-white border px-2 py-0.5 text-dark font-monospace num-tabular" style="font-size: 0.8rem;">{{ $staff->employee_id }}</code>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Branch:</span>
                        <span class="fw-semibold text-dark text-end">{{ $staff->branch->name ?? '—' }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">Position:</span>
                        <span class="badge bg-dark text-white fw-normal">{{ $staff->position->name ?? '—' }}</span>
                    </div>
                </div>
                <div class="alert alert-warning py-2 px-3 small mb-0 border" style="background: #FEF3C7; color: #92400E;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    This action will soft-delete the staff profile, remove user account login credentials, and audit the removal. Subordinates or assigned agents must be reassigned prior to deletion.
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 d-flex gap-2 justify-content-end bg-light">
                <button type="button" class="btn btn-outline-dark btn-sm px-3" data-bs-dismiss="modal">
                    Cancel
                </button>
                <form method="POST" action="{{ route('admin.staff.destroy', $staff) }}" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm px-3 d-flex align-items-center gap-1">
                        <i class="bi bi-trash me-1"></i> Delete Staff
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
