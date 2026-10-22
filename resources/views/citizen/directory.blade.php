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
            allLeaders: false,
            showStory: false,
            person: null,
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
                this.allLeaders = false;
                this.showStory = false;
                this.person = null;
                this.showBarangayProfile = true;
                this.$nextTick(() => window.renderIcons?.());
            },
            // Past leaders, newest first.
            get profileLeaders() {
                return [...(this.selectedBarangayProfile?.history?.leadership || [])].reverse();
            },
            isCurrentTerm(l) {
                return /present/i.test(l?.term || '');
            },
            officialRole(o) {
                const p = (o?.position || '').toLowerCase();
                return p.includes('punong barangay') ? 'captain' : p.includes('kagawad') ? 'kagawad' : 'officer';
            },
            rosterGroup(role) {
                return (this.selectedBarangayProfile?.officialsRoster || []).filter((o) => this.officialRole(o) === role);
            },
            plainName(name) {
                return String(name || '').replace(/^\s*Hon\.\s*/, '').trim();
            },
            officialInitials(name) {
                return this.plainName(name).replace(/^Ma\.\s*/, '').split(/\s+/)
                    .filter((w) => w && !w.endsWith('.')).slice(0, 2).map((w) => w[0].toUpperCase()).join('');
            },
            officialAreaLabel(o) {
                const en = this.$store.lang.current === 'en';
                return { captain: en ? 'Also chairs' : 'Pinamumunuan din', officer: en ? 'Handles' : 'Hawak', kagawad: en ? 'Committee' : 'Komite' }[this.officialRole(o)];
            },
            // The committee, without a '(Former Punong Barangay, ...)' note that Also served already says.
            officialCommittee(o) {
                const c = o?.committee || '';
                return this.officialAlsoServed(o) ? c.replace(/\s*\(Former [^)]*\)/, '') : c;
            },
            // An earlier term in the leadership history, if this official had one.
            officialAlsoServed(o) {
                if (!o) return '';
                const me = this.plainName(o.name).toLowerCase();
                return (this.selectedBarangayProfile?.history?.leadership || [])
                    .filter((l) => this.plainName(l.name).toLowerCase() === me && !this.isCurrentTerm(l))
                    .map((l) => [l.title, l.term].filter(Boolean).join(', ')).join('; ');
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
                </div>
            </div>
            <p class="text-[11px] text-ink-muted" x-show="!loading && !failed" x-text="(tab === 'offices' ? filteredOffices.length + ' ' + ($store.lang.current === 'en' ? 'of ' + offices.length + ' offices' : 'sa ' + offices.length + ' opisina') : filteredBarangays.length + ' ' + ($store.lang.current === 'en' ? 'of ' + barangays.length + ' barangays' : 'sa ' + barangays.length + ' barangay'))"></p>
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

        <!-- Barangay Profile Modal (redesign, 2026-10-22; the app's barangay screen is the same):
             History - facts, the name, livelihood, leadership, places, the full text on demand -
             and Officials & Staff - the Punong Barangay, officers, kagawad by committee, each
             opening a profile with the official photo. A part the LGU has not written is left out. -->
        <div x-show="showBarangayProfile" x-cloak
            x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
            @click.self="showBarangayProfile = false"
        x-trap="showBarangayProfile && !person"
        role="dialog"
        aria-modal="true"
        aria-labelledby="barangay-profile-title"
        @keydown.escape.window="person ? person = null : showBarangayProfile = false">
            <div x-show="showBarangayProfile" x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                class="relative bg-white rounded-[20px] shadow-float w-full max-w-3xl mb-8 overflow-hidden">

                <div class="flex items-center gap-4 px-5 sm:px-7 py-5 border-b border-slate-200">
                    <div class="relative w-[52px] h-[52px] shrink-0">
                        <div class="absolute inset-0 rounded-full bg-brand-50 border-2 border-brand-100 flex items-center justify-center text-brand-500"><i data-lucide="map-pinned" class="w-5 h-5"></i></div>
                        <img :src="selectedBarangayProfile?.seal ? '{{ rtrim(asset(''), '/') }}/' + selectedBarangayProfile.seal : ''" x-show="selectedBarangayProfile?.seal" x-on:error="$el.style.display='none'" alt="" class="relative w-[52px] h-[52px] rounded-full object-cover border-2 border-brand-100">
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 id="barangay-profile-title" class="text-xl font-bold text-ink tracking-tight truncate" x-text="'Barangay ' + (selectedBarangayProfile?.name ?? '')"></h3>
                        <p class="text-sm text-ink-muted truncate" x-text="selectedBarangayProfile?.history?.summary || (selectedBarangayProfile?.captain ? 'Punong Barangay: ' + selectedBarangayProfile.captain : '')"></p>
                    </div>
                    <button aria-label="Close" @click="showBarangayProfile = false" class="w-11 h-11 rounded-xl flex items-center justify-center text-ink-muted hover:text-slate-700 hover:bg-slate-100 transition-colors shrink-0"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <template x-if="!selectedBarangayProfile?.history && !(selectedBarangayProfile?.officialsRoster || []).length">
                    <div class="flex flex-col items-center text-center py-14 px-6">
                        <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="book-open" class="w-7 h-7"></i></span>
                        <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Wala pang detalyadong profile para sa barangay na ito." en="A detailed profile isn't available for this barangay yet." /></p>
                        <p class="text-xs text-ink-muted mt-1 max-w-xs"><x-ui.t fil="Ang kasaysayan at listahan ng opisyal ay unti-unting idadagdag." en="History and the officials roster are being added barangay by barangay." /></p>
                    </div>
                </template>

                <template x-if="selectedBarangayProfile?.history || (selectedBarangayProfile?.officialsRoster || []).length">
                    <div>
                        <div class="flex gap-1.5 px-5 sm:px-7 pt-4" role="tablist">
                            <button type="button" role="tab" :aria-selected="profileTab === 'history'" @click="profileTab = 'history'" class="min-h-[44px] px-[18px] rounded-full text-sm font-semibold transition-colors" :class="profileTab === 'history' ? 'bg-brand-50 text-brand-700' : 'text-ink-muted hover:bg-slate-50'"><x-ui.t fil="Kasaysayan" en="History" /></button>
                            <button type="button" role="tab" :aria-selected="profileTab === 'officials'" @click="profileTab = 'officials'" class="min-h-[44px] px-[18px] rounded-full text-sm font-semibold transition-colors" :class="profileTab === 'officials' ? 'bg-brand-50 text-brand-700' : 'text-ink-muted hover:bg-slate-50'"><x-ui.t fil="Mga Opisyal at Kawani" en="Officials &amp; Staff" /></button>
                        </div>

                        <!-- History -->
                        <div x-show="profileTab === 'history'" class="px-5 sm:px-7 pt-6 pb-8 space-y-7 max-h-[70vh] overflow-y-auto scrollbar-thin">
                            <p x-show="!selectedBarangayProfile?.history" class="text-sm text-ink-muted text-center py-8"><x-ui.t fil="Wala pang naidaragdag na kasaysayan ng barangay na ito." en="The history of this barangay has not been added yet." /></p>

                            <div x-show="(selectedBarangayProfile?.history?.facts || []).length" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <template x-for="(f, fi) in (selectedBarangayProfile?.history?.facts || [])" :key="fi">
                                    <div class="rounded-[14px] bg-slate-50 px-4 py-3.5 flex flex-col gap-1">
                                        <span class="text-[22px] font-bold text-brand-600" x-text="f.value"></span>
                                        <span class="text-[13px] text-ink-muted" x-text="f.label"></span>
                                    </div>
                                </template>
                            </div>

                            <div x-show="selectedBarangayProfile?.history?.name_origin || (selectedBarangayProfile?.history?.livelihood?.items || []).length" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div x-show="selectedBarangayProfile?.history?.name_origin" class="rounded-[14px] border border-slate-200 p-[18px] flex flex-col gap-2">
                                    <span class="text-xs font-semibold tracking-[0.06em] uppercase text-ink-muted"><x-ui.t fil="Ang pangalan" en="The name" /></span>
                                    <span class="text-[17px] font-bold text-ink" x-text="selectedBarangayProfile?.history?.name_origin?.title"></span>
                                    <span class="text-sm leading-normal text-slate-600" x-text="selectedBarangayProfile?.history?.name_origin?.text"></span>
                                </div>
                                <div x-show="(selectedBarangayProfile?.history?.livelihood?.items || []).length" class="rounded-[14px] border border-slate-200 p-[18px] flex flex-col gap-2.5">
                                    <span class="text-xs font-semibold tracking-[0.06em] uppercase text-ink-muted"><x-ui.t fil="Kabuhayan" en="Livelihood" /></span>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="(l, li) in (selectedBarangayProfile?.history?.livelihood?.items || [])" :key="li">
                                            <span class="bg-brand-50 text-brand-700 text-[13px] font-semibold px-3 py-1.5 rounded-full" x-text="l"></span>
                                        </template>
                                    </div>
                                    <span x-show="selectedBarangayProfile?.history?.livelihood?.note" class="text-[13px] leading-normal text-ink-muted" x-text="selectedBarangayProfile?.history?.livelihood?.note"></span>
                                </div>
                            </div>

                            <div x-show="profileLeaders.length" class="flex flex-col gap-3">
                                <div class="flex items-baseline justify-between">
                                    <h4 class="text-base font-bold text-ink"><x-ui.t fil="Pamunuan" en="Leadership" /></h4>
                                    <button type="button" x-show="profileLeaders.length > 3" @click="allLeaders = !allLeaders" class="min-h-[44px] text-[13px] font-semibold text-brand-600 hover:underline" x-text="allLeaders ? ($store.lang.current === 'en' ? 'Show less' : 'Ipakita nang kaunti') : ($store.lang.current === 'en' ? 'Show all ' : 'Ipakita lahat ng ') + profileLeaders.length"></button>
                                </div>
                                <div class="rounded-[14px] border border-slate-200 overflow-hidden divide-y divide-slate-100">
                                    <template x-for="(l, li) in (allLeaders ? profileLeaders : profileLeaders.slice(0, 3))" :key="li">
                                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-x-4 gap-y-1 px-[18px] py-3">
                                            <span class="w-full sm:w-[170px] shrink-0 text-[13px] text-ink-muted tabular-nums" x-text="l.term"></span>
                                            <span class="flex-1 text-sm font-semibold text-ink" x-text="l.name"></span>
                                            <span x-show="isCurrentTerm(l)" class="bg-emerald-50 text-emerald-700 text-xs font-semibold px-2.5 py-1 rounded-full"><x-ui.t fil="Kasalukuyan" en="Current" /></span>
                                            <span class="text-xs text-ink-muted" x-text="l.title"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div x-show="(selectedBarangayProfile?.history?.landmarks || []).length" class="flex flex-col gap-3">
                                <h4 class="text-base font-bold text-ink"><x-ui.t fil="Mga lugar na dapat malaman" en="Places to know" /></h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <template x-for="(lm, li) in (selectedBarangayProfile?.history?.landmarks || [])" :key="li">
                                        <div class="flex gap-3 px-4 py-3.5 rounded-xl border border-slate-200">
                                            <i data-lucide="map-pin" class="w-[18px] h-[18px] text-brand-600 shrink-0 mt-0.5"></i>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-ink" x-text="lm.name"></p>
                                                <p class="text-[13px] text-ink-muted" x-text="[lm.location, lm.note].filter(Boolean).join(' · ')"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div x-show="selectedBarangayProfile?.history?.founding || selectedBarangayProfile?.history?.renaming || selectedBarangayProfile?.history?.today" class="flex flex-col gap-3 border-t border-slate-200 pt-5">
                                <button type="button" @click="showStory = !showStory" :aria-expanded="showStory" class="self-start min-h-[44px] px-4 rounded-[10px] border border-brand-100 bg-white text-sm font-semibold text-brand-600 hover:bg-brand-50" x-text="showStory ? ($store.lang.current === 'en' ? 'Hide full history' : 'Itago ang buong kasaysayan') : ($store.lang.current === 'en' ? 'Read full history' : 'Basahin ang buong kasaysayan')"></button>
                                <div x-show="showStory" x-cloak class="flex flex-col gap-3 text-sm leading-[1.7] text-slate-600">
                                    <p x-show="selectedBarangayProfile?.history?.founding" x-text="selectedBarangayProfile?.history?.founding"></p>
                                    <p x-show="selectedBarangayProfile?.history?.renaming" x-text="selectedBarangayProfile?.history?.renaming"></p>
                                    <p x-show="selectedBarangayProfile?.history?.today" x-text="selectedBarangayProfile?.history?.today"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Officials & Staff -->
                        <div x-show="profileTab === 'officials'" x-cloak class="px-5 sm:px-7 pt-6 pb-8 space-y-6 max-h-[70vh] overflow-y-auto scrollbar-thin">
                            <p x-show="!(selectedBarangayProfile?.officialsRoster || []).length" class="text-sm text-ink-muted text-center py-8"><x-ui.t fil="Wala pang naidaragdag na listahan ng mga opisyal." en="The officials roster has not been added yet." /></p>

                            <template x-for="(o, oi) in rosterGroup('captain')" :key="'pb' + oi">
                                <button type="button" @click="person = o" :aria-label="'View profile of ' + o.name" class="w-full flex items-center gap-[18px] p-5 rounded-2xl bg-slate-50 hover:bg-brand-50/60 text-left transition-colors">
                                    <span class="relative w-[72px] h-[72px] shrink-0">
                                        <span class="absolute inset-0 rounded-full bg-brand-600 text-white flex items-center justify-center text-[22px] font-bold" x-text="officialInitials(o.name)"></span>
                                        <img x-show="o.photo" :src="o.photo ? '{{ rtrim(asset(''), '/') }}/' + o.photo : ''" x-on:error="$el.style.display='none'" alt="" class="relative w-[72px] h-[72px] rounded-full object-cover">
                                    </span>
                                    <span class="flex-1 min-w-0 flex flex-col gap-1">
                                        <span class="text-xs font-semibold tracking-[0.06em] uppercase text-brand-600" x-text="o.position"></span>
                                        <span class="text-[19px] font-bold text-ink" x-text="o.name"></span>
                                        <span class="text-[13px] text-ink-muted" x-text="o.committee"></span>
                                    </span>
                                    <i data-lucide="chevron-right" class="w-5 h-5 text-slate-400 shrink-0"></i>
                                </button>
                            </template>

                            <div x-show="rosterGroup('officer').length" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <template x-for="(o, oi) in rosterGroup('officer')" :key="'of' + oi">
                                    <button type="button" @click="person = o" class="flex flex-row sm:flex-col items-center sm:items-start gap-2.5 p-4 rounded-[14px] border border-slate-200 bg-white hover:border-brand-200 text-left transition-colors">
                                        <span class="relative w-11 h-11 shrink-0">
                                            <span class="absolute inset-0 rounded-full bg-brand-50 text-brand-700 flex items-center justify-center text-sm font-bold" x-text="officialInitials(o.name)"></span>
                                            <img x-show="o.photo" :src="o.photo ? '{{ rtrim(asset(''), '/') }}/' + o.photo : ''" x-on:error="$el.style.display='none'" alt="" class="relative w-11 h-11 rounded-full object-cover">
                                        </span>
                                        <span class="flex flex-col gap-0.5 min-w-0">
                                            <span class="text-sm font-bold text-ink" x-text="o.name"></span>
                                            <span class="text-[13px] font-semibold text-brand-600" x-text="o.position"></span>
                                        </span>
                                    </button>
                                </template>
                            </div>

                            <div x-show="rosterGroup('kagawad').length" class="flex flex-col gap-3">
                                <div class="flex items-baseline gap-2">
                                    <h4 class="text-base font-bold text-ink">Barangay Kagawad</h4>
                                    <span class="text-[13px] text-ink-muted" x-text="rosterGroup('kagawad').length + ($store.lang.current === 'en' ? ' members · by committee' : ' kagawad · ayon sa komite')"></span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 border-t border-slate-100">
                                    <template x-for="(o, oi) in rosterGroup('kagawad')" :key="'kg' + oi">
                                        <button type="button" @click="person = o" class="w-full flex items-center gap-3 py-3 border-b border-slate-100 text-left hover:bg-slate-50/60">
                                            <span class="relative w-10 h-10 shrink-0">
                                                <span class="absolute inset-0 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[13px] font-bold" x-text="officialInitials(o.name)"></span>
                                                <img x-show="o.photo" :src="o.photo ? '{{ rtrim(asset(''), '/') }}/' + o.photo : ''" x-on:error="$el.style.display='none'" alt="" class="relative w-10 h-10 rounded-full object-cover">
                                            </span>
                                            <span class="flex flex-col gap-0.5 min-w-0">
                                                <span class="text-sm font-semibold text-ink" x-text="o.name"></span>
                                                <span class="text-[13px] text-ink-muted" x-text="officialCommittee(o)"></span>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- One official, in the middle of the screen: the photo, full size, and what they handle. -->
                <div x-show="person" x-cloak class="fixed inset-0 z-[60] bg-navy-950/50 flex items-center justify-center p-4" @click.self="person = null">
                    <div role="dialog" aria-modal="true" aria-label="Official profile" x-trap="person" class="relative w-full max-w-[400px] bg-white rounded-[20px] overflow-hidden shadow-float">
                        <button type="button" aria-label="Close profile" @click="person = null" class="absolute top-3 right-3 z-10 w-10 h-10 rounded-full bg-white/90 flex items-center justify-center text-ink hover:bg-white"><i data-lucide="x" class="w-[18px] h-[18px]"></i></button>
                        <div class="relative h-[380px] max-h-[50vh] bg-brand-50 flex items-center justify-center">
                            <span class="text-[64px] font-bold text-brand-700" x-text="officialInitials(person?.name || '')"></span>
                            <img x-show="person?.photo" :src="person?.photo ? '{{ rtrim(asset(''), '/') }}/' + person.photo : ''" x-on:error="$el.style.display='none'" :alt="person?.name ? 'Photograph of ' + person.name : ''" class="absolute inset-0 w-full h-full object-contain object-center">
                        </div>
                        <div class="flex flex-col items-center text-center gap-2.5 px-6 pt-5 pb-6">
                            <span class="text-xs font-semibold tracking-[0.06em] uppercase text-brand-600" x-text="person?.position"></span>
                            <span class="text-[22px] font-bold tracking-tight text-ink" x-text="person?.name"></span>
                            <div class="w-full flex flex-col gap-2.5 border-t border-slate-100 pt-3">
                                <div x-show="person?.committee" class="flex flex-col text-sm">
                                    <span class="text-xs text-ink-muted" x-text="officialAreaLabel(person)"></span>
                                    <span class="font-semibold text-ink" x-text="officialCommittee(person)"></span>
                                </div>
                                <div x-show="officialAlsoServed(person)" class="flex flex-col text-sm">
                                    <span class="text-xs text-ink-muted"><x-ui.t fil="Naging" en="Also served" /></span>
                                    <span class="font-semibold text-ink" x-text="officialAlsoServed(person)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.citizen>
