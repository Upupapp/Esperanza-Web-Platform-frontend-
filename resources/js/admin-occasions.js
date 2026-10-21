// Banners & Occasions (owner, 2026-10-21): the banners residents see in the
// Home carousel, and the Occasion & Pop-up manager - a birthday, a fiesta, a
// holiday, an LGU event - with its schedule, audience, pop-up, picture and
// the banner it may carry.
//
// The lists (types, triggers, sectors, frequencies, dismissals, placements,
// displays, picture rules) are config/esperanza_occasions.php, the same list
// the server checks. The server decides everything that matters: who is in an
// audience, whether an occasion is on, how often a pop-up shows. What is
// checked here only spares the officer a refused save. Previewing never
// publishes anything.
//
// Loaded with scope-admin-records.js (the Balita page's chunk).

import api from './api/client';

const toast = (message, variant = 'success') => window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
const errText = (err, fallback) => (err?.explain ? err.explain('en') : (err?.localised ? err.localised('en') : fallback));

/** A Y-m-d day at an HH:MM time in Esperanza, as the ISO the server takes; null without a day. */
const manilaIso = (ymd, hm) => (ymd ? String(ymd).slice(0, 10) + 'T' + (hm || '00:00') + ':00+08:00' : null);
/** An instant from the server, as the day and time it is in Esperanza. */
function manilaParts(iso) {
    const d = iso ? new Date(iso) : null;
    if (!d || Number.isNaN(d.getTime())) return { date: '', time: '' };
    const s = new Date(d.getTime() + 8 * 3600000).toISOString();
    return { date: s.slice(0, 10), time: s.slice(11, 16) };
}
const instant = (iso) => (iso ? new Date(iso).getTime() : NaN);
function when(iso, withTime = true) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    const opts = { timeZone: 'Asia/Manila', month: 'short', day: 'numeric', year: 'numeric' };
    return d.toLocaleString('en-US', withTime ? { ...opts, hour: 'numeric', minute: '2-digit' } : opts);
}
/** Two instants as the days they fall on in Esperanza, said once: "Oct 21–31, 2026". */
function dateRange(fromIso, toIso) {
    const part = (iso) => {
        const d = iso ? new Date(iso) : null;
        if (!d || Number.isNaN(d.getTime())) return null;
        const f = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', year: 'numeric', month: 'short', day: 'numeric' }).formatToParts(d);
        const get = (t) => f.find((x) => x.type === t)?.value;
        return { y: get('year'), m: get('month'), d: get('day') };
    };
    const a = part(fromIso);
    const b = part(toIso);
    if (!a && !b) return 'Any time';
    if (!b) return 'From ' + a.m + ' ' + a.d + ', ' + a.y;
    if (!a) return 'Until ' + b.m + ' ' + b.d + ', ' + b.y;
    if (a.y === b.y && a.m === b.m) return a.d === b.d ? a.m + ' ' + a.d + ', ' + a.y : a.m + ' ' + a.d + '–' + b.d + ', ' + a.y;
    if (a.y === b.y) return a.m + ' ' + a.d + ' – ' + b.m + ' ' + b.d + ', ' + a.y;
    return a.m + ' ' + a.d + ', ' + a.y + ' – ' + b.m + ' ' + b.d + ', ' + b.y;
}
const label = (list, key) => (list || []).find((x) => x.key === key)?.en || key || '';
const STATUS_WORDS = { Released: 'Published', Rejected: 'Sent back', 'Pending Review': 'Waiting for review' };

// The state chips. Scheduled, Active, Paused and Completed are worked out by
// the server from the dates and the pause switch: labels, not statuses.
const STATE_TONES = {
    draft: 'bg-slate-100 text-slate-700 ring-slate-200',
    scheduled: 'bg-sky-50 text-sky-800 ring-sky-200',
    active: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
    paused: 'bg-amber-50 text-amber-800 ring-amber-200',
    completed: 'bg-slate-50 text-slate-600 ring-slate-200',
    archived: 'bg-slate-50 text-slate-500 ring-slate-200',
};

