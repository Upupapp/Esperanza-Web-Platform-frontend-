@props([])

<div
    x-data="toast"
    x-on:toast.window="push($event.detail.message, $event.detail.variant)"
    class="fixed top-5 right-5 z-[100] flex flex-col gap-2 w-[22rem] max-w-[calc(100vw-2.5rem)]"
    role="status"
    aria-live="polite"
>
    <template x-for="item in items" :key="item.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-[260ms]"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-[160ms]"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 translate-y-1"
            class="flex items-start gap-3 surface-glass-sm rounded-2xl shadow-float border-white/90 px-4 py-3.5"
        >
            <span
                class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                :class="{
                    'bg-emerald-100 text-emerald-700 shadow-clay-light': item.variant === 'success',
                    'clay-light': item.variant === 'info',
                    'bg-rose-100 text-rose-700 shadow-clay-light': item.variant === 'error',
                    'clay-gold-light': item.variant === 'warning',
                }"
            >
                <i :data-lucide="item.variant === 'success' ? 'circle-check' : item.variant === 'error' ? 'circle-alert' : item.variant === 'warning' ? 'triangle-alert' : 'info'" class="w-4 h-4"></i>
            </span>
            <p class="text-sm text-ink-body flex-1 leading-snug pt-1" x-text="item.message"></p>
            <button aria-label="Close" @click="dismiss(item.id)" class="text-ink-faint hover:text-ink-muted transition-colors shrink-0 pt-1">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </template>
</div>
