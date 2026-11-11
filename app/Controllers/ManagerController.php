<?php
namespace App\Controllers;

use App\Core\{Auth, Controller, Session};
use App\Models\{BarModel, EvenementModel, RencontreModel};

/** Espace du gérant : son bar (un seul) et ses évènements. */
class ManagerController extends Controller {
    //Action GET /gerant : page du gérant (informations du bar, évènements, formulaire de création)
    public function show(): void {
        $user = $this->requireRole(Auth::ROLE_GERANT);
        //On retrouve le bar du gérant connecté : il ne peut donc voir et modifier que son bar
        $bar = (new BarModel())->findByManager((int) $user['id_utilisateur'])
            ?? $this->abort(404, "Aucun bar n'est rattaché à ton compte.");

        $this->view('manager/show', [
            'title'   => 'Profil gérant',
            'styles'  => ['pages.css', 'profil.css'],
            'user'    => $user,
            'bar'     => $bar,
            'events'  => (new EvenementModel())->allByBar((int) $bar['id_bar']),
            'matches' => (new RencontreModel())->upcoming(date('Y-m-d')),
        ]);
    }

    //Action POST /gerant/bar : modification des informations du bar et de sa photo
    public function updateBar(): void {
        $this->verifyCsrf();
        $user = $this->requireRole(Auth::ROLE_GERANT);
        $bars = new BarModel();
        $bar = $bars->findByManager((int) $user['id_utilisateur']) ?? $this->abort(404, 'Bar introuvable.');
        $barId = (int) $bar['id_bar'];

        //Données du formulaire rassemblées dans un tableau. Les cases à cocher valent 1 si cochées, 0 sinon (isset)
        $d = [
            'nom'          => $this->input('bar_name'),
            'adresse'      => $this->input('bar_address'),
            'code_postal'  => $this->input('bar_zip'),
            'ville'        => $this->input('bar_city'),
            'telephone'    => $this->input('bar_phone'),
            'description'  => $this->input('bar_desc'),
            'nb_ecrans'    => filter_var($_POST['bar_screens'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99]]),
            'restauration' => isset($_POST['bar_food']) ? 1 : 0,
            'ecran_geant'  => isset($_POST['bar_big']) ? 1 : 0,
            'acces_pmr'    => isset($_POST['bar_pmr']) ? 1 : 0,
        ];

        //Validation des champs
        $errors = [];
        if ($d['nom'] === '' || mb_strlen($d['nom']) > 100) {
            $errors['bar_name'] = 'Le nom du bar est obligatoire (100 caractères maximum).';
        }
        if ($d['adresse'] === '' || mb_strlen($d['adresse']) > 255) {
            $errors['bar_address'] = "L'adresse est obligatoire (255 caractères maximum).";
        }
        //Code postal : exactement 5 chiffres (expression régulière)
        if (!preg_match('/^[0-9]{5}$/', $d['code_postal'])) {
            $errors['bar_zip'] = 'Le code postal doit contenir 5 chiffres.';
        }
        if ($d['ville'] === '' || mb_strlen($d['ville']) > 100) {
            $errors['bar_city'] = 'La ville est obligatoire (100 caractères maximum).';
        }
        //Le téléphone est facultatif : vide = pas d'erreur, sinon chiffres, espaces, points, tirets, + et parenthèses
        if ($d['telephone'] !== '' && !preg_match('/^[0-9 .()+-]{6,20}$/', $d['telephone'])) {
            $errors['bar_phone'] = "Le numéro de téléphone n'est pas valide.";
        }
        if (mb_strlen($d['description']) > 2000) {
            $errors['bar_desc'] = 'La description ne doit pas dépasser 2000 caractères.';
        }
        if ($d['nb_ecrans'] === false) {
            $errors['bar_screens'] = "Le nombre d'écrans doit être compris entre 0 et 99.";
        }
        //Une adresse ne peut appartenir qu'à un seul bar (on ignore le bar en cours de modification)
        if (!$errors && $bars->addressTaken($d['adresse'], $d['code_postal'], $d['ville'], $barId)) {
            $errors['bar_address'] = 'Un autre bar est déjà enregistré à cette adresse.';
        }

        //Photo facultative : si une nouvelle est envoyée elle remplace l'ancienne
        // Photo : facultative, remplace l'ancienne si une nouvelle est envoyée
        [$newPhoto, $photoError] = $this->savePhoto($_FILES['bar_photo'] ?? null);
        if ($photoError) {
            $errors['bar_photo'] = $photoError;
        }
        //S'il y a des erreurs, on supprime la photo qui vient d'être enregistrée (puisque le formulaire est refusé) et on retourne au formulaire
        if ($errors) {
            $this->deletePhoto($newPhoto);               // photo déjà enregistrée mais formulaire refusé
            $this->backWithErrors('/gerant', $errors);
        }

        //Les champs facultatifs vides sont enregistrés à NULL dans la base
        $d['telephone'] = $d['telephone'] === '' ? null : $d['telephone'];
        $d['description'] = $d['description'] === '' ? null : $d['description'];
        //Mise à jour en base (erreur gérée si un doublon d'adresse apparaît au même moment)
        try {
            $bars->update($barId, $d);
        } catch (\PDOException $e) {
            $this->deletePhoto($newPhoto);                // l'image déjà enregistrée ne sert plus
            $this->backWithErrors('/gerant', ['bar_address' => 'Un autre bar est déjà enregistré à cette adresse.']);
        }

