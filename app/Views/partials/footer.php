    <footer>
        <?php //Liens vers les réseaux sociaux (pas encore renseignés) ?>
        <div id="RS">
            <h4>RESEAUX SOCIAUX :</h4>
            <a href="#"><i class="bi bi-instagram"></i></a>
            <a href="#"><i class="bi bi-reddit"></i></a>
            <a href="#"><i class="bi bi-facebook"></i></a>
        </div>
        <?php //Même logo que dans le header (change avec le thème du club) ?>
        <div id="footer-logo"><img src="<?= e(site_logo()) ?>" alt="Logo de RugbyMeet"></div>
        <nav aria-label="navigation secondaire" id="nav2">
            <a href="<?= e(url('/contact')) ?>">Contact</a>
            <a href="<?= e(url('/mentions-legales')) ?>">Mentions légales</a>
        </nav>
        <div class="lang-flags">🇫🇷 🇬🇧 🇪🇸 🇮🇹</div>
    </footer>
