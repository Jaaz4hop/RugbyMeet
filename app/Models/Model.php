<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use PDOStatement;

/** Base des modèles : un petit raccourci pour les requêtes préparées. */
//Classe mère des modèles : un modèle est la seule classe qui parle à la base de données (requêtes SQL)
abstract class Model {
    //Connexion à la base, récupérée dans le constructeur
    protected PDO $db;

    public function __construct() {
        $this->db = Database::pdo();
    }

    //Prépare puis exécute une requête : les ? sont remplacés par les valeurs de $params
    //C'est ce qui protège contre les injections SQL
    protected function run(string $sql, array $params = []): PDOStatement {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    //Retourne toutes les lignes du résultat (tableau de tableaux)
    protected function all(string $sql, array $params = []): array {
        return $this->run($sql, $params)->fetchAll();
    }

    //Retourne une seule ligne, ou null s'il n'y a aucun résultat
    protected function one(string $sql, array $params = []): ?array {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }
}
