    <section id="titles">
        <h1>INSCRIPTION</h1>
    </section>
    <div class="content narrow">
        <section class="panel">
            <h2>Rejoins RugbyMeet</h2>
            <p>Les champs marqués d'un astérisque (*) sont obligatoires.</p>
            <?php //Formulaire d'inscription envoyé en POST. novalidate : c'est auth.js et PHP qui font les vérifications ?>
            <form id="signupForm" action="<?= e(url('/inscription')) ?>" method="post" novalidate>
                    <?= csrf_field() ?>
<?php //Message général s'il y a au moins une erreur ?>
<?php if ($errs = form_errors()): ?>
                    <p class="form-error" role="alert">Merci de corriger les champs signalés ci-dessous.</p>
<?php endif; ?>
                <?php //Choix du rôle. old('role', 'supporter') : supporter coché par défaut, ou le choix précédent après une erreur ?>
                <fieldset class="role-choice">
                    <legend>Tu es : *</legend>
                    <label class="role-card">
                        <input type="radio" name="role" value="supporter"<?= old('role', 'supporter') !== 'gerant' ? ' checked' : '' ?>>
                        <span><i class="bi bi-people" aria-hidden="true"></i> Supporter</span>
                        <small>Je cherche où regarder mes matchs</small>
                    </label>
                    <label class="role-card">
                        <input type="radio" name="role" value="gerant"<?= old('role') === 'gerant' ? ' checked' : '' ?>>
                        <span><i class="bi bi-cup-straw" aria-hidden="true"></i> Gérant de bar</span>
                        <small>Je veux faire connaître mon bar</small>
                    </label>
                </fieldset>
                <?php //Message d'erreur du champ (vide s'il n'y en a pas) ?>
                <?= field_error('role') ?>
                <div class="field">
                    <label for="s-name">Nom du profil *</label>
                    <input type="text" id="s-name" name="name" required autocomplete="nickname" value="<?= old('name') ?>">
                        <?= field_error('name') ?>
                </div>
                <div class="field">
                    <label for="s-mail">Adresse mail *</label>
                    <input type="email" id="s-mail" name="email" required autocomplete="email" value="<?= old('email') ?>">
                        <?= field_error('email') ?>
                </div>
                <?php //Ville personnelle (supporter uniquement, auth.js la cache pour un gérant) ?>
                <div class="field" id="cityField">
                    <label for="s-city">Ta ville <small>(facultatif, pour te proposer des bars proches)</small></label>
                    <input type="text" id="s-city" name="city" autocomplete="address-level2" value="<?= old('city') ?>">
                        <?= field_error('city') ?>
                </div>
                <?php //Champs du bar : cachés par défaut (hidden), auth.js les affiche si on choisit gérant ?>
                <fieldset class="bar-fields" id="barFields" hidden>
                    <legend>Ton bar</legend>
                    <div class="field">
                        <label for="s-bar">Nom du bar *</label>
                        <input type="text" id="s-bar" name="bar_name" maxlength="100" value="<?= old('bar_name') ?>">
                        <?= field_error('bar_name') ?>
                    </div>
                    <div class="field">
                        <label for="s-addr">Adresse (numéro et rue) *</label>
                        <input type="text" id="s-addr" name="bar_address" maxlength="255" autocomplete="street-address" value="<?= old('bar_address') ?>">
                        <?= field_error('bar_address') ?>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="s-zip">Code postal *</label>
                            <input type="text" id="s-zip" name="bar_zip" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" autocomplete="postal-code" value="<?= old('bar_zip') ?>">
                        <?= field_error('bar_zip') ?>
                        </div>
                        <div class="field">
                            <label for="s-barcity">Ville du bar *</label>
                            <input type="text" id="s-barcity" name="bar_city" maxlength="100" value="<?= old('bar_city') ?>">
                        <?= field_error('bar_city') ?>
                        </div>
                    </div>
                </fieldset>
                <div class="field">
                    <?php //Mots de passe : jamais réaffichés après une erreur ?>
                    <label for="s-pwd">Mot de passe * <small>(8 caractères minimum)</small></label>
                    <input type="password" id="s-pwd" name="password" required minlength="8" autocomplete="new-password">
                        <?= field_error('password') ?>
                </div>
                <div class="field">
                    <label for="s-pwd2">Confirme le mot de passe *</label>
                    <input type="password" id="s-pwd2" name="password_confirm" required minlength="8" autocomplete="new-password">
                        
                </div>
                <p id="formError" class="form-error" role="alert" hidden></p>
                <button type="submit" class="btn">Créer mon compte</button>
                <p class="alt-link">Déjà inscrit ? <a href="<?= e(url('/connexion')) ?>">Connecte-toi</a></p>
            </form>
        </section>
    </div>
