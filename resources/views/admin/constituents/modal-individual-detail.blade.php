{{-- ============================================================
     FILE: modal-individual-detail.blade.php
     Partial: Individual detail modal
     Alpine scope: parent x-data (showIndividualDetail, selectedIndividual, etc.)

     Trimmed significantly from its earlier, much larger version: that one
     built most of its surface (a 7-tab layout with Dokyu/Tulong/Events/Sakuna
     history, a profile-completion ring, per-signal ID/face/mobile/terms
     review with its own advice text, and a duplicate-identity resolution
     flow) around data no backend endpoint returns for a resident record —
     confirmed by reading ConstituentsController/CitizenVerificationController
     end to end, not assumed. What's kept below is exactly what
     GET /admin/residents/{ref} and GET /admin/citizens/{accountNo} return.
     ============================================================ --}}

<div
    x-data="{
        get open() { return showIndividualDetail; },
        set open(v) { showIndividualDetail = v; },
        editing: false,
        saving: false,
        edit: {},
        lopen: false,
        startEdit() {
            const i = selectedIndividual;
            this.edit = {
                sex: i.sex, civil_status: i.civil_status, contact: i.contact, occupation: i.occupation,
                education: i.education, income_range: i.income_range, sitio: i.sitio, relation: i.relation,
                tags: [...(i.tags || [])],
            };
            this.editing = true;
        },
        toggleEditTag(tag) {
            if (this.edit.tags.includes(tag)) this.edit.tags = this.edit.tags.filter(t => t !== tag);
            else this.edit.tags = [...this.edit.tags, tag];
        },
        async saveEdit() {
            if (this.saving) return;
            this.saving = true;
            try {
                const res = await $api.put('/admin/residents/' + selectedIndividual.ref, this.edit);
                Object.assign(selectedIndividual, res.data);
                const idx = this.individuals.findIndex(x => x.ref === selectedIndividual.ref);
                if (idx !== -1) this.individuals[idx] = { ...this.individuals[idx], ...res.data };
                this.editing = false;
                this.$dispatch('toast', { message: 'Resident record updated.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not save changes.', variant: 'error' });
            } finally { this.saving = false; }
        },
    }"
