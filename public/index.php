<?php
//Ce fichier est le seul accessible depuis le navigateur, toutes les pages du site passent par lui (grâce au .htaccess)

//On définit la racine du projet pour pouvoir inclure les autres fichiers facilement
define('ROOT', dirname(__DIR__));

// Chargement automatique : App\Core\Router -> app/Core/Router.php
//Chargement automatique des classes : quand on écrit new App\Core\Router, PHP va chercher tout seul le fichier app/Core/Router.php
//Cela évite de faire un require pour chaque classe
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $path = ROOT . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

//On charge les fonctions utilitaires (e(), url(), asset()...) qui ne sont pas dans des classes
require ROOT . '/app/Core/helpers.php';

use App\Core\{Router, Session};

//Chargement de la configuration (base de données, upload, contact...) puis réglage du fuseau horaire
require ROOT . '/config/config.php';
date_default_timezone_set('Europe/Paris');

//En production (debug à false) on cache les messages d'erreur PHP pour ne pas les montrer aux visiteurs
if (!DEBUG) {
    ini_set('display_errors', '0');
}

// En-têtes de sécurité envoyés sur toutes les pages
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

//Démarrage de la session (c'est elle qui permet de garder l'utilisateur connecté)
Session::start();

//On crée le routeur, on lui donne la liste des routes (config/routes.php) puis il appelle le bon contrôleur selon l'URL demandée
$router = new Router();
require ROOT . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
