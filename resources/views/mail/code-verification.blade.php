<x-mail::message>
# Bonjour {{ $prenom }},

{{ $nouveau ? 'Bienvenue sur CotaTrans ! Pour confirmer ton adresse e-mail, saisis ce code :' : 'Voici ton code pour te connecter à CotaTrans :' }}

<x-mail::panel>
<div style="font-size:32px;font-weight:700;letter-spacing:8px;text-align:center;color:#0C4173">{{ $code }}</div>
</x-mail::panel>

Ce code est valable {{ $minutes }} minutes. Ne le partage avec personne, même pas avec l’équipe CotaTrans.

Si ce n’est pas toi, ignore cet e-mail et change ton mot de passe.

L’équipe CotaTrans
</x-mail::message>
