{{-- Read-only "submitted application" replica — the citizen's original form,
     locked. Shared by Dokyu, Tulong, and Payments detail modals so every
     record renders the exact same format, not a bespoke one per module.
     Expects the parent x-data scope to expose `selected` with citizen,
     barangay, ref, submitted, purpose. --}}
<div class="mt-4 rounded-2xl border border-slate-200 overflow-hidden">
    <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 border-b border-slate-200">
        <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400"></i>
        <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide">Submitted Application</p>
        <span class="text-[10px] text-slate-400 ml-auto">As filed by the citizen — read only</span>
    </div>
    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3.5">
        <div>
            <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Applicant Name</label>
            <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700 truncate" x-text="selected.citizen"></p>
        </div>
        <div>
            <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Barangay</label>
            <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700 truncate" x-text="'Brgy. ' + selected.barangay"></p>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Purpose / Reason for Request</label>
            <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700" x-text="selected.purpose"></p>
        </div>
        <div>
            <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Reference No.</label>
            <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm font-mono text-slate-700 truncate" x-text="selected.ref"></p>
        </div>
        <div>
            <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Date Filed</label>
            <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700" x-text="selected.submitted"></p>
        </div>
    </div>
</div>
