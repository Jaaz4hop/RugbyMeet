<?php
namespace App\Controllers;

use App\Core\Controller;

//Pages simples sans traitement : à propos et mentions légales
class PageController extends Controller {
    public function about(): void {
        $this->view('pages/about', ['title' => 'À propos', 'styles' => ['pages.css']]);
    }

    public function legal(): void {
        $this->view('pages/legal', ['title' => 'Mentions légales', 'styles' => ['pages.css']]);
    }
}
