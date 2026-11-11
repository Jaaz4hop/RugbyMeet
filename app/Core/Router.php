<?php
namespace App\Core;

use App\Controllers\ErrorController;

/** Routeur minimal : associe méthode + chemin à une action de contrôleur. */
class Router {
    //Liste des routes enregistrées, rangées par méthode (GET ou POST) puis par chemin
    private array $routes = [];

    //Raccourcis pour enregistrer une route GET ou POST (utilisés dans config/routes.php)
    public function get(string $path, array $handler): void  { $this->routes['GET'][$path] = $handler; }
    public function post(string $path, array $handler): void { $this->routes['POST'][$path] = $handler; }

    //Cherche la route qui correspond à l'URL demandée et appelle l'action du contrôleur
    public function dispatch(string $method, string $uri): void {
        //Une requête HEAD est traitée comme une requête GET
        $method = $method === 'HEAD' ? 'GET' : $method;
        //On récupère uniquement le chemin de l'URL (sans ?filtres=...)
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        //Si le site est dans un sous-dossier (ex : /rugbymeet/public) on l'enlève pour retrouver /bars, /calendrier...
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');

        //Toute erreur imprévue est attrapée plus bas pour afficher une page d'erreur 500 propre
        try {
            //Le chemin existe pour cette méthode : on crée le contrôleur et on appelle son action
            //Exemple : [BarController::class, 'index'] donne (new BarController())->index()
            if (isset($this->routes[$method][$path])) {
                [$class, $action] = $this->routes[$method][$path];
                (new $class())->$action();
                return;
            }
            //Le chemin existe mais avec une autre méthode (ex : GET sur une page POST) : erreur 405
            foreach ($this->routes as $otherMethod => $paths) {
                if (isset($paths[$path])) {
                    header('Allow: ' . $otherMethod);
                    (new ErrorController())->show(405, 'Méthode non autorisée.');
                    return;
                }
            }
            //Aucune route ne correspond : page introuvable (erreur 404)
            (new ErrorController())->show(404, "Cette page n'existe pas (ou plus).", 'Page introuvable');
        //Erreur imprévue : on l'enregistre dans les logs et on affiche une page d'erreur 500
        //Le détail de l'erreur n'est montré qu'en mode debug
        } catch (\Throwable $e) {
            error_log($e);
            (new ErrorController())->show(500, 'Une erreur est survenue. Réessaie dans un instant.', 'Erreur', DEBUG ? $e->getMessage() : null);
        }
    }
}
