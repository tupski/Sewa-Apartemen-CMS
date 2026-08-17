

import * as Turbo from '@hotwired/turbo';
// ponytail: Turbo Drive aktif otomatis untuk link same-origin & submit form;
// tidak dipasang Stimulus karena belum dibutuhkan — tambah saat butuh interaktivitas reaktif.

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ponytail: Alpine.start() one-time app-level, sengaja dibiarkan di sini — TIDAK di `turbo:load`.
// Aman: Alpine memakai MutationObserver pada document (lifecycle.js, startObservingMutations),
// sehingga node baru dari Turbo body-swap ter-init otomatis via onElAdded -> initTree.
// Tidak ada DOMContentLoaded/jQuery/editor init di resources/js yang perlu di-rebind.
Alpine.start();
