<?php
namespace App\Core;

use App\Models\UtilisateurModel;

/** Utilisateur connecté (lu depuis la session, rechargé depuis la base). */
class Auth {
    //Les numéros des rôles correspondent à la table role de la base
    public const ROLE_SUPPORTER = 1;
    public const ROLE_GERANT    = 2;
    public const ROLE_ADMIN     = 3;

    //Utilisateur connecté, gardé en mémoire pour ne pas refaire la requête à chaque appel
    private static ?array $user = null;
    private static bool $loaded = false;

    //Retourne l'utilisateur connecté (tableau) ou null si personne n'est connecté
    //On lit son id en session puis on le recharge depuis la base
    public static function user(): ?array {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            if ($id) {
                self::$user = (new UtilisateurModel())->find((int) $id);
                //Si le compte n'existe plus, on oublie la session
                if (self::$user === null) {      // compte supprimé entre-temps
                    Session::forget('user_id');
                }
            }
        }
        return self::$user;
    }

    //Vrai si un utilisateur est connecté
    public static function check(): bool {
        return self::user() !== null;
    }

    //Vrai si l'utilisateur connecté a ce rôle (supporter, gérant ou admin)
    public static function isRole(int $role): bool {
        return self::check() && (int) self::user()['id_role'] === $role;
    }

    //Connexion : on change l'id de session (protection contre le vol de session) puis on mémorise l'id de l'utilisateur
    public static function login(int $userId): void {
        Session::regenerate();               // évite la fixation de session
        Session::set('user_id', $userId);
        self::$loaded = false;
        self::$user = null;
    }

    //Déconnexion : on oublie l'utilisateur et on détruit la session
    public static function logout(): void {
        self::$user = null;
        self::$loaded = false;
        Session::destroy();
    }

    /** Page "profil" adaptée au rôle. */
    //Adresse de la page profil selon le rôle : /gerant pour un gérant, /profil sinon
    public static function homeUrl(): string {
        return self::isRole(self::ROLE_GERANT) ? url('/gerant') : url('/profil');
    }
}