/** The same audience in words the server gives back (Audience::summary), for a form not yet saved. */
function audienceWords(contract, barangays, sectors) {
    const places = (barangays || []).map((b) => 'Brgy. ' + b).join(', ');
    const groups = (sectors || []).map((s) => label(contract.audience_sectors, s)).join(', ');
    if (!places && !groups) return 'All residents of Esperanza';
    if (!places) return groups + ' in all barangays';
    if (!groups) return 'Residents of ' + places;
    return groups + ' in ' + places;
}

const imageRules = (contract, kind, maxBytes) => ({
    mime: contract.image?.mime,
    maxBytes: contract.image?.max_bytes || maxBytes,
    min: kind === 'banner' ? contract.image?.banner_min : null,
    recommended: kind === 'banner' ? contract.image?.banner_recommended : contract.image?.popup_recommended,
});
const noImage = (existing = null) => ({ id: existing?.id ?? null, url: existing?.url ?? null, busy: false, failed: false, changed: false });
// Closing a form without saving: what its picture field uploaded and never
// saved goes now, not a day later. The server refuses to discard a picture
// already attached to a record, so a saved one is never touched.
const discardPicture = (fieldId) => document.querySelector('[data-image-field="' + fieldId + '"]')?.dispatchEvent(new CustomEvent('image-field-discard'));

// ------------------------------------------------------------------ Banners

function bannerForm(b = null, nextOrder = 1) {
    return {
        id: b?.id ?? null,
        title: b?.title || '',
        subtitle: b?.subtitle || '',
        alt_text: b?.alt_text || '',
        cta_label: b?.cta_label || '',
        cta_route: b?.cta_route || 'citizen.dashboard',
        theme: b?.theme || 'from-brand-600 to-navy-800',
        placement: b?.placement || 'home',
        barangays: [...(b?.barangays || [])],
        sectors: [...(b?.sectors || [])],
        start_date: b?.start_date || '',
        end_date: b?.end_date || '',
        order: b?.order ?? nextOrder,
    };
}

