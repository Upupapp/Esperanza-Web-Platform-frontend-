@php
    // All four Constituents tabs now load from the real API in loadRegistry()
    // below -- nothing in this file reads config('esperanza_constituents')
    // any more.
    $barangayOptions = config('esperanza.barangays');
    $tab  = $tab  ?? 'registry';
    $view = $view ?? 'individuals';
@endphp

<x-layouts.admin title="Constituents" subtitle="LGU constituent registry — residents, families, households." active="constituents">
{{-- The notes inside this x-data are Blade comments, not JS // comments: a JS
     comment ships in every Constituents page, and that kept the page over its
     120 KB gz budget (gate.sh, 2026-11-18). Write new ones the same way. --}}
<div
    x-data="{
        tab:  '{{ $tab }}',
        view: '{{ $view }}',
        loading: true,
        failed: false,
        {{-- Set when a registry list has more rows than one load fetches (getAll's
             bound), so the screen says it is a partial view instead of implying
             these are every resident. --}}
        registryTruncated: false,
        registryTotals: { residents: 0, households: 0, families: 0 },
        {{-- The six tiles' numbers from one-row pages, apart from the lists (UI
             sweep 2026-11-18): the tiles counted the lists, so every role read
             six 0s for as long as their pages took, while Occupations said 341. --}}
        tileCounts: null,
        {{-- Once the registry is bigger than one load (getAll's 2,000), search
             and the filters go to the server, so they find every resident
             (live test 2026-10-12: after a 2,000-row import they missed most). --}}
        remote: { key: '', rows: null, loading: false, total: 0, page: 1, lastPage: 1, params: {} },
        {{-- Fifty rows a list at a time (live test 2026-10-12): with thousands
             of residents every list here drew them all, hidden tabs included,
             and the page took minutes to open. x-admin.pager draws the controls. --}}
        pageSize: 50,
        pages: {},
        pageCount(list) { return Math.max(1, Math.ceil((list || []).length / this.pageSize)); },
        pageOf(key, list) { return Math.min(Math.max(1, this.pages[key] || 1), this.pageCount(list)); },
        paged(list, key) { const p = this.pageOf(key, list); return (list || []).slice((p - 1) * this.pageSize, p * this.pageSize); },
        turnPage(key, step, list) { this.pages = { ...this.pages, [key]: this.pageOf(key, list) + step }; this.$nextTick(() => window.renderIcons?.()); },
        get filterKey() { return [this.tab, this.view, this.search, this.barangayFilter, this.verificationFilter, this.statusFilter, this.tagFilter, this.dqTypeFilter, this.dqStatusFilter, this.cvSearch, this.cvStatusFilter, this.cvBarangayFilter, this.cvTagFilter, this.cvType].join('|'); },
        remoteTimer: null,
        citizensByRef: {},
        {{-- The resident detail colours profile completeness by how far along
             it is; it called this helper, which did not exist, and threw. --}}
        completionTextColor(pct) {
            const p = Number(pct) || 0;
            return p >= 80 ? 'text-emerald-700' : (p >= 50 ? 'text-amber-700' : 'text-rose-700');
        },
        individuals:  [],
        families:     [],
        households:   [],
        citizens:     [],
        qualityIssues: [],
        encoders:     [],
        dqScanning:   false,
        barangayCoverage: [],
        {{-- Occupations waiting for a DRRM classification (the Occupations tab). --}}
        lvReviewCount: 0,
        coverageMunicipal: null,
        barangayOptions: @js($barangayOptions),

        async init() {
            this.$watch('remoteKey', () => this.queueRemoteSearch());
            this.$watch('filterKey', () => { this.pages = {}; });
            this.$watch('cvTagFilter', () => this.loadCvTagged());
            await this.loadRegistry();
            {{-- Live (bug hunt 25 Nov, #20): another officer's approval, or a
                 resident's new ID or profile, shows without a reload. The
                 accounts and the tiles only - the registry's thousands of rows
                 are not fetched again every time. --}}
            this.$store.live.on(this.$el, 'residents', () => this.refreshAccounts(), { busy: () => this.showAccountReview || this.processingCitizen });
            {{-- From the top bar's search: a resident's record (?ref=) or a
                 barangay's registry (?barangay=). --}}
            const params = new URLSearchParams(window.location.search);
            if (params.get('barangay')) this.barangayFilter = params.get('barangay');
            {{-- From the dashboard's Needs your action (18 Nov): a tab, a rung of
                 the record ladder, the officer's own data issues. --}}
            if (['registry', 'data-quality', 'citizen-verification'].includes(params.get('tab'))) this.tab = params.get('tab');
            if (['Unverified', 'For Validation', 'Partially Verified', 'Verified'].includes(params.get('verification'))) { this.tab = 'registry'; this.view = 'individuals'; this.verificationFilter = params.get('verification'); }
            this.dqMineOnly = this.tab === 'data-quality' && params.get('mine') === '1';
            const wanted = params.get('ref');
            if (wanted) {
                {{-- A record past the loaded pages (Scan ID's link, 2026-10-29)
                     opens from its own detail; the server says if it is not in scope. --}}
                const person = this.individuals.find(i => i.ref === wanted) || (/^[A-Z]+-\d{4}-\d+$/i.test(wanted) ? { ref: wanted, name: wanted, stub: true } : null);
                if (person) this.$nextTick(() => this.openIndividual(person));
            }
            {{-- Landed here via the Back to Profile link from Dokyu/Tulong — reopen
                 exactly the resident that navigation started from, then forget the flag. --}}
            const stored = sessionStorage.getItem('reopenResident');
            if (!stored) return;
            sessionStorage.removeItem('reopenResident');
            try {
                const target = JSON.parse(stored);
                const person = this.individuals.find(i => i.ref === target.ref);
                if (person) this.$nextTick(() => this.openIndividual(person));
            } catch (e) { /* malformed flag — ignore */ }
        },

        async loadRegistry() {
            this.loading = true;
            this.failed = false;
            try {
                {{-- Each side list is asked for only when the role holds it: every
                     403 pops up 'Walang pahintulot', and five roles saw it on
                     opening this page for the personnel list (QA run 2026-10-05). --}}
                await this.$store.session.whenReady();
                const may = (sub) => this.$store.session.canPerm(sub, 'view');
                const none = (extra = {}) => Promise.resolve({ data: [], ...extra });
                {{-- Asked first, so the server answers them before the lists' pages. --}}
                this.loadTileCounts(may);
                const [residents, households, families, citizens, issues, encoders, coverage] = await Promise.all([
                    {{-- A to Z (bug hunt 25 Nov, #13): the server's default is newest
                         first, and the registry opened at "Zaldy". --}}
                    $api.getAll('/admin/residents', { params: { sort: 'name', direction: 'asc' } }),
                    $api.getAll('/admin/households', { params: { sort: 'label', direction: 'asc' } }),
                    $api.getAll('/admin/families'),
                    {{-- A citizen-verification queue's 'Not Registered' bucket needs
                         every resident, so this joins onto individuals below rather
                         than driving its own separate list. --}}
                    may('const_citizen_verification') ? $api.getAll('/admin/citizens').catch(() => ({ data: [] })) : none(),
                    {{-- Data Quality tab. Caught separately so a permission gap on
                         this one queue doesn't take down the whole Registry. --}}
                    may('const_data_quality') ? $api.getAll('/admin/data-quality').catch(() => ({ data: [] })) : none(),
                    {{-- Encoder list for the Assign action -- not everyone who can
                         see the queue can see Personnel, so this degrades to an
                         empty picker rather than failing the page. --}}
                    {{-- Or, for an officer who assigns issues but cannot read Personnel
                         (the Records Officer), the officers they may assign to (#14). --}}
                    may('um_personnel') ? $api.getAll('/admin/users').catch(() => ({ data: [] }))
                        : (this.can('const_data_quality', 'assign') ? $api.get('/admin/data-quality/assignees').catch(() => ({ data: [] })) : none()),
                    {{-- Profiling Coverage tab. `meta.municipal` is only present for
                         a municipality-scoped actor -- null for a barangay-scoped one. --}}
                    may('const_barangay') ? $api.get('/admin/coverage').catch(() => ({ data: [], meta: null })) : none({ meta: null }),
                ]);
                const citizensByRef = {};
                (citizens.data || []).forEach(c => { if (c.resident_profile?.ref) citizensByRef[c.resident_profile.ref] = c; });
                this.citizensByRef = citizensByRef;
                this.individuals = (residents.data || []).map(i => ({ ...i, citizen_account: citizensByRef[i.ref] || null }));
                this.photoByRef = Object.fromEntries(this.individuals.filter(i => i.citizen_account?.photo_url).map(i => [i.ref, i.citizen_account.photo_url]));
                this.households = households.data || [];
                this.families = families.data || [];
                this.citizens = citizens.data || [];
                this.qualityIssues = issues.data || [];
                this.encoders = encoders.data || [];
                this.barangayCoverage = coverage.data || [];
                this.coverageMunicipal = coverage.meta?.municipal || null;
                this.registryTruncated = [residents, households, families].some(r => r.meta?.truncated);
                this.registryTotals = {
                    residents: residents.meta?.total ?? this.individuals.length,
                    households: households.meta?.total ?? this.households.length,
                    families: families.meta?.total ?? this.families.length,
                };
            } catch { this.failed = true; } finally {
                this.loading = false;
                this.remote = { key: '', rows: null, loading: false, total: 0, page: 1, lastPage: 1, params: {} };
                this.queueRemoteSearch(0);
            }
        },

        async refreshAccounts() {
            const may = (sub) => this.$store.session.canPerm(sub, 'view');
            this.loadTileCounts(may);
            if (!may('const_citizen_verification')) return;
            const res = await $api.getAll('/admin/citizens').catch(() => null);
            if (!res) return;
            this.citizens = res.data || [];
            const byRef = {};
            this.citizens.forEach(c => { if (c.resident_profile?.ref) byRef[c.resident_profile.ref] = c; });
            this.citizensByRef = byRef;
            this.individuals = this.individuals.map(i => ({ ...i, citizen_account: byRef[i.ref] || null }));
        },
        {{-- An account the officer just decided, in every copy the page keeps:
             a row for an account whose record is not among the loaded residents
             is rebuilt from this.citizens on each render, and kept saying
             Pending Review until a reload (#20). --}}
        keepAccount(updated) {
            if (!updated?.account_no) return;
            this.citizens = (this.citizens || []).map(c => c.account_no === updated.account_no ? { ...c, ...updated } : c);
        },

        {{-- One row a list: the totals are in each page's meta, and the residents'
             carries the confirmed and sent-in counts for the whole scope. A list
             the role does not hold, or a failure, leaves that tile to the lists. --}}
        async loadTileCounts(may) {
            const meta = (sub, url, params = {}) => may(sub)
                ? $api.get(url, { params: { ...params, per_page: 1 } }).then(r => r.meta || {}).catch(() => ({}))
                : Promise.resolve({});
            const [res, hh, fam, dq] = await Promise.all([
                meta('const_resident', '/admin/residents', { counts: 1 }),
                meta('const_household', '/admin/households'),
                meta('const_family', '/admin/families'),
                {{-- Open work only: the tile says Needs attention. --}}
                meta('const_data_quality', '/admin/data-quality', { status: 'Open,In Review' }),
            ]);
            this.tileCounts = { residents: res.total, households: hh.total, families: fam.total, verified: res.counts?.verified, pending: res.counts?.pending, issues: dq.total };
        },

        {{-- What the server is asked for while the registry is too big to hold:
             empty when everything is loaded or nothing is being looked for.
             A tag always goes to the server: the list rows carry no tags (only
             a resident's detail does), so filtering them here emptied the list
             (QA sweep 2026-11-18). --}}
        get remoteKey() {
            const q = this.search.trim();
            if (!this.tagFilter && (!this.registryTruncated || !(q || this.barangayFilter || this.verificationFilter))) return '';
            return [q, this.barangayFilter, this.verificationFilter, this.tagFilter].join('|');
        },
        queueRemoteSearch(delay = 350) {
            clearTimeout(this.remoteTimer);
            this.remoteTimer = setTimeout(() => this.searchRegistry(), delay);
        },
        {{-- One page of 50 from the server at a time, so any number of matches
             works (live test 2026-10-12: fetching them all stopped at 3,000 of
             an import's 4,999). --}}
        async searchRegistry(page = 1) {
            const key = this.remoteKey;
            if (!key) { this.remote = { key: '', rows: null, loading: false, total: 0, page: 1, lastPage: 1, params: {} }; return; }
            if (key === this.remote.key && page === this.remote.page && (this.remote.rows || this.remote.loading)) return;
            const q = this.search.trim();
            const params = {};
            if (/^IMP-\d{4}-\d+$/i.test(q)) params.import = q.toUpperCase(); else if (q) params.q = q;
            if (this.barangayFilter) params.barangay = this.barangayFilter;
            if (this.verificationFilter) params.verification = this.verificationFilter;
            if (this.tagFilter) params.tag = this.tagFilter;
            this.remote = { ...this.remote, key, page, loading: true, params };
            try {
                const res = await $api.get('/admin/residents', { params: { ...params, per_page: this.pageSize, page } });
                if (this.remoteKey !== key || this.remote.page !== page) return;
                this.remote = { key, page, params, loading: false, rows: (res.data || []).map(i => ({ ...i, citizen_account: this.citizensByRef[i.ref] || null })), total: res.meta?.total ?? (res.data || []).length, lastPage: res.meta?.last_page || 1 };
            } catch {
                if (this.remoteKey === key) this.remote = { key, page, params, rows: null, loading: false, total: 0, lastPage: 1 };
            }
            this.$nextTick(() => window.renderIcons?.());
        },
        turnRemotePage(step) { this.searchRegistry(Math.min(Math.max(1, this.remote.page + step), this.remote.lastPage)); },
        {{-- Exports go through the server, which checks the export permission,
             keeps to the account's scope and logs each one (owner, 2026-10-29):
             hiding the buttons alone enforced nothing. A server search sends
             its filters; otherwise only a narrowed list sends its refs. --}}
        async exportIndividuals(format = 'csv') {
            if (this.remoteActive) return this.exportFrom('residents', { filters: this.remote.params }, format, 'individuals-export.csv');
            return this.exportShown('residents', this.filteredIndividuals, this.individuals, format, 'individuals-export.csv');
        },
        exportShown(dataset, shown, all, format, filename) {
            return this.exportFrom(dataset, shown.length === all.length ? {} : { refs: shown.map(r => r.ref) }, format, filename);
        },
        async exportFrom(dataset, body, format, filename) {
            let rows;
            try {
                const res = await $api.post('/admin/constituents/exports/' + dataset, { ...body, format: format === 'pdf' ? 'PDF' : 'CSV' });
                rows = res.data || [];
            } catch { this.$dispatch('toast', { message: 'Could not export these records.', variant: 'error' }); return; }
            return format === 'pdf' ? this.exportPDF(rows, filename) : this.exportCSV(rows, filename);
        },
        get remoteActive() { return !!this.remoteKey && this.remote.key === this.remoteKey && !!this.remote.rows; },

        search:             '',
        barangayFilter:     '',
        verificationFilter: '',
        statusFilter:       '',
        tagFilter:          '',
        dqTypeFilter:       '',
        dqStatusFilter:     '',
        dqMineOnly:         false,
        showIndividualDetail: false,
        showAddIndividual:    false,
        showFamilyDetail:     false,
        showAddFamily:        false,
        showHouseholdDetail:  false,
        showAddHousehold:     false,
        showLifecycle:        false,
        showMerge:            false,
        showDQDetail:         false,
        showAddMenu:          false,
        selectedIndividual:   null,
        selectedFamily:       null,
        selectedHousehold:    null,
        {{-- The open family's or household's members, from the server: the page
             holds only the first 2,000 residents (live test 2026-10-12: a family
             of four showed no members at all). --}}
        members: { key: '', rows: [], loading: false, failed: false },
        {{-- The open resident's documents and assistance received, filed on the
             portal or imported from an office's records (2026-10-12). --}}
        residentRequests: { ref: '', rows: [], loading: false, failed: false },
        {{-- One Tulong case's notes, opened from that list (owner, 2026-11-05). --}}
        caseNotes: { ref: '', rows: [], loading: false, failed: false, denied: false },
        selectedDQIssue:      null,
        lifecycleAction:      '',
        addIndividualStep: 1,
        addFamilyStep:     1,
        addHouseholdStep:  1,
        individualTab: 'Overview',
        familyTab:     'overview',
        householdTab:  'overview',

        get isMunicipalScope() {
            const s = this.$store.session.account?.scope;
            return !s || s === 'Municipal';
        },
        {{-- Loaded, the lists give the counts, so a change made here shows at
             once; past one load the server's whole-scope counts do. Until then
             (or if the lists failed) the one-row counts, or a dash: never a 0
             that is not true. --}}
        get activeStats() {
            const c = this.tileCounts || {};
            const big = this.registryTruncated;
            const listed = {
                residents: big ? this.registryTotals.residents : this.individuals.length,
                households: big ? this.registryTotals.households : this.households.length,
                families: big ? this.registryTotals.families : this.families.length,
                verified: big && c.verified != null ? c.verified : this.individuals.filter(i => i.verification === 'Verified').length,
                pending: big && c.pending != null ? c.pending : this.individuals.filter(i => i.verification === 'For Validation').length,
                {{-- Needs attention: a resolved issue is done (QA sweep 2026-11-18:
                     the tile and the tab's badge counted every issue ever raised). --}}
                issues: this.qualityIssues.filter(i => i.status !== 'Resolved').length,
            };
            if (!this.loading && !this.failed) return listed;
            return Object.fromEntries(Object.keys(listed).map(k => [k, c[k] ?? '—']));
        },

        cvType: 'Individual',
        cvSearch: '',
        cvStatusFilter: '',
        cvBarangayFilter: '',
        cvTagFilter: '',
        {{-- The residents carrying the tag chosen on To Verify, by ref, from the server:
             the list rows carry no tags, so the filter matched no one (QA sweep
             2026-11-18). Asked again each time a tag is chosen. --}}
        cvTagged: { tag: '', refs: null, loading: false },
        async loadCvTagged() {
            const tag = this.cvTagFilter;
            if (!tag) { this.cvTagged = { tag: '', refs: null, loading: false }; return; }
            this.cvTagged = { tag, refs: null, loading: true };
            let refs = new Set();
            try {
                refs = new Set(((await $api.getAll('/admin/residents', { params: { tag } })).data || []).map(r => r.ref));
            } catch { /* nothing matches rather than everything */ }
            if (this.cvTagFilter === tag) this.cvTagged = { tag, refs, loading: false };
        },
        rejectAccountRemarks: '',
        processingCitizen: false,
        {{-- The account review (modal-citizen-account): the detail is an
             audited read of the account, its valid ID, selfie and photo. --}}
        showAccountReview: false,
        accountRow: null,
        accountDetail: null,
        accountLoading: false,
        photoRejectRemarks: '',
        {{-- Identity (2026-10-09): the reason an officer gives to verify an
             identity by hand, restore one, or revoke it. Kept on the record. --}}
        identityReason: '',
        {{-- Residency, apart from identity (2026-10-13): the reason an officer
             gives to withdraw an approved residency. The resident reads it. --}}
        revokeApprovalReason: '',
        {{-- The registry record registration recognised the resident as, and
             records the account may really be (2026-10-09). --}}
        registryMatch: null,
        registryBusy: false,
        {{-- A resident's approved photo by registry ref (from GET /admin/citizens), for
             the rows, the detail and household and family members. --}}
        photoByRef: {},
        photoOf(ref) { return this.photoByRef[ref] || null; },
        {{-- Every resident in scope belongs here, not just the ones who signed
             up on the Citizen Portal — Individual/Family/Household already show
             everyone, so Citizen Verification's Individual list matches that
             same total instead of silently dropping non-portal residents.
             Residents with no citizen_account render as Not Registered further
             down, since there's nothing to approve/reject for them yet.
             Resident records, plus registrants with no resident record yet: a
             new account is not linked to one, and used to be missing from this
             queue entirely, so nobody could verify it from here. --}}
        get citizenAccountList() {
            const linked = new Set(this.individuals.map(i => i.ref));
            const accountOnly = (this.citizens || [])
                .filter(c => !(c.resident_profile?.ref && linked.has(c.resident_profile.ref)))
                .map(c => ({ ref: c.account_no, name: c.name, barangay: c.barangay, tags: [], citizen_account: c, accountOnly: true }));
            return [...this.individuals, ...accountOnly].filter(i => this.$store.session.inScope(i.barangay));
        },
        async openAccount(i) {
            if (!i?.citizen_account) return;
            this.accountRow = i;
            this.accountDetail = null;
            this.photoRejectRemarks = '';
            this.identityReason = '';
            this.revokeApprovalReason = '';
            this.showAccountReview = true;
            this.accountLoading = true;
            this.registryMatch = null;
            this.loadRegistryMatch(i);
            try {
                this.accountDetail = (await $api.get('/admin/citizens/' + encodeURIComponent(i.citizen_account.account_no))).data;
            } catch (err) {
                this.showAccountReview = false;
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not open this account.', variant: 'error' });
            } finally {
                this.accountLoading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },
        async loadRegistryMatch(i) {
            try {
                this.registryMatch = (await $api.get('/admin/citizens/' + encodeURIComponent(i.citizen_account.account_no) + '/registry-match')).data;
            } catch { this.registryMatch = null; }
            this.$nextTick(() => window.renderIcons?.());
        },
        async decideRegistryMatch(action, ref = null) {
            const i = this.accountRow;
            if (!i || this.registryBusy) return;
            if (action === 'reject' && !window.confirm('Not the same person? ' + (this.registryMatch?.record?.ref || 'The record') + ' stays as it is, and the account gets a record of its own.')) return;
            if (action === 'link' && !window.confirm('Use ' + ref + ' as this resident\'s record? Their account and requests move to it; the record made at registration is merged into it (it can be reverted from Data Quality).')) return;
            this.registryBusy = true;
            try {
                const url = '/admin/citizens/' + encodeURIComponent(i.citizen_account.account_no) + '/registry-match/' + action;
                this.registryMatch = (await $api.post(url, action === 'link' ? { ref } : {})).data;
                this.$dispatch('toast', { message: { confirm: 'Confirmed: the record is this resident\'s.', reject: 'The account has a record of its own now.', link: 'The account now uses ' + ref + '.' }[action], variant: 'success' });
                this.loadRegistry();
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not update the registry record.', variant: 'error' });
            } finally {
                this.registryBusy = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },
        async decideAccount(action) {
            const i = this.accountRow;
            if (!i) return;
            if (action === 'verify') await this.verifyCitizenAccount(i); else await this.rejectCitizenAccount(i);
            await this.openAccount(i);
        },
        {{-- Identity, apart from residency (2026-10-09). verify = by hand or
             restoring a revoked identity (const_citizen_verification,verify);
             revoke (…,reject) asks for confirmation; recheck runs a check the
             service could not finish. None of them approves the account. --}}
        async decideIdentity(action) {
            const i = this.accountRow;
            if (!i || this.processingCitizen) return;
            const reason = this.identityReason.trim();
            if (action !== 'recheck' && reason.length < 5) return;
            if (action === 'revoke' && !window.confirm('Revoke ' + (i.name || 'this resident') + '\'s identity verification? Their access drops to Unverified until an officer restores it. Their residency decision and the original check stay as they are.')) return;
            this.processingCitizen = true;
            try {
                const url = '/admin/citizens/' + encodeURIComponent(i.citizen_account.account_no) + '/identity/' + action;
                const res = await $api.post(url, action === 'recheck' ? {} : { reason });
                this.accountDetail = res.data;
                i.citizen_account = { ...i.citizen_account, identity_status: res.data?.identity?.status, access_level: res.data?.access_level };
                this.identityReason = '';
                this.$dispatch('toast', {
                    message: { verify: 'Identity verified. Approve the account separately.', revoke: 'Identity revoked; the resident was told why.', recheck: 'Check run again.' }[action],
                    variant: action === 'revoke' ? 'info' : 'success',
                });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not update the identity.', variant: 'error' });
            } finally {
                this.processingCitizen = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },
        {{-- Withdraw an approved residency (const_citizen_verification,reject).
             The account goes back to Rejected, the Resident ID is suspended and
             its QR stops verifying; the identity is left as it is. Approving the
             account again restores it. --}}
        async revokeApproval() {
            const i = this.accountRow;
            const reason = this.revokeApprovalReason.trim();
            if (!i || this.processingCitizen || reason.length < 5) return;
            if (!window.confirm('Revoke ' + (i.name || 'this resident') + '\'s residency approval? Their Resident ID is suspended and its QR stops verifying until the account is approved again. Their identity verification stays as it is.')) return;
            this.processingCitizen = true;
            try {
                const res = await $api.post('/admin/citizens/' + encodeURIComponent(i.citizen_account.account_no) + '/revoke-approval', { reason });
                this.accountDetail = res.data;
                i.citizen_account = { ...i.citizen_account, status: res.data?.status, access_level: res.data?.access_level };
                this.revokeApprovalReason = '';
                this.$dispatch('toast', { message: 'Residency approval revoked; the resident was told why.', variant: 'info' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not revoke the approval.', variant: 'error' });
            } finally {
                this.processingCitizen = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },
        identityPillClass(s) {
            return {
                'Pending': 'bg-slate-100 text-slate-700 ring-slate-200',
                'In Progress': 'bg-sky-50 text-sky-700 ring-sky-200',
                'Verified': 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'Failed': 'bg-rose-50 text-rose-700 ring-rose-200',
                'Requires Review': 'bg-amber-50 text-amber-700 ring-amber-200',
                'Revoked': 'bg-rose-50 text-rose-700 ring-rose-200',
            }[s] || 'bg-slate-100 text-slate-700 ring-slate-200';
        },
        async decideAccountPhoto(action) {
            const i = this.accountRow;
            if (!i || this.processingCitizen) return;
            if (action === 'reject' && this.photoRejectRemarks.trim().length < 5) return;
            this.processingCitizen = true;
            try {
                const url = '/admin/citizens/' + encodeURIComponent(i.citizen_account.account_no) + '/photo/' + action;
                const res = await $api.post(url, action === 'reject' ? { remarks: this.photoRejectRemarks.trim() } : {});
                this.accountDetail = res.data;
                i.citizen_account = { ...i.citizen_account, photo_pending: false };
                this.photoRejectRemarks = '';
                this.$dispatch('toast', { message: action === 'approve' ? 'Photo approved; it is on the Digital ID now.' : 'Photo rejected; the resident was told why.', variant: action === 'approve' ? 'success' : 'info' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not decide the photo.', variant: 'error' });
            } finally { this.processingCitizen = false; }
        },
        {{-- The badge says what it is counting (live test 2026-10-07, UX-4): it
             read 2 while every account was approved, because it also counts the
             family and household records not confirmed yet.
             Family and household records count only once someone has started
             checking them (For Validation, Partially Verified). A plain
             Unverified record is not waiting on anyone, and counting those let
             one import of a barangay's list push the badge into the hundreds
             (flow guide 2026-11-13, Residents bug 6). --}}
        get verificationQueueParts() {
            const started = r => ['For Validation', 'Partially Verified'].includes(r.verification);
            return {
                accounts: this.citizenAccountList.filter(i => this.cvNeedsReview(i)).length,
                families: this.cvFamilyList.filter(started).length,
                households: this.cvHouseholdList.filter(started).length,
            };
        },
        {{-- The server's account statuses are Pending Review, Approved, Rejected
             and Archived. The queue splits Pending Review in two by the identity
             check, so the Under Verification card means something: the ID is
             confirmed and only the officer's Verify account is left. It read 0
             forever before (flow guide 2026-11-13, Residents bug 1). --}}
        cvStatusOf(i) {
            const a = i?.citizen_account;
            if (!a) return 'Not Registered';
            if (a.status === 'Pending Review' && a.identity_status === 'Verified') return 'Under Verification';
            {{-- An approved resident whose ID check was revoked sent a new ID:
                 it waits for an officer like any new one (bug hunt 25 Nov, #5). --}}
            if (a.status === 'Approved' && a.identity_status !== 'Verified' && a.id_status === 'Pending Review') return 'New ID Sent';
            return a.status;
        },
        {{-- The statuses that wait on an officer's review. --}}
        cvNeedsReview(i) { return ['Pending Review', 'Under Verification', 'New ID Sent'].includes(this.cvStatusOf(i)); },
        get pendingCitizenVerificationCount() {
            const p = this.verificationQueueParts;
            return p.accounts + p.families + p.households;
        },
        get verificationQueueBreakdown() {
            const p = this.verificationQueueParts;
            const n = (k, one, many) => k + ' ' + (k === 1 ? one : many);
            return n(p.accounts, 'account ID', 'account IDs') + ', ' + n(p.families, 'family record', 'family records') + ', ' + n(p.households, 'household record', 'household records') + ' to verify';
        },
        get cvCounts() {
            const list = this.citizenAccountList;
            const n = s => list.filter(i => this.cvStatusOf(i) === s).length;
            return {
                {{-- A new ID sent counts with the IDs not confirmed yet (#5). --}}
                pending: n('Pending Review') + n('New ID Sent'),
                underVerification: n('Under Verification'),
                verified: n('Approved'),
                rejected: n('Rejected'),
                notRegistered: n('Not Registered'),
                archived: n('Archived'),
            };
        },
        get filteredCitizenAccounts() {
            let list = this.citizenAccountList.filter(i => {
                const status = this.cvStatusOf(i);
                {{-- Archived accounts only when asked for: no card counted them (#28). --}}
                if (!this.cvStatusFilter && status === 'Archived') return false;
                if (this.cvStatusFilter && status !== this.cvStatusFilter && !(this.cvStatusFilter === 'Pending Review' && status === 'New ID Sent')) return false;
                if (this.cvBarangayFilter && i.barangay !== this.cvBarangayFilter) return false;
                if (this.cvTagFilter && !this.cvTagged.refs?.has(i.ref)) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    {{-- Not the email: the list's is masked, so it never matched (#27). The account number does. --}}
                    return (i.name || '').toLowerCase().includes(q) || (i.ref || '').toLowerCase().includes(q) || (i.barangay || '').toLowerCase().includes(q) || (i.citizen_account?.account_no || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = i => {
                if (!i.citizen_account) return 2;
                return this.cvNeedsReview(i) ? 0 : 1;
            };
            return [...list].sort((a, b) => priority(a) - priority(b));
        },
        async verifyCitizenAccount(i) {
            if (this.processingCitizen || !i.citizen_account) return;
            this.processingCitizen = true;
            try {
                const res = await $api.post('/admin/citizens/' + i.citizen_account.account_no + '/verify', {});
                i.citizen_account = { ...i.citizen_account, ...res.data };
                this.keepAccount(i.citizen_account);
                this.$dispatch('toast', { message: i.name + '\'s citizen account was verified.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not verify this account.', variant: 'error' });
            } finally { this.processingCitizen = false; }
        },
        async rejectCitizenAccount(i) {
            if (this.processingCitizen || !i.citizen_account || this.rejectAccountRemarks.trim().length < 5) return;
            this.processingCitizen = true;
            try {
                const res = await $api.post('/admin/citizens/' + i.citizen_account.account_no + '/reject', { remarks: this.rejectAccountRemarks.trim() });
                i.citizen_account = { ...i.citizen_account, ...res.data };
                this.keepAccount(i.citizen_account);
                this.rejectAccountRemarks = '';
                this.$dispatch('toast', { message: i.name + '\'s citizen account was rejected.', variant: 'error' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not reject this account.', variant: 'error' });
            } finally { this.processingCitizen = false; }
        },
        get cvFamilyList() {
            return this.families.filter(f => this.$store.session.inScope(f.barangay));
        },
        get cvHouseholdList() {
            return this.households.filter(h => this.$store.session.inScope(h.barangay));
        },
        {{-- A family or household record is Unverified, For Validation,
             Partially Verified or Verified (DataQualityVocabulary::VERIFICATION);
             the server has no Rejected or Missing Documents for them, so those
             cards and filter options never matched anything (Residents bug 2). --}}
        get cvFamilyCounts() {
            const list = this.cvFamilyList;
            return {
                unverified: list.filter(f => f.verification === 'Unverified').length,
                partial: list.filter(f => ['Partially Verified', 'For Validation'].includes(f.verification)).length,
                verified: list.filter(f => f.verification === 'Verified').length,
            };
        },
        get cvHouseholdCounts() {
            const list = this.cvHouseholdList;
            return {
                unverified: list.filter(h => h.verification === 'Unverified').length,
                partial: list.filter(h => ['Partially Verified', 'For Validation'].includes(h.verification)).length,
                verified: list.filter(h => h.verification === 'Verified').length,
            };
        },
        get cvVerificationOptions() {
            return ['Unverified', 'For Validation', 'Partially Verified', 'Verified'];
        },
        {{-- The Needs Review card counts For Validation and Partially Verified
             together, so its filter keeps both (QA sweep 2026-11-18: it kept only
             Partially Verified, and the records residents sent in disappeared). --}}
        cvStatusMatches(v) {
            if (!this.cvStatusFilter) return true;
            return this.cvStatusFilter === 'Needs Review' ? ['Partially Verified', 'For Validation'].includes(v) : v === this.cvStatusFilter;
        },
        get filteredCvFamilies() {
            let list = this.cvFamilyList.filter(f => {
                if (!this.cvStatusMatches(f.verification)) return false;
                if (this.cvBarangayFilter && f.barangay !== this.cvBarangayFilter) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (f.label || '').toLowerCase().includes(q) || (f.ref || '').toLowerCase().includes(q) || (f.barangay || '').toLowerCase().includes(q) || (f.head_name || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = v => v === 'Verified' ? 1 : 0;
            return [...list].sort((a, b) => priority(a.verification) - priority(b.verification));
        },
        get filteredCvHouseholds() {
            let list = this.cvHouseholdList.filter(h => {
                if (!this.cvStatusMatches(h.verification)) return false;
                if (this.cvBarangayFilter && h.barangay !== this.cvBarangayFilter) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (h.label || '').toLowerCase().includes(q) || (h.ref || '').toLowerCase().includes(q) || (h.barangay || '').toLowerCase().includes(q) || (h.head_name || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = v => v === 'Verified' ? 1 : 0;
            return [...list].sort((a, b) => priority(a.verification) - priority(b.verification));
        },
        {{-- A resident's submitted Resident Profile (the phone said Pending
             Verification with nothing here to finish it, 2026-10-06). Records
             staff or the barangay office, which verifies residents. --}}
        profileReturnNote: '',
        profileReviewBusy: false,
        get mayReviewProfile() {
            return this.can('const_data_quality', 'verify') || this.can('const_citizen_verification', 'verify');
        },
        applyProfileReview(r, d) {
            const row = (this.individuals || []).find((x) => x.ref === r.ref);
            for (const x of [r, row].filter(Boolean)) {
                x.verification = d.verification;
                x.review_note = d.review_note;
                x.reviewed_by = d.reviewed_by;
                x.reviewed_at = d.reviewed_at;
            }
        },
        async verifyProfile(r) {
            if (!r || this.profileReviewBusy) return;
            this.profileReviewBusy = true;
            try {
                const res = await $api.post('/admin/residents/' + r.ref + '/verify', { level: 'Verified' });
                this.applyProfileReview(r, res.data);
                this.profileReturnNote = '';
                this.$dispatch('toast', { message: 'Verified. ' + (r.citizen_account ? 'The resident has been told.' : ''), variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not verify this profile.', variant: 'error' });
            } finally { this.profileReviewBusy = false; }
        },
        async returnProfile(r) {
            const note = this.profileReturnNote.trim();
            if (!r || this.profileReviewBusy || note.length < 5) return;
            this.profileReviewBusy = true;
            try {
                const res = await $api.post('/admin/residents/' + r.ref + '/return', { note });
                this.applyProfileReview(r, res.data);
                this.profileReturnNote = '';
                this.$dispatch('toast', { message: 'Sent back with your note. ' + (r.citizen_account ? 'The resident has been told.' : ''), variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not send this profile back.', variant: 'error' });
            } finally { this.profileReviewBusy = false; }
        },
        async verifyFamily(f) {
            try {
                await $api.post('/admin/families/' + f.ref + '/verify', { level: 'Verified' });
                f.verification = 'Verified';
                this.$dispatch('toast', { message: f.label + ' has been marked as verified.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not verify this family.', variant: 'error' });
            }
        },
        async verifyHousehold(h) {
            try {
                await $api.post('/admin/households/' + h.ref + '/verify', { level: 'Verified' });
                h.verification = 'Verified';
                this.$dispatch('toast', { message: h.label + ' has been marked as verified.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not verify this household.', variant: 'error' });
            }
        },

        get filteredIndividuals() {
            return (this.remoteActive ? this.remote.rows : this.individuals).filter(r => {
                if (!this.$store.session.inScope(r.barangay)) return false;
                if (this.barangayFilter && r.barangay !== this.barangayFilter) return false;
                if (this.verificationFilter && r.verification !== this.verificationFilter) return false;
                {{-- The server's page already holds only the tagged residents. --}}
                if (this.tagFilter && !this.remoteActive && !this.registryTags(r.tags).includes(this.tagFilter)) return false;
                if (this.search) {
                    const q = this.search.toLowerCase();
                    return (r.name||'').toLowerCase().includes(q)||(r.ref||'').toLowerCase().includes(q)||(r.barangay||'').toLowerCase().includes(q)||(r.occupation||'').toLowerCase().includes(q)||(r.imported_from||'').toLowerCase()===q;
                }
                return true;
            });
        },
        get filteredFamilies() {
            return this.families.filter(f => {
                if (!this.$store.session.inScope(f.barangay)) return false;
                if (this.barangayFilter && f.barangay !== this.barangayFilter) return false;
                if (this.search) { const q=this.search.toLowerCase(); return (f.label||'').toLowerCase().includes(q)||(f.head_name||'').toLowerCase().includes(q)||(f.ref||'').toLowerCase().includes(q); }
                return true;
            });
        },
        {{-- A resident's tags as the staff keys (senior, pwd, 4ps, solo_parent):
             the list rows now carry tags (QA 2026-11-18 #30/#36), and the ones a
             resident ticks on their own profile are stored as words ('Senior
             Citizen', 'PWD', '4Ps', 'Solo Parent'). Each tag once, so the chips and
             the filter read both the same. --}}
        registryTags(tags) {
            const words = { 'senior citizen': 'senior', 'senior': 'senior', 'pwd': 'pwd', '4ps': '4ps', '4ps beneficiary': '4ps', 'solo parent': 'solo_parent', 'solo_parent': 'solo_parent', 'osca member': 'osca' };
            const out = [];
            (Array.isArray(tags) ? tags : []).forEach(t => {
                const raw = String(t ?? '').trim();
                if (!raw) return;
                const key = words[raw.toLowerCase()] || raw;
                if (!out.includes(key)) out.push(key);
            });
            return out;
        },
        get filteredHouseholds() {
            return this.households.filter(h => {
                if (!this.$store.session.inScope(h.barangay)) return false;
                if (this.barangayFilter && h.barangay !== this.barangayFilter) return false;
                {{-- Sitio and address too, now that the list rows carry them (#35). --}}
                if (this.search) { const q=this.search.toLowerCase(); return (h.label||'').toLowerCase().includes(q)||(h.head_name||'').toLowerCase().includes(q)||(h.ref||'').toLowerCase().includes(q)||(h.sitio||'').toLowerCase().includes(q)||(h.address||'').toLowerCase().includes(q); }
                return true;
            });
        },
        get filteredQualityIssues() {
            return this.qualityIssues.filter(i => {
                if (!this.$store.session.inScope(i.barangay)) return false;
                if (this.dqTypeFilter && i.type !== this.dqTypeFilter) return false;
                if (this.dqStatusFilter && i.status !== this.dqStatusFilter) return false;
                if (this.dqMineOnly && i.assigned_to !== this.$store.session.account?.name) return false;
                if (this.search) {
                    const q = this.search.toLowerCase();
                    return (this.dqRecordName(i.record)||'').toLowerCase().includes(q) || (i.description||'').toLowerCase().includes(q) || (i.barangay||'').toLowerCase().includes(q) || (i.ref||'').toLowerCase().includes(q);
                }
                return true;
            });
        },
        {{-- The data-quality index only returns a `record` ref (e.g. RES-2026-0042),
             never a name -- resolved here against what Registry already loaded
             instead of a second fetch per row. --}}
        dqRecordName(ref) {
            if (!ref) return null;
            const person = this.dqRecord(ref);
            if (person) return person.name;
            const hh = this.households.find(x => x.ref === ref);
            if (hh) return hh.label || hh.ref;
            return ref;
        },
        {{-- Residents fetched one by one (a merge's pair) are kept here too: the
             page holds the first 2,000 only, and a pair past them showed nothing
             but dashes to choose from (QA sweep 2026-11-18). --}}
        fetchedRecords: {},
        dqRecord(ref) {
            return this.individuals.find(x => x.ref === ref) || this.fetchedRecords[ref] || null;
        },
        async runDataQualityDetection() {
            if (this.dqScanning) return;
            this.dqScanning = true;
            try {
                const res = await $api.post('/admin/data-quality/detect', {});
                const { created = 0, skipped = 0 } = res.data || {};
                this.$dispatch('toast', {
                    message: created
                        ? `Scan complete: ${created} new issue(s) found, ${skipped} already open.`
                        : `Scan complete: no new issues. ${skipped} already open.`,
                    variant: created ? 'warning' : 'info',
                });
                if (created) {
                    const refreshed = await $api.getAll('/admin/data-quality');
                    this.qualityIssues = refreshed.data || [];
                }
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not run the detection scan.', variant: 'error' });
            } finally { this.dqScanning = false; }
        },
        async assignDQIssue(issue, encoderId) {
            if (!issue || !encoderId) return;
            try {
                const res = await $api.post('/admin/data-quality/' + issue.ref + '/assign', { encoder_id: encoderId });
                Object.assign(issue, res.data);
                this.$dispatch('toast', { message: 'Issue assigned.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not assign this issue.', variant: 'error' });
            }
        },
        async resolveDQIssue(issue, resolution) {
            if (!issue || !resolution || resolution.trim().length < 3) return;
            try {
                const res = await $api.post('/admin/data-quality/' + issue.ref + '/resolve', { resolution: resolution.trim() });
                Object.assign(issue, res.data);
                this.$dispatch('toast', { message: 'Issue marked as resolved.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not resolve this issue.', variant: 'error' });
            }
        },
        {{-- Merging IS the resolution of the issue that raised it -- the backend
             resolves issueRef itself when given, this just mirrors that locally. --}}
        get barangayStatusBreakdown() {
            const colors = { 'Complete': '#10b981', 'In Progress': '#3b82f6', 'Needs Follow-up': '#f59e0b', 'Not Started (Re-visit Required)': '#f43f5e' };
            const counts = {};
            this.barangayCoverage.forEach(b => { const s = b.status || 'Not Started (Re-visit Required)'; counts[s] = (counts[s] || 0) + 1; });
            return Object.keys(colors).filter(s => counts[s]).map(s => ({ label: s, value: counts[s], color: colors[s] }));
        },
        goToBarangayResidents(name) {
            this.tab = 'registry'; this.view = 'individuals'; this.clearFilters(); this.barangayFilter = name;
        },
        async confirmDQMerge(keepRef, mergeRef, issueRef, note) {
            const res = await $api.post('/admin/merges', { keep: keepRef, merge: mergeRef, issue_ref: issueRef || null, note: note || null });
            this.individuals = this.individuals.filter(i => i.ref !== mergeRef);
            if (issueRef) {
                const issue = this.qualityIssues.find(i => i.ref === issueRef);
                if (issue) issue.status = 'Resolved';
            }
            return res.data;
        },

        async openIndividual(r) {
            {{-- From a family's or household's members: that window closes, or
                 the resident's opened behind it (each is teleported to the end of
                 the page in include order) and the click seemed to do nothing
                 (QA sweep 2026-11-18). --}}
            this.showFamilyDetail = false;
            this.showHouseholdDetail = false;
            this.selectedIndividual = r;
            this.individualTab = 'Overview';
            this.rejectAccountRemarks = '';
            this.showIndividualDetail = true;
            this.$nextTick(() => window.renderIcons?.());
            this.loadResidentRequests(r.ref);
            this.caseNotes = { ref: '', rows: [], loading: false, failed: false, denied: false };
            {{-- The list row is intentionally lean (no tags/household/family/
                 sensitive fields) -- the detail endpoint has the rest. --}}
            try {
                const res = await $api.get('/admin/residents/' + r.ref);
                const merged = { ...res.data, citizen_account: r.citizen_account };
                this.selectedIndividual = merged;
                const idx = this.individuals.findIndex(x => x.ref === r.ref);
                if (idx !== -1) this.individuals[idx] = { ...this.individuals[idx], ...merged };
            } catch {
                {{-- Keep the list row's fields if the detail fetch fails; a record
                     opened by its link alone has none to keep. --}}
                if (r.stub) this.showIndividualDetail = false;
            }
        },
        async loadResidentRequests(ref) {
            this.residentRequests = { ref, rows: [], loading: true, failed: false };
            try {
                const res = await $api.get('/admin/residents/' + encodeURIComponent(ref) + '/requests');
                if (this.residentRequests.ref === ref) this.residentRequests = { ref, rows: res.data || [], loading: false, failed: false };
            } catch {
                if (this.residentRequests.ref === ref) this.residentRequests = { ...this.residentRequests, loading: false, failed: true };
            }
            this.$nextTick(() => window.renderIcons?.());
        },
        get residentAssistanceTotal() {
            return this.residentRequests.rows.filter(q => q.type === 'tulong' && q.status === 'Completed' && q.amount).reduce((n, q) => n + Number(q.amount), 0);
        },
        {{-- Real cross-module navigation, not just a sub-tab switch: leaves the
             Constituents page entirely, lands on this resident's requests in the
             Dokyu/Tulong module, and remembers who to reopen when they come back. --}}
        goToDokyu(r, ref) {
            sessionStorage.setItem('returnToProfile', JSON.stringify({ ref: r.ref, name: r.name }));
            const q = ref ? 'ref=' + encodeURIComponent(ref) : 'citizen=' + encodeURIComponent(r.name);
            window.location.href = '/admin/document-requests?' + q;
        },
        goToTulong(r, ref) {
            sessionStorage.setItem('returnToProfile', JSON.stringify({ ref: r.ref, name: r.name }));
            const q = ref ? 'ref=' + encodeURIComponent(ref) : 'citizen=' + encodeURIComponent(r.name);
            window.location.href = '/admin/assistance-requests?' + q;
        },
        {{-- A Tulong case's notes, read-only, in the resident's window (owner,
             2026-11-05: no separate services list; the notes of the tulong the
             resident received). Loaded only when opened, newest first as the
             server sends them; a voided note shows as voided. A confidential
             type's notes stay with its office (the server keeps them so).
             Notes are written only inside the Tulong case. --}}
        noteModes: { office: 'At the office', home_visit: 'Home visit', field: 'In the field', online: 'Online or by phone' },
        get mayReadCaseNotes() { return this.$store.session.canPerm('tulong_records', 'view'); },
        toggleCaseNotes(ref) {
            if (this.caseNotes.ref === ref) { this.caseNotes = { ref: '', rows: [], loading: false, failed: false, denied: false }; return; }
            this.loadCaseNotes(ref);
        },
        async loadCaseNotes(ref) {
            this.caseNotes = { ref, rows: [], loading: true, failed: false, denied: false };
            try {
                const res = await $api.get('/admin/services-rendered', { params: { request_ref: ref, include_voided: 1, per_page: 50 }, silent: true });
                if (this.caseNotes.ref === ref) this.caseNotes = { ...this.caseNotes, rows: res.data || [], loading: false };
            } catch (err) {
                if (this.caseNotes.ref === ref) this.caseNotes = { ...this.caseNotes, loading: false, denied: !!err?.isForbidden, failed: !err?.isForbidden };
            }
            this.$nextTick(() => window.renderIcons?.());
        },
        noteDay(d) { return d ? new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : ''; },
        {{-- The head of a new family or household: residents already on file by
             that name (in the chosen barangay), to be picked and linked by ref.
             A name typed alone always made a new resident, a second record of
             someone already on file (QA sweep 2026-11-18). --}}
        async headMatches(name, barangay) {
            const q = (name || '').trim();
            if (q.length < 3) return [];
            try {
                const res = await $api.get('/admin/residents', { params: { q, ...(barangay ? { barangay } : {}), per_page: 6 }, silent: true });
                return res.data || [];
            } catch { return []; }
        },
        openFamily(f)     { this.selectedFamily=f;     this.familyTab='overview';     this.showFamilyDetail=true;     this.loadMembers('family', f.ref);     this.$nextTick(()=>window.renderIcons?.()); },
        openHousehold(h)  { this.selectedHousehold=h;  this.householdTab='overview';  this.showHouseholdDetail=true;  this.loadMembers('household', h.ref);  this.$nextTick(()=>window.renderIcons?.()); },
        {{-- What the page already has shows at once; the server's list replaces it. --}}
        async loadMembers(kind, ref) {
            const key = kind + ':' + ref;
            const here = this.individuals.filter(r => r[kind]?.ref === ref);
            this.members = { key, rows: here, loading: true, failed: false };
            try {
                const res = await $api.getAll('/admin/residents', { params: { [kind]: ref }, maxPages: 10 });
                if (this.members.key !== key) return;
                {{-- The head first, then the spouse or partner, then the eldest down. --}}
                const rank = (r) => (r.relation === 'Head' ? 0 : ['Spouse', 'Partner'].includes(r.relation) ? 1 : 2);
                const rows = (res.data || []).map(i => ({ ...i, citizen_account: this.citizensByRef[i.ref] || null }))
                    .sort((x, y) => rank(x) - rank(y) || (y.age ?? -1) - (x.age ?? -1));
                this.members = { key, loading: false, failed: false, rows };
            } catch {
                if (this.members.key === key) this.members = { ...this.members, loading: false, failed: true };
            }
            this.$nextTick(() => window.renderIcons?.());
        },
        openLifecycle(r, action) { this.selectedIndividual=r; this.lifecycleAction=action; this.showLifecycle=true; this.$nextTick(()=>window.renderIcons?.()); },
        openDQIssue(issue)       { this.selectedDQIssue=issue; this.showDQDetail=true; this.$nextTick(()=>window.renderIcons?.()); },
        {{-- A household's electricity, water and toilet are words, not yes/no:
             the registry says Yes or No, a resident's profile None, Solar or Deep
             Well (QA sweep 2026-11-18: a household that reported None read
             Available). Empty is not recorded; None or No is not available. --}}
        amenityState(v) {
            const s = String(v ?? '').trim().toLowerCase();
            return !s ? 'unknown' : (['none', 'no'].includes(s) ? 'no' : 'yes');
        },
        hasAmenity(v) { return this.amenityState(v) === 'yes'; },
        {{-- The household's own word when it says more than yes or no. --}}
        amenityNote(v) { const s = String(v ?? '').trim(); return ['', 'yes', 'no', 'none'].includes(s.toLowerCase()) ? '' : s; },
        verificationBadge(v) {
            const m={'Verified':'text-emerald-700 bg-emerald-50 ring-1 ring-emerald-200','Partially Verified':'text-amber-700 bg-amber-50 ring-1 ring-amber-200','For Validation':'text-indigo-700 bg-indigo-50 ring-1 ring-indigo-200','Missing Documents':'text-orange-700 bg-orange-50 ring-1 ring-orange-200','Unverified':'text-ink-muted bg-slate-100 ring-1 ring-slate-200','Rejected':'text-rose-700 bg-rose-50 ring-1 ring-rose-200'};
            return { class: m[v]||m['Unverified'], label: v || 'Unverified' };
        },
        statusBadge(s) {
            const m={'Active':'text-emerald-700 bg-emerald-50','New Resident':'text-brand-700 bg-brand-50','Transferred':'text-indigo-700 bg-indigo-50','Temporarily Away':'text-amber-700 bg-amber-50','Overseas':'text-teal-700 bg-teal-50','Deceased':'text-ink-muted bg-slate-100','Archived':'text-ink-muted bg-slate-50','Under Review':'text-purple-700 bg-purple-50'};
            return m[s]||m['Active'];
        },
        severityBadge(s) {
            const m={'Critical':'text-rose-700 bg-rose-50 ring-1 ring-rose-200','High':'text-orange-700 bg-orange-50 ring-1 ring-orange-200','Medium':'text-amber-700 bg-amber-50 ring-1 ring-amber-200','Low':'text-slate-600 bg-slate-100 ring-1 ring-slate-200'};
            return m[s]||m['Low'];
        },
        exportCSV(rows, filename) {
            if (!rows.length) { this.$dispatch('toast',{message:'No records to export.',variant:'warning'}); return; }
            const keys=Object.keys(rows[0]).filter(k=>!['services','emergency','member_ids','family_ids','citizen_account','household','family','tags'].includes(k));
            {{-- One plain cell a value, quoted the spreadsheet way (QA sweep
                 2026-11-18): a livelihood or a programs list went out as JSON and
                 its commas split the row, a backslash-escaped quote broke it, and a
                 name typed as =HYPERLINK(...) ran as a formula. A cell that starts
                 like a formula is opened as text. --}}
            const flat=v=>Array.isArray(v)?v.map(flat).filter(x=>x!=='').join('; '):(v&&typeof v==='object'?(v.full_label??v.label??v.name??v.ref??''):(v??''));
            const cell=v=>{const s=String(flat(v));const safe=/^[=+\-@\t\r]/.test(s)?'\''+s:s;return /[\x22,\n\r]/.test(safe)?'\x22'+safe.replace(/\x22/g,'\x22\x22')+'\x22':safe;};
            {{-- Headers in words, not raw keys (bug hunt 25 Nov, #31). --}}
            const words={ref:'Reference No.',dob:'Birth Date',sitio:'Purok / Sitio',civil_status:'Civil Status',income_range:'Income Range',head_name:'Head',head_ref:'Head Reference No.',household_ref:'Household Reference No.',member_count:'Members',family_type:'Family Type',dwelling_type:'Dwelling Type',housing_tenure:'Housing Tenure',flood_risk:'Flood Risk',imported_from:'Imported From'};
            const head=k=>words[k]||String(k).replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
            const csv='\uFEFF'+[keys.map(k=>cell(head(k))).join(','),...rows.map(r=>keys.map(k=>cell(r[k])).join(','))].join('\r\n');
            const a=Object.assign(document.createElement('a'),{href:URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8'})),download:filename});
            a.click(); this.$dispatch('toast',{message:'Exported successfully.',variant:'success'});
        },
        {{-- The same rows as a PDF (owner, 2026-10-15: PDF as well as the spreadsheet). --}}
        async exportPDF(rows, filename) {
            const say = (message, variant) => window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
            if (!rows.length) { say('No records to export.', 'warning'); return; }
            const titles = { 'individuals-export.csv': 'Residents', 'families-export.csv': 'Families', 'households-export.csv': 'Households', 'profiling-coverage-export.csv': 'Profiling coverage by barangay', 'data-quality-export.csv': 'Data quality issues' };
            try {
                await window.tablePdf(rows, { filename, title: titles[filename] || 'Constituents', subtitle: this.barangayFilter ? 'Brgy. ' + this.barangayFilter : '', skip: ['services','emergency','member_ids','family_ids','citizen_account','household','family','tags','photo','photo_url'] });
                say('PDF downloaded.', 'success');
            } catch { say('Could not make the PDF.', 'error'); }
        },
        can(sub,perm) { return this.$store.session.canPerm?this.$store.session.canPerm(sub,perm):true; },
        clearFilters() { this.search='';this.barangayFilter='';this.verificationFilter='';this.statusFilter='';this.tagFilter='';this.dqTypeFilter='';this.dqStatusFilter='';this.dqMineOnly=false;this.cvSearch='';this.cvStatusFilter='';this.cvBarangayFilter='';this.cvTagFilter=''; },
    }"
    {{-- No x-init="init()": Alpine runs init() by itself, and calling it
         again loaded the whole registry twice (live test 2026-10-12: every
         list fetched twice with 14,584 residents). --}}
    x-on:residents-imported.window="loadRegistry()"
    x-on:livelihood-review-import.window="tab = 'occupations'"
    x-on:registry-show-import.window="tab='registry';view='individuals';clearFilters();search=$event.detail.ref"
    class="animate-fade-up"
>

    <div x-show="!$store.session.can('constituents')" x-cloak>
        <x-admin.access-restricted module="Constituent Management" icon="users" />
    </div>

    <div x-show="$store.session.can('constituents')" x-cloak>

        {{-- Scope banner --}}
        <div x-show="$store.session.account && $store.session.account.scope !== 'Municipal'" x-cloak
             class="flex items-center gap-2.5 rounded-xl bg-purple-50 border border-purple-100 px-3.5 py-2.5 mb-4">
            <i data-lucide="map-pin" class="w-4 h-4 text-purple-500 shrink-0"></i>
            <p class="text-xs text-purple-700">Barangay-scoped access — data outside <strong x-text="'Brgy. '+$store.session.account?.scope"></strong> is hidden.</p>
        </div>

        {{-- Page header --}}
        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
            <div>
                <h2 class="text-lg font-bold text-ink">Constituent Registry</h2>
                <p class="text-xs text-ink-muted mt-0.5">Municipality of Esperanza · 20 barangays · <span x-text="isMunicipalScope?'Municipality-wide view':'Brgy. '+$store.session.account?.scope+' view'"></span></p>
            </div>
            <div class="flex items-center gap-2 relative" x-show="can('const_resident','create') || can('const_family','create') || can('const_household','create')">
                <button @click="showAddMenu=!showAddMenu" @click.outside="showAddMenu=false"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-semibold clay-blue hover:brightness-95 transition-all">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>Add record
                    <i data-lucide="chevron-down" class="w-3 h-3 opacity-70 transition-transform" :class="showAddMenu&&'rotate-180'"></i>
                </button>
                <div x-show="showAddMenu" x-transition.origin.top.right x-cloak
                     class="absolute right-0 top-full mt-1.5 w-52 bg-white rounded-xl border border-slate-200 shadow-lg z-30 overflow-hidden">
                    <button x-show="can('const_resident','create')" @click="addIndividualStep=1;showAddIndividual=true;showAddMenu=false;$nextTick(()=>window.renderIcons?.())"
                            class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-white/60 flex items-center gap-2.5">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5 text-brand-500"></i>New resident
                    </button>
                    <button x-show="can('const_family','create')" @click="addFamilyStep=1;showAddFamily=true;showAddMenu=false;$nextTick(()=>window.renderIcons?.())"
                            class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-white/60 flex items-center gap-2.5">
                        <i data-lucide="users" class="w-3.5 h-3.5 text-purple-500"></i>New family
                    </button>
                    <button x-show="can('const_household','create')" @click="addHouseholdStep=1;showAddHousehold=true;showAddMenu=false;$nextTick(()=>window.renderIcons?.())"
                            class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-white/60 flex items-center gap-2.5">
                        <i data-lucide="house" class="w-3.5 h-3.5 text-emerald-500"></i>New household
                    </button>
                    {{-- The LGU's own resident lists, in bulk (owner, 2026-10-09). --}}
                    <div x-show="can('const_resident','create')" class="border-t border-slate-100">
                        <button @click="showAddMenu=false;$dispatch('open-resident-import')"
                                class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-white/60 flex items-center gap-2.5">
                            <i data-lucide="file-up" class="w-3.5 h-3.5 text-gold-600"></i>Import from a file
                        </button>
                        <button @click="showAddMenu=false;$dispatch('open-resident-import',{panel:'history'})"
                                class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-white/60 flex items-center gap-2.5">
                            <i data-lucide="history" class="w-3.5 h-3.5 text-slate-400"></i>Past imports
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
            <x-ui.stat-card layout="compact" @click="tab='registry';view='individuals';clearFilters()" class="cursor-pointer" label="Registered Residents" valueBind="activeStats.residents"  icon="users"          color="brand"  :delay="0"   />
            <x-ui.stat-card layout="compact" @click="tab='registry';view='households';clearFilters()" class="cursor-pointer" label="Active Households"    valueBind="activeStats.households" icon="house"           color="green"  :delay="40"  />
            <x-ui.stat-card layout="compact" @click="tab='registry';view='families';clearFilters()" class="cursor-pointer" label="Recorded Families"    valueBind="activeStats.families"   icon="users-round"    color="purple" :delay="80"  />
            <x-ui.stat-card layout="compact" @click="tab='registry';view='individuals';clearFilters();verificationFilter='Verified'" class="cursor-pointer" label="Records Confirmed"    valueBind="activeStats.verified"   icon="badge-check"    color="gold"   :delay="120" sublabel="Registry record checked" />
            <x-ui.stat-card layout="compact" @click="tab='registry';view='individuals';clearFilters();verificationFilter='For Validation'" class="cursor-pointer" label="Profiles to Review"   valueBind="activeStats.pending"    icon="clock"          color="orange" :delay="160" sublabel="Sent in by residents" />
            <x-ui.stat-card layout="compact" @click="tab='data-quality';clearFilters()" class="cursor-pointer" label="Data Quality Issues"  valueBind="activeStats.issues"     icon="triangle-alert" color="orange" :delay="200" sublabel="Needs attention" />
        </div>

        {{-- Primary tab bar. On a phone the tabs wrap onto a second line, so every
             section stays in view (NAV-4: tabs wrap, never hide off screen). --}}
        <div class="flex flex-wrap items-center gap-1.5 surface-pressed-sm rounded-2xl p-1 w-fit max-w-full mb-5">
            <button @click="tab='registry';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap"
                    :class="tab==='registry'     ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>Registry
            </button>
            <button @click="tab='profiling';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap"
                    :class="tab==='profiling'    ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'">
                <i data-lucide="user-round-search" class="w-3.5 h-3.5"></i>Profiling Coverage
            </button>
            <button @click="tab='data-quality';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 relative whitespace-nowrap"
                    :class="tab==='data-quality' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'">
                <i data-lucide="triangle-alert" class="w-3.5 h-3.5"></i>Data Quality
                {{-- The same colours on the active tab: it is white now, and white
                     text on it hid the count (live test 2026-10-13). --}}
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-rose-100 text-rose-600" x-text="activeStats.issues"></span>
            </button>
            <button x-show="can('const_resident','view')" @click="tab='occupations';clearFilters()"
                    x-on:livelihood-review-count.window="lvReviewCount = $event.detail"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 relative whitespace-nowrap"
                    :class="tab==='occupations' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'">
                <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>Occupations
                <span x-show="lvReviewCount > 0" class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700" x-text="lvReviewCount"></span>
            </button>
            <button @click="tab='citizen-verification';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 relative whitespace-nowrap"
                    :class="tab==='citizen-verification' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'">
                <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>To Verify
                <span
                    :title="verificationQueueBreakdown"
                    x-show="pendingCitizenVerificationCount > 0"
                    class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-600"
                    x-text="pendingCitizenVerificationCount"
                ></span>
            </button>
        </div>

        <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-40 opacity-80" /></div>
        <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load the constituent registry.'" retryExpr="loadRegistry()" :retryable="true" /></div>
        <div x-show="!loading && !failed && registryTruncated" x-cloak role="status"
             class="mb-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0"></i>
            <span>
                The registry has <strong x-text="registryTotals.residents.toLocaleString()"></strong> residents; the list shows the first
                <strong x-text="individuals.length.toLocaleString()"></strong>. Search, the barangay and the filters look through all of them.
                <span x-show="tileCounts?.verified != null">The family and household lists cover the records shown.</span>
                <span x-show="tileCounts?.verified == null">The Records Confirmed and Profiles to Review counts, and the family and household lists, cover the records shown.</span>
            </span>
        </div>

        <div x-show="!loading && !failed">
        {{-- ── Registry tab ────────────────────────────────────────── --}}
        <div x-show="tab === 'registry'">
            {{-- Unified toolbar: view switcher on top, search/filters/actions below --}}
            <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
                <div class="flex flex-wrap items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit max-w-full">
                    <button @click="view='individuals';clearFilters()"
                            class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap"
                            :class="view==='individuals' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>Individuals
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600" x-text="(remoteActive ? remote.total : filteredIndividuals.length).toLocaleString()"></span>
                    </button>
                    <button @click="view='families';clearFilters()"
                            class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap"
                            :class="view==='families'    ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>Families
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600" x-text="filteredFamilies.length"></span>
                    </button>
                    <button @click="view='households';clearFilters()"
                            class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap"
                            :class="view==='households'  ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                        <i data-lucide="house" class="w-3.5 h-3.5"></i>Households
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600" x-text="filteredHouseholds.length"></span>
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-brand-600/10">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-ink-muted absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Search" x-model="search" type="text"
                               :placeholder="view==='individuals'?'Search residents...':view==='families'?'Search families...':'Search households...'"
                               class="w-52 sm:w-64 pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                    </div>
                    <select aria-label="Barangay filter" x-show="isMunicipalScope" x-model="barangayFilter"
                            class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                        <option value="">All Barangays</option>
                        @foreach($barangayOptions as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                    </select>
                    <span x-show="!isMunicipalScope" x-cloak
                          class="inline-flex items-center gap-1.5 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-100 rounded-xl px-3 py-2">
                        <i data-lucide="lock" class="w-3 h-3"></i>Brgy. <span x-text="$store.session.account?.scope"></span>
                    </span>
                    <div x-show="view==='individuals'" class="flex items-center gap-2">
                        <select aria-label="Verification filter" x-model="verificationFilter"
                                class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                            <option value="">All Verification</option>
                            <option>Verified</option>
                            <option>Partially Verified</option>
                            <option>For Validation</option>
                            <option>Unverified</option>
                        </select>
                        <select aria-label="Tag filter" x-model="tagFilter"
                                class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                            <option value="">All Tags</option>
                            <option value="senior">Senior Citizen</option>
                            <option value="osca">OSCA Member</option>
                            <option value="pwd">PWD</option>
                            <option value="4ps">4Ps Beneficiary</option>
                            <option value="solo_parent">Solo Parent</option>
                            <option value="youth">Youth</option>
                            <option value="child">Child</option>
                            <option value="infant">Infant</option>
                            <option value="osy">Out-of-School Youth</option>
                            <option value="farmer">Farmer</option>
                            <option value="high_risk">High Risk</option>
                            <option value="ip">Indigenous Peoples</option>
                        </select>
                    </div>
                    <button x-show="search||barangayFilter||verificationFilter||tagFilter" @click="clearFilters()"
                            class="text-xs text-ink-muted hover:text-rose-600 flex items-center gap-1 transition-colors">
                        <i data-lucide="circle-x" class="w-3.5 h-3.5"></i>Clear
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button x-show="can(view==='individuals' ? 'const_resident' : 'const_household', 'export')" @click="view==='individuals'?exportIndividuals():(view==='families'?exportShown('families',filteredFamilies,families,'csv','families-export.csv'):exportShown('households',filteredHouseholds,households,'csv','households-export.csv'))"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 hover:bg-white hover:border-slate-300 transition-all">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>Export CSV
                    </button>
                    <button x-show="can(view==='individuals' ? 'const_resident' : 'const_household', 'export')" @click="view==='individuals'?exportIndividuals('pdf'):(view==='families'?exportShown('families',filteredFamilies,families,'pdf','families-export.csv'):exportShown('households',filteredHouseholds,households,'pdf','households-export.csv'))"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 hover:bg-white hover:border-slate-300 transition-all">
                        <i data-lucide="file-down" class="w-3.5 h-3.5"></i>Export PDF
                    </button>
                    <button @click="window.print()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 hover:bg-white hover:border-slate-300 transition-all">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>Print
                    </button>
                </div>
                </div>
            </div>

            <div x-show="view==='individuals'">@include('admin.constituents.view-individuals')</div>
            <div x-show="view==='families'"    x-cloak>@include('admin.constituents.view-families')</div>
            <div x-show="view==='households'"  x-cloak>@include('admin.constituents.view-households')</div>
        </div>

        {{-- ── Profiling Coverage tab ──────────────────────────────── --}}
        <div x-show="tab === 'profiling'" x-cloak>
            @include('admin.constituents.tab-profiling')
        </div>

        {{-- ── Citizen Verification tab ────────────────────────────── --}}
        <div x-show="tab === 'citizen-verification'" x-cloak>
            @include('admin.constituents.tab-citizen-verification')
        </div>

        {{-- ── Occupations tab: the DRRM review list (owner, 2026-10-29) ── --}}
        <div x-show="tab === 'occupations'" x-cloak>
            @include('admin.constituents.tab-occupations')
        </div>

        {{-- ── Data Quality tab ────────────────────────────────────── --}}
        <div x-show="tab === 'data-quality'" x-cloak>
            @include('admin.constituents.tab-data-quality')
        </div>

        </div>
    </div>

    {{-- ── Modals ──────────────────────────────────────────────────── --}}
    <div x-show="showIndividualDetail" x-cloak>@include('admin.constituents.modal-individual-detail')</div>
    <div x-show="showAddIndividual"    x-cloak><x-lazy-partial name="constituents-add-individual" when="showAddIndividual" /></div>
    <div x-show="showFamilyDetail"     x-cloak>@include('admin.constituents.modal-family-detail')</div>
    <div x-show="showAddFamily"        x-cloak><x-lazy-partial name="constituents-add-family" when="showAddFamily" /></div>
    <div x-show="showHouseholdDetail"  x-cloak>@include('admin.constituents.modal-household-detail')</div>
    <div x-show="showAddHousehold"     x-cloak><x-lazy-partial name="constituents-add-household" when="showAddHousehold" /></div>
    <div x-show="showLifecycle"        x-cloak><x-lazy-partial name="constituents-lifecycle" when="showLifecycle" /></div>
    <div x-show="showMerge"            x-cloak><x-lazy-partial name="constituents-merge" when="showMerge" /></div>
    <div x-show="showDQDetail"         x-cloak>@include('admin.constituents.modal-dq-detail')</div>
    <div x-show="showAccountReview"    x-cloak>@include('admin.constituents.modal-citizen-account')</div>
    {{-- The add, life-event and merge windows above, and the import window's
         own window, load apart from the page (resources/views/lazy/, page
         budget 2026-11-25): <x-lazy-partial> puts each back in its place. --}}
    @include('admin.constituents.modal-import')

</div>
</x-layouts.admin>
