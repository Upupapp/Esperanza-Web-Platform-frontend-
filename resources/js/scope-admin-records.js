// Scope round 2026-10-15 - Constituents and Communications: data requests, corrections, record history, message history, merges, the feedback desk, editing posts and events.
//
// Loaded only on the pages that use it (see the area loader at the end of
// app.js), so the shared bundle stays within its budget. register() runs
// before Alpine starts.
//
// Every component here sits inside its page's own component, and Alpine
// resolves `this` over the whole scope chain: a method may use the page's
// state and helpers (openAccount, accountDetail, citizenAccountList,
// loadRegistry, dqRecordName, tab ...). So the Constituents components'
// own fields carry a prefix (dr, ic, cm, rh, mh) and none can shadow a
// name the page's markup around them uses.

import api from './api/client';
import { registerBalitaMedia } from './balita-media';
import { registerOccasions } from './admin-occasions';
import { telHref, GUARDIAN_SOURCES } from './guardian-scan';

const OPEN = ['Submitted', 'Under Review'];

// The statuses these screens show, in x-ui.badge's colours. The badge's
// statusBind form prints its whole colour table into the page at every use,
// and the Constituents page is near its size budget.
const TONES = {
    Submitted: ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
    'Under Review': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
    Completed: ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
    Rejected: ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
    Cancelled: ['bg-slate-50 text-slate-500 ring-slate-200', 'bg-slate-400'],
};
const tone = (status) => (TONES[status] || ['bg-white text-slate-500 ring-slate-300', 'bg-slate-300'])[0];
const dot = (status) => (TONES[status] || ['', 'bg-slate-300'])[1];

