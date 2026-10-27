@props([
    // 'edit' while the form is filled in; 'view' for a saved form.
    'mode' => 'edit',
])

{{-- The signature lines at the foot of a BDRRMC paper form, as the barangay's
     files print them: "Prepared by:" (or "Distributed by:"), the line, the
     signer's title and "Signature Over Printed Name". Who signs and under what
     title comes from the form's description (resources/js/bdrrmc-paper.js).

     The names are the form's own and editable (signoff.prepared /
     .consolidated / .noted): Consolidated and Noted are filled from the
     barangay's officials; Prepared by is left blank to be written in, as on
     the paper (owner, 2026-10-27), never the signed-in officer. --}}
<div class="pp-sign" :class="paperSigners({{ $mode === 'view' ? "'view'" : "'edit'" }}).length === 2 && 'pp-sign-two'">
    <template x-for="s in paperSigners({{ $mode === 'view' ? "'view'" : "'edit'" }})" :key="s.model">
        <div class="pp-signer">
            <p x-text="s.role"></p>
            @if ($mode === 'view')
                <p class="pp-sign-name" x-text="s.name"></p>
            @else
                <input type="text" x-model="signoff[s.model]" :aria-label="s.role.replace(':', '') + ' name'" class="pp-sign-name pp-input">
            @endif
            <p x-text="s.title"></p>
            <p>Signature Over Printed Name</p>
        </div>
    </template>
</div>