/** The Banners view: the list, the form with its picture and its preview, and delete. */
function adminBanners(contract = {}, barangayNames = [], maxImageBytes = 10485760) {
    return {
        contract,
        barangayNames,
        loading: true,
        failed: false,
        banners: [],
        search: '',
        statusFilter: 'All',
        formOpen: false,
        mode: 'add',
        form: bannerForm(),
        image: noImage(),
        existingImage: null,
        snapshot: '',
        formKey: 1,
        previewDevice: 'desktop',
        processing: false,
        // Opened from an occasion's form ("Create a new banner"): the new
        // banner goes back to that form.
        returnTo: null,
        themes: { 'from-brand-600 to-navy-800': 'Brand', 'from-purple-600 to-navy-900': 'Purple', 'from-emerald-600 to-teal-800': 'Emerald', 'from-gold-500 to-orange-700': 'Gold', 'from-rose-600 to-navy-900': 'Rose', 'from-slate-700 to-navy-950': 'Slate' },
        ctaRoutes: { 'citizen.dashboard': 'Dashboard', 'citizen.document-requests': 'Dokyu', 'citizen.assistance-requests': 'Tulong', 'citizen.announcements': 'Balita', 'citizen.events': 'Events' },
        rules: imageRules(contract, 'banner', maxImageBytes),

        async load() {
            this.loading = true;
            this.failed = false;
            try {
                const res = await api.getAll('/admin/banners');
                this.banners = res.data || [];
            } catch {
                this.failed = true;
            } finally {
                this.loading = false;
            }
        },
        get filtered() {
            const q = this.search.trim().toLowerCase();
            return this.banners
                .filter((b) => (this.statusFilter === 'All' || b.status === this.statusFilter) && (!q || (b.title || '').toLowerCase().includes(q)))
                .slice().sort((a, c) => a.order - c.order || a.id - c.id);
        },
        statusLabel(s) { return STATUS_WORDS[s] || s; },
        can(action) { return this.$store.session.canPerm('comm_banners', action); },
        canMove(b, to) { const action = b.available_transitions?.[to]; return !!action && this.can(action); },
        editable(b) { return ['Draft', 'Pending Review', 'Rejected'].includes(b.status); },

        openAdd(returnTo = null) {
            this.mode = 'add';
            this.returnTo = returnTo;
            this.form = bannerForm(null, this.banners.reduce((n, b) => Math.max(n, b.order || 0), 0) + 1);
            this.existingImage = null;
            this.image = noImage();
            this.previewDevice = 'desktop';
            this.formKey += 1;
            this.formOpen = true;
            this.$nextTick(() => { this.snapshot = this.formState(); this.raise(); });
        },
        // Opened over an occasion's form: its window goes on top (dialogs
        // stack in the order they sit in the page).
        raise() {
            const layer = document.getElementById('banner-title')?.closest('[data-modal-layer]');
            if (layer && layer !== document.body.lastElementChild) document.body.appendChild(layer);
        },
        openEdit(b) {
            this.mode = 'edit';
            this.returnTo = null;
            this.form = bannerForm(b);
            this.existingImage = b.image || null;
            this.image = noImage(b.image);
            this.previewDevice = 'desktop';
            this.formKey += 1;
            this.formOpen = true;
            this.$nextTick(() => { this.snapshot = this.formState(); });
        },
        formState() { return JSON.stringify([this.form, this.image.id]); },
        // What stops a save is said once the officer has started, not on an empty form.
        get touched() { return this.snapshot !== '' && this.formState() !== this.snapshot; },
        requestClose() {
            if (this.processing) return;
            const dirty = this.snapshot !== '' && this.formState() !== this.snapshot;
            if ((this.image.busy || dirty) && !window.confirm(this.image.busy ? 'The picture is still uploading. Close and discard it?' : 'Discard the changes to this banner?')) return;
            discardPicture('banner-image');
            this.formOpen = false;
        },
        get problem() {
            if (!this.form.title.trim()) return 'Give the banner a title.';
            if (this.image.busy) return 'Wait for the picture to finish uploading.';
            if (this.image.failed) return 'The picture did not upload. Try it again or remove it.';
            if (this.image.id && !this.form.alt_text.trim()) return 'Describe the picture for residents who use a screen reader (alt text).';
            if (this.form.start_date && this.form.end_date && this.form.end_date < this.form.start_date) return 'The end date is before the start date.';
            return '';
        },
        audienceText(b) { return audienceWords(this.contract, b.barangays, b.sectors); },
        get formAudience() { return audienceWords(this.contract, this.form.barangays, this.form.sectors); },
        toggle(list, value) {
            const i = this.form[list].indexOf(value);
            if (i >= 0) this.form[list].splice(i, 1); else this.form[list].push(value);
        },
        async save() {
            if (this.processing || this.problem) return;
            this.processing = true;
            const body = {
                title: this.form.title.trim(), subtitle: this.form.subtitle.trim() || null, alt_text: this.form.alt_text.trim() || null,
                cta_label: this.form.cta_label.trim() || null, cta_route: this.form.cta_route || null, theme: this.form.theme || null,
                placement: this.form.placement, barangays: this.form.barangays, sectors: this.form.sectors,
                start_date: this.form.start_date || null, end_date: this.form.end_date || null, order: Number(this.form.order) || 0,
            };
            if (this.mode === 'add' || this.image.changed) body.media_id = this.image.id;
            try {
                const res = this.mode === 'add'
                    ? await api.post('/admin/banners', body)
                    : await api.put('/admin/banners/' + this.form.id, body);
                this.formOpen = false;
                toast(this.mode === 'add' ? 'Banner saved as a draft.' : 'Banner updated.');
                await this.load();
                if (this.returnTo) {
                    window.dispatchEvent(new CustomEvent('banner-created', { detail: { banner: res.data, returnTo: this.returnTo } }));
                    this.returnTo = null;
                }
            } catch (err) {
                toast(errText(err, 'Could not save the banner.'), 'error');
            } finally {
                this.processing = false;
            }
        },
        async transition(b, to) {
            if (to === 'Archived' && !window.confirm('Archive "' + b.title + '"? It leaves the carousel.')) return;
            try {
                await api.post('/admin/banners/' + b.id + '/transition', { to });
                await this.load();
                toast(to === 'Released' ? 'Published to the carousel while its dates allow.' : 'Banner is now ' + this.statusLabel(to) + '.');
            } catch (err) {
                toast(errText(err, 'Could not update this banner.'), 'error');
            }
        },
        // Draft, then reviewed, approved and published, as far as this
        // officer may: the same steps as one at a time.
        async publish(b) {
            const steps = ['Draft', 'Pending Review', 'Approved', 'Released'];
            let row = b;
            try {
                while (steps.indexOf(row.status) >= 0 && row.status !== 'Released') {
                    const next = steps[steps.indexOf(row.status) + 1];
                    if (!this.canMove(row, next)) break;
                    row = (await api.post('/admin/banners/' + b.id + '/transition', { to: next })).data;
                }
            } catch (err) {
                toast(errText(err, 'Could not publish this banner.'), 'error');
                await this.load();
                return;
            }
            await this.load();
            toast(row.status === 'Released' ? 'Published to the carousel while its dates allow.' : 'Banner is now ' + this.statusLabel(row.status) + '.');
        },
        async remove(b) {
            const used = (b.used_by || []).map((o) => o.name);
            if (used.length) {
                toast('"' + b.title + '" is used by ' + used.join(', ') + '. Remove it from those occasions first.', 'error');
                return;
            }
            if (!window.confirm('Delete the banner "' + b.title + '"? This cannot be undone.')) return;
            try {
                await api.delete('/admin/banners/' + b.id);
                toast('Banner deleted.');
                await this.load();
            } catch (err) {
                toast(errText(err, 'Could not delete this banner.'), 'error');
            }
        },
        async move(b, dir) {
            const list = this.filtered;
            const idx = list.findIndex((x) => x.id === b.id);
            const other = list[idx + dir];
            if (!other) return;
            try {
                await Promise.all([
                    api.put('/admin/banners/' + b.id, { order: other.order }),
                    api.put('/admin/banners/' + other.id, { order: b.order }),
                ]);
                await this.load();
            } catch (err) {
                toast(errText(err, 'Could not reorder.'), 'error');
            }
        },
        when,
    };
}

