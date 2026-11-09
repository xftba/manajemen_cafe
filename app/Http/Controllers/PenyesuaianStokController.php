<?php

namespace App\Http\Controllers;

use App\Exceptions\TransaksiGagal;
use App\Models\BahanBaku;
use App\Models\PenyesuaianStok;
use App\Services\AktivitasService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenyesuaianStokController extends Controller
{
   public function index(Request $request)
    {
        $penyesuaian = PenyesuaianStok::with(['bahanBaku', 'pengaju', 'penyetuju'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->bahan, fn ($q, $v) => $q->where('bahan_baku_id', $v))
            ->orderByRaw("status = 'pending' desc")
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $bahan    = BahanBaku::orderBy('nama')->get(['id', 'nama', 'satuan', 'stok']);
        $menunggu = PenyesuaianStok::where('status', 'pending')->count();
        $pending  = PenyesuaianStok::where('status', 'pending')->pluck('bahan_baku_id')->all();

        return view('penyesuaian-stok.index', compact('penyesuaian', 'bahan', 'menunggu', 'pending'));
    }

    // Form ada di panel kanan halaman index
    public function create()
    {
        return redirect()->route('penyesuaian-stok.index');
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'bahan_baku_id' => ['required', 'exists:bahan_baku,id'],
            'stok_fisik'    => ['required', 'numeric', 'min:0', 'max:9999999'],
            'alasan'        => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'bahan_baku_id.required' => 'Pilih bahan baku.',
            'stok_fisik.required'    => 'Isi stok fisik hasil penghitungan.',
            'alasan.required'        => 'Alasan penyesuaian wajib diisi.',
            'alasan.min'             => 'Alasan minimal 5 karakter.',
        ]);

        try {
            $p = DB::transaction(function () use ($data) {
                // Kunci baris bahan supaya stok sistem yang tercatat adalah angka yang sedang berlaku
                $bahan = BahanBaku::lockForUpdate()->findOrFail($data['bahan_baku_id']);

                if (PenyesuaianStok::where('bahan_baku_id', $bahan->id)->where('status', 'pending')->exists()) {
                    throw new TransaksiGagal("Masih ada pengajuan {$bahan->nama} yang menunggu persetujuan admin.");
                }

                if (abs((float) $bahan->stok - (float) $data['stok_fisik']) < 0.005) {
                    throw new TransaksiGagal('Stok fisik sama dengan stok sistem, jadi tidak perlu penyesuaian.');
                }

                return PenyesuaianStok::create([
                    'bahan_baku_id' => $bahan->id,
                    'diajukan_oleh' => auth()->id(),
                    'stok_sistem'   => $bahan->stok,
                    'stok_fisik'    => $data['stok_fisik'],
                    'alasan'        => $data['alasan'],
                    'status'        => 'pending',
                ]);
            });
        } catch (TransaksiGagal $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $p->load('bahanBaku');
        AktivitasService::catat(
            'ajukan',
            'penyesuaian stok',
            "Mengajukan penyesuaian {$p->bahanBaku->nama}: sistem " . BahanBaku::angka($p->stok_sistem) . ", fisik " . BahanBaku::angka($p->stok_fisik)
        );

        return redirect()->route('penyesuaian-stok.index')
            ->with('sukses', 'Pengajuan terkirim dan menunggu persetujuan admin.');
    }

    public function setujui(PenyesuaianStok $penyesuaianStok, StokService $stok)
    {
        try {
            $p = DB::transaction(function () use ($penyesuaianStok, $stok) {
                // Kunci pengajuan supaya dua admin tidak menyetujui bersamaan
                $p = PenyesuaianStok::lockForUpdate()->findOrFail($penyesuaianStok->id);

                if ($p->status !== 'pending') {
                    throw new TransaksiGagal('Pengajuan ini sudah diproses sebelumnya.');
                }

                $stok->ubahManual(
                    $p->bahan_baku_id,
                    $p->selisih,
                    'penyesuaian',
                    $p->id,
                    'Penyesuaian stok disetujui: ' . $p->alasan
                );

                $p->update([
                    'status'         => 'disetujui',
                    'disetujui_oleh' => auth()->id(),
                ]);

                return $p;
            });
        } catch (TransaksiGagal $e) {
            return back()->with('error', $e->getMessage());
        }

        $p->load('bahanBaku');
        AktivitasService::catat('setujui', 'penyesuaian stok', "Menyetujui penyesuaian {$p->bahanBaku->nama} (selisih " . BahanBaku::angka($p->selisih) . ')');

        return back()->with('sukses', 'Penyesuaian disetujui dan stok bahan sudah diperbarui.');
    }

    public function tolak(Request $request, PenyesuaianStok $penyesuaianStok)
    {
        $data = $request->validate([
            'catatan_admin' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'catatan_admin.required' => 'Alasan penolakan wajib diisi.',
            'catatan_admin.min'      => 'Alasan penolakan minimal 5 karakter.',
        ]);

        try {
            $p = DB::transaction(function () use ($penyesuaianStok, $data) {
                $p = PenyesuaianStok::lockForUpdate()->findOrFail($penyesuaianStok->id);

                if ($p->status !== 'pending') {
                    throw new TransaksiGagal('Pengajuan ini sudah diproses sebelumnya.');
                }

                $p->update([
                    'status'         => 'ditolak',
                    'disetujui_oleh' => auth()->id(),
                    'catatan_admin'  => $data['catatan_admin'],
                ]);

                return $p;
            });
        } catch (TransaksiGagal $e) {
            return back()->with('error', $e->getMessage());
        }

        $p->load('bahanBaku');
        AktivitasService::catat('tolak', 'penyesuaian stok', "Menolak penyesuaian {$p->bahanBaku->nama}: {$data['catatan_admin']}");

        return back()->with('sukses', 'Pengajuan ditolak. Stok tidak berubah.');
    }
}