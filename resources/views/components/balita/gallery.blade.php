{{-- A Balita post's photos and videos in its card (2026-10-18): one alone at
     its own shape, two side by side, three with the first across, four or more
     as a 2x2 grid whose last tile says how many more. A tile opens the
     full-screen viewer ($store.balitaViewer, x-balita.viewer).
     The viewer is handed only the items it can show (those with a url) and
     the tapped one's place among them (sweep 2026-11-18): it drops the ones
     still being checked, so the tile's place in the whole list opened the
     next photo, and the one after an unchecked video could never be opened. --}}
@props(['media', 'label' => "''", 'english' => false])
@php $en = $english ? 'true' : 'false'; @endphp
<div x-show="({{ $media }} || []).length" {{ $attributes->merge(['class' => 'grid gap-0.5 overflow-hidden']) }} :class="balitaGridClass(({{ $media }} || []).length)">
    <template x-for="tile in balitaTiles({{ $media }})" :key="tile.media.id">
        <button type="button"
            @click="(shown => $store.balitaViewer.show(shown, shown.findIndex((m) => m.id === tile.media.id), {{ $label }}, {{ $en }}))(({{ $media }} || []).filter((m) => m && m.url))"
            :disabled="!tile.media.url"
            class="relative block w-full overflow-hidden bg-slate-900/5 focus:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-brand-300"
            :class="(({{ $media }} || []).length === 1 && tile.media.kind === 'video') ? 'aspect-video bg-navy-950' : balitaTileClass(({{ $media }} || []).length, tile.index)"
            :aria-label="(tile.media.kind === 'video' ? (({{ $en }} || $store.lang.current === 'en') ? 'Play video ' : 'I-play ang video ') : (({{ $en }} || $store.lang.current === 'en') ? 'Open photo ' : 'Buksan ang larawan ')) + (tile.index + 1) + (({{ $en }} || $store.lang.current === 'en') ? ' of ' : ' sa ') + ({{ $media }} || []).length">
            <template x-if="tile.media.kind === 'image' && tile.media.url">
                <img :src="tile.media.url" alt="" loading="lazy" decoding="async"
                    class="w-full object-cover" :class="({{ $media }} || []).length === 1 ? 'h-auto max-h-[28rem]' : 'h-full'">
            </template>
            <template x-if="tile.media.kind === 'video' && tile.media.url">
                <span class="block w-full h-full">
                    <img x-show="tile.media.poster_url" :src="tile.media.poster_url" alt="" loading="lazy" decoding="async" class="w-full h-full" :class="({{ $media }} || []).length === 1 ? 'object-contain' : 'object-cover'">
                    <video x-show="!tile.media.poster_url" :src="tile.media.url + '#t=0.1'" muted playsinline preload="metadata" class="w-full h-full object-cover" aria-hidden="true" tabindex="-1"></video>
                </span>
            </template>
            <span x-show="!tile.media.url" class="absolute inset-0 grid place-items-center text-[11px] text-ink-muted px-2 text-center" x-text="({{ $en }} || $store.lang.current === 'en') ? 'Being checked' : 'Sinusuri pa'"></span>
            <span x-show="tile.media.kind === 'video' && tile.media.url" class="absolute inset-0 grid place-items-center pointer-events-none">
                <span class="w-12 h-12 rounded-full bg-navy-950/60 text-white grid place-items-center shadow-float"><i data-lucide="play" class="w-5 h-5 translate-x-px"></i></span>
            </span>
            <span x-show="tile.media.kind === 'video' && tile.media.duration_ms" class="absolute right-2 bottom-2 rounded-full bg-navy-950/75 text-white text-[10.5px] font-semibold px-2 py-0.5 tabular-nums pointer-events-none" x-text="fmtBalitaDuration(tile.media.duration_ms)"></span>
            <span x-show="tile.more" class="absolute inset-0 bg-navy-950/55 grid place-items-center text-white text-xl font-extrabold pointer-events-none" x-text="'+' + tile.more"></span>
        </button>
    </template>
</div>
