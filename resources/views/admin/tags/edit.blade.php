@extends('layouts.admin')

@section('page-title', 'Edit Tag')

@section('content')
<div class="max-w-2xl mx-auto">
    <x-admin-breadcrumb
        :items="[['label' => 'Tags', 'route' => 'admin.tags.index']]"
        current="Edit Tag" />
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Tag</h2>
        <form action="{{ route('admin.tags.update', $tag) }}" method="POST" data-warn-unsaved>
            @csrf
            @method('PUT')
            @include('admin.tags._form')
        </form>
    </div>
</div>
@endsection
