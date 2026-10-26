{{-- The photo-and-video picker of a Balita being written (2026-10-18), for the
     resident's New Post and the staff's New/Edit Balita. Logic in
     resources/js/balita-media.js (balitaMediaField): photos are resized on the
     device, videos get a still, each file goes up in retried pieces. The form
     reads `value` ({ ids, busy, failed }) through x-model.
     look="composer": the staff's Create post (2026-10-26). No drop zone; a
     button outside opens the picker with a `balita-add-media` event, and the
     files show as large tiles with Add more and the count below. --}}
@props(['base', 'model', 'existing' => '[]', 'english' => false, 'look' => 'drop'])
@if($look === 'composer')
<div x-data="balitaMediaField('{{ $base }}', {{ $existing }}, {{ $english ? 'true' : 'false' }})" x-modelable="value" x-model="{{ $model }}" @balita-add-media.window="if (room > 0) $refs.picker.click()" {{ $attributes }}>
    <input x-ref="picker" type="file" multiple class="sr-only" tabindex="-1" aria-hidden="true" :accept="accept" @change="pick($event.target.files); $event.target.value = ''">
    <p x-show="notice" x-cloak role="alert" class="text-[11px] text-rose-700 mb-2" x-text="notice"></p>
    <div x-show="items.length" x-cloak
        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)"
        class="rounded-[10px] border p-2 space-y-2 transition-colors" :class="dragging ? 'border-brand-400 bg-brand-50' : 'border-slate-200'">
        <ul class="grid grid-cols-2 gap-2" :aria-label="t('Attached photos and videos', 'Mga nakalakip na larawan at video')">
            <template x-for="(item, index) in items" :key="item.key">
                <li class="relative rounded-lg overflow-hidden bg-slate-100 h-[180px]" :class="items.length === 1 && 'col-span-2 h-[240px]'" :aria-label="label(item, index)">
                    <img x-show="item.preview" :src="item.preview" alt="" class="w-full h-full object-cover">
                    <video x-show="!item.preview && item.videoSrc" :src="item.videoSrc" muted playsinline preload="metadata" class="w-full h-full object-cover" aria-hidden="true"></video>
                    <span x-show="!item.preview && !item.videoSrc" class="absolute inset-0 grid place-items-center text-ink-muted"><i :data-lucide="item.kind === 'video' ? 'film' : 'image'" class="w-6 h-6"></i></span>
                    <span x-show="item.kind === 'video' && item.durationMs" class="absolute left-2 bottom-2 inline-flex items-center gap-1 rounded-full bg-navy-950/75 text-white text-[10px] font-semibold px-1.5 py-0.5"><i data-lucide="play" class="w-3 h-3"></i><span x-text="fmtDuration(item.durationMs)"></span></span>

                    <div x-show="item.status !== 'done' || (item.media && item.media.ready === false)" class="absolute inset-0 bg-white/80 flex flex-col items-center justify-center gap-1.5 p-2 text-center" role="status">
                        <span x-show="item.status === 'preparing' || item.status === 'uploading'"><i data-lucide="loader-circle" class="w-4 h-4 animate-spin text-brand-600"></i></span>
                        <span x-show="item.status === 'error'" class="text-rose-600"><i data-lucide="circle-alert" class="w-4 h-4"></i></span>
                        <span class="text-[11px] leading-tight font-medium" :class="item.status === 'error' ? 'text-rose-700 line-clamp-4' : 'text-ink-body'" x-text="statusText(item)"></span>
                        <span x-show="item.status === 'uploading'" class="block w-4/5 h-1 rounded-full bg-slate-200 overflow-hidden" aria-hidden="true"><span class="block h-full bg-brand-500 transition-all" :style="'width:' + item.progress + '%'"></span></span>
                        <button x-show="item.status === 'error'" type="button" @click="retry(item)" class="text-[11px] font-semibold text-brand-700 hover:underline" x-text="t('Try again', 'Subukang muli')"></button>
                    </div>

                    <button type="button" @click="remove(item)" :aria-label="t('Remove ', 'Alisin ang ') + label(item, index)" class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white text-ink shadow grid place-items-center hover:bg-slate-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200"><i data-lucide="x" class="w-4 h-4"></i></button>
                    <div x-show="items.length > 1" class="absolute top-2 left-2 flex gap-1">
                        <button type="button" x-show="index > 0" @click="move(item, -1)" :aria-label="t('Move earlier: ', 'Ilipat pauna: ') + label(item, index)" class="w-8 h-8 rounded-full bg-white/90 text-ink grid place-items-center hover:bg-white focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200"><i data-lucide="arrow-left" class="w-4 h-4"></i></button>
                        <button type="button" x-show="index < items.length - 1" @click="move(item, 1)" :aria-label="t('Move later: ', 'Ilipat pahuli: ') + label(item, index)" class="w-8 h-8 rounded-full bg-white/90 text-ink grid place-items-center hover:bg-white focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200"><i data-lucide="arrow-right" class="w-4 h-4"></i></button>
                    </div>
                </li>
            </template>
        </ul>
        <div class="flex flex-wrap items-center justify-between gap-2 px-1">
            <button type="button" x-show="room > 0" @click="$refs.picker.click()" class="inline-flex items-center gap-1.5 h-9 px-3 rounded-md bg-slate-100 text-sm font-semibold text-ink hover:bg-slate-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"><i data-lucide="plus" class="w-4 h-4"></i><span x-text="t('Add more', 'Magdagdag pa')"></span></button>
            <span class="text-xs text-ink-muted tabular-nums" x-text="items.length + ' / ' + limits.maxItems + ' · ' + shortHint()"></span>
        </div>
    </div>
