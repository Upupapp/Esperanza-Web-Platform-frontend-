<x-layouts.citizen title="Government Directory" subtitle="Contact details for municipal and barangay offices." active="directory">
    <div
        x-data="{
            tab: 'offices',
            search: '',
            offices: [],
            barangays: [],
            loading: true,
            failed: false,
            showBarangayProfile: false,
            selectedBarangayProfile: null,
            profileTab: 'history',
            iconPalette: [
                { icon: 'landmark', color: 'from-navy-600 to-navy-800' },
                { icon: 'scale', color: 'from-brand-500 to-brand-700' },
                { icon: 'heart-pulse', color: 'from-rose-500 to-rose-700' },
                { icon: 'shield-check', color: 'from-emerald-500 to-emerald-700' },
                { icon: 'briefcase', color: 'from-purple-500 to-purple-700' },
                { icon: 'users', color: 'from-amber-500 to-amber-700' },
            ],
            // Emergency (2026-10-17): the hotlines and the evacuation centres,
            // read when the tab is first opened (GET /hotlines, /sakuna/centers).
            hotlines: [],
            centres: [],
            emergencyLoaded: false,
            emergencyLoading: false,
            emergencyFailed: false,
            async loadEmergency() {
                if (this.emergencyLoaded || this.emergencyLoading) return;
                this.emergencyLoading = true;
                this.emergencyFailed = false;
                try {
                    const [hotlines, centres] = await Promise.all([
                        $api.get('/hotlines'),
                        $api.get('/sakuna/centers', { params: { per_page: 100 } }),
                    ]);
                    this.hotlines = hotlines.data || [];
                    this.centres = centres.data || [];
                    this.emergencyLoaded = true;
                } catch {
                    this.emergencyFailed = true;
                } finally {
                    this.emergencyLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
            telOf(n) { return 'tel:' + String(n || '').replace(/[^0-9+]/g, ''); },
            // A centre takes evacuees while it is open (Processing); otherwise it stands by.
            centreOpen(c) { return c.status === 'Processing' || c.status === 'Open'; },
            centreLabel(c) {
                const en = this.$store.lang.current === 'en';
                return this.centreOpen(c) ? (en ? 'Open' : 'Bukas') : (en ? 'On standby' : 'Nakahanda');
            },
            get filteredHotlines() {
                const q = this.search.trim().toLowerCase();
                return this.hotlines.filter(h => q === '' || [h.office, h.position, h.contact].some(v => String(v || '').toLowerCase().includes(q)));
            },
            get filteredCentres() {
                const q = this.search.trim().toLowerCase();
                const mine = this.$store.barangay.mine?.label;
                return this.centres.filter(c => q === '' || [c.name, c.barangay, c.address].some(v => String(v || '').toLowerCase().includes(q)))
                    .sort((a, b) => (b.barangay === mine) - (a.barangay === mine));
            },
            async init() {
                if (new URLSearchParams(window.location.search).get('tab') === 'emergency') { this.tab = 'emergency'; this.loadEmergency(); }
                this.loading = true;
                this.failed = false;
                try {
                    const [offices, barangays] = await Promise.all([
                        $api.get('/directory'),
                        $api.get('/barangays'),
                    ]);
                    this.offices = (offices.data || []).map((o, i) => ({
                        name: o.office,
                        head: o.official_name || '',
                        contact: o.contact || '',
                        email: o.email || '',
                        icon: this.iconPalette[i % this.iconPalette.length].icon,
                        color: this.iconPalette[i % this.iconPalette.length].color,
                    }));
                    this.barangays = (barangays.data || []).map((b) => ({
                        name: b.name,
                        captain: b.punong_barangay || '',
                        secretary: b.barangay_secretary || '',
                        contact: b.contact,
                        seal: b.seal_path,
                        history: b.history,
                        officialsRoster: b.officials_roster,
                    })).sort((a, b) => a.name.localeCompare(b.name));
                } catch {
                    this.failed = true;
                } finally {
                    this.loading = false;
                }
            },
            openBarangayProfile(b) {
                this.selectedBarangayProfile = b;
                this.profileTab = 'history';
                this.showBarangayProfile = true;
                this.$nextTick(() => window.renderIcons?.());
            },
            get myBarangayProfile() {
                return this.barangays.find(b => b.name === this.$store.barangay.mine?.label) || null;
            },
            get filteredOffices() {
                const q = this.search.trim().toLowerCase();
                return this.offices.filter(o => q === '' || o.name.toLowerCase().includes(q) || o.head.toLowerCase().includes(q));
            },
            get filteredBarangays() {
                const q = this.search.trim().toLowerCase();
                return this.barangays.filter(b => q === '' || b.name.toLowerCase().includes(q) || b.captain.toLowerCase().includes(q));
            },
        }"
        class="animate-fade-up"
    >

        <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input aria-label="Search" type="text" x-model="search" x-bind:placeholder="$store.lang.current === 'en' ? 'Search offices or barangays...' : 'Maghanap ng opisina o barangay...'" class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                </div>
                <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit overflow-x-auto shrink-0">
                    <button type="button" @click="tab = 'offices'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'offices' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                        <i data-lucide="building-2" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Opisina" en="Municipal Offices" />
                    </button>
                    <button type="button" @click="tab = 'barangays'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'barangays' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Barangay" en="Barangays" />
                    </button>
                    <button type="button" @click="tab = 'emergency'; loadEmergency()" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'emergency' ? 'bg-white text-rose-700 shadow-sm' : 'text-ink-muted hover:text-slate-700'" data-testid="directory-emergency-tab">
                        <i data-lucide="siren" class="w-3.5 h-3.5"></i><x-ui.t fil="Emergency" en="Emergency" />
                    </button>
                </div>
            </div>
            <p class="text-[11px] text-ink-muted" x-show="!loading && !failed && tab !== 'emergency'" x-text="(tab === 'offices' ? filteredOffices.length + ' ' + ($store.lang.current === 'en' ? 'of ' + offices.length + ' offices' : 'sa ' + offices.length + ' opisina') : filteredBarangays.length + ' ' + ($store.lang.current === 'en' ? 'of ' + barangays.length + ' barangays' : 'sa ' + barangays.length + ' barangay'))"></p>
        </div>

        <div x-show="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <x-ui.skeleton class="h-40 rounded-2xl" />
            <x-ui.skeleton class="h-40 rounded-2xl opacity-80" />
            <x-ui.skeleton class="h-40 rounded-2xl opacity-60" />
        </div>

        <div x-show="failed" x-cloak>
            <x-ui.error-state messageExpr="($store.lang.current === 'en' ? 'Could not load the directory.' : 'Hindi ma-load ang direktoryo.')" retryExpr="init()" :retryable="true" />
        </div>

        <!-- Municipal Offices -->
        <div x-show="!loading && !failed && tab === 'offices'">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="office in filteredOffices" :key="office.name">
                    <div class="surface-glass rounded-[26px] overflow-hidden hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard group">
                        <div class="h-20 bg-gradient-to-br flex items-center px-4 relative overflow-hidden" :class="office.color">
                            <i data-lucide="landmark" class="w-16 h-16 text-white/10 absolute -right-2 -bottom-3 rotate-12"></i>
                            <span class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur flex items-center justify-center text-white shrink-0 relative">
                                <i :data-lucide="office.icon" class="w-5 h-5"></i>
                            </span>
                        </div>
                        <div class="p-4">
                            <p class="text-sm font-semibold text-ink leading-snug" x-text="office.name"></p>
                            <p class="text-xs text-ink-muted mt-0.5" x-text="office.head"></p>
                            <div class="flex items-center gap-2 mt-3.5 pt-3.5 border-t border-brand-600/10">
                                <a :href="'tel:' + office.contact.replace(/[^0-9+]/g, '')" class="flex-1 inline-flex items-center justify-center gap-1.5 text-[11px] font-medium text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-xl px-2.5 py-2 transition-colors">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i><span x-text="office.contact"></span>
                                </a>
                                <a :href="'mailto:' + office.email" class="w-9 h-9 shrink-0 inline-flex items-center justify-center text-ink-muted bg-slate-50 hover:bg-brand-50 hover:text-brand-600 rounded-xl transition-colors" :title="office.email">
                                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="flex flex-col items-center text-center py-12 bg-white border border-brand-600/10 rounded-2xl" x-show="filteredOffices.length === 0">
                <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang natagpuang opisina." en="No offices match your search." /></p>
            </div>
        </div>

        <!-- Emergency (2026-10-17): hotlines and evacuation centres, with the
             way to the Emergency page (alerts and incident reports). -->
        <div x-show="tab === 'emergency'" x-cloak class="space-y-4" data-testid="directory-emergency">
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-rose-800"><x-ui.t fil="Sa panganib ngayon? Tumawag sa 911." en="In danger now? Call 911." /></p>
                    <p class="text-xs text-rose-900/80 mt-0.5"><x-ui.t fil="Para sa baha, sunog, pagguho o aksidente sa inyong lugar, mag-ulat ng insidente para makita ito ng MDRRMO." en="For flooding, fire, a landslide or an accident near you, report an incident so the MDRRMO sees it." /></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="tel:911" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 text-white px-3.5 py-2 text-xs font-semibold hover:bg-rose-700"><i data-lucide="phone-call" class="w-3.5 h-3.5"></i>911</a>
                    <a href="{{ route('citizen.emergency') }}?report=1" class="inline-flex items-center gap-1.5 rounded-xl bg-white border border-rose-300 text-rose-800 px-3.5 py-2 text-xs font-semibold hover:bg-rose-100"><i data-lucide="flag" class="w-3.5 h-3.5"></i><x-ui.t fil="Mag-ulat ng insidente" en="Report an incident" /></a>
                    <a href="{{ route('citizen.emergency') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 px-3.5 py-2 text-xs font-semibold hover:bg-slate-50"><i data-lucide="megaphone" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga babala" en="Alerts" /></a>
                </div>
            </div>

            <div x-show="emergencyLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-3" aria-busy="true"><x-ui.skeleton class="h-20 rounded-2xl" /><x-ui.skeleton class="h-20 rounded-2xl opacity-80" /></div>
            <div x-show="emergencyFailed" x-cloak>
                <x-ui.error-state messageExpr="($store.lang.current === 'en' ? 'Could not load the hotlines and evacuation centres.' : 'Hindi ma-load ang mga hotline at evacuation center.')" retryExpr="loadEmergency()" :retryable="true" />
            </div>

            <section x-show="emergencyLoaded" aria-labelledby="dir-hotlines">
                <h2 id="dir-hotlines" class="text-sm font-bold text-ink flex items-center gap-2 mb-2"><i data-lucide="phone" class="w-4 h-4 text-rose-600"></i><x-ui.t fil="Mga Emergency Hotline" en="Emergency Hotlines" /></h2>
                <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <template x-for="h in filteredHotlines" :key="h.office + h.contact">
                        <li class="surface-glass rounded-2xl p-3.5 flex items-center gap-3">
                            <span class="w-10 h-10 shrink-0 rounded-xl bg-rose-50 text-rose-600 grid place-items-center" aria-hidden="true"><i data-lucide="siren" class="w-5 h-5"></i></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-ink leading-snug" x-text="h.office"></p>
                                <p class="text-[11px] text-ink-muted" x-text="h.position"></p>
                            </div>
                            <a :href="telOf(h.contact)" class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-rose-600 text-white px-3 py-2 text-xs font-semibold hover:bg-rose-700" :aria-label="($store.lang.current === 'en' ? 'Call ' : 'Tawagan ang ') + h.office + ', ' + h.contact"><i data-lucide="phone" class="w-3.5 h-3.5"></i><span x-text="h.contact"></span></a>
                        </li>
                    </template>
                </ul>
                <p x-show="!filteredHotlines.length" class="text-xs text-ink-muted"><x-ui.t fil="Walang hotline na tumutugma." en="No hotlines match." /></p>
            </section>

            <section x-show="emergencyLoaded" aria-labelledby="dir-centres">
                <h2 id="dir-centres" class="text-sm font-bold text-ink flex items-center gap-2 mb-2"><i data-lucide="house" class="w-4 h-4 text-brand-600"></i><x-ui.t fil="Mga Evacuation Center" en="Evacuation Centres" /></h2>
                <p class="text-xs text-ink-muted mb-2"><x-ui.t fil="Alamin na ngayon kung saan ang pinakamalapit sa iyo. Ang nasa iyong barangay ang nauuna." en="Know now where your nearest one is. Those in your barangay come first." /></p>
                <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <template x-for="c in filteredCentres" :key="c.ref">
                        <li class="surface-glass rounded-2xl p-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-ink leading-snug" x-text="c.name"></p>
                                <span class="shrink-0 text-[10.5px] font-semibold rounded-full px-2 py-0.5 ring-1 ring-inset" :class="centreOpen(c) ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200'" x-text="centreLabel(c)"></span>
                            </div>
                            <p class="text-xs text-ink-muted mt-0.5" x-text="'Brgy. ' + c.barangay + (c.address ? ' · ' + c.address : '')"></p>
                            <a :href="'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(c.name + ', ' + (c.address || ('Brgy. ' + c.barangay)) + ', Esperanza, Masbate')" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"><i data-lucide="navigation" class="w-3.5 h-3.5"></i><x-ui.t fil="Direksyon" en="Directions" /></a>
                        </li>
                    </template>
                </ul>
                <p x-show="!filteredCentres.length" class="text-xs text-ink-muted"><x-ui.t fil="Walang nakalistang evacuation center sa ngayon. Itanong sa iyong barangay o sa MDRRMO." en="No evacuation centres are listed right now. Ask your barangay or the MDRRMO." /></p>
            </section>
        </div>

        <!-- Barangays -->
        <div x-show="!loading && !failed && tab === 'barangays'" x-cloak>
            <!-- My Barangay -->
            <div x-show="$store.barangay.mine" x-cloak class="rounded-2xl bg-gradient-to-br from-brand-50 via-white to-purple-50/60 border border-brand-100 shadow-card p-4 sm:p-5 mb-4">
                <p class="text-[11px] font-semibold text-brand-600 uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="star" class="w-3.5 h-3.5"></i><x-ui.t fil="Barangay" en="Barangay" /></p>
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex items-center gap-3.5 flex-1 min-w-0">
                        <div class="relative w-14 h-14 shrink-0">
                            <div class="absolute inset-0 rounded-full bg-white border-2 border-gold-400 flex items-center justify-center text-ink-faint"><i data-lucide="landmark" class="w-6 h-6"></i></div>
                            <img
                                :src="$store.barangay.mine?.seal ? '{{ rtrim(asset(''), '/') }}/' + $store.barangay.mine.seal : ''"
                                x-show="$store.barangay.mine?.seal" x-on:error="$el.style.display='none'" alt=""
                                class="relative w-14 h-14 rounded-full object-cover border-2 border-gold-400"
                            >
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-ink truncate"><x-ui.t fil="Barangay" en="Barangay" /> <span x-text="$store.barangay.mine?.label"></span></h3>
                            <p class="text-xs text-ink-muted mt-0.5 truncate"><x-ui.t fil="Punong Barangay:" en="Punong Barangay:" /> <span class="font-medium text-slate-700" x-text="$store.barangay.mine?.officials?.punong_barangay"></span></p>
                            <p class="text-xs text-ink-muted mt-0.5 truncate"><x-ui.t fil="Kalihim:" en="Barangay Secretary:" /> <span class="font-medium text-slate-700" x-text="$store.barangay.mine?.officials?.barangay_secretary"></span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            :href="'tel:' + ($store.barangay.mine?.contact || '').replace(/[^0-9+]/g, '')"
                            x-show="$store.barangay.mine?.contact"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-white/60 rounded-2xl px-3.5 py-2.5 transition-colors"
                        ><i data-lucide="phone" class="w-3.5 h-3.5"></i><span x-text="$store.barangay.mine?.contact"></span></a>
                        <button
                            type="button"
                            @click="myBarangayProfile && openBarangayProfile(myBarangayProfile)"
                            x-show="myBarangayProfile?.history"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-white/60 rounded-2xl px-3.5 py-2.5 transition-colors"
                        ><i data-lucide="book-open" class="w-3.5 h-3.5"></i><x-ui.t fil="Kasaysayan" en="History & Officials" /></button>
                        <x-ui.button :href="route('citizen.document-requests')" size="sm" icon="file-text"><x-ui.t fil="Humiling ng Dokyu" en="Request Documents" /></x-ui.button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="b in filteredBarangays" :key="b.name">
                    <button
                        type="button" @click="openBarangayProfile(b)"
                        class="text-left surface-glass rounded-[26px] p-4 hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard flex items-center gap-3.5"
                    >
                        <div class="relative w-12 h-12 shrink-0">
                            <div class="absolute inset-0 rounded-full bg-gradient-to-br from-brand-50 to-purple-50 border-2 border-brand-100 flex items-center justify-center text-brand-400">
                                <i data-lucide="map-pinned" class="w-5 h-5"></i>
                            </div>
                            <img
                                :src="b.seal ? '{{ rtrim(asset(''), '/') }}/' + b.seal : ''"
                                x-show="b.seal" x-on:error="$el.style.display='none'" alt=""
                                class="relative w-12 h-12 rounded-full object-cover border-2 border-brand-100"
                            >
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink truncate" x-text="'Brgy. ' + b.name"></p>
                            <p class="text-xs text-ink-muted truncate" x-text="b.captain"></p>
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 mt-1">
                                <i data-lucide="phone" class="w-3 h-3"></i><span x-text="b.contact"></span>
                            </span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-ink-faint shrink-0"></i>
                    </button>
                </template>
            </div>
            <div class="flex flex-col items-center text-center py-12 bg-white border border-brand-600/10 rounded-2xl" x-show="filteredBarangays.length === 0">
                <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang natagpuang barangay." en="No barangays match your search." /></p>
            </div>
        </div>

        <!-- Barangay Profile Modal — History & Officials & Staff -->
        <div x-show="showBarangayProfile" x-cloak
            x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
            @click.self="showBarangayProfile = false"
        x-trap="showBarangayProfile"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showBarangayProfile = false">
            <div x-show="showBarangayProfile" x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                class="bg-white rounded-2xl shadow-float w-full max-w-2xl mb-8">

                <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="relative w-11 h-11 shrink-0">
                            <div class="absolute inset-0 rounded-full bg-gradient-to-br from-brand-50 to-purple-50 border-2 border-brand-100 flex items-center justify-center text-brand-400"><i data-lucide="map-pinned" class="w-5 h-5"></i></div>
                            <img :src="selectedBarangayProfile?.seal ? '{{ rtrim(asset(''), '/') }}/' + selectedBarangayProfile.seal : ''" x-show="selectedBarangayProfile?.seal" x-on:error="$el.style.display='none'" alt="" class="relative w-11 h-11 rounded-full object-cover border-2 border-brand-100">
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-ink-body truncate" x-text="'Barangay ' + (selectedBarangayProfile?.name ?? '')"></h3>
                            <p class="text-xs text-ink-muted truncate" x-text="selectedBarangayProfile?.captain"></p>
                        </div>
                    </div>
                    <button aria-label="Close" @click="showBarangayProfile = false" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors shrink-0"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <template x-if="!selectedBarangayProfile?.history && !selectedBarangayProfile?.officialsRoster">
                    <div class="flex flex-col items-center text-center py-14 px-6">
                        <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="book-open" class="w-7 h-7"></i></span>
                        <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Wala pang detalyadong profile para sa barangay na ito." en="A detailed profile isn't available for this barangay yet." /></p>
                        <p class="text-xs text-ink-muted mt-1 max-w-xs"><x-ui.t fil="Ang kasaysayan at listahan ng opisyal ay unti-unting idadagdag." en="History and the officials roster are being added barangay by barangay." /></p>
                    </div>
                </template>

                <template x-if="selectedBarangayProfile?.history || selectedBarangayProfile?.officialsRoster">
                    <div>
                        <div class="flex items-center gap-1.5 px-6 pt-4">
                            <button type="button" @click="profileTab = 'history'" class="px-3 py-1.5 text-xs font-medium rounded-xl transition-colors" :class="profileTab === 'history' ? 'bg-brand-50 text-brand-700' : 'text-ink-muted hover:bg-white/60'">History</button>
                            <button type="button" @click="profileTab = 'officials'" class="px-3 py-1.5 text-xs font-medium rounded-xl transition-colors" :class="profileTab === 'officials' ? 'bg-brand-50 text-brand-700' : 'text-ink-muted hover:bg-white/60'">Officials &amp; Staff</button>
                        </div>

                        <!-- History Tab -->
                        <div x-show="profileTab === 'history'" class="p-6 pt-4 space-y-5 max-h-[65vh] overflow-y-auto scrollbar-thin">
                            <div>
                                <p class="text-xs font-semibold text-ink uppercase tracking-wide mb-1.5 flex items-center gap-1.5"><i data-lucide="sprout" class="w-3.5 h-3.5 text-brand-500"></i>Founding</p>
                                <p class="text-sm text-slate-600 leading-relaxed" x-text="selectedBarangayProfile?.history?.founding"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-ink uppercase tracking-wide mb-1.5 flex items-center gap-1.5"><i data-lucide="signpost" class="w-3.5 h-3.5 text-brand-500"></i>Renaming to its Present Name</p>
                                <p class="text-sm text-slate-600 leading-relaxed" x-text="selectedBarangayProfile?.history?.renaming"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-ink uppercase tracking-wide mb-1.5 flex items-center gap-1.5"><i data-lucide="house" class="w-3.5 h-3.5 text-brand-500"></i>Barangay Today</p>
                                <p class="text-sm text-slate-600 leading-relaxed" x-text="selectedBarangayProfile?.history?.today"></p>
                            </div>

                            <div x-show="(selectedBarangayProfile?.history?.leadership || []).length">
                                <p class="text-xs font-semibold text-ink uppercase tracking-wide mb-2 flex items-center gap-1.5"><i data-lucide="landmark" class="w-3.5 h-3.5 text-brand-500"></i>Leadership Timeline</p>
                                <div class="space-y-2.5 relative pl-4">
                                    <div class="absolute left-[3px] top-1 bottom-1 w-px bg-slate-200"></div>
                                    <template x-for="(l, li) in (selectedBarangayProfile?.history?.leadership || [])" :key="li">
                                        <div class="relative">
                                            <span class="absolute -left-4 top-1.5 w-2 h-2 rounded-full bg-brand-400"></span>
                                            <p class="text-sm font-medium text-slate-700" x-text="l.name"></p>
                                            <p class="text-xs text-ink-muted" x-text="l.title + ' — ' + l.term"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div x-show="(selectedBarangayProfile?.history?.landmarks || []).length">
                                <p class="text-xs font-semibold text-ink uppercase tracking-wide mb-2 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-brand-500"></i>Landmarks &amp; Points of Interest</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <template x-for="(lm, li) in (selectedBarangayProfile?.history?.landmarks || [])" :key="li">
                                        <div class="rounded-xl border-0 surface-pressed p-3">
                                            <p class="text-xs font-semibold text-slate-700" x-text="lm.name"></p>
                                            <p class="text-[11px] text-ink-muted mt-0.5" x-text="lm.location"></p>
                                            <p class="text-[11px] text-ink-muted mt-1" x-text="lm.note"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Officials & Staff Tab -->
                        <div x-show="profileTab === 'officials'" x-cloak class="p-6 pt-4 max-h-[65vh] overflow-y-auto scrollbar-thin">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <template x-for="(o, oi) in (selectedBarangayProfile?.officialsRoster || [])" :key="oi">
                                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/40 p-3">
                                        <img
                                            :src="o.photo ? '{{ rtrim(asset(''), '/') }}/' + o.photo : ''"
                                            x-on:error="$el.style.display='none'"
                                            :alt="o.name ? 'Photograph of ' + o.name : ''"
                                            class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm shrink-0"
                                        >
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-ink-body truncate" x-text="o.name"></p>
                                            <p class="text-xs text-brand-600 font-medium truncate" x-text="o.position"></p>
                                            <p class="text-[11px] text-ink-muted truncate" x-text="o.committee"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-layouts.citizen>
