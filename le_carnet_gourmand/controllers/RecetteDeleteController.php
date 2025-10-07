<?php

    require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/entities/Recette.php");
    require_once(ROOT . "/services/CategorieService.php");
    require_once(ROOT . "/services/CategorieRecetteService.php");

class RecetteDeleteController extends AbstractController implements IController {
    private RecetteService $service;

    public function __construct(array $form) {
        parent::__construct($form);
        $this->service = new RecetteService();
    }

    protected function checkForm() {
        if (!isset($this->form['pk_recette'])) {
            throw new HttpStatusException("Le champ 'pk_recette' est obligatoire", 400);
        }
    }

        protected function checkCybersec() {}

    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté pour modifier une recette.");
        }
    }

    protected function processRequest() {
        $this->service->delete((int)$this->form['pk_recette']);

        $this->response = [
            "message" => "Recette supprimée avec succès",
            "id" => (int)$this->form['pk_recette']
        ];
    }
}
?>