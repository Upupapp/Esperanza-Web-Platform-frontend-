<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * QA sweep 2026-11-18, assignment web-1: the Constituents registry, its
 * family and household windows, To Verify, Data Quality and the merge and
 * lifecycle windows.
 */
class Sweep1118web1Test extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    private function page(): string
    {
        return $this->source('resources/views/admin/constituents.blade.php');
    }

    private function part(string $name): string
    {
        return $this->source('resources/views/admin/constituents/'.$name.'.blade.php');
    }

    /** Laravel refused every save: the amenity columns are strings, the forms sent true/false. */
    public function test_household_amenities_go_to_the_server_as_words(): void
    {
        $add = $this->part('modal-add-household');
        $this->assertStringContainsString("const body = { ...this.form, electricity: this.form.electricity ? 'Yes' : 'No', water: this.form.water ? 'Yes' : 'No', toilet: this.form.toilet ? 'Yes' : 'No' };", $add);
        $this->assertStringContainsString("await \$api.post('/admin/households', body);", $add);
        $this->assertStringNotContainsString("\$api.post('/admin/households', this.form)", $add);

        $detail = $this->part('modal-household-detail');
        $this->assertStringNotContainsString('!!selectedHousehold.', $detail);
        $this->assertStringContainsString('electricity: hasAmenity(selectedHousehold.electricity),', $detail);
        // An untouched box keeps the household's own word; a changed one is Yes or No.
        $this->assertStringContainsString('if (payload[k] === hasAmenity(selectedHousehold[k])) delete payload[k];', $detail);
        $this->assertStringContainsString("else payload[k] = payload[k] ? 'Yes' : 'No';", $detail);
        // A new head typed here is made a resident of the household's barangay.
        $this->assertStringContainsString('if (!payload.head_name) delete payload.head_name; else payload.barangay = selectedHousehold.barangay;', $detail);
    }

    /** The unchanged head was sent every time, and the server failed creating it again. */
    public function test_a_family_edit_sends_the_head_only_when_it_changed(): void
    {
        $detail = $this->part('modal-family-detail');

        $this->assertStringContainsString("if (!head || head === (selectedFamily.head_name || '').trim()) delete payload.head_name;", $detail);
        $this->assertStringContainsString('else { payload.head_name = head; payload.barangay = selectedFamily.barangay; }', $detail);
        $this->assertStringContainsString("await \$api.put('/admin/families/' + selectedFamily.ref, payload);", $detail);
        $this->assertStringNotContainsString("selectedFamily.ref, this.edit)", $detail);
    }

    /** The list rows carry no tags, so both tag filters emptied their lists. */
    public function test_the_tag_filters_ask_the_server_who_carries_the_tag(): void
    {
        $page = $this->page();

        // Registry: a tag always goes to the server, whose page is trusted.
        $this->assertStringContainsString("if (!this.tagFilter && (!this.registryTruncated || !(q || this.barangayFilter || this.verificationFilter))) return '';", $page);
        // The rows' tags read as the staff keys (a resident's own 'PWD' is 'pwd').
        $this->assertStringContainsString('if (this.tagFilter && !this.remoteActive && !this.registryTags(r.tags).includes(this.tagFilter)) return false;', $page);

        // To Verify: the tagged residents' refs, asked for when a tag is chosen.
        $this->assertStringContainsString("this.\$watch('cvTagFilter', () => this.loadCvTagged());", $page);
        $this->assertStringContainsString("refs = new Set(((await \$api.getAll('/admin/residents', { params: { tag } })).data || []).map(r => r.ref));", $page);
        $this->assertStringContainsString('if (this.cvTagFilter && !this.cvTagged.refs?.has(i.ref)) return false;', $page);
        $this->assertStringNotContainsString('(i.tags || []).includes(this.cvTagFilter)', $page);
        $this->assertStringContainsString('x-show="cvTagged.loading"', $this->part('tab-citizen-verification'));
    }

    /** JSON cells split rows, quotes were backslash-escaped, and =formulas ran in Excel. */
    public function test_the_registry_csv_has_flat_quoted_formula_safe_cells(): void
    {
        $page = $this->page();
        $export = substr($page, strpos($page, 'exportCSV(rows, filename) {'), 1800);

        $this->assertStringNotContainsString('JSON.stringify', $export);
        // A livelihood becomes its label, a programs list one joined cell.
        $this->assertStringContainsString("const flat=v=>Array.isArray(v)?v.map(flat).filter(x=>x!=='').join('; '):(v&&typeof v==='object'?(v.full_label??v.label??v.name??v.ref??''):(v??''));", $export);
        // A leading = + - @ tab or CR is opened as text; quotes are doubled.
        $this->assertStringContainsString("const safe=/^[=+\\-@\\t\\r]/.test(s)?'\\''+s:s;", $export);
        $this->assertStringContainsString("'\\x22'+safe.replace(/\\x22/g,'\\x22\\x22')+'\\x22'", $export);
        $this->assertStringContainsString("const csv='\\uFEFF'+[keys.map(cell).join(','),...rows.map(r=>keys.map(k=>cell(r[k])).join(','))].join('\\r\\n');", $export);
        $this->assertStringContainsString("type:'text/csv;charset=utf-8'", $export);
    }

    /** Needs attention counted resolved issues too, so it never went down. */
    public function test_needs_attention_counts_open_issues_only(): void
    {
        $page = $this->page();

        $this->assertStringContainsString("issues: this.qualityIssues.filter(i => i.status !== 'Resolved').length,", $page);
        $this->assertStringNotContainsString('issues: this.qualityIssues.length,', $page);
        $this->assertStringContainsString("meta('const_data_quality', '/admin/data-quality', { status: 'Open,In Review' }),", $page);
    }

    /** A typed head always made a second record of a resident already on file. */
    public function test_a_new_family_or_household_head_can_be_picked_from_the_registry(): void
    {
        $page = $this->page();
        $this->assertStringContainsString("const res = await \$api.get('/admin/residents', { params: { q, ...(barangay ? { barangay } : {}), per_page: 6 }, silent: true });", $page);

        foreach (['modal-add-family', 'modal-add-household'] as $modal) {
            $src = $this->part($modal);
            $this->assertStringContainsString('@input.debounce.300ms="lookHead()"', $src, $modal);
            $this->assertStringContainsString('const rows = await headMatches(this.form.head_name, this.form.barangay);', $src, $modal);
            $this->assertStringContainsString('<button type="button" @click="pickHead(r)"', $src, $modal);
            // Picked: sent by ref; only a name not picked is a new resident.
            $this->assertStringContainsString('if (this.headPicked) { body.head_ref = this.head.ref; delete body.head_name; }', $src, $modal);
            $this->assertStringNotContainsString("If they aren't already a resident on file, a new resident record is created", $src, $modal);
        }

        // The edit forms too: a family's mother made its head was a second record of her.
        foreach (['modal-family-detail' => 'selectedFamily', 'modal-household-detail' => 'selectedHousehold'] as $modal => $record) {
            $src = $this->part($modal);
            $this->assertStringContainsString('x-model="edit.head_name" @input.debounce.300ms="lookHead()"', $src, $modal);
            $this->assertStringContainsString("const rows = await headMatches(this.edit.head_name, {$record}.barangay);", $src, $modal);
            $this->assertStringContainsString('<button type="button" @click="pickHead(r)"', $src, $modal);
            $this->assertStringContainsString('if (this.headPicked) { delete payload.head_name; payload.head_ref = this.head.ref; }', $src, $modal);
        }
        // Only spaces typed keep the household's head rather than detach it.
        $this->assertStringContainsString("if (typeof payload.head_name === 'string') payload.head_name = payload.head_name.trim();", $this->part('modal-household-detail'));
    }

    /** The list rows have no flood risk or amenities: every household read Unknown and None. */
    public function test_the_households_list_shows_only_what_its_rows_carry(): void
    {
        $list = $this->part('view-households');

        $this->assertStringContainsString('<span x-show="h.flood_risk !== undefined"', $list);
        $this->assertStringContainsString('x-show="h.electricity !== undefined || h.water !== undefined || h.toilet !== undefined"', $list);
        $this->assertStringContainsString(":class=\"hasAmenity(h.electricity) ? 'text-amber-500' : 'text-ink-faint'\"", $list);
        $this->assertStringNotContainsString("h.electricity ? 'Has Electricity' : 'No Electricity'", $list);
        $this->assertStringContainsString('<span x-show="selectedHousehold?.flood_risk !== undefined"', $this->part('modal-household-detail'));
    }

    /** The accounts' pager showed over the family and household lists. */
    public function test_each_to_verify_pager_sits_with_its_own_list(): void
    {
        $tab = $this->part('tab-citizen-verification');

        foreach ([['Individual', 'filteredCitizenAccounts', 'cv'], ['Family', 'filteredCvFamilies', 'cvFam'], ['Household', 'filteredCvHouseholds', 'cvHh']] as [$type, $list, $key]) {
            $this->assertMatchesRegularExpression(
                '/<div x-show="cvType === \''.$type.'\'"[^>]*>\s*<x-ui\.table>(?:(?!<x-ui\.table).)*<\/x-ui\.table>\s*<x-admin\.pager list="'.$list.'" key="'.$key.'" \/>\s*<\/div>/s',
                $tab,
                $type
            );
        }
        $this->assertStringNotContainsString('<x-ui.table x-show="cvType', $tab);
    }

    /** The card counted For Validation and Partially Verified; its filter kept one. */
    public function test_needs_review_keeps_both_waiting_states(): void
    {
        $page = $this->page();
        $tab = $this->part('tab-citizen-verification');

        $this->assertSame(2, substr_count($tab, "@click=\"cvStatusFilter = 'Needs Review'\" class=\"cursor-pointer\" label=\"Needs Review\""));
        $this->assertStringNotContainsString("@click=\"cvStatusFilter = 'Partially Verified'\"", $tab);
        $this->assertStringContainsString("return this.cvStatusFilter === 'Needs Review' ? ['Partially Verified', 'For Validation'].includes(v) : v === this.cvStatusFilter;", $page);
        $this->assertStringContainsString('if (!this.cvStatusMatches(f.verification)) return false;', $page);
        $this->assertStringContainsString('if (!this.cvStatusMatches(h.verification)) return false;', $page);
        // The status picker can show it.
        $this->assertStringContainsString("x-for=\"s in ['Needs Review', ...cvVerificationOptions]\"", $tab);
    }

    /** The three record buttons need about 440 px and ran off a 390 px phone. */
    public function test_the_record_switcher_scrolls_inside_itself_on_a_phone(): void
    {
        $tab = $this->part('tab-citizen-verification');

        $this->assertStringContainsString('<div class="surface-glass rounded-[26px] p-1.5 w-fit max-w-full">', $tab);
        $this->assertStringContainsString('<div class="inline-flex max-w-full overflow-x-auto items-center gap-1 surface-pressed rounded-xl p-1">', $tab);
        $this->assertStringContainsString('class="inline-flex shrink-0 items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl transition-colors whitespace-nowrap"', $tab);
    }

    /** A pair past the first 2,000 residents showed nothing but dashes. */
    public function test_the_merge_preview_reads_records_past_the_loaded_rows(): void
    {
        $page = $this->page();
        $merge = $this->part('modal-merge');

        $this->assertStringContainsString('fetchedRecords = { ...fetchedRecords, [refs[idx]]: res.data };', $merge);
        // A loaded row (with the fetched detail merged in) first, then the fetched record.
        $this->assertStringContainsString('return this.individuals.find(x => x.ref === ref) || this.fetchedRecords[ref] || null;', $page);
        $this->assertStringContainsString('const person = this.dqRecord(ref);', $page);
    }

    /** Record B was cut off on a phone with no way to scroll to it. */
    public function test_the_merge_comparison_scrolls_sideways(): void
    {
        $merge = $this->part('modal-merge');

        $this->assertStringContainsString('<div class="rounded-xl overflow-x-auto border border-slate-200">', $merge);
        $this->assertStringNotContainsString('<div class="rounded-xl overflow-hidden border border-slate-200">', $merge);
    }

    /** The resident opened under the family or household window. */
    public function test_a_member_opens_the_resident_on_top(): void
    {
        $page = $this->page();
        $open = substr($page, strpos($page, 'async openIndividual(r) {'), 500);

        $this->assertStringContainsString('this.showFamilyDetail = false;', $open);
        $this->assertStringContainsString('this.showHouseholdDetail = false;', $open);
    }

    /** A household that reported None read Available. */
    public function test_the_amenities_tab_reads_the_households_words(): void
    {
        $page = $this->page();
        $detail = $this->part('modal-household-detail');

        $this->assertStringContainsString("return !s ? 'unknown' : (['none', 'no'].includes(s) ? 'no' : 'yes');", $page);
        $this->assertStringContainsString("hasAmenity(v) { return this.amenityState(v) === 'yes'; },", $page);
        foreach (['water', 'electricity', 'toilet'] as $k) {
            $this->assertStringContainsString("x-text=\"{ yes: 'Available', no: 'Not Available', unknown: 'Not Recorded' }[amenityState(selectedHousehold.{$k})]\"", $detail);
            $this->assertStringContainsString("x-text=\"amenityNote(selectedHousehold.{$k})\"", $detail);
            $this->assertStringNotContainsString("selectedHousehold.{$k} ? 'Available'", $detail);
        }
    }

    /** Escape kept the last resident's death details, and every action sent every field. */
    public function test_the_lifecycle_form_is_fresh_and_sends_only_its_fields(): void
    {
        $lifecycle = $this->part('modal-lifecycle');

        $this->assertStringContainsString("x-init=\"\$watch('open', v => { if (v) resetLifecycleForm(); })\"", $lifecycle);
        $this->assertStringContainsString("deceased: ['date_of_passing', 'cause_of_death', 'informant_name', 'death_cert_ref'],", $lifecycle);
        $this->assertStringContainsString('{ action, ...Object.fromEntries(fields.map(k => [k, this.form[k]])) }', $lifecycle);
        $this->assertStringNotContainsString('{ action, ...this.form }', $lifecycle);
    }

    /** These windows draw their own header: the modal added an empty title bar and a second X. */
    public function test_windows_with_their_own_header_are_bare(): void
    {
        $this->assertStringContainsString('<x-ui.modal maxWidth="sm" :bare="true">', $this->part('modal-lifecycle'));
        $this->assertStringContainsString('<x-ui.modal maxWidth="2xl" :bare="true">', $this->part('modal-merge'));
        $this->assertStringContainsString('<x-ui.modal maxWidth="xl" :bare="true">', $this->part('modal-dq-detail'));
    }

    /** View-only roles filled in the whole form and were refused on Save. */
    public function test_the_add_buttons_need_the_create_permission(): void
    {
        $this->assertStringContainsString("<button x-show=\"can('const_family','create')\"\n            @click=\"showAddFamily = true;", $this->part('view-families'));
        $this->assertStringContainsString("<button x-show=\"can('const_household','create')\"\n            @click=\"showAddHousehold = true;", $this->part('view-households'));
    }
}
