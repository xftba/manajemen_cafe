<?php

namespace App\Http\Controllers;

use App\Services\PenjualanService;
use Illuminate\Http\Request;

class AnalitikController extends Controller
{
    public function index(Request $request, PenjualanService $p)
    {
        $kasirId = $request->user()->id;   // kasir hanya melihat transaksinya sendiri

        [$dari, $sampai] = $p->rentang(
            $request->query('dari'),
            $request->query('sampai'),
            today()->startOfMonth(),
            today()
        );

        return view('kasir.analitik', [
            'dari'      => $dari,
            'sampai'    => $sampai,
            'ringkasan' => $p->ringkasan($dari, $sampai, $kasirId),
            'harian'    => $p->perHari($dari, $sampai, $kasirId),
            'metode'    => $p->perMetode($dari, $sampai, $kasirId),
            'tipe'      => $p->perTipe($dari, $sampai, $kasirId),
            'terlaris'  => $p->menuTerlaris($dari, $sampai, $kasirId, 10),
        ]);
    }
}