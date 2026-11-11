<?php
namespace App\Models;

//Requêtes sur la table club
class ClubModel extends Model {
    //Tous les clubs, classés par nom
    public function list(): array {
        return $this->all('SELECT id_club, nom, abreviation, logo, theme FROM club ORDER BY nom');
    }

    //Un club à partir de son identifiant
    public function find(int $id): ?array {
        return $this->one('SELECT id_club, nom, abreviation, logo, theme FROM club WHERE id_club = ?', [$id]);
    }
}
