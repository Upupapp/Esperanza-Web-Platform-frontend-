{{--
    papeles.blade.php — Citizen Document Repository, organized Barangay ->
    Individual -> Documents. Promoted from the "Citizen Document Storage"
    tab that used to live inside Dokyu into its own top-level module, since
    it's used across Dokyu, Tulong, and Constituents alike, not just Dokyu.
    Reuses the real $individuals records from config/esperanza_constituents.php
    -- no parallel citizen identity is invented here.
--}}
@php
    $storageBarangays = config('esperanza.barangays');
    $storageIndividuals = config('esperanza_constituents.individuals');
    // Citizen name -> avatar map, built here rather than in a config file —
    // see config/esperanza_citizen_avatars.php's own header for why a
    // config file can't safely call config() on another config file from
    // its own top level (Laravel loads them in filename order during
    // bootstrap) — so a resident's real picture shows up here too, not
    // just on their own Constituents profile.
    $citizenAvatarData = config('esperanza_citizen_avatars');
    $generatedAvatarFor = function ($acctId) use ($citizenAvatarData) {
        $bg = $citizenAvatarData['backgrounds'][crc32($acctId) % count($citizenAvatarData['backgrounds'])];
        return 'https://api.dicebear.com/9.x/personas/svg?seed=' . urlencode($acctId) . '&backgroundColor=' . $bg;
    };
    $citizenAvatars = [];
    foreach ($storageIndividuals as $ind) {
        if (empty($ind['citizen_account_id'])) {
            continue;
        }
        $acctId = $ind['citizen_account_id'];
        $citizenAvatars[$ind['name']] = isset($citizenAvatarData['photoByAccountId'][$acctId])
            ? asset($citizenAvatarData['photoByAccountId'][$acctId])
            : $generatedAvatarFor($acctId);
    }

    // Small, realistic pool of supporting-document types citizens commonly
    // have on file, reusing the same category taxonomy Dokyu already uses
    // where the document maps onto an existing Dokyu category, plus two
    // categories (Identity / Verification, Supporting Documents) for
    // generic attachments that aren't themselves a Dokyu deliverable.
    $docTypePool = [
        ['type' => 'Valid ID (PhilSys National ID)', 'category' => 'Identity / Verification', 'file_type' => 'jpg'],
        ['type' => '2x2 ID Photo', 'category' => 'Identity / Verification', 'file_type' => 'jpg'],
        ['type' => 'PSA Birth Certificate', 'category' => 'Civil Registry', 'file_type' => 'pdf'],
        ['type' => 'Marriage Certificate', 'category' => 'Civil Registry', 'file_type' => 'pdf'],
        ['type' => 'Barangay Certificate of Indigency', 'category' => 'Barangay Certificates', 'file_type' => 'pdf'],
        ['type' => 'Barangay Residency', 'category' => 'Barangay Certificates', 'file_type' => 'pdf'],
        ['type' => 'Cedula (Community Tax Certificate)', 'category' => 'Tax & Property', 'file_type' => 'jpg'],
        ['type' => 'DTI Registration', 'category' => 'Business & Permits', 'file_type' => 'pdf'],
        ['type' => 'Certificate of Employment', 'category' => 'Supporting Documents', 'file_type' => 'docx'],
        ['type' => 'Proof of Income', 'category' => 'Supporting Documents', 'file_type' => 'pdf'],
    ];
    $storageStatuses = ['Submitted', 'Under Verification', 'Under Review', 'Approved', 'Completed', 'Rejected'];
    $storageMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];

    // One representative citizen per barangay (two for the six barangays
    // with richer constituent data) -- real RES-#### ids from
    // config/esperanza_constituents.php, not invented identities.
    $storageCitizenIds = [
        'RES-2026-023', 'RES-2026-024', 'RES-2026-028', 'RES-2026-030',
        'RES-2026-101', 'RES-2026-104', 'RES-2026-106', 'RES-2026-108',
        'RES-2026-121', 'RES-2026-122', 'RES-2026-123', 'RES-2026-124', 'RES-2026-125',
        'RES-2026-126', 'RES-2026-127', 'RES-2026-128', 'RES-2026-129', 'RES-2026-130', 'RES-2026-131',
    ];
    $storageIndividualsById = collect($storageIndividuals)->keyBy('id');

    // Real citizen accounts (for actually-verified IDs on file) and real
    // payment records (for "Resibo") — same source data Constituents and
    // Payments themselves use, so a citizen's Papeles folder shows what
    // they actually uploaded/paid instead of a parallel invented set.
    // Matched on a normalized name (middle initials stripped) rather than
    // exact equality, since Payments records citizens without a middle
    // initial (e.g. "Maria Fe Bacaltos") while Constituents carries the
    // full name ("Maria Fe A. Bacaltos") for the same person.
    $normalizePersonName = function ($name) {
        $parts = preg_split('/\s+/', trim($name));
        $parts = array_filter($parts, fn ($p) => !preg_match('/^[A-Z]\.?$/', $p));
        return strtolower(implode(' ', $parts));
    };
    $citizenAccountsById = collect(config('esperanza_citizens')['accounts'])->keyBy('id');
    $paymentsByCitizenName = collect(config('esperanza_payments'))->groupBy(fn ($p) => $normalizePersonName($p['citizen']));

    $citizenDocuments = [];
    $docSeq = 1;
    foreach ($storageCitizenIds as $ci => $citizenId) {
        $person = $storageIndividualsById->get($citizenId);
        if (!$person) {
            continue;
        }
        $docCount = 2 + ($ci % 3); // 2-4 documents per citizen, deterministic spread
        for ($d = 0; $d < $docCount; $d++) {
            $tpl = $docTypePool[($ci * 3 + $d) % count($docTypePool)];
            $citizenDocuments[] = [
                'id' => sprintf('CD-%04d', $docSeq++),
                'citizen_id' => $citizenId,
                'name' => str_replace([' ', '(', ')'], ['-', '', ''], $tpl['type']) . '.' . $tpl['file_type'],
                'category' => $tpl['category'],
                'type' => $tpl['type'],
                'file_type' => $tpl['file_type'],
                'status' => $storageStatuses[($ci + $d) % count($storageStatuses)],
                'uploaded' => $storageMonths[($ci + $d) % count($storageMonths)] . ' ' . (1 + (($ci * 7 + $d) % 27)) . ', 2026',
            ];
        }

        // Real verified IDs — replaces the synthetic pool entirely for this
        // citizen with what's actually on file for their linked account.
        $account = !empty($person['citizen_account_id']) ? $citizenAccountsById->get($person['citizen_account_id']) : null;
        if ($account && $account['status'] === 'Approved' && !empty($account['verifiedIds'])) {
            foreach ($account['verifiedIds'] as $idDoc) {
                $citizenDocuments[] = [
                    'id' => sprintf('CD-%04d', $docSeq++),
                    'citizen_id' => $citizenId,
                    'name' => str_replace([' ', '(', ')'], ['-', '', ''], $idDoc['type']) . '.jpg',
                    'category' => 'Identity / Verification',
                    'type' => $idDoc['type'],
                    'file_type' => 'jpg',
                    'status' => 'Approved',
                    'uploaded' => $account['registeredAt'] ?? 'Jul 1, 2026',
                ];
            }
        }

        // Real payment receipts ("Resibo") — same records shown in Payments,
        // matched by citizen name since payments don't carry a resident id.
        foreach ($paymentsByCitizenName->get($normalizePersonName($person['name']), collect()) as $pmt) {
            if (empty($pmt['receipt'])) {
                continue;
            }
            $citizenDocuments[] = [
                'id' => sprintf('CD-%04d', $docSeq++),
                'citizen_id' => $citizenId,
                'name' => $pmt['receipt'],
                'category' => 'Payment Receipt',
                'type' => 'Resibo — ' . $pmt['item'],
                'file_type' => str_contains($pmt['receipt'], '.pdf') ? 'pdf' : 'jpg',
                'status' => $pmt['status'],
                'uploaded' => $pmt['date'],
            ];
        }
    }

    // Per-barangay aggregate counts for the folder grid -- citizens count
    // uses ALL real constituents in that barangay (not just the ones with
    // mock documents above), documents/pending counts come from the mock set.
    $storageCitizensByBarangay = collect($storageIndividuals)->groupBy('barangay');
    $storageDocsByCitizen = collect($citizenDocuments)->groupBy('citizen_id');
    $storageFinalStatuses = ['Approved', 'Completed', 'Rejected', 'Archived'];
    $barangayFolders = collect($storageBarangays)->map(function ($brgy) use ($storageCitizensByBarangay, $storageIndividualsById, $storageDocsByCitizen, $storageFinalStatuses) {
        $residents = $storageCitizensByBarangay->get($brgy, collect());
        $docs = $residents->flatMap(fn ($r) => $storageDocsByCitizen->get($r['id'], collect()));
        return [
            'name' => $brgy,
            'citizens' => $residents->count(),
            'documents' => $docs->count(),
            'pending' => $docs->filter(fn ($d) => !in_array($d['status'], $storageFinalStatuses))->count(),
        ];
    })->values()->all();
