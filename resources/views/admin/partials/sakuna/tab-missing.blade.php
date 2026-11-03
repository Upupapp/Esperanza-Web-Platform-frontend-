{{-- tab-missing.blade.php — Missing Persons (owner, 2026-10-30).
     People in a disaster case, each under an incident: GET /admin/sakuna/missing
     (+ /{ref}), POST /admin/sakuna/incidents/{ref}/missing, PUT /admin/sakuna/missing/{ref},
     POST /admin/sakuna/missing/{ref}/status. Written by the MDRRMO and the
     Super Administrator (sakuna_missing create/edit/close); a closed case is kept. --}}

<div class="space-y-3"
     x-data="{
        rows: [], page: 1, lastPage: 1, total: 0, loading: false, failed: false, off: false, summary: null,
        f: { q: '', status: 'active', kind: '', barangay: '', incident: '', from: '', to: '' },
        open: null, saving: false, move: { status: '', found_place: '', note: '' },
        adding: false, incidents: [], residents: [], residentQuery: '',
        form: { incident: '', kind: 'missing', individual_ref: '', name: '', sex: '', age: '', barangay: '', description: '', last_seen_place: '', last_seen_at: '' },
        statusTone: { 'Missing': 'bg-orange-50 text-orange-800', 'Unidentified': 'bg-amber-50 text-amber-800', 'Deceased': 'bg-slate-100 text-slate-700', 'Found deceased': 'bg-slate-100 text-slate-700', 'Found safe': 'bg-emerald-50 text-emerald-700', 'Found injured': 'bg-orange-50 text-orange-700', 'Identified': 'bg-sky-50 text-sky-700', 'Closed': 'bg-slate-50 text-slate-500' },
        kinds: { missing: 'Missing person', deceased: 'Deceased person', unidentified: 'Unidentified body' },
        when(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'; },
        params() { return Object.fromEntries(Object.entries({ ...this.f, page: this.page, per_page: 25 }).filter(([, v]) => v !== '' && v !== null)); },
        async load(page = 1) {
            this.page = page; this.loading = true; this.failed = false;
            try {
                const res = await $api.get('/admin/sakuna/missing', { params: this.params() });
                this.rows = res.data || []; this.total = res.meta?.total ?? this.rows.length; this.lastPage = res.meta?.last_page || 1;
                saveCopy('missing', { params: JSON.stringify(this.params()), rows: this.rows, total: this.total, lastPage: this.lastPage });
            } catch (err) {
                const copy = lastCopy('missing');
                if (err?.code === 'FEATURE_OFF' || err?.status === 404) this.off = true;
                else if (copy && copy.data.params === JSON.stringify(this.params())) { useCopy('missing'); this.rows = copy.data.rows || []; this.total = copy.data.total || 0; this.lastPage = copy.data.lastPage || 1; }
                else this.failed = true;
            } finally { this.loading = false; }
        },
        // The stat strip: people, not cases, as the Command Center counts them.
        loadSummary() { $api.get('/admin/sakuna/missing/summary').then((r) => { this.summary = r.data; saveCopy('missing-summary', r.data); }).catch(() => { this.summary = lastCopy('missing-summary')?.data || null; }); },
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
                await this.show(this.open.ref); await this.load(this.page); this.loadSummary();
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
            this.form = { incident: '', kind: 'missing', individual_ref: '', name: '', sex: '', age: '', barangay: '', description: '', last_seen_place: '', last_seen_at: '' };
            try { this.incidents = (await $api.get('/admin/sakuna/incidents', { params: { per_page: 100 } })).data || []; } catch { this.incidents = []; }
            const want = this.f.incident || '';
            if (want && this.incidents.some((i) => i.ref === want)) this.$nextTick(() => { this.form.incident = want; });
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
                this.adding = false; await this.load(1); await this.show(res.data.ref); this.loadSummary();
            } catch (err) { this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not record this person.', variant: 'error' }); }
            finally { this.saving = false; }
        },
     }"
     x-init="f.incident = new URLSearchParams(window.location.search).get('incident') || ''; load(1); loadSummary()"
     @sakuna-missing-reload.window="load(page); loadSummary()">

    <div x-show="off" x-cloak class="sk-card sk-empty" style="padding: 24px;">
        Missing Persons is not switched on here yet. It holds personal details of people in a disaster, so it opens once the LGU's Data Protection Officer agrees.
    </div>

    <div x-show="!off" class="space-y-3">
        {{-- Stats + actions (Sakuna Redesign, 2026-11-02): counted as people,
             not cases, from GET /admin/sakuna/missing/summary like the Command Center. --}}
        <div class="sk-pagebar">
            <div class="sk-stats">
                <div class="sk-stat"><span class="sk-slab"><span class="sk-dot sk-t-amber" x-show="summary?.missing"></span>Still missing</span><span class="sk-sval tabular-nums" x-text="summary?.missing ?? '—'"></span></div>
                <div class="sk-stat"><span class="sk-slab"><span class="sk-dot sk-t-amber" x-show="summary?.unidentified"></span>Unidentified</span><span class="sk-sval tabular-nums" x-text="summary?.unidentified ?? '—'"></span></div>
                <div class="sk-stat"><span class="sk-slab">Recorded this week</span><span class="sk-sval tabular-nums" x-text="summary?.recent ?? '—'"></span></div>
                <div class="sk-stat"><span class="sk-slab">Found</span><span class="sk-sval tabular-nums" x-text="summary?.found ?? '—'"></span></div>
                <div class="sk-stat"><span class="sk-slab">Closed</span><span class="sk-sval tabular-nums" x-text="summary?.closed ?? '—'"></span></div>
            </div>
            <div class="sk-actions">
                <button type="button" data-info class="sk-iconbtn sk-iconbtn-bare" aria-label="About Missing Persons" title="Missing, found, deceased and unidentified persons, each recorded under the incident they belong to. A resolved case is closed and kept.">
                    <i data-lucide="info"></i>
                </button>
                <button type="button" class="sk-btn sk-btn-p disabled:opacity-50" x-show="can('sakuna_missing','create')" @click="startAdd()" :disabled="onLastCopy" :title="onLastCopy ? 'Waits for the connection' : ''">
                    <i data-lucide="user-plus"></i> Record a person
                </button>
            </div>
        </div>

        <section class="sk-card overflow-hidden">
            {{-- Filters --}}
            <h2 class="sr-only">Missing, found, deceased and unidentified persons</h2>
            <div class="sk-toolbar">
                <div class="sk-search">
                    <label for="mp-q" class="sr-only">Search name or case number</label>
                    <i data-lucide="search"></i>
                    <input id="mp-q" type="search" x-model="f.q" @input.debounce.400ms="load(1)" placeholder="Name or case number…">
                </div>
                <select aria-label="Status" x-model="f.status" @change="load(1)" class="sk-sel">
                    <option value="">Every status</option><option value="active">Still missing or unidentified</option><option value="found">Found</option>
                    <option>Missing</option><option>Found safe</option><option>Found injured</option><option>Found deceased</option>
                    <option>Deceased</option><option>Unidentified</option><option>Identified</option><option>Closed</option>
                </select>
                <select aria-label="Kind" x-model="f.kind" @change="load(1)" class="sk-sel">
                    <option value="">Every kind</option><option value="missing">Missing person</option><option value="deceased">Deceased person</option><option value="unidentified">Unidentified body</option>
                </select>
                <select aria-label="Barangay" x-model="f.barangay" @change="load(1)" class="sk-sel">
                    <option value="">Every barangay</option>
                    @foreach($barangays as $b)<option value="{{ is_array($b) ? $b['name'] : $b }}">{{ is_array($b) ? $b['name'] : $b }}</option>@endforeach
                </select>
                <div class="sk-search" style="flex: 0 1 150px; min-width: 120px;">
                    <label for="mp-incident" class="sr-only">Incident number</label>
                    <i data-lucide="hash"></i>
                    <input id="mp-incident" type="text" x-model="f.incident" @change="load(1)" placeholder="INC-2026-…">
                </div>
                <label class="sk-chk">Recorded from
                    <input type="date" x-model="f.from" @change="load(1)" class="sk-sel" style="width: auto; height: 32px; accent-color: auto;">
                </label>
                <label class="sk-chk">to
                    <input type="date" x-model="f.to" :min="f.from" @change="load(1)" class="sk-sel" style="width: auto; height: 32px; accent-color: auto;">
                </label>
                <button type="button" @click="clear()" class="sk-clear">Clear</button>
                <span class="sk-spacer"></span>
                <span class="sk-csub" x-show="!loading && !failed" x-text="total + (total === 1 ? ' case' : ' cases')"></span>
            </div>

            <div x-show="failed" x-cloak><x-ui.error-state tone="neutral" icon="cloud-off" title="Not loaded" messageExpr="'Could not load missing persons. ' + offlineWhy" retryExpr="load(page)" :retryable="true" /></div>
            <div x-show="!loading && !failed && rows.length === 0" x-cloak class="sk-empty">No cases match these filters.</div>

            <div x-show="rows.length" class="sk-scroll" tabindex="0" role="region" aria-label="Missing persons cases table (scrolls sideways)">
                <table class="sk-tbl sk-tbl-act">
                    <thead>
                        <tr>
                            <th scope="col">Case</th>
                            <th scope="col">Person</th>
                            <th scope="col">Status</th>
                            <th scope="col">Barangay</th>
                            <th scope="col">Last seen</th>
                            <th scope="col">Incident</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in rows" :key="r.ref">
                            <tr @click="show(r.ref)" @keydown.enter.prevent="show(r.ref)" tabindex="0">
                                <td><span class="sk-mono" x-text="r.ref"></span></td>
                                <td><span class="sk-b" x-text="r.name || 'Unidentified'"></span> <span class="sk-tag ml-1" x-text="kinds[r.kind]"></span></td>
                                <td><span class="sk-pill" :class="statusTone[r.status]" x-text="r.status"></span></td>
                                <td x-text="r.barangay || '—'"></td>
                                <td x-text="(r.last_seen_place || '—') + (r.last_seen_at ? ' · ' + when(r.last_seen_at) : '')"></td>
                                <td><span class="sk-mono" x-text="r.incident ? r.incident.ref : '—'"></span> <span class="sk-muted" x-show="r.incident" x-text="r.incident ? '· ' + r.incident.type : ''"></span></td>
                                {{-- An action column like the other Sakuna tables (critique 2026-11-03). --}}
                                <td class="whitespace-nowrap" @click.stop>
                                    <button type="button" @click="show(r.ref)" class="sk-btn sk-btn-sm sk-btn-soft" :aria-label="'View ' + r.ref"><i data-lucide="eye"></i> View</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="lastPage > 1" class="sk-tfoot flex items-center justify-end gap-2">
                <button type="button" :disabled="page <= 1" @click="load(page - 1)" class="sk-btn sk-btn-sm">Previous</button>
                <span x-text="'Page ' + page + ' of ' + lastPage + ' · ' + total + ' cases'"></span>
                <button type="button" :disabled="page >= lastPage" @click="load(page + 1)" class="sk-btn sk-btn-sm">Next</button>
            </div>
        </section>

        {{-- One case --}}
        <section x-show="open" x-cloak class="sk-card overflow-hidden" aria-label="Case details">
            <div class="sk-chead">
                <div>
                    <p class="sk-csub" x-text="open ? open.ref + ' · ' + kinds[open.kind] : ''"></p>
                    <div class="flex flex-wrap items-center gap-2 mt-0.5">
                        <h2 class="sk-ctitle" style="font-size: 15px;" x-text="open ? (open.name || 'Unidentified') : ''"></h2>
                        <span class="sk-pill" :class="open ? statusTone[open.status] : ''" x-text="open ? open.status : ''"></span>
                    </div>
                </div>
                <button type="button" @click="open = null" class="sk-btn sk-btn-sm">Close</button>
            </div>
            <div class="p-3.5 space-y-3">
                <dl class="grid gap-3 sm:grid-cols-2 text-[12.5px]" x-show="open">
                    <div><dt class="sk-ssub">Incident</dt><dd x-text="open?.incident ? open.incident.ref + ' · ' + open.incident.title : '—'"></dd></div>
                    <div><dt class="sk-ssub">Registry record</dt><dd x-text="open?.registry_ref || 'Not in the registry'"></dd></div>
                    <div><dt class="sk-ssub">Sex and age</dt><dd x-text="[open?.sex, open?.age !== null && open?.age !== undefined ? open.age + ' years' : null].filter(Boolean).join(', ') || '—'"></dd></div>
                    <div><dt class="sk-ssub">Barangay</dt><dd x-text="open?.barangay || '—'"></dd></div>
                    <div><dt class="sk-ssub">Last seen</dt><dd x-text="(open?.last_seen_place || '—') + (open?.last_seen_at ? ' · ' + when(open.last_seen_at) : '')"></dd></div>
                    <div><dt class="sk-ssub">Found</dt><dd x-text="(open?.found_place || '—') + (open?.found_at ? ' · ' + when(open.found_at) : '')"></dd></div>
                    <div class="sm:col-span-2"><dt class="sk-ssub">Identifying description (internal)</dt><dd x-text="open?.description || '—'"></dd></div>
                </dl>

                <div x-show="open && open.next_statuses.length && can('sakuna_missing','edit')" class="rounded-lg bg-[#F8FAFD] border border-[#EEF2F7] p-3 space-y-2">
                    <p class="text-xs font-semibold text-slate-800">Update the case</p>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="s in (open ? open.next_statuses : [])" :key="s">
                            <button type="button" @click="move.status = s" :aria-pressed="move.status === s"
                                    x-show="s !== 'Closed' || can('sakuna_missing','close')"
                                    class="sk-btn sk-btn-sm" :class="move.status === s ? 'sk-btn-soft' : ''" x-text="s"></button>
                        </template>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2" x-show="move.status">
                        <input type="text" x-show="move.status.startsWith('Found')" x-model="move.found_place" placeholder="Where found" aria-label="Where found" class="sk-sel w-full">
                        <input type="text" x-model="move.note" placeholder="Note for the history" aria-label="Note for the history" class="sk-sel w-full">
                    </div>
                    <button type="button" class="sk-btn sk-btn-sm sk-btn-p" x-show="move.status" :disabled="saving" @click="moveTo()"><i data-lucide="check"></i><span x-text="saving ? 'Saving…' : 'Mark as ' + move.status"></span></button>
                </div>

                <div class="rounded-lg border border-[#E3E8F1] p-3 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-800">Balita posts</p>
                        <button type="button" class="sk-btn sk-btn-sm" x-show="open && open.status !== 'Closed' && can('sakuna_missing','edit') && can('comm_news','create')" :disabled="saving" @click="draftPost()"><i data-lucide="newspaper"></i> Prepare a Balita post</button>
                    </div>
                    <p class="text-xs sk-muted">Nothing is posted by itself. A draft holds only what may be made public (no description, address or contact details) and needs approval by the Information Officer, the Super Administrator or the MDRRMO Head.</p>
                    <ul class="space-y-1 text-xs">
                        <template x-for="a in (open ? (open.announcements || []) : [])" :key="a.id">
                            <li class="flex flex-wrap gap-x-2">
                                <a :href="'{{ route('admin.communications') }}?tab=balita&post=' + a.id" class="sk-link" style="font-size: 12px;" x-text="a.title"></a>
                                <span class="sk-muted" x-text="a.status"></span>
                                <span class="sk-muted" x-show="a.published" x-text="a.published ? 'published ' + when(a.published.released_at) + (a.published.released_by ? ' by ' + a.published.released_by : '') : ''"></span>
                            </li>
                        </template>
                    </ul>
                    <p class="text-xs sk-muted" x-show="open && !(open.announcements || []).length">No post has been prepared from this case.</p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-800 mb-1.5">History</p>
                    <ol class="text-xs">
                        <template x-for="(h, i) in (open ? open.history : [])" :key="i">
                            <li class="flex flex-wrap gap-x-2 py-1.5 border-b border-[#EEF2F7] last:border-b-0"><span class="sk-muted" x-text="when(h.at)"></span><span class="font-semibold" x-text="h.to ? (h.from ? h.from + ' → ' + h.to : h.to) : 'Details updated'"></span><span class="sk-muted" x-text="h.by ? 'by ' + h.by : ''"></span><span x-text="h.note || ''"></span></li>
                        </template>
                    </ol>
                </div>
            </div>
        </section>

        {{-- Record a person --}}
        <section x-show="adding" x-cloak class="sk-card overflow-hidden" aria-label="Record a person">
            <div class="sk-chead"><h2 class="sk-ctitle">Record a person</h2><button type="button" @click="adding = false" class="sk-btn sk-btn-sm">Cancel</button></div>
            <div class="p-3.5 space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="text-xs text-[#4B5568] flex flex-col gap-1">Incident *
                        <select x-model="form.incident" class="sk-sel w-full">
                            <option value="">Choose the incident</option>
                            <template x-for="i in incidents" :key="i.ref"><option :value="i.ref" x-text="i.ref + ' · ' + i.type + ' · ' + i.barangay"></option></template>
                        </select>
                    </label>
                    <label class="text-xs text-[#4B5568] flex flex-col gap-1">Kind *
                        <select x-model="form.kind" class="sk-sel w-full">
                            <option value="missing">Missing person</option><option value="deceased">Deceased person</option><option value="unidentified">Unidentified body</option>
                        </select>
                    </label>
                    <div class="sm:col-span-2" x-show="form.kind !== 'unidentified' && can('const_resident','view')">
                        <label class="text-xs text-[#4B5568]" for="mp-resident">Find the resident in the registry (optional)</label>
                        <input id="mp-resident" type="search" x-model="residentQuery" @input.debounce.400ms="findResident()" placeholder="Name or RES number" class="sk-sel w-full mt-1">
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <template x-for="p in residents" :key="p.ref">
                                <button type="button" @click="form.individual_ref = p.ref; form.name = ''; residents = []; residentQuery = p.name + ' (' + p.ref + ')'" class="sk-btn sk-btn-sm" x-text="p.name + ' · ' + p.barangay"></button>
                            </template>
                        </div>
                        <p class="text-xs sk-muted mt-1" x-show="form.individual_ref">The name, sex and barangay come from the registry record <span x-text="form.individual_ref"></span>.</p>
                    </div>
                    <label class="text-xs text-[#4B5568] flex flex-col gap-1" x-show="!form.individual_ref">Name
                        <input type="text" x-model="form.name" maxlength="160" class="sk-sel w-full">
                    </label>
                    <label class="text-xs text-[#4B5568] flex flex-col gap-1">Sex
                        <select x-model="form.sex" class="sk-sel w-full"><option value="">—</option><option>Male</option><option>Female</option></select>
                    </label>
                    <label class="text-xs text-[#4B5568] flex flex-col gap-1">Approximate age
                        <input type="number" min="0" max="130" x-model="form.age" class="sk-sel w-full">
                    </label>
                    <label class="text-xs text-[#4B5568] flex flex-col gap-1">Last seen at
                        <input type="text" x-model="form.last_seen_place" maxlength="255" class="sk-sel w-full">
                    </label>
                    <label class="text-xs text-[#4B5568] flex flex-col gap-1">Last seen on
                        <input type="datetime-local" x-model="form.last_seen_at" class="sk-sel w-full">
                    </label>
                    <label class="text-xs text-[#4B5568] flex flex-col gap-1 sm:col-span-2">Identifying description (internal, not for publication)
                        <textarea x-model="form.description" rows="2" maxlength="2000" class="w-full text-[12.5px] border border-[#E3E8F1] rounded-lg px-2 py-1.5"></textarea>
                    </label>
                </div>
                <button type="button" class="sk-btn sk-btn-p" :disabled="saving || !form.incident" @click="record()"><i data-lucide="check"></i><span x-text="saving ? 'Saving…' : 'Record'"></span></button>
            </div>
        </section>
    </div>
</div>
