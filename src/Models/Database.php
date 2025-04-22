<?php
//  Gérer une seule instance PDO dans toute l'application
class Database {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            // Paramètres de connexion
            $host     = 'localhost';
            $dbname   = 'stockflow';
            $username = 'root';
            $password = '';
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

            try {
                // Création de l'instance PDO
                self::$instance = new PDO($dsn, $username, $password);
            } catch (PDOException $e) {
                // En cas d’erreur, on stoppe tout
                die("Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
?>