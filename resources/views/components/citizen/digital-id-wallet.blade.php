{{-- The Unified Digital ID Wallet (owner decisions, 2026-10-13), from
     GET /citizen/wallet (esperanza-backend App\Domain\Identity\CitizenWallet),
     the same view the mobile app's Digital ID screen shows:

     - the Resident ID the LGU issues (x-citizen.digital-id-card), only while
       it is active; plainly suspended or not issued otherwise, with no QR;
     - where things stand: identity, residency and the Resident ID, each with
       its own status, never merged;
     - My IDs: government IDs the resident keeps for reference in Papeles,
       "Uploaded - Not checked" unless the LGU verified that very upload (it then
       carries the Esperanza seal) or returned it. No
       identity check ran on them and no issuing agency is connected, and the
       page says so.

     Every status comes from the server. Adding sends the type, the photos and
     (for Other) a name and issuer; nothing here sends a status. Removing is
     the Papeles archive: kept, never deleted. --}}
<div
    x-data="{
        wallet: null,
        loading: true,
        failed: false,
        view: 'wallet',
        doc: null,
        form: { kind: '', name: '', issuer: '', expires: '', replaces: null, replacingName: '' },
        front: null,
        back: null,
        frontPreview: '',
        backPreview: '',
        sending: false,
        removing: false,
        formError: '',
        tried: false,
        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        // Each kind of ID as the wallet draws it (owner, 2026-10-13), the same
        // as the app's lib/widgets/wallet_id_card.dart: its colours, icon, the
        // name its number goes by, and the fields on its front and back. Not
        // the agency's design: no seal, logo or layout of the real card.
        designs: {
            philippine_national_id: { band: 'from-navy-700 to-brand-600', icon: 'fingerprint', number: 'PCN', front: ['birthdate', 'sex'], back: ['address', 'blood_type', 'civil_status', 'place_of_birth'] },
            drivers_license: { band: 'from-blue-700 to-sky-700', icon: 'car', number: 'License no.', front: ['birthdate', 'sex', 'expires_on'], back: ['address', 'blood_type', 'restrictions', 'conditions'] },
            passport: { band: 'from-navy-900 to-indigo-700', icon: 'plane', number: 'Passport no.', front: ['birthdate', 'sex', 'expires_on'], back: ['nationality', 'place_of_birth'] },
            umid: { band: 'from-teal-700 to-cyan-700', icon: 'credit-card', number: 'CRN', front: ['birthdate', 'sex'], back: ['address'] },
            sss: { band: 'from-blue-700 to-brand-500', icon: 'shield', number: 'SS no.', front: ['birthdate'], back: ['address'] },
            gsis: { band: 'from-emerald-700 to-teal-500', icon: 'landmark', number: 'BP no.', front: ['birthdate'], back: ['address'] },
            philhealth: { band: 'from-green-700 to-emerald-500', icon: 'heart-pulse', number: 'PIN', front: ['birthdate', 'sex'], back: ['address'] },
            owwa: { band: 'from-sky-700 to-blue-500', icon: 'globe', number: 'OWWA no.', front: ['birthdate'], back: ['destination_country', 'address'] },
            prc: { band: 'from-indigo-700 to-purple-500', icon: 'award', number: 'Reg. no.', front: ['profession', 'expires_on'], back: ['registered_on', 'birthdate'] },
            postal: { band: 'from-orange-700 to-amber-500', icon: 'mail', number: 'PRN', front: ['birthdate', 'expires_on'], back: ['address', 'nationality'] },
            voters: { band: 'from-purple-700 to-indigo-500', icon: 'vote', number: 'VIN', front: ['birthdate'], back: ['address', 'precinct'] },
            tin: { band: 'from-slate-700 to-slate-500', icon: 'receipt', number: 'TIN', front: ['birthdate'], back: ['address'] },
            other: { band: 'from-navy-800 to-slate-600', icon: 'id-card', number: 'ID no.', front: ['birthdate', 'expires_on'], back: ['address'] },
        },
        extraLabels: {
            blood_type: ['Blood type', 'Uri ng dugo'], civil_status: ['Civil status', 'Katayuang sibil'], place_of_birth: ['Place of birth', 'Lugar ng kapanganakan'],
            restrictions: ['Restrictions', 'Mga restriksyon'], conditions: ['Conditions', 'Mga kondisyon'], nationality: ['Nationality', 'Nasyonalidad'],
            destination_country: ['Country of work', 'Bansang pinagtatrabahuhan'], profession: ['Profession', 'Propesyon'], registered_on: ['Registered', 'Nairehistro'], precinct: ['Precinct', 'Presinto'],
        },
        design(kind) { return this.designs[kind] || this.designs.other; },
        // The face on the front photo (found on the phone), as the card's
        // picture: the photo sized and placed so only that box shows.
        portraitStyle(d) {
            const b = String(d.portrait_box || '').split(',').map(Number);
            if (b.length !== 4 || b.some((v) => !(v >= 0 && v <= 1)) || !b[2] || !b[3] || !d.front_url) return '';
            const pos = (start, size) => (size >= 1 ? 0 : (start / (1 - size)) * 100);
            return 'background-image: url(' + JSON.stringify(d.front_url) + '); background-size: ' + (100 / b[2]) + '% ' + (100 / b[3]) + '%; background-position: ' + pos(b[0], b[2]) + '% ' + pos(b[1], b[3]) + '%;';
        },
        fieldLabel(key) {
            const base = { birthdate: ['Date of birth', 'Petsa ng kapanganakan'], sex: ['Sex', 'Kasarian'], address: ['Address', 'Tirahan'], expires_on: ['Valid until', 'Valid hanggang'] };
            const l = base[key] || this.extraLabels[key];
            return l ? this.t(l[0], l[1]) : key;
        },
        fieldValue(key, d) {
            if (key === 'birthdate') return d.birthdate ? this.date(d.birthdate) : '';
            if (key === 'sex') return d.sex ? (d.sex === 'Male' ? this.t('Male', 'Lalaki') : this.t('Female', 'Babae')) : '';
            if (key === 'address') return d.address || '';
            if (key === 'expires_on') return d.expires_on ? this.date(d.expires_on) : '';
            const v = (d.details || {})[key] || '';
            return key === 'registered_on' && v ? this.date(v) : v;
        },
        sideFields(d, side) { return this.design(d.id_kind)[side].map((k) => [this.fieldLabel(k), this.fieldValue(k, d)]).filter((f) => f[1]); },
        extrasFor(kind) { const ds = this.design(kind); return [...ds.front, ...ds.back].filter((k) => this.extraLabels[k]); },
        usesSex(kind) { const ds = this.design(kind); return [...ds.front, ...ds.back].includes('sex'); },
        details: { id_number: '', holder_name: '', birthdate: '', sex: '', address: '', extras: {} },
        editing: false,
        savingDetails: false,
        fillDetails(d) {
            this.details = {
                id_number: d?.id_number || '', holder_name: d?.holder_name || '', birthdate: d?.birthdate || '',
                sex: d?.sex || '', address: d?.address || '', extras: { ...((d && d.details) || {}) },
            };
        },
        detailsPayload() {
            const extras = {};
            for (const k of this.extrasFor(this.form.kind)) {
                const v = (this.details.extras[k] || '').trim();
                if (v) extras[k] = v;
            }
            return {
                id_number: this.details.id_number.trim() || null, holder_name: this.details.holder_name.trim() || null,
                birthdate: this.details.birthdate || null, sex: this.details.sex || null, address: this.details.address.trim() || null, details: extras,
            };
        },
        startEdit(doc) {
            this.form = { ...this.form, kind: doc.id_kind, expires: doc.expires_on || '' };
            this.fillDetails(doc);
            this.formError = '';
            this.editing = true;
        },
        async saveDetails() {
            if (this.savingDetails || !this.doc) return;
            this.savingDetails = true;
            this.formError = '';
            try {
                const res = await $api.patch('/citizen/wallet/ids/' + encodeURIComponent(this.doc.id), { ...this.detailsPayload(), expires_on: this.form.expires || null });
                this.doc = res.data;
                this.editing = false;
                this.$dispatch('toast', { message: this.t('Details saved.', 'Na-save ang mga detalye.'), variant: 'success' });
                await this.load();
            } catch (err) {
                this.formError = err?.localised ? err.localised(this.$store.lang.current) : this.t('Could not save the details.', 'Hindi ma-save ang mga detalye.');
            } finally {
                this.savingDetails = false;
            }
        },
        get card() {
            return this.wallet?.resident_id?.state === 'active' ? this.wallet.resident_id.card : null;
        },
        get kind() { return (this.wallet?.id_types || []).find((k) => k.key === this.form.kind) || null; },
        get isOther() { return this.form.kind === 'other'; },
        async init() { await this.load(); },
        async load() {
            this.loading = true;
            this.failed = false;
            try {
                this.wallet = (await $api.get('/citizen/wallet')).data;
            } catch {
                this.failed = true;
            } finally {
                this.loading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },
        identityPill(s) {
            return {
                'Verified': 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'Failed': 'bg-rose-50 text-rose-700 ring-rose-200',
                'Revoked': 'bg-rose-50 text-rose-700 ring-rose-200',
                'Requires Review': 'bg-amber-50 text-amber-700 ring-amber-200',
                'In Progress': 'bg-sky-50 text-sky-700 ring-sky-200',
            }[s] || 'bg-slate-100 text-slate-600 ring-slate-200';
        },
        sourceText(s) {
            return {
                didit: this.t('Checked by the automated ID and selfie check', 'Sinuri ng awtomatikong pagsusuri ng ID at selfie'),
                lgu_officer: this.t('Decided by LGU staff', 'Pinagpasyahan ng kawani ng LGU'),
                lgu_before_checks: this.t('Accepted by LGU staff before automated checks', 'Tinanggap ng kawani ng LGU bago ang awtomatikong pagsusuri'),
                test_check: this.t('Checked by a test check (not a live one)', 'Sinuri ng pansubok na pagsusuri (hindi totoo)'),
                pending: this.t('Waiting for the check', 'Hinihintay ang pagsusuri'),
            }[s] || '';
        },
        docStatus(s) {
            return {
                verified_by_lgu: [this.t('Verified by the LGU', 'Beripikado ng LGU'), 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
                returned_by_lgu: [this.t('Returned by the LGU', 'Ibinalik ng LGU'), 'bg-rose-50 text-rose-700 ring-rose-200'],
            }[s] || [this.t('Uploaded — Not checked', 'Na-upload — Hindi nasuri'), 'bg-slate-100 text-slate-700 ring-slate-200'];
        },
        date(d) { return d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : ''; },
        open(doc) { this.doc = doc; this.editing = false; this.view = 'doc'; this.$nextTick(() => window.renderIcons?.()); },
        startAdd(replacing = null) {
            this.form = { kind: replacing ? replacing.id_kind : '', name: replacing?.id_kind === 'other' ? replacing.name : '', issuer: replacing?.id_kind === 'other' ? (replacing.issuer || '') : '', expires: replacing?.expires_on || '', replaces: replacing?.id || null, replacingName: replacing?.name || '' };
            this.clear('front');
            this.clear('back');
            this.fillDetails(replacing);
            this.formError = '';
            this.tried = false;
            this.view = 'add';
            this.$nextTick(() => window.renderIcons?.());
        },
        pick(side, file) {
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp', 'application/pdf'].includes(file.type)) {
                this.formError = this.t('Choose a JPG, PNG, WebP or PDF file.', 'Pumili ng JPG, PNG, WebP o PDF na file.');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                this.formError = this.t('That file is larger than 10 MB. Please choose a smaller one.', 'Lampas sa 10 MB ang file. Pumili ng mas maliit.');
                return;
            }
            this.clear(side);
            this.formError = '';
            this[side] = file;
            this[side + 'Preview'] = file.type.startsWith('image/') ? URL.createObjectURL(file) : '';
        },
        clear(side) {
            if (this[side + 'Preview']) URL.revokeObjectURL(this[side + 'Preview']);
            this[side] = null;
            this[side + 'Preview'] = '';
        },
        get problem() {
            if (!this.form.kind) return this.t('Choose the type of ID.', 'Piliin ang uri ng ID.');
            if (this.isOther && !this.form.name.trim()) return this.t('Enter the name of the ID.', 'Ilagay ang pangalan ng ID.');
            if (this.isOther && !this.form.issuer.trim()) return this.t('Enter who issued the ID.', 'Ilagay kung sino ang nag-isyu ng ID.');
            if (!this.front) return this.t('Add a photo of the front of the ID.', 'Magdagdag ng litrato ng harap ng ID.');
            return '';
        },
        async submit() {
            this.tried = true;
            if (this.problem) { this.formError = this.problem; return; }
            const body = new FormData();
            body.append('id_kind', this.form.kind);
            if (this.isOther) {
                body.append('name', this.form.name.trim());
                body.append('issuer', this.form.issuer.trim());
            }
            if (this.form.expires) body.append('expires_on', this.form.expires);
            if (this.form.replaces) body.append('replaces', String(this.form.replaces));
            body.append('front', this.front);
            if (this.back) body.append('back', this.back);
            // What the ID says, for its card.
            const said = this.detailsPayload();
            for (const k of ['id_number', 'holder_name', 'birthdate', 'sex', 'address']) {
                if (said[k]) body.append(k, said[k]);
            }
            for (const [k, v] of Object.entries(said.details)) body.append('details[' + k + ']', v);
            this.sending = true;
            this.formError = '';
            try {
                await $api.post('/citizen/wallet/ids', body);
                this.$dispatch('toast', { message: this.t('ID added to your wallet. It is kept as uploaded and is not checked.', 'Naidagdag ang ID sa iyong wallet. Itinatago ito bilang na-upload at hindi nasuri.'), variant: 'success' });
                this.clear('front');
                this.clear('back');
                this.view = 'wallet';
                await this.load();
            } catch (err) {
                this.formError = err?.localised ? err.localised(this.$store.lang.current) : this.t('Could not add the ID.', 'Hindi maidagdag ang ID.');
            } finally {
                this.sending = false;
            }
        },
        async remove(doc) {
            if (this.removing) return;
            if (!window.confirm(this.t('Remove this ID from your wallet? The LGU keeps its record of the upload.', 'Alisin ang ID na ito sa iyong wallet? Itatago ng LGU ang talaan ng upload.'))) return;
            this.removing = true;
            try {
                await $api.post('/citizen/papeles/' + encodeURIComponent(doc.id) + '/archive');
                this.view = 'wallet';
                this.doc = null;
                await this.load();
            } catch (err) {
                this.$dispatch('toast', { message: err?.localised ? err.localised(this.$store.lang.current) : this.t('Could not remove the ID.', 'Hindi maalis ang ID.'), variant: 'error' });
            } finally {
                this.removing = false;
            }
        },
    }"
    class="space-y-4"
