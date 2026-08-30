/**
 * One submission path for every write in the platform. TAB 07.
 *
 * Before this there were 3 <form> elements against 297 <input> and 366 x-model
 * bindings — the whole app was Alpine state with nowhere to submit to. Every
 * citizen request, every admin decision and every settings change needs the
 * same five things, and getting any of them wrong is consequential in a system
 * that issues government documents:
 *
 *   - in-flight state, so the user knows it is happening
 *   - a double-submit guard, because a duplicated Tulong application is a
 *     duplicated claim
 *   - server-side field errors displayed against the SAME field keys
 *   - the user's input preserved when it fails
 *   - a warning before navigating away from a dirty form
 *
 * Client validation here MIRRORS the spec and never replaces server validation.
 * The server is the boundary; this only saves a round trip and points at the
 * right box.
 *
 *     <form x-data="specForm({
 *             spec: @js($spec),
 *             submit: (values) => $api.post('/dokyu/requests', values),
 *           })"
 *           @submit.prevent="submit()">
 */

import api from './client';

/** Fields the spec marks required. `required` defaults to true when absent. */
function requiredKeys(spec) {
    const keys = [];

    for (const step of spec.steps || []) {
        for (const field of step.fields || []) {
            if (field.required !== false && field.type !== 'derivedAge') {
                keys.push(field.key);
            }
        }
    }

    return keys;
}

function blankValue(field) {
    if (field.type === 'multiselect') return [];
    if (field.type === 'checkbox') return false;

    return '';
}

export function specForm({ spec = { steps: [] }, submit = null, initial = {}, onSuccess = null } = {}) {
    const fields = (spec.steps || []).flatMap((s) => s.fields || []);

    const blank = {};
    for (const f of fields) blank[f.key] = blankValue(f);

    return {
        spec,
        values: { ...blank, ...initial },
        pristine: JSON.stringify({ ...blank, ...initial }),

        /** Server (422) and client errors, keyed by the SAME field key. */
        errors: {},
        formError: null,
        submitting: false,
        submitted: false,

        get dirty() {
            return JSON.stringify(this.values) !== this.pristine;
        },

        /** Derived fields are computed, never typed into. */
        derive() {
            for (const f of fields) {
                if (f.type !== 'derivedAge' || !f.derivedFromKey) continue;

                const dob = this.values[f.derivedFromKey];
                if (!dob) { this.values[f.key] = ''; continue; }

                const born = new Date(dob);
                if (Number.isNaN(born.getTime())) { this.values[f.key] = ''; continue; }

                const now = new Date();
                let age = now.getFullYear() - born.getFullYear();
                const m = now.getMonth() - born.getMonth();
                if (m < 0 || (m === 0 && now.getDate() < born.getDate())) age--;

                this.values[f.key] = age >= 0 ? String(age) : '';
            }
        },

        /** Mirrors the spec. Returns true when nothing obvious is missing. */
        validate() {
            this.errors = {};

            for (const key of requiredKeys(spec)) {
                const v = this.values[key];
                const missing = Array.isArray(v) ? v.length === 0
                    : typeof v === 'boolean' ? v === false
                        : String(v ?? '').trim() === '';

                if (missing) {
                    this.errors[key] = this.$store?.lang?.current === 'en'
                        ? 'This field is required.'
                        : 'Kailangan ang field na ito.';
                }
            }

            return Object.keys(this.errors).length === 0;
        },

        hasError(key) {
            return Boolean(this.errors[key]);
        },

        errorFor(key) {
            return this.errors[key] || '';
        },

        async submit() {
            // Guard: a duplicated Tulong application is a duplicated claim.
            if (this.submitting) return;

            this.formError = null;
            this.derive();

            if (!this.validate()) {
                this.focusFirstError();

                return;
            }

            if (typeof submit !== 'function') {
                // No endpoint wired yet. Fail honestly rather than pretending
                // the request succeeded.
                this.formError = this.$store?.lang?.current === 'en'
                    ? 'Submitting is not available yet.'
                    : 'Hindi pa available ang pagsusumite.';

                return;
            }

            this.submitting = true;

            try {
                const result = await submit(this.values, api);

                this.submitted = true;
                this.pristine = JSON.stringify(this.values); // no longer dirty
                if (typeof onSuccess === 'function') onSuccess(result);
            } catch (err) {
                // 422: map server field errors onto the same keys. The user's
                // input is NEVER cleared — a citizen who filled a 19-field PWD
                // registration must not lose it to a 500.
                if (err && err.isValidation && err.fields) {
                    for (const [key, messages] of Object.entries(err.fields)) {
                        this.errors[key] = Array.isArray(messages) ? messages[0] : String(messages);
                    }
                    this.focusFirstError();
                } else {
                    this.formError = err && typeof err.localised === 'function'
                        ? err.localised(this.$store?.lang?.current || 'fil')
                        : (err && err.message) || 'Request failed.';
                }
            } finally {
                this.submitting = false;
            }
        },

        focusFirstError() {
            const key = Object.keys(this.errors)[0];
            if (!key) return;

            this.$nextTick(() => {
                const el = this.$el.querySelector(`[name="${key}"]`);
                if (el && typeof el.focus === 'function') el.focus();
            });
        },

        /** Warn before losing a part-filled government form. */
        guardUnload(event) {
            if (!this.dirty || this.submitted) return;

            event.preventDefault();
            event.returnValue = '';
        },
    };
}

export default specForm;
