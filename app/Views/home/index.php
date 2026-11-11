        <?php //Image de bannière ?>
        <section id="banner">
            <img src="<?= e(asset('Images/fan-zone-sport_1.png')) ?>" alt="Bannière Rugby-meet">
        </section>
        <section id="titles">
            <h1>LES MATCHS</h1>
            <div>
                <h2>TOP 14</h2>
            </div>
        </section>
        <?php //Titre : numéro de la journée affichée ?>
        <h3 id="journeeTitle"><?= $journee ? 'Journée ' . (int) $journee : 'Prochaine journée' ?></h3>
        <?php //Message affiché si la table est vide ou s'il n'y a plus de match à venir ?>
        <p id="journeeNote" class="note" aria-live="polite"><?php
            if ($journee === null) {
                echo 'Le calendrier de la saison arrive bientôt.';
            } elseif (!$upcoming) {
                echo 'Aucun match à venir pour le moment : dernière journée connue.';
            }
        ?></p>
        <?php //On affiche une carte pour chaque match de la journée (match_card.php est un morceau de vue réutilisable, il utilise la variable $m) ?>
        <section id="matchCards" aria-label="Matchs de la prochaine journée">
<?php foreach ($matches as $m): require ROOT . '/app/Views/partials/match_card.php'; endforeach; ?>
        </section>
