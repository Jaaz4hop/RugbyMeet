<?php
use App\Core\Auth;
//$user vaut null si personne n'est connecté
/** @var ?array $user */
?>
    <header>
        <?php //Logo cliquable : retour à l'accueil ?>
        <a href="<?= e(url('/')) ?>" id="logo"><img src="<?= e(site_logo()) ?>" alt="Logo de RugbyMeet"></a>
        <?php //Bouton burger (affiché seulement sur mobile), son fonctionnement est dans main.js ?>
        <button id="burger" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="nav1">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav aria-label="Navigation Principale" id="nav1">
            <a href="<?= e(url('/')) ?>">ACCUEIL</a>
            <a href="<?= e(url('/calendrier')) ?>">CALENDRIER</a>
            <a href="<?= e(url('/bars')) ?>">BAR</a>
            <a href="<?= e(url('/a-propos')) ?>" class="last-desktop">A PROPOS</a>
<?php //Dans le menu mobile : profil et déconnexion si connecté, sinon connexion et inscription ?>
<?php if ($user): ?>
            <a href="<?= e(Auth::homeUrl()) ?>" class="nav-mobile-only">PROFIL</a>
            <form method="post" action="<?= e(url('/deconnexion')) ?>" class="nav-mobile-only">
                <?= csrf_field() ?>
                <button type="submit">DÉCONNEXION</button>
            </form>
<?php else: ?>
            <a href="<?= e(url('/connexion')) ?>" class="nav-mobile-only">CONNEXION</a>
            <a href="<?= e(url('/inscription')) ?>" class="nav-mobile-only">INSCRIPTION</a>
<?php endif; ?>
        </nav>
        <?php //Barre de recherche : envoie le texte à la page des bars (?q=...) ?>
        <form action="<?= e(url('/bars')) ?>" method="get" role="search" id="searchForm">
            <input type="search" id="searchBar" name="q" placeholder="🔍Rechercher un bar" aria-label="Rechercher un bar" maxlength="100">
        </form>
<?php if ($user): ?>
        <?php //Bouton profil et déconnexion (la déconnexion est un formulaire POST avec jeton CSRF) ?>
        <a href="<?= e(Auth::homeUrl()) ?>"><button id="profil" type="button">PROFIL</button></a>
        <form method="post" action="<?= e(url('/deconnexion')) ?>" class="logout-form">
            <?= csrf_field() ?>
            <button type="submit" class="logout" title="Se déconnecter" aria-label="Se déconnecter"><i class="bi bi-box-arrow-right"></i></button>
        </form>
<?php else: ?>
        <a href="<?= e(url('/connexion')) ?>"><button id="profil" type="button">CONNEXION</button></a>
<?php endif; ?>
    </header>
