{{-- Emergency (Sakuna, the citizen slice): call 911, the alerts the
     Municipality has published, the hotlines and evacuation centres (public
     GET /alerts, /hotlines, /sakuna/centers), and reporting an incident and
     following it (GET/POST /citizen/incidents, GET /citizen/incidents/{ref}),
     open to an unverified account on purpose. ?incident=REF opens a report;
     ?report=1 opens the form. Mirrors the app's Sakuna tab. Logic in
     resources/js/scope-citizen.js (citizenEmergency); the resident's own
     shelter and relief in resources/js/citizen-shelter.js (2026-10-27). --}}
<x-layouts.citizen title="Emergency" subtitle="Hotlines, evacuation centres, alerts, and incident reports." active="emergency">
    <div x-data="citizenEmergency" class="animate-fade-up">

        {{-- In a life-threatening emergency, the fastest thing on the page is the call. --}}
        <a href="tel:911"
            class="flex items-center gap-3 rounded-[22px] bg-rose-600 hover:bg-rose-700 text-white px-5 py-4 shadow-card transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-rose-200"
            :aria-label="$store.lang.current === 'en' ? 'Call 911. In a life-threatening emergency, call 911 or the MDRRMO directly.' : 'Tumawag sa 911. Sa emergency na banta sa buhay, tumawag sa 911 o direkta sa MDRRMO.'">
            <i data-lucide="triangle-alert" class="w-6 h-6 shrink-0"></i>
            <span class="flex-1 min-w-0">
                <span class="block text-[15px] font-extrabold leading-tight"><x-ui.t fil="Sa emergency na banta sa buhay, tumawag sa 911." en="In a life-threatening emergency, call 911." /></span>
                <span class="block text-xs text-white mt-0.5"><x-ui.t fil="O tumawag direkta sa MDRRMO o sa isa sa mga hotline sa ibaba." en="Or call the MDRRMO directly, or one of the hotlines below." /></span>
            </span>
            <span class="w-11 h-11 rounded-full bg-white/15 grid place-items-center shrink-0"><i data-lucide="phone-call" class="w-5 h-5"></i></span>
        </a>

        {{-- Active alerts --}}
        <section class="mt-5" aria-labelledby="alerts-heading">
            <h2 id="alerts-heading" class="text-base font-extrabold text-ink flex items-center gap-2 mb-2.5"><i data-lucide="megaphone" class="w-5 h-5 text-rose-600"></i><x-ui.t fil="Mga Aktibong Babala" en="Active Alerts" /></h2>
            <p x-show="alertsState === 'loading'" class="text-xs text-ink-muted"><x-ui.t fil="Tinitingnan ang mga babala…" en="Checking for emergency alerts…" /></p>
            <div x-show="alertsState === 'error'" x-cloak class="flex flex-wrap items-center gap-3 text-xs text-ink-muted">
                <span><x-ui.t fil="Hindi masuri ang mga babala." en="Emergency alerts could not be checked." /></span>
                <x-ui.button size="sm" variant="secondary" icon="rotate-ccw" @click="loadAlerts()"><x-ui.t fil="Subukang muli" en="Try again" /></x-ui.button>
            </div>
            <p x-show="alertsState === 'loaded' && !alerts.length" x-cloak class="text-xs text-emerald-700 flex items-center gap-1.5"><i data-lucide="circle-check" class="w-4 h-4"></i><x-ui.t fil="Walang aktibong babala sa ngayon." en="No emergency alerts in force right now." /></p>
            <div class="space-y-3" x-show="alerts.length" x-cloak>
                <template x-for="alert in alerts" :key="alert.ref">
                    <div role="alert" class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3.5 shadow-card">
                        <i data-lucide="siren" class="w-5 h-5 text-rose-700 shrink-0 mt-0.5"></i>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-700" x-show="alert.type || alert.level" x-text="alertKind(alert)"></p>
                            <p class="text-sm font-semibold text-rose-900" x-text="alertText(alert.title)"></p>
                            <p class="text-sm text-rose-900/90 mt-0.5 whitespace-pre-line" x-show="alertText(alert.body)" x-text="alertText(alert.body)"></p>
                            <p class="text-xs text-rose-800 mt-1" x-text="[alertWhere(alert), alert.published_at ? fmtDateTime(alert.published_at) : '', alertUntil(alert)].filter(Boolean).join(' · ')"></p>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_360px] gap-5 items-start mt-6">
            <div class="space-y-5 min-w-0">

                {{-- Report an incident, and my reports --}}
                <section class="surface-glass rounded-[26px] p-5" aria-labelledby="reports-heading">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 id="reports-heading" class="text-base font-extrabold text-ink flex items-center gap-2"><i data-lucide="flag" class="w-5 h-5 text-rose-600"></i><x-ui.t fil="Mag-ulat ng Insidente" en="Report an Incident" /></h2>
                            <p class="text-xs text-ink-muted mt-1"><x-ui.t fil="Baha, sunog, pagguho, aksidente, o iba pa sa iyong lugar. Diretso ito sa MDRRMO, at makikita mo rito ang bawat hakbang nila. Tumatanggap ng ulat kahit hindi pa beripikado ang account." en="A flood, fire, landslide, accident or other emergency near you. It goes straight to the MDRRMO, and you follow each step they take here. You can report even before your account is verified." /></p>
                        </div>
                        <x-ui.button variant="danger" icon="flag" @click="openReport()"><x-ui.t fil="Mag-ulat" en="Report" /></x-ui.button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-4">
                        <template x-for="type in types" :key="type.key">
                            <button type="button" @click="openReport(type.name)"
                                class="flex items-center gap-2.5 rounded-2xl border border-brand-600/10 bg-white/70 hover:bg-white px-3 py-2.5 text-left transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100">
                                <span class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 grid place-items-center shrink-0"><i :data-lucide="type.icon" class="w-4 h-4"></i></span>
                                <span class="text-xs font-semibold text-ink leading-tight" x-text="$store.lang.current === 'en' ? type.name : type.fil"></span>
                            </button>
                        </template>
                    </div>

                    <h3 class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mt-6 mb-2.5"><x-ui.t fil="Aking mga Ulat" en="My Reports" /></h3>
                    <div x-show="incidentsState === 'loading'" class="space-y-2"><x-ui.skeleton class="h-14 rounded-2xl" /><x-ui.skeleton class="h-14 rounded-2xl opacity-70" /></div>
                    <div x-show="incidentsState === 'error'" x-cloak>
                        <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="$store.lang.current === 'en' ? 'Your reports could not be loaded.' : 'Hindi ma-load ang iyong mga ulat.'" retryExpr="loadIncidents()" class="!py-8" />
                    </div>
                    <p x-show="incidentsState === 'loaded' && !incidents.length" x-cloak class="rounded-2xl surface-pressed-sm px-4 py-3 text-xs text-ink-muted"><x-ui.t fil="Wala ka pang naiulat na insidente." en="You have not reported an incident yet." /></p>
                    <ul class="space-y-2" x-show="incidents.length" x-cloak>
                        <template x-for="row in incidents" :key="row.ref">
                            <li>
                                <button type="button" @click="openIncident(row)" class="w-full flex items-center gap-3 rounded-2xl border border-brand-600/10 bg-white/70 hover:bg-white px-3.5 py-3 text-left transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100">
                                    <span class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 grid place-items-center shrink-0"><i :data-lucide="typeIcon(row.type)" class="w-4 h-4"></i></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-ink truncate" x-text="typeLabel(row.type) + (row.barangay ? ' · Brgy. ' + row.barangay : '')"></span>
                                        <span class="block text-[11px] text-ink-muted truncate" x-text="[row.ref, fmtDateTime(row.reported)].filter(Boolean).join(' · ')"></span>
                                    </span>
                                    <span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset" :class="statusTone(row.status)" x-text="statusLabel(row.status)"></span>
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-ink-faint shrink-0"></i>
                                </button>
                            </li>
                        </template>
                    </ul>
                </section>

                {{-- My shelter and relief (2026-10-27, Phase 3): the family's
                     stays and the relief released to it, found by the registry
                     link only; confirming an item came; a mistake becomes a
                     support ticket; and a household list kept ready, with
                     consent, while the LGU has it on. Logic in
                     resources/js/citizen-shelter.js. --}}
                <section id="my-records" class="surface-glass rounded-[26px] p-5 scroll-mt-4" aria-labelledby="my-records-heading" x-data="citizenShelter">
                    <h2 id="my-records-heading" class="text-base font-extrabold text-ink flex items-center gap-2"><i data-lucide="house" class="w-5 h-5 text-brand-600"></i><x-ui.t fil="Aking Paglikas at Relief" en="My Shelter and Relief" /></h2>
                    <p class="text-xs text-ink-muted mt-1"><x-ui.t fil="Ang mga pananatili ng inyong pamilya sa evacuation center at ang relief na inilabas para sa inyo. Ang staff pa rin ang nagtatala ng check-in at relief." en="Your family's stays at evacuation centres and the relief released to you. Staff still record check-ins and relief." /></p>

                    <div x-show="recordsState === 'loading'" class="space-y-2 mt-4"><x-ui.skeleton class="h-14 rounded-2xl" /><x-ui.skeleton class="h-14 rounded-2xl opacity-70" /></div>
                    <div x-show="recordsState === 'error'" x-cloak>
                        <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="$store.lang.current === 'en' ? 'Your shelter and relief records could not be loaded.' : 'Hindi ma-load ang iyong mga tala.'" retryExpr="loadRecords()" class="!py-8" />
                    </div>
                    <p x-show="recordsState === 'loaded' && enabled && !linked" x-cloak class="mt-4 rounded-2xl surface-pressed-sm px-4 py-3 text-xs text-ink-muted" data-records-unlinked><x-ui.t fil="Lalabas dito ang inyong mga tala kapag naka-link na ang inyong account sa talaan ng mga residente (pagkatapos ma-verify ng munisipyo)." en="Your records appear here once your account is linked to the resident registry (after the Municipality verifies it)." /></p>
                    <p x-show="recordsState === 'loaded' && enabled && linked && !stays.length && !relief.length" x-cloak class="mt-4 rounded-2xl surface-pressed-sm px-4 py-3 text-xs text-ink-muted"><x-ui.t fil="Wala pang pananatili o relief na nakatala para sa inyong pamilya." en="No stay or relief is on record for your family yet." /></p>

                    <div x-show="recordsState === 'loaded' && stays.length" x-cloak class="mt-4">
                        <h3 class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2.5"><x-ui.t fil="Mga Pananatili" en="Stays" /></h3>
                        <ul class="space-y-2">
                            <template x-for="s in stays" :key="s.ref">
                                <li class="rounded-2xl border border-brand-600/10 bg-white/70 px-3.5 py-3">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-ink" x-text="s.center || t('Evacuation centre', 'Evacuation center')"></p>
                                            <p class="text-[11px] text-ink-muted"><span class="font-mono" x-text="s.ref"></span> · <span x-text="t('Checked in ', 'Nag-check in ') + when(s.checked_in) + (s.checked_out ? t(' · left the centre ', ' · umalis sa center ') + when(s.checked_out) : '')"></span></p>
                                            <p class="text-[11px] text-ink-muted mt-0.5" x-text="s.members.length ? s.members.map((m) => m.name).join(', ') : (s.member_count + ' ' + t('people', 'katao'))"></p>
                                        </div>
                                        <span class="shrink-0 text-[10.5px] font-bold rounded-full px-2 py-0.5" :class="s.status === 'Processing' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="s.status === 'Processing' ? t('Staying now', 'Nananatili ngayon') : t('Left the centre', 'Umalis na sa center')"></span>
                                    </div>
                                    <button type="button" @click="openReport(s.ref)" class="mt-2 text-xs font-semibold text-brand-700 hover:text-brand-800 min-h-[44px]" :aria-expanded="reportFor === s.ref" x-text="t('Report a mistake', 'Mag-ulat ng mali')"></button>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div x-show="recordsState === 'loaded' && relief.length" x-cloak class="mt-4">
                        <h3 class="text-xs font-bold uppercase tracking-[.07em] text-ink-muted mb-2.5"><x-ui.t fil="Relief na Inilabas" en="Relief Released" /></h3>
                        <ul class="space-y-2">
                            <template x-for="r in relief" :key="r.ref">
                                <li class="rounded-2xl border border-brand-600/10 bg-white/70 px-3.5 py-3" data-relief-item>
                                    <p class="text-sm font-semibold text-ink" x-text="reliefLine(r)"></p>
                                    <p class="text-[11px] text-ink-muted"><span x-text="r.operation"></span> · <span x-text="when(r.released_at)"></span><span x-show="r.where" x-text="' · ' + r.where"></span></p>
                                    <div x-show="r.can_acknowledge" class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="text-xs text-ink-body" x-text="t('Did your family receive this?', 'Natanggap ba ito ng inyong pamilya?')"></span>
                                        <button type="button" :disabled="answering === r.ref" @click="acknowledge(r, true)" class="inline-flex items-center justify-center gap-1.5 min-h-[44px] rounded-2xl px-4 text-sm font-semibold transition-colors disabled:opacity-50 bg-emerald-600 text-white hover:bg-emerald-700" x-text="t('Received', 'Natanggap')"></button>
                                        <button type="button" :disabled="answering === r.ref" @click="acknowledge(r, false)" class="inline-flex items-center justify-center gap-1.5 min-h-[44px] rounded-2xl px-4 text-sm font-semibold transition-colors disabled:opacity-50 bg-white text-ink border border-slate-200 hover:border-slate-300" x-text="t('Not received', 'Hindi natanggap')"></button>
                                    </div>
                                    <p x-show="r.acknowledgement" class="mt-2 text-xs font-semibold" :class="r.acknowledgement === 'received' ? 'text-emerald-700' : 'text-amber-700'"
                                        x-text="(r.acknowledgement === 'received' ? t('You confirmed this was received', 'Kinumpirma ninyong natanggap ito') : t('You said this was not received', 'Sinabi ninyong hindi ito natanggap')) + (r.acknowledged_at ? ' · ' + when(r.acknowledged_at) : '')"></p>
                                    <button type="button" @click="openReport(r.ref)" class="mt-1 text-xs font-semibold text-brand-700 hover:text-brand-800 min-h-[44px]" :aria-expanded="reportFor === r.ref" x-text="t('Report a mistake', 'Mag-ulat ng mali')"></button>
                                </li>
                            </template>
                        </ul>
                        <p class="text-[11px] text-ink-muted mt-2"><x-ui.t fil="Ang inyong sagot dito ay katabi ng pirma sa papel na listahan ng relief; hindi nito pinapalitan ang pirma." en="Your answer here sits beside the signature on the paper relief sheet; it does not replace the signature." /></p>
                    </div>

                    <div x-show="reportFor" x-cloak class="mt-4 rounded-2xl border border-brand-600/10 p-3 space-y-2" data-report-mistake>
                        <label class="block">
                            <span class="block text-xs font-semibold text-ink mb-1.5" x-text="t('What is wrong in ', 'Ano ang mali sa ') + reportFor + '?'"></span>
                            <textarea x-model="reportMessage" rows="3" maxlength="2000" class="w-full rounded-2xl border-0 surface-pressed px-3.5 py-2.5 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"
                                :placeholder="t('e.g. My son is not listed; we left on Oct 30, not Oct 31.', 'hal. Wala sa listahan ang anak ko; umalis kami noong Okt 30, hindi Okt 31.')"></textarea>
                        </label>
                        <p class="text-[11px] text-ink-muted"><x-ui.t fil="Ipapadala ito bilang support ticket; doon sasagot ang opisina." en="This is sent as a support ticket; the office replies there." /></p>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="reportFor = ''" class="inline-flex items-center justify-center gap-1.5 min-h-[44px] rounded-2xl px-4 text-sm font-semibold transition-colors disabled:opacity-50 bg-white text-ink border border-slate-200" x-text="t('Cancel', 'Kanselahin')"></button>
                            <button type="button" :disabled="reportSending || reportMessage.trim().length < 5" @click="sendReport()" class="inline-flex items-center justify-center gap-1.5 min-h-[44px] rounded-2xl px-4 text-sm font-semibold transition-colors disabled:opacity-50 bg-brand-600 text-white hover:bg-brand-700" x-text="reportSending ? t('Sending…', 'Ipinapadala…') : t('Send', 'Ipadala')"></button>
                        </div>
                    </div>

                    {{-- The household list, kept ready for check-in. --}}
                    <div x-show="hhState === 'loaded' && hhEnabled" x-cloak class="mt-6 pt-5 border-t border-brand-600/10" data-household-ready>
                        <h3 class="text-sm font-extrabold text-ink flex items-center gap-2"><i data-lucide="users-round" class="w-4 h-4 text-brand-600"></i><x-ui.t fil="Listahan ng Sambahayan para sa Paglikas" en="Household List for Evacuation" /></h3>
                        <p class="text-xs text-ink-muted mt-1"><x-ui.t fil="Ihanda ang listahan ng mga kasama ninyo sa bahay. Kung lumikas kayo, mapupunan ng staff ang check-in mula rito; sila pa rin ang magtatala ng pagdating ninyo." en="Keep a list of the people in your home ready. If you evacuate, staff can fill the check-in from it; they still record your arrival." /></p>
                        <div x-show="household && !hhEditing" class="mt-3 rounded-2xl border border-brand-600/10 bg-white/70 px-3.5 py-3">
                            <p class="text-sm font-semibold text-ink" x-text="(household?.members || []).length + ' ' + t('people', 'katao') + ' · Brgy. ' + (household?.barangay || '') + (household?.purok ? ', ' + household.purok : '')"></p>
                            <p class="text-[11px] text-ink-muted" x-text="(household?.members || []).map((m) => m.name).join(', ')"></p>
                            <p class="text-[11px] text-ink-muted mt-0.5" x-text="t('Updated ', 'Na-update ') + when(household?.updated_at)"></p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button type="button" @click="editHousehold()" class="inline-flex items-center justify-center gap-1.5 min-h-[44px] rounded-2xl px-4 text-sm font-semibold transition-colors disabled:opacity-50 bg-white text-ink border border-slate-200" x-text="t('Edit', 'I-edit')"></button>
                                <button type="button" @click="withdrawHousehold()" class="inline-flex items-center justify-center gap-1.5 min-h-[44px] rounded-2xl px-4 text-sm font-semibold transition-colors disabled:opacity-50 bg-white text-rose-700 border border-rose-200" x-text="t('Delete', 'Burahin')"></button>
                            </div>
                        </div>
                        <button type="button" x-show="!household && !hhEditing" @click="editHousehold()" class="mt-3 inline-flex items-center justify-center gap-1.5 min-h-[44px] rounded-2xl px-4 text-sm font-semibold transition-colors disabled:opacity-50 bg-brand-600 text-white hover:bg-brand-700" x-text="t('Prepare my household list', 'Ihanda ang listahan')"></button>

                        <div x-show="hhEditing" x-cloak class="mt-3 space-y-3" data-household-form>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label class="block"><span class="block text-xs font-semibold text-ink mb-1.5">Barangay <span class="text-rose-600">*</span></span>
                                    <select x-model="hhForm.barangay" class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed px-3.5 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="" x-text="t('Choose the barangay', 'Piliin ang barangay')"></option><template x-for="b in barangays" :key="b"><option :value="b" x-text="b" :selected="b === hhForm.barangay"></option></template></select></label>
                                <label class="block"><span class="block text-xs font-semibold text-ink mb-1.5">Sitio</span><input type="text" x-model="hhForm.sitio" maxlength="120" class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed px-3.5 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></label>
                                <label class="block"><span class="block text-xs font-semibold text-ink mb-1.5">Purok</span><input type="text" x-model="hhForm.purok" maxlength="80" class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed px-3.5 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></label>
                            </div>
                            <template x-for="(m, i) in hhForm.members" :key="i">
                                <fieldset class="rounded-2xl border border-brand-600/10 p-3 grid grid-cols-2 sm:grid-cols-5 gap-2">
                                    <legend class="sr-only" x-text="t('Person ', 'Tao ') + (i + 1)"></legend>
                                    <input type="text" x-model="m.name" maxlength="160" :aria-label="t('Person ' + (i + 1) + ' full name', 'Buong pangalan ng tao ' + (i + 1))" :placeholder="t('Full name', 'Buong Pangalan')" class="col-span-2 min-h-[44px] rounded-2xl border-0 surface-pressed px-3 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                    <input type="text" x-model="m.relation" maxlength="60" :aria-label="t('Person ' + (i + 1) + ' relation', 'Kaugnayan ng tao ' + (i + 1))" :placeholder="i === 0 ? 'Head' : t('Relation', 'Kaugnayan')" class="min-h-[44px] rounded-2xl border-0 surface-pressed px-3 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                    <select x-model="m.sex" :aria-label="t('Person ' + (i + 1) + ' sex', 'Kasarian ng tao ' + (i + 1))" class="min-h-[44px] rounded-2xl border-0 surface-pressed px-3 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="" x-text="t('Sex', 'Kasarian')"></option><option value="Male" x-text="t('Male', 'Lalaki')"></option><option value="Female" x-text="t('Female', 'Babae')"></option></select>
                                    <input type="date" x-model="m.dob" :aria-label="t('Person ' + (i + 1) + ' birth date', 'Kaarawan ng tao ' + (i + 1))" class="min-h-[44px] rounded-2xl border-0 surface-pressed px-3 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                    <select x-model="m.vulnerability" :aria-label="t('Person ' + (i + 1) + ' needs special care', 'Kailangan ng tao ' + (i + 1) + ' ng espesyal na alaga')" class="col-span-2 sm:col-span-4 min-h-[44px] rounded-2xl border-0 surface-pressed px-3 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="" x-text="t('No special care needed', 'Walang espesyal na alaga')"></option><template x-for="v in vulnerabilities" :key="v"><option :value="v" x-text="v"></option></template></select>
                                    <button type="button" @click="removeHouseholdMember(i)" class="min-h-[44px] text-xs font-semibold text-rose-700" x-text="t('Remove', 'Alisin')"></button>
                                </fieldset>
                            </template>
                            <button type="button" @click="addHouseholdMember()" class="text-xs font-semibold text-brand-700 min-h-[44px]" x-text="t('+ Add a person', '+ Magdagdag ng tao')"></button>
                            <label class="flex items-start gap-2 rounded-2xl surface-pressed-sm px-3.5 py-3 text-xs text-ink-body">
                                <input type="checkbox" x-model="hhForm.consent" class="mt-0.5 rounded border-slate-300 text-brand-600">
                                <span><x-ui.t fil="Pumapayag akong itago ng munisipyo ang listahang ito, kasama ang mga detalye ng mga bata, para lamang sa check-in sa evacuation center. Maaari ko itong burahin anumang oras." en="I agree that the Municipality keeps this list, including children's details, only for check-in at an evacuation centre. I can delete it at any time." /></span>
                            </label>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="hhEditing = false" class="inline-flex items-center justify-center gap-1.5 min-h-[44px] rounded-2xl px-4 text-sm font-semibold transition-colors disabled:opacity-50 bg-white text-ink border border-slate-200" x-text="t('Cancel', 'Kanselahin')"></button>
                                <button type="button" :disabled="hhSaving || !hhReady" @click="saveHousehold()" class="inline-flex items-center justify-center gap-1.5 min-h-[44px] rounded-2xl px-4 text-sm font-semibold transition-colors disabled:opacity-50 bg-brand-600 text-white hover:bg-brand-700" x-text="hhSaving ? t('Saving…', 'Itinatala…') : t('Save list', 'Itala ang listahan')"></button>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Evacuation centres --}}
                {{-- On a map or as a list, by barangay, and the nearest open one
                     (2026-10-26): distances are straight lines from the device's
                     GPS or a point the resident taps, never from a barangay name. --}}
                <section class="surface-glass rounded-[26px] p-5" aria-labelledby="centres-heading" x-data="sakunaCentresMap" x-effect="refresh()">
                    <h2 id="centres-heading" class="text-base font-extrabold text-ink flex items-center gap-2"><i data-lucide="house" class="w-5 h-5 text-brand-600"></i><x-ui.t fil="Mga Evacuation Center" en="Evacuation Centres" /></h2>
                    <p class="text-xs text-ink-muted mt-1"><x-ui.t fil="Ang mga bukas na center, kung ilan ang naroon na, at ang mga serbisyo nila." en="The centres that are set up, how full they are, and what they offer." /></p>

                    <div x-show="centresState === 'loaded' && centres.length" x-cloak class="mt-4 space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex rounded-2xl surface-pressed-sm p-1" role="group" :aria-label="t('Show the centres as', 'Ipakita ang mga center bilang')">
                                <button type="button" @click="view = 'list'" :aria-pressed="view === 'list'" class="inline-flex items-center gap-1.5 min-h-[36px] px-3 rounded-xl text-xs font-semibold transition-colors" :class="view === 'list' ? 'bg-white text-brand-700 shadow-sm' : 'text-ink-muted hover:text-ink'"><i data-lucide="list" class="w-4 h-4"></i><x-ui.t fil="Listahan" en="List" /></button>
                                <button type="button" @click="showMap()" :aria-pressed="view === 'map'" class="inline-flex items-center gap-1.5 min-h-[36px] px-3 rounded-xl text-xs font-semibold transition-colors" :class="view === 'map' ? 'bg-white text-brand-700 shadow-sm' : 'text-ink-muted hover:text-ink'"><i data-lucide="map" class="w-4 h-4"></i><x-ui.t fil="Mapa" en="Map" /></button>
                            </div>
                            <label class="sr-only" for="centre-barangay-filter"><x-ui.t fil="Barangay" en="Barangay" /></label>
                            <select id="centre-barangay-filter" x-model="barangayFilter" class="min-h-[40px] rounded-2xl border-0 surface-pressed-sm px-3 text-xs text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <option value="" x-text="t('All barangays', 'Lahat ng barangay')"></option>
                                <template x-for="b in centreBarangays" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                            </select>
                        </div>

                        {{-- Nearest open centre --}}
                        <div x-show="placed.length" class="rounded-2xl border border-brand-600/10 bg-white/70 p-3.5">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="findNearest()" :disabled="hereState === 'locating'" :aria-busy="hereState === 'locating'"
                                    class="inline-flex items-center gap-1.5 min-h-[40px] px-3.5 rounded-2xl text-xs font-semibold clay-blue btn-shine hover:brightness-95 disabled:opacity-60">
                                    <i data-lucide="locate-fixed" class="w-4 h-4"></i><span x-text="hereState === 'locating' ? t('Finding you…', 'Hinahanap ka…') : t('Find the nearest open centre', 'Hanapin ang pinakamalapit na bukas na center')"></span>
                                </button>
                                <button type="button" @click="pickOnMap()" class="inline-flex items-center gap-1.5 min-h-[40px] px-3.5 rounded-2xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:border-slate-300">
                                    <i data-lucide="map-pinned" class="w-4 h-4"></i><x-ui.t fil="Piliin ang lugar ko sa mapa" en="Choose my place on the map" />
                                </button>
                                <button type="button" x-show="here" @click="clearHere()" class="text-xs font-semibold text-ink-muted hover:text-ink px-2"><x-ui.t fil="Alisin" en="Clear" /></button>
                            </div>
                            <p x-show="hereState === 'idle'" class="text-[11px] text-ink-muted mt-1.5"><x-ui.t fil="Magtatanong muna ang iyong device bago ibahagi ang lokasyon mo; gagamitin lang ito para sukatin ang layo." en="Your device asks before sharing your location; it is used only to measure distances." /></p>
                            <p x-show="hereMessage" x-cloak role="status" class="text-[11px] mt-1.5" :class="hereTrouble ? 'text-amber-800 font-semibold' : 'text-ink-muted'" x-text="hereMessage"></p>
                            <div x-show="here && nearest" x-cloak class="mt-3 rounded-xl bg-emerald-50 border border-emerald-200 px-3.5 py-2.5">
                                <p class="text-[10.5px] font-bold uppercase tracking-wide text-emerald-800"><x-ui.t fil="Pinakamalapit na bukas na center" en="Nearest open centre" /></p>
                                <button type="button" @click="openCentre(nearest.c)" class="text-sm font-bold text-ink hover:underline text-left" x-text="nearest?.c.name"></button>
                                <p class="text-[11px] text-emerald-900" x-text="nearest ? distanceLabel(nearest.c) + ' · Brgy. ' + nearest.c.barangay : ''"></p>
                            </div>
                            <p x-show="here && !nearest" x-cloak class="mt-3 text-[11px] text-ink-muted"><x-ui.t fil="Walang bukas na center na nasa mapa ngayon." en="No open centre is on the map right now." /></p>
                            <p x-show="here" x-cloak class="mt-2 text-[11px] text-ink-muted"><x-ui.t fil="Tuwid na linya ang layo, hindi ayon sa daan. Hindi laging pinakaligtas o pinakamabilis puntahan ang pinakamalapit — sundin ang MDRRMO at ang mga opisyal ng inyong barangay." en="Distances are straight lines, not by road. The nearest is not always the safest or quickest to reach — follow the MDRRMO and your barangay officials." /></p>
                        </div>
                        <p x-show="!placed.length" class="rounded-2xl surface-pressed-sm px-4 py-3 text-xs text-ink-muted"><x-ui.t fil="Wala pang center na nasa mapa, kaya hindi pa masusukat ang layo. Gamitin ang listahan at ang barangay." en="No centre is on the map yet, so distances cannot be measured. Use the list and the barangay filter." /></p>
                    </div>
                    <div x-show="centresState === 'loading'" class="space-y-2 mt-4"><x-ui.skeleton class="h-16 rounded-2xl" /><x-ui.skeleton class="h-16 rounded-2xl opacity-70" /></div>
                    <div x-show="centresState === 'error'" x-cloak>
                        <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="$store.lang.current === 'en' ? 'The evacuation centres could not be loaded.' : 'Hindi ma-load ang mga evacuation center.'" retryExpr="loadDirectory()" class="!py-8" />
                    </div>
                    <p x-show="centresState === 'loaded' && !centres.length" x-cloak class="mt-4 rounded-2xl surface-pressed-sm px-4 py-3 text-xs text-ink-muted"><x-ui.t fil="Wala pang nakalistang evacuation center." en="No evacuation centres listed yet." /></p>
                    <div x-show="view === 'map' && centres.length" x-cloak class="mt-3">
                        <div x-show="placed.length" class="relative">
                            <div x-ref="map" class="sakuna-map h-72 sm:h-96 w-full rounded-2xl overflow-hidden border border-brand-600/10"
                                 role="application" :aria-label="t('Map of evacuation centres', 'Mapa ng mga evacuation center')"></div>
                            <div x-show="mapState === 'loading'" class="pointer-events-none absolute inset-x-0 top-2 flex justify-center z-[500]"><span class="text-[11px] font-semibold text-slate-600 bg-white/90 rounded-full px-2.5 py-1 shadow"><x-ui.t fil="Nilo-load ang mapa…" en="Loading the map…" /></span></div>
                        </div>
                        <div x-show="mapState === 'failed'" x-cloak role="alert" class="mt-2 rounded-2xl bg-amber-50 border border-amber-200 px-3.5 py-2.5 text-[11px] text-amber-900">
                            <p class="font-semibold"><x-ui.t fil="Hindi ma-load ang mapa (walang koneksyon, o abala ang serbisyo ng mapa)." en="The map could not load (no connection, or the map service is busy)." /></p>
                            <p><x-ui.t fil="Nasa listahan pa rin ang lahat ng center." en="Every centre is still in the list." /> <button type="button" @click="view = 'list'" class="font-semibold underline underline-offset-2"><x-ui.t fil="Ipakita ang listahan" en="Show the list" /></button> · <button type="button" @click="retryMap()" class="font-semibold underline underline-offset-2"><x-ui.t fil="Subukang muli" en="Try again" /></button></p>
                        </div>
                        <p x-show="placed.length" class="text-[11px] text-ink-muted mt-1.5" x-text="(hereState === 'picking') ? t('Tap the map where you are.', 'I-tap ang mapa kung nasaan ka.') : t('Select a centre for its details. Green: open now. Grey: not open.', 'Pumili ng center para sa mga detalye. Berde: bukas ngayon. Abo: hindi bukas.')"></p>
                        <p x-show="placed.length && placed.length < centres.length" class="text-[11px] text-ink-muted mt-0.5" x-text="t((centres.length - placed.length) + ' listed centre(s) are not on the map yet; they are in the list.', 'May ' + (centres.length - placed.length) + ' center na wala pa sa mapa; nasa listahan sila.')"></p>
                        <p x-show="!placed.length" class="rounded-2xl surface-pressed-sm px-4 py-3 text-xs text-ink-muted"><x-ui.t fil="Wala pang center na nasa mapa. Nasa listahan ang lahat." en="No centre is on the map yet. They are all in the list." /> <button type="button" @click="view = 'list'" class="font-semibold text-brand-700 underline underline-offset-2"><x-ui.t fil="Ipakita ang listahan" en="Show the list" /></button></p>
                    </div>
                    <p x-show="view === 'list' && centres.length && !shown.length" x-cloak class="mt-3 rounded-2xl surface-pressed-sm px-4 py-3 text-xs text-ink-muted"><x-ui.t fil="Walang center sa barangay na ito." en="No centre in this barangay." /></p>
                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-4" x-show="view === 'list' && shown.length" x-cloak>
                        <template x-for="c in shown" :key="c.ref">
                            <li>
                                <button type="button" @click="openCentre(c)" class="w-full h-full text-left rounded-2xl border border-brand-600/10 bg-white/70 hover:bg-white p-3.5 transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100">
                                    <span class="flex items-start justify-between gap-2">
                                        <span class="text-sm font-bold text-ink" x-text="c.name"></span>
                                        <span class="shrink-0 text-[10.5px] font-bold rounded-full px-2 py-0.5" :class="c.open ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="c.open ? t('Open now', 'Bukas ngayon') : t('Not open', 'Hindi bukas')"></span>
                                    </span>
                                    <span class="block text-[11px] text-ink-muted mt-0.5" x-text="[c.address, 'Brgy. ' + c.barangay].filter(Boolean).join(' · ')"></span>
                                    <span x-show="c.mine" class="inline-flex items-center gap-1 mt-1 text-[10.5px] font-semibold text-brand-700"><i data-lucide="map-pinned" class="w-3 h-3"></i><x-ui.t fil="Nasa iyong barangay" en="In your barangay" /></span>
                                    <span x-show="distanceLabel(c)" class="block text-[11px] font-semibold text-ink mt-1" x-text="distanceLabel(c)"></span>
                                    <span x-show="nearest && nearest.c.ref === c.ref" class="inline-flex items-center gap-1 mt-1 text-[10.5px] font-bold text-emerald-800 bg-emerald-50 rounded-full px-2 py-0.5"><i data-lucide="navigation" class="w-3 h-3"></i><x-ui.t fil="Pinakamalapit na bukas" en="Nearest open" /></span>
                                    <span class="block text-[11px] font-medium text-ink-body mt-1.5" x-text="occupancyText(c)"></span>
                                    <span x-show="occupancyWarning(c)" x-cloak class="block text-[11px] font-semibold mt-0.5" :class="c.pct >= 100 ? 'text-rose-700' : 'text-amber-800'" x-text="occupancyWarning(c)"></span>
                                    <span x-show="c.individuals !== null && c.capacity > 0" class="block h-1.5 rounded-full bg-slate-100 mt-1 overflow-hidden" aria-hidden="true"><span class="block h-full rounded-full" :class="occupancyTone(c)" :style="'width:' + Math.min(100, c.pct) + '%'"></span></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </section>
            </div>

            {{-- Hotlines --}}
            <aside class="surface-glass rounded-[26px] p-5 lg:sticky lg:top-4" aria-labelledby="hotlines-heading">
                <h2 id="hotlines-heading" class="text-base font-extrabold text-ink flex items-center gap-2"><i data-lucide="phone" class="w-5 h-5 text-rose-600"></i><x-ui.t fil="Mga Emergency Hotline" en="Emergency Hotlines" /></h2>
                <p class="text-xs text-ink-muted mt-1"><x-ui.t fil="I-tap para tumawag." en="Tap a number to call." /></p>
                <div x-show="hotlinesState === 'loading'" class="space-y-2 mt-4"><x-ui.skeleton class="h-14 rounded-2xl" /><x-ui.skeleton class="h-14 rounded-2xl opacity-70" /></div>
                <div x-show="hotlinesState === 'error'" x-cloak>
                    <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="$store.lang.current === 'en' ? 'The hotlines could not be loaded. In an emergency, call 911.' : 'Hindi ma-load ang mga hotline. Sa emergency, tumawag sa 911.'" retryExpr="loadDirectory()" class="!py-8" />
                </div>
                <p x-show="hotlinesState === 'loaded' && !hotlines.length" x-cloak class="mt-4 rounded-2xl surface-pressed-sm px-4 py-3 text-xs text-ink-muted"><x-ui.t fil="Wala pang nakalistang hotline." en="No hotlines listed yet." /></p>
                <ul class="space-y-2 mt-4" x-show="hotlines.length" x-cloak>
                    <template x-for="(h, i) in hotlines" :key="i">
                        <li>
                            <a :href="dialHref(h.contact) || null" :aria-label="(dialHref(h.contact) ? ($store.lang.current === 'en' ? 'Call ' : 'Tawagan ang ') : '') + h.office + ', ' + h.contact"
                                class="flex items-center gap-3 rounded-2xl border border-brand-600/10 bg-white/70 px-3.5 py-3 transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-rose-100" :class="dialHref(h.contact) && 'hover:bg-white'">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-ink" x-text="h.office"></span>
                                    <span class="block text-[11px] text-ink-muted" x-show="h.position" x-text="h.position"></span>
                                    <span class="block text-sm font-semibold text-rose-700 break-words" x-text="h.contact"></span>
                                </span>
                                <span x-show="dialHref(h.contact)" class="w-11 h-11 rounded-full bg-rose-50 text-rose-600 grid place-items-center shrink-0"><i data-lucide="phone" class="w-5 h-5"></i></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </aside>
        </div>

        {{-- One evacuation centre --}}
        <div x-data="{ get open() { return centreOpen }, set open(v) { centreOpen = v } }">
            <x-ui.modal title="Evacuation Center" titleEn="Evacuation Centre" maxWidth="sm">
                <template x-if="centre">
                    <div>
                        <p class="text-base font-bold text-ink" x-text="centre.name"></p>
                        <p class="text-xs text-ink-muted mt-0.5" x-text="[centre.address, 'Brgy. ' + centre.barangay].filter(Boolean).join(' · ')"></p>
                        <p class="text-sm font-semibold mt-2" :class="centre.open ? 'text-emerald-700' : 'text-slate-600'" x-text="centre.open ? t('Open now, taking evacuees', 'Bukas ngayon, tumatanggap ng lumilikas') : t('Not open right now', 'Hindi bukas sa ngayon')"></p>

                        <div class="rounded-xl border border-slate-200 p-3.5 mt-4">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1.5"><x-ui.t fil="Kapasidad at Kalagayan" en="Capacity and Status" /></p>
                            <p class="text-sm text-ink" x-show="centre.capacity > 0" x-text="t('Total capacity: ' + centre.capacity + ' people', 'Kabuuang kapasidad: ' + centre.capacity + ' katao')"></p>
                            <p class="text-sm text-ink" x-show="centre.individuals !== null" x-text="t('Now here: ' + centre.individuals + ' people' + (centre.families !== null ? ', ' + centre.families + ' families' : ''), 'Narito ngayon: ' + centre.individuals + ' katao' + (centre.families !== null ? ', ' + centre.families + ' pamilya' : ''))"></p>
                            <div x-show="centre.individuals !== null && centre.capacity > 0" class="h-2 rounded-full bg-slate-100 mt-2 overflow-hidden" role="img" :aria-label="centre.pct + '%'"><div class="h-full rounded-full" :class="occupancyTone(centre)" :style="'width:' + Math.min(100, centre.pct) + '%'"></div></div>
                            <p class="text-sm text-ink-muted" x-show="!(centre.capacity > 0) && centre.individuals === null"><x-ui.t fil="Walang impormasyon sa kapasidad." en="Capacity information unavailable." /></p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-3.5 mt-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1.5"><x-ui.t fil="Mga Serbisyo" en="Available Services" /></p>
                            <ul class="space-y-1" x-show="centre.services.length">
                                <template x-for="(s, i) in centre.services" :key="i">
                                    <li class="text-sm text-ink flex items-start gap-2"><i data-lucide="circle-check" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i><span x-text="s"></span></li>
                                </template>
                            </ul>
                            <p class="text-sm text-ink-muted" x-show="!centre.services.length"><x-ui.t fil="Wala pang impormasyon." en="No information available yet." /></p>
                        </div>

                        <div class="mt-3" x-show="centre.lat !== null && centre.lng !== null">
                            <div x-data="sakunaPinView({ point: () => ({ lat: centre?.lat, lng: centre?.lng }), tone: 'centre' })" x-effect="show()">
                                <div x-ref="map" class="sakuna-map h-48 w-full rounded-xl overflow-hidden border border-slate-200" role="img" :aria-label="t('Map showing where the centre is', 'Mapa kung nasaan ang center')"></div>
                                <p x-show="viewState === 'failed'" x-cloak class="text-[11px] text-amber-800 font-semibold mt-1"><x-ui.t fil="Hindi ma-load ang mapa. Gumagana pa rin ang direksyon sa ibaba." en="The map could not load. The directions below still work." /></p>
                            </div>
                        </div>
                        <p class="text-[11px] text-ink-muted mt-3" x-show="centre.lat === null || centre.lng === null"><x-ui.t fil="Wala pa sa mapa ang center na ito; hahanapin ng direksyon ang pangalan at address nito." en="This centre is not on the map yet; directions search for its name and address." /></p>
                        <a :href="directionsUrl(centre)" target="_blank" rel="noopener"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 min-h-[44px] px-4 py-2.5 rounded-2xl text-sm font-semibold clay-blue btn-shine hover:brightness-95">
                            <i data-lucide="navigation" class="w-4 h-4"></i><x-ui.t fil="Direksyon sa Google Maps" en="Directions in Google Maps" />
                        </a>
                    </div>
                </template>
                <x-slot:footer>
                    <x-ui.button size="sm" @click="centreOpen = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        {{-- One of my reports: its status, what happened, and its photos --}}
        <div x-data="{ get open() { return detailOpen }, set open(v) { detailOpen = v } }">
            <x-ui.modal title="Ang Aking Ulat" titleEn="My Report" maxWidth="md">
                <template x-if="detail">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-mono text-ink-muted" x-text="detail.ref"></p>
                                <p class="text-sm font-semibold text-ink" x-text="typeLabel(detail.type)"></p>
                            </div>
                            <span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset" :class="statusTone(detail.status)" x-text="statusLabel(detail.status)"></span>
                        </div>
                        <p x-show="detailLoading" class="text-xs text-ink-muted mt-4 flex items-center gap-1.5"><span><i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin"></i></span><x-ui.t fil="Naglo-load…" en="Loading…" /></p>
                        <p x-show="detailError" x-cloak role="alert" class="text-xs text-rose-700 mt-4" x-text="detailError"></p>

                        <dl class="grid grid-cols-2 gap-3 mt-4 text-sm" x-show="!detailLoading">
                            <div><dt class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted"><x-ui.t fil="Uri" en="Type" /></dt><dd class="text-ink" x-text="typeLabel(detail.type)"></dd></div>
                            <div><dt class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted"><x-ui.t fil="Kalubhaan" en="Severity" /></dt><dd><span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset" :class="severityClass(detail.severity)" x-text="severityLabel(detail.severity)"></span></dd></div>
                            <div><dt class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted"><x-ui.t fil="Lugar" en="Where" /></dt><dd class="text-ink" x-text="[detail.sitio, detail.barangay ? 'Brgy. ' + detail.barangay : ''].filter(Boolean).join(', ')"></dd></div>
                            <div><dt class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted"><x-ui.t fil="Iniulat" en="Reported" /></dt><dd class="text-ink" x-text="fmtDateTime(detail.reported)"></dd></div>
                            <div class="col-span-2" x-show="detail.description"><dt class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted"><x-ui.t fil="Paglalarawan" en="What is happening" /></dt><dd class="text-ink whitespace-pre-line" x-text="detail.description"></dd></div>
                        </dl>
                        <div class="mt-4" x-show="!detailLoading && detail.lat != null && detail.lng != null" x-cloak>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-2"><x-ui.t fil="Lokasyon sa mapa" en="Map location" /></p>
                            <div x-data="sakunaPinView({ point: () => ({ lat: detail?.lat, lng: detail?.lng }) })" x-effect="show()">
                                <div x-ref="map" class="sakuna-map h-44 w-full rounded-xl overflow-hidden border border-slate-200" role="img" :aria-label="t('Map showing where you pinned the report', 'Mapa kung saan mo na-pin ang ulat')"></div>
                            </div>
                            <p class="text-[11px] font-mono text-ink-muted mt-1" x-text="detail.lat != null ? Number(detail.lat).toFixed(5) + ', ' + Number(detail.lng).toFixed(5) : ''"></p>
                        </div>

                        <div class="mt-4" x-show="detail.photos.length" x-cloak>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-2"><x-ui.t fil="Mga Larawan" en="Photos" /></p>
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="(p, i) in detail.photos" :key="i">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 overflow-hidden aspect-square grid place-items-center text-center">
                                        <img x-show="p.src" :src="p.src" :alt="(($store.lang.current === 'en') ? 'Photo ' : 'Larawan ') + (i + 1)" class="w-full h-full object-cover">
                                        <div x-show="!p.src" class="px-2">
                                            <i data-lucide="image" class="w-5 h-5 text-ink-faint mx-auto"></i>
                                            <p class="text-[10.5px] text-ink-muted mt-1" x-text="photoNote(p)"></p>
                                            <a x-show="p.url && p.failed" :href="p.url" target="_blank" rel="noopener" class="text-[10.5px] font-semibold text-brand-600 hover:underline"><x-ui.t fil="Buksan" en="Open" /></a>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-5" x-show="steps.length" x-cloak>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-2"><x-ui.t fil="Mga Nangyari" en="What has happened" /></p>
                            <ol class="relative border-l border-slate-200 ml-1.5 space-y-3">
                                <template x-for="s in steps" :key="s.key">
                                    <li class="pl-4 relative">
                                        <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full bg-brand-500 ring-2 ring-white"></span>
                                        <p class="text-sm font-semibold text-ink" x-text="s.label"></p>
                                        <p class="text-[11px] text-ink-muted" x-text="[s.status, fmtDateTime(s.at)].filter(Boolean).join(' · ')"></p>
                                    </li>
                                </template>
                            </ol>
                        </div>
                    </div>
                </template>
                <x-slot:footer>
                    <x-ui.button size="sm" @click="detailOpen = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        {{-- Report an incident --}}
        <div x-data="{ get open() { return reportOpen }, set open(v) { reportOpen = v } }">
            <x-ui.modal title="Mag-ulat ng Insidente" titleEn="Report an Incident" maxWidth="md">
                <div>
                    <p class="flex items-start gap-2 rounded-2xl bg-rose-50 border border-rose-100 px-3.5 py-2.5 text-xs font-semibold text-rose-800">
                        <i data-lucide="phone-call" class="w-4 h-4 shrink-0"></i>
                        <span><x-ui.t fil="Sa emergency na banta sa buhay, tumawag muna sa hotline." en="For life-threatening emergencies, call a hotline first." /> <a href="tel:911" class="underline underline-offset-2">911</a></span>
                    </p>

                    {{-- Sent: the reference, and the form is ready for another. --}}
                    <div x-show="reported" x-cloak role="status" class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4">
                        <p class="text-sm font-bold text-emerald-800 flex items-center gap-2"><i data-lucide="circle-check" class="w-4 h-4"></i><x-ui.t fil="Naipadala ang iyong ulat." en="Your report was sent." /></p>
                        <p class="text-xs text-emerald-800 mt-1"><x-ui.t fil="Reference number:" en="Reference number:" /> <span class="font-mono font-semibold" x-text="reported?.ref"></span></p>
                        <p class="text-xs text-emerald-800 mt-1"><x-ui.t fil="Makikita mo rito ang status nito sa Aking mga Ulat." en="You can follow it here under My Reports." /></p>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <x-ui.button size="sm" variant="secondary" icon="eye" @click="reportOpen = false; openIncident(reported)"><x-ui.t fil="Tingnan ang ulat" en="View the report" /></x-ui.button>
                            <x-ui.button size="sm" icon="plus" @click="reported = null; openReport()"><x-ui.t fil="Mag-ulat ng iba pa" en="Report another" /></x-ui.button>
                        </div>
                    </div>

                    <form x-show="!reported" @submit.prevent="submitReport()" class="mt-4 space-y-4" novalidate>
                        <fieldset>
                            <legend class="text-xs font-semibold text-ink mb-2"><x-ui.t fil="Ano ang nangyayari?" en="What is happening?" /> <span class="text-rose-600">*</span></legend>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2" role="radiogroup">
                                <template x-for="type in types" :key="type.key">
                                    <button type="button" role="radio" :aria-checked="form.type === type.name" @click="form.type = type.name"
                                        class="flex items-center gap-2 rounded-2xl border px-3 py-2.5 text-left text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                                        :class="form.type === type.name ? 'border-rose-300 bg-rose-50 text-rose-800 ring-2 ring-rose-200' : 'border-brand-600/10 bg-white/70 hover:bg-white text-ink'">
                                        <i :data-lucide="type.icon" class="w-4 h-4 shrink-0"></i><span x-text="$store.lang.current === 'en' ? type.name : type.fil"></span>
                                    </button>
                                </template>
                            </div>
                            {{-- The rest of the names the MDRRMO files by (2026-10-17). --}}
                            <label class="block mt-2">
                                <span class="block text-[11px] font-semibold text-ink-muted mb-1"><x-ui.t fil="Iba pang uri" en="More kinds" /></span>
                                <select @change="if ($event.target.value) form.type = $event.target.value"
                                    class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed px-3.5 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"
                                    :class="isMoreType(form.type) ? 'ring-2 ring-rose-200' : ''">
                                    <option value="" :selected="!isMoreType(form.type)" x-text="$store.lang.current === 'en' ? 'Choose if it is not above' : 'Piliin kung wala sa itaas'"></option>
                                    <template x-for="type in moreTypes" :key="type.key"><option :value="type.name" :selected="form.type === type.name" x-text="$store.lang.current === 'en' ? type.name : type.fil"></option></template>
                                </select>
                            </label>
                        </fieldset>

                        <fieldset>
                            <legend class="text-xs font-semibold text-ink mb-2"><x-ui.t fil="Gaano kalala?" en="How serious is it?" /> <span class="text-rose-600">*</span></legend>
                            <div class="flex flex-wrap gap-2" role="radiogroup">
                                <template x-for="[value, fil] in severities" :key="value">
                                    <button type="button" role="radio" :aria-checked="form.severity === value" @click="form.severity = value"
                                        class="min-h-[40px] px-3.5 rounded-2xl border text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                                        :class="form.severity === value ? 'border-brand-400 bg-brand-50 text-brand-800 ring-2 ring-brand-200' : 'border-brand-600/10 bg-white/70 hover:bg-white text-ink'"
                                        x-text="$store.lang.current === 'en' ? value : fil"></button>
                                </template>
                            </div>
                        </fieldset>

                        <label class="block">
                            <span class="block text-xs font-semibold text-ink mb-1.5"><x-ui.t fil="Barangay" en="Barangay" /> <span class="text-rose-600">*</span></span>
                            <select x-model="form.barangay" class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed px-3.5 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <option value="" x-text="$store.lang.current === 'en' ? 'Choose the barangay' : 'Piliin ang barangay'"></option>
                                <template x-for="b in barangays" :key="b"><option :value="b" x-text="b" :selected="b === form.barangay"></option></template>
                            </select>
                        </label>

                        <label class="block">
                            <span class="block text-xs font-semibold text-ink mb-1.5"><x-ui.t fil="Sitio / purok / palatandaan (opsyonal)" en="Sitio / purok / landmark (optional)" /></span>
                            <input type="text" x-model="form.sitio" maxlength="120" class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed px-3.5 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </label>

                        {{-- Where the emergency is on the map (2026-10-26): optional, so nobody
                             is kept from reporting by a phone that cannot find itself. --}}
                        <x-sakuna.location-picker target="form" bilingual subject="incident" />

                        <label class="block">
                            <span class="block text-xs font-semibold text-ink mb-1.5"><x-ui.t fil="Ano ang nangyayari? (opsyonal)" en="What is happening? (optional)" /></span>
                            <textarea x-model="form.description" rows="3" maxlength="2000"
                                :placeholder="$store.lang.current === 'en' ? 'e.g. Water is knee-deep near the chapel; two families need help moving.' : 'hal. Hanggang tuhod ang tubig malapit sa kapilya; dalawang pamilya ang kailangang tulungang lumikas.'"
                                class="w-full rounded-2xl border-0 surface-pressed px-3.5 py-2.5 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                        </label>

                        <div>
                            <p class="text-xs font-semibold text-ink"><x-ui.t fil="Mga Larawan (opsyonal)" en="Photos (optional)" /></p>
                            <p class="text-[11px] text-ink-muted mt-0.5"><x-ui.t fil="Hanggang 3. Nakatutulong ang larawan para makita ng mga tutugon ang nangyayari. Huwag huminto para kumuha kung nasa panganib ka." en="Up to 3. A photo helps responders see what is happening. Do not stop to take one if you are in danger." /></p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <template x-for="(p, i) in photos" :key="p.preview">
                                    <div class="relative w-20 h-20 rounded-xl overflow-hidden border border-slate-200">
                                        <img :src="p.preview" :alt="($store.lang.current === 'en' ? 'Photo ' : 'Larawan ') + (i + 1)" class="w-full h-full object-cover">
                                        <button type="button" @click="removePhoto(i)" :aria-label="($store.lang.current === 'en' ? 'Remove photo ' : 'Alisin ang larawan ') + (i + 1)" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-navy-950/70 text-white grid place-items-center"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                    </div>
                                </template>
                                <label x-show="photos.length < maxPhotos" class="w-20 h-20 rounded-xl border-2 border-dashed border-brand-600/20 grid place-items-center text-center cursor-pointer hover:bg-white/70 text-ink-muted">
                                    <span><i data-lucide="camera" class="w-5 h-5 mx-auto"></i><span class="block text-[10px] font-semibold mt-0.5"><x-ui.t fil="Magdagdag" en="Add photo" /></span></span>
                                    <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" multiple @change="addPhotos($event.target.files); $event.target.value = ''">
                                </label>
                            </div>
                            <p x-show="photoError" x-cloak role="alert" class="text-[11px] text-rose-700 mt-1" x-text="photoError"></p>
                        </div>

                        <p x-show="reportError" x-cloak role="alert" class="rounded-2xl bg-rose-50 border border-rose-100 px-3.5 py-2.5 text-xs text-rose-800" x-text="reportError"></p>

                        <button type="submit" :disabled="submitting" :aria-busy="submitting"
                            class="w-full inline-flex items-center justify-center gap-2 min-h-[44px] px-4 py-2.5 rounded-2xl text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-60 transition-colors">
                            <span x-show="submitting" x-cloak><i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i></span>
                            <span x-show="!submitting"><i data-lucide="send" class="w-4 h-4"></i></span>
                            <span x-text="submitting ? ($store.lang.current === 'en' ? 'Sending…' : 'Ipinapadala…') : (reportFailure && typeof reportFailure !== 'string' ? ($store.lang.current === 'en' ? 'Try again' : 'Subukang muli') : ($store.lang.current === 'en' ? 'Submit Report' : 'Ipadala ang Ulat'))"></span>
                        </button>
                    </form>
                </div>
                <x-slot:footer>
                    <x-ui.button size="sm" variant="secondary" @click="closeReport()"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>
    </div>
</x-layouts.citizen>
