@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-center space-x-1 text-xs">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="px-2 py-1 text-gray-400 border border-gray-300 rounded">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="px-2 py-1 border border-gray-300 rounded hover:bg-gray-100">&lsaquo;</a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="px-2 py-1 text-gray-500">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-2 py-1 text-white bg-blue-500 border border-blue-500 rounded">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-2 py-1 border border-gray-300 rounded hover:bg-gray-100">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="px-2 py-1 border border-gray-300 rounded hover:bg-gray-100">&rsaquo;</a>
        @else
            <span class="px-2 py-1 text-gray-400 border border-gray-300 rounded">&rsaquo;</span>
        @endif
    </nav>
@endif
