@php
    $requests = [
        ['ref' => 'DR-2026-2210', 'type' => 'Cedula (Community Tax Certificate)', 'office' => "Treasurer's Office", 'purpose' => 'Employment requirement', 'submitted' => 'Jul 8, 2026', 'status' => 'Under Verification'],
        ['ref' => 'DR-2026-2187', 'type' => 'Barangay Clearance', 'office' => 'Barangay Hall', 'purpose' => 'Business permit renewal', 'submitted' => 'Jul 3, 2026', 'status' => 'Ready for Release'],
        ['ref' => 'DR-2026-1998', 'type' => 'Certificate of Residency', 'office' => 'Civil Registrar', 'purpose' => 'School enrollment', 'submitted' => 'Jun 14, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-1872', 'type' => 'Certificate of Indigency', 'office' => 'MSWDO', 'purpose' => 'Medical assistance application', 'submitted' => 'May 30, 2026', 'status' => 'Completed'],
        ['ref' => 'DR-2026-1655', 'type' => 'Business Permit (New)', 'office' => 'BPLO', 'purpose' => 'Sari-sari store registration', 'submitted' => 'May 12, 2026', 'status' => 'Rejected'],
        ['ref' => 'DR-2026-1490', 'type' => 'Real Property Tax Clearance', 'office' => "Treasurer's Office", 'purpose' => 'Lot sale requirement', 'submitted' => 'Apr 20, 2026', 'status' => 'Completed'],
    ];

    $documentTypes = [
        [
            'name' => 'Barangay Clearance', 'office' => 'Barangay Hall', 'fee' => '₱50.00', 'days' => 'Same day',
            'requirements' => ['One (1) valid government-issued ID', "Current year's Cedula (Community Tax Certificate)", 'Proof of residency (if newly transferred)'],
            'process' => ['Submit Request', 'ID & Cedula Verification', 'Pay Fee', 'Release'],
        ],
        [
            'name' => 'Cedula (Community Tax Certificate)', 'office' => "Treasurer's Office", 'fee' => '₱5.00 base + ₱1.00 per ₱1,000 income', 'days' => 'Same day',
            'requirements' => ['One (1) valid government-issued ID', 'Proof of income or Certificate of Employment (if applicable)'],
            'process' => ['Submit Request', 'Compute Tax Due', 'Pay Fee', 'Release'],
        ],
        [
            'name' => 'Certificate of Residency', 'office' => 'Civil Registrar', 'fee' => '₱50.00', 'days' => '1-2 working days',
            'requirements' => ['One (1) valid government-issued ID', 'Barangay Clearance', 'Proof of residency (utility bill or lease contract)'],
            'process' => ['Submit Request', 'Verify Residency', 'Approval', 'Release'],
        ],
        [
            'name' => 'Certificate of Indigency', 'office' => 'MSWDO', 'fee' => 'Free', 'days' => '2-3 working days',
            'requirements' => ['One (1) valid government-issued ID', 'Barangay Certification of Indigency', 'Brief interview / case assessment with MSWDO'],
            'process' => ['Submit Request', 'MSWDO Interview', 'Case Assessment', 'Release'],
        ],
        [
            'name' => 'Business Permit (New Application)', 'office' => 'BPLO', 'fee' => '₱500.00 and up (based on capital)', 'days' => '7 working days',
            'requirements' => ['DTI or SEC Registration', 'Barangay Business Clearance', 'Locational / Zoning Clearance', 'Fire Safety Inspection Certificate (FSIC)', 'Sanitary Permit', 'Cedula (Community Tax Certificate)', 'Lease contract or land title'],
            'process' => ['Submit Requirements', 'Zoning & Fire Inspection', 'Assessment & Payment', 'Release'],
        ],
        [
            'name' => 'Business Permit Renewal', 'office' => 'BPLO', 'fee' => 'Based on prior year gross receipts', 'days' => '3 working days',
            'requirements' => ["Previous year's Business Permit and Official Receipt", 'Barangay Clearance (current year)', 'Fire Safety Inspection Certificate (FSIC)', 'Sanitary Permit', 'Audited Financial Statement or Income Tax Return'],
            'process' => ['Submit Requirements', 'Verify Prior Permit', 'Assessment & Payment', 'Release'],
        ],
        [
            'name' => 'Real Property Tax Clearance', 'office' => "Treasurer's Office", 'fee' => '₱100.00', 'days' => 'Same day',
            'requirements' => ['Latest Tax Declaration', 'Official Receipt of last RPT payment', 'One (1) valid government-issued ID'],
            'process' => ['Submit Request', 'Verify Tax Records', 'Settle Balance (if any)', 'Release'],
        ],
        [
            'name' => 'Senior Citizen ID', 'office' => 'OSCA', 'fee' => 'Free', 'days' => '3-5 working days',
            'requirements' => ['PSA Birth Certificate or any proof of age', 'Two (2) valid government-issued IDs', '2x2 ID photo', 'Barangay Certification'],
            'process' => ['Submit Requirements', 'OSCA Verification', 'ID Encoding', 'Release'],
        ],
    ];

    $submittedDocs = [
        ['name' => 'PhilSys National ID.jpg', 'category' => 'IMG', 'type' => 'Valid ID', 'size' => '1.2 MB', 'uploaded' => 'Jul 8, 2026', 'status' => 'Approved'],
        ['name' => 'Barangay Certificate.pdf', 'category' => 'PDF', 'type' => 'Proof of Residency', 'size' => '480 KB', 'uploaded' => 'Jul 3, 2026', 'status' => 'Approved'],
        ['name' => '2x2 ID Photo.jpg', 'category' => 'IMG', 'type' => 'Photo', 'size' => '220 KB', 'uploaded' => 'Jun 14, 2026', 'status' => 'Approved'],
        ['name' => 'DTI Registration.pdf', 'category' => 'PDF', 'type' => 'Business Document', 'size' => '640 KB', 'uploaded' => 'May 12, 2026', 'status' => 'Pending Review'],
        ['name' => 'Affidavit of Loss.docx', 'category' => 'DOCX', 'type' => 'Supporting Document', 'size' => '96 KB', 'uploaded' => 'Apr 20, 2026', 'status' => 'Completed'],
    ];

    $availableFiles = [
        ['name' => 'TIN ID.jpg', 'category' => 'IMG'],
        ['name' => 'Marriage Certificate.pdf', 'category' => 'PDF'],
        ['name' => 'Certificate of Employment.docx', 'category' => 'DOCX'],
    ];

    $fileCardStyle = [
        'IMG' => ['ring' => 'from-brand-200 to-brand-50', 'icon' => 'image', 'iconColor' => 'text-brand-400', 'badge' => 'bg-brand-600 text-white'],
        'PDF' => ['ring' => 'from-rose-200 to-rose-50', 'icon' => 'file-text', 'iconColor' => 'text-rose-400', 'badge' => 'bg-rose-600 text-white'],
        'DOCX' => ['ring' => 'from-blue-200 to-blue-50', 'icon' => 'file-type-2', 'iconColor' => 'text-blue-400', 'badge' => 'bg-blue-600 text-white'],
    ];
