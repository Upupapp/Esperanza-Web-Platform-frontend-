{{-- ============================================================
     FILE: modal-individual-detail.blade.php
     Partial: Comprehensive individual detail modal
     Alpine scope: parent x-data (showIndividualDetail, selectedIndividual, etc.)
     ============================================================ --}}

<div
    x-data="{
        get open() { return showIndividualDetail; },
        set open(v) { showIndividualDetail = v; },
        reviewingSignal: null,
        get currentSignalMeta() {
            const acct = selectedIndividual?.citizen_account;
            // Distinctive per-signal reviewer guidance — what to actually
            // check before deciding, not just a bare Confirm/Reject button.
            // Mirrors the Roles & Permissions reference's own 'Verify'
            // definition ('confirm a submitted detail or document is
            // accurate') and the Citizen Account Verification module's
            // review responsibility, applied per signal rather than as one
            // blanket account-level judgment call.
            const map = {
                id_document: {
                    key: 'id_document', label: 'ID Document', icon: 'id-card', completed: acct?.id_uploaded,
                    notCompletedText: 'Citizen has not submitted an ID yet.', completedActionText: 'Submitted', review: acct?.signal_reviews?.id_document,
                    adviceIcon: 'search-check',
                    adviceText: 'Confirm this is a valid, unexpired Philippine government ID (PhilSys, Driver\'s License, UMID, Voter\'s ID, Passport, Postal ID, or PRC ID) and that the printed name matches the resident\'s registered name exactly. Reject if the ID type isn\'t accepted, is expired, or the image is unreadable.',
                },
                face_scan: {
                    key: 'face_scan', label: 'Face Scan', icon: 'scan-face', completed: acct?.face_verified,
                    notCompletedText: 'Citizen has not completed the face scan yet.', completedActionText: 'Completed', review: acct?.signal_reviews?.face_scan,
                    adviceIcon: 'video',
                    adviceText: 'This only confirms the resident completed the live face-scan step during onboarding — not that it matches their ID. Check the capture shows a clear, unobstructed face (not a photo of a photo) before marking it complete; the actual identity comparison happens separately in Face Match below.',
                },
                mobile: {
                    key: 'mobile', label: 'Mobile Number', icon: 'smartphone', completed: acct?.mobile_verified,
                    notCompletedText: 'Citizen has not verified a mobile number yet.', completedActionText: 'Verified', review: acct?.signal_reviews?.mobile,
                    adviceIcon: 'shield-check',
                    adviceText: 'Confirm the mobile number was OTP-verified during onboarding and isn\'t already registered to a different resident. A duplicate or unverifiable number should be rejected so the resident can register the correct one.',
                },
                terms: {
                    key: 'terms', label: 'Terms', icon: 'file-check', completed: acct?.terms_accepted,
                    notCompletedText: 'Citizen has not accepted the Terms & Conditions yet.', completedActionText: 'Accepted', review: acct?.signal_reviews?.terms,
                    adviceIcon: 'clipboard-check',
                    adviceText: 'A procedural check only — confirm the acceptance date falls on or after the resident\'s registration date. This rarely needs rejecting unless the record looks tampered with.',
                },
                face_match: {
                    key: 'face_match', label: 'Face Match', icon: 'scan-eye', completed: acct?.photo_captured, review: null,
                    adviceIcon: 'scan-eye',
                    adviceText: 'Compare the submitted ID photo directly against the onboarding selfie — focus on the eyes, nose bridge, and jawline. Lighting and angle differences are expected; a fundamentally different face is not.',
                },
            };
            return map[this.reviewingSignal] || { key: null, label: '', icon: 'shield-check', completed: false, review: null, adviceIcon: null, adviceText: null };
        },
    }"
