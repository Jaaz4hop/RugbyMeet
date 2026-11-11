<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\{ClubModel, RencontreModel};

//Page calendrier : tous les matchs, filtrables par journée et par club
class CalendarController extends Controller {
    //Action appelée par la route GET /calendrier
    public function index(): void {
        $matchesModel = new RencontreModel();
        $clubs = (new ClubModel())->list();
        $journees = $matchesModel->journees();

        //On lit les filtres dans l'URL, on vérifie que ce sont des entiers puis qu'ils existent vraiment
        //Sinon on les ignore (null = pas de filtre)
        // Filtres issus de l'URL (?journee=3&club=5), validés contre les valeurs existantes
        $journee = filter_input(INPUT_GET, 'journee', FILTER_VALIDATE_INT);
        $club    = filter_input(INPUT_GET, 'club', FILTER_VALIDATE_INT);
        $journee = in_array($journee, $journees, true) ? $journee : null;
        $club    = in_array($club, array_column($clubs, 'id_club'), false) ? $club : null;

        //On range les matchs par journée pour les afficher sous un titre par journée
        $byJournee = [];
        foreach ($matchesModel->search($journee, $club) as $m) {
            $byJournee[(int) $m['journee']][] = $m;
        }

        //Envoi des données à la vue (selJournee et selClub servent à garder les filtres sélectionnés)
        $this->view('calendar/index', [
            'title'     => 'Calendrier',
            'styles'    => ['filters.css', 'cards.css'],
            'clubs'     => $clubs,
            'journees'  => $journees,
            'selJournee' => $journee,
            'selClub'   => $club,
            'byJournee' => $byJournee,
            'count'     => array_sum(array_map('count', $byJournee)),
        ]);
    }
}
