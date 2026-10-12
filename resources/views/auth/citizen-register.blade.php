<x-layouts.guest title="Create Account" image="esperanza_community1.jpg" fullscreen>
    <div
        x-data="{
            step: 1,
            totalSteps: 3,
            stepLabels: {
                en: ['Personal Info', 'Address', 'Contact & Security'],
                fil: ['Personal na Impormasyon', 'Address', 'Contact at Seguridad'],
            },
            barangays: [],
            form: {
                first_name: '', middle_name: '', last_name: '', suffix: '',
                birthdate: '', sex: '', civil_status: '',
                barangay: '', purok: '', address: '',
                email: '', mobile: '', password: '',
            },
            accountNo: null,
            foundInRegistry: false,
            otpDestination: '',
            otpCode: '',
            loading: false,
            verifying: false,
            error: '',
            verifyError: '',
            async init() {
                try {
                    const result = await $api.get('/barangays');
                    this.barangays = (result.data || []).map((b) => b.name);
                } catch { this.barangays = []; }
            },
            // The three steps share one form. Left to the browser, its
            // validation ran over the hidden steps too: the first invalid
            // field was on a step the resident could not see, so Susunod did
            // nothing and said nothing. Each step now checks its own fields.
            validStep() {
                const box = this.$refs['step' + this.step];
                if (!box) return true;
                const bad = Array.from(box.querySelectorAll('input, select, textarea')).find((el) => !el.checkValidity());
                if (!bad) return true;
                bad.reportValidity();
                bad.focus();
                return false;
            },
            next() {
                if (!this.validStep()) return;
                if (this.step < this.totalSteps) { this.error = ''; this.step++; }
            },
            back() { if (this.step > 1) this.step--; },
            async submit() {
                if (this.loading) return;
                if (!this.validStep()) return;
                this.error = '';
                this.loading = true;
                try {
                    const result = await $api.post('/auth/citizen/register', { ...this.form, accepts_terms: true });
                    this.accountNo = result.data.account_no;
                    this.foundInRegistry = result.data.registry_match === 'pending';
                    this.otpDestination = result.data.verification?.destination || '';
                } catch (err) {
                    this.error = err.explain ? err.explain($store.lang.current) : 'Registration failed.';
                } finally {
                    this.loading = false;
                }
            },
            async verify() {
                if (this.verifying) return;
                this.verifyError = '';
                this.verifying = true;
                try {
                    await $api.post('/auth/citizen/verify', { account_no: this.accountNo, code: this.otpCode.trim() });
                } catch (err) {
                    this.verifyError = err.explain ? err.explain($store.lang.current) : 'Verification failed.';
                    this.verifying = false;
                    return;
                }
                // The account is verified at this point. Signing in is a
                // separate step: if it fails, the sign-in page can finish it,
                // rather than leaving the resident on a code screen that
                // reads as if the code was wrong.
                try {
                    await this.$store.citizenSession.login(this.form.email.trim() || this.form.mobile.trim() || this.accountNo, this.form.password);
                    window.location.href = '{{ route('citizen.dashboard') }}';
                } catch (err) {
                    window.location.href = '{{ route('citizen.login') }}';
                } finally {
                    this.verifying = false;
                }
            },
        }"
    >
        <a href="{{ route('citizen.login') }}" x-show="!accountNo" class="inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-slate-600 mb-6 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> <x-ui.t fil="Bumalik sa login" en="Back to login" />
        </a>

        <div x-show="!accountNo">
            <h1 class="text-2xl font-semibold text-ink"><x-ui.t fil="Gumawa ng Account ng Mamamayan" en="Create your Citizen Account" /></h1>
            <p class="text-ink-muted text-sm mt-1.5"><x-ui.t fil="Magrehistro para humiling ng dokumento at ma-access ang mga serbisyo ng munisipyo online." en="Register to request documents and access municipal services online." /></p>

            <!-- Step indicator: three equal columns, the line running from one
                 circle's centre to the next, so the spacing never depends on
                 how long a label is. An upcoming step is an outlined circle
                 (slate-100 fill vanished on the card: step 3 showed as a bare
                 "3" with no line before it). -->
            <ol class="grid grid-cols-3 mt-6 mb-1" :aria-label="$store.lang.current === 'en' ? 'Sign-up steps' : 'Mga hakbang sa pagrehistro'">
                <template x-for="(label, i) in ($store.lang.current === 'en' ? stepLabels.en : stepLabels.fil)" :key="i">
                    <li class="relative flex flex-col items-center gap-1.5 text-center" :aria-current="step === i + 1 ? 'step' : null">
                        <span x-show="i < 2" aria-hidden="true"
                              class="absolute top-[13px] left-1/2 w-full h-0.5 rounded-full transition-colors duration-[300ms] ease-standard"
                              :class="step > i + 1 ? 'bg-emerald-400' : 'bg-slate-300'"></span>
                        <span
                            class="relative z-10 w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 transition-colors duration-[300ms] ease-standard"
                            :class="step > i + 1 ? 'bg-emerald-500 text-white' : (step === i + 1 ? 'clay-blue ring-4 ring-brand-100' : 'bg-white text-ink-muted border-2 border-slate-300')"
                        >
                            <i data-lucide="check" class="w-3.5 h-3.5" x-show="step > i + 1" x-cloak></i>
                            <span x-show="step <= i + 1" x-text="i + 1"></span>
                        </span>
                        <span class="text-[11px] leading-tight max-w-[7.5rem] px-1"
                              :class="step === i + 1 ? 'text-ink font-semibold' : (step > i + 1 ? 'text-ink' : 'text-ink-muted')" x-text="label"></span>
                    </li>
                </template>
            </ol>

            <form class="mt-5 space-y-5" novalidate @submit.prevent="step === totalSteps ? submit() : next()">
                <!-- Step 1: Personal Information -->
                <div x-show="step === 1" x-ref="step1" x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input name="first_name" label="First Name" label-fil="Pangalan" placeholder="Maria" x-model="form.first_name" required />
                        <x-ui.input name="last_name" label="Last Name" label-fil="Apelyido" placeholder="Bacaltos" x-model="form.last_name" required />
                    </div>
                    <x-ui.input name="middle_name" label="Middle Name" label-fil="Gitnang Pangalan" placeholder="Cabaltera" x-model="form.middle_name" />

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input name="birthdate" type="date" label="Date of Birth" label-fil="Petsa ng Kapanganakan" x-model="form.birthdate" required />
                        <div>
                            <label for="sex" class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Kasarian" en="Sex" /></label>
                            <select id="sex" name="sex" x-model="form.sex" required class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard appearance-none">
                                <option value="" x-text="$store.lang.current === 'en' ? 'Select' : 'Pumili'">Select</option>
                                <option value="Female" x-text="$store.lang.current === 'en' ? 'Female' : 'Babae'">Female</option>
                                <option value="Male" x-text="$store.lang.current === 'en' ? 'Male' : 'Lalaki'">Male</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="civil_status" class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Katayuang Sibil" en="Civil Status" /></label>
                        <select id="civil_status" name="civil_status" x-model="form.civil_status" required class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard appearance-none">
                            <option value="" x-text="$store.lang.current === 'en' ? 'Select your civil status' : 'Piliin ang katayuang sibil'">Select your civil status</option>
                            <option>Single</option>
                            <option>Married</option>
                            <option>Widowed</option>
                            <option>Separated</option>
                            <option>Annulled</option>
                            <option>Live-in</option>
                        </select>
                    </div>

                    <x-ui.button type="submit" size="lg" class="w-full" icon="arrow-right" iconPosition="right">
                        <x-ui.t fil="Susunod" en="Continue" />
                    </x-ui.button>
                </div>

                <!-- Step 2: Address -->
                <div x-show="step === 2" x-ref="step2" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-4">
                    <div>
                        <label for="barangay" class="block text-sm font-medium text-slate-700 mb-1.5">Barangay</label>
                        <div class="relative">
                            <i data-lucide="map-pin" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-ink-muted pointer-events-none"></i>
                            <select id="barangay" name="barangay" x-model="form.barangay" required class="w-full rounded-2xl border-0 surface-pressed py-2.5 pl-10 pr-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard appearance-none">
                                <option value="" x-text="$store.lang.current === 'en' ? 'Select your barangay' : 'Piliin ang iyong barangay'">Select your barangay</option>
                                <template x-for="b in barangays" :key="b">
                                    <option :value="b" x-text="b"></option>
                                </template>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-muted pointer-events-none"></i>
                        </div>
                    </div>

                    <x-ui.input name="purok" label="Purok / Sitio" label-fil="Purok / Sitio" placeholder="Purok 3, Sitio Mabuhay" x-model="form.purok" />
                    <x-ui.input name="address" label="Complete Address" label-fil="Kumpletong Address" icon="house" placeholder="Purok 3, Sitio Mabuhay" x-model="form.address" required />

                    <div class="flex items-center gap-2.5">
                        <x-ui.button type="button" @click="back()" variant="secondary" size="lg" icon="arrow-left">
                            <x-ui.t fil="Bumalik" en="Back" />
                        </x-ui.button>
                        <x-ui.button type="submit" size="lg" class="flex-1" icon="arrow-right" iconPosition="right">
                            <x-ui.t fil="Susunod" en="Continue" />
                        </x-ui.button>
                    </div>
                </div>

                <!-- Step 3: Contact & Security -->
                <div x-show="step === 3" x-ref="step3" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-4">
                    <x-ui.input name="email" type="email" label="Email Address" label-fil="Email Address" icon="mail" placeholder="maria.bacaltos@email.com" x-model="form.email" />
                    <x-ui.input name="mobile" type="tel" inputmode="numeric" maxlength="11" label="Mobile Number" label-fil="Mobile Number" icon="phone" placeholder="09XXXXXXXXX" x-model="form.mobile" @input="form.mobile = window.phMobile.bind($event.target)" />
                    <x-ui.input name="password" type="password" label="Password" label-fil="Password" icon="lock" x-bind:placeholder="$store.lang.current === 'en' ? 'Create a password' : 'Gumawa ng password'" x-model="form.password" required />

                    <label class="flex items-start gap-2.5 text-xs text-ink-muted pt-1">
                        <input type="checkbox" required class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                        <span x-show="$store.lang.current === 'en'">
                            I agree to the <a href="{{ route('citizen.terms') }}" target="_blank" class="text-brand-600 hover:underline font-medium">Terms of Service</a> and <a href="{{ route('citizen.privacy-policy') }}" target="_blank" class="text-brand-600 hover:underline font-medium">Data Privacy policy</a> of the Municipality of Esperanza.
                        </span>
                        <span x-show="$store.lang.current === 'fil'" x-cloak>
                            Sumasang-ayon ako sa <a href="{{ route('citizen.terms') }}" target="_blank" class="text-brand-600 hover:underline font-medium">Terms of Service</a> at <a href="{{ route('citizen.privacy-policy') }}" target="_blank" class="text-brand-600 hover:underline font-medium">patakaran sa Data Privacy</a> ng Munisipyo ng Esperanza.
                        </span>
                    </label>

                    <div x-show="error" x-cloak class="flex items-center gap-2.5 rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-2.5">
                        <i data-lucide="circle-alert" class="w-4 h-4 text-rose-500 shrink-0"></i>
                        <p class="text-xs text-rose-600" x-text="error"></p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <x-ui.button type="button" @click="back()" x-bind:disabled="loading" variant="secondary" size="lg" icon="arrow-left">
                            <x-ui.t fil="Bumalik" en="Back" />
                        </x-ui.button>
                        <x-ui.button type="submit" size="lg" class="flex-1" x-bind:disabled="loading">
                            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="$store.lang.current === 'en' ? (loading ? 'Creating your account…' : 'Create Account') : (loading ? 'Ginagawa ang account…' : 'Gumawa ng Account')"></span>
                        </x-ui.button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Verification step — the account exists but is not usable until the OTP is confirmed -->
        <div x-show="accountNo" x-cloak>
            <div class="w-12 h-12 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center mb-4">
                <i data-lucide="mail-check" class="w-6 h-6"></i>
            </div>
            <h1 class="text-xl font-semibold text-ink"><x-ui.t fil="Kumpirmahin ang Account" en="Confirm your account" /></h1>
            <p class="text-ink-muted text-sm mt-1.5" x-text="($store.lang.current === 'en' ? 'Enter the 6-digit code sent to ' : 'Ilagay ang 6-digit code na ipinadala sa ') + otpDestination"></p>

            {{-- Recognised as a record the barangay already had (2026-10-09). --}}
            <p x-show="foundInRegistry" x-cloak class="mt-4 flex gap-2 rounded-xl bg-brand-50 px-3.5 py-2.5 text-xs leading-relaxed text-brand-800"><i data-lucide="user-check" class="w-4 h-4 shrink-0 mt-0.5"></i><x-ui.t fil="Nakita ka namin sa talaan ng iyong barangay. Iuugnay ito sa iyong account kapag na-verify ka ng barangay office." en="We found you in your barangay's registry. It will be linked to your account when the barangay office verifies you." /></p>

            <form class="mt-6 space-y-4" @submit.prevent="verify">
                <x-ui.input name="code" type="text" label="6-digit code" label-fil="6-digit na code" icon="shield-check" class="font-mono text-center tracking-[0.4em]" x-model="otpCode" maxlength="6" required />

                <div x-show="verifyError" x-cloak class="flex items-center gap-2.5 rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-2.5">
                    <i data-lucide="circle-alert" class="w-4 h-4 text-rose-500 shrink-0"></i>
                    <p class="text-xs text-rose-600" x-text="verifyError"></p>
                </div>

                <x-ui.button type="submit" size="lg" class="w-full" x-bind:disabled="verifying">
                    <span x-text="verifying ? 'Verifying…' : 'Verify and Continue'"></span>
                </x-ui.button>
            </form>
        </div>
    </div>
</x-layouts.guest>
