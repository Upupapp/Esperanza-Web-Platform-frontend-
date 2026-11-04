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
    aria-labelledby="incident-detail-title"
    @keydown.escape.window="if (incidentConfirmVerb) incidentConfirmVerb = ''; else if (showAssignForm) showAssignForm = false; else if (showEscalateForm) showEscalateForm = false; else showIncidentDetail = false">
    <div
        x-transition:enter="transition duration-[200ms] ease-emphasized"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        class="bg-white rounded-[28px] shadow-float w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden"
    >
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center text-brand-700">
                    {{-- Rebuilt whole on each incident: Lucide swaps the <i> for an <svg>, so a bound <i> would pile up. --}}
                    <span class="contents" x-html="`<i data-lucide='${incidentTypeIcon(selectedIncident?.type || '')}' class='w-4.5 h-4.5'></i>`" x-effect="selectedIncident?.type; $nextTick(() => window.renderIcons?.())"></span>
                </div>
                <div>
                    <h2 id="incident-detail-title" class="text-base font-semibold text-ink-body" x-text="selectedIncident?.ref ?? '—'"></h2>
                    <p class="text-xs text-ink-muted mt-0.5" x-text="[selectedIncident?.type, selectedIncident?.barangay, selectedIncident?.sitio, selectedIncident?.reported && isWaiting(selectedIncident) ? 'waiting ' + waitedLabel(selectedIncident.reported) : null].filter(Boolean).join(' · ')"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span x-show="selectedIncident?.severity" :class="severityBadge(selectedIncident?.severity ?? '')" class="sk-pill" x-text="(selectedIncident?.severity ?? '') + ' severity'"></span>
                <span :class="statusBadge(selectedIncident?.status ?? '')" x-text="incidentStatusLabel(selectedIncident?.status ?? '')" :title="'Status on record: ' + (selectedIncident?.status ?? '')"></span>
                <button aria-label="Close window" title="Close window" @click="showIncidentDetail = false" class="sk-mclose ml-2">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Modal Body --}}
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

            <p x-show="incidentDetailFailed" x-cloak role="status" class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 inline-flex items-start gap-1.5">
                <i data-lucide="cloud-off" class="w-4 h-4 shrink-0 mt-0.5"></i>
                <span x-text="'The full details could not load. ' + offlineWhy + ' Showing the summary from the list: the description, reporter, people recorded and history are not loaded.'"></span>
            </p>

            <div class="p-4 bg-slate-50 rounded-xl border border-brand-600/10">
                <p class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-1.5">Description</p>
                <p class="text-sm text-slate-700 leading-relaxed" :class="incidentDetailFailed && !selectedIncident?.description ? 'italic text-ink-muted' : ''" x-text="selectedIncident?.description || (incidentDetailFailed ? 'Not loaded' : '—')"></p>
            </div>

            {{-- The people recorded under this incident (owner, 2026-10-30): the
                 same cases the Missing Persons tab lists, not a copy. Null when
                 the role may not read them or the feature is off. --}}
            <div x-show="Array.isArray(selectedIncident?.persons)" x-cloak class="p-4 rounded-xl border border-rose-100 bg-white">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <p class="text-xs font-bold text-ink-muted uppercase tracking-[.07em]">Missing, deceased or unidentified persons</p>
                    <a x-show="can('sakuna_missing','create')" :href="tabUrls['missing-persons'] + '?incident=' + encodeURIComponent(selectedIncident?.ref || '')" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Record a person</a>
                </div>
                <p x-show="!(selectedIncident?.persons || []).length" class="text-xs text-ink-muted">No one is recorded under this incident.</p>
                <ul class="space-y-1">
                    <template x-for="p in (selectedIncident?.persons || [])" :key="p.ref">
                        <li class="flex flex-wrap items-center gap-2 text-xs">
                            <a :href="tabUrls['missing-persons'] + '?incident=' + encodeURIComponent(selectedIncident?.ref || '')" class="font-mono text-brand-600 hover:underline" x-text="p.ref"></a>
                            <span class="font-semibold text-ink" x-text="p.name || 'Unidentified'"></span>
                            <span class="text-ink-muted" x-text="p.status"></span>
                        </li>
                    </template>
                </ul>
            </div>

            {{-- The photos the resident sent with the report (citizen app). A photo
                 still being scanned has no link yet. --}}
            <div x-show="(selectedIncident?.photos || []).length" x-cloak>
                <h3 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5 mb-2">
                    <i data-lucide="image" class="w-3.5 h-3.5"></i> Photos from the resident
                </h3>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="(photo, idx) in (selectedIncident?.photos || [])" :key="idx">
                        <div>
                            <a x-show="photo.url" :href="photo.url" target="_blank" rel="noopener" class="block aspect-square rounded-xl overflow-hidden border border-slate-200 bg-slate-50 hover:border-brand-600/40 transition-colors" :aria-label="'Open photo ' + (idx + 1) + ' of ' + selectedIncident.photos.length">
                                <img :src="photo.url" :alt="'Photo ' + (idx + 1) + ' sent with the report'" class="w-full h-full object-cover" loading="lazy">
                            </a>
                            <div x-show="!photo.url" class="aspect-square rounded-xl border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-center px-2">
                                <span class="text-xs text-ink-muted">Being checked — available shortly</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i> Incident Information
                    </h3>
                    <dl class="space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-ink-muted flex-shrink-0">Type</dt>
                            <dd class="text-xs font-medium text-ink-body text-right" x-text="selectedIncident?.type ?? '—'"></dd>
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
                    <h3 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Location & Reporter
                    </h3>
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
                            <dd class="text-xs font-medium text-ink-body text-right" x-text="selectedIncident?.reporter_name || (incidentDetailFailed ? 'Not loaded' : '—')"></dd>
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

            {{-- Where it is on the map (2026-10-26). A report without a pin says so;
                 staff who may edit incidents add one or move it. --}}
            <div>
                <h3 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5 mb-2">
                    <i data-lucide="map" class="w-3.5 h-3.5"></i> Map Location
                </h3>
                <div x-show="!incidentLocationEditing">
                    <div x-show="hasMapPin(selectedIncident)">
                        <div x-data="sakunaPinView({ point: () => ({ lat: selectedIncident?.lat, lng: selectedIncident?.lng }), severity: () => selectedIncident?.severity })" x-effect="show()">
                            <div x-ref="map" class="sakuna-map h-56 w-full rounded-xl overflow-hidden border border-slate-200" role="img" aria-label="Map showing where the incident is"></div>
                            <p x-show="viewState === 'failed'" x-cloak class="text-xs text-amber-800 font-semibold mt-1.5">The map could not load. The coordinates below are still the saved ones.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs">
                            <span class="font-mono text-slate-700" x-text="hasMapPin(selectedIncident) ? window.sakunaGeo.coordText(selectedIncident.lat, selectedIncident.lng) : ''"></span>
                            <a :href="hasMapPin(selectedIncident) ? window.sakunaGeo.osmUrl(selectedIncident.lat, selectedIncident.lng) : '#'" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">Open in OpenStreetMap</a>
                            <a :href="hasMapPin(selectedIncident) ? window.sakunaGeo.directionsUrl(selectedIncident.lat, selectedIncident.lng) : '#'" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">Directions</a>
                        </div>
                        <p x-show="selectedIncident?.source === 'Citizen Portal'" class="text-xs text-ink-muted mt-1">Pinned by the resident who reported it. Where it matters, confirm the place when you call back.</p>
                    </div>
                    <p x-show="!hasMapPin(selectedIncident)" class="text-xs text-ink-muted">No map location was saved with this incident. Use the barangay and sitio above.</p>
                    <button x-show="can('sakuna_incidents', 'edit')" type="button" @click="startIncidentLocation()" :disabled="incidentDetailFailed || onLastCopy" :title="incidentDetailFailed || onLastCopy ? 'Waits for the connection' : ''"
                        class="sk-btn sk-btn-soft sk-btn-sm mt-2">
                        <i data-lucide="map-pinned" class="w-3.5 h-3.5"></i><span x-text="hasMapPin(selectedIncident) ? 'Move the pin' : 'Add map location'"></span>
                    </button>
                </div>
                <div x-show="incidentLocationEditing" x-cloak>
                    <x-sakuna.location-picker target="incidentLocationDraft" search subject="incident" />
                    <div class="flex items-center gap-2 mt-2">
                        <button type="button" @click="saveIncidentLocation()" :disabled="incidentLocationSaving"
                            class="sk-btn sk-btn-p sk-btn-sm">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i><span x-text="incidentLocationSaving ? 'Saving…' : (hasMapPin(incidentLocationDraft) ? 'Save location' : 'Save without a pin')"></span>
                        </button>
                        <button type="button" @click="incidentLocationEditing = false" class="sk-btn sk-btn-sm">Cancel</button>
                    </div>
                </div>
            </div>

            {{-- Timeline --}}
            <div>
                <h3 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] flex items-center gap-1.5 mb-3">
                    <i data-lucide="history" class="w-3.5 h-3.5"></i> Incident Timeline
                </h3>
                <div class="space-y-0">
                    <template x-for="(entry, idx) in (selectedIncident?.timeline || [])" :key="idx">
                        <div class="flex items-start gap-3 py-2.5" :class="idx < (selectedIncident.timeline.length - 1) ? 'border-b border-slate-50' : ''">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <div class="w-2 h-2 rounded-full bg-brand-400 mt-1.5"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-semibold text-slate-700 capitalize" x-text="({ validate: 'validated', assign: 'assigned', dispatch: 'dispatched', escalate: 'escalated', resolve: 'resolved', close: 'closed', report: 'reported' })[entry.verb] || entry.verb"></span>
                                    <span class="text-xs text-ink-muted" x-text="incidentStatusLabel(entry.from) + (entry.to && entry.to !== entry.from ? ' → ' + incidentStatusLabel(entry.to) : '')"></span>
                                </div>
                                <p class="text-xs text-ink-muted mt-0.5" x-show="entry.note" x-text="entry.note"></p>
                            </div>
                            <span class="text-xs text-ink-muted whitespace-nowrap flex-shrink-0 mt-0.5" x-text="entry.at ? new Date(entry.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></span>
                        </div>
                    </template>
                    <p x-show="incidentDetailFailed" class="text-xs text-ink-muted italic py-2">History not loaded.</p>
                    <p x-show="!incidentDetailFailed && !(selectedIncident?.timeline || []).length" class="text-xs text-ink-muted py-2">No actions recorded yet — reported, awaiting validation.</p>
                </div>
            </div>

        </div>{{-- /Modal Body --}}

        {{-- Assign and Escalate open here, beside the buttons that opened them,
             never below the fold of the window (critique 2026-11-03, third run). --}}
        <div x-show="showEscalateForm || showAssignForm" x-cloak class="px-6 pt-3 border-t border-brand-600/10 flex-shrink-0 space-y-3"
             x-effect="if (showAssignForm) $nextTick(() => $refs.assignFirst?.focus()); else if (showEscalateForm) $nextTick(() => $refs.escalateFirst?.focus())">
                {{-- Escalate mini-form --}}
                <div x-show="showEscalateForm" x-cloak class="p-4 bg-amber-50/70 border border-amber-200 rounded-xl space-y-2.5">
                    <p class="text-xs font-semibold text-orange-800">Escalate — raise severity without rewinding progress</p>
                    <div class="flex items-center gap-2">
                        <select x-ref="escalateFirst" aria-label="New severity" x-model="escalateSeverity" class="text-xs rounded-xl border border-orange-200 py-1.5 px-2">
                            <template x-for="sev in escalateChoices()" :key="sev"><option :value="sev" x-text="sev"></option></template>
                        </select>
                        <input type="text" x-model="escalateNote" placeholder="Note (optional)" class="flex-1 text-xs rounded-xl border border-orange-200 py-1.5 px-2">
                    </div>
                    <div class="flex gap-2">
                        <button @click="actIncidentVerb(selectedIncident, 'escalate', { severity: escalateSeverity, note: escalateNote || null }); escalateNote = ''" class="sk-btn sk-btn-sm sk-btn-p" x-text="'Raise from ' + (selectedIncident?.severity || '—') + ' to ' + escalateSeverity">Raise severity</button>
                        <button @click="showEscalateForm = false" class="sk-btn sk-btn-sm">Cancel</button>
                    </div>
                </div>

                {{-- Assign (critique 2026-11-03, sixth run): one searchable list. MDRRMO
                     officers first as choices; everyone else behind "Show other
                     officers"; each with the open incidents already in hand. --}}
                <div x-show="showAssignForm" x-cloak class="p-4 bg-brand-50/60 border border-brand-100 rounded-xl space-y-2.5">
                    <p class="text-xs font-semibold text-brand-800" id="assign-list-label">Assign to</p>
                    <input x-ref="assignFirst" type="search" x-model="assignSearch" aria-label="Find a person to assign" placeholder="Type a name, ID or role…"
                        class="w-full text-sm rounded-xl border border-brand-100 py-1.5 px-2.5 bg-white">
                    <div role="radiogroup" aria-labelledby="assign-list-label" class="max-h-44 overflow-y-auto rounded-xl bg-white border border-slate-200 divide-y divide-slate-100">
                        <template x-for="g in assignGroups(personnel.filter(p => p.scope === 'Municipal' || p.scope === selectedIncident?.barangay))" :key="g.role">
                            <div x-show="/mdrrmo/i.test(g.role) || assignShowOthers || assignSearch.trim()">
                                <p class="px-3 pt-2 pb-1 text-[12px] font-semibold text-ink-muted" x-text="g.role"></p>
                                <template x-for="p in g.people" :key="p.id">
                                    <label class="flex items-center gap-2.5 px-3 py-1.5 text-sm cursor-pointer hover:bg-brand-50" :class="String(assignTo) === String(p.id) ? 'bg-brand-50' : ''">
                                        <input type="radio" name="assign-to" class="accent-brand-600" :value="p.id" x-model="assignTo"
                                            :aria-label="p.name + ' (' + p.employee_id + ') - ' + p.role + ', ' + (openLoad(p) ? openLoad(p) + ' open' : 'none open')">
                                        <span class="min-w-0 flex-1 truncate"><span class="text-ink-body" x-text="p.name"></span> <span class="sk-muted text-xs" x-text="p.employee_id"></span></span>
                                        <span class="text-xs shrink-0" :class="openLoad(p) ? 'text-amber-800 font-semibold' : 'sk-muted'" x-text="openLoad(p) ? openLoad(p) + ' open' : 'none open'"></span>
                                    </label>
                                </template>
                            </div>
                        </template>
                        <p x-show="!assignGroups(personnel.filter(p => p.scope === 'Municipal' || p.scope === selectedIncident?.barangay)).length" class="px-3 py-3 text-sm sk-muted">No one matches.</p>
                    </div>
                    <button type="button" x-show="!assignShowOthers && !assignSearch.trim() && assignGroups(personnel.filter(p => p.scope === 'Municipal' || p.scope === selectedIncident?.barangay)).some(g => !/mdrrmo/i.test(g.role))"
                        @click="assignShowOthers = true" class="sk-link" style="font-size: 12px;">Show other officers</button>
                    <p class="text-xs text-ink-muted">Only officers who can see incidents in this barangay are listed.</p>
                    <div class="flex gap-2">
                        <button :disabled="!assignTo" @click="actIncidentVerb(selectedIncident, 'assign', { assigned_to: assignTo }); assignTo = ''" class="sk-btn sk-btn-sm sk-btn-p">Assign</button>
                        <button @click="showAssignForm = false" class="sk-btn sk-btn-sm">Cancel</button>
                    </div>
                </div>
        </div>

        {{-- Modal Footer Actions (critique 2026-11-03) ─────────────────────────
             One primary button: the incident's next step. Assign and Escalate
             open their forms; the other steps wait under More actions. Marking
             resolved or closing the incident asks first, because it ends the
             work; closing the window is only the × above. --}}
        <div x-show="!showAssignForm && !showEscalateForm" class="flex flex-wrap items-center gap-2 px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 flex-shrink-0"
             x-data="{ more: false }" @keydown.escape.stop="if (more) more = false; else if (incidentConfirmVerb) incidentConfirmVerb = ''; else if (showAssignForm) showAssignForm = false; else if (showEscalateForm) showEscalateForm = false; else showIncidentDetail = false">
            <template x-if="incidentConfirmVerb">
                <div class="w-full flex flex-wrap items-center gap-2" role="alertdialog" aria-labelledby="incident-confirm-text">
                    <p id="incident-confirm-text" class="text-sm text-ink-body flex-1 min-w-[14rem]"
                       x-text="incidentConfirmVerb === 'close' ? 'Close ' + selectedIncident?.ref + '? It leaves the open list and no further steps are taken on it.' : 'Mark ' + selectedIncident?.ref + ' resolved? Use this when the response is finished.'"></p>
                    <button type="button" class="sk-btn sk-btn-sm" @click="incidentConfirmVerb = ''">Keep it open</button>
                    <button type="button" class="sk-btn sk-btn-sm sk-btn-p" :disabled="incidentActing"
                        @click="runIncidentVerb(selectedIncident, incidentConfirmVerb)"
                        x-text="incidentActing ? 'Saving…' : incidentVerbLabel(incidentConfirmVerb)"></button>
                </div>
            </template>
            <p x-show="onLastCopy" x-cloak class="w-full text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 inline-flex items-center gap-1.5">
                <i data-lucide="cloud-off" class="w-4 h-4 shrink-0"></i> This page is showing a last copy. Steps that change the incident wait until the connection returns.
            </p>
            <template x-if="!incidentConfirmVerb">
                <div class="w-full flex flex-wrap items-center gap-2" :class="onLastCopy ? 'opacity-50 pointer-events-none' : ''" :aria-disabled="onLastCopy ? 'true' : 'false'">
                    <button type="button" id="incident-next-step" x-show="nextIncidentVerb(selectedIncident)" class="sk-btn sk-btn-p" :disabled="incidentActing"
                        @click="askIncidentVerb(selectedIncident, nextIncidentVerb(selectedIncident))">
                        <span x-text="incidentActing ? 'Saving…' : incidentVerbLabel(nextIncidentVerb(selectedIncident))"></span>
                    </button>
                    <button type="button" x-show="selectedIncident?.available_verbs?.includes('assign')" @click="showAssignForm = !showAssignForm; showEscalateForm = false; loadPersonnel()" :aria-expanded="showAssignForm ? 'true' : 'false'" class="sk-btn">Assign</button>
                    <button type="button" x-show="selectedIncident?.available_verbs?.includes('escalate') && escalateChoices().length" @click="escalateSeverity = escalateChoices()[0]; showEscalateForm = !showEscalateForm; showAssignForm = false" :aria-expanded="showEscalateForm ? 'true' : 'false'" class="sk-btn">Escalate</button>
                    <div class="relative" x-show="otherIncidentVerbs(selectedIncident).length">
                        <button type="button" class="sk-btn" @click="more = !more" :aria-expanded="more ? 'true' : 'false'" aria-haspopup="menu">
                            More actions <i data-lucide="chevron-down"></i>
                        </button>
                        <div x-show="more" x-cloak @click.outside="more = false" role="menu"
                             class="absolute bottom-full mb-1.5 left-0 min-w-[11rem] rounded-2xl bg-white border border-slate-200 shadow-float p-1 z-10">
                            <template x-for="v in otherIncidentVerbs(selectedIncident)" :key="v">
                                <button type="button" role="menuitem" class="w-full text-left px-3 py-2 text-sm text-ink-body rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors"
                                        @click="more = false; askIncidentVerb(selectedIncident, v)" x-text="incidentVerbLabel(v)"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>{{-- /Incident Detail Modal --}}
