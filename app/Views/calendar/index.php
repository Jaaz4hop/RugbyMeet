        <section id="titles">
            <h1>CALENDRIER</h1>
            <div><h2>TOP 14</h2></div>
        </section>
        <?php //Filtres envoyés en GET : les choix apparaissent dans l'URL (?journee=3&club=5), on peut donc partager le lien ?>
        <form id="filters" method="get" action="<?= e(url('/calendrier')) ?>" aria-label="Filtrer les matchs">
            <div class="filter">
                <label for="filterJournee">Journée</label>
                <select id="filterJournee" name="journee">
                    <option value="">Toutes les journées</option>
<?php //Une option par journée, 'selected' garde le choix actuel ?>
<?php foreach ($journees as $j): ?>
                    <option value="<?= (int) $j ?>"<?= $selJournee === $j ? ' selected' : '' ?>>Journée <?= (int) $j ?></option>
<?php endforeach; ?>
                </select>
            </div>
            <div class="filter">
                <label for="filterClub">Club</label>
                <select id="filterClub" name="club">
                    <option value="">Tous les clubs</option>
<?php //Une option par club ?>
<?php foreach ($clubs as $c): ?>
                    <option value="<?= (int) $c['id_club'] ?>"<?= $selClub === (int) $c['id_club'] ? ' selected' : '' ?>><?= e($c['nom']) ?></option>
<?php endforeach; ?>
                </select>
            </div>
            <?php //Le bouton envoie le formulaire, le lien Réinitialiser revient à la page sans filtre ?>
            <button type="submit" class="btn-filter">Filtrer</button>
            <a href="<?= e(url('/calendrier')) ?>" class="btn-filter">Réinitialiser</a>
        </form>
        <?php //Nombre de matchs trouvés (plural accorde au pluriel) ?>
        <p id="resultCount" class="note" aria-live="polite"><?= e(plural($count, 'match') . ' ' . ($count > 1 ? 'trouvés' : 'trouvé')) ?></p>
        <div id="calendarList">
<?php if (!$byJournee): ?>
            <p class="empty">Aucun match ne correspond à ta recherche.</p>
<?php endif; ?>
<?php //Les matchs sont groupés par journée ?>
<?php foreach ($byJournee as $num => $list): ?>
            <section class="journee-group">
                <h3>Journée <?= (int) $num ?></h3>
                <div class="match-cards">
<?php foreach ($list as $m): require ROOT . '/app/Views/partials/match_card.php'; endforeach; ?>
                </div>
            </section>
<?php endforeach; ?>
        </div>
