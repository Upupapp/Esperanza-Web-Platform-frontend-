{{-- ============================================================
     An event's Registrations window (Esperanza round 2, 2026-10-30), opened
     from a published event's card: who registered and who attended, for the
     municipality's record, and the certificates of those who attended.
     - Counts: registered (of the capacity), attended, not here yet.
     - Check in by code: the code on the resident's phone or printout
       (XXXX-XXXX, with or without the dash, any case), from the event's day;
       the field keeps focus for the next person, so a phone or a handheld
       QR scanner works through a queue.
     - Add attendee: a resident found in the records (the people search of
       Apply for New Tulong), or a name and barangay for someone who is not.
     - The list: search, Not here yet / Attended / Cancelled, a tick per
       person for attendance (saved with Save attendance), the certificate
       once issued (opens the certificate window), and Download list (CSV).
     - Issue certificates: for an officer who approves events, from the
       event's day, confirmed here on the page (no confirm()).
     Logic: eventDesk() in resources/js/event-desk.js.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
@php
    $edInput = 'w-full rounded-2xl border-0 surface-pressed-sm py-2 px-3.5 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard';
@endphp
<div x-data="eventDesk()" x-on:event-desk.window="edShow($event.detail)">
    <div x-data="{ get open() { return edOpen; }, set open(v) { edOpen = v; } }">
    <x-ui.modal title="Registrations" maxWidth="2xl" :fullscreen="true">
        <template x-if="edEvent">
            <div class="space-y-4 max-w-4xl mx-auto">
                {{-- The event --}}
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-base font-bold text-ink" x-text="edEvent.title || edEvent.name"></p>
                        <p class="text-xs text-ink-muted mt-0.5" x-text="[edDay(edEvent.date), edEvent.time, edEvent.venue, edEvent.barangay ? 'Brgy. ' + edEvent.barangay : 'Municipality-wide'].filter(Boolean).join(' · ')"></p>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                            <span class="inline-flex items-center gap-1 text-[10.5px] font-medium rounded-full px-2 py-0.5" :class="edEvent.registration?.open ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600'"><i data-lucide="ticket" class="w-3 h-3"></i><span x-text="edRegistrationLabel()"></span></span>
                            <span class="inline-flex items-center gap-1 text-[10.5px] font-medium rounded-full px-2 py-0.5" :class="edCertOn() ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-600'"><i data-lucide="award" class="w-3 h-3"></i><span x-text="edCertOn() ? (edEvent.certificate?.title || 'Certificate') + (edEvent.certificates_issued_at ? ' · issued' : '') : 'No certificate'"></span></span>
                            <span x-show="edEvent.status !== 'Released'" class="text-[10.5px] font-medium rounded-full px-2 py-0.5 bg-slate-100 text-slate-600" x-text="edEvent.status"></span>
                        </div>
                    </div>
                    <x-ui.button size="sm" variant="ghost" icon="pencil" x-show="edEvent.status === 'Released' && $store.session.canPerm('comm_events', 'approve')" @click="edEdit()">Edit event</x-ui.button>
                </div>

                {{-- Counts --}}
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div class="rounded-2xl bg-white/80 border border-brand-600/10 px-3.5 py-2.5">
                        <dt class="text-[10.5px] font-semibold uppercase tracking-wide text-ink-muted">Registered</dt>
                        <dd class="text-xl font-extrabold text-ink tabular-nums"><span x-text="edTotals().registered"></span> <span class="text-xs font-medium text-ink-muted" x-text="edCapacityText()"></span></dd>
                    </div>
                    <div class="rounded-2xl bg-emerald-50/70 border border-emerald-200/70 px-3.5 py-2.5">
                        <dt class="text-[10.5px] font-semibold uppercase tracking-wide text-emerald-800">Attended</dt>
                        <dd class="text-xl font-extrabold text-emerald-800 tabular-nums" x-text="edTotals().attended"></dd>
                    </div>
                    <div class="rounded-2xl bg-white/80 border border-brand-600/10 px-3.5 py-2.5">
                        <dt class="text-[10.5px] font-semibold uppercase tracking-wide text-ink-muted" x-text="edDayCome() ? 'Not here yet' : 'Cancelled'"></dt>
                        <dd class="text-xl font-extrabold text-ink tabular-nums" x-text="edDayCome() ? edTotals().waiting : edTotals().cancelled"></dd>
                    </div>
                    <div class="rounded-2xl bg-amber-50/70 border border-amber-200/70 px-3.5 py-2.5">
                        <dt class="text-[10.5px] font-semibold uppercase tracking-wide text-amber-800">Certificates</dt>
                        <dd class="text-xl font-extrabold text-amber-800 tabular-nums" x-text="edCertOn() ? edTotals().certificates : '—'"></dd>
                    </div>
                </dl>

                <p x-show="edError" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" role="alert" aria-live="assertive" x-text="edError"></p>
                <p x-show="edResult" x-cloak class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800" role="status" x-text="edResult"></p>

                {{-- Check in by code --}}
                <form x-show="edCanEdit()" @submit.prevent="edCheckIn()" class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-3.5">
                    <label for="ed-code" class="block text-xs font-semibold text-emerald-900 mb-1.5">Check in by code</label>
                    <div class="flex items-stretch gap-2">
                        <input id="ed-code" type="text" x-model="edCode" @input="edTypeCode()" maxlength="12" autocomplete="off" autocapitalize="characters" spellcheck="false" enterkeyhint="go" placeholder="ABCD-2345" :disabled="!edDayCome()" aria-describedby="ed-code-hint"
                               class="flex-1 min-w-0 rounded-2xl border-0 surface-pressed py-2.5 px-4 text-lg font-mono font-bold tracking-[.18em] uppercase text-ink placeholder:text-ink-faint placeholder:font-medium focus:bg-white/90 focus:ring-4 focus:ring-emerald-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60">
                        <x-ui.button type="submit" icon="user-check" x-bind:disabled="edChecking || !edDayCome()">Check in</x-ui.button>
                    </div>
                    <p id="ed-code-hint" class="text-[11px] text-emerald-900/80 mt-1.5" x-text="edDayCome() ? 'The code on the resident’s phone or printout: 8 letters and numbers, with or without the dash. Press Enter or Check in; the box is ready for the next person.' : 'Check-in opens on the day of the event, ' + edDay(edEvent.date) + '.'"></p>
                    <div x-show="edCheck" x-cloak role="status" aria-live="polite" class="mt-2.5 rounded-xl border px-3 py-2.5 flex items-start gap-2.5"
                         :class="edCheck?.tone === 'ok' ? 'border-emerald-300 bg-white text-emerald-900' : (edCheck?.tone === 'again' ? 'border-amber-300 bg-amber-50 text-amber-900' : 'border-rose-300 bg-rose-50 text-rose-800')">
                        <span class="shrink-0 mt-0.5" aria-hidden="true">
                            <span x-show="edCheck?.tone === 'ok'"><i data-lucide="circle-check" class="w-5 h-5"></i></span>
                            <span x-show="edCheck?.tone === 'again'"><i data-lucide="info" class="w-5 h-5"></i></span>
                            <span x-show="edCheck?.tone === 'error'"><i data-lucide="circle-alert" class="w-5 h-5"></i></span>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-bold" x-text="edCheck?.title"></span>
                            <span class="block text-xs" x-text="edCheck?.text"></span>
                        </span>
                    </div>
                </form>

                {{-- Toolbar --}}
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[180px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="search" x-model="edQuery" placeholder="Search a name, barangay, account or code" aria-label="Search the registrations" class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                    <x-ui.button size="sm" variant="secondary" icon="user-plus" x-show="edCanEdit()" @click="edAdd()">Add attendee</x-ui.button>
                    <x-ui.button size="sm" variant="secondary" icon="download" x-show="edRows.length" @click="edCsv()">Download list (CSV)</x-ui.button>
                </div>
                <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit max-w-full overflow-x-auto" role="group" aria-label="Show">
                    <button type="button" @click="edFilter = 'all'" :aria-pressed="edFilter === 'all'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="edFilter === 'all' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" x-text="'Everyone (' + edTotals().registered + ')'"></button>
                    <button type="button" @click="edFilter = 'waiting'" :aria-pressed="edFilter === 'waiting'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="edFilter === 'waiting' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" x-text="'Not here yet (' + edTotals().waiting + ')'"></button>
                    <button type="button" @click="edFilter = 'here'" :aria-pressed="edFilter === 'here'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="edFilter === 'here' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" x-text="'Attended (' + edTotals().attended + ')'"></button>
                    <button type="button" x-show="edTotals().cancelled" @click="edFilter = 'cancelled'" :aria-pressed="edFilter === 'cancelled'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="edFilter === 'cancelled' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" x-text="'Cancelled (' + edTotals().cancelled + ')'"></button>
                </div>

                {{-- Add attendee --}}
                <div x-show="edPanel === 'add'" x-cloak class="rounded-2xl border border-indigo-200 bg-indigo-50/40 p-3.5 space-y-3" role="region" aria-label="Add attendee">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-indigo-950">Add attendee</p>
                        <div class="flex items-center gap-1 surface-pressed-sm rounded-xl p-1" role="group" aria-label="How to find the person">
                            <button type="button" x-show="!edPBlocked" @click="edMode = 'search'" :aria-pressed="edMode === 'search'" class="px-2.5 py-1 text-[11px] font-medium rounded-lg" :class="edMode === 'search' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted'">Find a resident</button>
                            <button type="button" @click="edMode = 'new'" :aria-pressed="edMode === 'new'" class="px-2.5 py-1 text-[11px] font-medium rounded-lg" :class="edMode === 'new' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted'">Not in the records</button>
                        </div>
                    </div>
                    <div x-show="edMode === 'search'" class="space-y-2">
                        <input id="ed-person-q" type="search" x-model="edPq" @input.debounce.300ms="edPSearch()" placeholder="Name, account no. or record no. (two letters or more)" aria-label="Find a resident" class="{{ $edInput }}">
                        <p x-show="edPLoading" class="text-[11px] text-ink-muted">Searching…</p>
                        <p x-show="edPFailed" x-cloak class="text-[11px] text-rose-700">Could not search. <button type="button" class="underline" @click="edPSearch()">Try again</button></p>
                        <p x-show="edPSearched && !edPLoading && !edPResults.length" x-cloak class="text-[11px] text-ink-muted">No one found. Check the spelling, or choose Not in the records.</p>
                        <ul x-show="edPResults.length && !edPerson" class="max-h-56 overflow-y-auto space-y-1">
                            <template x-for="p in edPResults" :key="(p.individual_ref || '') + (p.account_no || '')">
                                <li><button type="button" @click="edPerson = p" class="w-full text-left rounded-xl bg-white/90 border border-slate-200 px-3 py-2 hover:border-brand-300 hover:bg-brand-50/40 transition-colors">
                                    <span class="block text-xs font-semibold text-ink" x-text="p.name"></span>
                                    <span class="block text-[11px] text-ink-muted" x-text="edPLine(p) + (edListed(p) ? ' · already on the list' : '')"></span>
                                </button></li>
                            </template>
                        </ul>
                        <div x-show="edPerson" x-cloak class="flex items-center justify-between gap-2 rounded-xl bg-white border border-brand-300 px-3 py-2">
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold text-ink" x-text="edPerson?.name"></span>
                                <span class="block text-[11px] text-ink-muted" x-text="edPLine(edPerson) + (edPerson && !edPerson.account_no ? ' · no portal account: their certificate is printed here' : '')"></span>
                            </span>
                            <button type="button" @click="edPerson = null" class="shrink-0 text-[11px] font-semibold text-brand-600 hover:underline">Change</button>
                        </div>
                    </div>
                    <div x-show="edMode === 'new'" x-cloak class="space-y-2">
                        <p x-show="edPBlocked" class="text-[11px] text-ink-muted">Your account cannot search the resident records; write the person’s name and barangay.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <input id="ed-new-name" type="text" x-model="edNew.name" maxlength="150" placeholder="Full name" aria-label="Full name" class="{{ $edInput }}">
                            <select x-model="edNew.barangay" aria-label="Barangay" class="{{ $edInput }}">
                                <option value="">Barangay…</option>
                                <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="b" :selected="edNew.barangay === b"></option></template>
                            </select>
                            <input type="tel" x-model="edNew.contact" maxlength="40" placeholder="Contact (optional)" aria-label="Contact number (optional)" class="{{ $edInput }}">
                        </div>
                    </div>
                    <label x-show="edDayCome()" class="flex items-center gap-2 text-xs text-ink-body cursor-pointer">
                        <input type="checkbox" x-model="edMark" class="rounded text-emerald-600 focus:ring-emerald-200">
                        Mark as attended now
                    </label>
                    <p x-show="edAddProblem() && (edPerson || edMode === 'new')" x-cloak class="text-[11px] text-rose-700" x-text="edAddProblem()"></p>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" icon="user-plus" x-bind:disabled="edBusy || !!edAddProblem()" @click="edAddSave()">Add to the list</x-ui.button>
                        <x-ui.button size="sm" variant="secondary" @click="edPanel = null">Close</x-ui.button>
                    </div>
                </div>

                {{-- Issue certificates: confirmed here, no browser confirm(). --}}
                <div x-show="edPanel === 'issue'" x-cloak class="rounded-2xl border border-amber-200 bg-amber-50/60 p-3.5 space-y-2" role="region" aria-label="Issue certificates">
                    <p class="text-sm font-semibold text-amber-950" x-text="'Issue ' + (edIssueCount() === 1 ? '1 certificate' : edIssueCount() + ' certificates') + '?'"></p>
                    <ul class="text-xs text-amber-950 list-disc pl-4 space-y-0.5">
                        <li x-text="'Everyone marked as attended who has none yet receives a ' + (edEvent.certificate?.title || 'certificate') + ' with their name, a control number and a QR code anyone can check on the Verify page.'"></li>
                        <li>Residents with an account are told and find it on the event in the portal and the app. For the others, open their certificate here and print it.</li>
                        <li x-show="edDirty().length">The attendance on screen is saved first.</li>
                        <li>No one receives two. Someone marked as attended later gets theirs when certificates are issued again.</li>
                    </ul>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <x-ui.button size="sm" icon="award" x-bind:disabled="edBusy" @click="edIssue()"><span x-text="edIssueCount() === 1 ? 'Issue 1 certificate' : 'Issue ' + edIssueCount() + ' certificates'"></span></x-ui.button>
                        <x-ui.button size="sm" variant="secondary" @click="edPanel = null">Not yet</x-ui.button>
                    </div>
                </div>

                {{-- The list --}}
                <div>
                    <div x-show="edLoading && !edRows.length" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-14" /><x-ui.skeleton class="h-14 opacity-80" /></div>
                    <p x-show="edFailed" x-cloak class="text-xs text-rose-700" role="alert">Could not load the registrations. <button type="button" class="underline font-semibold" @click="edLoad()">Try again</button></p>
                    <div x-show="!edLoading && !edFailed && !edRows.length" x-cloak class="flex flex-col items-center text-center py-8 rounded-2xl border border-dashed border-slate-200 bg-white/60">
                        <span class="w-12 h-12 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-3"><i data-lucide="clipboard-list" class="w-6 h-6"></i></span>
                        <p class="text-sm font-medium text-slate-600">No one has registered yet.</p>
                        <p class="text-xs text-ink-muted mt-1 max-w-sm" x-text="edEvent.registration?.open ? 'Residents register on the event in the portal and the app. Walk-ins are added here with Add attendee.' : 'Online registration is off for this event. Add the people who come with Add attendee, or turn registration on in Edit.'"></p>
                    </div>
                    <p x-show="edRows.length && !edList().length" x-cloak class="text-xs text-ink-muted py-3">No one here matches.</p>
                    <ul x-show="edList().length" class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white/80 overflow-hidden">
                        <template x-for="r in edList()" :key="r.id">
                            <li class="flex items-center gap-3 px-3 py-2" :class="r._attended !== (r.attended === true) && !r.cancelled_at ? 'bg-amber-50/70' : (r.cancelled_at ? 'opacity-60' : '')">
                                <label class="shrink-0 w-10 h-10 grid place-items-center rounded-xl" :class="edCanMark(r) ? 'cursor-pointer hover:bg-emerald-50' : ''">
                                    <input type="checkbox" x-model="r._attended" :disabled="!edCanMark(r)" :aria-label="r.name + ' attended'" class="w-5 h-5 rounded text-emerald-600 focus:ring-emerald-200 disabled:opacity-50">
                                </label>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink truncate" x-text="r.name"></p>
                                    <p class="text-[11px] text-ink-muted truncate" x-text="edLine(r)"></p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-mono text-xs font-bold text-ink tracking-wider" x-text="edShowCode(r.code)"></p>
                                    <button type="button" x-show="r.certificate" @click="edCert(r)" class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-600 hover:underline"><i data-lucide="award" class="w-3 h-3"></i><span x-text="r.certificate?.control_number || 'Certificate'"></span></button>
                                    <p x-show="!r.certificate" class="text-[10.5px]" :class="edStateTone(r)" x-text="edState(r)"></p>
                                </div>
                            </li>
                        </template>
                    </ul>
                    <p x-show="edDirty().length" x-cloak class="mt-1.5 text-[11px] text-amber-700" x-text="(edDirty().length === 1 ? '1 change' : edDirty().length + ' changes') + ' to attendance not saved yet.'"></p>
                </div>

                {{-- Why certificates cannot be issued yet --}}
                <p x-show="edCanIssue() && edIssueBlock() && edCertOn()" x-cloak class="text-[11px] text-ink-muted flex items-start gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5 shrink-0 mt-px"></i><span x-text="edIssueBlock()"></span></p>
                <p x-show="edCanIssue() && !edCertOn()" x-cloak class="text-[11px] text-ink-muted flex items-start gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5 shrink-0 mt-px"></i><span>Certificates are off for this event. To give them, turn them on in <button type="button" class="font-semibold text-brand-600 hover:underline" @click="edEdit()">Edit event</button>, under Certificate.</span></p>
            </div>
        </template>
        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-ui.button size="sm" variant="secondary" icon="save" x-show="edCanEdit()" x-bind:disabled="edBusy || !edDirty().length" @click="edSaveAttendance()">Save attendance</x-ui.button>
                <x-ui.button size="sm" icon="award" x-show="edCanIssue() && edCertOn()" x-bind:disabled="edBusy || !!edIssueBlock()" x-bind:title="edIssueBlock() || null" @click="edAskIssue()">Issue certificates</x-ui.button>
                <x-ui.button size="sm" variant="secondary" @click="edOpen = false">Close</x-ui.button>
            </div>
        </x-slot:footer>
    </x-ui.modal>
    </div>
</div>
