<?php
use App\Core\Auth;
//Le supporter a une ville et un club favori, l'admin n'a qu'un profil simple
$isSupporter = (int) $user['id_role'] === Auth::ROLE_SUPPORTER;
?>
        <div id="titles">
            <h1>TON PROFIL</h1>
        </div>

        <div class="content narrow">
            <section class="panel" id="personal-info">
                <h2>Tes infos</h2>
                <?php //Formulaire des infos personnelles. old('name', valeur) réaffiche la saisie après une erreur, sinon la valeur actuelle de la base ?>
                <form method="post" action="<?= e(url('/profil/infos')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="name">Nom du profil :</label>
                        <input type="text" id="name" name="name" required maxlength="50" value="<?= old('name', $user['nom_profil']) ?>">
                        <?= field_error('name') ?>
                    </div>
                    <div class="field">
                        <label for="mail">Mail :</label>
                        <input type="email" id="mail" name="email" required maxlength="255" value="<?= old('email', $user['email']) ?>">
                        <?= field_error('email') ?>
                    </div>
<?php //Ville et club : réservés aux supporters ?>
<?php if ($isSupporter): ?>
                    <div class="field">
                        <label for="city">Ta ville :</label>
                        <input type="text" id="city" name="city" maxlength="100" value="<?= old('city', $user['ville'] ?? '') ?>">
                        <?= field_error('city') ?>
                    </div>
                    <p>Ton club : <strong><?= e($user['club_nom'] ?? 'aucun pour le moment') ?></strong></p>
<?php else: ?>
                    <p>Compte administrateur.</p>
<?php endif; ?>
                    <div class="form-actions"><button type="submit" class="btn-save">Modifier</button></div>
                </form>
            </section>

            <?php //Changement de mot de passe : il faut saisir l'ancien ?>
            <section class="panel" id="password-info">
                <h2>Mot de passe</h2>
                <form method="post" action="<?= e(url('/profil/mot-de-passe')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="mdp-actuel">Mot de passe actuel :</label>
                        <input type="password" id="mdp-actuel" name="current_password" required autocomplete="current-password">
                        <?= field_error('current_password') ?>
                    </div>
                    <div class="field">
                        <label for="mdp-new">Nouveau mot de passe <small>(8 caractères minimum)</small> :</label>
                        <input type="password" id="mdp-new" name="new_password" required minlength="8" autocomplete="new-password">
                        <?= field_error('new_password') ?>
                    </div>
                    <div class="field">
                        <label for="mdp-new2">Confirme le nouveau mot de passe :</label>
                        <input type="password" id="mdp-new2" name="new_password_confirm" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="form-actions"><button type="submit" class="btn-save">Changer</button></div>
                </form>
            </section>

<?php if ($isSupporter): ?>
            <?php //Choix du club favori : un bouton radio par club (le premier = aucun club) ?>
            <section class="panel" id="club-selector">
                <h2>Ton club favori</h2>
                <p>Choisis ton club favori pour adopter ses couleurs !</p>
                <p class="hint">Les couleurs du site changent si ton club dispose d'un thème accessible.</p>
                <form method="post" action="<?= e(url('/profil/club')) ?>">
                    <?= csrf_field() ?>
                    <div class="club-grid">
                        <label class="club-option">
                            <span class="no-club">Aucun</span>
                            <input type="radio" class="switch" name="id_club" value=""<?= $user['id_club'] === null ? ' checked' : '' ?>>
                        </label>
<?php foreach ($clubs as $c): ?>
                        <label class="club-option">
                            <img src="<?= e(asset($c['logo'])) ?>" alt="<?= e($c['nom']) ?>">
                            <input type="radio" class="switch" name="id_club" value="<?= (int) $c['id_club'] ?>"<?= (int) $user['id_club'] === (int) $c['id_club'] ? ' checked' : '' ?>>
                        </label>
<?php endforeach; ?>
                    </div>
                    <div class="form-actions"><button type="submit" class="btn-save">Enregistrer mon club</button></div>
                </form>
            </section>

            <?php //Liste des bars favoris avec un bouton pour les retirer ?>
            <section class="panel" id="favorites">
                <h2>Tes bars favoris</h2>
<?php if (!$favorites): ?>
                <p>Aucun bar favori. <a href="<?= e(url('/bars')) ?>">Trouve un bar</a> et clique sur le coeur.</p>
<?php else: ?>
                <ul class="item-list">
<?php foreach ($favorites as $b): ?>
                    <li>
                        <span><i class="bi bi-heart-fill"></i> <?= e($b['nom']) ?> <small>(<?= e($b['ville']) ?>)</small></span>
                        <form method="post" action="<?= e(url('/bars/favori')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_bar" value="<?= (int) $b['id_bar'] ?>">
                            <input type="hidden" name="back" value="<?= e(url('/profil')) ?>">
                            <button type="submit" class="btn-link" aria-label="Retirer <?= e($b['nom']) ?> des favoris">Retirer</button>
                        </form>
                    </li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
            </section>
<?php endif; ?>
        </div>
