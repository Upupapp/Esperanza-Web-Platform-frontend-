@php
    // All of Cristy's own Dokyu requests are seeded fresh as Submitted —
    // a clean baseline to walk through the Process tab workflow end to end
    // (Submitted -> open -> Under Verification -> ... ) rather than
    // starting from a mix of already-resolved statuses. The one exception
    // is DR-2026-2098 below: the dashboard's own "Mark to Release" stat
    // card (see the stat-card row further down this file) has always
    // claimed a count of 1, with nothing behind it — this gives that
    // number a real request, already carrying the same formSnapshot/docKey
    // a citizen's own submission captures, so the Track Request modal's
    // Preview/Download/Print of the actual issued document is demonstrable
    // without needing devtools to hand-edit a request's status.
    // TAB 04: fixtures live in resources/data/citizen/document-requests.php so this view stays presentation only.
    $__fixtures = require base_path('resources/data/citizen/document-requests.php');
    $requests = $__fixtures['requests'];

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
    foreach ($requests as &$r) {
        $r['days'] = $daysByType[$r['type']] ?? '3-5 working days';
    }
    unset($r);

    // Where to follow up / claim a released document, keyed by office name.
    // Barangay Hall entries vary per barangay (the office string already
    // carries "Barangay Hall (Brgy. X)"), so those are matched by prefix.
    $pickupDirectory = $__fixtures['pickupDirectory'];

    $submittedDocs = $__fixtures['submittedDocs'];

    $availableFiles = $__fixtures['availableFiles'];

    $fileCardStyle = $__fixtures['fileCardStyle'];
@endphp

