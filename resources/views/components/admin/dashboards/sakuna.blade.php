{{-- The Sakuna dashboard (owner, 18 Nov): GET /admin/dashboard/summary?area=sakuna,
     the MDRRMO's home and a tab for the Information Officer and the
     administrators. Sakuna is scoped by barangay only (the MDRRMO is
     municipal here); each block needs its own submodule. The head's wording
     where the account publishes alerts, the staff's otherwise. --}}
<div x-data="areaDashboard('sakuna')" class="flex flex-col gap-[22px]">

    <x-admin.dashboards.toolbar eyebrow="Sakuna dashboard" :sakuna-reports="true">
        <p class="mt-0.5 text-[13px] text-ink-muted">Incidents, evacuation, alerts and relief, as they stand now and in the period.</p>
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
                <div x-show="has('incidents_open')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.sakuna.incidents')" label="Open incidents" valueBind="num(k.incidents_open)" icon="siren" color="red" sublabelBind="assigns ? num(k.incidents_to_validate) + ' to validate' : num(k.incidents_mine) + ' assigned to you'" :delay="0" /></div>
                <div x-show="has('centers_open')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.sakuna.evacuation-centers')" label="Evacuation centers open" valueBind="num(k.centers_open)" icon="building-2" color="brand" sublabelBind="plural(k.families_sheltered, 'family', 'families') + ' inside'" :delay="40" /></div>
                <div x-show="has('alerts_live')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.sakuna.alerts')" label="Live public alerts" valueBind="num(k.alerts_live)" icon="bell-ring" color="gold" sublabelBind="plural(k.alerts_drafts, 'draft', 'drafts') + (publishes ? ' to publish' : ' waiting for the head')" :delay="80" /></div>
                <div x-show="has('missing')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.sakuna.missing-persons')" label="Missing persons" valueBind="num(k.missing)" icon="user-round-search" color="orange" sublabelBind="num(k.unidentified) + ' not identified'" :delay="120" /></div>
                <div x-show="has('relief_released')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.sakuna.relief')" label="Relief released" valueBind="num(k.relief_released)" icon="package-check" color="deep" sublabelBind="plural(k.relief_families, 'family', 'families') + ' · ' + num(k.relief_to_release) + ' to release'" :delay="160" /></div>
                <div x-show="has('damage_reports')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.sakuna.damage-assessment')" label="Damage reports" valueBind="num(k.damage_reports)" icon="clipboard-list" color="slate" sublabelBind="pesoTile(k.damage_cost) + ' estimated'" :delay="200" /></div>
            </div>

            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">
                <x-admin.dashboards.action-list />

                <x-ui.card x-show="has('incidents_open')">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Incidents by type</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted" x-text="plural(k.incidents_in_period, 'report', 'reports') + ' in the period'"></span>
                    </div>
                    <x-ui.clay-donut rows="typeRows" total-label="Incidents" empty="No incidents reported in this period." />
                </x-ui.card>

                <x-ui.card x-show="has('centers_open')">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Evacuation centers</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted" x-text="plural(k.individuals_sheltered, 'person', 'persons') + ' inside'"></span>
                    </div>
                    <x-ui.clay-bars rows="centerRows" empty="No evacuation center is open." />
                </x-ui.card>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5" x-show="has('incidents_open')">
                <x-ui.card class="flex flex-col" ::class="charts.by_barangay ? 'xl:col-span-3' : 'xl:col-span-5'">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-3">
                        <h3 class="text-lg font-extrabold text-ink">Incidents over time</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted" x-text="(charts.trend || []).length > 62 ? 'By week' : 'By day'"></span>
                    </div>
                    <x-ui.clay-trend class="my-auto" data="trend" label="Incidents reported in each day or week of the period" />
                </x-ui.card>
                <x-ui.card class="xl:col-span-2" x-show="charts.by_barangay">
                    <h3 class="text-lg font-extrabold text-ink mb-4">Incidents by barangay</h3>
                    <x-ui.clay-bars rows="barangayRows" tone="rose" empty="No incidents reported in this period." />
                </x-ui.card>
            </div>

            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">
                <x-ui.card x-show="lists.feed">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-1">
                        <h3 class="text-lg font-extrabold text-ink">Latest from the field</h3>
                        <a href="{{ route('admin.sakuna.incidents') }}" class="shrink-0 whitespace-nowrap text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View All</a>
                    </div>
                    <div>
                        <template x-for="(f, i) in feed" :key="i">
                            <a :href="'{{ route('admin.sakuna.incidents') }}?ref=' + encodeURIComponent(f.incident || '')" class="flex items-start gap-3 px-1 py-3 hover:bg-white/40 rounded-xl" :class="i && 'border-t border-brand-600/[.09]'">
                                <span class="w-[38px] h-[38px] rounded-full bg-rose-50 text-rose-600 grid place-items-center shrink-0"><i data-lucide="radio" class="w-[17px] h-[17px]"></i></span>
                                <span class="min-w-0">
                                    <span class="block text-sm leading-snug"><span class="font-mono font-bold text-ink" x-text="f.incident"></span><span class="text-ink-muted"> · </span><span class="font-semibold text-ink-muted" x-text="feedText(f)"></span></span>
                                    <span x-show="f.note" class="block text-[12.5px] text-ink-body break-words" x-text="f.note"></span>
                                    <span class="block text-[11.5px] text-ink-faint mt-0.5" x-text="f.at ? new Date(f.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></span>
                                </span>
                            </a>
                        </template>
                        <p x-show="!feed.length" class="py-6 text-center text-sm text-ink-muted">Nothing new from the field.</p>
                    </div>
                </x-ui.card>
                <x-admin.dashboards.alerts rows="alerts" x-show="lists.alerts" />
                <x-admin.dashboards.activity />
            </div>

            <x-ui.card x-show="(charts.damage_by_sector || []).length">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="text-lg font-extrabold text-ink">Damage by sector</h3>
                    <span class="text-[12.5px] font-semibold text-ink-muted">Estimated cost of the reports in the period</span>
                </div>
                <x-ui.clay-bars rows="sectorRows" tone="gold" :columns="true" />
            </x-ui.card>
        </div>
    </template>
</div>
