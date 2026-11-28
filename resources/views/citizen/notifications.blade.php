{{-- The resident's Notifications page (redesign 28 Nov 2026, after eBPCO's):
     All / Unread, Search, Category and When, unread notices tinted with a blue
     dot. LANGUAGE (TAB 09): its words are in x-notifications.page, in both
     languages; the page title is the top
     bar's ($filTitles 'notifications'). --}}
<x-layouts.citizen title="Notifications" subtitle="All your account and request updates in one place." active="notifications">
    <x-notifications.page portal="citizen" />
</x-layouts.citizen>
