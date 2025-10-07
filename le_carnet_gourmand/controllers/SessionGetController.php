<?php

require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/utils/SessionManager.php");
require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/services/CompteService.php");

class SessionGetController implements IController
{
    private CompteService $compteService;
    private array $form;

    public function __construct(array $form)
    {
        $this->form = $form;
        $this->compteService = new CompteService();

        // ✅ Toujours s'assurer que la session est démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function execute(): string
    {
        $sessionState = new stdClass();     // créer une classe Standard

        // ✅ Utiliser l'opérateur null coalescent pour éviter les warnings
        $sessionState->starttime = $_SESSION[START_TIME] ?? null;
        $sessionState->endTime   = ($sessionState->starttime ?? time()) + SessionManager::getMaxTime();
        
        $sessionState->isLogged  = SessionManager::isLogged();

        if(SessionManager::isLogged())
        {
            $sessionInfo = new stdClass(); 
            $sessionInfo->pk = $_SESSION['pk_compte'];
            $sessionInfo->email = $_SESSION['email'];
            $sessionInfo->pseudo = $_SESSION['pseudo'];
            $sessionInfo->password = $_SESSION['mot_de_passe'];
            $sessionInfo->estSupprime = $_SESSION['est_supprime'];
            $sessionInfo->estBanni = $_SESSION['est_banni'];
            $sessionInfo->roleId = $_SESSION['fk_role'];

            $sessionState->userInfo = $sessionInfo;
        }
        return json_encode($sessionState);
    }

}
?>