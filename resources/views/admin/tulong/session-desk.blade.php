{{-- ============================================================
     The session window and the New / Edit session form (welfare round,
     2026-10-29). The window: when and where, the facilitators, the
     participants (role, case, account, attendance, certificate), Add
     waiting cases, a walk-in filed through Apply for New Tulong, Remove,
     Save attendance, Edit, Cancel with a reason, and "Finish and issue
     certificates" - confirmed here on the page, saying who receives one.
     The form: service, title, date, start and end, venue, capacity, notes,
     and the facilitators (name, position, role; a counselor's DSWD
     accreditation number and its validity). Placed after the case window,
     so it opens above it. Logic: sessionDesk() in
     resources/js/welfare-admin.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
@php
    $sdLabel = 'block text-xs font-semibold text-slate-700 mb-1';
    $sdInput = 'w-full rounded-2xl border-0 surface-pressed-sm py-2 px-3.5 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard';
@endphp
<div x-data="sessionDesk()"
     x-on:tulong-open-session.window="sdShow($event.detail?.ref)"
     x-on:tulong-new-session.window="sfNew()"
     x-on:welfare-session-changed.window="if ($event.detail?.source !== 'desk' && ses && sdOpen && $event.detail?.ref === ses.ref) { $event.detail.session ? sdTake($event.detail.session) : sdShow(ses.ref) }">

    {{-- The session window --}}
    <div x-data="{ get open() { return sdOpen }, set open(v) { sdOpen = v } }">
    <x-ui.modal title="Group session" maxWidth="xl">
        <div x-show="sdLoading && !ses" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-24 opacity-80" /></div>
        <p x-show="sdError" x-cloak class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" role="alert" aria-live="assertive" x-text="sdError"></p>
        <p x-show="sdResult" x-cloak class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800" role="status" x-text="sdResult"></p>
        <template x-if="ses">
            <div class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-mono text-ink-muted" x-text="ses.ref"></p>
                        <p class="text-base font-bold text-ink" x-text="ses.title || ses.service_name"></p>
                        <p class="text-xs text-ink-muted" x-text="ses.service_name"></p>
                    </div>
                    <span class="text-[11px] font-semibold rounded-full px-2.5 py-0.5 ring-1 ring-inset" :class="ses.status === 'Done' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : (ses.status === 'Cancelled' ? 'bg-slate-100 text-slate-600 ring-slate-200' : 'bg-indigo-50 text-indigo-700 ring-indigo-200')" x-text="ses.status"></span>
                </div>
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div><dt class="text-ink-muted">Date</dt><dd class="font-semibold text-ink" x-text="day(ses.date)"></dd></div>
                    <div><dt class="text-ink-muted">Time</dt><dd class="font-semibold text-ink" x-text="time(ses.start_time) + (ses.end_time ? ' – ' + time(ses.end_time) : '')"></dd></div>
                    <div class="col-span-2"><dt class="text-ink-muted">Venue</dt><dd class="font-semibold text-ink" x-text="ses.venue"></dd></div>
                    <div><dt class="text-ink-muted">Participants</dt><dd class="font-semibold text-ink" x-text="sdTotals().all + (ses.capacity ? ' of ' + ses.capacity : '')"></dd></div>
                    <div><dt class="text-ink-muted">Attended</dt><dd class="font-semibold text-ink" x-text="sdTotals().here"></dd></div>
                    <div x-show="ses.status === 'Done'"><dt class="text-ink-muted">Certificates</dt><dd class="font-semibold text-ink" x-text="ses.counts.certificates ?? '—'"></dd></div>
                    <div x-show="ses.cancel_reason" class="col-span-2"><dt class="text-ink-muted">Cancelled because</dt><dd class="text-ink" x-text="ses.cancel_reason"></dd></div>
                </dl>
                <p x-show="ses.notes" class="text-xs text-slate-600 whitespace-pre-line" x-text="ses.notes"></p>

                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.07em] text-ink-muted mb-1.5">Facilitators</p>
                    <ul class="flex flex-wrap gap-2">
                        <template x-for="(f, fi) in ses.facilitators" :key="fi">
                            <li class="rounded-xl border border-slate-200 bg-white/70 px-3 py-1.5 text-xs">
                                <span class="font-semibold text-ink" x-text="f.name"></span>
                                <span class="text-ink-muted" x-text="' · ' + [f.position, facRole(f.role)].filter(Boolean).join(' · ')"></span>
                                <span x-show="f.accreditation_no" class="block text-[11px] text-ink-muted" x-text="'DSWD Accreditation No. ' + f.accreditation_no + (f.accreditation_valid_until ? ', valid until ' + day(f.accreditation_valid_until) : '')"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Participants --}}
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                        <p class="text-[11px] font-bold uppercase tracking-[.07em] text-ink-muted">Participants</p>
                        <div class="flex flex-wrap gap-2" x-show="sdScheduled() && sdCan('edit')">
                            <x-ui.button size="sm" variant="secondary" icon="list-plus" x-bind:disabled="sdBusy" @click="sdLoadWaiting()">Add waiting cases</x-ui.button>
                            <x-ui.button size="sm" variant="secondary" icon="user-plus" x-bind:disabled="sdBusy" @click="sdWalkIn()">Add a walk-in</x-ui.button>
                        </div>
                    </div>
                    <p x-show="!ses.participants.length" class="text-xs text-ink-muted">No one is in this session yet.</p>
                    <div x-show="ses.participants.length" class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-xs">
                            <thead><tr class="bg-slate-50 text-left text-[10.5px] uppercase tracking-wide text-ink-muted">
                                <th class="px-3 py-2">Attended</th><th class="px-3 py-2">Name</th><th class="px-3 py-2">Case</th><th class="px-3 py-2 hidden sm:table-cell">Account</th><th class="px-3 py-2">Certificate</th><th class="px-3 py-2"><span class="sr-only">Remove</span></th>
                            </tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="p in ses.participants" :key="p.id">
                                    <tr>
                                        <td class="px-3 py-2">
                                            <input type="checkbox" x-model="p._attended" :disabled="!sdScheduled() || !sdCan('edit')" :aria-label="p.name + ' attended'" class="rounded text-emerald-600 focus:ring-emerald-200">
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="font-semibold text-ink" x-text="p.name"></span>
                                            <span class="ml-1 text-[10px] font-semibold rounded-full px-1.5 py-0.5" :class="p.role === 'partner' ? 'bg-pink-50 text-pink-700' : 'bg-slate-100 text-slate-600'" x-text="roleLabel(p.role)"></span>
                                            <span x-show="ses.status === 'Done' && p.attended === false" class="block text-[10.5px] text-amber-700">Did not attend</span>
                                        </td>
                                        <td class="px-3 py-2"><button type="button" x-show="p.request_ref" @click="sdOpenCase(p)" class="font-mono text-brand-600 hover:underline" x-text="p.request_ref"></button></td>
                                        <td class="px-3 py-2 hidden sm:table-cell font-mono text-ink-muted" x-text="p.account_no || '—'"></td>
                                        <td class="px-3 py-2">
                                            <button type="button" x-show="p.certificate" @click="sdCert(p)" class="inline-flex items-center gap-1 text-brand-600 hover:underline"><i data-lucide="award" class="w-3 h-3"></i><span x-text="p.certificate?.control_number"></span></button>
                                            <span x-show="!p.certificate" class="text-ink-faint">—</span>
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap">
                                            <template x-if="sdScheduled() && sdCan('edit')">
                                                <span>
                                                    <button type="button" x-show="!p._confirmRemove" @click="p._confirmRemove = true" class="text-rose-600 hover:underline">Remove</button>
                                                    <span x-show="p._confirmRemove" class="inline-flex items-center gap-2">
                                                        <button type="button" :disabled="sdBusy" @click="sdRemove(p)" class="font-semibold text-rose-700 hover:underline">Remove</button>
                                                        <button type="button" @click="p._confirmRemove = false" class="text-ink-muted">Keep</button>
                                                    </span>
                                                </span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p x-show="sdScheduled() && sdDirty()" x-cloak class="mt-1.5 text-[11px] text-amber-700">The attendance on screen is not saved yet.</p>
                </div>

                {{-- Add waiting cases --}}
                <div x-show="sdPanel === 'waiting'" x-cloak class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-3">
                    <p class="text-xs font-semibold text-indigo-900">Cases waiting for a session of this service</p>
                    <p x-show="sdWaitingLoading" class="text-xs text-ink-muted mt-1">Loading…</p>
                    <p x-show="sdWaitingFailed" class="text-xs text-rose-700 mt-1">Could not load them. <button type="button" class="underline" @click="sdLoadWaiting()">Try again</button></p>
                    <p x-show="!sdWaitingLoading && !sdWaitingFailed && !sdWaiting.length" class="text-xs text-ink-muted mt-1">No case is waiting.</p>
                    <ul class="mt-2 space-y-1 max-h-56 overflow-y-auto">
                        <template x-for="c in sdWaiting" :key="c.ref">
                            <li><label class="flex items-start gap-2 rounded-lg bg-white/80 border border-slate-200 px-2.5 py-1.5 text-xs cursor-pointer">
                                <input type="checkbox" x-model="c._pick" class="mt-0.5 rounded text-brand-600 focus:ring-brand-200">
                                <span class="min-w-0">
                                    <span class="font-semibold text-ink" x-text="[c.applicant || c.citizen, c.partner_name].filter(Boolean).join(' and ')"></span>
                                    <span class="block text-[11px] text-ink-muted" x-text="[c.ref, c.barangay ? 'Brgy. ' + c.barangay : '', c.status, c.submitted ? 'filed ' + day(c.submitted) : ''].filter(Boolean).join(' · ')"></span>
                                </span>
                            </label></li>
                        </template>
                    </ul>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <x-ui.button size="sm" icon="list-plus" x-bind:disabled="sdBusy || !sdPicked().length" @click="sdAddWaiting()"><span x-text="'Add ' + sdPicked().length + ' case' + (sdPicked().length === 1 ? '' : 's')"></span></x-ui.button>
                        <x-ui.button size="sm" variant="secondary" @click="sdPanel = null">Close</x-ui.button>
                    </div>
                </div>

                {{-- Cancel the session --}}
                <div x-show="sdPanel === 'cancel'" x-cloak class="rounded-xl border border-rose-200 bg-rose-50/50 p-3 space-y-2">
                    <label for="sd-cancel" class="block text-xs font-semibold text-rose-900">Why is the session cancelled? The participants are told, and their cases wait for a new schedule.</label>
                    <textarea id="sd-cancel" x-model="sdCancelReason" rows="2" maxlength="500" class="w-full rounded-xl border-0 px-2.5 py-1.5 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-rose-100"></textarea>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" variant="danger" icon="calendar-x" x-bind:disabled="sdBusy || sdCancelReason.trim().length < 5" @click="sdCancel()">Cancel the session</x-ui.button>
                        <x-ui.button size="sm" variant="secondary" @click="sdPanel = null">Keep it</x-ui.button>
                    </div>
                </div>

                {{-- Finish and issue certificates: confirmed here, no browser confirm(). --}}
                <div x-show="sdPanel === 'finish'" x-cloak class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3 space-y-2" role="region" aria-label="Finish the session">
                    <p class="text-xs font-semibold text-emerald-900">Finish the session and issue the certificates?</p>
                    <ul class="text-xs text-emerald-900 list-disc pl-4 space-y-0.5">
                        <li>A certificate goes to every case whose participants <strong>all</strong> attended<span x-show="sdType(ses.service_key)?.sessionCouple">: for a couple, both partners</span>. It is issued on the case, the case is completed, and each participant with an account is notified.</li>
                        <li>A case with someone absent stays open, marked "Did not attend", and waits for a new session.</li>
                        <li x-show="sdType(ses.service_key)?.certificate?.requiresAccreditation">This certificate needs a counselor with a DSWD accreditation valid on the session day; it is printed with their name and number.</li>
                        <li>The attendance on screen is saved first. Finishing cannot be undone.</li>
                    </ul>
                    <p class="text-xs font-semibold text-emerald-900" x-text="sdCases().filter((c) => c.complete).length + ' case(s) will receive a certificate; ' + sdCases().filter((c) => !c.complete).length + ' will wait for a new session.'"></p>
                    <ul class="text-[11px] text-emerald-900 space-y-0.5 max-h-32 overflow-y-auto">
                        <template x-for="c in sdCases()" :key="c.ref || c.names.join()">
                            <li x-text="(c.complete ? '✓ ' : '✗ ') + c.names.join(' and ') + (c.ref ? ' (' + c.ref + ')' : '')"></li>
                        </template>
                    </ul>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" icon="award" loading="sdBusy" @click="sdFinish()">Finish and issue certificates</x-ui.button>
                        <x-ui.button size="sm" variant="secondary" @click="sdPanel = null">Not yet</x-ui.button>
                    </div>
                </div>
            </div>
        </template>
        <x-slot:footer>
            <div class="mr-auto flex flex-wrap gap-2" x-show="ses && sdScheduled()">
                <x-ui.button size="sm" variant="ghost" icon="pencil" x-show="sdCan('edit')" @click="sfEdit()">Edit</x-ui.button>
                <x-ui.button size="sm" variant="ghost" icon="calendar-x" x-show="sdCan('edit')" @click="sdPanel = 'cancel'; sdCancelReason = ''; $nextTick(() => document.getElementById('sd-cancel')?.focus())">Cancel session</x-ui.button>
            </div>
            <x-ui.button size="sm" variant="secondary" icon="save" x-show="ses && sdScheduled() && sdCan('edit')" x-bind:disabled="sdBusy || !sdDirty()" @click="sdSaveAttendance()">Save attendance</x-ui.button>
            <x-ui.button size="sm" icon="award" x-show="ses && sdScheduled() && sdCan('release')" x-bind:disabled="sdBusy || !ses?.participants.length" @click="sdPanel = 'finish'">Finish and issue certificates</x-ui.button>
            <x-ui.button size="sm" variant="secondary" x-show="!ses || !sdScheduled()" @click="sdOpen = false">Close</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    </div>

    {{-- New / Edit session --}}
    <div x-data="{ get open() { return sfOpen }, set open(v) { sfOpen = v } }">
    <x-ui.modal title="Session" maxWidth="lg">
        <template x-if="sf">
            <div class="space-y-3">
                <p class="text-sm font-bold text-ink" x-text="sfRef ? 'Edit ' + sfRef : 'New session'"></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label for="sf-service" class="{{ $sdLabel }}">Service *</label>
                        <select id="sf-service" x-model="sf.service_key" @change="sfService(sf.service_key)" :disabled="!!sfRef" class="{{ $sdInput }}">
                            <option value="">—</option>
                            <template x-for="t in sdServices()" :key="t.key"><option :value="t.key" x-text="t.name" :selected="sf.service_key === t.key"></option></template>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="sf-title" class="{{ $sdLabel }}">Title *</label>
                        <input id="sf-title" type="text" x-model="sf.title" maxlength="150" class="{{ $sdInput }}">
                    </div>
                    <div>
                        <label for="sf-date" class="{{ $sdLabel }}">Date *</label>
                        <input id="sf-date" type="date" x-model="sf.date" class="{{ $sdInput }}">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="sf-start" class="{{ $sdLabel }}">Starts *</label>
                            <input id="sf-start" type="time" x-model="sf.start_time" class="{{ $sdInput }}">
                        </div>
                        <div>
                            <label for="sf-end" class="{{ $sdLabel }}">Ends</label>
                            <input id="sf-end" type="time" x-model="sf.end_time" class="{{ $sdInput }}">
                        </div>
                    </div>
                    <div>
                        <label for="sf-venue" class="{{ $sdLabel }}">Venue *</label>
                        <input id="sf-venue" type="text" x-model="sf.venue" maxlength="200" placeholder="e.g. MSWDO Conference Room, Municipal Hall" class="{{ $sdInput }}">
                    </div>
                    <div>
                        <label for="sf-capacity" class="{{ $sdLabel }}">Capacity (participants)</label>
                        <input id="sf-capacity" type="number" min="1" x-model="sf.capacity" placeholder="No limit" class="{{ $sdInput }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="sf-notes" class="{{ $sdLabel }}">Notes</label>
                        <textarea id="sf-notes" x-model="sf.notes" rows="2" maxlength="1000" placeholder="What to bring, the topics" class="{{ $sdInput }}"></textarea>
                    </div>
                </div>

                <fieldset>
                    <legend class="text-xs font-bold text-ink mb-1">Facilitators *</legend>
                    <p class="text-[11px] text-ink-muted mb-2" x-text="sdType(sf.service_key)?.certificate?.signedBy === 'counselor' ? 'The certificate is signed by the counselor: add the DSWD-accredited counselor with the accreditation number.' : 'The certificate lists every facilitator, name and position, as signatories.'"></p>
                    <div class="space-y-2">
                        <template x-for="(f, i) in sf.facilitators" :key="i">
                            <div class="rounded-xl border border-slate-200 p-2.5 grid grid-cols-1 sm:grid-cols-3 gap-2 relative">
                                <div><label :for="'sf-fn-' + i" class="{{ $sdLabel }}">Name</label><input :id="'sf-fn-' + i" type="text" x-model="f.name" maxlength="150" class="{{ $sdInput }}"></div>
                                <div><label :for="'sf-fp-' + i" class="{{ $sdLabel }}">Position</label><input :id="'sf-fp-' + i" type="text" x-model="f.position" maxlength="150" placeholder="e.g. MSWDO, Municipal Health Officer" class="{{ $sdInput }}"></div>
                                <div>
                                    <label :for="'sf-fr-' + i" class="{{ $sdLabel }}">Role</label>
                                    <select :id="'sf-fr-' + i" x-model="f.role" class="{{ $sdInput }}">
                                        <template x-for="r in sfRoles" :key="r[0]"><option :value="r[0]" x-text="r[1]" :selected="f.role === r[0]"></option></template>
                                    </select>
                                </div>
                                <div x-show="f.role === 'counselor'" class="sm:col-span-2"><label :for="'sf-fa-' + i" class="{{ $sdLabel }}">DSWD accreditation no.</label><input :id="'sf-fa-' + i" type="text" x-model="f.accreditation_no" maxlength="60" class="{{ $sdInput }}"></div>
                                <div x-show="f.role === 'counselor'"><label :for="'sf-fv-' + i" class="{{ $sdLabel }}">Valid until</label><input :id="'sf-fv-' + i" type="date" x-model="f.accreditation_valid_until" class="{{ $sdInput }}"></div>
                                <button type="button" x-show="sf.facilitators.length > 1" @click="sfDrop(i)" :aria-label="'Remove facilitator ' + (i + 1)" class="absolute top-1.5 right-1.5 text-ink-faint hover:text-rose-600"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="sfAdd()" class="mt-2 text-xs font-semibold text-brand-700 hover:underline">Add a facilitator</button>
                </fieldset>
                <p x-show="sfNeedsAccreditation() && !sfAccredited()" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] text-amber-800">No counselor with an accreditation valid on the session day yet. The session can be scheduled, but it cannot be finished, nor its certificates issued, without one (DSWD MC 01 s. 2019). A counselor takes at most 6 couples a session.</p>
            </div>
        </template>
        <x-slot:footer>
            <p class="mr-auto min-w-0 text-xs text-rose-600" role="alert" aria-live="assertive" x-text="sfError"></p>
            <x-ui.button size="sm" variant="secondary" @click="sfOpen = false">Cancel</x-ui.button>
            <x-ui.button size="sm" icon="check" loading="sfBusy" @click="sfSave()"><span x-text="sfRef ? 'Save changes' : 'Schedule the session'"></span></x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    </div>
</div>
