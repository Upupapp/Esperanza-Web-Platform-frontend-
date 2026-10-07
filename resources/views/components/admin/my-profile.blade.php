{{-- My Profile: the officer's own account and password (live test 2026-10-07, PR-8).

     "My Profile" used to answer with a toast ("a frontend preview"), and staff
     had no way to change their own password: every staff password stayed one
     an administrator chose. Opened from the profile menu (open-my-profile), and
     opened by itself, unable to close, while the server says the password was
     set by an administrator (must_change_password, or a PASSWORD_CHANGE_REQUIRED
     answer from any call). Staff site: English only, by the owner's ruling. --}}
<div
    x-data="{
        open: false,
        forced: false,
        form: { current_password: '', password: '', password_confirmation: '' },
        show: { current: false, next: false },
        saving: false,
        error: '',
        done: false,
        get account() { return $store.session.account || {}; },
        get rules() {
            const p = this.form.password;
            return [
                { ok: p.length >= 8, text: 'At least 8 characters' },
                { ok: /[A-Z]/.test(p), text: 'An uppercase letter' },
                { ok: /[a-z]/.test(p), text: 'A lowercase letter' },
                { ok: /[0-9]/.test(p), text: 'A number' },
                { ok: /[^A-Za-z0-9]/.test(p), text: 'A special character' },
            ];
        },
        get canSave() {
            return !this.saving && this.form.current_password !== '' && this.rules.every((r) => r.ok)
                && this.form.password === this.form.password_confirmation && this.form.password !== this.form.current_password;
        },
        openFor(forced) {
            this.forced = !!forced;
            this.error = '';
            this.done = false;
            this.form = { current_password: '', password: '', password_confirmation: '' };
            this.open = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        close() { if (!this.forced) this.open = false; },
        async save() {
            if (!this.canSave) return;
            this.saving = true;
            this.error = '';
            try {
                await $api.put('/admin/me/password', this.form);
                this.done = true;
                if (this.forced) {
                    await $store.session.refresh().catch(() => {});
                    window.location.reload();
                    return;
                }
                this.form = { current_password: '', password: '', password_confirmation: '' };
                $dispatch('toast', { message: 'Password changed. Your other sessions were signed out.', variant: 'success' });
            } catch (err) {
                this.error = err.explain ? err.explain('en') : 'The password could not be changed.';
            } finally {
                this.saving = false;
            }
        },
        init() {
            if ($store.session.account?.mustChangePassword) this.openFor(true);
        },
    }"
    @open-my-profile.window="openFor(false)"
    @password-change-required.window="openFor(true)"
>
    <template x-teleport="body">
        <div x-show="open" x-cloak data-modal-layer style="position: fixed; inset: 0; z-index: 95;" @keydown.escape.window="close()">
            <div class="absolute inset-0 bg-navy-950/60 backdrop-blur-sm" @click="close()"></div>
            <div class="relative mx-auto mt-[8vh] w-[min(34rem,calc(100%-2rem))] max-h-[84vh] overflow-y-auto rounded-2xl bg-white shadow-float"
                 role="dialog" aria-modal="true" aria-labelledby="my-profile-title">
                <div class="flex items-start justify-between gap-3 px-6 pt-5 pb-4 border-b border-brand-600/10">
                    <div>
                        <h2 id="my-profile-title" class="text-base font-semibold text-ink" x-text="forced ? 'Choose your own password' : 'My Profile'"></h2>
                        <p class="text-xs text-ink-muted mt-0.5" x-show="forced">Your password was set by an administrator. Choose your own before you continue.</p>
                    </div>
                    <button type="button" x-show="!forced" @click="close()" aria-label="Close" class="p-1.5 rounded-xl text-ink-faint hover:text-slate-600 hover:bg-white/60">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm" x-show="!forced">
                        <dt class="text-ink-muted">Name</dt><dd class="text-ink font-medium" x-text="account.name || '—'"></dd>
                        <dt class="text-ink-muted">Employee ID</dt><dd class="text-ink font-mono" x-text="account.id || '—'"></dd>
                        <dt class="text-ink-muted">Role</dt><dd class="text-ink" x-text="account.role || '—'"></dd>
                        <dt class="text-ink-muted">Office</dt><dd class="text-ink" x-text="account.dept || '—'"></dd>
                        <dt class="text-ink-muted">Scope</dt><dd class="text-ink" x-text="account.scope === 'Municipal' ? 'Municipal-wide' : (account.scope ? 'Brgy. ' + account.scope : '—')"></dd>
                    </dl>
                    <p class="text-[11px] text-ink-muted" x-show="!forced">To change your name, office or role, ask your Super Administrator (User Management).</p>

                    <form class="space-y-3" @submit.prevent="save()">
                        <h3 class="text-sm font-semibold text-ink" x-show="!forced">Change password</h3>
                        <div>
                            <label for="mp-current" class="block text-xs font-medium text-slate-700 mb-1" x-text="forced ? 'Password you were given' : 'Current password'"></label>
                            <div class="relative">
                                <input id="mp-current" :type="show.current ? 'text' : 'password'" x-model="form.current_password" autocomplete="current-password"
                                       class="w-full rounded-2xl border-0 surface-pressed py-2.5 pl-4 pr-10 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <button type="button" @click="show.current = !show.current" :aria-label="show.current ? 'Hide password' : 'Show password'" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-ink-faint hover:text-slate-600"><i data-lucide="eye" class="w-4 h-4"></i></button>
                            </div>
                        </div>
                        <div>
                            <label for="mp-new" class="block text-xs font-medium text-slate-700 mb-1">New password</label>
                            <div class="relative">
                                <input id="mp-new" :type="show.next ? 'text' : 'password'" x-model="form.password" autocomplete="new-password"
                                       class="w-full rounded-2xl border-0 surface-pressed py-2.5 pl-4 pr-10 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <button type="button" @click="show.next = !show.next" :aria-label="show.next ? 'Hide password' : 'Show password'" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-ink-faint hover:text-slate-600"><i data-lucide="eye" class="w-4 h-4"></i></button>
                            </div>
                            <ul class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-[11px]">
                                <template x-for="r in rules" :key="r.text">
                                    <li class="flex items-center gap-1.5" :class="r.ok ? 'text-emerald-700' : 'text-ink-muted'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="r.ok ? 'bg-emerald-500' : 'bg-slate-300'"></span><span x-text="r.text"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <div>
                            <label for="mp-confirm" class="block text-xs font-medium text-slate-700 mb-1">Confirm new password</label>
                            <input id="mp-confirm" type="password" x-model="form.password_confirmation" autocomplete="new-password"
                                   class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <p class="text-[11px] text-rose-600 mt-1" x-show="form.password_confirmation !== '' && form.password !== form.password_confirmation" x-cloak>The two new passwords do not match.</p>
                            <p class="text-[11px] text-rose-600 mt-1" x-show="form.password !== '' && form.password === form.current_password" x-cloak>The new password must be different.</p>
                        </div>
                        <p class="text-xs text-rose-600 bg-rose-50 rounded-lg px-3 py-2" x-show="error" x-text="error" role="alert"></p>
                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button type="button" x-show="!forced" @click="close()" class="px-4 py-2 text-sm rounded-2xl text-slate-600 hover:bg-white/60">Close</button>
                            <button type="button" x-show="forced" @click="$store.session.logout().finally(() => window.location.href = '{{ route('admin.login') }}')" class="px-4 py-2 text-sm rounded-xl text-slate-600 hover:bg-white/60">Sign out</button>
                            <button type="submit" :disabled="!canSave" class="px-4 py-2 text-sm font-medium rounded-2xl clay-blue hover:brightness-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                    x-text="saving ? 'Saving…' : 'Change password'"></button>
                        </div>
                        <p class="text-[11px] text-ink-muted">Changing it signs out your other sessions; this one stays.</p>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
