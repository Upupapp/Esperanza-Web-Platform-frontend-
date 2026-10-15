{{-- Appointments: its own sidebar entry (owner, 2026-10-15), rather than the
     Dashboard tab it first was. The bookings of the schedules this officer
     runs, day by day, and the schedules with their closed days
     (x-admin.scope.appointments; the logic is appointmentsDesk() in
     resources/js/scope-admin-requests.js). --}}
<x-layouts.admin title="Appointments" subtitle="Residents' bookings by day, and the office's schedules and closed days." active="appointments">
    <div x-data class="animate-fade-up">
        <div x-show="$store.session.permsReady && !($store.session.canPerm('appt_bookings', 'view') || $store.session.canPerm('appt_schedules', 'view'))" x-cloak>
            <x-admin.access-restricted module="Appointments" icon="calendar-clock" />
        </div>
        <template x-if="$store.session.canPerm('appt_bookings', 'view') || $store.session.canPerm('appt_schedules', 'view')">
            <x-admin.scope.appointments />
        </template>
    </div>
</x-layouts.admin>
