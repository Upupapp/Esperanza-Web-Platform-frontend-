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
            class="flex items-start gap-3 rounded-xl shadow-float border bg-white px-4 py-3.5"
            :class="{
                'border-emerald-100': item.variant === 'success',
                'border-brand-100': item.variant === 'info',
                'border-rose-100': item.variant === 'error',
                'border-amber-100': item.variant === 'warning',
            }"
        >
            <span
                class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                :class="{
                    'bg-emerald-50 text-emerald-600': item.variant === 'success',
                    'bg-brand-50 text-brand-600': item.variant === 'info',
                    'bg-rose-50 text-rose-600': item.variant === 'error',
                    'bg-amber-50 text-amber-600': item.variant === 'warning',
                }"
            >
                <i :data-lucide="item.variant === 'success' ? 'circle-check' : item.variant === 'error' ? 'circle-alert' : item.variant === 'warning' ? 'triangle-alert' : 'info'" class="w-4 h-4"></i>
            </span>
            <p class="text-sm text-slate-600 flex-1 leading-snug pt-0.5" x-text="item.message"></p>
            <button aria-label="Close" @click="dismiss(item.id)" class="text-slate-300 hover:text-slate-500 transition-colors shrink-0">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </template>
</div>
