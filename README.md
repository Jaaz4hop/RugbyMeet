# RugbyMeet (PHP / MVC)

Site de supporters du Top 14 : calendrier, bars qui diffusent les matchs, comptes supporter et gérant de bar.

## Installation avec Laragon
1. Copier ce dossier dans `C:\laragon\www\` sous le nom **rugbymeet**.
2. Importer `database/rugbymeet.sql` dans MySQL (HeidiSQL ou MySQL Workbench). Le script recrée la base `rugbymeet`.
3. Vérifier `config/config.php` (identifiants MySQL : `root` sans mot de passe par défaut dans Laragon).
4. Laragon > clic droit > Apache > recharger (ou « Démarrer tout »). Ouvrir **http://rugbymeet.test**
   - Seul le dossier `public/` doit être la racine web. Si l'hôte virtuel automatique pointe sur la racine du projet,
     changer le `DocumentRoot` vers `.../rugbymeet/public`.
   - Sans hôte virtuel, `http://127.0.0.1/rugbymeet/public/` fonctionne aussi.

Comptes de démonstration (mot de passe `RugbyMeet2026!`) : `camille@rugbymeet.local` (supporter),
`taur@rugbymeet.local` (gérant), `admin@rugbymeet.local` (admin).

## Structure
```
public/            seul dossier accessible : index.php (point d'entrée), css/, js/, Images/, uploads/bars/
  css/             style.css (variables + thèmes), base.css (page, en-tête, pied), components.css (boutons, champs, panneaux),
                   puis un fichier par page : accueil, cards, filters, bar, pages, profil
app/Core/          Router, Controller, Database (PDO), Auth, Session, Csrf, helpers.php (fonctions utilitaires)
app/Controllers/   Home, Calendar, Bar, Page, Contact, Auth, Profile, Manager, Error
app/Models/        Club, Rencontre, Bar, Evenement, Utilisateur (requêtes préparées)
app/Views/         layout/ (top.php et bottom.php) + partials/ + une vue par page
config/            config.php (constantes : base, upload, contact) et routes.php
database/          rugbymeet.sql
storage/mails/     messages du formulaire de contact (mode local)
```

## Points à connaître
- Le formulaire de contact écrit un fichier dans `storage/mails/` tant que `SEND_MAIL` vaut `false`
  (Laragon n'envoie pas de vrais mails). Passer à `true` avec un serveur de mail configuré.
- En production : `DEBUG` à `false`, changer le mot de passe du compte admin, supprimer les comptes de démo.
- Thèmes : un club avec une valeur dans `club.theme` change les couleurs du site pour ses supporters
  (variable `--primary-color-ST` dans `public/css/style.css`; le logo change aussi, voir `site_logo()` dans `app/Core/helpers.php`).