        //Nouvelle photo : on l'enregistre puis on supprime l'ancienne. La case 'supprimer la photo' enlève simplement la photo
        if ($newPhoto !== null) {
            $bars->setPhoto($barId, $newPhoto);
            $this->deletePhoto($bar['photo']);
        } elseif (isset($_POST['remove_photo'])) {
            $bars->setPhoto($barId, null);
            $this->deletePhoto($bar['photo']);
        }

        Session::flash('success', 'Ton bar a été mis à jour.');
        $this->redirect('/gerant');
    }

    //Action POST /gerant/evenements : le gérant ajoute la diffusion d'un match
    public function createEvent(): void {
        $this->verifyCsrf();
        $user = $this->requireRole(Auth::ROLE_GERANT);
        $bar = (new BarModel())->findByManager((int) $user['id_utilisateur']) ?? $this->abort(404, 'Bar introuvable.');
        $barId = (int) $bar['id_bar'];
        $events = new EvenementModel();

        //Lecture des champs. Le gérant saisit lui-même la date et l'heure de la diffusion
        $matchId = filter_var($_POST['ev_match'] ?? '', FILTER_VALIDATE_INT);
        $date = $this->input('ev_date');
        $time = $this->input('ev_time');
        $note = $this->input('ev_note');
        $today = date('Y-m-d');

        $errors = [];
        //Le match doit exister, ne pas être passé, et ne pas être déjà diffusé par ce bar
        $match = $matchId ? (new RencontreModel())->find($matchId) : null;
        if ($match === null || $match['date_match'] < $today) {
            $errors['ev_match'] = 'Choisis un match à venir.';
        } elseif ($events->exists($barId, $matchId)) {
            $errors['ev_match'] = 'Tu diffuses déjà ce match.';
        }

        //Vérifie que la date a le bon format (AAAA-MM-JJ) et qu'elle existe vraiment (checkdate), puis qu'elle n'est pas dans le passé
        $dateOk = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4));
        if (!$dateOk) {
            $errors['ev_date'] = 'Indique une date valide.';
        } elseif ($date < $today) {
            $errors['ev_date'] = 'La date de diffusion ne peut pas être dans le passé.';
        }
        //Vérifie le format de l'heure (HH:MM, de 00:00 à 23:59)
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            $errors['ev_time'] = 'Indique une heure valide.';
        }
        if (mb_strlen($note) > 1000) {
            $errors['ev_note'] = 'Les infos complémentaires ne doivent pas dépasser 1000 caractères.';
        }
        if ($errors) {
            $this->backWithErrors('/gerant#event-create', $errors);
        }

        try {
            $events->create($barId, $matchId, "$date $time:00", $note === '' ? null : $note);
        } catch (\PDOException $e) {
            $this->backWithErrors('/gerant#event-create', ['ev_match' => 'Tu diffuses déjà ce match.']);
        }
        Session::flash('success', 'Évènement créé.');
        $this->redirect('/gerant#event-list');
    }

    //Action POST /gerant/evenements/supprimer : supprime un évènement du bar du gérant (numéro envoyé dans le formulaire, champ caché id_evenement)
    public function deleteEvent(): void {
        $id = (int) $this->input('id_evenement');
        $this->verifyCsrf();
        $user = $this->requireRole(Auth::ROLE_GERANT);
        $bar = (new BarModel())->findByManager((int) $user['id_utilisateur']) ?? $this->abort(404, 'Bar introuvable.');

        //La requête SQL vérifie que l'évènement appartient bien à ce bar : impossible de supprimer celui d'un autre
        // La requête SQL vérifie aussi que l'évènement appartient bien à ce bar
        if (!(new EvenementModel())->delete($id, (int) $bar['id_bar'])) {
            $this->abort(404, "Cet évènement n'existe pas.");
        }
        Session::flash('success', 'Évènement supprimé.');
        $this->redirect('/gerant#event-list');
    }

    //Enregistre la photo envoyée dans public/uploads/bars
    //Retourne [chemin, null] si tout va bien, [null, message d'erreur] sinon, ou [null, null] si aucun fichier n'a été envoyé (la photo est facultative)
    private function savePhoto(mixed $file): array {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        //Vérifications de base sur ce que le navigateur a envoyé
        if (!is_int($file['error']) || !is_string($file['tmp_name'] ?? null)) {
            return [null, "L'envoi de l'image a échoué. Réessaie."];
        }
        //Fichier trop lourd (limite fixée dans config.php)
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > UPLOAD_MAX_SIZE) {
            return [null, "L'image est trop lourde (" . round(UPLOAD_MAX_SIZE / 1048576) . " Mo maximum)."];
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return [null, "L'envoi de l'image a échoué. Réessaie."];
        }

        //Types autorisés et extension associée. Le type est lu dans le vrai contenu du fichier, jamais d'après son nom
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($types[$mime]) || @getimagesize($file['tmp_name']) === false) {
            return [null, 'Format non accepté : utilise une image JPG, PNG ou WebP.'];
        }

        //Création du dossier d'envoi s'il n'existe pas
        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
            return [null, "Le dossier d'envoi est introuvable."];
        }
        //Nom aléatoire : évite les doublons et empêche d'envoyer un fichier .php
        $name = bin2hex(random_bytes(16)) . '.' . $types[$mime];
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
            return [null, "L'enregistrement de l'image a échoué."];
        }
        return [UPLOAD_URL . '/' . $name, null];
    }

    //Supprime une ancienne photo (uniquement si son nom correspond à ceux que l'on génère : 32 caractères hexadécimaux + extension)
    private function deletePhoto(?string $path): void {
        if (!$path) {
            return;
        }
        $name = basename($path);
        if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) && is_file(UPLOAD_DIR . '/' . $name)) {
            unlink(UPLOAD_DIR . '/' . $name);
        }
    }
}
