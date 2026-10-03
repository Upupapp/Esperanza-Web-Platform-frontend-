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
            next() { if (this.step < this.totalSteps) { this.error = ''; this.step++; } },
            back() { if (this.step > 1) this.step--; },
            async submit() {
                if (this.loading) return;
                this.error = '';
                this.loading = true;
                try {
                    const result = await $api.post('/auth/citizen/register', { ...this.form, accepts_terms: true });
                    this.accountNo = result.data.account_no;
                    this.otpDestination = result.data.verification?.destination || '';
                } catch (err) {
                    this.error = err.localised ? err.localised($store.lang.current) : 'Registration failed.';
                    if (err.fields && Object.keys(err.fields).length) {
                        const first = Object.values(err.fields)[0];
                        this.error = Array.isArray(first) ? first[0] : String(first);
                    }
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
                    await this.$store.citizenSession.login(this.accountNo, this.form.password);
                    window.location.href = '{{ route('citizen.dashboard') }}';
                } catch (err) {
                    this.verifyError = err.localised ? err.localised($store.lang.current) : 'Verification failed.';
                } finally {
                    this.verifying = false;
                }
            },
        }"
    >
        <a href="{{ route('citizen.login') }}" x-show="!accountNo" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-600 mb-6 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> <x-ui.t fil="Bumalik sa login" en="Back to login" />
        </a>

        <div x-show="!accountNo">
            <h1 class="text-2xl font-semibold text-navy-900"><x-ui.t fil="Gumawa ng Account ng Mamamayan" en="Create your Citizen Account" /></h1>
            <p class="text-slate-500 text-sm mt-1.5"><x-ui.t fil="Magrehistro para humiling ng dokumento at ma-access ang mga serbisyo ng munisipyo online." en="Register to request documents and access municipal services online." /></p>

            <!-- Step indicator -->
            <div class="flex items-center mt-6 mb-1">
                <template x-for="(label, i) in ($store.lang.current === 'en' ? stepLabels.en : stepLabels.fil)" :key="i">
                    <div class="flex-1 flex items-center">
                        <div class="flex flex-col items-center gap-1.5 text-center">
                            <span
                                class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold shrink-0 transition-colors duration-[300ms] ease-standard"
                                :class="step > i + 1 ? 'bg-emerald-500 text-white' : (step === i + 1 ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-500')"
                            >
                                <i data-lucide="check" class="w-3 h-3" x-show="step > i + 1" x-cloak></i>
                                <span x-show="step <= i + 1" x-text="i + 1"></span>
                            </span>
                            <span class="text-[10px] max-w-[90px] leading-tight" :class="step === i + 1 ? 'text-navy-900 font-medium' : 'text-slate-500'" x-text="label"></span>
                        </div>
                        <div class="flex-1 h-0.5 -mt-4 transition-colors duration-[300ms] ease-standard" :class="step > i + 1 ? 'bg-emerald-400' : 'bg-slate-100'" x-show="i < 2"></div>
                    </div>
                </template>
            </div>

            <form class="mt-5 space-y-5" @submit.prevent="step === totalSteps ? submit() : next()">
                <!-- Step 1: Personal Information -->
                <div x-show="step === 1" x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input name="first_name" label="First Name" placeholder="Maria" x-model="form.first_name" required />
                        <x-ui.input name="last_name" label="Last Name" placeholder="Bacaltos" x-model="form.last_name" required />
                    </div>
                    <x-ui.input name="middle_name" label="Middle Name" placeholder="Cabaltera" x-model="form.middle_name" />

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input name="birthdate" type="date" label="Date of Birth" x-model="form.birthdate" required />
                        <div>
                            <label for="sex" class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Kasarian" en="Sex" /></label>
                            <select id="sex" name="sex" x-model="form.sex" required class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard appearance-none">
                                <option value="">Select</option>
                                <option>Female</option>
                                <option>Male</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="civil_status" class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Katayuang Sibil" en="Civil Status" /></label>
                        <select id="civil_status" name="civil_status" x-model="form.civil_status" required class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard appearance-none">
                            <option value="">Select your civil status</option>
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
                <div x-show="step === 2" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-4">
                    <div>
                        <label for="barangay" class="block text-sm font-medium text-slate-700 mb-1.5">Barangay</label>
                        <div class="relative">
                            <i data-lucide="map-pin" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-slate-500 pointer-events-none"></i>
                            <select id="barangay" name="barangay" x-model="form.barangay" required class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard appearance-none">
                                <option value="">Select your barangay</option>
                                <template x-for="b in barangays" :key="b">
                                    <option :value="b" x-text="b"></option>
                                </template>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
                        </div>
                    </div>

                    <x-ui.input name="purok" label="Purok / Sitio" placeholder="Purok 3, Sitio Mabuhay" x-model="form.purok" />
                    <x-ui.input name="address" label="Complete Address" icon="house" placeholder="Purok 3, Sitio Mabuhay" x-model="form.address" required />

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
                <div x-show="step === 3" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-4">
                    <x-ui.input name="email" type="email" label="Email Address" icon="mail" placeholder="maria.bacaltos@email.com" x-model="form.email" />
                    <x-ui.input name="mobile" type="text" label="Mobile Number" icon="phone" placeholder="09XX XXX XXXX" x-model="form.mobile" />
                    <x-ui.input name="password" type="password" label="Password" icon="lock" placeholder="Create a password" x-model="form.password" required />

                    <label class="flex items-start gap-2.5 text-xs text-slate-500 pt-1">
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
            <h1 class="text-xl font-semibold text-navy-900"><x-ui.t fil="Kumpirmahin ang Account" en="Confirm your account" /></h1>
            <p class="text-slate-500 text-sm mt-1.5" x-text="($store.lang.current === 'en' ? 'Enter the 6-digit code sent to ' : 'Ilagay ang 6-digit code na ipinadala sa ') + otpDestination"></p>

            <form class="mt-6 space-y-4" @submit.prevent="verify">
                <x-ui.input name="code" type="text" label="6-digit code" icon="shield-check" class="font-mono text-center tracking-[0.4em]" x-model="otpCode" maxlength="6" required />

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
