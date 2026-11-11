        <?php //Page d'erreur : $code est le numéro (404, 403...), $message le texte ?>
        <section id="titles">
            <h1><?= (int) $code ?></h1>
            <div><h2><?= e($title ?? 'Erreur') ?></h2></div>
        </section>
        <div class="content narrow">
            <section class="panel">
                <p><?= e($message) ?></p>
<?php //Le détail technique n'apparaît qu'en mode debug ?>
<?php if (!empty($detail)): ?>
                <pre class="debug"><?= e($detail) ?></pre>
<?php endif; ?>
                <p class="alt-link"><a href="<?= e(url('/')) ?>">Retour à l'accueil</a></p>
            </section>
        </div>
