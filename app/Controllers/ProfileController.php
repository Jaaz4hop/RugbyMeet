<?php
namespace App\Controllers;

use App\Core\{Auth, Controller, Session};
use App\Models\{ClubModel, UtilisateurModel};

/** Profil du supporter (et de l'admin). Les formulaires "infos" et "mot de passe" servent aussi au gérant. */
class ProfileController extends Controller {
    //Action GET /profil : page du supporter (les gérants sont renvoyés vers leur propre page)
    public function show(): void {
        $user = $this->requireLogin();
        if ((int) $user['id_role'] === Auth::ROLE_GERANT) {
            $this->redirect('/gerant');
        }
        $users = new UtilisateurModel();
        $this->view('profile/show', [
            'title'     => 'Profil',
            'styles'    => ['pages.css', 'profil.css'],
            'user'      => $user,
            'clubs'     => (new ClubModel())->list(),
            'favorites' => $users->favoriteBars((int) $user['id_utilisateur']),
        ]);
    }

    //Action POST /profil/infos : modification du nom, du mail et de la ville
    public function updateInfos(): void {
        $this->verifyCsrf();
        $user = $this->requireLogin();
        //Page de retour selon le rôle (gérant ou supporter)
        $home = Auth::isRole(Auth::ROLE_GERANT) ? '/gerant' : '/profil';
        $id = (int) $user['id_utilisateur'];
        $users = new UtilisateurModel();

        $name  = $this->input('name');
        $email = mb_strtolower($this->input('email'));
        $city  = $this->input('city');
        //Seul le supporter a une ville personnelle (celle du gérant est la ville de son bar)
        $isSupporter = (int) $user['id_role'] === Auth::ROLE_SUPPORTER;

        //Validation. $id sert à ignorer l'utilisateur lui-même pour les vérifications de nom et de mail déjà pris
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
            $errors['name'] = 'Le nom du profil doit faire entre 2 et 50 caractères.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors['email'] = "Cette adresse mail n'est pas valide.";
        }
        if ($isSupporter && mb_strlen($city) > 100) {
            $errors['city'] = 'La ville ne doit pas dépasser 100 caractères.';
        }
        if (!isset($errors['name']) && $users->nameTaken($name, $id)) {
            $errors['name'] = 'Ce nom de profil est déjà pris.';
        }
        if (!isset($errors['email']) && $users->emailTaken($email, $id)) {
            $errors['email'] = 'Un compte existe déjà avec cette adresse mail.';
        }
        if ($errors) {
            $this->backWithErrors($home, $errors);
        }

        //Si une erreur de la base survient (doublon), on affiche un message au lieu de planter
        try {
            $users->updateInfos($id, $name, $email, $isSupporter ? ($city === '' ? null : $city) : $user['ville']);
        } catch (\PDOException $e) {
            $this->backWithErrors($home, ['name' => 'Ce nom de profil ou cette adresse mail est déjà utilisé.']);
        }
        Session::flash('success', 'Tes informations ont été mises à jour.');
        $this->redirect($home);
    }

    //Action POST /profil/mot-de-passe : changement du mot de passe
    public function updatePassword(): void {
        $this->verifyCsrf();
        $user = $this->requireLogin();
        $home = Auth::isRole(Auth::ROLE_GERANT) ? '/gerant' : '/profil';

        //Mot de passe actuel, nouveau mot de passe et confirmation
        $current = $this->raw('current_password');
        $new     = $this->raw('new_password');
        $confirm = $this->raw('new_password_confirm');

        $errors = [];
        //On exige le mot de passe actuel : si quelqu'un utilise la session d'un autre, il ne peut pas changer le mot de passe sans le connaître
        if (!password_verify($current, $user['mot_de_passe'])) {
            $errors['current_password'] = 'Le mot de passe actuel est incorrect.';
        }
        //Nouveau mot de passe : 8 caractères minimum, 72 maximum (limite de bcrypt), et identique à la confirmation
        if (mb_strlen($new) < 8) {
            $errors['new_password'] = 'Le nouveau mot de passe doit faire au moins 8 caractères.';
        } elseif (strlen($new) > 72) {
            $errors['new_password'] = 'Le nouveau mot de passe est trop long (72 caractères maximum).';
        } elseif ($new !== $confirm) {
            $errors['new_password'] = 'Les mots de passe ne correspondent pas.';
        }
        if ($errors) {
            $this->backWithErrors($home, $errors);
        }

        //Le nouveau mot de passe est hashé avant l'enregistrement
        (new UtilisateurModel())->updatePassword((int) $user['id_utilisateur'], password_hash($new, PASSWORD_DEFAULT));
        Auth::login((int) $user['id_utilisateur']);   // nouvel identifiant de session après changement sensible
        Session::flash('success', 'Mot de passe modifié.');
        $this->redirect($home);
    }

    /** Choix du club de coeur (facultatif) : il détermine le thème du site s'il en existe un. */
    //Action POST /profil/club : le supporter choisit son club favori (réservé aux supporters)
    public function updateClub(): void {
        $this->verifyCsrf();
        $user = $this->requireRole(Auth::ROLE_SUPPORTER);

        //Un choix vide = aucun club (null), sinon on vérifie que le club existe
        $clubId = filter_var($_POST['id_club'] ?? '', FILTER_VALIDATE_INT);
        $clubId = $clubId === false ? null : $clubId;       // "" = aucun club
        if ($clubId !== null && (new ClubModel())->find($clubId) === null) {
            $this->abort(422, "Ce club n'existe pas.");
        }

        (new UtilisateurModel())->setClub((int) $user['id_utilisateur'], $clubId);
        Session::flash('success', $clubId === null ? 'Club favori retiré.' : 'Club favori enregistré.');
        $this->redirect('/profil');
    }
}
