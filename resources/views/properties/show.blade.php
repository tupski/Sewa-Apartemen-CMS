@extends('layouts.frontend')

@section('content')
    @php
        $primaryColor = \App\Services\SettingsService::get('primary_color', '#3b82f6');
        $secondaryColor = \App\Services\SettingsService::get('secondary_color', '#10b981');
        $whatsapp = \App\Services\SettingsService::get('whatsapp_default', '');
        $contactPhone = \App\Services\SettingsService::get('contact_phone', '');
    @endphp

    <!-- Hero Image -->
    <section class="relative bg-gray-900">
        @if ($property->featuredImage)
            <img src="{{ $property->featuredImage->url }}" alt="{{ $property->name }}"
                 class="w-full h-[40vh] md:h-[55vh] object-cover opacity-80">
        @else
            <div class="w-full h-[40vh] md:h-[55vh] opacity-80"
                 style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);"></div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
        <div class="absolute bottom-0 inset-x-0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
                <a href="{{ route('properties.public.index') }}" class="text-sm text-white/80 hover:text-white inline-flex items-center mb-3">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('prop.back_to_list') }}
                </a>
                <h1 class="text-3xl md:text-5xl font-extrabold text-white">{{ $property->name }}</h1>
                @if ($property->city || $property->province)
                    <p class="text-white/90 mt-2 flex items-center">
                        <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $property->city }}{{ $property->province ? ', ' . $property->province : '' }}
                    </p>
                @endif
            </div>
        </div>
    </section>

    <section class="py-12 bg-gray-50 dark:bg-gray-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    @if ($property->description)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8 mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('prop.about') }}</h2>
                            <div class="prose prose-gray max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                                {!! $property->description !!}
                            </div>
                        </div>
                    @endif

                    @if ($property->amenities->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8 mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('prop.amenities') }}</h2>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($property->amenities as $amenity)
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium"
                                          style="background-color: {{ $primaryColor }}12; color: {{ $primaryColor }}">
                                        @if ($amenity->icon)
                                            <i class="fa-solid {{ $amenity->icon }} text-base mr-1.5"></i>
                                        @else
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @endif
                                        {{ $amenity->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Photo Gallery (Traveloka-style category tabs) -->
                    @if ($property->photos->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8 mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ __('prop.gallery') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Lihat suasana {{ $property->name }} per kategori</p>

                            <div class="flex flex-wrap gap-2 mb-6" id="gal-tabs">
                                @foreach ($property->photos->pluck('category')->unique()->values() as $cat)
                                    <button type="button" data-gal-cat="{{ $cat }}"
                                            class="gal-tab px-4 py-2 rounded-full text-sm font-medium border transition {{ $loop->first ? 'text-white border-transparent' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-700' }}"
                                            style="{{ $loop->first ? 'background-color: ' . $primaryColor : '' }}">
                                        {{ $cat }} <span class="opacity-75">({{ $property->photos->where('category', $cat)->count() }})</span>
                                    </button>
                                @endforeach
                            </div>

                            <div id="gal-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3"></div>

                            <!-- Lightbox -->
                            <div id="gal-lightbox" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
                                <div class="absolute inset-0 bg-black/85" data-gal-close></div>
                                <button type="button" data-gal-close class="absolute top-5 right-5 text-white/80 hover:text-white text-4xl leading-none z-10">&times;</button>
                                <button type="button" id="gal-prev" class="absolute left-4 md:left-8 text-white/80 hover:text-white text-4xl z-10">&lsaquo;</button>
                                <img id="gal-lightbox-img" src="" alt="" class="relative max-h-[85vh] max-w-[90vw] rounded-xl shadow-2xl object-contain">
                                <button type="button" id="gal-next" class="absolute right-4 md:right-8 text-white/80 hover:text-white text-4xl z-10">&rsaquo;</button>
                            </div>
                        </div>
                    @endif

                    <!-- Booking Widget -->
                    @if (!empty($property->unit_types))
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8 mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ __('prop.book_now') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Pilih tipe kamar & jenis sewa untuk melihat estimasi harga.</p>

                            <!-- Room Types -->
                            <div class="flex flex-wrap gap-2 mb-6">
                                @foreach ($property->unit_types as $type)
                                    <button type="button" data-type="{{ $type }}"
                                            class="type-pill inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium border transition"
                                            style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                                        {{ $property->typeLabel($type) }}
                                        @if ($property->priceFor($type, 'night_wd'))
                                            <span class="text-xs opacity-80">{{ __('home.from') }} Rp {{ number_format($property->priceFor($type, 'night_wd'), 0, ',', '.') }}/malam</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>

                            <!-- Sewa Type Tabs -->
                            <div class="flex flex-wrap gap-2 mb-5">
                                @foreach (['daily' => __('prop.daily'), 'transit' => __('prop.transit'), 'weekly' => __('prop.weekly'), 'monthly' => __('prop.monthly')] as $bkKey => $bkLabel)
                                    <button type="button" data-btype="{{ $bkKey }}"
                                            class="btype-tab px-4 py-2 rounded-full text-sm font-medium border transition {{ $bkKey === 'daily' ? 'text-white border-transparent' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-700' }}"
                                            style="{{ $bkKey === 'daily' ? 'background-color: ' . $primaryColor : '' }}">
                                        {{ $bkLabel }}
                                    </button>
                                @endforeach
                            </div>

                            <!-- Inputs -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="bk-checkin" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('prop.checkin') }}</label>
                                    <input type="date" id="bk-checkin"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2">
                                </div>
                                <div id="bk-checkout-wrap">
                                    <label for="bk-checkout" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('prop.checkout') }}</label>
                                    <input type="date" id="bk-checkout"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2">
                                </div>
                                <div id="bk-hours-wrap" style="display: none;">
                                    <label for="bk-hours" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('prop.duration') }}</label>
                                    <select id="bk-hours"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2">
                                        <option value="3">3 jam</option>
                                        <option value="6">6 jam</option>
                                        <option value="9">9 jam</option>
                                        <option value="12">12 jam</option>
                                        <option value="24">24 jam</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="bk-guests" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('prop.guests') }}</label>
                                    <input type="number" id="bk-guests" value="1" min="1" max="20"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2">
                                </div>
                            </div>

                            <!-- Live Total -->
                            <div class="mt-6 p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400" id="bk-detail">—</p>
                                </div>
                                <p class="text-2xl font-bold" style="color: {{ $primaryColor }}" id="bk-total">Rp 0</p>
                            </div>

                            <button type="button" id="bk-open"
                                    class="mt-5 w-full py-3.5 rounded-full text-white font-semibold hover:opacity-90 transition"
                                    style="background-color: {{ $primaryColor }}">
                                {{ __('prop.continue') }}
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ __('prop.contact_us') }}</h2>
                        <ul class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                            @if ($property->city)
                                <li class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 shrink-0 mt-0.5" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>{{ $property->address ? $property->address . ', ' : '' }}{{ $property->city }}{{ $property->province ? ', ' . $property->province : '' }}</span>
                                </li>
                            @endif
                            @if ($contactPhone)
                                <li class="flex items-center space-x-3">
                                    <svg class="w-5 h-5 shrink-0" style="color: {{ $primaryColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span>{{ $contactPhone }}</span>
                                </li>
                            @endif
                            @if ($whatsapp)
                                <li class="flex items-center space-x-3">
                                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener" class="text-green-600 font-medium hover:underline">
                                        {{ __('prop.chat_whatsapp') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ urlencode('Halo, saya ingin bertanya tentang ' . $property->name) }}"
                               target="_blank" rel="noopener"
                               class="mt-6 w-full inline-flex items-center justify-center px-5 py-3 rounded-full text-sm font-semibold text-white hover:opacity-90 transition"
                               style="background-color: #25d366">
                                {{ __('prop.ask_whatsapp') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Modal -->
    <div id="bk-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/60" data-close></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <!-- Form state -->
            <div id="bk-form-state">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $property->name }} — {{ __('prop.book_now') }}</h3>
                    <button type="button" data-close class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 text-sm space-y-1" id="bk-modal-summary"></div>

                    <div>
                        <label for="bk-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('prop.your_name') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="bk-name" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="bk-phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('prop.phone') }} <span class="text-red-500">*</span></label>
                            <input type="tel" id="bk-phone" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="bk-whatsapp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('prop.whatsapp') }}</label>
                            <input type="tel" id="bk-whatsapp" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label for="bk-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('prop.email') }}</label>
                        <input type="email" id="bk-email" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="bk-msg" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('prop.message') }}</label>
                        <textarea id="bk-msg" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Deposit 30% diperlukan untuk konfirmasi. Kami akan menghubungi Anda secepatnya.</p>

                    <button type="button" id="bk-submit" class="w-full py-3 rounded-full text-white font-semibold hover:opacity-90 transition" style="background-color: {{ $primaryColor }}">
                        {{ __('prop.submit_booking') }}
                    </button>
                    <p id="bk-error" class="hidden text-sm text-red-600 dark:text-red-400 text-center"></p>
                </div>
            </div>

            <!-- Success state -->
            <div id="bk-success-state" class="hidden text-center p-10">
                <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('prop.success_title') }}</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-2">{{ __('prop.success_desc') }}</p>
                <p class="text-2xl font-mono font-bold" style="color: {{ $primaryColor }}" id="bk-code">—</p>
                <button type="button" data-close class="mt-6 px-6 py-2.5 rounded-full text-white font-medium hover:opacity-90 transition" style="background-color: {{ $primaryColor }}">
                    {{ __('prop.close') }}
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var photos = @json($property->photos->groupBy('category')->map(fn ($g) => $g->map(fn ($p) => $p->media->url)->values())->toArray());
        var activeCat = null;
        var lightboxIndex = 0;

        function gridFor(cat) {
            return photos[cat] || [];
        }

        function renderGrid(cat) {
            activeCat = cat;
            var grid = document.getElementById('gal-grid');
            grid.innerHTML = '';
            gridFor(cat).forEach(function (url, i) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'aspect-[4/3] rounded-xl overflow-hidden group';
                btn.innerHTML = '<img src="' + url + '" alt="' + cat + '" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">';
                btn.addEventListener('click', function () { openLightbox(i); });
                grid.appendChild(btn);
            });
        }

        document.querySelectorAll('.gal-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.gal-tab').forEach(function (t) {
                    t.classList.remove('text-white', 'border-transparent');
                    t.style.backgroundColor = '';
                    t.classList.add('text-gray-600', 'dark:text-gray-300', 'border-gray-300', 'dark:border-gray-700');
                });
                tab.classList.remove('text-gray-600', 'dark:text-gray-300', 'border-gray-300', 'dark:border-gray-700');
                tab.classList.add('text-white', 'border-transparent');
                tab.style.backgroundColor = '{{ $primaryColor }}';
                renderGrid(tab.dataset.galCat);
            });
        });

        function openLightbox(i) {
            var list = gridFor(activeCat);
            lightboxIndex = i;
            document.getElementById('gal-lightbox-img').src = list[i];
            document.getElementById('gal-lightbox').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function move(delta) {
            var list = gridFor(activeCat);
            lightboxIndex = (lightboxIndex + delta + list.length) % list.length;
            document.getElementById('gal-lightbox-img').src = list[lightboxIndex];
        }

        document.querySelectorAll('[data-gal-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                document.getElementById('gal-lightbox').classList.add('hidden');
                document.body.style.overflow = '';
            });
        });
        document.getElementById('gal-prev').addEventListener('click', function () { move(-1); });
        document.getElementById('gal-next').addEventListener('click', function () { move(1); });
        document.addEventListener('keydown', function (e) {
            if (document.getElementById('gal-lightbox').classList.contains('hidden')) return;
            if (e.key === 'Escape') document.getElementById('gal-lightbox').classList.add('hidden');
            if (e.key === 'ArrowLeft') move(-1);
            if (e.key === 'ArrowRight') move(1);
        });

        // Init first tab
        var first = document.querySelector('.gal-tab');
        if (first) first.click();
    })();
