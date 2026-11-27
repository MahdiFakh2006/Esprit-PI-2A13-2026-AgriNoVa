/**
 * assets/js/diagnostic.js — Module 3 : aperçu de l'analyse d'une feuille, AVANT l'envoi.
 *   - contrôle de la photo (type JPG/PNG, 5 Mo maximum) ;
 *   - carte des taches : les pixels malades sont coloriés sur une copie de la photo ;
 *   - pourcentages par couleur.
 * Mêmes seuils que AnalyseController::classerPixel() : le SERVEUR refait toujours l'analyse,
 * ce script ne sert qu'au confort de l'utilisateur.
 */
(function () {
    'use strict';

    const MAX_SIZE = 5 * 1024 * 1024;
    const TYPES = ['image/jpeg', 'image/png'];
    const COULEURS = {            // couleur utilisée sur la carte des taches
        jaune: [241, 196, 15],
        brun: [192, 57, 43],
        blanc: [52, 152, 219],
        noir: [142, 68, 173],
    };
    const LIBELLES = { sain: 'Vert (sain)', jaune: 'Taches jaunes', brun: 'Taches brunes', blanc: 'Feutrage blanc', noir: 'Dépôt noir' };

    const form = document.getElementById('diag-form');
    const input = document.getElementById('image');
    if (!form || !input) return;
    const apercu = document.getElementById('diag-preview');
    const photo = document.getElementById('diag-photo');
    const carte = document.getElementById('diag-carte');
    const barres = document.getElementById('diag-barres');

    /** Même classement que le PHP (teinte, saturation, luminosité). */
    function classerPixel(r, g, b) {
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        const v = max / 255, s = max === 0 ? 0 : (max - min) / max;
        let h = 0;
        if (max !== min) {
            if (max === r) h = (60 * ((g - b) / (max - min)) + 360) % 360;
            else if (max === g) h = 60 * ((b - r) / (max - min)) + 120;
            else h = 60 * ((r - g) / (max - min)) + 240;
        }
        if (v < 0.22) return 'noir';
        if (v > 0.78 && s < 0.18) return 'blanc';
        if (h >= 70 && h <= 170 && s > 0.18) return 'sain';
        if (h >= 42 && h < 70 && s > 0.30 && v > 0.40) return 'jaune';
        if ((h < 42 || h >= 330) && s > 0.20 && v <= 0.80) return 'brun';
        return 'autre';
    }

    function erreur(message) {
        const bloc = input.closest('.field');
        const zone = bloc && bloc.querySelector('.field-error');
        if (zone) zone.textContent = message;
        input.classList.toggle('is-invalid', message !== '');
    }

    function verifierFichier() {
        const f = input.files[0];
        if (!f) return true;
        if (!TYPES.includes(f.type)) { erreur('Format non accepté : uniquement JPG ou PNG.'); return false; }
        if (f.size > MAX_SIZE) { erreur('Image trop lourde : 5 Mo maximum.'); return false; }
        erreur('');
        return true;
    }

    form.addEventListener('submit', (e) => { if (!verifierFichier()) e.preventDefault(); });

    input.addEventListener('change', () => {
        apercu.hidden = true;
        if (!input.files[0] || !verifierFichier()) return;
        const lecteur = new FileReader();          // data: URL (autorisée par la politique de sécurité CSP)
        lecteur.onload = () => { photo.onload = analyser; photo.src = lecteur.result; };
        lecteur.readAsDataURL(input.files[0]);
    });

    function analyser() {
        const largeur = 160;
        const hauteur = Math.max(1, Math.round(photo.naturalHeight * largeur / photo.naturalWidth));
        carte.width = largeur;
        carte.height = hauteur;
        const ctx = carte.getContext('2d');
        ctx.drawImage(photo, 0, 0, largeur, hauteur);
        const img = ctx.getImageData(0, 0, largeur, hauteur);
        const px = img.data;
        const compte = { sain: 0, jaune: 0, brun: 0, blanc: 0, noir: 0 };

        for (let y = 0; y < hauteur; y++) {
            const ligne = [];
            let premier = -1, dernier = -1;
            for (let x = 0; x < largeur; x++) {
                const i = (y * largeur + x) * 4;
                const c = classerPixel(px[i], px[i + 1], px[i + 2]);
                ligne.push(c);
                if (c === 'sain' || c === 'jaune' || c === 'brun') {
                    if (premier < 0) premier = x;
                    dernier = x;
                }
            }
            for (let x = 0; x < largeur; x++) {
                const i = (y * largeur + x) * 4;
                const dedans = premier >= 0 && x >= premier && x <= dernier;
                const c = ligne[x];
                if (dedans && c in compte) compte[c]++;
                if (dedans && COULEURS[c]) {
                    [px[i], px[i + 1], px[i + 2]] = COULEURS[c];          // tache : couleur vive
                } else {
                    const gris = (px[i] + px[i + 1] + px[i + 2]) / 3;      // le reste en gris clair
                    px[i] = px[i + 1] = px[i + 2] = 150 + gris * 0.4;
                }
            }
        }
        ctx.putImageData(img, 0, 0);

        const feuille = Object.values(compte).reduce((a, b) => a + b, 0);
        barres.innerHTML = '';
        if (feuille / (largeur * hauteur) < 0.10) {
            barres.innerHTML = '<p class="alert alert-error">Aucune feuille détectée : prenez la photo plus près, sur un fond uni.</p>';
        } else {
            const atteinte = 100 * (feuille - compte.sain) / feuille;
            const titre = document.createElement('p');
            titre.innerHTML = 'Feuille touchée : <b>' + atteinte.toFixed(1).replace('.', ',') + ' %</b>';
            barres.appendChild(titre);
            for (const c of Object.keys(compte)) {
                const pct = 100 * compte[c] / feuille;
                const row = document.createElement('div');
                row.className = 'bar-row';
                const couleur = COULEURS[c] ? 'rgb(' + COULEURS[c].join(',') + ')' : 'var(--green)';
                row.innerHTML = '<span class="bar-label"></span><span class="bar-track"><span class="bar-fill"></span></span><span class="bar-value"></span>';
                row.querySelector('.bar-label').textContent = LIBELLES[c];
                row.querySelector('.bar-fill').style.cssText = 'width:' + pct.toFixed(1) + '%;background:' + couleur;
                row.querySelector('.bar-value').textContent = pct.toFixed(0) + ' %';
                barres.appendChild(row);
            }
        }
        apercu.hidden = false;
    }
})();
