{{--
    The DRRM employment and livelihood classification as two dropdowns
    (owner, 2026-10-28/29): Employment Group, then the specific occupation in
    that group, with "Others, specify" where the form has it. Every occupation
    field on the site uses this, so there is one list.

    `model` is the Alpine expression of the object being edited; the component
    reads and writes its livelihood_code, livelihood_other and occupation.
    `id` names the fields; inside an x-for, `dynamic-id` is a JS expression
    for it instead (e.g. "'rp-m-' + i"). A
    record whose occupation was typed before the list existed keeps its words
    until someone chooses; they are shown as a reminder.
--}}
@props([
    'model',
    'id' => '',
    'dynamicId' => null,
    'inputClass' => 'w-full rounded-2xl py-2.5 px-3 text-sm bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-400',
    'labelClass' => 'block text-xs font-semibold text-ink mb-1.5',
])
@php
    // One field's id and its label's for, fixed or computed in a loop.
    $idFor = fn (string $part) => $dynamicId
        ? ':id="'.$dynamicId." + '-{$part}'\""
        : 'id="'.$id.'-'.$part.'"';
    $forFor = fn (string $part) => $dynamicId
        ? ':for="'.$dynamicId." + '-{$part}'\""
        : 'for="'.$id.'-'.$part.'"';
@endphp

<div x-data="{ lvGroup: '' }"
     x-init="$store.livelihood.load(); lvGroup = (({{ $model }}).livelihood_code || '').charAt(0); $watch(() => ({{ $model }}).livelihood_code, (c) => { if (c && c.charAt(0) !== lvGroup) lvGroup = c.charAt(0); })"
     {{ $attributes->merge(['class' => 'grid gap-3 sm:grid-cols-2']) }}>
    <div>
        <label class="{{ $labelClass }}" {!! $forFor('group') !!}><x-ui.t fil="Uri ng hanapbuhay" en="Employment group" /></label>
        <select {!! $idFor('group') !!} x-model="lvGroup" class="{{ $inputClass }}"
                @change="if ((({{ $model }}).livelihood_code || '').charAt(0) !== lvGroup) { ({{ $model }}).livelihood_code = ''; ({{ $model }}).livelihood_other = ''; ({{ $model }}).occupation = ''; }">
            <option value="">—</option>
            <template x-for="g in $store.livelihood.groups" :key="g.code">
                <option :value="g.code" :selected="g.code === lvGroup" x-text="g.code + ' · ' + g.label"></option>
            </template>
        </select>
    </div>
    <div>
        <label class="{{ $labelClass }}" {!! $forFor('item') !!}><x-ui.t fil="Hanapbuhay" en="Occupation or livelihood" /></label>
        <select {!! $idFor('item') !!} x-model="({{ $model }}).livelihood_code" :disabled="!lvGroup" class="{{ $inputClass }} disabled:opacity-60"
                @change="if (!$event.target.value) ({{ $model }}).occupation = ''; if (!$store.livelihood.isOther($event.target.value)) ({{ $model }}).livelihood_other = '';">
            <option value="">—</option>
            <template x-for="i in $store.livelihood.items(lvGroup)" :key="i.code">
                <option :value="i.code" :selected="i.code === ({{ $model }}).livelihood_code" x-text="(i.heading ? i.heading + ' › ' : '') + i.label"></option>
            </template>
        </select>
    </div>
    <div class="sm:col-span-2" x-show="$store.livelihood.isOther(({{ $model }}).livelihood_code)" x-cloak>
        <label class="{{ $labelClass }}" {!! $forFor('other') !!}><x-ui.t fil="Tukuyin ang hanapbuhay" en="Specify the occupation" /></label>
        <input {!! $idFor('other') !!} type="text" maxlength="100" x-model="({{ $model }}).livelihood_other" class="{{ $inputClass }}">
    </div>
    <p class="sm:col-span-2 text-xs text-ink-muted" x-show="({{ $model }}).occupation && !({{ $model }}).livelihood_code" x-cloak>
        <x-ui.t fil="Nakatala dati:" en="Recorded before as:" />
        <span class="font-semibold text-ink" x-text="({{ $model }}).occupation"></span>.
        <x-ui.t fil="Piliin ang katugma sa listahan." en="Choose the matching item from the list." />
    </p>
    <p class="sm:col-span-2 text-xs text-rose-700" x-show="$store.livelihood.failed" x-cloak>
        <x-ui.t fil="Hindi ma-load ang listahan ng hanapbuhay. Subukan muli mamaya." en="The occupation list could not load. Try again later." />
    </p>
</div>
