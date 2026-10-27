{{--
    A BDRRMC capture form drawn as Barangay Labangtaytay's paper file lays it
    out (owner, 2026-10-27): A4 in the file's orientation, its heading and
    "Label: ______" lines, the ruled table with the paper's merged headers and
    highlights, 20 numbered rows a sheet (25 on the relief sheet) with a
    further sheet when they are full, the TOTAL row and the notes on the last
    sheet, and the signature lines. The same drawing is used to fill a form
    in, to print it, and to read or reprint a saved one, so the three cannot
    differ. Column definitions: resources/js/bdrrmc-paper.js.

    $mode: 'edit' (the form being filled in) or 'view' (a saved form).
--}}
@php
    $m = $mode === 'view' ? "'view'" : "'edit'";
    $edit = $mode !== 'view';
@endphp

<div class="pp-paper">
    <template x-for="page in paperPages({{ $m }})" :key="page.key">
        <section class="pp-sheet" :class="'pp-' + paperSpec({{ $m }}).orientation + (paperSpec({{ $m }}).type ? ' pp-type-' + paperSpec({{ $m }}).type : '')" :style="'--pp-row:' + paperSpec({{ $m }}).rowHeight + 'pt'" :aria-label="'Sheet ' + (page.p + 1)">
            @include('admin.partials.sakuna.bdrrmc-paper-heading', ['mode' => $mode])

            <table class="pp-table" lang="en">
                <colgroup>
                    <template x-for="c in paperLeaves({{ $m }})" :key="c.key"><col :style="'width:' + c.w + 'pt'"></template>
                </colgroup>
                <thead>
                    <template x-if="paperCaption({{ $m }})">
                        <tr class="pp-caption-row">
                            <template x-for="(cap, ci) in paperCaption({{ $m }})" :key="ci"><th :colspan="cap.colspan" class="pp-caption" x-text="cap.text || ''"></th></template>
                        </tr>
                    </template>
                    <template x-for="(hr, hi) in paperHead({{ $m }})" :key="hi">
                        <tr>
                            <template x-for="(h, hj) in hr" :key="hj">
                                <th :colspan="h.colspan" :rowspan="h.rowspan" :class="paperHeadClass(h)"><span x-text="h.label"></span><span x-show="h.star" class="pp-red">*</span><template x-if="h.sub"><span class="pp-sub" :class="h.subBold && 'pp-sub-bold'" x-text="h.sub"></span></template></th>
                            </template>
                        </tr>
                    </template>
                </thead>
                <tbody>
                    <template x-for="slot in page.slots" :key="{{ $edit ? "slot.r ? rowKey(openForm, slot.r, slot.i) : 'blank' + slot.i" : 'slot.i' }}">
                        <tr @if ($edit) :class="slot.r && rowAlert(openForm, slot.r) ? 'pp-' + rowAlert(openForm, slot.r) : ''" @endif>
                            <template x-for="c in paperLeaves({{ $m }})" :key="c.key">
                                <td :class="'pp-c pp-t-' + c.type" :style="c.type === 'no' ? 'text-align:' + paperNoAlign({{ $m }}, c.key) : ''">
                                    <template x-if="c.type === 'no'">
                                        <span class="pp-no"><span x-text="paperNo({{ $m }}, slot.i)"></span>
                                            @if ($edit)
                                                <button type="button" x-show="c.key === 'no' && slot.r && !isBlankRow(slot.r)" @click="removeRow(openForm, slot.i)" class="no-print pp-remove" :aria-label="'Remove row ' + (slot.i + 1)">&times;</button>
                                            @endif
                                        </span>
                                    </template>
                                    @if ($edit)
                                        <template x-if="c.type === 'text'">
                                            <div>
                                                <input type="text" x-model="slot.r[c.key]" :aria-label="c.aria || c.label" class="pp-cell-input" :class="editedClass(openForm, slot.r, c.key)">
                                                <template x-if="c.notes && rowSource(openForm, slot.r)">
                                                    <div class="no-print pp-notes">
                                                        <p class="pp-mono" x-text="rowSource(openForm, slot.r).ref + (rowSource(openForm, slot.r).released_by ? ' · released by ' + rowSource(openForm, slot.r).released_by : '')"></p>
                                                        <template x-for="(w, wi) in rowWarnings(openForm, slot.r)" :key="wi"><p :class="'pp-w-' + w.level" x-text="w.message"></p></template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="c.type === 'int'">
                                            <input type="number" min="0" step="1" x-model.number="slot.r[c.key]" :aria-label="c.aria || c.label" class="pp-cell-input pp-num" :class="editedClass(openForm, slot.r, c.key)">
                                        </template>
                                        <template x-if="c.type === 'num'">
                                            <input type="number" min="0" step="0.01" x-model.number="slot.r[c.key]" :aria-label="c.aria || c.label" class="pp-cell-input pp-num" :class="editedClass(openForm, slot.r, c.key)">
                                        </template>
                                        {{-- The paper asks for the level's number (1 ankle - 5 head); it prints as that number. --}}
                                        <template x-if="c.type === 'flood'">
                                            <select x-model="slot.r[c.key]" data-print="value" :aria-label="c.label" class="pp-cell-input pp-num">
                                                <option value=""></option><option value="1">1 Ankle</option><option value="2">2 Knee</option><option value="3">3 Waist</option><option value="4">4 Chest</option><option value="5">5 Head</option>
                                            </select>
                                        </template>
                                        <template x-if="['sum-m', 'sum-f', 'sum-all'].includes(c.type)">
                                            <span x-text="paperComputed(slot.r, c.type)"></span>
                                        </template>
                                        {{-- Blank: the beneficiary signs or thumbmarks the printed sheet. --}}
                                        <template x-if="c.type === 'sign'">
                                            <span class="no-print pp-hint" x-show="slot.r && !isBlankRow(slot.r)">signed on the printed sheet</span>
                                        </template>
                                    @else
                                        <template x-if="c.type !== 'no'"><span x-text="paperValue(slot.r, c)"></span></template>
                                    @endif
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
                <template x-if="page.last && paperTotals({{ $m }}).length">
                    <tfoot>
                        <tr class="pp-total">
                            <template x-for="(t, ti) in paperTotals({{ $m }})" :key="ti">
                                <td :colspan="t.colspan" :class="t.label ? 'pp-total-label pp-al-' + t.align : 'pp-num'" x-text="t.label || paperTotal({{ $m }}, t)"></td>
                            </template>
                        </tr>
                    </tfoot>
                </template>
            </table>

            <template x-if="page.last">
                <div class="pp-after">
                    {{-- The evacuee profile's own note, then what the records add: the
                         recorded headcount (not a column on the paper, owner
                         2026-10-27) and families of exactly five, kept apart until
                         the LGU rules. --}}
                    <template x-if="paperKey({{ $m }}) === 'evacuees'">
                        <div class="pp-notes-row">
                            <p class="pp-note">Note: List should be by families (Family Head and Dependents)</p>
                            <div class="pp-digital" data-paper-notes>
                                @if ($edit)
                                    <p>Recorded headcount: <strong x-text="recordedHeadcount()"></strong>; people in the age and sex columns: <strong x-text="evacueeGrandTotal()"></strong>.<span x-show="recordedHeadcount() !== evacueeGrandTotal()"> The difference had no age or sex on record.</span></p>
                                    <p x-show="fiveRule() === 'unresolved' && familySizes().eq5">Families with exactly 5 members: <strong x-text="familySizes().eq5"></strong>, not counted in either line above until the LGU confirms where they belong. This report is provisional.</p>
                                @else
                                    <p>Recorded headcount: <strong x-text="viewHeadcount().recorded"></strong>; people in the age and sex columns: <strong x-text="viewHeadcount().classified"></strong>.<span x-show="viewHeadcount().recorded !== viewHeadcount().classified"> The difference had no age or sex on record.</span></p>
                                    <p x-show="viewing.meta && viewing.meta.five_member_rule === 'unresolved' && viewing.meta.families_eq5">Families with exactly 5 members: <strong x-text="viewing.meta ? viewing.meta.families_eq5 : ''"></strong>, not counted in either line above until the LGU confirms where they belong. This report is provisional.</p>
                                @endif
                            </div>
                        </div>
                    </template>
                    <template x-if="paperSpec({{ $m }}).footnote === 'flood'">
                        <p class="pp-flood">*Levels of flood height: (1) Ankle; (2) knee; (3) waist; (4) chest; (5) head</p>
                    </template>
                    <x-admin.sakuna.bdrrmc-signoff :mode="$mode" />
                </div>
            </template>
        </section>
    </template>
</div>
