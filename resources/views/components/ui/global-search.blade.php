{{-- The top bar's search, both portals (window.globalSearch in resources/js/app.js).
     config: { portal, pages: [{ en, fil?, icon, href, module? }], routes, barangays? }.
     Type to find a page, a request, a service, a resident or a post; the arrows
     and Enter pick one; "/" anywhere jumps here.

     mobile: the box is hidden below md, so a search button stands in for it
     there and opens it full width at the top of the screen (scope round
     2026-10-15, the citizen portal). Without it nothing changes. --}}
@props(['config', 'placeholderEn', 'placeholderFil' => null, 'width' => 'w-56 lg:w-64 2xl:w-[360px]', 'mobile' => false])

@if($mobile)
<div class="contents" x-data="{ phoneSearch: false }" @keydown.escape.window="phoneSearch = false">
    <button
        type="button"
        class="md:hidden w-11 h-11 rounded-2xl surface-glass-sm grid place-items-center text-ink-body hover:text-brand-600 transition-colors"
        :aria-label="$store.lang?.current === 'en' ? 'Search' : 'Maghanap'"
        :aria-expanded="phoneSearch"
        @click="phoneSearch = true; $nextTick(() => document.getElementById('global-search-input')?.focus())"
    >
        <i data-lucide="search" class="w-5 h-5"></i>
    </button>
    <div x-show="phoneSearch" x-cloak x-transition.opacity class="md:hidden fixed inset-0 z-[80] bg-navy-950/40 backdrop-blur-sm" @click="phoneSearch = false" aria-hidden="true"></div>
@endif
<div
    @if($mobile)
        class="hidden md:block"
        :class="phoneSearch ? '!block fixed inset-x-0 top-0 z-[81] p-3 bg-white shadow-float md:!relative md:inset-auto md:z-auto md:p-0 md:bg-transparent md:shadow-none' : 'relative'"
    @else
        class="hidden md:block relative"
    @endif
    x-data="globalSearch(@js($config))"
    @click.outside="open = false"
    x-on:keydown.window="if ($event.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) && !document.activeElement?.isContentEditable) { $event.preventDefault(); $refs.input.focus(); }"
>
    @if($mobile)<div class="flex items-center gap-2">@endif
    <label
        @if($mobile)
            class="flex items-center gap-2.5 h-11 px-4 rounded-2xl surface-pressed transition-all duration-[160ms] ease-standard focus-within:bg-white/90 focus-within:shadow-[var(--shadow-pressed-sm),0_0_0_4px_var(--color-brand-100)]"
            :class="phoneSearch ? 'flex-1 min-w-0 md:flex-none {{ $width }}' : '{{ $width }}'"
        @else
            class="flex items-center gap-2.5 h-11 {{ $width }} px-4 rounded-2xl surface-pressed transition-all duration-[160ms] ease-standard focus-within:bg-white/90 focus-within:shadow-[var(--shadow-pressed-sm),0_0_0_4px_var(--color-brand-100)]"
        @endif
    >
        <i data-lucide="search" class="w-[18px] h-[18px] text-ink-muted shrink-0 pointer-events-none"></i>
        <input
            x-ref="input"
            @if($mobile) id="global-search-input" @endif
            type="search"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="open && q.trim() !== ''"
            aria-label="{{ $placeholderEn }}"
            x-model="q"
            @focus="focus()"
            @input="active = 0; open = true"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="go()"
            @keydown.escape="open = false; $el.blur()"
            :placeholder="$store.lang?.current === 'fil' && @js($placeholderFil) ? @js($placeholderFil) : @js($placeholderEn)"
            placeholder="{{ $placeholderFil ?? $placeholderEn }}"
            class="w-full min-w-0 bg-transparent border-0 p-0 text-sm text-ink placeholder:text-ink-faint focus:ring-0 focus:outline-none [&::-webkit-search-cancel-button]:hidden"
        >
        <kbd x-show="!q" class="hidden lg:grid place-items-center w-5 h-5 rounded-md bg-white/70 text-[11px] font-semibold text-ink-faint shadow-[0_1px_0_rgb(29_71_214/.12)] shrink-0" aria-hidden="true">/</kbd>
    </label>
    @if($mobile)
        <button type="button" x-show="phoneSearch" x-cloak @click="phoneSearch = false; open = false" class="md:hidden w-11 h-11 rounded-2xl grid place-items-center text-ink-muted hover:text-ink hover:bg-slate-100 shrink-0" :aria-label="$store.lang?.current === 'en' ? 'Close search' : 'Isara ang paghahanap'">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    @endif

    <div
        x-show="open && q.trim() !== ''"
        x-transition:enter="transition duration-[200ms] ease-emphasized"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-cloak
        role="listbox"
        @if($mobile)
            class="absolute mt-2 max-h-[70vh] overflow-y-auto scrollbar-thin bg-white rounded-[22px] shadow-float border border-brand-600/10 z-50 origin-top-right py-1.5"
            :class="phoneSearch ? 'left-3 right-3' : 'right-0 w-[min(460px,calc(100vw-2rem))]'"
        @else
            class="absolute right-0 mt-2 w-[min(460px,calc(100vw-2rem))] max-h-[70vh] overflow-y-auto scrollbar-thin bg-white rounded-[22px] shadow-float border border-brand-600/10 z-50 origin-top-right py-1.5"
        @endif
    >
        <template x-for="(it, i) in results" :key="it.group + '|' + it.href + '|' + i">
            <div>
                <p x-show="i === 0 || results[i - 1].group !== it.group" class="px-4 pt-2.5 pb-1 text-[11px] font-bold uppercase tracking-[.07em] text-ink-faint" x-text="groupLabel(it.group)"></p>
                <a
                    :href="it.href"
                    role="option"
                    :aria-selected="i === active"
                    @mouseenter="active = i"
                    class="flex items-center gap-3 mx-1.5 px-2.5 py-2 rounded-xl transition-colors"
                    :class="i === active ? 'bg-brand-50' : ''"
                >
                    <span class="relative w-9 h-9 rounded-xl bg-brand-50 text-brand-600 grid place-items-center shrink-0 overflow-hidden" :class="it.photo && 'rounded-full'">
                        <i :data-lucide="it.icon" class="w-4 h-4"></i>
                        <img x-show="it.photo" :src="it.photo" alt="" class="absolute inset-0 w-full h-full object-cover" x-on:error="$el.style.display='none'">
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-ink truncate" x-text="it.title"></span>
                        <span x-show="it.sub" class="block text-xs text-ink-muted truncate" x-text="it.sub"></span>
                    </span>
                    <i data-lucide="corner-down-left" class="w-3.5 h-3.5 text-brand-400 shrink-0" x-show="i === active"></i>
                </a>
            </div>
        </template>
        <div x-show="loading" class="flex items-center gap-2 px-4 py-2.5 text-xs text-ink-muted">
            <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin"></i>
            <span x-text="t('Searching…', 'Naghahanap…')"></span>
        </div>
        <div x-show="!loading && !results.length" class="px-4 py-4 text-center">
            <p class="text-sm font-semibold text-ink" x-text="t('No matches found.', 'Walang nahanap.')"></p>
            <p class="text-xs text-ink-muted mt-0.5" x-text="t('Try a name, a reference number or a barangay.', 'Subukan ang pangalan, reference number o barangay.')"></p>
        </div>
    </div>
</div>
@if($mobile)
</div>
@endif
