        <section id="titles">
            <h1>CONTACT</h1>
        </section>
        <div class="content">
            <section class="panel">
                <h2>Une question, une suggestion ?</h2>
                <p>Écris-nous, nous te répondrons dès que possible. Les champs marqués d'un astérisque (*) sont obligatoires.</p>
                <?php //Formulaire de contact envoyé en POST vers ContactController::send() ?>
                <form id="contactForm" action="<?= e(url('/contact')) ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <?php //Champ piège (honeypot) caché en CSS : un humain ne le voit pas, un robot le remplit et son message est ignoré ?>
                    <div class="field hp" aria-hidden="true">
                        <label for="c-website">Ne pas remplir</label>
                        <input type="text" id="c-website" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="field">
                        <label for="c-name">Nom *</label>
                        <input type="text" id="c-name" name="name" required maxlength="100" autocomplete="name" value="<?= old('name') ?>">
                        <?= field_error('name') ?>
                    </div>
                    <div class="field">
                        <label for="c-mail">Adresse mail *</label>
                        <input type="email" id="c-mail" name="email" required maxlength="255" autocomplete="email" value="<?= old('email') ?>">
                        <?= field_error('email') ?>
                    </div>
                    <div class="field">
                        <label for="c-subject">Sujet *</label>
                        <select id="c-subject" name="subject" required>
                            <option value="" disabled<?= old('subject') === '' ? ' selected' : '' ?>>Choisis un sujet</option>
<?php //Sujets envoyés par le contrôleur ($subjects) ?>
<?php foreach ($subjects as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= old('subject') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
                        </select>
                        <?= field_error('subject') ?>
                    </div>
                    <div class="field">
                        <label for="c-message">Message *</label>
                        <textarea id="c-message" name="message" rows="6" required maxlength="5000"><?= old('message') ?></textarea>
                        <?= field_error('message') ?>
                    </div>
                    <button type="submit" class="btn">Envoyer</button>
                </form>
            </section>
        </div>
