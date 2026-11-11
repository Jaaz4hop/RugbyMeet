<?php
//Ici on associe chaque URL à une méthode de contrôleur
//get = affichage d'une page, post = réception d'un formulaire
// Table des routes : méthode, chemin, Contrôleur@action.
/** @var App\Core\Router $router */

use App\Controllers\{HomeController, CalendarController, BarController, PageController,
    ContactController, AuthController, ProfileController, ManagerController};

//Pages visibles par tout le monde
// Pages publiques
$router->get('/',                 [HomeController::class, 'index']);
$router->get('/calendrier',       [CalendarController::class, 'index']);
$router->get('/bars',             [BarController::class, 'index']);
$router->get('/a-propos',         [PageController::class, 'about']);
$router->get('/mentions-legales', [PageController::class, 'legal']);
$router->get('/contact',          [ContactController::class, 'show']);
$router->post('/contact',         [ContactController::class, 'send']);

//Inscription, connexion, déconnexion
// Authentification
$router->get('/inscription',      [AuthController::class, 'showRegister']);
$router->post('/inscription',     [AuthController::class, 'register']);
$router->get('/connexion',        [AuthController::class, 'showLogin']);
$router->post('/connexion',       [AuthController::class, 'login']);
$router->post('/deconnexion',     [AuthController::class, 'logout']);

//Profil du supporter et favoris (il faut être connecté)
// Espace supporter (et admin : profil simple)
$router->get('/profil',                       [ProfileController::class, 'show']);
$router->post('/profil/infos',                [ProfileController::class, 'updateInfos']);
$router->post('/profil/mot-de-passe',         [ProfileController::class, 'updatePassword']);
$router->post('/profil/club',                 [ProfileController::class, 'updateClub']);
$router->post('/bars/favori',                 [BarController::class, 'toggleFavorite']);

//Profil du gérant : son bar et ses évènements (le numéro de l'évènement à supprimer est envoyé dans le formulaire)
// Espace gérant
$router->get('/gerant',                          [ManagerController::class, 'show']);
$router->post('/gerant/bar',                     [ManagerController::class, 'updateBar']);
$router->post('/gerant/evenements',              [ManagerController::class, 'createEvent']);
$router->post('/gerant/evenements/supprimer', [ManagerController::class, 'deleteEvent']);
