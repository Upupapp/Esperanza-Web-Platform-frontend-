@php
    $data            = config('esperanza_constituents');
    $citizenAccounts = collect(config('esperanza_citizens')['accounts'])->keyBy('id');
    // Real portrait/ID photos ported from the Esperanza Mobile app's own
    // seeded demo assets (same account ids: ESP-RES-2024-1102 = Ronaldo,
    // ESP-RES-2024-1044 = Cristy) — only these two residents have a real
    // photo on file. ID scans stay this way (only Ronaldo/Cristy) since a
    // fake generated ID card would read as a forged government document,
    // which isn't something to synthesize even for mock data.
    // TAB 04: fixtures live in resources/data/admin/constituents.php so this view stays presentation only.
    $__fixtures = require base_path('resources/data/admin/constituents.php');
    $residentPhotoAsset = $__fixtures['residentPhotoAsset'];
    $residentIdImageAsset = $__fixtures['residentIdImageAsset'];
    // Every other citizen account gets a generated portrait instead of the
    // plain initials circle — a deterministic, illustrated avatar (DiceBear
    // "personas", seeded by the account id so it's stable across reloads)
    // rather than a fabricated photograph, which would misrepresent a real
    // person even for a fictional resident. Background rotates through a
    // small palette pulled from the platform's own brand/gold/slate tones.
    $avatarBackgrounds = $__fixtures['avatarBackgrounds'];
    $generatedAvatarFor = function ($acctId) use ($avatarBackgrounds) {
        $bg = $avatarBackgrounds[crc32($acctId) % count($avatarBackgrounds)];
        return 'https://api.dicebear.com/9.x/personas/svg?seed=' . urlencode($acctId) . '&backgroundColor=' . $bg;
    };
    $photoAssetFor = function ($acctId) use ($residentPhotoAsset, $generatedAvatarFor) {
        return $residentPhotoAsset[$acctId] ?? $generatedAvatarFor($acctId);
    };
    // Only Labangtaytay has a real seal asset on file (same as the mobile
    // app); every other barangay falls back to a generic badge in the UI
    // rather than a missing/broken image.
    $barangaySealAsset = $__fixtures['barangaySealAsset'];
    $individuals     = collect($data['individuals'])->map(function ($ind) use ($citizenAccounts, $photoAssetFor, $residentIdImageAsset) {
        if (!empty($ind['citizen_account_id']) && $citizenAccounts->has($ind['citizen_account_id'])) {
            $acct = $citizenAccounts->get($ind['citizen_account_id']);
            // Access tier mirrors Esperanza-Mobile's 3-tier model: only a
            // 'Verified' status grants Verified (full Dokyu/Tulong access);
            // every other status (Pending Review, Under Verification,
            // Rejected, Draft) is Unverified.
            $ind['citizen_account'] = [
                'id'                   => $acct['id'],
                'email'                => $acct['email'],
                'mobile'               => $acct['mobile'],
                'status'               => $acct['status'] === 'Approved' ? 'Verified' : $acct['status'],
                'access_level'         => $acct['status'] === 'Approved' ? 'Verified' : 'Unverified',
                'resident_since'       => $acct['residentSince'],
                'registered_at'        => $acct['registeredAt'],
                'id_uploaded'          => $acct['idUploaded'],
                'face_verified'        => $acct['faceVerified'],
                'terms_accepted'       => $acct['termsAccepted'],
                'profile_completeness' => $acct['profileCompleteness'],
                // The photo shown on this resident's profile is the selfie
                // captured during onboarding — only available once both the
                // ID upload and face-verification wizard steps are done.
                'photo_captured'       => (bool) ($acct['idUploaded'] && $acct['faceVerified']),
                'reject_remarks'       => $acct['rejectRemarks'] ?? null,
                'identity_check'       => [
                    'face_match'  => $acct['identityCheck']['faceMatch']  ?? 'Not Reviewed',
                    'remarks'     => $acct['identityCheck']['remarks']    ?? null,
                    'reviewed_by' => $acct['identityCheck']['reviewedBy'] ?? null,
                    'reviewed_at' => $acct['identityCheck']['reviewedAt'] ?? null,
                ],
                'mobile_verified'      => $acct['mobileVerified'] ?? false,
                // Only meaningful once an officer has actually approved the
                // registration — an unapproved ID upload isn't "verified" yet.
                // A resident may have more than one valid ID on file.
                'verified_ids'         => $acct['status'] === 'Approved' ? ($acct['verifiedIds'] ?? []) : [],
                // Per-signal admin review record — same "past the review
                // queue" boundary already used above for identity_check's
                // own default. An account still sitting in Pending Review /
                // Under Verification genuinely hasn't had its individual
                // signals reviewed yet (e.g. Ronaldo — every wizard step
                // done, account still Pending Review), so every signal
                // correctly reads "Not Reviewed" in the review popup. Once
                // an officer has actually moved the account past that queue
                // (Approved or Rejected), every signal the citizen completed
                // was necessarily reviewed to get there, so it's seeded as
                // reviewed here instead of defaulting to "Not Reviewed"
                // forever — the popup can only show a real distinction
                // between the two states if the underlying data has one.
                'signal_reviews'       => in_array($acct['status'], ['Pending Review', 'Under Verification'], true)
                    ? []
                    : collect([
                        'id_document' => $acct['idUploaded'],
                        'face_scan'   => $acct['faceVerified'],
                        'mobile'      => $acct['mobileVerified'] ?? false,
                        'terms'       => $acct['termsAccepted'],
                    ])->filter()->map(fn () => [
                        'status'      => 'Complete',
                        'remarks'     => null,
                        'reviewed_by' => $acct['identityCheck']['reviewedBy'] ?? 'LGU Verification Staff',
                        'reviewed_at' => $acct['identityCheck']['reviewedAt'] ?? $acct['registeredAt'],
                    ])->all(),
                // Real seeded photo/ID assets ported from the mobile app for
                // the handful of residents that have one — see the mapping
                // above this closure.
                'photo_asset'          => $photoAssetFor($acct['id']),
                'id_image_asset'       => $residentIdImageAsset[$acct['id']] ?? null,
            ];
        }
        return $ind;
    })->all();
    $families        = $data['families'];
    $households      = $data['households'];
    $barangayCoverage= $data['barangayCoverage'];
    $qualityIssues   = $data['dataQualityIssues'];
    $municipalStats  = $data['municipalStats'];
    $barangayStats   = $data['barangayStats'];
    $barangayOptions = config('esperanza.barangays');
    $tab  = $tab  ?? 'registry';
    $view = $view ?? 'individuals';
