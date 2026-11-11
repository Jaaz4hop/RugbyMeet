        <?php //Page du gérant : $user, $bar, $events et $matches sont envoyés par ManagerController::show() ?>
        <div id="titles">
            <h1>PROFIL GÉRANT</h1>
        </div>

        <div class="content narrow">
            <section class="panel" id="personal-info">
                <h2>Tes infos</h2>
                <?php //Infos personnelles et mot de passe : mêmes formulaires que pour le supporter (ProfileController) ?>
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
                    <div class="form-actions"><button type="submit" class="btn-save">Modifier</button></div>
                </form>
            </section>

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

            <section class="panel" id="bar-info">
                <h2>Ton bar</h2>
                <?php //Formulaire du bar. enctype="multipart/form-data" est obligatoire pour envoyer un fichier (la photo) ?>
                <form method="post" action="<?= e(url('/gerant/bar')) ?>" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>
<?php //Si le bar a déjà une photo, on l'affiche avec une case pour la supprimer ?>
<?php if ($bar['photo']): ?>
                    <div class="photo-current">
                        <img src="<?= e(asset($bar['photo'])) ?>" alt="Photo actuelle de ton bar">
                        <label><input type="checkbox" name="remove_photo" value="1"> Supprimer la photo</label>
                    </div>
<?php endif; ?>
                    <div class="field">
                        <label for="bar-photo"><?= $bar['photo'] ? 'Remplacer la photo' : 'Photo du bar' ?> <small>(JPG, PNG ou WebP, 2 Mo max)</small> :</label>
                        <?php //Champ d'envoi de la photo. accept filtre seulement dans le navigateur : PHP revérifie le fichier (ManagerController::savePhoto) ?>
                        <input type="file" id="bar-photo" name="bar_photo" accept="image/jpeg,image/png,image/webp">
                        <?= field_error('bar_photo') ?>
                    </div>
                    <div class="field">
                        <label for="bar-name">Nom du bar :</label>
                        <?php //Les champs sont pré-remplis avec les infos du bar : old('champ', valeur de la base) ?>
                        <input type="text" id="bar-name" name="bar_name" required maxlength="100" value="<?= old('bar_name', $bar['nom']) ?>">
                        <?= field_error('bar_name') ?>
                    </div>
                    <div class="field">
                        <label for="bar-address">Adresse :</label>
                        <input type="text" id="bar-address" name="bar_address" required maxlength="255" value="<?= old('bar_address', $bar['adresse']) ?>">
                        <?= field_error('bar_address') ?>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="bar-zip">Code postal :</label>
                            <input type="text" id="bar-zip" name="bar_zip" required inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="<?= old('bar_zip', $bar['code_postal']) ?>">
                            <?= field_error('bar_zip') ?>
                        </div>
                        <div class="field">
                            <label for="bar-city">Ville :</label>
                            <input type="text" id="bar-city" name="bar_city" required maxlength="100" value="<?= old('bar_city', $bar['ville']) ?>">
                            <?= field_error('bar_city') ?>
                        </div>
                    </div>
                    <div class="field">
                        <label for="bar-phone">Téléphone :</label>
                        <input type="tel" id="bar-phone" name="bar_phone" maxlength="20" value="<?= old('bar_phone', $bar['telephone'] ?? '') ?>">
                        <?= field_error('bar_phone') ?>
                    </div>
                    <div class="field">
                        <label for="bar-desc">Description :</label>
                        <textarea id="bar-desc" name="bar_desc" rows="4" maxlength="2000"><?= old('bar_desc', $bar['description'] ?? '') ?></textarea>
                        <?= field_error('bar_desc') ?>
                    </div>
                    <div class="field">
                        <label for="bar-screens">Nombre d'écrans :</label>
                        <input type="number" id="bar-screens" name="bar_screens" min="0" max="99" value="<?= old('bar_screens', (string) $bar['nb_ecrans']) ?>">
                        <?= field_error('bar_screens') ?>
                    </div>
                    <?php //Cases à cocher : cochées si la valeur en base vaut 1 ?>
                    <div class="check-row">
                        <label for="bar-food">Restauration</label>
                        <input type="checkbox" class="switch" id="bar-food" name="bar_food" value="1"<?= $bar['restauration'] ? ' checked' : '' ?>>
                    </div>
                    <div class="check-row">
                        <label for="bar-big">Écran géant</label>
                        <input type="checkbox" class="switch" id="bar-big" name="bar_big" value="1"<?= $bar['ecran_geant'] ? ' checked' : '' ?>>
                    </div>
                    <div class="check-row">
                        <label for="bar-pmr">Accès handicapé (PMR)</label>
                        <input type="checkbox" class="switch" id="bar-pmr" name="bar_pmr" value="1"<?= $bar['acces_pmr'] ? ' checked' : '' ?>>
                    </div>
                    <div class="form-actions"><button type="submit" class="btn-save">Enregistrer</button></div>
                </form>
            </section>

            <?php //Création d'un évènement : le gérant choisit un match puis saisit lui-même la date et l'heure de la diffusion ?>
            <section class="panel" id="event-create">
                <h2>Créer un évènement</h2>
                <form method="post" action="<?= e(url('/gerant/evenements')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="ev-match">Match diffusé :</label>
                        <select id="ev-match" name="ev_match" required>
                            <option value="">Choisis un match</option>
