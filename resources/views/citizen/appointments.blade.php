{{-- Appointments (scope round 2026-10-15): book a time with an office -
     schedule, then a day and a free slot, then the purpose and optionally one
     of the resident's own requests - and see, add to a calendar or cancel the
     appointments already booked. ?request=REF links that request and picks its
     office's schedule. Logic in resources/js/scope-citizen.js
     (citizenAppointments). --}}
<x-layouts.citizen title="Appointments" subtitle="Book a time with an office and see your appointments." active="appointments">
    <div
        x-data="citizenAppointments(@js(['dokyu' => route('citizen.document-requests'), 'tulong' => route('citizen.assistance-requests')]))"
        class="animate-fade-up"
    >
        <x-ui.page-intro id="appointments" icon="calendar-clock" title="Mag-book ng oras sa opisina" titleEn="Book a time with an office">
            <x-ui.t fil="Pumili ng opisina, araw at oras, at sabihin kung para saan ang pagbisita. Kukumpirmahin ng opisina ang iyong booking. Hanggang tatlong paparating na appointment ang maaari." en="Choose an office, a day and a time, and say what the visit is for. The office confirms your booking. You can hold up to three upcoming appointments." />
        </x-ui.page-intro>

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_380px] gap-5 items-start">

            {{-- Booking --}}
            <section id="appointment-booking" class="order-2 lg:order-1 surface-glass rounded-[26px] p-5 scroll-mt-4 min-w-0" aria-labelledby="booking-heading">
                <h2 id="booking-heading" class="text-base font-extrabold text-ink flex items-center gap-2"><i data-lucide="calendar-check" class="w-5 h-5 text-brand-600"></i><x-ui.t fil="Mag-book ng Appointment" en="Book an Appointment" /></h2>

                <div x-show="!verified" x-cloak role="status" class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <i data-lucide="hourglass" class="w-4 h-4 text-amber-700 shrink-0 mt-0.5"></i>
                    <p class="text-xs text-amber-900">
                        <x-ui.t fil="Kailangang beripikado ang iyong account bago mag-book. Maaari mo pa ring tingnan ang mga bukas na oras." en="Your account must be verified before you can book. You can still look at the open times." />
                        <a href="{{ route('citizen.profile') }}" class="font-semibold underline underline-offset-2"><x-ui.t fil="Tingnan ang aking account" en="View my account" /></a>
                    </p>
                </div>

                <p x-show="hintText" x-cloak role="status" class="mt-4 flex items-start gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs text-sky-900"><i data-lucide="info" class="w-4 h-4 shrink-0"></i><span x-text="hintText"></span></p>

                {{-- Booked: what and when, a calendar file, and another booking. --}}
                <div x-show="booked" x-cloak role="status" class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4">
                    <p class="text-sm font-bold text-emerald-800 flex items-center gap-2"><i data-lucide="circle-check" class="w-4 h-4"></i><x-ui.t fil="Naka-book na ang iyong appointment!" en="Your appointment is booked!" /></p>
                    <p class="text-xs text-emerald-800 mt-1"><x-ui.t fil="Kukumpirmahin ito ng opisina, at aabisuhan ka." en="The office will confirm it, and you will be notified." /></p>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 mt-3 text-sm">
                        <div><dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-emerald-700"><x-ui.t fil="Reference" en="Reference" /></dt><dd class="font-mono font-semibold text-ink" x-text="booked?.ref"></dd></div>
                        <div><dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-emerald-700"><x-ui.t fil="Kailan" en="When" /></dt><dd class="font-semibold text-ink" x-text="booked ? when(booked) : ''"></dd></div>
                        <div><dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-emerald-700"><x-ui.t fil="Opisina" en="Office" /></dt><dd class="text-ink" x-text="booked ? [booked.place, booked.schedule].filter(Boolean).join(' · ') : ''"></dd></div>
                        <div x-show="booked?.location"><dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-emerald-700"><x-ui.t fil="Lugar" en="Where" /></dt><dd class="text-ink" x-text="booked?.location"></dd></div>
                    </dl>
                    <div class="flex flex-wrap gap-2 mt-4">
                        <x-ui.button size="sm" variant="secondary" icon="calendar-days" @click="saveToCalendar(booked)"><x-ui.t fil="I-save sa kalendaryo" en="Add to calendar" /></x-ui.button>
                        <x-ui.button size="sm" icon="plus" @click="bookAnother()"><x-ui.t fil="Mag-book ng isa pa" en="Book another" /></x-ui.button>
                    </div>
                </div>

                <div x-show="!booked">
                    {{-- Step 1: an office's schedule --}}
                    <div class="mt-5">
                        <h3 class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2.5 flex items-center gap-2"><span class="w-5 h-5 rounded-full clay-blue grid place-items-center text-[10px] font-extrabold">1</span><x-ui.t fil="Piliin ang opisina" en="Choose an office" /></h3>

                        <div x-show="schedulesLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" aria-busy="true">
                            <x-ui.skeleton class="h-20 rounded-2xl" />
                            <x-ui.skeleton class="h-20 rounded-2xl opacity-70" />
                        </div>
                        <div x-show="!schedulesLoading && schedulesFailure" x-cloak>
                            <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="schedulesError" retryExpr="loadSchedules()" class="!py-8" />
                        </div>
                        <div x-show="!schedulesLoading && !schedulesFailure && !schedules.length" x-cloak class="flex flex-col items-center text-center py-8 px-4">
                            <span class="w-14 h-14 rounded-[20px] surface-pressed flex items-center justify-center mb-3" aria-hidden="true"><i data-lucide="calendar-x" class="w-6 h-6 text-ink-muted"></i></span>
                            <p class="text-sm font-bold text-ink"><x-ui.t fil="Wala pang opisinang tumatanggap ng appointment online." en="No office is taking appointments online yet." /></p>
                            <p class="text-xs text-ink-muted mt-1"><x-ui.t fil="Bumisita o tumawag sa opisina, o subukan ulit sa ibang araw." en="Visit or call the office, or check again another day." /></p>
                        </div>

                        <div x-show="!schedulesLoading && !schedulesFailure && schedules.length" x-cloak>
                            <div x-show="schedules.length > 4" class="relative mb-2.5">
                                <i data-lucide="search" class="w-4 h-4 text-ink-faint absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="search" x-model="scheduleQuery"
                                    :aria-label="$store.lang.current === 'en' ? 'Search offices' : 'Maghanap ng opisina'"
                                    :placeholder="$store.lang.current === 'en' ? 'Search offices…' : 'Maghanap ng opisina…'"
                                    class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed py-2.5 pl-10 pr-4 text-sm text-ink placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" role="radiogroup" :aria-label="$store.lang.current === 'en' ? 'Office schedule' : 'Iskedyul ng opisina'">
                                <template x-for="s in filteredSchedules" :key="s.id">
                                    <button type="button" role="radio" :aria-checked="scheduleId === s.id" @click="selectSchedule(s)"
                                        class="text-left rounded-2xl border p-3.5 transition-all duration-[160ms] ease-standard focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                                        :class="scheduleId === s.id ? 'border-brand-400 bg-brand-50/80 ring-2 ring-brand-200' : 'border-brand-600/10 bg-white/60 hover:bg-white'">
                                        <span class="flex items-start justify-between gap-2">
                                            <span class="min-w-0">
                                                <span class="block text-sm font-bold text-ink" x-text="place(s)"></span>
                                                <span class="block text-xs text-ink-body mt-0.5" x-text="s.label"></span>
                                            </span>
                                            <span x-show="scheduleId === s.id" class="text-brand-600 shrink-0"><i data-lucide="circle-check" class="w-5 h-5"></i></span>
                                        </span>
                                        <span class="block text-[11px] text-ink-muted mt-1.5" x-text="weekdays(s) + ' · ' + hours(s)"></span>
                                        <span x-show="s.service" class="block text-[11px] text-ink-muted" x-text="t('For ', 'Para sa ') + s.service"></span>
                                        <span x-show="s.location" class="block text-[11px] text-ink-muted" x-text="s.location"></span>
                                    </button>
                                </template>
                            </div>
                            <p x-show="!filteredSchedules.length" class="text-xs text-ink-muted py-3"><x-ui.t fil="Walang opisinang tumutugma." en="No office matches." /></p>
                        </div>
                    </div>

                    {{-- Step 2: a day, then a free time --}}
                    <div x-show="schedule" x-cloak class="mt-7">
                        <h3 class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2.5 flex items-center gap-2"><span class="w-5 h-5 rounded-full clay-blue grid place-items-center text-[10px] font-extrabold">2</span><x-ui.t fil="Piliin ang araw at oras" en="Choose a day and time" /></h3>
                        <p x-show="schedule?.notes" class="mb-3 flex items-start gap-2 rounded-2xl bg-amber-50 border border-amber-100 px-3.5 py-2.5 text-xs text-amber-900"><i data-lucide="info" class="w-4 h-4 shrink-0"></i><span x-text="schedule?.notes"></span></p>

                        <div x-show="daysLoading" class="flex gap-2 overflow-hidden" aria-busy="true">
                            <x-ui.skeleton class="h-[74px] w-[76px] shrink-0 rounded-2xl" />
                            <x-ui.skeleton class="h-[74px] w-[76px] shrink-0 rounded-2xl opacity-80" />
                            <x-ui.skeleton class="h-[74px] w-[76px] shrink-0 rounded-2xl opacity-60" />
                        </div>
                        <div x-show="!daysLoading && daysFailure" x-cloak>
                            <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="daysError" retryExpr="loadDays(true)" class="!py-8" />
                        </div>
                        <p x-show="!daysLoading && !daysFailure && !days.some((d) => dayState(d) === 'open')" x-cloak class="rounded-2xl surface-pressed-sm px-4 py-3 text-xs text-ink-muted"><x-ui.t fil="Walang bakanteng oras sa susunod na mga araw. Pumili ng ibang opisina o bumalik ulit." en="No free times in the coming days. Choose another office or check back later." /></p>

                        <div x-show="!daysLoading && !daysFailure && days.length" x-cloak>
                            <div class="-mx-1 px-1 pb-2 overflow-x-auto scrollbar-thin">
                                <div class="flex gap-2 w-max" role="radiogroup" :aria-label="$store.lang.current === 'en' ? 'Day' : 'Araw'">
                                    <template x-for="d in days" :key="d.date">
                                        <button type="button" role="radio" :aria-checked="date === d.date" :disabled="dayState(d) !== 'open'" @click="pickDay(d)"
                                            :title="d.closed ? ($store.lang.current === 'en' ? 'Closed: ' : 'Sarado: ') + d.closed : ''"
                                            class="w-[76px] shrink-0 rounded-2xl border px-1.5 py-2.5 text-center transition-all duration-[160ms] ease-standard disabled:cursor-not-allowed focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                                            :class="date === d.date ? 'clay-blue border-transparent' : (dayState(d) === 'open' ? 'border-brand-600/15 bg-white/70 hover:bg-white text-ink' : 'border-slate-200 bg-slate-50 text-ink-faint')">
                                            <span class="block text-[10.5px] font-semibold uppercase tracking-wide" x-text="weekdayShort(d)"></span>
                                            <span class="block text-sm font-bold" x-text="dayShort(d)"></span>
                                            <span class="block text-[10px] mt-0.5" x-text="dayNote(d)"></span>
                                            <span class="sr-only" x-text="d.closed || ''"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div x-show="day" class="mt-3">
                                <p class="text-xs font-semibold text-ink mb-2" x-text="day ? fmtDate(day.date, { weekday: 'long' }) : ''"></p>
                                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2" role="radiogroup" :aria-label="$store.lang.current === 'en' ? 'Time' : 'Oras'">
                                    <template x-for="sl in (day ? day.slots : [])" :key="sl.start">
                                        <button type="button" role="radio" :aria-checked="start === sl.start" :disabled="sl.left < 1" @click="pickSlot(sl)"
                                            class="rounded-xl border px-3 py-2 text-left transition-all duration-[160ms] ease-standard disabled:cursor-not-allowed focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                                            :class="start === sl.start ? 'clay-blue border-transparent' : (sl.left > 0 ? 'border-brand-600/15 bg-white/70 hover:bg-white text-ink' : 'border-slate-200 bg-slate-50 text-ink-faint line-through')">
                                            <span class="block text-sm font-semibold" x-text="clock(sl.start)"></span>
                                            <span class="block text-[10.5px]" x-text="slotNote(sl)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Step 3: what for, and which request --}}
                    <div x-show="slot" x-cloak class="mt-7">
                        <h3 class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2.5 flex items-center gap-2"><span class="w-5 h-5 rounded-full clay-blue grid place-items-center text-[10px] font-extrabold">3</span><x-ui.t fil="Ilagay ang detalye" en="Add the details" /></h3>
                        <label for="appointment-purpose" class="block text-sm font-semibold text-ink-body mb-1.5"><x-ui.t fil="Layunin ng pagbisita" en="Purpose of your visit" /></label>
                        <textarea id="appointment-purpose" x-model="purpose" rows="3" maxlength="500"
                            :placeholder="$store.lang.current === 'en' ? 'E.g. Claim my Barangay Clearance' : 'Hal. Kukunin ang aking Barangay Clearance'"
                            class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                        <p class="text-[11px] mt-1 flex justify-between gap-2" :class="purposeLength && purposeLength < 3 ? 'text-rose-600' : 'text-ink-muted'">
                            <span x-text="purposeLength && purposeLength < 3 ? t('Write at least 3 characters.', 'Sumulat ng hindi bababa sa 3 titik.') : t('Required', 'Kailangan')"></span>
                            <span x-text="purposeLength + '/500'"></span>
                        </p>

                        <label for="appointment-request" class="block text-sm font-semibold text-ink-body mt-4 mb-1.5"><x-ui.t fil="Kaugnay na kahilingan (opsyonal)" en="Related request (optional)" /></label>
                        <select id="appointment-request" x-model="requestRef" class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <option value="" x-text="t('None', 'Wala')"></option>
                            <template x-for="r in requestOptions" :key="r.ref"><option :value="r.ref" :selected="r.ref === requestRef" x-text="requestLabel(r)"></option></template>
                        </select>
                    </div>

                    {{-- Review and confirm --}}
                    <div x-show="slot" x-cloak class="mt-7 rounded-2xl border border-brand-600/15 bg-white/70 p-4">
                        <h3 class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-3"><x-ui.t fil="Suriin at kumpirmahin" en="Review and confirm" /></h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2.5 text-sm">
                            <div><dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-ink-muted"><x-ui.t fil="Opisina" en="Office" /></dt><dd class="font-semibold text-ink" x-text="schedule ? [place(schedule), schedule.label].filter(Boolean).join(' · ') : ''"></dd></div>
                            <div><dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-ink-muted"><x-ui.t fil="Kailan" en="When" /></dt><dd class="font-semibold text-ink" x-text="whenChosen"></dd></div>
                            <div x-show="schedule?.location"><dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-ink-muted"><x-ui.t fil="Lugar" en="Where" /></dt><dd class="text-ink" x-text="schedule?.location"></dd></div>
                            <div><dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-ink-muted"><x-ui.t fil="Kahilingan" en="Request" /></dt><dd class="text-ink" x-text="linkedRequest ? requestLabel(linkedRequest) : t('None', 'Wala')"></dd></div>
                            <div class="sm:col-span-2"><dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-ink-muted"><x-ui.t fil="Layunin" en="Purpose" /></dt><dd class="text-ink break-words" x-text="purpose.trim() || '—'"></dd></div>
                        </dl>
                        <p x-show="bookError" x-cloak role="alert" class="mt-3 flex items-start gap-2 rounded-xl bg-rose-50 border border-rose-200 px-3 py-2 text-xs text-rose-700"><i data-lucide="circle-alert" class="w-4 h-4 shrink-0"></i><span x-text="bookError"></span></p>
                        <x-ui.button class="w-full sm:w-auto mt-4" x-bind:disabled="!ready || booking" x-bind:aria-busy="booking" @click="book()">
                            <span class="inline-flex items-center gap-2">
                                <span x-show="booking" x-cloak><i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i></span>
                                <span x-show="!booking"><i data-lucide="calendar-check" class="w-4 h-4"></i></span>
                                <span x-text="booking ? t('Booking…', 'Nagbu-book…') : t('Confirm booking', 'Kumpirmahin ang booking')"></span>
                            </span>
                        </x-ui.button>
                    </div>
                </div>
            </section>

            {{-- The resident's appointments --}}
            <section class="order-1 lg:order-2 surface-glass rounded-[26px] p-5 min-w-0" aria-labelledby="mine-heading">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="mine-heading" class="text-base font-extrabold text-ink flex items-center gap-2"><i data-lucide="calendar-clock" class="w-5 h-5 text-brand-600"></i><x-ui.t fil="Aking mga Appointment" en="My Appointments" /></h2>
                    <button type="button" @click="loadMine()" :aria-label="$store.lang.current === 'en' ? 'Refresh' : 'I-refresh'" class="w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-white/70 transition-colors"><i data-lucide="rotate-ccw" class="w-4 h-4"></i></button>
                </div>
                <div class="flex items-center gap-1 surface-pressed-sm rounded-2xl p-1 mt-3 w-fit" role="tablist">
                    <button type="button" role="tab" :aria-selected="listTab === 'upcoming'" @click="listTab = 'upcoming'" class="px-3 py-1.5 text-xs font-semibold rounded-xl transition-colors" :class="listTab === 'upcoming' ? 'bg-white/85 text-brand-600 shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'" x-text="t('Upcoming', 'Paparating') + ' · ' + upcoming.length"></button>
                    <button type="button" role="tab" :aria-selected="listTab === 'past'" @click="listTab = 'past'" class="px-3 py-1.5 text-xs font-semibold rounded-xl transition-colors" :class="listTab === 'past' ? 'bg-white/85 text-brand-600 shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'" x-text="t('Past', 'Nakaraan') + ' · ' + past.length"></button>
                </div>

                <div x-show="mineLoading" class="space-y-2.5 mt-3" aria-busy="true">
                    <x-ui.skeleton class="h-24 rounded-2xl" />
                    <x-ui.skeleton class="h-24 rounded-2xl opacity-70" />
                </div>
                <div x-show="!mineLoading && mineFailure" x-cloak>
                    <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="mineError" retryExpr="loadMine()" class="!py-8" />
                </div>
                <div x-show="!mineLoading && !mineFailure && !shown.length" x-cloak class="flex flex-col items-center text-center py-8 px-4">
                    <span class="w-14 h-14 rounded-[20px] surface-pressed flex items-center justify-center mb-3" aria-hidden="true"><i data-lucide="calendar-days" class="w-6 h-6 text-ink-muted"></i></span>
                    <p class="text-sm font-semibold text-ink" x-text="listTab === 'upcoming' ? t('You have no upcoming appointments.', 'Wala kang paparating na appointment.') : t('No past appointments yet.', 'Wala pang nakaraang appointment.')"></p>
                </div>

                <ul x-show="!mineLoading && !mineFailure && shown.length" x-cloak class="mt-3 space-y-2.5">
                    <template x-for="a in shown" :key="a.ref">
                        <li class="rounded-2xl border border-brand-600/10 bg-white/70 p-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-ink" x-text="when(a)"></p>
                                    <p class="text-xs text-ink-body mt-0.5" x-text="[a.place, a.schedule].filter(Boolean).join(' · ')"></p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset whitespace-nowrap shrink-0" :class="tone(a.status)[0]">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="tone(a.status)[1]"></span><span x-text="a.status"></span>
                                </span>
                            </div>
                            <p x-show="a.location" class="text-[11px] text-ink-muted mt-1 flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3 shrink-0"></i><span x-text="a.location"></span></p>
                            <p class="text-xs text-ink-body mt-1.5 break-words" x-text="a.purpose"></p>
                            <p x-show="a.request_ref" class="text-[11px] mt-1"><a :href="a.request_ref ? requestHref(a.request_ref) : '#'" class="font-mono font-semibold text-brand-600 hover:underline" x-text="a.request_ref"></a></p>
                            <p x-show="statusNote(a)" class="text-[11px] text-ink-muted mt-1" x-text="statusNote(a)"></p>
                            <p x-show="cancelNote(a)" class="text-[11px] text-slate-600 mt-1" x-text="cancelNote(a)"></p>
                            <p class="font-mono text-[10.5px] text-ink-faint mt-1.5" x-text="a.ref"></p>

                            <div x-show="isUpcoming(a) && cancelRef !== a.ref" class="flex flex-wrap gap-2 mt-2.5">
                                <button type="button" @click="saveToCalendar(a)" class="inline-flex items-center gap-1.5 rounded-xl border border-brand-600/15 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-brand-700 hover:bg-brand-50"><i data-lucide="calendar-days" class="w-3.5 h-3.5"></i><x-ui.t fil="I-save sa kalendaryo" en="Add to calendar" /></button>
                                <button type="button" x-show="a.can_cancel" @click="askCancel(a)" class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 hover:bg-rose-50"><i data-lucide="x" class="w-3.5 h-3.5"></i><x-ui.t fil="Kanselahin" en="Cancel" /></button>
                            </div>
                            <div x-show="cancelRef === a.ref" x-cloak class="mt-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3">
                                <p class="text-xs font-semibold text-rose-700"><x-ui.t fil="Kanselahin ang appointment na ito?" en="Cancel this appointment?" /></p>
                                <label class="block mt-2">
                                    <span class="block text-[11px] font-medium text-slate-600 mb-1"><x-ui.t fil="Dahilan (opsyonal)" en="Reason (optional)" /></span>
                                    <textarea x-model="cancelReason" rows="2" maxlength="300" class="w-full text-xs rounded-xl px-2.5 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-rose-100 outline-none"></textarea>
                                </label>
                                <p x-show="cancelError" role="alert" class="text-[11px] text-rose-700 mt-1" x-text="cancelError"></p>
                                <div class="flex flex-wrap justify-end gap-2 mt-2">
                                    <x-ui.button variant="secondary" size="sm" @click="cancelRef = null"><x-ui.t fil="Huwag na" en="Keep it" /></x-ui.button>
                                    <x-ui.button variant="danger" size="sm" icon="x" loading="cancelling" @click="confirmCancel(a)"><x-ui.t fil="Oo, kanselahin" en="Yes, cancel" /></x-ui.button>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>
            </section>
        </div>
    </div>
</x-layouts.citizen>
