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
            async init() {
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

        <div class="bg-white border border-slate-100 rounded-2xl shadow-card p-3 mb-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input aria-label="Search" type="text" x-model="search" x-bind:placeholder="$store.lang.current === 'en' ? 'Search offices or barangays...' : 'Maghanap ng opisina o barangay...'" class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                </div>
                <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto shrink-0">
                    <button type="button" @click="tab = 'offices'" class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'offices' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <i data-lucide="building-2" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Opisina" en="Municipal Offices" />
                    </button>
                    <button type="button" @click="tab = 'barangays'" class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'barangays' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Barangay" en="Barangays" />
                    </button>
                </div>
            </div>
            <p class="text-[11px] text-slate-500" x-show="!loading && !failed" x-text="(tab === 'offices' ? filteredOffices.length + ' ' + ($store.lang.current === 'en' ? 'of ' + offices.length + ' offices' : 'sa ' + offices.length + ' opisina') : filteredBarangays.length + ' ' + ($store.lang.current === 'en' ? 'of ' + barangays.length + ' barangays' : 'sa ' + barangays.length + ' barangay'))"></p>
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
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard group">
                        <div class="h-20 bg-gradient-to-br flex items-center px-4 relative overflow-hidden" :class="office.color">
                            <i data-lucide="landmark" class="w-16 h-16 text-white/10 absolute -right-2 -bottom-3 rotate-12"></i>
                            <span class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur flex items-center justify-center text-white shrink-0 relative">
                                <i :data-lucide="office.icon" class="w-5 h-5"></i>
                            </span>
                        </div>
                        <div class="p-4">
                            <p class="text-sm font-semibold text-navy-900 leading-snug" x-text="office.name"></p>
                            <p class="text-xs text-slate-500 mt-0.5" x-text="office.head"></p>
                            <div class="flex items-center gap-2 mt-3.5 pt-3.5 border-t border-slate-100">
                                <a :href="'tel:' + office.contact.replace(/[^0-9+]/g, '')" class="flex-1 inline-flex items-center justify-center gap-1.5 text-[11px] font-medium text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-lg px-2.5 py-2 transition-colors">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i><span x-text="office.contact"></span>
                                </a>
                                <a :href="'mailto:' + office.email" class="w-9 h-9 shrink-0 inline-flex items-center justify-center text-slate-500 bg-slate-50 hover:bg-brand-50 hover:text-brand-600 rounded-lg transition-colors" :title="office.email">
                                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="flex flex-col items-center text-center py-12 bg-white border border-slate-100 rounded-2xl" x-show="filteredOffices.length === 0">
                <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang natagpuang opisina." en="No offices match your search." /></p>
            </div>
        </div>

        <!-- Barangays -->
        <div x-show="!loading && !failed && tab === 'barangays'" x-cloak>
            <!-- My Barangay -->
            <div x-show="$store.barangay.mine" x-cloak class="rounded-2xl bg-gradient-to-br from-brand-50 via-white to-purple-50/60 border border-brand-100 shadow-card p-4 sm:p-5 mb-4">
                <p class="text-[11px] font-semibold text-brand-600 uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="star" class="w-3.5 h-3.5"></i><x-ui.t fil="Aking Barangay" en="My Barangay" /></p>
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex items-center gap-3.5 flex-1 min-w-0">
                        <div class="relative w-14 h-14 shrink-0">
                            <div class="absolute inset-0 rounded-full bg-white border-2 border-gold-400 flex items-center justify-center text-slate-300"><i data-lucide="landmark" class="w-6 h-6"></i></div>
                            <img
                                :src="$store.barangay.mine?.seal ? '{{ rtrim(asset(''), '/') }}/' + $store.barangay.mine.seal : ''"
                                x-show="$store.barangay.mine?.seal" onerror="this.style.display='none'" alt=""
                                class="relative w-14 h-14 rounded-full object-cover border-2 border-gold-400"
                            >
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-navy-900 truncate"><x-ui.t fil="Barangay" en="Barangay" /> <span x-text="$store.barangay.mine?.label"></span></h3>
                            <p class="text-xs text-slate-500 mt-0.5 truncate"><x-ui.t fil="Punong Barangay:" en="Punong Barangay:" /> <span class="font-medium text-slate-700" x-text="$store.barangay.mine?.officials?.punong_barangay"></span></p>
                            <p class="text-xs text-slate-500 mt-0.5 truncate"><x-ui.t fil="Kalihim:" en="Barangay Secretary:" /> <span class="font-medium text-slate-700" x-text="$store.barangay.mine?.officials?.barangay_secretary"></span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            :href="'tel:' + ($store.barangay.mine?.contact || '').replace(/[^0-9+]/g, '')"
                            x-show="$store.barangay.mine?.contact"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl px-3.5 py-2.5 transition-colors"
                        ><i data-lucide="phone" class="w-3.5 h-3.5"></i><span x-text="$store.barangay.mine?.contact"></span></a>
                        <button
                            type="button"
                            @click="myBarangayProfile && openBarangayProfile(myBarangayProfile)"
                            x-show="myBarangayProfile?.history"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl px-3.5 py-2.5 transition-colors"
                        ><i data-lucide="book-open" class="w-3.5 h-3.5"></i><x-ui.t fil="Kasaysayan" en="History & Officials" /></button>
                        <x-ui.button :href="route('citizen.document-requests')" size="sm" icon="file-text"><x-ui.t fil="Humiling ng Dokyu" en="Request Documents" /></x-ui.button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="b in filteredBarangays" :key="b.name">
                    <button
                        type="button" @click="openBarangayProfile(b)"
                        class="text-left bg-white rounded-2xl border border-slate-100 shadow-card p-4 hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard flex items-center gap-3.5"
                    >
                        <div class="relative w-12 h-12 shrink-0">
                            <div class="absolute inset-0 rounded-full bg-gradient-to-br from-brand-50 to-purple-50 border-2 border-brand-100 flex items-center justify-center text-brand-400">
                                <i data-lucide="map-pinned" class="w-5 h-5"></i>
                            </div>
                            <img
                                :src="b.seal ? '{{ rtrim(asset(''), '/') }}/' + b.seal : ''"
                                x-show="b.seal" onerror="this.style.display='none'" alt=""
                                class="relative w-12 h-12 rounded-full object-cover border-2 border-brand-100"
                            >
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-navy-900 truncate" x-text="'Brgy. ' + b.name"></p>
                            <p class="text-xs text-slate-500 truncate" x-text="b.captain"></p>
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 mt-1">
                                <i data-lucide="phone" class="w-3 h-3"></i><span x-text="b.contact"></span>
                            </span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 shrink-0"></i>
                    </button>
                </template>
            </div>
            <div class="flex flex-col items-center text-center py-12 bg-white border border-slate-100 rounded-2xl" x-show="filteredBarangays.length === 0">
                <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
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

                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="relative w-11 h-11 shrink-0">
                            <div class="absolute inset-0 rounded-full bg-gradient-to-br from-brand-50 to-purple-50 border-2 border-brand-100 flex items-center justify-center text-brand-400"><i data-lucide="map-pinned" class="w-5 h-5"></i></div>
                            <img :src="selectedBarangayProfile?.seal ? '{{ rtrim(asset(''), '/') }}/' + selectedBarangayProfile.seal : ''" x-show="selectedBarangayProfile?.seal" onerror="this.style.display='none'" alt="" class="relative w-11 h-11 rounded-full object-cover border-2 border-brand-100">
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-slate-800 truncate" x-text="'Barangay ' + (selectedBarangayProfile?.name ?? '')"></h3>
                            <p class="text-xs text-slate-500 truncate" x-text="selectedBarangayProfile?.captain"></p>
                        </div>
                    </div>
                    <button @click="showBarangayProfile = false" class="p-2 rounded-xl text-slate-500 hover:text-slate-600 hover:bg-slate-100 transition-colors shrink-0"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <template x-if="!selectedBarangayProfile?.history && !selectedBarangayProfile?.officialsRoster">
                    <div class="flex flex-col items-center text-center py-14 px-6">
                        <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mb-4"><i data-lucide="book-open" class="w-7 h-7"></i></span>
                        <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Wala pang detalyadong profile para sa barangay na ito." en="A detailed profile isn't available for this barangay yet." /></p>
                        <p class="text-xs text-slate-500 mt-1 max-w-xs"><x-ui.t fil="Ang kasaysayan at listahan ng opisyal ay unti-unting idadagdag." en="History and the officials roster are being added barangay by barangay." /></p>
                    </div>
                </template>

                <template x-if="selectedBarangayProfile?.history || selectedBarangayProfile?.officialsRoster">
                    <div>
                        <div class="flex items-center gap-1.5 px-6 pt-4">
                            <button type="button" @click="profileTab = 'history'" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors" :class="profileTab === 'history' ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50'">History</button>
                            <button type="button" @click="profileTab = 'officials'" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors" :class="profileTab === 'officials' ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50'">Officials &amp; Staff</button>
                        </div>

                        <!-- History Tab -->
                        <div x-show="profileTab === 'history'" class="p-6 pt-4 space-y-5 max-h-[65vh] overflow-y-auto scrollbar-thin">
                            <div>
                                <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide mb-1.5 flex items-center gap-1.5"><i data-lucide="sprout" class="w-3.5 h-3.5 text-brand-500"></i>Founding</p>
                                <p class="text-sm text-slate-600 leading-relaxed" x-text="selectedBarangayProfile?.history?.founding"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide mb-1.5 flex items-center gap-1.5"><i data-lucide="signpost" class="w-3.5 h-3.5 text-brand-500"></i>Renaming to its Present Name</p>
                                <p class="text-sm text-slate-600 leading-relaxed" x-text="selectedBarangayProfile?.history?.renaming"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide mb-1.5 flex items-center gap-1.5"><i data-lucide="house" class="w-3.5 h-3.5 text-brand-500"></i>Barangay Today</p>
                                <p class="text-sm text-slate-600 leading-relaxed" x-text="selectedBarangayProfile?.history?.today"></p>
                            </div>

                            <div x-show="(selectedBarangayProfile?.history?.leadership || []).length">
                                <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide mb-2 flex items-center gap-1.5"><i data-lucide="landmark" class="w-3.5 h-3.5 text-brand-500"></i>Leadership Timeline</p>
                                <div class="space-y-2.5 relative pl-4">
                                    <div class="absolute left-[3px] top-1 bottom-1 w-px bg-slate-200"></div>
                                    <template x-for="(l, li) in (selectedBarangayProfile?.history?.leadership || [])" :key="li">
                                        <div class="relative">
                                            <span class="absolute -left-4 top-1.5 w-2 h-2 rounded-full bg-brand-400"></span>
                                            <p class="text-sm font-medium text-slate-700" x-text="l.name"></p>
                                            <p class="text-xs text-slate-500" x-text="l.title + ' — ' + l.term"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div x-show="(selectedBarangayProfile?.history?.landmarks || []).length">
                                <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide mb-2 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-brand-500"></i>Landmarks &amp; Points of Interest</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <template x-for="(lm, li) in (selectedBarangayProfile?.history?.landmarks || [])" :key="li">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                                            <p class="text-xs font-semibold text-slate-700" x-text="lm.name"></p>
                                            <p class="text-[11px] text-slate-500 mt-0.5" x-text="lm.location"></p>
                                            <p class="text-[11px] text-slate-500 mt-1" x-text="lm.note"></p>
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
                                            onerror="this.style.display='none'"
                                            :alt="o.name ? 'Photograph of ' + o.name : ''"
                                            class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm shrink-0"
                                        >
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-800 truncate" x-text="o.name"></p>
                                            <p class="text-xs text-brand-600 font-medium truncate" x-text="o.position"></p>
                                            <p class="text-[11px] text-slate-500 truncate" x-text="o.committee"></p>
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
