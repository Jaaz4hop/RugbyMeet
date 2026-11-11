<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\RencontreModel;

//Page d'accueil : affiche les matchs de la prochaine journée
class HomeController extends Controller {
    //Action appelée par la route GET /
    public function index(): void {
        $model = new RencontreModel();
        //Retourne [numéro de journée, vrai si elle est à venir] ou null si la table est vide
        $next = $model->nextJournee(date('Y-m-d'));

        //On envoie les données à la vue : 'styles' liste les fichiers css à charger en plus
        $this->view('home/index', [
            'title'     => 'Accueil',
            'styles'    => ['accueil.css', 'cards.css'],
            'journee'   => $next[0] ?? null,
            'upcoming'  => $next[1] ?? false,
            'matches'   => $next ? $model->byJournee($next[0]) : [],
        ]);
    }
}
