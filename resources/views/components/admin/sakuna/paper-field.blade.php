@props([
    // What the paper prints before the line, e.g. "Type of Disaster:".
    'label',
    // 'edit' while the form is filled in; 'view' for a saved form.
    'mode' => 'edit',
    // text | date | time | barangay | static | computed
    'type' => 'text',
    // The value an edit binds (x-model), or a computed edit value (x-text).
    'model' => null,
    // The saved form's value (x-text).
    'view' => null,
    // A fixed value, printed as on the paper (Region V, MASBATE ...).
    'value' => null,
    'aria' => null,
    'readonly' => null,
    'upper' => false,
    'bold' => false,
])

{{-- One "Label: ______" line of a BDRRMC paper form (bdrrmc-paper.blade.php):
     in edit, an underlined box that prints as the text typed in it; in view,
     the saved value on the line. --}}
<span {{ $attributes->merge(['class' => 'pp-field']) }}>
    <span class="pp-label">{{ $label }}</span>
    @if ($type === 'static')
        <span class="pp-value pp-filled {{ $bold ? 'pp-b' : '' }}">{{ $value }}</span>
    @elseif ($mode === 'view')
        <span class="pp-value pp-filled {{ $upper ? 'pp-upper' : '' }}" x-text="{{ $view }}"></span>
    @elseif ($type === 'computed')
        <span class="pp-value pp-filled" x-text="{{ $model }}"></span>
    @elseif ($type === 'barangay')
        <select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked || !!(drafted && drafted[openForm])" @change="fillOfficials()" class="pp-value pp-input pp-upper">
            <option value="">—</option>
            {{-- :selected, because the sheet is redrawn after Fill from records sets the
                 barangay, and x-model alone leaves the redrawn select on "—". --}}
            <template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name" :selected="b.name === barangay"></option></template>
        </select>
    @else
        <input aria-label="{{ $aria ?? trim($label, ': ') }}" type="{{ $type }}" x-model="{{ $model }}" @if ($readonly) :readonly="{{ $readonly }}" @endif class="pp-value pp-input {{ $upper ? 'pp-upper' : '' }}">
    @endif
</span>
