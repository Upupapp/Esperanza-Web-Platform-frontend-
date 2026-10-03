<x-layouts.citizen title="My Profile" subtitle="Manage your resident information and account details." active="profile">
    <div
        x-data="{
            open: false,
            submitting: false,
            submitted: false,
            idType: '',
            idFile: null,
            idTypes: ['PhilSys National ID', 'Driver\'s License (LTO)', 'Voter\'s ID', 'Passport', 'PRC ID', 'SSS/GSIS ID', 'PWD ID', 'Senior Citizen ID'],
            reset() { this.open = false; this.submitted = false; this.idType = ''; this.idFile = null; },
            async submitId() {
                if (this.submitting || !this.idType || !this.idFile) return;
                this.submitting = true;
                try {
                    const form = new FormData();
                    form.append('file', this.idFile);
                    form.append('name', this.idType);
                    form.append('type', this.idType);
                    form.append('category', 'Identity / Verification');
                    await $api.post('/citizen/papeles', form);
                    this.submitted = true;
                    await this.loadDocuments();
                    setTimeout(() => { this.reset(); this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Valid ID submitted for verification.' : 'Naisumite ang valid ID para sa beripikasyon.', variant: 'success' }); }, 1400);
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised(this.$store.lang.current) : 'Could not submit ID.', variant: 'error' });
                } finally {
                    this.submitting = false;
                }
            },

            showDigitalId: false,

            familyMembers: [],
            residentProfileLoading: true,
            documents: [],
            documentsLoading: true,
            async loadDocuments() {
                this.documentsLoading = true;
                try {
                    const result = await $api.get('/citizen/papeles');
                    this.documents = result.data || [];
                } catch { this.documents = []; } finally { this.documentsLoading = false; }
            },
            async init() {
                this.residentProfileLoading = true;
                try {
                    const result = await $api.get('/citizen/resident-profile');
                    this.familyMembers = result.data?.family?.members || [];
                } catch { this.familyMembers = []; } finally { this.residentProfileLoading = false; }
                this.loadDocuments();
            },

            showEditProfile: false,
            savingProfile: false,
            editForm: { firstName: '', lastName: '', mobile: '', occupation: '', address: '' },
            openEditProfile() {
                const a = this.$store.citizenSession.account || {};
                this.editForm = { firstName: a.firstName || '', lastName: a.lastName || '', mobile: a.mobile || '', occupation: a.occupation || '', address: a.address || '' };
                this.showEditProfile = true;
            },
            async saveProfile() {
                if (this.savingProfile) return;
                this.savingProfile = true;
                try {
                    // Identity fields (first/middle/last name, suffix, birthdate,
                    // sex, barangay) are locked once the account is Verified (see
                    // identity_locked below) and PUT /citizen/profile 422s on
                    // them; occupation and address are always editable.
                    // Mobile is NOT accepted here at all -- a contact change is
                    // proven at the new number via POST /citizen/profile/contact
                    // + /verify, a flow this quick-edit modal does not implement
                    // (see PRODUCTION_READINESS.md).
                    const payload = { occupation: this.editForm.occupation, address: this.editForm.address };
                    if (!this.$store.citizenSession.account?.identityLocked) {
                        payload.first_name = this.editForm.firstName;
                        payload.last_name = this.editForm.lastName;
                    }
                    await $api.put('/citizen/profile', payload);
                    await this.$store.citizenSession.refresh();
                    this.showEditProfile = false;
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Profile updated.' : 'Na-update ang profile.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised(this.$store.lang.current) : 'Could not update profile.', variant: 'error' });
                } finally {
                    this.savingProfile = false;
                }
            },
        }"
        class="animate-fade-up"
    >

        <!-- Profile hero -->
        <div class="relative rounded-2xl overflow-hidden mb-5 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-700 shadow-card">
            <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top_right,theme(colors.white),transparent_55%)] pointer-events-none"></div>
            <div class="relative px-5 py-6 sm:px-7 sm:py-7 flex flex-col sm:flex-row sm:items-center gap-5">
                <div class="relative shrink-0">
                    <div class="w-20 h-20 text-2xl rounded-full bg-white/15 backdrop-blur border-2 border-white/40 text-white flex items-center justify-center font-semibold shadow-lg" x-text="$store.citizenSession.initials()"></div>
                    <span class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-emerald-500 border-2 border-navy-900 flex items-center justify-center">
                        <i data-lucide="badge-check" class="w-3.5 h-3.5 text-white"></i>
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold text-white" x-text="$store.citizenSession.account?.fullName || 'Resident'"></h2>
                        <span
                            class="inline-flex items-center gap-1 text-[11px] font-medium rounded-full px-2 py-0.5 border"
                            :class="$store.citizenSession.account?.fullServiceAccess ? 'text-emerald-100 bg-emerald-500/20 border-emerald-400/30' : 'text-amber-100 bg-amber-500/20 border-amber-400/30'"
                            x-text="$store.citizenSession.account?.fullServiceAccess ? ($store.lang.current === 'en' ? 'Verified Resident' : 'Verified na Residente') : ($store.citizenSession.account?.status || ($store.lang.current === 'en' ? 'Unverified' : 'Hindi Pa Verified'))"
                        ></span>
                    </div>
                    <p class="text-sm text-white/60 mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="flex items-center gap-1.5"><i data-lucide="hash" class="w-3.5 h-3.5"></i><span x-text="$store.citizenSession.account?.id"></span></span>
                        <span class="flex items-center gap-1.5"><i data-lucide="map-pinned" class="w-3.5 h-3.5"></i><span x-text="'Brgy. ' + ($store.citizenSession.account?.barangay || '—')"></span></span>
                        <span class="flex items-center gap-1.5"><i data-lucide="calendar" class="w-3.5 h-3.5"></i><span x-text="($store.lang.current === 'en' ? 'Resident since ' : 'Residente mula ') + ($store.citizenSession.account?.residentSince || '—')"></span></span>
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button
                        @click="open = true"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-white bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl px-3.5 py-2.5 transition-colors"
                    ><i data-lucide="shield-plus" class="w-3.5 h-3.5"></i> <x-ui.t fil="Mag-verify ng ID" en="Verify ID" /></button>
                    <button
                        @click="openEditProfile()"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-navy-900 bg-white hover:bg-slate-100 rounded-xl px-3.5 py-2.5 transition-colors shadow-sm"
                    ><i data-lucide="pencil" class="w-3.5 h-3.5"></i> <x-ui.t fil="I-edit" en="Edit" /></button>
                </div>
            </div>

            <!-- Profile completeness -->
            <div class="relative px-5 pb-5 sm:px-7 sm:pb-6">
                <div class="flex items-center justify-between mb-1.5">
                    <p class="text-xs font-medium text-white/70"><x-ui.t fil="Kumpletuhin ang Profile" en="Profile Completeness" /></p>
                    <p class="text-xs font-semibold text-white" x-text="($store.citizenSession.account?.profileCompleteness || 0) + '%'"></p>
                </div>
                <div class="h-1.5 rounded-full bg-white/15 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-300 transition-all duration-[600ms] ease-standard" :style="'width:' + ($store.citizenSession.account?.profileCompleteness || 0) + '%'"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Personal information -->
            <div class="lg:col-span-2 space-y-5">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4 flex items-center gap-2"><i data-lucide="user" class="w-4 h-4 text-slate-500"></i><x-ui.t fil="Personal na Impormasyon" en="Personal Information" /></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="signature" class="w-4 h-4 text-slate-300 shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500"><x-ui.t fil="Buong Pangalan" en="Full Name" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.fullName || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="cake" class="w-4 h-4 text-slate-300 shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500"><x-ui.t fil="Petsa ng Kapanganakan" en="Date of Birth" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.birthdate || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="venus-and-mars" class="w-4 h-4 text-slate-300 shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500"><x-ui.t fil="Kasarian" en="Sex" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.sex || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="heart" class="w-4 h-4 text-slate-300 shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500"><x-ui.t fil="Katayuang Sibil" en="Civil Status" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.civilStatus || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="flag" class="w-4 h-4 text-slate-300 shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500"><x-ui.t fil="Nasyonalidad" en="Nationality" /></p>
                                <p class="text-sm text-slate-700 font-medium"><x-ui.t fil="Pilipino" en="Filipino" /></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="briefcase" class="w-4 h-4 text-slate-300 shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500"><x-ui.t fil="Trabaho" en="Occupation" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.occupation || '—'"></p>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4 flex items-center gap-2"><i data-lucide="contact" class="w-4 h-4 text-slate-500"></i><x-ui.t fil="Contact at Address" en="Contact & Address" /></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="mail" class="w-4 h-4 text-slate-300 shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500"><x-ui.t fil="Email Address" en="Email Address" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.email || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="phone" class="w-4 h-4 text-slate-300 shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500"><x-ui.t fil="Numero ng Mobile" en="Mobile Number" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.mobile || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3 sm:col-span-2">
                            <i data-lucide="map-pin" class="w-4 h-4 text-slate-300 shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500"><x-ui.t fil="Kumpletong Address" en="Complete Address" /></p>
                                <p class="text-sm text-slate-700 font-medium" x-text="$store.citizenSession.account?.address || '—'"></p>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-5">
                {{-- Digital ID — a wallet for already-issued official credentials,
                     a different concept from the "Verify ID" flow above (which
                     submits evidence for LGU review). Backed by GET /citizen/digital-id
                     (App\Domain\Identity\DigitalId), available once the account
                     has full service access. --}}
                <button
                    type="button"
                    x-show="$store.citizenSession.account?.fullServiceAccess" x-cloak
                    @click="showDigitalId = true"
                    class="w-full text-left rounded-2xl bg-gradient-to-br from-navy-950 via-navy-900 to-brand-900 border border-navy-800 shadow-card p-4 flex items-center gap-3 hover:shadow-card-hover transition-all duration-[220ms] ease-standard group"
                >
                    <div class="w-11 h-11 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform duration-[220ms] ease-standard">
                        <i data-lucide="badge-check" class="w-5 h-5 text-gold-300"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-white">Digital ID</p>
                        <p class="text-[11px] text-white/50">Your Barangay Resident ID, always with you</p>
                    </div>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-white/40 shrink-0"></i>
                </button>

                <!-- My Barangay -->
                <div x-show="$store.barangay.mine" x-cloak class="rounded-2xl bg-gradient-to-br from-brand-50 via-white to-purple-50/60 border border-brand-100 shadow-card p-4">
                    <p class="text-[11px] font-semibold text-brand-600 uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="landmark" class="w-3.5 h-3.5"></i><x-ui.t fil="Aking Barangay" en="My Barangay" /></p>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="relative w-11 h-11 shrink-0">
                            <div class="absolute inset-0 rounded-full bg-white border-2 border-gold-400 flex items-center justify-center text-slate-300"><i data-lucide="landmark" class="w-5 h-5"></i></div>
                            <img
                                :src="$store.barangay.mine?.seal ? '{{ rtrim(asset(''), '/') }}/' + $store.barangay.mine.seal : ''"
                                x-show="$store.barangay.mine?.seal" onerror="this.style.display='none'" alt=""
                                class="relative w-11 h-11 rounded-full object-cover border-2 border-gold-400"
                            >
                        </div>
                        <h3 class="text-sm font-semibold text-navy-900 truncate"><x-ui.t fil="Barangay" en="Barangay" /> <span x-text="$store.barangay.mine?.label"></span></h3>
                    </div>
                    <div class="space-y-1.5 text-xs border-t border-brand-100/70 pt-3">
                        <div class="flex items-center justify-between gap-2"><span class="text-slate-500 shrink-0"><x-ui.t fil="Punong Barangay" en="Punong Barangay" /></span><span class="font-medium text-slate-700 text-right truncate" x-text="$store.barangay.mine?.officials?.punong_barangay"></span></div>
                        <div class="flex items-center justify-between gap-2"><span class="text-slate-500 shrink-0"><x-ui.t fil="Kalihim" en="Secretary" /></span><span class="font-medium text-slate-700 text-right truncate" x-text="$store.barangay.mine?.officials?.barangay_secretary"></span></div>
                        <div class="flex items-center justify-between gap-2" x-show="$store.barangay.mine?.contact"><span class="text-slate-500 shrink-0"><x-ui.t fil="Contact" en="Contact" /></span><a class="font-medium text-brand-600 hover:underline text-right truncate" :href="'tel:' + ($store.barangay.mine?.contact || '').replace(/[^0-9+]/g, '')" x-text="$store.barangay.mine?.contact"></a></div>
                    </div>
                </div>

                <!-- Household -->
                <x-ui.card padded="false">
                    <div class="px-4 pt-3.5 pb-2.5 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-navy-900 flex items-center gap-2"><i data-lucide="users" class="w-4 h-4 text-slate-500"></i><x-ui.t fil="Kasapi ng Pamilya" en="Family Members" /></h3>
                        <span class="text-[11px] font-medium text-slate-500" x-text="familyMembers.length"></span>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <template x-for="member in familyMembers" :key="member.ref">
                            <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50/60 transition-colors">
                                <div class="w-8 h-8 text-xs rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold shrink-0" x-text="(member.name || '').split(/\s+/).filter(Boolean).map((w) => w[0]).slice(0, 2).join('')"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-700 truncate" x-text="member.name"></p>
                                    <p class="text-xs text-slate-500" x-text="member.dob ? (new Date().getFullYear() - new Date(member.dob).getFullYear()) + ' ' + ($store.lang.current === 'en' ? 'y/o' : 'taong gulang') : ''"></p>
                                </div>
                                <span class="text-[10px] font-medium text-slate-500 bg-slate-100 rounded-full px-2 py-0.5 shrink-0" x-text="member.relation"></span>
                            </div>
                        </template>
                        <p class="px-4 py-6 text-center text-xs text-slate-500" x-show="!residentProfileLoading && !familyMembers.length">
                            <x-ui.t fil="Wala pang resident profile o pamilya sa file." en="No resident profile or family on file yet." />
                        </p>
                    </div>
                </x-ui.card>

                <!-- Documents on file -->
                <x-ui.card padded="false">
                    <div class="px-4 pt-3.5 pb-2.5">
                        <h3 class="text-sm font-semibold text-navy-900 flex items-center gap-2"><i data-lucide="folder-check" class="w-4 h-4 text-slate-500"></i><x-ui.t fil="Mga Dokumento sa File" en="Documents on File" /></h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <template x-for="doc in documents" :key="doc.id">
                            <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50/60 transition-colors">
                                <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                </span>
                                <p class="text-sm text-slate-700 min-w-0 truncate flex-1" x-text="doc.name"></p>
                                <x-ui.badge status-bind="doc.status" class="shrink-0" />
                            </div>
                        </template>
                        <p class="px-4 py-6 text-center text-xs text-slate-500" x-show="!documentsLoading && !documents.length">
                            <x-ui.t fil="Wala pang dokumento sa file." en="No documents on file yet." />
                        </p>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-ui.modal title="I-verify ang Valid ID" titleEn="Verify Valid ID" maxWidth="md">
            <!-- Success state -->
            <div x-show="submitted" x-cloak x-transition.opacity class="flex flex-col items-center justify-center text-center py-10">
                <div class="relative flex items-center justify-center mb-4 w-16 h-16">
                    <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse"></span>
                    <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse" style="animation-delay: 0.35s;"></span>
                    <span class="relative w-14 h-14 rounded-full bg-emerald-500 text-white flex items-center justify-center animate-check-pop">
                        <i data-lucide="check" class="w-7 h-7"></i>
                    </span>
                </div>
                <p class="text-sm font-semibold text-navy-900">
                    <x-ui.t fil="Naisumite para sa Beripikasyon!" en="Submitted for Verification!" />
                    <span class="inline-block animate-check-pop" style="animation-delay: 0.3s;">🙂</span>
                </p>
                <p class="text-xs text-slate-500 mt-1 max-w-[240px]"><x-ui.t fil="Susuriin ng aming staff ang iyong ID at ia-update ang iyong profile sa lalong madaling panahon." en="Our staff will review your ID and update your profile shortly." /></p>
            </div>

            <div x-show="!submitted">
                <p class="text-sm text-slate-500 mb-4"><x-ui.t fil="Magsumite ng valid ID para ma-verify ng aming staff ang iyong resident profile." en="Submit a valid ID so our staff can verify your resident profile." /></p>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Uri ng ID" en="ID Type" /></label>
                    <select aria-label="Id type" x-model="idType" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        <option value="" disabled x-text="$store.lang.current === 'en' ? 'Select ID type' : 'Pumili ng uri ng ID'"></option>
                        <template x-for="t in idTypes" :key="t">
                            <option :value="t" x-text="t"></option>
                        </template>
                    </select>
                </div>

                <p class="text-sm font-semibold text-navy-900 mb-1"><x-ui.t fil="Ikabit ang Larawan ng ID" en="Attach ID Photo" /></p>
                <p class="text-xs text-slate-500 mb-3"><x-ui.t fil="Malinaw na larawan o scan ng harap ng iyong ID." en="A clear photo or scan of the front of your ID." /></p>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-600 flex-1" x-text="idFile ? idFile.name : ($store.lang.current === 'en' ? 'No file chosen' : 'Walang napiling file')"></p>
                        <label class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap cursor-pointer">
                            <x-ui.t fil="Pumili ng File" en="Choose File" />
                            <input type="file" accept="image/*,.pdf" class="sr-only" @change="idFile = $event.target.files[0] || null">
                        </label>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" x-show="!submitted" x-bind:disabled="submitting" @click="reset()"><x-ui.t fil="Kanselahin" en="Cancel" /></x-ui.button>
                <x-ui.button
                    size="sm"
                    x-show="!submitted"
                    ::disabled="!idType || !idFile || submitting"
                    @click="submitId()"
                >
                    <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="submitting" x-cloak></i>
                    <x-ui.t fil="Isumite para sa Beripikasyon" en="Submit for Verification" />
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        <div x-data="{ get open() { return showDigitalId }, set open(v) { showDigitalId = v } }">
            <x-ui.modal title="Digital ID" titleEn="Digital ID" maxWidth="md">
                <x-citizen.digital-id-card />
            </x-ui.modal>
        </div>

        <div x-data="{ get open() { return showEditProfile }, set open(v) { showEditProfile = v } }">
            <x-ui.modal title="I-edit ang Profile" titleEn="Edit Profile" maxWidth="md" :fullscreen="true">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input name="edit_first_name" label="First Name" x-model="editForm.firstName" />
                        <x-ui.input name="edit_last_name" label="Last Name" x-model="editForm.lastName" />
                    </div>
                    <x-ui.input name="edit_mobile" label="Mobile Number" icon="phone" x-model="editForm.mobile" />
                    <x-ui.input name="edit_occupation" label="Occupation" icon="briefcase" x-model="editForm.occupation" />
                    <x-ui.input name="edit_address" label="Complete Address" icon="house" x-model="editForm.address" />
                    <p class="text-xs text-slate-500"><x-ui.t fil="Ang barangay at Resident ID ay hindi maaaring baguhin dito — makipag-ugnayan sa iyong Barangay Hall." en="Barangay and Resident ID can't be changed here — contact your Barangay Hall for those." /></p>
                </div>

                <x-slot:footer>
                    <x-ui.button variant="secondary" size="sm" x-bind:disabled="savingProfile" @click="showEditProfile = false"><x-ui.t fil="Kanselahin" en="Cancel" /></x-ui.button>
                    <x-ui.button size="sm" x-bind:disabled="savingProfile" @click="saveProfile()">
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="savingProfile" x-cloak></i>
                            <span x-text="savingProfile ? ($store.lang.current === 'en' ? 'Saving…' : 'Sine-save…') : ($store.lang.current === 'en' ? 'Save Changes' : 'I-save ang Pagbabago')"></span>
                        </span>
                    </x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>
    </div>
</x-layouts.citizen>
