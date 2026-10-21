{{-- One picture, for a banner or an occasion's pop-up (Banners & Occasions,
     2026-10-21). Logic in resources/js/balita-media.js (imageField): the
     file's real type is read from its first bytes, it is resized on this
     computer, and it goes up in retried pieces like a Balita photo; the
     server checks it all again. The form reads `value` ({ id, url, busy,
     failed, changed }) through x-model. --}}
{{-- fit: 'cover' shows the crop a placement makes (a banner); 'contain' the
     whole picture, as a pop-up shows it. --}}
@props(['base' => '/admin/balita', 'model', 'existing' => 'null', 'rules' => '{}', 'label' => 'Picture', 'aspect' => 'aspect-[3/1]', 'fit' => 'cover', 'fieldId' => 'image-field'])
<div x-data="imageField('{{ $base }}', {{ $existing }}, {{ $rules }})" x-modelable="value" x-model="{{ $model }}" data-image-field="{{ $fieldId }}" {{ $attributes->merge(['class' => 'space-y-2']) }}>
    <p class="text-sm font-medium text-slate-700" id="{{ $fieldId }}-label">{{ $label }}</p>

    <label x-show="!item"
        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)"
        class="flex flex-col items-center justify-center gap-1 rounded-2xl border-2 border-dashed px-4 py-6 text-center cursor-pointer transition-colors focus-within:ring-4 focus-within:ring-brand-100"
        :class="dragging ? 'border-brand-400 bg-brand-50' : 'border-brand-600/20 hover:bg-white/70'">
        <span class="flex items-center gap-2 text-sm font-semibold text-brand-700"><i data-lucide="image-plus" class="w-4 h-4"></i>Choose or drop a picture</span>
        <span class="text-[11px] text-ink-muted" x-text="hint()"></span>
        <input type="file" class="sr-only" :accept="accept" aria-labelledby="{{ $fieldId }}-label" @change="pick($event.target.files); $event.target.value = ''">
    </label>

    <div x-show="item" x-cloak class="space-y-2">
        <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 {{ $aspect }}">
            <img x-show="item && item.preview" :src="item ? item.preview : ''" alt="" class="w-full h-full {{ $fit === 'contain' ? 'object-contain' : 'object-cover' }}">
            <div x-show="item && (item.status !== 'done' || (item.media && item.media.ready === false))" class="absolute inset-0 bg-white/80 flex flex-col items-center justify-center gap-1.5 p-3 text-center" role="status">
                <span x-show="item && (item.status === 'preparing' || item.status === 'uploading')"><i data-lucide="loader-circle" class="w-5 h-5 animate-spin text-brand-600"></i></span>
                <span x-show="item && item.status === 'error'" class="text-rose-600"><i data-lucide="circle-alert" class="w-5 h-5"></i></span>
                <span class="text-xs font-medium" :class="item && item.status === 'error' ? 'text-rose-700' : 'text-ink-body'" x-text="statusText()"></span>
                <span x-show="item && item.status === 'uploading'" class="block w-3/5 h-1.5 rounded-full bg-slate-200 overflow-hidden" aria-hidden="true"><span class="block h-full bg-brand-500 transition-all" :style="'width:' + (item ? item.progress : 0) + '%'"></span></span>
                <button x-show="item && item.status === 'error'" type="button" @click="retry()" class="text-xs font-semibold text-brand-700 hover:underline">Try again</button>
                <button x-show="item && item.status === 'error' && kept" type="button" @click="keepCurrent()" class="text-xs font-semibold text-ink-body hover:underline">Keep the current picture</button>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <label class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-ink-body cursor-pointer hover:bg-brand-50 focus-within:ring-4 focus-within:ring-brand-100">
                <i data-lucide="replace" class="w-3.5 h-3.5"></i>Replace
                <input type="file" class="sr-only" :accept="accept" aria-label="Replace the picture" @change="pick($event.target.files); $event.target.value = ''">
            </label>
            <button type="button" @click="remove()" class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-rose-100"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Remove</button>
            <span x-show="item && item.width && item.height" class="text-[11px] text-ink-muted tabular-nums" x-text="item ? item.width + ' × ' + item.height + ' px' : ''"></span>
        </div>
    </div>
    <p x-show="item && item.status === 'error' && kept" x-cloak class="text-[11px] text-ink-muted">The picture saved before is kept until a new one is in.</p>
    <p x-show="notice" x-cloak role="alert" class="text-[11px] text-rose-700" x-text="notice"></p>
    <p x-show="warning" x-cloak class="text-[11px] text-amber-800" x-text="warning"></p>
</div>
