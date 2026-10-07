@props(['files', 'onSelect', 'uploadAction' => null, 'search' => true, 'columns' => 3, 'empty' => 'No files found.', 'bilingual' => true])

@php
    $gridCols = ['2' => 'grid-cols-2', '3' => 'grid-cols-3', '4' => 'grid-cols-4'][(string) $columns] ?? 'grid-cols-3';
@endphp

<div x-data="{ pickerQuery: '' }" class="surface-glass rounded-[26px] overflow-hidden">
    @if($search)
        <div class="relative border-b border-brand-600/10 p-2.5">
            <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            <input aria-label="Search files"
                type="text"
                x-model="pickerQuery"
                @if($bilingual)
                    x-bind:placeholder="$store.lang.current === 'en' ? 'Search files...' : 'Maghanap ng file...'"
                @else
                    placeholder="Search files..."
                @endif
                class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border-0 surface-pressed-sm text-ink placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200"
            >
        </div>
    @endif

    <div class="p-2.5 max-h-64 overflow-y-auto scrollbar-thin">
        <div
            class="grid {{ $gridCols }} gap-2"
            x-show="{{ $files }}.filter(f => !pickerQuery || f.name.toLowerCase().includes(pickerQuery.toLowerCase())).length > 0"
        >
            <template x-for="file in {{ $files }}.filter(f => !pickerQuery || f.name.toLowerCase().includes(pickerQuery.toLowerCase()))" :key="file.name">
                <button
                    type="button"
                    @click="{{ $onSelect }}"
                    class="group relative flex flex-col items-center gap-1.5 rounded-2xl bg-white/50 p-2.5 hover:bg-white/80 hover:shadow-glass-sm hover:-translate-y-0.5 transition-all duration-200 text-center"
                >
                    <span
                        class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                        :class="{
                            'bg-gradient-to-br from-brand-100 to-brand-50 text-brand-500': file.category === 'IMG',
                            'bg-gradient-to-br from-rose-100 to-rose-50 text-rose-500': file.category === 'VID' || file.category === 'PDF',
                            'bg-gradient-to-br from-purple-100 to-purple-50 text-purple-500': file.category === 'GIF',
                            'bg-gradient-to-br from-blue-100 to-blue-50 text-blue-500': file.category === 'DOCX',
                        }"
                    >
                        <i data-lucide="image" class="w-5 h-5" x-show="file.category === 'IMG'"></i>
                        <i data-lucide="video" class="w-5 h-5" x-show="file.category === 'VID'"></i>
                        <i data-lucide="sticker" class="w-5 h-5" x-show="file.category === 'GIF'"></i>
                        <i data-lucide="file-text" class="w-5 h-5" x-show="file.category === 'PDF'"></i>
                        <i data-lucide="file-type" class="w-5 h-5" x-show="file.category === 'DOCX'"></i>
                    </span>
                    <span class="text-[10.5px] font-medium text-ink-body leading-tight truncate max-w-full" x-text="file.name"></span>
                    <span class="absolute inset-0 rounded-2xl ring-2 ring-brand-400 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></span>
                </button>
            </template>
        </div>

        <div
            class="flex flex-col items-center text-center py-6 px-2"
            x-show="{{ $files }}.filter(f => !pickerQuery || f.name.toLowerCase().includes(pickerQuery.toLowerCase())).length === 0"
        >
            <span class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center mb-2" aria-hidden="true"><i data-lucide="folder-search" class="w-4 h-4"></i></span>
            <p class="text-[11px] text-slate-500" x-text="pickerQuery ? 'No matches for &quot;' + pickerQuery + '&quot;.' : @js($empty)"></p>
            <p class="text-[10px] text-slate-300 mt-0.5" x-show="pickerQuery">Try a different search term.</p>
        </div>
    </div>

    @if($uploadAction)
        <button
            type="button" @click="{{ $uploadAction }}"
            class="w-full flex items-center gap-2 justify-center text-xs font-semibold text-brand-600 hover:bg-white/60 py-2.5 border-t border-brand-600/10 transition-colors"
        ><i data-lucide="upload" class="w-3.5 h-3.5"></i>@if($bilingual)<x-ui.t fil="Mag-upload ng bago" en="Upload new" />@else Upload new @endif</button>
    @endif
</div>
