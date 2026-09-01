{{--
    tab-bdrrmc-forms.blade.php
    Exact-replica BDRRMC capture forms supplied by Barangay Labangtaytay —
    Profile of Evacuees, Human & Housing Damages, Agriculture Damages,
    Fishing Damages, Business Damages, and the Relief Distribution Sheet (RDS).
    Included by sakuna.blade.php — local x-data scoped here, parent state untouched.
--}}

@php
    // TAB 04: fixtures live in resources/data/admin/partials/sakuna/tab-bdrrmc-forms.php so this view stays presentation only.
    $__fixtures = require base_path('resources/data/admin/partials/sakuna/tab-bdrrmc-forms.php');
    $bdrrmcForms = $__fixtures['bdrrmcForms'];
@endphp

<div
    x-data="{
        openForm: null,
        disasterType: 'Southwest Monsoon & Flooding',
        purok: '',

        evacueeRows: [
            { purok: '9', hh: 'LBT-0091', head: 'Rodrigo M. Panganiban', pre03m:0,pre03f:0,pre46m:1,pre46f:0,ch712m:1,ch712f:1,yth1317m:0,yth1317f:1,ad1859m:1,ad1859f:1,sc60m:0,sc60f:1, assist:'Food Pack, Hygiene Kit' },
            { purok: '10', hh: 'LBT-0134', head: 'Nenita B. Cruz', pre03m:0,pre03f:1,pre46m:0,pre46f:0,ch712m:0,ch712f:0,yth1317m:1,yth1317f:0,ad1859m:1,ad1859f:1,sc60m:0,sc60f:0, assist:'Food Pack' },
        ],
        addEvacueeRow() { this.evacueeRows.push({ purok:'', hh:'', head:'', pre03m:0,pre03f:0,pre46m:0,pre46f:0,ch712m:0,ch712f:0,yth1317m:0,yth1317f:0,ad1859m:0,ad1859f:0,sc60m:0,sc60f:0, assist:'' }); },
        evacueeFamTotal(r) { return (r.pre03m+r.pre03f+r.pre46m+r.pre46f+r.ch712m+r.ch712f+r.yth1317m+r.yth1317f+r.ad1859m+r.ad1859f+r.sc60m+r.sc60f) || 0; },
        evacueeGrandTotal() { return this.evacueeRows.reduce((s,r) => s + this.evacueeFamTotal(r), 0); },

        housingRows: [
            { hh:'LBT-0091', head:'Rodrigo M. Panganiban', deps:5, cas_m:0,cas_f:0, miss_m:0,miss_f:0, resc_m:0,resc_f:0, roof_slight_nipa:1,roof_slight_gi:0, roof_partial_nipa:0,roof_partial_gi:0, roof_total_nipa:0,roof_total_gi:0, floodHeight:'2', estPartial:8000, estTotal:0 },
        ],
        addHousingRow() { this.housingRows.push({ hh:'', head:'', deps:0, cas_m:0,cas_f:0, miss_m:0,miss_f:0, resc_m:0,resc_f:0, roof_slight_nipa:0,roof_slight_gi:0, roof_partial_nipa:0,roof_partial_gi:0, roof_total_nipa:0,roof_total_gi:0, floodHeight:'', estPartial:0, estTotal:0 }); },

        agriRows: [
            { hh:'LBT-0077', owner:'Teodato M. Rodrigo', livestockType:'Native Pig', heads:3, age:'6 mos.', crop:'Palay (Rice)', stage:'Vegetative', areaAffected:'0.5 ha', product:'Grain', lossRecovery:'With chance of recovery', metricTons:0.4 },
        ],
        addAgriRow() { this.agriRows.push({ hh:'', owner:'', livestockType:'', heads:'', age:'', crop:'', stage:'', areaAffected:'', product:'', lossRecovery:'', metricTons:'' }); },

        fishingRows: [
            { hh:'LBT-0102', owner:'Monie C. Bruza', qty:2, gear:'Pukot (fishnet)', damage:'Partial', estDamage:3500 },
        ],
        addFishingRow() { this.fishingRows.push({ hh:'', owner:'', qty:'', gear:'', damage:'', estDamage:'' }); },

        businessRows: [
            { code:'BC-04-2026', owner:'Cherry Ann S. Andales', establishment:'Andales Sari-Sari Store', type:'Store', workers:1, merch:'Partial', equip:'None', struct:'Partial', floodHeight:'2', estDamage:6500 },
        ],
        addBusinessRow() { this.businessRows.push({ code:'', owner:'', establishment:'', type:'', workers:'', merch:'', equip:'', struct:'', floodHeight:'', estDamage:'' }); },

        rdsRows: [
            { hh:'LBT-0091', beneficiary:'Rodrigo M. Panganiban', deps:5, kind:'Family Food Pack', qty:'1 pack', received:true },
            { hh:'LBT-0134', beneficiary:'Nenita B. Cruz', deps:4, kind:'Family Food Pack', qty:'1 pack', received:true },
        ],
        addRdsRow() { this.rdsRows.push({ hh:'', beneficiary:'', deps:'', kind:'', qty:'', received:false }); },

        print() { this.$dispatch('toast', { message: 'Form sent to printer.', variant: 'info' }); },
        save() { this.$dispatch('toast', { message: 'Capture form saved.', variant: 'success' }); this.openForm = null; },
    }"
    class="space-y-5"
