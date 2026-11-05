{{-- One incident's printed report (Sprint 2, owner 2026-11-05), opened by the
     incident window's Print report (a sakuna-print-incident event). GET
     /admin/sakuna/reports/incident/{ref}, sakuna_reports.print; the server
     logs each one. The sheet is the portal's printed report (rep-paper, A4
     portrait, as components/admin/report-sheet.blade.php); logic:
     incidentReportPrint() in resources/js/sakuna-report.js. Times are
     Esperanza time. LANGUAGE (TAB 09): staff site, English only. --}}
{{-- incidentReportOpen (the page's): Escape closes this window, not the incident's behind it. --}}
<div x-data="incidentReportPrint" @sakuna-print-incident.window="show($event.detail.ref)" x-effect="incidentReportOpen = open">
    <x-ui.modal title="Incident Report" maxWidth="4xl" :fullscreen="true">
        <div x-show="loading" class="space-y-3 max-w-[210mm] mx-auto">
            <x-ui.skeleton class="h-24" />
            <x-ui.skeleton class="h-8 w-2/3 mx-auto" />
            <x-ui.skeleton class="h-56" />
        </div>
        <div x-show="failed" x-cloak>
            <x-ui.error-state messageExpr="error" retryExpr="show(ref)" :retryable="true" />
        </div>

        <template x-if="report && !loading">
            <div class="rep-scroll">
                <div id="dp-print-area">
                    <article class="rep-paper" data-incident-report>
                        <header class="rep-letterhead">
                            <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="Seal of the Municipality of Esperanza" class="rep-seal">
                            <div class="rep-lgu">
                                <p>Republic of the Philippines</p>
                                <p>Province of Masbate</p>
                                <p class="rep-muni">Municipality of Esperanza</p>
                                <p class="rep-office">Municipal Disaster Risk Reduction and Management Office</p>
                            </div>
                            <span class="rep-seal" aria-hidden="true"></span>
                        </header>

                        <h1 class="rep-title" x-text="report.title"></h1>
                        <p class="rep-period"><strong x-text="report.incident.ref"></strong><span x-show="report.incident.title" x-text="' · ' + report.incident.title"></span></p>

                        <section class="rep-section rep-keep">
                            <h2>Incident details</h2>
                            <table>
                                <tbody>
                                    <template x-for="(row, ri) in detailRows" :key="ri">
                                        <tr>
                                            <th scope="row" class="rep-nowrap" x-text="row[0][0]"></th>
                                            <td :colspan="row[1] ? 1 : 3" x-text="row[0][1]"></td>
                                            <th scope="row" class="rep-nowrap" x-show="row[1]" x-text="row[1] ? row[1][0] : ''"></th>
                                            <td x-show="row[1]" x-text="row[1] ? row[1][1] : ''"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="report.incident.description">
                                        <th scope="row">Description</th>
                                        <td colspan="3" class="whitespace-pre-line" x-text="report.incident.description"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>

                        <section class="rep-section">
                            <h2>Timeline</h2>
                            <table>
                                <thead><tr><th>When</th><th>Step</th><th>Status</th><th>By</th><th>Note</th></tr></thead>
                                <tbody>
                                    <template x-for="(e, ei) in (report.timeline || [])" :key="ei">
                                        <tr>
                                            <td class="rep-nowrap" x-text="when(e.at)"></td>
                                            <td x-text="word(e.verb)"></td>
                                            <td x-text="[status(e.from), status(e.to)].filter(Boolean).join(' → ')"></td>
                                            <td x-text="e.by || ''"></td>
                                            <td class="whitespace-pre-line" x-text="e.note || ''"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!(report.timeline || []).length"><td colspan="5" class="rep-empty">No steps recorded.</td></tr>
                                </tbody>
                            </table>
                        </section>

                        <section class="rep-section">
                            <h2>Resources</h2>
                            <table>
                                <thead><tr><th>Resource</th><th>Category</th><th class="rep-num">Deployed</th><th class="rep-num">Returned</th><th class="rep-num">Still out</th><th>Unit</th><th>First deployed</th><th>Last returned</th></tr></thead>
                                <tbody>
                                    <template x-for="(r, ri) in (report.resources?.items || [])" :key="ri">
                                        <tr>
                                            <td x-text="r.name || r.ref || ''"></td>
                                            <td x-text="r.category || ''"></td>
                                            <td class="rep-num" x-text="num(r.deployed)"></td>
                                            <td class="rep-num" x-text="num(r.returned)"></td>
                                            <td class="rep-num" x-text="num(r.still_out)"></td>
                                            <td x-text="r.unit || ''"></td>
                                            <td class="rep-nowrap" x-text="when(r.first_deployed_at)"></td>
                                            <td class="rep-nowrap" x-text="when(r.last_returned_at)"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!(report.resources?.items || []).length"><td colspan="8" class="rep-empty">No resources deployed to this incident.</td></tr>
                                </tbody>
                            </table>
                            <table x-show="(report.resources?.movements || []).length" class="mt-2">
                                <thead><tr><th>When</th><th>Resource</th><th>Movement</th><th class="rep-num">Quantity</th><th>Unit</th><th>By</th></tr></thead>
                                <tbody>
                                    <template x-for="(m, mi) in (report.resources?.movements || [])" :key="mi">
                                        <tr>
                                            <td class="rep-nowrap" x-text="when(m.at)"></td>
                                            <td x-text="m.resource || ''"></td>
                                            <td x-text="word(m.verb)"></td>
                                            <td class="rep-num" x-text="num(m.quantity)"></td>
                                            <td x-text="m.unit || ''"></td>
                                            <td x-text="m.by || ''"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </section>

                        <section class="rep-section">
                            <h2>Damage</h2>
                            <p class="mb-1" x-text="num(report.damage?.reports) + (report.damage?.reports === 1 ? ' report' : ' reports') + ' · ' + num(report.damage?.units_affected) + ' units affected · estimated cost ' + peso(report.damage?.estimated_cost)"></p>
                            <table x-show="(report.damage?.list || []).length">
                                <thead><tr><th>Report</th><th>Sector</th><th>Barangay</th><th>Status</th><th class="rep-num">Units affected</th><th class="rep-num">Estimated cost</th><th>Recorded</th></tr></thead>
                                <tbody>
                                    <template x-for="(d, di) in (report.damage?.list || [])" :key="di">
                                        <tr>
                                            <td class="rep-nowrap" x-text="d.ref"></td>
                                            <td x-text="d.sector || ''"></td>
                                            <td x-text="d.barangay || ''"></td>
                                            <td x-text="d.status || ''"></td>
                                            <td class="rep-num" x-text="num(d.units_affected)"></td>
                                            <td class="rep-num" x-text="peso(d.estimated_cost)"></td>
                                            <td class="rep-nowrap" x-text="when(d.recorded_at)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </section>

                        {{-- Only when the server sends the list: null means the viewer may
                             not read Missing Persons, or it is switched off. --}}
                        <template x-if="Array.isArray(report.persons)">
                            <section class="rep-section">
                                <h2>Persons</h2>
                                <table>
                                    <thead><tr><th>Case</th><th>Kind</th><th>Status</th><th>Name</th><th class="rep-num">Age</th><th>Sex</th><th>Last seen</th><th>Last seen at</th></tr></thead>
                                    <tbody>
                                        <template x-for="(p, pi) in report.persons" :key="pi">
                                            <tr>
                                                <td class="rep-nowrap" x-text="p.ref"></td>
                                                <td x-text="kind(p.kind)"></td>
                                                <td x-text="p.status || ''"></td>
                                                <td x-text="p.name || 'Not yet identified'"></td>
                                                <td class="rep-num" x-text="p.age ?? ''"></td>
                                                <td x-text="p.sex || ''"></td>
                                                <td x-text="p.last_seen_place || ''"></td>
                                                <td class="rep-nowrap" x-text="when(p.last_seen_at)"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="!report.persons.length"><td colspan="8" class="rep-empty">No persons recorded under this incident.</td></tr>
                                    </tbody>
                                </table>
                            </section>
                        </template>

                        <section class="rep-section" x-show="(report.notes || []).length">
                            <h2>Notes</h2>
                            <ul class="list-disc pl-5">
                                <template x-for="(n, ni) in (report.notes || [])" :key="ni"><li class="rep-note" x-text="n"></li></template>
                            </ul>
                        </section>

                        <footer class="rep-end">
                            <p class="rep-generated" x-text="generatedText"></p>
                        </footer>
                    </article>
                </div>
            </div>
        </template>

        <x-slot:footer>
            <span class="mr-auto text-[12px] text-ink-muted hidden sm:inline">Opening this report is recorded under your name.</span>
            <x-ui.button variant="secondary" @click="open = false">Close</x-ui.button>
            <x-ui.button icon="printer" x-show="report && !loading" @click="print()">Print</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
