/**
 * assets/js/validation.js
 * Validation des formulaires côté navigateur (Input Validation).
 *
 * Utilisation dans n'importe quel formulaire du projet :
 *   <form class="js-validate" novalidate> ...
 *   <input name="prix" data-rules="required|number|min:0.1">
 *   <small class="field-error"></small>   (dans le même bloc .field que le champ)
 *
 * Règles disponibles : required, name, email, phone, password, password? (facultatif),
 * match:autreChamp, checked, number, integer, min:x, max:x, minlen:x, maxlen:x,
 * date, futuredate, after:autreChampDate.
 *
 * IMPORTANT : ces contrôles améliorent le confort, mais le PHP refait toujours
 * les mêmes vérifications côté serveur (le JavaScript peut être désactivé).
 */
(function () {
    'use strict';

    const MESSAGES = {
        required: 'Ce champ est obligatoire.',
        name: '2 à 50 lettres (espaces, tirets et apostrophes acceptés).',
        email: 'Adresse e-mail invalide.',
        phone: '8 chiffres (ex : 22 123 456 ou +216 22 123 456).',
        password: '8 caractères minimum, avec une majuscule, une minuscule et un chiffre.',
        match: 'Les deux valeurs ne correspondent pas.',
        checked: 'Vous devez cocher cette case.',
        number: 'Veuillez saisir un nombre.',
        integer: 'Veuillez saisir un nombre entier.',
        min: (x) => 'La valeur doit être supérieure ou égale à ' + x + '.',
        max: (x) => 'La valeur doit être inférieure ou égale à ' + x + '.',
        minlen: (x) => 'Au moins ' + x + ' caractères.',
        maxlen: (x) => 'Au maximum ' + x + ' caractères.',
        date: 'Date invalide.',
        futuredate: 'La date doit être aujourd’hui ou plus tard.',
        after: 'Cette date doit être postérieure à la date de début.'
    };

    const NAME_RE = /^[A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ '\-]{1,49}$/;
    const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    const PHONE_RE = /^(\+216)?[2-9]\d{7}$/;

    function strongPassword(v) {
        return v.length >= 8 && v.length <= 72 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /\d/.test(v);
    }

    /** Renvoie le message d'erreur du champ, ou '' s'il est valide. */
    function check(input, form) {
        const rules = (input.dataset.rules || '').split('|').filter(Boolean);
        const value = input.type === 'checkbox' ? '' : input.value.trim();

        for (const rule of rules) {
            const [name, arg] = rule.split(':');
            const empty = value === '';

            if (name === 'required' && empty) return MESSAGES.required;
            if (name === 'checked' && !input.checked) return MESSAGES.checked;
            if (empty && name !== 'match') continue; // les autres règles ne s'appliquent qu'à un champ rempli

            switch (name) {
                case 'name': if (!NAME_RE.test(value)) return MESSAGES.name; break;
                case 'email': if (!EMAIL_RE.test(value) || value.length > 150) return MESSAGES.email; break;
                case 'phone': if (!PHONE_RE.test(value.replace(/[\s.\-]/g, ''))) return MESSAGES.phone; break;
                case 'password':
                case 'password?': if (!strongPassword(input.value)) return MESSAGES.password; break;
                case 'match': {
                    const other = form.querySelector('[name="' + arg + '"]');
                    if (other && other.value !== input.value) return MESSAGES.match;
                    break;
                }
                case 'number': if (isNaN(Number(value))) return MESSAGES.number; break;
                case 'integer': if (!/^-?\d+$/.test(value)) return MESSAGES.integer; break;
                case 'min': if (Number(value) < Number(arg)) return MESSAGES.min(arg); break;
                case 'max': if (Number(value) > Number(arg)) return MESSAGES.max(arg); break;
                case 'minlen': if (value.length < Number(arg)) return MESSAGES.minlen(arg); break;
                case 'maxlen': if (value.length > Number(arg)) return MESSAGES.maxlen(arg); break;
                case 'date': if (isNaN(Date.parse(value))) return MESSAGES.date; break;
                case 'futuredate': {
                    const today = new Date(); today.setHours(0, 0, 0, 0);
                    if (new Date(value) < today) return MESSAGES.futuredate;
                    break;
                }
                case 'after': {
                    const start = form.querySelector('[name="' + arg + '"]');
                    if (start && start.value && new Date(value) <= new Date(start.value)) return MESSAGES.after;
                    break;
                }
            }
        }
        return '';
    }

    function show(input, message) {
        const field = input.closest('.field') || input.parentElement;
        const slot = field.querySelector('.field-error');
        input.classList.toggle('is-invalid', message !== '');
        input.setAttribute('aria-invalid', message !== '' ? 'true' : 'false');
        if (slot) slot.textContent = message;
    }

    function validateForm(form) {
        let firstInvalid = null;
        form.querySelectorAll('[data-rules]').forEach((input) => {
            const message = check(input, form);
            show(input, message);
            if (message && !firstInvalid) firstInvalid = input;
        });
        if (firstInvalid) firstInvalid.focus();
        return firstInvalid === null;
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form.js-validate').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!validateForm(form)) event.preventDefault();
            });
            // Vérification au fil de la saisie, une fois le champ quitté
            form.querySelectorAll('[data-rules]').forEach((input) => {
                const live = () => show(input, check(input, form));
                input.addEventListener('blur', live);
                input.addEventListener('change', live);
                input.addEventListener('input', () => {
                    if (input.classList.contains('is-invalid')) live();
                });
            });
        });

        // Confirmation avant une action destructrice (ex : suppression)
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.confirm(form.dataset.confirm)) event.preventDefault();
            });
        });
    });
})();
