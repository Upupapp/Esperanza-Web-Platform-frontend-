{{--
    The heading of each BDRRMC paper form, as Barangay Labangtaytay's files
    print it (owner, 2026-10-27): the title lines, then the "Label: ______"
    lines where the paper has them. The barangay's own name takes
    Labangtaytay's place. $mode: 'edit' (filled in) or 'view' (a saved form).
    Included by bdrrmc-paper.blade.php, once per sheet.
--}}
@php
    $m = $mode === 'view' ? "'view'" : "'edit'";
    $brgy = $mode === 'view' ? 'viewing.barangay' : 'barangay';
    $vm = fn ($k) => "(viewing.meta && viewing.meta.{$k}) || ''";
@endphp

{{-- 1. Profile of Evacuees (DROMIC Form 1.2) --}}
<template x-if="paperKey({{ $m }}) === 'evacuees'">
    <div class="pp-heading" @if ($mode !== 'view') x-effect="asOfTyped = asOfDate && asOfTime ? asOfDate + 'T' + asOfTime : ''" @endif>
        <p class="pp-b pp-10">Dromic form 1.2</p>
        <p class="pp-center pp-b pp-11">PROFILE OF EVACUEES INSIDE THE EVACUATION CENTER</p>
        <p class="pp-center pp-10">
            @if ($mode === 'view')
                <x-admin.sakuna.paper-field label="As of Date:" mode="view" view="paperAsOf(viewing.meta && viewing.meta.as_of).date" class="pp-w-110" />
                <x-admin.sakuna.paper-field label="Time:" mode="view" view="paperAsOf(viewing.meta && viewing.meta.as_of).time" class="pp-w-120" />
            @else
                <template x-if="drafted && drafted.evacuees">
                    <span>
                        <x-admin.sakuna.paper-field label="As of Date:" type="computed" model="paperAsOf(drafted.evacuees.params.as_of).date" class="pp-w-110" />
                        <x-admin.sakuna.paper-field label="Time:" type="computed" model="paperAsOf(drafted.evacuees.params.as_of).time" class="pp-w-120" />
                    </span>
                </template>
                <template x-if="!(drafted && drafted.evacuees)">
                    <span>
                        <x-admin.sakuna.paper-field label="As of Date:" type="date" model="asOfDate" aria="As of: date" class="pp-w-110" />
                        <x-admin.sakuna.paper-field label="Time:" type="time" model="asOfTime" aria="As of: time" class="pp-w-120" />
                    </span>
                </template>
            @endif
        </p>
        <div class="pp-two pp-10">
            <div class="pp-lines">
                <x-admin.sakuna.paper-field label="Region:" type="static" value="Region V" />
                <x-admin.sakuna.paper-field label="Type of Disaster:" :mode="$mode" model="disasterType" :view="$vm('disaster') . ' || viewing.period || \'\''" class="pp-w-140" />
                <x-admin.sakuna.paper-field label="Date of Occurrence:" :mode="$mode" type="date" model="occurred" view="paperDate(viewing.meta && viewing.meta.occurred_on)" class="pp-w-140" />
                <x-admin.sakuna.paper-field label="Barangay:" :mode="$mode" type="barangay" :view="$brgy" upper class="pp-w-110" />
                <x-admin.sakuna.paper-field label="Name of Evacuation Center:" :mode="$mode" model="center" :view="$vm('evacuation_center')" readonly="!!(drafted && drafted.evacuees)" class="pp-w-190" />
                <x-admin.sakuna.paper-field label="Address:" :mode="$mode" model="address" :view="$vm('address')" aria="Address of the Evacuation Center" class="pp-w-240" />
            </div>
            <div class="pp-lines">
                @if ($mode === 'view')
                    <x-admin.sakuna.paper-field label="Total Number of Families with less than 5 members:" mode="view" view="viewing.meta && viewing.meta.families_lt5 !== undefined ? viewing.meta.families_lt5 + (viewing.meta.five_member_rule === 'fewer' ? viewing.meta.families_eq5 : 0) : ''" class="pp-w-80" />
                    <x-admin.sakuna.paper-field label="Total Number of Families with more than 5 members:" mode="view" view="viewing.meta && viewing.meta.families_gt5 !== undefined ? viewing.meta.families_gt5 + (viewing.meta.five_member_rule === 'more' ? viewing.meta.families_eq5 : 0) : ''" class="pp-w-80" />
                @else
                    <x-admin.sakuna.paper-field label="Total Number of Families with less than 5 members:" type="computed" model="hasFamilies() ? familySizes().lt5 + (fiveRule() === 'fewer' ? familySizes().eq5 : 0) : ''" class="pp-w-80" />
                    <x-admin.sakuna.paper-field label="Total Number of Families with more than 5 members:" type="computed" model="hasFamilies() ? familySizes().gt5 + (fiveRule() === 'more' ? familySizes().eq5 : 0) : ''" class="pp-w-80" />
                @endif
            </div>
        </div>
    </div>
