<li class="agent-tree-item" data-agent-id="{{ $node->id }}" data-depth="{{ $depth }}">
    <div class="agent-node {{ $node->status === 'active' ? '' : 'is-inactive' }}">
        @if($node->treeChildren->isNotEmpty())
            <button type="button" class="agent-tree-toggle" onclick="toggleAgentDownline(this)" title="Toggle downline network ({{ $node->treeChildren->count() }})">
                <i class="bi bi-chevron-down"></i>
            </button>
        @else
            <span class="agent-tree-toggle-spacer"></span>
        @endif

        @if($node->profile_image_url)
            <img src="{{ $node->profile_image_url }}" alt="{{ $node->name }}" class="agent-node-avatar object-fit-cover">
        @else
            <div class="agent-node-avatar">{{ strtoupper(substr($node->name, 0, 1)) }}</div>
        @endif

        <div class="agent-node-main">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <a href="{{ route('admin.agents.show', $node) }}" class="fw-semibold text-decoration-none agent-name-link">
                    {{ $node->name }}
                </a>
                <span class="badge bg-light text-dark border font-monospace px-2 py-0 num-tabular" style="font-size:0.75rem;">
                    {{ $node->member_code ?? $node->agent_id }}
                </span>
                @if($node->rank)
                    <span class="badge bg-dark text-white fw-normal" style="font-size:0.72rem;">{{ $node->rank->name }} (Lvl {{ $node->rank->level }})</span>
                @endif
                @if($node->status === 'active')
                    <span class="badge-active">Active</span>
                @elseif($node->status === 'pending')
                    <span class="badge-pending"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                @elseif($node->status === 'rejected')
                    <span class="badge-inactive">Rejected</span>
                @else
                    <span class="badge-inactive">Inactive</span>
                @endif
            </div>

            <div class="agent-node-meta">
                <span><i class="bi bi-building me-1 text-muted"></i>{{ $node->branch->name ?? 'Free Field' }}</span>
                @if($node->place)
                    <span><i class="bi bi-geo-alt me-1 text-muted"></i>{{ $node->place }}</span>
                @endif
                @if($node->phone)
                    <span><i class="bi bi-telephone me-1 text-muted"></i><span class="num-tabular">{{ $node->phone }}</span></span>
                @endif
                @if($node->parentAgent)
                    <span><i class="bi bi-arrow-up-right me-1 text-muted"></i>Sponsor: <strong class="text-secondary">{{ $node->parentAgent->name }}</strong></span>
                @endif
                @if($node->treeChildren->isNotEmpty())
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0" style="font-size:0.7rem;">
                        <i class="bi bi-people-fill me-1"></i>{{ $node->treeChildren->count() }} direct downline
                    </span>
                @endif
            </div>
        </div>

        <div class="agent-node-actions d-flex gap-1 flex-shrink-0 align-items-center ms-auto">
            @if(auth()->user()->isSuperAdmin() && $node->status === 'pending')
                <form action="{{ route('admin.agents.accept', $node) }}" method="POST"
                      data-confirm="Verify and accept agent '{{ $node->name }}' into active service?"
                      data-confirm-title="Verify & Accept Agent"
                      data-confirm-type="success"
                      data-confirm-btn="Verify & Accept">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success px-2 py-1" title="Verify & Accept Agent">
                        <i class="bi bi-check-lg me-1"></i>Accept
                    </button>
                </form>
                <form action="{{ route('admin.agents.reject', $node) }}" method="POST"
                      data-confirm="Reject agent application for '{{ $node->name }}'?"
                      data-confirm-title="Reject Agent"
                      data-confirm-type="danger"
                      data-confirm-btn="Reject">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="Reject Agent">
                        <i class="bi bi-x-lg me-1"></i>Reject
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.agents.show', $node) }}"
               class="btn btn-action" title="View Profile">
                <i class="bi bi-eye"></i>
            </a>
            <a href="{{ route('admin.targets.agents.show', $node) }}"
               class="btn btn-action text-success" title="Financial Year Targets">
                <i class="bi bi-crosshair"></i>
            </a>
            <a href="{{ route('admin.career.agent-performance', $node) }}"
               class="btn btn-action text-primary" title="Career Scorecard">
                <i class="bi bi-award"></i>
            </a>
            <a href="{{ route('admin.leads.index', ['agent_id' => $node->id]) }}"
               class="btn btn-action" title="View Sourced Leads">
                <i class="bi bi-briefcase"></i>
            </a>
            <a href="{{ route('admin.agents.edit', $node) }}"
               class="btn btn-action" title="Edit Agent">
                <i class="bi bi-pencil"></i>
            </a>

            @if($node->status !== 'pending')
            <form action="{{ route('admin.agents.toggle-status', $node) }}" method="POST"
                  data-confirm="Are you sure you want to {{ $node->status === 'active' ? 'deactivate' : 'activate' }} this agent?"
                  data-confirm-title="{{ $node->status === 'active' ? 'Deactivate Agent' : 'Activate Agent' }}"
                  data-confirm-type="{{ $node->status === 'active' ? 'danger' : 'success' }}"
                  data-confirm-btn="{{ $node->status === 'active' ? 'Deactivate' : 'Activate' }}">
                @csrf
                <button type="submit" class="btn btn-action {{ $node->status === 'active' ? 'text-danger' : 'text-success' }}"
                        title="{{ $node->status === 'active' ? 'Deactivate Agent' : 'Activate Agent' }}">
                    <i class="bi {{ $node->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                </button>
            </form>
            @endif

            @if(auth()->user()->isSuperAdmin())
            <form action="{{ route('admin.agents.destroy', $node) }}" method="POST" class="d-inline"
                  data-confirm="Are you sure you want to delete agent '{{ $node->name }}' ({{ $node->agent_id }})? This action cannot be undone."
                  data-confirm-title="Delete Agent"
                  data-confirm-type="danger"
                  data-confirm-btn="Delete Agent">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-action text-danger" title="Delete Agent">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
            @endif
        </div>
    </div>

    @if($node->treeChildren->isNotEmpty())
        <ul class="agent-tree-children">
            @foreach($node->treeChildren as $childAgent)
                @include('admin.agents._tree-node', ['node' => $childAgent, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
