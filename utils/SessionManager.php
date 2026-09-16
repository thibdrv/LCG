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

    public static function manageSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => 'localhost',
                'secure' => false, // Mettre à true en production avec HTTPS
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
        self::initSession();
        if (self::isSessionExpired()) {
            self::reinitSession();
        }
    }

    public static function initSession(): void {
        if (!isset($_SESSION[self::START_TIME])) {
            $_SESSION[self::START_TIME] = time();
        } else if (self::isLogged()) {
            $_SESSION[self::START_TIME] = time();
        }
    }

    public static function isSessionExpired(): bool {
        return isset($_SESSION[self::START_TIME]) && 
               ($_SESSION[self::START_TIME] + self::getMaxTime()) < time();
    }

    public static function reinitSession(): void {
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        self::initSession();
    }

    public static function isLogged(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION[self::START_TIME]) && 
               isset($_SESSION[self::PK_COMPTE]) && 
               $_SESSION[self::PK_COMPTE] !== null;
    }

    public static function createISessionInfoProvider(): ISessionInfoProvider {
        return new BaseSessionInfoProvider();
    }

    public static function getComptePkFromSession(): ?int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return self::isLogged() ? (int)$_SESSION[self::PK_COMPTE] : null;
    }

    public static function getCompteFromSession(): ?Compte {
        if (!self::isLogged()) {
            return null;
        }

        $compteService = new CompteService();
        $comptePk = self::getComptePkFromSession();
        try {
            return $compteService->findByPk($comptePk);
        } catch (HttpStatusException $e) {
            return null;
        }
    }

    public static function getRolePkFromSession(): ?int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return self::isLogged() ? (int)$_SESSION[self::FK_ROLE] : null;
    }

    public static function getMaxTime(): int {
        return 45 * 60; // 45 minutes
    }

    public static function login(int $pk): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);
        $_SESSION[self::PK_COMPTE] = $pk;
        $_SESSION[self::START_TIME] = time();
    }

    public static function logout(): void {
        self::reinitSession();
    }
}
?>