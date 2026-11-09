@php
    $cafe      = \App\Models\PengaturanCafe::first();
    $nav       = config('sidebar.kasir', []);
    $user      = auth()->user();
    $menuHabis = \App\Models\Menu::jumlahHabis();

    // Bahan yang sudah habis atau mencapai batas minimum (habis ditampilkan lebih dulu)
    $bahanNotif = \App\Models\BahanBaku::whereColumn('stok', '<=', 'stok_minimum')
        ->orderByRaw('stok > 0')
        ->orderBy('stok')
        ->limit(20)
        ->get();

    $jumlahNotif = $bahanNotif->count() + ($menuHabis > 0 ? 1 : 0);
    $angkaNotif  = fn ($n) => \App\Models\BahanBaku::angka($n);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ $cafe?->nama_cafe ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-sans text-neutral-900 antialiased">

{{-- ================= SIDEBAR IKON ================= --}}
<aside class="fixed inset-y-0 left-0 z-30 flex w-[52px] flex-col items-center border-r border-neutral-200 bg-white py-3">
    <div class="flex h-9 w-9 items-center justify-center overflow-hidden">
        @if ($cafe?->logo)
            <img src="{{ asset('storage/' . $cafe->logo) }}" alt="Logo" class="h-full w-full object-contain">
        @else
            <svg class="h-9 w-9 text-neutral-800" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 11h12v4a5 5 0 01-5 5h-2a5 5 0 01-5-5v-4z"/>
                <path d="M17 12h1.5a2.5 2.5 0 010 5H17"/>
                <path d="M8 3c0 1.5 1.5 1.5 1.5 3M12 3c0 1.5 1.5 1.5 1.5 3"/>
                <path d="M4 22h14"/>
            </svg>
        @endif
    </div>

    <nav class="mt-6 flex flex-1 flex-col items-center gap-2">
        @foreach ($nav as $item)
            @php $aktif = request()->routeIs($item['aktif'] ?? $item['route']); @endphp
            <a href="{{ route($item['route']) }}" aria-label="{{ $item['label'] }}"
               class="group relative flex h-9 w-9 items-center justify-center rounded-lg transition
                      {{ $aktif ? 'bg-cream text-accent' : 'text-neutral-400 hover:text-neutral-800' }}">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>

                @if ($aktif)
                    <span class="absolute -right-[8px] h-6 w-[3px] rounded-full bg-accent"></span>
                @endif

                {{-- Tooltip muncul saat kursor diarahkan --}}
                <span class="pointer-events-none absolute left-full ml-4 whitespace-nowrap rounded-md bg-neutral-900 px-2.5 py-1 text-[11px] font-medium text-white opacity-0 shadow-lg transition group-hover:opacity-100">
                    {{ $item['label'] }}
                </span>
            </a>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" aria-label="Keluar"
                class="group relative flex h-9 w-9 items-center justify-center rounded-lg text-red-500 transition hover:bg-red-50">
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
            </svg>
            <span class="pointer-events-none absolute left-full ml-4 whitespace-nowrap rounded-md bg-neutral-900 px-2.5 py-1 text-[11px] font-medium text-white opacity-0 shadow-lg transition group-hover:opacity-100">
                Keluar
            </span>
        </button>
    </form>
</aside>

<div class="ml-[52px] min-h-screen min-w-0">

    {{-- ================= BILAH ATAS ================= --}}
    <header class="sticky top-0 z-20 flex h-12 items-center justify-between gap-3 border-b border-neutral-200 bg-white px-4">

        <div class="min-w-0 flex-1">
            <h1 class="truncate text-[14px] font-semibold">
                @yield('title')
            </h1>
        </div>

        <div class="flex items-center gap-2">

            {{-- Tombol Notifikasi --}}
            <x-lonceng :jumlah="$jumlahNotif">
                @foreach ($bahanNotif as $b)
                    @php $habis = (float) $b->stok <= 0; @endphp
                    <a href="{{ route('kasir.ketersediaan') }}"
                    class="block rounded-lg px-3 py-2 transition {{ $habis ? 'bg-red-50 hover:bg-red-100' : 'bg-amber-50 hover:bg-amber-100' }}">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-[11px] font-semibold">{{ $b->nama }}</p>
                            <span class="shrink-0 rounded-full px-2 py-px text-[9px] font-semibold {{ $habis ? 'bg-red-600 text-white' : 'bg-amber-500 text-white' }}">
                                {{ $habis ? 'Habis' : 'Menipis' }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-[10px] {{ $habis ? 'text-red-700' : 'text-amber-700' }}">
                            Sisa {{ $angkaNotif($b->stok) }} {{ $b->satuan }} • Min {{ $angkaNotif($b->stok_minimum) }} {{ $b->satuan }}
                        </p>
                    </a>
                @endforeach

                @if ($menuHabis > 0)
                    <a href="{{ route('kasir.ketersediaan') }}" class="block rounded-lg bg-neutral-100 px-3 py-2 transition hover:bg-neutral-200">
                        <p class="text-[11px] font-semibold">{{ $menuHabis }} menu sedang habis</p>
                        <p class="mt-0.5 text-[10px] text-neutral-500">Cek status menu di halaman Ketersediaan.</p>
                    </a>
                @endif
            </x-lonceng>

            {{-- Profil dan menu akun --}}
            <div x-data="{ buka: false }" class="relative">
                <button type="button"
                        class="flex items-center gap-2 rounded-full bg-neutral-100 py-0.5 pl-0.5 pr-3 transition">

                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent text-[13px] font-semibold text-white">
                        {{ strtoupper(substr($user->nama, 0, 1)) }}
                    </span>

                    <span class="text-left leading-tight">
                        <span class="block text-[9px] text-accent">{{ $user->role_label }}</span>
                        <span class="block text-[11px] font-semibold">{{ $user->nama }}</span>
                    </span>

                </button>
            </div>
        </div>
    </header>

    @if (session('sukses'))
        <div class="fixed right-5 top-16 z-[60] rounded-lg bg-green-100 px-3 py-2 text-xs text-green-800 shadow-lg">{{ session('sukses') }}</div>
    @endif
    @if (session('error'))
        <div class="fixed right-5 top-16 z-[60] rounded-lg bg-red-100 px-3 py-2 text-xs text-red-800 shadow-lg">{{ session('error') }}</div>
    @endif

    <main>
        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>