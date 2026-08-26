@extends('layouts.admin')

@section('page-title', 'Edit Amenity')

@section('content')
<div class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Amenity</h2>
            <p class="text-sm text-gray-600 mt-1">Update amenity details</p>
        </div>
        <a href="{{ route('admin.amenities.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-3xl">
        <form action="{{ route('admin.amenities.update', $amenity) }}" method="POST" data-warn-unsaved>
            @csrf
            @method('PUT')
            @include('admin.amenities._form', ['amenity' => $amenity])

            <div class="mt-8 flex items-center gap-3 border-t border-gray-200 pt-6">
                <button type="submit"
                        class="inline-flex items-center px-6 py-2.5 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    <i class="fa-solid fa-check mr-2"></i> Update Amenity
                </button>
                <a href="{{ route('admin.amenities.index') }}"
                   class="px-6 py-2.5 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
