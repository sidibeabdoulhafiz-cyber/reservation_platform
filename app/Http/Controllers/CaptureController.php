<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaptureController extends Controller
{
    public function show(Paiement $paiement): StreamedResponse
    {
        abort_unless(auth()->user()?->estAdmin(), 403);
        abort_unless(
            $paiement->capture_path && Storage::disk('local')->exists($paiement->capture_path),
            404
        );

        return Storage::disk('local')->response($paiement->capture_path);
    }
}
