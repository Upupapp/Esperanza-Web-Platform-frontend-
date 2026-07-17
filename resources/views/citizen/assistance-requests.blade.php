@php
    $requests = [
        ['ref' => 'AR-2026-0142', 'type' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'purpose' => 'Hospital bill support — Masbate Provincial Hospital', 'submitted' => 'Jun 28, 2026', 'status' => 'Approved'],
        ['ref' => 'AR-2026-0098', 'type' => 'Educational Assistance', 'office' => 'MSWDO', 'purpose' => 'Tuition support for 2 dependents, SY 2026-2027', 'submitted' => 'May 20, 2026', 'status' => 'Released'],
        ['ref' => 'AR-2026-0061', 'type' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'purpose' => 'Livelihood capital support', 'submitted' => 'Apr 2, 2026', 'status' => 'Completed'],
        ['ref' => 'AR-2026-0037', 'type' => 'Social Pension (Indigent Senior Citizen)', 'office' => 'OSCA', 'purpose' => 'Quarterly social pension enrollment', 'submitted' => 'Feb 10, 2026', 'status' => 'Completed'],
    ];

    $assistanceTypes = [
        [
            'name' => 'Medical Assistance (AICS)', 'office' => 'MSWDO', 'amount' => '₱1,000 – ₱150,000 per case', 'fee' => 'Free',
            'desc' => 'Hospital bills, medicines, and treatment support under DSWD\'s Assistance to Individuals in Crisis Situation.',
            'requirements' => ['One (1) valid government-issued ID', "Medical Abstract or Doctor's prescription", 'Hospital bill or Statement of Account', 'Barangay Certificate of Indigency'],
            'process' => ['Submit Request', 'MSWDO Assessment', 'Approval', 'Cash / Guarantee Letter Release'],
        ],
        [
            'name' => 'Burial Assistance (AICS)', 'office' => 'MSWDO', 'amount' => 'Up to ₱5,000', 'fee' => 'Free',
            'desc' => 'Financial aid for funeral and burial expenses. Must be filed within 15 days from date of death.',
            'requirements' => ['Death Certificate', 'Valid ID of claimant', 'Proof of relationship to the deceased', 'Funeral contract or bill', 'Barangay Certificate of Indigency'],
            'process' => ['Submit Request', 'Verify Death Certificate', 'Approval', 'Release'],
        ],
        [
            'name' => 'Educational Assistance', 'office' => "MSWDO / Mayor's Office", 'amount' => 'Tuition + allowance per semester', 'fee' => 'Free',
            'desc' => 'Municipal scholarship and school supplies support for indigent students in good academic standing.',
            'requirements' => ['Certificate of Enrollment', 'Report Card / Form 138 (GWA 80 and above)', 'Certificate of Indigency (family income below ₱120,000/year)', 'Barangay Residency Certificate'],
            'process' => ['Submit Requirements', 'Scholarship Committee Review', 'Approval', 'Disbursement'],
        ],
        [
            'name' => 'Financial Assistance (AICS)', 'office' => 'MSWDO', 'amount' => 'Based on social worker assessment', 'fee' => 'Free',
            'desc' => 'Cash assistance for individuals and families in crisis situations.',
            'requirements' => ['One (1) valid government-issued ID', 'Barangay Certificate of Indigency', 'Brief interview / social case study with MSWDO'],
            'process' => ['Submit Request', 'Social Case Study', 'Approval', 'Release'],
        ],
        [
            'name' => 'Food / Relief Assistance', 'office' => 'MSWDO / MDRRMO', 'amount' => 'Relief goods package', 'fee' => 'Free',
            'desc' => 'Food packs and relief goods for families affected by calamities or emergencies.',
            'requirements' => ['Barangay Certification', 'One (1) valid government-issued ID'],
            'process' => ['Submit Request', 'Barangay Verification', 'Approval', 'Distribution'],
        ],
        [
            'name' => 'Social Pension (Indigent Senior Citizen)', 'office' => 'OSCA', 'amount' => '₱1,000/month (₱3,000 per quarter)', 'fee' => '₱100.00',
            'desc' => 'Monthly stipend for indigent senior citizens 60 and above with no pension or regular income.',
            'requirements' => ['Senior Citizen ID', 'Affidavit of no pension, income, or family support (notarized)', 'Barangay Certification'],
            'process' => ['Submit Requirements', 'OSCA / DSWD Verification', 'Enrollment', 'Quarterly Release'],
        ],
        [
            'name' => 'Solo Parent Cash Assistance', 'office' => 'MSWDO', 'amount' => 'Cash grant + goods, per assessment', 'fee' => 'Free',
            'desc' => 'Support for solo parents under the Expanded Solo Parents Welfare Act (RA 11861).',
            'requirements' => ['Solo Parent ID', 'PSA Birth Certificate(s) of children', 'Barangay Certification'],
            'process' => ['Submit Requirements', 'MSWDO Assessment', 'Approval', 'Release'],
        ],
    ];

    $submittedDocs = [
        ['name' => 'PhilSys National ID.jpg', 'category' => 'IMG'],
        ['name' => 'Barangay Certificate of Indigency.pdf', 'category' => 'PDF'],
        ['name' => 'Medical Abstract.pdf', 'category' => 'PDF'],
        ['name' => 'Hospital Bill.pdf', 'category' => 'PDF'],
        ['name' => 'Senior Citizen ID.jpg', 'category' => 'IMG'],
    ];
