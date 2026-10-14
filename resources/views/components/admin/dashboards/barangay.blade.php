{{-- One barangay's dashboard (owner, 2026-10-14): its residents and how well
     they are recorded, the certificates it issued and the fees collected, the
     assistance its residents received from every office, Sakuna, and its
     printed reports. GET /admin/dashboard/barangay; a barangay account sees
     its own barangay only. Each part shows only to an account that may read
     its module. Inside the Dashboard page's dashboardHost. --}}
<div x-data="brgyDashboard()" class="flex flex-col gap-[22px]">

    <div class="surface-glass rounded-[22px] px-4 py-4 sm:px-5 flex flex-col gap-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11.5px] font-bold uppercase tracking-[.08em] text-ink-muted">Barangay dashboard</p>
                <h3 class="text-xl font-extrabold text-ink leading-tight" x-text="'Barangay ' + (barangay || '')"></h3>
            </div>
            <div x-show="choosesBarangay">
                <label class="sr-only" for="brgy-pick">Barangay</label>
                <select id="brgy-pick" x-model="barangay" @change="load()" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    <template x-for="b in subjects.barangays" :key="b">
                        <option :value="b" x-text="'Brgy. ' + b" :selected="b === barangay"></option>
                    </template>
                </select>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-admin.dashboards.period-picker />
            <p class="text-[12.5px] font-semibold text-ink-muted" x-show="data" x-text="data ? 'Period: ' + data.period.label : ''"></p>
        </div>
    </div>

    <div x-show="loading && !data" class="grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
        @for ($i = 0; $i < 6; $i++)
            <div class="surface-glass rounded-[22px] min-h-[120px] p-4 space-y-3"><x-ui.skeleton class="h-3 w-24" /><x-ui.skeleton class="h-9 w-16" /></div>
        @endfor
    </div>
    <div x-show="failed" x-cloak>
        <x-ui.error-state messageExpr="error" retryExpr="load()" :retryable="true" />
    </div>

    <template x-if="data">
        <div class="flex flex-col gap-[22px] transition-opacity duration-200" :class="loading && 'opacity-60'">

            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
                <div x-show="people"><x-ui.stat-card layout="compact" class="h-full" label="Residents" valueBind="num(people && people.residents)" icon="users" color="brand" sublabelBind="people ? num(people.households) + ' households · ' + num(people.families) + ' families' : ''" :delay="0" /></div>
                <div x-show="people"><x-ui.stat-card layout="compact" class="h-full" label="Profiled" valueBind="people && people.profiling_pct !== null ? people.profiling_pct + '%' : '—'" icon="user-round-search" color="green" sublabelBind="people ? num(people.verified) + ' records verified' : ''" :delay="40" /></div>
                <div x-show="docs"><x-ui.stat-card layout="compact" class="h-full" label="Certificates Released" valueBind="num(docs && docs.released)" icon="file-text" color="light" sublabelBind="docs ? num(docs.open) + ' still open · ' + num(docs.needs_action) + (docs.needs_action === 1 ? ' needs' : ' need') + ' action' : ''" :delay="80" /></div>
                <div x-show="docs"><x-ui.stat-card layout="compact" class="h-full" label="Fees Collected" valueBind="pesoTile(docs ? docs.fees_collected + docs.fees_from_office_records : 0)" icon="wallet" color="gold" sublabelBind="docs ? plural(docs.receipts, 'receipt', 'receipts') + (docs.fees_from_office_records ? ' · ' + peso(docs.fees_from_office_records) + ' from the logbook' : '') : ''" :delay="120" /></div>
                <div x-show="aid"><x-ui.stat-card layout="compact" class="h-full" label="Assistance Received" valueBind="num(aid && aid.people)" icon="hand-heart" color="deep" sublabelBind="aid ? (aid.people === 1 ? 'person · ' : 'persons · ') + pesoTile(aid.amount_released) : ''" :delay="160" /></div>
                <div x-show="people"><x-ui.stat-card layout="compact" class="h-full" label="To Verify" valueBind="num(people ? people.accounts_to_verify + people.profiles_to_review : 0)" icon="user-round-check" color="orange" sublabelBind="people ? plural(people.accounts_to_verify, 'account', 'accounts') + ' · ' + plural(people.profiles_to_review, 'profile', 'profiles') : ''" :delay="200" /></div>
                <div x-show="sakuna"><x-ui.stat-card layout="compact" class="h-full" label="Active Incidents" valueBind="num(sakuna && sakuna.active_incidents)" icon="triangle-alert" color="red" sublabelBind="sakuna ? num(sakuna.families_sheltered) + ' families sheltered' : ''" :delay="240" /></div>
            </div>

            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))] items-start">
                <x-ui.card x-show="people">
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Residents</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted" x-text="people ? num(people.residents) + ' active' : ''"></span>
                    </div>
                    <x-ui.clay-donut rows="residentsDemo.sex" total-label="Residents" empty="No residents on record yet." :keep-colors="true" />
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted mb-2">Age group</p>
                            <template x-for="a in residentsDemo.ages" :key="a.label">
                                <div class="mb-2">
                                    <div class="flex justify-between text-[13px]"><span class="text-ink-body" x-text="a.label"></span><span class="font-bold tabular-nums" x-text="num(a.value)"></span></div>
                                    <div class="h-2 rounded-full bg-track mt-1"><div class="h-full rounded-full bg-brand-500" :style="'width:' + a.pct + '%'"></div></div>
                                </div>
                            </template>
                            <p class="text-[11.5px] text-ink-faint" x-show="residentsDemo.unknownAge" x-text="plural(residentsDemo.unknownAge, 'resident', 'residents') + ' with no birth date on record'"></p>
                        </div>
                        <div>
                            <p class="text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted mb-2">Sector</p>
                            <template x-for="s in residentsDemo.sectors" :key="s.label">
                                <div class="mb-2">
                                    <div class="flex justify-between text-[13px]"><span class="text-ink-body" x-text="s.label"></span><span class="font-bold tabular-nums" x-text="num(s.value)"></span></div>
                                    <div class="h-2 rounded-full bg-track mt-1"><div class="h-full rounded-full bg-gold-500" :style="'width:' + s.pct + '%'"></div></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card x-show="docs">
                    <h3 class="text-lg font-extrabold text-ink mb-4">Certificates released</h3>
                    <div class="space-y-3.5">
                        <template x-for="d in docRows" :key="d.label">
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-1.5">
                                    <span class="text-[14px] font-semibold text-ink-body truncate" x-text="d.label"></span>
                                    <span class="text-sm font-bold text-ink tabular-nums" x-text="d.value"></span>
                                </div>
                                <div class="h-3 rounded-full bg-track shadow-pressed-sm p-[2px]"><div class="h-full rounded-full bg-gradient-to-r from-brand-400 to-brand-600" :style="'width:' + d.pct + '%'"></div></div>
                            </div>
                        </template>
                        <p x-show="!docRows.length" class="py-6 text-center text-sm text-ink-muted">No certificates released in this period.</p>
                    </div>
                </x-ui.card>

                <x-ui.card x-show="aid">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <h3 class="text-lg font-extrabold text-ink">Assistance received</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted" x-text="aid ? plural(aid.served, 'case', 'cases') : ''"></span>
                    </div>
                    <p class="text-[12.5px] text-ink-muted mb-3">From every office, for residents of this barangay.</p>
                    <div class="divide-y divide-brand-600/[.08]">
                        <template x-for="p in (aid ? aid.by_program : [])" :key="p.program">
                            <div class="flex items-center justify-between gap-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-[14px] font-semibold text-ink-body truncate" x-text="p.program"></p>
                                    <p class="text-[11.5px] font-bold uppercase tracking-[.05em] text-ink-faint" x-text="p.office"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold text-ink tabular-nums" x-text="p.served"></p>
                                    <p class="text-[12px] text-ink-muted tabular-nums" x-text="peso(p.amount_released)"></p>
                                </div>
                            </div>
                        </template>
                        <p x-show="aid && !aid.by_program.length" class="py-6 text-center text-sm text-ink-muted">No assistance completed in this period.</p>
                    </div>
                </x-ui.card>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 items-start">
                <x-ui.card class="xl:col-span-3">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-3">
                        <h3 class="text-lg font-extrabold text-ink">Twelve months</h3>
                        <p class="flex items-center gap-3 text-[13px] font-semibold text-ink-muted">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-500"></span>Certificates</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gold-600"></span>Assistance</span>
                        </p>
                    </div>
                    <x-ui.clay-trend data="trend" label="Certificates released and assistance completed in each of the last twelve months" />
                </x-ui.card>

                <x-ui.card class="xl:col-span-2" x-show="people">
                    <h3 class="text-lg font-extrabold text-ink mb-3">Records to look after</h3>
                    <dl class="divide-y divide-brand-600/[.08] text-sm">
                        <div class="flex justify-between gap-3 py-2.5"><dt class="text-ink-body">Portal accounts waiting for verification</dt><dd class="font-bold tabular-nums" x-text="people ? people.accounts_to_verify : 0"></dd></div>
                        <div class="flex justify-between gap-3 py-2.5"><dt class="text-ink-body">Resident profiles waiting for review</dt><dd class="font-bold tabular-nums" x-text="people ? people.profiles_to_review : 0"></dd></div>
                        <div class="flex justify-between gap-3 py-2.5"><dt class="text-ink-body">Data quality issues open</dt><dd class="font-bold tabular-nums" x-text="people ? people.data_quality_open : 0"></dd></div>
                        <div class="flex justify-between gap-3 py-2.5"><dt class="text-ink-body">Accounts registered in the period</dt><dd class="font-bold tabular-nums" x-text="people ? people.accounts_registered_in_period : 0"></dd></div>
                        <div class="flex justify-between gap-3 py-2.5"><dt class="text-ink-body">Households profiled</dt><dd class="font-bold tabular-nums" x-text="people && people.households_profiled_pct !== null ? people.households_profiled_pct + '%' : '—'"></dd></div>
                    </dl>
                </x-ui.card>
            </div>

            <x-ui.card x-show="reports().length" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                    <h3 class="text-lg font-extrabold text-ink">Printable reports</h3>
                    <span class="text-[12.5px] font-semibold text-ink-muted" x-text="'Brgy. ' + barangay + ' · ' + data.period.label"></span>
                </div>
                <p class="text-[13px] text-ink-muted mb-4">Ready to print in the LGU's report format: prepared by the Barangay Secretary, approved by the Punong Barangay.</p>
                <div class="grid gap-3 grid-cols-1 sm:grid-cols-2">
                    <template x-for="r in reports()" :key="r.key">
                        <button type="button" @click="print(r.key)" class="group flex items-start text-left rounded-[18px] bg-white/60 border border-white/80 px-4 py-3.5 shadow-glass-sm hover:-translate-y-0.5 transition-transform duration-200">
                            <span class="flex items-start gap-3">
                                <span class="w-10 h-10 rounded-xl grid place-items-center shrink-0 bg-brand-50 text-brand-600"><i :data-lucide="info(r.key).icon" class="w-5 h-5"></i></span>
                                <span class="min-w-0">
                                    <span class="block text-[14px] font-bold text-ink" x-text="r.title"></span>
                                    <span class="block text-[12px] text-ink-muted mt-0.5" x-text="info(r.key).desc"></span>
                                    <span class="block text-[11.5px] font-semibold mt-1.5 text-brand-600">Preview and print</span>
                                </span>
                            </span>
                        </button>
                    </template>
                </div>
            </x-ui.card>
        </div>
    </template>
</div>
