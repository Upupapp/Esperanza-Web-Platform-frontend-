{{--
    _incident-detail-modal.blade.php
    Shared real incident detail modal for Sakuna -- @include'd from any tab
    that needs it (Command Center, Incidents). Reads/writes the parent
    x-data's selectedIncident/showIncidentDetail/showEscalateForm/
    showAssignForm/escalateSeverity/escalateNote/assignTo/personnel, and
    calls the shared openIncidentDetail()/actIncidentVerb() methods.
    GET /sakuna/incidents/{ref} for the fetch; POST /sakuna/incidents/{ref}/
    {verb} for every action (validate/assign/dispatch/escalate/resolve/close).
--}}
<div
    x-show="showIncidentDetail"
    x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-[150ms] ease-exit"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showIncidentDetail = false"
    x-trap="showIncidentDetail"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="showIncidentDetail = false">
    <div
        x-transition:enter="transition duration-[200ms] ease-emphasized"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        class="bg-white rounded-[28px] shadow-float w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden"
    >
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center">
                    <i data-lucide="flame" class="w-4.5 h-4.5 text-rose-600"></i>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-ink-body" x-text="selectedIncident?.ref ?? '—'"></h2>
                    <p class="text-xs text-ink-muted mt-0.5" x-text="(selectedIncident?.type ?? '') + ' — Brgy. ' + (selectedIncident?.barangay ?? '')"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span :class="statusBadge(selectedIncident?.status ?? '')" x-text="incidentStatusLabel(selectedIncident?.status ?? '')" :title="'Status on record: ' + (selectedIncident?.status ?? '')"></span>
                <button aria-label="Close" @click="showIncidentDetail = false" class="w-8 h-8 rounded-2xl hover:bg-slate-100 flex items-center justify-center text-ink-muted hover:text-slate-600 transition-colors ml-2">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Modal Body --}}
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

            <div class="p-4 bg-slate-50 rounded-xl border border-brand-600/10">
                <p class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-1.5">Description</p>
                <p class="text-sm text-slate-700 leading-relaxed" x-text="selectedIncident?.description || '—'"></p>
            </div>

            {{-- The photos the resident sent with the report (citizen app). A photo
                 still being scanned has no link yet. --}}
            <div x-show="(selectedIncident?.photos || []).length" x-cloak>
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5 mb-2">
                    <i data-lucide="image" class="w-3.5 h-3.5"></i> Photos from the resident
                </h4>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="(photo, idx) in (selectedIncident?.photos || [])" :key="idx">
                        <div>
                            <a x-show="photo.url" :href="photo.url" target="_blank" rel="noopener" class="block aspect-square rounded-xl overflow-hidden border border-slate-200 bg-slate-50 hover:border-brand-600/40 transition-colors" :aria-label="'Open photo ' + (idx + 1) + ' of ' + selectedIncident.photos.length">
                                <img :src="photo.url" :alt="'Photo ' + (idx + 1) + ' sent with the report'" class="w-full h-full object-cover" loading="lazy">
                            </a>
                            <div x-show="!photo.url" class="aspect-square rounded-xl border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-center px-2">
                                <span class="text-[11px] text-ink-muted">Being checked — available shortly</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="space-y-3">
                    <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i> Incident Information
                    </h4>
                    <dl class="space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-ink-muted flex-shrink-0">Type</dt>
                            <dd class="text-xs font-medium text-ink-body text-right" x-text="selectedIncident?.type ?? '—'"></dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-ink-muted flex-shrink-0">Severity</dt>
                            <dd><span :class="severityBadge(selectedIncident?.severity ?? '') + ' px-2 py-0.5 rounded-full text-xs font-medium border'" x-text="selectedIncident?.severity ?? '—'"></span></dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-ink-muted flex-shrink-0">Date Reported</dt>
                            <dd class="text-xs font-medium text-ink-body text-right" x-text="selectedIncident?.reported ? new Date(selectedIncident.reported).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-ink-muted flex-shrink-0">Source</dt>
                            <dd class="text-xs font-medium text-ink-body text-right" x-text="selectedIncident?.source || '—'"></dd>
                        </div>
                    </dl>
                </div>

                <div class="space-y-3">
                    <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Location & Reporter
                    </h4>
                    <dl class="space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-ink-muted flex-shrink-0">Barangay</dt>
                            <dd class="text-xs font-medium text-ink-body text-right" x-text="selectedIncident?.barangay ?? '—'"></dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-ink-muted flex-shrink-0">Sitio</dt>
                            <dd class="text-xs font-medium text-ink-body text-right" x-text="selectedIncident?.sitio || '—'"></dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-ink-muted flex-shrink-0">Reporter</dt>
                            <dd class="text-xs font-medium text-ink-body text-right" x-text="selectedIncident?.reporter_name || '—'"></dd>
                        </div>
                        {{-- How to reach the resident who reported it (2026-10-17): the read is audited. --}}
                        <template x-if="selectedIncident?.reporter">
                            <div class="space-y-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <dt class="text-xs text-ink-muted flex-shrink-0">Account</dt>
                                    <dd class="text-xs font-mono text-ink-body text-right" x-text="selectedIncident.reporter.account_no"></dd>
                                </div>
                                <div class="flex items-start justify-between gap-2" x-show="selectedIncident.reporter.mobile">
                                    <dt class="text-xs text-ink-muted flex-shrink-0">Mobile</dt>
                                    <dd class="text-xs font-medium text-right"><a :href="'tel:' + selectedIncident.reporter.mobile" class="text-brand-700 hover:underline" x-text="selectedIncident.reporter.mobile"></a></dd>
                                </div>
                                <div class="flex items-start justify-between gap-2" x-show="selectedIncident.reporter.email">
                                    <dt class="text-xs text-ink-muted flex-shrink-0">Email</dt>
                                    <dd class="text-xs font-medium text-ink-body text-right break-all" x-text="selectedIncident.reporter.email"></dd>
                                </div>
                            </div>
                        </template>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-ink-muted flex-shrink-0">Assigned To</dt>
                            <dd class="text-xs font-medium text-ink-body text-right" x-text="selectedIncident?.assigned_name || 'Unassigned'"></dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Timeline --}}
            <div>
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5 mb-3">
                    <i data-lucide="history" class="w-3.5 h-3.5"></i> Incident Timeline
                </h4>
                <div class="space-y-0">
                    <template x-for="(entry, idx) in (selectedIncident?.timeline || [])" :key="idx">
                        <div class="flex items-start gap-3 py-2.5" :class="idx < (selectedIncident.timeline.length - 1) ? 'border-b border-slate-50' : ''">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <div class="w-2 h-2 rounded-full bg-brand-400 mt-1.5"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-semibold text-slate-700 capitalize" x-text="entry.verb"></span>
                                    <span class="text-[11px] text-ink-muted" x-text="entry.from + (entry.to && entry.to !== entry.from ? ' → ' + entry.to : '')"></span>
                                </div>
                                <p class="text-[11px] text-ink-muted mt-0.5" x-show="entry.note" x-text="entry.note"></p>
                            </div>
                            <span class="text-[11px] text-ink-muted whitespace-nowrap flex-shrink-0 mt-0.5" x-text="entry.at ? new Date(entry.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></span>
                        </div>
                    </template>
                    <p x-show="!(selectedIncident?.timeline || []).length" class="text-xs text-ink-muted py-2">No actions recorded yet — reported, awaiting validation.</p>
                </div>
            </div>

            {{-- Escalate mini-form --}}
            <div x-show="showEscalateForm" x-cloak class="p-4 bg-orange-50 border border-orange-200 rounded-xl space-y-2.5">
                <p class="text-xs font-semibold text-orange-800">Escalate — raise severity without rewinding progress</p>
                <div class="flex items-center gap-2">
                    <select aria-label="New severity" x-model="escalateSeverity" class="text-xs rounded-xl border border-orange-200 py-1.5 px-2">
                        <option>Critical</option><option>High</option><option>Medium</option><option>Low</option>
                    </select>
                    <input type="text" x-model="escalateNote" placeholder="Note (optional)" class="flex-1 text-xs rounded-xl border border-orange-200 py-1.5 px-2">
                </div>
                <div class="flex gap-2">
                    <button @click="actIncidentVerb(selectedIncident, 'escalate', { severity: escalateSeverity, note: escalateNote || null }); escalateNote = ''" class="px-3 py-1.5 text-xs font-semibold bg-orange-600 hover:bg-orange-700 text-white rounded-xl transition-colors">Confirm Escalation</button>
                    <button @click="showEscalateForm = false" class="px-3 py-1.5 text-xs font-medium bg-white text-slate-600 border border-slate-200 rounded-xl transition-colors">Cancel</button>
                </div>
            </div>

            {{-- Assign mini-form --}}
            <div x-show="showAssignForm" x-cloak class="p-4 bg-violet-50 border border-violet-200 rounded-xl space-y-2.5">
                <p class="text-xs font-semibold text-violet-800">Assign to personnel</p>
                <div class="flex items-center gap-2">
                    <select aria-label="Assign to" x-model="assignTo" class="flex-1 text-xs rounded-xl border border-violet-200 py-1.5 px-2">
                        <option value="">Select…</option>
                        <template x-for="p in personnel" :key="p.id"><option :value="p.id" x-text="p.name + ' (' + p.employee_id + ')'"></option></template>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button :disabled="!assignTo" @click="actIncidentVerb(selectedIncident, 'assign', { assigned_to: assignTo }); assignTo = ''" class="px-3 py-1.5 text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white rounded-xl transition-colors disabled:opacity-40">Confirm Assignment</button>
                    <button @click="showAssignForm = false" class="px-3 py-1.5 text-xs font-medium bg-white text-slate-600 border border-slate-200 rounded-xl transition-colors">Cancel</button>
                </div>
            </div>

        </div>{{-- /Modal Body --}}

        {{-- Modal Footer Actions --}}
        <div class="flex flex-wrap items-center gap-2 px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 flex-shrink-0">
            <button x-show="selectedIncident?.available_verbs?.includes('validate')" @click="actIncidentVerb(selectedIncident, 'validate')" class="px-3 py-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-colors">Validate</button>
            <button x-show="selectedIncident?.available_verbs?.includes('assign')" @click="showAssignForm = !showAssignForm; showEscalateForm = false" class="px-3 py-1.5 text-xs font-medium bg-white hover:bg-white/60 text-violet-700 border border-violet-200 rounded-xl transition-colors">Assign</button>
            <button x-show="selectedIncident?.available_verbs?.includes('dispatch')" @click="actIncidentVerb(selectedIncident, 'dispatch')" class="px-3 py-1.5 text-xs font-medium bg-white hover:bg-white/60 text-purple-700 border border-purple-200 rounded-xl transition-colors">Dispatch</button>
            <button x-show="selectedIncident?.available_verbs?.includes('escalate')" @click="showEscalateForm = !showEscalateForm; showAssignForm = false" class="px-3 py-1.5 text-xs font-medium bg-white hover:bg-white/60 text-orange-700 border border-orange-200 rounded-xl transition-colors">Escalate</button>
            <button x-show="selectedIncident?.available_verbs?.includes('resolve')" @click="actIncidentVerb(selectedIncident, 'resolve')" class="px-3 py-1.5 text-xs font-medium bg-white hover:bg-white/60 text-emerald-700 border border-emerald-200 rounded-xl transition-colors">Resolve</button>
            <button x-show="selectedIncident?.available_verbs?.includes('close')" @click="actIncidentVerb(selectedIncident, 'close')" class="px-3 py-1.5 text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200 rounded-xl transition-colors">Close</button>
            <button @click="showIncidentDetail = false" class="ml-auto px-4 py-1.5 text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-colors">Dismiss</button>
        </div>
    </div>
</div>{{-- /Incident Detail Modal --}}
