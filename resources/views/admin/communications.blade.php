@php
    $announcements = [
        ['title' => 'Fiesta ng Esperanza 2026 — Schedule of Activities', 'category' => 'Community', 'author' => 'Corazon Villareal', 'date' => 'Jul 9, 2026', 'status' => 'Draft', 'image' => 'esperanza_festival.jpg', 'scope' => 'Municipal'],
        ['title' => 'Free Medical & Dental Mission — Brgy. Poblacion', 'category' => 'Health', 'author' => 'Dr. Leilani Domingo', 'date' => 'Jul 7, 2026', 'status' => 'Pending Review', 'image' => 'esperanza_people.jpg', 'scope' => 'Poblacion'],
        ['title' => 'Online Document Requests now accept GCash payment', 'category' => 'Public Service', 'author' => 'Paolo Reyes', 'date' => 'Jul 5, 2026', 'status' => 'Approved', 'image' => 'rectangle_cityhall.jpg', 'scope' => 'Municipal'],
        ['title' => 'Typhoon Preparedness Advisory', 'category' => 'Advisory', 'author' => 'MDRRMO', 'date' => 'Jul 3, 2026', 'status' => 'Completed', 'image' => 'esperanza-aerial.jpg', 'scope' => 'Municipal'],
        ['title' => 'Free Skills Training for Out-of-School Youth', 'category' => 'Livelihood', 'author' => 'Corazon Villareal', 'date' => 'Jun 28, 2026', 'status' => 'Completed', 'image' => 'esperanza_community2.jpg', 'scope' => 'Municipal'],
        ['title' => '2025 Barangay Election Results Advisory', 'category' => 'Governance', 'author' => 'Atty. Marivic Ong', 'date' => 'Jan 15, 2026', 'status' => 'Archived', 'image' => 'rectangle_lgu.png', 'scope' => 'Municipal'],
        ['title' => 'Brgy. Labangtaytay Clean-Up Drive This Weekend', 'category' => 'Community', 'author' => 'Jenneth A. Cachila', 'date' => 'Jul 11, 2026', 'status' => 'Pending Review', 'image' => 'esperanza_community3.jpg', 'scope' => 'Labangtaytay'],
    ];

    $offices = [
        ['name' => 'Office of the Municipal Mayor', 'head' => 'Hon. Ricardo M. Espallardo', 'contact' => '(056) 333-1021', 'email' => 'mayor@esperanza.gov.ph', 'icon' => 'landmark', 'color' => 'from-navy-600 to-navy-900'],
        ['name' => 'Municipal Social Welfare & Development Office', 'head' => 'Ms. Corazon P. Villareal', 'contact' => '(056) 333-1044', 'email' => 'mswdo@esperanza.gov.ph', 'icon' => 'hand-heart', 'color' => 'from-purple-500 to-purple-700'],
        ['name' => 'Municipal Treasurer\'s Office', 'head' => 'Mr. Bienvenido T. Salazar', 'contact' => '(056) 333-1032', 'email' => 'treasury@esperanza.gov.ph', 'icon' => 'wallet', 'color' => 'from-gold-500 to-gold-700'],
        ['name' => 'Municipal Health Office', 'head' => 'Dr. Leilani F. Domingo', 'contact' => '(056) 333-1056', 'email' => 'health@esperanza.gov.ph', 'icon' => 'stethoscope', 'color' => 'from-emerald-500 to-emerald-700'],
        ['name' => 'Office of the Municipal Civil Registrar', 'head' => 'Atty. Marivic S. Ong', 'contact' => '(056) 333-1067', 'email' => 'civilregistrar@esperanza.gov.ph', 'icon' => 'file-text', 'color' => 'from-rose-500 to-rose-700'],
        ['name' => 'ICT Office', 'head' => 'Engr. Paolo J. Reyes', 'contact' => '(056) 333-1099', 'email' => 'ict@esperanza.gov.ph', 'icon' => 'cpu', 'color' => 'from-brand-500 to-brand-700'],
    ];

    // Real seal per barangay, where onboarded (see config/esperanza_barangay_documents.php).
    // Barangays without a configured seal fall back to a placeholder icon in the UI.
    $barangaySeals = collect(config('esperanza_barangay_documents'))
        ->mapWithKeys(fn ($info) => [$info['label'] => $info['seal']]);

    $barangays = collect(config('esperanza.barangays'))->map(fn ($b) => [
        'name' => $b,
        'captain' => 'Hon. ' . collect(['Roberto','Elena','Manuel','Fe','Domingo','Rosario','Alfredo','Teresita'])->random() . ' ' . collect(['Cruz','Santos','Reyes','Bautista','Delos Reyes','Villanueva'])->random(),
        'contact' => '0917 ' . rand(100, 999) . ' ' . rand(1000, 9999),
        'email' => 'brgy.' . strtolower(str_replace(' ', '', $b)) . '@esperanza.gov.ph',
        'hall_address' => 'Barangay Hall, Brgy. ' . $b . ', Esperanza, Masbate',
        'seal' => $barangaySeals[$b] ?? null,
    ])->values()->all();

    $communityPosts = [
        ['author' => 'Elmer Bantillo', 'barangay' => 'Santiago', 'body' => 'Successful po ang blood donation drive natin ngayong araw dito sa Brgy. Santiago! Umabot ng 40+ na donors. Maraming salamat sa lahat ng dumalo at sa Red Cross Masbate!', 'image' => 'esperanza_community1.jpg', 'time' => '3 hrs ago', 'likes' => 47, 'comments' => 1, 'shares' => 6, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Josefina Marbella', 'barangay' => 'Baras', 'body' => 'Gusto ko lang po magpasalamat sa MSWDO at OSCA sa mabilis na pag-asikaso ng aking social pension application. Simple lang pero malaking tulong na po sa amin. Maraming salamat po talaga!', 'image' => null, 'time' => '1 day ago', 'likes' => 83, 'comments' => 1, 'shares' => 4, 'reports' => 0, 'featured' => true, 'status' => 'Approved'],
        ['author' => 'Michael Bacus', 'barangay' => 'Iligan', 'body' => 'May nakakita po ba nito? Nawala malapit sa covered court kagabi. Itim na may puting paa, mahiyain pero friendly naman. Tulungan niyo po ako i-share.', 'image' => 'esperanza_event3.jpg', 'time' => '1 day ago', 'likes' => 29, 'comments' => 0, 'shares' => 22, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Angelica Fajardo', 'barangay' => 'Rizal', 'body' => 'Coverage ng aming fiesta rehearsal kagabi! Ang saya-saya, sana dumagsa kayo sa opening program.', 'image' => 'esperanza_event2.jpg', 'time' => '2 days ago', 'likes' => 61, 'comments' => 0, 'shares' => 9, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Teresita Salazar', 'barangay' => 'Agoho', 'body' => 'Tanong lang po, kailan po ulit ang susunod na free medical mission? Gusto ko sanang isama ang lola ko na senior citizen.', 'image' => null, 'time' => '4 days ago', 'likes' => 12, 'comments' => 1, 'shares' => 1, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Rodrigo Palma', 'barangay' => 'Domorog', 'body' => 'Bumili kayo sa amin, best price sa buong Masbate! Message niyo lang po ako dito para sa mga detalye at promo ngayong buwan.', 'image' => null, 'time' => '6 hrs ago', 'likes' => 2, 'comments' => 0, 'shares' => 0, 'reports' => 3, 'featured' => false, 'status' => 'Pending Review'],
    ];

    // Media library for the New Balita composer's attachment picker (photos, GIFs, videos).
    $balitaMedia = [
        ['name' => 'esperanza_festival.jpg', 'category' => 'IMG'],
        ['name' => 'esperanza_people.jpg', 'category' => 'IMG'],
        ['name' => 'rectangle_cityhall.jpg', 'category' => 'IMG'],
        ['name' => 'esperanza-aerial.jpg', 'category' => 'IMG'],
        ['name' => 'esperanza_community2.jpg', 'category' => 'IMG'],
        ['name' => 'esperanza_community3.jpg', 'category' => 'IMG'],
        ['name' => 'fiesta-countdown.gif', 'category' => 'GIF'],
        ['name' => 'medical-mission-recap.mp4', 'category' => 'VID'],
        ['name' => 'barangay-cleanup-highlights.mp4', 'category' => 'VID'],
    ];

    // Barangay Blotter — day-to-day peace-and-order incident log kept by the Barangay Secretary,
    // distinct from Sakuna's disaster Incident Management (that module is calamity-response only).
    $blotterEntries = [
        ['ref' => 'BLT-2026-0041', 'barangay' => 'Labangtaytay', 'date' => 'Jul 10, 2026', 'type' => 'Noise Complaint', 'details' => "Kapitbahay reported ang malakas na videoke sa Purok 10 hanggang alas-dose ng gabi. Pinag-usapan ang magkabilang panig, nagkasundo na tatapusin ang videoke bago mag-alas-diyes.", 'reportedBy' => 'Jenneth A. Cachila', 'status' => 'Resolved'],
        ['ref' => 'BLT-2026-0038', 'barangay' => 'Labangtaytay', 'date' => 'Jun 22, 2026', 'type' => 'Property Dispute', 'details' => 'Away sa hangganan ng lote sa pagitan ng dalawang magkapitbahay sa Purok 9. Na-refer sa Lupong Tagapamayapa para sa mediation.', 'reportedBy' => 'Jenneth A. Cachila', 'status' => 'Under Mediation'],
        ['ref' => 'BLT-2026-0025', 'barangay' => 'Poblacion', 'date' => 'May 30, 2026', 'type' => 'Lost Item', 'details' => 'Nawalang cellphone na-report sa Barangay Hall, na-turn over ng nakakita ilang oras pagkatapos.', 'reportedBy' => 'Barangay Secretary', 'status' => 'Resolved'],
        ['ref' => 'BLT-2026-0019', 'barangay' => 'Masbaranon', 'date' => 'May 12, 2026', 'type' => 'Domestic Dispute', 'details' => 'Away mag-asawa na-report ng kapitbahay. Pinuntahan ng Barangay Tanod, pinayapa ang magkabilang panig, walang isinampang reklamo.', 'reportedBy' => 'Barangay Secretary', 'status' => 'Closed'],
    ];

    $tab = $tab ?? 'balita';
@endphp

<x-layouts.admin title="Balita" subtitle="Announcements, community posts, and the municipal directory." active="communications">
    <div
        x-data="{
            tab: '{{ $tab }}',
            communityPosts: @js($communityPosts),
            postOpen: false,
            selectedPost: null,
            barangays: @js($barangays),
            barangayNames: @js(config('esperanza.barangays')),
            open: false,
            selectedBarangay: null,
            editBarangay(b) { this.selectedBarangay = { ...b }; this.open = true; },
            saveBarangay() {
                const idx = this.barangays.findIndex(x => x.name === this.selectedBarangay.name);
                if (idx !== -1) this.barangays[idx] = { ...this.selectedBarangay };
                this.open = false;
                $dispatch('toast', { message: 'Brgy. ' + this.selectedBarangay.name + ' directory entry updated.', variant: 'success' });
            },

            // Balita: content queue filters (status, audience/scope, category, search) —
            // mirrors a Meta Business Suite-style content admin: filter chips + a review queue.
            announcements: @js($announcements),
            balitaStatus: 'All',
            balitaScope: 'All',
            balitaCategory: 'All',
            balitaSearch: '',
            reviewOpen: false,
            selectedAnnouncement: null,
            // Municipal-wide posts are public to everyone; barangay-specific posts
            // only to Municipal accounts and accounts scoped to that same barangay.
            visibleToMe(a) { return a.scope === 'Municipal' || this.$store.session.inScope(a.scope); },
            get filteredAnnouncements() {
                return this.announcements.filter(a =>
                    this.visibleToMe(a) &&
                    (this.balitaStatus === 'All' || a.status === this.balitaStatus) &&
                    (this.balitaScope === 'All' || a.scope === this.balitaScope) &&
                    (this.balitaCategory === 'All' || a.category === this.balitaCategory) &&
                    (this.balitaSearch === '' || a.title.toLowerCase().includes(this.balitaSearch.toLowerCase()))
                );
            },
            reviewAnnouncement(a) { this.selectedAnnouncement = a; this.reviewOpen = true; },

            // New Balita composer audience
            newBalitaAudience: 'Municipal',
            newBalitaBarangay: '{{ $barangays[0]['name'] ?? '' }}',
            balitaMedia: @js($balitaMedia),
            newBalitaAttachment: null,
            showMediaPicker: false,

            // Community Posts: same filter treatment as Balita
            communityStatus: 'All',
            communityScope: 'All',
            communitySearch: '',
            get filteredCommunityPosts() {
                return this.communityPosts.filter(p =>
                    this.$store.session.inScope(p.barangay) &&
                    (this.communityStatus === 'All' || p.status === this.communityStatus) &&
                    (this.communityScope === 'All' || p.barangay === this.communityScope) &&
                    (this.communitySearch === '' || p.author.toLowerCase().includes(this.communitySearch.toLowerCase()) || p.body.toLowerCase().includes(this.communitySearch.toLowerCase()))
                );
            },

            // Barangay Blotter — peace-and-order incident log, scoped like everything else by inScope().
            blotterEntries: @js($blotterEntries),
            blotterSearch: '',
            blotterBarangayFilter: 'All',
            showAddBlotter: false,
            newBlotter: { barangay: '{{ $barangays[0]['name'] ?? '' }}', date: '', type: '', details: '' },
            get filteredBlotter() {
                return this.blotterEntries.filter(b =>
                    this.$store.session.inScope(b.barangay) &&
                    (this.blotterBarangayFilter === 'All' || b.barangay === this.blotterBarangayFilter) &&
                    (this.blotterSearch === '' || b.type.toLowerCase().includes(this.blotterSearch.toLowerCase()) || b.details.toLowerCase().includes(this.blotterSearch.toLowerCase()))
                );
            },
            addBlotterEntry() {
                this.blotterEntries.unshift({
                    ref: 'BLT-2026-' + Math.floor(1000 + Math.random() * 9000),
                    barangay: this.newBlotter.barangay,
                    date: this.newBlotter.date || new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                    type: this.newBlotter.type,
                    details: this.newBlotter.details,
                    reportedBy: this.$store.session.account?.name || 'Barangay Secretary',
                    status: 'Open',
                });
                this.showAddBlotter = false;
                this.newBlotter = { barangay: '{{ $barangays[0]['name'] ?? '' }}', date: '', type: '', details: '' };
                $dispatch('toast', { message: 'Blotter entry logged.', variant: 'success' });
            },
        }"
        class="animate-fade-up"
    >

        <div x-show="!$store.session.can('communications')" x-cloak>
            <x-admin.access-restricted module="Balita" icon="megaphone" />
        </div>

        <div x-show="$store.session.can('communications')" x-cloak>

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-4 overflow-x-auto">
            <button @click="tab = 'balita'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'balita' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="megaphone" class="w-3.5 h-3.5"></i>Balita</button>
            <button @click="tab = 'community'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'community' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="users-round" class="w-3.5 h-3.5"></i>Community Posts
                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full" :class="tab === 'community' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-600'" x-show="communityPosts.filter(p => p.status === 'Pending Review').length > 0" x-text="communityPosts.filter(p => p.status === 'Pending Review').length"></span>
            </button>
            <button @click="tab = 'offices'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'offices' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="building-2" class="w-3.5 h-3.5"></i>Offices</button>
            <button @click="tab = 'barangays'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'barangays' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i>Barangays</button>
            <button @click="tab = 'blotter'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'blotter' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="book-text" class="w-3.5 h-3.5"></i>Blotter</button>
        </div>

        <!-- Balita -->
        <div x-show="tab === 'balita'" x-data="{ open: false }">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card label="Published" value="28" icon="megaphone" color="brand" sublabel="This year" :delay="0" />
                <x-ui.stat-card label="Pending Review" value="3" icon="file-clock" color="orange" :delay="40" />
                <x-ui.stat-card label="Drafts" value="2" icon="pencil-line" color="purple" :delay="80" />
                <x-ui.stat-card label="Total Reach" value="14,208" icon="eye" color="green" sublabel="Citizen views this month" :delay="120" />
            </div>

            <!-- Toolbar: search + filters + primary action, all in one -->
            <div class="bg-white border border-slate-100 rounded-2xl shadow-card p-3 mb-4 space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="text" x-model="balitaSearch" placeholder="Search Balita titles..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                    <select x-model="balitaCategory" class="text-xs rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        <option value="All">All Categories</option>
                        <template x-for="c in ['Community','Health','Public Service','Advisory','Livelihood','Governance']" :key="c"><option :value="c" x-text="c"></option></template>
                    </select>
                    <select x-model="balitaScope" class="text-xs rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        <option value="All">All Audiences</option>
                        <option value="Municipal">Municipal-wide</option>
                        <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                    </select>
                    <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>
                    <x-ui.button @click="open = true" icon="plus" size="sm" class="shrink-0">New Balita</x-ui.button>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto">
                        <template x-for="s in ['All','Draft','Pending Review','Approved','Completed','Archived']" :key="s">
                            <button
                                type="button" @click="balitaStatus = s"
                                class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap"
                                :class="balitaStatus === s ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                x-text="s"
                            ></button>
                        </template>
                    </div>
                    <p class="text-[11px] text-slate-400 shrink-0" x-text="filteredAnnouncements.length + ' of ' + announcements.length + ' posts'"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="a in filteredAnnouncements" :key="a.title">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard group">
                        <div class="relative h-36 overflow-hidden">
                            <img :src="'{{ rtrim(asset('images/esperanza'), '/') }}/' + a.image" alt="" class="w-full h-full object-cover transition-transform duration-[220ms] ease-standard group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-navy-950/50 via-transparent to-transparent"></div>
                            <span class="absolute top-2.5 left-2.5 text-[10px] font-semibold text-white bg-navy-900/70 backdrop-blur rounded-full px-2.5 py-1" x-text="a.category"></span>
                            <span class="absolute top-2.5 right-2.5">
                                <x-ui.badge status="Draft" x-show="a.status === 'Draft'" />
                                <x-ui.badge status="Pending Review" x-show="a.status === 'Pending Review'" x-cloak />
                                <x-ui.badge status="Approved" x-show="a.status === 'Approved'" x-cloak />
                                <x-ui.badge status="Completed" x-show="a.status === 'Completed'" x-cloak />
                                <x-ui.badge status="Archived" x-show="a.status === 'Archived'" x-cloak />
                            </span>
                        </div>
                        <div class="p-3.5">
                            <span
                                class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-2 py-0.5 mb-2"
                                :class="a.scope === 'Municipal' ? 'bg-brand-50 text-brand-700' : 'bg-purple-50 text-purple-700'"
                            >
                                <i :data-lucide="a.scope === 'Municipal' ? 'landmark' : 'map-pin'" class="w-2.5 h-2.5"></i>
                                <span x-text="a.scope === 'Municipal' ? 'Municipality-wide' : 'Brgy. ' + a.scope"></span>
                            </span>
                            <p class="text-sm font-semibold text-navy-900 leading-snug line-clamp-2 mb-2" x-text="a.title"></p>
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1.5 text-xs text-slate-500 min-w-0">
                                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold shrink-0 text-[9px]" x-text="a.author.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                                    <span class="truncate" x-text="a.author"></span>
                                </span>
                                <span class="text-[11px] text-slate-400 shrink-0" x-text="a.date"></span>
                            </div>
                        </div>
                        <button
                            @click="a.status === 'Pending Review' ? reviewAnnouncement(a) : $dispatch('toast', { message: 'Editing Balita posts is a frontend preview.', variant: 'info' })"
                            class="w-full flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 border-t border-slate-100 transition-colors"
                            :class="a.status === 'Pending Review' ? 'text-orange-600 hover:bg-orange-50/60' : 'text-brand-600 hover:bg-brand-50/60'"
                        >
                            <i data-lucide="history" class="w-3.5 h-3.5" x-show="a.status === 'Pending Review'"></i>
                            <i data-lucide="pencil" class="w-3.5 h-3.5" x-show="a.status !== 'Pending Review'"></i>
                            <span x-text="a.status === 'Pending Review' ? 'Review' : 'Edit'"></span>
                        </button>
                    </div>
                </template>
                <div class="col-span-full flex flex-col items-center text-center py-10" x-show="filteredAnnouncements.length === 0">
                    <span class="w-11 h-11 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3"><i data-lucide="search-x" class="w-5 h-5"></i></span>
                    <p class="text-sm font-medium text-slate-600">No Balita posts match these filters.</p>
                </div>
            </div>

            <x-ui.modal title="New Balita" maxWidth="lg" :fullscreen="true">
                <div class="space-y-4">
                    <x-ui.input name="title" label="Title" placeholder="e.g. Free Medical Mission — Brgy. Poblacion" />
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Category</label>
                        <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                            <option>Community</option>
                            <option>Health</option>
                            <option>Public Service</option>
                            <option>Advisory</option>
                            <option>Livelihood</option>
                            <option>Governance</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Audience</label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button
                                type="button" @click="newBalitaAudience = 'Municipal'"
                                class="text-left rounded-xl border p-3 transition-all duration-[160ms] ease-standard"
                                :class="newBalitaAudience === 'Municipal' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'"
                            >
                                <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-1.5"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                <p class="text-xs font-semibold text-navy-900">Municipal-wide</p>
                                <p class="text-[10.5px] text-slate-500 mt-0.5">Visible to every barangay</p>
                            </button>
                            <button
                                type="button" @click="newBalitaAudience = 'Barangay'"
                                class="text-left rounded-xl border p-3 transition-all duration-[160ms] ease-standard"
                                :class="newBalitaAudience === 'Barangay' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'"
                            >
                                <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mb-1.5"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                <p class="text-xs font-semibold text-navy-900">Specific Barangay</p>
                                <p class="text-[10.5px] text-slate-500 mt-0.5">Only that barangay sees it</p>
                            </button>
                        </div>
                        <div x-show="newBalitaAudience === 'Barangay'" x-cloak x-transition:enter="transition ease-out duration-[160ms]" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="mt-2.5">
                            <select x-model="newBalitaBarangay" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Content</label>
                        <textarea rows="4" placeholder="Write the announcement content..." class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-slate-700">Attachment <span class="text-slate-400 font-normal">(optional)</span></label>
                            <span class="text-[10.5px] text-slate-400">Photo, GIF, or video</span>
                        </div>

                        <div x-show="!newBalitaAttachment">
                            <button
                                type="button" @click="showMediaPicker = !showMediaPicker"
                                class="w-full flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed py-5 text-xs font-medium transition-all duration-[160ms] ease-standard"
                                :class="showMediaPicker ? 'border-brand-300 bg-brand-50/50 text-brand-600' : 'border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-slate-500 hover:text-brand-600'"
                            >
                                <i data-lucide="image-plus" class="w-5 h-5"></i>
                                <span>Add Attachment</span>
                            </button>
                        </div>

                        <div x-show="newBalitaAttachment" x-cloak class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 p-2.5">
                            <span class="w-10 h-10 rounded-lg bg-gradient-to-br from-brand-100 to-brand-50 text-brand-500 flex items-center justify-center shrink-0">
                                <i data-lucide="image" class="w-5 h-5" x-show="newBalitaAttachment?.endsWith('.gif')"></i>
                                <i data-lucide="video" class="w-5 h-5" x-show="newBalitaAttachment?.endsWith('.mp4')"></i>
                                <i data-lucide="image" class="w-5 h-5" x-show="newBalitaAttachment && !newBalitaAttachment.endsWith('.gif') && !newBalitaAttachment.endsWith('.mp4')"></i>
                            </span>
                            <span class="text-xs font-medium text-slate-600 truncate flex-1" x-text="newBalitaAttachment"></span>
                            <button type="button" @click="newBalitaAttachment = null" class="text-slate-300 hover:text-rose-500 shrink-0"><i data-lucide="x" class="w-4 h-4"></i></button>
                        </div>

                        <div x-show="showMediaPicker" x-cloak x-transition:enter="transition ease-out duration-[160ms]" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="mt-2.5">
                            <x-ui.file-picker
                                files="balitaMedia"
                                onSelect="newBalitaAttachment = file.name; showMediaPicker = false"
                                uploadAction="newBalitaAttachment = 'New upload.jpg'; showMediaPicker = false; $dispatch('toast', { message: 'File upload is a frontend preview — no data is saved.', variant: 'info' })"
                                :columns="3"
                                :bilingual="false"
                                empty="No media uploaded yet."
                            />
                        </div>
                    </div>
                </div>

                <x-slot:footer>
                    <x-ui.button variant="secondary" size="sm" @click="open = false; newBalitaAttachment = null; showMediaPicker = false; $dispatch('toast', { message: 'Saved as draft.', variant: 'info' })">Save as Draft</x-ui.button>
                    <x-ui.button size="sm" icon="send" @click="open = false; newBalitaAttachment = null; showMediaPicker = false; $dispatch('toast', { message: 'Announcement submitted for review.', variant: 'success' })">Submit for Review</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>

            <div x-data="{ get open() { return reviewOpen; }, set open(v) { reviewOpen = v; } }">
                <x-ui.modal title="Review Balita Post" maxWidth="lg">
                    <template x-if="selectedAnnouncement">
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0 text-sm" x-text="selectedAnnouncement.author.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-navy-900" x-text="selectedAnnouncement.author"></p>
                                    <p class="text-xs text-slate-400" x-text="(selectedAnnouncement.scope === 'Municipal' ? 'Municipality-wide' : 'Brgy. ' + selectedAnnouncement.scope) + ' · ' + selectedAnnouncement.date"></p>
                                </div>
                                <span class="text-[10px] font-medium text-slate-500 bg-slate-100 rounded-full px-2 py-0.5 shrink-0" x-text="selectedAnnouncement.category"></span>
                            </div>
                            <p class="text-sm font-semibold text-navy-900 mb-2" x-text="selectedAnnouncement.title"></p>
                            <img :src="'{{ rtrim(asset('images/esperanza'), '/') }}/' + selectedAnnouncement.image" class="w-full max-h-64 object-cover rounded-xl mb-3">
                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-3.5 text-xs text-slate-500">
                                This post is awaiting review before it goes live on the citizen Balita feed. Approving will publish it immediately to its selected audience.
                            </div>
                        </div>
                    </template>

                    <x-slot:footer>
                        <x-ui.button variant="danger" size="sm" @click="selectedAnnouncement.status = 'Draft'; open = false; $dispatch('toast', { message: 'Sent back to draft for revisions.', variant: 'error' })">Request Changes</x-ui.button>
                        <x-ui.button size="sm" icon="check" @click="selectedAnnouncement.status = 'Approved'; open = false; $dispatch('toast', { message: 'Balita post approved and published.', variant: 'success' })">Approve & Publish</x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
            </div>
        </div>

        <!-- Community posts -->
        <div x-show="tab === 'community'" x-cloak x-init="$nextTick(() => window.renderIcons?.())">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card label="Total Posts" value="{{ count($communityPosts) }}" valueBind="communityPosts.length" icon="users-round" color="brand" :delay="0" />
                <x-ui.stat-card label="Reported" value="{{ collect($communityPosts)->where('status', 'Pending Review')->count() }}" valueBind="communityPosts.filter(p => p.status === 'Pending Review').length" icon="flag" color="red" sublabel="Needs moderation" :delay="40" />
                <x-ui.stat-card label="Featured" value="{{ collect($communityPosts)->where('featured', true)->count() }}" valueBind="communityPosts.filter(p => p.featured).length" icon="star" color="gold" :delay="80" />
                <x-ui.stat-card label="Total Engagement" value="{{ collect($communityPosts)->sum(fn($p) => $p['likes'] + $p['comments'] + $p['shares']) }}" valueBind="communityPosts.reduce((n, p) => n + p.likes + p.comments + p.shares, 0)" icon="activity" color="purple" sublabel="Likes, comments &amp; shares" :delay="120" />
            </div>

            <!-- Toolbar -->
            <div class="bg-white border border-slate-100 rounded-2xl shadow-card p-3 mb-4 space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="text" x-model="communitySearch" placeholder="Search author or post content..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                    <select x-model="communityScope" class="text-xs rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        <option value="All">All Barangays</option>
                        <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                    </select>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto">
                        <template x-for="s in ['All','Approved','Pending Review','Archived']" :key="s">
                            <button
                                type="button" @click="communityStatus = s"
                                class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap"
                                :class="communityStatus === s ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                x-text="s"
                            ></button>
                        </template>
                    </div>
                    <p class="text-[11px] text-slate-400 shrink-0" x-text="filteredCommunityPosts.length + ' of ' + communityPosts.length + ' posts'"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <template x-for="p in filteredCommunityPosts" :key="p.author + p.time">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden hover:shadow-card-hover transition-all duration-[220ms] ease-standard">
                        <!-- Header -->
                        <div class="flex items-center gap-2.5 p-3.5 pb-2.5">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0 text-sm" x-text="p.author.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-navy-900 flex items-center gap-1.5 truncate">
                                    <span x-text="p.author"></span>
                                    <i data-lucide="star" class="w-3.5 h-3.5 text-gold-500 shrink-0" style="fill: currentColor" x-show="p.featured"></i>
                                </p>
                                <p class="text-[11px] text-slate-400" x-text="'Brgy. ' + p.barangay + ' · ' + p.time"></p>
                            </div>
                            <span class="shrink-0"><x-ui.badge status="Approved" x-show="p.status === 'Approved'" /><x-ui.badge status="Pending Review" x-show="p.status === 'Pending Review'" x-cloak /><x-ui.badge status="Archived" x-show="p.status === 'Archived'" x-cloak /></span>
                        </div>

                        <!-- Body -->
                        <p class="px-3.5 pb-2.5 text-[13px] text-slate-700 leading-relaxed line-clamp-3" x-text="p.body"></p>

                        <!-- Media -->
                        <div class="bg-slate-100" x-show="p.image">
                            <img :src="'{{ asset('images/esperanza/') }}/' + p.image" alt="" class="w-full max-h-64 object-cover">
                        </div>

                        <!-- Engagement + reports -->
                        <div class="flex items-center justify-between px-3.5 py-2.5 border-t border-slate-50">
                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                <span class="inline-flex items-center gap-1"><i data-lucide="heart" class="w-3.5 h-3.5 text-rose-400"></i><span x-text="p.likes"></span></span>
                                <span class="inline-flex items-center gap-1"><i data-lucide="message-circle" class="w-3.5 h-3.5 text-brand-400"></i><span x-text="p.comments"></span></span>
                                <span class="inline-flex items-center gap-1"><i data-lucide="share-2" class="w-3.5 h-3.5 text-slate-400"></i><span x-text="p.shares"></span></span>
                                <span class="inline-flex items-center gap-1 text-rose-600 font-medium" x-show="p.reports > 0"><i data-lucide="flag" class="w-3.5 h-3.5"></i><span x-text="p.reports + ' report' + (p.reports > 1 ? 's' : '')"></span></span>
                            </div>
                            <button @click="selectedPost = p; postOpen = true" class="text-xs font-semibold text-brand-600 hover:underline whitespace-nowrap shrink-0">Moderate</button>
                        </div>
                    </div>
                </template>
                <div class="col-span-full flex flex-col items-center text-center py-10" x-show="filteredCommunityPosts.length === 0">
                    <span class="w-11 h-11 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3"><i data-lucide="search-x" class="w-5 h-5"></i></span>
                    <p class="text-sm font-medium text-slate-600">No community posts match these filters.</p>
                </div>
            </div>

            <x-ui.modal title="Moderate Community Post" maxWidth="lg">
                <template x-if="selectedPost">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0 text-sm" x-text="selectedPost.author.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                            <div>
                                <p class="text-sm font-semibold text-navy-900" x-text="selectedPost.author"></p>
                                <p class="text-xs text-slate-400" x-text="'Brgy. ' + selectedPost.barangay + ' · ' + selectedPost.time"></p>
                            </div>
                        </div>
                        <p class="text-sm text-slate-700 leading-relaxed mb-3" x-text="selectedPost.body"></p>
                        <img :src="'{{ asset('images/esperanza/') }}/' + selectedPost.image" x-show="selectedPost.image" class="w-full max-h-72 object-cover rounded-xl mb-3">

                        <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 text-sm text-slate-600 space-y-1.5">
                            <p><span class="text-slate-400">Engagement:</span> <span x-text="selectedPost.likes"></span> likes · <span x-text="selectedPost.comments"></span> comments · <span x-text="selectedPost.shares"></span> shares</p>
                            <p x-show="selectedPost.reports > 0"><span class="text-slate-400">Reports:</span> <span class="text-rose-600 font-medium" x-text="selectedPost.reports + ' citizen report(s) — review for possible spam or policy violation'"></span></p>
                        </div>
                    </div>
                </template>

                <x-slot:footer>
                    <x-ui.button variant="danger" size="sm" @click="selectedPost.status = 'Archived'; postOpen = false; $dispatch('toast', { message: 'Post hidden from Balita feed.', variant: 'error' })">Hide Post</x-ui.button>
                    <x-ui.button variant="secondary" size="sm" icon="share-2" @click="selectedPost.shares++; $dispatch('toast', { message: 'Shared to the Municipal Balita feed.', variant: 'success' })">Share to Municipal Feed</x-ui.button>
                    <x-ui.button variant="secondary" size="sm" @click="selectedPost.featured = !selectedPost.featured; $dispatch('toast', { message: selectedPost.featured ? 'Post featured on Balita.' : 'Post unfeatured.', variant: 'success' })"><span x-text="selectedPost && selectedPost.featured ? 'Unfeature' : 'Feature Post'"></span></x-ui.button>
                    <x-ui.button size="sm" icon="check" @click="selectedPost.status = 'Approved'; postOpen = false; $dispatch('toast', { message: 'Post approved and kept live.', variant: 'success' })">Approve & Keep Live</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        <!-- Municipal offices -->
        <div x-show="tab === 'offices'" x-cloak>
            <div class="flex items-center justify-end mb-3">
                <x-ui.button icon="plus" size="sm">Add Office</x-ui.button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($offices as $office)
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard">
                        <div class="relative h-24 bg-gradient-to-br {{ $office['color'] }} flex items-center justify-center">
                            <i data-lucide="{{ $office['icon'] }}" class="w-9 h-9 text-white/90"></i>
                        </div>
                        <div class="p-3.5">
                            <p class="text-sm font-semibold text-navy-900 leading-snug mb-2.5">{{ $office['name'] }}</p>
                            <div class="flex items-center gap-2 mb-2">
                                <x-ui.avatar :name="$office['head']" size="sm" class="!w-6 !h-6 !text-[9px] shrink-0" />
                                <span class="text-xs text-slate-600 truncate">{{ $office['head'] }}</span>
                            </div>
                            <p class="text-xs text-slate-400 flex items-center gap-1.5 mb-1"><i data-lucide="phone" class="w-3 h-3 shrink-0"></i>{{ $office['contact'] }}</p>
                            <p class="text-xs text-slate-400 flex items-center gap-1.5 truncate"><i data-lucide="mail" class="w-3 h-3 shrink-0"></i>{{ $office['email'] }}</p>
                        </div>
                        <button class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50/60 py-2.5 border-t border-slate-100 transition-colors"><i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit</button>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Barangays -->
        <div x-show="tab === 'barangays'" x-cloak>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="b in barangays" :key="b.name">
                    <div x-show="$store.session.inScope(b.name)" x-cloak class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard">
                        <div class="flex items-center gap-3 p-3.5 pb-3 bg-gradient-to-br from-brand-50/70 to-white">
                            <div class="relative w-12 h-12 shrink-0">
                                <div class="absolute inset-0 rounded-full bg-white border-2 border-gold-400 flex items-center justify-center text-slate-300"><i data-lucide="landmark" class="w-5 h-5"></i></div>
                                <img
                                    :src="b.seal ? '{{ rtrim(asset(''), '/') }}/' + b.seal : ''"
                                    x-show="b.seal" onerror="this.style.display='none'" alt=""
                                    class="relative w-12 h-12 rounded-full object-cover border-2 border-gold-400"
                                >
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-navy-900 truncate"><span>Brgy.</span> <span x-text="b.name"></span></p>
                                <p class="text-[11px] text-slate-400 truncate" x-text="b.captain"></p>
                            </div>
                        </div>
                        <div class="p-3.5 pt-2.5">
                            <p class="text-xs text-slate-500 flex items-center gap-1.5 mb-1"><i data-lucide="phone" class="w-3 h-3 shrink-0 text-slate-400"></i><span x-text="b.contact"></span></p>
                            <p class="text-xs text-slate-500 flex items-center gap-1.5 mb-1 truncate"><i data-lucide="mail" class="w-3 h-3 shrink-0 text-slate-400"></i><span x-text="b.email"></span></p>
                            <p class="text-xs text-slate-500 flex items-center gap-1.5 truncate"><i data-lucide="map-pin" class="w-3 h-3 shrink-0 text-slate-400"></i><span x-text="b.hall_address"></span></p>
                        </div>
                        <button
                            @click="editBarangay(b)"
                            x-show="$store.session.canPerm('comm_barangay_directory', 'edit')"
                            class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50/60 py-2.5 border-t border-slate-100 transition-colors"
                        ><i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit</button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Barangay Blotter -->
        <div x-show="tab === 'blotter'" x-cloak>
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input type="text" x-model="blotterSearch" placeholder="Search incident type or details..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                </div>
                <select x-model="blotterBarangayFilter" class="text-xs border border-slate-200 rounded-xl px-3 py-2 bg-white focus:border-brand-300 focus:outline-none transition-colors">
                    <option value="All">All Barangays</option>
                    <template x-for="bn in barangayNames" :key="bn"><option :value="bn" x-text="bn"></option></template>
                </select>
                <button @click="showAddBlotter = true; $nextTick(() => window.renderIcons?.())" class="inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-3.5 py-2 shadow-card transition-colors ml-auto">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Entry
                </button>
            </div>

            <div class="space-y-2.5">
                <template x-for="b in filteredBlotter" :key="b.ref">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0"><i data-lucide="book-text" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-navy-900 truncate" x-text="b.type"></p>
                                    <p class="text-xs text-slate-400" x-text="'Brgy. ' + b.barangay + ' — ' + b.date + ' — ' + b.ref"></p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 min-h-[26px] px-2.5 py-1.5 rounded-full text-xs font-semibold tracking-[0.02em] ring-1 ring-inset whitespace-nowrap shrink-0"
                                :class="{
                                    'bg-amber-50 text-amber-700 ring-amber-200': b.status === 'Open',
                                    'bg-indigo-50 text-indigo-700 ring-indigo-200': b.status === 'Under Mediation',
                                    'bg-emerald-50 text-emerald-700 ring-emerald-200': b.status === 'Resolved',
                                    'bg-slate-100 text-slate-500 ring-slate-200': b.status === 'Closed',
                                }"
                                x-text="b.status"
                            ></span>
                        </div>
                        <p class="text-sm text-slate-600 leading-relaxed pl-[46px]" x-text="b.details"></p>
                        <p class="text-xs text-slate-400 mt-2 pl-[46px]">Reported by <span class="font-medium text-slate-600" x-text="b.reportedBy"></span></p>
                    </div>
                </template>
                <div class="flex flex-col items-center text-center py-12 bg-white border border-slate-100 rounded-2xl" x-show="filteredBlotter.length === 0">
                    <span class="w-11 h-11 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3"><i data-lucide="search-x" class="w-5 h-5"></i></span>
                    <p class="text-sm font-medium text-slate-600">No blotter entries match your filters.</p>
                </div>
            </div>
        </div>

        <!-- Log Blotter Entry Modal -->
        <div x-show="showAddBlotter" x-cloak
            x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" @click.self="showAddBlotter = false">
            <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                class="bg-white rounded-2xl shadow-float w-full max-w-lg">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-navy-900">Log Blotter Entry</h3>
                    <button @click="showAddBlotter = false" class="text-slate-400 hover:text-slate-600 transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Barangay</label>
                        <select x-model="newBlotter.barangay" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                            <template x-for="bn in barangayNames" :key="bn"><option :value="bn" x-text="bn"></option></template>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Date</label>
                            <input type="date" x-model="newBlotter.date" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Incident Type</label>
                            <input type="text" x-model="newBlotter.type" placeholder="e.g. Noise Complaint" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Details</label>
                        <textarea rows="4" x-model="newBlotter.details" placeholder="Describe the incident..." class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-slate-100 bg-slate-50/60">
                    <x-ui.button variant="secondary" size="sm" @click="showAddBlotter = false">Cancel</x-ui.button>
                    <x-ui.button size="sm" @click="addBlotterEntry()">Log Entry</x-ui.button>
                </div>
            </div>
        </div>

        <x-ui.modal title="Edit Barangay Directory Entry" maxWidth="md">
            <template x-if="selectedBarangay">
                <div class="space-y-4">
                    <div class="flex items-center gap-2.5 rounded-xl bg-slate-50 border border-slate-100 px-3.5 py-2.5">
                        <i data-lucide="map-pinned" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <p class="text-sm font-medium text-slate-700" x-text="'Brgy. ' + selectedBarangay.name"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Barangay Captain</label>
                        <input type="text" x-model="selectedBarangay.captain" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact Number</label>
                        <input type="text" x-model="selectedBarangay.contact" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 font-mono focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                        <input type="email" x-model="selectedBarangay.email" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Barangay Hall Address</label>
                        <input type="text" x-model="selectedBarangay.hall_address" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                </div>
            </template>

            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
                <x-ui.button size="sm" icon="check" @click="saveBarangay()">Save Changes</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        </div>
    </div>
</x-layouts.admin>
