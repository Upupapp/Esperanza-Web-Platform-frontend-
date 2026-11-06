{{-- The Appointments page (scope round 2026-10-15): the bookings of the
     schedules this officer runs, one day at a time, with Confirm, Complete,
     No-show and Cancel; and the schedules themselves (days, hours, slot
     length, residents per slot) with their closed days. Which schedules an
     officer runs is the server's (AppointmentBook::schedulesFor); the logic is
     appointmentsDesk() in resources/js/scope-admin-requests.js. --}}
@php
    $field = 'w-full min-h-[40px] rounded-2xl border-0 surface-pressed-sm px-3 py-2 text-sm text-ink placeholder:text-ink-faint focus:bg-white/90 focus:outline-none focus:ring-4 focus:ring-brand-100';
    $small = 'text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none';
    $iconBtn = 'w-9 h-9 shrink-0 rounded-xl grid place-items-center surface-glass-sm text-ink-muted hover:text-ink transition-colors';
    $err = 'mt-1 text-xs text-rose-600';
@endphp
<div x-data="appointmentsDesk()" class="flex flex-col gap-[22px]">

    <div class="surface-glass rounded-[22px] px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11.5px] font-bold uppercase tracking-[.08em] text-ink-muted">Appointments</p>
            <h3 class="text-xl font-extrabold text-ink leading-tight" x-text="section === 'bookings' ? 'Bookings' : 'Schedules and closed days'">Appointments</h3>
        </div>
        <div x-show="mayBookings && maySchedules" x-cloak class="inline-flex p-1 rounded-2xl surface-pressed-sm" role="tablist" aria-label="Appointments">
            <button type="button" role="tab" :aria-selected="section === 'bookings'" @click="show('bookings')" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-[13px] transition-all duration-200" :class="section === 'bookings' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'font-semibold text-ink-muted hover:text-ink'"><i data-lucide="calendar-check" class="w-4 h-4"></i>Bookings</button>
            <button type="button" role="tab" :aria-selected="section === 'schedules'" @click="show('schedules')" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-[13px] transition-all duration-200" :class="section === 'schedules' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'font-semibold text-ink-muted hover:text-ink'"><i data-lucide="calendar-cog" class="w-4 h-4"></i>Schedules</button>
        </div>
    </div>

    <div x-show="!ready" aria-busy="true" class="space-y-3"><x-ui.skeleton class="h-24 rounded-[22px]" /><x-ui.skeleton class="h-24 rounded-[22px] opacity-80" /></div>
    <div x-show="ready && !mayBookings && !maySchedules" x-cloak class="surface-pressed rounded-2xl px-4 py-6 text-center text-[14.5px] font-bold text-ink">Appointments are not part of your role.</div>

    {{-- Bookings (owner, 2026-11-06): on the left one day at a time, today
         first; on the right what is coming, soonest first, like a list of
         notifications. Stacked on a phone, the day first. --}}
    <div x-show="ready && mayBookings && section === 'bookings'" x-cloak class="grid gap-[22px] lg:grid-cols-[minmax(0,1fr)_minmax(280px,360px)] items-start">
    <section id="appt-day-list" class="flex flex-col gap-4 min-w-0 scroll-mt-4" aria-label="Bookings by day">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="shiftDate(-1)" aria-label="Previous day" class="{{ $iconBtn }}"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                <label for="appt-date" class="sr-only">Day</label>
                <input id="appt-date" type="date" x-model="date" @change="loadBookings()" class="{{ $small }}">
                <button type="button" @click="shiftDate(1)" aria-label="Next day" class="{{ $iconBtn }}"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                <button type="button" x-show="date !== today" x-cloak @click="goToday()" class="px-2 py-1.5 text-xs font-bold text-brand-600 hover:underline">Today</button>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label for="appt-status" class="sr-only">Status</label>
                <select id="appt-status" x-model="statusFilter" @change="loadBookings()" class="{{ $small }}">
                    <option value="">All statuses</option>
                    <template x-for="s in statuses" :key="s"><option :value="s" x-text="s"></option></template>
                </select>
                <button type="button" @click="loadBookings()" aria-label="Reload the bookings" class="{{ $iconBtn }}"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
            </div>
        </div>
        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
            <h4 class="text-[15px] font-extrabold text-ink" x-text="dateLabel(date)"></h4>
            <p class="text-[12.5px] font-semibold text-ink-muted" x-show="todaySummary" x-text="todaySummary"></p>
        </div>

        <div x-show="bLoading && !bookings.length" aria-busy="true" class="space-y-3"><x-ui.skeleton class="h-28 rounded-[22px]" /><x-ui.skeleton class="h-28 rounded-[22px] opacity-80" /></div>
        <div x-show="bFailed" x-cloak><x-ui.error-state messageExpr="bError" retryExpr="loadBookings()" :retryable="true" /></div>
        <div x-show="!bLoading && !bFailed && !bookings.length" x-cloak class="surface-pressed rounded-2xl px-4 py-8 text-center">
            <p class="text-[14.5px] font-bold text-ink" x-text="statusFilter ? 'No ' + statusFilter.toLowerCase() + ' appointments ' + (date === today ? 'today.' : 'on this day.') : (date === today ? 'No appointments today' : 'No appointments on this day.')"></p>
            <p class="mt-1 text-[12.5px] text-ink-muted">Residents book the open slots of your schedules from the Citizen Web.</p>
        </div>

        <ul x-show="!bFailed && bookings.length" class="flex flex-col gap-3 transition-opacity" :class="bLoading && 'opacity-60'">
            <template x-for="b in bookings" :key="b.ref">
                <li class="surface-glass rounded-[22px] p-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div class="sm:w-24 shrink-0 flex sm:flex-col items-baseline gap-x-2">
                        <p class="text-[15px] font-extrabold text-ink tabular-nums" x-text="time(b.start_time)"></p>
                        <p class="text-[12px] text-ink-muted tabular-nums" x-text="'to ' + time(b.end_time)"></p>
                    </div>
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-[14.5px] font-bold text-ink" x-text="b.citizen ? b.citizen.name : 'Resident'"></p>
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset whitespace-nowrap" :class="statusClass(b.status)"><i :data-lucide="statusIcon(b.status)" class="w-3 h-3"></i><span x-text="b.status"></span></span>
                        </div>
                        <p class="text-[12px] text-ink-muted break-words"><span class="font-mono" x-text="b.ref"></span><span x-text="b.citizen ? ' · ' + b.citizen.account_no + (b.citizen.barangay ? ' · Brgy. ' + b.citizen.barangay : '') : ''"></span></p>
                        <p class="text-[12.5px] text-ink-body" x-text="[b.place, b.schedule, b.service].filter(Boolean).join(' · ')"></p>
                        <p class="text-[12.5px] text-ink-body break-words" x-show="b.purpose" x-text="'Purpose: ' + b.purpose"></p>
                        <p class="text-[12.5px]" x-show="b.request_ref"><a :href="requestLink(b.request_ref || '')" class="font-semibold text-brand-600 hover:underline" x-text="'Request ' + b.request_ref"></a></p>
                        <p class="text-[12px] text-rose-700 break-words" x-show="b.status === 'Cancelled' && b.cancel_reason" x-text="'Cancelled by ' + (b.cancelled_by === 'citizen' ? 'the resident' : 'the office') + ': ' + b.cancel_reason"></p>
                        <p class="text-[12px] text-ink-muted break-words" x-show="b.staff_note" x-text="'Note: ' + b.staff_note"></p>
                        <p class="text-[11.5px] text-ink-faint" x-show="b.handled_by" x-text="'Handled by ' + b.handled_by"></p>
                    </div>
                    <div x-show="isActive(b) && (can('approve') || can('edit') || can('reject'))" class="shrink-0 sm:w-40 flex flex-col gap-1.5">
                        <div class="flex flex-wrap gap-2 sm:flex-col sm:items-stretch">
                            <x-ui.button size="sm" icon="check" x-show="b.status === 'Booked' && can('approve')" x-bind:disabled="!!actingRef" @click="act(b, 'confirm')">Confirm</x-ui.button>
                            <x-ui.button size="sm" variant="secondary" icon="circle-check" x-show="can('edit')" x-bind:disabled="!!actingRef || tooEarly(b)" @click="act(b, 'complete')">Completed</x-ui.button>
                            <x-ui.button size="sm" variant="secondary" icon="user-x" x-show="can('reject')" x-bind:disabled="!!actingRef || tooEarly(b)" @click="act(b, 'no-show')">No-show</x-ui.button>
                            <x-ui.button size="sm" variant="ghost" icon="x" x-show="can('reject')" x-bind:disabled="!!actingRef" @click="askCancel(b)">Cancel…</x-ui.button>
                        </div>
                        <p x-show="tooEarly(b) && (can('edit') || can('reject'))" class="text-[11px] leading-snug text-ink-faint">Completed and No-show open an hour before the time.</p>
                    </div>
                </li>
            </template>
        </ul>
    </section>

    {{-- Upcoming: booked and confirmed from tomorrow on, the soonest first.
         A row opens its day on the left, where its actions are. --}}
    <aside class="surface-glass rounded-[22px] p-4 flex flex-col gap-3 min-w-0 lg:sticky lg:top-4" aria-labelledby="appt-upcoming-title" data-testid="appt-upcoming">
        <div class="flex items-center justify-between gap-2">
            <h4 id="appt-upcoming-title" class="text-[15px] font-extrabold text-ink flex items-center gap-2"><i data-lucide="bell-ring" class="w-4 h-4 text-brand-600"></i>Upcoming</h4>
            <button type="button" @click="loadUpcoming()" aria-label="Reload the upcoming appointments" class="{{ $iconBtn }}"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
        </div>
        <p class="text-[12px] text-ink-muted -mt-1">Booked and confirmed, from tomorrow. The soonest is first.</p>

        <div x-show="uLoading && !upcoming.length" aria-busy="true" class="space-y-2"><x-ui.skeleton class="h-16 rounded-2xl" /><x-ui.skeleton class="h-16 rounded-2xl opacity-80" /></div>
        <p x-show="uFailed" x-cloak class="text-[12.5px] text-rose-700" role="alert">Could not load the upcoming appointments. <button type="button" class="underline font-semibold" @click="loadUpcoming()">Try again</button></p>
        <p x-show="!uLoading && !uFailed && !upcoming.length" x-cloak class="surface-pressed rounded-2xl px-3 py-5 text-center text-[13px] font-semibold text-ink-muted">Nothing booked after today.</p>

        <ol x-show="!uFailed && upcoming.length" class="flex flex-col gap-2 transition-opacity" :class="uLoading && 'opacity-60'">
            <template x-for="b in upcoming" :key="b.ref">
                <li>
                    <button type="button" @click="openDay(b)" :aria-label="'Open ' + dateLabel(b.date) + ': ' + (b.citizen ? b.citizen.name : 'Resident')"
                            class="w-full text-left rounded-2xl border border-slate-200 bg-white/80 p-3 hover:border-brand-300 hover:bg-white transition-colors flex gap-3">
                        <span class="w-9 h-9 shrink-0 rounded-xl grid place-items-center" :class="statusClass(b.status)"><i :data-lucide="statusIcon(b.status)" class="w-4 h-4"></i></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10.5px] font-bold ring-1 ring-inset whitespace-nowrap" :class="soonClass(b)" x-text="soonLabel(b)"></span>
                                <span class="text-[12px] font-semibold text-ink tabular-nums" x-text="b.when || (day(b.date) + ' · ' + time(b.start_time))"></span>
                            </span>
                            <span class="block mt-1 text-[13.5px] font-bold text-ink truncate" x-text="b.citizen ? b.citizen.name : 'Resident'"></span>
                            <span class="block text-[12px] text-ink-muted truncate" x-text="[b.place, b.status].filter(Boolean).join(' · ')"></span>
                            <span class="block text-[12px] text-ink-body truncate" x-show="b.purpose" x-text="b.purpose"></span>
                        </span>
                    </button>
                </li>
            </template>
        </ol>
        <p x-show="uMore && !uFailed" x-cloak class="text-[11.5px] text-ink-faint">The next 20 are shown. Pick a later day on the left for the rest.</p>
    </aside>
    </div>

    {{-- Schedules, with their closed days. --}}
    <section x-show="ready && maySchedules && section === 'schedules'" x-cloak class="flex flex-col gap-4" aria-label="Schedules">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-[13.5px] text-ink-body max-w-2xl">When your office or barangay hall receives residents by appointment. Residents book the open slots from the Citizen Web.</p>
            <x-ui.button size="sm" icon="plus" x-show="canS('create')" @click="newSchedule()">New schedule</x-ui.button>
        </div>

        <div x-show="sLoading && !schedules.length" aria-busy="true" class="grid gap-4 lg:grid-cols-2"><x-ui.skeleton class="h-56 rounded-[22px]" /><x-ui.skeleton class="h-56 rounded-[22px] opacity-80" /></div>
        <div x-show="sFailed" x-cloak><x-ui.error-state messageExpr="sError" retryExpr="loadSchedules()" :retryable="true" /></div>
        <div x-show="!sLoading && !sFailed && !schedules.length" x-cloak class="surface-pressed rounded-2xl px-4 py-8 text-center">
            <p class="text-[14.5px] font-bold text-ink">No schedules yet.</p>
            <p class="mt-1 text-[12.5px] text-ink-muted" x-text="canS('create') ? 'Add one to let residents book a time with your office.' : 'Ask your department head to add one.'"></p>
        </div>

        <div x-show="!sFailed && schedules.length" class="grid gap-4 lg:grid-cols-2 items-start">
            <template x-for="s in schedules" :key="s.id">
                <article class="surface-glass rounded-[22px] p-4 sm:p-5 flex flex-col gap-3 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h4 class="text-[15.5px] font-extrabold text-ink leading-snug break-words" x-text="s.label"></h4>
                            <p class="text-[12.5px] text-ink-muted" x-text="s.place + ' · ' + (s.service || 'Any of its services')"></p>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset whitespace-nowrap" :class="s.active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200'"><i :data-lucide="s.active ? 'circle-check' : 'circle-pause'" class="w-3 h-3"></i><span x-text="s.active ? 'Open for booking' : 'Switched off'"></span></span>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-[12.5px]">
                        <div><dt class="text-ink-muted">Days</dt><dd class="font-semibold text-ink" x-text="weekdaysLabel(s.weekdays)"></dd></div>
                        <div><dt class="text-ink-muted">Hours</dt><dd class="font-semibold text-ink" x-text="time(s.start_time) + ' – ' + time(s.end_time)"></dd></div>
                        <div><dt class="text-ink-muted">Each slot</dt><dd class="font-semibold text-ink" x-text="s.slot_minutes + ' min, ' + s.capacity + (s.capacity === 1 ? ' resident' : ' residents')"></dd></div>
                        <div><dt class="text-ink-muted">In a day</dt><dd class="font-semibold text-ink" x-text="s.slots_per_day + (s.slots_per_day === 1 ? ' slot' : ' slots')"></dd></div>
                        <div><dt class="text-ink-muted">Booked up to</dt><dd class="font-semibold text-ink" x-text="s.book_days_ahead + ' days ahead'"></dd></div>
                        <div><dt class="text-ink-muted">Upcoming bookings</dt><dd class="font-semibold text-ink" x-text="s.upcoming"></dd></div>
                        <div class="col-span-2" x-show="s.location"><dt class="text-ink-muted">Where</dt><dd class="font-semibold text-ink break-words" x-text="s.location"></dd></div>
                    </dl>
                    <p x-show="s.notes" class="text-[12.5px] text-ink-body whitespace-pre-line break-words" x-text="s.notes"></p>

                    <div class="rounded-2xl surface-pressed-sm p-3 space-y-2">
                        <p class="text-[11.5px] font-bold uppercase tracking-[.06em] text-ink-muted">Closed days</p>
                        <ul x-show="s.closures.length" class="space-y-1">
                            <template x-for="c in s.closures" :key="c.id">
                                <li class="flex items-center justify-between gap-2 text-[12.5px]">
                                    <span class="min-w-0 break-words"><span class="font-semibold text-ink" x-text="day(c.date)"></span><span class="text-ink-muted" x-text="' · ' + c.reason"></span></span>
                                    <button type="button" x-show="canS('edit')" @click="removeClosure(s, c)" :disabled="s._busy" :aria-label="'Open ' + day(c.date) + ' for booking again'" class="w-8 h-8 shrink-0 rounded-lg grid place-items-center text-ink-muted hover:text-rose-600 hover:bg-white/70 disabled:opacity-40"><i data-lucide="x" class="w-4 h-4"></i></button>
                                </li>
                            </template>
                        </ul>
                        <p x-show="!s.closures.length" class="text-[12.5px] text-ink-muted">None coming up.</p>
                        <form x-show="canS('edit')" @submit.prevent="addClosure(s)" class="flex flex-col gap-2 sm:flex-row sm:items-end pt-1">
                            <div class="sm:w-40">
                                <label :for="'appt-close-date-' + s.id" class="block text-[11px] font-semibold text-ink-muted mb-1">Close a day</label>
                                <input :id="'appt-close-date-' + s.id" type="date" :min="today" x-model="s._closeDate" class="{{ $small }} w-full">
                            </div>
                            <div class="flex-1 min-w-0">
                                <label :for="'appt-close-reason-' + s.id" class="block text-[11px] font-semibold text-ink-muted mb-1">Reason</label>
                                <input :id="'appt-close-reason-' + s.id" type="text" maxlength="200" x-model="s._closeReason" placeholder="e.g. Town fiesta" class="{{ $small }} w-full">
                            </div>
                            <x-ui.button type="submit" size="sm" variant="secondary" icon="calendar-x" x-bind:disabled="s._busy || !s._closeDate || s._closeReason.trim().length < 3">Close</x-ui.button>
                        </form>
                        <p x-show="canS('edit')" class="text-[11.5px] text-ink-muted">Bookings already made for a closed day are cancelled with your reason, and each resident is notified.</p>
                    </div>

                    <div x-show="canS('edit') || canS('delete')" class="flex flex-wrap items-center gap-2">
                        <x-ui.button size="sm" variant="secondary" icon="pencil" x-show="canS('edit')" @click="editSchedule(s)">Edit</x-ui.button>
                        <x-ui.button size="sm" variant="ghost" icon="trash-2" x-show="canS('delete')" @click="askDelete(s)">Delete</x-ui.button>
                    </div>
                </article>
            </template>
        </div>
    </section>

    {{-- Cancel a booking: the resident is told the reason. --}}
    <div x-data="{ get open() { return cancelOpen }, set open(v) { cancelOpen = v } }">
        <x-ui.modal title="Cancel Appointment" maxWidth="sm">
            <div class="space-y-3">
                <p class="text-sm text-ink-body" x-text="cancelTarget ? cancelTarget.ref + ' · ' + (cancelTarget.citizen ? cancelTarget.citizen.name : 'Resident') + ' · ' + day(cancelTarget.date) + ', ' + time(cancelTarget.start_time) : ''"></p>
                <div>
                    <label for="appt-cancel-reason" class="block text-xs font-semibold text-ink mb-1">Reason <span class="font-normal text-ink-muted">(the resident is told)</span></label>
                    <textarea id="appt-cancel-reason" x-model="cancelReason" rows="3" maxlength="300" placeholder="e.g. The office is closed for an emergency meeting. Please book another day." class="{{ $field }} resize-none"></textarea>
                    <p class="mt-1 text-[11px] text-ink-muted" x-text="cancelReason.trim().length < 5 ? 'At least 5 characters.' : (300 - cancelReason.length) + ' characters left.'"></p>
                </div>
                <p x-show="cancelError" x-cloak role="alert" class="text-xs text-rose-700" x-text="cancelError"></p>
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="cancelOpen = false">Keep it</x-ui.button>
                <x-ui.button variant="danger" size="sm" icon="x" x-bind:disabled="cancelReason.trim().length < 5 || !!actingRef" @click="confirmCancel()"><span x-text="actingRef ? 'Cancelling…' : 'Cancel appointment'"></span></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>

    {{-- New or changed schedule. --}}
    <div x-data="{ get open() { return formOpen }, set open(v) { formOpen = v } }">
        <x-ui.modal title="Appointment Schedule" maxWidth="lg">
            <form id="appt-schedule-form" @submit.prevent="saveSchedule()" novalidate class="space-y-4">
                <p class="text-sm font-bold text-ink" x-text="editingId ? 'Change ' + form.label : 'New schedule'"></p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="appt-office" class="block text-xs font-semibold text-ink mb-1">Office</label>
                        <select id="appt-office" x-model="form.office" @change="officeChanged()" class="{{ $field }}">
                            <option value="">Choose the office…</option>
                            <template x-for="o in officeOptions" :key="o"><option :value="o" x-text="officeName(o)" :selected="o === form.office"></option></template>
                        </select>
                        <p class="{{ $err }}" x-show="fieldError('office')" x-text="fieldError('office')"></p>
                    </div>
                    <div x-show="form.office === barangayOffice">
                        <label for="appt-barangay" class="block text-xs font-semibold text-ink mb-1">Barangay</label>
                        <select id="appt-barangay" x-model="form.barangay" class="{{ $field }}">
                            <option value="">Choose the barangay…</option>
                            <template x-for="b in choices.barangays" :key="b"><option :value="b" x-text="'Brgy. ' + b" :selected="b === form.barangay"></option></template>
                        </select>
                        <p class="{{ $err }}" x-show="fieldError('barangay')" x-text="fieldError('barangay')"></p>
                    </div>
                    <div>
                        <label for="appt-service" class="block text-xs font-semibold text-ink mb-1">Service <span class="font-normal text-ink-muted">(optional)</span></label>
                        <select id="appt-service" x-model="form.service_key" class="{{ $field }}">
                            <option value="">Any of the office's services</option>
                            <template x-for="sv in servicesFor(form.office)" :key="sv.key"><option :value="sv.key" x-text="sv.name" :selected="sv.key === form.service_key"></option></template>
                        </select>
                        <p class="{{ $err }}" x-show="fieldError('service_key')" x-text="fieldError('service_key')"></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="appt-label" class="block text-xs font-semibold text-ink mb-1">Name</label>
                        <input id="appt-label" type="text" x-model="form.label" maxlength="160" placeholder="e.g. Civil registry window, mornings" class="{{ $field }}">
                        <p class="{{ $err }}" x-show="fieldError('label')" x-text="fieldError('label')"></p>
                    </div>
                </div>
                <fieldset>
                    <legend class="block text-xs font-semibold text-ink mb-1.5">Days</legend>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="w in weekdayChoices" :key="w[0]">
                            <label class="inline-flex items-center gap-1.5 rounded-xl px-2.5 py-1.5 text-xs cursor-pointer surface-pressed-sm" :class="form.weekdays.includes(w[0]) ? 'text-brand-700 font-bold' : 'text-ink-body'">
                                <input type="checkbox" :value="w[0]" x-model.number="form.weekdays" class="rounded border-slate-300 text-brand-600 focus:ring-brand-200"><span x-text="w[1]"></span>
                            </label>
                        </template>
                    </div>
                    <p class="{{ $err }}" x-show="fieldError('weekdays')" x-text="fieldError('weekdays')"></p>
                </fieldset>
                <div class="grid gap-3 grid-cols-2 sm:grid-cols-3">
                    <div>
                        <label for="appt-start" class="block text-xs font-semibold text-ink mb-1">Opens</label>
                        <input id="appt-start" type="time" x-model="form.start_time" class="{{ $field }}">
                        <p class="{{ $err }}" x-show="fieldError('start_time')" x-text="fieldError('start_time')"></p>
                    </div>
                    <div>
                        <label for="appt-end" class="block text-xs font-semibold text-ink mb-1">Closes</label>
                        <input id="appt-end" type="time" x-model="form.end_time" class="{{ $field }}">
                        <p class="{{ $err }}" x-show="fieldError('end_time')" x-text="fieldError('end_time')"></p>
                    </div>
                    <div>
                        <label for="appt-slot" class="block text-xs font-semibold text-ink mb-1">Slot length (minutes)</label>
                        <input id="appt-slot" type="number" min="5" max="240" step="5" inputmode="numeric" x-model.number="form.slot_minutes" class="{{ $field }}">
                        <p class="{{ $err }}" x-show="fieldError('slot_minutes')" x-text="fieldError('slot_minutes')"></p>
                    </div>
                    <div>
                        <label for="appt-capacity" class="block text-xs font-semibold text-ink mb-1">Residents per slot</label>
                        <input id="appt-capacity" type="number" min="1" max="200" inputmode="numeric" x-model.number="form.capacity" class="{{ $field }}">
                        <p class="{{ $err }}" x-show="fieldError('capacity')" x-text="fieldError('capacity')"></p>
                    </div>
                    <div>
                        <label for="appt-ahead" class="block text-xs font-semibold text-ink mb-1">Bookable days ahead</label>
                        <input id="appt-ahead" type="number" min="1" max="120" inputmode="numeric" x-model.number="form.book_days_ahead" class="{{ $field }}">
                        <p class="{{ $err }}" x-show="fieldError('book_days_ahead')" x-text="fieldError('book_days_ahead')"></p>
                    </div>
                </div>
                <p class="text-[12.5px] font-semibold text-ink-muted" x-show="slotPreview" x-text="slotPreview"></p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="appt-location" class="block text-xs font-semibold text-ink mb-1">Where <span class="font-normal text-ink-muted">(optional)</span></label>
                        <input id="appt-location" type="text" maxlength="200" x-model="form.location" placeholder="e.g. Ground floor, window 3" class="{{ $field }}">
                    </div>
                    <div>
                        <label for="appt-notes" class="block text-xs font-semibold text-ink mb-1">Notes for residents <span class="font-normal text-ink-muted">(optional)</span></label>
                        <textarea id="appt-notes" rows="2" maxlength="1000" x-model="form.notes" placeholder="e.g. Bring a valid ID." class="{{ $field }} resize-none"></textarea>
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-ink-body cursor-pointer">
                    <input type="checkbox" x-model="form.active" class="rounded border-slate-300 text-brand-600 focus:ring-brand-200"> Open for booking
                </label>
                <p x-show="formError" x-cloak role="alert" class="text-xs text-rose-700" x-text="formError"></p>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="formOpen = false">Cancel</x-ui.button>
                <x-ui.button type="submit" form="appt-schedule-form" size="sm" icon="check" x-bind:disabled="saving"><span x-text="saving ? 'Saving…' : 'Save schedule'"></span></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>

    {{-- Delete a schedule: one with bookings is switched off instead (AppointmentsController::destroySchedule). --}}
    <div x-data="{ get open() { return delOpen }, set open(v) { delOpen = v } }">
        <x-ui.modal title="Delete Schedule" maxWidth="sm">
            <div class="space-y-2 text-sm text-ink-body">
                <p>Delete <span class="font-bold text-ink" x-text="delTarget ? delTarget.label : ''"></span>?</p>
                <p class="text-[13px] text-ink-muted">A schedule that already has bookings is switched off instead, so their history stays. Either way residents can no longer book it.</p>
                <p x-show="delTarget && delTarget.upcoming" class="text-[13px] text-amber-700" x-text="delTarget && delTarget.upcoming ? delTarget.upcoming + (delTarget.upcoming === 1 ? ' upcoming booking stays booked' : ' upcoming bookings stay booked') + ': cancel them under Bookings, with a reason, if the office will not receive them.' : ''"></p>
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="delOpen = false">Keep it</x-ui.button>
                <x-ui.button variant="danger" size="sm" icon="trash-2" x-bind:disabled="deleting" @click="confirmDelete()"><span x-text="deleting ? 'Deleting…' : 'Delete'"></span></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</div>
