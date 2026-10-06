<x-layouts.guest title="Citizen Login" image="esperanza-community.jpg" fullscreen>
    <div
        x-data="{
            identifier: '',
            password: '',
            loading: false,
            error: '',
            async submit() {
                if (this.loading) return;
                this.error = '';
                this.loading = true;
                try {
                    await this.$store.citizenSession.login(this.identifier.trim(), this.password);
                    window.location.href = '{{ route('citizen.dashboard') }}';
                } catch (err) {
                    this.error = err.explain ? err.explain($store.lang.current) : ($store.lang.current === 'en' ? 'Sign in failed.' : 'Hindi ma-sign in.');
                } finally {
                    this.loading = false;
                }
            },
        }"
    >
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-600 mb-6 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> <x-ui.t fil="Bumalik" en="Back" />
        </a>

        <div class="flex items-center justify-between animate-fade-up">
            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                <i data-lucide="user" class="w-5 h-5"></i>
            </div>
            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-brand-700 bg-brand-50 border border-brand-100 rounded-full px-2.5 py-1">
                <i data-lucide="shield-check" class="w-3 h-3"></i> <x-ui.t fil="Access ng Mamamayan" en="Citizen Access" />
            </span>
        </div>

        <h1 class="text-2xl font-semibold text-navy-900 mt-3 animate-fade-up" style="animation-delay: 40ms"><x-ui.t fil="Maligayang Pagbabalik" en="Welcome back" /></h1>
        <p class="text-slate-500 text-sm mt-1.5 animate-fade-up" style="animation-delay: 60ms"><x-ui.t fil="Mag-sign in gamit ang iyong resident ID, email, o numero ng mobile." en="Sign in with your resident ID, email, or mobile number." /></p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <div class="animate-fade-up" style="animation-delay: 90ms">
                <x-ui.input name="identifier" type="text" label="Resident ID, Email, or Mobile" label-fil="Resident ID, Email, o Mobile" icon="user" placeholder="ESP-RES-2026-000123" x-model="identifier" x-ref="identifier" required />
            </div>

            <div class="animate-fade-up" style="animation-delay: 120ms" x-data="passwordField">
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Password" en="Password" /></label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-slate-500 pointer-events-none"></i>
                    <input
                        :type="visible ? 'text' : 'password'"
                        name="password" id="password" placeholder="••••••••" required
                        x-model="password"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-11 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                    >
                    <button type="button" @click="toggle" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-600 transition-colors">
                        <i data-lucide="eye" class="w-[18px] h-[18px]" x-show="!visible"></i>
                        <i data-lucide="eye-off" class="w-[18px] h-[18px]" x-show="visible" x-cloak></i>
                    </button>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <label class="flex items-center gap-2 text-sm text-slate-500">
                        <input type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                        <x-ui.t fil="Tandaan ako" en="Remember me" />
                    </label>
                    <a href="{{ route('citizen.password-reset') }}" class="text-sm text-brand-600 hover:underline"><x-ui.t fil="Nakalimutan ang password?" en="Forgot password?" /></a>
                </div>
            </div>

            <div x-show="error" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="flex items-center gap-2.5 rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-2.5">
                <i data-lucide="circle-alert" class="w-4 h-4 text-rose-500 shrink-0"></i>
                <p class="text-xs text-rose-600" x-text="error"></p>
            </div>

            <x-ui.button
                type="submit"
                size="lg"
                class="w-full animate-fade-up"
                style="animation-delay: 150ms"
                x-bind:disabled="loading"
            >
                <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="$store.lang.current === 'en' ? (loading ? 'Signing in…' : 'Sign In') : (loading ? 'Nagsa-sign in…' : 'Mag-sign In')"></span>
            </x-ui.button>
        </form>

        <p class="text-center text-sm text-slate-500 mt-4">
            <x-ui.t fil="Wala ka pang account?" en="Don't have an account?" /> <a href="{{ route('citizen.register') }}" class="text-brand-600 font-medium hover:underline"><x-ui.t fil="Magrehistro rito" en="Register here" /></a>
        </p>
    </div>
</x-layouts.guest>
