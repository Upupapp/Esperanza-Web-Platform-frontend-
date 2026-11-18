{{-- modal-citizen-account.blade.php — Review a citizen ACCOUNT: what the officer
     verifies on (the valid ID, its back, the selfie) and the profile photo.
     Rendered inside constituents.blade.php's x-data scope; open state bound to
     showAccountReview, detail from GET /admin/citizens/{accountNo} (an audited
     sensitive read). Every decision here is a real endpoint. --}}

<div x-data="{ get open() { return showAccountReview }, set open(v) { showAccountReview = v } }">
    {{-- Bare: this dialog draws its own header (name, status, account
         number, close), so the modal's empty titled strip with a second X
         is left out. --}}
    <x-ui.modal maxWidth="2xl" :bare="true">
        <div class="flex items-start justify-between gap-4 p-6 pb-4 border-b border-brand-600/10 shrink-0">
            <div class="relative overflow-hidden w-12 h-12 rounded-full clay-blue grid place-items-center text-sm font-extrabold shrink-0">
                <span x-text="(accountDetail?.name || accountRow?.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0, 2).join('').toUpperCase()"></span>
                <img x-show="accountDetail?.photo?.current?.file?.url || accountRow?.citizen_account?.photo_url" x-cloak :src="accountDetail?.photo?.current?.file?.url || accountRow?.citizen_account?.photo_url" alt="" class="absolute inset-0 w-full h-full object-cover" x-on:error="$el.style.display='none'">
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h2 class="text-base font-bold text-ink truncate" x-text="accountDetail?.name || accountRow?.name || 'Citizen account'"></h2>
                    <x-ui.badge statusBind="(accountDetail?.status && accountDetail.status !== 'Pending Review') ? accountDetail.status : (accountRow ? cvStatusOf(accountRow) : accountDetail?.status)" />
                </div>
                <p class="text-xs text-ink-muted font-mono" x-text="accountDetail?.account_no || accountRow?.citizen_account?.account_no"></p>
            </div>
            <button type="button" @click="showAccountReview = false" class="text-ink-muted hover:text-slate-700" aria-label="Close"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>

        <div x-show="accountLoading" class="p-6 space-y-2 overflow-y-auto"><x-ui.skeleton class="h-10" /><x-ui.skeleton class="h-40 opacity-80" /></div>

        <div x-show="!accountLoading && accountDetail" class="p-6 space-y-5 overflow-y-auto scrollbar-thin min-h-0">
            {{-- The account --}}
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-xs">
                <div class="flex justify-between gap-2"><dt class="text-ink-muted">Barangay</dt><dd class="font-semibold text-ink" x-text="accountDetail?.barangay ? 'Brgy. ' + accountDetail.barangay : '—'"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-ink-muted">Registered</dt><dd class="font-semibold text-ink" x-text="accountDetail?.registered_at ? new Date(accountDetail.registered_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-ink-muted">Email</dt><dd class="font-semibold text-ink truncate" x-text="(accountDetail?.email || '—') + (accountDetail?.email_verified ? ' ✓' : '')"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-ink-muted">Mobile</dt><dd class="font-semibold text-ink" x-text="(accountDetail?.mobile || '—') + (accountDetail?.mobile_verified ? ' ✓' : '')"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-ink-muted">Birthdate</dt><dd class="font-semibold text-ink" x-text="accountDetail?.birthdate || '—'"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-ink-muted">Address</dt><dd class="font-semibold text-ink text-right" x-text="accountDetail?.address || '—'"></dd></div>
            </dl>

            {{-- Locked details (scope round 2026-10-15): once approved, the
                 resident cannot change their name, birth date, sex or
                 barangay; an officer corrects them here with a reason, and can
                 close the resident's correction request with it.
                 PUT /admin/citizens/{accountNo}/identity; component
                 identityCorrection. --}}
            <section x-data="identityCorrection" x-show="(icLocked || icMismatch.length || icDone) && icMay" x-cloak x-on:ic-match-id.window="icMatchId()" class="rounded-2xl border border-slate-200 p-4 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="user-pen" class="w-3.5 h-3.5"></i> <span x-text="icLocked ? 'Locked details' : 'Account details'"></span></p>
                    <button type="button" x-show="!icOpen" @click="icStart()" class="text-xs font-semibold rounded-xl px-3 py-2 bg-brand-50 text-brand-700 hover:bg-brand-100">Correct details</button>
                </div>
                <p x-show="!icOpen && icLocked" class="text-xs text-ink-muted">The account is verified, so the resident cannot change their name, birth date, sex or barangay. Correct them here when the ID shows they are wrong, or when the resident asks for a change of name with proof.</p>
                <p x-show="!icOpen && !icLocked && icMismatch.length" class="text-xs text-ink-muted">The ID differs from the account (see Identity verification below). Correct the account here to match the ID, with the reason.</p>
                {{-- The names the account has had (2026-10-16). --}}
                <div x-show="icHistory.length" class="text-xs" data-testid="name-history">
                    <p class="text-[11px] font-semibold text-ink-muted uppercase tracking-wide">Previous names</p>
                    <ul class="mt-1 space-y-1">
                        <template x-for="(h, k) in icHistory" :key="k">
                            <li class="break-words"><span class="text-ink-muted line-through" x-text="h.from"></span> → <span class="font-semibold text-ink" x-text="h.to"></span> <span class="text-ink-muted" x-text="'· ' + when(h.at) + (h.by ? ' by ' + h.by : '') + (h.reason ? ': ' + h.reason : '')"></span></li>
                        </template>
                    </ul>
                </div>
                <div x-show="icDone" x-cloak role="status" class="rounded-xl bg-emerald-50 border border-emerald-100 px-3 py-2 text-xs text-emerald-800">
                    <p class="font-semibold">Corrected. The resident was told why.</p>
                    <ul class="mt-1 space-y-0.5">
                        <template x-for="c in (icDone || [])" :key="c.label"><li class="break-words" x-text="c.label + ': ' + c.from + ' → ' + c.to"></li></template>
                    </ul>
                </div>
                <form x-show="icOpen" x-cloak @submit.prevent="icSave()" class="space-y-3" novalidate>
                    <p class="text-[11px] text-ink-muted break-words" x-text="'On the account now: ' + [accountDetail?.name, accountDetail?.birthdate ? 'born ' + accountDetail.birthdate : null, accountDetail?.barangay ? 'Brgy. ' + accountDetail.barangay : null].filter(Boolean).join(' · ')"></p>
                    <p x-show="!icNameKnown" class="text-[11px] text-amber-700">The middle name and suffix could not be read from the account's name. Fill them in from the ID, or they are saved empty.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="ic-first" class="block text-xs font-medium text-slate-700 mb-1">First name</label>
                            <input id="ic-first" type="text" x-model="icForm.first_name" maxlength="80" autocomplete="off" class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                        <div>
                            <label for="ic-middle" class="block text-xs font-medium text-slate-700 mb-1">Middle name</label>
                            <input id="ic-middle" type="text" x-model="icForm.middle_name" maxlength="80" autocomplete="off" class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                        <div>
                            <label for="ic-last" class="block text-xs font-medium text-slate-700 mb-1">Last name</label>
                            <input id="ic-last" type="text" x-model="icForm.last_name" maxlength="80" autocomplete="off" class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                        <div>
                            <label for="ic-suffix" class="block text-xs font-medium text-slate-700 mb-1">Suffix</label>
                            <input id="ic-suffix" type="text" x-model="icForm.suffix" maxlength="20" autocomplete="off" placeholder="e.g. Jr." class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                        <div>
                            <label for="ic-birthdate" class="block text-xs font-medium text-slate-700 mb-1">Birth date</label>
                            <input id="ic-birthdate" type="date" x-model="icForm.birthdate" :max="icToday" class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                        <div>
                            <label for="ic-sex" class="block text-xs font-medium text-slate-700 mb-1">Sex</label>
                            <select id="ic-sex" x-model="icForm.sex" class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <option value="">Choose…</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            <p x-show="icSexHint" class="text-[11px] text-ink-muted mt-1" x-text="icSexHint"></p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="ic-barangay" class="block text-xs font-medium text-slate-700 mb-1">Barangay</label>
                            <select id="ic-barangay" x-model="icForm.barangay" :disabled="!isMunicipalScope" class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none disabled:opacity-70">
                                @foreach($barangayOptions as $b)
                                    <option value="{{ $b }}">{{ $b }}</option>
                                @endforeach
                            </select>
                            <p x-show="!isMunicipalScope" class="text-[11px] text-ink-muted mt-1">Only the municipal office can move an account to another barangay.</p>
                        </div>
                    </div>
                    <div x-show="icRequests.length">
                        <label for="ic-request" class="block text-xs font-medium text-slate-700 mb-1">Close the resident's correction request too</label>
                        <select id="ic-request" x-model="icForm.data_request_ref" class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <option value="">No, leave it open</option>
                            {{-- :selected as well: these options arrive after the value is set, and x-model selects only when the value changes. --}}
                            <template x-for="r in icRequests" :key="r.ref"><option :value="r.ref" :selected="r.ref === icForm.data_request_ref" x-text="r.ref + (r.detail ? ': ' + (r.detail.length > 70 ? r.detail.slice(0, 69) + '…' : r.detail) : '')"></option></template>
                        </select>
                    </div>
                    <div>
                        <label for="ic-reason" class="block text-xs font-medium text-slate-700 mb-1">Reason <span class="font-normal text-ink-muted">(kept on the record; the resident reads it)</span></label>
                        <textarea id="ic-reason" x-model="icForm.reason" rows="2" maxlength="500" placeholder="e.g. Birth date was typed wrong at registration; checked against the PSA birth certificate." class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                        <p x-show="icForm.reason.trim().length < 10" class="text-[11px] text-ink-muted mt-0.5">At least 10 characters.</p>
                    </div>
                    <p x-show="icError" class="text-xs text-rose-600" role="alert" x-text="icError"></p>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button type="submit" size="sm" icon="check" x-bind:disabled="icBusy || icForm.reason.trim().length < 10"><span x-text="icBusy ? 'Saving…' : 'Save correction'"></span></x-ui.button>
                        <button type="button" @click="icOpen = false; icError = ''" :disabled="icBusy" class="text-xs font-semibold rounded-xl px-3 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-50">Cancel</button>
                    </div>
                </form>
            </section>

            {{-- Registry record (owner, 2026-10-09): the record the LGU
                 already had that registration recognised this resident as, or
                 records they may be. --}}
            <section x-show="registryMatch && (registryMatch.status || registryMatch.candidates?.length || registryMatch.claimed?.length)" x-cloak class="rounded-2xl border p-4 space-y-3" :class="registryMatch?.status === 'pending' ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200'">
                <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="user-search" class="w-3.5 h-3.5"></i> Registry record</p>
                <template x-if="registryMatch?.record && ['pending', 'confirmed'].includes(registryMatch?.status)">
                    <div class="rounded-xl bg-white border border-slate-200 px-3 py-2.5">
                        <p class="text-sm font-semibold text-ink"><span x-text="registryMatch.record.name"></span> <span class="text-xs font-mono text-ink-muted" x-text="registryMatch.record.ref"></span></p>
                        <p class="text-xs text-ink-muted" x-text="[registryMatch.record.age != null ? registryMatch.record.age + ' yrs' : null, registryMatch.record.sex, registryMatch.record.household?.label, registryMatch.record.imported_from ? 'imported from ' + registryMatch.record.imported_from.filename : null].filter(Boolean).join(' · ')"></p>
                        <p x-show="registryMatch.reasons?.length" class="text-xs text-emerald-700 mt-1" x-text="'Matched on: ' + window.registryReasonText(registryMatch.reasons)"></p>
                        <template x-if="registryMatch.record.comparison?.length">
                            <table class="mt-2 w-full text-[11px]">
                                <thead class="text-ink-muted"><tr><th class="text-left font-semibold py-1 pr-2">&nbsp;</th><th class="text-left font-semibold py-1 pr-2">Registered as</th><th class="text-left font-semibold py-1 pr-2">In the registry</th><th></th></tr></thead>
                                <tbody>
                                    <template x-for="c in registryMatch.record.comparison" :key="c.field">
                                        <tr class="border-t border-slate-100">
                                            <td class="py-1 pr-2 text-ink-muted" x-text="c.label"></td>
                                            <td class="py-1 pr-2 text-ink" x-text="c.account || '—'"></td>
                                            <td class="py-1 pr-2 text-ink"><span x-show="!c.record_hidden" x-text="c.record || '—'"></span><span x-show="c.record_hidden" class="text-ink-muted" title="Only officers who may see sensitive details see this value">hidden</span></td>
                                            <td class="py-1 text-right whitespace-nowrap font-semibold" :class="{ same: 'text-emerald-700', similar: 'text-amber-700', different: 'text-rose-700', missing: 'text-ink-muted' }[c.status]" x-text="{ same: '✓ same', similar: '≈ written differently', different: '✗ different', missing: 'not given' }[c.status]"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </template>
                    </div>
                </template>
                <template x-if="registryMatch?.status === 'pending'">
                    <div class="space-y-2">
                        <p class="text-xs text-ink"><span x-text="({ id_document: 'Their verified ID recognised this resident: the name and birth date on it match a record the barangay already had.', resident_profile: 'The birth date in their Resident Profile recognised this resident as a record the barangay already had.' })[registryMatch.source] || 'Registration recognised this resident as a record the barangay already had.'"></span> Until an officer confirms it, the resident sees only the details they gave themselves. <strong>Verifying the account confirms it.</strong></p>
                        <p x-show="registryMatch.set_aside" class="text-xs text-ink-muted" x-text="'The record made when they signed up, ' + (registryMatch.set_aside?.ref || '') + ', is merged into this one when you confirm (Data Quality can undo it). If it is not them, the account keeps that record.'"></p>
                        <p x-show="registryMatch.held_profile" class="text-xs text-ink-muted">They have sent their Resident Profile; it goes onto the record once confirmed.</p>
                        <div class="flex flex-wrap gap-2" x-show="can('const_citizen_verification','verify')">
                            <x-ui.button size="sm" icon="user-check" x-bind:disabled="registryBusy" @click="decideRegistryMatch('confirm')">It is them</x-ui.button>
                            <button type="button" @click="decideRegistryMatch('reject')" :disabled="registryBusy" class="text-xs font-semibold rounded-xl px-3 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-50">Not the same person</button>
                        </div>
                    </div>
                </template>
                <p x-show="registryMatch?.status === 'confirmed'" class="text-xs text-ink-muted" x-text="(registryMatch?.linked_by_officer ? 'Linked' : 'Confirmed') + (registryMatch?.decided_by ? ' by ' + registryMatch.decided_by : '') + (registryMatch?.decided_at ? ', ' + new Date(registryMatch.decided_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '') + '.'"></p>
                <p x-show="registryMatch?.status === 'rejected'" class="text-xs text-ink-muted" x-text="'Not ' + (registryMatch?.rejected_ref || 'the matched record') + (registryMatch?.decided_by ? ' (decided by ' + registryMatch.decided_by + ')' : '') + '. The account has a record of its own.'"></p>
                <template x-if="registryMatch?.claimed?.length">
                    <div class="rounded-xl bg-amber-50 border border-amber-200 px-3 py-2.5 space-y-1">
                        <p class="text-xs font-semibold text-amber-800 flex items-center gap-1.5"><i data-lucide="triangle-alert" class="w-3.5 h-3.5"></i>A record like this resident's already has an account</p>
                        <template x-for="c in registryMatch.claimed" :key="c.ref">
                            <p class="text-xs text-amber-900"><span class="font-semibold" x-text="c.name"></span> <span class="font-mono" x-text="c.ref"></span> belongs to <span class="font-mono" x-text="c.account_no"></span> (<span x-text="c.account_status"></span>) · <span x-text="window.registryReasonText(c.reasons)"></span></p>
                        </template>
                        <p class="text-[11px] text-amber-800">This may be a second account of the same person, or someone registering as them. Check the ID before approving.</p>
                    </div>
                </template>
                <template x-if="registryMatch?.candidates?.length">
                    <div class="space-y-2">
                        <p class="text-xs text-ink">Records already in the registry that may be this resident:</p>
                        <template x-for="c in registryMatch.candidates" :key="c.ref">
                            <div class="flex flex-wrap items-start gap-3 rounded-xl bg-white border border-slate-200 px-3 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink"><span x-text="c.name"></span> <span class="text-xs font-mono text-ink-muted" x-text="c.ref"></span>
                                        <span class="ml-1 text-[11px] font-semibold rounded-full px-2 py-0.5 ring-1" :class="c.strength === 'strong' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200'" x-text="c.strength === 'strong' ? 'Strong match' : 'Possible match'"></span></p>
                                    <p class="text-xs text-ink-muted" x-text="[c.age != null ? c.age + ' yrs' : null, c.sex, c.household?.label, c.imported_from ? 'imported from ' + c.imported_from.filename : null].filter(Boolean).join(' · ')"></p>
                                    <p class="text-xs text-ink-muted mt-0.5" x-text="window.registryReasonText(c.reasons)"></p>
                                    <details x-show="c.comparison?.length" class="mt-1 text-[11px]">
                                        <summary class="cursor-pointer text-brand-700">Compare</summary>
                                        <table class="mt-1 w-full">
                                            <tbody>
                                                <template x-for="f in (c.comparison || [])" :key="f.field">
                                                    <tr class="border-t border-slate-100">
                                                        <td class="py-0.5 pr-2 text-ink-muted" x-text="f.label"></td>
                                                        <td class="py-0.5 pr-2 text-ink" x-text="f.account || '—'"></td>
                                                        <td class="py-0.5 pr-2 text-ink"><span x-show="!f.record_hidden" x-text="f.record || '—'"></span><span x-show="f.record_hidden" class="text-ink-muted">hidden</span></td>
                                                        <td class="py-0.5 text-right font-semibold" :class="{ same: 'text-emerald-700', similar: 'text-amber-700', different: 'text-rose-700', missing: 'text-ink-muted' }[f.status]" x-text="{ same: '✓', similar: '≈', different: '✗', missing: '–' }[f.status]"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </details>
                                </div>
                                <button type="button" x-show="can('const_citizen_verification','verify')" @click="decideRegistryMatch('link', c.ref)" :disabled="registryBusy" class="text-xs font-semibold rounded-xl px-3 py-2 bg-brand-50 text-brand-700 hover:bg-brand-100 disabled:opacity-50">Use this record</button>
                            </div>
                        </template>
                    </div>
                </template>
            </section>

            {{-- Identity (2026-10-09). Separate from the residency decision
                 at the bottom: the automated check (Didit) says whether the
                 person is who the ID says; it never says they live here. --}}
            <section class="rounded-2xl border border-slate-200 p-4 space-y-3" x-show="accountDetail?.identity">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Identity verification</p>
                    <span class="inline-flex items-center text-[11px] font-semibold rounded-full px-2 py-0.5 ring-1" :class="identityPillClass(accountDetail?.identity?.status)" x-text="accountDetail?.identity?.status || 'Not checked'"></span>
                </div>
                <p class="text-[11px] text-ink-muted" x-text="accountDetail?.identity?.note"></p>
                <p x-show="accountDetail?.identity?.needs_officer" x-cloak class="text-xs text-rose-700">Revoked earlier: only an officer can restore this identity, whatever a new check says.</p>

                {{-- The latest check, exactly as it found it. --}}
                <template x-if="accountDetail?.identity?.checks?.length">
                    <div class="rounded-xl bg-slate-50 border border-brand-600/10 p-3 space-y-2" x-data="{ get c() { return accountDetail.identity.checks[0] } }">
                        <div class="flex flex-wrap items-center gap-2 text-[11px] text-ink-muted">
                            <span class="font-semibold text-ink">Latest check</span>
                            <span x-text="c.completed_at ? new Date(c.completed_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'not finished'"></span>
                            <span class="font-bold uppercase tracking-wide rounded px-1.5 py-0.5" x-show="c.environment !== 'live'" :class="c.environment === 'sandbox' ? 'bg-amber-100 text-amber-800' : 'bg-violet-100 text-violet-800'" x-text="c.environment === 'sandbox' ? 'Sandbox - not a real check' : 'Simulated - not a real check'"></span>
                        </div>
                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                            <div><dt class="text-ink-muted">Document</dt><dd class="font-semibold text-ink" x-text="c.document?.status || 'Not run'"></dd></div>
                            <div><dt class="text-ink-muted">Face match</dt><dd class="font-semibold text-ink" x-text="(c.face_match?.status || 'Not run') + (c.face_match?.score != null ? ' (' + c.face_match.score + ')' : '')"></dd></div>
                            <div><dt class="text-ink-muted">Liveness</dt><dd class="font-semibold text-ink" x-text="(c.liveness?.status || 'Not run') + (c.liveness?.score != null ? ' (' + c.liveness.score + ')' : '')"></dd></div>
                        </dl>
                        <p x-show="(c.reasons || []).length" class="text-xs text-slate-700" x-text="'Reasons: ' + (c.reasons || []).join(', ')"></p>
                        <p x-show="[...(c.document?.warnings || []), ...(c.face_match?.warnings || []), ...(c.liveness?.warnings || [])].length" class="text-[11px] text-ink-muted" x-text="'Provider warnings: ' + [...(c.document?.warnings || []), ...(c.face_match?.warnings || []), ...(c.liveness?.warnings || [])].join(', ')"></p>
                        <p x-show="c.error" class="text-xs text-amber-700" x-text="'Could not run yet (' + c.error + '), attempt ' + c.attempts + '. It is retried automatically.'"></p>
                        {{-- What the ID says, beside what the account says (2026-10-16):
                             the ID never overwrites the account; a difference
                             stops the check here, for the officer. --}}
                        <div x-show="(c.comparison || []).length" class="pt-1 border-t border-brand-600/10" data-testid="id-vs-account">
                            <table class="w-full text-xs">
                                <thead><tr class="text-left text-ink-muted"><th class="font-medium py-1 pr-2"></th><th class="font-medium py-1 pr-2">On the account</th><th class="font-medium py-1 pr-2">On the ID</th><th class="font-medium py-1"></th></tr></thead>
                                <tbody>
                                    <template x-for="row in (c.comparison || [])" :key="row.field">
                                        <tr class="border-t border-brand-600/10 align-top">
                                            <td class="py-1 pr-2 text-ink-muted" x-text="{ name: 'Name', birthdate: 'Birth date', sex: 'Sex' }[row.field]"></td>
                                            <td class="py-1 pr-2 font-semibold text-ink break-words" x-text="row.account || '—'"></td>
                                            <td class="py-1 pr-2 font-semibold break-words" :class="row.matches === false ? 'text-rose-700' : 'text-ink'" x-text="row.id || '—'"></td>
                                            <td class="py-1 whitespace-nowrap font-semibold" :class="row.matches === false ? 'text-rose-700' : (row.matches ? 'text-emerald-700' : 'text-ink-muted')" x-text="row.matches === false ? 'Differs' : (row.matches ? 'Same' : 'Not compared')"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div x-show="(c.comparison || []).some((r) => r.matches === false)" class="mt-2 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-xs text-rose-900 space-y-1.5">
                                <p>The ID does not say what the account says, so this check was not accepted as it is. Nothing on the account was changed from the ID. Compare the ID photo with the account, then either make the account match the ID (the identity is then verified), or decide by hand below with a reason.</p>
                                <button type="button" x-show="can('const_citizen_verification','verify') && c.account_from_id" @click="$dispatch('ic-match-id')" class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-rose-300 px-2.5 py-1.5 font-semibold text-rose-800 hover:bg-rose-100"><i data-lucide="user-check" class="w-3.5 h-3.5"></i> Make the account match the ID</button>
                            </div>
                        </div>
                        <dl x-show="c.extracted && Object.keys(c.extracted).length" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-xs pt-1 border-t border-brand-600/10">
                            <div x-show="!(c.comparison || []).length" class="flex justify-between gap-2"><dt class="text-ink-muted">Name on ID</dt><dd class="font-semibold text-ink text-right" x-text="c.extracted?.full_name || [c.extracted?.first_name, c.extracted?.last_name].filter(Boolean).join(' ') || '—'"></dd></div>
                            <div x-show="!(c.comparison || []).length" class="flex justify-between gap-2"><dt class="text-ink-muted">Birthdate on ID</dt><dd class="font-semibold text-ink text-right" x-text="c.extracted?.date_of_birth || '—'"></dd></div>
                            <div class="flex justify-between gap-2"><dt class="text-ink-muted">Document</dt><dd class="font-semibold text-ink text-right" x-text="[c.extracted?.document_type, c.extracted?.issuing_state].filter(Boolean).join(' · ') || '—'"></dd></div>
                            <div class="flex justify-between gap-2"><dt class="text-ink-muted">Number (last 4)</dt><dd class="font-semibold text-ink font-mono" x-text="c.extracted?.document_number_last4 || '—'"></dd></div>
                            <div class="flex justify-between gap-2"><dt class="text-ink-muted">Expires</dt><dd class="font-semibold text-ink" x-text="c.extracted?.expiration_date || '—'"></dd></div>
                        </dl>
                    </div>
                </template>

                {{-- Officer actions. A reason is required and kept. --}}
                <div class="space-y-2" x-show="can('const_citizen_verification','verify') || can('const_citizen_verification','reject')">
                    <textarea aria-label="Reason" x-model="identityReason" rows="2"
                              :placeholder="accountDetail?.identity?.status === 'Verified' ? 'Reason for revoking (the resident will read it)...' : 'Why the identity is verified by hand (e.g. checked in person against the original ID)...'"
                              x-show="(accountDetail?.identity?.status === 'Verified' && can('const_citizen_verification','reject')) || (accountDetail?.identity?.status !== 'Verified' && can('const_citizen_verification','verify'))"
                              class="w-full text-xs rounded-xl border border-slate-200 px-2.5 py-2 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" icon="user-check" x-show="accountDetail?.identity?.status !== 'Verified' && can('const_citizen_verification','verify')" x-bind:disabled="processingCitizen || identityReason.trim().length < 5" @click="decideIdentity('verify')">
                            <span x-text="(accountDetail?.identity?.status === 'Revoked' || accountDetail?.identity?.needs_officer) ? 'Restore identity' : 'Verify identity manually'"></span>
                        </x-ui.button>
                        <button type="button" x-show="accountDetail?.identity?.status === 'Verified' && can('const_citizen_verification','reject')" @click="decideIdentity('revoke')" :disabled="processingCitizen || identityReason.trim().length < 5" class="text-xs font-semibold rounded-xl px-3 py-2 bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-50">Revoke identity</button>
                        <button type="button" x-show="['Pending', 'In Progress'].includes(accountDetail?.identity?.status) && can('const_citizen_verification','verify')" @click="decideIdentity('recheck')" :disabled="processingCitizen" class="text-xs font-semibold rounded-xl px-3 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 disabled:opacity-50">Run check again</button>
                    </div>
                </div>

                {{-- Who verified, revoked or restored it, and why. --}}
                <details x-show="accountDetail?.identity?.history?.length" class="text-xs">
                    <summary class="cursor-pointer text-ink-muted hover:text-ink">History (<span x-text="accountDetail?.identity?.history?.length || 0"></span>)</summary>
                    <ul class="mt-2 space-y-1.5">
                        <template x-for="(h, k) in (accountDetail?.identity?.history || [])" :key="k">
                            <li class="flex flex-wrap gap-x-2 text-slate-700">
                                <span class="text-ink-muted" x-text="h.at ? new Date(h.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></span>
                                <span class="font-semibold" x-text="(h.from || '—') + ' → ' + h.to"></span>
                                <span x-text="'(' + h.method + (h.actor_name ? ', ' + h.actor_name : (h.actor_type === 'system' ? ', automated' : '')) + ')'"></span>
                                <span x-show="h.reason" class="basis-full text-ink-muted" x-text="h.reason"></span>
                            </li>
                        </template>
                    </ul>
                </details>
            </section>

            {{-- The valid ID --}}
            <section class="rounded-2xl border border-slate-200 p-4">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="id-card" class="w-3.5 h-3.5"></i> Valid ID</p>
                    <span x-show="accountDetail?.valid_id" class="text-[11px] text-ink-muted" x-text="(accountDetail?.valid_id?.id_type || '') + ' · ' + (accountDetail?.valid_id?.status || '')"></span>
                </div>
                <p x-show="!accountDetail?.valid_id" class="text-xs text-ink-muted">No ID sent yet. The resident can send one from their profile on the portal or in the app.</p>
                <div x-show="accountDetail?.valid_id" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <template x-for="part in [['Front', accountDetail?.valid_id?.front], ['Back', accountDetail?.valid_id?.back], ['Selfie', accountDetail?.valid_id?.selfie]]" :key="part[0]">
                        <div class="rounded-xl border border-brand-600/10 bg-slate-50 p-2">
                            <p class="text-[11px] font-medium text-slate-600 mb-1.5" x-text="part[0]"></p>
                            <template x-if="part[1] && part[1].url && (part[1].mime || '').startsWith('image/')">
                                <a :href="part[1].url" target="_blank" rel="noopener"><img :src="part[1].url" :alt="part[0] + ' of the ID'" class="w-full h-32 object-contain rounded-lg bg-white"></a>
                            </template>
                            {{-- A PDF ID is read here too (PDF.js draws it; nothing in it runs). --}}
                            <template x-if="part[1] && part[1].url && !(part[1].mime || '').startsWith('image/')">
                                <div x-data="pdfPreview" x-effect="show(part[1].url, part[1].mime || 'pdf')" class="max-h-64 overflow-y-auto rounded-lg bg-white">
                                    <p x-show="loading" class="text-xs text-ink-muted py-3 text-center">Opening…</p>
                                    <p x-show="failed" x-cloak class="text-xs text-rose-600 py-3 text-center">This file could not be shown here.</p>
                                    <div x-ref="pages"></div>
                                    <a :href="part[1].url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 hover:underline px-1 pb-1"><i data-lucide="download" class="w-3 h-3"></i> Download</a>
                                </div>
                            </template>
                            <p x-show="part[1] && !part[1].url" class="text-xs leading-snug" :class="part[1]?.scan_status === 'pending' ? 'text-ink-muted' : 'text-rose-600'" x-text="window.fileNotice(part[1])"></p>
                            <p x-show="!part[1]" class="text-xs text-ink-muted">Not sent.</p>
                        </div>
                    </template>
                </div>
                <p x-show="accountDetail?.valid_id?.remarks" class="text-xs text-slate-600 mt-2" x-text="'Remarks: ' + (accountDetail?.valid_id?.remarks || '')"></p>
            </section>

            {{-- The profile photo --}}
            <section class="rounded-2xl border border-slate-200 p-4">
                <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5 mb-3"><i data-lucide="user-round" class="w-3.5 h-3.5"></i> Profile photo</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-[11px] font-medium text-slate-600 mb-1.5">On the Digital ID</p>
                        <img x-show="accountDetail?.photo?.current?.file?.url" :src="accountDetail?.photo?.current?.file?.url" alt="Approved photo" class="w-28 h-28 rounded-xl object-cover border border-slate-200">
                        <p x-show="accountDetail?.photo?.current?.file && !accountDetail?.photo?.current?.file?.url" class="text-xs leading-snug text-rose-600" x-text="window.fileNotice(accountDetail?.photo?.current?.file)"></p>
                        <p x-show="!accountDetail?.photo?.current" class="text-xs text-ink-muted">None yet.</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-medium text-slate-600 mb-1.5">Waiting for approval</p>
                        <img x-show="accountDetail?.photo?.pending?.file?.url" :src="accountDetail?.photo?.pending?.file?.url" alt="New photo" class="w-28 h-28 rounded-xl object-cover border border-amber-300">
                        <p x-show="accountDetail?.photo?.pending?.file && !accountDetail?.photo?.pending?.file?.url" class="text-xs leading-snug" :class="accountDetail?.photo?.pending?.file?.scan_status === 'pending' ? 'text-ink-muted' : 'text-rose-600'" x-text="window.fileNotice(accountDetail?.photo?.pending?.file)"></p>
                        <p x-show="!accountDetail?.photo?.pending" class="text-xs text-ink-muted">Nothing pending.</p>
                    </div>
                </div>
                {{-- A verified resident's new photo is decided on its own; an
                     unverified account's goes with the account decision below. --}}
                <div x-show="accountDetail?.photo?.pending && accountDetail?.status === 'Approved'" class="mt-3 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button size="sm" icon="check" x-show="can('const_citizen_verification','verify')" x-bind:disabled="processingCitizen" @click="decideAccountPhoto('approve')">Approve photo</x-ui.button>
                    </div>
                    <div x-show="can('const_citizen_verification','reject')" class="flex flex-col sm:flex-row gap-2">
                        <input type="text" x-model="photoRejectRemarks" aria-label="Reason the photo is not approved" placeholder="Reason the photo is not approved (the resident will read it)" class="flex-1 text-xs rounded-xl border border-slate-200 px-2.5 py-2 focus:border-rose-400 focus:ring-4 focus:ring-rose-100 outline-none">
                        <button type="button" @click="decideAccountPhoto('reject')" :disabled="processingCitizen || photoRejectRemarks.trim().length < 5" class="text-xs font-semibold rounded-xl px-3 py-2 bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-50">Reject photo</button>
                    </div>
                </div>
                <p x-show="accountDetail?.photo?.pending && accountDetail?.status !== 'Approved'" class="text-xs text-ink-muted mt-2">Approved together with the account.</p>
            </section>

            {{-- The account decision --}}
            <section x-show="['Pending Review', 'Rejected'].includes(accountDetail?.status) && (can('const_citizen_verification','verify') || can('const_citizen_verification','reject'))" class="space-y-2.5 pt-1">
                <p class="text-xs font-semibold text-ink uppercase tracking-wide">Residency decision</p>
                <p class="text-xs text-ink-muted">Compare the ID and the selfie with the account and the resident record before deciding. Approving confirms residency, and needs a verified identity first.</p>
                <p x-show="accountDetail?.residency?.approval_revoked" x-cloak class="text-xs text-rose-700">The residency approval was revoked. Verifying the account again restores it.</p>
                {{-- Only when the backend reports identity: an older backend
                     (staging before it is updated) sends none, and approval
                     must keep working there exactly as before. --}}
                <p x-show="accountDetail?.identity && accountDetail.identity.status !== 'Verified'" x-cloak class="text-xs text-amber-700">The identity is not verified yet. Verify it above (by hand, with a reason, if the automated check could not) before approving the account.</p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" icon="check" x-show="can('const_citizen_verification','verify')" x-bind:disabled="processingCitizen || (accountDetail?.identity && accountDetail.identity.status !== 'Verified')" @click="decideAccount('verify')">Verify account</x-ui.button>
                </div>
                <div x-show="accountDetail?.status === 'Pending Review' && can('const_citizen_verification','reject')" class="space-y-2">
                    <textarea aria-label="Rejection remarks" x-model="rejectAccountRemarks" rows="2" placeholder="Reason for rejection (the resident will read it)..." class="w-full text-xs rounded-xl border border-slate-200 px-2.5 py-2 focus:border-rose-400 focus:ring-4 focus:ring-rose-100 outline-none"></textarea>
                    <button type="button" @click="decideAccount('reject')" :disabled="processingCitizen || rejectAccountRemarks.trim().length < 5" class="text-xs font-semibold rounded-xl px-3 py-2 bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-50">Reject account</button>
                </div>
            </section>

            {{-- Withdraw an approved residency (2026-10-13). Separate from
                 revoking the identity: that one is above, with its own history. --}}
            <section x-show="accountDetail?.status === 'Approved' && can('const_citizen_verification','reject')" class="space-y-2.5 pt-1">
                <p class="text-xs font-semibold text-ink uppercase tracking-wide">Revoke residency approval</p>
                <p class="text-xs text-ink-muted">For a resident whose approval no longer holds (moved away, approved in error). The account goes back to Rejected, the Resident ID is suspended and its QR stops verifying. The identity verification is not touched, and the approval stays in the history.</p>
                <textarea aria-label="Reason for revoking the approval" x-model="revokeApprovalReason" rows="2" placeholder="Reason for revoking the approval (the resident will read it)..." class="w-full text-xs rounded-xl border border-slate-200 px-2.5 py-2 focus:border-rose-400 focus:ring-4 focus:ring-rose-100 outline-none"></textarea>
                <button type="button" @click="revokeApproval()" :disabled="processingCitizen || revokeApprovalReason.trim().length < 5" class="text-xs font-semibold rounded-xl px-3 py-2 bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-50">Revoke approval</button>
            </section>

            {{-- Every residency decision: approved, rejected, revoked, restored. --}}
            <details x-show="accountDetail?.residency?.history?.length" class="text-xs">
                <summary class="cursor-pointer text-ink-muted hover:text-ink">Residency history (<span x-text="accountDetail?.residency?.history?.length || 0"></span>)</summary>
                <ul class="mt-2 space-y-1.5">
                    <template x-for="(h, k) in (accountDetail?.residency?.history || [])" :key="k">
                        <li class="flex flex-wrap gap-x-2 text-slate-700">
                            <span class="text-ink-muted" x-text="h.at ? new Date(h.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></span>
                            <span class="font-semibold" x-text="(h.from_status || '—') + ' → ' + h.to_status"></span>
                            <span x-text="'(' + h.action + (h.actor_name ? ', ' + h.actor_name : '') + ')'"></span>
                            <span x-show="h.reason" class="basis-full text-ink-muted" x-text="h.reason"></span>
                        </li>
                    </template>
                </ul>
            </details>

            {{-- What the resident was sent (scope round 2026-10-15): every
                 notification, newest first, and how each email or SMS went.
                 GET /admin/citizens/{accountNo}/messages, read only when
                 opened; component citizenMessages. --}}
            <section x-data="citizenMessages" class="rounded-2xl border border-slate-200 p-4 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-ink uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="mails" class="w-3.5 h-3.5"></i> Messages sent</p>
                    <button type="button" @click="cmToggle()" :aria-expanded="cmOpen" class="text-xs font-semibold text-brand-700 hover:underline px-1.5 py-1" x-text="cmOpen ? 'Hide' : 'Show'"></button>
                </div>
                <p x-show="!cmOpen" class="text-xs text-ink-muted">Notifications on the portal and the app, and the emails and text messages that carried them.</p>
                <div x-show="cmOpen" x-cloak class="space-y-2">
                    <div x-show="cmLoading" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-14" /><x-ui.skeleton class="h-14 opacity-80" /></div>
                    <p x-show="cmFailed" class="text-xs text-rose-700" role="alert">Could not load the messages. <button type="button" class="underline" @click="cmLoad(1)">Try again</button></p>
                    <p x-show="!cmLoading && !cmFailed && !cmRows.length" class="text-xs text-ink-muted">Nothing has been sent to this resident yet.</p>
                    <p x-show="cmRows.length" class="text-[11px] text-ink-muted" x-text="cmTotal + (cmTotal === 1 ? ' message' : ' messages') + ', newest first'"></p>
                    <ul x-show="cmRows.length" class="divide-y divide-brand-600/[.08]">
                        <template x-for="m in cmRows" :key="m.id">
                            <li class="py-2.5">
                                <div class="flex flex-wrap items-start gap-2">
                                    <p class="text-sm font-semibold text-ink flex-1 min-w-0 break-words" x-text="m.title"></p>
                                    <span class="text-[11px] font-semibold rounded-full px-2 py-0.5 ring-1 ring-inset whitespace-nowrap" :class="m.read ? 'bg-slate-50 text-slate-600 ring-slate-200' : 'bg-sky-50 text-sky-700 ring-sky-200'" x-text="m.read ? 'Read' : 'Not read yet'"></span>
                                </div>
                                <p x-show="m.body" class="text-xs text-slate-700 whitespace-pre-line break-words mt-0.5" x-text="m.body"></p>
                                <p class="text-[11px] text-ink-muted mt-1" x-text="[when(m.sent_at), m.category ? m.category.charAt(0).toUpperCase() + m.category.slice(1) : null, m.ref].filter(Boolean).join(' · ')"></p>
                                <div x-show="(m.deliveries || []).length" class="flex flex-wrap gap-1.5 mt-1.5">
                                    <template x-for="(d, k) in (m.deliveries || [])" :key="k">
                                        <span class="inline-flex items-center text-[11px] font-medium rounded-full px-2 py-0.5 ring-1 ring-inset break-all" :class="cmTone(d)" x-text="cmLabel(d)"></span>
                                    </template>
                                </div>
                                <template x-for="(d, k) in cmProblems(m)" :key="'p' + k">
                                    <p class="text-[11px] text-rose-700 mt-1 break-words" x-text="cmChannel(d) + ': ' + d.error"></p>
                                </template>
                            </li>
                        </template>
                    </ul>
                    <div x-show="cmPage < cmLastPage" class="flex justify-center pt-1">
                        <x-ui.button variant="secondary" size="sm" x-bind:disabled="cmMore" @click="cmLoad(cmPage + 1)"><span x-text="cmMore ? 'Loading…' : 'Older messages'"></span></x-ui.button>
                    </div>
                </div>
            </section>
        </div>
    </x-ui.modal>
</div>
