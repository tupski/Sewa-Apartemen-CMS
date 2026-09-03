@extends('layouts.admin')

@section('page-title', 'Voucher')

@section('content')
<div class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Voucher</h2>
            <p class="text-sm text-gray-600 mt-1">Kelola kode diskon untuk booking tamu. (Dapat digunakan hanya jika mengaktifkan metode Booking Form)</p>
        </div>
        <a href="{{ route('admin.vouchers.create') }}"
           class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
            + Tambah Voucher
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Search filter -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" class="flex gap-3">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari kode atau nama voucher..."
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
            <button type="submit"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200 transition">
                Cari
            </button>
            @if(request('search'))
            <a href="{{ route('admin.vouchers.index') }}"
               class="px-4 py-2 bg-gray-50 text-gray-500 text-sm rounded-md hover:bg-gray-100 transition">
                Reset
            </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        @if($vouchers->count())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diskon</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Penggunaan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Masa Berlaku</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($vouchers as $voucher)
                    <tr class="{{ $voucher->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3">
                            <code class="px-2 py-0.5 bg-gray-100 rounded text-sm font-mono font-medium text-gray-800">
                                {{ $voucher->code }}
                            </code>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $voucher->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            @if($voucher->discount_type === 'percent')
                                <span class="font-medium text-blue-700">{{ $voucher->discount_value }}%</span>
                                @if($voucher->max_discount_amount)
                                    <span class="text-xs text-gray-400 block">max Rp {{ number_format($voucher->max_discount_amount, 0, ',', '.') }}</span>
                                @endif
                            @else
                                <span class="font-medium text-green-700">Rp {{ number_format($voucher->discount_value, 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-center text-gray-700">
                            {{ $voucher->used_count }}
                            @if($voucher->usage_limit)
                                / {{ $voucher->usage_limit }}
                            @else
                                <span class="text-gray-400">/ ∞</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if($voucher->valid_from || $voucher->valid_until)
                                {{ $voucher->valid_from?->format('d/m/Y') ?? '—' }}
                                –
                                {{ $voucher->valid_until?->format('d/m/Y') ?? '—' }}
                            @else
                                <span class="text-gray-400">Tidak terbatas</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($voucher->trashed())
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">Dihapus</span>
                            @elseif($voucher->isValid())
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if(!$voucher->trashed())
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.vouchers.edit', $voucher) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs font-medium">Edit</a>
                                <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher) }}"
                                      onsubmit="return confirm('Hapus voucher {{ $voucher->code }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Hapus</button>
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $vouchers->links() }}
        </div>
        @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada voucher</h3>
            <p class="mt-1 text-sm text-gray-500">Buat voucher pertama untuk memberikan diskon kepada tamu.</p>
            <div class="mt-4">
                <a href="{{ route('admin.vouchers.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
                    + Tambah Voucher
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
