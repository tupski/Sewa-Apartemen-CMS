@extends('layouts.admin')

@section('page-title', 'Edit Category')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.categories.index') }}" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Categories</a>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Category</h2>
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" data-warn-unsaved>
            @csrf
            @method('PUT')
            @include('admin.categories._form')
        </form>
    </div>
</div>
@endsection
