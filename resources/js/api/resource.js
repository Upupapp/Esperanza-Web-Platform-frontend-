/**
 * The four states, once, for every data surface.
 *
 * TAB 06: every list, table, chart, detail panel and stat card needs loading,
 * empty, error and loaded — not just loaded. Before TAB 05 nothing could fail,
 * so nothing handled failure: 22 skeleton usages, 7 empty states and ZERO error
 * states across 98 views.
 *
 * Designed around the fact that this app currently renders BUILD-TIME fixtures
 * (see resources/data/). Pass those as `initial` and the surface starts in the
 * `loaded` state rendering exactly what it renders today; the loading and error
 * states are real but simply do not occur until something calls load(). When
 * the API exists, the same surface gets all four for free by calling load().
 *
 *     <div x-data="resource(() => $api.get('/dokyu/requests'), { initial: @js($requests) })">
 *         <template x-if="loading"> ...skeleton... </template>
 *         <template x-if="failed">
 *             <x-ui.error-state messageExpr="message($store.lang.current)" retryExpr="retry()" />
 *         </template>
 *         <template x-if="empty">  ...x-ui.empty-state... </template>
 *         <template x-if="loaded && !empty"> ...rows... </template>
 *     </div>
 *
 * Three rules this encodes, so no screen has to remember them:
 *
 *   1. `empty` is ONLY true once loaded. A stat card showing 0 while a request
 *      is still in flight is a lie an officer will act on, so `count` is not
 *      readable as "none" until the load resolved.
 *   2. A failure NEVER degrades to an empty list. `items` is left untouched on
 *      error and `failed` becomes true — a caught exception that renders "no
 *      results" tells the officer there is no data, which is the worst outcome
 *      available.
 *   3. Each surface owns its own state, so one failing endpoint cannot blank a
 *      dashboard of independent panels. Partial failure is normal.
 */

import api from './client';

export function resource(loader, options = {}) {
    const hasInitial = Object.prototype.hasOwnProperty.call(options, 'initial');

    return {
        items: hasInitial ? options.initial : [],
        error: null,

        // idle -> the surface has data it was born with and has not fetched.
        state: hasInitial ? 'loaded' : 'idle',

        get loading() {
            return this.state === 'loading';
        },

        get failed() {
            return this.state === 'error';
        },

        get loaded() {
            return this.state === 'loaded';
        },

        get count() {
            if (Array.isArray(this.items)) {
                return this.items.length;
            }

            return this.items === null || this.items === undefined ? 0 : 1;
        },

        /** Rule 1: never "empty" until we actually know. */
        get empty() {
            return this.state === 'loaded' && this.count === 0;
        },

        async load() {
            this.state = 'loading';
            this.error = null;

            try {
                const result = await loader(api);

                // Rule 2 continued: only replace items on success.
                this.items = options.unwrap ? options.unwrap(result) : result;
                this.state = 'loaded';
            } catch (err) {
                if (err && err.code === 'REQUEST_CANCELLED') {
                    // A cancelled request is a navigation, not a failure. Leave
                    // the surface as it was rather than flashing an error.
                    this.state = hasInitial || this.count > 0 ? 'loaded' : 'idle';

                    return;
                }

                this.error = err;
                this.state = 'error';
            }
        },

        retry() {
            return this.load();
        },

        /** Human-readable failure text in the reader's current language. */
        message(lang) {
            if (!this.error) {
                return '';
            }

            return typeof this.error.localised === 'function'
                ? this.error.localised(lang)
                : this.error.message;
        },

        /** True when a retry is worth offering at all (5xx, 429, offline). */
        get retryable() {
            return Boolean(this.error && this.error.retryable);
        },
    };
}

export default resource;
