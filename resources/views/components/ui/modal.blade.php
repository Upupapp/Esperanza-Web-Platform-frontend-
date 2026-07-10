@props(['title' => null, 'maxWidth' => 'md'])

@php
    $widths = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl'];
    $w = $widths[$maxWidth] ?? $widths['md'];
@endphp

<div x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
    <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-navy-950/60 backdrop-blur-sm"></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        @click.outside="open = false"
        class="relative bg-white rounded-2xl shadow-float w-full {{ $w }} max-h-[85vh] flex flex-col overflow-hidden"
    >
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
            <h3 class="text-sm font-semibold text-navy-900">{{ $title }}</h3>
            <button @click="open = false" class="text-slate-400 hover:text-slate-600 transition-colors">
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
    </div>
</div>
