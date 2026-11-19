<?php
/**
 * config/database.php
 * Connexion PDO sécurisée (patron Singleton).
 * Adaptez DB_HOST, DB_NAME, DB_USER et DB_PASS à votre installation XAMPP / WAMP.
 */

const DB_HOST = 'localhost';
const DB_NAME = 'agrinova';
const DB_USER = 'root';
const DB_PASS = '';

class Database
{
    private static ?PDO $pdo = null;

    // Constructeur privé : on ne peut pas faire "new Database()"
    private function __construct()
    {
    }

    public static function getConnexion(): PDO
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false, // vraies requêtes préparées (anti injection SQL)
                    ]
                );
            } catch (PDOException $e) {
                // On n'affiche jamais le détail technique à l'utilisateur
                error_log('Erreur de connexion BD : ' . $e->getMessage());
                http_response_code(500);
                die('Erreur : impossible de se connecter à la base de données. Vérifiez config/database.php.');
            }
        }
        return self::$pdo;
    }
}
