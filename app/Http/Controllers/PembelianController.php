<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Http\Controllers\Controller;
use App\Models\Pengepul;
use App\Models\SampahPlastik;
use App\Models\TransaksiPembelian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PembelianController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // 
    }

    public function pembelianTransaction($invoice = null){
        $now = Carbon::now('GMT+8')->format('Y-m-d');
        $pengepul = Pengepul::orderBy('name','asc')->get();
        $sampah = SampahPlastik::orderBy('name','asc')->get();

        if ($invoice == null) {
            $random = Carbon::now('GMT+8')->format('YmdHis');
            $count = Pembelian::count();
            $invoice = 'INV-'.$random.$count;
            $data = 'empty';
            $id_pembelian = 0;
            $total = 0;
            return view('page',compact('now','pengepul','sampah','data','invoice','id_pembelian','total'));
        } else {
            $data = TransaksiPembelian::join('sampah_plastiks','transaksi_pembelians.sampah_plastik_id','=','sampah_plastiks.id')
            ->join('pembelians','transaksi_pembelians.pembelian_id','=','pembelians.id')
            ->select('transaksi_pembelians.id as id_transaksi','sampah_plastiks.name','transaksi_pembelians.qty','transaksi_pembelians.satuan','transaksi_pembelians.harga')
            ->where('pembelians.invoice',$invoice)
            ->orderBy('transaksi_pembelians.created_at','desc')
            ->get();

            $id_pembelian = Pembelian::where('invoice',$invoice)->sum('id');
            $total = Pembelian::where('invoice',$invoice)->sum('total');
            return view('page',compact('now','pengepul','sampah','data','invoice','id_pembelian','total'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Cek radio button
        if ($request->berat == "gram") {
            $qty = $request->qty / 1000;
            $satuan = "Gram";
            $harga = $request->qty * $request->hargag;
        } elseif ($request->berat == "kg") {
            $qty = $request->qty;
            $satuan = "Kg";
            $harga = $request->qty * $request->hargakg;
        } else {
            $qty = $request->qty;
            $satuan = "Kg";
            $harga = $request->qty * $request->hargakg;
        }

        // Cek If Invoice already store
        $cekInvoice = Pembelian::where('invoice',$request->invoice)->count();

        if ($cekInvoice > 0) {
            $data = new TransaksiPembelian;
            $data->pembelian_id = $request->id_pembelian;
            $data->sampah_plastik_id = $request->sampah_id;
            $data->date = $request->date;
            $data->qty = $qty;
            $data->satuan = $satuan;
            $data->harga = $harga;
            $data->created_at = Carbon::now('GMT+8')->format('Y-m-d H:i:s');
            $data->save();

            $total = Pembelian::where('id',$request->id_pembelian)->sum('total');
            $total += $harga;

            $pembelian = Pembelian::find($request->id_pembelian);
            $pembelian->total = $total;
            $pembelian->update();

            return redirect('pembelian-transaction/'.$request->invoice)->withInput();
        } else {
            $pembelian = new Pembelian;
            $pembelian->user_id = Auth::user()->id;
            $pembelian->pengepul_id = $request->pengepul_id;
            $pembelian->invoice = $request->invoice;
            $pembelian->date = $request->date;
            $pembelian->total = $harga;
            $pembelian->status = 'unprint';
            $pembelian->save();

            $id_pembelian = Pembelian::where('invoice',$request->invoice)->sum('id');

            $data = new TransaksiPembelian;
            $data->pembelian_id = $id_pembelian;
            $data->sampah_plastik_id = $request->sampah_id;
            $data->date = $request->date;
            $data->qty = $qty;
            $data->satuan = $satuan;
            $data->harga = $harga;
            $data->created_at = Carbon::now('GMT+8')->format('Y-m-d H:i:s');
            $data->save();

            return redirect('pembelian-transaction/'.$request->invoice)->withInput();
        }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Pembelian  $pembelian
     * @return \Illuminate\Http\Response
     */
    public function show(Pembelian $pembelian)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Pembelian  $pembelian
     * @return \Illuminate\Http\Response
     */
    public function edit(Pembelian $pembelian)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Pembelian  $pembelian
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Pembelian $pembelian)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Pembelian  $pembelian
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pembelian $pembelian)
    {
        //
    }
}
