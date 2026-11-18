@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="flex gap-2 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-3.5 py-1.5 text-xs font-semibold text-[#94A3B8] dark:text-[#64748B] bg-white dark:bg-[#111A15] border border-[#E2E8E4] dark:border-[#1E2C24] rounded-xl cursor-not-allowed">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-3.5 py-1.5 text-xs font-semibold text-[#0F172A] dark:text-[#F8FAFC] bg-white dark:bg-[#111A15] border border-[#E2E8E4] dark:border-[#1E2C24] rounded-xl hover:bg-[#F8FAF9] dark:hover:bg-[#18241E] transition-colors">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-3.5 py-1.5 text-xs font-semibold text-[#0F172A] dark:text-[#F8FAFC] bg-white dark:bg-[#111A15] border border-[#E2E8E4] dark:border-[#1E2C24] rounded-xl hover:bg-[#F8FAF9] dark:hover:bg-[#18241E] transition-colors">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="inline-flex items-center px-3.5 py-1.5 text-xs font-semibold text-[#94A3B8] dark:text-[#64748B] bg-white dark:bg-[#111A15] border border-[#E2E8E4] dark:border-[#1E2C24] rounded-xl cursor-not-allowed">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:gap-2 sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-[#64748B] dark:text-[#94A3B8] leading-5">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-semibold text-[#0F172A] dark:text-[#F8FAFC]">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-semibold text-[#0F172A] dark:text-[#F8FAFC]">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-semibold text-[#0F172A] dark:text-[#F8FAFC]">{{ $paginator->total() }}</span>
                    {!! __('records') !!}
                </p>
            </div>

            <div>
                <span class="inline-flex gap-1 rtl:flex-row-reverse">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-[#94A3B8] dark:text-[#64748B] bg-white dark:bg-[#111A15] border border-[#E2E8E4] dark:border-[#1E2C24] rounded-xl cursor-not-allowed" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-[#0F172A] dark:text-[#F8FAFC] bg-white dark:bg-[#111A15] border border-[#E2E8E4] dark:border-[#1E2C24] rounded-xl hover:bg-[#F8FAF9] dark:hover:bg-[#18241E] transition-colors" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="m15 18-6-6 6-6" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-[#64748B] dark:text-[#94A3B8] bg-white dark:bg-[#111A15] border border-[#E2E8E4] dark:border-[#1E2C24] rounded-xl">{{ $element }}</span>
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-white bg-[#14532D] dark:bg-[#059669] border border-[#14532D] dark:border-[#059669] rounded-xl shadow-xs">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-[#0F172A] dark:text-[#F8FAFC] bg-white dark:bg-[#111A15] border border-[#E2E8E4] dark:border-[#1E2C24] rounded-xl hover:bg-[#F8FAF9] dark:hover:bg-[#18241E] transition-colors" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-[#0F172A] dark:text-[#F8FAFC] bg-white dark:bg-[#111A15] border border-[#E2E8E4] dark:border-[#1E2C24] rounded-xl hover:bg-[#F8FAF9] dark:hover:bg-[#18241E] transition-colors" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-[#94A3B8] dark:text-[#64748B] bg-white dark:bg-[#111A15] border border-[#E2E8E4] dark:border-[#1E2C24] rounded-xl cursor-not-allowed" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