</script>
<script>
    (function () {
        var prices = @json($property->prices ?? []);
        var weekendDays = @json($property->weekendDays());
        var propertyId = {{ $property->id }};

        var state = { type: '{{ $property->unit_types[0] ?? 'studio' }}', btype: 'daily', total: 0 };

        var $typePills = document.querySelectorAll('.type-pill');
        var $btypeTabs = document.querySelectorAll('.btype-tab');
        var $checkin = document.getElementById('bk-checkin');
        var $checkout = document.getElementById('bk-checkout');
        var $hours = document.getElementById('bk-hours');
        var $guests = document.getElementById('bk-guests');
        var $detail = document.getElementById('bk-detail');
        var $total = document.getElementById('bk-total');
        var $modal = document.getElementById('bk-modal');

        function fmt(n) {
            return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
        }

        function isWeekend(date) {
            return weekendDays.indexOf(date.getDay()) !== -1;
        }

        function calc() {
            var p = prices[state.type] || {};
            var total = 0, detail = '';

            if (state.btype === 'transit') {
                var h = parseInt($hours.value, 10) || 3;
                var d = new Date($checkin.value + 'T00:00:00');
                var key = (isWeekend(d) ? 't' + h + '_we' : 't' + h + '_wd');
                total = parseFloat(p[key] || 0);
                detail = (isWeekend(d) ? 'Weekend' : 'Weekday') + ' · ' + h + ' jam';
            } else if (state.btype === 'weekly') {
                total = parseFloat(p.weekly || 0);
                detail = '7 malam';
            } else if (state.btype === 'monthly') {
                total = parseFloat(p.monthly || 0);
                detail = '30 hari';
            } else {
                if ($checkin.value && $checkout.value) {
                    var start = new Date($checkin.value + 'T00:00:00');
                    var end = new Date($checkout.value + 'T00:00:00');
                    var nights = Math.max(1, Math.round((end - start) / 86400000));
                    for (var i = 0; i < nights; i++) {
                        var d = new Date(start.getTime() + i * 86400000);
                        total += parseFloat(p[isWeekend(d) ? 'night_we' : 'night_wd'] || 0);
                    }
                    detail = nights + ' malam';
                } else {
                    total = 0; detail = 'Pilih tanggal check-in & check-out';
                }
            }

            state.total = total;
            $total.textContent = fmt(total);
            $detail.textContent = detail;
        }

        function activeTab(el, activeCls) {
            el.classList.add('text-white', 'border-transparent');
            el.style.backgroundColor = '{{ $primaryColor }}';
        }
        function inactiveTab(el) {
            el.classList.remove('text-white', 'border-transparent');
            el.style.backgroundColor = '';
        }

        $typePills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                $typePills.forEach(function (p) {
                    p.style.backgroundColor = '';
                    p.classList.remove('text-white');
                });
                pill.style.backgroundColor = '{{ $primaryColor }}';
                pill.classList.add('text-white');
                state.type = pill.dataset.type;
                calc();
            });
        });
        $typePills[0].click();

        $btypeTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                $btypeTabs.forEach(function (t) { inactiveTab(t); });
                activeTab(tab);
                state.btype = tab.dataset.btype;
                document.getElementById('bk-hours-wrap').style.display = state.btype === 'transit' ? '' : 'none';
                document.getElementById('bk-checkout-wrap').style.display = state.btype === 'transit' ? 'none' : '';
                $checkout.required = state.btype !== 'transit';
                if (state.btype === 'transit' && !$checkin.value) {
                    $checkin.value = new Date().toISOString().slice(0, 10);
                }
                calc();
            });
        });

        [$checkin, $checkout, $hours, $guests].forEach(function (el) {
            el.addEventListener('change', calc);
        });

        // Modal
        document.querySelectorAll('[data-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                $modal.classList.add('hidden');
            });
        });

        document.getElementById('bk-open').addEventListener('click', function () {
            if (state.total <= 0) {
                alert('Silakan lengkapi pilihan tanggal / durasi terlebih dahulu.');
                return;
            }
            var p = prices[state.type] || {};
            document.getElementById('bk-modal-summary').innerHTML =
                '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Tipe kamar</span><span class="font-medium">' + state.type.toUpperCase().replace('BR', ' BR') + '</span></div>' +
                '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Jenis sewa</span><span class="font-medium">' + state.btype.charAt(0).toUpperCase() + state.btype.slice(1) + '</span></div>' +
                '<div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Detail</span><span class="font-medium">' + $detail.textContent + '</span></div>' +
                '<div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-2 mt-2"><span class="font-medium">Total</span><span class="font-bold" style="color: {{ $primaryColor }}">' + fmt(state.total) + '</span></div>';
            $modal.classList.remove('hidden');
        });

        document.getElementById('bk-submit').addEventListener('click', function () {
            var name = document.getElementById('bk-name').value.trim();
            var phone = document.getElementById('bk-phone').value.trim();
            var $error = document.getElementById('bk-error');

            if (!name || !phone) {
                $error.textContent = 'Nama dan No. HP wajib diisi.';
                $error.classList.remove('hidden');
                return;
            }
            $error.classList.add('hidden');

            var payload = {
                property_id: propertyId,
                booking_type: state.btype,
                unit_type: state.type,
                duration_hours: state.btype === 'transit' ? parseInt($hours.value, 10) : null,
                check_in: $checkin.value,
                check_out: state.btype === 'transit' ? null : $checkout.value,
                guests: parseInt($guests.value, 10) || 1,
                customer_name: name,
                customer_phone: phone,
                customer_whatsapp: document.getElementById('bk-whatsapp').value.trim(),
                customer_email: document.getElementById('bk-email').value.trim(),
                message: document.getElementById('bk-msg').value.trim(),
            };

            fetch('{{ route('bookings.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(payload),
            })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (result.ok && result.data.success) {
                    document.getElementById('bk-code').textContent = result.data.code;
                    document.getElementById('bk-form-state').classList.add('hidden');
                    document.getElementById('bk-success-state').classList.remove('hidden');
                } else {
                    $error.textContent = result.data.message || 'Terjadi kesalahan. Coba lagi.';
                    $error.classList.remove('hidden');
                }
            })
            .catch(function () {
                $error.textContent = 'Koneksi gagal. Coba lagi.';
                $error.classList.remove('hidden');
            });
        });
    })();
</script>
@endpush
