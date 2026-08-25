{{-- Read-only "submitted application" replica — the citizen's original form,
     locked, styled like the actual document it represents (same letterhead
     treatment as the government-ID tiles elsewhere in this app) rather than
     a plain dashboard field list. Shared by Dokyu, Tulong, and Payments
     detail modals so every record renders the exact same format, not a
     bespoke one per module. Expects the parent x-data scope to expose
     `selected` with citizen, barangay, ref, submitted, purpose. --}}
<div class="mt-4 rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
    {{-- Letterhead — mirrors the "Republic of the Philippines" header used
         on submitted ID tiles, so this reads as a facsimile of the actual
         filed document instead of a generic summary card. --}}
    <div class="relative bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 px-4 py-3.5 text-white overflow-hidden">
        <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="relative flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[8px] uppercase tracking-widest text-white/50">Republic of the Philippines · Municipality of Esperanza, Masbate</p>
                <p class="text-xs font-bold uppercase tracking-wide mt-0.5">Submitted Application</p>
            </div>
            <span class="inline-flex items-center gap-1 text-[10px] font-medium text-white/70 bg-white/10 rounded-full px-2 py-1 shrink-0">
                <i data-lucide="lock" class="w-3 h-3"></i> As filed — read only
            </span>
        </div>
    </div>

    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3.5 bg-white">
        <div class="flex items-start gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
            <div class="min-w-0 flex-1">
                <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-0.5">Applicant Name</label>
                <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700 truncate" x-text="selected.citizen"></p>
            </div>
        </div>
        <div class="flex items-start gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
            <div class="min-w-0 flex-1">
                <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-0.5">Barangay</label>
                <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700 truncate" x-text="'Brgy. ' + selected.barangay"></p>
            </div>
        </div>
        <div class="flex items-start gap-2.5 sm:col-span-2">
            <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="clipboard-list" class="w-4 h-4"></i></span>
            <div class="min-w-0 flex-1">
                <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-0.5">Purpose / Reason for Request</label>
                <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700" x-text="selected.purpose"></p>
            </div>
        </div>
        <div class="flex items-start gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="hash" class="w-4 h-4"></i></span>
            <div class="min-w-0 flex-1">
                <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-0.5">Reference No.</label>
                <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm font-mono text-slate-700 truncate" x-text="selected.ref"></p>
            </div>
        </div>
        <div class="flex items-start gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="calendar-days" class="w-4 h-4"></i></span>
            <div class="min-w-0 flex-1">
                <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-0.5">Date Filed</label>
                <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700" x-text="selected.submitted"></p>
            </div>
        </div>
    </div>

    {{-- Full mobile form preview — every field the citizen actually filled
         in on the Esperanza Mobile app's own step-by-step wizard for this
         specific service, ported field-for-field from that app's
         ServiceFormSpec data (see config/esperanza_service_form_specs.php).
         Only renders for services that have a sourced form on file; every
         other service keeps just the generic fields above, exactly
         mirroring how mobile itself falls back to a plain Purpose +
         Attachments flow for any catalog item with no sourced formSpec. --}}
    <template x-if="selected.form_steps && selected.form_steps.length">
        <div class="border-t border-slate-100">
            <div class="px-4 pt-3.5 pb-1 flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-3 h-3 text-slate-300"></i>
                <p class="text-[10px] text-slate-400" x-text="'From the Esperanza Mobile app’s own ' + selected.type + ' form'"></p>
            </div>
            <template x-for="step in selected.form_steps" :key="step.label">
                <div class="px-4 pb-3.5">
                    <p class="text-xs font-semibold text-navy-900 mb-0.5" x-text="step.label"></p>
                    <p class="text-[11px] text-slate-400 mb-2" x-show="step.description" x-text="step.description"></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <template x-for="field in step.fields" :key="field.key">
                            <div class="flex items-start gap-2.5" :class="field.type === 'textarea' ? 'sm:col-span-2' : ''">
                                <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center shrink-0"><i :data-lucide="fieldIcon(field)" class="w-4 h-4"></i></span>
                                <div class="min-w-0 flex-1">
                                    <label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-0.5" x-text="field.label"></label>
                                    <template x-if="field.type === 'checkbox'">
                                        <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm flex items-center gap-1.5"
                                           :class="field.answer ? 'text-emerald-700' : 'text-slate-400'">
                                            <i :data-lucide="field.answer ? 'check-circle-2' : 'circle'" class="w-3.5 h-3.5 shrink-0"></i>
                                            <span x-text="field.answer ? 'Yes' : 'No'"></span>
                                        </p>
                                    </template>
                                    <template x-if="field.type === 'derivedAge'">
                                        <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700"
                                           x-text="derivedAgeDisplay(step, field)"></p>
                                    </template>
                                    <template x-if="field.type === 'date'">
                                        <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700" x-text="formatFieldDate(field.answer)"></p>
                                    </template>
                                    <template x-if="field.type === 'multiselect'">
                                        <div class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 flex flex-wrap gap-1.5">
                                            <template x-for="opt in (field.answer || [])" :key="opt">
                                                <span class="inline-flex items-center text-[10px] font-semibold px-2 py-1 rounded-full ring-1 ring-inset bg-brand-50 text-brand-700 ring-brand-200" x-text="opt"></span>
                                            </template>
                                            <span class="text-sm text-slate-400" x-show="!field.answer || !field.answer.length">—</span>
                                        </div>
                                    </template>
                                    <template x-if="!['checkbox', 'derivedAge', 'date', 'multiselect'].includes(field.type)">
                                        <p class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                                           :class="(field.answer ?? '') !== '' ? 'border-slate-200 bg-slate-50/80 text-slate-700' : 'border-slate-200 bg-slate-50/50 text-slate-400 italic'"
                                           x-text="(field.answer ?? '') !== '' ? field.answer : 'Not provided'"></p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