/** 'Oct 15, 2026, 9:30 AM' in Manila time; '' when there is no date. */
function when(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString('en-US', { timeZone: 'Asia/Manila', month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

/** The error's own words, the field's for a 422 (staff site: English). */
function errText(err, fallback) {
    if (err?.explain) return err.explain('en');
    return err?.localised ? err.localised('en') : fallback;
}

// On window: a toast raised after an await may come from a button its own
// action removed, and that dispatch would never arrive.
function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}

// A 401 or 403 already raised its own toast (app.js), so it is not doubled.
function fail(err, fallback) {
    if (err?.status === 401 || err?.status === 403) return;
    toast(errText(err, fallback), 'error');
}

// ------------------------------------------------------------------ Names

const IDENTITY_FIELDS = { first_name: 'First name', middle_name: 'Middle name', last_name: 'Last name', suffix: 'Suffix', birthdate: 'Birth date', sex: 'Sex', barangay: 'Barangay' };

/**
 * The middle name and suffix of an account, read back from its full name:
 * the account detail sends name, first_name and last_name only, and the
 * name is those four joined by spaces (CitizenAccount::getNameAttribute).
 * Where the last name fits more than once the last place wins, so a middle
 * name equal to the last name stays the middle name. `known` is false when
 * the name does not split that way, and the officer is told to check.
 */
function splitName(full, first, last) {
    const f = String(full || '').trim();
    const a = String(first || '').trim();
    const z = String(last || '').trim();
    if (!f || !a || !z) return { middle: '', suffix: '', known: false };
    if (f === a + ' ' + z) return { middle: '', suffix: '', known: true };
    if (!f.startsWith(a + ' ')) return { middle: '', suffix: '', known: false };
    const rest = f.slice(a.length + 1);
    for (let p = rest.length - z.length; p >= 0; p -= 1) {
        if (rest.slice(p, p + z.length) !== z) continue;
        if (p > 0 && rest[p - 1] !== ' ') continue;
        const after = rest.slice(p + z.length);
        if (after && after[0] !== ' ') continue;
        const suffix = after.trim();
        if (suffix.includes(' ')) continue;
        return { middle: rest.slice(0, Math.max(0, p - 1)).trim(), suffix, known: true };
    }
    return { middle: '', suffix: '', known: false };
}

function normSex(v) {
    const s = String(v || '').trim().toLowerCase();
    if (s === 'male' || s === 'm') return 'Male';
    if (s === 'female' || s === 'f') return 'Female';
    return '';
}

function shown(v) {
    if (v === null || v === undefined || v === '') return '(empty)';
    if (typeof v === 'boolean') return v ? 'Yes' : 'No';
    let s;
    if (Array.isArray(v)) s = v.length ? v.map((x) => (x && typeof x === 'object' ? JSON.stringify(x) : String(x))).join(', ') : '(none)';
    else if (typeof v === 'object') s = JSON.stringify(v);
    else s = String(v);
    return s.length > 200 ? s.slice(0, 199) + '…' : s;
}

function humanize(key) {
    const s = String(key || '').replace(/_/g, ' ').trim();
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
}

// ---------------------------------------------------------- Record history

const HISTORY_ACTIONS = {
    'constituent.individual.created': 'Record created',
    'constituent.individual.updated': 'Record edited',
    'constituent.individual.deceased': 'Marked as deceased',
    'constituent.individual.transfer': 'Transferred',
    'constituent.individual.archive': 'Archived',
    'constituents.merged': 'Merged with another record',
    'constituents.merge_reverted': 'Merge undone',
    'constituents.verified': 'Verified',
    'constituents.returned': 'Sent back to the resident for changes',
    'constituent.registry_match.confirmed': 'Confirmed as the resident’s record',
    'constituent.registry_match.linked': 'Linked to the resident’s account',
    'constituent.registry_match.rejected': 'Found to be someone else',
    'resident_profile.created': 'Profile sent by the resident',
    'resident_profile.matched': 'Matched to the resident’s profile',
};

const HISTORY_FIELDS = {
    dob: 'Birth date', sitio: 'Sitio / Purok', contact: 'Contact number', income_range: 'Income range',
    civil_status: 'Civil status', relation: 'Relation in household', household_id: 'Household', family_id: 'Family',
    tags: 'Sector tags', verification: 'Verification', review_note: 'Note to the resident', merged_into: 'Merged into',
};

// ------------------------------------------------------------- Data requests

const DR_KINDS = { access: 'See their data', correction: 'Correct their data', erasure: 'Erase their data', portability: 'A copy of their data', name_change: 'Change their name' };

// Why a verified resident's name changes, and the proof each needs (2026-10-16).
const NAME_REASONS = {
    marriage: ['Marriage', 'PSA marriage certificate'],
    annulment: ['Annulment or nullity of marriage', 'Annotated PSA marriage certificate, or the court decision'],
    court_order: ['Court order', 'Certified copy of the court order or decision'],
    clerical: ['Misspelling', 'PSA birth certificate, or a valid ID with the right spelling'],
};
const fullName = (n) => [n?.first_name, n?.middle_name, n?.last_name, n?.suffix].filter(Boolean).join(' ');

// --------------------------------------------------------------- Feedback

const FB_CHIPS = [
    { key: 'open', label: 'Open', params: { open: 1 } },
    { key: 'new', label: 'Submitted', params: { status: 'Submitted' } },
    { key: 'review', label: 'Under Review', params: { status: 'Under Review' } },
    { key: 'resolved', label: 'Completed', params: { status: 'Completed' } },
    { key: 'closed', label: 'Cancelled', params: { status: 'Cancelled' } },
    { key: 'all', label: 'All', params: {} },
];

// --------------------------------------------------------------- Balita

// Owner, 2026-10-18: "polish the balita flow in both admin and user type" -
// photos and videos are attached while writing, every move a post can make
// comes from the server (available_transitions) and the officer's own
// permission, and comments and residents' reports are in reach.

const BALITA_CATEGORIES = ['Community', 'Health', 'Public Service', 'Advisory', 'Livelihood', 'Governance'];
const emptyMedia = () => ({ ids: [], busy: false, failed: false });
const balitaToast = (message, variant = 'success') => window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
const sameIds = (a, b) => a.length === b.length && a.every((id, i) => id === b[i]);

// One feed (owner, 2026-10-21): Balita is the LGU's posts, with no greeting
// kind; the pop-ups residents see on an occasion are kept in Banners &
// Occasions. Any post can be scheduled. Times are Esperanza's (UTC+8, no
// daylight saving), whatever the officer's computer is set to.

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

/** The composer's fields, empty or read from a post. */
function postForm(post = null, barangays = [], ownBarangay = null) {
    const at = manilaParts(post?.publish_at);
    return {
        title: post?.title || '',
        category: post?.category || 'Community',
        body: post?.body || '',
        audience: ownBarangay || post?.barangay ? 'Barangay' : 'Municipal',
        barangay: ownBarangay || post?.barangay || barangays[0] || '',
        schedule: !!post?.publish_at && instant(post.publish_at) > Date.now(),
        publish_date: at.date,
        publish_time: at.time || '08:00',
        // Also post on Facebook when published: the writer's choice, or null
        // to follow the Page's default sharing (welfare round 2026-10-29).
        fb_share: typeof post?.facebook?.share === 'boolean' ? post.facebook.share : null,
    };
}

// A post's Facebook status as a chip (welfare round 2026-10-29), from the
// row's `facebook: {share, status, permalink, error, posted_at}`.
const FACEBOOK_CHIPS = {
    pending: { label: 'Waiting to post on Facebook', tone: 'bg-sky-50 text-sky-800 ring-sky-200' },
    posting: { label: 'Posting to Facebook…', tone: 'bg-sky-50 text-sky-800 ring-sky-200' },
    posted: { label: 'Posted on Facebook', tone: 'bg-emerald-50 text-emerald-800 ring-emerald-200' },
    failed: { label: 'Facebook: failed', tone: 'bg-rose-50 text-rose-800 ring-rose-200' },
    notShared: { label: 'Not shared on Facebook', tone: 'bg-slate-100 text-slate-700 ring-slate-200' },
    planned: { label: 'Goes to Facebook when published', tone: 'bg-slate-100 text-slate-700 ring-slate-200' },
};

/** The Balita tab: one feed of LGU posts on Draft -> Review -> Publish -> Archive. */
function adminBalita(barangays = []) {
    return {
        loading: true,
        failed: false,
        posts: [],
        summary: null,
        search: '',
        status: 'All',
        scope: 'All',
        category: 'All',
        processing: false,
        statuses: ['Draft', 'Pending Review', 'Approved', 'Released', 'Rejected', 'Archived'],
        // The Scheduled chip sits beside the status chips but is not a
        // status: approved posts waiting for their time (2026-10-21).
        scheduledOnly: false,
        categoryList: BALITA_CATEGORIES,
        composerOpen: false,
        editing: null,
        form: postForm(null, barangays),
        // What the form held when it opened, to ask before throwing changes away.
        snapshot: '',
        // The day and time show in the schedule strip while they are chosen.
        scheduleEditing: false,
        fieldErrors: {},
        media: emptyMedia(),
        formKey: 1,
        reviewOpen: false,
        selected: null,
        rejectReason: '',
        // How many posts the feed shows; Show more adds ten (redesign 2026-10-26).
        shown: 10,
        // The Facebook Page connection, without its token (GET
        // /admin/integrations/facebook), or null when it could not be read.
        fb: null,
        fbBusy: null,
        // Delete asks in the page, with "Also remove it from Facebook".
        archOpen: false,
        archPost: null,
        archFromFb: false,

        init() {
            // A new filter starts the feed at the top again.
            this.$watch(() => [this.search, this.status, this.scope, this.category, this.scheduledOnly].join('|'), () => { this.shown = 10; });
            // Live updates (2026-11-15): a post, like or comment anywhere
            // reloads the feed quietly once it has loaded, while its tab is
            // shown and no dialog (the composer, a review) is open.
            this.$store.live.on(this.$el, 'balita', () => (this.loadedOnce ? this.load(true) : null), { busy: () => this.composerOpen || this.reviewOpen || this.archOpen || this.processing });
        },
        loadedOnce: false,
        // quiet (a live update): the feed on screen stays, with its filters and
        // Show more, until the new one comes; a failed reload keeps it.
        async load(quiet = false) {
            if (!quiet) {
                this.loading = true;
                this.failed = false;
            }
            try {
                const [res, summary, fb] = await Promise.all([
                    api.getAll('/admin/announcements'),
                    api.get('/admin/announcements/summary').catch(() => (quiet ? { data: this.summary } : null)),
                    api.get('/admin/integrations/facebook').catch(() => (quiet ? { data: this.fb } : null)),
                ]);
                this.posts = res.data || [];
                this.summary = summary?.data || null;
                this.fb = fb?.data || null;
                this.failed = false;
                this.loadedOnce = true;
            } catch {
                if (!quiet) this.failed = true;
            } finally {
                this.loading = false;
            }
            if (!quiet) this.openLinked();
        },
        // A reviewer's notification opens /admin/communications/?tab=balita&post=ID,
        // once: the list reloads after every decision, and the link must not
        // filter it to that post again (2026-10-19).
        linkedDone: false,
        openLinked() {
            const params = new URLSearchParams(window.location.search);
            if (this.linkedDone || params.get('tab') !== 'balita' || !params.get('post')) return;
            const post = this.posts.find((p) => String(p.id) === params.get('post'));
            if (!post) return;
            this.linkedDone = true;
            if (post.status === 'Pending Review' && this.canMove(post, 'Approved')) this.openReview(post);
            else this.search = post.title;
        },
        get categories() { return [...new Set([...BALITA_CATEGORIES, ...this.posts.map((p) => p.category).filter(Boolean)])]; },
        get filtered() {
            const q = this.search.trim().toLowerCase();
            return this.posts.filter((a) =>
                (a.barangay === null || this.$store.session.inScope(a.barangay))
                && (this.scheduledOnly ? this.isScheduled(a) : (this.status === 'All' || a.status === this.status))
                && (this.scope === 'All' || (this.scope === 'Municipal' ? !a.barangay : a.barangay === this.scope))
                && (this.category === 'All' || a.category === this.category)
                && (!q || (a.title || '').toLowerCase().includes(q) || (a.body || '').toLowerCase().includes(q)));
        },
        get shownPosts() { return this.filtered.slice(0, this.shown); },
        count(status) { return this.posts.filter((a) => a.status === status).length; },
        // Needs your attention: posts this officer can decide, and their own sent back.
        get waitingForMe() { return this.posts.filter((a) => a.status === 'Pending Review' && this.canMove(a, 'Approved')).length; },
        get sentBackToMe() { return this.posts.filter((a) => a.status === 'Rejected' && this.mayChange(a)).length; },
        // The post's line under its writer: when it went out, or when it will.
        postWhen(post) {
            if (post.published_at) return when(post.published_at).split(',').slice(0, 2).join(',');
            if (this.isScheduled(post) && post.publish_at) return 'Scheduled · ' + when(post.publish_at);
            return 'Not yet published';
        },
        // The four cards: the server's counts, or the list's while they load.
        card(key) {
            if (this.summary && key in this.summary) {
                return key === 'drafts' ? this.summary.drafts + (this.summary.sent_back || 0) : this.summary[key];
            }
            return {
                published: this.count('Released'), pending_review: this.count('Pending Review'),
                drafts: this.count('Draft') + this.count('Rejected'), scheduled: this.scheduledCount,
            }[key] ?? 0;
        },
        get scheduledCount() { return this.posts.filter((a) => this.isScheduled(a)).length; },
        showStatus(s) { this.scheduledOnly = false; this.status = s; },
        // The status pill: a status, or Scheduled, which is a filter of its own.
        get statusChoice() { return this.scheduledOnly ? 'Scheduled' : this.status; },
        set statusChoice(v) { if (v === 'Scheduled') this.showScheduled(); else this.showStatus(v); },
        showScheduled() { this.scheduledOnly = true; this.status = 'All'; },

        // -- Scheduling
        // Approved and waiting for its time; the server releases it then.
        isScheduled(post) { return post.scheduled === true || (post.status === 'Approved' && instant(post.publish_at) > Date.now()); },
        // Has a time to come, whatever its status: approving it keeps it until then.
        hasFutureTime(post) { return instant(post?.publish_at) > Date.now(); },
        goesLiveText(post) {
            return post.publish_at ? 'Goes live ' + when(post.publish_at) : 'Goes live at its time';
        },
        get scheduleIso() { return this.form.schedule ? manilaIso(this.form.publish_date, this.form.publish_time) : null; },
        get scheduleProblem() {
            if (!this.form.schedule) return '';
            if (!this.form.publish_date) return 'Choose the day it goes out.';
            if (instant(this.scheduleIso) <= Date.now()) return 'Choose a time that is still to come.';
            return '';
        },
        fieldError(key) {
            const v = this.fieldErrors?.[key];
            return Array.isArray(v) ? (v[0] || '') : (v || '');
        },

        // One set of words for a status, in the chips, the cards and the editor.
        statusLabel(status) { return { Released: 'Published', Rejected: 'Sent back', 'Pending Review': 'Waiting for review' }[status] || status; },
        statusTone(status) {
            return {
                Draft: 'bg-slate-100 text-slate-700 ring-slate-200',
                'Pending Review': 'bg-amber-50 text-amber-800 ring-amber-200',
                Approved: 'bg-sky-50 text-sky-800 ring-sky-200',
                Released: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                Rejected: 'bg-rose-50 text-rose-800 ring-rose-200',
                Archived: 'bg-slate-50 text-slate-500 ring-slate-200',
            }[status] || 'bg-slate-100 text-slate-700 ring-slate-200';
        },

        // What the officer may do to a post: the move must be one the server
        // lists for it, and the officer must hold the permission it names.
        // Every officer writes Balita; one who cannot approve (a writer)
        // changes only their own posts, as the server checks (2026-10-19).
        can(action) { return this.$store.session.canPerm('comm_news', action); },
        get approver() { return this.can('approve'); },
        // The server's can_change (owner, 2026-11-05) is false where this
        // officer may not touch the post, e.g. an MDRRMO Head looking at
        // another office's post. Absent (an older server), nothing changes.
        mayChange(post) { return post.can_change !== false && (this.approver || !!post.mine); },
        canMove(post, to) {
            const action = post.available_transitions?.[to];
            return !!action && this.can(action) && this.mayChange(post);
        },
        canEdit(post) { return ['Draft', 'Pending Review', 'Rejected'].includes(post.status) && this.can('edit') && this.mayChange(post); },
        // For good, once it is a draft, sent back or archived (2026-10-21):
        // anything residents can see is archived first.
        // A barangay officer writes for their own barangay only.
        get ownBarangay() {
            const account = this.$store.session.account;
            if (!account || account.roleKey === 'super_admin' || !account.scope || account.scope === 'Municipal') return null;
            return account.scope;
        },
        get audienceText() {
            if (this.ownBarangay) return 'Brgy. ' + this.ownBarangay;
            return this.form.audience === 'Barangay' ? 'Brgy. ' + this.form.barangay : 'All of Esperanza';
        },

        // -- Writing (Facebook-like: who it is for, the words, the photos, and
        //    when it goes out; the preview beside it is what residents see)
        openNew() {
            this.editing = null;
            this.form = postForm(null, barangays, this.ownBarangay);
            this.fieldErrors = {};
            this.media = emptyMedia();
            this.scheduleEditing = false;
            this.formKey += 1;
            this.composerOpen = true;
            this.$nextTick(() => { this.snapshot = this.formState(); });
        },
        openEdit(post) {
            this.editing = post;
            this.form = postForm(post, barangays, this.ownBarangay);
            this.fieldErrors = {};
            this.media = { ids: (post.media || []).map((m) => m.id), busy: false, failed: false };
            this.scheduleEditing = false;
            this.formKey += 1;
            this.reviewOpen = false;
            this.composerOpen = true;
            this.$nextTick(() => { this.snapshot = this.formState(); });
        },
        // Share an update: the composer, opened on photos, a time or an advisory.
        compose(start) {
            this.openNew();
            this.$nextTick(() => {
                if (start === 'schedule') this.addSchedule();
                if (start === 'advisory') this.form.category = 'Advisory';
                // Where it started is not a change to ask about on closing.
                this.snapshot = this.formState();
                // The picker is remade with the form; ask it once it is there.
                if (start === 'media') setTimeout(() => window.dispatchEvent(new CustomEvent('balita-add-media')), 50);
            });
        },
        // -- Add to your post: a time to go out, or one barangay.
        addSchedule() {
            this.form.schedule = true;
            this.scheduleEditing = true;
            this.$nextTick(() => document.getElementById('balita-publish-date')?.focus());
        },
        removeSchedule() {
            this.form.schedule = false;
            this.scheduleEditing = false;
        },
        toggleBarangay() {
            this.form.audience = this.form.audience === 'Barangay' ? 'Municipal' : 'Barangay';
            if (this.form.audience === 'Barangay') this.$nextTick(() => document.getElementById('balita-barangay')?.focus());
        },
        formState() { return JSON.stringify([this.form, this.media.ids]); },
        get dirty() { return this.composerOpen && this.snapshot !== '' && this.formState() !== this.snapshot; },
        // Cancel, the X, Esc and a click outside all come here.
        requestClose() {
            if (this.processing) return;
            if (this.media.busy || this.dirty) {
                if (!window.confirm(this.media.busy ? 'A photo or video is still uploading. Close and discard it?' : 'Discard the changes to this post?')) return;
            }
            this.composerOpen = false;
        },
        get formReady() {
            return this.form.title.trim() !== '' && this.form.body.trim() !== '' && !this.media.busy && !this.scheduleProblem;
        },
        // What the primary button does: publish now (an approver), send for
        // review (a writer), or schedule (an approver with a time).
        get primaryLabel() {
            if (this.form.schedule) return this.approver ? 'Schedule Post' : 'Send for review';
            return this.approver ? 'Publish now' : 'Send for review';
        },
        // The post as residents will see it in Balita.
        get previewWhen() {
            if (this.form.schedule && this.scheduleIso && !this.scheduleProblem) return when(this.scheduleIso);
            return 'Just now';
        },

        /** Saves the post as it stands; the saved post (with its status). */
        async persist() {
            const barangay = this.ownBarangay || (this.form.audience === 'Barangay' ? this.form.barangay : null);
            const publishAt = this.scheduleIso;
            let post = this.editing;
            if (!post) {
                const res = await api.post('/admin/announcements', {
                    title: this.form.title.trim(), body: this.form.body.trim(), category: this.form.category,
                    barangay, official: true, media_ids: this.media.ids, publish_at: publishAt,
                    ...(this.fbOffered() ? { facebook_share: this.fbShareChecked() } : {}),
                });
                return res.data;
            }
            const changes = {};
            if (this.form.title.trim() !== post.title) changes.title = this.form.title.trim();
            if (this.form.body.trim() !== post.body) changes.body = this.form.body.trim();
            if (this.form.category !== post.category) changes.category = this.form.category;
            if (barangay !== (post.barangay || null)) changes.barangay = barangay;
            if (!sameIds(this.media.ids, (post.media || []).map((m) => m.id))) changes.media_ids = this.media.ids;
            if ((publishAt ? instant(publishAt) : null) !== (post.publish_at ? instant(post.publish_at) : null)) changes.publish_at = publishAt;
            if (this.fbOffered() && this.form.fb_share !== null && this.form.fb_share !== (post.facebook?.share ?? null)) changes.facebook_share = this.form.fb_share;
            if (Object.keys(changes).length) await api.put('/admin/announcements/' + post.id, changes);
            return post;
        },
        /**
         * how: 'draft' saves it; 'send' saves it and moves it on as far as
         * this officer may: a writer sends it for review; an approver
         * publishes it now, or approves it for its time (the server puts it
         * out then).
         */
        async save(how) {
            if (this.processing || !this.formReady) return;
            if (this.media.failed) {
                balitaToast('Some photos or videos did not upload. Try them again or remove them first.', 'error');
                return;
            }
            this.processing = true;
            this.fieldErrors = {};
            let saved = false;
            try {
                const post = await this.persist();
                saved = true;
                let status = post.status;
                if (how === 'send') {
                    const step = (to) => api.post('/admin/announcements/' + post.id + '/transition', { to }).then(() => { status = to; });
                    if (status === 'Rejected') await step('Draft');
                    if (status === 'Draft') await step('Pending Review');
                    if (this.approver && status === 'Pending Review') {
                        await step('Approved');
                        if (!this.form.schedule) await step('Released');
                    }
                }
                this.composerOpen = false;
                const where = post.barangay ? 'Residents of Brgy. ' + post.barangay : 'Residents';
                balitaToast(
                    how === 'draft' ? (this.editing ? 'Changes saved.' : 'Saved as a draft.')
                        : status === 'Released' ? 'Published. ' + where + ' are notified.'
                            : status === 'Approved' ? 'Scheduled for ' + when(this.scheduleIso) + '. It goes out by itself then.'
                                : 'Sent for review. ' + (this.approver ? 'The officers who approve Balita are notified.'
                                    : this.$store.session.account?.roleKey === 'mio_staff' ? 'Your MIO Head is notified to review it.'
                                        : this.$store.session.account?.roleKey === 'mdrrmo_staff' ? 'Your MDRRMO Head approves it.'
                                        : 'Your Department Head or the Municipal Information Office approves it.'),
                );
                await this.load();
            } catch (err) {
                if (err?.status === 422) this.fieldErrors = err.fields || {};
                balitaToast(errText(err, saved ? 'The post was saved, but could not be moved on. Do it from its card.' : 'Could not save this post.'), 'error');
                if (saved) this.composerOpen = false;
                await this.load();
            } finally {
                this.processing = false;
            }
        },

        // -- Use as Template: a new draft by this officer with the same words,
        //    audience and photos, made by the server; it opens to edit.
        async useAsTemplate(post) {
            if (this.processing) return;
            this.processing = true;
            try {
                const res = await api.post('/admin/announcements/' + post.id + '/duplicate');
                await this.load();
                const copy = this.posts.find((p) => p.id === res.data.id) || res.data;
                balitaToast('A new draft was made from "' + post.title + '".', 'info');
                this.openEdit(copy);
            } catch (err) {
                balitaToast(errText(err, 'Could not make a draft from this post.'), 'error');
            } finally {
                this.processing = false;
            }
        },

        // -- Moving it along
        // true when it moved; 'scheduled' when it may not be published before
        // its time (the server releases it then); false otherwise.
        async move(post, to, remarks) {
            try {
                await api.post('/admin/announcements/' + post.id + '/transition', { to, remarks: remarks || undefined });
                return true;
            } catch (err) {
                if (err?.code === 'SCHEDULED_FOR_LATER') {
                    balitaToast('Approved. ' + this.goesLiveText(post) + '.', 'info');
                    return 'scheduled';
                }
                balitaToast(errText(err, 'Could not update this post.'), 'error');
                return false;
            }
        },
        openReview(post) {
            this.selected = post;
            this.rejectReason = '';
            this.reviewOpen = true;
        },
        async approveAndPublish() {
            if (this.processing || !this.selected) return;
            this.processing = true;
            try {
                if (!(await this.move(this.selected, 'Approved'))) return;
                // A post with its own time is only approved: the server puts
                // it out at that time.
                if (this.hasFutureTime(this.selected)) {
                    balitaToast('Approved. ' + this.goesLiveText(this.selected) + '.');
                    this.reviewOpen = false;
                    await this.load();
                    return;
                }
                const published = await this.move(this.selected, 'Released');
                if (published === 'scheduled') {
                    // Said by move(); the post is approved and waits for its time.
                } else if (published) {
                    balitaToast('Published. Residents ' + (this.selected.barangay ? 'of Brgy. ' + this.selected.barangay + ' ' : '') + 'are notified.');
                } else {
                    balitaToast('Approved, but not published yet. Publish it from its card.', 'error');
                }
                this.reviewOpen = false;
                await this.load();
            } finally {
                this.processing = false;
            }
        },
        async requestChanges() {
            if (this.processing || this.rejectReason.trim().length < 5) return;
            this.processing = true;
            try {
                if (await this.move(this.selected, 'Rejected', this.rejectReason.trim())) {
                    this.reviewOpen = false;
                    balitaToast('Sent back with your remarks.', 'info');
                    await this.load();
                }
            } finally {
                this.processing = false;
            }
        },
        async quick(post, to, done) {
            if (this.processing) return;
            if (to === 'Archived' && !window.confirm('Archive "' + post.title + '"? Residents no longer see it.')) return;
            this.processing = true;
            try {
                const moved = await this.move(post, to);
                if (moved) {
                    if (moved === true) balitaToast(done);
                    await this.load();
                }
            } finally {
                this.processing = false;
            }
        },
        // A scheduled post is changed from Drafts: back there first (the
        // server lets an approved post go back to Draft, and the scheduler
        // releases only approved ones), then the editor opens on it, where
        // the text or the time can change, or the time be cleared. It could
        // not be changed or stopped at all before (flow guide 2026-11-13,
        // MIO bug 5).
        async changeScheduled(post) {
            if (this.processing) return;
            this.processing = true;
            let moved = false;
            try {
                moved = (await this.move(post, 'Draft')) === true;
                if (moved) await this.load();
            } finally {
                this.processing = false;
            }
            if (!moved) return;
            balitaToast('Back in Drafts: it will not go out at its time. Change it, then send it for review again.', 'info');
            this.openEdit(this.posts.find((p) => p.id === post.id) || { ...post, status: 'Draft' });
        },
        // Posts are archived, never deleted (owner, 2026-11-14: "archives only
        // no delete"). Asked in the page: a post that is on Facebook may go
        // from there too (remove_from_facebook on the move to Archived).
        archivePost(post) {
            if (this.processing) return;
            this.archPost = post;
            this.archFromFb = false;
            this.archOpen = true;
        },
        async confirmArchive() {
            const post = this.archPost;
            if (this.processing || !post) return;
            this.processing = true;
            try {
                const fromFb = post.facebook?.status === 'posted' && this.archFromFb;
                await api.post('/admin/announcements/' + post.id + '/transition', { to: 'Archived', remove_from_facebook: fromFb || undefined });
                this.archOpen = false;
                balitaToast(fromFb ? 'Archived, and removed from the Facebook Page.' : 'Archived. Residents no longer see it.');
                await this.load();
            } catch (err) {
                balitaToast(errText(err, 'Could not archive this post.'), 'error');
            } finally {
                this.processing = false;
            }
        },
        openComments(post) {
            window.dispatchEvent(new CustomEvent('balita-comments', { detail: { kind: 'announcements', id: post.id, title: post.title, moderate: this.approver && this.mayChange(post) } }));
        },
        cover(post) { return (post.media || [])[0] || null; },
        when,

        // -- Facebook (welfare round 2026-10-29)
        fbConnected() { return !!(this.fb && (this.fb.connected ?? this.fb.page_id)); },
        // The editor's tick box: only while a Page is connected and posts there by itself.
        fbOffered() { return this.fbConnected() && this.fb.auto_post !== false; },
        fbPageName() { return this.fb?.page_name || 'the Facebook Page'; },
        // What the box starts as: the Page's default sharing for this audience.
        fbDefault() {
            const share = this.fb?.default_share || 'municipal';
            if (share === 'all') return true;
            if (share === 'none') return false;
            return !this.ownBarangay && this.form.audience !== 'Barangay';
        },
        fbShareChecked() { return this.form.fb_share === null ? this.fbDefault() : this.form.fb_share; },
        fbChip(post) {
            const f = post.facebook || {};
            if (['pending', 'posting', 'posted', 'failed'].includes(f.status)) {
                return { kind: f.status, ...FACEBOOK_CHIPS[f.status], error: f.error || '', permalink: f.permalink || '' };
            }
            if (!this.fbConnected()) return null;
            if (post.status === 'Released') return { kind: 'notShared', ...FACEBOOK_CHIPS.notShared, error: '', permalink: '' };
            if (f.share === true && !['Archived', 'Rejected'].includes(post.status)) return { kind: 'planned', ...FACEBOOK_CHIPS.planned, error: '', permalink: '' };
            return null;
        },
        // Post now, or again after a failure (POST /admin/announcements/{id}/facebook).
        fbMayPost(post) { return this.approver && post.can_change !== false && post.status === 'Released' && this.fbConnected() && this.fb?.status !== 'token_problem'; },
        async fbPost(post) {
            if (this.fbBusy) return;
            this.fbBusy = post.id;
            try {
                const res = await api.post('/admin/announcements/' + post.id + '/facebook');
                const d = res?.data || {};
                post.facebook = d.facebook || (d.status ? d : { ...(post.facebook || {}), status: 'pending', error: null });
                balitaToast('Sent to Facebook. It goes up within a minute or two.');
            } catch (err) {
                balitaToast(errText(err, 'Could not send this post to Facebook.'), 'error');
            } finally {
                this.fbBusy = null;
            }
        },
    };
}

// The watermark's geometry, as App\Domain\Balita\Watermark burns it in
// (welfare round 2026-10-29): the seal's width is a share of the photo's
// shorter side, clamped; the margin a quarter of the mark; 85% opacity; the
// wordmark's height 60% of the seal size, never wider than half the photo.
// GET /admin/balita/watermark may send its own `geometry`, which wins.
const WATERMARK_GEOMETRY = {
    size_percent: { small: 12, medium: 15, large: 20 },
    min_px: 64,
    max_px: { small: 230, medium: 288, large: 384 },
    margin_ratio: 0.25,
    opacity: 0.85,
    wordmark_height_ratio: 0.6,
    wordmark_max_width_ratio: 0.5,
};
// The sample photo's own size, so the preview is the photo as uploaded.
// No people in the sample (a review found identifiable residents in the first one, 2026-10-29).
const SAMPLE_PHOTO = { src: '/images/esperanza/esperanza-aerial.jpg', width: 2000, height: 1500 };
// backend resources/branding/watermark-wordmark.png (seal + two lines of words).
const WORDMARK_ASPECT = 1053 / 228;

/**
 * Balita settings (welfare round 2026-10-29), for the officers who approve
 * Balita (comm_news approve): the watermark the server puts on every photo an
 * officer uploads - on or off, the seal or the seal with the municipality's
 * name, and its size - with a preview drawn to the server's numbers.
 * GET/PUT /admin/balita/watermark {enabled, mark, size}.
 */
function balitaSettings() {
    return {
        wmOpen: false,
        wmLoading: false,
        wmFailed: false,
        wmSaving: false,
        wm: { enabled: true, mark: 'seal', size: 'medium' },
        wmSaved: '',
        wmGeometry: WATERMARK_GEOMETRY,
        sample: SAMPLE_PHOTO,

        async wmShow() {
            this.wmOpen = true;
            await this.wmLoad();
        },
        async wmLoad() {
            this.wmLoading = true;
            this.wmFailed = false;
            try {
                const res = await api.get('/admin/balita/watermark');
                const d = res.data || {};
                this.wm = {
                    enabled: d.enabled !== false,
                    mark: d.mark === 'wordmark' ? 'wordmark' : 'seal',
                    size: ['small', 'medium', 'large'].includes(d.size) ? d.size : 'medium',
                };
                if (d.geometry) this.wmGeometry = { ...WATERMARK_GEOMETRY, ...d.geometry };
                this.wmSaved = JSON.stringify(this.wm);
            } catch (err) {
                this.wmFailed = true;
                fail(err, 'Could not read the Balita settings.');
            } finally {
                this.wmLoading = false;
                window.Alpine?.nextTick(() => window.renderIcons?.());
            }
        },
        wmDirty() { return this.wmSaved !== '' && JSON.stringify(this.wm) !== this.wmSaved; },
        async wmSave() {
            if (this.wmSaving || !this.wmDirty()) return;
            this.wmSaving = true;
            try {
                await api.put('/admin/balita/watermark', { ...this.wm });
                this.wmSaved = JSON.stringify(this.wm);
                this.wmOpen = false;
                toast(this.wm.enabled ? 'Saved. Photos uploaded from now on carry the watermark.' : 'Saved. Photos uploaded from now on have no watermark.');
            } catch (err) {
                fail(err, 'Could not save the Balita settings.');
            } finally {
                this.wmSaving = false;
            }
        },
        // The mark on the sample photo, in percentages of the photo, to the
        // pixel rules the server uses on a photo of this size.
        wmBox() {
            const g = this.wmGeometry;
            const { width, height } = this.sample;
            const short = Math.min(width, height);
            const pct = g.size_percent?.[this.wm.size] ?? 15;
            const max = g.max_px?.[this.wm.size] ?? 288;
            const target = Math.max(g.min_px ?? 64, Math.min(max, Math.round(short * pct / 100)));
            let w = target;
            let h = target;
            if (this.wm.mark === 'wordmark') {
                h = Math.round(target * (g.wordmark_height_ratio ?? 0.6));
                w = Math.round(h * WORDMARK_ASPECT);
                const cap = Math.floor(width * (g.wordmark_max_width_ratio ?? 0.5));
                if (w > cap) { w = cap; h = Math.round(w / WORDMARK_ASPECT); }
            }
            const margin = Math.round(Math.min(w, h) * (g.margin_ratio ?? 0.25));
            // An object, not a string: a string :style replaces the whole
            // attribute, x-show's display:none with it.
            return {
                width: (w / width * 100).toFixed(3) + '%',
                right: (margin / width * 100).toFixed(3) + '%',
                bottom: (margin / height * 100).toFixed(3) + '%',
                opacity: String(g.opacity ?? 0.85),
            };
        },
    };
}

/** The comments of one announcement, for the officers who moderate them. */
function balitaComments() {
    return {
        bcOpen: false,
        bcKind: 'announcements',
        bcId: null,
        bcTitle: '',
        bcModerate: false,
        bcLoading: false,
        bcFailed: false,
        bcItems: [],
        bcHiding: null,
        bcReason: '',
        bcBusy: false,
        async bcShow(detail) {
            Object.assign(this, { bcKind: detail.kind, bcId: detail.id, bcTitle: detail.title || '', bcModerate: !!detail.moderate, bcHiding: null, bcReason: '' });
            this.bcOpen = true;
            await this.bcLoad();
        },
        async bcLoad() {
            this.bcLoading = true;
            this.bcFailed = false;
            try {
                const res = await api.getAll('/admin/balita/' + this.bcKind + '/' + this.bcId + '/comments');
                this.bcItems = res.data || [];
            } catch {
                this.bcFailed = true;
            } finally {
                this.bcLoading = false;
            }
        },
        get bcHiddenCount() { return this.bcItems.filter((c) => c.hidden).length; },
        async bcHide(comment) {
            if (this.bcBusy || this.bcReason.trim().length < 3) return;
            this.bcBusy = true;
            try {
                await api.post('/admin/balita/' + this.bcKind + '/' + this.bcId + '/comments/' + comment.id + '/hide', { reason: this.bcReason.trim() });
                this.bcHiding = null;
                this.bcReason = '';
                balitaToast('Comment hidden from residents.', 'info');
                await this.bcLoad();
            } catch (err) {
                balitaToast(errText(err, 'Could not hide this comment.'), 'error');
            } finally {
                this.bcBusy = false;
            }
        },
        async bcRestore(comment) {
            if (this.bcBusy) return;
            this.bcBusy = true;
            try {
                await api.post('/admin/balita/' + this.bcKind + '/' + this.bcId + '/comments/' + comment.id + '/restore');
                balitaToast('Comment shown again.');
                await this.bcLoad();
            } catch (err) {
                balitaToast(errText(err, 'Could not restore this comment.'), 'error');
            } finally {
                this.bcBusy = false;
            }
        },
        when,
    };
}

// ================================================================= Events

const EVENT_CATEGORY_COLOR = { Community: 'bg-brand-500', Health: 'bg-emerald-500', 'Public Service': 'bg-indigo-500', Advisory: 'bg-amber-500', Livelihood: 'bg-teal-500', Governance: 'bg-purple-500' };

/** A time typed as text before (8:00 AM) as HH:MM; '' when it cannot be read. */
function toHm(t) {
    const m = /^(\d{1,2}):(\d{2})(?::\d{2})?\s*([AaPp][Mm])?$/.exec(String(t || '').trim());
    if (!m) return '';
    let h = Number(m[1]);
    const ap = (m[3] || '').toLowerCase();
    if (ap === 'pm' && h < 12) h += 12;
    if (ap === 'am' && h === 12) h = 0;
    return h > 23 ? '' : String(h).padStart(2, '0') + ':' + m[2];
}

/** '13:30' -> '1:30 PM'. */
function clock(hm) {
    const m = /^(\d{1,2}):(\d{2})/.exec(String(hm || ''));
    if (!m) return hm || '';
    const h = Number(m[1]);
    return (h % 12 || 12) + ':' + m[2] + (h < 12 ? ' AM' : ' PM');
}

// Registration and certificates (Esperanza round 2, 2026-10-30; owner: "the
// e-certifications also should be in the Event screen ... have also buttons
// for users to register to the posted events so that the municipality will
// have record of those who attended"). The event form sets who may register
// (and how many, until when) and the certificate those who attend receive:
// its title, its wording with :name, :event, :date and :venue filled in for
// each person by the server, and who signs it. The day itself - check-in,
// walk-ins, attendance and issuing - is the Registrations window
// (resources/js/event-desk.js).
const CERT_PRESETS = [
    { key: 'participation', title: 'Certificate of Participation', body: 'This is to certify that :name actively participated in :event held on :date at :venue.' },
    { key: 'attendance', title: 'Certificate of Attendance', body: 'This is to certify that :name attended :event held on :date at :venue.' },
    { key: 'completion', title: 'Certificate of Completion', body: 'This is to certify that :name has successfully completed :event held on :date at :venue.' },
];
const MAX_SIGNATORIES = 4;
let signatoryKey = 0;
const signatory = (s = {}) => ({ k: ++signatoryKey, name: s.name || '', position: s.position || '' });

/**
 * The form's registration and certificate fields, empty or read from an
 * event row (registration {open, capacity, closes_at, count, full},
 * certificate {enabled, title, body, signatories}). A closing time equal to
 * the event's start is the default, shown empty: it follows the event if
 * the date or time is moved.
 */
function eventExtras(e = null) {
    const reg = e?.registration || {};
    const cfg = e?.certificate || null;
    const start = e?.date ? String(e.date).slice(0, 10) + 'T' + (toHm(e.time) || '00:00') : '';
    const at = reg.closes_at ? manilaParts(reg.closes_at) : null;
    const closes = at && at.date ? at.date + 'T' + at.time : '';
    const preset = CERT_PRESETS.find((p) => p.title === cfg?.title);
    const sigs = Array.isArray(cfg?.signatories) && cfg.signatories.length ? cfg.signatories : [{}];
    return {
        // The setting, not whether one can register right now: the block's
        // open turns false once full or started, and saving would switch
        // registration off (round 2 review, 2026-10-30).
        registration_open: !!(e?.registration_open ?? reg.enabled ?? reg.open),
        capacity: reg.capacity ?? '',
        closes_at: closes && closes !== start ? closes : '',
        cert: {
            enabled: !!cfg?.enabled,
            had: !!cfg,
            preset: cfg ? (preset ? preset.key : 'custom') : CERT_PRESETS[0].key,
            title: cfg?.title || CERT_PRESETS[0].title,
            body: cfg?.body || CERT_PRESETS[0].body,
            signatories: sigs.map(signatory),
        },
    };
}

/** Those fields as the API takes them (create and update alike). */
function extrasPayload(f) {
    const c = f.cert;
    const cap = String(f.capacity ?? '').trim();
    return {
        registration_open: !!f.registration_open,
        capacity: cap === '' ? null : Number(cap),
        registration_closes_at: f.closes_at ? manilaIso(f.closes_at.slice(0, 10), f.closes_at.slice(11, 16)) : null,
        certificate: c.enabled || c.had ? {
            enabled: !!c.enabled,
            title: c.title.trim(),
            body: c.body.trim(),
            signatories: c.signatories.map((s) => ({ name: s.name.trim(), position: s.position.trim() })).filter((s) => s.name),
        } : null,
    };
}

/**
 * The Events tab (2026-10-21). An event reaches residents now: publishing it
 * tells the residents it is for (the bell and their phones) and, unless the
 * officer unticks "Post in Balita too", puts it in Balita with its photos.
 * A published event can still be put right by an officer who approves
 * events - residents are told of a new date, time or place - and one
 * withdrawn before its day is a cancellation, told with its reason.
 */
function adminEvents(barangays = []) {
    // A start and an end time (owner, Sprint 4 2026-11-06), "HH:MM" each;
    // the server writes the words residents read (`time`) from them.
    const blank = () => ({ name: '', description: '', category: 'Community', date: '', start_time: '', end_time: '', venue: '', audience: 'Municipal', barangay: barangays[0] || '', post_to_balita: true, ...eventExtras() });
    return {
        loading: true,
        failed: false,
        events: [],
        open: false,
        reviewOpen: false,
        cancelOpen: false,
        selected: null,
        rejectReason: '',
        cancelReason: '',
        search: '',
        status: 'All',
        scope: 'All',
        category: 'All',
        processing: false,
        statuses: ['Draft', 'Pending Review', 'Approved', 'Released', 'Rejected', 'Archived'],
        categoryColor: EVENT_CATEGORY_COLOR,
        editing: null,
        form: blank(),
        media: emptyMedia(),
        formKey: 1,
        certPresets: CERT_PRESETS,
        maxSignatories: MAX_SIGNATORIES,

        init() {
            // Live updates (2026-11-15): an event changed anywhere reloads the
            // list quietly once it has loaded, while its tab is shown and no
            // dialog (the event form, a review, a cancel) is open.
            this.$store.live.on(this.$el, 'balita', () => (this.loadedOnce ? this.load(true) : null), { busy: () => this.open || this.reviewOpen || this.cancelOpen || this.processing });
        },
        loadedOnce: false,
        // quiet (a live update): the list on screen stays until the new one
        // comes; a failed reload keeps it.
        async load(quiet = false) {
            if (!quiet) {
                this.loading = true;
                this.failed = false;
            }
            try {
                const res = await api.getAll('/admin/events');
                this.events = res.data || [];
                this.failed = false;
                this.loadedOnce = true;
            } catch {
                if (!quiet) this.failed = true;
            } finally {
                this.loading = false;
            }
            if (!quiet) this.openLinked();
        },
        // A reviewer's notification opens /admin/communications/?tab=events&event=KEY,
        // once (the list reloads after every decision).
        linkedDone: false,
        openLinked() {
            const params = new URLSearchParams(window.location.search);
            if (this.linkedDone || params.get('tab') !== 'events' || !params.get('event')) return;
            const e = this.events.find((x) => x.key === params.get('event'));
            if (!e) return;
            this.linkedDone = true;
            if (e.status === 'Pending Review' && this.canMove(e, 'Approved')) this.openReview(e);
            else this.search = e.title || e.name;
        },
        get categories() { return [...new Set(this.events.map((e) => e.category).filter(Boolean))].sort(); },
        get filtered() {
            const q = this.search.trim().toLowerCase();
            return this.events.filter((e) =>
                (!e.barangay || this.$store.session.inScope(e.barangay))
                && (this.status === 'All' || e.status === this.status)
                && (this.scope === 'All' || (this.scope === 'Municipal' ? !e.barangay : e.barangay === this.scope))
                && (this.category === 'All' || e.category === this.category)
                && (!q || (e.title || e.name || '').toLowerCase().includes(q)));
        },
        count(status) { return this.events.filter((e) => e.status === status).length; },
        can(action) { return this.$store.session.canPerm('comm_events', action); },
        canMove(e, to) {
            const action = e.available_transitions?.[to];
            return !!action && this.can(action);
        },
        // In the editorial flow, or published and put right by one who approves events.
        canEdit(e) {
            return (['Draft', 'Pending Review', 'Rejected'].includes(e.status) && this.can('edit'))
                || (e.status === 'Released' && this.can('approve'));
        },
        // Withdrawing a published event before its day cancels it, and residents are told.
        isCancel(e) { return e.status === 'Released' && !!e.upcoming; },
        dayLabel(d) {
            return d ? new Date(d + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : '';
        },

        openNew() {
            this.editing = null;
            this.timeBase = { start: '', end: '' };
            this.legacyTime = '';
            this.form = blank();
            this.media = emptyMedia();
            this.formKey += 1;
            this.open = true;
        },
        // A time typed as text before (8:00 AM) for the time field; one it
        // cannot read leaves the field empty and is kept as it was.
        toTime(t) { return toHm(t); },
        // The times the event had when the form opened: only a change is sent.
        timeBase: { start: '', end: '' },
        // An older event's time in words that no picker holds ("Whole day",
        // "8 AM onwards" before the server read it): shown above the pickers,
        // and kept unless a start time is set.
        legacyTime: '',
        openEdit(e) {
            const start = toHm(e.start_time) || (e.start_time == null ? this.toTime(e.time) : '');
            const end = toHm(e.end_time);
            this.timeBase = { start, end };
            this.legacyTime = !start && e.time ? String(e.time) : '';
            this.form = {
                name: e.title || e.name || '', description: e.description || '', category: e.category || 'Community',
                date: e.date || '', start_time: start, end_time: end, venue: e.venue || '',
                audience: e.barangay ? 'Barangay' : 'Municipal', barangay: e.barangay || barangays[0] || '',
                post_to_balita: e.post_to_balita !== false,
                ...eventExtras(e),
            };
            this.media = emptyMedia();
            this.editing = e;
            this.formKey += 1;
            this.open = true;
        },
        get mediaProblem() {
            if (this.media.busy) return 'Wait for the photos to finish uploading.';
            if (this.media.failed) return 'A photo did not upload. Remove it or try it again.';
            return '';
        },
        // ---- Registration and certificate (round 2, 2026-10-30)
        /** '' when registration and the certificate are ready to save, else what to put right. */
        /** '' when the times are right: an end needs a start and comes after it. */
        timeProblem() {
            const f = this.form;
            if (f.end_time && !f.start_time) return 'Set a start time before the end time.';
            if (f.start_time && f.end_time && f.end_time <= f.start_time) return 'The end time must be after the start time.';
            return '';
        },
        extrasProblem() {
            const f = this.form;
            if (f.registration_open) {
                const cap = String(f.capacity ?? '').trim();
                if (cap !== '' && !(/^\d+$/.test(cap) && Number(cap) >= 1)) return 'Capacity is a whole number of people, or empty for no limit.';
                if (f.closes_at && f.date && f.closes_at > f.date + 'T' + (f.start_time || '23:59')) return 'Registration has to close before the event starts.';
            }
            const c = f.cert;
            if (c.enabled) {
                if (c.title.trim().length < 3) return 'Give the certificate a title.';
                if (!/:name\b/.test(c.body)) return 'The certificate wording needs :name, where each person’s name goes.';
                if (!c.signatories.some((s) => s.name.trim())) return 'Name at least one person who signs the certificate.';
                if (c.signatories.some((s) => s.name.trim() && !s.position.trim())) return 'Write the position of each person who signs.';
            }
            return '';
        },
        capacityHint() {
            const n = this.editing?.registration?.count || 0;
            const cap = Number(this.form.capacity);
            if (n && cap && cap < n) return n + ' have registered already. No one is removed, but no one new can register.';
            return n ? n + (n === 1 ? ' resident has' : ' residents have') + ' registered so far.' : 'Empty for no limit.';
        },
        closesHint() {
            const f = this.form;
            if (f.closes_at) return 'Residents can register until ' + this.dayLabel(f.closes_at.slice(0, 10)) + ', ' + clock(f.closes_at.slice(11, 16)) + '.';
            return 'Empty: until the event starts' + (f.date ? ' (' + this.dayLabel(f.date) + (f.start_time ? ', ' + clock(f.start_time) : '') + ')' : '') + '.';
        },
        /** A preset's title, and its wording too while the wording is still a preset's own. */
        certPreset(key) {
            const c = this.form.cert;
            const p = CERT_PRESETS.find((x) => x.key === key);
            const stock = !c.body.trim() || CERT_PRESETS.some((x) => x.body === c.body.trim());
            if (p) {
                c.title = p.title;
                if (stock) c.body = p.body;
            } else if (CERT_PRESETS.some((x) => x.title === c.title)) {
                c.title = '';
            }
        },
        addSignatory() {
            if (this.form.cert.signatories.length < MAX_SIGNATORIES) this.form.cert.signatories.push(signatory());
        },
        removeSignatory(s) {
            const list = this.form.cert.signatories;
            if (list.length > 1) list.splice(list.indexOf(s), 1);
        },
        /** The wording as one attendee will read it. */
        certSample() {
            const f = this.form;
            const date = f.date ? new Date(f.date + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '(the date)';
            return f.cert.body
                .replace(/:names?\b/g, 'Juan Dela Cruz')
                .replace(/:event\b/g, f.name.trim() || '(the event)')
                .replace(/:date\b/g, date)
                .replace(/:venue\b/g, f.venue.trim() || '(the venue)');
        },

        // ---- On the card
        /** '12 of 50 registered', 'Full: 50 of 50 registered', with who attended once known. */
        regChip(e) {
            const r = e.registration || {};
            const n = r.count || 0;
            const text = ((r.enabled ?? r.open) && r.full ? 'Full: ' : '') + n + (r.capacity ? ' of ' + r.capacity : '') + ' registered';
            return text + (e.attended_count ? ' · ' + e.attended_count + ' attended' : '');
        },
        regTone(e) {
            const r = e.registration || {};
            if ((r.enabled ?? r.open) && r.full) return 'bg-amber-50 text-amber-800';
            return r.open ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600';
        },
        /** Registrations and attendance, for a published event (or an archived one that had some). */
        canDesk(e) {
            if (!this.can('edit') && !this.can('approve')) return false;
            if (e.status === 'Released') return true;
            return e.status === 'Archived' && ((e.registration?.count || 0) + (e.attended_count || 0)) > 0;
        },
        // On window: the Registrations window is a component of its own
        // (eventDesk), and it asks for Edit back the same way (events-edit).
        openDesk(e) { window.dispatchEvent(new CustomEvent('event-desk', { detail: e })); },
        editByKey(key) {
            const e = this.events.find((x) => x.key === key);
            if (e && this.canEdit(e)) this.openEdit(e);
        },

        async saveEvent(submit) {
            const f = this.form;
            if (this.processing || !f.name.trim() || !f.date) return;
            if (this.mediaProblem) { balitaToast(this.mediaProblem, 'error'); return; }
            if (this.timeProblem()) { balitaToast(this.timeProblem(), 'error'); return; }
            const problem = this.extrasProblem();
            if (problem) { balitaToast(problem, 'error'); return; }
            return this.editing ? this.saveEdit(submit) : this.create(submit);
        },
        async create(submit) {
            const f = this.form;
            this.processing = true;
            try {
                const key = f.name.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 60) + '-' + Date.now().toString(36);
                await api.post('/admin/events', {
                    key, name: f.name.trim(), title: f.name.trim(), description: f.description.trim() || null, date: f.date,
                    // `time` for a server from before the start and end times; a
                    // newer one writes it from start_time and end_time.
                    time: f.start_time || null, start_time: f.start_time || null, end_time: f.start_time ? (f.end_time || null) : null,
                    venue: f.venue.trim() || null, category: f.category,
                    barangay: f.audience === 'Barangay' ? f.barangay : null,
                    post_to_balita: !!f.post_to_balita, media_ids: this.media.ids,
                    ...extrasPayload(f),
                });
                if (submit) await api.post('/admin/events/' + encodeURIComponent(key) + '/transition', { to: 'Pending Review' });
                this.open = false;
                balitaToast(submit ? 'Submitted for review.' : 'Saved as draft.');
                await this.load();
            } catch (err) {
                balitaToast(errText(err, 'Could not create the event.'), 'error');
            } finally {
                this.processing = false;
            }
        },
        // Only what changed goes; each step that succeeds is kept on the
        // event, so trying again after a failure repeats only what is left.
        async saveEdit(submit) {
            const e = this.editing;
            const f = this.form;
            const barangay = f.audience === 'Barangay' ? f.barangay : null;
            const changes = {};
            if (f.name.trim() !== (e.title || e.name)) { changes.name = f.name.trim(); changes.title = f.name.trim(); }
            if (f.description.trim() !== (e.description || '')) changes.description = f.description.trim() || null;
            if (f.date !== e.date) changes.date = f.date;
            // Both times go when either changed: the server checks one against the other.
            if (f.start_time !== this.timeBase.start || f.end_time !== this.timeBase.end) {
                changes.start_time = f.start_time || null;
                changes.end_time = f.start_time ? (f.end_time || null) : null;
                // Cleared, an older event's words stay (null clears them only when they were the old start).
                changes.time = f.start_time || (this.legacyTime || null);
            }
            if (f.venue.trim() !== (e.venue || '')) changes.venue = f.venue.trim() || null;
            if (f.category !== (e.category || 'Community')) changes.category = f.category;
            if (barangay !== (e.barangay || null)) changes.barangay = barangay;
            if (!!f.post_to_balita !== (e.post_to_balita !== false)) changes.post_to_balita = !!f.post_to_balita;
            if (!sameIds(this.media.ids, (e.media || []).map((m) => m.id))) changes.media_ids = this.media.ids;
            // Registration and certificate: each field that differs from the event's own.
            const was = extrasPayload(eventExtras(e));
            Object.entries(extrasPayload(f)).forEach(([k, v]) => { if (JSON.stringify(v) !== JSON.stringify(was[k])) changes[k] = v; });
            if (!submit && !Object.keys(changes).length) {
                this.open = false;
                this.editing = null;
                balitaToast('Nothing was changed.', 'info');
                return;
            }
            this.processing = true;
            try {
                const path = '/admin/events/' + encodeURIComponent(e.key);
                if (Object.keys(changes).length) { await api.put(path, changes); Object.assign(e, changes); }
                if (submit && e.status === 'Rejected') { await api.post(path + '/transition', { to: 'Draft' }); e.status = 'Draft'; }
                if (submit) { await api.post(path + '/transition', { to: 'Pending Review' }); e.status = 'Pending Review'; }
                this.open = false;
                this.editing = null;
                const told = e.status === 'Released' && e.upcoming && ['date', 'time', 'start_time', 'end_time', 'venue'].some((k) => k in changes);
                balitaToast(submit ? 'Saved and submitted for review.' : (told ? 'Saved. Residents were told of the change.' : 'Changes saved.'));
                await this.load();
            } catch (err) {
                balitaToast(errText(err, 'Could not save the changes.'), 'error');
            } finally {
                this.processing = false;
            }
        },
        async transition(e, to, remarks) {
            try {
                await api.post('/admin/events/' + encodeURIComponent(e.key) + '/transition', { to, remarks: remarks || undefined });
                return true;
            } catch (err) {
                balitaToast(errText(err, 'Could not update this event.'), 'error');
                return false;
            }
        },
        async submit(e) {
            if (await this.transition(e, 'Pending Review')) { balitaToast('Submitted for review.'); await this.load(); }
        },
        openReview(e) {
            this.selected = e;
            this.rejectReason = '';
            this.reviewOpen = true;
        },
        // Approved and published in one go; a publish that fails leaves it
        // Approved, with Publish on its card.
        async approveAndPublish() {
            if (this.processing) return;
            this.processing = true;
            if (await this.transition(this.selected, 'Approved')) {
                const live = await this.transition(this.selected, 'Released');
                this.reviewOpen = false;
                balitaToast(live ? 'Published. Residents were told.' : 'Approved, but not yet published. Use Publish on its card.', live ? 'success' : 'error');
                await this.load();
            }
            this.processing = false;
        },
        async requestChanges() {
            if (this.processing || this.rejectReason.trim().length < 5) return;
            this.processing = true;
            if (await this.transition(this.selected, 'Rejected', this.rejectReason.trim())) {
                this.reviewOpen = false;
                this.rejectReason = '';
                balitaToast('Sent back with remarks.');
                await this.load();
            }
            this.processing = false;
        },
        async release(e) {
            if (await this.transition(e, 'Released')) { balitaToast('Published. Residents were told.'); await this.load(); }
        },
        // Events are archived, never deleted (owner, 2026-11-14: "archives
        // only no delete"); an archived event stays under Archived.
        async archive(e) {
            if (await this.transition(e, 'Archived')) { balitaToast('Archived.'); await this.load(); }
        },
        openCancel(e) {
            this.selected = e;
            this.cancelReason = '';
            this.cancelOpen = true;
        },
        async confirmCancel() {
            if (this.processing || !this.selected) return;
            this.processing = true;
            if (await this.transition(this.selected, 'Archived', this.cancelReason.trim())) {
                this.cancelOpen = false;
                balitaToast('Event cancelled. Residents were told.');
                await this.load();
            }
            this.processing = false;
        },
    };
}

export function register(Alpine) {
    registerBalitaMedia(Alpine);
    registerOccasions(Alpine);
    Alpine.data('adminBalita', adminBalita);
    Alpine.data('adminEvents', adminEvents);
    Alpine.data('balitaComments', balitaComments);
    Alpine.data('balitaSettings', balitaSettings);
    // A correction request opened from the Data requests list: the account's
    // "Correct details" opens by itself, with that request to close.
    Alpine.store('recordsDesk', { pendingCorrection: null });

    // ---------------------------------------------------------------------
    // Residents' data requests (to see, correct, erase or get a copy of
    // their data), the second view of the To Verify tab. Wraps the whole tab
    // so the switch can show either view; the existing markup inside keeps
    // reading the page's state.
    Alpine.data('dataRequests', () => ({
        drView: 'accounts',
        drOpenCount: null,
        drRows: [],
        drLoading: false,
        drFailed: false,
        drLoaded: false,
        drFilter: 'open',
        drKind: '',
        drSearch: '',
        drPage: 1,
        drLastPage: 1,
        drMoreLoading: false,
        drSeq: 0,
        drTimer: null,
        drSel: null,
        drOutcome: 'Completed',
        drNote: '',
        drBusy: false,
        drError: '',
        drKinds: DR_KINDS,
        drOutcomes: [
            ['Completed', 'Done. Say what was done.'],
            ['Rejected', 'Declined. Say why.'],
            ['Under Review', 'Still checking. Say what happens next.'],
        ],

        get drMay() { return this.$store.session.canPerm('const_citizen_verification', 'view'); },
        get drMayResolve() { return this.$store.session.canPerm('const_citizen_verification', 'verify'); },

        async init() {
            this.$watch('drSearch', () => {
                clearTimeout(this.drTimer);
                this.drTimer = setTimeout(() => this.drReload(), 350);
            });
            this.$watch('drKind', () => this.drReload());
            await this.$store.session.whenReady();
            if (this.drMay) this.drCount();
        },

        when,
        drTone: tone,
        drDot: dot,
        drKindLabel(kind) { return DR_KINDS[kind] || humanize(kind); },
        drReasonLabel(r) { return (NAME_REASONS[r] || [humanize(r)])[0]; },
        drProofHint(r) { return (NAME_REASONS[r] || [])[1] || ''; },
        drNewName(r) { return fullName(r?.requested); },
        drIsOpen(r) { return OPEN.includes(r?.status); },

        drShow(view) {
            this.drView = view;
            if (view === 'requests' && !this.drLoaded && !this.drLoading) this.drLoad();
        },
        drSetFilter(f) {
            if (this.drFilter === f) return;
            this.drFilter = f;
            this.drReload();
        },
        // Filters reload the list once it has been looked at.
        drReload() { if (this.drLoaded || this.drView === 'requests') this.drLoad(); },

        async drCount() {
            try {
                const res = await api.get('/admin/data-requests', { params: { open: 1, per_page: 1 } });
                this.drOpenCount = res.meta?.total ?? 0;
            } catch {
                // The switch then shows no count.
            }
        },

        drParams(page) {
            const p = { page, per_page: 25 };
            if (this.drFilter === 'open') p.open = 1;
            else if (this.drFilter === 'closed') p.status = 'Completed,Rejected';
            if (this.drKind) p.kind = this.drKind;
            const q = this.drSearch.trim();
            if (q) p.q = q;
            return p;
        },

        async drLoad() {
            const seq = ++this.drSeq;
            this.drLoading = true;
            this.drFailed = false;
            try {
                const res = await api.get('/admin/data-requests', { params: this.drParams(1) });
                if (seq !== this.drSeq) return;
                this.drRows = res.data || [];
                this.drPage = 1;
                this.drLastPage = res.meta?.last_page || 1;
                if (this.drFilter === 'open' && !this.drKind && !this.drSearch.trim()) this.drOpenCount = res.meta?.total ?? this.drRows.length;
                this.drLoaded = true;
            } catch {
                if (seq === this.drSeq) this.drFailed = true;
            } finally {
                if (seq === this.drSeq) this.drLoading = false;
            }
        },

        async drMore() {
            if (this.drMoreLoading || this.drPage >= this.drLastPage) return;
            const seq = this.drSeq;
            this.drMoreLoading = true;
            try {
                const res = await api.get('/admin/data-requests', { params: this.drParams(this.drPage + 1) });
                if (seq !== this.drSeq) return;
                const seen = new Set(this.drRows.map((r) => r.ref));
                this.drRows = [...this.drRows, ...(res.data || []).filter((r) => !seen.has(r.ref))];
                this.drPage += 1;
                this.drLastPage = res.meta?.last_page || this.drLastPage;
            } catch (err) {
                fail(err, 'Could not load more data requests.');
            } finally {
                this.drMoreLoading = false;
            }
        },

        drRefresh() {
            this.drCount();
            if (this.drLoaded) this.drLoad();
        },

        drPick(r) {
            this.drSel = r;
            this.drOutcome = 'Completed';
            this.drNote = '';
            this.drError = '';
        },

        async drResolve() {
            const r = this.drSel;
            const note = this.drNote.trim();
            if (!r || this.drBusy || note.length < 5) return;
            this.drBusy = true;
            this.drError = '';
            const outcome = this.drOutcome;
            try {
                const res = await api.post('/admin/data-requests/' + encodeURIComponent(r.ref) + '/resolve', { status: outcome, resolution: note });
                const row = { ...r, ...(res.data || {}), handled_by: this.$store.session.account?.name || r.handled_by };
                this.drRows = this.drRows.map((x) => (x.ref === r.ref ? row : x));
                this.drSel = null;
                toast({
                    Completed: 'Marked done. The resident was told.',
                    Rejected: 'Declined. The resident was told why.',
                    'Under Review': 'Marked as being checked. The resident was told.',
                }[outcome]);
                this.drRefresh();
            } catch (err) {
                if (err?.code === 'ALREADY_RESOLVED') {
                    // Someone else closed it meanwhile: show the list as it is now.
                    this.drSel = null;
                    toast('This request was already resolved by someone else. The list is up to date now.', 'warning');
                    this.drRefresh();
                } else {
                    // The note stays, to send again.
                    this.drError = errText(err, 'Could not save this.');
                }
            } finally {
                this.drBusy = false;
            }
        },

        // The account the request is about, in the account review. An open
        // correction request opens the account's "Correct details" with it; an
        // open change of name, with the new name and its reason filled in.
        drOpenAccount(r) {
            const no = r?.citizen?.account_no;
            if (!no) return;
            const row = (this.citizenAccountList || []).find((i) => i.citizen_account?.account_no === no)
                || { ref: no, name: r.citizen.name, barangay: r.citizen.barangay, tags: [], citizen_account: { account_no: no }, accountOnly: true };
            let hint = null;
            if (this.drIsOpen(r) && r.kind === 'correction') hint = { account: no, ref: r.ref };
            if (this.drIsOpen(r) && r.kind === 'name_change' && r.requested) {
                hint = {
                    account: no,
                    ref: r.ref,
                    prefill: { first_name: r.requested.first_name || '', middle_name: r.requested.middle_name || '', last_name: r.requested.last_name || '', suffix: r.requested.suffix || '' },
                    reason: 'Name change (' + this.drReasonLabel(r.reason).toLowerCase() + '): ' + (r.current_name || r.citizen?.name || '') + ' to ' + this.drNewName(r) + '. Proof checked.',
                };
            }
            this.$store.recordsDesk.pendingCorrection = hint;
            this.drSel = null;
            this.openAccount(row);
        },
    }));

    // ---------------------------------------------------------------------
    // Correcting a verified account's locked details (name, birth date, sex,
    // barangay) with a reason, in the account review. The resident cannot
    // change them once the account is approved.
    Alpine.data('identityCorrection', () => ({
        icFor: null,
        icOpen: false,
        icForm: { first_name: '', middle_name: '', last_name: '', suffix: '', birthdate: '', sex: '', barangay: '', reason: '', data_request_ref: '' },
        icNameKnown: true,
        icSexFrom: '',
        icBusy: false,
        icError: '',
        icDone: null,
        icRequests: [],
        when,

        get icMay() { return this.$store.session.canPerm('const_citizen_verification', 'verify'); },
        get icLocked() { return this.accountDetail?.status === 'Approved'; },
        // The latest identity check found the ID saying something else than
        // the account (2026-10-16): the account can be corrected to match it,
        // approved or not.
        get icCheck() { return this.accountDetail?.identity?.checks?.[0] || null; },
        // Read against the account as it is now: a corrected account no longer differs.
        get icMismatch() { return (this.icCheck?.comparison || []).filter((r) => r.matches === false).map((r) => r.field); },
        get icHistory() { return this.accountDetail?.name_history || []; },
        get icToday() { return new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' }); },
        get icSexHint() {
            if (this.icSexFrom === 'record') return 'The account does not show its sex here: this is the registry record’s. Check it against the ID.';
            if (!this.icForm.sex) return 'Choose the sex shown on the ID.';
            return '';
        },

        init() {
            this.$watch('accountDetail?.account_no', (no) => this.icSync(no));
            this.icSync(this.accountDetail?.account_no);
        },

        icSync(no) {
            if ((no || null) === this.icFor) return;
            this.icFor = no || null;
            this.icOpen = false;
            this.icError = '';
            this.icDone = null;
            this.icRequests = [];
            // The hint is for the next account to open: used there, or dropped
            // (that account failed to open and another was opened instead).
            const hint = this.$store.recordsDesk.pendingCorrection;
            if (no && hint) {
                this.$store.recordsDesk.pendingCorrection = null;
                if (hint.account === no && (this.icLocked || this.icMismatch.length) && this.icMay) this.icStart(hint.ref, hint.prefill, hint.reason);
            }
        },

        // "Make the account match the ID": the correction form, filled in
        // from what the ID says; the officer checks it and saves.
        icMatchId() {
            const id = this.icCheck?.account_from_id;
            if (!id) return;
            const prefill = {};
            ['first_name', 'middle_name', 'last_name', 'birthdate', 'sex'].forEach((k) => { if (id[k]) prefill[k] = id[k]; });
            this.icStart(null, prefill, 'Matched to the verified ID (' + (this.icCheck.extracted?.document_type || 'ID') + (this.icCheck.extracted?.document_number_last4 ? ' ending ' + this.icCheck.extracted.document_number_last4 : '') + '), checked by the officer.');
            this.$nextTick(() => this.$root?.scrollIntoView?.({ behavior: 'smooth', block: 'start' }));
        },

        // Where the account's own sex can be read: the registry comparison
        // carries the account's value; otherwise the linked registry record's.
        icAccountSex() {
            // The account's own, which its detail carries; an account with no
            // registry record had none to fall back on (2026-10-16).
            if (normSex(this.accountDetail?.sex)) return { value: normSex(this.accountDetail.sex), from: 'account' };
            const m = this.registryMatch;
            const fields = [m?.record?.comparison, ...(m?.candidates || []).map((c) => c.comparison)].filter(Array.isArray).flat();
            const own = fields.find((f) => f?.field === 'sex' && f.account);
            if (own && normSex(own.account)) return { value: normSex(own.account), from: 'account' };
            const row = this.accountRow;
            if (row && !row.accountOnly && normSex(row.sex)) return { value: normSex(row.sex), from: 'record' };
            return { value: '', from: '' };
        },

        icStart(ref = null, prefill = null, reason = '') {
            const d = this.accountDetail || {};
            const parts = splitName(d.name, d.first_name, d.last_name);
            const sex = this.icAccountSex();
            this.icForm = {
                first_name: d.first_name || '',
                middle_name: d.middle_name ?? parts.middle,
                last_name: d.last_name || '',
                suffix: d.suffix ?? parts.suffix,
                birthdate: d.birthdate || '',
                sex: sex.value,
                barangay: d.barangay || '',
                reason: reason || '',
                data_request_ref: ref || '',
                ...(prefill || {}),
            };
            this.icNameKnown = parts.known;
            this.icSexFrom = sex.from;
            this.icError = '';
            this.icDone = null;
            this.icOpen = true;
            this.icLoadRequests(ref);
        },

        // The account's open correction requests, one of which the save can close.
        async icLoadRequests(ref) {
            const no = this.icFor;
            if (!no || !this.$store.session.canPerm('const_citizen_verification', 'view')) return;
            try {
                const res = await api.get('/admin/data-requests', { params: { open: 1, kind: 'correction,name_change', q: no, per_page: 20 } });
                if (this.icFor !== no) return;
                const list = (res.data || []).filter((r) => r.citizen?.account_no === no);
                if (ref && !list.some((r) => r.ref === ref)) list.unshift({ ref, detail: '' });
                this.icRequests = list;
            } catch {
                this.icRequests = ref ? [{ ref, detail: '' }] : [];
            }
        },

        async icSave() {
            const f = this.icForm;
            const no = this.icFor;
            if (!no || this.icBusy) return;
            if (!f.first_name.trim() || !f.last_name.trim() || !f.birthdate || !f.sex || !f.barangay) {
                this.icError = 'Fill in the first and last name, the birth date, the sex and the barangay.';
                return;
            }
            if (f.reason.trim().length < 10) {
                this.icError = 'Give the reason in at least 10 characters.';
                return;
            }
            this.icBusy = true;
            this.icError = '';
            const payload = {
                first_name: f.first_name.trim(),
                middle_name: f.middle_name.trim() || null,
                last_name: f.last_name.trim(),
                suffix: f.suffix.trim() || null,
                birthdate: f.birthdate,
                sex: f.sex,
                barangay: f.barangay,
                reason: f.reason.trim(),
                data_request_ref: f.data_request_ref || null,
            };
            try {
                const res = await api.put('/admin/citizens/' + encodeURIComponent(no) + '/identity', payload);
                const before = res.data?.before || {};
                const after = res.data?.after || {};
                this.icDone = Object.keys(IDENTITY_FIELDS)
                    .filter((k) => String(before[k] ?? '') !== String(after[k] ?? ''))
                    .map((k) => ({ label: IDENTITY_FIELDS[k], from: shown(before[k]), to: shown(after[k]) }));
                this.icOpen = false;
                toast((payload.data_request_ref ? 'Details corrected and ' + payload.data_request_ref + ' closed. The resident was told why.' : 'Details corrected. The resident was told why.')
                    + (res.data?.identity_verified_now ? ' The account now matches the ID, so the identity is verified.' : ''));
                // Both refreshed: the account here, and the data requests list.
                window.dispatchEvent(new CustomEvent('data-requests-changed'));
                await this.icRefresh(no);
            } catch (err) {
                // The form keeps what was typed.
                if (err?.code === 'NO_CHANGE') this.icError = 'Nothing was changed: the account has these details already.';
                else this.icError = errText(err, 'Could not correct the details.');
            } finally {
                this.icBusy = false;
            }
        },

        async icRefresh(no) {
            try {
                const res = await api.get('/admin/citizens/' + encodeURIComponent(no));
                if (this.accountDetail?.account_no !== no) return;
                const d = res.data || {};
                this.accountDetail = d;
                const row = this.accountRow;
                if (row?.citizen_account?.account_no === no) {
                    row.citizen_account = { ...row.citizen_account, name: d.name, first_name: d.first_name, last_name: d.last_name, barangay: d.barangay };
                    if (row.accountOnly) { row.name = d.name; row.barangay = d.barangay; }
                }
            } catch {
                // The review keeps what it showed; reopening it reads it again.
            }
        },
    }));

    // ---------------------------------------------------------------------
    // What a resident was sent: every notification, newest first, with how
    // each email or SMS went (addresses masked by the server).
    Alpine.data('citizenMessages', () => ({
        cmFor: null,
        cmOpen: false,
        cmRows: [],
        cmLoading: false,
        cmFailed: false,
        cmPage: 1,
        cmLastPage: 1,
        cmTotal: 0,
        cmMore: false,

        init() {
            this.$watch('accountDetail?.account_no', (no) => this.cmSync(no));
            this.cmSync(this.accountDetail?.account_no);
        },

        when,
        cmSync(no) {
            if ((no || null) === this.cmFor) return;
            this.cmFor = no || null;
            this.cmOpen = false;
            this.cmRows = [];
            this.cmFailed = false;
            this.cmLoading = false;
            this.cmPage = 1;
            this.cmLastPage = 1;
            this.cmTotal = 0;
        },
        cmToggle() {
            this.cmOpen = !this.cmOpen;
            if (this.cmOpen && !this.cmRows.length && !this.cmLoading) this.cmLoad(1);
        },

        async cmLoad(page = 1) {
            const no = this.cmFor;
            if (!no) return;
            if (page === 1) { this.cmLoading = true; this.cmFailed = false; } else { this.cmMore = true; }
            try {
                const res = await api.get('/admin/citizens/' + encodeURIComponent(no) + '/messages', { params: { page, per_page: 20 } });
                if (this.cmFor !== no) return;
                this.cmRows = page === 1 ? (res.data || []) : [...this.cmRows, ...(res.data || [])];
                this.cmPage = page;
                this.cmLastPage = res.meta?.last_page || 1;
                this.cmTotal = res.meta?.total ?? this.cmRows.length;
            } catch (err) {
                if (this.cmFor !== no) return;
                if (page === 1) this.cmFailed = true; else fail(err, 'Could not load older messages.');
            } finally {
                this.cmLoading = false;
                this.cmMore = false;
            }
        },

        cmChannel(d) { return { in_app: 'Portal and app', email: 'Email', sms: 'SMS' }[d?.channel] || humanize(d?.channel); },
        cmState(d) { return { Released: 'Sent', 'Pending Review': 'Waiting to send', Rejected: 'Failed', Cancelled: 'Not sent' }[d?.status] || d?.status || ''; },
        cmLabel(d) { return this.cmChannel(d) + (d?.to ? ' ' + d.to : '') + ': ' + this.cmState(d); },
        cmTone(d) {
            return {
                Released: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'Pending Review': 'bg-amber-50 text-amber-700 ring-amber-200',
                Rejected: 'bg-rose-50 text-rose-700 ring-rose-200',
            }[d?.status] || 'bg-slate-50 text-slate-600 ring-slate-200';
        },
        cmProblems(m) { return (m?.deliveries || []).filter((d) => d.error && d.status !== 'Released'); },
    }));

    // ---------------------------------------------------------------------
    // A resident record's change history, from the audit log: who, when,
    // what, and each field before and after. Sensitive values come masked
    // from the server for a role without view_sensitive; shown as given.
    Alpine.data('residentHistory', () => ({
        rhFor: null,
        rhOpen: false,
        rhRows: [],
        rhLoading: false,
        rhFailed: false,
        rhSensitive: true,

        init() {
            this.$watch('selectedIndividual?.ref', (ref) => this.rhSync(ref));
            this.$watch('showIndividualDetail', (v) => { if (v) this.rhReset(); });
            // An edit saved in this window is the newest change: show it.
            this.$watch('editing', (v) => { if (!v && this.rhOpen) this.rhLoad(); });
            this.rhSync(this.selectedIndividual?.ref);
        },

        when,
        rhSync(ref) {
            if ((ref || null) === this.rhFor) return;
            this.rhFor = ref || null;
            this.rhReset();
        },
        rhReset() {
            this.rhOpen = false;
            this.rhRows = [];
            this.rhFailed = false;
            this.rhLoading = false;
        },
        rhToggle() {
            this.rhOpen = !this.rhOpen;
            if (this.rhOpen && !this.rhRows.length && !this.rhLoading) this.rhLoad();
        },

        async rhLoad() {
            const ref = this.rhFor;
            if (!ref) return;
            this.rhLoading = true;
            this.rhFailed = false;
            try {
                const res = await api.get('/admin/residents/' + encodeURIComponent(ref) + '/history');
                if (this.rhFor !== ref) return;
                this.rhRows = res.data || [];
                this.rhSensitive = res.meta?.sensitive_shown !== false;
            } catch {
                if (this.rhFor === ref) this.rhFailed = true;
            } finally {
                if (this.rhFor === ref) this.rhLoading = false;
            }
        },

        rhAction(action) {
            if (HISTORY_ACTIONS[action]) return HISTORY_ACTIONS[action];
            return humanize(String(action || '').split('.').pop()) || 'Changed';
        },
        rhField(field) { return HISTORY_FIELDS[field] || humanize(field); },
        rhValue: shown,
    }));

    // ---------------------------------------------------------------------
    // A resident's guardian (welfare round 2026-10-29), on the individual
    // detail: what the server resolved (the office's own entry, else the ID
    // application's, else a minor's household adult) and, for an officer
    // with const_resident edit, setting or clearing the office's entry.
    // Markup: admin/constituents/section-guardian.blade.php.
    Alpine.data('residentGuardian', () => ({
        gdOpen: false,
        gdMode: 'typed',
        gdForm: { name: '', relationship: '', contact: '', address: '' },
        gdQuery: '',
        gdResults: [],
        gdSearching: false,
        gdSearched: false,
        gdPicked: null,
        gdBusy: false,
        gdErrors: {},
        gdConfirmClear: false,
        gdSeq: 0,

        init() {
            // Another resident opened in the same window starts clean.
            this.$watch('selectedIndividual?.ref', () => { this.gdOpen = false; this.gdConfirmClear = false; });
        },
        get gdCurrent() { return this.selectedIndividual?.guardian || null; },
        get gdMayEdit() { return this.$store.session.canPerm('const_resident', 'edit'); },
        gdTel: telHref,
        get gdSourceLine() {
            const g = this.gdCurrent;
            if (!g) return '';
            const where = GUARDIAN_SOURCES[g.source]?.en || '';
            const who = g.source === 'record' && (g.updated_by || g.updated_at)
                ? ' · ' + [g.updated_by, g.updated_at ? when(g.updated_at) : null].filter(Boolean).join(', ')
                : '';
            return where + who;
        },
        get gdReady() {
            if (!this.gdForm.relationship.trim()) return false;
            return this.gdMode === 'linked' ? !!this.gdPicked : this.gdForm.name.trim().length >= 2;
        },
        gdError(key) {
            const v = this.gdErrors?.[key];
            return Array.isArray(v) ? (v[0] || '') : (v || '');
        },
        gdStart() {
            const g = this.gdCurrent?.source === 'record' ? this.gdCurrent : null;
            this.gdMode = g?.individual_ref ? 'linked' : 'typed';
            this.gdPicked = g?.individual_ref ? { ref: g.individual_ref, name: g.name } : null;
            this.gdForm = { name: g?.name || '', relationship: g?.relationship || '', contact: g?.contact || '', address: g?.address || '' };
            this.gdQuery = '';
            this.gdResults = [];
            this.gdSearched = false;
            this.gdErrors = {};
            this.gdConfirmClear = false;
            this.gdOpen = true;
            this.$nextTick(() => document.getElementById(this.gdMode === 'linked' ? 'gd-relationship' : 'gd-name')?.focus());
        },
        // The registry, searched the way the Individuals list searches it.
        async gdSearch() {
            const q = this.gdQuery.trim();
            const seq = ++this.gdSeq;
            if (q.length < 2) { this.gdResults = []; this.gdSearched = false; return; }
            this.gdSearching = true;
            try {
                const res = await api.get('/admin/residents', { params: { q, per_page: 8 } });
                if (seq !== this.gdSeq) return;
                this.gdResults = (res.data || []).filter((r) => r.ref !== this.selectedIndividual?.ref);
                this.gdSearched = true;
            } catch (err) {
                if (seq === this.gdSeq) { this.gdResults = []; fail(err, 'Could not search the registry.'); }
            } finally {
                if (seq === this.gdSeq) this.gdSearching = false;
            }
        },
        gdPick(r) {
            this.gdPicked = { ref: r.ref, name: r.name };
            this.gdResults = [];
            this.gdQuery = '';
            this.$nextTick(() => document.getElementById('gd-relationship')?.focus());
        },
        // The server's answer ({ref, guardian_applicable, guardian}) onto the
        // open record and its row in the list.
        gdApply(data) {
            const patch = { guardian: data?.guardian ?? null, guardian_applicable: !!data?.guardian_applicable };
            if (this.selectedIndividual) Object.assign(this.selectedIndividual, patch);
            const row = (this.individuals || []).find((x) => x.ref === this.selectedIndividual?.ref);
            if (row) Object.assign(row, patch);
        },
        async gdSave() {
            const ref = this.selectedIndividual?.ref;
            if (!ref || this.gdBusy || !this.gdReady) return;
            this.gdBusy = true;
            this.gdErrors = {};
            const f = this.gdForm;
            const body = { relationship: f.relationship.trim() };
            if (this.gdMode === 'linked') {
                body.individual_ref = this.gdPicked.ref;
                // Left empty, the linked record's own number and address are used.
                if (f.contact.trim()) body.contact = f.contact.trim();
                if (f.address.trim()) body.address = f.address.trim();
            } else {
                Object.assign(body, { name: f.name.trim(), contact: f.contact.trim() || null, address: f.address.trim() || null });
            }
            try {
                const res = await api.put('/admin/residents/' + encodeURIComponent(ref) + '/guardian', body);
                this.gdApply(res.data);
                this.gdOpen = false;
                toast('Guardian saved.');
            } catch (err) {
                if (err?.status === 422) this.gdErrors = err.fields || {};
                fail(err, 'Could not save the guardian.');
            } finally {
                this.gdBusy = false;
                window.Alpine?.nextTick(() => window.renderIcons?.());
            }
        },
        async gdClear() {
            const ref = this.selectedIndividual?.ref;
            if (!ref || this.gdBusy) return;
            this.gdBusy = true;
            try {
                const res = await api.delete('/admin/residents/' + encodeURIComponent(ref) + '/guardian');
                this.gdApply(res.data);
                this.gdConfirmClear = false;
                toast('Guardian cleared.');
            } catch (err) {
                fail(err, 'Could not clear the guardian.');
            } finally {
                this.gdBusy = false;
            }
        },
    }));

    // ---------------------------------------------------------------------
    // Every merge of two resident records, and undoing one: the merged-away
    // record comes back with exactly the rows that were moved off it.
    Alpine.data('mergeHistory', () => ({
        mhRows: [],
        mhLoading: false,
        mhFailed: false,
        mhLoaded: false,
        mhPage: 1,
        mhLastPage: 1,
        mhMore: false,
        mhSeq: 0,
        mhConfirm: null,
        mhBusy: false,
        mhError: '',

        get mhMay() { return this.$store.session.canPerm('const_data_quality', 'view'); },
        get mhMayUndo() { return this.$store.session.canPerm('const_data_quality', 'merge'); },

        init() {
            // Fresh each time the tab is opened; a merge made elsewhere shows.
            this.$watch('tab', (t) => { if (t === 'data-quality') this.mhLoad(); });
            if (this.tab === 'data-quality') this.mhLoad();
        },

        when,
        // The registry knows the kept record's name; a merged-away record is
        // out of the list until it is restored, so its reference stands alone.
        mhName(ref) {
            if (!ref || typeof this.dqRecordName !== 'function') return '';
            const name = this.dqRecordName(ref);
            return name && name !== ref ? name : '';
        },

        async mhLoad() {
            await this.$store.session.whenReady();
            if (!this.mhMay) return;
            const seq = ++this.mhSeq;
            this.mhLoading = true;
            this.mhFailed = false;
            try {
                const res = await api.get('/admin/merges', { params: { page: 1, per_page: 10, sort: 'created_at', direction: 'desc' } });
                if (seq !== this.mhSeq) return;
                this.mhRows = res.data || [];
                this.mhPage = 1;
                this.mhLastPage = res.meta?.last_page || 1;
                this.mhLoaded = true;
            } catch {
                if (seq === this.mhSeq) this.mhFailed = true;
            } finally {
                if (seq === this.mhSeq) this.mhLoading = false;
            }
        },

        async mhLoadMore() {
            if (this.mhMore || this.mhPage >= this.mhLastPage) return;
            const seq = this.mhSeq;
            this.mhMore = true;
            try {
                const res = await api.get('/admin/merges', { params: { page: this.mhPage + 1, per_page: 10, sort: 'created_at', direction: 'desc' } });
                if (seq !== this.mhSeq) return;
                const seen = new Set(this.mhRows.map((m) => m.id));
                this.mhRows = [...this.mhRows, ...(res.data || []).filter((m) => !seen.has(m.id))];
                this.mhPage += 1;
                this.mhLastPage = res.meta?.last_page || this.mhLastPage;
            } catch (err) {
                fail(err, 'Could not load older merges.');
            } finally {
                this.mhMore = false;
            }
        },

        mhAsk(m) {
            this.mhConfirm = m;
            this.mhError = '';
        },

        async mhUndo() {
            const m = this.mhConfirm;
            if (!m || this.mhBusy) return;
            this.mhBusy = true;
            this.mhError = '';
            try {
                const res = await api.post('/admin/merges/' + encodeURIComponent(m.id) + '/revert', {});
                const at = res.data?.reverted_at || new Date().toISOString();
                this.mhRows = this.mhRows.map((x) => (x.id === m.id ? { ...x, reverted_at: at } : x));
                this.mhConfirm = null;
                toast((res.data?.restored || m.merged || 'The record') + ' is its own record again, with the links it had.');
                // The restored record and its household and family links.
                if (typeof this.loadRegistry === 'function') this.loadRegistry();
            } catch (err) {
                if (err?.code === 'ALREADY_REVERTED') {
                    this.mhConfirm = null;
                    toast('This merge was already undone. The list is up to date now.', 'warning');
                    this.mhLoad();
                } else {
                    this.mhError = errText(err, 'Could not undo this merge.');
                }
            } finally {
                this.mhBusy = false;
            }
        },
    }));

    // ---------------------------------------------------------------------
    // Communications > Feedback: residents' tickets within the officer's
    // reach (FeedbackDesk::visibleTo), the conversation, replies with a
    // status, routing to an office and assigning to an officer, and how the
    // help content was rated.
    Alpine.data('feedbackDesk', () => ({
        chips: FB_CHIPS,
        status: 'open',
        category: '',
        office: '',
        search: '',
        mine: false,
        rows: [],
        counts: { open: 0, new: 0, resolved: 0, closed: 0 },
        categories: ['Account & Login', 'Dokyu', 'Tulong', 'Technical Issue', 'App Problem', 'Other'],
        loading: false,
        failed: false,
        loaded: false,
        started: false,
        page: 1,
        lastPage: 1,
        total: 0,
        more: false,
        seq: 0,
        timer: null,
        sel: null,
        selRef: null,
        selLoading: false,
        selFailed: false,
        reply: '',
        replyStatus: 'Completed',
        sending: false,
        replyError: '',
        routing: false,
        routeOffice: '',
        routeTo: '',
        assignees: [],
        assigneesLoading: false,
        assigning: false,
        routeError: '',
        offices: [],
        officesLoading: null,
        help: [],
        helpLoading: false,
        helpFailed: false,
        helpLoaded: false,
        helpAll: false,

        get may() { return this.$store.session.canPerm('comm_feedback', 'view'); },
        get mayReply() { return this.$store.session.canPerm('comm_feedback', 'edit'); },
        get mayAssign() { return this.$store.session.canPerm('comm_feedback', 'assign'); },

        async init() {
            this.$watch('search', () => {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.reload(), 350);
            });
            this.$watch('category', () => this.reload());
            this.$watch('office', () => this.reload());
            this.$watch('mine', () => this.reload());
            this.$watch('tab', (t) => { if (t === 'feedback' && this.may) this.start(); });
            await this.$store.session.whenReady();
            if (!this.may) return;
            if (this.tab === 'feedback') this.start();
            else this.loadCounts();
            // Live updates (2026-11-15): tickets count as other changes on the
            // server, ratings as Balita. The queue reloads quietly once open
            // (the tab's count otherwise), not while a reply or a routing is
            // being sent.
            this.$store.live.on(null, ['other', 'balita'], () => (this.started ? this.load(true) : this.loadCounts()), { busy: () => this.sending || this.assigning || this.routing });
        },

        // The first time the tab is shown: the queue, the offices, the help
        // ratings, and a ticket named in the address (?ref=, from the
        // notification an assignee gets).
        start() {
            if (this.started) return;
            this.started = true;
            this.load();
            this.loadOffices();
            this.loadHelp();
            const ref = new URLSearchParams(window.location.search).get('ref');
            if (ref) this.openTicket(ref);
        },
        reload() { if (this.started) this.load(); },

        when,
        tone,
        dot,
        chipCount(key) {
            const c = this.counts;
            return { open: c.open, new: c.new, review: Math.max(0, c.open - c.new), resolved: c.resolved, closed: c.closed }[key];
        },
        setStatus(key) {
            if (this.status === key) return;
            this.status = key;
            this.reload();
        },
        applyMeta(meta) {
            if (meta?.counts) {
                this.counts = { open: 0, new: 0, resolved: 0, closed: 0, ...meta.counts };
                // The page's tab button shows how many are open.
                this.feedbackOpen = this.counts.open;
            }
            if (Array.isArray(meta?.categories) && meta.categories.length) this.categories = meta.categories;
        },

        async loadCounts() {
            try {
                const res = await api.get('/admin/support/tickets', { params: { open: 1, per_page: 1 } });
                this.applyMeta(res.meta);
            } catch {
                // The tab then shows no count.
            }
        },

        params(page) {
            const chip = this.chips.find((c) => c.key === this.status) || this.chips[0];
            const p = { ...chip.params, page, per_page: 25 };
            if (this.category) p.category = this.category;
            if (this.office) p.office = this.office;
            if (this.mine) p.mine = 1;
            const q = this.search.trim();
            if (q) p.q = q;
            return p;
        },

        // quiet (a live update): the queue on screen stays until the new first
        // page comes, and the tickets Load more added stay below it.
        async load(quiet = false) {
            const seq = ++this.seq;
            if (!quiet) {
                this.loading = true;
                this.failed = false;
            }
            try {
                const res = await api.get('/admin/support/tickets', { params: this.params(1) });
                if (seq !== this.seq) return;
                const fresh = res.data || [];
                if (quiet && this.page > 1) {
                    const refs = new Set(fresh.map((t) => t.ref));
                    this.rows = [...fresh, ...this.rows.filter((t) => !refs.has(t.ref))];
                } else {
                    this.rows = fresh;
                    this.page = 1;
                }
                this.lastPage = Math.max(this.page, res.meta?.last_page || 1);
                this.total = res.meta?.total ?? this.rows.length;
                this.applyMeta(res.meta);
                this.loaded = true;
                this.failed = false;
            } catch {
                if (seq === this.seq && !quiet) this.failed = true;
            } finally {
                if (seq === this.seq) this.loading = false;
            }
        },

        async loadMore() {
            if (this.more || this.page >= this.lastPage) return;
            const seq = this.seq;
            this.more = true;
            try {
                const res = await api.get('/admin/support/tickets', { params: this.params(this.page + 1) });
                if (seq !== this.seq) return;
                const seen = new Set(this.rows.map((t) => t.ref));
                this.rows = [...this.rows, ...(res.data || []).filter((t) => !seen.has(t.ref))];
                this.page += 1;
                this.lastPage = res.meta?.last_page || this.lastPage;
            } catch (err) {
                fail(err, 'Could not load more tickets.');
            } finally {
                this.more = false;
            }
        },

        // Offices to route to: every office that runs a service (the public
        // catalog, which any officer can read), and the barangay halls.
        loadOffices() {
            if (this.officesLoading) return this.officesLoading;
            this.officesLoading = api.get('/services').then((res) => {
                const seen = new Map();
                (res.data || []).forEach((s) => { if (s.office && !seen.has(s.office)) seen.set(s.office, s.office_short || null); });
                if (!seen.has('Barangay Office')) seen.set('Barangay Office', null);
                this.offices = [...seen].map(([name, short]) => ({ name, short })).sort((a, b) => a.name.localeCompare(b.name));
            }).catch(() => { this.officesLoading = null; });
            return this.officesLoading;
        },
        officeName(o) {
            if (o.name === 'Barangay Office') return 'Barangay hall of the resident';
            return o.short && o.short !== o.name ? o.name + ' (' + o.short + ')' : o.name;
        },
        officeLabel(t) {
            if (!t?.office) return 'Not routed yet';
            if (t.office === 'Barangay Office') return t.barangay ? 'Brgy. ' + t.barangay + ' hall' : 'Barangay hall';
            return t.office_short || t.office;
        },
        requestLink(ref) {
            return (/^TUL-/i.test(String(ref)) ? '/admin/assistance-requests' : '/admin/document-requests') + '?ref=' + encodeURIComponent(ref);
        },
        size(bytes) {
            const n = Number(bytes) || 0;
            return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB';
        },

        async openTicket(ref) {
            if (!ref) return;
            this.selRef = ref;
            const row = this.rows.find((t) => t.ref === ref);
            // What the list knows shows at once; the conversation follows.
            this.sel = row ? { ...row } : null;
            this.selLoading = true;
            this.selFailed = false;
            this.reply = '';
            this.replyError = '';
            this.routing = false;
            this.routeError = '';
            this.replyStatus = row?.status === 'Cancelled' ? 'Under Review' : 'Completed';
            if (window.matchMedia?.('(max-width: 1023px)').matches) this.$nextTick(() => this.$refs.ticket?.scrollIntoView({ block: 'start', behavior: 'smooth' }));
            try {
                const res = await api.get('/admin/support/tickets/' + encodeURIComponent(ref));
                if (this.selRef !== ref) return;
                this.sel = res.data;
                this.replyStatus = res.data?.status === 'Cancelled' ? 'Under Review' : 'Completed';
            } catch (err) {
                if (this.selRef !== ref) return;
                this.selFailed = true;
                if (!this.sel) this.sel = { ref, subject: err?.status === 404 ? 'Ticket not found' : 'Could not open this ticket', notFound: err?.status === 404 };
            } finally {
                if (this.selRef === ref) this.selLoading = false;
            }
        },
        closeTicket() {
            this.sel = null;
            this.selRef = null;
            this.routing = false;
        },

        // The resident's first message is the ticket itself; replies follow in
        // order. A reply sent before conversations existed (2026-10-15) is
        // kept only as `response`, and is shown last.
        get thread() {
            const t = this.sel;
            if (!t || t.notFound) return [];
            const list = [{ from: 'citizen', name: t.citizen?.name || 'Resident', body: t.body, at: t.created_at }];
            const replies = Array.isArray(t.messages) ? t.messages : [];
            list.push(...replies);
            if (Array.isArray(t.messages) && t.response && !replies.some((m) => m.from === 'staff')) {
                list.push({ from: 'staff', name: 'LGU staff', body: t.response, at: t.responded_at });
            }
            return list.filter((m) => m.body);
        },

        get replyOptions() {
            if (this.sel?.status === 'Cancelled') return [['Under Review', 'Under Review: reopen the ticket']];
            return [
                ['Completed', 'Completed: resolved'],
                ['Under Review', 'Under Review: still working on it'],
                ['Cancelled', 'Cancelled: closed without action'],
            ];
        },

        patchRow(t) {
            if (!t?.ref) return;
            const { messages, ...row } = t;
            this.rows = this.rows.map((x) => (x.ref === row.ref ? { ...x, ...row } : x));
        },

        async sendReply() {
            const t = this.sel;
            const message = this.reply.trim();
            if (!t?.ref || this.sending || !message) return;
            this.sending = true;
            this.replyError = '';
            try {
                const res = await api.post('/admin/support/tickets/' + encodeURIComponent(t.ref) + '/reply', { message, status: this.replyStatus || null });
                if (this.selRef === t.ref) {
                    this.sel = res.data;
                    this.reply = '';
                    this.replyStatus = res.data?.status === 'Cancelled' ? 'Under Review' : 'Completed';
                }
                this.patchRow(res.data);
                toast('Reply sent. The resident is notified.');
                this.loadCounts();
            } catch (err) {
                // The reply stays in the box, to send again.
                if (err?.code === 'TICKET_CLOSED') {
                    this.replyStatus = 'Under Review';
                    this.replyError = 'This ticket is closed. Send the reply with Under Review to reopen it.';
                } else {
                    this.replyError = errText(err, 'The reply could not be sent.');
                }
            } finally {
                this.sending = false;
            }
        },

        async startRouting() {
            const t = this.sel;
            if (!t?.ref) return;
            this.routing = true;
            this.routeError = '';
            this.routeOffice = t.office || '';
            this.routeTo = t.assigned_to?.employee_id || '';
            await this.loadOffices();
            if (t.office && !this.offices.some((o) => o.name === t.office)) this.offices = [...this.offices, { name: t.office, short: t.office_short || null }];
            await this.loadAssignees();
        },

        // Officers who may take this ticket for the chosen office.
        async loadAssignees() {
            const t = this.sel;
            if (!t?.ref) return;
            const office = this.routeOffice;
            this.assigneesLoading = true;
            this.assignees = [];
            try {
                const res = await api.get('/admin/support/tickets/' + encodeURIComponent(t.ref) + '/assignees', { params: office ? { office } : {} });
                if (this.routeOffice !== office || this.sel?.ref !== t.ref) return;
                this.assignees = res.data || [];
                if (this.routeTo && !this.assignees.some((a) => a.employee_id === this.routeTo)) this.routeTo = '';
            } catch (err) {
                if (this.routeOffice === office) this.routeError = errText(err, 'Could not load the officers for this office.');
            } finally {
                if (this.routeOffice === office) this.assigneesLoading = false;
            }
        },

        async saveRouting() {
            const t = this.sel;
            if (!t?.ref || this.assigning) return;
            this.assigning = true;
            this.routeError = '';
            const body = {};
            if (this.routeOffice) body.office = this.routeOffice;
            if (this.routeTo) body.employee_id = this.routeTo;
            try {
                const res = await api.post('/admin/support/tickets/' + encodeURIComponent(t.ref) + '/assign', body);
                if (this.selRef === t.ref) this.sel = res.data;
                this.patchRow(res.data);
                this.routing = false;
                toast(res.data?.assigned_to ? 'Assigned to ' + res.data.assigned_to.name + '. They are notified.' : 'Routed to ' + this.officeLabel(res.data) + '.');
                this.loadCounts();
            } catch (err) {
                if (err?.code === 'NOT_AN_ASSIGNEE') {
                    this.routeError = 'That officer cannot take tickets for this office. Choose someone from the list.';
                    this.loadAssignees();
                } else {
                    this.routeError = errText(err, 'Could not assign this ticket.');
                }
            } finally {
                this.assigning = false;
            }
        },

        async loadHelp() {
            this.helpLoading = true;
            this.helpFailed = false;
            try {
                const res = await api.get('/admin/support/helpfulness');
                // What residents found unhelpful first: that is what needs work.
                this.help = (res.data || []).slice().sort((a, b) => (b.not_helpful - a.not_helpful) || (b.helpful - a.helpful));
                this.helpLoaded = true;
            } catch {
                this.helpFailed = true;
            } finally {
                this.helpLoading = false;
            }
        },
        get helpShown() { return this.helpAll ? this.help : this.help.slice(0, 6); },
    }));
}
