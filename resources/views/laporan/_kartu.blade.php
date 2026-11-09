{{-- $kartu = [[judul, nilai, ikon, warnaIkon, warnaAngka], ...]  |  $kolom = kelas grid (opsional) --}}
<div class="mb-3 grid gap-3 {{ $kolom ?? 'grid-cols-2 lg:grid-cols-4' }}">
    @foreach ($kartu as [$judul, $nilai, $ikon, $warnaIkon, $warnaAngka])
        <div class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm print:shadow-none print:ring-1 print:ring-neutral-200">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $warnaIkon }} print:hidden">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
            </span>
            <div class="min-w-0">
                <p class="truncate text-[11px] text-neutral-500">{{ $judul }}</p>
                <p class="truncate text-[15px] font-semibold leading-tight {{ $warnaAngka }}">{{ $nilai }}</p>
            </div>
        </div>
    @endforeach
</div>