@php $cafe = \App\Models\PengaturanCafe::first(); @endphp

{{-- Tab laporan, filter ringkas, dan tombol (tidak ikut tercetak) --}}
{{-- Tab di kiri, tanggal dan tombol di pojok kanan --}}
<div class="mb-3 flex items-center gap-3 print:hidden">

    <nav class="no-scrollbar -mx-1 flex min-w-0 flex-1 flex-nowrap items-center gap-2 overflow-x-auto px-1 py-1">
        @foreach ($tabs as $t)
            @php $aktifTab = request()->routeIs($t['route']); @endphp
            <a href="{{ route($t['route']) }}"
               class="flex h-9 shrink-0 items-center whitespace-nowrap rounded-full px-4 text-[11px] font-semibold transition
                      {{ $aktifTab ? 'bg-accent text-white shadow-sm' : 'bg-neutral-200/80 text-neutral-500 hover:bg-neutral-300/70' }}">
                {{ $t['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="ml-auto flex shrink-0 flex-nowrap items-center gap-2">

        @isset($filterRingkas)
            <x-tanggal :name="$filterRingkas['name']"
                       :label="$filterRingkas['label']"
                       :value="$filterRingkas['value']"
                       :type="$filterRingkas['type'] ?? 'date'"
                       :min="$filterRingkas['min'] ?? null"
                       :max="$filterRingkas['max'] ?? null"
                       :aktif="$filterRingkas['aktif'] ?? false"
                       :ringkas="true" />
        @endisset

        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
           class="inline-flex h-9 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full border border-accent px-4 text-[11px] font-semibold text-accent transition hover:bg-accent/5">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 4v11M7 11l5 5 5-5M5 20h14"/>
            </svg>
            Ekspor CSV
        </a>
        <button type="button" onclick="window.print()"
                class="inline-flex h-9 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full bg-accent px-4 text-[11px] font-semibold text-white shadow-sm transition hover:opacity-90">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9V3h12v6M6 18H4a1 1 0 01-1-1v-6a2 2 0 012-2h14a2 2 0 012 2v6a1 1 0 01-1 1h-2"/><rect x="6" y="14" width="12" height="7" rx="1"/>
            </svg>
            Cetak / PDF
        </button>
    </div>
</div>

{{-- Kop surat: hanya muncul saat dicetak --}}
<div class="mb-4 hidden border-b-2 border-neutral-800 pb-3 print:block">
    <div class="flex items-center gap-3">
        @if ($cafe?->logo)
            <img src="{{ asset('storage/' . $cafe->logo) }}" alt="Logo" class="h-14 w-14 object-contain">
        @endif
        <div>
            <p class="text-xl font-bold">{{ $cafe?->nama_cafe ?? config('app.name') }}</p>
            @if ($cafe?->alamat) <p class="text-sm">{{ $cafe->alamat }}</p> @endif
            @if ($cafe?->telepon) <p class="text-sm">Telp. {{ $cafe->telepon }}</p> @endif
        </div>
    </div>
    <p class="mt-3 text-lg font-semibold">{{ $judul }}</p>
    <p class="text-sm">{{ $periode }}</p>
    <p class="text-xs text-neutral-600">Dicetak oleh {{ auth()->user()->nama }} pada {{ now()->translatedFormat('d F Y H:i') }}</p>
</div>