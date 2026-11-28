{{-- The staff Notifications page (redesign 28 Nov 2026, after eBPCO's):
     every notification sent to this officer, with All / Unread, Search,
     Category and When. It opens from the bell's "See all notifications"; no
     sidebar entry (the owner wants as few as possible), and a breadcrumb back
     to the Dashboard. x-notifications.page; English only by owner ruling. --}}
<x-layouts.admin title="Notifications" subtitle="Everything sent to you: requests, messages from residents, alerts and summaries." active="notifications">
    <x-notifications.page portal="admin" />
</x-layouts.admin>
