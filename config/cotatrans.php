<?php

return [
    // Code de vérification envoyé par e-mail à l'inscription et à chaque connexion sur un nouvel appareil.
    'code_connexion' => (bool) env('CODE_CONNEXION', true),

    // Durée pendant laquelle un appareil « de confiance » ne redemande pas de code.
    'appareil_confiance_jours' => (int) env('APPAREIL_CONFIANCE_JOURS', 30),

    // Durée de validité d'un code (minutes).
    'code_validite_minutes' => 10,
];
