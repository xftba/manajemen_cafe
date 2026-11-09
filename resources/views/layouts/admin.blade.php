@php
    $cafe  = \App\Models\PengaturanCafe::first();
    $user  = auth()->user();
    $menu  = config('sidebar.' . $user->role, []);
    $aktif = fn ($item) => request()->routeIs($item['aktif']);

    // Notifikasi: bahan yang habis atau mencapai batas minimum (habis ditampilkan lebih dulu)
    $menuHabis  = \App\Models\Menu::jumlahHabis();
    $bahanNotif = \App\Models\BahanBaku::whereColumn('stok', '<=', 'stok_minimum')
        ->orderByRaw('stok > 0')
        ->orderBy('stok')
        ->limit(20)
        ->get();

    $jumlahNotif = $bahanNotif->count() + (($menuHabis > 0 && $user->role === 'admin') ? 1 : 0);
    $angkaNotif  = fn ($n) => \App\Models\BahanBaku::angka($n);
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - {{ $cafe?->nama_cafe ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-sans text-neutral-900 antialiased"
      x-data="{ drawer: false }" @keydown.escape.window="drawer = false">

{{-- Latar gelap saat drawer terbuka --}}
<div x-show="drawer" x-cloak @click="drawer = false"
     class="fixed inset-0 z-40 bg-black/40 lg:hidden print:hidden"></div>

{{-- ================= SIDEBAR ================= --}}
<aside :class="drawer ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-[220px] flex-col overflow-y-auto border-r border-black/10 bg-white px-3 py-4 transition-transform lg:translate-x-0 print:hidden">

    {{-- Logo dan nama café (dari Pengaturan Café) --}}
    <div class="flex items-center gap-2.5 px-1">
        @if ($cafe?->logo)
            <img src="{{ asset('storage/' . $cafe->logo) }}" alt="Logo" class="h-8 w-8 rounded-lg object-contain">
        @else
            <svg class="h-8 w-8 text-neutral-800" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 11h12v4a5 5 0 01-5 5h-2a5 5 0 01-5-5v-4z"/>
                <path d="M17 12h1.5a2.5 2.5 0 010 5H17"/>
                <path d="M8 3c0 1.5 1.5 1.5 1.5 3M12 3c0 1.5 1.5 1.5 1.5 3"/>
                <path d="M4 22h14"/>
            </svg>
        @endif
        <span class="text-[13px] font-semibold leading-tight">{{ $cafe?->nama_cafe ?? config('app.name') }}</span>
    </div>

    {{-- Menu sesuai role --}}
    <nav class="mt-5 flex-1 space-y-0.5">
        @foreach ($menu as $item)
            @if (isset($item['anak']))

                {{-- Grup menu: terbuka otomatis kalau salah satu anaknya sedang aktif --}}
                @php $terbuka = collect($item['anak'])->contains(fn ($a) => $aktif($a)); @endphp
                <div x-data="{ buka: $persist(@js($terbuka)).as('sidebar_{{ \Illuminate\Support\Str::slug($item['label']) }}') }">
                    <button type="button" @click="buka = !buka"
                            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-[12px] font-semibold transition hover:bg-neutral-100">
                        <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                        <span class="flex-1">{{ $item['label'] }}</span>
                        <svg class="h-3.5 w-3.5 text-neutral-500 transition-transform" :class="buka ? 'rotate-180' : ''"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 9l6 6 6-6"/>
                        </svg>
                    </button>

                    <div x-show="buka" x-cloak class="mt-0.5 space-y-0.5">
                        @foreach ($item['anak'] as $anak)
                            <a href="{{ route($anak['route']) }}"
                               class="block rounded-md py-1.5 pl-[38px] pr-2.5 text-[11px] transition {{ $aktif($anak) ? 'font-semibold text-accent' : 'text-neutral-500 hover:text-neutral-900' }}">
                                {{ $anak['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

            @else

                {{-- Menu tunggal --}}
                @php $sedang = $aktif($item); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[12px] font-semibold transition {{ $sedang ? 'bg-accent text-white' : 'hover:bg-neutral-100' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                    <span>{{ $item['label'] }}</span>
                </a>

            @endif
        @endforeach
    </nav>

    {{-- Keluar --}}
    <div class="mt-3 border-t border-neutral-200 pt-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-[12px] font-medium transition hover:bg-red-50">
                <svg class="h-[18px] w-[18px] shrink-0 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
                Keluar
            </button>
        </form>
    </div>
</aside>

<div class="min-w-0 lg:ml-[220px] print:ml-0">

    {{-- ================= BILAH ATAS ================= --}}
    <header class="sticky top-0 z-30 flex h-12 items-center justify-between gap-3 border-b border-neutral-200 bg-white px-3 lg:px-4 print:hidden">

        <div class="flex min-w-0 flex-1 items-center gap-2">
            {{-- Tombol menu (layar kecil) --}}
            <button type="button" @click="drawer = true" aria-label="Buka menu"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg hover:bg-neutral-100 lg:hidden">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <h1 class="truncate text-[14px] font-semibold">
                @yield('title', 'Dashboard')
            </h1>
        </div>

        <div class="flex items-center gap-2">

            {{-- Tanggal --}}
            <div class="hidden h-8 items-center gap-1.5 rounded-full bg-neutral-100 px-3 md:flex">
                <svg class="h-3.5 w-3.5 shrink-0 text-neutral-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>
                </svg>
                <span class="text-[11px] font-medium leading-none text-neutral-600">
                    {{ now()->translatedFormat('l, d F Y') }}
                </span>
            </div>

            {{-- Tombol Notifikasi --}}
            <x-lonceng :jumlah="$jumlahNotif">
                @foreach ($bahanNotif as $b)
                    @php $habis = (float) $b->stok <= 0; @endphp
                    <a href="{{ route('bahan-baku.index', ['menipis' => 1]) }}"
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

                @if ($menuHabis > 0 && $user->role === 'admin')
                    <a href="{{ route('menu.index') }}" class="block rounded-lg bg-neutral-100 px-3 py-2 transition hover:bg-neutral-200">
                        <p class="text-[11px] font-semibold">{{ $menuHabis }} menu sedang habis</p>
                        <p class="mt-0.5 text-[10px] text-neutral-500">Stok bahan tidak cukup atau resep belum dibuat.</p>
                    </a>
                @endif
            </x-lonceng>

            {{-- Profil akun --}}
            <div class="flex items-center gap-2 rounded-full bg-neutral-100 py-0.5 pl-0.5 pr-3">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent text-[13px] font-semibold text-white">
                    {{ strtoupper(substr($user->nama, 0, 1)) }}
                </span>
                <span class="hidden text-left leading-tight sm:block">
                    <span class="block text-[9px] text-accent">{{ $user->role_label }}</span>
                    <span class="block text-[11px] font-semibold">{{ $user->nama }}</span>
                </span>
            </div>
        </div>
    </header>

    <main class="min-w-0 p-3 lg:p-4 print:p-0">
        @if (session('sukses'))
            <div class="mb-3 rounded-lg bg-green-100 px-3 py-2 text-xs text-green-800 print:hidden">{{ session('sukses') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-3 rounded-lg bg-red-100 px-3 py-2 text-xs text-red-800 print:hidden">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>