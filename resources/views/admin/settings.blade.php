@php
    $tab = $tab ?? 'general';
    $supportIconOptions = ['phone', 'mail', 'message-square', 'map-pin', 'clock', 'life-buoy', 'headset', 'globe'];
@endphp

<x-layouts.admin title="Settings" subtitle="Platform preferences and configuration." :active="$tab === 'support' ? 'support' : 'settings'">
    <div x-data="{ tab: '{{ $tab }}', can(sub, perm) { return this.$store.session.canPerm ? this.$store.session.canPerm(sub, perm) : true; } }" class="animate-fade-up">

        <div x-show="!$store.session.can('settings')" x-cloak>
            <x-admin.access-restricted module="System Settings" icon="settings" />
        </div>

        <div x-show="$store.session.can('settings')" x-cloak class="grid grid-cols-1 lg:grid-cols-4 gap-4">

        <div class="lg:col-span-1">
            <x-ui.card padded="false" class="p-2">
                <nav class="space-y-0.5">
                    @foreach(['general' => ['building-2', 'General'], 'support' => ['life-buoy', 'Support Tickets'], 'notifications' => ['bell', 'Notifications'], 'security' => ['shield', 'Security'], 'branding' => ['palette', 'Branding'], 'service-catalog' => ['layout-list', 'Service Catalog'], 'site-content' => ['file-text', 'Site Content'], 'integrations' => ['plug', 'Integrations'], 'audit-logs' => ['scroll-text', 'Audit Logs']] as $key => [$icon, $label])
                        <button
                            @click="tab = '{{ $key }}'"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium transition-colors"
                            :class="tab === '{{ $key }}' ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50'"
                        >
                            <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>{{ $label }}
                        </button>
                    @endforeach
                </nav>
            </x-ui.card>
        </div>

        <div class="lg:col-span-3">
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
                    <h3 class="text-sm font-semibold text-navy-900 mb-4">General Settings</h3>

                    <div
                        x-show="!can('set_general','edit')"
                        class="mb-4 flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3"
                    >
                        <i data-lucide="lock" class="w-4 h-4 text-slate-500 shrink-0 mt-0.5"></i>
                        <p class="text-xs text-slate-500">
                            <span class="font-semibold text-slate-600">View only.</span>
                            Only the <span class="font-medium text-slate-600">Super Administrator</span> and <span class="font-medium text-slate-600">Municipal Administrator</span> roles can change general settings.
                        </p>
                    </div>

                    <div class="space-y-4 max-w-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Municipality</label>
                                <p class="text-sm text-slate-500 py-2.5 px-4 rounded-xl bg-slate-50" x-text="municipality"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Province</label>
                                <p class="text-sm text-slate-500 py-2.5 px-4 rounded-xl bg-slate-50" x-text="province"></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="default-language">Default Language</label>
                            <select id="default-language" x-model="language" :disabled="!can('set_general','edit')" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
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
                    <h3 class="text-sm font-semibold text-navy-900 mb-1">Notification Settings</h3>
                    <p class="text-xs text-slate-500 mb-3">Choose which system events trigger notifications for administrators.</p>
                    <p class="mb-4 flex items-start gap-2 rounded-xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 text-xs text-slate-600">
                        <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
                        <span>These apply to every officer. The bell refreshes every minute. Emails go to the officer's work email; addresses on test domains are skipped.</span>
                    </p>
                    <div class="divide-y divide-slate-50">
                        <template x-for="pref in prefs" :key="pref.key">
                            <label class="flex items-center justify-between gap-4 py-2.5" :class="can('set_general','edit') ? 'cursor-pointer' : 'cursor-not-allowed'">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700" x-text="pref.label"></p>
                                    <p class="text-xs text-slate-500 mt-0.5" x-text="pref.desc"></p>
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
                    <h3 class="text-sm font-semibold text-navy-900 mb-3">What protects staff accounts</h3>
                    <ul class="space-y-2.5 text-sm text-slate-600">
                        <li class="flex gap-2.5"><i data-lucide="lock" class="w-4 h-4 mt-0.5 text-slate-500 shrink-0"></i><span>Five wrong passwords lock an account for 15 minutes. Every failed sign-in and lockout is in the Audit Logs.</span></li>
                        <li class="flex gap-2.5"><i data-lucide="key-round" class="w-4 h-4 mt-0.5 text-slate-500 shrink-0"></i><span>A password set by an administrator (a new account, or a reset) must be replaced by the officer at their next sign-in. Officers change their own password under My Profile.</span></li>
                        <li class="flex gap-2.5"><i data-lucide="smartphone" class="w-4 h-4 mt-0.5 text-slate-500 shrink-0"></i><span>Sign-in codes are required for the roles the role catalog marks for them.</span></li>
                        <li class="flex gap-2.5"><i data-lucide="log-out" class="w-4 h-4 mt-0.5 text-slate-500 shrink-0"></i><span>Archiving an account, or resetting its password, signs it out everywhere at once.</span></li>
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
                        <i data-lucide="lock" class="w-4 h-4 text-slate-500 shrink-0 mt-0.5"></i>
                        <p class="text-xs text-slate-500">
                            <span class="font-semibold text-slate-600">View only.</span>
                            Branding applies to both portals, so only the <span class="font-medium text-slate-600">Super Administrator</span> and <span class="font-medium text-slate-600">Municipal Administrator</span> roles can change it.
                        </p>
                    </div>

                    <x-ui.card>
                        <h3 class="text-sm font-semibold text-navy-900 mb-4">Municipal Seal</h3>
                        <div class="flex items-center gap-5">
                            <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="Municipal Seal" class="w-20 h-20 rounded-full ring-4 ring-slate-100 object-cover">
                            <div>
                                <p class="text-sm text-slate-600">esperanza-seal.png</p>
                                <p class="text-xs text-slate-500 mt-0.5">512×512px · PNG · Used across both portals</p>
                                <p class="text-[11px] text-slate-500 mt-2">To change the seal, send the new image to the ICT Office: it is part of the site, not a setting.</p>
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <h3 class="text-sm font-semibold text-navy-900 mb-4">Theme</h3>
                        <p class="mb-4 flex items-start gap-2 rounded-xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 text-xs text-slate-600 max-w-sm">
                            <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
                            <span>Both portals apply the theme colours and the municipality name on their next page load. Every version is kept.</span>
                        </p>
                        <div class="max-w-sm space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Color Theme</label>
                                <select aria-label="Color Theme" x-model="theme" :disabled="!can('set_branding','edit')" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                                    <template x-for="t in themes" :key="t"><option :value="t" x-text="t"></option></template>
                                </select>
                            </div>
                            <x-ui.input name="municipality_name" label="Municipality Name" x-model="municipalityName" x-bind:disabled="!can('set_branding','edit')" class="disabled:opacity-60 disabled:cursor-not-allowed" />
                            <p class="text-[11px] text-slate-500" x-show="current?.version">Current version <span x-text="current?.version"></span><span x-show="current?.effective_date"> · effective <span x-text="current?.effective_date"></span></span></p>
                            <x-ui.button icon="check" x-bind:disabled="saving" @click="save()" x-show="can('set_branding','edit')" x-text="saving ? 'Publishing…' : 'Publish New Version'"></x-ui.button>
                        </div>
                    </x-ui.card>

                    <x-ui.card class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-navy-900">Municipality Information</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ config('esperanza.municipality') }}, {{ config('esperanza.province') }} · {{ count(config('esperanza.barangays')) }} barangays</p>
                        </div>
                    </x-ui.card>
                </div>
            </div>

            {{-- ============================= SERVICE CATALOG ============================= --}}
            <div
                x-data="{
                    loading: true, failed: false,
                    services: [],
                    typeFilter: 'dokyu',
                    search: '',
                    open: false,
                    selected: null,
                    reqInput: '',
                    saving: false,
                    async loadServices() {
                        this.loading = true; this.failed = false;
                        try {
                            const [dokyu, tulong] = await Promise.all([
                                $api.get('/services', { params: { type: 'dokyu' } }),
                                $api.get('/services', { params: { type: 'tulong' } }),
                            ]);
                            this.services = [...(dokyu.data || []), ...(tulong.data || [])];
                        } catch { this.failed = true; } finally { this.loading = false; }
                    },
                    get filteredServices() {
                        const q = this.search.trim().toLowerCase();
                        return this.services.filter(s =>
                            s.type === this.typeFilter &&
                            (q === '' || s.name.toLowerCase().includes(q) || (s.category||'').toLowerCase().includes(q) || (s.office||'').toLowerCase().includes(q))
                        );
                    },
                    editService(s) {
                        this.selected = JSON.parse(JSON.stringify(s));
                        this.reqInput = '';
                        this.open = true;
                    },
                    addRequirement() {
                        if (!this.reqInput.trim()) return;
                        this.selected.requirements.push(this.reqInput.trim());
                        this.reqInput = '';
                    },
                    removeRequirement(i) { this.selected.requirements.splice(i, 1); },
                    async saveService() {
                        if (this.saving || this.selected.policy_blocked) return;
                        this.saving = true;
                        try {
                            await $api.post('/admin/settings/catalog/' + this.selected.key, {
                                fee: this.selected.fee?.display,
                                days: this.selected.days,
                                requirements: this.selected.requirements,
                            });
                            this.open = false;
                            this.$dispatch('toast', { message: 'Service catalog updated — a new version was published.', variant: 'success' });
                            await this.loadServices();
                        } catch (err) {
                            this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not update the service.', variant: 'error' });
                        } finally { this.saving = false; }
                    },
                }"
                x-show="tab === 'service-catalog'" x-cloak
                x-init="loadServices()"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit">
                        <button type="button" @click="typeFilter = 'dokyu'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="typeFilter === 'dokyu' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"><i data-lucide="file-text" class="w-3.5 h-3.5"></i>Dokyu</button>
                        <button type="button" @click="typeFilter = 'tulong'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="typeFilter === 'tulong' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"><i data-lucide="hand-heart" class="w-3.5 h-3.5"></i>Tulong</button>
                    </div>
                    <div class="relative">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Search services" type="text" x-model="search" placeholder="Search services..." class="w-56 pl-9 pr-3 py-2 text-xs rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shadow-card">
                    </div>
                </div>

                {{-- New services are seeded from the contract, not created here — no
                     backend endpoint exists to create one at runtime, so unlike the
                     rest of this tab, "Add Service" was dropped rather than wired. --}}
                <div
                    x-show="!can('set_service_catalog','edit')"
                    class="mb-4 flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3"
                >
                    <i data-lucide="lock" class="w-4 h-4 text-slate-500 shrink-0 mt-0.5"></i>
                    <p class="text-xs text-slate-500">
                        <span class="font-semibold text-slate-600">View only.</span>
                        The Service Catalog controls what every citizen pays and submits for Dokyu and Tulong, so only the <span class="font-medium text-slate-600">Super Administrator</span> and <span class="font-medium text-slate-600">Municipal Administrator</span> roles can edit it.
                    </p>
                </div>

                <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /></div>
                <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load the service catalog.'" retryExpr="loadServices()" :retryable="true" /></div>

                <div x-show="!loading && !failed">
                <p class="text-xs text-slate-500 mb-3" x-text="filteredServices.length + ' ' + (filteredServices.length === 1 ? 'service' : 'services') + ' in ' + (typeFilter === 'dokyu' ? 'Dokyu' : 'Tulong')"></p>

                <x-ui.table>
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Service</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell">Category</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden lg:table-cell">Office</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Fee</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Level</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="s in filteredServices" :key="s.key">
                            <tr class="hover:bg-slate-50/70 transition-colors cursor-pointer group" @click="editService(s)"
        tabindex="0"
        @keydown.enter.prevent="editService(s)"
        @keydown.space.prevent="editService(s)">
                                <td class="px-4 py-3">
                                    <p class="text-slate-700 font-medium group-hover:text-brand-600 transition-colors" x-text="s.name"></p>
                                    <p class="text-[11px] text-slate-500" x-text="(s.requirements||[]).length + ' requirement' + ((s.requirements||[]).length === 1 ? '' : 's')"></p>
                                </td>
                                <td class="px-4 py-3 text-slate-500 hidden md:table-cell" x-text="s.category"></td>
                                <td class="px-4 py-3 text-slate-500 hidden lg:table-cell" x-text="s.office"></td>
                                <td class="px-4 py-3">
                                    <span class="text-[11px] font-medium rounded-full px-2 py-0.5" :class="s.fee?.display === 'Free' ? 'bg-slate-100 text-slate-500' : 'bg-gold-50 text-gold-700'" x-text="s.fee?.display || '—'"></span>
                                </td>
                                <td class="px-4 py-3 hidden sm:table-cell">
                                    <span class="text-[11px] font-medium rounded-full px-2 py-0.5" :class="{ 'bg-teal-50 text-teal-600': s.level === 'Barangay', 'bg-brand-50 text-brand-600': s.level === 'Municipal', 'bg-purple-50 text-purple-600': s.level === 'Multi-Office' }" x-text="s.level"></span>
                                </td>
                                <td class="px-4 py-3 text-right" @click.stop x-show="can('set_service_catalog','edit')">
                                    <button aria-label="Edit" @click="editService(s)" class="text-slate-500 hover:text-brand-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </x-ui.table>

                <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="filteredServices.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600">No services match this search.</p>
                </div>
                </div>

                <x-ui.modal title="Edit Service" titleEn="Edit Service" maxWidth="lg">
                    <template x-if="selected">
                        <div class="space-y-4">
                            <div x-show="selected.policy_blocked" x-cloak class="flex items-start gap-2.5 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
                                <i data-lucide="triangle-alert" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
                                <p class="text-xs text-amber-700"><span class="font-semibold">Awaiting an LGU ruling.</span> <span x-text="selected.policy_gap_note"></span> This service cannot be edited until then.</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div><label class="block text-xs font-medium text-slate-500 mb-1">Service Name</label><p class="text-slate-700 font-medium" x-text="selected.name"></p></div>
                                <div><label class="block text-xs font-medium text-slate-500 mb-1">Category</label><p class="text-slate-700" x-text="selected.category"></p></div>
                                <div><label class="block text-xs font-medium text-slate-500 mb-1">Office</label><p class="text-slate-700" x-text="selected.office"></p></div>
                                <div><label class="block text-xs font-medium text-slate-500 mb-1">Level</label><p class="text-slate-700" x-text="selected.level"></p></div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <x-ui.input name="svc_fee" label="Fee" x-model="selected.fee.display" x-bind:disabled="!can('set_service_catalog','edit') || selected.policy_blocked" class="disabled:opacity-60 disabled:cursor-not-allowed" />
                                <x-ui.input name="svc_days" label="Processing Time" x-model="selected.days" x-bind:disabled="!can('set_service_catalog','edit') || selected.policy_blocked" class="disabled:opacity-60 disabled:cursor-not-allowed" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Requirements</label>
                                <ul class="space-y-1.5 mb-2">
                                    <template x-for="(req, i) in selected.requirements" :key="i">
                                        <li class="flex items-center gap-2 text-sm text-slate-600 bg-slate-50 rounded-lg px-3 py-1.5 transition-colors duration-[160ms] ease-standard focus-within:bg-white focus-within:ring-4 focus-within:ring-brand-100">
                                            <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500 shrink-0"></i>
                                            <input aria-label="Requirements"
                                                type="text" x-model="selected.requirements[i]" :disabled="!can('set_service_catalog','edit') || selected.policy_blocked"
                                                class="flex-1 min-w-0 bg-transparent border-0 p-0 text-sm text-slate-600 focus:outline-none focus:ring-0 disabled:opacity-60"
                                            >
                                            <button type="button" @click="removeRequirement(i)" x-show="can('set_service_catalog','edit') && !selected.policy_blocked" class="text-slate-300 hover:text-rose-500 shrink-0" title="Remove requirement"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                        </li>
                                    </template>
                                </ul>
                                <div class="flex items-center gap-2" x-show="can('set_service_catalog','edit') && !selected.policy_blocked">
                                    <input aria-label="Add a requirement" type="text" x-model="reqInput" @keydown.enter.prevent="addRequirement()" placeholder="Add a requirement..." class="flex-1 rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3.5 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                    <x-ui.button variant="secondary" size="sm" icon="plus" @click="addRequirement()">Add</x-ui.button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Workflow</label>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <template x-for="(step, i) in (selected.process || [])" :key="i">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-600 bg-slate-100 rounded-full px-2.5 py-1"><span class="text-slate-500" x-text="(i+1) + '.'"></span><span x-text="step"></span></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <x-slot:footer>
                        <x-ui.button variant="secondary" size="sm" @click="open = false" x-text="can('set_service_catalog','edit') ? 'Cancel' : 'Close'"></x-ui.button>
                        <x-ui.button size="sm" icon="check" x-bind:disabled="saving" @click="saveService()" x-show="can('set_service_catalog','edit') && selected && !selected.policy_blocked" x-text="saving ? 'Saving…' : 'Save Service'"></x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
            </div>

            {{-- ============================= SITE CONTENT ============================= --}}
            <div
                x-data="{
                    contentTab: 'privacy',
                    loading: true, failed: false,
                    previewOpen: false,
                    expandedSection: 0,
                    openIconPicker: null,
                    saving: false,
                    // Empty documents until the published ones load: the editor's
                    // bindings read these fields at once, and threw on null.
                    docs: {
                        privacy: { title: '', version: null, effective_date: '', intro: '', sections: [] },
                        terms: { title: '', version: null, effective_date: '', intro: '', sections: [] },
                        support: { title: '', version: null, effective_date: '', intro: '', channels: [], office_hours: '', office_hours_fil: '' },
                    },
                    supportIconOptions: @js($supportIconOptions),
                    async load() {
                        this.loading = true; this.failed = false;
                        try {
                            const [privacy, terms, support] = await Promise.all([
                                $api.get('/admin/settings/site-content/privacy'),
                                $api.get('/admin/settings/site-content/terms'),
                                $api.get('/admin/settings/site-content/support'),
                            ]);
                            this.docs = { privacy: privacy.data, terms: terms.data, support: support.data };
                        } catch { this.failed = true; } finally { this.loading = false; }
                    },
                    get doc() { return this.contentTab === 'privacy' ? this.docs.privacy : this.docs.terms; },
                    addSection() {
                        this.doc.sections.push({ heading: 'New Section', body: '' });
                        this.expandedSection = this.doc.sections.length - 1;
                    },
                    removeSection(i) {
                        this.doc.sections.splice(i, 1);
                        this.expandedSection = null;
                    },
                    moveSection(i, dir) {
                        const j = i + dir;
                        if (j < 0 || j >= this.doc.sections.length) return;
                        const tmp = this.doc.sections[i];
                        this.doc.sections[i] = this.doc.sections[j];
                        this.doc.sections[j] = tmp;
                        if (this.expandedSection === i) this.expandedSection = j;
                        else if (this.expandedSection === j) this.expandedSection = i;
                    },
                    async publish(docKey, doc) {
                        if (this.saving) return;
                        this.saving = true;
                        try {
                            await $api.post('/admin/settings/site-content/' + docKey, {
                                title: doc.title, effective_date: doc.effective_date, intro: doc.intro,
                                sections: doc.sections, channels: doc.channels || [],
                                office_hours: doc.office_hours, office_hours_fil: doc.office_hours_fil,
                            });
                            this.$dispatch('toast', { message: 'Published a new version.', variant: 'success' });
                            await this.load();
                        } catch (err) {
                            this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not publish.', variant: 'error' });
                        } finally { this.saving = false; }
                    },
                    addChannel() {
                        this.docs.support.channels = this.docs.support.channels || [];
                        this.docs.support.channels.push({ key: 'channel_' + Date.now(), icon: 'phone', label: '', label_fil: '', value: '', color: 'text-brand-600 bg-brand-50' });
                    },
                    removeChannel(i) { this.docs.support.channels.splice(i, 1); },
                }"
                x-show="tab === 'site-content'" x-cloak
                x-init="load()"
            >
                <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-40 opacity-80" /></div>
                <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load site content.'" retryExpr="load()" :retryable="true" /></div>

                <div x-show="!loading && !failed">
                <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
                    <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit">
                        <button type="button" @click="contentTab = 'privacy'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="contentTab === 'privacy' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i>Privacy Policy</button>
                        <button type="button" @click="contentTab = 'terms'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="contentTab === 'terms' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"><i data-lucide="file-text" class="w-3.5 h-3.5"></i>Terms &amp; Conditions</button>
                        <button type="button" @click="contentTab = 'support'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="contentTab === 'support' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"><i data-lucide="life-buoy" class="w-3.5 h-3.5"></i>Support Info</button>
                    </div>
                    <x-ui.button x-show="contentTab !== 'support'" variant="secondary" size="sm" icon="eye" @click="previewOpen = true">Preview as Citizen</x-ui.button>
                </div>

                <div
                    x-show="!can('set_site_content','edit')"
                    class="mb-4 flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3"
                >
                    <i data-lucide="lock" class="w-4 h-4 text-slate-500 shrink-0 mt-0.5"></i>
                    <p class="text-xs text-slate-500">
                        <span class="font-semibold text-slate-600">View only.</span>
                        Site Content is published straight to the Citizen Portal, so only the <span class="font-medium text-slate-600">Super Administrator</span> and <span class="font-medium text-slate-600">Municipal Administrator</span> roles can edit it.
                    </p>
                </div>

                <!-- Privacy Policy / Terms & Conditions editor -->
                <div x-show="contentTab === 'privacy' || contentTab === 'terms'">
                    <x-ui.card class="mb-3.5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1" for="effective-date-2">Effective Date</label>
                                <input id="effective-date-2" type="date" x-model="doc.effective_date" :disabled="!can('set_site_content','edit')" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Version</label>
                                <p class="text-sm text-slate-500 py-2.5 px-4 rounded-xl bg-slate-50 flex items-center gap-1.5"><i data-lucide="clock" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i><span x-text="'v' + doc.version + (doc.last_updated_by ? ' · updated by ' + doc.last_updated_by : '')"></span></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Introduction</label>
                            <p class="text-[11px] text-slate-500 mb-1.5">The opening paragraph citizens read before the numbered sections below.</p>
                            <textarea aria-label="Intro" rows="3" x-model="doc.intro" :disabled="!can('set_site_content','edit')" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 leading-relaxed focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed"></textarea>
                        </div>
                    </x-ui.card>

                    <div class="rounded-2xl border border-slate-100 bg-white shadow-card divide-y divide-slate-100 overflow-hidden">
                        <template x-for="(section, i) in doc.sections" :key="i">
                            <div>
                                <div
                                    class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50/70 transition-colors"
                                    @click="expandedSection = expandedSection === i ? null : i"

        role="button"
        tabindex="0"
        @keydown.enter.prevent="expandedSection = expandedSection === i ? null : i"
        @keydown.space.prevent="expandedSection = expandedSection === i ? null : i">
                                    <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-500 text-[11px] font-bold flex items-center justify-center shrink-0" x-text="i + 1"></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-navy-900 truncate" x-text="section.heading || 'Untitled section'"></p>
                                        <p class="text-xs text-slate-500 truncate" x-show="expandedSection !== i" x-text="section.body || 'No content yet'"></p>
                                    </div>
                                    <div class="flex items-center gap-0.5 shrink-0" x-show="can('set_site_content','edit')" @click.stop>
                                        <button type="button" @click="moveSection(i, -1)" :disabled="i === 0" title="Move up" class="p-1.5 rounded-lg text-slate-500 hover:text-navy-700 hover:bg-slate-100 disabled:opacity-25 disabled:pointer-events-none transition-colors"><i data-lucide="chevron-up" class="w-3.5 h-3.5"></i></button>
                                        <button type="button" @click="moveSection(i, 1)" :disabled="i === doc.sections.length - 1" title="Move down" class="p-1.5 rounded-lg text-slate-500 hover:text-navy-700 hover:bg-slate-100 disabled:opacity-25 disabled:pointer-events-none transition-colors"><i data-lucide="chevron-down" class="w-3.5 h-3.5"></i></button>
                                        <button type="button" @click="removeSection(i)" title="Remove section" class="p-1.5 rounded-lg text-slate-300 hover:text-rose-600 hover:bg-rose-50 transition-colors"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-300 shrink-0 transition-transform duration-[160ms] ease-standard" :class="expandedSection === i && 'rotate-180'"></i>
                                </div>
                                <div
                                    x-show="expandedSection === i" x-cloak
                                    x-transition:enter="transition duration-[200ms] ease-emphasized"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="px-4 pb-4 bg-slate-50/40"
                                >
                                    <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-1 mt-1">Section Heading</label>
                                    <input aria-label="Section Heading"
                                        type="text" x-model="section.heading" placeholder="Section heading" :disabled="!can('set_site_content','edit')"
                                        class="w-full bg-transparent border-0 border-b-2 border-slate-200 px-0.5 py-1.5 text-base font-bold text-navy-900 placeholder:text-slate-300 placeholder:font-normal focus:border-brand-400 outline-none transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                                    >
                                    <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wide mb-1 mt-4">Section Content</label>
                                    <textarea aria-label="Section Content"
                                        rows="5" x-model="section.body" placeholder="Write what citizens need to know in this section…" :disabled="!can('set_site_content','edit')"
                                        class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3.5 text-sm text-slate-700 leading-relaxed focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none disabled:opacity-60 disabled:cursor-not-allowed"
                                    ></textarea>
                                    <p class="text-[11px] text-slate-500 mt-1.5" x-text="(section.body || '').length + ' characters'"></p>
                                </div>
                            </div>
                        </template>
                        <div class="px-4 py-8 text-center" x-show="doc.sections.length === 0">
                            <p class="text-xs text-slate-500">No sections yet.</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-4" x-show="can('set_site_content','edit')">
                        <button type="button" @click="addSection()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:underline"><i data-lucide="plus" class="w-3.5 h-3.5"></i>Add Section</button>
                        <x-ui.button size="sm" icon="check" x-bind:disabled="saving" @click="publish(contentTab, doc)" x-text="saving ? 'Publishing…' : 'Save Changes'"></x-ui.button>
                    </div>
                </div>

                <!-- Support Info editor -->
                <div x-show="contentTab === 'support'" x-cloak>
                    <x-ui.card class="mb-3.5">
                        <label class="block text-xs font-medium text-slate-700 mb-1" for="support-page-introduction">Support Page Introduction</label>
                        <textarea id="support-page-introduction" rows="2" x-model="docs.support.intro" :disabled="!can('set_site_content','edit')" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed"></textarea>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1" for="office-hours">Office Hours</label>
                                <input id="office-hours" type="text" x-model="docs.support.office_hours" :disabled="!can('set_site_content','edit')" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1" for="office-hours-filipino">Office Hours (Filipino)</label>
                                <input id="office-hours-filipino" type="text" x-model="docs.support.office_hours_fil" :disabled="!can('set_site_content','edit')" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                            </div>
                        </div>
                    </x-ui.card>

                    <div class="space-y-2.5">
                        <template x-for="(ch, i) in (docs.support.channels || [])" :key="ch.key">
                            <div class="rounded-2xl border border-slate-100 bg-white shadow-card p-3.5 flex items-center gap-3" x-data="{ iconOpen: false }">
                                <div class="relative shrink-0">
                                    <button
                                        type="button" @click="iconOpen = !iconOpen" :disabled="!can('set_site_content','edit')"
                                        class="w-11 h-11 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 hover:border-brand-300 hover:text-brand-600 transition-colors disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:border-slate-200 disabled:hover:text-slate-500"
                                    >
                                        <i :data-lucide="ch.icon" class="w-5 h-5"></i>
                                    </button>
                                    <div
                                        x-show="iconOpen" x-cloak @click.outside="iconOpen = false"
                                        x-transition:enter="transition duration-[160ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                        class="absolute z-20 top-full left-0 mt-1.5 w-44 rounded-xl bg-white border border-slate-200 shadow-card-hover p-1.5 grid grid-cols-4 gap-1"
                                    >
                                        <template x-for="opt in supportIconOptions" :key="opt">
                                            <button
                                                type="button" @click="ch.icon = opt; iconOpen = false; $nextTick(() => window.renderIcons?.())" :title="opt"
                                                class="w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                                                :class="ch.icon === opt ? 'bg-brand-50 text-brand-600 ring-1 ring-brand-200' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-600'"
                                            >
                                                <i :data-lucide="opt" class="w-4 h-4"></i>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-[10px] font-medium text-slate-500 mb-1">Label</label>
                                        <input aria-label="Label" type="text" x-model="ch.label" placeholder="e.g. Call the ICT Office" :disabled="!can('set_site_content','edit')" class="w-full rounded-lg border border-slate-200 bg-slate-50/60 py-2 px-3 text-xs text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-medium text-slate-500 mb-1">Value</label>
                                        <input aria-label="Value" type="text" x-model="ch.value" placeholder="e.g. (056) 333-1099" :disabled="!can('set_site_content','edit')" class="w-full rounded-lg border border-slate-200 bg-slate-50/60 py-2 px-3 text-xs text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                                    </div>
                                </div>
                                <button type="button" @click="removeChannel(i)" x-show="can('set_site_content','edit')" title="Remove channel" class="p-1.5 rounded-lg text-slate-300 hover:text-rose-600 hover:bg-rose-50 transition-colors shrink-0"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-between mt-4" x-show="can('set_site_content','edit')">
                        <button type="button" @click="addChannel()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:underline"><i data-lucide="plus" class="w-3.5 h-3.5"></i>Add Contact Channel</button>
                        <x-ui.button size="sm" icon="check" x-bind:disabled="saving" @click="publish('support', docs.support)" x-text="saving ? 'Publishing…' : 'Save Changes'"></x-ui.button>
                    </div>
                </div>

                <!-- Citizen preview -->
                <div x-data="{ get open() { return previewOpen }, set open(v) { previewOpen = v } }">
                    <x-ui.modal :bare="true" maxWidth="lg">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
                            <h3 class="text-sm font-semibold text-navy-900" x-text="contentTab === 'privacy' ? 'Preview — Privacy Policy' : 'Preview — Terms &amp; Conditions'"></h3>
                            <button aria-label="Close" @click="previewOpen = false" class="text-slate-500 hover:text-slate-600 transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>
                        </div>
                        <div class="p-5 max-h-[65vh] overflow-y-auto scrollbar-thin">
                            <h2 class="text-base font-bold text-navy-900" x-text="doc.title"></h2>
                            <p class="text-xs text-slate-500 mt-0.5 mb-4" x-text="'Effective ' + doc.effective_date"></p>
                            <p class="text-sm text-slate-600 leading-relaxed pb-4 border-b border-slate-100" x-text="doc.intro"></p>
                            <div class="space-y-4 mt-4">
                                <template x-for="section in doc.sections" :key="section.heading">
                                    <div>
                                        <h3 class="text-sm font-semibold text-navy-900 mb-1" x-text="section.heading"></h3>
                                        <p class="text-xs text-slate-600 leading-relaxed" x-text="section.body"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                            <x-ui.button size="sm" @click="previewOpen = false">Close Preview</x-ui.button>
                        </div>
                    </x-ui.modal>
                </div>
                </div>
            </div>

            {{-- ============================= INTEGRATIONS ============================= --}}
            {{-- No backend concept exists for third-party connection status yet —
                 left as a static preview, same treatment as Security above. --}}
            <div x-show="tab === 'integrations'" x-cloak>
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4">Connected Services</h3>
                    <div class="space-y-2.5">
                        @foreach([['name' => 'GCash Payment Gateway', 'icon' => 'credit-card', 'status' => 'Approved'], ['name' => 'SMS Gateway (Semaphore)', 'icon' => 'message-square', 'status' => 'Approved'], ['name' => 'PhilSys Verification API', 'icon' => 'badge-check', 'status' => 'Draft']] as $svc)
                            <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 p-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="{{ $svc['icon'] }}" class="w-4 h-4"></i></span>
                                    <p class="text-sm font-medium text-slate-700">{{ $svc['name'] }}</p>
                                </div>
                                <x-ui.badge :status="$svc['status']" />
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            {{-- ============================= SUPPORT TICKETS ============================= --}}
            {{-- Residents send tickets from Help → Suporta. The server had a list
                 and a reply for them, but no staff screen used either, so every
                 ticket waited forever (live test 2026-10-07, BUG-5). A reply
                 reaches the resident's bell. Permission: set_general. --}}
            <div
                x-show="tab === 'support'" x-cloak
                x-data="{
                    loading: false, failed: false, loaded: false,
                    tickets: [], filter: 'open',
                    selected: null, response: '', replyStatus: 'Completed', sending: false, error: '',
                    openStatuses: ['Submitted', 'Pending Review', 'Under Review'],
                    get shown() {
                        return this.tickets.filter((t) => this.filter === 'all' || (this.filter === 'open' ? this.openStatuses.includes(t.status) : !this.openStatuses.includes(t.status)));
                    },
                    get openCount() { return this.tickets.filter((t) => this.openStatuses.includes(t.status)).length; },
                    async load() {
                        if (!can('set_general', 'view')) return;
                        this.loading = true; this.failed = false;
                        try { this.tickets = (await $api.getAll('/admin/support/tickets')).data || []; this.loaded = true; }
                        catch { this.failed = true; }
                        finally { this.loading = false; }
                    },
                    pick(t) { this.selected = t; this.response = t.response || ''; this.replyStatus = 'Completed'; this.error = ''; },
                    async reply() {
                        if (!this.selected || this.response.trim().length < 2 || this.sending) return;
                        this.sending = true; this.error = '';
                        try {
                            const res = await $api.post('/admin/support/tickets/' + encodeURIComponent(this.selected.ref) + '/respond', { response: this.response.trim(), status: this.replyStatus });
                            const row = res.data || {};
                            this.tickets = this.tickets.map((t) => t.ref === this.selected.ref ? { ...t, ...row } : t);
                            this.selected = this.tickets.find((t) => t.ref === row.ref) || null;
                            $dispatch('toast', { message: 'Reply sent. The resident is notified in their bell.', variant: 'success' });
                        } catch (err) {
                            this.error = err.explain ? err.explain('en') : 'The reply could not be sent.';
                        } finally { this.sending = false; }
                    },
                    when(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''; },
                }"
                x-effect="if (tab === 'support' && !loaded && !loading) load()"
                class="space-y-4"
            >
                <x-ui.card x-show="!can('set_general', 'view')" x-cloak>
                    <p class="text-sm text-slate-600">Support tickets are answered by staff who hold General Settings.</p>
                </x-ui.card>
                <div x-show="can('set_general', 'view')" class="grid grid-cols-1 xl:grid-cols-5 gap-4">
                    <x-ui.card padded="false" class="xl:col-span-2 overflow-hidden">
                        <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-slate-100">
                            <h3 class="text-sm font-semibold text-navy-900">Tickets <span class="text-slate-400 font-normal" x-text="'(' + openCount + ' open)'"></span></h3>
                            <select x-model="filter" aria-label="Show" class="text-xs rounded-lg border border-slate-200 py-1.5 px-2">
                                <option value="open">Open</option>
                                <option value="closed">Answered / closed</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                        <p class="px-4 py-6 text-sm text-slate-500" x-show="loading">Loading…</p>
                        <p class="px-4 py-6 text-sm text-rose-600" x-show="failed" x-cloak>The tickets could not be loaded. <button type="button" class="underline" @click="load()">Try again</button></p>
                        <p class="px-4 py-6 text-sm text-slate-500" x-show="loaded && !shown.length" x-cloak>No tickets here.</p>
                        <ul class="divide-y divide-slate-100 max-h-[32rem] overflow-y-auto">
                            <template x-for="t in shown" :key="t.ref">
                                <li>
                                    <button type="button" @click="pick(t)" class="w-full text-left px-4 py-3 hover:bg-slate-50" :class="selected?.ref === t.ref ? 'bg-brand-50/60' : ''">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-medium text-navy-900 truncate" x-text="t.subject"></span>
                                            <span class="text-[10.5px] font-semibold px-2 py-0.5 rounded-full shrink-0" :class="openStatuses.includes(t.status) ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'" x-text="t.status"></span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5" x-text="t.ref + ' · ' + (t.citizen?.name || 'Resident') + (t.citizen?.barangay ? ' · Brgy. ' + t.citizen.barangay : '') + ' · ' + when(t.created_at)"></p>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </x-ui.card>
                    <x-ui.card class="xl:col-span-3">
                        <p class="text-sm text-slate-500" x-show="!selected">Choose a ticket to read and answer it.</p>
                        <div x-show="selected" x-cloak class="space-y-3">
                            <div>
                                <p class="text-[11px] text-slate-500" x-text="(selected?.ref || '') + ' · ' + (selected?.category || '')"></p>
                                <h3 class="text-base font-semibold text-navy-900" x-text="selected?.subject"></h3>
                                <p class="text-[11px] text-slate-500 mt-0.5" x-text="(selected?.citizen?.name || 'Resident') + (selected?.citizen?.account_no ? ' · ' + selected.citizen.account_no : '')"></p>
                            </div>
                            <p class="text-sm text-slate-700 whitespace-pre-line bg-slate-50 rounded-xl p-3" x-text="selected?.body"></p>
                            <p class="text-[11px] text-slate-500" x-show="selected?.attachment" x-text="'Attachment: ' + (selected?.attachment?.name || '')"></p>
                            <div x-show="selected?.response" class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-3">
                                <p class="text-[11px] font-semibold text-emerald-700" x-text="'Answered ' + when(selected?.responded_at)"></p>
                                <p class="text-sm text-slate-700 whitespace-pre-line mt-1" x-text="selected?.response"></p>
                            </div>
                            <div x-show="can('set_general', 'edit')" class="space-y-2">
                                <label for="ticket-reply" class="block text-xs font-medium text-slate-700">Reply to the resident</label>
                                <textarea id="ticket-reply" x-model="response" rows="5" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 p-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none" placeholder="Write the answer the resident will see in their bell."></textarea>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <select x-model="replyStatus" aria-label="After replying" class="text-xs rounded-lg border border-slate-200 py-1.5 px-2">
                                        <option value="Completed">Answered and closed</option>
                                        <option value="Under Review">Answered, still working on it</option>
                                        <option value="Cancelled">Closed without action</option>
                                    </select>
                                    <x-ui.button icon="send" @click="reply()" ::disabled="sending || response.trim().length < 2"><span x-text="sending ? 'Sending…' : 'Send reply'"></span></x-ui.button>
                                </div>
                                <p class="text-xs text-rose-600" x-show="error" x-text="error"></p>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </div>

            {{-- ============================= AUDIT LOGS ============================= --}}
            <div
                x-data="{
                    loading: true, failed: false,
                    logs: [],
                    search: '',
                    moduleFilter: '',
                    // The trail is paged on the server, newest first. It used to
                    // load one page of 100 and present it as the whole log; now it
                    // says how many entries exist and loads older ones on request.
                    page: 0, lastPage: 1, total: 0, loadingMore: false,
                    async load() {
                        this.loading = true; this.failed = false;
                        try {
                            const res = await $api.get('/admin/audit-logs', { params: { per_page: 100, page: 1, module: this.moduleFilter || undefined } });
                            this.logs = res.data || [];
                            this.page = 1;
                            this.lastPage = res.meta?.last_page || 1;
                            this.total = res.meta?.total ?? this.logs.length;
                        } catch { this.failed = true; } finally { this.loading = false; }
                    },
                    async loadMore() {
                        if (this.loadingMore || this.page >= this.lastPage) return;
                        this.loadingMore = true;
                        try {
                            const res = await $api.get('/admin/audit-logs', { params: { per_page: 100, page: this.page + 1, module: this.moduleFilter || undefined } });
                            this.logs = [...this.logs, ...(res.data || [])];
                            this.page += 1;
                            this.lastPage = res.meta?.last_page || this.lastPage;
                        } catch (err) {
                            this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not load older entries.', variant: 'error' });
                        } finally { this.loadingMore = false; }
                    },
                    get filteredLogs() {
                        const q = this.search.trim().toLowerCase();
                        return this.logs.filter(l => q === '' || (l.user||'').toLowerCase().includes(q) || (l.action||'').toLowerCase().includes(q));
                    },
                }"
                x-show="tab === 'audit-logs'" x-cloak
                x-init="load()"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Search actions or personnel" type="text" x-model="search" placeholder="Search actions or personnel..." class="w-64 pl-10 pr-4 py-2 text-sm rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shadow-card">
                    </div>
                    <select aria-label="Module filter" x-model="moduleFilter" @change="load()" class="text-sm rounded-xl border border-slate-200 bg-white py-2 px-3.5 shadow-card focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        <option value="">All Modules</option>
                        <option value="dokyu">Dokyu</option>
                        <option value="tulong">Tulong</option>
                        <option value="sakuna">Sakuna</option>
                        <option value="constituents">Constituents</option>
                        <option value="communications">Communications</option>
                        <option value="payments">Payments</option>
                        <option value="papeles">Papeles</option>
                        <option value="user_management">User Management</option>
                        <option value="identity">Sign-in &amp; Accounts</option>
                        <option value="support">Support</option>
                        <option value="reports">Reports</option>
                        <option value="privacy">Privacy</option>
                        <option value="settings">Settings</option>
                    </select>
                </div>

                <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /></div>
                <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load audit logs.'" retryExpr="load()" :retryable="true" /></div>

                <div x-show="!loading && !failed">
                <x-ui.table>
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Personnel</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Action</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell">Module</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden lg:table-cell">Target</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="log in filteredLogs" :key="log.id">
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-2.5 text-slate-700 font-medium whitespace-nowrap" x-text="log.user"></td>
                                <td class="px-4 py-2.5 text-slate-600" x-text="log.action"></td>
                                <td class="px-4 py-2.5 hidden md:table-cell"><span class="text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md" x-text="log.module"></span></td>
                                <td class="px-4 py-2.5 text-slate-500 hidden lg:table-cell text-xs" x-text="log.target || '—'"></td>
                                <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell whitespace-nowrap" x-text="log.time ? new Date(log.time).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></td>
                            </tr>
                        </template>
                        <tr x-show="!filteredLogs.length"><td colspan="5" class="px-4 py-6 text-center text-xs text-slate-500">No matching audit log entries.</td></tr>
                    </tbody>
                </x-ui.table>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                    <span>
                        Showing <span x-text="logs.length.toLocaleString()"></span> of <span x-text="total.toLocaleString()"></span> entries, newest first.
                        <span x-show="search.trim() !== ''">Search covers the entries loaded.</span>
                    </span>
                    <button type="button" x-show="page < lastPage" @click="loadMore()" :disabled="loadingMore"
                            class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-card hover:bg-slate-50 disabled:opacity-60">
                        <span x-text="loadingMore ? 'Loading…' : 'Load older entries'"></span>
                    </button>
                </div>
                </div>
            </div>
        </div>

        </div>
    </div>
</x-layouts.admin>
