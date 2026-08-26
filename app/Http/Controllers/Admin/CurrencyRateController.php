<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyRate;
use App\Services\CurrencyRateService;
use Illuminate\Http\Request;

class CurrencyRateController extends Controller
{
    public function index()
    {
        $rates = CurrencyRate::orderBy('to_currency')->get();
        return view('admin.currency.index', compact('rates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'to_currency' => 'required|string|max:10',
            'rate'        => 'required|numeric|min:0.000001',
        ]);
        CurrencyRateService::setManual(strtoupper($request->to_currency), (float) $request->rate);
        return back()->with('success', 'Rate berhasil disimpan.');
    }

    public function update(Request $request, CurrencyRate $currencyRate)
    {
        $request->validate(['rate' => 'required|numeric|min:0.000001']);
        CurrencyRateService::setManual($currencyRate->to_currency, (float) $request->rate);
        return back()->with('success', 'Rate berhasil diperbarui.');
    }

    public function destroy(CurrencyRate $currencyRate)
    {
        CurrencyRateService::delete($currencyRate->to_currency);
        return back()->with('success', 'Rate dihapus.');
    }

    public function fetchNow()
    {
        $ok = CurrencyRateService::fetchAndStore();
        return back()->with($ok ? 'success' : 'error', $ok ? 'Rate berhasil diperbarui dari API.' : 'Gagal fetch rate dari API.');
    }
}
