{{--
    Sakuna location picker (2026-10-26): where an incident is, or where a
    centre stands. Tap the map, use the device's location, or (staff) search
    an address; the pin can be dragged. Writes `lat` and `lng` into the form
    named by `target` (a JS expression), the pair the API keeps, and clears
    both together. resources/js/sakuna-map.js has the logic.

    Optional unless `required` (a JS expression) is true, as for a centre
    being registered: an emergency is still reported when the phone cannot
    find itself or the map does not load. `subject`: incident, centre or place.
--}}
@props([
    'target',
    'search' => false,
    'bilingual' => false,
    'required' => 'false',
    'subject' => 'place',
])
<div
    x-data="sakunaLocationPicker({ target: () => {{ $target }}, search: @js((bool) $search), bilingual: @js((bool) $bilingual), required: () => ({{ $required }}), subject: @js($subject) })"
    x-effect="follow()"
    {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white/70 p-3.5']) }}
>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-700">
                <span x-text="t('Map location', 'Lokasyon sa mapa')"></span>
                <span x-show="isRequired" class="text-rose-500">*</span>
                <span x-show="!isRequired" class="font-normal text-ink-muted" x-text="t('(optional)', '(opsyonal)')"></span>
            </p>
            <p x-show="hasPin" class="text-[11px] font-semibold text-emerald-700 mt-0.5 flex items-center gap-1">
                <i data-lucide="map-pin-check" class="w-3.5 h-3.5"></i>
                <span x-text="t('Pinned at', 'Naka-pin sa')"></span>
                <span class="font-mono" x-text="coords"></span>
            </p>
            <p x-show="!hasPin" class="text-[11px] mt-0.5" :class="isRequired ? 'text-rose-700 font-semibold' : 'text-ink-muted'" x-text="emptyText"></p>
        </div>
        <div class="flex items-center gap-1.5" x-show="hasPin">
            <a :href="osm" target="_blank" rel="noopener" class="text-[11px] font-semibold text-brand-600 hover:underline" x-text="t('View larger', 'Tingnan nang mas malaki')"></a>
            <button type="button" @click="clearPin()" class="text-[11px] font-semibold text-slate-600 hover:text-rose-700 px-2 py-1 rounded-lg hover:bg-rose-50" x-text="t('Remove pin', 'Alisin ang pin')"></button>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mt-2.5">
        <button type="button" x-show="!mapOpen" @click="openMap()"
            class="inline-flex items-center gap-1.5 min-h-[40px] text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 px-3 py-2 rounded-xl transition-colors">
            <i data-lucide="map-pinned" class="w-4 h-4"></i><span x-text="hasPin ? t('Show on map', 'Ipakita sa mapa') : t('Pin on map', 'I-pin sa mapa')"></span>
        </button>
        <button type="button" @click="useMyLocation()" :disabled="gps === 'locating'" :aria-busy="gps === 'locating'"
            class="inline-flex items-center gap-1.5 min-h-[40px] text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:border-slate-300 px-3 py-2 rounded-xl transition-colors disabled:opacity-60">
            <i data-lucide="locate-fixed" class="w-4 h-4"></i><span x-text="gps === 'locating' ? t('Finding you…', 'Hinahanap ka…') : t('Use my location', 'Gamitin ang aking lokasyon')"></span>
        </button>
    </div>
    <p x-show="gps === 'idle' && !hasPin" class="text-[11px] text-ink-muted mt-1.5"
       x-text="t('Your device asks before sharing your location; it is used only to place the pin.', 'Magtatanong muna ang iyong device bago ibahagi ang lokasyon mo; gagamitin lang ito para ilagay ang pin.')"></p>
    <p x-show="gpsMessage" x-cloak role="status" class="text-[11px] mt-1.5" :class="gpsTrouble ? 'text-amber-800 font-semibold' : 'text-ink-muted'" x-text="gpsMessage"></p>

    @if ($search)
        <div x-show="canSearch" class="mt-3">
            <label class="sr-only" :for="$id('place-search')" x-text="t('Search for a place or address', 'Maghanap ng lugar o address')"></label>
            <div class="flex gap-2">
                <input :id="$id('place-search')" type="search" x-model="query" maxlength="120" @keydown.enter.prevent="runSearch()"
                    :placeholder="t('Search a place or address, e.g. Esperanza Central School', 'Maghanap ng lugar o address')"
                    class="flex-1 min-w-0 text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
                <button type="button" @click="runSearch()" :disabled="query.trim().length < 3 || searchState === 'searching'"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 px-3 py-2 rounded-2xl transition-colors disabled:opacity-50">
                    <i data-lucide="search" class="w-4 h-4"></i><span x-text="searchState === 'searching' ? t('Searching…', 'Hinahanap…') : t('Find', 'Hanapin')"></span>
                </button>
            </div>
            <ul x-show="results.length" class="mt-2 space-y-1">
                <template x-for="(r, i) in results" :key="i">
                    <li>
                        <button type="button" @click="choose(r)" class="w-full text-left text-xs text-slate-700 rounded-xl border border-slate-200 bg-white hover:bg-brand-50 px-3 py-2" x-text="r.name"></button>
                    </li>
                </template>
            </ul>
            <p x-show="searchState === 'empty'" class="text-[11px] text-ink-muted mt-1.5" x-text="t('Nothing found for that. Try other words, or pin it on the map.', 'Walang nahanap. Subukan ang ibang salita, o i-pin sa mapa.')"></p>
            <p x-show="searchState === 'failed'" class="text-[11px] text-amber-800 font-semibold mt-1.5" x-text="t('The address search is not available right now. Pin the place on the map instead.', 'Hindi magamit ang paghahanap ngayon. I-pin na lang sa mapa.')"></p>
            <p x-show="searchState === 'chosen'" class="text-[11px] text-ink-muted mt-1.5" x-text="t('A search result is approximate. Check the pin and drag it to the exact spot.', 'Tantiya lang ang resulta ng paghahanap. Tingnan ang pin at i-drag sa eksaktong lugar.')"></p>
        </div>
    @endif

    <div x-show="mapOpen" x-cloak class="mt-3">
        <div class="relative">
            <div x-ref="map" class="sakuna-map h-64 sm:h-72 w-full rounded-xl overflow-hidden border border-slate-200"
                 :aria-label="t('Map. Tap or click to place the pin.', 'Mapa. I-tap o i-click para ilagay ang pin.')" role="application"></div>
            <div x-show="mapState === 'loading'" class="pointer-events-none absolute inset-x-0 top-2 flex justify-center">
                <span class="text-[11px] font-semibold text-slate-600 bg-white/90 rounded-full px-2.5 py-1 shadow" x-text="t('Loading the map…', 'Nilo-load ang mapa…')"></span>
            </div>
        </div>
        <p x-show="mapState !== 'failed'" class="text-[11px] text-ink-muted mt-1.5"
           x-text="t('Tap the map to place the pin. Tap another spot to move it.', 'I-tap ang mapa para ilagay ang pin. I-tap ang ibang lugar para ilipat ito.') + ' ' + t('You can also drag the pin.', 'Puwede mo ring i-drag ang pin.')"></p>
        <div x-show="mapState === 'failed'" x-cloak role="alert" class="mt-2 rounded-xl bg-amber-50 border border-amber-200 px-3 py-2 text-[11px] text-amber-900">
            <p class="font-semibold" x-text="t('The map could not load (no connection, or the map service is busy).', 'Hindi ma-load ang mapa (walang koneksyon, o abala ang serbisyo ng mapa).')"></p>
            <p x-text="failedText"></p>
            <button type="button" @click="retryMap()" class="mt-1 font-semibold underline underline-offset-2" x-text="t('Try again', 'Subukang muli')"></button>
        </div>
    </div>
</div>
