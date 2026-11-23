<x-filament-widgets::widget>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px">
        <h2 style="font-size:1.05rem;font-weight:700;margin:0">À faire maintenant</h2>
        @if ($toutBon)
            <span style="display:inline-flex;align-items:center;gap:6px;font-size:.85rem;font-weight:600;color:#15803d;background:#dcfce7;padding:4px 10px;border-radius:99px">
                <x-filament::icon icon="heroicon-m-check-circle" style="width:16px;height:16px" /> Tout est à jour
            </span>
        @endif
    </div>
    <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))">
        @foreach ($taches as $t)
            <a href="{{ $t['url'] }}" class="fi-section" style="display:flex;gap:14px;align-items:center;padding:16px;border-radius:14px;text-decoration:none;transition:transform .15s;{{ $t['n'] ? 'box-shadow:inset 4px 0 0 '.$t['couleur'] : 'opacity:.7' }}"
               onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                <span style="flex:none;width:46px;height:46px;border-radius:12px;display:grid;place-items:center;background:{{ $t['couleur'] }}1a;color:{{ $t['couleur'] }}">
                    <x-filament::icon :icon="$t['icone']" style="width:24px;height:24px" />
                </span>
                <span style="min-width:0">
                    <span style="display:block;font-size:1.6rem;font-weight:800;line-height:1.1;color:{{ $t['n'] ? $t['couleur'] : 'inherit' }}">{{ $t['n'] }}</span>
                    <span style="display:block;font-weight:600">{{ $t['titre'] }}</span>
                    <span style="display:block;font-size:.8rem;opacity:.65">{{ $t['aide'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</x-filament-widgets::widget>
