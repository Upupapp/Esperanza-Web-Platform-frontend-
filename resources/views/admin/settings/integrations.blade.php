{{-- ============================= INTEGRATIONS =============================
     What the server is connected to, as configured (GET /admin/system/status;
     never a key or password), for technical administrators (set_system).
     Anyone else is told where to find it. This was a static list that called
     GCash and an SMS gateway "Approved" though neither exists (docs review,
     scope round 2026-10-15). integrationsTab in
     resources/js/scope-admin-settings.js. --}}
<div x-data="integrationsTab" x-show="tab === 'integrations'" x-cloak x-effect="tab === 'integrations' && start()">
    <x-ui.card>
        <h3 class="text-sm font-semibold text-ink mb-1">Connected Services</h3>
        <p class="text-xs text-ink-muted mb-4">As the server is set up now. Keys and passwords are never shown.</p>

        <div x-show="started && !allowed && !loading" x-cloak class="{{ $ui['note'] }}">
            <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
            <p>Integration status is shown to technical administrators, on the System tab.</p>
        </div>

        <div x-show="allowed" x-cloak>
            <div x-show="loading && !loaded" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-14" /><x-ui.skeleton class="h-14 opacity-80" /><x-ui.skeleton class="h-14 opacity-60" /></div>
            <div x-show="failed && !loading" x-cloak><x-ui.error-state messageExpr="'Could not read the integrations.'" retryExpr="load()" :retryable="true" /></div>
            <div x-show="loaded && !failed" class="space-y-2.5">
                @foreach(['mail' => 'mail', 'sms' => 'message-square', 'identity' => 'scan-face', 'scan' => 'shield-check', 'payments' => 'credit-card'] as $integration => $integrationIcon)
                    <template x-for="c in cards.filter((i) => i.key === '{{ $integration }}')" :key="c.key">
                        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1.5 rounded-xl border border-brand-600/10 p-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <span class="w-8 h-8 rounded-lg bg-slate-100 text-ink-muted flex items-center justify-center shrink-0"><i data-lucide="{{ $integrationIcon }}" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700" x-text="c.name"></p>
                                    <template x-for="(line, li) in c.lines" :key="li"><p class="text-xs text-ink-muted break-words" x-text="line"></p></template>
                                </div>
                            </div>
                            <span class="{{ $ui['pill'] }}" :class="@js($tones)[c.tone]" x-text="c.state"></span>
                        </div>
                    </template>
                @endforeach
                <p class="text-xs text-ink-muted pt-1" x-text="webUrl ? 'Links in emails open ' + webUrl : 'Links in emails are off: no website address is configured.'"></p>
            </div>
        </div>
    </x-ui.card>
</div>
