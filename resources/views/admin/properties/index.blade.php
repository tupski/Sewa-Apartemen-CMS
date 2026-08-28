@extends('layouts.admin')

@section('page-title', 'Properties')

@section('content')
<div class="w-full" x-data="bulkSelect()">
    <!-- Header with Actions -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Apartemen</h2>
            <p class="text-sm text-gray-600 mt-1">Halaman ini untuk mengelola lokasi apartemen</p>
        </div>
        <a href="{{ route('admin.properties.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Lokasi Apartemen Baru
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('admin.properties.index') }}" class="flex flex-col md:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <input type="text"
                       name="search"
                       placeholder="Search by name..."
                       value="{{ request('search') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Status Filter -->
            <div class="w-full md:w-48">
                <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draf</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Dipublish</option>
                </select>
            </div>

            <!-- City Filter -->
            <div class="w-full md:w-48">
                <input type="text"
                       name="city"
                       placeholder="Filter by city..."
                       value="{{ request('city') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Filter Button -->
            <button type="submit"
                    class="px-6 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                Filter
            </button>

            <!-- Reset Button -->
            @if(request()->hasAny(['search', 'status', 'city']))
                <a href="{{ route('admin.properties.index') }}"
                   class="px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition text-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Bulk Action Toolbar (appears when items are selected) -->
    <div x-show="selectedIds.length > 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 mb-4 flex flex-col sm:flex-row sm:items-center gap-3"
         style="display:none">
        <span class="text-sm font-medium text-blue-800">
            <span x-text="selectedIds.length"></span> selected
        </span>
        <div class="flex items-center gap-2 flex-wrap">
            <select x-model="bulkAction"
                    class="px-3 py-1.5 text-sm border border-blue-300 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white">
                <option value="">-- Select Action --</option>
                <option value="publish">Publish</option>
                <option value="draft">Draft</option>
                <option value="feature">Feature</option>
                <option value="unfeature">Unfeature</option>
                <option value="delete">Delete</option>
            </select>
            <button @click="applyBulkAction()"
                    :disabled="!bulkAction || applying"
                    class="px-4 py-1.5 text-sm bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition">
                <span x-show="!applying">Apply</span>
                <span x-show="applying" x-cloak>Applying...</span>
            </button>
            <button @click="clearSelection()"
                    class="px-3 py-1.5 text-sm text-blue-700 hover:text-blue-900 font-medium transition">
                Clear
            </button>
        </div>
    </div>

    <!-- Properties Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        @if($properties->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <!-- Checkbox column -->
                            <th scope="col" class="px-4 py-3 w-10">
                                <input type="checkbox"
                                       @change="toggleAll($event)"
                                       :checked="allSelected"
                                       :indeterminate="someSelected && !allSelected"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                       aria-label="Select all">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                City
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Units
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Featured
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($properties as $property)
                            <tr class="hover:bg-gray-50" :class="selectedIds.includes({{ $property->id }}) ? 'bg-blue-50' : ''">
                                <!-- Row checkbox -->
                                <td class="px-4 py-4">
                                    <input type="checkbox"
                                           :value="{{ $property->id }}"
                                           @change="toggleItem({{ $property->id }})"
                                           :checked="selectedIds.includes({{ $property->id }})"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                           aria-label="Select {{ $property->name }}">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('admin.properties.edit', $property) }}"
                                       class="text-sm font-medium text-blue-600 hover:text-blue-900">
                                        {{ $property->name }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-500">{{ $property->city ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-500">{{ count($property->unit_types ?? []) }} room types</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select onchange="updateStatus({{ $property->id }}, this.value)"
                                            class="px-3 py-1 text-xs font-semibold rounded-full border-0 focus:ring-2 focus:ring-blue-500 cursor-pointer
                                                {{ $property->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        <option value="published" {{ $property->status === 'published' ? 'selected' : '' }}>Published</option>
                                        <option value="draft" {{ $property->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button type="button"
                                            onclick="toggleFeatured({{ $property->id }}, this)"
                                            class="text-2xl focus:outline-none transition-colors duration-200"
                                            title="{{ $property->is_featured ? 'Remove from featured' : 'Mark as featured' }}">
                                        <i class="{{ $property->is_featured ? 'fas text-yellow-400' : 'far text-gray-300 hover:text-yellow-400' }} fa-star"></i>
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.properties.edit', $property) }}"
                                           class="text-blue-600 hover:text-blue-900 px-2 py-1 rounded hover:bg-blue-50 transition"
                                           title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.properties.destroy', $property) }}"
                                              method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this property?')"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-600 hover:text-red-900 px-2 py-1 rounded hover:bg-red-50 transition"
                                                    title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($properties->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $properties->withQueryString()->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Properti tidak ditemukan!</h3>
                <p class="mt-1 text-sm text-gray-500">Silakan coba buat properti apartemen baru.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.properties.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Buat Apartemen Baru
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// ─── Bulk Select Alpine component ──────────────────────────────────────────
function bulkSelect() {
    return {
        selectedIds: [],
        bulkAction: '',
        applying: false,
        allPropertyIds: @json($properties->pluck('id')),

        get allSelected() {
            return this.allPropertyIds.length > 0 &&
                   this.allPropertyIds.every(id => this.selectedIds.includes(id));
        },

        get someSelected() {
            return this.selectedIds.length > 0;
        },

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = [...this.allPropertyIds];
            } else {
                this.selectedIds = [];
            }
        },

        toggleItem(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx === -1) {
                this.selectedIds.push(id);
            } else {
                this.selectedIds.splice(idx, 1);
            }
        },

        clearSelection() {
            this.selectedIds = [];
            this.bulkAction = '';
        },

        applyBulkAction() {
            if (!this.bulkAction || this.selectedIds.length === 0) return;

            if (this.bulkAction === 'delete') {
                if (!confirm(`Delete ${this.selectedIds.length} selected properties? This cannot be undone.`)) return;
            }

            this.applying = true;

            fetch('{{ route('admin.properties.bulk-action') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: this.bulkAction,
                    ids: this.selectedIds
                })
            })
            .then(r => r.json())
            .then(data => {
                this.applying = false;
                if (data.success) {
                    window.toast(data.message, 'success');
                    // Reload after short delay to reflect changes
                    setTimeout(() => location.reload(), 800);
                } else {
                    window.toast(data.message || 'Action failed', 'error');
                }
            })
            .catch(() => {
                this.applying = false;
                window.toast('Failed to apply bulk action', 'error');
            });
        }
    };
}

