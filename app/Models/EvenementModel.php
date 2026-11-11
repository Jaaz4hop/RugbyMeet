<?php
namespace App\Models;

//Requêtes sur la table evenement (diffusion d'un match dans un bar)
class EvenementModel extends Model {
    //Requête de base : l'évènement avec les infos du match et des deux clubs
    private const SELECT = '
        SELECT ev.id_evenement, ev.date_heure_diffusion, ev.infos_complementaires, ev.id_bar,
               r.id_rencontre, r.journee, r.date_match, r.heure_coup_envoi,
               d.nom AS domicile_nom, d.abreviation AS domicile_abr, d.logo AS domicile_logo,
               x.nom AS exterieur_nom, x.abreviation AS exterieur_abr, x.logo AS exterieur_logo
        FROM evenement ev
        JOIN rencontre r ON r.id_rencontre = ev.id_rencontre
        JOIN club d ON d.id_club = r.id_club_domicile
        JOIN club x ON x.id_club = r.id_club_exterieur';

    /** Diffusions à venir de plusieurs bars, regroupées par id_bar. */
    //Diffusions à venir pour plusieurs bars à la fois, rangées par bar (une seule requête pour toute la page)
    public function upcomingByBars(array $barIds, string $now): array {
        if (!$barIds) {
            return [];
        }
        //Fabrique autant de ? que de bars : IN (?, ?, ?)
        $in = implode(',', array_fill(0, count($barIds), '?'));
        $rows = $this->all(self::SELECT . " WHERE ev.id_bar IN ($in) AND ev.date_heure_diffusion >= ?
                                           ORDER BY ev.date_heure_diffusion", [...array_values($barIds), $now]);
        //On range les évènements dans un tableau indexé par l'id du bar
        $byBar = [];
        foreach ($rows as $row) {
            $byBar[(int) $row['id_bar']][] = $row;
        }
        return $byBar;
    }

    /** Tous les évènements d'un bar (passés compris), pour la gestion par le gérant. */
    //Tous les évènements d'un bar, passés compris (pour la page du gérant)
    public function allByBar(int $barId): array {
        return $this->all(self::SELECT . ' WHERE ev.id_bar = ? ORDER BY ev.date_heure_diffusion', [$barId]);
    }

    //Vrai si ce bar diffuse déjà ce match (évite les doublons)
    public function exists(int $barId, int $rencontreId): bool {
        return $this->one('SELECT 1 AS ok FROM evenement WHERE id_bar = ? AND id_rencontre = ?',
            [$barId, $rencontreId]) !== null;
    }

    //Ajoute une diffusion
    public function create(int $barId, int $rencontreId, string $dateHeure, ?string $infos): void {
        $this->run('INSERT INTO evenement (date_heure_diffusion, infos_complementaires, id_bar, id_rencontre)
                    VALUES (?, ?, ?, ?)', [$dateHeure, $infos, $barId, $rencontreId]);
    }

    /** Supprime un évènement uniquement s'il appartient bien à ce bar. */
    //Supprime un évènement seulement s'il appartient à ce bar : un gérant ne peut pas supprimer ceux des autres
    //retourne vrai si une ligne a été supprimée
    public function delete(int $eventId, int $barId): bool {
        return $this->run('DELETE FROM evenement WHERE id_evenement = ? AND id_bar = ?', [$eventId, $barId])->rowCount() > 0;
    }
}
