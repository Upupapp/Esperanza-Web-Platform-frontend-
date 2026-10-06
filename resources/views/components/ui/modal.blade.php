@props(['title' => null, 'titleEn' => null, 'maxWidth' => 'md', 'fullscreen' => false, 'bare' => false, 'startCompact' => false])

@php
    // Roughly 1.5x the standard Tailwind max-w scale for each named tier.
    $widths = ['sm' => 'max-w-xl', 'md' => 'max-w-2xl', 'lg' => 'max-w-3xl', 'xl' => 'max-w-4xl', '2xl' => 'max-w-5xl', '3xl' => 'max-w-6xl', '4xl' => 'max-w-7xl'];
    $w = $widths[$maxWidth] ?? $widths['md'];

    // Unique per modal instance so aria-labelledby resolves to THIS dialog's
    // heading. A bare modal has no heading, so it falls back to aria-label.
    //
    // NOT uniqid(). This is a STATIC EXPORT: uniqid() gave every modal a new id
    // on every build, so each deploy rewrote every page that contains one -- and
    // it made "does this refactor change the rendered output?" unanswerable.
    // A per-request counter is unique within a page and identical across builds,
    // because the render order is deterministic.
    static $modalSeq = 0;
    $titleId = 'modal-title-' . (++$modalSeq);
@endphp

<template x-teleport="body">
    <div
        data-modal-layer
        x-show="open"
        x-cloak
        x-effect="window.modalLayer($el, open)"
        @keydown.escape.window="if (window.closesOnEscape($el)) open = false"
        style="position: fixed; inset: 0; z-index: 90;"
    >
        <div x-show="open" x-transition.opacity @click="open = false" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="open = false"
        @keydown.space.prevent="open = false"></div>

        @if($fullscreen)
            {{-- Defaults to fullscreen; `compact` toggles it down to a centered popup the same size as maxWidth.
                 startCompact lets a caller open already-compact instead (e.g. the Dokyu document editor's own
                 "Full View" button expects the modal to start at its normal size and enlarge only on request) —
                 every existing caller leaves this false, so nothing about the default fullscreen-first behavior
                 changes for them. --}}
            <div
                x-data="{ compact: {{ $startCompact ? 'true' : 'false' }} }"
                :class="compact
                    ? 'fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[88vh] w-[calc(100vw-2rem)] {{ $w }}'
                    : 'fixed inset-3 sm:inset-4'"
            >
                <div
                    x-show="open"
                    x-transition:enter="transition duration-[200ms] ease-emphasized"
                    x-transition:enter-start="opacity-0 scale-[0.98]"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition duration-[150ms] ease-exit"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-[0.98]"
                    role="dialog"
                    aria-modal="true"
                    x-trap="open"
                    @if($bare) aria-label="Dialog" @else aria-labelledby="{{ $titleId }}" @endif
                    class="absolute inset-0 bg-white rounded-2xl shadow-float flex flex-col overflow-hidden"
                >
                    @if($bare)
                        {{ $slot }}
                    @else
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                            <h3 id="{{ $titleId }}" class="text-base font-semibold text-navy-900" x-text="$store.lang.current === 'en' ? @js($titleEn ?? $title) : @js($title)">{{ $title }}</h3>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="compact = !compact" :aria-label="compact ? 'Expand to full screen' : 'Shrink to popup'" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-600 hover:bg-slate-100 transition-colors" :title="compact ? 'Expand to full screen' : 'Shrink to popup'">
                                    <span x-show="compact"><i data-lucide="maximize-2" class="w-4 h-4"></i></span>
                                    <span x-show="!compact"><i data-lucide="minimize-2" class="w-4 h-4"></i></span>
                                </button>
                                <button type="button" @click="open = false" aria-label="Close dialog" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 sm:px-10 sm:py-8">
                            <div class="w-full">
                                {{ $slot }}
                            </div>
                        </div>
                        @isset($footer)
                            <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                                {{ $footer }}
                            </div>
                        @endisset
                    @endif
                </div>
            </div>
        @else
            <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <div
                    x-show="open"
                    x-transition:enter="transition duration-[200ms] ease-emphasized"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition duration-[150ms] ease-exit"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    {{-- A click in a window opened on top of this one (the Dokyu
                         "Prepared Document" over its request) is not a click
                         away from it: it closed the request behind, and the
                         officer came back to the list (Esperanza bugs QA). --}}
                    @click.outside="if (!$event.target.closest('[data-modal-layer], [data-modal-overlay]')) open = false"
                    role="dialog"
                    aria-modal="true"
                    x-trap="open"
                    @if($bare) aria-label="Dialog" @else aria-labelledby="{{ $titleId }}" @endif
                    style="max-height: 88vh; width: calc(100vw - 2rem);"
                    class="relative bg-white rounded-2xl shadow-float {{ $w }} flex flex-col overflow-hidden"
                >
                    @if($bare)
                        {{ $slot }}
                    @else
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
                            <h3 id="{{ $titleId }}" class="text-sm font-semibold text-navy-900" x-text="$store.lang.current === 'en' ? @js($titleEn ?? $title) : @js($title)">{{ $title }}</h3>
                            <button type="button" @click="open = false" aria-label="Close dialog" class="text-slate-500 hover:text-slate-600 transition-colors">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <div class="p-5 overflow-y-auto scrollbar-thin">
                            {{ $slot }}
                        </div>
                        @isset($footer)
                            <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                                {{ $footer }}
                            </div>
                        @endisset
                    @endif
                </div>
            </div>
        @endif
    </div>
</template>
