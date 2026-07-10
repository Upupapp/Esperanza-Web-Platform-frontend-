@php
    $household = [
        ['name' => 'Ramon Bacaltos', 'relation' => 'Spouse', 'age' => 41],
        ['name' => 'Josiah Bacaltos', 'relation' => 'Son', 'age' => 16],
        ['name' => 'Andrea Bacaltos', 'relation' => 'Daughter', 'age' => 12],
    ];

    $documents = [
        ['label' => 'Philippine National ID (PhilSys)', 'status' => 'Approved', 'icon' => 'badge-check'],
        ['label' => 'Proof of Residency — Barangay Certificate', 'status' => 'Approved', 'icon' => 'file-check-2'],
        ['label' => 'Voter\'s ID', 'status' => 'Pending Review', 'icon' => 'file-clock'],
    ];
@endphp

<x-layouts.citizen title="My Profile" subtitle="Manage your resident information and account details." active="profile">
    <div class="animate-fade-up">

        <!-- Profile header -->
        <x-ui.card class="mb-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                <x-ui.avatar name="Maria Fe Bacaltos" size="lg" />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-navy-900">Maria Fe Bacaltos</h2>
                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-full px-2 py-0.5">
                            <i data-lucide="badge-check" class="w-3 h-3"></i> Verified Resident
                        </span>
                    </div>
                    <p class="text-sm text-slate-400 mt-1">Resident ID: ESP-RES-2024-08841 · Brgy. Poblacion · Resident since 2024</p>
                </div>
                <x-ui.button variant="secondary" icon="pencil" size="sm">Edit Profile</x-ui.button>
            </div>
        </x-ui.card>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Personal information -->
            <x-ui.card class="lg:col-span-2">
                <h3 class="text-sm font-semibold text-navy-900 mb-4">Personal Information</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-xs text-slate-400 mb-1">Full Name</dt>
                        <dd class="text-slate-700 font-medium">Maria Fe Cabaltera Bacaltos</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-1">Date of Birth</dt>
                        <dd class="text-slate-700 font-medium">March 14, 1987 (39 y/o)</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-1">Sex</dt>
                        <dd class="text-slate-700 font-medium">Female</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-1">Civil Status</dt>
                        <dd class="text-slate-700 font-medium">Married</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-1">Nationality</dt>
                        <dd class="text-slate-700 font-medium">Filipino</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-1">Occupation</dt>
                        <dd class="text-slate-700 font-medium">Public School Teacher</dd>
                    </div>
                </dl>

                <div class="border-t border-slate-100 mt-5 pt-5">
                    <h3 class="text-sm font-semibold text-navy-900 mb-4">Contact & Address</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-slate-400 mb-1">Email Address</dt>
                            <dd class="text-slate-700 font-medium">maria.bacaltos@email.com</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-1">Mobile Number</dt>
                            <dd class="text-slate-700 font-medium">0917 342 8810</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-slate-400 mb-1">Complete Address</dt>
                            <dd class="text-slate-700 font-medium">Purok 3, Brgy. Poblacion, Esperanza, Masbate</dd>
                        </div>
                    </dl>
                </div>
            </x-ui.card>

            <div class="space-y-5">
                <!-- Household -->
                <x-ui.card padded="false">
                    <div class="px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-navy-900">Household Members</h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        @foreach($household as $member)
                            <div class="flex items-center gap-3 px-4 py-2.5">
                                <x-ui.avatar :name="$member['name']" size="sm" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700 truncate">{{ $member['name'] }}</p>
                                    <p class="text-xs text-slate-400">{{ $member['relation'] }} · {{ $member['age'] }} y/o</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <!-- Documents on file -->
                <x-ui.card padded="false">
                    <div class="px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-navy-900">Documents on File</h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        @foreach($documents as $doc)
                            <div class="flex items-center gap-3 px-4 py-2.5">
                                <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                                    <i data-lucide="{{ $doc['icon'] }}" class="w-4 h-4"></i>
                                </span>
                                <p class="text-sm text-slate-700 min-w-0 truncate flex-1">{{ $doc['label'] }}</p>
                                <x-ui.badge :status="$doc['status']" class="shrink-0" />
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.citizen>
