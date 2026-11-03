{{-- A filed internal form as its office's paper (2026-11-03): the form
     number, letterhead, title and stamp of the MHRMO / MGSO file it follows,
     the answers under the paper's own section headings (every choice printed
     with "( / )" on the chosen ones, as the papers are tick-box forms), the
     paper's fixed statements, each approval step's part as its officer filled
     it, the signature lines with the role under each, and the footnotes.
     Reads the Alpine object `viewing` (GET /admin/internal-forms/submissions/
     {ref}); printed by printPaper(), once per copy the paper names. --}}
<div class="if-paper" data-print-area x-ref="ifPaper">
    <div class="if-top">
        <p class="if-formno" x-text="viewing.paper?.formNo || ''"></p>
        <p class="if-corner" x-show="viewing.paper?.corner" x-text="viewing.paper?.corner"></p>
    </div>
    <div class="if-letterhead" x-show="paperLetterhead(viewing).length">
        <template x-for="(line, li) in paperLetterhead(viewing)" :key="li">
            <p :class="li === paperLetterhead(viewing).length - 1 ? 'if-office' : (/municipality/i.test(line) ? 'if-lgu' : '')" x-text="line"></p>
        </template>
    </div>
    <div class="if-titlebar">
        <h2 class="if-title" x-text="viewing.paper?.title || viewing.form"></h2>
        <div class="if-stamp" x-show="viewing.paper?.stamp">
            <p x-text="viewing.paper?.stamp"></p>
            <strong x-text="viewing.submitted ? longDate(viewing.submitted) : ''"></strong>
        </div>
    </div>
    <p class="if-subtitle" x-show="viewing.paper?.subtitle" x-text="viewing.paper?.subtitle"></p>
    <p class="if-ref" x-text="'Control No. ' + viewing.ref + (viewing.submitted ? ' · Filed ' + longDate(viewing.submitted) : '') + (viewing.submitted_by ? ' by ' + viewing.submitted_by : '')"></p>

    <p x-show="viewing.restricted" x-cloak class="if-restricted">The contents of this form are shown only to the person who filed it and the offices that sign it.</p>

    <template x-for="(sec, si) in paperSections(viewing.fields, viewing.data)" :key="'s' + si">
        <section class="if-section">
            <h3 x-show="sec.heading" x-text="sec.heading"></h3>
            <p class="if-statement" x-show="sec.heading && viewing.paper?.statements?.[sec.heading]" x-text="viewing.paper?.statements?.[sec.heading] || ''"></p>
            <dl class="if-grid">
                <template x-for="item in sec.items.filter(i => i.f.type !== 'table')" :key="item.f.key">
                    <div :class="['textarea', 'checkbox-group'].includes(item.f.type) || item.f.wide || (item.f.options || []).length > 4 ? 'if-wide' : ''">
                        <dt x-text="item.f.label"></dt>
                        <template x-if="['select', 'checkbox-group'].includes(item.f.type) && (item.f.options || []).length">
                            <dd class="if-ticks">
                                <template x-for="o in item.f.options" :key="o"><span><span x-text="optionTick(item.value, o)"></span> <span x-text="o"></span></span></template>
                            </dd>
                        </template>
                        <template x-if="!(['select', 'checkbox-group'].includes(item.f.type) && (item.f.options || []).length)">
                            <dd x-text="item.text"></dd>
                        </template>
                    </div>
                </template>
            </dl>
            <template x-for="item in sec.items.filter(i => i.f.type === 'table')" :key="'t' + item.f.key">
                <div class="if-table-wrap">
                    <p class="if-table-label" x-text="item.f.label"></p>
                    <table class="if-table">
                        <thead><tr><template x-for="c in item.f.columns" :key="c.key"><th x-text="c.label"></th></template></tr></thead>
                        <tbody>
                            <template x-for="(row, ri) in (item.value || [])" :key="ri">
                                <tr><template x-for="c in item.f.columns" :key="c.key"><td x-text="fieldText(c, row?.[c.key]) === '—' ? '' : fieldText(c, row?.[c.key])"></td></template></tr>
                            </template>
                            <tr x-show="!(item.value || []).length"><td :colspan="item.f.columns.length" class="if-none">None entered</td></tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </section>
    </template>
    <p class="if-statement if-statement-end" x-show="viewing.paper?.statements?._end" x-text="viewing.paper?.statements?._end || ''"></p>

    {{-- Each approval step: its own part of the paper, and who decided it --}}
    <template x-for="step in (viewing.chain || [])" :key="'c' + step.step">
        <section class="if-section if-office-part">
            <h3 x-text="step.part || step.label"></h3>
            <template x-if="approvalOf(step.step)">
                <div>
                    <dl class="if-grid">
                        <template x-for="item in paperSections(step.fields, approvalOf(step.step).data || {}).flatMap(s => s.items).filter(i => i.f.type !== 'table')" :key="item.f.key">
                            <div :class="['textarea', 'checkbox-group'].includes(item.f.type) || item.f.wide || (item.f.options || []).length > 4 ? 'if-wide' : ''">
                                <dt x-text="item.f.label"></dt>
                                <template x-if="['select', 'checkbox-group'].includes(item.f.type) && (item.f.options || []).length">
                                    <dd class="if-ticks">
                                        <template x-for="o in item.f.options" :key="o"><span><span x-text="optionTick(item.value, o)"></span> <span x-text="o"></span></span></template>
                                    </dd>
                                </template>
                                <template x-if="!(['select', 'checkbox-group'].includes(item.f.type) && (item.f.options || []).length)">
                                    <dd x-text="item.text"></dd>
                                </template>
                            </div>
                        </template>
                    </dl>
                    <p class="if-decision">
                        <strong x-text="approvalOf(step.step).decision === 'approve' ? 'Approved' : 'Disapproved'"></strong>
                        <span x-text="(approvalOf(step.step).by ? ' by ' + approvalOf(step.step).by : '') + (approvalOf(step.step).decided_at ? ', ' + longDate(approvalOf(step.step).decided_at) : '')"></span>
                    </p>
                    <p class="if-remarks" x-show="approvalOf(step.step).remarks" x-text="(approvalOf(step.step).decision === 'approve' ? 'Remarks: ' : 'Disapproved due to: ') + approvalOf(step.step).remarks"></p>
                </div>
            </template>
            <p x-show="!approvalOf(step.step)" class="if-pending" x-text="viewing.status === 'Rejected' ? 'Not reached' : 'Waiting on ' + (step.by === 'submitter' ? 'the employee’s own office' : (step.office || 'this step'))"></p>
        </section>
    </template>

    {{-- Signature lines, the role under each, as the paper has them --}}
    <div class="if-signs">
        <template x-for="(sg, gi) in paperSignatures(viewing)" :key="'sg' + gi">
            <div class="if-sign">
                <p class="if-sign-name" x-text="sg.name"></p>
                <p class="if-sign-role" x-text="sg.role"></p>
            </div>
        </template>
    </div>

    <template x-if="(viewing.paper?.notes || []).length">
        <ul class="if-notes">
            <template x-for="(n, ni) in viewing.paper.notes" :key="ni"><li x-text="n"></li></template>
        </ul>
    </template>
</div>
