<x-layouts.guest title="LGU Personnel Login" image="rectangle_cityhall.jpg" fullscreen>
    <div x-data="authForm('{{ route('admin.dashboard') }}')">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-600 mb-4 transition-colors">
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

        <h1 class="text-xl font-semibold text-navy-900 mt-3 animate-fade-up" style="animation-delay: 40ms">LGU Personnel Login</h1>
        <p class="text-slate-500 text-sm mt-1 animate-fade-up" style="animation-delay: 60ms">Restricted to authorized municipal staff. Accounts are provisioned by the ICT Office.</p>

        <form class="mt-5 space-y-3.5" @submit.prevent="submit">
            <div class="animate-fade-up" style="animation-delay: 100ms">
                <x-ui.input name="employee_id" type="text" label="Employee ID or Email" icon="badge-check" placeholder="ESP-2026-0142" class="font-mono" required />
            </div>

            <div class="animate-fade-up" style="animation-delay: 150ms" x-data="passwordField">
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-slate-400 pointer-events-none"></i>
                    <input
                        :type="visible ? 'text' : 'password'"
                        name="password" id="password" placeholder="••••••••" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-11 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-navy-400 focus:ring-4 focus:ring-navy-100 outline-none transition-all duration-200"
                    >
                    <button type="button" @click="toggle" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                        <i data-lucide="eye" class="w-[18px] h-[18px]" x-show="!visible"></i>
                        <i data-lucide="eye-off" class="w-[18px] h-[18px]" x-show="visible" x-cloak></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2.5 rounded-xl bg-slate-50 border border-slate-100 px-3.5 py-2.5 animate-fade-up" style="animation-delay: 190ms">
                <i data-lucide="smartphone" class="w-4 h-4 text-slate-400 shrink-0"></i>
                <p class="text-xs text-slate-400">Two-factor verification will be requested after password sign-in.</p>
            </div>

            <button
                type="submit"
                class="btn-shine w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-b from-navy-800 to-navy-900 text-white py-3 text-sm font-medium shadow-card hover:shadow-card-hover transition-all duration-200 active:scale-[0.98] disabled:opacity-70 animate-fade-up"
                style="animation-delay: 230ms"
                :disabled="loading"
            >
                <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Verifying credentials…' : 'Sign In'"></span>
                <i data-lucide="arrow-right" class="w-4 h-4" x-show="!loading"></i>
            </button>
        </form>

        <p class="text-center text-xs text-slate-400 mt-4">
            Personnel accounts cannot self-register — contact the ICT Office for access.
        </p>
    </div>
</x-layouts.guest>
