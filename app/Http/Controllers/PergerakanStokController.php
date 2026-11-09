<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\PergerakanStok;
use Illuminate\Http\Request;

class PergerakanStokController extends Controller
{
    public function index(Request $request)
    {
        $pergerakan = PergerakanStok::with(['bahanBaku', 'user', 'transaksi', 'barangMasuk'])
            ->when($request->bahan, fn ($q, $v) => $q->where('bahan_baku_id', $v))
            ->when($request->tipe, fn ($q, $v) => $q->where('tipe_pergerakan', $v))
            ->when($request->dari, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->sampai, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $bahan = BahanBaku::orderBy('nama')->get(['id', 'nama']);

        return view('pergerakan-stok.index', compact('pergerakan', 'bahan'));
    }
}