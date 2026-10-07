{{-- One field of a service form spec, rendered FROM the spec. TAB 07.

     config/esperanza_service_form_specs.php holds 25 services / 45 steps /
     230 fields. Hand-writing them is what makes 28 services unmaintainable, so
     they render from the definition instead.

     Every control gets a real <label for> and a generated id. That is not
     incidental: the platform has 324 <label> elements against 297 inputs and
     only 6 use for=, and the rendered-DOM audit measured 799 of 1729 controls
     with no accessible name at all. Anything generated from a spec should not
     add to that.

     Errors are bound to the SAME key the spec uses, which is the same key a
     422 response returns — so a server-side field error lands on its own box
     with no mapping table in between.

     Labels stay English, deliberately: these come from official LGU document
     forms whose wording is fixed by the issuing office (see CLAUDE.md
     "Language"). The chrome around them is bilingual. --}}

@props(['field', 'formVar' => 'values'])

@php
    $key = $field['key'];
    $type = $field['type'] ?? 'text';
    $label = $field['label'] ?? $key;
    $required = ($field['required'] ?? true) && $type !== 'derivedAge';
    $hint = $field['hint'] ?? null;
    $options = $field['options'] ?? [];
    $id = 'f-' . $key . '-' . substr(md5($key . $type . $label), 0, 6);

    $model = $formVar . "['" . $key . "']";
    // Clay design (STYLE_GUIDE §3.2): every control a pressed well that lifts on
    // focus; an error is a rose ring inside it.
    $control = 'w-full min-h-[44px] rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink placeholder:text-ink-faint '
        . 'focus:bg-white/90 focus:ring-4 outline-none transition-all duration-[160ms] ease-standard';
    $readonly = 'w-full min-h-[44px] rounded-2xl border-0 surface-pressed-sm py-2.5 px-4 text-sm text-ink-muted outline-none';
    $errorRing = "hasError('" . $key . "') ? 'ring-2 ring-inset ring-rose-300 focus:ring-rose-100' : 'focus:ring-brand-100'";
@endphp

<div class="space-y-1.5">
    @if($type !== 'checkbox')
        <label for="{{ $id }}" class="block text-sm font-semibold text-ink-body">
            {{ $label }}
            @if($required)
                <span class="text-rose-500" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    @if($type === 'textarea')
        <textarea
            id="{{ $id }}" name="{{ $key }}" rows="3"
            x-model="{{ $model }}"
            @if($required) aria-required="true" @endif
            :aria-invalid="hasError('{{ $key }}') ? 'true' : 'false'"
            :aria-describedby="hasError('{{ $key }}') ? '{{ $id }}-error' : @js($hint ? $id . '-hint' : null)"
            :class="{{ $errorRing }}"
            class="{{ $control }} resize-y"
        ></textarea>

    @elseif($type === 'select')
        <div class="relative">
            <select
                id="{{ $id }}" name="{{ $key }}"
                x-model="{{ $model }}"
                @if($required) aria-required="true" @endif
                :aria-invalid="hasError('{{ $key }}') ? 'true' : 'false'"
                :class="{{ $errorRing }}"
                class="{{ $control }} appearance-none pr-10"
            >
                <option value="" disabled>{{ __('Select') }} {{ $label }}</option>
                @foreach($options as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
            <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-muted pointer-events-none" aria-hidden="true"></i>
        </div>

    @elseif($type === 'multiselect')
        <div class="space-y-1.5" role="group" aria-labelledby="{{ $id }}-group">
            <span id="{{ $id }}-group" class="sr-only">{{ $label }}</span>
            @foreach($options as $i => $opt)
                <label for="{{ $id }}-{{ $i }}" class="flex items-center gap-2.5 text-sm text-ink-body">
                    <input
                        type="checkbox" id="{{ $id }}-{{ $i }}" name="{{ $key }}"
                        value="{{ $opt }}" x-model="{{ $model }}"
                        class="w-5 h-5 rounded-md accent-brand-600 focus:ring-4 focus:ring-brand-100"
                    >
                    {{ $opt }}
                </label>
            @endforeach
        </div>

    @elseif($type === 'checkbox')
        <label for="{{ $id }}" class="flex items-start gap-2.5 text-sm text-ink-body">
            <input
                type="checkbox" id="{{ $id }}" name="{{ $key }}"
                x-model="{{ $model }}"
                @if($required) aria-required="true" @endif
                :aria-invalid="hasError('{{ $key }}') ? 'true' : 'false'"
                class="mt-0.5 w-5 h-5 rounded-md accent-brand-600 focus:ring-4 focus:ring-brand-100"
            >
            <span>{{ $label }}@if($required)<span class="text-rose-500" aria-hidden="true">*</span>@endif</span>
        </label>

    @elseif($type === 'derivedAge')
        {{-- Computed from another field, never typed into: readonly rather than
             disabled, so it is still announced and still submitted. --}}
        <input
            type="text" id="{{ $id }}" name="{{ $key }}" readonly
            x-model="{{ $model }}"
            aria-readonly="true"
            class="{{ $readonly }}"
        >

    @else
        <input
            type="{{ $type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text') }}"
            id="{{ $id }}" name="{{ $key }}"
            x-model="{{ $model }}"
            @if($required) aria-required="true" @endif
            :aria-invalid="hasError('{{ $key }}') ? 'true' : 'false'"
            :aria-describedby="hasError('{{ $key }}') ? '{{ $id }}-error' : @js($hint ? $id . '-hint' : null)"
            :class="{{ $errorRing }}"
            class="{{ $control }}"
        >
    @endif

    @if($hint)
        <p id="{{ $id }}-hint" class="text-xs text-ink-muted">{{ $hint }}</p>
    @endif

    {{-- Server field errors land here, on the same key the spec uses. --}}
    <p
        id="{{ $id }}-error"
        x-show="hasError('{{ $key }}')"
        x-cloak
        role="alert"
        class="text-xs text-rose-600"
        x-text="errorFor('{{ $key }}')"
    ></p>
</div>
