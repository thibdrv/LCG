<?php

    require_once(ROOT . "/exceptions/HttpStatusException.php");
    require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/services/IService.php");


class LogoutGetController extends AbstractController implements IController
{
    private CompteService $compteService;

    public function __construct(array $form)
    {
        parent::__construct($form, "LogoutGetController");
        $this->compteService = new CompteService();
    }

    public function getService() : IService {
        return $this->compteService;
    }

    protected function checkForm() {} // Pas de paramètres à vérifier

    protected function checkCybersec() {} // Pas de vérification de sécurité

    protected function checkRights() {} // Pas de vérification de droits

    protected function processRequest()
    {
        if (!isLogged()) {
        throw new HttpStatusException("Vous êtes déjà déconnecté", 400);
    }
        SessionManager::reinitSession();
        $this->response = ["status" => "success", "message" => "Déconnexion réussie"];
    }

    // Optionnel : Redéfinir processResponse pour s'assurer que la réponse est bien envoyée
    protected function processResponse()
    {
        header('Content-Type: application/json');
        return json_encode($this->response);
    }
}
?>