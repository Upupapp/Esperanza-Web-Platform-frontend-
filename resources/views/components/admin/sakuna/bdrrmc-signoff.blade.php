@props([
    'preparedLabel' => 'Assigned Barangay Official',
    'preparedRole' => 'Prepared by',
    'preparedName' => '',
    'consolidatedName' => '',
    'consolidatedLabel' => 'Barangay Secretary',
    'consolidatedRole' => 'Consolidated by',
    'notedName' => '',
    'notedLabel' => 'Punong Barangay',
    'notedRole' => 'Noted by',
    'twoColumn' => false,
])

<div class="grid {{ $twoColumn ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1 sm:grid-cols-3' }} gap-4 pt-2">
    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 text-center">
        <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-4">{{ $preparedRole }}</p>
        <p class="text-sm font-semibold text-slate-700 border-b border-slate-300 pb-1 mb-1">{{ $preparedName ?: '&nbsp;' }}</p>
        <p class="text-xs text-slate-500">{{ $preparedLabel }}</p>
        <p class="text-[10px] text-slate-500 mt-1">Signature Over Printed Name</p>
    </div>

    @unless($twoColumn)
    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 text-center">
        <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-4">{{ $consolidatedRole }}</p>
        <p class="text-sm font-semibold text-slate-700 border-b border-slate-300 pb-1 mb-1">{{ $consolidatedName ?: '&nbsp;' }}</p>
        <p class="text-xs text-slate-500">{{ $consolidatedLabel }}</p>
        <p class="text-[10px] text-slate-500 mt-1">Signature Over Printed Name</p>
    </div>
    @endunless

    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 text-center">
        <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-4">{{ $notedRole }}</p>
        <p class="text-sm font-semibold text-slate-700 border-b border-slate-300 pb-1 mb-1">{{ $notedName ?: '&nbsp;' }}</p>
        <p class="text-xs text-slate-500">{{ $notedLabel }}</p>
        <p class="text-[10px] text-slate-500 mt-1">Signature Over Printed Name</p>
    </div>
</div>
