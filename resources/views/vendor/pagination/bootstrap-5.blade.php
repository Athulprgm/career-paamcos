@if ($paginator->hasPages())
    <nav class="d-flex justify-items-center justify-content-between">
        <div class="d-flex justify-content-between flex-fill d-sm-none">
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.previous')</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">@lang('pagination.previous')</a>
                    </li>
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">@lang('pagination.next')</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.next')</span>
                    </li>
                @endif
            </ul>
        </div>

        <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between">
            <div>
                <p class="small text-muted mb-0">
                    {!! __('Showing') !!}
                    <span class="fw-semibold text-dark">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="fw-semibold text-dark">{{ $paginator->lastItem() }}</span>
                    {!! __('of') !!}
                    <span class="fw-semibold text-dark">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            <div>
                <ul class="pagination pagination-sm mb-0">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                            <span class="page-link d-inline-flex align-items-center justify-content-center" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            </span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link d-inline-flex align-items-center justify-content-center" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            </a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link d-inline-flex align-items-center justify-content-center" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                            <span class="page-link d-inline-flex align-items-center justify-content-center" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <style>
        .pagination {
            gap: 4px;
        }
        .pagination .page-item {
            margin: 0;
        }
        .pagination .page-link {
            color: var(--text-main, #0F172A);
            border-color: var(--border-color, #E2E8F0);
            font-size: 0.775rem;
            padding: 6px 12px;
            border-radius: 8px !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
            background: #FFFFFF;
            transition: all 0.12s ease;
        }
        .pagination .page-link:hover,
        .pagination .page-link:focus {
            background: var(--surface-subtle, #F8FAF9);
            color: var(--text-heading, #0F172A);
            border-color: var(--color-primary, #14532D);
        }
        .pagination .page-item.active .page-link {
            background: var(--color-primary, #14532D) !important;
            border-color: var(--color-primary, #14532D) !important;
            color: #FFFFFF !important;
            font-weight: 700 !important;
        }
        .pagination .page-item.disabled .page-link {
            color: var(--text-muted, #94A3B8);
            border-color: var(--border-color, #E2E8E4);
            background: #FFFFFF;
            opacity: 0.6;
        }

        /* Dark Mode Pagination */
        html.dark .pagination .page-link {
            color: #94A3B8;
            border-color: #1E2C24;
            background: #111A15;
        }
        html.dark .pagination .page-link:hover,
        html.dark .pagination .page-link:focus {
            background: #18241E;
            color: #F8FAFC;
            border-color: #34D399;
        }
        html.dark .pagination .page-item.active .page-link {
            background: #059669 !important;
            border-color: #059669 !important;
            color: #FFFFFF !important;
        }
        html.dark .pagination .page-item.disabled .page-link {
            color: #64748B;
            border-color: #1E2C24;
            background: #111A15;
            opacity: 0.5;
        }
    </style>
@endif
