<?php

// Le principe de ce design pattern est de s'assurer que je n'aurai qu'une seule
// instance de la classe BddSingleton dans toute mon application.
// Pour faire ça, on va rendre le constructeur privé, il ne sera utilisable
// qu'à l'intérieur de la classe avec le mécanisme d'encapsulation
// on accèdera à l'unique instance de notre objet par "accès statique"

class BddSingleton {
    private static $_INSTANCE = null;
    private $pdo;

    // Configuration de la base de données
    private const DB_HOST = 'mysql-bgetest.alwaysdata.net';
    private const DB_PORT = '3306';
    private const DB_NAME = 'bgetest_lcg';
    private const DB_USER = 'bgetest';
    private const DB_PASS = 'xz1kyemx';
    private const DB_CHARSET = 'utf8';

    private function __construct() {
        $dsn = "mysql:host=" . self::DB_HOST . ";port=" . self::DB_PORT . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
        try {
            $this->pdo = new PDO(
                $dsn,
                self::DB_USER,
                self::DB_PASS,
                array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch (PDOException $e) {
            error_log("DB Connection error: " . $e->getMessage());
            http_response_code(500);
            die("Erreur de connexion à la base de données.");
        }
    }

    public static function getInstance(): BddSingleton {
        if (is_null(self::$_INSTANCE)) {
            self::$_INSTANCE = new BddSingleton();
        }
        return self::$_INSTANCE;
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }

    private function __clone() {}
    public function __wakeup() {}

    function __destruct() {
        unset($this->pdo);
    }
}
?>
