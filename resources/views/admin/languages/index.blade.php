@extends('layouts.admin')
@section('page-title', 'Manajemen Bahasa')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">Manajemen Bahasa</h1>
        <a href="{{ route('admin.languages.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
            <i class="fa-solid fa-plus"></i> Tambah Bahasa
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                <tr>
                    <th class="px-5 py-3 text-left">Urutan</th>
                    <th class="px-5 py-3 text-left">Bendera</th>
                    <th class="px-5 py-3 text-left">Kode</th>
                    <th class="px-5 py-3 text-left">Nama</th>
                    <th class="px-5 py-3 text-left">Nama Lokal</th>
                    <th class="px-5 py-3 text-center">Default</th>
                    <th class="px-5 py-3 text-center">Aktif</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($languages as $lang)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-5 py-3 text-gray-500">{{ $lang->sort_order }}</td>
                    <td class="px-5 py-3 text-2xl">
                        @if($lang->flag)
                            <span class="leading-none" title="{{ $lang->flag_code ?: strtoupper($lang->code) }}">{{ $lang->flag }}</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 font-mono font-bold text-gray-800 dark:text-white uppercase">{{ $lang->code }}</td>
                    <td class="px-5 py-3 text-gray-700 dark:text-gray-200">{{ $lang->name }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $lang->native_name }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($lang->is_default)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full font-medium">
                                <i class="fa-solid fa-star text-xs"></i> Default
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        <button type="button"
                                data-id="{{ $lang->id }}"
                                data-active="{{ $lang->is_active ? '1' : '0' }}"
                                onclick="toggleLanguage(this)"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                                       {{ $lang->is_active ? 'bg-green-500' : 'bg-gray-300' }}">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                         {{ $lang->is_active ? 'translate-x-4' : 'translate-x-1' }}"></span>
                        </button>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.languages.edit', $lang) }}"
                               class="text-xs px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 transition">
                                Edit
                            </a>
                            @if(!$lang->is_default)
                            <form method="POST" action="{{ route('admin.languages.destroy', $lang) }}"
                                  onsubmit="return confirm('Hapus bahasa {{ $lang->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs px-3 py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">Hapus</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-10 text-center text-gray-400">Belum ada bahasa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleLanguage(btn) {
    const id     = btn.dataset.id;
    const active = btn.dataset.active === '1';
    fetch(`/admin/languages/${id}/toggle-status`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { alert(data.message); return; }
        btn.dataset.active = data.is_active ? '1' : '0';
        btn.classList.toggle('bg-green-500', data.is_active);
        btn.classList.toggle('bg-gray-300', !data.is_active);
        const dot = btn.querySelector('span');
        dot.classList.toggle('translate-x-4', data.is_active);
        dot.classList.toggle('translate-x-1', !data.is_active);
    });
}
</script>
@endpush