</template>

{{-- 2. "Human, Housing & Flooding" --}}
<template x-if="paperKey({{ $m }}) === 'human-housing'">
    <div class="pp-heading">
        <p class="pp-center pp-10">BARANGAY DISASTER AND RISK REDUCTION MANAGEMENT COMMITTEE</p>
        <p class="pp-center pp-10" x-text="({{ $brgy }} ? {{ $brgy }}.toUpperCase() + ', ' : '') + 'ESPERANZA, MASBATE'"></p>
        <p class="pp-center pp-b pp-11"><span class="pp-red">&ldquo;HUMAN, HOUSING &amp; FLOODING&rdquo;</span> CALAMITY/ DAMAGES CAPTURE AND ASSESSMENT FORM</p>
        <p class="pp-line pp-10">
            <x-admin.sakuna.paper-field label="Barangay:" :mode="$mode" type="barangay" :view="$brgy" upper class="pp-w-110" />
            <x-admin.sakuna.paper-field label="Purok:" :mode="$mode" model="purok" :view="$vm('purok')" class="pp-w-60" />
            <x-admin.sakuna.paper-field label="Type of Disaster:" :mode="$mode" model="disasterType" :view="$vm('disaster') . ' || viewing.period || \'\''" class="pp-w-150" />
        </p>
    </div>
</template>

{{-- 3. "Agricultural Crops & Livestock" --}}
<template x-if="paperKey({{ $m }}) === 'agriculture'">
    <div class="pp-heading">
        <p class="pp-center pp-b pp-12">BARANGAY DISASTER RISK REDUCTION AND MANAGEMENT COMMITTEE</p>
        <p class="pp-center pp-10">ESPERANZA, MASBATE</p>
        <p class="pp-center pp-10"><span class="pp-red pp-b">&ldquo;AGRICULTURAL CROPS &amp; LIVESTOCK&rdquo;</span> CALAMITY DAMAGES CAPTURED ASSESSMENT FORM</p>
        <p class="pp-line pp-10">
            <x-admin.sakuna.paper-field label="Barangay:" :mode="$mode" type="barangay" :view="$brgy" upper class="pp-w-110" />
            <x-admin.sakuna.paper-field label="Name of Disaster:" :mode="$mode" model="disasterType" :view="$vm('disaster') . ' || viewing.period || \'\''" class="pp-w-130" />
            <x-admin.sakuna.paper-field label="Date of Occurrence:" :mode="$mode" type="date" model="occurred" view="paperDate(viewing.meta && viewing.meta.occurred_on)" class="pp-w-160" />
        </p>
    </div>
</template>

