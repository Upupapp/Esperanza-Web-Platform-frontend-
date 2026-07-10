<x-layouts.guest title="Citizen Login" image="esperanza-community.jpg" fullscreen>
    <div x-data="authForm('{{ route('citizen.dashboard') }}')">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-600 mb-4 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>

        <div class="flex items-center justify-between animate-fade-up">
            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                <i data-lucide="user" class="w-5 h-5"></i>
            </div>
            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-brand-700 bg-brand-50 border border-brand-100 rounded-full px-2.5 py-1">
                <i data-lucide="shield-check" class="w-3 h-3"></i> Citizen Access
            </span>
        </div>

        <h1 class="text-xl font-semibold text-navy-900 mt-3 animate-fade-up" style="animation-delay: 40ms">Welcome back</h1>
        <p class="text-slate-500 text-sm mt-1 animate-fade-up" style="animation-delay: 60ms">Sign in to access document requests, assistance programs, and announcements.</p>

        <form class="mt-5 space-y-3.5" @submit.prevent="submit">
            <div class="animate-fade-up" style="animation-delay: 100ms">
                <x-ui.input name="email" type="email" label="Email or Mobile Number" icon="mail" placeholder="juan.delacruz@email.com" required />
            </div>

            <div class="animate-fade-up" style="animation-delay: 150ms" x-data="passwordField">
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-slate-400 pointer-events-none"></i>
                    <input
                        :type="visible ? 'text' : 'password'"
                        name="password" id="password" placeholder="••••••••" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-11 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200"
                    >
                    <button type="button" @click="toggle" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                        <i data-lucide="eye" class="w-[18px] h-[18px]" x-show="!visible"></i>
                        <i data-lucide="eye-off" class="w-[18px] h-[18px]" x-show="visible" x-cloak></i>
                    </button>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <label class="flex items-center gap-2 text-sm text-slate-500">
                        <input type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                        Remember me
                    </label>
                    <a href="#" class="text-sm text-brand-600 hover:underline">Forgot password?</a>
                </div>
            </div>

            <button
                type="submit"
                class="btn-shine w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-b from-brand-500 to-brand-600 text-white py-3 text-sm font-medium shadow-card hover:shadow-card-hover transition-all duration-200 active:scale-[0.98] disabled:opacity-70 animate-fade-up"
                style="animation-delay: 200ms"
                :disabled="loading"
            >
                <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Signing in…' : 'Sign In'"></span>
            </button>
        </form>

        <p class="text-center text-sm text-slate-400 mt-4">
            Don't have an account? <a href="{{ route('citizen.register') }}" class="text-brand-600 font-medium hover:underline">Register here</a>
        </p>
    </div>
</x-layouts.guest>
