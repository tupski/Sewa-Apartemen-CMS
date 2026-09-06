@extends('layouts.admin')

@section('page-title', 'Edit Category')

@section('content')
<div class="max-w-2xl mx-auto">
    <x-admin-breadcrumb
        :items="[['label' => 'Categories', 'route' => 'admin.categories.index']]"
        current="Edit Category" />
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
