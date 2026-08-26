@extends('layouts.admin')

@section('page-title', 'Tambah Voucher')

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
            <h2 class="text-2xl font-bold text-gray-800">Tambah Voucher</h2>
            <p class="text-sm text-gray-600 mt-0.5">Buat kode diskon baru untuk tamu</p>
        </div>
    </div>

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
         x-data="{ discountType: '{{ old('discount_type', 'percent') }}' }">
        <form method="POST" action="{{ route('admin.vouchers.store') }}" data-warn-unsaved>
            @csrf

            @include('admin.vouchers._form')

            <div class="mt-8 flex items-center gap-3 pt-6 border-t border-gray-200">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Simpan Voucher
                </button>
                <a href="{{ route('admin.vouchers.index') }}"
                   class="px-6 py-2 bg-gray-100 text-gray-700 font-medium rounded-md hover:bg-gray-200 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