// ---------------------------------------------------------------- Occasions

function occasionForm(o = null) {
    const s = manilaParts(o?.starts_at);
    const e = manilaParts(o?.ends_at);
    const p = o?.popup || {};
    return {
        name: o?.name || '',
        description: o?.description || '',
        type: o?.type || 'barangay_fiesta',
        trigger: o?.trigger || 'window',
        start_date: s.date, start_time: s.time || '00:00',
        end_date: e.date, end_time: e.time || '23:59',
        audience: (o?.barangays?.length || o?.sectors?.length) ? 'some' : 'all',
        barangays: [...(o?.barangays || [])],
        sectors: [...(o?.sectors || [])],
        popup_title: p.title || '',
        popup_body: p.body || '',
        button: p.post_id ? 'post' : (p.button_url ? 'link' : 'none'),
        popup_button_label: p.button_label || '',
        popup_button_url: p.button_url || '',
        popup_post_id: p.post_id || '',
        popup_frequency: p.frequency || 'once',
        popup_dismissal: p.dismissal || 'seen',
        banner_mode: o?.banner ? 'existing' : 'none',
        banner_id: o?.banner?.id || '',
        banner_sync: !!o?.banner_sync,
        banner_display: o?.banner_display || 'carousel',
        // The pop-up shows its banner's picture (the officer's choice).
        picture_from_banner: !!p.image_from_banner,
    };
}

