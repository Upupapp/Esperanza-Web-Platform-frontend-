{{-- ============================================================
     FILE: modal-request-import.blade.php
     Dokyu / Tulong -> Import records (owner, 2026-10-12): an office's own
     records of documents issued or assistance given, brought into the portal
     and matched to the resident each was for. Logic:
     resources/js/request-import-ui.js (state, steps) and request-import.js
     (reading the file; loaded on first open).
     Include with ['importType' => 'tulong' | 'dokyu'] where the button goes.
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
@php
    $importType = $importType ?? 'tulong';
    $importKeys = $importType === 'tulong'
        ? collect(config('esperanza_tulong_types.types'))->pluck('key')->filter()->values()->all()
        : collect(config('esperanza_municipal_documents.documents'))->concat(collect(config('esperanza_barangay_documents'))->first()['documents'] ?? [])->pluck('key')->filter()->values()->all();
    $importBarangays = config('esperanza.barangays');
    $isTulong = $importType === 'tulong';
@endphp
<div class="contents" x-data="requestImport('{{ $importType }}', @js($importKeys), @js($importBarangays))"
     x-on:open-request-import.window="if ($event.detail?.type === type) openImport($event.detail?.panel || 'import')"
     x-on:beforeunload.window="guardLeave($event)">
<button type="button" x-show="allowed" x-cloak @click="openImport()"
        title="Bring in the office's own records from a file and match them to residents."
        class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-2xl px-3 py-2 border border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors shrink-0">
    <i data-lucide="file-up" class="w-3.5 h-3.5"></i> Import records
</button>
<x-ui.modal title="{{ $isTulong ? 'Import Tulong records from a file' : 'Import Dokyu records from a file' }}" maxWidth="3xl" :fullscreen="true">

    {{-- Tabs + steps --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-1.5 surface-pressed-sm rounded-2xl p-1 w-fit">
            <button type="button" @click="panel='import';$nextTick(()=>window.renderIcons?.())" class="px-3.5 py-1.5 text-xs font-medium rounded-xl flex items-center gap-1.5" :class="panel==='import' ? 'bg-white/85 text-brand-600 font-bold' : 'text-ink-muted hover:text-ink'">
                <i data-lucide="file-up" class="w-3.5 h-3.5"></i>Import a file
            </button>
            <button type="button" @click="panel='history';loadHistory()" class="px-3.5 py-1.5 text-xs font-medium rounded-xl flex items-center gap-1.5" :class="panel==='history' ? 'bg-white/85 text-brand-600 font-bold' : 'text-ink-muted hover:text-ink'">
                <i data-lucide="history" class="w-3.5 h-3.5"></i>Past imports
            </button>
        </div>
        <ol x-show="panel==='import'" class="flex items-center gap-2 text-xs" aria-label="Import steps">
            <template x-for="(label, i) in ['Choose a file','Match columns','Review','Import']" :key="i">
                <li class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full grid place-items-center text-[11px] font-bold border" :class="step > i+1 ? 'bg-brand-600 border-brand-600 text-white' : step === i+1 ? 'border-brand-600 text-brand-600 bg-white' : 'border-slate-200 text-ink-muted bg-white'" x-text="step > i+1 ? '✓' : i+1"></span>
                    <span class="hidden sm:inline font-medium" :class="step === i+1 ? 'text-ink' : 'text-ink-muted'" x-text="label"></span>
                    <span x-show="i < 3" class="w-6 h-px bg-slate-200 hidden sm:block"></span>
                </li>
            </template>
        </ol>
    </div>

    <div x-show="error" x-cloak class="mb-5 flex items-start gap-2.5 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800" role="alert">
        <i data-lucide="triangle-alert" class="w-4 h-4 mt-0.5 shrink-0"></i><p x-text="error"></p>
    </div>
    <div x-show="busy" x-cloak class="mb-5 flex items-center gap-3 rounded-xl bg-brand-50 border border-brand-100 px-4 py-3 text-sm text-brand-700" role="status">
        <i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i><span x-text="busyText"></span>
    </div>

    {{-- ═════════════ 1 · Choose a file ═════════════ --}}
    <div x-show="panel==='import' && step===1" class="grid lg:grid-cols-[minmax(0,1fr)_340px] gap-6">
        <label class="relative flex flex-col items-center justify-center text-center gap-3 rounded-3xl border-2 border-dashed px-6 py-14 cursor-pointer transition-colors"
               :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-slate-300 bg-white/60 hover:border-brand-400 hover:bg-brand-50/40'"
               @dragover.prevent="dragging=true" @dragleave.prevent="dragging=false" @drop.prevent="dragging=false;takeFile($event.dataTransfer.files[0])">
            <input type="file" class="sr-only" accept=".xlsx,.xlsm,.xlsb,.xls,.xlt,.xltx,.ods,.fods,.numbers,.csv,.tsv,.txt,.prn,.tab,.dif,.slk,.dbf,.html,.htm,.xml,.json,.docx,.odt,.pdf,.doc,.rtf,.jpg,.jpeg,.png,.heic" @change="takeFile($event.target.files[0]);$event.target.value=''" :disabled="busy">
            <span class="w-14 h-14 rounded-2xl clay-blue grid place-items-center"><i data-lucide="{{ $isTulong ? 'hand-heart' : 'file-text' }}" class="w-7 h-7"></i></span>
            <span class="text-base font-bold text-ink">{{ $isTulong ? 'Drop the office’s list of assistance given here, or choose a file' : 'Drop the office’s list of documents issued here, or choose a file' }}</span>
            <span class="text-sm text-ink-muted max-w-md">Excel, CSV, OpenDocument or Numbers spreadsheets, a table in Word or PDF, or JSON. Up to 40 MB.</span>
            <span class="inline-flex items-center gap-1.5 mt-1 px-4 py-2 rounded-2xl text-sm font-semibold clay-blue"><i data-lucide="folder-open" class="w-4 h-4"></i>Choose a file</span>
        </label>
        <aside class="space-y-4">
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-sm font-bold text-ink mb-1">Start from the template</p>
                <p class="text-xs text-ink-muted mb-3">{{ $isTulong ? 'One assistance given per row: who received it, what kind, how much and when.' : 'One document issued per row: who received it, which document and when.' }}</p>
                <button type="button" @click="downloadTemplate()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-brand-50 text-brand-700 hover:bg-brand-100"><i data-lucide="download" class="w-3.5 h-3.5"></i>Download the template (.xlsx)</button>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4 space-y-3 text-xs text-ink-muted">
                <p class="flex gap-2"><i data-lucide="user-check" class="w-4 h-4 text-brand-600 shrink-0"></i><span><strong class="text-ink">Each record finds its resident.</strong> It is matched to the registry by name, barangay and birth date, so the resident's profile shows what they received. When two residents could be the person, you choose.</span></p>
                <p class="flex gap-2"><i data-lucide="lock" class="w-4 h-4 text-emerald-600 shrink-0"></i><span><strong class="text-ink">The file stays on this computer.</strong> Only the records you import are sent.</span></p>
                <p class="flex gap-2"><i data-lucide="copy" class="w-4 h-4 text-sky-600 shrink-0"></i><span><strong class="text-ink">Nothing is entered twice.</strong> A record already imported is left out.</span></p>
                <p class="flex gap-2"><i data-lucide="undo-2" class="w-4 h-4 text-amber-600 shrink-0"></i><span><strong class="text-ink">It can be undone.</strong> Past imports lists every import, with Undo.</span></p>
            </div>
        </aside>
    </div>

    {{-- ═════════════ 2 · Match columns ═════════════ --}}
    <div x-show="panel==='import' && step===2" x-cloak class="space-y-5">
        <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white border border-slate-200 px-4 py-3">
            <i data-lucide="file-spreadsheet" class="w-5 h-5 text-emerald-600"></i>
            <div class="min-w-0">
                <p class="text-sm font-bold text-ink truncate" x-text="fileName"></p>
                <p class="text-xs text-ink-muted" x-text="body.length.toLocaleString() + ' records · ' + headers.length + ' columns'"></p>
            </div>
            <div class="flex flex-wrap items-center gap-2 ml-auto">
                <label x-show="tables.length > 1" class="text-xs text-ink-muted flex items-center gap-1.5">Sheet
                    <select class="text-xs rounded-lg border-slate-200 py-1.5" :value="tableIndex" @change="useTable($event.target.value)" aria-label="Sheet">
                        <template x-for="(t, i) in tables" :key="i"><option :value="i" x-text="t.name + ' (' + t.rows.length + ' rows)'" :selected="i===tableIndex"></option></template>
                    </select>
                </label>
                <label class="text-xs text-ink-muted flex items-center gap-1.5">Column names are on
                    <select class="text-xs rounded-lg border-slate-200 py-1.5 max-w-[16rem]" x-model.number="headerRow" @change="applyHeader(true)" aria-label="Row with the column names">
                        <option value="-1">No column names</option>
                        <template x-for="c in headerChoices" :key="c.value"><option :value="c.value" x-text="c.label" :selected="c.value===headerRow"></option></template>
                    </select>
                </label>
                <button type="button" @click="startOver()" class="text-xs font-semibold text-brand-700 hover:underline">Choose another file</button>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 text-xs">
            <template x-for="need in [{ok: hasName, label: 'Name (a full name, or first and last)'}, {ok: hasBarangay && !unmatchedBarangays, label: 'Barangay'}, {ok: hasService && !unmatchedServices, label: serviceWord}, {ok: hasDate, label: '{{ $isTulong ? 'Date given' : 'Date issued' }}'}]" :key="need.label">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full ring-1" :class="need.ok ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200'"><i :data-lucide="need.ok ? 'circle-check' : 'circle-alert'" class="w-3.5 h-3.5"></i><span x-text="need.label"></span></span>
            </template>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full ring-1" :class="column('dob') >= 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200'"><i :data-lucide="column('dob') >= 0 ? 'circle-check' : 'info'" class="w-3.5 h-3.5"></i><span x-text="column('dob') >= 0 ? 'Birth date' : 'Birth date (optional: it tells apart residents with the same name)'"></span></span>
        </div>

        <div class="grid xl:grid-cols-[minmax(0,1fr)_380px] gap-5 items-start">
            <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,1fr)] gap-3 px-4 py-2.5 bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-ink-muted">
                    <span>Column in your file</span><span>Examples</span><span>Import as</span>
                </div>
                <template x-for="(h, i) in headers" :key="i">
                    <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,1fr)] gap-3 items-center px-4 py-2.5 border-t border-slate-100" :class="mapping[i] ? '' : 'opacity-70'">
                        <p class="text-sm font-semibold text-ink truncate" :title="h"><span class="text-[11px] font-bold text-ink-muted mr-1.5" x-text="letter(i)"></span><span x-text="h"></span></p>
                        <p class="text-xs text-ink-muted truncate" :title="samples(i).join(' · ')" x-text="samples(i).join(' · ') || '(empty)'"></p>
                        <select class="text-xs rounded-lg py-1.5" :class="mapping[i] ? 'border-brand-300 bg-brand-50/50 text-brand-800 font-semibold' : 'border-slate-200'" @change="setField(i, $event.target.value)" :aria-label="'Import column ' + h + ' as'">
                            <option value="" :selected="!mapping[i]">Don’t import</option>
                            <template x-for="f in fields" :key="f.key"><option :value="f.key" x-text="f.label" :selected="mapping[i]===f.key"></option></template>
                        </select>
                    </div>
                </template>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl bg-white border border-slate-200 p-4">
                    <p class="text-sm font-bold text-ink mb-2 flex items-center gap-1.5"><i data-lucide="{{ $isTulong ? 'hand-heart' : 'file-text' }}" class="w-4 h-4 text-brand-600"></i><span x-text="serviceWord"></span></p>
                    <template x-if="column('service') < 0">
                        <div>
                            <p class="text-xs text-ink-muted mb-2">Your file has no column for it. Which one are all these records?</p>
                            <select class="w-full text-sm rounded-xl border-slate-200" x-model="fixedService" :aria-label="serviceWord + ' of every record'">
                                <option value="">Choose…</option>
                                <template x-for="s in services" :key="s.key"><option :value="s.key" x-text="s.name" :selected="s.key===fixedService"></option></template>
                            </select>
                        </div>
                    </template>
                    <template x-if="column('service') >= 0">
                        <div class="space-y-1.5 max-h-72 overflow-y-auto scrollbar-thin pr-1">
                            <p class="text-xs text-ink-muted mb-1">How each value in your file reads:</p>
                            <template x-for="v in serviceValues" :key="v.raw">
                                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] gap-2 items-center">
                                    <span class="text-xs text-ink truncate" :title="v.raw"><span x-text="v.raw"></span> <span class="text-ink-muted" x-text="'(' + v.count + ')'"></span></span>
                                    <select class="text-xs rounded-lg py-1" :class="v.match ? 'border-slate-200' : 'border-rose-300 bg-rose-50'" x-model="v.match" :aria-label="serviceWord + ' for ' + v.raw">
                                        <option value="">Leave these records out</option>
                                        <template x-for="s in services" :key="s.key"><option :value="s.key" x-text="s.name" :selected="s.key===v.match"></option></template>
                                    </select>
                                </div>
                            </template>
                            <p x-show="unmatchedServices" class="text-xs text-rose-700 pt-1" x-text="unmatchedServices + ' value(s) are not matched: those records will be left out unless you pick one.'"></p>
                        </div>
                    </template>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 p-4">
                    <p class="text-sm font-bold text-ink mb-2 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-4 h-4 text-brand-600"></i>Barangay</p>
                    <template x-if="column('barangay') < 0">
                        <div>
                            <p class="text-xs text-ink-muted mb-2" x-text="scope ? 'Every record is from your barangay.' : 'Your file has no barangay column. Which barangay are these records from?'"></p>
                            <select class="w-full text-sm rounded-xl border-slate-200" x-model="fixedBarangay" :disabled="!!scope" aria-label="Barangay of every record">
                                <option value="">Choose the barangay</option>
                                <template x-for="b in barangays" :key="b"><option :value="b" x-text="b" :selected="b===fixedBarangay"></option></template>
                            </select>
                        </div>
                    </template>
                    <template x-if="column('barangay') >= 0">
                        <div class="space-y-1.5 max-h-60 overflow-y-auto scrollbar-thin pr-1">
                            <template x-for="v in barangayValues" :key="v.raw">
                                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-2 items-center">
                                    <span class="text-xs text-ink truncate" :title="v.raw"><span x-text="v.raw"></span> <span class="text-ink-muted" x-text="'(' + v.count + ')'"></span></span>
                                    <select class="text-xs rounded-lg py-1" :class="v.match ? 'border-slate-200' : 'border-rose-300 bg-rose-50'" x-model="v.match" :aria-label="'Barangay for ' + v.raw">
                                        <option value="">Leave these records out</option>
                                        <template x-for="b in barangays" :key="b"><option :value="b" x-text="b" :selected="b===v.match"></option></template>
                                    </select>
                                </div>
                            </template>
                            <p x-show="unmatchedBarangays" class="text-xs text-rose-700 pt-1" x-text="unmatchedBarangays + ' value(s) are not matched: those records will be left out unless you pick a barangay.'"></p>
                        </div>
                    </template>
                </div>

                <div x-show="column('full_name') >= 0 && !fullNameHasCommas" class="rounded-2xl bg-white border border-slate-200 p-4">
                    <label class="flex items-start gap-2 text-xs text-ink"><input type="checkbox" class="mt-0.5 rounded" x-model="surnameFirst">Surnames come first, with no comma (DELA CRUZ JUAN P.)</label>
                </div>

                <div x-show="dateAmbiguous" class="rounded-2xl bg-white border border-slate-200 p-4">
                    <p class="text-sm font-bold text-ink mb-2 flex items-center gap-1.5"><i data-lucide="calendar" class="w-4 h-4 text-brand-600"></i>Dates are written</p>
                    <select class="w-full text-sm rounded-xl border-slate-200" x-model="dateOrder" aria-label="How dates are written">
                        <option value="mdy">Month first: 03/14/2025</option>
                        <option value="dmy">Day first: 14/03/2025</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ═════════════ 3 · Review ═════════════ --}}
    <div x-show="panel==='import' && step===3" x-cloak class="space-y-5">
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <template x-for="t in [{k:'linked',label:'Matched to a resident',icon:'user-check',tone:'emerald'},{k:'possible',label:'Choose the resident',icon:'users',tone:'amber'},{k:'none',label:'Not in the registry',icon:'user-x',tone:'slate'},{k:'duplicate',label:'Already imported',icon:'copy',tone:'sky'},{k:'error',label:'Need fixing',icon:'circle-alert',tone:'rose'}]" :key="t.k">
                <button type="button" @click="setFilter(filter===t.k ? 'all' : t.k)" class="text-left rounded-2xl border px-4 py-3 transition-colors"
                        :class="filter===t.k ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 bg-white hover:border-brand-200'">
                    <span class="flex items-center gap-2 text-xs font-semibold" :class="'text-' + t.tone + '-700'"><i :data-lucide="t.icon" class="w-4 h-4"></i><span x-text="t.label"></span></span>
                    <span class="block text-2xl font-extrabold text-ink mt-1 tabular-nums" x-text="(counts[t.k] || 0).toLocaleString()"></span>
                </button>
            </template>
        </div>

        <p x-show="counts.outside" x-cloak class="flex items-start gap-2 rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-xs text-ink-muted">
            <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
            <span x-text="(counts.outside || 0).toLocaleString() + ' record(s) are from other barangays and are left out: you import for Brgy. ' + scope + '.'"></span>
        </p>

        <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 px-4 py-3 border-b border-slate-100">
                <p class="text-sm font-bold text-ink" x-text="({all:'All records',linked:'Matched to a resident',possible:'Choose the resident',none:'Not in the registry',duplicate:'Already imported',error:'Need fixing'})[filter] + ' · ' + filtered.length.toLocaleString()"></p>
                <p x-show="filter==='possible'" class="text-xs text-ink-muted">More than one resident, or a near spelling, could be this person. Pick the right one, or keep the record by name only.</p>
                <p x-show="filter==='none'" class="text-xs text-ink-muted">No resident of the barangay has this name. The record is kept with its name and barangay.</p>
                <div class="relative ml-auto w-full sm:w-64">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="search" :value="reviewSearch" @input="setReviewSearch($event.target.value)" aria-label="Find a record" placeholder="Find a name, row or reference" class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border-slate-200">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-ink-muted">
                        <tr><th class="px-3 py-2 text-left">Row</th><th class="px-3 py-2 text-left">Name in the file</th><th class="px-3 py-2 text-left" x-text="serviceWord"></th><th class="px-3 py-2 text-left">Date</th><th class="px-3 py-2 text-right">Amount</th><th class="px-3 py-2 text-left min-w-[18rem]">Resident</th></tr>
                    </thead>
                    <tbody>
                        <template x-for="r in pageRows" :key="r.row">
                            <tr class="border-t border-slate-100 align-top" :class="r.action==='skip' ? 'opacity-50' : ''">
                                <td class="px-3 py-2.5 text-ink-muted tabular-nums" x-text="r.row"></td>
                                <td class="px-3 py-2.5">
                                    <p class="font-semibold text-ink" x-text="displayName(r) || '—'"></p>
                                    <p class="text-ink-muted" x-text="'Brgy. ' + (r.barangay || '—') + (r.dob ? ' · born ' + r.dob : '')"></p>
                                </td>
                                <td class="px-3 py-2.5">
                                    <p class="text-ink" x-text="serviceName(r.service_key) || r.service_raw || '—'"></p>
                                    <p class="text-ink-muted" x-text="[r.recordStatus === 'completed' ? '' : (r.recordStatus === 'rejected' ? 'Rejected' : 'Cancelled'), r.source_ref].filter(Boolean).join(' · ')"></p>
                                </td>
                                <td class="px-3 py-2.5 text-ink whitespace-nowrap tabular-nums" x-text="r.date || '—'"></td>
                                <td class="px-3 py-2.5 text-right text-ink tabular-nums" x-text="r.amount != null ? '₱' + Number(r.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'"></td>
                                <td class="px-3 py-2.5">
                                    <template x-if="r.status==='error'">
                                        <p class="flex items-start gap-1.5 text-rose-700"><i data-lucide="circle-alert" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i><span x-text="r.errors.join('; ')"></span></p>
                                    </template>
                                    <template x-if="r.status==='duplicate'">
                                        <p class="text-sky-700" x-text="'Already imported as ' + r.duplicateOf"></p>
                                    </template>
                                    <template x-if="r.status==='outside'">
                                        <p class="text-ink-muted" x-text="'Brgy. ' + r.barangay + ': not your barangay'"></p>
                                    </template>
                                    <template x-if="r.status==='linked'">
                                        <div>
                                            <p class="flex items-center gap-1.5 font-semibold text-emerald-700"><i data-lucide="user-check" class="w-3.5 h-3.5"></i><span x-text="r.resident.name + ' (' + r.resident.ref + ')'"></span></p>
                                            <p class="text-ink-muted mt-0.5" x-text="reasonText(r.resident.reasons)"></p>
                                        </div>
                                    </template>
                                    <template x-if="r.status==='possible' || r.status==='none'">
                                        <div class="space-y-1">
                                            <select class="w-full text-xs rounded-lg py-1" :class="r.status==='possible' && r.choice==='' ? 'border-amber-300 bg-amber-50' : 'border-slate-200'" x-model="r.choice" :aria-label="'Resident for row ' + r.row">
                                                <option value="" x-show="r.status==='possible'">Choose the resident…</option>
                                                <template x-for="c in r.candidates" :key="c.ref"><option :value="c.ref" x-text="candidateLabel(c)" :selected="c.ref===r.choice"></option></template>
                                                <option value="none">No resident: keep the name only</option>
                                            </select>
                                            <template x-if="r.choice && r.choice !== 'none'">
                                                <p class="text-ink-muted" x-text="reasonText((r.candidates.find((c) => c.ref === r.choice) || {}).reasons)"></p>
                                            </template>
                                        </div>
                                    </template>
                                    <label x-show="['linked','possible','none'].includes(r.status)" class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] text-ink-muted"><input type="checkbox" class="rounded" :checked="r.action==='skip'" @change="r.action = $event.target.checked ? 'skip' : 'auto'">Leave this record out</label>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="pages > 1" class="flex items-center justify-between px-4 py-2.5 border-t border-slate-100 text-xs">
                <button type="button" class="px-2.5 py-1 rounded-lg hover:bg-slate-100 disabled:opacity-40" :disabled="page<=1" @click="page--;$nextTick(()=>window.renderIcons?.())">Previous</button>
                <span class="text-ink-muted" x-text="'Page ' + page + ' of ' + pages"></span>
                <button type="button" class="px-2.5 py-1 rounded-lg hover:bg-slate-100 disabled:opacity-40" :disabled="page>=pages" @click="page++;$nextTick(()=>window.renderIcons?.())">Next</button>
            </div>
        </div>
    </div>

    {{-- ═════════════ 4 · Import ═════════════ --}}
    <div x-show="panel==='import' && step===4" x-cloak class="max-w-2xl mx-auto">
        <div x-show="!summary" class="rounded-3xl bg-white border border-slate-200 p-6 text-center">
            <p class="text-base font-bold text-ink mb-1" x-text="running ? 'Adding the records…' : 'Import paused'"></p>
            <p class="text-sm text-ink-muted mb-4" x-text="progress.done.toLocaleString() + ' of ' + progress.total.toLocaleString() + ' records sent' + (importRef ? ' · ' + importRef : '')"></p>
            <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden mb-5"><div class="h-full bg-brand-600 rounded-full transition-all duration-300" :style="'width:' + (progress.total ? Math.round(progress.done*100/progress.total) : 0) + '%'"></div></div>
            <div class="flex flex-wrap justify-center gap-2">
                <button type="button" x-show="running" @click="stop()" :disabled="stopRequested" class="px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 text-ink hover:bg-slate-200 disabled:opacity-50" x-text="stopRequested ? 'Stopping after this batch…' : 'Stop'"></button>
                <button type="button" x-show="!running && stoppedAt !== null" @click="runImport()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold clay-blue"><i data-lucide="refresh-cw" class="w-4 h-4"></i>Try again</button>
                <button type="button" x-show="!running && stoppedAt !== null" @click="finish()" class="px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 text-ink hover:bg-slate-200">Finish with what is in</button>
            </div>
            <p class="text-xs text-ink-muted mt-4">You can close this window; the import carries on. Stay on this page until it finishes.</p>
        </div>

        <div x-show="summary" class="rounded-3xl bg-white border border-slate-200 p-6">
            <div class="flex items-center gap-3 mb-5">
                <span class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 grid place-items-center"><i data-lucide="circle-check" class="w-6 h-6"></i></span>
                <div>
                    <p class="text-base font-bold text-ink" x-text="summary?.stopped ? 'Import stopped' : 'Import finished'"></p>
                    <p class="text-xs text-ink-muted" x-text="(summary?.ref || '') + ' · ' + fileName"></p>
                </div>
            </div>
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                <div class="rounded-2xl bg-emerald-50 px-3 py-2.5"><dt class="text-[11px] font-semibold text-emerald-700">Added</dt><dd class="text-xl font-extrabold text-ink tabular-nums" x-text="(summary?.created ?? 0).toLocaleString()"></dd></div>
                <div class="rounded-2xl bg-brand-50 px-3 py-2.5"><dt class="text-[11px] font-semibold text-brand-700">Linked to residents</dt><dd class="text-xl font-extrabold text-ink tabular-nums" x-text="(summary?.linked ?? 0).toLocaleString()"></dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-2.5"><dt class="text-[11px] font-semibold text-slate-600">Left out</dt><dd class="text-xl font-extrabold text-ink tabular-nums" x-text="(summary?.skipped ?? 0).toLocaleString()"></dd></div>
                <div class="rounded-2xl bg-rose-50 px-3 py-2.5"><dt class="text-[11px] font-semibold text-rose-700">Not imported</dt><dd class="text-xl font-extrabold text-ink tabular-nums" x-text="(summary?.failed ?? 0).toLocaleString()"></dd></div>
            </dl>
            <p x-show="summary?.unlinked" class="text-xs text-ink-muted mb-4" x-text="summary?.unlinked + (summary?.unlinked === 1 ? ' record is' : ' records are') + ' kept by name only: no resident of the barangay matched.'"></p>
            <p x-show="summary?.stopped" class="text-xs text-ink-muted mb-4">The rest of the file was not sent. To add it, import the same file again: the records already in are recognised and left out.</p>
            <p x-show="summary?.undone" class="text-xs text-amber-700 mb-4">This import was undone.</p>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="showImported(summary.ref)" x-show="!summary?.undone && summary?.created" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold clay-blue"><i data-lucide="list" class="w-4 h-4"></i>Show the imported records</button>
                <button type="button" @click="downloadNotAdded()" x-show="notAdded.length" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 text-ink hover:bg-slate-200"><i data-lucide="download" class="w-4 h-4"></i><span x-text="'Records not added (' + notAdded.length + ')'"></span></button>
                <button type="button" @click="startOver()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 text-ink hover:bg-slate-200"><i data-lucide="file-up" class="w-4 h-4"></i>Import another file</button>
            </div>
        </div>
    </div>

    {{-- ═════════════ Past imports ═════════════ --}}
    <div x-show="panel==='history'" x-cloak class="space-y-3">
        <p x-show="historyLoading" class="text-sm text-ink-muted flex items-center gap-2"><i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i>Loading…</p>
        <p x-show="historyFailed" class="text-sm text-rose-700">Past imports could not load. <button type="button" class="underline" @click="loadHistory()">Try again</button></p>
        <p x-show="!historyLoading && !historyFailed && !history.length" class="text-sm text-ink-muted">No imports yet.</p>
        <template x-for="h in history" :key="h.ref">
            <div class="rounded-2xl bg-white border border-slate-200 px-4 py-3">
                <div class="flex flex-wrap items-start gap-3">
                    <i data-lucide="file-spreadsheet" class="w-5 h-5 text-emerald-600 mt-0.5"></i>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-ink"><span x-text="h.filename"></span> <span class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1" :class="{'Completed':'bg-emerald-50 text-emerald-700 ring-emerald-200','In Progress':'bg-sky-50 text-sky-700 ring-sky-200','Undone':'bg-slate-100 text-slate-600 ring-slate-200'}[h.status]" x-text="h.status"></span></p>
                        <p class="text-xs text-ink-muted" x-text="h.ref + ' · ' + (h.created_by || '—') + ' · ' + when(h.created_at) + (h.scope ? ' · Brgy. ' + h.scope : '')"></p>
                        <p class="text-xs text-ink mt-1" x-text="h.created + ' added · ' + h.linked + ' linked to residents · ' + h.skipped + ' left out · ' + h.failed + ' not imported'"></p>
                        <p x-show="h.status==='Undone'" class="text-xs text-amber-700 mt-1" x-text="'Undone by ' + (h.undone_by || '—') + ': ' + ((h.undo_summary || {}).removed || 0) + ' records archived' + (h.undo_reason ? ' · ' + h.undo_reason : '')"></p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" x-show="h.status!=='Undone' && h.created" @click="showImported(h.ref)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-brand-700 hover:bg-brand-50">Show records</button>
                        <button type="button" x-show="h.status!=='Undone'" @click="askUndo(h)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-700 hover:bg-rose-50">Undo</button>
                    </div>
                </div>
                <div x-show="undoTarget && undoTarget.ref===h.ref" x-cloak class="mt-3 rounded-xl bg-rose-50 border border-rose-200 p-3">
                    <p class="text-xs text-rose-800 mb-2">Undo archives every record this import added: they leave the {{ $isTulong ? 'Tulong' : 'Dokyu' }} list, the counts and the residents' profiles, and stay in the records. Nothing is deleted.</p>
                    <div class="flex flex-wrap gap-2">
                        <input type="text" x-model="undoReason" maxlength="500" aria-label="Why the import is undone" placeholder="Why? (at least 5 characters)" class="flex-1 min-w-[12rem] text-xs rounded-lg border-rose-200">
                        <button type="button" @click="confirmUndo()" :disabled="undoing || undoReason.trim().length < 5" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-600 text-white disabled:opacity-50" x-text="undoing ? 'Undoing…' : 'Undo the import'"></button>
                        <button type="button" @click="undoTarget=null" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-ink hover:bg-white">Cancel</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <x-slot:footer>
        <template x-if="panel==='import' && step===2">
            <div class="flex items-center gap-2.5 w-full">
                <p class="text-xs mr-auto" :class="canCheck ? 'text-ink-muted' : 'font-semibold text-ink'" x-text="canCheck ? mappedCount + ' of ' + headers.length + ' columns will be imported' : 'Still needed: ' + missingForCheck.join(', ') + '.'"></p>
                <button type="button" @click="startOver()" class="px-4 py-2 rounded-xl text-sm font-semibold text-ink hover:bg-slate-100">Back</button>
                <button type="button" @click="toReview()" :disabled="!canCheck" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold clay-blue disabled:opacity-50">Match to residents<i data-lucide="arrow-right" class="w-4 h-4"></i></button>
            </div>
        </template>
        <template x-if="panel==='import' && step===3">
            <div class="flex items-center gap-2.5 w-full">
                <p class="text-xs mr-auto" :class="unchosen || !plan.add ? 'font-semibold text-ink' : 'text-ink-muted'"
                   x-text="!plan.add && plan.fix ? 'Nothing to import yet: the records need fixing in the file.' : !plan.add ? 'Every record in this file is already imported.' : unchosen ? unchosen + (unchosen === 1 ? ' record needs' : ' records need') + ' a resident chosen (or No resident).' : plan.add.toLocaleString() + ' to import · ' + plan.linked.toLocaleString() + ' linked to residents · ' + plan.skip.toLocaleString() + ' left out · ' + plan.fix.toLocaleString() + ' need fixing'"></p>
                <button type="button" @click="step=2;$nextTick(()=>window.renderIcons?.())" class="px-4 py-2 rounded-xl text-sm font-semibold text-ink hover:bg-slate-100">Back</button>
                <button type="button" @click="runImport()" :disabled="!plan.add || unchosen || running" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold clay-blue disabled:opacity-50"><i data-lucide="file-up" class="w-4 h-4"></i><span x-text="'Import ' + plan.add.toLocaleString() + (plan.add === 1 ? ' record' : ' records')"></span></button>
            </div>
        </template>
        <template x-if="!(panel==='import' && (step===2 || step===3))">
            <button type="button" @click="open=false" class="px-4 py-2 rounded-xl text-sm font-semibold text-ink hover:bg-slate-100">Close</button>
        </template>
    </x-slot:footer>
</x-ui.modal>
</div>
