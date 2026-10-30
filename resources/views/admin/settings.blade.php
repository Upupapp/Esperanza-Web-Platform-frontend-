@php
    $tab = $tab ?? 'general';
    $supportIconOptions = ['phone', 'mail', 'message-square', 'map-pin', 'clock', 'life-buoy', 'headset', 'globe'];
    // Every tab, and the address that opens it (routes/web.php). Support
    // tickets moved to Communications > Feedback; Document Templates and
    // System joined (scope round 2026-10-15). A tab without an address of
    // its own keeps ?tab= so a reload stays on it. The Service Catalog left
    // for Dokyu's and Tulong's own pages (round 2, 2026-10-30).
    $settingsTabs = [
        'general' => ['building-2', 'General', '/admin/settings'],
        // The offices and barangay officials residents read in Direktoryo
        // (owner, 2026-10-21: they were Balita's Offices and Barangays tabs).
        'directory' => ['book-user', 'Direktoryo', '/admin/settings/directory'],
        'notifications' => ['bell', 'Notifications', null],
        'security' => ['shield', 'Security', null],
        'branding' => ['palette', 'Branding', '/admin/settings/branding'],
        'templates' => ['file-badge', 'Document Templates', '/admin/settings/templates'],
        'site-content' => ['file-text', 'Site Content', '/admin/settings/site-content'],
        'integrations' => ['plug', 'Integrations', null],
        'system' => ['server-cog', 'System', '/admin/settings/system'],
        'audit-logs' => ['scroll-text', 'Audit Logs', '/admin/settings/audit-logs'],
    ];
    $tabRoutes = array_filter(array_map(fn ($t) => $t[2], $settingsTabs));
    // Classes the tab partials share (resources/views/admin/settings/).
    $ui = [
        'label' => 'block text-xs font-semibold text-slate-700 mb-1',
        'input' => 'w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed',
        'inputSm' => 'w-full rounded-2xl border-0 surface-pressed-sm py-2 px-3.5 text-xs text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard',
        'search' => 'w-full rounded-2xl border-0 surface-pressed-sm py-2 pl-9 pr-3 text-xs text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard',
        'select' => 'text-xs rounded-2xl py-2 pl-3 pr-8 border-0 surface-pressed-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none',
        'th' => 'px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted',
        'iconBtn' => 'p-1.5 rounded-xl text-ink-muted hover:text-navy-700 hover:bg-slate-100 disabled:opacity-25 disabled:pointer-events-none transition-colors',
        'iconBtnDanger' => 'p-1.5 rounded-xl text-ink-faint hover:text-rose-600 hover:bg-rose-50 transition-colors',
        'note' => 'flex items-start gap-2 rounded-xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 text-xs text-slate-600',
        'lock' => 'flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-xs text-ink-muted',
        'warn' => 'flex items-start gap-2 rounded-xl bg-amber-50 border border-amber-200 px-3.5 py-2.5 text-xs text-amber-800',
        'error' => 'text-xs text-rose-600 mt-1',
        'pill' => 'inline-flex items-center gap-1.5 text-[11px] font-semibold rounded-full px-2.5 py-0.5 ring-1 ring-inset whitespace-nowrap',
    ];
    // Status pill colours by tone; the words beside them carry the meaning.
    $tones = [
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
@endphp

<x-layouts.admin title="Settings" subtitle="Platform preferences and configuration." active="settings">
    <div
        x-data="{
            tab: @js($tab),
            tabRoutes: @js($tabRoutes),
            can(sub, perm) { return this.$store.session.canPerm ? this.$store.session.canPerm(sub, perm) : true; },
            // Direktoryo is also for officers without Settings: the Municipal
            // Information Officer and each Barangay Secretary (2026-10-21).
            get directoryAllowed() { return this.$store.session.canPerm('comm_directory', 'view') || this.$store.session.canPerm('comm_barangay_directory', 'view'); },
            // The Facebook Page card on Integrations is also for anyone who can
            // post in Balita, without the rest of Settings (owner, 2026-10-29:
            // should apply to anyone who can post).
            get facebookAllowed() { return this.$store.session.canPerm('comm_news', 'create') || this.$store.session.canPerm('set_system', 'view'); },
            // A tab's address replaces the current one, so a reload stays on it.
            go(t) {
                this.tab = t;
                const url = this.tabRoutes[t] || ('/admin/settings?tab=' + t);
                if (window.location.pathname.replace(/\/+$/, '') + window.location.search !== url) history.replaceState(null, '', url);
            },
            init() {
                const asked = new URLSearchParams(window.location.search).get('tab');
                if (this.tab === 'general' && ['notifications', 'security', 'integrations'].includes(asked)) this.tab = asked;
                // Without Settings, Direktoryo and the Facebook Page are the only tabs there are.
                // Settings is the module without the Service Catalog (canSettings, app.js):
                // a Department Head holds that one for their own office's types (round 2).
                this.$store.session.whenReady().then(() => {
                    if (this.$store.session.canSettings()) return;
                    if (this.tab === 'integrations' && this.facebookAllowed) return;
                    if (this.directoryAllowed) this.go('directory');
                    else if (this.facebookAllowed) this.go('integrations');
                });
            },
        }"
        class="animate-fade-up"
    >

        <div x-show="!$store.session.canSettings() && !directoryAllowed && !facebookAllowed" x-cloak>
            <x-admin.access-restricted module="System Settings" icon="settings" />
        </div>

        <div x-show="$store.session.canSettings() || directoryAllowed || facebookAllowed" x-cloak class="grid grid-cols-1 lg:grid-cols-4 gap-4">

        {{-- A strip that scrolls sideways below the desktop width, a list beside the content from it. --}}
        <div class="lg:col-span-1 min-w-0">
            <x-ui.card :padded="false" class="p-2">
                <nav aria-label="Settings sections" class="flex lg:flex-col gap-0.5 overflow-x-auto lg:overflow-visible scrollbar-thin" x-init="$nextTick(() => $el.querySelector('[aria-current=page]')?.scrollIntoView({ block: 'nearest', inline: 'center' }))">
                    @foreach($settingsTabs as $tabKey => [$tabIcon, $tabLabel])
                        <button
                            type="button"
                            @click="go('{{ $tabKey }}')"
                            @if($tabKey === 'system') x-show="$store.session.canPerm('set_system', 'view')" x-cloak
                            @elseif($tabKey === 'directory') x-show="directoryAllowed" x-cloak
                            @elseif($tabKey === 'integrations') x-show="$store.session.canSettings() || facebookAllowed" x-cloak
                            @else x-show="$store.session.canSettings()" x-cloak @endif
                            class="shrink-0 lg:w-full flex items-center gap-2.5 px-3.5 py-2 rounded-2xl text-sm font-medium whitespace-nowrap transition-colors"
                            :class="tab === '{{ $tabKey }}' ? 'bg-brand-50 text-brand-700' : 'text-ink-muted hover:bg-white/60'"
                            :aria-current="tab === '{{ $tabKey }}' ? 'page' : null"
                        >
                            <i data-lucide="{{ $tabIcon }}" class="w-4 h-4 shrink-0"></i>{{ $tabLabel }}
                        </button>
                    @endforeach
                </nav>
            </x-ui.card>
        </div>

        <div class="lg:col-span-3 min-w-0">
            @include('admin.settings.directory')

            {{-- Outside the Settings-only block: its Facebook Page card is also
                 for Balita's approvers (welfare round 2026-10-29). --}}
            @include('admin.settings.integrations')

            <template x-if="$store.session.canSettings()">
            <div>
            {{-- ============================= GENERAL ============================= --}}
            <div
                x-show="tab === 'general'"
                x-data="{
                    loading: true, failed: false,
                    municipality: null, province: null, language: 'English',
                    async load() {
                        this.loading = true; this.failed = false;
                        try {
                            const res = await $api.get('/admin/settings/general');
                            this.municipality = res.data.municipality;
                            this.province = res.data.province;
                            this.language = res.data.settings?.default_language || 'English';
                        } catch { this.failed = true; } finally { this.loading = false; }
                    },
                    async save() {
                        if (!can('set_general','edit')) return;
                        try {
                            await $api.put('/admin/settings/general', { key: 'default_language', value: this.language });
                            $dispatch('toast', { message: 'General settings saved.', variant: 'success' });
                        } catch (err) {
                            $dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not save settings.', variant: 'error' });
                        }
                    },
                }"
                x-init="load()"
            >
                <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-12" /></div>
                <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load general settings.'" retryExpr="load()" :retryable="true" /></div>

                <x-ui.card x-show="!loading && !failed">
                    <h3 class="text-sm font-semibold text-ink mb-4">General Settings</h3>

                    <div
                        x-show="!can('set_general','edit')"
                        class="mb-4 flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3"
                    >
                        <i data-lucide="lock" class="w-4 h-4 text-ink-muted shrink-0 mt-0.5"></i>
                        <p class="text-xs text-ink-muted">
                            <span class="font-semibold text-slate-600">View only.</span>
                            Only the <span class="font-medium text-slate-600">Super Administrator</span> and <span class="font-medium text-slate-600">Municipal Administrator</span> roles can change general settings.
                        </p>
                    </div>

                    <div class="space-y-4 max-w-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Municipality</label>
                                <p class="text-sm text-ink-muted py-2.5 px-4 rounded-xl bg-slate-50" x-text="municipality"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Province</label>
                                <p class="text-sm text-ink-muted py-2.5 px-4 rounded-xl bg-slate-50" x-text="province"></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="default-language">Default Language</label>
                            <select id="default-language" x-model="language" :disabled="!can('set_general','edit')" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                                <option>English</option>
                                <option>Filipino</option>
                            </select>
                        </div>
                        <x-ui.button icon="check" @click="save()" x-show="can('set_general','edit')">Save Changes</x-ui.button>
                    </div>
                </x-ui.card>
            </div>

            {{-- ============================= NOTIFICATIONS ============================= --}}
            <div
                x-show="tab === 'notifications'" x-cloak
                x-data="{
                    loading: true, failed: false,
                    prefs: [
                        { key: 'new_document_requests', label: 'New Document Requests', desc: 'A Dokyu request is filed or a corrected one comes back: the officers who review that document in its barangay (or the officer handling it) get it in their bell.' },
                        { key: 'new_assistance_requests', label: 'New Assistance Requests', desc: 'The same for Tulong cases.' },
                        { key: 'new_incident_reports', label: 'New Incident Reports', desc: 'A Sakuna incident is reported, by staff or from a resident\'s phone: the officers who validate incidents in that barangay get it in their bell.' },
                        { key: 'overdue_requests', label: 'Overdue Requests', desc: 'Every day at 8:00 AM: requests past the expected release the resident was given, in one digest per officer, in the bell and by email. Each request is named once.' },
                        { key: 'weekly_summary', label: 'Weekly Summary', desc: 'Every Monday at 8:00 AM: the past seven days in each officer\'s scope (filed, released, open, overdue, payments), in the bell and by email.' },
                        { key: 'balita_reviews', label: 'Balita and Events to Review', desc: 'An officer sends a Balita post or an event for review. A writer\'s post goes to their own Department Head and the Municipal Information Office; what the Information Office sends goes to the Municipal Administrator, its lead; the Super Administrator gets them only when neither is set up.' },
                    ],
                    values: {},
                    async load() {
                        this.loading = true; this.failed = false;
                        try {
                            const res = await $api.get('/admin/settings/notifications');
                            this.values = res.data;
                        } catch { this.failed = true; } finally { this.loading = false; }
                    },
                    async toggle(key) {
                        if (!can('set_general','edit')) return;
                        const next = !this.values[key];
                        this.values[key] = next;
                        try {
                            await $api.put('/admin/settings/notifications', { [key]: next });
                        } catch (err) {
                            this.values[key] = !next;
                            $dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not save.', variant: 'error' });
                        }
                    },
                }"
                x-init="load()"
            >
                <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-12" /></div>
                <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load notification settings.'" retryExpr="load()" :retryable="true" /></div>

                <x-ui.card x-show="!loading && !failed">
                    <h3 class="text-sm font-semibold text-ink mb-1">Notification Settings</h3>
                    <p class="text-xs text-ink-muted mb-3">Choose which system events trigger notifications for administrators.</p>
                    <p class="mb-4 flex items-start gap-2 rounded-xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 text-xs text-slate-600">
                        <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
                        <span>These apply to every officer. The bell refreshes every minute. Emails go to the officer's work email; addresses on test domains are skipped.</span>
                    </p>
                    <div class="divide-y divide-brand-600/[.08]">
                        <template x-for="pref in prefs" :key="pref.key">
                            <label class="flex items-center justify-between gap-4 py-2.5" :class="can('set_general','edit') ? 'cursor-pointer' : 'cursor-not-allowed'">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700" x-text="pref.label"></p>
                                    <p class="text-xs text-ink-muted mt-0.5" x-text="pref.desc"></p>
                                </div>
                                <div class="relative shrink-0">
                                    <button
                                        @click="toggle(pref.key)"
                                        type="button"
                                        class="w-10 h-6 rounded-full transition-colors duration-[160ms] ease-standard relative"
                                        :class="values[pref.key] ? 'bg-brand-600' : 'bg-slate-200'"
                                    >
                                        <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-[160ms] ease-standard" :class="values[pref.key] && 'translate-x-4'"></span>
                                    </button>
                                </div>
                            </label>
                        </template>
                    </div>
                </x-ui.card>
            </div>

            {{-- ============================= SECURITY ============================= --}}
            {{-- 'Enforce 2FA for all' and a session-timeout choice answered with a
                 'frontend preview' toast: no policy store exists for either (live
                 test 2026-10-07, PR-8). This tab now says what the platform does. --}}
            <div x-show="tab === 'security'" x-cloak class="space-y-4">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink mb-3">What protects staff accounts</h3>
                    <ul class="space-y-2.5 text-sm text-slate-600">
                        <li class="flex gap-2.5"><i data-lucide="lock" class="w-4 h-4 mt-0.5 text-ink-muted shrink-0"></i><span>Five wrong passwords lock an account for 15 minutes. Every failed sign-in and lockout is in the Audit Logs.</span></li>
                        <li class="flex gap-2.5"><i data-lucide="key-round" class="w-4 h-4 mt-0.5 text-ink-muted shrink-0"></i><span>A password set by an administrator (a new account, or a reset) must be replaced by the officer at their next sign-in. Officers change their own password under My Profile.</span></li>
                        <li class="flex gap-2.5"><i data-lucide="smartphone" class="w-4 h-4 mt-0.5 text-ink-muted shrink-0"></i><span>Sign-in codes are required for the roles the role catalog marks for them.</span></li>
                        <li class="flex gap-2.5"><i data-lucide="log-out" class="w-4 h-4 mt-0.5 text-ink-muted shrink-0"></i><span>Archiving an account, or resetting its password, signs it out everywhere at once.</span></li>
                    </ul>
                </x-ui.card>
            </div>

            {{-- ============================= BRANDING ============================= --}}
            <div
                x-show="tab === 'branding'" x-cloak
                x-data="{
                    loading: true, failed: false,
                    themes: [],
                    theme: 'Brand Blue',
                    municipalityName: '',
                    current: null,
                    saving: false,
                    async load() {
                        this.loading = true; this.failed = false;
                        try {
                            const res = await $api.get('/admin/settings/branding');
                            this.themes = res.data.themes || [];
                            this.current = res.data.current;
                            this.theme = res.data.current?.theme || this.themes[0] || 'Brand Blue';
                            this.municipalityName = res.data.current?.municipality_name || '';
                        } catch { this.failed = true; } finally { this.loading = false; }
                    },
                    async save() {
                        if (this.saving) return;
                        this.saving = true;
                        try {
                            await $api.post('/admin/settings/branding', { theme: this.theme, municipality_name: this.municipalityName || null });
                            $dispatch('toast', { message: 'Branding published — a new version.', variant: 'success' });
                            await this.load();
                        } catch (err) {
                            $dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not publish branding.', variant: 'error' });
                        } finally { this.saving = false; }
                    },
                }"
                x-init="load()"
            >
                <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-20" /></div>
                <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load branding.'" retryExpr="load()" :retryable="true" /></div>

                <div x-show="!loading && !failed" class="space-y-4">
                    <div
                        x-show="!can('set_branding','edit')"
                        class="flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3"
                    >
                        <i data-lucide="lock" class="w-4 h-4 text-ink-muted shrink-0 mt-0.5"></i>
                        <p class="text-xs text-ink-muted">
                            <span class="font-semibold text-slate-600">View only.</span>
                            Branding applies to both portals, so only the <span class="font-medium text-slate-600">Super Administrator</span> and <span class="font-medium text-slate-600">Municipal Administrator</span> roles can change it.
                        </p>
                    </div>

                    <x-ui.card>
                        <h3 class="text-sm font-semibold text-ink mb-4">Municipal Seal</h3>
                        <div class="flex items-center gap-5">
                            <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="Municipal Seal" class="w-20 h-20 rounded-full ring-4 ring-slate-100 object-cover">
                            <div>
                                <p class="text-sm text-slate-600">esperanza-seal.png</p>
                                <p class="text-xs text-ink-muted mt-0.5">512×512px · PNG · Used across both portals</p>
                                <p class="text-[11px] text-ink-muted mt-2">To change the seal, send the new image to the ICT Office: it is part of the site, not a setting.</p>
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <h3 class="text-sm font-semibold text-ink mb-4">Theme</h3>
                        <p class="mb-4 flex items-start gap-2 rounded-xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 text-xs text-slate-600 max-w-sm">
                            <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
                            <span>Both portals apply the theme colours and the municipality name on their next page load. Every version is kept.</span>
                        </p>
                        <div class="max-w-sm space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Color Theme</label>
                                <select aria-label="Color Theme" x-model="theme" :disabled="!can('set_branding','edit')" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                                    <template x-for="t in themes" :key="t"><option :value="t" x-text="t"></option></template>
                                </select>
                            </div>
                            <x-ui.input name="municipality_name" label="Municipality Name" x-model="municipalityName" x-bind:disabled="!can('set_branding','edit')" class="disabled:opacity-60 disabled:cursor-not-allowed" />
                            <p class="text-[11px] text-ink-muted" x-show="current?.version">Current version <span x-text="current?.version"></span><span x-show="current?.effective_date"> · effective <span x-text="current?.effective_date"></span></span></p>
                            <x-ui.button icon="check" x-bind:disabled="saving" @click="save()" x-show="can('set_branding','edit')" x-text="saving ? 'Publishing…' : 'Publish New Version'"></x-ui.button>
                        </div>
                    </x-ui.card>

                    <x-ui.card class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-ink">Municipality Information</p>
                            <p class="text-xs text-ink-muted mt-0.5">{{ config('esperanza.municipality') }}, {{ config('esperanza.province') }} · {{ count(config('esperanza.barangays')) }} barangays</p>
                        </div>
                    </x-ui.card>
                </div>
            </div>

            @include('admin.settings.templates')

            @include('admin.settings.site-content')

            @include('admin.settings.system')

            @include('admin.settings.audit-logs')
            </div>
            </template>
        </div>

        </div>
    </div>
</x-layouts.admin>
