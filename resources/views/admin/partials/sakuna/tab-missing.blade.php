{{-- tab-missing.blade.php — Missing Persons (owner, 2026-10-30).
     People in a disaster case, each under an incident: GET /admin/sakuna/missing
     (+ /{ref}), POST /admin/sakuna/incidents/{ref}/missing, PUT /admin/sakuna/missing/{ref},
     POST /admin/sakuna/missing/{ref}/status. Written by the MDRRMO and the
     Super Administrator (sakuna_missing create/edit/close); a closed case is kept. --}}

<div class="space-y-5"
     x-data="{
        rows: [], page: 1, lastPage: 1, total: 0, loading: false, failed: false, off: false,
        f: { q: '', status: 'active', kind: '', barangay: '', incident: '', from: '', to: '' },
        open: null, saving: false, move: { status: '', found_place: '', note: '' },
        adding: false, incidents: [], residents: [], residentQuery: '',
        form: { incident: '', kind: 'missing', individual_ref: '', name: '', sex: '', age: '', barangay: '', description: '', last_seen_place: '', last_seen_at: '' },
        statusTone: { 'Missing': 'bg-rose-50 text-rose-700', 'Unidentified': 'bg-amber-50 text-amber-800', 'Deceased': 'bg-slate-100 text-slate-700', 'Found deceased': 'bg-slate-100 text-slate-700', 'Found safe': 'bg-emerald-50 text-emerald-700', 'Found injured': 'bg-orange-50 text-orange-700', 'Identified': 'bg-sky-50 text-sky-700', 'Closed': 'bg-slate-50 text-slate-500' },
        kinds: { missing: 'Missing person', deceased: 'Deceased person', unidentified: 'Unidentified body' },
        when(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'; },
        params() { return Object.fromEntries(Object.entries({ ...this.f, page: this.page, per_page: 25 }).filter(([, v]) => v !== '' && v !== null)); },
        async load(page = 1) {
            this.page = page; this.loading = true; this.failed = false;
            try {
                const res = await $api.get('/admin/sakuna/missing', { params: this.params() });
                this.rows = res.data || []; this.total = res.meta?.total ?? this.rows.length; this.lastPage = res.meta?.last_page || 1;
            } catch (err) {
                if (err?.code === 'FEATURE_OFF' || err?.status === 404) this.off = true; else this.failed = true;
            } finally { this.loading = false; }
        },
        clear() { this.f = { q: '', status: 'active', kind: '', barangay: '', incident: '', from: '', to: '' }; this.load(1); },
        async show(ref) {
            try { this.open = (await $api.get('/admin/sakuna/missing/' + encodeURIComponent(ref))).data; this.move = { status: '', found_place: '', note: '' }; }
            catch { this.$dispatch('toast', { message: 'Could not open this case.', variant: 'error' }); }
        },
        async moveTo() {
            if (this.saving || !this.open || !this.move.status) return;
            this.saving = true;
            try {
                await $api.post('/admin/sakuna/missing/' + encodeURIComponent(this.open.ref) + '/status', { status: this.move.status, found_place: this.move.found_place || null, note: this.move.note || null });
                this.$dispatch('toast', { message: this.open.ref + ' is now ' + this.move.status + '.', variant: 'success' });
                await this.show(this.open.ref); await this.load(this.page);
            } catch (err) { this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not change this case.', variant: 'error' }); }
            finally { this.saving = false; }
        },
        // A Balita draft from the case (owner, 2026-10-30): it goes through
        // Balita's review; the MIO, the Super Administrator or the MDRRMO Head
        // approves it, never its writer.
        async draftPost() {
            if (this.saving || !this.open) return;
            this.saving = true;
            try {
                const res = await $api.post('/admin/sakuna/missing/' + encodeURIComponent(this.open.ref) + '/announcement');
                this.$dispatch('toast', { message: 'Draft ready in Balita. Check it and send it for review.', variant: 'success' });
                await this.show(this.open.ref);
                window.open('{{ route('admin.communications') }}?tab=balita&post=' + res.data.id, '_self');
            } catch (err) { this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not prepare the post.', variant: 'error' }); }
            finally { this.saving = false; }
        },
        async startAdd() {
            this.adding = true; this.residents = []; this.residentQuery = '';
            this.form = { incident: this.f.incident || '', kind: 'missing', individual_ref: '', name: '', sex: '', age: '', barangay: '', description: '', last_seen_place: '', last_seen_at: '' };
            try { this.incidents = (await $api.get('/admin/sakuna/incidents', { params: { per_page: 100 } })).data || []; } catch { this.incidents = []; }
        },
        async findResident() {
            const q = this.residentQuery.trim();
            if (q.length < 2) { this.residents = []; return; }
            try { this.residents = (await $api.get('/admin/residents', { params: { q, per_page: 10 } })).data || []; } catch { this.residents = []; }
        },
        async record() {
            if (this.saving || !this.form.incident) return;
            this.saving = true;
            const body = Object.fromEntries(Object.entries(this.form).filter(([k, v]) => k !== 'incident' && v !== '' && v !== null));
            if (body.age !== undefined) body.age = Number(body.age);
            try {
                const res = await $api.post('/admin/sakuna/incidents/' + encodeURIComponent(this.form.incident) + '/missing', body);
                this.$dispatch('toast', { message: res.data.ref + ' recorded.', variant: 'success' });
                this.adding = false; await this.load(1); await this.show(res.data.ref);
            } catch (err) { this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not record this person.', variant: 'error' }); }
            finally { this.saving = false; }
        },
     }"
     x-init="f.incident = new URLSearchParams(window.location.search).get('incident') || ''; load(1)">

    <div x-show="off" x-cloak class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-ink-muted">
        Missing Persons is not switched on here yet. It holds personal details of people in a disaster, so it opens once the LGU's Data Protection Officer agrees.
    </div>

    <div x-show="!off" class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="max-w-2xl">
                <h3 class="text-sm font-bold text-ink">Missing Persons</h3>
                <p class="text-xs text-ink-muted mt-1">Missing, found, deceased and unidentified persons, each recorded under the incident they belong to. A resolved case is closed and kept.</p>
            </div>
            <x-ui.button size="sm" icon="user-plus" x-show="can('sakuna_missing','create')" @click="startAdd()">Record a person</x-ui.button>
        </div>

        {{-- Filters --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 rounded-2xl border border-slate-200 bg-white p-4">
            <label class="text-xs text-ink-muted">Search
                <input type="search" x-model="f.q" @input.debounce.400ms="load(1)" placeholder="Name or case number" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
            </label>
            <label class="text-xs text-ink-muted">Status
                <select x-model="f.status" @change="load(1)" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                    <option value="">Every status</option><option value="active">Still missing or unidentified</option><option value="found">Found</option>
                    <option>Missing</option><option>Found safe</option><option>Found injured</option><option>Found deceased</option>
                    <option>Deceased</option><option>Unidentified</option><option>Identified</option><option>Closed</option>
                </select>
            </label>
            <label class="text-xs text-ink-muted">Kind
                <select x-model="f.kind" @change="load(1)" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                    <option value="">Every kind</option><option value="missing">Missing person</option><option value="deceased">Deceased person</option><option value="unidentified">Unidentified body</option>
                </select>
            </label>
            <label class="text-xs text-ink-muted">Barangay
                <select x-model="f.barangay" @change="load(1)" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                    <option value="">Every barangay</option>
                    @foreach($barangays as $b)<option value="{{ is_array($b) ? $b['name'] : $b }}">{{ is_array($b) ? $b['name'] : $b }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs text-ink-muted">Incident number
                <input type="text" x-model="f.incident" @change="load(1)" placeholder="INC-2026-…" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
            </label>
            <label class="text-xs text-ink-muted">Recorded from
                <input type="date" x-model="f.from" @change="load(1)" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
            </label>
            <label class="text-xs text-ink-muted">Recorded to
                <input type="date" x-model="f.to" :min="f.from" @change="load(1)" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
            </label>
            <div class="flex items-end"><button type="button" @click="clear()" class="text-xs text-ink-muted hover:text-rose-600">Clear filters</button></div>
        </div>

        <p x-show="failed" x-cloak class="text-sm text-rose-700">The list could not load. Try again.</p>
        <p x-show="!loading && !failed && rows.length === 0" x-cloak class="text-sm text-ink-muted text-center py-6">No cases match these filters.</p>

        <div x-show="rows.length" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="text-xs text-ink-muted uppercase tracking-wide bg-slate-50">
                    <tr>
                        <th scope="col" class="text-left font-medium px-4 py-2.5">Case</th>
                        <th scope="col" class="text-left font-medium px-4 py-2.5">Person</th>
                        <th scope="col" class="text-left font-medium px-4 py-2.5">Status</th>
                        <th scope="col" class="text-left font-medium px-4 py-2.5">Barangay</th>
                        <th scope="col" class="text-left font-medium px-4 py-2.5">Last seen</th>
                        <th scope="col" class="text-left font-medium px-4 py-2.5">Incident</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in rows" :key="r.ref">
                        <tr class="border-t border-slate-100 hover:bg-slate-50 cursor-pointer" @click="show(r.ref)" @keydown.enter="show(r.ref)" tabindex="0">
                            <td class="px-4 py-2.5 font-mono text-xs" x-text="r.ref"></td>
                            <td class="px-4 py-2.5"><span class="font-semibold text-ink" x-text="r.name || 'Unidentified'"></span> <span class="text-xs text-ink-muted" x-text="kinds[r.kind]"></span></td>
                            <td class="px-4 py-2.5"><span class="text-xs font-semibold px-2 py-1 rounded-lg" :class="statusTone[r.status]" x-text="r.status"></span></td>
                            <td class="px-4 py-2.5" x-text="r.barangay || '—'"></td>
                            <td class="px-4 py-2.5 text-xs" x-text="(r.last_seen_place || '—') + (r.last_seen_at ? ' · ' + when(r.last_seen_at) : '')"></td>
                            <td class="px-4 py-2.5 text-xs" x-text="r.incident ? r.incident.ref + ' · ' + r.incident.type : '—'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="lastPage > 1" class="flex items-center justify-end gap-2 text-xs">
            <button type="button" :disabled="page <= 1" @click="load(page - 1)" class="px-3 py-1.5 rounded-xl border border-slate-200 disabled:opacity-40">Previous</button>
            <span x-text="'Page ' + page + ' of ' + lastPage + ' · ' + total + ' cases'"></span>
            <button type="button" :disabled="page >= lastPage" @click="load(page + 1)" class="px-3 py-1.5 rounded-xl border border-slate-200 disabled:opacity-40">Next</button>
        </div>

        {{-- One case --}}
        <section x-show="open" x-cloak class="rounded-2xl border border-brand-200 bg-white p-5 space-y-4" aria-label="Case details">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p class="text-xs text-ink-muted" x-text="open ? open.ref + ' · ' + kinds[open.kind] : ''"></p>
                    <p class="text-lg font-bold text-ink" x-text="open ? (open.name || 'Unidentified') : ''"></p>
                    <span class="text-xs font-semibold px-2 py-1 rounded-lg" :class="open ? statusTone[open.status] : ''" x-text="open ? open.status : ''"></span>
                </div>
                <button type="button" @click="open = null" class="text-xs text-ink-muted hover:text-ink">Close</button>
            </div>
            <dl class="grid gap-3 sm:grid-cols-2 text-sm" x-show="open">
                <div><dt class="text-xs text-ink-muted">Incident</dt><dd x-text="open?.incident ? open.incident.ref + ' · ' + open.incident.title : '—'"></dd></div>
                <div><dt class="text-xs text-ink-muted">Registry record</dt><dd x-text="open?.registry_ref || 'Not in the registry'"></dd></div>
                <div><dt class="text-xs text-ink-muted">Sex and age</dt><dd x-text="[open?.sex, open?.age !== null && open?.age !== undefined ? open.age + ' years' : null].filter(Boolean).join(', ') || '—'"></dd></div>
                <div><dt class="text-xs text-ink-muted">Barangay</dt><dd x-text="open?.barangay || '—'"></dd></div>
                <div><dt class="text-xs text-ink-muted">Last seen</dt><dd x-text="(open?.last_seen_place || '—') + (open?.last_seen_at ? ' · ' + when(open.last_seen_at) : '')"></dd></div>
                <div><dt class="text-xs text-ink-muted">Found</dt><dd x-text="(open?.found_place || '—') + (open?.found_at ? ' · ' + when(open.found_at) : '')"></dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-ink-muted">Identifying description (internal)</dt><dd x-text="open?.description || '—'"></dd></div>
            </dl>

            <div x-show="open && open.next_statuses.length && can('sakuna_missing','edit')" class="rounded-xl bg-slate-50 p-3 space-y-2">
                <p class="text-xs font-semibold text-ink">Update the case</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="s in (open ? open.next_statuses : [])" :key="s">
                        <button type="button" @click="move.status = s" :aria-pressed="move.status === s"
                                x-show="s !== 'Closed' || can('sakuna_missing','close')"
                                class="text-xs px-3 py-1.5 rounded-full border" :class="move.status === s ? 'border-brand-400 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white'" x-text="s"></button>
                    </template>
                </div>
                <div class="grid gap-2 sm:grid-cols-2" x-show="move.status">
                    <input type="text" x-show="move.status.startsWith('Found')" x-model="move.found_place" placeholder="Where found" aria-label="Where found" class="text-sm border border-slate-200 rounded-xl px-3 py-2">
                    <input type="text" x-model="move.note" placeholder="Note for the history" aria-label="Note for the history" class="text-sm border border-slate-200 rounded-xl px-3 py-2">
                </div>
                <x-ui.button size="sm" icon="check" x-show="move.status" x-bind:disabled="saving" @click="moveTo()"><span x-text="saving ? 'Saving…' : 'Mark as ' + move.status"></span></x-ui.button>
            </div>

            <div class="rounded-xl border border-slate-200 p-3 space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-ink">Balita posts</p>
                    <x-ui.button size="sm" variant="secondary" icon="newspaper" x-show="open && open.status !== 'Closed' && can('sakuna_missing','edit') && can('comm_news','create')" x-bind:disabled="saving" @click="draftPost()">Prepare a Balita post</x-ui.button>
                </div>
                <p class="text-[11px] text-ink-muted">Nothing is posted by itself. A draft holds only what may be made public (no description, address or contact details) and needs approval by the Information Officer, the Super Administrator or the MDRRMO Head.</p>
                <ul class="space-y-1 text-xs">
                    <template x-for="a in (open ? (open.announcements || []) : [])" :key="a.id">
                        <li class="flex flex-wrap gap-x-2">
                            <a :href="'{{ route('admin.communications') }}?tab=balita&post=' + a.id" class="font-semibold text-brand-600 hover:underline" x-text="a.title"></a>
                            <span class="text-ink-muted" x-text="a.status"></span>
                            <span class="text-ink-muted" x-show="a.published" x-text="a.published ? 'published ' + when(a.published.released_at) + (a.published.released_by ? ' by ' + a.published.released_by : '') : ''"></span>
                        </li>
                    </template>
                </ul>
                <p class="text-[11px] text-ink-muted" x-show="open && !(open.announcements || []).length">No post has been prepared from this case.</p>
            </div>

            <div>
                <p class="text-xs font-semibold text-ink mb-2">History</p>
                <ol class="space-y-1.5 text-xs">
                    <template x-for="(h, i) in (open ? open.history : [])" :key="i">
                        <li class="flex flex-wrap gap-x-2"><span class="text-ink-muted" x-text="when(h.at)"></span><span class="font-semibold" x-text="h.to ? (h.from ? h.from + ' → ' + h.to : h.to) : 'Details updated'"></span><span class="text-ink-muted" x-text="h.by ? 'by ' + h.by : ''"></span><span x-text="h.note || ''"></span></li>
                    </template>
                </ol>
            </div>
        </section>

        {{-- Record a person --}}
        <section x-show="adding" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4" aria-label="Record a person">
            <div class="flex items-center justify-between"><p class="text-sm font-bold text-ink">Record a person</p><button type="button" @click="adding = false" class="text-xs text-ink-muted">Cancel</button></div>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="text-xs text-ink-muted">Incident *
                    <select x-model="form.incident" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                        <option value="">Choose the incident</option>
                        <template x-for="i in incidents" :key="i.ref"><option :value="i.ref" x-text="i.ref + ' · ' + i.type + ' · ' + i.barangay"></option></template>
                    </select>
                </label>
                <label class="text-xs text-ink-muted">Kind *
                    <select x-model="form.kind" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                        <option value="missing">Missing person</option><option value="deceased">Deceased person</option><option value="unidentified">Unidentified body</option>
                    </select>
                </label>
                <div class="sm:col-span-2" x-show="form.kind !== 'unidentified' && can('const_resident','view')">
                    <label class="text-xs text-ink-muted" for="mp-resident">Find the resident in the registry (optional)</label>
                    <input id="mp-resident" type="search" x-model="residentQuery" @input.debounce.400ms="findResident()" placeholder="Name or RES number" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                    <div class="flex flex-wrap gap-2 mt-2">
                        <template x-for="p in residents" :key="p.ref">
                            <button type="button" @click="form.individual_ref = p.ref; form.name = ''; residents = []; residentQuery = p.name + ' (' + p.ref + ')'" class="text-xs px-2.5 py-1 rounded-full border border-slate-200 hover:border-brand-300" x-text="p.name + ' · ' + p.barangay"></button>
                        </template>
                    </div>
                    <p class="text-[11px] text-ink-muted mt-1" x-show="form.individual_ref">The name, sex and barangay come from the registry record <span x-text="form.individual_ref"></span>.</p>
                </div>
                <label class="text-xs text-ink-muted" x-show="!form.individual_ref">Name
                    <input type="text" x-model="form.name" maxlength="160" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                </label>
                <label class="text-xs text-ink-muted">Sex
                    <select x-model="form.sex" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2"><option value="">—</option><option>Male</option><option>Female</option></select>
                </label>
                <label class="text-xs text-ink-muted">Approximate age
                    <input type="number" min="0" max="130" x-model="form.age" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                </label>
                <label class="text-xs text-ink-muted">Last seen at
                    <input type="text" x-model="form.last_seen_place" maxlength="255" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                </label>
                <label class="text-xs text-ink-muted">Last seen on
                    <input type="datetime-local" x-model="form.last_seen_at" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                </label>
                <label class="text-xs text-ink-muted sm:col-span-2">Identifying description (internal, not for publication)
                    <textarea x-model="form.description" rows="2" maxlength="2000" class="mt-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2"></textarea>
                </label>
            </div>
            <x-ui.button size="sm" icon="check" x-bind:disabled="saving || !form.incident" @click="record()"><span x-text="saving ? 'Saving…' : 'Record'"></span></x-ui.button>
        </section>
    </div>
</div>
