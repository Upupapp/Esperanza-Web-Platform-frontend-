@php
    // The resident's requests, their documents and every action on them go
    // through the API (see the x-data below); this block only builds the
    // catalog and lookup tables the wizard and tracker render from.
    // TAB 04: fixtures live in resources/data/citizen/document-requests.php so this view stays presentation only.
    $__fixtures = require base_path('resources/data/citizen/document-requests.php');
    // The resident's own requests come from the API after paint
    // (GET /citizen/requests). This used to be a fixture list, and the New
    // Request wizard only added a row to the page: nothing reached the
    // Municipality.
    $requests = [];

    // The 6 core municipal Dokyu types (Cedula, Certificate of Residency,
    // Certificate of Indigency, Business Permit New/Renewal, Real Property
    // Tax Clearance) now live in config/esperanza_municipal_documents.php
    // alongside the rest of the municipal catalog, so this file has a single
    // source of truth for fees (see admin Settings > Service Catalog) —
    // populated by the foreach below, same as every other municipal document.
    $documentTypes = $__fixtures['documentTypes'];

    // Additional municipal documents (MCRO = Municipal Civil Registrar's
    // Office, and other municipal offices like Agriculture) — these are
    // municipal by definition, never barangay, per config/esperanza_municipal_documents.php.
    // Carry structured 'fields' + a request-slip preview, same mechanism as
    // the barangay documents below but with 'previewType' => 'municipal_request'
    // instead of 'barangay', since these represent requests for certified
    // copies of civil registry records rather than a barangay-issued
    // certification with legal paragraph text.
    foreach (config('esperanza_municipal_documents.documents') as $md) {
        $documentTypes[] = [
            'key' => $md['key'],
            'name' => $md['name'],
            'category' => $md['category'],
            'office' => $md['office'],
            'office_short' => $md['office_short'] ?? $md['office'],
            'level' => $md['level'],
            'fee' => $md['fee'],
            'days' => $md['days'],
            'requirements' => $md['requirements'],
            'process' => $md['process'],
            'fields' => $md['fields'],
            'certTitle' => $md['title'],
            'formNo' => $md['formNo'] ?? null,
            'officiant' => $md['officiant'] ?? null,
            'officiantTitle' => $md['officiantTitle'] ?? null,
            'technician' => $md['technician'] ?? null,
            'technicianTitle' => $md['technicianTitle'] ?? null,
            'verifier' => $md['verifier'] ?? null,
            'verifierTitle' => $md['verifierTitle'] ?? null,
            'previewType' => 'municipal_request',
        ];
    }

    // Barangay-issued documents — every barangay in config/esperanza_barangay_documents.php
    // currently shares the exact same document schema (fields/requirements/process), only
    // the barangay name/office/seal differ. Rather than pre-expanding that schema into 20
    // full duplicate copies server-side (which used to bloat this page's payload ~10x for
    // data 19/20 of which no citizen ever sees, since Step 1 only shows their own barangay),
    // we ship the schema ONCE ($barangayDocTemplate) plus the list of barangay names
    // ($barangayNames), and the wizard's own x-data expands them into the same flat 'docs'
    // shape client-side on init — same final data/behavior, a fraction of the bytes.
    // $barangayCatalog carries the branding (seal, officials) per barangay for the tab's
    // letterhead. Only Labangtaytay is onboarded with real officials/history so far — add
    // more barangays to config/esperanza_barangay_documents.php and they pick up here
    // automatically, no other changes needed (as long as their 'documents' array still
    // matches the shared schema; if any barangay ever needs a genuinely different set of
    // documents, that one barangay would need to go back to explicit per-barangay expansion).
    $barangayCatalog = $__fixtures['barangayCatalog'];
    $barangayNames = $__fixtures['barangayNames'];
    $barangayDocTemplate = null;
    foreach (config('esperanza_barangay_documents') as $slug => $info) {
        $barangayCatalog[$info['label']] = [
            'label' => $info['label'],
            'seal' => $info['seal'],
            'officials' => $info['officials'],
        ];
        $barangayNames[] = $info['label'];
        if ($barangayDocTemplate === null) {
            $barangayDocTemplate = $info['documents'];
        }
    }

    // Attach expected-processing-time onto each seeded request by matching its
    // document type name against $documentTypes, so the citizen-facing
    // "Track Request" view can compute an expected release date without
    // needing every mock row to duplicate that detail by hand. Barangay
    // documents live in $barangayDocTemplate now (see above), so both are
    // merged here for this lookup only — $documentTypes itself stays municipal-only.
    $daysByType = collect($documentTypes)->pluck('days', 'name')
        ->union(collect($barangayDocTemplate)->pluck('days', 'name'));
    $officeByType = collect($documentTypes)->pluck('office', 'name');
    // The same, by service key: the API's service_key is exact, while display
    // names differ between this catalog and the backend's for several
    // municipal services. The name maps stay as the fallback.
    $daysByKey = collect($documentTypes)->merge($barangayDocTemplate ?? [])
        ->filter(fn ($d) => ! empty($d['key']))->pluck('days', 'key');
    // The pickup directory below is keyed by an office's SHORT name, which the
    // API does not send; the catalog has it per service key.
    $officeShortByKey = collect($documentTypes)->filter(fn ($d) => ! empty($d['key']))->pluck('office_short', 'key');

    // Where to follow up / claim a released document, keyed by office name.
    // Barangay Hall entries vary per barangay (the office string already
    // carries "Barangay Hall (Brgy. X)"), so those are matched by prefix.
    $pickupDirectory = $__fixtures['pickupDirectory'];

    $fileCardStyle = $__fixtures['fileCardStyle'];
@endphp

