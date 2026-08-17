@php
    $notifPrefs = [
        ['label' => 'New document requests', 'desc' => 'Notify when a citizen submits a new request.', 'checked' => true],
        ['label' => 'New assistance requests', 'desc' => 'Notify when a citizen applies for assistance.', 'checked' => true],
        ['label' => 'Overdue requests', 'desc' => 'Alert when requests exceed SLA processing time.', 'checked' => true],
        ['label' => 'Weekly summary report', 'desc' => 'Email digest of municipal service activity.', 'checked' => false],
    ];

    $palette = [
        ['name' => 'Brand Blue', 'hex' => '#2F62F5', 'class' => 'bg-brand-500'],
        ['name' => 'Navy', 'hex' => '#0B1730', 'class' => 'bg-navy-900'],
        ['name' => 'Gold', 'hex' => '#ECC02A', 'class' => 'bg-gold-400'],
        ['name' => 'Emerald', 'hex' => '#22C55E', 'class' => 'bg-emerald-500'],
    ];

    $logs = [
        ['user' => 'Juan Dela Cruz', 'action' => 'Approved document request DR-2026-2154', 'module' => 'Dokyu', 'time' => 'Jul 10, 2026 · 9:42 AM', 'ip' => '203.177.42.11'],
        ['user' => 'Corazon Villareal', 'action' => 'Published announcement "Free Medical & Dental Mission"', 'module' => 'Balita', 'time' => 'Jul 9, 2026 · 4:15 PM', 'ip' => '203.177.42.19'],
        ['user' => 'Paolo Reyes', 'action' => 'Created personnel account for Angeline Mercado', 'module' => 'User Management', 'time' => 'Jul 9, 2026 · 2:03 PM', 'ip' => '203.177.42.02'],
        ['user' => 'Bienvenido Salazar', 'action' => 'Released document request DR-2026-2187', 'module' => 'Dokyu', 'time' => 'Jul 8, 2026 · 11:27 AM', 'ip' => '203.177.42.33'],
        ['user' => 'Leilani Domingo', 'action' => 'Approved assistance request AR-2026-0142', 'module' => 'Tulong', 'time' => 'Jul 8, 2026 · 10:05 AM', 'ip' => '203.177.42.08'],
        ['user' => 'System', 'action' => 'Automated backup completed successfully', 'module' => 'System', 'time' => 'Jul 8, 2026 · 2:00 AM', 'ip' => 'internal'],
        ['user' => 'Juan Dela Cruz', 'action' => 'Updated municipality branding colors', 'module' => 'Branding', 'time' => 'Jul 7, 2026 · 3:41 PM', 'ip' => '203.177.42.11'],
        ['user' => 'Marivic Ong', 'action' => 'Rejected document request DR-2026-1655', 'module' => 'Dokyu', 'time' => 'Jul 6, 2026 · 1:18 PM', 'ip' => '203.177.42.27'],
    ];

    // Service Catalog — configuration view over the Dokyu/Tulong service
    // definitions that already drive the citizen wizards and admin routing
    // (config/esperanza_municipal_documents.php, config/esperanza_barangay_documents.php,
    // and the Tulong program list in config/esperanza_rbac.php's 'tulong'
    // module). Reuses those real names/categories/offices rather than
    // inventing a parallel taxonomy — editing here is a frontend preview
    // only, same as the rest of this admin's mock CRUD (Roles, Personnel).
    $tulongCategoryMeta = [
        'tulong_medical' => ['category' => 'Medical & Health', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'tulong_burial' => ['category' => 'Burial & Bereavement', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'tulong_educational' => ['category' => 'Education', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Multi-Office'],
        'tulong_financial' => ['category' => 'Financial Assistance', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'tulong_food' => ['category' => 'Food & Relief', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Multi-Office'],
        'tulong_pension' => ['category' => 'Social Protection', 'office' => 'Office of Senior Citizens Affairs', 'level' => 'Municipal'],
        'tulong_solo_parent' => ['category' => 'Social Protection', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'tulong_erpat' => ['category' => 'Social Protection', 'office' => 'Municipal Social Welfare & Development Office', 'level' => 'Municipal'],
        'tulong_tupad' => ['category' => 'Livelihood & Employment', 'office' => 'Public Employment Service Office', 'level' => 'Municipal'],
        'tulong_tesda' => ['category' => 'Livelihood & Employment', 'office' => 'Public Employment Service Office', 'level' => 'Municipal'],
        'tulong_livelihood_mapping' => ['category' => 'Livelihood & Employment', 'office' => 'Public Employment Service Office', 'level' => 'Municipal'],
        'tulong_kabuhayan' => ['category' => 'Livelihood & Employment', 'office' => 'Public Employment Service Office', 'level' => 'Municipal'],
        'tulong_business_plan' => ['category' => 'Livelihood & Employment', 'office' => 'Public Employment Service Office', 'level' => 'Municipal'],
    ];
    $tulongSubmodules = collect(config('esperanza_rbac.permission_modules'))->firstWhere('key', 'tulong')['submodules'];

    $serviceCatalog = collect(config('esperanza_municipal_documents.documents'))
        ->concat(config('esperanza_barangay_documents.labangtaytay.documents'))
        ->map(fn ($d) => [
            'key' => $d['key'], 'name' => $d['name'], 'category' => $d['category'], 'office' => $d['office'], 'level' => $d['level'],
            'fee' => $d['fee'] ?? 'Free', 'days' => $d['days'] ?? '—', 'requirements' => $d['requirements'] ?? [], 'process' => $d['process'] ?? [],
            'service_type' => 'Dokyu', 'active' => true,
        ])
        ->concat(collect($tulongSubmodules)->map(fn ($s) => [
            'key' => $s['key'], 'name' => $s['name'],
            'category' => $tulongCategoryMeta[$s['key']]['category'], 'office' => $tulongCategoryMeta[$s['key']]['office'], 'level' => $tulongCategoryMeta[$s['key']]['level'],
            'fee' => 'Free', 'days' => '3-5 working days', 'requirements' => ['One (1) valid government-issued ID', 'Barangay Certificate of Indigency'], 'process' => ['Submit Request', 'MSWDO Assessment', 'Approval', 'Release'],
            'service_type' => 'Tulong', 'active' => true,
        ]))
        ->values()->all();

    $catalogRoles = collect(config('esperanza_rbac.roles'))->where('key', '!=', 'citizen')->map(fn ($r) => ['key' => $r['key'], 'name' => $r['name']])->values()->all();

    $tab = $tab ?? 'general';
@endphp

<x-layouts.admin title="Settings" subtitle="Platform preferences and configuration." active="settings">
    <div x-data="{ tab: '{{ $tab }}' }" class="animate-fade-up">

        <div x-show="!$store.session.can('settings')" x-cloak>
            <x-admin.access-restricted module="System Settings" icon="settings" />
        </div>

        <div x-show="$store.session.can('settings')" x-cloak class="grid grid-cols-1 lg:grid-cols-4 gap-4">

        <div class="lg:col-span-1">
            <x-ui.card padded="false" class="p-2">
                <nav class="space-y-0.5">
                    @foreach(['general' => ['building-2', 'General'], 'notifications' => ['bell', 'Notifications'], 'security' => ['shield', 'Security'], 'branding' => ['palette', 'Branding'], 'service-catalog' => ['layout-list', 'Service Catalog'], 'integrations' => ['plug', 'Integrations'], 'audit-logs' => ['scroll-text', 'Audit Logs']] as $key => [$icon, $label])
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
            <div x-show="tab === 'general'">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4">General Settings</h3>
                    <div class="space-y-4 max-w-lg">
                        <x-ui.input name="municipality" label="Municipality Name" value="Esperanza" />
                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.input name="province" label="Province" value="Masbate" />
                            <x-ui.input name="region" label="Region" value="Region V (Bicol Region)" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Default Language</label>
                            <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                <option>English</option>
                                <option>Filipino</option>
                            </select>
                        </div>
                        <x-ui.button icon="check" @click="$dispatch('toast', { message: 'Settings saved.', variant: 'success' })">Save Changes</x-ui.button>
                    </div>
                </x-ui.card>
            </div>

            <div x-show="tab === 'notifications'" x-cloak>
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-1">Notification Settings</h3>
                    <p class="text-xs text-slate-400 mb-4">Choose which system events trigger notifications for administrators.</p>
                    <div class="divide-y divide-slate-50">
                        @foreach($notifPrefs as $pref)
                            <label class="flex items-center justify-between gap-4 py-2.5 cursor-pointer">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700">{{ $pref['label'] }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $pref['desc'] }}</p>
                                </div>
                                <div class="relative shrink-0" x-data="{ on: {{ $pref['checked'] ? 'true' : 'false' }} }">
                                    <button
                                        @click="on = !on"
                                        type="button"
                                        class="w-10 h-6 rounded-full transition-colors duration-[160ms] ease-standard relative"
                                        :class="on ? 'bg-brand-600' : 'bg-slate-200'"
                                    >
                                        <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-[160ms] ease-standard" :class="on && 'translate-x-4'"></span>
                                    </button>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            <div x-show="tab === 'security'" x-cloak class="space-y-4">
                <x-ui.card class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-navy-900">Two-Factor Authentication</p>
                        <p class="text-xs text-slate-400 mt-0.5">Require 2FA for all personnel accounts.</p>
                    </div>
                    <x-ui.button variant="secondary" size="sm">Enforce for All</x-ui.button>
                </x-ui.card>
                <x-ui.card class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-navy-900">Session Timeout</p>
                        <p class="text-xs text-slate-400 mt-0.5">Automatically sign out inactive sessions.</p>
                    </div>
                    <select class="text-sm rounded-xl border border-slate-200 bg-white py-2 px-3 shadow-card focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        <option>15 minutes</option>
                        <option selected>30 minutes</option>
                        <option>1 hour</option>
                    </select>
                </x-ui.card>
            </div>

            <div x-show="tab === 'branding'" x-cloak class="space-y-4">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4">Municipal Seal</h3>
                    <div class="flex items-center gap-5">
                        <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="Municipal Seal" class="w-20 h-20 rounded-full ring-4 ring-slate-100 object-cover">
                        <div>
                            <p class="text-sm text-slate-600">esperanza-seal.png</p>
                            <p class="text-xs text-slate-400 mt-0.5">512×512px · PNG · Used across both portals</p>
                            <x-ui.button variant="secondary" size="sm" icon="upload" class="mt-2.5">Replace Seal</x-ui.button>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4">Brand Colors</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                        @foreach($palette as $color)
                            <div class="rounded-xl border border-slate-100 overflow-hidden">
                                <div class="h-16 {{ $color['class'] }}"></div>
                                <div class="p-2.5">
                                    <p class="text-xs font-medium text-slate-700">{{ $color['name'] }}</p>
                                    <p class="text-[11px] text-slate-400 font-mono">{{ $color['hex'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <x-ui.card class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-navy-900">Municipality Information</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ config('esperanza.municipality') }}, {{ config('esperanza.province') }} · {{ config('esperanza.region') }} · {{ count(config('esperanza.barangays')) }} barangays</p>
                    </div>
                    <x-ui.button variant="secondary" size="sm" icon="pencil">Edit Information</x-ui.button>
                </x-ui.card>
            </div>

            <div
                x-data="{
                    services: @js($serviceCatalog),
                    roles: @js($catalogRoles),
                    typeFilter: 'Dokyu',
                    search: '',
                    open: false,
                    selected: null,
                    isNew: false,
                    reqInput: '',
                    get filteredServices() {
                        const q = this.search.trim().toLowerCase();
                        return this.services.filter(s =>
                            s.service_type === this.typeFilter &&
                            (q === '' || s.name.toLowerCase().includes(q) || s.category.toLowerCase().includes(q) || s.office.toLowerCase().includes(q))
                        );
                    },
                    editService(s) {
                        this.selected = JSON.parse(JSON.stringify(s));
                        this.isNew = false;
                        this.reqInput = '';
                        this.open = true;
                    },
                    addService() {
                        this.selected = { key: '', name: '', category: '', office: '', level: 'Municipal', fee: 'Free', days: '3-5 working days', requirements: [], process: ['Submit Request', 'Review', 'Approval', 'Release'], roles: [], service_type: this.typeFilter, active: true };
                        this.isNew = true;
                        this.reqInput = '';
                        this.open = true;
                    },
                    addRequirement() {
                        if (!this.reqInput.trim()) return;
                        this.selected.requirements.push(this.reqInput.trim());
                        this.reqInput = '';
                    },
                    removeRequirement(i) { this.selected.requirements.splice(i, 1); },
                    toggleRole(key) {
                        this.selected.roles = this.selected.roles || [];
                        const i = this.selected.roles.indexOf(key);
                        if (i === -1) { this.selected.roles.push(key); } else { this.selected.roles.splice(i, 1); }
                    },
                    saveService() {
                        if (!this.selected.name.trim()) return;
                        if (this.isNew) {
                            this.selected.key = 'svc_' + this.selected.name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
                            this.services.push(this.selected);
                        } else {
                            const idx = this.services.findIndex(s => s.key === this.selected.key);
                            if (idx !== -1) this.services[idx] = this.selected;
                        }
                        this.open = false;
                        this.$dispatch('toast', { message: 'Service catalog updated — frontend preview only, no data is saved.', variant: 'success' });
                    },
                }"
                x-show="tab === 'service-catalog'" x-cloak
            >
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit">
                        <button type="button" @click="typeFilter = 'Dokyu'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="typeFilter === 'Dokyu' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"><i data-lucide="file-text" class="w-3.5 h-3.5"></i>Dokyu</button>
                        <button type="button" @click="typeFilter = 'Tulong'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="typeFilter === 'Tulong' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"><i data-lucide="hand-heart" class="w-3.5 h-3.5"></i>Tulong</button>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="text" x-model="search" placeholder="Search services..." class="w-56 pl-9 pr-3 py-2 text-xs rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shadow-card">
                        </div>
                        <x-ui.button size="sm" icon="plus" @click="addService()">Add Service</x-ui.button>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mb-3" x-text="filteredServices.length + ' ' + (filteredServices.length === 1 ? 'service' : 'services') + ' in ' + typeFilter"></p>

                <x-ui.table>
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Service</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Category</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Office</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Level</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="s in filteredServices" :key="s.key">
                            <tr class="hover:bg-slate-50/70 transition-colors cursor-pointer group" @click="editService(s)">
                                <td class="px-4 py-3">
                                    <p class="text-slate-700 font-medium group-hover:text-brand-600 transition-colors" x-text="s.name"></p>
                                    <p class="text-[11px] text-slate-400" x-text="s.requirements.length + ' requirement' + (s.requirements.length === 1 ? '' : 's')"></p>
                                </td>
                                <td class="px-4 py-3 text-slate-500 hidden md:table-cell" x-text="s.category"></td>
                                <td class="px-4 py-3 text-slate-500 hidden lg:table-cell" x-text="s.office"></td>
                                <td class="px-4 py-3 hidden sm:table-cell">
                                    <span class="text-[11px] font-medium rounded-full px-2 py-0.5" :class="{ 'bg-teal-50 text-teal-600': s.level === 'Barangay', 'bg-brand-50 text-brand-600': s.level === 'Municipal', 'bg-purple-50 text-purple-600': s.level === 'Multi-Office' }" x-text="s.level"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset" :class="s.active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="s.active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        <span x-text="s.active ? 'Active' : 'Inactive'"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right" @click.stop>
                                    <button @click="editService(s)" class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </x-ui.table>

                <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="filteredServices.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600">No services match this search.</p>
                </div>

                <x-ui.modal title="Edit Service" titleEn="Edit Service" maxWidth="lg">
                    <template x-if="selected">
                        <div class="space-y-4">
                            <x-ui.input name="svc_name" label="Service Name" x-model="selected.name" />
                            <div class="grid grid-cols-2 gap-3">
                                <x-ui.input name="svc_category" label="Category" x-model="selected.category" />
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Processing Level</label>
                                    <select x-model="selected.level" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                        <option value="Barangay">Barangay</option>
                                        <option value="Municipal">Municipal</option>
                                        <option value="Multi-Office">Multi-Office</option>
                                    </select>
                                </div>
                            </div>
                            <x-ui.input name="svc_office" label="Responsible Office / Department" x-model="selected.office" />
                            <div class="grid grid-cols-2 gap-3">
                                <x-ui.input name="svc_fee" label="Fee" x-model="selected.fee" />
                                <x-ui.input name="svc_days" label="Processing Time" x-model="selected.days" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Requirements</label>
                                <ul class="space-y-1.5 mb-2">
                                    <template x-for="(req, i) in selected.requirements" :key="i">
                                        <li class="flex items-center gap-2 text-sm text-slate-600 bg-slate-50 rounded-lg px-3 py-1.5">
                                            <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                            <span class="flex-1 min-w-0" x-text="req"></span>
                                            <button type="button" @click="removeRequirement(i)" class="text-slate-300 hover:text-rose-500 shrink-0"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                        </li>
                                    </template>
                                </ul>
                                <div class="flex items-center gap-2">
                                    <input type="text" x-model="reqInput" @keydown.enter.prevent="addRequirement()" placeholder="Add a requirement..." class="flex-1 rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3.5 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                    <x-ui.button variant="secondary" size="sm" icon="plus" @click="addRequirement()">Add</x-ui.button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Workflow</label>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <template x-for="(step, i) in selected.process" :key="i">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-600 bg-slate-100 rounded-full px-2.5 py-1"><span class="text-slate-400" x-text="(i+1) + '.'"></span><span x-text="step"></span></span>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Responsible Roles</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="r in roles" :key="r.key">
                                        <button
                                            type="button" @click="toggleRole(r.key)"
                                            class="text-[11px] font-medium rounded-full px-2.5 py-1 border transition-colors"
                                            :class="(selected.roles || []).includes(r.key) ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'"
                                            x-text="r.name"
                                        ></button>
                                    </template>
                                </div>
                            </div>

                            <label class="flex items-center justify-between gap-4 py-1 cursor-pointer">
                                <div>
                                    <p class="text-sm font-medium text-slate-700">Active</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Inactive services are hidden from the citizen-facing catalog.</p>
                                </div>
                                <button type="button" @click="selected.active = !selected.active" class="w-10 h-6 rounded-full transition-colors duration-[160ms] ease-standard relative shrink-0" :class="selected.active ? 'bg-brand-600' : 'bg-slate-200'">
                                    <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-[160ms] ease-standard" :class="selected.active && 'translate-x-4'"></span>
                                </button>
                            </label>
                        </div>
                    </template>

                    <x-slot:footer>
                        <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
                        <x-ui.button size="sm" icon="check" @click="saveService()">Save Service</x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
            </div>

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

            <div x-show="tab === 'audit-logs'" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="text" placeholder="Search actions or personnel..." class="w-64 pl-10 pr-4 py-2 text-sm rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shadow-card">
                    </div>
                    <select class="text-sm rounded-xl border border-slate-200 bg-white py-2 px-3.5 shadow-card focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        <option>All Modules</option>
                        <option>Dokyu</option>
                        <option>Tulong</option>
                        <option>Balita</option>
                        <option>User Management</option>
                        <option>System</option>
                    </select>
                </div>

                <x-ui.table>
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Personnel</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Action</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Module</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Timestamp</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($logs as $log)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-2.5 text-slate-700 font-medium whitespace-nowrap">{{ $log['user'] }}</td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $log['action'] }}</td>
                                <td class="px-4 py-2.5 hidden md:table-cell"><span class="text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">{{ $log['module'] }}</span></td>
                                <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell whitespace-nowrap">{{ $log['time'] }}</td>
                                <td class="px-4 py-2.5 text-slate-400 hidden lg:table-cell font-mono text-xs">{{ $log['ip'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </div>
        </div>

        </div>
    </div>
</x-layouts.admin>
