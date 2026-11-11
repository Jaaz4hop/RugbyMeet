<?php
namespace App\Core;

/** Protection CSRF : un jeton par session, vérifié sur chaque formulaire POST. */
class Csrf {
    //Retourne le jeton CSRF de la session (créé s'il n'existe pas encore)
    //Il est ajouté en champ caché dans chaque formulaire
    public static function token(): string {
        if (empty($_SESSION['_csrf'])) {
            //Jeton aléatoire impossible à deviner
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    //Vérifie que le jeton reçu est identique à celui de la session
    //hash_equals compare de façon sécurisée. Cela empêche un autre site d'envoyer un formulaire à la place de l'utilisateur
    public static function check(?string $token): bool {
        return is_string($token) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }
}
