{{-- The Resident Profile, filled or corrected on the website (live test
     2026-10-07, UX-8). It could only be done in the Android app, so a resident
     on an iPhone, or one who only uses the website, stayed Unverified. Opened
     with open-resident-profile (detail: the GET /citizen/resident-profile
     view); logic in resources/js/resident-profile-form.js. --}}
@php
    $inputClass = 'w-full rounded-xl border-0 surface-pressed py-2.5 px-3.5 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none';
    $labelClass = 'block text-xs font-medium text-slate-700 mb-1';
@endphp
{{-- view-resident-profile (detail: { view, status }) opens the same form view
     only, once it is sent: For Validation or Verified, every field greyed out,
     no Send, and the app's note (ResidentProfileLockNote) on top (parity check,
     2026-10-26). --}}
<div x-data="residentProfileForm" @open-resident-profile.window="openWith($event.detail)" @view-resident-profile.window="openWith($event.detail?.view, $event.detail?.status)" class="contents">
    <x-ui.modal title="Resident Profile" titleEn="Resident Profile" maxWidth="2xl">
        <div class="space-y-5">
            <p class="text-xs text-ink-muted leading-relaxed" x-show="!readOnly"><x-ui.t fil="Ito ang talaan mo at ng iyong sambahayan sa barangay. Susuriin ito ng iyong barangay office bago ka ma-verify." en="Your record and your household's at the barangay. Your barangay office checks it before you are verified." /></p>

            {{-- The app's lock note, in its words. --}}
            <div x-show="readOnly" x-cloak role="note" data-resident-profile-lock-note
                class="flex items-start gap-2.5 rounded-xl px-3.5 py-3 text-xs leading-relaxed"
                :class="lockedStatus === 'Verified' ? 'bg-emerald-50 text-emerald-700' : 'bg-brand-50 text-brand-700'">
                <i data-lucide="badge-check" class="w-4 h-4 shrink-0 mt-0.5" x-show="lockedStatus === 'Verified'"></i>
                <i data-lucide="hourglass" class="w-4 h-4 shrink-0 mt-0.5" x-show="lockedStatus !== 'Verified'"></i>
                <p x-show="lockedStatus === 'Verified'"><x-ui.t fil="Na-verify ng iyong barangay office ang impormasyon mo at ng iyong sambahayan. Para may baguhin, makipag-ugnayan sa iyong barangay office." en="Your barangay office verified your information and your household's. To change something, ask your barangay office." /></p>
                <p x-show="lockedStatus !== 'Verified'"><x-ui.t fil="Sinusuri ito ng iyong barangay office. Aabisuhan ka kapag tapos na, at maaari mo itong baguhin muli kung ibalik nila ito." en="Your barangay office is reviewing it. You will be told when it is done, and you can change it again if they send it back." /></p>
            </div>

            <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit max-w-full overflow-x-auto" role="tablist">
                @foreach (['personal' => ['Ikaw', 'You'], 'household' => ['Sambahayan', 'Household'], 'family' => ['Pamilya', 'Family']] as $key => [$fil, $en])
                    <button type="button" role="tab" :aria-selected="section === '{{ $key }}'" @click="section = '{{ $key }}'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-xl whitespace-nowrap transition-colors"
                        :class="section === '{{ $key }}' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"><x-ui.t :fil="$fil" :en="$en" /></button>
                @endforeach
            </div>

            {{-- A disabled fieldset turns off every box, choice and button inside it at once. --}}
            <fieldset class="min-w-0 transition-opacity" :disabled="readOnly" :aria-disabled="readOnly ? 'true' : 'false'" :class="readOnly && 'opacity-60 cursor-not-allowed select-text'" data-resident-profile-fields>
            {{-- You --}}
            <div x-show="section === 'personal'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $labelClass }}" for="rp-sex"><x-ui.t fil="Kasarian *" en="Sex *" /></label>
                    <select id="rp-sex" x-model="personal.sex" class="{{ $inputClass }}"><option value="">—</option><template x-for="o in options.sex" :key="o"><option :value="o" x-text="o"></option></template></select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="rp-dob"><x-ui.t fil="Petsa ng kapanganakan *" en="Birthdate *" /></label>
                    <input id="rp-dob" type="date" x-model="personal.dob" :max="new Date().toISOString().slice(0, 10)" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="rp-civil"><x-ui.t fil="Katayuang sibil *" en="Civil status *" /></label>
                    <select id="rp-civil" x-model="personal.civil_status" class="{{ $inputClass }}"><option value="">—</option><template x-for="o in options.civilStatus" :key="o"><option :value="o" x-text="o"></option></template></select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="rp-sitio"><x-ui.t fil="Sitio / Purok *" en="Sitio / Purok *" /></label>
                    <input id="rp-sitio" type="text" x-model="personal.sitio" maxlength="120" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="rp-contact"><x-ui.t fil="Mobile number" en="Mobile number" /></label>
                    <input id="rp-contact" type="tel" inputmode="numeric" maxlength="11" placeholder="09XXXXXXXXX" x-model="personal.contact" @input="personal.contact = window.phMobile.clean($event.target.value)" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="rp-occupation"><x-ui.t fil="Hanapbuhay" en="Occupation" /></label>
                    <input id="rp-occupation" type="text" x-model="personal.occupation" maxlength="100" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="rp-education"><x-ui.t fil="Natapos na pag-aaral" en="Education" /></label>
                    <select id="rp-education" x-model="personal.education" class="{{ $inputClass }}"><option value="">—</option><template x-for="o in options.education" :key="o"><option :value="o" x-text="o"></option></template></select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="rp-income"><x-ui.t fil="Buwanang kita ng sambahayan" en="Household monthly income" /></label>
                    <select id="rp-income" x-model="personal.income_range" class="{{ $inputClass }}"><option value="">—</option><template x-for="o in options.income" :key="o"><option :value="o" x-text="o"></option></template></select>
                </div>
                <div class="sm:col-span-2">
                    <p class="{{ $labelClass }}"><x-ui.t fil="Alin ang tugma sa iyo?" en="Which apply to you?" /></p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="t in options.tags" :key="t">
                            <button type="button" @click="toggleTag(t)" :aria-pressed="personal.tags.includes(t)"
                                class="px-3 py-1.5 rounded-xl text-xs font-medium border transition-colors"
                                :class="personal.tags.includes(t) ? 'bg-brand-600 border-brand-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'" x-text="t"></button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Household --}}
            <div x-show="section === 'household'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $labelClass }}" for="rp-hh-sitio"><x-ui.t fil="Sitio / Purok ng bahay" en="Sitio / Purok of the house" /></label>
                    <input id="rp-hh-sitio" type="text" x-model="household.sitio" maxlength="120" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="rp-hh-address"><x-ui.t fil="Kumpletong address" en="Complete address" /></label>
                    <input id="rp-hh-address" type="text" x-model="household.address" maxlength="255" class="{{ $inputClass }}">
                </div>
                @foreach ([
                    ['housing_tenure', 'tenure', 'Pagmamay-ari ng bahay', 'Home ownership'],
                    ['dwelling_type', 'dwelling', 'Uri ng bahay', 'Type of house'],
                    ['water', 'water', 'Pinagkukunan ng tubig', 'Water source'],
                    ['toilet', 'toilet', 'Palikuran', 'Toilet'],
                    ['electricity', 'electricity', 'Kuryente', 'Electricity'],
                ] as [$field, $opts, $fil, $en])
                    <div>
                        <label class="{{ $labelClass }}" for="rp-hh-{{ $field }}"><x-ui.t :fil="$fil" :en="$en" /></label>
                        <select id="rp-hh-{{ $field }}" x-model="household.{{ $field }}" class="{{ $inputClass }}"><option value="">—</option><template x-for="o in options.{{ $opts }}" :key="o"><option :value="o" x-text="o"></option></template></select>
                    </div>
                @endforeach
            </div>

            {{-- Family --}}
            <div x-show="section === 'family'" x-cloak class="space-y-3">
                <div class="max-w-xs">
                    <label class="{{ $labelClass }}" for="rp-surname"><x-ui.t fil="Apelyido ng pamilya" en="Family surname" /></label>
                    <input id="rp-surname" type="text" x-model="surname" maxlength="100" class="{{ $inputClass }}">
                </div>
                <p class="text-xs text-ink-muted"><x-ui.t fil="Idagdag ang mga kasama mo sa pamilya (hindi kasama ang sarili mo)." en="Add the people in your family (not yourself)." /></p>
                <template x-for="(m, i) in members" :key="i">
                    <div class="rounded-xl border border-slate-200 p-3 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-ink" x-text="(en ? 'Family member ' : 'Kasapi ') + (i + 1)"></p>
                            <button type="button" x-show="!readOnly" @click="removeMember(i)" class="text-xs text-rose-600 hover:text-rose-700"><x-ui.t fil="Alisin" en="Remove" /></button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="{{ $labelClass }}" :for="'rp-m-name-' + i"><x-ui.t fil="Buong pangalan *" en="Full name *" /></label>
                                <input :id="'rp-m-name-' + i" type="text" x-model="m.name" maxlength="160" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" :for="'rp-m-rel-' + i"><x-ui.t fil="Kaugnayan sa iyo" en="Relationship to you" /></label>
                                <select :id="'rp-m-rel-' + i" x-model="m.relation" class="{{ $inputClass }}"><option value="">—</option><template x-for="o in options.relation" :key="o"><option :value="o" x-text="o"></option></template></select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" :for="'rp-m-sex-' + i"><x-ui.t fil="Kasarian" en="Sex" /></label>
                                <select :id="'rp-m-sex-' + i" x-model="m.sex" class="{{ $inputClass }}"><option value="">—</option><template x-for="o in options.sex" :key="o"><option :value="o" x-text="o"></option></template></select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" :for="'rp-m-dob-' + i"><x-ui.t fil="Petsa ng kapanganakan" en="Birthdate" /></label>
                                <input :id="'rp-m-dob-' + i" type="date" x-model="m.dob" :max="new Date().toISOString().slice(0, 10)" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" :for="'rp-m-civil-' + i"><x-ui.t fil="Katayuang sibil" en="Civil status" /></label>
                                <select :id="'rp-m-civil-' + i" x-model="m.civil_status" class="{{ $inputClass }}"><option value="">—</option><template x-for="o in options.civilStatus" :key="o"><option :value="o" x-text="o"></option></template></select>
                            </div>
                        </div>
                    </div>
                </template>
                <p class="text-xs text-ink-muted" x-show="readOnly && !members.length" x-cloak><x-ui.t fil="Walang kasaping idinagdag." en="No family members added." /></p>
                <button type="button" x-show="!readOnly" @click="addMember()" class="inline-flex items-center gap-1.5 rounded-2xl border border-dashed border-slate-300 px-3.5 py-2 text-xs font-medium text-slate-600 hover:border-brand-400 hover:text-brand-700">
                    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i><x-ui.t fil="Magdagdag ng kasapi" en="Add a family member" />
                </button>
            </div>
            </fieldset>

            <p class="text-xs text-rose-600 bg-rose-50 rounded-lg px-3 py-2 break-words" x-show="error" x-text="error" role="alert"></p>
        </div>

        <x-slot:footer>
            {{-- View only: Close, and nothing to send. --}}
            <x-ui.button variant="secondary" size="sm" x-show="readOnly" x-cloak @click="open = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
            <x-ui.button variant="secondary" size="sm" x-show="!readOnly" @click="open = false"><x-ui.t fil="Kanselahin" en="Cancel" /></x-ui.button>
            <x-ui.button size="sm" icon="send" x-show="!readOnly" ::disabled="saving" @click="save()">
                <span x-text="saving ? (en ? 'Sending…' : 'Ipinapadala…') : (en ? 'Send to my barangay' : 'Ipadala sa barangay')"></span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
