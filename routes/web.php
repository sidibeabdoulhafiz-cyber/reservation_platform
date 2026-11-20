<?php

use App\Http\Controllers\CaptureController;
use App\Http\Controllers\ImpressionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Livewire\Controle\Scanner;
use App\Livewire\Evenements\Catalogue;
use App\Livewire\Pages\Contact;
use App\Livewire\Pages\Organisateurs;
use App\Livewire\Reservations\Create;
use App\Livewire\Reservations\MyReservations;
use App\Livewire\Reservations\Suivi;
use App\Models\Evenement;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Parcours public
Route::get('/', Catalogue::class)->name('home');
Route::get('/evenements/{evenement}', Create::class)->name('evenements.show');
Route::get('/organisateurs', Organisateurs::class)->name('organisateurs');
Route::get('/contact', Contact::class)->name('contact');
Route::view('/confidentialite', 'pages.confidentialite')->name('confidentialite');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response(implode("\n", [
    'User-agent: *',
    'Allow: /',
    'Disallow: /admin',
    'Disallow: /controle',
    'Disallow: /reservations/',
    'Disallow: /mes-reservations',
    'Disallow: /profile',
    'Disallow: /captures/',
    '',
    'Sitemap: '.route('sitemap'),
]), 200, ['Content-Type' => 'text/plain; charset=UTF-8']))->name('robots');

Route::middleware(['auth'])->group(function () {
    // Après connexion / inscription : on renvoie là où le passager en était.
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->estAdmin()) {
            return redirect('/admin');
        }

        if ($user->peutScanner()) {
            return redirect()->route('controle.scanner');
        }

        $draft = session('reservation_draft');
        if (is_array($draft) && ($evt = Evenement::find($draft['evenement_id'] ?? 0))) {
            return redirect()->route('evenements.show', $evt);
        }

        return redirect()->route('reservations.index');
    })->name('dashboard');

    Route::get('/mes-reservations', MyReservations::class)->name('reservations.index');
    Route::get('/reservations/{reservation}', Suivi::class)->name('reservations.show');
    Route::get('/reservations/{reservation}/imprimer', ImpressionController::class)->name('reservations.imprimer');

    Route::get('/controle', Scanner::class)->name('controle.scanner');

    Route::get('/captures/{paiement}', [CaptureController::class, 'show'])->name('captures.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Ancienne URL : redirection permanente.
Route::redirect('/my-reservations', '/mes-reservations', 301);
Route::redirect('/mes-billets', '/mes-reservations', 301);

if (! app()->environment('production')) {
    Route::get('/dev/payer', function () {
        return view('dev.payer', [
            'montant' => (int) request('amount', 0),
            'tx' => 'T_'.strtoupper(Str::random(10)),
        ]);
    })->name('dev.payer');
}

require __DIR__.'/auth.php';