{{-- 4. "Fishing" (portrait) --}}
<template x-if="paperKey({{ $m }}) === 'fishing'">
    <div class="pp-heading">
        <p class="pp-center pp-b pp-12">BARANGAY DISASTER RISK REDUCTION AND MANAGEMENT COMMITTEE</p>
        <p class="pp-center pp-10">ESPERANZA, MASBATE</p>
        <p class="pp-center pp-10"><span class="pp-red pp-b">&ldquo;FISHING&rdquo;</span> CALAMITY DAMAGES CAPTURED ASSESSMENT FORM</p>
        <div class="pp-lines pp-10">
            <p class="pp-line">
                <x-admin.sakuna.paper-field label="BARANGAY:" :mode="$mode" type="barangay" :view="$brgy" upper class="pp-w-150" />
                <x-admin.sakuna.paper-field label="PUROK:" :mode="$mode" model="purok" :view="$vm('purok')" class="pp-w-60" />
            </p>
            <x-admin.sakuna.paper-field label="NAME & TYPE OF DISASTER:" :mode="$mode" model="disasterType" :view="$vm('disaster') . ' || viewing.period || \'\''" aria="Name and type of disaster" class="pp-w-150" />
            <x-admin.sakuna.paper-field label="DATE OF OCCURRENCE:" :mode="$mode" type="date" model="occurred" view="paperDate(viewing.meta && viewing.meta.occurred_on)" aria="Date of Occurrence" class="pp-w-160" />
        </div>
    </div>
</template>

{{-- 5. "Business" --}}
<template x-if="paperKey({{ $m }}) === 'business'">
    <div class="pp-heading">
        <p class="pp-center pp-b pp-12">BARANGAY DISASTER RISK REDUCTION AND MANAGEMENT COMMITTEE</p>
        <p class="pp-center pp-10">ESPERANZA, MASBATE</p>
        <p class="pp-center pp-10"><span class="pp-red pp-b">&ldquo;BUSINESS&rdquo;</span> CALAMITY DAMAGES CAPTURED ASSESSMENT FORM</p>
        <p class="pp-line pp-10">
            <x-admin.sakuna.paper-field label="Barangay:" :mode="$mode" type="barangay" :view="$brgy" upper class="pp-w-110" />
            <x-admin.sakuna.paper-field label="Purok:" :mode="$mode" model="purok" :view="$vm('purok')" class="pp-w-60" />
            <x-admin.sakuna.paper-field label="Disaster:" :mode="$mode" model="disasterType" :view="$vm('disaster') . ' || viewing.period || \'\''" class="pp-w-120" />
            <x-admin.sakuna.paper-field label="Date of Occurrence:" :mode="$mode" type="date" model="occurred" view="paperDate(viewing.meta && viewing.meta.occurred_on)" class="pp-w-140" />
        </p>
    </div>
</template>

{{-- 6. Relief Distribution Sheet (portrait) --}}
<template x-if="paperKey({{ $m }}) === 'rds'">
    <div class="pp-heading">
        <p class="pp-center pp-10">Republic of the Philippines</p>
        <p class="pp-center pp-10" x-text="'Barangay ' + ({{ $brgy }} || '__________') + ',Esperanza,Masbate'"></p>
        <p class="pp-center pp-b pp-10">RELIEF DISTRIBUTION SHEET (RDS)</p>
        <div class="pp-two pp-two-rds pp-10">
            <div class="pp-lines">
                <x-admin.sakuna.paper-field label="Region:" type="static" value="V" bold />
                <x-admin.sakuna.paper-field label="Province:" type="static" value="MASBATE" bold />
                <x-admin.sakuna.paper-field label="Municipality:" type="static" value="ESPERANZA" bold />
                <x-admin.sakuna.paper-field label="Barangay:" :mode="$mode" type="barangay" :view="$brgy" upper class="pp-w-110" />
            </div>
            <div class="pp-lines">
                <x-admin.sakuna.paper-field label="Name of Evacuation Center:" :mode="$mode" model="center" :view="$vm('evacuation_center')" class="pp-w-130" />
                <x-admin.sakuna.paper-field label="Type of Disaster:" :mode="$mode" model="disasterType" :view="$vm('disaster') . ' || viewing.period || \'\''" class="pp-w-170" />
                <x-admin.sakuna.paper-field label="Date of Occurrence:" :mode="$mode" type="date" model="occurred" view="paperDate(viewing.meta && viewing.meta.occurred_on)" class="pp-w-160" />
                <x-admin.sakuna.paper-field label="Date of Relief Distribution:" :mode="$mode" type="date" model="distributedOn" view="paperDate(viewing.meta && viewing.meta.distribution_date)" class="pp-w-130" />
            </div>
        </div>
    </div>
</template>
