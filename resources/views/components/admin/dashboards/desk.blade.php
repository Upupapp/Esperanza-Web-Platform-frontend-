{{-- The front desk dashboard (owner, 18 Nov), the Front Desk Officer's home:
     GET /admin/dashboard/summary?area=desk. What came in today and how,
     what waits on staff, what is ready to hand over, who is booked today,
     accounts to verify and open tickets. Requests are counted as the request
     lists show them (scope, the services the account may view, and the
     confidential cases only for their own officers). --}}
@php($docs = route('admin.document-requests'))
<div x-data="areaDashboard('desk')" class="flex flex-col gap-[22px]">

    <x-admin.dashboards.toolbar eyebrow="Front desk dashboard">
        <p class="mt-0.5 text-[13px] text-ink-muted">Walk-ins, requests to hand over, appointments and accounts to verify.</p>
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
                <div x-show="has('filed_today')"><x-ui.stat-card layout="compact" class="h-full" :href="$docs" label="Filed today" valueBind="num(k.filed_today)" icon="calendar-clock" color="brand" sublabelBind="num(k.filed) + ' in the period · ' + num(k.walk_ins_by_me) + ' by you'" :delay="0" /></div>
                <div x-show="has('waiting')"><x-ui.stat-card layout="compact" class="h-full" :href="$docs . '?open=1'" label="Waiting on staff" valueBind="num(waitingTotal)" icon="inbox" color="orange" sublabelBind="waitingText" :delay="40" /></div>
                <div x-show="has('ready_to_claim')"><x-ui.stat-card layout="compact" class="h-full" :href="$docs . '?status=Mark%20to%20Release'" label="Ready to claim" valueBind="num(readyTotal)" icon="package-check" color="green" sublabel="To hand over to residents" :delay="80" /></div>
                <div x-show="has('appointments_today')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.appointments')" label="Appointments today" valueBind="num(k.appointments_today && k.appointments_today.total)" icon="calendar-check" color="light" sublabelBind="num(apptCount('Confirmed')) + ' confirmed · ' + num(apptCount('Completed')) + ' done'" :delay="120" /></div>
                <div x-show="has('accounts_to_verify')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.constituents') . '?tab=citizen-verification'" label="Accounts to verify" valueBind="num(k.accounts_to_verify)" icon="user-round-check" color="purple" sublabelBind="plural(k.identity_to_review, 'ID check', 'ID checks') + ' to review'" :delay="160" /></div>
                <div x-show="has('tickets_open')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.settings.feedback')" label="Open tickets" valueBind="num(k.tickets_open)" icon="message-square" color="gold" sublabelBind="num(k.tickets_mine) + ' assigned to you'" :delay="200" /></div>
            </div>

            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">
                <x-admin.dashboards.action-list />

                <x-ui.card>
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">How requests came in</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted">In the period</span>
                    </div>
                    <x-ui.clay-donut rows="channels" total-label="Requests" empty="No requests filed in this period." :keep-colors="true" />
                </x-ui.card>

                <x-ui.card>
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-1">
                        <h3 class="text-lg font-extrabold text-ink">Ready to claim</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted">Oldest first</span>
                    </div>
                    <div>
                        <template x-for="(r, i) in readyList" :key="r.ref">
                            <a :href="requestUrl(r)" class="flex items-center justify-between gap-3 px-1 py-2.5 rounded-xl hover:bg-white/40" :class="i && 'border-t border-brand-600/[.09]'">
                                <span class="min-w-0">
                                    <span class="block text-[14px] font-bold text-ink truncate" x-text="r.applicant"></span>
                                    <span class="block text-[12px] text-ink-muted truncate" x-text="[r.service, r.office, r.barangay ? 'Brgy. ' + r.barangay : null].filter(Boolean).join(' · ')"></span>
                                </span>
                                <span class="shrink-0 text-right">
                                    <span class="block font-mono text-[12px] font-bold text-brand-600" x-text="r.ref"></span>
                                    <span class="block text-[11.5px] text-ink-faint" x-text="'Since ' + since(r.since)"></span>
                                </span>
                            </a>
                        </template>
                        <p x-show="!readyList.length" class="py-6 text-center text-sm text-ink-muted">Nothing waits to be handed over.</p>
                    </div>
                </x-ui.card>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
                <x-ui.card class="flex flex-col xl:col-span-3">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-3">
                        <h3 class="text-lg font-extrabold text-ink" x-text="charts.daily && charts.daily.weekly ? 'Filed each week' : 'Filed each day'"></h3>
                        <p class="flex items-center gap-3 text-[13px] font-semibold text-ink-muted">
                            <span x-show="$store.session.can('dokyu')" class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-500"></span>Dokyu</span>
                            <span x-show="$store.session.can('tulong')" class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gold-600"></span>Tulong</span>
                        </p>
                    </div>
                    <x-ui.clay-trend class="my-auto" data="daily" label="Dokyu and Tulong requests filed in each day of the period" />
                </x-ui.card>
                <x-ui.card class="xl:col-span-2">
                    <h3 class="text-lg font-extrabold text-ink mb-4">Open requests by office</h3>
                    <x-ui.clay-bars rows="officeRows" empty="No open requests." />
                </x-ui.card>
            </div>

            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">
                <x-ui.card x-show="lists.appointments_today">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-1">
                        <h3 class="text-lg font-extrabold text-ink">Appointments today</h3>
                        <a href="{{ route('admin.appointments') }}" class="shrink-0 whitespace-nowrap text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View All</a>
                    </div>
                    <div>
                        <template x-for="(a, i) in appointments" :key="a.ref">
                            <div class="flex items-center gap-3 px-1 py-2.5" :class="i && 'border-t border-brand-600/[.09]'">
                                <span class="w-14 shrink-0 font-mono text-[13px] font-bold text-brand-600" x-text="String(a.time || '').slice(0, 5)"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[14px] font-bold text-ink truncate" x-text="a.resident || 'Resident'"></span>
                                    <span class="block text-[12px] text-ink-muted truncate" x-text="[a.schedule, a.office].filter(Boolean).join(' · ')"></span>
                                </span>
                                <span class="shrink-0 surface-pressed-sm rounded-full px-2.5 py-1 text-[11px] font-bold text-ink-body" x-text="a.status"></span>
                            </div>
                        </template>
                        <p x-show="!appointments.length" class="py-6 text-center text-sm text-ink-muted">No more appointments today.</p>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex flex-col items-start gap-3 mb-4">
                        <h3 class="text-lg font-extrabold text-ink leading-tight">Open requests by barangay</h3>
                        <div x-show="$store.session.can('dokyu') && $store.session.can('tulong')" class="inline-flex p-1 rounded-xl surface-pressed-sm shrink-0" role="tablist" aria-label="Module">
                            <button type="button" role="tab" :aria-selected="brgyTab === 'dokyu'" @click="brgyTab = 'dokyu'" class="px-3 py-1.5 rounded-[10px] text-[12.5px] transition-all duration-200" :class="brgyTab === 'dokyu' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'font-semibold text-ink-muted hover:text-ink'">Dokyu</button>
                            <button type="button" role="tab" :aria-selected="brgyTab === 'tulong'" @click="brgyTab = 'tulong'" class="px-3 py-1.5 rounded-[10px] text-[12.5px] transition-all duration-200" :class="brgyTab === 'tulong' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'font-semibold text-ink-muted hover:text-ink'">Tulong</button>
                        </div>
                    </div>
                    <x-ui.clay-bars rows="barangayRows" empty="No open requests." />
                </x-ui.card>

                <x-admin.dashboards.activity />
            </div>
        </div>
    </template>
</div>
