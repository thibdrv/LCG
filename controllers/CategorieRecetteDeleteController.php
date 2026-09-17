<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/CategorieRecette.php");
require_once(ROOT . "/services/CategorieRecetteService.php");


class CategorieRecetteDeleteController extends AbstractController implements IController
{
    private CategorieRecetteService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new CategorieRecetteService();
    }


    protected function checkForm() {
        if (!isset($this->form['fk_recette'], $this->form['fk_categorie'])) {
            throw new HttpStatusException("fk_recette et fk_categorie sont obligatoires pour supprimer une association", 400);
        }
    }

    protected function checkCybersec() {
        if (!ctype_digit((string)$this->form['fk_recette']) || !ctype_digit((string)$this->form['fk_categorie'])) {
            throw new HttpStatusException("fk_recette et fk_categorie doivent être des entiers", 400);
        }
    }

    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté.");
        }
    }

    protected function processRequest() {
        $pk = [
            'fk_recette' => (int)$this->form['fk_recette'],
            'fk_categorie' => (int)$this->form['fk_categorie']
        ];

        $this->service->delete($pk);
        $this->response = ["message" => "Association recette / catégorie supprimée"];
    }
}
?>