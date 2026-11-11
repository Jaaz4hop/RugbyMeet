<?php
//Ces fonctions ne sont pas dans une classe : on peut les appeler partout, y compris dans les vues
// Fonctions utilitaires disponibles partout (vues comprises).

use App\Core\{Auth, Csrf, Session};

/** Échappe une valeur pour l'afficher en HTML (protection XSS). */
//Échappe le texte avant de l'afficher dans la page : si quelqu'un écrit du code HTML ou JavaScript dans un formulaire, il ne sera pas exécuté (protection contre les failles XSS)
function e(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Dossier de base de l'application (vide si l'hôte pointe sur public/). */
//Retourne le dossier du site dans l'URL (ex : /rugbymeet/public) ou vide si le site est à la racine
//Ainsi les liens fonctionnent dans les deux cas
function base_path(): string {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
}

/** URL d'une page : url('/calendrier'). */
//Construit l'adresse complète d'une page
function url(string $path = '/'): string {
    return base_path() . '/' . ltrim($path, '/');
}

/** URL d'un fichier statique de public/ : asset('css/accueil.css'). */
//Construit l'adresse d'un fichier de public/ (image, css, js)
//rawurlencode remplace les espaces des noms de fichiers par %20
function asset(string $path): string {
    $segments = array_map('rawurlencode', explode('/', ltrim($path, '/')));   // "Logo Rugby Meet.png" -> espaces encodés
    return base_path() . '/' . implode('/', $segments);
}

/** Logo du site : version aux couleurs du club de coeur si elle existe, sinon logo par défaut. */
//Choisit le logo à afficher : celui aux couleurs du Stade Toulousain si l'utilisateur l'a choisi comme club favori, sinon le logo par défaut
function site_logo(): string {
    $themed  = ['stade-toulousain' => 'Images/logo-rugby-meet-ST.png'];
    $theme   = (string) (Auth::user()['club_theme'] ?? '');
    $default = ['Images/Logo Rugby Meet.png', 'Images/logo-Rugby-Meet.png', 'Images/logo-rugby-meet.png'];

    $candidates = array_filter([$themed[$theme] ?? '', ...$default]);
    foreach ($candidates as $file) {
        if (is_file(dirname(__DIR__, 2) . '/public/' . $file)) {
            return asset($file);
        }
    }
    return asset($default[0]);
}

//Champ caché avec le jeton CSRF, à mettre dans chaque formulaire POST
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Valeur saisie avant une erreur de validation (sinon valeur par défaut). */
//Réaffiche ce que l'utilisateur avait saisi après une erreur (ou une valeur par défaut)
function old(string $key, mixed $default = ''): string {
    global $__form;
    return e($__form['old'][$key] ?? $default);
}

//Affiche le message d'erreur d'un champ s'il y en a un
function field_error(string $key): string {
    global $__form;
    return isset($__form['errors'][$key])
        ? '<p class="field-error" role="alert">' . e($__form['errors'][$key]) . '</p>'
        : '';
}

//Vrai si le formulaire a au moins une erreur
function has_errors(): bool {
    global $__form;
    return !empty($__form['errors']);
}

//Retourne toutes les erreurs du formulaire
function form_errors(): array {
    global $__form;
    return $__form['errors'] ?? [];
}

//Fonctions pour afficher les dates et heures à la française
// ----- Formats français -----

const JOURS_FR = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

/** '2026-09-19' -> 'Samedi 19/09' */
//Transforme une date de la base (2026-09-19) en 'Samedi 19/09'
function fr_date(string $iso): string {
    $d = DateTime::createFromFormat('Y-m-d', substr($iso, 0, 10));
    return JOURS_FR[(int) $d->format('w')] . ' ' . $d->format('d/m');
}

/** '14:30:00' -> '14h30' ; NULL -> 'Horaire à venir' */
//Transforme une heure (14:30:00) en 14h30. Si l'heure est inconnue : 'Horaire à venir'
function fr_time(?string $time): string {
    return $time === null || $time === '' ? 'Horaire à venir' : str_replace(':', 'h', substr($time, 0, 5));
}

/** '2026-09-19 20:45:00' -> 'Samedi 19/09 · 20h45' */
//Date et heure ensemble
function fr_datetime(string $dt): string {
    return fr_date($dt) . ' · ' . fr_time(substr($dt, 11, 5));
}

//Accorde le mot au pluriel : plural(2, 'match') donne '2 matchs'
function plural(int $n, string $singular, ?string $plural = null): string {
    return $n . ' ' . ($n > 1 ? ($plural ?? $singular . 's') : $singular);
}
