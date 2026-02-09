@if ($paginator->hasPages())
    <nav class="pagination-nav" aria-label="Навигация по страницам">
        <ul class="pagination">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="pagination__prev disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span aria-hidden="true"><span class="pagination__arrow">←</span></span>
                </li>
            @else
                <li class="pagination__prev">
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')"><span class="pagination__arrow">←</span></a>
                </li>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="pagination__ellipsis disabled" aria-disabled="true"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="pagination__item active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li class="pagination__item"><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="pagination__next">
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')"><span class="pagination__arrow">→</span></a>
                </li>
            @else
                <li class="pagination__next disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span aria-hidden="true"><span class="pagination__arrow">→</span></span>
                </li>
            @endif
        </ul>
    </nav>
@endif
