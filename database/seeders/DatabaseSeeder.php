<?php

namespace Database\Seeders;

use App\Models\Depart;
use App\Models\Evenement;
use App\Models\Setting;
use App\Models\TypeBillet;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        User::updateOrCreate(
            ['email' => 'admin@cotatrans.test'],
            ['name' => 'Administrateur', 'password' => $password, 'role' => 'admin', 'actif' => true, 'tel' => '+2250700000001', 'email_verified_at' => now()]
        );

        User::updateOrCreate(
            ['email' => 'client@cotatrans.test'],
            ['name' => 'Awa Koné', 'password' => $password, 'role' => 'client', 'actif' => true, 'tel' => '+2250700000002', 'email_verified_at' => now()]
        );

        User::updateOrCreate(
            ['email' => 'controle@cotatrans.test'],
            ['name' => 'Contrôleur', 'password' => $password, 'role' => 'controleur', 'actif' => true, 'tel' => '+2250700000003', 'email_verified_at' => now()]
        );

        $lienLocal = rtrim((string) config('app.url'), '/').'/dev/payer';

        Setting::set('site_ouvert', '1');
        Setting::set('wave_link', $lienLocal);
        Setting::set('wave_amount', '1');
        Setting::set('delai_paiement_min', '30');
        Setting::set('message_global', '');

        $yopougon = Zone::updateOrCreate(['nom' => 'Yopougon'], ['prix_defaut' => 2500, 'ordre' => 1, 'actif' => true]);
        $cocody = Zone::updateOrCreate(['nom' => 'Cocody'], ['prix_defaut' => 3000, 'ordre' => 2, 'actif' => true]);
        $marcory = Zone::updateOrCreate(['nom' => 'Marcory'], ['prix_defaut' => 2500, 'ordre' => 3, 'actif' => true]);

        $vehicule = Vehicule::updateOrCreate(
            ['immatriculation' => 'AB-1234-CI'],
            ['nom' => 'Sprinter 1', 'capacite' => 18, 'chauffeur_nom' => 'Koffi Yao', 'chauffeur_tel' => '+2250700000010', 'actif' => true]
        );

        $evenement = Evenement::updateOrCreate(
            ['slug' => 'concert-plateau'],
            [
                'titre' => 'Concert au Plateau',
                'accroche' => 'La grande soirée live de la saison, aller-retour en car.',
                'lien_whatsapp' => 'https://chat.whatsapp.com/exemple-cotatrans',
                'description' => 'Soirée live. Places en véhicule et billets d’entrée.',
                'lieu' => 'Palais de la Culture',
                'ville' => 'Abidjan',
                'debut_at' => now()->addDays(10)->setTime(20, 0),
                'fin_at' => now()->addDays(10)->setTime(23, 30),
                'statut' => 'publie',
            ]
        );

        $depart = Depart::updateOrCreate(
            [
                'evenement_id' => $evenement->id,
                'depart_at' => now()->addDays(10)->setTime(18, 0),
            ],
            [
                'vehicule_id' => $vehicule->id,
                'retour_at' => now()->addDays(11)->setTime(1, 0),
                'places_total' => 18,
                'max_par_commande' => 8,
                'statut' => 'ouvert',
            ]
        );

        // Itinéraire : point principal puis arrêts intermédiaires, dans l'ordre de passage.
        foreach ([
            [$yopougon, 2500, 'Gare de Yopougon', '17:00', true],
            [$cocody, 3000, 'Carrefour de la vie', '17:20', false],
            [$marcory, 2500, 'Cap Sud', '17:40', false],
        ] as $i => [$zone, $prix, $rdv, $heure, $principal]) {
            $depart->tarifs()->updateOrCreate(
                ['zone_id' => $zone->id],
                ['ordre' => $i + 1, 'principal' => $principal, 'prix' => $prix, 'point_rdv' => $rdv, 'heure_ramassage' => $heure, 'actif' => true]
            );
        }

        // Second événement : transport uniquement, deux départs.
        $retro = Evenement::updateOrCreate(
            ['slug' => 'journee-retro-bassam'],
            [
                'titre' => 'Journée Rétro à Bassam',
                'accroche' => 'Musique des années 80-90, plage et ambiance. On t’emmène !',
                'description' => 'Une journée entière de sons rétro au bord de la mer.',
                'lieu' => 'Plage de Grand-Bassam',
                'ville' => 'Grand-Bassam',
                'debut_at' => now()->addDays(17)->setTime(11, 0),
                'fin_at' => now()->addDays(17)->setTime(20, 0),
                'statut' => 'publie',
            ]
        );

        foreach ([[8, 0], [9, 30]] as [$h, $m]) {
            $d = Depart::updateOrCreate(
                ['evenement_id' => $retro->id, 'depart_at' => now()->addDays(17)->setTime($h, $m)],
                ['vehicule_id' => $vehicule->id, 'retour_at' => now()->addDays(17)->setTime(21, 0), 'places_total' => 18, 'max_par_commande' => 6, 'statut' => 'ouvert']
            );
            foreach ([
                [$cocody, 4000, 'Riviera 2, Carrefour Duncan', 0, true],
                [$marcory, 3500, 'Boulevard VGE, Cap Sud', 25, false],
            ] as $i => [$zone, $prix, $rdv, $decalage, $principal]) {
                $d->tarifs()->updateOrCreate(
                    ['zone_id' => $zone->id],
                    ['ordre' => $i + 1, 'principal' => $principal, 'prix' => $prix, 'point_rdv' => $rdv,
                        'heure_ramassage' => now()->setTime($h, $m)->addMinutes($decalage)->format('H:i'), 'actif' => true]
                );
            }
        }

        TypeBillet::updateOrCreate(
            ['evenement_id' => $evenement->id, 'nom' => 'Standard'],
            ['prix' => 5000, 'quota' => 200, 'max_par_commande' => 2, 'actif' => true, 'ordre' => 1]
        );

        TypeBillet::updateOrCreate(
            ['evenement_id' => $evenement->id, 'nom' => 'VIP'],
            ['prix' => 15000, 'quota' => 40, 'max_par_commande' => 2, 'actif' => true, 'ordre' => 2]
        );

        // Affiches de démonstration (copiées sur le disque public).
        foreach ([$evenement, $retro] as $evt) {
            $source = database_path('seeders/affiches/'.$evt->slug.'.jpg');
            if (is_file($source)) {
                $chemin = 'evenements/'.$evt->slug.'.jpg';
                Storage::disk('public')->put($chemin, file_get_contents($source));
                $evt->update(['image_path' => $chemin]);
            }
        }
    }
}
