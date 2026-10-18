{{-- The full-screen viewer for Balita photos and videos (2026-10-18), once per
     page. Opened by x-balita.gallery through $store.balitaViewer: arrows or a
     swipe move between them, Esc closes, a video plays with its controls. --}}
<div x-data x-show="$store.balitaViewer.open" x-cloak data-balita-viewer
    class="fixed inset-0 z-[90] flex flex-col bg-navy-950/95 text-white"
    role="dialog" aria-modal="true"
    :aria-label="$store.balitaViewer.t('Photos and videos', 'Mga larawan at video')"
    x-trap.noscroll="$store.balitaViewer.open"
    x-effect="window.modalLayer($el, $store.balitaViewer.open)"
    @keydown.escape.window="$store.balitaViewer.open && window.closesOnEscape($el) && $store.balitaViewer.close()"
    @keydown.arrow-right.window="$store.balitaViewer.open && $store.balitaViewer.go(1)"
    @keydown.arrow-left.window="$store.balitaViewer.open && $store.balitaViewer.go(-1)"
    @touchstart.passive="$store.balitaViewer.touchStart($event)"
    @touchend="$store.balitaViewer.touchEnd($event)">
    <div class="flex items-center justify-between gap-3 px-4 py-3" style="padding-top: max(0.75rem, env(safe-area-inset-top))">
        <p class="min-w-0 text-sm font-semibold truncate" x-text="$store.balitaViewer.label"></p>
        <div class="flex items-center gap-3 shrink-0">
            <span x-show="$store.balitaViewer.items.length > 1" class="text-xs tabular-nums text-white/80" x-text="($store.balitaViewer.index + 1) + ' / ' + $store.balitaViewer.items.length"></span>
            <button type="button" @click="$store.balitaViewer.close()" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 grid place-items-center focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40" :aria-label="$store.balitaViewer.t('Close', 'Isara')"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
    </div>
    <div class="relative flex-1 min-h-0 flex items-center justify-center px-2 pb-4">
        <template x-if="$store.balitaViewer.current && $store.balitaViewer.current.kind === 'image'">
            <img :src="$store.balitaViewer.current.url" alt="" class="max-w-full max-h-full object-contain select-none">
        </template>
        <template x-if="$store.balitaViewer.current && $store.balitaViewer.current.kind === 'video'">
            <video :src="$store.balitaViewer.current.url" :poster="$store.balitaViewer.current.poster_url || null" controls playsinline autoplay preload="metadata" class="max-w-full max-h-full bg-black"></video>
        </template>
        <button type="button" x-show="$store.balitaViewer.items.length > 1" @click="$store.balitaViewer.go(-1)" class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 grid place-items-center focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40" :aria-label="$store.balitaViewer.t('Previous', 'Nakaraan')"><i data-lucide="chevron-left" class="w-6 h-6"></i></button>
        <button type="button" x-show="$store.balitaViewer.items.length > 1" @click="$store.balitaViewer.go(1)" class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 grid place-items-center focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40" :aria-label="$store.balitaViewer.t('Next', 'Susunod')"><i data-lucide="chevron-right" class="w-6 h-6"></i></button>
    </div>
</div>