<x-layouts.citizen title="Dokyu" subtitle="Request and track municipal documents online." active="document-requests">
    <div
        x-data="{
            tab: 'all',
            requests: [],
            requestsLoading: true,
            requestsFailed: false,
            daysByType: @js($daysByType),
            daysByKey: @js($daysByKey),
            officeShortByKey: @js($officeShortByKey),
            officeByType: @js($officeByType),
            papeles: [],
            papelesLoading: true,
            doneStatuses: ['Completed', 'Released', 'Rejected', 'Cancelled', 'Archived'],
            statusStyles: {
                'Draft': ['bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
                'Submitted': ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
                'Under Verification': ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
                'Under Review': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
                'Resubmitted': ['bg-sky-50 text-sky-700 ring-sky-200', 'bg-sky-500'],
                'Approved': ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
                'Rejected': ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
                'Mark to Release': ['bg-teal-50 text-teal-700 ring-teal-200', 'bg-teal-500'],
                'Released': ['bg-cyan-50 text-cyan-700 ring-cyan-200', 'bg-cyan-500'],
                'Completed': ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
                'Cancelled': ['bg-slate-100 text-ink-muted ring-slate-200', 'bg-slate-400'],
                'Archived': ['bg-slate-100 text-ink-muted ring-slate-200', 'bg-slate-300'],
            },
            // Rejected and cancelled requests have their own tab and tile, so the
            // tiles add up to the total (live test 2026-10-07, UX-7).
            stoppedStatuses: ['Rejected', 'Cancelled'],
            get filteredRequests() {
                return this.requests.filter(r => {
                    if (this.tab === 'all') return true;
                    const stopped = this.stoppedStatuses.includes(r.status);
                    if (this.tab === 'stopped') return stopped;
                    const isDone = this.doneStatuses.includes(r.status);
                    return this.tab === 'done' ? (isDone && !stopped) : !isDone;
                });
            },
            pickupDirectory: @js($pickupDirectory),
            // Same lookup the New Request wizard uses for its live preview's
            // letterhead (seal/officials per barangay) — duplicated here (it's
            // a small array, already computed once server-side) so the Track
            // Request modal's Document Preview can resolve the SAME letterhead
            // for an already-submitted request, without needing to share Alpine
            // scope with the wizard's own separate x-data.
            barangayCatalog: @js($barangayCatalog),
            docTypeLookup: @js(config('esperanza_document_type_lookup')),
            progressOpen: false,
            // The proof-of-release preview inside the request's progress view.
            proofOpen: false,
            docPreviewOpen: false,
            selectedRequest: null,
            resubmitNote: '',
            resubmitting: false,
            // ---- From the API -----------------------------------------------
            lang(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
            apiMessage(err, fallback) { return err && err.localised ? err.localised(this.$store.lang.current) : fallback; },
            formatDay(iso) {
                if (!iso) return '';
                const d = new Date(iso);
                return isNaN(d) ? iso : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },
            async loadRequests() {
                this.requestsLoading = true;
                this.requestsFailed = false;
                try {
                    const res = await $api.getAll('/citizen/requests', { params: { sort: '-submitted_at' } });
                    this.requests = (res.data || []).filter((r) => r.type === 'dokyu').map((r) => ({
                        ref: r.ref,
                        type: r.service || '—',
                        serviceKey: r.service_key || null,
                        barangay: r.barangay || null,
                        office: r.office || this.officeByType[r.service] || '',
                        purpose: '',
                        days: this.daysByKey[r.service_key] || this.daysByType[r.service] || '3-5 working days',
                        submitted: this.formatDay(r.submitted),
                        submitted_at: r.submitted,
                        status: r.status,
                        requirements: null,
                    }));
                } catch { this.requestsFailed = true; } finally {
                    this.requestsLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
            async loadPapeles() {
                this.papelesLoading = true;
                try {
                    const res = await $api.get('/citizen/papeles');
                    this.papeles = res.data || [];
                } catch { this.papeles = []; } finally { this.papelesLoading = false; }
            },
            applyDetail(r, d) {
                r.status = d.status;
                r.office = d.office || r.office;
                r.barangay = d.barangay || r.barangay;
                r.remarks = d.decision_remarks || null;
                r.purpose = (d.form_data && d.form_data.purpose) ? [].concat(d.form_data.purpose).join(', ') : r.purpose;
                r.formSnapshot = d.form_data || {};
                r.canResubmit = !!d.can_resubmit;
                // Required documents not uploaded yet hold the resubmit back (QA run 2026-10-05).
                r.missingRequired = d.missing_required || [];
                r.payment = d.payment || null;
                // The photo or scan the office took at release, once released
                // (Esperanza QA, 2026-10-07). A signed, short-lived link from the
                // resident's own request detail.
                r.releaseProof = d.proof_of_release?.file || null;
                r.history = d.history || [];
                r.requirements = (d.requirements || []).map((q) => ({
                    key: q.key,
                    name: q.label,
                    status: q.status === 'Pending Review' ? (q.file ? 'Submitted' : 'Not submitted') : q.status,
                    reject_reason: q.reason,
                    remarks: q.remarks,
                    file: q.file?.name || null,
                    file_url: q.file?.url || null,
                    attachable: !!q.attachable,
                    // Flagged, or rejected while the office can still review a
                    // replacement (Esperanza QA, 2026-10-07).
                    replaceable: !!q.replaceable,
                    _busy: false,
                }));
            },
            async openProgress(r) {
                this.selectedRequest = r;
                this.progressOpen = true;
                this.proofOpen = false;
                try {
                    const res = await $api.get('/citizen/requests/' + encodeURIComponent(r.ref));
                    this.applyDetail(r, res.data);
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not load ' + r.ref + '.'), variant: 'error' });
                } finally { this.$nextTick(() => window.renderIcons?.()); }
            },
            // Attach the document for a requirement the office has not decided
            // yet, or replace one it flagged. Both are real uploads now.
            async sendRequirementFile(req, file, action) {
                if (!file || req._busy) return;
                const r = this.selectedRequest;
                const form = new FormData();
                form.append('file', file);
                req._busy = true;
                try {
                    const res = await $api.post('/citizen/requests/' + encodeURIComponent(r.ref) + '/requirements/' + encodeURIComponent(req.key) + '/' + action, form);
                    this.applyDetail(r, res.data);
                    this.$dispatch('toast', { message: action === 'replace' ? this.lang('Replacement uploaded — awaiting review.', 'Na-upload ang kapalit — hinihintay ang pagsusuri.') : this.lang('Document attached.', 'Naikabit ang dokumento.'), variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, this.lang('Could not upload the file.', 'Hindi ma-upload ang file.')), variant: 'error' });
                } finally { req._busy = false; this.$nextTick(() => window.renderIcons?.()); }
            },
            replaceRequirementFile(req, file) { return this.sendRequirementFile(req, file, 'replace'); },
            attachRequirementFile(req, file) { return this.sendRequirementFile(req, file, 'attach'); },
            get canResubmitRequirements() {
                return !!(this.selectedRequest && this.selectedRequest.canResubmit);
            },
            // Under Review -> Resubmitted on the server: the same request, the
            // same ref, its history kept.
            async resubmitRequirements() {
                if (this.resubmitting || !this.canResubmitRequirements) return;
                const r = this.selectedRequest;
                this.resubmitting = true;
                try {
                    const res = await $api.post('/citizen/requests/' + encodeURIComponent(r.ref) + '/resubmit');
                    this.applyDetail(r, res.data);
                    this.$dispatch('toast', { message: this.lang(r.ref + ' resubmitted for review.', 'Naisumite muli ang ' + r.ref + ' para sa pagsusuri.'), variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: this.apiMessage(err, 'Could not resubmit.'), variant: 'error' });
                } finally { this.resubmitting = false; }
            },
            stepOrder: ['Submitted', 'Under Verification', 'Approved', 'Mark to Release', 'Released'],
            stepIndex(status) {
                const map = { 'Submitted': 0, 'Under Verification': 1, 'Under Review': 1, 'Resubmitted': 1, 'Approved': 2, 'Mark to Release': 3, 'Released': 4, 'Completed': 4, 'Rejected': 0, 'Cancelled': 0 };
                return map[status] ?? 0;
            },
            // The resident's barangay, for a barangay document's letterhead and
            // where to claim it (the wizard below has its own copy).
            get myBarangay() { return this.$store.citizenSession.account?.barangay ?? null; },
            get pickupInfo() {
                const r = this.selectedRequest;
                if (!r) return null;
                // A barangay document is claimed at that barangay's hall; the
                // backend names the office 'Barangay Office'.
                if (/^Barangay (Hall|Office)/.test(r.office || '')) {
                    const hall = r.office.startsWith('Barangay Hall') ? r.office : 'Barangay Hall (Brgy. ' + (r.barangay || this.myBarangay || '') + ')';
                    return { contact: 'Visit your Barangay Hall', address: hall + ', Esperanza, Masbate' };
                }
                // The directory is keyed by the office's short name; where an
                // office is listed as 'A / B', A is where to claim it.
                const short = (this.officeShortByKey[r.serviceKey] || r.office || '').split('/')[0].trim();
                return this.pickupDirectory[short]
                    || { contact: 'Municipal Hall Trunkline (056) 333-1000', address: 'Municipal Hall, Poblacion, Esperanza, Masbate' };
            },
            get expectedRelease() {
                const r = this.selectedRequest;
                if (!r) return '';
                if (/same day/i.test(r.days)) return r.submitted + ' (' + ($store.lang.current === 'en' ? 'same day' : 'ngayon din') + ')';
                const match = r.days.match(/(\d+)(?!.*\d)/);
                let left = match ? parseInt(match[1]) : 5;
                const d = new Date(r.submitted);
                // Working days skip Saturdays and Sundays, the same rule staff
                // are alerted by when a request runs past this date.
                if (/working/i.test(r.days)) {
                    while (left > 0) { d.setDate(d.getDate() + 1); if (d.getDay() !== 0 && d.getDay() !== 6) left--; }
                } else {
                    d.setDate(d.getDate() + left);
                }
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },
            // The issued document renders from config/esperanza_document_type_lookup.php,
            // found by the request's service key (its docKey) -- or, from a
            // backend that does not send service_key yet, by the service's
            // display name. The snapshot starts from the citizen's own name
            // and fills in what the account has on file. A service with no
            // template returns null, which the empty state says honestly.
            get resolvedDocument() {
                const r = this.selectedRequest;
                if (!r) return null;
                if (r.previewType && r.formSnapshot) {
                    return { previewType: r.previewType, docKey: r.docKey, certTitle: r.certTitle, certSubtitle: r.certSubtitle, snapshot: r.formSnapshot, barangay: r.barangay || this.myBarangay };
                }
                const meta = (r.serviceKey && Object.values(this.docTypeLookup).find((m) => m.docKey === r.serviceKey))
                    || this.docTypeLookup[r.type];
                if (!meta) return null;
                const account = this.$store.citizenSession.account;
                const citizenName = [account?.firstName, account?.lastName].filter(Boolean).join(' ');
                const snapshot = {};
                if (meta.nameField) snapshot[meta.nameField] = citizenName;
                if (meta.purposeField) snapshot[meta.purposeField] = meta.purposeIsArray ? (r.purpose ? [r.purpose] : []) : (r.purpose || '');
                // What the resident actually filed (the server's form_data) wins
                // over the defaults above.
                for (const [key, value] of Object.entries(r.formSnapshot || {})) {
                    if (value !== null && value !== '' && !(Array.isArray(value) && !value.length)) snapshot[key] = value;
                }
                // The citizen's own account already has address/purok/birthdate/
                // sex/civilStatus/occupation on file from registration (see
                // config/esperanza_citizens.php) — merge whichever of those a
                // docKey's template actually uses instead of leaving them
                // blank just because this request never had its own
                // formSnapshot. Never overwrites a field already set above.
                if (account) {
                    const purok = (account.purok || '').replace(/^Purok\s+/i, '');
                    const bioFields = {
                        age: account.age, date_of_birth: account.birthdate, sex: account.sex,
                        civil_status: account.civilStatus, citizenship: 'Filipino', occupation: account.occupation,
                        address: account.address, residence: account.address, purok: purok || null,
                    };
                    for (const [key, value] of Object.entries(bioFields)) {
                        if (!(key in snapshot) && value) snapshot[key] = value;
                    }
                }
                return { previewType: meta.previewType, docKey: meta.docKey, certTitle: meta.certTitle, certSubtitle: meta.certSubtitle || null, snapshot, barangay: r.barangay || this.myBarangay };
            },
            get documentReady() {
                const r = this.selectedRequest;
                return !!(r && ['Approved', 'Mark to Release', 'Released'].includes(r.status) && this.resolvedDocument);
            },
            get selectedRequestBarangayInfo() {
                const b = this.resolvedDocument?.barangay;
                return b ? (this.barangayCatalog[b] ?? null) : null;
            },
            printDocument() {
                document.body.classList.add('dp-printing');
                window.print();
            },
            async downloadDocument() {
                const el = document.getElementById('dp-print-area');
                if (!el) return;
                let html2pdf;
                try {
                    html2pdf = await window.loadHtml2Pdf();
                } catch (e) {
                    this.$dispatch('toast', { message: 'Could not load the PDF generator. Check your connection and try again.', variant: 'error' });
                    return;
                }
                const account = this.$store.citizenSession.account;
                const citizenName = [account?.firstName, account?.lastName].filter(Boolean).join('-') || 'Citizen';
                const docName = (this.resolvedDocument?.certTitle || this.selectedRequest?.type || 'Document').replace(/[^a-z0-9]+/gi, '-');
                document.body.classList.add('dp-printing');
                await this.$nextTick();
                // As the staff download does (admin/document-requests): the seal and
                // watermark loaded, the oklch() colours swapped for rgb() while
                // html2canvas reads the page, and Alpine stripped from its clone.
                // Without the swap every download threw on oklch and saved nothing.
                await window.DokyuEditor.waitForImages(el);
                const restoreColors = window.DokyuEditor.sanitizeExportColors(document.body);
                try {
                    await html2pdf().set({
                        margin: 0,
                        filename: 'Esperanza-' + docName + '-' + citizenName.replace(/[^a-z0-9-]+/gi, '') + '.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: {
                            scale: 2, useCORS: true, allowTaint: false, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff',
                            onclone: (clonedDoc) => {
                                clonedDoc.querySelectorAll('*').forEach((node) => {
                                    Array.from(node.attributes).forEach((attr) => {
                                        if (attr.name.startsWith('x-') || attr.name.startsWith(':') || attr.name.startsWith('@')) {
                                            node.removeAttribute(attr.name);
                                        }
                                    });
                                });
                            },
                        },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                        pagebreak: { mode: ['avoid-all', 'css', 'legacy'], avoid: ['.doc-paper'] },
                    }).from(window.DokyuEditor.exportClone(el)).save();
                } catch (error) {
                    console.error('Citizen document PDF download failed:', error);
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Could not download the PDF. Use Print and choose Save as PDF.' : 'Hindi ma-download ang PDF. Gamitin ang I-print at piliin ang Save as PDF.', variant: 'error' });
                } finally {
                    restoreColors();
                    document.body.classList.remove('dp-printing');
                }
            },
            // Deep-link landing: a notification click appends ?ref=DOK-2026-xxxx
            // (see citizen/notifications.blade.php) — open that exact
            // request's tracking modal instead of just landing on the list.
            init() {
                // Notifications are server-driven now (see resources/js/app.js) —
                // the backend lifecycle itself creates one on every status
                // change, so there is nothing left to backfill here.
                const ref = new URLSearchParams(window.location.search).get('ref');
                this.loadPapeles();
                this.loadRequests().then(() => {
                    if (!ref) return;
                    const match = this.requests.find(r => r.ref === ref);
                    if (match) this.openProgress(match);
                });
            },
        }"
        class="animate-fade-up"
    >

        <x-ui.page-intro id="dokyu" icon="file-text" title="Paano gumagana ang Dokyu" titleEn="How Dokyu works">
            <span x-show="tab === 'all'"><x-ui.t fil="Pumili ng dokumento, kumpirmahin ang iyong detalye, bayaran ang bayarin kung meron (marami, tulad ng Certificate of Indigency, ay libre), pagkatapos i-submit. Nagsisimula sa" en="Pick a document, confirm your details, pay the fee if there is one (many, like the Certificate of Indigency, are free), then submit. Reference numbers start with" /> <span class="font-mono">DOK-</span> <x-ui.t fil="ang reference number." en="." /></span>
            <span x-show="tab === 'active'" x-cloak><x-ui.t fil="Ang mga kahilingang ito ay nire-review, vine-verify, o inihahanda pa. Aabisuhan ka namin kapag may update — hindi na kailangang sumunod pa sa opisina." en="These requests are still being reviewed, verified, or prepared. We'll notify you the moment there's an update — no need to follow up in person." /></span>
            <span x-show="tab === 'done'" x-cloak><x-ui.t fil="Ang mga kahilingang ito ay na-release o kumpleto na. Puwede mo pa ring balikan ang mga ito rito anumang oras kung kailangan mo ng kopya ng detalye." en="These requests have been released or fully completed. You can revisit any of them here anytime you need a copy of the details." /></span>
            <span x-show="tab === 'stopped'" x-cloak><x-ui.t fil="Ang mga kahilingang ito ay tinanggihan o kinansela. Buksan ang isa para makita ang dahilan; puwede kang magsumite ulit ng bagong kahilingan." en="These requests were rejected or cancelled. Open one to see why; you can always file a new request." /></span>
        </x-ui.page-intro>

        <x-citizen.approval-notice service="Dokyu" />

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
            <x-ui.stat-card class="col-span-2 sm:col-span-1" label="Kabuuang Kahilingan" labelEn="Total Requests" value="0" valueBind="requests.length" icon="file-text" color="brand" :delay="0" />
            <x-ui.stat-card label="Isinasagawa" labelEn="In Progress" value="0" valueBind="requests.filter(r => !doneStatuses.includes(r.status) && r.status !== 'Mark to Release').length" icon="loader" color="orange" :delay="40" />
            <x-ui.stat-card label="Handa nang Kunin" labelEn="Mark to Release" value="0" valueBind="requests.filter(r => r.status === 'Mark to Release').length" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card label="Kumpleto" labelEn="Completed" value="0" valueBind="requests.filter(r => ['Released', 'Completed', 'Archived'].includes(r.status)).length" icon="circle-check" color="deep" :delay="120" />
            <x-ui.stat-card label="Hindi Natuloy" labelEn="Not Completed" value="0" valueBind="requests.filter(r => stoppedStatuses.includes(r.status)).length" icon="circle-x" color="red" :delay="160" sublabel="Tinanggihan o kinansela" sublabelEn="Rejected or cancelled" />
        </div>

        <div class="flex items-center gap-3 mb-3">
            <h3 class="text-sm font-semibold text-ink"><x-ui.t fil="Aking mga Kahilingan" en="My Requests" /></h3>
            <x-citizen.status-guide />
        </div>

        <div class="surface-glass rounded-[26px] px-3 py-2.5 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2.5 min-w-0 max-w-full">
                    <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 max-w-full overflow-x-auto">
                        @foreach(['all' => ['Lahat', 'All'], 'active' => ['Isinasagawa', 'In Progress'], 'done' => ['Kumpleto', 'Completed'], 'stopped' => ['Hindi Natuloy', 'Not Completed']] as $key => [$labelFil, $labelEn])
                            <button
                                @click="tab = '{{ $key }}'"
                                class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap"
                                :class="tab === '{{ $key }}' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
                            ><x-ui.t :fil="$labelFil" :en="$labelEn" /></button>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-ink-muted shrink-0" x-text="filteredRequests.length + ' ' + ($store.lang.current === 'en' ? 'of ' + requests.length + ' requests' : 'sa ' + requests.length + ' kahilingan')"></p>
                </div>
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <div x-data="{ open: false, fileTab: 'all', uploading: false,
                    fileKind(doc) {
                        const t = (doc.file_type || '').toLowerCase();
                        if (t.includes('pdf')) return 'PDF';
                        if (t.includes('word') || t.includes('docx')) return 'DOCX';
                        return 'IMG';
                    },
                    async upload(file) {
                        if (!file || this.uploading) return;
                        this.uploading = true;
                        const form = new FormData();
                        form.append('file', file);
                        form.append('name', file.name);
                        form.append('type', 'Supporting document');
                        form.append('category', 'Supporting Documents');
                        try {
                            await $api.post('/citizen/papeles', form);
                            await loadPapeles();
                            $dispatch('toast', { message: lang('Saved to your documents.', 'Naimbak sa iyong mga dokumento.'), variant: 'success' });
                        } catch (err) {
                            $dispatch('toast', { message: apiMessage(err, 'Could not upload the file.'), variant: 'error' });
                        } finally { this.uploading = false; }
                    },
                    // Each card shows the file itself: a photo, or page 1 of a PDF
                    // (resident portal, 2026-10-07). The API sends files as downloads
                    // only, so the page reads the bytes through a short-lived signed
                    // link and draws them; a DOCX keeps its icon.
                    thumbs: {},
                    viewing: null,
                    viewUrl: null,
                    viewFailed: false,
                    docKind(doc) { return doc ? window.previewKind('file', '.' + (doc.file_type || '')) : null; },
                    async signedUrl(doc) {
                        const res = await $api.get('/citizen/papeles/' + doc.id + '/download');
                        return res.data?.url || res.data?.download_url || null;
                    },
                    async loadThumb(doc, el) {
                        if (!doc.downloadable || this.thumbs[doc.id]) return;
                        const kind = this.docKind(doc);
                        if (!kind) { this.thumbs[doc.id] = { state: 'none' }; return; }
                        this.thumbs[doc.id] = { state: 'loading' };
                        try {
                            const url = await this.signedUrl(doc);
                            if (!url) throw new Error('no link');
                            if (kind === 'pdf') {
                                await window.renderPdfInto(el, url, { maxPages: 1, canvasClass: 'block w-full h-auto bg-white' });
                                this.thumbs[doc.id] = { state: 'pdf' };
                            } else {
                                this.thumbs[doc.id] = { state: 'img', src: await window.imageBlobUrl(url, kind) };
                            }
                        } catch (err) { this.thumbs[doc.id] = { state: 'failed' }; }
                    },
                    // Buksan: the file in a viewer on this page, not a download.
                    async view(doc) {
                        this.viewing = doc;
                        this.viewUrl = null;
                        this.viewFailed = false;
                        $dispatch('papeles-view');
                        try {
                            this.viewUrl = await this.signedUrl(doc);
                            if (!this.viewUrl) this.viewFailed = true;
                        } catch (err) { this.viewFailed = true; }
                    },
                    // I-download: saved as the document's name with its own extension.
                    async download(doc) {
                        if (!doc) return;
                        try {
                            const url = await this.signedUrl(doc);
                            if (!url) throw new Error('no link');
                            await window.downloadFile(url, window.downloadName(doc.name, doc.file_type));
                        } catch (err) {
                            $dispatch('toast', { message: apiMessage(err, lang('Could not download the file.', 'Hindi ma-download ang file.')), variant: 'error' });
                        }
                    },
                }" class="contents">
                    <x-ui.button @click="open = true; loadPapeles()" variant="secondary" icon="folder-open" size="sm" class="shrink-0"><x-ui.t fil="Aking mga Dokumento" en="My Documents" /></x-ui.button>
                    <x-ui.modal title="Aking mga Dokumento" titleEn="My Documents" maxWidth="2xl">
                        <p class="text-sm text-ink-muted mb-4"><x-ui.t fil="Ang mga dokumentong naka-imbak sa iyong Papeles. Maaari mong gamitin muli ang mga ito sa iyong mga kahilingan." en="The documents kept in your Papeles. You can reuse them for your requests." /></p>

                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit">
                                @foreach(['all' => 'All', 'IMG' => 'Images', 'PDF' => 'PDF', 'DOCX' => 'DOCX'] as $key => $label)
                                    <button
                                        @click="fileTab = '{{ $key }}'"
                                        class="px-3 py-1.5 text-xs font-medium rounded-xl transition-colors whitespace-nowrap"
                                        :class="fileTab === '{{ $key }}' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
                                    >{{ $label }}</button>
                                @endforeach
                            </div>
                            <label class="inline-flex items-center gap-1.5 text-xs font-medium text-white bg-brand-600 rounded-lg px-3 py-1.5 hover:bg-brand-700 transition-colors cursor-pointer" :class="uploading && 'opacity-60 pointer-events-none'">
                                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                <span x-text="uploading ? lang('Uploading…', 'Ina-upload…') : lang('Upload a Document', 'Mag-upload ng Dokumento')"></span>
                                <input type="file" class="sr-only" accept="image/*,application/pdf" @change="upload($event.target.files[0]); $event.target.value = ''">
                            </label>
                        </div>

                        <div x-show="papelesLoading" class="grid grid-cols-2 sm:grid-cols-3 gap-3"><x-ui.skeleton class="h-32" /><x-ui.skeleton class="h-32 opacity-80" /></div>
                        <p x-show="!papelesLoading && !papeles.length" class="text-sm text-ink-muted py-6 text-center"><x-ui.t fil="Wala ka pang naka-imbak na dokumento." en="You have no saved documents yet." /></p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" x-show="!papelesLoading">
                            <template x-for="doc in papeles" :key="doc.id">
                                <div x-show="fileTab === 'all' || fileTab === fileKind(doc)" class="rounded-xl border border-brand-600/10 overflow-hidden hover:shadow-card-hover transition-all duration-[220ms] ease-standard group">
                                    <div class="relative aspect-square bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center overflow-hidden">
                                        <i x-show="!['pdf', 'img'].includes(thumbs[doc.id]?.state)" :data-lucide="fileKind(doc) === 'PDF' ? 'file-text' : (fileKind(doc) === 'DOCX' ? 'file-type' : 'image')" class="w-9 h-9 text-ink-muted" :class="thumbs[doc.id]?.state === 'loading' && 'animate-pulse'"></i>
                                        <div x-show="thumbs[doc.id]?.state === 'pdf'" x-effect="open && loadThumb(doc, $el)" class="absolute inset-0"></div>
                                        <img x-show="thumbs[doc.id]?.state === 'img'" x-cloak :src="thumbs[doc.id]?.src || ''" :alt="doc.name" class="absolute inset-0 w-full h-full object-cover">
                                        <span class="absolute top-1.5 left-1.5 text-[9px] font-bold bg-white/90 text-slate-600 rounded px-1.5 py-0.5" x-text="fileKind(doc)"></span>
                                        <span class="absolute top-1.5 right-1.5"><x-ui.badge statusBind="doc.status" class="!px-1.5 !py-0.5 !text-[9px]" /></span>
                                    </div>
                                    <div class="p-2.5 bg-white">
                                        <p class="text-xs font-medium text-slate-700 truncate" x-text="doc.name"></p>
                                        <p class="text-[10px] text-ink-muted mt-0.5" x-text="formatDay(doc.uploaded_at)"></p>
                                        <div x-show="doc.downloadable" class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <button type="button" @click="view(doc)" class="text-[11px] font-medium text-brand-600 hover:underline"><x-ui.t fil="Buksan" en="Open" /></button>
                                            <button type="button" @click="download(doc)" class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 hover:underline"><i data-lucide="download" class="w-3 h-3"></i><x-ui.t fil="I-download" en="Download" /></button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <x-slot:footer>
                            <x-ui.button size="sm" @click="open = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                        </x-slot:footer>
                    </x-ui.modal>

                    {{-- Buksan: the file in a viewer over the list (a photo, or the PDF's pages). --}}
                    <div x-data="{ open: false }" x-on:papeles-view.window="open = true" class="contents">
                        <x-ui.modal title="Tingnan ang Dokumento" titleEn="View Document" maxWidth="lg">
                            <p class="text-sm font-semibold text-ink mb-3 break-words" x-text="viewing?.name"></p>
                            <p x-show="viewFailed" x-cloak class="text-xs text-rose-600 text-center py-8"><x-ui.t fil="Hindi mabuksan ang file. Subukang i-download ito." en="The file could not be opened. Try downloading it." /></p>
                            <div x-data="pdfPreview" x-effect="show(open && docKind(viewing) ? viewUrl : null, '.' + (viewing?.file_type || ''))" x-show="!viewFailed && docKind(viewing)" class="max-h-[65vh] overflow-y-auto rounded-xl border border-slate-200 bg-slate-100 p-2">
                                <p x-show="loading || !viewUrl" class="text-xs text-ink-muted text-center py-8"><x-ui.t fil="Binubuksan…" en="Opening…" /></p>
                                <p x-show="failed" x-cloak class="text-xs text-rose-600 text-center py-8"><x-ui.t fil="Hindi maipakita dito ang file. Subukang i-download ito." en="This file could not be shown here. Try downloading it." /></p>
                                <img x-show="image" x-cloak :src="image" :alt="viewing?.name || ''" class="block w-full h-auto rounded-lg bg-white">
                                <div x-ref="pages"></div>
                            </div>
                            <div x-show="viewing && !docKind(viewing)" class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 text-center py-12 px-4">
                                <p class="text-sm text-slate-600"><x-ui.t fil="Hindi maipakita dito ang ganitong file. Pindutin ang I-download para mabuksan ito sa iyong device." en="This kind of file cannot be shown here. Press Download to open it on your device." /></p>
                            </div>
                            <x-slot:footer>
                                <x-ui.button variant="secondary" size="sm" @click="open = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                                <x-ui.button size="sm" icon="download" @click="download(viewing)"><x-ui.t fil="I-download" en="Download" /></x-ui.button>
                            </x-slot:footer>
                        </x-ui.modal>
                    </div>
                </div>
                <div
                    x-data="{
                        open: false,
                        step: 1,
                        selectedDoc: null,
                        attachments: {},
                        showFilePicker: null,
                        submitted: false,
                        submitting: false,
                        // Municipal docs come pre-built from the server; barangay docs are
                        // expanded here from a single shared template ($barangayDocTemplate)
                        // across every barangay name ($barangayNames) — same final shape/order/
                        // indices as the old fully-server-expanded array (previewType, certTitle,
                        // certSubtitle, validity, office, barangay all match field-for-field), just
                        // built once client-side instead of duplicated 20x over the wire.
                        docs: (() => {
                            const municipal = @js($documentTypes);
                            const template = @js($barangayDocTemplate);
                            const barangayDocs = @js($barangayNames).flatMap(barangayLabel => template.map(bd => ({
                                key: bd.key,
                                name: bd.name,
                                office: 'Barangay Hall (Brgy. ' + barangayLabel + ')',
                                barangay: barangayLabel,
                                fee: bd.fee,
                                days: bd.days,
                                requirements: bd.requirements,
                                process: bd.process,
                                fields: bd.fields,
                                certTitle: bd.title,
                                certSubtitle: bd.subtitle ?? null,
                                validity: bd.validity ?? null,
                                previewType: 'barangay',
                            })));
                            return [...municipal, ...barangayDocs];
                        })(),
                        barangayCatalog: @js($barangayCatalog),
                        catalogTab: 'barangay',
                        // The backend's own catalog (GET /services?type=dokyu): a
                        // document it does not list cannot be filed, and a
                        // policy-blocked one has a fee the LGU has not ruled on.
                        serviceMeta: {},
                        catalogLoaded: false,
                        async loadCatalog() {
                            try {
                                const res = await $api.get('/services', { params: { type: 'dokyu' } });
                                const meta = {};
                                (res.data || []).forEach((sv) => { meta[sv.key] = sv; });
                                this.serviceMeta = meta;
                            } catch { this.serviceMeta = {}; } finally { this.catalogLoaded = true; }
                        },
                        canFile(doc) { return !this.catalogLoaded || !Object.keys(this.serviceMeta).length || !!this.serviceMeta[doc?.key]; },
                        // A fee assessed on a declared amount (capital, income,
                        // gross receipts, project cost) needs that amount at
                        // filing: the server refuses the request without it
                        // (B-053), and this form never asked (Esperanza bugs QA,
                        // 2026-10-06). The names come from the service's own
                        // fee.required_inputs, as in the phone app.
                        feeInputs: {},
                        feeInputLabels: {
                            declared_capital: ['Ipinahayag na kapital (₱)', 'Declared capital (₱)'],
                            prior_year_gross_receipts: ['Kabuuang benta noong nakaraang taon (₱)', 'Last year\'s gross receipts (₱)'],
                            income: ['Taunang kita (₱)', 'Annual income (₱)'],
                            project_cost: ['Halaga ng proyekto (₱)', 'Project cost (₱)'],
                        },
                        get feeInputNames() { return this.serviceMeta[this.docs[this.selectedDoc]?.key]?.fee?.required_inputs || []; },
                        // A fee amount the details step already asked for is not asked
                        // again: the Business Permit took the capital twice, and the two
                        // answers could disagree (live test 2026-10-07, UX-10).
                        feeInputSources: { declared_capital: 'capital_investment' },
                        feeInputSource(name) {
                            const key = this.feeInputSources[name];
                            return key && (this.docs[this.selectedDoc]?.fields || []).some((f) => f.key === key) ? key : null;
                        },
                        get feeInputsAsked() { return this.feeInputNames.filter((n) => !this.feeInputSource(n)); },
                        feeInputValue(name) {
                            const key = this.feeInputSource(name);
                            return this.parseAmount(key ? this.formData[key] : this.feeInputs[name]);
                        },
                        // A number field holds a number: the server kept 'QA TEST value'
                        // as a capital (live test 2026-10-07, UX-10).
                        numberProblem() {
                            const en = this.$store.lang.current === 'en';
                            for (const f of (this.docs[this.selectedDoc]?.fields || []).filter((f) => f.type === 'number')) {
                                const raw = this.formData[f.key];
                                if (String(raw ?? '').trim() !== '' && this.parseAmount(raw) === null) return f.label + (en ? ': numbers only.' : ': numero lamang.');
                            }
                            for (const name of this.feeInputNames) {
                                const key = this.feeInputSource(name);
                                if (key && this.parseAmount(this.formData[key]) === null) {
                                    const f = (this.docs[this.selectedDoc]?.fields || []).find((x) => x.key === key);
                                    return (f?.label || key) + (en ? ': enter the amount; the fee is computed from it.' : ': ilagay ang halaga; dito kinukuwenta ang bayarin.');
                                }
                            }
                            return '';
                        },
                        feeInputLabel(name) {
                            const l = this.feeInputLabels[name] || [name.replace(/_/g, ' '), name.replace(/_/g, ' ')];
                            return this.$store.lang.current === 'en' ? l[1] : l[0];
                        },
                        parseAmount(raw) {
                            const cleaned = String(raw ?? '').replace(/[₱,\s]/g, '');
                            if (cleaned === '' || !/^\d+(\.\d+)?$/.test(cleaned)) return null;
                            return Number(cleaned);
                        },
                        selectedCategory: null,
                        categoryIcons: {
                            'Civil Registry': 'file-badge', 'Tax & Property': 'receipt', 'Business & Permits': 'briefcase',
                            'Social Services': 'hand-heart', 'Senior Citizen / PWD': 'users', 'Other Municipal Services': 'layout-grid',
                        },
                        search: '',
                        get myBarangay() { return this.$store.citizenSession.account?.barangay ?? null; },
                        get myBarangayInfo() { return this.barangayCatalog[this.myBarangay] ?? null; },
                        get myBarangayDocs() { return this.docs.map((d, i) => ({ ...d, i })).filter(d => d.barangay === this.myBarangay); },
                        get selectedDocBarangayInfo() { return this.barangayCatalog[this.docs[this.selectedDoc]?.barangay] ?? null; },
                        get municipalDocs() { return this.docs.map((d, i) => ({ ...d, i })).filter(d => !d.barangay); },
                        get filteredMyBarangayDocs() { return this.myBarangayDocs.filter(d => d.name.toLowerCase().includes(this.search.toLowerCase())); },
                        get filteredMunicipalDocs() { return this.municipalDocs.filter(d => d.name.toLowerCase().includes(this.search.toLowerCase())); },
                        get municipalCategories() {
                            const counts = {};
                            this.municipalDocs.forEach(d => { counts[d.category] = (counts[d.category] || 0) + 1; });
                            return Object.keys(counts).map(name => ({ name, count: counts[name], icon: this.categoryIcons[name] || 'folder' }));
                        },
                        get municipalDocsInCategory() {
                            return this.filteredMunicipalDocs.filter(d => d.category === this.selectedCategory);
                        },
                        chooseCategory(cat) { this.selectedCategory = cat; },
                        backToCategories() { this.selectedCategory = null; this.search = ''; },
                        formData: {},
                        otherPurposeText: {},
                        selectDoc(i) {
                            this.selectedDoc = i;
                            this.attachments = {};
                            this.formData = {};
                            this.otherPurposeText = {};
                            (this.docs[i]?.fields || []).forEach(f => {
                                if (f.type === 'checkbox-group') { this.formData[f.key] = []; }
                                else { this.formData[f.key] = f.default || ''; }
                            });
                            Object.entries(window.profileDefaults(this.docs[i]?.fields, this.$store.citizenSession.account)).forEach(([k, v]) => {
                                if (this.formData[k] === '') this.formData[k] = v;
                            });
                            this.stepError = '';
                            this.stepWarning = '';
                            this.attachWarned = false;
                        },
                        // Continue checks the step it leaves. Step 2: every
                        // field marked * is filled; a request with no
                        // requirement attached is allowed (they can be added
                        // from Track Request) but only after saying so once.
                        stepError: '',
                        stepWarning: '',
                        attachWarned: false,
                        // The applicant's mobile number and any number field: '' when every one is fine.
                        phoneProblem() {
                            const lang = this.$store.lang.current;
                            const own = window.phMobile.error(this.applicant.mobile, false, lang);
                            if (own) return own;
                            for (const f of (this.docs[this.selectedDoc]?.fields || []).filter((f) => f.type === 'tel')) {
                                const err = window.phMobile.error(this.formData[f.key], !!f.required, lang);
                                if (err) return f.label + ': ' + err;
                            }
                            return '';
                        },
                        missingRequired() {
                            return (this.docs[this.selectedDoc]?.fields || []).filter((f) => {
                                if (!f.required) return false;
                                const v = this.formData[f.key];
                                return Array.isArray(v) ? v.length === 0 : String(v ?? '').trim() === '';
                            }).map((f) => f.label);
                        },
                        advance() {
                            const en = this.$store.lang.current === 'en';
                            this.stepError = '';
                            this.stepWarning = '';
                            if (this.step === 2) {
                                const missing = this.missingRequired();
                                if (missing.length) {
                                    this.stepError = (en ? 'Please fill in: ' : 'Pakipunan ang: ') + missing.join(', ');
                                    return;
                                }
                                // Every number field takes a Philippine mobile number (Esperanza QA, 2026-10-07).
                                const badPhone = this.phoneProblem();
                                if (badPhone) {
                                    this.stepError = badPhone;
                                    return;
                                }
                                const badNumber = this.numberProblem();
                                if (badNumber) {
                                    this.stepError = badNumber;
                                    return;
                                }
                                // Names only what the resident owes: a step the office
                                // does, or a document marked if applicable, is not missing.
                                const owed = (this.docs[this.selectedDoc]?.requirements || []).filter((q, i) => reqKind(q) === 'required' && !this.attachments[i]);
                                if (owed.length && !this.attachWarned) {
                                    this.attachWarned = true;
                                    this.stepWarning = en
                                        ? 'Still to attach, and the office needs them to process the request: ' + owed.join(', ') + '. Attach them now, or press Continue to add them later from Track Request.'
                                        : 'Hindi pa naikakabit, at kailangan ito ng opisina: ' + owed.join(', ') + '. Ikabit na ngayon, o pindutin ang Magpatuloy para idagdag mamaya sa Subaybayan.';
                                    return;
                                }
                            }
                            if (this.step === 3) {
                                const bad = this.feeInputsAsked.filter((n) => this.parseAmount(this.feeInputs[n]) === null);
                                if (bad.length) {
                                    this.stepError = (en ? 'Enter the amount the fee is computed from: ' : 'Ilagay ang halagang pagbabatayan ng bayarin: ')
                                        + bad.map((n) => this.feeInputLabel(n)).join(', ');
                                    return;
                                }
                            }
                            this.step++;
                        },
                        toggleChecklist(fieldKey, option) {
                            const list = this.formData[fieldKey] || [];
                            const i = list.indexOf(option);
                            if (i === -1) { list.push(option); } else { list.splice(i, 1); }
                            this.formData[fieldKey] = list;
                        },
                        computeAge(dateStr) {
                            if (!dateStr) return null;
                            const dob = new Date(dateStr);
                            if (isNaN(dob)) return null;
                            const today = new Date();
                            let age = today.getFullYear() - dob.getFullYear();
                            const monthDiff = today.getMonth() - dob.getMonth();
                            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) age--;
                            return age;
                        },
                        applicant: { firstName: '', lastName: '', address: '', mobile: '', email: '' },
                        seedApplicant() {
                            const a = this.$store.citizenSession.account;
                            this.applicant = a ? {
                                firstName: a.firstName, lastName: a.lastName, address: a.address, mobile: a.mobile, email: a.email,
                            } : { firstName: '', lastName: '', address: '', mobile: '', email: '' };
                        },
                        // No online payment exists (open LGU question Q3): the fee is
                        // assessed when the request is filed and paid at the office,
                        // which issues the official receipt. The GCash and Maya
                        // 'numbers' this listed were placeholders a resident could
                        // have sent real money to.
                        stepLabels: {
                            fil: ['Pumili ng Dokumento', 'Detalye ng Aplikasyon', 'Bayad', 'I-review at I-submit'],
                            en: ['Choose Document', 'Application Details', 'Payment', 'Review & Submit'],
                        },
                        stepIcons: ['file-text', 'user-round', 'credit-card', 'clipboard-check'],
                        reset() { this.step = 1; this.selectedDoc = null; this.attachments = {}; this.feeInputs = {}; this.showFilePicker = null; this.submitted = false; this.submittedRef = null; this.formData = {}; this.otherPurposeText = {}; this.search = ''; this.stepError = ''; this.stepWarning = ''; this.attachWarned = false; },
                        submittedRef: null,
                        // One requirement's attachment: a new upload, or a document
                        // the resident already keeps in Papeles.
                        attachFile(i, file) { if (file) this.attachments[i] = { kind: 'file', file, name: file.name }; this.showFilePicker = null; },
                        attachPapeles(i, doc) { this.attachments[i] = { kind: 'papeles', id: doc.id, name: doc.name }; this.showFilePicker = null; },
                        // POST /citizen/requests, then each attachment onto its
                        // requirement (POST .../requirements/{key}/attach). The
                        // server assigns the ref, assesses the fee and notifies
                        // the office; an attachment that fails is reported and can
                        // be added from Track Request.
                        async submitRequest() {
                            const doc = this.docs[this.selectedDoc];
                            const formData = JSON.parse(JSON.stringify(this.formData || {}));
                            Object.entries(this.otherPurposeText || {}).forEach(([k, v]) => {
                                if (v && Array.isArray(formData[k])) formData[k] = formData[k].map((o) => /^others/i.test(o) ? 'Others: ' + v : o);
                            });
                            // The applicant block says 'edit if needed', so what the
                            // resident confirmed or corrected goes with the request.
                            Object.assign(formData, {
                                applicant_first_name: this.applicant.firstName || '',
                                applicant_last_name: this.applicant.lastName || '',
                                applicant_address: this.applicant.address || '',
                                applicant_mobile: this.applicant.mobile || '',
                                applicant_email: this.applicant.email || '',
                            });
                            const feeInputs = Object.fromEntries(this.feeInputNames.map((n) => [n, this.feeInputValue(n)]));
                            const res = await $api.post('/citizen/requests', { service_key: doc.key, form_data: formData, ...(this.feeInputNames.length ? { fee_inputs: feeInputs } : {}) });
                            const created = res.data || {};
                            const serverReqs = created.requirements || [];
                            const failed = [];
                            const names = doc.requirements || [];
                            for (const [i, att] of Object.entries(this.attachments)) {
                                if (!att) continue;
                                const label = names[i];
                                const target = serverReqs.find((q) => q.label === label) || serverReqs[Number(i)];
                                if (!target) { failed.push(label); continue; }
                                try {
                                    const url = '/citizen/requests/' + encodeURIComponent(created.ref) + '/requirements/' + encodeURIComponent(target.key) + '/attach';
                                    if (att.kind === 'papeles') {
                                        await $api.post(url, { papeles_id: att.id });
                                    } else {
                                        const form = new FormData();
                                        form.append('file', att.file);
                                        await $api.post(url, form);
                                    }
                                } catch { failed.push(label); }
                            }
                            this.submittedRef = created.ref;
                            await this.loadRequests();
                            return { ref: created.ref, failed };
                        },
                    }"
                    x-init="catalogTab = myBarangayInfo ? 'barangay' : 'municipal'; seedApplicant(); loadCatalog(); $watch('$store.citizenSession.account', () => seedApplicant())"
                    x-effect="!open && reset()"
                    class="contents"
                >
                    <x-ui.button @click="open = true" ::disabled="$store.citizenSession.account && !$store.citizenSession.account.fullServiceAccess" icon="file-plus" size="sm" class="w-full sm:w-auto justify-center"><x-ui.t fil="Bagong Kahilingan sa Dokyu" en="New Dokyu Request" /></x-ui.button>
                    <x-ui.modal title="Bagong Kahilingan sa Dokyu" titleEn="New Dokyu Request" maxWidth="3xl" :fullscreen="true">
                        <!-- Success state -->
                        <div x-show="submitted" x-cloak x-transition.opacity class="flex flex-col items-center justify-center text-center py-10">
                            <div class="relative flex items-center justify-center mb-4 w-16 h-16">
                                <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse"></span>
                                <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse" style="animation-delay: 0.35s;"></span>
                                <span class="relative w-14 h-14 rounded-full bg-emerald-500 text-white flex items-center justify-center animate-check-pop">
                                    <i data-lucide="check" class="w-7 h-7"></i>
                                </span>
                            </div>
                            <p class="text-sm font-semibold text-ink">
                                <x-ui.t fil="Naisumite ang Kahilingan!" en="Request Submitted!" />
                                <span class="inline-block animate-check-pop" style="animation-delay: 0.3s;">🙂</span>
                            </p>
                            <p class="text-xs text-ink-muted mt-1 max-w-[220px]"><x-ui.t fil="Aabisuhan ka namin kapag na-review na ang iyong kahilingan sa Dokyu." en="We'll notify you once your Dokyu request is reviewed." /></p>
                        </div>

                        <div x-show="!submitted">
                        {{-- Step indicator: equal columns, each circle centred in its own, the label
                             below, and each line drawn from one circle's centre to the next. It was a
                             row sized by its labels: the lines came out of different lengths, the last
                             step left a gap, and a one-line label ("Bayad") dropped its circle below
                             the others (2026-10-07). --}}
                        <div class="grid grid-cols-4 mb-8">
                            <template x-for="(label, i) in ($store.lang.current === 'en' ? stepLabels.en : stepLabels.fil)" :key="i">
                                <div class="relative flex flex-col items-center text-center px-1">
                                    <div x-show="i < 3" class="absolute top-4 left-[calc(50%_+_26px)] right-[calc(-50%_+_26px)] h-1 rounded-full transition-colors duration-[300ms] ease-standard" :class="step > i + 1 ? 'bg-emerald-400' : 'bg-brand-600/10'"></div>
                                    <span
                                        class="relative w-9 h-9 rounded-full flex items-center justify-center shrink-0 transition-all duration-[300ms] ease-standard"
                                        :class="step > i + 1 ? 'bg-emerald-500 text-white shadow-sm' : (step === i + 1 ? 'clay-blue ring-4 ring-brand-100' : 'bg-white text-ink-faint ring-1 ring-brand-600/15')"
                                    >
                                        <i data-lucide="check" class="w-4 h-4" x-show="step > i + 1" x-cloak></i>
                                        <i :data-lucide="stepIcons[i]" class="w-4 h-4" x-show="step <= i + 1"></i>
                                    </span>
                                    <span class="mt-2 text-[11px] max-w-[110px] leading-tight transition-colors duration-[300ms] ease-standard" :class="step === i + 1 ? 'text-ink font-bold' : 'text-ink-muted'" x-text="label"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Step 1: choose document -->
                        <div x-show="step === 1" class="space-y-4">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                                <div class="flex items-center gap-1 surface-pressed rounded-2xl p-1 w-fit shrink-0" role="tablist">
                                    <button type="button" role="tab" :aria-selected="catalogTab === 'barangay'" @click="catalogTab = 'barangay'" class="px-4 py-2 text-[13px] rounded-xl transition-all duration-200 flex items-center gap-1.5" :class="catalogTab === 'barangay' ? 'bg-white/90 text-brand-600 font-bold shadow-[3px_4px_8px_rgb(29_71_214/.16),-2px_-2px_5px_rgb(255_255_255/.9),inset_0_1px_0_#fff]' : 'font-semibold text-ink-muted hover:text-ink'">
                                        <i data-lucide="map-pinned" class="w-4 h-4"></i><x-ui.t fil="Aking Barangay" en="My Barangay" />
                                    </button>
                                    <button type="button" role="tab" :aria-selected="catalogTab === 'municipal'" @click="catalogTab = 'municipal'" class="px-4 py-2 text-[13px] rounded-xl transition-all duration-200 flex items-center gap-1.5" :class="catalogTab === 'municipal' ? 'bg-white/90 text-brand-600 font-bold shadow-[3px_4px_8px_rgb(29_71_214/.16),-2px_-2px_5px_rgb(255_255_255/.9),inset_0_1px_0_#fff]' : 'font-semibold text-ink-muted hover:text-ink'">
                                        <i data-lucide="landmark" class="w-4 h-4"></i><x-ui.t fil="Munisipyo" en="Municipal" />
                                    </button>
                                </div>
                                <div class="flex-1">
                                <div class="relative">
                                    <i data-lucide="search" class="w-4 h-4 text-ink-faint absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                    <input aria-label="Search"
                                        type="text" x-model="search"
                                        x-bind:placeholder="$store.lang.current === 'en' ? 'Search documents...' : 'Maghanap ng dokumento...'"
                                        class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed py-2.5 pl-11 pr-4 text-sm text-ink placeholder:text-ink-faint focus:bg-white focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                                    >
                                </div>
                                </div>
                            </div>

                            <!-- My Barangay -->
                            <div x-show="catalogTab === 'barangay'">
                                <template x-if="myBarangayInfo">
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-3.5 rounded-2xl bg-white border border-brand-600/10 shadow-[0_1px_2px_rgb(11_23_48/.04),0_4px_12px_rgb(29_71_214/.05)] px-4 py-3.5">
                                            <div class="relative w-12 h-12 shrink-0">
                                                <div class="absolute inset-0 rounded-full bg-white border-2 border-gold-400 flex items-center justify-center text-ink-faint"><i data-lucide="landmark" class="w-5 h-5"></i></div>
                                                <img
                                                    :src="myBarangayInfo.seal ? '{{ rtrim(asset(''), '/') }}/' + myBarangayInfo.seal : null"
                                                    x-show="myBarangayInfo.seal" x-on:error="$el.style.display='none'"
                                                    :alt="myBarangayInfo.name ? 'Seal of Barangay ' + myBarangayInfo.name : 'Barangay seal'"
                                                    class="relative w-12 h-12 rounded-full object-cover border-2 border-gold-400"
                                                >
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[15px] font-bold text-ink"><x-ui.t fil="Barangay" en="Barangay" /> <span x-text="myBarangayInfo.label"></span></p>
                                                <p class="text-[12.5px] text-ink-muted"><x-ui.t fil="Punong Barangay:" en="Punong Barangay:" /> <span x-text="myBarangayInfo.officials.punong_barangay"></span></p>
                                            </div>
                                            <p class="hidden sm:block text-[12.5px] text-ink-muted max-w-[260px] text-right"><x-ui.t fil="Mga dokumentong maaari mong hilingin mula sa iyong barangay hall." en="Documents you can request from your own barangay hall." /></p>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-3">
                                            <template x-for="doc in filteredMyBarangayDocs" :key="doc.i">
                                                <label class="choice-card" :class="selectedDoc === doc.i && 'is-selected'">
                                                    <input type="radio" name="doc_type" class="sr-only" :checked="selectedDoc === doc.i" @change="selectDoc(doc.i)">
                                                    <span class="choice-icon">
                                                        <i data-lucide="file-text" class="w-5 h-5" x-show="selectedDoc !== doc.i"></i>
                                                        <i data-lucide="check" class="w-5 h-5" x-show="selectedDoc === doc.i" x-cloak></i>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-[14.5px] font-semibold text-ink leading-snug" x-text="doc.name"></span>
                                                        <span class="block text-[12.5px] text-ink-muted mt-0.5 truncate" x-text="doc.office + ' · ' + doc.days"></span>
                                                    </span>
                                                    <span class="shrink-0 self-start text-[11.5px] font-bold rounded-full px-2.5 py-1" :class="doc.fee === 'Free' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70' : 'bg-brand-50 text-brand-700 ring-1 ring-brand-200/70'" x-text="doc.fee"></span>
                                                </label>
                                            </template>
                                            <div class="md:col-span-2 2xl:col-span-3 flex flex-col items-center text-center py-10 rounded-2xl bg-white border border-dashed border-brand-600/15" x-show="filteredMyBarangayDocs.length === 0">
                                                <span class="w-11 h-11 rounded-full bg-brand-50 text-brand-500 flex items-center justify-center mb-2.5"><i data-lucide="search-x" class="w-5 h-5"></i></span>
                                                <p class="text-sm font-semibold text-ink"><x-ui.t fil="Walang dokumentong tumutugma." en="No documents match your search." /></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!myBarangayInfo">
                                    <div class="flex flex-col items-center text-center py-12 px-4 rounded-2xl bg-white border border-dashed border-brand-600/15">
                                        <span class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-500 flex items-center justify-center mb-4"><i data-lucide="map-pinned" class="w-7 h-7"></i></span>
                                        <p class="text-sm font-semibold text-ink"><x-ui.t fil="Hindi pa available online ang mga dokumento ng iyong barangay" en="Your barangay's documents aren't available online yet" /></p>
                                        <p class="text-[12.5px] text-ink-muted mt-1 max-w-xs"><x-ui.t fil="Bisitahin ang iyong Barangay Hall, o pumili ng dokumentong munisipyo sa halip." en="Please visit your Barangay Hall directly, or choose a municipal document instead." /></p>
                                    </div>
                                </template>
                            </div>

                            <!-- Municipal -->
                            <div x-show="catalogTab === 'municipal'" x-cloak>
                                <!-- Category grid (shown first, or when searching) -->
                                <template x-if="!selectedCategory || search">
                                    <div>
                                        <h4 class="text-base font-bold text-ink mb-3" x-show="!search"><x-ui.t fil="Piliin ang uri ng dokumentong kailangan mo." en="Choose the kind of document you need." /></h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-3" x-show="!search">
                                            <template x-for="cat in municipalCategories" :key="cat.name">
                                                <button type="button" @click="chooseCategory(cat.name)" class="choice-card group">
                                                    <span class="choice-icon"><i :data-lucide="cat.icon" class="w-5 h-5"></i></span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-[15px] font-semibold text-ink truncate" x-text="cat.name"></span>
                                                        <span class="block text-[12.5px] text-ink-muted mt-0.5" x-text="cat.count + (cat.count === 1 ? ' document' : ' documents')"></span>
                                                    </span>
                                                    <span class="w-8 h-8 rounded-full bg-brand-50 text-brand-600 grid place-items-center shrink-0 transition-transform duration-200 group-hover:translate-x-0.5"><i data-lucide="chevron-right" class="w-4 h-4"></i></span>
                                                </button>
                                            </template>
                                        </div>

                                        <!-- Search results (flat, across categories) -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-3" x-show="search">
                                            <template x-for="doc in filteredMunicipalDocs" :key="doc.i">
                                                <label class="choice-card" :class="selectedDoc === doc.i && 'is-selected'">
                                                    <input type="radio" name="doc_type" class="sr-only" :checked="selectedDoc === doc.i" @change="selectDoc(doc.i)">
                                                    <span class="choice-icon">
                                                        <i data-lucide="landmark" class="w-5 h-5" x-show="selectedDoc !== doc.i"></i>
                                                        <i data-lucide="check" class="w-5 h-5" x-show="selectedDoc === doc.i" x-cloak></i>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-[14.5px] font-semibold text-ink leading-snug" x-text="doc.name"></span>
                                                        <span class="block text-[12.5px] text-ink-muted mt-0.5 truncate" x-text="doc.category + ' · ' + doc.office_short"></span>
                                                    </span>
                                                    <span class="shrink-0 self-start text-[11.5px] font-bold rounded-full px-2.5 py-1" :class="doc.fee === 'Free' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70' : 'bg-brand-50 text-brand-700 ring-1 ring-brand-200/70'" x-text="doc.fee"></span>
                                                </label>
                                            </template>
                                            <div class="md:col-span-2 2xl:col-span-3 flex flex-col items-center text-center py-10 rounded-2xl bg-white border border-dashed border-brand-600/15" x-show="filteredMunicipalDocs.length === 0">
                                                <span class="w-11 h-11 rounded-full bg-brand-50 text-brand-500 flex items-center justify-center mb-2.5"><i data-lucide="search-x" class="w-5 h-5"></i></span>
                                                <p class="text-sm font-semibold text-ink"><x-ui.t fil="Walang dokumentong tumutugma." en="No documents match your search." /></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Documents within the chosen category -->
                                <template x-if="selectedCategory && !search">
                                    <div>
                                        <button type="button" @click="backToCategories()" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-brand-600 hover:text-brand-700 mb-3 transition-colors">
                                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                                            <x-ui.t fil="Bumalik sa mga kategorya" en="Back to categories" />
                                        </button>
                                        <p class="text-[15px] font-bold text-ink mb-3" x-text="selectedCategory"></p>
                                        <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-3">
                                            <template x-for="doc in municipalDocsInCategory" :key="doc.i">
                                                <label class="choice-card" :class="selectedDoc === doc.i && 'is-selected'">
                                                    <input type="radio" name="doc_type" class="sr-only" :checked="selectedDoc === doc.i" @change="selectDoc(doc.i)">
                                                    <span class="choice-icon">
                                                        <i data-lucide="landmark" class="w-5 h-5" x-show="selectedDoc !== doc.i"></i>
                                                        <i data-lucide="check" class="w-5 h-5" x-show="selectedDoc === doc.i" x-cloak></i>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-[14.5px] font-semibold text-ink leading-snug" x-text="doc.name"></span>
                                                        <span class="block text-[12.5px] text-ink-muted mt-0.5 truncate" x-text="doc.office_short + ' · ' + doc.days"></span>
                                                    </span>
                                                    <span class="shrink-0 self-start text-[11.5px] font-bold rounded-full px-2.5 py-1" :class="doc.fee === 'Free' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70' : 'bg-brand-50 text-brand-700 ring-1 ring-brand-200/70'" x-text="doc.fee"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Step 2: application details + requirements -->
                        <div x-show="step === 2" x-cloak>
                            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start mb-6">
                                <div class="lg:col-span-3">
                                    <p class="text-sm font-semibold text-ink mb-1"><x-ui.t fil="Impormasyon ng Aplikante" en="Applicant Information" /></p>
                                    <p class="text-xs text-ink-muted mb-3"><x-ui.t fil="Naka-prefill mula sa iyong resident profile — i-edit kung kailangan." en="Pre-filled from your resident profile — edit if needed." /></p>
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <x-ui.input name="req_first_name" label="First Name" x-model="applicant.firstName" />
                                        <x-ui.input name="req_last_name" label="Last Name" x-model="applicant.lastName" />
                                    </div>
                                    <div class="mb-3">
                                        <x-ui.input name="req_address" label="Complete Address" icon="map-pin" x-model="applicant.address" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <x-ui.input name="req_mobile" type="tel" inputmode="numeric" maxlength="11" label="Mobile Number" icon="phone" placeholder="09XXXXXXXXX" x-model="applicant.mobile" @input="applicant.mobile = window.phMobile.clean($event.target.value)" />
                                        <x-ui.input name="req_email" type="email" label="Email Address" icon="mail" x-model="applicant.email" />
                                    </div>
                                </div>

                                <!-- Live request summary -->
                                <div class="lg:col-span-2">
                                    <div class="lg:sticky lg:top-0 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50/60 to-white p-4">
                                        <p class="text-xs font-semibold text-ink uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="receipt-text" class="w-3.5 h-3.5 text-brand-500"></i><x-ui.t fil="Buod ng Kahilingan" en="Request Summary" /></p>
                                        <div class="flex items-center gap-2.5 mb-3">
                                            <span class="w-10 h-10 rounded-lg clay-blue flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4.5 h-4.5"></i></span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-ink leading-snug" x-text="docs[selectedDoc]?.name"></p>
                                                <p class="text-xs text-ink-muted truncate" x-text="docs[selectedDoc]?.office"></p>
                                            </div>
                                        </div>
                                        <div class="space-y-1.5 text-xs border-t border-brand-100/70 pt-3">
                                            <div class="flex items-center justify-between"><span class="text-ink-muted"><x-ui.t fil="Bayarin" en="Fee" /></span><span class="font-semibold text-ink" x-text="docs[selectedDoc]?.fee"></span></div>
                                            <div class="flex items-center justify-between"><span class="text-ink-muted"><x-ui.t fil="Oras ng Pagproseso" en="Processing Time" /></span><span class="font-medium text-slate-600" x-text="docs[selectedDoc]?.days"></span></div>
                                            <div class="flex items-center justify-between"><span class="text-ink-muted"><x-ui.t fil="Requirements" en="Requirements" /></span><span class="font-medium text-slate-600" x-text="Object.keys(attachments).filter(k => attachments[k]).length + ' / ' + (docs[selectedDoc]?.requirements || []).filter(q => reqKind(q) !== 'office').length + ' attached'"></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Full-width: generic purpose OR structured fields (barangay documents) — sits below the two-column grid so it isn't squeezed beside the sticky Request Summary -->
                            <div class="mb-6" x-show="!(docs[selectedDoc]?.fields?.length)">
                                <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Layunin" en="Purpose" /></label>
                                <textarea x-model="formData.purpose" aria-label="e.g. Employment requirement" rows="2" placeholder="e.g. Employment requirement" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                            </div>

                            <div class="mb-6" x-show="docs[selectedDoc]?.fields?.length" x-cloak>
                                <p class="text-sm font-semibold text-ink mb-1" x-text="docs[selectedDoc]?.certTitle || ''"></p>
                                <p class="text-xs text-ink-muted mb-3" x-show="docs[selectedDoc]?.certSubtitle" x-text="docs[selectedDoc]?.certSubtitle"></p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                    <template x-for="f in (docs[selectedDoc]?.fields || [])" :key="f.key">
                                        <div :class="f.type === 'checkbox-group' ? 'sm:col-span-2 lg:col-span-3' : ''">
                                            <template x-if="f.type === 'derived_age'">
                                                <div x-init="$watch(() => formData[f.derived_from], v => formData[f.key] = computeAge(v)); formData[f.key] = computeAge(formData[f.derived_from])">
                                                    <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label"></label>
                                                    <div class="w-full rounded-xl border border-slate-200 surface-pressed py-2.5 px-4 text-sm text-ink-muted" x-text="(formData[f.key] ?? null) !== null ? formData[f.key] + ' years old' : 'Set birthdate first'"></div>
                                                </div>
                                            </template>
                                            <template x-if="f.type === 'text' || f.type === 'number' || f.type === 'date' || f.type === 'tel'">
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                    <input :aria-label="f.label"
                                                        :type="f.type" x-model="formData[f.key]"
                                                    :inputmode="f.type === 'tel' ? 'numeric' : null" :maxlength="f.type === 'tel' ? 11 : null" :placeholder="f.type === 'tel' ? '09XXXXXXXXX' : null"
                                                    @input="if (f.type === 'tel') formData[f.key] = window.phMobile.clean($event.target.value)"
                                                        class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                                                    >
                                                </div>
                                            </template>
                                            <template x-if="f.type === 'select'">
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                    <select :aria-label="f.label" x-model="formData[f.key]" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                                        <option value="">—</option>
                                                        <template x-for="opt in f.options" :key="opt"><option :value="opt" x-text="opt"></option></template>
                                                    </select>
                                                </div>
                                            </template>
                                            <template x-if="f.type === 'checkbox-group'">
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-700 mb-2" x-text="f.label"></label>
                                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                        <template x-for="opt in f.options" :key="opt">
                                                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-600 cursor-pointer hover:border-slate-300 transition-colors" :class="(formData[f.key] || []).includes(opt) && 'border-brand-300 bg-brand-50/60 text-brand-700'">
                                                                <input type="checkbox" class="rounded text-brand-600 focus:ring-brand-200" :checked="(formData[f.key] || []).includes(opt)" @change="toggleChecklist(f.key, opt)">
                                                                <span x-text="opt"></span>
                                                            </label>
                                                        </template>
                                                    </div>
                                                    <div class="mt-2" x-show="f.allowOther">
                                                        <input aria-label="Others (please specify)" type="text" x-model="otherPurposeText[f.key]" placeholder="Others (please specify)" class="w-full rounded-2xl border-0 surface-pressed py-2 px-3.5 text-xs text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <p class="text-[11px] text-ink-muted mt-3" x-show="docs[selectedDoc]?.validity" x-text="docs[selectedDoc]?.validity"></p>
                            </div>

                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-ink"><x-ui.t fil="Mga Requirement" en="Requirements" /></p>
                                <p class="text-[11px] font-medium text-ink-muted" x-text="Object.keys(attachments).filter(k => attachments[k]).length + ' / ' + (docs[selectedDoc]?.requirements || []).filter(q => reqKind(q) !== 'office').length"></p>
                            </div>
                            <div class="h-1 rounded-full bg-slate-100 overflow-hidden mb-3">
                                <div class="h-full bg-emerald-400 rounded-full transition-all duration-[600ms] ease-standard" :style="'width:' + (((docs[selectedDoc]?.requirements || []).filter(q => reqKind(q) !== 'office').length ? Math.min(1, Object.keys(attachments).filter(k => attachments[k]).length / (docs[selectedDoc]?.requirements || []).filter(q => reqKind(q) !== 'office').length) : 0) * 100) + '%'"></div>
                            </div>
                            <p class="text-xs text-ink-muted mb-3"><x-ui.t fil="Mag-upload ng file (larawan o PDF), o gamitin ang isang dokumentong naka-imbak na sa iyong Papeles. Ipapadala ang mga ito kasama ng iyong kahilingan." en="Upload a file (photo or PDF), or reuse a document already saved in your Papeles. They are sent with your request." /></p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <template x-for="(req, i) in docs[selectedDoc]?.requirements || []" :key="i">
                                    <div class="rounded-xl border p-3 transition-colors duration-[220ms] ease-standard" :class="attachments[i] ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200'">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-xs text-slate-600 flex-1"><span x-text="req"></span> <span class="ml-1 inline-block rounded px-1.5 py-px text-[10px] font-semibold align-middle" :class="reqKindClass(req)" x-text="reqKindLabel(req)"></span></p>
                                            <button
                                                type="button" x-show="!attachments[i] && reqKind(req) !== 'office'"
                                                @click="showFilePicker = (showFilePicker === i ? null : i)"
                                                class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap"
                                            ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                                            <div class="flex items-center gap-1.5 shrink-0" x-show="attachments[i]">
                                                <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap">
                                                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i><span class="truncate max-w-[140px]" x-text="attachments[i]?.name"></span>
                                                </span>
                                                <button aria-label="Close" type="button" @click="attachments[i] = null" class="text-ink-faint hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                            </div>
                                        </div>
                                        <div x-show="showFilePicker === i" x-cloak x-transition class="mt-2.5 pt-2.5 border-t border-brand-600/10 space-y-2">
                                            <label class="inline-flex items-center gap-1.5 text-[11px] font-medium text-white bg-brand-600 rounded-lg px-2.5 py-1.5 hover:bg-brand-700 transition-colors cursor-pointer">
                                                <i data-lucide="upload" class="w-3.5 h-3.5"></i><x-ui.t fil="Mag-upload ng file" en="Upload a file" />
                                                <input type="file" class="sr-only" accept="image/*,application/pdf" @change="attachFile(i, $event.target.files[0]); $event.target.value = ''">
                                            </label>
                                            <div x-show="papeles.length" class="space-y-1">
                                                <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted"><x-ui.t fil="O gamitin mula sa Papeles" en="Or reuse from Papeles" /></p>
                                                <template x-for="doc in papeles" :key="doc.id">
                                                    <button type="button" @click="attachPapeles(i, doc)" class="w-full text-left text-[11px] text-slate-600 hover:text-brand-700 hover:bg-brand-50 rounded-xl px-2 py-1 truncate" x-text="doc.name"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Step 3: payment -->
                        <div x-show="step === 3" x-cloak>
                            <template x-if="docs[selectedDoc]?.fee === 'Free'">
                                <div class="flex flex-col items-center text-center py-8">
                                    <span class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mb-3">
                                        <i data-lucide="badge-check" class="w-6 h-6"></i>
                                    </span>
                                    <p class="text-sm font-semibold text-ink"><x-ui.t fil="Walang Kailangang Bayaran" en="No Payment Required" /></p>
                                    <p class="text-xs text-ink-muted mt-1 max-w-[260px]" x-text="docs[selectedDoc]?.name + ($store.lang.current === 'en' ? ' is issued free of charge.' : ' ay ibinibigay nang libre.')"></p>
                                </div>
                            </template>

                            <template x-if="docs[selectedDoc]?.fee !== 'Free'">
                                <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">
                                    <div class="lg:col-span-3 order-2 lg:order-1">
                                        <div x-show="feeInputNames.length" x-cloak class="mb-4 rounded-xl border border-brand-100 bg-brand-50/40 p-4 space-y-3">
                                            <template x-for="name in feeInputNames.filter((n) => feeInputSource(n))" :key="'from-' + name">
                                                <p class="text-xs text-slate-600" x-text="feeInputLabel(name) + ': ₱' + (feeInputValue(name) ?? 0).toLocaleString('en-PH') + ($store.lang.current === 'en' ? ' (from the details you gave)' : ' (mula sa detalyeng inilagay mo)')"></p>
                                            </template>
                                            <div>
                                                <p class="text-sm font-semibold text-ink"><x-ui.t fil="Detalye para sa bayarin" en="Fee details" /></p>
                                                <p class="text-xs text-ink-muted mt-0.5"><x-ui.t fil="Kinukuwenta ang bayarin mula sa halagang ilalagay mo. Kukumpirmahin ito ng opisina." en="The fee is computed from the amount you declare. The office confirms it during review." /></p>
                                            </div>
                                            <template x-for="name in feeInputsAsked" :key="name">
                                                <label class="block">
                                                    <span class="text-xs font-medium text-slate-700" x-text="feeInputLabel(name)"></span>
                                                    <input type="text" inputmode="decimal" x-model="feeInputs[name]" :aria-label="feeInputLabel(name)" placeholder="50,000"
                                                        class="mt-1 w-full rounded-xl px-3 py-2 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none border-0 surface-pressed-sm">
                                                </label>
                                            </template>
                                        </div>
                                        <p class="text-sm font-semibold text-ink mb-2"><x-ui.t fil="Paano magbayad" en="How to pay" /></p>
                                        <div class="rounded-xl border-0 surface-pressed p-4 space-y-2 text-xs text-slate-600 leading-relaxed">
                                            <p class="flex items-start gap-2"><i data-lucide="landmark" class="w-4 h-4 text-brand-600 shrink-0 mt-0.5"></i><x-ui.t fil="Babayaran mo ang bayarin nang personal sa tanggapang nakalista, at bibigyan ka roon ng opisyal na resibo." en="You pay the fee in person at the office listed, which gives you an official receipt." /></p>
                                            <p class="flex items-start gap-2"><i data-lucide="calculator" class="w-4 h-4 text-brand-600 shrink-0 mt-0.5"></i><x-ui.t fil="Kinukuwenta ang eksaktong halaga kapag naisumite mo na ang kahilingan, at makikita mo ito sa Subaybayan." en="The exact amount is assessed when you submit, and shows on Track Request." /></p>
                                            <p class="flex items-start gap-2" x-show="serviceMeta[docs[selectedDoc]?.key]?.policy_blocked"><i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i><x-ui.t fil="Hinihintay pa ang pagpapasya ng LGU sa bayarin ng serbisyong ito; kukumpirmahin ito ng opisina." en="The LGU has not yet ruled on this service's fee; the office will confirm it." /></p>
                                            <p class="flex items-start gap-2 text-amber-700"><i data-lucide="shield-alert" class="w-4 h-4 shrink-0 mt-0.5"></i><x-ui.t fil="Huwag magpadala ng bayad sa anumang e-wallet o account na hindi kinumpirma ng Munisipyo." en="Never send payment to an e-wallet or account the Municipality has not confirmed." /></p>
                                        </div>
                                    </div>

                                    <!-- Invoice -->
                                    <div class="lg:col-span-2 order-1 lg:order-2">
                                        <div class="lg:sticky lg:top-0 rounded-xl border border-slate-200 overflow-hidden">
                                            <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b border-brand-600/10">
                                                <p class="text-xs font-semibold text-ink uppercase tracking-wide"><x-ui.t fil="Buod ng Invoice" en="Invoice Summary" /></p>
                                            </div>
                                            <div class="px-4 py-3.5 text-sm">
                                                <p class="text-[10px] font-mono text-ink-muted mb-2">Municipality of Esperanza</p>
                                                <div class="flex items-center justify-between py-1.5">
                                                    <span class="text-ink-muted"><x-ui.t fil="Sisingilin" en="Bill To" /></span>
                                                    <span class="text-slate-700 font-medium" x-text="applicant.firstName + ' ' + applicant.lastName"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5">
                                                    <span class="text-ink-muted"><x-ui.t fil="Item" en="Item" /></span>
                                                    <span class="text-slate-700 font-medium text-right" x-text="docs[selectedDoc]?.name"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5">
                                                    <span class="text-ink-muted"><x-ui.t fil="Kokolekta na Opisina" en="Collecting Office" /></span>
                                                    <span class="text-slate-700 font-medium" x-text="docs[selectedDoc]?.office"></span>
                                                </div>
                                                <div class="border-t border-dashed border-slate-200 my-2"></div>
                                                <div class="flex items-center justify-between py-1">
                                                    <span class="text-sm font-semibold text-ink inline-flex items-center gap-1">
                                                        <x-ui.t fil="Kabuuang Babayaran" en="Total Due" />
                                                        <x-ui.tooltip text="This is the official rate set by municipal ordinance — the same amount you'd pay at the counter.">
                                                            <i data-lucide="info" class="w-3 h-3 text-ink-faint hover:text-ink-muted cursor-help"></i>
                                                        </x-ui.tooltip>
                                                    </span>
                                                    <span class="text-base font-bold text-brand-700" x-text="docs[selectedDoc]?.fee"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Step 4: review & submit -->
                        <div x-show="step === 4" x-cloak>
                            <p class="text-sm font-semibold text-ink mb-3"><x-ui.t fil="I-review ang Iyong Kahilingan" en="Review Your Request" /></p>
                            <div class="rounded-xl border border-slate-200 overflow-hidden mb-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-brand-600/[.08] bg-slate-50/60">
                                    <div class="flex items-center gap-2.5 px-4 py-3">
                                        <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-ink-muted uppercase tracking-wide"><x-ui.t fil="Dokumento" en="Document" /></p>
                                            <p class="text-sm font-medium text-ink truncate" x-text="docs[selectedDoc]?.name || ''"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5 px-4 py-3">
                                        <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-ink-muted uppercase tracking-wide"><x-ui.t fil="Opisinang Magpoproseso" en="Processing Office" /></p>
                                            <p class="text-sm font-medium text-ink truncate" x-text="docs[selectedDoc]?.office || ''"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5 px-4 py-3">
                                        <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-ink-muted uppercase tracking-wide"><x-ui.t fil="Aplikante" en="Applicant" /></p>
                                            <p class="text-sm font-medium text-ink truncate" x-text="applicant.firstName + ' ' + applicant.lastName"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5 px-4 py-3">
                                        <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="wallet" class="w-4 h-4"></i></span>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-ink-muted uppercase tracking-wide"><x-ui.t fil="Bayad" en="Payment" /></p>
                                            <p class="text-sm font-medium text-ink truncate">
                                                <template x-if="docs[selectedDoc]?.fee === 'Free'"><span><x-ui.t fil="Hindi aplikable" en="Not applicable" /></span></template>
                                                <template x-if="docs[selectedDoc]?.fee !== 'Free'"><span x-text="$store.lang.current === 'en' ? 'Pay at the office (' + docs[selectedDoc]?.fee + ')' : 'Bayaran sa opisina (' + docs[selectedDoc]?.fee + ')'"></span></template>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-5" x-show="docs[selectedDoc]?.fields?.length" x-cloak>
                                <p class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-2">
                                    <x-ui.t fil="Preview" en="Preview" />
                                </p>
                                <template x-if="docs[selectedDoc]?.previewType === 'barangay'">
                                    <x-citizen.barangay-certificate-preview
                                        docKey="docs[selectedDoc]?.key"
                                        bind="formData"
                                        title="docs[selectedDoc]?.certTitle"
                                        subtitle="docs[selectedDoc]?.certSubtitle"
                                        barangay="selectedDocBarangayInfo?.label"
                                        seal="selectedDocBarangayInfo?.seal"
                                        punongBarangay="selectedDocBarangayInfo?.officials?.punong_barangay"
                                        barangaySecretary="selectedDocBarangayInfo?.officials?.barangay_secretary"
                                    />
                                </template>
                                <template x-if="docs[selectedDoc]?.previewType === 'municipal_request'">
                                    <x-citizen.municipal-request-preview
                                        docKey="docs[selectedDoc]?.key"
                                        bind="formData"
                                        title="docs[selectedDoc]?.certTitle"
                                        office="docs[selectedDoc]?.office"
                                        formNo="docs[selectedDoc]?.formNo"
                                        officiant="docs[selectedDoc]?.officiant"
                                        officiantTitle="docs[selectedDoc]?.officiantTitle"
                                        technician="docs[selectedDoc]?.technician"
                                        technicianTitle="docs[selectedDoc]?.technicianTitle"
                                        verifier="docs[selectedDoc]?.verifier"
                                        verifierTitle="docs[selectedDoc]?.verifierTitle"
                                    />
                                </template>
                            </div>

                            <p class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-2"><x-ui.t fil="Mga Nakakabit na Requirement" en="Attached Requirements" /></p>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 mb-5">
                                <template x-for="(req, i) in docs[selectedDoc]?.requirements || []" :key="i">
                                    <li class="flex items-center gap-2 text-xs">
                                        <i data-lucide="circle-check" class="w-3.5 h-3.5 shrink-0" :class="attachments[i] ? 'text-emerald-500' : 'text-ink-faint'"></i>
                                        <span :class="attachments[i] ? 'text-slate-600' : 'text-ink-muted'" class="truncate" x-text="req"></span>
                                    </li>
                                </template>
                            </ul>

                            <p class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3"><x-ui.t fil="Ano ang Susunod" en="What Happens Next" /></p>
                            <div class="flex items-center rounded-xl border border-brand-600/10 bg-slate-50/60 px-4 py-4">
                                <template x-for="(step_label, i) in docs[selectedDoc]?.process || []" :key="i">
                                    <div class="flex-1 flex items-center">
                                        <div class="flex flex-col items-center gap-1.5 text-center">
                                            <span class="w-7 h-7 rounded-full clay-blue flex items-center justify-center text-xs font-semibold shrink-0" x-text="i + 1"></span>
                                            <span class="text-[10px] text-ink-muted max-w-[80px] leading-tight" x-text="step_label"></span>
                                        </div>
                                        <div class="flex-1 h-0.5 bg-brand-200 -mt-5" x-show="i < (docs[selectedDoc]?.process || []).length - 1"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        </div>

                        <x-slot:footer>
                            <p x-show="!submitted && (stepError || stepWarning)" x-cloak role="alert" class="mr-auto min-w-0 text-xs leading-snug" :class="stepError ? 'text-rose-600' : 'text-amber-700'" x-text="stepError || stepWarning"></p>
                            <x-ui.button variant="secondary" size="sm" x-show="!submitted" @click="stepError = ''; stepWarning = ''; step > 1 ? step-- : (open = false, reset())">
                                <span x-text="$store.lang.current === 'en' ? (step > 1 ? 'Back' : 'Cancel') : (step > 1 ? 'Bumalik' : 'Kanselahin')"></span>
                            </x-ui.button>
                            <x-ui.button
                                size="sm"
                                x-show="!submitted"
                                ::disabled="submitting || (step === 1 && (selectedDoc === null || !canFile(docs[selectedDoc])))"
                                @click="
                                    if (step < 4) { advance() }
                                    else {
                                        submitting = true;
                                        submitRequest().then(({ ref, failed }) => {
                                            submitted = true;
                                            const msg = $store.lang.current === 'en'
                                                ? 'Request ' + ref + ' submitted.' + (failed.length ? ' Some files did not upload (' + failed.join(', ') + ') — add them from Track Request.' : '')
                                                : 'Naisumite ang kahilingan ' + ref + '.' + (failed.length ? ' May mga file na hindi na-upload (' + failed.join(', ') + ') — idagdag sa Subaybayan.' : '');
                                            setTimeout(() => { open = false; reset(); $dispatch('toast', { message: msg, variant: failed.length ? 'warning' : 'success' }) }, 1200);
                                        }).catch((err) => {
                                            $dispatch('toast', { message: err && err.explain ? err.explain($store.lang.current) : 'Could not submit the request.', variant: 'error' });
                                        }).finally(() => { submitting = false; });
                                    }
                                "
                            >
                                <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="submitting" x-cloak></i>
                                <span x-text="submitting ? ($store.lang.current === 'en' ? 'Submitting…' : 'Isinusumite…') : ($store.lang.current === 'en' ? (step < 4 ? 'Continue' : 'Submit Request') : (step < 4 ? 'Magpatuloy' : 'Isumite ang Kahilingan'))"></span>
                            </x-ui.button>
                        </x-slot:footer>
                    </x-ui.modal>
                </div>
                </div>
            </div>
        </div>

        {{-- On a phone each request is a card with a full-width Subaybayan
             button; the table cut that column to one letter (live test
             2026-10-07, BUG-4). --}}
        <div class="sm:hidden space-y-2.5">
            <template x-for="req in filteredRequests" :key="'card-' + req.ref">
                <div class="surface-glass rounded-[26px] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-[11px] text-ink-muted" x-text="req.ref"></p>
                            <p class="text-sm font-medium text-ink-body mt-0.5 break-words" x-text="req.type"></p>
                            <p class="text-[11px] text-ink-muted mt-0.5" x-show="req.submitted" x-text="($store.lang.current === 'en' ? 'Submitted ' : 'Isinumite ') + req.submitted"></p>
                        </div>
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap shrink-0"
                            :class="(statusStyles[req.status] || statusStyles['Draft'])[0]"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="(statusStyles[req.status] || statusStyles['Draft'])[1]"></span>
                            <span x-text="req.status"></span>
                        </span>
                    </div>
                    <button type="button" @click="openProgress(req)"
                        class="mt-3 w-full inline-flex items-center justify-center gap-1.5 rounded-2xl border border-slate-200 bg-white py-2 text-xs font-medium text-brand-700 hover:bg-white/60">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i><x-ui.t fil="Subaybayan" en="Track" />
                    </button>
                </div>
            </template>
        </div>

        <x-ui.table class="hidden sm:block">
            <thead>
                <tr class="bg-well/70">
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted"><x-ui.t fil="Reference" en="Reference" /></th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted"><x-ui.t fil="Uri ng Dokumento" en="Document Type" /></th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden lg:table-cell"><x-ui.t fil="Opisina" en="Office" /></th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden md:table-cell"><x-ui.t fil="Layunin" en="Purpose" /></th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted hidden sm:table-cell"><x-ui.t fil="Isinumite" en="Submitted" /></th>
                    <th class="px-4 py-2.5 text-left text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted"><x-ui.t fil="Status" en="Status" /></th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-600/[.08]">
                <template x-for="req in filteredRequests" :key="req.ref">
                    <tr class="hover:bg-white/60 transition-colors cursor-pointer" @click="openProgress(req)"
        tabindex="0"
        @keydown.enter.prevent="openProgress(req)"
        @keydown.space.prevent="openProgress(req)">
                        <td class="px-4 py-3 font-mono text-xs text-ink-muted" x-text="req.ref"></td>
                        <td class="px-4 py-3 text-slate-700 font-medium" x-text="req.type"></td>
                        <td class="px-4 py-3 text-ink-muted hidden lg:table-cell" x-text="req.office"></td>
                        <td class="px-4 py-3 text-ink-muted hidden md:table-cell" x-text="req.purpose"></td>
                        <td class="px-4 py-3 text-ink-muted hidden sm:table-cell" x-text="req.submitted"></td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap"
                                :class="(statusStyles[req.status] || statusStyles['Draft'])[0]"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="(statusStyles[req.status] || statusStyles['Draft'])[1]"
                                ></span>
                                <span x-text="req.status"></span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right" @click.stop="openProgress(req)">
                            <span class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap"><x-ui.t fil="Subaybayan" en="Track" /></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </x-ui.table>

        <div x-show="requestsLoading" class="space-y-2 mt-3"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /></div>
        <div x-show="requestsFailed" x-cloak class="mt-3"><x-ui.error-state messageExpr="$store.lang.current === 'en' ? 'Could not load your requests.' : 'Hindi ma-load ang iyong mga kahilingan.'" retryExpr="loadRequests()" :retryable="true" /></div>
        <div class="flex flex-col items-center text-center py-10 bg-white border border-brand-600/10 rounded-2xl mt-3" x-show="!requestsLoading && !requestsFailed && filteredRequests.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang kahilingang tumutugma dito." en="No requests in this view." /></p>
        </div>

        <!-- Track Request -->
        <div x-data="{ get open() { return progressOpen }, set open(v) { progressOpen = v } }">
            <x-ui.modal title="Subaybayan ang Kahilingan" titleEn="Track Request" maxWidth="md">
                <template x-if="selectedRequest">
                    <div>
                        <p class="text-xs font-mono text-ink-muted" x-text="selectedRequest.ref"></p>
                        <p class="text-sm font-semibold text-ink" x-text="selectedRequest.type"></p>
                        <p class="text-xs text-ink-muted mt-0.5" x-text="selectedRequest.purpose"></p>

                        <!-- Progress stepper -->
                        <div class="flex items-center mt-6 mb-2" x-show="!['Rejected', 'Cancelled'].includes(selectedRequest.status)">
                            <template x-for="(label, i) in stepOrder" :key="i">
                                <div class="flex-1 flex items-center">
                                    <div class="flex flex-col items-center gap-1.5 text-center">
                                        <span
                                            class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold shrink-0 transition-colors duration-[300ms] ease-standard"
                                            :class="stepIndex(selectedRequest.status) > i ? 'bg-emerald-500 text-white' : (stepIndex(selectedRequest.status) === i ? 'clay-blue' : 'bg-slate-100 text-ink-muted')"
                                        >
                                            <i data-lucide="check" class="w-3 h-3" x-show="stepIndex(selectedRequest.status) > i" x-cloak></i>
                                            <span x-show="stepIndex(selectedRequest.status) <= i" x-text="i + 1"></span>
                                        </span>
                                        <span class="text-[9px] max-w-[60px] leading-tight" :class="stepIndex(selectedRequest.status) === i ? 'text-ink font-medium' : 'text-ink-muted'" x-text="label"></span>
                                    </div>
                                    <div class="flex-1 h-0.5 -mt-4 transition-colors duration-[300ms] ease-standard" :class="stepIndex(selectedRequest.status) > i ? 'bg-emerald-400' : 'bg-slate-100'" x-show="i < stepOrder.length - 1"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Document Ready: Preview / Download / Print, once Approved -->
                        <div class="rounded-xl bg-emerald-50 border border-emerald-100 mt-5 p-3.5" x-show="['Approved', 'Mark to Release', 'Released'].includes(selectedRequest.status)" x-cloak>
                            <template x-if="documentReady">
                                <div>
                                    <div class="flex items-start gap-2.5">
                                        <i data-lucide="file-check-2" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-emerald-700" x-text="selectedRequest.status === 'Released' ? ($store.lang.current === 'en' ? 'Your document has been released' : 'Naipagkaloob na ang iyong dokumento') : ($store.lang.current === 'en' ? 'Your document is ready' : 'Handa na ang iyong dokumento')"></p>
                                            <p class="text-xs text-emerald-600 mt-0.5"><x-ui.t fil="Tingnan, i-download, o i-print ang iyong opisyal na kopya." en="Preview, download, or print your official copy." /></p>
                                        </div>
                                    </div>
                                    <x-ui.button size="sm" variant="secondary" icon="eye" class="mt-3 w-full justify-center" @click="docPreviewOpen = true">
                                        <x-ui.t fil="Tingnan ang Dokumento" en="View Document" />
                                    </x-ui.button>
                                </div>
                            </template>
                            <template x-if="!documentReady">
                                <div class="flex items-start gap-2.5">
                                    <i data-lucide="info" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
                                    <p class="text-xs text-emerald-700"><x-ui.t fil="Naaprubahan na ang iyong kahilingan. Hindi available ang online preview para dito — bisitahin ang opisina sa ibaba para kunin ang iyong kopya." en="Your request has been approved. Online preview isn't available for this request — visit the office below to claim your physical copy." /></p>
                                </div>
                            </template>
                        </div>

                        <div class="rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-2.5 mt-5" x-show="selectedRequest.status === 'Rejected'">
                            <div class="flex items-start gap-2.5">
                                <i data-lucide="circle-x" class="w-4 h-4 text-rose-500 shrink-0 mt-0.5"></i>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-rose-700"><x-ui.t fil="Tinanggihan ang kahilingang ito" en="This request was rejected" /></p>
                                    <p class="text-xs text-rose-600 mt-0.5" x-show="selectedRequest.remarks" x-text="selectedRequest.remarks"></p>
                                    <p class="text-xs text-rose-600 mt-0.5" x-show="!selectedRequest.remarks"><x-ui.t fil="Bisitahin ang opisina para sa detalye." en="Visit the office below for details." /></p>
                                </div>
                            </div>

                            {{-- A rejected request is final on the server (the ref never
                                 reopens). The old Correct & Resubmit box only changed this
                                 page; the way forward is a new request. --}}
                            <p class="mt-3 pt-3 border-t border-rose-100/70 text-xs text-rose-700"><x-ui.t fil="Maaari kang magsumite ng bagong kahilingan kapag naayos mo na ang dahilan sa itaas." en="You can file a new request once you've addressed the reason above." /></p>
                        </div>

                        {{-- At every stage, so the resident sees which documents the office accepted
                             (Esperanza bugs QA, 2026-10-06); Attach shows only where a file can still go. --}}
                        <div class="rounded-xl border border-slate-200 mt-5 p-3.5" x-show="(selectedRequest.requirements || []).length" x-cloak>
                            <p class="text-xs font-semibold text-ink mb-2"><x-ui.t fil="Aking mga Dokumento para dito" en="My documents for this request" /></p>
                            <div class="space-y-2">
                                <template x-for="req in (selectedRequest.requirements || [])" :key="req.key">
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-600/10 p-2.5">
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-slate-700 truncate"><span x-text="req.name"></span> <span x-show="req.status !== 'Approved'" class="ml-1 inline-block rounded px-1.5 py-px text-[10px] font-semibold align-middle" :class="reqKindClass(req.name)" x-text="reqKindLabel(req.name)"></span></p>
                                            <p class="text-[11px] truncate" :class="req.file ? 'text-ink-muted' : 'text-amber-600'" x-text="req.file || (reqKind(req.name) === 'office' ? ($store.lang.current === 'en' ? 'Nothing to upload: the office does this with you' : 'Walang ia-upload: gagawin ito ng opisina kasama ka') : ($store.lang.current === 'en' ? 'No file attached yet' : 'Wala pang nakakabit na file'))"></p>
                                            {{-- A rejected document says why, and takes a replacement below. --}}
                                            <p x-show="req.status === 'Rejected' && (req.reject_reason || req.remarks)" class="text-[11px] text-rose-600 whitespace-normal" x-text="($store.lang.current === 'en' ? 'Reason: ' : 'Dahilan: ') + [req.reject_reason, req.remarks].filter((v, i, a) => v && a.indexOf(v) === i).join(' — ')"></p>
                                        </div>
                                        <label x-show="req.attachable && reqKind(req.name) !== 'office'" class="shrink-0 text-[11px] font-medium text-brand-600 hover:underline cursor-pointer" :class="req._busy && 'opacity-60 pointer-events-none'">
                                            <span x-text="req._busy ? '…' : (req.file ? ($store.lang.current === 'en' ? 'Change' : 'Palitan') : ($store.lang.current === 'en' ? 'Attach' : 'Ikabit'))"></span>
                                            <input type="file" class="sr-only" accept="image/*,application/pdf" @change="attachRequirementFile(req, $event.target.files[0]); $event.target.value = ''">
                                        </label>
                                        <label x-show="req.replaceable && req.status === 'Rejected'" class="shrink-0 text-[11px] font-medium text-brand-600 hover:underline cursor-pointer" :class="req._busy && 'opacity-60 pointer-events-none'">
                                            <span x-text="req._busy ? '…' : ($store.lang.current === 'en' ? 'Upload replacement' : 'Mag-upload ng kapalit')"></span>
                                            <input type="file" class="sr-only" accept="image/*,application/pdf" @change="replaceRequirementFile(req, $event.target.files[0]); $event.target.value = ''">
                                        </label>
                                        <span x-show="!req.attachable && !(req.replaceable && req.status === 'Rejected')" class="shrink-0 text-[11px]" :class="req.status === 'Approved' ? 'text-emerald-600 font-semibold' : (req.status === 'Rejected' ? 'text-rose-600 font-semibold' : 'text-ink-muted')" x-text="req.status === 'Approved' ? ($store.lang.current === 'en' ? 'Accepted' : 'Tinanggap') : (req.status === 'Rejected' ? ($store.lang.current === 'en' ? 'Not accepted' : 'Hindi tinanggap') : req.status)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Once released: the photo or scan the office took at release, shown
                             here in the page's own viewer (Esperanza QA, 2026-10-07). --}}
                        <div class="rounded-xl border border-slate-200 mt-5 p-3.5" x-show="selectedRequest.releaseProof" x-cloak>
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-ink"><x-ui.t fil="Patunay ng Pag-release" en="Proof of release" /></p>
                                    <p class="text-[11px] text-ink-muted truncate" x-text="selectedRequest.releaseProof?.name"></p>
                                </div>
                                <button type="button" class="shrink-0 text-[11px] font-medium text-brand-600 hover:underline" @click="proofOpen = !proofOpen" x-text="proofOpen ? lang('Hide', 'Itago') : lang('View', 'Tingnan')"></button>
                            </div>
                            <div x-show="proofOpen" x-cloak x-data="pdfPreview" x-effect="show(proofOpen && selectedRequest.releaseProof ? selectedRequest.releaseProof.url : null, (selectedRequest.releaseProof?.mime || '') + ' ' + (selectedRequest.releaseProof?.name || ''))" class="mt-3 max-h-[60vh] overflow-y-auto rounded-xl border border-slate-200 bg-slate-100 p-2">
                                <p x-show="loading" class="text-xs text-ink-muted text-center py-8"><x-ui.t fil="Binubuksan…" en="Opening…" /></p>
                                <p x-show="failed" x-cloak class="text-xs text-rose-600 text-center py-8"><x-ui.t fil="Hindi maipakita ang file. Isara at buksan muli ang kahilingan." en="The file could not be shown. Close and reopen the request." /></p>
                                <img x-show="image" x-cloak :src="image" alt="Proof of release" class="block w-full h-auto rounded-lg bg-white">
                                <div x-ref="pages"></div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-amber-50 border border-amber-100 mt-5" x-show="selectedRequest.status === 'Under Review' && (selectedRequest.requirements || []).length">
                            <div class="flex items-start gap-2.5 px-3.5 py-2.5">
                                <i data-lucide="triangle-alert" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-amber-700"><x-ui.t fil="Kailangan ng Ayos ang Kahilingan" en="Application Needs Correction" /></p>
                                    <p class="text-xs text-amber-600 mt-0.5"><x-ui.t fil="May mga requirement sa ibaba na kailangan ng atensyon." en="One or more requirements below need your attention." /></p>
                                </div>
                            </div>
                            <div class="px-3.5 pb-3.5 space-y-2">
                                <template x-for="req in (selectedRequest.requirements || [])" :key="req.key">
                                    <div class="rounded-xl border bg-white p-3" :class="{ 'border-emerald-200': req.status === 'Approved', 'border-rose-200': req.status === 'Rejected', 'border-amber-200': req.status === 'Flagged for Replacement', 'border-sky-200': req.status === 'Resubmitted', 'border-slate-200': req.status === 'Submitted' || req.status === 'Not submitted' }">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <i data-lucide="file-text" class="w-4 h-4 text-ink-muted shrink-0"></i>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-medium text-slate-700 truncate" x-text="req.name"></p>
                                                    <p class="text-[11px] text-ink-muted truncate" x-text="req.file || (reqKind(req.name) === 'office' ? ($store.lang.current === 'en' ? 'Done at the office' : 'Gagawin sa opisina') : ($store.lang.current === 'en' ? 'Not yet submitted' : 'Wala pang naisumite'))"></p>
                                                </div>
                                            </div>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset shrink-0"
                                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': req.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': req.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': req.status === 'Submitted' || req.status === 'Flagged for Replacement', 'bg-sky-50 text-sky-700 ring-sky-200': req.status === 'Resubmitted', 'bg-slate-100 text-slate-600 ring-slate-200': req.status === 'Not submitted' }"
                                            ><span x-text="req.status === 'Approved' ? ($store.lang.current === 'en' ? 'Accepted' : 'Tinanggap') : (req.status === 'Not submitted' ? ($store.lang.current === 'en' ? 'Not submitted' : 'Wala pa') : req.status)"></span></span>
                                        </div>

                                        <div x-show="req.status === 'Flagged for Replacement'" class="mt-2 pt-2 border-t border-amber-100 text-[11px]">
                                            <p class="font-semibold text-amber-700" x-text="req.reject_reason"></p>
                                            <p class="text-amber-600 mt-0.5" x-show="req.remarks" x-text="req.remarks"></p>
                                            <label class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 hover:underline cursor-pointer" :class="req._busy && 'opacity-60 pointer-events-none'">
                                                <i data-lucide="upload" class="w-3 h-3"></i><span x-text="req._busy ? ($store.lang.current === 'en' ? 'Uploading…' : 'Ina-upload…') : ($store.lang.current === 'en' ? 'Replace Document' : 'Palitan ang Dokumento')"></span>
                                                <input type="file" class="sr-only" accept="image/*,application/pdf" @change="replaceRequirementFile(req, $event.target.files[0]); $event.target.value = ''">
                                            </label>
                                        </div>
                                    </div>
                                </template>
                                <x-ui.button
                                    class="w-full justify-center" icon="rotate-ccw"
                                    x-bind:disabled="resubmitting || !canResubmitRequirements"
                                    @click="resubmitRequirements()"
                                >
                                    <span x-text="resubmitting ? ($store.lang.current === 'en' ? 'Resubmitting…' : 'Isinusumite muli…') : ($store.lang.current === 'en' ? 'Resubmit Application' : 'Isumite Muli ang Aplikasyon')"></span>
                                </x-ui.button>
                                <p x-show="(selectedRequest?.missingRequired || []).length" x-cloak class="text-[11px] text-amber-700" x-text="($store.lang.current === 'en' ? 'Attach these first, then resubmit: ' : 'I-upload muna ang mga ito bago muling isumite: ') + (selectedRequest?.missingRequired || []).map(m => m.label).join(', ')"></p>
                            </div>
                        </div>

                        <x-citizen.request-fee />

                        <div class="grid grid-cols-2 gap-3 mt-5">
                            <div class="rounded-xl bg-slate-50 border border-brand-600/10 p-3.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1"><x-ui.t fil="Inaasahang Petsa ng Release" en="Expected Release" /></p>
                                <p class="text-sm font-semibold text-ink" x-text="expectedRelease"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-brand-600/10 p-3.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1"><x-ui.t fil="Isinumite" en="Submitted" /></p>
                                <p class="text-sm font-semibold text-ink" x-text="selectedRequest.submitted"></p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-3.5 mt-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-2"><x-ui.t fil="Saan Kukunin / Sino ang Tatawagan" en="Where to Claim / Who to Contact" /></p>
                            <p class="text-sm font-medium text-ink" x-text="selectedRequest.office"></p>
                            <p class="text-xs text-ink-muted mt-1.5 flex items-center gap-1.5" x-show="pickupInfo"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-ink-faint shrink-0"></i><span x-text="pickupInfo?.address"></span></p>
                            <p class="text-xs text-ink-muted mt-1 flex items-center gap-1.5" x-show="pickupInfo"><i data-lucide="phone" class="w-3.5 h-3.5 text-ink-faint shrink-0"></i><span x-text="pickupInfo?.contact"></span></p>
                        </div>
                    </div>
                </template>

                <x-slot:footer>
                    <x-ui.button size="sm" @click="progressOpen = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        <!-- Document Preview: Preview / Download / Print the citizen's actual
             issued Dokyu, reusing the exact same letterhead components the
             New Request wizard uses for its live pre-submission preview —
             here bound to the frozen formSnapshot from submission time
             instead of the wizard's live formData, so it always reflects
             what was actually submitted and approved, not the current
             (possibly since-cleared) wizard state. -->
        <div x-data="{ get open() { return docPreviewOpen }, set open(v) { docPreviewOpen = v } }">
            <x-ui.modal title="Ang Iyong Dokumento" titleEn="Your Document" maxWidth="2xl">
                <template x-if="selectedRequest && documentReady">
                    <div>
                        <div id="dp-print-area">
                            <div class="flex items-center justify-between mb-4 text-[11px] text-ink-muted dp-no-print">
                                <span x-text="selectedRequest.ref"></span>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full font-medium ring-1 ring-inset"
                                      :class="selectedRequest.status === 'Released' ? 'bg-cyan-50 text-cyan-700 ring-cyan-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200'"
                                      x-text="selectedRequest.status"></span>
                            </div>
                            <template x-if="resolvedDocument?.previewType === 'barangay'">
                                <x-citizen.barangay-certificate-preview
                                    docKey="resolvedDocument.docKey"
                                    bind="resolvedDocument.snapshot"
                                    title="resolvedDocument.certTitle"
                                    subtitle="resolvedDocument.certSubtitle"
                                    barangay="selectedRequestBarangayInfo?.label"
                                    seal="selectedRequestBarangayInfo?.seal"
                                    punongBarangay="selectedRequestBarangayInfo?.officials?.punong_barangay"
                                    barangaySecretary="selectedRequestBarangayInfo?.officials?.barangay_secretary"
                                />
                            </template>
                            <template x-if="resolvedDocument?.previewType === 'municipal_request'">
                                <x-citizen.municipal-request-preview
                                    docKey="resolvedDocument.docKey"
                                    bind="resolvedDocument.snapshot"
                                    title="resolvedDocument.certTitle"
                                    office="selectedRequest.office"
                                    formNo="selectedRequest.formNo"
                                    officiant="selectedRequest.officiant"
                                    officiantTitle="selectedRequest.officiantTitle"
                                    technician="selectedRequest.technician"
                                    technicianTitle="selectedRequest.technicianTitle"
                                    verifier="selectedRequest.verifier"
                                    verifierTitle="selectedRequest.verifierTitle"
                                />
                            </template>
                        </div>
                        <p class="text-[11px] text-ink-muted text-center mt-4 dp-no-print">
                            <x-ui.t fil="Kopya para sa iyong sanggunian. Ang pirmadong kopya ay kinukuha sa opisina." en="Reference copy. The signed copy is released at the office." />
                        </p>
                    </div>
                </template>

                <x-slot:footer>
                    <x-ui.button size="sm" variant="secondary" icon="printer" @click="printDocument()"><x-ui.t fil="I-print" en="Print" /></x-ui.button>
                    <x-ui.button size="sm" icon="download" @click="downloadDocument()"><x-ui.t fil="I-download" en="Download PDF" /></x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>
    </div>
</x-layouts.citizen>
