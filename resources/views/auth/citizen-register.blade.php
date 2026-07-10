@php
    $barangays = config('esperanza.barangays');
@endphp
<x-layouts.guest title="Create Account" image="esperanza_community1.jpg" fullscreen>
    <div x-data="authForm('{{ route('citizen.dashboard') }}')">
        <a href="{{ route('citizen.login') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-600 mb-6 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to login
        </a>

        <h1 class="text-2xl font-semibold text-navy-900">Create your Citizen Account</h1>
        <p class="text-slate-500 text-sm mt-1.5">Register to request documents and access municipal services online.</p>

        <form class="mt-7 space-y-4" @submit.prevent="submit">
            <div class="grid grid-cols-2 gap-4">
                <x-ui.input name="first_name" label="First Name" placeholder="Maria" required />
                <x-ui.input name="last_name" label="Last Name" placeholder="Bacaltos" required />
            </div>

            <div>
                <label for="barangay" class="block text-sm font-medium text-slate-700 mb-1.5">Barangay</label>
                <div class="relative">
                    <i data-lucide="map-pin" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-slate-400 pointer-events-none"></i>
                    <select id="barangay" name="barangay" required class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200 appearance-none">
                        <option value="">Select your barangay</option>
                        @foreach($barangays as $brgy)
                            <option value="{{ $brgy }}">{{ $brgy }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            <x-ui.input name="email" type="email" label="Email Address" icon="mail" placeholder="maria.bacaltos@email.com" required />
            <x-ui.input name="mobile" type="text" label="Mobile Number" icon="phone" placeholder="09XX XXX XXXX" required />
            <x-ui.input name="password" type="password" label="Password" icon="lock" placeholder="Create a password" required />

            <label class="flex items-start gap-2.5 text-xs text-slate-500 pt-1">
                <input type="checkbox" required class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                I agree to the Terms of Service and Data Privacy policy of the Municipality of Esperanza.
            </label>

            <button
                type="submit"
                class="btn-shine w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-b from-brand-500 to-brand-600 text-white py-3.5 text-sm font-medium shadow-card hover:shadow-card-hover transition-all duration-200 active:scale-[0.98] disabled:opacity-70"
                :disabled="loading"
            >
                <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Creating your account…' : 'Create Account'"></span>
            </button>
        </form>
    </div>
</x-layouts.guest>