@endphp

<x-layouts.admin title="Constituents" subtitle="LGU constituent registry — residents, families, households." active="constituents">
<div
    x-data="{
        tab:  '{{ $tab }}',
        view: '{{ $view }}',
        individuals:    @js($individuals),
        barangaySealAsset: @js($barangaySealAsset),
        barangaySealFor(brgy) { return this.barangaySealAsset[brgy] || null; },
        families:       @js($families),
        households:     @js($households),
        qualityIssues:  @js($qualityIssues),
        municipalStats: @js($municipalStats),
        barangayStats:  @js($barangayStats),
        // Landed here via the Back to Profile link from Dokyu/Tulong — reopen
        // exactly the resident that navigation started from, then forget the flag.
        init() {
            const stored = sessionStorage.getItem('reopenResident');
            if (!stored) return;
            sessionStorage.removeItem('reopenResident');
            try {
                const target = JSON.parse(stored);
                const person = this.individuals.find(i => i.id === target.id);
                if (person) this.$nextTick(() => this.openIndividual(person));
            } catch (e) { /* malformed flag — ignore */ }
        },
        search:             '',
        barangayFilter:     '',
        verificationFilter: '',
        statusFilter:       '',
        tagFilter:          '',
        dqTypeFilter:       '',
        dqStatusFilter:     '',
        sortField: 'name',
        sortDir:   'asc',
        showIndividualDetail: false,
        showAddIndividual:    false,
        showFamilyDetail:     false,
        showAddFamily:        false,
        showHouseholdDetail:  false,
        showAddHousehold:     false,
        showLifecycle:        false,
        showMerge:            false,
        showAdvancedFilters:  false,
        showDQDetail:         false,
        showAddMenu:          false,
        selectedIndividual:   null,
        selectedFamily:       null,
        selectedHousehold:    null,
        selectedDQIssue:      null,
        lifecycleAction:      '',
        addIndividualStep: 1,
        addFamilyStep:     1,
        addHouseholdStep:  1,
        individualTab: 'Overview',
        familyTab:     'overview',
        householdTab:  'overview',
        // Issuing office per ID type — mirrors mobile's GovernmentIdRecord.
        // issuingOffice field, derived here from the ID type name itself
        // rather than stored per-record, since it's a fixed 1:1 mapping.
        issuingOfficeFor(idType) {
            const map = {
                'PhilSys National ID': 'Philippine Statistics Authority',
                'Driver\'s License (LTO)': 'Land Transportation Office',
                'UMID': 'SSS / GSIS / PhilHealth / Pag-IBIG',
                'Voter\'s ID': 'Commission on Elections',
                'Philippine Passport': 'Department of Foreign Affairs',
                'Postal ID': 'Philippine Postal Corporation',
                'PRC ID': 'Professional Regulation Commission',
            };
            return map[idType] || 'Philippine Government';
        },
        get individualTimeline() {
            const r = this.selectedIndividual; if (!r) return [];
            const items = [];
            if (r.created)      items.push({ date: r.created,      label: 'Record Created', desc: 'Resident profile encoded into the system.', icon: 'user-plus',       color: 'blue'   });
            if (r.last_updated) items.push({ date: r.last_updated, label: 'Last Updated',   desc: 'Profile data last modified.',                icon: 'pencil',           color: 'amber'  });
            (r.document_requests   || []).forEach(d => items.push({ date: d.submitted, label: 'Dokyu · ' + d.type,  desc: d.status + ' — ' + d.office,  icon: 'file-text',        color: 'indigo' }));
            (r.assistance_requests || []).forEach(a => items.push({ date: a.submitted, label: 'Tulong · ' + a.type, desc: a.status + ' — ' + a.office,  icon: 'hand-heart',       color: 'rose'   }));
            (r.incident_reports    || []).forEach(i => items.push({ date: i.date,      label: 'Sakuna · ' + i.type, desc: i.status + ' — ' + i.location, icon: 'triangle-alert',  color: 'orange' }));
            return items.sort((a, b) => new Date(b.date) - new Date(a.date));
        },
        get activeStats() {
            const s = this.$store.session.account?.scope;
            return (!s || s === 'Municipal') ? this.municipalStats : (this.barangayStats[s] ?? this.municipalStats);
        },
        get isMunicipalScope() {
            const s = this.$store.session.account?.scope;
            return !s || s === 'Municipal';
        },
        cvType: 'Individual',
        cvSearch: '',
        cvStatusFilter: '',
        cvBarangayFilter: '',
        cvTagFilter: '',
        rejectAccountRemarks: '',
        faceMismatchRemarks: '',
        // Every resident in scope belongs here, not just the ones who signed
        // up on the Citizen Portal — Individual/Family/Household already show
        // everyone, so Citizen Verification's Individual list matches that
        // same total instead of silently dropping non-portal residents.
        // Residents with no citizen_account render as Not Registered further
        // down, since there's nothing to approve/reject for them yet.
        get citizenAccountList() {
            return this.individuals.filter(i => this.$store.session.inScope(i.barangay));
        },
        get pendingCitizenVerificationCount() {
            const indiv = this.citizenAccountList.filter(i => i.citizen_account && ['Pending Review', 'Under Verification'].includes(i.citizen_account.status)).length;
            const fam = this.cvFamilyList.filter(f => f.verification !== 'Verified').length;
            const hh = this.cvHouseholdList.filter(h => h.verification !== 'Verified').length;
            return indiv + fam + hh;
        },
        get cvCounts() {
            const list = this.citizenAccountList;
            return {
                pending: list.filter(i => i.citizen_account?.status === 'Pending Review').length,
                underVerification: list.filter(i => i.citizen_account?.status === 'Under Verification').length,
                verified: list.filter(i => i.citizen_account?.status === 'Approved').length,
                rejected: list.filter(i => i.citizen_account?.status === 'Rejected').length,
                flagged: list.filter(i => i.citizen_account?.identity_check?.face_match === 'Mismatch' || (i.identity_flags || []).length).length,
                notRegistered: list.filter(i => !i.citizen_account).length,
            };
        },
        get filteredCitizenAccounts() {
            let list = this.citizenAccountList.filter(i => {
                if (this.cvStatusFilter === 'Flagged') {
                    if (!(i.citizen_account?.identity_check?.face_match === 'Mismatch' || (i.identity_flags || []).length)) return false;
                } else if (this.cvStatusFilter === 'Not Registered') {
                    if (i.citizen_account) return false;
                } else if (this.cvStatusFilter && i.citizen_account?.status !== this.cvStatusFilter) {
                    return false;
                }
                if (this.cvBarangayFilter && i.barangay !== this.cvBarangayFilter) return false;
                if (this.cvTagFilter && !(i.tags || []).includes(this.cvTagFilter)) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (i.name || '').toLowerCase().includes(q) || (i.id || '').toLowerCase().includes(q) || (i.barangay || '').toLowerCase().includes(q) || (i.citizen_account?.email || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = i => {
                if (!i.citizen_account) return 2;
                return ['Pending Review', 'Under Verification'].includes(i.citizen_account.status) ? 0 : 1;
            };
            return [...list].sort((a, b) => priority(a) - priority(b));
        },
        verifyCitizenAccount(i) {
            i.citizen_account.status = 'Approved';
            i.citizen_account.access_level = 'Verified';
            delete i.citizen_account.reject_remarks;
            this.$dispatch('toast', { message: i.name + '\'s citizen account was verified.', variant: 'success' });
        },
        rejectCitizenAccount(i) {
            if (!this.rejectAccountRemarks.trim()) return;
            i.citizen_account.status = 'Rejected';
            i.citizen_account.access_level = 'Unverified';
            i.citizen_account.reject_remarks = this.rejectAccountRemarks.trim();
            this.rejectAccountRemarks = '';
            this.$dispatch('toast', { message: i.name + '\'s citizen account was rejected.', variant: 'error' });
        },
        flagFaceMismatch(i) {
            if (!this.faceMismatchRemarks.trim()) return;
            i.citizen_account.identity_check.face_match = 'Mismatch';
            i.citizen_account.identity_check.remarks = this.faceMismatchRemarks.trim();
            i.citizen_account.identity_check.reviewed_by = this.$store.session.account?.name || 'LGU Verification Staff';
            i.citizen_account.identity_check.reviewed_at = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            this.faceMismatchRemarks = '';
            this.$dispatch('toast', { message: 'Face-match flag recorded for ' + i.name + '.', variant: 'warning' });
        },
        clearFaceMismatch(i) {
            i.citizen_account.identity_check.face_match = 'Match';
            i.citizen_account.identity_check.remarks = null;
            i.citizen_account.identity_check.reviewed_by = this.$store.session.account?.name || 'LGU Verification Staff';
            i.citizen_account.identity_check.reviewed_at = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            this.$dispatch('toast', { message: 'Face match confirmed for ' + i.name + '.', variant: 'success' });
        },
        // Per-signal review — each onboarding signal (Terms, ID Document,
        // Face Scan, Mobile Number) can be independently confirmed complete
        // or rejected once the citizen has actually completed that step,
        // instead of the whole account only ever moving as one block.
        reviewSignal(i, signalKey, outcome, remarks) {
            if (!i.citizen_account.signal_reviews) i.citizen_account.signal_reviews = {};
            i.citizen_account.signal_reviews[signalKey] = {
                status: outcome,
                remarks: remarks || null,
                reviewed_by: this.$store.session.account?.name || 'LGU Verification Staff',
                reviewed_at: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }),
            };
            this.$dispatch('toast', { message: signalKey + ' marked as ' + outcome + '.', variant: outcome === 'Complete' ? 'success' : 'warning' });
        },
        // Duplicate Identity Signal resolution — the flag alone never decides
        // anything; an officer must actually reach the resident first. Two
        // outcomes only, matching how this is handled in practice:
        //  1) The resident confirms it's their own second account: it stays
        //     usable but is permanently blocked from ever becoming Verified,
        //     since Dokyu/Tulong access must come from their one real Verified
        //     account instead.
        //  2) The resident says it isn't theirs: the account is cancelled.
        //     Flagging it as a suspected impostor account is a separate,
        //     deliberate admin decision made only after that denial is
        //     confirmed — never automatic.
        duplicateResolutionRemarks: '',
        resolveDuplicateOwner(i, flag) {
            if (!this.duplicateResolutionRemarks.trim()) return;
            flag.resolution = 'confirmed_owner';
            flag.resolution_remarks = this.duplicateResolutionRemarks.trim();
            flag.resolved_by = this.$store.session.account?.name || 'LGU Verification Staff';
            flag.resolved_at = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            i.citizen_account.status = 'Rejected';
            i.citizen_account.access_level = 'Unverified';
            i.citizen_account.verification_blocked = true;
            i.citizen_account.verification_blocked_reason = 'Confirmed duplicate of the resident\'s own existing account. This account cannot be verified — Dokyu and Tulong access must come from their original Verified account.';
            this.duplicateResolutionRemarks = '';
            this.$dispatch('toast', { message: 'Confirmed as a duplicate — this account is now permanently blocked from verification.', variant: 'warning' });
        },
        resolveDuplicateDenied(i, flag) {
            if (!this.duplicateResolutionRemarks.trim()) return;
            flag.resolution = 'denied';
            flag.resolution_remarks = this.duplicateResolutionRemarks.trim();
            flag.resolved_by = this.$store.session.account?.name || 'LGU Verification Staff';
            flag.resolved_at = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            flag.flagged_as_impostor = null;
            i.citizen_account.status = 'Cancelled';
            i.citizen_account.access_level = 'Unverified';
            this.duplicateResolutionRemarks = '';
            this.$dispatch('toast', { message: 'Account cancelled — confirmed not to belong to ' + i.name + '.', variant: 'error' });
        },
        setImpostorFlag(i, flag, value) {
            flag.flagged_as_impostor = value;
            this.$dispatch('toast', { message: value ? 'Flagged as a suspected impostor account.' : 'Closed without an impostor flag.', variant: value ? 'warning' : 'info' });
        },
        get cvFamilyList() {
            return this.families.filter(f => this.$store.session.inScope(f.barangay));
        },
        get cvHouseholdList() {
            return this.households.filter(h => this.$store.session.inScope(h.barangay));
        },
        get cvFamilyCounts() {
            const list = this.cvFamilyList;
            return {
                unverified: list.filter(f => f.verification === 'Unverified').length,
                partial: list.filter(f => f.verification === 'Partially Verified' || f.verification === 'For Validation' || f.verification === 'Missing Documents').length,
                verified: list.filter(f => f.verification === 'Verified').length,
                rejected: list.filter(f => f.verification === 'Rejected').length,
            };
        },
        get cvHouseholdCounts() {
            const list = this.cvHouseholdList;
            return {
                unverified: list.filter(h => h.verification === 'Unverified').length,
                partial: list.filter(h => h.verification === 'Partially Verified' || h.verification === 'For Validation' || h.verification === 'Missing Documents').length,
                verified: list.filter(h => h.verification === 'Verified').length,
                rejected: list.filter(h => h.verification === 'Rejected').length,
            };
        },
        get cvVerificationOptions() {
            return ['Unverified', 'Partially Verified', 'For Validation', 'Missing Documents', 'Verified', 'Rejected'];
        },
        get filteredCvFamilies() {
            let list = this.cvFamilyList.filter(f => {
                if (this.cvStatusFilter && f.verification !== this.cvStatusFilter) return false;
                if (this.cvBarangayFilter && f.barangay !== this.cvBarangayFilter) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (f.label || '').toLowerCase().includes(q) || (f.id || '').toLowerCase().includes(q) || (f.barangay || '').toLowerCase().includes(q) || (f.head_name || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = v => v === 'Verified' ? 1 : 0;
            return [...list].sort((a, b) => priority(a.verification) - priority(b.verification));
        },
        get filteredCvHouseholds() {
            let list = this.cvHouseholdList.filter(h => {
                if (this.cvStatusFilter && h.verification !== this.cvStatusFilter) return false;
                if (this.cvBarangayFilter && h.barangay !== this.cvBarangayFilter) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (h.label || '').toLowerCase().includes(q) || (h.id || '').toLowerCase().includes(q) || (h.barangay || '').toLowerCase().includes(q) || (h.head_name || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = v => v === 'Verified' ? 1 : 0;
            return [...list].sort((a, b) => priority(a.verification) - priority(b.verification));
        },
        verifyFamily(f) {
            f.verification = 'Verified';
            this.$dispatch('toast', { message: f.label + ' has been marked as verified.', variant: 'success' });
        },
        verifyHousehold(h) {
            h.verification = 'Verified';
            this.$dispatch('toast', { message: h.label + ' has been marked as verified.', variant: 'success' });
        },
        get filteredIndividuals() {
            let list = this.individuals.filter(r => {
                if (!this.$store.session.inScope(r.barangay)) return false;
                if (this.barangayFilter && r.barangay !== this.barangayFilter) return false;
                if (this.verificationFilter && r.verification !== this.verificationFilter) return false;
                if (this.tagFilter && !(r.tags||[]).includes(this.tagFilter)) return false;
                if (this.search) {
                    const q = this.search.toLowerCase();
                    return (r.name||'').toLowerCase().includes(q)||(r.id||'').toLowerCase().includes(q)||(r.barangay||'').toLowerCase().includes(q)||(r.occupation||'').toLowerCase().includes(q)||(r.contact||'').includes(q);
                }
                return true;
            });
            if (this.sortField) {
                list = [...list].sort((a,b) => this.sortDir==='asc' ? String(a[this.sortField]??'').localeCompare(String(b[this.sortField]??'')) : String(b[this.sortField]??'').localeCompare(String(a[this.sortField]??'')));
            }
            return list;
        },
        get filteredFamilies() {
            return this.families.filter(f => {
                if (!this.$store.session.inScope(f.barangay)) return false;
                if (this.barangayFilter && f.barangay !== this.barangayFilter) return false;
                if (this.search) { const q=this.search.toLowerCase(); return (f.label||'').toLowerCase().includes(q)||(f.head_name||'').toLowerCase().includes(q)||(f.id||'').toLowerCase().includes(q); }
                return true;
            });
        },
        get filteredHouseholds() {
            return this.households.filter(h => {
                if (!this.$store.session.inScope(h.barangay)) return false;
                if (this.barangayFilter && h.barangay !== this.barangayFilter) return false;
                if (this.search) { const q=this.search.toLowerCase(); return (h.label||'').toLowerCase().includes(q)||(h.head_name||'').toLowerCase().includes(q)||(h.id||'').toLowerCase().includes(q); }
                return true;
            });
        },
        get filteredQualityIssues() {
            return this.qualityIssues.filter(i => {
                if (!this.$store.session.inScope(i.barangay)) return false;
                if (this.dqTypeFilter && i.type !== this.dqTypeFilter) return false;
                if (this.dqStatusFilter && i.status !== this.dqStatusFilter) return false;
                if (this.search) { const q=this.search.toLowerCase(); return (i.record_name||'').toLowerCase().includes(q)||(i.description||'').toLowerCase().includes(q)||(i.barangay||'').toLowerCase().includes(q); }
                return true;
            });
        },
        openIndividual(r) { this.selectedIndividual=r; this.individualTab='Overview'; this.rejectAccountRemarks=''; this.showIndividualDetail=true; this.$nextTick(()=>window.renderIcons?.()); },
        // Real cross-module navigation, not just a sub-tab switch: leaves the
        // Constituents page entirely, lands on this resident's requests in the
        // Dokyu/Tulong module, and remembers who to reopen when they come back.
        goToDokyu(r, ref) {
            sessionStorage.setItem('returnToProfile', JSON.stringify({ id: r.id, name: r.name }));
            const q = ref ? 'ref=' + encodeURIComponent(ref) : 'citizen=' + encodeURIComponent(r.name);
            window.location.href = '/admin/document-requests?' + q;
        },
        goToTulong(r, ref) {
            sessionStorage.setItem('returnToProfile', JSON.stringify({ id: r.id, name: r.name }));
            const q = ref ? 'ref=' + encodeURIComponent(ref) : 'citizen=' + encodeURIComponent(r.name);
            window.location.href = '/admin/assistance-requests?' + q;
        },
        goToSakuna(r, ref) {
            sessionStorage.setItem('returnToProfile', JSON.stringify({ id: r.id, name: r.name }));
            window.location.href = '/admin/sakuna?ref=' + encodeURIComponent(ref);
        },
        openFamily(f)     { this.selectedFamily=f;     this.familyTab='overview';     this.showFamilyDetail=true;     this.$nextTick(()=>window.renderIcons?.()); },
        openHousehold(h)  { this.selectedHousehold=h;  this.householdTab='overview';  this.showHouseholdDetail=true;  this.$nextTick(()=>window.renderIcons?.()); },
        openLifecycle(r, action) { this.selectedIndividual=r; this.lifecycleAction=action; this.showLifecycle=true; this.$nextTick(()=>window.renderIcons?.()); },
        openDQIssue(issue)       { this.selectedDQIssue=issue; this.showDQDetail=true; this.$nextTick(()=>window.renderIcons?.()); },
        verificationBadge(v) {
            const m={'Verified':'text-emerald-700 bg-emerald-50 ring-1 ring-emerald-200','Partially Verified':'text-amber-700 bg-amber-50 ring-1 ring-amber-200','For Validation':'text-indigo-700 bg-indigo-50 ring-1 ring-indigo-200','Missing Documents':'text-orange-700 bg-orange-50 ring-1 ring-orange-200','Unverified':'text-slate-500 bg-slate-100 ring-1 ring-slate-200','Rejected':'text-rose-700 bg-rose-50 ring-1 ring-rose-200'};
            return { class: m[v]||m['Unverified'], label: v || 'Unverified' };
        },
        statusBadge(s) {
            const m={'Active':'text-emerald-700 bg-emerald-50','New Resident':'text-brand-700 bg-brand-50','Transferred':'text-indigo-700 bg-indigo-50','Temporarily Away':'text-amber-700 bg-amber-50','Overseas':'text-teal-700 bg-teal-50','Deceased':'text-slate-500 bg-slate-100','Archived':'text-slate-400 bg-slate-50','Under Review':'text-purple-700 bg-purple-50'};
            return m[s]||m['Active'];
        },
        severityBadge(s) {
            const m={'Critical':'text-rose-700 bg-rose-50 ring-1 ring-rose-200','High':'text-orange-700 bg-orange-50 ring-1 ring-orange-200','Medium':'text-amber-700 bg-amber-50 ring-1 ring-amber-200','Low':'text-slate-600 bg-slate-100 ring-1 ring-slate-200'};
            return m[s]||m['Low'];
        },
        completionColor(pct) { return pct>=90?'bg-emerald-500':pct>=70?'bg-amber-500':'bg-rose-500'; },
        completionTextColor(pct) { return pct>=90?'text-emerald-700':pct>=70?'text-amber-700':'text-rose-700'; },
        // Real completeness derived from which profile sections are actually
        // filled in, not the static config seed value — evaluated only over
        // sections that apply to this resident (e.g. Citizen Portal onboarding
        // only counts against residents who actually have an account), so a
        // resident with no portal account isn't unfairly capped below 100%.
        // Reflects ONLY how far the resident's own signup/onboarding went —
        // the steps of the Esperanza Mobile registration wizard itself.
        // Deliberately excludes anything an LGU encoder fills in later
        // (household/family linkage, verification badge, emergency contact,
        // Dokyu/Tulong activity) — those are record-keeping, not onboarding.
        computeProfileCompleteness(ind) {
            if (!ind) return 0;
            // No Citizen Portal account -> no onboarding wizard was ever run;
            // fall back to the barangay-profiling completeness already on file.
            if (!ind.citizen_account) return ind.profile_pct ?? 0;
            const acct = ind.citizen_account;
            const steps = [
                !!(ind.name && ind.dob && ind.sex && ind.civil_status),      // Personal Information step
                !!(ind.barangay && ind.sitio && ind.address),                 // Address step
                !!(ind.contact) && !!acct.mobile_verified,                    // Contact & Security step
                !!acct.terms_accepted,                                        // Terms & Conditions accepted
                !!acct.id_uploaded,                                           // Valid ID submitted
                !!acct.face_verified,                                         // Face / liveness scan captured
            ];
            return Math.round((steps.filter(Boolean).length / steps.length) * 100);
        },
        // Recent Activity feed for the Overview tab — merges this
        // resident's Dokyu + Tulong request history (already on their
        // record; no separate mock data to keep in sync) into one
        // notification-style list, newest first. Server-rendered from the
        // Constituents record itself, so it's always populated the moment
        // this modal opens — unlike the citizen-facing Notifications page,
        // it doesn't depend on a live browser action or page-visit order.
        recentActivity(ind) {
            if (!ind) return [];
            const dokyu = (ind.document_requests || []).map(r => ({ ...r, portal: 'Dokyu', icon: 'file-stack' }));
            const tulong = (ind.assistance_requests || []).map(r => ({ ...r, portal: 'Tulong', icon: 'hand-heart' }));
            return [...dokyu, ...tulong]
                .sort((a, b) => new Date(b.submitted) - new Date(a.submitted))
                .slice(0, 6);
        },
        onboardingStatus(acct) {
            if (!acct) return 'Draft';
            const wizardDone = !!(acct.terms_accepted && acct.id_uploaded && acct.face_verified);
            if (!wizardDone) return 'Draft';
            if (acct.status === 'Approved') return 'Completed';
            if (acct.status === 'Rejected') return 'Rejected';
            return 'Submitted';
        },
        onboardingSteps(acct) {
            if (!acct) return [];
            const wizardDone = !!(acct.terms_accepted && acct.id_uploaded && acct.face_verified);
            const isVerified = acct.status === 'Approved';
            const isRejected = acct.status === 'Rejected';
            return [
                { label: 'Account Created', done: true },
                { label: 'Personal Information Completed', done: true },
                { label: 'Terms & Conditions Accepted', done: !!acct.terms_accepted },
                { label: 'Valid ID Submitted', done: !!acct.id_uploaded },
                { label: 'Face Verification Completed', done: !!acct.face_verified },
                { label: 'Information Reviewed', done: wizardDone },
                { label: 'Submitted for Verification', done: wizardDone },
                { label: acct.status || 'Pending Review', done: isVerified, rejected: isRejected, current: wizardDone && !isVerified && !isRejected },
            ];
        },
        // The exact push-notification/in-app text the resident would see on
        // Esperanza Mobile for their current status — makes the verification
        // flow tangible to admins instead of an abstract status label.
        citizenNotificationMessage(acct) {
            if (!acct) return '';
            if (acct.status === 'Cancelled') {
                return 'This account has been cancelled by the LGU. If you believe this is a mistake, please visit your Barangay Hall.';
            }
            if (acct.verification_blocked) {
                return 'This account can\'t be verified — you already have a Verified Esperanza account. Please log in with that account to access Dokyu and Tulong.';
            }
            if (acct.status === 'Rejected') {
                return acct.reject_remarks
                    ? `Your verification requires correction: ${acct.reject_remarks}`
                    : 'Your verification requires correction. Please review and resubmit your details.';
            }
            if (acct.status === 'Under Verification') return 'Your identity verification is currently being reviewed by the LGU. We will notify you once a decision is made.';
            if (acct.status === 'Pending Review') return 'Your registration has been submitted and is awaiting LGU review.';
            if (acct.status === 'Approved') return 'Your identity has been verified. Welcome — you now have full access to Dokyu and Tulong.';
            return 'Please complete your registration to continue.';
        },
        // Maps a Sakuna incident's current status onto the Reported -> Barangay
        // Intake -> DRRM Review -> Response -> Resolution data path.
        sakunaStage(status) {
            const stages = { 'Submitted': 1, 'Validated': 2, 'Dispatched': 3, 'Resolved': 4, 'Closed': 4 };
            return stages[status] ?? 0;
        },
        exportCSV(rows, filename) {
            if (!rows.length) { this.$dispatch('toast',{message:'No records to export.',variant:'warning'}); return; }
            const keys=Object.keys(rows[0]).filter(k=>!['services','emergency','member_ids','family_ids'].includes(k));
            const csv=[keys.join(','),...rows.map(r=>keys.map(k=>JSON.stringify(r[k]??'')).join(','))].join('\n');
            const a=Object.assign(document.createElement('a'),{href:URL.createObjectURL(new Blob([csv],{type:'text/csv'})),download:filename});
            a.click(); this.$dispatch('toast',{message:'Exported successfully.',variant:'success'});
        },
        can(sub,perm) { return this.$store.session.canPerm?this.$store.session.canPerm(sub,perm):true; },
        clearFilters() { this.search='';this.barangayFilter='';this.verificationFilter='';this.statusFilter='';this.tagFilter='';this.dqTypeFilter='';this.dqStatusFilter='';this.cvSearch='';this.cvStatusFilter='';this.cvBarangayFilter='';this.cvTagFilter=''; },
    }"
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
                <h2 class="text-lg font-bold text-navy-900">Constituent Registry</h2>
                <p class="text-xs text-slate-400 mt-0.5">Municipality of Esperanza · 20 barangays · <span x-text="isMunicipalScope?'Municipality-wide view':'Brgy. '+$store.session.account?.scope+' view'"></span></p>
            </div>
            <div class="flex items-center gap-2 relative" x-show="can('const_resident','create') || can('const_family','create') || can('const_household','create')">
                <button @click="showAddMenu=!showAddMenu" @click.outside="showAddMenu=false"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-brand-600 text-white hover:bg-brand-700 shadow-sm shadow-brand-200 transition-all">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>Add Record
                    <i data-lucide="chevron-down" class="w-3 h-3 opacity-70 transition-transform" :class="showAddMenu&&'rotate-180'"></i>
                </button>
                <div x-show="showAddMenu" x-transition.origin.top.right x-cloak
                     class="absolute right-0 top-full mt-1.5 w-44 bg-white rounded-xl border border-slate-200 shadow-lg z-30 overflow-hidden">
                    <button x-show="can('const_resident','create')" @click="addIndividualStep=1;showAddIndividual=true;showAddMenu=false;$nextTick(()=>window.renderIcons?.())"
                            class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2.5">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5 text-brand-500"></i>New Resident
                    </button>
                    <button x-show="can('const_family','create')" @click="addFamilyStep=1;showAddFamily=true;showAddMenu=false;$nextTick(()=>window.renderIcons?.())"
                            class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2.5">
                        <i data-lucide="users" class="w-3.5 h-3.5 text-purple-500"></i>New Family
                    </button>
                    <button x-show="can('const_household','create')" @click="addHouseholdStep=1;showAddHousehold=true;showAddMenu=false;$nextTick(()=>window.renderIcons?.())"
                            class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2.5">
                        <i data-lucide="house" class="w-3.5 h-3.5 text-emerald-500"></i>New Household
                    </button>
                </div>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
            <x-ui.stat-card @click="tab='registry';view='individuals';clearFilters()" class="cursor-pointer" label="Registered Residents" valueBind="activeStats.residents"  icon="users"          color="brand"  :delay="0"   />
            <x-ui.stat-card @click="tab='registry';view='households';clearFilters()" class="cursor-pointer" label="Active Households"    valueBind="activeStats.households" icon="house"           color="green"  :delay="40"  />
            <x-ui.stat-card @click="tab='registry';view='families';clearFilters()" class="cursor-pointer" label="Recorded Families"    valueBind="activeStats.families"   icon="users-round"    color="purple" :delay="80"  />
            <x-ui.stat-card @click="tab='registry';view='individuals';clearFilters();verificationFilter='Verified'" class="cursor-pointer" label="ID Verified"          valueBind="activeStats.verified"   icon="badge-check"    color="gold"   :delay="120" />
            <x-ui.stat-card @click="tab='registry';view='individuals';clearFilters();verificationFilter='For Validation'" class="cursor-pointer" label="Pending Validation"   valueBind="activeStats.pending"    icon="clock"          color="orange" :delay="160" />
            <x-ui.stat-card @click="tab='data-quality';clearFilters()" class="cursor-pointer" label="Data Quality Issues"  valueBind="activeStats.issues"     icon="triangle-alert" color="red"    :delay="200" sublabel="Needs attention" />
        </div>

        {{-- Primary tab bar --}}
        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-5">
            <button @click="tab='registry';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5"
                    :class="tab==='registry'     ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>Registry
            </button>
            <button @click="tab='profiling';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5"
                    :class="tab==='profiling'    ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="user-round-search" class="w-3.5 h-3.5"></i>Profiling Coverage
            </button>
            <button @click="tab='data-quality';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 relative"
                    :class="tab==='data-quality' ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="triangle-alert" class="w-3.5 h-3.5"></i>Data Quality
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                      :class="tab==='data-quality' ? 'bg-white/25 text-white' : 'bg-rose-100 text-rose-600'">{{ count($qualityIssues) }}</span>
            </button>
            <button @click="tab='citizen-verification';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 relative"
                    :class="tab==='citizen-verification' ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>Citizen Verification
                <span
                    x-show="pendingCitizenVerificationCount > 0"
                    class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                    :class="tab==='citizen-verification' ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-600'"
                    x-text="pendingCitizenVerificationCount"
                ></span>
            </button>
        </div>

        {{-- ── Registry tab ────────────────────────────────────────── --}}
        <div x-show="tab === 'registry'">
            {{-- Unified toolbar: view switcher on top, search/filters/actions below --}}
            <div class="bg-white border border-slate-200 rounded-2xl shadow-card p-3 mb-4 space-y-3">
                <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto">
                    <button @click="view='individuals';clearFilters()"
                            class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                            :class="view==='individuals' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>Individuals
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600" x-text="filteredIndividuals.length"></span>
                    </button>
                    <button @click="view='families';clearFilters()"
                            class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                            :class="view==='families'    ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>Families
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600" x-text="filteredFamilies.length"></span>
                    </button>
                    <button @click="view='households';clearFilters()"
                            class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                            :class="view==='households'  ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <i data-lucide="house" class="w-3.5 h-3.5"></i>Households
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600" x-text="filteredHouseholds.length"></span>
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Search" x-model="search" type="text"
                               :placeholder="view==='individuals'?'Search residents...':view==='families'?'Search families...':'Search households...'"
                               class="w-52 sm:w-64 pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                    </div>
                    <select aria-label="Barangay filter" x-show="isMunicipalScope" x-model="barangayFilter"
                            class="text-xs rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
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
                                class="text-xs rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                            <option value="">All Verification</option>
                            <option>Verified</option>
                            <option>Partially Verified</option>
                            <option>For Validation</option>
                            <option>Unverified</option>
                        </select>
                        <select aria-label="Tag filter" x-model="tagFilter"
                                class="text-xs rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
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
                        </select>
                    </div>
                    <button x-show="search||barangayFilter||verificationFilter||tagFilter" @click="clearFilters()"
                            class="text-xs text-slate-500 hover:text-rose-600 flex items-center gap-1 transition-colors">
                        <i data-lucide="circle-x" class="w-3.5 h-3.5"></i>Clear
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="exportCSV(view==='individuals'?filteredIndividuals:view==='families'?filteredFamilies:filteredHouseholds,view+'-export.csv')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 hover:bg-white hover:border-slate-300 transition-all">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>Export CSV
                    </button>
                    <button onclick="window.print()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 hover:bg-white hover:border-slate-300 transition-all">
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

        {{-- ── Data Quality tab ────────────────────────────────────── --}}
        <div x-show="tab === 'data-quality'" x-cloak>
            @include('admin.constituents.tab-data-quality')
        </div>

    </div>

    {{-- ── Modals ──────────────────────────────────────────────────── --}}
    <div x-show="showIndividualDetail" x-cloak>@include('admin.constituents.modal-individual-detail')</div>
    <div x-show="showAddIndividual"    x-cloak>@include('admin.constituents.modal-add-individual')</div>
    <div x-show="showFamilyDetail"     x-cloak>@include('admin.constituents.modal-family-detail')</div>
    <div x-show="showAddFamily"        x-cloak>@include('admin.constituents.modal-add-family')</div>
    <div x-show="showHouseholdDetail"  x-cloak>@include('admin.constituents.modal-household-detail')</div>
    <div x-show="showAddHousehold"     x-cloak>@include('admin.constituents.modal-add-household')</div>
    <div x-show="showLifecycle"        x-cloak>@include('admin.constituents.modal-lifecycle')</div>
    <div x-show="showMerge"            x-cloak>@include('admin.constituents.modal-merge')</div>
    <div x-show="showDQDetail"         x-cloak>@include('admin.constituents.modal-dq-detail')</div>

</div>
</x-layouts.admin>
