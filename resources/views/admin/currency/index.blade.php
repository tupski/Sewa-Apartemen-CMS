@extends('layouts.admin')
@section('page-title', 'Kurs Mata Uang')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">Kurs Mata Uang</h1>
        <form method="POST" action="{{ route('admin.currency-rates.fetch') }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition">
                <i class="fa-solid fa-rotate"></i> Fetch dari API
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form tambah rate manual --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4 text-sm">Tambah / Update Rate Manual</h3>
            <form method="POST" action="{{ route('admin.currency-rates.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kode Mata Uang</label>
                    <input type="text" name="to_currency" maxlength="10" placeholder="USD, SGD, EUR..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kurs (IDR per 1 unit)</label>
                    <input type="number" name="rate" step="0.0001" min="0.0001" placeholder="15500"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <p class="text-xs text-gray-400 mt-1">Contoh: USD → 15500 berarti 1 USD = Rp 15.500</p>
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                    Simpan Rate
                </button>
            </form>
        </div>

        {{-- Tabel rates --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-700 dark:text-gray-200 text-sm">Rate Tersimpan (Base: IDR)</h3>
                <span class="text-xs text-gray-400">Diperbarui max 4x/hari via cron</span>
            </div>
            @if($rates->count())
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3 text-left">Mata Uang</th>
                        <th class="px-5 py-3 text-right">1 unit = IDR</th>
                        <th class="px-5 py-3 text-center">Sumber</th>
                        <th class="px-5 py-3 text-center">Diperbarui</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($rates as $rate)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <td class="px-5 py-3 font-bold font-mono text-gray-800 dark:text-white">{{ $rate->to_currency }}</td>
                        <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">
                            Rp {{ number_format($rate->rate, 2, ',', '.') }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $rate->source === 'manual' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $rate->source ?? 'api' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center text-xs text-gray-400">
                            {{ $rate->fetched_at ? $rate->fetched_at->diffForHumans() : $rate->updated_at->diffForHumans() }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('admin.currency-rates.destroy', $rate) }}"
                                  onsubmit="return confirm('Hapus rate {{ $rate->to_currency }}?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="px-5 py-12 text-center">
                <p class="text-sm text-gray-400 mb-3">Belum ada data kurs.</p>
                <p class="text-xs text-gray-400">Klik "Fetch dari API" atau tambah manual.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Info API settings --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 text-sm text-blue-700 dark:text-blue-300">
        <p class="font-semibold mb-1"><i class="fa-solid fa-circle-info mr-1"></i> Tentang API Kurs</p>
        <p class="text-xs">Rate diperbarui otomatis maksimal 4x sehari via scheduler. Sumber default: <strong>frankfurter.app</strong> (gratis, tanpa API key).
        Untuk API berbayar (Fixer, OpenExchangeRates), set <code>currency_api_key</code> dan <code>currency_api_url</code> di Settings → Integrations.</p>
    </div>
</div>
@endsection
