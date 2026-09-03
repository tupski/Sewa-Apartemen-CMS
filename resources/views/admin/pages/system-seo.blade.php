@extends('layouts.admin')

@section('page-title', 'SEO: ' . $systemPage->label)

@section('content')
<div class="w-full max-w-4xl">
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('admin.pages.index') }}"
           class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-3">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Kembali ke Pages
        </a>
        <h2 class="text-2xl font-bold text-gray-800">SEO: {{ $systemPage->label }}</h2>
        <p class="text-sm text-gray-600 mt-1">
            Halaman ini tidak dibuat lewat CMS. Kosongkan kolom untuk memakai nilai default aplikasi.
            <span class="font-mono text-xs text-gray-400">({{ $systemPage->key }})</span>
        </p>
    </div>

    @if(session('error'))
        <div class="mb-6 px-4 py-3 rounded-md bg-red-50 border border-red-200 text-red-700 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm">
        <form method="POST" action="{{ route('admin.pages.system-seo.update', $systemPage) }}" data-warn-unsaved>
            @csrf
            @method('PUT')

            <div class="p-6 space-y-8">

                @if($systemPage->supportsPlaceholders())
                    <div class="px-4 py-3 rounded-md bg-blue-50 border border-blue-200 text-blue-800 text-sm">
                        <p class="font-semibold mb-1">Halaman template</p>
                        <p>
                            Nilai di bawah dipakai untuk SEMUA detail apartemen yang belum punya SEO sendiri.
                            Gunakan placeholder berikut:
                        </p>
                        <ul class="mt-2 space-y-0.5 font-mono text-xs">
                            <li><code>:name</code> — nama apartemen</li>
                            <li><code>:city</code> — kota</li>
                            <li><code>:province</code> — provinsi</li>
                            <li><code>:price</code> — harga terendah (mis. "Rp 150.000")</li>
                        </ul>
                        <p class="mt-2 text-xs">
                            Contoh: <code class="font-mono">Sewa :name :city Harian — Mulai :price</code>.
                            SEO per-apartemen di halaman edit properti selalu menang atas template ini.
                        </p>
                    </div>
                @endif

                {{-- ── Meta Tags ── --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Meta Tags</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="seo_meta_title" class="block text-sm font-medium text-gray-700 mb-2">
                                Meta Title
                            </label>
                            <input type="text"
                                   name="seo[meta_title]"
                                   id="seo_meta_title"
                                   maxlength="255"
                                   value="{{ old('seo.meta_title', $systemPage->seo->meta_title ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">
                                Disarankan 50–60 karakter. Nama situs otomatis ditambahkan di akhir.
                            </p>
                            @error('seo.meta_title')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="seo_meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                                Meta Description
                            </label>
                            <textarea name="seo[meta_description]"
                                      id="seo_meta_description"
                                      rows="3"
                                      maxlength="320"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('seo.meta_description', $systemPage->seo->meta_description ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Disarankan 150–160 karakter.</p>
                            @error('seo.meta_description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="seo_canonical_url" class="block text-sm font-medium text-gray-700 mb-2">
                                Canonical URL
                            </label>
                            <input type="url"
                                   name="seo[canonical_url]"
                                   id="seo_canonical_url"
                                   maxlength="2048"
                                   placeholder="https://contoh.com/halaman"
                                   value="{{ old('seo.canonical_url', $systemPage->seo->canonical_url ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Kosongkan untuk memakai URL halaman saat ini.</p>
                            @error('seo.canonical_url')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-md">
                            <input type="hidden" name="seo[index_status]" value="0">
                            <input type="checkbox"
                                   name="seo[index_status]"
                                   id="seo_index_status"
                                   value="1"
                                   {{ old('seo.index_status', $systemPage->seo->index_status ?? true) ? 'checked' : '' }}
                                   class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 shrink-0">
                            <label for="seo_index_status" class="text-sm text-gray-700 cursor-pointer">
                                Izinkan mesin pencari mengindeks halaman ini
                                <span class="block text-xs text-gray-500">Jika dimatikan, halaman dikirim dengan <code class="font-mono">noindex, follow</code>.</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- ── Open Graph ── --}}
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Open Graph (Facebook / WhatsApp)</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Kosongkan untuk memakai Meta Title / Description di atas.
                    </p>
                    <div class="space-y-4">
                        <div>
                            <label for="og_title" class="block text-sm font-medium text-gray-700 mb-2">OG Title</label>
                            <input type="text"
                                   name="seo[open_graph][title]"
                                   id="og_title"
                                   maxlength="255"
                                   value="{{ old('seo.open_graph.title', $systemPage->seo->open_graph['title'] ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            @error('seo.open_graph.title')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="og_description" class="block text-sm font-medium text-gray-700 mb-2">OG Description</label>
                            <textarea name="seo[open_graph][description]"
                                      id="og_description"
                                      rows="2"
                                      maxlength="320"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('seo.open_graph.description', $systemPage->seo->open_graph['description'] ?? '') }}</textarea>
                            @error('seo.open_graph.description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="og_image" class="block text-sm font-medium text-gray-700 mb-2">OG Image</label>
                            <input type="text"
                                   name="seo[open_graph][image]"
                                   id="og_image"
                                   maxlength="2048"
                                   placeholder="media/og-image.jpg atau https://..."
                                   value="{{ old('seo.open_graph.image', $systemPage->seo->open_graph['image'] ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">
                                Path relatif storage atau URL absolut. Kosongkan untuk memakai OG image sitewide.
                                Rekomendasi 1200×630 px.
                            </p>
                            @error('seo.open_graph.image')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ── Twitter ── --}}
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Twitter / X Card</h3>
                    <p class="text-sm text-gray-500 mb-4">Kosongkan untuk mengikuti nilai Open Graph.</p>
                    <div class="space-y-4">
                        <div>
                            <label for="tw_title" class="block text-sm font-medium text-gray-700 mb-2">Twitter Title</label>
                            <input type="text"
                                   name="seo[twitter][title]"
                                   id="tw_title"
                                   maxlength="255"
                                   value="{{ old('seo.twitter.title', $systemPage->seo->twitter['title'] ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            @error('seo.twitter.title')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tw_description" class="block text-sm font-medium text-gray-700 mb-2">Twitter Description</label>
                            <textarea name="seo[twitter][description]"
                                      id="tw_description"
                                      rows="2"
                                      maxlength="320"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('seo.twitter.description', $systemPage->seo->twitter['description'] ?? '') }}</textarea>
                            @error('seo.twitter.description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tw_image" class="block text-sm font-medium text-gray-700 mb-2">Twitter Image</label>
                            <input type="text"
                                   name="seo[twitter][image]"
                                   id="tw_image"
                                   maxlength="2048"
                                   placeholder="media/twitter-image.jpg atau https://..."
                                   value="{{ old('seo.twitter.image', $systemPage->seo->twitter['image'] ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            @error('seo.twitter.image')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

            </div>

            {{-- Actions --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end gap-3">
                <a href="{{ route('admin.pages.index') }}"
                   class="px-6 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Simpan SEO
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
