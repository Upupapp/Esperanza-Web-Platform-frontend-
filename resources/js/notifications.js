// Notifications, both portals: the stores behind the bell, the pop-ups and
// the Notifications pages (moved out of app.js, 28 Nov 2026).
//
// Real since the production-readiness programme (2026-09-25):
// GET {citizen|admin}/notifications and POST .../notifications/{id}/read are
// the same ModuleController on the backend; category IS the request type
// ('dokyu' | 'tulong') for a Dokyu/Tulong lifecycle notification.
//
// The redesign (owner, 28 Nov 2026, after eBPCO's): the bell has one filter
// row (All / Unread with counts, and the kinds), Mark all as read and a close
// button at the top and "See all notifications" at the bottom; the
// Notifications page (notifications-page.js) filters by words, kind and when.
// An unread notice is a blue-tinted row with a bold title and a blue dot: the
// dot marks it read, and so does opening its link. On the staff site a link
// shows only to a role that can open the page it leads to (mayOpen below, the
// same checks those pages make). Messages from residents (a reply on a
// ticket, a ticket handed to an officer) are their own kind, 'messages',
// with an Open conversation link.
//
// The server filters (unread, category, q, since, until) and names the kinds
// a person has (meta.categories) since backend cdbad32. Until that backend is
// deployed it ignores them: a feed then filters what it loaded itself
// (meta.filtered is missing), so the pages work against either.

import api from './api/client';

const t = (en, fil) => ({ en, fil });

// Each kind's icon and tone (the category of the notification).
export const notificationStyle = {
    dokyu: ['file-text', 'brand'], tulong: ['hand-heart', 'purple'],
    account: ['user-round', 'indigo'], community: ['megaphone', 'teal'],
    events: ['calendar', 'amber'], sakuna: ['shield-alert', 'rose'],
    summary: ['calendar-range', 'indigo'], messages: ['message-square-reply', 'sky'],
    communications: ['megaphone', 'teal'], request: ['file-text', 'brand'],
};

// Each kind's name in the Category filters. 'events' are appointments
// (AppointmentBook); 'community' is Balita and the LGU's events. The staff
// site reads the English only.
const KIND_NAMES = {
    dokyu: t('Dokyu', 'Dokyu'),
    tulong: t('Tulong', 'Tulong'),
    messages: t('Messages from residents', 'Mga mensahe'),
    account: t('Account', 'Account'),
    community: t('Balita and events', 'Balita at events'),
    communications: t('Balita', 'Balita'),
    events: t('Appointments', 'Appointment'),
    sakuna: t('Sakuna', 'Sakuna'),
    summary: t('Weekly summary', 'Lingguhang buod'),
    request: t('Requests', 'Mga request'),
};

const lang = () => window.Alpine?.store('lang')?.current || 'fil';

export function kindName(key, language = 'en') {
    const name = KIND_NAMES[key];
    if (name) return language === 'en' ? name.en : name.fil;
    return key ? key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ') : '';
}

