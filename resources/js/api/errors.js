/**
 * One error envelope, parsed in one place.
 *
 * The backend contract defines a single error body carrying a machine-readable
 * code and bilingual messages. Every failure in this app becomes one of these,
 * so no caller has to guess at a response shape — and no caller has an excuse
 * to swallow one.
 *
 * ASSUMPTION, to settle with the backend before it ships (see TAB 05):
 * the envelope is assumed to be
 *
 *     { error: { code: 'DOKYU_NOT_FOUND',
 *                message: { fil: '...', en: '...' } | '...',
 *                fields: { field_key: [ '...' ] } } }
 *
 * The parser below degrades safely if it is anything else, rather than throwing
 * a second error while reporting the first.
 */

export class ApiError extends Error {
    constructor({ status, code, messages, fields, retryable, cause }) {
        super(messages?.en || messages?.fil || `Request failed (${status ?? 'network'})`);
        this.name = 'ApiError';
        this.status = status ?? null;
        this.code = code ?? null;
        this.messages = messages ?? {};
        this.fields = fields ?? {};
        this.retryable = Boolean(retryable);
        this.cause = cause;
    }

    /** Message in the reader's current language, falling back sensibly. */
    localised(lang) {
        return this.messages[lang] || this.messages.en || this.messages.fil || this.message;
    }

    /**
     * The first field's own message, in the reader's language. A sign-in
     * failure says what is wrong only here ("Invalid identifier or
     * password", "not verified yet", "locked"); the envelope is the generic
     * "check the highlighted fields". The server writes some of these as
     * "Filipino / English".
     */
    fieldMessage(lang) {
        const first = Object.values(this.fields || {})[0];
        const text = Array.isArray(first) ? first[0] : first;
        if (!text) return null;
        const parts = String(text).split(' / ');
        if (parts.length === 2) return lang === 'en' ? parts[1] : parts[0];
        return String(text);
    }

    /** The field's own reason when there is one, otherwise the envelope message. */
    explain(lang) {
        return this.fieldMessage(lang) || this.localised(lang);
    }

    get isValidation() {
        return this.status === 422;
    }

    get isAuth() {
        return this.status === 401;
    }

    get isForbidden() {
        return this.status === 403;
    }
}

/** Normalise anything axios can hand back into exactly one ApiError. */
export function toApiError(err) {
    // No response at all: DNS, CORS, timeout, offline. Always retryable.
    if (!err.response) {
        const aborted = err.code === 'ERR_CANCELED' || err.name === 'CanceledError';

        return new ApiError({
            status: null,
            code: aborted ? 'REQUEST_CANCELLED' : 'NETWORK_UNREACHABLE',
            messages: aborted
                ? { en: 'Request cancelled.', fil: 'Kanselado ang kahilingan.' }
                : {
                    en: 'Could not reach the server. Check your connection and try again.',
                    fil: 'Hindi maabot ang server. Pakisuri ang koneksyon at subukang muli.',
                },
            retryable: !aborted,
            cause: err,
        });
    }

    const { status, data } = err.response;
    const envelope = data && typeof data === 'object' ? (data.error ?? data) : {};

    let messages = {};
    if (typeof envelope.message === 'string') {
        messages = { en: envelope.message };
    } else if (envelope.message && typeof envelope.message === 'object') {
        messages = envelope.message;
    }

    if (!messages.en && !messages.fil) {
        messages = defaultMessageFor(status);
    }

    return new ApiError({
        status,
        code: envelope.code ?? null,
        messages,
        fields: envelope.fields ?? envelope.errors ?? {},
        // 5xx and 429 are worth another attempt; 4xx generally is not.
        retryable: status >= 500 || status === 429,
        cause: err,
    });
}

function defaultMessageFor(status) {
    if (status === 401) {
        return { en: 'Your session has expired. Please sign in again.', fil: 'Nag-expire na ang session. Mangyaring mag-sign in muli.' };
    }
    if (status === 403) {
        return { en: 'Your account does not have permission to do this.', fil: 'Walang pahintulot ang iyong account para dito.' };
    }
    if (status === 404) {
        return { en: 'That record could not be found.', fil: 'Hindi natagpuan ang rekord na iyon.' };
    }
    if (status === 422) {
        return { en: 'Please check the highlighted fields.', fil: 'Pakisuri ang mga na-highlight na field.' };
    }
    if (status === 429) {
        return { en: 'Too many requests. Please wait a moment.', fil: 'Masyadong maraming kahilingan. Maghintay sandali.' };
    }
    if (status >= 500) {
        return { en: 'Something went wrong on our end. Please try again.', fil: 'May nangyaring mali sa aming panig. Subukang muli.' };
    }

    return { en: 'The request could not be completed.', fil: 'Hindi natapos ang kahilingan.' };
}
