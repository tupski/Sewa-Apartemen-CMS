@extends('layouts.admin')
@section('page-title', 'Tambah Bahasa')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('admin.languages.index') }}" class="text-sm text-blue-600 hover:underline">← Kembali</a>
        <h1 class="text-xl font-bold text-gray-800 dark:text-white mt-1">Tambah Bahasa</h1>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.languages.store') }}" data-warn-unsaved>
            @csrf
            @include('admin.languages._form')
        </form>
    </div>
</div>
@endsection
