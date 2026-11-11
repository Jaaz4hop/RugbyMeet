<?php
namespace App\Controllers;

use App\Core\{Controller, Session};

//Formulaire de contact
class ContactController extends Controller {
    //Sujets proposés dans la liste déroulante (clé = valeur envoyée, texte = libellé)
    private const SUBJECTS = [
        'question' => 'Question générale',
        'bar'      => 'Ajouter / signaler un bar',
        'bug'      => 'Signaler un problème',
        'autre'    => 'Autre',
    ];

    //Action GET /contact : affiche le formulaire
    public function show(): void {
        $this->view('contact/form', ['title' => 'Contact', 'styles' => ['pages.css'], 'subjects' => self::SUBJECTS]);
    }

    //Action POST /contact : vérifie le formulaire puis envoie le message
    public function send(): void {
        $this->verifyCsrf();

        //Champ caché "website" : un humain ne le voit pas donc ne le remplit pas, un robot oui. S'il est rempli on ignore le message
        // Champ piège invisible pour les humains : un robot le remplit
        if ($this->input('website') !== '') {
            $this->redirect('/contact');
        }

        //Lecture des champs
        $name    = $this->input('name');
        $email   = $this->input('email');
        $subject = $this->input('subject');
        $message = $this->input('message');

        //Validation : pour chaque champ incorrect on ajoute un message dans le tableau $errors
        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) {
            $errors['name'] = 'Le nom est obligatoire (100 caractères maximum).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors['email'] = "Cette adresse mail n'est pas valide.";
        }
        if (!isset(self::SUBJECTS[$subject])) {
            $errors['subject'] = 'Choisis un sujet.';
        }
        if (mb_strlen($message) < 5 || mb_strlen($message) > 5000) {
            $errors['message'] = 'Le message doit faire entre 5 et 5000 caractères.';
        }
        //S'il y a des erreurs on retourne au formulaire avec les messages
        if ($errors) {
            $this->backWithErrors('/contact', $errors);
        }

        //Envoi du message, puis message de succès ou d'échec
        if (!$this->deliver($name, $email, self::SUBJECTS[$subject], $message)) {
            Session::flash('error', "Ton message n'a pas pu être envoyé. Réessaie plus tard.");
            $this->backWithErrors('/contact', []);
        }
        Session::flash('success', 'Merci ! Ton message a bien été envoyé.');
        $this->redirect('/contact');
    }

    //Envoie le mail, ou l'écrit dans un fichier en local (selon config.php)
    private function deliver(string $name, string $email, string $subject, string $message): bool {
        //On enlève les retours à la ligne du nom : empêche d'injecter de faux en-têtes de mail
        $name = preg_replace('/[\r\n]+/', ' ', $name);          // anti-injection d'en-têtes
        $title = '[RugbyMeet] ' . $subject;
        $body = "De : $name <$email>\nSujet : $subject\n\n$message\n";

        //Mode réel : envoi avec la fonction mail() de PHP
        if (SEND_MAIL) {
            $headers = [
                'From'         => CONTACT_FROM,
                'Reply-To'     => $email,
                'Content-Type' => 'text/plain; charset=UTF-8',
            ];
            return mail(CONTACT_TO, '=?UTF-8?B?' . base64_encode($title) . '?=', $body, $headers);
        }

        //Mode local : Laragon n'envoie pas de vrais mails, on enregistre le message dans un fichier texte
        // Mode local : le message est conservé dans storage/mails/
        $dir = ROOT . '/storage/mails';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return false;
        }
        $file = $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.txt';
        return file_put_contents($file, "À : " . CONTACT_TO . "\n" . $body) !== false;
    }
}
