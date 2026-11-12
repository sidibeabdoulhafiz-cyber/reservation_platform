@php
    $reservation = $record->reservation;
@endphp
<div style="display:grid; gap:12px; font-size:14px">
    <div><b>{{ $reservation->ref }}</b> · {{ $reservation->user->name }}@if ($reservation->user->tel) · {{ $reservation->user->tel }}@endif</div>
    <div>{{ $reservation->libelleOffre() }}</div>
    <div>{{ $reservation->quantite }} place(s) réservée(s) · attendu <b>{{ number_format($record->montant_attendu, 0, ',', ' ') }} FCFA</b></div>
    <div>Déclaré : <b>{{ number_format($record->montant_declare, 0, ',', ' ') }} FCFA</b> · {{ $record->quantite_deduite }} place(s) déduite(s) · {{ $record->controle->getLabel() }}</div>
    <div>Transaction Wave : <b>{{ $record->transaction_id }}</b></div>

    @if ($record->capture_path)
        <img
            src="{{ route('captures.show', $record) }}"
            alt="Capture du reçu"
            style="max-width:100%; max-height:60vh; object-fit:contain; border-radius:8px; border:1px solid #d3dfd9"
        >
    @endif
</div>
