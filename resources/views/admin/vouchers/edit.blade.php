@extends('layouts.admin')

@section('page-title', 'Edit Voucher — ' . $voucher->code)

@section('content')
<div class="w-full max-w-3xl">
    <!-- Header -->
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.vouchers.index') }}"
           class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Voucher</h2>
            <p class="text-sm text-gray-600 mt-0.5">
                <code class="px-1.5 py-0.5 bg-gray-100 rounded font-mono">{{ $voucher->code }}</code>
                — digunakan {{ $voucher->used_count }}× kali
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6"
         x-data="{ discountType: '{{ old('discount_type', $voucher->discount_type) }}' }">
        <form method="POST" action="{{ route('admin.vouchers.update', $voucher) }}" data-warn-unsaved>
            @csrf @method('PUT')

            @include('admin.vouchers._form')

            <div class="mt-8 flex items-center gap-3 pt-6 border-t border-gray-200">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Simpan Perubahan
                </button>
                <a href="{{ route('admin.vouchers.index') }}"
                   class="px-6 py-2 bg-gray-100 text-gray-700 font-medium rounded-md hover:bg-gray-200 transition">
                    Batal
                </a>
                <div class="ml-auto">
                    <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher) }}"
                          onsubmit="return confirm('Hapus voucher {{ $voucher->code }}? Tindakan ini tidak dapat dibatalkan.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-red-600 border border-red-200 text-sm font-medium rounded-md hover:bg-red-50 transition">
                            Hapus Voucher
                        </button>
                    </form>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
