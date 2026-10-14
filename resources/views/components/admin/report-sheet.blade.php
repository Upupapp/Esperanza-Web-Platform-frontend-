{{-- A standard printed report (owner, 2026-10-14), in the LGU's own layout
     (the Municipal Agriculture Office's Accomplishment Report): the
     letterhead, the title, the period covered, the tables with their totals,
     and who signs. Opened by window 'open-report' events (resources/js/
     dashboards.js openReport). Print and Excel are each recorded under the
     officer's name by the backend before the copy is made; the names on the
     signature lines can be changed first and are never saved. One per page. --}}
<div x-data="reportSheet()" @open-report.window="show($event.detail)">
    <x-ui.modal title="Report Preview" maxWidth="4xl" :fullscreen="true">
        <div x-show="loading" class="space-y-3 max-w-[210mm] mx-auto">
            <x-ui.skeleton class="h-24" />
            <x-ui.skeleton class="h-8 w-2/3 mx-auto" />
            <x-ui.skeleton class="h-56" />
        </div>
        <div x-show="failed" x-cloak>
            <x-ui.error-state messageExpr="error" retryExpr="show(params)" :retryable="true" />
        </div>

        <template x-if="report && !loading">
            <div>
                <p class="dp-no-print mb-3 text-[12.5px] text-ink-muted leading-relaxed mx-auto" :class="landscape ? 'max-w-[297mm]' : 'max-w-[210mm]'">
                    This is the copy as it prints. You can change the names on the signature lines first (an officer-in-charge signing for the head, for example); what you type is not saved.
                </p>
                <div class="rep-scroll">
                    <div id="dp-print-area">
                        <article class="rep-paper" :class="landscape && 'rep-landscape'">
                            <header class="rep-letterhead">
                                <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="Seal of the Municipality of Esperanza" class="rep-seal">
                                <div class="rep-lgu">
                                    <p>Republic of the Philippines</p>
                                    <p>Province of Masbate</p>
                                    <p class="rep-muni">Municipality of Esperanza</p>
                                    <p class="rep-office" x-text="officeLine"></p>
                                    <p class="rep-suboffice" x-show="subOfficeLine" x-text="subOfficeLine"></p>
                                </div>
                                <span class="rep-seal" aria-hidden="true"></span>
                            </header>

                            <h1 class="rep-title" x-text="report.title"></h1>
                            <p class="rep-period"><span x-text="report.as_of ? '' : 'Period Covered: '"></span><strong x-text="periodText"></strong></p>

                            <template x-if="report.key === 'dept_accomplishment'">
                                <dl class="rep-ident">
                                    <div><dt>Name:</dt><dd x-text="signatories[0] ? signatories[0].name : ''"></dd></div>
                                    <div><dt>Designation:</dt><dd x-text="signatories[0] ? signatories[0].position : ''"></dd></div>
                                    <div><dt>LGU:</dt><dd>Municipality of Esperanza, Masbate</dd></div>
                                </dl>
                            </template>

                            <template x-for="(s, si) in report.sections" :key="si">
                                <section class="rep-section">
                                    <h2 x-show="s.title" x-text="s.title"></h2>
                                    <table>
                                        <thead>
                                            <tr>
                                                <template x-for="c in s.columns" :key="c.key">
                                                    <th :class="align(c)" x-text="c.label"></th>
                                                </template>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(row, ri) in s.rows" :key="ri">
                                                <tr>
                                                    <template x-for="c in s.columns" :key="c.key">
                                                        <td :class="align(c)" x-text="cell(row[c.key], c)"></td>
                                                    </template>
                                                </tr>
                                            </template>
                                            <tr x-show="!s.rows.length">
                                                <td :colspan="s.columns.length" class="rep-empty">None in this period.</td>
                                            </tr>
                                        </tbody>
                                        {{-- A body, not a tfoot: the browser repeats a tfoot on every printed page. --}}
                                        <tbody x-show="s.totals && s.rows.length">
                                            <tr class="rep-total">
                                                <template x-for="c in s.columns" :key="c.key">
                                                    <td :class="align(c)" x-text="total(s, c)"></td>
                                                </template>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p class="rep-note" x-show="s.note" x-text="s.note"></p>
                                </section>
                            </template>

                            <footer class="rep-end">
                            <div class="rep-signatures" :class="'rep-signatures-' + signatories.length">
                                <template x-for="(sig, i) in signatories" :key="i">
                                    <div class="rep-sign">
                                        <p class="rep-sign-role" x-text="sig.role + ':'"></p>
                                        {{-- Text areas, not inputs: a long name (HON. ... T. TALISIC) wraps instead of being cut off on the page. --}}
                                        <textarea rows="1" class="rep-sign-name" x-model="sig.name" :aria-label="sig.role + ', name'" placeholder="Name"></textarea>
                                        <textarea rows="1" class="rep-sign-position" x-model="sig.position" :aria-label="sig.role + ', position'" placeholder="Position"></textarea>
                                    </div>
                                </template>
                            </div>

                            <p class="rep-generated" x-text="generatedText"></p>
                            </footer>
                        </article>
                    </div>
                </div>
            </div>
        </template>

        <x-slot:footer>
            <span class="mr-auto text-[12px] text-ink-muted hidden sm:inline" x-show="report" x-text="report ? (report.row_count + (report.row_count === 1 ? ' row' : ' rows') + ' · ' + (report.can_print ? 'Each print is recorded under your name.' : 'View only: your role cannot print this report.')) : ''"></span>
            <x-ui.button variant="secondary" @click="open = false">Close</x-ui.button>
            <x-ui.button variant="secondary" icon="file-spreadsheet" x-show="report && report.can_export" loading="busy === 'excel'" @click="excel()">Excel</x-ui.button>
            <x-ui.button icon="printer" x-show="report && report.can_print" loading="busy === 'print'" @click="print()">Print</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