@endphp

<x-layouts.citizen title="Dokyu" subtitle="Request and track municipal documents online." active="document-requests">
    <div x-data="{ tab: 'all' }" class="animate-fade-up">

        <x-ui.page-intro id="dokyu" icon="file-text" title="Paano gumagana ang Dokyu" titleEn="How Dokyu works">
            <span x-show="tab === 'all'"><x-ui.t fil="Pumili ng dokumento, kumpirmahin ang iyong detalye, bayaran ang bayarin kung meron (marami, tulad ng Certificate of Indigency, ay libre), pagkatapos i-submit. Nagsisimula sa" en="Pick a document, confirm your details, pay the fee if there is one (many, like the Certificate of Indigency, are free), then submit. Reference numbers start with" /> <span class="font-mono">DR-</span> <x-ui.t fil="ang reference number." en="." /></span>
            <span x-show="tab === 'active'" x-cloak><x-ui.t fil="Ang mga kahilingang ito ay nire-review, vine-verify, o inihahanda pa. Aabisuhan ka namin kapag may update — hindi na kailangang sumunod pa sa opisina." en="These requests are still being reviewed, verified, or prepared. We'll notify you the moment there's an update — no need to follow up in person." /></span>
            <span x-show="tab === 'done'" x-cloak><x-ui.t fil="Ang mga kahilingang ito ay na-release o kumpleto na. Puwede mo pa ring balikan ang mga ito rito anumang oras kung kailangan mo ng kopya ng detalye." en="These requests have been released or fully completed. You can revisit any of them here anytime you need a copy of the details." /></span>
        </x-ui.page-intro>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card label="Kabuuang Kahilingan" labelEn="Total Requests" value="6" icon="file-text" color="brand" :delay="0" />
            <x-ui.stat-card label="Isinasagawa" labelEn="In Progress" value="1" icon="loader" color="orange" :delay="40" />
            <x-ui.stat-card label="Handa nang Kunin" labelEn="Ready for Release" value="1" icon="package-check" color="green" :delay="80" />
            <x-ui.stat-card label="Kumpleto" labelEn="Completed" value="3" icon="check-circle-2" color="purple" :delay="120" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit overflow-x-auto">
                @foreach(['all' => ['Lahat', 'All'], 'active' => ['Isinasagawa', 'In Progress'], 'done' => ['Kumpleto', 'Completed']] as $key => [$labelFil, $labelEn])
                    <button
                        @click="tab = '{{ $key }}'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap"
                        :class="tab === '{{ $key }}' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"
                    ><x-ui.t :fil="$labelFil" :en="$labelEn" /></button>
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                <div x-data="{ open: false, fileTab: 'all', showAdd: false, availableFiles: @js($availableFiles) }" class="contents">
                    <x-ui.button @click="open = true" variant="secondary" icon="folder-open" size="sm"><x-ui.t fil="Aking mga Isinumiteng Dokyu" en="My Submitted Dokyus" /></x-ui.button>
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
                                <div x-show="fileTab === 'all' || fileTab === '{{ $doc['category'] }}'" class="rounded-xl border border-slate-100 overflow-hidden hover:shadow-card-hover transition-all duration-300 group">
                                    <div class="relative aspect-square bg-gradient-to-br {{ $style['ring'] }} flex items-center justify-center">
                                        <i data-lucide="{{ $style['icon'] }}" class="w-9 h-9 {{ $style['iconColor'] }} transition-transform duration-300 group-hover:scale-110"></i>
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
                        paymentMethod: null,
                        alreadyPaid: false,
                        receipt: null,
                        showReceiptPicker: false,
                        docs: @js($documentTypes),
                        files: @js($submittedDocs),
                        paymentMethods: [
                            { key: 'gcash', label: 'GCash', hint: 'Send to 0917-000-0000', hintFil: 'Ipadala sa 0917-000-0000', icon: 'smartphone' },
                            { key: 'paymaya', label: 'PayMaya', hint: 'Send to 0918-000-0000', hintFil: 'Ipadala sa 0918-000-0000', icon: 'wallet' },
                            { key: 'center', label: 'Payment in Center', labelFil: 'Bayad sa Opisina', hint: 'Pay at the Treasurer\'s Office', hintFil: 'Magbayad sa Treasurer\'s Office', icon: 'landmark' },
                        ],
                        stepLabels: {
                            fil: ['Pumili ng Dokumento', 'Detalye ng Aplikasyon', 'Bayad', 'I-review at I-submit'],
                            en: ['Choose Document', 'Application Details', 'Payment', 'Review & Submit'],
                        },
                        reset() { step = 1; selectedDoc = null; attachments = {}; showFilePicker = null; submitted = false; paymentMethod = null; alreadyPaid = false; receipt = null; showReceiptPicker = false; },
                    }"
                    class="contents"
                >
                    <x-ui.button @click="open = true" icon="file-plus-2" size="sm"><x-ui.t fil="Bagong Kahilingan sa Dokyu" en="New Dokyu Request" /></x-ui.button>
                    <x-ui.modal title="Bagong Kahilingan sa Dokyu" titleEn="New Dokyu Request" maxWidth="xl">
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
                        <div class="flex items-center mb-6">
                            <template x-for="(label, i) in ($store.lang.current === 'en' ? stepLabels.en : stepLabels.fil)" :key="i">
                                <div class="flex-1 flex items-center">
                                    <div class="flex flex-col items-center gap-1.5 text-center">
                                        <span
                                            class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold shrink-0"
                                            :class="step > i + 1 ? 'bg-emerald-500 text-white' : (step === i + 1 ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400')"
                                        >
                                            <i data-lucide="check" class="w-3 h-3" x-show="step > i + 1" x-cloak></i>
                                            <span x-show="step <= i + 1" x-text="i + 1"></span>
                                        </span>
                                        <span class="text-[10px] max-w-[80px] leading-tight" :class="step === i + 1 ? 'text-navy-900 font-medium' : 'text-slate-400'" x-text="label"></span>
                                    </div>
                                    <div class="flex-1 h-0.5 -mt-4" :class="step > i + 1 ? 'bg-emerald-400' : 'bg-slate-100'" x-show="i < 3"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Step 1: choose document -->
                        <div x-show="step === 1">
                            <p class="text-sm text-slate-500 mb-4"><x-ui.t fil="Piliin ang dokumentong gusto mong hilingin." en="Select the document you'd like to request." /></p>
                            <div class="space-y-2">
                                <template x-for="(doc, i) in docs" :key="i">
                                    <label
                                        class="flex items-center justify-between gap-3 rounded-xl border p-3.5 cursor-pointer transition-all duration-200"
                                        :class="selectedDoc === i ? 'border-brand-400 bg-brand-50/60 ring-4 ring-brand-100' : 'border-slate-200 hover:border-slate-300'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <input type="radio" name="doc_type" class="text-brand-600 focus:ring-brand-200" :checked="selectedDoc === i" @change="selectedDoc = i; attachments = {}">
                                            <div>
                                                <p class="text-sm font-medium text-slate-700" x-text="doc.name"></p>
                                                <p class="text-xs text-slate-400" x-text="doc.office + ' · ' + doc.days"></p>
                                            </div>
                                        </div>
                                        <span class="text-xs font-semibold text-navy-900 shrink-0 text-right" x-text="doc.fee"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- Step 2: application details + requirements -->
                        <div x-show="step === 2" x-cloak>
                            <p class="text-sm font-semibold text-navy-900 mb-1"><x-ui.t fil="Impormasyon ng Aplikante" en="Applicant Information" /></p>
                            <p class="text-xs text-slate-400 mb-3"><x-ui.t fil="Naka-prefill mula sa iyong resident profile — i-edit kung kailangan." en="Pre-filled from your resident profile — edit if needed." /></p>
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <x-ui.input name="req_first_name" label="First Name" value="Maria Fe" />
                                <x-ui.input name="req_last_name" label="Last Name" value="Bacaltos" />
                            </div>
                            <div class="mb-3">
                                <x-ui.input name="req_address" label="Complete Address" icon="map-pin" value="Purok 3, Brgy. Poblacion, Esperanza, Masbate" />
                            </div>
                            <div class="grid grid-cols-2 gap-3 mb-5">
                                <x-ui.input name="req_mobile" label="Mobile Number" icon="phone" value="0917 342 8810" />
                                <x-ui.input name="req_email" type="email" label="Email Address" icon="mail" value="maria.bacaltos@email.com" />
                            </div>

                            <div class="mb-5">
                                <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Layunin" en="Purpose" /></label>
                                <textarea rows="2" placeholder="e.g. Employment requirement" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200"></textarea>
                            </div>

                            <p class="text-sm font-semibold text-navy-900 mb-1"><x-ui.t fil="Mga Requirement" en="Requirements" /></p>
                            <p class="text-xs text-slate-400 mb-3"><x-ui.t fil="Ikabit mula sa mga file na na-upload mo na, o mag-upload ng bago." en="Attach from your already-uploaded files, or upload a new one." /></p>
                            <div class="space-y-2">
                                <template x-for="(req, i) in docs[selectedDoc]?.requirements || []" :key="i">
                                    <div class="rounded-xl border border-slate-200 p-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-xs text-slate-600 flex-1" x-text="req"></p>
                                            <button
                                                type="button" x-show="!attachments[i]"
                                                @click="showFilePicker = (showFilePicker === i ? null : i)"
                                                class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap"
                                            ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                                            <div class="flex items-center gap-1.5 shrink-0" x-show="attachments[i]">
                                                <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap">
                                                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i><span x-text="attachments[i]"></span>
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
                                <div>
                                    <!-- Invoice -->
                                    <div class="rounded-xl border border-slate-200 overflow-hidden mb-5">
                                        <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b border-slate-100">
                                            <p class="text-xs font-semibold text-navy-900 uppercase tracking-wide"><x-ui.t fil="Buod ng Invoice" en="Invoice Summary" /></p>
                                            <span class="text-[10px] font-mono text-slate-400">Municipality of Esperanza</span>
                                        </div>
                                        <div class="px-4 py-3.5 text-sm">
                                            <div class="flex items-center justify-between py-1.5">
                                                <span class="text-slate-500"><x-ui.t fil="Sisingilin" en="Bill To" /></span>
                                                <span class="text-slate-700 font-medium">Maria Fe Bacaltos</span>
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

                                    <template x-if="!alreadyPaid">
                                        <div>
                                            <p class="text-sm font-semibold text-navy-900 mb-3"><x-ui.t fil="Piliin ang Paraan ng Pagbabayad" en="Choose Payment Method" /></p>
                                            <div class="space-y-2 mb-4">
                                                <template x-for="m in paymentMethods" :key="m.key">
                                                    <label
                                                        class="flex items-center gap-3 rounded-xl border p-3 cursor-pointer transition-all duration-200"
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
                                                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i><span x-text="receipt"></span>
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
                            </template>
                        </div>

                        <!-- Step 4: review & submit -->
                        <div x-show="step === 4" x-cloak>
                            <p class="text-sm font-semibold text-navy-900 mb-3"><x-ui.t fil="I-review ang Iyong Kahilingan" en="Review Your Request" /></p>
                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 text-sm text-slate-600 space-y-1.5 mb-5">
                                <p><span class="text-slate-400"><x-ui.t fil="Dokumento:" en="Document:" /></span> <span x-text="docs[selectedDoc]?.name || ''"></span></p>
                                <p><span class="text-slate-400"><x-ui.t fil="Opisinang Magpoproseso:" en="Processing Office:" /></span> <span x-text="docs[selectedDoc]?.office || ''"></span></p>
                                <p><span class="text-slate-400"><x-ui.t fil="Bayarin:" en="Fee:" /></span> <span x-text="docs[selectedDoc]?.fee || ''"></span></p>
                                <p><span class="text-slate-400"><x-ui.t fil="Aplikante:" en="Applicant:" /></span> Maria Fe Bacaltos</p>
                                <p>
                                    <span class="text-slate-400"><x-ui.t fil="Bayad:" en="Payment:" /></span>
                                    <template x-if="docs[selectedDoc]?.fee === 'Free'"><span><x-ui.t fil="Hindi aplikable" en="Not applicable" /></span></template>
                                    <template x-if="docs[selectedDoc]?.fee !== 'Free' && alreadyPaid"><span x-text="$store.lang.current === 'en' ? ('Paid — receipt attached (' + receipt + ')') : ('Nabayaran na — nakakabit ang resibo (' + receipt + ')')"></span></template>
                                    <template x-if="docs[selectedDoc]?.fee !== 'Free' && !alreadyPaid"><span x-text="paymentMethod ? (($store.lang.current === 'en' ? 'To pay via ' : 'Babayaran sa pamamagitan ng ') + (paymentMethods.find(m => m.key === paymentMethod)?.label || '')) : ($store.lang.current === 'en' ? 'Not selected' : 'Wala pang napili')"></span></template>
                                </p>
                            </div>

                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2"><x-ui.t fil="Mga Nakakabit na Requirement" en="Attached Requirements" /></p>
                            <ul class="space-y-1.5 mb-5">
                                <template x-for="(req, i) in docs[selectedDoc]?.requirements || []" :key="i">
                                    <li class="flex items-center gap-2 text-xs">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 shrink-0" :class="attachments[i] ? 'text-emerald-500' : 'text-slate-300'"></i>
                                        <span :class="attachments[i] ? 'text-slate-600' : 'text-slate-400'" x-text="req"></span>
                                        <span class="text-slate-300" x-show="attachments[i]">— <span x-text="attachments[i]"></span></span>
                                    </li>
                                </template>
                            </ul>

                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3"><x-ui.t fil="Ano ang Susunod" en="What Happens Next" /></p>
                            <div class="flex items-center">
                                <template x-for="(step_label, i) in docs[selectedDoc]?.process || []" :key="i">
                                    <div class="flex-1 flex items-center">
                                        <div class="flex flex-col items-center gap-1 text-center">
                                            <span class="w-5 h-5 rounded-full bg-brand-600 text-white flex items-center justify-center text-[10px] font-semibold shrink-0" x-text="i + 1"></span>
                                            <span class="text-[9px] text-slate-500 max-w-[64px] leading-tight" x-text="step_label"></span>
                                        </div>
                                        <div class="flex-1 h-0.5 bg-brand-200 -mt-4" x-show="i < (docs[selectedDoc]?.process || []).length - 1"></div>
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
                                ::disabled="(step === 1 && selectedDoc === null) || (step === 3 && docs[selectedDoc]?.fee !== 'Free' && !alreadyPaid && !paymentMethod) || (step === 3 && alreadyPaid && !receipt)"
                                @click="
                                    if (step < 4) { step++ }
                                    else {
                                        submitted = true;
                                        setTimeout(() => { open = false; reset(); $dispatch('toast', { message: $store.lang.current === 'en' ? 'Document request submitted — this is a frontend preview, no data is saved.' : 'Naisumite ang kahilingan sa dokumento — frontend preview lang ito, walang naiimbak na datos.', variant: 'success' }) }, 1400);
                                    }
                                "
                            >
                                <span x-text="$store.lang.current === 'en' ? (step < 4 ? 'Continue' : 'Submit Request') : (step < 4 ? 'Magpatuloy' : 'Isumite ang Kahilingan')"></span>
                            </x-ui.button>
                        </x-slot:footer>
                    </x-ui.modal>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 mb-2">
            <h3 class="text-sm font-semibold text-navy-900"><x-ui.t fil="Aking mga Kahilingan" en="My Requests" /></h3>
            <x-citizen.status-guide />
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
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($requests as $req)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $req['ref'] }}</td>
                        <td class="px-4 py-3 text-slate-700 font-medium">{{ $req['type'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden lg:table-cell">{{ $req['office'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $req['purpose'] }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ $req['submitted'] }}</td>
                        <td class="px-4 py-3"><x-ui.badge :status="$req['status']" /></td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    </div>
</x-layouts.citizen>