</div>
@else
<div x-data="balitaMediaField('{{ $base }}', {{ $existing }}, {{ $english ? 'true' : 'false' }})" x-modelable="value" x-model="{{ $model }}" {{ $attributes->merge(['class' => 'space-y-2']) }}>
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-semibold text-ink" x-text="t('Photos and videos (optional)', 'Mga larawan at video (opsyonal)')"></p>
        <span class="text-[11px] text-ink-muted tabular-nums" x-text="items.length + ' / ' + limits.maxItems"></span>
    </div>

    <label x-show="room > 0"
        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)"
        class="flex flex-col items-center justify-center gap-1 rounded-2xl border-2 border-dashed px-4 py-4 text-center cursor-pointer transition-colors focus-within:ring-4 focus-within:ring-brand-100"
        :class="dragging ? 'border-brand-400 bg-brand-50' : 'border-brand-600/20 hover:bg-white/70'">
        <span class="flex items-center gap-2 text-sm font-semibold text-brand-700"><i data-lucide="image-plus" class="w-4 h-4"></i><span x-text="t('Add photos or videos', 'Magdagdag ng larawan o video')"></span></span>
        <span class="text-[11px] text-ink-muted" x-text="hint()"></span>
        <input type="file" multiple class="sr-only" :accept="accept" :aria-label="t('Add photos or videos', 'Magdagdag ng larawan o video')" @change="pick($event.target.files); $event.target.value = ''">
    </label>
    <p x-show="notice" x-cloak role="alert" class="text-[11px] text-rose-700" x-text="notice"></p>

    <ul x-show="items.length" x-cloak class="grid grid-cols-3 sm:grid-cols-4 gap-2" :aria-label="t('Attached photos and videos', 'Mga nakalakip na larawan at video')">
        <template x-for="(item, index) in items" :key="item.key">
            <li class="relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 aspect-square" :aria-label="label(item, index)">
                <img x-show="item.preview" :src="item.preview" alt="" class="w-full h-full object-cover">
                <video x-show="!item.preview && item.videoSrc" :src="item.videoSrc" muted playsinline preload="metadata" class="w-full h-full object-cover" aria-hidden="true"></video>
                <span x-show="!item.preview && !item.videoSrc" class="absolute inset-0 grid place-items-center text-ink-muted"><i :data-lucide="item.kind === 'video' ? 'film' : 'image'" class="w-6 h-6"></i></span>
                <span x-show="item.kind === 'video' && item.durationMs" class="absolute left-1.5 bottom-1.5 inline-flex items-center gap-1 rounded-full bg-navy-950/75 text-white text-[10px] font-semibold px-1.5 py-0.5"><i data-lucide="play" class="w-3 h-3"></i><span x-text="fmtDuration(item.durationMs)"></span></span>

                <div x-show="item.status !== 'done' || (item.media && item.media.ready === false)" class="absolute inset-0 bg-white/80 flex flex-col items-center justify-center gap-1.5 p-2 text-center" role="status">
                    <span x-show="item.status === 'preparing' || item.status === 'uploading'"><i data-lucide="loader-circle" class="w-4 h-4 animate-spin text-brand-600"></i></span>
                    <span x-show="item.status === 'error'" class="text-rose-600"><i data-lucide="circle-alert" class="w-4 h-4"></i></span>
                    <span class="text-[10px] leading-tight font-medium" :class="item.status === 'error' ? 'text-rose-700 line-clamp-4' : 'text-ink-body'" x-text="statusText(item)"></span>
                    <span x-show="item.status === 'uploading'" class="block w-4/5 h-1 rounded-full bg-slate-200 overflow-hidden" aria-hidden="true"><span class="block h-full bg-brand-500 transition-all" :style="'width:' + item.progress + '%'"></span></span>
                    <button x-show="item.status === 'error'" type="button" @click="retry(item)" class="text-[10px] font-semibold text-brand-700 hover:underline" x-text="t('Try again', 'Subukang muli')"></button>
                </div>

                <button type="button" @click="remove(item)" :aria-label="t('Remove ', 'Alisin ang ') + label(item, index)" class="absolute top-1 right-1 w-7 h-7 rounded-full bg-navy-950/70 text-white grid place-items-center hover:bg-navy-950/90 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                <div x-show="items.length > 1" class="absolute top-1 left-1 flex gap-1">
                    <button type="button" x-show="index > 0" @click="move(item, -1)" :aria-label="t('Move earlier: ', 'Ilipat pauna: ') + label(item, index)" class="w-7 h-7 rounded-full bg-white/90 text-ink grid place-items-center hover:bg-white focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200"><i data-lucide="arrow-left" class="w-3.5 h-3.5"></i></button>
                    <button type="button" x-show="index < items.length - 1" @click="move(item, 1)" :aria-label="t('Move later: ', 'Ilipat pahuli: ') + label(item, index)" class="w-7 h-7 rounded-full bg-white/90 text-ink grid place-items-center hover:bg-white focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200"><i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></button>
                </div>
            </li>
        </template>
    </ul>
</div>
@endif
