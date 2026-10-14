{{-- Who handles this request, in a Dokyu or Tulong detail. "Assign to me"
     and "Unassign me" are as before (the page's setAssignment). An officer who
     may hand requests on - the service's assign, or approve where it has no
     assign - also assigns it to another officer who works this service and
     can see it, or unassigns whoever has it (scope round 2026-10-15). Logic:
     requestAssign() in resources/js/scope-admin-requests.js. --}}
@props(['noun' => 'request'])
<div x-data="requestAssign()">
    <div x-show="mayTake || mine || mayHandOn" x-cloak class="flex flex-wrap items-center justify-between gap-2 mt-3">
        <p class="min-w-0 text-xs text-ink-muted" x-text="mine ? 'You are handling this {{ $noun }}.' : (selected.assigned ? selected.assigned + ' is handling this {{ $noun }}.' : 'This {{ $noun }} hasn’t been routed to a processor yet.')"></p>
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button variant="secondary" size="sm" icon="user-plus" x-show="mayTake" x-bind:disabled="processing || assigning" @click="setAssignment(true)">Assign to me</x-ui.button>
            <x-ui.button variant="secondary" size="sm" icon="user-minus" x-show="mine" x-bind:disabled="processing || assigning" @click="setAssignment(false)">Unassign me</x-ui.button>
            <x-ui.button variant="secondary" size="sm" icon="user-x" x-show="mayHandOn && selected.assigned && !mine" x-bind:disabled="processing || assigning" @click="unassign()">Unassign</x-ui.button>
            <x-ui.button variant="secondary" size="sm" icon="users" x-show="mayHandOn" x-bind:disabled="assigning" x-bind:aria-expanded="picking && pickRef === selected.ref" @click="openPicker()"><span x-text="selected.assigned ? 'Reassign…' : 'Assign to…'"></span></x-ui.button>
        </div>
    </div>
    <div x-show="mayHandOn && picking && pickRef === selected.ref" x-cloak class="mt-2 rounded-xl border border-slate-200 p-3">
        <div x-show="loadingOfficers" aria-busy="true"><x-ui.skeleton class="h-10" /></div>
        <div x-show="!loadingOfficers && officersFailed" x-cloak role="alert" class="flex flex-wrap items-center justify-between gap-2 text-xs text-rose-700">
            <span x-text="officersError"></span>
            <button type="button" @click="loadOfficers()" class="font-semibold underline">Try again</button>
        </div>
        <p x-show="!loadingOfficers && !officersFailed && !officers.length" x-cloak class="text-xs text-ink-muted">No officer who works this service can see this {{ $noun }}.</p>
        <div x-show="!loadingOfficers && !officersFailed && officers.length" x-cloak class="flex flex-col gap-2 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
                <label :for="$id('assignee')" class="mb-1 block text-[11px] font-medium text-ink-muted">Officer</label>
                <select :id="$id('assignee')" x-model="choice" class="w-full rounded-xl border-0 px-3 py-2 text-xs surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">Choose an officer…</option>
                    <template x-for="o in officers" :key="o.employee_id"><option :value="o.employee_id" :disabled="o.current" x-text="officerLabel(o)"></option></template>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button size="sm" icon="user-check" x-bind:disabled="!choice || assigning" @click="assignTo()"><span x-text="assigning ? 'Assigning…' : 'Assign'"></span></x-ui.button>
                <x-ui.button size="sm" variant="ghost" @click="picking = false">Cancel</x-ui.button>
            </div>
        </div>
    </div>
</div>
