<?php
namespace App\Models;

//Requêtes sur la table utilisateur et sur la table utilisateur_bar (bars favoris)
class UtilisateurModel extends Model {
    //Requête de base : on récupère aussi le nom du rôle (JOIN) et le club favori s'il existe (LEFT JOIN : l'utilisateur peut ne pas avoir de club)
    private const SELECT = '
        SELECT u.id_utilisateur, u.nom_profil, u.email, u.mot_de_passe, u.ville, u.date_inscription,
               u.id_role, ro.libelle AS role, u.id_club, c.nom AS club_nom, c.logo AS club_logo, c.theme AS club_theme
        FROM utilisateur u
        JOIN `role` ro ON ro.id_role = u.id_role
        LEFT JOIN club c ON c.id_club = u.id_club';

    //Un utilisateur à partir de son identifiant
    public function find(int $id): ?array {
        return $this->one(self::SELECT . ' WHERE u.id_utilisateur = ?', [$id]);
    }

    //Un utilisateur à partir de son adresse mail (utilisé à la connexion)
    public function findByEmail(string $email): ?array {
        return $this->one(self::SELECT . ' WHERE u.email = ?', [$email]);
    }

    //Vrai si l'adresse mail est déjà utilisée par un autre compte ($exceptId sert à ignorer l'utilisateur en cours de modification)
    public function emailTaken(string $email, ?int $exceptId = null): bool {
        return $this->exists('email', $email, $exceptId);
    }

    //Vrai si le nom de profil est déjà pris
    public function nameTaken(string $name, ?int $exceptId = null): bool {
        return $this->exists('nom_profil', $name, $exceptId);
    }

    //Vérification d'unicité commune aux deux fonctions du dessus
    private function exists(string $column, string $value, ?int $exceptId): bool {
        // $column vient du code (jamais de l'utilisateur) : pas d'injection possible.
        return $this->one("SELECT 1 AS ok FROM utilisateur WHERE $column = ? AND id_utilisateur <> ?",
            [$value, $exceptId ?? 0]) !== null;
    }

    //Création d'un compte, retourne l'identifiant créé (lastInsertId)
    public function create(string $name, string $email, string $passwordHash, ?string $ville, int $roleId): int {
        $this->run(
            'INSERT INTO utilisateur (nom_profil, email, mot_de_passe, ville, id_role) VALUES (?, ?, ?, ?, ?)',
            [$name, $email, $passwordHash, $ville, $roleId]
        );
        return (int) $this->db->lastInsertId();
    }

    //Modification du nom, de l'adresse mail et de la ville
    public function updateInfos(int $id, string $name, string $email, ?string $ville): void {
        $this->run('UPDATE utilisateur SET nom_profil = ?, email = ?, ville = ? WHERE id_utilisateur = ?',
            [$name, $email, $ville, $id]);
    }

    //Enregistre le nouveau mot de passe (déjà hashé)
    public function updatePassword(int $id, string $hash): void {
        $this->run('UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?', [$hash, $id]);
    }

    //Change le club favori (null = aucun club)
    public function setClub(int $id, ?int $clubId): void {
        $this->run('UPDATE utilisateur SET id_club = ? WHERE id_utilisateur = ?', [$clubId, $id]);
    }

    //Gestion des bars favoris
    // ----- Bars favoris (table utilisateur_bar) -----

    //Liste des identifiants des bars favoris de l'utilisateur
    public function favoriteBarIds(int $userId): array {
        return array_map('intval', array_column(
            $this->all('SELECT id_bar FROM utilisateur_bar WHERE id_utilisateur = ?', [$userId]), 'id_bar'));
    }

    //Les bars favoris avec leur nom et leur ville, pour la page profil
    public function favoriteBars(int $userId): array {
        return $this->all(
            'SELECT b.id_bar, b.nom, b.ville
             FROM utilisateur_bar ub JOIN bar b ON b.id_bar = ub.id_bar
             WHERE ub.id_utilisateur = ? ORDER BY ub.date_ajout, b.nom', [$userId]);
    }

    //Ajoute un favori seulement s'il n'y est pas déjà
    public function addFavorite(int $userId, int $barId): void {
        if (!in_array($barId, $this->favoriteBarIds($userId), true)) {
            $this->run('INSERT INTO utilisateur_bar (id_utilisateur, id_bar) VALUES (?, ?)', [$userId, $barId]);
        }
    }

    //Retire un bar des favoris
    public function removeFavorite(int $userId, int $barId): void {
        $this->run('DELETE FROM utilisateur_bar WHERE id_utilisateur = ? AND id_bar = ?', [$userId, $barId]);
    }
}