>
    <div x-show="loading" class="space-y-3">
        <x-ui.skeleton class="aspect-[1.58/1] rounded-2xl" />
    </div>

    <div x-show="failed" x-cloak>
        <x-ui.error-state messageExpr="($store.lang.current === 'en' ? 'Could not load your Digital ID.' : 'Hindi ma-load ang iyong Digital ID.')" retryExpr="load()" :retryable="true" />
    </div>

    {{-- ── The wallet ─────────────────────────────────────────────────── --}}
    <template x-if="!loading && !failed && wallet && view === 'wallet'">
        <div class="space-y-4">
            {{-- The Resident ID --}}
            <template x-if="card">
                <x-citizen.digital-id-card />
            </template>
            <div x-show="!card" class="rounded-2xl border p-4 text-center"
                 :class="wallet.resident_id.state === 'suspended' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50'">
                <i :data-lucide="wallet.resident_id.state === 'suspended' ? 'ban' : 'hourglass'" class="w-6 h-6 mx-auto" :class="wallet.resident_id.state === 'suspended' ? 'text-rose-700' : 'text-amber-700'"></i>
                <p class="text-sm font-semibold text-ink mt-2" x-text="wallet.resident_id.state === 'suspended' ? t('Resident ID suspended', 'Suspendido ang Resident ID') : t('Resident ID not issued yet', 'Hindi pa naiisyu ang Resident ID')"></p>
                <p class="text-xs text-slate-600 mt-1" x-show="wallet.resident_id.state === 'suspended'" x-text="wallet.resident_id.suspended_because === 'identity_revoked'
                    ? t('The LGU withdrew the verification of your identity, so your Resident ID is not valid and its QR code no longer verifies. Send a new valid ID and selfie, or visit your barangay office.', 'Binawi ng LGU ang beripikasyon ng iyong pagkakakilanlan, kaya hindi valid ang iyong Resident ID at hindi na gumagana ang QR code nito. Magpadala ng bagong valid ID at selfie, o bumisita sa inyong barangay.')
                    : t('The LGU withdrew the approval of your residency, so your Resident ID is not valid and its QR code no longer verifies. Please visit your barangay office.', 'Binawi ng LGU ang pag-apruba ng iyong paninirahan, kaya hindi valid ang iyong Resident ID at hindi na gumagana ang QR code nito. Mangyaring bumisita sa inyong barangay.')"></p>
                <p class="text-xs text-slate-600 mt-1" x-show="wallet.resident_id.state !== 'suspended'" x-text="wallet.identity.status === 'Verified'
                    ? t('Your identity is verified. Your Resident ID is issued once the LGU approves your residency.', 'Beripikado ang iyong pagkakakilanlan. Iisyu ang iyong Resident ID kapag inaprubahan ng LGU ang iyong paninirahan.')
                    : t('Your Resident ID is issued once your identity is verified and the LGU approves your residency.', 'Iisyu ang iyong Resident ID kapag beripikado na ang iyong pagkakakilanlan at inaprubahan ng LGU ang iyong paninirahan.')"></p>
            </div>

            {{-- Where things stand --}}
            <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
                <p class="text-sm font-semibold text-ink"><x-ui.t fil="Kalagayan" en="Where things stand" /></p>
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-ink"><x-ui.t fil="Pagkakakilanlan" en="Identity" /></p>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1" :class="identityPill(wallet.identity.status)" x-text="wallet.identity.status || t('Not started', 'Hindi pa nasisimulan')"></span>
                    </div>
                    <p class="text-[11px] text-ink-muted mt-1" x-show="wallet.identity.source" x-text="sourceText(wallet.identity.source)"></p>
                    <p class="text-[11px] text-ink-muted" x-show="wallet.identity.checked_document" x-text="t('Checked ID: ', 'Sinuring ID: ') + (wallet.identity.checked_document?.type || 'ID') + (wallet.identity.checked_document?.number_last4 ? t(', ending ', ', nagtatapos sa ') + wallet.identity.checked_document.number_last4 : '')"></p>
                </div>
                <div class="border-t border-slate-100 pt-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-ink"><x-ui.t fil="Paninirahan" en="Residency" /></p>
                        <x-ui.badge status-bind="wallet.residency.status" />
                    </div>
                    <p class="text-[11px] mt-1" :class="wallet.residency.approval_revoked ? 'text-rose-700' : 'text-ink-muted'" x-text="wallet.residency.approval_revoked ? t('The LGU withdrew the approval of your residency.', 'Binawi ng LGU ang pag-apruba ng iyong paninirahan.') : t('Decided by the LGU, separately from your identity.', 'Pinagpapasyahan ng LGU, hiwalay sa iyong pagkakakilanlan.')"></p>
                </div>
                <div class="border-t border-slate-100 pt-3 flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-ink">Resident ID</p>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1"
                          :class="{ active: 'bg-emerald-50 text-emerald-700 ring-emerald-200', suspended: 'bg-rose-50 text-rose-700 ring-rose-200' }[wallet.resident_id.state] || 'bg-amber-50 text-amber-700 ring-amber-200'"
                          x-text="{ active: t('Active', 'Aktibo'), suspended: t('Suspended', 'Suspendido') }[wallet.resident_id.state] || t('Not issued yet', 'Hindi pa naiisyu')"></span>
                </div>
            </div>

            {{-- My IDs --}}
            <div>
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-ink"><x-ui.t fil="Aking mga ID" en="My IDs" /></p>
                    <x-ui.button size="sm" variant="secondary" icon="plus" @click="startAdd()"><x-ui.t fil="Magdagdag ng ID" en="Add ID" /></x-ui.button>
                </div>
                <p class="text-[11px] text-ink-muted mt-1"><x-ui.t fil="Itago rito ang kopya ng iyong mga government ID para sa sanggunian. Hindi ito nabeberipika sa pagdagdag: hindi ito sinusuri ng LGU, ng pagsusuri ng pagkakakilanlan, o ng ahensyang nag-isyu nito. May tatak ng Esperanza ang mga ID na beripikado ng LGU." en="Keep copies of your government IDs here for reference. Adding an ID does not verify it: it is not checked by the LGU, by the identity check, or by the agency that issued it. IDs verified by the LGU have the Esperanza seal on them." /></p>
                <p x-show="!wallet.documents.length" class="text-xs text-ink-muted mt-3 rounded-xl border border-dashed border-slate-200 p-4 text-center"><x-ui.t fil="Wala pang naidagdag na ID." en="No IDs added yet." /></p>
                <div class="space-y-3 mt-3">
                    <template x-for="d in wallet.documents" :key="d.id">
                        {{-- The wallet's card for this kind of ID, from what it says. --}}
                        <button type="button" @click="open(d)" class="relative w-full text-left rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-card hover:shadow-card-hover transition-shadow flex flex-col">
                            <div class="bg-gradient-to-r text-white h-12 pl-3 pr-28 flex items-center gap-2" :class="design(d.id_kind).band">
                                <span class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0"><i :data-lucide="design(d.id_kind).icon" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-extrabold truncate" x-text="d.name"></p>
                                    <p class="text-[10px] uppercase tracking-wide text-white/80 truncate" x-text="d.issuer || ''"></p>
                                </div>
                            </div>
                            <div class="flex gap-3 p-3">
                                <div class="w-16 h-20 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center">
                                    <div x-show="portraitStyle(d)" role="img" :aria-label="t('Photo on the ID', 'Litrato sa ID')" class="w-full h-full bg-no-repeat" :style="portraitStyle(d)"></div>
                                    <img x-show="d.front_url && !portraitStyle(d)" :src="d.front_url" :alt="t('Your photo of the ID', 'Iyong litrato ng ID')" class="w-full h-full object-cover">
                                    <i x-show="!d.front_url" data-lucide="image" class="w-5 h-5 text-slate-400"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-600"><x-ui.t fil="Pangalan" en="Name" /></p>
                                    <p class="text-sm font-bold truncate" :class="d.holder_name ? 'text-ink' : 'text-ink-muted'" x-text="d.holder_name || t('Not entered', 'Wala pang nailagay')"></p>
                                    <p class="text-[10px] uppercase tracking-wide text-slate-600 mt-1" x-text="design(d.id_kind).number"></p>
                                    <p class="text-sm font-bold tabular-nums tracking-wide truncate" :class="d.id_number ? 'text-navy-800' : 'text-ink-muted'" x-text="d.id_number || t('Not entered', 'Wala pang nailagay')"></p>
                                    <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1">
                                        <template x-for="f in sideFields(d, 'front')" :key="f[0]">
                                            <div><p class="text-[10px] uppercase tracking-wide text-slate-600" x-text="f[0]"></p><p class="text-xs font-semibold text-ink" x-text="f[1]"></p></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <p class="h-6 px-3 flex items-center text-[10px] text-slate-600 bg-slate-50"><x-ui.t fil="Kopya sa wallet ng Esperanza · hindi opisyal na ID" en="Esperanza wallet copy · not an official ID" /></p>
                            {{-- Verified by the LGU: the Esperanza seal on it. --}}
                            <img x-show="d.document_status === 'verified_by_lgu'" x-cloak src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" :alt="t('Esperanza seal: verified by the LGU', 'Tatak ng Esperanza: beripikado ng LGU')" class="absolute top-2 right-2 w-12 h-12 rounded-full ring-2 ring-white shadow-card opacity-90">
                            <span x-show="d.document_status !== 'verified_by_lgu'" class="absolute top-3 right-3 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1" :class="docStatus(d.document_status)[1]" x-text="d.document_status === 'returned_by_lgu' ? t('Returned', 'Ibinalik') : t('Not checked', 'Hindi nasuri')"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </template>

    {{-- ── One kept ID ────────────────────────────────────────────────── --}}
    <template x-if="!loading && !failed && view === 'doc' && doc">
        <div class="space-y-4">
            <button type="button" @click="view = 'wallet'" class="text-xs font-medium text-brand-600 hover:underline inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> <x-ui.t fil="Bumalik" en="Back" /></button>
            <p class="text-sm font-semibold text-ink" x-text="doc.name"></p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <template x-for="side in [[t('Front', 'Harap'), doc.front_url, true], [t('Back', 'Likod'), doc.back_url, doc.has_back]]" :key="side[0]">
                    <div x-show="side[2]" class="rounded-xl border border-slate-200 bg-slate-50 p-2">
                        <p class="text-[11px] font-medium text-slate-600 mb-1.5" x-text="side[0]"></p>
                        <a x-show="side[1]" :href="side[1]" target="_blank" rel="noopener"><img :src="side[1]" :alt="doc.name + ' - ' + side[0]" class="w-full h-32 object-contain rounded-lg bg-white"></a>
                        <p x-show="!side[1]" class="text-xs text-ink-muted"><x-ui.t fil="Hindi maipakita ang larawan ngayon." en="This image cannot be shown right now." /></p>
                    </div>
                </template>
            </div>
            <dl class="rounded-2xl border border-slate-200 p-4 text-xs space-y-1.5">
                <div class="flex justify-between gap-2"><dt class="text-ink-muted"><x-ui.t fil="Nag-isyu" en="Issued by" /></dt><dd class="font-semibold text-ink text-right" x-text="doc.issuer || '—'"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-ink-muted"><x-ui.t fil="Pag-expire" en="Expires" /></dt><dd class="font-semibold text-ink text-right" x-text="doc.expires_on ? date(doc.expires_on) : t('No expiry date given', 'Walang petsa ng pag-expire')"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-ink-muted"><x-ui.t fil="Idinagdag" en="Added" /></dt><dd class="font-semibold text-ink text-right" x-text="date(doc.uploaded_at) || '—'"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-ink-muted" x-text="design(doc.id_kind).number"></dt><dd class="font-semibold text-ink text-right tabular-nums" x-text="doc.id_number || t('Not entered', 'Wala pang nailagay')"></dd></div>
                <div class="flex justify-between gap-2"><dt class="text-ink-muted"><x-ui.t fil="Pangalan" en="Name" /></dt><dd class="font-semibold text-ink text-right" x-text="doc.holder_name || t('Not entered', 'Wala pang nailagay')"></dd></div>
                <template x-for="f in [...sideFields(doc, 'front'), ...sideFields(doc, 'back')].filter((f, i, all) => all.findIndex((g) => g[0] === f[0]) === i)" :key="f[0]">
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted" x-text="f[0]"></dt><dd class="font-semibold text-ink text-right" x-text="f[1]"></dd></div>
                </template>
            </dl>
            <div x-show="!editing">
                <x-ui.button size="sm" variant="secondary" icon="pencil" @click="startEdit(doc)"><x-ui.t fil="I-edit ang mga detalye" en="Edit details" /></x-ui.button>
            </div>
            <div x-show="editing" x-cloak class="rounded-2xl border border-slate-200 p-4 space-y-4">
                <p class="text-sm font-semibold text-ink"><x-ui.t fil="Ano ang nakasulat sa ID" en="What the ID says" /></p>
                <p x-show="doc.document_status !== 'uploaded_not_checked'" class="text-[11px] text-amber-700"><x-ui.t fil="Ang pagbabago ay ibabalik ang ID na ito sa LGU para suriin." en="Saving a change sends this ID back to the LGU for review." /></p>
                <x-citizen.wallet-id-fields />
                <p x-show="formError" x-cloak class="text-xs text-rose-700" x-text="formError"></p>
                <div class="flex gap-2">
                    <x-ui.button size="sm" x-bind:disabled="savingDetails" @click="saveDetails()"><x-ui.t fil="I-save" en="Save" /></x-ui.button>
                    <x-ui.button size="sm" variant="secondary" @click="editing = false"><x-ui.t fil="Kanselahin" en="Cancel" /></x-ui.button>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4 space-y-3 text-xs">
                <p class="text-sm font-semibold text-ink"><x-ui.t fil="Mga pagsusuri sa ID na ito" en="Checks on this ID" /></p>
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold text-ink"><x-ui.t fil="Ang upload na ito" en="This upload" /></span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1" :class="docStatus(doc.document_status)[1]" x-text="docStatus(doc.document_status)[0]"></span>
                    </div>
                    <p x-show="doc.document_status === 'verified_by_lgu'" x-cloak class="text-[11px] text-ink-muted mt-1" x-text="t('LGU staff verified this ID', 'Beripikado ng kawani ng LGU ang ID na ito') + (doc.verified_at ? t(' on ', ' noong ') + date(doc.verified_at) : '') + t('. It carries the Esperanza seal in your wallet.', '. May tatak ito ng Esperanza sa iyong wallet.')"></p>
                    <div x-show="doc.document_status === 'returned_by_lgu'" x-cloak class="mt-1 space-y-0.5">
                        <p class="text-[11px] text-rose-700" x-text="t('Reason: ', 'Dahilan: ') + (doc.lgu_remarks || t('No reason given.', 'Walang ibinigay na dahilan.'))"></p>
                        <p class="text-[11px] text-ink-muted"><x-ui.t fil="Gamitin ang Palitan sa ibaba para magpadala ng mas malinaw na litrato." en="Use Replace below to send a clearer photo." /></p>
                    </div>
                </div>
                <div class="border-t border-slate-100 pt-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold text-ink"><x-ui.t fil="Pagsusuri ng pagkakakilanlan" en="Identity check" /></span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 bg-slate-100 text-slate-600 ring-slate-200"><x-ui.t fil="Hindi nasuri" en="Not checked" /></span>
                    </div>
                    <p class="text-[11px] text-ink-muted mt-1"><x-ui.t fil="Ang pagsusuri ng pagkakakilanlan ay ginawa sa ID na ipinadala mo para sa beripikasyon ng account, hindi sa upload na ito." en="The identity check ran on the ID you sent to verify your account, not on this upload." /></p>
                </div>
                <div class="border-t border-slate-100 pt-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold text-ink"><x-ui.t fil="Ahensyang nag-isyu" en="Issuing agency" /></span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 bg-slate-100 text-slate-600 ring-slate-200"><x-ui.t fil="Hindi available" en="Not available" /></span>
                    </div>
                    <p class="text-[11px] text-ink-muted mt-1"><x-ui.t fil="Hindi konektado ang Esperanza sa ahensyang nag-isyu ng ID na ito." en="Esperanza is not connected to the agency that issued this ID." /></p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-ui.button size="sm" variant="secondary" icon="refresh-cw" x-bind:disabled="removing" @click="startAdd(doc)"><x-ui.t fil="Palitan" en="Replace" /></x-ui.button>
                <button type="button" @click="remove(doc)" :disabled="removing" class="text-xs font-semibold rounded-xl px-3 py-2 bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-50"><x-ui.t fil="Alisin sa wallet" en="Remove from wallet" /></button>
            </div>
        </div>
    </template>

    {{-- ── Add or replace an ID ───────────────────────────────────────── --}}
    <template x-if="!loading && !failed && wallet && view === 'add'">
        <div class="space-y-4">
            <button type="button" @click="view = 'wallet'" class="text-xs font-medium text-brand-600 hover:underline inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> <x-ui.t fil="Bumalik" en="Back" /></button>
            <p class="text-sm font-semibold text-ink" x-text="form.replaces ? t('Replace ', 'Palitan ang ') + form.replacingName : t('Add an ID', 'Magdagdag ng ID')"></p>

            <label class="block">
                <span class="text-xs font-medium text-slate-700"><x-ui.t fil="Uri ng ID" en="Type of ID" /> <span class="text-rose-600">*</span></span>
                <select x-model="form.kind" :disabled="!!form.replaces" class="mt-1 w-full text-sm rounded-xl border border-slate-200 px-3 py-2 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none">
                    <option value="" x-text="t('Choose the type of ID', 'Piliin ang uri ng ID')"></option>
                    <template x-for="k in wallet.id_types" :key="k.key">
                        <option :value="k.key" x-text="k.key === 'other' ? t('Other', 'Iba pa') : k.name" :selected="k.key === form.kind"></option>
                    </template>
                </select>
            </label>
            <p x-show="kind && kind.issuer" class="text-[11px] text-ink-muted" x-text="t('Issued by ', 'Inisyu ng ') + (kind?.issuer || '')"></p>

            <div x-show="isOther" x-cloak class="space-y-3">
                <label class="block">
                    <span class="text-xs font-medium text-slate-700"><x-ui.t fil="Pangalan ng ID" en="Name of the ID" /> <span class="text-rose-600">*</span></span>
                    <input type="text" maxlength="80" x-model="form.name" :placeholder="t('e.g. Barangay Health Worker ID', 'hal. Barangay Health Worker ID')" class="mt-1 w-full text-sm rounded-xl border px-3 py-2 outline-none focus:ring-4 focus:ring-brand-100" :class="tried && !form.name.trim() ? 'border-rose-300' : 'border-slate-200'">
                </label>
                <label class="block">
                    <span class="text-xs font-medium text-slate-700"><x-ui.t fil="Nag-isyu" en="Issued by" /> <span class="text-rose-600">*</span></span>
                    <input type="text" maxlength="120" x-model="form.issuer" :placeholder="t('The office or organization that issued it', 'Ang opisina o organisasyong nag-isyu nito')" class="mt-1 w-full text-sm rounded-xl border px-3 py-2 outline-none focus:ring-4 focus:ring-brand-100" :class="tried && !form.issuer.trim() ? 'border-rose-300' : 'border-slate-200'">
                </label>
            </div>

            <template x-for="side in [['front', t('Front (required)', 'Harap (kailangan)')], ['back', t('Back (optional)', 'Likod (opsyonal)')]]" :key="side[0]">
                <div class="rounded-xl border p-3" :class="side[0] === 'front' && tried && !front ? 'border-rose-300 bg-rose-50/50' : 'border-slate-200'">
                    <p class="text-xs font-semibold text-ink mb-2" x-text="side[1]"></p>
                    <img x-show="$data[side[0] + 'Preview']" :src="$data[side[0] + 'Preview']" :alt="side[1]" class="w-full max-h-48 object-contain rounded-lg bg-slate-50 mb-2">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-600 flex-1 truncate" x-text="$data[side[0]] ? $data[side[0]].name : t('No file chosen', 'Walang napiling file')"></p>
                        <button type="button" x-show="$data[side[0]]" @click="clear(side[0])" class="text-[11px] font-medium text-rose-600 hover:underline shrink-0"><x-ui.t fil="Alisin" en="Remove" /></button>
                        <label class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap cursor-pointer">
                            <span x-text="$data[side[0]] ? t('Choose another', 'Pumili ng iba') : t('Choose File', 'Pumili ng File')"></span>
                            <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="sr-only" :aria-label="side[1]" @change="pick(side[0], $event.target.files[0] || null); $event.target.value = ''">
                        </label>
                    </div>
                </div>
            </template>

            <p class="text-[11px] text-ink-muted"><x-ui.t fil="Itatago ang iyong ID bilang na-upload at hindi ito susuriin. JPG, PNG, WebP o PDF, hanggang 10 MB bawat panig." en="Your ID is kept as uploaded and is not checked. JPG, PNG, WebP or PDF, up to 10 MB per side." /></p>
            <div x-show="form.kind" x-cloak class="space-y-3 pt-2">
                <p class="text-sm font-semibold text-ink"><x-ui.t fil="Ano ang nakasulat sa ID" en="What the ID says" /></p>
                <p class="text-[11px] text-ink-muted"><x-ui.t fil="Lalabas ito sa card ng iyong ID sa wallet. Ilagay gaya ng nakalimbag." en="These go on your ID's card in the wallet. Type them as printed." /></p>
                <x-citizen.wallet-id-fields />
            </div>
            <p x-show="formError" x-cloak class="text-xs text-rose-700" x-text="formError"></p>
            <x-ui.button size="sm" x-bind:disabled="sending" @click="submit()">
                <span class="inline-flex items-center gap-1.5">
                    <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="sending" x-cloak></i>
                    <span x-text="form.replaces ? t('Replace', 'Palitan') : t('Add to wallet', 'Idagdag sa wallet')"></span>
                </span>
            </x-ui.button>
        </div>
    </template>
</div>
