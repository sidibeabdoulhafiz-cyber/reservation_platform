<x-app-layout>
    <x-slot name="header">
        <h1>Profil</h1>
        <p>Votre compte n’est jamais supprimé : vous pouvez seulement le désactiver.</p>
    </x-slot>

    <div style="display:grid;gap:24px">
        @include('profile.partials.update-profile-information-form')
        @include('profile.partials.update-password-form')
        @include('profile.partials.delete-user-form')
    </div>
</x-app-layout>
