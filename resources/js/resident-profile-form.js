import api from './api/client';

// The Resident Profile on the website (live test 2026-10-07, UX-8): it could
// only be filled or corrected in the Android app, so a resident on an iPhone,
// or one who only uses the website, stayed Unverified. Same questions and the
// same choices as the app (ResidentProfileOptions), sent to the same
// PUT /citizen/resident-profile.
const options = {
    sex: ['Male', 'Female'],
    civilStatus: ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'],
    education: ['No Formal Education', 'Elementary', 'High School', 'Senior High School', 'Vocational', 'College', 'Post Graduate'],
    relation: ['Spouse', 'Son', 'Daughter', 'Father', 'Mother', 'Parent', 'Sibling', 'Grandchild', 'Other Relative', 'Non-Relative'],
    income: ['No Income', 'Below ₱5,000', '₱5,000–₱9,999', '₱10,000–₱14,999', '₱15,000–₱24,999', '₱25,000–₱49,999', '₱50,000 and above'],
    tags: ['Senior Citizen', 'PWD', 'Solo Parent', 'Registered Voter', '4Ps'],
    tenure: ['Owned', 'Renting', 'Rent-Free with Consent', 'Informal Settler'],
    dwelling: ['Concrete', 'Semi-Concrete', 'Wood', 'Mixed / Light Materials'],
    water: ['Piped / Communal System', 'Deep Well', 'Shallow Well', 'Bottled / Refilled', 'Other'],
    toilet: ['Water-Sealed (Own Use)', 'Water-Sealed (Shared)', 'Open Pit', 'None'],
    electricity: ['Grid-Connected (Meralco/Coop)', 'Solar', 'Shared / Informal Connection', 'None'],
};

// The occupation is the two DRRM dropdowns (owner, 2026-10-29); `_lv` keeps
// what was loaded, so an unchanged choice is not re-sent as a new one.
const blankLivelihood = () => ({ livelihood_code: '', livelihood_other: '', _lv: { code: '', other: '' } });
const livelihoodOf = (x) => ({ livelihood_code: x?.livelihood?.code || '', livelihood_other: x?.livelihood?.other || '', _lv: { code: x?.livelihood?.code || '', other: x?.livelihood?.other || '' } });
const blankPersonal = () => ({ sex: '', dob: '', civil_status: '', sitio: '', contact: '', occupation: '', ...blankLivelihood(), education: '', income_range: '', tags: [] });
const blankHousehold = () => ({ sitio: '', address: '', dwelling_type: '', housing_tenure: '', electricity: '', water: '', toilet: '' });
const blankMember = () => ({ ref: null, name: '', sex: '', dob: '', civil_status: '', relation: '', occupation: '', ...blankLivelihood(), education: '', contact: '' });

// The record's contact is the email when the resident gave no mobile number:
// only a Philippine mobile number belongs in a Mobile number box (retest
// 2026-10-07: an email became "42607" here, and the form could not be sent).
const mobileOrEmpty = (v) => {
    const raw = String(v ?? '');
    if (raw.includes('@')) return '';
    const digits = window.phMobile.clean(raw.replace(/^\+?63/, '0'));
    return /^09\d{9}$/.test(digits) ? digits : '';
};

// The record statuses in which the profile is shown, not edited (axis 3).
const LOCKED_STATUSES = ['For Validation', 'Verified'];

const text = (v) => {
    const t = String(v ?? '').trim();
    return t === '' || t === '—' ? null : t;
};

