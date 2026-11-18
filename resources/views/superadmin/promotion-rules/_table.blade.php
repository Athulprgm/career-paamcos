<div class="card border-0" style="background: var(--card-bg, #FFFFFF); border: 1px solid var(--border-color, #E2E8F0) !important; border-radius: 14px !important; overflow: hidden; box-shadow: var(--shadow-card);">
    <!-- Data Table Grid -->
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle promotion-rules-table" style="border-collapse: separate; border-spacing: 0;">
                <thead style="background: var(--surface-subtle); border-bottom: 1px solid var(--border-color);">
                    <tr>
                        <th scope="col" style="width: 44px; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; padding: 10px 14px;" class="ps-3">
                            #
                        </th>
                        <th scope="col" style="min-width: 240px; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; padding: 10px 14px;">
                            Career Advancement Path
                        </th>
                        <th scope="col" style="min-width: 170px; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; padding: 10px 14px;">
                            Team Target &amp; Ratio
                        </th>
                        <th scope="col" style="min-width: 120px; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; padding: 10px 14px;">
                            Personal Quota
                        </th>
                        <th scope="col" style="min-width: 140px; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; padding: 10px 14px;">
                            Downline Matrix
                        </th>
                        <th scope="col" style="min-width: 120px; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; padding: 10px 14px;">
                            Period
                        </th>
                        <th scope="col" style="min-width: 110px; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; padding: 10px 14px;">
                            Governance
                        </th>
                        <th scope="col" style="width: 90px; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; padding: 10px 14px;">
                            Status
                        </th>
                        <th scope="col" style="width: 130px; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; padding: 10px 16px;" class="text-end pe-3">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody style="border-top: none;">
                    @forelse($rules as $rule)
                        <tr class="rule-row" style="border-bottom: 1px solid var(--border-color); transition: background-color 0.12s ease;">
                            <!-- Row Index -->
                            <td class="ps-3" style="font-size: 0.75rem; color: #94A3B8; font-variant-numeric: tabular-nums;">
                                {{ $rules->firstItem() + $loop->index }}
                            </td>

                            <!-- Career Path -->
                            <td style="padding: 11px 14px;">
                                <div class="d-inline-flex align-items-center gap-1.5 mb-1" style="font-size: 0.775rem;">
                                    <span class="d-inline-flex align-items-center gap-1 px-1.5 py-0.5" style="background: var(--surface-subtle); color: var(--text-body); border: 1px solid var(--border-color); font-size: 0.6875rem; font-weight: 600;">
                                        <span style="font-family: ui-monospace, monospace; color: #64748B; font-size: 0.65rem;">L{{ $rule->fromRank?->level ?? $rule->fromRank?->sort_order }}</span>
                                        <span>{{ $rule->fromRank?->name ?? 'Rank ' . $rule->from_rank_id }}</span>
                                    </span>
                                    <i class="bi bi-arrow-right text-muted" style="font-size: 0.65rem;"></i>
                                    <span class="d-inline-flex align-items-center gap-1 px-1.5 py-0.5 text-white" style="background: var(--color-primary); font-size: 0.6875rem; font-weight: 600;">
                                        <span style="font-family: ui-monospace, monospace; color: #D9F99D; font-size: 0.65rem;">L{{ $rule->toRank?->level ?? $rule->toRank?->sort_order }}</span>
                                        <span>{{ $rule->toRank?->name ?? 'Rank ' . $rule->to_rank_id }}</span>
                                    </span>
                                </div>
                                <div class="rule-title-wrapper">
                                    <a href="{{ route('superadmin.promotion-rules.show', $rule) }}" class="rule-title-link fw-semibold text-dark d-inline-block" style="font-size: 0.8125rem; letter-spacing: -0.01em;" title="View promotion rule details">
                                        {{ $rule->name }}
                                    </a>
                                </div>
                            </td>

                            <!-- Team Deposit Target & Ratio -->
                            <td style="padding: 11px 14px;">
                                <div class="d-flex align-items-baseline gap-1.5">
                                    <span style="font-weight: 700; color: var(--text-heading); font-size: 0.875rem; font-variant-numeric: tabular-nums;">
                                        ₹{{ $rule->formatted_min_team_deposit }}
                                    </span>
                                    @if((float) $rule->min_team_deposit > 0)
                                        <span class="text-muted" style="font-size: 0.7rem;">
                                            ({{ $rule->min_team_deposit_text }})
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap">
                                    <span class="px-1.5 py-0.5" style="background: var(--surface-subtle); border: 1px solid var(--border-color); font-size: 0.6875rem; font-family: ui-monospace, monospace; color: var(--text-body);">
                                        <span style="color: var(--color-primary); font-weight: 600;">RD {{ number_format((float) $rule->rd_composition_percentage, 0) }}%</span>
                                        <span class="text-muted opacity-50">/</span>
                                        <span style="color: var(--color-copper, #C2410C); font-weight: 600;">FD {{ number_format((float) $rule->fd_composition_percentage, 0) }}%</span>
                                    </span>
                                    @if($rule->apply_tenure_targets)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.65rem; font-weight: 600;" title="Tenure Multiplier Active">
                                            Weighted
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Personal Quota -->
                            <td style="padding: 11px 14px;">
                                @if((float) $rule->min_individual_deposit > 0)
                                    <div class="fw-semibold text-dark" style="font-size: 0.8125rem; font-variant-numeric: tabular-nums;">
                                        ₹{{ $rule->formatted_min_individual_deposit }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">
                                        ({{ $rule->min_individual_deposit_text }})
                                    </div>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>

                            <!-- Downline Matrix -->
                            <td style="padding: 11px 14px;">
                                @php
                                    $tiers = $rule->qualifying_agent_tiers ?? [];
                                @endphp
                                @if(!empty($tiers))
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($tiers as $tier)
                                            @php
                                                $tRank = isset($tier['rank_id']) && $tier['rank_id'] ? \App\Models\CareerLevel::find($tier['rank_id']) : $rule->getTargetQualifyingRank();
                                            @endphp
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <span class="px-1.5 py-0.5" style="font-size: 0.6875rem; background: var(--surface-subtle); color: var(--text-heading); font-weight: 700; border: 1px solid var(--border-color); font-family: ui-monospace, monospace;">
                                                    {{ $tier['count'] }}&times;
                                                </span>
                                                <span style="font-size: 0.75rem; color: var(--text-body);">
                                                    {{ $tRank?->name ?? 'Direct Agent' }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($rule->min_qualifying_direct_facilitators > 0)
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="px-1.5 py-0.5" style="font-size: 0.6875rem; background: var(--surface-subtle); color: var(--text-heading); font-weight: 700; border: 1px solid var(--border-color); font-family: ui-monospace, monospace;">
                                            {{ $rule->min_qualifying_direct_facilitators }}&times;
                                        </span>
                                        <span style="font-size: 0.75rem; color: var(--text-body);">
                                            {{ $rule->getTargetQualifyingRank()?->name ?? 'Direct Agent' }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>

                            <!-- Period -->
                            <td style="padding: 11px 14px;">
                                @if($rule->financialYear)
                                    <span class="badge px-1.5 py-0.5" style="background: var(--surface-subtle); color: var(--text-heading); border: 1px solid var(--border-strong); font-size: 0.6875rem; font-family: ui-monospace, monospace;">
                                        {{ $rule->financialYear->code }}
                                    </span>
                                @else
                                    <span style="font-size: 0.775rem; color: var(--text-body);">
                                        {{ $rule->getCalculationPeriodLabel() }}
                                    </span>
                                @endif
                            </td>

                            <!-- Governance -->
                            <td style="padding: 11px 14px;">
                                @if(!$rule->requires_approval)
                                    <span class="d-inline-flex align-items-center gap-1 px-2 py-0.5 bg-info-subtle text-info border border-info-subtle" style="font-size: 0.7rem; font-weight: 600;">
                                        <span>Auto-Execute</span>
                                    </span>
                                @elseif($rule->authorized_approval_role === 'super_admin')
                                    <span class="d-inline-flex align-items-center gap-1 px-2 py-0.5 bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem; font-weight: 600;">
                                        <span>Super Admin</span>
                                    </span>
                                @elseif($rule->authorized_approval_role === 'admin')
                                    <span class="d-inline-flex align-items-center gap-1 px-2 py-0.5" style="background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; font-size: 0.7rem; font-weight: 600;">
                                        <span>Admin Review</span>
                                    </span>
                                @else
                                    <span class="d-inline-flex align-items-center gap-1 px-2 py-0.5" style="background: var(--surface-subtle); color: var(--text-muted); border: 1px solid var(--border-color); font-size: 0.7rem; font-weight: 500;">
                                        <span>Any Signatory</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td style="padding: 11px 14px;">
                                @if($rule->status === 'active')
                                    <span class="bank-pill bank-pill-active">
                                        <span class="bank-pill-dot"></span>
                                        <span>Active</span>
                                    </span>
                                @else
                                    <span class="bank-pill bank-pill-inactive">
                                        <span class="bank-pill-dot"></span>
                                        <span>Inactive</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="text-end pe-3" style="padding: 11px 16px;">
                                <div class="d-inline-flex align-items-center gap-1 justify-content-end">
                                    <a href="{{ route('superadmin.promotion-rules.show', $rule) }}"
                                       class="btn btn-sm btn-outline-secondary rule-action-btn"
                                       title="View Criteria &amp; Requests">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('superadmin.promotion-rules.edit', $rule) }}"
                                       class="btn btn-sm btn-outline-secondary rule-action-btn"
                                       title="Edit Rule">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('superadmin.promotion-rules.toggle-status', $rule) }}" method="POST" class="d-inline m-0"
                                          data-confirm="Are you sure you want to {{ $rule->status === 'active' ? 'deactivate' : 'activate' }} this promotion rule?"
                                          data-confirm-title="{{ $rule->status === 'active' ? 'Deactivate Promotion Rule' : 'Activate Promotion Rule' }}"
                                          data-confirm-type="{{ $rule->status === 'active' ? 'warning' : 'success' }}"
                                          data-confirm-btn="{{ $rule->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-secondary rule-action-btn"
                                                title="{{ $rule->status === 'active' ? 'Deactivate Rule' : 'Activate Rule' }}">
                                            <i class="bi bi-{{ $rule->status === 'active' ? 'pause-circle' : 'play-circle' }}"></i>
                                        </button>
                                    </form>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-rule rule-action-btn"
                                            title="Delete Rule"
                                            data-action="{{ route('superadmin.promotion-rules.destroy', $rule) }}"
                                            data-name="{{ $rule->name }}">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div style="width: 44px; height: 44px; border-radius: 8px; background: var(--surface-subtle); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px; color: var(--text-muted); font-size: 1.25rem;">
                                    <i class="bi bi-folder2-open"></i>
                                </div>
                                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">No promotion rules found matching your filters.</div>
                                <p class="text-muted small mb-2.5" style="font-size: 0.78rem;">Adjust your filter parameters or configure a new career advancement pathway.</p>
                                <button type="button" class="btn btn-sm btn-outline-dark" onclick="if(window.resetPromotionFilters) { window.resetPromotionFilters(); } else { window.location.href='{{ route('superadmin.promotion-rules.index') }}'; }" style="border-radius: 6px; font-weight: 500; padding: 5px 12px; font-size: 0.8125rem;">
                                    <i class="bi bi-x-circle me-1"></i> Clear Filters
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Minimalist Pagination Ledger Footer -->
    @if($rules->hasPages())
        <div class="card-footer bg-transparent border-top" style="padding: 12px 20px; border-color: var(--border-color) !important;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div style="font-size: 0.775rem; color: #64748B; font-variant-numeric: tabular-nums;">
                    Showing <strong class="text-dark">{{ $rules->firstItem() }}</strong> to <strong class="text-dark">{{ $rules->lastItem() }}</strong> of <strong class="text-dark">{{ $rules->total() }}</strong> rules
                </div>
                <div>
                    {{ $rules->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    @endif
</div>
