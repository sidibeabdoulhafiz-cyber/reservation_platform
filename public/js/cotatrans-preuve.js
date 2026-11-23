/*
 * CotaTrans · lecture automatique du reçu Wave (montant + ID de transaction).
 *
 * La lecture se fait dans le navigateur. La preuve n'est acceptée que si le montant
 * est exactement celui de la réservation ; l'équipe contrôle toujours la capture.
 */
(function () {
    const TESSERACT_URL = 'https://cdn.jsdelivr.net/npm/tesseract.js@6.0.1/dist/tesseract.min.js';
    const DELAI_MAX_MS = 60000;

    let chargement = null;
    function chargerTesseract() {
        if (window.Tesseract) return Promise.resolve(window.Tesseract);
        if (chargement) return chargement;
        chargement = new Promise((ok, ko) => {
            const s = document.createElement('script');
            s.src = TESSERACT_URL;
            s.async = true;
            s.onload = () => (window.Tesseract ? ok(window.Tesseract) : ko(new Error('Tesseract indisponible')));
            s.onerror = () => { chargement = null; ko(new Error('Chargement de Tesseract impossible')); };
            document.head.appendChild(s);
        });
        return chargement;
    }

    function avecDelai(promesse, ms) {
        return Promise.race([
            promesse,
            new Promise((_, ko) => setTimeout(() => ko(new Error('Délai de lecture dépassé')), ms)),
        ]);
    }

    /** Réduit l'image (plus rapide à envoyer et à lire). */
    async function compresser(fichier) {
        try {
            const img = await createImageBitmap(fichier);
            const echelle = Math.min(1, 1600 / Math.max(img.width, img.height));
            const c = document.createElement('canvas');
            c.width = Math.round(img.width * echelle);
            c.height = Math.round(img.height * echelle);
            c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
            const blob = await new Promise((r) => c.toBlob(r, 'image/jpeg', 0.85));
            return blob ? new File([blob], 'recu.jpg', { type: 'image/jpeg' }) : fichier;
        } catch (e) {
            return fichier;
        }
    }

    /**
     * Extrait montant et ID d'un texte de reçu.
     * Montant : on privilégie le montant attendu, puis un multiple du prix unitaire, puis le plus grand.
     */
    function extraire(texte, attendu, prixUnitaire) {
        const t = (texte || '')
            .replace(/[  ]/g, ' ')
            .replace(/[Oo](?=\d)|(?<=\d)[Oo]/g, '0')
            .replace(/[ \t]+/g, ' ');

        const montants = [];
        const reMontant = /(\d{1,3}(?:[ .,]\d{3})+|\d{3,7})\s*(?:F\s*CFA|FCFA|CFA|F)\b/gi;
        let m;
        while ((m = reMontant.exec(t)) !== null) {
            const v = parseInt(m[1].replace(/[^\d]/g, ''), 10);
            if (v > 0 && v < 10000000) montants.push(v);
        }
        if (!montants.length) {
            const reMot = /(?:montant|total|somme|amount)\s*:?\s*(\d{1,3}(?:[ .,]\d{3})+|\d{3,7})/gi;
            while ((m = reMot.exec(t)) !== null) {
                const v = parseInt(m[1].replace(/[^\d]/g, ''), 10);
                if (v > 0) montants.push(v);
            }
        }

        let montant = null;
        if (montants.length) {
            montant = montants.find((v) => v === attendu)
                ?? montants.find((v) => prixUnitaire > 0 && v % prixUnitaire === 0)
                ?? Math.max(...montants);
        }

        // ID de transaction Wave : « T_XXXXXXXXXX » (l'OCR lit parfois « T-», « T » ou « T_ »).
        let id = null;
        const reWave = /\bT(?:\s?[_\-]\s?|\s)([A-Z0-9]{8,24})\b/g;
        while ((m = reWave.exec(t.toUpperCase())) !== null) {
            if (/\d/.test(m[1]) && /[A-Z]/.test(m[1])) { id = 'T_' + m[1]; break; }
        }
        if (!id) {
            const reMot = /(?:id\s*(?:de)?\s*transaction|transaction\s*id|r[ée]f[ée]rence|ref)\s*[:#]?\s*([A-Z0-9][A-Z0-9_\-]{6,30})/i;
            const r = t.match(reMot);
            if (r) id = r[1].toUpperCase();
        }

        return { montant, id };
    }

    function fcfa(v) {
        return String(v).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' FCFA';
    }

    function composant(config) {
        return {
            attendu: Number(config.attendu) || 0,
            prix: Number(config.prix) || 0,
            apercu: null,
            progress: 0,
            envoi: false,
            etat: 'vide', // vide | lecture | lu | partiel | echec
            lecturePct: 0,
            montantLu: false,
            idLu: false,
            message: '',
            fichier: null,

            async choisir(e) {
                const f = e.target.files && e.target.files[0];
                if (!f) return;
                this.reinitialiser(false);
                this.apercu = URL.createObjectURL(f);
                this.fichier = await compresser(f);
                this.envoyer();
                this.lire();
            },

            envoyer() {
                this.envoi = true;
                this.progress = 0;
                this.$wire.upload('capture', this.fichier,
                    () => { this.envoi = false; this.progress = 100; },
                    () => { this.envoi = false; this.message = "L'envoi de l'image a échoué. Réessaie."; },
                    (ev) => { this.progress = ev.detail.progress; });
            },

            async lire() {
                if (!this.fichier) return;
                this.etat = 'lecture';
                this.lecturePct = 0;
                this.message = '';
                let worker = null;
                try {
                    const T = await avecDelai(chargerTesseract(), 20000);
                    worker = await avecDelai(T.createWorker('fra', 1, {
                        logger: (m) => { if (m.status === 'recognizing text') this.lecturePct = Math.round(m.progress * 100); },
                    }), 30000);
                    const res = await avecDelai(worker.recognize(this.fichier), DELAI_MAX_MS);
                    const { montant, id } = extraire(res.data.text, this.attendu, this.prix);

                    if (montant) { this.$wire.set('montant_declare', montant, false); this.montantLu = true; }
                    if (id) { this.$wire.set('transaction_id', id, false); this.idLu = true; }

                    if (this.montantLu && montant !== this.attendu) {
                        this.etat = 'ecart';
                        this.message = 'Le reçu indique ' + fcfa(montant) + ' mais ta réservation coûte ' + fcfa(this.attendu)
                            + '. Paie le montant exact puis ajoute le nouveau reçu.';
                    } else if (this.montantLu && this.idLu) {
                        this.etat = 'lu';
                    } else if (this.montantLu || this.idLu) {
                        this.etat = 'partiel';
                        this.message = this.montantLu
                            ? "ID de transaction illisible : recopie-le depuis le reçu."
                            : 'Montant illisible : vérifie-le ci-dessous.';
                    } else {
                        this.etat = 'echec';
                        this.message = 'Lecture automatique impossible : saisis l’ID et le montant à la main.';
                    }
                } catch (err) {
                    console.warn('[CotaTrans] lecture du reçu :', err);
                    this.etat = 'echec';
                    this.message = 'Lecture automatique indisponible (' + (err && err.message ? err.message : 'erreur') + '). Saisis l’ID et le montant à la main.';
                } finally {
                    if (worker) { try { await worker.terminate(); } catch (_) {} }
                }
            },

            /** Montant saisi à la main (lecture impossible) : il doit aussi être exact. */
            get montantOk() {
                const v = Number(this.$wire.montant_declare);
                return v === this.attendu;
            },

            relancer() {
                this.montantLu = false;
                this.idLu = false;
                this.lire();
            },

            reinitialiser(viderFichier = true) {
                this.apercu = null;
                this.progress = 0;
                this.envoi = false;
                this.etat = 'vide';
                this.montantLu = false;
                this.idLu = false;
                this.message = '';
                if (viderFichier) {
                    this.fichier = null;
                    this.$wire.set('capture', null, false);
                    if (this.$refs.file) this.$refs.file.value = '';
                }
            },
        };
    }

    document.addEventListener('alpine:init', () => {
        window.Alpine.data('preuvePaiement', composant);
    });

    // Exposé pour les tests.
    window.CotaTransPreuve = { extraire };
})();
