<?php

	require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/entities/Favori.php");
	require_once(ROOT . "/services/FavoriService.php");

class FavoriGetController extends AbstractController implements IController
{
    private FavoriService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new FavoriService();
    }

    protected function checkForm() {
        // Ici, GET peut se limiter à lister les favoris du compte connecté
        // Donc pas de paramètres obligatoires
    }

    protected function checkCybersec() {
        // Pas grand chose à vérifier ici (tout vient de la session)
    }

    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté.");
        }
    }

    protected function processRequest() {
        $this->response = $this->service->findByCompte(getComptePkFromSession());
    }
}
?>