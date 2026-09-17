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
            throw new HttpStatusException("Vous devez être connecté.", 401);
        }
    }

    protected function processRequest() {
        $pkRecette = $this->form["pk_recette"] ?? null;

        if ($pkRecette !== null) {
            $estFavori = $this->service->estFavori(getComptePkFromSession(), (int)$pkRecette);
            $this->response = [
                "success" => true,
                "estFavori" => $estFavori
            ];
            return;
        }

        $this->response = [
            "success" => true,
            "favoris" => $this->service->findByCompte(getComptePkFromSession())
        ];
    }
}
?>