// ─── Individual toggle featured ───────────────────────────────────────────
function toggleFeatured(propertyId, button) {
    const icon = button.querySelector('i');
    fetch(`/admin/properties/${propertyId}/featured`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.is_featured) {
            icon.classList.remove('far', 'text-gray-300', 'hover:text-yellow-400');
            icon.classList.add('fas', 'text-yellow-400');
            button.title = 'Remove from featured';
        } else {
            icon.classList.remove('fas', 'text-yellow-400');
            icon.classList.add('far', 'text-gray-300', 'hover:text-yellow-400');
            button.title = 'Mark as featured';
        }
    })
    .catch(err => console.error('Failed to toggle featured:', err));
}

// ─── Individual status update ─────────────────────────────────────────────
function updateStatus(propertyId, status) {
    const select = event.target;
    fetch(`/admin/properties/${propertyId}/status`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status })
    })
    .then(res => res.json())
    .then(data => {
        if (status === 'published') {
            select.classList.remove('bg-gray-100', 'text-gray-800');
            select.classList.add('bg-green-100', 'text-green-800');
        } else {
            select.classList.remove('bg-green-100', 'text-green-800');
            select.classList.add('bg-gray-100', 'text-gray-800');
        }
        window.toast('Status updated', 'success');
    })
    .catch(err => {
        console.error('Failed to update status:', err);
        location.reload();
    });
}
</script>
@endpush
@endsection
