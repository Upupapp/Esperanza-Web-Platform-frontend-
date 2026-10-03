<x-layouts.guest title="Reset Password" image="esperanza-community.jpg" fullscreen>
    <div
        x-data="{
            step: 'request',
            identifier: '',
            code: '',
            password: '',
            passwordConfirmation: '',
            loading: false,
            sent: false,
            error: '',
            async requestCode() {
                if (this.loading) return;
                this.error = '';
                this.loading = true;
                try {
                    await $api.post('/auth/citizen/password/forgot', { identifier: this.identifier.trim() });
                    this.sent = true;
                    this.step = 'reset';
                } catch (err) {
                    this.error = err.localised ? err.localised($store.lang.current) : 'Request failed.';
                } finally {
                    this.loading = false;
                }
            },
            async resetPassword() {
                if (this.loading) return;
                this.error = '';
                this.loading = true;
                try {
                    await $api.post('/auth/citizen/password/reset', {
                        identifier: this.identifier.trim(), code: this.code.trim(),
                        password: this.password, password_confirmation: this.passwordConfirmation,
                    });
                    this.step = 'done';
                } catch (err) {
                    this.error = err.localised ? err.localised($store.lang.current) : 'Reset failed.';
                } finally {
                    this.loading = false;
                }
            },
        }"
    >
        <a href="{{ route('citizen.login') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-600 mb-6 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> <x-ui.t fil="Bumalik sa login" en="Back to login" />
        </a>

        <h1 class="text-2xl font-semibold text-navy-900"><x-ui.t fil="I-reset ang Password" en="Reset your password" /></h1>
        <p class="text-slate-500 text-sm mt-1.5" x-show="step === 'request'"><x-ui.t fil="Ilagay ang iyong resident ID, email, o mobile. Magpapadala kami ng code kung verified ang account." en="Enter your resident ID, email, or mobile. We'll send a code if the account is verified." /></p>
        <p class="text-slate-500 text-sm mt-1.5" x-show="step === 'reset'"><x-ui.t fil="Ilagay ang code at ang iyong bagong password." en="Enter the code and your new password." /></p>

        <form x-show="step === 'request'" class="mt-6 space-y-4" @submit.prevent="requestCode">
            <x-ui.input name="identifier" type="text" label="Resident ID, Email, or Mobile" icon="user" x-model="identifier" required />
            <div x-show="error" x-cloak class="flex items-center gap-2.5 rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-2.5">
                <i data-lucide="circle-alert" class="w-4 h-4 text-rose-500 shrink-0"></i>
                <p class="text-xs text-rose-600" x-text="error"></p>
            </div>
            <x-ui.button type="submit" size="lg" class="w-full" x-bind:disabled="loading">
                <span x-text="loading ? 'Sending…' : 'Send Code'"></span>
            </x-ui.button>
        </form>

        <form x-show="step === 'reset'" x-cloak class="mt-6 space-y-4" @submit.prevent="resetPassword">
            <div x-show="sent" class="flex items-center gap-2.5 rounded-xl bg-emerald-50 border border-emerald-100 px-3.5 py-2.5 mb-1">
                <i data-lucide="mail-check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <p class="text-xs text-emerald-700"><x-ui.t fil="Kung verified ang account, may naipadala nang code." en="If the account is verified, a code has been sent." /></p>
            </div>
            <x-ui.input name="code" type="text" label="6-digit code" icon="shield-check" class="font-mono text-center tracking-[0.4em]" x-model="code" maxlength="6" required />
            <x-ui.input name="password" type="password" label="New Password" icon="lock" x-model="password" required />
            <x-ui.input name="password_confirmation" type="password" label="Confirm New Password" icon="lock" x-model="passwordConfirmation" required />
            <div x-show="error" x-cloak class="flex items-center gap-2.5 rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-2.5">
                <i data-lucide="circle-alert" class="w-4 h-4 text-rose-500 shrink-0"></i>
                <p class="text-xs text-rose-600" x-text="error"></p>
            </div>
            <x-ui.button type="submit" size="lg" class="w-full" x-bind:disabled="loading">
                <span x-text="loading ? 'Resetting…' : 'Reset Password'"></span>
            </x-ui.button>
        </form>

        <div x-show="step === 'done'" x-cloak class="mt-6 text-center py-6">
            <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="check" class="w-6 h-6"></i>
            </div>
            <p class="text-sm font-medium text-navy-900 mb-1"><x-ui.t fil="Na-reset na ang password" en="Password reset" /></p>
            <p class="text-xs text-slate-500 mb-4"><x-ui.t fil="Puwede ka nang mag-sign in gamit ang bagong password." en="You can now sign in with your new password." /></p>
            <a href="{{ route('citizen.login') }}" class="text-sm font-medium text-brand-600 hover:underline"><x-ui.t fil="Bumalik sa Sign In" en="Back to Sign In" /></a>
        </div>
    </div>
</x-layouts.guest>
