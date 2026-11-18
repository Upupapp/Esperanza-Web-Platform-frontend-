<x-layouts.citizen title="My Profile" subtitle="Manage your resident information and account details." active="profile">
    <div
        x-data="{
            open: false,
            submitting: false,
            submitted: false,
            idType: '',
            idFile: null,
            idBack: null,
            idSelfie: null,
            // GET /citizen/verification/id: the accepted ID types (the server's
            // list, never typed here), whether one may be sent, and the latest
            // submission with its review status. The ID used to go to Papeles,
            // which no officer reads when verifying an account.
            // `identity` (2026-10-09): every ID sent is checked automatically -
            // the document, the selfie against the ID's photo, and that the
            // selfie is live - and an officer can verify or revoke it. It is
            // the IDENTITY result only; residency stays the officer's decision
            // (the account status). Its six values are their own vocabulary.
            idView: { id_types: [], can_submit: false, submission: null, identity: { status: null, reasons: [], awaiting_check: false }, capture: 'own', session: null },
            launchingDidit: false,
            checking: false,
            selfieError: '',
            async loadIdView() {
                try { this.idView = (await $api.get('/citizen/verification/id')).data || this.idView; } catch { /* keeps the last view */ }
            },
            // Sending again is offered where the server takes it and the last
            // ID is not waiting for an officer - unless its check failed or was
            // revoked, which only new photos can fix.
            get canResend() {
                const s = this.idView.identity?.status;
                return this.idView.can_submit && (this.idView.submission?.status !== 'Pending Review' || s === 'Failed' || s === 'Revoked');
            },
            identityPill(s) {
                return {
                    'Pending': 'text-slate-100 bg-white/10 border-white/20',
                    'In Progress': 'text-sky-100 bg-sky-500/20 border-sky-400/30',
                    'Verified': 'text-emerald-100 bg-emerald-500/20 border-emerald-400/30',
                    'Failed': 'text-rose-100 bg-rose-500/25 border-rose-400/40',
                    'Requires Review': 'text-amber-100 bg-amber-500/20 border-amber-400/30',
                    'Revoked': 'text-rose-100 bg-rose-500/25 border-rose-400/40',
                }[s] || 'text-slate-100 bg-white/10 border-white/20';
            },
            identityText(s) {
                const en = this.$store.lang.current === 'en';
                return {
                    'Pending': en ? 'Your ID and selfie are waiting to be checked. This usually takes a few minutes.' : 'Naghihintay masuri ang iyong ID at selfie. Karaniwang ilang minuto lamang ito.',
                    'In Progress': en ? 'Your ID and selfie are being checked now.' : 'Sinusuri na ngayon ang iyong ID at selfie.',
                    'Verified': en ? 'Your identity is confirmed. Your residency is reviewed separately by the LGU.' : 'Kumpirmado ang iyong pagkakakilanlan. Hiwalay na sinusuri ng LGU ang iyong paninirahan.',
                    'Failed': en ? 'We could not confirm your identity from the photos you sent. Please send a new ID photo and selfie.' : 'Hindi namin makumpirma ang iyong pagkakakilanlan mula sa mga ipinadalang litrato. Magpadala ng bagong litrato ng ID at selfie.',
                    'Requires Review': en ? 'LGU staff will review your ID and selfie themselves. No action needed from you right now.' : 'Susuriin ng staff ng LGU ang iyong ID at selfie. Wala ka pang kailangang gawin.',
                    'Revoked': en ? 'The LGU withdrew the verification of your identity. Send a new ID and selfie, or visit your barangay office. Requests are paused until then.' : 'Binawi ng LGU ang beripikasyon ng iyong pagkakakilanlan. Magpadala ng bagong ID at selfie, o bumisita sa inyong barangay. Nakahinto muna ang mga request.',
                }[s] || '';
            },
            reasonText(code) {
                const en = this.$store.lang.current === 'en';
                return {
                    FACE_MISMATCH: en ? 'Your selfie did not match the photo on your ID.' : 'Hindi tugma ang iyong selfie sa litrato sa iyong ID.',
                    LIVENESS_FAILED: en ? 'Take the selfie live with the camera, not a photo of a photo or a screen.' : 'Kunan ang selfie nang live sa camera, hindi litrato ng litrato o ng screen.',
                    SELFIE_UNUSABLE: en ? 'We could not see your face clearly in the selfie.' : 'Hindi malinaw ang iyong mukha sa selfie.',
                    DOCUMENT_NOT_GENUINE: en ? 'Photograph the ID card itself, not a screen, a photocopy or a printout.' : 'Kunan ng litrato ang mismong ID, hindi ang screen, photocopy o printout.',
                    DOCUMENT_EXPIRED: en ? 'The ID has expired. Please use a valid, unexpired ID.' : 'Expired na ang ID. Gumamit ng valid at hindi pa expired na ID.',
                    // The ID never overwrites the account: a difference goes to an officer (2026-10-16).
                    NAME_MISMATCH: en ? 'The name on your ID is different from the name on your account. Your barangay office will compare them.' : 'Iba ang pangalan sa iyong ID kaysa sa pangalan sa iyong account. Ihahambing ito ng iyong barangay.',
                    BIRTHDATE_MISMATCH: en ? 'The birth date on your ID is different from the one on your account. Your barangay office will compare them.' : 'Iba ang kaarawan sa iyong ID kaysa sa nasa iyong account. Ihahambing ito ng iyong barangay.',
                    SEX_MISMATCH: en ? 'The sex on your ID is different from the one on your account. Your barangay office will compare them.' : 'Iba ang kasarian sa iyong ID kaysa sa nasa iyong account. Ihahambing ito ng iyong barangay.',
                }[code] || null;
            },
            // The check runs right after the upload is answered: poll until
            // it settles (seconds when the service is up), then refresh the
            // session so access reflects it. Gives up quietly - it stays
            // Pending and is checked shortly; nothing is lost.
            async awaitCheck() {
                if (!this.idView.identity?.awaiting_check) return;
                this.checking = true;
                try {
                    for (let i = 0; i < 12; i++) {
                        await new Promise((r) => setTimeout(r, 2000));
                        try { this.idView = (await $api.get('/citizen/verification/id')).data || this.idView; } catch { /* try again */ }
                        if (!this.idView.identity?.awaiting_check) break;
                    }
                    try { await this.$store.citizenSession.refresh(); } catch { /* the next page load refreshes it */ }
                } finally { this.checking = false; }
            },
            // Live camera only (owner, 2026-10-09): the liveness check must
            // see a capture taken now, so there is no file fallback.
            async captureSelfie() {
                const en = this.$store.lang.current === 'en';
                const file = await window.takeSelfie({ title: en ? 'Take a selfie' : 'Mag-selfie', capture: en ? 'Capture' : 'Kunan', cancel: en ? 'Cancel' : 'Kanselahin' });
                if (file === undefined) {
                    this.selfieError = en
                        ? 'A camera is needed to take your selfie. Allow camera access in your browser, or use a phone or computer with a camera.'
                        : 'Kailangan ng camera para sa iyong selfie. Payagan ang camera sa iyong browser, o gumamit ng telepono o computer na may camera.';
                    return;
                }
                if (file) { this.idSelfie = file; this.selfieError = ''; }
            },
            // identity_capture = hosted (2026-10-09): the ID scan and the face
            // check run on Didit's page. The portal only opens it; the result is
            // read from Didit by the server when the resident is back.
            async startDidit() {
                if (this.launchingDidit) return;
                this.launchingDidit = true;
                try {
                    const r = await $api.post('/citizen/verification/session', { platform: 'web' });
                    if (r.data?.url) { window.location.href = r.data.url; return; }
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Verification could not be opened. Please try again.' : 'Hindi mabuksan ang beripikasyon. Pakisubukang muli.', variant: 'error' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised(this.$store.lang.current) : 'Could not start verification.', variant: 'error' });
                } finally { this.launchingDidit = false; }
            },
            // Back from Didit's page (?verification=done): have the server read
            // the result of the open session, then wait for it to settle.
            async resumeFromDidit() {
                const id = this.idView.session?.id;
                if (!id) return;
                try { this.idView = (await $api.post('/citizen/verification/session/' + encodeURIComponent(id) + '/sync')).data || this.idView; } catch { /* the poll below retries */ }
                await this.awaitCheck();
                history.replaceState(null, '', window.location.pathname);
            },
            reset() { this.open = false; this.submitted = false; this.idType = ''; this.idFile = null; this.idBack = null; this.idSelfie = null; this.selfieError = ''; },
            async submitId() {
                if (this.submitting || !this.idType || !this.idFile || !this.idSelfie) return;
                this.submitting = true;
                try {
                    const form = new FormData();
                    form.append('id_type', this.idType);
                    form.append('front', this.idFile);
                    if (this.idBack) form.append('back', this.idBack);
                    if (this.idSelfie) form.append('selfie', this.idSelfie);
                    const result = await $api.post('/citizen/verification/id', form);
                    this.idView = result.data || this.idView;
                    this.submitted = true;
                    setTimeout(() => { this.reset(); this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Valid ID sent. Checking your ID and selfie now.' : 'Naipadala ang valid ID. Sinusuri na ang iyong ID at selfie.', variant: 'success' }); }, 1400);
                    this.awaitCheck();
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised(this.$store.lang.current) : 'Could not submit ID.', variant: 'error' });
                } finally {
                    this.submitting = false;
                }
            },

            showDigitalId: false,

            // The profile photo is an identification record: a new one waits
            // for an officer, and the Digital ID keeps the approved one.
            photo: { url: null, latest: null, can_change: true, next_change_at: null, change_months: 6 },
            uploadingPhoto: false,
            async loadPhoto() {
                try { this.photo = (await $api.get('/citizen/profile')).data?.photo || this.photo; } catch { /* keeps the last view */ }
            },
            async uploadPhoto(file) {
                if (!file || this.uploadingPhoto) return;
                this.uploadingPhoto = true;
                try {
                    const form = new FormData();
                    form.append('photo', file);
                    this.photo = (await $api.post('/citizen/profile/photo', form)).data || this.photo;
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Photo sent. It appears on your Digital ID once the LGU approves it.' : 'Naipadala ang litrato. Lalabas ito sa iyong Digital ID kapag inaprubahan ng LGU.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised(this.$store.lang.current) : 'Could not send the photo.', variant: 'error' });
                } finally { this.uploadingPhoto = false; }
            },
            formatDate(iso) { return iso ? new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : ''; },

            familyMembers: [],
            // The Resident Profile filed on the phone: the barangay's decision.
            profileVerification: null,
            profileReview: null,
            residentProfileView: null,
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
            async loadResidentProfile() {
                this.residentProfileLoading = true;
                try {
                    const result = await $api.get('/citizen/resident-profile');
                    this.residentProfileView = result.data || null;
                    this.familyMembers = result.data?.family?.members || [];
                    this.profileVerification = result.data?.individual?.verification || null;
                    this.profileReview = result.data?.review || null;
                } catch { this.familyMembers = []; } finally { this.residentProfileLoading = false; }
            },
            async init() {
                await this.loadResidentProfile();
                this.loadDocuments();
                const back = new URLSearchParams(window.location.search).has('verification');
                // Back from scanning a wallet ID on Didit's page: the wallet
                // reads the result when it opens.
                if (new URLSearchParams(window.location.search).has('wallet_check')) this.showDigitalId = true;
                await this.loadIdView();
                if (back && this.idView.capture === 'hosted') this.resumeFromDidit();
                this.loadPhoto();
            },

            showEditProfile: false,
            savingProfile: false,
            editForm: { firstName: '', lastName: '', mobile: '', occupation: '', livelihood_code: '', livelihood_other: '', address: '', barangay: '' },
            // Until the account is verified the resident can still correct
            // their barangay, as in the app (parity check, 2026-10-23); then it
            // is locked like the name (IDENTITY_LOCKED on the server).
            barangays: [],
            // A new mobile number is proven with a one-time code before it lands
            // (POST /citizen/profile/contact, then /verify). The field used to be
            // editable and its value silently dropped (Esperanza QA, 2026-10-07).
            mobileStep: null,
            mobileCode: '',
            mobileSentTo: '',
            originalMobile: '',
            // A verified account's name is locked (the server refuses it): the
            // window shows it locked, with a name change request - reason and
            // proof - in its place (owner, 2026-10-15 and 2026-10-16; the
            // nameChangeRequest component in scope-citizen.js). Staff apply it
            // from Constituents > To Verify > Data requests.
            openEditProfile() {
                const a = this.$store.citizenSession.account || {};
                this.originalMobile = window.phMobile.clean(a.mobile || '');
                this.editForm = { firstName: a.firstName || '', lastName: a.lastName || '', mobile: this.originalMobile, occupation: a.occupation || '', livelihood_code: a.livelihood?.code || '', livelihood_other: a.livelihood?.other || '', address: a.address || '', barangay: a.barangay || '' };
                this.mobileStep = null;
                this.mobileCode = '';
                this.showEditProfile = true;
                if (!a.identityLocked && !this.barangays.length) {
                    $api.get('/barangays').then((res) => { this.barangays = (res.data || []).map((b) => b.name); }).catch(() => {});
                }
            },
            get mobileChanged() { return this.editForm.mobile !== '' && this.editForm.mobile !== this.originalMobile; },
            async saveProfile() {
                if (this.savingProfile) return;
                const lang = this.$store.lang.current;
                const mobileError = this.mobileChanged ? window.phMobile.error(this.editForm.mobile, false, lang) : '';
                if (mobileError) {
                    this.$dispatch('toast', { message: mobileError, variant: 'error' });
                    return;
                }
                this.savingProfile = true;
                try {
                    // Identity fields (first/middle/last name, suffix, birthdate,
                    // sex, barangay) are locked once the account is Verified (see
                    // identity_locked below) and PUT /citizen/profile 422s on
                    // them; occupation and address are always editable.
                    // Mobile is not part of PUT /citizen/profile: a changed number
                    // goes through the code step below instead.
                    const a = this.$store.citizenSession.account || {};
                    const payload = {};
                    // The occupation is the two DRRM dropdowns (owner, 2026-10-29),
                    // sent only when the resident changed them.
                    if ((this.editForm.livelihood_code || '') !== (a.livelihood?.code || '') || (this.editForm.livelihood_other || '') !== (a.livelihood?.other || '')) {
                        payload.livelihood_code = this.editForm.livelihood_code || null;
                        payload.livelihood_other = this.editForm.livelihood_other || null;
                        if (!this.editForm.livelihood_code) payload.occupation = this.editForm.occupation || null;
                    }
                    if ((this.editForm.address || '') !== (a.address || '')) payload.address = this.editForm.address;
                    if (!a.identityLocked) {
                        if (this.editForm.firstName !== (a.firstName || '')) payload.first_name = this.editForm.firstName;
                        if (this.editForm.lastName !== (a.lastName || '')) payload.last_name = this.editForm.lastName;
                        if (this.editForm.barangay && this.editForm.barangay !== (a.barangay || '')) payload.barangay = this.editForm.barangay;
                    }
                    if (!Object.keys(payload).length && !this.mobileChanged) {
                        this.showEditProfile = false;
                        this.$dispatch('toast', { message: lang === 'en' ? 'Nothing was changed.' : 'Walang binago.', variant: 'warning' });
                        return;
                    }
                    if (Object.keys(payload).length) await $api.put('/citizen/profile', payload);
                    await this.$store.citizenSession.refresh();
                    if (this.mobileChanged) {
                        const res = await $api.post('/citizen/profile/contact', { channel: 'mobile', value: this.editForm.mobile });
                        this.mobileSentTo = res.data?.destination || '';
                        this.mobileStep = 'code';
                        this.$dispatch('toast', { message: lang === 'en' ? 'Profile saved. Enter the code we sent to confirm your new mobile number.' : 'Na-save ang profile. Ilagay ang code na ipinadala para kumpirmahin ang bagong mobile number.', variant: 'success' });
                        return;
                    }
                    this.showEditProfile = false;
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Profile updated.' : 'Na-update ang profile.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised(this.$store.lang.current) : 'Could not update profile.', variant: 'error' });
                } finally {
                    this.savingProfile = false;
                }
            },
            async verifyMobile() {
                if (this.savingProfile) return;
                const lang = this.$store.lang.current;
                if (!/^[0-9]{6}$/.test(this.mobileCode)) {
                    this.$dispatch('toast', { message: lang === 'en' ? 'Enter the 6-digit code.' : 'Ilagay ang 6-digit na code.', variant: 'error' });
                    return;
                }
                this.savingProfile = true;
                try {
                    await $api.post('/citizen/profile/contact/verify', { code: this.mobileCode });
                    await this.$store.citizenSession.refresh();
                    this.showEditProfile = false;
                    this.mobileStep = null;
                    this.$dispatch('toast', { message: lang === 'en' ? 'Mobile number updated.' : 'Na-update ang mobile number.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised(lang) : 'Could not confirm the code.', variant: 'error' });
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
                {{-- As wide as the photo: on a phone the column stretched it across
                     the card and the camera and check sat at the far edge (2026-11-18). --}}
                <div class="relative shrink-0 w-20">
                    <img x-show="photo.url" :src="photo.url" alt="Profile photo" class="w-20 h-20 rounded-full object-cover border-2 border-white/40 shadow-lg">
                    <div x-show="!photo.url" class="w-20 h-20 text-2xl rounded-full bg-white/15 backdrop-blur border-2 border-white/40 text-white flex items-center justify-center font-semibold shadow-lg" x-text="$store.citizenSession.initials()"></div>
                    <span x-show="$store.citizenSession.account?.fullServiceAccess" class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-emerald-500 border-2 border-navy-900 flex items-center justify-center">
                        <i data-lucide="badge-check" class="w-3.5 h-3.5 text-white"></i>
                    </span>
                    <label x-show="photo.can_change" class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-white text-ink border-2 border-navy-900 flex items-center justify-center cursor-pointer hover:bg-slate-100"
                           :title="$store.lang.current === 'en' ? 'Change photo' : 'Palitan ang litrato'">
                        <i data-lucide="camera" class="w-3.5 h-3.5" x-show="!uploadingPhoto"></i>
                        <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="uploadingPhoto" x-cloak></i>
                        <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" :aria-label="$store.lang.current === 'en' ? 'Change profile photo' : 'Palitan ang profile photo'"
                               @change="uploadPhoto($event.target.files[0]); $event.target.value = ''">
                    </label>
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
                    <p class="text-xs text-amber-100 mt-1.5" x-show="photo.latest?.status === 'Pending Review'" x-cloak
                       x-text="$store.lang.current === 'en' ? 'Your new photo is waiting for LGU approval.' : 'Naghihintay ng pag-apruba ng LGU ang iyong bagong litrato.'"></p>
                    <p class="text-xs text-rose-100 mt-1.5" x-show="photo.latest?.status === 'Rejected'" x-cloak
                       x-text="($store.lang.current === 'en' ? 'Your new photo was not approved: ' : 'Hindi naaprubahan ang bagong litrato: ') + (photo.latest?.remarks || '')"></p>
                    <p class="text-xs text-white/60 mt-1.5" x-show="!photo.can_change && photo.next_change_at" x-cloak
                       x-text="($store.lang.current === 'en' ? 'You can change your photo again on ' : 'Maaari mong palitan muli ang litrato sa ') + formatDate(photo.next_change_at)"></p>
                    {{-- Identity, apart from residency (2026-10-09). The six
                         names are a fixed vocabulary, shown as they are. --}}
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-white/80" x-show="idView.identity?.status" x-cloak>
                        <span><x-ui.t fil="Pagkakakilanlan:" en="Identity:" /></span>
                        <span class="inline-flex items-center gap-1 font-semibold rounded-full px-2 py-0.5 border" :class="identityPill(idView.identity?.status)" x-text="idView.identity?.status"></span>
                        <span x-show="checking" x-cloak class="inline-flex items-center gap-1 text-white/70"><i data-lucide="loader-circle" class="w-3 h-3 animate-spin"></i> <x-ui.t fil="Sinusuri..." en="Checking..." /></span>
                    </div>
                    <p class="text-xs text-white/75 mt-1 max-w-xl" x-show="['Failed', 'Revoked', 'Requires Review'].includes(idView.identity?.status)" x-cloak x-text="identityText(idView.identity?.status)"></p>
                    <template x-for="r in (idView.identity?.reasons || [])" :key="r">
                        <p class="text-xs text-rose-100 mt-0.5" x-show="reasonText(r)" x-text="'• ' + reasonText(r)"></p>
                    </template>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <span x-show="idView.submission?.status === 'Pending Review' && !canResend" x-cloak
                          class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-100 bg-amber-500/20 border border-amber-400/30 rounded-xl px-3.5 py-2.5">
                        {{-- Once the identity is verified, what waits is the LGU's
                             residency decision, not the ID (2026-10-09). --}}
                        <i data-lucide="hourglass" class="w-3.5 h-3.5"></i> <span x-text="idView.identity?.status === 'Verified' ? ($store.lang.current === 'en' ? 'Awaiting LGU approval' : 'Hinihintay ang pag-apruba ng LGU') : ($store.lang.current === 'en' ? 'ID under review' : 'Sinusuri ang ID')"></span>
                    </span>
                    <button
                        x-show="canResend" x-cloak
                        @click="idView.capture === 'hosted' ? startDidit() : (open = true)"
                        :disabled="launchingDidit"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-white bg-white/10 hover:bg-white/20 border border-white/20 rounded-2xl px-3.5 py-2.5 transition-colors"
                    ><i data-lucide="shield-plus" class="w-3.5 h-3.5"></i> <span x-text="(idView.submission?.status === 'Rejected' || ['Failed', 'Revoked'].includes(idView.identity?.status)) ? ($store.lang.current === 'en' ? 'Send a new ID' : 'Magpadala ng bagong ID') : ($store.lang.current === 'en' ? 'Verify ID' : 'Mag-verify ng ID')"></span></button>
                    <button
                        @click="openEditProfile()"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-ink bg-white hover:bg-slate-100 rounded-2xl px-3.5 py-2.5 transition-colors shadow-sm"
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
                    <h3 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2"><i data-lucide="user" class="w-4 h-4 text-ink-muted"></i><x-ui.t fil="Personal na Impormasyon" en="Personal Information" /></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="signature" class="w-4 h-4 text-ink-faint shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-ink-muted"><x-ui.t fil="Buong Pangalan" en="Full Name" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.fullName || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="cake" class="w-4 h-4 text-ink-faint shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-ink-muted"><x-ui.t fil="Petsa ng Kapanganakan" en="Date of Birth" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.birthdate || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="venus-and-mars" class="w-4 h-4 text-ink-faint shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-ink-muted"><x-ui.t fil="Kasarian" en="Sex" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.sex || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="heart" class="w-4 h-4 text-ink-faint shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-ink-muted"><x-ui.t fil="Katayuang Sibil" en="Civil Status" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.civilStatus || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="flag" class="w-4 h-4 text-ink-faint shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-ink-muted"><x-ui.t fil="Nasyonalidad" en="Nationality" /></p>
                                <p class="text-sm text-slate-700 font-medium"><x-ui.t fil="Pilipino" en="Filipino" /></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="briefcase" class="w-4 h-4 text-ink-faint shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-ink-muted"><x-ui.t fil="Trabaho" en="Occupation" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.occupation || '—'"></p>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2"><i data-lucide="contact" class="w-4 h-4 text-ink-muted"></i><x-ui.t fil="Contact at Address" en="Contact & Address" /></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="mail" class="w-4 h-4 text-ink-faint shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-ink-muted"><x-ui.t fil="Email Address" en="Email Address" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.email || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3">
                            <i data-lucide="phone" class="w-4 h-4 text-ink-faint shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-ink-muted"><x-ui.t fil="Numero ng Mobile" en="Mobile Number" /></p>
                                <p class="text-sm text-slate-700 font-medium truncate" x-text="$store.citizenSession.account?.mobile || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50/70 p-3 sm:col-span-2">
                            <i data-lucide="map-pin" class="w-4 h-4 text-ink-faint shrink-0 mt-0.5"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-ink-muted"><x-ui.t fil="Kumpletong Address" en="Complete Address" /></p>
                                <p class="text-sm text-slate-700 font-medium" x-text="$store.citizenSession.account?.address || '—'"></p>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-5">
                {{-- Digital ID — the Unified Digital ID Wallet (2026-10-13), a
                     different concept from the "Verify ID" flow above (which
                     submits evidence for LGU review). Backed by GET /citizen/wallet:
                     the Resident ID once it is issued, each status on its own, and
                     the government IDs the resident keeps for reference. Open to
                     every signed-in resident; the Resident ID inside it is not. --}}
                <button
                    type="button"
                    @click="showDigitalId = true"
                    class="w-full text-left rounded-2xl bg-gradient-to-br from-navy-950 via-navy-900 to-brand-900 border border-navy-800 shadow-card p-4 flex items-center gap-3 hover:shadow-card-hover transition-all duration-[220ms] ease-standard group"
                >
                    <div class="w-11 h-11 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform duration-[220ms] ease-standard">
                        <i data-lucide="badge-check" class="w-5 h-5 text-gold-300"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-white">Digital ID</p>
                        <p class="text-[11px] text-white/50"><x-ui.t fil="Ang iyong Resident ID at iba pang ID" en="Your Resident ID and your other IDs" /></p>
                    </div>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-white/40 shrink-0"></i>
                </button>

                <!-- My Barangay -->
                <div x-show="$store.barangay.mine" x-cloak class="rounded-2xl bg-gradient-to-br from-brand-50 via-white to-purple-50/60 border border-brand-100 shadow-card p-4">
                    <p class="text-[11px] font-semibold text-brand-600 uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="landmark" class="w-3.5 h-3.5"></i><x-ui.t fil="Barangay" en="Barangay" /></p>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="relative w-11 h-11 shrink-0">
                            <div class="absolute inset-0 rounded-full bg-white border-2 border-gold-400 flex items-center justify-center text-ink-faint"><i data-lucide="landmark" class="w-5 h-5"></i></div>
                            <img
                                :src="$store.barangay.mine?.seal ? '{{ rtrim(asset(''), '/') }}/' + $store.barangay.mine.seal : ''"
                                x-show="$store.barangay.mine?.seal" x-on:error="$el.style.display='none'" alt=""
                                class="relative w-11 h-11 rounded-full object-cover border-2 border-gold-400"
                            >
                        </div>
                        <h3 class="text-sm font-semibold text-ink truncate"><x-ui.t fil="Barangay" en="Barangay" /> <span x-text="$store.barangay.mine?.label"></span></h3>
                    </div>
                    <div class="space-y-1.5 text-xs border-t border-brand-100/70 pt-3">
                        <div class="flex items-center justify-between gap-2"><span class="text-ink-muted shrink-0"><x-ui.t fil="Punong Barangay" en="Punong Barangay" /></span><span class="font-medium text-slate-700 text-right truncate" x-text="$store.barangay.mine?.officials?.punong_barangay"></span></div>
                        <div class="flex items-center justify-between gap-2"><span class="text-ink-muted shrink-0"><x-ui.t fil="Kalihim" en="Secretary" /></span><span class="font-medium text-slate-700 text-right truncate" x-text="$store.barangay.mine?.officials?.barangay_secretary"></span></div>
                        <div class="flex items-center justify-between gap-2" x-show="$store.barangay.mine?.contact"><span class="text-ink-muted shrink-0"><x-ui.t fil="Contact" en="Contact" /></span><a class="font-medium text-brand-600 hover:underline text-right truncate" :href="'tel:' + ($store.barangay.mine?.contact || '').replace(/[^0-9+]/g, '')" x-text="$store.barangay.mine?.contact"></a></div>
                    </div>
                </div>

                <!-- Resident Profile: where a notice about the barangay's decision lands -->
                <x-ui.card id="resident-profile" x-show="profileVerification" x-cloak @resident-profile-saved.window="loadResidentProfile()">
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <h3 class="text-sm font-semibold text-ink flex items-center gap-2"><i data-lucide="clipboard-check" class="w-4 h-4 text-ink-muted"></i><x-ui.t fil="Resident Profile" en="Resident Profile" /></h3>
                        {{-- LANGUAGE (TAB 09): the record status is a ruled status name, shown as is.
                             Not before the profile is sent (2026-11-18): an unsent profile's record
                             reads Unverified, which beside "Verified na Residente" said the opposite
                             of the account; the line below says it is not sent yet. --}}
                        <span class="text-[11px] font-semibold rounded-full px-2 py-0.5" x-show="profileVerification !== 'Unverified' || profileReview?.outcome === 'returned'" :class="profileVerification === 'Verified' ? 'bg-emerald-50 text-emerald-700' : (profileReview?.outcome === 'returned' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700')" x-text="profileVerification"></span>
                    </div>
                    {{-- Whose status this is (2026-11-18): the household record the barangay
                         checks, not the account the hero calls verified (ID and residency). --}}
                    <p class="text-[11px] text-ink-muted leading-relaxed mb-2" data-resident-profile-scope><x-ui.t fil="Ang talaan mo at ng iyong sambahayan sa barangay. Hiwalay ito sa pag-verify ng iyong account." en="Your and your household's record at the barangay. It is separate from your account's verification." /></p>
                    {{-- Recognised at registration as a record the barangay already had (2026-10-09). --}}
                    <p class="flex gap-2 text-xs text-brand-800 bg-brand-50 rounded-xl px-3 py-2 mb-2 leading-relaxed" x-show="residentProfileView?.registry_match?.status === 'pending'" x-cloak><i data-lucide="user-check" class="w-4 h-4 shrink-0 mt-0.5"></i><x-ui.t fil="May tala ka na sa talaan ng iyong barangay. Kukumpirmahin ito ng barangay office kapag na-verify ang iyong account; hanggang doon, ang mga detalyeng ibinigay mo lang ang makikita mo rito." en="Your barangay already has a record of you. The barangay office confirms it when they verify your account; until then you see only the details you gave." /></p>
                    <p class="text-xs text-slate-600 leading-relaxed" x-show="profileVerification === 'Verified'"><x-ui.t fil="Na-verify ng iyong barangay office ang impormasyon mo at ng iyong sambahayan." en="Your barangay office verified your information and your household's." /></p>
                    <p class="text-xs text-slate-600 leading-relaxed" x-show="profileVerification === 'For Validation'"><x-ui.t fil="Sinusuri ito ng iyong barangay office. Aabisuhan ka kapag tapos na." en="Your barangay office is reviewing it. You will be told when they finish." /></p>
                    <div class="space-y-1" x-show="profileReview?.outcome === 'returned'">
                        <p class="text-xs font-semibold text-amber-700"><x-ui.t fil="Kailangang ayusin ayon sa iyong barangay office:" en="Your barangay office asks you to change:" /></p>
                        <p class="text-xs text-amber-800 leading-relaxed break-words" x-text="profileReview?.note"></p>
                        <p class="text-[11px] text-ink-muted"><x-ui.t fil="Ayusin ito rito, o sa Esperanza app (Android), at isumite muli." en="Fix it here, or in the Esperanza app (Android), and send it again." /></p>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed" x-show="profileVerification === 'Unverified' && profileReview?.outcome !== 'returned'"><x-ui.t fil="Hindi pa naisusumite. Kumpletuhin ito rito, o sa Esperanza app (Android)." en="Not submitted yet. Complete it here, or in the Esperanza app (Android)." /></p>
                    {{-- The household's code in the barangay's resident registry
                         (2026-10-27): the server assigns it; shown as the app shows it. --}}
                    <div class="mt-2.5 rounded-xl surface-pressed-sm px-3 py-2.5" x-show="residentProfileView?.household?.ref" x-cloak data-household-code>
                        <p class="text-[11px] font-semibold text-ink-muted uppercase tracking-wide"><x-ui.t fil="Code ng sambahayan" en="Household code" /></p>
                        <p class="text-sm font-bold text-ink font-mono tracking-wide select-all" x-text="residentProfileView?.household?.ref"></p>
                        <p class="text-[11px] text-ink-muted leading-relaxed mt-0.5"><x-ui.t fil="Ang code ng inyong sambahayan sa talaan ng mga residente ng barangay. Ginagamit ito ng staff sa mga form ng paglikas at relief." en="Your household's code in the barangay's resident registry. Staff use it on evacuation and relief forms." /></p>
                        <p class="text-[11px] text-amber-700 leading-relaxed mt-0.5" x-show="profileVerification !== 'Verified'"><x-ui.t fil="Kukumpirmahin ito ng iyong barangay office kapag na-verify ang iyong profile." en="Your barangay office confirms it when it verifies your profile." /></p>
                    </div>
                    {{-- The website fills and corrects it too (live test 2026-10-07,
                         UX-8): the app is Android only. --}}
                    <x-ui.button size="sm" class="mt-3 w-full justify-center" icon="clipboard-pen" x-show="profileVerification === 'Unverified'" x-cloak
                        @click="$dispatch('open-resident-profile', residentProfileView)">
                        <span x-text="$store.lang.current === 'en' ? (profileReview?.outcome === 'returned' ? 'Fix my Resident Profile' : 'Fill in my Resident Profile') : (profileReview?.outcome === 'returned' ? 'Ayusin ang Resident Profile' : 'Punan ang Resident Profile')"></span>
                    </x-ui.button>
                    {{-- Once sent, the resident can still see what they sent, view
                         only, as in the app; only the family list showed before
                         (parity check, 2026-10-26). --}}
                    <x-ui.button variant="secondary" size="sm" class="mt-3 w-full justify-center" icon="eye" x-show="profileVerification === 'For Validation' || profileVerification === 'Verified'" x-cloak data-view-resident-profile
                        @click="$dispatch('view-resident-profile', { view: residentProfileView, status: profileVerification })">
                        <x-ui.t fil="Tingnan ang ipinadala mo" en="View what you sent" />
                    </x-ui.button>
                </x-ui.card>

                <!-- Household -->
                <x-ui.card :padded="false">
                    <div class="px-4 pt-3.5 pb-2.5 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-ink flex items-center gap-2"><i data-lucide="users" class="w-4 h-4 text-ink-muted"></i><x-ui.t fil="Kasapi ng Pamilya" en="Family Members" /></h3>
                        <span class="text-[11px] font-medium text-ink-muted" x-text="familyMembers.length"></span>
                    </div>
                    <div class="divide-y divide-brand-600/[.08]">
                        <template x-for="member in familyMembers" :key="member.ref">
                            <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/60 transition-colors">
                                <div class="w-8 h-8 text-xs rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0" x-text="(member.name || '').split(/\s+/).filter(Boolean).map((w) => w[0]).slice(0, 2).join('')"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-700 truncate" x-text="member.name"></p>
                                    <p class="text-xs text-ink-muted" x-text="member.dob ? (new Date().getFullYear() - new Date(member.dob).getFullYear()) + ' ' + ($store.lang.current === 'en' ? 'y/o' : 'taong gulang') : ''"></p>
                                </div>
                                <span class="text-[10px] font-medium text-ink-muted bg-slate-100 rounded-full px-2 py-0.5 shrink-0" x-text="member.relation"></span>
                            </div>
                        </template>
                        <p class="px-4 py-6 text-center text-xs text-ink-muted" x-show="!residentProfileLoading && !familyMembers.length">
                            <x-ui.t fil="Wala pang resident profile o pamilya sa file." en="No resident profile or family on file yet." />
                        </p>
                    </div>
                </x-ui.card>

                <!-- Documents on file -->
                <x-ui.card :padded="false">
                    <div class="px-4 pt-3.5 pb-2.5">
                        <h3 class="text-sm font-semibold text-ink flex items-center gap-2"><i data-lucide="folder-check" class="w-4 h-4 text-ink-muted"></i><x-ui.t fil="Mga Dokumento sa File" en="Documents on File" /></h3>
                    </div>
                    <div class="divide-y divide-brand-600/[.08]">
                        <template x-for="doc in documents" :key="doc.id">
                            <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/60 transition-colors">
                                <span class="w-8 h-8 rounded-lg bg-slate-100 text-ink-muted flex items-center justify-center shrink-0">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                </span>
                                <p class="text-sm text-slate-700 min-w-0 truncate flex-1" x-text="doc.name"></p>
                                <x-ui.badge status-bind="doc.status" class="shrink-0" />
                            </div>
                        </template>
                        <p class="px-4 py-6 text-center text-xs text-ink-muted" x-show="!documentsLoading && !documents.length">
                            <x-ui.t fil="Wala pang dokumento sa file." en="No documents on file yet." />
                        </p>
                    </div>
                </x-ui.card>
            </div>
        </div>

        {{-- The long lists span the whole page (owner, 2026-11-17: no empty space on
             the right). Under the two columns they used to leave the right third blank
             once the side cards ended. --}}
        <div class="mt-5 space-y-5">
            {{-- The Dokyu and Tulong on their record, the offices' included (2026-10-13). --}}
            <x-citizen.records />

            {{-- Every file attached to their requests, from the app or the website. --}}
            <x-citizen.uploads />
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
                <p class="text-sm font-semibold text-ink">
                    <x-ui.t fil="Naisumite para sa Beripikasyon!" en="Submitted for Verification!" />
                    <span class="inline-block animate-check-pop" style="animation-delay: 0.3s;">🙂</span>
                </p>
                <p class="text-xs text-ink-muted mt-1 max-w-[240px]"><x-ui.t fil="Sinusuri na ang iyong ID at selfie. Makikita mo ang resulta sa iyong profile." en="We are checking your ID and selfie now. You will see the result on your profile." /></p>
            </div>

            <div x-show="!submitted">
                <p class="text-sm text-ink-muted mb-4"><x-ui.t fil="Magsumite ng valid ID para ma-verify ng aming staff ang iyong account." en="Submit a valid ID so our staff can verify your account." /></p>
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs text-rose-800" x-show="idView.submission?.status === 'Rejected'" x-cloak>
                    <span x-text="($store.lang.current === 'en' ? 'Your last ID was not accepted: ' : 'Hindi tinanggap ang huling ID: ') + (idView.submission?.remarks || '')"></span>
                </div>
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs text-rose-800 space-y-1" x-show="['Failed', 'Revoked'].includes(idView.identity?.status)" x-cloak>
                    <p x-text="identityText(idView.identity?.status)"></p>
                    <template x-for="r in (idView.identity?.reasons || [])" :key="r">
                        <p x-show="reasonText(r)" x-text="'• ' + reasonText(r)"></p>
                    </template>
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Uri ng ID" en="ID Type" /></label>
                    <select aria-label="Id type" x-model="idType" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        <option value="" disabled x-text="$store.lang.current === 'en' ? 'Select ID type' : 'Pumili ng uri ng ID'"></option>
                        <template x-for="t in idView.id_types" :key="t">
                            <option :value="t" x-text="t"></option>
                        </template>
                    </select>
                </div>

                <p class="text-sm font-semibold text-ink mb-1"><x-ui.t fil="Ikabit ang Larawan ng ID" en="Attach ID Photo" /></p>
                <p class="text-xs text-ink-muted mb-3"><x-ui.t fil="Malinaw na larawan o scan ng harap ng iyong ID." en="A clear photo or scan of the front of your ID." /></p>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-600 flex-1" x-text="idFile ? idFile.name : ($store.lang.current === 'en' ? 'No file chosen' : 'Walang napiling file')"></p>
                        <label class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap cursor-pointer">
                            <x-ui.t fil="Pumili ng File" en="Choose File" />
                            <input type="file" accept="image/*,.pdf" class="sr-only" @change="idFile = $event.target.files[0] || null">
                        </label>
                    </div>
                </div>

                <p class="text-sm font-semibold text-ink mt-4 mb-1"><x-ui.t fil="Likod ng ID (opsyonal)" en="Back of the ID (optional)" /></p>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-600 flex-1" x-text="idBack ? idBack.name : ($store.lang.current === 'en' ? 'No file chosen' : 'Walang napiling file')"></p>
                        <label class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap cursor-pointer">
                            <x-ui.t fil="Pumili ng File" en="Choose File" />
                            <input type="file" accept="image/*,.pdf" class="sr-only" @change="idBack = $event.target.files[0] || null">
                        </label>
                    </div>
                </div>

                <p class="text-sm font-semibold text-ink mt-4 mb-1"><x-ui.t fil="Selfie" en="Selfie" /></p>
                <p class="text-xs text-ink-muted mb-3"><x-ui.t fil="Kunan ngayon gamit ang camera. Ihahambing ito sa litrato sa iyong ID para makumpirmang ikaw ito." en="Taken now with your camera. It is compared with the photo on your ID to confirm it is you." /></p>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-600 flex-1" x-text="idSelfie ? ($store.lang.current === 'en' ? 'Selfie taken' : 'Nakuha ang selfie') : ($store.lang.current === 'en' ? 'No selfie yet' : 'Wala pang selfie')"></p>
                        <button type="button" @click="captureSelfie()" class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap">
                            <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                            <span x-text="idSelfie ? ($store.lang.current === 'en' ? 'Retake' : 'Kunan muli') : ($store.lang.current === 'en' ? 'Take selfie' : 'Mag-selfie')"></span>
                        </button>
                    </div>
                    <p x-show="selfieError" x-cloak class="text-xs text-rose-600 mt-2" x-text="selfieError"></p>
                </div>
            </div>

            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" x-show="!submitted" x-bind:disabled="submitting" @click="reset()"><x-ui.t fil="Kanselahin" en="Cancel" /></x-ui.button>
                <x-ui.button
                    size="sm"
                    x-show="!submitted"
                    ::disabled="!idType || !idFile || !idSelfie || submitting"
                    @click="submitId()"
                >
                    <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="submitting" x-cloak></i>
                    <x-ui.t fil="Isumite para sa Beripikasyon" en="Submit for verification" />
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        <div x-data="{ get open() { return showDigitalId }, set open(v) { showDigitalId = v } }">
            <x-ui.modal title="Digital ID" titleEn="Digital ID" maxWidth="md">
                <template x-if="showDigitalId">
                    <x-citizen.digital-id-wallet />
                </template>
            </x-ui.modal>
        </div>

        <div x-data="{ get open() { return showEditProfile }, set open(v) { showEditProfile = v } }">
            <x-ui.modal title="I-edit ang Profile" titleEn="Edit Profile" maxWidth="md" :fullscreen="true">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3" x-show="!$store.citizenSession.account?.identityLocked">
                        <x-ui.input name="edit_first_name" label="First Name" x-model="editForm.firstName" />
                        <x-ui.input name="edit_last_name" label="Last Name" x-model="editForm.lastName" />
                    </div>
                    {{-- Verified: the name is locked here; a correction goes to the barangay office. --}}
                    <div x-show="$store.citizenSession.account?.identityLocked" x-cloak class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <x-ui.input name="locked_first_name" label="First Name" icon="lock" x-model="editForm.firstName" readonly aria-readonly="true" class="cursor-not-allowed opacity-80" />
                            <x-ui.input name="locked_last_name" label="Last Name" icon="lock" x-model="editForm.lastName" readonly aria-readonly="true" class="cursor-not-allowed opacity-80" />
                        </div>
                        <template x-if="showEditProfile">
                        <div x-data="nameChangeRequest" class="rounded-xl border border-amber-200 bg-amber-50 p-3 space-y-2.5" data-testid="name-change">
                            <p class="text-xs text-amber-900"><x-ui.t fil="Beripikado na ang iyong account, kaya hindi na mababago rito ang iyong pangalan. Kung nagbago ito (hal. ikinasal ka) o mali ang pagkakasulat, humiling ng pagpapalit at ilakip ang patunay: susuriin ito ng iyong barangay at ipapaalam sa iyo." en="Your account is verified, so your name can't be changed here. If it has changed (for example, you got married) or it is misspelled, request a change with your proof: your barangay office will check it and let you know." /></p>
                            {{-- One at a time: the request still waiting. --}}
                            <div x-show="ncPending" x-cloak class="rounded-lg bg-white/70 border border-amber-200 px-3 py-2 text-xs text-amber-900 flex items-start gap-2">
                                <i data-lucide="hourglass" class="w-3.5 h-3.5 shrink-0 mt-px"></i>
                                <p><span class="font-semibold" x-text="t('Waiting for your barangay: ', 'Hinihintay ang iyong barangay: ') + (ncPending?.ref || '')"></span> <span x-text="ncPending?.requested ? '→ ' + [ncPending.requested.first_name, ncPending.requested.middle_name, ncPending.requested.last_name].filter(Boolean).join(' ') : ''"></span><span class="block text-amber-800/80 mt-0.5" x-text="t('Sent ', 'Ipinadala noong ') + fmtDate(ncPending?.submitted_at) + t('. You can send another once it is answered.', '. Maaari kang magpadala ng bago kapag nasagot na ito.')"></span></p>
                            </div>
                            <button type="button" x-show="!ncOpen && !ncPending" @click="ncStart()" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:underline"><i data-lucide="pencil-line" class="w-3.5 h-3.5"></i><x-ui.t fil="Humiling ng pagpapalit ng pangalan" en="Request a name change" /></button>
                            <div x-show="ncOpen" x-cloak class="space-y-2.5">
                                <div>
                                    <label for="nc_reason" class="block text-xs font-semibold text-ink-body mb-1"><x-ui.t fil="Dahilan" en="Reason" /></label>
                                    <select id="nc_reason" x-model="ncForm.reason" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                        <option value="" x-text="t('Choose…', 'Pumili…')"></option>
                                        <template x-for="r in ncReasons" :key="r.key"><option :value="r.key" x-text="t(r.en, r.fil)"></option></template>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <x-ui.input name="nc_first_name" label="New first name" labelFil="Bagong pangalan" x-model="ncForm.firstName" maxlength="80" />
                                    <x-ui.input name="nc_last_name" label="New last name" labelFil="Bagong apelyido" x-model="ncForm.lastName" maxlength="80" />
                                </div>
                                <x-ui.input name="nc_middle_name" label="Middle name (optional)" labelFil="Gitnang pangalan (opsyonal)" x-model="ncForm.middleName" maxlength="80" />
                                <div>
                                    <p class="text-xs font-semibold text-ink-body"><x-ui.t fil="Patunay" en="Proof" /></p>
                                    <p class="text-[11px] text-ink-muted" x-text="ncProofHint"></p>
                                    <label class="mt-1.5 inline-flex items-center gap-1.5 rounded-lg bg-white/80 border border-brand-200 px-3 py-1.5 text-xs font-semibold text-brand-700 cursor-pointer hover:bg-white">
                                        <i data-lucide="paperclip" class="w-3.5 h-3.5"></i><span x-text="ncFile ? ncFile.name : t('Attach a photo or PDF', 'Mag-attach ng litrato o PDF')"></span>
                                        <input id="nc_proof" type="file" class="sr-only" accept="image/*,application/pdf" @change="ncPick($event.target.files[0]); $event.target.value = ''">
                                    </label>
                                </div>
                                <x-ui.input name="nc_note" label="Note (optional)" labelFil="Paliwanag (opsyonal)" x-model="ncForm.note" maxlength="300" />
                                <p x-show="ncError" x-cloak role="alert" class="text-xs font-medium text-rose-700" x-text="ncError"></p>
                                <p class="text-[11px] text-amber-800/90"><x-ui.t fil="Walang bayad at walang hintayang panahon, pero isa lang ang maaaring nakabinbin. Mananatili sa record ang dati mong pangalan." en="Free, and no waiting period, but only one request can be waiting at a time. Your former name stays on record." /></p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.button size="sm" x-bind:disabled="ncSending" @click="ncSend()"><span x-text="ncSending ? t('Sending…', 'Ipinapadala…') : t('Send request', 'Ipadala ang kahilingan')"></span></x-ui.button>
                                    <x-ui.button variant="secondary" size="sm" x-bind:disabled="ncSending" @click="ncOpen = false"><x-ui.t fil="Huwag na" en="Never mind" /></x-ui.button>
                                </div>
                            </div>
                        </div>
                        </template>
                    </div>
                    <x-ui.input name="edit_mobile" type="tel" inputmode="numeric" maxlength="11" label="Mobile Number" icon="phone" placeholder="09XXXXXXXXX" x-model="editForm.mobile" x-bind:disabled="mobileStep === 'code'" @input="editForm.mobile = window.phMobile.clean($event.target.value)" />
                    <div x-show="mobileStep === 'code'" x-cloak class="rounded-xl border border-brand-100 bg-brand-50/50 p-3 space-y-2">
                        <p class="text-xs text-slate-600" x-text="$store.lang.current === 'en' ? ('We sent a 6-digit code to ' + (mobileSentTo || 'you') + '. Enter it to change your mobile number.') : ('Nagpadala kami ng 6-digit na code sa ' + (mobileSentTo || 'iyo') + '. Ilagay ito para palitan ang mobile number.')"></p>
                        <x-ui.input name="edit_mobile_code" type="text" inputmode="numeric" maxlength="6" label="Verification code" placeholder="000000" x-model="mobileCode" @input="mobileCode = $event.target.value.replace(/[^0-9]/g, '').slice(0, 6)" />
                    </div>
                    {{-- Barangay: editable until the account is verified, as in the app. --}}
                    <div x-show="!$store.citizenSession.account?.identityLocked">
                        <label for="edit_barangay" class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Barangay" en="Barangay" /></label>
                        <select id="edit_barangay" x-model="editForm.barangay" class="w-full rounded-xl border-0 surface-pressed py-2.5 px-3 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <template x-if="editForm.barangay && !barangays.includes(editForm.barangay)"><option :value="editForm.barangay" x-text="editForm.barangay"></option></template>
                            <template x-for="b in barangays" :key="b"><option :value="b" x-text="b" :selected="b === editForm.barangay"></option></template>
                        </select>
                    </div>
                    <x-livelihood-select model="editForm" id="edit-livelihood" label-class="block text-sm font-medium text-slate-700 mb-1.5"
                        input-class="w-full rounded-xl border-0 surface-pressed py-2.5 px-3 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none" />
                    <x-ui.input name="edit_address" label="Complete Address" icon="house" x-model="editForm.address" />
                    <p class="text-xs text-ink-muted" x-show="$store.citizenSession.account?.identityLocked" x-cloak><x-ui.t fil="Ang barangay at Resident ID ay hindi maaaring baguhin dito — makipag-ugnayan sa iyong Barangay Hall." en="Barangay and Resident ID can't be changed here — contact your Barangay Hall for those." /></p>
                    <p class="text-xs text-ink-muted" x-show="!$store.citizenSession.account?.identityLocked"><x-ui.t fil="Ang Resident ID ay hindi maaaring baguhin dito. Kapag beripikado na ang iyong account, ang barangay ay ipapaayos sa iyong Barangay Hall." en="Your Resident ID can't be changed here. Once your account is verified, your barangay is changed through your Barangay Hall." /></p>
                </div>

                <x-slot:footer>
                    <x-ui.button variant="secondary" size="sm" x-bind:disabled="savingProfile" @click="showEditProfile = false"><x-ui.t fil="Kanselahin" en="Cancel" /></x-ui.button>
                    <x-ui.button size="sm" x-show="mobileStep === 'code'" x-cloak x-bind:disabled="savingProfile" @click="verifyMobile()"><x-ui.t fil="Kumpirmahin ang code" en="Confirm code" /></x-ui.button>
                    <x-ui.button size="sm" x-show="mobileStep !== 'code'" x-bind:disabled="savingProfile" @click="saveProfile()">
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="savingProfile" x-cloak></i>
                            <span x-text="savingProfile ? ($store.lang.current === 'en' ? 'Saving…' : 'Sine-save…') : ($store.lang.current === 'en' ? 'Save changes' : 'I-save ang pagbabago')"></span>
                        </span>
                    </x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>
    </div>
    <x-citizen.resident-profile-form />
</x-layouts.citizen>
