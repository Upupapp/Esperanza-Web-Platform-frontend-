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
        <div class="flex items-start justify-between gap-4 p-6 pb-4 border-b border-slate-100 shrink-0">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h2 class="text-base font-bold text-navy-900 truncate" x-text="accountDetail?.name || accountRow?.name || 'Citizen account'"></h2>
                    <x-ui.badge statusBind="accountDetail?.status || accountRow?.citizen_account?.status" />
                </div>
                <p class="text-xs text-slate-500 font-mono" x-text="accountDetail?.account_no || accountRow?.citizen_account?.account_no"></p>
            </div>
            <button type="button" @click="showAccountReview = false" class="text-slate-500 hover:text-slate-700" aria-label="Close"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>

        <div x-show="accountLoading" class="p-6 space-y-2 overflow-y-auto"><x-ui.skeleton class="h-10" /><x-ui.skeleton class="h-40 opacity-80" /></div>

        <div x-show="!accountLoading && accountDetail" class="p-6 space-y-5 overflow-y-auto scrollbar-thin min-h-0">
            {{-- The account --}}
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-xs">
                <div class="flex justify-between gap-2"><dt class="text-slate-500">Barangay</dt><dd class="font-semibold text-navy-900" x-text="accountDetail?.barangay ? 'Brgy. ' + accountDetail.barangay : '—'"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-slate-500">Registered</dt><dd class="font-semibold text-navy-900" x-text="accountDetail?.registered_at ? new Date(accountDetail.registered_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-slate-500">Email</dt><dd class="font-semibold text-navy-900 truncate" x-text="(accountDetail?.email || '—') + (accountDetail?.email_verified ? ' ✓' : '')"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-slate-500">Mobile</dt><dd class="font-semibold text-navy-900" x-text="(accountDetail?.mobile || '—') + (accountDetail?.mobile_verified ? ' ✓' : '')"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-slate-500">Birthdate</dt><dd class="font-semibold text-navy-900" x-text="accountDetail?.birthdate || '—'"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-slate-500">Address</dt><dd class="font-semibold text-navy-900 text-right" x-text="accountDetail?.address || '—'"></dd></div>
            </dl>

            {{-- The valid ID --}}
            <section class="rounded-2xl border border-slate-200 p-4">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide flex items-center gap-1.5"><i data-lucide="id-card" class="w-3.5 h-3.5"></i> Valid ID</p>
                    <span x-show="accountDetail?.valid_id" class="text-[11px] text-slate-500" x-text="(accountDetail?.valid_id?.id_type || '') + ' · ' + (accountDetail?.valid_id?.status || '')"></span>
                </div>
                <p x-show="!accountDetail?.valid_id" class="text-xs text-slate-500">No ID sent yet. The resident can send one from their profile on the portal or in the app.</p>
                <div x-show="accountDetail?.valid_id" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <template x-for="part in [['Front', accountDetail?.valid_id?.front], ['Back', accountDetail?.valid_id?.back], ['Selfie', accountDetail?.valid_id?.selfie]]" :key="part[0]">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-2">
                            <p class="text-[11px] font-medium text-slate-600 mb-1.5" x-text="part[0]"></p>
                            <template x-if="part[1] && part[1].url && (part[1].mime || '').startsWith('image/')">
                                <a :href="part[1].url" target="_blank" rel="noopener"><img :src="part[1].url" :alt="part[0] + ' of the ID'" class="w-full h-32 object-contain rounded-lg bg-white"></a>
                            </template>
                            {{-- A PDF ID is read here too (PDF.js draws it; nothing in it runs). --}}
                            <template x-if="part[1] && part[1].url && !(part[1].mime || '').startsWith('image/')">
                                <div x-data="pdfPreview" x-effect="show(part[1].url, part[1].mime || 'pdf')" class="max-h-64 overflow-y-auto rounded-lg bg-white">
                                    <p x-show="loading" class="text-xs text-slate-500 py-3 text-center">Opening…</p>
                                    <p x-show="failed" x-cloak class="text-xs text-rose-600 py-3 text-center">This file could not be shown here.</p>
                                    <div x-ref="pages"></div>
                                    <a :href="part[1].url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 hover:underline px-1 pb-1"><i data-lucide="download" class="w-3 h-3"></i> Download</a>
                                </div>
                            </template>
                            <p x-show="part[1] && !part[1].url" class="text-xs leading-snug" :class="part[1]?.scan_status === 'pending' ? 'text-slate-500' : 'text-rose-600'" x-text="window.fileNotice(part[1])"></p>
                            <p x-show="!part[1]" class="text-xs text-slate-500">Not sent.</p>
                        </div>
                    </template>
                </div>
                <p x-show="accountDetail?.valid_id?.remarks" class="text-xs text-slate-600 mt-2" x-text="'Remarks: ' + (accountDetail?.valid_id?.remarks || '')"></p>
            </section>

            {{-- The profile photo --}}
            <section class="rounded-2xl border border-slate-200 p-4">
                <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide flex items-center gap-1.5 mb-3"><i data-lucide="user-round" class="w-3.5 h-3.5"></i> Profile photo</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-[11px] font-medium text-slate-600 mb-1.5">On the Digital ID</p>
                        <img x-show="accountDetail?.photo?.current?.file?.url" :src="accountDetail?.photo?.current?.file?.url" alt="Approved photo" class="w-28 h-28 rounded-xl object-cover border border-slate-200">
                        <p x-show="accountDetail?.photo?.current?.file && !accountDetail?.photo?.current?.file?.url" class="text-xs leading-snug text-rose-600" x-text="window.fileNotice(accountDetail?.photo?.current?.file)"></p>
                        <p x-show="!accountDetail?.photo?.current" class="text-xs text-slate-500">None yet.</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-medium text-slate-600 mb-1.5">Waiting for approval</p>
                        <img x-show="accountDetail?.photo?.pending?.file?.url" :src="accountDetail?.photo?.pending?.file?.url" alt="New photo" class="w-28 h-28 rounded-xl object-cover border border-amber-300">
                        <p x-show="accountDetail?.photo?.pending?.file && !accountDetail?.photo?.pending?.file?.url" class="text-xs leading-snug" :class="accountDetail?.photo?.pending?.file?.scan_status === 'pending' ? 'text-slate-500' : 'text-rose-600'" x-text="window.fileNotice(accountDetail?.photo?.pending?.file)"></p>
                        <p x-show="!accountDetail?.photo?.pending" class="text-xs text-slate-500">Nothing pending.</p>
                    </div>
                </div>
                {{-- A verified resident's new photo is decided on its own; an
                     unverified account's goes with the account decision below. --}}
                <div x-show="accountDetail?.photo?.pending && accountDetail?.status === 'Approved'" class="mt-3 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button size="sm" icon="check" x-show="can('const_citizen_verification','verify')" x-bind:disabled="processingCitizen" @click="decideAccountPhoto('approve')">Approve photo</x-ui.button>
                    </div>
                    <div x-show="can('const_citizen_verification','reject')" class="flex flex-col sm:flex-row gap-2">
                        <input type="text" x-model="photoRejectRemarks" aria-label="Reason the photo is not approved" placeholder="Reason the photo is not approved (the resident will read it)" class="flex-1 text-xs rounded-lg border border-slate-200 px-2.5 py-2 focus:border-rose-400 focus:ring-4 focus:ring-rose-100 outline-none">
                        <button type="button" @click="decideAccountPhoto('reject')" :disabled="processingCitizen || photoRejectRemarks.trim().length < 5" class="text-xs font-semibold rounded-lg px-3 py-2 bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-50">Reject photo</button>
                    </div>
                </div>
                <p x-show="accountDetail?.photo?.pending && accountDetail?.status !== 'Approved'" class="text-xs text-slate-500 mt-2">Approved together with the account.</p>
            </section>

            {{-- The account decision --}}
            <section x-show="['Pending Review', 'Rejected'].includes(accountDetail?.status) && (can('const_citizen_verification','verify') || can('const_citizen_verification','reject'))" class="space-y-2.5 pt-1">
                <p class="text-xs text-slate-500">Compare the ID and the selfie with the account and the resident record before deciding.</p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" icon="check" x-show="can('const_citizen_verification','verify')" x-bind:disabled="processingCitizen" @click="decideAccount('verify')">Verify Account</x-ui.button>
                </div>
                <div x-show="accountDetail?.status === 'Pending Review' && can('const_citizen_verification','reject')" class="space-y-2">
                    <textarea aria-label="Rejection remarks" x-model="rejectAccountRemarks" rows="2" placeholder="Reason for rejection (the resident will read it)..." class="w-full text-xs rounded-lg border border-slate-200 px-2.5 py-2 focus:border-rose-400 focus:ring-4 focus:ring-rose-100 outline-none"></textarea>
                    <button type="button" @click="decideAccount('reject')" :disabled="processingCitizen || rejectAccountRemarks.trim().length < 5" class="text-xs font-semibold rounded-lg px-3 py-2 bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-50">Reject Account</button>
                </div>
            </section>
        </div>
    </x-ui.modal>
</div>
