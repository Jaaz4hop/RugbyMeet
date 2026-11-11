<?php
namespace App\Core;

use PDO;

/** Connexion PDO unique (requêtes préparées partout). */
class Database {
    //Une seule connexion PDO pour tout le site (null tant qu'elle n'est pas créée)
    private static ?PDO $pdo = null;

    //On crée la connexion à la première demande, ensuite on réutilise la même (principe du singleton)
    public static function pdo(): PDO {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                DB_DSN,
                DB_USER,
                DB_PASS,
                [
                    //Les erreurs SQL lancent une exception au lieu d'être silencieuses
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    //Les résultats sont des tableaux associatifs : $ligne['nom']
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    //Vraies requêtes préparées : protège contre les injections SQL
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            //Uniquement pour mes tests en SQLite : active les clés étrangères (MySQL le fait déjà)
            if (self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                self::$pdo->exec('PRAGMA foreign_keys = ON');
            }
        }
        return self::$pdo;
    }
}
