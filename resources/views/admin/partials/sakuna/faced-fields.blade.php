{{--
    faced-fields.blade.php
    The registration side of the DSWD FACED card, shared by the check-in form
    and the evacuee detail's edit form (MSWDO forms, 2026-10-26).
    $model: the Alpine object the fields bind to; $idPrefix: unique input ids.
--}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="{{ $idPrefix }}-serial">FACED Serial No.</label>
        <input id="{{ $idPrefix }}-serial" type="text" x-model="{{ $model }}.faced_serial" class="w-full px-3 py-2 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
    </div>
    <div>
        <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="{{ $idPrefix }}-contact">Contact No.</label>
        <input id="{{ $idPrefix }}-contact" type="tel" x-model="{{ $model }}.contact" class="w-full px-3 py-2 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
    </div>
    <div>
        <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="{{ $idPrefix }}-ownership">House Ownership</label>
        <select id="{{ $idPrefix }}-ownership" x-model="{{ $model }}.house_ownership" class="w-full px-3 py-2 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">—</option>
            <template x-for="o in houseOwnershipChoices" :key="o"><option :value="o" x-text="o"></option></template>
        </select>
    </div>
    <div>
        <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="{{ $idPrefix }}-damage">Shelter Damage</label>
        <select id="{{ $idPrefix }}-damage" x-model="{{ $model }}.shelter_damage" class="w-full px-3 py-2 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            <option value="">—</option>
            <template x-for="o in shelterDamageChoices" :key="o"><option :value="o" x-text="o"></option></template>
        </select>
    </div>
    <div>
        <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="{{ $idPrefix }}-income">Monthly Family Net Income</label>
        <input id="{{ $idPrefix }}-income" type="text" x-model="{{ $model }}.monthly_income" placeholder="e.g. ₱6,000" class="w-full px-3 py-2 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
    </div>
    <div class="flex flex-wrap items-end gap-4 pb-1.5">
        <label class="inline-flex items-center gap-2 text-sm text-ink-body"><input type="checkbox" x-model="{{ $model }}.is_4ps" class="rounded"> 4Ps beneficiary</label>
        <label class="inline-flex items-center gap-2 text-sm text-ink-body"><input type="checkbox" x-model="{{ $model }}.is_ip" class="rounded"> Indigenous People</label>
    </div>
    <div class="sm:col-span-2" x-show="{{ $model }}.is_ip" x-cloak>
        <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="{{ $idPrefix }}-ip">IP Ethnicity</label>
        <input id="{{ $idPrefix }}-ip" type="text" x-model="{{ $model }}.ip_ethnicity" class="w-full px-3 py-2 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
    </div>
</div>
