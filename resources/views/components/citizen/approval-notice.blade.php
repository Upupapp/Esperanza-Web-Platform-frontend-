@props(['service' => 'Dokyu'])

{{-- A resident can file Dokyu and Tulong requests only once staff have
     verified the account (CitizenRequestController: ACCOUNT_NOT_VERIFIED).
     Said here, before the wizard, rather than by a refusal at its last step.
     What it says follows the account status AND the identity (bug hunt 25 Nov,
     #6): an approved resident whose ID was revoked, or one whose account was
     not approved or had its approval withdrawn, is not "waiting for approval" -
     they send a new valid ID. --}}
<div
    x-show="$store.citizenSession.account && !$store.citizenSession.account.fullServiceAccess"
    x-data="{
        get a() { return $store.citizenSession.account || {}; },
        get kind() {
            if (this.a.status === 'Approved' && this.a.identityStatus === 'Revoked') return 'idRevoked';
            if (this.a.status === 'Rejected') return 'rejected';
            return 'waiting';
        },
    }"
    x-cloak
    role="status"
    class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 mb-4"
>
    <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0"><i data-lucide="hourglass" class="w-4 h-4"></i></span>
    <div class="min-w-0 text-xs text-amber-800">
        <p class="text-sm font-semibold text-amber-900">
            <span x-show="kind === 'waiting'"><x-ui.t fil="Hinihintay pa ang pag-apruba ng iyong account" en="Your account is waiting for approval" /></span>
            <span x-show="kind === 'idRevoked'" x-cloak><x-ui.t fil="Naka-pause muna ang mga request mo" en="Your requests are paused" /></span>
            <span x-show="kind === 'rejected'" x-cloak><x-ui.t fil="Hindi pa verified ang account mo" en="Your account is not verified" /></span>
            {{-- Status names stay in English (owner, 19 Nov). --}}
            <span class="ml-1 inline-flex items-center rounded-full bg-white/70 px-2 py-0.5 text-[10.5px] font-medium text-amber-700 ring-1 ring-amber-200" x-text="a.status"></span>
        </p>
        <p class="mt-1">
            <span x-show="kind === 'waiting'">
                <x-ui.t
                    fil="Makakapag-file ka ng kahilingan sa {{ $service }} kapag nasuri na ng LGU ang iyong valid ID at na-verify ang iyong account. Aabisuhan ka kapag tapos na ito."
                    en="You can file a {{ $service }} request once the LGU has checked your valid ID and verified your account. You will be notified when that is done."
                />
            </span>
            <span x-show="kind === 'idRevoked'" x-cloak>
                <x-ui.t
                    fil="Approved pa rin ang account mo, pero binawi ng LGU ang ID check mo. Magpadala ng bagong valid ID para makapag-file ulit ng {{ $service }} request."
                    en="Your account is still approved, but the LGU revoked your ID check. Send a new valid ID to file {{ $service }} requests again."
                />
            </span>
            <span x-show="kind === 'rejected'" x-cloak>
                <x-ui.t
                    fil="Magpadala ng bagong valid ID mula sa iyong profile, o pumunta sa inyong barangay office."
                    en="Send a new valid ID from your profile, or visit your barangay office."
                />
            </span>
            <a href="{{ route('citizen.profile') }}" class="font-medium text-amber-900 underline underline-offset-2"><x-ui.t fil="Tingnan ang aking account" en="View my account" /></a>
        </p>
    </div>
</div>