@endphp

<x-layouts.admin title="Papeles" subtitle="Citizen document repository — browse by barangay, then by resident." active="papeles">

    <div x-show="!$store.session.can('papeles')" x-cloak>
        <x-admin.access-restricted module="Papeles (Document Repository)" icon="folder-open" />
    </div>

    <div
        x-show="$store.session.can('papeles')" x-cloak
        x-data="{
            open: false,
            selectedBarangay: null,
            selectedCitizenId: null,
            previewDoc: null,
            search: '',
            sort: 'newest',
            showAdvancedFilters: false,
            docCategoryFilter: '',
            docStatusFilter: '',
            folders: @js($barangayFolders),
            individuals: @js($storageIndividuals),
            citizenAvatars: @js($citizenAvatars),
            documents: @js($citizenDocuments),
            statusStyles: {
                'Submitted': ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
                'Under Verification': ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
                'Under Review': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
                'Approved': ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
                'Rejected': ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
                'Completed': ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
            },
            fileIcons: { jpg: 'image', jpeg: 'image', png: 'image', pdf: 'file-text', docx: 'file-type-2' },
            get visibleFolders() {
                return this.folders.filter(f => this.$store.session.inScope(f.name));
            },
            get barangayIndividuals() {
                return this.individuals.filter(i => i.barangay === this.selectedBarangay);
            },
            get filteredCitizens() {
                const q = this.search.trim().toLowerCase();
                let list = this.barangayIndividuals.filter(i =>
                    q === '' || i.name.toLowerCase().includes(q) || i.id.toLowerCase().includes(q) ||
                    this.citizenDocs(i.id).some(d => d.name.toLowerCase().includes(q) || d.type.toLowerCase().includes(q))
                );
                list = [...list].sort((a, b) => {
                    if (this.sort === 'name_asc') return a.name.localeCompare(b.name);
                    if (this.sort === 'name_desc') return b.name.localeCompare(a.name);
                    return 0;
                });
                return list;
            },
            citizenDocs(citizenId) {
                let docs = this.documents.filter(d => d.citizen_id === citizenId);
                if (this.docCategoryFilter) docs = docs.filter(d => d.category === this.docCategoryFilter);
                if (this.docStatusFilter) docs = docs.filter(d => d.status === this.docStatusFilter);
                if (this.sort === 'newest') docs = [...docs].sort((a, b) => new Date(b.uploaded) - new Date(a.uploaded));
                if (this.sort === 'oldest') docs = [...docs].sort((a, b) => new Date(a.uploaded) - new Date(b.uploaded));
                if (this.sort === 'type') docs = [...docs].sort((a, b) => a.type.localeCompare(b.type));
                return docs;
            },
            get selectedCitizen() { return this.individuals.find(i => i.id === this.selectedCitizenId) || null; },
            get selectedCitizenDocs() { return this.selectedCitizenId ? this.citizenDocs(this.selectedCitizenId) : []; },
            get docCategories() { return [...new Set(this.documents.map(d => d.category))].sort(); },
            openBarangay(name) { this.selectedBarangay = name; this.selectedCitizenId = null; this.search = ''; this.sort = 'newest'; this.$nextTick(() => window.renderIcons?.()); },
            openCitizen(id) { this.selectedCitizenId = id; this.open = true; this.$nextTick(() => window.renderIcons?.()); },
            backToBarangays() { this.selectedBarangay = null; this.selectedCitizenId = null; },
            backToCitizenList() { this.open = false; },
            can(action) { return this.$store.session.canPerm('papeles_documents', action); },
        }"
    >
        {{-- ═══════════════ Level 1: Barangay folder grid (page-level) ═══════════════ --}}
        <div x-show="!selectedBarangay" x-cloak>
            <div class="bg-white border border-slate-100 rounded-2xl shadow-card p-3 mb-4 flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="folder-open" class="w-5 h-5"></i></span>
                <div>
                    <h3 class="text-sm font-semibold text-navy-900">Citizen Documents by Barangay</h3>
                    <p class="text-xs text-slate-400" x-text="visibleFolders.length + ' barangay' + (visibleFolders.length === 1 ? '' : 's') + ' in your scope'"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 mb-5">
                <template x-for="f in visibleFolders" :key="f.name">
                    <button
                        type="button" @click="openBarangay(f.name)"
                        class="text-left bg-white rounded-2xl border border-slate-100 shadow-card p-4 hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center shrink-0"><i data-lucide="folder" class="w-5 h-5"></i></span>
                            <span x-show="f.pending > 0" class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-200" x-text="f.pending + ' pending'"></span>
                        </div>
                        <p class="text-sm font-semibold text-navy-900 truncate" x-text="'Brgy. ' + f.name"></p>
                        <p class="text-xs text-slate-400 mt-2" x-text="f.citizens.toLocaleString() + ' citizen' + (f.citizens === 1 ? '' : 's')"></p>
                        <p class="text-xs text-slate-400" x-text="f.documents.toLocaleString() + ' document' + (f.documents === 1 ? '' : 's')"></p>
                    </button>
                </template>
                <div class="sm:col-span-2 lg:col-span-3 flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl" x-show="visibleFolders.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="folder-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600">No barangays in your current scope.</p>
                </div>
            </div>
        </div>

        {{-- ═══════════════ Level 2: Barangay citizen list (page-level) ═══════════════ --}}
        <div
            x-show="selectedBarangay" x-cloak
            x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        >
            <button type="button" @click="backToBarangays()" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-600 transition-colors mb-3">
                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Citizen Documents
            </button>

            <div class="relative bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 text-white px-6 py-5 rounded-2xl overflow-hidden mb-4">
                <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
                <div class="flex items-center gap-4 relative z-10">
                    <span class="w-14 h-14 rounded-xl bg-white/10 flex items-center justify-center shrink-0"><i data-lucide="folder-open" class="w-7 h-7"></i></span>
                    <div class="min-w-0">
                        <h2 class="text-xl font-bold leading-tight truncate" x-text="'Brgy. ' + selectedBarangay"></h2>
                        <p class="text-sm text-white/60 mt-0.5" x-text="filteredCitizens.length + ' citizens · ' + documents.filter(d => barangayIndividuals.some(i => i.id === d.citizen_id)).length + ' documents'"></p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-100 rounded-2xl shadow-card p-3 mb-4 space-y-2.5">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="text" x-model="search" placeholder="Search citizen name, ID, or document..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                    <select x-model="sort" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shrink-0">
                        <option value="newest">Sort: Newest</option>
                        <option value="oldest">Sort: Oldest</option>
                        <option value="name_asc">Citizen A&ndash;Z</option>
                        <option value="name_desc">Citizen Z&ndash;A</option>
                        <option value="type">Document Type</option>
                    </select>
                    <button
                        type="button" @click="showAdvancedFilters = !showAdvancedFilters"
                        class="inline-flex items-center gap-1.5 text-xs font-medium rounded-xl px-3 py-2 border transition-colors shrink-0"
                        :class="showAdvancedFilters || docCategoryFilter || docStatusFilter ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-slate-50 border-slate-200 text-slate-500 hover:text-slate-700'"
                    ><i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> Advanced</button>
                </div>
                <div x-show="showAdvancedFilters" x-cloak x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="flex flex-wrap items-center gap-2">
                    <select x-model="docCategoryFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        <option value="">All Document Categories</option>
                        <template x-for="c in docCategories" :key="c"><option :value="c" x-text="c"></option></template>
                    </select>
                    <select x-model="docStatusFilter" class="text-xs rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        <option value="">All Statuses</option>
                        <template x-for="s in Object.keys(statusStyles)" :key="s"><option :value="s" x-text="s"></option></template>
                    </select>
                    <button type="button" @click="docCategoryFilter = ''; docStatusFilter = ''" class="text-xs text-slate-400 hover:text-slate-600 transition-colors" x-show="docCategoryFilter || docStatusFilter">Clear</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                <template x-for="c in filteredCitizens" :key="c.id">
                    <button
                        type="button" @click="openCitizen(c.id)"
                        class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 text-left hover:border-brand-300 hover:shadow-sm transition-all duration-[220ms] ease-standard"
                    >
                        <img x-show="citizenAvatars[c.name]" :src="citizenAvatars[c.name]" class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow-sm shrink-0" :alt="c.name">
                        <div x-show="!citizenAvatars[c.name]" class="w-10 h-10 text-xs rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0" x-text="(c.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-700 truncate" x-text="c.name"></p>
                            <p class="text-xs text-slate-400 font-mono" x-text="c.id"></p>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-400 shrink-0" x-text="citizenDocs(c.id).length + ' docs'"></span>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 shrink-0"></i>
                    </button>
                </template>
                <div class="sm:col-span-2 lg:col-span-3 flex flex-col items-center text-center py-12 bg-white border border-slate-100 rounded-2xl" x-show="filteredCitizens.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600">No citizens match this search.</p>
                </div>
            </div>
        </div>

        {{-- ═══════════════ Level 3: Citizen document profile (modal — leaf action) ═══════════════ --}}
        <x-ui.modal maxWidth="3xl" :bare="true">
            <template x-if="selectedCitizen">
                <div class="flex flex-col min-h-0" style="max-height: 88vh;">
                    <div class="relative shrink-0 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 text-white px-6 py-5 rounded-t-2xl overflow-hidden">
                        <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
                        <button @click="open = false" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-20"><i data-lucide="x" class="w-4 h-4"></i></button>
                        <div class="flex items-center gap-1.5 text-xs text-white/60 mb-3 relative z-10 pr-12">
                            <button @click="open = false; selectedBarangay = null; selectedCitizenId = null" class="hover:text-white transition-colors">Citizen Documents</button>
                            <i data-lucide="chevron-right" class="w-3 h-3"></i>
                            <button @click="open = false" class="hover:text-white transition-colors" x-text="'Brgy. ' + selectedBarangay"></button>
                            <i data-lucide="chevron-right" class="w-3 h-3"></i>
                            <span class="text-white" x-text="selectedCitizen.name"></span>
                        </div>
                        <div class="flex items-center gap-4 relative z-10">
                            <img x-show="citizenAvatars[selectedCitizen.name]" :src="citizenAvatars[selectedCitizen.name]" class="w-14 h-14 rounded-full object-cover ring-2 ring-white/20 shrink-0" :alt="selectedCitizen.name">
                            <div x-show="!citizenAvatars[selectedCitizen.name]" class="w-14 h-14 rounded-full bg-white/10 flex items-center justify-center font-bold text-lg uppercase shrink-0" x-text="(selectedCitizen.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('')"></div>
                            <div class="min-w-0">
                                <h2 class="text-xl font-bold leading-tight truncate" x-text="selectedCitizen.name"></h2>
                                <p class="text-sm text-white/60 mt-0.5">
                                    ID: <span class="font-mono text-white/80" x-text="selectedCitizen.id"></span>
                                    &nbsp;&middot;&nbsp;
                                    <span x-text="'Brgy. ' + selectedCitizen.barangay"></span>
                                    &nbsp;&middot;&nbsp;
                                    <span x-text="selectedCitizenDocs.length + ' documents'"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin p-6 bg-slate-50/50 space-y-4">
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Citizen Information</h4>
                            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-3">
                                <div><dt class="text-xs text-slate-400">Full Name</dt><dd class="text-sm font-semibold text-navy-900" x-text="selectedCitizen.name"></dd></div>
                                <div><dt class="text-xs text-slate-400">Resident ID</dt><dd class="text-sm font-mono font-semibold text-navy-900" x-text="selectedCitizen.id"></dd></div>
                                <div><dt class="text-xs text-slate-400">Barangay</dt><dd class="text-sm font-semibold text-navy-900" x-text="'Brgy. ' + selectedCitizen.barangay"></dd></div>
                                <div><dt class="text-xs text-slate-400">Verification</dt><dd class="text-sm font-semibold" :class="selectedCitizen.verification === 'Verified' ? 'text-emerald-600' : 'text-slate-500'" x-text="selectedCitizen.verification || '—'"></dd></div>
                                <div><dt class="text-xs text-slate-400">Document Count</dt><dd class="text-sm font-semibold text-navy-900" x-text="selectedCitizenDocs.length"></dd></div>
                                <div><dt class="text-xs text-slate-400">Submitted</dt><dd class="text-sm font-semibold text-amber-600" x-text="selectedCitizenDocs.filter(d => ['Submitted','Under Verification','Under Review'].includes(d.status)).length"></dd></div>
                            </dl>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider p-4 pb-0">Stored Documents</h4>
                            <ul class="divide-y divide-slate-100 mt-3">
                                <template x-for="doc in selectedCitizenDocs" :key="doc.id">
                                    <li class="flex items-center gap-3 px-4 py-3">
                                        <span class="w-9 h-9 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0"><i :data-lucide="fileIcons[doc.file_type] || 'file'" class="w-4 h-4"></i></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-slate-700 truncate" x-text="doc.type"></p>
                                            <p class="text-xs text-slate-400" x-text="doc.category + ' · ' + doc.uploaded"></p>
                                        </div>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap shrink-0" :class="(statusStyles[doc.status] || statusStyles['Submitted'])[0]">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="(statusStyles[doc.status] || statusStyles['Submitted'])[1]"></span>
                                            <span x-text="doc.status"></span>
                                        </span>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <button x-show="can('view')" @click="previewDoc = doc; $nextTick(() => window.renderIcons?.())" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition-colors" title="View"><i data-lucide="eye" class="w-4 h-4"></i></button>
                                            <button x-show="can('export')" @click="$dispatch('toast', { message: 'Downloading ' + doc.name + ' — frontend preview only.', variant: 'default' })" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition-colors" title="Download"><i data-lucide="download" class="w-4 h-4"></i></button>
                                            <button x-show="can('verify') && doc.status !== 'Approved'" @click="doc.status = 'Approved'; $dispatch('toast', { message: doc.type + ' marked verified.', variant: 'success' })" class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Verify"><i data-lucide="check-check" class="w-4 h-4"></i></button>
                                        </div>
                                    </li>
                                </template>
                                <li class="flex flex-col items-center text-center py-10" x-show="selectedCitizenDocs.length === 0">
                                    <span class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3"><i data-lucide="folder-open" class="w-6 h-6"></i></span>
                                    <p class="text-sm font-medium text-slate-600">No citizen documents yet.</p>
                                    <p class="text-xs text-slate-400 mt-1 max-w-xs">Documents submitted or stored for this citizen will appear here.</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </template>
        </x-ui.modal>

        {{-- ═══════════════ Document preview (nested above the citizen profile modal) ═══════════════ --}}
        <div
            x-show="previewDoc" x-cloak
            x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition duration-[150ms] ease-exit" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
            @click.self="previewDoc = null" @keydown.escape.window="previewDoc = null"
            role="dialog" aria-modal="true"
        >
            <template x-if="previewDoc">
                <div
                    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    class="bg-white rounded-2xl shadow-float w-full max-w-lg"
                >
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-9 h-9 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0"><i :data-lucide="fileIcons[previewDoc.file_type] || 'file'" class="w-4 h-4"></i></span>
                            <p class="text-sm font-semibold text-navy-900 truncate" x-text="previewDoc.type"></p>
                        </div>
                        <button @click="previewDoc = null" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors shrink-0"><i data-lucide="x" class="w-4 h-4"></i></button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 flex flex-col items-center justify-center py-14">
                            <span class="w-14 h-14 rounded-2xl bg-white text-slate-400 flex items-center justify-center mb-3 shadow-card"><i :data-lucide="fileIcons[previewDoc.file_type] || 'file'" class="w-6 h-6"></i></span>
                            <p class="text-xs text-slate-400" x-text="previewDoc.name"></p>
                            <p class="text-[11px] text-slate-300 mt-1">Frontend preview — no real file is stored.</p>
                        </div>
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-2.5">
                            <div><dt class="text-xs text-slate-400">Citizen</dt><dd class="text-sm font-semibold text-navy-900" x-text="selectedCitizen?.name"></dd></div>
                            <div><dt class="text-xs text-slate-400">Barangay</dt><dd class="text-sm font-semibold text-navy-900" x-text="'Brgy. ' + selectedBarangay"></dd></div>
                            <div><dt class="text-xs text-slate-400">Document Type</dt><dd class="text-sm font-semibold text-navy-900" x-text="previewDoc.type"></dd></div>
                            <div><dt class="text-xs text-slate-400">Submitted</dt><dd class="text-sm font-semibold text-navy-900" x-text="previewDoc.uploaded"></dd></div>
                        </dl>
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-semibold tracking-[0.02em] ring-1 ring-inset whitespace-nowrap" :class="(statusStyles[previewDoc.status] || statusStyles['Submitted'])[0]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="(statusStyles[previewDoc.status] || statusStyles['Submitted'])[1]"></span>
                                <span x-text="previewDoc.status"></span>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-slate-100 bg-slate-50/60">
                        <x-ui.button variant="secondary" size="sm" @click="previewDoc = null">Close</x-ui.button>
                        <x-ui.button size="sm" icon="download" x-show="can('export')" @click="$dispatch('toast', { message: 'Downloading ' + previewDoc.name + ' — frontend preview only.', variant: 'default' })">Download</x-ui.button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-layouts.admin>
