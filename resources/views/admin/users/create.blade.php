@extends('layouts.admin')

@section('page-title', 'Create User')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Create User</h2>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" data-warn-unsaved>
            @csrf
            @include('admin.users._form')
            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition">Create User</button>
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
