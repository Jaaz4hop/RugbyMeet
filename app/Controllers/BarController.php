<?php
namespace App\Controllers;

use App\Core\{Auth, Controller};
use App\Models\{BarModel, ClubModel, EvenementModel, UtilisateurModel};

//Page des bars (recherche + filtres) et ajout/retrait d'un bar en favori
class BarController extends Controller {
    //Action appelée par la route GET /bars
    public function index(): void {
        $bars = new BarModel();
        $clubs = (new ClubModel())->list();
        $cities = $bars->cities();

        //Filtres lus dans l'URL et nettoyés : le texte est limité à 100 caractères, la ville doit exister dans la liste
        $filters = [
            'q'            => mb_substr(trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : ''), 0, 100),
            'ville'        => in_array($_GET['ville'] ?? '', $cities, true) ? $_GET['ville'] : '',
            'club'         => null,
            'restauration' => !empty($_GET['restauration']),
            'ecran_geant'  => !empty($_GET['ecran_geant']),
            'acces_pmr'    => !empty($_GET['acces_pmr']),
        ];
        //Le club doit être un entier qui correspond à un vrai club
        $club = filter_input(INPUT_GET, 'club', FILTER_VALIDATE_INT);
        if ($club && in_array($club, array_column($clubs, 'id_club'), false)) {
            $filters['club'] = $club;
        }

        //Date et heure actuelles : on n'affiche que les diffusions à venir
        $now = date('Y-m-d H:i:s');
        $list = $bars->search($filters, $now);
        //Les diffusions à venir des bars trouvés
        $events = (new EvenementModel())->upcomingByBars(array_column($list, 'id_bar'), $now);

        //Seuls les supporters connectés ont des favoris (pas les gérants ni les visiteurs)
        // Les favoris n'existent que pour les supporters connectés
        $favorites = Auth::isRole(Auth::ROLE_SUPPORTER)
            ? (new UtilisateurModel())->favoriteBarIds((int) Auth::user()['id_utilisateur'])
            : [];

        //Envoi des données à la vue
        $this->view('bars/index', [
            'title'     => 'Bars',
            'styles'    => ['filters.css', 'bar.css'],
            'bars'      => $list,
            'events'    => $events,
            'cities'    => $cities,
            'clubs'     => $clubs,
            'filters'   => $filters,
            'favorites' => $favorites,
        ]);
    }

    //Action appelée par la route POST /bars/favori (le numéro du bar est envoyé dans le formulaire, champ caché id_bar)
    /** Ajoute ou retire un bar des favoris (supporters connectés uniquement). */
    public function toggleFavorite(): void {
        //Numéro du bar envoyé par le formulaire (0 si absent ou invalide)
        $id = (int) $this->input('id_bar');
        //Dans l'ordre : vérification du jeton, du rôle puis de l'existence du bar
        $this->verifyCsrf();
        $user = $this->requireRole(Auth::ROLE_SUPPORTER);
        (new BarModel())->find($id) ?? $this->abort(404, "Ce bar n'existe pas.");

        $users = new UtilisateurModel();
        $uid = (int) $user['id_utilisateur'];
        //Si le favori est déjà là ou n'y est plus, on le retire ou on l'ajoute (bascule)
        try {
            if (in_array($id, $users->favoriteBarIds($uid), true)) {
                $users->removeFavorite($uid, $id);
            } else {
                $users->addFavorite($uid, $id);
            }
        } catch (\PDOException $e) {
            //Cas rare : deux clics en même temps, la base refuse le doublon, on ignore l'erreur
            // Deux clics simultanés : le favori existe déjà, rien à faire
        }

        //On retourne sur la page d'où vient l'utilisateur, mais seulement si l'adresse est sur ce site (sinon un pirate pourrait nous rediriger vers un autre site)
        // Retour sur la page d'origine, uniquement si elle est sur ce site
        $back = $this->input('back');
        $base = base_path();
        $ok = str_starts_with($back, $base . '/') && !str_contains($back, '//') && !str_contains($back, '\\');
        $this->redirect($ok ? substr($back, strlen($base)) : '/bars');
    }
}