>
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-lg font-bold text-slate-800">BDRRMC Capture Forms</h2>
            <p class="text-sm text-slate-500 mt-0.5">Barangay Labangtaytay's official DRRM capture forms — fill out, print, and export in the exact format submitted to MDRRMO/PDRRMC.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        @foreach($bdrrmcForms as $f)
            <div class="bg-white rounded-2xl shadow-card border border-slate-100 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl {{ $f['color'] }} flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $f['icon'] }}" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">{{ $f['code'] }}</p>
                        <p class="text-sm font-semibold text-slate-800 leading-snug">{{ $f['name'] }}</p>
                    </div>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed mb-3">{{ $f['desc'] }}</p>
                <button
                    @click="openForm = '{{ $f['key'] }}'; $nextTick(() => window.renderIcons?.())"
                    class="w-full text-xs font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-lg py-2 transition-colors"
                >
                    Open Capture Form
                </button>
            </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════ 1. PROFILE OF EVACUEES ══════════════════════════════════════ --}}
    <template x-if="openForm === 'evacuees'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Dromic Form 1.2</p>
                    <h3 class="font-semibold text-slate-800">Profile of Evacuees Inside the Evacuation Center</h3>
                </div>
                <button @click="openForm = null" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Region</label><input type="text" value="Region V" readonly class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm bg-slate-50 text-slate-500 cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Barangay</label><input type="text" value="LABANGTAYTAY" readonly class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm bg-slate-50 text-slate-500 cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Type of Disaster</label><input type="text" x-model="disasterType" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Name of Evacuation Center</label><input type="text" placeholder="e.g. Labangtaytay Barangay Hall" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-sky-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-slate-500" rowspan="2">Purok</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500" rowspan="2">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500" rowspan="2">Family Head</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Pre-Schooler 0-3</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Pre-Schooler 4-6</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Children 7-12</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Youth 13-17</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Adult 18-59</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Senior 60+</th>
                                <th class="px-2 py-2 text-center font-semibold text-slate-500" rowspan="2">Total</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500" rowspan="2">Assistance Extended</th>
                                <th class="px-2 py-2" rowspan="2"></th>
                            </tr>
                            <tr class="bg-sky-50/70 border-b border-slate-200 text-[10px] text-sky-600">
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(r, ri) in evacueeRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input type="text" x-model="r.purok" class="w-12 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.hh" class="w-20 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.head" class="w-36 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.pre03m" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.pre03f" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.pre46m" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.pre46f" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.ch712m" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.ch712f" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.yth1317m" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.yth1317f" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.ad1859m" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.ad1859f" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.sc60m" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.sc60f" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-2 py-1 text-center font-semibold text-slate-700" x-text="evacueeFamTotal(r)"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.assist" class="w-32 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><button @click="evacueeRows.splice(ri,1)" class="text-slate-300 hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-700">
                                <td colspan="15" class="px-2 py-2 text-right">Grand Total</td>
                                <td class="px-2 py-2 text-center" x-text="evacueeGrandTotal()"></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button @click="addEvacueeRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-slate-500 hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Family Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Barangay Official (Evacuation Center)" consolidated-name="JENNETH A. CACHILA" consolidated-label="Barangay Secretary" noted-name="JORGE C. BRUZA" noted-label="Punong Barangay" />
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> Save Form</button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════ 2. HUMAN, HOUSING & FLOODING ══════════════════════════════════ --}}
    <template x-if="openForm === 'human-housing'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-slate-800">"Human, Housing & Flooding" Calamity/Damages Capture and Assessment Form</h3>
                </div>
                <button @click="openForm = null" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Barangay</label><input type="text" value="LABANGTAYTAY" readonly class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm bg-slate-50 text-slate-500 cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Purok</label><input type="text" x-model="purok" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Type of Disaster</label><input type="text" x-model="disasterType" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-rose-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-slate-500" rowspan="2">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500" rowspan="2">Head of Family</th>
                                <th class="px-2 py-2 text-center font-semibold text-slate-500" rowspan="2">No. of Dependents</th>
                                <th class="px-2 py-1 text-center font-semibold text-rose-700" colspan="2">Casualty</th>
                                <th class="px-2 py-1 text-center font-semibold text-rose-700" colspan="2">Missing</th>
                                <th class="px-2 py-1 text-center font-semibold text-rose-700" colspan="2">Rescued</th>
                                <th class="px-2 py-1 text-center font-semibold text-orange-700" colspan="6">Roof Damage (Nipa / GI Sheets)</th>
                                <th class="px-2 py-2 text-center font-semibold text-slate-500" rowspan="2">Flood Height*</th>
                                <th class="px-2 py-1 text-center font-semibold text-emerald-700" colspan="2">Estimated Damage (₱)</th>
                                <th class="px-2 py-2" rowspan="2"></th>
                            </tr>
                            <tr class="bg-rose-50/70 border-b border-slate-200 text-[10px] text-rose-600">
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center text-orange-600">Slight-Nipa</th><th class="px-1 py-1 text-center text-orange-600">Slight-GI</th>
                                <th class="px-1 py-1 text-center text-orange-600">Partial-Nipa</th><th class="px-1 py-1 text-center text-orange-600">Partial-GI</th>
                                <th class="px-1 py-1 text-center text-orange-600">Total-Nipa</th><th class="px-1 py-1 text-center text-orange-600">Total-GI</th>
                                <th class="px-1 py-1 text-center text-emerald-600">Partial</th><th class="px-1 py-1 text-center text-emerald-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(r, ri) in housingRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input type="text" x-model="r.hh" class="w-20 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.head" class="w-32 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="number" min="0" x-model.number="r.deps" class="w-12 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.cas_m" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.cas_f" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.miss_m" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.miss_f" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.resc_m" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.resc_f" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.roof_slight_nipa" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.roof_slight_gi" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.roof_partial_nipa" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.roof_partial_gi" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.roof_total_nipa" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.roof_total_gi" class="w-9 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1">
                                        <select x-model="r.floodHeight" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">—</option>
                                            <option value="1">(1) Ankle</option>
                                            <option value="2">(2) Knee</option>
                                            <option value="3">(3) Waist</option>
                                            <option value="4">(4) Chest</option>
                                            <option value="5">(5) Head</option>
                                        </select>
                                    </td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.estPartial" class="w-16 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-right focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-0.5 py-1"><input type="number" min="0" x-model.number="r.estTotal" class="w-16 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-right focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><button @click="housingRows.splice(ri,1)" class="text-slate-300 hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="text-[10px] text-slate-400">*Levels of flood height: (1) Ankle; (2) Knee; (3) Waist; (4) Chest; (5) Head</p>
                <button @click="addHousingRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-slate-500 hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Household Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-name="JENNETH A. CACHILA" consolidated-label="Barangay Secretary" noted-name="JORGE C. BRUZA" noted-label="Punong Barangay" />
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> Save Form</button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════════ 3. AGRICULTURE DAMAGES ══════════════════════════════════════ --}}
    <template x-if="openForm === 'agriculture'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-slate-800">"Agricultural Crops & Livestock" Calamity Damages Captured Assessment Form</h3>
                </div>
                <button @click="openForm = null" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Barangay</label><input type="text" value="LABANGTAYTAY" readonly class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm bg-slate-50 text-slate-500 cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Name of Disaster</label><input type="text" x-model="disasterType" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Date of Occurrence</label><input type="date" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-amber-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Affected Owner/Farmer</th>
                                <th class="px-2 py-2 text-left font-semibold text-amber-700">Type of Livestock</th>
                                <th class="px-2 py-2 text-center font-semibold text-amber-700">No. of Heads</th>
                                <th class="px-2 py-2 text-center font-semibold text-amber-700">Age</th>
                                <th class="px-2 py-2 text-left font-semibold text-emerald-700">Type of Crop Planted</th>
                                <th class="px-2 py-2 text-left font-semibold text-emerald-700">Stage of Crop</th>
                                <th class="px-2 py-2 text-left font-semibold text-emerald-700">Area Affected</th>
                                <th class="px-2 py-2 text-left font-semibold text-emerald-700">Type of Product</th>
                                <th class="px-2 py-2 text-left font-semibold text-emerald-700">Production Loss</th>
                                <th class="px-2 py-2 text-center font-semibold text-emerald-700">Metric Tons</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(r, ri) in agriRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input type="text" x-model="r.hh" class="w-20 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.owner" class="w-32 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.livestockType" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="number" min="0" x-model.number="r.heads" class="w-14 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.age" class="w-16 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.crop" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1">
                                        <select x-model="r.stage" class="w-28 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">—</option>
                                            <option>Vegetative</option>
                                            <option>Productive</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.areaAffected" class="w-20 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.product" class="w-20 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1">
                                        <select x-model="r.lossRecovery" class="w-36 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">—</option>
                                            <option>With chance of recovery</option>
                                            <option>No chance of recovery</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1"><input type="number" step="0.1" min="0" x-model.number="r.metricTons" class="w-16 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><button @click="agriRows.splice(ri,1)" class="text-slate-300 hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button @click="addAgriRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-slate-500 hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Farmer Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-name="JENNETH A. CACHILA" consolidated-label="Barangay Secretary" noted-name="JORGE C. BRUZA" noted-label="Punong Barangay/BDRRMC Chairperson" />
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> Save Form</button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════════ 4. FISHING DAMAGES ══════════════════════════════════════ --}}
    <template x-if="openForm === 'fishing'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-slate-800">"Fishing" Calamity Damages Captured Assessment Form</h3>
                </div>
                <button @click="openForm = null" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Barangay</label><input type="text" value="LABANGTAYTAY" readonly class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm bg-slate-50 text-slate-500 cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Purok</label><input type="text" x-model="purok" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Name & Type of Disaster</label><input type="text" x-model="disasterType" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Date of Occurrence</label><input type="date" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-cyan-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Owner</th>
                                <th class="px-2 py-2 text-center font-semibold text-slate-500">Qty.</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Fishing Gear / Equipment</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Description of Damage</th>
                                <th class="px-2 py-2 text-right font-semibold text-slate-500">Estimated Damage (₱)</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(r, ri) in fishingRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input type="text" x-model="r.hh" class="w-20 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.owner" class="w-36 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="number" min="0" x-model.number="r.qty" class="w-14 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.gear" class="w-40 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1">
                                        <select x-model="r.damage" class="w-28 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">—</option>
                                            <option>Partial</option>
                                            <option>Total</option>
                                            <option>Missing</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1"><input type="number" min="0" x-model.number="r.estDamage" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-right focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><button @click="fishingRows.splice(ri,1)" class="text-slate-300 hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button @click="addFishingRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-slate-500 hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Owner Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-name="JENNETH A. CACHILA" consolidated-label="Barangay Secretary" noted-name="JORGE C. BRUZA" noted-label="Punong Barangay" />
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> Save Form</button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════════ 5. BUSINESS DAMAGES ══════════════════════════════════════ --}}
    <template x-if="openForm === 'business'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-slate-800">"Business" Calamity Damages Captured Assessment Form</h3>
                </div>
                <button @click="openForm = null" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Barangay</label><input type="text" value="LABANGTAYTAY" readonly class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm bg-slate-50 text-slate-500 cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Purok</label><input type="text" x-model="purok" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Disaster</label><input type="text" x-model="disasterType" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Date of Occurrence</label><input type="date" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-violet-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Business Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Full Name of Owner</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Establishment/Store</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Business Type</th>
                                <th class="px-2 py-2 text-center font-semibold text-slate-500">No. of Workers</th>
                                <th class="px-2 py-2 text-center font-semibold text-violet-700">Merchandise (Total/Partial)</th>
                                <th class="px-2 py-2 text-center font-semibold text-violet-700">Equipment (Total/Partial)</th>
                                <th class="px-2 py-2 text-center font-semibold text-violet-700">Structures (Total/Partial)</th>
                                <th class="px-2 py-2 text-center font-semibold text-slate-500">Flood Height*</th>
                                <th class="px-2 py-2 text-right font-semibold text-slate-500">Estimated Damage (₱)</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(r, ri) in businessRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input type="text" x-model="r.code" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.owner" class="w-32 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.establishment" class="w-32 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.type" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard" placeholder="store, products, food, rental"></td>
                                    <td class="px-1 py-1"><input type="number" min="0" x-model.number="r.workers" class="w-14 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1">
                                        <select x-model="r.merch" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">—</option><option>Partial</option><option>Total</option><option>None</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1">
                                        <select x-model="r.equip" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">—</option><option>Partial</option><option>Total</option><option>None</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1">
                                        <select x-model="r.struct" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">—</option><option>Partial</option><option>Total</option><option>None</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1">
                                        <select x-model="r.floodHeight" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                            <option value="">—</option>
                                            <option value="1">(1) Ankle</option><option value="2">(2) Knee</option><option value="3">(3) Waist</option><option value="4">(4) Chest</option><option value="5">(5) Head</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1"><input type="number" min="0" x-model.number="r.estDamage" class="w-24 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-right focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><button @click="businessRows.splice(ri,1)" class="text-slate-300 hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="text-[10px] text-slate-400">*Levels of flood height: (1) Ankle; (2) Knee; (3) Waist; (4) Chest; (5) Head</p>
                <button @click="addBusinessRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-slate-500 hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Business Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-name="JENNETH A. CACHILA" consolidated-label="Barangay Secretary" noted-name="JORGE C. BRUZA" noted-label="Punong Barangay/BDRRMC Chairperson" />
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> Save Form</button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════════ 6. RELIEF DISTRIBUTION SHEET ══════════════════════════════════════ --}}
    <template x-if="openForm === 'rds'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-2xl shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">RDS</p>
                    <h3 class="font-semibold text-slate-800">Relief Distribution Sheet</h3>
                </div>
                <button @click="openForm = null" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Barangay</label><input type="text" value="LABANGTAYTAY" readonly class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm bg-slate-50 text-slate-500 cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Name of Evacuation Center</label><input type="text" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Type of Disaster</label><input type="text" x-model="disasterType" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-slate-400 uppercase tracking-wide mb-1">Date of Relief Distribution</label><input type="date" class="w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-emerald-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Name of Beneficiary</th>
                                <th class="px-2 py-2 text-center font-semibold text-slate-500">No. of Dependents</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Kind/Type</th>
                                <th class="px-2 py-2 text-left font-semibold text-slate-500">Qty/Unit</th>
                                <th class="px-2 py-2 text-center font-semibold text-slate-500">Received in Good Condition</th>
                                <th class="px-2 py-2 text-center font-semibold text-slate-500">Signature/Thumbmark</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(r, ri) in rdsRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input type="text" x-model="r.hh" class="w-20 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.beneficiary" class="w-36 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="number" min="0" x-model.number="r.deps" class="w-14 rounded-lg border border-slate-200 bg-white py-1 px-1 text-xs text-center focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.kind" class="w-32 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1"><input type="text" x-model="r.qty" class="w-20 rounded-lg border border-slate-200 bg-white py-1 px-1.5 text-xs focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></td>
                                    <td class="px-1 py-1 text-center"><input type="checkbox" x-model="r.received" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"></td>
                                    <td class="px-1 py-1 text-center text-slate-300 italic">signed on release</td>
                                    <td class="px-1 py-1"><button @click="rdsRows.splice(ri,1)" class="text-slate-300 hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button @click="addRdsRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-slate-500 hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Beneficiary Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Barangay Official" prepared-role="Distributed by" consolidated-name="" consolidated-label="" noted-name="JORGE C. BRUZA" noted-label="Punong Barangay" noted-role="Certified True and Correct" :two-column="true" />
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> Save Form</button>
            </div>
        </div>
        </div>
    </div>
    </template>

</div>
