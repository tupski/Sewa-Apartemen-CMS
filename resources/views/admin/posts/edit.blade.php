@extends('layouts.admin')

@section('page-title', 'Edit Post')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.posts.index') }}" class="text-blue-600 hover:text-blue-900 text-sm">
            &larr; Back to Posts
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Post</h2>

        <form action="{{ route('admin.posts.update', $post) }}" method="POST" enctype="multipart/form-data" data-warn-unsaved>
            @csrf
            @method('PUT')
            @include('admin.posts._form')
        </form>
    </div>
</div>
@endsection
