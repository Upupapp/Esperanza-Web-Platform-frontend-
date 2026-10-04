@props(['service' => 'Dokyu'])

{{-- A resident can file Dokyu and Tulong requests only once staff have
     verified the account (CitizenRequestController: ACCOUNT_NOT_VERIFIED).
     Said here, before the wizard, rather than by a refusal at its last step. --}}
<div
    x-show="$store.citizenSession.account && !$store.citizenSession.account.fullServiceAccess"
    x-cloak
    role="status"
    class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 mb-4"
>
    <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0"><i data-lucide="hourglass" class="w-4 h-4"></i></span>
    <div class="min-w-0 text-xs text-amber-800">
        <p class="text-sm font-semibold text-amber-900">
            <x-ui.t fil="Hinihintay pa ang pag-apruba ng iyong account" en="Your account is waiting for approval" />
            <span class="ml-1 inline-flex items-center rounded-full bg-white/70 px-2 py-0.5 text-[10.5px] font-medium text-amber-700 ring-1 ring-amber-200" x-text="$store.citizenSession.account?.status"></span>
        </p>
        <p class="mt-1">
            <x-ui.t
                fil="Makakapag-file ka ng kahilingan sa {{ $service }} kapag nasuri na ng LGU ang iyong valid ID at na-verify ang iyong account. Aabisuhan ka kapag tapos na ito."
                en="You can file a {{ $service }} request once the LGU has checked your valid ID and verified your account. You will be notified when that is done."
            />
            <a href="{{ route('citizen.profile') }}" class="font-medium text-amber-900 underline underline-offset-2"><x-ui.t fil="Tingnan ang aking account" en="View my account" /></a>
        </p>
    </div>
</div>
