<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Screen sweep 18 Nov (web-2): Sakuna's confirmed defects in the staff page
 * and its tabs - lists that showed part of their records as all of them, an
 * edit that could not clear a field or opened a draft blank, a failed load
 * read as "none", and phone/wide-screen layout. Source-reading, as the rest
 * of this suite.
 */
class Sweep1118web2Test extends TestCase
{
    private function source(string $path): string
    {
        return str_replace("\r\n", "\n", file_get_contents(base_path($path)));
    }

    private function page(): string
    {
        return $this->source('resources/views/admin/sakuna.blade.php');
    }

    private function tab(string $name): string
    {
        return $this->source('resources/views/admin/partials/sakuna/'.$name.'.blade.php');
    }

    public function test_every_saved_bdrrmc_form_is_listed_and_a_failed_load_is_not_an_empty_list(): void
    {
        $tab = $this->tab('tab-bdrrmc-forms');

        // Every page, under the server's own sort key; one get() gave the newest 25.
        $this->assertStringContainsString("\$api.getAll('/admin/sakuna/bdrrmc-forms/submissions', { params: { sort: 'created_at' } })", $tab);
        $this->assertStringNotContainsString("\$api.get('/admin/sakuna/bdrrmc-forms/submissions', ", $tab);
        $this->assertStringNotContainsString("sort: '-created_at'", $tab);
        $this->assertStringContainsString('this.submissionsTruncated = !!res.meta?.truncated;', $tab);
        $this->assertStringContainsString('x-show="!submissionsLoading && submissionsTruncated"', $tab);

        // A failure says so, and is not "No capture form has been saved yet".
        $this->assertStringNotContainsString('catch { this.submissions = []; }', $tab);
        $this->assertStringContainsString('this.submissionsFailed = true;', $tab);
        $this->assertStringContainsString('x-show="!submissionsLoading && !submissionsFailed && !submissions.length" class="sk-empty">No capture form has been saved yet.', $tab);
        $this->assertStringContainsString('Could not load the submitted forms.', $tab);
    }

    public function test_editing_a_draft_alert_from_the_list_reads_its_messages_first(): void
    {
        $page = $this->page();

        $this->assertStringContainsString('async openEditAlert(a) {', $page);
        $this->assertMatchesRegularExpression(
            "/async openEditAlert\\(a\\) \\{.*?if \\(!a\\.body\\) \\{\\s*try \\{\\s*a = \\{ \\.\\.\\.a, \\.\\.\\.\\(await \\\$api\\.get\\('\\/admin\\/sakuna\\/alerts\\/' \\+ a\\.ref\\)\\)\\.data \\};.*?return;.*?this\\.alertForm = \\{/s",
            $page,
        );
        // The list row's Edit still hands over the row; the fetch fills the gap.
        $this->assertStringContainsString('@click="openEditAlert(a)"', $this->tab('tab-alerts'));
    }

    public function test_all_livelihoods_clears_the_occupation_and_the_trend_names_its_bucket(): void
    {
        $tab = $this->tab('tab-reports');

        // '' (All livelihoods), or a group the code is not in, drops the code
        // chosen before. The rule now lives in groupChanged() itself (the root
        // cause), so the select only calls it.
        $this->assertStringContainsString('@change="groupChanged()"', $tab);
        $this->assertStringNotContainsString('f.livelihood_code = \'\'; groupChanged()', $tab);
        $report = $this->source('resources/js/sakuna-report.js');
        $this->assertStringContainsString(
            "if (code && (!group || !(this.\$store.livelihood?.items(group) || []).some((i) => i.code === code))) this.f.livelihood_code = '';",
            $report,
        );
        $this->assertStringNotContainsString('startsWith(this.f.livelihood_group)', $report);

        // Weekly buckets (a week apart) are called weekly, not 'Per day'.
        $this->assertStringNotContainsString('<span class="sk-csub">Per day</span>', $tab);
        $this->assertStringContainsString("> 864e5 ? 'Per week' : 'Per day'", $tab);

        // On a phone the date popover spans the card instead of running off it.
        $this->assertStringContainsString('class="sk-card sk-popover max-sm:!left-2 max-sm:!right-2 max-sm:!w-auto" :style="rangePos"', $tab);
    }

    public function test_the_report_date_button_names_the_30_days_the_server_counts_without_a_from_date(): void
    {
        $tab = $this->tab('tab-reports');

        // No preset sends empty dates under the name 'All dates' any more.
        $this->assertStringNotContainsString("presetRange('all')", $tab);
        $this->assertStringNotContainsString('>All dates<', $tab);
        $this->assertStringNotContainsString('x-text="rangeLabel"', $tab);
        $this->assertStringNotContainsString(':title="rangeLabel"', $tab);

        // Without a From date the label is the API's default period
        // (SakunaReport::period(): the 30 days to To, or to today).
        $label = "f.from ? rangeLabel : f.to ? '30 days to ' + new Date(f.to + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Last 30 days'";
        $this->assertStringContainsString(':title="'.$label.'"', $tab);
        $this->assertStringContainsString('<span x-text="'.$label.'">Last 30 days</span>', $tab);
    }

