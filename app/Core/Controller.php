<?php
namespace App\Core;

/** Base des contrôleurs : rendu, redirections, droits d'accès, CSRF. */
//Classe mère de tous les contrôleurs : elle regroupe ce qui est utilisé partout (afficher une vue, rediriger, vérifier les droits...)
//abstract = on ne peut pas l'instancier directement, seulement en hériter
abstract class Controller {
    //Affiche une page : début commun (top.php), puis la vue demandée, puis fin commune (bottom.php)
    protected function view(string $name, array $data = []): void {
        //Anciennes valeurs et erreurs du formulaire (pour old() et field_error()), puis messages flash
        global $__form;
        $__form = Session::pullForm();
        $flash = Session::pullFlash();
        //extract transforme le tableau en variables : ['title' => 'Bars'] devient $title dans la vue
        extract($data, EXTR_SKIP);
        require ROOT . '/app/Views/layout/top.php';
        require ROOT . '/app/Views/' . $name . '.php';
        require ROOT . '/app/Views/layout/bottom.php';
    }

    //Redirige vers une autre page puis arrête le script (never = la fonction ne retourne jamais)
    protected function redirect(string $path): never {
        header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
        exit;
    }

    //Affiche une page d'erreur (403, 404...) avec le bon code HTTP puis arrête le script
    protected function abort(int $code, string $message): never {
        (new \App\Controllers\ErrorController())->show($code, $message);
        exit;
    }

    //Exige d'être connecté : sinon redirection vers la page de connexion avec un message
    protected function requireLogin(): array {
        $user = Auth::user();
        if ($user === null) {
            Session::flash('error', 'Connecte-toi pour accéder à cette page.');
            $this->redirect('/connexion');
        }
        return $user;
    }

    //Exige un rôle précis (ex : gérant) : sinon erreur 403 (accès interdit)
    protected function requireRole(int $role): array {
        $user = $this->requireLogin();
        if ((int) $user['id_role'] !== $role) {
            $this->abort(403, "Tu n'as pas accès à cette page.");
        }
        return $user;
    }

    /** À appeler au début de chaque action POST. */
    //Vérification du jeton CSRF, au début de chaque action qui reçoit un formulaire (POST)
    protected function verifyCsrf(): void {
        //Si l'envoi dépasse la taille max autorisée par PHP, $_POST est vide : on affiche un message clair
        // Envoi plus gros que post_max_size : PHP vide $_POST et $_FILES, on l'explique clairement
        if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $this->abort(413, "Les données envoyées sont trop volumineuses (une photo de plus de 2 Mo ?).");
        }
        //Jeton absent ou différent de celui de la session : le formulaire est refusé (erreur 419)
        if (!Csrf::check(isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : null)) {
            $this->abort(419, 'Formulaire expiré. Recharge la page et réessaie.');
        }
    }

    /** Retourne au formulaire avec les erreurs et les valeurs saisies. */
    //En cas d'erreur de validation : on garde ce que l'utilisateur a saisi, on mémorise les erreurs et on revient sur le formulaire
    protected function backWithErrors(string $path, array $errors): never {
        //On ne remet jamais les mots de passe dans le formulaire
        $old = $_POST;
        unset($old['password'], $old['password_confirm'], $old['current_password'], $old['new_password'], $old['new_password_confirm'], $old['_csrf']);
        $old = array_filter($old, 'is_string');           // on ne conserve que des textes
        Session::flashForm($old, $errors);
        $this->redirect($path);
    }

    /** Champ de formulaire texte, nettoyé. Un tableau (name[]=...) ou une valeur absente donnent ''. */
    //Lit un champ de formulaire texte et enlève les espaces au début et à la fin
    protected function input(string $key): string {
        return trim($this->raw($key));
    }

    /** Champ tel quel (mots de passe : pas de trim). */
    //Lit un champ sans le modifier (utile pour les mots de passe)
    //Si le champ est absent ou si ce n'est pas un texte, on retourne une chaîne vide
    protected function raw(string $key): string {
        $v = $_POST[$key] ?? '';
        return is_string($v) ? $v : '';
    }
}
