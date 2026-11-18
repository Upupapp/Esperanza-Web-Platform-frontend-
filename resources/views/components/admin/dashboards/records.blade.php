{{-- The Records dashboard (owner, 18 Nov), the Records Officer's home: GET
     /admin/dashboard/summary?area=records. The resident registry and how well
     it is evidenced (the record verification ladder, axis 3), profiling,
     portal accounts and ID checks to verify, data quality and Papeles. Each
     figure only with its own permission; scoped by barangay. --}}
@php($people = route('admin.constituents'))
<div x-data="areaDashboard('records')" class="flex flex-col gap-[22px]">

    <x-admin.dashboards.toolbar eyebrow="Records dashboard">
        <p class="mt-0.5 text-[13px] text-ink-muted">The resident registry, profiling, accounts to verify and data quality.</p>
    </x-admin.dashboards.toolbar>

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
                <div x-show="has('residents')"><x-ui.stat-card layout="compact" class="h-full" :href="$people" label="Registered residents" valueBind="num(k.residents)" icon="users" color="brand" sublabelBind="[has('households') ? plural(k.households, 'household', 'households') : null, has('families') ? plural(k.families, 'family', 'families') : null].filter(Boolean).join(' · ') || num(k.residents_added) + ' added in the period'" :delay="0" /></div>
                <div x-show="has('profiled_pct')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.resident-profiling')" label="Profiled" valueBind="Math.round(k.profiled_pct) + '%'" icon="user-round-search" color="green" sublabelBind="num(k.profiled) + ' of ' + num(k.of) + ' residents'" :delay="40" /></div>
                <div x-show="has('records_to_review')"><x-ui.stat-card layout="compact" class="h-full" :href="$people . '?tab=registry&verification=For%20Validation'" label="Profiles to review" valueBind="num(k.records_to_review)" icon="clock" color="orange" sublabelBind="num(ladder('Partially Verified')) + ' partially verified'" :delay="80" /></div>
                <div x-show="has('accounts_to_verify')"><x-ui.stat-card layout="compact" class="h-full" :href="$people . '?tab=citizen-verification'" label="Accounts to verify" valueBind="num(k.accounts_to_verify)" icon="user-round-check" color="light" sublabelBind="plural(k.identity_to_review, 'ID check', 'ID checks') + ' to review'" :delay="120" /></div>
                <div x-show="has('dq_open')"><x-ui.stat-card layout="compact" class="h-full" :href="$people . '?tab=data-quality'" label="Data quality issues" valueBind="num(k.dq_open)" icon="triangle-alert" color="red" sublabelBind="num(k.dq_mine) + ' assigned to you'" :delay="160" /></div>
                <div x-show="has('papeles_to_verify')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.papeles')" label="Papeles to verify" valueBind="num(k.papeles_to_verify)" icon="folder-open" color="gold" sublabelBind="num(k.papeles_verified) + ' verified in the period'" :delay="200" /></div>
            </div>

            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">
                <x-admin.dashboards.action-list />

                <x-ui.card>
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Record verification</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted">How well each record is evidenced</span>
                    </div>
                    <x-ui.clay-donut rows="ladderRows" total-label="Records" empty="No resident records yet." :keep-colors="true" />
                </x-ui.card>

                <x-ui.card x-show="(charts.coverage_by_barangay || []).length">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Profiling by barangay</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted">Lowest first</span>
                    </div>
                    <x-ui.clay-bars rows="coverageRows" empty="No profiling figures yet." />
                </x-ui.card>

                <x-ui.card x-show="!(charts.coverage_by_barangay || []).length && charts.demographics">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Residents</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted" x-text="plural(demo.people, 'person', 'persons')"></span>
                    </div>
                    <x-ui.clay-donut rows="demo.sex" total-label="Residents" empty="No residents on record yet." :keep-colors="true" />
                </x-ui.card>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
                <x-ui.card class="flex flex-col xl:col-span-3">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-3">
                        <h3 class="text-lg font-extrabold text-ink">Residents and accounts added</h3>
                        <p class="flex items-center gap-3 text-[13px] font-semibold text-ink-muted">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-500"></span>Residents</span>
                            <span x-show="charts.trend && charts.trend.accounts_registered" class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gold-600"></span>Accounts</span>
                        </p>
                    </div>
                    <x-ui.clay-trend class="my-auto" data="trend" label="Residents added and portal accounts registered in each of the last twelve months" />
                </x-ui.card>
                <x-ui.card class="xl:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Data quality issues by type</h3>
                        <a href="{{ $people }}?tab=data-quality" class="shrink-0 whitespace-nowrap text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View All</a>
                    </div>
                    <x-ui.clay-bars rows="dqRows" tone="orange" empty="No open data quality issues." />
                </x-ui.card>
            </div>

            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">
                <x-ui.card x-show="lists.to_review">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-1">
                        <h3 class="text-lg font-extrabold text-ink">Profiles to review</h3>
                        <a href="{{ $people }}?tab=registry&verification=For%20Validation" class="shrink-0 whitespace-nowrap text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View All</a>
                    </div>
                    <div>
                        <template x-for="(r, i) in toReview" :key="r.ref">
                            <a :href="'{{ $people }}?ref=' + encodeURIComponent(r.ref)" class="flex items-center justify-between gap-3 px-1 py-2.5 rounded-xl hover:bg-white/40" :class="i && 'border-t border-brand-600/[.09]'">
                                <span class="min-w-0">
                                    <span class="block text-[14px] font-bold text-ink truncate" x-text="r.name"></span>
                                    <span class="block text-[12px] text-ink-muted" x-text="r.ref + ' · Brgy. ' + r.barangay"></span>
                                </span>
                                <span class="shrink-0 text-[12px] font-semibold text-ink-muted" x-text="'Since ' + shortDate(r.since)"></span>
                            </a>
                        </template>
                        <p x-show="!toReview.length" class="py-6 text-center text-sm text-ink-muted">No profile waits for review.</p>
                    </div>
                </x-ui.card>

                <x-ui.card x-show="lists.dq_mine">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-1">
                        <h3 class="text-lg font-extrabold text-ink">Your data issues</h3>
                        <a href="{{ $people }}?tab=data-quality&mine=1" class="shrink-0 whitespace-nowrap text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View All</a>
                    </div>
                    <div>
                        <template x-for="(r, i) in dqMine" :key="r.ref">
                            <div class="px-1 py-2.5" :class="i && 'border-t border-brand-600/[.09]'">
                                <p class="text-[14px] font-bold text-ink leading-snug" x-text="r.type"></p>
                                <p class="text-[12.5px] text-ink-body break-words" x-text="r.description"></p>
                                <p class="text-[11.5px] text-ink-faint mt-0.5" x-text="[r.ref, r.severity, r.barangay ? 'Brgy. ' + r.barangay : null, r.status].filter(Boolean).join(' · ')"></p>
                            </div>
                        </template>
                        <p x-show="!dqMine.length" class="py-6 text-center text-sm text-ink-muted">No data issue is assigned to you.</p>
                    </div>
                </x-ui.card>

                <x-admin.dashboards.activity />
            </div>
        </div>
    </template>
</div>
