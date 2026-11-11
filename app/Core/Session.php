<?php
namespace App\Core;

/** Session sécurisée + messages "flash" (affichés une seule fois). */
class Session {
    //Démarre la session avec des réglages de sécurité
    public static function start(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        //Vrai si le site est en HTTPS
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_name('rugbymeet');
        //Réglages du cookie de session : httponly = inaccessible depuis le JavaScript, samesite = limite l'envoi depuis d'autres sites
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        //PHP refuse les identifiants de session inventés par un visiteur
        //La session ne passe que par le cookie (pas par l'URL)
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();

        //Déconnexion automatique après 2 h d'inactivité (7200 secondes)
        // Déconnexion automatique après 2 h d'inactivité
        if (isset($_SESSION['_last']) && time() - $_SESSION['_last'] > 7200) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['_last'] = time();
    }

    //Petites fonctions pour lire, écrire et supprimer une valeur de la session
    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void {
        unset($_SESSION[$key]);
    }

    //Change l'identifiant de session (utilisé à la connexion)
    public static function regenerate(): void {
        session_regenerate_id(true);
    }

    //Déconnexion : on vide la session, on supprime le cookie puis on détruit la session
    public static function destroy(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ----- Flash -----
    //Message flash : on le stocke en session, il sera affiché à la prochaine page puis supprimé
    public static function flash(string $type, string $message): void {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    //Récupère les messages flash et les efface aussitôt (pull = lire puis supprimer)
    public static function pullFlash(): array {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    // ----- Anciennes valeurs de formulaire et erreurs (après un échec de validation) -----
    //Après une erreur de formulaire : on garde ce que l'utilisateur avait saisi ($old) et les messages d'erreur
    public static function flashForm(array $old, array $errors): void {
        $_SESSION['_old'] = $old;
        $_SESSION['_errors'] = $errors;
    }

    //Récupère puis efface ces valeurs pour qu'elles ne s'affichent qu'une fois
    public static function pullForm(): array {
        $form = ['old' => $_SESSION['_old'] ?? [], 'errors' => $_SESSION['_errors'] ?? []];
        unset($_SESSION['_old'], $_SESSION['_errors']);
        return $form;
    }
}
