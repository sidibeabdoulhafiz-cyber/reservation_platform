<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Zone;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function index()
    {
        $zones = Zone::where('actif', true)->get();
        $settings = Setting::all()->pluck('value', 'key');

        return view('welcome', compact('zones', 'settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'zone_id' => 'required|exists:zones,id',
            'places' => 'required|integer|min:1|max:' . (Setting::get('max_places', 8)),
        ]);

        $user = Auth::user();
        $zone = Zone::findOrFail($request->zone_id);
        $montant = $zone->prix * $request->places;

        $ref = 'VH-' . strtoupper(Str::random(6));

        $reservation = Reservation::create([
            'ref' => $ref,
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            'places' => $request->places,
            'montant' => $montant,
            'statut' => 'en_attente',
        ]);

        $waveLink = Setting::get('wave_link', 'https://pay.wave.com/m/M_ci_0S9vrDPL2ExV/c/ci/');
        $paymentUrl = $waveLink . '?amount=' . $montant;

        return response()->json([
            'success' => true,
            'ref' => $ref,
            'payment_url' => $paymentUrl,
            'montant' => $montant
        ]);
    }

    public function myReservations()
    {
        $reservations = Auth::user()->reservations()->latest()->get();
        return view('reservations.index', compact('reservations'));
    }

    public function confirmPayment(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'paiement_id' => 'required|string',
            'capture' => 'required|image|max:10240',
        ]);

        $path = $request->file('capture')->store('captures', 'public');

        $reservation->update([
            'paiement_id' => $request->paiement_id,
            'capture_path' => $path,
            'statut' => 'payee',
            'date_paiement' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement confirmé avec succès !'
        ]);
    }
}
