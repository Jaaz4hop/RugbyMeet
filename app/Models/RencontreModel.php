<?php
namespace App\Models;

//Requêtes sur la table rencontre (les matchs)
class RencontreModel extends Model {
    //Requête de base réutilisée partout : on joint la table club deux fois (d = club qui reçoit, x = club qui se déplace) pour avoir les noms et les logos
    private const SELECT = '
        SELECT r.id_rencontre, r.journee, r.date_match, r.heure_coup_envoi,
               d.id_club AS domicile_id,  d.nom AS domicile_nom,  d.abreviation AS domicile_abr,  d.logo AS domicile_logo,
               x.id_club AS exterieur_id, x.nom AS exterieur_nom, x.abreviation AS exterieur_abr, x.logo AS exterieur_logo
        FROM rencontre r
        JOIN club d ON d.id_club = r.id_club_domicile
        JOIN club x ON x.id_club = r.id_club_exterieur';

    //Tri : par journée, puis date, puis heure (les matchs sans heure sont placés à la fin)
    private const ORDER = ' ORDER BY r.journee, r.date_match, (r.heure_coup_envoi IS NULL), r.heure_coup_envoi, r.id_rencontre';

    /** Matchs filtrés par journée et/ou club (null = pas de filtre). */
    //Recherche des matchs avec des filtres facultatifs : on construit le WHERE seulement avec les filtres choisis
    public function search(?int $journee, ?int $club): array {
        $where = [];
        $params = [];
        if ($journee !== null) {
            $where[] = 'r.journee = ?';
            $params[] = $journee;
        }
        if ($club !== null) {
            $where[] = '(r.id_club_domicile = ? OR r.id_club_exterieur = ?)';
            array_push($params, $club, $club);
        }
        //Assemblage de la requête finale
        $sql = self::SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . self::ORDER;
        return $this->all($sql, $params);
    }

    /** Numéros de journées existants. */
    //Liste des numéros de journées (pour le menu déroulant du calendrier)
    public function journees(): array{
        return array_map('intval', array_column(
            $this->all('SELECT DISTINCT journee FROM rencontre ORDER BY journee'), 'journee'));
    }

    /**
     * Prochaine journée : celle du premier match dont la date n'est pas passée.
     * Si la saison est terminée, on retombe sur la dernière journée.
     * Retourne [numéro de journée, "à venir ?"] ou null si la table est vide.
     */
    //Cherche la prochaine journée à afficher sur l'accueil : celle du premier match qui n'est pas encore passé
    public function nextJournee(string $today): ?array {
        $row = $this->one('SELECT journee FROM rencontre WHERE date_match >= ? ORDER BY date_match, journee LIMIT 1', [$today]);
        if ($row) {
            return [(int) $row['journee'], true];
        }
        $row = $this->one('SELECT MAX(journee) AS journee FROM rencontre');
        return ($row && $row['journee'] !== null) ? [(int) $row['journee'], false] : null;
    }

    //Tous les matchs d'une journée
    public function byJournee(int $journee): array {
        return $this->search($journee, null);
    }

    /** Matchs à venir (pour créer un évènement). */
    //Matchs à venir, proposés au gérant quand il ajoute une diffusion
    public function upcoming(string $today): array {
        return $this->all(self::SELECT . ' WHERE r.date_match >= ?' . self::ORDER, [$today]);
    }

    //Un match à partir de son identifiant
    public function find(int $id): ?array {
        return $this->one(self::SELECT . ' WHERE r.id_rencontre = ?', [$id]);
    }
}
