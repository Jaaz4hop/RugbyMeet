<?php
namespace App\Controllers;

use App\Core\{Auth, Controller, Database};
use App\Models\{BarModel, UtilisateurModel};

//Inscription, connexion et déconnexion
class AuthController extends Controller {
    //Action GET /inscription : affiche le formulaire (un utilisateur déjà connecté est redirigé)
    //auth.js gère l'affichage des champs du bar pour les gérants
    public function showRegister(): void {
        $this->redirectIfLoggedIn();
        $this->view('auth/register', ['title' => 'Inscription', 'styles' => ['pages.css'], 'scripts' => ['auth.js']]);
    }

    //Action POST /inscription : valide le formulaire puis crée le compte (et le bar pour un gérant)
    public function register(): void {
        $this->verifyCsrf();
        $this->redirectIfLoggedIn();

        //Seuls supporter et gérant sont proposés. Le rôle admin ne peut pas être choisi à l'inscription
        // Le rôle "admin" n'est jamais proposé à l'inscription : seuls supporter et gérant sont acceptés.
        $role = $this->input('role');
        $isGerant = $role === 'gerant';
        $roleId = $isGerant ? Auth::ROLE_GERANT : Auth::ROLE_SUPPORTER;

        //Lecture des champs du formulaire (le mail est mis en minuscules)
        $name  = $this->input('name');
        $email = mb_strtolower($this->input('email'));
        $city  = $this->input('city');
        $pwd   = $this->raw('password');
        $pwd2  = $this->raw('password_confirm');
        $barName = $this->input('bar_name');
        $barAddr = $this->input('bar_address');
        $barZip  = $this->input('bar_zip');
        $barCity = $this->input('bar_city');

        //Les modèles servent à parler à la base de données
        $users = new UtilisateurModel();
        $bars  = new BarModel();

        //Validation : pour chaque champ incorrect on ajoute un message dans le tableau $errors
        $errors = [];
        if (!in_array($role, ['supporter', 'gerant'], true)) {
            $errors['role'] = 'Choisis un type de compte.';
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
            $errors['name'] = 'Le nom du profil doit faire entre 2 et 50 caractères.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors['email'] = "Cette adresse mail n'est pas valide.";
        }
        if (!$isGerant && mb_strlen($city) > 100) {
            $errors['city'] = 'La ville ne doit pas dépasser 100 caractères.';
        }
        //Mot de passe : 8 caractères minimum, 72 maximum (limite de bcrypt), et identique à la confirmation
        if (mb_strlen($pwd) < 8) {
            $errors['password'] = 'Le mot de passe doit faire au moins 8 caractères.';
        } elseif (strlen($pwd) > 72) {
            $errors['password'] = 'Le mot de passe est trop long (72 caractères maximum).';
        } elseif ($pwd !== $pwd2) {
            $errors['password'] = 'Les mots de passe ne correspondent pas.';
        }
        //On vérifie en base que le nom de profil et le mail ne sont pas déjà pris (seulement si le champ est valide)
        if (!isset($errors['name']) && $users->nameTaken($name)) {
            $errors['name'] = 'Ce nom de profil est déjà pris.';
        }
        if (!isset($errors['email']) && $users->emailTaken($email)) {
            $errors['email'] = 'Un compte existe déjà avec cette adresse mail.';
        }
        //Un gérant doit aussi renseigner son bar
        if ($isGerant) {
            if ($barName === '' || mb_strlen($barName) > 100) {
                $errors['bar_name'] = 'Le nom du bar est obligatoire (100 caractères maximum).';
            }
            if ($barAddr === '' || mb_strlen($barAddr) > 255) {
                $errors['bar_address'] = "L'adresse du bar est obligatoire (255 caractères maximum).";
            }
            //Code postal : exactement 5 chiffres (expression régulière)
            if (!preg_match('/^[0-9]{5}$/', $barZip)) {
                $errors['bar_zip'] = 'Le code postal doit contenir 5 chiffres.';
            }
            if ($barCity === '' || mb_strlen($barCity) > 100) {
                $errors['bar_city'] = 'La ville du bar est obligatoire (100 caractères maximum).';
            }
            //Un seul bar par adresse
            if (!$errors && $bars->addressTaken($barAddr, $barZip, $barCity)) {
                $errors['bar_address'] = 'Un bar est déjà enregistré à cette adresse.';
            }
        }
        //Au moins une erreur : on retourne au formulaire avec les messages et les valeurs saisies
        if ($errors) {
            $this->backWithErrors('/inscription', $errors);
        }

        //Transaction : le compte et le bar sont créés ensemble. Si l'un échoue, rien n'est enregistré (rollBack)
        // Compte + bar créés ensemble : tout ou rien
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            //Le mot de passe est hashé avant d'être enregistré, on ne le stocke jamais en clair
            $userId = $users->create($name, $email, password_hash($pwd, PASSWORD_DEFAULT),
                ($isGerant || $city === '') ? null : $city, $roleId);
            if ($isGerant) {
                $bars->create($userId, $barName, $barAddr, $barZip, $barCity);
            }
            $pdo->commit();
        //En cas de problème (ex : doublon créé au même instant par quelqu'un d'autre) on annule tout et on affiche une erreur
        } catch (\PDOException $e) {
            $pdo->rollBack();
            // Doublon créé entre la vérification et l'insertion (course) : on le signale proprement
            $this->backWithErrors('/inscription', ['email' => 'Ce nom de profil, cette adresse mail ou ce bar existe déjà.']);
        }

        //Le nouvel utilisateur est connecté directement puis redirigé vers sa page
        Auth::login($userId);
        $this->redirect($isGerant ? '/gerant' : '/profil');
    }

    //Action GET /connexion : affiche le formulaire
    public function showLogin(): void {
        $this->redirectIfLoggedIn();
        $this->view('auth/login', ['title' => 'Connexion', 'styles' => ['pages.css']]);
    }

    //Action POST /connexion : vérifie l'adresse mail et le mot de passe
    public function login(): void {
        $this->verifyCsrf();
        $email = mb_strtolower($this->input('email'));
        $pwd = $this->raw('password');

        //On cherche le compte, puis password_verify compare le mot de passe saisi avec le hash enregistré
        $user = (new UtilisateurModel())->findByEmail($email);
        // Même message d'erreur que le compte existe ou non : on ne révèle pas quelles adresses sont inscrites
        if ($user === null || !password_verify($pwd, $user['mot_de_passe'])) {
            $this->backWithErrors('/connexion', ['login' => 'Adresse mail ou mot de passe incorrect.']);
        }

        //Si l'algorithme de hash a évolué, on met à jour le hash du mot de passe
        if (password_needs_rehash($user['mot_de_passe'], PASSWORD_DEFAULT)) {
            (new UtilisateurModel())->updatePassword((int) $user['id_utilisateur'], password_hash($pwd, PASSWORD_DEFAULT));
        }

        //Connexion réussie : on redirige un gérant vers /gerant et les autres vers /profil
        Auth::login((int) $user['id_utilisateur']);
        $this->redirect((int) $user['id_role'] === Auth::ROLE_GERANT ? '/gerant' : '/profil');
    }

    //Action POST /deconnexion
    public function logout(): void {
        $this->verifyCsrf();
        Auth::logout();
        $this->redirect('/');
    }

    //Si l'utilisateur est déjà connecté, inutile de lui montrer l'inscription ou la connexion
    private function redirectIfLoggedIn(): void {
        if (Auth::check()) {
            $this->redirect(Auth::isRole(Auth::ROLE_GERANT) ? '/gerant' : '/profil');
        }
    }
}
