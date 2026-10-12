@props([
    'label' => null,
    'labelFil' => null,
    'name',
    'type' => 'text',
    'icon' => null,
    'error' => null,
    'hint' => null,
])

@php
    // Every password field can show what was typed (owner, 2026-10-12: on the
    // portal and the app alike). The eye toggles the input's type in place.
    $password = $type === 'password';
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-semibold text-ink-body mb-1.5">@if($labelFil)<x-ui.t :fil="$labelFil" :en="$label" />@else{{ $label }}@endif</label>
    @endif

    <div class="relative" @if($password) x-data="{ shown: false }" @endif>
        @if($icon)
            <i data-lucide="{{ $icon }}" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-ink-muted pointer-events-none"></i>
        @endif

        <input
            type="{{ $type }}"
            @if($password) :type="shown ? 'text' : 'password'" autocapitalize="off" spellcheck="false" @endif
            name="{{ $name }}"
            id="{{ $name }}"
            @if($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @elseif($hint) aria-describedby="{{ $name }}-hint" @endif
            {{ $attributes->merge([
                // Clay design (STYLE_GUIDE §3.2): a pressed well that lifts on focus.
                'class' => 'w-full min-h-[44px] rounded-2xl border-0 surface-pressed py-2.5 text-sm text-ink placeholder:text-ink-faint transition-all duration-[160ms] ease-standard focus:bg-white/90 focus:outline-none focus:ring-4 '
                    . ($error
                        ? 'ring-2 ring-inset ring-rose-300 focus:ring-rose-100'
                        : 'focus:ring-brand-100')
                    . ' ' . ($icon ? 'pl-10' : 'pl-4') . ' ' . ($password ? 'pr-12' : 'pr-4'),
            ]) }}
        >
        @if($password)
            <button type="button" @click="shown = !shown"
                    class="absolute right-1 top-1/2 -translate-y-1/2 w-11 h-11 flex items-center justify-center rounded-xl text-ink-muted hover:text-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                    :aria-label="shown ? ($store.lang.current === 'en' ? 'Hide password' : 'Itago ang password') : ($store.lang.current === 'en' ? 'Show password' : 'Ipakita ang password')"
                    :aria-pressed="shown ? 'true' : 'false'">
                <svg x-show="!shown" aria-hidden="true" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"></path><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"></path><path d="m2 2 20 20"></path></svg>
                <svg x-show="shown" x-cloak aria-hidden="true" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg>
            </button>
        @endif
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
