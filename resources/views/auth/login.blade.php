@php $cafe = \App\Models\PengaturanCafe::first(); @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ $cafe?->nama_cafe ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-neutral-900 antialiased">

<div class="grid min-h-screen lg:grid-cols-2">

    {{-- ================= KIRI: BRANDING ================= --}}
    <section class="relative hidden overflow-hidden bg-accent text-white lg:flex lg:flex-col lg:justify-between lg:p-12">

        {{-- Dekorasi lingkaran --}}
        <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute bottom-40 right-16 h-24 w-24 rounded-full bg-white/10"></div>

        {{-- Logo dan nama café --}}
        <div class="relative flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-white p-1.5">
                @if ($cafe?->logo)
                    <img src="{{ asset('storage/' . $cafe->logo) }}" alt="Logo" class="h-full w-full object-contain">
                @else
                    <svg class="h-7 w-7 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 11h12v4a5 5 0 01-5 5h-2a5 5 0 01-5-5v-4z"/>
                        <path d="M17 12h1.5a2.5 2.5 0 010 5H17"/>
                        <path d="M8 3c0 1.5 1.5 1.5 1.5 3M12 3c0 1.5 1.5 1.5 1.5 3"/>
                        <path d="M4 22h14"/>
                    </svg>
                @endif
            </span>
            <span class="text-lg font-semibold">{{ $cafe?->nama_cafe ?? config('app.name') }}</span>
        </div>

        {{-- Tagline --}}
        <div class="relative max-w-md">
            <h2 class="text-3xl font-semibold leading-tight xl:text-4xl">
                Kelola café lebih mudah, dari pesanan sampai laporan.
            </h2>
            <p class="mt-4 text-sm leading-relaxed text-white/75">
                Satu sistem untuk kasir, stok bahan, dan menu. Semua tercatat otomatis dan selalu terbarui.
            </p>

            <div class="mt-8 space-y-4">
                @php
                    $fitur = [
                        ['Pesanan cepat', 'Kasir mencatat transaksi dalam hitungan detik.',
                         '<path d="M3 5h2l2.4 10.2a1 1 0 001 .8h8.9a1 1 0 001-.8L20 8H6"/><circle cx="9" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/>'],
                        ['Stok terpantau', 'Bahan menipis langsung muncul sebagai notifikasi.',
                         '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>'],
                        ['Menu otomatis', 'Ketersediaan menu mengikuti resep dan stok bahan.',
                         '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>'],
                    ];
                @endphp

                @foreach ($fitur as [$judul, $isi, $ikon])
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/15">
                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold">{{ $judul }}</p>
                            <p class="text-xs text-white/70">{{ $isi }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="relative text-xs text-white/60">
            &copy; {{ date('Y') }} {{ $cafe?->nama_cafe ?? config('app.name') }}. Hak cipta dilindungi.
        </p>
    </section>

        {{-- ================= KANAN: FORM LOGIN ================= --}}
    <section class="flex items-center justify-center bg-surface p-6 sm:p-10">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg ring-1 ring-black/5 sm:p-10"
             x-data="{ lihat: false, proses: false }">

            {{-- Logo (layar kecil saja) --}}
            <div class="mb-6 text-center lg:hidden">
                @if ($cafe?->logo)
                    <img src="{{ asset('storage/' . $cafe->logo) }}" alt="Logo" class="mx-auto mb-3 h-14 w-14 object-contain">
                @else
                    <svg class="mx-auto mb-3 h-14 w-14 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 11h12v4a5 5 0 01-5 5h-2a5 5 0 01-5-5v-4z"/>
                        <path d="M17 12h1.5a2.5 2.5 0 010 5H17"/>
                        <path d="M8 3c0 1.5 1.5 1.5 1.5 3M12 3c0 1.5 1.5 1.5 1.5 3"/>
                        <path d="M4 22h14"/>
                    </svg>
                @endif
                <p class="text-lg font-semibold">{{ $cafe?->nama_cafe ?? config('app.name') }}</p>
            </div>

            <div class="mb-7">
                <h1 class="text-2xl font-semibold">Selamat datang</h1>
                <p class="mt-1.5 text-sm text-neutral-500">Masuk dengan akun kamu untuk melanjutkan.</p>
            </div>

            @if (session('sukses'))
                <div class="mb-4 rounded-xl bg-green-100 px-4 py-3 text-sm text-green-800">{{ session('sukses') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-xl bg-red-100 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4" @submit="proses = true">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="mb-1.5 block text-[13px] font-medium text-neutral-700">Email</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-neutral-400"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>
                        </svg>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                               autocomplete="email" placeholder="nama@email.com"
                               class="h-11 w-full rounded-xl border bg-white pl-11 pr-3 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent
                                      {{ $errors->has('email') ? 'border-red-400' : 'border-neutral-300 focus:border-accent' }}">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="mb-1.5 block text-[13px] font-medium text-neutral-700">Password</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-neutral-400"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>
                        </svg>
                        <input id="password" :type="lihat ? 'text' : 'password'" name="password" required
                               autocomplete="current-password" placeholder="Masukkan password"
                               class="h-11 w-full rounded-xl border border-neutral-300 bg-white pl-11 pr-11 text-sm placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent">
                        <button type="button" @click="lihat = !lihat"
                                :aria-label="lihat ? 'Sembunyikan password' : 'Tampilkan password'"
                                class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-neutral-400 transition hover:text-neutral-700">
                            <svg x-show="!lihat" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg x-show="lihat" x-cloak class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3l18 18"/><path d="M10.6 6.1A10.7 10.7 0 0112 6c6.5 0 10 6 10 6a17 17 0 01-3.2 3.9M6.6 6.6A16.5 16.5 0 002 12s3.5 7 10 7a9.9 9.9 0 004.1-.9"/><path d="M9.9 9.9a3 3 0 004.2 4.2"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-2 text-[13px] text-neutral-600">
                    <input type="checkbox" name="remember"
                           class="h-4 w-4 rounded border-neutral-300 text-accent focus:ring-accent">
                    Ingat saya
                </label>

                <button type="submit" :disabled="proses"
                        class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent text-sm font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70">
                    <svg x-show="proses" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                        <path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                    <span x-text="proses ? 'Memproses...' : 'Login'"></span>
                </button>
            </form>

            <p class="mt-8 text-center text-xs text-neutral-400 lg:hidden">
                &copy; {{ date('Y') }} {{ $cafe?->nama_cafe ?? config('app.name') }}
            </p>
        </div>
    </section>
</div>

</body>
</html>