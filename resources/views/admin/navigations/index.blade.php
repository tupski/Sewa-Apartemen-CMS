@extends('layouts.admin')

@section('page-title', 'Navigation Menus')

@section('content')
<div class="w-full">
    <!-- Header with Actions -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Navigation Menus</h2>
            <p class="text-sm text-gray-600 mt-1">Manage your site navigation structure</p>
        </div>
        <a href="{{ route('admin.navigations.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Menu Item
        </a>
    </div>

    <!-- Menu Locations Tabs -->
    <div x-data="{ activeLocation: 'main' }" class="bg-white rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="activeLocation = 'main'"
                        :class="activeLocation === 'main' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    Main Menu
                </button>
                <button @click="activeLocation = 'footer'"
                        :class="activeLocation === 'footer' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    Footer Menu
                </button>
                <button @click="activeLocation = 'sidebar'"
                        :class="activeLocation === 'sidebar' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 border-b-2 font-medium text-sm transition">
                    Sidebar Menu
                </button>
            </nav>
        </div>

        <!-- Main Menu Content -->
        <div x-show="activeLocation === 'main'" class="p-6">
            @php
                $mainMenuItems = $navigations->where('location', 'main')->sortBy('order');
            @endphp

            @if($mainMenuItems->count() > 0)
                <div class="space-y-2">
                    @foreach($mainMenuItems->where('parent_id', null) as $item)
                        @include('admin.navigations._menu-item', ['item' => $item, 'children' => $mainMenuItems->where('parent_id', $item->id)])
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500">No menu items yet. Add your first item to get started.</p>
                </div>
            @endif
        </div>

        <!-- Footer Menu Content -->
        <div x-show="activeLocation === 'footer'" class="p-6" style="display: none;">
            @php
                $footerMenuItems = $navigations->where('location', 'footer')->sortBy('order');
            @endphp

            @if($footerMenuItems->count() > 0)
                <div class="space-y-2">
                    @foreach($footerMenuItems->where('parent_id', null) as $item)
                        @include('admin.navigations._menu-item', ['item' => $item, 'children' => $footerMenuItems->where('parent_id', $item->id)])
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500">No menu items yet. Add your first item to get started.</p>
                </div>
            @endif
        </div>

        <!-- Sidebar Menu Content -->
        <div x-show="activeLocation === 'sidebar'" class="p-6" style="display: none;">
            @php
                $sidebarMenuItems = $navigations->where('location', 'sidebar')->sortBy('order');
            @endphp

            @if($sidebarMenuItems->count() > 0)
                <div class="space-y-2">
                    @foreach($sidebarMenuItems->where('parent_id', null) as $item)
                        @include('admin.navigations._menu-item', ['item' => $item, 'children' => $sidebarMenuItems->where('parent_id', $item->id)])
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500">No menu items yet. Add your first item to get started.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Instructions -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Menu Management Tips</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Create menu items and assign them to different locations (Main, Footer, Sidebar)</li>
                        <li>Use parent-child relationships to create dropdown menus</li>
                        <li>Drag and drop items to reorder them (feature coming soon)</li>
                        <li>Toggle active/inactive status to show or hide menu items</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Drag and drop functionality can be added here in the future
    // For now, manual ordering through the form works
</script>
@endpush
@endsection
