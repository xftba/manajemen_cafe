
@if ($paginator->hasPages())
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        {{-- Informasi jumlah data --}}
        <p class="text-xs text-neutral-500">
            Menampilkan
            <span class="font-semibold text-neutral-800">
                {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}
            </span>
            dari
            <span class="font-semibold text-neutral-800">
                {{ $paginator->total() }}
            </span>
            data
        </p>

        {{-- Navigasi halaman --}}
        <nav class="flex items-center gap-1.5" aria-label="Pagination">

            {{-- Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="flex h-9 w-9 cursor-not-allowed items-center justify-center
                             rounded-lg border border-neutral-200 text-neutral-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="flex h-9 w-9 items-center justify-center rounded-lg
                          border border-neutral-200 text-neutral-600
                          transition hover:bg-neutral-100">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                </a>
            @endif

            {{-- Nomor halaman --}}
            @foreach ($elements as $element)

                @if (is_string($element))
                    <span class="px-2 text-xs text-neutral-400">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)

                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="flex h-9 min-w-9 items-center justify-center
                                         rounded-lg bg-accent px-3 text-xs
                                         font-semibold text-white shadow-sm">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="flex h-9 min-w-9 items-center justify-center
                                      rounded-lg border border-neutral-200
                                      bg-white px-3 text-xs font-medium
                                      text-neutral-600 transition
                                      hover:border-accent hover:bg-cream hover:text-accent">
                                {{ $page }}
                            </a>
                        @endif

                    @endforeach
                @endif

            @endforeach

            {{-- Berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="flex h-9 w-9 items-center justify-center rounded-lg
                          border border-neutral-200 text-neutral-600
                          transition hover:bg-neutral-100">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </a>
            @else
                <span class="flex h-9 w-9 cursor-not-allowed items-center justify-center
                             rounded-lg border border-neutral-200 text-neutral-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </span>
            @endif

        </nav>
    </div>
@endif
