<?php
namespace App\Models;

//Requêtes sur la table bar
class BarModel extends Model {
    //Colonnes récupérées à chaque fois (les backticks autour de description évitent un conflit avec un mot réservé SQL)
    private const COLUMNS = 'b.id_bar, b.nom, b.adresse, b.code_postal, b.ville, b.telephone, b.`description`,
        b.photo, b.nb_ecrans, b.restauration, b.ecran_geant, b.acces_pmr, b.id_utilisateur';

    //Un bar à partir de son identifiant
    public function find(int $id): ?array {
        return $this->one('SELECT ' . self::COLUMNS . ' FROM bar b WHERE b.id_bar = ?', [$id]);
    }

    //Le bar d'un gérant (un gérant a un seul bar)
    public function findByManager(int $userId): ?array {
        return $this->one('SELECT ' . self::COLUMNS . ' FROM bar b WHERE b.id_utilisateur = ?', [$userId]);
    }

    //Liste des villes où il y a des bars, pour le filtre
    public function cities(): array {
        return array_column($this->all('SELECT DISTINCT ville FROM bar ORDER BY ville'), 'ville');
    }

    /**
     * Recherche de bars. $f : q, ville, club (id), restauration, ecran_geant, acces_pmr.
     * Le filtre "club" ne garde que les bars qui diffusent encore un match de ce club.
     */
    //Recherche des bars selon les filtres choisis : on ajoute une condition au WHERE seulement pour les filtres remplis
    public function search(array $f, string $now): array {
        $where = [];
        $params = [];

        //Recherche texte dans le nom, la ville ou la description (LIKE avec % autour du mot)
        if (($f['q'] ?? '') !== '') {
            $where[] = '(b.nom LIKE ? OR b.ville LIKE ? OR b.`description` LIKE ?)';
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            array_push($params, $like, $like, $like);
        }
        //Filtre par ville
        if (($f['ville'] ?? '') !== '') {
            $where[] = 'b.ville = ?';
            $params[] = $f['ville'];
        }
        //Filtre par club : on garde les bars qui diffusent un match à venir de ce club (sous-requête EXISTS)
        if (!empty($f['club'])) {
            $where[] = 'EXISTS (SELECT 1 FROM evenement ev JOIN rencontre r ON r.id_rencontre = ev.id_rencontre
                                WHERE ev.id_bar = b.id_bar AND ev.date_heure_diffusion >= ?
                                  AND (r.id_club_domicile = ? OR r.id_club_exterieur = ?))';
            array_push($params, $now, $f['club'], $f['club']);
        }
        //Filtres cases à cocher : restauration, écran géant, accès PMR
        foreach (['restauration', 'ecran_geant', 'acces_pmr'] as $flag) {
            if (!empty($f[$flag])) {
                $where[] = "b.$flag = 1";
            }
        }

        //Assemblage de la requête finale avec le tri par ville puis par nom
        $sql = 'SELECT ' . self::COLUMNS . ' FROM bar b'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY b.ville, b.nom';
        return $this->all($sql, $params);
    }

    //Création du bar quand un gérant s'inscrit
    public function create(int $userId, string $nom, string $adresse, string $cp, string $ville): int {
        $this->run('INSERT INTO bar (nom, adresse, code_postal, ville, id_utilisateur) VALUES (?, ?, ?, ?, ?)',
            [$nom, $adresse, $cp, $ville, $userId]);
        return (int) $this->db->lastInsertId();
    }

    //Vrai si un autre bar a déjà exactement cette adresse
    public function addressTaken(string $adresse, string $cp, string $ville, ?int $exceptBarId = null): bool {
        return $this->one(
            'SELECT 1 AS ok FROM bar WHERE adresse = ? AND code_postal = ? AND ville = ? AND id_bar <> ?',
            [$adresse, $cp, $ville, $exceptBarId ?? 0]) !== null;
    }

    //Mise à jour des informations du bar par son gérant
    public function update(int $barId, array $d): void {
        $this->run(
            'UPDATE bar SET nom = ?, adresse = ?, code_postal = ?, ville = ?, telephone = ?, `description` = ?,
                            nb_ecrans = ?, restauration = ?, ecran_geant = ?, acces_pmr = ?
             WHERE id_bar = ?',
            [$d['nom'], $d['adresse'], $d['code_postal'], $d['ville'], $d['telephone'], $d['description'],
             $d['nb_ecrans'], $d['restauration'], $d['ecran_geant'], $d['acces_pmr'], $barId]);
    }

    //Enregistre le chemin de la photo (null pour la supprimer)
    public function setPhoto(int $barId, ?string $path): void {
        $this->run('UPDATE bar SET photo = ? WHERE id_bar = ?', [$path, $barId]);
    }
}
