<?php

require_once(ROOT . "/exceptions/HttpStatusException.php");
require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/utils/SessionManager.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/services/CompteService.php");

class SessionGetController extends AbstractController implements IController
{
    private CompteService $compteService;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->compteService = new CompteService();
    }

    protected function checkForm() {}
    protected function checkCybersec() {}
    protected function checkRights() {}

    protected function processRequest()
    {
        $startTime = $_SESSION[START_TIME] ?? null;

        $this->response = [
            "isLogged" => SessionManager::isLogged(),
            "startTime" => $startTime,
            "endTime"   => ($startTime ?? time()) + SessionManager::getMaxTime(),
        ];

        if (SessionManager::isLogged()) {
            $this->response["pk"]         = $_SESSION['pk_compte'];
            $this->response["email"]      = $_SESSION['email'];
            $this->response["pseudo"]     = $_SESSION['pseudo'];
            $this->response["estSupprime"] = $_SESSION['est_supprime'];
            $this->response["estBanni"]    = $_SESSION['est_banni'];
            $this->response["roleId"]      = $_SESSION['fk_role'];
            // pas de mot de passe ici
        }
    }
}
?>