<?php //Liste des matchs à venir dans le menu déroulant ?>
<?php foreach ($matches as $m): ?>
                            <option value="<?= (int) $m['id_rencontre'] ?>"<?= old('ev_match') === (string) $m['id_rencontre'] ? ' selected' : '' ?>>J<?= (int) $m['journee'] ?> · <?= e($m['domicile_abr'] . ' - ' . $m['exterieur_abr']) ?> (<?= e(fr_date($m['date_match'])) ?> <?= e($m['heure_coup_envoi'] ? fr_time($m['heure_coup_envoi']) : '') ?>)</option>
<?php endforeach; ?>
                        </select>
                        <?= field_error('ev_match') ?>
                    </div>
                    <div class="field-row equal">
                        <div class="field">
                            <label for="ev-date">Date :</label>
                            <input type="date" id="ev-date" name="ev_date" required value="<?= old('ev_date') ?>">
                            <?= field_error('ev_date') ?>
                        </div>
                        <div class="field">
                            <label for="ev-time">Heure de diffusion :</label>
                            <input type="time" id="ev-time" name="ev_time" required value="<?= old('ev_time') ?>">
                            <?= field_error('ev_time') ?>
                        </div>
                    </div>
                    <div class="field">
                        <label for="ev-note">Infos complémentaires :</label>
                        <textarea id="ev-note" name="ev_note" rows="3" maxlength="1000" placeholder="Ambiance, menu spécial, réservation conseillée..."><?= old('ev_note') ?></textarea>
                        <?= field_error('ev_note') ?>
                    </div>
                    <div class="form-actions"><button type="submit" class="btn-save">Créer</button></div>
                </form>
            </section>

            <?php //Liste des évènements du bar avec un bouton pour supprimer ?>
            <section class="panel" id="event-list">
                <h2>Tes évènements</h2>
<?php if (!$events): ?>
                <p>Aucun évènement pour le moment.</p>
<?php else: ?>
                <ul class="item-list">
<?php foreach ($events as $ev): ?>
                    <li>
                        <span>
                            <strong><?= e(fr_datetime($ev['date_heure_diffusion'])) ?></strong><br>
                            <?= e($ev['domicile_abr'] . ' - ' . $ev['exterieur_abr']) ?> <small>(J<?= (int) $ev['journee'] ?>)</small>
<?php if ($ev['infos_complementaires']): ?><br><small><?= e($ev['infos_complementaires']) ?></small><?php endif; ?>
                        </span>
                        <?php //Suppression : formulaire POST avec jeton CSRF (jamais par un simple lien) ?>
                        <form method="post" action="<?= e(url('/gerant/evenements/supprimer')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_evenement" value="<?= (int) $ev['id_evenement'] ?>">
                            <button type="submit" class="btn-link" aria-label="Supprimer l'évènement du <?= e(fr_datetime($ev['date_heure_diffusion'])) ?>">Supprimer</button>
                        </form>
                    </li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
            </section>
        </div>
