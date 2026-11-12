{{-- The hero was rectangle_cityhall.jpg until 2026-09-03. That photograph is a
     municipal hall whose facade seal reads "MUNICIPALITY OF ESPERANZA — Agusan
     del Sur": a different Esperanza, in Caraga, not this one in Masbate. It was
     the first thing LGU staff saw when signing in. Replaced with scenery that
     makes no place claim at all; see FRONTEND_PENDING.md and
     tests/Feature/PlaceIdentityTest.php. --}}
<x-layouts.guest title="LGU Personnel Login" image="rectangle_sunset.jpg" fullscreen :lang-switch="false">
    <div
        x-data="{
            barangayNames: [],
            scope: '',
            scopeSearch: '',
            employeeId: '',
            password: '',
            mfaCode: '',
            // Straight to Employee ID and password: the account already knows
            // its scope, and a list of 21 scopes was the first thing every
            // officer had to get right (live test 2026-10-07, UX-2). The
            // scope step stays reachable only from the chip below, which shows
            // once the server has said which scope this account is.
            step: 'credentials',
            loading: false,
            error: '',
            notice: '',
            scopeLabel(s) { return s === 'Municipal' ? 'Municipal-wide access' : 'Brgy. ' + s; },
            async init() {
                // What the browser or a password manager filled in before the page's
                // scripts ran stays: x-model would otherwise empty the fields (2026-11-12).
                this.employeeId = this.$el.querySelector('[name=employee_id]')?.value || '';
                this.password = this.$el.querySelector('[name=password]')?.value || '';
                try {
                    const result = await $api.get('/barangays');
                    this.barangayNames = (result.data || []).map((b) => b.name);
                } catch { this.barangayNames = []; }
            },
            get filteredScopes() {
                const q = this.scopeSearch.trim().toLowerCase();
                return this.barangayNames.filter(b => !q || b.toLowerCase().includes(q));
            },
            chooseScope(s) {
                this.scope = s;
                this.error = '';
                this.step = 'credentials';
                this.$nextTick(() => this.$refs.employeeId?.focus());
            },
            async submit() {
                if (this.loading) return;
                this.error = '';
                this.notice = '';
                this.loading = true;
                try {
                    const result = await this.$store.session.login(this.employeeId.trim(), this.password, this.mfaCode || null);
                    // The scope chosen in step 1 must be the account's own. The
                    // server scopes the data either way; this tells the officer
                    // which door they are actually signing in through.
                    if (result.scope && this.scope && result.scope !== this.scope) {
                        const chosen = this.scope;
                        await this.$store.session.logout();
                        this.scope = result.scope;
                        this.mfaCode = '';
                        this.step = 'credentials';
                        this.loading = false;
                        this.error = 'This account is assigned to ' + this.scopeLabel(result.scope) + ', not ' + this.scopeLabel(chosen) + '. The scope has been changed above; press Sign In to continue.';
                        return;
                    }
                    if (result.mfaRequired) {
                        this.step = 'mfa';
                        this.loading = false;
                        this.$nextTick(() => this.$refs.mfaCode?.focus());
                        return;
                    }
                    window.location.href = '{{ route('admin.dashboard') }}';
                } catch (err) {
                    this.loading = false;
                    this.error = err.explain ? err.explain('en') : 'Sign in failed.';
                    if (err.code === 'MFA_CODE_INVALID') {
                        this.mfaCode = '';
                        this.$nextTick(() => this.$refs.mfaCode?.focus());
                    }
                }
            },
            // A new code: signing in again without one sends a fresh code
            // (a wrong code no longer does, so the one being typed stays good).
            async resendCode() {
                if (this.loading) return;
                this.error = '';
                this.notice = '';
                this.mfaCode = '';
                this.loading = true;
                try {
                    await this.$store.session.login(this.employeeId.trim(), this.password, null);
                    this.notice = 'A new code has been sent. Codes sent earlier no longer work.';
                    this.$nextTick(() => this.$refs.mfaCode?.focus());
                } catch (err) {
                    this.error = err.explain ? err.explain('en') : 'Could not send a new code.';
                } finally {
                    this.loading = false;
                }
            },
        }"
    >
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-slate-600 mb-4 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>

        <div class="flex items-center justify-between animate-fade-up">
            <div class="w-10 h-10 rounded-xl bg-navy-900 text-white flex items-center justify-center shadow-card">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-navy-700 bg-slate-100 border border-slate-200 rounded-full px-2.5 py-1">
                <i data-lucide="lock" class="w-3 h-3"></i> Restricted Access
            </span>
        </div>

        <h1 class="text-xl font-semibold text-ink mt-3 animate-fade-up" style="animation-delay: 40ms">LGU Personnel Login</h1>
        <p class="text-ink-muted text-sm mt-1 animate-fade-up" style="animation-delay: 60ms" x-text="step === 'scope' ? 'First, select your assigned access scope.' : (step === 'mfa' ? 'Enter the 6-digit code sent to confirm it is you.' : (!scope ? 'Sign in with your Employee ID and password.' : 'Signing in to ' + (scope === 'Municipal' ? 'Municipal-wide access.' : 'Brgy. ' + scope + '.')))"></p>

        <!-- Step 1: choose access scope (informational only — the server returns the account's real scope) -->
        <div x-show="step === 'scope'" x-transition:leave="transition duration-[150ms] ease-exit" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mt-5 animate-fade-up" style="animation-delay: 90ms">
            <button
                type="button" @click="chooseScope('Municipal')"
                class="w-full flex items-center gap-2.5 text-left rounded-2xl border border-slate-200 bg-white hover:border-navy-300 hover:bg-navy-50/40 hover:-translate-y-0.5 px-3.5 py-3 text-sm shadow-sm hover:shadow-card transition-all duration-[220ms] ease-standard mb-2.5"
            >
                <span class="w-8 h-8 rounded-lg bg-navy-50 text-navy-700 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                <span>
                    <span class="block text-xs font-semibold text-ink">Municipal-wide</span>
                    <span class="block text-[10.5px] text-ink-muted">Municipal Hall departments &amp; offices</span>
                </span>
            </button>

            <div class="relative mb-2.5">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input aria-label="Search your barangay" type="text" x-model="scopeSearch" placeholder="Search your barangay..." class="w-full pl-9 pr-3 py-2.5 text-sm rounded-2xl focus:border-navy-300 focus:ring-4 focus:ring-navy-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-brand-100">
            </div>
            <div class="grid grid-cols-2 gap-1.5 max-h-56 overflow-y-auto scrollbar-thin pr-0.5">
                <template x-for="b in filteredScopes" :key="b">
                    <button
                        type="button" @click="chooseScope(b)"
                        class="flex items-center gap-1.5 text-left rounded-2xl border border-slate-200 bg-white hover:border-navy-300 hover:bg-navy-50/40 hover:-translate-y-0.5 px-3 py-2.5 text-xs font-medium text-slate-600 hover:text-navy-800 shadow-sm hover:shadow-card transition-all duration-[220ms] ease-standard"
                    >
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-ink-muted shrink-0"></i><span class="truncate" x-text="b"></span>
                    </button>
                </template>
                <div class="col-span-2 flex flex-col items-center text-center py-6" x-show="filteredScopes.length === 0">
                    <span class="w-9 h-9 rounded-full bg-slate-100 text-ink-muted flex items-center justify-center mb-2"><i data-lucide="search-x" class="w-4 h-4"></i></span>
                    <p class="text-[11px] text-ink-muted">No barangay matches "<span x-text="scopeSearch"></span>".</p>
                </div>
            </div>
        </div>

        <!-- Step 2: credentials -->
        <div x-show="step === 'credentials'" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="mt-5">
            <button
                type="button" x-show="scope" x-cloak @click="step = 'scope'; scope = ''; scopeSearch = ''"
                class="w-full flex items-center justify-between gap-2 rounded-2xl border border-navy-200 bg-navy-50/60 px-3.5 py-2.5 text-left hover:bg-navy-50 transition-colors mb-3.5 animate-fade-up"
            >
                <span class="flex items-center gap-2 text-sm font-medium text-navy-800 min-w-0">
                    <i :data-lucide="scope === 'Municipal' ? 'landmark' : 'map-pin'" class="w-4 h-4 shrink-0"></i>
                    <span class="truncate" x-text="scope === 'Municipal' ? 'Municipal-wide' : 'Brgy. ' + scope"></span>
                </span>
                <span class="text-[11px] font-medium text-navy-600 flex items-center gap-0.5 shrink-0">Change <i data-lucide="chevron-right" class="w-3 h-3"></i></span>
            </button>

            <form class="space-y-3.5" @submit.prevent="submit">
                <div class="animate-fade-up" style="animation-delay: 40ms">
                    <x-ui.input name="employee_id" type="text" label="Employee ID" icon="badge-check" placeholder="SA-001" class="font-mono uppercase" x-model="employeeId" x-ref="employeeId" required />
                </div>

                <div class="animate-fade-up" style="animation-delay: 80ms" x-data="passwordField">
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-ink-muted pointer-events-none"></i>
                        <input
                            :type="visible ? 'text' : 'password'"
                            name="password" id="password" placeholder="••••••••" required
                            x-model="password"
                            class="w-full rounded-2xl border-0 surface-pressed py-2.5 pl-10 pr-11 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white focus:border-navy-400 focus:ring-4 focus:ring-navy-100 outline-none transition-all duration-[160ms] ease-standard"
                        >
                        <button type="button" @click="toggle" :aria-pressed="visible ? 'true' : 'false'" :aria-label="visible ? 'Hide password' : 'Show password'" class="absolute right-1.5 top-1/2 -translate-y-1/2 w-8 h-8 grid place-items-center rounded-xl text-ink-muted hover:text-slate-600 focus-visible:ring-4 focus-visible:ring-brand-100 outline-none transition-colors">
                            <i data-lucide="eye" class="w-[18px] h-[18px]" x-show="!visible"></i>
                            <i data-lucide="eye-off" class="w-[18px] h-[18px]" x-show="visible" x-cloak></i>
                        </button>
                    </div>
                </div>

                <div x-show="error" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="flex items-center gap-2.5 rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-2.5">
                    <i data-lucide="circle-alert" class="w-4 h-4 text-rose-500 shrink-0"></i>
                    <p class="text-xs text-rose-600" x-text="error"></p>
                </div>

                <button
                    type="submit"
                    class="btn-shine w-full inline-flex items-center justify-center gap-2 min-h-[48px] px-6 rounded-2xl bg-gradient-to-b from-navy-800 to-navy-900 text-white py-3.5 text-sm font-medium select-none shadow-card hover:shadow-card-hover transition-all duration-[160ms] ease-standard focus:outline-none focus-visible:ring-4 focus-visible:ring-slate-300 active:duration-[80ms] active:scale-[0.985] disabled:opacity-50 disabled:pointer-events-none animate-fade-up"
                    style="animation-delay: 120ms"
                    :disabled="loading"
                >
                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Verifying credentials…' : 'Sign in'"></span>
                    <i data-lucide="arrow-right" class="w-4 h-4" x-show="!loading"></i>
                </button>
            </form>
        </div>

        <!-- Step 3: MFA challenge (super_admin, municipal_admin — see PersonnelAccount::mfaRequired()) -->
        <div x-show="step === 'mfa'" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="mt-5">
            <form class="space-y-3.5" @submit.prevent="submit">
                <x-ui.input name="mfa_code" type="text" label="6-digit code" icon="shield-check" placeholder="000000" class="font-mono text-center tracking-[0.4em]" x-model="mfaCode" x-ref="mfaCode" maxlength="6" required />

                <div x-show="error" x-cloak class="flex items-center gap-2.5 rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-2.5">
                    <i data-lucide="circle-alert" class="w-4 h-4 text-rose-500 shrink-0"></i>
                    <p class="text-xs text-rose-600" x-text="error"></p>
                </div>
                <div x-show="notice" x-cloak class="flex items-center gap-2.5 rounded-xl bg-emerald-50 border border-emerald-100 px-3.5 py-2.5">
                    <i data-lucide="mail-check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <p class="text-xs text-emerald-700" x-text="notice"></p>
                </div>

                <button
                    type="submit"
                    class="btn-shine w-full inline-flex items-center justify-center gap-2 min-h-[48px] px-6 rounded-2xl bg-gradient-to-b from-navy-800 to-navy-900 text-white py-3.5 text-sm font-medium select-none shadow-card hover:shadow-card-hover transition-all duration-[160ms] ease-standard disabled:opacity-50 disabled:pointer-events-none"
                    :disabled="loading"
                >
                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Verifying…' : 'Verify and sign in'"></span>
                </button>
                <p class="text-center text-xs text-ink-muted">
                    Didn't get it, or it expired?
                    <button type="button" @click="resendCode()" :disabled="loading" class="font-medium text-navy-700 hover:underline disabled:opacity-50">Send a new code</button>
                </p>
            </form>
        </div>

        <p class="text-center text-xs text-ink-muted mt-4">
            Personnel accounts cannot self-register — contact the ICT Office for access.
        </p>
    </div>
</x-layouts.guest>