>
    <x-ui.modal maxWidth="2xl" :bare="true" :fullscreen="true">

    <div class="flex flex-col min-h-0 h-full" x-show="selectedIndividual">

        {{-- ════════════════════════════════════════════════════════
             MODAL HEADER (compact — tabs live in the side nav below)
        ════════════════════════════════════════════════════════ --}}
        <div
            class="relative shrink-0 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 text-white px-6 py-5 rounded-t-2xl overflow-hidden"
        >
            {{-- Decorative backdrop ring --}}
            <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute -bottom-6 -left-6 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>

            {{-- Compact toggle + Close --}}
            <div class="absolute top-4 right-4 flex items-center gap-1.5 z-20">
                <button
                    type="button"
                    @click="compact = !compact"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                    :title="compact ? 'Expand to full screen' : 'Shrink to popup'"
                >
                    <span x-show="compact"><i data-lucide="maximize-2" class="w-3.5 h-3.5"></i></span>
                    <span x-show="!compact"><i data-lucide="minimize-2" class="w-3.5 h-3.5"></i></span>
                </button>
                <button
                    type="button"
                    @click="showIndividualDetail = false"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                    title="Close"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="flex items-start gap-5 relative z-10">

                {{-- Profile completion ring + avatar --}}
                <div class="flex-shrink-0 relative">
                    {{-- Outer ring (SVG donut) --}}
                    <div class="relative w-20 h-20">
                        <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80">
                            <circle cx="40" cy="40" r="36" stroke="rgba(255,255,255,0.15)" stroke-width="5" fill="none"/>
                            <circle
                                cx="40" cy="40" r="36"
                                stroke="rgba(255,255,255,0.85)"
                                stroke-width="5"
                                fill="none"
                                stroke-linecap="round"
                                :stroke-dasharray="'226.2'"
                                :stroke-dashoffset="226.2 - (226.2 * (computeProfileCompleteness(selectedIndividual) || 0) / 100)"
                                class="transition-all duration-[600ms] ease-standard"
                            />
                        </svg>
                        {{-- Avatar inside ring — the real seeded portrait when one exists
                             on file for this resident (ported 1:1 from the Esperanza
                             Mobile app's own demo photos); the onboarding-captured
                             selfie placeholder when a photo-verified Citizen Portal
                             account exists but no real asset is seeded; initials
                             otherwise (no photo was ever captured for them). --}}
                        <div
                            class="absolute inset-2 rounded-full overflow-hidden flex items-center justify-center text-white font-bold text-xl uppercase"
                            :class="selectedIndividual?.citizen_account?.photo_asset ? '' : (selectedIndividual?.citizen_account?.photo_captured ? 'bg-gradient-to-br from-slate-600 to-slate-900' : 'bg-white/10')"
                        >
                            <template x-if="selectedIndividual?.citizen_account?.photo_asset">
                                <img :src="selectedIndividual.citizen_account.photo_asset" class="w-full h-full object-cover" :alt="selectedIndividual?.name">
                            </template>
                            <template x-if="!selectedIndividual?.citizen_account?.photo_asset && selectedIndividual?.citizen_account?.photo_captured">
                                <i data-lucide="user-round" class="w-9 h-9 text-white/60"></i>
                            </template>
                            <template x-if="!selectedIndividual?.citizen_account?.photo_asset && !selectedIndividual?.citizen_account?.photo_captured">
                                <span x-text="(selectedIndividual?.name || '').split(' ').map(w=>w[0]).slice(0,2).join('')"></span>
                            </template>
                        </div>
                        {{-- Face-match outcome badge — only shown once an onboarding photo exists to check --}}
                        <div
                            x-show="selectedIndividual?.citizen_account?.photo_captured"
                            x-cloak
                            class="absolute -bottom-0.5 -right-0.5 w-6 h-6 rounded-full flex items-center justify-center ring-2 ring-navy-900"
                            :class="{
                                'bg-emerald-500': selectedIndividual?.citizen_account?.identity_check?.face_match === 'Match',
                                'bg-rose-500': selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch',
                                'bg-amber-400': !['Match','Mismatch'].includes(selectedIndividual?.citizen_account?.identity_check?.face_match),
                            }"
                            :title="'Onboarding photo captured · Face match: ' + (selectedIndividual?.citizen_account?.identity_check?.face_match || 'Not Reviewed')"
                        >
                            <i data-lucide="check" class="w-3 h-3 text-white" x-show="selectedIndividual?.citizen_account?.identity_check?.face_match === 'Match'"></i>
                            <i data-lucide="triangle-alert" class="w-3 h-3 text-white" x-show="selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch'"></i>
                            <i data-lucide="clock" class="w-3 h-3 text-white" x-show="!['Match','Mismatch'].includes(selectedIndividual?.citizen_account?.identity_check?.face_match)"></i>
                        </div>
                    </div>
                    {{-- Profile % label --}}
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 bg-white/20 rounded-full px-2 py-0.5 text-[10px] font-bold text-white whitespace-nowrap">
                        <span x-text="(computeProfileCompleteness(selectedIndividual) || 0) + '%'"></span>
                    </div>
                </div>

                {{-- Identity block --}}
                <div class="flex-1 min-w-0 pt-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        {{-- Status badge --}}
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white"
                            x-text="selectedIndividual?.status"
                        ></span>
                        {{-- Verification badge --}}
                        <span
                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/10 text-white/80"
                        >
                            <i data-lucide="shield-check" class="w-3 h-3"></i>
                            <span x-text="selectedIndividual?.verification"></span>
                        </span>
                        {{-- Citizen Portal account linkage indicator --}}
                        <span
                            x-show="selectedIndividual?.citizen_account"
                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/30"
                        >
                            <i data-lucide="smartphone" class="w-3 h-3"></i>
                            Portal Linked
                        </span>
                        <span
                            x-show="!selectedIndividual?.citizen_account"
                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/5 text-white/40"
                        >
                            <i data-lucide="smartphone" class="w-3 h-3"></i>
                            No Portal Account
                        </span>
                    </div>

                    <h2 class="text-xl font-bold text-white leading-tight truncate" x-text="selectedIndividual?.name"></h2>
                    <p class="text-sm text-white/60 mt-0.5">
                        ID: <span class="font-mono font-medium text-white/80" x-text="selectedIndividual?.id"></span>
                        &nbsp;·&nbsp;
                        <span x-text="selectedIndividual?.sex || '—'"></span>,
                        <span x-text="selectedIndividual?.age + ' years old'"></span>
                    </p>

                    {{-- Role / relation chip --}}
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                            <i data-lucide="house" class="w-3 h-3"></i>
                            <span x-text="selectedIndividual?.relation || 'Member'"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                            <i data-lucide="map-pin" class="w-3 h-3"></i>
                            <span x-text="'Brgy. ' + (selectedIndividual?.barangay || '—')"></span>
                        </span>
                        {{-- Program / sector tag chips --}}
                        <template x-for="tag in (selectedIndividual?.tags || [])" :key="'hdrtag-'+tag">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                                <i data-lucide="tag" class="w-3 h-3"></i>
                                <span x-text="
                                    tag === 'solo_parent' ? 'Solo Parent' :
                                    tag === 'high_risk' ? 'High Risk' :
                                    tag === 'osy' ? 'Out-of-School Youth' :
                                    tag === '4ps' ? '4Ps' :
                                    tag === 'osca' ? 'OSCA' :
                                    tag === 'pwd' ? 'PWD' :
                                    tag.charAt(0).toUpperCase() + tag.slice(1)
                                "></span>
                            </span>
                        </template>
                    </div>

                    {{-- Compact identity-verification signifiers — at-a-glance signal
                         strip, separate from the tag chips above so the header doesn't
                         read as overloaded. Only shown once there's a Citizen Portal
                         account to actually report signals for. --}}
                    <div class="flex flex-wrap gap-2.5 mt-2.5" x-show="selectedIndividual?.citizen_account" x-cloak>
                        {{-- Wording matters here: "Submitted"/"Completed" mean the wizard
                             step happened, not that it was confirmed correct — that's the
                             separate Face Match signal, so this strip never contradicts a
                             Rejected/Mismatch status the way "Face Verified" used to. --}}
                        <template x-for="sig in [
                            { ok: selectedIndividual?.citizen_account?.id_uploaded, warn: selectedIndividual?.citizen_account?.signal_reviews?.id_document?.status === 'Rejected', onLabel: 'ID Submitted', offLabel: selectedIndividual?.citizen_account?.signal_reviews?.id_document?.status === 'Rejected' ? 'ID Document Rejected' : 'ID Not Yet Submitted', icon: 'id-card', signalKey: 'id_document' },
                            { ok: selectedIndividual?.citizen_account?.face_verified, warn: selectedIndividual?.citizen_account?.signal_reviews?.face_scan?.status === 'Rejected', onLabel: 'Face Scan Completed', offLabel: selectedIndividual?.citizen_account?.signal_reviews?.face_scan?.status === 'Rejected' ? 'Face Scan Rejected' : 'Face Scan Not Yet Done', icon: 'scan-face', signalKey: 'face_scan' },
                            { ok: selectedIndividual?.citizen_account?.mobile_verified, warn: selectedIndividual?.citizen_account?.signal_reviews?.mobile?.status === 'Rejected', onLabel: 'Mobile Verified', offLabel: selectedIndividual?.citizen_account?.signal_reviews?.mobile?.status === 'Rejected' ? 'Mobile Number Rejected' : 'Mobile Not Verified', icon: 'smartphone', signalKey: 'mobile' },
                            { ok: selectedIndividual?.citizen_account?.terms_accepted, warn: selectedIndividual?.citizen_account?.signal_reviews?.terms?.status === 'Rejected', onLabel: 'Terms Accepted', offLabel: selectedIndividual?.citizen_account?.signal_reviews?.terms?.status === 'Rejected' ? 'Terms Rejected' : 'Terms Not Accepted', icon: 'file-check', signalKey: 'terms' },
                            { ok: selectedIndividual?.citizen_account?.identity_check?.face_match === 'Match', warn: selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch', onLabel: 'Face Matches ID', offLabel: selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch' ? 'Face Does Not Match ID' : 'Face Match Not Reviewed', icon: 'scan-eye', signalKey: 'face_match' },
                        ]" :key="sig.icon">
                            {{-- Every signifier is clickable — opens that signal's own
                                 independent review popup, with its supporting evidence
                                 and its own Confirm/Reject action, whether it's already
                                 resolved or still needs action. Both chip variants are
                                 wrapped in one `contents` element because x-for templates
                                 only ever render a single root child — two sibling
                                 buttons directly inside <template> would silently drop
                                 the second one. --}}
                            <div class="contents">
                                <button
                                    type="button"
                                    @click="reviewingSignal = sig.signalKey; $nextTick(() => window.renderIcons?.())"
                                    class="inline-flex items-center gap-1 text-[11px] font-medium rounded-full hover:bg-white/10 px-1.5 py-0.5 -mx-1.5 -my-0.5 transition-colors cursor-pointer"
                                    x-show="!sig.warn"
                                    :class="sig.ok ? 'text-emerald-200' : 'text-amber-200/80'"
                                    :title="(sig.ok ? sig.onLabel : sig.offLabel) + ' — click to review'"
                                >
                                    <i :data-lucide="sig.ok ? 'circle-check' : 'circle-alert'" class="w-3 h-3"></i>
                                    <span x-text="sig.ok ? sig.onLabel : sig.offLabel"></span>
                                </button>
                                <button
                                    type="button"
                                    @click="reviewingSignal = sig.signalKey; $nextTick(() => window.renderIcons?.())"
                                    x-show="sig.warn" x-cloak
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-200 rounded-full hover:bg-white/10 px-1.5 py-0.5 -mx-1.5 -my-0.5 transition-colors cursor-pointer"
                                    :title="sig.offLabel + ' — click to review'"
                                >
                                    <i data-lucide="triangle-alert" class="w-3 h-3"></i>
                                    <span x-text="sig.offLabel"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Persistent identity-review notice — stays visible across every tab
             (not just Overview) so it isn't missed by a clerk who jumps straight
             into Dokyu/Tulong/Sakuna to process this resident's request. --}}
        <template x-for="flag in (selectedIndividual?.identity_flags || [])" :key="'persist-'+flag.type">
            <div
                class="shrink-0 flex items-center gap-2 px-6 py-2 bg-amber-50 border-b border-amber-200 text-xs text-amber-800 cursor-pointer hover:bg-amber-100 transition-colors"
                @click="individualTab = 'Overview'"
                title="Click to view details in Overview"
            
        role="button"
        tabindex="0"
        @keydown.enter.prevent="individualTab = 'Overview'"
        @keydown.space.prevent="individualTab = 'Overview'">
                <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                <span class="font-semibold" x-text="flag.type + ':'"></span>
                <span class="truncate" x-text="flag.recommendation"></span>
            </div>
        </template>

        {{-- ════════════════════════════════════════════════════════
             BODY: side nav + scrollable content panel
        ════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-h-0 flex flex-col sm:flex-row overflow-hidden">

            {{-- ── Side nav ── --}}
            <div class="w-full sm:w-48 shrink-0 flex flex-row sm:flex-col gap-0.5 border-b sm:border-b-0 sm:border-r border-slate-100 bg-slate-50/60 p-2 sm:p-2.5 overflow-x-auto sm:overflow-x-visible sm:overflow-y-auto scrollbar-thin">
                <template x-for="tab in [
                    { name: 'Overview', icon: 'layout-dashboard' },
                    { name: 'Programs & Benefits', icon: 'landmark' },
                    { name: 'Dokyu', icon: 'file-stack', count: (selectedIndividual?.document_requests || []).length },
                    { name: 'Tulong', icon: 'hand-heart', count: (selectedIndividual?.assistance_requests || []).length },
                    { name: 'Events', icon: 'calendar-clock', count: (selectedIndividual?.services || []).length },
                    { name: 'Sakuna', icon: 'triangle-alert', count: (selectedIndividual?.incident_reports || []).length },
                    { name: 'History', icon: 'history' },
                ]" :key="tab.name">
                    <button
                        @click="individualTab = tab.name"
                        class="shrink-0 sm:w-full flex items-center gap-2 sm:gap-2.5 px-3 py-2 sm:py-2.5 rounded-lg text-[13px] font-medium transition-colors whitespace-nowrap sm:whitespace-normal text-left"
                        :class="individualTab === tab.name ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'"
                    >
                        <i :data-lucide="tab.icon" class="w-4 h-4 shrink-0" :class="individualTab === tab.name ? 'text-brand-600' : 'text-slate-400'"></i>
                        <span class="flex-1 min-w-0 sm:truncate" x-text="tab.name"></span>
                        <span
                            x-show="tab.count"
                            class="text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0"
                            :class="individualTab === tab.name ? 'bg-brand-100 text-brand-700' : 'bg-slate-200 text-slate-500'"
                            x-text="tab.count"
                        ></span>
                    </button>
                </template>
            </div>

            {{-- ── Content panel ── --}}
            <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin px-6 py-5 bg-slate-50/50">

            {{-- ── TAB 1: Overview ── --}}
            <div x-show="individualTab === 'Overview'" class="space-y-4">

                {{-- Quick Summary — compact, clickable jump strip. Answers "what does
                     this resident have on file" at a glance and takes the admin
                     straight to the relevant tab instead of hunting for it. --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-3.5">
                    <div class="flex flex-wrap items-stretch gap-2">
                        <template x-for="q in [
                            { label: 'Completeness', value: computeProfileCompleteness(selectedIndividual) + '%', icon: 'chart-bar', tab: null },
                            { label: 'Verification', value: selectedIndividual?.verification, icon: 'shield-check', tab: null },
                            { label: 'Household', value: selectedIndividual?.household_id || 'Unlinked', icon: 'house', tab: null },
                            { label: 'Programs', value: (selectedIndividual?.tags || []).length, icon: 'landmark', tab: 'Programs & Benefits' },
                            { label: 'Dokyu', value: (selectedIndividual?.document_requests || []).length, icon: 'file-stack', tab: 'Dokyu' },
                            { label: 'Tulong', value: (selectedIndividual?.assistance_requests || []).length, icon: 'hand-heart', tab: 'Tulong' },
                            { label: 'Events', value: (selectedIndividual?.services || []).length, icon: 'calendar-clock', tab: 'Events' },
                            { label: 'Sakuna', value: (selectedIndividual?.incident_reports || []).length, icon: 'triangle-alert', tab: 'Sakuna' },
                        ]" :key="q.label">
                            <button
                                type="button"
                                @click="q.tab && (individualTab = q.tab)"
                                class="flex-1 min-w-[92px] rounded-xl px-2.5 py-2 text-left transition-colors"
                                :class="q.tab ? 'hover:bg-slate-50 cursor-pointer' : 'cursor-default'"
                            >
                                <div class="flex items-center gap-1 text-slate-400">
                                    <i :data-lucide="q.icon" class="w-3 h-3"></i>
                                    <span class="text-[9px] font-semibold uppercase tracking-wide" x-text="q.label"></span>
                                </div>
                                <p class="text-sm font-bold text-navy-900 mt-0.5 truncate" x-text="q.value"></p>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Two independent flex columns (not a flat CSS grid) — a flat grid
                     forces every row to match its tallest cell, which left a large
                     dead gap under the short Personal Information card once Account
                     & Onboarding grew taller. Independent columns size themselves. --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                <div class="flex flex-col gap-4">

                {{-- Personal info grid --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Personal Information</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="user" class="w-3 h-3"></i>Full Name</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.name || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="calendar-days" class="w-3 h-3"></i>Date of Birth</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.dob || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="cake" class="w-3 h-3"></i>Age</dt>
                            <dd class="text-sm font-semibold text-navy-900">
                                <span x-text="(selectedIndividual?.age || '—') + (selectedIndividual?.age ? ' years old' : '')"></span>
                            </dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="venus-and-mars" class="w-3 h-3"></i>Sex</dt>
                            <dd class="text-sm font-semibold text-navy-900">
                                <span x-text="selectedIndividual?.sex || '—'"></span>
                            </dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="heart-handshake" class="w-3 h-3"></i>Civil Status</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.civil_status || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="user" class="w-3 h-3"></i>Father's Full Name</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.father_name || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="user" class="w-3 h-3"></i>Mother's Maiden Name</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.mother_maiden_name || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="phone" class="w-3 h-3"></i>Contact Number</dt>
                            <dd class="text-sm font-semibold text-navy-900">
                                <a
                                    :href="'tel:' + selectedIndividual?.contact"
                                    class="text-brand-600 hover:underline"
                                    x-text="selectedIndividual?.contact || '—'"
                                ></a>
                            </dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="mail" class="w-3 h-3"></i>Email Address</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.email || selectedIndividual?.citizen_account?.email || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="briefcase" class="w-3 h-3"></i>Occupation</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.occupation || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="graduation-cap" class="w-3 h-3"></i>Highest Education</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.education || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="wallet" class="w-3 h-3"></i>Income Range</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.income_range || '—'"></dd>
                        </div>

                    </dl>
                </div>

                {{-- Address & Household info --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    {{-- Connection signifiers sit to the right of the section title —
                         a data-quality/record-completeness signal, not an implication
                         of anything suspicious. --}}
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Address & Household</h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                class="inline-flex items-center gap-1 text-[11px] font-medium"
                                :class="selectedIndividual?.household_id ? 'text-emerald-600' : 'text-amber-600'"
                                :title="selectedIndividual?.household_id ? 'Connected to Household' : 'Not Connected to Household'"
                            >
                                <i :data-lucide="selectedIndividual?.household_id ? 'circle-check' : 'circle-alert'" class="w-3.5 h-3.5"></i>
                                Household
                            </span>
                            <span
                                class="inline-flex items-center gap-1 text-[11px] font-medium"
                                :class="selectedIndividual?.family_id ? 'text-emerald-600' : 'text-amber-600'"
                                :title="selectedIndividual?.family_id ? 'Connected to Family' : 'Not Connected to Family'"
                            >
                                <i :data-lucide="selectedIndividual?.family_id ? 'circle-check' : 'circle-alert'" class="w-3.5 h-3.5"></i>
                                Family
                            </span>
                        </div>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="map-pin" class="w-3 h-3"></i>Barangay</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="'Brgy. ' + (selectedIndividual?.barangay || '—')"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="signpost" class="w-3 h-3"></i>Sitio / Purok</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.sitio || '—'"></dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="home" class="w-3 h-3"></i>Full Address</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.address || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="hash" class="w-3 h-3"></i>Household ID</dt>
                            <dd class="text-sm font-mono font-semibold text-navy-900" x-text="selectedIndividual?.household_id || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="hash" class="w-3 h-3"></i>Family ID</dt>
                            <dd class="text-sm font-mono font-semibold text-navy-900" x-text="selectedIndividual?.family_id || '—'"></dd>
                        </div>

                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-400 uppercase tracking-wide mb-0.5"><i data-lucide="users" class="w-3 h-3"></i>Relation in Household</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.relation || '—'"></dd>
                        </div>

                    </dl>

                    <p
                        class="mt-4 pt-4 border-t border-slate-100 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2"
                        x-show="!selectedIndividual?.household_id"
                    >
                        Critical profile gap: this resident is not currently linked to a household record.
                    </p>
                </div>

                {{-- Vulnerable Sector Tags --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-show="(selectedIndividual?.tags || []).length > 0">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Vulnerable Sector Classification</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="tag in (selectedIndividual?.tags || [])" :key="tag">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold"
                                :class="{
                                    'bg-amber-100 text-amber-800 ring-1 ring-amber-200': tag === 'senior' || tag === 'osca',
                                    'bg-blue-100 text-blue-800 ring-1 ring-blue-200': tag === 'pwd',
                                    'bg-green-100 text-green-800 ring-1 ring-green-200': tag === 'youth',
                                    'bg-violet-100 text-violet-800 ring-1 ring-violet-200': tag === 'child',
                                    'bg-orange-100 text-orange-800 ring-1 ring-orange-200': tag === 'osy',
                                    'bg-cyan-100 text-cyan-800 ring-1 ring-cyan-200': tag === '4ps',
                                    'bg-rose-100 text-rose-800 ring-1 ring-rose-200': tag === 'solo_parent',
                                    'bg-lime-100 text-lime-800 ring-1 ring-lime-200': tag === 'farmer',
                                    'bg-red-100 text-red-800 ring-1 ring-red-200': tag === 'high_risk',
                                }"
                            >
                                <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                                <span x-text="
                                    tag === 'solo_parent' ? 'Solo Parent' :
                                    tag === 'high_risk' ? 'High Risk' :
                                    tag === 'osy' ? 'Out-of-School Youth' :
                                    tag === '4ps' ? '4Ps Beneficiary' :
                                    tag === 'osca' ? 'OSCA' :
                                    tag === 'pwd' ? 'PWD' :
                                    tag.charAt(0).toUpperCase() + tag.slice(1)
                                "></span>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Submitted Onboarding Documents — moved here from the right
                     column so both columns carry a closer, more even amount of
                     content instead of everything piling up on the right. Real
                     document tiles sized like a verification-officer review
                     screen, not a cramped text badge. The first tile shows the
                     resident's actual scanned ID (ported from the Esperanza
                     Mobile app's own seeded demo assets) where one exists on
                     file; every other tile uses styled placeholder imagery. --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-show="selectedIndividual?.citizen_account?.id_uploaded || selectedIndividual?.citizen_account?.face_verified" x-cloak>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Submitted Onboarding Documents</h3>
                    <div class="flex flex-wrap items-start gap-4">
                        {{-- Valid ID tiles — a resident may have more than one ID on
                             file; loop when verified (post-approval), otherwise show a
                             single generic placeholder tile for the one step completed. --}}
                        <template x-if="(selectedIndividual?.citizen_account?.verified_ids || []).length" x-cloak>
                            <template x-for="(idDoc, idIdx) in selectedIndividual.citizen_account.verified_ids" :key="idDoc.type">
                                <div class="w-52 shrink-0">
                                    <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                                        <template x-if="idIdx === 0 && selectedIndividual?.citizen_account?.id_image_asset">
                                            <div class="relative">
                                                <img :src="selectedIndividual.citizen_account.id_image_asset" class="w-full h-24 object-cover" :alt="idDoc.type">
                                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 to-transparent px-2.5 pt-4 pb-1.5">
                                                    <p class="text-[10px] font-bold text-white truncate" x-text="idDoc.type"></p>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!(idIdx === 0 && selectedIndividual?.citizen_account?.id_image_asset)">
                                            <div class="bg-gradient-to-br from-navy-800 to-brand-900 px-3 py-2 text-white">
                                                <p class="text-[8px] uppercase tracking-widest text-white/50">Republic of the Philippines</p>
                                                <p class="text-[11px] font-bold truncate" x-text="idDoc.type"></p>
                                            </div>
                                        </template>
                                        <div class="p-3 flex items-center gap-2.5">
                                            <template x-if="!(idIdx === 0 && selectedIndividual?.citizen_account?.id_image_asset)">
                                                <div class="w-11 h-14 rounded bg-slate-100 flex items-center justify-center shrink-0">
                                                    <i data-lucide="user-round" class="w-5 h-5 text-slate-300"></i>
                                                </div>
                                            </template>
                                            <div class="min-w-0">
                                                <p class="text-[9px] text-slate-400 uppercase tracking-wide">ID Number</p>
                                                <p class="text-xs font-mono font-semibold text-navy-900" x-text="idDoc.masked"></p>
                                                <p class="text-[10px] text-slate-500 mt-1.5 truncate" x-text="'Issued by ' + issuingOfficeFor(idDoc.type)"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1.5 text-center">Valid ID — submitted during onboarding</p>
                                </div>
                            </template>
                        </template>
                        <div class="w-52 shrink-0" x-show="selectedIndividual?.citizen_account?.id_uploaded && !(selectedIndividual?.citizen_account?.verified_ids || []).length">
                            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                                {{-- Still shows the real scanned ID (ported from the Esperanza
                                     Mobile app) when one is on file, even though the account
                                     isn't Approved yet — submitting an ID and being verified
                                     are two different facts, same as mobile's own Digital ID
                                     screen ("Submitted ID Document" vs the verified card). --}}
                                <template x-if="selectedIndividual?.citizen_account?.id_image_asset">
                                    <div class="relative">
                                        <img :src="selectedIndividual.citizen_account.id_image_asset" class="w-full h-24 object-cover" alt="Submitted ID">
                                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 to-transparent px-2.5 pt-4 pb-1.5">
                                            <p class="text-[10px] font-bold text-white truncate">Valid Government ID</p>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!selectedIndividual?.citizen_account?.id_image_asset">
                                    <div class="bg-gradient-to-br from-navy-800 to-brand-900 px-3 py-2 text-white">
                                        <p class="text-[8px] uppercase tracking-widest text-white/50">Republic of the Philippines</p>
                                        <p class="text-[11px] font-bold truncate">Valid Government ID</p>
                                    </div>
                                </template>
                                <div class="p-3 flex items-center gap-2.5">
                                    <template x-if="!selectedIndividual?.citizen_account?.id_image_asset">
                                        <div class="w-11 h-14 rounded bg-slate-100 flex items-center justify-center shrink-0">
                                            <i data-lucide="user-round" class="w-5 h-5 text-slate-300"></i>
                                        </div>
                                    </template>
                                    <div class="min-w-0">
                                        <p class="text-[9px] text-slate-400 uppercase tracking-wide">ID Number</p>
                                        <p class="text-xs font-mono font-semibold text-navy-900">•••• •••• ••••</p>
                                        <p class="text-[10px] text-slate-500 mt-1.5 truncate" x-text="selectedIndividual?.name"></p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1.5 text-center">Valid ID — submitted during onboarding</p>
                        </div>

                        {{-- Selfie / liveness capture tile --}}
                        <div class="w-32 shrink-0" x-show="selectedIndividual?.citizen_account?.face_verified">
                            <div class="relative rounded-xl overflow-hidden border border-slate-200 shadow-sm aspect-[3/4] bg-gradient-to-br from-slate-600 to-slate-900 flex items-center justify-center">
                                <i data-lucide="user-round" class="w-11 h-11 text-white/50"></i>
                                <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-bold bg-black/40 text-white">LIVE</span>
                                <span
                                    class="absolute bottom-1.5 left-1.5 right-1.5 flex items-center justify-center px-1.5 py-1 rounded text-[9px] font-bold"
                                    :class="selectedIndividual?.citizen_account?.identity_check?.face_match === 'Match' ? 'bg-emerald-500 text-white' : selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch' ? 'bg-rose-500 text-white' : 'bg-amber-400 text-white'"
                                    x-text="selectedIndividual?.citizen_account?.identity_check?.face_match || 'Not Reviewed'"
                                ></span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1.5 text-center">Selfie — onboarding scan</p>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity — notification-style feed of this
                     resident's own Dokyu/Tulong submissions, e.g. "Cristy
                     submitted Barangay Clearance" — pulled straight from
                     document_requests/assistance_requests already on her
                     record (see recentActivity() in constituents.blade.php),
                     so it's always here the moment this tab opens. --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-show="recentActivity(selectedIndividual).length">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i data-lucide="bell" class="w-4 h-4 text-brand-400"></i>
                        Recent Activity
                    </h3>
                    <div class="divide-y divide-slate-50">
                        <template x-for="a in recentActivity(selectedIndividual)" :key="a.ref">
                            <div class="flex items-start gap-3 py-2.5 first:pt-0 last:pb-0">
                                <span
                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                    :class="a.portal === 'Tulong' ? 'bg-purple-50 text-purple-500' : 'bg-brand-50 text-brand-500'"
                                ><i :data-lucide="a.icon" class="w-3.5 h-3.5"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-navy-900">
                                        <span class="font-semibold" x-text="selectedIndividual?.name?.split(' ')[0]"></span>
                                        <span class="text-slate-500"> submitted </span>
                                        <span class="font-semibold" x-text="a.type"></span>
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        <span class="font-mono" x-text="a.ref"></span>&nbsp;&middot;&nbsp;<span x-text="a.submitted"></span>
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0"
                                    :class="{
                                        'bg-green-100 text-green-700': ['Released','Completed','Approved','Mark to Release'].includes(a.status),
                                        'bg-amber-100 text-amber-700': ['Processing','Under Review','Assigned'].includes(a.status),
                                        'bg-blue-100 text-blue-700': ['Submitted','Under Verification'].includes(a.status),
                                        'bg-red-100 text-red-700': ['Rejected','Cancelled'].includes(a.status),
                                    }"
                                    x-text="a.status"
                                ></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Emergency Contact --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="circle-alert" class="w-4 h-4 text-rose-400"></i>
                        Emergency Contact
                    </h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Name</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.emergency?.name || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Contact</dt>
                            <dd class="text-sm font-semibold text-navy-900">
                                <a :href="'tel:' + selectedIndividual?.emergency?.contact"
                                   class="text-brand-600 hover:underline"
                                   x-text="selectedIndividual?.emergency?.contact || '—'"></a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Relationship</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.emergency?.relation || '—'"></dd>
                        </div>
                    </dl>
                </div>

                </div>
                <div class="flex flex-col gap-4">

                {{-- Right column — Account & Onboarding (Esperanza Citizen Portal
                     signup) broken into its own standalone cards (instead of one
                     tall merged card) so this column's rhythm matches the left
                     column's card-per-section pattern rather than towering over
                     it as one continuous block. --}}

                {{-- Identity anomaly notice — neutral language, no automatic
                     accusation. Surfaces a review recommendation only; the
                     admin decides what happens next. Already self-styled with
                     its own colored border, so it stands alone with no extra
                     white-card wrapper. --}}
                <template x-for="flag in (selectedIndividual?.identity_flags || [])" :key="flag.type">
                        <div
                            class="rounded-xl border p-4"
                            :class="flag.resolution === 'denied' ? 'bg-rose-50 border-rose-200' : flag.resolution === 'confirmed_owner' ? 'bg-slate-50 border-slate-200' : 'bg-amber-50 border-amber-200'"
                        >
                            <div class="flex items-start gap-2.5">
                                <i data-lucide="shield-alert" class="w-4.5 h-4.5 shrink-0 mt-0.5" :class="flag.resolution === 'denied' ? 'text-rose-600' : flag.resolution === 'confirmed_owner' ? 'text-slate-400' : 'text-amber-600'"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold" :class="flag.resolution === 'denied' ? 'text-rose-800' : flag.resolution === 'confirmed_owner' ? 'text-slate-600' : 'text-amber-800'" x-text="flag.type + ' — ' + flag.severity"></p>
                                    <p class="text-xs mt-1" :class="flag.resolution === 'denied' ? 'text-rose-700' : flag.resolution === 'confirmed_owner' ? 'text-slate-500' : 'text-amber-700'" x-text="flag.summary"></p>
                                    <p class="text-xs font-medium mt-2" :class="flag.resolution === 'denied' ? 'text-rose-800' : flag.resolution === 'confirmed_owner' ? 'text-slate-500' : 'text-amber-800'" x-text="flag.recommendation"></p>
                                    <p class="text-[10px] mt-1.5" :class="flag.resolution === 'denied' ? 'text-rose-600/70' : flag.resolution === 'confirmed_owner' ? 'text-slate-400' : 'text-amber-600/70'" x-text="'Detected ' + flag.detected"></p>

                                    {{-- Resolution flow — only meaningful for a duplicate-identity
                                         signal, and only once a human has actually reached the
                                         resident. Two outcomes only; nothing here is automatic. --}}
                                    <template x-if="flag.type === 'Duplicate Identity Signal' && !flag.resolution && can('const_citizen_verification','verify')">
                                        <div class="mt-3.5 pt-3.5 border-t border-amber-200">
                                            <label class="block text-[11px] font-semibold text-amber-700 uppercase tracking-wide mb-1.5">
                                                Resolve This Flag <span class="normal-case font-normal text-amber-600/70">— after contacting the resident</span>
                                            </label>
                                            <textarea
                                                x-model="duplicateResolutionRemarks" rows="3" placeholder="Notes from resident contact — how they were reached, what they confirmed…"
                                                class="w-full rounded-xl border border-amber-200 bg-white py-2.5 px-3.5 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"
                                            ></textarea>
                                            <div class="mt-2.5 flex flex-wrap items-center gap-2.5">
                                                <x-ui.button size="sm" icon="user-check" x-bind:disabled="!duplicateResolutionRemarks.trim()" @click="resolveDuplicateOwner(selectedIndividual, flag)">Resident Confirms: Account Is Theirs</x-ui.button>
                                                <x-ui.button size="sm" variant="danger" icon="user-x" x-bind:disabled="!duplicateResolutionRemarks.trim()" @click="resolveDuplicateDenied(selectedIndividual, flag)">Resident Denies: Not Their Account</x-ui.button>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="flag.type === 'Duplicate Identity Signal' && flag.resolution === 'confirmed_owner'">
                                        <div class="mt-3.5 pt-3.5 border-t border-slate-200">
                                            <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600"><i data-lucide="user-check" class="w-3.5 h-3.5"></i>Resolved — Confirmed Duplicate, Blocked from Verification</p>
                                            <p class="text-xs text-slate-500 mt-1.5" x-text="flag.resolution_remarks"></p>
                                            <p class="text-[10px] text-slate-400 mt-1.5" x-text="'Resolved by ' + flag.resolved_by + ' · ' + flag.resolved_at"></p>
                                        </div>
                                    </template>

                                    <template x-if="flag.type === 'Duplicate Identity Signal' && flag.resolution === 'denied'">
                                        <div class="mt-3.5 pt-3.5 border-t border-rose-200">
                                            <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-700"><i data-lucide="user-x" class="w-3.5 h-3.5"></i>Resolved — Account Cancelled</p>
                                            <p class="text-xs text-rose-600 mt-1.5" x-text="flag.resolution_remarks"></p>
                                            <p class="text-[10px] text-rose-500/70 mt-1.5" x-text="'Resolved by ' + flag.resolved_by + ' · ' + flag.resolved_at"></p>

                                            <template x-if="flag.flagged_as_impostor === null && can('const_citizen_verification','verify')">
                                                <div class="mt-3 pt-3 border-t border-rose-200">
                                                    <p class="text-[11px] font-semibold text-rose-700 uppercase tracking-wide mb-1.5">Flag as a Suspected Impostor Account?</p>
                                                    <p class="text-[11px] text-rose-500 mb-2">A separate decision from cancelling the account — only for cases warranting escalation.</p>
                                                    <div class="flex flex-wrap items-center gap-2.5">
                                                        <x-ui.button size="sm" variant="danger" icon="flag" @click="setImpostorFlag(selectedIndividual, flag, true)">Yes, Flag as Impostor</x-ui.button>
                                                        <x-ui.button size="sm" variant="secondary" icon="x" @click="setImpostorFlag(selectedIndividual, flag, false)">No, Close Without Flag</x-ui.button>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="flag.flagged_as_impostor === true">
                                                <div class="mt-3 pt-3 border-t border-rose-200">
                                                    <p class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-700"><i data-lucide="flag" class="w-3.5 h-3.5"></i>Flagged — Suspected Impostor Account</p>
                                                    <p class="text-[11px] text-rose-500 mt-1">Escalated per standard duplicate-identity procedure — coordinate with the Municipal Civil Registrar and, if warranted, the Municipal Police Station (PNP Esperanza).</p>
                                                </div>
                                            </template>
                                            <template x-if="flag.flagged_as_impostor === false">
                                                <p class="mt-3 pt-3 border-t border-rose-200 text-[11px] text-rose-500">Closed without an impostor flag — cancelled registration only.</p>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                {{-- Verification Attempt History — only rendered when there's more than one attempt on file --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-show="(selectedIndividual?.verification_attempts || []).length > 1">
                    <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-2.5 flex items-center gap-1.5">
                        <i data-lucide="history" class="w-3.5 h-3.5 text-slate-400"></i>
                        Verification Attempt History
                    </h4>
                    <div class="space-y-1.5">
                        <template x-for="(att, ai) in (selectedIndividual?.verification_attempts || [])" :key="ai">
                            <div class="flex items-center justify-between gap-3 text-xs bg-slate-50 rounded-lg px-3 py-2">
                                <span class="text-slate-500" x-text="att.date"></span>
                                <span class="flex-1 text-slate-600 truncate" x-text="att.reason"></span>
                                <span
                                    class="font-semibold shrink-0"
                                    :class="att.result === 'Rejected' ? 'text-rose-600' : 'text-amber-600'"
                                    x-text="att.result"
                                ></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- `contents` so this participates in the column's own flex gap
                     instead of adding another nesting/spacing layer — x-show
                     (not x-if) since a template x-if only ever allows one root
                     child, and this wraps several independent sibling cards. --}}
                <div x-show="selectedIndividual?.citizen_account" x-cloak class="contents">
                            {{-- Verification Decision — placed first, immediately under any
                                 identity-flag warnings, so approving/rejecting never requires
                                 scrolling past documents/signals/timeline to find the buttons.
                                 Remarks come before the buttons they gate, in natural fill order. --}}
                            <div
                                id="verification-decision-card"
                                x-show="['Pending Review','Under Verification','Rejected','Draft','Cancelled'].includes(selectedIndividual?.citizen_account?.status)"
                                class="rounded-2xl border p-4 transition-shadow duration-300"
                                :class="['Rejected','Cancelled'].includes(selectedIndividual?.citizen_account?.status) ? 'bg-rose-50/50 border-rose-200' : 'bg-amber-50/40 border-amber-200'"
                            >
                                <div class="flex items-start gap-2.5 text-xs" :class="['Rejected','Cancelled'].includes(selectedIndividual?.citizen_account?.status) ? 'text-rose-700' : 'text-amber-700'">
                                    <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                                    <span x-text="
                                        selectedIndividual?.citizen_account?.status === 'Cancelled'
                                            ? 'This account was cancelled — confirmed not to belong to this resident during duplicate-identity resolution.'
                                            : selectedIndividual?.citizen_account?.verification_blocked
                                            ? 'This account is permanently blocked from verification — see the duplicate-identity resolution below.'
                                            : selectedIndividual?.citizen_account?.status === 'Rejected'
                                            ? 'Registration was rejected — resident needs to resubmit valid ID / details before Dokyu and Tulong access can be granted.'
                                            : selectedIndividual?.citizen_account?.status === 'Under Verification'
                                            ? 'LGU is actively reviewing this registration. Dokyu and Tulong remain locked until verified.'
                                            : 'Registration submitted and awaiting LGU review. Dokyu and Tulong remain locked until verified.'
                                    "></span>
                                </div>

                                <div
                                    class="mt-3 rounded-lg bg-white/70 border border-rose-100 px-3 py-2.5"
                                    x-show="selectedIndividual?.citizen_account?.verification_blocked"
                                    x-cloak
                                >
                                    <p class="text-[10px] font-semibold text-rose-500 uppercase tracking-wide">Permanently Blocked — Confirmed Duplicate</p>
                                    <p class="mt-1 text-xs text-rose-700 font-medium" x-text="selectedIndividual?.citizen_account?.verification_blocked_reason"></p>
                                </div>

                                <div
                                    class="mt-3 rounded-lg bg-white/70 border border-rose-100 px-3 py-2.5"
                                    x-show="selectedIndividual?.citizen_account?.status === 'Rejected' && !selectedIndividual?.citizen_account?.verification_blocked && selectedIndividual?.citizen_account?.reject_remarks"
                                >
                                    <p class="text-[10px] font-semibold text-rose-500 uppercase tracking-wide">Correction Required</p>
                                    <p class="mt-1 text-xs text-rose-700 font-medium" x-text="selectedIndividual?.citizen_account?.reject_remarks"></p>
                                    <p class="mt-1.5 text-[11px] text-rose-500">Resident must correct and resubmit through the Citizen Portal — the corrected submission will return here as a new Pending Review.</p>
                                </div>

                                {{-- What the citizen would see if they tried to access Dokyu/Tulong
                                     from this specific blocked or cancelled account. --}}
                                <div
                                    class="mt-3 rounded-lg bg-slate-50 border border-slate-200 px-3.5 py-2.5"
                                    x-show="selectedIndividual?.citizen_account?.verification_blocked || selectedIndividual?.citizen_account?.status === 'Cancelled'"
                                    x-cloak
                                >
                                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1 flex items-center gap-1.5">
                                        <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                                        What the citizen sees on Mobile
                                    </p>
                                    <p class="text-xs text-slate-600 italic" x-text="'“' + citizenNotificationMessage(selectedIndividual?.citizen_account) + '”'"></p>
                                </div>

                                <div
                                    class="mt-4 pt-4 border-t"
                                    :class="selectedIndividual?.citizen_account?.status === 'Rejected' ? 'border-rose-200' : 'border-amber-200'"
                                    x-show="['Pending Review','Under Verification'].includes(selectedIndividual?.citizen_account?.status) && (can('const_citizen_verification','verify') || can('const_citizen_verification','reject'))"
                                >
                                    {{-- Final-step guidance — this is the account-level decision,
                                         distinct from (and downstream of) the 5 per-signal reviews
                                         above; tells the reviewer what "done" looks like before
                                         they commit to Verify or Reject. --}}
                                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-2.5 mb-3 flex items-start gap-2">
                                        <i data-lucide="clipboard-check" class="w-3.5 h-3.5 text-indigo-500 shrink-0 mt-0.5"></i>
                                        <p class="text-[11px] text-indigo-800 leading-relaxed">Before deciding, confirm every signal above — ID Document, Face Scan, Mobile Number, Terms, and Face Match — is marked Complete and reviewed. Verify once all signals check out; Reject only for a concrete, specific reason (unreadable ID, mismatched name, face mismatch) so the resident knows exactly what to correct before resubmitting.</p>
                                    </div>
                                    <div x-show="can('const_citizen_verification','reject')">
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                            Rejection Remarks <span class="normal-case font-normal text-slate-400">— required only if rejecting</span>
                                        </label>
                                        <textarea aria-label="Rejection Remarks — required only if rejecting"
                                            x-model="rejectAccountRemarks" rows="4" placeholder="e.g. ID photo unreadable, name doesn't match uploaded ID, selfie doesn't clearly show the face..."
                                            class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3.5 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"
                                        ></textarea>
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-2.5">
                                        <x-ui.button x-show="can('const_citizen_verification','verify')" icon="check" @click="verifyCitizenAccount(selectedIndividual)">Verify Registration</x-ui.button>
                                        <x-ui.button x-show="can('const_citizen_verification','reject')" variant="danger" icon="x" x-bind:disabled="!rejectAccountRemarks.trim()" @click="rejectCitizenAccount(selectedIndividual)">Reject Registration</x-ui.button>
                                    </div>
                                </div>
                            </div>

                            {{-- Verified — the identity area now foregrounds the actual verified ID
                                 in place of a generic onboarding label, plus a citizen-facing
                                 congratulations moment mirroring what the resident sees on Mobile. --}}
                            <div
                                class="rounded-xl bg-gradient-to-br from-emerald-50 to-white border border-emerald-200 p-4"
                                x-show="selectedIndividual?.citizen_account?.status === 'Approved'"
                                x-cloak
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <i data-lucide="party-popper" class="w-4 h-4 text-emerald-600"></i>
                                    <p class="text-sm font-semibold text-emerald-800">Verified Citizen of Esperanza</p>
                                </div>
                                <p class="text-xs text-emerald-700">
                                    <span x-text="selectedIndividual?.name"></span> passed identity verification and is now a full, verified citizen with unlocked Dokyu and Tulong access.
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-1.5" x-show="(selectedIndividual?.citizen_account?.verified_ids || []).length" x-cloak>
                                    <template x-for="idDoc in (selectedIndividual?.citizen_account?.verified_ids || [])" :key="idDoc.type">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-navy-900 text-white"
                                            :title="'Verified via ' + idDoc.type"
                                        >
                                            <i data-lucide="badge-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                                            <span x-text="idDoc.type"></span>
                                            <span class="font-mono text-white/60" x-text="idDoc.masked"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>

                            {{-- Status Summary — who / when / status at a glance, plus what
                                 the citizen currently sees on Mobile for this account. --}}
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                                <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                                    <i :data-lucide="selectedIndividual?.citizen_account?.status === 'Approved' ? 'badge-check' : 'smartphone'" class="w-3.5 h-3.5" :class="selectedIndividual?.citizen_account?.status === 'Approved' ? 'text-emerald-500' : 'text-brand-400'"></i>
                                    <span x-text="selectedIndividual?.citizen_account?.status === 'Approved' ? 'Verified Identity' : 'Account & Onboarding'"></span>
                                </h4>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': selectedIndividual?.citizen_account?.status === 'Approved',
                                            'bg-amber-100 text-amber-700': selectedIndividual?.citizen_account?.status === 'Pending Review',
                                            'bg-indigo-100 text-indigo-700': selectedIndividual?.citizen_account?.status === 'Under Verification',
                                            'bg-rose-100 text-rose-700': selectedIndividual?.citizen_account?.status === 'Rejected',
                                            'bg-slate-100 text-slate-600': !['Approved','Pending Review','Under Verification','Rejected'].includes(selectedIndividual?.citizen_account?.status),
                                        }"
                                    >
                                        <i data-lucide="shield-check" class="w-3.5 h-3.5" x-show="selectedIndividual?.citizen_account?.status === 'Approved'"></i>
                                        <span x-text="selectedIndividual?.citizen_account?.status || 'Draft'"></span>
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                        :class="selectedIndividual?.citizen_account?.access_level === 'Verified' ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'"
                                    >
                                        <i data-lucide="key-round" class="w-3.5 h-3.5"></i>
                                        <span x-text="(selectedIndividual?.citizen_account?.access_level || 'Unverified') + ' Access'"></span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-50 text-slate-500">
                                        <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>
                                        <span x-text="'Registered ' + (selectedIndividual?.citizen_account?.registered_at || '—')"></span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 ring-1 ring-purple-100">
                                        <i data-lucide="route" class="w-3.5 h-3.5"></i>
                                        <span x-text="'Onboarding: ' + onboardingStatus(selectedIndividual?.citizen_account)"></span>
                                    </span>
                                </div>

                                {{-- What the citizen sees on Mobile right now — makes the
                                     Pending/Under Verification/Rejected flow tangible instead of
                                     an abstract admin-only status label. --}}
                                <div class="mt-3.5 pt-3.5 border-t border-slate-100 rounded-lg" x-show="selectedIndividual?.citizen_account?.status !== 'Approved'">
                                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                        <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                                        What the citizen sees on Mobile
                                    </p>
                                    <p class="text-xs text-slate-600 italic" x-text="'“' + citizenNotificationMessage(selectedIndividual?.citizen_account) + '”'"></p>
                                </div>
                            </div>

                            {{-- Account Information — Citizen/Residency Information sub-blocks
                                 removed here since Full Name and Barangay are already shown
                                 in the Personal Information / Address & Household cards on the
                                 left; only what's unique to the Citizen Portal account stays. --}}
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                                <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-2.5">Account Information</h4>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2.5">
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-xs text-slate-400">Citizen Portal ID</dt>
                                        <dd class="text-xs font-mono font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.id || '—'"></dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-xs text-slate-400">Email</dt>
                                        <dd class="text-xs font-semibold text-navy-900 truncate" x-text="selectedIndividual?.citizen_account?.email || '—'"></dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-xs text-slate-400">Mobile</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.mobile || '—'"></dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-xs text-slate-400">Resident Since</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.resident_since || '—'"></dd>
                                    </div>
                                </dl>

                                {{-- Mobile App Profile Completeness — how far signup/onboarding
                                     itself progressed (separate from LGU record-keeping). --}}
                                <div class="mt-4 pt-4 border-t border-slate-100">
                                    <dt class="text-xs text-slate-400 mb-1">Mobile App Profile Completeness</dt>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all duration-[600ms] ease-standard"
                                                :class="completionColor(selectedIndividual?.citizen_account?.profile_completeness)"
                                                :style="'width:' + (selectedIndividual?.citizen_account?.profile_completeness || 0) + '%'"
                                            ></div>
                                        </div>
                                        <span
                                            class="text-xs font-bold shrink-0"
                                            :class="completionTextColor(selectedIndividual?.citizen_account?.profile_completeness)"
                                            x-text="(selectedIndividual?.citizen_account?.profile_completeness || 0) + '%'"
                                        ></span>
                                    </div>
                                </div>
                            </div>

                            {{-- System Verification Signals — read-only status per row,
                                 but the row itself is clickable (where it maps to a real
                                 signal) and opens that signal's own independent review
                                 popup, same as clicking its header chip. --}}
                            <div id="verification-signals-card" class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 transition-shadow duration-300">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">System Verification Signals</h4>
                                    <span class="text-[11px] font-medium text-slate-400">Click a signal to review</span>
                                </div>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-0.5">
                                    {{-- Each row's own wording distinguishes "the step was
                                         completed" (submission-type signals) from "it was
                                         confirmed correct" (match-type signals) — using the
                                         same word for both is what let a Rejected/Mismatch
                                         account still show "Face Verified: Confirmed". Six
                                         items (not seven) so the 2-column grid fills evenly
                                         with no odd trailing row, and every label is short
                                         enough to stay on one line — a wrapped label next to
                                         a single-line one was what threw the two columns out
                                         of alignment with each other. --}}
                                    <template x-for="sig in [
                                        { label: 'Terms & Conditions', key: 'terms', ok: selectedIndividual?.citizen_account?.terms_accepted, pending: false, okText: 'Accepted', notOkText: 'Not Accepted' },
                                        { label: 'ID Document', key: 'id_document', ok: selectedIndividual?.citizen_account?.id_uploaded, pending: false, okText: 'Submitted', notOkText: 'Not Submitted' },
                                        { label: 'Face / Liveness Scan', key: 'face_scan', ok: selectedIndividual?.citizen_account?.face_verified, pending: false, okText: 'Completed', notOkText: 'Not Completed' },
                                        { label: 'Mobile Number', key: 'mobile', ok: selectedIndividual?.citizen_account?.mobile_verified, pending: false, okText: 'Verified', notOkText: 'Not Verified' },
                                        { label: 'Face Matches ID', key: 'face_match', ok: selectedIndividual?.citizen_account?.identity_check?.face_match === 'Match', pending: selectedIndividual?.citizen_account?.identity_check?.face_match === 'Not Reviewed', okText: 'Confirmed Match', notOkText: 'Mismatch Found' },
                                        { label: 'Identity Information', key: null, ok: !(selectedIndividual?.identity_flags || []).length, pending: false, okText: 'Consistent', notOkText: 'Flagged for Review' },
                                    ]" :key="sig.label">
                                        <button
                                            type="button"
                                            @click="if (sig.key) { reviewingSignal = sig.key; $nextTick(() => window.renderIcons?.()); }"
                                            class="flex items-center justify-between gap-3 py-1.5 -mx-1.5 px-1.5 rounded-lg transition-colors text-left w-full"
                                            :class="sig.key ? 'hover:bg-slate-50 cursor-pointer' : 'cursor-default'"
                                        >
                                            <dt class="text-xs text-slate-400 truncate" x-text="sig.label"></dt>
                                            <dd
                                                class="inline-flex items-center gap-1 text-xs font-semibold shrink-0"
                                                :class="sig.ok ? 'text-emerald-600' : sig.pending ? 'text-amber-600' : 'text-rose-600'"
                                            >
                                                <i :data-lucide="sig.ok ? 'circle-check' : sig.pending ? 'clock' : 'circle-x'" class="w-3.5 h-3.5"></i>
                                                <span x-text="sig.ok ? sig.okText : sig.pending ? 'Not Reviewed' : sig.notOkText"></span>
                                            </dd>
                                        </button>
                                    </template>
                                </dl>
                                <p
                                    class="mt-3 text-[11px] text-rose-600 bg-rose-50 rounded-lg px-2.5 py-2 leading-snug"
                                    x-show="selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch' && selectedIndividual?.citizen_account?.identity_check?.remarks"
                                    x-text="selectedIndividual?.citizen_account?.identity_check?.remarks"
                                ></p>
                                <p
                                    class="mt-1.5 text-[10px] text-slate-400"
                                    x-show="selectedIndividual?.citizen_account?.identity_check?.reviewed_by"
                                    x-text="'Reviewed by ' + selectedIndividual?.citizen_account?.identity_check?.reviewed_by + ' · ' + selectedIndividual?.citizen_account?.identity_check?.reviewed_at"
                                ></p>
                            </div>

                            {{-- Onboarding Timeline — mirrors the Esperanza Mobile 6-step registration wizard --}}
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-data="{ onboardingExpanded: false }">
                                <button
                                    type="button"
                                    @click="onboardingExpanded = !onboardingExpanded"
                                    class="flex items-center justify-between w-full text-left group/timeline"
                                >
                                    <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                        <i data-lucide="route" class="w-3.5 h-3.5 text-purple-400"></i>
                                        Onboarding Timeline
                                    </h4>
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-[160ms] ease-standard group-hover/timeline:text-slate-600" :class="onboardingExpanded && 'rotate-180'"></i>
                                </button>

                                <div
                                    x-show="onboardingExpanded" x-cloak
                                    x-transition:enter="transition duration-[200ms] ease-emphasized"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="mt-3.5 space-y-0"
                                >
                                    <template x-for="(step, si) in onboardingSteps(selectedIndividual?.citizen_account)" :key="si">
                                        <div class="flex items-start gap-3">
                                            <div class="flex flex-col items-center shrink-0">
                                                <span
                                                    class="w-5 h-5 rounded-full flex items-center justify-center shrink-0"
                                                    :class="step.rejected ? 'bg-rose-100 text-rose-600' : step.done ? 'bg-emerald-500 text-white' : step.current ? 'bg-amber-100 text-amber-600 animate-pulse' : 'bg-slate-100 text-slate-300'"
                                                >
                                                    <span x-show="step.rejected"><i data-lucide="x" class="w-3 h-3"></i></span>
                                                    <span x-show="step.done && !step.rejected"><i data-lucide="check" class="w-3 h-3"></i></span>
                                                    <span class="w-1.5 h-1.5 rounded-full bg-current" x-show="!step.done && !step.rejected"></span>
                                                </span>
                                                <div class="w-px flex-1 my-0.5" :class="step.done && !step.rejected ? 'bg-emerald-200' : 'bg-slate-100'" x-show="si < onboardingSteps(selectedIndividual?.citizen_account).length - 1" style="min-height: 1.25rem"></div>
                                            </div>
                                            <p
                                                class="text-xs pb-3.5 -mt-0.5"
                                                :class="step.rejected ? 'text-rose-600 font-semibold' : step.done ? 'text-slate-700 font-medium' : step.current ? 'text-amber-600 font-semibold' : 'text-slate-400'"
                                                x-text="step.label"
                                            ></p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Digital ID — same flippable card the citizen sees on
                                 their own Profile page (components/citizen/digital-
                                 id-card.blade.php), surfaced here too so an admin
                                 reviewing this resident's onboarding can see the
                                 credential it results in. Same Cristy-only gate as
                                 the citizen-facing card — see that component's own
                                 header comment for why. --}}
                            <div x-show="selectedIndividual?.id === 'RES-2026-104'" x-cloak class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                                <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide flex items-center gap-1.5 mb-3.5">
                                    <i data-lucide="badge-check" class="w-3.5 h-3.5 text-gold-500"></i>
                                    Digital ID
                                </h4>
                                <x-citizen.digital-id-card />
                            </div>

                </div>{{-- /citizen_account contents wrapper --}}

                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-show="!selectedIndividual?.citizen_account">
                    <div class="py-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2">
                            <i data-lucide="smartphone" class="w-5 h-5 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-600">No linked Citizen Portal account</p>
                        <p class="text-xs text-slate-400 mt-1">This resident has not registered (or is not yet linked to) an Esperanza Citizen Portal account.</p>
                    </div>
                </div>

                </div>
                </div>
            </div>{{-- /Overview --}}

            {{-- ── TAB 2: Programs & Benefits ── --}}
            <div x-show="individualTab === 'Programs & Benefits'" class="space-y-3" x-data="{ expandedProgram: null }">

                <p class="text-xs text-slate-500 bg-blue-50 border border-blue-100 rounded-lg px-4 py-2.5 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-blue-400 flex-shrink-0"></i>
                    Programs shown are based on vulnerable sector tags encoded on this resident's profile. Click a program for full beneficiary detail.
                </p>

                {{-- Program list derived from tags --}}
                <template x-if="(selectedIndividual?.tags || []).length === 0">
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="package-open" class="w-6 h-6 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-600">No program enrollments found</p>
                        <p class="text-xs text-slate-400 mt-1">Add sector tags on the resident's profile to reflect program eligibility.</p>
                    </div>
                </template>

                <template x-if="(selectedIndividual?.tags || []).length > 0">
                    <div class="space-y-3">
                        <template x-for="tag in (selectedIndividual?.tags || [])" :key="'prog-'+tag">
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
                            <div
                                class="p-4 flex items-center justify-between gap-4 cursor-pointer hover:bg-slate-50/60 transition-colors"
                                @click="expandedProgram = expandedProgram === tag ? null : tag"
                            
        role="button"
        tabindex="0"
        @keydown.enter.prevent="expandedProgram = expandedProgram === tag ? null : tag"
        @keydown.space.prevent="expandedProgram = expandedProgram === tag ? null : tag">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                        :class="{
                                            'bg-amber-100': tag === 'senior' || tag === 'osca',
                                            'bg-blue-100': tag === 'pwd',
                                            'bg-green-100': tag === 'youth',
                                            'bg-violet-100': tag === 'child',
                                            'bg-orange-100': tag === 'osy',
                                            'bg-cyan-100': tag === '4ps',
                                            'bg-rose-100': tag === 'solo_parent',
                                            'bg-lime-100': tag === 'farmer',
                                            'bg-red-100': tag === 'high_risk',
                                        }"
                                    >
                                        <i data-lucide="landmark" class="w-5 h-5"
                                            :class="{
                                                'text-amber-600': tag === 'senior' || tag === 'osca',
                                                'text-blue-600': tag === 'pwd',
                                                'text-green-600': tag === 'youth',
                                                'text-violet-600': tag === 'child',
                                                'text-orange-600': tag === 'osy',
                                                'text-cyan-600': tag === '4ps',
                                                'text-rose-600': tag === 'solo_parent',
                                                'text-lime-700': tag === 'farmer',
                                                'text-red-600': tag === 'high_risk',
                                            }"
                                        ></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-navy-900" x-text="
                                            tag === '4ps' ? 'Pantawid Pamilyang Pilipino Program (4Ps)' :
                                            tag === 'osca' || tag === 'senior' ? 'OSCA / Senior Citizen Assistance' :
                                            tag === 'pwd' ? 'PWD Assistance & Benefits Program' :
                                            tag === 'solo_parent' ? 'Solo Parent Cash Assistance Program' :
                                            tag === 'youth' ? 'Youth Development Program' :
                                            tag === 'child' ? 'Child Protection & Early Childhood Program' :
                                            tag === 'osy' ? 'Alternative Learning System (ALS / OSY)' :
                                            tag === 'farmer' ? 'Farmers\' Assistance & Livelihood Program' :
                                            tag === 'high_risk' ? 'High-Risk Family Monitoring Program' :
                                            tag.toUpperCase() + ' Program'
                                        "></p>
                                        <p class="text-xs text-slate-500 mt-0.5">Barangay-administered &bull; MSWDO linked</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                                        Active
                                    </span>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-[160ms] ease-standard" :class="expandedProgram === tag && 'rotate-180'"></i>
                                </div>
                            </div>

                            {{-- Beneficiary detail — expands in place so the profile
                                 context is never lost. --}}
                            <div
                                x-show="expandedProgram === tag" x-cloak
                                x-transition:enter="transition duration-[200ms] ease-emphasized"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="border-t border-slate-100 bg-slate-50/60 p-4 space-y-4"
                            >
                                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <div>
                                        <dt class="text-[10px] text-slate-400 uppercase tracking-wide mb-0.5">Beneficiary</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedIndividual?.name"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] text-slate-400 uppercase tracking-wide mb-0.5">Resident ID</dt>
                                        <dd class="text-xs font-mono font-semibold text-navy-900" x-text="selectedIndividual?.id"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] text-slate-400 uppercase tracking-wide mb-0.5">Enrolled Since</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedIndividual?.created || '—'"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] text-slate-400 uppercase tracking-wide mb-0.5">Administering Office</dt>
                                        <dd class="text-xs font-semibold text-navy-900">MSWDO</dd>
                                    </div>
                                </dl>

                                <div>
                                    <dt class="text-[10px] text-slate-400 uppercase tracking-wide mb-1">Eligibility Basis</dt>
                                    <dd class="text-xs text-slate-600" x-text="
                                        tag === '4ps' ? 'Household meets the income-based eligibility classification for Pantawid Pamilyang Pilipino Program enrollment.' :
                                        tag === 'osca' || tag === 'senior' ? 'Resident is 60 years old or above, qualifying for OSCA benefits under RA 9994.' :
                                        tag === 'pwd' ? 'Resident holds PWD classification per barangay profiling records.' :
                                        tag === 'solo_parent' ? 'Head of household qualifies as a solo parent under RA 8972 (Solo Parents\' Welfare Act).' :
                                        tag === 'youth' ? 'Resident falls within the youth age bracket (15–30) per barangay profiling.' :
                                        tag === 'child' ? 'Resident is a minor under LGU child-welfare monitoring.' :
                                        tag === 'osy' ? 'Resident is classified Out-of-School Youth per barangay profiling.' :
                                        tag === 'farmer' ? 'Resident is registered under the local farmer/fisherfolk livelihood classification.' :
                                        tag === 'high_risk' ? 'Household flagged for high-risk monitoring per barangay social-welfare assessment.' :
                                        'Eligibility recorded during resident profiling.'
                                    "></dd>
                                </div>

                                <div>
                                    <dt class="text-[10px] text-slate-400 uppercase tracking-wide mb-1.5">Requirements on File</dt>
                                    <div class="space-y-1.5">
                                        <template x-for="req in [
                                            { label: 'Barangay Certificate of Residency', ok: true },
                                            { label: 'Valid Government-Issued ID', ok: selectedIndividual?.citizen_account?.id_uploaded ?? (selectedIndividual?.verification === 'Verified') },
                                            { label: tag === '4ps' ? '4Ps Beneficiary Card' : tag === 'pwd' ? 'PWD Identification Card' : tag === 'osca' || tag === 'senior' ? 'OSCA Senior Citizen ID' : tag === 'solo_parent' ? 'Solo Parent ID' : 'Sector Classification Document', ok: selectedIndividual?.verification === 'Verified' },
                                        ]" :key="req.label">
                                            <div class="flex items-center gap-2 text-xs">
                                                <i :data-lucide="req.ok ? 'circle-check' : 'circle-dashed'" class="w-3.5 h-3.5" :class="req.ok ? 'text-emerald-500' : 'text-slate-300'"></i>
                                                <span :class="req.ok ? 'text-slate-600' : 'text-slate-400'" x-text="req.label"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <p class="text-[11px] text-slate-400 pt-2 border-t border-slate-200">
                                    Source: Programs shown are derived from vulnerable-sector tags recorded during resident profiling — not a separate program-enrollment record.
                                </p>
                            </div>
                            </div>
                        </template>
                    </div>
                </template>

            </div>{{-- /Programs & Benefits --}}


            {{-- ── TAB 3: Events — resident-facing LGU service/interaction log ── --}}
            {{-- Events has no separate top-level admin module to redirect to (unlike
                 Dokyu/Tulong/Sakuna) — it's a profile-only concept. Clicking a card
                 expands it in place instead, so it still "goes somewhere" on click. --}}
            <div x-show="individualTab === 'Events'" class="space-y-4" x-data="{ expandedEvent: null }">

                <p class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 flex items-center gap-2">
                    <i data-lucide="calendar-clock" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                    LGU services and transactions this resident has completed. Click an event for details. For a full cross-system activity feed, see History.
                </p>

                <template x-if="!(selectedIndividual?.services?.length)">
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="calendar-clock" class="w-6 h-6 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-600">No events recorded yet</p>
                        <p class="text-xs text-slate-400 mt-1">LGU services availed will appear here as transactions are processed.</p>
                    </div>
                </template>

                <template x-if="selectedIndividual?.services?.length">
                    <div class="relative">
                        {{-- Timeline line --}}
                        <div class="absolute left-[19px] top-3 bottom-3 w-0.5 bg-slate-200 rounded-full"></div>

                        <div class="space-y-4">
                            <template x-for="(svc, idx) in (selectedIndividual?.services || [])" :key="idx">
                                <div class="flex items-start gap-4 relative">
                                    {{-- Timeline dot --}}
                                    <div class="w-9 h-9 rounded-full bg-white border-2 border-slate-200 flex items-center justify-center flex-shrink-0 shadow-sm z-10">
                                        <i data-lucide="file-text" class="w-4 h-4 text-brand-500"></i>
                                    </div>
                                    <div class="flex-1 bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
                                        <div
                                            class="p-4 flex items-start justify-between gap-3 cursor-pointer hover:bg-slate-50/60 transition-colors"
                                            @click="expandedEvent = expandedEvent === idx ? null : idx"
                                        
        role="button"
        tabindex="0"
        @keydown.enter.prevent="expandedEvent = expandedEvent === idx ? null : idx"
        @keydown.space.prevent="expandedEvent = expandedEvent === idx ? null : idx">
                                            <div>
                                                <p class="text-sm font-semibold text-navy-900" x-text="svc.type"></p>
                                                <p class="text-xs text-slate-400 mt-0.5" x-text="svc.date"></p>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                                    :class="{
                                                        'bg-green-100 text-green-700': svc.status === 'Completed' || svc.status === 'Released',
                                                        'bg-amber-100 text-amber-700': svc.status === 'Processing' || svc.status === 'Pending',
                                                        'bg-blue-100 text-blue-700': svc.status === 'Approved',
                                                        'bg-red-100 text-red-700': svc.status === 'Rejected' || svc.status === 'Cancelled',
                                                        'bg-slate-100 text-slate-600': !['Completed','Released','Processing','Pending','Approved','Rejected','Cancelled'].includes(svc.status),
                                                    }"
                                                    x-text="svc.status"
                                                ></span>
                                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-[160ms] ease-standard" :class="expandedEvent === idx && 'rotate-180'"></i>
                                            </div>
                                        </div>
                                        <div
                                            x-show="expandedEvent === idx" x-cloak
                                            x-transition:enter="transition duration-[200ms] ease-emphasized"
                                            x-transition:enter-start="opacity-0 -translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            class="border-t border-slate-100 bg-slate-50/60 p-4"
                                        >
                                            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                                <div>
                                                    <dt class="text-[10px] text-slate-400 uppercase tracking-wide mb-0.5">Beneficiary</dt>
                                                    <dd class="text-xs font-semibold text-navy-900" x-text="selectedIndividual?.name"></dd>
                                                </div>
                                                <div>
                                                    <dt class="text-[10px] text-slate-400 uppercase tracking-wide mb-0.5">Resident ID</dt>
                                                    <dd class="text-xs font-mono font-semibold text-navy-900" x-text="selectedIndividual?.id"></dd>
                                                </div>
                                                <div>
                                                    <dt class="text-[10px] text-slate-400 uppercase tracking-wide mb-0.5">Barangay</dt>
                                                    <dd class="text-xs font-semibold text-navy-900" x-text="'Brgy. ' + (selectedIndividual?.barangay || '—')"></dd>
                                                </div>
                                                <div>
                                                    <dt class="text-[10px] text-slate-400 uppercase tracking-wide mb-0.5">Status</dt>
                                                    <dd class="text-xs font-semibold text-navy-900" x-text="svc.status"></dd>
                                                </div>
                                            </dl>
                                            <p class="text-[11px] text-slate-400 mt-3 pt-3 border-t border-slate-200">
                                                Source: LGU transaction record. Events has no separate module of its own — this is the complete record on file for this transaction.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

            </div>{{-- /Events --}}


            {{-- ── TAB 4: Dokyu ── --}}
            <div x-show="individualTab === 'Dokyu'" class="space-y-3">

                <p class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                    Document checklist status is derived from verification records. Upload to update.
                </p>

                {{-- Base required documents --}}
                <template x-for="doc in [
                    { label: 'PSA Birth Certificate', key: 'psa_birth', icon: 'scroll-text', required: true, tag: null },
                    { label: 'Valid Government-Issued ID', key: 'gov_id', icon: 'id-card', required: true, tag: null },
                    { label: 'PhilSys National ID', key: 'philsys', icon: 'credit-card', required: true, tag: null },
                    { label: 'Proof of Residency', key: 'proof_residency', icon: 'home', required: true, tag: null },
                    { label: 'School ID / Enrollment Form', key: 'school_id', icon: 'graduation-cap', required: false, tag: 'youth' },
                    { label: 'School ID / Enrollment Form', key: 'school_id_child', icon: 'graduation-cap', required: false, tag: 'child' },
                    { label: 'PWD Identification Card', key: 'pwd_id', icon: 'accessibility', required: false, tag: 'pwd' },
                    { label: 'OSCA Senior Citizen ID', key: 'osca_id', icon: 'award', required: false, tag: 'senior' },
                    { label: 'Solo Parent ID', key: 'solo_id', icon: 'heart', required: false, tag: 'solo_parent' },
                    { label: '4Ps Beneficiary Card', key: 'fourps_card', icon: 'wallet', required: false, tag: '4ps' },
                ]" :key="doc.key">
                    {{-- Show if no tag OR tag is present in resident's tags --}}
                    <template x-if="!doc.tag || (selectedIndividual?.tags || []).includes(doc.tag)">
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                :class="selectedIndividual?.verification === 'Verified' ? 'bg-green-100' : 'bg-slate-100'"
                            >
                                <i
                                    :data-lucide="doc.icon"
                                    class="w-5 h-5"
                                    :class="selectedIndividual?.verification === 'Verified' ? 'text-green-600' : 'text-slate-400'"
                                ></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-navy-900" x-text="doc.label"></p>
                                <p class="text-xs text-slate-400 mt-0.5" x-text="doc.required ? 'Required' : 'Conditional (sector-specific)'"></p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0"
                                :class="selectedIndividual?.verification === 'Verified'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-slate-100 text-slate-500'"
                            >
                                <template x-if="selectedIndividual?.verification === 'Verified'">
                                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
                                </template>
                                <template x-if="selectedIndividual?.verification !== 'Verified'">
                                    <i data-lucide="circle-dashed" class="w-3.5 h-3.5"></i>
                                </template>
                                <span x-text="selectedIndividual?.verification === 'Verified' ? 'Submitted' : 'Missing'"></span>
                            </span>
                        </div>
                    </template>
                </template>

                <button
                    @click="$dispatch('toast', {message: 'Document upload feature — connect to document management module.', variant: 'info'})"
                    class="w-full mt-2 flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 border-dashed border-slate-200 text-sm text-slate-500 hover:border-brand-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                >
                    <i data-lucide="upload" class="w-4 h-4"></i>
                    Upload Document
                </button>

                {{-- Dokyu request history — visually separate from the identity checklist above --}}
                <div class="pt-2 mt-4 border-t border-dashed border-slate-200">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i data-lucide="file-stack" class="w-4 h-4 text-brand-400"></i>
                        Document Requests (Dokyu)
                    </h3>

                    <template x-if="!(selectedIndividual?.document_requests?.length)">
                        <div class="py-8 text-center bg-white rounded-xl border border-slate-200">
                            <i data-lucide="inbox" class="w-6 h-6 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-sm font-medium text-slate-600">No Dokyu requests on file</p>
                            <p class="text-xs text-slate-400 mt-1">Document requests submitted via Dokyu will appear here.</p>
                        </div>
                    </template>

                    <template x-if="selectedIndividual?.document_requests?.length">
                        <div class="space-y-2">
                            <template x-for="d in (selectedIndividual?.document_requests || [])" :key="d.ref">
                                <div
                                    class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 flex items-start justify-between gap-3 cursor-pointer hover:border-brand-200 hover:shadow-md transition-all group/dokyu"
                                    @click="goToDokyu(selectedIndividual, d.ref)"
                                    title="Open this resident's requests in Dokyu"
                                
        role="button"
        tabindex="0"
        @keydown.enter.prevent="goToDokyu(selectedIndividual, d.ref)"
        @keydown.space.prevent="goToDokyu(selectedIndividual, d.ref)">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-navy-900 group-hover/dokyu:text-brand-700 transition-colors" x-text="d.type"></p>
                                        <p class="text-xs text-slate-400 mt-0.5" x-text="d.office"></p>
                                        <p class="text-xs text-slate-500 mt-1 bg-slate-50 rounded-lg px-2.5 py-1.5">
                                            <span class="font-semibold text-slate-400">Purpose of request:</span> <span x-text="d.purpose"></span>
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1">
                                            <span class="font-mono" x-text="d.ref"></span>
                                            &nbsp;·&nbsp;
                                            <span x-text="'Submitted ' + d.submitted"></span>
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                            :class="{
                                                'bg-green-100 text-green-700': ['Released','Completed','Approved','Mark to Release'].includes(d.status),
                                                'bg-amber-100 text-amber-700': ['Processing','Under Review','Assigned'].includes(d.status),
                                                'bg-blue-100 text-blue-700': ['Submitted','Under Verification'].includes(d.status),
                                                'bg-red-100 text-red-700': ['Rejected','Cancelled'].includes(d.status),
                                                'bg-slate-100 text-slate-600': !['Released','Completed','Approved','Mark to Release','Processing','Under Review','Assigned','Submitted','Under Verification','Rejected','Cancelled'].includes(d.status),
                                            }"
                                            x-text="d.status"
                                        ></span>
                                        <span class="text-[10px] font-medium text-brand-500 opacity-0 group-hover/dokyu:opacity-100 transition-opacity inline-flex items-center gap-0.5">
                                            Open in Dokyu <i data-lucide="arrow-up-right" class="w-2.5 h-2.5"></i>
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

            </div>{{-- /Dokyu --}}


            {{-- ── TAB: Tulong (assistance request history) ── --}}
            <div x-show="individualTab === 'Tulong'" class="space-y-3">

                <p class="text-xs text-slate-500 bg-rose-50 border border-rose-100 rounded-lg px-4 py-2.5 flex items-center gap-2">
                    <i data-lucide="hand-heart" class="w-4 h-4 text-rose-400 flex-shrink-0"></i>
                    Assistance requests submitted via Tulong — distinct from ongoing sector-based Programs &amp; Benefits enrollment.
                </p>

                <template x-if="!(selectedIndividual?.assistance_requests?.length)">
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="hand-heart" class="w-6 h-6 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-600">No Tulong requests on file</p>
                        <p class="text-xs text-slate-400 mt-1">Assistance requests submitted by this resident will appear here.</p>
                    </div>
                </template>

                <template x-if="selectedIndividual?.assistance_requests?.length">
                    <div class="space-y-2">
                        <template x-for="a in (selectedIndividual?.assistance_requests || [])" :key="a.ref">
                            <div
                                class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 flex items-start justify-between gap-3 cursor-pointer hover:border-brand-200 hover:shadow-md transition-all group/tulong"
                                @click="goToTulong(selectedIndividual, a.ref)"
                                title="Open this resident's requests in Tulong"
                            
        role="button"
        tabindex="0"
        @keydown.enter.prevent="goToTulong(selectedIndividual, a.ref)"
        @keydown.space.prevent="goToTulong(selectedIndividual, a.ref)">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-navy-900 group-hover/tulong:text-brand-700 transition-colors" x-text="a.type"></p>
                                    <p class="text-xs text-slate-400 mt-0.5" x-text="a.office"></p>
                                    <p class="text-xs text-slate-500 mt-1 bg-slate-50 rounded-lg px-2.5 py-1.5">
                                        <span class="font-semibold text-slate-400">Purpose of request:</span> <span x-text="a.purpose"></span>
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        <span class="font-mono" x-text="a.ref"></span>
                                        &nbsp;·&nbsp;
                                        <span x-text="'Submitted ' + a.submitted"></span>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-1.5 shrink-0">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-green-100 text-green-700': ['Released','Completed','Approved'].includes(a.status),
                                            'bg-amber-100 text-amber-700': ['Processing','Under Review','Assigned'].includes(a.status),
                                            'bg-blue-100 text-blue-700': a.status === 'Submitted',
                                            'bg-red-100 text-red-700': ['Rejected','Cancelled'].includes(a.status),
                                            'bg-slate-100 text-slate-600': !['Released','Completed','Approved','Processing','Under Review','Assigned','Submitted','Rejected','Cancelled'].includes(a.status),
                                        }"
                                        x-text="a.status"
                                    ></span>
                                    <span class="text-[10px] font-medium text-brand-500 opacity-0 group-hover/tulong:opacity-100 transition-opacity inline-flex items-center gap-0.5">
                                        Open in Tulong <i data-lucide="arrow-up-right" class="w-2.5 h-2.5"></i>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

            </div>{{-- /Tulong --}}


            {{-- ── TAB: Sakuna (incident report history) ── --}}
            <div x-show="individualTab === 'Sakuna'" class="space-y-3">

                <p class="text-xs text-slate-500 bg-orange-50 border border-orange-100 rounded-lg px-4 py-2.5 flex items-center gap-2">
                    <i data-lucide="triangle-alert" class="w-4 h-4 text-orange-400 flex-shrink-0"></i>
                    Disaster / incident reports filed by or concerning this resident.
                </p>

                <template x-if="!(selectedIndividual?.incident_reports?.length)">
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="triangle-alert" class="w-6 h-6 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-600">No incident reports on file</p>
                        <p class="text-xs text-slate-400 mt-1">Sakuna incident reports involving this resident will appear here.</p>
                    </div>
                </template>

                <template x-if="selectedIndividual?.incident_reports?.length">
                    <div class="space-y-2">
                        <template x-for="i in (selectedIndividual?.incident_reports || [])" :key="i.ref">
                            <div
                                class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 cursor-pointer hover:border-brand-200 hover:shadow-md transition-all group/sakuna"
                                @click="goToSakuna(selectedIndividual, i.ref)"
                                title="Open this report in Sakuna"
                            
        role="button"
        tabindex="0"
        @keydown.enter.prevent="goToSakuna(selectedIndividual, i.ref)"
        @keydown.space.prevent="goToSakuna(selectedIndividual, i.ref)">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-navy-900 group-hover/sakuna:text-brand-700 transition-colors" x-text="i.type"></p>
                                            <span
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold"
                                                :class="i.source === 'Citizen Portal' ? 'bg-brand-50 text-brand-600' : 'bg-indigo-50 text-indigo-600'"
                                            >
                                                <i :data-lucide="i.source === 'Citizen Portal' ? 'smartphone' : 'landmark'" class="w-2.5 h-2.5"></i>
                                                <span x-text="'Source: ' + (i.source || 'Municipal DRRM Office')"></span>
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-0.5" x-text="i.office + ' · ' + i.location"></p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            <span class="font-mono" x-text="i.ref"></span>
                                            &nbsp;·&nbsp;
                                            <span x-text="i.date"></span>
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1.5 bg-slate-50 rounded-lg px-2.5 py-1.5" x-show="i.description">
                                            <span class="font-semibold text-slate-400">What was reported:</span> <span x-text="i.description"></span>
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                            :class="{
                                                'bg-green-100 text-green-700': ['Resolved','Closed'].includes(i.status),
                                                'bg-amber-100 text-amber-700': ['Dispatched'].includes(i.status),
                                                'bg-blue-100 text-blue-700': ['Submitted','Validated'].includes(i.status),
                                                'bg-slate-100 text-slate-600': !['Resolved','Closed','Dispatched','Submitted','Validated'].includes(i.status),
                                            }"
                                            x-text="i.status"
                                        ></span>
                                        <span class="text-[10px] font-medium text-brand-500 opacity-0 group-hover/sakuna:opacity-100 transition-opacity inline-flex items-center gap-0.5">
                                            Open in Sakuna <i data-lucide="arrow-up-right" class="w-2.5 h-2.5"></i>
                                        </span>
                                    </div>
                                </div>

                                {{-- Data path — where the report came from and where it goes next --}}
                                <div class="flex items-start mt-4 pt-3 border-t border-slate-100">
                                    <template x-for="(step, si) in ['Reported', 'Barangay Intake', 'DRRM Review', 'Response', 'Resolution']" :key="si">
                                        <div class="flex items-center flex-1 last:flex-none">
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="w-2 h-2 rounded-full shrink-0" :class="si <= sakunaStage(i.status) ? 'bg-orange-500' : 'bg-slate-200'"></span>
                                                <span class="text-[9px] text-center leading-tight w-14" :class="si <= sakunaStage(i.status) ? 'text-orange-600 font-medium' : 'text-slate-300'" x-text="step"></span>
                                            </div>
                                            <div class="h-px flex-1 -mt-3.5" :class="si < sakunaStage(i.status) ? 'bg-orange-300' : 'bg-slate-200'" x-show="si < 4"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

            </div>{{-- /Sakuna --}}


            {{-- ── TAB 5: History ── --}}
            <div x-show="individualTab === 'History'" class="space-y-4">

                {{-- Profile History comes first — the record-level facts
                     (created/updated/verification/completion), distinct from
                     the dated cross-system feed below. --}}
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Profile History</h3>
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card divide-y divide-slate-100">

                    <div class="p-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="user-plus" class="w-4 h-4 text-blue-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-navy-900">Record Created</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Resident profile encoded into the system.
                                Encoded by: <span class="font-medium text-slate-700">System</span>
                            </p>
                        </div>
                        <p class="text-xs text-slate-400 flex-shrink-0" x-text="selectedIndividual?.created || '—'"></p>
                    </div>

                    <div class="p-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="pencil" class="w-4 h-4 text-amber-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-navy-900">Last Updated</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Profile data last modified.
                                Updated by: <span class="font-medium text-slate-700">System</span>
                            </p>
                        </div>
                        <p class="text-xs text-slate-400 flex-shrink-0" x-text="selectedIndividual?.last_updated || '—'"></p>
                    </div>

                    <div class="p-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                            :class="selectedIndividual?.verification === 'Verified' ? 'bg-green-100' : 'bg-slate-100'"
                        >
                            <i data-lucide="shield-check" class="w-4 h-4"
                                :class="selectedIndividual?.verification === 'Verified' ? 'text-green-600' : 'text-slate-400'"
                            ></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-navy-900">Verification Status</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Current verification level:
                                <span class="font-medium text-slate-700" x-text="selectedIndividual?.verification"></span>
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                            :class="verificationBadge(selectedIndividual?.verification).class"
                            x-text="selectedIndividual?.verification"
                        ></span>
                    </div>

                    <div class="p-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full bg-violet-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="chart-bar" class="w-4 h-4 text-violet-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-navy-900">Profile Completion</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Current profile completeness score.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-[600ms] ease-standard"
                                    :class="completionColor(computeProfileCompleteness(selectedIndividual))"
                                    :style="'width:' + (computeProfileCompleteness(selectedIndividual) || 0) + '%'"
                                ></div>
                            </div>
                            <span
                                class="text-xs font-bold"
                                :class="completionTextColor(computeProfileCompleteness(selectedIndividual))"
                                x-text="(computeProfileCompleteness(selectedIndividual) || 0) + '%'"
                            ></span>
                        </div>
                    </div>

                    </div>
                </div>

                {{-- Merged chronological activity timeline (account, Dokyu, Tulong, Sakuna events) --}}
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Activity Timeline</h3>

                    <template x-if="!individualTimeline.length">
                        <div class="bg-slate-50 rounded-xl border border-dashed border-slate-200 p-5 text-center">
                            <i data-lucide="clock-2" class="w-6 h-6 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-xs text-slate-400">No timeline events recorded for this resident yet.</p>
                        </div>
                    </template>

                    <template x-if="individualTimeline.length">
                        <div class="relative">
                            <div class="absolute left-[19px] top-3 bottom-3 w-0.5 bg-slate-200 rounded-full"></div>
                            <div class="space-y-3">
                                <template x-for="(ev, idx) in individualTimeline" :key="idx">
                                    <div class="flex items-start gap-4 relative">
                                        {{-- The resident's own barangay seal stands in for the event
                                             icon when one is on file (ties every entry back to
                                             "this happened for a resident of Brgy. X"); the colored
                                             ring still carries the event-type distinction. Falls back
                                             to the plain icon-circle for barangays with no seal asset. --}}
                                        <div
                                            class="w-9 h-9 rounded-full bg-white border-2 flex items-center justify-center flex-shrink-0 shadow-sm z-10 overflow-hidden"
                                            :class="{
                                                'border-blue-200': ev.color === 'blue',
                                                'border-amber-200': ev.color === 'amber',
                                                'border-indigo-200': ev.color === 'indigo',
                                                'border-rose-200': ev.color === 'rose',
                                                'border-orange-200': ev.color === 'orange',
                                            }"
                                        >
                                            <img
                                                x-show="barangaySealFor(selectedIndividual?.barangay)"
                                                :src="barangaySealFor(selectedIndividual?.barangay)"
                                                class="w-full h-full object-cover"
                                                :alt="'Brgy. ' + selectedIndividual?.barangay + ' seal'"
                                            >
                                            <i
                                                x-show="!barangaySealFor(selectedIndividual?.barangay)"
                                                :data-lucide="ev.icon"
                                                class="w-4 h-4"
                                                :class="{
                                                    'text-blue-500': ev.color === 'blue',
                                                    'text-amber-500': ev.color === 'amber',
                                                    'text-indigo-500': ev.color === 'indigo',
                                                    'text-rose-500': ev.color === 'rose',
                                                    'text-orange-500': ev.color === 'orange',
                                                }"
                                            ></i>
                                        </div>
                                        <div class="flex-1 bg-white rounded-2xl border border-slate-100 shadow-card p-3.5">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-navy-900" x-text="ev.label"></p>
                                                    <p class="text-xs text-slate-500 mt-0.5" x-text="ev.desc"></p>
                                                </div>
                                                <p class="text-xs text-slate-400 flex-shrink-0" x-text="ev.date"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

            </div>{{-- /History --}}

            </div>{{-- /content panel --}}

        </div>{{-- /body: side nav + content panel --}}


        {{-- ════════════════════════════════════════════════════════
             FOOTER ACTIONS
        ════════════════════════════════════════════════════════ --}}
        <div class="shrink-0 px-6 py-4 border-t border-slate-200 bg-white rounded-b-2xl flex items-center justify-end gap-3 flex-wrap">

            <div class="flex items-center gap-2">

                {{-- Edit button --}}
                <button
                    @click="$dispatch('toast', {message: 'Edit resident — connect to edit modal.', variant: 'info'})"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                    x-show="can('const_resident','edit')"
                >
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                    Edit
                </button>

                {{-- Lifecycle dropdown --}}
                <div class="relative" x-data="{ lopen: false }" x-show="can('const_resident','edit')">
                    <button
                        @click="lopen = !lopen"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800 transition-colors"
                    >
                        <i data-lucide="settings-2" class="w-4 h-4"></i>
                        Lifecycle
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 ml-0.5 transition-transform" :class="lopen ? 'rotate-180' : ''"></i>
                    </button>

                    <div
                        x-show="lopen"
                        @click.outside="lopen = false"
                        x-transition:enter="transition duration-[200ms] ease-emphasized"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition duration-[150ms] ease-exit"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                        class="absolute bottom-full right-0 mb-2 w-52 rounded-xl bg-white border border-slate-200 shadow-lg divide-y divide-slate-100 overflow-hidden z-50"
                        style="display:none"
                    >
                        <div class="py-1">
                            <button
                                @click="lopen=false; openLifecycle(selectedIndividual, 'Transfer'); showIndividualDetail=false"
                                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                            >
                                <i data-lucide="arrow-right-from-line" class="w-4 h-4 text-indigo-400"></i>
                                Transfer Resident
                            </button>
                            <button
                                @click="lopen=false; openLifecycle(selectedIndividual, 'Deceased'); showIndividualDetail=false"
                                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                            >
                                <i data-lucide="flower" class="w-4 h-4 text-slate-400"></i>
                                Mark as Deceased
                            </button>
                        </div>
                        <div class="py-1">
                            <button
                                @click="lopen=false; openLifecycle(selectedIndividual, 'Archive'); showIndividualDetail=false"
                                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-amber-600 hover:bg-amber-50 transition-colors text-left"
                            >
                                <i data-lucide="archive" class="w-4 h-4"></i>
                                Archive Record
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>{{-- /root --}}

    {{-- ════════════════════════════════════════════════════════
         SIGNAL REVIEW POPUP — a genuine popup: a compact card
         centered over a dimmed backdrop, sized to its content
         instead of stretching full-screen. Opened by clicking any
         signal signifier (header chip or Overview summary row);
         Back/Close/backdrop-click/Esc all return to whatever tab
         was open underneath. ──────────────────────────────────
    ════════════════════════════════════════════════════════ --}}
    <div
        x-show="reviewingSignal"
        x-cloak
        x-transition:enter="transition duration-[200ms] ease-emphasized"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-[150ms] ease-exit"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 z-40 bg-navy-950/50 backdrop-blur-sm rounded-2xl flex items-center justify-center p-4"
        @click.self="reviewingSignal = null"
        @keydown.escape.window="reviewingSignal = null"
        x-data="{ rejecting: false, rejectRemarks: '' }"
    >
        <div
            class="w-full max-w-sm bg-white rounded-2xl shadow-float flex flex-col max-h-full overflow-hidden"
            x-transition:enter="transition duration-[200ms] ease-emphasized"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
        >
            <div class="shrink-0 flex items-center gap-2.5 px-4 py-3 border-b border-slate-100">
                <button
                    type="button"
                    @click="reviewingSignal = null"
                    class="w-7 h-7 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500 hover:text-navy-900 transition-colors shrink-0"
                    title="Back to profile"
                >
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </button>
                <div class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                    <i :data-lucide="currentSignalMeta.icon" class="w-3.5 h-3.5"></i>
                </div>
                <h3 class="text-sm font-semibold text-navy-900 truncate" x-text="currentSignalMeta.label"></h3>
                <button
                    type="button"
                    @click="reviewingSignal = null"
                    class="ml-auto w-7 h-7 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors shrink-0"
                    title="Close"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto scrollbar-thin px-4 py-4">
                <template x-if="!selectedIndividual?.citizen_account">
                    <div class="py-6 text-center">
                        <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2">
                            <i data-lucide="shield-off" class="w-4.5 h-4.5 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-600">No Citizen Portal account to verify</p>
                    </div>
                </template>

                {{-- ID Document / Face Scan / Mobile Number / Terms — same
                     evidence-then-action shape, evidence content differs. --}}
                <template x-if="selectedIndividual?.citizen_account && ['id_document','face_scan','mobile','terms'].includes(reviewingSignal)">
                    <div>
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Status</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-semibold"
                                :class="!currentSignalMeta.completed ? 'text-slate-400' : (!currentSignalMeta.review || currentSignalMeta.review.status === 'Not Reviewed') ? 'text-amber-600' : currentSignalMeta.review.status === 'Complete' ? 'text-emerald-600' : 'text-rose-600'"
                            >
                                <i :data-lucide="!currentSignalMeta.completed ? 'minus-circle' : (!currentSignalMeta.review) ? 'clock' : currentSignalMeta.review.status === 'Complete' ? 'circle-check' : 'circle-x'" class="w-3.5 h-3.5"></i>
                                <span x-text="!currentSignalMeta.completed ? 'Not Yet Completed' : (!currentSignalMeta.review ? 'Not Reviewed' : currentSignalMeta.review.status === 'Complete' ? currentSignalMeta.completedActionText : 'Rejected')"></span>
                            </span>
                        </div>

                        {{-- Evidence — what the citizen actually submitted for this signal. --}}
                        <template x-if="reviewingSignal === 'id_document'">
                            <div class="mb-3">
                                <template x-if="(selectedIndividual?.citizen_account?.verified_ids || []).length">
                                    <div class="flex flex-wrap gap-2.5">
                                        <template x-for="idDoc in selectedIndividual.citizen_account.verified_ids" :key="idDoc.type">
                                            <div class="w-40 rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white shrink-0">
                                                <div class="bg-gradient-to-br from-navy-800 to-brand-900 px-2.5 py-1.5 text-white">
                                                    <p class="text-[9px] font-bold truncate" x-text="idDoc.type"></p>
                                                </div>
                                                <div class="p-2.5 flex items-center gap-2">
                                                    <div class="w-9 h-11 rounded bg-slate-100 flex items-center justify-center shrink-0"><i data-lucide="user-round" class="w-4 h-4 text-slate-300"></i></div>
                                                    <p class="text-[11px] font-mono font-semibold text-navy-900" x-text="idDoc.masked"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!(selectedIndividual?.citizen_account?.verified_ids || []).length && currentSignalMeta.completed">
                                    <div class="w-40 rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                                        <div class="bg-gradient-to-br from-navy-800 to-brand-900 px-2.5 py-1.5 text-white">
                                            <p class="text-[9px] font-bold truncate">Valid Government ID</p>
                                        </div>
                                        <div class="p-2.5 flex items-center gap-2">
                                            <div class="w-9 h-11 rounded bg-slate-100 flex items-center justify-center shrink-0"><i data-lucide="user-round" class="w-4 h-4 text-slate-300"></i></div>
                                            <p class="text-[11px] font-mono font-semibold text-navy-900">•••• •••• ••••</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="reviewingSignal === 'face_scan'">
                            <div class="mb-3" x-show="currentSignalMeta.completed">
                                <div class="relative w-24 rounded-xl overflow-hidden border border-slate-200 shadow-sm aspect-[3/4] bg-gradient-to-br from-slate-600 to-slate-900 flex items-center justify-center">
                                    <i data-lucide="user-round" class="w-8 h-8 text-white/50"></i>
                                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-bold bg-black/40 text-white">LIVE</span>
                                </div>
                            </div>
                        </template>
                        <template x-if="reviewingSignal === 'mobile'">
                            <div class="mb-3 text-xs text-slate-600" x-show="currentSignalMeta.completed">
                                Registered mobile: <span class="font-mono font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.mobile || '—'"></span> — OTP-confirmed during onboarding.
                            </div>
                        </template>
                        <template x-if="reviewingSignal === 'terms'">
                            <div class="mb-3 text-xs text-slate-600" x-show="currentSignalMeta.completed">
                                Terms & Conditions accepted during the onboarding wizard on <span class="font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.registered_at || '—'"></span>.
                            </div>
                        </template>

                        <p class="text-xs text-slate-400 italic" x-show="!currentSignalMeta.completed" x-text="currentSignalMeta.notCompletedText"></p>

                        {{-- Reviewer guidance — distinctive per signal, not a generic
                             "please verify" line, so this is more than a bare
                             Confirm/Reject button: it tells the reviewer what to
                             actually check for THIS signal before deciding. Only
                             shown while there's still a decision to make — once
                             reviewed, the outcome above already speaks for itself. --}}
                        <div
                            class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-2.5 mb-3 flex items-start gap-2"
                            x-show="currentSignalMeta.completed && !currentSignalMeta.review"
                        >
                            <i :data-lucide="currentSignalMeta.adviceIcon" class="w-3.5 h-3.5 text-indigo-500 shrink-0 mt-0.5"></i>
                            <p class="text-[11px] text-indigo-800 leading-relaxed" x-text="currentSignalMeta.adviceText"></p>
                        </div>

                        {{-- Action — only once the citizen has actually completed the
                             step AND it hasn't already been reviewed. A signal that's
                             already Complete/Rejected is done — the popup still opens
                             to show what happened (evidence + "Reviewed by" byline
                             above), but there's nothing left to confirm or reject, so
                             the action buttons don't render at all rather than sitting
                             there uselessly next to an already-final outcome. --}}
                        <div x-show="currentSignalMeta.completed" class="pt-3 border-t border-slate-100">
                            <p class="text-[11px] text-slate-400 mb-1.5" x-show="currentSignalMeta.review && currentSignalMeta.review.status !== 'Not Reviewed'" x-text="'Reviewed by ' + currentSignalMeta.review?.reviewed_by + ' · ' + currentSignalMeta.review?.reviewed_at"></p>
                            <p class="text-[11px] text-rose-600 bg-rose-50 rounded-lg px-2.5 py-2 mb-2" x-show="currentSignalMeta.review?.status === 'Rejected' && currentSignalMeta.review?.remarks" x-text="currentSignalMeta.review?.remarks"></p>
                            <template x-if="!currentSignalMeta.review">
                                <div>
                                    <div class="flex items-center gap-2" x-show="can('const_citizen_verification','verify') && !rejecting">
                                        <x-ui.button size="sm" icon="check" @click="reviewSignal(selectedIndividual, reviewingSignal, 'Complete')">Confirm Complete</x-ui.button>
                                        <x-ui.button size="sm" variant="danger" icon="flag" @click="rejecting = true; rejectRemarks = ''">Reject</x-ui.button>
                                    </div>
                                    <div x-show="rejecting" x-cloak class="space-y-2">
                                        <textarea aria-label="'Reason for rejecting ' + currentSignalMeta.label + '…'"
                                            x-model="rejectRemarks" rows="2" :placeholder="'Reason for rejecting ' + currentSignalMeta.label + '…'"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-xs text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"
                                        ></textarea>
                                        <div class="flex items-center gap-2">
                                            <x-ui.button variant="danger" size="sm" icon="flag" x-bind:disabled="!rejectRemarks.trim()" @click="reviewSignal(selectedIndividual, reviewingSignal, 'Rejected', rejectRemarks); rejecting = false">Confirm Reject</x-ui.button>
                                            <button type="button" @click="rejecting = false" class="text-xs font-medium text-slate-400 hover:text-slate-600">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Face Match — compares two pieces of evidence at once and has
                     its own working Flag/Confirm flow. --}}
                <template x-if="selectedIndividual?.citizen_account && reviewingSignal === 'face_match'">
                    <div>
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Status</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-semibold"
                                :class="selectedIndividual?.citizen_account?.identity_check?.face_match === 'Match' ? 'text-emerald-600' : selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch' ? 'text-rose-600' : 'text-amber-600'"
                            >
                                <i :data-lucide="selectedIndividual?.citizen_account?.identity_check?.face_match === 'Match' ? 'circle-check' : selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch' ? 'circle-x' : 'clock'" class="w-3.5 h-3.5"></i>
                                <span x-text="selectedIndividual?.citizen_account?.identity_check?.face_match === 'Match' ? 'Confirmed Match' : selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch' ? 'Mismatch Found' : 'Not Reviewed'"></span>
                            </span>
                        </div>
                        {{-- Reviewer guidance — only while there's still a decision to
                             make; once Confirmed/Flagged the outcome above already
                             speaks for itself. Matches the same distinctive-per-signal
                             advice pattern as the other four signals above. --}}
                        <div
                            class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-2.5 mb-3 flex items-start gap-2"
                            x-show="selectedIndividual?.citizen_account?.identity_check?.face_match === 'Not Reviewed'"
                        >
                            <i :data-lucide="currentSignalMeta.adviceIcon" class="w-3.5 h-3.5 text-indigo-500 shrink-0 mt-0.5"></i>
                            <p class="text-[11px] text-indigo-800 leading-relaxed" x-text="currentSignalMeta.adviceText"></p>
                        </div>
                        <div class="flex flex-wrap items-start gap-3 mb-3" x-show="selectedIndividual?.citizen_account?.photo_captured">
                            <div class="w-28 shrink-0">
                                <div class="w-full h-16 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center"><i data-lucide="id-card" class="w-5 h-5 text-slate-300"></i></div>
                                <p class="text-[10px] text-slate-400 mt-1 text-center">Submitted ID</p>
                            </div>
                            <div class="w-20 shrink-0">
                                <div class="w-full aspect-[3/4] rounded-xl bg-gradient-to-br from-slate-600 to-slate-900 flex items-center justify-center"><i data-lucide="user-round" class="w-6 h-6 text-white/50"></i></div>
                                <p class="text-[10px] text-slate-400 mt-1 text-center">Selfie</p>
                            </div>
                        </div>
                        <p
                            class="text-[11px] text-rose-600 bg-rose-50 rounded-lg px-2.5 py-2 mb-2 leading-snug"
                            x-show="selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch' && selectedIndividual?.citizen_account?.identity_check?.remarks"
                            x-text="selectedIndividual?.citizen_account?.identity_check?.remarks"
                        ></p>
                        <p
                            class="text-[11px] text-slate-400 mb-2"
                            x-show="selectedIndividual?.citizen_account?.identity_check?.reviewed_by"
                            x-text="'Reviewed by ' + selectedIndividual?.citizen_account?.identity_check?.reviewed_by + ' · ' + selectedIndividual?.citizen_account?.identity_check?.reviewed_at"
                        ></p>
                        <div class="pt-3 border-t border-slate-100" x-show="selectedIndividual?.citizen_account?.photo_captured && can('const_citizen_verification','verify')">
                            <button
                                type="button"
                                x-show="selectedIndividual?.citizen_account?.identity_check?.face_match !== 'Mismatch'"
                                @click="$refs.faceMismatchBox.classList.toggle('hidden')"
                                class="text-[11px] font-medium text-rose-600 hover:underline inline-flex items-center gap-1"
                            >
                                <i data-lucide="flag" class="w-3 h-3"></i> Flag face mismatch
                            </button>
                            <button
                                type="button"
                                x-show="selectedIndividual?.citizen_account?.identity_check?.face_match === 'Mismatch'"
                                @click="clearFaceMismatch(selectedIndividual)"
                                class="text-[11px] font-medium text-emerald-600 hover:underline inline-flex items-center gap-1"
                            >
                                <i data-lucide="check" class="w-3 h-3"></i> Confirm match instead
                            </button>
                            <div x-ref="faceMismatchBox" class="hidden mt-2 space-y-1.5">
                                <textarea
                                    x-model="faceMismatchRemarks" rows="2" placeholder="Describe the mismatch — e.g. face angle unclear, lighting differs from ID photo…"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-xs text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"
                                ></textarea>
                                <x-ui.button variant="danger" size="sm" icon="flag" x-bind:disabled="!faceMismatchRemarks.trim()" @click="flagFaceMismatch(selectedIndividual); $refs.faceMismatchBox.classList.add('hidden')">Record Mismatch</x-ui.button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>{{-- /Signal Review Popup --}}

    </x-ui.modal>
</div>
