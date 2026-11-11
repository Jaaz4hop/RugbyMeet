        <section id="titles">
            <h1>CONNEXION</h1>
        </section>
        <div class="content narrow">
            <section class="panel">
                <h2>Content de te revoir</h2>
                <form id="loginForm" action="<?= e(url('/connexion')) ?>" method="post">
                    <?php //Jeton CSRF caché ?>
                    <?= csrf_field() ?>
<?php //Message d'erreur général (mauvais mail ou mot de passe) ?>
<?php if ($msg = form_errors()['login'] ?? null): ?>
                    <p class="form-error" role="alert"><?= e($msg) ?></p>
<?php endif; ?>
                    <div class="field">
                        <label for="l-mail">Adresse mail</label>
                        <?php //old('email') remet l'adresse saisie après une erreur ?>
                        <input type="email" id="l-mail" name="email" required autocomplete="email" value="<?= old('email') ?>">
                    </div>
                    <div class="field">
                        <label for="l-pwd">Mot de passe</label>
                        <input type="password" id="l-pwd" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn">Se connecter</button>
                    <p class="alt-link">Pas encore de compte ? <a href="<?= e(url('/inscription')) ?>">Inscris-toi</a></p>
                </form>
            </section>
        </div>
