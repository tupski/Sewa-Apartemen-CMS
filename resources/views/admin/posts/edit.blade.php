@extends('layouts.admin')

@section('page-title', 'Edit Post')

@section('content')
<div class="max-w-4xl mx-auto">
    <x-admin-breadcrumb
        :items="[['label' => 'Posts', 'route' => 'admin.posts.index']]"
        current="Edit Post" />

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