<x-layouts.citizen title="Dokyu" subtitle="Request and track municipal documents online." active="document-requests">
    <div
        x-data="{
            tab: 'all',
            requests: @js($requests),
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
                'Cancelled': ['bg-slate-100 text-slate-500 ring-slate-200', 'bg-slate-400'],
                'Archived': ['bg-slate-100 text-slate-400 ring-slate-200', 'bg-slate-300'],
            },
            get filteredRequests() {
                return this.requests.filter(r => {
                    if (this.tab === 'all') return true;
                    const isDone = this.doneStatuses.includes(r.status);
                    return this.tab === 'done' ? isDone : !isDone;
                });
            },
            pickupDirectory: @js($pickupDirectory),
            files: @js($submittedDocs),
            // Same lookup the New Request wizard uses for its live preview's
            // letterhead (seal/officials per barangay) — duplicated here (it's
            // a small array, already computed once server-side) so the Track
            // Request modal's Document Preview can resolve the SAME letterhead
            // for an already-submitted request, without needing to share Alpine
            // scope with the wizard's own separate x-data.
            barangayCatalog: @js($barangayCatalog),
            docTypeLookup: @js(config('esperanza_document_type_lookup')),
            progressOpen: false,
            docPreviewOpen: false,
            selectedRequest: null,
            resubmitNote: '',
            resubmitting: false,
            openProgress(r) { this.selectedRequest = r; this.resubmitNote = ''; this.progressOpen = true; },
            resubmitRequest() {
                if (this.resubmitting || !this.resubmitNote.trim()) return;
                this.resubmitting = true;
                setTimeout(() => {
                    this.selectedRequest.status = 'Submitted';
                    this.selectedRequest.purpose = this.resubmitNote.trim();
                    this.selectedRequest.submitted = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    delete this.selectedRequest.remarks;
                    this.resubmitting = false;
                    this.progressOpen = false;
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? this.selectedRequest.ref + ' resubmitted for review.' : 'Naisumite muli ang ' + this.selectedRequest.ref + ' para sa pagsusuri.', variant: 'success' });
                }, 800);
            },
            replaceRequirementFile(req, fileName) {
                req.versions = req.versions || [];
                req.versions.push({ file: req.file, status: req.status, date: req._flaggedDate || '', reject_reason: req.reject_reason, remarks: req.remarks });
                req.file = fileName;
                req.status = 'Resubmitted';
                req.reject_reason = null;
                req.remarks = null;
                req._showPicker = false;
                this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Replacement uploaded — awaiting review.' : 'Na-upload ang kapalit — hinihintay ang pagsusuri.', variant: 'success' });
            },
            get canResubmitRequirements() {
                return this.selectedRequest && (this.selectedRequest.requirements || []).every(r => r.status !== 'Flagged for Replacement');
            },
            // The SAME request moves Under Review -> Resubmitted here — it
            // never goes back to Submitted, never resets, and keeps its ref,
            // original documents, and review remarks (see replaceRequirementFile
            // above, which preserves the flagged version in req.versions
            // rather than overwriting it). On the admin side there's no
            // matching mark-resubmitted click needed — once flagged
            // requirements are checked and approved, the whole request can
            // be approved directly (see admin/document-requests.blade.php).
            resubmitRequirements() {
                if (this.resubmitting || !this.canResubmitRequirements) return;
                this.resubmitting = true;
                setTimeout(() => {
                    this.selectedRequest.status = 'Resubmitted';
                    this.selectedRequest.resubmitted_at = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    this.resubmitting = false;
                    this.progressOpen = false;
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? this.selectedRequest.ref + ' resubmitted for review.' : 'Naisumite muli ang ' + this.selectedRequest.ref + ' para sa pagsusuri.', variant: 'success' });
                }, 800);
            },
            stepOrder: ['Submitted', 'Under Verification', 'Approved', 'Mark to Release', 'Released'],
            stepIndex(status) {
                const map = { 'Submitted': 0, 'Under Verification': 1, 'Under Review': 1, 'Resubmitted': 1, 'Approved': 2, 'Mark to Release': 3, 'Released': 4, 'Completed': 4, 'Rejected': 0, 'Cancelled': 0 };
                return map[status] ?? 0;
            },
            get pickupInfo() {
                const r = this.selectedRequest;
                if (!r) return null;
                if (r.office.startsWith('Barangay Hall')) {
                    return { contact: 'Visit your Barangay Hall', address: r.office + ', Esperanza, Masbate' };
                }
                return this.pickupDirectory[r.office] || { contact: 'Municipal Hall Trunkline (056) 333-1000', address: 'Municipal Hall, Poblacion, Esperanza, Masbate' };
            },
            get expectedRelease() {
                const r = this.selectedRequest;
                if (!r) return '';
                if (/same day/i.test(r.days)) return r.submitted + ' (' + ($store.lang.current === 'en' ? 'same day' : 'ngayon din') + ')';
                const match = r.days.match(/(\d+)(?!.*\d)/);
                const addDays = match ? parseInt(match[1]) : 5;
                const d = new Date(r.submitted);
                d.setDate(d.getDate() + addDays);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },
            // The issued document renders from whichever data is actually
            // available: a request submitted through the wizard carries its
            // own formSnapshot (docKey/previewType/certTitle all captured at
            // submit time — see submitRequest()); a request seeded
            // server-side as pre-resolved demo history (most of the array at
            // the top of this file) never went through the wizard, so this
            // falls back to docTypeLookup (config/esperanza_document_type_lookup.php)
            // to resolve the same docKey/previewType from the request's
            // plain 'type' string, synthesizing a minimal snapshot from the
            // citizen's own name so the correct document still renders
            // instead of dead-ending on a no-document-ready empty state. Only a request
            // whose type isn't in EITHER source returns null — genuinely no
            // template exists for it, which the empty state states honestly.
            get resolvedDocument() {
                const r = this.selectedRequest;
                if (!r) return null;
                if (r.previewType && r.formSnapshot) {
                    return { previewType: r.previewType, docKey: r.docKey, certTitle: r.certTitle, certSubtitle: r.certSubtitle, snapshot: r.formSnapshot, barangay: r.barangay || this.myBarangay };
                }
                const meta = this.docTypeLookup[r.type];
                if (!meta) return null;
                const account = this.$store.citizenSession.account;
                const citizenName = [account?.firstName, account?.lastName].filter(Boolean).join(' ');
                const snapshot = {};
                if (meta.nameField) snapshot[meta.nameField] = citizenName;
                if (meta.purposeField) snapshot[meta.purposeField] = meta.purposeIsArray ? (r.purpose ? [r.purpose] : []) : (r.purpose || '');
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
                return { previewType: meta.previewType, docKey: meta.docKey, certTitle: meta.certTitle, certSubtitle: meta.certSubtitle || null, snapshot, barangay: this.myBarangay };
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
                try {
                    await html2pdf().set({
                        margin: 0,
                        filename: 'Esperanza-' + docName + '-' + citizenName.replace(/[^a-z0-9-]+/gi, '') + '.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { scale: 2, useCORS: true, allowTaint: false, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff' },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                        pagebreak: { mode: ['avoid-all', 'css', 'legacy'], avoid: ['.doc-paper'] },
                    }).from(el).save();
                } catch (error) {
                    console.error('Citizen document PDF download failed:', error);
                } finally {
                    document.body.classList.remove('dp-printing');
                }
            },
            // Deep-link landing: a notification click appends ?ref=DR-2026-xxxx
            // (see citizen/notifications.blade.php) — open that exact
            // request's tracking modal instead of just landing on the list.
            init() {
                // Notifications are server-driven now (see resources/js/app.js) —
                // the backend lifecycle itself creates one on every status
                // change, so there is nothing left to backfill here.
                const ref = new URLSearchParams(window.location.search).get('ref');
                if (!ref) return;
                const match = this.requests.find(r => r.ref === ref);
                if (match) this.openProgress(match);
            },
        }"
        class="animate-fade-up"
    >

        <x-ui.page-intro id="dokyu" icon="file-text" title="Paano gumagana ang Dokyu" titleEn="How Dokyu works">
            <span x-show="tab === 'all'"><x-ui.t fil="Pumili ng dokumento, kumpirmahin ang iyong detalye, bayaran ang bayarin kung meron (marami, tulad ng Certificate of Indigency, ay libre), pagkatapos i-submit. Nagsisimula sa" en="Pick a document, confirm your details, pay the fee if there is one (many, like the Certificate of Indigency, are free), then submit. Reference numbers start with" /> <span class="font-mono">DR-</span> <x-ui.t fil="ang reference number." en="." /></span>
            <span x-show="tab === 'active'" x-cloak><x-ui.t fil="Ang mga kahilingang ito ay nire-review, vine-verify, o inihahanda pa. Aabisuhan ka namin kapag may update — hindi na kailangang sumunod pa sa opisina." en="These requests are still being reviewed, verified, or prepared. We'll notify you the moment there's an update — no need to follow up in person." /></span>
            <span x-show="tab === 'done'" x-cloak><x-ui.t fil="Ang mga kahilingang ito ay na-release o kumpleto na. Puwede mo pa ring balikan ang mga ito rito anumang oras kung kailangan mo ng kopya ng detalye." en="These requests have been released or fully completed. You can revisit any of them here anytime you need a copy of the details." /></span>
        </x-ui.page-intro>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card label="Kabuuang Kahilingan" labelEn="Total Requests" value="6" icon="file-text" color="brand" :delay="0" />
            <x-ui.stat-card label="Isinasagawa" labelEn="In Progress" value="1" icon="loader" color="orange" :delay="40" />
            <x-ui.stat-card label="Handa nang Kunin" labelEn="Mark to Release" value="1" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card label="Kumpleto" labelEn="Completed" value="3" icon="circle-check" color="purple" :delay="120" />
        </div>

        <div class="flex items-center gap-3 mb-3">
            <h3 class="text-sm font-semibold text-navy-900"><x-ui.t fil="Aking mga Kahilingan" en="My Requests" /></h3>
            <x-citizen.status-guide />
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-card px-3 py-2.5 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto shrink-0">
                        @foreach(['all' => ['Lahat', 'All'], 'active' => ['Isinasagawa', 'In Progress'], 'done' => ['Kumpleto', 'Completed']] as $key => [$labelFil, $labelEn])
                            <button
                                @click="tab = '{{ $key }}'"
                                class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap"
                                :class="tab === '{{ $key }}' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            ><x-ui.t :fil="$labelFil" :en="$labelEn" /></button>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-slate-400 shrink-0" x-text="filteredRequests.length + ' ' + ($store.lang.current === 'en' ? 'of ' + requests.length + ' requests' : 'sa ' + requests.length + ' kahilingan')"></p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                <div x-data="{ open: false, fileTab: 'all', showAdd: false, availableFiles: @js($availableFiles) }" class="contents">
                    <x-ui.button @click="open = true" variant="secondary" icon="folder-open" size="sm" class="shrink-0"><x-ui.t fil="Aking mga Isinumiteng Dokyu" en="My Submitted Dokyus" /></x-ui.button>
                    <x-ui.modal title="Aking mga Isinumiteng Dokyu" titleEn="My Submitted Dokyus" maxWidth="2xl">
                        <p class="text-sm text-slate-500 mb-4"><x-ui.t fil="Mga dokumentong na-upload mo para sa iyong mga kahilingan sa Dokyu at Tulong." en="Documents you've uploaded for your Dokyu and Tulong requests." /></p>

                        <!-- Add file toolbar -->
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit">
                                @foreach(['all' => 'All', 'IMG' => 'Images', 'PDF' => 'PDF', 'DOCX' => 'DOCX'] as $key => $label)
                                    <button
                                        @click="fileTab = '{{ $key }}'"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap"
                                        :class="fileTab === '{{ $key }}' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                    >{{ $label }}</button>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="$dispatch('toast', { message: 'File upload is a frontend preview — no data is saved.', variant: 'info' })"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg px-3 py-1.5 hover:border-slate-300 transition-colors"
                                ><i data-lucide="image-plus" class="w-3.5 h-3.5"></i><x-ui.t fil="Magdagdag ng Larawan" en="Add Image" /></button>
                                <button
                                    @click="showAdd = !showAdd"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-white bg-brand-600 rounded-lg px-3 py-1.5 hover:bg-brand-700 transition-colors"
                                ><i data-lucide="folder-plus" class="w-3.5 h-3.5"></i><x-ui.t fil="Magdagdag ng Naka-upload na File" en="Add Pre-uploaded File" /></button>
                            </div>
                        </div>

                        <!-- Pre-uploaded file picker -->
                        <div x-show="showAdd" x-cloak x-transition class="mb-4">
                            <x-ui.file-picker
                                files="availableFiles"
                                onSelect="showAdd = false; $dispatch('toast', { message: file.name + ' attached — this is a frontend preview, no data is saved.', variant: 'success' })"
                                :columns="3"
                            />
                        </div>

                        <!-- File gallery -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($submittedDocs as $doc)
                                @php $style = $fileCardStyle[$doc['category']]; @endphp
                                <div x-show="fileTab === 'all' || fileTab === '{{ $doc['category'] }}'" class="rounded-xl border border-slate-100 overflow-hidden hover:shadow-card-hover transition-all duration-[220ms] ease-standard group">
                                    <div class="relative aspect-square bg-gradient-to-br {{ $style['ring'] }} flex items-center justify-center">
                                        <i data-lucide="{{ $style['icon'] }}" class="w-9 h-9 {{ $style['iconColor'] }} transition-transform duration-[220ms] ease-standard group-hover:scale-110"></i>
                                        <span class="absolute top-1.5 left-1.5 text-[9px] font-bold {{ $style['badge'] }} rounded px-1.5 py-0.5">{{ $doc['category'] }}</span>
                                        <span class="absolute top-1.5 right-1.5"><x-ui.badge :status="$doc['status']" class="!px-1.5 !py-0.5 !text-[9px]" /></span>
                                    </div>
                                    <div class="p-2.5 bg-white">
                                        <p class="text-xs font-medium text-slate-700 truncate">{{ $doc['name'] }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $doc['size'] }} · {{ $doc['uploaded'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <x-slot:footer>
                            <x-ui.button size="sm" @click="open = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                        </x-slot:footer>
                    </x-ui.modal>
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
                        paymentMethod: null,
                        alreadyPaid: false,
                        receipt: null,
                        showReceiptPicker: false,
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
                        files: @js($submittedDocs),
                        barangayCatalog: @js($barangayCatalog),
                        catalogTab: 'barangay',
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
                        paymentMethods: [
                            { key: 'gcash', label: 'GCash', hint: 'Send to 0917-000-0000', hintFil: 'Ipadala sa 0917-000-0000', icon: 'smartphone' },
                            { key: 'paymaya', label: 'Maya', hint: 'Send to 0918-000-0000', hintFil: 'Ipadala sa 0918-000-0000', icon: 'wallet' },
                            { key: 'center', label: 'Onsite', labelFil: 'Bayad sa Opisina', hint: 'Pay at the Treasurer\'s Office', hintFil: 'Magbayad sa Treasurer\'s Office', icon: 'landmark' },
                        ],
                        stepLabels: {
                            fil: ['Pumili ng Dokumento', 'Detalye ng Aplikasyon', 'Bayad', 'I-review at I-submit'],
                            en: ['Choose Document', 'Application Details', 'Payment', 'Review & Submit'],
                        },
                        stepIcons: ['file-text', 'user-round', 'credit-card', 'clipboard-check'],
                        reset() { this.step = 1; this.selectedDoc = null; this.attachments = {}; this.showFilePicker = null; this.submitted = false; this.paymentMethod = null; this.alreadyPaid = false; this.receipt = null; this.showReceiptPicker = false; this.formData = {}; this.otherPurposeText = {}; this.search = ''; },
                        submitRequest() {
                            const doc = this.docs[this.selectedDoc];
                            const purpose = Array.isArray(this.formData.purpose) ? this.formData.purpose.join(', ') : (doc?.name || 'Personal use');
                            const ref = 'DR-2026-' + Math.floor(1000 + Math.random() * 9000);
                            this.requests.unshift({
                                ref,
                                type: doc?.name || '—',
                                office: doc?.office || '—',
                                purpose,
                                days: doc?.days || '3-5 working days',
                                submitted: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                                status: 'Submitted',
                                // Snapshot of exactly what the citizen typed + which template
                                // rendered it, taken now while formData is still live — this is
                                // what powers the Preview/Download/Print of the actual issued
                                // document once the request reaches Approved. A deep clone, not
                                // a reference, so later edits to the wizard's formData (the next
                                // request) can never bleed into an already-submitted one.
                                previewType: doc?.previewType || null,
                                docKey: doc?.key || null,
                                certTitle: doc?.certTitle || null,
                                certSubtitle: doc?.certSubtitle || null,
                                barangay: doc?.barangay || null,
                                formNo: doc?.formNo || null,
                                officiant: doc?.officiant || null,
                                officiantTitle: doc?.officiantTitle || null,
                                technician: doc?.technician || null,
                                technicianTitle: doc?.technicianTitle || null,
                                verifier: doc?.verifier || null,
                                verifierTitle: doc?.verifierTitle || null,
                                formSnapshot: JSON.parse(JSON.stringify(this.formData || {})),
                            });
                            // This wizard still writes the new request into local
                            // Alpine state rather than POST /requests (see
                            // PRODUCTION_READINESS.md) — submission is not yet on
                            // the real backend, so there is no server-side
                            // NotificationService.notifyTransition() call to
                            // reflect here. $store.citizenNotifications and
                            // $store.adminNotifications now read real rows from
                            // GET {citizen,admin}/notifications; a client-side
                            // push() into either would show a notification for a
                            // request the server has never heard of.
                        },
                    }"
                    x-init="catalogTab = myBarangayInfo ? 'barangay' : 'municipal'; seedApplicant(); $watch('$store.citizenSession.account', () => seedApplicant())"
                    x-effect="!open && reset()"
                    class="contents"
                >
                    <x-ui.button @click="open = true" icon="file-plus" size="sm" class="shrink-0"><x-ui.t fil="Bagong Kahilingan sa Dokyu" en="New Dokyu Request" /></x-ui.button>
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
                            <p class="text-sm font-semibold text-navy-900">
                                <x-ui.t fil="Naisumite ang Kahilingan!" en="Request Submitted!" />
                                <span class="inline-block animate-check-pop" style="animation-delay: 0.3s;">🙂</span>
                            </p>
                            <p class="text-xs text-slate-400 mt-1 max-w-[220px]"><x-ui.t fil="Aabisuhan ka namin kapag na-review na ang iyong kahilingan sa Dokyu." en="We'll notify you once your Dokyu request is reviewed." /></p>
                        </div>

                        <div x-show="!submitted">
                        <!-- Step indicator -->
                        <div class="flex items-center mb-8 px-1">
                            <template x-for="(label, i) in ($store.lang.current === 'en' ? stepLabels.en : stepLabels.fil)" :key="i">
                                <div class="flex-1 flex items-center">
                                    <div class="flex flex-col items-center gap-2 text-center">
                                        <span
                                            class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 transition-all duration-[300ms] ease-standard"
                                            :class="step > i + 1 ? 'bg-emerald-500 text-white shadow-sm' : (step === i + 1 ? 'bg-brand-600 text-white shadow-card ring-4 ring-brand-100' : 'bg-slate-100 text-slate-400')"
                                        >
                                            <i data-lucide="check" class="w-4 h-4" x-show="step > i + 1" x-cloak></i>
                                            <i :data-lucide="stepIcons[i]" class="w-4 h-4" x-show="step <= i + 1"></i>
                                        </span>
                                        <span class="text-[11px] max-w-[100px] leading-tight transition-colors duration-[300ms] ease-standard" :class="step === i + 1 ? 'text-navy-900 font-semibold' : 'text-slate-400'" x-text="label"></span>
                                    </div>
                                    <div class="flex-1 h-1 rounded-full mx-1.5 -mt-6 transition-colors duration-[300ms] ease-standard" :class="step > i + 1 ? 'bg-emerald-400' : 'bg-slate-100'" x-show="i < 3"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Step 1: choose document -->
                        <div x-show="step === 1">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 mb-4">
                                <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit shrink-0">
                                    <button type="button" @click="catalogTab = 'barangay'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="catalogTab === 'barangay' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                        <i data-lucide="map-pinned" class="w-3.5 h-3.5"></i><x-ui.t fil="Aking Barangay" en="My Barangay" />
                                    </button>
                                    <button type="button" @click="catalogTab = 'municipal'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="catalogTab === 'municipal' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                        <i data-lucide="landmark" class="w-3.5 h-3.5"></i><x-ui.t fil="Munisipyo" en="Municipal" />
                                    </button>
                                </div>
                                <div class="relative flex-1">
                                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-300 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                    <input aria-label="Search"
                                        type="text" x-model="search"
                                        x-bind:placeholder="$store.lang.current === 'en' ? 'Search documents...' : 'Maghanap ng dokumento...'"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-8 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                                    >
                                </div>
                            </div>

                            <!-- My Barangay -->
                            <div x-show="catalogTab === 'barangay'">
                                <template x-if="myBarangayInfo">
                                    <div>
                                        <div class="flex items-center gap-3 rounded-xl bg-gradient-to-br from-brand-50 to-white border border-brand-100 px-4 py-3 mb-4">
                                            <div class="relative w-11 h-11 shrink-0">
                                                <div class="absolute inset-0 rounded-full bg-white border-2 border-gold-400 flex items-center justify-center text-slate-300"><i data-lucide="landmark" class="w-5 h-5"></i></div>
                                                <img
                                                    :src="'{{ rtrim(asset(''), '/') }}/' + myBarangayInfo.seal"
                                                    x-show="myBarangayInfo.seal" onerror="this.style.display='none'"
                                                    :alt="myBarangayInfo.name ? 'Seal of Barangay ' + myBarangayInfo.name : 'Barangay seal'"
                                                    class="relative w-11 h-11 rounded-full object-cover border-2 border-gold-400"
                                                >
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-navy-900"><x-ui.t fil="Barangay" en="Barangay" /> <span x-text="myBarangayInfo.label"></span></p>
                                                <p class="text-xs text-slate-500"><x-ui.t fil="Punong Barangay:" en="Punong Barangay:" /> <span x-text="myBarangayInfo.officials.punong_barangay"></span></p>
                                            </div>
                                        </div>
                                        <p class="text-sm text-slate-500 mb-3"><x-ui.t fil="Mga dokumentong maaari mong hilingin mula sa iyong barangay hall." en="Documents you can request from your own barangay hall." /></p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            <template x-for="doc in filteredMyBarangayDocs" :key="doc.i">
                                                <label
                                                    class="relative flex flex-col rounded-xl border p-3.5 cursor-pointer transition-all duration-[220ms] ease-standard"
                                                    :class="selectedDoc === doc.i ? 'border-brand-400 bg-brand-50/60 ring-4 ring-brand-100 shadow-sm' : 'border-slate-200 hover:border-slate-300 hover:shadow-sm hover:-translate-y-0.5'"
                                                >
                                                    <input type="radio" name="doc_type" class="sr-only" :checked="selectedDoc === doc.i" @change="selectDoc(doc.i)">
                                                    <div class="flex items-center justify-between mb-2.5">
                                                        <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-colors duration-[160ms] ease-standard" :class="selectedDoc === doc.i ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400'">
                                                            <i data-lucide="file-text" class="w-4 h-4"></i>
                                                        </span>
                                                        <i data-lucide="circle-check" class="w-4 h-4 text-brand-500" x-show="selectedDoc === doc.i" x-cloak></i>
                                                    </div>
                                                    <p class="text-sm font-medium text-slate-700 leading-snug" x-text="doc.name"></p>
                                                    <p class="text-xs text-slate-400 mt-1 truncate" x-text="doc.office + ' · ' + doc.days"></p>
                                                    <span
                                                        class="inline-block w-fit mt-2.5 text-[11px] font-semibold rounded-full px-2.5 py-1"
                                                        :class="doc.fee === 'Free' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-navy-900'"
                                                        x-text="doc.fee"
                                                    ></span>
                                                </label>
                                            </template>
                                            <div class="sm:col-span-2 flex flex-col items-center text-center py-8" x-show="filteredMyBarangayDocs.length === 0">
                                                <span class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-2"><i data-lucide="search-x" class="w-4.5 h-4.5"></i></span>
                                                <p class="text-xs text-slate-400"><x-ui.t fil="Walang dokumentong tumutugma." en="No documents match your search." /></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!myBarangayInfo">
                                    <div class="flex flex-col items-center text-center py-10 px-4">
                                        <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="map-pinned" class="w-7 h-7"></i></span>
                                        <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Hindi pa available online ang mga dokumento ng iyong barangay" en="Your barangay's documents aren't available online yet" /></p>
                                        <p class="text-xs text-slate-400 mt-1 max-w-xs"><x-ui.t fil="Bisitahin ang iyong Barangay Hall, o pumili ng dokumentong munisipyo sa halip." en="Please visit your Barangay Hall directly, or choose a municipal document instead." /></p>
                                    </div>
                                </template>
                            </div>

                            <!-- Municipal -->
                            <div x-show="catalogTab === 'municipal'" x-cloak>
                                <!-- Category grid (shown first, or when searching) -->
                                <template x-if="!selectedCategory || search">
                                    <div>
                                        <p class="text-sm text-slate-500 mb-3" x-show="!search"><x-ui.t fil="Piliin ang uri ng dokumentong kailangan mo." en="Choose the kind of document you need." /></p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" x-show="!search">
                                            <template x-for="cat in municipalCategories" :key="cat.name">
                                                <button
                                                    type="button" @click="chooseCategory(cat.name)"
                                                    class="flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 text-left hover:border-brand-300 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard"
                                                >
                                                    <span class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                                                        <i :data-lucide="cat.icon" class="w-5 h-5"></i>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-sm font-medium text-slate-700 truncate" x-text="cat.name"></span>
                                                        <span class="block text-xs text-slate-400 mt-0.5" x-text="cat.count + (cat.count === 1 ? ' document' : ' documents')"></span>
                                                    </span>
                                                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 shrink-0"></i>
                                                </button>
                                            </template>
                                        </div>

                                        <!-- Search results (flat, across categories) -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" x-show="search">
                                            <template x-for="doc in filteredMunicipalDocs" :key="doc.i">
                                                <label
                                                    class="relative flex flex-col rounded-xl border p-3.5 cursor-pointer transition-all duration-[220ms] ease-standard"
                                                    :class="selectedDoc === doc.i ? 'border-brand-400 bg-brand-50/60 ring-4 ring-brand-100 shadow-sm' : 'border-slate-200 hover:border-slate-300 hover:shadow-sm hover:-translate-y-0.5'"
                                                >
                                                    <input type="radio" name="doc_type" class="sr-only" :checked="selectedDoc === doc.i" @change="selectDoc(doc.i)">
                                                    <div class="flex items-center justify-between mb-2.5">
                                                        <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-colors duration-[160ms] ease-standard" :class="selectedDoc === doc.i ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400'">
                                                            <i data-lucide="landmark" class="w-4 h-4"></i>
                                                        </span>
                                                        <i data-lucide="circle-check" class="w-4 h-4 text-brand-500" x-show="selectedDoc === doc.i" x-cloak></i>
                                                    </div>
                                                    <p class="text-sm font-medium text-slate-700 leading-snug" x-text="doc.name"></p>
                                                    <p class="text-xs text-slate-400 mt-1 truncate" x-text="doc.category + ' · ' + doc.office_short"></p>
                                                    <span
                                                        class="inline-block w-fit mt-2.5 text-[11px] font-semibold rounded-full px-2.5 py-1"
                                                        :class="doc.fee === 'Free' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-navy-900'"
                                                        x-text="doc.fee"
                                                    ></span>
                                                </label>
                                            </template>
                                            <div class="sm:col-span-2 flex flex-col items-center text-center py-8" x-show="filteredMunicipalDocs.length === 0">
                                                <span class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-2"><i data-lucide="search-x" class="w-4.5 h-4.5"></i></span>
                                                <p class="text-xs text-slate-400"><x-ui.t fil="Walang dokumentong tumutugma." en="No documents match your search." /></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Documents within the chosen category -->
                                <template x-if="selectedCategory && !search">
                                    <div>
                                        <button type="button" @click="backToCategories()" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-brand-600 mb-3 transition-colors">
                                            <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                                            <x-ui.t fil="Bumalik sa mga kategorya" en="Back to categories" />
                                        </button>
                                        <p class="text-sm font-semibold text-navy-900 mb-3" x-text="selectedCategory"></p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            <template x-for="doc in municipalDocsInCategory" :key="doc.i">
                                                <label
                                                    class="relative flex flex-col rounded-xl border p-3.5 cursor-pointer transition-all duration-[220ms] ease-standard"
                                                    :class="selectedDoc === doc.i ? 'border-brand-400 bg-brand-50/60 ring-4 ring-brand-100 shadow-sm' : 'border-slate-200 hover:border-slate-300 hover:shadow-sm hover:-translate-y-0.5'"
                                                >
                                                    <input type="radio" name="doc_type" class="sr-only" :checked="selectedDoc === doc.i" @change="selectDoc(doc.i)">
                                                    <div class="flex items-center justify-between mb-2.5">
                                                        <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-colors duration-[160ms] ease-standard" :class="selectedDoc === doc.i ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400'">
                                                            <i data-lucide="landmark" class="w-4 h-4"></i>
                                                        </span>
                                                        <i data-lucide="circle-check" class="w-4 h-4 text-brand-500" x-show="selectedDoc === doc.i" x-cloak></i>
                                                    </div>
                                                    <p class="text-sm font-medium text-slate-700 leading-snug" x-text="doc.name"></p>
                                                    <p class="text-xs text-slate-400 mt-1 truncate" x-text="doc.office_short + ' · ' + doc.days"></p>
                                                    <span
                                                        class="inline-block w-fit mt-2.5 text-[11px] font-semibold rounded-full px-2.5 py-1"
                                                        :class="doc.fee === 'Free' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-navy-900'"
                                                        x-text="doc.fee"
                                                    ></span>
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
                                    <p class="text-sm font-semibold text-navy-900 mb-1"><x-ui.t fil="Impormasyon ng Aplikante" en="Applicant Information" /></p>
                                    <p class="text-xs text-slate-400 mb-3"><x-ui.t fil="Naka-prefill mula sa iyong resident profile — i-edit kung kailangan." en="Pre-filled from your resident profile — edit if needed." /></p>
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <x-ui.input name="req_first_name" label="First Name" x-model="applicant.firstName" />
                                        <x-ui.input name="req_last_name" label="Last Name" x-model="applicant.lastName" />
                                    </div>
                                    <div class="mb-3">
                                        <x-ui.input name="req_address" label="Complete Address" icon="map-pin" x-model="applicant.address" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <x-ui.input name="req_mobile" label="Mobile Number" icon="phone" x-model="applicant.mobile" />
                                        <x-ui.input name="req_email" type="email" label="Email Address" icon="mail" x-model="applicant.email" />
                                    </div>
                                </div>

                                <!-- Live request summary -->
                                <div class="lg:col-span-2">
                                    <div class="lg:sticky lg:top-0 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50/60 to-white p-4">
                                        <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="receipt-text" class="w-3.5 h-3.5 text-brand-500"></i><x-ui.t fil="Buod ng Kahilingan" en="Request Summary" /></p>
                                        <div class="flex items-center gap-2.5 mb-3">
                                            <span class="w-10 h-10 rounded-lg bg-brand-600 text-white flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4.5 h-4.5"></i></span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-navy-900 leading-snug" x-text="docs[selectedDoc]?.name"></p>
                                                <p class="text-xs text-slate-400 truncate" x-text="docs[selectedDoc]?.office"></p>
                                            </div>
                                        </div>
                                        <div class="space-y-1.5 text-xs border-t border-brand-100/70 pt-3">
                                            <div class="flex items-center justify-between"><span class="text-slate-400"><x-ui.t fil="Bayarin" en="Fee" /></span><span class="font-semibold text-navy-900" x-text="docs[selectedDoc]?.fee"></span></div>
                                            <div class="flex items-center justify-between"><span class="text-slate-400"><x-ui.t fil="Oras ng Pagproseso" en="Processing Time" /></span><span class="font-medium text-slate-600" x-text="docs[selectedDoc]?.days"></span></div>
                                            <div class="flex items-center justify-between"><span class="text-slate-400"><x-ui.t fil="Requirements" en="Requirements" /></span><span class="font-medium text-slate-600" x-text="Object.keys(attachments).filter(k => attachments[k]).length + ' / ' + (docs[selectedDoc]?.requirements || []).length + ' attached'"></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Full-width: generic purpose OR structured fields (barangay documents) — sits below the two-column grid so it isn't squeezed beside the sticky Request Summary -->
                            <div class="mb-6" x-show="!(docs[selectedDoc]?.fields?.length)">
                                <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Layunin" en="Purpose" /></label>
                                <textarea aria-label="e.g. Employment requirement" rows="2" placeholder="e.g. Employment requirement" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                            </div>

                            <div class="mb-6" x-show="docs[selectedDoc]?.fields?.length" x-cloak>
                                <p class="text-sm font-semibold text-navy-900 mb-1" x-text="docs[selectedDoc]?.certTitle || ''"></p>
                                <p class="text-xs text-slate-400 mb-3" x-show="docs[selectedDoc]?.certSubtitle" x-text="docs[selectedDoc]?.certSubtitle"></p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                    <template x-for="f in (docs[selectedDoc]?.fields || [])" :key="f.key">
                                        <div :class="f.type === 'checkbox-group' ? 'sm:col-span-2 lg:col-span-3' : ''">
                                            <template x-if="f.type === 'derived_age'">
                                                <div x-init="$watch(() => formData[f.derived_from], v => formData[f.key] = computeAge(v)); formData[f.key] = computeAge(formData[f.derived_from])">
                                                    <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label"></label>
                                                    <div class="w-full rounded-xl border border-slate-200 bg-slate-100/70 py-2.5 px-4 text-sm text-slate-500" x-text="(formData[f.key] ?? null) !== null ? formData[f.key] + ' years old' : 'Set birthdate first'"></div>
                                                </div>
                                            </template>
                                            <template x-if="f.type === 'text' || f.type === 'number' || f.type === 'date'">
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                    <input :aria-label="f.label"
                                                        :type="f.type" x-model="formData[f.key]"
                                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                                                    >
                                                </div>
                                            </template>
                                            <template x-if="f.type === 'select'">
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
                                                    <select :aria-label="f.label" x-model="formData[f.key]" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
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
                                                        <input aria-label="Others (please specify)" type="text" x-model="otherPurposeText[f.key]" placeholder="Others (please specify)" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3.5 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-3" x-show="docs[selectedDoc]?.validity" x-text="docs[selectedDoc]?.validity"></p>
                            </div>

                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-navy-900"><x-ui.t fil="Mga Requirement" en="Requirements" /></p>
                                <p class="text-[11px] font-medium text-slate-400" x-text="Object.keys(attachments).filter(k => attachments[k]).length + ' / ' + (docs[selectedDoc]?.requirements || []).length"></p>
                            </div>
                            <div class="h-1 rounded-full bg-slate-100 overflow-hidden mb-3">
                                <div class="h-full bg-emerald-400 rounded-full transition-all duration-[600ms] ease-standard" :style="'width:' + (((docs[selectedDoc]?.requirements || []).length ? (Object.keys(attachments).filter(k => attachments[k]).length / (docs[selectedDoc]?.requirements || []).length) : 0) * 100) + '%'"></div>
                            </div>
                            <p class="text-xs text-slate-400 mb-3"><x-ui.t fil="Ikabit mula sa mga file na na-upload mo na, o mag-upload ng bago." en="Attach from your already-uploaded files, or upload a new one." /></p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <template x-for="(req, i) in docs[selectedDoc]?.requirements || []" :key="i">
                                    <div class="rounded-xl border p-3 transition-colors duration-[220ms] ease-standard" :class="attachments[i] ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200'">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-xs text-slate-600 flex-1" x-text="req"></p>
                                            <button
                                                type="button" x-show="!attachments[i]"
                                                @click="showFilePicker = (showFilePicker === i ? null : i)"
                                                class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap"
                                            ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                                            <div class="flex items-center gap-1.5 shrink-0" x-show="attachments[i]">
                                                <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap">
                                                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i><span x-text="attachments[i]"></span>
                                                </span>
                                                <button type="button" @click="attachments[i] = null" class="text-slate-300 hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                            </div>
                                        </div>
                                        <div x-show="showFilePicker === i" x-cloak x-transition class="mt-2.5 pt-2.5 border-t border-slate-100">
                                            <x-ui.file-picker
                                                files="files"
                                                onSelect="attachments[i] = file.name; showFilePicker = null"
                                                uploadAction="attachments[i] = 'New upload.jpg'; showFilePicker = null; $dispatch('toast', { message: 'File upload is a frontend preview — no data is saved.', variant: 'info' })"
                                                :columns="2"
                                                :search="false"
                                            />
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
                                    <p class="text-sm font-semibold text-navy-900"><x-ui.t fil="Walang Kailangang Bayaran" en="No Payment Required" /></p>
                                    <p class="text-xs text-slate-400 mt-1 max-w-[260px]" x-text="docs[selectedDoc]?.name + ($store.lang.current === 'en' ? ' is issued free of charge.' : ' ay ibinibigay nang libre.')"></p>
                                </div>
                            </template>

                            <template x-if="docs[selectedDoc]?.fee !== 'Free'">
                                <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">
                                    <div class="lg:col-span-3 order-2 lg:order-1">
                                        <template x-if="!alreadyPaid">
                                            <div>
                                                <p class="text-sm font-semibold text-navy-900 mb-3"><x-ui.t fil="Piliin ang Paraan ng Pagbabayad" en="Choose Payment Method" /></p>
                                                <div class="space-y-2 mb-4">
                                                    <template x-for="m in paymentMethods" :key="m.key">
                                                        <label
                                                            class="flex items-center gap-3 rounded-xl border p-3 cursor-pointer transition-all duration-[220ms] ease-standard"
                                                            :class="paymentMethod === m.key ? 'border-brand-400 bg-brand-50/60 ring-4 ring-brand-100' : 'border-slate-200 hover:border-slate-300'"
                                                        >
                                                            <input type="radio" name="payment_method" class="text-brand-600 focus:ring-brand-200" :checked="paymentMethod === m.key" @change="paymentMethod = m.key">
                                                            <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                                                                <i :data-lucide="m.icon" class="w-4 h-4"></i>
                                                            </span>
                                                            <div class="min-w-0">
                                                                <p class="text-sm font-medium text-slate-700" x-text="$store.lang.current === 'en' ? m.label : (m.labelFil || m.label)"></p>
                                                                <p class="text-xs text-slate-400" x-text="$store.lang.current === 'en' ? m.hint : (m.hintFil || m.hint)"></p>
                                                            </div>
                                                        </label>
                                                    </template>
                                                </div>
                                                <button type="button" @click="alreadyPaid = true" class="text-xs font-medium text-brand-600 hover:underline"><x-ui.t fil="Nakabayad na ako — ikakabit ang resibo" en="I've already paid — attach my receipt instead" /></button>
                                            </div>
                                        </template>

                                        <template x-if="alreadyPaid">
                                            <div>
                                                <div class="flex items-center justify-between mb-3">
                                                    <p class="text-sm font-semibold text-navy-900"><x-ui.t fil="Ikabit ang Patunay ng Bayad" en="Attach Proof of Payment" /></p>
                                                    <button type="button" @click="alreadyPaid = false; receipt = null" class="text-xs font-medium text-slate-400 hover:text-slate-600"><x-ui.t fil="Kanselahin" en="Cancel" /></button>
                                                </div>
                                                <div class="rounded-xl border border-slate-200 p-3">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <p class="text-xs text-slate-600 flex-1"><x-ui.t fil="Resibo o screenshot ng kumpirmasyon ng bayad" en="Receipt or payment confirmation screenshot" /></p>
                                                        <button
                                                            type="button" x-show="!receipt"
                                                            @click="showReceiptPicker = !showReceiptPicker"
                                                            class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap"
                                                        ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                                                        <div class="flex items-center gap-1.5 shrink-0" x-show="receipt">
                                                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap">
                                                                <i data-lucide="circle-check" class="w-3.5 h-3.5"></i><span x-text="receipt"></span>
                                                            </span>
                                                            <button type="button" @click="receipt = null" class="text-slate-300 hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                                        </div>
                                                    </div>
                                                    <div x-show="showReceiptPicker" x-cloak x-transition class="mt-2.5 pt-2.5 border-t border-slate-100">
                                                        <x-ui.file-picker
                                                            files="files"
                                                            onSelect="receipt = file.name; showReceiptPicker = false"
                                                            uploadAction="receipt = 'Receipt Scan.jpg'; showReceiptPicker = false; $dispatch('toast', { message: 'File upload is a frontend preview — no data is saved.', variant: 'info' })"
                                                            :columns="2"
                                                            :search="false"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Invoice -->
                                    <div class="lg:col-span-2 order-1 lg:order-2">
                                        <div class="lg:sticky lg:top-0 rounded-xl border border-slate-200 overflow-hidden">
                                            <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b border-slate-100">
                                                <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide"><x-ui.t fil="Buod ng Invoice" en="Invoice Summary" /></p>
                                            </div>
                                            <div class="px-4 py-3.5 text-sm">
                                                <p class="text-[10px] font-mono text-slate-400 mb-2">Municipality of Esperanza</p>
                                                <div class="flex items-center justify-between py-1.5">
                                                    <span class="text-slate-500"><x-ui.t fil="Sisingilin" en="Bill To" /></span>
                                                    <span class="text-slate-700 font-medium" x-text="applicant.firstName + ' ' + applicant.lastName"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5">
                                                    <span class="text-slate-500"><x-ui.t fil="Item" en="Item" /></span>
                                                    <span class="text-slate-700 font-medium text-right" x-text="docs[selectedDoc]?.name"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5">
                                                    <span class="text-slate-500"><x-ui.t fil="Kokolekta na Opisina" en="Collecting Office" /></span>
                                                    <span class="text-slate-700 font-medium" x-text="docs[selectedDoc]?.office"></span>
                                                </div>
                                                <div class="border-t border-dashed border-slate-200 my-2"></div>
                                                <div class="flex items-center justify-between py-1">
                                                    <span class="text-sm font-semibold text-navy-900 inline-flex items-center gap-1">
                                                        <x-ui.t fil="Kabuuang Babayaran" en="Total Due" />
                                                        <x-ui.tooltip text="This is the official rate set by municipal ordinance — the same amount you'd pay at the counter.">
                                                            <i data-lucide="info" class="w-3 h-3 text-slate-300 hover:text-slate-500 cursor-help"></i>
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
                            <p class="text-sm font-semibold text-navy-900 mb-3"><x-ui.t fil="I-review ang Iyong Kahilingan" en="Review Your Request" /></p>
                            <div class="rounded-xl border border-slate-200 overflow-hidden mb-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 bg-slate-50/60">
                                    <div class="flex items-center gap-2.5 px-4 py-3">
                                        <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wide"><x-ui.t fil="Dokumento" en="Document" /></p>
                                            <p class="text-sm font-medium text-navy-900 truncate" x-text="docs[selectedDoc]?.name || ''"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5 px-4 py-3">
                                        <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wide"><x-ui.t fil="Opisinang Magpoproseso" en="Processing Office" /></p>
                                            <p class="text-sm font-medium text-navy-900 truncate" x-text="docs[selectedDoc]?.office || ''"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5 px-4 py-3">
                                        <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="user" class="w-4 h-4"></i></span>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wide"><x-ui.t fil="Aplikante" en="Applicant" /></p>
                                            <p class="text-sm font-medium text-navy-900 truncate" x-text="applicant.firstName + ' ' + applicant.lastName"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5 px-4 py-3">
                                        <span class="w-8 h-8 rounded-lg bg-white text-brand-600 border border-slate-200 flex items-center justify-center shrink-0"><i data-lucide="wallet" class="w-4 h-4"></i></span>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wide"><x-ui.t fil="Bayad" en="Payment" /></p>
                                            <p class="text-sm font-medium text-navy-900 truncate">
                                                <template x-if="docs[selectedDoc]?.fee === 'Free'"><span><x-ui.t fil="Hindi aplikable" en="Not applicable" /></span></template>
                                                <template x-if="docs[selectedDoc]?.fee !== 'Free' && alreadyPaid"><span x-text="$store.lang.current === 'en' ? 'Paid — receipt attached' : 'Nabayaran na — nakakabit ang resibo'"></span></template>
                                                <template x-if="docs[selectedDoc]?.fee !== 'Free' && !alreadyPaid"><span x-text="paymentMethod ? (paymentMethods.find(m => m.key === paymentMethod)?.label || '') : ($store.lang.current === 'en' ? 'Not selected' : 'Wala pang napili')"></span></template>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-5" x-show="docs[selectedDoc]?.fields?.length" x-cloak>
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
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

                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2"><x-ui.t fil="Mga Nakakabit na Requirement" en="Attached Requirements" /></p>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 mb-5">
                                <template x-for="(req, i) in docs[selectedDoc]?.requirements || []" :key="i">
                                    <li class="flex items-center gap-2 text-xs">
                                        <i data-lucide="circle-check" class="w-3.5 h-3.5 shrink-0" :class="attachments[i] ? 'text-emerald-500' : 'text-slate-300'"></i>
                                        <span :class="attachments[i] ? 'text-slate-600' : 'text-slate-400'" class="truncate" x-text="req"></span>
                                    </li>
                                </template>
                            </ul>

                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3"><x-ui.t fil="Ano ang Susunod" en="What Happens Next" /></p>
                            <div class="flex items-center rounded-xl border border-slate-100 bg-slate-50/60 px-4 py-4">
                                <template x-for="(step_label, i) in docs[selectedDoc]?.process || []" :key="i">
                                    <div class="flex-1 flex items-center">
                                        <div class="flex flex-col items-center gap-1.5 text-center">
                                            <span class="w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-semibold shrink-0 shadow-sm" x-text="i + 1"></span>
                                            <span class="text-[10px] text-slate-500 max-w-[80px] leading-tight" x-text="step_label"></span>
                                        </div>
                                        <div class="flex-1 h-0.5 bg-brand-200 -mt-5" x-show="i < (docs[selectedDoc]?.process || []).length - 1"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        </div>

                        <x-slot:footer>
                            <x-ui.button variant="secondary" size="sm" x-show="!submitted" @click="step > 1 ? step-- : (open = false, reset())">
                                <span x-text="$store.lang.current === 'en' ? (step > 1 ? 'Back' : 'Cancel') : (step > 1 ? 'Bumalik' : 'Kanselahin')"></span>
                            </x-ui.button>
                            <x-ui.button
                                size="sm"
                                x-show="!submitted"
                                ::disabled="submitting || (step === 1 && selectedDoc === null) || (step === 3 && docs[selectedDoc]?.fee !== 'Free' && !alreadyPaid && !paymentMethod) || (step === 3 && alreadyPaid && !receipt)"
                                @click="
                                    if (step < 4) { step++ }
                                    else {
                                        submitting = true;
                                        setTimeout(() => {
                                            submitting = false;
                                            submitted = true;
                                            submitRequest();
                                            setTimeout(() => { open = false; reset(); $dispatch('toast', { message: $store.lang.current === 'en' ? 'Document request submitted — this is a frontend preview, no data is saved.' : 'Naisumite ang kahilingan sa dokumento — frontend preview lang ito, walang naiimbak na datos.', variant: 'success' }) }, 1400);
                                        }, 700);
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

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400"><x-ui.t fil="Reference" en="Reference" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400"><x-ui.t fil="Uri ng Dokumento" en="Document Type" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell"><x-ui.t fil="Opisina" en="Office" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell"><x-ui.t fil="Layunin" en="Purpose" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell"><x-ui.t fil="Isinumite" en="Submitted" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400"><x-ui.t fil="Status" en="Status" /></th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="req in filteredRequests" :key="req.ref">
                    <tr class="hover:bg-slate-50/70 transition-colors cursor-pointer" @click="openProgress(req)"
        tabindex="0"
        @keydown.enter.prevent="openProgress(req)"
        @keydown.space.prevent="openProgress(req)">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500" x-text="req.ref"></td>
                        <td class="px-4 py-3 text-slate-700 font-medium" x-text="req.type"></td>
                        <td class="px-4 py-3 text-slate-500 hidden lg:table-cell" x-text="req.office"></td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell" x-text="req.purpose"></td>
                        <td class="px-4 py-3 text-slate-500 hidden sm:table-cell" x-text="req.submitted"></td>
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

        <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="filteredRequests.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang kahilingang tumutugma dito." en="No requests in this view." /></p>
        </div>

        <!-- Track Request -->
        <div x-data="{ get open() { return progressOpen }, set open(v) { progressOpen = v } }">
            <x-ui.modal title="Subaybayan ang Kahilingan" titleEn="Track Request" maxWidth="md">
                <template x-if="selectedRequest">
                    <div>
                        <p class="text-xs font-mono text-slate-400" x-text="selectedRequest.ref"></p>
                        <p class="text-sm font-semibold text-navy-900" x-text="selectedRequest.type"></p>
                        <p class="text-xs text-slate-400 mt-0.5" x-text="selectedRequest.purpose"></p>

                        <!-- Progress stepper -->
                        <div class="flex items-center mt-6 mb-2" x-show="!['Rejected', 'Cancelled'].includes(selectedRequest.status)">
                            <template x-for="(label, i) in stepOrder" :key="i">
                                <div class="flex-1 flex items-center">
                                    <div class="flex flex-col items-center gap-1.5 text-center">
                                        <span
                                            class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold shrink-0 transition-colors duration-[300ms] ease-standard"
                                            :class="stepIndex(selectedRequest.status) > i ? 'bg-emerald-500 text-white' : (stepIndex(selectedRequest.status) === i ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400')"
                                        >
                                            <i data-lucide="check" class="w-3 h-3" x-show="stepIndex(selectedRequest.status) > i" x-cloak></i>
                                            <span x-show="stepIndex(selectedRequest.status) <= i" x-text="i + 1"></span>
                                        </span>
                                        <span class="text-[9px] max-w-[60px] leading-tight" :class="stepIndex(selectedRequest.status) === i ? 'text-navy-900 font-medium' : 'text-slate-400'" x-text="label"></span>
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

                            <div class="mt-3 pt-3 border-t border-rose-100/70" x-show="!(selectedRequest.requirements || []).length">
                                <label class="block text-xs font-medium text-rose-700 mb-1.5"><x-ui.t fil="Itama at Isumite Muli" en="Correct & Resubmit" /></label>
                                <textarea aria-label="Resubmit note"
                                    x-model="resubmitNote" rows="2"
                                    :placeholder="$store.lang.current === 'en' ? 'Describe what you corrected, or provide the missing requirement...' : 'Ilarawan kung ano ang inayos mo, o ibigay ang kulang na requirement...'"
                                    class="w-full rounded-xl border border-rose-200 bg-white py-2.5 px-3.5 text-sm text-slate-700 focus:border-rose-400 focus:ring-4 focus:ring-rose-100 outline-none transition-all duration-[160ms] ease-standard resize-none"
                                ></textarea>
                                <x-ui.button
                                    size="sm" icon="rotate-ccw" class="mt-2.5 w-full justify-center"
                                    x-bind:disabled="resubmitting || !resubmitNote.trim()"
                                    @click="resubmitRequest()"
                                >
                                    <span x-text="resubmitting ? ($store.lang.current === 'en' ? 'Resubmitting…' : 'Isinusumite muli…') : ($store.lang.current === 'en' ? 'Resubmit Request' : 'Isumite Muli ang Kahilingan')"></span>
                                </x-ui.button>
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
                                    <div class="rounded-xl border bg-white p-3" :class="{ 'border-emerald-200': req.status === 'Approved', 'border-rose-200': req.status === 'Rejected', 'border-amber-200': req.status === 'Flagged for Replacement', 'border-sky-200': req.status === 'Resubmitted', 'border-slate-200': req.status === 'Submitted' }">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <i data-lucide="file-text" class="w-4 h-4 text-slate-400 shrink-0"></i>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-medium text-slate-700 truncate" x-text="req.name"></p>
                                                    <p class="text-[11px] text-slate-400 truncate" x-text="req.file || 'Not yet submitted'"></p>
                                                </div>
                                            </div>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset shrink-0"
                                                :class="{ 'bg-emerald-50 text-emerald-700 ring-emerald-200': req.status === 'Approved', 'bg-rose-50 text-rose-700 ring-rose-200': req.status === 'Rejected', 'bg-amber-50 text-amber-700 ring-amber-200': req.status === 'Submitted' || req.status === 'Flagged for Replacement', 'bg-sky-50 text-sky-700 ring-sky-200': req.status === 'Resubmitted' }"
                                            ><span x-text="req.status === 'Approved' ? 'Verified' : req.status"></span></span>
                                        </div>

                                        <div x-show="req.status === 'Flagged for Replacement'" class="mt-2 pt-2 border-t border-amber-100 text-[11px]">
                                            <p class="font-semibold text-amber-700" x-text="req.reject_reason"></p>
                                            <p class="text-amber-600 mt-0.5" x-show="req.remarks" x-text="req.remarks"></p>
                                            <button type="button" x-show="!req._showPicker" @click="req._showPicker = true" class="mt-1.5 text-[11px] font-medium text-brand-600 hover:underline"><x-ui.t fil="Palitan ang Dokumento" en="Replace Document" /></button>
                                            <div x-show="req._showPicker" x-cloak x-transition class="mt-2 pt-2 border-t border-amber-100/70">
                                                <x-ui.file-picker
                                                    files="files"
                                                    onSelect="replaceRequirementFile(req, file.name)"
                                                    uploadAction="replaceRequirementFile(req, 'New upload.jpg')"
                                                    :columns="2"
                                                    :search="false"
                                                    :bilingual="false"
                                                />
                                            </div>
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
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mt-5">
                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-3.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 mb-1"><x-ui.t fil="Inaasahang Petsa ng Release" en="Expected Release" /></p>
                                <p class="text-sm font-semibold text-navy-900" x-text="expectedRelease"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-3.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 mb-1"><x-ui.t fil="Isinumite" en="Submitted" /></p>
                                <p class="text-sm font-semibold text-navy-900" x-text="selectedRequest.submitted"></p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-3.5 mt-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 mb-2"><x-ui.t fil="Saan Kukunin / Sino ang Tatawagan" en="Where to Claim / Who to Contact" /></p>
                            <p class="text-sm font-medium text-navy-900" x-text="selectedRequest.office"></p>
                            <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1.5" x-show="pickupInfo"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i><span x-text="pickupInfo?.address"></span></p>
                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5" x-show="pickupInfo"><i data-lucide="phone" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i><span x-text="pickupInfo?.contact"></span></p>
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
                            <div class="flex items-center justify-between mb-4 text-[11px] text-slate-400 dp-no-print">
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
                        <p class="text-[11px] text-slate-400 text-center mt-4 dp-no-print">
                            <x-ui.t fil="Frontend na preview lamang — walang tunay na e-signature o QR code na naka-verify." en="Frontend preview only — not digitally signed or QR-verified." />
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
