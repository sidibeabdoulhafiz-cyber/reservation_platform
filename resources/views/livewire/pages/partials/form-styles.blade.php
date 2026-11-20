<style>
    .page-form { display: grid; gap: 28px; align-items: start; max-width: 1080px; margin: 8px auto 0; }
    @media (min-width: 900px) { .page-form { grid-template-columns: 1fr 1.15fr; gap: 44px; } .form-intro { position: sticky; top: 100px; } }
    .form-intro h1 { color: var(--brand); }
    .lead-txt { color: var(--muted); font-size: 1.08rem; }
    .perks { list-style: none; padding: 0; margin: 20px 0; display: grid; gap: 12px; }
    .perks li { display: flex; gap: 12px; align-items: flex-start; }
    .perks .ico { color: var(--accent); margin-top: 2px; }
    .form-card h2 { color: var(--fg); font-size: 1.3rem; }
    .form-card legend { font-size: 1rem; color: var(--brand); }
    .field textarea { font: 400 16px var(--body); background: #fff; border: 1.5px solid var(--line); border-radius: 12px; padding: 12px 14px; width: 100%; color: var(--fg); resize: vertical; }
    .field textarea:focus { border-color: var(--brand); outline: none; box-shadow: 0 0 0 4px var(--brand-soft); }
    .hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
    .done { text-align: center; padding: 20px 6px; }
    .done-ico { width: 72px; height: 72px; border-radius: 50%; display: grid; place-items: center; background: var(--ok-soft); color: var(--ok); margin: 0 auto 14px; animation: pop .5s cubic-bezier(.2,1.4,.4,1); }
    @keyframes pop { from { transform: scale(.5); opacity: 0; } to { transform: none; opacity: 1; } }
    .done p { color: var(--muted); }
</style>
