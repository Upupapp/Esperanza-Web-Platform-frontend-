{{-- ============================= SYSTEM =============================
     For technical administrators (set_system; only the Super Administrator
     by default): whether each dependency answers, versions, the background
     scheduler, notifications sent in the last day, the integrations as
     configured - never a secret -, storage and the last backup
     (SystemController, scope round 2026-10-15). systemTab in
     resources/js/scope-admin-settings.js. --}}
@php
    // $tones and $ui come from settings.blade.php.
    $pill = $ui['pill'];
    $cardTitle = 'text-sm font-semibold text-ink flex items-center gap-2';
@endphp
<div x-data="systemTab" x-show="tab === 'system'" x-cloak x-effect="tab === 'system' && start()">
    <x-ui.card x-show="denied" x-cloak>
        <p class="text-sm text-slate-600">System status is for technical administrators. Your role does not include it.</p>
    </x-ui.card>

    <div x-show="!denied" class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-ink">System status</p>
                <p class="text-xs text-ink-muted" x-text="data?.generated_at ? 'Checked ' + when(data.generated_at) : 'Health, background work, integrations and backups.'"></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button variant="secondary" size="sm" icon="refresh-cw" x-bind:disabled="loading" @click="load(true)"><span x-text="loading ? 'Checking…' : 'Refresh'"></span></x-ui.button>
                <x-ui.button size="sm" icon="send" x-show="canEdit" x-bind:disabled="sending" @click="testEmail()"><span x-text="sending ? 'Sending…' : 'Send test email'"></span></x-ui.button>
            </div>
        </div>
        <p x-show="lastTest" x-cloak class="text-xs" :class="lastTest?.ok ? 'text-emerald-700' : 'text-amber-800'" x-text="lastTest ? 'Last test email, ' + when(lastTest.at) + ': ' + lastTest.text : ''"></p>

        <div x-show="loading && !loaded" class="grid grid-cols-1 lg:grid-cols-2 gap-4" aria-busy="true"><x-ui.skeleton class="h-40 lg:col-span-2" /><x-ui.skeleton class="h-32 opacity-80" /><x-ui.skeleton class="h-32 opacity-80" /></div>
        <div x-show="failed && !loading" x-cloak><x-ui.error-state messageExpr="'Could not read the system status.'" retryExpr="load(true)" :retryable="true" /></div>

        <div x-show="loaded && !failed" x-cloak class="grid grid-cols-1 lg:grid-cols-2 gap-4 transition-opacity" :class="loading && 'opacity-60'" :aria-busy="loading ? 'true' : 'false'">
            {{-- Health --}}
            <x-ui.card class="lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <h3 class="{{ $cardTitle }}"><i data-lucide="heart-pulse" class="w-4 h-4 text-ink-muted"></i>Health checks</h3>
                    <span class="{{ $pill }}" :class="@js($tones)[overall.tone]" x-text="overall.label"></span>
                </div>
                <ul class="divide-y divide-brand-600/[.08]">
                    <template x-for="c in checks" :key="c.name">
                        <li class="py-2.5 flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-700" x-text="c.label"></p>
                                <p class="text-xs text-rose-700 break-words" x-show="!c.ok && c.detail" x-text="c.detail"></p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-[11px] text-ink-muted tabular-nums" x-text="c.latency"></span>
                                <span class="{{ $pill }}" :class="c.ok ? @js($tones['emerald']) : @js($tones['rose'])" x-text="c.ok ? 'OK' : 'Failing'"></span>
                            </div>
                        </li>
                    </template>
                </ul>
            </x-ui.card>

            {{-- Versions --}}
            <x-ui.card>
                <h3 class="{{ $cardTitle }} mb-2"><i data-lucide="package" class="w-4 h-4 text-ink-muted"></i>Versions</h3>
                <dl class="divide-y divide-brand-600/[.08]">
                    <template x-for="v in versions" :key="v[0]">
                        <div class="flex items-baseline justify-between gap-3 py-1.5 text-sm">
                            <dt class="text-ink-muted" x-text="v[0]"></dt>
                            <dd class="text-slate-700 font-mono text-xs text-right break-all" x-text="v[1]"></dd>
                        </div>
                    </template>
                </dl>
            </x-ui.card>

            {{-- Background work --}}
            <x-ui.card>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <h3 class="{{ $cardTitle }}"><i data-lucide="timer" class="w-4 h-4 text-ink-muted"></i>Background scheduler</h3>
                    <span class="{{ $pill }}" :class="scheduler.ok ? @js($tones['emerald']) : @js($tones['rose'])" x-text="scheduler.label"></span>
                </div>
                <p class="text-xs text-slate-600" x-text="scheduler.text"></p>
                <dl class="mt-2 grid grid-cols-2 gap-2 text-sm">
                    <div class="rounded-xl bg-slate-50 px-3 py-2"><dt class="text-[11px] text-ink-muted">Jobs waiting</dt><dd class="font-semibold text-slate-700 tabular-nums" x-text="scheduler.queued ?? '—'"></dd></div>
                    <div class="rounded-xl px-3 py-2" :class="scheduler.failed > 0 ? 'bg-rose-50' : 'bg-slate-50'"><dt class="text-[11px] text-ink-muted">Jobs failed</dt><dd class="font-semibold tabular-nums" :class="scheduler.failed > 0 ? 'text-rose-700' : 'text-slate-700'" x-text="scheduler.failed ?? '—'"></dd></div>
                </dl>
                {{-- What the scheduler runs, as it last recorded it (background.scheduled). --}}
                <p class="text-[11px] font-semibold text-ink-muted mt-3 mb-1">Runs on the schedule</p>
                <ul class="text-[11px] text-ink-muted space-y-0.5">
                    <template x-for="j in scheduledJobs" :key="j.key">
                        <li><span class="font-medium text-slate-600" x-text="j.when"></span>: <span x-text="j.label"></span><span x-show="j.next" x-text="' (next: ' + j.next + ')'"></span></li>
                    </template>
                </ul>
            </x-ui.card>

            {{-- Notifications in the last 24 hours --}}
            <x-ui.card>
                <h3 class="{{ $cardTitle }} mb-2"><i data-lucide="bell-ring" class="w-4 h-4 text-ink-muted"></i>Notifications, last 24 hours</h3>
                <p x-show="!deliveries.length" class="text-xs text-ink-muted">Nothing was sent by email or SMS in the last 24 hours.</p>
                <div class="space-y-3">
                    <template x-for="d in deliveries" :key="d.channel">
                        <div>
                            <p class="text-xs font-semibold text-slate-700" x-text="d.label + ': ' + d.total"></p>
                            <ul class="mt-1 flex flex-wrap gap-1.5">
                                <template x-for="c in d.counts" :key="c.status">
                                    <li class="{{ $pill }}" :class="@js($tones)[c.tone]" x-text="c.label + ': ' + c.count"></li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </x-ui.card>

            {{-- Storage --}}
            <x-ui.card>
                <h3 class="{{ $cardTitle }} mb-2"><i data-lucide="hard-drive" class="w-4 h-4 text-ink-muted"></i>Storage</h3>
                <p x-show="!storage" class="text-xs text-ink-muted">The server did not report its disk space.</p>
                <template x-if="storage">
                    <div>
                        <p class="text-sm text-slate-700"><span class="font-semibold" x-text="storage.free"></span> free of <span x-text="storage.total"></span> <span class="text-ink-muted" x-text="'(' + storage.used + '% used)'"></span></p>
                        <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden" role="meter" aria-label="Disk used" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="storage.used">
                            <div class="h-full rounded-full" :class="storage.low ? 'bg-rose-500' : 'bg-brand-500'" :style="'width: ' + storage.used + '%'"></div>
                        </div>
                        <p x-show="storage.low" class="text-xs font-medium text-rose-700 mt-1.5">Running low: uploads and backups need room.</p>
                    </div>
                </template>
            </x-ui.card>

            {{-- Last backup --}}
            <x-ui.card>
                <h3 class="{{ $cardTitle }} mb-2"><i data-lucide="archive" class="w-4 h-4 text-ink-muted"></i>Last backup</h3>
                <p x-show="!backup" class="text-xs text-ink-muted">No backup recorded. The server's backup job writes its result here once it runs.</p>
                <template x-if="backup">
                    <div class="space-y-1.5">
                        <span class="{{ $pill }}" :class="backup.ok ? @js($tones['emerald']) : @js($tones['rose'])" x-text="backup.ok ? 'Completed' : 'Problem: ' + backup.status"></span>
                        <p class="text-sm text-slate-700" x-text="backup.when"></p>
                        <p class="text-xs text-ink-muted" x-show="backup.size" x-text="'Size: ' + backup.size"></p>
                        <p class="text-xs text-ink-muted break-all" x-show="backup.file" x-text="'File: ' + backup.file"></p>
                        <p class="text-xs text-ink-muted break-all" x-show="backup.where" x-text="'Kept at: ' + backup.where"></p>
                        <p class="text-xs break-words" :class="backup.ok ? 'text-ink-muted' : 'text-rose-700'" x-show="backup.message" x-text="backup.message"></p>
                    </div>
                </template>
            </x-ui.card>

            {{-- Integrations: configured or not; secrets never leave the server. --}}
            <x-ui.card class="lg:col-span-2">
                <h3 class="{{ $cardTitle }} mb-3"><i data-lucide="plug" class="w-4 h-4 text-ink-muted"></i>Integrations</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach(['mail' => 'mail', 'sms' => 'message-square', 'identity' => 'scan-face', 'scan' => 'shield-check', 'payments' => 'credit-card'] as $integration => $integrationIcon)
                        <template x-for="c in integrations.filter((i) => i.key === '{{ $integration }}')" :key="c.key">
                            <div class="rounded-2xl border border-brand-600/10 bg-white/60 p-3.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-slate-700 flex items-center gap-2"><i data-lucide="{{ $integrationIcon }}" class="w-4 h-4 text-ink-muted"></i><span x-text="c.name"></span></p>
                                    <span class="{{ $pill }}" :class="@js($tones)[c.tone]" x-text="c.state"></span>
                                </div>
                                <ul class="mt-1.5 space-y-0.5">
                                    <template x-for="(line, li) in c.lines" :key="li"><li class="text-xs text-ink-muted break-words" x-text="line"></li></template>
                                </ul>
                            </div>
                        </template>
                    @endforeach
                </div>
                <p class="text-xs text-ink-muted mt-3" x-show="webUrl" x-text="'Links in emails open ' + webUrl"></p>
                <p class="text-xs text-ink-muted mt-3" x-show="!webUrl">Links in emails are off: no website address is configured.</p>
            </x-ui.card>
        </div>
    </div>
</div>
