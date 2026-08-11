@extends('layouts.admin')

@section('page-title', 'Create New Block')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.blocks.index') }}" class="hover:text-gray-900">Blocks</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span class="text-gray-900">Create New Block</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Create New Block</h2>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-sm">
        <form method="POST" action="{{ route('admin.blocks.store') }}">
            @csrf

            <div class="p-6">
                @include('admin.blocks._form')
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end gap-3">
                <a href="{{ route('admin.blocks.index') }}"
                   class="px-6 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Create Block
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
