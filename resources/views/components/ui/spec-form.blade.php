{{-- A whole service form, rendered from its spec. TAB 07.

     Wraps x-ui.spec-field with the submission machinery from
     resources/js/api/form.js, so a service gets in-flight state, a
     double-submit guard, server field errors on the right boxes, preserved
     input on failure, and an unsaved-changes warning — without any of it being
     re-implemented per service. That is what makes 28 services maintainable.

         <x-ui.spec-form
             :spec="config('esperanza_service_form_specs')['Barangay Clearance']"
             submit-expr="$api.post('/dokyu/requests', values)" />

     `submitExpr` is an Alpine expression evaluated with `values` in scope. Leave
     it off and the form validates and reports honestly that submitting is not
     wired yet, rather than pretending to succeed — there is no endpoint yet. --}}

@props([
    'spec',
    'submitExpr' => null,
    'submitLabel' => 'Isumite',
    'submitLabelEn' => 'Submit',
])

@php
    $steps = $spec['steps'] ?? [];
@endphp

<form
    x-data="specForm({
        spec: @js($spec),
        @if($submitExpr) submit: (values) => {{ $submitExpr }}, @endif
    })"
    @submit.prevent="submit()"
    @beforeunload.window="guardUnload($event)"
    novalidate
>
    @foreach($steps as $i => $step)
        <fieldset class="mb-6 last:mb-0">
            @if(!empty($step['label']))
                <legend class="text-sm font-semibold text-navy-900 mb-3">
                    {{ $step['label'] }}
                    @if(count($steps) > 1)
                        <span class="text-xs font-normal text-slate-400">({{ $i + 1 }}/{{ count($steps) }})</span>
                    @endif
                </legend>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach($step['fields'] ?? [] as $field)
                    <x-ui.spec-field :field="$field" />
                @endforeach
            </div>
        </fieldset>
    @endforeach

    {{-- Whole-form failure: a 500, a network drop, or no endpoint wired.
         Never silent, and never mistaken for a field problem. --}}
    <div x-show="formError" x-cloak role="alert" class="mt-4 rounded-xl bg-rose-50 border border-rose-100 px-4 py-3">
        <p class="text-sm text-rose-700" x-text="formError"></p>
    </div>

    <div x-show="submitted" x-cloak role="status" class="mt-4 rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3">
        <p class="text-sm text-emerald-700">
            <x-ui.t fil="Naisumite ang iyong kahilingan." en="Your request was submitted." />
        </p>
    </div>

    <div class="mt-5 flex items-center justify-end gap-2.5">
        {{ $actions ?? '' }}

        {{-- loading="submitting" disables the button, sets aria-busy and swaps
             the icon for a spinner — the double-submit guard is also enforced
             in submit() itself, because a disabled button is a UI courtesy and
             not a guarantee. --}}
        <x-ui.button type="submit" loading="submitting" icon="send">
            <x-ui.t :fil="$submitLabel" :en="$submitLabelEn" />
        </x-ui.button>
    </div>
</form>
