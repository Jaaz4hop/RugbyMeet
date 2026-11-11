<?php
use App\Core\Auth;

//Début commun à toutes les pages (head, haut de page, messages) : le contrôleur l'inclut avant la vue, puis inclut bottom.php après
//Utilisateur connecté (ou null)
$user = Auth::user();
$theme = $user['club_theme'] ?? null;                    // thème du club de coeur (si un thème conforme WCAG existe)
//Fichiers css et js supplémentaires demandés par le contrôleur (tableau vide par défaut)
$styles = $styles ?? [];
$scripts = $scripts ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php //Titre de l'onglet (e() échappe le texte pour éviter les failles XSS) ?>
    <title>RugbyMeet - <?= e($title ?? '') ?></title>
<?php //Les css communs sont chargés sur toutes les pages, puis ceux propres à la page ?>
<?php foreach (array_merge(['style.css', 'base.css', 'components.css'], $styles) as $css): ?>
    <link rel="stylesheet" href="<?= e(asset('css/' . $css)) ?>">
<?php endforeach; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <?php //main.js (menu burger) est chargé partout, les autres scripts seulement si la page les demande ?>
    <script src="<?= e(asset('js/main.js')) ?>" defer></script>
<?php foreach ($scripts as $js): ?>
    <script src="<?= e(asset('js/' . $js)) ?>" defer></script>
<?php endforeach; ?>
</head>
<?php //Si l'utilisateur a un club avec un thème (ex : Stade Toulousain), on ajoute data-theme : le css change alors les couleurs ?>
<body<?= $theme ? ' data-theme="' . e($theme) . '"' : '' ?>>
<?php //Haut de page (logo, menu, recherche, connexion) ?>
<?php require ROOT . '/app/Views/partials/header.php'; ?>
    <main>
<?php //Messages flash (succès ou erreur) affichés une seule fois ?>
<?php if (!empty($flash)): ?>
        <div class="flash-area">
<?php foreach ($flash as $f): ?>
            <p class="flash flash-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($f['message']) ?></p>
<?php endforeach; ?>
        </div>
<?php endif; ?>
