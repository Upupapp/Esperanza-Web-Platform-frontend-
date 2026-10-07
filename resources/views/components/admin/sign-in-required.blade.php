{{-- Shown when there is NO admin session at all.

     Deliberately separate from x-admin.access-restricted: that component is for
     a signed-in account whose role lacks a module, and it renders "Signed in
     as ...", which would be false here.

     TAB 08: the session store fails CLOSED, so an unauthenticated visitor gets
     this instead of an unrestricted preview of the whole Administrative Portal.
     This hides what the visitor cannot use — it is not a security boundary. --}}

<div class="flex flex-col items-center justify-center text-center py-24 px-6 surface-glass rounded-[26px] animate-fade-up">
    <span class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mb-4">
        <i data-lucide="lock" class="w-6 h-6"></i>
    </span>

    <h3 class="text-base font-semibold text-ink">Sign in to continue</h3>

    <p class="text-sm text-ink-muted mt-1.5 max-w-sm">
        The Administrative Portal is available to signed-in LGU personnel only.
        Your account determines which modules and which barangays you can see.
    </p>

    <x-ui.button :href="route('admin.login')" icon="log-in" class="mt-5">
        LGU Personnel Login
    </x-ui.button>
</div>
