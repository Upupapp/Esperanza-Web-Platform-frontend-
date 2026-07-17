@php
    $household = [
        ['name' => 'Ramon Bacaltos', 'relation' => 'Spouse', 'age' => 41],
        ['name' => 'Josiah Bacaltos', 'relation' => 'Son', 'age' => 16],
        ['name' => 'Andrea Bacaltos', 'relation' => 'Daughter', 'age' => 12],
    ];

    $documents = [
        ['label' => 'Philippine National ID (PhilSys)', 'status' => 'Approved', 'icon' => 'badge-check'],
        ['label' => 'Proof of Residency — Barangay Certificate', 'status' => 'Approved', 'icon' => 'file-check-2'],
        ['label' => "Voter's ID", 'status' => 'Pending Review', 'icon' => 'file-clock'],
    ];

    $idTypes = ["Voter's ID", "Driver's License", 'Passport', 'PRC ID', 'Senior Citizen ID', 'PWD ID', 'Postal ID', 'Other Valid ID'];

    $availableFiles = [
        ['name' => "Voter's ID Scan.jpg", 'category' => 'IMG'],
        ["name" => "Driver's License Scan.jpg", 'category' => 'IMG'],
        ['name' => 'Passport Bio Page.pdf', 'category' => 'PDF'],
    ];
@endphp

