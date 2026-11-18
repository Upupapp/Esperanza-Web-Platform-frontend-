{{--
    papeles.blade.php — Citizen Document Repository, organized Barangay ->
    Individual -> Documents.

    GET /admin/papeles (ModuleController::papeles, PapelesDocument) is the
    sole source now. It was blocked until two backend gaps were fixed
    (commits aee7ec9, c60bfe5): the row never exposed citizen_name/
    citizen_barangay despite eager-loading both, so there was no field to
    group or label by; and there was no admin download route at all, only
    the citizen portal's own.

    Real lifecycle: Submitted -> Approved via verify. A government ID a
    resident keeps in their Digital ID wallet (category "Government ID",
    2026-10-13) can also go Submitted/Approved -> Rejected via return, with
    the reason the resident reads; nothing else can be returned. Verifying
    one puts the Esperanza seal on it in the resident's wallet ("Verified by
    the LGU"). It never changes the resident's identity status and never
    checks the ID with the agency that issued it, and the page says so where
    the officer decides. No Under Verification/Under
    Review/Completed. The mock's 6-status map and its avatar-by-name lookup
    (a parallel identity source, dropped from Constituents for the same
    reason) are both trimmed accordingly.
--}}
@php
    $barangayOptions = config('esperanza.barangays');
@endphp

