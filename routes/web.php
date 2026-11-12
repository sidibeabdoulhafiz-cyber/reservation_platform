<?php

use App\Http\Controllers\CaptureController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Controle\Scanner;
use App\Livewire\Reservations\Create;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', Create::class)->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return redirect('/admin');
        }

        if ($user->role === 'controleur') {
            return redirect()->route('controle.scanner');
        }

        return redirect()->route('home');
    })->name('dashboard');

    Route::get('/my-reservations', fn () => redirect()->route('home'))->name('reservations.index');

    Route::get('/controle', Scanner::class)->name('controle.scanner');

    Route::get('/captures/{paiement}', [CaptureController::class, 'show'])->name('captures.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

if (! app()->environment('production')) {
    Route::get('/dev/payer', function () {
        return view('dev.payer', [
            'montant' => (int) request('amount', 0),
            'tx' => 'T_'.strtoupper(Str::random(10)),
        ]);
    })->name('dev.payer');
}

require __DIR__.'/auth.php';
