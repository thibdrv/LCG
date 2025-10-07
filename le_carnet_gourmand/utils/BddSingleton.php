<?php

	// Le principe de ce design pattern est de s'assurer que je n'aurai qu'une seule
	// instance de la classe BddSingleton dans toute mon application.
	// Pour faire ça, on va rendre le constructeur privé, il ne sera utilisable
	// qu'à l'intérieur de la classe avec le mécanisme d'encapsulation
	// on accèdera à l'unique instance de notre objet par "accès statique"
	// PS : ne pas confondre avec static en C qui est un concept totalement différent
	class BddSingleton {
		private static $_INSTANCE = null; // L'unique instance de ma classe

		private $pdo;

		private function __construct() {
			// TODO : coller les param de connexion bdd dans un fichier puis include
			$DSN = "mysql:host=localhost;port=3306;dbname=le_carnet_gourmand";
			try {
				// $this->pdo = new PDO($DSN, 'bge_blog_nrb', 'azerty-12');
				$this->pdo = new PDO($DSN,'root','',
					array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
				);
				// Activation des messages d'erreur PDO, car il n'est pas assez bavard
				$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch (PDOException $e) {
				error_log($e);
				http_response_code(500);
				die("DB Connection error: " . htmlspecialchars($e->getMessage()));
			}
		}

		// L'accès à mon singleton
		public static function getInstance() : BddSingleton {
			// Ici on fait ce qu'on appelle une lazy initialization
			// Je crée à la volée en gros
			if ( is_null(self::$_INSTANCE) ) {
				self::$_INSTANCE = new BddSingleton();
			}
			return self::$_INSTANCE;
		}

		public function getPdo() : PDO {
			return $this->pdo;
		}

		//    /** Empêche le clonage */
		// private function __clone() {}
		
		// TO DO bug postman si present
    	// 	/** Empêche la désérialisation */
    	// private function __wakeup() {}

		function __destruct() { // Appelé automatiquement à la destruction de mon singleton
			unset($this->pdo);
		}

	}
?>