<?php

    require_once(ROOT . "/utils/sessioninfo/BaseSessionInfoProvider.php");
    require_once(ROOT . "/utils/sessioninfo/ISessionInfoProvider.php");
    require_once(ROOT . "/entities/Compte.php");
    require_once(ROOT . "/services/CompteService.php");
    require_once(ROOT . "/exceptions/HttpStatusException.php");


class SessionManager {
    private static ?SessionManager $_INSTANCE = null;

    const START_TIME = "start_time";
    const PK_COMPTE = "pk_compte";
    const EMAIL = "email";
    const PSEUDO = "pseudo";
    const MOTDEPASSE = "mot_de_passe";
    const DATE_CREATION = "date_creation";
    const EST_SUPPRIME = "est_supprime";
    const EST_BANNI = "est_banni";
    const FK_ROLE = "fk_role";

    private function __construct() {}


    public static function getInstance(): SessionManager {
        if (is_null(self::$_INSTANCE)) {
            self::$_INSTANCE = new SessionManager();
        }
        return self::$_INSTANCE;
    }

	public static function manageSession(): void
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_set_cookie_params([
				'lifetime' => 0, // La session expire à la fermeture du navigateur
				'path' => '/',
				'domain' => 'localhost',
				'secure' => true, // Mettez à false si vous n'avez pas de HTTPS
				'httponly' => true,
				'samesite' => 'Lax' // Permet les cookies cross-site
			]);
			session_start();
		}
		error_log("Session ID: " . session_id());
		error_log("Session: " . print_r($_SESSION, true));
		self::initSession();
		if (self::isSessionExpired()) {
			self::reinitSession();
		}
	}

    // Initaialise le temps de session
    public static function initSession(): void 
    { // on va créer notre propre timeout de session
        if (!isset($_SESSION[self::START_TIME])) {
            $_SESSION[self::START_TIME] = time();
        } else if (self::isLogged()) { // l'utilisateur est logué, on rajoute du temps
            $_SESSION[self::START_TIME] = time();
        }
    }

    public static function isSessionExpired(): bool {
        return isset($_SESSION[self::START_TIME]) && ($_SESSION[self::START_TIME] + self::getMaxTime()) < time();
    }

    // Redémarrer une nouvelle session
    public static function reinitSession(): void {
        error_log("reinitSession() - Destruction de la session...");
        session_destroy();
        session_start();
        self::initSession();
    }

    // Vérifier si l'utilisateur est logué
    public static function isLogged(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION[self::START_TIME]) && isset($_SESSION[self::PK_COMPTE]);
    }

    // On centralise ici la création de l'objet, au lieu de l'instancier partout dans le code
    public static function createISessionInfoProvider():ISessionInfoProvider {
         // Cette classe implémente ISessionInfoProvider et sait lire pk_compte, fk_role, email depuis $_SESSION
        return new BaseSessionInfoProvider();
    }

    public static function getComptePkFromSession(): ?int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return self::isLogged() ? $_SESSION[self::PK_COMPTE] : null;
    }

	public static function getCompteFromSession(): ?Compte
	{
		if (!self::isLogged()) {
			return null;
		}

		$compteService = new CompteService();
		$comptePk = self::getComptePkFromSession();
		return $compteService->findByPkForSession($comptePk);
	}
    public static function getRolePkFromSession(): ?int {
        return self::isLogged() ? $_SESSION[self::FK_ROLE] : null;
    }

    // délai maximum autorisé pour la session, exprimé en secondes
    public static function getMaxTime(): int {
        return 45 * 60; // 45 minutes
    }

    public static function login(int $pk): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Regénérer l'ID de session pour éviter fixation
        session_regenerate_id(true);

        // Debug : log avant/après écriture
        error_log("SessionManager::login - before set: " . print_r($_SESSION, true));

        $_SESSION[self::PK_COMPTE] = $pk;

        error_log("SessionManager::login - after set: " . print_r($_SESSION, true));
    }

    public static function logout(): void {
        self::reinitSession();
    }
}
?>