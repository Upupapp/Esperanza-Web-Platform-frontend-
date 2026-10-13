{{-- What a kept ID says (owner, 2026-10-13), for its card in the wallet:
     the number by the name it goes by on that kind of ID, the name, birth
     date, sex, address, expiry and the kind's own fields. Typed here; the
     mobile app reads them off the photos for the resident to check. Used by
     Add ID and Edit details in x-citizen.digital-id-wallet, whose `details`,
     `form` and helpers it reads. --}}
<div class="space-y-4">
    <label class="block">
        <span class="text-xs font-medium text-slate-700" x-text="form.kind === 'other' ? t('ID number', 'Numero ng ID') : t('ID number', 'Numero ng ID') + ' (' + design(form.kind).number + ')'"></span>
        <input type="text" maxlength="40" x-model="details.id_number" :placeholder="t('As printed on the ID', 'Gaya ng nakalimbag sa ID')" class="mt-1 w-full text-sm rounded-xl border border-slate-200 px-3 py-2 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none">
    </label>
    <label class="block">
        <span class="text-xs font-medium text-slate-700"><x-ui.t fil="Pangalan gaya ng nakalimbag" en="Name as printed" /></span>
        <input type="text" maxlength="160" x-model="details.holder_name" class="mt-1 w-full text-sm rounded-xl border border-slate-200 px-3 py-2 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none">
    </label>
    <label class="block">
        <span class="text-xs font-medium text-slate-700"><x-ui.t fil="Petsa ng kapanganakan" en="Date of birth" /></span>
        <input type="date" x-model="details.birthdate" class="mt-1 w-full text-sm rounded-xl border border-slate-200 px-3 py-2 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none">
    </label>
    <label class="block" x-show="usesSex(form.kind)">
        <span class="text-xs font-medium text-slate-700"><x-ui.t fil="Kasarian" en="Sex" /></span>
        <select x-model="details.sex" class="mt-1 w-full text-sm rounded-xl border border-slate-200 px-3 py-2 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none">
            <option value="" x-text="t('Not entered', 'Wala pang nailagay')"></option>
            <option value="Male" x-text="t('Male', 'Lalaki')"></option>
            <option value="Female" x-text="t('Female', 'Babae')"></option>
        </select>
    </label>
    <label class="block">
        <span class="text-xs font-medium text-slate-700"><x-ui.t fil="Tirahan" en="Address" /></span>
        <textarea rows="2" maxlength="255" x-model="details.address" class="mt-1 w-full text-sm rounded-xl border border-slate-200 px-3 py-2 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
    </label>
    <label class="block">
        <span class="text-xs font-medium text-slate-700"><x-ui.t fil="Hanggang kailan valid (kung nakalimbag sa ID)" en="Valid until (if printed on the ID)" /></span>
        <input type="date" x-model="form.expires" class="mt-1 w-full text-sm rounded-xl border border-slate-200 px-3 py-2 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none">
    </label>
    <template x-for="key in extrasFor(form.kind)" :key="key">
        <label class="block">
            <span class="text-xs font-medium text-slate-700" x-text="fieldLabel(key) + (key === 'registered_on' ? ' (yyyy-mm-dd)' : '')"></span>
            <input type="text" maxlength="120" x-model="details.extras[key]" class="mt-1 w-full text-sm rounded-xl border border-slate-200 px-3 py-2 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none">
        </label>
    </template>
</div>