export function residentProfileForm() {
    return {
        open: false,
        saving: false,
        error: '',
        section: 'personal',
        options,
        personal: blankPersonal(),
        household: blankHousehold(),
        // The household's registry code, read only: the server assigns it (2026-10-27).
        householdRef: '',
        surname: '',
        members: [],
        // View only once sent (parity check, 2026-10-26): while the barangay
        // checks it (For Validation) or once it is Verified, the resident sees
        // what they sent, greyed out, and cannot change or resend it - the
        // app's ResidentProfile.editable and ResidentProfileLockNote.
        readOnly: false,
        lockedStatus: null,

        get en() { return this.$store.lang.current === 'en'; },

        // Opened from the profile page with what the server already holds, so
        // a correction starts from the record the barangay sent back.
        openWith(view, status = null) {
            const lockedStatus = status ?? view?.individual?.verification ?? null;
            this.readOnly = LOCKED_STATUSES.includes(lockedStatus);
            this.lockedStatus = this.readOnly ? lockedStatus : null;
            const me = view?.individual || {};
            const hh = view?.household || {};
            const fam = view?.family || {};
            const account = this.$store.citizenSession.account || {};
            this.personal = {
                ...blankPersonal(),
                sex: me.sex || account.sex || '',
                dob: (me.dob || '').slice(0, 10),
                civil_status: me.civil_status || '',
                sitio: me.sitio || '',
                contact: mobileOrEmpty(me.contact) || mobileOrEmpty(account.mobile),
                occupation: me.occupation || '',
                ...livelihoodOf(me),
                education: me.education || '',
                income_range: me.income_range || '',
                tags: Array.isArray(me.tags) ? me.tags.filter((t) => options.tags.includes(t)) : [],
            };
            this.householdRef = hh.ref || '';
            this.household = {
                ...blankHousehold(),
                sitio: hh.sitio || me.sitio || '',
                address: hh.address || '',
                dwelling_type: hh.dwelling_type || '',
                housing_tenure: hh.housing_tenure || '',
                electricity: hh.electricity || '',
                water: hh.water || '',
                toilet: hh.toilet || '',
            };
            this.surname = fam.surname || account.lastName || '';
            this.members = (fam.members || []).map((m) => ({
                ...blankMember(),
                ref: m.ref || null,
                name: m.name || '',
                sex: m.sex || '',
                dob: (m.dob || '').slice(0, 10),
                civil_status: m.civil_status || '',
                relation: m.relation || '',
                occupation: m.occupation || '',
                ...livelihoodOf(m),
                education: m.education || '',
                contact: mobileOrEmpty(m.contact),
            }));
            this.section = 'personal';
            this.error = '';
            this.open = true;
            this.$nextTick(() => window.renderIcons?.());
        },

        toggleTag(tag) {
            if (this.readOnly) return;
            const i = this.personal.tags.indexOf(tag);
            if (i === -1) this.personal.tags.push(tag); else this.personal.tags.splice(i, 1);
        },
        addMember() {
            if (this.readOnly) return;
            this.members.push(blankMember());
            this.$nextTick(() => window.renderIcons?.());
        },
        removeMember(i) { if (!this.readOnly) this.members.splice(i, 1); },

        // What is still missing before the barangay can check it.
        get problems() {
            const out = [];
            const p = this.personal;
            if (!p.sex) out.push(this.en ? 'Sex' : 'Kasarian');
            if (!p.dob) out.push(this.en ? 'Birthdate' : 'Petsa ng kapanganakan');
            if (!p.civil_status) out.push(this.en ? 'Civil status' : 'Katayuang sibil');
            if (!p.sitio) out.push(this.en ? 'Sitio / Purok' : 'Sitio / Purok');
            const phone = window.phMobile.error(p.contact, false, this.$store.lang.current);
            if (phone) out.push('Mobile number: ' + phone);
            this.members.forEach((m, i) => {
                if (!text(m.name)) out.push((this.en ? 'Family member ' : 'Kasapi ') + (i + 1) + (this.en ? ': name' : ': pangalan'));
                if (m.dob && m.dob >= new Date().toISOString().slice(0, 10)) out.push((this.en ? 'Family member ' : 'Kasapi ') + (i + 1) + (this.en ? ': birthdate' : ': petsa ng kapanganakan'));
            });
            return out;
        },

        payload() {
            const person = (x) => ({
                sex: text(x.sex), dob: text(x.dob), civil_status: text(x.civil_status),
                education: text(x.education), contact: text(x.contact),
                ...occupation(x),
            });
            // A changed choice is sent as the code; otherwise the words, which
            // the server leaves alone when they are the same.
            const occupation = (x) => ((x.livelihood_code || '') !== x._lv.code || (x.livelihood_other || '') !== x._lv.other)
                ? { livelihood_code: x.livelihood_code || null, livelihood_other: text(x.livelihood_other), ...(x.livelihood_code ? {} : { occupation: text(x.occupation) }) }
                : { occupation: text(x.occupation) };
            return {
                personal: {
                    ...person(this.personal),
                    sitio: text(this.personal.sitio),
                    income_range: text(this.personal.income_range),
                    tags: [...this.personal.tags],
                },
                household: Object.fromEntries(Object.entries(this.household).map(([k, v]) => [k, text(v)])),
                family: {
                    surname: text(this.surname),
                    members: this.members.filter((m) => text(m.name)).map((m) => ({
                        ...(m.ref ? { ref: m.ref } : {}),
                        name: m.name.trim(),
                        ...person(m),
                        relation: text(m.relation),
                    })),
                },
            };
        },

        async save() {
            if (this.saving || this.readOnly) return;
            if (this.problems.length) {
                this.error = (this.en ? 'Please complete: ' : 'Pakikumpleto ang: ') + this.problems.join(', ');
                return;
            }
            this.saving = true;
            this.error = '';
            try {
                const res = await api.put('/citizen/resident-profile', this.payload());
                this.open = false;
                this.$dispatch('resident-profile-saved', res.data || null);
                this.$dispatch('toast', {
                    message: this.en
                        ? 'Resident Profile sent. Your barangay office will check it and tell you when it is done.'
                        : 'Naipadala ang Resident Profile. Susuriin ito ng iyong barangay office at aabisuhan ka kapag tapos na.',
                    variant: 'success',
                });
            } catch (err) {
                this.error = err.localised ? err.localised(this.$store.lang.current) : (this.en ? 'Could not send the Resident Profile.' : 'Hindi naipadala ang Resident Profile.');
            } finally {
                this.saving = false;
            }
        },
    };
}
