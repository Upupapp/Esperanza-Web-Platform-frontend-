<x-layouts.admin title="Payments" subtitle="Track Dokyu and Tulong invoices, methods, and issued receipts." active="payments">
    <div
        x-data="{
            // Opens on what the cashier has to act on (live test 2026-10-07, BUG-3).
            tab: 'review',
            search: '',
            barangayFilter: '',
            open: false,
            selected: null,
            payments: [],
            loading: true,
            failed: false,
            method: null,
            rejectReason: '',
            showRejectReason: false,
            processing: false,
            barangays: @js(config('esperanza.barangays')),
            get isMunicipalScope() {
                const s = this.$store.session.account?.scope;
                return !s || s === 'Municipal';
            },
            async loadPayments() {
                this.loading = true;
                this.failed = false;
                try {
                    const res = await $api.getAll('/admin/payments');
                    this.payments = res.data || [];
                } catch { this.failed = true; } finally { this.loading = false; }
            },
            matches(p) {
                if (!this.$store.session.inScope(p.barangay)) return false;
                const tabOk = this.tab === 'all'
                    || (this.tab === 'review' && p.status === 'Pending Review')
                    || (this.tab === 'paid' && p.status === 'Approved')
                    || (this.tab === 'rejected' && p.status === 'Rejected')
                    || (this.tab === 'closed' && p.status === 'Cancelled')
                    || (this.tab === 'refund' && this.refundToDecide(p));
                const barangayOk = this.barangayFilter === '' || p.barangay === this.barangayFilter;
                const q = this.search.trim().toLowerCase();
                const searchOk = q === '' || p.ref.toLowerCase().includes(q) || (p.request_ref || '').toLowerCase().includes(q) || (p.citizen || '').toLowerCase().includes(q);
                return tabOk && barangayOk && searchOk;
            },
            // Arriving from a request's own process modal via 'View in
            // Payments' — pre-filter to exactly that ref, mirroring the same
            // ?ref= pattern admin/document-requests.blade.php already uses.
            async init() {
                const params = new URLSearchParams(window.location.search);
                const ref = params.get('ref');
                if (ref) this.search = ref;
                await this.loadPayments();
            },
            get visibleCount() { return this.payments.filter(p => this.matches(p)).length; },
            // A request rejected or cancelled closes its unpaid payment as Cancelled
            // (the server does it); a paid one on such a request is a refund for a
            // person to decide. Neither counts as owed.
            refundToDecide(p) { return p.status === 'Approved' && ['Rejected', 'Cancelled'].includes(p.request_status); },
            get totalInvoiced() { return this.payments.filter(p => p.status !== 'Cancelled').reduce((sum, p) => sum + Number(p.amount || 0), 0); },
            get totalCollected() { return this.payments.filter(p => p.status === 'Approved').reduce((sum, p) => sum + Number(p.amount || 0), 0); },
            async issueReceipt() {
                if (this.processing || !this.method || this.selected === null) return;
                this.processing = true;
                try {
                    const res = await $api.post('/admin/payments/' + this.payments[this.selected].ref + '/receipt', { method: this.method });
                    this.payments[this.selected].status = 'Approved';
                    this.payments[this.selected].method = this.method;
                    this.payments[this.selected]._receipt = res.data;
                    this.$dispatch('toast', { message: 'Receipt issued — payment marked as paid.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not issue the receipt.', variant: 'error' });
                } finally { this.processing = false; }
            },
            async rejectPayment() {
                if (this.processing || this.rejectReason.trim().length < 5 || this.selected === null) return;
                this.processing = true;
                try {
                    await $api.post('/admin/payments/' + this.payments[this.selected].ref + '/reject', { reason: this.rejectReason.trim() });
                    this.payments[this.selected].status = 'Rejected';
                    this.showRejectReason = false;
                    this.rejectReason = '';
                    this.$dispatch('toast', { message: 'Payment rejected — citizen will be notified.', variant: 'error' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not reject the payment.', variant: 'error' });
                } finally { this.processing = false; }
            },
        }"
        class="animate-fade-up"
    >

        <div x-show="!$store.session.can('payments')" x-cloak>
            <x-admin.access-restricted module="Payments" icon="receipt" />
        </div>

        <div x-show="$store.session.can('payments')" x-cloak>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
            <x-ui.stat-card layout="compact" label="Total Invoiced" valueBind="'₱' + totalInvoiced.toLocaleString('en-PH', { minimumFractionDigits: 2 })" icon="receipt" color="brand" sublabel="Not counting closed requests" :delay="0" />
            <x-ui.stat-card layout="compact" label="Total Collected" valueBind="'₱' + totalCollected.toLocaleString('en-PH', { minimumFractionDigits: 2 })" icon="banknote" color="green" :delay="40" />
            <x-ui.stat-card layout="compact" label="Paid" valueBind="payments.filter(p => p.status === 'Approved').length" icon="circle-check" color="green" :delay="80" />
            <x-ui.stat-card layout="compact" label="Pending Review" valueBind="payments.filter(p => p.status === 'Pending Review').length" icon="clock" color="orange" sublabel="Awaiting receipt" :delay="120" />
            <x-ui.stat-card layout="compact" label="Rejected" valueBind="payments.filter(p => p.status === 'Rejected').length" icon="circle-x" color="red" :delay="160" />
        </div>

        <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input aria-label="Search reference or citizen" type="text" x-model="search" placeholder="Search reference or citizen..." class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                </div>
                <select aria-label="Barangay filter" x-show="isMunicipalScope" x-model="barangayFilter" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard shrink-0 border-0 surface-pressed-sm">
                    <option value="">All Barangays</option>
                    <template x-for="b in barangays" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                </select>
                <span x-show="!isMunicipalScope" x-cloak class="inline-flex items-center gap-1.5 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-100 rounded-xl px-3 py-2 shrink-0">
                    <i data-lucide="lock" class="w-3 h-3"></i>Brgy. <span x-text="$store.session.account?.scope"></span>
                </span>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit max-w-full">
                    <button type="button" @click="tab = 'all'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="tab === 'all' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">All</button>
                    <button type="button" @click="tab = 'review'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="tab === 'review' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">Pending Review</button>
                    <button type="button" @click="tab = 'paid'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="tab === 'paid' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">Paid</button>
                    <button type="button" @click="tab = 'rejected'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="tab === 'rejected' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">Rejected</button>
                    <button type="button" @click="tab = 'closed'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="tab === 'closed' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" title="Unpaid payments of requests that were rejected or cancelled">Request closed</button>
                    <button type="button" x-show="payments.some((p) => refundToDecide(p))" x-cloak @click="tab = 'refund'" class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap" :class="tab === 'refund' ? 'bg-white text-ink shadow-sm' : 'text-amber-700 hover:text-amber-800'">Refund to decide <span x-text="payments.filter((p) => refundToDecide(p)).length"></span></button>
                </div>
                <p class="text-[11px] text-ink-muted shrink-0" x-text="visibleCount + ' of ' + payments.length + ' payments'"></p>
            </div>
        </div>

        <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /><x-ui.skeleton class="h-12 opacity-60" /></div>
        <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load payments.'" retryExpr="loadPayments()" :retryable="true" /></div>

        <div x-show="!loading && !failed">
        <x-ui.table>
            <thead>
                <tr class="bg-well/70">
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden xl:table-cell">Reference</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Citizen</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden 2xl:table-cell">Item</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Amount</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden 2xl:table-cell">Method</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden xl:table-cell">Date</th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-600/[.08]">
                <template x-for="(p, i) in payments" :key="p.ref">
                <tr
                    x-show="matches(p)" x-cloak
                    class="hover:bg-white/60 transition-colors group cursor-pointer"
                    @click="open = true; selected = i; method = p.method; showRejectReason = false; rejectReason = ''; $nextTick(() => window.renderIcons?.())"
                    tabindex="0"
                    @keydown.enter.prevent="open = true; selected = i; method = p.method; showRejectReason = false; rejectReason = ''; $nextTick(() => window.renderIcons?.())"
                    @keydown.space.prevent="open = true; selected = i; method = p.method; showRejectReason = false; rejectReason = ''; $nextTick(() => window.renderIcons?.())"
                >
                    <td class="px-4 py-3 font-mono text-xs text-ink-muted hidden xl:table-cell" x-text="p.ref"></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 text-xs rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0" x-text="(p.citizen || '').split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                            <div class="min-w-0">
                                <p class="text-slate-700 font-medium whitespace-nowrap truncate group-hover:text-brand-600 transition-colors" x-text="p.citizen"></p>
                                {{-- Which request it pays for, at every width: the Item column is hidden below 2xl. --}}
                                <p class="text-[11px] text-ink-muted truncate" x-text="(p.item || '') + (p.request_ref ? ' · ' + p.request_ref : '')"></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 hidden 2xl:table-cell">
                        <p class="text-slate-700" x-text="p.item"></p>
                        <p class="text-[11px] text-ink-muted mt-0.5" x-text="p.type"></p>
                    </td>
                    <td class="px-4 py-3 text-slate-700 font-semibold whitespace-nowrap" x-text="'₱' + Number(p.amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })"></td>
                    <td class="px-4 py-3 text-ink-muted hidden 2xl:table-cell" x-text="p.method || '—'"></td>
                    <td class="px-4 py-3 text-ink-muted hidden xl:table-cell whitespace-nowrap" x-text="p.date"></td>
                    <td class="px-4 py-3">
                        <x-ui.badge statusBind="p.status" />
                        <p x-show="refundToDecide(p)" x-cloak class="text-[10.5px] font-medium text-amber-700 mt-1" x-text="'Request ' + (p.request_status || '').toLowerCase() + ': refund to decide'"></p>
                        <p x-show="p.status === 'Cancelled' && p.request_status" x-cloak class="text-[10.5px] text-ink-muted mt-1" x-text="'Request ' + (p.request_status || '').toLowerCase()"></p>
                    </td>
                    <td class="px-4 py-3 text-right" @click.stop>
                        <button
                            @click="open = true; selected = i; method = p.method; showRejectReason = false; rejectReason = ''; $nextTick(() => window.renderIcons?.())"
                            class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap"
                        >Manage</button>
                    </td>
                </tr>
                </template>
            </tbody>
        </x-ui.table>

        <div class="flex flex-col items-center text-center py-10 bg-white border border-brand-600/10 rounded-2xl mt-3" x-show="visibleCount === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No payments match these filters.</p>
        </div>
        </div>

        <x-ui.modal title="Payment Details" maxWidth="lg">
            <template x-if="selected !== null">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-mono text-ink-muted" x-text="payments[selected].ref"></span>
                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full" :class="payments[selected].type === 'Dokyu' ? 'bg-brand-50 text-brand-700' : 'bg-purple-50 text-purple-700'" x-text="payments[selected].type"></span>
                    </div>

                    {{-- Invoice — same letterhead + icon-field treatment as the
                         Submitted Application replica in Dokyu/Tulong, so a
                         payment record reads as the same kind of official
                         document instead of a differently-styled one-off. --}}
                    <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-sm mb-5">
                        <div class="relative bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 px-4 py-3.5 text-white overflow-hidden">
                            <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full bg-white/5 pointer-events-none"></div>
                            <div class="relative flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[8px] uppercase tracking-widest text-white/50">Republic of the Philippines · Municipality of Esperanza, Masbate</p>
                                    <p class="text-xs font-bold uppercase tracking-wide mt-0.5">Invoice Summary</p>
                                </div>
                                <span class="inline-flex items-center gap-1 text-[10px] font-medium text-white/70 bg-white/10 rounded-full px-2 py-1 shrink-0">
                                    <i data-lucide="receipt" class="w-3 h-3"></i> <span x-text="payments[selected].ref"></span>
                                </span>
                            </div>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3.5 bg-white">
                            <div class="flex items-start gap-2.5">
                                <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-ink-muted flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                <div class="min-w-0 flex-1">
                                    <label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-0.5">Citizen</label>
                                    <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700 truncate" x-text="payments[selected].citizen"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-ink-muted flex items-center justify-center shrink-0"><i data-lucide="calendar-days" class="w-4 h-4"></i></span>
                                <div class="min-w-0 flex-1">
                                    <label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-0.5">Date Invoiced</label>
                                    <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700" x-text="payments[selected].date"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5 sm:col-span-2">
                                <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-ink-muted flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                                <div class="min-w-0 flex-1">
                                    <label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-0.5">Item</label>
                                    <p class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700" x-text="payments[selected].item"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5 sm:col-span-2">
                                <span class="w-8 h-8 rounded-lg bg-brand-50 border border-brand-100 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="wallet" class="w-4 h-4"></i></span>
                                <div class="min-w-0 flex-1 flex items-center justify-between gap-3 rounded-lg border border-brand-100 bg-brand-50/50 px-3 py-2">
                                    <span class="text-sm font-semibold text-ink">Total Due</span>
                                    <span class="text-base font-bold text-brand-700" x-text="'₱' + Number(payments[selected].amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jumps to the same request in Dokyu/Tulong to see the full
                         submitted application and documents behind this invoice —
                         a payment record on its own is only half the story. -->
                    <button
                        type="button"
                        @click="window.location.href = (payments[selected].type === 'Dokyu' ? '/admin/document-requests' : '/admin/assistance-requests') + '?ref=' + encodeURIComponent(payments[selected].ref)"
                        class="w-full flex items-center justify-between gap-3 rounded-2xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/30 transition-colors p-3 mb-5 group"
                    >
                        <span class="flex items-center gap-2.5 text-left">
                            <span class="w-8 h-8 rounded-lg bg-slate-100 group-hover:bg-brand-100 text-ink-muted group-hover:text-brand-600 flex items-center justify-center shrink-0 transition-colors"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold text-ink">View Full Application</span>
                                <span class="block text-[11px] text-ink-muted">Submitted form and documents in <span x-text="payments[selected].type"></span></span>
                            </span>
                        </span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-ink-faint group-hover:text-brand-500 group-hover:translate-x-0.5 transition-all shrink-0"></i>
                    </button>

                    <!-- Receipt already on file (either just issued this session,
                         which carries the real receipt number, or approved
                         earlier — the admin list endpoint doesn't return a
                         receipt number for those, so only the method and date
                         are shown, honestly, rather than a fabricated one). -->
                    <div x-show="payments[selected].status === 'Approved'" x-cloak class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0"><i data-lucide="circle-check" class="w-4 h-4"></i></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-emerald-800">Paid via <span x-text="payments[selected].method || 'unspecified method'"></span></p>
                                <p class="text-[11px] text-emerald-700/80" x-text="payments[selected]._receipt ? ('Receipt ' + payments[selected]._receipt.receipt_no + ' issued ' + new Date(payments[selected]._receipt.issued_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })) : (payments[selected].receipt_no ? ('Receipt ' + payments[selected].receipt_no + ' · paid on ' + payments[selected].date) : ('On ' + payments[selected].date))"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Admin: issue the official receipt (Pending Review rows) -->
                    <div x-show="payments[selected].status === 'Pending Review'" x-cloak>
                        <p class="text-sm font-semibold text-ink mb-3 mt-4">Issue Receipt</p>
                        <p class="text-xs text-ink-muted mb-3">Select how the citizen paid, then issue the official receipt.</p>
                        {{-- The server refuses a receipt for a provisional fee (FEE_SCHEDULE_PENDING);
                             the cashier learned that only after trying (live check, 2026-10-06). --}}
                        <p x-show="payments[selected].provisional" x-cloak class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mb-3">No official receipt can be issued yet: the LGU has not set the official fee schedule for this service, so this amount is provisional. It does not hold the document's release.</p>
                        <div class="space-y-2 mb-4">
                            <template x-for="m in [{ name: 'GCash', icon: 'smartphone' }, { name: 'PayMaya', icon: 'wallet' }, { name: 'Payment in Center', icon: 'building-2' }]" :key="m.name">
                                <label
                                    class="flex items-center gap-3 rounded-xl border p-3 cursor-pointer transition-all duration-[160ms] ease-standard"
                                    :class="method === m.name ? 'border-brand-400 bg-brand-50/60 ring-4 ring-brand-100' : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <input type="radio" name="admin_payment_method" class="text-brand-600 focus:ring-brand-200" :checked="method === m.name" @change="method = m.name">
                                    <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors" :class="method === m.name ? 'bg-brand-100 text-brand-600' : 'bg-slate-100 text-ink-muted'"><i :data-lucide="m.icon" class="w-3.5 h-3.5"></i></span>
                                    <span class="text-sm font-medium text-slate-700" x-text="m.name"></span>
                                </label>
                            </template>
                        </div>

                        <div x-show="showRejectReason" x-cloak x-transition class="rounded-xl border border-rose-200 bg-rose-50/60 p-3 mb-1">
                            <label class="block text-[11px] font-medium text-rose-700 uppercase tracking-wide mb-1.5">Reason for Rejection</label>
                            <textarea aria-label="Reason for Rejection" x-model="rejectReason" rows="2" placeholder="Explain why this payment could not be verified (min. 5 characters)..." class="w-full text-xs rounded-xl border border-rose-200 bg-white px-3 py-2 focus:border-rose-400 focus:ring-4 focus:ring-rose-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                            <div class="flex items-center justify-end gap-2 mt-2">
                                <button type="button" @click="showRejectReason = false; rejectReason = ''" class="text-[11px] text-ink-muted hover:text-slate-600">Cancel</button>
                                <button type="button" :disabled="rejectReason.trim().length < 5 || processing" @click="rejectPayment()" class="text-[11px] font-medium text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:pointer-events-none rounded-xl px-3 py-1.5">Confirm Rejection</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <x-slot:footer>
                <template x-if="selected !== null && payments[selected].status === 'Pending Review' && !showRejectReason">
                    <div class="flex items-center gap-2.5 w-full">
                        <x-ui.button variant="danger" size="sm" @click="showRejectReason = true">Reject</x-ui.button>
                        <x-ui.button size="sm" x-bind:disabled="!method || processing || payments[selected].provisional" @click="issueReceipt()" class="flex-1">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="processing" x-cloak></i>
                                <i data-lucide="check" class="w-3.5 h-3.5" x-show="!processing"></i>
                                <span x-text="processing ? 'Issuing…' : 'Issue Receipt'"></span>
                            </span>
                        </x-ui.button>
                    </div>
                </template>
                <template x-if="selected !== null && !(payments[selected].status === 'Pending Review' && !showRejectReason)">
                    <x-ui.button size="sm" @click="open = false; showRejectReason = false">Close</x-ui.button>
                </template>
            </x-slot:footer>
        </x-ui.modal>

        </div>
    </div>
</x-layouts.admin>
