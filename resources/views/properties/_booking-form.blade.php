@php
    $maxDays = $property->maxBookingDays();

    // Build active types: only those with at least one price > 0
    $activeTypes = collect($property->unit_types ?? [])
        ->filter(function ($type) use ($property) {
            $prices = $property->prices[$type] ?? [];
            return collect($prices)->contains(fn($v) => $v !== null && $v !== '' && (float)$v > 0);
        })
        ->values();

    $usePills = $activeTypes->count() <= 4;
@endphp

<div data-bkf class="space-y-4">

    {{-- ====== ROOM TYPE SELECTOR ====== --}}
    @if ($activeTypes->count() > 1)
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Tipe Kamar</label>

            @if ($usePills)
                {{-- Pills for ≤4 types --}}
                <div class="flex flex-wrap gap-2" role="group" aria-label="Pilih tipe kamar">
                    @foreach ($activeTypes as $i => $type)
                        <button type="button"
                                class="bkf-room-pill px-3 py-1.5 rounded-full text-sm font-medium border transition
                                       {{ $i === 0 ? 'bkf-pill-active' : 'bkf-pill-inactive' }}"
                                data-type="{{ $type }}"
                                aria-pressed="{{ $i === 0 ? 'true' : 'false' }}">
                            {{ $property->typeLabel($type) }}
                        </button>
                    @endforeach
                </div>
            @else
                {{-- Dropdown for >4 types --}}
                <select id="{{ $prefix }}-room-type"
                        class="bkf-room-type w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2"
                        aria-label="Tipe kamar">
                    @foreach ($activeTypes as $type)
                        <option value="{{ $type }}">{{ $property->typeLabel($type) }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    @else
        {{-- Single type: hidden input, no selector shown --}}
        <input type="hidden" class="bkf-room-type-hidden" value="{{ $activeTypes->first() ?? '' }}">
    @endif

    {{-- ====== CHECK-IN DATE + TIME ====== --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="{{ $prefix }}-checkin" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                Tanggal Check-in
            </label>
            <div class="relative">
                <input type="text"
                       id="{{ $prefix }}-checkin"
                       class="bkf-checkin w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2"
                       placeholder="Pilih tanggal"
                       readonly
                       aria-required="true">
                {{-- Overlay native date input: tap lands on real picker (works iOS/Android/desktop) --}}
                <input type="date"
                       class="bkf-checkin-native absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                       tabindex="-1"
                       aria-label="Tanggal Check-in">
                <input type="hidden" id="{{ $prefix }}-checkin-raw" class="bkf-checkin-raw">
            </div>
        </div>
        <div>
            <label for="{{ $prefix }}-checkin-time" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                Waktu Check-in
            </label>
            <input type="time"
                   id="{{ $prefix }}-checkin-time"
                   class="bkf-checkin-time w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2"
                   value="{{ \App\Services\SettingsService::get('booking_checkin_default_time', '14:00') }}">
        </div>
    </div>

    {{-- ====== UNIT (Tipe Sewa) + DURATION — Tipe Sewa tampil dulu ====== --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="{{ $prefix }}-unit" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                Tipe Sewa
            </label>
            <select id="{{ $prefix }}-unit"
                    class="bkf-unit w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2">
                {{-- Options populated by JS on load & room-type change --}}
            </select>
        </div>
        <div>
            <label for="{{ $prefix }}-duration" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 bkf-duration-label">
                Durasi
            </label>
            {{-- JS will replace this with the correct input/select based on satuan --}}
            <div id="{{ $prefix }}-duration-wrap" class="relative">
                <input type="number"
                       id="{{ $prefix }}-duration"
                       class="bkf-duration w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2"
                       value="1" min="1" max="{{ $maxDays ?: 365 }}">
                <span class="bkf-duration-suffix absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400 pointer-events-none hidden"></span>
            </div>
        </div>
    </div>

    {{-- ====== GUESTS ====== --}}
    <div>
        <label for="{{ $prefix }}-guests" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
            Jumlah Tamu
        </label>
        <div class="relative">
            <input type="number"
                   id="{{ $prefix }}-guests"
                   class="bkf-guests w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2"
                   value="2" min="1" max="20"
                   aria-label="Jumlah tamu">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400 pointer-events-none">Dewasa</span>
        </div>
    </div>

    {{-- ====== PRICE SUMMARY ====== --}}
    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400 bkf-detail">—</p>
        <p class="text-xl font-bold bkf-total" style="color: {{ $primaryColor }}">Rp 0</p>
    </div>

    {{-- ====== PROMO BANNER ====== --}}
    <div class="bkf-promo-banner hidden px-3 py-2 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-sm text-green-800 dark:text-green-300">
    </div>

    {{-- ====== VOUCHER FIELD ====== --}}
    <div class="space-y-2">
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Kode Voucher (opsional)</label>
        <div class="flex gap-2">
            <input type="text"
                   class="bkf-voucher-input flex-1 px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2 uppercase"
                   placeholder="Masukkan kode voucher"
                   style="text-transform: uppercase"
                   aria-label="Kode voucher">
            <button type="button"
                    class="bkf-voucher-apply px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                Terapkan
            </button>
        </div>
        <p class="bkf-voucher-msg hidden text-xs mt-1"></p>
        <input type="hidden" class="bkf-voucher-id">
        <input type="hidden" class="bkf-voucher-code">
    </div>

    <button type="button"
            class="bkf-open w-full py-3.5 rounded-full text-white font-semibold hover:opacity-90 transition"
            style="background-color: {{ $primaryColor }}">
        Lanjut Pemesanan
    </button>
    <p class="bkf-error hidden text-sm text-red-600 dark:text-red-400 text-center" role="alert"></p>

    {{-- Pass promos data for JS --}}
    <script>
    (function () {
        var bkf = document.currentScript.closest('[data-bkf]');
        if (!bkf) return;

        var promos = @json($property->promoRates->where('is_active', true)->values());
        var propertyId = {{ $property->id }};
        var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

        var voucherInput  = bkf.querySelector('.bkf-voucher-input');
        var voucherApply  = bkf.querySelector('.bkf-voucher-apply');
        var voucherMsg    = bkf.querySelector('.bkf-voucher-msg');
        var voucherIdIn   = bkf.querySelector('.bkf-voucher-id');
        var voucherCodeIn = bkf.querySelector('.bkf-voucher-code');
        var promoBanner   = bkf.querySelector('.bkf-promo-banner');

        // Auto-uppercase voucher input
        voucherInput.addEventListener('input', function () {
            var pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);

            // If user changes voucher code, clear the applied state
            voucherIdIn.value   = '';
            voucherCodeIn.value = '';
            voucherMsg.classList.add('hidden');
        });

        voucherApply.addEventListener('click', function () {
            applyVoucher();
        });

        voucherInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); applyVoucher(); }
        });

        function applyVoucher() {
            var code = voucherInput.value.trim();
            if (!code) return;

            // Get current total from bkf-total text
            var totalEl = bkf.querySelector('.bkf-total');
            var raw = totalEl ? totalEl.textContent.replace(/[^\d]/g, '') : '0';
            var amount = parseInt(raw) || 0;

            voucherApply.disabled = true;
            voucherApply.textContent = '...';

            fetch('/booking/validate-voucher', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ code: code, property_id: propertyId, amount: amount }),
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                voucherApply.disabled = false;
                voucherApply.textContent = 'Terapkan';

                if (data.valid) {
                    voucherIdIn.value   = data.voucher_id;
                    voucherCodeIn.value = data.code;

                    showVoucherMsg(
                        '✓ Voucher berhasil diterapkan! Diskon: Rp ' + formatRupiah(data.discount_amount),
                        'green'
                    );

                    // Update displayed total
                    if (totalEl) {
                        totalEl.textContent = 'Rp ' + formatRupiah(data.final_amount);
                    }
                } else {
                    voucherIdIn.value   = '';
                    voucherCodeIn.value = '';
                    showVoucherMsg(data.message || 'Kode voucher tidak valid atau sudah kadaluarsa.', 'red');
                }
            })
            .catch(function () {
                voucherApply.disabled = false;
                voucherApply.textContent = 'Terapkan';
                showVoucherMsg('Gagal memverifikasi voucher. Coba lagi.', 'red');
            });
        }

        function showVoucherMsg(msg, color) {
            voucherMsg.textContent = msg;
            voucherMsg.className = 'bkf-voucher-msg text-xs mt-1 ' + (color === 'green' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400');
            voucherMsg.classList.remove('hidden');
        }

        function formatRupiah(n) {
            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // ---- Promo detection ----
        function checkPromos() {
            if (!promos || promos.length === 0) return;

            var dateInput = bkf.querySelector('[class*="bkf-checkin"]') || bkf.querySelector('input[type="date"]');
            var timeSelect = bkf.querySelector('select[aria-label*="jam"]') || bkf.querySelector('.bkf-checkin-time');
            var bookingTypeEl = bkf.querySelector('.bkf-booking-type, [name="booking_type"]');

            if (!dateInput || !dateInput.value) {
                promoBanner.classList.add('hidden');
                return;
            }

            var dateVal = dateInput.value;
            var timeVal = timeSelect ? (timeSelect.value || '00:00') : '00:00';
            var bookingType = bookingTypeEl ? (bookingTypeEl.value || 'daily') : 'daily';

            // day of week (0=Sun..6=Sat)
            var parts = dateVal.split('-');
            var d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
            var dow = d.getDay();

            var matched = null;
            for (var i = 0; i < promos.length; i++) {
                var p = promos[i];
                if (!p.is_active) continue;

                // booking type check
                if (p.booking_type !== 'all' && p.booking_type !== bookingType) continue;

                // day check
                var weekendDays = [0, 6]; // simplified; property-level handled server-side
                var dayOk = false;
                if (p.applies_to === 'all') {
                    dayOk = true;
                } else if (p.applies_to === 'weekday') {
                    dayOk = !weekendDays.includes(dow);
                } else if (p.applies_to === 'weekend') {
                    dayOk = weekendDays.includes(dow);
                } else if (p.applies_to === 'custom') {
                    dayOk = (p.active_days || []).includes(dow);
                }
                if (!dayOk) continue;

                // time window check
                var start = p.start_time ? p.start_time.substring(0, 5) : '00:00';
                var end   = p.end_time   ? p.end_time.substring(0, 5)   : '23:59';
                var inWindow = (start <= end) ? (timeVal >= start && timeVal < end) : (timeVal >= start || timeVal < end);
                if (!inWindow) continue;

                matched = p;
                break;
            }

            if (matched) {
                promoBanner.innerHTML = '🎉 <strong>Promo tersedia: ' + matched.name + '</strong> — Rp ' + formatRupiah(matched.price);
                promoBanner.classList.remove('hidden');
            } else {
                promoBanner.classList.add('hidden');
            }
        }

        // Re-check promos when date/time changes
        document.addEventListener('change', function (e) {
            if (e.target.closest('[data-bkf]') === bkf) {
                checkPromos();
                // Re-validate voucher if one is applied
                if (voucherIdIn.value) {
                    applyVoucher();
                }
            }
        });
        // Initial check
        checkPromos();
    })();
    </script>

    {{-- Enhanced form behavior --}}
    <script>
    (function () {
        var bkf = document.currentScript.closest('[data-bkf]');
        if (!bkf) return;

        var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        var checkinDisplay = bkf.querySelector('.bkf-checkin');
        var checkinRaw = bkf.querySelector('.bkf-checkin-raw');
        var checkinTime = bkf.querySelector('.bkf-checkin-time');
        var durationWrap = bkf.querySelector('[id$="-duration-wrap"]');
        var durationSuffix = bkf.querySelector('.bkf-duration-suffix');

        // 1. Date picker with Indonesian format.
        // Overlay native <input type="date"> (bkf-checkin-native) covers the readonly display field,
        // so taps open the real native picker on iOS/Android/desktop. showPicker() is unsupported on iOS Safari.
        if (checkinDisplay) {
            var nativeInput = bkf.querySelector('.bkf-checkin-native');
            if (nativeInput) {
                nativeInput.min = new Date().toISOString().split('T')[0];

                nativeInput.addEventListener('change', function() {
                    if (nativeInput.value) {
                        var parts = nativeInput.value.split('-');
                        var d = new Date(parts[0], parts[1] - 1, parts[2]);
                        var formatted = d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
                        checkinDisplay.value = formatted;
                        checkinRaw.value = nativeInput.value;

                        // Trigger change for promo detection
                        var evt = new Event('change', { bubbles: true });
                        checkinDisplay.dispatchEvent(evt);
                    }
                });
            }
        }

        // 2. Disable past hours for today
        if (checkinTime && checkinRaw) {
            function updateTimeOptions() {
                var selectedDate = checkinRaw.value;
                if (!selectedDate) return;

                var parts = selectedDate.split('-');
                var selected = new Date(parts[0], parts[1] - 1, parts[2]);
                selected.setHours(0, 0, 0, 0);
                var now = new Date();
                now.setHours(0, 0, 0, 0);

                if (selected.getTime() === now.getTime()) {
                    // Today: disable past hours
                    var currentHour = new Date().getHours();
                    var currentMinute = new Date().getMinutes();
                    var currentTime = (currentHour < 10 ? '0' : '') + currentHour + ':' + (currentMinute < 10 ? '0' : '') + currentMinute;

                    if (checkinTime.value < currentTime) {
                        checkinTime.value = currentTime;
                    }
                    checkinTime.min = currentTime;
                } else {
                    // Future date: all hours available
                    checkinTime.removeAttribute('min');
                }
            }

            checkinRaw.addEventListener('change', updateTimeOptions);
            checkinDisplay.addEventListener('change', updateTimeOptions);
        }

        // 3 & 4. Duration suffix handling for "Harian" with max 7
        // This runs after show.blade.php's rebuildDurasi, watching for changes
        var observer = new MutationObserver(function() {
            var durationInput = durationWrap ? durationWrap.querySelector('.bkf-duration') : null;
            var unitSelect = bkf.querySelector('.bkf-unit');

            if (durationInput && unitSelect && durationSuffix) {
                var unitValue = unitSelect.value || '';

                if (unitValue.toLowerCase() === 'harian') {
                    // Show "Malam" suffix
                    durationSuffix.textContent = 'Malam';
                    durationSuffix.classList.remove('hidden');

                    // Enforce max 7 for harian
                    if (durationInput.type === 'number') {
                        durationInput.max = '7';
                        if (parseInt(durationInput.value) > 7) {
                            durationInput.value = '7';
                        }
                    }
                } else {
                    // Hide suffix for other types
                    durationSuffix.classList.add('hidden');
                }
            }
        });

        if (durationWrap) {
            observer.observe(durationWrap, { childList: true, subtree: true });
        }

        // Initial trigger
        setTimeout(function() {
            var unitSelect = bkf.querySelector('.bkf-unit');
            if (unitSelect) {
                var evt = new Event('change', { bubbles: true });
                unitSelect.dispatchEvent(evt);
            }
        }, 100);
    })();
    </script>
</div>

@if ($maxDays)
    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
        Maksimal durasi sewa: {{ $maxDays }} malam.
    </p>
@endif
