{{--
    Citizen sign-up, step for step as the mobile app does it (owner, 2026-10-12:
    "the website should follow the flow of the mobile"; the app's
    lib/screens/auth/register_screen.dart). One request, one account:

      1. Personal Information - name, an email proven with a 6-digit code
         before the account exists, mobile, barangay, purok, a password that
         meets the five rules.
      2. Terms & Conditions - the summary, the two documents, the agreement.
      3. Valid ID Upload and 4. Selfie - only when the server captures the ID
         on the portal's own screens (GET /portal-settings identity_capture =
         own). With Didit's screen (hosted) they come after the account
         exists, from the last step.
      5. Review - every answer, each with Edit.
      6. Verify Identity (hosted) / Verification Status (own) - signed in
         already, since the email was proven: Didit's identity check, or
         "do it later" from My Profile; the account's verification panel;
         Continue to the portal.

    A server that still asks for a code after sign-up (verification.required)
    gets the code box on the last step, as the app does.
--}}
<x-layouts.guest title="Create Account" image="esperanza_community1.jpg" fullscreen>
    {{-- Below lg the brand panel is hidden: a compact seal and name keep the card identifiable (design critique 2026-11-04). --}}
    <div class="lg:hidden flex items-center gap-3 mb-5">
        <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" class="w-10 h-10 rounded-full ring-2 ring-slate-200 object-cover" alt="Municipal Seal">
        <div>
            <p class="text-sm font-semibold text-ink leading-tight">Municipality of Esperanza</p>
            <p class="text-xs text-ink-muted">Province of Masbate</p>
        </div>
    </div>
    <div
        x-data="{
            hosted: true,
            stepKey: 'personal',
            barangays: [],
            idTypes: [],
            idTypesFailed: false,
            form: { first_name: '', last_name: '', email: '', mobile: '', barangay: '', purok: '', password: '' },
            // The email is proven before the account exists: send a code,
            // confirm it, and keep the single-use token registration presents.
            emailState: { sending: false, sent: false, confirming: false, verified: false, token: null, code: '', error: '' },
            termsAccepted: false,
            idType: '',
            idFile: null,
            selfie: null,
            selfiePreview: null,
            selfieError: '',
            accountNo: null,
            otpDestination: '',
            otpCode: '',
            foundInRegistry: false,
            signedIn: false,
            identitySent: false,
            identityError: '',
            sendingIdentity: false,
            launchingDidit: false,
            loading: false,
            attempted: false,
            error: '',

            t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
            get steps() {
                return this.hosted ? ['personal', 'terms', 'review', 'status'] : ['personal', 'terms', 'id', 'selfie', 'review', 'status'];
            },
            get stepIndex() { return this.steps.indexOf(this.stepKey); },
            stepLabel(key) {
                return {
                    personal: this.t('Personal Information', 'Personal na Impormasyon'),
                    terms: this.t('Terms & Conditions', 'Mga Tuntunin at Kundisyon'),
                    id: this.t('Valid ID Upload', 'Valid ID'),
                    selfie: 'Selfie',
                    review: this.t('Review', 'Suriin'),
                    status: this.hosted ? this.t('Verify Identity', 'Beripikasyon') : this.t('Verification Status', 'Status ng Beripikasyon'),
                }[key];
            },
            get passwordRules() {
                const p = this.form.password;
                return [
                    { ok: [...p].length >= 8, en: '8 characters', fil: '8 characters', unmet: this.t('The password must be at least 8 characters.', 'Dapat hindi bababa sa 8 characters ang password.') },
                    { ok: /\p{Lu}/u.test(p), en: '1 uppercase letter', fil: '1 uppercase letter', unmet: this.t('The password needs at least 1 uppercase letter.', 'Dapat may kahit 1 uppercase letter ang password.') },
                    { ok: /\p{Ll}/u.test(p), en: '1 lowercase letter', fil: '1 lowercase letter', unmet: this.t('The password needs at least 1 lowercase letter.', 'Dapat may kahit 1 lowercase letter ang password.') },
                    { ok: /\d/.test(p), en: '1 number', fil: '1 number', unmet: this.t('The password needs at least 1 number.', 'Dapat may kahit 1 number ang password.') },
                    { ok: /[^\p{L}\d]/u.test(p), en: '1 special character', fil: '1 special character', unmet: this.t('The password needs at least 1 special character.', 'Dapat may kahit 1 special character ang password.') },
                ];
            },

            async init() {
                try { this.barangays = ((await $api.get('/barangays')).data || []).map((b) => b.name); } catch { this.barangays = []; }
                try { this.hosted = (await $api.get('/portal-settings')).data?.identity_capture === 'hosted'; } catch { this.hosted = false; }
                if (!this.hosted) this.loadIdTypes();
                // What a failed Continue said goes once the form is edited.
                this.$watch('form', () => { if (this.error) this.error = ''; });
            },
            async loadIdTypes() {
                this.idTypesFailed = false;
                try { this.idTypes = (await $api.get('/id-types')).data || []; } catch { this.idTypes = []; this.idTypesFailed = true; }
            },

            // Each step checks its own answers, and says what is missing.
            validStep() {
                this.error = this.problem();
                if (!this.error) { this.attempted = false; return true; }
                this.attempted = true;
                this.$nextTick(() => this.$refs.stepError?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                return false;
            },
            problem() {
                switch (this.stepKey) {
                    case 'personal': {
                        if (!this.form.first_name.trim() || !this.form.last_name.trim()) return this.t('Please enter your first and last name.', 'Pakilagay ang iyong pangalan at apelyido.');
                        if (!this.form.email.trim()) return this.t('Please enter your email address.', 'Pakilagay ang iyong email address.');
                        if (!this.emailState.verified) return this.t('Please verify your email before continuing.', 'Paki-verify muna ang iyong email bago magpatuloy.');
                        const phone = window.phMobile.error(this.form.mobile, false, this.$store.lang.current);
                        if (phone) return phone;
                        if (!this.form.barangay) return this.t('Please select your barangay.', 'Piliin ang iyong barangay.');
                        const unmet = this.passwordRules.filter((r) => !r.ok).map((r) => '• ' + r.unmet);
                        return unmet.length ? unmet.join('\n') : '';
                    }
                    case 'terms':
                        return this.termsAccepted ? '' : this.t('Please accept the Terms & Conditions to continue.', 'Pakitanggap ang Mga Tuntunin at Kundisyon para magpatuloy.');
                    case 'id':
                        if (!this.idType) return this.t('Please choose the type of ID.', 'Piliin ang uri ng ID.');
                        return this.idFile ? '' : this.t('Please upload a valid ID to continue.', 'Mag-upload ng valid ID para magpatuloy.');
                    case 'selfie':
                        return this.selfie ? '' : this.t('Please take a selfie to continue.', 'Mag-selfie para magpatuloy.');
                    default:
                        return '';
                }
            },
            next() {
                if (!this.validStep()) return;
                this.error = '';
                this.stepKey = this.steps[Math.min(this.stepIndex + 1, this.steps.length - 1)];
            },
            back() {
                if (this.stepIndex === 0) { window.location.href = '{{ route('citizen.login') }}'; return; }
                this.error = '';
                this.stepKey = this.steps[this.stepIndex - 1];
            },
            edit(key) { this.error = ''; this.stepKey = key; },

            // Editing the address invalidates any code or proof issued for the old one.
            emailChanged() {
                const s = this.emailState;
                if (!s.sent && !s.verified && !s.error) return;
                this.emailState = { sending: false, sent: false, confirming: false, verified: false, token: null, code: '', error: '' };
            },
            async sendEmailCode() {
                const email = this.form.email.trim();
                if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
                    this.emailState.error = this.t('That email is not valid. Check how it is written (for example: name@gmail.com).', 'Hindi valid ang email. Tingnan kung tama ang pagkakasulat (halimbawa: name@gmail.com).');
                    return;
                }
                this.emailState.sending = true;
                this.emailState.error = '';
                try {
                    await $api.post('/auth/citizen/email/send-code', { email });
                    this.emailState.sent = true;
                    this.emailState.code = '';
                } catch (err) {
                    this.emailState.error = err?.code === 'EMAIL_TAKEN'
                        ? this.t('This email is already registered. Use a different email.', 'Naka-register na ang email na ito. Gumamit ng ibang email.')
                        : (err?.explain ? err.explain(this.$store.lang.current) : this.t('The code could not be sent.', 'Hindi naipadala ang code.'));
                } finally { this.emailState.sending = false; }
            },
            async confirmEmailCode() {
                const code = this.emailState.code.trim();
                if (code.length !== 6) {
                    this.emailState.error = this.t('Enter the 6-digit code sent to your email.', 'Ilagay ang 6-digit code na ipinadala sa iyong email.');
                    return;
                }
                this.emailState.confirming = true;
                this.emailState.error = '';
                try {
                    const token = (await $api.post('/auth/citizen/email/verify-code', { email: this.form.email.trim(), code })).data?.verification_token;
                    if (!token) throw null;
                    this.emailState.token = token;
                    this.emailState.verified = true;
                    if (this.error) this.error = '';
                } catch (err) {
                    this.emailState.error = err?.explain ? err.explain(this.$store.lang.current) : this.t('The code could not be confirmed. Please request a new one.', 'Hindi makumpirma ang code. Humingi ng bagong code.');
                } finally { this.emailState.confirming = false; }
            },

            pickId(event) {
                const file = event.target.files?.[0];
                if (file) { this.idFile = file; this.error = ''; }
                event.target.value = '';
            },
            // Live camera only (owner, 2026-10-09): the liveness check must see
            // a capture taken now, so there is no file fallback.
            async takeSelfie() {
                const file = await window.takeSelfie({ title: this.t('Take a selfie', 'Mag-selfie'), capture: this.t('Capture', 'Kunan'), cancel: this.t('Cancel', 'Kanselahin') });
                if (file === undefined) {
                    this.selfieError = this.t('A camera is needed to take your selfie. Allow camera access in your browser, or use a phone or computer with a camera.', 'Kailangan ng camera para sa iyong selfie. Payagan ang camera sa iyong browser, o gumamit ng telepono o computer na may camera.');
                    return;
                }
                if (file) {
                    this.selfie = file;
                    if (this.selfiePreview) URL.revokeObjectURL(this.selfiePreview);
                    this.selfiePreview = URL.createObjectURL(file);
                    this.selfieError = '';
                    this.error = '';
                }
            },

            signInIdentifier() { return this.form.email.trim() || this.form.mobile.trim(); },
            address() {
                const purok = this.form.purok.trim();
                return (purok ? purok + ', ' : '') + 'Barangay ' + this.form.barangay + ', Esperanza, Masbate';
            },
            // The account is created, and - its email already proven - signed in
            // straight away; a server that still wants a code gets the code box.
            async submit() {
                if (this.loading) return;
                this.loading = true;
                this.error = '';
                try {
                    const result = (await $api.post('/auth/citizen/register', {
                        first_name: this.form.first_name.trim(),
                        last_name: this.form.last_name.trim(),
                        email: this.form.email.trim() || null,
                        mobile: this.form.mobile.trim() || null,
                        password: this.form.password,
                        barangay: this.form.barangay,
                        purok: this.form.purok.trim() || null,
                        address: this.address(),
                        accepts_terms: true,
                        email_verification_token: this.emailState.token,
                    })).data;
                    this.foundInRegistry = result?.registry_match === 'pending';
                    const needsCode = result?.verification?.required ?? true;
                    if (!needsCode && result?.account_no) {
                        await this.$store.citizenSession.login(this.signInIdentifier(), this.form.password);
                        this.signedIn = true;
                        await this.sendIdentity();
                    } else {
                        this.accountNo = result?.account_no || null;
                        this.otpDestination = result?.verification?.destination || '';
                    }
                    this.stepKey = 'status';
                } catch (err) {
                    this.error = err?.explain ? err.explain(this.$store.lang.current) : this.t('Registration failed.', 'Hindi natuloy ang pagrehistro.');
                } finally { this.loading = false; }
            },
            async verifyCode() {
                if (this.loading || !this.accountNo || this.otpCode.trim().length !== 6) return;
                this.loading = true;
                this.error = '';
                try {
                    await $api.post('/auth/citizen/verify', { account_no: this.accountNo, code: this.otpCode.trim() });
                    await this.$store.citizenSession.login(this.signInIdentifier(), this.form.password);
                    this.signedIn = true;
                    this.accountNo = null;
                    await this.sendIdentity();
                } catch (err) {
                    this.error = err?.explain ? err.explain(this.$store.lang.current) : this.t('Verification failed.', 'Hindi na-verify.');
                } finally { this.loading = false; }
            },
            // Own capture: the ID and the selfie, sent with the new session. A
            // failure does not undo the account: the last step says so and
            // offers to send them again.
            async sendIdentity() {
                if (this.hosted || !this.idType || !this.idFile || this.identitySent) return;
                this.sendingIdentity = true;
                try {
                    const form = new FormData();
                    form.append('id_type', this.idType);
                    form.append('front', this.idFile);
                    if (this.selfie) form.append('selfie', this.selfie);
                    await $api.post('/citizen/verification/id', form);
                    this.identitySent = true;
                    this.identityError = '';
                } catch (err) {
                    this.identityError = err?.explain ? err.explain(this.$store.lang.current) : this.t('The ID could not be sent.', 'Hindi naipadala ang ID.');
                } finally { this.sendingIdentity = false; }
            },
            // Didit's page: the ID scan and the face check. Back from it, My
            // Profile reads the result (DIDIT_WEB_CALLBACK_URL).
            async startDidit() {
                if (this.launchingDidit) return;
                this.launchingDidit = true;
                try {
                    const r = await $api.post('/citizen/verification/session', { platform: 'web' });
                    if (r.data?.url) { window.location.href = r.data.url; return; }
                    this.$dispatch('toast', { message: this.t('Verification could not be opened. Please try again.', 'Hindi mabuksan ang beripikasyon. Pakisubukang muli.'), variant: 'error' });
                } catch (err) {
                    this.$dispatch('toast', { message: err?.localised ? err.localised(this.$store.lang.current) : this.t('Could not start verification.', 'Hindi masimulan ang beripikasyon.'), variant: 'error' });
                } finally { this.launchingDidit = false; }
            },
            get identityVerified() { return this.$store.citizenSession.account?.identityStatus === 'Verified'; },
        }"
    >
        <a href="{{ route('citizen.login') }}" x-show="stepKey === 'personal'" class="inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-slate-600 mb-6 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> <x-ui.t fil="Bumalik sa login" en="Back to login" />
        </a>

        <h1 class="text-2xl font-semibold text-ink" x-text="stepKey === 'status' ? t('Verification Status', 'Status ng Beripikasyon') : t('Create your account', 'Gumawa ng iyong account')"></h1>

        <!-- Step indicator: one column per step the server's capture mode uses. -->
        <ol class="grid mt-6 mb-1" :style="'grid-template-columns: repeat(' + steps.length + ', minmax(0, 1fr))'" :aria-label="t('Sign-up steps', 'Mga hakbang sa pagrehistro')">
            <template x-for="(key, i) in steps" :key="key">
                <li class="relative flex flex-col items-center gap-1.5 text-center" :aria-current="stepIndex === i ? 'step' : null">
                    <span x-show="i < steps.length - 1" aria-hidden="true"
                          class="absolute top-[13px] left-1/2 w-full h-0.5 rounded-full transition-colors duration-[300ms] ease-standard"
                          :class="stepIndex > i ? 'bg-emerald-400' : 'bg-slate-300'"></span>
                    <span class="relative z-10 w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 transition-colors duration-[300ms] ease-standard"
                          :class="stepIndex > i ? 'bg-emerald-500 text-white' : (stepIndex === i ? 'clay-blue ring-4 ring-brand-100' : 'bg-white text-ink-muted border-2 border-slate-300')">
                        <i data-lucide="check" class="w-3.5 h-3.5" x-show="stepIndex > i" x-cloak></i>
                        <span x-show="stepIndex <= i" x-text="i + 1"></span>
                    </span>
                    <span class="text-[11px] leading-tight max-w-[7.5rem] px-1"
                          :class="stepIndex === i ? 'text-ink font-semibold' : (stepIndex > i ? 'text-ink' : 'text-ink-muted')" x-text="stepLabel(key)"></span>
                </li>
            </template>
        </ol>

        <form class="mt-5 space-y-5" novalidate @submit.prevent="stepKey === 'review' ? submit() : next()">

            <!-- 1. Personal Information -->
            <div x-show="stepKey === 'personal'" x-ref="step1" class="space-y-4">
                <div class="flex items-start gap-2.5 rounded-xl bg-brand-50 px-3.5 py-3 text-xs leading-relaxed text-brand-700">
                    <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5 text-brand-600"></i>
                    <span x-text="t('Let\'s start with your basic information. You can complete your full Resident Profile later.', 'Simulan natin sa iyong pangunahing impormasyon. Maaari mong kumpletuhin ang iyong buong Resident Profile mamaya.')"></span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-ui.input name="first_name" label="First name" label-fil="Pangalan" x-model="form.first_name" />
                        <p x-show="attempted && !form.first_name.trim()" x-cloak class="mt-1 text-xs text-rose-600" x-text="t('Required', 'Kailangan')"></p>
                    </div>
                    <div>
                        <x-ui.input name="last_name" label="Last name" label-fil="Apelyido" x-model="form.last_name" />
                        <p x-show="attempted && !form.last_name.trim()" x-cloak class="mt-1 text-xs text-rose-600" x-text="t('Required', 'Kailangan')"></p>
                    </div>
                </div>

                <!-- Email, proven before the account exists -->
                <div>
                    <div class="flex items-end gap-2.5">
                        <div class="flex-1 min-w-0">
                            <x-ui.input name="email" type="email" label="Email address" label-fil="Email address" icon="mail" x-model="form.email" @input="emailChanged()" />
                        </div>
                        <div class="h-[42px] flex items-center shrink-0">
                            <span x-show="emailState.verified" x-cloak class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-700">
                                <i data-lucide="badge-check" class="w-5 h-5"></i> <span x-text="t('Verified', 'Na-verify')"></span>
                            </span>
                            <x-ui.button x-show="!emailState.verified" type="button" size="sm" @click="sendEmailCode()" loading="emailState.sending">
                                <span x-text="emailState.sent ? t('Resend', 'Ipadala muli') : t('Verify', 'I-verify')"></span>
                            </x-ui.button>
                        </div>
                    </div>
                    <div x-show="emailState.sent && !emailState.verified" x-cloak class="mt-3">
                        <div class="flex items-end gap-2.5">
                            <div class="flex-1 min-w-0">
                                <x-ui.input name="email_code" type="text" inputmode="numeric" maxlength="6" label="Verification code" label-fil="Verification code" icon="mail-check" placeholder="6-digit code" x-model="emailState.code" />
                            </div>
                            <div class="h-[42px] flex items-center shrink-0">
                                <x-ui.button type="button" size="sm" @click="confirmEmailCode()" loading="emailState.confirming">
                                    <span x-text="t('Confirm', 'Kumpirmahin')"></span>
                                </x-ui.button>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-ink-muted" x-text="t('The code was sent to ' + form.email.trim() + '. Check your spam folder too.', 'Ipinadala ang code sa ' + form.email.trim() + '. Tingnan din ang spam folder.')"></p>
                    </div>
                    <p x-show="emailState.error" x-cloak class="mt-1 text-xs text-rose-600" x-text="emailState.error"></p>
                </div>

                <x-ui.input name="mobile" type="tel" inputmode="numeric" maxlength="11" label="Mobile number" label-fil="Mobile number" icon="phone" placeholder="09XXXXXXXXX" x-model="form.mobile" @input="form.mobile = window.phMobile.bind($event.target)" />

                <div>
                    <label for="barangay" class="block text-sm font-semibold text-ink-body mb-1.5">Barangay</label>
                    <div class="relative">
                        <i data-lucide="map-pin" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-ink-muted pointer-events-none"></i>
                        <select id="barangay" name="barangay" x-model="form.barangay" class="w-full rounded-2xl border-0 surface-pressed py-2.5 pl-10 pr-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard appearance-none">
                            <option value="" x-text="t('Select your barangay', 'Piliin ang iyong barangay')">Select your barangay</option>
                            <template x-for="b in barangays" :key="b">
                                <option :value="b" x-text="b"></option>
                            </template>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-muted pointer-events-none"></i>
                    </div>
                </div>

                <x-ui.input name="purok" label="Purok / Sitio" label-fil="Purok / Sitio" icon="map-pinned" x-model="form.purok" />

                <div>
                    <x-ui.input name="password" type="password" label="Password" label-fil="Password" icon="lock" x-bind:placeholder="t('At least 8 characters', 'Hindi bababa sa 8 characters')" x-model="form.password" />
                    <ul class="mt-2 grid gap-1 text-xs" :aria-label="t('Password requirements', 'Mga kailangan ng password')">
                        <template x-for="r in passwordRules" :key="r.en">
                            <li class="flex items-center gap-1.5" :class="r.ok ? 'text-emerald-700' : 'text-ink-muted'">
                                <svg x-show="r.ok" aria-hidden="true" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
                                <svg x-show="!r.ok" aria-hidden="true" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle></svg>
                                <span class="sr-only" x-text="r.ok ? t('Met:', 'Natugunan:') : t('Not yet:', 'Hindi pa:')"></span>
                                <span><span x-text="t('At least ', 'Hindi bababa sa ')"></span><span class="font-semibold" x-text="t(r.en, r.fil)"></span></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <!-- 2. Terms & Conditions -->
            <div x-show="stepKey === 'terms'" x-ref="step2" x-cloak class="space-y-3">
                <h2 class="text-base font-bold text-ink" x-text="t('Terms & Conditions', 'Mga Tuntunin at Kundisyon')"></h2>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm leading-relaxed text-slate-600">
                    <p x-text="t('By creating an Esperanza account, you agree to provide accurate information for barangay/municipal record-keeping. Your data is used only for LGU service delivery (document requests, assistance programs, resident records) and is not shared with third parties. Esperanza LGU staff may contact you to verify submitted information. False or misleading information may result in your verification being rejected.', 'Sa paggawa ng Esperanza account, sumasang-ayon kang magbigay ng tamang impormasyon para sa talaan ng barangay at munisipyo. Ginagamit lamang ang iyong datos sa mga serbisyo ng LGU (paghiling ng dokumento, mga programa ng tulong, talaan ng residente) at hindi ibinabahagi sa iba. Maaaring makipag-ugnayan sa iyo ang staff ng Esperanza LGU para i-verify ang iyong impormasyon. Ang mali o mapanlinlang na impormasyon ay maaaring maging dahilan ng pagtanggi sa iyong beripikasyon.')"></p>
                    <p class="mt-3" x-text="t('When you finish, your details are sent to the Municipality of Esperanza for verification.', 'Pagkatapos, ipapadala ang iyong mga detalye sa Munisipyo ng Esperanza para sa beripikasyon.')"></p>
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1">
                    <a href="{{ route('citizen.terms') }}" target="_blank" class="text-sm font-semibold text-brand-600 hover:underline" x-text="t('Read the Terms and Conditions', 'Basahin ang Mga Tuntunin at Kundisyon')"></a>
                    <a href="{{ route('citizen.privacy-policy') }}" target="_blank" class="text-sm font-semibold text-brand-600 hover:underline" x-text="t('Read the Privacy Policy', 'Basahin ang Patakaran sa Privacy')"></a>
                </div>
                <label class="flex items-start gap-2.5 text-sm text-slate-700 pt-1 cursor-pointer">
                    <input type="checkbox" x-model="termsAccepted" @change="error = ''" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                    <span x-text="t('I have read and agree to the Terms & Conditions and Privacy Policy.', 'Nabasa ko at sumasang-ayon ako sa Mga Tuntunin at Kundisyon at sa Patakaran sa Privacy.')"></span>
                </label>
            </div>

            <!-- 3. Valid ID Upload (own capture only) -->
            <div x-show="stepKey === 'id'" x-ref="step3" x-cloak class="space-y-3">
                <h2 class="text-base font-bold text-ink" x-text="t('Valid ID Upload', 'Pag-upload ng Valid ID')"></h2>
                <p class="text-sm text-ink-muted" x-text="t('Upload a photo or scan of any valid government-issued ID. This helps Esperanza LGU verify your identity.', 'Mag-upload ng litrato o scan ng anumang valid na ID mula sa gobyerno. Nakakatulong ito sa Esperanza LGU na i-verify ang iyong pagkakakilanlan.')"></p>
                <div>
                    <label for="id_type" class="block text-sm font-semibold text-ink-body mb-1.5" x-text="t('Type of ID', 'Uri ng ID')"></label>
                    <select id="id_type" x-model="idType" @change="error = ''" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none appearance-none">
                        <option value="" x-text="t('Select the type of ID', 'Piliin ang uri ng ID')"></option>
                        <template x-for="type in idTypes" :key="type"><option :value="type" x-text="type"></option></template>
                    </select>
                    <div x-show="idTypesFailed" x-cloak class="mt-1 flex items-center justify-between text-xs text-ink-muted">
                        <span x-text="t('The list of IDs could not be loaded.', 'Hindi ma-load ang listahan ng mga ID.')"></span>
                        <button type="button" class="font-semibold text-brand-600" @click="loadIdTypes()" x-text="t('Try again', 'Subukang muli')"></button>
                    </div>
                </div>
                <div x-show="idFile" x-cloak class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-3">
                    <i data-lucide="circle-check" class="w-5 h-5 text-emerald-700 shrink-0"></i>
                    <span class="flex-1 min-w-0 truncate text-sm font-semibold text-emerald-700" x-text="idFile?.name"></span>
                    <button type="button" class="text-sm font-semibold text-brand-600" @click="idFile = null" x-text="t('Change', 'Palitan')"></button>
                </div>
                <label x-show="!idFile" class="flex flex-col items-center justify-center gap-1.5 rounded-xl border border-brand-200 bg-brand-50 py-8 cursor-pointer">
                    <i data-lucide="id-card" class="w-8 h-8 text-brand-500"></i>
                    <span class="text-sm font-bold text-brand-600" x-text="t('Upload Valid ID', 'Mag-upload ng Valid ID')"></span>
                    <span class="text-xs text-ink-muted">JPG, PNG, or PDF</span>
                    <input type="file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" class="sr-only" @change="pickId($event)">
                </label>
                <p class="text-center text-[11px] text-ink-muted" x-text="t('Your ID and selfie are sent to the LGU when your account is created. Staff check them before verifying your account.', 'Ipapadala ang iyong ID at selfie sa LGU kapag nagawa na ang iyong account. Susuriin ito ng staff bago i-verify ang iyong account.')"></p>
            </div>

            <!-- 4. Selfie (own capture only) -->
            <div x-show="stepKey === 'selfie'" x-ref="step4" x-cloak class="space-y-4">
                <h2 class="text-base font-bold text-ink">Selfie</h2>
                <p class="text-sm text-ink-muted" x-text="t('Take a clear photo of your face. The LGU staff who verify your account compare it with your ID.', 'Kumuha ng malinaw na litrato ng iyong mukha. Ihahambing ito ng staff ng LGU sa iyong ID.')"></p>
                <div class="mx-auto w-[168px] h-[168px] rounded-full p-1.5 bg-gradient-to-br from-gold-300 to-gold-500">
                    <div class="w-full h-full rounded-full overflow-hidden bg-brand-50 flex items-center justify-center">
                        <img x-show="selfiePreview" x-cloak :src="selfiePreview" :alt="t('Your selfie', 'Ang iyong selfie')" class="w-full h-full object-cover">
                        <i x-show="!selfiePreview" data-lucide="camera" class="w-16 h-16 text-brand-400"></i>
                    </div>
                </div>
                <ul class="space-y-2 text-sm text-slate-700">
                    <li class="flex gap-2"><i data-lucide="sun" class="w-[18px] h-[18px] text-navy-700 shrink-0"></i><span x-text="t('Stand in good light, facing the camera.', 'Tumayo sa maliwanag na lugar, nakaharap sa camera.')"></span></li>
                    <li class="flex gap-2"><i data-lucide="scan-face" class="w-[18px] h-[18px] text-navy-700 shrink-0"></i><span x-text="t('Show your whole face: no sunglasses, mask or cap.', 'Ipakita ang buong mukha: walang sunglasses, mask o sumbrero.')"></span></li>
                    <li class="flex gap-2"><i data-lucide="user" class="w-[18px] h-[18px] text-navy-700 shrink-0"></i><span x-text="t('Only you in the photo, with a plain background if you can.', 'Ikaw lang sa litrato, at payak ang likuran kung maaari.')"></span></li>
                </ul>
                <div class="flex gap-2 rounded-2xl border border-navy-700/15 bg-navy-800/5 px-3.5 py-3 text-xs leading-relaxed text-navy-800">
                    <i data-lucide="lock" class="w-[18px] h-[18px] shrink-0"></i>
                    <span x-text="t('Your selfie is used only to verify your account: the LGU staff who review it compare it with your valid ID. It is not your profile photo and is never shown publicly.', 'Ginagamit lamang ang iyong selfie para i-verify ang iyong account: ihahambing ito ng staff ng LGU sa iyong valid ID. Hindi ito ang iyong profile photo at hindi ito ipinapakita sa publiko.')"></span>
                </div>
                <x-ui.button type="button" size="lg" class="w-full" icon="camera" @click="takeSelfie()">
                    <span x-text="selfie ? t('Retake selfie', 'Kunan muli') : t('Take selfie', 'Mag-selfie')"></span>
                </x-ui.button>
                <p x-show="selfieError" x-cloak class="text-center text-xs text-rose-600" x-text="selfieError"></p>
            </div>

            <!-- 5. Review -->
            <div x-show="stepKey === 'review'" x-ref="step5" x-cloak class="space-y-3">
                <h2 class="text-base font-bold text-ink" x-text="t('Review Your Information', 'Suriin ang Iyong Impormasyon')"></h2>
                <p class="text-sm text-ink-muted" x-text="t('Make sure everything looks correct before submitting for verification.', 'Siguraduhing tama ang lahat bago ipasa para sa beripikasyon.')"></p>
                <dl class="mt-2 space-y-3">
                    <template x-for="row in [
                        { label: t('Name', 'Pangalan'), value: (form.first_name.trim() + ' ' + form.last_name.trim()).trim(), step: 'personal' },
                        { label: 'Email', value: form.email.trim(), step: 'personal' },
                        { label: 'Mobile', value: form.mobile.trim() || '—', step: 'personal' },
                        { label: 'Barangay', value: form.barangay || '—', step: 'personal' },
                        { label: t('Terms & Conditions', 'Mga Tuntunin at Kundisyon'), value: termsAccepted ? t('Accepted', 'Tinanggap') : t('Not accepted', 'Hindi tinanggap'), step: 'terms' },
                        ...(hosted ? [] : [
                            { label: t('Type of ID', 'Uri ng ID'), value: idType || '—', step: 'id' },
                            { label: 'Valid ID', value: idFile?.name || '—', step: 'id' },
                            { label: 'Selfie', value: selfie ? t('Taken', 'Nakuha na') : t('Not taken', 'Wala pa'), step: 'selfie' },
                        ]),
                    ]" :key="row.label">
                        <div class="flex items-start gap-3">
                            <div class="flex-1 min-w-0">
                                <dt class="text-xs text-ink-muted" x-text="row.label"></dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-700 break-words" x-text="row.value"></dd>
                            </div>
                            <button type="button" class="text-sm font-semibold text-brand-600" @click="edit(row.step)" x-text="t('Edit', 'Baguhin')"></button>
                        </div>
                    </template>
                </dl>
                <p x-show="hosted" class="text-sm text-ink-muted" x-text="t('Next: once your account is created, you will verify your identity by scanning your valid ID and doing a quick face check.', 'Susunod: kapag nagawa na ang iyong account, iveverify mo ang iyong pagkakakilanlan sa pag-scan ng iyong valid ID at isang mabilis na face check.')"></p>
            </div>

            <!-- What a Continue found missing -->
            <p x-show="error && stepKey !== 'status'" x-cloak x-ref="stepError" class="whitespace-pre-line text-sm text-rose-600" x-text="error"></p>

            <!-- Back / Continue / Submit for Verification -->
            <div x-show="stepKey !== 'status'" class="flex items-center gap-2.5 pt-1">
                <x-ui.button x-show="stepIndex > 0" type="button" variant="secondary" size="lg" class="flex-1" @click="back()" x-bind:disabled="loading">
                    <span x-text="t('Back', 'Bumalik')"></span>
                </x-ui.button>
                <x-ui.button type="submit" size="lg" class="flex-[2]" loading="loading">
                    <span class="inline-flex items-center gap-2">
                        <span x-text="stepKey === 'review' ? t('Submit for verification', 'Ipasa para sa beripikasyon') : t('Continue', 'Susunod')"></span>
                        <i x-show="stepKey !== 'review'" data-lucide="arrow-right" class="w-4 h-4"></i>
                    </span>
                </x-ui.button>
            </div>
        </form>

        <!-- 6. Verify Identity / Verification Status -->
        <div x-show="stepKey === 'status'" x-ref="step6" x-cloak class="mt-5 space-y-5">
            <div class="text-center">
                <div class="mx-auto w-[72px] h-[72px] rounded-full bg-amber-50 text-amber-700 flex items-center justify-center">
                    <i data-lucide="hourglass" class="w-8 h-8"></i>
                </div>
                <h2 class="mt-4 text-base font-bold text-ink" x-text="t('You\'re All Set!', 'Handa ka na!')"></h2>
                <p class="mt-1 text-sm text-ink-muted" x-text="accountNo
                    ? t('Enter the 6-digit code sent to ' + otpDestination + ' to confirm your account.', 'Ilagay ang 6-digit code na ipinadala sa ' + otpDestination + ' para kumpirmahin ang iyong account.')
                    : t('Your email is verified. Your information has been sent to Esperanza LGU for review.', 'Na-verify na ang iyong email. Naipasa na ang iyong impormasyon sa Esperanza LGU para ma-review.')"></p>
            </div>

            {{-- Recognised as a record the barangay already had (2026-10-09). --}}
            <p x-show="foundInRegistry" x-cloak class="flex gap-2 rounded-xl bg-brand-50 px-3.5 py-2.5 text-xs leading-relaxed text-brand-800"><i data-lucide="user-check" class="w-4 h-4 shrink-0 mt-0.5"></i><x-ui.t fil="Nakita ka namin sa talaan ng iyong barangay. Iuugnay ito sa iyong account kapag na-verify ka ng barangay office." en="We found you in your barangay's registry. It will be linked to your account when the barangay office verifies you." /></p>

            <!-- Own capture: the ID did not reach the LGU -->
            <div x-show="identityError && !identitySent && signedIn" x-cloak class="space-y-2 text-center">
                <p class="text-sm text-rose-600" x-text="t('Your account was created, but your ID did not reach the LGU: ', 'Nagawa ang iyong account, pero hindi naipadala ang iyong ID sa LGU: ') + identityError"></p>
                <x-ui.button type="button" class="w-full" icon="upload" @click="sendIdentity()" loading="sendingIdentity">
                    <span x-text="t('Send my ID again', 'Ipadala muli ang aking ID')"></span>
                </x-ui.button>
            </div>

            <!-- A server that still asks for a code -->
            <form x-show="accountNo" x-cloak class="space-y-3" @submit.prevent="verifyCode()">
                <x-ui.input name="code" type="text" inputmode="numeric" maxlength="6" label="6-digit code" label-fil="6-digit na code" icon="mail-check" class="font-mono text-center tracking-[0.4em]" x-model="otpCode" />
                <p x-show="error" x-cloak class="text-sm text-rose-600" x-text="error"></p>
                <x-ui.button type="submit" size="lg" class="w-full" loading="loading">
                    <span x-text="t('Confirm code', 'Kumpirmahin ang code')"></span>
                </x-ui.button>
            </form>

            <!-- Didit's identity check, or "do it later" from My Profile -->
            <div x-show="hosted && signedIn && !identityVerified" x-cloak class="space-y-2">
                <h3 class="text-base font-bold text-ink" x-text="t('Verify your identity', 'I-verify ang iyong pagkakakilanlan')"></h3>
                <p class="text-sm text-ink-muted" x-text="t('Have your valid ID ready. You will scan it with your camera, then do a quick face check - about 2 minutes. The check is done by Didit, our identity verification provider. You can also do this later from your profile.', 'Ihanda ang iyong valid ID. I-scan mo ito gamit ang camera, saka isang mabilis na face check - mga 2 minuto. Ang Didit, ang aming identity verification provider, ang gagawa ng pagsusuri. Maaari mo rin itong gawin mamaya mula sa iyong profile.')"></p>
                <x-ui.button type="button" size="lg" class="w-full" icon="scan-line" @click="startDidit()" loading="launchingDidit">
                    <span x-text="t('Verify my identity', 'I-verify ang aking pagkakakilanlan')"></span>
                </x-ui.button>
            </div>

            <!-- The account's verification -->
            <div class="rounded-2xl border border-slate-200 bg-white/70 px-4 py-3.5">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-ink" x-text="t('Account Verification', 'Beripikasyon ng Account')"></span>
                    <x-ui.badge status="Pending Review" />
                </div>
                <p class="mt-2 text-sm text-ink-muted" x-text="hosted && signedIn && !identityVerified
                    ? t('Esperanza LGU reviews your residency once your identity is verified. Verify your identity below first.', 'Susuriin ng Esperanza LGU ang iyong paninirahan kapag na-verify na ang iyong pagkakakilanlan. I-verify muna ang iyong pagkakakilanlan.')
                    : t('Esperanza LGU is reviewing your submitted information. This usually takes 1–3 business days — no action needed from you right now.', 'Sinusuri ng Esperanza LGU ang iyong ipinasang impormasyon. Karaniwang 1–3 araw ng trabaho ito — wala ka pang kailangang gawin ngayon.')"></p>
            </div>

            <x-ui.button x-show="signedIn" href="{{ route('citizen.dashboard') }}" size="lg" class="w-full" icon="arrow-right" iconPosition="right">
                <span x-text="t('Continue to app', 'Magpatuloy sa app')"></span>
            </x-ui.button>
        </div>
    </div>
</x-layouts.guest>
