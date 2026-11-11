<?php
use App\Core\Auth;
//Qui regarde la page ? Un supporter connecté peut utiliser les favoris, un visiteur est invité à se connecter
$isSupporter = Auth::isRole(Auth::ROLE_SUPPORTER);
$isVisitor = !Auth::check();
//Adresse de la page actuelle, envoyée au formulaire des favoris pour revenir ici ensuite
$self = $_SERVER['REQUEST_URI'] ?? url('/bars');
?>
        <section id="titles">
            <h1>LES BARS</h1>
            <div><h2>OÙ REGARDER LE MATCH ?</h2></div>
        </section>
        <form id="filters" method="get" action="<?= e(url('/bars')) ?>" aria-label="Filtrer les bars">
<?php //On garde le texte tapé dans la barre de recherche du header quand on utilise les filtres ?>
<?php if ($filters['q'] !== ''): ?>
            <input type="hidden" name="q" value="<?= e($filters['q']) ?>">
<?php endif; ?>
            <div class="filter">
                <label for="filterCity">Ville</label>
                <select id="filterCity" name="ville">
                    <option value="">Toutes les villes</option>
<?php //Une option par ville ('selected' garde le choix actuel) ?>
<?php foreach ($cities as $c): ?>
                    <option value="<?= e($c) ?>"<?= $filters['ville'] === $c ? ' selected' : '' ?>><?= e($c) ?></option>
<?php endforeach; ?>
                </select>
            </div>
            <div class="filter">
                <label for="filterTeam">Club diffusé</label>
                <select id="filterTeam" name="club">
                    <option value="">Tous les clubs</option>
<?php foreach ($clubs as $c): ?>
                    <option value="<?= (int) $c['id_club'] ?>"<?= $filters['club'] === (int) $c['id_club'] ? ' selected' : '' ?>><?= e($c['nom']) ?></option>
<?php endforeach; ?>
                </select>
            </div>
            <?php //Cases à cocher : 'checked' si le filtre est actif ?>
            <fieldset class="filter-options">
                <legend>Équipements</legend>
                <label><input type="checkbox" id="optFood" name="restauration" value="1"<?= $filters['restauration'] ? ' checked' : '' ?>> Restauration</label>
                <label><input type="checkbox" id="optBig" name="ecran_geant" value="1"<?= $filters['ecran_geant'] ? ' checked' : '' ?>> Écran géant</label>
                <label><input type="checkbox" id="optPmr" name="acces_pmr" value="1"<?= $filters['acces_pmr'] ? ' checked' : '' ?>> Accès PMR</label>
            </fieldset>
            <button type="submit" class="btn-filter">Filtrer</button>
            <a href="<?= e(url('/bars')) ?>" class="btn-filter">Réinitialiser</a>
        </form>
        <?php //Nombre de bars trouvés ?>
        <p id="resultCount" class="note" aria-live="polite"><?= e(plural(count($bars), 'bar') . ' ' . (count($bars) > 1 ? 'trouvés' : 'trouvé')) ?><?= $filters['q'] !== '' ? ' pour « ' . e($filters['q']) . ' »' : '' ?></p>
        <?php //Liste des bars : une carte par bar ?>
        <section id="barList" aria-label="Liste des bars">
<?php if (!$bars): ?>
            <p class="empty">Aucun bar ne correspond à ta recherche.</p>
<?php endif; ?>
<?php //$isFav vaut vrai si ce bar est dans les favoris de l'utilisateur ?>
<?php foreach ($bars as $b): $isFav = in_array((int) $b['id_bar'], $favorites, true); ?>
            <article class="bar-card">
<?php //La photo est facultative ?>
<?php if ($b['photo']): ?>
              <img class="bar-photo" src="<?= e(asset($b['photo'])) ?>" alt="Photo du bar <?= e($b['nom']) ?>" loading="lazy">
<?php endif; ?>
              <div class="bar-head">
                <div>
                  <h3><?= e($b['nom']) ?></h3>
                  <p class="bar-city"><i class="bi bi-geo-alt"></i> <?= e($b['ville']) ?></p>
                </div>
<?php //Visiteur : le coeur mène à la page de connexion. Supporter : formulaire POST pour ajouter ou retirer le favori. Gérant : rien ?>
<?php if ($isVisitor): ?>
                <a class="fav" href="<?= e(url('/connexion')) ?>" title="Connecte-toi pour ajouter ce bar à tes favoris" aria-label="Se connecter pour ajouter aux favoris"><i class="bi bi-heart"></i></a>
<?php elseif ($isSupporter): ?>
                <form method="post" action="<?= e(url('/bars/favori')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id_bar" value="<?= (int) $b['id_bar'] ?>">
                  <input type="hidden" name="back" value="<?= e($self) ?>">
                  <button type="submit" class="fav<?= $isFav ? ' is-fav' : '' ?>" aria-pressed="<?= $isFav ? 'true' : 'false' ?>" aria-label="<?= $isFav ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>"><i class="bi bi-heart<?= $isFav ? '-fill' : '' ?>"></i></button>
                </form>
<?php endif; ?>
              </div>
<?php //nl2br garde les retours à la ligne de la description (après échappement par e()) ?>
<?php if ($b['description']): ?>
              <p class="bar-desc"><?= nl2br(e($b['description'])) ?></p>
<?php endif; ?>
              <?php //Équipements du bar : écrans, écran géant, restauration, accès PMR ?>
              <ul class="badges" aria-label="Équipements">
                <li><i class="bi bi-display"></i> <?= e(plural((int) $b['nb_ecrans'], 'écran')) ?></li>
<?php if ($b['ecran_geant']): ?>                <li><i class="bi bi-tv"></i> Écran géant</li>
<?php endif; if ($b['restauration']): ?>                <li><i class="bi bi-egg-fried"></i> Restauration</li>
<?php endif; if ($b['acces_pmr']): ?>                <li><i class="bi bi-person-wheelchair"></i> Accès PMR</li>
<?php endif; ?>
              </ul>
              <?php //Matchs diffusés à venir dans ce bar ?>
              <h4>Diffusions</h4>
              <ul class="bar-events">
<?php foreach ($events[(int) $b['id_bar']] ?? [] as $ev): ?>
                <li class="bar-event">
                  <span class="ev-date"><?= e(fr_datetime($ev['date_heure_diffusion'])) ?></span>
                  <span class="ev-teams">
                    <img src="<?= e(asset($ev['domicile_logo'])) ?>" alt=""><?= e($ev['domicile_abr']) ?>
                    <b>vs</b>
                    <?= e($ev['exterieur_abr']) ?><img src="<?= e(asset($ev['exterieur_logo'])) ?>" alt="">
                  </span>
<?php if ($ev['infos_complementaires']): ?>
                  <span class="ev-note"><?= e($ev['infos_complementaires']) ?></span>
<?php endif; ?>
                </li>
<?php endforeach; ?>
<?php //Message si le bar n'a aucune diffusion à venir ?>
<?php if (empty($events[(int) $b['id_bar']])): ?>
                <li class="ev-none">Aucune diffusion programmée.</li>
<?php endif; ?>
              </ul>
              <?php //Adresse et téléphone (si renseigné) ?>
              <p class="bar-contact"><i class="bi bi-signpost"></i> <?= e($b['adresse'] . ', ' . $b['code_postal'] . ' ' . $b['ville']) ?><?php if ($b['telephone']): ?><br><i class="bi bi-telephone"></i> <?= e($b['telephone']) ?><?php endif; ?></p>
            </article>
<?php endforeach; ?>
        </section>