/** The Occasions & Pop-ups view. */
function adminOccasions(contract = {}, barangayNames = [], maxImageBytes = 10485760) {
    return {
        contract,
        barangayNames,
        loading: true,
        failed: false,
        occasions: [],
        counts: {},
        state: 'all',
        search: '',
        processing: false,
        formOpen: false,
        editing: null,
        form: occasionForm(),
        image: noImage(),
        existingImage: null,
        formKey: 1,
        snapshot: '',
        fieldErrors: {},
        barangayQuery: '',
        reach: null,
        reachTimer: null,
        banners: [],
        posts: [],
        previewOpen: false,
        previewOf: null,
        viewOpen: false,
        viewing: null,
        rules: imageRules(contract, 'popup', maxImageBytes),
        stateChips: [
            { key: 'all', label: 'All' }, { key: 'draft', label: 'Draft' }, { key: 'scheduled', label: 'Scheduled' },
            { key: 'active', label: 'Active' }, { key: 'paused', label: 'Paused' }, { key: 'completed', label: 'Completed' },
            { key: 'archived', label: 'Archived' },
        ],

        async load() {
            this.loading = true;
            this.failed = false;
            try {
                const res = await api.get('/admin/occasions', { params: { state: this.state, q: this.search.trim() || undefined, per_page: 100 } });
                this.occasions = res.data || [];
                this.counts = res.meta?.counts || {};
            } catch {
                this.failed = true;
            } finally {
                this.loading = false;
            }
        },
        setState(key) { this.state = key; this.load(); },
        chipCount(key) {
            if (key === 'all') return Object.entries(this.counts).filter(([k]) => k !== 'archived').reduce((n, [, v]) => n + v, 0);
            return this.counts[key] || 0;
        },
        can(action) { return this.$store.session.canPerm('comm_banners', action); },
        canMove(o, to) { const action = o.available_transitions?.[to]; return !!action && this.can(action); },
        stateTone(state) { return STATE_TONES[state] || STATE_TONES.draft; },
        statusLabel(s) { return STATUS_WORDS[s] || s; },
        typeLabel(key) { return label(this.contract.types, key); },
        triggerLabel(key) { return label(this.contract.triggers, key); },
        frequencyLabel(key) { return label(this.contract.popup_frequencies, key); },
        // The card's one line: "Oct 21–31, 2026", "Every year · Dec 24–26, 2026",
        // "On each resident's birthday". View shows the times.
        cardWhen(o) {
            if (o.trigger === 'birthday') return "On each resident's birthday";
            return (o.trigger === 'annual' ? 'Every year · ' : '') + dateRange(o.next_starts_at || o.starts_at, o.next_ends_at || o.ends_at);
        },
        windowText(o) {
            if (o.trigger === 'birthday') {
                return "On each resident's birthday" + (o.starts_at || o.ends_at ? ', ' + (o.starts_at ? when(o.starts_at, false) : 'now') + ' – ' + (o.ends_at ? when(o.ends_at, false) : 'no end') : '');
            }
            const from = o.next_starts_at || o.starts_at;
            const to = o.next_ends_at || o.ends_at;
            return (o.trigger === 'annual' ? 'Every year · ' : '') + when(from) + ' – ' + when(to);
        },

        // -- The form
        async openNew() {
            this.editing = null;
            this.form = occasionForm();
            this.existingImage = null;
            this.image = noImage();
            await this.openForm();
        },
        async openEdit(o) {
            this.editing = o;
            this.form = occasionForm(o);
            this.existingImage = o.popup?.image || null;
            this.image = noImage(o.popup?.image);
            this.viewOpen = false;
            await this.openForm();
        },
        async openForm() {
            this.fieldErrors = {};
            this.barangayQuery = '';
            this.formKey += 1;
            this.formOpen = true;
            this.$nextTick(() => { this.snapshot = this.formState(); });
            this.countReach();
            // What the form chooses from: the banners, and the Balita posts a
            // button may open.
            try {
                const [banners, posts] = await Promise.all([
                    api.getAll('/admin/banners'),
                    api.getAll('/admin/announcements', { params: { status: 'Released,Approved' } }).catch(() => ({ data: [] })),
                ]);
                this.banners = (banners.data || []).filter((b) => b.status !== 'Archived' || b.id === Number(this.form.banner_id));
                this.posts = posts.data || [];
                // The banner's picture was shared, and the banner has none now
                // (or another): the pop-up kept its own link to the file, so
                // it is the pop-up's own picture from here on.
                if (this.form.picture_from_banner && !this.bannerPicture) {
                    this.form.picture_from_banner = false;
                    this.snapshot = this.formState();
                }
            } catch {
                this.banners = [];
            }
        },
        formState() { return JSON.stringify([this.form, this.image.id]); },
        // What stops a save is said once the officer has started, not on an empty form.
        get touched() { return this.snapshot !== '' && this.formState() !== this.snapshot; },
        // The pop-up's picture: its own, or the chosen banner's (a link of its
        // own to the same file: removing either later leaves the other).
        get bannerPicture() { return this.form.banner_mode === 'existing' && this.chosenBanner?.image ? this.chosenBanner.image : null; },
        useBannerPicture(on) {
            this.form.picture_from_banner = on;
        },
        get sharesBannerPicture() { return this.form.picture_from_banner && !!this.bannerPicture; },
        requestClose() {
            if (this.processing) return;
            const dirty = this.snapshot !== '' && this.formState() !== this.snapshot;
            if ((this.image.busy || dirty) && !window.confirm(this.image.busy ? 'The picture is still uploading. Close and discard it?' : 'Discard the changes to this occasion?')) return;
            discardPicture('occ-image');
            this.formOpen = false;
        },
        toggle(list, value) {
            const i = this.form[list].indexOf(value);
            if (i >= 0) this.form[list].splice(i, 1); else this.form[list].push(value);
            this.countReach();
        },
        get barangayMatches() {
            const q = this.barangayQuery.trim().toLowerCase();
            return this.barangayNames.filter((b) => !q || b.toLowerCase().includes(q));
        },
        setAudience(kind) {
            this.form.audience = kind;
            if (kind === 'all') { this.form.barangays = []; this.form.sectors = []; }
            this.countReach();
        },
        get audienceSummary() { return audienceWords(this.contract, this.form.barangays, this.form.sectors); },
        get partialSectors() { return this.form.sectors.some((s) => s !== 'senior'); },
        // How many residents it reaches now, from the server, a moment after
        // the officer stops choosing.
        countReach() {
            clearTimeout(this.reachTimer);
            this.reachTimer = setTimeout(async () => {
                try {
                    const res = await api.get('/admin/occasions/audience', { params: { barangays: this.form.barangays, sectors: this.form.sectors } });
                    this.reach = res.data;
                } catch {
                    this.reach = null;
                }
            }, 350);
        },
        onType() {
            if (this.form.type === 'birthday') this.form.trigger = 'birthday';
            else if (this.form.trigger === 'birthday') this.form.trigger = 'window';
        },
        get startsIso() { return this.form.start_date ? manilaIso(this.form.start_date, this.form.start_time) : null; },
        get endsIso() { return this.form.end_date ? manilaIso(this.form.end_date, this.form.end_time || '23:59') : null; },
        get chosenBanner() { return this.banners.find((b) => b.id === Number(this.form.banner_id)) || null; },
        /** The first thing that would stop a save; the server still decides. */
        get problem() {
            const f = this.form;
            if (!f.name.trim()) return 'Name the occasion.';
            if (f.type === 'birthday' && f.trigger !== 'birthday') return "A birthday occasion shows on each resident's birthday.";
            if (f.trigger !== 'birthday' && (!f.start_date || !f.end_date)) return 'Say when it starts and ends.';
            if (this.startsIso && this.endsIso && instant(this.endsIso) <= instant(this.startsIso)) return 'It must end after it starts.';
            if (f.trigger === 'window' && this.endsIso && instant(this.endsIso) <= Date.now()) return 'It would already be over.';
            if (f.audience === 'some' && !f.barangays.length && !f.sectors.length) return 'Choose the barangays or groups it is for, or choose all residents.';
            if (!f.popup_title.trim() || !f.popup_body.trim()) return 'Write the pop-up\'s title and message.';
            if (f.button === 'link' && !/^https:\/\/\S+$/i.test(f.popup_button_url.trim())) return 'The button link must start with https://.';
            if (f.button === 'post' && !f.popup_post_id) return 'Choose the Balita post the button opens.';
            if (f.button !== 'none' && !f.popup_button_label.trim()) return 'Write the button\'s label.';
            if (f.banner_mode === 'existing' && !f.banner_id) return 'Choose the banner, or choose no banner.';
            if (!this.sharesBannerPicture && this.image.busy) return 'Wait for the picture to finish uploading.';
            if (!this.sharesBannerPicture && this.image.failed) return 'The picture did not upload. Try it again, keep the current one, or remove it.';
            return '';
        },
        // What may not work as expected, before it is saved; the server says
        // the same (and more) on the saved occasion.
        get formWarnings() {
            const out = [];
            const b = this.form.banner_mode === 'existing' ? this.chosenBanner : null;
            if (b) {
                if (!b.has_image) out.push('The banner has no picture yet.');
                if (!['Approved', 'Released'].includes(b.status)) out.push('The banner is ' + this.statusLabel(b.status) + ': it shows only once approved.');
                if (!this.form.banner_sync && this.form.trigger === 'window' && this.form.start_date && this.form.end_date) {
                    const covers = (!b.start_date || b.start_date <= this.form.start_date) && (!b.end_date || b.end_date >= this.form.end_date);
                    if (!covers) out.push("The banner's own dates do not cover this occasion. Turn on \"Show the banner during this occasion\" or change the banner's dates.");
                }
                const others = (b.used_by || []).filter((o) => o.id !== this.editing?.id).map((o) => o.name);
                if (others.length) out.push('This banner is also used by: ' + others.join(', ') + '. Its own schedule is not changed by either.');
            }
            if (this.partialSectors) out.push('PWD, solo parent, 4Ps and IP come from barangay constituent records: residents whose record is not linked or tagged will not see this.');
            if (this.form.trigger === 'birthday') out.push('Residents with no birthdate on their account do not get this.');
            return out;
        },
        payload() {
            const f = this.form;
            const some = f.audience === 'some';
            const body = {
                name: f.name.trim(), description: f.description.trim() || null, type: f.type, trigger: f.trigger,
                starts_at: this.startsIso, ends_at: this.endsIso,
                barangays: some ? f.barangays : [], sectors: some ? f.sectors : [],
                popup_title: f.popup_title.trim(), popup_body: f.popup_body.trim(),
                popup_button_label: f.button === 'none' ? null : f.popup_button_label.trim(),
                popup_button_url: f.button === 'link' ? f.popup_button_url.trim() : null,
                popup_post_id: f.button === 'post' ? Number(f.popup_post_id) : null,
                popup_frequency: f.popup_frequency, popup_dismissal: f.popup_dismissal,
                banner_id: f.banner_mode === 'existing' && f.banner_id ? Number(f.banner_id) : null,
                banner_sync: f.banner_mode === 'existing' && !!f.banner_sync,
                banner_display: f.banner_mode === 'existing' && f.banner_sync ? f.banner_display : null,
            };
            if (this.sharesBannerPicture) {
                // Sent again only when it is a new choice: an edit of the words
                // keeps the link the pop-up already has.
                if (!this.editing || !this.editing.popup?.image_from_banner || this.editing.banner?.id !== Number(f.banner_id)) body.picture_from_banner = true;
            } else if (!this.editing || this.image.changed || this.editing.popup?.image_from_banner) {
                body.media_id = this.image.id;
            }
            return body;
        },
        fieldError(key) {
            const v = this.fieldErrors?.[key];
            return Array.isArray(v) ? (v[0] || '') : (v || '');
        },
        // "Create a new banner": the Banners form opens, and the banner it
        // saves comes back here.
        newBanner() {
            window.dispatchEvent(new CustomEvent('banner-new', { detail: { returnTo: 'occasion' } }));
        },
        async bannerCreated(detail) {
            if (detail?.returnTo !== 'occasion' || !detail.banner) return;
            try {
                this.banners = ((await api.getAll('/admin/banners')).data || []).filter((b) => b.status !== 'Archived');
            } catch { /* the id is enough */ }
            this.form.banner_mode = 'existing';
            this.form.banner_id = detail.banner.id;
            toast('Banner "' + detail.banner.title + '" is now this occasion\'s banner. It needs approving like any banner.', 'info');
        },
        /** how: 'draft' saves it; 'activate' saves it and takes it as far as this officer may, up to Activated. */
        async save(how) {
            if (this.processing || this.problem) return;
            this.processing = true;
            this.fieldErrors = {};
            let saved = null;
            try {
                const res = this.editing
                    ? await api.put('/admin/occasions/' + this.editing.id, this.payload())
                    : await api.post('/admin/occasions', this.payload());
                saved = res.data;
                if (how === 'activate') saved = await this.advance(saved, 'Released');
                this.formOpen = false;
                toast(how === 'draft' ? (this.editing ? 'Changes saved.' : 'Saved as a draft.') : this.doneText(saved));
                await this.load();
            } catch (err) {
                if (err?.status === 422) this.fieldErrors = err.fields || {};
                toast(errText(err, saved ? 'Saved, but could not be activated. Do it from the list.' : 'Could not save this occasion.'), 'error');
                if (saved) { this.formOpen = false; await this.load(); }
            } finally {
                this.processing = false;
            }
        },
        doneText(o) {
            if (o.status === 'Released') return o.state === 'active' ? 'Activated. Residents in its audience see it the next time they open Esperanza.' : 'Activated. It starts by itself on ' + when(o.next_starts_at || o.starts_at) + '.';
            if (o.status === 'Pending Review') return 'Sent for review.';
            return 'Saved.';
        },
        /** Moves an occasion along the editorial steps up to `target`, as far as this officer may. */
        async advance(o, target) {
            const steps = ['Draft', 'Pending Review', 'Approved', 'Released'];
            let row = o;
            if (row.status === 'Rejected') row = (await api.post('/admin/occasions/' + row.id + '/transition', { to: 'Draft' })).data;
            while (steps.indexOf(row.status) >= 0 && steps.indexOf(row.status) < steps.indexOf(target)) {
                const next = steps[steps.indexOf(row.status) + 1];
                if (!this.canMove(row, next)) break;
                row = (await api.post('/admin/occasions/' + row.id + '/transition', { to: next })).data;
            }
            return row;
        },

        // -- The list's actions
        async act(o, what) {
            if (this.processing) return;
            if (what === 'archive' && !window.confirm('Archive "' + o.name + '"? It stops showing and leaves this list; its record is kept.')) return;
            this.processing = true;
            try {
                let row = o;
                if (what === 'activate') row = await this.advance(o, 'Released');
                else if (what === 'pause') row = (await api.post('/admin/occasions/' + o.id + '/pause')).data;
                else if (what === 'resume') row = (await api.post('/admin/occasions/' + o.id + '/resume')).data;
                else if (what === 'archive') row = (await api.post('/admin/occasions/' + o.id + '/transition', { to: 'Archived' })).data;
                else if (what === 'duplicate') row = (await api.post('/admin/occasions/' + o.id + '/duplicate')).data;
                toast({
                    activate: this.doneText(row),
                    pause: 'Paused. Residents no longer see it until it is resumed.',
                    resume: 'Resumed. It shows again as its dates allow.',
                    archive: 'Archived.',
                    duplicate: 'A draft copy was made: "' + row.name + '".',
                }[what]);
                await this.load();
                if (what === 'duplicate') this.openEdit(row);
            } catch (err) {
                toast(errText(err, 'Could not update this occasion.'), 'error');
                await this.load();
            } finally {
                this.processing = false;
            }
        },
        view(o) { this.viewing = o; this.viewOpen = true; },

        // -- Preview: the pop-up as a resident sees it. Nothing is saved or sent.
        previewForm() {
            const f = this.form;
            this.previewOf = {
                birthday: f.trigger === 'birthday',
                type: this.typeLabel(f.type),
                title: (f.popup_title.trim() || 'Title of the pop-up').replaceAll('{first_name}', 'Juan'),
                message: (f.popup_body.trim() || 'The message residents read.').replaceAll('{first_name}', 'Juan'),
                image: this.sharesBannerPicture ? this.bannerPicture.url : this.image.url,
                button: f.button === 'none' ? '' : (f.popup_button_label.trim() || (f.button === 'post' ? 'Tingnan sa Balita' : 'Open')),
                external: f.button === 'link',
                dismissible: f.popup_dismissal === 'until_dismissed',
                frequency: this.frequencyLabel(f.popup_frequency),
            };
            this.previewOpen = true;
        },
        previewRow(o) {
            const p = o.popup || {};
            this.previewOf = {
                birthday: o.trigger === 'birthday',
                type: this.typeLabel(o.type),
                title: (p.title || '').replaceAll('{first_name}', 'Juan'),
                message: (p.body || '').replaceAll('{first_name}', 'Juan'),
                image: p.image?.url || null,
                button: p.button_label || '',
                external: !!p.button_url,
                dismissible: p.dismissal === 'until_dismissed',
                frequency: this.frequencyLabel(p.frequency),
            };
            this.previewOpen = true;
        },
        when,
    };
}

export function registerOccasions(Alpine) {
    Alpine.data('adminBanners', adminBanners);
    Alpine.data('adminOccasions', adminOccasions);
}
