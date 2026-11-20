@props(['url', 'texte', 'titre' => 'Partager', 'compact' => false])
@php
    $u = rawurlencode($url);
    $t = rawurlencode($texte);
@endphp
{{-- Partage d'un événement : WhatsApp, Facebook, partage natif du téléphone, copie du lien. --}}
<div {{ $attributes->merge(['class' => 'partage'.($compact ? ' compact' : '')]) }}
     x-data="{ copie: false, natif: !! navigator.share,
               async partager() { try { await navigator.share({ title: @js($titre), text: @js($texte), url: @js($url) }); } catch (e) {} },
               async copier() { try { await navigator.clipboard.writeText(@js($url)); } catch (e) { const i = document.createElement('input'); i.value = @js($url); document.body.appendChild(i); i.select(); document.execCommand('copy'); i.remove(); } this.copie = true; setTimeout(() => this.copie = false, 2000); } }">
    @if ($titre)<span class="partage-lbl">{{ $titre }}</span>@endif
    <a class="sh sh-wa" href="https://wa.me/?text={{ $t }}%20{{ $u }}" target="_blank" rel="noopener" aria-label="Partager sur WhatsApp" title="WhatsApp"><x-social name="whatsapp" size="18" /><span>WhatsApp</span></a>
    <a class="sh sh-fb" href="https://www.facebook.com/sharer/sharer.php?u={{ $u }}" target="_blank" rel="noopener" aria-label="Partager sur Facebook" title="Facebook"><x-social name="facebook" size="18" /><span>Facebook</span></a>
    <button type="button" class="sh sh-nat" x-show="natif" x-cloak @click="partager()" aria-label="Plus d’options de partage"><x-ico name="share" size="18" /><span>Autres</span></button>
    <button type="button" class="sh sh-cp" @click="copier()" :aria-label="copie ? 'Lien copié' : 'Copier le lien'"><x-ico name="check" size="18" x-show="copie" x-cloak /><x-ico name="link" size="18" x-show="! copie" /><span x-text="copie ? 'Lien copié !' : 'Copier le lien'">Copier le lien</span></button>
</div>
