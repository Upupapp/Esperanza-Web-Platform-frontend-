{{-- ============================================================
     FILE: modal-import.blade.php
     Constituents -> Import from a file (owner, 2026-10-09): the LGU's own
     resident lists, in any common format, into the registry. Logic:
     resources/js/resident-import-ui.js (state, steps) and
     resident-import.js (reading the file; loaded on first open).
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
<div x-data="residentImport" x-on:open-resident-import.window="openImport($event.detail?.panel || 'import')" x-on:beforeunload.window="guardLeave($event)">
<x-ui.modal title="Import residents from a file" maxWidth="3xl" :fullscreen="true">

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

    {{-- Error --}}
    <div x-show="error" x-cloak class="mb-5 flex items-start gap-2.5 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800" role="alert">
        <i data-lucide="triangle-alert" class="w-4 h-4 mt-0.5 shrink-0"></i><p x-text="error"></p>
    </div>

    {{-- Working --}}
    <div x-show="busy" x-cloak class="mb-5 flex items-center gap-3 rounded-xl bg-brand-50 border border-brand-100 px-4 py-3 text-sm text-brand-700" role="status">
        <i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i><span x-text="busyText"></span>
    </div>

    {{-- ═════════════ 1 · Choose a file ═════════════ --}}
    <div x-show="panel==='import' && step===1" class="grid lg:grid-cols-[minmax(0,1fr)_340px] gap-6">
        <label class="relative flex flex-col items-center justify-center text-center gap-3 rounded-3xl border-2 border-dashed px-6 py-14 cursor-pointer transition-colors"
               :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-slate-300 bg-white/60 hover:border-brand-400 hover:bg-brand-50/40'"
               @dragover.prevent="dragging=true" @dragleave.prevent="dragging=false" @drop.prevent="dragging=false;takeFile($event.dataTransfer.files[0])">
            <input type="file" class="sr-only" accept=".xlsx,.xlsm,.xlsb,.xls,.xlt,.xltx,.ods,.fods,.numbers,.csv,.tsv,.txt,.prn,.tab,.dif,.slk,.dbf,.html,.htm,.xml,.json,.docx,.odt,.pdf,.doc,.rtf,.jpg,.jpeg,.png,.heic" @change="takeFile($event.target.files[0]);$event.target.value=''" :disabled="busy">
            <span class="w-14 h-14 rounded-2xl clay-blue grid place-items-center"><i data-lucide="file-up" class="w-7 h-7"></i></span>
            <span class="text-base font-bold text-ink">Drop your resident list here, or choose a file</span>
            <span class="text-sm text-ink-muted max-w-md">Excel, CSV, OpenDocument or Numbers spreadsheets, a table in Word or PDF, or JSON. Up to 40 MB.</span>
            <span class="inline-flex items-center gap-1.5 mt-1 px-4 py-2 rounded-2xl text-sm font-semibold clay-blue"><i data-lucide="folder-open" class="w-4 h-4"></i>Choose a file</span>
        </label>
        <aside class="space-y-4">
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-sm font-bold text-ink mb-1">Start from the template</p>
                <p class="text-xs text-ink-muted mb-3">The columns the registry keeps, with an example household. Copy your list into it if your file is laid out differently.</p>
                <button type="button" @click="downloadTemplate()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-brand-50 text-brand-700 hover:bg-brand-100"><i data-lucide="download" class="w-3.5 h-3.5"></i>Download the template (.xlsx)</button>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4 space-y-3 text-xs text-ink-muted">
                <p class="flex gap-2"><i data-lucide="lock" class="w-4 h-4 text-emerald-600 shrink-0"></i><span><strong class="text-ink">The file stays on this computer.</strong> It is read here; only the rows you import are sent to the registry.</span></p>
                <p class="flex gap-2"><i data-lucide="user-check" class="w-4 h-4 text-brand-600 shrink-0"></i><span><strong class="text-ink">No one is entered twice.</strong> Residents already in the registry are found and left as they are, and when a listed resident registers, their account is matched to their record.</span></p>
                <p class="flex gap-2"><i data-lucide="undo-2" class="w-4 h-4 text-amber-600 shrink-0"></i><span><strong class="text-ink">It can be undone.</strong> Past imports lists every import, with Undo.</span></p>
                <p class="flex gap-2"><i data-lucide="scan-line" class="w-4 h-4 text-slate-500 shrink-0"></i><span><strong class="text-ink">Paper lists and photos</strong> have no text to read. Type them into the template first.</span></p>
            </div>
        </aside>
    </div>

    {{-- ═════════════ 2 · Match columns ═════════════ --}}
    <div x-show="panel==='import' && step===2" x-cloak class="space-y-5">
        <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white border border-slate-200 px-4 py-3">
            <i data-lucide="file-spreadsheet" class="w-5 h-5 text-emerald-600"></i>
            <div class="min-w-0">
                <p class="text-sm font-bold text-ink truncate" x-text="fileName"></p>
                <p class="text-xs text-ink-muted" x-text="body.length.toLocaleString() + ' rows · ' + headers.length + ' columns'"></p>
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

        <p x-show="!body.length" x-cloak class="flex items-start gap-2 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800" role="alert">
            <i data-lucide="triangle-alert" class="w-4 h-4 mt-0.5 shrink-0"></i>
            <span>There are no rows under the column names. If the first row is a resident, set <strong>Column names are on</strong> to <strong>No column names</strong>; otherwise choose another sheet or file.</span>
        </p>

        <div class="flex flex-wrap gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full ring-1" :class="hasName ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200'"><i :data-lucide="hasName ? 'circle-check' : 'circle-alert'" class="w-3.5 h-3.5"></i>Name (a full name, or first and last)</span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full ring-1" :class="hasBarangay && !unmatchedBarangays ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200'"><i :data-lucide="hasBarangay && !unmatchedBarangays ? 'circle-check' : 'circle-alert'" class="w-3.5 h-3.5"></i>Barangay</span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full ring-1" :class="column('dob') >= 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200'"><i :data-lucide="column('dob') >= 0 ? 'circle-check' : 'info'" class="w-3.5 h-3.5"></i><span x-text="column('dob') >= 0 ? 'Birth date' : 'Birth date (recommended: it is how a resident who registers is recognised)'"></span></span>
        </div>

        <div class="grid xl:grid-cols-[minmax(0,1fr)_360px] gap-5 items-start">
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
                    <p class="text-sm font-bold text-ink mb-2 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-4 h-4 text-brand-600"></i>Barangay</p>
                    <template x-if="column('barangay') < 0">
                        <div>
                            <p class="text-xs text-ink-muted mb-2" x-text="scope ? 'Every row goes into your barangay.' : 'Your file has no barangay column. Which barangay is this list from?'"></p>
                            <select class="w-full text-sm rounded-xl border-slate-200" x-model="fixedBarangay" :disabled="!!scope" aria-label="Barangay of every row">
                                <option value="">Choose the barangay</option>
                                <template x-for="b in barangays" :key="b"><option :value="b" x-text="b" :selected="b===fixedBarangay"></option></template>
                            </select>
                        </div>
                    </template>
                    <template x-if="column('barangay') >= 0">
                        <div class="space-y-1.5 max-h-72 overflow-y-auto scrollbar-thin pr-1">
                            <p class="text-xs text-ink-muted mb-1">How each value in your file reads:</p>
                            <p x-show="scope" class="text-xs text-ink-muted mb-1" x-text="'You import for Brgy. ' + scope + '; rows of other barangays are left out.'"></p>
                            <template x-for="v in barangayValues" :key="v.raw">
                                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-2 items-center">
                                    <span class="text-xs text-ink truncate" :title="v.raw"><span x-text="v.raw"></span> <span class="text-ink-muted" x-text="'(' + v.count + ')'"></span></span>
                                    <select class="text-xs rounded-lg py-1" :class="v.match ? 'border-slate-200' : 'border-rose-300 bg-rose-50'" x-model="v.match" :aria-label="'Barangay for ' + v.raw">
                                        <option value="">Leave these rows out</option>
                                        <template x-for="b in barangays" :key="b"><option :value="b" x-text="b" :selected="b===v.match"></option></template>
                                    </select>
                                </div>
                            </template>
                            <p x-show="unmatchedBarangays" class="text-xs text-rose-700 pt-1" x-text="unmatchedBarangays + ' value(s) are not matched: those rows will be left out unless you pick a barangay.'"></p>
                        </div>
                    </template>
                </div>

                <div x-show="column('full_name') >= 0" class="rounded-2xl bg-white border border-slate-200 p-4">
                    <p class="text-sm font-bold text-ink mb-2 flex items-center gap-1.5"><i data-lucide="user-round" class="w-4 h-4 text-brand-600"></i>Names</p>
                    <label x-show="!fullNameHasCommas" class="flex items-start gap-2 text-xs text-ink mb-2"><input type="checkbox" class="mt-0.5 rounded" x-model="surnameFirst">Surnames come first, with no comma (DELA CRUZ JUAN P.)</label>
                    <template x-for="n in namePreview" :key="n.raw">
                        <p class="text-xs text-ink-muted py-1 border-t border-slate-100"><span class="text-ink" x-text="n.raw"></span> → <span x-text="'Last: ' + (n.last_name || '—') + ' · First: ' + (n.first_name || '—') + (n.middle_name ? ' · Middle: ' + n.middle_name : '') + (n.suffix ? ' · ' + n.suffix : '')"></span></p>
                    </template>
                </div>

                <div x-show="dateAmbiguous" class="rounded-2xl bg-white border border-slate-200 p-4">
                    <p class="text-sm font-bold text-ink mb-2 flex items-center gap-1.5"><i data-lucide="calendar" class="w-4 h-4 text-brand-600"></i>Birth dates are written</p>
                    <select class="w-full text-sm rounded-xl border-slate-200" x-model="dateOrder" aria-label="How birth dates are written">
                        <option value="mdy">Month first: 03/14/1985</option>
                        <option value="dmy">Day first: 14/03/1985</option>
                    </select>
                </div>

                <div x-show="hasHouseholdNo || hasRelation" class="rounded-2xl bg-white border border-slate-200 p-4">
                    <p class="text-sm font-bold text-ink mb-2 flex items-center gap-1.5"><i data-lucide="house" class="w-4 h-4 text-emerald-600"></i>Households</p>
                    <label class="flex items-start gap-2 text-xs text-ink"><input type="checkbox" class="mt-0.5 rounded" x-model="groupHouseholds">
                        <span x-text="hasHouseholdNo ? 'Make one household (and family) of the rows with the same household number; a blank number is the household of the row above. The row whose relationship is Head becomes its head.' : 'The rows listed under each Head belong to that Head\'s household. Use this only if the file lists each household together.'"></span></label>
                </div>
            </div>
        </div>
    </div>

    {{-- ═════════════ 3 · Review ═════════════ --}}
    <div x-show="panel==='import' && step===3" x-cloak class="space-y-5">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <template x-for="t in [{k:'ready',label:'New residents',icon:'user-plus',tone:'emerald'},{k:'match',label:'Already in the registry',icon:'user-check',tone:'sky'},{k:'twice',label:'Listed twice in the file',icon:'copy',tone:'amber'},{k:'error',label:'Need fixing',icon:'circle-alert',tone:'rose'}]" :key="t.k">
                <button type="button" @click="setFilter(filter===t.k ? 'all' : t.k)" class="text-left rounded-2xl border px-4 py-3 transition-colors"
                        :class="filter===t.k ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 bg-white hover:border-brand-200'">
                    <span class="flex items-center gap-2 text-xs font-semibold" :class="'text-' + t.tone + '-700'"><i :data-lucide="t.icon" class="w-4 h-4"></i><span x-text="t.label"></span></span>
                    <span class="block text-2xl font-extrabold text-ink mt-1 tabular-nums" x-text="counts[t.k].toLocaleString()"></span>
                </button>
            </template>
        </div>

        <p x-show="counts.outside" x-cloak class="flex items-start gap-2 rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-xs text-ink-muted">
            <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
            <span x-text="counts.outside.toLocaleString() + ' row(s) are from other barangays and are left out: you import for Brgy. ' + scope + '. Municipal staff can import the whole list.'"></span>
        </p>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-sm font-bold text-ink mb-2">Residents already in the registry</p>
                <label class="flex items-start gap-2 text-xs text-ink mb-1.5"><input type="radio" value="skip" x-model="onMatch" class="mt-0.5">Leave their records as they are</label>
                <label class="flex items-start gap-2 text-xs text-ink"><input type="radio" value="fill" x-model="onMatch" class="mt-0.5">Fill in details their records are missing (nothing already filled in is changed)</label>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-sm font-bold text-ink mb-2">Mark the new records as</p>
                <select class="w-full text-sm rounded-xl border-slate-200" x-model="verification" aria-label="Mark the new records as">
                    <option value="Unverified">Unverified: the barangay still checks them</option>
                    <option value="Verified">Verified: this is the LGU’s official, checked registry</option>
                </select>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 bg-slate-50 text-xs">
                <span class="font-semibold text-ink" x-text="(filter==='all' ? 'All rows' : {ready:'New residents',match:'Already in the registry',twice:'Listed twice',error:'Need fixing'}[filter]) + ' · ' + filtered.length.toLocaleString()"></span>
                <span x-show="filter==='error' && counts.error" class="text-ink-muted">Fix each row here and it is checked again. Rows still needing a fix are left out of the import.</span>
                <label class="relative ml-auto">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-2.5 top-1/2 -translate-y-1/2"></i>
                    <input type="search" :value="reviewSearch" @input.debounce.250ms="setReviewSearch($event.target.value)" aria-label="Find a row" placeholder="Find a name, row or household no." class="w-64 max-w-full pl-8 pr-3 py-1.5 rounded-lg text-xs border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200">
                </label>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="text-[11px] uppercase tracking-wide text-ink-muted">
                        <tr class="border-b border-slate-100"><th class="text-left font-bold px-4 py-2">Row</th><th class="text-left font-bold px-2 py-2">Name</th><th class="text-left font-bold px-2 py-2">Sex</th><th class="text-left font-bold px-2 py-2">Birth date</th><th class="text-left font-bold px-2 py-2">Barangay</th><th class="text-left font-bold px-2 py-2">Household</th><th class="text-left font-bold px-2 py-2 min-w-[16rem]">What happens</th></tr>
                    </thead>
                    <template x-for="r in pageRows" :key="r.row">
                        <tbody>
                            <tr class="border-b border-slate-100 align-top" :class="editing===r.row ? 'bg-brand-50/50' : ''">
                                <td class="px-4 py-2 text-ink-muted tabular-nums" x-text="r.row"></td>
                                <td class="px-2 py-2 font-semibold text-ink"><span x-text="displayName(r) || '—'"></span> <span x-show="r.edited" class="ml-1 px-1.5 rounded bg-brand-100 text-[10px] font-semibold text-brand-700">Edited</span></td>
                                <td class="px-2 py-2 text-ink-muted" x-text="r.sex || '—'"></td>
                                <td class="px-2 py-2 text-ink-muted tabular-nums" x-text="r.dob || '—'"></td>
                                <td class="px-2 py-2 text-ink-muted" x-text="r.barangay || '—'"></td>
                                <td class="px-2 py-2 text-ink-muted" x-text="r.household_key ? (r.is_head ? 'Head · ' : '') + r.household_key : (r.relation || '—')"></td>
                                <td class="px-2 py-2">
                                    <template x-if="r.status==='ready'">
                                        <select class="text-xs rounded-lg border-emerald-200 bg-emerald-50 text-emerald-800 py-1" x-model="r.action" :aria-label="'What happens to row ' + r.row"><option value="auto">Add as a new resident</option><option value="skip">Leave out</option></select>
                                    </template>
                                    <template x-if="r.status==='match'">
                                        <div class="space-y-1">
                                            <p class="text-sky-800"><span class="font-semibold" x-text="'Already in the registry: ' + r.match.ref"></span> <span x-text="r.match.name"></span></p>
                                            <p class="text-ink-muted" x-text="reasonText(r.match.reasons) + (r.match.has_account ? ' · has an account' : '')"></p>
                                            <select class="text-xs rounded-lg border-sky-200 bg-sky-50 text-sky-800 py-1" x-model="r.action" :aria-label="'What happens to row ' + r.row">
                                                <option value="auto" x-text="onMatch==='fill' ? 'Fill in its blank details' : 'Leave the record as it is'"></option>
                                                <option value="fill" x-show="onMatch!=='fill'">Fill in its blank details</option>
                                                <option value="skip" x-show="onMatch==='fill'">Leave the record as it is</option>
                                                <option value="new">This is a different person: add</option>
                                            </select>
                                        </div>
                                    </template>
                                    <template x-if="r.status==='twice'">
                                        <div class="space-y-1">
                                            <p class="text-amber-800 font-semibold" x-text="'Same person as row ' + r.twinOf"></p>
                                            <select class="text-xs rounded-lg border-amber-200 bg-amber-50 text-amber-800 py-1" x-model="r.action" :aria-label="'What happens to row ' + r.row"><option value="skip">Leave out</option><option value="new">A different person: add</option></select>
                                        </div>
                                    </template>
                                    <template x-if="r.status==='error'">
                                        <div class="flex flex-wrap items-start gap-2">
                                            <p class="text-rose-700 flex-1 min-w-[10rem]"><i data-lucide="circle-alert" class="w-3.5 h-3.5 inline -mt-0.5 mr-1"></i><span x-text="r.errors.join('; ')"></span></p>
                                            <button type="button" x-show="editing!==r.row" @click="startEdit(r)" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-600 text-white hover:bg-rose-700"><i data-lucide="pencil" class="w-3 h-3"></i>Fix</button>
                                        </div>
                                    </template>
                                    <button type="button" x-show="r.status!=='error' && editing!==r.row" @click="startEdit(r)" class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-brand-700 hover:underline"><i data-lucide="pencil" class="w-3 h-3"></i>Edit</button>
                                </td>
                            </tr>
                            {{-- Correct the row here; it is checked again on save. --}}
                            <template x-if="editing===r.row">
                                <tr class="border-b border-slate-200 bg-brand-50/50">
                                    <td colspan="7" class="px-4 pb-4 pt-1">
                                        <div class="rounded-xl bg-white border border-brand-200 p-4 space-y-3">
                                            <p class="text-[11px] text-ink-muted"><span class="font-semibold text-ink" x-text="'Row ' + r.row + ' in the file:'"></span> <span x-text="r.cells.filter(Boolean).join(' · ')"></span></p>
                                            <div x-show="draftErrors.length" class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-xs text-rose-800" role="alert" x-text="draftErrors.join('; ')"></div>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                                <label class="text-[11px] font-semibold text-slate-600">First name <span class="text-rose-500">*</span><input type="text" x-model="draft.first_name" maxlength="100" aria-label="First name" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60"></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Middle name<input type="text" x-model="draft.middle_name" maxlength="100" aria-label="Middle name" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60"></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Last name <span class="text-rose-500">*</span><input type="text" x-model="draft.last_name" maxlength="100" aria-label="Last name" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60"></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Suffix
                                                    <select x-model="draft.suffix" aria-label="Suffix" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60"><option value="">None</option><option>Jr.</option><option>Sr.</option><option>II</option><option>III</option><option>IV</option><option>V</option></select></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Sex
                                                    <select x-model="draft.sex" aria-label="Sex" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60"><option value="">Not given</option><option>Male</option><option>Female</option></select></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Birth date
                                                    <input type="date" x-model="draft.dob" min="1900-01-02" :max="new Date().toISOString().slice(0, 10)" aria-label="Birth date" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60">
                                                    <span x-show="fileValue(r, 'dob')" class="block font-normal text-ink-muted mt-0.5" x-text="'In the file: ' + fileValue(r, 'dob')"></span></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Civil status
                                                    <select x-model="draft.civil_status" aria-label="Civil status" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60"><option value="">Not given</option><option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option><option>Annulled</option><option>Live-in</option><template x-if="draft.civil_status && !['Single','Married','Widowed','Separated','Annulled','Live-in'].includes(draft.civil_status)"><option :value="draft.civil_status" x-text="draft.civil_status"></option></template></select></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Barangay <span class="text-rose-500">*</span>
                                                    <select x-model="draft.barangay" aria-label="Barangay" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60" :disabled="!!scope"><option value="">Choose the barangay</option><template x-for="b in barangays" :key="b"><option :value="b" x-text="b" :selected="b===draft.barangay"></option></template></select>
                                                    <span x-show="fileValue(r, 'barangay') && !draft.barangay" class="block font-normal text-ink-muted mt-0.5" x-text="'In the file: ' + fileValue(r, 'barangay')"></span></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Purok / Sitio<input type="text" x-model="draft.sitio" maxlength="120" aria-label="Purok or sitio" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60"></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Relationship to head
                                                    <select x-model="draft.relation" aria-label="Relationship to head" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60"><option value="">Not given</option><option>Head</option><option>Spouse</option><option>Son</option><option>Daughter</option><option>Child</option><option>Grandchild</option><option>Other</option><template x-if="draft.relation && !['Head','Spouse','Son','Daughter','Child','Grandchild','Other'].includes(draft.relation)"><option :value="draft.relation" x-text="draft.relation"></option></template></select></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Household number<input type="text" x-model="draft.household_key" maxlength="80" aria-label="Household number" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60"></label>
                                                <label class="text-[11px] font-semibold text-slate-600">Contact number<input type="text" x-model="draft.contact" maxlength="40" aria-label="Contact number" class="mt-1 w-full px-3 py-2 rounded-lg text-xs font-normal text-ink border-0 surface-pressed-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-200 disabled:opacity-60"></label>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button type="button" @click="saveEdit(r)" :disabled="checkingRow" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold clay-blue disabled:opacity-50"><i data-lucide="check" class="w-3.5 h-3.5"></i><span x-text="checkingRow ? 'Checking…' : 'Save and check again'"></span></button>
                                                <button type="button" @click="cancelEdit()" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-ink hover:bg-slate-100">Cancel</button>
                                                <p class="text-[11px] text-ink-muted">The file is not changed; the corrected row is what gets imported.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </template>
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
            <p class="text-base font-bold text-ink mb-1" x-text="running ? 'Adding the residents…' : 'Import paused'"></p>
            <p class="text-sm text-ink-muted mb-4" x-text="progress.done.toLocaleString() + ' of ' + progress.total.toLocaleString() + ' rows sent' + (importRef ? ' · ' + importRef : '')"></p>
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
                <div class="rounded-2xl bg-sky-50 px-3 py-2.5"><dt class="text-[11px] font-semibold text-sky-700">Details filled in</dt><dd class="text-xl font-extrabold text-ink tabular-nums" x-text="(summary?.filled ?? 0).toLocaleString()"></dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-2.5"><dt class="text-[11px] font-semibold text-slate-600">Left as they were</dt><dd class="text-xl font-extrabold text-ink tabular-nums" x-text="(summary?.skipped ?? 0).toLocaleString()"></dd></div>
                <div class="rounded-2xl bg-rose-50 px-3 py-2.5"><dt class="text-[11px] font-semibold text-rose-700">Not imported</dt><dd class="text-xl font-extrabold text-ink tabular-nums" x-text="(summary?.failed ?? 0).toLocaleString()"></dd></div>
            </dl>
            <p x-show="summary?.stopped" class="text-xs text-ink-muted mb-4">The rest of the file was not sent. To add it, import the same file again: the residents already in are recognised and left as they are.</p>
            <p x-show="summary?.households" class="text-xs text-ink-muted mb-4" x-text="summary?.households + (summary?.households === 1 ? ' household and its family was' : ' households and their families were') + ' made from the household numbers.'"></p>
            <p x-show="summary?.undone" class="text-xs text-amber-700 mb-4">This import was undone.</p>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="showImported(summary.ref)" x-show="!summary?.undone" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold clay-blue"><i data-lucide="users" class="w-4 h-4"></i>Show the imported residents</button>
                <button type="button" @click="fixLeftovers()" x-show="!summary?.undone && leftovers" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100"><i data-lucide="pencil" class="w-4 h-4"></i><span x-text="leftovers === 1 ? 'Fix the row that needs it' : 'Fix the ' + leftovers + ' rows that need it'"></span></button>
                <button type="button" @click="downloadNotAdded()" x-show="notAdded.length" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 text-ink hover:bg-slate-200"><i data-lucide="download" class="w-4 h-4"></i><span x-text="'Rows not added (' + notAdded.length + ')'"></span></button>
                <button type="button" @click="startOver()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 text-ink hover:bg-slate-200"><i data-lucide="file-up" class="w-4 h-4"></i>Import another file</button>
                <button type="button" x-show="mayUndo && !summary?.undone" @click="panel='history';loadHistory().then(()=>askUndo(history.find(h=>h.ref===summary.ref)))" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold text-rose-700 hover:bg-rose-50"><i data-lucide="undo-2" class="w-4 h-4"></i>Undo this import</button>
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
                        <p class="text-xs text-ink-muted" x-text="h.ref + ' · ' + (h.created_by || '—') + ' · ' + new Date(h.created_at).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) + (h.scope ? ' · Brgy. ' + h.scope : '')"></p>
                        <p class="text-xs text-ink mt-1" x-text="h.created + ' added · ' + h.filled + ' filled in · ' + h.skipped + ' left as they were · ' + h.failed + ' not imported' + (h.households_created ? ' · ' + h.households_created + ' households' : '')"></p>
                        <p x-show="h.status==='In Progress' && !(running && h.ref===importRef)" class="text-xs text-sky-700 mt-1">Not finished. If it was left part way (the page was closed or the connection dropped), import the same file again to add the rest: the residents already in are left as they are.</p>
                        <p x-show="h.status==='Undone'" class="text-xs text-amber-700 mt-1" x-text="'Undone by ' + (h.undone_by || '—') + ': ' + undoneText(h) + (h.undo_reason ? ' · ' + h.undo_reason : '')"></p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" x-show="h.status!=='Undone' && h.created" @click="showImported(h.ref)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-brand-700 hover:bg-brand-50">Show residents</button>
                        <button type="button" x-show="mayUndo && h.status!=='Undone'" @click="askUndo(h)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-700 hover:bg-rose-50">Undo</button>
                    </div>
                </div>
                <div x-show="undoTarget && undoTarget.ref===h.ref" x-cloak class="mt-3 rounded-xl bg-rose-50 border border-rose-200 p-3">
                    <p class="text-xs text-rose-800 mb-2">Undo removes the residents, households and families this import added, and empties the details it filled in. Anyone who has since registered, filed a request or been listed as an evacuee is kept.</p>
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
                <p class="text-xs text-ink-muted mr-auto" x-text="mappedCount + ' of ' + headers.length + ' columns will be imported'"></p>
                <button type="button" @click="startOver()" class="px-4 py-2 rounded-xl text-sm font-semibold text-ink hover:bg-slate-100">Back</button>
                <button type="button" @click="toReview()" :disabled="!hasName || !hasBarangay || busy || !body.length" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold clay-blue disabled:opacity-50">Check the rows<i data-lucide="arrow-right" class="w-4 h-4"></i></button>
            </div>
        </template>
        <template x-if="panel==='import' && step===3">
            <div class="flex items-center gap-2.5 w-full">
                <p class="text-xs mr-auto" :class="!(plan.add + plan.fill) && (plan.fix || plan.leave) ? 'font-semibold text-ink' : 'text-ink-muted'" x-text="!(plan.add + plan.fill) && plan.fix ? 'Nothing to add yet: fix the rows that need it.' : !(plan.add + plan.fill) && plan.leave ? 'Everyone in this file is already in the registry. To complete their records, choose “Fill in details their records are missing”.' : plan.add.toLocaleString() + ' to add · ' + plan.fill.toLocaleString() + ' to fill in · ' + plan.leave.toLocaleString() + ' left as they are · ' + plan.fix.toLocaleString() + ' need fixing' + (households ? ' · ' + households + ' households' : '')"></p>
                <button type="button" @click="step=2;$nextTick(()=>window.renderIcons?.())" class="px-4 py-2 rounded-xl text-sm font-semibold text-ink hover:bg-slate-100">Back</button>
                <button type="button" @click="runImport()" :disabled="!(plan.add + plan.fill) || running" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold clay-blue disabled:opacity-50"><i data-lucide="file-up" class="w-4 h-4"></i><span x-text="'Import ' + (plan.add + plan.fill).toLocaleString() + ((plan.add + plan.fill) === 1 ? ' row' : ' rows')"></span></button>
            </div>
        </template>
        <template x-if="!(panel==='import' && (step===2 || step===3))">
            <button type="button" @click="open=false" class="px-4 py-2 rounded-xl text-sm font-semibold text-ink hover:bg-slate-100">Close</button>
        </template>
    </x-slot:footer>
</x-ui.modal>
</div>
