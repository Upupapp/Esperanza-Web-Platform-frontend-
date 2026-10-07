@props([
    'label' => null,
    'labelFil' => null,
    'name',
    'type' => 'text',
    'icon' => null,
    'error' => null,
    'hint' => null,
])

<div class="w-full">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-semibold text-ink-body mb-1.5">@if($labelFil)<x-ui.t :fil="$labelFil" :en="$label" />@else{{ $label }}@endif</label>
    @endif

    <div class="relative">
        @if($icon)
            <i data-lucide="{{ $icon }}" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-ink-muted pointer-events-none"></i>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            @if($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @elseif($hint) aria-describedby="{{ $name }}-hint" @endif
            {{ $attributes->merge([
                // Clay design (STYLE_GUIDE §3.2): a pressed well that lifts on focus.
                'class' => 'w-full min-h-[44px] rounded-2xl border-0 surface-pressed py-2.5 text-sm text-ink placeholder:text-ink-faint transition-all duration-[160ms] ease-standard focus:bg-white/90 focus:outline-none focus:ring-4 '
                    . ($error
                        ? 'ring-2 ring-inset ring-rose-300 focus:ring-rose-100'
                        : 'focus:ring-brand-100')
                    . ' ' . ($icon ? 'pl-10 pr-4' : 'px-4'),
            ]) }}
        >
    </div>

    @if($hint && ! $error)
        <p id="{{ $name }}-hint" class="mt-1.5 text-xs text-ink-muted">{{ $hint }}</p>
    @endif

    @if($error)
        <p id="{{ $name }}-error" class="mt-1.5 text-xs text-rose-500 flex items-center gap-1" role="alert">
            <i data-lucide="circle-alert" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $error }}
        </p>
    @endif
</div>
