<x-mail::message>
# Bonjour {{ $prenom }},

{{ $nouveau ? 'Bienvenue sur CotaTrans ! Pour confirmer votre adresse e-mail, saisissez ce code :' : 'Voici votre code pour vous connecter à CotaTrans :' }}

<x-mail::panel>
<div style="font-size:34px;font-weight:800;letter-spacing:10px;text-align:center;color:#0C4173;">{{ $code }}</div>
</x-mail::panel>

Ce code est valable {{ $minutes }} minutes. Ne le communiquez à personne, pas même à l’équipe CotaTrans.

Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail et changez votre mot de passe.

L’équipe CotaTrans
</x-mail::message>
