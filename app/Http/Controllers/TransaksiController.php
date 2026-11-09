<?php

namespace App\Http\Controllers;

use App\Exceptions\TransaksiGagal;
use App\Models\PengaturanCafe;
use App\Models\PergerakanStok;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\AktivitasService;
use App\Services\TransaksiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\NotifikasiService;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        // Tanggal: tidak diisi = hari ini. Dikosongkan atau "semua" = seluruh riwayat.
        $semuaTanggal = $request->query('tanggal') === 'semua'
            || ($request->has('tanggal') && blank($request->query('tanggal')));
        $tanggal   = $semuaTanggal ? null : $this->tanggal($request->query('tanggal'));
        $filter    = in_array($request->query('filter'), ['qris', 'tunai', 'batal'], true) ? $request->query('filter') : null;
        $kataKunci = trim((string) $request->query('q', ''));

        // Dasar: pembatasan role, tanggal, dan pencarian.
        // Chip filter belum dipakai di sini supaya kartu ringkasan tetap stabil saat berpindah chip.
        $dasar = Transaksi::query()
            ->when($user->role === 'kasir', fn ($q) => $q->where('user_id', $user->id))
            ->when($user->role === 'admin' && $request->query('kasir'), fn ($q) => $q->where('user_id', $request->query('kasir')))
            ->when($tanggal, fn ($q) => $q->whereDate('tanggal_transaksi', $tanggal->toDateString()))
            ->when($kataKunci !== '', function ($q) use ($kataKunci) {
                $q->where(function ($w) use ($kataKunci) {
                    $w->where('no_invoice', 'like', "%{$kataKunci}%")
                        ->orWhere('nama_customer', 'like', "%{$kataKunci}%")
                        ->orWhereHas('detail.menu', fn ($m) => $m->where('nama', 'like', "%{$kataKunci}%"));
                });
            });

        // Omset, tunai, dan QRIS hanya menghitung transaksi yang selesai
        $ringkasan = [
            'jumlah' => (clone $dasar)->count(),
            'batal'  => (clone $dasar)->where('status', 'batal')->count(),
            'omset'  => (clone $dasar)->where('status', 'selesai')->sum('total'),
            'tunai'  => (clone $dasar)->where('status', 'selesai')
                ->whereHas('pembayaran', fn ($p) => $p->where('metode', 'tunai'))->sum('total'),
            'qris'   => (clone $dasar)->where('status', 'selesai')
                ->whereHas('pembayaran', fn ($p) => $p->where('metode', 'qris'))->sum('total'),
        ];

        $daftar = (clone $dasar)
            ->with(['user', 'pembayaran', 'detail.menu'])
            ->when($filter === 'batal', fn ($q) => $q->where('status', 'batal'))
            ->when(in_array($filter, ['qris', 'tunai'], true),
                fn ($q) => $q->whereHas('pembayaran', fn ($p) => $p->where('metode', $filter)))
            ->latest('tanggal_transaksi')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        // Transaksi yang sedang dibuka di panel struk. Dicari di dalam dasar,
        // jadi kasir tidak bisa membuka transaksi milik kasir lain lewat URL.
        $dipilih = $request->integer('detail') > 0
            ? (clone $dasar)->find($request->integer('detail'))
            : null;
        $dipilih ??= $daftar->first();
        $dipilih?->loadMissing(['user', 'detail.menu', 'pembayaran']);

        return view('transaksi.index', [
            'transaksi'   => $daftar,
            'dipilih'     => $dipilih,
            'bisaBatal'   => $dipilih ? $dipilih->bisaDibatalkanOleh($user) : false,
            'ringkasan'   => $ringkasan,
            'tanggal'     => $tanggal?->toDateString(),
            'filter'      => $filter,
            'cafe'        => PengaturanCafe::first(),
            'daftarKasir' => $user->role === 'admin'
                ? User::where('role', 'kasir')->orderBy('nama')->get()
                : collect(),
            'rute'        => $this->rute($user),
        ]);
    }

    public function show(Request $request, Transaksi $transaksi)
    {
        /** @var User $user */
        $user = $request->user();
        $this->otorisasi($user, $transaksi);

        $transaksi->load(['user', 'detail.menu', 'pembayaran']);

        // Siapa dan kapan transaksi dibatalkan, diambil dari catatan stok
        $pembatalan = $transaksi->status === 'batal'
            ? PergerakanStok::with('user')
                ->where('transaksi_id', $transaksi->id)
                ->where('tipe_pergerakan', 'pembatalan')
                ->oldest()
                ->first()
            : null;

        // Jejak stok transaksi ini, hanya untuk admin
        $pergerakan = $user->role === 'admin'
            ? PergerakanStok::with('bahanBaku')->where('transaksi_id', $transaksi->id)->orderBy('id')->get()
            : collect();

        return view('transaksi.show', [
            'transaksi'  => $transaksi,
            'pembayaran' => $transaksi->pembayaran->first(),
            'pembatalan' => $pembatalan,
            'pergerakan' => $pergerakan,
            'bisaBatal'  => $transaksi->bisaDibatalkanOleh($user),
            'rute'       => $this->rute($user),
        ]);
    }

    public function batal(Request $request, Transaksi $transaksi, TransaksiService $service)
    {
        /** @var User $user */
        $user = $request->user();
        $this->otorisasi($user, $transaksi);

        if ($transaksi->status === 'batal') {
            return back()->with('error', 'Transaksi ini sudah dibatalkan sebelumnya.');
        }

        abort_unless($transaksi->bisaDibatalkanOleh($user), 403, 'Anda tidak diizinkan membatalkan transaksi ini.');

        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
            'alasan.min'      => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        try {
            $service->batalkan($transaksi, $user, $data['alasan']);
        } catch (TransaksiGagal $e) {
            return back()->with('error', $e->getMessage());
        }

        AktivitasService::catat('batal', 'transaksi', "Membatalkan transaksi {$transaksi->no_invoice}: {$data['alasan']}");

        // Admin diberi tahu kalau kasir yang membatalkan
        if ($user->role === 'kasir') {
            NotifikasiService::kirimKeRole(
                'admin',
                'batal',
                "Transaksi {$transaksi->no_invoice} dibatalkan",
                "Oleh {$user->nama}: {$data['alasan']}",
                route('transaksi.show', $transaksi)
            );
        }

        // Kembali ke halaman asal (daftar riwayat atau halaman detail), lengkap dengan filternya
        return redirect()
            ->to(url()->previous(route($this->rute($user)['show'], $transaksi)))
            ->with('sukses', 'Transaksi dibatalkan dan stok bahan sudah dikembalikan.');
    }

    public function struk(Request $request, Transaksi $transaksi)
    {
        $this->otorisasi($request->user(), $transaksi);

        $transaksi->load(['user', 'detail.menu', 'pembayaran']);

        return view('kasir.struk', [
            'transaksi'  => $transaksi,
            'pembayaran' => $transaksi->pembayaran->first(),
            'cafe'       => PengaturanCafe::first(),
        ]);
    }

    private function tanggal(?string $nilai): Carbon
    {
        try {
            return $nilai ? Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay() : today();
        } catch (\Throwable $e) {
            return today();
        }
    }

    // Kasir hanya boleh membuka transaksinya sendiri
    private function otorisasi(User $user, Transaksi $transaksi): void
    {
        abort_if(
            $user->role === 'kasir' && $transaksi->user_id !== $user->id,
            403,
            'Anda hanya dapat membuka transaksi milik Anda sendiri.'
        );
    }

    // Nama route berbeda untuk admin dan kasir, tampilannya sama
    private function rute(User $user): array
    {
        return $user->role === 'kasir'
            ? ['index' => 'kasir.riwayat', 'show' => 'kasir.riwayat.show', 'batal' => 'kasir.riwayat.batal', 'struk' => 'kasir.struk']
            : ['index' => 'transaksi.index', 'show' => 'transaksi.show', 'batal' => 'transaksi.batal', 'struk' => 'transaksi.struk'];
    }
}