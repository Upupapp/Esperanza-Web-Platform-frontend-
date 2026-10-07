@props([
    'preparedLabel' => 'Assigned Barangay Official',
    'preparedRole' => 'Prepared by',
    'consolidatedLabel' => 'Barangay Secretary',
    'consolidatedRole' => 'Consolidated by',
    'notedLabel' => 'Punong Barangay',
    'notedRole' => 'Noted by',
    'twoColumn' => false,
])

{{-- The names are the form's own (signoff.prepared / .consolidated / .noted in
     the capture forms' component), filled from the officer and the
     barangay's officials and editable; they were hard-coded to one
     barangay's officials. --}}

<div class="grid {{ $twoColumn ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1 sm:grid-cols-3' }} gap-4 pt-2">
    <div class="rounded-xl border-0 surface-pressed p-3.5 text-center">
        <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide mb-4">{{ $preparedRole }}</p>
        <input type="text" aria-label="{{ $preparedRole }}: name" x-model="signoff.prepared" placeholder="Name" class="w-full text-center text-sm font-semibold text-slate-700 bg-transparent border-0 border-b border-slate-300 pb-1 mb-1 px-1 focus:ring-0 focus:border-brand-400 outline-none">
        <p class="text-xs text-ink-muted">{{ $preparedLabel }}</p>
        <p class="text-[10px] text-ink-muted mt-1">Signature Over Printed Name</p>
    </div>

    @unless($twoColumn)
    <div class="rounded-xl border-0 surface-pressed p-3.5 text-center">
        <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide mb-4">{{ $consolidatedRole }}</p>
        <input type="text" aria-label="{{ $consolidatedRole }}: name" x-model="signoff.consolidated" placeholder="Name" class="w-full text-center text-sm font-semibold text-slate-700 bg-transparent border-0 border-b border-slate-300 pb-1 mb-1 px-1 focus:ring-0 focus:border-brand-400 outline-none">
        <p class="text-xs text-ink-muted">{{ $consolidatedLabel }}</p>
        <p class="text-[10px] text-ink-muted mt-1">Signature Over Printed Name</p>
    </div>
    @endunless

    <div class="rounded-xl border-0 surface-pressed p-3.5 text-center">
        <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide mb-4">{{ $notedRole }}</p>
        <input type="text" aria-label="{{ $notedRole }}: name" x-model="signoff.noted" placeholder="Name" class="w-full text-center text-sm font-semibold text-slate-700 bg-transparent border-0 border-b border-slate-300 pb-1 mb-1 px-1 focus:ring-0 focus:border-brand-400 outline-none">
        <p class="text-xs text-ink-muted">{{ $notedLabel }}</p>
        <p class="text-[10px] text-ink-muted mt-1">Signature Over Printed Name</p>
    </div>
</div>