@endphp

<x-layouts.citizen title="Tulong" subtitle="Submit and follow up assistance program requests." active="assistance-requests">
    <div x-data="{ open: false }" class="animate-fade-up">

        <x-ui.page-intro id="tulong" icon="hand-heart" title="Paano gumagana ang Tulong" titleEn="How Tulong works" color="purple">
            <x-ui.t
                fil="Piliin ang programang tulong o ayuda na kailangan mo, sabihin sa amin ang iyong sitwasyon, ikabit ang mga requirement, pagkatapos i-submit. Halos lahat ng programa rito ay libreng i-apply — nagsisimula sa AR- ang reference number."
                en="Choose the assistance or ayuda program you need, tell us about your situation, attach your requirements, then submit. Almost every program here is free to apply for — reference numbers start with AR-."
            />
        </x-ui.page-intro>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
            <x-ui.stat-card label="Kabuuang Kahilingan" labelEn="Total Requests" value="4" icon="hand-heart" color="purple" :delay="0" />
            <x-ui.stat-card label="Aprubado" labelEn="Approved" value="1" icon="check-circle-2" color="green" :delay="40" />
            <x-ui.stat-card label="Na-release" labelEn="Released" value="1" icon="package-check" color="brand" :delay="80" />
            <x-ui.stat-card label="Kumpleto" labelEn="Completed" value="2" icon="badge-check" color="gold" :delay="120" />
            <x-ui.stat-card label="Natanggap na Ayuda" labelEn="Ayuda Received" value="₱42,500" icon="hand-coins" color="green" sublabel="Kabuuang natanggap na tulong" sublabelEn="Total aid received" :delay="160" />
        </div>

        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-navy-900"><x-ui.t fil="Aking mga Kahilingan" en="My Requests" /></h3>
                <x-citizen.status-guide />
            </div>

            <div
                x-data="{
                    open: false,
                    step: 1,
                    selectedType: null,
                    attachments: {},
                    showFilePicker: null,
                    submitted: false,
                    paymentMethod: null,
                    alreadyPaid: false,
                    receipt: null,
                    showReceiptPicker: false,
                    types: @js($assistanceTypes),
                    files: @js($submittedDocs),
                    paymentMethods: [
                        { key: 'gcash', label: 'GCash', hint: 'Send to 0917-000-0000', hintFil: 'Ipadala sa 0917-000-0000', icon: 'smartphone' },
                        { key: 'paymaya', label: 'PayMaya', hint: 'Send to 0918-000-0000', hintFil: 'Ipadala sa 0918-000-0000', icon: 'wallet' },
                        { key: 'center', label: 'Payment in Center', labelFil: 'Bayad sa Opisina', hint: 'Pay at the MSWDO / OSCA office', hintFil: 'Magbayad sa opisina ng MSWDO / OSCA', icon: 'landmark' },
                    ],
                    stepLabels: {
                        fil: ['Pumili ng Tulong', 'Detalye ng Aplikasyon', 'Bayad', 'I-review at I-submit'],
                        en: ['Choose Assistance', 'Application Details', 'Payment', 'Review & Submit'],
                    },
                    reset() { step = 1; selectedType = null; attachments = {}; showFilePicker = null; submitted = false; paymentMethod = null; alreadyPaid = false; receipt = null; showReceiptPicker = false; },
                }"
                class="contents"
            >
                <x-ui.button @click="open = true" icon="hand-heart" size="sm"><x-ui.t fil="Bagong Kahilingan sa Tulong" en="New Tulong Request" /></x-ui.button>
                <x-ui.modal title="Bagong Kahilingan sa Tulong" titleEn="New Tulong Request" maxWidth="xl">
                    <!-- Success state -->
                    <div x-show="submitted" x-cloak x-transition.opacity class="flex flex-col items-center justify-center text-center py-10">
                        <div class="relative flex items-center justify-center mb-4 w-16 h-16">
                            <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse"></span>
                            <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse" style="animation-delay: 0.35s;"></span>
                            <span class="relative w-14 h-14 rounded-full bg-emerald-500 text-white flex items-center justify-center animate-check-pop">
                                <i data-lucide="check" class="w-7 h-7"></i>
                            </span>
                        </div>
                        <p class="text-sm font-semibold text-navy-900"><x-ui.t fil="Naisumite ang Kahilingan!" en="Request Submitted!" /></p>
                        <p class="text-xs text-slate-400 mt-1 max-w-[220px]"><x-ui.t fil="Aabisuhan ka namin kapag na-review na ang iyong kahilingan sa Tulong." en="We'll notify you once your Tulong request is reviewed." /></p>
                    </div>

                    <div x-show="!submitted">
                    <!-- Step indicator -->
                    <div class="flex items-center mb-6">
                        <template x-for="(label, i) in ($store.lang.current === 'en' ? stepLabels.en : stepLabels.fil)" :key="i">
                            <div class="flex-1 flex items-center">
                                <div class="flex flex-col items-center gap-1.5 text-center">
                                    <span
                                        class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold shrink-0"
                                        :class="step > i + 1 ? 'bg-emerald-500 text-white' : (step === i + 1 ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-400')"
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

                    <!-- Step 1: choose assistance type -->
                    <div x-show="step === 1">
                        <p class="text-sm text-slate-500 mb-4"><x-ui.t fil="Piliin ang uri ng tulong na kailangan mo." en="Choose the type of assistance you need." /></p>
                        <div class="space-y-2">
                            <template x-for="(type, i) in types" :key="i">
                                <label
                                    class="flex items-center justify-between gap-3 rounded-xl border p-3.5 cursor-pointer transition-all duration-200"
                                    :class="selectedType === i ? 'border-purple-400 bg-purple-50/60 ring-4 ring-purple-100' : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="assist_type" class="text-purple-600 focus:ring-purple-200" :checked="selectedType === i" @change="selectedType = i; attachments = {}">
                                        <div>
                                            <p class="text-sm font-medium text-slate-700" x-text="type.name"></p>
                                            <p class="text-xs text-slate-400" x-text="type.office"></p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold text-navy-900 shrink-0 text-right max-w-[35%]" x-text="type.amount"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Step 2: application details + requirements -->
                    <div x-show="step === 2" x-cloak>
                        <p class="text-sm font-semibold text-navy-900 mb-1"><x-ui.t fil="Impormasyon ng Aplikante" en="Applicant Information" /></p>
                        <p class="text-xs text-slate-400 mb-3"><x-ui.t fil="Naka-prefill mula sa iyong resident profile — i-edit kung kailangan." en="Pre-filled from your resident profile — edit if needed." /></p>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <x-ui.input name="assist_first_name" label="First Name" value="Maria Fe" />
                            <x-ui.input name="assist_last_name" label="Last Name" value="Bacaltos" />
                        </div>
                        <div class="mb-3">
                            <x-ui.input name="assist_address" label="Complete Address" icon="map-pin" value="Purok 3, Brgy. Poblacion, Esperanza, Masbate" />
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <x-ui.input name="assist_mobile" label="Mobile Number" icon="phone" value="0917 342 8810" />
                            <x-ui.input name="assist_email" type="email" label="Email Address" icon="mail" value="maria.bacaltos@email.com" />
                        </div>

                        <div class="mb-5">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Detalye ng Kahilingan" en="Details of Request" /></label>
                            <textarea rows="2" placeholder="Briefly describe your situation and needs" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200"></textarea>
                        </div>

                        <p class="text-sm font-semibold text-navy-900 mb-1"><x-ui.t fil="Mga Requirement" en="Requirements" /></p>
                        <p class="text-xs text-slate-400 mb-3"><x-ui.t fil="Ikabit mula sa mga file na na-upload mo na, o mag-upload ng bago." en="Attach from your already-uploaded files, or upload a new one." /></p>
                        <div class="space-y-2">
                            <template x-for="(req, i) in types[selectedType]?.requirements || []" :key="i">
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs text-slate-600 flex-1" x-text="req"></p>
                                        <button
                                            type="button" x-show="!attachments[i]"
                                            @click="showFilePicker = (showFilePicker === i ? null : i)"
                                            class="text-[11px] font-medium text-purple-600 hover:underline shrink-0 whitespace-nowrap"
                                        ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                                        <div class="flex items-center gap-1.5 shrink-0" x-show="attachments[i]">
                                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap">
                                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i><span x-text="attachments[i]"></span>
                                            </span>
                                            <button type="button" @click="attachments[i] = null" class="text-slate-300 hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                        </div>
                                    </div>
                                    <div x-show="showFilePicker === i" x-cloak class="mt-2.5 pt-2.5 border-t border-slate-100 space-y-1">
                                        <template x-for="file in files" :key="file.name">
                                            <button
                                                type="button"
                                                @click="attachments[i] = file.name; showFilePicker = null"
                                                class="w-full flex items-center gap-2 text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition-colors"
                                            >
                                                <span
                                                    class="w-5 h-5 rounded text-[8px] font-bold flex items-center justify-center text-white shrink-0"
                                                    :class="file.category === 'IMG' ? 'bg-brand-500' : (file.category === 'PDF' ? 'bg-rose-500' : 'bg-blue-500')"
                                                    x-text="file.category"
                                                ></span>
                                                <span class="text-xs text-slate-600 flex-1 truncate" x-text="file.name"></span>
                                            </button>
                                        </template>
                                        <button
                                            type="button"
                                            @click="attachments[i] = 'New upload.jpg'; showFilePicker = null; $dispatch('toast', { message: 'File upload is a frontend preview — no data is saved.', variant: 'info' })"
                                            class="w-full flex items-center gap-2 text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition-colors text-purple-600"
                                        >
                                            <i data-lucide="upload" class="w-3.5 h-3.5"></i><span class="text-xs font-medium"><x-ui.t fil="Mag-upload ng bagong file" en="Upload a new file" /></span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Step 3: payment -->
                    <div x-show="step === 3" x-cloak>
                        <template x-if="types[selectedType]?.fee === 'Free'">
                            <div class="flex flex-col items-center text-center py-8">
                                <span class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mb-3">
                                    <i data-lucide="badge-check" class="w-6 h-6"></i>
                                </span>
                                <p class="text-sm font-semibold text-navy-900"><x-ui.t fil="Walang Kailangang Bayaran" en="No Payment Required" /></p>
                                <p class="text-xs text-slate-400 mt-1 max-w-[280px]" x-text="types[selectedType]?.name + ($store.lang.current === 'en' ? ' has no application fee under municipal ordinance.' : ' ay walang bayarin sa pag-apply ayon sa municipal ordinance.')"></p>
                            </div>
                        </template>

                        <template x-if="types[selectedType]?.fee !== 'Free'">
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
                                            <span class="text-slate-700 font-medium text-right" x-text="$store.lang.current === 'en' ? 'Documentary / Notarization Fee' : 'Bayarin sa Dokumento / Notaryo'"></span>
                                        </div>
                                        <div class="flex items-center justify-between py-1.5">
                                            <span class="text-slate-500"><x-ui.t fil="Kaugnay na Aplikasyon" en="Related Application" /></span>
                                            <span class="text-slate-700 font-medium text-right" x-text="types[selectedType]?.name"></span>
                                        </div>
                                        <div class="flex items-center justify-between py-1.5">
                                            <span class="text-slate-500"><x-ui.t fil="Kokolektang Opisina" en="Collecting Office" /></span>
                                            <span class="text-slate-700 font-medium" x-text="types[selectedType]?.office"></span>
                                        </div>
                                        <div class="border-t border-dashed border-slate-200 my-2"></div>
                                        <div class="flex items-center justify-between py-1">
                                            <span class="text-sm font-semibold text-navy-900 inline-flex items-center gap-1">
                                                <x-ui.t fil="Kabuuang Babayaran" en="Total Due" />
                                                <x-ui.tooltip text="This documentary fee is separate from your assistance amount — it won't be deducted from the aid you receive.">
                                                    <i data-lucide="info" class="w-3 h-3 text-slate-300 hover:text-slate-500 cursor-help"></i>
                                                </x-ui.tooltip>
                                            </span>
                                            <span class="text-base font-bold text-purple-700" x-text="types[selectedType]?.fee"></span>
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
                                                    :class="paymentMethod === m.key ? 'border-purple-400 bg-purple-50/60 ring-4 ring-purple-100' : 'border-slate-200 hover:border-slate-300'"
                                                >
                                                    <input type="radio" name="assist_payment_method" class="text-purple-600 focus:ring-purple-200" :checked="paymentMethod === m.key" @change="paymentMethod = m.key">
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
                                        <button type="button" @click="alreadyPaid = true" class="text-xs font-medium text-purple-600 hover:underline"><x-ui.t fil="Nakabayad na ako — ikakabit ang resibo" en="I've already paid — attach my receipt instead" /></button>
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
                                                    class="text-[11px] font-medium text-purple-600 hover:underline shrink-0 whitespace-nowrap"
                                                ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                                                <div class="flex items-center gap-1.5 shrink-0" x-show="receipt">
                                                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap">
                                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i><span x-text="receipt"></span>
                                                    </span>
                                                    <button type="button" @click="receipt = null" class="text-slate-300 hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                                </div>
                                            </div>
                                            <div x-show="showReceiptPicker" x-cloak class="mt-2.5 pt-2.5 border-t border-slate-100 space-y-1">
                                                <template x-for="file in files" :key="file.name">
                                                    <button
                                                        type="button"
                                                        @click="receipt = file.name; showReceiptPicker = false"
                                                        class="w-full flex items-center gap-2 text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition-colors"
                                                    >
                                                        <span
                                                            class="w-5 h-5 rounded text-[8px] font-bold flex items-center justify-center text-white shrink-0"
                                                            :class="file.category === 'IMG' ? 'bg-brand-500' : (file.category === 'PDF' ? 'bg-rose-500' : 'bg-blue-500')"
                                                            x-text="file.category"
                                                        ></span>
                                                        <span class="text-xs text-slate-600 flex-1 truncate" x-text="file.name"></span>
                                                    </button>
                                                </template>
                                                <button
                                                    type="button"
                                                    @click="receipt = 'Receipt Scan.jpg'; showReceiptPicker = false; $dispatch('toast', { message: 'File upload is a frontend preview — no data is saved.', variant: 'info' })"
                                                    class="w-full flex items-center gap-2 text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition-colors text-purple-600"
                                                >
                                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i><span class="text-xs font-medium"><x-ui.t fil="Mag-upload ng bagong file" en="Upload a new file" /></span>
                                                </button>
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
                            <p><span class="text-slate-400"><x-ui.t fil="Uri ng Tulong:" en="Assistance Type:" /></span> <span x-text="types[selectedType]?.name || ''"></span></p>
                            <p><span class="text-slate-400"><x-ui.t fil="Nangangasiwang Opisina:" en="Handling Office:" /></span> <span x-text="types[selectedType]?.office || ''"></span></p>
                            <p><span class="text-slate-400"><x-ui.t fil="Tinatayang Halaga:" en="Estimated Amount:" /></span> <span x-text="types[selectedType]?.amount || ''"></span></p>
                            <p><span class="text-slate-400"><x-ui.t fil="Aplikante:" en="Applicant:" /></span> Maria Fe Bacaltos</p>
                            <p>
                                <span class="text-slate-400"><x-ui.t fil="Bayarin sa Dokumento:" en="Documentary Fee:" /></span>
                                <template x-if="types[selectedType]?.fee === 'Free'"><span><x-ui.t fil="Hindi aplikable" en="Not applicable" /></span></template>
                                <template x-if="types[selectedType]?.fee !== 'Free' && alreadyPaid"><span x-text="$store.lang.current === 'en' ? ('Paid — receipt attached (' + receipt + ')') : ('Nabayaran na — nakakabit ang resibo (' + receipt + ')')"></span></template>
                                <template x-if="types[selectedType]?.fee !== 'Free' && !alreadyPaid"><span x-text="paymentMethod ? (($store.lang.current === 'en' ? 'To pay via ' : 'Babayaran sa pamamagitan ng ') + (paymentMethods.find(m => m.key === paymentMethod)?.label || '')) : ($store.lang.current === 'en' ? 'Not selected' : 'Wala pang napili')"></span></template>
                            </p>
                        </div>

                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2"><x-ui.t fil="Mga Nakakabit na Requirement" en="Attached Requirements" /></p>
                        <ul class="space-y-1.5 mb-5">
                            <template x-for="(req, i) in types[selectedType]?.requirements || []" :key="i">
                                <li class="flex items-center gap-2 text-xs">
                                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5 shrink-0" :class="attachments[i] ? 'text-emerald-500' : 'text-slate-300'"></i>
                                    <span :class="attachments[i] ? 'text-slate-600' : 'text-slate-400'" x-text="req"></span>
                                    <span class="text-slate-300" x-show="attachments[i]">— <span x-text="attachments[i]"></span></span>
                                </li>
                            </template>
                        </ul>

                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3"><x-ui.t fil="Ano ang Susunod" en="What Happens Next" /></p>
                        <div class="flex items-center">
                            <template x-for="(step_label, i) in types[selectedType]?.process || []" :key="i">
                                <div class="flex-1 flex items-center">
                                    <div class="flex flex-col items-center gap-1 text-center">
                                        <span class="w-5 h-5 rounded-full bg-purple-600 text-white flex items-center justify-center text-[10px] font-semibold shrink-0" x-text="i + 1"></span>
                                        <span class="text-[9px] text-slate-500 max-w-[64px] leading-tight" x-text="step_label"></span>
                                    </div>
                                    <div class="flex-1 h-0.5 bg-purple-200 -mt-4" x-show="i < (types[selectedType]?.process || []).length - 1"></div>
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
                            ::disabled="(step === 1 && selectedType === null) || (step === 3 && types[selectedType]?.fee !== 'Free' && !alreadyPaid && !paymentMethod) || (step === 3 && alreadyPaid && !receipt)"
                            @click="
                                if (step < 4) { step++ }
                                else {
                                    submitted = true;
                                    setTimeout(() => { open = false; reset(); $dispatch('toast', { message: $store.lang.current === 'en' ? 'Assistance request submitted — this is a frontend preview, no data is saved.' : 'Naisumite ang kahilingan sa tulong — frontend preview lang ito, walang naiimbak na datos.', variant: 'success' }) }, 1400);
                                }
                            "
                        >
                            <span x-text="$store.lang.current === 'en' ? (step < 4 ? 'Continue' : 'Submit Request') : (step < 4 ? 'Magpatuloy' : 'Isumite ang Kahilingan')"></span>
                        </x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
            </div>
        </div>

        <x-ui.table>
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400"><x-ui.t fil="Reference" en="Reference" /></th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400"><x-ui.t fil="Uri ng Tulong" en="Assistance Type" /></th>
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
