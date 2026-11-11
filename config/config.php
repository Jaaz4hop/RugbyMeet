<?php
// Configuration de RugbyMeet (valeurs par défaut de Laragon).
// ROOT est défini dans public/index.php.
//Chaque réglage est une constante : on l'utilise directement n'importe où, par exemple DB_USER
//true en développement: affiche les erreurs
const DEBUG = true;

//Connexion à la base MySQL de Laragon
const DB_DSN  = 'mysql:host=127.0.0.1;port=3306;dbname=rugbymeet;charset=utf8mb4';
const DB_USER = 'root';
const DB_PASS = '';                          // Laragon : mot de passe vide par défaut

//Dossier et taille max pour les photos des bars
const UPLOAD_DIR      = ROOT . '/public/uploads/bars';
const UPLOAD_URL      = 'uploads/bars';      // chemin relatif stocké en base
const UPLOAD_MAX_SIZE = 2 * 1024 * 1024;     // 2 Mo

//Adresses utilisées par le formulaire de contact
const CONTACT_TO   = 'contact@rugbymeet.test';
const CONTACT_FROM = 'no-reply@rugbymeet.test';
// false : le message est écrit dans storage/mails/ (utile en local, Laragon n'envoie pas de vrais mails).
// true  : envoi réel avec mail() (nécessite un serveur de mail configuré).
const SEND_MAIL = false;
