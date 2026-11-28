// The Notifications page, both portals (redesign 28 Nov 2026, after eBPCO's):
// All / Unread tabs; Search (words in a notification), Category (the kinds
// this person has, with counts) and When (Any time, Today, Last 7 days, Last
// 30 days, or a date picked); Clear filters once something is filtered; a
// plain empty state with a way back. The page keeps its own list
// (store.newFeed()), filtered on the server, and hands it to the store so a
// notice read in the bell or here is read in both. It follows live updates.
//
// Loaded only on /admin/notifications and /citizen/notifications (the area
// loader at the end of app.js); register() runs before Alpine starts.

import { whenRange } from './notifications';

const today = () => {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
};

export function register(Alpine) {
    Alpine.data('notificationsPage', (storeName) => ({
        feed: null,
        tab: 'all',
        q: '',
        category: '',
        when: 'any',
        date: '',
        markingAll: false,

        get store() { return this.$store[storeName]; },
        get en() { return storeName === 'adminNotifications' || this.$store.lang.current === 'en'; },
        get rows() { return this.feed?.shown || []; },
        get loading() { return !this.feed || this.feed.loading; },
        get failed() { return !!this.feed?.failed; },
        get loaded() { return this.feed?.state === 'loaded'; },
        get empty() { return this.loaded && this.rows.length === 0; },
        // Search, Category or When; the tabs are not filters.
        get filtered() { return !!this.q.trim() || !!this.category || this.when !== 'any'; },
        get today() { return today(); },

        init() {
            this.feed = this.store.newFeed();
            this.store.attach(this.feed);
            this.apply();
            this.$watch('q', () => this.apply());
            // A notification written anywhere reloads the list quietly
            // (live updates, 2026-11-15), not while a search is being typed.
            Alpine.store('live').on(this.$el, 'notifications', () => this.feed.load(true), { dialogs: false });
            this.$watch('rows', () => this.$nextTick(() => window.renderIcons?.()));
        },

        apply() {
            const range = whenRange(this.when, this.date);
            this.feed.filters = { unread: this.tab === 'unread', category: this.category, q: this.q, since: range.since, until: range.until };
            this.feed.load();
        },
        setTab(tab) {
            if (this.tab === tab) return;
            this.tab = tab;
            this.apply();
        },
        setWhen(value) {
            this.when = value;
            if (value !== 'on') {
                this.date = '';
                this.apply();
                return;
            }
            // "On a date…" opens the date picker at once.
            this.$nextTick(() => { try { this.$refs.date?.showPicker?.(); } catch { this.$refs.date?.focus(); } });
            if (this.date) this.apply();
        },
        clearFilters() {
            this.q = '';
            this.category = '';
            this.when = 'any';
            this.date = '';
            this.apply();
        },
        // The empty state's way out: every notification again.
        showAll() {
            this.tab = 'all';
            this.clearFilters();
        },

        kindLabel(k) {
            return this.store.kindName(k.key) + ' (' + (this.tab === 'unread' ? k.unread : k.total) + ')';
        },

        // The store answers true or false, and puts the unread marks back
        // when the server refused (sweep 2026-11-18).
        markAllRead() {
            if (this.markingAll) return;
            this.markingAll = true;
            this.store.markAllRead().then((ok) => {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: ok === false
                        ? { message: this.en ? 'Could not mark the notifications as read. Please try again.' : 'Hindi namarkahan nang nabasa ang mga abiso. Pakisubukang muli.', variant: 'error' }
                        : { message: this.en ? 'All notifications marked as read.' : 'Lahat ng abiso ay minarkahan nang nabasa na.', variant: 'success' },
                }));
            }).finally(() => { this.markingAll = false; });
        },
    }));
}
