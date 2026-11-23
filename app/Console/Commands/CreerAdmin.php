<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreerAdmin extends Command
{
    protected $signature = 'cotatrans:admin {--role=admin : admin ou controleur}';

    protected $description = 'Crée (ou met à jour) un compte administrateur ou agent de bord, avec un mot de passe solide';

    public function handle(): int
    {
        $role = $this->option('role') === 'controleur' ? 'controleur' : 'admin';
        $email = strtolower(trim((string) $this->ask('E-mail')));
        $nom = (string) $this->ask('Nom complet', $role === 'admin' ? 'Administrateur CotaTrans' : 'Agent de bord');
        $mdp = (string) $this->secret('Mot de passe (12 caractères minimum, lettres et chiffres)');
        $confirmation = (string) $this->secret('Confirme le mot de passe');

        $v = Validator::make(compact('email', 'nom', 'mdp'), [
            'email' => ['required', 'email'],
            'nom' => ['required', 'max:120'],
            'mdp' => ['required', 'min:12', 'regex:/[A-Za-z]/', 'regex:/\d/'],
        ]);
        if ($v->fails() || $mdp !== $confirmation) {
            $this->error($mdp !== $confirmation ? 'Les mots de passe ne correspondent pas.' : $v->errors()->first());

            return self::FAILURE;
        }

        $user = User::updateOrCreate(['email' => $email], [
            'name' => $nom,
            'password' => Hash::make($mdp),
            'role' => $role,
            'actif' => true,
        ]);

        $this->info(($user->wasRecentlyCreated ? 'Compte créé' : 'Compte mis à jour').' : '.$email.' ('.$role.').');
        $this->line('À la première connexion, un code de vérification sera envoyé à cette adresse.');

        return self::SUCCESS;
    }
}
