{{-- One office's dashboard (owner, 2026-10-14; the MSWDO first): what waits
     on the office, what it served in the period, by program, by barangay and
     by who was helped, what is overdue, and its printed reports. Counted by
     GET /admin/dashboard/department; a department account sees its own office
     only. Inside the Dashboard page's dashboardHost (subjects, catalog). --}}
<div x-data="deptDashboard()" class="flex flex-col gap-[22px]">

    <div class="surface-glass rounded-[22px] px-4 py-4 sm:px-5 flex flex-col gap-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11.5px] font-bold uppercase tracking-[.08em] text-ink-muted">Office dashboard</p>
                <h3 class="text-xl font-extrabold text-ink leading-tight text-balance" x-text="data ? data.office : (office || 'No office')"></h3>
                <p class="mt-0.5 text-[13px] text-ink-muted" x-show="data && data.head" x-text="data ? (data.head + (data.head_position ? ', ' + data.head_position : '')) : ''"></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label class="sr-only" for="dept-office">Office</label>
                <select id="dept-office" x-show="choosesOffice" x-model="office" @change="officeChanged()" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none max-w-[380px]">
                    <template x-for="o in subjects.offices" :key="o.name">
                        <option :value="o.name" x-text="(o.short ? o.short + ' · ' : '') + o.name" :selected="o.name === office"></option>
                    </template>
                </select>
                <label class="sr-only" for="dept-barangay">Barangay</label>
                <select id="dept-barangay" x-model="barangay" @change="load()" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    <option value="">All barangays</option>
                    <template x-for="b in barangays" :key="b">
                        <option :value="b" x-text="'Brgy. ' + b" :selected="b === barangay"></option>
                    </template>
                </select>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-admin.dashboards.period-picker />
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-[12.5px] font-semibold text-ink-muted" x-show="data" x-text="data ? 'Period: ' + data.period.label : ''"></p>
                {{-- The Summary Report: this dashboard on paper, with its graphs,
                     as narrowed now; preview, then PDF, Excel or print. --}}
                <x-ui.button icon="file-down" x-show="summary && data" x-cloak @click="print('dept_summary')">Download report</x-ui.button>
            </div>
        </div>
        {{-- Narrow the dashboard and its reports (2026-10-28): one program,
             and who was helped. --}}
        <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-brand-600/[.08]">
            <span class="flex items-center gap-1.5 text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted mr-1"><i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>Filter</span>
            <label class="sr-only" for="dept-service">Program or document</label>
            <select id="dept-service" x-model="service" @change="changed()" x-show="services.length > 1" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none max-w-[260px]">
                <option value="" x-text="hasTulong && hasDokyu ? 'All programs and documents' : (hasTulong ? 'All programs' : 'All documents')"></option>
                <template x-for="o in services" :key="o.key">
                    <option :value="o.key" x-text="o.name" :selected="o.key === service"></option>
                </template>
            </select>
            <label class="sr-only" for="dept-sex">Sex</label>
            <select id="dept-sex" x-model="sex" @change="changed()" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                <option value="">Any sex</option>
                <template x-for="o in sexOptions" :key="o.value">
                    <option :value="o.value" x-text="o.label" :selected="o.value === sex"></option>
                </template>
            </select>
            <label class="sr-only" for="dept-age">Age group</label>
            <select id="dept-age" x-model="age" @change="changed()" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                <option value="">Any age</option>
                <template x-for="o in ageOptions" :key="o.value">
                    <option :value="o.value" x-text="o.label" :selected="o.value === age"></option>
                </template>
            </select>
            <label class="sr-only" for="dept-sector">Sector</label>
            <select id="dept-sector" x-model="sector" @change="changed()" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                <option value="">Any sector</option>
                <template x-for="o in sectorOptions" :key="o.value">
                    <option :value="o.value" x-text="o.label" :selected="o.value === sector"></option>
                </template>
            </select>
            <button type="button" x-show="anyFilter()" x-cloak @click="clearFilters()" class="inline-flex items-center gap-1 rounded-xl px-2.5 py-1.5 text-[12.5px] font-semibold text-brand-600 hover:bg-brand-50"><i data-lucide="x" class="w-3.5 h-3.5"></i>Clear filters</button>
            <p x-show="personFilter()" x-cloak class="basis-full text-[12px] text-ink-muted">Counting only cases linked to a resident record, with each person's age as it was on the day counted.</p>
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
    <div x-show="!office && !loading" x-cloak class="surface-pressed rounded-2xl px-4 py-6 text-center text-[14.5px] font-bold text-ink">
        No office with Dokyu or Tulong services is linked to your account.
    </div>

    <template x-if="data">
        <div class="flex flex-col gap-[22px] transition-opacity duration-200" :class="loading && 'opacity-60'">

            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
                <x-ui.stat-card layout="compact" class="h-full" label="Needs Action" valueBind="num(k.needs_action)" icon="inbox" color="orange" sublabel="Waiting on the office now" :delay="0" />
                <x-ui.stat-card layout="compact" class="h-full" label="Overdue" valueBind="num(k.overdue)" icon="timer" color="red" sublabelBind="num(k.open) + ' open in all'" :delay="40" />
                <x-ui.stat-card layout="compact" class="h-full" label="Received" valueBind="num(k.received)" icon="file-text" color="light" sublabel="Filed in the period" :delay="80" />
                <div x-show="hasTulong"><x-ui.stat-card layout="compact" class="h-full" label="Clients Served" valueBind="num(k.served)" icon="package-check" color="deep" sublabelBind="num(k.people_served) + ' different persons'" :delay="120" /></div>
                <div x-show="!hasTulong"><x-ui.stat-card layout="compact" class="h-full" label="Released" valueBind="num(k.served)" icon="package-check" color="deep" sublabel="Documents released in the period" :delay="120" /></div>
                <div x-show="hasTulong"><x-ui.stat-card layout="compact" class="h-full" label="Amount Released" valueBind="pesoTile(k.amount_released)" icon="wallet" color="gold" sublabelBind="peso(k.amount_released) + ' to clients'" :delay="160" /></div>
                <x-ui.stat-card layout="compact" class="h-full" label="Processing Time" valueBind="k.avg_working_days === null || k.avg_working_days === undefined ? '—' : (k.avg_working_days < 0.5 ? 'Same day' : k.avg_working_days + (k.avg_working_days == 1 ? ' day' : ' days'))" icon="calendar-clock" color="slate" sublabel="Working days, filing to release" :delay="200" />
            </div>

            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))] items-start">
                <x-ui.card>
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Where the open cases are</h3>
                        <span x-show="pipeline.length" class="flex items-center gap-1.5 text-[12px] font-semibold text-ink-muted"><span class="w-2 h-2 rounded-full bg-orange-500"></span>Waiting on the office</span>
                    </div>
                    <div class="space-y-3">
                        <template x-for="row in pipeline" :key="row.type + row.status">
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-1.5">
                                    <span class="flex items-center gap-2 text-[14px] font-semibold text-ink-body min-w-0">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="row.needs_action ? 'bg-orange-500' : 'bg-slate-300'"></span>
                                        <span class="truncate" x-text="row.status"></span>
                                        <span x-show="hasTulong && hasDokyu" class="text-[11px] font-bold uppercase text-ink-faint" x-text="row.type"></span>
                                    </span>
                                    <span class="text-sm font-bold text-ink tabular-nums" x-text="row.count"></span>
                                </div>
                                <div class="h-3 rounded-full bg-track shadow-pressed-sm p-[2px]">
                                    <div class="h-full rounded-full transition-all duration-[600ms] ease-standard" :class="row.needs_action ? 'bg-gradient-to-r from-orange-300 to-orange-500' : 'bg-gradient-to-r from-brand-300 to-brand-500'" :style="'width:' + row.pct + '%'"></div>
                                </div>
                            </div>
                        </template>
                        <p x-show="!pipeline.length" class="py-6 text-center text-sm text-ink-muted">No open cases.</p>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Who was served</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted" x-text="num(demo.people) + (demo.people === 1 ? ' person' : ' persons')"></span>
                    </div>
                    <x-ui.clay-donut rows="demo.sex" total-label="Persons" empty="No one served in this period." :keep-colors="true" />
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted mb-2">Age group</p>
                            <template x-for="a in demo.ages" :key="a.label">
                                <div class="mb-2">
                                    <div class="flex justify-between text-[13px]"><span class="text-ink-body" x-text="a.label"></span><span class="font-bold tabular-nums" x-text="a.value"></span></div>
                                    <div class="h-2 rounded-full bg-track mt-1"><div class="h-full rounded-full bg-brand-500" :style="'width:' + a.pct + '%'"></div></div>
                                </div>
                            </template>
                            <p class="text-[11.5px] text-ink-faint" x-show="demo.unknownAge" x-text="plural(demo.unknownAge, 'person', 'persons') + ' with no birth date on record'"></p>
                        </div>
                        <div>
                            <p class="text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted mb-2">Sector</p>
                            <template x-for="s in demo.sectors" :key="s.label">
                                <div class="mb-2">
                                    <div class="flex justify-between text-[13px]"><span class="text-ink-body" x-text="s.label"></span><span class="font-bold tabular-nums" x-text="s.value"></span></div>
                                    <div class="h-2 rounded-full bg-track mt-1"><div class="h-full rounded-full bg-gold-500" :style="'width:' + s.pct + '%'"></div></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-lg font-extrabold text-ink mb-4">Served by barangay</h3>
                    <div class="space-y-3.5">
                        <template x-for="b in byBarangay" :key="b.label">
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-1.5">
                                    <span class="text-[14px] font-semibold text-ink-body min-w-0 truncate" x-text="'Brgy. ' + b.label"></span>
                                    <span class="text-sm font-bold text-ink shrink-0 tabular-nums" x-text="b.value + ' served'"></span>
                                </div>
                                <div class="h-3 rounded-full bg-track shadow-pressed-sm p-[2px]">
                                    <div class="h-full rounded-full bg-gradient-to-r from-brand-400 to-brand-600" :style="'width:' + b.pct + '%'"></div>
                                </div>
                                <p class="mt-1 text-[12px] text-ink-muted tabular-nums" x-show="(hasTulong && b.amount) || b.open" x-text="[hasTulong && b.amount ? peso(b.amount) + ' released' : null, b.open ? b.open + ' still open' : null].filter(Boolean).join(' · ')"></p>
                            </div>
                        </template>
                        <p x-show="!byBarangay.length" class="py-6 text-center text-sm text-ink-muted">No one served in this period.</p>
                    </div>
                </x-ui.card>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 items-start">
                <x-ui.card class="xl:col-span-3">
                    <h3 class="text-lg font-extrabold text-ink mb-3" x-text="hasTulong ? 'By program' : 'By document'"></h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[11.5px] font-bold uppercase tracking-[.06em] text-ink-muted">
                                    <th class="py-2 pr-3">Program / document</th>
                                    <th class="py-2 px-2 text-right">Received</th>
                                    <th class="py-2 px-2 text-right">Served</th>
                                    <th class="py-2 px-2 text-right">Rejected</th>
                                    <th class="py-2 px-2 text-right">Open</th>
                                    <th class="py-2 pl-2 text-right" x-show="hasTulong">Released</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-600/[.08]">
                                <template x-for="r in data.by_service" :key="r.service">
                                    <tr>
                                        <td class="py-2.5 pr-3 font-semibold text-ink-body" x-text="r.service"></td>
                                        <td class="py-2.5 px-2 text-right tabular-nums" x-text="r.received"></td>
                                        <td class="py-2.5 px-2 text-right tabular-nums font-bold" x-text="r.served"></td>
                                        <td class="py-2.5 px-2 text-right tabular-nums" x-text="r.rejected"></td>
                                        <td class="py-2.5 px-2 text-right tabular-nums" x-text="r.open"></td>
                                        <td class="py-2.5 pl-2 text-right tabular-nums" x-show="hasTulong" x-text="peso(r.amount_released)"></td>
                                    </tr>
                                </template>
                                <tr x-show="!data.by_service.length"><td colspan="6" class="py-6 text-center text-ink-muted">Nothing filed or served in this period.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>

                <x-ui.card class="xl:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-3">
                        <h3 class="text-lg font-extrabold text-ink">Twelve months</h3>
                        <p class="flex items-center gap-3 text-[13px] font-semibold text-ink-muted">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-500"></span>Received</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gold-600"></span><span x-text="hasTulong ? 'Served' : 'Released'"></span></span>
                        </p>
                    </div>
                    <x-ui.clay-trend data="trend" label="Cases received and served in each of the last twelve months" />
                </x-ui.card>
            </div>

            {{-- The Senior Citizen, PWD and Solo Parent IDs the office released
                 (MSWDO, OSCA): in the period, and of all, good today and
                 lapsing within 30 days. --}}
            <x-ui.card x-show="data.ids" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="text-lg font-extrabold text-ink">IDs released</h3>
                    <span class="text-[12.5px] font-semibold text-ink-muted" x-text="data.ids ? num(data.ids.valid) + ' valid today · ' + num(data.ids.lapsing) + ' lapsing within 30 days' : ''"></span>
                </div>
                <div class="grid gap-3 grid-cols-1 sm:grid-cols-3">
                    <template x-for="k in (data.ids ? data.ids.kinds : [])" :key="k.sector">
                        <div class="rounded-2xl surface-pressed-sm px-4 py-3">
                            <p class="text-[13px] font-bold text-ink-body" x-text="k.name"></p>
                            <p class="mt-1 text-2xl font-extrabold text-ink tabular-nums" x-text="num(k.released)"></p>
                            <p class="text-[12px] text-ink-muted" x-text="'released in the period · ' + num(k.valid) + ' valid today' + (k.lapsing ? ' · ' + num(k.lapsing) + ' lapsing soon' : '')"></p>
                        </div>
                    </template>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h3 class="text-lg font-extrabold text-ink">Overdue</h3>
                    <span class="text-[12.5px] font-semibold text-ink-muted" x-text="k.overdue ? 'Past the processing days the service allows; oldest first' : ''"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" x-show="data.overdue.length">
                        <thead>
                            <tr class="text-left text-[11.5px] font-bold uppercase tracking-[.06em] text-ink-muted">
                                <th class="py-2 pr-3">Reference</th><th class="py-2 px-2">Applicant</th><th class="py-2 px-2 hidden md:table-cell">Program</th>
                                <th class="py-2 px-2 hidden sm:table-cell">Barangay</th><th class="py-2 px-2">Status</th><th class="py-2 pl-2 text-right">Days late</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="r in data.overdue" :key="r.ref">
                                <tr>
                                    <td class="py-2.5 pr-3"><a :href="requestsUrl(r)" class="font-mono text-[12.5px] font-bold text-brand-600 hover:underline" x-text="r.ref"></a></td>
                                    <td class="py-2.5 px-2 text-ink-body" x-text="r.applicant"></td>
                                    <td class="py-2.5 px-2 text-ink-muted hidden md:table-cell" x-text="r.service"></td>
                                    <td class="py-2.5 px-2 text-ink-muted hidden sm:table-cell" x-text="r.barangay"></td>
                                    <td class="py-2.5 px-2 text-ink-muted" x-text="r.status"></td>
                                    <td class="py-2.5 pl-2 text-right font-bold text-rose-600 tabular-nums" x-text="r.days_overdue"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <p x-show="!data.overdue.length" class="py-5 text-center text-sm font-semibold text-emerald-700">Nothing is overdue.</p>
                </div>
            </x-ui.card>

            <x-ui.card x-show="reports().length" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                    <h3 class="text-lg font-extrabold text-ink">Printable reports</h3>
                    <span class="text-[12.5px] font-semibold text-ink-muted" x-text="(data.short || data.office) + (barangay ? ' · Brgy. ' + barangay : '') + ' · ' + data.period.label"></span>
                </div>
                <p class="text-[13px] text-ink-muted mb-4">Ready to print in the LGU's report format, with the letterhead, totals and signature lines.</p>
                <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
                    <template x-for="r in reports()" :key="r.key">
                        <button type="button" @click="mayOpen(r) && print(r.key)" :disabled="!mayOpen(r)" class="group flex items-start text-left rounded-[18px] bg-white/60 border border-white/80 px-4 py-3.5 shadow-glass-sm hover:-translate-y-0.5 transition-transform duration-200 disabled:opacity-55 disabled:hover:translate-y-0">
                            <span class="flex items-start gap-3">
                                <span class="w-10 h-10 rounded-xl grid place-items-center shrink-0 bg-brand-50 text-brand-600"><i :data-lucide="info(r.key).icon" class="w-5 h-5"></i></span>
                                <span class="min-w-0">
                                    <span class="block text-[14px] font-bold text-ink" x-text="r.title || (hasTulong ? 'Report of Assistance Extended' : 'Report of Documents Issued')"></span>
                                    <span class="block text-[12px] text-ink-muted mt-0.5" x-text="info(r.key).desc"></span>
                                    <span class="block text-[11.5px] font-semibold mt-1.5" :class="mayOpen(r) ? 'text-brand-600' : 'text-ink-faint'" x-text="mayOpen(r) ? 'Preview and print' : 'Department Head and administrators only'"></span>
                                </span>
                            </span>
                        </button>
                    </template>
                </div>
            </x-ui.card>
        </div>
    </template>
</div>