    public function test_the_damage_form_can_clear_fields_and_never_keeps_a_stale_photo(): void
    {
        $page = $this->page();
        $tab = $this->tab('tab-damage');

        // The file input is emptied whenever the form holds no photo.
        $this->assertStringContainsString("x-effect=\"if (!damageForm.photoFile) \$el.value = ''\"", $tab);

        // A cleared field is sent on edit (as ''), only once its saved value is known.
        $this->assertStringNotContainsString('if (this.damageForm.notes) body.append(', $page);
        $this->assertStringNotContainsString('if (this.damageForm.incident_id) body.append(', $page);
        $this->assertStringContainsString("const clears = (key) => this.damageFormMode === 'edit' && this.damageClearable.includes(key);", $page);
        $this->assertStringContainsString("if (v !== '') body.append(key, v);", $page);
        // units_affected is NOT NULL (default 0): a cleared Units is sent as 0, never null.
        $this->assertStringContainsString("else if (clears(key)) body.append(key, key === 'units_affected' ? '0' : '');", $page);
        $this->assertStringContainsString("this.damageClearable = ['units_affected', 'estimated_cost'];", $page);
        // Notes and the incident link only after the detail fetch; an incident
        // the picker does not hold is never sent as cleared.
        $this->assertStringContainsString("this.damageClearable = ['units_affected', 'estimated_cost', 'notes', ...(inc || !res.data?.incident_ref ? ['incident_id'] : [])];", $page);
    }

    public function test_the_csv_export_keeps_every_column_and_escapes_as_excel_reads_it(): void
    {
        $page = $this->page();

        $this->assertStringNotContainsString('Object.keys(rows[0]).filter(k => !Array.isArray(rows[0][k])', $page);
        $this->assertStringNotContainsString('JSON.stringify(r[k]', $page);
        // Every row's keys; a null-valued key stays; lists are joined.
        $this->assertStringContainsString('const keys = [...new Set(rows.flatMap(r => Object.keys(r || {})))].filter(k => rows.every(r => plain(r?.[k])));', $page);
        $this->assertStringContainsString("Array.isArray(v) ? v.map(text).filter(Boolean).join('; ')", $page);
        // Quotes doubled, a BOM, CRLF, UTF-8.
        $this->assertStringContainsString('q + t.split(q).join(q + q) + q', $page);
        $this->assertStringContainsString('const csv = String.fromCharCode(0xFEFF) + ', $page);
        $this->assertStringContainsString("type: 'text/csv;charset=utf-8'", $page);
    }

    public function test_sakuna_fills_the_page_width_and_says_when_a_list_arrived_cut(): void
    {
        $page = $this->page();

        // No width cap: no empty band on the right of wide screens.
        $this->assertStringContainsString('class="sk space-y-3"', $page);
        $this->assertStringNotContainsString('max-w-[1480px]', $page);

        // Evacuees and relief count their tiles from the rows that arrived: the tab says how many.
        $this->assertStringContainsString("this.noteCut('evacuees', ev);", $page);
        $this->assertStringContainsString("this.noteCut('relief', res);", $page);
        $this->assertStringContainsString('const cut = res?.meta?.truncated ? { loaded: res.meta.loaded, total: res.meta.total } : null;', $page);
        $this->assertStringContainsString('x-show="tabCopy && listCut[tabCopy[0]]"', $page);
    }

    public function test_the_incident_window_header_wraps_on_a_phone(): void
    {
        $modal = $this->tab('_incident-detail-modal');

        $this->assertStringContainsString('<div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 px-6 py-4 border-b border-brand-600/10 flex-shrink-0">', $modal);
        $this->assertStringContainsString('<div class="flex items-center gap-3 min-w-0">', $modal);
        $this->assertStringContainsString('<div class="flex flex-wrap items-center justify-end gap-2 ml-auto">', $modal);
    }

    public function test_a_failed_occupant_load_is_not_an_empty_center(): void
    {
        $page = $this->page();
        $tab = $this->tab('tab-centers');

        $this->assertStringNotContainsString('the tab just shows no families rather than failing the modal', $page);
        $this->assertStringContainsString('if (this.selectedCenter?.ref === c.ref) this.centerEvacueesFailed = true;', $page);
        $this->assertStringContainsString('<div x-show="centerEvacueesLoading"', $tab);
        $this->assertStringContainsString('@click="loadCenterEvacuees()"', $tab);
        $this->assertStringContainsString('x-show="!centerEvacueesLoading && !centerEvacueesFailed && centerEvacuees.length === 0"', $tab);
    }

    public function test_a_resource_quantity_never_goes_below_the_units_deployed(): void
    {
        $page = $this->page();
        $tab = $this->tab('tab-resources');

        $this->assertStringContainsString("deployed: r.deployed || 0 };", $page);
        $this->assertStringContainsString("Number(this.resourceForm.quantity) < (this.resourceForm.deployed || 0)", $page);
        $this->assertStringContainsString("|| this.resourceQuantityTooLow) return;", $page);
        // deployed is shown, never sent.
        $this->assertDoesNotMatchRegularExpression('/const payload = \{ name: this\.resourceForm\.name[^\n]*deployed/', $page);

        $this->assertStringContainsString(":min=\"resourceFormMode === 'edit' ? (resourceForm.deployed || 0) : 0\"", $tab);
        $this->assertStringContainsString("resourceForm.quantity === '' || resourceQuantityTooLow\" @click=\"saveResource()\"", $tab);
    }

    public function test_reports_marked_not_valid_are_not_active_and_can_be_listed(): void
    {
        $tab = $this->tab('tab-incidents');

        $this->assertStringContainsString("incidentsList.filter(i => !['Completed','Archived','Rejected'].includes(i.status)).length", $tab);
        $this->assertStringContainsString('<option value="Rejected">Not valid</option>', $tab);
        // The status on record keeps its canonical name; only the label is the incident's word.
        $this->assertStringContainsString("'Rejected': 'Not valid',", $this->page());
    }
}
