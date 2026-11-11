<?php
namespace App\Controllers;

use App\Core\Controller;

//Affiche les pages d'erreur (403, 404, 405, 500...) : utilisé par le routeur et par la méthode abort() des contrôleurs
class ErrorController extends Controller {
    public function show(int $code, string $message, string $title = 'Erreur', ?string $detail = null): void
    {
        //Le code HTTP indique au navigateur le type d'erreur
        http_response_code($code);
        $this->view('errors/error', [
            'code'    => $code,
            'message' => $message,
            'title'   => $title,
            'detail'  => $detail,
            'styles'  => ['pages.css'],
        ]);
    }
}
