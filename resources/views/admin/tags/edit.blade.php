@extends('layouts.admin')

@section('page-title', 'Edit Tag')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.tags.index') }}" class="text-blue-600 hover:text-blue-900 text-sm">&larr; Back to Tags</a>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Tag</h2>
        <form action="{{ route('admin.tags.update', $tag) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.tags._form')
        </form>
    </div>
</div>
@endsection
