<?php /** @var array $m une rencontre (voir RencontreModel) */ ?>
      <?php //Carte d'un match : $m est une ligne retournée par RencontreModel (date, heure, noms et logos des deux clubs) ?>
      <div class="cards">
        <div class="topCard"><h4><?= e(fr_date($m['date_match'])) ?></h4></div>
        <div class="bodyCard">
          <div class="home-date-away">
            <i class="bi bi-house" title="Domicile"></i>
            <?php //Si l'heure est inconnue (NULL), on ajoute la classe tbd pour un style différent ?>
            <h5<?= $m['heure_coup_envoi'] === null ? ' class="tbd"' : '' ?>><?= e(fr_time($m['heure_coup_envoi'])) ?></h5>
            <i class="bi bi-airplane" title="Extérieur"></i>
          </div>
          <div class="match-teams">
            <div class="home-team">
              <h6><?= e($m['domicile_abr']) ?></h6>
              <img src="<?= e(asset($m['domicile_logo'])) ?>" alt="Logo <?= e($m['domicile_nom']) ?>">
            </div>
            <div class="away-team">
              <h6><?= e($m['exterieur_abr']) ?></h6>
              <img src="<?= e(asset($m['exterieur_logo'])) ?>" alt="Logo <?= e($m['exterieur_nom']) ?>">
            </div>
          </div>
        </div>
      </div>
