@extends('superadmin.layouts.app')

@section('page-title', 'Branch Admins')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Branch Admins</h1>
        <div class="page-breadcrumb">Branch & Details / Branch Admins</div>
    </div>
    <a href="{{ route('superadmin.branch-admins.create') }}" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Admin
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <div class="flex-grow-1" style="min-width: 220px;">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search name or email…" value="{{ request('search') }}">
            </div>
            <div style="min-width: 180px;">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-dark btn-sm">
                <i class="bi bi-search me-1"></i> Filter
            </button>
            @if(request()->hasAny(['search','branch_id']))
                <a href="{{ route('superadmin.branch-admins.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x me-1"></i> Clear
                </a>
            @endif
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 45px;">#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Created At</th>
                        <th class="text-end" style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($admins as $admin)
                    <tr>
                        <td class="text-muted small">{{ $admins->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $admin->name }}</div>
                        </td>
                        <td class="small">{{ $admin->email }}</td>
                        <td class="text-muted small">
                            {{ $admin->created_at ? $admin->created_at->format('d M Y, h:i A') : '—' }}
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('superadmin.branch-admins.edit', $admin) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Edit Admin">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('superadmin.branch-admins.destroy', $admin) }}" method="POST"
                                      data-confirm="Are you sure you want to remove this Branch Admin?"
                                      data-confirm-title="Remove Branch Admin"
                                      data-confirm-type="danger"
                                      data-confirm-btn="Remove Admin">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove Admin">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-person-gear d-block mb-2 text-muted" style="font-size:2rem;"></i>
                            No branch admins found.
                            <a href="{{ route('superadmin.branch-admins.create') }}"
                               class="d-block mt-2 btn btn-dark btn-sm mx-auto" style="width:fit-content;">
                                Add First Admin
                            </a>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($admins->hasPages())
        <div class="card-footer bg-transparent border-top py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $admins->firstItem() }}–{{ $admins->lastItem() }} of {{ $admins->total() }} admins
                </div>
                {{ $admins->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>
@endsection