>
    <x-ui.modal maxWidth="2xl" :bare="true" :fullscreen="true">

    <div class="flex flex-col min-h-0 h-full" x-show="selectedIndividual">

        {{-- ════════════════════════════════════════════════════════
             MODAL HEADER
        ════════════════════════════════════════════════════════ --}}
        <div class="relative shrink-0 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 text-white px-6 py-5 rounded-t-2xl overflow-hidden">
            <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute -bottom-6 -left-6 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>

            <div class="absolute top-4 right-4 flex items-center gap-1.5 z-20">
                <button type="button" @click="compact = !compact" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" :title="compact ? 'Expand to full screen' : 'Shrink to popup'">
                    <span x-show="compact"><i data-lucide="maximize-2" class="w-3.5 h-3.5"></i></span>
                    <span x-show="!compact"><i data-lucide="minimize-2" class="w-3.5 h-3.5"></i></span>
                </button>
                <button type="button" @click="showIndividualDetail = false; editing = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" title="Close">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="flex items-start gap-5 relative z-10">
                <div class="flex-shrink-0 w-20 h-20 rounded-full bg-white/10 flex items-center justify-center text-white font-bold text-xl uppercase">
                    <span x-text="(selectedIndividual?.name || '').split(' ').map(w=>w[0]).slice(0,2).join('')"></span>
                </div>

                {{-- Identity block --}}
                <div class="flex-1 min-w-0 pt-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white" x-text="selectedIndividual?.status"></span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/10 text-white/80">
                            <i data-lucide="shield-check" class="w-3 h-3"></i>
                            <span x-text="selectedIndividual?.verification"></span>
                        </span>
                        <span x-show="selectedIndividual?.citizen_account" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/30">
                            <i data-lucide="smartphone" class="w-3 h-3"></i> Portal Linked
                        </span>
                        <span x-show="!selectedIndividual?.citizen_account" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/5 text-white/40">
                            <i data-lucide="smartphone" class="w-3 h-3"></i> No Portal Account
                        </span>
                    </div>

                    <h2 class="text-xl font-bold text-white leading-tight truncate" x-text="selectedIndividual?.name"></h2>
                    <p class="text-sm text-white/60 mt-0.5">
                        ID: <span class="font-mono font-medium text-white/80" x-text="selectedIndividual?.ref"></span>
                        &nbsp;·&nbsp;
                        <span x-text="selectedIndividual?.sex || '—'"></span>,
                        <span x-text="(selectedIndividual?.age ?? '—') + ' years old'"></span>
                    </p>

                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                            <i data-lucide="house" class="w-3 h-3"></i>
                            <span x-text="selectedIndividual?.relation || 'Member'"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                            <i data-lucide="map-pin" class="w-3 h-3"></i>
                            <span x-text="'Brgy. ' + (selectedIndividual?.barangay || '—')"></span>
                        </span>
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
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
             BODY
        ════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin px-6 py-5 bg-slate-50/50 space-y-4">

            <template x-if="!editing">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
            <div class="flex flex-col gap-4">

                {{-- Personal info --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Personal Information</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="user" class="w-3 h-3"></i>Full Name</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.name || '—'"></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="calendar-days" class="w-3 h-3"></i>Date of Birth</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.dob || '—'"></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="venus-and-mars" class="w-3 h-3"></i>Sex</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.sex || '—'"></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="heart-handshake" class="w-3 h-3"></i>Civil Status</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.civil_status || '—'"></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="phone" class="w-3 h-3"></i>Contact Number</dt>
                            <dd class="text-sm font-semibold text-navy-900">
                                <a :href="'tel:' + selectedIndividual?.contact" class="text-brand-600 hover:underline" x-text="selectedIndividual?.contact || '—'"></a>
                            </dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="briefcase" class="w-3 h-3"></i>Occupation</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.occupation || '—'"></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="graduation-cap" class="w-3 h-3"></i>Highest Education</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.education || '—'"></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="wallet" class="w-3 h-3"></i>Income Range</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.income_range || '—'"></dd>
                        </div>
                    </dl>
                </div>

                {{-- Address & Household --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Address & Household</h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium" :class="selectedIndividual?.household ? 'text-emerald-600' : 'text-amber-600'">
                                <i :data-lucide="selectedIndividual?.household ? 'circle-check' : 'circle-alert'" class="w-3.5 h-3.5"></i>Household
                            </span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium" :class="selectedIndividual?.family ? 'text-emerald-600' : 'text-amber-600'">
                                <i :data-lucide="selectedIndividual?.family ? 'circle-check' : 'circle-alert'" class="w-3.5 h-3.5"></i>Family
                            </span>
                        </div>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="map-pin" class="w-3 h-3"></i>Barangay</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="'Brgy. ' + (selectedIndividual?.barangay || '—')"></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="signpost" class="w-3 h-3"></i>Sitio / Purok</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.sitio || '—'"></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="hash" class="w-3 h-3"></i>Household</dt>
                            <dd class="text-sm font-mono font-semibold text-navy-900" x-text="selectedIndividual?.household?.ref || '—'"></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="hash" class="w-3 h-3"></i>Family</dt>
                            <dd class="text-sm font-mono font-semibold text-navy-900" x-text="selectedIndividual?.family?.ref || '—'"></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1 text-xs text-slate-500 uppercase tracking-wide mb-0.5"><i data-lucide="users" class="w-3 h-3"></i>Relation in Household</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.relation || '—'"></dd>
                        </div>
                    </dl>
                    <p class="mt-4 pt-4 border-t border-slate-100 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2" x-show="!selectedIndividual?.household">
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

            </div>

            {{-- Right column --}}
            <div class="flex flex-col gap-4">

                {{-- Citizen Portal Account — real fields and real verify/reject
                     actions only. The mock's per-signal ID/face/mobile/terms
                     review, face-match state, and duplicate-account flagging
                     have no backend equivalent (CitizenVerificationController's
                     row carries only what's below) and are not reproduced here. --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-show="selectedIndividual?.citizen_account">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Citizen Portal Account</h3>
                    <dl class="space-y-2.5 mb-4">
                        <div class="flex justify-between items-center gap-2">
                            <dt class="text-xs text-slate-500">Account No.</dt>
                            <dd class="text-xs font-mono font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.account_no"></dd>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <dt class="text-xs text-slate-500">Status</dt>
                            <dd>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 ring-emerald-200': selectedIndividual?.citizen_account?.status === 'Approved',
                                        'bg-amber-50 text-amber-700 ring-amber-200': selectedIndividual?.citizen_account?.status === 'Pending Review',
                                        'bg-indigo-50 text-indigo-700 ring-indigo-200': selectedIndividual?.citizen_account?.status === 'Under Verification',
                                        'bg-rose-50 text-rose-700 ring-rose-200': selectedIndividual?.citizen_account?.status === 'Rejected',
                                        'bg-slate-100 text-slate-500 ring-slate-200': !['Approved','Pending Review','Under Verification','Rejected'].includes(selectedIndividual?.citizen_account?.status),
                                    }"
                                    x-text="selectedIndividual?.citizen_account?.status"
                                ></span>
                            </dd>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <dt class="text-xs text-slate-500">Email</dt>
                            <dd class="text-xs font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.email || '—'"></dd>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <dt class="text-xs text-slate-500">Mobile</dt>
                            <dd class="text-xs font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.mobile || '—'"></dd>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <dt class="text-xs text-slate-500">Email / Mobile Verified</dt>
                            <dd class="text-xs font-semibold text-navy-900" x-text="(selectedIndividual?.citizen_account?.email_verified ? 'Email ✓' : 'Email ✗') + ' · ' + (selectedIndividual?.citizen_account?.mobile_verified ? 'Mobile ✓' : 'Mobile ✗')"></dd>
                        </div>
                        <div class="flex justify-between items-center gap-2" x-show="selectedIndividual?.citizen_account?.profile_completeness != null">
                            <dt class="text-xs text-slate-500">Profile Completeness</dt>
                            <dd class="text-xs font-bold" :class="completionTextColor(selectedIndividual?.citizen_account?.profile_completeness)" x-text="(selectedIndividual?.citizen_account?.profile_completeness || 0) + '%'"></dd>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <dt class="text-xs text-slate-500">Registered</dt>
                            <dd class="text-xs text-slate-600" x-text="selectedIndividual?.citizen_account?.registered_at ? new Date(selectedIndividual.citizen_account.registered_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'"></dd>
                        </div>
                    </dl>

                    {{-- The account is decided where its valid ID, selfie and photo
                         are: the account review. --}}
                    <div x-show="selectedIndividual?.citizen_account && (can('const_citizen_verification','view'))" class="pt-3 border-t border-slate-100">
                        <x-ui.button size="sm" variant="secondary" icon="scan-search" @click="openAccount(selectedIndividual)">Review account, ID and photo</x-ui.button>
                    </div>
                </div>

                {{-- The resident's submitted Resident Profile: verify it, or send it
                     back with what to change. The resident is told either way. --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-show="mayReviewProfile && selectedIndividual">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Resident Profile Review</h3>
                    <p class="text-xs leading-relaxed text-slate-600" x-show="selectedIndividual?.verification === 'For Validation'">Submitted for validation. Check the details on this record, then verify it or send it back with what to change.</p>
                    <p class="text-xs leading-relaxed text-emerald-700" x-show="selectedIndividual?.verification === 'Verified'" x-text="'Verified' + (selectedIndividual?.reviewed_by ? ' by ' + selectedIndividual.reviewed_by : '') + (selectedIndividual?.reviewed_at ? ' on ' + new Date(selectedIndividual.reviewed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '') + '.'"></p>
                    <p class="text-xs leading-relaxed text-amber-700 break-words" x-show="selectedIndividual?.review_note && selectedIndividual?.verification === 'Unverified'" x-text="'Sent back' + (selectedIndividual?.reviewed_by ? ' by ' + selectedIndividual.reviewed_by : '') + ', waiting for the resident: ' + selectedIndividual?.review_note"></p>
                    <div class="mt-3 space-y-2" x-show="selectedIndividual?.verification !== 'Verified'">
                        <x-ui.button size="sm" icon="shield-check" class="w-full justify-center" x-bind:disabled="profileReviewBusy" @click="verifyProfile(selectedIndividual)">Verify profile</x-ui.button>
                        <div class="space-y-2" x-show="selectedIndividual?.verification === 'For Validation'">
                            <textarea aria-label="What the resident should change" x-model="profileReturnNote" rows="2" maxlength="1000" placeholder="What should the resident change? (at least 5 characters)" class="w-full text-xs rounded-lg bg-slate-50 border border-slate-200 py-2 px-2.5 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition"></textarea>
                            <x-ui.button size="sm" variant="secondary" icon="undo-2" class="w-full justify-center" x-bind:disabled="profileReviewBusy || profileReturnNote.trim().length < 5" @click="returnProfile(selectedIndividual)">Send back for changes</x-ui.button>
                        </div>
                    </div>
                </div>

                {{-- Record actions --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-show="can('const_resident','delete')">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Record Actions</h3>
                    <div class="space-y-2">
                        <button type="button" class="w-full flex items-center gap-2.5 rounded-lg border border-slate-200 px-3 py-2.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors" @click="lopen=false; openLifecycle(selectedIndividual, 'Transfer'); showIndividualDetail=false">
                            <i data-lucide="arrow-right-from-line" class="w-3.5 h-3.5 text-indigo-500"></i>Transfer to Another Barangay
                        </button>
                        <button type="button" class="w-full flex items-center gap-2.5 rounded-lg border border-slate-200 px-3 py-2.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors" @click="lopen=false; openLifecycle(selectedIndividual, 'Deceased'); showIndividualDetail=false">
                            <i data-lucide="flower" class="w-3.5 h-3.5 text-slate-500"></i>Mark as Deceased
                        </button>
                        <button type="button" class="w-full flex items-center gap-2.5 rounded-lg border border-slate-200 px-3 py-2.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors" @click="lopen=false; openLifecycle(selectedIndividual, 'Archive'); showIndividualDetail=false">
                            <i data-lucide="archive" class="w-3.5 h-3.5 text-amber-500"></i>Archive Record
                        </button>
                    </div>
                </div>

            </div>
            </div>
            </template>

            {{-- ── EDIT FORM ── --}}
            <template x-if="editing">
                <div class="max-w-2xl space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1.5">Sex</label>
                            <select aria-label="Sex" x-model="edit.sex" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1.5">Civil Status</label>
                            <select aria-label="Civil Status" x-model="edit.civil_status" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                                <option value="Annulled">Annulled</option>
                                <option value="Live-in">Live-in</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1.5" for="ind-edit-contact">Contact Number</label>
                            <input id="ind-edit-contact" type="text" x-model="edit.contact" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-800 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1.5" for="ind-edit-sitio">Sitio / Purok</label>
                            <input id="ind-edit-sitio" type="text" x-model="edit.sitio" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-800 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1.5" for="ind-edit-occ">Occupation</label>
                            <input id="ind-edit-occ" type="text" x-model="edit.occupation" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-800 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1.5" for="ind-edit-edu">Education</label>
                            <input id="ind-edit-edu" type="text" x-model="edit.education" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-800 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1.5" for="ind-edit-relation">Relation</label>
                            <input id="ind-edit-relation" type="text" x-model="edit.relation" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-800 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-2">Vulnerable Sector Tags</label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="tag in ['senior','osca','pwd','youth','child','osy','4ps','solo_parent','farmer','high_risk']" :key="tag">
                                <button type="button" @click="toggleEditTag(tag)" class="text-xs font-medium rounded-full px-2.5 py-1 border transition-colors" :class="edit.tags.includes(tag) ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-white border-slate-200 text-slate-500'" x-text="tag"></button>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-ui.button variant="secondary" size="sm" @click="editing = false">Cancel</x-ui.button>
                        <x-ui.button size="sm" icon="check" x-bind:disabled="saving" @click="saveEdit()" x-text="saving ? 'Saving…' : 'Save Changes'"></x-ui.button>
                    </div>
                </div>
            </template>

        </div>

        {{-- ── Modal Footer ── --}}
        <div class="flex-shrink-0 px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3 rounded-b-2xl" x-show="!editing">
            <x-ui.button size="sm" icon="pencil" x-show="can('const_resident','edit')" @click="startEdit()">Edit Resident</x-ui.button>
        </div>

    </div>

    </x-ui.modal>
</div>
