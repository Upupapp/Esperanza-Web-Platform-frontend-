{{-- tab-occupations.blade.php — the occupation review list (owner, 2026-10-29).
     What the DRRM matcher left for a person, grouped by the words people typed:
     GET /admin/livelihood/review (+ /records), POST /admin/livelihood/review/classify.
     A review classifies the records; it never changes the words they typed. --}}

<div class="space-y-5"
     x-data="{
        rows: [], counts: { needs_review: 0, likely_test: 0, conflict: 0 },
        status: '', importRef: '', loading: false, failed: false,
        open: null, records: [], recordsLoading: false, picked: [],
        choice: { livelihood_code: '', livelihood_other: '', occupation: '' }, saving: false,
        reasons: { needs_review: 'Needs a person', likely_test: 'Looks like test data', conflict: 'Differs from the linked account' },
        get total() { return this.counts.needs_review + this.counts.likely_test + this.counts.conflict; },
        async load() {
            await this.$store.session.whenReady?.();
            if (!this.$store.session.canPerm('const_resident', 'view')) return;
            this.loading = true; this.failed = false;
            try {
                const res = await $api.get('/admin/livelihood/review', { params: { status: this.status || undefined, import: this.importRef || undefined } });
                this.rows = res.data || [];
                this.counts = { needs_review: 0, likely_test: 0, conflict: 0, ...(res.meta?.counts || {}) };
                if (!this.status && !this.importRef) this.$dispatch('livelihood-review-count', this.total);
            } catch { this.failed = true; } finally { this.loading = false; }
        },
        async review(row) {
            this.open = row; this.records = []; this.picked = [];
            this.choice = { livelihood_code: '', livelihood_other: '', occupation: '' };
            this.recordsLoading = true;
            try {
                const res = await $api.getAll('/admin/livelihood/review/records', { params: { value: row.value, status: row.status, import: this.importRef || undefined }, maxPages: 20 });
                this.records = res.data || [];
            } catch { this.records = []; } finally { this.recordsLoading = false; }
        },
        async classify() {
            if (this.saving || !this.open || !this.choice.livelihood_code) return;
            this.saving = true;
            try {
                const body = { value: this.open.value, status: this.open.status, livelihood_code: this.choice.livelihood_code, livelihood_other: this.choice.livelihood_other || null, import: this.importRef || undefined };
                if (this.picked.length) body.refs = this.picked;
                const res = await $api.post('/admin/livelihood/review/classify', body);
                // classified counts only what settled; a record whose resident chose
                // a different occupation on their account keeps the choice but stays
                // in conflict, and is counted in still_conflict (QA 2026-11-18 #58).
                const n = Number(res.data?.classified || 0);
                const left = Number(res.data?.still_conflict || 0);
                const parts = [];
                if (n || !left) parts.push(n + (n === 1 ? ' record' : ' records') + ' classified as ' + this.$store.livelihood.label(res.data.code) + '.');
                if (left) parts.push(left + (left === 1 ? ' record is' : ' records are') + ' still in conflict: the resident chose a different occupation on their account. Pick the one they chose, or ask them to change theirs.');
                this.$dispatch('toast', { message: parts.join(' '), variant: left ? 'warning' : 'success' });
                this.open = null;
                await this.load();
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not classify these records.', variant: 'error' });
            } finally { this.saving = false; }
        },
     }"
     x-init="load(); $store.livelihood.load()"
     x-on:livelihood-review-import.window="importRef = $event.detail || ''; status = ''; open = null; load()">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="max-w-2xl">
            <h3 class="text-sm font-bold text-ink">Occupations to classify</h3>
            <p class="text-xs text-ink-muted mt-1">Occupations the DRRM list could not classify with certainty. Choose the right item once for each typed value; the words people typed stay as they are. Linked app accounts follow the registry.</p>
        </div>
        <button type="button" @click="load()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 hover:bg-white">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>Refresh
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter by reason">
        <template x-for="opt in [['', 'All waiting', total], ['needs_review', reasons.needs_review, counts.needs_review], ['likely_test', reasons.likely_test, counts.likely_test], ['conflict', reasons.conflict, counts.conflict]]" :key="opt[0]">
            <button type="button" @click="status = opt[0]; open = null; load()" :aria-pressed="status === opt[0]"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors"
                    :class="status === opt[0] ? 'border-brand-400 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                <span x-text="opt[1]"></span><span class="font-bold tabular-nums" x-text="opt[2]"></span>
            </button>
        </template>
        <span x-show="importRef" x-cloak class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs bg-purple-50 text-purple-700 border border-purple-200">
            From import <strong x-text="importRef"></strong>
            <button type="button" @click="importRef = ''; load()" aria-label="Show every import" class="hover:text-purple-900"><i data-lucide="x" class="w-3 h-3"></i></button>
        </span>
    </div>

    <p x-show="failed" x-cloak class="text-sm text-rose-700">The list could not load. Refresh to try again.</p>
    <p x-show="!loading && !failed && rows.length === 0" x-cloak class="text-sm text-ink-muted py-6 text-center">Nothing is waiting. Every typed occupation here is classified.</p>

    <div x-show="rows.length" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="text-xs text-ink-muted uppercase tracking-wide bg-slate-50">
                <tr>
                    <th scope="col" class="text-left font-medium px-4 py-2.5">Typed occupation</th>
                    <th scope="col" class="text-left font-medium px-4 py-2.5">Waiting because</th>
                    <th scope="col" class="text-right font-medium px-4 py-2.5">Residents</th>
                    <th scope="col" class="text-right font-medium px-4 py-2.5">App accounts</th>
                    <th scope="col" class="px-4 py-2.5"><span class="sr-only">Action</span></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in rows" :key="row.status + '|' + row.value">
                    <tr class="border-t border-slate-100" :class="open && open.value === row.value && open.status === row.status ? 'bg-brand-50/40' : ''">
                        <td class="px-4 py-2.5 font-semibold text-ink" x-text="row.value"></td>
                        <td class="px-4 py-2.5 text-xs" :class="row.status === 'likely_test' ? 'text-amber-700' : (row.status === 'conflict' ? 'text-rose-700' : 'text-ink-muted')" x-text="reasons[row.status]"></td>
                        <td class="px-4 py-2.5 text-right tabular-nums" x-text="row.residents"></td>
                        <td class="px-4 py-2.5 text-right tabular-nums" x-text="row.accounts"></td>
                        <td class="px-4 py-2.5 text-right">
                            <button type="button" x-show="row.residents > 0 || row.accounts > 0" @click="review(row)" class="text-xs font-medium text-brand-600 hover:text-brand-700">Review</button>
                            <span x-show="row.residents === 0 && !(row.accounts > 0)" class="text-[11px] text-ink-muted">Follows the registry</span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- One value's records, and the choice for them. --}}
    <section x-show="open" x-cloak class="rounded-2xl border border-brand-200 bg-white p-4 space-y-4" aria-label="Classify a typed occupation">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <p class="text-xs text-ink-muted">Typed as</p>
                <p class="text-base font-bold text-ink" x-text="open ? open.value : ''"></p>
                <p class="text-xs text-amber-700 mt-1" x-show="open && open.status === 'likely_test'">These look like test entries. Fix or remove the test records in the registry rather than classifying them as real work.</p>
                <p class="text-xs text-rose-700 mt-1" x-show="open && open.status === 'conflict'">The registry record and the resident's app account say different things. Both are shown below. It is settled only when you pick the occupation the resident chose on their account; any other choice is kept on the record, but it stays in conflict until the resident changes theirs.</p>
            </div>
            <button type="button" @click="open = null" class="text-xs text-ink-muted hover:text-ink">Close</button>
        </div>

        <div class="overflow-x-auto max-h-72 overflow-y-auto rounded-xl border border-slate-100">
            <table class="w-full text-xs">
                <thead class="text-ink-muted bg-slate-50 sticky top-0">
                    <tr>
                        <th scope="col" class="px-3 py-2 w-8"><span class="sr-only">Choose</span></th>
                        <th scope="col" class="text-left font-medium px-3 py-2">Resident</th>
                        <th scope="col" class="text-left font-medium px-3 py-2">Barangay</th>
                        <th scope="col" class="text-left font-medium px-3 py-2">App account says</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in records" :key="r.ref">
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-1.5"><input type="checkbox" :value="r.ref" x-model="picked" :aria-label="'Only ' + r.name" class="rounded border-slate-300 text-brand-600"></td>
                            <td class="px-3 py-1.5"><span class="font-medium text-ink" x-text="r.name"></span> <span class="text-ink-muted" x-text="r.ref"></span></td>
                            <td class="px-3 py-1.5" x-text="r.barangay"></td>
                            <td class="px-3 py-1.5" x-text="r.account ? (r.account.occupation || '—') + (r.account.livelihood?.code ? ' (' + r.account.livelihood.code + ')' : '') : '—'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <p x-show="recordsLoading" class="text-xs text-ink-muted p-3">Loading records…</p>
            {{-- Held only by app accounts with no registry record (QA 2026-11-18 #58):
                 the server now classifies those accounts directly by the typed words. --}}
            <p x-show="!recordsLoading && open && records.length === 0" x-cloak class="text-xs text-ink-muted p-3">No registry record says this, only residents' app accounts that are not linked to a registry record. Your choice classifies those accounts. An account linked to a registry record follows its record.</p>
        </div>

        <div x-show="$store.session.canPerm('const_resident', 'edit')" class="space-y-3">
            <x-livelihood-select model="choice" id="lv-review" />
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button size="sm" icon="check" x-bind:disabled="saving || !choice.livelihood_code" @click="classify()">
                    <span x-text="saving ? 'Saving…' : (picked.length ? 'Classify the ' + picked.length + ' chosen' : 'Classify all ' + records.length)"></span>
                </x-ui.button>
                <span class="text-xs text-ink-muted" x-show="!picked.length">Tick residents above to classify only some of them.</span>
            </div>
        </div>
        <p x-show="!$store.session.canPerm('const_resident', 'edit')" class="text-xs text-ink-muted">Your role can see this list but not classify. A records officer or administrator can.</p>
    </section>
</div>