<x-layouts.admin title="Papeles" subtitle="Citizen document repository — browse by barangay, then by resident." active="papeles">

    <div x-show="!$store.session.can('papeles')" x-cloak>
        <x-admin.access-restricted module="Papeles (Document Repository)" icon="folder-open" />
    </div>

    <div
        x-show="$store.session.can('papeles')" x-cloak
        x-data="{
            loading: true,
            failed: false,
            open: false,
            selectedBarangay: null,
            selectedCitizenId: null,
            previewDoc: null,
            search: '',
            // Find a resident in ANY barangay (owner, 2026-11-06): the search
            // inside a folder only ever looked inside that one barangay.
            globalSearch: '',
            sort: 'newest',
            showAdvancedFilters: false,
            docCategoryFilter: '',
            docStatusFilter: '',
            archiveReason: '',
            returnReason: '',
            viewSide: 'front',
            processingDoc: null,
            documents: [],
            barangayOptions: @js($barangayOptions),
            statusStyles: {
                'Submitted': ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
                'Approved': ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
                'Rejected': ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
            },
            fileIcons: { jpg: 'image', jpeg: 'image', png: 'image', pdf: 'file-text', docx: 'file-type-2' },

            async init() { await this.load(); },
            async load() {
                this.loading = true; this.failed = false;
                try {
                    const res = await $api.getAll('/admin/papeles');
                    this.documents = res.data || [];
                } catch { this.failed = true; } finally { this.loading = false; }
            },

            get visibleFolders() {
                const byBarangay = {};
                this.documents.forEach(d => {
                    if (!d.citizen_barangay) return;
                    const f = byBarangay[d.citizen_barangay] = byBarangay[d.citizen_barangay] || { citizens: new Set(), documents: 0, pending: 0 };
                    f.documents++;
                    f.citizens.add(d.citizen_id);
                    if (d.status === 'Submitted') f.pending++;
                });
                return this.barangayOptions
                    .filter(b => this.$store.session.inScope(b))
                    .map(b => ({
                        name: b,
                        citizens: byBarangay[b]?.citizens.size || 0,
                        documents: byBarangay[b]?.documents || 0,
                        pending: byBarangay[b]?.pending || 0,
                    }));
            },
            // Every resident with a document, in a barangay this officer may
            // see, matched by name or account number from what is already
            // loaded (no extra request). The first 20, by name.
            get globalResults() {
                const q = this.globalSearch.trim().toLowerCase();
                if (q.length < 2) return [];
                const byCitizen = {};
                this.documents.forEach(d => {
                    if (!d.citizen_id || !d.citizen_barangay || !this.$store.session.inScope(d.citizen_barangay)) return;
                    const c = byCitizen[d.citizen_id] = byCitizen[d.citizen_id] || { id: d.citizen_id, name: d.citizen_name, barangay: d.citizen_barangay, docs: 0 };
                    c.docs++;
                });
                return Object.values(byCitizen)
                    .filter(c => (c.name || '').toLowerCase().includes(q) || String(c.id).toLowerCase().includes(q))
                    .sort((a, b) => (a.name || '').localeCompare(b.name || ''))
                    .slice(0, 20);
            },
            openFromSearch(c) { this.openBarangay(c.barangay); this.globalSearch = ''; this.openCitizen(c.id); },
            get citizensInBarangay() {
                const byCitizen = {};
                this.documents
                    .filter(d => d.citizen_barangay === this.selectedBarangay)
                    .forEach(d => {
                        byCitizen[d.citizen_id] = byCitizen[d.citizen_id] || { id: d.citizen_id, name: d.citizen_name, barangay: d.citizen_barangay };
                    });
                return Object.values(byCitizen);
            },
            get filteredCitizens() {
                const q = this.search.trim().toLowerCase();
                let list = this.citizensInBarangay.filter(c =>
                    q === '' || (c.name || '').toLowerCase().includes(q) || (c.id || '').toLowerCase().includes(q) ||
                    this.citizenDocs(c.id).some(d => d.name.toLowerCase().includes(q) || d.type.toLowerCase().includes(q))
                );
                list = [...list].sort((a, b) => {
                    if (this.sort === 'name_asc') return (a.name || '').localeCompare(b.name || '');
                    if (this.sort === 'name_desc') return (b.name || '').localeCompare(a.name || '');
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
            get selectedCitizen() {
                if (!this.selectedCitizenId) return null;
                const docs = this.documents.filter(d => d.citizen_id === this.selectedCitizenId);
                return docs.length ? { id: this.selectedCitizenId, name: docs[0].citizen_name, barangay: docs[0].citizen_barangay } : null;
            },
            get selectedCitizenDocs() { return this.selectedCitizenId ? this.citizenDocs(this.selectedCitizenId) : []; },
            get docCategories() { return [...new Set(this.documents.map(d => d.category))].sort(); },

            fmtDate(iso) { return iso ? new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'; },
            // A type stored as a key (affidavit_of_no_pension_income_or_family_support_notarized)
            // has no spaces, so it could not wrap and ran into the next column. Shown as words.
            typeLabel(t) { const s = String(t || ''); return /\s/.test(s) || !s.includes('_') ? s : s.charAt(0).toUpperCase() + s.slice(1).replace(/_/g, ' '); },
            // A wallet ID's own name (PhilHealth ID, or the resident's for Other);
            // every other document by its type.
            isWalletId(doc) { return doc?.category === 'Government ID'; },
            docLabel(doc) { return this.isWalletId(doc) && doc.name ? doc.name : this.typeLabel(doc.type); },
            closePreview() { this.previewDoc = null; this.archiveReason = ''; this.returnReason = ''; this.viewSide = 'front'; },

            openBarangay(name) { this.selectedBarangay = name; this.selectedCitizenId = null; this.search = ''; this.sort = 'newest'; this.$nextTick(() => window.renderIcons?.()); },
            openCitizen(id) { this.selectedCitizenId = id; this.open = true; this.$nextTick(() => window.renderIcons?.()); },
            backToBarangays() { this.selectedBarangay = null; this.selectedCitizenId = null; },

            async verifyDoc(doc) {
                if (this.processingDoc) return;
                this.processingDoc = doc.id;
                try {
                    const res = await $api.post('/admin/papeles/' + doc.id + '/verify', {});
                    Object.assign(doc, res.data, { review_remarks: null });
                    this.$dispatch('toast', { message: (this.isWalletId(doc) ? this.docLabel(doc) : doc.type) + ' marked verified.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not verify this document.', variant: 'error' });
                } finally { this.processingDoc = null; }
            },
            // A wallet ID sent back to the resident with the reason they read.
            async returnDoc(doc) {
                const reason = this.returnReason.trim();
                if (this.processingDoc || reason.length < 5) return;
                if (!window.confirm('Return ' + this.docLabel(doc) + ' to the resident? They will see your reason and can replace it.')) return;
                this.processingDoc = doc.id;
                try {
                    const res = await $api.post('/admin/papeles/' + doc.id + '/return', { reason });
                    Object.assign(doc, { status: res.data.status, review_remarks: res.data.review_remarks });
                    this.returnReason = '';
                    this.$dispatch('toast', { message: this.docLabel(doc) + ' returned; the resident was told why.', variant: 'info' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not return this ID.', variant: 'error' });
                } finally { this.processingDoc = null; }
            },
            async archiveDoc(doc) {
                if (this.processingDoc || this.archiveReason.trim().length < 5) return;
                this.processingDoc = doc.id;
                try {
                    await $api.post('/admin/papeles/' + doc.id + '/archive', { reason: this.archiveReason.trim() });
                    this.documents = this.documents.filter(d => d.id !== doc.id);
                    if (this.previewDoc?.id === doc.id) this.previewDoc = null;
                    this.archiveReason = '';
                    this.$dispatch('toast', { message: doc.type + ' archived.', variant: 'warning' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not archive this document.', variant: 'error' });
                } finally { this.processingDoc = null; }
            },
            // Read in the portal (Esperanza bugs QA, 2026-10-06: staff had to
            // download a document to see it). GET .../view needs only view, and
            // returns the same short-lived link with the file type.
            viewing: null,
            viewFailed: false,
            async openView(doc, side = 'front') {
                this.viewing = null;
                this.viewFailed = false;
                this.viewSide = side;
                if (!doc?.has_file) return;
                try {
                    const res = await $api.get('/admin/papeles/' + doc.id + '/view' + (side === 'back' ? '?side=back' : ''));
                    if (this.previewDoc?.id === doc.id) this.viewing = res.data;
                } catch { this.viewFailed = true; }
            },
            get viewingImage() { return !!this.viewing?.url && /^image\//i.test(this.viewing.mime || ''); },
            get viewingPdf() { return !!this.viewing?.url && /pdf/i.test((this.viewing.mime || '') + ' ' + (this.viewing.name || '')); },
            async downloadDoc(doc, side = 'front') {
                if (!doc.has_file) { this.$dispatch('toast', { message: 'No file was uploaded for this document.', variant: 'warning' }); return; }
                try {
                    const res = await $api.get('/admin/papeles/' + doc.id + '/download' + (side === 'back' ? '?side=back' : ''));
                    window.open(res.data.url, '_blank', 'noopener');
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not get a download link.', variant: 'error' });
                }
            },
            can(action) { return this.$store.session.canPerm('papeles_documents', action); },
        }"
        {{-- No x-init="init()": Alpine runs init() by itself (it loaded twice). --}}
    >
        <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-40 opacity-80" /></div>
        <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load the document repository.'" retryExpr="load()" :retryable="true" /></div>

        <div x-show="!loading && !failed">

        {{-- ═══════════════ Level 1: Barangay folder grid (page-level) ═══════════════ --}}
        <div x-show="!selectedBarangay" x-cloak>
            <div class="surface-glass rounded-[26px] p-3 mb-4 flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="folder-open" class="w-5 h-5"></i></span>
                <div>
                    <h3 class="text-sm font-semibold text-ink">Citizen Documents by Barangay</h3>
                    <p class="text-xs text-ink-muted" x-text="visibleFolders.length + ' barangay' + (visibleFolders.length === 1 ? '' : 's') + ' in your scope'"></p>
                </div>
            </div>

            {{-- Search every barangay at once: a resident by name or account
                 number, opened straight from the result. --}}
            <div class="surface-glass rounded-[26px] p-3 mb-4" data-testid="papeles-global-search">
                <div class="relative">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input id="papeles-global-search" aria-label="Find a resident in any barangay by name or account number" type="search" x-model="globalSearch" autocomplete="off"
                           @keydown.enter.prevent="globalResults.length === 1 && openFromSearch(globalResults[0])"
                           placeholder="Find a resident in any barangay: name or account number..." class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                </div>
                <div x-show="globalSearch.trim().length >= 2" x-cloak class="mt-2.5">
                    <p class="text-[11px] text-ink-muted mb-1.5" x-text="globalResults.length ? (globalResults.length === 20 ? 'The first 20 residents found. Type more to narrow it.' : globalResults.length + ' resident' + (globalResults.length === 1 ? '' : 's') + ' found') : 'No resident with documents matches this, in the barangays in your scope.'"></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        <template x-for="c in globalResults" :key="c.id">
                            <button
                                type="button" @click="openFromSearch(c)"
                                class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 text-left hover:border-brand-300 hover:shadow-sm transition-all duration-[220ms] ease-standard"
                            >
                                <div class="w-9 h-9 text-xs rounded-full clay-blue font-extrabold flex items-center justify-center shrink-0" x-text="(c.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-700 truncate" x-text="c.name"></p>
                                    <p class="text-xs text-ink-muted truncate"><span class="font-mono" x-text="c.id"></span> · <span x-text="'Brgy. ' + c.barangay"></span></p>
                                </div>
                                <span class="text-[11px] font-semibold text-ink-muted shrink-0" x-text="c.docs + ' doc' + (c.docs === 1 ? '' : 's')"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 mb-5">
                <template x-for="f in visibleFolders" :key="f.name">
                    <button
                        type="button" @click="openBarangay(f.name)"
                        class="text-left surface-glass rounded-[26px] p-4 hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <span class="w-11 h-11 rounded-xl clay-blue font-extrabold flex items-center justify-center shrink-0"><i data-lucide="folder" class="w-5 h-5"></i></span>
                            <span x-show="f.pending > 0" class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-200" x-text="f.pending + ' pending'"></span>
                        </div>
                        <p class="text-sm font-semibold text-ink truncate" x-text="'Brgy. ' + f.name"></p>
                        <p class="text-xs text-ink-muted mt-2" x-text="f.citizens.toLocaleString() + ' citizen' + (f.citizens === 1 ? '' : 's') + ' with documents'"></p>
                        <p class="text-xs text-ink-muted" x-text="f.documents.toLocaleString() + ' document' + (f.documents === 1 ? '' : 's')"></p>
                    </button>
                </template>
                <div class="sm:col-span-2 lg:col-span-3 flex flex-col items-center text-center py-10 bg-white border border-brand-600/10 rounded-2xl" x-show="visibleFolders.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="folder-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600">No barangays in your current scope.</p>
                </div>
            </div>
        </div>

        {{-- ═══════════════ Level 2: Barangay citizen list (page-level) ═══════════════ --}}
        <div
            x-show="selectedBarangay" x-cloak
            x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        >
            <button type="button" @click="backToBarangays()" class="inline-flex items-center gap-1.5 text-xs text-ink-muted hover:text-slate-600 transition-colors mb-3">
                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Citizen Documents
            </button>

            <div class="relative bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 text-white px-6 py-5 rounded-2xl overflow-hidden mb-4">
                <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
                <div class="flex items-center gap-4 relative z-10">
                    <span class="w-14 h-14 rounded-xl bg-white/10 flex items-center justify-center shrink-0"><i data-lucide="folder-open" class="w-7 h-7"></i></span>
                    <div class="min-w-0">
                        <h2 class="text-xl font-bold leading-tight truncate" x-text="'Brgy. ' + selectedBarangay"></h2>
                        <p class="text-sm text-white/60 mt-0.5" x-text="filteredCitizens.length + ' citizens · ' + documents.filter(d => d.citizen_barangay === selectedBarangay).length + ' documents'"></p>
                    </div>
                </div>
            </div>

            <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-2.5">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Search citizen name, ID, or document" type="text" x-model="search" placeholder="Search citizen name, ID, or document..." class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                    </div>
                    <select aria-label="Sort" x-model="sort" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shrink-0 border-0 surface-pressed-sm">
                        <option value="newest">Sort: Newest</option>
                        <option value="oldest">Sort: Oldest</option>
                        <option value="name_asc">Citizen A&ndash;Z</option>
                        <option value="name_desc">Citizen Z&ndash;A</option>
                        <option value="type">Document Type</option>
                    </select>
                    <button
                        type="button" @click="showAdvancedFilters = !showAdvancedFilters"
                        class="inline-flex items-center gap-1.5 text-xs font-medium rounded-2xl px-3 py-2 border transition-colors shrink-0"
                        :class="showAdvancedFilters || docCategoryFilter || docStatusFilter ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-slate-50 border-slate-200 text-ink-muted hover:text-slate-700'"
                    ><i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> Advanced</button>
                </div>
                <div x-show="showAdvancedFilters" x-cloak x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="flex flex-wrap items-center gap-2">
                    <select aria-label="Doc document category filter" x-model="docCategoryFilter" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                        <option value="">All Document Categories</option>
                        <template x-for="c in docCategories" :key="c"><option :value="c" x-text="c"></option></template>
                    </select>
                    <select aria-label="Doc status filter" x-model="docStatusFilter" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                        <option value="">All Statuses</option>
                        <template x-for="s in Object.keys(statusStyles)" :key="s"><option :value="s" x-text="s"></option></template>
                    </select>
                    <button type="button" @click="docCategoryFilter = ''; docStatusFilter = ''" class="text-xs text-ink-muted hover:text-slate-600 transition-colors" x-show="docCategoryFilter || docStatusFilter">Clear</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                <template x-for="c in filteredCitizens" :key="c.id">
                    <button
                        type="button" @click="openCitizen(c.id)"
                        class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 text-left hover:border-brand-300 hover:shadow-sm transition-all duration-[220ms] ease-standard"
                    >
                        <div class="w-10 h-10 text-xs rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0" x-text="(c.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-700 truncate" x-text="c.name"></p>
                            <p class="text-xs text-ink-muted font-mono" x-text="c.id"></p>
                        </div>
                        <span class="text-[11px] font-semibold text-ink-muted shrink-0" x-text="citizenDocs(c.id).length + ' docs'"></span>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-ink-faint shrink-0"></i>
                    </button>
                </template>
                <div class="sm:col-span-2 lg:col-span-3 flex flex-col items-center text-center py-12 bg-white border border-brand-600/10 rounded-2xl" x-show="filteredCitizens.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
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
                        <button aria-label="Close" @click="open = false" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-20"><i data-lucide="x" class="w-4 h-4"></i></button>
                        <div class="flex items-center gap-1.5 text-xs text-white/60 mb-3 relative z-10 pr-12">
                            <button @click="open = false; selectedBarangay = null; selectedCitizenId = null" class="hover:text-white transition-colors">Citizen Documents</button>
                            <i data-lucide="chevron-right" class="w-3 h-3"></i>
                            <button @click="open = false" class="hover:text-white transition-colors" x-text="'Brgy. ' + selectedBarangay"></button>
                            <i data-lucide="chevron-right" class="w-3 h-3"></i>
                            <span class="text-white" x-text="selectedCitizen.name"></span>
                        </div>
                        <div class="flex items-center gap-4 relative z-10">
                            <div class="w-14 h-14 rounded-full bg-white/10 flex items-center justify-center font-bold text-lg uppercase shrink-0" x-text="(selectedCitizen.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('')"></div>
                            <div class="min-w-0">
                                <h2 class="text-xl font-bold leading-tight truncate" x-text="selectedCitizen.name"></h2>
                                <p class="text-sm text-white/60 mt-0.5">
                                    Account: <span class="font-mono text-white/80" x-text="selectedCitizen.id"></span>
                                    &nbsp;&middot;&nbsp;
                                    <span x-text="'Brgy. ' + selectedCitizen.barangay"></span>
                                    &nbsp;&middot;&nbsp;
                                    <span x-text="selectedCitizenDocs.length + ' documents'"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin p-6 bg-slate-50/50 space-y-4">
                        <div class="surface-glass rounded-[26px] p-4">
                            <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3">Citizen Information</h4>
                            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-3">
                                <div><dt class="text-xs text-ink-muted">Full Name</dt><dd class="text-sm font-semibold text-ink" x-text="selectedCitizen.name"></dd></div>
                                <div><dt class="text-xs text-ink-muted">Account No.</dt><dd class="text-sm font-mono font-semibold text-ink" x-text="selectedCitizen.id"></dd></div>
                                <div><dt class="text-xs text-ink-muted">Barangay</dt><dd class="text-sm font-semibold text-ink" x-text="'Brgy. ' + selectedCitizen.barangay"></dd></div>
                                <div><dt class="text-xs text-ink-muted">Document Count</dt><dd class="text-sm font-semibold text-ink" x-text="selectedCitizenDocs.length"></dd></div>
                                <div><dt class="text-xs text-ink-muted">Awaiting Verification</dt><dd class="text-sm font-semibold text-amber-600" x-text="selectedCitizenDocs.filter(d => d.status === 'Submitted').length"></dd></div>
                            </dl>
                        </div>

                        <div class="surface-glass rounded-[26px] overflow-hidden">
                            <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] p-4 pb-0">Stored Documents</h4>
                            <ul class="divide-y divide-brand-600/[.08] mt-3">
                                <template x-for="doc in selectedCitizenDocs" :key="doc.id">
                                    <li class="flex items-center gap-3 px-4 py-3">
                                        <span class="w-9 h-9 rounded-lg bg-slate-100 text-ink-muted flex items-center justify-center shrink-0"><i :data-lucide="fileIcons[doc.file_type] || 'file'" class="w-4 h-4"></i></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-slate-700 truncate" x-text="docLabel(doc)"></p>
                                            <p class="text-xs text-ink-muted" x-text="doc.category + ' · ' + fmtDate(doc.uploaded)"></p>
                                        </div>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap shrink-0" :class="(statusStyles[doc.status] || statusStyles['Submitted'])[0]">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="(statusStyles[doc.status] || statusStyles['Submitted'])[1]"></span>
                                            <span x-text="doc.status"></span>
                                        </span>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <button x-show="can('view')" @click="previewDoc = doc; openView(doc); $nextTick(() => window.renderIcons?.())" class="p-1.5 rounded-lg text-ink-muted hover:text-brand-600 hover:bg-brand-50 transition-colors" title="View"><i data-lucide="eye" class="w-4 h-4"></i></button>
                                            <button x-show="can('export')" :disabled="processingDoc === doc.id" @click="downloadDoc(doc)" class="p-1.5 rounded-xl text-ink-muted hover:text-brand-600 hover:bg-brand-50 transition-colors disabled:opacity-40" title="Download"><i data-lucide="download" class="w-4 h-4"></i></button>
                                            <button x-show="can('verify') && doc.status !== 'Approved'" :disabled="processingDoc === doc.id" @click="verifyDoc(doc)" class="p-1.5 rounded-xl text-ink-muted hover:text-emerald-600 hover:bg-emerald-50 transition-colors disabled:opacity-40" title="Verify" aria-label="Verify"><i data-lucide="check-check" class="w-4 h-4"></i></button>
                                        </div>
                                    </li>
                                </template>
                                <li class="flex flex-col items-center text-center py-10" x-show="selectedCitizenDocs.length === 0">
                                    <span class="w-14 h-14 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-3"><i data-lucide="folder-open" class="w-6 h-6"></i></span>
                                    <p class="text-sm font-medium text-slate-600">No citizen documents yet.</p>
                                    <p class="text-xs text-ink-muted mt-1 max-w-xs">Documents submitted or stored for this citizen will appear here.</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </template>
        </x-ui.modal>

        {{-- ═══════════════ Document preview (nested above the citizen profile modal) ═══════════════ --}}
        {{-- Its own focus trap (QA 2026-11-18): it sits outside the resident
             window, whose trap sent every focus here back to the eye button,
             so the Return and Archive reasons could not be typed in. A trap
             opened on top pauses the one below and hands focus back on close. --}}
        <div
            data-modal-overlay x-show="previewDoc" x-cloak
            x-trap="!!previewDoc"
            x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition duration-[150ms] ease-exit" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
            @click.self="closePreview()" @keydown.escape.window="closePreview()"
            role="dialog" aria-modal="true"
        >
            <template x-if="previewDoc">
                <div
                    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    class="bg-white rounded-2xl shadow-float w-full max-w-2xl max-h-[92vh] overflow-y-auto"
                >
                    <div class="flex items-center justify-between px-5 py-4 border-b border-brand-600/10">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-9 h-9 rounded-lg bg-slate-100 text-ink-muted flex items-center justify-center shrink-0"><i :data-lucide="fileIcons[previewDoc.file_type] || 'file'" class="w-4 h-4"></i></span>
                            <p class="text-sm font-semibold text-ink truncate" x-text="docLabel(previewDoc)"></p>
                        </div>
                        <button aria-label="Close" @click="closePreview()" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors shrink-0"><i data-lucide="x" class="w-4 h-4"></i></button>
                    </div>
                    <div class="p-5 space-y-4">
                        {{-- A wallet ID has a front and, often, a back. --}}
                        <div x-show="previewDoc.has_back" x-cloak class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-medium" role="group" aria-label="Side of the ID">
                            <button type="button" @click="openView(previewDoc, 'front')" :aria-pressed="viewSide === 'front'" class="px-3 py-1.5 rounded-lg transition-colors" :class="viewSide === 'front' ? 'bg-white text-ink shadow-card' : 'text-ink-muted hover:text-ink'">Front</button>
                            <button type="button" @click="openView(previewDoc, 'back')" :aria-pressed="viewSide === 'back'" class="px-3 py-1.5 rounded-lg transition-colors" :class="viewSide === 'back' ? 'bg-white text-ink shadow-card' : 'text-ink-muted hover:text-ink'">Back</button>
                        </div>
                        <div x-data="pdfPreview" x-effect="show(viewingImage || viewingPdf ? viewing.url : null, (viewing?.mime || '') + ' ' + (viewing?.name || ''))" x-show="viewingImage || viewingPdf" x-cloak class="max-h-[60vh] overflow-y-auto rounded-xl border border-slate-200 bg-slate-100 p-2">
                            <p x-show="loading" class="text-xs text-ink-muted text-center py-8">Opening the document…</p>
                            <p x-show="failed" x-cloak class="text-xs text-rose-600 text-center py-8">This file could not be shown here. Use Download.</p>
                            <img x-show="image" x-cloak :src="image" :alt="previewDoc.type" class="block w-full h-auto rounded-lg bg-white">
                            <div x-ref="pages"></div>
                        </div>
                        <div x-show="!viewingImage && !viewingPdf" class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 flex flex-col items-center justify-center py-14">
                            <span class="w-14 h-14 rounded-2xl bg-white text-ink-muted flex items-center justify-center mb-3 shadow-card"><i :data-lucide="fileIcons[previewDoc.file_type] || 'file'" class="w-6 h-6"></i></span>
                            <p class="text-xs text-ink-muted" x-text="previewDoc.name"></p>
                            <p class="text-[11px] text-ink-muted mt-1" x-show="previewDoc.has_file && !viewing && !viewFailed">Opening…</p>
                            <p class="text-[11px] text-ink-muted mt-1" x-show="previewDoc.has_file && (viewFailed || viewing)">This file cannot be shown here. Use Download.</p>
                            <p class="text-[11px] text-rose-400 mt-1" x-show="!previewDoc.has_file">No file was uploaded for this document.</p>
                        </div>
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-2.5">
                            <div class="min-w-0"><dt class="text-xs text-ink-muted">Citizen</dt><dd class="text-sm font-semibold text-ink break-words" x-text="selectedCitizen?.name"></dd></div>
                            <div class="min-w-0"><dt class="text-xs text-ink-muted">Barangay</dt><dd class="text-sm font-semibold text-ink break-words" x-text="'Brgy. ' + selectedBarangay"></dd></div>
                            <div class="min-w-0"><dt class="text-xs text-ink-muted">Document Type</dt><dd class="text-sm font-semibold text-ink break-words [overflow-wrap:anywhere]" x-text="typeLabel(previewDoc.type)"></dd></div>
                            <div class="min-w-0"><dt class="text-xs text-ink-muted">Submitted</dt><dd class="text-sm font-semibold text-ink" x-text="fmtDate(previewDoc.uploaded)"></dd></div>
                            <template x-if="isWalletId(previewDoc)">
                                <div class="contents">
                                    <div class="min-w-0"><dt class="text-xs text-ink-muted">Issued By</dt><dd class="text-sm font-semibold text-ink break-words" x-text="previewDoc.issuer || '—'"></dd></div>
                                    <div class="min-w-0"><dt class="text-xs text-ink-muted">Expires</dt><dd class="text-sm font-semibold text-ink" x-text="previewDoc.expires_on ? fmtDate(previewDoc.expires_on) : 'Not given'"></dd></div>
                                    <div class="min-w-0"><dt class="text-xs text-ink-muted">Sides</dt><dd class="text-sm font-semibold text-ink" x-text="previewDoc.has_back ? 'Front and back' : 'Front only'"></dd></div>
                                    <div class="min-w-0"><dt class="text-xs text-ink-muted">Verified</dt><dd class="text-sm font-semibold text-ink" x-text="previewDoc.status === 'Approved' && previewDoc.verified_at ? fmtDate(previewDoc.verified_at) : 'Not yet'"></dd></div>
                                    {{-- Didit read and approved it (2026-10-14): at registration, or scanned for the wallet. --}}
                                    <div class="min-w-0"><dt class="text-xs text-ink-muted">Checked by Didit</dt><dd class="text-sm font-semibold" :class="previewDoc.checked_by_didit_at ? 'text-sky-700' : 'text-ink'" x-text="previewDoc.checked_by_didit_at ? fmtDate(previewDoc.checked_by_didit_at) : 'No'"></dd></div>
                                </div>
                            </template>
                        </dl>
                        {{-- What the ID says, as the resident entered it (2026-10-13), to check
                             against the photo: only with view_sensitive, and every
                             reveal is logged server side. --}}
                        <div x-show="isWalletId(previewDoc) && viewing?.id_details" x-cloak class="rounded-xl border border-slate-200 px-3 py-2.5">
                            <p class="text-xs font-semibold text-ink mb-1.5">What the ID says (as the resident entered it)</p>
                            <p x-show="viewing?.id_details?.restricted" class="text-xs text-ink-muted">Hidden: seeing ID numbers and personal details needs the Papeles "view sensitive" permission.</p>
                            <dl x-show="viewing?.id_details && !viewing.id_details.restricted" class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-xs">
                                <template x-for="row in [['ID number', viewing?.id_details?.id_number], ['Name', viewing?.id_details?.holder_name], ['Date of birth', viewing?.id_details?.birthdate], ['Sex', viewing?.id_details?.sex], ['Address', viewing?.id_details?.address], ...Object.entries(viewing?.id_details?.details || {}).map(([k, v]) => [k.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase()), v])]" :key="row[0]">
                                    <div class="min-w-0"><dt class="text-ink-muted" x-text="row[0]"></dt><dd class="font-semibold text-ink break-words" x-text="row[1] || '—'"></dd></div>
                                </template>
                            </dl>
                        </div>
                        <p x-show="isWalletId(previewDoc)" x-cloak class="text-xs text-ink-muted rounded-xl bg-slate-50 border border-slate-200 px-3 py-2">An ID the resident keeps in their Digital ID wallet. Verifying it puts the Esperanza seal on it in their wallet. It does not change the resident's identity status, and it does not check the ID with the agency that issued it. "Checked by Didit" means Didit read and approved the ID; the seal is still only yours to give.</p>
                        <p x-show="previewDoc.status === 'Rejected' && previewDoc.review_remarks" x-cloak class="text-xs text-rose-700" x-text="'Returned: ' + (previewDoc.review_remarks || '')"></p>
                        <div class="flex items-center justify-between gap-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-semibold tracking-[0.02em] ring-1 ring-inset whitespace-nowrap" :class="(statusStyles[previewDoc.status] || statusStyles['Submitted'])[0]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="(statusStyles[previewDoc.status] || statusStyles['Submitted'])[1]"></span>
                                <span x-text="previewDoc.status"></span>
                            </span>
                            <button x-show="can('verify') && previewDoc.status !== 'Approved'" :disabled="processingDoc === previewDoc.id" @click="verifyDoc(previewDoc)" class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 hover:text-emerald-800 transition-colors disabled:opacity-40">
                                <i data-lucide="check-check" class="w-3.5 h-3.5"></i> Verify
                            </button>
                        </div>
                        <div x-show="can('verify') && isWalletId(previewDoc) && previewDoc.status !== 'Rejected'" x-cloak class="pt-3 border-t border-brand-600/10">
                            <label class="text-xs font-medium text-ink-muted mb-1 block" for="papeles-return-reason">Return this ID to the resident (they will read the reason, min. 5 characters)</label>
                            <div class="flex items-center gap-2">
                                <input id="papeles-return-reason" type="text" maxlength="500" x-model="returnReason" placeholder="e.g. The photo is too blurry to read" class="flex-1 text-xs rounded-xl border border-slate-200 py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                                <button :disabled="processingDoc === previewDoc.id || returnReason.trim().length < 5" @click="returnDoc(previewDoc)" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-xl border border-rose-200 text-rose-700 bg-rose-50 hover:bg-rose-100 transition disabled:opacity-40 disabled:pointer-events-none">
                                    <i data-lucide="undo-2" class="w-3.5 h-3.5"></i> Return
                                </button>
                            </div>
                        </div>
                        <div x-show="can('edit')" class="pt-3 border-t border-brand-600/10">
                            <label class="text-xs font-medium text-ink-muted mb-1 block" for="papeles-archive-reason">Archive this document (requires a reason, min. 5 characters)</label>
                            <div class="flex items-center gap-2">
                                <input id="papeles-archive-reason" type="text" x-model="archiveReason" placeholder="Reason for archiving" class="flex-1 text-xs rounded-xl border border-slate-200 py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                                <button :disabled="processingDoc === previewDoc.id || archiveReason.trim().length < 5" @click="archiveDoc(previewDoc)" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-xl border border-rose-200 text-rose-700 bg-rose-50 hover:bg-rose-100 transition disabled:opacity-40 disabled:pointer-events-none">
                                    <i data-lucide="archive" class="w-3.5 h-3.5"></i> Archive
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-brand-600/10 bg-slate-50/60">
                        <x-ui.button variant="secondary" size="sm" @click="closePreview()">Close</x-ui.button>
                        <x-ui.button size="sm" icon="download" x-show="can('export')" @click="downloadDoc(previewDoc, viewSide)"><span x-text="previewDoc.has_back ? 'Download ' + viewSide : 'Download'"></span></x-ui.button>
                    </div>
                </div>
            </template>
        </div>

        </div>
    </div>
</x-layouts.admin>