// The server links a request's notice as /citizen/document-requests/{ref}
// (the app's form); this site has no page there - it opens a request from
// its list with ?ref= - so a tapped notice ended on "page not found"
// (2026-11-13). The same address, the way this site opens it.
function siteLink(link) {
    const m = typeof link === 'string' && link.match(/^\/citizen\/(document-requests|assistance-requests)\/([^/?#]+)\/?$/);
    return m ? '/citizen/' + m[1] + '?ref=' + m[2] : link;
}

function notificationItem(n) {
    const [icon, color] = notificationStyle[n.category] || ['bell', 'brand'];
    const language = lang();

    return {
        id: n.id,
        portal: n.category,
        icon, color,
        title: n.title?.[language] || n.title?.en || '',
        titleFil: n.title?.fil, titleEn: n.title?.en,
        body: n.body?.[language] || n.body?.en || '',
        bodyFil: n.body?.fil, bodyEn: n.body?.en,
        pill: n.pill, ref: n.ref, link: siteLink(n.link),
        pinned: n.pinned, unread: n.unread,
        // Read here, in this view: an Unread list keeps it (now plain) until it reloads.
        readHere: false,
        popup: n.popup === true,
        createdAt: n.time,
    };
}

// What a feed shows when the server did not filter (a backend before cdbad32).
function matches(n, f) {
    if (f.unread && !(n.unread || n.readHere)) return false;
    if (f.category && n.portal !== f.category) return false;
    const words = String(f.q || '').toLowerCase().split(/\s+/).filter(Boolean);
    if (words.length) {
        const text = [n.titleEn, n.titleFil, n.bodyEn, n.bodyFil, n.ref].filter(Boolean).join(' ').toLowerCase();
        if (!words.every((w) => text.includes(w))) return false;
    }
    const at = n.createdAt ? new Date(n.createdAt).getTime() : NaN;
    if (f.since && !(at >= new Date(f.since).getTime())) return false;
    if (f.until && !(at < new Date(f.until).getTime())) return false;
    return true;
}

/**
 * The When filter as two instants, from this browser's own midnight:
 * 'today', '7d' (today and the 6 days before), '30d', or 'on' a date
 * (YYYY-MM-DD) - that day only.
 */
export function whenRange(when, date = '') {
    const midnight = new Date();
    midnight.setHours(0, 0, 0, 0);
    const back = (d, days) => { const x = new Date(d); x.setDate(x.getDate() + days); return x; };
    if (when === 'today') return { since: midnight.toISOString(), until: null };
    if (when === '7d') return { since: back(midnight, -6).toISOString(), until: null };
    if (when === '30d') return { since: back(midnight, -29).toISOString(), until: null };
    if (when === 'on' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
        const [y, m, d] = date.split('-').map(Number);
        const start = new Date(y, m - 1, d);
        return { since: start.toISOString(), until: back(start, 1).toISOString() };
    }
    return { since: null, until: null };
}

/** '5 minutes ago', '2 days ago', then 'Nov 3, 2026'; in Filipino on the resident site. */
export function timeAgo(iso, en = lang() === 'en') {
    if (!iso) return '';
    const mins = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);
    if (mins < 1) return en ? 'Just now' : 'Ngayon lang';
    if (mins < 60) return en ? mins + ' minute' + (mins === 1 ? '' : 's') + ' ago' : mins + ' minuto ang nakalipas';
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return en ? hrs + ' hour' + (hrs === 1 ? '' : 's') + ' ago' : hrs + ' oras ang nakalipas';
    const days = Math.floor(hrs / 24);
    if (days < 7) return en ? days + ' day' + (days === 1 ? '' : 's') + ' ago' : days + ' araw ang nakalipas';
    return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// ---- Staff links: only where the role may go -------------------------------
// The checks each page makes before it shows anything but Access Restricted
// (its x-admin.access-restricted guard), by the address a notification opens.
const BALITA_TABS = { balita: 'comm_news', banners: 'comm_banners', events: 'comm_events', blotter: 'comm_directory' };

export function mayOpen(link, s) {
    if (!s?.account) return false;
    const [path, rest = ''] = String(link).split('?');
    const page = path.replace(/\/+$/, '');
    const tab = new URLSearchParams(rest.split('#')[0]).get('tab');
    const any = (subs) => subs.some((sub) => s.canPerm(sub, 'view'));
    const under = (prefix) => page === prefix || page.startsWith(prefix + '/');
    if (under('/admin/settings/feedback') || ((under('/admin/communications') || under('/admin/announcements')) && tab === 'feedback')) return s.canPerm('comm_feedback', 'view');
    if (under('/admin/settings/directory')) return any(['comm_directory', 'comm_barangay_directory']);
    if (under('/admin/settings')) return s.canSettings() || any(['comm_feedback', 'comm_directory', 'comm_barangay_directory', 'set_system']) || s.canPerm('comm_news', 'create');
    if (under('/admin/communications') || under('/admin/announcements')) {
        if (under('/admin/communications/banners')) return s.canPerm('comm_banners', 'view');
        return BALITA_TABS[tab] ? s.canPerm(BALITA_TABS[tab], 'view') : any(Object.values(BALITA_TABS));
    }
    if (under('/admin/appointments') || under('/admin/dashboard/appointments')) return any(['appt_bookings', 'appt_schedules']);
    const modules = {
        '/admin/document-requests': 'dokyu', '/admin/assistance-requests': 'tulong', '/admin/constituents': 'constituents',
        '/admin/papeles': 'papeles', '/admin/payments': 'payments', '/admin/reports': 'reports', '/admin/sakuna': 'sakuna',
        '/admin/users': 'user_management',
    };
    const module = Object.keys(modules).find((prefix) => under(prefix));
    return module ? s.can(modules[module]) : true;
}

// Where a row opens, and what its link says. A notice without a link that
// names a Dokyu or Tulong request opens that request in its list.
function target(n, admin) {
    if (n.link) return n.link;
    if ((n.portal === 'dokyu' || n.portal === 'tulong') && n.ref) {
        const list = n.portal === 'tulong' ? 'assistance-requests' : 'document-requests';
        return (admin ? '/admin/' : '/citizen/') + list + '?ref=' + encodeURIComponent(n.ref);
    }
    return null;
}

const STAFF_PLACES = [
    ['/admin/settings/feedback', 'Open conversation'], ['/admin/communications?tab=feedback', 'Open conversation'],
    ['/admin/document-requests', 'Open in Dokyu'], ['/admin/assistance-requests', 'Open in Tulong'],
    ['/admin/sakuna', 'Open in Sakuna'], ['/admin/communications', 'Open in Balita'], ['/admin/constituents', 'Open in Constituents'],
    ['/admin/papeles', 'Open in Papeles'], ['/admin/payments', 'Open in Payments'], ['/admin/appointments', 'Open in Appointments'],
    ['/admin/reports', 'Open in Reports'], ['/admin/dashboard', 'Open dashboard'],
];

// ---- A list of notifications, with its filters ----------------------------
// The base list behind the bell (unfiltered: the pop-ups and the number read
// it), the bell's filtered list, and each Notifications page's list.
function makeFeed(path, perPage, extra = {}) {
    const pageParams = (page = null) => ({ ...(perPage ? { per_page: perPage } : {}), ...(page ? { page } : {}) });
    const feed = {
        items: [],
        state: 'idle',
        error: null,
        // The server's count of every unread notification (meta.unread),
        // not just the page loaded here: the number on the bell.
        serverUnread: null,
        // meta.total (this filter's), meta.total_all (every one) and
        // meta.categories, when the server sends them.
        total: null,
        totalAll: null,
        kinds: null,
        serverFiltered: false,
        page: 1,
        lastPage: 1,
        loadingMore: false,
        moreFailed: false,
        seq: 0,
        filters: { unread: false, category: '', q: '', since: null, until: null },

        get loading() { return this.state === 'loading'; },
        get failed() { return this.state === 'error'; },
        get hasMore() { return this.state === 'loaded' && this.page < this.lastPage; },
        get filtering() {
            const f = this.filters;
            return f.unread || !!f.category || !!String(f.q || '').trim() || !!f.since || !!f.until;
        },
        // What to draw: the server's answer, or (an older backend) what of it matches.
        get shown() { return this.serverFiltered || !this.filtering ? this.items : this.items.filter((n) => matches(n, this.filters)); },
        params(page = null) {
            const f = this.filters;
            const q = String(f.q || '').trim();
            return {
                ...pageParams(page),
                ...(f.unread ? { unread: 1 } : {}),
                ...(f.category ? { category: f.category } : {}),
                ...(q ? { q } : {}),
                ...(f.since ? { since: f.since } : {}),
                ...(f.until ? { until: f.until } : {}),
            };
        },
        takeMeta(meta) {
            if (Number.isInteger(meta?.unread)) this.serverUnread = meta.unread;
            this.serverFiltered = meta?.filtered === true;
            this.total = Number.isInteger(meta?.total) ? meta.total : null;
            if (Number.isInteger(meta?.total_all)) this.totalAll = meta.total_all;
            if (Array.isArray(meta?.categories)) this.kinds = meta.categories;
        },

        // quiet: a background refresh keeps what is on screen until the answer comes.
        async load(quiet = false) {
            const seq = ++this.seq;
            if (!quiet) this.state = 'loading';
            this.error = null;
            try {
                const result = await api.get(path, { params: this.params() });
                // A newer load (the filters changed meanwhile) wins.
                if (seq !== this.seq) return;
                this.serverUnread = Number.isInteger(result.meta?.unread) ? result.meta.unread : null;
                this.takeMeta(result.meta);
                const fresh = (result.data || []).map(notificationItem);
                if (quiet && this.page > 1) {
                    // The quiet refresh keeps the older pages already opened
                    // with "Load more".
                    const ids = new Set(fresh.map((n) => n.id));
                    this.items = [...fresh, ...this.items.filter((n) => !ids.has(n.id))];
                } else {
                    this.items = fresh;
                    this.page = 1;
                }
                this.lastPage = Math.max(this.page, Number(result.meta?.last_page) || 1);
                this.state = 'loaded';
                // The rows' <i data-lucide> placeholders only become icons
                // when Lucide runs after Alpine has drawn them.
                window.Alpine?.nextTick(() => window.renderIcons?.());
            } catch (err) {
                if (seq !== this.seq) return;
                this.error = err;
                if (!quiet || this.state !== 'loaded') this.state = 'error';
            }
        },

        // The next page, added under what is shown. A row already shown
        // (new ones arriving shift the pages down) is not repeated.
        async loadMore() {
            if (this.loadingMore || !this.hasMore) return;
            this.loadingMore = true;
            this.moreFailed = false;
            const seq = this.seq;
            try {
                const next = this.page + 1;
                const result = await api.get(path, { params: this.params(next) });
                if (seq !== this.seq) return;
                const ids = new Set(this.items.map((n) => n.id));
                this.items = [...this.items, ...(result.data || []).map(notificationItem).filter((n) => !ids.has(n.id))];
                this.page = next;
                this.lastPage = Math.max(next, Number(result.meta?.last_page) || next);
                this.takeMeta(result.meta);
                window.Alpine?.nextTick(() => window.renderIcons?.());
            } catch {
                this.moreFailed = true;
            } finally {
                this.loadingMore = false;
            }
        },
    };
    return Object.defineProperties(feed, Object.getOwnPropertyDescriptors(extra));
}

// The server pages notifications 25 at a time unless asked. Residents' bell
// and the Notifications pages ask for 100 (the server's most), and "Load
// more" fetches the next page while meta.last_page allows (parity check,
// 2026-10-26). The staff bell keeps the server's 25.
export const NOTIFICATIONS_PER_PAGE = 100;

function makeNotificationStore(path, perPage = null) {
    const admin = path.startsWith('/admin');
    return makeFeed(path, perPage, {
        admin,
        // The bell's own filter row; a filtered bell has its own list.
        bellTab: 'all',
        bellCategory: '',
        bell: makeFeed(path, perPage),
        // Each Notifications page's list, so a read there is read everywhere.
        pages: [],

        get visible() { return this.items; },
        get unreadCount() { return this.serverUnread ?? this.visible.filter((n) => n.unread).length; },
        get allCount() { return this.totalAll ?? this.total ?? this.items.length; },
        // The kinds this person has, with how many and how many unread: the
        // server's, or (an older backend) counted from what is loaded.
        get categories() {
            if (Array.isArray(this.kinds)) return this.kinds;
            const counts = {};
            this.items.forEach((n) => {
                if (!n.portal) return;
                counts[n.portal] ??= { key: n.portal, total: 0, unread: 0 };
                counts[n.portal].total++;
                if (n.unread) counts[n.portal].unread++;
            });
            return Object.values(counts).sort((a, b) => b.total - a.total);
        },
        kindName(key) { return kindName(key, admin ? 'en' : lang()); },
        kindCount(key, unreadOnly = false) {
            const k = this.categories.find((c) => c.key === key);
            return k ? (unreadOnly ? k.unread : k.total) : 0;
        },
        ago(n) { return timeAgo(n.createdAt, admin || lang() === 'en'); },

        // Where a row opens; null when it opens nothing, or (staff) a page
        // this role cannot open.
        href(n) {
            const link = target(n, admin);
            if (!link || !admin) return link;
            return mayOpen(link, window.Alpine?.store('session')) ? link : null;
        },
        // What the row's link says.
        action(n) {
            const link = this.href(n);
            if (!link) return '';
            if (admin) return (STAFF_PLACES.find(([prefix]) => link.startsWith(prefix)) || [null, 'Open'])[1];
            const en = lang() === 'en';
            return /^\/citizen\/(document|assistance)-requests\?ref=/.test(link) ? (en ? 'Open request' : 'Buksan ang request') : (en ? 'Open' : 'Buksan');
        },

        get bellFiltered() { return this.bellTab !== 'all' || !!this.bellCategory; },
        get bellItems() { return this.bellFiltered ? this.bell.shown : this.visible; },
        get bellLoading() { return this.bellFiltered ? this.bell.loading : this.loading; },
        setBell(tab, category = this.bellCategory) {
            this.bellTab = tab;
            this.bellCategory = category;
            if (!this.bellFiltered) return;
            this.bell.filters = { ...this.bell.filters, unread: tab === 'unread', category };
            this.bell.load();
        },

        attach(feed) {
            this.pages = [...this.pages, feed];
            return () => { this.pages = this.pages.filter((f) => f !== feed); };
        },
        // Every list a notification may be in, and each one's kinds.
        lists() { return [this, this.bell, ...this.pages]; },
        countKind(key, delta) {
            this.lists().forEach((feed) => (feed.kinds || []).forEach((k) => { if (k.key === key) k.unread = Math.max(0, k.unread + delta); }));
        },

        async markRead(id) {
            const rows = this.lists().flatMap((feed) => feed.items.filter((i) => i.id === id && i.unread));
            if (!rows.length) return;
            rows.forEach((n) => { n.unread = false; n.readHere = true; });
            if (this.serverUnread) this.serverUnread--;
            this.countKind(rows[0].portal, -1);
            try {
                await api.post(`${path}/${id}/read`);
            } catch {
                // the server did not accept it — undo the optimistic flip
                rows.forEach((n) => { n.unread = true; });
                if (this.serverUnread !== null) this.serverUnread++;
                this.countKind(rows[0].portal, 1);
            }
        },

        // A row's link was opened: it is read. The page goes away at once,
        // which cancels the client's requests, so this one is sent to outlive
        // it (as Sign Out's is).
        open(n) {
            if (!n?.unread || !this.href(n)) return;
            this.lists().forEach((feed) => feed.items.forEach((i) => { if (i.id === n.id) { i.unread = false; i.readHere = true; } }));
            if (this.serverUnread) this.serverUnread--;
            this.countKind(n.portal, -1);
            try {
                fetch(api.baseUrl.replace(/\/$/, '') + `${path}/${n.id}/read`, {
                    method: 'POST',
                    keepalive: true,
                    headers: { Accept: 'application/json', Authorization: 'Bearer ' + api.getToken(), 'X-Requested-With': 'XMLHttpRequest' },
                }).catch(() => {});
            } catch {
                // It stays unread on the server; the next load says so.
            }
        },

        // One call clears every unread notification, including ones on
        // pages not loaded here, and it holds after a refresh.
        // True once the server took it; false when it refused, after the
        // unread marks are put back, so a caller can tell the two apart
        // (QA 2026-11-18) rather than guess from the count.
        async markAllRead() {
            const before = this.lists().flatMap((feed) => feed.items.filter((n) => n.unread));
            const kinds = this.lists().map((feed) => (feed.kinds || []).map((k) => [k, k.unread]));
            const count = this.serverUnread;
            before.forEach((n) => { n.unread = false; n.readHere = true; });
            kinds.flat().forEach(([k]) => { k.unread = 0; });
            this.serverUnread = 0;
            try {
                await api.post(`${path}/read-all`);
                return true;
            } catch {
                before.forEach((n) => { n.unread = true; });
                kinds.flat().forEach(([k, unread]) => { k.unread = unread; });
                this.serverUnread = count;
                return false;
            }
        },

        // New notifications arrive without a reload: every minute while the
        // tab is in view. The bell only loaded once per page before.
        // Live updates (2026-11-15): and within a second or two of one
        // being written anywhere (live:notifications); the minute poll
        // stays as the safety net. A filtered bell follows too.
        poll(ms = 60000) {
            if (this.timer) return;
            const refresh = () => {
                if (this.bellFiltered && this.bell.state !== 'loading') this.bell.load(true);
                return this.state !== 'loading' ? this.load(true) : null;
            };
            this.timer = setInterval(() => {
                if (document.visibilityState === 'visible') refresh();
            }, ms);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') refresh();
            });
            window.Alpine.store('live').on(null, 'notifications', () => refresh(), { dialogs: false, typing: false });
        },

        // A Sakuna alert the resident has not seen yet opens as a pop-up once.
        shownAlerts: (() => { try { return JSON.parse(localStorage.getItem('esperanza_shown_alerts') || '[]'); } catch { return []; } })(),
        get pendingAlert() {
            return this.visible.find((n) => n.portal === 'sakuna' && n.popup && n.unread && !this.shownAlerts.includes(n.id)) || null;
        },
        // The notice that the account is verified opens once as a welcome
        // (live test 2026-10-07, UX-9): it was only a red dot on the bell
        // while Dokyu and Tulong still said to wait for approval.
        get pendingWelcome() {
            return this.visible.find((n) => n.portal === 'account' && n.pill === 'Verified' && n.unread && !this.shownAlerts.includes(n.id)) || null;
        },
        alertShown(id) {
            this.shownAlerts = [...this.shownAlerts, id].slice(-50);
            try { localStorage.setItem('esperanza_shown_alerts', JSON.stringify(this.shownAlerts)); } catch { /* the pop-up just may show again */ }
            this.markRead(id);
        },

        // A list for a Notifications page, its filters its own.
        newFeed() { return makeFeed(path, NOTIFICATIONS_PER_PAGE); },
    });
}

export function registerNotifications(Alpine) {
    Alpine.store('citizenNotifications', makeNotificationStore('/citizen/notifications', NOTIFICATIONS_PER_PAGE));
    Alpine.store('adminNotifications', makeNotificationStore('/admin/notifications'));
}