<x-layouts.citizen title="My Profile" subtitle="Manage your resident information and account details." active="profile">
    <div
        x-data="{
            open: false,
            submitted: false,
            idType: '',
            idNumber: '',
            attachment: null,
            showFilePicker: false,
            verifiedIndex: null,
            files: @js($availableFiles),
            idTypes: @js($idTypes),
            reset() { open = false; submitted = false; idType = ''; idNumber = ''; attachment = null; showFilePicker = false; },
        }"
        class="animate-fade-up"
    >

        <!-- Profile header -->
        <x-ui.card class="mb-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                <x-ui.avatar name="Maria Fe Bacaltos" size="lg" />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-navy-900">Maria Fe Bacaltos</h2>
                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-full px-2 py-0.5">
                            <i data-lucide="badge-check" class="w-3 h-3"></i> <x-ui.t fil="Verified na Residente" en="Verified Resident" />
                        </span>
                        <button
                            @click="open = true"
                            class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 bg-brand-50 border border-brand-100 rounded-full px-2 py-0.5 hover:bg-brand-100 transition-colors"
                        ><i data-lucide="shield-plus" class="w-3 h-3"></i> <x-ui.t fil="Mag-verify ng Karagdagang ID" en="Verify Additional ID" /></button>
                    </div>
                    <p class="text-sm text-slate-400 mt-1"><x-ui.t fil="Resident ID" en="Resident ID" />: ESP-RES-2024-08841 · Brgy. Poblacion · <x-ui.t fil="Residente mula 2024" en="Resident since 2024" /></p>
                </div>
                <x-ui.button variant="secondary" icon="pencil" size="sm"><x-ui.t fil="I-edit ang Profile" en="Edit Profile" /></x-ui.button>
            </div>
        </x-ui.card>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Personal information -->
            <x-ui.card class="lg:col-span-2">
                <h3 class="text-sm font-semibold text-navy-900 mb-4"><x-ui.t fil="Personal na Impormasyon" en="Personal Information" /></h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-xs text-slate-400 mb-1"><x-ui.t fil="Buong Pangalan" en="Full Name" /></dt>
                        <dd class="text-slate-700 font-medium">Maria Fe Cabaltera Bacaltos</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-1"><x-ui.t fil="Petsa ng Kapanganakan" en="Date of Birth" /></dt>
                        <dd class="text-slate-700 font-medium"><x-ui.t fil="Marso 14, 1987 (39 taong gulang)" en="March 14, 1987 (39 y/o)" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-1"><x-ui.t fil="Kasarian" en="Sex" /></dt>
                        <dd class="text-slate-700 font-medium"><x-ui.t fil="Babae" en="Female" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-1"><x-ui.t fil="Katayuang Sibil" en="Civil Status" /></dt>
                        <dd class="text-slate-700 font-medium"><x-ui.t fil="Kasal" en="Married" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-1"><x-ui.t fil="Nasyonalidad" en="Nationality" /></dt>
                        <dd class="text-slate-700 font-medium"><x-ui.t fil="Pilipino" en="Filipino" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-1"><x-ui.t fil="Trabaho" en="Occupation" /></dt>
                        <dd class="text-slate-700 font-medium"><x-ui.t fil="Guro sa Pampublikong Paaralan" en="Public School Teacher" /></dd>
                    </div>
                </dl>

                <div class="border-t border-slate-100 mt-5 pt-5">
                    <h3 class="text-sm font-semibold text-navy-900 mb-4"><x-ui.t fil="Contact at Address" en="Contact & Address" /></h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-slate-400 mb-1"><x-ui.t fil="Email Address" en="Email Address" /></dt>
                            <dd class="text-slate-700 font-medium">maria.bacaltos@email.com</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-1"><x-ui.t fil="Numero ng Mobile" en="Mobile Number" /></dt>
                            <dd class="text-slate-700 font-medium">0917 342 8810</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-slate-400 mb-1"><x-ui.t fil="Kumpletong Address" en="Complete Address" /></dt>
                            <dd class="text-slate-700 font-medium">Purok 3, Brgy. Poblacion, Esperanza, Masbate</dd>
                        </div>
                    </dl>
                </div>
            </x-ui.card>

            <div class="space-y-5">
                <!-- Household -->
                <x-ui.card padded="false">
                    <div class="px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-navy-900"><x-ui.t fil="Kasapi ng Sambahayan" en="Household Members" /></h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        @foreach($household as $member)
                            <div class="flex items-center gap-3 px-4 py-2.5">
                                <x-ui.avatar :name="$member['name']" size="sm" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700 truncate">{{ $member['name'] }}</p>
                                    <p class="text-xs text-slate-400">{{ $member['relation'] }} · {{ $member['age'] }} <x-ui.t fil="taong gulang" en="y/o" /></p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <!-- Documents on file -->
                <x-ui.card padded="false">
                    <div class="px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-navy-900"><x-ui.t fil="Mga Dokumento sa File" en="Documents on File" /></h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        @foreach($documents as $doc)
                            <div class="flex items-center gap-3 px-4 py-2.5">
                                <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                                    <i data-lucide="{{ $doc['icon'] }}" class="w-4 h-4"></i>
                                </span>
                                <p class="text-sm text-slate-700 min-w-0 truncate flex-1">{{ $doc['label'] }}</p>
                                @if($doc['status'] === 'Approved')
                                    <x-ui.badge :status="$doc['status']" class="shrink-0" />
                                @else
                                    <x-ui.badge :status="$doc['status']" class="shrink-0" x-show="verifiedIndex !== {{ $loop->index }}" />
                                    <x-ui.badge status="Under Verification" class="shrink-0" x-show="verifiedIndex === {{ $loop->index }}" x-cloak />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-ui.modal title="I-verify ang Valid ID" titleEn="Verify Valid ID" maxWidth="md">
            <!-- Success state -->
            <div x-show="submitted" x-cloak x-transition.opacity class="flex flex-col items-center justify-center text-center py-10">
                <div class="relative flex items-center justify-center mb-4 w-16 h-16">
                    <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse"></span>
                    <span class="absolute inline-flex h-16 w-16 rounded-full bg-emerald-400/40 animate-ring-pulse" style="animation-delay: 0.35s;"></span>
                    <span class="relative w-14 h-14 rounded-full bg-emerald-500 text-white flex items-center justify-center animate-check-pop">
                        <i data-lucide="check" class="w-7 h-7"></i>
                    </span>
                </div>
                <p class="text-sm font-semibold text-navy-900"><x-ui.t fil="Naisumite para sa Beripikasyon!" en="Submitted for Verification!" /></p>
                <p class="text-xs text-slate-400 mt-1 max-w-[240px]"><x-ui.t fil="Susuriin ng aming staff ang iyong ID at ia-update ang iyong profile sa lalong madaling panahon." en="Our staff will review your ID and update your profile shortly." /></p>
            </div>

            <div x-show="!submitted">
                <p class="text-sm text-slate-500 mb-4"><x-ui.t fil="Magsumite ng valid ID para ma-verify ng aming staff ang iyong resident profile." en="Submit a valid ID so our staff can verify your resident profile." /></p>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Uri ng ID" en="ID Type" /></label>
                    <select x-model="idType" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                        <option value="" disabled x-text="$store.lang.current === 'en' ? 'Select ID type' : 'Pumili ng uri ng ID'"></option>
                        <template x-for="t in idTypes" :key="t">
                            <option :value="t" x-text="t"></option>
                        </template>
                    </select>
                </div>

                <div class="mb-5">
                    <x-ui.input name="valid_id_number" label="ID Number" placeholder="e.g. 1234-5678-9012" x-model="idNumber" />
                </div>

                <p class="text-sm font-semibold text-navy-900 mb-1"><x-ui.t fil="Ikabit ang Larawan ng ID" en="Attach ID Photo" /></p>
                <p class="text-xs text-slate-400 mb-3"><x-ui.t fil="Ikabit mula sa mga file na na-upload mo na, o mag-upload ng bago." en="Attach from your already-uploaded files, or upload a new one." /></p>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-600 flex-1"><x-ui.t fil="Malinaw na larawan o scan ng harap ng iyong ID" en="Clear photo or scan of the front of your ID" /></p>
                        <button
                            type="button" x-show="!attachment"
                            @click="showFilePicker = !showFilePicker"
                            class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap"
                        ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                        <div class="flex items-center gap-1.5 shrink-0" x-show="attachment">
                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i><span x-text="attachment"></span>
                            </span>
                            <button type="button" @click="attachment = null" class="text-slate-300 hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                        </div>
                    </div>
                    <div x-show="showFilePicker" x-cloak class="mt-2.5 pt-2.5 border-t border-slate-100 space-y-1">
                        <template x-for="file in files" :key="file.name">
                            <button
                                type="button"
                                @click="attachment = file.name; showFilePicker = false"
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
                            @click="attachment = 'New upload.jpg'; showFilePicker = false; $dispatch('toast', { message: 'File upload is a frontend preview — no data is saved.', variant: 'info' })"
                            class="w-full flex items-center gap-2 text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition-colors text-brand-600"
                        >
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i><span class="text-xs font-medium"><x-ui.t fil="Mag-upload ng bagong file" en="Upload a new file" /></span>
                        </button>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" x-show="!submitted" @click="reset()"><x-ui.t fil="Kanselahin" en="Cancel" /></x-ui.button>
                <x-ui.button
                    size="sm"
                    x-show="!submitted"
                    ::disabled="!idType || !attachment"
                    @click="
                        let idx = @js(collect($documents)->pluck('label')->all()).indexOf(idType);
                        verifiedIndex = idx;
                        submitted = true;
                        setTimeout(() => { reset(); $dispatch('toast', { message: $store.lang.current === 'en' ? 'Valid ID submitted for verification — this is a frontend preview, no data is saved.' : 'Naisumite ang valid ID para sa beripikasyon — frontend preview lang ito, walang naiimbak na datos.', variant: 'success' }) }, 1400);
                    "
                ><x-ui.t fil="Isumite para sa Beripikasyon" en="Submit for Verification" /></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.citizen>
