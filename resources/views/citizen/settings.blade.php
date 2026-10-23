<x-layouts.citizen title="Settings" subtitle="Manage your account preferences." active="settings">
    <div
        x-data="{
            tab: 'notifications',
            notifPrefs: [],
            notifPrefsLoading: true,
            savingPassword: false,
            passwordForm: { current_password: '', password: '', password_confirmation: '' },
            // Data-subject requests (RA 10173): the four kinds the backend takes
            // (CitizenPortalController::DATA_REQUEST_KINDS), the resident's own
            // words, and their history -- the same as mobile's My Data Requests.
            dataKinds: [
                { key: 'access', fil: 'Tingnan ang aking datos', en: 'See my data' },
                { key: 'correction', fil: 'Itama ang aking datos', en: 'Correct my data' },
                { key: 'erasure', fil: 'Burahin ang aking datos', en: 'Delete my data' },
                { key: 'portability', fil: 'Kumuha ng kopya', en: 'Get a copy' },
            ],
            dataForm: { kind: 'access', detail: '' },
            dataRequests: [],
            dataRequestsLoading: true,
            requestingData: false,

            async init() {
                this.notifPrefsLoading = true;
                try {
                    const result = await $api.get('/citizen/notification-preferences');
                    this.notifPrefs = result.data?.preferences || [];
                    this.smsAvailable = result.data?.sms_available !== false;
                } catch { this.notifPrefs = []; } finally { this.notifPrefsLoading = false; }
                this.loadDataRequests();
            },
            // One switch per channel. The old single switch set in-app, SMS and
            // email together off the in-app flag -- which the server always
            // delivers anyway -- so a resident could not choose texts without
            // email. In-app is not a choice (backend B3); SMS and email are.
            // in_app is still sent as true: a backend from before B3 requires it.
            // No SMS gateway yet: the SMS switches are greyed out and say so,
            // instead of offering a channel that sends nothing (live test
            // 2026-10-07, PR-10). The server sends email in their place.
            smsAvailable: true,
            async togglePref(pref, channel) {
                if (channel === 'sms' && !this.smsAvailable) return;
                const next = !pref[channel];
                pref[channel] = next;
                try {
                    await $api.put('/citizen/notification-preferences', {
                        preferences: this.notifPrefs.map((p) => ({ category: p.category, in_app: true, sms: !!p.sms, email: !!p.email })),
                    });
                } catch (err) {
                    pref[channel] = !next;
                    this.$dispatch('toast', { message: err.localised ? err.localised(this.$store.lang.current) : 'Could not save preference.', variant: 'error' });
                }
            },
            kindLabel(key) {
                // Filed from Edit Profile, with its proof (2026-10-16).
                if (key === 'name_change') return this.$store.lang.current === 'en' ? 'Change my name' : 'Palitan ang aking pangalan';
                const kind = this.dataKinds.find((k) => k.key === key);
                if (!kind) return key;
                return this.$store.lang.current === 'en' ? kind.en : kind.fil;
            },
            async loadDataRequests() {
                this.dataRequestsLoading = true;
                try {
                    const result = await $api.get('/citizen/data-requests');
                    this.dataRequests = result.data || [];
                } catch { this.dataRequests = []; } finally { this.dataRequestsLoading = false; }
            },
            async updatePassword() {
                if (this.savingPassword) return;
                this.savingPassword = true;
                try {
                    await $api.put('/citizen/password', this.passwordForm);
                    this.passwordForm = { current_password: '', password: '', password_confirmation: '' };
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Password updated.' : 'Na-update ang password.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised(this.$store.lang.current) : 'Could not update password.', variant: 'error' });
                } finally {
                    this.savingPassword = false;
                }
            },
            async requestData() {
                if (this.requestingData || this.dataForm.detail.trim() === '') return;
                this.requestingData = true;
                try {
                    const result = await $api.post('/citizen/data-requests', { kind: this.dataForm.kind, detail: this.dataForm.detail.trim() });
                    this.dataRequests = [result.data, ...this.dataRequests];
                    this.dataForm = { kind: this.dataForm.kind, detail: '' };
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Request filed as ' + result.data.ref + '. The LGU will respond by email or mobile.' : 'Naisampa ang hiling bilang ' + result.data.ref + '. Sasagutin ito ng LGU sa email o mobile.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised(this.$store.lang.current) : 'Could not submit request.', variant: 'error' });
                } finally {
                    this.requestingData = false;
                }
            },
        }"
        class="animate-fade-up grid grid-cols-1 lg:grid-cols-4 gap-5"
    >

        <div class="lg:col-span-1">
            <x-ui.card padded="false" class="p-2">
                <nav class="space-y-0.5">
                    @foreach(['notifications' => ['bell', 'Mga Abiso', 'Notifications'], 'security' => ['shield', 'Seguridad', 'Security'], 'privacy' => ['lock', 'Pribasiya', 'Privacy'], 'preferences' => ['sliders-horizontal', 'Kagustuhan', 'Preferences']] as $key => [$icon, $labelFil, $labelEn])
                        <button
                            @click="tab = '{{ $key }}'"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-colors"
                            :class="tab === '{{ $key }}' ? 'bg-brand-50 text-brand-700' : 'text-ink-muted hover:bg-white/60'"
                        >
                            <i data-lucide="{{ $icon }}" class="w-4 h-4"></i><x-ui.t :fil="$labelFil" :en="$labelEn" />
                        </button>
                    @endforeach
                </nav>
            </x-ui.card>
        </div>

        <div class="lg:col-span-3">
            <div x-show="tab === 'notifications'">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink mb-1"><x-ui.t fil="Mga Kagustuhan sa Abiso" en="Notification Preferences" /></h3>
                    <p class="text-xs text-ink-muted mb-1"><x-ui.t fil="Piliin kung paano mo gustong matanggap ang bawat uri ng update." en="Choose how you want to receive each kind of update." /></p>
                    <p class="text-xs text-ink-muted mb-4 flex items-center gap-1.5">
                        <i data-lucide="bell-ring" class="w-3.5 h-3.5 text-brand-600"></i>
                        <x-ui.t fil="Laging naka-on ang mga abiso sa portal at app — dito mo makikita ang lahat ng update sa iyong mga kahilingan." en="Notifications in the portal and app are always on — they are where every update on your requests appears." />
                    </p>
                    <p x-show="!smsAvailable" x-cloak class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mb-4 flex items-start gap-1.5">
                        <i data-lucide="message-square-off" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i>
                        <x-ui.t fil="Darating pa ang text message (SMS). Hangga't wala pa, sa email namin ipinapadala ang mga update na pinili mong i-text." en="Text messages (SMS) are coming soon. Until then, updates you chose to get by text are sent by email." />
                    </p>
                    <div class="hidden sm:grid grid-cols-[minmax(0,1fr)_64px_64px] gap-4 pb-2 text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">
                        <span><x-ui.t fil="Uri ng update" en="Update type" /></span>
                        <span class="text-center" x-text="smsAvailable ? 'SMS' : ($store.lang.current === 'en' ? 'SMS (soon)' : 'SMS (darating)')"></span>
                        <span class="text-center">Email</span>
                    </div>
                    <div class="divide-y divide-brand-600/[.08]">
                        <template x-for="pref in notifPrefs" :key="pref.category">
                            <div class="grid grid-cols-[minmax(0,1fr)_64px_64px] items-center gap-4 py-3.5">
                                <p class="min-w-0 text-sm font-medium text-slate-700" x-text="$store.lang.current === 'en' ? pref.label?.en : (pref.label?.fil || pref.label?.en)"></p>
                                <template x-for="channel in ['sms', 'email']" :key="channel">
                                    <div class="flex flex-col items-center gap-1">
                                        <button
                                            @click="togglePref(pref, channel)"
                                            type="button"
                                            role="switch"
                                            :aria-checked="pref[channel] ? 'true' : 'false'"
                                            :aria-label="(channel === 'sms' ? 'SMS' : 'Email') + ': ' + ($store.lang.current === 'en' ? pref.label?.en : (pref.label?.fil || pref.label?.en))"
                                            :disabled="channel === 'sms' && !smsAvailable"
                                            :title="channel === 'sms' && !smsAvailable ? ($store.lang.current === 'en' ? 'Text messages are coming soon' : 'Darating pa ang text message') : null"
                                            class="w-10 h-6 rounded-full transition-colors duration-[160ms] ease-standard relative disabled:opacity-40 disabled:cursor-not-allowed"
                                            :class="pref[channel] ? 'bg-brand-600' : 'bg-slate-200'"
                                        >
                                            <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-[160ms] ease-standard" :class="pref[channel] && 'translate-x-4'"></span>
                                        </button>
                                        <span class="sm:hidden text-[10px] text-ink-muted" x-text="channel === 'sms' ? 'SMS' : 'Email'"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <p class="py-6 text-center text-xs text-ink-muted" x-show="!notifPrefsLoading && !notifPrefs.length">
                            <x-ui.t fil="Walang mga kagustuhan sa abiso." en="No notification preferences available." />
                        </p>
                    </div>
                </x-ui.card>
            </div>

            <div x-show="tab === 'security'" x-cloak class="space-y-5">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink mb-4"><x-ui.t fil="Palitan ang Password" en="Change Password" /></h3>
                    <div class="space-y-4 max-w-md">
                        <x-ui.input name="current_password" type="password" label="Current Password" icon="lock" placeholder="••••••••" x-model="passwordForm.current_password" />
                        <x-ui.input name="new_password" type="password" label="New Password" icon="lock" placeholder="••••••••" x-model="passwordForm.password" />
                        <x-ui.input name="confirm_password" type="password" label="Confirm New Password" icon="lock" placeholder="••••••••" x-model="passwordForm.password_confirmation" />
                        <x-ui.button x-bind:disabled="savingPassword" @click="updatePassword()">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="savingPassword" x-cloak></i>
                                <i data-lucide="check" class="w-3.5 h-3.5" x-show="!savingPassword"></i>
                                <span x-text="savingPassword ? ($store.lang.current === 'en' ? 'Saving…' : 'Sine-save…') : ($store.lang.current === 'en' ? 'Update Password' : 'I-update ang Password')"></span>
                            </span>
                        </x-ui.button>
                    </div>
                </x-ui.card>
                {{-- Two-factor setup was a setTimeout preview that saved nothing and
                     told the resident it was "enabling". There is no citizen 2FA on
                     the backend, so the control is gone until one exists. --}}
            </div>

            <div x-show="tab === 'privacy'" x-cloak>
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink mb-1"><x-ui.t fil="Pribasiya ng Datos" en="Data Privacy" /></h3>
                    <p class="text-xs text-ink-muted mb-4"><x-ui.t fil="Pamahalaan kung paano ginagamit ang iyong datos bilang residente sa buong platform, alinsunod sa Data Privacy Act of 2012." en="Manage how your resident data is used across the platform, in line with the Data Privacy Act of 2012." /></p>
                    <div class="flex items-start gap-3 rounded-xl bg-slate-50 border border-brand-600/10 p-4">
                        <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"></i>
                        <p class="text-xs text-ink-muted leading-relaxed">
                            <x-ui.t
                                fil="Kinokolekta at pinoproseso ng Munisipyo ng Esperanza ang iyong personal na datos para lamang sa layunin ng paghahatid ng serbisyong publiko. Hindi ibinabahagi ang iyong datos sa ibang partido nang walang pahintulot."
                                en="The Municipality of Esperanza collects and processes your personal data solely for the purpose of delivering public services. Your data is not shared with third parties without consent."
                            />
                        </p>
                    </div>
                    <form class="mt-5 space-y-3 max-w-xl" @submit.prevent="requestData()">
                        <h4 class="text-sm font-semibold text-ink"><x-ui.t fil="Humiling tungkol sa iyong datos" en="Make a request about your data" /></h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="kind in dataKinds" :key="kind.key">
                                <label class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm cursor-pointer transition-colors"
                                       :class="dataForm.kind === kind.key ? 'border-brand-300 bg-brand-50 text-brand-800' : 'border-slate-200 text-slate-700 hover:bg-white/60'">
                                    <input type="radio" name="data_request_kind" class="accent-brand-600" :value="kind.key" x-model="dataForm.kind">
                                    <span x-text="$store.lang.current === 'en' ? kind.en : kind.fil"></span>
                                </label>
                            </template>
                        </div>
                        <label class="block">
                            <span class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Mga detalye" en="Details" /></span>
                            <textarea x-model="dataForm.detail" rows="3" maxlength="2000" required
                                      :aria-label="$store.lang.current === 'en' ? 'Details of your data request' : 'Mga detalye ng iyong hiling sa datos'"
                                      :placeholder="$store.lang.current === 'en' ? 'Tell the LGU what you need, e.g. which information is wrong and what it should say.' : 'Sabihin sa LGU ang kailangan mo, hal. aling impormasyon ang mali at ano dapat ito.'"
                                      class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                        </label>
                        <x-ui.button type="submit" size="sm" x-bind:disabled="requestingData || dataForm.detail.trim() === ''">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="requestingData" x-cloak></i>
                                <i data-lucide="send" class="w-3.5 h-3.5" x-show="!requestingData"></i>
                                <span x-text="requestingData ? ($store.lang.current === 'en' ? 'Sending…' : 'Ipinapadala…') : ($store.lang.current === 'en' ? 'Send Request' : 'Ipadala ang Hiling')"></span>
                            </span>
                        </x-ui.button>
                    </form>

                    <div class="mt-6">
                        <h4 class="text-sm font-semibold text-ink mb-2"><x-ui.t fil="Aking mga hiling sa datos" en="My data requests" /></h4>
                        <div x-show="dataRequestsLoading"><x-ui.skeleton class="h-12" /></div>
                        <p x-show="!dataRequestsLoading && !dataRequests.length" class="text-xs text-ink-muted"><x-ui.t fil="Wala ka pang hiling." en="You have no requests yet." /></p>
                        <ul class="divide-y divide-brand-600/[.08]" x-show="!dataRequestsLoading && dataRequests.length">
                            <template x-for="req in dataRequests" :key="req.ref">
                                <li class="py-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-700"><span x-text="kindLabel(req.kind)"></span> · <span class="font-mono text-xs text-ink-muted" x-text="req.ref"></span></p>
                                        <p class="text-xs text-ink-muted mt-0.5 line-clamp-2" x-text="req.detail"></p>
                                        <p class="text-xs text-slate-600 mt-1" x-show="req.resolution" x-text="req.resolution"></p>
                                    </div>
                                    <x-ui.badge statusBind="req.status" />
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 mt-5">
                        <x-ui.button variant="ghost" size="sm" :href="route('citizen.privacy-policy')" icon="shield-check">
                            <x-ui.t fil="Buong Patakaran sa Privacy" en="View Full Privacy Policy" />
                        </x-ui.button>
                        <x-ui.button variant="ghost" size="sm" :href="route('citizen.terms')" icon="file-text">
                            <x-ui.t fil="Mga Tuntunin at Kundisyon" en="Terms & Conditions" />
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>

            <div x-show="tab === 'preferences'" x-cloak>
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink mb-4"><x-ui.t fil="Pangkalahatang Kagustuhan" en="General Preferences" /></h3>
                    <div class="space-y-4 max-w-md">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Wika" en="Language" /></label>
                            <p class="text-xs text-ink-muted mb-2.5"><x-ui.t fil="Piliin ang wikang gagamitin sa Citizen Portal." en="Choose the language used across the Citizen Portal." /></p>
                            <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit">
                                <button
                                    type="button"
                                    @click="$store.lang.set('fil'); $dispatch('toast', { message: 'Naka-Filipino na ang wika ng portal.', variant: 'success' })"
                                    class="px-4 py-2 text-sm font-medium rounded-xl transition-colors"
                                    :class="$store.lang.current === 'fil' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
                                >Filipino</button>
                                <button
                                    type="button"
                                    @click="$store.lang.set('en'); $dispatch('toast', { message: 'Portal language set to English.', variant: 'success' })"
                                    class="px-4 py-2 text-sm font-medium rounded-xl transition-colors"
                                    :class="$store.lang.current === 'en' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
                                >English</button>
                            </div>
                        </div>
                        {{-- A fact, not a choice: the dropdown here saved nothing, and every
                             date on the portal is in the LGU's own time (parity check, 2026-10-23). --}}
                        <div>
                            <p class="block text-sm font-medium text-slate-700 mb-1"><x-ui.t fil="Time Zone" en="Time Zone" /></p>
                            <p class="text-xs text-ink-muted"><x-ui.t fil="Lahat ng petsa at oras ay nasa oras ng Pilipinas (GMT+8, Manila)." en="Every date and time is shown in Philippine time (GMT+8, Manila)." /></p>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.citizen>
